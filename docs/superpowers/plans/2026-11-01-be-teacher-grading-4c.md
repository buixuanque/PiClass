# BE lát 4c Giáo viên (TC-11, TC-12, TC-13): chấm bài, công bố điểm, file bài làm Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Cho FE Giáo viên dựng được TC-11/12/13: danh sách bài nộp và học sinh theo bài có tên và tiến độ, chi tiết bài nộp có đề đúng phiên bản, giữ điểm rồi công bố ngay hoặc hẹn giờ kèm ba cờ hiển thị, và file bài làm / file đính kèm nhận xét.

**Architecture:** Mở rộng module sẵn có, không thêm module. `ResultProgress` giữ cờ hiển thị và giờ hẹn trên chính `results`, công bố hẹn giờ chạy bằng scheduler mỗi phút. `SubmissionGrading` và `Assessment` làm giàu response đọc. `File` cho gắn file vào câu trả lời và nhận xét, đĩa lưu chọn bằng biến môi trường `ATTACHMENT_DISK`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL, Scramble (OpenAPI), PHPStan, Pint.

**Spec:** `docs/requirements/decision-log.md` (`DEC-LRN-027`); màn hình `docs/product/screens/teacher-bai-danh-gia.md` (TC-11…13, khoảng trống TB-2, TB-5, TB-6, TB-9, G6); mockup đã merge `piclass-mockup` #28 (`PiClass Giáo viên`); `docs/BACKLOG.md` `B-073`, `B-067`; `docs/requirements/assessment-rules.md` `BR-ASSESS-025`; `DEC-FILE-001..003`.

## Global Constraints

- Đọc `piclass-api/CLAUDE.md` trước; vi phạm R1–R11 là lỗi (`ArchitectureTest`). Controller mỏng, Action chỉ có `handle()`, Domain thuần, thời gian đi qua `AppTime` (đọc `docs/rules/time.md` trước khi viết field ngày giờ; mirror cách `CreateAssessmentRequest` nhận `opens_at`).
- Comment trong code và mô tả test bằng tiếng Việt; tên hàm, tên test method, `error.code`, commit message bằng tiếng Anh. Commit message kết thúc bằng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`.
- Mã lỗi mới: xem `docs/architecture/error-codes.md` trước, dùng lại mã cũ nếu cùng điều kiện, cập nhật danh mục trong cùng PR. Test lỗi assert cả `code` lẫn `message` và status.
- Sau khi đổi endpoint hoặc field: `docker compose exec api php artisan scramble:export` (`piclass-infra/local`) rồi commit `storage/api-docs/openapi.json`.
- Mỗi task chạy: nhóm test liên quan, `vendor/bin/phpstan`, `vendor/bin/pint`. Task cuối chạy cả bộ test.
- Giới hạn file bài làm và nhận xét là config đọc từ env, mặc định: tối đa **1 file**, **5120 KB**, `image/jpeg`, `image/png`, `application/pdf`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`.
- Nhánh từ `origin/main`, push nhánh, không push `main`, không mở PR hay merge khi user chưa nói.

## Quyết định thiết kế đã khóa trong plan này

1. **Mặc định giữ điểm.** Chấm xong không còn tự công bố. Chỉ có ba đường công bố: giáo viên bấm công bố, công bố hàng loạt, hoặc đến giờ hẹn. Cột `assessment_versions.result_release_at` không còn được đọc (giữ cột, ghi vào `docs/architecture/known-debt.md`). Đây là đổi hành vi so với hôm nay (hiện tự công bố khi `result_release_at` null), cần user đã chốt ở `DEC-LRN-027` mục 1.
2. **Cờ hiển thị và giờ hẹn nằm trên `results`**, theo từng lần công bố (`B-073`): ba cột boolean nullable `show_answers`, `show_explanations`, `show_question_scores` (null nghĩa là dùng cờ của phiên bản bài), `scheduled_publish_at` (timestamp nullable), `scheduled_by_user_id` (FK `users`, nullable).
3. **Hẹn giờ** chạy bằng `Schedule::call` mỗi phút trong `routes/console.php`; hạ tầng phải có cron `schedule:run` (việc của `piclass-infra`, ghi vào mô tả PR).
4. **Phạm vi chưa làm:** hủy hẹn giờ riêng (muốn đổi thì công bố ngay hoặc hẹn lại), `late_policy` của `B-067`, upload thẳng lên S3 bằng URL ký sẵn, lịch sử sửa điểm (G5).

