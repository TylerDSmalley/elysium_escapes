"use strict";

/*
   Author:     Tyler Smalley
   Date:       2021-09-14
   Filename:   trCalendar.js  
*/

let thisDay = new Date();

document.getElementById('calendar').innerHTML = makeCalendar(thisDay);


function makeCalendar(calDate) {
    let specialsCalendar = "<table id='calendar_table'>";
    specialsCalendar += calendarMonthTitle(calDate);
    specialsCalendar += calendarWeekDayColumns();
    specialsCalendar += calendarDays(calDate);
    specialsCalendar += '</table>';

    return specialsCalendar;
}

function calendarMonthTitle(calDate) {
    let monthName = [
        'January', 'February', 'March',
        'April', 'May', 'June',
        'July', 'August', 'September',
        'October', 'November', 'December'
    ];

    let currentMonth = calDate.getMonth();
    let currentYear = calDate.getFullYear();
    let calCaption = monthName[currentMonth] + ' ' + currentYear;
    return '<caption>' + calCaption + '</caption>';
}

function calendarWeekDayColumns() {
    let dayArray = [
        'SUN', 'MON', 'TUES', 'WED', 'THURS', 'FRI', 'SAT'
    ];

    let rowElement = '<tr>';


    for (let dayIndex = 0; dayIndex < dayArray.length; dayIndex++) {
        rowElement += "<th class='calendar_weekdays'>" +
            dayArray[dayIndex] + '</th>';
    }
    rowElement += '</tr>';
    return rowElement;
}

function daysInMonth(calDate) {
    let dayCount = [
        31, 28, 31,
        30, 31, 30,
        31, 31, 30,
        31, 30, 31
    ];

    let thisMonth = calDate.getMonth();
    let thisYear = calDate.getFullYear();

    // leap year check
    if (thisYear % 4 === 0) {
        if ((thisYear % 100 != 0) || (thisYear % 400 === 0)) {
            dayCount[1] = 29;
        }
    }

    return dayCount[thisMonth];
}

function calendarDays(calDate) {
    let today = new Date(calDate.getFullYear(), calDate.getMonth(), 1);
    let weekDay = today.getDay();

    let trElements = '<tr>';
    for (let countBlank = 0; countBlank < weekDay; countBlank++) {
        trElements += '<td>&nbsp;</td>';
    }

    let totalDays = daysInMonth(calDate);

    let highlightDay = calDate.getDate();

    for (let days = 1; days <= totalDays; days++) {
        today.setDate(days);
        weekDay = today.getDay();

        if (weekDay === 0) {
            trElements += '<tr>';
        }
        if (days === highlightDay) {
            trElements += "<td class='calendar_dates' id='calendar_today'>" + days +
                dayEvent[days] + '</td>';
        } else {
            trElements += "<td class='calendar_dates'>" + days +
                dayEvent[days] + '</td>';
        }
        if (weekDay === 6) {
            trElements += '</tr>';
        }
    }
    return trElements;
}