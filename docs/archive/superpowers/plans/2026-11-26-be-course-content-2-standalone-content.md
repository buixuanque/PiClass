# Khóa học có nội dung học, lát 2: nội dung độc lập (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giáo viên (và Admin) tạo bài giảng, tài liệu **độc lập** (`course_id` null), xem danh sách "Bài giảng của tôi" (TC-18), giao cho Lớp hoặc Học sinh, sao chép vào khóa, lưu trữ hoặc xóa; kèm các minor còn lại của lát 1b (`B-101`, `B-102`).

**Architecture:** Mọi thứ nằm trong module `CourseContent`. Bảng mới `content_audiences` (mẫu `course_audiences`: một dòng là một Lớp hoặc một Học sinh, không ngày hiệu lực, gỡ = xóa dòng + audit). Nội dung độc lập dùng lại `LearningContent` đã có; một scope duy nhất `LearningContent::standalone()` canh `course_id IS NULL`. Học sinh chưa đọc được gì ở lát này (lát 4): lát này chỉ ghi và liệt kê phía giáo viên.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL; chạy lệnh trong `piclass-infra/local` bằng `docker compose exec -T api ...`.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 3 `content_audiences`, mục 4 điều 1, 7, 8, mục 7 API, mục 8 phân quyền/audit; mục 11 ghi chú 1b). Quyết định: `DEC-LRN-052` mục 4, `DEC-LRN-053` (TC-18, ST-12), `ARCH-39`. Quy ước code: `piclass-api/CLAUDE.md`, `docs/architecture/backend-gotchas.md`.

## Global Constraints

- Nhánh `feat/course-content-standalone-content` của `piclass-api`, cắt từ `origin/main` mới nhất. Không commit thẳng `main`.
- Module `CourseContent` chỉ **đọc** Academic (`ClassScope`, `ClassMember`, `SchoolClass`, `Student`), File; ghi vào module khác qua Action của module đó. Gọi `Audit\AuditLogger` như `AddCourseAudience`. `ArchitectureTest` (R1..R11) phải xanh: khai báo `ContentAudience` trong `MODEL_OWNERS` (`CourseContent`), enum dùng chung mới trong `SHARED_ENUMS`, không để literal enum (R8).
- Mọi truy vấn danh sách độc lập đi qua `LearningContent::standalone()`; không viết `whereNull('course_id')` ở chỗ khác.
- Nội dung có `course_id` **không** có dòng `content_audiences` (Action chặn, test canh); nội dung độc lập không có `course_items`.
- Đúng một đích mỗi dòng audience (`class_id` xor `student_id`), unique `(content_id, class_id)` và `(content_id, student_id)`, CHECK ở DB.
- Quyền giao: người tạo hoặc Admin; Teacher chỉ giao cho Lớp mình đang dạy (`ClassScope::classIdsFor($user, 'teacher')`) hoặc Học sinh đang `active` trong các lớp đó. Admin không bị giới hạn.
- Người không quản lý được nội dung độc lập nhận **404** (không lộ tồn tại); khóa/lớp đã lưu trữ chặn thêm đối tượng (`COURSE_ARCHIVED`, `CLASS_ARCHIVED`).
- Audit bắt buộc trong cùng transaction (DEC-AUD-005): `content_audience.add`, `content_audience.remove`, `content.archive`, `content.restore`, `content.delete`, `course.settings_update`, `course_item.remove` (bài giảng/tài liệu). `correlation_key` = id nội dung (hoặc id khóa với hai mục cuối).
- Mọi mã lỗi mới ghi vào `docs/architecture/error-codes.md`; tên test method, mã lỗi, commit message bằng tiếng Anh; comment và mô tả test bằng tiếng Việt.
- TDD: mỗi thay đổi hành vi có test chạy đỏ trước rồi mới xanh; cuối plan chạy cả bộ test, Pint (chỉ file đã đổi), PHPStan (không thêm lỗi mới), xuất OpenAPI.

## Quyết định chốt khi viết plan (2026-11-26)