## Review Focus

- Hẹn giờ ở quá khứ hoặc đúng hiện tại: 422 theo trường `publish_at`, không công bố ngầm.
- Đến giờ hẹn nhưng bài chưa chấm xong: job bỏ qua, công bố khi chấm xong ở lần chạy sau; bài `invalidated` không bao giờ được job công bố.
- Phụ huynh không tải được file bài làm của con trước khi kết quả công bố; học sinh khác trong lớp không tải được file của bạn (404, không lộ tồn tại).
- Giáo viên không phải người tạo bài và không dạy lớp/khóa của bài: 404 khi tải file bài làm và khi xem danh sách bài nộp.
- Tải file `.zip` hoặc file hơn 5 MB đã `POST /attachments` được (giới hạn chung 10 MB) nhưng bị chặn lúc gắn vào câu trả lời/nhận xét.
- Gắn file vào câu không phải `file_upload`, hoặc vào bài làm đã nộp (`attempt` không còn `in_progress`): 422.
- Học sinh của khóa chưa bắt đầu làm (dòng ảo `id` null) vẫn có tên và `progress = not_started`.
- Công bố hàng loạt khi không có kết quả nào sẵn sàng: 200 với danh sách rỗng, không lỗi.

---

### Task 1: Đĩa lưu file chọn bằng `ATTACHMENT_DISK`

**Files:**
- Modify: `config/attachments.php`, `.env.example`, `app/Modules/File/Providers/FileServiceProvider.php`
- Rename: `app/Modules/File/Storage/S3AttachmentStorage.php` → `DiskAttachmentStorage.php`
- Test: `tests/Unit/Modules/File/DiskAttachmentStorageTest.php`

**Interfaces:**
- Produces: `config('attachments.disk')` (mặc định `local`); `DiskAttachmentStorage implements AttachmentStorage` với `put(string $key, string $contents): bool`, `delete(string $key): void`, `temporaryUrl(string $key, DateTimeInterface $expiresAt): string`. Interface `AttachmentStorage` không đổi.

- [ ] **Step 1: Viết test `test_put_then_temporary_url_then_delete_on_configured_disk`**: `config(['attachments.disk' => 'local'])`, `Storage::fake('local', ['serve' => true])`; `put('attachments/a.pdf', 'x')` trả `true` và file tồn tại; `temporaryUrl(..., now()->addMinutes(10))` trả chuỗi chứa `attachments/a.pdf`; `delete` làm file biến mất. Thêm `test_uses_disk_name_from_config`: đổi `attachments.disk` sang `s3` với `Storage::fake('s3')` và assert `put` ghi vào đĩa `s3`. Nếu `temporaryUrl` ném `RuntimeException` trên đĩa giả thì truyền `['serve' => true]` cho `Storage::fake`.
- [ ] **Step 2: Chạy `php artisan test --filter=DiskAttachmentStorageTest`, mong đợi FAIL (class chưa có).**
- [ ] **Step 3: Đổi tên lớp thành `DiskAttachmentStorage`, thay hằng `DISK = 's3'` bằng `config('attachments.disk')`; sửa binding trong `FileServiceProvider`; thêm `'disk' => env('ATTACHMENT_DISK', 'local')` vào `config/attachments.php`; thêm `ATTACHMENT_DISK=local` kèm comment "production đặt `s3` và điền `AWS_*`" vào `.env.example`. Sửa mọi chỗ còn tham chiếu `S3AttachmentStorage` (grep) và docblock tiếng Việt.**
- [ ] **Step 4: Chạy `php artisan test tests/Unit/Modules/File tests/Feature/File`, mong đợi PASS.**
- [ ] **Step 5: Commit** `refactor(file): pick attachment disk from ATTACHMENT_DISK`.

