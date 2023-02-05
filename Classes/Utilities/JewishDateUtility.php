<?php

namespace Porthd\Timer\Utilities;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Porthd\Timer\Constants\JewishHolidayConst;
use Porthd\Timer\Constants\TimerConst;
use Porthd\Timer\Exception\TimerException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/***************************************************************
 *
 *  code inspired by https://www.david-greve.de/luach-code/jewish-php.html visited 20221205
 *
 *  Copyright notice
 *
 *  (c) 2022 original code refactored and extended by Dr. Dieter Porth <info@mobger.de>
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

/**
 *
 */
class JewishDateUtility extends JewishHolidayConst
{
    /**
     * @param int $year
     * @return bool
     */
    public static function isJewishLeapYear(int $year)
    {
        return in_array(($year % 19), [0, 3, 6, 8, 11, 14, 17]);
    }

    /**
     * @param int $jewishMonth
     * @param int $jewishYear
     * @return string
     */
    public static function getJewishMonthName(int $jewishMonth, int $jewishYear)
    {
        return ((self::isJewishLeapYear($jewishYear)) ?
            self::JEWISH_MONTH_NAMES_LEAP[($jewishMonth - 1)] :
            self::JEWISH_MONTH_NAMES_NON_LEAP[($jewishMonth - 1)]
        );
    }

    /**
     * 2022-12-27 not in use now
     *
     * @param int $month
     * @param int $day
     * @param int $year
     * @return bool
     */
    public static function isIsraeliDaylightSavingsTime(int $month, int $day, int $year): bool
    {
        // Get Jewish year of Yom Kippur in the passed Gregorian year
        $jdCur = gregoriantojd(12, 31, $year);
        $jewishCur = jdtojewish($jdCur);
        [$jewishCurMonth, $jewishCurDay, $jewishCurYear] = explode("/", $jewishCur, 3);
        $jewishCurYearNum = (int)$jewishCurYear;

        // Get Last Friday before 2nd of April
        $jdDSTBegin = gregoriantojd(4, 2, $year); // 2 April
        $jdDSTBegin--; // get the day before 2nd of April
        while (jddayofweek($jdDSTBegin, 0) !== 5) { // gets the weekday, 5 = Friday
            $jdDSTBegin--;
        } // counts to the previous day until Friday

        // Get Sunday between Rosh Hashana and Yom Kippur
        // Take the first Sunday on or after 3rd of Tishri
        $jdDSTEnd = jewishtojd(1, 3, $jewishCurYearNum);
        while (jddayofweek($jdDSTEnd, 0) !== 0) { // gets the weekday, 0 = Sunday
            $jdDSTEnd++;
        } // counts to the next day until Sunday

        // Check if the current date is between the start and end date ...
        $jdCurrent = gregoriantojd($month, $day, $year);
        return (
            ($jdCurrent >= $jdDSTBegin) &&
            ($jdCurrent < $jdDSTEnd)
        );
    }

    /**
     * format an array with jewish-date-informationen from DateTime-Object with similiar values, which are
     * used for the different tokens/chars in the format-string for the format-method of the datTime-object
     *
     * @param mixed $date
     * @param string $format
     * @return array<mixed>
     * @throws TimerException
     */
    public static function formatJewishDateFromDateTime($date, string $format): array
    {
        if ((!$date instanceof DateTime) && (!$date instanceof DateTimeImmutable)) {
            throw new TimerException(
                'The first parameter must be an instance of `dateTime` or `DateTimeImmutable`. ' .
                'The variable is an other type: ' . print_r($date, true) . '.',
                1601994931
            );
        }

        // Build pattern for preg_replace
        $dateTimeParts = [];
        foreach ([
                     'w',
                     'D',
                     'a',
                     'A',
                     'g',
                     'G',
                     'h',
                     'H',
                     'i',
                     's',
                     'Y',
                     'y',
                     'n',
                     'm',
                     'M',
                     'F',
                     'd',
                     't',
                     'e',
                     'I',
                     'O',
                     'P',
                     'p',
                     'T',
                     'Z',
                     'U',
                 ] as $itemFormat) {
            $dateTimeParts[$itemFormat] = $date->format($itemFormat);
        }
        // change entry for jewish-date
        $jdNumber = gregoriantojd(
            ((int)$dateTimeParts['n']),
            ((int)$dateTimeParts['d']),
            ((int)$dateTimeParts['Y'])
        );
        $jewishDate = jdtojewish($jdNumber);
        $jewishWeekday = jddayofweek($jdNumber, 0);
        [$jewishMonth, $jewishDay, $jewishYear] = explode('/', $jewishDate);
        $jewishMonthInt = (int)$jewishMonth;
        $jewishYearInt = (int)$jewishYear;
        $jewishYearShort = (string)($jewishYearInt % 100);
        $dateTimeParts['t'] = cal_days_in_month(CAL_JEWISH, $jewishMonthInt, $jewishYearInt);
        $dateTimeParts['w'] = (int)$jewishWeekday;
        $dateTimeParts['D'] = 'viewhelper.jewishMonth.' . $jewishWeekday . '.day';
        $dateTimeParts['j'] = (int)$jewishDay;
        $dateTimeParts['d'] = str_pad($jewishDay, 2, '0', STR_PAD_LEFT);
        $dateTimeParts['y'] = str_pad($jewishYearShort, 2, '0', STR_PAD_LEFT);
        $dateTimeParts['Y'] = $jewishYear;
        $dateTimeParts['n'] = $jewishMonth;
        $dateTimeParts['m'] = str_pad($jewishMonth, 2, '0', STR_PAD_LEFT);
        $dateTimeParts['M'] = JewishDateUtility::getJewishMonthName($jewishMonthInt, $jewishYearInt);
        $dateTimeParts['F'] = 'viewhelper.jewishMonth.' . $dateTimeParts['M'];
        $dateTimeParts['D'] = LocalizationUtility::translate(
            'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:' . $dateTimeParts['D'],
            TimerConst::EXTENSION_NAME
        ); // translation of weekday-name
        $dateTimeParts['F'] = LocalizationUtility::translate(
            'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:' . $dateTimeParts['F'],
            TimerConst::EXTENSION_NAME
        ); // translation month

        return $dateTimeParts;
    }


