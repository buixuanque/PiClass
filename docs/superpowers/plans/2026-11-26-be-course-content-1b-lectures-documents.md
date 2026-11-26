# BE khóa học có nội dung học, lát 1b: mục nhiều loại, bài giảng, tài liệu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Khóa học chứa được chương, bài giảng (HTML kiểu Word, có ảnh) và tài liệu (tệp) bên cạnh bài đánh giá; Giáo viên chủ khóa và Admin soạn, sắp xếp, gỡ qua API; khóa lưu thêm cài đặt học (tuần tự, điều kiện hoàn thành).

**Architecture:** Module `CourseContent` (đã có từ lát 1a) sở hữu `LearningContent` và `CourseItem` mở rộng; Academic sở hữu 3 cột cài đặt mới của `Course`; File sở hữu `AttachmentLink` (cột mới `learning_content_id`) và Action đồng bộ liên kết tệp. HTML bài giảng làm sạch ở backend bằng HTMLPurifier, ảnh lưu thành `Attachment` rồi tham chiếu bằng `data-attachment-id`. Học sinh **chưa** đọc được nội dung ở lát này (lát 4).

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL, `ezyang/htmlpurifier`; chạy lệnh trong `piclass-infra/local` bằng `docker compose exec api ...`.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 2, 3, 4.6 đến 4.8, 5, 6, 9 lát 1); `DEC-LRN-052`, `DEC-LRN-053`, `ARCH-39`. Phần việc dồn từ review lát 1a: `B-097`.

## Global Constraints

