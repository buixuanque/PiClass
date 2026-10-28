# Nợ kỹ thuật backend đã biết

Code chưa đạt quy ước `piclass-api/CLAUDE.md` hoặc còn lỗi có sẵn đã ghi nhận (không phải hồi quy). Sửa khi chạm tới module liên quan. **Xóa dòng khi đã xong** (lịch sử ở git). Ngữ cảnh/quyết định: [`open-decisions.md`](open-decisions.md) (`ARCH-22`..`ARCH-31` ở `decisions/`). Việc cần ưu tiên làm: [`../BACKLOG.md`](../BACKLOG.md) (trỏ tới đây bằng ID `D-xx`).

**Cách đọc:** nhóm theo module. Làm module nào thì `grep -n "^## Lesson"` rồi đọc đúng nhóm đó, không đọc cả file. Dòng dài có tóm tắt ở đây, nguyên văn ở [`known-debt-detail.md`](known-debt-detail.md). ID `D-xx` ổn định, không đánh lại số khi xóa dòng. Mục thêm mới: ID kế tiếp (`D-34`...), đặt đúng nhóm module.

## Attendance

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-05 | Khoảng cách code–tài liệu của Attendance (không chặn điểm danh buổi `cancelled` — bug thật so với `BR-ATT-004`; bulk mark không audit; bốn khoảng cách spec↔code nhỏ) | `Attendance/Actions/BulkMarkAttendance`, `GetSessionAttendance`, `ListAbsenceExplanations`, `ReviewAbsenceExplanation` | Cần quyết định/PR đổi hành vi riêng — xem `ARCH-22` ở `open-decisions.md` |
| D-07 | Nhánh teacher trong `AttendanceRecordPolicy`/`AbsenceExplanationPolicy::review` bỏ qua vai trò đang hoạt động (user có hồ sơ teacher đang ở vai trò `parent` vẫn `allowed`); không tới được qua HTTP, lệch `ARCH-11` ở tầng Policy. Chi tiết: [D-07](known-debt-detail.md#d-07) | `Attendance/Policies/{AttendanceRecordPolicy,AbsenceExplanationPolicy}.php` | Hướng: yêu cầu `code() === 'teacher'` (hoặc rỗng cho job), hoặc tách site theo vai trò. Thảo luận ở issue #21 |
| D-08 | Lỗi có sẵn, không phải hồi quy: `GET /absence-explanations?student_id=abc` → 500 (Postgres báo uuid không hợp lệ) và `?status[]=x` → 500 | `Attendance/Http/Requests/ListAbsenceExplanationsRequest`, `AbsenceExplanationsQuery` | Khi có task sửa hành vi Attendance (validate bộ lọc → 422) |
| D-10 | Tên case của data provider trong test Attendance còn tiếng Anh (ví dụ `AbsenceExplanationBehaviorTest`, `AttendanceEndpointsBehaviorTest`) | `tests/Feature/Attendance/*` | Chờ user quyết có tính là "mô tả trong test" (phải tiếng Việt) không |

## SubmissionGrading và ResultProgress

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-01 | Query dựng mảng JSON và chứa rule chọn kết quả `highest/latest` (Enum `AttemptScorePolicy` đã có) | `StudentAssessmentResultQuery` | Lần sửa tới của `ResultProgress`: rule chọn → `Domain/`, định dạng → Resource |
| D-02 | Tên Query thiếu hậu tố `Query` | `GetSubmission`, `ListSubmissions` | Lần sửa tới của `SubmissionGrading` |
| D-03 | DTO nằm trong `Actions/` | `SubmissionGrading/Actions/GradingOutcome.php` | Lần sửa tới của `SubmissionGrading`: chuyển sang `Data/` |
| D-04 | Static công khai nên ở `Domain/` | `BuildAttemptQuestions::order/optionOrderFor` | Lần sửa tới của `SubmissionGrading` |
| D-21 | Trạng thái của module `Assessment` còn là literal trong module khác: `StudentProgressQuery` (`'cancelled'` của `Assessment.status`; R8 chỉ bắt khi module import đúng Enum) | `ResultProgress/Queries/StudentProgressQuery` | Lần sửa tới của `ResultProgress`: `AssessmentStatus::Cancelled->value` và khai `Assessment/AssessmentStatus` vào `SHARED_ENUMS` |

## UserManagement và Identity

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-11 | Lỗi có sẵn, không phải hồi quy: `GET /users?name[]=x` → 500 (bộ lọc `name` ép mảng thành chuỗi) | `UserManagement/Queries/ListUsersQuery` | Khi có task sửa hành vi: validate bộ lọc `name`/`email`/`role`/`status` là chuỗi (422) |
| D-13 | Minor sau review Opus của 9g (xem `ARCH-29(e)`): siết ngoại lệ R7, test giữ session hiện tại khi đổi mật khẩu, test `Auth::shouldUse('web')`, `SESSION_KEY` thay literal | `ArchitectureTest`, `Identity/Http`, `tests/Feature/Identity` | Task dọn riêng hoặc lần sửa tới của Identity |
| D-25 | CSRF mismatch trả HTTP 419 với `error.code = "ERROR"`, chưa có trong `error-codes.md`; `login`/`me`/`context` trả JSON trần không bọc `data` (lệch quy ước Resource bọc `data`, FE `api-client` đang xử lý cả hai) | `piclass-api` Identity, `architecture/error-codes.md` | Khi chốt hợp đồng với FE: ghi 419 vào `error-codes.md`, quyết định có bọc `data` không |

## Academic (gồm liên kết Class–Course)

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-14 | Nợ phát hiện khi lên plan Academic (issue #34–#37): Teacher thiếu hồ sơ khớp `null === null` với Course dùng chung; thiếu chặn Course `archived`/Subject `inactive`; thiếu audit ghi danh Course; chỉ phân công Teacher `active`; `POST /courses` của teacher không hồ sơ trả 500. Chi tiết: [D-14](known-debt-detail.md#d-14) | `Academic/Policies/CoursePolicy`, `Academic/Actions/EnrollInCourse`, `Academic/Actions/CreateClass`, `UserManagement/Actions/AssignTeacherToClass`, `Academic/Http/Requests/CreateCourseRequest` | PR đổi hành vi riêng sau Task 9f — xem `ARCH-28` ở `open-decisions.md` |
| D-26 | `EnrollInCourse`/`EnrollInCourseRequest` còn nhánh ghi danh theo `class_id` — dead code thật (FE không còn gọi, đã thay bằng `LinkClassToCourse`). Chi tiết: [D-26](known-debt-detail.md#d-26) | `piclass-api/app/Modules/Academic/{Actions/EnrollInCourse.php, Http/Requests/EnrollInCourseRequest.php}` | Task dọn riêng ở `piclass-api`: xóa nhánh `class_id` khỏi Action/Request (chỉ còn `student_id`) |
| D-27 | Race hiếm giữa `Link/UnlinkClassFromCourse` và `AddClassMember`/`EndClassMember`/lưu trữ lớp trên cùng một lớp: không có khóa chung, có thể thiếu/thừa một ghi danh tạm thời (tự sửa ở lần đồng bộ sau). Chi tiết: [D-27](known-debt-detail.md#d-27) | `piclass-api/app/Modules/Academic/Actions/{LinkClassToCourse,UnlinkClassFromCourse}.php`, `UserManagement/Actions/{AddClassMember,EndClassMember}.php` | Xem chi tiết |
| D-28 | Ba Action `SyncCourseEnrollmentsOnClass*` không tự mở `DB::transaction` (dựa vào nơi gọi); hiện mọi nơi gọi đều có transaction nên chưa phải bug. Chi tiết: [D-28](known-debt-detail.md#d-28) | `piclass-api/app/Modules/Academic/Actions/SyncCourseEnrollmentsOnClass*.php` | Khi một trong 3 Action này được gọi từ nơi chưa chắc có transaction (job, console...) |
| D-29 | `UnlinkClassFromCourse` không khóa dòng `course_class_links` trước khi cập nhật — hai yêu cầu gỡ đồng thời đều qua được và ghi 2 dòng audit `unlink` (không sai dữ liệu ghi danh vì dòng ghi danh đã khóa riêng, chỉ audit trùng) | `piclass-api/app/Modules/Academic/Actions/UnlinkClassFromCourse.php` | Khi cần audit log sạch tuyệt đối cho thao tác này |
| D-31 | Xóa cứng `Class`/`Course` sẽ cascade xóa `course_class_links`, đưa `source_class_link_id` về null (ghi danh qua lớp thành "cá nhân"); chưa có luồng xóa cứng nên chưa phải bug. Chi tiết: [D-31](known-debt-detail.md#d-31) | `piclass-api/database/migrations/2026_10_26_000002_add_source_class_link_id_to_course_enrollments_table.php` | Nếu sau này có chức năng xóa cứng Class/Course |

## Assessment

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-15 | `ListAssessmentsRequest::rules()` rỗng có chủ đích (thêm validate đổi 200 thành 422): `GET /assessments?class_id=x\|course_id=x` còn 500 | `Assessment/Http/Requests/ListAssessmentsRequest` | Khi có task sửa hành vi: validate bộ lọc (`uuid`, `string`) trả 422 (`ARCH-24` b) |
| D-16 | Quyết định `ARCH-14` (đồng bộ `StudentAssessment` khi học viên vào lớp/Course sau khi gắn audience) chưa chốt | `Assessment/Actions/AddAssessmentAudience`, `UserManagement/Actions/AddClassMember`, `Academic/EnrollInCourseController` | User chốt hướng (hook hay tính lúc đọc), làm PR đổi hành vi riêng |

## Lesson

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-17 | `GET /classes/{class}/lesson-sessions?from=abc\|until=abc\|from[]=x` trả 500 (bộ lọc ngày không validate, `ListLessonSessionsRequest::rules()` rỗng có chủ đích để giữ hành vi); không ghim trong test | `Lesson/Http/Requests/ListLessonSessionsRequest.php` | Khi chốt hợp đồng với FE: thêm `date` rule (đổi 500 thành 422) |
| D-18 | Lỗi có sẵn của Lesson còn mở ở `ARCH-26`: chuyển trạng thái không bị chặn, `PATCH` không kiểm `ends_at`/`effective_until`, phát hiện xung đột lịch qua nửa đêm, race của `reason`/`review_required` (múi giờ và audit của buổi đã sửa, `DEC-SCH-003`, `DEC-LRN-011`) | `Lesson/Actions/*` | PR đổi hành vi riêng sau khi user chốt |
| D-32 | `PATCH /schedules/{id}` gần như không tác dụng lên buổi đã sinh (hệ quả `DEC-SCH-004`). Hướng xử lý `update-series` (`ARCH-37`) đã merge. ⚠ Cần kiểm xem xóa dòng này được chưa. Chi tiết: [D-32](known-debt-detail.md#d-32) | `piclass-api/app/Modules/Lesson/Actions/UpdateSchedule.php` | Cùng lúc làm `ARCH-37` (gộp tab Lịch/Buổi học) |
| D-33 | `UpdateScheduleAndFollowingSessions` không sống lại được buổi đã `cancelled` bởi chính nó (dedupe theo `occurrence_date` + unique index): rút ngắn rồi nới dài lại `effective_until` làm mất buổi vĩnh viễn. Đã chốt chấp nhận làm nợ. Chi tiết: [D-33](known-debt-detail.md#d-33) | `piclass-api/app/Modules/Lesson/Actions/{UpdateScheduleAndFollowingSessions,GenerateLessonSessions}.php` | Xem chi tiết |

## File, Notification, Audit

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-19 | Nợ của File sau `fix/file-learner-access` (`ARCH-30(h)`): chưa gắn/xem file của `answer_id`/`feedback_id` (Student `file_upload`, feedback có file), chưa dọn blob mồ côi/retention | `File/Policies/AttachmentPolicy`, `File/Http/Requests/LinkAttachmentRequest` | PR-2 retrofit Module 4 (issue riêng); `ARCH-12` |
| D-20 | Nợ của Notification/Audit (`ARCH-31`): hai danh sách không phân trang, người nhận không xét `valid_to`, `NotificationDispatcher` không transaction, `GET /audit-logs` 500 với `entity_id` không phải uuid hoặc bộ lọc mảng | `Notification/NotificationDispatcher`, `Audit/Queries/AuditLogsQuery`, `Notification/Queries/*` | Khi có task sửa hành vi: phân trang, validate bộ lọc (422), transaction cho dispatcher |
| D-30 | Catalog hành động audit (`docs/product/screens/admin.md`) chưa liệt kê `course_class_link.link`/`.unlink`, `course_enrollment.relink` (thêm ở tính năng liên kết Class–Course, 2026-10-26) | `docs/product/screens/admin.md` | Khi màn `/audit-logs` được port FE và cần danh mục đầy đủ |

## Chung: kiến trúc, tooling, vận hành

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-06 | R8 theo module có thể bỏ sót: module so literal với cột đã cast của Model module khác mà KHÔNG import Enum đó (ví dụ `$attempt->status === 'graded'`) thì R8 không bắt (bản gộp toàn cục trước đây bắt được) | `tests/Architecture/ArchitectureTest.php` (`enumValues`, `SHARED_ENUMS`) | Hướng: R8 lấy thêm Enum của các cột được cast trên mọi Model mà module tham chiếu |
| D-09 | Vận hành: thông báo Attendance (và ResultProgress) phụ thuộc queue worker (`QUEUE_CONNECTION=database`) | `Attendance/Listeners/*` | Sau khi merge phải restart worker để nạp listener mới |
| D-12 | Literal role code còn ở module chưa dùng `RoleCode` | `ClassScope` (`classIdsFor($user, 'teacher')`...), `Attendance/{Queries,Policies}`, `Assessment/{Queries,Policies}`, `Lesson/Policies/Concerns` | Task dọn riêng: `RoleCode::X->value`, thêm `'Identity/RoleCode'` vào `SHARED_ENUMS` của module |
| D-22 | PHPStan mức 5: baseline còn nhiều lỗi, gần hết do method quan hệ Eloquent chưa khai báo kiểu trả về. ⚠ Số liệu "188 lỗi" đã cũ (baseline đã giảm sau các PR gần đây) — cần kiểm ở bước rà lỗi thời. Chi tiết: [D-22](known-debt-detail.md#d-22) | `app/Models/*`, `phpstan-baseline.neon`, `StudentAssessmentMetadataQuery`, `RecalculateResult`, `StudentProgressQuery` | Xem chi tiết |
| D-23 | OpenAPI sinh tự động còn khoảng trống: ~10 response 2xx yếu, không suy ra phân trang/bộ lọc query/danh mục `error.code`, 25 cảnh báo khi export. Chi tiết: [D-23](known-debt-detail.md#d-23) | `piclass-api/app/Providers/OpenApiServiceProvider.php`, `app/Http/Resources`, các Resource/Request trên | Task riêng không đổi hành vi: docblock `@mixin`/`@property` cho ~20 Resource, `#[QueryParameter]` cho query |
| D-24 | `make lint` (Pint) đỏ ở khoảng 41 file test cũ lỗi style, có từ trước ⚠ Cần kiểm số liệu (có thể đã cũ). | `piclass-api/tests/*` | Task dọn riêng: chạy Pint sửa hàng loạt rồi giữ xanh |
