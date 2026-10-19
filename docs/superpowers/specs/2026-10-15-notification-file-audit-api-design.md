# Step 9 — API Specification: Notification + File + Audit

**Trạng thái:** Spec đã được duyệt theo từng section qua brainstorming (`superpowers:brainstorming`), chờ user review file này trước khi sang `writing-plans`.
**Phạm vi:** Nhóm module thứ năm (cuối cùng) của Step 9 — `Notification` + `File`/`Attachment` + `AuditLog` theo ranh giới tại `docs/architecture/module-boundaries.md`. Khác với 4 module trước, module này **không chỉ thêm endpoint mới** mà còn phải **sửa lại các controller đã code xong của Module 1-4** để gắn `AuditLog`/`Notification` vào đúng action — những chỗ đó đã được chính các spec trước ghi rõ "thuộc Module 5" khi tới lượt.
**Không tạo business rule mới.** Mọi hành vi dưới đây chỉ chuyển các rule đã chốt ở `docs/requirements/notification-rules.md` (`BR-NOTIF-001..007`), `docs/requirements/file-and-audit-rules.md` (`BR-FILE-001..004`, `BR-AUD-001..007`), `docs/requirements/decision-log.md` (`DEC-NOTIF-*`, `DEC-FILE-*`, `DEC-AUD-*`), `docs/access-control/*`, `docs/database/schema.md` thành endpoint + luồng nghiệp vụ cụ thể.
**Lưu ý quan trọng:** schema cho `Notification`/`NotificationContext`/`Attachment`/`AttachmentLink`/`AuditLog` đã chốt đầy đủ ở Step 6, nhưng **chưa có migration/model nào** trong `piclass-api/` — khác hẳn thuần module trước, module này mới thực sự "trắng" từ đầu đến cuối (kể cả API upload file thật).

## 1. Mục tiêu

Định nghĩa: (a) `AuditLog` append-only cho action nhạy cảm của mọi module trước; (b) `Notification` in-app cho các event cần người dùng biết/hành động; (c) API upload/liên kết/xem file thật qua Laravel Storage (driver S3-compatible). Và liệt kê chính xác những controller đã có từ Module 1-4 cần sửa để gọi 2 service `AuditLogger`/`NotificationDispatcher`.

## 2. Quy ước chung

Kế thừa quy ước đã chốt ở các spec trước: base path `/api/v1/...`, `snake_case`, `{data, meta}`, `{error:{code,message}}`, permission gate 4 bước (`access-control/access-rules.md` §1), không Form Request/Policy/Resource — validate+authorize inline, response raw array.

## 3. AuditLog — model + service ghi log

Schema (Step 6, mới tạo migration ở module này): `AuditLog(id, actor_user_id FK User nullable, action, entity_type, entity_id, occurred_at, before_data/after_data JSON nullable, reason nullable, correlation_key nullable)`. **Append-only**: không có endpoint `PATCH`/`DELETE`.

**Service** `app/Services/Audit/AuditLogger.php`:
```
AuditLogger::record(actor: ?User, action: string, entityType: string, entityId: string, before: ?array, after: ?array, reason: ?string): AuditLog
```
Validate `reason`/before-after bắt buộc theo action (`BR-AUD-003`) được làm ở **controller gọi service**, không ở trong `AuditLogger` — nếu thiếu, action chính phải fail trước khi ghi log (không ghi log cho hành động đã fail).