1. **API giao đối tượng theo mẫu khóa**, không dùng `PUT` thay cả tập như spec §7 bản đầu: `GET|POST /contents/{content}/audience`, `DELETE /contents/{content}/audience/{audienceId}` (cùng cách `course_audience`, audit từng dòng).
2. **Danh sách TC-18 chỉ có nội dung của chính mình** (Admin thấy tất cả): Teacher khác dạy cùng lớp không thấy bài giảng người khác giao. Lý do: màn tên "Bài giảng của tôi", tránh lộ bản nháp.
3. **Lưu trữ và khôi phục** là hai endpoint riêng (`POST .../archive`, `POST .../restore`) để giáo viên ẩn bài mà không mất; xóa cứng (`DELETE`) chỉ cho nội dung độc lập chưa có người học. Bảng tiến độ có ở lát 4, nên lát này mọi nội dung chưa có người học: `DeleteStandaloneContent` có một chỗ kiểm `hasLearners()` luôn false, lát 4 nối vào `content_progress` (ghi ở ghi chú lát 4).
4. **Sao chép vào khóa** theo spec §4.8: bản mới (`course_id` có giá trị), chép tiêu đề, `body_html`, và các liên kết tệp trỏ **cùng `Attachment`** (ảnh bài giảng, tệp tài liệu). Vì thế "ảnh dùng lại ở nơi khác" là cố ý (bỏ minor đó của 1b) và **không** đặt unique `attachment_links.attachment_id`.
5. Chỉ `lecture` và `document` tạo được độc lập ở lát này; `video` ở lát 3 (cùng `ContentVideo`).

## Review Focus

Các điểm kế hoạch ngầm hiểu mà test thường không chạm; người review cuối nhánh kiểm từng dòng (mỗi dòng đã có test ở task chủ):

1. Giáo viên khác (kể cả dạy cùng lớp) GET/PATCH/DELETE/giao nội dung độc lập của người khác phải 404, không rò tồn tại (Task 2, 4, 6).
2. Giao cho lớp mình không dạy hoặc học sinh ngoài lớp mình: 403; id lớp/học sinh không tồn tại: 422 (Task 4).
3. Thêm đối tượng vào nội dung của khóa (có `course_id`): 422 `CONTENT_HAS_NO_AUDIENCE`; nội dung đã lưu trữ: 422 `CONTENT_ARCHIVED`; lớp lưu trữ: `CLASS_ARCHIVED`; trùng: 409 (Task 4).
4. Sao chép tài liệu/bài giảng vào khóa: bản mới độc lập (sửa gốc không đổi bản chép, xóa bản chép không làm mất `Attachment` hay liên kết của gốc); bản chép không kế thừa audience; khóa lưu trữ 422; người không quản lý khóa 403 (Task 5).
5. Xóa nội dung độc lập: xóa kéo theo audience và liên kết tệp, giữ `Attachment`; nội dung của khóa bị `DELETE /contents/{id}` từ chối 422 (Task 6).
6. Hai yêu cầu song song: thêm cùng một đối tượng hai lần chỉ một dòng (409 cho lượt sau, nhờ unique) (Task 4); thêm mục và xóa/sắp xếp mục song song không làm hở `position` (Task 8).

## File Structure

Tạo:
- `database/migrations/2026_11_27_000001_create_content_audiences.php`
- `app/Models/ContentAudience.php`, `database/factories/ContentAudienceFactory.php`
- `app/Modules/CourseContent/Actions/`: `CreateStandaloneContent`, `AddContentAudience`, `RemoveContentAudience`, `CopyContentToCourse`, `ArchiveLearningContent`, `RestoreLearningContent`, `DeleteStandaloneContent`
- `app/Modules/CourseContent/Queries/ListStandaloneContentsQuery.php`, `ListContentAudienceQuery.php`
- `app/Modules/CourseContent/Exceptions/`: `ContentHasNoAudience`, `ContentArchived`, `ContentBelongsToCourse`
- `app/Modules/CourseContent/Http/Requests/`: `CreateStandaloneContentRequest`, `ListStandaloneContentsRequest`, `ManageContentAudienceRequest`, `AddContentAudienceRequest`, `CopyContentToCourseRequest`
- `app/Modules/CourseContent/Http/Controllers/`: `CreateStandaloneContentController`, `ListStandaloneContentsController`, `ListContentAudienceController`, `AddContentAudienceController`, `RemoveContentAudienceController`, `CopyContentToCourseController`, `ArchiveLearningContentController`, `RestoreLearningContentController`, `DeleteStandaloneContentController`
- `app/Modules/CourseContent/Http/Resources/`: `StandaloneContentResource`, `ContentAudienceResource`
- Tests: `tests/Feature/CourseContent/{ContentAudienceSchemaTest, StandaloneContentTest, ListStandaloneContentsTest, ContentAudienceTest, CopyContentToCourseTest, ArchiveDeleteContentTest, CourseContentAuditTest, CourseItemLocksTest}`

