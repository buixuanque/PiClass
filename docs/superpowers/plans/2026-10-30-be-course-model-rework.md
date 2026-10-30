# Làm lại mô hình Khóa học (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay ghi danh và liên kết Lớp–Khóa bằng `CourseAudience`, thêm `CourseItem`, `Question.visibility`, `Assessment.course_id` trong `piclass-api`.

**Architecture:** Academic sở hữu `Course`/`CourseAudience`; Assessment sở hữu `CourseItem` và bài của khóa (cùng bảng `assessments`, phân biệt bằng `course_id`, dùng chung engine làm bài). Với bài của khóa, `StudentAssessment` chỉ được tạo lười khi học sinh (là học viên của khóa) bắt đầu làm; bài cá nhân giao theo lớp giữ cơ chế ghi sẵn cũ.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, PHPUnit 12, Scramble (OpenAPI). Quy ước: `piclass-api/CLAUDE.md`.

**Spec:** `docs/requirements/decision-log.md` `DEC-LRN-026`; `docs/architecture/decisions/ARCH-38.md`; `docs/database/schema.md` (CourseAudience, CourseItem, Question, Assessment); `BR-COURSE-002..007`, `BR-ASSESS-003/008/029`.

## Global Constraints

- Nhánh `feat/course-model-rework` từ `origin/main` của `piclass-api`. Không push `main`, không force-push.
- Đang phát triển: được xóa dữ liệu cũ; **một migration mới** drop `course_enrollments`, `course_class_links` và đổi các cột (không sửa migration cũ). Test dùng `RefreshDatabase`.
- Comment code, mô tả test: tiếng Việt. Tên hàm/class/test method/`error.code`/commit: tiếng Anh. Test mới: feature test hành vi + case lỗi (assert `code` và status; 403/404 assert cả `message`).
- Mọi Action ghi: một method `handle()`; audit bắt buộc cùng transaction (`DEC-AUD-005`). Audit mới: `course_audience.add`, `course_audience.remove` (`entityType` `CourseAudience`, `correlationKey` = `course_id`).
- Sửa endpoint thì `docker compose exec api php artisan scramble:export` (trong `piclass-infra/local`) trước khi commit; mã lỗi mới ghi vào `docs/architecture/error-codes.md`.
- Không chạy test song song với subagent trên cùng DB test. Cuối mỗi task: `php artisan test --filter=<nhóm>`, `vendor/bin/pint`, `composer analyse` (không thêm lỗi, không thêm baseline).
- Review cuối nhánh: subagent **model `opus`** tường minh (CLAUDE.md root).

## Review Focus

- Học sinh vừa trong danh sách trực tiếp vừa trong lớp thuộc khóa: gỡ một đường vẫn làm được bài, gỡ cả hai thì không bắt đầu làm bài mới được nhưng dòng và bài làm đã có giữ nguyên.
- Giáo viên A thêm câu `private` của giáo viên B vào bài bằng `question_version_id` đoán được: bị từ chối.
- Bài của khóa không bao giờ có `AssessmentAudience`; giao audience cho bài có `course_id` bị từ chối (422).
- `GET /assessments` mặc định không trả bài của khóa; Giáo viên không phải chủ khóa (kể cả khóa dùng chung) không tạo/sao chép bài vào khóa.
- Học sinh rời lớp hoặc lớp bị lưu trữ giữa chừng: không còn thấy bài của khóa qua lớp, dữ liệu làm dở không bị xóa.
- Học sinh vào lớp/khóa sau khi bài của khóa đã publish vẫn thấy và làm được bài, không cần hook.

---

### Task 1: Schema và model

**Files:**
- Create: `database/migrations/2026_10_30_000001_rework_course_model.php`, `app/Models/CourseAudience.php`, `app/Models/CourseItem.php`, `database/factories/CourseAudienceFactory.php`, `database/factories/CourseItemFactory.php`, `app/Modules/Academic/Enums/CourseItemType.php` (hoặc Assessment, theo `ARCH-38`: đặt ở `Assessment/Enums`)
- Modify: `app/Models/Course.php`, `Question.php`, `Assessment.php`, `AssessmentAudience.php`, `Student.php`, `SchoolClass.php`; `database/factories/{Question,Assessment}Factory.php`
- Delete: `app/Models/CourseEnrollment.php`, `CourseClassLink.php`, factories và enums `CourseEnrollmentStatus`, `CourseClassLinkStatus`, `tests/Unit/Modules/Academic/Enums/CourseEnrollmentStatusTest.php`, dòng liên quan trong `tests/Feature/Models/EnumCastsTest.php`

