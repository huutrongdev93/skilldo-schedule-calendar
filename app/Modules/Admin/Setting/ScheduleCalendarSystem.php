<?php
namespace ScheduleCalendar\Modules\Admin\Setting;

use SkillDo\Cms\Support\Admin;

class ScheduleCalendarSystem
{
    static function register($tabs)
    {
        $tabs['schedule-calendar'] = [
            'group' => 'marketing',
            'label' => 'Lên lịch bài viết',
            'description' => 'Quản lý danh sách lên lịch bài viết',
            'callback' => [self::class, 'render'],
            'icon' => '<i class="fa-duotone fa-solid fa-calendar-days"></i>',
            'form' => false
        ];
        return $tabs;
    }

    static public function render($request, $tab): void
    {
        echo view('schedule-calendar::admin/schedule-calendar');
    }

    static public function button(): void
    {
        echo Admin::button('white', [
            'text' => 'Danh sách lên lịch',
            'href' => route('admin.system.detail', ['schedule-calendar']),
            'icon' => '<i class="fa-light fa-calendar-clock"></i>'
        ]);
    }

}