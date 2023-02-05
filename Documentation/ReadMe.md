# extension timer - version 11.x

## Preliminary remark

The basis for this documentation is the German variant `de.ReadMe.md`. The English version was translated
using `google.translate.de`. The documentation includes a presentation that I prepared for the TYPO3 Barcamp in
Kamp-Lintfort in 2022

## Attention

- ~~8. November 2022: The Timer `RangeListTimer` und `PeriodListTimer` don`t work correctly.~~
- 27 Dezember 2022: The timer `RangeListTimer` and `PeriodListTimer` show work now. Unittest are defined and aproved the function of that timers.

## Motivation

TYPO3 provides the fields `starttime` and `endtime` in its standard tables. Pages can be shown and hidden at specific
times using the fields. These fields are also taken into account during caching. However, there is no way to
periodically show and hide pages, content elements and/or images at certain times.

### User stories

#### The Interactive Pub Voucher.

A restaurant owner wants to give his guests a 50% discount during happy hour. Customers receive this as a QR code on
their mobile phone when they enter the motto of the day hanging above the counter and a Like comment in the interactive
form. In the content element, the current motto is automatically checked, of course.

#### The annual company foundation week

A company celebrates its birthday by looking back at the successes of the past year. These are updated by the employees
on a day-to-day basis. The TYPO3 system takes care of the activation of this special page. The events calendar A small
concert organizer would like to offer periodic events such as poetry slams, readings or open stages mixed with special
concert dates in a list.

#### Reaction column

One party would like to show content from its home page that is similar to that of its competitors on their home pages.
Its website should automatically react to changes made by its competitors. The articles should disappear again for a
certain time.
(Actually, it has nothing to do with time. But the problem could be solved with a timer...)

#### Marketing of virtual concerts

A concert agency in December 2021 would like to organize a virtual concert in Berlin, which should be able to be
received throughout Europe. Since summer time ends in 2021, the organizer expects different time zones in Europe. The
website should display the respective local time for the users.

## Idea

A basic idea of ​​the timer extension is to split the task into two subtasks: the task of the Time management and the
start time control task. The page can be updated regularly via a task in the scheduler (cron job). The integration of
information within templates can be controlled via Viewhelper, whereby the Developers then also have to think about the
caching of the front end. It should be easy to compile simple lists via data processors, which can be displayed in the
frontend. The information from the Flexform fields can be processed in the templates with view helpers.

## Installation of the extension

You can install in one of the classic ways.

- manually
- with the extension manager of the admin tools
- with the composer ``composer require porthd/timer``

Check if the database schema has been updated. The typoscript of the extension is to be included. Depending on the usage
requirements, a scheduler task must be activated or a data processor called in your own typescript.

## Application Aspects

### Periodic content or pages

For content or pages that appear periodically, one of the extension's console tasks must be set up. Out-of-the-box, it
evaluates the elements for which a timer is defined and for which the scheduler evaluation flag is set has been set to
active.

### Content element `timersimul` as an example

The content element `timersimul` shows an example of how the view helpers and data processors are used. In production
environments, they should be hidden from editors. It will be removed when the extension reaches `beta` status.

### Content element `periodlist` for simple appointment lists
The content element `periodlist` has a similar structure to the content element `textmedia`.
It also allows the output of simple appointment lists, provided for the preset
Timer `periodlisttimer` a valid yaml file with a list of appointments is stored.
#### Remarks
##### _Note 1_
In order to enable the most flexible integration of appointment lists, there are two input fields `yamlPeriodFilePath` and `yamlPeriodFalRelation`.
The field `yamlPeriodFilePath` has more the integrator in mind and allows four variants,
to specify the location of the YAML file:
1. Absolute path specification if necessary. also with relative path
2. Path specification with the prefix `EXT:`
3. Simple URL starting with `http://` or with `https://`
4. URL with server password in the format `username:password:url`, where the actual url starts with url starting with `http://` or with `https://`. The YAML instruction `import` is not supported for the URL information.

The `yamlPeriodFalRelation` field has more of the editor in mind and allows the integration of the YAML file via the TYPO3 backend.
Here the editor also has the option of including several files, which the timer treats as one large list.

##### _Note 2_
Various data can be stored in the `data` attribute so that it is structured using a suitable partial or template
Special information such as admission price, advance sales prices or similar can be transferred via file.
This form works well when it comes to automating data about the format of a YAML file from other sources
to accept. This saves entering the data in the backend.

