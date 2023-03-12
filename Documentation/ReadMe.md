~~# Extension Timer - version 11.x

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
where the developer then has to think about the caching of the frontend. About
data processors
It should be easy to compile simple lists that can be displayed in the frontend.
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

#### Content element `periodlist` for simple appointment lists

The content element `periodlist` has a similar structure to the content
element `textmedia`.
It also allows the output of simple appointment lists. The data for this content
element will be
saved as a flexform in the `pi_flexform` field.

In addition to the parameters for the periodic data, you can also specify paths
to JavaScript and CSS.
In this way you can integrate your own calendar system. Exemplary was
Here is the lean JavaScript framework by Jack
Ducasse ([https://github.com/jackducasse/caleandar](https://github.com/jackducasse/caleandar))
used. In principle, you can of course also use any other calendar framework.

#### Content element `holidaycalendar` for holiday

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

Three data processors have been defined so that the data can be read.

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

~~The third data processor `MappingProcessor` is required to transfer the
appointment data to the Fluid template as a JSON string.
In this way, the data can easily be made available to the calendar framework via
an HTML attribute.~~
`MappingProcessor` is deprecated and will be removed in version 12 because it
doesn't support multilevel arrays.

In the future, `BetterMappingProcessor` will be used as the third data processor
for the mapping.
It can help to pass an appropriate JSON string to the Fluid template.
The data can easily be made available to the TuiCalendar framework or another
calendar framework via an HTML attribute.

### Content element `timersimul` as an example

The content element `timersimul` shows an example of how the view helpers and
the data processors are used. In
Production environments should hide them for editors. It will be removed when
the extension reaches `beta` status
reached.

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

* CalendarDateRelTimer - (In preparation) Most religious, historical, political,
  economic or other holidays are fixed on a date in a calendar. The powerful
  want to avoid mentally overwhelming the common people (so that every Dölmer
  also appreciates the festival at the right time). In the course of human
  history, many different calendar systems have been developed and there are
  many regionally different important festivals. The timer wants to take this
  variability into account by allowing the consideration of different calendar
  systems.
  (Example 5760 minutes (=2 days) after Ramadan (1.9.; Islamic calendar) for 720
  minutes (=6 hours)). At the same time, this timer can also be used to output
  lists of appointments. The workflow is supported to define the appointment
  lists - i.e. public holiday list as well as timer definitions in an Excel
  table and to provide the timer with the list as a CSV file.
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
  **Recommendation** _Use the new more general timer `calendarDateRelTimer`_**
  instead
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

#### Notes on the CalendarDateRelTimer workflow

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
timer `CalendarDateRelTimer`.

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
- _easterly_: This keyword is only valid for a calculation form limited to the
  Gregorian or to the Julian calendar (`arg.calendar`). It determines a public
  holiday relative to Easter Sunday, which can be calculated using Gauss's
  Easter formula or the PHP function. In `arg.statusCount` the positive or
  negative number of days relative to Easter Sunday is given. If the number is
  missing or there is a `0`, then of course Easter Sunday itself is meant.
- _weekdayly_: Here you calculate the i.th (`arg.statusCount`) day of the
  week (`arg.status`) within a month (`arg.month`) for a selected
  calendar (`arg.calendar`). The day of the week is characterized by a number,
  with 1 standing for Monday and 7 for Sunday. If there is a negative number
  in `arg.statusCount` then the position of the weekday is determined relative
  to the end of the month. There is no check as to whether the date is still in
  the month in question. If another value is specified in `arg.secDayCount`,
  then the day is determined relative to the calculated day of the week. This is
  required, for example, for the Geneva Day of Prayer in Switzerland, which is
  always celebrated on the Thursday after the first Sunday in September.
- _mooninmonth_: Some festivals have been transferred from one calendar system
  to another throughout the stories, such as the Vesakh festival.
  Today the festival is celebrated in some countries on the first in other
  countries on the second full moon day in May. In general, for a selected
  calendar (`arg.calendar`), this means that the month is given as a number in
  the `arg.month` field. The calendar is defined in `arg.calendar`. The phase of
  the moon is defined in `arg.status` with the encoding 0/new_moon = new moon,
  1/first_quarter = waxing crescent, 2/full_moon = full moon and 3/last_quarter
  = waning (Islamic?) crescent. If a '1' is specified in `arg.statusCount` or if
  no specification is given, the first moon phase of the month is always used,
  even if there are two identical moon phases in the month. Otherwise, the
  second moon phase is used if it exists in the same month.

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
- _arg_: captures the arguments for the different timer/types `fixed`
  , `fixedrelated`, `fixedshifting`, `weekdayly`, `easterly` or `mooninmonth`.
  The parameters are used to calculate public holidays.
- _arg.startYear_: The year describes from which year of the selected calendar
  the holiday is valid. It applies to all types (`fixed`, `fixedrelated`
  , `fixedshifting`, `fixedmultiyear`, `season`, `seasonshifting`, `weekdayly`
  , `easterly` or `mooninmonth`).
- _arg.endYear_: The year describes up to which year of the selected calendar
  including the holiday is/was valid. As above, it applies to all types.
- _arg.day_: The parameter describes a day of the month for a holiday
  calculation. The entry is used in `fixed`, `fixedrelated`, `fixedshifting`
  , `fixedmultiyear`, `weekdayly`.
- _arg.month_: The parameter describes the month that is important for the
  holiday calculation. The entry is used in `fixed`, `fixedrelated`
  , `fixedmultiyear`, `fixedshifting`, `weekdayly` or `mooninmonth`
- _arg.type_: Describes the type of calendar calculation. `fixed`
  , `fixedrelated`, `fixedshifting`, `fixedmultiyear`, `weekdayly`, `easterly`
  or `mooninmonth` are possible. All identifiers that the various timers provide
  as identifiers are also possible.
- _arg.calendar_: The parameter defines which calendar is used. Usually the
  calendar is `gregorian`. Also allowed are: `buddhist`, `chinese`, `coptic`
  , `dangi`, `ethiopic`, `ethiopic`, `gregorian`, `hebrew`, `indian`, `islamic`
  , `islamic`, `islamic` `, `islamic`, `islamic`, `julian`, `japanese`, `
  persian`, `roc`. The parameter applies to all types (`fixed`, `
  fixedshifting`, `weekdayly`, `easterly` or `mooninmonth`).
- _arg.status_: The entry in `fixedmultiyear`, `fixedrelated`, `season`
  , `weekdayly` or `mooninmonth` is used. With `weekdayly` or
  with `fixedrelated` it specifies the day of the week as a number (1 = Monday,
  ...7 = Sunday). With `mooninmonth` it indicates the phase of the moon (1 =
  waxing crescent, 2 = full moon, 3 = waning crescent, 4 = new moon).
  At `season` it indicates the beginning of the astronomical season (1 = spring
  equinox, 2 = summer solstice, 3 autumn equinox, 4 = winter solstice).
  With `fixedmultiyear` a reference year is specified here.
- _arg.statusCount_: The entry is used in `fixedshifting`, `fixedrelated`
  , `seasonshifting`, `fixedmultiyear`, `weekdayly`, `mooninmonth` or `easterly`
  . With `fixedshifting`, the deviation from the existing day of the week is
  defined via a comma-separated list. `easterly` defines the distance to Easter
  Sunday. With `weekdayly` or with `fixedrelated` it is specified which (first,
  second, ..) weekday in the corresponding month or relative to the fixed date
  is meant. Negative numbers count from the end of the month or towards the
  past. `mooninmonth` defines whether the first or second moon phase of the
  month is meant.
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
- timer:format.date - works like `f:format.date`, with the addition of
  outputting times for a specific time zone
  permitted.
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
  from the abbreviation for the nation (DE, GB, US, AT, Switzerland, France,
  ...). The value in __locale__ could look like this: `de_DE`, `en_GB`
  or `es_US`.

### Data Processors

Since the results of the data processors are cached, the user has to think about
what makes more sense
caching period and define it accordingly.

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

See also example in example content element ``timersimul``

##### _Parameters for the data processor `RangeListQueryProcessor`_

Due to the repetition of periods, a data record can be listed several times.
Therefore, a start time and an end time must always be defined.

| Parameters | Default | Description
|----------------|--------------------------------
--------------------------------------------------
-------------------------------------------------- ----|--------------
| | **_Records_** |
| if | true | If the value or the typescript expression evaluates to false, the
data processor is not executed.
| tables | tx_timer_domain_model_event | This table is used to search for all
available records with timer information.
| pidInList | | Comma-separated list of numeric IDs for pages that may contain
records for determining the list of timer events.
| as | records | Comma-separated list of numeric IDs for pages that may contain
records for determining the list of timer events.
| | **_Start and General_** |
| datetimeFormat | Y-m-d H:i:s | Defines the format in which the date is given.
The characters defined in PHP apply (
see [List](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php))
.
| datetimeStart | &lt;now&gt; | Defines the point in time at which the list
should start. If `reverse = false` it is the earliest time, and
if `reverse = true` it is the latest time.
| time zone | &lt;defined in PHP system&gt; | Defines the time zone to be used
with the date values.
| reverse | false | Defines whether the list of active areas is sorted in
descending or ascending order. With `reverse = true` the end of the active areas
is decisive; In the default case `reverse = true` it is the beginning of the
active time.
| | **_Limit of the period_** |
| maxCount | 25 | Limits the list to the maximum number of list items
| maxLate | &lt;seven days relative to start date&gt; | Delimits the list via a
stop date that can never be reached.
| maxGap | P7D | Limits the list by calculating the corresponding stop time from
the start time. The PHP notation for time intervals is to be used to specify the
time difference (
see [Overview](https://www.php.net/manual/en/class.dateinterval.php)).
| | **_Special_** |
| userRangeCompare
| `Porthd\Timer\Services\ListOfEventsService::compareForBelowList`
or `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Only the
date values are used to determine the order. The user could also consider other
sorting criteria. For example, one might want a list sorted first by start date
and then by duration of active areas if the start date is the same.

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

In contrast to the `RangeListQueryProcessor`, the `SortListQueryProcessor` uses
data generated by a previous or parent data processor process.
The parameters `table` plus `pidInList` are therefore omitted and the
parameter `fieldName` is added.

| Parameters | Default | Description
|-------------------------------|------------------------------
--------------------------------------------------
-------------------------------------------------- ------|--------------
| | **_Records_** |
| if | true | If the value or the typescript expression evaluates to false, the
data processor is not executed.
| fieldName | myrecords | This table is used to search for all available records
with timer information.
| as | sortedrecords | Name of the object that contains the individual events
and is transferred to the Fluid template. Look
at `&lt;f:debug>{sortedrecords}</f:debug>` for the exact structure.
| | **_Start and General_** |
| datetimeFormat | Y-m-d H:i:s | Defines the format in which the date is given.
The characters defined in PHP apply (
see [List](https://www.php.net/manual/en/datetimeimmutable.createfromformat.php))
.
| datetimeStart | &lt;now&gt; | Defines the point in time at which the list
should start. If `reverse = false` it is the earliest time, and
if `reverse = true` it is the latest time.
| time zone | &lt;defined in PHP system&gt; | Defines the time zone to be used
with the date values.
| reverse | false | Defines whether the list of active areas is sorted in
descending or ascending order. With `reverse = true` the end of the active areas
is decisive; In the default case `reverse = true` it is the beginning of the
active time.
| | **_Limit of the period_** |
| maxCount | 25 | Limits the list to the maximum number of list items
| maxLate | &lt;seven days relative to start date&gt; | Delimits the list via a
stop date that can never be reached.
| maxGap | P7D | Limits the list by calculating the corresponding stop time from
the start time. The PHP notation for time intervals is to be used to specify the
time difference (
see [Overview](https://www.php.net/manual/en/class.dateinterval.php)).
| | **_Special_** |
| userRangeCompare
| `Porthd\Timer\Services\ListOfEventsService::compareForBelowList`
or `Porthd\Timer\Services\ListOfEventsService::compareForAboveList` | Only the
date values are used to determine the order. The user could also consider other
sorting criteria. For example, one might want a list sorted first by start date
and then by duration of active areas if the start date is the same.

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

#### MappingProcessor (deprecated)

The data processor `MappingProcessor` allows mapping of arrays into new arrays
or into a JSON string.
In this way, the data can easily be made available to the JavaScript using HTML
attributes.
The data processor knows simple generic functions, for example to assign unique
IDs to events.
It also allows the mapping/mapping of field contents and the creation of new
fields with constant data.

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

             # Output format has the values `array`,`json`
             # If the output format is unknown, json is the default
             outputFormat = json

             # Output variable with the resulting list
             # Default is `periodlist`
             asString = periodListJson

         }

```

#### BetterMappingProcessor

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

             # The default value for the input field is 'holidayList';
             inputfield = holidayList
             # Each field must be part of holiday calendar
             # allowed types are
             # `constant`(=pretext.posttext),
             # `index`(=pretext.<indexOfDataRow>.posttext)
             # `datetime` (=dateTimeObject->format(posttext); dateTimeObject is in the Field, which is declared be pretext)
             # every entry must be some formal
             # generic {
             # id {
             # pretext = event
             # post text = holiday
             # type = index
             # }
             #
             # calendarId {
             # pretext = cal1
             # post text =
             # type = constant
             # }
             #                begin {
             # pretext = date
             # posttext = Y-m-d
             # type = constant
             # }
             # }
             generic {
                 10 {
                     # the input field may be missing
                     inField =
                     # if the output field is missing or the key has a type error, an exception will occur.
                     outField = category
                     pretext = everyday
                     post text =
                     # allowed types are `constant`, `includevalue`, `includeindex`, `datetime`
                     # if the inField is missing for type `includevalue`, an empty string will be used
                     type = constant
                 }
                 20 {
                     inField = dateStart
                     # the output field must contain a DateTime object
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
#
# @todo 2023-03-12: allow custom function
#40 {
# inField = cal.add.freelocale
# outField = class
# type = userfunc
# userfunc =
# }
             }


             # output format has the values `array`,`json`, `yaml`
             # if the output format is unknown/undifned, `json` will be used by default
             outputFormat = json

             # if the output-format is yaml, then `yamlStartKey` will define a starting-key for your result-array.
             # the default is an empty string, which emans no starting-key for your array in a simplified yaml format
             #yamlStartKey = holydayList

             # output variable with the resulting list
             # default value is `holidayListJson`
             as = holidaycalendarJson

         }

```

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

```~~