### Task 2: Cột cờ hiển thị và giờ hẹn trên `results`, công bố nhận tùy chọn

**Files:**
- Create: `database/migrations/2026_11_01_000001_add_display_flags_and_schedule_to_results_table.php`, `app/Modules/ResultProgress/Data/PublishOptions.php`
- Modify: `app/Models/Result.php`, `app/Modules/ResultProgress/Actions/PublishResult.php`
- Test: `tests/Feature/ResultProgress/PublishResultOptionsTest.php`

**Interfaces:**
- Produces: `final readonly class PublishOptions { public function __construct(public ?bool $showAnswers = null, public ?bool $showExplanations = null, public ?bool $showQuestionScores = null) }`; `PublishResult::handle(Result $result, User $actor, bool $auto = false, ?PublishOptions $options = null): void`. Khi `$options` khác null thì ghi ba cờ (giá trị null giữ nguyên cột); luôn xóa `scheduled_publish_at` và `scheduled_by_user_id` khi công bố xong. `Result` cast ba cờ thành `bool` nullable, `scheduled_publish_at` thành `datetime`.

- [ ] **Step 1: Viết test**: `test_publish_stores_chosen_flags` (publish với `new PublishOptions(true, false, true)` thì `result->show_answers === true`, `show_explanations === false`, `show_question_scores === true`); `test_publish_without_options_leaves_flags_null`; `test_publish_clears_schedule` (result có `scheduled_publish_at` và `scheduled_by_user_id`, sau publish cả hai null); `test_republish_after_withdraw_keeps_flags`.
- [ ] **Step 2: Chạy test, mong đợi FAIL (cột chưa có).**
- [ ] **Step 3: Viết migration (năm cột như Quyết định 2, `down()` bỏ cột), thêm vào `$fillable` và `$casts` của `Result`, thêm `PublishOptions`, sửa `PublishResult` theo Interfaces; ghi cờ trong cùng transaction, thêm `show_*` vào `after` của audit `result.publish`.**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/ResultProgress`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(results): store display flags and schedule on results`.

### Task 3: Bỏ tự công bố khi chấm xong, công bố hẹn giờ bằng scheduler

**Files:**
- Create: `app/Modules/ResultProgress/Actions/PublishDueResults.php`, `app/Modules/ResultProgress/Actions/ScheduleResultPublication.php`
- Modify: `app/Modules/ResultProgress/Actions/RecalculateResult.php`, `routes/console.php`, `docs/architecture/known-debt.md` (repo docs)
- Test: `tests/Feature/ResultProgress/PublishDueResultsTest.php`; sửa các test đang kỳ vọng tự công bố: `RecalculateResultActionTest`, `GradeQuestionTest`, `ResultNotificationTest`, `ResultAuditTest`, `GradingEndpointsBehaviorTest`, `SubmitAttemptTest` (grep `result_release_at`)

**Interfaces:**
- Consumes: `PublishResult::handle(..., auto: true, options: PublishOptions)` từ Task 2.
- Produces: `ScheduleResultPublication::handle(Result $result, User $actor, CarbonInterface $at, PublishOptions $options): Result` (ghi `scheduled_publish_at`, `scheduled_by_user_id`, ba cờ; result đã `published` thì không đổi gì; audit `result.schedule_publish`); `PublishDueResults::handle(): int` trả số kết quả đã công bố.