#### representation in a calendar

Two path fields for JavaScript and for style sheets were added to the Flexform.
In this way it is possible to display the appointments in calendar form. The default settings are set so
that the school holidays for Lower Saxony and Bremen from the year 2022 are shown in a calendar.

#### dataprocessors for this timer

Three data processors were defined so that the data could be read.
The `FlexToArrayProcessor` allows reading Flexform fields and converting them into simple arrays.
This way you can dynamically load the JavaAScript and style sheet files from the content element.
The DataProcessor `PeriodlistProcessor` allows the reading of the appointment list, which is stored in the PeriodlistTimer in the Yaml file
is defined. In addition to the actual fields, the data processor also generates the corresponding DatTime objects for the start and end times of the appointments and calculates the number of days (24 hours = 1 day) between the appointments.
The third data processor `MappingProcessor` is required to transfer the appointment data to the Fluid template as a JSON string.
In this way, the data can easily be made available to the calendar framework via an HTML attribute.

### Use of the periodic timer (Customtimer)

The extension currently comes with several periodic timers. Two of the timers are not yet fully developed. This is one
of the reasons why the status of the extension was changed to
`experimental`.

#### Customtimer - General

You can enable the timers in the constants of the extension.

Custom timers must derive from the interface ``Porthd\Timer\Interfaces\TimerInterface`` and be able to
in ``ext_localconf.php`` to be added to the timer extension as follows:

````
            \Porthd\Timer\Utilities\ConfigurationUtility::mergeCustomTimer(
                [\Vendor\YourNamespaceTimer\YourTimer::class, ]
            );
````

#### Predefined Timer - Overview

* CalendarDateRelTimer - (In preparation) Most religious, historical, political,
  economic or other holidays are fixed on a date in a calendar. The powerful
  want to avoid mentally overwhelming the common people (so that every Dölmer
  also appreciates the festival at the right time). In the course of human
  history, many different calendar systems have been developed and there are
  many regionally different important festivals. The timer wants to take this
  variability into account by allowing the consideration of different calendar
  systems.
  (Example 5760 minutes (=2 days) after Ramadan (1.9.; Islamic calendar) for 720
  minutes (=6 hours))
* DailyTimer - active times recurring daily for a few minutes
  (daily from 10:00 a.m. for 120 minutes)
* DatePeriodTimer - active times recurring periodically for a few minutes relative to a start time. Consider it, that
  because of the return of the time to the UTC zone during the calculation DST can produce unexpected results for hourly
  periodicities.
  (whole day every year for birthdays, every week for 120 minutes until 12:00 from May 13, 1970, ..)
* DefaultTimer - Default timer/null element
* EasterRelTimer - Determines the active period relative to important German public holidays (New Year's Eve, New Year's Eve, World Towel Day, Day of Stupidity) and the most important Christian public holidays (first Advent, Christmas, Shrove Monday, Good Friday, Easter, Ascension Day, Pentecost)
  (2nd Advent from 12:00 p.m. to 2:00 p.m., Rose Monday from 8:00 p.m. to 6:00 a.m. of the following day)
* JewishHolidayTimer (in progress 2022-12-28) - Periods starting relative to one
  of the Jewish holidays related to the Jewish calendar
  (IMHO: I was thinking for a long time about adding this timer because I
  consider parts of Judaism to be morally problematic. It is a practice that is
  lived and promised by orthodox
  Jews (https://www.zentralratderjuden.de/fileadmin/user_upload/pdfs/Important_Documents/ZdJ_tb2019_web.pdf
  on page 21 [german text]) that their male offspring's penises are circumcised
  for religious reasons I wonder what the value of a religion is that prides
  itself on being able to rape weak, helpless babies by using its Sacrificing
  the foreskin of the penis under its screams for the god. What distinguishes
  this religious delusion from the chosen people in its fundamental way of
  thinking from the Aryan delusion that some perverts still cling to today? From
  a humanistic point of view, the freedom of parents to educate ends for me at
  the time when they for their own religious beliefs want to do violence to
  their children Every baby has the right to express their own religion or
  beliefs freely to choose. Parents can and should influence the child's
  decision-making through speech and through their own life as an example;
  because no one knows for sure the only true truth. I think: Any rape for
  religious reasons is a perverse crime - be it by the Catholics or the Jews or
  any other sect. dr Dieter Porth)
  **!!!Warning: I cannot guarantee the correctness of the Jewish holidays, as I
  am not familiar with them at all. Please check for correctness before use.**
  _During testing, I noticed that the calculated date for Yom Kippur did not
  take into account that the holiday begins after sunset the previous day. I
  didn't feel like making a correction at this point. The Jewish calendar is not
  that important for me. For the same reason, the tests were only carried out as
  an example using the example of Yom Kippur._
  **Recommendation:** _Use the new more general timer `calendarDateRelTimer`_**
  instead