**Interfaces:**
- Produces: `Course::audiences(): HasMany<CourseAudience>`, `Course::items(): HasMany<CourseItem>` (sắp theo `position`), `Course::assessments(): HasMany<Assessment>`; `Assessment::course(): BelongsTo`; `Question.visibility` cast `QuestionVisibility` (enum `Shared='shared'`, `Private='private'`, ở `app/Modules/QuestionBank/Enums`); `CourseItemType::Assessment='assessment'`.
- Bảng (khớp `docs/database/schema.md`): `course_audiences(id, course_id, class_id null, student_id null, added_by_user_id, created_at)` unique `(course_id,class_id)`, `(course_id,student_id)`, check đúng một trong hai; `course_items(id, course_id, parent_id null, position, type, title, assessment_id null unique, created_at, updated_at)`; thêm `questions.visibility` default `shared`; `assessments.course_id` nullable FK; `assessment_questions.source_question_version_id` NOT NULL; drop `assessment_audiences.course_id`.

- [ ] **Step 1: Test** `tests/Feature/Models/CourseModelSchemaTest.php`: `test_course_audience_requires_exactly_one_of_class_or_student` (cả hai null và cả hai có giá trị đều lỗi DB), `test_course_audience_is_unique_per_course_and_target`, `test_question_visibility_defaults_to_shared`, `test_assessment_question_requires_source_version`.
- [ ] **Step 2:** chạy, thấy FAIL.
- [ ] **Step 3:** viết migration (drop hai bảng cũ, tạo bảng mới, đổi cột), model, factory, enum; xóa model/factory/enum cũ. Chưa xóa controller (Task 3), nên Task 1 để suite đỏ ở Academic là chấp nhận; chạy riêng nhóm Models.
- [ ] **Step 4:** `php artisan migrate:fresh --env=testing` rồi `php artisan test --filter=CourseModelSchemaTest` PASS.
- [ ] **Step 5:** commit `feat(academic): course audience and item schema`.

### Task 2: Question visibility (QuestionBank)

**Files:** Modify `CreateQuestion.php`, `UpdateQuestion.php`, `ListQuestionsQuery.php`, `QuestionPolicy.php`, `GetQuestionController`/Request, `QuestionResource`, `Create/UpdateQuestionRequest`, `AddAssessmentQuestion.php` (Assessment); Create `app/Modules/QuestionBank/Exceptions/QuestionNotVisible.php`; Test `tests/Feature/QuestionBank/QuestionVisibilityTest.php`.

**Interfaces:**
- Produces: field `visibility` (`shared|private`, mặc định `shared`) ở request tạo/sửa và resource; `ListQuestionsQuery::handle(User, array $filters, int $perPage)` thêm lọc `visibility` và luôn áp phạm vi xem; `QuestionPolicy::view(User, Question): Response` (Admin hoặc người tạo, hoặc `shared`; ngoài ra 404 theo mẫu ẩn tồn tại); mã lỗi `QUESTION_NOT_VISIBLE` (403) khi thêm câu không xem được vào bài.
- Quy tắc: Giáo viên thấy câu `shared` của mọi người và câu của chính mình; Admin thấy tất cả; chỉ người tạo hoặc Admin đổi `visibility`; đổi không ảnh hưởng bài đã dùng.

- [ ] **Step 1: Test** `test_teacher_cannot_see_other_teachers_private_question_in_list`, `test_private_question_returns_404_to_other_teacher`, `test_creator_can_toggle_visibility_without_changing_existing_assessment_snapshot`, `test_admin_sees_all_questions`, `test_adding_other_teachers_private_question_version_to_assessment_is_rejected` (code `QUESTION_NOT_VISIBLE`, 403).
- [ ] **Step 2:** FAIL. **Step 3:** implement theo Interfaces; `AddAssessmentQuestion` kiểm `QuestionPolicy::view` qua Gate trước khi chụp.
- [ ] **Step 4:** `--filter=QuestionVisibilityTest` và `--filter=QuestionBank` PASS. **Step 5:** commit `feat(question-bank): question visibility`.