- Nhánh mới từ `origin/main` mới nhất của `piclass-api` (`git fetch origin` trước). Không commit/push thẳng `main`; push nhánh sau mỗi task xanh; không force-push, không `--amend` commit đã push; PR/merge chỉ khi user nói rõ. Mọi commit đẩy lên phải xanh (chạy test trước khi commit).
- Comment trong code và mô tả test bằng **tiếng Việt**; tên biến/hàm/class/test method/`error.code`/commit message bằng tiếng Anh; commit message kết thúc bằng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`.
- Theo `piclass-api/CLAUDE.md`: controller mỏng (R1), Action không biết HTTP (R2, một method `handle()`), `Domain/` thuần (R3), DI qua constructor (R5), ghi dữ liệu model của module khác chỉ qua Action của module đó (R4), không literal trùng giá trị Enum (R8), thời gian qua `AppTime` (R9 đến R11), response luôn qua Resource.
- Thứ tự kiểm tra lỗi: 403/404 (quyền) → 422 (input) → 409 (trạng thái). Test 403/404 assert cả `code` lẫn `message`; lỗi nghiệp vụ là `DomainException` với `error.code` UPPER_SNAKE (xem `docs/architecture/error-codes.md` trước khi thêm mã).
- Giới hạn từ spec, chép nguyên văn: bài giảng HTML tối đa **200 KB**; thẻ HTML cho phép `h2 h3 p b strong i em u s ul ol li blockquote table thead tbody tr th td a img br`; thuộc tính `href` (chỉ `http`, `https`, `mailto`), `data-attachment-id`, `alt`, `style` chỉ `text-align`; tài liệu PDF/Word/PowerPoint tối đa **50 MB**; ảnh trong bài tối đa **5 MB**, JPEG hoặc PNG; khóa `archived` không nhận thêm mục (`COURSE_ARCHIVED`, `BR-COURSE-006`).
- Chương là mục `chapter` đứng phẳng trong danh sách mục (đúng mockup), **không** dùng `parent_id`; cột `parent_id` giữ nguyên, không dùng.
- Cuối plan: `php artisan test` (cả bộ), `vendor/bin/pint --test` trên file đã đổi, `composer analyse` (PHPStan mức 5: baseline hiện 33 lỗi cũ, không thêm lỗi mới, không thêm vào baseline), `php artisan scramble:export` (`storage/api-docs/openapi.json` bị gitignore, chỉ kiểm export không lỗi và `tests/Feature/OpenApiSpecTest.php` xanh). Biết trước: `TeacherGradingDemoSeederTest` chập chờn, chạy lại riêng nếu đỏ.
- Mọi test bài có tên `Course*` hiện có (`tests/Feature/Assessment/*Course*`, `tests/Feature/CourseContent`, `tests/Feature/Academic`) vẫn xanh, không sửa kỳ vọng, trừ khi task nói rõ.

## Review Focus

- Bài giảng chứa mã tấn công (`<script>`, `onerror=`, `javascript:` trong `href`, `<iframe>`, `style` chứa `url(...)`) bị làm sạch cả lúc ghi lẫn lúc trả về; test chuỗi XSS thật (Task 3, 5).
- Học sinh hoặc Giáo viên khác khóa đọc/sửa/xóa nội dung, mục, thứ tự của khóa không phải của mình: 403/404 với fixture có dữ liệu thật, kể cả khóa dùng chung (chỉ Admin quản lý) (Task 4, 5, 6).
- Chèn ảnh/tệp của người khác (`attachment_id` do người khác upload) hoặc sai định dạng, quá cỡ, đã gắn nơi khác vào bài giảng/tài liệu của mình: bị từ chối, không lộ tệp (Task 7, 8).
- Hai yêu cầu thêm mục đồng thời không trùng `position`; mất kết nối giữa chừng không để lại mục mồ côi hoặc nội dung không có mục (Task 4).
- Gỡ chương/bài giảng/tài liệu đánh số lại liên tục 1..n và xóa nội dung đi kèm, không đụng bài đánh giá khác; gỡ mục bài đánh giá vẫn lưu trữ bài + audit như trước (Task 5).
- Sắp xếp lại với danh sách thiếu, thừa hoặc trùng id mục: 422, không đổi gì (Task 6).

---

### Task 1: Schema, enum, model nền

**Files:**
- Create: `database/migrations/2026_11_26_000001_add_course_content_foundations.php`
- Create: `app/Models/LearningContent.php`, `database/factories/LearningContentFactory.php`
- Create: `app/Modules/CourseContent/Enums/LearningContentType.php`, `app/Modules/CourseContent/Enums/LearningContentStatus.php`, `app/Modules/Academic/Enums/CourseCompletionRule.php`
- Modify: `app/Modules/Academic/Enums/CourseItemType.php`, `app/Models/CourseItem.php`, `app/Models/Course.php`, `app/Models/AttachmentLink.php`, `database/factories/CourseItemFactory.php`, `tests/Architecture/ArchitectureTest.php` (`MODEL_OWNERS['LearningContent'] = 'CourseContent'`, `SHARED_ENUMS` nếu R8/`test_shared_enums_match_the_imports` đòi), `tests/Feature/Models/EnumCastsTest.php`
- Test: `tests/Feature/CourseContent/CourseContentSchemaTest.php`

**Interfaces:**
- Produces: `CourseItemType` thêm `Lecture='lecture'`, `Document='document'`, `Video='video'`, `Chapter='chapter'`; `LearningContentType {Lecture, Document, Video}`; `LearningContentStatus {Active, Archived}`; `CourseCompletionRule {AllItems='all_items', AllItemsAndMinScore='all_items_and_min_score'}`; model `LearningContent` (fillable `type, course_id, owner_user_id, title, body_html, status, archived_at`; casts enum; quan hệ `course()`, `ownerUser()`, `item(): HasOne<CourseItem>` theo `content_id`, `attachmentLinks(): HasMany<AttachmentLink>`); `CourseItem` thêm `content_id` vào fillable và `content(): BelongsTo`; `Course` thêm cột `sequential_unlock` (bool), `completion_rule` (`CourseCompletionRule`), `completion_min_score` (decimal 2 chữ số, nullable) vào fillable/casts; `AttachmentLink` thêm `learning_content_id` vào fillable và `learningContent(): BelongsTo`.

- [ ] **Step 1: Viết test thất bại** `CourseContentSchemaTest`: (a) `test_course_defaults_to_free_order_and_all_items_rule` (khóa mới có `sequential_unlock=false`, `completion_rule=AllItems`, `completion_min_score=null`); (b) `test_course_item_check_constraint_matches_type`: tạo trực tiếp bằng `DB::table('course_items')->insert` rồi assert `QueryException` cho từng tổ hợp sai: `assessment` thiếu `assessment_id`; `assessment` có `content_id`; `lecture` thiếu `content_id`; `lecture` có `assessment_id`; `chapter` có `content_id` hoặc `assessment_id`; còn mỗi tổ hợp đúng chèn được; (c) `test_course_item_content_id_is_unique`; (d) `test_attachment_link_can_point_to_learning_content`; (e) `test_learning_content_course_id_restrict_on_delete` (xóa khóa còn nội dung bị chặn).

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter=CourseContentSchemaTest`
Expected: FAIL (cột/bảng chưa có).

- [ ] **Step 3: Viết migration** (không cần `down` đảo ngược, theo mẫu `2026_10_30_000001_rework_course_model.php`): `courses` thêm `sequential_unlock boolean default false`, `completion_rule` (enum, default `all_items`), `completion_min_score decimal(5,2) null`; tạo `learning_contents` đúng cột/chỉ mục ở spec mục 3 (kiểu `uuid`, `type` enum 3 giá trị, `course_id` null FK `courses` `restrict`, `owner_user_id` FK `users`, `status` enum default `active`, chỉ mục `(course_id)` và `(owner_user_id, type)`); `course_items`: gỡ CHECK của cột `type` (kiểm tên thật trong `pg_constraint`, thường `course_items_type_check`) rồi thêm CHECK mới cho 4 giá trị, thêm `content_id` null FK `learning_contents` `restrict` unique, và CHECK theo `type` như spec mục 3; `attachment_links` thêm `learning_content_id` null FK `learning_contents` `cascadeOnDelete`.

- [ ] **Step 4: Thêm enum, model, factory và cập nhật `ArchitectureTest`/`EnumCastsTest`** như phần Interfaces. `LearningContentFactory` mặc định `type=lecture`, `course_id` từ `Course::factory()`, `owner_user_id` từ `User::factory()`, `title` ngẫu nhiên, `body_html` `<p>…</p>`; có state `document()` và `standalone()` (`course_id` null). `CourseItemFactory` giữ mặc định `assessment`, thêm state `lecture()`, `chapter()`. Docblock enum/model bằng tiếng Việt.

- [ ] **Step 5: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/Models tests/Architecture`
Expected: PASS (gồm `EnumCastsTest` đối chiếu Enum với CHECK mới và `test_every_model_has_an_owner_and_the_map_is_complete`).

- [ ] **Step 6: Commit**

```bash
git add -A app database tests
git commit -m "feat: add learning contents schema, course item types and course learning settings columns"
```

### Task 2: Cài đặt học của khóa

**Files:**
- Modify: `app/Modules/Academic/Http/Requests/UpdateCourseRequest.php`, `app/Modules/Academic/Actions/UpdateCourse.php`, `app/Modules/Academic/Http/Resources/CourseResource.php`, `app/Modules/Academic/Http/Resources/UpdatedCourseResource.php` (nếu cũng trả cột khóa), `database/factories/CourseFactory.php` (nếu cần)
- Test: `tests/Feature/Academic/CourseLearningSettingsTest.php`

**Interfaces:**
- Consumes: `Course` cột mới, `CourseCompletionRule` (Task 1).
- Produces: `PATCH /courses/{course}` nhận thêm `sequential_unlock` (boolean), `completion_rule` (`Rule::enum(CourseCompletionRule::class)`), `completion_min_score` (số 0 đến 10, tối đa 2 chữ số thập phân, nullable); `CourseResource` (và `UpdatedCourseResource`) trả thêm ba field cùng tên. Quy tắc: `completion_rule = all_items_and_min_score` thì bắt buộc có `completion_min_score` (so với giá trị đã lưu khi request chỉ gửi một trong hai) → 422 `VALIDATION_FAILED` trường `completion_min_score`; chuyển về `all_items` thì `completion_min_score` được đặt `null`.

- [ ] **Step 1: Viết test thất bại**: chủ khóa bật `sequential_unlock` và đặt rule + điểm (200, response có ba field); rule điểm tối thiểu thiếu điểm → 422; điểm âm hoặc > 10 → 422; về `all_items` xóa điểm; Giáo viên khác (không phải chủ) → 403 với `code` và `message` đúng như test `CourseTest` hiện có cho `PATCH`; Giáo viên không sửa được khóa dùng chung; khóa `archived` vẫn sửa được cài đặt? (không: dùng lại `COURSE_ARCHIVED` giống quy tắc "không thêm đối tượng/mục vào khóa lưu trữ" **chỉ cho mục**; sửa cài đặt khóa đã lưu trữ giữ nguyên hành vi hiện tại của `PATCH`, test ghim hành vi đó).

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter=CourseLearningSettingsTest`
Expected: FAIL.

- [ ] **Step 3: Implement** trong `UpdateCourseRequest::rules()` + `withValidator`/`after()` kiểm điều kiện điểm tối thiểu; `UpdateCourse::handle` giữ chữ ký, thêm nhánh đặt `completion_min_score=null` khi rule về `all_items`; mở rộng hai Resource.

- [ ] **Step 4: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/Academic tests/Feature/Assessment/CourseModelReviewFixesTest.php`
Expected: PASS (test `CourseTest` cũ không đổi kỳ vọng; nếu một test cũ so sánh **toàn bộ tập key** của `CourseResource` thì cập nhật đúng tập key đó và ghi rõ vì sao trong báo cáo, đó là thay đổi hợp lệ duy nhất cho phép).

- [ ] **Step 5: Commit**

```bash
git add -A app database tests
git commit -m "feat: course learning settings (sequential unlock, completion rule)"
```

### Task 3: `LectureHtmlSanitizer` và `LectureImages`

**Files:**
- Modify: `composer.json`, `composer.lock` (thêm `ezyang/htmlpurifier`)
- Create: `app/Modules/CourseContent/Domain/LectureHtmlSanitizer.php`, `app/Modules/CourseContent/Domain/LectureImages.php`
- Test: `tests/Unit/CourseContent/LectureHtmlSanitizerTest.php`, `tests/Unit/CourseContent/LectureImagesTest.php` (không cần DB)

**Interfaces:**
- Produces:
  - `final class LectureHtmlSanitizer { public function sanitize(string $html): string }` — danh sách cho phép đúng như Global Constraints; tắt cache định nghĩa của HTMLPurifier (`Cache.DefinitionImpl = null`) để không cần thư mục ghi; không dùng facade/`config()` (R3).
  - `final class LectureImages { public static function ids(string $html): array /* list<string> uuid duy nhất, theo thứ tự xuất hiện */; public static function withUrls(string $html, array $urlsByAttachmentId): string /* gắn src cho mỗi <img data-attachment-id>; id không có trong map thì bỏ thẻ img */ }`.

- [ ] **Step 1: Cài thư viện**

Run: `docker compose exec api composer require ezyang/htmlpurifier`
Expected: cài thành công, `composer.json`/`composer.lock` đổi.

- [ ] **Step 2: Viết test thất bại** cho sanitizer, mỗi ca một assert trên chuỗi kết quả: giữ nguyên `<h2>`, `<b>`, `<ul><li>`, `<table>` hợp lệ, `<a href="https://x.vn">`; bỏ `<script>alert(1)</script>`, `<img src=x onerror=alert(1)>` (và `onerror`), `<iframe src=...>`, `<a href="javascript:alert(1)">` (mất `href`), `style="position:fixed"` (mất), giữ `style="text-align:center"`, bỏ thẻ không có trong danh sách (`<div>`, `<font>`), giữ `data-attachment-id`, chuỗi XSS biến thể (`<svg onload=...>`, `<IMG SRC=JaVaScRiPt:...>`, thẻ lồng sai). Test `LectureImages`: `ids` lấy đúng và loại trùng, bỏ id không phải uuid; `withUrls` gắn `src`, bỏ img thiếu trong map.

- [ ] **Step 3: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter="LectureHtmlSanitizerTest|LectureImagesTest"`
Expected: FAIL (class chưa có).

