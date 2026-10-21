# Step 9 — API Specification: Academic + Lesson + Attendance

**Trạng thái:** Spec đã được duyệt theo từng section qua brainstorming (`superpowers:brainstorming`), chờ user review file này trước khi sang `writing-plans`.
**Phạm vi:** Nhóm module thứ hai của Step 9 (theo thứ tự ở `docs/progress.md`) — `Academic` (Subject, Class, Schedule, Course/CourseEnrollment), `Lesson` (LessonSession) và `Attendance` (AttendanceRecord/History, AbsenceExplanation) theo ranh giới tại `docs/architecture/module-boundaries.md`. Không bao gồm `ClassMember`/`TeacherClassAssignment` CRUD (đã spec và code ở nhóm 1 — User Management), Question Bank, Assessment, Submission/Grading, Result/Progress, Notification, File, Audit.
**Không tạo business rule mới.** Hành vi dưới đây chuyển các rule đã chốt ở `docs/requirements/decision-log.md`, `docs/requirements/learning-rules.md`, `docs/access-control/*`, `docs/database/schema.md` thành endpoint cụ thể. Vài điểm thiết kế kỹ thuật (cơ chế sinh session, conflict check, auto-transition status) được chốt trong phiên này — xem mục 9.

## 1. Mục tiêu

Định nghĩa REST API cho: danh mục Subject, CRUD Class (không gồm membership/assignment — đã có), Schedule + sinh/sửa LessonSession, Attendance (ghi nhận + sửa có lịch sử), AbsenceExplanation, và Course/CourseEnrollment (`DEC-LRN-010`).

## 2. Quy ước chung

Giữ nguyên quy ước đã chốt ở spec nhóm 1 (`docs/superpowers/specs/2026-10-12-identity-auth-user-management-api-design.md` §2): base path `/api/v1/...`, field `snake_case`, pagination kiểu "page", error format `{ "error": { "code", "message" } }`, permission gate theo `access-rules.md` §1 (active user → role/permission → scope → relationship hiệu lực → lifecycle/visibility; list và get-one cùng scope).

## 3. Subject

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/subjects` | Admin: toàn bộ danh mục. Teacher/Student/Parent: chỉ Subject xuất hiện qua Class trong scope của họ (không có "list toàn bộ Subject" cho non-Admin) | Theo role, scoped với non-Admin |
| `POST /api/v1/subjects` | Tạo Subject | Chỉ Admin |
| `PATCH /api/v1/subjects/{id}` | Sửa tên/mô tả/status (`active/inactive`) | Chỉ Admin |

## 4. Class

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/classes` | Admin: toàn bộ. Teacher: lớp có `TeacherClassAssignment` hiệu lực. Student: lớp có `ClassMember` hiệu lực. Parent: lớp của child có `ParentStudentRelationship` hiệu lực | Scoped theo role |
| `GET /api/v1/classes/{id}` | Chi tiết 1 Class | Cùng scope như trên; `404` nếu ngoài scope |
| `POST /api/v1/classes` | Tạo Class (`subject_id`, `name`, `label`/`description` để phân biệt tên trùng — `BR-CLASS-003`) | Chỉ Admin. `status` khởi tạo `preparing` |
| `PATCH /api/v1/classes/{id}` | Sửa tên/label/description/archive | Chỉ Admin |

**Không định nghĩa lại** `GET/POST/DELETE/PATCH .../members` và `.../teachers` — đã có ở spec nhóm 1 §8, vẫn áp dụng nguyên trạng, chỉ nested dưới Class đã có CRUD ở đây.

### 4.1 Class status tự động chuyển (quyết định thiết kế)

`status` không phải field Admin set tay giữa `preparing`/`active`. Theo đúng ngữ nghĩa `DEC-LRN-001` ("lớp chỉ được coi là đang vận hành khi có Teacher phụ trách"):

