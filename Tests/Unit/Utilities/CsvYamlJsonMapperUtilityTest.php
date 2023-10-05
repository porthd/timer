<?php

declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\Utilities;

use Porthd\Timer\Utilities\CsvYamlJsonMapperUtility;
use PHPUnit\Framework\TestCase;

class CsvYamlJsonMapperUtilityTest extends TestCase
{
    /**
     * @test
     */
    public function mapCsvToRawArray()
    {
        $myTestString = <<<DOMTEST
title,eventtitle,identifier,type,arg.month,arg.day,arg.calendar,arg.status,arg.statusCount,arg.secDayCount,tag,add.category.COMMA,add.rank,add.locale.COMMA,add.freelocale.COMMA,add.alias
Heiligabend,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmasEve,greg-christmasEve,fixed,12,24,gregorian,,,,religion,christian,3,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Weihnachten,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmas,greg-christmas,fixed,12,25,gregorian,,,,religion,christian,4,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Weihnachten (2. Tag),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmasSecondDay,greg-christmasSecondDay,fixed,12,26,gregorian,,,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Silvester,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.silvester,greg-silvester,fixed,31,12,gregorian,,,,culture,,4,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Neujahr,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.newyear,greg-newYear,fixed,1,1,gregorian,,,,culture,,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Valentinstag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.eco.greg.valentinsday,greg-valentinsDay ,fixed,14,2,gregorian,,,,economic,,2,,,
Rosenmontag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.rosemonday,greg-roseMonday,easterly,,,gregorian,easter,-48,,religion,christian,3,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Fasching,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.carnival,greg-carnival,easterly,,,gregorian,easter,-47,,religion,christian,3,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Karfreitag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.goodfriday,greg-goodFriday,easterly,,,gregorian,easter,-3,,religion,christian,4,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Ostern,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.easter,greg-easter,easterly,,,gregorian,easter,0,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Ostermontag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.eastermonday,greg-easterMonday,easterly,,,gregorian,easter,1,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Welttag der Dummheit,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.stupidity,greg-stupidityDay,fixed,16,4,gregorian,,,,culture,philosophy,1,,,
Tag der Arbeit,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.labourday,greg-labourDay,fixed,1,5,gregorian,,,,politics,"communist, laborunion",5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Muttertag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.mothersday,greg-mothersDay,weekdayinmonth,5,,gregorian,sunday,2,,culture,"politics, gender",2,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Welthandtuchtag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.towlday,greg-towlDay,fixed,25,5,gregorian,,,,culture,houmoristic,1,,,
Pfingsten,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.pentecost,greg-pentecost,easterly,,,gregorian,easter,49,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Pfingstmontag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.pentecostmonday,greg-pentecostMonday,easterly,,,gregorian,easter,50,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",
Tag der Deutschen Einheit,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.germanunity,greg-germanUnity,fixed,3,10,gregorian,,,,historical,"politics, government",5,de_DE,de_DE,
Reformationstag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.reformationDay,greg-reformationDay,fixed,31,10,gregorian,,,,religion,"christian, lutherans, calvians",3,de_DE,,
Allerheiligen,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.allSaintsDay,greg-allSaintsDay,fixed,1,11,gregorian,,,,religion,"christian, catholics",3,de_DE,,
Mauerfall,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.fallOfTheWall,greg-fallOfTheWall,,9,11,gregorian,,,,politics,"revolution, resistance",4,de_DE,,
Buß und Betttag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.dayOfPrayerAndRepentance,greg-prayerAndRepentance,weekdayly,12,25,gregorian,sunday,-5,-4,religion,"christian, lutherans, calvians",5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",europe
Totensonntag,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.deadSunday,greg-deadSunday,weekdayly,12,25,gregorian,sunday,-5,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",europe
1. Advent,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.firstAdvent,greg-firstAdvent,weekdayly,12,25,gregorian,sunday,-4,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",europe
2. Advent,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.secondAdvent,greg-secondAdvent,weekdayly,12,25,gregorian,sunday,-3,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",europe
3. Advent,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.thirdAdvent,greg-thirdAdvent,weekdayly,12,25,gregorian,sunday,-2,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",europe
4. Advent,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.forthAdvent,greg-forthAdvent,weekdayly,12,25,gregorian,sunday,-1,,religion,christian,5,"de_DE,de_AT,de_CH","de_DE,de_AT,de_CH",europe
,,,,,,,,,,,,,,,
Chin. Neujahr,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.chin.newyear,chin-newYear,fixed,1,1,chinese,,,,culture,,5,zh_CN,,
,,,,,,,,,,,,,,,
Vesakh,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.buddh.indean.vesakh,ind-vesakh,moonly,2,,indian,fullmoon,1,,religion,buddhist,5,_all,,
,,,,,,,,,,,,,,,
Laylat Al Baraat (Nacht der Vergebung),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.lailatAlBaraa,isl-lailatAlBaraa,fixed,14,8,islamic,,,,religion,"islamic, sunnits, schiits",5,,,
Ramadan (Beginn der Fastenzeit),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.ramadan,isl-ramadan,fixed,1,9,islamic,,,,religion,"islamic, sunnits, schiits",5,,,
Laylat al-Qadr (Nacht der Bestimmung),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.laylatulQadr,isl-laylatAlQadr,fixed,27,9,islamic,,,,religion,"islamic, sunnits, schiits",4,,,
Eid al-Fitr (Tag des Fastenbrechens),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.eidAlFitr,isl-eidAlFitr,fixed,1,10,islamic,,,,religion,"islamic, sunnits, schiits",4,,,
Eid ul-Adha (Opferfest),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.eidUlAdha,isl-eidUlAdha,fixed,10,12,islamic,,,,religion,"islamic, sunnits, schiits",4,,,
Aschura,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.aschura,isl-aschura,fixed,10,1,islamic,,,,culture,"islamic, sunnits, schiits",2,,,
Isl. Neujahr,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.newYear,isl-newYear,fixed,1,1,islamic,,,,religion,"islamic, sunnits, schiits",4,,,
Sunniten: Geburtstag des Propheten (Maulid an-Nabī) ,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.sunnit.MaulidAnNabi,isl-sunnit-maulidAnNabi,fixed,17,3,islamic,,,,religion,"islamic, sunnits",3,,,
Schiiten: Geburtstag des Propheten (Maulid an-Nabī) ,LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.schiit.MaulidAnNabi,isl-schiit-maulidAnNabi,fixed,12,3,islamic,,,,religion,"islamic, schiits",3,,,
"Eidgenössischer Dank-, Buss- und Bettag",LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.ch.dayOfThanksPrayerAndRepentance,greg-ch-thanksPrayerAndRepentance,weekdayinmonth,9,,gregorian,sunday,3,,culture,,4,de_CH,,
,,,,,,,,,,,,,,,
Jüd. Neujahrsfest (Rosch Haschana),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.roschHaschana,hebr-roschHaschana,fixed,1,1,hebrew,,,,culture,jewish,3,he_IL,,
Jüd. Neujahrsfest 2. Tag  (Rosch Haschana II),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.roschHaschanaIi,hebr-roschHaschanaIi,fixed,1,2,hebrew,,,,culture,jewish,3,he_IL,,
Versöhnungstag (Jom Kippur),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomKippur,hebr-jomKippur,fixed,1,10,hebrew,,,,religion,jewish,3,he_IL,,
Laubhüttenfest (Sukkot),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.sukkot,hebr-sukkot,fixed,1,15,hebrew,,,,religion,jewish,3,he_IL,,
Schlussfest (Schemini Azeret),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.scheminiAzeret,hebr-scheminiAzeret,fixed,1,23,hebrew,,,,religion,jewish,3,he_IL,,
Torafreudenfest (Simchat Tora),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.simchatTora,hebr-simchatTora,fixed,1,24,hebrew,,,,religion,jewish,4,he_IL,,
Tempelweihfest (Chanukka),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.chanukka,hebr-chanukka,fixed,3,25,hebrew,,,,religion,jewish,4,he_IL,,
Neujahrsfest der Bäume (Tu Bischwat),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.tuBischwat,hebr-tuBischwat,fixed,5,15,hebrew,,,,religion,jewish,4,he_IL,,
Errettung der Juden in Persien (Purim),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.purim,hebr-purim,leapmonth,6,14,hebrew,,,,religion,jewish,4,he_IL,,
Beginn des 1. Wallfahrtfestes (Pessach / Überschreitung),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.pessach ,hebr-pessach,fixed,7,15,hebrew,,,,religion,jewish,4,he_IL,,
1. Tag des Wochenfests (Schawuot),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.firstSchawuot ,hebr-firstSchawuot,fixed,9,6,hebrew,,,,religion,jewish,4,he_IL,,
2. Tag des Wochenfests (Schawuot),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.secondSchawuot  ,hebr-secondSchawuot,fixed,9,7,hebrew,,,,religion,jewish,4,he_IL,,
Gedenktag an die Opfer der Schoa (Jom Haschoa),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomHaschoa ,hebr-jomHaschoa,fixed,7,27,hebrew,,,,historical,jewish,4,he_IL,,
Unabhängigkeitstag (Jom Ha’azma’ut),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomHaAzmaUt ,hebr-jomHaAzmaUt,fixed,8,5,hebrew,,,,politics,jewish,4,he_IL,,
Jerusalemtag (Jom Jeruschalajim),LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomJeruschalajim ,hebr-jomJeruschalajim,fixed,8,28,hebrew,,,,historical,jewish,4,he_IL,,
DOMTEST;
        $testYaml = '';
        $rawArray = CsvYamlJsonMapperUtility::mapCsvToRawArray($myTestString);
        $filteredArray = CsvYamlJsonMapperUtility::removeEmptyRowCsv($rawArray, 0);
        $checkedArray = CsvYamlJsonMapperUtility::reorganizeSimpleArrayByHeadline($filteredArray);
        $checkYaml = CsvYamlJsonMapperUtility::mapAssoativeArrayToYaml($checkedArray);
        $this->assertEquals($testYaml, $checkYaml, 'use a simple csv-string to check various specific cases.');
    }
}
