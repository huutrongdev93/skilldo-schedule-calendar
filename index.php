<?php

class ScheduleCalendar
{
    public function active(): void
    {
        \ScheduleCalendar\Services\ActivatorService::activate();
    }

    /**
     * Chạy khi bật lại một plugin ĐÃ từng cài.
     *
     * `active()` chỉ chạy đúng một lần trong đời site nên cột mới của bản cập
     * nhật sẽ không bao giờ tới được site đang chạy nếu thiếu bước này.
     * ActivatorService viết theo kiểu chạy lại được: cột đã có thì bỏ qua.
     */
    public function restart(): void
    {
        \ScheduleCalendar\Services\ActivatorService::activate();
    }

    public function uninstall(): void
    {
        \ScheduleCalendar\Services\DeactivatorService::uninstall();
    }
}
