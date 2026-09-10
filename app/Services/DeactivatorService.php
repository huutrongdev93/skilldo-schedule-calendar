<?php
namespace ScheduleCalendar\Services;

use Illuminate\Database\Schema\Blueprint;
use ScheduleCalendar\Supports\ScheduleCalendarHelper;
use SkillDo\Cache\Cache;
use SkillDo\Cms\Support\Option;

Class DeactivatorService
{
    public static function uninstall(): void
    {
        foreach (ActivatorService::TABLES as $table)
        {
            if(!schema()->hasTable($table) || !schema()->hasColumn($table, 'schedule'))
            {
                continue;
            }

            schema()->table($table, function (Blueprint $blueprint)
            {
                $blueprint->dropColumn('schedule');
                $blueprint->dropColumn('schedule_type');
            });

            Cache::delete('table_columns_'.$table);
        }

        Option::delete(MigrationService::OPTION);

        ScheduleCalendarHelper::forgetModules();
    }
}