- `preparing → active` tự động khi `TeacherClassAssignment` đầu tiên còn hiệu lực được tạo cho Class đó (trong `AssignTeacherToClassController` hiện có, nhóm 1).
- `active → preparing` tự động khi assignment hiệu lực cuối cùng kết thúc (trong `EndTeacherClassAssignmentController` hiện có, nhóm 1).
- `archived` vẫn là hành động chủ động của Admin (`PATCH .../archive` hoặc field trong `PATCH /api/v1/classes/{id}`), không tự động từ hai transition trên.

Việc này chạm vào 2 controller đã code ở nhóm 1 (`piclass-api`) — cần task riêng trong plan BE để bổ sung side-effect này, không viết lại business rule.

## 5. Schedule

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/classes/{class_id}/schedules` | List mẫu lịch của Class | Admin; Teacher assigned; Student/Parent (lớp mình/child) |
| `POST /api/v1/classes/{class_id}/schedules` | Tạo Schedule (`timezone` mặc định `Asia/Ho_Chi_Minh`, `effective_from`, `effective_until` nullable, `local_start_time`, `duration_minutes`, `repeat_weekly`, `weekdays`) | Admin hoặc Teacher trong scope Class |
| `PATCH /api/v1/schedules/{id}` | Sửa mẫu — chỉ ảnh hưởng session tương lai **chưa sinh và chưa diễn ra** (`BR-CLASS-007`); ghi `ScheduleHistory`, `reason` bắt buộc | Admin hoặc Teacher trong scope |
| `POST /api/v1/schedules/{id}/cancel` | Hủy mẫu — không xóa session đã sinh, `cancel_reason` bắt buộc | Admin hoặc Teacher trong scope |

### 5.1 Sinh LessonSession (quyết định thiết kế)

- Tạo/sửa Schedule → sinh ngay LessonSession cho **4 tuần kế tiếp** kể từ ngày hiệu lực (hoặc tới `effective_until` nếu gần hơn 4 tuần).
- Laravel Scheduler (đã chốt `ARCH-08`) chạy job hàng ngày: với mỗi Schedule `active`, đảm bảo luôn có sẵn LessonSession cho đủ 4 tuần kế tiếp kể từ "hôm nay", sinh thêm phần còn thiếu, dừng lại nếu chạm `effective_until`.
- Sửa mẫu (`PATCH`) chỉ áp dụng cho các LessonSession **chưa sinh ra sau thời điểm sửa**; các session đã sinh (kể cả chưa diễn ra) giữ nguyên trừ khi người dùng sửa/hủy riêng từng session đó qua API ở mục 6.

### 5.2 Conflict check (`DEC-SCH-002`)

`POST /api/v1/classes/{class_id}/schedules` so Teacher được phân công Class đó (theo `TeacherClassAssignment` hiệu lực) với các Schedule `active` khác của **cùng Teacher** (ở Class khác) có `weekdays` + `local_start_time`/`duration_minutes` chồng giờ. Có xung đột → vẫn tạo thành công (`201`), trả thêm `warnings: [{ "type": "teacher_schedule_conflict", "conflicting_schedule_id": "...", "class_id": "..." }]`. Không chặn cứng (đúng `DEC-SCH-002`: "đưa cảnh báo để người có quyền xử lý ngoại lệ").

## 6. LessonSession

Giờ (`starts_at`, `ends_at`, và bộ lọc `from`/`until` của danh sách) theo giờ Việt Nam (`DEC-SCH-003`): giờ không kèm offset được hiểu là giờ Việt Nam, giờ kèm offset được quy đổi đúng; response hiển thị ISO 8601 với offset `+07:00`.

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/classes/{class_id}/lesson-sessions` | List buổi học (lọc theo khoảng thời gian) | Admin; Teacher assigned; Student/Parent (lớp mình/child) |
| `GET /api/v1/lesson-sessions/{id}` | Chi tiết 1 buổi | Cùng scope |
| `POST /api/v1/classes/{class_id}/lesson-sessions` | Tạo buổi thủ công, không qua Schedule (`schedule_id=null`) | Admin hoặc Teacher trong scope |
| `PATCH /api/v1/lesson-sessions/{id}` | Sửa giờ/nội dung/ghi chú, hoặc dời riêng buổi này. Mọi lần sửa có thay đổi ghi `LessonSessionHistory` (`before_data`/`after_data`) và AuditLog `lesson_session.update`; `reason` bắt buộc khi buổi đã `completed`, tùy chọn ở trạng thái khác (`DEC-LRN-005`, `DEC-LRN-011`, `BR-CLASS-011`) | Admin hoặc Teacher trong scope |
| `POST /api/v1/lesson-sessions/{id}/cancel` | Hủy buổi — `cancellation_reason` bắt buộc; nếu đã có Attendance thì giữ nguyên và set `review_required=true`, không xóa (`DEC-LRN-004`) | Admin hoặc Teacher trong scope |
| `POST /api/v1/lesson-sessions/{id}/complete` | Đánh dấu hoàn tất, set `completed_at` | Admin hoặc Teacher trong scope |