**Endpoint xem:**

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/audit-logs` | List, lọc `entity_type`, `entity_id`, `action`, khoảng thời gian | Admin: toàn bộ. Teacher: chỉ `entity_type` thuộc business history (`Attendance`, `Grade`, `Schedule`, `LessonSession`, `ClassMember`, `TeacherClassAssignment`...) và entity nằm trong scope Class/Course của Teacher |

## 4. Danh sách action cần gắn `AuditLogger::record()` vào controller đã có

Theo `BR-AUD-002`/`DEC-AUD-001`. `reason bắt buộc` nghĩa là controller phải validate request có `reason` trước khi cho phép action, rồi truyền vào `AuditLogger::record()`.

| Module | Controller (file đã có) | `action` | `reason` bắt buộc? |
|---|---|---|---|
| 1 | `UpdateUserStatusController` | `user.status_update` | Không |
| 1 | `AssignUserRoleController`, `EndUserRoleController` | `user_role.assign` / `user_role.end` | Không |
| 1 | `CreateParentStudentRelationshipController`, `EndParentStudentRelationshipController` | `parent_student_relationship.create` / `.end` | Không |
| 1 | `AddClassMemberController`, `EndClassMemberController` | `class_member.add` / `.end` | Không |
| 1 | **`UpdateClassMemberController`** (nợ cụ thể từ Plan 2, xem `docs/progress.md` mục 5) | `class_member.retroactive_update` | **Có** — request đã bắt buộc `reason` theo `BR-USER-017`, giờ mới có nơi ghi |
| 1 | `AssignTeacherToClassController`, `EndTeacherClassAssignmentController` | `teacher_class_assignment.assign` / `.end` | Không |
| 2 | `UpdateAttendanceRecordController` | `attendance_record.update` | **Có** (`BR-AUD-003`) |
| 2 | `ReviewAbsenceExplanationController` | `absence_explanation.review` | Không |
| 2 | `UpdateScheduleController`, `CancelScheduleController` | `schedule.update` / `.cancel` | **Có** (`BR-AUD-003`) |
| 2 | `UpdateLessonSessionController`, `CancelLessonSessionController` | `lesson_session.update` / `.cancel` | **Có** |
| 3 | `PublishAssessmentController`, `CloseAssessmentController`, `ReopenAssessmentController`, `CancelAssessmentController` | `assessment.publish` / `.close` / `.reopen` / `.cancel` | Không (log lại `reason` của `cancel` nếu có, nhưng không bắt buộc riêng ở tầng Audit) |
| 4 | `GradeQuestionController`, `GradeSubmissionController` | `grade.create` / `.update` | **Có, chỉ khi sửa Grade của Result đã `published`** (dùng đúng điều kiện code đã có) |
| 4 | `PublishResultController`, `WithdrawResultController`, `BulkPublishResultsController` | `result.publish` / `.withdraw` | Không |
| 4 | `ReissueAttemptController` | `attempt.reissue` | **Có** (request đã bắt buộc `reason`) |
| 4 | `SubmitAttempt` (Action, dùng chung cho POST nộp bài và GET tự nộp khi hết giờ) | `attempt.submit` | Không — bổ sung theo `ARCH-19` (2026-10-19), `DEC-AUD-001` liệt kê "Submission" là event bắt buộc |

**Mẫu sửa (áp dụng lặp lại cho toàn bộ bảng trên):** ngay sau khi action chính thành công (sau `update()`/`create()`), gọi:
```php
AuditLogger::record(
    actor: $request->user(),
    action: 'grade.update',
    entityType: 'Grade',
    entityId: $grade->id,
    before: $before,   // snapshot lấy TRƯỚC update(), như pattern $before = $existing->only([...]) đã có ở GradeQuestionController
    after: $grade->only(['score', 'max_score']),
    reason: $data['reason'] ?? null,
);
```

## 5. Notification — model + service + dedupe

Schema: `Notification(id, recipient_user_id, event_key, event_type, title, body, created_at, read_at nullable)` — **unique `(recipient_user_id, event_key)`**. `NotificationContext(id, notification_id, [đúng 1 trong] assessment_id/lesson_session_id/result_id/schedule_id)`.

**Service** `app/Services/Notification/NotificationDispatcher.php`:
```
NotificationDispatcher::dispatch(recipients: Collection<User>, eventKey: string, eventType: string, title: string, body: string, context: array): void
```
Dùng `Notification::firstOrCreate(['recipient_user_id' => ..., 'event_key' => ...], [...])` cho mỗi recipient — chống trùng tự nhiên qua unique constraint (`BR-NOTIF-004`). `event_key` phải encode đủ để cùng sự kiện ra cùng key, sự kiện khác (dù cùng loại) ra key khác (ví dụ thêm timestamp cho event "cập nhật").

**Endpoint cho người nhận:**

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/me/notifications` | List của tôi, lọc `read=true/false` | Mọi role đã login |
| `PATCH /api/v1/notifications/{notification}/read` | Đánh dấu đã đọc (`read_at=now()` nếu chưa có) | Chỉ `recipient_user_id` = actor |

