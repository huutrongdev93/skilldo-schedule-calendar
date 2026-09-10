<?php
namespace ScheduleCalendar\Services;

use SkillDo\Cms\Support\Option;

/**
 * Tự vá lược đồ cho site đã cài plugin từ trước.
 *
 * `active()` của plugin chỉ chạy đúng một lần trong đời site, còn bản cập nhật
 * qua nút "Cập nhật" ở màn hình Plugin thì không gọi lại nó. Site đang chạy bản
 * 2.0.x vì thế sẽ không bao giờ có 2 cột lịch đăng trên bảng `products`.
 *
 * Cách vá: mỗi lần vào admin so một option nhỏ với hằng số phiên bản dưới đây.
 * Option nằm sẵn trong cache `system` nên phép so này không tốn thêm truy vấn;
 * chỉ khi lệch mới chạm tới lược đồ.
 */
class MigrationService
{
    const OPTION = 'schedule_calendar_db_version';

    /**
     * Tăng số này mỗi khi thêm bảng/cột mới vào ActivatorService.
     */
    const VERSION = '2.1.0';

    public static function check(): void
    {
        if(Option::get(self::OPTION) === self::VERSION)
        {
            return;
        }

        ActivatorService::activate();
    }

    public static function stamp(): void
    {
        Option::update(self::OPTION, self::VERSION);
    }
}
