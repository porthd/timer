<?php

declare(strict_types=1);

namespace Porthd\Timer\EventListener;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2026 Dr. Dieter Porth <info@mobger.de>
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
use Porthd\Timer\CustomTimer\DefaultTimer;
use Porthd\Timer\Utilities\TcaUtility;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;

// PURPOSE: Resolve the flexform data structure of the `tx_timer_timer` field
// from the timer selected in `tx_timer_selector`. TYPO3 14 removed the TCA
// option `ds_pointerField` (record-value based ds selection); the documented
// replacement are these two FlexFormTools events.
//
// ADVANTAGES:
//   - Works identically on TYPO3 13 and 14 (events exist since v12), so the
//     dual-core constraint of the extension stays intact.
//   - Keeps the dynamic timer list (custom timers via extension settings)
//     as single source for the ds mapping.
//
// PRECONDITIONS (data requirements):
//   - TcaUtility::mergeNameFlexformArray() always contains the key of
//     DefaultTimer::TIMER_NAME as fallback.
//
// EDGE CASES:
//   - New records (selector not yet set) fall back to the default timer.
//   - Unknown selector values (e.g. timer disabled meanwhile) fall back to
//     the default timer instead of breaking the backend form.
final class FlexFormDsPointerResolver
{
    private const IDENTIFIER_TYPE = 'txTimerDsPointer';

    public function resolveIdentifier(BeforeFlexFormDataStructureIdentifierInitializedEvent $event): void
    {
        if ($event->getFieldName() !== TimerConst::TIMER_FIELD_FLEX_ACTIVE) {
            return;
        }
        $row = $event->getRow();
        $selector = (string)($row[TimerConst::TIMER_FIELD_SELECTOR] ?? '');
        if ($selector === '') {
            $selector = DefaultTimer::TIMER_NAME;
        }
        $event->setIdentifier([
            'type' => self::IDENTIFIER_TYPE,
            'dataStructureKey' => $selector,
        ]);
    }

    public function provideDataStructure(BeforeFlexFormDataStructureParsedEvent $event): void
    {
        $identifier = $event->getIdentifier();
        if (($identifier['type'] ?? '') !== self::IDENTIFIER_TYPE) {
            return;
        }
        $map = TcaUtility::mergeNameFlexformArray();
        $dataStructure = $map[$identifier['dataStructureKey'] ?? '']
            ?? $map[DefaultTimer::TIMER_NAME]
            ?? null;
        if ($dataStructure !== null) {
            $event->setDataStructure($dataStructure);
        }
    }
}
