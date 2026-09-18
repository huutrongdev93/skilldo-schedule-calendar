# CLAUDE.md — Plugin schedule-calendar

File này giúp agent hiểu ngay cấu trúc plugin mà không cần scan lại source. Đọc file này TRƯỚC khi sửa bất kỳ file nào trong plugin.

## Plugin này là gì

**schedule-calendar** — hẹn giờ xuất bản **bài viết** (module `post` của core) và **sản phẩm** (module `products` của plugin sicommerce), kèm một khung lịch kéo–thả ở *Cấu hình hệ thống → Lên lịch đăng*.

- Namespace PHP: `ScheduleCalendar\*` (alias autoload trỏ về `app/`), **không có alias toàn cục**.
- Main class: `ScheduleCalendar` trong `index.php` — `active()` / `restart()` / `uninstall()`.
- Provider duy nhất: `ScheduleCalendarServiceProvider` — chỉ đăng ký một cron `everyMinute`.
- Phụ thuộc **mềm** sicommerce: chưa cài thì phần sản phẩm tự tắt (không có bảng `products`, hook `manage_products_input` không ai phát, `ScheduleCalendarHelper::modules()` bỏ qua).

## Cơ chế lưu trữ — đọc trước khi sửa bất cứ thứ gì

Mỗi bảng được hẹn giờ có thêm 2 cột (`post`, `page`, `products`):

| Cột | Ý nghĩa |
|---|---|
| `schedule` | Mốc hẹn đăng, **unix timestamp**; `0` = không hẹn |
| `schedule_type` | Giá trị `public` sẽ được **trả lại** khi tới giờ; `-1` = không có hẹn nào đang chờ |
| `public` (cột sẵn có) | Bị ép về `0` trong lúc chờ nên nội dung không hiện ngoài site |

Nghĩa là "đang chờ xuất bản" = `schedule > time() AND schedule_type <> -1`. Đừng suy ra trạng thái này từ mỗi `public = 0` — sản phẩm bị ẩn thủ công cũng có `public = 0`.

Có **ba** nơi đưa nội dung từ hàng chờ ra xuất bản, cả ba phải giữ cùng một phép biến đổi:

1. `Schedules\ScheduleCalendarSchedules::check()` — cron mỗi phút, cập nhật hàng loạt.
2. `Supports\ScheduleCalendarHelper::publishIfDue()` — chốt chặn thứ hai, chạy lúc vẽ cột "Lịch đăng" ở bảng danh sách (cron chỉ chạy khi site có lượt truy cập).
3. `Modules\Admin\Post\ScheduleCalendarPost::saveValueField()` — người dùng lưu form với mốc thời gian đã ở quá khứ.

## Map file

| File | Chức năng |
|---|---|
| `plugin.json` | Manifest. `autoload.alias` = `ScheduleCalendar` → `app/`; riêng `ScheduleCalendar\Schedules` khai PSR-4 tường minh |
| `index.php` | Class `ScheduleCalendar`: `active()` (cài lần đầu), `restart()` (bật lại — gọi lại Activator để site cũ có cột mới), `uninstall()` |
| `bootstrap/config.php` | **Registry hook duy nhất** — muốn biết plugin can thiệp vào đâu thì tra file này |
| `bootstrap/ajax.php` | 2 ajax admin: `ScheduleCalendarAjax::data` / `::update` |
| `app/Providers/ScheduleCalendarServiceProvider.php` | `Schedule::call(...)->everyMinute()` gọi `ScheduleCalendarSchedules::check()` |
| `app/Services/ActivatorService.php` | Thêm 2 cột vào `post` / `page` / `products`. **Viết theo kiểu chạy lại được** (cột đã có thì bỏ qua) |
| `app/Services/DeactivatorService.php` | Gỡ 2 cột khỏi cả ba bảng + xóa option phiên bản |
| `app/Services/MigrationService.php` | Tự vá lược đồ cho site đã cài bản cũ (xem mục Gotcha) |
| `app/Supports/ScheduleCalendarHelper.php` | Phần dùng chung: bản đồ module→model, `publishIfDue()`, `columnLabel()`, `toTimestamp()` |
| `app/Modules/Admin/Post/ScheduleCalendarPost.php` | Bài viết: ô form, cột bảng, **và 2 callback dùng chung cho mọi module** (`setValueField`, `saveValueField`, `blockPublishWhilePending`) |
| `app/Modules/Admin/Product/ScheduleCalendarProduct.php` | Sản phẩm: ô form (nhóm riêng "Lên lịch đăng") + cột bảng |
| `app/Modules/Admin/Setting/ScheduleCalendarSystem.php` | Tab *Cấu hình hệ thống → Lên lịch đăng* + nút "Danh sách lên lịch" ở đầu trang bài viết/sản phẩm + khai báo tác vụ định kỳ qua `cms_cronjob_tasks` |
| `app/Ajax/Admin/ScheduleCalendarAjax.php` | `data` (nạp sự kiện theo khoảng ngày + lọc theo loại), `update` (kéo thả đổi ngày). Hằng `TYPES` là nơi khai nhãn/màu/đường dẫn sửa của từng loại |
| `views/admin/schedule-calendar.blade.php` | FullCalendar (CDN), chú giải có checkbox lọc theo loại, template sự kiện |