**Nguyên tắc bắt buộc (`BR-NOTIF-007`):** response của 2 endpoint trên **không trả nội dung đối tượng được dẫn chiếu**, chỉ trả `title`/`body`/`context={type, id}` — client tự gọi API gốc để lấy chi tiết, API gốc tự kiểm tra quyền lại từ đầu.

## 6. Danh sách event cần gắn `NotificationDispatcher::dispatch()` vào controller đã có

| Module | Controller | Event | Recipient |
|---|---|---|---|
| 3 | `AddAssessmentAudienceController` | Giao Assessment mới | Từng Student mới có `StudentAssessment` qua audience vừa tạo + Parent (nếu `ParentStudentRelationship` hiệu lực) |
| 3 | `UpdateAssessmentController`, `PublishAssessmentController` (khi republish version đã có Student) | Cập nhật Assessment đã giao | Toàn bộ Student có `StudentAssessment.status=assigned` + Parent liên quan |
| 2 | `UpdateScheduleController`, `CancelScheduleController` | Đổi/hủy lịch học | Student (`ClassMember` active) + Teacher (`TeacherClassAssignment` active) của Class |
| 2 | `UpdateLessonSessionController`, `CancelLessonSessionController` | Đổi/hủy buổi học cụ thể | Như trên, scope theo buổi |
| 4 | `PublishResultController`, `BulkPublishResultsController` | Công bố kết quả | Student (owner `StudentAssessment`) + Parent liên quan |
| 2 | `CreateAbsenceExplanationController` | Cha mẹ nộp đơn xin phép | Teacher phụ trách Class của Student đó |
| 2 | `ReviewAbsenceExplanationController` | Giáo viên duyệt/từ chối đơn | Parent đã nộp đơn + Student |

**Parent filter dùng chung mọi event:** chỉ thêm Parent vào recipient nếu `ParentStudentRelationship` còn hiệu lực (`valid_to` null hoặc tương lai) — tái dùng điều kiện đã viết ở Module 4 (`GetChildResultController`).

## 7. API upload/liên kết/xem file (Attachment/AttachmentLink)

File lưu ở dịch vụ bên ngoài qua Laravel Storage abstraction, driver S3-compatible (MinIO dev/staging, AWS S3 production — đã chốt `architecture/technology-decisions.md`, đổi qua env không đổi code). `Attachment.storage_key` chỉ lưu metadata/key tham chiếu, **không lưu binary trong DB**.