### Task 3: CourseAudience (Academic), bỏ ghi danh và liên kết lớp

**Files:**
- Create: `Actions/AddCourseAudience.php`, `Actions/RemoveCourseAudience.php`, `Queries/CourseLearnersQuery.php`, controllers `AddCourseAudienceController`, `RemoveCourseAudienceController`, `ListCourseAudienceController`, `ListCourseLearnersController`, các Request/Resource tương ứng, `Exceptions/AudienceAlreadyAdded.php`
- Modify: `CoursePolicy.php` (xem qua đối tượng; `manage`; bỏ `viewEnrollments`), `ListCoursesQuery.php`, `routes/api.php`, `ClassScope.php::teacherCoversStudent`, `StudentPolicy.php`, `UpdateClass.php`, `AddClassMember.php`, `EndClassMember.php`
- Delete: mọi Action/Controller/Request/Resource/Exception/Query/Concern liên quan ghi danh và class-link (danh sách ở `grep -rli "enrollment\|CourseClassLink" app`), test Academic tương ứng (`CourseEnrollment*`, `CourseClassLinkTest`, `LinkClassToCourseTest`, `UnlinkClassFromCourseTest`) và phần ghi danh trong `Academic*Test`, `AcademicFixtures`

**Interfaces:**
- Produces: `AddCourseAudience::handle(Course $course, array $target /* class_id|student_id */, User $actor): CourseAudience`; `RemoveCourseAudience::handle(Course $course, CourseAudience $audience, User $actor): void`; `CourseLearnersQuery::handle(Course $course): Collection<{student_id, sources: list<{type: 'direct'|'class', class_id?}>}>`; `CourseLearnersQuery::isLearner(string $courseId, string $studentId): bool`.
- Routes (Admin + Teacher chủ khóa ghi; xem theo `CoursePolicy::view`): `POST /courses/{course}/audience`, `GET /courses/{course}/audience`, `DELETE /courses/{course}/audience/{audience}`, `GET /courses/{course}/learners` (Admin/Teacher quản lý; Student/Parent 403).
- Quy tắc: khóa `archived` → `COURSE_ARCHIVED` (422); lớp `archived` → `CLASS_ARCHIVED`; trùng → `AUDIENCE_ALREADY_ADDED` (409, thêm vào `error-codes.md`); Teacher thêm Student/Lớp bất kỳ vào khóa của mình (`BR-COURSE-004`), khóa dùng chung chỉ Admin. Student/Parent xem khóa nếu học sinh nằm trong danh sách trực tiếp hoặc là `ClassMember` đang hoạt động của lớp trong danh sách; ngoài ra 404. Không có hook đồng bộ phiếu bài (xem Task 5).

- [ ] **Step 1: Test** `tests/Feature/Academic/CourseAudienceTest.php`: add class, add student, duplicate 409, archived course 422, teacher non-owner 403 (message), shared course teacher 403, audit `course_audience.add/remove` có trong cùng giao dịch (audit lỗi thì không có dòng audience), `test_student_sees_course_via_class_membership_and_loses_it_when_member_ended`, `test_learners_merges_direct_and_class_sources_once_per_student`, `test_course_list_for_student_only_contains_audience_courses`.
- [ ] **Step 2:** FAIL. **Step 3:** implement; xóa code/test ghi danh; `AddClassMember`/`EndClassMember`/`UpdateClass` bỏ gọi `SyncCourseEnrollments*`; `ClassScope::teacherCoversStudent` dùng `CourseLearnersQuery::isLearner`.
- [ ] **Step 4:** `php artisan test --filter=Academic` và `--filter=ClassScope` PASS; `ArchitectureTest` PASS. **Step 5:** commit `feat(academic): course audience replaces enrollment and class link`.

### Task 4: Bài của khóa (Assessment.course_id) và CourseItem

**Files:**
- Create: `Actions/CopyAssessmentToCourse.php`, `Actions/UpdateCourseItem.php`, `Actions/RemoveCourseItem.php`, controllers + requests + `CourseItemResource`, `Exceptions/CourseAssessmentRules.php`; Test `tests/Feature/Assessment/CourseAssessmentTest.php`
- Modify: `CreateAssessment.php` (nhận `course_id`), `CreateAssessmentRequest`, `AssessmentPolicy.php`, `ClassScope.php` (`teacherCoversAssessment`, `teacherCoversStudentAssessment`), `ListAssessmentsQuery.php`, `AddAssessmentAudience.php` + Request (bỏ `course_id`, từ chối bài có `course_id`), `ReopenAssessment.php` + Request (bỏ `course_id`), `AssessmentResource` (thêm `course_id`), `routes/api.php`

