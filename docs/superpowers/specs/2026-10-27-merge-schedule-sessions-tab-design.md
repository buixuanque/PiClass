# Thiết kế: Gộp tab Lịch + Buổi học thành "Lịch học" (AD-10d/AD-10e)

**Ngày:** 2026-10-27
**Phạm vi:** `piclass-api` (Lesson) + `piclass-web` (Admin — thay AD-10d/AD-10e, ảnh hưởng nhẹ tới AD-11)
**Trạng thái:** chờ duyệt (review xong, chờ builder-check rồi tới user)

## Bối cảnh và vì sao

Phát hiện khi user hỏi "nút Hủy lịch ở tab Lịch có xóa buổi học không": không — `CancelSchedule` (tab Lịch) chỉ đổi `Schedule.status`, không đụng `LessonSession`; trong khi `CancelLessonSessionAndFollowing` (`DEC-SCH-005`, tab Buổi học) lại hủy cả hai. Hai hành vi khác nhau cho cùng một ý "không dạy lịch này nữa", vì 2 tab tách biệt (Lịch = sửa mẫu, Buổi học = sửa từng buổi) khiến admin không biết chỗ nào làm gì.

Đào sâu tiếp lộ một lỗi có sẵn khác: từ khi `DEC-SCH-004` bắt buộc `effective_until` và sinh TOÀN BỘ buổi ngay lúc tạo/sửa (không còn cron sinh dần), `PATCH /schedules/{id}` gần như vô tác dụng lên buổi đã sinh — generator chỉ lấp "phần còn thiếu", mà giờ hầu như không còn phần thiếu nào.

User chốt: dừng vá riêng từng lỗi, gộp 2 tab thành một mặt bày kiểu Google Calendar — xem lịch lặp thông qua các buổi cụ thể, không qua một danh sách mẫu riêng. Bàn chi tiết qua `superpowers:brainstorming` (2026-10-27), chốt từng điểm, ghi lại ở đây.

## Mục tiêu (Goals)

1. Một tab **"Lịch học"** duy nhất thay cho "Lịch" (AD-10d) và "Buổi học" (AD-10e), hiển thị dạng **calendar** (view Ngày/Tuần/Tháng), tái dùng component đã có ở AD-11 (`MyScheduleDayView`/`WeekView`/`MonthView`), thu hẹp về đúng một lớp.
2. Filter trên tab này: chuyển view + lướt ngày + "Hôm nay" (giữ nguyên từ AD-11); lọc theo **giáo viên của lớp**; ẩn buổi đã hủy; lọc theo **trạng thái buổi** và/hoặc **chỉ buổi cần rà soát điểm danh**.
3. "Tạo lịch lặp" và "Thêm buổi lẻ" là 2 hành động (nút) trên toolbar của tab này, mở đúng 2 form đã có (`ScheduleCreateDialog`, `SessionFormDialog` mode create) — không đổi nội dung form.
4. Bấm vào một buổi mở chi tiết; nếu buổi thuộc lịch lặp (`schedule_id != null`), hiện thêm thông tin mẫu lịch (ngày lặp, giờ, đến ngày nào, trạng thái mẫu) — đây là cách duy nhất còn lại để biết thông tin mẫu lịch (user chốt: không cần UI riêng liệt kê mẫu, "nhìn calendar là thấy hết", giống Google Calendar không có màn liệt kê recurring rule riêng).
5. Sửa hoặc hủy một buổi thuộc lịch lặp luôn hỏi phạm vi, **2 lựa chọn** (không có lựa chọn 3 "tất cả", giữ tinh thần `DEC-SCH-005`):
   - **"Chỉ buổi này"** — không đụng mẫu lịch, chỉ đổi đúng buổi đó.
   - **"Buổi này và từ nay trở đi"** — đổi mẫu lịch + mọi buổi `scheduled` từ ngày buổi đang chọn trở đi.
   Buổi lẻ (`schedule_id = null`) không hỏi, luôn xử lý như "chỉ buổi này".
6. Hủy dùng đúng 2 API đã có (`POST .../cancel`, `POST .../cancel-series`). **Sửa "và từ nay trở đi" cần một API mới** để thực sự có tác dụng lên buổi đã sinh (xem phần API).
7. Đường dẫn sâu từ AD-11 (`ScheduleAddSessionDialog` → `/admin/classes/{id}?tab=sessions&openCreate=1`) tiếp tục hoạt động đúng sau khi gộp tab.

## Non-goals

