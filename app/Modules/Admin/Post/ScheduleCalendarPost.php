<?php
namespace ScheduleCalendar\Modules\Admin\Post;

use ScheduleCalendar\Supports\ScheduleCalendarHelper;
use SkillDo\Cms\FormAdmin\FormAdmin;
use SkillDo\Cms\Models\Post;
use SkillDo\Cms\Table\Columns\ColumnText;
use SkillDo\Support\SKD_Error;

Class ScheduleCalendarPost
{
    public static function addField(FormAdmin $form): FormAdmin
    {
        $form->right()
            ->group('taxonomies')
            ->addField('schedule', 'datetime', ['label' => 'Lịch đăng'])
            ->addField('schedule_type', 'hidden', ['value' => -1])
            ->addField('public', 'hidden', ['value' => 1]);

        return $form;
    }

    /**
     * Đổi timestamp trong DB thành chuỗi cho ô datetime.
     *
     * Hook `sets_field_before` chỉ nhận đối tượng chứ không nhận module, nên
     * điều kiện đặt ở chính dữ liệu: có cột `schedule` thì mới đụng vào. Trang
     * nào không có ô lịch đăng trong form (ví dụ trang tĩnh) thì giá trị này
     * cũng không được đọc tới.
     */
    public static function setValueField($object)
    {
        if(!isset($object->schedule))
        {
            return $object;
        }

        $object->schedule = (empty($object->schedule) || !is_numeric($object->schedule))
            ? ''
            : date('d/m/Y H:i:s', $object->schedule);

        return $object;
    }

    /**
     * Chạy cho MỌI module (bài viết lẫn sản phẩm) — `save_object_before` là hook chung.
     *
     * Hẹn giờ trong tương lai thì cất giá trị `public` người dùng chọn vào
     * `schedule_type` rồi ép `public = 0`; tới giờ thì trả ngược lại.
     */
    public static function saveValueField($data)
    {
        if(!isset($data['schedule']) || is_numeric($data['schedule']))
        {
            return $data;
        }

        $data['schedule'] = ScheduleCalendarHelper::toTimestamp($data['schedule']);

        if($data['schedule'] > time())
        {
            if($data['schedule_type'] == -1)
            {
                $data['schedule_type'] = $data['public'];
            }

            $data['public'] = 0;
        }
        else
        {
            if($data['schedule_type'] != -1)
            {
                $data['public'] = $data['schedule_type'];

                $data['schedule_type'] = -1;
            }
        }

        return $data;
    }

    public static function postSelect($select) {
        $select[] = 'schedule';
        return $select;
    }

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
                            ScheduleCalendarHelper::publishIfDue(Post::class, $item);

                            return ScheduleCalendarHelper::columnLabel($item);
                        })
                ];
            }
        }

        return $columnsNew;
    }

    /**
     * Chặn bật/tắt nhanh cột hiển thị ở bảng danh sách bài viết.
     */
    public static function updatePublic($params): void
    {
        if(((!empty($params['table']) && $params['table'] == 'post') || (!empty($params['module']) && $params['module'] == 'post')) && $params['row'] == 'public')
        {
            if($params['object']->schedule > time())
            {
                response()->error(trans('Bài viết đang ở chế độ chờ xuất bản không thể cập nhật trạng thái hiển thị'));
            }
        }
    }

    /**
     * Chốt chặn chung cho mọi đường ghi đi qua Model::save().
     *
     * Bảng sản phẩm không dùng `up_boolean_before` mà có ajax riêng
     * (`ProductAjax::savePublic`) và ajax đó không phát hook nào. Filter
     * `pre_insert_{table}_check` do chính Model gọi nên bắt được cả hai.
     *
     * Chỉ chặn đúng thao tác "bật hiển thị" rời rạc: bản ghi gửi lên có `public`
     * bằng 1 và KHÔNG kèm `schedule` (đường lưu form luôn gửi kèm lịch đăng và
     * đã tự quyết định giá trị `public` ở saveValueField).
     */
    public static function blockPublishWhilePending($error, $model, string $message)
    {
        if(is_skd_error($error))
        {
            return $error;
        }

        $attributes = $model->getAttributes();

        if(!isset($attributes['public']) || (int)$attributes['public'] !== 1)
        {
            return $error;
        }

        if(array_key_exists('schedule', $attributes))
        {
            return $error;
        }

        $id = (int)($attributes[$model->getPrimaryKey()] ?? 0);

        if(empty($id))
        {
            return $error;
        }

        $current = \Illuminate\Support\Facades\DB::table($model->getTable())
            ->where($model->getPrimaryKey(), $id)
            ->select('schedule', 'schedule_type')
            ->first();

        if(empty($current) || empty($current->schedule) || $current->schedule <= time())
        {
            return $error;
        }

        if(is_null($current->schedule_type) || $current->schedule_type == -1)
        {
            return $error;
        }

        return new SKD_Error('error', $message);
    }
}
