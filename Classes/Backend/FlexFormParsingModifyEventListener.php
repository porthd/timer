<?php

declare(strict_types=1);

namespace Porthd\Timer\Backend;


use DateTime;
use Exception;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\Interfaces\TimerInterface;
use Porthd\Timer\Utilities\DateTimeUtility;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;

final class FlexFormParsingModifyEventListener
{

    public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();

        if (($identifier['type'] === 'tca') &&
            ($identifier['fieldName'] === TimerConst::TIMER_FIELD_FLEX_ACTIVE)
        ) {
            $parsedDataStructure = $event->getDataStructure();
            if (empty($parsedDataStructure['dataStructureKey'])) {
                $parsedDataStructure['dataStructureKey'] = DefaultTimer::TIMER_NAME;
            }
            $parsedDataStructure = $this->recursiveDefaultAnalytic($parsedDataStructure);
            $event->setDataStructure($parsedDataStructure);
        }
    }

    /**
     * @param array<mixed> $dataStructure
     * @return array<mixed>
     */
    protected function recursiveDefaultAnalytic(array $dataStructure)
    {
        if (empty($dataStructure)) {
            return [];
        }
        $result = [];
        foreach ($dataStructure as $key => $item) {
            if ($key !== 'config') {
                if (is_array($item)) {
                    $result[$key] = $this->recursiveDefaultAnalytic($item);
                } else {
                    $result[$key] = $item;
                }
            } else {
                if ((array_key_exists('renderType', $item)) &&
                    ($item['renderType'] === 'inputDateTime') &&
                    array_key_exists(DefaultTimer::TIMER_NAME, $item)
                ) {
                    try {
                        // remark probelm with Format and timezone
                        // https://stackoverflow.com/questions/32109936/php-datetime-format-does-not-respect-timezones
                        $dateValue = new DateTime($item[DefaultTimer::TIMER_NAME]);
                        if ($dateValue !== false) {
                            $item[DefaultTimer::TIMER_NAME] = DateTimeUtility::formatForZone($dateValue, TimerInterface::TIMER_FORMAT_DATETIME);
                        }
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
                if ((array_key_exists('type', $item)) &&
                    ($item['type'] === 'datetime') &&
                    array_key_exists(DefaultTimer::TIMER_NAME, $item)
                ) {
                    try {
                        // remark probelm with Format and timezone
                        // https://stackoverflow.com/questions/32109936/php-datetime-format-does-not-respect-timezones
                        $dateValue = new DateTime($item[DefaultTimer::TIMER_NAME]);
                        if ($dateValue !== false) {
                            $item[DefaultTimer::TIMER_NAME] = DateTimeUtility::formatForZone($dateValue, TimerInterface::TIMER_FORMAT_DATETIME);
                        }
                    } catch (Exception $e) {
                        // do nothing
                    }
                }
                $result[$key] = $item;
            }
        }
        return $result;
    }

}