## Thêm một loại nội dung mới vào lịch

Bốn chỗ, không hơn:

1. `ActivatorService::TABLES` — tên bảng, và tăng `MigrationService::VERSION`.
2. `ScheduleCalendarHelper::MODELS` — `'<module>' => <Model>::class`.
3. `ScheduleCalendarAjax::TYPES` — nhãn, icon, màu, đường dẫn trang sửa.
4. `bootstrap/config.php` — gắn `manage_{module}_input` + hook cột của module đó, và một `pre_insert_{bảng}_check`.

`ScheduleCalendarSchedules::CACHE_PREFIX` thêm tiền tố cache trang chi tiết nếu module có cache.

## Gotcha

- **`active()` chỉ chạy MỘT LẦN trong đời site.** Nút "Cập nhật" ở màn hình Plugin không gọi lại nó, nên cột mới của bản cập nhật sẽ không bao giờ tới được site đang chạy. Vì vậy có `MigrationService::check()` gắn vào `admin_init`: so `Option('schedule_calendar_db_version')` với hằng `VERSION`, lệch thì chạy lại Activator. **Thêm cột/bảng mới là phải tăng `VERSION`**, nếu không site cũ không nhận được.
- **Cron không đi qua `admin_init`.** Scheduler chạy bằng endpoint riêng `schedule-run` (nhóm api, `app/Controllers/Admin/ScheduleController.php`), do cron hệ thống gọi từ localhost. Nghĩa là có một khoảng từ lúc file mới về tới lúc admin vào trang đầu tiên, trong đó bảng chưa được vá mà cron đã chạy mã mới. Chốt chặn là `ScheduleCalendarHelper::modules()`: module nào bảng chưa có cột thì bị loại khỏi danh sách, nên cron bỏ qua thay vì ném "Unknown column".
- **Alter bảng xong phải xóa cache `table_columns_{bảng}`.** `Model::buildColumns()` dựng danh sách cột từ lược đồ thật rồi cache lại ở khóa đó; cache cũ khiến `DatabaseColumnCleaner` coi 2 cột mới là cột lạ và **loại khỏi câu INSERT/UPDATE** — triệu chứng: lưu form xong lịch đăng vẫn bằng 0, không báo lỗi gì.
- **`sets_field_before` và `save_object_before` là hook CHUNG cho mọi module**, không phải riêng bài viết. `ScheduleCalendarPost::saveValueField` vì thế phục vụ luôn sản phẩm — đừng nhân bản nó sang `ScheduleCalendarProduct`.
- **Bảng sản phẩm không dùng `up_boolean_before`.** sicommerce có ajax riêng `ProductAjax::savePublic` và ajax đó **không phát hook nào**. Chốt chặn "đang chờ thì không cho bật hiển thị" của sản phẩm nằm ở filter `pre_insert_products_check` (do chính `Model::save()` gọi), không phải ở tầng ajax.
- **Tham số thứ hai của `pre_insert_{bảng}_check` không đồng nhất ở core cũ.** `Model::save()` truyền Model, còn `Builder::create()` (thêm mới) truyền **Builder** tới hết 8.2.4 → gọi `->getAttributes()` thẳng sẽ lỗi `Call to undefined method Builder::getAttributes()`. `blockPublishWhilePending` tự đổi Builder về `getModel()`; giữ lại dòng đó chừng nào còn site chạy core cũ.
- **Cron phải gỡ global scope `setDefaultPublicColumn`.** Ngoài admin, `Post`/`Product` tự chèn `public = 1` vào truy vấn — đúng cái tập hợp mà cron đang cần loại ra. Bản cũ né bằng mẹo `->where('public', '<>', null)`; nay dùng `withoutGlobalScope('setDefaultPublicColumn')` cho rõ nghĩa.
- **Ô form bắt buộc có đủ ba:** `schedule` (datetime) + `schedule_type` (hidden, -1) + `public` (hidden, 1). Thiếu `public` thì `saveValueField` không biết phải cất giá trị nào vào `schedule_type`.
- `views/` không có file ngôn ngữ — chuỗi hiển thị viết thẳng tiếng Việt, giữ nguyên phong cách đó khi sửa.
- FullCalendar nạp từ CDN jsdelivr, **không** pin phiên bản. Site chạy trong mạng chặn CDN sẽ mất khung lịch.
