"use strict";

/* eslint-disable */
/* eslint-env jquery */
/* global moment, tui, chance */
/* global findCalendar, CalendarList, ScheduleList, generateSchedule */

(function (window, Calendar) {
    var idTuiCalendar = 'tuicalendar';
    var eventContainer = document.getElementById(idTuiCalendar);
    var events = JSON.parse(eventContainer.dataset.events);
    var cal, resizeThrottled;
    // var useCreationPopup = false;
    var useDetailPopup = true;
    var lllMilestone = ((eventContainer.dataset.milestone) ? eventContainer.dataset.milestone : 'milestone');
    var lllTask = ((eventContainer.dataset.task) ? eventContainer.dataset.task : 'task');
    var lllAllDay = ((eventContainer.dataset.allday) ? eventContainer.dataset.allday : 'All Day');
    var listOfDayNames = ((eventContainer.dataset.weekdays) ? JSON.parse(eventContainer.dataset.weekdays) : ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]);
    ;
    // var datePicker, selectedCalendar;
    cal = new Calendar('#' + idTuiCalendar, {
        defaultView: 'month',
        useDetailPopup: useDetailPopup,
        calendars: CalendarList,

        taskView: true,    // Can be also ['milestone', 'task']
        scheduleView: true,  // Can be also ['allday', 'time']
        Template: {
            milestone: function (schedule) {
                return '<span style="color:red;"><i class="fa fa-flag"></i> ' + schedule.title + '</span>';
            },
            milestoneTitle: function () {
                return lllMilestone; // Translate the required language.
            },
            task: function (schedule) {
                return '&nbsp;&nbsp;#' + schedule.title;
            },
            taskTitle: function () {
                return '<label><input type="checkbox" />' + lllTask + '</label>'; // translate the required language.
            },
            allday: function (schedule) {
                return schedule.title + ' <i class="fa fa-refresh"></i>';
            },
            alldayTitle: function () {
                return lllAllDay; // Translate the required language.
            },
            time: function (schedule) {
                return schedule.title + ' <i class="fa fa-refresh"></i>' + schedule.start;
            }
        },
        month: {
            dayNames: listOfDayNames, // Translate the required language.
            startDayOfWeek: 0,
            narrowWeekend: true
        },
        week: {
            dayNames: listOfDayNames, // Translate the required language.
            startDayOfWeek: 0,
            narrowWeekend: true
        }
    });
    cal.createEvents(events);

    // event handlers
    cal.on({
        'clickTimezonesCollapseBtn': function (timezonesCollapsed) {

            if (timezonesCollapsed) {
                cal.setTheme({
                    'week.daygridLeft.width': '77px',
                    'week.timegridLeft.width': '77px'
                });
            } else {
                cal.setTheme({
                    'week.daygridLeft.width': '60px',
                    'week.timegridLeft.width': '60px'
                });
            }

            return true;
        }
    });

    /**
     * Get time template for time and all-day
     * @param {Schedule} schedule - schedule
     * @param {boolean} isAllDay - isAllDay or hasMultiDates
     * @returns {string}
     */
    function getTimeTemplate(schedule, isAllDay) {
        var html = [];
        var start = moment(schedule.start.toUTCString());
        if (!isAllDay) {
            html.push('<strong>' + start.format('HH:mm') + '</strong> ');
        }
        if (schedule.isPrivate) {
            html.push('<span class="calendar-font-icon ic-lock-b"></span>');
            html.push(' Private');
        } else {
            if (schedule.isReadOnly) {
                html.push('<span class="calendar-font-icon ic-readonly-b"></span>');
            } else if (schedule.recurrenceRule) {
                html.push('<span class="calendar-font-icon ic-repeat-b"></span>');
            } else if (schedule.attendees.length) {
                html.push('<span class="calendar-font-icon ic-user-b"></span>');
            } else if (schedule.location) {
                html.push('<span class="calendar-font-icon ic-location-b"></span>');
            }
            html.push(' ' + schedule.title);
        }

        return html.join('');
    }

    /**
     * A listener for click the menu
     * @param {Event} e - click event
     */
    function onClickMenu(e) {
        var target = e.target.closest('a[role="menuitem"]');
        var action = getDataAction(target);
        var options = cal.getOptions();
        var viewLllName = target.innerText;

        var viewName = '';
        switch (action) {
            case 'toggle-daily':
                viewName = 'day';
                break;
            case 'toggle-weekly':
                viewName = 'week';
                break;
            case 'toggle-monthly':
                viewName = 'month';
                break;
            default:
                break;
        }
        cal.setOptions(options, true);
        cal.changeView(viewName, true);

        setDropdownCalendarType(viewLllName);
        setRenderRangeText();
    }

    function onClickNavi(e) {
        var action = getDataAction(e.target);

        switch (action) {
            case 'move-prev':
                cal.prev();
                break;
            case 'move-next':
                cal.next();
                break;
            case 'move-today':
                cal.today();
                break;
            default:
                return;
        }

        setRenderRangeText();
    }

    function onChangeCalendars(e) {
        var calendarId = e.target.value;
        var checked = e.target.checked;
        var viewAll = document.querySelector('.lnb-calendars-item input');
        var calendarElements = Array.prototype.slice.call(document.querySelectorAll('#calendarList input'));
        var allCheckedCalendars = true;

        if (calendarId === 'all') {
            allCheckedCalendars = checked;

            calendarElements.forEach(function (input) {
                var span = input.parentNode;
                input.checked = checked;
                span.style.backgroundColor = checked ? span.style.borderColor : 'transparent';
            });

            CalendarList.forEach(function (calendar) {
                calendar.checked = checked;
            });
        } else {
            findCalendar(calendarId).checked = checked;

            allCheckedCalendars = calendarElements.every(function (input) {
                return input.checked;
            });

            if (allCheckedCalendars) {
                viewAll.checked = true;
            } else {
                viewAll.checked = false;
            }
        }

        refreshScheduleVisibility();
    }

    function refreshScheduleVisibility() {
        var calendarElements = Array.prototype.slice.call(document.querySelectorAll('#calendarList input'));

        CalendarList.forEach(function (calendar) {
            cal.toggleSchedules(calendar.id, !calendar.checked, false);
        });

        cal.render(true);

        calendarElements.forEach(function (input) {
            var span = input.nextElementSibling;
            span.style.backgroundColor = input.checked ? span.style.borderColor : 'transparent';
        });
    }

    function setDropdownCalendarType(viewName) {
        var calendarTypeName = document.getElementById('calendarTypeName');
        var calendarTypeIcon = document.getElementById('calendarTypeIcon');
        var options = cal.getOptions();
        var type = cal.getViewName();
        var iconClassName;
        var showName = (viewName) ? viewName : calendarTypeName.dataset.default;

        if (type === 'day') {
            iconClassName = 'calendar-icon ic_view_day';
        } else if (type === 'week') {
            iconClassName = 'calendar-icon ic_view_week';
        } else {
            iconClassName = 'calendar-icon ic_view_month';
        }

        calendarTypeName.innerHTML = showName;
        calendarTypeIcon.className = iconClassName;
    }

    function currentCalendarDate(format) {
        var currentDate = moment([cal.getDate().getFullYear(), cal.getDate().getMonth(), cal.getDate().getDate()]);

        return currentDate.format(format);
    }

    function setRenderRangeText() {
        var renderRange = document.getElementById('renderRange');
        var options = cal.getOptions();
        var viewName = cal.getViewName();

        var html = [];
        if (viewName === 'day') {
            html.push(currentCalendarDate('YYYY.MM.DD'));
        } else if (viewName === 'month' &&
            (!options.month.visibleWeeksCount || options.month.visibleWeeksCount > 4)) {
            html.push(currentCalendarDate('YYYY.MM'));
        } else {
            html.push(moment(cal.getDateRangeStart().getTime()).format('YYYY.MM.DD'));
            html.push(' ~ ');
            html.push(moment(cal.getDateRangeEnd().getTime()).format(' MM.DD'));
        }
        renderRange.innerHTML = html.join('');
    }

    function setEventListener() {
        document.getElementById('menu-navi').addEventListener('click', onClickNavi);
        var nodeList = document.querySelectorAll('.dropdown-menu a[role="menuitem"]'); // returns NodeList
        var nodeArray = [...nodeList]; // converts NodeList to Array
        nodeArray.forEach((node) => {
            node.addEventListener('click', onClickMenu);
        });

        window.addEventListener('resize', resizeThrottled);
    }

    function getDataAction(target) {
        return target.dataset ? target.dataset.action : target.getAttribute('data-action');
    }

    resizeThrottled = tui.util.throttle(function () {
        cal.render();
    }, 50);

    window.cal = cal;

    setDropdownCalendarType();

    setRenderRangeText();
    // setSchedules();
    setEventListener();
    // respect order

})(window, tui.Calendar);