Sửa: `app/Models/LearningContent.php` (scope, quan hệ `audiences`), `LearningContentPolicy` (`assign`, `assignTarget`), `ManageLearningContentRequest` (404 cho độc lập), `CreateCourseItemRequest` (rút kiểm tra tài liệu dùng chung), `routes/api.php`, `tests/Architecture/ArchitectureTest.php`, `Academic/Actions/UpdateCourse`, `CourseContent/Actions/{RemoveCourseItem, ReorderCourseItems, UpdateCourseItem, AddCourseDocument}`, `ManageCourseItemRequest`; tài liệu: `error-codes.md`, `model-ownership.md`, `module-boundaries.md`, spec mục 12, `BACKLOG.md`, `STATE.md`.

---

### Task 1: Bảng `content_audiences`, model, scope `standalone()`

**Files:**
- Create: migration, `ContentAudience`, `ContentAudienceFactory`, `tests/Feature/CourseContent/ContentAudienceSchemaTest.php`
- Modify: `LearningContent` (scope + `audiences(): HasMany`), `ArchitectureTest` (`MODEL_OWNERS['ContentAudience'] = 'CourseContent'`), `docs/architecture/model-ownership.md`

**Interfaces:**
- Produces: `ContentAudience` (`id` uuid, `content_id`, `class_id` null, `student_id` null, `added_by_user_id`, `created_at`; `$timestamps = false`; quan hệ `content()`, `schoolClass()`, `student()`); `LearningContent::scopeStandalone($query)`; `LearningContent::audiences()`; factory `ContentAudienceFactory` (state `forClass()`, `forStudent()`).

