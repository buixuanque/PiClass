# Đặc tả màn hình — Cổng Teacher: vận hành lớp (TC-01…06, 14, 15)

> Phần này mô tả **nội dung, hành vi và trạng thái** của các màn hình giáo viên liên quan đến vận hành lớp học: tổng quan, lớp của tôi, chi tiết lớp, lịch dạy, buổi học và điểm danh, đơn xin phép, tiến độ học sinh, khóa học. Không bàn màu sắc hay kiểu dáng. Nguồn: `docs/product/screen-inventory.md` (mã màn hình, ký hiệu), code BE tại `piclass-api/app/Modules/{Academic,Lesson,Attendance,Audit,ResultProgress}` (Resource, Request, Action, Policy), `docs/architecture/error-codes.md`, `docs/requirements/learning-rules.md` (BR-CLASS/ATT/COURSE) và `decision-log.md` (DEC-LRN). Mã G1…G14 là khoảng trống BE đã thống nhất; **G15 là khoảng trống mới** phát hiện khi viết phần này (xem mục "Khoảng trống mới").
>
> Ký hiệu: ✅ BE đủ · 🟡 thiếu API (kèm cách ghép tạm) · 🔵 đề xuất ngoài phạm vi đã chốt · 🎨 đã có mockup mẫu.

## Mục lục

