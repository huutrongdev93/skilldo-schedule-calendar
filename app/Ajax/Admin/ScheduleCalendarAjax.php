<?php
namespace ScheduleCalendar\Ajax\Admin;

use ScheduleCalendar\Supports\ScheduleCalendarHelper;
use SkillDo\Cms\Support\Image;
use SkillDo\Cms\Support\Url;
use SkillDo\Http\Request;

class ScheduleCalendarAjax
{
    /**
     * Cấu hình hiển thị từng loại nội dung trên lịch.
     *
     * `edit` là đường dẫn trang sửa, ghép thêm id ở dưới.
     */
    const TYPES = [
        'post' => [
            'label' => 'Bài viết',
            'icon'  => '<i class="fa-duotone fa-solid fa-books"></i>',
            'color' => '#2f6fed',
            'edit'  => 'post/edit/',
        ],
        'products' => [
            'label' => 'Sản phẩm',
            'icon'  => '<i class="fa-duotone fa-solid fa-cart-shopping"></i>',
            'color' => '#e8833a',
            'edit'  => 'products/edit/',
        ],
    ];

    static function data(Request $request): void
    {
        $start = (int)$request->input('start', 0)/1000;

        $end = (int)$request->input('end', 0)/1000;

        if(empty($start) || empty($end))
        {
            response()->error('Tham số không hợp lệ');
        }

        $start = strtotime(date('Y-m-d', $start).' 00:00:00');

        $end = strtotime(date('Y-m-d', $end).' 23:59:59');

        $types = $request->input('types');

        $types = (is_array($types) && !empty($types)) ? $types : array_keys(self::TYPES);

        $datas = [];

        foreach (ScheduleCalendarHelper::modules() as $module => $model)
        {
            if(!isset(self::TYPES[$module]) || !in_array($module, $types))
            {
                continue;
            }

            $type = self::TYPES[$module];

            $objects = $model::where('schedule_type', '<>', -1)
                ->whereBetween('schedule', [$start, $end])
                ->select('id', 'title', 'schedule', 'image', 'slug')
                ->fetch();

            if(noItems($objects))
            {
                continue;
            }

            foreach ($objects as $object)
            {
                $datas[] = [
                    "id"                => $module.':'.$object->id,
                    "objectId"          => $object->id,
                    "type"              => $module,
                    "typeLabel"         => $type['label'],
                    "typeIcon"          => $type['icon'],
                    "typeColor"         => $type['color'],
                    "title"             => $object->title,
                    "start"             => date('Y-m-d', $object->schedule),
                    "end"               => date('Y-m-d', $object->schedule),
                    "image"             => Image::source($object->image)->link(),
                    "slug"              => Url::admin($type['edit'].$object->id),
                    "scheduleTime"      => date('H:i', $object->schedule),
                    "backgroundColor"   => '#fff',
                    "borderColor"       => '#fff'
                ];
            }
        }

        response()->success('Load dữ liệu thành công', $datas);
    }

    static function update(Request $request): void
    {
        $start = (int)$request->input('start', 0);

        $id = (int)$request->input('id', 0);

        $type = (string)$request->input('type', 'post');

        $model = ScheduleCalendarHelper::model($type);

        if(empty($start) || empty($id) || empty($model))
        {
            response()->error('Tham số không hợp lệ');
        }

        $start = $start/1000;

        $object = $model::whereKey($id)->where('schedule_type', '<>', -1)->first();

        if(!have_posts($object))
        {
            response()->error('Nội dung không tồn tại hoặc không còn nằm trong hàng chờ xuất bản');
        }

        /* Kéo thả chỉ đổi NGÀY, giữ nguyên giờ đã hẹn. */
        $start = strtotime(date('Y-m-d', $start).' '.date('H:i:s', $object->schedule));

        if($start <= time())
        {
            response()->error('Không thể hẹn lịch về quá khứ');
        }

        $object->schedule = $start;

        $object->save();

        response()->success('Cập nhật dữ liệu thành công');
    }
}