- [ ] **Step 1: Viết test** `PublishDueResultsTest`: `test_publishes_due_complete_result_with_stored_flags` (đến hạn, `complete`, thì `published`, giữ ba cờ, `scheduled_*` null, publication có `actor_user_id` = `scheduled_by_user_id`, audit `actor` null); `test_skips_not_yet_due`; `test_skips_incomplete_grading_then_publishes_after_complete`; `test_never_publishes_invalidated_submission`; `test_returns_count`. Trong `RecalculateResultActionTest`: `test_grading_complete_does_not_auto_publish_even_when_release_at_is_past`.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Bỏ khối auto-publish và tham số `$publish` của `RecalculateResult` (cập nhật docblock); viết hai Action theo Interfaces (`PublishDueResults` dùng `Result` có `scheduled_publish_at <= now()`, `unpublished`, submission không `invalidated`, bỏ qua `grading_status != complete`); đăng ký trong `routes/console.php`: `Schedule::call(fn (PublishDueResults $action) => $action->handle())->name('results:publish-due')->everyMinute()->withoutOverlapping();`. Sửa các test cũ kỳ vọng tự công bố sang gọi công bố tường minh. Ghi `result_release_at` không còn dùng vào `known-debt.md`.**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/ResultProgress tests/Feature/SubmissionGrading tests/Feature/Grading tests/Feature/Notification tests/Feature/Audit tests/Feature/Submission`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(results): hold results by default and publish due results on schedule`.

### Task 4: Endpoint công bố nhận cờ và giờ hẹn (từng bài và hàng loạt)

**Files:**
- Modify: `app/Modules/ResultProgress/Http/Requests/PublishResultRequest.php`, `BulkPublishResultsRequest.php`, `Http/Controllers/PublishResultController.php`, `BulkPublishResultsController.php`, `Http/Resources/BulkPublishResultsResource.php`, `Actions/BulkPublishResults.php`
- Test: `tests/Feature/ResultProgress/PublishEndpointsOptionsTest.php`

**Interfaces:**
- Consumes: `PublishOptions`, `ScheduleResultPublication`, `PublishResult` (Task 2, 3).
- Produces: body tùy chọn cho `POST /results/{result}/publish` và `POST /assessments/{assessment}/results/publish`: `publish_at` (date, phải sau hiện tại), `show_answers`, `show_explanations`, `show_question_scores` (boolean). `BulkPublishResults::handle(Assessment $assessment, User $actor, PublishOptions $options = new PublishOptions, ?CarbonInterface $at = null): array{published: list<string>, scheduled: list<string>, skipped: list<array{id: string, reason: string}>}`; `scheduled` rỗng khi không có `$at`. Có `publish_at` thì kết quả chuyển thành hẹn giờ và response `result` có `scheduled_publish_at`, `publication_status` vẫn `unpublished`.

- [ ] **Step 1: Viết test**: `test_publish_now_with_flags` (response `result.show_answers === true`, `publication_status = published`); `test_publish_with_publish_at_schedules_instead` (`unpublished`, `scheduled_publish_at` đúng, cờ đã lưu, chưa có thông báo); `test_publish_at_in_the_past_is_rejected_with_422_on_publish_at`; `test_bulk_schedules_only_complete_results_and_skips_the_rest` (`scheduled` có id bài xong, `skipped` có lý do `grading chưa hoàn tất`); `test_bulk_with_nothing_ready_returns_empty_lists`; `test_bulk_without_body_behaves_as_before` (giữ key `published`, `skipped`).
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Thêm `rules()` vào hai Request (mirror rule ngày giờ của `opens_at`, `publish_at` dùng `after:now`); Controller dựng `PublishOptions` từ `validated()` rồi gọi `ScheduleResultPublication` hoặc `PublishResult`; `BulkPublishResults` mở rộng theo Interfaces; Resource thêm khóa `scheduled`.**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/ResultProgress`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(results): publish endpoints accept display flags and schedule`.

### Task 5: Màn kết quả học sinh/phụ huynh đọc cờ theo từng lần công bố

**Files:**
- Modify: `app/Modules/ResultProgress/Queries/StudentAssessmentResultQuery.php`
- Test: `tests/Feature/ResultProgress/StudentResultFlagsTest.php`

**Interfaces:**
- Consumes: ba cột trên `Result` (Task 2).
- Produces: `result.show_answers|show_explanations|show_question_scores` = `$representative->{cờ} ?? $version->{cờ}`.