- [ ] **Step 1: Viết test schema** (`ContentAudienceSchemaTest`): `test_a_row_must_have_exactly_one_target` (không đích và hai đích đều vi phạm CHECK `content_audiences_one_target`), `test_the_same_class_or_student_cannot_be_added_twice_to_one_content`, `test_deleting_a_content_cascades_its_audiences`, `test_standalone_scope_returns_only_contents_without_a_course`.
- [ ] **Step 2: Chạy, thấy đỏ** — `docker compose exec -T api php artisan test --filter=ContentAudienceSchemaTest`. Expected: FAIL (bảng chưa có).
- [ ] **Step 3: Viết migration** `content_audiences` đúng spec §3: khóa `uuid`, FK `content_id` → `learning_contents` cascade, `class_id` FK `classes` restrict null, `student_id` FK `students` restrict null, `added_by_user_id` FK `users`, `created_at`; CHECK đúng một đích; unique `(content_id, class_id)` và `(content_id, student_id)` (Postgres coi null khác nhau nên hai unique độc lập đúng); chỉ mục `(class_id)`, `(student_id)`. Viết model, factory, scope, quan hệ; khai báo `MODEL_OWNERS`, thêm dòng vào `model-ownership.md`.
- [ ] **Step 4: Chạy lại** test schema và `tests/Architecture`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: content audiences table and standalone scope`.

---

### Task 2: Tạo nội dung độc lập (`POST /contents`) và quyền xem/sửa

**Files:**
- Create: `CreateStandaloneContent`, `CreateStandaloneContentRequest`, `CreateStandaloneContentController`, `StandaloneContentResource`, `tests/Feature/CourseContent/StandaloneContentTest.php`
- Modify: `ManageLearningContentRequest`, `CreateCourseItemRequest` (rút phần kiểm tài liệu thành một trait/lớp dùng chung `DocumentAttachmentRules`), `routes/api.php` (`POST /contents` trong nhóm `role:admin,teacher`)

**Interfaces:**
- Consumes: `LectureHtmlSanitizer`, `SyncLectureImages::handle(LearningContent, User, string)`, `SyncContentAttachments::handle(LearningContent, array $ids, AttachmentLinkVisibility)`, `LearningContentPolicy::manage`.
- Produces: `CreateStandaloneContent::handle(User $actor, LearningContentType $type, string $title, ?string $bodyHtml, ?string $attachmentId): LearningContent` (lecture cần `bodyHtml` tùy chọn, document cần `attachmentId`; throw `LectureTooLarge`, `LectureImageNotAllowed`); body `POST /contents`: `type` ∈ `lecture|document`, `title` (1..255), `body_html` (lecture), `attachment_id` (document, kiểm như `CreateCourseItemRequest`). Trả `201` `StandaloneContentResource` (`id,type,title,status,owner_user_id,audience_count,created_at,updated_at`).

- [ ] **Step 1: Viết test đỏ** (`StandaloneContentTest`): `test_teacher_creates_a_standalone_lecture_with_sanitized_html`, `test_teacher_creates_a_standalone_document_from_own_attachment`, `test_video_type_is_rejected_in_this_slice`, `test_student_and_parent_cannot_create_content`, `test_document_attachment_of_someone_else_or_already_linked_is_rejected`, `test_owner_gets_and_patches_a_standalone_content`, `test_another_teacher_gets_404_on_get_and_patch_of_someone_elses_standalone_content`, `test_admin_can_manage_any_standalone_content`, `test_standalone_content_has_no_course_item`. Mỗi test assert `course_id` null và `owner_user_id` đúng.
- [ ] **Step 2: Chạy, thấy đỏ.** Expected: FAIL (route 404/405).
- [ ] **Step 3: Cài đặt** Action (một transaction: tạo `LearningContent` `course_id=null`, đồng bộ ảnh hoặc gắn tệp; nội dung rỗng thành `<p></p>`), Request (dùng chung phần kiểm tài liệu với `CreateCourseItemRequest`, không sao chép mã), Controller, Resource, route. `ManageLearningContentRequest`: với nội dung độc lập mà `manage` bị từ chối, trả **404** (ném `ModelNotFoundException` hoặc `abort(404)`), không phải 403.
- [ ] **Step 4: Chạy lại** `StandaloneContentTest`, `LearningContentTest`, `CourseDocumentTest`. Expected: PASS (không hỏng hành vi 1b).
- [ ] **Step 5: Commit** `feat: create standalone lectures and documents`.

---

### Task 3: Danh sách "Bài giảng của tôi" (`GET /contents`, TC-18)

**Files:**
- Create: `ListStandaloneContentsQuery`, `ListStandaloneContentsRequest`, `ListStandaloneContentsController`, `tests/Feature/CourseContent/ListStandaloneContentsTest.php`
- Modify: `routes/api.php` (`GET /contents`, chú ý khai báo trước `/contents/{content}`), `StandaloneContentResource` (nếu cần thêm trường)

**Interfaces:**
- Consumes: `LearningContent::standalone()`, `UnaccentSearch::apply($query, 'title', $text)`, `ActiveRole`.
- Produces: `ListStandaloneContentsQuery::handle(User $user, array $filters, int $perPage): LengthAwarePaginator` với bộ lọc `type` (`lecture|document`), `status` (`active|archived`, mặc định `active`), `search`, `class_id` (có audience trỏ vào lớp đó); phân trang `per_page` (mặc định 15, tối đa 100) như các danh sách khác trong repo; sắp `created_at` giảm dần rồi `id`. Admin thấy tất cả, người khác chỉ thấy `owner_user_id = user->id`. Resource thêm `audience_count`.

- [ ] **Step 1: Viết test đỏ**: `test_list_returns_only_my_standalone_contents`, `test_course_contents_never_appear_in_the_list`, `test_admin_sees_everyones_contents`, `test_filters_by_type_status_search_and_class`, `test_search_ignores_diacritics_and_case`, `test_archived_contents_are_hidden_by_default`, `test_per_page_is_capped_and_invalid_filter_values_are_rejected`, `test_student_and_parent_get_403`. Fixture có dữ liệu thật cho "người khác": nội dung của giáo viên khác dạy **cùng lớp** và nội dung của khóa của chính mình (phải vắng).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** Query (tránh N+1: `withCount('audiences')`), Request (validate bộ lọc), Controller, route.
- [ ] **Step 4: Chạy lại** `ListStandaloneContentsTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: list my standalone contents`.

---

### Task 4: Giao cho Lớp hoặc Học sinh (audience) có audit

**Files:**
- Create: `AddContentAudience`, `RemoveContentAudience`, `ListContentAudienceQuery`, exceptions `ContentHasNoAudience` (422 `CONTENT_HAS_NO_AUDIENCE`), `ContentArchived` (422 `CONTENT_ARCHIVED`), `ManageContentAudienceRequest`, `AddContentAudienceRequest`, `ListContentAudienceController`, `AddContentAudienceController`, `RemoveContentAudienceController`, `ContentAudienceResource`, `tests/Feature/CourseContent/ContentAudienceTest.php`
- Modify: `LearningContentPolicy` (`assign`, `assignTarget`), `routes/api.php`, `docs/architecture/error-codes.md`

**Interfaces:**
- Consumes: `AuditLogger::record(actor:, action:, entityType:, entityId:, before:, after:, correlationKey:)`, `Academic\Exceptions\{ClassArchived, AudienceAlreadyAdded}`, `ClassScope::classIdsFor`.
- Produces: `AddContentAudience::handle(LearningContent $content, array $target, User $actor): ContentAudience` (target `class_id` xor `student_id`; bắt `UniqueConstraintViolationException` thành `AudienceAlreadyAdded`); `RemoveContentAudience::handle(LearningContent $content, ContentAudience $audience, User $actor): void` (audience không thuộc nội dung → 404); `LearningContentPolicy::assign(User, LearningContent): Response` (người tạo hoặc Admin, nội dung phải độc lập) và `assignTarget(User, LearningContent, array $target): Response` (mẫu `AssessmentPolicy::assignTarget`). Endpoint: `GET|POST /contents/{content}/audience`, `DELETE /contents/{content}/audience/{audienceId}`. Audit `content_audience.add|remove`, `entityType` `ContentAudience`, `correlationKey` = id nội dung.

- [ ] **Step 1: Viết test đỏ**: `test_owner_assigns_a_class_and_a_student_and_lists_them`; `test_duplicate_target_is_409_and_leaves_one_row`; `test_exactly_one_target_is_required`; `test_unknown_class_or_student_id_is_422`; `test_teacher_cannot_assign_a_class_they_do_not_teach_or_a_student_outside_their_classes` (403); `test_admin_may_assign_any_target`; `test_another_teacher_gets_404`; `test_course_content_has_no_audience` (422 `CONTENT_HAS_NO_AUDIENCE`); `test_archived_content_and_archived_class_reject_new_targets`; `test_remove_deletes_the_row_and_writes_audit`; `test_audience_of_another_content_cannot_be_removed_through_this_one` (404); `test_add_writes_audit_in_the_same_transaction` (audit lỗi thì không còn dòng audience: ép `AuditLogger` ném lỗi); `test_student_and_parent_get_403`. Thứ tự kiểm: quyền (404/403) → định dạng 422 → "đúng một đích" → phạm vi đích 403 → trạng thái (`COURSE`-kiểu 422).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** theo `AddCourseAudience`/`RemoveCourseAudience` (cùng cấu trúc, đổi bảng); `ListContentAudienceQuery` nạp `schoolClass`, `student.personProfile` cho resource; ghi mã lỗi mới vào `error-codes.md`.
- [ ] **Step 4: Chạy lại** `ContentAudienceTest` và `tests/Architecture`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: assign standalone contents to classes and students`.

