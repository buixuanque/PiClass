# Danh sách màn hình FE (bản nháp 2)

> **Chi tiết từng màn hình** (khối nội dung, dữ liệu, hành động, trạng thái, lỗi, dữ liệu mẫu) nằm ở [screens/](screens/): [common](screens/common.md) · [admin](screens/admin.md) · [teacher-van-hanh](screens/teacher-van-hanh.md) · [teacher-bai-danh-gia](screens/teacher-bai-danh-gia.md) · [student-parent](screens/student-parent.md). **API cần bổ sung hoặc sửa:** [screens/api-gaps.md](screens/api-gaps.md). File này giữ vai trò mục lục và bảng trạng thái.

> **Mục đích:** nguồn để thiết kế mockup hàng loạt (Claude Design) và để lập Plan FE. Dựng từ: backend hiện có (`piclass-api/routes/api.php`, 124 endpoint), `product/feature-map.md`, `flows/*`, và nghiệp vụ trong `product-definition.md`, `requirements/*`. Mockup mẫu (`piclass-mockup/sample`) chỉ dùng để hình dung giao diện sau khi có design system, **không phải nguồn yêu cầu**; dấu 🎨 chỉ nghĩa là "đã có hình dung". **Chưa duyệt** — cần bạn chốt các mục 🔵 và 🟡 trước khi coi là phạm vi.
>
> **Thứ tự ưu tiên tài liệu:** `requirements/*` > `access-control/*` > `database/*` > tài liệu này. Chức năng ngoài `requirements/*` (🔵) phải qua Decision Log trước khi làm.

## Cách đọc

| Ký hiệu | Nghĩa |
|---|---|
| ✅ | Backend đã có API, làm FE được ngay |
| 🟡 | Thuộc phạm vi V1 đã chốt nhưng **thiếu API** (hoặc API chưa đủ); cần BE bổ sung. Có ghi cách ghép tạm nếu có |
| 🔵 | **Đề xuất mới**, ngoài phạm vi đã chốt; cần quyết định trước khi làm |
| ⛔ | Ngoài V1 (`product-definition.md`: chat, gamification, thanh toán, AI, video…) — không làm |
| 🎨 | Đã có trong mockup mẫu |

Mã màn hình: `CM` chung · `AD` Admin · `TC` Teacher · `ST` Student · `PR` Parent.

## Quy ước chung cho mọi màn hình (đưa vào brief)

- **Trạng thái bắt buộc:** đang tải, rỗng, lỗi (có nút thử lại), không có quyền (403/404 cùng thông điệp "Request failed."), mất phiên (401 → về đăng nhập).
- **Lỗi form:** API trả 422 theo từng trường (`error.details.<trường>`), hiện ngay dưới trường; lỗi nghiệp vụ trả `error.code` + `error.message` (danh mục ở `architecture/error-codes.md`), hiện dạng thông báo.
- **Vai trò:** một người có thể giữ nhiều vai trò; luôn có chỗ đổi vai trò đang hoạt động (CM-04). Điều hướng thay đổi theo vai trò.
- **Thời gian:** hiển thị giờ Việt Nam (+07:00). **Thông báo** là kéo về (không realtime). **Danh sách** hiện chưa phân trang (trừ `GET /users`).
- **Responsive:** mọi cổng dùng tốt trên điện thoại, máy tính bảng và web máy tính (Student/Parent dùng cả hai; Teacher/Admin chủ yếu web máy tính, riêng điểm danh và chấm bài phải dùng tốt trên máy tính bảng). Không mất chức năng nào khi đổi khổ màn hình.
- **File đính kèm:** tối đa 10 MB; pdf, doc/docx, jpg, png, zip. Link tải hết hạn sau 10 phút.

---

## CM — Chung cho mọi vai trò

