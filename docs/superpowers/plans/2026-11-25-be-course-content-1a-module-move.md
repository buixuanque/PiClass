# BE khóa học có nội dung học, lát 1a: tạo module `CourseContent` và chuyển `CourseItem` Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tạo module thứ 13 `CourseContent` và chuyển toàn bộ việc ghi/đọc `CourseItem` từ `Assessment` sang đó, **không đổi hành vi, URL hay JSON** của bất kỳ endpoint nào.

**Architecture:** `CourseContent` điều phối mục khóa và gọi Action của `Assessment` khi mục là bài đánh giá (chỉ một chiều `CourseContent → Assessment`; Assessment không còn gọi vào CourseContent). `Assessment` mất mọi ghi vào `course_items`. Đây là bước nền cho lát 1b (schema nhiều loại mục, bài giảng, tài liệu).

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL; chạy lệnh trong `piclass-infra/local` bằng `docker compose exec api ...`.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 2, mục 9 lát 1); quyết định `ARCH-39`.

## Global Constraints

- Nhánh mới từ `origin/main` mới nhất của `piclass-api` (`git fetch origin` trước). Không commit/push thẳng `main`. Push nhánh sau mỗi task xong và test xanh; không force-push, không `--amend` commit đã push. PR và merge chỉ khi user nói rõ.
- Comment trong code và mô tả test bằng **tiếng Việt**; tên biến/hàm/class/test method/`error.code`/commit message bằng tiếng Anh; commit message kết thúc bằng dòng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`.
- Theo `piclass-api/CLAUDE.md`: controller mỏng (R1), Action không biết HTTP (R2) và chỉ có một method `handle()`, DI qua constructor (R5), ghi dữ liệu model của module khác chỉ qua Action của module đó (R4), không literal trùng giá trị Enum (R8: dùng case của Enum).
- Giữ nguyên mọi URL, tên field JSON, status và `error.code` hiện có. Mọi test đang xanh trong `tests/Feature/Assessment/*Course*`, `tests/Feature/Models/CourseModelSchemaTest.php`, `tests/Feature/Academic/ParentClassCourseScopeTest.php` phải vẫn xanh **không sửa kỳ vọng**.
- Cuối plan: `php artisan test`, `vendor/bin/pint`, `composer analyse` (PHPStan mức 5, không thêm lỗi, không thêm vào baseline), rồi export OpenAPI (`php artisan scramble:export`); `storage/api-docs/openapi.json` **không đổi** (kiểm bằng `git diff`).

## Review Focus

- Học sinh/Parent không thấy mục có bài chưa mở (`draft`, `cancelled`, `archived`) sau khi chuyển `ListCourseItemsQuery`: test hiện có phải còn bắt được nếu bỏ điều kiện `whereHas`.
- Gỡ mục khi `AuditLogger` ném lỗi: bài **không** bị lưu trữ và mục **không** bị xóa (rollback chung một transaction, `DEC-AUD-005`).
- Sao chép nhiều bài mà một nguồn không hợp lệ: không có bản sao hay mục nào được tạo (nguyên tử).
- Đổi thứ tự mục đồng thời: vị trí vẫn liên tục 1..n sau khi gỡ mục ở giữa.
- Tạo bài có `course_id` của khóa đã lưu trữ vẫn trả `COURSE_ARCHIVED` và không để lại bài mồ côi.

---

### Task 1: Module `CourseContent` và đường đọc mục khóa

**Files:**
- Create: `app/Modules/CourseContent/Providers/CourseContentServiceProvider.php`
- Create (di chuyển từ `Assessment`, đổi namespace sang `App\Modules\CourseContent\...`): `Queries/ListCourseItemsQuery.php`, `Http/Controllers/ListCourseItemsController.php`, `Http/Requests/ViewCourseRequest.php`, `Http/Resources/CourseItemResource.php`, `Http/Resources/CourseItemsResource.php`
- Modify: `bootstrap/providers.php`, `routes/api.php` (import `ListCourseItemsController`), `tests/Architecture/ArchitectureTest.php` (`MODULES` thêm `'CourseContent'`)
- Delete: các file nguồn tương ứng trong `app/Modules/Assessment/`

**Interfaces:**
- Produces: `App\Modules\CourseContent\Queries\ListCourseItemsQuery::handle(Course $course, User $user): Collection<int, CourseItem>` (giữ nguyên chữ ký và hành vi); `CourseItemResource`, `CourseItemsResource` (giữ nguyên JSON); `ViewCourseRequest`.

- [ ] **Step 1: Chạy bộ test khóa học để ghi nhận mức xanh ban đầu**

Run: `docker compose exec api php artisan test tests/Feature/Assessment tests/Feature/Models/CourseModelSchemaTest.php tests/Feature/Academic tests/Architecture`
Expected: PASS toàn bộ (ghi lại số test để so sánh ở các task sau).

- [ ] **Step 2: Tạo `CourseContentServiceProvider` rỗng (`register()` và `boot()` trống) và đăng ký trong `bootstrap/providers.php` sau `AcademicServiceProvider`.**

- [ ] **Step 3: Di chuyển 5 file đọc bằng `git mv`, sửa `namespace` và `use` bên trong; cập nhật import trong `routes/api.php`.** Không đổi logic. Docblock giữ nguyên (đã tiếng Việt).

- [ ] **Step 4: Thêm `'CourseContent'` vào `MODULES` của `ArchitectureTest` (cuối danh sách).**

- [ ] **Step 5: Chạy lại bộ test ở Step 1**

Run: như Step 1.
Expected: PASS, cùng số test. `test_module_list_is_wired_to_real_modules` yêu cầu module có controller (đã có `ListCourseItemsController`). Nếu `test_module_follows_architecture_rules` báo R8 cho `CourseContent`, thay literal bằng case Enum và, nếu cần Enum module khác, thêm đúng dòng vào `SHARED_ENUMS` (kèm `test_shared_enums_match_the_imports` xanh).

- [ ] **Step 6: Commit**

```bash
git add -A app bootstrap routes tests
git commit -m "refactor: add CourseContent module and move course item listing"
```

### Task 2: `ArchiveCourseAssessment` tách khỏi `RemoveCourseItem`

**Files:**
- Create: `app/Modules/Assessment/Actions/ArchiveCourseAssessment.php`
- Test: `tests/Feature/Assessment/ArchiveCourseAssessmentTest.php`

**Interfaces:**
- Produces: `final class ArchiveCourseAssessment { __construct(AuditLogger $audit); handle(Assessment $assessment, User $actor, string $courseId): void }` — khóa dòng bài (trait `LocksAssessment`), đặt `status = Archived` và `archived_at = now()`, ghi audit bắt buộc `assessment.archive` (`correlationKey = $courseId`) **trong cùng transaction**; **không** đụng `course_items`.

- [ ] **Step 1: Viết test thất bại** `test_archives_the_assessment_and_audits_in_one_transaction`: tạo bài của khóa đã publish, gọi action, assert `status = archived`, `archived_at` khác null, đúng một dòng audit `assessment.archive` có `before.status = published`, và `CourseItem` của bài **vẫn tồn tại**. Thêm `test_audit_failure_leaves_the_assessment_untouched`: bind `AuditLogger` giả ném lỗi với message cố định (theo `docs/rules/testing.md`), assert `assertSame` message đó và bài vẫn `published`.

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter=ArchiveCourseAssessmentTest`
Expected: FAIL (class không tồn tại).

- [ ] **Step 3: Implement `ArchiveCourseAssessment`** bằng cách lấy đúng phần archive + audit của `Assessment\Actions\RemoveCourseItem::handle` (khối `lockedAssessment` … `audit->record`), đặt trong `DB::transaction`.

- [ ] **Step 4: Chạy test để thấy PASS**

Run: như Step 2.
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Assessment/Actions/ArchiveCourseAssessment.php tests/Feature/Assessment/ArchiveCourseAssessmentTest.php
git commit -m "refactor: extract ArchiveCourseAssessment from RemoveCourseItem"
```

### Task 3: Chuyển sửa/gỡ mục sang `CourseContent`

**Files:**
- Create (chuyển từ `Assessment`): `app/Modules/CourseContent/Actions/UpdateCourseItem.php`, `Http/Controllers/UpdateCourseItemController.php`, `Http/Controllers/RemoveCourseItemController.php`, `Http/Requests/ManageCourseItemRequest.php`
- Create: `app/Modules/CourseContent/Actions/RemoveCourseItem.php` (viết lại, xem Interfaces)
- Modify: `routes/api.php`
- Delete: `Assessment/Actions/RemoveCourseItem.php`, `Assessment/Actions/UpdateCourseItem.php` và các controller/request tương ứng

**Interfaces:**
- Consumes: `ArchiveCourseAssessment::handle(Assessment, User, string): void` (Task 2).
- Produces: `CourseContent\Actions\UpdateCourseItem::handle(CourseItem $item, array $data): CourseItem` (giữ nguyên); `CourseContent\Actions\RemoveCourseItem::handle(CourseItem $item, User $actor): void`.

- [ ] **Step 1: Viết test thất bại** trong `tests/Feature/CourseContent/RemoveCourseItemTest.php`: (a) `test_removing_an_assessment_item_archives_the_assessment_and_renumbers`: khóa có 3 mục, gỡ mục giữa qua `DELETE /api/v1/courses/{id}/items/{itemId}`, assert 204 (đúng status hiện có), bài đã `archived`, mục còn lại có `position` 1 và 2; (b) `test_audit_failure_keeps_the_item_and_the_assessment`: audit giả ném lỗi, assert mục vẫn còn, bài vẫn `published`, vị trí không đổi.

- [ ] **Step 2: Chạy test để thấy kết quả hiện tại** (có thể đã xanh vì hành vi chưa đổi; mục đích ghim hành vi trước khi chuyển — theo `backend-gotchas.md`: "viết test ghim hành vi hiện tại trước, thấy xanh, rồi chuyển").

Run: `docker compose exec api php artisan test --filter=RemoveCourseItemTest`
Expected: PASS với code hiện tại.

- [ ] **Step 3: `git mv` `UpdateCourseItem`, hai controller và `ManageCourseItemRequest` sang `CourseContent`, sửa namespace; cập nhật `routes/api.php`.**

- [ ] **Step 4: Viết `CourseContent\Actions\RemoveCourseItem::handle(CourseItem $item, User $actor): void`:** một `DB::transaction` gồm gọi `ArchiveCourseAssessment` (inject qua constructor) cho `$item->assessment`, xóa `$item`, đánh số lại các mục còn lại của khóa liên tục 1..n có `lockForUpdate` (giữ logic cũ). Xóa `Assessment\Actions\RemoveCourseItem`.

- [ ] **Step 5: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/Assessment tests/Architecture`
Expected: PASS, cùng số test cũ cộng test mới. Nếu `ArchitectureTest` báo R4 cho `CourseItem::...` trong `CourseContent` vì `MODEL_OWNERS['CourseItem']` còn là `Assessment`, **chưa sửa ở task này** (Task 6): ghi nhận để xử lý đúng lúc; nếu test đỏ thì tạm thời thêm đúng dòng vào danh sách ngoại lệ đã có (`KNOWN_*`) kèm comment "bỏ ở Task 6".

- [ ] **Step 6: Commit**

```bash
git add -A app routes tests
git commit -m "refactor: move course item update and removal into CourseContent"
```

### Task 4: Chuyển sao chép bài vào khóa

**Files:**
- Create: `app/Modules/CourseContent/Actions/AppendCourseItem.php`, `AddAssessmentToCourse.php`, `AddAssessmentsToCourse.php`
- Create (chuyển từ `Assessment`): `Http/Controllers/CopyAssessmentToCourseController.php`, `Http/Controllers/CopyAssessmentsToCourseController.php`, `Http/Requests/CopyAssessmentToCourseRequest.php`, `Http/Requests/CopyAssessmentsToCourseRequest.php`
- Modify: `app/Modules/Assessment/Actions/CopyAssessmentToCourse.php` (chỉ tạo bài và trả `Assessment`), `routes/api.php`
- Delete: `Assessment/Actions/CopyAssessmentsToCourse.php` và controller/request cũ
- Test: `tests/Feature/CourseContent/AddAssessmentToCourseTest.php`; giữ nguyên `tests/Feature/Assessment/CopyAssessmentsToCourseTest.php` (không sửa kỳ vọng)

**Interfaces:**
- Produces:
  - `AppendCourseItem::handle(Course $course, CourseItemType $type, string $title, array $target): CourseItem` — `$target` là `['assessment_id' => string]` (lát 1b thêm `content_id`); `position = max(position) + 1` của khóa; chỉ tạo dòng, không kiểm quyền.
  - `AddAssessmentToCourse::handle(Assessment $source, Course $course, User $actor): CourseItem` — kiểm khóa chưa lưu trữ (`CourseArchived`), gọi `Assessment\Actions\CopyAssessmentToCourse` (nay trả `Assessment` bản sao) rồi `AppendCourseItem` với `title` = tiêu đề phiên bản hiện tại của bản sao, trong một transaction.
  - `AddAssessmentsToCourse::handle(array $assessmentIds, Course $course, User $actor): array` (`list<CourseItem>`), nguyên tử, giữ thứ tự gửi lên.
- Consumes: `Assessment\Actions\CopyAssessmentToCourse::handle(Assessment $source, Course $course, User $actor): Assessment` (sau khi sửa trong task này).

- [ ] **Step 1: Chạy riêng test sao chép hiện có để ghim hành vi**

Run: `docker compose exec api php artisan test tests/Feature/Assessment/CopyAssessmentsToCourseTest.php tests/Feature/Assessment/CourseAssessmentTest.php`
Expected: PASS.

- [ ] **Step 2: Viết test thất bại** `AddAssessmentToCourseTest`: `test_single_copy_appends_item_with_copy_title`, `test_archived_course_rejects_with_course_archived`, `test_course_assessment_cannot_be_a_copy_source` (nguồn đã có `course_id` → 422 như hiện có: lấy đúng `error.code` từ test cũ), `test_batch_is_atomic_when_one_source_is_invalid` (không bản sao nào, không mục nào).

- [ ] **Step 3: Sửa `Assessment\Actions\CopyAssessmentToCourse`** để chỉ tạo `Assessment` + `AssessmentVersion` + `AssessmentQuestion` và **trả `Assessment`** (bỏ `CourseItem::create` và `use CourseItem`); giữ kiểm `course_id` của nguồn và `CourseArchived`.

- [ ] **Step 4: Implement ba Action `CourseContent`; chuyển hai controller, hai request, cập nhật `routes/api.php`.** Controller trả đúng `CourseItemResource`/`CourseItemsResource` như cũ (`->load('assessment')`).

- [ ] **Step 5: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/Assessment tests/Architecture`
Expected: PASS, kể cả `CopyAssessmentsToCourseTest` không sửa.

- [ ] **Step 6: Commit**

```bash
git add -A app routes tests
git commit -m "refactor: move copying assessments into a course to CourseContent"
```

### Task 5: Tạo bài đánh giá ngay trong khóa

**Files:**
- Create: `app/Modules/CourseContent/Actions/CreateCourseAssessment.php`
- Modify: `app/Modules/Assessment/Actions/CreateAssessment.php` (bỏ `CourseItem::create` và `use CourseItem`), `app/Modules/Assessment/Http/Controllers/CreateAssessmentController.php`
- Test: `tests/Feature/CourseContent/CreateCourseAssessmentTest.php`

**Interfaces:**
- Produces: `CreateCourseAssessment::handle(User $actor, array $data): Assessment` — `$data` đã validate và có `course_id`; trong một `DB::transaction` gọi `Assessment\Actions\CreateAssessment::handle` rồi `AppendCourseItem` (`title` = `$data['title']`); lỗi `CourseArchived` từ Action con được giữ nguyên.
- Consumes: `AppendCourseItem::handle` (Task 4), `Assessment\Actions\CreateAssessment::handle(User, array): Assessment` (nay không tạo mục).

- [ ] **Step 1: Viết test thất bại** (ghim trước, dùng route `POST /api/v1/assessments`): `test_create_with_course_id_adds_item_at_the_end` (assert mục có `title` = tên bài, `position` = số mục cũ + 1, JSON `AssessmentCreatedResource` không đổi), `test_archived_course_returns_course_archived_and_leaves_no_orphan` (không có `Assessment` mới), `test_create_without_course_id_creates_no_item`.

- [ ] **Step 2: Chạy test để thấy PASS với hành vi hiện tại, rồi chuyển** (cùng cách ghim ở Task 3).

Run: `docker compose exec api php artisan test --filter=CreateCourseAssessmentTest`
Expected: PASS.

- [ ] **Step 3: Sửa `CreateAssessment`** bỏ ghi `CourseItem`; **sửa `CreateAssessmentController`:** nếu `validated()['course_id']` có giá trị thì gọi `CreateCourseAssessment` (inject thêm vào `__invoke`), ngược lại gọi `CreateAssessment`; vẫn trả `AssessmentCreatedResource`.

- [ ] **Step 4: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/Assessment tests/Architecture`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A app tests
git commit -m "refactor: create course assessments through CourseContent"
```

### Task 6: Đổi chủ sở hữu `CourseItem`, dọn và kiểm tra cuối

**Files:**
- Modify: `tests/Architecture/ArchitectureTest.php` (`MODEL_OWNERS['CourseItem'] = 'CourseContent'`, gỡ ngoại lệ tạm ở Task 3 nếu có), `app/Models/CourseItem.php` (docblock), docs `docs/architecture/model-ownership.md` (repo docs: dòng `CourseItem`)
- Modify (docs): `docs/architecture/open-decisions.md` (`ARCH-39` → "ĐÃ CHỐT VÀ ĐÃ LÀM một phần: lát 1a"), `docs/STATE.md`

- [ ] **Step 1: Đổi `MODEL_OWNERS['CourseItem']` sang `'CourseContent'`; chạy `ArchitectureTest`**

Run: `docker compose exec api php artisan test tests/Architecture`
Expected: PASS. Nếu R4 báo `CourseItem::` còn xuất hiện trong module khác `CourseContent` (ví dụ seeder không tính, nhưng kiểm `grep -rn "CourseItem::" app/Modules | grep -v CourseContent`), sửa nốt về Action của `CourseContent`.

- [ ] **Step 2: Chạy toàn bộ kiểm tra chất lượng**

Run: `docker compose exec api php artisan test && docker compose exec api vendor/bin/pint --test && docker compose exec api composer analyse`
Expected: toàn bộ PASS, Pint không đổi file, PHPStan không thêm lỗi.

- [ ] **Step 3: Export OpenAPI và kiểm không đổi**

Run: `docker compose exec api php artisan scramble:export && git diff --stat storage/api-docs/openapi.json`
Expected: không có thay đổi (contract giữ nguyên). Nếu khác, tìm field/route bị đổi và sửa về như cũ.

- [ ] **Step 4: Cập nhật docs (repo docs, nhánh riêng):** sửa dòng `CourseItem` trong `model-ownership.md` thành `CourseContent`; ghi `ARCH-39` đã làm lát 1a; `STATE.md` "lát 1a xong, kế tiếp lát 1b".

- [ ] **Step 5: Commit và push (không mở PR)**

```bash
git add -A
git commit -m "refactor: CourseContent owns CourseItem"
git push -u origin <tên nhánh>
```

---

## Self-Review (đã chạy)

- **Phủ spec:** lát 1a chỉ phủ "chuyển `CourseItem` sang `CourseContent`" (spec mục 2 và dòng đầu lát 1 ở mục 9). Phần còn lại của lát 1 (migration `learning_contents`, cột cài đặt khóa, loại mục mới, chương, bài giảng/tài liệu, làm sạch HTML, ảnh, tệp, sắp xếp) thuộc **plan lát 1b**, viết sau khi 1a xong để bám đúng cấu trúc thật.
- **Quét bước:** mỗi step có lệnh hoặc chữ ký; không còn "TBD".
- **Nhất quán kiểu:** `AppendCourseItem::handle(Course, CourseItemType, string, array): CourseItem` dùng thống nhất ở Task 4 và 5; `CopyAssessmentToCourse` đổi kiểu trả (CourseItem → Assessment) chỉ ở Task 4 và mọi nơi gọi được liệt kê (`AddAssessmentToCourse`).
- **Ngoài phạm vi có chủ ý:** seeder (`TeacherCourseDemoSeeder`, `ParentDemoSeeder`) và `CourseItemFactory` vẫn dùng model trực tiếp, không bị R4 canh (R4 chỉ quét `app/Modules`).
- **Quyết định lệch spec ghi cho 1b:** "chương" làm mục `chapter` đứng như dấu phân cách trong danh sách phẳng (đúng mockup), **không** dùng `parent_id`; spec mục 3 sẽ sửa ở 1b.
