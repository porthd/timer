<?php

// Thanks to hellbringer for this simplified version:
// last visited (Y-m-d) 2023-02-03: https://www.php.de/forum/webentwicklung/php-fortgeschrittene/1607278-chinesisches-datum-westliches-datum-fehlerhaft-falsche-konfiguriert-oder-php-bug
$locale = 'de_DE@calendar=chinese';

$traditionalFormatter = new IntlDateFormatter(
    $locale,
    IntlDateFormatter::MEDIUM,
    IntlDateFormatter::SHORT,
    'Europe/Berlin',
    IntlDateFormatter::TRADITIONAL
);

$gregorianFormatter = new IntlDateFormatter(
    $locale,
    IntlDateFormatter::MEDIUM,
    IntlDateFormatter::SHORT,
    'Europe/Berlin',
    IntlDateFormatter::GREGORIAN
);

$dateTime = new DateTime();

$traditionalDate = $traditionalFormatter->format($dateTime);
$parsedTimestamp = $traditionalFormatter->parse($traditionalDate);

var_dump($gregorianFormatter->format($dateTime));
var_dump($gregorianFormatter->format($parsedTimestamp));