- [ ] **Step 4: Implement hai class** (HTMLPurifier cấu hình `HTML.AllowedElements`, `HTML.AllowedAttributes`, `URI.AllowedSchemes`, `CSS.AllowedProperties=['text-align']`; `LectureImages` dùng `DOMDocument`).

- [ ] **Step 5: Chạy test, Pint, PHPStan**

Run: `docker compose exec api php artisan test --filter="LectureHtmlSanitizerTest|LectureImagesTest" && docker compose exec api composer analyse`
Expected: PASS; PHPStan không thêm lỗi.

- [ ] **Step 6: Commit**

```bash
git add -A app composer.json composer.lock tests
git commit -m "feat: lecture html sanitizer and image reference helpers"
```

### Task 4: Thêm chương và bài giảng vào khóa

**Files:**
- Create: `app/Modules/CourseContent/Policies/LearningContentPolicy.php` (đăng ký trong `CourseContentServiceProvider`), `Actions/CreateCourseItem.php`, `Actions/AddCourseChapter.php`, `Actions/AddCourseLecture.php`, `Http/Controllers/CreateCourseItemController.php`, `Http/Requests/CreateCourseItemRequest.php`, `Exceptions/LectureTooLarge.php`
- Modify: `app/Modules/CourseContent/Actions/AppendCourseItem.php`, `Http/Resources/CourseItemResource.php`, `Queries/ListCourseItemsQuery.php`, `routes/api.php`, `app/Modules/CourseContent/Actions/CreateCourseAssessment.php` (không đổi hành vi), `tests/Architecture/ArchitectureTest.php` (nếu R8 đòi)
- Test: `tests/Feature/CourseContent/CreateCourseItemTest.php`, `tests/Feature/CourseContent/AppendCourseItemTest.php`