- Không đổi mô hình dữ liệu: `Schedule` và `LessonSession` vẫn là 2 bảng riêng như hiện tại — chỉ gộp giao diện, không gộp dữ liệu.
- Không làm UI riêng để xem/so sánh nhiều mẫu lịch của một lớp cùng lúc (xem mục tiêu 4 — user chốt không cần).
- Không đổi form "Tạo lịch lặp"/"Thêm buổi lẻ" hiện có (field, validate) — chỉ đổi nơi mở chúng.
- Không làm phòng/địa điểm (G13 — đã bỏ ở V1 theo quyết định trước, `admin.md`).
- Không qua bước vẽ mockup tay/`ui-ux-pro-max` trước khi code (user giao thiết kế thẳng cho Claude trong buổi `brainstorming` này) — nhưng khi code **vẫn phải đối chiếu design system** (token, component có sẵn) theo `piclass-web/CLAUDE.md`, không tự ý thêm style/token mới.
- Không đổi hành vi "chỉ buổi này" của Sửa/Hủy (vẫn đúng API hiện có) — chỉ đổi **đường vào** các form đó.

## Anti-goals

- Không để "và từ nay trở đi" (hủy hoặc sửa) đụng tới buổi đã `completed` hoặc đã `cancelled`, hoặc buổi có `occurrence_date` **trước** ngày của buổi đang chọn — giữ đúng bất biến "buổi đã diễn ra không bị viết lại" (`DEC-SCH-001`), đã áp dụng đúng cho hủy, phải áp dụng y hệt cho sửa.
- Đổi `weekdays` qua "và từ nay trở đi" không được để sót: ngày không còn khớp thứ mới phải bị hủy, ngày mới khớp thứ mới (chưa có buổi) phải được sinh thêm — không để lớp có buổi sai thứ hoặc thiếu buổi.
- Không phá đường dẫn sâu hiện có từ AD-11 vào tab buổi học của một lớp.
- Không âm thầm đổi ý nghĩa của trạng thái `Schedule` (`active`/`cancelled`/`ended`) khi hiển thị lại qua dòng phụ của buổi.

## Thiết kế UI (`piclass-web`)

### Tab "Lịch học" (thay AD-10d + AD-10e)

- Tái dùng `MyScheduleDayView`/`WeekView`/`MonthView` (`features/lesson/ui/admin/`) nhưng scoped về một lớp: bỏ filter lớp/môn của AD-11 (đã ở trong 1 lớp), giữ lại filter giáo viên (xem dưới), thêm filter trạng thái/rà soát.
- **Khoảng trống kỹ thuật để builder giải quyết, không phải quyết định nghiệp vụ:** 3 component view hiện nhận `sessions: MyLessonSession[]` (từ `/me/lesson-sessions`, có `class_name`/`subject_name`/`teacher_names`), còn tab lớp sẽ đọc từ `GET /classes/{id}/lesson-sessions` (`LessonSessionListItem[]`, không có các field đó). Hai hướng builder có thể chọn: (a) đổi 3 view sang nhận một type hẹp hơn, dùng chung (chỉ `id`, `starts_at`, `ends_at`, `status`, `review_required`, `schedule_id`), nơi gọi tự quyết định hiển thị thêm gì theo ngữ cảnh; hoặc (b) giữ 2 bộ view riêng nếu dùng chung không đáng. Khuyến nghị (a) để tránh trôi dữ liệu giữa 2 bản copy.
- Toolbar: view tabs Ngày/Tuần/Tháng + nút lướt + "Hôm nay" (giữ nguyên hành vi AD-11) · Select lọc giáo viên của lớp (options lấy từ danh sách giáo viên **đang** phân công ở tab Giáo viên/AD-10c — **quyết định (builder-check hỏi, Claude tự quyết):** không gồm giáo viên đã kết thúc phân công, cùng gap đã biết ở `GM-3`/AD-10c; không mở rộng phạm vi lần này, chấp nhận gap) · Select lọc trạng thái (Tất cả/Dự kiến/Đã hoàn tất/Đã hủy, mặc định Tất cả) · Switch "Chỉ buổi cần rà soát điểm danh" (`review_required`) · nút "Tạo lịch lặp" (mở `ScheduleCreateDialog`) · nút "Thêm buổi lẻ" (mở `SessionFormDialog` mode create).
- **Quyết định (builder-check hỏi, Claude tự quyết):** bỏ hẳn switch riêng "Ẩn buổi đã hủy" khỏi đề xuất ban đầu — nó trùng việc với Select lọc trạng thái (chọn khác "Đã hủy"/"Tất cả" là tự ẩn rồi) và tạo mơ hồ về độ ưu tiên khi cả hai cùng bật. Vì `review_required = true` trong thực tế chỉ xảy ra trên buổi `cancelled` (đặt bởi `CancelLessonSession`/`CancelsScheduleSessions` khi hủy buổi đã có điểm danh), mặc định "Tất cả" của Select trạng thái đã luôn hiện buổi cần rà soát — không còn nguy cơ "lọt" buổi cần rà soát mà không cần thêm luật ưu tiên nào giữa các control.
- Icon nhỏ (vd. 🔁) trên ô/dòng buổi có `schedule_id != null`, phân biệt với buổi lẻ.