---

### Task 5: Sao chép nội dung độc lập vào khóa (`POST /contents/{content}/copy-to-course`)

**Files:**
- Create: `CopyContentToCourse`, `CopyContentToCourseRequest`, `CopyContentToCourseController`, `tests/Feature/CourseContent/CopyContentToCourseTest.php`
- Modify: `routes/api.php`, `docs/architecture/error-codes.md` (nếu thêm mã)

**Interfaces:**
- Consumes: `AppendCourseItem::handle(Course, CourseItemType, string $title, array $target): CourseItem`, `LearningContent::attachmentLinks()`, `CourseItemResource`, `CoursePolicy::manage`.
- Produces: `CopyContentToCourse::handle(LearningContent $source, Course $course, User $actor): CourseItem` (nguồn phải độc lập, nếu không 422 `CONTENT_BELONGS_TO_COURSE`; khóa lưu trữ → `CourseArchived`); body `{course_id}` (uuid tồn tại). Trả `201` `CourseItemResource` đã nạp `CourseItem::DISPLAY_RELATIONS`. Bản chép: `type` giữ nguyên, `course_id` = khóa, `owner_user_id` = người gọi, `title`/`body_html` chép, `status` active; mỗi `AttachmentLink` của nguồn được tạo bản mới (cùng `attachment_id`, cùng `visibility`, `learning_content_id` mới); không chép audience.