**Interfaces:**
- Consumes: Task 1 (model, enum), Task 3 (`LectureHtmlSanitizer`).
- Produces:
  - `LearningContentPolicy::manage(User, LearningContent): Response` — `course_id` có giá trị thì như `CoursePolicy::manage` của khóa đó (chủ khóa hoặc Admin; khóa dùng chung chỉ Admin); `course_id` null thì Admin hoặc `owner_user_id` (chuẩn bị lát 2). Thông điệp 403 như `CoursePolicy::manage`.
  - `CreateCourseItem::handle(Course $course, User $actor, array $data): CourseItem` — router theo `$data['type']` (`chapter` | `lecture`; Task 7 thêm `document`), ném `CourseArchived` khi khóa lưu trữ.
  - `AddCourseChapter::handle(Course, string $title): CourseItem`; `AddCourseLecture::handle(Course, User $actor, string $title, string $bodyHtml): CourseItem` (tạo `LearningContent` `lecture` với HTML đã làm sạch, `owner_user_id = $actor->id`, rồi `AppendCourseItem` với `content_id`); cả hai chạy trong một `DB::transaction`.
  - `AppendCourseItem::handle(...)` nay khóa dòng khóa (`Course::whereKey($course->id)->lock('for no key update')->firstOrFail()`) trước khi đọc `max(position)`; chữ ký giữ nguyên, `$target` nhận `assessment_id` hoặc `content_id`.
  - `POST /courses/{course}/items` body `{type: chapter|lecture, title: string(1..255), body_html?: string (lecture; bắt buộc có, rỗng thì `<p></p>`)}` → 201 `CourseItemResource`; thêm field `content_id`, `content_type` (`lecture|document|video|null`) vào `CourseItemResource` (cần eager-load `content`). Route đặt cùng nhóm `role:admin,teacher` với `PATCH /courses/{course}/items/{itemId}`.
  - `ListCourseItemsQuery`: người quản lý khóa thấy **mọi** mục; người khác vẫn chỉ thấy mục bài đánh giá đã mở (chương/bài giảng/tài liệu chưa hiện với học viên, lát 4 sẽ mở).
- Lỗi mới: `LectureTooLarge` (422, mã `LECTURE_TOO_LARGE`, khi `body_html` > 200 KB, kiểm trước làm sạch), thêm vào `docs/architecture/error-codes.md` sau (controller ghi ở cuối lát).

