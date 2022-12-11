<?php

namespace Porthd\Timer\Utilities;

use DateTime;
use Porthd\Timer\Constants\TimerConst;
use PorthD\Wysiwyg\Utility\LocalizationUtility;

/***************************************************************
 *
 *  code inspired by https://www.david-greve.de/luach-code/jewish-php.html visited 20221205
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
class JewishDateUtility
{


    protected const SUNDAY = 0;
    // protected const MONDAY = 1; // not needed
    // protected const TUESDAY = 2; // not needed
    // protected const WEDNESDAY = 3; // not needed
    protected const THURSDAY = 4;
    protected const FRIDAY = 5;
    protected const SATURDAY = 6;


    protected const TISHRI = 1;
    protected const HESHVAN = 2;
    protected const KISLEV = 3;
    protected const TEVET = 4;
    protected const SHEVAT = 5;
    protected const ADAR = 6;
    protected const ADAR_II = 7;
    protected const NISAN = 8;
    protected const IYAR = 9;
    protected const SIVAN = 10;
    protected const TAMMUZ = 11;
    protected const AV = 12;
    protected const ELUL = 13;
    protected const ADAR_I = 6;


    protected const JEWISH_MONTH_NAMES_LEAP = [
        self::TISHRI => "Tishri",
        self::HESHVAN => "Heshvan",
        self::KISLEV => "Kislev",
        self::TEVET => "Tevet",
        self::SHEVAT => "Shevat",
        self::ADAR_I => "Adar I",
        self::ADAR_II => "Adar II",
        self::NISAN => "Nisan",
        self::IYAR => "Iyar",
        self::SIVAN => "Sivan",
        self::TAMMUZ => "Tammuz",
        self::AV => "Av",
        self::ELUL => "Elul",
    ];
    protected const JEWISH_MONTH_NAMES_NON_LEAP = [
        self::TISHRI => "Tishri",
        self::HESHVAN => "Heshvan",
        self::KISLEV => "Kislev",
        self::TEVET => "Tevet",
        self::SHEVAT => "Shevat",
        self::ADAR => "Adar",
        self::ADAR_II => "Adar",
        self::NISAN => "Nisan",
        self::IYAR => "Iyar",
        self::SIVAN => "Sivan",
        self::TAMMUZ => "Tammuz",
        self::AV => "Av",
        self::ELUL => "Elul",
    ];


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

    public static function isIsraeliDaylightSavingsTime($month, $day, $year)
    {
        // Get Jewish year of Yom Kippur in the passed Gregorian year
        $jdCur = gregoriantojd(12, 31, $year);
        $jewishCur = jdtojewish($jdCur);
        [$jewishCurMonth, $jewishCurDay, $jewishCurYear] = explode("/", $jewishCur, 3);
        $jewishCurYearNum = (int)$jewishCurYear;

        // Get Last Friday before 2nd of April
        $jdDSTBegin = gregoriantojd(4, 2, $year); // 2 April
        $jdDSTBegin--; // get the day before 2nd of April
        while (jddayofweek($jdDSTBegin, 0) !== 5) // gets the weekday, 5 = Friday
        {
            $jdDSTBegin--;
        } // counts to the previous day until Friday

        // Get Sunday between Rosh Hashana and Yom Kippur
        // Take the first Sunday on or after 3rd of Tishri
        $jdDSTEnd = jewishtojd(1, 3, $jewishCurYearNum);
        while (jddayofweek($jdDSTEnd, 0) !== 0) // gets the weekday, 0 = Sunday
        {
            $jdDSTEnd++;
        } // counts to the next day until Sunday

        // Check if the current date is between the start and end date ...
        $jdCurrent = gregoriantojd($month, $day, $year);
        if (($jdCurrent >= $jdDSTBegin) &&
            ($jdCurrent < $jdDSTEnd)
        ) {
            return true;
        }
        return false;
    }

    /**
     * format a DateTime-Object to an
     * @param DateTime $date
     * @param string $format
     * @return array
     */
    public static function formatJewishDateFromDateTime(DateTime $date, string $format): array
    {
        // Build pattern for preg_replace
        $dateTimeParts = [];
        foreach ([
                     '/([^\\])w/' => 'w',
                     '/([^\\])D/' => 'D',
                     '/([^\\])a/' => 'a',
                     '/([^\\])A/' => 'A',
                     '/([^\\])g/' => 'g',
                     '/([^\\])G/' => 'G',
                     '/([^\\])h/' => 'h',
                     '/([^\\])H/' => 'H',
                     '/([^\\])i/' => 'i',
                     '/([^\\])s/' => 's',
                     '/([^\\])Y/' => 'Y',
                     '/([^\\])y/' => 'y',
                     '/([^\\])n/' => 'n',
                     '/([^\\])m/' => 'm',
                     '/([^\\])M/' => 'M',
                     '/([^\\])F/' => 'F',
                     '/([^\\])d/' => 'd',
                     '/([^\\])t/' => 't',
                     '/([^\\])e/' => 'e',
                     '/([^\\])I/' => 'I',
                     '/([^\\])O/' => 'O',
                     '/([^\\])P/' => 'P',
                     '/([^\\])p/' => 'p',
                     '/([^\\])T/' => 'T',
                     '/([^\\])Z/' => 'Z',
                     '/([^\\])U/' => 'U',
                 ] as $key => $itemFormat) {
            $dateTimeParts[$key] = '$1' . $date->format($itemFormat);
        }
        // change entry for jewish-date
        $jdNumber = gregoriantojd($dateTimeParts['n'], $dateTimeParts['d'], $dateTimeParts['Y']);
        $jewishDate = jdtojewish($jdNumber);
        $jewishWeekday = jddayofweek($jdNumber, 0);
        [$jewishMonth, $jewishDay, $jewishYear] = explode('/', $jewishDate);
        $jewishMonth = (int)$jewishMonth;

        $dateTimeParts['t'] = cal_days_in_month(CAL_JEWISH, $jewishMonth, $jewishYear);
        $dateTimeParts['w'] = (int)$jewishWeekday;
        $dateTimeParts['D'] = 'viewhelper.jewishMonth.' . $jewishWeekday . '.day';
        $dateTimeParts['j'] = (int)$jewishDay;
        $dateTimeParts['d'] = str_pad($jewishDay, 2, '0', STR_PAD_LEFT);
        $dateTimeParts['y'] = str_pad(($jewishYear % 100), 2, '0', STR_PAD_LEFT);
        $dateTimeParts['Y'] = $jewishYear;
        $dateTimeParts['n'] = $jewishMonth;
        $dateTimeParts['m'] = str_pad($jewishMonth, 2, '0', STR_PAD_LEFT);
        $dateTimeParts['F'] = 'viewhelper.jewishMonth.' . (
            $dateTimeParts['M'] = JewishDateUtility::getJewishMonthName($jewishMonth, $jewishYear)
            );

        return $dateTimeParts;
    }

    /**
     * @param int $jdCurrent
     * @param bool $isDiaspora
     * @param bool $postponeShushanPurimOnSaturday
     * @return array
     */
    public static function getJewishHoliday(int $jdCurrent, bool $isDiaspora, bool $postponeShushanPurimOnSaturday)
    {
        $result = [];


        $jewishDate = jdtojewish($jdCurrent);
        [$jewishMonth, $jewishDay, $jewishYear] = explode('/', $jewishDate);
        $jewishMonth = (int)$jewishMonth;
        $jewishDay = (int) $jewishDay;
        $jewishYear = (int)$jewishYear;
        // Holidays in Elul
        if ($jewishDay == 29 && $jewishMonth == self::ELUL) {
            $result[] = "Erev Rosh Hashanah";
        }

        // Holidays in Tishri
        if ($jewishDay == 1 && $jewishMonth == self::TISHRI) {
            $result[] = "Rosh Hashanah I";
        }
        if ($jewishDay == 2 && $jewishMonth == self::TISHRI) {
            $result[] = "Rosh Hashanah II";
        }
        $jd = jewishtojd(self::TISHRI, 3, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 3 Tishri would fall on Saturday ...
            // ... postpone Tzom Gedaliah to Sunday
            if ($jewishDay == 4 && $jewishMonth == self::TISHRI) {
                $result[] = "Tzom Gedaliah";
            }
        } else {
            if ($jewishDay == 3 && $jewishMonth == self::TISHRI) {
                $result[] = "Tzom Gedaliah";
            }
        }
        if ($jewishDay == 9 && $jewishMonth == self::TISHRI) {
            $result[] = "Erev Yom Kippur";
        }
        if ($jewishDay == 10 && $jewishMonth == self::TISHRI) {
            $result[] = "Yom Kippur";
        }
        if ($jewishDay == 14 && $jewishMonth == self::TISHRI) {
            $result[] = "Erev Sukkot";
        }
        if ($jewishDay == 15 && $jewishMonth == self::TISHRI) {
            $result[] = "Sukkot I";
        }
        if ($jewishDay == 16 && $jewishMonth == self::TISHRI && $isDiaspora) {
            $result[] = "Sukkot II";
        }
        if ($isDiaspora) {
            if ($jewishDay >= 17 && $jewishDay <= 20 && $jewishMonth == self::TISHRI) {
                $result[] = "Hol Hamoed Sukkot";
            }
        } else {
            if ($jewishDay >= 16 && $jewishDay <= 20 && $jewishMonth == self::TISHRI) {
                $result[] = "Hol Hamoed Sukkot";
            }
        }
        if ($jewishDay == 21 && $jewishMonth == self::TISHRI) {
            $result[] = "Hoshana Rabbah";
        }
        if ($isDiaspora) {
            if ($jewishDay == 22 && $jewishMonth == self::TISHRI) {
                $result[] = "Shemini Azeret";
            }
            if ($jewishDay == 23 && $jewishMonth == self::TISHRI) {
                $result[] = "Simchat Torah";
            }
            if ($jewishDay == 24 && $jewishMonth == self::TISHRI) {
                $result[] = "Isru Chag";
            }
        } else {
            if ($jewishDay == 22 && $jewishMonth == self::TISHRI) {
                $result[] = "Shemini Azeret/Simchat Torah";
            }
            if ($jewishDay == 23 && $jewishMonth == self::TISHRI) {
                $result[] = "Isru Chag";
            }
        }

        // Holidays in Kislev/Tevet
        $hanukkahStart = jewishtojd(self::KISLEV, 25, $jewishYear);
        $hanukkahNo = (int)($jdCurrent - $hanukkahStart + 1);
        if ($hanukkahNo == 1) {
            $result[] = "Hanukkah I";
        }
        if ($hanukkahNo == 2) {
            $result[] = "Hanukkah II";
        }
        if ($hanukkahNo == 3) {
            $result[] = "Hanukkah III";
        }
        if ($hanukkahNo == 4) {
            $result[] = "Hanukkah IV";
        }
        if ($hanukkahNo == 5) {
            $result[] = "Hanukkah V";
        }
        if ($hanukkahNo == 6) {
            $result[] = "Hanukkah VI";
        }
        if ($hanukkahNo == 7) {
            $result[] = "Hanukkah VII";
        }
        if ($hanukkahNo == 8) {
            $result[] = "Hanukkah VIII";
        }

        // Holidays in Tevet
        $jd = jewishtojd(self::TEVET, 10, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 10 Tevet would fall on Saturday ...
            // ... postpone Tzom Tevet to Sunday
            if ($jewishDay == 11 && $jewishMonth == self::TEVET) {
                $result[] = "Tzom Tevet";
            }
        } else {
            if ($jewishDay == 10 && $jewishMonth == self::TEVET) {
                $result[] = "Tzom Tevet";
            }
        }

        // Holidays in Shevat
        if ($jewishDay == 15 && $jewishMonth == self::SHEVAT) {
            $result[] = "Tu B'Shevat";
        }

        // Holidays in Adar I
        if (self::isJewishLeapYear($jewishYear) && $jewishDay == 14 && $jewishMonth == self::ADAR_I) {
            $result[] = "Purim Katan";
        }
        if (self::isJewishLeapYear($jewishYear) && $jewishDay == 15 && $jewishMonth == self::ADAR_I) {
            $result[] = "Shushan Purim Katan";
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
                $result[] = "Ta'anith Esther";
            }
        } else {
            if ($jewishDay == 13 && $jewishMonth == $purimMonth) {
                $result[] = "Ta'anith Esther";
            }
        }
        if ($jewishDay == 14 && $jewishMonth == $purimMonth) {
            $result[] = "Purim";
        }
        if ($postponeShushanPurimOnSaturday) {
            $jd = jewishtojd($purimMonth, 15, $jewishYear);
            $weekdayNo = jddayofweek($jd, 0);
            if ($weekdayNo == self::SATURDAY) { // If the 15 Adar or Adar II would fall on Saturday ...
                // ... postpone Shushan Purim to Sunday
                if ($jewishDay == 16 && $jewishMonth == $purimMonth) {
                    $result[] = "Shushan Purim";
                }
            } else {
                if ($jewishDay == 15 && $jewishMonth == $purimMonth) {
                    $result[] = "Shushan Purim";
                }
            }
        } else {
            if ($jewishDay == 15 && $jewishMonth == $purimMonth) {
                $result[] = "Shushan Purim";
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
            $result[] = "Shabbat Hagadol";
        }
        if ($jewishDay == 14 && $jewishMonth == self::NISAN) {
            $result[] = "Erev Pesach";
        }
        if ($jewishDay == 15 && $jewishMonth == self::NISAN) {
            $result[] = "Pesach I";
        }
        if ($jewishDay == 16 && $jewishMonth == self::NISAN && $isDiaspora) {
            $result[] = "Pesach II";
        }
        if ($isDiaspora) {
            if ($jewishDay >= 17 && $jewishDay <= 20 && $jewishMonth == self::NISAN) {
                $result[] = "Hol Hamoed Pesach";
            }
        } else {
            if ($jewishDay >= 16 && $jewishDay <= 20 && $jewishMonth == self::NISAN) {
                $result[] = "Hol Hamoed Pesach";
            }
        }
        if ($jewishDay == 21 && $jewishMonth == self::NISAN) {
            $result[] = "Pesach VII";
        }
        if ($jewishDay == 22 && $jewishMonth == self::NISAN && $isDiaspora) {
            $result[] = "Pesach VIII";
        }
        if ($isDiaspora) {
            if ($jewishDay == 23 && $jewishMonth == self::NISAN) {
                $result[] = "Isru Chag";
            }
        } else {
            if ($jewishDay == 22 && $jewishMonth == self::NISAN) {
                $result[] = "Isru Chag";
            }
        }

        $jd = jewishtojd(self::NISAN, 27, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::FRIDAY) { // If the 27 Nisan would fall on Friday ...
            // ... then Yom Hashoah falls on Thursday
            if ($jewishDay == 26 && $jewishMonth == self::NISAN) {
                $result[] = "Yom Hashoah";
            }
        } else {
            if ($jewishYear >= 5757) { // Since 1997 (5757) ...
                if ($weekdayNo == self::SUNDAY) { // If the 27 Nisan would fall on Friday ...
                    // ... then Yom Hashoah falls on Thursday
                    if ($jewishDay == 28 && $jewishMonth == self::NISAN) {
                        $result[] = "Yom Hashoah";
                    }
                } else {
                    if ($jewishDay == 27 && $jewishMonth == self::NISAN) {
                        $result[] = "Yom Hashoah";
                    }
                }
            } else {
                if ($jewishDay == 27 && $jewishMonth == self::NISAN) {
                    $result[] = "Yom Hashoah";
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
                $result[] = "Yom Hazikaron";
            }
            if ($jewishDay == 3 && $jewishMonth == self::IYAR) {
                $result[] = "Yom Ha'Atzmaut";
            }
        } else {
            if ($weekdayNo == self::THURSDAY) {
                if ($jewishDay == 3 && $jewishMonth == self::IYAR) {
                    $result[] = "Yom Hazikaron";
                }
                if ($jewishDay == 4 && $jewishMonth == self::IYAR) {
                    $result[] = "Yom Ha'Atzmaut";
                }
            } else {
                if ($jewishYear >= 5764) { // Since 2004 (5764) ...
                    if ($weekdayNo == self::SUNDAY) { // If the 4 Iyar would fall on Sunday ...
                        // ... then Yom Hazicaron falls on Monday
                        if ($jewishDay == 5 && $jewishMonth == self::IYAR) {
                            $result[] = "Yom Hazikaron";
                        }
                        if ($jewishDay == 6 && $jewishMonth == self::IYAR) {
                            $result[] = "Yom Ha'Atzmaut";
                        }
                    } else {
                        if ($jewishDay == 4 && $jewishMonth == self::IYAR) {
                            $result[] = "Yom Hazikaron";
                        }
                        if ($jewishDay == 5 && $jewishMonth == self::IYAR) {
                            $result[] = "Yom Ha'Atzmaut";
                        }
                    }
                } else {
                    if ($jewishDay == 4 && $jewishMonth == self::IYAR) {
                        $result[] = "Yom Hazikaron";
                    }
                    if ($jewishDay == 5 && $jewishMonth == self::IYAR) {
                        $result[] = "Yom Ha'Atzmaut";
                    }
                }
            }
        }

        if ($jewishDay == 14 && $jewishMonth == self::IYAR) {
            $result[] = "Pesach Sheini";
        }
        if ($jewishDay == 18 && $jewishMonth == self::IYAR) {
            $result[] = "Lag B'Omer";
        }
        if ($jewishDay == 28 && $jewishMonth == self::IYAR) {
            $result[] = "Yom Yerushalayim";
        }

        // Holidays in Sivan
        if ($jewishDay == 5 && $jewishMonth == self::SIVAN) {
            $result[] = "Erev Shavuot";
        }
        if ($jewishDay == 6 && $jewishMonth == self::SIVAN) {
            $result[] = "Shavuot I";
        }
        if ($jewishDay == 7 && $jewishMonth == self::SIVAN && $isDiaspora) {
            $result[] = "Shavuot II";
        }
        if ($isDiaspora) {
            if ($jewishDay == 8 && $jewishMonth == self::SIVAN) {
                $result[] = "Isru Chag";
            }
        } else {
            if ($jewishDay == 7 && $jewishMonth == self::SIVAN) {
                $result[] = "Isru Chag";
            }
        }

        // Holidays in Tammuz
        $jd = jewishtojd(self::TAMMUZ, 17, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 17 Tammuz would fall on Saturday ...
            // ... postpone Tzom Tammuz to Sunday
            if ($jewishDay == 18 && $jewishMonth == self::TAMMUZ) {
                $result[] = "Tzom Tammuz";
            }
        } else {
            if ($jewishDay == 17 && $jewishMonth == self::TAMMUZ) {
                $result[] = "Tzom Tammuz";
            }
        }

        // Holidays in Av
        $jd = jewishtojd(self::AV, 9, $jewishYear);
        $weekdayNo = jddayofweek($jd, 0);
        if ($weekdayNo == self::SATURDAY) { // If the 9 Av would fall on Saturday ...
            // ... postpone Tisha B'Av to Sunday
            if ($jewishDay == 10 && $jewishMonth == self::AV) {
                $result[] = "Tisha B'Av";
            }
        } else {
            if ($jewishDay == 9 && $jewishMonth == self::AV) {
                $result[] = "Tisha B'Av";
            }
        }
        if ($jewishDay == 15 && $jewishMonth == self::AV) {
            $result[] = "Tu B'Av";
        }

        return $result;
    }
}

