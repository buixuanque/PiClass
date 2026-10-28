# API cần bổ sung hoặc sửa để khớp với màn hình

> Gộp từ năm file đặc tả màn hình trong thư mục này (`common.md`, `admin.md`, `teacher-van-hanh.md`, `teacher-bai-danh-gia.md`, `student-parent.md`) sau khi đối chiếu với code ngày 2026-10-22. Mỗi dòng ghi **mã gốc** (G, GM, TB, N) để tra lại đặc tả chi tiết. `G1…G14` được định nghĩa ở `../screen-inventory.md`.
>
> **Cách dùng:** vẽ mockup theo đặc tả (phần 🟡 vẽ theo dự kiến), còn BE làm theo thứ tự mức A → B → C. Mỗi mục khi chốt sẽ thành một issue riêng ở repo docs.

## Mức A — Chặn một thao tác chính (UI vẽ được nhưng không chạy được)

| # | Việc cần làm | Mã gốc | Vì sao chặn | Màn hình |
|---|---|---|---|---|
| A1 | ~~Trả `id` của bản ghi điểm danh (và `lesson_session_id`) trong `GET` và `PUT /lesson-sessions/{id}/attendance`~~ **Xong 2026-10-28** (`DEC-LRN-017`) | G15 | `PATCH /attendance-records/{id}` cần id nhưng không endpoint nào trả; không sửa được điểm danh từng học sinh | TC-05 |
| A2 | ~~Cho kết thúc vai trò theo mã role, hoặc trả `role_id` ở `GET /users/{id}`~~ | GM-6 | **Đã xong 2026-10-25** (`piclass-api` nhánh `feat/invite-auto-assign-role`): `GET /users/{id}` trả `active_roles: [{role_id, role}]`, `role_id` dùng thẳng cho `DELETE /users/{id}/roles/{roleId}` (có test end-to-end). | AD-03 |
| A3 | Trả chi tiết bài cho Student/Parent: hướng dẫn, thời lượng, số câu, số lượt tối đa và đã dùng, hạn riêng, nguồn lớp/khóa, tệp đính kèm | N-A, N-D | `GET /me/assessments/{id}` chỉ có tên, loại, giờ, điểm tối đa; không vẽ được màn "chi tiết bài" và "thông tin trước khi làm" | ST-04, ST-05, PR-05 |
| A4 | Tìm lại lượt đang làm dở (`GET /me/assessments/{id}/attempts` hoặc trả lượt `in_progress` ở chi tiết bài); thêm `attempt.id` vào lịch sử lượt | N-E, N-B | Mất `attempt.id` (đổi máy, xóa dữ liệu trình duyệt) là mất bài và tốn một lượt, trái BR-ASSESS-018 | ST-05, ST-06, ST-07 |
| A5 | Kết quả chi tiết theo câu cho Student/Parent (điểm từng câu, đáp án đúng, giải thích), tôn trọng `show_answers`, `show_explanations`, `show_question_scores` | N-B | `…/result` chỉ có điểm tổng và nhận xét, nên không có màn "xem lại bài" | ST-07, ST-07b, PR-05 |
| A6 | Lịch theo người dùng: `GET /me/lesson-sessions?from&until`, mỗi buổi kèm tên lớp, môn, địa điểm | G2, G13 | Lịch tuần của một người phải lặp qua mọi lớp; buổi học không có tên lớp | TC-01, TC-04, ST-01, ST-02, PR-02, PR-03, AD-11 — **phần Admin xong 2026-10-25** (`piclass-api` nhánh `feat/my-lesson-sessions-endpoint`, chưa PR): `GET /me/lesson-sessions` dùng chung cho mọi vai trò, phạm vi theo `ClassScope` (Admin thấy toàn hệ thống, dùng ngay cho AD-11); lọc `from/until/class_id/teacher_id/subject_id/hide_cancelled`; mỗi buổi kèm `class_name/subject_name/teacher_names`. Chưa có địa điểm (G13, tạm bỏ theo quyết định nhóm 2). Phần Teacher/Student/Parent tái dùng được luôn, chưa cần sửa gì thêm khi tới lượt các cổng đó. **FE AD-11 xong 2026-10-25** (`piclass-web` nhánh `feat/admin-schedule-group4`, chưa PR): màn `MyScheduleScreen` tại `/admin/schedule`, 3 view Ngày/Tuần/Tháng, cờ trùng giờ giáo viên tính ở FE. |
| A7 | ~~Danh sách hồ sơ học viên, phụ huynh, giáo viên (tìm kiếm, phân trang), kèm liên kết hai chiều tài khoản ↔ hồ sơ~~ | G4, GM-1 | `/users` chỉ liệt kê hồ sơ đã có tài khoản; không mở được hồ sơ từ tài khoản | AD-02…AD-07 — **Xong cả hai chiều.** Teacher/Student/Parent: 2026-10-24/25 (`GET /teachers`, `GET /students`, `GET /parents`, kèm `GET /*/{id}` trả `account`, `piclass-api` nhánh `feat/teacher-list-and-classes` + `feat/invite-auto-assign-role`). Chiều ngược (mở hồ sơ từ tài khoản, AD-03): **2026-10-28**, BE (`piclass-api` nhánh `feat/gm1-reverse-profile-link`) + FE (`piclass-web` nhánh `feat/gm1-reverse-profile-link-fe`) — `GET /users/{id}` trả thêm `profile: {type, id}`, AD-03 hiện nút mở hồ sơ. |
| A8 | Danh sách lớp của một người: lớp của từng người con, lớp đang học/dạy của một học viên/giáo viên | G9, GM-2 | Phụ huynh nhiều con không biết lớp nào của con nào; chi tiết học viên không có lớp | PR-01, PR-03, PR-04, AD-05, AD-07a — **giáo viên và học viên xong** (`GET /teachers/{teacher}/classes` 2026-10-24; `classes: [{id,name}]` trong `GET /students`/`GET /students/{id}` 2026-10-25); Phụ huynh nhiều con (PR) còn mở. **Bảng "Lớp học" đầy đủ ở AD-05 — xong 2026-10-25:** `classes` trong `GET /students/{id}` nay có thêm `subject_name`/`teacher_names`/`enrolled_from`; FE đổi chip thành bảng (Lớp \| Môn \| Giáo viên \| Vào lớp từ). |
| A9 | Điểm danh theo học sinh: `GET /students/{id}/attendance`, `GET /me/attendance` | G3 | Chỉ có điểm danh theo từng buổi | PR-04, TC-14, ST-02, AD-05 |
| A10 | Lời mời tài khoản: màn/token riêng cho lời mời (sống lâu hơn 60 phút), "gửi lại lời mời", Admin gửi link đặt mật khẩu hộ, mời có thể gán vai trò luôn | G11, GM-5 | Phụ huynh mở mail hôm sau gặp link hết hạn; mời lần hai bị từ chối | CM-09, AD-05b, AD-05c — **xong** 2026-10-24: BE (`piclass-api` PR #39, G11 đóng) + FE CM-09 (`piclass-web` nhánh `feat/auth-screens`). Admin gửi link hộ dùng `POST /person-profiles/{id}/invitation` đã có; gán vai trò luôn khi mời chưa làm. |
| A11 | File bài làm và file trong nhận xét | G6 (issue #50) | Câu `file_upload` chưa dùng được | ST-06, TC-13 |
| A12 | Làm giàu danh sách bài nộp và danh sách học sinh theo bài: tên học sinh, số lượt, trạng thái đã nộp/đang làm/chưa làm; chi tiết bài nộp kèm đề đúng phiên bản | TB-5, TB-9 | Giáo viên chỉ thấy mã học sinh khi chấm | TC-11, TC-12, TC-13 |

## Mức B — Sai quy tắc nghiệp vụ hoặc lộ dữ liệu (cần sửa, không phải "thêm mới")

| # | Việc cần làm | Mã gốc | Chi tiết |
|---|---|---|---|
| B1 | **Ẩn điểm chưa công bố khỏi Student.** `POST /me/attempts/{id}/submit` và `GET /me/attempts/{id}` trả kèm `result` (có `score`) kể cả khi `publication_status` chưa công bố | N-A (student-parent) | Trái `BR-SYS-007`, `BR-ASSESS-028`. Agent đặc tả và tôi cùng đọc code: `SubmitAttemptController` trả `SubmissionResultResource` với nguyên bản ghi `Result`; **chưa chạy thử**, cần viết test xác nhận trước khi sửa |
| B2 | ~~Quyết định `notes` của buổi học có công khai không; ẩn `review_required` khỏi Student/Parent~~ **Xong 2026-10-28:** `DEC-LRN-017`, cả hai trường chỉ Admin/Teacher thấy | G12 | Trước đó `GET /lesson-sessions/{id}` trả cả hai trường cho mọi vai trò |
| B3 | Cho đặt hẹn giờ công bố điểm (`result_release_at`) và chính sách nộp trễ (`late_policy`) khi tạo/sửa bài; hiện kết quả tự công bố ngay khi chấm xong | TB-6 | Mâu thuẫn `BR-ASSESS-025` ("người tạo chọn lúc công bố") |
| B4 | Kiểm tra phía BE: thứ tự `opens_at ≤ deadline_at ≤ closes_at`, `passing_score ≤ max_score`, tổng điểm câu khớp `max_score`, bài phải có đối tượng trước khi publish, số lựa chọn/đáp án đúng của câu | TB-10 | Hiện chỉ FE tự chặn; gọi API trực tiếp vẫn lọt |
| B5 | Chốt hành vi "phiên bản mới" với học sinh đã được giao; chốt "Mở lại" có kéo dài `closes_at` không; chốt "gia hạn" là kéo dài giờ đóng hay chỉ miễn đánh dấu trễ | TB-7, Q-D | UI không thể vẽ đúng khi chưa biết kết quả |
| B6 | Chốt schema `type_payload` cho câu nối/sắp xếp/điền khuyết nhiều chỗ, ý nghĩa `grading_mode = mixed` | TB-8, Q-B | Form soạn câu hỏi và màn làm bài phụ thuộc vào đây |
| B7 | Lớp `archived` có chặn thêm học viên, giáo viên, lịch không; học viên `inactive` vào lớp (docs nói không được, code không chặn); lưu trữ lớp có tự kết thúc thành viên/lịch không | `ARCH-28(n)` | Câu hỏi nghiệp vụ, đang mở |
| B8 | Bỏ cờ `review_required` sau khi rà soát | G15b | Hạ ưu tiên 2026-10-28: `DEC-LRN-016` chặn hủy buổi đã qua `ends_at`, cờ chỉ còn với buổi đang diễn ra bị hủy và dữ liệu cũ; chưa làm ở V1 |

## Mức C — Tiện dụng, làm sau khi lõi chạy

| # | Việc cần làm | Mã gốc |
|---|---|---|
| C1 | API tổng quan theo cổng và hàng đợi "cần xử lý" (bài chờ chấm, đơn chờ duyệt, buổi chưa điểm danh). **Chốt 2026-10-25 (nhóm 5 Admin):** phạm vi Admin ở V1 chỉ còn 2 mục ("buổi hủy cần rà soát điểm danh", "lớp đang chuẩn bị chưa có giáo viên") và cả hai tính được hoàn toàn ở FE từ API đã có (`GET /classes?status=preparing`, `GET /me/lesson-sessions` lọc `review_required`) — **không cần API mới cho AD-01**. "Bài chờ chấm"/"đơn chờ duyệt" để dành bàn ở chủ đề tổng quan Giáo viên | G1 |
| C2 | Phân trang + lọc ngày/người/lớp cho thông báo, audit, các danh sách lớn; audit trả tên người và tên đối tượng | G5, GM-7 |
| C3 | Làm giàu danh sách để tránh N+1: lớp (tên môn, giáo viên, sĩ số, buổi kế tiếp — **phần lớp chốt làm luôn 2026-10-25, nhóm 2 Admin**), liên kết phụ huynh–học sinh (còn thiếu), khóa học (chủ sở hữu — **xong 2026-10-28**, `piclass-api` nhánh `feat/gm4-course-owner-teacher-name`: `GET /courses` và `GET /courses/{id}` trả kèm `owner_teacher_name`), ghi danh (tên — **xong 2026-10-25**, `piclass-api` PR #46: `GET /courses/{id}/enrollments` trả kèm `student_name`), đơn xin phép (tên, giờ buổi, `submitted_at`, `review_note`) | G9, GM-4, N-C |
| C4 | Lịch sử điểm: danh sách kết quả đã công bố (tên bài, ngày, %, môn) cho biểu đồ | G8 |
| C5 | Danh sách nhận xét đã công bố theo con | G7 |
| C6 | Thông báo: đếm chưa đọc, đánh dấu tất cả đã đọc; thêm id đích vào `context` (`result_published` thiếu `assessment_id`, `schedule_*` thiếu `class_id`); `body` của thông báo duyệt đơn đang là giá trị trạng thái thô | G10 |
| C7 | Tìm văn bản không dấu (`unaccent`) cho `/users`, `/questions`, `/assessments`; thêm tham số tìm kiếm cho hai danh sách sau. **Chốt 2026-10-25:** viết cơ chế chung một lần ở BE khi làm nhóm 2 Admin (`/classes`), tái dùng cho `/subjects`, `/students`, `/parents`, `/teachers` | TB-1 |
| C8 | Tên người tạo/chấm/viết nhận xét trong response | TB-2 |
| C9 | Lịch sử phiên bản câu hỏi, "câu này đang dùng ở bài nào", liệt kê tệp đính kèm của câu/bài | TB-3, TB-4 |
| C10 | ~~Xem thành viên/phân công đã kết thúc của lớp~~ | GM-3 — **đã xong 2026-10-28**, xem `admin.md` bảng GM |
| C11 | `cancellation_reason`, `lesson_content`, `schedule_id` trong response buổi học | G15b |
| C12 | Địa điểm lớp/buổi học. **Chốt 2026-10-25: tạm bỏ ở V1** (nhóm 2 Admin) — Admin ghi phòng vào Nhãn/Mô tả, quyết lại sau khi hỏi giáo viên | G13 |
| C13 | Nhân bản bài đánh giá | G14 |
| C14 | Giờ máy chủ cho đồng hồ làm bài (hiện FE lấy từ header `Date`); số giây khóa đăng nhập thành trường riêng thay vì nằm trong message | — |
| C15 | "Yêu cầu nộp lại" cho Bài tập (hiện reissue chỉ Quiz/Exam và chỉ Admin) | TB-11 |
| C16 | `CourseEnrollmentListResource` (`GET /courses/{id}/enrollments`) không trả `end_reason` dù cột đã có trên `course_enrollments` (model fillable) — AD-12a không hiện được lý do kết thúc ghi danh trên từng dòng, chỉ hiện ngày kết thúc. Không chặn thao tác (kết thúc ghi danh vẫn lưu lý do bình thường, chỉ không đọc lại được ở danh sách) | AD-12a (phát hiện 2026-10-26 khi build FE) |

## Quyết định cần bạn chốt (gộp từ báo cáo của năm agent)

**Sản phẩm / luồng**
1. Khung điều hướng cho tài khoản giữ cả Admin và Teacher: gộp hai nhóm "Giảng dạy / Quản trị" hay công tắc đổi vai trò (Q6).
2. ~~Học sinh chưa có tài khoản làm bài thế nào (Q4)~~ — đã chốt: `DEC-USR-013` (học sinh đều có tài khoản) + `DEC-USR-016` (Teacher hoặc Admin tạo hồ sơ và mời kích hoạt, không chỉ Admin).
3. Tab "Lịch sử thay đổi" cho Teacher có làm không (🔵).
4. ~~Nhận hay không: AD-15 (checklist thiết lập lần đầu)~~ — đã chốt 2026-10-25: bỏ hẳn, Admin đầu tiên tự làm tuần tự qua các màn đã có hoặc seed. Còn mở: AD-05c (gửi lại lời mời).
5. ~~`login_identifier` có luôn là email không~~ — đã chốt: không bắt buộc, nhưng luôn có lựa chọn dùng email (`DEC-USR-017`). Còn mở: sau khi đặt mật khẩu ở CM-09 có tự đăng nhập không; thời hạn lời mời (60 phút hay 7 ngày).

**Nghiệp vụ**
6. Học sinh có được xem lại bài làm và đáp án đúng trước khi công bố điểm không.
7. Phụ huynh: gửi nhiều đơn cho một buổi, gửi cho buổi đã hủy, gửi lại sau khi bị từ chối — cho hay chặn.
8. Chấp nhận đơn xin phép có tự gợi ý đổi điểm danh sang "Có phép" không (hiện hai bước tách biệt).
9. ~~Hủy lịch lặp có kèm hủy hàng loạt các buổi đã sinh không~~ — đã chốt 2026-10-25: giữ nguyên, không hủy hàng loạt (`BR-CLASS-009`).
10. Câu `file_upload`: ẩn khỏi bài giao cho tới khi G6 xong?
11. Có cần "giữ kết quả, công bố sau" ở TC-10 (cần B3); có cần thao tác gỡ điểm tổng để quay lại chấm từng câu.
12. ~~Quy ước `weekdays` (1 là Thứ Hai hay Chủ nhật), lịch `repeat_weekly = false` có sinh buổi không~~ — đã chốt 2026-10-25: ISO weekday (1 = Thứ Hai), `repeat_weekly=false` vẫn sinh 1 buổi tại `effective_from` (giữ nguyên code đã có).
13. ~~Việc gán vai trò sau khi mời: BE tự gán hay Admin gán tay~~ — đã chốt: BE tự gán (`DEC-USR-018`).

**Vẽ mockup khi chưa chốt:** các mục 1, 3, 4, 10 chỉ ảnh hưởng một vài màn, vẽ phương án đề xuất và đánh dấu "chờ chốt". Mục 2 ảnh hưởng lớn nhất, nên chốt trước khi vẽ ST-05/ST-06.