* MoonphaseRelTimer - Periods starting relative to a moon phase for a specified time period
* MoonriseRelTimer - Periods relative to moonrise or moonset for a specified time period
* PeriodListTimer - Reads active period data from a yaml file. Helpful, for example, for holiday
  lists or artist tour plans
* RangeListTimer - Reads periodic list from yaml files or from table `` and merges merge them into
  new active areas when they overlap. You can also define a list of prohibited areas, which can reduce such overlaps. (
  Example: every Tuesday and Thursday from 12pm to 2pm [active timers] except during school holidays and public holidays [forbidden timers])
* SunriseRelTimer - periods relative to sunrise and sunset
* WeekdayInMonthTimer - Periods on specific days of the week within a month starting at specific times with a specific
  duration
  (Example: every second Friday of the month in the two hours before 19:00)
* WeekdaylyTimer - Whole day of a specific weekday or days. (Example: Every Monday or Thursday)

#### CustomTimer - General parameters for all timers

Some parameters are the same for all timers. Two parameters deal with the handling of time zones. Two other parameters
determine the period in which the timer is valid at all. A parameter for controlling the scheduler was omitted. I can't
think of a use case where such an exclusion really makes sense. If you need something like this, you can program a
corresponding timer.

* timeZoneOfEvent - stores the name of the time zone to use. if the server time zone does not match the event time zone,
  the server time will be converted to the event time zone time.
  *Value range*:
  List of generated time zones. The time zone issue is important because some timers need to convert times to UTC. (
  course of the sun, ...)
  *Annotation*:
  All time zones are currently being generated. It is possible to limit the time zones to a general selection.
* useTimeZoneOfFrontend - yes/no parameters. If the value is set, the server's time zone is always used.
* ultimateBeginningTimer - Ultimate beginning of timers
  *Default*:
  January 1 0001 00:00:00
* ultimateEndingTimer - Ultimate end of the timer
  *Default*:
  December 31, 9999 23:59:59

#### Customtimer - Developer - Motivation

The timers do not cover every case. You can also define your own timer class, which must implement the `TimerInterface`.
. You integrate them via your `ext_localconf.php`. You can use your own Flexform to set your own timer give parameters.

### Viewhelper

There are four view helpers:

- timer:isActive - works similar to `f:if`, checking if a time is in the active
  range of a periodic timer.
- timer:flexToArray - When converting a Flexform definition to an array, the
  array contains many superfluous
  intermediate levels. These levels can be removed with the Viewhelper, so that
  the resulting array of the flexform
  array becomes flatter/simpler.
- timer:format.date - works like `f:format.date`, allowing the output of times
  for a specific time zone.
- timer:format.jewishDate - works similarly to `f:format.date`, outputting times
  for a specific time zone
  allowed and whereby the dates are transformed into the Jewish calendar.
  **Deprecated - Will be removed in version 12! _Use the new view
  helper `timer:format.calendarDate`_**
- timer:format.calendarDate - works more comprehensively than `f:format.date`
  because in addition to taking into account the time zone, it also allows you
  to choose from the various calendars supported by PHP
  allowed and also allowed three instead of two date formatting variants.
  Besides being defined according to the strftime formatting rules and the
  dateTimeInterface::format formatting rules, the ICU formatting language can
  also be used.
  A well-known shortcoming is that the conversion from the Chinese lunar
  calendar to the Gregorian (western) solar calendar has an error that is to be
  found in PHP. The timer makes the view helper ``timer:format.jewishDate``
  superfluous.

#### timer:format.calendarDate - Attributes

