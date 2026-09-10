<?php

use SkillDo\Support\Path;

class ScheduleCalendar
{
    public function active(): void
    {
        \ScheduleCalendar\Services\ActivatorService::activate();
    }

    public function uninstall(): void
    {
        \ScheduleCalendar\Services\DeactivatorService::uninstall();
    }
}