# Đặc tả màn hình — Cổng Student và Parent (ST, PR)

> **Phạm vi:** nội dung, hành vi và trạng thái của 11 màn hình Student (ST-01…09 cùng hai màn tách thêm ST-06b, ST-07b) và 8 màn hình Parent (PR-01…08). Không bàn màu sắc, kiểu dáng. Nguồn: `docs/product/screen-inventory.md` (mã màn hình, ký hiệu), `piclass-api` (route, Resource, Query, Policy, Action), `docs/requirements/*` (`BR-`/`DEC-`), `docs/architecture/error-codes.md`. Ký hiệu ✅🟡🔵🎨 và mã khoảng trống G1…G14 theo `screen-inventory.md`; các khoảng trống phát hiện thêm khi đọc code đặt tên **N-A…N-E** ở cuối file, chưa nằm trong danh sách G.
>
> **Responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính.** Hai cổng này là nơi học sinh làm bài và phụ huynh xem tình hình; cả hai thường mở bằng điện thoại nhưng cũng hay dùng web máy tính ở nhà. Mỗi màn hình phải dùng tốt ở khổ hẹp và khổ rộng, không mất chức năng nào khi thu nhỏ.

## Mục lục

- [Quy ước chung của hai cổng](#quy-ước-chung-của-hai-cổng)
- [Student vs Parent thấy gì](#student-vs-parent-thấy-gì)
- [Cổng Student](#cổng-student): [ST-01](#st-01--tổng-quan) · [ST-02](#st-02--lịch-học) · [ST-03](#st-03--bài-tập) · [ST-04](#st-04--bài-kiểm-tra) · [ST-05](#st-05--chi-tiết-bài) · [ST-06](#st-06--làm-bài) · [ST-06b](#st-06b--đã-nộp-bài) · [ST-07](#st-07--kết-quả) · [ST-07b](#st-07b--xem-lại-bài-làm) · [ST-08](#st-08--tiến-độ-học-tập) · [ST-09](#st-09--khóa-học-của-em)
- [Cổng Parent](#cổng-parent): [PR-01](#pr-01--chọn-con) · [PR-02](#pr-02--tổng-quan-của-con) · [PR-03](#pr-03--lịch-học-của-con) · [PR-04](#pr-04--điểm-danh-của-con) · [PR-05](#pr-05--bài-tập--kết-quả-của-con) · [PR-06](#pr-06--nhận-xét) · [PR-07](#pr-07--tiến-độ-của-con) · [PR-08](#pr-08--đơn-xin-phép--giải-trình-vắng)
- [Khoảng trống phát hiện thêm (N-A…N-E)](#khoảng-trống-phát-hiện-thêm)
- [Câu hỏi cần user chốt](#câu-hỏi-cần-user-chốt)

---

## Quy ước chung của hai cổng

- **Nhãn trạng thái bài của học sinh** (`status` trong `GET /me/assessments`, giá trị `assigned`/`withdrawn`/`completed`) **không đủ** để vẽ chip "chưa làm / chờ chấm / đã chấm". FE tự suy ra: `assigned` và chưa có lượt = *Chưa làm*; `assigned` và đang làm dở = *Đang làm* (biết được chỉ khi giữ `attempt.id` cục bộ, xem N-E); `completed` và chưa có kết quả công bố (`GET …/result` 404) = *Chờ chấm / chờ công bố*; `completed` và có kết quả = *Đã có điểm*; `withdrawn` hoặc `assessment_status = cancelled` = *Đã hủy*; `assessment_status = closed` mà chưa làm = *Đã đóng, chưa nộp*. Bài `draft` không bao giờ xuất hiện với học sinh/phụ huynh. Cách suy ra này ghép từ nhiều request, nên chốt trạng thái ở mức màn hình danh sách là việc của FE (và là lý do của N-A).
- **Ngữ cảnh "đang xem con nào" (Parent):** `studentId` của con là một **phần của đường dẫn** mọi màn hình Parent (`/parent/children/{studentId}/…`), không chỉ nằm trong state. Hệ quả: mở lại link, nút Back, tải lại trang vẫn đúng con; thông báo trỏ vào bài/buổi của con nào thì mở đúng con đó. Con vừa chọn được nhớ cục bộ (lưu trình duyệt, mất cũng không sao) để lần sau vào thẳng. Khung điều hướng luôn hiện **tên con đang xem** ở đầu màn hình, chạm vào để mở trình chuyển con (danh sách từ `GET /me/children`). Phụ huynh một con: bỏ qua PR-01, không hiện trình chuyển (chỉ hiện tên con).
- **Mất quyền xem con ngay lập tức:** liên kết đã kết thúc (Admin kết thúc `parent-student-relationship`) thì mọi API `…/children/{studentId}/…` trả 404 ngay, thông báo "Không tìm thấy." hoặc "Request failed." (xem `error-codes.md`). Màn hình nào nhận 404 trên một `studentId` đã chọn phải: bỏ con đó khỏi bộ nhớ cục bộ, xóa dữ liệu con đang hiển thị trong bộ nhớ đệm FE, báo "Bạn không còn liên kết với học sinh này" và chuyển về PR-01 (hoặc về màn hình trống nếu không còn con nào).
- **Chỉ hiện phần đã công bố:** điểm và nhận xét chỉ có khi giáo viên đã công bố (`BR-ASSESS-028`). Màn hình không bao giờ đoán/hiển thị điểm "tạm" lấy từ response nộp bài (`POST …/submit` trả cả `result` kể cả chưa công bố — **không hiển thị `score` từ đó**).
- **Roster lớp:** Student/Parent không xem danh sách bạn cùng lớp (`BR-CLASS-014`, `DEC-LRN-009`). Không có màn hình/khối nào liệt kê học sinh khác; điểm danh một buổi chỉ trả bản ghi của chính mình/các con.
- **Thông tin lớp** chỉ gồm `name`, `label`, `description`, `status` và môn (`subject_id` → tên từ `GET /subjects`); chưa có tên giáo viên, sĩ số, địa điểm (G9, G13).
- **Thời gian:** giờ Việt Nam; hạn nộp/đóng bài hiển thị tuyệt đối ("Thứ Sáu 24/10 · 23:59") kèm tương đối ("còn 2 ngày").
- Các quy ước 401/403/404, 422, thông báo kéo về xem `screen-inventory.md` mục "Quy ước chung".

---

## Student vs Parent thấy gì

| Dữ liệu | Student (của mình) | Parent (của con đang chọn) |
|---|---|---|
| Danh sách lớp, môn | Lớp mình đang học (`GET /classes`) | Lớp của **tất cả** con gộp chung (`GET /classes`); chưa phân biệt lớp nào của con nào (G9) |
| Lịch, buổi học, `lesson_content` | Có | Có |
| `notes` của buổi học | Hiện đang trả về; cần chốt (G12) | Như Student; cần chốt (G12) |
| `review_required` của buổi học | Hiện đang trả về; **không hiển thị**, cần ẩn ở BE (G12) | Như Student |
| Điểm danh | Chỉ của mình; chưa có API theo học sinh (G3) | Chỉ của con; chưa có API theo học sinh (G3) |
| Roster, tên bạn cùng lớp | Không | Không |
| Danh sách bài giao | Bài giao cho mình (`GET /me/assessments`) | Bài giao cho con (`GET /me/children/{id}/assessments`) |
| Làm bài, lưu đáp án, nộp | Có | **Không** (không có endpoint) |
| Bài làm đã nộp (câu trả lời của con) | Xem được bài làm của mình (`GET /me/attempts/{id}`) | **Không** thấy bài làm của con |
| Điểm, % , đạt/chưa đạt | Chỉ sau công bố | Chỉ sau công bố |
| Lịch sử các lượt (điểm từng lượt) | Có (`attempts_history`) | Có |
| Cờ `show_answers`/`show_explanations`/`show_question_scores` | Có (trong `result`) | Có (trong `result`) |
| Nhận xét giáo viên đã công bố | Mức hiển thị `learner` | Mức hiển thị `learner` **và** `parent_if_published`; mức `private` không ai trong hai vai trò thấy |
| Tiến độ (TB, số bài, theo môn) | Có (`GET /me/progress`) | Có (`GET /me/children/{id}/progress`) |
| Khóa học | Khóa mình đã ghi danh | Khóa các con đã ghi danh |
| Đơn xin phép / giải trình vắng | **Không** thấy, không gửi được | Gửi và xem đơn của con |
| Thông báo | Của mình | Của mình, chỉ khi liên kết còn hiệu lực (`BR-NOTIF-003`) |

---

# Cổng Student

Mọi màn hình Student chạy với vai trò đang hoạt động là `student`. Tài khoản chưa có hồ sơ Student gặp 403 "Tài khoản không có hồ sơ Student." hoặc 404 "Request failed." (xem Q4 trong `screen-inventory.md`: học sinh nhỏ chưa có tài khoản không dùng cổng này).

### ST-01 — Tổng quan   🟡 🎨
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** mở app là biết ngay hôm nay có buổi học nào, bài nào sắp đến hạn, có gì mới.
- **Vào từ / đi tới:** trang đầu sau đăng nhập. Bấm buổi học → ST-02 (chi tiết buổi); bấm bài → ST-05; "Xem tất cả bài" → ST-03; chuông → CM-06.
- **Khối nội dung & dữ liệu hiển thị:**
  1. Lời chào + ngày hôm nay (tên từ `GET /auth/me`).
  2. *Buổi học hôm nay* (tối đa vài dòng, theo giờ): giờ bắt đầu–kết thúc, tên lớp + môn, trạng thái buổi (`scheduled`/`completed`/`cancelled`). Ghép: `GET /classes` → mỗi lớp `GET /classes/{id}/lesson-sessions?from=<0h hôm nay>&until=<24h>`; tên lớp/môn lấy từ `GET /classes` + `GET /subjects`.
  3. *Bài sắp đến hạn*: bài có `status = assigned`, `assessment_status = published`, sắp theo `deadline_at` tăng dần; mỗi dòng: tên (`title`), loại (`type`), hạn, chip trạng thái theo "Quy ước chung". Nguồn `GET /me/assessments`.
  4. *Mới công bố điểm*: ghép tạm bằng cách gọi `GET /me/assessments/{id}/result` cho các bài `completed` (N+1); thiếu danh sách kết quả đã công bố (G8).
  5. Dải "Bài đang làm dở" nếu FE còn giữ `attempt.id` cục bộ của một lượt `in_progress`: nút "Tiếp tục làm bài" (xem N-E).
- **Chức năng & hành động:** chỉ điều hướng. "Chuỗi ngày liên tiếp"/điểm thưởng là ⛔ ngoài V1, không vẽ.
- **Trạng thái cần vẽ:** tải (khung xương từng khối, mỗi khối tự tải riêng) · rỗng toàn trang ("Hôm nay em không có buổi học. Xem lịch tuần" → ST-02) · rỗng từng khối ("Chưa có bài nào đến hạn") · lỗi từng khối (nút thử lại riêng, khối khác vẫn hiển thị) · buổi hôm nay bị hủy (hiện gạch ngang + nhãn "Đã hủy").
- **Quy tắc & lỗi nghiệp vụ:** không có lỗi nghiệp vụ riêng. Bài `draft` không xuất hiện (BE lọc).
- **API:** `GET /auth/me`, `GET /classes`, `GET /subjects`, `GET /classes/{id}/lesson-sessions`, `GET /me/assessments`. 🟡 thiếu: API tổng quan gộp (G1) và lịch theo người dùng có tên lớp/môn (G2); ghép tạm tốn 1 + N request (N = số lớp).
- **Dữ liệu mẫu gợi ý:** "Chào Minh Anh!" · 17:30–19:00 Toán 9A (Còn 3 giờ nữa) · "Bài tập Hàm số bậc nhất — hạn 23:59 Thứ Sáu, chưa làm".

### ST-02 — Lịch học   ✅ (lịch tổng hợp 🟡 G2) 🎨
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** xem tuần này/tháng này học gì, lúc nào, buổi nào đổi hoặc hủy, em có mặt hay vắng.
- **Vào từ / đi tới:** menu "Lịch học", ST-01, thông báo `schedule`/`lesson_session` (CM-06). Bấm một buổi mở bảng chi tiết buổi (cùng màn, dạng trang con/ngăn kéo).
- **Khối nội dung & dữ liệu hiển thị:**
  1. Bộ chuyển *Tuần / Tháng* + mũi tên đổi kỳ; trên điện thoại mặc định danh sách theo ngày trong tuần (nhóm theo ngày), khổ rộng hiện lưới.
  2. Danh sách buổi: `starts_at`–`ends_at`, tên lớp, môn, `status` (`scheduled`/`completed`/`cancelled`). Buổi `cancelled` vẫn hiện, gạch ngang, nhãn "Đã hủy" (không bị ẩn để em không đi nhầm).
  3. *Chi tiết buổi* (`GET /lesson-sessions/{id}`): `lesson_content` (nội dung buổi học); `notes` **chỉ hiện sau khi G12 chốt công khai** (tạm ẩn); `review_required` **không hiển thị**; điểm danh của chính em (nhãn Có mặt / Đi muộn / Vắng / Có phép + ghi chú `note`) từ `GET /lesson-sessions/{id}/attendance` (BE chỉ trả bản ghi của em); nếu buổi đã diễn ra mà chưa có bản ghi: "Chưa điểm danh".
  4. Chưa có: tên giáo viên, địa điểm/phòng (G13), nên khối này chỉ vẽ chỗ trống dự phòng "Địa điểm: cập nhật sau" nếu design muốn; không bịa dữ liệu.
- **Chức năng & hành động:** đổi tuần/tháng; bấm buổi xem chi tiết. Không có nút sửa/hủy/điểm danh. Không có nút gửi đơn (đơn do phụ huynh gửi, Student không thấy PR-08).
- **Trạng thái cần vẽ:** tải · rỗng ("Tuần này em không có buổi học") · lỗi (thử lại) · buổi đã hủy · buổi đang diễn ra (nổi bật theo giờ) · không có quyền/không tìm thấy buổi (404 "Request failed.").
- **Quy tắc & lỗi nghiệp vụ:** 404 "Request failed." khi buổi không thuộc lớp của em (kể cả bị rút khỏi lớp). Không hiện lịch của lớp đã `Ended` ngoài phạm vi.
- **API:** `GET /classes`, `GET /classes/{id}/lesson-sessions?from&until` (có lọc ngày ✅), `GET /lesson-sessions/{id}`, `GET /lesson-sessions/{id}/attendance`, `GET /subjects`. 🟡 thiếu: lịch theo người dùng, buổi kèm tên lớp/môn (G2) — ghép tạm lặp qua mọi lớp; điểm danh theo học sinh (G3) — điểm danh chỉ lấy được khi mở chi tiết từng buổi.
- **Dữ liệu mẫu gợi ý:** Thứ Ba 21/10 · 17:30–19:00 Toán 9A (đã học, em có mặt) · Thứ Năm 23/10 · 17:30–19:00 Văn 9A (đã hủy) · nội dung buổi: "Ôn tập hệ phương trình bậc nhất hai ẩn".

### ST-03 — Bài tập   ✅ 🎨
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết mình còn bài tập nào chưa nộp, hạn khi nào, bài nào đã có điểm.
- **Vào từ / đi tới:** menu "Bài tập", ST-01. Bấm dòng → ST-05 (chi tiết bài).
- **Khối nội dung & dữ liệu hiển thị:**
  1. Bộ lọc nhanh theo trạng thái: Tất cả · Chưa làm · Chờ chấm · Đã có điểm · Đã đóng/hủy (lọc ở FE).
  2. Danh sách bài `type = assignment`: `title`, hạn `deadline_at` (và `closes_at` nếu khác), chip trạng thái ghép (xem "Quy ước chung"), điểm tối đa `max_score`. Nhóm: *Cần làm* (sắp theo hạn) trước, *Đã xong* sau.
  3. Chưa có tên lớp/môn cho từng bài (`source_class_id`/`source_course_id` không được trả ra) → không lọc theo môn được (N-A).
- **Chức năng & hành động:** bấm vào bài; kéo xuống làm mới. Không có hành động trực tiếp trên dòng.
- **Trạng thái cần vẽ:** tải · rỗng ("Em chưa có bài tập nào. Khi giáo viên giao, bài sẽ hiện ở đây") · lỗi · bài quá hạn chưa nộp (nhãn "Quá hạn" nếu còn cho nộp trễ, "Đã đóng" nếu hết `closes_at`) · bài đã hủy.
- **Quy tắc & lỗi nghiệp vụ:** danh sách không phân trang; 404 "Request failed." nếu tài khoản không có hồ sơ Student.
- **API:** `GET /me/assessments` → `data[]` gồm `id, title, type, assessment_status, opens_at, closes_at, deadline_at, max_score, status`. Thiếu: chip "chờ chấm/đã chấm" thật (ghép từ `…/result`, N+1), tên lớp/môn (N-A), hạn riêng của em nếu được gia hạn (`individual_deadline_at` không có trong response; N-A).
- **Dữ liệu mẫu gợi ý:** "Bài tập Hàm số bậc nhất" · hạn Thứ Sáu 24/10 23:59 · 10 điểm · Chưa làm; "Luyện đọc hiểu: Lão Hạc" · Đã có điểm 8/10.

### ST-04 — Bài kiểm tra   ✅ 🎨
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết kỳ kiểm tra/bài thi nào sắp mở, mở lúc nào, làm bao lâu, đã làm chưa.
- **Vào từ / đi tới:** menu "Bài kiểm tra", ST-01. Bấm dòng → ST-05.
- **Khối nội dung & dữ liệu hiển thị:** như ST-03 nhưng `type = quiz` (trắc nghiệm) và `type = exam` (thi). Mỗi dòng thêm: nhãn loại (Kiểm tra / Thi), **khung giờ** `opens_at`–`closes_at` (nổi bật hơn hạn; `deadline_at` của bài thi trùng khung giờ), trạng thái "Chưa mở" nếu giờ hiện tại trước `opens_at`, "Đang mở" nếu trong khung. **Thời lượng làm bài và số câu chưa có trong danh sách** (N-A): hiển thị ở ST-05 sau khi có API; tạm chỉ hiện khung giờ.
- **Chức năng & hành động:** như ST-03. Không có nút "Làm bài" ở danh sách (đi qua ST-05 để đọc lưu ý trước).
- **Trạng thái cần vẽ:** như ST-03 + *Chưa mở* (bấm vẫn vào ST-05, nút Làm bài khóa) · *Đang mở* · *Đã đóng, em không làm*.
- **Quy tắc & lỗi nghiệp vụ:** như ST-03. `Exam` hết giờ là tự nộp (`BR-ASSESS-019`); nhắc ở ST-05.
- **API:** `GET /me/assessments` (lọc `type` ở FE). Thiếu: thời lượng, số câu (N-A).
- **Dữ liệu mẫu gợi ý:** "Kiểm tra 15 phút — Căn bậc hai" · mở 18:00–18:30 ngày 25/10 · Chưa mở; "Thi giữa kỳ I — Toán 9" · 08:00–09:30 Thứ Bảy 01/11 · Chưa làm.

### ST-05 — Chi tiết bài   🟡 🎨
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** đọc hướng dẫn, biết còn mấy lượt, điểm lượt trước, rồi bấm làm bài.
- **Vào từ / đi tới:** ST-03/ST-04/ST-01, thông báo `assessment`. Nút "Làm bài" → ST-06; "Xem kết quả" → ST-07.
- **Khối nội dung & dữ liệu hiển thị:**
  1. Đầu trang: `title`, loại, môn/lớp nguồn (🟡 N-A), chip trạng thái.
  2. *Thông tin hiện có* từ `GET /me/assessments/{id}`: `opens_at`, `closes_at`, `deadline_at`, `max_score`, `assessment_status`, `status`.
  3. *Thông tin chưa có API (🟡 N-A)*, vẽ sẵn chỗ: hướng dẫn làm bài (`instructions`), thời lượng (`duration_seconds`), số câu, số lượt tối đa (`max_attempts`, có thể bị ghi đè theo từng học sinh `attempt_limit_override`), chính sách nộp trễ (`late_policy.allow_late`), cách tính điểm nhiều lượt (cao nhất / lần cuối), hạn riêng của em.
  4. *Số lượt còn lại* và *điểm lượt trước*: 🟡 N-A. "Điểm các lượt trước" lấy được từ `attempts_history` của `GET /me/assessments/{id}/result` nhưng **chỉ lượt đã công bố**; lượt chờ chấm không có điểm.
  5. *File đề đính kèm*: 🟡 N-D (có `GET /attachments/{id}` nhưng không có API trả danh sách/ id file đính kèm của bài cho học sinh).
- **Chức năng & hành động:**
  - Nút **Làm bài**: hiện khi `status = assigned` và `assessment_status = published`. Vô hiệu hóa kèm lý do khi: chưa tới `opens_at` ("Chưa tới giờ mở bài, mở lúc …"), đã qua `closes_at` ("Đã quá giờ đóng bài"), hết lượt ("Em đã dùng hết số lượt"), bài đã đóng/hủy. Bấm gọi `POST /me/assessments/{id}/attempts` (tạo lượt, trả 201 kèm danh sách câu) → chuyển ST-06. Vì BE tính lượt ngay khi bắt đầu (`BR-ASSESS-015`), có hộp xác nhận nếu bài có thời lượng: "Bấm Bắt đầu sẽ tính 1 lượt và đồng hồ chạy ngay. Làm tiếp được nếu mất mạng nhưng đồng hồ không dừng."
  - Nếu em đang có lượt `in_progress` mà FE biết `attempt.id`: nút chính là **Tiếp tục làm bài** (mở lại ST-06 qua `GET /me/attempts/{id}`), không tạo lượt mới (N-E).
  - Nút **Xem kết quả** khi có kết quả công bố (`GET …/result` không 404).
- **Trạng thái cần vẽ:** tải · lỗi · bài chưa mở · bài đang mở · bài hết lượt · bài đã đóng · bài đã hủy · đã nộp, chờ chấm · đã có điểm · 404 (bài không giao cho em).
- **Quy tắc & lỗi nghiệp vụ** (khi bấm Làm bài): `ASSESSMENT_NOT_OPEN_YET` 409 "Chưa tới giờ mở bài." · `ASSESSMENT_CLOSED` 409 "Đã quá giờ đóng bài." · `ASSESSMENT_NOT_PUBLISHED` 409 "Assessment chưa được publish hoặc đã đóng." (đề xuất hiển thị: "Bài này chưa mở hoặc đã đóng.") · `ATTEMPT_LIMIT_REACHED` 409 "Đã dùng hết số lượt làm bài cho phép." · `STUDENT_ASSESSMENT_NOT_ASSIGNED` 409 "Bài không còn ở trạng thái được giao." · 404 "Request failed." nếu bài không giao cho em. Mọi lỗi hiện dạng thông báo, rồi tải lại chi tiết để cập nhật nút.
- **API:** `GET /me/assessments/{id}`, `GET /me/assessments/{id}/result` (lịch sử lượt), `POST /me/assessments/{id}/attempts`, `GET /attachments/{id}`. 🟡 thiếu (N-A, N-D): chi tiết đầy đủ bài cho học sinh (hướng dẫn, thời lượng, số câu, `max_attempts`, số lượt đã dùng, file đính kèm); (N-E) lượt đang dở.
- **Dữ liệu mẫu gợi ý:** "Kiểm tra 15 phút — Căn bậc hai" · Mở 18:00, đóng 18:30 · Thời lượng 15 phút · 10 câu · Còn 1/1 lượt · "Em có thể thoát và quay lại, đồng hồ vẫn chạy."

### ST-06 — Làm bài   ✅ (câu nộp file 🟡 G6) 🎨
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính (phải dùng tốt cả khi màn hình nhỏ, xoay ngang, bàn phím ảo che màn hình, và khi dùng chuột, bàn phím).
- **Người dùng muốn làm gì:** trả lời từng câu mà không sợ mất bài, biết còn bao lâu, nộp khi xong.
- **Vào từ / đi tới:** ST-05 ("Làm bài"/"Tiếp tục"). Nộp xong → ST-06b. Thoát giữa chừng → ST-05 (bài vẫn đang mở, đồng hồ vẫn chạy).
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Thanh trên cố định*: tên bài; **đồng hồ đếm ngược** (nếu `ends_at` không null); trạng thái lưu ("Đã lưu 17:42" / "Đang lưu…" / "Chưa lưu được — đang thử lại"); nút "Danh sách câu".
  2. *Một câu mỗi màn hình* (điện thoại): số thứ tự `position`/tổng, điểm `points`, nội dung `snapshot.prompt`, ghi chú `snapshot.instructions` nếu có, vùng trả lời theo loại (bên dưới), nút đánh dấu "Xem lại sau".
  3. *Thanh dưới*: "Câu trước" · "Câu sau"; câu cuối chuyển thành "Xem lại & nộp".
  4. *Bảng điều hướng câu* (ngăn kéo/modal): lưới số câu, mỗi ô có trạng thái *Đã trả lời* / *Chưa trả lời* / *Đã đánh dấu*; chạm để nhảy tới câu. Đánh dấu chỉ lưu ở FE (không có trường trên BE); mất khi tải lại nếu không lưu cục bộ, chấp nhận được.
  5. Nguồn dữ liệu: `GET /me/attempts/{id}` → `attempt{ id, attempt_number, status, started_at, ends_at, attempt_questions[{ id, position, assessment_question_id, question_type, points, snapshot{ prompt, instructions, options[{id, position, content}], type_payload } }], answers[], submission? }`. Thứ tự câu và thứ tự đáp án đã được BE xáo theo cấu hình; FE hiển thị đúng thứ tự trả về, không tự xáo. BE **không** trả đáp án đúng.
- **Cách hiển thị và nhập đáp án cho từng loại câu** (lưu bằng `PUT /me/attempts/{id}/answers/{assessment_question_id}` body `{ "answer_payload": … }`; `null`/rỗng nghĩa là bỏ trống):

  | Loại (`question_type`) | Hiển thị | Cách nhập | `answer_payload` gửi lên |
  |---|---|---|---|
  | `single_choice` | Danh sách đáp án (`options[].content`) dạng nút chọn một | Chạm một đáp án, đổi chọn tự bỏ chọn cái cũ | **mảng một phần tử**: `["<optionId>"]` (BE so mảng, không nhận chuỗi trần) |
  | `multiple_choice` | Danh sách đáp án dạng ô tích; ghi chú "Chọn tất cả đáp án đúng" | Chạm để tích/bỏ tích | mảng id các đáp án đã chọn: `["a","c"]`. Chấm **đúng cả tập mới có điểm** (không điểm từng phần): nhắc ngay dưới câu |
  | `true_false` | Hai lựa chọn lớn "Đúng" / "Sai" (từ `options`) | Chạm một trong hai | `["<optionId>"]` |
  | `fill_in_the_blank` | Câu có chỗ trống; ô nhập ngay dưới nội dung | Gõ vào một ô văn bản một dòng | **chuỗi** (BE chỉ chấm một chuỗi, so khớp danh sách đáp án chấp nhận). Câu nhiều chỗ trống: cần xác nhận khi dựng (`type_payload` chưa ghim hình dạng) |
  | `short_answer` | Nội dung câu + một ô trả lời ngắn | Gõ vào ô một dòng | chuỗi |
  | `essay` | Nội dung câu + vùng soạn nhiều dòng tự giãn, đếm ký tự | Gõ; bàn phím ảo không che vùng đang gõ | chuỗi; **chấm tay**, sau khi nộp trạng thái "chờ chấm" |
  | `matching` | Hai cột (trái: mục cần nối; phải: lựa chọn); trên điện thoại mỗi mục trái là một dòng có nút chọn đáp án bên phải (không kéo thả) | Chạm mục trái → chọn mục phải từ danh sách; mục phải đã dùng được đánh dấu | **đối tượng** khóa–giá trị: `{"<khóa trái>":"<khóa phải>"}`. Danh sách hai cột lấy từ `snapshot.type_payload`; hình dạng cụ thể cần xác nhận khi dựng (BE chỉ ghim khóa `correct` bị ẩn) |
  | `ordering` | Danh sách các mục theo thứ tự xáo ban đầu | Nút lên/xuống mỗi mục (ưu tiên, đảm bảo dùng được trên điện thoại) và/hoặc kéo thả | **mảng** khóa các mục theo thứ tự em sắp. Chưa chạm vào thì chưa lưu và tính là "Chưa trả lời" |
  | `file_upload` 🟡 G6 | Nội dung câu + vùng "Chọn tệp hoặc chụp ảnh" (pdf, doc/docx, jpg, png, zip, tối đa 10 MB) | Chọn/chụp, xem tên tệp đã gắn, thay hoặc gỡ | **chưa dùng được**: `POST /attachments` có tồn tại nhưng không có cách gắn tệp vào `answer` và không có API xem lại (policy: link `answer_id` chưa cho xem). Vẽ vùng này ở trạng thái khóa kèm dòng "Câu nộp file sẽ bật sau"; giáo viên không nên giao loại câu này cho tới khi G6 xong. Chấm tay |

- **Chức năng & hành động:**
  - **Tự lưu từng câu:** chọn đáp án/tích/nối/sắp xếp lưu ngay khi chạm; câu gõ văn bản lưu sau khi ngừng gõ ~1 giây, khi rời ô, và khi chuyển câu (không bao giờ chỉ lưu khi bấm nút). Mỗi lần lưu gọi `PUT …/answers/{q}`; thành công trả `answer{ …, saved_at, is_submitted }` (HTTP 200 kể cả lần đầu). Lưu theo hàng đợi mỗi câu: lần gõ sau thay lần trước, không để hai request cùng câu chồng nhau.
  - **Đồng hồ đếm ngược lấy mốc từ server:** thời gian còn lại = `ends_at` − giờ máy chủ hiện tại. BE không trả trường giờ máy chủ riêng; FE tính độ lệch (offset) giữa đồng hồ máy em và máy chủ từ header `Date` của lần `GET /me/attempts/{id}` (cần xác nhận khi dựng), rồi đếm theo offset, **không** tin đồng hồ máy em và **không** dùng `started_at + thời lượng`. Với bài thi/quiz, `ends_at` đã cắt theo `closes_at`, nên em vào muộn thì thời gian còn lại ngắn hơn thời lượng (`BR-ASSESS-019`): hiện dòng "Bài đóng lúc … nên em chỉ còn … phút". Còn 5 phút và 1 phút: đổi trạng thái đồng hồ (cảnh báo, không chặn). Bài không có thời lượng (`ends_at = null`, thường là bài tập): không có đồng hồ, hiển thị `deadline_at` thay thế.
  - **Đồng hồ không dừng khi mất mạng hoặc thoát trang** (`BR-ASSESS-018`); nói rõ trong hộp xác nhận ở ST-05 và dải thông báo khi mất kết nối.
  - **Hết giờ tự nộp:** khi đồng hồ về 0, FE khóa nhập, rồi gọi lại `GET /me/attempts/{id}`; BE tự nộp phần đã lưu khi đọc một lượt đã quá `ends_at` (Exam luôn tự nộp; Quiz/Assignment chỉ tự nộp nếu `late_policy.allow_late ≠ false`). Sau đó hiển thị thông báo "Hết giờ, bài của em đã được nộp" → ST-06b. Nếu response vẫn `in_progress` (bài không cho nộp trễ) thì hiện "Đã hết thời gian nộp bài" và nút về ST-05.
  - **Xác nhận nộp:** nút "Nộp bài" (thanh dưới, màn danh sách câu) mở hộp xác nhận nêu *số câu đã trả lời / tổng* và *số câu chưa trả lời + câu đã đánh dấu* kèm đường nhảy tới câu đó: "Em còn 3 câu chưa làm. Nộp bài? Nộp rồi không sửa được." Hai nút "Quay lại làm" / "Nộp bài". Trước khi gọi `POST /me/attempts/{id}/submit`, FE phải chờ hàng đợi lưu rỗng (đã lưu mọi câu); nếu còn câu chưa lưu được: "Còn câu chưa lưu — Thử lại" và chưa cho nộp.
  - **Điều hướng câu:** Trước/Sau, chạm số trong bảng; giữ nguyên vị trí khi tải lại (lưu số câu hiện tại cục bộ). Thoát bằng nút Back/đóng tab: hỏi "Em có chắc muốn rời trang? Bài vẫn đang mở và đồng hồ vẫn chạy."
  - **Trạng thái mất kết nối:** dải cố định trên cùng "Mất kết nối — bài của em vẫn được giữ trên máy, sẽ tự lưu khi có mạng lại"; giữ đáp án chưa lưu trong bộ nhớ (và lưu cục bộ để sống qua tải lại), thử lại theo chu kỳ tăng dần; ô trạng thái lưu hiển thị "Chưa lưu"; **nút Nộp bài bị khóa khi chưa có mạng/hàng đợi chưa rỗng**; đồng hồ vẫn đếm bằng offset đã biết. Khi có mạng lại: gửi lại lần lượt, dải đổi thành "Đã kết nối lại, đã lưu".
- **Trạng thái cần vẽ:** tải bài · lượt vừa tạo (bắt đầu đồng hồ) · từng loại câu (chưa trả lời, đã trả lời, đã đánh dấu) · đang lưu / đã lưu / lưu lỗi · mất kết nối · cảnh báo còn 5 phút / 1 phút · hết giờ đang nộp · hộp xác nhận nộp (có và không có câu bỏ trống) · nộp đang xử lý · lượt không còn mở (xem lỗi) · không có quyền.
- **Quy tắc & lỗi nghiệp vụ:**
  - `ATTEMPT_NOT_OPEN` 409 "Attempt đã nộp hoặc không còn mở." (lưu hoặc nộp một lượt đã nộp/hủy; ví dụ hai tab, hoặc Admin cấp lại lượt): chặn nhập, báo "Bài này đã được nộp hoặc không còn mở", nút về ST-05/ST-07.
  - `ATTEMPT_TIMED_OUT` 409 "Đã hết giờ làm bài." (lưu câu sau `ends_at`): khóa nhập, chạy luồng hết giờ ở trên (gọi `GET` để tự nộp). Câu gõ dở sau giờ **không** được lưu: báo rõ.
  - `ATTEMPT_DEADLINE_PASSED` 409 "Đã quá hạn nộp bài, bài này không cho nộp trễ." (nộp sau hạn khi `allow_late = false`): giữ nguyên các câu đã lưu, hiện thông báo lớn "Đã quá hạn nộp bài"; liên hệ giáo viên (giáo viên có thể gia hạn riêng hoặc Admin cấp lại).
  - `ATTEMPT_LIMIT_REACHED` 409 "Đã dùng hết số lượt làm bài cho phép.": phát sinh ở bước bắt đầu (ST-05), không ở ST-06; nếu gặp ở đây thì đưa về ST-05.
  - 404 "Request failed." khi `attempt` không phải của em hoặc câu hỏi không thuộc lượt. 401: lưu cục bộ phần đang làm rồi về đăng nhập, quay lại đúng lượt sau khi đăng nhập.
- **API:** `GET /me/attempts/{id}`, `PUT /me/attempts/{id}/answers/{assessmentQuestionId}`, `POST /me/attempts/{id}/submit`; `POST /me/assessments/{id}/attempts` (ST-05). 🟡 thiếu: file bài làm (G6); tìm lại lượt đang dở khi đổi thiết bị/xóa dữ liệu trình duyệt (N-E). Lưu ý: `attempt.answers[]` trong `GET` dùng để khôi phục đáp án đã lưu khi vào lại.
- **Dữ liệu mẫu gợi ý:** "Kiểm tra 15 phút — Căn bậc hai · Câu 3/10 · 1 điểm · √49 bằng bao nhiêu? ○ 5 ○ 7 ○ 9 ○ 14" · đồng hồ "12:40" · "Đã lưu 18:07" · câu 7 (sắp xếp): "Xếp các bước giải phương trình theo thứ tự đúng".

### ST-06b — Đã nộp bài   ✅ (thêm mới)
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết chắc bài đã được nộp, và bước tiếp theo là gì.
- **Vào từ / đi tới:** sau khi nộp (bấm nộp thành công hoặc hết giờ tự nộp) từ ST-06. Nút "Về chi tiết bài" → ST-05; "Về trang chủ" → ST-01; "Xem kết quả" → ST-07 (khi có).
- **Khối nội dung & dữ liệu hiển thị:** dấu hiệu thành công; tên bài; giờ nhận bài (`submission.received_at`); nhãn "Nộp trễ" nếu `submission.is_late`; trạng thái `submission.status`: `graded` (chấm máy xong, chờ giáo viên công bố) hoặc `awaiting_grading` (có câu tự luận chờ giáo viên chấm); câu "Điểm sẽ hiện khi giáo viên công bố". **Không hiển thị điểm** dù response nộp bài có `result.score` (chưa công bố; xem Quy ước chung). Còn lượt hay không: 🟡 N-A.
- **Chức năng & hành động:** các nút điều hướng nêu trên; "Làm lượt khác" chỉ khi biết còn lượt (N-A).
- **Trạng thái cần vẽ:** nộp thành công · nộp trễ · nộp do hết giờ · chờ chấm tay · chờ công bố.
- **Quy tắc & lỗi nghiệp vụ:** không có (lỗi nộp xử lý ở ST-06).
- **API:** `POST /me/attempts/{id}/submit` → `{ submission{ id, attempt_id, received_at, status, is_late, … }, result{…} }` (chỉ dùng `submission`).
- **Dữ liệu mẫu gợi ý:** "Đã nộp lúc 18:14. Bài có 2 câu tự luận, cô sẽ chấm và công bố điểm."

### ST-07 — Kết quả   ✅ (xem lại câu hỏi 🟡 N-B) 🎨
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết mình được bao nhiêu điểm, đạt không, cô nhận xét gì, có đáp án không.
- **Vào từ / đi tới:** ST-05 ("Xem kết quả"), ST-03/ST-04 (dòng đã có điểm), thông báo `result` (CM-06). Nút "Xem lại bài làm" → ST-07b.
- **Khối nội dung & dữ liệu hiển thị:** (nguồn `GET /me/assessments/{id}/result` → `{ result, attempts_history, feedbacks }`; chỉ trả khi đã công bố)
  1. *Điểm đại diện* (theo chính sách tính điểm nhiều lượt: cao nhất hoặc lần cuối, do BE chọn): `result.score` / `result.max_score`, `result.percentage`, nhãn **Đạt / Chưa đạt** từ `result.passed` (nếu bài không đặt điểm đạt, cần xác nhận khi dựng giá trị null).
  2. *Các lượt*: `attempts_history[]` (`attempt_number`, `score`, `percentage`) — bảng nhỏ; đánh dấu lượt được tính. Chỉ lượt đã công bố.
  3. *Nhận xét của giáo viên*: `feedbacks[]` mức học sinh (`body`, `created_at`; `assessment_question_id` null = nhận xét chung cho bài, khác null = nhận xét cho một câu). Tên giáo viên chỉ có `author_user_id` (chưa có tên). Hiển thị "Nhận xét của giáo viên".
  4. *Điểm từng câu / đáp án đúng / giải thích*: phụ thuộc cờ `result.show_question_scores`, `show_answers`, `show_explanations`. Cờ `false`: **ẩn hẳn khối** (không để chỗ trống, không hiện "không có"). Cờ `true`: nội dung tương ứng, nhưng **BE chưa có API trả điểm từng câu, đáp án đúng, giải thích cho học sinh** → 🟡 N-B. Vẽ khối ở trạng thái dự kiến cho từng câu: đúng/sai, điểm câu / điểm tối đa, đáp án của em, đáp án đúng, giải thích. Bài thi (Exam) mặc định ẩn đáp án cho tới khi giáo viên cho phép (`BR-ASSESS-025`).
- **Chức năng & hành động:** chuyển giữa các lượt (chỉ hiển thị điểm; chi tiết câu theo lượt đại diện); "Xem lại bài làm" (ST-07b). Không có nút khiếu nại/yêu cầu sửa điểm (ngoài V1; sửa điểm do giáo viên, `BR-ASSESS-024`).
- **Trạng thái cần vẽ:** tải · **chưa công bố** (404 "Request failed." từ `…/result`: hiện "Kết quả chưa được công bố. Cô sẽ báo khi có điểm" thay vì lỗi) · lỗi mạng (khác 404, có nút thử lại) · đã đạt / chưa đạt · nhiều lượt · có/không có nhận xét · nhận xét bị rút lại (biến mất; không hiện dấu vết).
- **Quy tắc & lỗi nghiệp vụ:** không có `error.code` riêng; 404 "Request failed." gộp chung các trường hợp (chưa công bố, không giao cho em, đã rút công bố). Khi giáo viên rút công bố, lần tải sau tự về trạng thái "chưa công bố".
- **API:** `GET /me/assessments/{id}/result`. 🟡 thiếu: điểm/đáp án/giải thích từng câu (N-B).
- **Dữ liệu mẫu gợi ý:** "8,5 / 10 · 85% · Đạt" · lượt 1: 6/10, lượt 2: 8,5/10 (được tính) · nhận xét: "Em làm tốt phần căn bậc hai, cần chú ý bước đặt điều kiện."

### ST-07b — Xem lại bài làm   🟡 (thêm mới)
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** xem lại mình đã trả lời gì cho từng câu, đối chiếu với nhận xét của giáo viên.
- **Vào từ / đi tới:** ST-07 ("Xem lại bài làm"). Quay lại ST-07.
- **Khối nội dung & dữ liệu hiển thị:** danh sách câu theo `position`: nội dung `snapshot.prompt`, loại, điểm tối đa `points`, **đáp án em đã chọn/viết** (`attempt.answers[].answer_payload` khớp theo `assessment_question_id`), nhận xét theo câu (`feedbacks[]` có `assessment_question_id`). Khi G/N-B xong: thêm đúng/sai, điểm câu, đáp án đúng, giải thích, theo các cờ `show_*`.
- **Chức năng & hành động:** cuộn/nhảy câu. Chỉ xem, không sửa.
- **Trạng thái cần vẽ:** tải · lỗi · câu bỏ trống ("Em chưa trả lời câu này") · câu file (🟡 G6) · các cờ `show_*` tắt.
- **Quy tắc & lỗi nghiệp vụ:** 404 "Request failed." nếu không phải lượt của em. Chỉ cho vào khi bài đã có kết quả công bố (FE kiểm soát; BE cho đọc lượt của chính mình bất cứ lúc nào, cần xác nhận với nghiệp vụ có nên chặn xem lại trước khi công bố, N-B).
- **API:** `GET /me/attempts/{id}`. 🟡 thiếu: `attempt.id` không có trong `attempts_history` và không có API liệt kê lượt của em (N-B, N-E); đáp án đúng/giải thích (N-B).
- **Dữ liệu mẫu gợi ý:** "Câu 3 — √49 bằng bao nhiêu? Em chọn: 7 (đúng) · 1/1 điểm".

### ST-08 — Tiến độ học tập   ✅ (biểu đồ theo thời gian 🟡 G8)
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết điểm mình đang ở đâu so với cả quá trình, môn nào yếu.
- **Vào từ / đi tới:** menu "Tiến độ". Bấm một môn → lọc danh sách bài của môn đó (ST-03/ST-04, lọc FE, sau khi N-A xong).
- **Khối nội dung & dữ liệu hiển thị:** (nguồn `GET /me/progress`, phẳng, không bọc `data`)
  1. *Điểm trung bình* `average_percentage` (%; `null` nếu chưa có bài nào được công bố: hiện "Chưa có điểm").
  2. *Số bài đã có điểm* `graded_count` và *số bài đang chờ chấm/công bố* `pending_count`.
  3. *Theo môn* `by_subject[]`: `subject_name`, `average_percentage`, `graded_count`; nhóm "Khác" (`subject_id = null`) là bài không gắn lớp/khóa.
  4. Bộ lọc khoảng thời gian (`from`, `to`) và môn (`subject_id`) là tham số truy vấn BE hỗ trợ (không kiểm tra hợp lệ), tùy chọn.
  5. 🟡 **Biểu đồ điểm theo thời gian và "bài gần đây"**: chưa có API (G8); `progress` không có điểm từng bài. Vẽ khối ở trạng thái dự kiến (đường điểm % theo ngày công bố); cho tới khi có G8 thì ẩn hoặc hiển thị "Sắp có". Điểm danh / chuyên cần cũng chưa có (G3).
  6. Mỗi bài chỉ tính một lần (kể cả nhiều lượt), theo chính sách tính điểm của bài; bài chưa công bố hoặc đã hủy không tính.
- **Chức năng & hành động:** đổi bộ lọc thời gian/môn.
- **Trạng thái cần vẽ:** tải · rỗng ("Chưa có bài nào được chấm. Điểm sẽ hiện khi cô công bố") · lỗi · chỉ có 1 bài (không vẽ biểu đồ) · có bài chờ chấm.
- **Quy tắc & lỗi nghiệp vụ:** `BR-ASSESS-026`: bài chưa chấm không tính trung bình; bài hủy không tính điểm.
- **API:** `GET /me/progress?from&to&subject_id&class_id&course_id`. 🟡 thiếu: lịch sử điểm (G8), điểm danh (G3).
- **Dữ liệu mẫu gợi ý:** TB 78,5% · 6 bài đã có điểm · 1 bài chờ · Toán 82% (4 bài) · Văn 72% (2 bài).

### ST-09 — Khóa học của em   ✅ (bài thuộc khóa 🟡 N-A)

> ⚠ **Đổi mô hình `DEC-LRN-026` (2026-10-30):** khóa hiện theo **đối tượng khóa** (em nằm trong danh sách trực tiếp hoặc lớp của em có trong danh sách), không còn "đã ghi danh" và không còn thời gian ghi danh. Màn hiện các mục (bài) của khóa theo thứ tự. Bản cũ bên dưới cần viết lại sau mockup.
- **Vai trò · responsive:** Student · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** xem mình đang ghi danh khóa nào và các bài thuộc khóa đó (nội dung khóa chính là các bài đánh giá gán qua khóa, `BR-COURSE-002`).
- **Vào từ / đi tới:** menu "Khóa học". Bấm khóa → chi tiết khóa; bấm bài → ST-05.
- **Khối nội dung & dữ liệu hiển thị:**
  1. Danh sách khóa (`GET /courses`, đã lọc theo ghi danh còn hiệu lực của em): `name`, `description`, `status`, môn (chi tiết `GET /courses/{id}` mới có `subject_id`; danh sách không có); tên giáo viên phụ trách chưa có (chỉ `owner_teacher_id`).
  2. Chi tiết khóa: thông tin khóa (`GET /courses/{id}`); thời gian ghi danh của em (`GET /courses/{id}/enrollments` → hàng của chính em: `status`, `valid_from`, `valid_to`).
  3. *Bài thuộc khóa*: 🟡 N-A — `GET /me/assessments` **không** trả `source_course_id`/`source_class_id`, nên không biết bài nào thuộc khóa nào. Vẽ khối ở trạng thái dự kiến (danh sách bài của khóa với chip trạng thái như ST-03). Tạm thời không ghép được.
- **Chức năng & hành động:** mở khóa, mở bài. Không có tự ghi danh/rút (Admin hoặc giáo viên sở hữu khóa quản lý).
- **Trạng thái cần vẽ:** tải · rỗng ("Em chưa ghi danh khóa học nào") · lỗi · khóa đã `archived`/hết hiệu lực (ghi danh `ended`: khóa biến mất khỏi danh sách) · 404 "Request failed." khi mở khóa không thuộc em.
- **Quy tắc & lỗi nghiệp vụ:** 404 (Student chỉ thấy khóa có ghi danh đang hiệu lực).
- **API:** `GET /courses`, `GET /courses/{id}`, `GET /courses/{id}/enrollments`. 🟡 thiếu: liên kết khóa → bài (N-A).
- **Dữ liệu mẫu gợi ý:** "Luyện thi vào 10 — Toán" · "Ôn tập tổng hợp cuối kỳ" · ghi danh từ 01/09/2026.

---

# Cổng Parent

Mọi màn hình Parent chạy với vai trò đang hoạt động là `parent`. Phụ huynh chỉ **xem** và **gửi đơn xin phép**; không làm bài, không sửa gì khác. Luôn chọn con trước (PR-01); `studentId` nằm trong đường dẫn (xem "Quy ước chung").

### PR-01 — Chọn con   ✅
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** chọn con cần xem (nếu có nhiều con) và chuyển nhanh giữa các con.
- **Vào từ / đi tới:** tự hiện sau đăng nhập khi có từ hai con; trình chuyển con ở đầu mọi màn hình Parent mở lại màn hình này (hoặc ngăn kéo cùng nội dung). Chọn con → PR-02 của con đó. Phụ huynh có đúng một con: bỏ qua màn hình này, vào thẳng PR-02.
- **Khối nội dung & dữ liệu hiển thị:** thẻ từng con: tên `full_name`, quan hệ `relationship_type` (bố / mẹ / người giám hộ... hiển thị nhãn tiếng Việt, giá trị thật cần xác nhận khi dựng), `student_id`. Chưa có: lớp đang học, ảnh, "có gì mới" (G9, G1). Sau khi chọn, con vừa chọn được ghi nhớ cục bộ.
- **Chức năng & hành động:** chạm thẻ để chọn. Không có thêm/xóa con (Admin quản lý liên kết).
- **Trạng thái cần vẽ:** tải · **rỗng** ("Tài khoản của bạn chưa được liên kết với học sinh nào. Liên hệ trung tâm để được liên kết", không có hành động tự thêm) · lỗi · liên kết vừa kết thúc (con biến mất khỏi danh sách; nếu đang ở màn hình của con đó thì xem "Mất quyền xem con").
- **Quy tắc & lỗi nghiệp vụ:** chỉ liên kết `active` được liệt kê. `GET /me/children/{id}` của con không còn liên kết trả 404 "Không tìm thấy.".
- **API:** `GET /me/children` → `data[{ student_id, full_name, relationship_type }]`; `GET /me/children/{studentId}` → `student{ id, full_name, status }` (có thể dùng để kiểm tra con còn hiệu lực khi mở app). 🟡 thiếu: danh sách lớp của con (G9) — ghép tạm: chỉ biết tập lớp gộp của tất cả con (`GET /classes`).
- **Dữ liệu mẫu gợi ý:** "Nguyễn Minh Anh — con gái" · "Nguyễn Gia Bảo — con trai".

### PR-02 — Tổng quan của con   🟡 🎨
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** mở app là trả lời ngay "hôm nay con có học không, có bài nào sắp đến hạn, có kết quả/nhận xét mới không" (Q5: V1 chỉ có thông báo trong ứng dụng nên màn này là chỗ phụ huynh biết "có gì mới").
- **Vào từ / đi tới:** trang đầu sau đăng nhập (sau PR-01 nếu nhiều con). Điều hướng tới PR-03 (lịch), PR-05 (bài), PR-06 (nhận xét), PR-07 (tiến độ), PR-08 (gửi đơn từ buổi sắp tới).
- **Khối nội dung & dữ liệu hiển thị:**
  1. Đầu trang: tên con + trình chuyển con.
  2. *Hôm nay con học gì*: buổi học hôm nay (giờ, tên lớp, môn, trạng thái, hủy/đổi). Ghép: `GET /classes` (lớp của mọi con) → `…/lesson-sessions?from&until`; **không xác định được buổi nào của con nào khi nhiều con** (G9/G2).
  3. *Bài sắp đến hạn của con*: `GET /me/children/{id}/assessments` lọc `status = assigned`, sắp theo `deadline_at`.
  4. *Kết quả mới công bố*: ghép từ `…/assessments/{aid}/result` cho các bài `completed` (N+1); thiếu danh sách (G8).
  5. *Nhận xét mới nhất*: nằm trong `…/result` → `feedbacks[]` (G7 khi cần danh sách tổng hợp).
  6. Gợi ý "con vắng buổi gần đây" cần G3.
- **Chức năng & hành động:** điều hướng và lối tắt "Xin phép nghỉ" tới PR-08 kèm buổi.
- **Trạng thái cần vẽ:** tải (từng khối) · rỗng từng khối ("Hôm nay con không có buổi học") · lỗi từng khối · mất quyền xem con (chuyển PR-01).
- **Quy tắc & lỗi nghiệp vụ:** 404 → xem "Mất quyền xem con".
- **API:** `GET /me/children`, `GET /me/children/{id}`, `GET /classes`, `GET /classes/{id}/lesson-sessions`, `GET /me/children/{id}/assessments`, `GET /me/children/{id}/assessments/{aid}/result`. 🟡 thiếu: tổng quan gộp (G1), lịch theo người dùng/con (G2), danh sách lớp của con (G9), lịch sử điểm (G8).
- **Dữ liệu mẫu gợi ý:** "Hôm nay Minh Anh có 1 buổi: Toán 9A 17:30–19:00" · "Bài Hàm số bậc nhất — hạn Thứ Sáu 23:59" · "Cô vừa nhận xét bài Căn bậc hai".

### PR-03 — Lịch học của con   🟡 (G9: chưa biết lớp của con) 🎨
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết tuần này con học khi nào, buổi nào đổi hoặc hủy để đưa đón.
- **Vào từ / đi tới:** menu "Lịch học", PR-02, thông báo `schedule`/`lesson_session`. Bấm buổi → chi tiết buổi; trong chi tiết có "Gửi đơn xin phép" → PR-08.
- **Khối nội dung & dữ liệu hiển thị:** như ST-02 (tuần/tháng, danh sách buổi, buổi hủy gạch ngang) cho con đang chọn. Chi tiết buổi: `lesson_content` hiển thị; `notes` chờ chốt (G12); `review_required` **ẩn**; điểm danh buổi đó của con (từ `GET /lesson-sessions/{id}/attendance`, BE chỉ trả bản ghi của các con của phụ huynh này) và trạng thái đơn xin phép của buổi (ghép từ PR-08). Không hiển thị bạn cùng lớp.
- **Chức năng & hành động:** đổi tuần/tháng; xem chi tiết; "Gửi đơn xin phép" cho buổi `scheduled` ở tương lai hoặc buổi con vắng gần đây (nút ẩn với buổi đã hủy).
- **Trạng thái cần vẽ:** tải · rỗng · lỗi · buổi đã hủy · buổi đang diễn ra · 404 buổi không thuộc lớp con.
- **Quy tắc & lỗi nghiệp vụ:** 404 "Request failed." khi buổi ngoài phạm vi lớp con (hoặc liên kết đã kết thúc). **Hạn chế dữ liệu:** `GET /classes` của Parent trả lớp của **tất cả** con; BE không cho biết lớp nào của con nào. Với phụ huynh một con, hiển thị bình thường; với nhiều con, **cần xác nhận khi dựng** — phương án tạm dùng `GET /courses/{id}/enrollments` chỉ cho khóa, không áp dụng cho lớp; nên coi đây là phụ thuộc cứng vào G9.
- **API:** `GET /classes`, `GET /classes/{id}/lesson-sessions?from&until`, `GET /lesson-sessions/{id}`, `GET /lesson-sessions/{id}/attendance`, `GET /subjects`. 🟡 thiếu: lớp theo con (G9), lịch theo người dùng/con (G2), điểm danh theo con (G3).
- **Dữ liệu mẫu gợi ý:** Thứ Ba 21/10 · 17:30–19:00 Toán 9A (con có mặt) · Thứ Năm 23/10 · 17:30–19:00 Văn 9A (đã hủy).

### PR-04 — Điểm danh của con   🟡 (G3) 🎨
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** xem con đi học đều không, buổi nào vắng; từ đó gửi đơn giải trình nếu vắng có lý do.
- **Vào từ / đi tới:** menu "Điểm danh", PR-02. Bấm buổi vắng → "Gửi đơn giải trình" → PR-08.
- **Khối nội dung & dữ liệu hiển thị:** (dự kiến, khi G3 xong) *tỷ lệ chuyên cần* (có mặt + đi muộn + có phép / tổng buổi đã điểm danh); *10 buổi gần nhất*: ngày, môn/lớp, trạng thái (Có mặt / Đi muộn / Vắng / Có phép — `present/late/absent/excused`), ghi chú `note`; *buổi vắng* tô nổi bật kèm trạng thái đơn xin phép nếu có (`submitted/under_review/accepted/rejected`). Buổi đã hủy không tính vào chuyên cần.
- **Chức năng & hành động:** mở buổi; "Gửi đơn giải trình" trên buổi vắng chưa có đơn (không khóa nếu đã có đơn bị từ chối, có thể gửi lại — cần xác nhận khi dựng); **không có nút sửa điểm danh** (phụ huynh chỉ gửi yêu cầu, không đổi trạng thái; `BR-ATT-005`). Lời giải thích trên màn: "Đơn của bạn là yêu cầu để giáo viên xem xét, không tự đổi điểm danh."
- **Trạng thái cần vẽ:** tải · rỗng ("Con chưa có buổi nào được điểm danh") · lỗi · không có dữ liệu vì G3 chưa xong (tạm: không vẽ tỷ lệ).
- **Quy tắc & lỗi nghiệp vụ:** 404 khi con không còn liên kết.
- **API:** 🟡 thiếu `GET /students/{id}/attendance` (G3). Ghép tạm duy nhất khả dĩ: với từng buổi đã qua của lớp con, gọi `GET /lesson-sessions/{id}/attendance` (N request mỗi buổi, kèm hạn chế G9); chỉ nên dùng cho thử nghiệm, không dùng thật.
- **Dữ liệu mẫu gợi ý:** "Chuyên cần 90% (9/10 buổi)" · Thứ Hai 13/10 Toán 9A: Vắng — chưa có đơn · Thứ Tư 15/10 Văn 9A: Có phép.

### PR-05 — Bài tập & kết quả của con   ✅ 🎨
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết con có bài gì, đã nộp chưa, kết quả ra sao.
- **Vào từ / đi tới:** menu "Bài tập", PR-02, thông báo `assessment`/`result`. Bấm dòng → chi tiết bài của con; từ đó "Xem kết quả" (chỉ khi có).
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Danh sách* (`GET /me/children/{id}/assessments`): `title`, `type`, hạn `deadline_at`, khung giờ, `max_score`, chip trạng thái ghép như Student (Chưa làm / Chờ chấm / Đã có điểm / Đã đóng / Đã hủy); bộ lọc Bài tập / Kiểm tra / Thi (lọc FE).
  2. *Chi tiết bài* (`…/assessments/{aid}`): các trường như ST-05 phần có sẵn. **Không có** nút làm bài, không có đồng hồ, không có số lượt dùng, không thấy câu hỏi hay bài làm của con.
  3. *Kết quả* (`…/assessments/{aid}/result`, chỉ khi đã công bố): `result.score/max_score/percentage/passed`; `attempts_history[]`; `feedbacks[]` mức `learner` và `parent_if_published` (xem PR-06); cờ `show_*` không làm gì ở PR vì không có API chi tiết câu (N-B); **phụ huynh không thấy câu trả lời của con**, chỉ điểm và nhận xét.
- **Chức năng & hành động:** mở bài, mở kết quả. Không nộp, không sửa.
- **Trạng thái cần vẽ:** tải · rỗng ("Con chưa có bài nào") · lỗi · kết quả chưa công bố (404 "Request failed." → "Chưa có kết quả, cô sẽ công bố sau") · bài đã hủy · mất quyền xem con.
- **Quy tắc & lỗi nghiệp vụ:** `BR-ASSESS-028`: chỉ xem kết quả sau công bố và khi còn liên kết. 404 "Request failed."/"Không tìm thấy." khi bài không giao cho con hoặc liên kết đã kết thúc.
- **API:** `GET /me/children/{studentId}/assessments`, `…/{assessmentId}`, `…/{assessmentId}/result`. Thiếu: tên lớp/môn của bài, thời lượng (N-A).
- **Dữ liệu mẫu gợi ý:** "Bài tập Hàm số bậc nhất — hạn Thứ Sáu 24/10 — Chưa làm" · "Kiểm tra 15 phút — Căn bậc hai — 8,5/10 — Đạt".

### PR-06 — Nhận xét   ✅ theo từng bài / 🟡 danh sách (G7) 🎨
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** đọc giáo viên nhận xét gì về con (kênh chính để biết tình hình học vì V1 không có chat).
- **Vào từ / đi tới:** menu "Nhận xét", PR-02 (nhận xét mới nhất), thông báo `result`. Bấm nhận xét → bài tương ứng ở PR-05.
- **Khối nội dung & dữ liệu hiển thị:** danh sách nhận xét đã công bố cho phụ huynh, mới nhất trước: tên bài, ngày (`created_at`), nội dung `body`, nhãn "Nhận xét chung" hoặc "Về câu số …" (khi `assessment_question_id` khác null; số câu không biết được nếu phụ huynh không thấy đề: hiển thị "Về một câu hỏi trong bài"). Tên giáo viên chưa có (chỉ `author_user_id`). Mức hiển thị: phụ huynh thấy `learner` và `parent_if_published`; mức `private` (ghi chú nội bộ của giáo viên) **không bao giờ** hiện. Danh sách tổng hợp **chưa có API** (G7): tạm ghép bằng cách duyệt `…/result` của từng bài đã có điểm (N+1). Nhận xét theo tuần/tháng không gắn bài (N6) là 🔵, không vẽ.
- **Chức năng & hành động:** đọc, mở bài. Không có nút trả lời (không có chat).
- **Trạng thái cần vẽ:** tải · rỗng ("Chưa có nhận xét nào từ giáo viên") · lỗi · nhận xét bị rút (biến mất).
- **Quy tắc & lỗi nghiệp vụ:** chỉ nhận xét ở trạng thái đã công bố; bản nháp/rút lại không hiện.
- **API:** `GET /me/children/{id}/assessments/{aid}/result` → `feedbacks[]`. 🟡 thiếu: danh sách nhận xét đã công bố theo con (G7).
- **Dữ liệu mẫu gợi ý:** "Kiểm tra 15 phút — Căn bậc hai · 22/10 · 'Minh Anh làm tốt phần căn bậc hai, cần chú ý bước đặt điều kiện.'"

### PR-07 — Tiến độ của con   ✅ (biểu đồ theo thời gian 🟡 G8)
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** biết điểm trung bình của con, môn nào mạnh/yếu.
- **Vào từ / đi tới:** menu "Tiến độ", PR-02.
- **Khối nội dung & dữ liệu hiển thị:** như ST-08 cho con đang chọn: `average_percentage`, `graded_count`, `pending_count`, `by_subject[]`, bộ lọc thời gian/môn; điểm danh và biểu đồ theo thời gian/"bài gần đây" là 🟡 (G3, G8).
- **Chức năng & hành động:** đổi bộ lọc; xem bài của một môn (PR-05).
- **Trạng thái cần vẽ:** như ST-08 + mất quyền xem con.
- **Quy tắc & lỗi nghiệp vụ:** như ST-08; 404 khi hết liên kết.
- **API:** `GET /me/children/{studentId}/progress?from&to&subject_id&class_id&course_id`.
- **Dữ liệu mẫu gợi ý:** "Điểm TB 78,5% · Toán 82% · Văn 72% · 1 bài đang chờ chấm".

### PR-08 — Đơn xin phép / giải trình vắng   ✅ 🎨
- **Vai trò · responsive:** Parent · responsive: dùng tốt cả trên điện thoại lẫn web máy tính.
- **Người dùng muốn làm gì:** báo trước hoặc giải trình sau việc con nghỉ buổi nào, biết giáo viên đã chấp nhận chưa.
- **Vào từ / đi tới:** menu "Đơn xin phép", PR-03 (nút trên buổi), PR-04 (nút trên buổi vắng). Gửi xong quay lại danh sách.
- **Khối nội dung & dữ liệu hiển thị:**
  1. *Danh sách đơn đã gửi* của con đang chọn: `GET /absence-explanations?student_id=<con>`, mỗi đơn `id`, `lesson_session_id`, `message`, `status` (`submitted` Đã gửi / `under_review` Đang xem xét / `accepted` Đã chấp nhận / `rejected` Bị từ chối). Thông tin buổi (ngày giờ, lớp) không có trong đơn: FE ghép từ `GET /lesson-sessions/{lesson_session_id}` và `GET /classes`. **Không có** ghi chú duyệt (`review_note`) và thời điểm gửi trong response danh sách (N-C); khi bị từ chối phụ huynh chưa biết lý do trong app.
  2. *Biểu mẫu gửi đơn* (trang hoặc ngăn kéo): chọn buổi học (danh sách buổi của lớp con, ưu tiên buổi sắp tới và vừa qua), chọn con (đã cố định theo ngữ cảnh), **lý do** (`message`, bắt buộc, nhiều dòng).
- **Chức năng & hành động:**
  - **Gửi đơn:** nút "Gửi đơn" → `POST /absence-explanations` body `{ student_id, lesson_session_id, message }` (cả ba bắt buộc); thành công (201) trả `absence_explanation{ id, student_id, lesson_session_id, status: submitted }`, thông báo "Đã gửi đơn. Giáo viên sẽ xem xét", đơn xuất hiện đầu danh sách.
  - Đơn là **yêu cầu để giáo viên/Admin xem xét, không tự đổi điểm danh** (`BR-ATT-005`, `DEC-LRN-008`): hiển thị câu này trên biểu mẫu và trên mỗi đơn đã chấp nhận ("Giáo viên đã chấp nhận; điểm danh sẽ do giáo viên cập nhật").
  - Không có sửa/thu hồi đơn (không có endpoint). Có gửi nhiều đơn cho cùng một buổi không: BE không chặn; cần xác nhận khi dựng.
  - Không có thao tác duyệt (thuộc TC-06).
- **Trạng thái cần vẽ:** tải · rỗng ("Chưa có đơn nào" + nút "Gửi đơn") · lỗi · đang gửi · gửi thành công · gửi thất bại (giữ nội dung đã gõ) · đơn theo từng `status` · buổi đã hủy (nếu chọn buổi đã hủy: cần xác nhận khi dựng, BE không chặn) · mất quyền xem con.
- **Quy tắc & lỗi nghiệp vụ:**
  - 422 theo trường (`error.details.<trường>`): `student_id`, `lesson_session_id`, `message` thiếu/sai định dạng, hiện ngay dưới trường (với `message`: "Vui lòng nhập lý do").
  - `STUDENT_NOT_ELIGIBLE` 422 "Học viên không thuộc lớp của buổi học này." (buổi của lớp mà con không học vào thời điểm đó): hiện thông báo, đề xuất "Buổi này không thuộc lớp của con. Vui lòng chọn buổi khác."
  - 403 "Không có quyền gửi giải trình cho học viên này." (con không còn liên kết): chuyển về PR-01 theo "Mất quyền xem con".
- **API:** `POST /absence-explanations`, `GET /absence-explanations?student_id=&status=`, `GET /lesson-sessions/{id}`, `GET /classes/{id}/lesson-sessions`. 🟡 thiếu: lý do duyệt/ngày gửi/thông tin buổi trong đơn (N-C).
- **Dữ liệu mẫu gợi ý:** "Buổi Toán 9A, Thứ Ba 21/10 17:30 — 'Con bị sốt, xin phép nghỉ' — Đã chấp nhận" · "Buổi Văn 9A, Thứ Năm 23/10 — 'Gia đình có việc' — Đã gửi".

---

## Khoảng trống phát hiện thêm

Ngoài G1…G14 trong `screen-inventory.md`, khi đối chiếu với code phát hiện (cần BE bổ sung hoặc cần user chốt):

- **N-A — Chi tiết bài cho Student/Parent quá nghèo.** `GET /me/assessments`, `GET /me/assessments/{id}` và bản của Parent chỉ trả `id, title, type, assessment_status, opens_at, closes_at, deadline_at, max_score, status`. Thiếu: hướng dẫn (`instructions`), thời lượng, số câu, số lượt tối đa và số lượt đã dùng (kể cả `attempt_limit_override`), `late_policy`, cách tính điểm nhiều lượt, hạn riêng (`individual_deadline_at`), lớp/môn/khóa nguồn (`source_class_id`/`source_course_id`) và tên lớp/môn. Hậu quả: ST-04/ST-05/ST-09 không đủ dữ liệu; không lọc bài theo môn; không dẫn từ khóa tới bài (ST-09).
- **N-B — Xem lại chi tiết từng câu sau khi có điểm chưa có.** `…/result` chỉ trả tổng điểm, lịch sử lượt và nhận xét; không có điểm từng câu, đáp án đúng, giải thích, mặc dù có cờ `show_answers`/`show_explanations`/`show_question_scores`. `attempts_history` không có `attempt.id`. Cần API kết quả chi tiết theo câu (tôn trọng các cờ) và `attempt.id` trong lịch sử.
- **N-C — Đơn xin phép thiếu thông tin cho phụ huynh.** Danh sách đơn không có `review_note` (lý do từ chối/chấp nhận), ngày gửi, giờ/tên buổi; không có thu hồi/sửa đơn.
- **N-D — Không có đường lấy file đính kèm của đề cho học sinh.** `GET /attachments/{id}` có quyền theo bài nhưng không API nào trả danh sách/ id tệp đính kèm của bài hay của câu cho Student/Parent (snapshot an toàn không có trường tệp).
- **N-E — Không khôi phục được lượt đang dở khi mất `attempt.id`.** Chỉ có `POST …/attempts` (luôn tạo lượt mới, tính lượt) và `GET /me/attempts/{id}` (cần biết id). Không có API liệt kê lượt của em hoặc tự trả lượt `in_progress`. `BR-ASSESS-018` yêu cầu khôi phục cùng Attempt (đổi thiết bị/xóa dữ liệu trình duyệt thì mất, dùng hết lượt). Cần `GET /me/assessments/{id}/attempts` hoặc để `POST` idempotent trả lượt đang dở.
- **Giờ máy chủ cho đồng hồ:** không có trường giờ máy chủ, FE dùng header `Date` (cần xác nhận khi dựng).
- **Lộ điểm chưa công bố:** `POST …/submit` và `GET /me/attempts/{id}` trả kèm `result` (có `score`) kể cả `publication_status = unpublished`. FE không hiển thị, nhưng nên cân nhắc BE ẩn khỏi Student.

## Câu hỏi cần user chốt

1. **G12:** `notes` của buổi học có công khai cho Student/Parent không (đề xuất: không, dùng `lesson_content` làm nội dung công khai)? Ẩn `review_required` khỏi hai vai trò này ở BE?
2. **Học sinh có được xem lại bài làm và đáp án đúng** (ST-07b) **trước khi công bố điểm không?** BE hiện cho đọc lượt của chính mình bất cứ lúc nào (kể cả `result.score` chưa công bố qua response nộp bài).
3. **Phụ huynh nhiều con:** ưu tiên G9 (lớp theo con) trước khi vẽ PR-03/PR-04, vì không phân biệt được lớp của con nào.
4. **Gửi nhiều đơn cho cùng một buổi / gửi đơn cho buổi đã hủy / gửi lại sau khi bị từ chối:** cho phép hay chặn?
5. **Câu `file_upload`:** có hoãn bật cho tới khi G6 xong (đề xuất: ẩn khỏi bài giao ở V1 đầu)?
6. **Q4:** học sinh nhỏ chưa có tài khoản làm bài thế nào (ảnh hưởng toàn bộ cổng Student).