| Mã | Màn hình | Chức năng | API | Trạng thái |
|---|---|---|---|---|
| CM-01 | Đăng nhập | Nhập tài khoản + mật khẩu, ghi nhớ đăng nhập; báo sai thông tin, tạm khóa do nhập sai nhiều lần (429), tài khoản bị vô hiệu; sau đăng nhập chọn vai trò nếu có nhiều | `POST /auth/login` | ✅ |
| CM-02 | Quên mật khẩu | Nhập email; luôn báo "nếu tồn tại, đã gửi hướng dẫn" | `POST /auth/password/forgot` | ✅ |
| CM-03 | Đặt lại mật khẩu | Mở từ email (token); mật khẩu mới ≥ 8 ký tự | `POST /auth/password/reset` | ✅ |
| CM-04 | Chọn / đổi vai trò | Danh sách vai trò đang giữ, đổi vai trò đang hoạt động (menu hoặc modal) | `GET /auth/me`, `POST /auth/context` | ✅ |
| CM-05 | Hồ sơ cá nhân | Xem/sửa email, số điện thoại (Student/Teacher/Parent; Admin không dùng); đổi mật khẩu; đăng xuất. Ảnh đại diện: xem 🔵 | `GET/PATCH /me/profile`, `PATCH /me/password`, `POST /auth/logout` | ✅ (ảnh đại diện 🔵) |
| CM-06 | Trung tâm thông báo | Chuông + huy hiệu chưa đọc; danh sách lọc đã/chưa đọc; đánh dấu đã đọc; bấm để mở màn hình đích theo `context` (`assessment`, `lesson_session`, `result`, `schedule`) | `GET /me/notifications`, `PATCH /notifications/{id}/read` | ✅; "đánh dấu tất cả đã đọc", đếm chưa đọc 🔵 |
| CM-07 | Trang lỗi | 403/404, 500, hết phiên | — | ✅ (thiết kế) |
| CM-08 | Xem / tải file | Tải file đính kèm của đề, câu hỏi (Student/Parent theo visibility) | `GET /attachments/{id}` | ✅ |

## AD — Cổng Admin

| Mã | Màn hình | Chức năng | API | Trạng thái |
|---|---|---|---|---|
| AD-01 | Tổng quan | Số học viên, giáo viên, lớp; lịch hôm nay; điểm danh hôm nay; bài chờ chấm; bài sắp hạn; thống kê cơ bản | (ghép tạm: `GET /users?role=…` lấy tổng, `GET /classes`) | 🟡 cần API tổng hợp |
| AD-02 | Tài khoản | Bảng có phân trang, lọc trạng thái / vai trò / tên / email | `GET /users` | ✅ |
| AD-03 | Chi tiết tài khoản | Xem vai trò đang giữ; gán / kết thúc vai trò; kích hoạt / vô hiệu hóa (chặn Admin cuối `LAST_ADMIN`, chặn Teacher còn phân công `ACTIVE_ASSIGNMENTS_EXIST`) | `GET/PATCH /users/{id}`, `POST /users/{id}/roles`, `DELETE /users/{id}/roles/{roleId}` | ✅ |
| AD-04 | Học viên (danh sách) | Danh sách, tìm theo tên, lọc trạng thái, nút tạo | `GET /users?role=student` | 🟡 hồ sơ **chưa có tài khoản** không nằm trong `/users`; cần API danh sách hồ sơ |
| AD-05 | Học viên (tạo / sửa / chi tiết) | Tạo hồ sơ, sửa thông tin & trạng thái, mời tạo tài khoản (gửi email), xem lớp đang học, phụ huynh liên kết, tiến độ | `POST /students`, `PATCH /students/{id}`, `GET /students/{id}`, `PATCH /person-profiles/{id}`, `POST /person-profiles/{id}/invite`, `GET /students/{id}/progress` | ✅ |
| AD-06 | Phụ huynh | Danh sách (cùng hạn chế như AD-04), tạo / sửa / chi tiết; liên kết và kết thúc liên kết với học viên | `POST /parents`, `PATCH /parents/{id}`, `GET /parents/{id}`, `GET/POST /parent-student-relationships`, `DELETE …/{id}` | ✅ (danh sách 🟡) |
| AD-07 | Giáo viên | Danh sách (như AD-04), tạo / sửa / chi tiết (lớp đang dạy); vô hiệu hóa bị chặn khi còn phân công | `POST /teachers`, `PATCH /teachers/{id}`, `GET /teachers/{id}` | ✅ (danh sách 🟡) |
| AD-08 | Môn học | Danh sách, tạo, sửa, ngừng sử dụng | `GET/POST /subjects`, `PATCH /subjects/{id}` | ✅ |
| AD-09 | Lớp học (danh sách) | Lọc trạng thái (`preparing`/`active`/`archived`) và môn; tạo lớp | `GET/POST /classes` | ✅ |
| AD-10 | Chi tiết lớp (tab) | **Tổng quan** (sửa, lưu trữ) · **Học sinh** (thêm, kết thúc, sửa thành viên) · **Giáo viên** (phân công, kết thúc) · **Lịch** · **Buổi học** | `GET/PATCH /classes/{id}`, `…/members`, `…/teachers`, `…/schedules`, `…/lesson-sessions` | ✅ |
| AD-11 | Lịch & buổi học | Lịch tuần/tháng toàn hệ thống, lọc theo lớp / giáo viên | chỉ có theo từng lớp: `GET /classes/{id}/lesson-sessions` | 🟡 cần API lịch tổng hợp |
| AD-12 | Khóa học, đối tượng & mục khóa | Danh sách, tạo, sửa; thêm/gỡ đối tượng (Lớp hoặc Học sinh); xem mục (bài) của khóa — `DEC-LRN-026` (bản ghi danh cũ đã bỏ) | `GET/POST /courses`, `PATCH /courses/{id}`, `…/audience` (tên sẽ chốt ở plan BE) | 🔁 làm lại |
| AD-13 | Cấp lại lượt làm bài | Từ chi tiết bài nộp: chọn lượt, nhập lý do, cấp lại | `POST /attempts/{id}/reissue` | ✅ |
| AD-14 | Nhật ký audit | Bảng lọc `entity_type` / `entity_id` / `action`; xem chi tiết trước/sau (JSON), lý do, người thực hiện | `GET /audit-logs` | ✅ (chưa phân trang) |
| — | Dùng chung với Teacher | Buổi học & điểm danh (TC-05), đơn xin phép (TC-06), ngân hàng câu hỏi (TC-07/08), bài đánh giá (TC-09…13) | — | ✅ |