- [ ] **Step 1: Viết test thất bại** `CreateCourseItemTest`: chủ khóa thêm chương rồi bài giảng → 201, `position` 1 và 2, bài giảng có `content_type=lecture`, `body_html` đã bị làm sạch trong DB (chuỗi có `<script>` không còn); Admin thêm vào khóa dùng chung; Giáo viên khác thêm vào khóa không phải của mình → 403, vào khóa dùng chung → 403; khóa `archived` → 422 `COURSE_ARCHIVED` và không có nội dung/mục mới; `title` rỗng hoặc > 255 → 422; `type` lạ (`video`, `document` chưa hỗ trợ ở task này) → 422; `body_html` > 200 KB → 422 `LECTURE_TOO_LARGE`; học sinh gọi route → 403 (middleware `role:`); `GET /courses/{id}/items` trả chương và bài giảng cho chủ khóa nhưng **không** trả cho Học sinh là học viên (vẫn chỉ bài đã mở).
  `AppendCourseItemTest` (ghim B-097): `test_two_appends_never_share_a_position` (gọi hai lần liên tiếp trong cùng transaction, vị trí 1 và 2) và `test_failed_append_leaves_no_orphan_content` (mock/bind `AppendCourseItem` ném lỗi trong `AddCourseLecture`: không còn `LearningContent` nào, `Assessment::count()` không đổi).

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter="CreateCourseItemTest|AppendCourseItemTest"`
Expected: FAIL.

- [ ] **Step 3: Implement** theo Interfaces; `CreateCourseItemRequest::authorize()` gọi `Gate::inspect('manage', $this->route('course'))`; kiểm `LECTURE_TOO_LARGE` trong Action (không trong controller). Bỏ trùng lặp: mọi nơi tạo `CourseItem` vẫn chỉ qua `AppendCourseItem`.

- [ ] **Step 4: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/Assessment tests/Architecture`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A app routes tests
git commit -m "feat: add chapters and lectures to a course"
```

### Task 5: Đọc, sửa bài giảng; sửa tên mục; gỡ mục theo loại

**Files:**
- Create: `app/Modules/CourseContent/Actions/UpdateLearningContent.php`, `Http/Controllers/GetLearningContentController.php`, `Http/Controllers/UpdateLearningContentController.php`, `Http/Requests/ManageLearningContentRequest.php`, `Http/Resources/LearningContentResource.php`, `Queries/LearningContentDetailQuery.php`, `Data/LearningContentDetail.php`
- Modify: `app/Modules/CourseContent/Actions/UpdateCourseItem.php`, `Actions/RemoveCourseItem.php`, `app/Modules/Assessment/Actions/ArchiveCourseAssessment.php`, `routes/api.php` (thêm `'content'` vào danh sách `Route::pattern` UUID, route mới), `tests/Feature/Assessment/ArchiveCourseAssessmentTest.php`, `tests/Feature/CourseContent/RemoveCourseItemTest.php`
- Test: `tests/Feature/CourseContent/LearningContentTest.php`

**Interfaces:**
- Consumes: Task 3 (`LectureHtmlSanitizer`), Task 4 (`LearningContentPolicy`).
- Produces:
  - `GET /contents/{content}` → 200 `LearningContentResource`: `{id, type, course_id, title, body_html, attachments: [{id, file_name, media_type, size_bytes, download_url}]}`; chỉ người `manage` được xem (403/404 như `CoursePolicy`: người không có quyền xem khóa → 404 `DenialMessage::NOT_FOUND`, người xem được khóa nhưng không quản lý → 403). `LearningContentDetailQuery::handle(LearningContent): LearningContentDetail` (readonly DTO ở `Data/`) nạp tệp đính kèm và ký URL bằng `File\Actions\IssueDownloadUrl` (tiêm qua constructor); `body_html` trả về đã làm sạch lại (làm sạch lúc hiển thị) và ảnh `data-attachment-id` có `src` ký (Task 8 hoàn thiện phần ảnh; ở task này `attachments` có thể rỗng).
  - `PATCH /contents/{content}` body `{title?: string(1..255), body_html?: string}` → 200 `LearningContentResource`; `UpdateLearningContent::handle(LearningContent $content, array $data): LearningContent` làm sạch HTML, kiểm `LECTURE_TOO_LARGE`, chỉ cho `lecture` sửa `body_html` (loại khác gửi `body_html` → 422), đồng bộ `course_items.title` khi đổi `title` (cùng transaction). Nội dung của khóa `archived`: sửa vẫn được (giống `PATCH /courses`), ghi vào test.
  - `UpdateCourseItem::handle(CourseItem, array)`: khi `title` đổi và mục là bài giảng/tài liệu/video thì cập nhật luôn `learning_contents.title`; mục `assessment`/`chapter` giữ như cũ.
  - `RemoveCourseItem::handle(CourseItem, User)` rẽ nhánh theo `type`: `assessment` → như hiện nay (`ArchiveCourseAssessment`); `chapter` → chỉ xóa mục; `lecture|document|video` → xóa mục rồi xóa `LearningContent` (bảng tiến độ chưa có; lát 4 thêm nhánh lưu trữ khi đã có người học, ghi chú vào docblock). Luôn đánh số lại 1..n trong cùng transaction.
  - `ArchiveCourseAssessment::handle(Assessment, User, string $courseId)`: ném `InvalidArgumentException`-loại lỗi lập trình (không phải DomainException) khi `$assessment->course_id !== $courseId` (B-097).

- [ ] **Step 1: Viết test thất bại** `LearningContentTest`: chủ khóa `GET` đọc bài giảng của mình (đủ tập key của Resource, so sánh toàn bộ tập key); Giáo viên khác khóa → 403 hoặc 404 đúng theo quyền xem khóa; Học sinh là học viên → 403 (middleware `role:`); `PATCH` đổi `title` thì `course_items.title` đổi theo; `PATCH body_html` chứa XSS bị làm sạch (kiểm trong DB và response); `PATCH body_html` trên nội dung không phải `lecture` → 422; `LECTURE_TOO_LARGE`. `RemoveCourseItemTest` thêm: gỡ chương không đụng bài đánh giá; gỡ bài giảng xóa cả `LearningContent`, vị trí còn lại liên tục; gỡ bài đánh giá vẫn lưu trữ bài + audit (ca cũ giữ nguyên); khóa có 4 mục hỗn hợp gỡ mục giữa. `ArchiveCourseAssessmentTest` thêm ca `courseId` không khớp.

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter="LearningContentTest|RemoveCourseItemTest|ArchiveCourseAssessmentTest"`
Expected: FAIL ở các ca mới.

