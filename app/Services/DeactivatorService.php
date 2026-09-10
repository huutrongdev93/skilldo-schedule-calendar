<?php
namespace ScheduleCalendar\Services;

use Illuminate\Database\Schema\Blueprint;

Class DeactivatorService
{
    public static function uninstall(): void
    {
        schema()->table('post', function (Blueprint $table)
        {
            $table->dropColumn('schedule');
            $table->dropColumn('schedule_type');
        });

        schema()->table('page', function (Blueprint $table)
        {
            $table->dropColumn('schedule');
            $table->dropColumn('schedule_type');
        });
    }
}