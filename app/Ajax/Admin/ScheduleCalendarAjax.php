<?php
namespace ScheduleCalendar\Ajax\Admin;

use SkillDo\Cms\Models\Post;
use SkillDo\Cms\Support\Image;
use SkillDo\Http\Request;

class ScheduleCalendarAjax
{
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

        $posts = Post::where('schedule_type', '<>', -1)
            ->whereBetween('schedule', [$start, $end])
            ->select('id', 'title', 'schedule', 'image','slug')
            ->fetch();

        $datas = [];

        foreach ($posts as $post) {
            $datas[] = [
                "id" => $post->id,
                "title" => $post->title,
                "start" => date('Y-m-d', $post->schedule),
                "end" => date('Y-m-d', $post->schedule),
                "image" => Image::source($post->image)->link(),
                "slug" => 'admin/post/edit/'.$post->id,
                "scheduleTime" => date('H:s', $post->schedule),
                "backgroundColor" => '#fff',
                "borderColor" => '#fff'
            ];
        }

        response()->success('Load dữ liệu thành công', $datas);
    }
    static function update(Request $request): void
    {
        $start = (int)$request->input('start', 0);

        $id = (int)$request->input('id', 0);

        if(empty($start) || empty($id))
        {
            response()->error('Tham số không hợp lệ');
        }

        $start = $start/1000;

        $post = Post::whereKey($id)->where('schedule_type', '<>', -1)->first();

        if(!have_posts($post))
        {
            response()->error('Bài viết không tồn tại');
        }

        $start = strtotime(date('Y-m-d', $start).' '.date('H:i:s', $post->schedule));

        $post->schedule = $start;

        $post->save();

        response()->success('Cập nhật dữ liệu thành công');
    }
}