    /**
     * get the holyday for an given date
     * if no holiday, the return an empty array
     * there may be two holidays on the same day
     *
     * @param int $jdCurrent a Julian day number
     * @param bool $isDiaspora
     * @param bool $postponeShushanPurimOnSaturday
     * @return array<int,string>
     */
    public static function getJewishHolidayOnJulianDayNumber(
        int $jdCurrent,
        bool $isDiaspora,
        bool $postponeShushanPurimOnSaturday
    ): array {
        $result = [];
        $jewishDate = jdtojewish($jdCurrent);
        [$jewishMonth, $jewishDay, $jewishYear] = explode('/', $jewishDate);
        $jewishMonth = (int)$jewishMonth;
        $jewishDay = (int)$jewishDay;
        $jewishYear = (int)$jewishYear;
        // Holidays in Elul
        if ($jewishDay == 29 && $jewishMonth == self::ELUL) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ErevRoshHashanah'
            );
        }

        // Holidays in Tishri
        if ($jewishDay == 1 && $jewishMonth == self::TISHRI) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.RoshHashanahI'
            );
        }
        if ($jewishDay == 2 && $jewishMonth == self::TISHRI) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.RoshHashanahII'
            );
        }
        $jd = jewishtojd(self::TISHRI, 3, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 3 Tishri would fall on Saturday ...
            // ... postpone Tzom Gedaliah to Sunday
            if ($jewishDay == 4 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TzomGedaliah'
                );
            }
        } else {
            if ($jewishDay == 3 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TzomGedaliah'
                );
            }
        }
        if ($jewishDay == 9 && $jewishMonth == self::TISHRI) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ErevYomKippur'
            );
        }
        if ($jewishDay == 10 && $jewishMonth == self::TISHRI) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomKippur'
            );
        }
        if ($jewishDay == 14 && $jewishMonth == self::TISHRI) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ErevSukkot'
            );
        }
        if ($jewishDay == 15 && $jewishMonth == self::TISHRI) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.SukkotI'
            );
        }
        if ($jewishDay == 16 && $jewishMonth == self::TISHRI && $isDiaspora) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.SukkotII_DiasporOnly'
            );
        }
        if ($isDiaspora) {
            if ($jewishDay >= 17 && $jewishDay <= 20 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HolHamoedSukkot_inIsraelInDiaspora'
                );
            }
        } else {
            if ($jewishDay >= 16 && $jewishDay <= 20 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HolHamoedSukkot_inIsraelInDiaspora'
                );
            }
        }
        if ($jewishDay == 21 && $jewishMonth == self::TISHRI) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HoshanaRabbah'
            );
        }
        if ($isDiaspora) {
            if ($jewishDay == 22 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.SheminiAzeret_inDiaspora'
                );
            }
            if ($jewishDay == 23 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.SimchatTorah_inDiaspora'
                );
            }
            if ($jewishDay == 24 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.IsruChagTishri_inDiaspora'
                );
            }
        } else {
            if ($jewishDay == 22 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.SheminiAzeretSimchatTorah_inIsrael'
                );
            }
            if ($jewishDay == 23 && $jewishMonth == self::TISHRI) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.IsruChagTishri_inIsrael'
                );
            }
        }

        // Holidays in Kislev/Tevet
        $hanukkahStart = jewishtojd(self::KISLEV, 25, $jewishYear);
        $hanukkahNo = (int)($jdCurrent - $hanukkahStart + 1);
        if ($hanukkahNo == 1) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahI'
            );
        }
        if ($hanukkahNo == 2) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahII'
            );
        }
        if ($hanukkahNo == 3) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahIII'
            );
        }
        if ($hanukkahNo == 4) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahIV'
            );
        }
        if ($hanukkahNo == 5) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahV'
            );
        }
        if ($hanukkahNo == 6) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahVI'
            );
        }
        if ($hanukkahNo == 7) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahVII'
            );
        }
        if ($hanukkahNo == 8) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HanukkahVIII'
            );
        }

        // Holidays in Tevet
        $jd = jewishtojd(self::TEVET, 10, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 10 Tevet would fall on Saturday ...
            // ... postpone Tzom Tevet to Sunday
            if ($jewishDay == 11 && $jewishMonth == self::TEVET) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TzomTevet'
                );
            }
        } else {
            if ($jewishDay == 10 && $jewishMonth == self::TEVET) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TzomTevet'
                );
            }
        }

        // Holidays in Shevat
        if ($jewishDay == 15 && $jewishMonth == self::SHEVAT) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TuBShevat'
            );
        }

        // Holidays in Adar I
        if (self::isJewishLeapYear($jewishYear) && $jewishDay == 14 && $jewishMonth == self::ADAR_I) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.PurimKatan'
            );
        }
        if (self::isJewishLeapYear($jewishYear) && $jewishDay == 15 && $jewishMonth == self::ADAR_I) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ShushanPurimKatan'
            );
        }

        // Holidays in Adar or Adar II
        if (self::isJewishLeapYear($jewishYear)) {
            $purimMonth = self::ADAR_II;
        } else {
            $purimMonth = self::ADAR;
        }
        $jd = jewishtojd($purimMonth, 13, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 13 Adar or Adar II would fall on Saturday ...
            // ... move Ta'anit Esther to the preceding Thursday
            if ($jewishDay == 11 && $jewishMonth == $purimMonth) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TaAnithEsther'
                );
            }
        } else {
            if ($jewishDay == 13 && $jewishMonth == $purimMonth) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TaAnithEsther'
                );
            }
        }
        if ($jewishDay == 14 && $jewishMonth == $purimMonth) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.Purim'
            );
        }
        if ($postponeShushanPurimOnSaturday) {
            $jd = jewishtojd($purimMonth, 15, $jewishYear);
            $weekdayNo = jddayofweek($jd, 0);
            if ($weekdayNo == self::SATURDAY) { // If the 15 Adar or Adar II would fall on Saturday ...
                // ... postpone Shushan Purim to Sunday
                if ($jewishDay == 16 && $jewishMonth == $purimMonth) {
                    $result[] = LocalizationUtility::translate(
                        'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ShushanPurim'
                    );
                }
            } else {
                if ($jewishDay == 15 && $jewishMonth == $purimMonth) {
                    $result[] = LocalizationUtility::translate(
                        'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ShushanPurim'
                    );
                }
            }
        } else {
            if ($jewishDay == 15 && $jewishMonth == $purimMonth) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ShushanPurim'
                );
            }
        }

        // Holidays in Nisan
        $shabbatHagadolDay = 14;
        $jd = jewishtojd(self::NISAN, $shabbatHagadolDay, $jewishYear);
        while (jddayofweek($jd, 0) != self::SATURDAY) {
            $jd--;
            $shabbatHagadolDay--;
        }
        if ($jewishDay == $shabbatHagadolDay && $jewishMonth == self::NISAN) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ShabbatHagadol'
            );
        }
        if ($jewishDay == 14 && $jewishMonth == self::NISAN) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ErevPesach'
            );
        }
        if ($jewishDay == 15 && $jewishMonth == self::NISAN) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.PesachI'
            );
        }
        if ($jewishDay == 16 && $jewishMonth == self::NISAN && $isDiaspora) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.PesachII_DiasporOnly'
            );
        }
        if ($isDiaspora) {
            if ($jewishDay >= 17 && $jewishDay <= 20 && $jewishMonth == self::NISAN) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HolHamoedPesach_inDiaspora'
                );
            }
        } else {
            if ($jewishDay >= 16 && $jewishDay <= 20 && $jewishMonth == self::NISAN) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.HolHamoedPesach_inIsrael'
                );
            }
        }
        if ($jewishDay == 21 && $jewishMonth == self::NISAN) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.PesachVII'
            );
        }
        if ($jewishDay == 22 && $jewishMonth == self::NISAN && $isDiaspora) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.PesachVIII_DiasporOnly'
            );
        }
        if ($isDiaspora) {
            if ($jewishDay == 23 && $jewishMonth == self::NISAN) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.IsruChagNisan_inDiaspora'
                );
            }
        } else {
            if ($jewishDay == 22 && $jewishMonth == self::NISAN) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.IsruChagNisan_inIsrael'
                );
            }
        }

        $jd = jewishtojd(self::NISAN, 27, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::FRIDAY) { // If the 27 Nisan would fall on Friday ...
            // ... then Yom Hashoah falls on Thursday
            if ($jewishDay == 26 && $jewishMonth == self::NISAN) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHashoah'
                );
            }
        } else {
            if ($jewishYear >= 5757) { // Since 1997 (5757) ...
                if ($weekdayNo == self::SUNDAY) { // If the 27 Nisan would fall on Friday ...
                    // ... then Yom Hashoah falls on Thursday
                    if ($jewishDay == 28 && $jewishMonth == self::NISAN) {
                        $result[] = LocalizationUtility::translate(
                            'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHashoah'
                        );
                    }
                } else {
                    if ($jewishDay == 27 && $jewishMonth == self::NISAN) {
                        $result[] = LocalizationUtility::translate(
                            'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHashoah'
                        );
                    }
                }
            } else {
                if ($jewishDay == 27 && $jewishMonth == self::NISAN) {
                    $result[] = LocalizationUtility::translate(
                        'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHashoah'
                    );
                }
            }
        }

        // Holidays in Iyar

        $jd = jewishtojd(self::IYAR, 4, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);

        // If the 4 Iyar would fall on Friday or Thursday ...
        // ... then Yom Hazikaron falls on Wednesday and Yom Ha'Atzmaut on Thursday
        if ($weekdayNo == self::FRIDAY) {
            if ($jewishDay == 2 && $jewishMonth == self::IYAR) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHazikaron'
                );
            }
            if ($jewishDay == 3 && $jewishMonth == self::IYAR) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHaAtzmaut'
                );
            }
        } else {
            if ($weekdayNo == self::THURSDAY) {
                if ($jewishDay == 3 && $jewishMonth == self::IYAR) {
                    $result[] = LocalizationUtility::translate(
                        'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHazikaron'
                    );
                }
                if ($jewishDay == 4 && $jewishMonth == self::IYAR) {
                    $result[] = LocalizationUtility::translate(
                        'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHaAtzmaut'
                    );
                }
            } else {
                if ($jewishYear >= 5764) { // Since 2004 (5764) ...
                    if ($weekdayNo == self::SUNDAY) { // If the 4 Iyar would fall on Sunday ...
                        // ... then Yom Hazicaron falls on Monday
                        if ($jewishDay == 5 && $jewishMonth == self::IYAR) {
                            $result[] = LocalizationUtility::translate(
                                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHazikaron'
                            );
                        }
                        if ($jewishDay == 6 && $jewishMonth == self::IYAR) {
                            $result[] = LocalizationUtility::translate(
                                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHaAtzmaut'
                            );
                        }
                    } else {
                        if ($jewishDay == 4 && $jewishMonth == self::IYAR) {
                            $result[] = LocalizationUtility::translate(
                                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHazikaron'
                            );
                        }
                        if ($jewishDay == 5 && $jewishMonth == self::IYAR) {
                            $result[] = LocalizationUtility::translate(
                                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHaAtzmaut'
                            );
                        }
                    }
                } else {
                    if ($jewishDay == 4 && $jewishMonth == self::IYAR) {
                        $result[] = LocalizationUtility::translate(
                            'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHazikaron'
                        );
                    }
                    if ($jewishDay == 5 && $jewishMonth == self::IYAR) {
                        $result[] = LocalizationUtility::translate(
                            'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomHaAtzmaut'
                        );
                    }
                }
            }
        }

        if ($jewishDay == 14 && $jewishMonth == self::IYAR) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.PesachSheini'
            );
        }
        if ($jewishDay == 18 && $jewishMonth == self::IYAR) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.LagBOmer'
            );
        }
        if ($jewishDay == 28 && $jewishMonth == self::IYAR) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.YomYerushalayim'
            );
        }

        // Holidays in Sivan
        if ($jewishDay == 5 && $jewishMonth == self::SIVAN) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ErevShavuot'
            );
        }
        if ($jewishDay == 6 && $jewishMonth == self::SIVAN) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ShavuotI'
            );
        }
        if ($jewishDay == 7 && $jewishMonth == self::SIVAN && $isDiaspora) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.ShavuotII_DiasporOnly'
            );
        }
        if ($isDiaspora) {
            if ($jewishDay == 8 && $jewishMonth == self::SIVAN) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.IsruChagSivan_inDiaspora'
                );
            }
        } else {
            if ($jewishDay == 7 && $jewishMonth == self::SIVAN) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.IsruChagSivan_inIsrael'
                );
            }
        }

        // Holidays in Tammuz
        $jd = jewishtojd(self::TAMMUZ, 17, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 17 Tammuz would fall on Saturday ...
            // ... postpone Tzom Tammuz to Sunday
            if ($jewishDay == 18 && $jewishMonth == self::TAMMUZ) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TzomTammuz'
                );
            }
        } else {
            if ($jewishDay == 17 && $jewishMonth == self::TAMMUZ) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TzomTammuz'
                );
            }
        }

        // Holidays in Av
        $jd = jewishtojd(self::AV, 9, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 9 Av would fall on Saturday ...
            // ... postpone Tisha B'Av to Sunday
            if ($jewishDay == 10 && $jewishMonth == self::AV) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TishaBAv'
                );
            }
        } else {
            if ($jewishDay == 9 && $jewishMonth == self::AV) {
                $result[] = LocalizationUtility::translate(
                    'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TishaBAv'
                );
            }
        }
        if ($jewishDay == 15 && $jewishMonth == self::AV) {
            $result[] = LocalizationUtility::translate(
                'LLL:EXT:timer/Resources/Private/Language/locallang_jewish.xlf:customTimer.holiday.TuBAv'
            );
        }
        return $result;
    }

    /**
     * get a list of five yearly dates for a named holiday, where the middle is defined by an reference date
     *
     * @param string $holidayNameId
     * @param DateTime $checkDate
     * @return array<mixed>
     * @throws TimerException
     */
    public static function getJewishHolidayByName(
        string $holidayNameId,
        DateTime $checkDate
    ): array {
        $jdCurrent = gregoriantojd(
            ((int)$checkDate->format('m')),
            ((int)$checkDate->format('d')),
            ((int)$checkDate->format('Y'))
        );
        $curJewishDate = jdtojewish($jdCurrent);
        [$curJewishMonth, $curJewishDay, $curJewishYear] = explode('/', $curJewishDate);
        $curJewishYear = (int)$curJewishYear;
        $resultDates = [];
        switch ($holidayNameId) {
            // Holidays in Elul
            case self::ARG_NAMED_DATE_EREVROSHHASHANAH:
                $resultDates = self::getListAroundCurrentYear(
                    self::ELUL,
                    29,
                    $curJewishYear,
                    $checkDate
                );
                break;

                // Holidays in Tishri
            case self::ARG_NAMED_DATE_ROSHHASHANAHI:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    1,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_ROSHHASHANAHII:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    2,
                    $curJewishYear,
                    $checkDate
                );
                break;

            case self::ARG_NAMED_DATE_TZOMGEDALIAH:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    3,
                    $curJewishYear,
                    $checkDate,
                    true
                );
                break;


            case self::ARG_NAMED_DATE_EREVYOMKIPPUR:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    9,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_YOMKIPPUR:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    10,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_EREVSUKKOT:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    14,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SUKKOTI:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    15,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SUKKOTII_DIASPORONLY:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    16,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_HOLHAMOEDSUKKOT_INISRAEL:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    16,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_HOLHAMOEDSUKKOT_INDIASPORA:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    17,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_HOSHANARABBAH:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    21,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SHEMINIAZERET_INDIASPORA:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    22,
                    $curJewishYear,
                    $checkDate
                );
                break;

            case self::ARG_NAMED_DATE_SIMCHATTORAH_INDIASPORA:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    23,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_ISRUCHAGTISHRI_INDIASPORA:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    24,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SHEMINIAZERETSIMCHATTORAH_INISRAEL:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    22,
                    $curJewishYear,
                    $checkDate
                );

                break;
            case self::ARG_NAMED_DATE_ISRUCHAGTISHRI_INISRAEL:
                $resultDates = self::getListAroundCurrentYear(
                    self::TISHRI,
                    23,
                    $curJewishYear,
                    $checkDate
                );
                break;

                // Holidays in Kislev/Tevet
            case self::ARG_NAMED_DATE_HANUKKAHI:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    0
                );
                break;
            case self::ARG_NAMED_DATE_HANUKKAHII:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    1
                );
                break;
            case self::ARG_NAMED_DATE_HANUKKAHIII:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    2
                );
                break;
            case self::ARG_NAMED_DATE_HANUKKAHIV:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    3
                );
                break;
            case self::ARG_NAMED_DATE_HANUKKAHV:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    4
                );
                break;
            case self::ARG_NAMED_DATE_HANUKKAHVI:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    5
                );
                break;
            case self::ARG_NAMED_DATE_HANUKKAHVII:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    6
                );
                break;
            case self::ARG_NAMED_DATE_HANUKKAHVIII:
                $resultDates = self::getListAroundCurrentYear(
                    self::KISLEV,
                    25,
                    $curJewishYear,
                    $checkDate,
                    false,
                    7
                );
                break;

                // Holidays in Tevet
            case self::ARG_NAMED_DATE_TZOMTEVET:
                $resultDates = self::getListAroundCurrentYear(
                    self::TEVET,
                    10,
                    $curJewishYear,
                    $checkDate,
                    true
                );
                break;

                // Holidays in Shevat
            case self::ARG_NAMED_DATE_TUBSHEVAT:
                $resultDates = self::getListAroundCurrentYear(
                    self::SHEVAT,
                    15,
                    $curJewishYear,
                    $checkDate,
                    false
                );
                break;


                // Holidays in Adar I
            case self::ARG_NAMED_DATE_PURIMKATAN:
                $resultDates = self::getListOfLeapYearsAroundCurrentYear(
                    self::ADAR_I,
                    14,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SHUSHANPURIMKATAN:
                $resultDates = self::getListOfLeapYearsAroundCurrentYear(
                    self::ADAR_I,
                    15,
                    $curJewishYear,
                    $checkDate
                );
                break;

                // Holidays in Adar or Adar II
            case self::ARG_NAMED_DATE_TAANITHESTHER:
                $resultDates = self::getListRespectLeapYearsAroundCurrentYear(
                    13,
                    $curJewishYear,
                    $checkDate,
                    true,
                    false
                );
                break;
            case self::ARG_NAMED_DATE_PURIM:
                $resultDates = self::getListRespectLeapYearsAroundCurrentYear(
                    14,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SHUSHANPURIM_POST:
                $resultDates = self::getListRespectLeapYearsAroundCurrentYear(
                    15,
                    $curJewishYear,
                    $checkDate,
                    false,
                    true
                );
                break;
            case self::ARG_NAMED_DATE_SHUSHANPURIM_PUR:
                $resultDates = self::getListRespectLeapYearsAroundCurrentYear(
                    15,
                    $curJewishYear,
                    $checkDate,
                    false,
                    false
                );
                break;

            case self::ARG_NAMED_DATE_SHABBATHAGADOL:
                $resultDates = self::getListForShabbathAgadol($curJewishYear, $checkDate);
                $hello = $resultDates;
                break;

            case self::ARG_NAMED_DATE_EREVPESACH:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    14,
                    $curJewishYear,
                    $checkDate,
                    false
                );
                break;
            case self::ARG_NAMED_DATE_PESACHI:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    15,
                    $curJewishYear,
                    $checkDate,
                    false
                );
                break;
            case self::ARG_NAMED_DATE_PESACHII_DIASPORONLY:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    16,
                    $curJewishYear,
                    $checkDate
                );
                break;

            case self::ARG_NAMED_DATE_HOLHAMOEDPESACH_INISRAEL:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    16,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_HOLHAMOEDPESACH_INDIASPORA:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    17,
                    $curJewishYear,
                    $checkDate
                );

                break;
            case self::ARG_NAMED_DATE_PESACHVII:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    21,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_PESACHVIII_DIASPORONLY:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    22,
                    $curJewishYear,
                    $checkDate
                );
                break;

            case self::ARG_NAMED_DATE_ISRUCHAGNISAN_INDIASPORA:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    23,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_ISRUCHAGNISAN_INISRAEL:
                $resultDates = self::getListAroundCurrentYear(
                    self::NISAN,
                    22,
                    $curJewishYear,
                    $checkDate
                );
                break;

            case self::ARG_NAMED_DATE_YOMHASHOAH:
                $resultDates = self::getListForYomHashoahAroundCurrentYear($curJewishYear, $checkDate);
                break;


                // Holidays in Iyar
            case self::ARG_NAMED_DATE_YOMHAZIKARON:
                $resultDates = self::getListForYomHaAtzmautAroundCurrentYear($curJewishYear, $checkDate, true);
                break;
            case self::ARG_NAMED_DATE_YOMHAATZMAUT:
                $resultDates = self::getListForYomHaAtzmautAroundCurrentYear($curJewishYear, $checkDate);
                break;

            case self::ARG_NAMED_DATE_PESACHSHEINI:
                $resultDates = self::getListAroundCurrentYear(
                    self::IYAR,
                    14,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_LAGBOMER:
                $resultDates = self::getListAroundCurrentYear(
                    self::IYAR,
                    18,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_YOMYERUSHALAYIM:
                $resultDates = self::getListAroundCurrentYear(
                    self::IYAR,
                    28,
                    $curJewishYear,
                    $checkDate
                );
                break;

                // Holidays in Sivan
            case self::ARG_NAMED_DATE_EREVSHAVUOT:
                $resultDates = self::getListAroundCurrentYear(
                    self::SIVAN,
                    5,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SHAVUOTI:
                $resultDates = self::getListAroundCurrentYear(
                    self::SIVAN,
                    6,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_SHAVUOTII_DIASPORONLY:
                $resultDates = self::getListAroundCurrentYear(
                    self::SIVAN,
                    7,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_ISRUCHAGSIVAN_INDIASPORA:
                $resultDates = self::getListAroundCurrentYear(
                    self::SIVAN,
                    8,
                    $curJewishYear,
                    $checkDate
                );
                break;
            case self::ARG_NAMED_DATE_ISRUCHAGSIVAN_INISRAEL:
                $resultDates = self::getListAroundCurrentYear(
                    self::SIVAN,
                    7,
                    $curJewishYear,
                    $checkDate
                );
                break;

                // Holidays in Tammuz
            case self::ARG_NAMED_DATE_TZOMTAMMUZ:
                $resultDates = self::getListAroundCurrentYear(
                    self::TAMMUZ,
                    17,
                    $curJewishYear,
                    $checkDate,
                    true
                );
                break;

                // Holidays in Av
            case self::ARG_NAMED_DATE_TISHABAV:
                $resultDates = self::getListAroundCurrentYear(
                    self::AV,
                    9,
                    $curJewishYear,
                    $checkDate,
                    true
                );
                break;
            case self::ARG_NAMED_DATE_TUBAV:
                $resultDates = self::getListAroundCurrentYear(
                    self::AV,
                    15,
                    $curJewishYear,
                    $checkDate
                );
                break;

            default:
                throw new TimerException(
                    'The id `'.$holidayNameId.'`, which maps the name of the jewish holiday, is unknown. '.
                    'Make a screenshot and inform the webmaster/programmer.',
                    1672394374
                );
        }
        return $resultDates;
    }

    /**
     * @param int $jewishMonthForHoliday
     * @param int $jewishDayForHoliday
     * @param int $curJewishYear
     * @param DateTime $checkDate
     * @param bool $flagPostPoneSaturday
     * @param int $addDays
     * @return array<mixed>
     * @throws \Exception
     */
    protected static function getListAroundCurrentYear(
        int $jewishMonthForHoliday,
        int $jewishDayForHoliday,
        int $curJewishYear,
        DateTime $checkDate,
        bool $flagPostPoneSaturday = false,
        int $addDays = 0
    ): array {
        $resultDates = [];
        foreach ([-2, -1, 0, 1, 2] as $addYear) {
            $jdNumber = jewishtojd($jewishMonthForHoliday, $jewishDayForHoliday, ($curJewishYear + $addYear));
            $gregorianDate = jdtogregorian($jdNumber);
            $resultDates[$addYear] = DateTime::createFromFormat(
                'm/d/Y H:i:s',
                $gregorianDate . ' 00:00:00',
                $checkDate->getTimezone()
            );
            if ($flagPostPoneSaturday) {
                $weekdayNo = jddayofweek($jdNumber, 0);
                if ($weekdayNo === self::SATURDAY) {
                    $resultDates[$addYear]->add(new DateInterval('P1D'));
                }
            }
            if ($addDays > 0) {
                $resultDates[$addYear]->add(new DateInterval('P' . $addDays . 'D'));
            }
        }
        return $resultDates;
    }

    /**
     * @param int $curJewishMonth
     * @param int $curJewishDay
     * @param int $curJewishYear
     * @param DateTime $checkDate
     * @return array<mixed>
     */
    protected static function getListOfLeapYearsAroundCurrentYear(
        int $curJewishMonth,
        int $curJewishDay,
        int $curJewishYear,
        DateTime $checkDate
    ): array {
        $runYear = $curJewishYear;
        $resultDates = [];
        for ($i = 0; $i <= 2; $i++) {
            while (!self::isJewishLeapYear($runYear)) {
                $runYear--;
            }
            $runYear--;
        }

        foreach ([-2, -1, 0, 1, 2] as $addYear) {
            while (!self::isJewishLeapYear($runYear)) {
                $runYear++;
            }
            $jdNumber = jewishtojd($curJewishMonth, $curJewishDay, $runYear);
            while (jddayofweek($jdNumber, 0) !== self::SATURDAY) {
                $jdNumber--;
            }
            $gregorianDate = jdtogregorian($jdNumber);
            $resultDates[$addYear] = DateTime::createFromFormat(
                'm/d/Y H:i:s',
                $gregorianDate . ' 00:00:00',
                $checkDate->getTimezone()
            );
        }
        return $resultDates;
    }

    /**
     * @param int $jewishDayForHoliday
     * @param int $curJewishYear
     * @param DateTime $checkDate
     * @param bool $flagPostPoneSaturdayTaAnithEsther
     * @param bool $postponeShushanPurimOnSaturday
     * @return array<mixed>
     */
    protected static function getListRespectLeapYearsAroundCurrentYear(
        int $jewishDayForHoliday,
        int $curJewishYear,
        DateTime $checkDate,
        bool $flagPostPoneSaturdayTaAnithEsther = false,
        bool $postponeShushanPurimOnSaturday = false
    ): array {
        $resultDates = [];
        foreach ([-2, -1, 0, 1, 2] as $addYear) {
            if (self::isJewishLeapYear($curJewishYear + $addYear)) {
                $purimMonth = self::ADAR_II;
            } else {
                $purimMonth = self::ADAR;
            }

            $jdNumber = jewishtojd($purimMonth, $jewishDayForHoliday, ($curJewishYear + $addYear));
            $gregorianDate = jdtogregorian($jdNumber);
            $resultDates[$addYear] = DateTime::createFromFormat(
                'm/d/Y H:i:s',
                $gregorianDate . ' 00:00:00',
                $checkDate->getTimezone()
            );
            if ($flagPostPoneSaturdayTaAnithEsther) {
                $weekdayNo = jddayofweek($jdNumber, 0);
                if ($weekdayNo === self::SATURDAY) {
                    $resultDates[$addYear]->sub(new DateInterval('P2D'));
                }
            }
            if ($postponeShushanPurimOnSaturday) {
                $weekdayNo = jddayofweek($jdNumber, 0);
                if ($weekdayNo === self::SATURDAY) {
                    $resultDates[$addYear]->add(new DateInterval('P1D'));
                }
            }
        }
        return $resultDates;
    }

    /**
     * @param int $curJewishYear
     * @param DateTime $checkDate
     * @return array<mixed>
     */
    protected static function getListForShabbathAgadol(int $curJewishYear, DateTime $checkDate): array
    {
        $resultDates = [];
        foreach ([-2, -1, 0, 1, 2] as $addYear) {
            $jdNumber = jewishtojd(self::NISAN, 14, ($curJewishYear + $addYear));
            while (jddayofweek($jdNumber, 0) !== self::SATURDAY) {
                $jdNumber--;
            }
            $gregorianDate = jdtogregorian($jdNumber);
            $resultDates[$addYear] = DateTime::createFromFormat(
                'm/d/Y H:i:s',
                $gregorianDate . ' 00:00:00',
                $checkDate->getTimezone()
            );
        }
        return $resultDates;
    }

    /**
     * @param int $curJewishYear
     * @param DateTime $checkDate
     * @param bool $flagYomHazikaron
     * @return array<mixed>
     */
    protected static function getListForYomHaAtzmautAroundCurrentYear(
        int $curJewishYear,
        DateTime $checkDate,
        bool $flagYomHazikaron = false
    ): array {
        $resultDates = [];
        foreach ([-2, -1, 0, 1, 2] as $addYear) {
            $refYear = ($curJewishYear + $addYear);
            $jdNumber = jewishtojd(self::IYAR, 4, $refYear);
            $gregorianDate = jdtogregorian($jdNumber);
            $weekdayNo = jddayofweek($jdNumber, 0);
            $resultDates[$addYear] = DateTime::createFromFormat(
                'm/d/Y H:i:s',
                $gregorianDate . ' 00:00:00',
                $checkDate->getTimezone()
            );
            if ($weekdayNo === self::FRIDAY) {
                $resultDates[$addYear]->sub(new DateInterval('P1D'));
            } else {
                if ($weekdayNo !== self::THURSDAY) {
                    if (
                        ($refYear >= self::JEWISH_YEAR_CALENDAR_SECOND_CHANGE) &&
                        ($weekdayNo === self::SUNDAY)
                    ) {
                        $resultDates[$addYear]->add(new DateInterval('P2D'));
                    } else {
                        $resultDates[$addYear]->add(new DateInterval('P1D'));
                    }
                }
            }
            if ($flagYomHazikaron) {
                $resultDates[$addYear]->sub(new DateInterval('P1D'));
            }
        }
        return $resultDates;
    }

    /**
     * @param int $curJewishYear
     * @param DateTime $checkDate
     * @return array<mixed>
     */
    protected static function getListForYomHashoahAroundCurrentYear(int $curJewishYear, DateTime $checkDate): array
    {
        $resultDates = [];
        foreach ([-2, -1, 0, 1, 2] as $addYear) {
            $refYear = ($curJewishYear + $addYear);
            $jdNumber = jewishtojd(self::NISAN, 27, $refYear);
            $gregorianDate = jdtogregorian($jdNumber);
            $weekdayNo = jddayofweek($jdNumber, 0);
            $resultDates[$addYear] = DateTime::createFromFormat(
                'm/d/Y H:i:s',
                $gregorianDate . ' 00:00:00',
                $checkDate->getTimezone()
            );
            if ($weekdayNo === self::FRIDAY) {
                $resultDates[$addYear]->sub(new DateInterval('P1D'));
            } else {
                if (
                    ($refYear >= self::JEWISH_YEAR_CALENDAR_FIRST_CHANGE) &&
                    ($weekdayNo === self::SUNDAY)
                ) {
                    $resultDates[$addYear]->add(new DateInterval('P1D'));
                }
            }
        }
        return $resultDates;
    }
}
