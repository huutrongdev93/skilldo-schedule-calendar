<?php
namespace ScheduleCalendar\Schedules;

use SkillDo\Cache\Cache;
use SkillDo\Cms\Models\Post;
use Illuminate\Support\Facades\DB;

class ScheduleCalendarSchedules
{
    static function check(): void
    {
        Post::withTrashed()->where('schedule', '<=', time())
            ->where('schedule_type', '<>', -1)
            ->where('public', '<>', null)
            ->update([
                'public'        => DB::raw('schedule_type'),
                'created'       => DB::raw('FROM_UNIXTIME(schedule)'),
                'schedule_type' => -1
            ]);

        Cache::delete('post_detail_', true);
    }
}