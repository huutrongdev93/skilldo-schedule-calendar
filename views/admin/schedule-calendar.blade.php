<script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>

<div class="box">
    <div class="box-content">
        <div class="schedule-calendar-legend mb-3 d-flex flex-wrap align-items-center gap-3">
            @foreach($types as $typeKey => $type)
                <label class="schedule-calendar-legend__item d-inline-flex align-items-center gap-2 mb-0">
                    <input type="checkbox" class="js_schedule_calendar_type" value="{{ $typeKey }}" checked>
                    <span class="schedule-calendar-legend__dot" style="background-color: {{ $type['color'] }}"></span>
                    <span>{!! $type['icon'] !!} {{ $type['label'] }}</span>
                </label>
            @endforeach
        </div>
        <div id='calendar'></div>
    </div>
</div>
<script id="template_calendar_post_event" type="text/x-custom-template">
    <div class="event-item" style="border-left: 3px solid ${typeColor}">
        <div class="event-header d-flex justify-content-between mb-1">
            <p class="mb-0">${typeIcon} ${typeLabel}</p>
            <p class="mb-0">${scheduleTime}</p>
        </div>
        <div class="event-img mb-1">
            <img src="${image}">
        </div>
        <div class="event-content mb-1">
            <p class="line-clamp-1 mb-0">${title}</p>
        </div>
        <div class="event-action">
            <a href="${slug}" target="_blank"><i class="fa-sharp fa-thin fa-pen"></i> edit</a>
        </div>
    </div>
</script>

<style>
    .fc-theme-standard table {
        border-radius: 5px;
        overflow: hidden;
    }
    .fc-theme-standard thead tr {
        background-color: var(--theme-color);
        color: #fff;
        padding: 10px;
        border:0;
    }
    .fc-theme-standard thead tr th {
        color: #fff;
        padding: 10px;
        border:0;
    }
    .fc-theme-standard thead tr th a {
        color: currentColor;
    }
    .fc-theme-standard tbody td {
        border: 1px solid #efefef;
    }

    .schedule-calendar-legend__item {
        cursor: pointer;
    }
    .schedule-calendar-legend__dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }

    .event-item {
        padding: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        margin: 5px;
        color:#161616;
    }
    .event-item .event-img {
        border-radius: 5px;
        overflow: hidden;
    }
    .event-item .event-img img {
        height: 100px;
        width: 100%;
        object-fit: cover;
    }
    .event-item .event-content {
        color:#161616;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        let calendarEl = document.getElementById('calendar');

        let typeInputs = document.querySelectorAll('.js_schedule_calendar_type');

        /* Các loại nội dung đang được tích chọn ở phần chú giải. */
        function selectedTypes() {
            let types = [];
            typeInputs.forEach(function (input) {
                if (input.checked) {
                    types.push(input.value);
                }
            });
            return types;
        }

        let calendar = new FullCalendar.Calendar(calendarEl, {
            timeZone: 'UTC+7',
            initialView: 'dayGridWeek',
            headerToolbar: {
                left: 'dayGridWeek,dayGridMonth',
                center: 'title',
                right: 'prev,next'
            },
            editable: true,
            longPressDelay: 1,
            locale: 'vi',
            buttonText: {
                today: 'Hôm nay',
                month: 'Tháng',
                week: 'Tuần',
                day: 'Ngày',
                list: 'Danh sách'
            },
            eventDrop: function(info) {
                let today = new Date();
                if (info.event.start < today) {
                    SkilldoMessage.error('Không thể kéo nội dung về quá khứ!');
                    info.revert();
                }
                else
                {
                    request.post(ajax, {
                        action: 'ScheduleCalendar\\Ajax\\Admin\\ScheduleCalendarAjax::update',
                        id: info.event.extendedProps.objectId,
                        type: info.event.extendedProps.type,
                        start: info.event.start.valueOf(),
                    })
                    .then(function (response) {
                        if (response.status === 'error') {
                            SkilldoMessage.response(response);
                            info.revert();
                        }
                    })
                }
            },
            events: function (info, successCallback, failureCallback) {
                request.post(ajax, {
                    action: 'ScheduleCalendar\\Ajax\\Admin\\ScheduleCalendarAjax::data',
                    types: selectedTypes(),
                    start: info.start.valueOf(),
                    end: info.end.valueOf()
                })
                .then(function (response) {
                    if(response.status === 'success')
                    {
                        successCallback(response.data);
                    }
                })
            },
            eventContent: function(info) {

                let html = document.getElementById('template_calendar_post_event').innerHTML

                let italicEl = document.createElement('div')

                info.event.image = info.event.extendedProps.image

                info.event.scheduleTime = info.event.extendedProps.scheduleTime

                info.event.slug = info.event.extendedProps.slug

                info.event.typeLabel = info.event.extendedProps.typeLabel

                info.event.typeIcon = info.event.extendedProps.typeIcon

                info.event.typeColor = info.event.extendedProps.typeColor

                italicEl.innerHTML = html.split(/\$\{(.+?)\}/g).map(render(info.event)).join('');

                // Bài không có ảnh đại diện (hoặc ảnh lỗi): bỏ khung ảnh thay vì để icon ảnh vỡ
                italicEl.querySelectorAll('.event-img').forEach(function (box) {
                    let img = box.querySelector('img')
                    if (!img || !img.getAttribute('src')) {
                        box.remove()
                    } else {
                        img.onerror = function () { box.remove() }
                    }
                })

                let arrayOfDomNodes = [ italicEl ]

                return { domNodes: arrayOfDomNodes }
            }
        });

        calendar.render();

        typeInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                calendar.refetchEvents();
            });
        });
    });
</script>
