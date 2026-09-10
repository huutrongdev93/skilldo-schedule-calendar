<?php
namespace ScheduleCalendar\Services;

use Illuminate\Database\Schema\Blueprint;

Class ActivatorService
{
    public static function activate(): void
    {
        if(!schema()->hasColumn('post', 'schedule'))
        {
            schema()->table('post', function (Blueprint $table)
            {
                $table->integer('schedule')->default(0)->after('order');
                $table->integer('schedule_type')->default(-1)->after('order');
                $table->index('schedule_type');
            });
        }

        if(!schema()->hasColumn('page', 'schedule'))
        {
            schema()->table('page', function (Blueprint $table)
            {
                $table->integer('schedule')->default(0)->after('order');
                $table->integer('schedule_type')->default(-1)->after('order');
            });
        }
    }
}