**Interfaces:**
- Produces: `CreateAssessment::handle(User $actor, array $data)` — khi có `course_id`: kiểm quyền quản lý khóa, tạo `Assessment.course_id` và `CourseItem(type=assessment, title=data.title, position=max+1)` cùng giao dịch, khóa `archived` → `COURSE_ARCHIVED`; `CopyAssessmentToCourse::handle(Assessment $source, Course $course, User $actor): Assessment` — sao chép cấu hình và các `AssessmentQuestion` của version hiện tại (giữ `source_question_version_id`, bản nháp mới, tạo mục cuối khóa); người sao chép phải xem được bài nguồn và quản lý khóa; `UpdateCourseItem::handle(CourseItem, array{title?, position?}, User)` (đổi thứ tự bù các mục khác, trong giao dịch); `RemoveCourseItem::handle(CourseItem, User)` — bài đã có `Attempt` thì chuyển bài sang `archived` rồi xóa mục, chưa có thì xóa mục và bài.
- Routes: `GET /courses/{course}/items` (Admin/Teacher xem khóa; Student/Parent chỉ thấy mục có bài `published`/`closed` và chỉ khi xem được khóa), `POST /courses/{course}/items/copy` body `{assessment_id}`, `PATCH /courses/{course}/items/{item}`, `DELETE /courses/{course}/items/{item}`. Tạo bài mới trong khóa: `POST /assessments` với `course_id`.
- `ListAssessmentsQuery`: luôn áp `whereNull('course_id')`; bỏ lọc `course_id`; một scope `Assessment::scopePersonal()` dùng ở mọi danh sách bài cá nhân (rule `BR-ASSESS-029`).
- Quy tắc lỗi: giao audience cho bài có `course_id` → `COURSE_ASSESSMENT_NO_AUDIENCE` (422, ghi `error-codes.md`).

- [ ] **Step 1: Test** `test_create_assessment_with_course_id_creates_item_at_end`, `test_teacher_cannot_create_assessment_in_course_they_do_not_own` (403), `test_shared_course_only_admin_can_add_assessments`, `test_personal_list_excludes_course_assessments`, `test_audience_on_course_assessment_is_rejected` (code), `test_copy_creates_independent_draft_with_same_questions_and_editing_source_does_not_change_copy`, `test_remove_item_with_attempts_archives_assessment`, `test_item_reorder_keeps_positions_contiguous`, `test_student_items_hide_draft_assessments`.
- [ ] **Step 2:** FAIL. **Step 3:** implement. **Step 4:** `--filter=Assessment` PASS (sửa các test cũ dùng `course_id` ở audience/reopen). **Step 5:** commit `feat(assessment): course-owned assessments and course items`.

### Task 5: Học sinh nhận bài của khóa (tạo `StudentAssessment` khi bắt đầu làm)

**Files:** Modify `app/Modules/SubmissionGrading/Actions/StartAttempt.php`, `app/Modules/SubmissionGrading/Policies/AttemptPolicy.php`, `app/Modules/Assessment/Queries/StudentAssessmentMetadataQuery.php`, `app/Modules/Assessment/Http/Controllers/ListAssessmentStudentsController.php` (và resource), `app/Modules/Assessment/Actions/StudentAssessmentSync.php`; Test `tests/Feature/Assessment/CourseAssessmentAccessTest.php`.