- [ ] **Step 3: Implement** theo Interfaces (controller mỏng; Resource không tự query: dữ liệu do `LearningContentDetailQuery` nạp sẵn).

- [ ] **Step 4: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/Assessment tests/Architecture`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A app routes tests
git commit -m "feat: read and edit lectures, remove course items by type"
```

### Task 6: Sắp xếp lại mục khóa

**Files:**
- Create: `app/Modules/CourseContent/Actions/ReorderCourseItems.php`, `Http/Controllers/ReorderCourseItemsController.php`, `Http/Requests/ReorderCourseItemsRequest.php`, `Exceptions/InvalidItemOrder.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/CourseContent/ReorderCourseItemsTest.php`

**Interfaces:**
- Produces: `PUT /courses/{course}/items/order` body `{item_ids: list<uuid>}` → 200 `CourseItemResource::collection` theo thứ tự mới; `ReorderCourseItems::handle(Course $course, array $itemIds): Collection` (khóa các dòng mục `lockForUpdate`, đặt `position` 1..n theo thứ tự gửi lên). `InvalidItemOrder` (422, mã `INVALID_ITEM_ORDER`) khi danh sách thiếu mục, thừa mục không thuộc khóa, hoặc trùng id. Quyền: `CoursePolicy::manage`; khóa lưu trữ → `COURSE_ARCHIVED`. Thứ tự kiểm: quyền → validate định dạng (422 `VALIDATION_FAILED`) → `INVALID_ITEM_ORDER` → `COURSE_ARCHIVED`.

- [ ] **Step 1: Viết test thất bại**: đảo thứ tự bốn mục hỗn hợp (chương, bài giảng, tài liệu giả lập bằng factory, bài đánh giá) → 200 và `position` mới; danh sách thiếu một mục → 422 `INVALID_ITEM_ORDER` và thứ tự cũ giữ nguyên; thừa mục của khóa khác (dữ liệu thật, mục của khóa của Giáo viên khác) → 422; trùng id → 422; Giáo viên khác → 403; khóa `archived` → 422 `COURSE_ARCHIVED`; `item_ids` không phải uuid → 422 `VALIDATION_FAILED`.

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter=ReorderCourseItemsTest`
Expected: FAIL.

- [ ] **Step 3: Implement** theo Interfaces; giữ nguyên `PATCH /courses/{course}/items/{itemId}` (đổi `position` một mục) như hiện có.

- [ ] **Step 4: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Architecture`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A app routes tests
git commit -m "feat: reorder course items in one request"
```

### Task 7: Tài liệu của khóa (tệp)

**Files:**
- Create: `app/Modules/File/Actions/SyncContentAttachments.php`, `app/Modules/CourseContent/Actions/AddCourseDocument.php`
- Modify: `config/attachments.php`, `app/Modules/File/Http/Requests/UploadAttachmentRequest.php`, `app/Modules/File/Policies/AttachmentPolicy.php`, `app/Modules/CourseContent/Actions/CreateCourseItem.php`, `Http/Requests/CreateCourseItemRequest.php`, `Queries/LearningContentDetailQuery.php`, `tests/Architecture/ArchitectureTest.php` (nếu cần), `piclass-infra/local/docker/nginx/default.conf` và `piclass-infra/local/docker/api/php.ini` (repo khác, xem Step 6)
- Test: `tests/Feature/CourseContent/CourseDocumentTest.php`, `tests/Feature/File/ContentAttachmentAccessTest.php`

**Interfaces:**
- Consumes: Task 4 (`CreateCourseItem`, `LearningContentPolicy`).
- Produces:
  - Config `attachments.course_document` = `max_size_kb` 51200 (env `ATTACHMENTS_COURSE_DOCUMENT_MAX_SIZE_KB`), `mime_types` PDF, Word (`application/msword`, `.docx`), PowerPoint (`application/vnd.ms-powerpoint`, `.pptx` `application/vnd.openxmlformats-officedocument.presentationml.presentation`); `attachments.course_image` = `max_size_kb` 5120 (env `ATTACHMENTS_COURSE_IMAGE_MAX_SIZE_KB`), `mime_types` `image/jpeg`, `image/png`.
  - `POST /attachments` nhận thêm trường tùy chọn `purpose` (`course_document` | `course_image`): có thì kiểm cỡ và MIME theo mục config tương ứng (MIME đọc từ nội dung tệp bằng `getMimeType()`), không có thì hành vi cũ không đổi.
  - `File\Actions\SyncContentAttachments::handle(LearningContent $content, array $attachmentIds, AttachmentLinkVisibility $visibility): void` — trong một transaction, khóa các dòng `attachments` liên quan (`lock('for no key update')`), thêm `AttachmentLink` còn thiếu (`learning_content_id`, `visibility`) và xóa link `learning_content_id` của nội dung không còn trong danh sách; không xóa `Attachment`.
  - `AddCourseDocument::handle(Course $course, User $actor, string $title, string $attachmentId): CourseItem` (tạo `LearningContent` `document`, `body_html` null, `SyncContentAttachments` với `[$attachmentId]` và `AttachmentLinkVisibility::Learner`, rồi `AppendCourseItem` `content_id`). `CreateCourseItemRequest` với `type=document` thêm `attachment_id` (uuid, tồn tại): người upload là chính actor hoặc actor là Admin → nếu không 403 `Response::deny` như `AttachmentPolicy::link`; tệp chưa gắn vào nội dung nào khác; cỡ và MIME theo `attachments.course_document` → vi phạm thì 422 `ATTACHMENT_NOT_ALLOWED` (tạo biến thể message theo giới hạn tài liệu: "File phải nhỏ hơn 50 MB và là PDF, Word hoặc PowerPoint.", thêm constructor/named constructor vào `AttachmentNotAllowed`, giữ nguyên message cũ cho bài làm).
  - `AttachmentPolicy::view`: link có `learning_content_id` xem được khi người xem `manage` nội dung đó (`Gate::forUser($user)->allows('manage', $content)`); mọi vai trò khác 404 như hiện nay (học sinh đọc ở lát 4).
  - `LearningContentDetailQuery` (Task 5) trả `attachments` của `document` (một tệp) kèm `download_url` ký 10 phút.
- Hạ tầng: 50 MB vượt giới hạn local hiện tại (`nginx client_max_body_size 20m`, `php.ini upload_max_filesize 20M`, `post_max_size 21M`).

- [ ] **Step 1: Viết test thất bại**: `CourseDocumentTest` — Giáo viên upload PDF 1 MB với `purpose=course_document` (201), rồi `POST /courses/{id}/items` `{type: document, title, attachment_id}` → 201 với `content_type=document`; PDF giả (đuôi `.pdf` nhưng nội dung ảnh) → 422 ở bước upload; tệp 51 MB với `purpose=course_document` → 422, tệp 49 MB OK (dùng `UploadedFile::fake()->create(..., kilobytes)`); tệp `.exe` → 422; `attachment_id` do Giáo viên khác upload → 403; gắn cùng một tệp hai lần → 422; `GET /contents/{id}` trả `attachments[0].download_url`; Giáo viên khác khóa không `GET` được. `ContentAttachmentAccessTest` — `GET /attachments/{id}` của tệp tài liệu: chủ khóa và Admin 200; Giáo viên khác 404; Học sinh là học viên 404 (lát 4 mở); Phụ huynh 404.

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter="CourseDocumentTest|ContentAttachmentAccessTest"`
Expected: FAIL.

