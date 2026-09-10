<?php

use ScheduleCalendar\Modules\Admin\Post\ScheduleCalendarPost;
use ScheduleCalendar\Modules\Admin\Product\ScheduleCalendarProduct;
use ScheduleCalendar\Modules\Admin\Setting\ScheduleCalendarSystem;
use ScheduleCalendar\Services\MigrationService;

/*
|--------------------------------------------------------------------------
| Vá lược đồ cho site đã cài plugin từ trước
|--------------------------------------------------------------------------
| active() chỉ chạy một lần trong đời site nên cột lịch đăng của bảng
| `products` phải được thêm ở đây. Phép so sánh nằm trong option đã cache.
*/
add_action('admin_init', [MigrationService::class, 'check']);

/*
|--------------------------------------------------------------------------
| Màn hình lịch (Cấu hình hệ thống → Lên lịch đăng)
|--------------------------------------------------------------------------
*/
add_filter('admin_system_tabs', [ScheduleCalendarSystem::class, 'register'], 20);
add_action('admin_post_post_action_bar_heading', [ScheduleCalendarSystem::class, 'button'], 20);
add_action('admin_products_action_bar_heading', [ScheduleCalendarSystem::class, 'button'], 20);

/*
|--------------------------------------------------------------------------
| Bài viết
|--------------------------------------------------------------------------
*/
add_filter('manage_post_post_input', [ScheduleCalendarPost::class, 'addField']);
add_filter('admin_post_post_controllers_index_select', [ScheduleCalendarPost::class, 'postSelect']);
add_filter('manage_post_post_columns', [ScheduleCalendarPost::class, 'columnHeader']);
add_action('up_boolean_before', [ScheduleCalendarPost::class, 'updatePublic'], 10, 3);

/*
|--------------------------------------------------------------------------
| Sản phẩm (chỉ chạy khi plugin sicommerce có mặt — hook do sicommerce phát)
|--------------------------------------------------------------------------
*/
add_filter('manage_products_input', [ScheduleCalendarProduct::class, 'addField']);
add_filter('manage_product_columns', [ScheduleCalendarProduct::class, 'columnHeader']);

/*
|--------------------------------------------------------------------------
| Đường lưu dùng chung
|--------------------------------------------------------------------------
| `sets_field_before` / `save_object_before` là hook chung cho mọi module nên
| một cặp callback phục vụ cả bài viết lẫn sản phẩm.
*/
add_filter('sets_field_before', [ScheduleCalendarPost::class, 'setValueField']);
add_filter('save_object_before', [ScheduleCalendarPost::class, 'saveValueField']);

/*
| Chặn bật hiển thị khi nội dung còn đang chờ tới giờ.
|
| Bảng sản phẩm không đi qua `up_boolean_before` mà có ajax riêng của
| sicommerce; `pre_insert_{bảng}_check` do chính Model gọi nên bắt được cả hai.
*/
add_filter('pre_insert_post_check', function ($error, $model) {
    return ScheduleCalendarPost::blockPublishWhilePending($error, $model, 'Bài viết đang ở chế độ chờ xuất bản, không thể cập nhật trạng thái hiển thị');
}, 10, 2);

add_filter('pre_insert_products_check', function ($error, $model) {
    return ScheduleCalendarPost::blockPublishWhilePending($error, $model, 'Sản phẩm đang ở chế độ chờ xuất bản, không thể cập nhật trạng thái hiển thị');
}, 10, 2);
