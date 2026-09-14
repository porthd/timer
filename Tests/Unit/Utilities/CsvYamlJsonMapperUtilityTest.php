<?php

declare(strict_types=1);

namespace Porthd\Timer\Tests\Unit\Utilities;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Porthd\Timer\Utilities\CsvYamlJsonMapperUtility;

class CsvYamlJsonMapperUtilityTest extends TestCase
{
    #[Test]
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
        $testYaml = <<<EXPECTYAML
mapped: 
    - 
        title: 'Heiligabend'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmasEve'
        identifier: 'greg-christmasEve'
        type: 'fixed'
        arg: 
            month: '12'
            day: '24'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '3'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Weihnachten'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmas'
        identifier: 'greg-christmas'
        type: 'fixed'
        arg: 
            month: '12'
            day: '25'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '4'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Weihnachten (2. Tag)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmasSecondDay'
        identifier: 'greg-christmasSecondDay'
        type: 'fixed'
        arg: 
            month: '12'
            day: '26'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Silvester'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.silvester'
        identifier: 'greg-silvester'
        type: 'fixed'
        arg: 
            month: '31'
            day: '12'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
            rank: '4'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Neujahr'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.newyear'
        identifier: 'greg-newYear'
        type: 'fixed'
        arg: 
            month: '1'
            day: '1'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Valentinstag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.eco.greg.valentinsday'
        identifier: 'greg-valentinsDay '
        type: 'fixed'
        arg: 
            month: '14'
            day: '2'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'economic'
        add: 
            category: 
            rank: '2'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Rosenmontag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.rosemonday'
        identifier: 'greg-roseMonday'
        type: 'easterly'
        arg: 
            month: ''
            day: ''
            calendar: 'gregorian'
            status: 'easter'
            statusCount: '-48'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '3'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Fasching'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.carnival'
        identifier: 'greg-carnival'
        type: 'easterly'
        arg: 
            month: ''
            day: ''
            calendar: 'gregorian'
            status: 'easter'
            statusCount: '-47'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '3'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Karfreitag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.goodfriday'
        identifier: 'greg-goodFriday'
        type: 'easterly'
        arg: 
            month: ''
            day: ''
            calendar: 'gregorian'
            status: 'easter'
            statusCount: '-3'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '4'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Ostern'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.easter'
        identifier: 'greg-easter'
        type: 'easterly'
        arg: 
            month: ''
            day: ''
            calendar: 'gregorian'
            status: 'easter'
            statusCount: '0'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Ostermontag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.eastermonday'
        identifier: 'greg-easterMonday'
        type: 'easterly'
        arg: 
            month: ''
            day: ''
            calendar: 'gregorian'
            status: 'easter'
            statusCount: '1'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Welttag der Dummheit'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.stupidity'
        identifier: 'greg-stupidityDay'
        type: 'fixed'
        arg: 
            month: '16'
            day: '4'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
                - 'philosophy'
            rank: '1'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Tag der Arbeit'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.labourday'
        identifier: 'greg-labourDay'
        type: 'fixed'
        arg: 
            month: '1'
            day: '5'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'politics'
        add: 
            category: 
                - 'communist'
                - 'laborunion'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Muttertag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.mothersday'
        identifier: 'greg-mothersDay'
        type: 'weekdayinmonth'
        arg: 
            month: '5'
            day: ''
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '2'
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
                - 'politics'
                - 'gender'
            rank: '2'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Welthandtuchtag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.towlday'
        identifier: 'greg-towlDay'
        type: 'fixed'
        arg: 
            month: '25'
            day: '5'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
                - 'houmoristic'
            rank: '1'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Pfingsten'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.pentecost'
        identifier: 'greg-pentecost'
        type: 'easterly'
        arg: 
            month: ''
            day: ''
            calendar: 'gregorian'
            status: 'easter'
            statusCount: '49'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Pfingstmontag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.pentecostmonday'
        identifier: 'greg-pentecostMonday'
        type: 'easterly'
        arg: 
            month: ''
            day: ''
            calendar: 'gregorian'
            status: 'easter'
            statusCount: '50'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: ''
    - 
        title: 'Tag der Deutschen Einheit'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.germanunity'
        identifier: 'greg-germanUnity'
        type: 'fixed'
        arg: 
            month: '3'
            day: '10'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'historical'
        add: 
            category: 
                - 'politics'
                - 'government'
            rank: '5'
            locale: 
                - 'de_DE'
            freelocale: 
                - 'de_DE'
            alias: ''
    - 
        title: 'Reformationstag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.reformationDay'
        identifier: 'greg-reformationDay'
        type: 'fixed'
        arg: 
            month: '31'
            day: '10'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
                - 'lutherans'
                - 'calvians'
            rank: '3'
            locale: 
                - 'de_DE'
            freelocale: 
            alias: ''
    - 
        title: 'Allerheiligen'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.allSaintsDay'
        identifier: 'greg-allSaintsDay'
        type: 'fixed'
        arg: 
            month: '1'
            day: '11'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
                - 'catholics'
            rank: '3'
            locale: 
                - 'de_DE'
            freelocale: 
            alias: ''
    - 
        title: 'Mauerfall'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.hist.greg.fallOfTheWall'
        identifier: 'greg-fallOfTheWall'
        type: ''
        arg: 
            month: '9'
            day: '11'
            calendar: 'gregorian'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'politics'
        add: 
            category: 
                - 'revolution'
                - 'resistance'
            rank: '4'
            locale: 
                - 'de_DE'
            freelocale: 
            alias: ''
    - 
        title: 'Buß und Betttag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.dayOfPrayerAndRepentance'
        identifier: 'greg-prayerAndRepentance'
        type: 'weekdayly'
        arg: 
            month: '12'
            day: '25'
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '-5'
            secDayCount: '-4'
        tag: 'religion'
        add: 
            category: 
                - 'christian'
                - 'lutherans'
                - 'calvians'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: 'europe'
    - 
        title: 'Totensonntag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.deadSunday'
        identifier: 'greg-deadSunday'
        type: 'weekdayly'
        arg: 
            month: '12'
            day: '25'
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '-5'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: 'europe'
    - 
        title: '1. Advent'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.firstAdvent'
        identifier: 'greg-firstAdvent'
        type: 'weekdayly'
        arg: 
            month: '12'
            day: '25'
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '-4'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: 'europe'
    - 
        title: '2. Advent'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.secondAdvent'
        identifier: 'greg-secondAdvent'
        type: 'weekdayly'
        arg: 
            month: '12'
            day: '25'
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '-3'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: 'europe'
    - 
        title: '3. Advent'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.thirdAdvent'
        identifier: 'greg-thirdAdvent'
        type: 'weekdayly'
        arg: 
            month: '12'
            day: '25'
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '-2'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: 'europe'
    - 
        title: '4. Advent'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.forthAdvent'
        identifier: 'greg-forthAdvent'
        type: 'weekdayly'
        arg: 
            month: '12'
            day: '25'
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '-1'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'christian'
            rank: '5'
            locale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            freelocale: 
                - 'de_DE'
                - 'de_AT'
                - 'de_CH'
            alias: 'europe'
    - 
        title: 'Chin. Neujahr'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.chin.newyear'
        identifier: 'chin-newYear'
        type: 'fixed'
        arg: 
            month: '1'
            day: '1'
            calendar: 'chinese'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
            rank: '5'
            locale: 
                - 'zh_CN'
            freelocale: 
            alias: ''
    - 
        title: 'Vesakh'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.buddh.indean.vesakh'
        identifier: 'ind-vesakh'
        type: 'moonly'
        arg: 
            month: '2'
            day: ''
            calendar: 'indian'
            status: 'fullmoon'
            statusCount: '1'
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'buddhist'
            rank: '5'
            locale: 
                - '_all'
            freelocale: 
            alias: ''
    - 
        title: 'Laylat Al Baraat (Nacht der Vergebung)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.lailatAlBaraa'
        identifier: 'isl-lailatAlBaraa'
        type: 'fixed'
        arg: 
            month: '14'
            day: '8'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
                - 'schiits'
            rank: '5'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Ramadan (Beginn der Fastenzeit)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.ramadan'
        identifier: 'isl-ramadan'
        type: 'fixed'
        arg: 
            month: '1'
            day: '9'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
                - 'schiits'
            rank: '5'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Laylat al-Qadr (Nacht der Bestimmung)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.laylatulQadr'
        identifier: 'isl-laylatAlQadr'
        type: 'fixed'
        arg: 
            month: '27'
            day: '9'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
                - 'schiits'
            rank: '4'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Eid al-Fitr (Tag des Fastenbrechens)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.eidAlFitr'
        identifier: 'isl-eidAlFitr'
        type: 'fixed'
        arg: 
            month: '1'
            day: '10'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
                - 'schiits'
            rank: '4'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Eid ul-Adha (Opferfest)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.eidUlAdha'
        identifier: 'isl-eidUlAdha'
        type: 'fixed'
        arg: 
            month: '10'
            day: '12'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
                - 'schiits'
            rank: '4'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Aschura'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.aschura'
        identifier: 'isl-aschura'
        type: 'fixed'
        arg: 
            month: '10'
            day: '1'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
                - 'schiits'
            rank: '2'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Isl. Neujahr'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.newYear'
        identifier: 'isl-newYear'
        type: 'fixed'
        arg: 
            month: '1'
            day: '1'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
                - 'schiits'
            rank: '4'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Sunniten: Geburtstag des Propheten (Maulid an-Nabī) '
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.sunnit.MaulidAnNabi'
        identifier: 'isl-sunnit-maulidAnNabi'
        type: 'fixed'
        arg: 
            month: '17'
            day: '3'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'sunnits'
            rank: '3'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Schiiten: Geburtstag des Propheten (Maulid an-Nabī) '
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.isl.schiit.MaulidAnNabi'
        identifier: 'isl-schiit-maulidAnNabi'
        type: 'fixed'
        arg: 
            month: '12'
            day: '3'
            calendar: 'islamic'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'islamic'
                - 'schiits'
            rank: '3'
            locale: 
            freelocale: 
            alias: ''
    - 
        title: 'Eidgenössischer Dank-, Buss- und Bettag'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.cult.greg.ch.dayOfThanksPrayerAndRepentance'
        identifier: 'greg-ch-thanksPrayerAndRepentance'
        type: 'weekdayinmonth'
        arg: 
            month: '9'
            day: ''
            calendar: 'gregorian'
            status: 'sunday'
            statusCount: '3'
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
            rank: '4'
            locale: 
                - 'de_CH'
            freelocale: 
            alias: ''
    - 
        title: 'Jüd. Neujahrsfest (Rosch Haschana)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.roschHaschana'
        identifier: 'hebr-roschHaschana'
        type: 'fixed'
        arg: 
            month: '1'
            day: '1'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
                - 'jewish'
            rank: '3'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Jüd. Neujahrsfest 2. Tag  (Rosch Haschana II)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.roschHaschanaIi'
        identifier: 'hebr-roschHaschanaIi'
        type: 'fixed'
        arg: 
            month: '1'
            day: '2'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'culture'
        add: 
            category: 
                - 'jewish'
            rank: '3'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Versöhnungstag (Jom Kippur)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomKippur'
        identifier: 'hebr-jomKippur'
        type: 'fixed'
        arg: 
            month: '1'
            day: '10'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '3'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Laubhüttenfest (Sukkot)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.sukkot'
        identifier: 'hebr-sukkot'
        type: 'fixed'
        arg: 
            month: '1'
            day: '15'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '3'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Schlussfest (Schemini Azeret)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.scheminiAzeret'
        identifier: 'hebr-scheminiAzeret'
        type: 'fixed'
        arg: 
            month: '1'
            day: '23'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '3'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Torafreudenfest (Simchat Tora)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.simchatTora'
        identifier: 'hebr-simchatTora'
        type: 'fixed'
        arg: 
            month: '1'
            day: '24'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Tempelweihfest (Chanukka)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.chanukka'
        identifier: 'hebr-chanukka'
        type: 'fixed'
        arg: 
            month: '3'
            day: '25'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Neujahrsfest der Bäume (Tu Bischwat)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.tuBischwat'
        identifier: 'hebr-tuBischwat'
        type: 'fixed'
        arg: 
            month: '5'
            day: '15'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Errettung der Juden in Persien (Purim)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.purim'
        identifier: 'hebr-purim'
        type: 'leapmonth'
        arg: 
            month: '6'
            day: '14'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Beginn des 1. Wallfahrtfestes (Pessach / Überschreitung)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.pessach '
        identifier: 'hebr-pessach'
        type: 'fixed'
        arg: 
            month: '7'
            day: '15'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: '1. Tag des Wochenfests (Schawuot)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.firstSchawuot '
        identifier: 'hebr-firstSchawuot'
        type: 'fixed'
        arg: 
            month: '9'
            day: '6'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: '2. Tag des Wochenfests (Schawuot)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.secondSchawuot  '
        identifier: 'hebr-secondSchawuot'
        type: 'fixed'
        arg: 
            month: '9'
            day: '7'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'religion'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Gedenktag an die Opfer der Schoa (Jom Haschoa)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomHaschoa '
        identifier: 'hebr-jomHaschoa'
        type: 'fixed'
        arg: 
            month: '7'
            day: '27'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'historical'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Unabhängigkeitstag (Jom Ha’azma’ut)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomHaAzmaUt '
        identifier: 'hebr-jomHaAzmaUt'
        type: 'fixed'
        arg: 
            month: '8'
            day: '5'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'politics'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''
    - 
        title: 'Jerusalemtag (Jom Jeruschalajim)'
        eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.rel.hebr.jomJeruschalajim '
        identifier: 'hebr-jomJeruschalajim'
        type: 'fixed'
        arg: 
            month: '8'
            day: '28'
            calendar: 'hebrew'
            status: ''
            statusCount: ''
            secDayCount: ''
        tag: 'historical'
        add: 
            category: 
                - 'jewish'
            rank: '4'
            locale: 
                - 'he_IL'
            freelocale: 
            alias: ''

EXPECTYAML;
        $rawArray = CsvYamlJsonMapperUtility::mapCsvToRawArray($myTestString);
        $filteredArray = CsvYamlJsonMapperUtility::removeEmptyRowCsv($rawArray, 0);
        $checkedArray = CsvYamlJsonMapperUtility::reorganizeSimpleArrayByHeadline($filteredArray);
        $checkYaml = CsvYamlJsonMapperUtility::mapAssoativeArrayToYaml($checkedArray);
        self::assertEquals($testYaml, $checkYaml, 'use a simple csv-string to check various specific cases.');
    }
}