## TC — Cổng Giáo viên

| Mã | Màn hình | Chức năng | API | Trạng thái |
|---|---|---|---|---|
| TC-01 | Tổng quan 🎨 | "Cần xử lý" (bài chờ chấm, đơn xin phép chờ duyệt, buổi chưa điểm danh), buổi dạy tuần này, bài sắp hạn, chuyên cần trung bình, lịch tuần | (ghép tạm: buổi dạy từ các lớp, `GET /assessments`, `…/submissions`) | 🟡 cần API tổng hợp |
| TC-02 | Lớp của tôi 🎨 | Danh sách, lọc, mở chi tiết. **Teacher không tạo lớp được** (chỉ Admin) — nút "Tạo lớp" trong mockup mẫu cần bỏ hoặc đổi | `GET /classes` | ✅ (Teacher không tạo lớp được: bỏ nút "Tạo lớp" của mockup mẫu) |
| TC-03 | Chi tiết lớp | Học sinh (chỉ xem), lịch, buổi học, bài đánh giá của lớp, tiến độ từng học sinh | `GET /classes/{id}`, `…/members`, `GET /students/{id}/progress` | ✅ |
| TC-04 | Lịch dạy 🎨 | Tuần / tháng; thêm buổi lẻ hoặc buổi bù; tạo lịch lặp hàng tuần (cảnh báo trùng lịch); sửa, hủy, hoàn tất buổi | `POST /classes/{id}/lesson-sessions`, `PATCH`, `…/cancel`, `…/complete`, `POST /classes/{id}/schedules`, `PATCH /schedules/{id}`, `…/cancel` | ✅ (lịch tổng hợp nhiều lớp 🟡) |
| TC-05 | Buổi học & điểm danh 🎨 | Danh sách học sinh, đặt trạng thái hàng loạt và sửa từng bản ghi, ghi nội dung / ghi chú, hoàn tất buổi. Lỗi: buổi đã hủy (`SESSION_CANCELLED`), học sinh không còn trong lớp (`STUDENT_NOT_ELIGIBLE`) | `GET/PUT /lesson-sessions/{id}/attendance`, `PATCH /attendance-records/{id}`, `GET/PATCH /lesson-sessions/{id}` | 🟡 A1: `GET`/`PUT` điểm danh không trả `id` bản ghi nên chưa sửa được từng em |
| TC-06 | Đơn xin phép | Danh sách chờ duyệt, chấp nhận / từ chối | `GET /absence-explanations`, `PATCH /absence-explanations/{id}` | ✅ |
| TC-07 | Ngân hàng câu hỏi | Danh sách; lọc môn, loại, topic, tag, "của tôi", trạng thái | `GET /questions`, `GET /topics`, `GET /tags` | ✅ |
| TC-08 | Soạn câu hỏi | 9 loại câu (một đáp án, nhiều đáp án, đúng/sai, điền khuyết, trả lời ngắn, tự luận, nối, sắp xếp, nộp file); nội dung, đáp án, cách chấm, topic / tag; mỗi lần sửa tạo phiên bản mới; lưu trữ | `POST/GET/PATCH/DELETE /questions`, `PATCH …/topics`, `PATCH …/tags` | ✅ |
| TC-09 | Danh sách bài 🎨 | Bài tập / trắc nghiệm / thi; lọc loại, lớp, trạng thái (nháp, đã phát hành, đã đóng, đã hủy) | `GET /assessments` | ✅ |
| TC-10 | Soạn & giao bài (nhiều bước) 🎨 | (1) Thông tin: loại, tên, hướng dẫn, giờ mở / hạn / đóng, thời lượng, số lượt, cách tính điểm (cao nhất / mới nhất), trộn câu và đáp án, hiện đáp án / giải thích / điểm từng câu, điểm đạt · (2) Câu hỏi: thêm từ ngân hàng, chỉnh điểm, xóa, sắp xếp · (3) Đối tượng: lớp, học sinh (bỏ khóa học, `DEC-LRN-026`) · (4) Xem trước & phát hành · tạo phiên bản mới, đóng, mở lại, hủy | `POST /assessments`, `PATCH`, `…/questions` (POST/PATCH/DELETE), `…/audience`, `…/publish`, `…/versions`, `…/close`, `…/reopen`, `…/cancel` | ✅. "Nộp ảnh hoặc file" 🟡 (issue #50) |
| TC-11 | Chi tiết bài | Tổng quan, đối tượng, danh sách học sinh theo bài (đã làm / chưa nộp), gia hạn hoặc ngoại lệ riêng cho từng học sinh | `GET /assessments/{id}`, `…/students`, `PATCH …/students/{studentId}` | ✅ |
| TC-12 | Bài nộp của một bài | Danh sách bài nộp, lọc trạng thái chấm | `GET /assessments/{id}/submissions` | ✅ |
| TC-13 | Chấm bài | Xem bài làm từng câu, chấm điểm từng câu / tổng, viết nhận xét (nháp → công bố → rút lại), lưu và chuyển bài kế tiếp, công bố hoặc rút kết quả (từng bài hoặc hàng loạt) | `GET /submissions/{id}`, `PUT …/questions/{q}/grade`, `PUT …/grade`, `POST …/feedback`, `PUT/PATCH /feedback/{id}`, `POST /results/{id}/publish`, `…/withdraw`, `POST /assessments/{id}/results/publish` | ✅. File bài làm của học sinh 🟡 (issue #50) |
| TC-14 | Tiến độ học sinh 🎨 | Điểm các bài gần đây, điểm trung bình, bài đã nộp; điểm danh 10 buổi gần nhất, chuyên cần | `GET /students/{id}/progress` | ✅ trung bình, số bài, theo môn · 🟡 "bài gần đây" và biểu đồ (C4) · 🟡 điểm danh theo học sinh (A9) |
| TC-15 | Khóa học của tôi | Danh sách, đối tượng (Lớp/Học sinh), mục khóa; soạn bài mới cho khóa hoặc lấy từ bài của tôi — `DEC-LRN-026` | `GET/POST /courses`, `…/audience`, `…/items` (tên sẽ chốt ở plan BE) | 🔁 làm lại |

## ST — Cổng Học sinh

| Mã | Màn hình | Chức năng | API | Trạng thái |
|---|---|---|---|---|
| ST-01 | Tổng quan 🎨 | Chào, buổi học hôm nay, bài sắp đến hạn | (ghép tạm: lớp của em → buổi học, `GET /me/assessments`) | 🟡 cần API tổng hợp. "Chuỗi ngày liên tiếp" ⛔ |
| ST-02 | Lịch học 🎨 | Tuần / tháng; chi tiết buổi (nội dung, giáo viên, trạng thái, điểm danh của mình) | `GET /classes`, `GET /classes/{id}/lesson-sessions`, `GET /lesson-sessions/{id}`, `…/attendance` | ✅ (lịch tổng hợp 🟡) |
| ST-03 | Bài tập 🎨 | Danh sách; chip trạng thái: chưa làm, chờ chấm, đã chấm; hạn nộp | `GET /me/assessments` (lọc loại ở FE) | ✅ |
| ST-04 | Bài kiểm tra 🎨 | Như ST-03 cho trắc nghiệm / thi; hiện thời lượng, số câu, giờ mở | `GET /me/assessments` | 🟡 A3: chi tiết bài thiếu thời lượng, số câu, số lượt |
| ST-05 | Chi tiết bài | Hướng dẫn, hạn, thời lượng, số lượt còn lại, điểm lượt trước; file đề đính kèm; nút "Làm bài" | `GET /me/assessments/{id}`, `GET /attachments/{id}`, `POST /me/assessments/{id}/attempts` | 🟡 A3, A4: thiếu hướng dẫn, số lượt đã dùng, tệp đính kèm; chưa khôi phục được lượt đang dở |
| ST-06 | Làm bài 🎨 | Hiện câu hỏi theo loại; **tự lưu từng câu**; đồng hồ đếm ngược; bảng điều hướng câu và đánh dấu; nộp bài có xác nhận; hết giờ tự nộp | `GET /me/attempts/{id}`, `PUT /me/attempts/{id}/answers/{q}`, `POST /me/attempts/{id}/submit` | ✅. Câu nộp file 🟡 (issue #50) |
| ST-07 | Kết quả 🎨 | Danh sách; chi tiết: điểm, từng câu đúng / sai, đáp án và giải thích (nếu giáo viên cho xem), nhận xét đã công bố | `GET /me/assessments/{id}/result` | 🟡 A5: chỉ có điểm tổng và nhận xét, chưa có đúng/sai từng câu |
| ST-08 | Tiến độ học tập | Biểu đồ điểm theo thời gian, điểm trung bình | `GET /me/progress` | ✅ trung bình và theo môn · 🟡 biểu đồ theo thời gian (C4) |
| ST-09 | Khóa học của em | Danh sách khóa em thuộc đối tượng (trực tiếp hoặc qua lớp) và mục của khóa — `DEC-LRN-026` | `GET /courses` | 🔁 làm lại |

## PR — Cổng Phụ huynh

| Mã | Màn hình | Chức năng | API | Trạng thái |
|---|---|---|---|---|
| PR-01 | Chọn con | Danh sách con đang liên kết, đổi con xem | `GET /me/children`, `GET /me/children/{id}` | ✅ |
| PR-02 | Tổng quan 🎨 | Buổi học hôm nay của con, bài sắp hạn, nhận xét mới nhất | (ghép tạm từ các API bên dưới) | 🟡 cần API tổng hợp |
| PR-03 | Lịch học của con 🎨 | Như ST-02 cho con | `GET /classes/{id}/lesson-sessions` (cần biết lớp của con) | 🟡 A6, A8: chưa biết lớp của từng con |
| PR-04 | Điểm danh của con 🎨 | 10 buổi gần nhất, tỷ lệ chuyên cần, buổi vắng | chưa có API theo học sinh | 🟡 A9 |
| PR-05 | Bài tập & kết quả 🎨 | Danh sách bài của con, chi tiết, kết quả (chỉ nội dung đã công bố cho phụ huynh) | `GET /me/children/{id}/assessments`, `…/{assessmentId}`, `…/result` | ✅ |
| PR-06 | Nhận xét 🎨 | Nhận xét giáo viên đã công bố cho phụ huynh (nằm trong kết quả) | trong `…/result` | ✅ theo từng bài; danh sách nhận xét tổng hợp 🟡 |
| PR-07 | Tiến độ của con | Điểm trung bình, điểm các bài gần đây | `GET /me/children/{id}/progress` | ✅ trung bình và theo môn · 🟡 "bài gần đây" (C4) |
| PR-08 | Đơn xin phép | Gửi đơn cho một buổi học (chọn buổi, lý do); xem trạng thái các đơn đã gửi | `POST /absence-explanations`, `GET /absence-explanations` | ✅ |

---

## Phần bổ sung đề xuất

### A. Cần BE bổ sung (thuộc phạm vi V1, đang thiếu API) — 🟡

> Danh sách đầy đủ, xếp theo mức độ và gồm cả các khoảng trống phát hiện thêm khi đặc tả chi tiết (mã GM, TB, N): [screens/api-gaps.md](screens/api-gaps.md).

| Mã | Nội dung | Vì sao cần | Gợi ý |
|---|---|---|---|
| G1 | **Tổng quan theo cổng** (AD-01, TC-01, ST-01, PR-02) | Trang chủ cần dữ liệu gộp; ghép tạm từ nhiều API sẽ chậm và N+1 | Một endpoint tổng quan cho mỗi vai trò |
| G2 | **Lịch theo người dùng** (AD-11, TC-04, ST-02, PR-03) | Hiện chỉ có lịch theo từng lớp; xem tuần của cả người cần lặp qua mọi lớp | `GET /me/lesson-sessions?from&until` |
| G3 | **Điểm danh theo học sinh** (PR-04, TC-14, ST-02) | Hiện chỉ có điểm danh theo từng buổi | `GET /students/{id}/attendance`, `GET /me/attendance` |
| G4 | **Danh sách hồ sơ** học viên / phụ huynh / giáo viên (AD-04/06/07) | `/users` chỉ liệt kê hồ sơ đã có tài khoản | `GET /students`, `/parents`, `/teachers` (có tìm kiếm, phân trang) |
| G5 | **Phân trang** thông báo, audit, các danh sách lớn | Danh sách tăng theo thời gian | Thống nhất `meta` như `GET /users` |
| G6 | **Nộp / xem file bài làm và feedback** (ST-06, TC-13) | Câu `file_upload` chưa dùng được | Issue #50 (cần chốt số file tối đa mỗi câu) |
| G7 | **Danh sách nhận xét theo con** (PR-06) | Phụ huynh muốn đọc nhận xét một chỗ | Lọc feedback đã công bố theo học sinh |
| G8 | **Lịch sử điểm** (ST-08, TC-14, PR-07) | `…/progress` chỉ trả trung bình, số bài và trung bình theo môn; không có điểm theo thời gian hay "bài gần đây" | Danh sách kết quả đã công bố: tên bài, ngày, %, môn |
| G9 | **Làm giàu response danh sách** (nhiều màn) | Lớp không có tên môn/giáo viên/sĩ số; buổi không có tên lớp; bài không có đối tượng/số đã nộp; con của phụ huynh không có lớp | Thêm trường hoặc tham số `include`, thay vì gọi từng id |
| G10 | **Thông báo** (CM-06) | Đếm chưa đọc, đánh dấu tất cả đã đọc (đã nêu ở N1); `context` của một số thông báo thiếu id đích | Chuyển N1 vào đây |
| G11 | **Lời mời tài khoản** (CM-09, AD-05b/c) | Token mời dùng chung với đặt lại mật khẩu, hết hạn sau 60 phút; mời lần hai bị từ chối | Màn kích hoạt riêng, "gửi lại lời mời", Admin gửi link đặt mật khẩu hộ |
| G12 | **Quyền hiển thị buổi học** | `GET /lesson-sessions/{id}` trả `notes` và `review_required` cho cả Student/Parent | Chốt `notes` có công khai không; ẩn `review_required` |
| G13 | **Địa điểm buổi học / lớp** (Q1) | Chưa có trường nào | Thêm `location` cho lớp, cho phép ghi đè ở từng buổi |
| G14 | **Nhân bản bài đánh giá** (N4) | Giáo viên giao lại cùng đề cho lớp/kỳ khác | Đã nhận ở N4, xếp vào nhóm này |

### B. Đề xuất mới, ngoài phạm vi đã chốt — 🔵 (cần quyết định; cột cuối là khuyến nghị theo nghiệp vụ)

| Mã | Chức năng | Giá trị nghiệp vụ (lớp học thêm) | Khuyến nghị |
|---|---|---|---|
| N1 | Đánh dấu tất cả thông báo đã đọc + đếm chưa đọc | V1 chỉ có thông báo trong ứng dụng (`BR-NOTIF-001`), nên chuông thông báo là kênh duy nhất; phải dễ dùng | **Nhận** (nhỏ) |
| N4 | Nhân bản bài đánh giá sang lớp / kỳ khác | Giáo viên dùng lại cùng một đề cho nhiều lớp, nhiều kỳ; thao tác lặp hằng tuần | **Nhận** (nhỏ) |
| N9 | Lọc / tìm trong từng danh sách (học sinh, lớp, bài) | Lớp học thêm vài chục đến vài trăm học viên; không tìm được là không dùng được | **Nhận** (vừa) |
| N6 | Nhận xét theo tuần / tháng cho phụ huynh (không gắn với bài) | V1 không có chat (⛔), nên đây là kênh chính để giáo viên báo tình hình cho phụ huynh; hiện BE chỉ có nhận xét gắn với bài làm | **Nên cân nhắc nhận**, cần thêm yêu cầu và bảng dữ liệu |
| N2 | Nhắc bài sắp đến hạn (thông báo tự động) | Giảm nộp muộn; có thể hiểu thuộc `BR-NOTIF-002` ("sự kiện cần hành động") nhưng vì chỉ có thông báo trong ứng dụng nên hiệu quả phụ thuộc người dùng có mở app không | **Hoãn**, làm sau khi lõi chạy ổn |
| N3 | Xuất bảng điểm / điểm danh của lớp ra CSV | Giáo viên lưu sổ, gửi báo cáo; chỉ cần xuất cơ bản (phân tích nâng cao ⛔) | **Hoãn** |
| N5 | Gợi ý "học sinh cần chú ý" (vắng liên tiếp N buổi, nhiều bài chưa nộp) | Giúp giáo viên phát hiện sớm; tính được ở FE khi có G3 | **Hoãn** đến sau G3 |
| N7 | Ghi chú nội bộ của giáo viên về học sinh | Ít cấp bách; cần quy tắc ai được xem | **Bỏ khỏi V1** |
| N8 | Ảnh đại diện | Không ảnh hưởng vận hành lớp | **Bỏ khỏi V1** |

### C. Điểm cần chốt theo nghiệp vụ (độc lập với mockup)

| # | Câu hỏi | Vì sao ảnh hưởng | Khuyến nghị |
|---|---|---|---|
| Q1 | **Địa điểm của buổi học / lớp** (phòng, cơ sở, tại nhà): có lưu không? Hiện `classes`, `schedules`, `lesson_sessions` **không có** trường này | V1 **không có lớp trực tuyến** (⛔), nghĩa là mọi buổi học đều ở một nơi thật; học sinh và phụ huynh cần biết đi đâu, nhất là khi đổi phòng hoặc học bù. Lịch học không có địa điểm là lịch thiếu | **Nên thêm** `location` cho lớp, cho phép ghi đè ở từng buổi (đổi database + requirements) |
| Q2 | **Loại lớp** (nhóm / kèm 1-1) có cần là trường riêng không | Chỉ để lọc / hiển thị; có thể suy ra từ số học sinh hoặc dùng `label` có sẵn | **Chưa cần** trường riêng |
| Q3 | **Quyền tạo lớp** của Teacher: BE chỉ cho Admin | Trong V1 một tài khoản thường mang cả Admin và Teacher (`product-definition.md`), nên khác biệt ít; nhưng giáo viên thuần thì phải nhờ Admin | **Giữ nguyên**: Admin tạo lớp |
| Q4 | **Học sinh nhỏ tuổi không có tài khoản** thì làm / nộp bài thế nào | Các endpoint làm bài đều yêu cầu đăng nhập vai trò Student; hồ sơ học viên có thể tồn tại không tài khoản. Nếu nhiều học sinh không có tài khoản thì cần luồng giáo viên nhập điểm hộ hoặc phụ huynh giúp con | **Cần xác nhận với nghiệp vụ thực tế** trước khi vẽ luồng làm bài |
| Q5 | **Kênh báo tin cho phụ huynh** khi V1 chỉ có thông báo trong ứng dụng (không email / SMS / push, `BR-NOTIF-001`) | Phụ huynh không mở app thì không biết bài mới, kết quả, đổi lịch. Đây là rủi ro sản phẩm lớn hơn bất kỳ màn hình nào | Ghi rõ trong định hướng sau V1 (email hoặc Zalo); ở V1, trang chủ phụ huynh phải trả lời ngay câu "có gì mới" |
| Q6 | **Gộp cổng Admin và Teacher** khi một tài khoản có cả hai vai trò | Cùng một người dùng cả hai; hai cổng tách biệt buộc phải đổi vai trò liên tục | **Một khung điều hướng**: phần "Giảng dạy" và phần "Quản trị" cùng nằm trong một giao diện khi có cả hai vai trò |

## Màn hình thêm khi đặc tả chi tiết

| Mã | Màn hình | Lý do | Trạng thái |
|---|---|---|---|
| CM-09 | Kích hoạt tài khoản lần đầu | Lời mời cần màn riêng, văn bản khác đặt lại mật khẩu, có trạng thái link hết hạn | 🟡 G11 |
| CM-10 | Khung điều hướng theo vai trò | Menu từng vai trò, chuông thông báo, đổi vai trò, đăng xuất; gộp "Giảng dạy / Quản trị" nếu giữ cả hai vai trò (Q6) | ✅ (phần gộp 🔵) |
| AD-15 | Checklist thiết lập lần đầu | Hệ thống mới rỗng; dẫn Admin qua môn → lớp → giáo viên → học sinh → lịch | 🔵 |
| AD-05c | Gửi lại lời mời | Lời mời hết hạn sau 60 phút | 🔵 🟡 G11 |
| ST-06b | Đã nộp bài | Xác nhận sau khi nộp hoặc hết giờ tự nộp | ✅ |
| ST-07b | Xem lại bài làm | Xem từng câu sau khi có điểm | 🟡 A5 |
| TC-03 (tab) | Lịch sử thay đổi | Teacher xem được audit log của lớp mình | 🔵 |

Các màn AD-05/06/07 được tách thành chi tiết, form, mời tài khoản; AD-10 thành khung + 5 tab (10a…10e); AD-12 thành 12 và 12a. Chi tiết ở `screens/admin.md`.

---

## Ưu tiên theo nghiệp vụ

Trong lớp học thêm, vòng lặp dùng hằng ngày là: *chuẩn bị buổi học → điểm danh → giao bài → chấm bài → phụ huynh xem kết quả*. Admin chỉ thiết lập một lần (người dùng, lớp, môn), ít dùng hằng ngày nên giữ đơn giản.

| Lát | Nội dung | Màn hình chính | Phụ thuộc BE |
|---|---|---|---|
| 1. Vận hành lớp | Đăng nhập, thiết lập (người dùng, lớp, lịch), điểm danh, Student / Parent xem lịch và điểm danh | CM-01…08, AD-02…12, TC-04/05/06, ST-02, PR-03/04/08 | G2, G3, G4 |
| 2. Giao và làm bài | Ngân hàng câu hỏi, soạn & giao bài, học sinh làm bài, chấm, công bố, xem kết quả | TC-07…13, ST-03…07, PR-05/06 | G6 (câu nộp file) |
| 3. Theo dõi | Tổng quan từng cổng, tiến độ, thông báo, nhật ký | AD-01, TC-01/14, ST-01/08, PR-02/07, AD-14 | G1, G5, G7 |

Vì BE của 5 nhóm module đã xong, FE có thể làm theo **lát theo giá trị người dùng** như trên, thay vì theo thứ tự nhóm module của Plan 1b–5b.

---

## Thứ tự gợi ý làm mockup hàng loạt

1. **Đã có:** các màn hình mẫu để hình dung (🎨), dùng cùng design system.
2. **Lô 1 — Nền tảng & xác thực:** CM-01 → CM-08 + khung điều hướng Admin (Admin chưa có mockup nào).
3. **Lô 2 — Admin CRUD:** AD-02 … AD-12. Các màn dạng bảng + form giống nhau nên chỉ cần vài mẫu đại diện (danh sách có lọc, chi tiết nhiều tab, form tạo / sửa) rồi áp dụng lại.
4. **Lô 3 — Giáo viên luồng khó:** TC-07/08 (ngân hàng & soạn câu hỏi), TC-10 (soạn & giao bài), TC-11/12/13 (chấm bài). Đây là phần phức tạp nhất, nên mockup kỹ.
5. **Lô 4 — Học sinh & Phụ huynh còn thiếu:** ST-05, ST-07 (chi tiết), ST-08, PR-04, PR-07, PR-08, các trạng thái rỗng / lỗi.

## Mẫu brief cho mỗi màn hình (điền khi giao cho Claude Design)

```
Màn hình: <mã + tên>            Vai trò: <Admin|Teacher|Student|Parent>
Mục tiêu người dùng: <1 câu, theo ngữ cảnh lớp học thêm>
Dữ liệu hiển thị: <tên trường, lấy từ API nào>
Hành động: <nút / thao tác, kèm điều kiện hiện / ẩn>
Trạng thái cần vẽ: tải · rỗng · lỗi · không có quyền · (nếu có) thành công
Quy tắc / lỗi nghiệp vụ cần thể hiện: <error.code → thông điệp>
Thiết bị ưu tiên: <điện thoại | máy tính>
Ghi chú: <🟡 thiếu API → vẽ theo dự kiến; 🔵 chưa duyệt → không vẽ>
```

Ví dụ điền sẵn — **TC-05 Buổi học & điểm danh:** Teacher; mục tiêu: điểm danh nhanh cả lớp trong đầu buổi; dữ liệu: tên học sinh, trạng thái điểm danh (có mặt, đi muộn, vắng, có phép), ghi chú, nội dung buổi; hành động: "đặt tất cả có mặt", chọn trạng thái từng em, lưu (bắt buộc chọn đủ trước khi lưu), hoàn tất buổi; lỗi: buổi đã hủy → khóa nút lưu và hiện lý do; học sinh không còn trong lớp → báo lỗi dòng đó; thiết bị: máy tính bảng.
