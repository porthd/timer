<?php

declare(strict_types=1);

namespace Porthd\Timer\Services;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2023 Dr. Dieter Porth <info@mobger.de>
 *
 *  All rights reserved
 *
 *  This script is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use Porthd\Timer\Utilities\CustomTimerUtility;
use ResourceBundle;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class TcaService
{
    /**
     * @param string $input
     * @return string
     */
    private function translate(string $input): string
    {
        return $this->getLanguageService()->sL($input);
    }

    /**
     * @return LanguageService
     */
    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected const CONFIG_PATH_VARIABLE = 'yamlHoliydayList';
    protected const YAML_CALENDAR_DATE_REL = 'calendarDateRel';
    protected const FLEXFORM_SELECT_ITEMS = 'items';
    protected const YAML_CALLIST_ITEM_EVENTTITLE = 'eventtitle';
    protected const YAML_CALLIST_ITEM_IDENTIFIER = 'identifier';

    /**
     * used in flexform in itemsProcFunc
     *
     * get all select-option defined for a flexform-definition for the TYPO3-backend from a Yaml-file,
     * defined in the extension-constants
     *
     * @param array<mixed> $params
     * @return void
     * @throws TimerException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function selectCalendarByYamlFileFromExtConstants(&$params): void
    {
        // Parts of code, which can by the extension-constants be controlled
        $timerConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get(TimerConst::EXTENSION_NAME);
        $pathYamlHolidayList = $timerConfig[self::CONFIG_PATH_VARIABLE];
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $yamlList = CustomTimerUtility::readListFromFileOrUrl($pathYamlHolidayList, $yamlFileLoader);
        if (array_key_exists(self::YAML_CALENDAR_DATE_REL, $yamlList)) {
            $yamlCalendarList = $yamlList[self::YAML_CALENDAR_DATE_REL];
        } else {
            $yamlCalendarList = $yamlList;
        }
        array_walk($yamlCalendarList, function (&$value, $key) {
            $value = [
                (
                    (strpos($value[self::YAML_CALLIST_ITEM_EVENTTITLE], 'LLL:') === 0) ?
                        ucfirst(
                            $this->translate($value[self::YAML_CALLIST_ITEM_EVENTTITLE])
                        ) :
                        $value[self::YAML_CALLIST_ITEM_IDENTIFIER]
                ),
            ];
        });
        $refKey = 0;
        usort($yamlCalendarList, function ($a, $b) use ($refKey) {
            return $a[$refKey] <=> $b[$refKey];
        });
        $params[self::FLEXFORM_SELECT_ITEMS] = array_merge($params[self::FLEXFORM_SELECT_ITEMS], $yamlCalendarList);
    }

    /**
     * used in flexform in itemsProcFunc
     *
     * get all locales-definitions of the ICU-Datebase in a sorted simple way
     * (big stuff)
     *
     * @param array<mixed> $params
     * @return void
     */
    public function selectOptionsForLocalesByPhpIntlExtension(array &$params): void
    {
        $bundle = new ResourceBundle('', 'ICUDATA');
        $locales = array_filter(
            $bundle->getLocales('')
        );
        sort($locales);
        foreach ($locales as $item) {
            $params[self::FLEXFORM_SELECT_ITEMS][] = [$item, $item];
        }
    }
}