### Chi tiết một buổi (bấm vào buổi trong calendar)

- Dialog/panel: ngày giờ, trạng thái, nội dung/ghi chú (gọi `GET /lesson-sessions/{id}` để có field này, giống hành vi hiện tại), cờ "Cần rà soát điểm danh" nếu có.
- Nếu `schedule_id != null`: thêm dòng "Thuộc lịch lặp: Thứ Ba, Thứ Năm · 17:30–19:00 · đến 30/06/2027 (Đang áp dụng/Đã hủy/Đã kết thúc)" — đọc từ `GET /schedules/{id}` (cần thêm cách FE lấy đúng Schedule, ví dụ gộp vào response chi tiết buổi hoặc gọi riêng; để builder quyết theo chi phí N+1).
- Hành động theo trạng thái buổi hiện có (giữ nguyên điều kiện cũ): Sửa (mọi trạng thái trừ quy tắc lý do bắt buộc khi `completed`, `BR-CLASS-011`), Hủy (khi chưa `cancelled`), Hoàn tất (khi `scheduled`).

### Luồng Sửa

- Buổi lẻ (`schedule_id = null`): mở trực tiếp form sửa buổi hiện có (`SessionFormDialog` mode edit → `PATCH /lesson-sessions/{id}`), không đổi.
- Buổi thuộc lịch lặp: trước khi mở form, hỏi phạm vi — dialog 2 lựa chọn giống cấu trúc `SessionCancelDialog` đã làm cho hủy (radio "Chỉ buổi này" / "Buổi này và từ nay trở đi"). **Quyết định (builder-check hỏi, Claude tự quyết):** dialog hỏi phạm vi hiện ra bất kể trạng thái của buổi đang bấm vào (`scheduled`/`completed`/`cancelled`) — cùng tiền lệ `CancelLessonSessionAndFollowing` đã triển khai (không kiểm tra trạng thái của buổi neo, chỉ quét các buổi `scheduled` từ ngày đó về sau khi áp dụng). "Chỉ buổi này" từ một buổi `completed`/`cancelled` vẫn mở đúng form sửa hiện có (áp lại đúng rule cũ: lý do bắt buộc khi `completed`). "Và từ nay trở đi" từ một buổi `completed`/`cancelled` vẫn hợp lệ — ý nghĩa là "sửa mẫu lịch áp dụng từ ngày của buổi này", không phụ thuộc buổi đó còn `scheduled` hay không.
  - **"Chỉ buổi này"** → mở đúng `SessionFormDialog` mode edit hiện có (không đổi).
  - **"Buổi này và từ nay trở đi"** → mở form có nội dung giống `ScheduleEditDialog` hiện có (giờ bắt đầu, thời lượng, các thứ, ngày kết thúc, lý do bắt buộc), nhưng gửi tới **API mới** (xem phần BE) thay vì `PATCH /schedules/{id}` cũ — vì BE cần tính lại giờ các buổi tương lai, không chỉ sửa dòng `Schedule`.

### Luồng Hủy

- Giữ nguyên `SessionCancelDialog` đã làm (2 lựa chọn, API đã có) — không đổi.

### Ảnh hưởng AD-11

- `ScheduleAddSessionDialog` điều hướng tới `/admin/classes/{id}?tab=sessions&openCreate=1`. Giữ nguyên tên giá trị `tab=sessions` trong code (chỉ là key nội bộ, không hiển thị) dù label hiển thị trên UI đổi thành "Lịch học" — tránh phải sửa đồng thời 2 nơi phát sinh cùng giá trị này.

## Thiết kế API (`piclass-api`)

### API mới: sửa buổi này và từ nay trở đi

