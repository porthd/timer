// /* in the browser environment namespace */
// calendar-framework found at https://github.com/jackducasse/caleandar
// The code is imported in folder `timer\Resources\Public\Javascript\caleandar-master`

let events = [
    {'Date': new Date(2022, 10, 7), 'Title': 'Doctor appointment at 3:25pm.'},
    {'Date': new Date(2022, 10, 19), 'Title': 'New Garfield movie comes out!', 'Link': 'https://garfield.com'},
    {
        'Date': new Date(2023, 11, 24),
        'Title': '25 year anniversary',
        'Link': 'https://www.google.com.au/#q=anniversary+gifts'
    },
];

function convertListToDailyEvents(eventList) {
    let result = {};
    console.log(eventList);
    eventList.forEach((item) => {
        let helpDateString = item.Date;
        let helpDate = helpDateString.split('-');
        let startDate = new Date(helpDate[0], (helpDate[1]-1), helpDate[2]);
        startDate.setDate(startDate.getDate() - 1);
        let key = Math.floor(startDate.getTime() / 86400);
        for (let i = 1; i < item.days; i++) {
            key = Math.floor(startDate.getTime() / 86400);
            startDate.setDate(startDate.getDate() + 1);
            if (result[key]) {
                result[key].Title = result[key].Title + "\n<br >" + item.Title;
            } else {
                result[key] = {
                    'Title': item.Title,
                    'Date': new Date(startDate.getTime()),
                    'key' : key,
                }
            }
        }
    });
    console.log(result);
    let resultList =  Object.values(result);
    resultList.sort((a, b) => {
        if (a.key < b.key) return -1
        return a.key > b.key ? 1 : 0

    })
    console.log(resultList);
    return resultList;
}

let settings = {
    Color: '',
    LinkColor: '',
    NavShow: true,
    NavVertical: false,
    NavLocation: '',
    DateTimeShow: true,
    DateTimeFormat: 'mmm, yyyy',
    DatetimeLocation: '',
    EventClick: '',
    EventTargetWholeDay: false,
    DisabledDays: [],
    ModelChange: 'function'
};

let element = document.getElementById('calendar');
let eventListJson = element.dataset.events;
if (eventListJson) {
    let eventlist = JSON.parse(eventListJson);

    console.log('eventListJson');
    console.log(eventListJson);
    console.log('eventlist');
    console.log(eventlist);
    let myEvents = convertListToDailyEvents(eventlist);
    console.log('myEvents');
    console.log(myEvents);
    caleandar(element, myEvents, settings);
}