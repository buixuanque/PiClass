# Khóa học có nội dung học, lát 4: học sinh học nội dung, tiến độ, mở khóa tuần tự, điểm dừng (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Học sinh (học viên của khóa hoặc thuộc đối tượng nội dung độc lập) đọc bài giảng, tài liệu, video; ghi tiến độ; bị khóa theo thứ tự khi khóa bật `sequential_unlock`; xem video qua link phát có chữ ký; trả lời điểm dừng câu hỏi (lượt đầu được ghi một lần, không tua qua điểm dừng chưa đúng); xem chi tiết khóa kèm trạng thái từng mục và điều kiện hoàn thành khóa.

**Architecture:** Hai bảng mới (`content_progress`, `checkpoint_responses`) trong module `CourseContent`. Một chỗ kiểm quyền duy nhất cho học sinh, `LearnerContentAccess` (thấy được? bị khóa?), dùng chung cho đọc nội dung, ghi tiến độ, xin link phát, mở luồng phát và xem tệp đính kèm; `ContentVideoAccess::canWatch` gọi vào đó cho học sinh. Phần thuần (mở khóa tuần tự, điều kiện xong, kẹp vị trí, hoàn thành khóa) nằm ở `Domain/` để test không cần DB. Tiến độ tạo lười khi mở lần đầu, không lưu "hoàn thành khóa" (tính khi cần).

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL; chạy lệnh trong `piclass-infra/local` bằng `docker compose exec -T api ...`.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 3 `content_progress`, `checkpoint_responses`; mục 4 điều 1, 2, 3, 4, 5, 6; mục 7 API học sinh; mục 8; mục 13 ghi chú lát 3). Quyết định: `DEC-LRN-052` mục 3, 8, 11; `DEC-LRN-053` mục 3; `ARCH-39`. Quy ước code: `piclass-api/CLAUDE.md`, `docs/architecture/backend-gotchas.md`. **Ngoài lát này (lát 4b, plan riêng):** tiến độ cho Giáo viên/Admin (`GET /courses/{id}/progress`) và Phụ huynh (`GET /students/{id}/courses/{courseId}/progress`).

## Global Constraints

- Nhánh `feat/course-content-student` của `piclass-api`, cắt từ `origin/main` mới nhất. Không commit thẳng `main`.
- Hằng số trong `config/content.php`: thêm `video.completion_ratio` = `0.95` (xem ≥ 95% thời lượng thì tính xong). Không để hằng số rải trong code.
- Học sinh thấy nội dung khi: nội dung của khóa → `CourseLearnersQuery::isLearner(course_id, student_id)`; nội dung độc lập → có dòng `content_audiences` trực tiếp (`student_id`) hoặc là `ClassMember` đang hoạt động của một lớp trong `content_audiences` (cùng điều kiện như `CourseLearnersQuery`: `status=active`, `valid_to` null hoặc tương lai, lớp chưa lưu trữ). Quyền tính khi cần, mất lớp là mất quyền ngay. Nội dung `archived` đối với học sinh là 404.
- Không thấy được (ngoài đối tượng, nội dung lưu trữ, id lạ) → 404 với `DenialMessage::NOT_FOUND`; không có hồ sơ học sinh → 403 như `AttemptPolicy`. Phụ huynh, Giáo viên, Admin bị chặn ở route (`role:student`), riêng playback và xem tệp theo mục 5.
- Mở khóa tuần tự (spec mục 4 điều 2): khi `courses.sequential_unlock`, mục thứ n (theo `position`, bỏ qua `chapter` và bài đánh giá chưa `published`/`closed`) chỉ mở khi mọi mục trước đã xong; mục khóa → **403 `CONTENT_LOCKED`** kèm `details.blocked_by = {item_id, title}` (mục đầu tiên chưa xong) ở mọi endpoint đọc nội dung, ghi tiến độ, xin link phát, mở luồng phát, trả lời điểm dừng, tải tệp đính kèm. Nội dung độc lập không khóa.
- Mục xong (điều 3): bài giảng/tài liệu = bấm "đã học" (bỏ được); video = vị trí ≥ `completion_ratio` × thời lượng **và** mọi điểm dừng có `answered_correctly_at`, hoặc (thời lượng không biết / nguồn link ngoài) học sinh gửi `completed=true` **và** mọi điểm dừng đúng; bài đánh giá = có bài nộp chưa `invalidated` (nộp trễ vẫn tính). Video đã `completed` không tự bỏ (thay tệp không xóa `completed`).
- Chặn tua (điều 4): vị trí tối đa = `at_seconds` của điểm dừng chưa trả lời đúng đầu tiên; `PUT progress` vượt mức bị kẹp (không báo lỗi); luôn kẹp thêm vào `duration_seconds` nếu biết; vị trí không âm.
- Lượt trả lời đầu của điểm dừng ghi **một lần**, không bao giờ ghi đè (`DEC-LRN-053` mục 3): `first_answer`, `first_is_correct`, `attempts_count`; lượt sau chỉ tăng `attempts_count` và đặt `answered_correctly_at` khi đúng lần đầu; đã đúng rồi thì lượt sau không đổi gì. Chấm bằng bộ chấm tự động sẵn có (`SubmissionGrading\Domain\AutoGrader`), "đúng" khi `score == max_score`.
- Hoàn thành khóa (điều 5), tính khi cần: `all_items` → mọi mục (trừ chương và bài đánh giá chưa mở) đã xong; `all_items_and_min_score` → thêm điểm trung bình các bài đánh giá của khóa ≥ `completion_min_score`, chỉ tính kết quả đã công bố và chấm xong (`publication_status=published`, `grading_status=complete`).
- Module `CourseContent` chỉ **đọc** Academic, Assessment, SubmissionGrading, ResultProgress, QuestionBank, File; ghi chỉ vào bảng của mình. `ArchitectureTest` (R1..R11) phải xanh: khai báo `ContentProgress`, `CheckpointResponse` trong `MODEL_OWNERS` (`CourseContent`), enum module khác dùng thêm vào `SHARED_ENUMS['CourseContent']`, không literal trùng giá trị enum (R8), thời gian qua `now()`/`AppTime` (R9–R11).
- Mã lỗi mới ghi vào `docs/architecture/error-codes.md`: `CONTENT_LOCKED` (403). Tên test method, mã lỗi, commit message tiếng Anh; comment và mô tả test tiếng Việt.
- TDD: mỗi thay đổi hành vi có test chạy đỏ trước rồi mới xanh. Cuối plan: cả bộ test, Pint (chỉ file đã đổi), PHPStan (không thêm lỗi mới so với baseline 30), xuất OpenAPI.