- [ ] **Step 1: Viết test đỏ**: `test_lecture_is_copied_with_title_body_and_image_links`; `test_document_is_copied_and_shares_the_attachment`; `test_copy_is_independent_editing_the_source_does_not_change_the_copy`; `test_deleting_the_copy_keeps_the_source_links_and_attachment`; `test_copy_does_not_inherit_the_audience`; `test_copy_appends_to_the_end_of_the_course`; `test_archived_course_is_rejected`; `test_source_must_be_standalone` (422); `test_teacher_cannot_copy_another_teachers_content` (404) `test_teacher_must_manage_the_course` (403); `test_missing_ids_are_validation_failed`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** một transaction: tạo nội dung bản chép, tạo liên kết (đi qua Action của module File khi có, hoặc tạo trực tiếp `AttachmentLink` nếu `SyncContentAttachments` buộc kiểm "chưa gắn nơi khác": viết một phương thức chép riêng trong module File, không nới luật cho `SyncContentAttachments`), rồi `AppendCourseItem`. Quyền: FormRequest `Gate::inspect('manage', $course)` rồi `manage` nguồn (404 khi không phải chủ).
- [ ] **Step 4: Chạy lại** `CopyContentToCourseTest`, `RemoveCourseItemTest`, `ContentAttachmentAccessTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: copy standalone contents into a course`.

---

### Task 6: Lưu trữ, khôi phục, xóa nội dung độc lập

**Files:**
- Create: `ArchiveLearningContent`, `RestoreLearningContent`, `DeleteStandaloneContent`, `ContentBelongsToCourse` (422 `CONTENT_BELONGS_TO_COURSE`), ba controller, `tests/Feature/CourseContent/ArchiveDeleteContentTest.php`
- Modify: `routes/api.php`, `error-codes.md`

**Interfaces:**
- Produces: `POST /contents/{content}/archive` và `/restore` (trả `StandaloneContentResource`; lưu trữ lần hai hoặc khôi phục khi đang active là idempotent, 200, không audit trùng); `DELETE /contents/{content}` → 204. `DeleteStandaloneContent::handle(LearningContent, User): void` xóa nội dung, audience (cascade), liên kết tệp của nó (không xóa `Attachment`); có hàm riêng `hasLearners(LearningContent): bool` trả `false` (lát 4 nối `content_progress`) và nếu `true` thì lưu trữ thay vì xóa. Cả ba chỉ cho nội dung độc lập (nội dung của khóa: 422 `CONTENT_BELONGS_TO_COURSE`, gỡ qua `DELETE /courses/{course}/items/{id}`). Audit `content.archive|restore|delete` (`before` có `title`, `type`, `owner_user_id`).