- [Mẫu chung cho cả nhóm](#mẫu-chung-cho-cả-nhóm)
- [TC-01 Tổng quan](#tc-01--tổng-quan)
- [TC-02 Lớp của tôi](#tc-02--lớp-của-tôi)
- [TC-03 Chi tiết lớp](#tc-03--chi-tiết-lớp) (tab: Học sinh · Giáo viên · Lịch · Buổi học · Bài của lớp · Tiến độ · Lịch sử thay đổi 🔵)
- [TC-04 Lịch dạy](#tc-04--lịch-dạy)
- [TC-05 Buổi học & điểm danh](#tc-05--buổi-học--điểm-danh)
- [TC-06 Đơn xin phép](#tc-06--đơn-xin-phép)
- [TC-14 Tiến độ học sinh](#tc-14--tiến-độ-học-sinh)
- [TC-15 Khóa học của tôi](#tc-15--khóa-học-của-tôi)
- [Khoảng trống mới, thay đổi so với screen-inventory, câu hỏi cần chốt](#khoảng-trống-mới-thay-đổi-so-với-screen-inventory-câu-hỏi-cần-chốt)

## Mẫu chung cho cả nhóm

Áp dụng cho mọi màn hình dưới đây, không nhắc lại từng chỗ.

- **Phạm vi dữ liệu theo phân công hiện hành.** Teacher chỉ thấy/ghi lớp mà mình đang có phân công hiệu lực (`valid_to` rỗng hoặc chưa tới). Hết phân công thì lớp biến khỏi `GET /classes`, và mở trực tiếp lớp/buổi đó trả **404 "Request failed."** (đọc) hoặc **403** (ghi). Màn hình xử lý như "không có quyền / không tìm thấy" chung (CM-07), không tiết lộ lớp có tồn tại hay không.
- **Giờ Việt Nam (+07:00).** `starts_at`/`ends_at` trả có offset +07:00. Khi gửi, giờ không kèm offset được hiểu là giờ Việt Nam; FE nên gửi dạng `2026-10-26T17:30:00+07:00`. `local_start_time` của lịch là `HH:MM:SS` giờ địa phương.
- **Ô lý do.** Hai kiểu: *bắt buộc* (nút xác nhận vô hiệu đến khi nhập; thiếu thì API trả 422 `error.details.<trường>`) và *tùy chọn*. Từng chỗ ghi rõ bên dưới. Lý do được lưu vào lịch sử/audit và (với đổi giờ, hủy) được dùng để thông báo cho người liên quan (BR-CLASS-009), nên hướng dẫn giáo viên viết ngắn gọn, người đọc là học sinh/phụ huynh.
- **Lỗi chung của nhóm Lesson:** `FORBIDDEN` (403) "Không có quyền thao tác với lớp này." (tạo lịch: "Không có quyền tạo lịch cho lớp này."; tạo buổi: "Request failed."); lớp ngoài phạm vi (404) "Request failed."; 422 theo trường.
- **Danh sách chưa phân trang** (trừ `GET /assessments`, có `per_page`, mặc định 20). Tìm/lọc trong danh sách làm ở FE (N9).
- **Hiển thị tên:** hầu hết resource chỉ trả `id`; tên môn lấy từ `GET /subjects`, tên học sinh từ `GET /classes/{id}/members`. Chỗ nào FE phải ghép thì ghi "(FE ghép)".
- **Một tài khoản một vai trò (`DEC-USR-015`, giải quyết Q6):** không có công tắc đổi vai trò. Người vừa quản trị vừa dạy dùng hai tài khoản riêng. Tài khoản Teacher chỉ thấy lớp được phân công; Admin dùng tài khoản Admin. Các màn này không cần nhóm "Giảng dạy" hay nhãn vai đang dùng.

---

### TC-01 — Tổng quan   🟡 🎨
- **Vai trò · responsive:** Teacher · responsive: ưu tiên web máy tính (mở đầu ngày dạy), vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Sáng đến trung tâm, biết ngay hôm nay dạy lớp nào mấy giờ và việc nào đang tồn đọng (bài chưa chấm, đơn xin phép chưa duyệt, buổi hôm qua chưa điểm danh).
- **Vào từ / đi tới:** trang đầu sau đăng nhập khi vai trò là Teacher. Từ thẻ "Cần xử lý": bài chờ chấm → TC-12 (bài nộp của bài đó); đơn chờ duyệt → TC-06; buổi chưa điểm danh → TC-05. Từ "Buổi dạy hôm nay/tuần này" → TC-05 (hoặc TC-04 cho lịch tuần). "Bài sắp hạn" → TC-11.
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Lời chào + ngày hôm nay* (tên từ `GET /auth/me`).
  2. *Cần xử lý* (3 ô đếm có thể bấm): số bài chờ chấm; số đơn xin phép chờ duyệt; số buổi đã qua chưa điểm danh. 🟡 G1.
  3. *Buổi dạy hôm nay*: danh sách theo giờ: giờ bắt đầu–kết thúc, tên lớp + môn, trạng thái buổi (`scheduled`/`completed`/`cancelled`), cờ `review_required`, nút "Điểm danh". 🟡 G2 (buổi chưa có tên lớp).
  4. *Lịch tuần này* dạng dải 7 ngày, mỗi ngày các buổi rút gọn (như khối 3).
  5. *Bài sắp hạn*: tên bài, lớp, hạn, số đã nộp/số được giao. 🟡 G9 (số đã nộp).
  6. *Chuyên cần trung bình* các lớp: 🟡 G3 (chưa có điểm danh theo học sinh/lớp); không vẽ số liệu giả, hiện "chưa có dữ liệu" cho đến khi có G3.
- **Chức năng & hành động:** bấm các ô/hàng để đi tới màn đích; nút làm mới (dữ liệu kéo về, không realtime). Không có thao tác ghi tại đây.
- **Trạng thái cần vẽ:** tải (khung xương từng khối, các khối tải độc lập) · rỗng toàn trang ("Bạn chưa được phân công lớp nào. Liên hệ quản trị viên", không có nút tạo lớp) · hôm nay không có buổi ("Hôm nay không có buổi dạy" kèm link TC-04 để thêm buổi bù) · không còn việc cần xử lý ("Đã xử lý hết") · lỗi từng khối có nút thử lại · mất phiên.
- **Quy tắc & lỗi nghiệp vụ:** không có lỗi nghiệp vụ riêng. "Buổi chưa điểm danh" = buổi đã qua giờ kết thúc, không `cancelled`, chưa đủ bản ghi điểm danh cho học sinh còn hiệu lực tại giờ bắt đầu buổi (xem TC-05); định nghĩa chính xác cần BE chốt khi làm G1.
- **API:** 🟡 G1 (`GET` tổng quan Teacher, chưa có). Ghép tạm (chậm, N+1): `GET /classes` → mỗi lớp `GET /classes/{id}/lesson-sessions?from=<đầu tuần>&until=<cuối tuần>` (G2); đơn chờ duyệt `GET /absence-explanations?status=submitted` (đếm ở FE); bài `GET /assessments?status=published` (phân trang, `per_page`) rồi `GET /assessments/{id}/submissions` từng bài để đếm chờ chấm; buổi chưa điểm danh: `GET /lesson-sessions/{id}/attendance` từng buổi đã qua. Với số lớp lớn không nên ghép tạm; ưu tiên dựng khung với dữ liệu mẫu.
- **Dữ liệu mẫu gợi ý:** "Chào cô Lan · Thứ Hai 26/10". Cần xử lý: 7 bài chờ chấm · 2 đơn xin phép · 1 buổi chưa điểm danh. Hôm nay: 17:30–19:00 Toán 9A (Đã lên lịch) · 19:15–20:45 Văn 8B.

### TC-02 — Lớp của tôi   ✅ (số liệu bổ sung 🟡 G9) 🎨
- **Vai trò · responsive:** Teacher · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Tìm nhanh lớp mình đang dạy và mở vào lớp đó.
- **Vào từ / đi tới:** menu "Lớp của tôi". Bấm một lớp → TC-03. **Không có nút "Tạo lớp"** (chỉ Admin được tạo; mockup mẫu cần bỏ nút này, hoặc với tài khoản có thêm vai Admin thì nút nằm ở nhóm "Quản trị", AD-09).
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Thanh lọc*: ô tìm theo tên lớp (không phân biệt dấu: BE chưa hỗ trợ nên lọc ở FE, chuẩn hóa bỏ dấu), lọc trạng thái (`preparing` Đang chuẩn bị / `active` Đang học / `archived` Đã lưu trữ), lọc môn (danh sách từ `GET /subjects`).
  2. *Danh sách lớp* (thẻ hoặc bảng), mỗi lớp: `name`, `label` (nhãn phân biệt, vd "Thứ 2-4-6"), môn (FE ghép từ `subject_id` + `GET /subjects`), `status`, `description` rút gọn. Sắp theo tên (BE đã sắp). 🟡 G9: sĩ số, giáo viên cùng dạy, buổi kế tiếp, số bài chờ chấm chưa có trong danh sách; ghép tạm cần gọi từng lớp (không khuyến nghị cho danh sách dài).
- **Chức năng & hành động:** mở lớp; lọc/tìm. Mặc định ẩn lớp `archived` (có công tắc "hiện lớp đã lưu trữ").
- **Trạng thái cần vẽ:** tải · rỗng chưa có phân công ("Bạn chưa được phân công lớp nào") · rỗng do bộ lọc ("Không có lớp khớp", nút xóa lọc) · lỗi/thử lại · lớp `preparing` (chưa vận hành, huy hiệu riêng) · lớp `archived` (chỉ xem lịch sử).
- **Quy tắc & lỗi nghiệp vụ:** chỉ lớp có phân công hiệu lực xuất hiện. Không có lỗi nghiệp vụ riêng.
- **API:** `GET /classes` → `[{id, subject_id, name, label, description, status}]` (có bao `data`; xác nhận khi dựng); `GET /subjects` (tên môn). 🟡 G9 cho số liệu bổ sung.
- **Dữ liệu mẫu gợi ý:** "Toán 9A · Thứ 2-4-6 · Toán · Đang học"; "Văn 8B · Thứ 3-5 · Ngữ văn · Đang học"; "Anh giao tiếp K12 · Cuối tuần · Tiếng Anh · Đang chuẩn bị".

### TC-03 — Chi tiết lớp   ✅ (một số khối 🟡)
- **Vai trò · responsive:** Teacher · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Xem một lớp từ đầu đến cuối: ai đang học, lịch, các buổi đã/sắp diễn ra, bài đã giao, em nào đang yếu.
- **Vào từ / đi tới:** TC-02, thông báo, TC-01. Trong trang là các tab; từ tab Buổi học → TC-05; tab Lịch → TC-04 (khung lớp này); tab Bài → TC-11; tab Tiến độ → TC-14; tab Học sinh → TC-14 (bấm tên). Mọi thao tác thêm/bớt học sinh, phân công giáo viên, sửa thông tin lớp **không có** ở cổng này.
- **Khối nội dung & dữ liệu hiển thị:**
  - *Đầu trang*: `name`, `label`, môn (FE ghép), `status`, `description` (`GET /classes/{id}`). 🟡 G13: địa điểm/phòng học chưa có trường nào; chừa chỗ, không vẽ giá trị giả.
  - *Tab Học sinh (chỉ xem)*: `GET /classes/{id}/members` → `student_id`, `full_name`, `valid_from`, `valid_to`. Học sinh còn hiệu lực ở trên; đã rời lớp (`valid_to` có giá trị, đã qua) gom thành nhóm "Đã rời lớp" thu gọn, kèm ngày. Không có nút thêm/xóa/sửa (DEC-LRN-003); nếu cần, ghi chú "Liên hệ quản trị viên". Ô tìm theo tên. Không hiển thị email/điện thoại (không có trong API).
  - *Tab Giáo viên*: `GET /classes/{id}/teachers` → `teacher_id`, `full_name`, `valid_from`, `valid_to`. Chỉ xem.
  - *Tab Lịch*: `GET /classes/{id}/schedules` → `id`, `status` (`active`/`cancelled`), `effective_from`, `effective_until`, `local_start_time`, `duration_minutes`, `repeat_weekly`, `weekdays` (1–7). Hiển thị "Thứ 2, 4, 6 · 17:30–19:00 · từ 05/10". Nút "Quản lý lịch" → TC-04.
  - *Tab Buổi học*: `GET /classes/{id}/lesson-sessions` → `id`, `starts_at`, `ends_at`, `status`, `review_required`; chia "Sắp tới" và "Đã diễn ra" (FE chia theo giờ hiện tại). Bấm dòng → TC-05. Lọc theo tháng bằng `from`/`until`.
  - *Tab Bài của lớp*: `GET /assessments?class_id=<id>` (phân trang) → danh sách bài giao cho lớp (cột như TC-09: tên, loại, trạng thái, hạn); bấm → TC-11. 🟡 G9: số đã nộp/chờ chấm.
  - *Tab Tiến độ*: bảng học sinh × (điểm trung bình, số bài đã chấm, số bài chờ chấm) lấy từ `GET /students/{id}/progress?class_id=<id>` cho từng học sinh (FE gọi N lần; chậm với lớp đông, 🟡 G9 nếu cần endpoint theo lớp). Cột chuyên cần 🟡 G3. Bấm hàng → TC-14.
  - *Tab Lịch sử thay đổi* 🔵 (xem dưới).
- **Chức năng & hành động:** chuyển tab (nhớ tab cuối); tìm học sinh; điều hướng. Trong lớp `archived`: ẩn các nút thêm buổi/lịch ở TC-04, chỉ xem. Lớp `preparing`: cho phép xem; ghi chú "Lớp chưa bắt đầu vận hành".
- **🔵 Tab "Lịch sử thay đổi" (đề xuất):** Teacher gọi được `GET /audit-logs` và BE tự giới hạn ở lớp họ dạy (`correlation_key` = id lớp). Hiển thị theo thứ tự mới nhất trước: thời điểm (`occurred_at`), hành động (`action`: dịch sang tiếng Việt, vd `lesson_session.update` → "Sửa buổi học", `lesson_session.cancel` → "Hủy buổi học", `schedule.update/cancel`, `attendance_record.create/update` → "Điểm danh/Sửa điểm danh"), đối tượng (`entity_type`), lý do (`reason`), thu gọn xem trước/sau (`before_data`/`after_data`). **Người thực hiện chỉ có `actor_user_id` (chưa có tên)** nên hiện "Người dùng #…" hoặc ẩn cột đến khi BE trả tên; **không phân trang, không lọc ngày/người** (G5): FE lọc theo `action`/`entity_type` bằng tham số API (`entity_type`, `entity_id`, `action`) và theo ngày ở FE. Giá trị nghiệp vụ: giáo viên (và Admin khi kiểm tra) biết ai đã đổi giờ/điểm danh và vì sao, giải quyết khiếu nại của phụ huynh. Ngoài `requirements/*` (BR-CLASS-011 chỉ yêu cầu lưu dấu vết, không yêu cầu màn xem) nên đánh dấu 🔵, cần chốt trước khi làm.
- **Trạng thái cần vẽ:** tải theo tab · rỗng từng tab (chưa có học sinh: "Quản trị viên chưa thêm học sinh"; chưa có lịch: "Chưa có lịch lặp, thêm ở TC-04"; chưa có buổi; chưa giao bài: nút "Giao bài" → TC-10 với lớp này sẵn chọn) · lỗi · không có quyền/hết phân công (404 "Request failed.") · lớp lưu trữ.
- **Quy tắc & lỗi nghiệp vụ:** 404 "Request failed." khi lớp ngoài phạm vi; không có `error.code` riêng.
- **API:** `GET /classes/{id}`, `/members`, `/teachers`, `/schedules`, `/lesson-sessions`, `GET /assessments?class_id=`, `GET /students/{id}/progress?class_id=`, `GET /audit-logs` 🔵. Thiếu: G3 (chuyên cần), G9 (số liệu gộp), G13 (địa điểm), G5 (audit).
- **Dữ liệu mẫu gợi ý:** Toán 9A, 28 học sinh: Nguyễn Minh Khôi, Trần Thu Hà, Lê Gia Bảo; đã rời: Phạm Đức Anh (đến 30/09). Giáo viên: Cô Lan (từ 01/09).

### TC-04 — Lịch dạy   ✅ (lịch nhiều lớp 🟡 G2) 🎨
- **Vai trò · responsive:** Teacher · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Xếp và chỉnh lịch dạy: mở lịch cố định hàng tuần cho một lớp, thêm một buổi lẻ hoặc buổi học bù, dời hoặc hủy một buổi khi bận, và báo cho học sinh biết lý do.
- **Vào từ / đi tới:** menu "Lịch dạy" (xem mọi lớp mình dạy) hoặc tab Lịch/Buổi học trong TC-03 (một lớp). Bấm một buổi → drawer chi tiết → "Điểm danh" tới TC-05.
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Thanh điều khiển*: chuyển Tuần/Tháng; mũi tên trước/sau + "Hôm nay"; bộ lọc lớp (nhiều lớp); nút **"Thêm buổi"** và **"Tạo lịch lặp"** (chọn lớp trước nếu đang xem tất cả).
  2. *Lưới lịch tuần/tháng*: mỗi buổi: giờ `starts_at`–`ends_at`, tên lớp (FE ghép), chip trạng thái (`scheduled` dự kiến · `completed` hoàn tất · `cancelled` đã hủy, gạch ngang), cờ `review_required` ("Cần rà soát điểm danh"). Buổi sinh từ lịch lặp và buổi lẻ trông như nhau (API list không nói buổi thuộc lịch nào). 🟡 G2: gọi theo `GET /classes/{id}/lesson-sessions?from&until` từng lớp rồi hợp; buổi chưa có tên lớp nên FE gắn theo lớp đã gọi.
  3. *Drawer chi tiết buổi* (`GET /lesson-sessions/{id}`): giờ, trạng thái, `lesson_content` (**hiển thị cho học sinh và phụ huynh**, ghi rõ dưới ô nhập), `notes` (ghi chú; G12: chưa chốt có công khai không nên đặt nhãn trung tính, ví dụ "Ghi chú buổi học", và cảnh báo giáo viên cho đến khi chốt), lý do hủy nếu đã hủy (BE lưu nhưng **GET không trả `cancellation_reason`**, xem mục khoảng trống).
  4. *Danh sách lịch lặp của lớp* (panel hoặc tab): các mẫu `schedules` như TC-03, với nút Sửa/Hủy từng mẫu.
- **Chức năng & hành động:**
  - **Thêm buổi lẻ / buổi bù** (modal): chọn lớp, ngày, giờ bắt đầu, giờ kết thúc (`ends_at` phải sau `starts_at`), nội dung buổi (tùy chọn), ghi chú (tùy chọn). **Không có ô lý do** (BE không nhận). Nhãn "Buổi bù" chỉ là gợi ý ở UI, BE không có cờ riêng. `POST /classes/{id}/lesson-sessions`. Chặn lớp `archived`. Không cảnh báo trùng lịch ở buổi lẻ (BE chỉ kiểm tra khi tạo lịch lặp); nếu muốn FE tự kiểm tra trùng với các buổi đã tải thì ghi rõ là cảnh báo mềm.
  - **Tạo lịch lặp** (modal): ngày bắt đầu hiệu lực `effective_from` (bắt buộc), ngày kết thúc `effective_until` (tùy chọn, ≥ ngày bắt đầu), giờ bắt đầu `local_start_time` (HH:MM → gửi HH:MM:SS), thời lượng `duration_minutes` (số phút ≥ 1; UI cho chọn 45/60/90/120 hoặc nhập), công tắc `repeat_weekly`, chọn thứ `weekdays` (1–7; bắt buộc khi lặp hàng tuần; quy ước 1=Thứ Hai … 7=Chủ nhật cần xác nhận khi dựng, chưa thấy ghi trong tài liệu). Sau khi tạo, hệ thống **sinh ngay buổi cho 4 tuần tới**; hiển thị thông báo "Đã tạo lịch và N buổi trong 4 tuần tới" (số buổi suy từ danh sách tải lại). Phản hồi trả `schedule` + `warnings`: mỗi cảnh báo `{type: "teacher_schedule_conflict", conflicting_schedule_id, class_id}` nghĩa là giáo viên đang có lịch lặp khác (lớp `class_id`) trùng thứ và giờ. **Chỉ cảnh báo, không chặn:** lịch vẫn đã được tạo. Hiển thị banner vàng sau khi tạo: "Trùng với lịch lớp <tên lớp> (thứ…, giờ…)" kèm nút "Giữ lịch" và "Sửa lịch" (mở form sửa mẫu vừa tạo) / "Hủy lịch này" (cần lý do). Cảnh báo chỉ tính cho lịch lặp hàng tuần và chỉ lúc tạo (sửa lịch không kiểm tra lại).
  - **Sửa lịch lặp:** đổi giờ bắt đầu, thời lượng, thứ, ngày kết thúc; **ô lý do bắt buộc** (`reason`). Nói rõ trên UI: chỉ ảnh hưởng buổi chưa sinh (hệ thống sinh thêm phần còn thiếu trong 4 tuần tới), **buổi đã sinh không tự đổi** (BR-CLASS-007); muốn đổi buổi đã sinh thì sửa từng buổi. `PATCH /schedules/{id}`. Người liên quan được thông báo.
  - **Hủy lịch lặp:** ô lý do bắt buộc (`cancel_reason`), xác nhận. `POST /schedules/{id}/cancel`. Cảnh báo trước xác nhận: "Các buổi đã sinh vẫn còn; hủy từng buổi nếu không dạy" kèm đường tắt chọn nhiều buổi (nếu làm, gọi hủy tuần tự từng buổi).
  - **Sửa một buổi** (dời giờ, đổi nội dung/ghi chú): ô lý do **tùy chọn** với buổi `scheduled`, **bắt buộc** với buổi `completed` (BE kiểm tra: buổi hoàn tất mà thiếu `reason` thì 422). Quy tắc nghiệp vụ BR-CLASS-011: mọi lần sửa đều được lưu lịch sử; UI nên khuyến khích nhập lý do kể cả khi tùy chọn, đặc biệt khi đổi giờ (học sinh/phụ huynh được thông báo khi có đổi `starts_at`/`ends_at`, không phụ thuộc có lý do hay không). `PATCH /lesson-sessions/{id}` (chỉ gửi trường thay đổi). Ẩn nút sửa giờ với buổi đã hủy.
  - **Hủy một buổi:** ô lý do **bắt buộc** (`cancellation_reason`); xác nhận. Nếu buổi đã có điểm danh: cảnh báo "Điểm danh đã ghi vẫn được giữ, buổi sẽ được đánh dấu cần rà soát" (xem `review_required` ở TC-05). `POST /lesson-sessions/{id}/cancel`. Ẩn với buổi đã hủy.
  - **Hoàn tất buổi:** nút "Đánh dấu hoàn tất" (không có ô nhập, không cần lý do); `POST /lesson-sessions/{id}/complete`. BE không kiểm tra trạng thái trước nên FE phải ẩn nút với buổi `cancelled`/`completed` và khuyến khích điểm danh trước khi hoàn tất (không bắt buộc ở BE).
- **Trạng thái cần vẽ:** tải · tuần/tháng trống ("Chưa có buổi nào trong tuần này", nút Thêm buổi / Tạo lịch lặp) · chưa được phân công lớp · lỗi · modal có lỗi 422 theo trường (`ends_at`, `weekdays`, `reason`, `cancellation_reason`...) · banner cảnh báo trùng lịch · buổi đã hủy (mờ, hiện lý do) · buổi có `review_required` · lớp lưu trữ (nút ghi bị khóa).
- **Quy tắc & lỗi nghiệp vụ:** 422 `reason` / `cancellation_reason` / `cancel_reason` "Bắt buộc nhập lý do"; 422 `ends_at` phải sau `starts_at`; 422 `weekdays` khi lặp hàng tuần mà chưa chọn thứ; 403 `FORBIDDEN` "Không có quyền thao tác với lớp này." (hết phân công giữa chừng) → báo và tải lại; 404 "Request failed.". Trùng lịch giáo viên là **cảnh báo** (`warnings`), không phải lỗi.
- **API:** `GET /classes/{id}/lesson-sessions?from&until`, `GET /lesson-sessions/{id}`, `POST /classes/{id}/lesson-sessions`, `PATCH /lesson-sessions/{id}`, `POST …/cancel`, `POST …/complete`, `GET/POST /classes/{id}/schedules`, `PATCH /schedules/{id}`, `POST /schedules/{id}/cancel`. 🟡 G2 (lịch tổng hợp nhiều lớp, buổi kèm tên lớp), G13 (địa điểm). 🟡 G15b (xem mục khoảng trống: GET không trả `cancellation_reason`, `lesson_content`/`notes` không có trong danh sách).
- **Dữ liệu mẫu gợi ý:** Lịch lặp Toán 9A: Thứ 2-4-6, 17:30, 90 phút, từ 05/10/2026. Buổi bù: Văn 8B, Chủ nhật 01/11, 08:00–09:30, nội dung "Ôn tập chương 2". Cảnh báo: "Trùng với Toán 9B, Thứ 4 17:00–18:30". Lý do hủy: "Giáo viên bị ốm, sẽ dạy bù vào Chủ nhật."

### TC-05 — Buổi học & điểm danh   ✅ (một phần 🟡 G15) 🎨
- **Vai trò · responsive:** Teacher (và Admin) · responsive: dùng tốt trên **máy tính bảng** (thao tác nhanh ở đầu buổi), vẫn dùng được trên máy tính và điện thoại.
- **Người dùng muốn làm gì:** Đầu buổi, điểm danh cả lớp nhanh; cuối buổi ghi nội dung đã dạy, rồi hoàn tất. Khi nhầm, sửa lại và ghi lý do.
- **Vào từ / đi tới:** TC-01 (buổi hôm nay, buổi chưa điểm danh), TC-04 (drawer buổi), TC-03 (tab Buổi học). Từ đây: về TC-04/TC-03; "Đơn xin phép của buổi này" → TC-06 (lọc theo buổi).
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Đầu trang buổi*: tên lớp + môn (FE ghép), `starts_at`–`ends_at`, chip `status`; nếu `cancelled`: banner "Buổi đã hủy" (+ lý do, xem khoảng trống); nếu `review_required = true`: banner "Buổi này đã hủy sau khi có điểm danh. Hãy rà soát lại các bản ghi" (BR-CLASS-010). `GET /lesson-sessions/{id}`.
  2. *Nội dung & ghi chú*: ô `lesson_content` (**học sinh/phụ huynh sẽ thấy**; nhãn rõ) và `notes` (G12: chưa chốt công khai; đặt nhãn "Ghi chú" và cảnh báo giáo viên đến khi chốt); nút Lưu → `PATCH /lesson-sessions/{id}` (lý do bắt buộc nếu buổi `completed`, như TC-04).
  3. *Bảng điểm danh*: mỗi học sinh một dòng: tên (FE ghép từ `GET /classes/{id}/members`, **chỉ học sinh hiệu lực tại giờ bắt đầu buổi**: `valid_from ≤ starts_at` và `valid_to` rỗng hoặc `> starts_at`), trạng thái hiện tại (`present` Có mặt · `late` Đi muộn · `absent` Vắng · `excused` Có phép; hoặc "Chưa điểm danh" nếu chưa có bản ghi), `note`, huy hiệu "Có đơn xin phép" (nếu `GET /absence-explanations` có đơn cho học sinh+buổi này). Nguồn: `GET /lesson-sessions/{id}/attendance` → `[{student_id, full_name, status, note}]` (chỉ học sinh đã có bản ghi; học sinh chưa có thì FE tự thêm từ roster).
  4. *Bộ đếm*: có mặt/muộn/vắng/có phép/chưa điểm danh (FE tính).
  5. *Lịch sử sửa* (tab hoặc drawer theo từng dòng) 🟡: các lần sửa điểm danh với trước/sau, người sửa, thời điểm, lý do. BE ghi `AttendanceHistory` và audit `attendance_record.update`, nhưng chỉ lộ qua `GET /audit-logs?entity_type=AttendanceRecord` (Teacher thấy log lớp mình; `entity_id` là id bản ghi nên phải biết id → G15; hoặc tải cả rồi lọc `correlation_key` = id lớp ở FE). Cũng dùng `entity_type=LessonSession&entity_id=<id buổi>` cho lịch sử sửa giờ/nội dung/ghi chú buổi. Người sửa chỉ có `actor_user_id` (G5). Đây là 🔵 về mặt màn hình (BR-ATT-003/BR-CLASS-011 chỉ yêu cầu lưu).
- **Chức năng & hành động:**
  - **Đặt hàng loạt:** nút "Tất cả có mặt" đặt trạng thái cho mọi dòng **chưa có bản ghi**; sau đó chỉnh từng em (chọn nhanh 4 trạng thái, ghi chú tùy chọn). Nút "Lưu điểm danh" gửi `PUT /lesson-sessions/{id}/attendance` với `records: [{student_id, status, note?}]` (≥ 1 dòng). Yêu cầu giao diện: phải chọn trạng thái cho mọi học sinh trước khi cho lưu (nghiệp vụ lớp học, BE chỉ yêu cầu ≥ 1 bản ghi). **Quan trọng:** `PUT` chỉ **tạo mới** bản ghi cho học sinh chưa có; **không ghi đè** bản ghi đã tồn tại (vì sửa phải có lý do). Do đó với học sinh đã có bản ghi, UI chuyển sang chế độ "Sửa" (bên dưới), và sau khi lưu hàng loạt phải tải lại.
  - **Sửa từng bản ghi:** bấm dòng đã điểm danh → chọn trạng thái mới, ghi chú, và **ô lý do bắt buộc** (`reason`, vd "Em đến muộn 10 phút, nhập nhầm là vắng"). `PATCH /attendance-records/{id}` (`status` bắt buộc, `note` tùy chọn, `reason` bắt buộc). Vẫn sửa được ở buổi đã hủy (để rà soát) và không khóa theo ngày (BR-ATT-002). 🟡 **G15:** GET attendance **không trả `id` của bản ghi** (chỉ `student_id`, `full_name`, `status`, `note`), trong khi PATCH cần id; `PUT` cũng chỉ trả `student_id`, `status`. Cần BE thêm `id` (và nên thêm `recorded_at`) mới làm được thao tác sửa; không có cách ghép tạm đáng tin.
  - **Xử lý `review_required`:** sau khi rà soát, BE chưa có thao tác bỏ cờ (không thấy endpoint); hiện chỉ hiển thị. Cần xác nhận khi dựng xem sửa bản ghi có tự xóa cờ không (chưa thấy trong `UpdateAttendanceRecord`): coi là 🟡 và ghi vào câu hỏi cần chốt.
  - **Hoàn tất buổi:** `POST /lesson-sessions/{id}/complete` (xác nhận; nếu còn học sinh chưa điểm danh, cảnh báo mềm).
  - **Hủy buổi:** như TC-04 (lý do bắt buộc).
  - Buổi đã hủy: khóa nút "Lưu điểm danh" và "Tất cả có mặt", hiện lý do; vẫn cho xem và sửa bản ghi có sẵn.
- **Trạng thái cần vẽ:** tải · lớp rỗng ("Không có học sinh nào hiệu lực ở buổi này") · chưa điểm danh · đã điểm danh một phần · đã lưu thành công (toast + đếm) · buổi `cancelled` · buổi `cancelled` có `review_required` · buổi `completed` (sửa nội dung cần lý do) · lỗi lưu giữ nguyên dữ liệu đã chọn · mất kết nối · không có quyền.
- **Quy tắc & lỗi nghiệp vụ:**
  - `SESSION_CANCELLED` (409): "Buổi học đã hủy, không thể điểm danh." Không có ngoại lệ Admin. Chỉ áp cho `PUT` hàng loạt; hiển thị bằng banner trên cùng, khóa lưu (BE kiểm tra sau phân quyền và validate, trước `STUDENT_NOT_ELIGIBLE`).
  - `STUDENT_NOT_ELIGIBLE` (422): "Một số học viên không thuộc lớp này tại thời điểm buổi học." kèm `error.details.student_ids` (danh sách id vi phạm). **Cả lô bị từ chối, không ghi bản ghi nào.** UI: đánh dấu đỏ đúng các dòng có `student_id` trong danh sách (tên lấy từ roster), thông báo "N học sinh không còn trong lớp vào thời điểm này", cho nút "Bỏ các em này khỏi lô rồi lưu lại" (giữ lựa chọn còn lại). Nguyên nhân thường gặp: học sinh vào lớp sau giờ buổi học hoặc đã rời lớp trước giờ đó (roster FE phải lọc đúng như mô tả ở khối 3 để hạn chế).
  - 422 theo trường: `records.0.status` (trạng thái không hợp lệ), `records` (rỗng), `reason` (thiếu khi sửa bản ghi), `records.*.student_id` (không tồn tại).
  - 403 `FORBIDDEN` / 404 "Request failed." như mẫu chung.
- **API:** `GET /lesson-sessions/{id}`, `PATCH /lesson-sessions/{id}`, `GET/PUT /lesson-sessions/{id}/attendance`, `PATCH /attendance-records/{id}`, `POST …/complete`, `POST …/cancel`, `GET /classes/{id}/members`, `GET /absence-explanations`, `GET /audit-logs` 🔵. 🟡 G15 (id bản ghi), G12 (quyền xem `notes`/`review_required`), G5 (audit).
- **Dữ liệu mẫu gợi ý:** Toán 9A · 17:30–19:00 · Thứ Hai 26/10. Nguyễn Minh Khôi (Có mặt), Trần Thu Hà (Đi muộn, "Muộn 10 phút"), Lê Gia Bảo (Vắng, có đơn xin phép chờ duyệt). Lỗi: "Phạm Đức Anh không còn trong lớp vào giờ này".

### TC-06 — Đơn xin phép   ✅ (hiển thị đủ thông tin 🟡 G9)
- **Vai trò · responsive:** Teacher (và Admin) · responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Đọc lý do phụ huynh xin cho con nghỉ và quyết định chấp nhận hoặc từ chối, để điểm danh phản ánh đúng.
- **Vào từ / đi tới:** menu "Đơn xin phép" (có huy hiệu số đơn chờ), thẻ "Cần xử lý" ở TC-01, thông báo "có đơn xin phép mới". Từ dòng đơn → TC-05 của buổi đó.
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Tab/lọc trạng thái*: Chờ duyệt (`submitted`, mặc định) · Đang xem xét (`under_review`) · Đã chấp nhận (`accepted`) · Đã từ chối (`rejected`) · Tất cả. Lọc `status` hỗ trợ ở API; lọc `student_id` cũng có. Lọc theo lớp ở FE.
  2. *Danh sách đơn* (Teacher chỉ thấy đơn của buổi thuộc lớp mình dạy): `message` (lý do phụ huynh viết), `status`, `student_id`, `lesson_session_id`. **Thiếu trong API:** tên học sinh, tên phụ huynh, lớp, giờ buổi học, thời điểm gửi. FE ghép: tên học sinh từ roster (`GET /classes/{id}/members`, cần biết lớp), giờ + lớp từ `GET /lesson-sessions/{id}` (lớp qua `class_id`), điểm danh hiện tại của em đó ở buổi ấy từ `GET /lesson-sessions/{id}/attendance`. Cách ghép gọi nhiều lần, nên đề xuất BE làm giàu response (G9): `student_name`, `class_name`, `session.starts_at`, `submitted_at`, `attendance_status`.
  3. *Chi tiết đơn (panel bên phải)*: nội dung đầy đủ, trạng thái điểm danh hiện tại của buổi, ghi chú duyệt cũ (`review_note`, **không có trong danh sách**, chỉ trả sau khi duyệt; xem khoảng trống).
- **Chức năng & hành động:**
  - **Chấp nhận** / **Từ chối** (hai nút; có thể thêm "Đang xem xét" để ghi nhận đã đọc): `PATCH /absence-explanations/{id}` với `status` ∈ {`under_review`, `accepted`, `rejected`}, `review_note` tùy chọn (khuyến khích khi từ chối: "Cần giấy xác nhận của trường"). Xác nhận bằng hộp thoại ngắn kèm ô ghi chú. Phụ huynh được thông báo kết quả.
  - **Chấp nhận KHÔNG tự đổi điểm danh** (DEC-LRN-008). Sau khi chấp nhận, hiện gợi ý: "Đổi điểm danh của em thành Có phép?" → mở TC-05 hoặc modal sửa bản ghi với lý do điền sẵn "Phụ huynh xin phép, đã chấp nhận" (`PATCH /attendance-records/{id}`, bắt buộc `reason`; cần G15 để lấy id). Nếu em chưa có bản ghi điểm danh thì dẫn tới TC-05 để điểm danh trước.
  - Được đổi quyết định đã đưa ra (BE không chặn): cho phép nút "Đổi quyết định" ở đơn đã duyệt.
- **Trạng thái cần vẽ:** tải · rỗng chờ duyệt ("Không có đơn nào chờ duyệt") · rỗng cho bộ lọc · lỗi · đơn của buổi đã hủy (hiện cảnh báo, nút duyệt vẫn dùng được) · đã chấp nhận / đã từ chối (kèm ghi chú) · đang xử lý (nút vô hiệu) · hết phân công (đơn biến khỏi danh sách).
- **Quy tắc & lỗi nghiệp vụ:** 422 `status` không hợp lệ; 403/404 "Request failed." khi đơn ngoài phạm vi; không có `error.code` riêng. Học sinh (vai Student) không gọi được endpoint này.
- **API:** `GET /absence-explanations?status=&student_id=` → `[{id, student_id, lesson_session_id, message, status}]`; `PATCH /absence-explanations/{id}` → `{absence_explanation: {id, status, review_note}}`. 🟡 G9 (làm giàu), G15 (id bản ghi điểm danh).
- **Dữ liệu mẫu gợi ý:** "Lê Gia Bảo · Toán 9A · 26/10 17:30 · 'Con bị sốt, xin phép cho con nghỉ buổi hôm nay.' · Chờ duyệt".

### TC-14 — Tiến độ học sinh   ✅ (điểm danh và biểu đồ 🟡 G3, G8) 🎨
- **Vai trò · responsive:** Teacher (và Admin) · responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Xem một em học thế nào để nói chuyện với phụ huynh hoặc quyết định phụ đạo: điểm trung bình, bài còn nợ, đi học đều không.
- **Vào từ / đi tới:** bấm học sinh ở TC-03 (tab Học sinh/Tiến độ). Quay lại lớp; bấm một bài → TC-13 (chỉ khi có API nhận diện bài, hiện chưa).
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Đầu trang*: tên học sinh (từ roster), lớp đang xem, bộ lọc: lớp (`class_id`), môn (`subject_id`), khóa học (`course_id`), khoảng ngày (`from`, `to`; lọc theo ngày nhận bài).
  2. *Số tổng quan*: `average_percentage` (điểm trung bình %, có thể `null` nếu chưa có bài công bố), `graded_count` (số bài đã công bố điểm), `pending_count` (số bài chưa chấm xong). `GET /students/{id}/progress`.
  3. *Theo môn*: `by_subject[]` → `subject_name` ("Khác" cho bài giao không qua lớp/khóa), `average_percentage`, `graded_count`.
  4. *Biểu đồ điểm theo thời gian* và *bài gần đây* (tên bài, ngày, %): 🟡 G8 (API chỉ trả trung bình/số bài/theo môn). Vẽ khối với trạng thái "đang chờ dữ liệu", không bịa.
  5. *Điểm danh 10 buổi gần nhất + tỷ lệ chuyên cần*: 🟡 G3. Ghép tạm: duyệt buổi đã qua của lớp (`GET /classes/{id}/lesson-sessions`) rồi `GET /lesson-sessions/{id}/attendance` từng buổi, lọc theo `student_id` (chậm; chỉ làm cho 10 buổi gần nhất).
- **Chức năng & hành động:** đổi bộ lọc; chuyển học sinh trước/sau trong cùng lớp (tiện duyệt cả lớp). Chỉ xem, không có thao tác ghi.
- **Trạng thái cần vẽ:** tải · rỗng chưa có bài nào được công bố (`average_percentage = null`: "Chưa có điểm. Có N bài đang chờ chấm") · lỗi · không có quyền/em không thuộc phạm vi (404) · điểm danh chưa có dữ liệu (G3).
- **Quy tắc & lỗi nghiệp vụ:** chỉ tính kết quả đã **công bố**; bài bị hủy, bài bị vô hiệu không tính; nhiều lượt làm của cùng một bài chỉ tính một lần theo cách tính điểm của bài (cao nhất/mới nhất). 404 "Request failed." nếu Teacher không phụ trách em đó (không phải lớp nào của Teacher chứa em).
- **API:** `GET /students/{id}/progress?class_id&course_id&subject_id&from&to` → `{average_percentage, graded_count, pending_count, by_subject:[{subject_id, subject_name, average_percentage, graded_count}]}` (không bao `data`). 🟡 G3, G8.
- **Dữ liệu mẫu gợi ý:** Nguyễn Minh Khôi · Toán 9A: TB 82,5% · 6 bài đã chấm · 1 bài chờ chấm; theo môn: Toán 85%, Khác 70%.

### TC-15 — Khóa học của tôi   ✅ (số liệu bổ sung 🟡)
- **Vai trò · responsive:** Teacher · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Quản lý nhóm bài tự học riêng của mình (ví dụ "Luyện đề vào 10"): ai được học, ai đã kết thúc; đồng thời xem các khóa dùng chung của trung tâm.
- **Vào từ / đi tới:** menu "Khóa học". Mở một khóa → trang chi tiết (cùng màn, tab Ghi danh · Bài). "Giao bài cho khóa" dẫn tới TC-10 (đối tượng là khóa). Nội dung khóa **chính là các bài đánh giá gán qua khóa** (BR-COURSE-002): tab Bài dùng `GET /assessments?course_id=<id>` → TC-11.
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Danh sách khóa* (`GET /courses`, sắp theo tên): `name`, `description`, `status` (`active`/`archived`), và **nhãn nguồn**: *"Của tôi"* nếu `owner_teacher_id` = hồ sơ Teacher của mình (id lấy ở `GET /me/profile`/`GET /auth/me`), *"Dùng chung"* nếu `owner_teacher_id` = null. Hai nhóm tách tab hoặc cột. Môn: `subject_id` có trong chi tiết (`GET /courses/{id}`), danh sách không trả `subject_id`.
  2. *Chi tiết khóa*: `name`, `description`, môn, `status`; **khóa của tôi**: nút Sửa tên/mô tả/môn, "Lưu trữ"; **khóa dùng chung**: chỉ xem (nhãn "Do quản trị viên quản lý"), ẩn mọi nút sửa/ghi danh/kết thúc ghi danh; **không xem được danh sách ghi danh của khóa chung** (BE trả 403 cho Teacher; DEC-LRN-012), hiện thông báo "Chỉ quản trị viên xem được học sinh ghi danh".
  3. *Tab Ghi danh (khóa của tôi)*: `GET /courses/{id}/enrollments` → `student_id`, `status` (`active`/`ended`), `valid_from`, `valid_to`; tên học sinh FE ghép (từ roster các lớp của mình; học sinh ngoài lớp mình dạy chưa có cách tra tên → G4). Mặc định hiện đang ghi danh, công tắc hiện đã kết thúc.
  4. *Tab Bài của khóa*: như trên.
- **Chức năng & hành động:**
  - **Tạo khóa:** form `name` (bắt buộc), `description`, `subject_id` (tùy chọn). Khóa tạo ra luôn thuộc về mình (Teacher không chọn chủ). `POST /courses`.
  - **Sửa khóa** (của tôi): `PATCH /courses/{id}`; vẫn sửa được khi đã lưu trữ.
  - **Lưu trữ khóa:** xác nhận ("Khóa lưu trữ không nhận ghi danh mới; các ghi danh hiện có vẫn xem được"). `PATCH` với `status: "archived"` (chỉ giá trị này hợp lệ, không có "bỏ lưu trữ").
  - **Ghi danh theo học sinh** (chọn một học sinh từ lớp mình dạy; xem ghi chú dưới) hoặc **theo lớp** (chọn lớp: ghi danh toàn bộ học sinh đang học của lớp tại thời điểm đó). Gửi **một trong hai** `student_id` hoặc `class_id` (không gửi cả hai). Kết quả `created_count` = số ghi danh mới thật sự tạo (học sinh đã ghi danh bị bỏ qua, có thể 0): hiển thị "Đã ghi danh 24 học sinh (4 em đã có từ trước)" nếu tính được. Lưu ý: ghi danh theo lớp là **ảnh chụp tại thời điểm bấm**, học sinh vào lớp sau không tự được ghi danh. `POST /courses/{id}/enrollments`. Teacher được ghi danh học sinh/lớp bất kỳ, không bị giới hạn bởi lớp mình dạy (BR-COURSE-004); danh sách chọn học sinh ngoài lớp mình 🟡 G4.
  - **Kết thúc ghi danh** một học sinh: **ô lý do bắt buộc** (`end_reason`), xác nhận; `DELETE /courses/{id}/enrollments/{studentId}` (body JSON `end_reason`; cần xác nhận khi dựng vì DELETE kèm body).
- **Trạng thái cần vvẽ:** tải · rỗng chưa có khóa ("Bạn chưa có khóa nào. Tạo khóa đầu tiên", cùng danh sách khóa chung nếu có) · khóa chưa có ghi danh · khóa chưa có bài · khóa `archived` (huy hiệu, nút ghi danh bị khóa, hiện lý do) · khóa dùng chung (chỉ xem) · lỗi · không có quyền (403 khi xem ghi danh khóa chung; 404 khi khóa không còn trong phạm vi).
- **Quy tắc & lỗi nghiệp vụ:** `COURSE_ARCHIVED` (422): "Course đã lưu trữ, không thể ghi danh thêm." → hiển thị rõ, vô hiệu nút Ghi danh (sửa tên/mô tả, xem và kết thúc ghi danh vẫn được). 422 `student_id`/`class_id` ("chọn một trong hai"); 422 `end_reason` thiếu; 404 "Request failed." khi kết thúc ghi danh của học sinh không còn ghi danh hiệu lực (hai người cùng thao tác); 403 "Request failed." khi sửa/ghi danh khóa chung hoặc khóa của người khác.
- **API:** `GET/POST /courses`, `GET/PATCH /courses/{id}`, `GET/POST /courses/{id}/enrollments`, `DELETE /courses/{id}/enrollments/{studentId}`, `GET /assessments?course_id=`. 🟡 G9 (số học sinh/số bài mỗi khóa trong danh sách), G4 (chọn học sinh ngoài lớp mình).
- **Dữ liệu mẫu gợi ý:** "Luyện đề vào 10 (Của tôi) · Đang mở · 24 học sinh"; "Ôn thi tốt nghiệp THPT (Dùng chung, Admin quản lý)". Lý do kết thúc ghi danh: "Học sinh chuyển sang nhóm khác."

---

## Khoảng trống mới, thay đổi so với screen-inventory, câu hỏi cần chốt

**Khoảng trống BE mới (ngoài G1…G14):**
- **G15 — Id bản ghi điểm danh:** `GET /lesson-sessions/{id}/attendance` và `PUT` không trả `id` của bản ghi, nhưng `PATCH /attendance-records/{id}` cần id. Chưa có cách sửa từng bản ghi, chưa tra được lịch sử sửa theo bản ghi. Đề xuất thêm `id` (và `lesson_session_id`) vào `AttendanceEntryResource`/`MarkedAttendanceResource`.
- **G15b — Thiếu trường trong response buổi học:** `GET /lesson-sessions/{id}` không trả `cancellation_reason` (BE lưu), danh sách buổi không có `lesson_content`/`notes` và không nói buổi có thuộc lịch lặp nào (`schedule_id`) hay là buổi lẻ/bù.
- **Đơn xin phép nghèo thông tin (TC-06):** summary không có `review_note`, `submitted_at`, tên học sinh/phụ huynh, giờ buổi. Gộp vào G9.
- **Không có thao tác bỏ cờ `review_required`:** chưa thấy endpoint hay hành vi tự bỏ cờ sau khi rà soát; cần BE chốt.

**Thay đổi so với `screen-inventory.md`:**
- TC-01 giữ 🟡 (G1). TC-02: bỏ nút "Tạo lớp" cho Teacher. TC-03: thêm tab "Lịch sử thay đổi" 🔵 và tách các tab; "tiến độ từng học sinh" phải gọi N lần (G9). TC-05: đánh dấu 🟡 vì G15 (thao tác sửa từng bản ghi chưa dựng được). TC-06: 🟡 một phần (hiển thị). TC-14: điểm trung bình/số bài/theo môn ✅, biểu đồ & bài gần đây G8, điểm danh G3. TC-15: tách "Của tôi" và "Dùng chung" (chỉ xem), thêm hành vi `COURSE_ARCHIVED`.
- `BR-ATT-004` nói "trừ khi Admin ghi nhận ngoại lệ", nhưng code `SESSION_CANCELLED` không có ngoại lệ Admin; theo code. `Teacher không xem được danh sách ghi danh của Course dùng chung` (DEC-LRN-012) nên màn hình ẩn khối này.

**Câu hỏi cần user chốt:**
1. Có làm tab "Lịch sử thay đổi" (🔵) cho Teacher không, và hiển thị người sửa bằng tên (cần BE trả tên, G5)?
2. `notes` của buổi học có công khai cho học sinh/phụ huynh (G12)? Đặt nhãn UI thế nào?
3. Quy ước `weekdays`: 1 = Thứ Hai hay Chủ nhật? Lịch `repeat_weekly = false` có sinh buổi không? (cần xác nhận khi dựng)
4. ~~Sửa điểm danh của buổi `review_required` có tự bỏ cờ không?~~ **Hạ ưu tiên (2026-10-28, `DEC-LRN-016`):** không hủy được buổi đã qua `ends_at` nên cờ chỉ còn với buổi đang diễn ra bị hủy và dữ liệu cũ; chưa làm thao tác bỏ cờ ở V1, mở lại nếu thực tế còn gặp.
5. Hủy lịch lặp có nên đi kèm hủy hàng loạt các buổi đã sinh không (hiện BE không đụng tới buổi đã sinh)?
6. Chấp nhận đơn xin phép có nên tự gợi ý đổi điểm danh sang "Có phép" (đề xuất UI ở TC-06) hay giữ hai bước tách biệt hoàn toàn?
