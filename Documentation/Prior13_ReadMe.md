# Extension Timer - version 12.x

## Preliminary remark

The basis for this documentation is the German variant `de.ReadMe.md`. The
English version was made using
Translated by `google.translate.de`. A presentation is attached to the
documentation, which I made in 2022 for the
TYPO3 Barcamp in Kamp-Lintfort

## Motivation

TYPO3 provides the fields `starttime` and `endtime` in its standard tables.
About the fields can
Fade in and out of pages at specific times. These fields are also taken into
account during caching. There are
but no possibility to show and hide pages, content elements and/or images
periodically at certain times.

### User Stories

#### The interactive pub voucher.

A restaurant owner wants to give his guests a 50% discount during happy hour.
Customers receive this as
QR code on their cell phone when they enter the motto of the day hanging above
the counter and a Like comment in the interactive
form. In the content element, the current motto is automatically checked, of
course.

#### The annual company foundation week

A company celebrates its birthday by looking back at the successes of the past
year. These will be in
Everyday maintained by the employees. The TYPO3 system takes care of the
activation of this special page. The
Events calendar A small concert promoter would like to organize periodic events
such as poetry slams,
offer readings or open stages mixed with special concert dates in one list.

#### Reaction Column

One party would like to show content from its home page that is similar to that
of its competitors on their home pages. His
The website should automatically react to changes made by competitors. The
articles should still have a certain time
disappear again.
(Actually, it has nothing to do with time. But the problem could be solved with
a timer...)

#### Marketing of virtual concerts

A concert agency in December 2021 would like to organize a virtual concert in
Berlin to be received throughout Europe
should be. Since summer time ends in 2021, the organizer expects different time
zones in Europe. The website
should show the respective local time for the users.

## Idea

A basic idea of the timer extension is to split the task into two subtasks: the
task of the
Time management and the start time control task. Via a task in the scheduler (
cron job), the page
be updated regularly. The integration of information within templates can be
controlled via Viewhelper
where the developer then has to think about the caching of the frontend. The
data processors should easily help to compile simple lists that can be displayed
in the frontend.
The results in the dataprocessors will be cached und some of the dataprocessors
will clear the caches of its corrosponding page.
The information from the
Flexform fields can be processed in the templates with view helpers.

## Installation of the extension

You can install in one of the classic ways.

- manually
- with the extension manager in the TYPO3 admin tools
- with the composer ``composer require porthd/timer``

Check if the database schema has been updated. The typoscript of the extension
is to be included. Depending on
A claim for use is to activate a scheduler task or to call up a data processor
in your own typescript.

## Application Aspects

### Periodic content or pages

For the periodic content or page, the scheduler task of the extension
set up to update the `starttime` and `endtime` fields regularly.
The task may update the `starttime` and `endtime` fields for those items that
have values defined in the `tx_timer_selector` field and the flag in
the `tx_timer_scheduler` field
the scheduler evaluation was set to active and whose `endtime` is in the past or
is not defined.

~~#### Content element `periodlist` for simple appointment lists~~
is removed in verion 13
~~The content element `periodlist` has a similar structure to the content
element `textmedia`.
It also allows the output of simple appointment lists. The data for this content
element will be
saved as a flexform in the `pi_flexform` field.~~