- **flagformat** determines which formatting rules should be used: 0
  = [PHP-DateTime](https://www.php.net/manual/en/datetime.format.php),
  1: [ICU-Datetime- formatting](https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
  or 2 = [PHP-strftime](https://www.php.net/manual/en/function.strftime .php).
- **format** defines the format of the date output.
- **base** is important for relative dates like 'now', '+4 days' or similar.
- **timezone** defines for which time zone a date should be output. You can get
  a list of allowed time zone names using the PHP
  function `timezone_abbreviations_list()`. But you can also find
  a [list by continents](https://www.php.net/manual/en/timezones.php) in the PHP
  documentation.
- **date** allows a date from the Gregorian (western) calendar to be specified
  if no specification is made for `datestring`. You can implicitly define the
  value for `date` by enclosing the value with the viewhelper tags. If you don't
  specify anything, if `datestring` is empty or missing, the current date will
  be used automatically.
- **datestring** allows specifying a date from a non-Gregorian calendar in the
  format ``year/month/day hour/minute/second``, where the year must be four
  digits and all other values two digits. The hour can be any value from 0 to
  23. Values between 1 and 13 are acceptable for the months.
- **calendarsource** defines the underlying calendar for the date
  in `datestring`. PHP allows the following values: 0:'buddhist', 1:'chinese',
  2:'coptic', 3:'dangi', 4:'default', 5:'ethiopic', 6:'ethiopic-amete-alem' ,
  8:'gregorian', 9:'hebrew', 10:'indian', 11:'islamic', 12:'islamic-civil', 13:'
  islamic-rgsa', 14:'islamic-tbla', 15 :'islamic-umalqura', 16: 'japanese',
  17: 'persian', 18: 'roc'. In addition 19., 'julian' is also allowed for the
  Julian calendar.
- **calendartarget** defines the calendar for which the date should be output.
  PHP allows the following values: 0:'buddhist', 1:'chinese', 2:'coptic', 3:'
  dangi', 4:'default', 5:'ethiopic', 6:'ethiopic-amete-alem' , 8:'gregorian',
  9:'hebrew', 10:'indian', 11:'islamic', 12:'islamic-civil', 13:'islamic-rgsa',
  14:'islamic-tbla', 15 :'islamic-umalqura', 16: 'japanese', 17: 'persian',
  18: 'roc'. In addition 19., 'julian' is also allowed for the Julian calendar.
- **locale** determines the regional localization and consists of the two-letter
  language abbreviation (de, en, fr, es, ...) and separated by an underscore
  from the abbreviation for the nation (DE, GB, US, AT, Switzerland, France,
  ...). The value in __locale__ could look like this: `de_DE`, `en_GB`
  or `es_US`.
### Data Processors

Since the results of the data processors are cached, the user must determine
what a reasonable caching period is and
define this accordingly.

In principle, an example for the application of the same should be found as a
comment in the source code of the respective DataProcessors.
For the friends of TypoScript programming it should be said that the parameters
are read in via the stdWrap method.
The recursive use of Typoscript to dynamize the setup is therefore possible;
even if it is expressly not recommended here.

#### RangeListQueryProcessor

The processor creates a list of dates for the records with periodic timers from a table. The data processor works
similar to the `DbQueryProcessor`.

##### _example in typoscript_
```
tt_content.timer_timersimul >
tt_content.timer_timersimul < lib.contentElement
tt_content.timer_timersimul {

    templateName = timersimul

    dataProcessing.10 = Porthd\Timer\DataProcessing\RangeListQueryProcessor
    dataProcessing.10 {
        table = tx_timer_domain_model_event

        # get the date, which are defined on the pages, declared in the field `pages`
        pidInList.stdWrap.cObject = TEXT
        pidInList.stdWrap.cObject.field = pages
        recursive = 1

        # sort in reverse order
        # reverse = false

        # name of output object
        as = examplelist
    }
}

```
See also example in example contentelement ``timersimul``

##### _Parameters for the data processor `RangeListQueryProcessor`_
Due to the repetition of periods, a data record can be listed several times. Therefore, a start time and an end time must always be defined.

| Parameters | Default                                                                                                                              | description
|----------------|--------------------------------------------------------------------------------------------------------------------------------------|--------------
| | **_Records_**                                                                                                                        |
| if | true                                                                                                                                 | If the value or the typescript expression evaluates to false, the data processor is not executed.
| tables | tx_timer_domain_model_event                                                                                                          | This table is used to search for all available records with timer information.
| pidInList |                                                                                                                                      | Comma-separated list of numeric IDs for pages that may contain records for determining the list of timer events.
| as | records                                                                                                                              | Name of the object that contains the individual events and is transferred to the Fluid template. Look at `&lt;f:debug>{records}</f:debug>` for the exact structure.
| | **_Start and General_**                                                                                                              |
| datetimeFormat | Y-m-d H:i:s                                                                                                                          | Defines the format in which the date is given. The characters defined in PHP apply (see [List](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart | &lt;now&gt;                                                                                                                          | Defines the point in time at which the list should start. If `reverse = false` it is the earliest time, and if `reverse = true` it is the latest time.
| time zone | &lt;defined in PHP system&gt;                                                                                                        | Defines the time zone to be used with the date values.
| reverse | false                                                                                                                                | Defines whether the list of active areas is sorted in descending or ascending order. With `reverse = true` the end of the active areas is decisive; In the default case `reverse = true` it is the beginning of the active time.
| | **_Limit of the period_**                                                                                                            |
| maxCount | 25                                                                                                                                   | Limits the list to the maximum number of list items
| maxLate | &lt;seven days relative to the start date&gt;                                                                                        | Delimits the list via a stop date that can never be reached.
| maxGap | P7D                                                                                                                                  | Limits the list by calculating the corresponding stop time from the start time. The PHP notation for time intervals is to be used to specify the time difference (see [Overview](https://www.php.net/manual/en/class.dateinterval.php)).
| | **_Special_**                                                                                                                        |
| userRangeCompare | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` or `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Only the date values are used to determine the order. The user could also consider other sorting criteria. For example, one might want a list sorted first by start date and then by duration of active areas if the start date is the same.

#### SortListQueryProcessor

The `sys_file_reference` table does not support the `starttime` and `endtime` fields. In order to still achieve
time-varying images, the media obtained by the data processor can be converted into have a list sorted by periodicity
transferred and converted and used accordingly in the template.


```
        dataProcessing {
            ...
            20 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
            20 {
                references.fieldName = media
                references.table = pages
                as = myfiles
            }

            30 = Porthd\Timer\DataProcessing\SortListQueryProcessor
            30 {
                fieldName = myfiles
                # length of the sorted list. Perhaps with identical images at different positions
                hard break = 25
                as = mysortedfiles
            }
            ...
        }

```

Note that FLUIDTEMPLATE is cached. That's why:

```
    stdWrap {
        cache {
            key = backendlayout_{page:uid}_{siteLanguage:languageId}
            key.insertData = 1
            lifetime = 3600
        }
    }
```

##### _Parameters for the data processor `SortListQueryProcessor`_
Due to the repetition of periods, a data record can be listed several times. Therefore, a start time and an end time must always be defined.

In contrast to the `RangeListQueryProcessor`, the `SortListQueryProcessor` uses data generated by a previous or parent data processor process.
The parameters `table` plus `pidInList` are therefore omitted and the parameter `fieldName` is added.

| Parameters | Default                                                                                                                              | description
|-------------------------------|--------------------------------------------------------------------------------------------------------------------------------------|--------------
| | **_Records_**                                                                                                                        |
| if | true                                                                                                                                 | If the value or the typescript expression evaluates to false, the data processor is not executed.
| fieldName | myrecords                                                                                                                            | This table is used to search for all available records with timer information.
| as | sortedrecords                                                                                                                        | Name of the object that contains the individual events and is transferred to the Fluid template. Look at `&lt;f:debug>{sortedrecords}</f:debug>` for the exact structure.
| | **_Start and General_**                                                                                                              |
| datetimeFormat | Y-m-d H:i:s                                                                                                                          | Defines the format in which the date is given. The characters defined in PHP apply (see [List](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart | &lt;now&gt;                                                                                                                          | Defines the point in time at which the list should start. If `reverse = false` it is the earliest time, and if `reverse = true` it is the latest time.
| time zone | &lt;defined in PHP system&gt;                                                                                                        | Defines the time zone to be used with the date values.
| reverse | false                                                                                                                                | Defines whether the list of active areas is sorted in descending or ascending order. With `reverse = true` the end of the active areas is decisive; In the default case `reverse = true` it is the beginning of the active time.
| | **_Limit of the period_**                                                                                                            |
| maxCount | 25                                                                                                                                   | Limits the list to the maximum number of list items
| maxLate | &lt;seven days relative to the start date&gt;                                                                                        | Delimits the list via a stop date that can never be reached.
| maxGap | P7D                                                                                                                                  | Limits the list by calculating the corresponding stop time from the start time. The PHP notation for time intervals is to be used to specify the time difference (see [Overview](https://www.php.net/manual/en/class.dateinterval.php)).
| | **_Special_**                                                                                                                        |
| userRangeCompare | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` or `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Only the date values are used to determine the order. The user could also consider other sorting criteria. For example, one might want a list sorted first by start date and then by duration of active areas if the start date is the same.

#### FlexToArrayProcessor

The `FlexToArrayProcessor` allows the reading of `flex` fields and converts them into simple arrays.
In this way, the calendar-specific resources could simply be reloaded for the content element `periodlist`.

```
        30 = Porthd\Timer\DataProcessing\FlexToArrayProcessor
        30 {
            # regular if syntax
            #if.isTrue.field = record

            # field with flexform array
            # default is `tx_timer_timer`
            field = tx_timer_timer

            # field with selector for flexform array
            # default is 'tx_timer_selector'
            # selectorField = tx_timer_selector

            # A definition of flattenkeys will override the default definition.
            # the attributes `timer` and `general` are used as sheet-names in my customTimer-flexforms
            # The following definition is the default: `data,general,timer,sDEF,lDEF,vDEF`
            flattenkeys = data,general,timer,sDEF,lDEF,vDEF

            # output variable with the resulting list
            as = flexlist

```

#### MappingProcessor
The data processor `MappingProcessor` allows arrays to be mapped into new arrays or into a JSON string.
In this way, the data can easily be made available to the JavaScript using HTML attributes.
The data processor knows simple generic functions, for example to assign unique IDs to events.
It also allows the mapping of field contents and the creation of new fields with constant data.

```

        20 = Porthd\Timer\DataProcessing\MappingProcessor
        20 {

            # regular if syntax
            #if.isTrue.field = record

            # Name of the field containing an array generated by a previous data processor
            inputfield = periodlist

            # Each field must be part of the period list
            # Each entry must be formal
            generic {
                # Define an index, e.g. `event1holiday` in the `id` field
                'id' {
                    pretext = event
                    post text = holiday
                    type = index
                }
                # Define a constant, e.g. `cal1` in the `calendarId` field
                calendarID {
                    pretext = cal1
                    post text =
                    type = constant
                }
            }

            mapping {
                # sourceFieldName in period list (see input field) => targetFieldName
                # The assignment is case-sensitive.
                title = title
                startJson = date
                diffDaysDatetime = days
            }

            # Output format has the values ​​`array`,`json`
            # If the output format is unknown, json is the default
            outputFormat = json

            # Output variable with the resulting list
            # Default is `periodlist`
            asString = periodListJson

        }

```

#### PeriodlistProcessor
The DataProcessor `PeriodlistProcessor` allows the reading of the appointment list, which is stored in the PeriodlistTimer in the Yaml file
is defined. In addition to the actual fields, the data processor also generates the corresponding DatTime objects for the start and end times of the appointments and calculates the number of days (24 hours = 1 day) between the appointments.

```
        10 = Porthd\Timer\DataProcessing\PeriodlistProcessor
        10 {

            # regular if syntax
            #if.isTrue.field = record

            # Time limit on selecting dates
            #limit {
            # # lower time; stdWrap is supported
            # lower = TEXT
            # lower {
            # data = date:U
            # strftime = %Y-%m-%d %H:%M:%S
            #
            # }
            # ## upper time limit; stdWrap is supported
            # #upper = TEXT
            # #upper {
            # # data = date:U
            # # strftime = %Y-%m-%d %H:%M:%S#
            # #}
            #}

            # Hard-wired help mechanism to convert the data fields with the start and end time into a desired format and to save them in an additional field
            dateToString {
                # startJson is the targetfieldName in the following dataprocessor mappingProcessor
                startJson {
                    # use the format parameter defined in https://www.php.net/manual/en/datetime.format.php
                    # escaping named parameters with the backslash in example \T
                    format = Y-m-d
                    # allowed are only `diffDaysDatetime`, `startDatetime` and `endDatetime`, because these are automatically created datetime-Object for the list
                    # These fields are datetime-object and they are generated from the estimated fields `start`and `stop` by this dataprocessor
                    source = startDatetime
                }
           # endJson {
           # format = Y-m-d
           # source = stopDatetime
           # }
            }

            # Limit the list to a maximum number of elements
            # If not specified, the list will be limited to 25 items
             maxCount = 100

            # Name of the output variable passed to the Fluid template.
            # If not specified, default is `periodlist`.
            as = period list

            ## If `flagStart = 1` or `flagStart = true`, the field `start` from the reference list is used to compare the upper and lower limit. This is the default state.
            ## If `flagStart = 0` or `flagStart = false`, the field `stop` from the reference list is used to compare the upper and lower limit.
            #flagStart = false
        }

```