## 7. Attendance

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/lesson-sessions/{id}/attendance` | Roster của buổi + `AttendanceRecord` hiện hành (nếu có) mỗi Student | Admin; Teacher assigned; Student/Parent (bản ghi của chính mình/child, không thấy của bạn học) |
| `PUT /api/v1/lesson-sessions/{id}/attendance` | **Bulk mark**: body `{ "records": [{ "student_id", "status", "note" }, ...] }` cho toàn bộ hoặc một phần roster trong 1 request. Chỉ nhận Student có `ClassMember` hiệu lực tại thời điểm buổi học (`BR-ATT-004`); bỏ qua/`422` nếu không. Buổi học `cancelled` bị từ chối `409 SESSION_CANCELLED` (`DEC-LRN-004`, không ngoại lệ Admin; `PATCH` bản ghi cũ của buổi đã hủy vẫn được). Mỗi bản ghi mới tạo ghi AuditLog `attendance_record.create` trong cùng transaction (`ARCH-22c`) | Admin hoặc Teacher được phân công |
| `PATCH /api/v1/attendance-records/{id}` | Sửa trạng thái hiện hành (đổi `status`/`note`) — `reason` bắt buộc, ghi `AttendanceHistory` (`before_data`/`after_data`) (`DEC-LRN-007`) | Admin hoặc Teacher được phân công |

### 7.1 AbsenceExplanation

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/absence-explanations` | Parent gửi giải trình (`student_id`, `lesson_session_id`, `message`) cho child có liên kết hiệu lực | Parent |
| `GET /api/v1/absence-explanations` | List, lọc theo `lesson_session_id`/`student_id`/`status` | Admin toàn bộ; Teacher trong scope Class; Parent chỉ của mình/child |
| `PATCH /api/v1/absence-explanations/{id}` | Review: set `status` (`under_review/accepted/rejected`) + `review_note`. Không tự đổi `AttendanceRecord` (`DEC-LRN-008`) | Admin hoặc Teacher trong scope |