- [ ] **Step 1: Viết test**: `test_flags_come_from_published_result_when_set` (version `show_answers = false`, result `true` thì response `true`); `test_flags_fall_back_to_version_when_result_flags_null`; áp dụng cho `GET /me/assessments/{id}/result` và `GET /me/children/{studentId}/assessments/{id}/result`.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Sửa ba dòng `show_*` trong query theo Interfaces.**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/ResultProgress`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(results): student result reads display flags from the result`.

### Task 6: Danh sách bài nộp và chi tiết bài nộp có học sinh, số lượt, đề đúng phiên bản (TB-5, TB-2)

**Files:**
- Modify: `app/Modules/SubmissionGrading/Queries/ListSubmissions.php`, `Queries/GetSubmission.php`, `Http/Resources/SubmissionSummaryResource.php`, `Http/Resources/SubmissionDetailResource.php`, `app/Models/Feedback.php` (nếu cần)
- Test: `tests/Feature/SubmissionGrading/SubmissionReadEnrichmentTest.php`

**Interfaces:**
- Produces, mỗi dòng `GET /assessments/{id}/submissions` giữ mọi khóa hiện có và thêm `student: {id, full_name}`, `student_assessment_id`, `attempt_number`; không để quan hệ `attempt` rò vào JSON. `GET /submissions/{id}` giữ khóa hiện có và thêm `submission.student: {id, full_name}`, `submission.version: {id, version_number, title, max_score, questions: [...]}` (mỗi câu cùng dạng với `current_version.questions` của `GET /assessments/{id}`, gồm snapshot và đáp án đúng, lấy từ `attempt.assessmentVersion` chứ không phải phiên bản hiện tại), và mỗi `feedback[]` thêm `author: {id, name}`.

- [ ] **Step 1: Viết test**: `test_list_rows_carry_student_name_and_attempt_number`; `test_list_does_not_leak_attempt_relation`; `test_detail_returns_questions_of_the_attempted_version_not_current` (bài có version 1 đã làm và version 2 hiện tại khác đề: response trả đề version 1); `test_detail_carries_student_and_feedback_author_name`; `test_teacher_outside_scope_still_gets_404` (giữ hành vi cũ).
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: `ListSubmissions` thêm `with('attempt.studentAssessment.student.personProfile')`; `GetSubmission` thêm `attempt.studentAssessment.student.personProfile`, `attempt.assessmentVersion.questions.sourceQuestionVersion.options`, `feedback.author`; hai Resource dựng JSON theo Interfaces. Tên người lấy từ `personProfile->full_name`, tên tác giả nhận xét từ `author` (xem cách module khác lấy tên người dùng).**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/SubmissionGrading tests/Feature/Grading`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(grading): student, attempt number and attempted version in submission reads`.

### Task 7: Học sinh theo bài có tên và tiến độ (TB-9)

**Files:**
- Create: `app/Modules/Assessment/Enums/StudentAssessmentProgress.php`
- Modify: `app/Modules/Assessment/Queries/ListAssessmentStudentsQuery.php`, `Http/Resources/AssessmentStudentListResource.php`, `app/Models/StudentAssessment.php` (quan hệ `attempts()` nếu chưa có)
- Test: `tests/Feature/Assessment/AssessmentStudentsProgressTest.php`

**Interfaces:**
- Produces: `enum StudentAssessmentProgress: string { NotStarted = 'not_started'; InProgress = 'in_progress'; Submitted = 'submitted' }`. Mỗi phần tử `data[]` của `GET /assessments/{id}/students` giữ khóa hiện có và thêm `student: {id, full_name}`, `attempts_used` (int), `progress` (giá trị enum), `latest_submission_id` (uuid hoặc null). Quy tắc `progress`: có lượt `in_progress` thì `in_progress`; ngược lại có bài nộp không `invalidated` thì `submitted`; ngược lại `not_started`. Dòng ảo của khóa (`id` null) luôn `not_started`, `attempts_used = 0`, vẫn có `student`.

