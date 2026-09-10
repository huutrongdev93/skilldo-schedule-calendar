<?php
namespace ScheduleCalendar\Supports;

/**
 * Phần dùng chung giữa bài viết và sản phẩm.
 *
 * Hai module có model khác nhau nhưng cùng một quy ước lưu trữ:
 *  - `schedule`      : mốc thời gian hẹn đăng (unix timestamp, 0 = không hẹn)
 *  - `schedule_type` : giá trị `public` sẽ được trả lại khi tới giờ; -1 = không có hẹn nào đang chờ
 *  - `public`        : bị ép về 0 trong lúc chờ nên đối tượng không hiện ngoài site
 */
class ScheduleCalendarHelper
{
    /**
     * Các module có cột lịch đăng. Key = module của form/table, value = class model.
     * Sản phẩm chỉ tồn tại khi plugin sicommerce đã cài nên phải lọc qua `modules()`.
     */
    const MODELS = [
        'post'      => \SkillDo\Cms\Models\Post::class,
        'products'  => \Ecommerce\Models\Product::class,
    ];

    /**
     * Kết quả của modules(), nhớ lại trong một request.
     */
    protected static ?array $modules = null;

    /**
     * Danh sách module thật sự dùng được trên site này.
     *
     * Hai điều kiện, cả hai đều bắt buộc:
     *  1. Class model có mặt (sản phẩm chỉ có khi sicommerce đã cài).
     *  2. Bảng của model ĐÃ có 2 cột lịch đăng.
     *
     * Điều kiện 2 không thừa: sau khi cập nhật plugin, mã mới đã chạy nhưng bảng
     * `products` chưa được vá cho tới lần vào admin kế tiếp. Cron lại chạy qua
     * endpoint riêng (`schedule-run`, nhóm api) nên không đi qua `admin_init` —
     * thiếu chốt này thì mỗi phút một lỗi SQL "Unknown column 'schedule'" vào log.
     *
     * Danh sách cột lấy từ chính model: `Model::buildColumns()` dựng nó từ lược đồ
     * thật rồi cache ở khóa `table_columns_{bảng}`, nên phép kiểm tra này không
     * thêm truy vấn nào so với việc dùng model.
     */
    public static function modules(): array
    {
        if(!is_null(self::$modules))
        {
            return self::$modules;
        }

        self::$modules = [];

        foreach (self::MODELS as $module => $model)
        {
            if(!class_exists($model))
            {
                continue;
            }

            $columns = (new $model)->getColumns();

            if(!isset($columns['schedule']) || !isset($columns['schedule_type']))
            {
                continue;
            }

            self::$modules[$module] = $model;
        }

        return self::$modules;
    }

    /**
     * Quên kết quả đã nhớ — gọi sau khi vá lược đồ trong cùng một request.
     */
    public static function forgetModules(): void
    {
        self::$modules = null;
    }

    public static function model(string $module): ?string
    {
        $modules = self::modules();

        return $modules[$module] ?? null;
    }

    /**
     * Đối tượng có đang nằm trong hàng chờ xuất bản không.
     */
    public static function isPending($object): bool
    {
        return (
            !empty($object->schedule)
            && $object->schedule > time()
            && !is_null($object->schedule_type ?? null)
            && ($object->schedule_type ?? -1) != -1
        );
    }

    /**
     * Đã tới giờ mà cron chưa kịp chạy thì xuất bản ngay lúc đang xem danh sách.
     *
     * Cron `everyMinute` chỉ chạy khi site có lượt truy cập, nên bảng danh sách
     * là chốt chặn thứ hai — bản cho bài viết đã làm vậy từ 1.x, giữ nguyên hành vi.
     */
    public static function publishIfDue(string $model, $item): void
    {
        if(!empty($item->schedule) && $item->schedule > time())
        {
            return;
        }

        if(is_null($item->schedule_type ?? null) || ($item->schedule_type ?? -1) == -1)
        {
            return;
        }

        if($item->public != 0)
        {
            return;
        }

        $update = [
            'public'        => $item->schedule_type,
            'schedule_type' => -1,
        ];

        if(!empty($item->schedule))
        {
            $update['created'] = date('Y-m-d H:i:s', $item->schedule);
        }

        $model::where('id', $item->id)->update($update);

        $item->public        = $item->schedule_type;
        $item->schedule_type = -1;
    }

    /**
     * Chuỗi hiển thị ở cột "Lịch đăng" của bảng danh sách.
     */
    public static function columnLabel($item): string
    {
        if(empty($item->schedule) || $item->schedule <= time())
        {
            return 'Đã xuất bản';
        }

        return carbon($item->schedule)->diffForHumans();
    }

    /**
     * Đổi giá trị người dùng nhập ("31/12/2026 08:00:00") thành timestamp.
     */
    public static function toTimestamp($value): int
    {
        if(empty($value))
        {
            return 0;
        }

        if(is_numeric($value))
        {
            return (int)$value;
        }

        return (int)strtotime(str_replace('/', '-', $value));
    }
}