- [ ] **Step 1: Viết test đỏ**: `test_archive_hides_the_content_from_the_default_list_and_restore_brings_it_back`; `test_archive_is_idempotent_and_writes_one_audit_row`; `test_delete_removes_content_audiences_and_links_but_keeps_the_attachment`; `test_delete_of_a_course_content_is_422_and_changes_nothing`; `test_another_teacher_gets_404_on_all_three`; `test_admin_can_archive_and_delete_anyones`; `test_adding_audience_to_an_archived_content_is_rejected` (đối chiếu Task 4); `test_audit_failure_rolls_back_the_delete`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** ba Action và controller, dùng `LearningContentPolicy::manage`; `archived_at = now()` khi lưu trữ, `null` khi khôi phục.
- [ ] **Step 4: Chạy lại** `ArchiveDeleteContentTest`, `ListStandaloneContentsTest`, `ContentAudienceTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: archive restore and delete standalone contents`.

---

### Task 7: Audit cho cài đặt khóa và xóa bài giảng/tài liệu trong khóa (`B-102`)

**Files:**
- Create: `tests/Feature/CourseContent/CourseContentAuditTest.php`
- Modify: `Academic/Actions/UpdateCourse`, `CourseContent/Actions/RemoveCourseItem`, `docs/requirements/decision-log.md` (nếu `DEC-AUD` liệt kê hành động: thêm `course.settings_update`, `course_item.remove`; không đổi quyết định cũ)

**Interfaces:**
- Consumes: `AuditLogger::record(...)`.
- Produces: `UpdateCourse` ghi `course.settings_update` **chỉ khi** một trong `sequential_unlock`, `completion_rule`, `completion_min_score` đổi giá trị (`before`/`after` có đủ ba trường); `RemoveCourseItem` ghi `course_item.remove` khi mục là `lecture` hoặc `document` (`before`: `item_id`, `type`, `title`, `content_id`); cả hai `correlationKey` = id khóa, cùng transaction.

- [ ] **Step 1: Viết test đỏ**: `test_changing_a_setting_writes_one_audit_row_with_before_and_after`; `test_changing_only_the_name_writes_no_settings_audit`; `test_clearing_the_min_score_through_all_items_is_recorded`; `test_removing_a_lecture_or_document_writes_audit_but_removing_a_chapter_does_not`; `test_audit_failure_rolls_back_the_removal`; `test_owner_teacher_can_read_these_rows_in_audit_logs` (`correlation_key` = id khóa).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** hai chỗ ghi audit (không đổi hành vi khác; chạy lại `CourseLearningSettingsTest`, `AcademicCourseBehaviorTest`, `RemoveCourseItemTest`).
- [ ] **Step 4: Chạy lại** các test trên. Expected: PASS.
- [ ] **Step 5: Commit** `feat: audit course settings changes and content item removal`.

---

### Task 8: Minor còn lại của 1b (`B-101`)

**Files:**
- Create: `tests/Feature/CourseContent/CourseItemLocksTest.php`, migration `2026_11_27_000002_index_attachment_links_learning_content.php`
- Modify: `RemoveCourseItem`, `ReorderCourseItems`, `UpdateCourseItem`, `AddCourseDocument`, `ManageCourseItemRequest`

**Interfaces:**
- Produces: ba Action trên khóa hàng khóa học (`Course::whereKey($id)->lock('for no key update')->first()`) **trước** khi khóa các mục, cùng thứ tự với `AppendCourseItem`, nên vị trí luôn liền 1..n; `AddCourseDocument` khóa hàng `attachments` rồi kiểm lại "chưa gắn nơi khác" trong transaction (cạnh tranh hai tài liệu cùng một tệp: lượt sau nhận 422 `attachment_id`); chỉ mục `attachment_links(learning_content_id)`; `ManageCourseItemRequest.title` thêm `max:255`. **Không** đặt unique `attachment_id` (bản sao ở Task 5 dùng chung `Attachment`); bỏ qua minor "ảnh đã gắn nơi khác" (quyết định 4, ghi vào ghi chú lát).