- [ ] **Step 1: Viết test**: `test_progress_not_started_in_progress_submitted`; `test_invalidated_attempt_does_not_count_as_submitted`; `test_course_virtual_row_has_name_and_not_started`; `test_attempts_used_counts_all_attempts`.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Query eager load `student.personProfile`, `attempts.submission`; với dòng ảo gắn quan hệ `student` từ `$learner['learner']`; Resource dựng mảng theo Interfaces; dùng Enum, không literal chuỗi (R8).**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/Assessment`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(assessment): student name and progress in assessment students list`.

### Task 8: Gắn file vào câu trả lời và nhận xét, giới hạn riêng, quyền xem (G6)

**Files:**
- Create: `app/Modules/File/Exceptions/AttachmentLimitExceeded.php` (422 `ATTACHMENT_LIMIT_EXCEEDED`), `AttachmentNotAllowed.php` (422 `ATTACHMENT_NOT_ALLOWED`), `app/Modules/File/Queries/SubmissionFileAccessQuery.php`
- Modify: `config/attachments.php`, `app/Modules/File/Http/Requests/LinkAttachmentRequest.php`, `Policies/AttachmentPolicy.php`, `app/Models/Answer.php`, `app/Models/Feedback.php` (quan hệ `attachmentLinks()`), bỏ `LinkTargetUnsupported` nếu không còn dùng (xóa luôn test của nó), `docs/architecture/error-codes.md` (repo docs)
- Test: `tests/Feature/File/AnswerAndFeedbackFilesTest.php`

**Interfaces:**
- Produces: `config('attachments.answer')` = `['max_files' => env('ATTACHMENTS_ANSWER_MAX_FILES', 1), 'max_size_kb' => env('ATTACHMENTS_ANSWER_MAX_SIZE_KB', 5120), 'mime_types' => [...năm loại ở Global Constraints]]`, dùng chung cho nhận xét. `POST /attachments/{attachment}/link` chấp nhận `answer_id` và `feedback_id`:
  - **Answer:** chỉ học sinh chủ bài làm, `attempt.status = in_progress`, câu thuộc loại `file_upload`; `visibility` chỉ nhận `private` hoặc `parent_if_published`.
  - **Feedback:** chỉ tác giả nhận xét hoặc Admin; `visibility` của link bị ghi đè bằng `feedback.visibility` (cùng bộ giá trị).
  - Vượt `max_files` thì `AttachmentLimitExceeded`; quá `max_size_kb` hoặc sai `mime_types` thì `AttachmentNotAllowed`.
  - **Xem file** (`AttachmentPolicy::view`, qua `SubmissionFileAccessQuery`): file câu trả lời xem được bởi Admin, học sinh chủ bài, giáo viên là người tạo bài hoặc dạy lớp/khóa của bài (`ClassScope::teacherCoversAssessment`), và phụ huynh của học sinh chỉ khi link là `parent_if_published` và kết quả của bài nộp đó đã `published`. File nhận xét xem được bởi Admin, tác giả, giáo viên như trên (mọi trạng thái), còn học sinh/phụ huynh chỉ khi nhận xét `published` và kết quả đã `published` và `visibility` cho phép (học sinh: `learner`; phụ huynh: `learner` hoặc `parent_if_published`). Người còn lại nhận 404.

- [ ] **Step 1: Viết test**: `test_student_links_file_to_own_file_upload_answer`; `test_second_file_on_same_answer_is_rejected_with_limit_code`; `test_zip_and_oversize_are_not_allowed_for_answers` (assert `code` và message); `test_cannot_link_to_non_file_upload_question`; `test_cannot_link_after_attempt_submitted`; `test_other_student_cannot_link_or_view`; `test_parent_cannot_view_answer_file_until_result_published`; `test_covering_teacher_and_creator_can_view_but_unrelated_teacher_gets_404`; `test_feedback_file_follows_feedback_visibility_and_published_gates`; `test_limits_are_read_from_config` (đổi `attachments.answer.max_files` thành 2 thì file thứ hai qua).
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo Interfaces: Request kiểm tra đích và giới hạn trong `after()` (cùng kiểu `InvalidLinkTarget`); `AttachmentPolicy::linkTarget` mở rộng cho `Answer` và `Feedback`; `canViewLink` thêm hai nhánh gọi `SubmissionFileAccessQuery`; thêm hai exception và dòng danh mục mã lỗi.**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/File tests/Unit/Modules/File`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(file): attach files to answers and feedback with configurable limits`.

