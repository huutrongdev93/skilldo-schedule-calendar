<?php

namespace ScheduleCalendar\Providers;

use Illuminate\Support\Facades\Schedule;
use ScheduleCalendar\Modules\Admin\Setting\ScheduleCalendarSystem;
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

        /*
        | Khai báo cron trên với màn hình *Cấu hình hệ thống → Cronjob*.
        | Đặt ngay cạnh chỗ đăng ký để sửa lịch là thấy luôn dòng phải sửa theo.
        */
        add_filter('cms_cronjob_tasks', [ScheduleCalendarSystem::class, 'cronjobTask']);
    }
}