~~In addition to the parameters for the periodic data, you can also specify
paths
to JavaScript and CSS.
In this way you can integrate your own calendar system. Exemplary was
Here is the lean JavaScript framework by Jack
Ducasse ([https://github.com/jackducasse/caleandar](https://github.com/jackducasse/caleandar))
used. In principle, you can of course also use any other calendar framework.~~

~~In order to be able to use the plugin, you must integrate the TypoScript for
the
content element starting from version 12.2. (The previously necessary
integration via extension constants has been removed.)~~

#### Content element `holidaycalendar` for holiday

Remark: In order to be able to use the plugin, you must integrate the TypoScript
for the content element starting from version 12.2. (The previously necessary
integration via extension constants has been removed.)

Most do not want to maintain appointments in a confusing TYPO3 backend because
you quickly lose track there.
A clear list of appointments
can be reached in an Excel spreadsheet. Most editors are also able to
save the data in their Excel spreadsheet as a CSV file.

The content element `holidaycalendar` is a further development of the content
element `periodlist`,
The focus here is on the editor workflow. It also allows the output of simple
public holiday lists
or other appointment lists that are defined via easy-to-create `excel` lists.
The data for this content item will be
saved as a flexform in the `pi_flexform` field.

In addition to the parameters for the periodic data, you can also specify paths
to JavaScript and CSS.
The powerful calendar
framework [ToastUI-calendar](https://github.com/nhn/tui.calendar/) was preset
for the out-of-the-box example.
It is a powerful framework that unfortunately comes with somewhat spartan
documentation.
(Maybe I just struggled with the integration because I haven't had much
experience with JavaScript so far.)
Several JavaScripts had to be integrated for the initial call of the content
element, which is why the `timer:forCommaList` view helper was programmed.
The enumeration of the various JavaScript files can be a comma-separated list
done in a field. The JavaScript and StyleSheet fields with the name
part `custom` are individual for each content element and are also loaded
multiple times if the content element is used multiple times.
The files from the other two fields are only loaded once for a page, no matter
how many times the content element is used on the page.

##### 'alias'-definition: only an experiment in the dataprocessor for holydays

The data can be imported via a CSV file or a Yaml file. Since most editors
presumably with the
are overwhelmed when creating a Yaml file, the import as a CSV file has also
been made possible.
The editor can easily generate a CSV file with `excel` (Microsoft Office)
or `calc` (LibriOffice/ Open Office) by saving his table as a CSV file.
The file is imported into the content element either by specifying the path (
field: `holidayFilePath`) or alternatively via the TYPO3 file system (
field: `holidayFalRelation`).
The editor can also import alias definitions via the fields `aliasFilePath`
and `aliasFalRelation`.
In the alias definitions one often finds reused definition components,
which automatically expand the data array of an entry if the corresponding name
of the alias definition can be found in the `add.alias` field. (**Note: This
alias feature has not yet been tested.**)

The powerful calendar
framework [ToastUI-calendar](https://github.com/nhn/tui.calendar/) is preset.

As preset dates for public holidays in different countries (I do not guarantee
the correctness and completeness of the dates.)
a CSV file is imported, which was generated with `calc` from
the [File (ExcelLikeListForHolidays.ods)](ExcelLikeListForHolidays.ods).
The file should also read and edit in `excel`.
You can use the dot notation in the title to control the structure of the array
in PHP afterwards.

```
Title to CSV:
    title
Value in first row of CSV:
    'my value'

>>> will be transformed to >>>

php:
  0=> [
      'title' => 'my value',
  ]

================================================== ===
Title to CSV:
    title.COMMA
Value in first row of CSV:
    'my value, my stuff, my idea'

>>> will be transformed to >>>

php:
  0 => [
      'title' => [
         'my value',
         'mystuff',
         'my idea',
      ],
  ]

================================================== ===
Title to CSV:
    title.subtitle.label
Value in first row of CSV:
    'my value, my stuff, my idea'
>>> will be transformed to >>>
php:
  0 => [
      'title' => [
         'subtitle' =>[
             'label' => 'my value,my stuff,my idea',
         ],
      ],
  ]

```

(For me it was amazing how many different variants and calculation rules there
are for public holidays. )

#### Remarks

##### _Note 1_

In order to enable the most flexible integration of appointment lists, there are
two input fields `yamlPeriodFilePath` and `yamlPeriodFalRelation`.
The field `yamlPeriodFilePath` has more the integrator in mind and allows four
variants,
to specify the location of the YAML file:

1. Absolute path specification if necessary. also with relative path
2. Path specification with the prefix `EXT:`
3. Simple URL starting with `http://` or with `https://`
4. URL with server password in the format `username:password:url`, where the
   actual url starts with url starting with `http://` or with `https://`. The
   YAML instruction `import` is not supported for the URL information.

The field `yamlPeriodFalRelation` has more the editor in mind and allows the
integration of the YAML file via the TYPO3 backend.
Here the editor also has the option of including several files, which the timer
treats as one large list.

##### _Note 2_

Various data can be stored in the `data` attribute so that it is structured
using a suitable partial or template
Special information such as admission price, advance sales prices or similar can
be transferred via file.
This form works well when it comes to automating data about the format of a YAML
file from other sources
to accept. This saves entering the data in the backend.

#### Representation of appointments in the calendar

Two path fields for JavaScript and for style sheets were added to the Flexform.
In this way it is possible to display the appointments in calendar form. The
default settings are set so
that the school holidays for Lower Saxony and Bremen from the year 2022 are
shown in a calendar.

#### Data processors for this timer

Six data processors were defined so that the data can be read in or converted.

The `FlexToArrayProcessor` allows reading Flexform fields and converting them
into simple arrays.
This way you can dynamically load the JavaAScript and style sheet files from the
content element.

The DataProcessor `PeriodlistProcessor` allows the reading of the appointment
list, which is stored in the PeriodlistTimer in the Yaml file
is defined. In addition to the actual fields, the data processor generates the
start and end times
of the appointments also the corresponding DatTime objects and calculates the
number of days (24 hours = 1 day) between the appointments.

The DataProcessor 'RangeListQueryProcessor' allows appointment lists to be read
out,
whereby the data processor also takes prohibited lists into account.
This can be used, for example, to create series of appointments of the type
“Every Tuesday,
except during school holidays”. More detailed information can be found below.

The DataProcessor 'HolidaycalendarProcessor' allows holiday dates to be read in
via CSV file.
If you absolutely have to, you can also use a YAML file for this.
More detailed information on the definition of public holidays can be found
below.

The DataProcessor 'SortListQueryProcessor' allows sorting object lists
that contain timer definitions. For example, you can control the output of
photos
from a gallery or you can controll the time-controlled output of images from a
collection.
More detailed information can be found below.

~~The third data processor `MappingProcessor` is required to transfer the
appointment data to the Fluid template as a JSON string.
In this way, the data can easily be made available to the calendar framework via
an HTML attribute.~~
`MappingProcessor` is deprecated and will be removed in version 12 because it
doesn't support multilevel arrays.

~~In the future, `BetterMappingProcessor` will be used as the third data
processor
for the mapping.
It can help to pass an appropriate JSON string to the Fluid template.
The data can easily be made available to the TuiCalendar framework or another
calendar framework via an HTML attribute.~~
The data processor `BetterMappingProcessor` was an interim solution.

In the future, `PhpMappingProcessor` will be used as a third data processor for
mapping.
In contrast to `BetterMappingProcessor`, this is easier to configure and follows
a target structure-oriented approach.
It can help to pass a suitable JSON string to the Fluid template.
This means that the data can easily be made available to the TuiCalendar
framework or
another calendar framework via an HTML attribute.

### Content element `timersimul` as an example

The content element `timersimul` shows an example of how the view helpers and
the data processors are used. In
Production environments should hide them for editors. It will be removed when
the extension reaches `beta` status
reached.

In order to be able to use the plugin, you must integrate the TypoScript for the
content element starting from version 12.2. (The previously necessary
integration via extension constants has been removed.)

### Use of the periodic timer (Customtimer)

The extension currently comes with several periodic timers. Two of the timers
are not yet fully developed. Under
other reasons why the status of the extension was on
`experimental`.

#### Customtimer - General

You can enable the timers in the constants of the extension.

Own timers must derive from the
interface ``Porthd\Timer\Interfaces\TimerInterface`` and be able to
in ``ext_localconf.php`` to be added to the timer extension as follows:

````
             \Porthd\Timer\Utilities\ConfigurationUtility::mergeCustomTimer(
                 [\Vendor\YourNamespaceTimer\YourTimer::class, ]
             );
````

### Predefined Timer - Overview

* (not realized 12.2.1) ~~CalendarDateRelTimer - (In preparation) Most
  religious, historical, political, economic or other holidays are fixed on a
  date in a calendar. The powerful want to avoid mentally overwhelming the
  common people (so that every Dölmer also appreciates the festival at the right
  time). In the course of human history, many different calendar systems have
  been developed and there are many regionally different important festivals.
  The timer wants to take this variability into account by allowing the
  consideration of different calendar systems.
  (Example 5760 minutes (=2 days) after Ramadan (1.9.; Islamic calendar) for 720
  minutes (=6 hours)). At the same time, this timer can also be used to output
  lists of appointments. The workflow is supported to define the appointment
  lists - i.e. public holiday list as well as timer definitions in an Excel
  table and to provide the timer with the list as a CSV file.~~ _(use
  HolidayTimer instead)_
* DailyTimer - Daily recurring active times for a few minutes
  (daily from 10:00 a.m. for 120 minutes)
* DatePeriodTimer - Active times recurring periodically for a few minutes
  relative to a start time. To ponder
  they that due to the return of the time to the UTC zone during the
  calculation, daylight saving time at periodicities
  on an hourly basis can lead to unexpected results.
  (whole day every year for birthdays, every week for 120 minutes until 12:00
  from May 13, 1970, ..)
* DefaultTimer - Default timer/null element
* EasterRelTimer - Determines the active period relative to important German
  public holidays (New Year's Eve, New Year's Eve, World Towel Day, Day of
  Stupidity) and the most important Christian public holidays (first Advent,
  Christmas, Shrove Monday, Good Friday, Easter, Ascension Day, Pentecost)
  (2nd Advent from 12:00 p.m. to 2:00 p.m., Rose Monday from 8:00 p.m. to 6:00
  a.m. of the following day)
* HolidayTimer (in progress 2023-09-17) - List for defining holidays. The
  holidays are recorded in a CSV file and can also be defined via a YAML file.
  For explanations of the CSV file, see below.
  The Yaml file blocks all data that is relevant for the holiday calendar under
  the expression `holidayTimerList`. A separate array element is created in the
  YAML block for each holiday. The names of the attributes are the same as those
  used as names for the columns in the CSV.
* JewishHolidayTimer (in progress 2022-12-28) - periods starting relative to one
  of the Jewish holidays related to the Jewish calendar
  (IMHO: I was thinking for a long time about adding this timer because I
  consider parts of Judaism to be morally problematic. It is a practice that is
  lived and promised by orthodox
  Jews (https://www.zentralratderjuden.de/fileadmin/user_upload/pdfs
  /Important_Documents/ZdJ_tb2019_web.pdf page 21) that their male offspring's
  penises are circumcised for religious reasons I wonder what the value of a
  religion is that prides itself on being able to rape weak, helpless babies by
  using its Sacrificing the foreskin of the penis under its screams for the god.
  What distinguishes this religious delusion from the chosen people in its
  fundamental way of thinking from the Aryan delusion that some perverts still
  cling to today? From a humanistic point of view, the freedom of parents to
  educate ends for me at the time when they for their own religious beliefs want
  to do violence to their children Every baby has the right to express their own
  religion or beliefs freely to choose. Parents can and should influence the
  child's decision-making through speech and through their own life as an
  example; because no one knows for sure the only true truth. I think: Any rape
  for religious reasons is a perverse crime - be it by the Catholics or the Jews
  or any other sect. dr Dieter Porth)
  **!!!Warning: I cannot guarantee the correctness of the Jewish holidays, as I
  am not familiar with them at all. Please check for correctness before use and
  write a better timer yourself if necessary.**
  _During testing, I noticed that the calculated date for Yom Kippur did not
  take into account that the holiday begins after sunset the previous day. I
  didn't feel like making a correction at this point. The Jewish calendar is not
  that important for me. For the same reason, the tests were only carried out
  using Yom Kippur as an example._
  **Recommendation** _Use the new more general timer `holidayTimer` (which can
  process lists of various holidays)._** instead
* MoonphaseRelTimer - Periods starting relative to a moon phase for a specified
  time period
* MoonriseRelTimer - Periods relative to moonrise or moonset for a specified
  time period
* PeriodListTimer - Reads active period data from a yaml file. Helpful for
  example
  for holiday lists or artist tour schedules
* RangeListTimer - Reads and merges periodic list from yaml files or table ``
  merge into new active areas when overlapped. You can also define a list of
  prohibited areas,
  which can reduce such overlaps. (Example: every Tuesday and Thursday from
  12-2pm [active timers] except during school holidays and on
  public holidays [forbidden timer])
* SunriseRelTimer - periods relative to sunrise and sunset
* WeekdayInMonthTimer - Periods on specific days of the week within a month from
  specific times with specific
  Length of time
  (Example: every second Friday of the month in the two hours before 19:00)
* WeekdaylyTimer - Weekly recurrence on a specific day or days of the week. (
  Example: Every Monday or
  Thursday)

#### Notes on the _HolidayTimer_ workflow

##### Challenge

The assessment of which public holidays an editor can/should use will certainly
differ from website to website. Maybe you only want to use Christian, Jewish,
Islamic or other holidays.
Furthermore, every editor will probably wish that the selection of public
holidays is limited to the necessary ones and that unwanted public holidays are
not available to the editor at all.
Furthermore, the developers will have very different wishes as to which
information should be saved in addition to the public holidays.
At the same time, you want to be able to manage the list of public holidays as
clearly as possible.

##### Workflow for individual lists

You manage the list of public holidays in a spreadsheet such as `Excel`
or `calc` and save the data in a CSV file.
The CSV file is uploaded to the server via FTP and the path to the CSV file is
specified in the settings for the extension configurations.
After deleting the cache you have the new list available in the
timer `HolidayTimer`.

##### supported holiday calculations (currently not working and tested 2023-02-25)

Like any man-made system, the calculation is simple in principle; but in detail
most highly complicated, because the clever want to distinguish themselves in
their stupidity from the stupid and because some people want the power to tell
others what the truth is. So it might be. The following calculation schemes are
currently supported, which must be specified in the CSV file in the `arg.type`:

- _fixed_: A defined day (`arg.day`) and a defined month (`arg.month`) are
  specified for a defined calendar (`arg.calendar`). The day and month must be
  entered as numbers. The leap month in the Jewish calendar is usually skipped
  and if there is a year with a leap month, the target month is automatically
  increased by one internally because the IntlFormatter simply continues to
  count the month, since in Jewish leap years the IntlDateFormatter simply uses
  13 months for the year. If you want to access the first Adar (6) instead of
  the second Adar (7) in leap years, you must enter a value greater than '1'
  in `arg.status` when using the Jewish calendar.
- _fixedshifting_: For a defined calendar (`arg.calendar`), a defined
  day (`arg.day`)
  and a defined month (`arg.month`). In some countries it is customary to
  provide a substitute holiday for the holiday
  if the holiday itself falls, for example, on the weekend or on a specific day
  of the week.
  In the `arg.statuscount` column there is a comma-separated list of seven whole
  numbers for this type. The numbers indicate by how many days
  a public holiday is postponed if, for example, the public holiday falls on a
  Wednesday. In the list represents the
  first number on Monday and last number on Sunday. In the example '
  0,1,2,0,3,2,1' a public holiday would be shifted by three days (5th entry) if
  it falls on a Friday (5th day of the week).
  Technically, the mechanics work in the same way as `_fixed_`.
- _fixedrelated_ or better _xmasrelated_: The fourth Advent example shows that
  there are holidays that are celebrated on certain days of the week relative to
  a fixed date (the first day of Christmas in Advent).
  With the numbers 1 = Monday to 7 = Sunday you define the day of the week
  in `arg.status` that must precede the fixed date of the target holiday.
  In `arg.statusCount` you then define how many weeks before the day must take
  place. For example, the first Advent with `-4` is the fourth Sunday before
  Christmas.
  In order to be able to define holiday specials such as the day of repentance
  and prayer - the Wednesday before the fifth Sunday before Christmas - you can
  specify in `arg.secDayCount` how many days the actual holiday is away from the
  calculated day.
- _season_: This defines the four seasons, which are defined with 1=spring,
  2=summer, 3=autumn and 4=winter via the `arg.status` attribute.
- _seasonshifting_: Like _`season`_, this defines the four seasons, which are
  defined with 1=spring, 2=summer, 3=autumn and 4=winter via the `arg.status`
  attribute. As with _fixedshifting_, the respective day of the week is also
  taken into account in the parameter `arg.statusCount` in order to enable a
  corresponding shift of the public holidays for corresponding days of the week.
- _matariki_: Matariki is only intended for the Matariki holiday in New Zealand.
  I haven't found an algorithm that can calculate this holiday in PHP. There is
  only one list for the next 30 years that I found from a (!) source on the
  Internet. This calculation routine has no parameters.
- _easterly_: This keyword only applies to a form of calculation limited to the
  Gregorian or Julian calendar (`arg.calendar`). It determines a holiday
  relative to Easter Sunday, which can be calculated using Gauss's Easter
  formula or the PHP function. In `arg.statusCount` the positive or negative
  number of days relative to Easter Sunday is specified. If the number is
  missing or there is a '0' there, then of course it means Easter Sunday itself.
- _weekdayly_: Here you calculate the ith (`arg.statusCount`)
  weekday (`arg.status`) within a month (`arg.month`) for a selected
  calendar (`arg.calendar`). The day of the week is characterized by a number,
  where 1 stands for Monday and 7 for Sunday. If there is a negative number
  in `arg.statusCount`, then the position of the day of the week is determined
  relative to the end of the month. There is no check whether the date is still
  in the month in question. If a value is specified in `arg.secDayCount`, then
  the day is determined relative to the calculated day of the week. This is
  needed, for example, for Geneva Prayer Day in Switzerland, which is always
  celebrated on the Thursday after the first Sunday in September.
- _mooninmonth_: Some festivals have been transferred from one calendar system
  to another calendar system throughout history, as is the case with the
  festival of Vesakh.
  Today the festival is celebrated in some countries on the first full moon day
  in May and in other countries possibly on the second full moon day. In
  general, this means for a selected calendar (`arg.calendar`) that the month is
  specified as a number in the `arg.month` field. The calendar is defined
  in `arg.calendar`. The phase of the moon is defined in `arg.status` with the
  encoding 0/new_moon = new moon, 1/first_quarter = waxing crescent,
  2/full_moon = full moon and 3/last_quarter = waning (Islamic?) crescent. If
  a '1' is specified in `arg.statusCount` or if no information is specified, the
  first moon phase of the month is always used, even if there should be two
  identical moon phases in the month. Otherwise, the second moon phase is used
  if it exists in the same month.

The methods are defined in such a way that a date is always determined. The
calculation procedures always return a Gregorian date.

##### Preliminary explanations of the internal structure of the CSV list

You manage the list of public holidays in a spreadsheet program like `Excel`
from MS Office or like `calc` from Libri Office.
A [calc example](ExcelLikeListForHolidays.ods) and
also [the CSV file saved from it](ExcelLikeListForHolidays.csv) can be found
here in the documentation.
The selected title line (first line) is important in the example, because the
notation of the titles defines the structure of an associative array with
several levels.
The expression 'add.rank' in the title leads to the following array if the
value `5` is assigned to the column in the corresponding row in the CSV:

```
$list = [
     // ...
     [
         'arg' => [
             'rank' => 5,
         ],
     ],
     // ...
];
```

The subexpression COMMA has a special meaning because it always interprets what
is specified in the field of this column as a comma-separated list, which is
automatically converted into an array with trimmed values.
The expression 'add.locale.COMMA' in the title leads to the following array if
the value `de_DE, de_CH , de_AT ` is assigned to the column in the corresponding
line in the CSV:

```
$list = [
     // ...
     [
         'arg' => [
             'locale' => [
                 'de_DE',
                 'de_CH',
                 'de_AT',
             ],
         ],
     ],
     // ...
];
```

The structure allows a compact CSV list to be converted into a more readable
Yaml structure.
Alternatively, you can therefore also use a file in Yaml format to have the
public holidays available.
You can add additional columns for your own information.
Efforts are being made to always make the information available in the frontend
as well.

##### Explanations of the internal structure of the CSV list

The structure is easiest to explain using the example of the YAML structure.
The explanations for the individual components are included as comments.

```
   -
# Auxiliary title for the overview in the Excel file. The value is not used in the program.
     title: 'Christmas Eve'
# Reference to the foreign language file in which the appointment is linguistically defined. If you only use one language
# you can also enter plain text here - for example the same as for `title`.
     eventtitle: 'LLL:EXT:timer/Resources/Private/Language/locallang_cal.xlf:calendarDate.christ.greg.christmasEve'
# The identifier should be unique in the list. In most cases, the abbreviation of the calendar should be like this
# plus the abbreviation of the holiday name result in a unique identifier. Is possible in the future
# intended for internal use.
     identifier: 'greg-christmasEve'
# `type` determines how the date is calculated. The uppercase and lowercase sensitive ones are currently defined
# Terms `fixed`, `easterly`, `weekdayinmonth`, `weekdayly`, `mooninmonth` and `leapmonth`.
# - `fixed`: Here, a defined day and month is determined annually for a specific calendar.
# - `easterly`: Here a feast day is determined annually according to the days relative to Easter Sunday. As a calendar
# Only the Gregorian and Julian calendars can be selected. All others lead to one
#   Error message. Most Christian holidays can be determined in this way.
# - `weekdayinmonth`: This is a specific day of the week for a specific month in a specific calendar
#   Are defined. This form of calculation is required, for example, for the "Day of Thanks, Repentance and Prayer" in Switzerland.
# - `weekdayly`: Here a certain day of the week is searched relative to a defined day and month. An example
# would be the fourth advent, which is the last Sunday before Christmas. This method also allows the calculation of
# a somewhat more complex case: the day of repentance and prayer. This takes place four days before the Sunday of the Dead,
# whereby Totensonntag is in turn defined as the 5th Sunday before Christmas, which in turn
# known always on 25.12. takes place.
# `mooninmonth`: Within a certain month for a defined calendar, the day with a
# determined moon position (full moon, new moon). (It is assumed that the creators of holidays always
# select only months that also have enough days for a full lunar cycle.)
# `leapmonth`: This method is limited to the Jewish calendar to calculate Purim, that
# can be in the leap month of the Jewish calendar. Any other calendar information will result in an error.
     type: 'fixed'
# The additional arguments for the respective holiday calculation method are defined here.
     bad:
       #important for: `fixed`, `weekdayinmonth`, `weekdayly`, `mooninmonth` and `leapmonth`.
       month: '12'
       #important for: `fixed`, `weekdayly`, and `leapmonth`.
       days: '24'
       #important for everyone: `fixed`, `easterly`, `weekdayinmonth`, `weekdayly`, `mooninmonth` and `leapmonth`.
       calendar: 'gregorian'
       #important for: `easterly`, `weekdayinmonth`, `weekdayly` and `mooninmonth`.
       state: ''
       #important for: `easterly`, `weekdayinmonth`, `weekdayly` and `mooninmonth`.
       statusCount: ''
       #important for: `weekdayly`.
       secDayCount: ''
# This is to characterize the holiday. The specification is optional and is currently not used in the program.
# I introduced the indication what the motivation for holidays is/could be. I used the categories
# `religion`,`culture`,`economic`, `politics` and `historical`. I found it striking that many holidays are religious
# are motivated. Holidays serve religious leaders as a rewarding ritual to uphold spiritual
# conditioning/manipulation of people?
# - `religion`: These festivals serve to bind believers to the institution of faith. As an opponent of religion
# One could say that the festivals serve to stabilize the conditioning of the believers.
# - `culture`: Here, certain rituals are maintained by a larger group of people without being obvious
# It is recognizable why the festival came about in the first place. Examples would be New Year's or
# also Mother's Day.
# - `economic`: The main driving force for the holiday is economic interest. A typical example is the
# Valentine's Day. Christmas as a consumer festival could perhaps also be counted among these days in Germany.
# - `politics`: On political holidays, you want certain political topics or issues to be permanently in
# keep the discussion. Examples of political holidays are May 1st (Labour Day),
# 3/8 (International Women's Day) or 4/16 (Stupid Day).
# - `historical`: These days always stand under the aspect of commemorating a historical event. A
# Example would be the Day of German Unity, which commemorates the signing of the treaties on the German
# to remember reunification. November 9, the day the Wall came down, was probably never considered a serious day
# Public holiday discussed because it would have made those in power and their supposed actions appear unimportant.
     tag: 'religion'
# In this block `add` any further attributes can be defined. For the calculation of public holidays
# this data is not necessary. But they can be looped through to the frontend.
     add:
# Here I tried to use keywords to narrow down the motivation area or the user groups.
       categories:
         - 'christian'
# The rank defines the importance of the holiday. The value reflects my naive assessment. It cannot be objectified.
       rank: '3'
# Here you could define in which regions and/or language zones certain holidays are important. Currently, my list is only exemplary and extremely incomplete.
       locale:
         - 'de_DE'
         - 'de_AT'
         - 'de_CH'
# Here you could define in which regions and/or language zones certain public holidays are non-working days.
       free locale:
         - 'de_DE'
         - 'de_AT'
         - 'de_CH'
# You don't want to write the same thing over and over again for every holiday. Additional information can be merged into the addlock via an alias. Warning: the alias can also overwrite definitions here.
       alias: ''
```

##### Important columns/column identifiers in the CSV

- _title_: This column designates the holiday and must always contain at least
  one character (no whitespace characters).
- _identifier_: This column designates a unique identifier for the holiday in
  the list. It should be an abbreviation of
  calendar used and an abbreviation for the holiday. It may be helpful to
  include the locale designation for the country in the identifier.
- _arg.timer_: this is a generic term under which the various parameters for the
  respective timers used for the `timer` extension are recorded.
- _arg_: captures the arguments for the different
  timer/types `fixed`, `fixedrelated`, `fixedshifting`, `weekdayly`, `easterly`
  or `mooninmonth`. The parameters are used to calculate public holidays.
- _arg.startYear_: The year describes from which year of the selected calendar
  the holiday is valid. It applies to all
  types (`fixed`, `fixedrelated`, `fixedshifting`, `fixedmultiyear`, `season`, `seasonshifting`, `weekdayly`, `easterly`
  or `mooninmonth`).
- _arg.endYear_: The year describes up to which year of the selected calendar
  including the holiday is/was valid. As above, it applies to all types.
- _arg.day_: The parameter describes a day of the month for a holiday
  calculation. The entry is used
  in `fixed`, `fixedrelated`, `fixedshifting`, `fixedmultiyear`, `weekdayly`.
- _arg.month_: The parameter describes the month that is important for the
  holiday calculation. The entry is used
  in `fixed`, `fixedrelated`, `fixedmultiyear`, `fixedshifting`, `weekdayly`
  or `mooninmonth`
- _arg.type_: Describes the type of calendar
  calculation. `fixed`, `fixedrelated`, `fixedshifting`, `fixedmultiyear`, `weekdayly`, `easterly`
  or `mooninmonth` are possible. All identifiers that the various timers provide
  as identifiers are also possible.
- _arg.calendar_: The parameter defines which calendar is used. Usually the
  calendar is `gregorian`. Also allowed
  are: `buddhist`, `chinese`, `coptic`, `dangi`, `ethiopic`, `ethiopic`, `gregorian`, `hebrew`, `indian`, `islamic`, `islamic`, `islamic` `, `
  islamic`, `islamic`, `julian`, `japanese`, `persian`, `
  roc`. The parameter applies to all types (`fixed`, `fixedshifting`, `
  weekdayly`, `easterly` or `mooninmonth`).
- _arg.status_: The entry
  in `fixedmultiyear`, `fixedrelated`, `season`, `weekdayly` or `mooninmonth` is
  used. With `weekdayly` or with `fixedrelated` it specifies the day of the week
  as a number (1 = Monday, ...7 = Sunday). With `mooninmonth` it indicates the
  phase of the moon (1 = waxing crescent, 2 = full moon, 3 = waning crescent,
  4 = new moon). At `season` it indicates the beginning of the astronomical
  season (1 = spring equinox, 2 = summer solstice, 3 autumn equinox, 4 = winter
  solstice). With `fixedmultiyear` a reference year is specified here.
- _arg.statusCount_: The entry is used
  in `fixedshifting`, `fixedrelated`, `seasonshifting`, `fixedmultiyear`, `weekdayly`, `mooninmonth`
  or `easterly`. With `fixedshifting`, the deviation from the existing day of
  the week is defined via a comma-separated list. `easterly` defines the
  distance to Easter Sunday. With `weekdayly` or with `fixedrelated` it is
  specified which (first, second, ..) weekday in the corresponding month or
  relative to the fixed date is meant. Negative numbers count from the end of
  the month or towards the past. `mooninmonth` defines whether the first or
  second moon phase of the month is meant.
- _arg.secDayCount_: The entry is only used in `weekdayly` or `fixedrelated`. It
  defines the distance in days relative to the selected day of the week. (
  Special definition for the Swiss or Federal Day of Thanks, Repentance and
  Prayer)

##### Different types of holiday calculation

- `fixed`: This type defines the holiday over a specific date in the calendar.
- `fixedrelated`: This type is best explained by Advent, which is the i.th
  Sunday before December 25th. is. The fixed date is defined here. The number of
  the day of the week (0=Sunday,..,6=Saturday) is entered in the `status`
  parameter.
  The number of days of the week is specified in `statusCount`, whereby a
  negative number means the i-th day of the week before the fixed day. If there
  is still a number in `arg.secDayCount`, then the date is determined which is
  the corresponding number of days away from the i-th weekday. (This is needed
  for the Day of Atonement and Prayer, for example.)
- `fixedshifting`: This type defines the holiday over a specific date in the
  calendar, whereby different days of the week can lead to deviations (
  substitution holiday).
- `fixedmultiyear`: This type defines the holiday on a specific date in the
  calendar, but the date is only celebrated every x years. In `arg.status` a
  year is specified in which the day was celebrated. `arg.statusCount` contains
  the value after how many years the day will be frozen again. For example, the
  change of presidency in Mexico, which takes place every six years, needs this
  variant, if I understood it correctly.
- `season`: Here the beginning of an astronomical season is determined (spring
  equinox, ...)
- `seasonshifting`: Here the beginning of an astronomical season is determined (
  spring equinox, ...), whereby there may be substituting deviations on certain
  days of the week as with `fixedshifting`.
- `weekdayly`: Here a certain day of the week in a certain month is declared a
  holiday.
- `easterly`: Here the holiday refers to the respective Easter Sunday. These
  holidays refer to either the Julian or the Gregorian calendar.
- `mooninmonth`: Here a certain moon phase is expected in a certain month.

##### Definition of parameters for the timer of the extension

The first entry in the sample file indicates how to define timers of the
function in the Excel file. Then you have to fill in the data that the timer
usually needs. (See Flexform Fields)
Enter the name of the timer in the `arg.type` column.

#### General parameters for all predefined timers

Some parameters are the same for all timers. Two parameters deal with the
handling of time zones. Two more parameters
determine the period in which the timer is valid at all. On a parameter to
control the scheduler was
waived. I can't think of a use case where such an exclusion really makes sense.
If you need something like this, you can program a corresponding timer.

* timeZoneOfEvent - stores the name of the time zone to use. if the time zone of
  the server matches the time zone of the
  event does not match, the server time is converted to the time zone time of
  the event.
  *Value range*:
  List of generated time zones. The time zone issue is important because some
  timers convert times to UTC
  must. (course of the sun, ...)
  *Annotation*:
  All time zones are currently being generated. It is possible to change the
  time zones to a general selection
  restrict.
* useTimeZoneOfFrontend - yes/no parameters. If the value is set, the server's
  time zone is always used.
* ultimateBeginningTimer - Ultimate beginning of timers
  *Default*:
  January 1 0001 00:00:00
* ultimateEndingTimer - Ultimate end of the timer
  *Default*:
  December 31, 9999 23:59:59

#### Customtimer - Developer - Motivation

The timers do not cover every case. You can also define your own timer class
using the `TimerInterface`
must implement. . You integrate them via your `ext_localconf.php`. You can use
your own Flexform to set your timer
give your own parameters.

### Viewhelper

There are five view helpers:

* timeZoneOfEvent - stores the name of the time zone to use. if the time zone of
  the server matches the time zone of the
  event does not match, the server time is converted to the time zone time of
  the event.
  *Value range*:
  List of generated time zones. The time zone issue is important because some
  timers convert times to UTC
  must. (course of the sun, ...)
  *Annotation*:
  All time zones are currently being generated. It is possible to change the
  time zones to a general selection
  restrict.
* useTimeZoneOfFrontend - yes/no parameters. If the value is set, the server's
  time zone is always used.
* ultimateBeginningTimer - Ultimate beginning of timers
  *Default*:
  January 1 0001 00:00:00
* ultimateEndingTimer - Ultimate end of the timer
  *Default*:
  December 31, 9999 23:59:59

#### Customtimer - Developer - Motivation

The timers do not cover every case. You can also define your own timer class
using the `TimerInterface`
must implement. . You integrate them via your `ext_localconf.php`. You can use
your own Flexform to set your timer
give your own parameters.

### Viewhelper

There are five view helpers:

- timer:isActive - works similar to `f:if`, checking if a time in the active
  range is a
  periodic timer.
- timer:flexToArray - When converting a flexform definition to an array, the
  array contains many
  superfluous intermediate levels. These levels can be removed with the
  Viewhelper, so that the resulting array of the
  flexform arrays become flatter/simpler.
- timer:forCommaList - works like `f:for`, but instead of an array or an
  iterable object
  here a string with a comma-separated list is to be specified for the
  attribute `each`. With the additional parameter `limiter`
  you can also replace the comma with other characters. With the additional
  Boolean switch `trim` you can force that
  the white characters (space, break, ...) at the beginning and end of the
  string are removed from the individual strings in the list.
- (will be removed in version 14) timer:format.date - works
  like `f:format.date`, with the addition of
  outputting times for a specific time zone
  permitted. You should instead use the optimized format.date-viewhelper with
  the parameter `locale``.
- (removed in 12.2.0) ~~timer:format.jewishDate - works similarly
  to `f:format.date`, outputting times for a specific time zone
  allowed and whereby the dates are transformed into the Jewish calendar.
  **Deprecated - Will be removed in version 12!~~ _Use the new view
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
  found in PHP. (Removed in 12.2.0) ~~The timer makes the view
  helper ``timer:format.jewishDate`` superfluous~~.

#### timer:format.calendarDate - Attributes

- **flagformat** determines which formatting rules should be used:
  0 = [PHP-DateTime](https://www.php.net/manual/en/datetime.format.php),
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
  17: 'persian', 18: 'roc'. In addition, 'julian' is also allowed as the 19th
  for the Julian calendar.
- **calendartarget** defines the calendar for which the date should be output.
  PHP allows the following values: 0:'buddhist', 1:'chinese', 2:'coptic', 3:'
  dangi', 4:'default', 5:'ethiopic', 6:'ethiopic-amete-alem' , 8:'gregorian',
  9:'hebrew', 10:'indian', 11:'islamic', 12:'islamic-civil', 13:'islamic-rgsa',
  14:'islamic-tbla', 15 :'islamic-umalqura', 16: 'japanese', 17: 'persian',
  18: 'roc'. In addition, 'julian' is also allowed as the 19th for the Julian
  calendar.
- **locale** determines the regional localization and consists of the two-letter
  language abbreviation (de, en, fr, es, ...) and separated by an underscore
  from the abbreviation for the nation (DE, GB, US, AT, Switzerland,
  France, ...). The value in __locale__ could look like this: `de_DE`, `en_GB`
  or `es_US`.

### Data Processors

Since the results of the data processors are cached, the user must consider
what a sensible caching period is and define this accordingly.
All data processors use the ``cache`` parameter to define the cache time.
A missing value leads to the default case. For data processors that use the
timer functionality,
the frontend cache is automatically deleted when the data is calculated.

All parameters are evaluated via `stdWrap`. That means,
Instead of explicit values, typescript can always be used to dynamically define
the values.

It must be checked whether the dynamization of the parameters leads to conflicts
with caching.
If in doubt, deactivate caching with ``cache = none``.
The expressions `0`, `` ``, ``none``, ``no`` or ``null`` are also permitted.
You can explicitly trigger the default case for caching
with ``cache = default``.

In principle, an example for the application of the same should be found as a
comment in the source code of the respective DataProcessors.
For the friends of TypoScript programming it should be said that the parameters
are read in via the stdWrap method. The recursive use of Typoscript to dynamize
the setup is therefore possible; even if it is expressly not recommended here.

#### RangeListQueryProcessor

The processor creates a list of dates for the records with periodic timers from
a table. The
Data processor works similar to `DbQueryProcessor`.

##### _Example in Typoscript_

```
tt_content.timer_timersimul >
tt_content.timer_timersimul < lib.contentElement
tt_content.timer_timersimul {

     templateName = timersimul

     dataProcessing.10 = Porthd\Timer\DataProcessing\RangeListQueryProcessor
     dataProcessing.10 {
         table = tx_timer_domain_model_event

         # defines, the page-id of the current page
         pidInList.stdWrap.cObject = TEXT
         pidInList.stdWrap.cObject.field = uid
         recursive = 1

         # sort in reverse order
         # reverse = false

         # name of output object
         as = examplelist

         # deactivate the caching of the rangelist-processor
         # the frontend-cache of the current page will be cleared every time
         cache = none
     }
}

```

See also example of content element ``timersimul``

##### _Parameters for the data processor `RangeListQueryProcessor`_

Due to the repetition of periods, a data record can be listed several times.
Therefore, a start time and an end time must always be defined.
Each time the list is recalculated, the frontend cache of the corresponding page
is also deleted.

To calculate the pages, the DataProcessor uses the getRecords method of the
ContentObjectRenderer, which in turn uses the parameters
``pidInList``,
``uidInList``,
``languageField``,
``markers``,
``includeRecordsWithoutDefaultTranslation``,
``selectFields``,
``max``,
``begin``,
``groupBy``,
``orderBy``,
``join``,
``leftjoin``,
``rightjoin``,
``recursive`` and
``where`` interpreted (
See [TypoScript>CONTENT>select](https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Select.html)
for more information) .

| Parameter        | Default                                                                                                                              | description
|------------------|--------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                  | **_Records_**                                                                                                                        |
| if               | true                                                                                                                                 | If the value or the typescript expression evaluates to false, the data processor is not executed.
| table            | tx_timer_domain_model_event                                                                                                          | This table is used to search for all available records with timer information.
| as               | records                                                                                                                              | Name of the output variable that is used, for example, in the fluid template
|                  | **_Start and General_**                                                                                                              |
| datetimeFormat   | Y-m-d H:i:s                                                                                                                          | Defines the format in which the date is given. The characters defined in PHP apply (see [List](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart    | &lt;now&gt;                                                                                                                          | Defines the point in time at which the list should start. If `reverse = false` it is the earliest time, and if `reverse = true` it is the latest time.
| timezone         | &lt;defined in PHP&gt;                                                                                                               | Defines the time zone to be used with the date values.
| reverse          | false                                                                                                                                | Defines whether the list of active areas is sorted in descending or ascending order. With `reverse = true` the end of the active areas is decisive; In the default case `reverse = true` it is the beginning of the active time.
| cache            | default                                                                                                                              | Defines cache behavior. In the default case, the caching time is calculated based on the timer list. A numerical specification overrides the calculated caching time with a static value. The parameters ``0``,`` ``,``no``,``none``, or ``null`` cause the data processor to recalculate the data each time.
|                  | **_Limit of the period_**                                                                                                            |
| maxCount         | 25                                                                                                                                   | Limits the list to the maximum number of list items
| maxLate          | &lt;seven days relative to start date&gt;                                                                                            | Delimits the list via a stop date that can never be reached.
| maxGap           | P7D                                                                                                                                  | Limits the list by calculating the corresponding stop time from the start time. The PHP notation for time intervals is to be used to specify the time difference (see [Overview](https://www.php.net/manual/en/class.dateinterval.php)).
|                  | **_Special_**                                                                                                                        |
| userRangeCompare | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` or `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Only the date values are used to determine the order. The user could also consider other sorting criteria. For example, one might want a list sorted first by start date and then by duration of active areas if the start date is the same.

#### SortListQueryProcessor

The `sys_file_reference` table does not support the `starttime` and `endtime`
fields. To nevertheless temporal
To achieve varying images, the media received by the data processor can be
sorted according to periodicity
Have the list transferred and converted and use it accordingly in the template.

##### _Example in TypoScript_

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

Note that FLUIDTEMPLATE is cached. For this reason:

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

Due to the repetition of periods, a data record can be listed several times.
Therefore, a start time and an end time must always be defined.
Each time the list is recalculated, the frontend cache of the corresponding page
is also deleted.

In contrast to the `RangeListQueryProcessor`, the `SortListQueryProcessor` uses
data generated by a previous or parent data processor process.

In contrast to the `RangeListQueryProcessor`, the `SortListQueryProcessor` uses
data generated by a previous or higher-level data processor process.
The parameters are similar to the `RangeListQueryProcessor`.
However, since the data processor is used for further processing of timer lists,
the getContent method is no longer used.
Therefore, the `table` parameter is replaced by the `fieldName` parameter.

| Parameter        | Default                                                                                                                              | description
|------------------|--------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                  | **_Records_**                                                                                                                        |
| if               | true                                                                                                                                 | If the value or the typescript expression evaluates to false, the data processor is not executed.
| fieldname        | myrecords                                                                                                                            | This table is used to search for all available records with timer information.
| as               | records                                                                                                                              | Name of the output variable that is used, for example, in the fluid template
|                  | **_Start and General_**                                                                                                              |
| datetimeFormat   | Y-m-d H:i:s                                                                                                                          | Defines the format in which the date is given. The characters defined in PHP apply (see [List](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php)).
| datetimeStart    | &lt;now&gt;                                                                                                                          | Defines the point in time at which the list should start. If `reverse = false` it is the earliest time, and if `reverse = true` it is the latest time.
| timezone         | &lt;defined in PHP&gt;                                                                                                               | Defines the time zone to be used with the date values.
| reverse          | false                                                                                                                                | Defines whether the list of active areas is sorted in descending or ascending order. With `reverse = true` the end of the active areas is decisive; In the default case `reverse = true` it is the beginning of the active time.
| cache            | default                                                                                                                              | Defines cache behavior. In the default case, the caching time is calculated based on the timer list. A numerical specification overrides the calculated caching time with a static value. The parameters ``0``,`` ``,``no``,``none``, or ``null`` cause the data processor to recalculate the data each time.
|                  | **_Limit of the period_**                                                                                                            |
| maxCount         | 25                                                                                                                                   | Limits the list to the maximum number of list items
| maxLate          | &lt;seven days relative to start date&gt;                                                                                            | Delimits the list via a stop date that can never be reached.
| maxGap           | P7D                                                                                                                                  | Limits the list by calculating the corresponding stop time from the start time. The PHP notation for time intervals is to be used to specify the time difference (see [Overview](https://www.php.net/manual/en/class.dateinterval.php)).
|                  | **_Special_**                                                                                                                        |
| userRangeCompare | `Porthd\Timer\Services\ListOfEventsService::compareForBelowList` or `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Only the date values are used to determine the order. The user could also consider other sorting criteria. For example, one might want a list sorted first by start date and then by duration of active areas if the start date is the same.

#### FlexToArrayProcessor

The `FlexToArrayProcessor` allows reading `Flex` fields and converting them into
simple arrays.
In this way, the calendar-specific resources could simply be reloaded for
the `periodlist` content element.

```
         30 = Porthd\Timer\DataProcessing\FlexToArrayProcessor
         30 {
             # regular if syntax to prevent using the data processor
             #if.isTrue.field = record

             # field containing the flexform array
             # Default is `tx_timer_timer`
             field = tx_timer_timer

             # A definition of flattenkeys overrides the default definition.
             # The attributes `timer` and `general` are used as sheet names in my customTimer flexforms
             # The following definition is the default if there is no definition: `data,general,timer,sDEF,lDEF,vDEF`
             flattenkeys = data,general,timer,sDEF,lDEF,vDEF

             # Output variable with the resulting list as an array
             as = flexlist

```

##### Parameters for the FlexToArrayProcessor

| Parameters  | Default                           | Description
|-------------|-----------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
| if          | true                              | If the value or typescript expression is false, the dataprocessor will not run.
| field       | tx_timer_timer                    | The name of the field that contains the Flexform string.
| flattenkeys | data,general,timer,sDEF,lDEF,vDEF | This table is used to search for all available records containing timer information.
| as          | flattenflex                       | Name of the output variable that is used, for example, in the fluid template
| cache       | default                           | Defines cache behavior. In the default case, the caching time is calculated based on the timer list. A numerical specification overrides the calculated caching time with a static value. The parameters ``0``,`` ``,``no``,``none``, or ``null`` cause the data processor to recalculate the data each time.

#### (removed since 12.3.0) ~~MappingProcessor (deprecated)~~

~~The data processor `MappingProcessor` allows mapping of arrays into new arrays
or into a JSON string.
In this way, the data can easily be made available to the JavaScript using HTML
attributes.
The data processor knows simple generic functions, for example to assign unique
IDs to events.
It also allows the mapping/mapping of field contents and the creation of new
fields with constant data.~~

~~removed code-exampe for typoscript~~

#### BetterMappingProcessor

Sometimes you use a JavaScript framework such as a calendar framework in
frontend,
which requires a list of data in a defined data format as a JSON string.
The problem is that TYPO3 knows the data but has saved it in a different form.
The BetterMappingProcessor helps to translate existing data lists into slightly
modified data lists.

!!!As soon as you use the BetterMappingProcessor, you should always ask yourself
whether there isn't a better solution, which is very likely.

The data processor `BetterMappingProcessor` allows mapping of arrays into new
arrays or into a JSON string.
The logic is slightly modified for the mapping data processor, now the input and
output fields have to be defined directly.
With the dot notation it is possible to use associative arrays with several
Read in the data from the lower levels or one for the output
to create an associative array with multiple levels.
Two variants have been added to the generic area.
It is planned for the future to expand the data processor with an interface for
a user function.
As before, the data processor allows the mapping/representation of field
contents, date values and the creation of new fields
with constant data.

```
        20 = Porthd\Timer\DataProcessing\BetterMappingProcessor
        20 {

            # regular if syntax
            #if.isTrue.field = record

            # The defaultvalue for the inputfield is 'holidayList';
            inputfield = holidayList
            # Each field must part of holidaycalendar
            # allowed types are
            #    `constant`(=pretext.posttext),
            #    `index`(=pretext.<indexOfDataRow>.posttext)
            #    `datetime` (=dateTimeObject->format(posttext); dateTimeObject is in the Field, which is declared be pretext)
            # every entry must be some formal
            #            generic {
            #                id {
            #                    pretext = event
            #                    posttext = holiday
            #                    type = index
            #                }
            #
            #                calendarId {
            #                    pretext = cal1
            #                    posttext =
            #                    type = constant
            #                }
            #                start {
            #                    pretext = date
            #                    posttext = Y-m-d
            #                    type = datetime
            #                }
            #            }
            generic {
                10 {
                    # the inputfield may missing
                    inField =
                    # if the outputfield is missing or the key has an typeerror, an exception will occur.
                    outField = category
                    pretext = allday
                    posttext =
                    # allowed types are `constant`, `includevalue`, `includeindex`, `datetime`
                    # if the inField is missing for type `includevalue`, a empty string will be used
                    type = constant
                }
                20 {
                    inField = dateStart
                    # the outputfield must contain a DateTime-Object
                    outField = start
                    format = Y-m-d
                    type = datetime
                }
                30 {
                    inField = dateEnd
                    outField = end
                    format = Y-m-d
                    type = datetime
                }
                40 {
                    inField = cal.eventtitle
                    outField = title
                    type = translate
                }

            }

            mapping {
                10 {
                    inField = cal.identifier
                    outField = id
                }
                20 {
                    inField = cal.title
                    outField = basetitle
                }
                30 {
                    inField = cal.tag
                    outField = calendarId
                }

               40 {
                    inField = cal.add.freelocale
                    outField = class
                    type = userfunc
                    # two parameter for customfunc(parameter, $betterMappingProcessorObject)
                    #    where parameter is the associative array with the keys
                    #    `params`, `mapKey`,`mapItem` and `start`
                    userfunc =  Vendor\Namespace\CustomClass->customFunc
                }
            }

            # outputformat has the values `array`,`json`, `yaml`
            # if the outputformat is unknown/undifined, `json` will be used by default
            outputFormat = json

            # if the output-format is yaml, then `yamlStartKey` will define a starting-key for your result-array.
            # the default is an empty string, which emans no starting-key for your array in a simplified yaml-format
            #yamlStartKey = holydayList

            # output variable with the resulting list
            # default-value is `holidayListJson`
            as = holidaycalendarJson

        }

```

##### Parameters for the BetterMappingProcessor

Note: Defining the mapping becomes easier if you use xdebug/var_dump to
visualize the structure of the transformation.

| Parameters   | Default                                                      | Description
|--------------|--------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|              | **_main level_**                                             |
| if           | true                                                         | If the value or typescript expression is false, the dataprocessor will not run.
| generic.     | &lt;Array with definitions&gt;                               | Generate a count of data based on the index of the list to be mapped or on constant values (``stdWrap`` affine)
| mapping.     | &lt;Array of conversion instructions&gt;                     | Creates an entry in the result list from the subfield (`inField`) of a list with a new associative index (`outField`). In addition to standard conversions, user-defined functions are also permitted.
| as           | betterMappingJson                                            | Name of the output variable that is used, for example, in the fluid template.
| outputFormat | &lt;Exception&;                                              | This information is mandatory. There is output format for the mapped associative array. The allowed values are `json`, `array` and `yaml`.
| yamlStartKey |                                                              | If the output format is `yaml`, then the list can be given a generic term. (Helpful if you want to merge multiple associative arrays into one file.)
| cache        | default                                                      | Defines cache behavior. In the default case, the caching time is calculated based on the timer list. A numerical specification overrides the calculated caching time with a static value. The parameters ``0``,`` ``,``no``,``none``, or ``null`` cause the data processor to recalculate the data each time.
|              | **_sub level `mapping.`_**                                   | _simple mapping_
| inField      |                                                              | Path to the date in the named field in the record from the data list, where the dot notation in the names allows access to deeper levels in the associative data array. (Attention: If the date in the data array itself is an array, only the first entry of the array will be transferred if it is a scalar - i.e. int, float, boll or string.)
| outField     |                                                              | Path for the data in the newly generated data set in the associative results array, which is generated with the data processor, where the dot notation in the names also allows the creation of deeper levels in the associative results array. (No check against overwriting during the generation process)
|              | **_general input fields in the sublevel `generic.`_**        | _simple-generic mapping_
| inField      |                                                              | Path to the date in the named field in the record from the data list, where the dot notation in the names allows access to deeper levels in the associative data array.
| outField     |                                                              | Path for the data in the newly generated data set in the associative results array, which is generated with the data processor, where the dot notation in the names also allows the creation of deeper levels in the associative results array. (No check against overwriting during the generation process)
| pretext      |                                                              | Defines a constant expression to be prefixed, which can also be created using Typescript. (Insert parameters using the `stdWrap` method; instead of static expressions you can also use `LLL:EXT:...` values for translations)
| posttext     |                                                              | Defines a constant trailing expression that can also be created using Typescript. (Insert parameters using the `stdWrap` method; instead of static expressions you can also use `LLL:EXT:...` values for translations)
| type         |                                                              | Defines the generic method of generic creation. The following variants are defined: ... .
|              | **_`generic.type=constant`_**                                | Ignores any input information from the current data set.
|              | **_`generic.type=index` oder `generic.type=includeindex` _** | Uses the value of the current index of the selected data record from the data list (usually a number; only in the associative array or iterative object a string)
|              | **_`generic.type=value` oder `generic.type=includevalue` _** | Uses the scalar value of the field of the selected data record from the data list that is currently defined via `inField`.
|              | **_`generic.type=translate`_**                               | Interprets the scalar value of the field currently defined via `inField` of the selected data record from the data list as a key or as a key path for a value in an `xlf` translation file. (As long as you don't define an explicit key path with `LLL:EXT:...`, the `localconf.xlf` file of the timer extension is used by default, which you can also use from external extensions to get your own key via extend 'override'](https://docs.typo3.org/p/lochmueller/autoloader/7.4/en-us/Loader/LanguageOverride.html) can.)
|              | **_`generic.type=datetime`_**                                | Converts the DateTime object from the field of the selected record defined via `inField` from the data list to a date format defined in 'format'.
| format       |                                                              | Defines the output format for the DateTime value to be generated. If nothing is specified, the format `Y-m-d\TH:i:s` is used by default. The [Parameters for defining the DateTime format](https://www.php.net/manual/en/datetime.format.php) can be found in the php manual.
|              | **_`generic.type=userfunc`_**                                |
| userfunc     | &lt;Vendor\Extension\PfadZuKlasse->userFunctionName&gt;      | Definition of the user function that receives two parameters: firstly, the parameter array with the current userfunc configuration (`params`), the key of the data set (`mapKey`), the entire data set (`mapItem`) and the scalar `inField ` value (`start`) and secondly the object of the current BetterMapping processor.
| 'any'        | &lt;any values&gt;                                           | In the subfield (`params`), which is passed as a parameter, there are also any defined parameters that have been defined here in addition to the method path in this section for the `userfunc`. (The user is therefore free to define his own control parameters for his user function.)

#### PhpMappingProcessor

Sometimes you use a JavaScript framework such as a calendar framework in the
frontend,
which requires a list of data in a defined data format as a JSON string.
The problem is that in TYPO3 the data is only stored in a different data
structure.
The ``PhpMappingProcessor`` is intended to help overcome such structural
deviations.
The basic principle of the ``PhpMappingProcessor`` is simple. Depending on the
input structure, you can generate a
target record or an array of target records. In TypoScript, you recreate the
target structure of the desired record(s) using the key terms below the `output`
attribute in TypoScript style. You can assign constants, data references or
functions to the target terms. The parameters of the functions are resolved
recursively.
This provides clarity and the greatest possible flexibility. On the input side,
the data must be available either via an associative array, via a stdClass
object or via a getter object.

##### Example TypoScript

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

            # The parent-variable contain an array i.e. data. The datamapper will use it only once.
            # It can although use a part of a dataset.
            # if you use the parameter `_all`, you can use the whole array of processed data `$processedData`.
            inputOrigin = holidayList
            # The parent-variable contain an array with multiplerows. The datamapper will iterate through all rows
            # and generate a list of converted rows.
            # two values allowed 'rows' oder 'static'
            inputType = rows

            # You can define the mapping a separate file instead of using  typoscript.
            # The filename should have the ending .yaml,.yml,.csv or .json
            # Be aware that .csv-files could only contain simple array.
            # dataFile =  EXT:FilePath/FileName.json
            output {
                # A string without the brackets "(" ")" will be interpreted as a data-containing string.
                # A string with the brackets "(" and the ending ")" will be interpreted as a userfunction.
                # The part outside the brackets does not contain any space oder colons.
                # A userfunction has thisway one of the three structures:
                #    methodName(<parameterlist>) (pure php method)
                #    Namespace/Of/Class->methodName(<parameterlist>) (every time the class will newly be instanciated)
                #    Namespace/Of/Class::methodName(<parameterlist>) (static method: recommended)
                # The parameter separated by a comma by default. The comma can be escaped by `\,`.
                # Unknown methods, missing/wrong parameters will cause an exception.
                # Call of methods in the parameter-list are allowed.
                # The attributes under rows only for Security-reasons
                # The type of output of @datatPath@ is not defined. It depends on the the structure of the origin.
                # If you need a specific type like an integer a flaot, then you should use floatval(@datatPath@) or
                # intval(@datatPath@). The custum function must always handle all types - unwished types with an exception.

                category = allday
                # Remark: starting the namespace of the custom method with `\` will cause an error
                start = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateStart@,'Y-m-d','Europe/Berlin')
                        # (new dateTime(@dateStart@))->format('Y-m-d')
                end = Porthd\Timer\UserFunc\MyDateTime->formatDateTime(@dateEnd@,'Y-m-d','Europe/Berlin')
                        # (new dateTime(@dateEnd@))->format('Y-m-d')
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

#### PeriodlistProcessor

The DataProcessor `PeriodlistProcessor` allows the reading of the appointment
list, which is stored in the PeriodlistTimer in the Yaml file
is defined. In addition to the actual fields, the data processor also generates
the corresponding DatTime objects for the start and end times of the
appointments and calculates the number of days (24 hours = 1 day) between the
appointments.

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
                 # startJson is the targetfieldName in the following data processor mappingProcessor
                 startJson {
                     # use the format parameter defined in https://www.php.net/manual/en/datetime.format.php
                     # escaping named parameters with the backslash in example \T
                     format = Y-m-d
                     # allowed are only `diffDaysDatetime`, `startDatetime` and `endDatetime`, because these are automatically created datetime-Object for the list
                     # These fields are datetime-object and they are generated from the estimated fields `start`and `stop` by this data processor
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

##### Parameters for the PeriodlistProcessor

The data processor Periodlist allows the sorted output of different time ranges,
which can explicitly be multi-day, which are always marked by a start or end
date and which are definitely not periodic.
This type of display is suitable, for example, for lists of school holiday dates
in different federal states or for lists of tour dates for various artists.

The structure of the YAML files has been described above for the
PeriodlistTimer.
Or you can find an example file
in `timer\Resources\Public\Yaml\Example_PeriodListTimerBremen.yaml`.
Please note that you can also insert additional attributes if you need
additional structured information for output in the frontend.
These attributes or the associated static information are easily looped through.

| Parameter      | Default                       | Beschreibung
|----------------|-------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                | **_main level_**              |
| if             | true                          | If the value or typescript expression is false, the dataprocessor will not run.
| field          | tx_timer_timer                | Name of the field that contains the string with the flexform information. The Flexform information contains either the YAML path or references to references to YAML files or CSV files. Note that YAML files can include additional YAML files via the `import` statement.
| selectorfield  | tx_timer_selector             | The check field defines the name of the field that determines the variant of the Flexform selection. This must have the value `txTimerPeriodList`.
| tablename      | tt_content                    | The table name is important if the data processor in the fluid template is based on data that does not come from the `tt_content` table or the `pages` table.
| limit.         |                               | Defines a TypoScript array for the interval boundaries for the list, which are generated from the lists of periodic data.
| flagStart      | true                          | Defines whether the different appointments are sorted according to the upper limit (false \| 0 \| '') or according to the lower limit (true).
| maxCount       | 25                            | Maximum number of appointments that are transferred to the list. This is a mandatory entry, which may also override the interval limits of `limit.`. There is no value for 'infinity'.
| &lt;start&gt;. | &lt;defined in TypoScript&gt; | The name defines the output field for the start value of an appointment in the new appointment list. It can be named as you need it later, for example in a JSON string.
| &lt;end&gt;.   | &lt;defined in TypoScript&gt; | The name defines the output field for the end value of an appointment in the new appointment list. It can be named as you need it later, for example in a JSON string.
|                | **_sub level `limit.`_**      | _Defines the range of expenses by two date values_
| lower          |                               | Defines the lower date limit from which dates are searched. [flagStart specifies whether the value is in `start` (true) or in `stop` (false).]
| upper          |                               | Defines the upper date limit up to which dates are searched. [flagStart specifies whether the value is in `start` (true) or in `stop` (false).]
|                | **_sub level `start.`_**      | _Defines the lower limit of the appointment range, which is called `start` here_
| format         |                               | Defines the output format for the date value.
| source         |                               | Name of the field in the input list, which may differ from the index name in the associative array for the output.
|                | **_sub level `end.`_**        | _Defines the lower limit of the date range, which is called `end` here_
| format         |                               | Defines the output format for the date value.
| source         |                               | Name of the field in the input list, which may differ from the index name in the associative array for the output.

#### HolidaycalendarProcessor

The data processor is used to evaluate dev CSV files with the holiday dates.

```
        10 = Porthd\Timer\DataProcessing\HolidaycalendarProcessor
        10 {

            #!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            #!!! every parameter will support the typoscript-functionality `stdWrap` !!!
            #!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            #!!! The chinese-calendar is not supported yet, because the php is buggy !!!
            #!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

            # regular if syntax
            #if.isTrue.field = record

            # This definition will override the system-definition and take this locale for the definition of locales.
            # You should normally not use this, becuse the locale should be defined in your LocalConfiguration.php.
            # The value will be seen in see GLOBALS['TYPO3_CONF_VAR']['SYS']['systemLocale'].
            # if there is no information and if this definition is missing, the locale 'en_GB.utf-8' will be used.
            locale = de_DE.utf-8
            # default is `gregorian`. Allowed are all calendars, which your intlDateFormatter in your php can handle,
            #    and the julian calendar
            calendar = gregorian
            # default is the timezone of your TYPO3-System (normally defined in the LocalConfiguration.php or
            #   see GLOBALS['TYPO3_CONF_VAR']['SYS']['phpTimeZone']
            timezone = Europe/Berlin
            start {
                # last year
                year.stdWrap.cObject = TEXT
                year.stdWrap.cObject {
                    value.stdWrap.cObject = TEXT
                    value.stdWrap.cObject {
                        data = date:Y
                        intval = 1
                        wrap = |
                    }

                    prioriCalc = 1
                }

                # inclusive monthnumber,  if missing, then it is equal to current month
                month = 1
                # inclusive daynumber,  if missing, then it is equal to current day
                day = 1
            }

            stop {
                # second next year
                year.stdWrap.cObject = TEXT
                year.stdWrap.cObject {
                    value.stdWrap.cObject = TEXT
                    value.stdWrap.cObject {
                        data = date:Y
                        intval = 1
                        wrap = |+2
                    }

                    prioriCalc = 1
                }

                # inclusive monthnumber,  if missing, then it is equal to startmonth
                month = 1
                # inclusive daynumber,  if missing, then it is equal to the startday
                day = 1
                # if `daybefore` is unequal to 0, then the date will be decremented by one day. So you can detect one year in a
                #   foreign calendar without knowing the number of days in the last month.
                # if `daybefore` is zero or if the value is missing, nothing will happen.
                daybefore = 1
            }

            # the alias-file contain a list of alias-phrases, which are merged nondestructive to the `add` part of each related holiday-definition,  under the attribute `aliasDateRel`.
            # this parameterblock is optional
            # direct path, EXT:Path or URL to the file with the alias-definition
            # the definition of `aliasConfig` will overrule the definition of `aliasPath`.
            #            aliasPath = directPath
            #            aliasConfig {
            #                flexDbField = pi_flexform
            #                pathFlexField = aliasPath
            #                falFlexField = aliasPath
            #            }

            # the holiday file can contain a list of alias-phrases, which are merged nondestructive to the `add` part of each related holiday-definition, under the attribute `aliasDateRel`
            # the holiday file has a list of holiday- or eventday-definition under the attribute 'calendarDateRel'
            # the definition of `holidayConfig` will overrule the definition of `holidayPath`.
            # the missing of both (`holidayPath` and `holidayConfig`) will cause an exception.
            # direct path, EXT:Path or URL to the file with the holiday-definition
            holidayPath = EXT:timer/Resources/Public/Csv/ExcelLikeListForHolidays.csv
            holidayConfig {
                flexDbField = pi_flexform
                pathFlexField = holidayFilePath
                falFlexField = holidayFalRelation
            }

            # name of output-variable
            as = holidayList
        }
```

##### Parameters for the HolidaycalendarProcessor

The data processor produces a list of holidays for a specific time interval from
a list of holidays.

| parameters     | default                                              | description
|----------------|------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
|                | **_main level_**                                     |
| if             | true                                                 | If the value or typescript expression is false, the dataprocessor will not run.
| aliasPath      |                                                      | Explicitly defines in Typescript a path to a file in CSV or YAML format, which contains configurations for various aliases, which in turn can supplement the configurations of holidays in the holiday lists. (Overrides any `aliasConfig.` definition.)
| alias.         | &lt;array with references to alias lists&gt;         | (2023/10/04 - To Do: still needs to be revised.) Defines the reference to a FAL entry or to a Flexform field in which YAML or CSV data with the lists of alias definitions can be found.
| holidayPath    |                                                      | Explicitly defines in Typescript a path to a file in CSV or YAML format that contains configurations for various holidays. (Overrides any `holidayConfig.` definition.)
| holidayConfig. | &lt;array containing references to holiday lists&gt; | (2023/10/04 - To Do: still needs to be revised.) Creates the reference to a FAL entry or to a Flexform field in which YAML or CSV data with the lists of holiday definitions can be found.
| as             | holidayList                                          | Name of the output variable that is used, for example, in the fluid template.
| cache          | default                                              | Defines cache behavior. In the default case, the caching time is calculated based on the timer list. A numerical specification overrides the calculated caching time with a static value. The parameters ``0``,`` ``,``no``,``none``, or ``null`` cause the data processor to recalculate the data each time.
| calendar       | gregorian                                            | (2023/10/04: not really tested) The Gregorian calendar is used as the base calendar. However, an alternative calendar can also be used if it is supported by the PHP extension 'IntlDateFormatter' and is not obviously error-prone - currently the calendars `dangi`, `chinese`. If such calendars are to be used, the result is calculated internally in the Gregorian calendar, which is then finally calculated back into the defined calendar. This makes it possible to display the holidays in non-Gregorian calendar systems. (However, as far as I know, there are hardly any calendar frameworks in JavaScript that also support non-Gregorian calendar systems.)
| timezone       | &lt;TYPO3 localconfiguration.php&gt;                 | (2023/10/04: not really tested) Defines the underlying time zone. By default, the time zone defined in the TYPO3 settings is used. But this can also be overstated.
| locale         | en_GB                                                | (2023/10/04: not really tested) Defines the underlying calendar system based on national localization.
|                | **_sub level `start.`_**                             | _Defines the lower limit of the appointment range._
| year           |                                                      | year. It depends on the calendar (locale).
| month          |                                                      | Month number. It depends on the calendar (locale).
| day            |                                                      | day of the month. The value is calendar dependent (locale).
|                | **_sub level `stop.`_**                              | _Defines the lower limit of the appointment range._
| year           |                                                      | year. It depends on the calendar (locale).
| month          |                                                      | Month number. It depends on the calendar (locale).
| day            |                                                      | day of the month. The value is calendar dependent (locale).
|                | **_`holidayConfig.`_**                               | _Reference to dates for holiday definitions_
| flexDbField    |                                                      | (2023/10/04: not really tested) Defines the name of the field that contains the flexform string in the data record.
| pathFlexField  |                                                      | (2023/10/04: not really tested) Defines the name of the field within the Flexform that contains the path information.
| falFlexField   |                                                      | (2023/10/04: not really tested) Defines the name of a FAL field in a Flexform definition.
|                | **_`aliasConfig.`_**                                 | _Reference to supplemental alias definition data that can be used in holiday definitions_
| flexDbField    |                                                      | (2023/10/04: not really tested) Defines the name of the field that contains the flexform string in the data record.
| pathFlexField  |                                                      | (2023/10/04: not really tested) Defines the name of the field within the Flexform that contains the path information.
| falFlexField   |                                                      | (2023/10/04: not really tested) Defines the name of a FAL field in a Flexform definition.
