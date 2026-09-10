<?php
namespace ScheduleCalendar\Modules\Admin\Setting;

use ScheduleCalendar\Ajax\Admin\ScheduleCalendarAjax;
use ScheduleCalendar\Supports\ScheduleCalendarHelper;
use SkillDo\Cms\Support\Admin;

class ScheduleCalendarSystem
{
    static function register($tabs)
    {
        $tabs['schedule-calendar'] = [
            'group' => 'marketing',
            'label' => 'Lên lịch đăng',
            'description' => 'Quản lý lịch đăng bài viết và sản phẩm',
            'callback' => [self::class, 'render'],
            'icon' => '<i class="fa-duotone fa-solid fa-calendar-days"></i>',
            'form' => false
        ];
        return $tabs;
    }

    static public function render($request, $tab): void
    {
        /*
        | Chỉ đưa ra lịch những loại nội dung site này thật sự có: sản phẩm biến
        | mất khỏi phần chú giải khi chưa cài sicommerce.
        */
        $types = array_intersect_key(
            ScheduleCalendarAjax::TYPES,
            ScheduleCalendarHelper::modules()
        );

        echo view('schedule-calendar::admin/schedule-calendar', ['types' => $types]);
    }

    static public function button(): void
    {
        echo Admin::button('white', [
            'text' => 'Danh sách lên lịch',
            'href' => route('admin.system.detail', ['schedule-calendar']),
            'icon' => '<i class="fa-light fa-calendar-clock"></i>'
        ]);
    }

    /**
     * Khai báo tác vụ định kỳ cho màn hình *Cấu hình hệ thống → Cronjob*.
     *
     * Nhãn liệt kê đúng những loại nội dung site này thật sự hẹn giờ được, để
     * site chưa cài sicommerce không hiện chữ "sản phẩm" gây hiểu nhầm.
     */
    static public function cronjobTask($tasks): array
    {
        $labels = array_intersect_key(
            ['post' => 'bài viết', 'products' => 'sản phẩm'],
            ScheduleCalendarHelper::modules()
        );

        $tasks['schedule-calendar'] = [
            'label'       => 'Xuất bản nội dung đã hẹn giờ',
            'description' => 'Tới giờ đã hẹn thì cho '.(empty($labels) ? 'nội dung' : implode(' và ', $labels)).' hiện ra ngoài site. Không có cronjob thì nội dung nằm chờ mãi.',
            'source'      => 'Plugin Lên lịch đăng',
            'interval'    => 'Mỗi phút',
        ];

        return $tasks;
    }
}