- Route đề xuất: `PATCH /lesson-sessions/{id}/update-series` (gắn vào buổi, không gắn vào `Schedule` — nhất quán với `cancel-series`, vì "từ buổi nào" là điểm cắt).
- Request: cùng field với sửa lịch hiện có — `local_start_time?`, `duration_minutes?`, `weekdays?`, `effective_until?`, `reason` (bắt buộc).
- 422 `SESSION_HAS_NO_SCHEDULE` khi `session.schedule_id === null` (đúng mã lỗi `cancel-series` đã dùng).
- Action mới (tên đề xuất `UpdateScheduleAndFollowingSessions`, hoặc builder đặt tên khác theo quy ước), trong `DB::transaction`:
  1. Tìm `Schedule` qua `session.schedule_id`, khóa dòng (`lockForUpdate`).
  2. Cập nhật field đổi trên `Schedule` — ghi `ScheduleHistory` + audit `schedule.update`, cùng mẫu `UpdateSchedule` hiện có.
  3. Với mọi `LessonSession` cùng `schedule_id`, `occurrence_date >= ngày buổi đang chọn`, `status = scheduled`:
     - Nếu `occurrence_date` **sau** `effective_until` mới (khi `effective_until` bị rút ngắn), **hoặc** không còn khớp `weekdays` mới (khi lịch lặp) → hủy (giống `CancelsScheduleSessions`, lý do tự động nêu rõ vì sao). **Quyết định (builder-check hỏi, Claude tự quyết):** đây là bổ sung so với bản đầu — bản đầu chỉ xét lệch `weekdays`, bỏ sót trường hợp rút ngắn `effective_until` mà buổi vẫn khớp thứ (buổi đó sẽ tồn tại quá ngày kết thúc mới nếu không hủy, đi ngược đúng ý nghĩa của `effective_until`).
     - Nếu còn khớp cả hai điều kiện trên → tính lại `starts_at`/`ends_at` theo `local_start_time`/`duration_minutes` mới, giữ nguyên `occurrence_date`.
  4. Sinh thêm buổi còn thiếu cho ngày khớp `weekdays` mới chưa có buổi (tái dùng `GenerateLessonSessions`, giới hạn từ ngày buổi đang chọn tới `effective_until` mới).
  5. Audit các buổi bị đổi: để builder quyết chi tiết (một dòng audit tổng hợp, hay một dòng mỗi buổi giống `CancelsScheduleSessions` — khuyến nghị theo mẫu đó để nhất quán).
- Bất biến (xem Anti-goals): không đụng buổi trước ngày cắt, hoặc buổi đã `completed`/`cancelled`.

### API đổi: `GET /classes/{id}/lesson-sessions` thêm filter

- Thêm query tùy chọn: `teacher_id`, `status` (`scheduled`/`completed`/`cancelled`), `review_required` (boolean).
- `teacher_id`: `LessonSession` không có cột giáo viên trực tiếp — lọc nghĩa là "giáo viên nào đang phân công cho lớp tại thời điểm buổi diễn ra" (join qua `teacher_class_assignments`, điều kiện `valid_from <= starts_at` và (`valid_to IS NULL` hoặc `valid_to > starts_at`)). Để builder xác nhận lại cách join đúng khi lên plan, không phải quyết định nghiệp vụ mới (suy ra trực tiếp từ mô hình `teacher_class_assignments` đã có).
- Giữ nguyên hành vi cũ của `from`/`until` (không validate — nợ đã ghi ở `known-debt.md`), không mở rộng trong lần này trừ khi builder thấy tiện làm cùng lúc.

## Việc cần làm (tóm tắt cho plan BE/FE riêng)

- **BE:** 1 Action + Controller + Request + route mới (`update-series`); mở rộng `ListLessonSessionsQuery`/`ListLessonSessionsRequest` thêm 3 filter; export lại OpenAPI; test cho cả 2.
- **FE:** dựng lại tab "Lịch học" từ component AD-11 (xem khoảng trống kỹ thuật ở trên); dialog hỏi phạm vi trước khi Sửa (giống cấu trúc dialog hỏi phạm vi trước khi Hủy); nối "và từ nay trở đi" của Sửa sang API mới; xóa `ClassScheduleTab`/`ClassSessionsTab` cũ, cập nhật `ClassDetailScreen` (bỏ 2 tab, thêm 1 tab); cập nhật `docs/product/screens/admin.md`. **Quyết định (builder-check hỏi, Claude tự quyết):** giữ mã màn **AD-10d**, đổi tên mục thành "AD-10d — Tab Lịch học" và viết lại nội dung gộp cả 2 phần cũ vào đó; xóa hẳn mục AD-10e, để lại một dòng trỏ sang AD-10d (tránh link gãy từ chỗ khác trong docs còn trỏ tới AD-10e) — không đổi mã các tab khác (AD-10a/b/c) hay số thứ tự AD-11.
- Theo quy trình `working-flow.md`: BE và FE là 2 plan riêng, đi qua `superpowers:writing-plans` + TDD từng task, chạy `superpowers:test-driven-development` khi code.
