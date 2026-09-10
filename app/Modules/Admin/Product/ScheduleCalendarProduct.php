<?php
namespace ScheduleCalendar\Modules\Admin\Product;

use Ecommerce\Models\Product;
use ScheduleCalendar\Supports\ScheduleCalendarHelper;
use SkillDo\Cms\FormAdmin\FormAdmin;
use SkillDo\Cms\Table\Columns\ColumnText;

/**
 * Lên lịch đăng cho sản phẩm (module `products` của plugin sicommerce).
 *
 * Dùng chung đường lưu với bài viết: filter `save_object_before` của
 * ScheduleCalendarPost chạy cho mọi module nên chỉ cần form có đủ 3 ô
 * `schedule` / `schedule_type` / `public` là cơ chế hoạt động.
 */
Class ScheduleCalendarProduct
{
    public static function addField(FormAdmin $form): FormAdmin
    {
        $form->right()
            ->addGroup('schedule', 'Lên lịch đăng', 'media')
            ->addField('schedule', 'datetime', ['label' => 'Lịch đăng'])
            ->addField('schedule_type', 'hidden', ['value' => -1])
            ->addField('public', 'hidden', ['value' => 1]);

        return $form;
    }

    /**
     * Chèn cột "Lịch đăng" vào bảng danh sách sản phẩm, ngay sau cột chuyên mục.
     */
    public static function columnHeader($columns): array
    {
        $columnsNew = [];

        foreach ($columns as $key => $column)
        {
            $columnsNew[$key] = $column;

            if($key == 'categories')
            {
                $columnsNew['schedule'] = [
                    'label' => 'Lịch đăng',
                    'column' => fn($item, $args) => ColumnText::make('schedule', $item, $args)
                        ->value(function ($item)
                        {
                            ScheduleCalendarHelper::publishIfDue(Product::class, $item);

                            return ScheduleCalendarHelper::columnLabel($item);
                        })
                ];
            }
        }

        return $columnsNew;
    }
}