## Quyết định chốt khi viết plan (2026-11-27)

1. **Không có `/me/courses` trong code.** Thêm `GET /me/courses/{course}` (nhóm `role:student`) trả khóa + danh sách mục có trạng thái + hoàn thành khóa. `GET /courses/{id}/items` hiện có giữ nguyên (học sinh vẫn chỉ thấy bài đánh giá ở đó).
2. **Bài đánh giá trong danh sách mục** chỉ là một dòng `{assessment_id, title, status}`; học sinh mở bài bằng luồng `/me/assessments` sẵn có. Bài `draft` ẩn, không tính vào tuần tự và hoàn thành.
3. **Điểm dừng cần xác nhận lại (`needs_recheck`) vẫn phát cho học sinh** (ẩn đi sẽ lặng lẽ bỏ qua câu hỏi); việc xác nhận hay xóa là của giáo viên.
4. **`explanation` của câu trả lời điểm dừng để trống** (câu hỏi chưa có trường lời giải, `B-087`); trả `{is_correct, can_continue}`, thêm `explanation` khi `B-087` xong.
5. **Link ngoài**: server không tự biết xem được bao nhiêu; chỉ kiểm điểm dừng và `completed=true`; vị trí vẫn lưu để xem tiếp.
6. **Lát này làm phần còn nợ của lát 3**: thay video (`ReplaceContentVideo`) đặt `last_position_seconds = 0` cho mọi `content_progress` của nội dung đó, không đụng `status` (Task 8).
7. Không audit cho thao tác của học sinh (spec mục 8 chỉ liệt kê thao tác của giáo viên/Admin).

## Review Focus

Các điểm kế hoạch ngầm hiểu mà test thường không chạm; người review cuối nhánh kiểm từng dòng (mỗi dòng đã có test ở task chủ):

1. Học sinh ngoài đối tượng (kể cả cùng lớp nhưng lớp không có trong audience, học sinh đã rời lớp, nội dung `archived`) đọc, ghi tiến độ, xin link phát, mở luồng phát, trả lời điểm dừng, tải tệp: 404, không rò id; phụ huynh/giáo viên gọi route học sinh: 403 (Task 3–7).
2. Mục khóa tuần tự bị chặn ở **mọi** đường (đọc, tiến độ, playback, stream đã ký trước khi khóa lại, trả lời, tệp); mở lại khi mục trước xong; mục trước là bài đánh giá `draft` không chặn (Task 2, 3, 6).
3. Chặn tua: gửi vị trí vượt điểm dừng chưa đúng bị kẹp; trả lời sai nhiều lần giữ nguyên lượt đầu; hai lượt trả lời song song cho cùng điểm dừng không tạo hai dòng, không mất `attempts_count` (Task 5, 7).
4. Thay video giữa chừng: vị trí xem dở về 0 nhưng `completed` giữ; điểm dừng mới thêm sau khi học sinh đã `completed` không làm mất `completed` (Task 5, 8).
5. Video link ngoài hoặc không có thời lượng: không bao giờ tự `completed` bằng vị trí; `completed=true` khi còn điểm dừng chưa đúng bị bỏ qua (Task 5).
6. Hoàn thành khóa: khóa không có mục nào, khóa chỉ có chương, kết quả chưa công bố hoặc chưa chấm xong không tính điểm, `completion_min_score` biên bằng đúng ngưỡng (Task 6).

## File Structure