- [ ] **Step 3: Implement** theo Interfaces.

- [ ] **Step 4: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/File tests/Architecture`
Expected: PASS (mọi test `tests/Feature/File` cũ xanh không sửa).

- [ ] **Step 5: Commit** (repo `piclass-api`)

```bash
git add -A app config routes tests
git commit -m "feat: course documents backed by attachments"
```

- [ ] **Step 6: Nâng giới hạn tải của hạ tầng local** (repo `piclass-infra`, nhánh riêng từ `origin/main`, commit riêng): trong `local/docker/nginx/default.conf` `client_max_body_size` thành `60m`; trong `local/docker/api/php.ini` `upload_max_filesize = 55M`, `post_max_size = 56M`. Tìm mọi cấu hình khác trong `piclass-infra` đặt các giá trị này (`grep -rn "client_max_body_size\|upload_max_filesize\|post_max_size" .`) và nâng như nhau. Không có test; xác nhận bằng cách khởi động lại container (`docker compose up -d --build api nginx`) rồi upload thật một tệp 30 MB qua `curl -F` tới `/api/v1/attachments` (có `purpose=course_document`) và ghi kết quả vào báo cáo. Nếu môi trường production có cấu hình riêng ngoài repo này, nêu rõ trong báo cáo (việc của user).

### Task 8: Ảnh trong bài giảng

**Files:**
- Modify: `app/Modules/CourseContent/Actions/AddCourseLecture.php`, `Actions/UpdateLearningContent.php`, `Queries/LearningContentDetailQuery.php`, `Policies/LearningContentPolicy.php` (nếu cần), `app/Modules/File/Policies/AttachmentPolicy.php`
- Create: `app/Modules/CourseContent/Exceptions/LectureImageNotAllowed.php`
- Test: `tests/Feature/CourseContent/LectureImagesTest.php`

**Interfaces:**
- Consumes: Task 3 (`LectureImages`), Task 7 (`SyncContentAttachments`, `attachments.course_image`).
- Produces: khi tạo/sửa bài giảng, `LectureImages::ids($sanitizedHtml)` cho danh sách ảnh tham chiếu; mỗi id phải là `Attachment` do actor (hoặc Admin) upload hoặc đã gắn sẵn vào chính nội dung này, `media_type` JPEG/PNG, cỡ ≤ `attachments.course_image.max_size_kb`; vi phạm → 422 `LECTURE_IMAGE_NOT_ALLOWED` (CourseContent), không lưu gì. Hợp lệ thì gọi `SyncContentAttachments` với danh sách ảnh (`AttachmentLinkVisibility::Learner`), nên ảnh bỏ khỏi bài thì mất link. `LearningContentDetailQuery` ký URL cho từng ảnh và gọi `LectureImages::withUrls` trước khi trả `body_html`; `attachments` của `lecture` liệt kê các ảnh. Ảnh dùng được qua `GET /attachments/{id}` theo quyền Task 7.

- [ ] **Step 1: Viết test thất bại**: chủ khóa upload ảnh PNG (`purpose=course_image`), tạo bài giảng chứa `<img data-attachment-id="{id}">` → 201, `AttachmentLink` có `learning_content_id`; `GET` trả `body_html` với `src` có chữ ký và `attachments` chứa ảnh; sửa bài bỏ ảnh → link bị xóa, `Attachment` còn; ảnh do Giáo viên khác upload → 422 `LECTURE_IMAGE_NOT_ALLOWED` và không có `LearningContent` mới; `attachment_id` không tồn tại hoặc không phải uuid trong `data-attachment-id` → bị bỏ qua bởi `LectureImages::ids` (không lỗi, thẻ img bị loại khi trả); ảnh `.gif`/6 MB → 422; hai ảnh trùng id chỉ tạo một link.

- [ ] **Step 2: Chạy test để thấy FAIL**

Run: `docker compose exec api php artisan test --filter=LectureImagesTest`
Expected: FAIL.

- [ ] **Step 3: Implement** theo Interfaces.

- [ ] **Step 4: Chạy test**

Run: `docker compose exec api php artisan test tests/Feature/CourseContent tests/Feature/File tests/Architecture`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A app tests
git commit -m "feat: images inside lectures through attachments"
```

