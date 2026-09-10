<?php

namespace ScheduleCalendar\Providers;

use Illuminate\Support\Facades\Schedule;
use ScheduleCalendar\Schedules\ScheduleCalendarSchedules;
use SkillDo\ServiceProvider;

class ScheduleCalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Schedule::call(function () {
            ScheduleCalendarSchedules::check();
        })->everyMinute();
    }
}