Tạo:
- `database/migrations/2026_11_29_000001_create_content_progress_tables.php`
- `app/Models/ContentProgress.php`, `CheckpointResponse.php` và factory tương ứng
- `app/Modules/CourseContent/Enums/ContentProgressStatus.php`, `ContentAccess.php` (`Ok`, `Hidden`, `Locked`)
- `app/Modules/CourseContent/Domain/`: `SequentialUnlock`, `ContentCompletion`, `CheckpointPosition`, `CourseCompletion`
- `app/Modules/CourseContent/Queries/`: `CourseItemStatusesQuery`, `LearnerContentAccess`, `ListMyContentsQuery`, `MyContentDetailQuery`, `MyCourseViewQuery`
- `app/Modules/CourseContent/Actions/`: `OpenContentProgress` (tạo lười), `UpdateContentProgress`, `AnswerCheckpoint`
- `app/Modules/CourseContent/Exceptions/ContentLocked.php`
- Requests, Controllers, Resources tương ứng cho từng endpoint (đặt tên ở từng task)
- Tests: `tests/Feature/CourseContent/{ContentProgressSchemaTest, LearnerContentAccessTest, MyContentReadTest, MyContentProgressTest, StudentPlaybackTest, CheckpointAnswerTest, MyCourseViewTest, ReplaceVideoProgressTest}`, `tests/Unit/Modules/CourseContent/{SequentialUnlockTest, ContentCompletionTest, CheckpointPositionTest, CourseCompletionTest}`

Sửa: `ContentVideoAccess` (nhánh học sinh), `AttachmentPolicy` (nhánh `learning_content` cho học sinh, module File), `PlaybackController` và `ManageLearningContentRequest` hoặc request mới cho playback học sinh, `ReplaceContentVideo`, `config/content.php`, `routes/api.php` (nhóm `role:student`, mẫu `Route::pattern` cho `checkpoint`), `tests/Architecture/ArchitectureTest.php`; tài liệu: `error-codes.md`, `model-ownership.md`, spec mục 14, `BACKLOG.md`, `STATE.md`.

---

### Task 1: Bảng, model, enum

**Files:**
- Create: migration, `ContentProgress`, `CheckpointResponse` + factory, `ContentProgressStatus`, `tests/Feature/CourseContent/ContentProgressSchemaTest.php`
- Modify: `ArchitectureTest` (`MODEL_OWNERS`), `docs/architecture/model-ownership.md`, `config/content.php` (`video.completion_ratio`)

**Interfaces:**
- Produces:
  - `ContentProgress` (`id` uuid, `student_id` FK `students` cascade, `content_id` FK `learning_contents` cascade, `status` `ContentProgressStatus` (`InProgress`, `Completed`), `last_position_seconds` int default 0 ≥ 0, `first_opened_at`, `completed_at` null, `updated_at`; unique `(student_id, content_id)`; quan hệ `student()`, `content()`).
  - `CheckpointResponse` (`id` uuid, `student_id`, `checkpoint_id` FK `video_checkpoints` cascade, `first_answer` json, `first_is_correct` bool, `attempts_count` int ≥ 1, `answered_correctly_at` null, timestamps; unique `(student_id, checkpoint_id)`; quan hệ `checkpoint()`).
  - `config('content.video.completion_ratio')` = `0.95`.

