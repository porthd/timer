# Extension Timer - Version 13.x

<a name="Table of Contents"></a>

## Table of Contents

- [Motivation](./#Motivation)
- [Installation](./#Installation)
- [For editors](./#Editors)
    - [Variants for defining repetitions of content elements and pages](./#Repetitions)
    - [Using appointment lists](./#AppointmentListsUse)
    - [Test cases / code examples](./#Examples)
- [For integrators](./#Integrators)
    - [View helper for dates in non-Gregorian formats](./#Formats)
    - [View helper for Flexform data](./#FlexformData)
    - [Remapping of data](./#Remapping)
- [For developers](./#Developers)
    - [Own data models](./#Data models)
    - [Appointment lists](./#Appointment lists)
- [Documentation before version 13](./#beforeVersion13)

<a name="Motivation"></a>

## Motivation

The idea for this extension came about because a pub offered the
event ``Full Moon Party`` and because TYPO3 did not offer the option of defining
periodic display of content.

For version 13, the documentation was streamlined and revised.

<a name="Installation"></a>

## Installation

- Install in one of the two classic ways:
    - with the extension manager in the TYPO3 admin tools
    - with the composer ``composer require porthd/timer``
- You must activate the planner task ``update``, whereby the planner should be
  triggered via a cron job at least twice as often as the smallest repetition
  interval is long.
- You may also need to install the appropriate Typoscript for the test cases.

<a name="Editors"></a>

## For editors

<a name="Repetitions"></a>

### Variants for defining repetitions of content elements and pages

In the page properties or in the content element, you define the model of the
repetition in the
`Periods`
tab in the `tx_timer_selector` selection field, the timer model.

Use the toggle field `tx_timer_scheduler` to define whether the scheduler should
consider the
page or the content element at all.

After automatic saving, you define the actual parameters for the
specific repetition in the `periods` tab in the
Flexform parameter field `tx_timer_timer`.

The extension currently has the following astronomical and calendar types of
repetition or repetition lists predefined.
The selection of parameters is presented using an example below.

#### `General` tab in every model for repetitions

Every repetition model contains a
`General` tab in the Flexform parameters field.

In this tab, the general handling of time zones is defined in the toggle
field `useTimeZoneOfFrontend` and in the
`timeZoneOfEvent` selection field.

In the date fields within this tab, a final
start time `ultimateBeginningTimer`
and a final end time `ultimateEndingTimer` are defined. These two
fields define when a content element
or a page should be shown for the first time at the earliest and the last time
at the latest.

These fields replace the TYPO3 fields `starttime` and `endtime` if the
`tx_timer_scheduler` toggle field mentioned above is set to active.
The fields are necessary because the scheduler task `updateTimer` uses the TYPO3
fields `starttime` and `endtime` to periodically show and hide content and pages
at a calculated time.

#### Sun and Moon

##### Repetitions relative to one of the four moon phases [MoonphaseRelTimer.php](..%2FClasses%2FCustomTimer%2FMoonphaseRelTimer.php)

Example: Exactly 3600 minutes (two days and twelve hours) (calculation
field `start time defined by the distance in minutes relative to the beginning of the day with the selected moon phase`)
after the start of each day with the waning crescent moon (selection
field `moonphase`) the content element is active for 200 minutes (three hours
and 20 minutes) (calculation
field `time span between -14400 and +14400 minutes`).

##### Repetitions relative to moonrise and moonset [MoonriseRelTimer.php](..%2FClasses%2FCustomTimer%2FMoonriseRelTimer.php)

Example: 360 minutes (6 hours) (calculation
field `minutes relativ to the selected moon-status`) after moonrise (selection
field `status of the moon`) in Bremen, defined by longitude (number
field `longitude`) and latitude (number field `latitude`), the content element
is active for 420 minutes (7 hours) (calculation
field `time span between -1439 and +1439 minutes`).

##### Repetitions relative to the position of the sun [SunriseRelTimer.php](..%2FClasses%2FCustomTimer%2FSunriseRelTimer.php)

Example: Exactly 300 minutes (5 hours) (select
field `minutes relative to selected time marker`) after the sunrise (select
field `starttime defined by sunposition`) at the anus of the world, determined
by latitude (number field `latitude`) and longitude (number field `longitude`),
the content element is shown for 420 minutes (seven hours) (number
field `time span between -1340 and +1340 minutes`). If the time span is defined
via a second position of the sun (select
field `active time period defined by fixed times or by the distance to the next sun position defined here (observe natural order)`),
this is preferred.

#### Gregorian calendar

##### Repetitions relative to the time of day [DailyTimer.php](..%2FClasses%2FCustomTimer%2FDailyTimer.php)

Example: Every weekday except Sunday (toggles for `active weekday`) from 11:35 (
time field `reference time for active time period`) for two hours (calculation
field `time period between -1439 and +1439 minutes`).

##### _Repetitions relative to fixed days in non-Gregorian
calendars [CalendarDateRelTimer.php](..%2FClasses%2FCustomTimer%2FCalendarDateRelTimer.php)_

_In planning. Looking for sponsors._

##### Repetitions relative to specific days in the Gregorian calendar [DatePeriodTimer.php](..%2FClasses%2FCustomTimer%2FDatePeriodTimer.php)

Example: From 1/23/2015 12:35 (time field `start of period`) for 120 minutes (
calculation field `active time period relative to start of period`) every 5 (
number field `period length`) days (select field `period unit`).

**Note:** With the period unit `Month`, the month in the date is simply
increased by one. If the day in the date is greater than 28, this can lead to
confusion.
The month after January 30th is February 30th. The date February 30th does not
exist in the
calendar. PHP converts it to a correct date. Depending on whether it is a
leap year or not, the date February 30th becomes either March 1st (leap year) or
March 2nd (not a leap year).

##### Repetitions relative to the Easter holiday [EasterRelTimer.php](..%2FClasses%2FCustomTimer%2FEasterRelTimer.php)

Example: Exactly 200 hours (calculation
field `minutes relative to the selected named date`) away from the start of
Easter Sunday (selection
field `selection of named holidays such as easter or christmas`), a content
element is active for the time of 1440 minutes (= 1 day) (calculation
field `time span between -444444 and +444444 minutes`) according to the
Gregorian calendar (selection field `use of the calendar for Easter; see (*)`).

**Note:** Easter Sunday, Whitsun Sunday, Shrove Monday, Good Friday and
Ascension Day are supported as Easter-based holidays. The selection list also
includes the calendar-fixed holidays First Advent, First Christmas Day, New
Year's Eve, New Year's Day, Labour Day, World Towel Day and World Stupidity Day.

##### Repetitions relative to weekdays in the month [WeekdayInMonthTimer.php](..%2FClasses%2FCustomTimer%2FWeekdayInMonthTimer.php)

Example: On every third last (switch field `position of weekday within month`
plus
switch `switch ON if the active weekday(s) should be counted from the end of the month`)
Friday (switch field `active weekday`) in the months February, May, August and
November (switch field `active month`) the content element is displayed for 120
minutes (-2 hours) (number field `time span between -1439 and +1439 minutes`)
until 12:00 (time field `start time`).

##### Repetitions relative to weekdays [WeekdaylyTimer.php](..%2FClasses%2FCustomTimer%2FWeekdaylyTimer.php)

Example: Every Friday (switch field `select active weekday(s)`) the content
element is activated.

#### non-Gregorian calendar

##### Repetitions relative to Jewish holidays [JewishHolidayTimer.php](..%2FClasses%2FCustomTimer%2FJewishHolidayTimer.php)

Example: From the start of the Yom Kippur festival (selection
field: `selection of named dates like Yom Kippur`), exactly 14400 minutes (10
days) (Calculation field `minutes relative to the selected named date`) away, a
content element is active for the time of 1440 minutes (= 1 day) (Calculation
field `active time span relative to startpoint in minutes`).

##### Repetitions relative to holidays [HolidayTimer.php](..%2FClasses%2FCustomTimer%2FHolidayTimer.php)

Example: You want to display a content element from 6:00 a.m. (calculation
field: `start time defined by the distance between -275,600 and +275,600 minutes relative to the start of the next holiday`)
for 8 hours (calculation
field `time span between -250,000 and +250,000 minutes`) for all holidays from a
stored file (text
field `local path or link to the file with the list of holidays in CSV or yaml format`)
and/or from an uploaded file (button
field `uploaded files with the list of holidays in CSV or yaml format`).

**Note:** The holidays can look like this in yaml format, whereby
additional fields below `data` are possible.

````yaml
periodlist:
- title: 'Winterferien Bremen'
  data:
    description: '- free to fill and free to add new attributes -'
  start: '2022-01-31 00:00:00'
  stop: '2022-02-01 23:59:59'
  zone: 'Europe/Berlin'
#...
````

For CSV files, a dot notation must be used in the header for `data` fields.

````csv
title,data.description,start,stop,zone
"Winterferien Bremen","- free to fill and free to add new attributes -","2022-01-31 00:00:00","2022-02-01 23:59:59","Europe/Berlin"
...
````

#### Lists

##### Repeats relative to time ranges from a list [PeriodListTimer.php](..%2FClasses%2FCustomTimer%2FPeriodListTimer.php)

Example: The list of active time ranges is defined in a file (text
field: `local path or link to the file with list`) or an uploaded file (button
field: `uploaded files with the list`) in YAML or CSV format.

##### Repetitions calculated from active and forbidden lists [RangeListTimer.php](..%2FClasses%2FCustomTimer%2FRangeListTimer.php)

Example: Every Friday (text
field `file path of the YAML definitions with list of active periods` plus
multi-selection
field `list of rows in the database with definitions for active periods`) except
during holidays and public holidays (text
field `File path of the YAML definitions with list of forbidden periods` plus
multi-selection
field `List of rows in the database with definitions for forbidden periods`).
The number of recursions (number
field `Maximum number of recursions when determining overlaps`) is limited in
order to limit conceivable infinite calculation loops by landing files with
RangeList timers.

**Note:** The resulting active ranges are reduced in size and
if necessary divided by the list of forbidden ranges.

Fragmentation of active areas is possible.

<a name="TerminlistenUse"></a>

### Using appointment lists

<a name="Examples"></a>

### Test cases / code examples

The test cases allow an initial test to see whether the extension is working as
desired.

The test cases are defined as content elements. The respective Typoscript must
be installed for each content element.

#### Content element `timersimul`

This content element allows the behavior of various view helpers that the system
provides to be tested.

It can be helpful for integrators to look for code examples for the view
helpers.

#### Content element `timerholidaycalendar`

This test element displays a calendar in the frontend that marks various
holidays of different nations in the calendar.
The list of international holidays is marked in a CSV file and is incomplete.

<a name="Integrators"></a>

## For integrators

<a name="Formats"></a>

### Viewhelper for dates in non-Gregorian formats

The viewhelper `timer:format.calendarDate` allows the output of date values
in calendars other than the Gregorian calendar.

````
<!-- fluid -->
    <div>
        <timer:format.calendarDate calendartarget="persian" locale="de_DE" format="d.m.Y H:i:s" flagformat="0">
            1600000000
        </timer:format.calendarDate>
    </div>
<!-- Output HTML -->
    <div>
        23.06.1399 14:26:40
    </div>
    <!-- gregorian date: 13.09.2020 14:26:40 -->
````

- **flagformat** determines which formatting rules should be used:
  0 = [PHP-DateTime](https://www.php.net/manual/en/datetime.format.php),
  1: [ICU Datetime Formatting](https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
  or 2 = [PHP strftime](https://www.php.net/manual/en/function.strftime.php).
- **format** defines the form of the date and time output. Supported
  are
    - DateTime notation (PHP) https://www.php.net/manual/en/datetime.format.php
    - strftime notation (
      PHP) https://www.php.net/manual/en/function.strftime.php
    - ICU rules (International Components for
      Unicode) https://unicode-org.github.io/icu/userguide/format_parse/datetime/
- **base** is important for relative dates like 'now', '+4 days' or similar.
- **timezone** defines the time zone for which a date should be output.
  You can get a list of the permitted time zone names using the
  PHP function `timezone_abbreviations_list()`. But you can also find a list
  sorted by continent in the
  PHP documentation.
- **date** allows you to specify a date from the Gregorian (Western)
  calendar, provided that no information was provided in `datestring`. You can
  define the
  value for `date` implicitly by enclosing the value with the
  viewhelper tags. If you do not specify anything, the
  current date is automatically used - if `datestring` is empty or missing.

- **datestring** allows you to specify a date from a non-Gregorian
  calendar in the format ``year/month/day hour/minute/second``, where the year
  must be four digits long and all other information two digits long. For the
  hour,
  any value from 0 to 23 is allowed. For the months, values ​​from 1 to 13
  are allowed.
- **calendarsource** defines the underlying calendar for the date
  in `datestring`. PHP allows the following values:
    - 0:buddhist
    - 1:chinese
    - 2:coptic
    - 3:dangi
    - 4:default
    - 5:ethiopic
    - 6:ethiopic-amete-alem
    - 8:gregorian
    - 9:hebrew
    - 10:indian
    - 11:islamic
    - 12:islamic-civil
    - 13:islamic-rgsa
    - 14:islamic-tbla
    - 15:islamic-umalqura
    - 16:japanese
    - 17:persian
    - 18:roc (?)
    - 19:julian for the Julian calendar
- **calendartarget** defines the calendar for which the date
  should be output. PHP allows the same values defined in the previous field.
- **locale** determines the regional localization and consists of the
  two-letter language code (de, en, fr, es, ...) and the national code (DE, GB,
  US, AT, CH, FR, ...) separated by an
  underscore. The
  value in __locale__ could, for example, look like this:
  `de_DE`, `en_GB` or `es_US`.

<a name="FlexformDaten"></a>

### Viewhelper for Flexform data

The Viewhelper helps you to use the string from Flexform fields directly,
without having to consider the Flexform superstructure in Fluid.

````
<!-- Fluid -->
<timer:flex flexstring="{data.tx_timer_timer}"
            as="timerflex"
            flattenkeys="data,general,timer,sDEF,lDEF,vDEF"
>
    <f:for each="{timerflex}" as="value" key="key">
        <tr>
            <td>{key}</td>
            <td>{value}</td>
        </tr>
    </f:for>
</timer:flex>

````

<a name="Remapping"></a>

### Remapping array data

The data processor ``PhpMappingProcessor`` is suitable for restructuring array
data.
Its basic principle is simple.
The output array is passed to the data processor with `inputOrigin`. If not
specified, the current processor array is used.
In TypoScript, you recreate the target structure of the target array to be
created under the `output` attribute. The endpoints in the target array are then
assigned

- constants
- references to data in the input array or
- functions.

The parameters of the functions are resolved recursively.
The target array or the JSON string (`outputformat`) is available in the
template as a
variable with the name specified in `as`.

##### Example with TypoScript

````
        15 = Porthd\Timer\DataProcessing\PhpMappingProcessor
        15 {

            outputformat = json
            as = holidaycalendarJson

            limiter {
                inputPart = '.'
                output {
                    path = @
                    part = .
                    params = ,
                    default = #
                }
            }

            inputOrigin = holidayList
            inputType = rows

            output {

                category = allday
                start = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateStart@,'Y-m-d','Europe/Berlin')
                end = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateEnd@,'Y-m-d','Europe/Berlin')
                title = @cal.eventtitle@
                body = Porthd\Timer\UserFunc\ResolveLocales->reduceSingleLocaleToNation(@cal.add.freelocale@)
                id = @cal.identifier@
                basetitle = @cal.title@
                calendarId = @cal.tag@
            }
        }

````

##### Parameter
| Parameter    | Default          | Description
|--------------|------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|              | **_Main level_** |
| configFile   |                  | Instead of the configuration in TypoScript, a YAML file with all configuration instructions can also be created.
| inputOrigin  | _all             | Path to the variable area for the data created in the previous DataProcessor flow. `_all` means the entire array.
| inputType    | _all             | It has the two values `rows` and `static`. `rows` expects an array and applies `output` line by line to `rows`, creating an array of records. `static` applies `output` to `rows` only once, creating a single record.
| as           | json             | Name of the variable, as it can be used later in Fluid for the output
| outputformat | json             | Type of data format. `json` (string), `array` (PHP array), `yaml` (string)
| output       | **_Main level_** | Is the starting point for the record definition (replication of the expected associative array for the data)
| */...        |                  | The sub-points define the target structure of the desired data record. The syntax of Typoscript is used to recreate the nesting structure. For a YAML file, the YAML syntax is of course used.
| limiter      | **_Main level_** | By default, the syntax for resolving the paths contains different separators. These separators can be overridden.
| */path       | @                | This limiter is used to separate a data reference from the text in the string. This makes it easy to add strings, such as in "We welcome @user@ to the test."
| */part       | .                | A data reference describes a hierarchically structured path in the input structure, similar to a folder structure on the hard drive. The separator delimits the keywords for the levels from each other.
| */defpart    | #                | It may be that a string is to be entered as a default value for a non-existent date. The default value is to be specified directly in the path. Example of a path specification: @Path#Defaultvalue@
| */params     | ,                | The separator is used to delimit the parameters of a function from each other. This is analogous to the syntax in many programming languages.
| */start      | (                | To separate the function name from the parameters, the first bracket in the string is used as a separator.
| */end        | )                | If a function is used, it must end with this delimiter. This also applies if a function is used recursively as a parameter.
| */escape     | \\               | It may happen that one of the delimiter characters is used as a character in a string. With the preceding escape character, the following character is recognized as a text character.
| */dynfunc    | ->               | In addition to normal PHP functions, you can also use user-defined functions from classes. If an instantiation of the function is necessary, the namespace of the class must be separated from the function name using this separator '->'. The mapping first instantiates the class again before the method is called. It is not recommended to change this delimiter.
| */statfunc   | ::               | In addition to normal PHP functions, you can also use user-defined functions from classes. If a static function is used, the namespace of the class must be separated from the function name using this separator '::'. It is not recommended to change this delimiter. It is recommended to use mindless static functions in the mapping.

<a name="Developer"></a>

## For developers

<a name="Data models"></a>

### Own data models

The developer can develop his own data models, for example to announce certain
events.
To use the extension timer, you only need to ensure that the following fields
with the TCA definitions from the extension are also present in the data model:

- tx_timer_scheduler
- tx_timer_timer
- tx_timer_selector

<a name="beforeVersion13"></a>

## Documentation before version 13

The current documentation has been streamlined. In addition to the code, the
documentation also contains information that you cannot find here.

- [German ](de.Prior13_ReadMe.md)
- [English ](Prior13_ReadMe.md)
