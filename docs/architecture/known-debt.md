# Nợ kỹ thuật backend đã biết

Code chưa đạt quy ước `piclass-api/CLAUDE.md` hoặc còn lỗi có sẵn đã ghi nhận (không phải hồi quy). Sửa khi chạm tới module liên quan. **Xóa dòng khi đã xong** (lịch sử ở git). Ngữ cảnh/quyết định: [`open-decisions.md`](open-decisions.md) (`ARCH-22`..`ARCH-31` ở `decisions/`). Việc cần ưu tiên làm: [`../BACKLOG.md`](../BACKLOG.md) (trỏ tới đây bằng ID `D-xx`).

**Cách đọc:** nhóm theo module. Làm module nào thì `grep -n "^## Lesson"` rồi đọc đúng nhóm đó, không đọc cả file. Dòng dài có tóm tắt ở đây, nguyên văn ở [`known-debt-detail.md`](known-debt-detail.md). ID `D-xx` ổn định, không đánh lại số khi xóa dòng. Mục thêm mới: ID kế tiếp (`D-34`...), đặt đúng nhóm module.

## Attendance

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-05 | Bốn khoảng cách spec↔code nhỏ của Attendance còn lại ở `ARCH-22(d)`: GET attendance chỉ trả bản ghi đã có (không trả cả roster); thiếu bộ lọc `lesson_session_id` ở `GET /absence-explanations`; người nhận thông báo không xét `valid_to`; audit `absence_explanation.review` thiếu `correlation_key`. (Hai ý cũ — chặn điểm danh buổi `cancelled`, bulk mark không audit — đã sửa 2026-10-22, issue #19/#20.) | `Attendance/Actions/{GetSessionAttendance,ListAbsenceExplanations,ReviewAbsenceExplanation}`, `Attendance/Queries/AbsenceExplanationsQuery`, `Notification/NotificationRecipientsQuery` | Cần quyết định/PR đổi hành vi riêng — xem `ARCH-22` ([decisions/ARCH-22.md](decisions/ARCH-22.md)) |
| D-07 | Nhánh teacher trong `AttendanceRecordPolicy`/`AbsenceExplanationPolicy::review` bỏ qua vai trò đang hoạt động (user có hồ sơ teacher đang ở vai trò `parent` vẫn `allowed`); không tới được qua HTTP, lệch `ARCH-11` ở tầng Policy. Chi tiết: [D-07](known-debt-detail.md#d-07) | `Attendance/Policies/{AttendanceRecordPolicy,AbsenceExplanationPolicy}.php` | Hướng: yêu cầu `code() === 'teacher'` (hoặc rỗng cho job), hoặc tách site theo vai trò. Thảo luận ở issue #21 |
| D-08 | Lỗi có sẵn, không phải hồi quy: `GET /absence-explanations?student_id=abc` → 500 (Postgres báo uuid không hợp lệ; `rules()` rỗng; suy từ code, chưa chạy thử). `?status[]=x` có thể đã hết lỗi (request đọc qua `string()`), cần chạy thử để chốt. | `Attendance/Http/Requests/ListAbsenceExplanationsRequest`, `AbsenceExplanationsQuery` | Khi có task sửa hành vi Attendance (validate bộ lọc → 422) |
| D-10 | Tên case của data provider trong test Attendance còn tiếng Anh (ví dụ `AbsenceExplanationBehaviorTest`, `AttendanceEndpointsBehaviorTest`) | `tests/Feature/Attendance/*` | Chờ user quyết có tính là "mô tả trong test" (phải tiếng Việt) không |

## SubmissionGrading và ResultProgress

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-01 | Query dựng mảng JSON và chứa rule chọn kết quả `highest/latest` (Enum `AttemptScorePolicy` đã có) | `StudentAssessmentResultQuery` | Lần sửa tới của `ResultProgress`: rule chọn → `Domain/`, định dạng → Resource |
| D-02 | Tên Query thiếu hậu tố `Query` | `GetSubmission`, `ListSubmissions` | Lần sửa tới của `SubmissionGrading` |
| D-03 | DTO nằm trong `Actions/` | `SubmissionGrading/Actions/GradingOutcome.php` | Lần sửa tới của `SubmissionGrading`: chuyển sang `Data/` |
| D-04 | Static công khai nên ở `Domain/` | `BuildAttemptQuestions::order/optionOrderFor` | Lần sửa tới của `SubmissionGrading` |
| D-21 | Trạng thái của `Assessment` còn là literal trong module khác: `StudentProgressQuery` dùng `'cancelled'`. | `ResultProgress/Queries/StudentProgressQuery` | Lần sửa tới của `ResultProgress`: đổi sang `AssessmentStatus::Cancelled->value` (`Assessment/AssessmentStatus` đã có trong `SHARED_ENUMS`) |

## UserManagement và Identity

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-11 | Lỗi có sẵn, không phải hồi quy: `GET /users?name[]=x` → 500 (bộ lọc `name` ép mảng thành chuỗi) | `UserManagement/Queries/ListUsersQuery` | Khi có task sửa hành vi: validate bộ lọc `name`/`email`/`role`/`status` là chuỗi (422) |
| D-13 | Minor sau review Opus của 9g (xem `ARCH-29(e)`): siết ngoại lệ R7 (R7 vẫn miễn cả tầng Http của Identity); `EnsureRole` và `MeController` còn literal `'active_role_code'` thay vì `ActiveRole::SESSION_KEY`. | `ArchitectureTest`, `Identity/Http/Middleware/EnsureRole`, `Identity/Http/Controllers/MeController` | Task dọn riêng hoặc lần sửa tới của Identity |
| D-25 | CSRF mismatch trả HTTP 419 với `error.code = "ERROR"`, chưa có trong `error-codes.md`; `login`/`me`/`context` trả JSON trần không bọc `data` (lệch quy ước Resource bọc `data`, FE `api-client` đang xử lý cả hai) | `piclass-api` Identity, `architecture/error-codes.md` | Khi chốt hợp đồng với FE: ghi 419 vào `error-codes.md`, quyết định có bọc `data` không |

## Academic (gồm liên kết Class–Course)

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-14 | `POST /courses` của teacher không có hồ sơ Teacher trả 500 (`CreateCourseRequest::ownerTeacherId` gọi `teacherProfile->id` trên null; suy từ code, chưa chạy thử, chưa có test ghim). Bốn ý còn lại của nợ cũ (#34–#37) đã xong. Chi tiết: [D-14](known-debt-detail.md#d-14) | `Academic/Http/Requests/CreateCourseRequest` | PR đổi hành vi riêng sau Task 9f — xem `ARCH-28` ở `open-decisions.md` |

## Assessment

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-15 | `ListAssessmentsRequest::rules()` rỗng có chủ đích (thêm validate đổi 200 thành 422): `GET /assessments?class_id=x\|course_id=x` còn 500 | `Assessment/Http/Requests/ListAssessmentsRequest` | Khi có task sửa hành vi: validate bộ lọc (`uuid`, `string`) trả 422 (`ARCH-24` b) |
| D-16 | Quyết định `ARCH-14` (đồng bộ `StudentAssessment` khi học viên vào lớp/Course sau khi gắn audience) chưa chốt | `Assessment/Actions/AddAssessmentAudience`, `UserManagement/Actions/AddClassMember`, `Academic/EnrollInCourseController` | User chốt hướng (hook hay tính lúc đọc), làm PR đổi hành vi riêng |

## Lesson

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-17 | `GET /classes/{class}/lesson-sessions?from=abc\|until=abc\|from[]=x` trả 500 (bộ lọc ngày không validate, `ListLessonSessionsRequest::rules()` rỗng có chủ đích để giữ hành vi); không ghim trong test | `Lesson/Http/Requests/ListLessonSessionsRequest.php` | Khi chốt hợp đồng với FE: thêm `date` rule (đổi 500 thành 422) |
| D-18 | Lỗi có sẵn của Lesson còn mở ở `ARCH-26` (phần múi giờ và sửa buổi `cancelled` đã xong, PR #58): hủy lại lịch đã hủy vẫn thành công; các chuyển trạng thái buổi còn lại (hủy/hoàn tất) không bị chặn; `PATCH` không kiểm `ends_at` sau `starts_at` và `effective_until`. Chưa kiểm lại: xung đột lịch qua nửa đêm, race của `reason`/`review_required`, audit buổi đã sửa (`DEC-LRN-011`). | `Lesson/Actions/*` | PR đổi hành vi riêng sau khi user chốt |
| D-32 | `PATCH /schedules/{id}` gần như không tác dụng lên buổi đã sinh (hệ quả `DEC-SCH-004`); FE không còn gọi route này (dùng `update-series`, `ARCH-37`, PR #53). User chốt giữ route ở BE (2026-10-28). Chi tiết: [D-32](known-debt-detail.md#d-32) | `piclass-api/app/Modules/Lesson/Actions/UpdateSchedule.php` | Cùng lúc làm `ARCH-37` (gộp tab Lịch/Buổi học) |
| D-33 | `UpdateScheduleAndFollowingSessions` không sống lại được buổi đã `cancelled` bởi chính nó (dedupe theo `occurrence_date` + unique index): rút ngắn rồi nới dài lại `effective_until` làm mất buổi vĩnh viễn. Đã chốt chấp nhận làm nợ (đã merge PR #53). Chi tiết: [D-33](known-debt-detail.md#d-33) | `piclass-api/app/Modules/Lesson/Actions/{UpdateScheduleAndFollowingSessions,GenerateLessonSessions}.php` | Xem chi tiết |

## File, Notification, Audit

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-19 | Nợ của File sau `fix/file-learner-access` (`ARCH-30(h)`): chưa gắn/xem file của `answer_id`/`feedback_id` (Student `file_upload`, feedback có file), chưa dọn blob mồ côi/retention | `File/Policies/AttachmentPolicy`, `File/Http/Requests/LinkAttachmentRequest` | PR-2 retrofit Module 4 (issue riêng); `ARCH-12` |
| D-20 | Nợ của Notification/Audit (`ARCH-31`): hai danh sách không phân trang, người nhận không xét `valid_to`, `NotificationDispatcher` không transaction, `GET /audit-logs` 500 với `entity_id` không phải uuid hoặc bộ lọc mảng | `Notification/NotificationDispatcher`, `Audit/Queries/AuditLogsQuery`, `Notification/Queries/*` | Khi có task sửa hành vi: phân trang, validate bộ lọc (422), transaction cho dispatcher |

## Chung: kiến trúc, tooling, vận hành

| ID | Nợ | Ở đâu | Sửa khi |
| --- | --- | --- | --- |
| D-06 | R8 theo module có thể bỏ sót: module so literal với cột đã cast của Model module khác mà KHÔNG import Enum đó (ví dụ `$attempt->status === 'graded'`) thì R8 không bắt (bản gộp toàn cục trước đây bắt được) | `tests/Architecture/ArchitectureTest.php` (`enumValues`, `SHARED_ENUMS`) | Hướng: R8 lấy thêm Enum của các cột được cast trên mọi Model mà module tham chiếu |
| D-12 | Literal role code còn ở module chưa dùng `RoleCode` (chưa chỗ nào được sửa, kiểm 2026-10-28). | `app/Support/ClassScope.php` (dòng 24-26, 47, 57, 70), `Attendance/Queries/{SessionAttendanceQuery,AbsenceExplanationsQuery}`, `Attendance/Policies/{AbsenceExplanationPolicy,AttendanceRecordPolicy}`, `Assessment/Queries/ListAssessmentsQuery`, `Assessment/Policies/AssessmentPolicy`, `Lesson/Policies/Concerns/ChecksClassScope` | Task dọn riêng: `RoleCode::X->value`, thêm `'Identity/RoleCode'` vào `SHARED_ENUMS` của module |
| D-22 | PHPStan mức 5: baseline còn 146 mục / 204 lượt lỗi (đếm `message:` / cộng `count:`, kiểm 2026-10-28), phần lớn do ~104 method quan hệ Eloquent trong `app/Models` chưa khai báo kiểu trả về. Số lỗi ở mức 6 chưa kiểm lại. Chi tiết: [D-22](known-debt-detail.md#d-22) | `app/Models/*`, `phpstan-baseline.neon`, `StudentAssessmentMetadataQuery`, `RecalculateResult`, `StudentProgressQuery` | Xem chi tiết |
| D-23 | OpenAPI sinh tự động còn khoảng trống: 4 Resource chưa có docblock (`ProgressResource`, `ResultResource`, `StudentAssessmentResultResource`, `CreatedSubjectResource`); 0 chỗ dùng `#[QueryParameter]` (phân trang/bộ lọc query chưa suy ra); 13 chỗ `items: {}`; số cảnh báo khi export (đang ghi 25) chưa kiểm lại. Chi tiết: [D-23](known-debt-detail.md#d-23) | `piclass-api/app/Providers/OpenApiServiceProvider.php`, `app/Http/Resources`, các Resource/Request trên | Task riêng không đổi hành vi: docblock `@mixin`/`@property` cho ~20 Resource, `#[QueryParameter]` cho query |
| D-24 | `make lint` (Pint) đỏ ở khoảng 41 file test cũ lỗi style, có từ trước ⚠ Chưa kiểm lại được (cần máy có PHP: `vendor/bin/pint --test`). | `piclass-api/tests/*` | Task dọn riêng: chạy Pint sửa hàng loạt rồi giữ xanh |

- **`assessment_versions.result_release_at` không còn được đọc (2026-11-01).** Từ lát 4c, chấm xong không tự công bố; hẹn giờ nằm ở `results.scheduled_publish_at` (`DEC-LRN-027`). Cột cũ và field trong model/`CreateAssessmentVersion`/`CopyAssessmentToCourse` còn nguyên, dọn khi có dịp đổi schema `assessment_versions`.