### Task 9: Gỡ file khỏi bài làm/nhận xét và trả danh sách file trong chi tiết bài nộp

**Files:**
- Modify: `app/Modules/File/Actions/DeleteAttachment.php`, `app/Modules/SubmissionGrading/Queries/GetSubmission.php`, `Http/Resources/SubmissionDetailResource.php`
- Test: `tests/Feature/File/DetachSubmissionFilesTest.php`, bổ sung `SubmissionReadEnrichmentTest`

**Interfaces:**
- Consumes: quan hệ `attachmentLinks()` của `Answer`, `Feedback` (Task 8).
- Produces: `DELETE /attachments/{attachment}` gỡ được file chỉ gắn vào câu trả lời của lượt còn `in_progress` hoặc vào nhận xét `draft` (xóa link rồi xóa file); các link khác vẫn ném `AttachmentInUse`. Chi tiết bài nộp thêm `files: [{attachment_id, file_name, media_type, size_bytes}]` ở mỗi phần tử `attempt.answers[]` và `feedback[]`.

- [ ] **Step 1: Viết test**: `test_student_can_replace_file_while_attempt_in_progress` (xóa file cũ rồi gắn file mới thành công); `test_cannot_delete_file_of_submitted_answer` (409 `AttachmentInUse`); `test_draft_feedback_file_can_be_deleted_but_published_cannot`; `test_submission_detail_lists_files_on_answers_and_feedback`.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: `DeleteAttachment` xóa trước các link "gỡ được" trong cùng transaction rồi mới kiểm tra link còn lại; `GetSubmission` eager load `attempt.answers.attachmentLinks.attachment` và `feedback.attachmentLinks.attachment`; Resource ánh xạ sang `files`, không rò quan hệ thô.**
- [ ] **Step 4: Chạy `php artisan test tests/Feature/File tests/Feature/SubmissionGrading`, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(file): detach submission files and list them in submission detail`.

### Task 10: OpenAPI, tài liệu, bộ test đầy đủ

**Files:**
- Modify: `storage/api-docs/openapi.json` (export), `docs/product/screens/teacher-bai-danh-gia.md` (TC-11…13: bỏ TB-5, TB-9, G6 khỏi danh sách khoảng trống đã giải quyết; ghi mặc định giữ điểm, hộp công bố, giờ hẹn, ba cờ theo lần công bố, giới hạn file), `docs/BACKLOG.md` (`B-073` xong; `B-067` chỉ còn `late_policy`), `docs/architecture/error-codes.md`, `docs/STATE.md`

- [ ] **Step 1: Chạy `docker compose exec api php artisan scramble:export` ở `piclass-infra/local`, kiểm tra `openapi.json` có các body/field mới ở Task 4, 6, 7, 8, 9.**
- [ ] **Step 2: Cập nhật tài liệu repo docs như danh sách Files; ghi trong mô tả PR việc hạ tầng cần cron `schedule:run` và biến `ATTACHMENT_DISK`, `ATTACHMENTS_ANSWER_*`.**
- [ ] **Step 3: Chạy `php artisan test` (cả bộ), `vendor/bin/phpstan`, `vendor/bin/pint --test`, mong đợi xanh hết.**
- [ ] **Step 4: Commit** `docs(api): export OpenAPI for teacher grading slice 4c`.
- [ ] **Step 5: Review độc lập cuối branch bằng subagent `model: "opus"` (theo `CLAUDE.md` gốc), sửa phát hiện, rồi cập nhật `STATE.md` (bước `review` → `chờ merge`).**