- [ ] **Step 1: Viết test đỏ**: `test_remove_reorder_and_update_lock_the_course_row_first` (cùng kỹ thuật `DB::listen` kiểm thứ tự câu truy vấn như `AppendCourseItemTest`, mỗi Action một ca); `test_removing_a_middle_item_keeps_positions_contiguous`; `test_patching_an_item_title_over_255_chars_is_422_not_500`; `test_second_document_with_the_same_attachment_is_rejected_inside_the_action` (gọi thẳng `AddCourseDocument` hai lần với cùng tệp); `test_learning_content_id_is_indexed` (đọc `pg_indexes`).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** như mục Interfaces.
- [ ] **Step 4: Chạy lại** `CourseItemLocksTest`, `RemoveCourseItemTest`, `ReorderCourseItemsTest`, `CourseDocumentTest`, `AppendCourseItemTest`. Expected: PASS.
- [ ] **Step 5: Commit** `fix: lock the course row on item changes, cap item title, index content links`.

---

### Task 9: Đối chiếu cuối, tài liệu, OpenAPI

**Files:**
- Modify: `docs/architecture/error-codes.md`, `docs/architecture/model-ownership.md`, `docs/architecture/module-boundaries.md` (nếu `CourseContent` gọi `Audit` chưa có trong cột phụ thuộc), spec thêm mục 12 "Ghi chú hiện thực lát 2", `docs/BACKLOG.md` (xóa `B-101`, `B-102` đã làm; thêm mục nếu còn), `docs/STATE.md`; `piclass-api/storage/api-docs/openapi.json` (xuất lại)

- [ ] **Step 1: Chạy cả bộ test.** Run: `docker compose exec -T api php artisan test`. Expected: tất cả PASS.
- [ ] **Step 2: Pint chỉ file đã đổi, PHPStan.** Run: `git diff --name-only origin/main...HEAD -- '*.php' | xargs docker compose exec -T api ./vendor/bin/pint` rồi `docker compose exec -T api composer analyse`. Expected: Pint sạch, PHPStan không thêm lỗi so với baseline 30.
- [ ] **Step 3: Xuất OpenAPI** theo `docs/rules/openapi.md`; kiểm có `POST|GET /contents`, `GET|POST /contents/{content}/audience`, `DELETE /contents/{content}/audience/{audienceId}`, `POST /contents/{content}/copy-to-course`, `POST /contents/{content}/archive|restore`, `DELETE /contents/{content}`.
- [ ] **Step 4: Kiểm biên.** Run: `grep -rn "whereNull('course_id')" app/Modules/CourseContent` — Expected: không dòng nào (chỉ qua `standalone()`); `grep -rn "Modules\\\\CourseContent" app/Modules/Assessment` — Expected: chỉ `CreateAssessmentController`.
- [ ] **Step 5: Cập nhật tài liệu và STATE** như mục Files; commit `docs: slice 2 notes` ở repo docs, commit OpenAPI ở `piclass-api`.

---

## Self-review

- **Phủ spec:** `content_audiences` (T1), danh sách TC-18 (T3), giao Lớp/Học sinh + audit add/remove (T4), sao chép vào khóa (T5), lưu trữ/xóa (T6), audit thiếu của 1b (T7), minor 1b (T8). Phần video, tiến độ, đọc phía học sinh, `GET /me/contents`: lát 3 và 4 (không thuộc lát này).
- **Kiểu nhất quán:** `ContentAudience`, `LearningContent::standalone()`, `AddContentAudience`/`RemoveContentAudience`, mã lỗi `CONTENT_HAS_NO_AUDIENCE`, `CONTENT_ARCHIVED`, `CONTENT_BELONGS_TO_COURSE` dùng đồng nhất ở T1..T6 và `error-codes.md`.
- **Tỷ lệ:** chín task, mỗi task có test đặt tên và Interfaces; không chép thân hàm mà chữ ký và test đã quyết định.