### Task 9: Kiểm tra cuối lát 1b

**Files:**
- Modify: `docs/` (repo docs, nhánh riêng, việc của controller, không phải implementer): `docs/architecture/error-codes.md` (`LECTURE_TOO_LARGE`, `LECTURE_IMAGE_NOT_ALLOWED`, `INVALID_ITEM_ORDER`), `docs/architecture/model-ownership.md` (`LearningContent`), `docs/STATE.md`, `docs/BACKLOG.md` (xóa phần `B-097` đã làm; thêm dòng cho việc dọn `Attachment` mồ côi khi ảnh/tài liệu bị gỡ)

- [ ] **Step 1: Chạy toàn bộ kiểm tra chất lượng** (trong `piclass-api`)

Run: `docker compose exec api php artisan test && docker compose exec api vendor/bin/pint --test && docker compose exec api composer analyse`
Expected: toàn bộ PASS, Pint không đổi file đã sửa, PHPStan không quá 33 lỗi (baseline).

- [ ] **Step 2: Export OpenAPI**

Run: `docker compose exec api php artisan scramble:export && docker compose exec api php artisan test tests/Feature/OpenApiSpecTest.php`
Expected: export không lỗi, test PASS; kiểm `storage/api-docs/openapi.json` có `POST /courses/{course}/items`, `PUT /courses/{course}/items/order`, `GET|PATCH /contents/{content}` và ba field mới của `course`.

- [ ] **Step 3: Kiểm tra ranh giới module**

Run: `grep -rn "AttachmentLink::\|CourseItem::\|LearningContent::" app/Modules | grep -v "CourseContent/\|File/"` và `grep -rln "App\\\\Modules\\\\CourseContent" app/Modules/Assessment`
Expected: dòng đầu không in gì; dòng sau chỉ in `CreateAssessmentController.php` (ngoại lệ `ARCH-39`).

- [ ] **Step 4: Cập nhật docs** như phần Files của task này (controller làm ở repo docs), push nhánh, không mở PR.

---

## Self-Review (đã chạy)

- **Phủ spec lát 1** (mục 9 dòng 1): chuyển `CourseItem` (1a, xong); migration `learning_contents` + cột cài đặt + mục nhiều loại + chương (Task 1, 2); CRUD bài giảng và tài liệu, làm sạch HTML, ảnh, tệp (Task 3, 4, 5, 7, 8); sắp xếp (Task 6). Spec 4.7 (xóa/lưu trữ) chỉ làm nhánh "xóa" vì bảng tiến độ chưa tồn tại; nhánh "lưu trữ khi đã có người học" ghi trong docblock Task 5, làm ở lát 4. Spec 4.8 (sao chép) và `ContentAudience` thuộc lát 2; video, điểm dừng thuộc lát 3.
- **Ngoài phạm vi có chủ ý:** học sinh/phụ huynh đọc nội dung, tiến độ, mở khóa tuần tự (lát 4); `ListCourseItemsQuery` giữ học viên chỉ thấy bài đánh giá đã mở cho tới lát đó.
- **Nhất quán kiểu:** `AppendCourseItem` (lát 1a) mở rộng ở Task 4 và dùng lại ở Task 7; `SyncContentAttachments` định nghĩa ở Task 7 và dùng ở Task 8; `LearningContentDetailQuery` tạo ở Task 5, mở rộng ở Task 7 và 8; `LearningContentPolicy::manage` tạo ở Task 4, dùng ở Task 5, 7.
- **Lệch spec đã xử lý:** chương là mục `chapter` phẳng, không dùng `parent_id` (spec mục 3 sửa cùng nhánh docs); ảnh ≤ 5 MB và tài liệu ≤ 50 MB cần config riêng `attachments.course_*` vì giới hạn chung hiện là 10 MB và không có định dạng PowerPoint.
- **Rủi ro biết trước:** tên CHECK constraint của `course_items.type` (kiểm `pg_constraint`); `ezyang/htmlpurifier` cần PHP 8.4 tương thích (kiểm khi `composer require`); giới hạn tải 50 MB cần nâng hạ tầng (Task 7 Step 6) và có thể còn cấu hình production ngoài repo.