## 8. Course / CourseEnrollment

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/courses` | Admin: toàn bộ. Teacher: Course mình sở hữu + Course dùng chung hệ thống (`owner_teacher_id=null`). Student/Parent: chỉ Course mình/child đang có `CourseEnrollment` hiệu lực | Scoped theo role |
| `GET /api/v1/courses/{id}` | Chi tiết | Cùng scope; `404` ngoài scope |
| `POST /api/v1/courses` | Tạo Course (`name`, `description`, `subject_id` nullable). Admin tạo có thể để `owner_teacher_id=null` (dùng chung) hoặc chỉ định Teacher; Teacher tự tạo thì `owner_teacher_id` luôn là chính mình | Admin hoặc Teacher. Không cần phê duyệt — dùng được ngay sau tạo (giống Question Bank, `BR-COURSE-004`) |
| `PATCH /api/v1/courses/{id}` | Sửa tên/mô tả/archive | Admin, hoặc Teacher là `owner_teacher_id` của Course đó |
| `GET /api/v1/courses/{id}/enrollments` | List CourseEnrollment hiệu lực + lịch sử | Admin; Teacher là owner của Course; Student/Parent chỉ bản ghi của chính mình/child |
| `POST /api/v1/courses/{id}/enrollments` | Enroll — body là **một trong hai**: `{ "student_id": "..." }` (từng người) hoặc `{ "class_id": "..." }` (bulk theo toàn bộ `ClassMember` hiệu lực hiện hành của Class đó). `student_id` **không giới hạn** Student thuộc Class Teacher đang phụ trách — Teacher sở hữu Course được chọn bất kỳ Student nào trong hệ thống, kể cả qua `class_id` của Class không do Teacher đó phụ trách (`BR-COURSE-004`, đã chốt). Course `archived` bị từ chối `422 COURSE_ARCHIVED` (`DEC-LRN-013`); mỗi học viên ghi danh mới có một AuditLog `course_enrollment.enroll` (`DEC-AUD-007`) | Admin, hoặc Teacher là owner của Course |
| `DELETE /api/v1/courses/{id}/enrollments/{student_id}` | Kết thúc CourseEnrollment — `end_reason` bắt buộc, set `valid_to`/`status=ended`, không xóa hàng; ghi AuditLog `course_enrollment.end` (`reason` = `end_reason`, `DEC-AUD-007`) | Admin, hoặc Teacher là owner của Course |

Khử trùng khi Student nhận Assessment qua cả Class lẫn Course: đã được quy tắc hợp nhất `StudentAssessment` xử lý (unique `assessment_id`+`student_id` theo `database/schema.md`), không cần logic dedup riêng ở Course — xác nhận lại ở spec nhóm 3 (Question Bank + Assessment) khi thiết kế `AssessmentAudience`/`StudentAssessment` endpoint.

## 9. Quyết định phát sinh trong lúc thiết kế (đã ghi ngược vào tài liệu nguồn)

- `BR-COURSE-004` (Teacher tạo/enroll Course) được chốt dứt khoát trong phiên này: Teacher sở hữu Course riêng không cần duyệt, enroll được **bất kỳ Student nào** trong hệ thống (không giới hạn Class mình phụ trách). Đã cập nhật `requirements/learning-rules.md`, `requirements/decision-log.md` (DEC-LRN-010 bỏ nhãn "sơ bộ"), `access-control/open-decisions.md`, `access-control/resource-permissions.md`, `access-control/permission-matrix.md`, `access-control/data-scope.md`.
- `ARCH-13` (mới): cơ chế sinh LessonSession (cửa sổ rolling 4 tuần + Scheduler job hàng ngày) — đã ghi vào `architecture/open-decisions.md`.
- Class `status` tự động chuyển `preparing↔active` theo `TeacherClassAssignment` hiệu lực — quyết định thiết kế nội bộ module Academic, không phải business rule mới (chỉ hiện thực hóa `DEC-LRN-001`); ghi chú tại §4.1 trên, không cần entry riêng ở `open-decisions.md` vì không phải lựa chọn công nghệ/kiến trúc liên module.
- Schedule conflict check trả `warnings` thay vì chặn — hiện thực hóa đúng câu chữ `DEC-SCH-002` ("đưa cảnh báo"), không phải quyết định mới.

## 10. Ngoài phạm vi spec này

- `ClassMember`/`TeacherClassAssignment` CRUD — đã ở spec nhóm 1.
- Question Bank, Assessment/AssessmentVersion/AssessmentAudience/StudentAssessment (kể cả phần Course liên quan tới Assessment) — nhóm module 3.
- Submission/Grading, Result/Progress — nhóm module 4.
- Notification, File, Audit — nhóm module 5.
- Chi tiết OpenAPI/schema validation từng field, response header cụ thể — thuộc `writing-plans`/implementation.

## 11. Nguồn tham khảo

`docs/requirements/decision-log.md`, `docs/requirements/learning-rules.md`, `docs/access-control/resource-permissions.md`, `permission-matrix.md`, `data-scope.md`, `open-decisions.md`, `docs/database/schema.md`, `docs/domain/domain-model.md`, `docs/architecture/module-boundaries.md`, `docs/architecture/technology-decisions.md`, `docs/architecture/open-decisions.md`.
