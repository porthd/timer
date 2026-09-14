<?php

declare(strict_types=1);

namespace Porthd\Timer\Backend;

use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\CustomTimer\DefaultTimer;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Utility\MathUtility;

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
                    $item = $this->normalizeTimestampDefault($item);
                }
                if ((array_key_exists('type', $item)) &&
                    ($item['type'] === 'datetime') &&
                    array_key_exists(DefaultTimer::TIMER_NAME, $item)
                ) {
                    $item = $this->normalizeTimestampDefault($item);
                }
                $result[$key] = $item;
            }
        }
        return $result;
    }

    /**
     * Normalize a FlexForm datetime `default` into the integer UTC timestamp that the
     * TYPO3 v13 FormEngine expects.
     *
     * @param array<mixed> $item the parsed FlexForm `config` array of a single field
     * @return array<mixed>
     */
    protected function normalizeTimestampDefault(array $item): array
    {
        // PURPOSE: Hand DatetimeElement a clean integer UTC timestamp as the FlexForm
        //          datetime default, so TYPO3 v13 renders it as a valid ISO-8601 value.
        //
        // PRECONDITIONS (data requirements):
        //   - $item[DefaultTimer::TIMER_NAME] is either a UNIX timestamp (e.g. "32535212340")
        //     or a human-readable date string.
        //
        // EDGE CASES:
        //   - A bare timestamp string MUST be read with the '@' prefix; `new \DateTime("32535212340")`
        //     misparses it as a calendar string (year 5212). Together with the previous
        //     'Y-m-d H:i:s' output (space instead of ISO-8601 'T') this froze v13's
        //     form-engine-validation.js and hung the edit form on the spinner.
        //   - An integer timestamp is left untouched: DatetimeElement converts it to a proper
        //     ISO-8601 string (with 'T') itself, respecting the timezone offset.
        if (!array_key_exists(DefaultTimer::TIMER_NAME, $item)) {
            return $item;
        }
        $default = $item[DefaultTimer::TIMER_NAME];
        try {
            if (MathUtility::canBeInterpretedAsInteger($default)) {
                $item[DefaultTimer::TIMER_NAME] = (int)$default;
            } else {
                $item[DefaultTimer::TIMER_NAME] = (new \DateTime((string)$default))->getTimestamp();
            }
        } catch (\Exception $e) {
            // do nothing — keep the original default untouched
        }
        return $item;
    }

}