**Giới hạn V1 (chốt tạm qua config, không hardcode — `ARCH-12` vẫn mở để chốt chính thức sau):** `config('attachments.max_size_kb', 10240)` (10MB), `config('attachments.allowed_mime_types', [...pdf, doc, docx, jpeg, png, zip])`.

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/attachments` | Upload file (multipart) qua Laravel Storage. Validate `size`/`mime` theo config. Tạo `Attachment(status=ready, uploaded_by_user_id=actor, storage_key, file_name, media_type, size_bytes, checksum)` | Admin, Teacher, Student (chỉ dùng để trả lời `file_upload`) |
| `POST /api/v1/attachments/{attachment}/link` | Tạo `AttachmentLink`: đúng 1 trong `question_version_id`/`assessment_version_id`/`answer_id`/`feedback_id`, `visibility`. Validate actor có quyền ghi đúng đối tượng đích | như trên, theo target |
| `GET /api/v1/attachments/{attachment}` | Trả metadata + signed URL tải ngắn hạn qua Storage (không serve binary qua Laravel) | Cần ≥1 `AttachmentLink` mà actor có quyền xem đối tượng đích **và** `visibility` cho phép |
| `DELETE /api/v1/attachments/{attachment}` | Chỉ xóa khi chưa có `AttachmentLink` nào tham chiếu (`409` nếu đã có — `BR-FILE-003`) | `uploaded_by_user_id` = actor, hoặc Admin |

**Kiểm tra quyền xem (`GET`) theo loại target (tái dùng logic module trước):**
- `question_version_id`/`assessment_version_id`: quyền xem Question/Assessment (Admin, Teacher chủ/scope).
- `answer_id`: Student owner của Attempt, hoặc Teacher/Admin trong scope Submission (như `ListSubmissionsController::authorizeScope`), hoặc Parent nếu `visibility=parent_if_published` và Result đã publish.
- `feedback_id`: theo `Feedback.visibility`/`status` — `learner`: Student owner; `parent_if_published`: Parent nếu `published`; `private`: chỉ tác giả Teacher/Admin.

## 8. Quyết định phát sinh trong lúc thiết kế

- `AuditLogger`/`NotificationDispatcher` được gọi **trực tiếp inline trong từng controller cũ** (không dùng Laravel Model Observer/Event) — khớp convention hiện tại (mọi logic inline, không event-driven), và vì `AuditLog` cần `actor`/`reason` chỉ có sẵn trong request context, Observer không lấy được đáng tin cậy.
- Giới hạn file size/format chốt tạm qua config (10MB, pdf/doc/docx/jpeg/png/zip) — không phải business rule mới, chỉ là giá trị kỹ thuật tạm cho V1; `ARCH-12` (File retention chi tiết) vẫn còn mở ở Step 8 để chốt chính thức sau.
- Thêm event "AbsenceExplanation nộp/duyệt" vào danh sách Notification — mở rộng hợp lý từ `BR-NOTIF-002` ("sự kiện học tập cần hành động"), không phải business rule mới.
- `Notification`/`AuditLog` view endpoint chỉ trả metadata/context tham chiếu, không trả nội dung đối tượng đích — bắt buộc theo `BR-NOTIF-007`.

## 9. Ngoài phạm vi spec này

- Email/SMS/push notification — V1 chỉ in-app (`BR-NOTIF-001`).
- Chính sách retention/pháp lý chi tiết cho file và AuditLog (`ARCH-12`, thời hạn lưu theo luật) — vẫn mở ở Step 8, không tự chốt số liệu pháp lý tại Step 9.
- Plan FE (Notification/File/Audit UI) — làm sau khi BE xong.
- Chi tiết JSON Schema, rate-limit, cấu hình CORS, cấu hình signed URL cụ thể của driver S3 — thuộc `writing-plans`/implementation.

## 10. Nguồn tham khảo

`docs/requirements/notification-rules.md`, `file-and-audit-rules.md`, `decision-log.md` (`DEC-NOTIF-*`, `DEC-FILE-*`, `DEC-AUD-*`), `docs/access-control/permission-matrix.md`, `resource-permissions.md`, `data-scope.md`, `docs/database/schema.md` (bảng `Notification`, `NotificationContext`, `Attachment`, `AttachmentLink`, `AuditLog`), `relationships.md`, `data-lifecycle.md`, `docs/architecture/technology-decisions.md` (File storage, S3-compatible), `docs/architecture/open-decisions.md` (`ARCH-12`), `docs/superpowers/specs/2026-10-15-question-bank-assessment-api-design.md` §9, `2026-10-15-submission-grading-result-progress-api-design.md` §10, `docs/progress.md` (checklist mục 5, nợ `UpdateClassMemberController`).
