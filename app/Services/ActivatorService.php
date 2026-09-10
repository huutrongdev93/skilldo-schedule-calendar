<?php
namespace ScheduleCalendar\Services;

use Illuminate\Database\Schema\Blueprint;
use ScheduleCalendar\Supports\ScheduleCalendarHelper;
use SkillDo\Cache\Cache;

Class ActivatorService
{
    /**
     * Các bảng được bổ sung 2 cột lịch đăng.
     *
     * `products` là bảng của plugin sicommerce — site không bán hàng thì không có
     * bảng này, nên phải kiểm tra `hasTable` chứ không alter thẳng.
     */
    const TABLES = ['post', 'page', 'products'];

    /**
     * Viết theo kiểu chạy lại được: cột đã có thì bỏ qua. Nhờ vậy `restart()` và
     * bước tự vá lược đồ (MigrationService) đều gọi lại được mà không hỏng gì.
     */
    public static function activate(): void
    {
        foreach (self::TABLES as $table)
        {
            self::addColumns($table);
        }

        MigrationService::stamp();

        // Bảng vừa đổi thì danh sách module đã nhớ trước đó không còn đúng.
        ScheduleCalendarHelper::forgetModules();
    }

    public static function addColumns(string $table): void
    {
        if(!schema()->hasTable($table) || schema()->hasColumn($table, 'schedule'))
        {
            return;
        }

        schema()->table($table, function (Blueprint $blueprint)
        {
            $blueprint->integer('schedule')->default(0)->after('order');
            $blueprint->integer('schedule_type')->default(-1)->after('order');
            $blueprint->index('schedule_type');
        });

        /*
        | Model dựng danh sách cột từ lược đồ thật và cache lại ở khóa này
        | (Model::buildColumns). Không xóa thì 2 cột vừa thêm bị coi là cột lạ
        | và bị DatabaseColumnCleaner loại khỏi câu INSERT/UPDATE — lưu form
        | xong lịch đăng vẫn bằng 0.
        */
        Cache::delete('table_columns_'.$table);
    }
}