**Interfaces:**
- Consumes: `CourseLearnersQuery::isLearner(string $courseId, string $studentId): bool` (Task 3).
- Produces: `StudentAssessmentSync::ensureForCourseLearner(Assessment $assessment, string $studentId): StudentAssessment` — chỉ khi `assessment.course_id` có giá trị, bài đã `published` hoặc `closed`, và học sinh là học viên của khóa; tạo dòng với `source_course_id = assessment.course_id`, `source_audience_id = null`, `assigned_version_id = current_version_id` (không tạo trùng; dòng `withdrawn` không được kích hoạt lại ở đây); ngoài điều kiện thì ném lỗi như "chưa được giao" hiện có. `StudentAssessmentMetadataQuery` trả thêm bài của các khóa học sinh thuộc (và của các con với Parent) kể cả chưa có dòng, với trạng thái hiển thị `not_started`. `ListAssessmentStudents` của bài có `course_id` gộp học viên của khóa chưa có dòng, trạng thái `not_started`.
- Quy tắc: bài `draft` hoặc `cancelled` không hiện; học sinh không còn là học viên thì không bắt đầu được lượt mới, dòng/Attempt cũ không bị xóa.

- [ ] **Step 1: Test** `test_course_learner_starting_attempt_creates_student_assessment_once`, `test_non_learner_cannot_start_course_assessment` (code như "chưa được giao", đúng status hiện có), `test_my_assessments_lists_course_assessments_before_any_attempt`, `test_student_joining_class_after_course_publish_can_start_attempt` (B-021 cho khóa), `test_teacher_student_list_shows_not_started_learners_of_course`, `test_removed_learner_keeps_existing_attempts_but_cannot_start_new`, `test_draft_course_assessment_is_invisible_to_learner`.
- [ ] **Step 2:** FAIL. **Step 3:** implement; không thêm hook vào `AddClassMember`/`EndClassMember`/`UpdateClass`/`PublishAssessment` (chỉ gỡ lời gọi Sync ghi danh ở Task 3).
- [ ] **Step 4:** `--filter=Assessment`, `--filter=SubmissionGrading`, `--filter=ResultProgress` PASS. **Step 5:** commit `feat(assessment): course learners get student assessment on first attempt`.

### Task 6: Dọn, tài liệu API, chạy cả bộ

**Files:** `routes/api.php`, `storage/api-docs/openapi.json`, `docs/architecture/error-codes.md`, `known-debt.md` (xóa D-26..D-31), `BACKLOG.md` (xóa B-021, B-042, B-047), `STATE.md`, `module-boundaries.md`.

- [ ] **Step 1:** `grep -rli "enrollment\|CourseClassLink\|course_class" app database routes tests` chỉ còn migration cũ; xóa phần thừa.
- [ ] **Step 2:** `php artisan test` (cả bộ), `vendor/bin/pint`, `composer analyse` đều xanh, không thêm baseline.
- [ ] **Step 3:** `scramble:export`, commit `docs: export OpenAPI for course model`; cập nhật docs ở repo docs (nhánh riêng, PR riêng).
- [ ] **Step 4:** review độc lập toàn nhánh bằng subagent `opus`, sửa theo review (commit mới, không amend). Push nhánh, báo repo/nhánh/số commit; PR chờ user.

---

**Ngoài plan này (làm sau khi BE xong, vì phụ thuộc hợp đồng API):** mockup AD-12/TC-15/ST-09/TC-07..10; FE Admin, Giáo viên, Học sinh. Hai plan riêng.

---

## Kết quả thực hiện (2026-10-30, nhánh `feat/course-model-rework` của `piclass-api`)

- Task 1–5 xong, cả bộ test xanh (2340), PHPStan sạch, baseline nhỏ đi (727 → 571 dòng). OpenAPI đã xuất (file bị git bỏ qua).
- Khác plan (đã ghi ở sổ thực hiện): đổi hiển thị câu hỏi qua endpoint riêng `PATCH /questions/{q}/visibility` (sửa câu tạo phiên bản mới); từ chối câu riêng tư qua Policy 403 `FORBIDDEN` (không thêm mã `QUESTION_NOT_VISIBLE`); `CourseItemType` đặt ở `Academic/Enums`; gỡ mục khóa luôn lưu trữ bài (không xóa cứng); quyền bắt đầu làm bài của khóa luôn dựa vào việc còn là học viên.
- Route mới: `/courses/{course}/audience` (GET/POST/DELETE `{audienceId}`), `/courses/{course}/learners`, `/courses/{course}/items` (GET), `/courses/{course}/items/copy` (POST), `/courses/{course}/items/{itemId}` (PATCH/DELETE), `/questions/{question}/visibility` (PATCH); `POST /assessments` nhận `course_id`.
- Còn lại của lát: mockup (AD-12, TC-15, ST-09, TC-07/08/09/10) và FE ba cổng, lập plan riêng.

