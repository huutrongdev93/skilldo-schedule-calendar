<?php
namespace ScheduleCalendar\Modules\Admin\Post;

use SkillDo\Cms\FormAdmin\FormAdmin;
use SkillDo\Cms\Models\Post;
use SkillDo\Cms\Table\Columns\ColumnText;

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
    public static function setValueField($object)
    {
        if(isset($object->schedule) && request()->input('post_type') == 'post')
        {
            $object->schedule = date('d/m/Y H:i:s', $object->schedule);
        }

        return $object;
    }
    public static function saveValueField($data)
    {
        if(isset($data['schedule']) && !is_numeric($data['schedule']))
        {
            $data['schedule'] = str_replace('/', '-', $data['schedule']);

            $data['schedule'] = strtotime($data['schedule']);

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
                            if(empty($item->schedule) || $item->schedule <= time())
                            {
                                if(!is_null($item->schedule_type) && $item->schedule_type != -1 && $item->public == 0)
                                {
                                    Post::where('id', $item->id)->update([
                                        'public' => $item->schedule_type,
                                        'schedule_type' => -1,
                                        'created' => date('Y-m-d H:i:s', $item->schedule)
                                    ]);
                                }
                            }

                            return (empty($item->schedule) || $item->schedule <= time()) ? 'Đã xuất bản' : carbon($item->schedule)->diffForHumans();
                        })
                ];
            }
        }
        return $columnsNew;
    }
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
}