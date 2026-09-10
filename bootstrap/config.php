<?php

use ScheduleCalendar\Modules\Admin\Post\ScheduleCalendarPost;
use ScheduleCalendar\Modules\Admin\Setting\ScheduleCalendarSystem;

add_filter('admin_system_tabs', [ScheduleCalendarSystem::class, 'register'], 20);
add_action('admin_post_post_action_bar_heading', [ScheduleCalendarSystem::class, 'button'], 20);


add_filter('manage_post_post_input', [ScheduleCalendarPost::class, 'addField']);
add_filter('sets_field_before', [ScheduleCalendarPost::class, 'setValueField']);
add_filter('save_object_before', [ScheduleCalendarPost::class, 'saveValueField']);
add_filter('admin_post_post_controllers_index_select', [ScheduleCalendarPost::class, 'postSelect']);
add_filter('manage_post_post_columns', [ScheduleCalendarPost::class, 'columnHeader']);
add_action('up_boolean_before', [ScheduleCalendarPost::class, 'updatePublic'], 10, 3);