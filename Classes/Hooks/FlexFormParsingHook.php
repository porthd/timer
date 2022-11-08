<?php

namespace Porthd\Timer\Hooks;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Dr. Dieter Porth <info@mobger.de>
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

use Porthd\Checker\Interfaces\DataDiggerOnAlternativeWayInterface;
use Porthd\Checker\Utility\Repository\GeneralRepository;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Utilities\TcaUtility;


class FlexFormParsingHook
{

    /**
     * @param $identifierArray
     * @return mixed|string
     */
    public function parseDataStructureByIdentifierPreProcess(&$identifierArray) {
        $result= '';
        if (($identifierArray['fieldName'] === 'tx_timer_timer') && ($identifierArray['type'] === 'tca')) {
            $list = TcaUtility::mergeNameFlexformArray();
            if (empty($identifierArray['dataStructureKey'])) {
                $identifierArray['dataStructureKey'] = 'default';
            }
            if (in_array($identifierArray['dataStructureKey'],$list)) {
                $result = $list[$identifierArray['dataStructureKey']];
            }
        }
        return $result;
    }

}