- [ ] **Step 1: Viết test schema** (`ContentProgressSchemaTest`): `test_one_progress_row_per_student_and_content`, `test_one_response_per_student_and_checkpoint`, `test_deleting_a_content_cascades_progress_and_deleting_a_checkpoint_cascades_responses`, `test_status_enum_values_match_the_check_constraint` (đọc `pg_get_constraintdef`, như `ContentVideoSchemaTest`), `test_negative_position_and_zero_attempts_are_rejected_by_check` (CHECK `last_position_seconds >= 0`, `attempts_count >= 1`).
- [ ] **Step 2: Chạy, thấy đỏ.** Run: `docker compose exec -T api php artisan test --filter=ContentProgressSchemaTest`. Expected: FAIL (bảng chưa có).
- [ ] **Step 3: Viết migration, model, factory (state `completed()` cho `ContentProgressFactory`), enum, cấu hình; khai báo `MODEL_OWNERS`; cập nhật `model-ownership.md`.**
- [ ] **Step 4: Chạy lại** test schema và `tests/Architecture`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: content progress and checkpoint response tables`.

---

### Task 2: Phần thuần và trạng thái các mục

**Files:**
- Create: `Domain/{SequentialUnlock, ContentCompletion, CheckpointPosition}`, `Queries/CourseItemStatusesQuery`, `Exceptions/ContentLocked`, `tests/Unit/Modules/CourseContent/{SequentialUnlockTest, ContentCompletionTest, CheckpointPositionTest}.php`, `tests/Feature/CourseContent/CourseItemStatusesTest.php`
- Modify: `error-codes.md`

**Interfaces:**
- Produces:
  - `SequentialUnlock::evaluate(list<array{id: string, title: string, counts: bool, done: bool}> $orderedItems, bool $sequential): array<string, array{locked: bool, blocked_by: ?array{item_id: string, title: string}}>` — chỉ mục `counts=true` (không phải chương, không phải bài đánh giá chưa mở) tham gia; `$sequential=false` thì không mục nào khóa; mục khóa trỏ tới mục **đầu tiên** chưa xong đứng trước nó.
  - `CheckpointPosition::maxAllowed(list<array{at_seconds: int, answered_correctly: bool}> $checkpoints): ?int` (giây của điểm dừng chưa đúng đầu tiên theo `at_seconds`, `null` nếu không có); `::clamp(int $requested, ?int $maxAllowed, ?int $duration): int` (≥ 0, ≤ `maxAllowed`, ≤ `duration` nếu có).
  - `ContentCompletion::isVideoDone(?int $durationSeconds, bool $external, int $positionSeconds, bool $allCheckpointsCorrect, bool $clientSaysDone, float $ratio): bool` — luật điều 3 ở Global Constraints.
  - `ContentLocked` (403 `CONTENT_LOCKED`, `details: {blocked_by: {item_id, title}}`).
  - `CourseItemStatusesQuery::handle(Course $course, Student $student): list<array{item: CourseItem, counts: bool, status: ContentProgressStatus|'not_started', done: bool, locked: bool, blocked_by: ?array}>` — một lượt truy vấn cho cả khóa (không N+1): tiến độ từ `content_progress`; bài đánh giá xong khi có `Submission` chưa `invalidated` của học sinh cho bài đó (đi qua `StudentAssessment`/`Attempt`, nộp trễ vẫn tính); bài `draft` có `counts=false`.

- [ ] **Step 1: Viết test đỏ.** Unit: `SequentialUnlockTest` (`test_nothing_is_locked_when_sequential_is_off`, `test_item_is_locked_until_every_previous_counting_item_is_done`, `test_blocked_by_points_to_the_first_unfinished_previous_item`, `test_chapters_and_uncounted_items_are_skipped`, `test_first_item_and_empty_list_are_never_locked`); `CheckpointPositionTest` (`test_max_allowed_is_the_first_unanswered_checkpoint_by_time_not_by_input_order`, `test_clamp_respects_max_allowed_duration_and_zero`, `test_no_checkpoints_means_no_cap`); `ContentCompletionTest` (`test_upload_video_needs_ratio_and_all_checkpoints`, `test_exactly_at_ratio_is_done`, `test_external_or_unknown_duration_never_done_by_position`, `test_external_needs_client_flag_and_all_checkpoints`). Feature: `CourseItemStatusesTest` (`test_statuses_for_lecture_video_and_assessment_items`, `test_draft_assessment_is_not_counted`, `test_invalidated_submission_does_not_complete_an_assessment_item`, `test_late_submission_completes_an_assessment_item`, `test_one_query_set_for_the_whole_course` đếm số truy vấn không tăng theo số mục).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** như mục Interfaces; `Domain/` thuần (R3, không `now()`, `config()`; `ratio` truyền vào).
- [ ] **Step 4: Chạy lại** các test trên và `ArchitectureTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: sequential unlock, completion and checkpoint position rules`.

---

### Task 3: Quyền của học sinh với một nội dung (`LearnerContentAccess`)

**Files:**
- Create: `Enums/ContentAccess`, `Queries/LearnerContentAccess`, `tests/Feature/CourseContent/LearnerContentAccessTest.php`

**Interfaces:**
- Consumes: `CourseLearnersQuery::isLearner`, `CourseItemStatusesQuery`, `ContentAudience`.
- Produces: `LearnerContentAccess::check(User $user, LearningContent $content): array{access: ContentAccess, blocked_by: ?array, student: ?Student}` — `Hidden` khi không có hồ sơ học sinh hợp lệ, nội dung `archived`, ngoài đối tượng; `Locked` khi mục của khóa bị khóa tuần tự; `Ok` còn lại. `::canView(User, LearningContent): bool` (`access` ≠ `Hidden`). `::assertOpen(User, LearningContent): Student` ném `NotFound` (404, `DenialMessage::NOT_FOUND`) khi `Hidden`, `ContentLocked` khi `Locked`, trả `Student` khi `Ok`.

- [ ] **Step 1: Viết test đỏ**: `test_course_learner_via_class_or_direct_audience_is_ok`, `test_student_outside_the_course_is_hidden`, `test_standalone_content_ok_for_direct_student_and_for_member_of_an_audience_class`, `test_class_in_audience_but_student_left_the_class_is_hidden` (`valid_to` quá khứ, `status` không hoạt động), `test_student_of_a_class_not_in_the_audience_is_hidden`, `test_archived_content_is_hidden`, `test_user_without_student_profile_is_hidden`, `test_locked_item_returns_locked_with_the_first_blocking_item`, `test_standalone_content_is_never_locked`, `test_check_uses_a_bounded_number_of_queries`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; dùng lại điều kiện thành viên lớp của `CourseLearnersQuery` (đọc nó trước, không chép lệch). Khóa tuần tự chỉ tính cho nội dung có `course_id`; tìm `CourseItem` của nội dung qua `content_id`.
- [ ] **Step 4: Chạy lại** `LearnerContentAccessTest`, `ArchitectureTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: learner access check for course and standalone contents`.

---

### Task 4: Đọc nội dung (`GET /me/contents`, `GET /me/contents/{content}`)

**Files:**
- Create: `Actions/OpenContentProgress`, `Queries/{ListMyContentsQuery, MyContentDetailQuery}`, `Http/Requests/{ListMyContentsRequest, ViewMyContentRequest}`, controllers, `Resources/{MyContentResource, MyContentSummaryResource}`, `tests/Feature/CourseContent/MyContentReadTest.php`
- Modify: `routes/api.php` (nhóm `role:student`)

**Interfaces:**
- Consumes: `LearnerContentAccess`, `LearningContentDetailQuery` (làm sạch HTML lúc đọc, ảnh, tệp đính kèm, trạng thái video), `ContentProgress`.
- Produces:
  - `OpenContentProgress::handle(Student $student, LearningContent $content): ContentProgress` — tạo lười (`first_opened_at = now()`), chịu được hai yêu cầu song song (`UniqueConstraintViolationException` → đọc lại dòng đã có).
  - `GET /me/contents` → danh sách nội dung độc lập đang `active` mà học sinh thấy: `{id, type, title, status (not_started|in_progress|completed)}`, mới nhất trước, cùng kiểu phân trang với `GET /contents`; lọc `type`.
  - `GET /me/contents/{content}` → `{id, type, course_id, title, body_html, attachments, video, progress: {status, last_position_seconds, completed_at}}`; `video` như `LearningContentResource` (không có đường dẫn đĩa, không có `storage_key`; không kèm link phát). Mở lần đầu tạo dòng tiến độ. `Hidden` → 404, `Locked` → 403 `CONTENT_LOCKED`.

- [ ] **Step 1: Viết test đỏ** (`MyContentReadTest`): `test_student_lists_only_standalone_contents_they_can_see`, `test_list_hides_archived_and_other_students_contents`, `test_detail_of_a_lecture_returns_sanitized_html_and_creates_progress_lazily`, `test_opening_twice_keeps_one_progress_row_and_the_first_opened_time`, `test_detail_of_a_document_lists_attachments_with_download_urls`, `test_detail_of_a_video_has_state_but_no_storage_key_or_path`, `test_content_outside_the_audience_is_404_with_the_not_found_message`, `test_locked_item_is_403_content_locked_with_blocked_by`, `test_teacher_and_parent_get_403_on_student_routes`, `test_user_without_student_profile_is_403`, `test_detail_does_not_run_per_attachment_queries`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; tái dùng `LearningContentDetailQuery`, không viết lại phần làm sạch HTML.
- [ ] **Step 4: Chạy lại** `MyContentReadTest`, `LearningContentTest`, `ArchitectureTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: student reads course and standalone contents`.

---

### Task 5: Ghi tiến độ (`PUT /me/contents/{content}/progress`)

**Files:**
- Create: `Actions/UpdateContentProgress`, `Http/Requests/UpdateMyProgressRequest`, controller, `Resources/MyProgressResource`, `tests/Feature/CourseContent/MyContentProgressTest.php`

**Interfaces:**
- Consumes: `LearnerContentAccess::assertOpen`, `OpenContentProgress`, `CheckpointPosition`, `ContentCompletion`, `CheckpointResponse`.
- Produces: `UpdateContentProgress::handle(Student $student, LearningContent $content, ?int $positionSeconds, ?bool $completed): ContentProgress`; body `{position_seconds?: int ≥ 0, completed?: bool}` (cả hai tùy chọn, ít nhất một, nếu không 422); trả `200 {data: {status, last_position_seconds, max_allowed_position_seconds, completed_at}}`. Khóa dòng `content_progress` (`FOR UPDATE`) khi ghi. Bài giảng/tài liệu: `completed=true` → `Completed` (`completed_at = now()` nếu chưa có), `false` → `InProgress`, `completed_at=null`; `position_seconds` bị bỏ qua. Video: kẹp vị trí rồi lưu; tính xong theo `ContentCompletion` (đọc mọi điểm dừng và `checkpoint_responses` của học sinh trong một lượt); `Completed` không bao giờ bị hạ; `completed=false` bị bỏ qua. Video chưa `ready` → 409 `VIDEO_NOT_READY`.

- [ ] **Step 1: Viết test đỏ** (`MyContentProgressTest`): `test_lecture_is_marked_done_and_unmarked`, `test_position_is_ignored_for_lectures_and_documents`, `test_video_position_is_saved_for_resuming`, `test_position_beyond_an_unanswered_checkpoint_is_clamped_to_it`, `test_position_is_clamped_to_the_duration_and_never_negative`, `test_video_completes_at_the_ratio_only_when_every_checkpoint_is_answered_correctly`, `test_external_video_is_never_completed_by_position_but_by_flag_when_checkpoints_are_correct`, `test_completed_flag_on_a_video_with_open_checkpoints_is_ignored`, `test_completed_video_stays_completed_when_a_checkpoint_is_added_or_the_position_is_rewound`, `test_video_not_ready_is_409`, `test_empty_body_is_422`, `test_negative_position_is_422`, `test_locked_item_is_403_and_outside_audience_is_404_without_writing`, `test_other_students_progress_is_not_touched`, `test_two_parallel_first_writes_leave_one_row` (gọi `OpenContentProgress` hai lần tuần tự sau khi chèn dòng bằng tay để mô phỏng thua cuộc đua).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; phản hồi luôn mang `max_allowed_position_seconds` (`null` khi không có trần) để FE biết chỗ chặn.
- [ ] **Step 4: Chạy lại** `MyContentProgressTest`, `CheckpointPositionTest`, `ContentCompletionTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: student progress with sequential lock and seek clamp`.

---

### Task 6: Học sinh phát video và xem tệp (`playback`, luồng đã ký, tệp đính kèm)

**Files:**
- Create: `tests/Feature/CourseContent/StudentPlaybackTest.php`
- Modify: `ContentVideoAccess` (nhánh học sinh), `PlaybackController`, request của `GET /contents/{content}/playback` (cho học sinh), `routes/api.php` (đưa route playback sang nhóm `role:admin,teacher,student`), `AttachmentPolicy` (File, nhánh `learning_content` cho học sinh), `StreamVideoController` nếu cần

**Interfaces:**
- Consumes: `LearnerContentAccess`, `IssuePlaybackUrl`.
- Produces: `GET /contents/{content}/playback` dùng được cho học sinh: quản lý nội dung (Admin, Giáo viên) đi nhánh cũ; học sinh phải `LearnerContentAccess` = `Ok` (`Hidden` → 404, `Locked` → 403 `CONTENT_LOCKED`), rồi `IssuePlaybackUrl` như cũ (409 `VIDEO_NOT_READY` khi chưa có video sẵn sàng). `ContentVideoAccess::canWatch(User, LearningContent)`: người quản lý như cũ; còn lại học sinh `LearnerContentAccess::check(...)['access'] === Ok`, kiểm lại **mỗi lần mở luồng** (mất lớp, bị khóa, người dùng không hoạt động → 403). `AttachmentPolicy` cho học sinh xem tệp đính kèm (tài liệu, ảnh bài giảng) của nội dung mình thấy và đã mở; nội dung bị khóa → 403 `CONTENT_LOCKED` hoặc 404 theo cách policy hiện dùng (giữ cùng kiểu với nhánh `learnerCanView` sẵn có).

- [ ] **Step 1: Viết test đỏ**: `test_student_gets_a_signed_url_that_streams_the_video`, `test_student_outside_the_audience_gets_404_on_playback`, `test_locked_video_gives_403_content_locked_on_playback`, `test_url_issued_before_the_item_became_locked_is_refused_when_opened` (đổi `sequential_unlock` hoặc xóa tiến độ mục trước rồi mở link cũ → 403), `test_student_who_left_the_class_cannot_open_an_issued_url`, `test_inactive_student_cannot_open_an_issued_url`, `test_external_video_returns_the_link_to_the_student`, `test_student_can_download_document_attachment_of_visible_unlocked_content`, `test_student_cannot_download_attachment_of_locked_or_hidden_content`, `test_parent_still_gets_403_on_playback` , `test_teacher_and_admin_playback_still_work`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; `canWatch` vẫn không phụ thuộc phiên (luồng đã ký không có phiên): học sinh xác định qua `user` trong chữ ký, không qua `ActiveRole`.
- [ ] **Step 4: Chạy lại** `StudentPlaybackTest`, `VideoPlaybackTest`, `AttachmentAccessTest` (tên test thật của File, tìm bằng `grep`), `ArchitectureTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: students watch videos and open attachments through the shared access check`.

---

### Task 7: Điểm dừng cho học sinh (`checkpoints` trong chi tiết, `POST /checkpoints/{checkpoint}/answers`)

**Files:**
- Create: `Actions/AnswerCheckpoint`, `Http/Requests/AnswerCheckpointRequest`, controller, `Resources/{CheckpointAnswerResource, MyCheckpointResource}`, `tests/Feature/CourseContent/CheckpointAnswerTest.php`
- Modify: `MyContentDetailQuery`, `MyContentResource` (thêm `video.checkpoints`), `routes/api.php` (`Route::pattern` cho `checkpoint`), `ArchitectureTest` (nếu cần thêm enum dùng chung)

**Interfaces:**
- Consumes: `LearnerContentAccess::assertOpen`, `CheckpointResponse`, `SubmissionGrading\Domain\AutoGrader::grade(AssessmentQuestion, mixed): ?array`.
- Produces:
  - `video.checkpoints` trong `GET /me/contents/{content}`: `[{id, at_seconds, question_type, question: {prompt, instructions, options[{id, content}], type_payload không có đáp án}, answered_correctly: bool}]` theo `at_seconds` tăng; **không bao giờ có** `is_correct`, `grading_config` đáp án, `type_payload.correct`. Dựng bằng cách dùng lại `AssessmentQuestion::studentSafeSnapshot` (tạo `AssessmentQuestion` chưa lưu từ bản chụp, `points = 1`); nếu `ArchitectureTest` cấm tham chiếu chéo module thì chuyển phần lõi thuần sang `app/Support/Grading/` và để `AutoGrader`/`studentSafeSnapshot` gọi nó (refactor không đổi hành vi, test hiện có giữ nguyên xanh).
  - `AnswerCheckpoint::handle(Student $student, VideoCheckpoint $checkpoint, mixed $answer): array{is_correct: bool, can_continue: bool}`; `POST /checkpoints/{checkpoint}/answers` body `{answer: <định dạng như bài đánh giá cùng loại câu>}` → `200 {data: {is_correct, can_continue}}`; `can_continue = (answered_correctly_at đã có)`. Khóa dòng `checkpoint_responses` (`FOR UPDATE` hoặc tạo-rồi-khóa chịu `UniqueConstraintViolationException`); lượt đầu ghi `first_answer`, `first_is_correct`, `attempts_count = 1`, `answered_correctly_at` nếu đúng; lượt sau `attempts_count += 1` và đặt `answered_correctly_at` một lần khi đúng; đã đúng rồi thì lượt sau không đổi dòng nào. Điểm dừng thuộc nội dung học sinh không mở được → 404/403 `CONTENT_LOCKED` như Task 3.

- [ ] **Step 1: Viết test đỏ** (`CheckpointAnswerTest`): `test_correct_first_answer_records_first_answer_and_unlocks_continue`, `test_wrong_then_correct_keeps_the_first_answer_and_counts_attempts`, `test_correct_answered_checkpoint_is_not_changed_by_later_answers`, `test_each_of_the_six_question_types_is_graded` (data provider: `single_choice`, `multiple_choice`, `true_false`, `fill_in_the_blank`, `matching`, `ordering`; đáp án đúng và sai), `test_empty_or_malformed_answer_is_422_or_wrong_without_crashing`, `test_detail_lists_checkpoints_in_time_order_without_answers`, `test_snapshot_changes_of_the_source_question_do_not_affect_grading`, `test_needs_recheck_checkpoints_are_still_served`, `test_student_outside_the_audience_gets_404_and_cannot_guess_the_checkpoint_id`, `test_locked_item_gives_403_content_locked`, `test_two_sequential_first_answers_never_create_two_rows` (chèn dòng bằng tay trước để mô phỏng thua cuộc đua rồi trả lời), `test_other_students_responses_are_untouched`, `test_answered_correctly_makes_the_position_clamp_move_to_the_next_checkpoint` (nối với Task 5).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; không dùng thân hàm chấm riêng cho điểm dừng.
- [ ] **Step 4: Chạy lại** `CheckpointAnswerTest`, `MyContentReadTest`, `MyContentProgressTest`, test chấm điểm tự động hiện có, `ArchitectureTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: checkpoint answers with first-attempt recording`.

---

### Task 8: Chi tiết khóa cho học sinh và hoàn thành khóa; thay video xóa vị trí xem dở

**Files:**
- Create: `Domain/CourseCompletion`, `Queries/MyCourseViewQuery`, `Http/Requests/ViewMyCourseRequest`, controller, `Resources/MyCourseResource`, `tests/Unit/Modules/CourseContent/CourseCompletionTest.php`, `tests/Feature/CourseContent/{MyCourseViewTest, ReplaceVideoProgressTest}.php`
- Modify: `ReplaceContentVideo` (đặt `last_position_seconds = 0`), `routes/api.php`, `ArchitectureTest` (`SHARED_ENUMS` nếu dùng enum của ResultProgress/Academic)

**Interfaces:**
- Consumes: `CourseItemStatusesQuery`, `CoursePolicy::view` (học viên), kết quả đã công bố (`ResultProgress\Queries\StudentRepresentativeResultsQuery::handle(studentId, ['course_id' => ...])` nếu `ArchitectureTest` cho đọc Query của module khác, ngược lại đọc thẳng model `Result`/`StudentAssessment`), `Course.completion_rule`, `completion_min_score`.
- Produces:
  - `CourseCompletion::isComplete(string $rule, int $countingItems, int $doneItems, ?float $minScore, ?float $averageScore): bool` (thuần; `all_items`: `done == counting` và `counting > 0`; rule hai thêm `averageScore !== null && averageScore >= minScore`; khóa không có mục nào đếm được thì **không** hoàn thành).
  - `GET /me/courses/{course}` (nhóm `role:student`) → `{id, name, sequential_unlock, completion_rule, completion_min_score, items: [{id, position, type, title, content_id, assessment_id, status, locked, blocked_by}], completion: {complete: bool, done: int, total: int, average_score: ?float}}`; không phải học viên → 404 (qua `CoursePolicy`). `average_score` chỉ trung bình các kết quả đã công bố và `grading_status=complete` (kết quả đại diện mỗi bài theo `AttemptScorePolicy` nếu dùng Query có sẵn).
  - `ReplaceContentVideo` đặt `ContentProgress::where('content_id', ...)->update(['last_position_seconds' => 0])` trong cùng transaction, không đổi `status`, `completed_at`.

- [ ] **Step 1: Viết test đỏ.** Unit `CourseCompletionTest`: `test_all_items_done_completes_the_course`, `test_course_with_no_counting_items_is_not_complete`, `test_min_score_rule_needs_average_at_or_above_the_threshold` (đúng bằng ngưỡng thì đạt), `test_missing_average_fails_the_min_score_rule`. Feature `MyCourseViewTest`: `test_items_carry_status_locked_and_blocked_by`, `test_chapters_are_listed_but_never_locked_or_counted`, `test_draft_assessment_items_are_hidden`, `test_completion_counts_assessment_items_by_submission`, `test_unpublished_or_ungraded_results_do_not_count_toward_the_average`, `test_non_learner_gets_404_and_parent_teacher_get_403`, `test_view_runs_a_bounded_number_of_queries`. `ReplaceVideoProgressTest`: `test_replacing_the_video_resets_every_students_resume_position_but_keeps_completed`, `test_setting_an_external_link_over_an_upload_resets_positions_too`, `test_same_link_resend_does_not_reset_positions`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** như mục Interfaces.
- [ ] **Step 4: Chạy lại** ba test trên, `VideoUploadTest`, `ExternalVideoTest`, `ArchitectureTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: student course view, course completion and resume reset on video replace`.

---

### Task 9: Đối chiếu cuối, tài liệu, OpenAPI

**Files:**
- Modify: `docs/architecture/error-codes.md`, `model-ownership.md`, spec thêm mục 14 "Ghi chú hiện thực lát 4", `docs/BACKLOG.md`, `docs/STATE.md`; xuất lại OpenAPI

- [ ] **Step 1: Chạy cả bộ test.** Run: `docker compose exec -T api php artisan test`. Expected: tất cả PASS (`TeacherGradingDemoSeederTest` chập chờn là `B-086`, rerun một lần).
- [ ] **Step 2: Pint chỉ file đã đổi, PHPStan.** Run: `git diff --name-only origin/main...HEAD -- '*.php'` rồi Pint trên danh sách đó; `docker compose exec -T api composer analyse`. Expected: Pint sạch, PHPStan không thêm lỗi so với 30.
- [ ] **Step 3: Xuất OpenAPI** (`composer api-docs`); kiểm có các endpoint: `GET /me/courses/{course}`, `GET /me/contents`, `GET /me/contents/{content}`, `PUT /me/contents/{content}/progress`, `POST /checkpoints/{checkpoint}/answers`; `GET /contents/{content}/playback` ghi nhận được cả học sinh; Resource mới có kiểu rõ (không `mixed`); không schema nào có `storage_key`, `is_correct` của điểm dừng, hay đường dẫn đĩa.
- [ ] **Step 4: Kiểm biên.** `grep -rn "Modules\\\\CourseContent" app/Modules/Assessment` — Expected: chỉ `CreateAssessmentController`; `grep -rn "is_correct" app/Modules/CourseContent/Http app/Modules/CourseContent/Queries/MyContentDetailQuery.php` — Expected: không có dòng nào đưa đáp án đúng vào response học sinh; kiểm bằng tay `MyContentResource`, `MyCheckpointResource`.
- [ ] **Step 5: Cập nhật tài liệu và STATE** như mục Files (ghi quyết định 1..7 vào spec mục 14, nợ vào `BACKLOG.md`: lời giải điểm dừng khi `B-087` xong, `GET /me/courses` danh sách khóa nếu FE cần, lát 4b); commit `docs: slice 4 notes` ở repo docs; OpenAPI không commit (file bị gitignore).

---

## Self-review

- **Phủ spec lát 4:** đọc theo quyền (Task 3, 4), tiến độ (Task 5), mở khóa tuần tự (Task 2, 3, mọi endpoint), trả lời điểm dừng và chặn tua (Task 5, 7), hoàn thành khóa (Task 8), phát video cho học sinh (Task 6), xóa vị trí xem dở khi thay video (Task 8, nợ lát 3). **Chưa phủ (lát 4b):** tiến độ cho Giáo viên/Admin/Phụ huynh (`GET /courses/{id}/progress`, `GET /students/{id}/courses/{courseId}/progress`); ghi rõ ở đầu plan.
- **Kiểu nhất quán:** `LearnerContentAccess::check/assertOpen/canView`, `CourseItemStatusesQuery::handle`, `SequentialUnlock::evaluate`, `CheckpointPosition::maxAllowed/clamp`, `ContentCompletion::isVideoDone`, `CourseCompletion::isComplete`, `ContentLocked` (`CONTENT_LOCKED`), `OpenContentProgress`, `UpdateContentProgress`, `AnswerCheckpoint` dùng đồng nhất ở Task 2–8.
- **Rủi ro cần người thực hiện đọc code trước:** điều kiện thành viên lớp của `CourseLearnersQuery` (Task 3), cách đi từ `Submission` về bài đánh giá của mục (Task 2), khả năng tham chiếu chéo `AutoGrader` từ `CourseContent` (Task 7), có dùng `StudentRepresentativeResultsQuery` được không (Task 8); mỗi chỗ có phương án dự phòng ghi ngay trong task.
- **Tỷ lệ:** chín task, mỗi task có Interfaces, test đặt tên và giá trị spec ghim; không chép thân hàm mà chữ ký và test đã quyết định.
