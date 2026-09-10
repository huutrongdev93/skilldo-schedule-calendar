<?php
namespace ScheduleCalendar\Schedules;

use ScheduleCalendar\Supports\ScheduleCalendarHelper;
use SkillDo\Cache\Cache;
use Illuminate\Support\Facades\DB;

class ScheduleCalendarSchedules
{
    /**
     * Tiền tố cache của trang chi tiết từng module, xóa sau khi có bản ghi được xuất bản.
     */
    const CACHE_PREFIX = [
        'post'      => 'post_detail_',
        'products'  => 'product_detail_',
    ];

    static function check(): void
    {
        foreach (ScheduleCalendarHelper::modules() as $module => $model)
        {
            /*
            | withoutGlobalScope: ngoài admin, model tự ép `public = 1` vào câu
            | truy vấn — đúng lúc mình đang cần tìm những bản ghi `public = 0`.
            | Cron chạy ở ngữ cảnh nào cũng phải gỡ phạm vi này ra.
            */
            $affected = $model::withTrashed()
                ->withoutGlobalScope('setDefaultPublicColumn')
                ->where('schedule', '>', 0)
                ->where('schedule', '<=', time())
                ->where('schedule_type', '<>', -1)
                ->update([
                    'public'        => DB::raw('schedule_type'),
                    'created'       => DB::raw('FROM_UNIXTIME(schedule)'),
                    'schedule_type' => -1
                ]);

            if(!empty($affected) && isset(self::CACHE_PREFIX[$module]))
            {
                Cache::delete(self::CACHE_PREFIX[$module], true);
            }
        }
    }
}
