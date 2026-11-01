# BE lát 4d Giáo viên (TC-14 Tiến độ học sinh) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm cho BE ba thứ màn Tiến độ học sinh còn thiếu: lịch sử điểm đã công bố (G8), điểm danh theo học sinh (G3) và nhận xét học sinh một chiều.

**Architecture:** Tách phần chọn "kết quả đại diện mỗi bài" khỏi `StudentProgressQuery` thành Query dùng chung để Tiến độ và Lịch sử điểm không lệch nhau. Điểm danh theo học sinh nằm ở module Attendance (Query + Controller). Nhận xét học sinh do module ResultProgress sở hữu (Model `StudentNote`, một Action ghi, bốn endpoint đọc/ghi); không có sửa/xóa.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL, Scramble (OpenAPI).

**Spec:** `docs/requirements/decision-log.md` `DEC-LRN-031`; màn `docs/product/screens/teacher-van-hanh.md` mục TC-14; khoảng trống `docs/product/screens/api-gaps.md` G3, G8. Quy ước code: `piclass-api/CLAUDE.md`.

## Global Constraints

- Repo `piclass-api`, nhánh từ `origin/main` mới nhất (`git fetch origin` trước). Không push `main`.
- Controller mỏng single-action; Action một method `handle()`; Query đọc có phạm vi; Resource cho mọi response (R1–R3).
- Lỗi nghiệp vụ = `DomainException` kế thừa `App\Support\Exceptions\DomainException`; xem `docs/architecture/error-codes.md` trước khi thêm mã.
- Thêm Model mới: thêm dòng vào `docs/architecture/model-ownership.md` và `MODEL_OWNERS` trong `ArchitectureTest` cùng PR.
- Comment và mô tả test tiếng Việt; tên hàm, tên test method, `error.code`, commit message tiếng Anh.
- Phạm vi xem học sinh của Teacher dùng `StudentPolicy` (`viewProgress`/`view`, cùng `ClassScope::teacherCoversStudent`); ngoài phạm vi trả 404 `NOT_FOUND` "Request failed.".
- Nhận xét đã gửi **không sửa, không xóa** (`DEC-LRN-031` mục 3): không có route PUT/PATCH/DELETE. Chưa có thông báo (mục 4): không dispatch Event.
- Chưa làm: endpoint điểm danh và lịch sử điểm cho Phụ huynh/Học sinh (làm ở lát cổng của họ); biểu đồ; nhận xét hàng loạt (`B-083`).
- Sau khi đổi route/Resource: `docker compose exec api php artisan scramble:export` (repo `piclass-infra/local`), commit `storage/api-docs/openapi.json`.
- Không chạy test song song cùng DB test. Lệnh: `php artisan test`, `vendor/bin/pint`, `composer analyse` (PHPStan không thêm lỗi, không thêm vào baseline).

## Quyết định của plan (cần user xem)

- Chuyên cần (`attendance_rate`) = (có mặt + đi muộn) / tổng số bản ghi trong cửa sổ, làm tròn nguyên %, `null` nếu không có bản ghi. "Có phép" vẫn tính vào mẫu số. Đây là cách tính do plan chọn, spec chưa quy định.
- Cửa sổ điểm danh: tối đa `limit` buổi gần nhất (mặc định 10, tối đa 30) theo `occurrence_date` giảm dần; chỉ buổi không bị hủy.
- Giáo viên xem mọi nhận xét của em đó (kể cả do giáo viên khác viết) khi em thuộc phạm vi của mình. Plan chọn cách đơn giản này; muốn giới hạn theo lớp thì nói trước khi làm Task 6.
- `class_id` của nhận xét là tùy chọn. Có thì lớp đó phải là lớp em đang học; Teacher còn phải đang dạy lớp đó.
- Nhận xét thuộc module ResultProgress (cùng nhóm "tiến độ học tập" với `Result`).

## Review Focus

- Em ngoài phạm vi (Teacher không dạy, Parent không phải cha mẹ) gọi bất kỳ endpoint mới nào: 404, không lộ sự tồn tại (test ở Task 2, 3, 5, 6).
- Em chưa có bài công bố / chưa có buổi điểm danh: mảng rỗng, `attendance_rate` = `null`, không lỗi 500 (Task 2, 3).
- Nhiều lượt làm cùng một bài chỉ ra một dòng trong lịch sử điểm; bài hủy và bài bị vô hiệu không xuất hiện (Task 1, 2).
- Nhận xét nội dung rỗng, toàn khoảng trắng hoặc quá 2000 ký tự: 422, không lưu (Task 5).
- `class_id` không phải lớp của em hoặc Teacher không dạy: lỗi 422 `STUDENT_NOTE_CLASS_INVALID` (Task 5).
- Gửi PUT/PATCH/DELETE lên nhận xét: không có route, trả 404/405 (Task 5).
- Buổi bị hủy không tính vào chuyên cần (Task 3).

---

### Task 1: Tách Query "kết quả đại diện" dùng chung

**Files:**
- Create: `app/Modules/ResultProgress/Queries/StudentRepresentativeResultsQuery.php`
- Modify: `app/Modules/ResultProgress/Queries/StudentProgressQuery.php`
- Test: các test sẵn có `tests/Feature/Result/ProgressTest.php`, `tests/Feature/ResultProgress/StudentProgressAuthorizationTest.php`, `tests/Feature/ResultProgress/ResultValueContractTest.php`

**Interfaces:**
- Produces: `StudentRepresentativeResultsQuery::handle(string $studentId, array $filters = []): Collection<int, Result>` trả các `Result` đã công bố, mỗi `StudentAssessment` đúng một dòng (theo `attempt_score_policy`: highest lấy `percentage` cao nhất, latest lấy `attempt_number` lớn nhất), đã áp bộ lọc `class_id`, `course_id`, `subject_id`, `from`, `to`; đã eager load `submission.attempt.studentAssessment.assignedVersion`, `.sourceClass`, `.sourceCourse`. `filters` giữ đúng ý nghĩa như `StudentProgressQuery` hiện tại. Kèm `StudentRepresentativeResultsQuery::subjectIdOf(Result $r): ?string` (môn resolve qua `sourceClass` rồi `sourceCourse`, `null` = nhóm "Khác").
- Produces: `StudentProgressQuery::handle(...)` giữ nguyên chữ ký và payload.

- [ ] **Step 1: Chạy ba test nền để chắc đang xanh**

Run: `php artisan test tests/Feature/Result/ProgressTest.php tests/Feature/ResultProgress/StudentProgressAuthorizationTest.php tests/Feature/ResultProgress/ResultValueContractTest.php`
Expected: PASS (đây là lưới an toàn cho bước refactor).

- [ ] **Step 2: Viết test cho Query mới** trong `tests/Feature/ResultProgress/StudentRepresentativeResultsQueryTest.php`: `test_two_attempts_of_one_assessment_return_one_result_by_policy` (policy highest trả lượt điểm cao nhất, latest trả lượt cuối), `test_unpublished_cancelled_and_invalidated_results_are_excluded`, `test_subject_filter_matches_class_then_course_subject`.

- [ ] **Step 3: Chạy để thấy FAIL** (`class not found`).

- [ ] **Step 4: Implement** `StudentRepresentativeResultsQuery` bằng cách chuyển nguyên logic dựng `$base`, lọc published, lọc môn và chọn đại diện từ `StudentProgressQuery`; không đổi hành vi. `pending_count` vẫn tính ở `StudentProgressQuery` (cần `$base` chưa lọc published), nên Query mới nhận thêm tham số `bool $onlyPublished` hoặc tách một method `baseQuery` công khai trả Builder, tùy cách ít trùng lặp hơn.

- [ ] **Step 5: Sửa `StudentProgressQuery`** gọi Query mới để lấy `$representatives`; giữ `groupBySubject`.

- [ ] **Step 6: Chạy lại ba test nền và test mới** → PASS toàn bộ.

- [ ] **Step 7: Commit** `refactor: extract representative results query from student progress`.

### Task 2: Lịch sử điểm đã công bố (G8)

**Files:**
- Create: `app/Modules/ResultProgress/Http/Controllers/ListStudentResultsController.php`, `.../Requests/ListStudentResultsRequest.php`, `.../Resources/StudentResultHistoryResource.php`
- Modify: `routes/api.php` (cạnh `GET /students/{student}/progress`, cùng nhóm `role:admin,teacher`)
- Test: `tests/Feature/ResultProgress/StudentResultHistoryTest.php`

**Interfaces:**
- Consumes: `StudentRepresentativeResultsQuery` (Task 1).
- Produces: `GET /api/v1/students/{student}/results?class_id&course_id&subject_id&from&to&limit` (limit mặc định 20, tối đa 100). Response `{"data":[{"result_id","assessment_id","title","class_id","subject_id","subject_name","score","max_score","percentage","received_at"}]}` sắp theo `received_at` giảm dần. `assessment_id` để Giáo viên bấm sang chi tiết bài (TC-11/13). Authorize bằng `Gate::inspect('viewProgress', $this->route('student'))`.

- [ ] **Step 1: Viết test (FAIL trước)**: `test_teacher_sees_published_results_newest_first_with_expected_keys`, `test_student_with_no_published_result_gets_empty_data`, `test_limit_and_filters_apply`, `test_teacher_not_covering_student_gets_404_not_found`, `test_parent_and_student_roles_are_forbidden` (assert `error.code` và message theo `StudentProgressAuthorizationTest`).
- [ ] **Step 2: Chạy test, thấy FAIL** (404 route).
- [ ] **Step 3: Implement** Request (`rules()` cho 5 bộ lọc + `limit`, authorize như trên), Controller (<= 10 dòng), Resource, và route.
- [ ] **Step 4: Chạy test, PASS.** Chạy thêm ba test nền của Task 1 vẫn PASS.
- [ ] **Step 5: Commit** `feat: add student published results history endpoint`.

### Task 3: Điểm danh theo học sinh (G3)

**Files:**
- Create: `app/Modules/Attendance/Queries/StudentAttendanceQuery.php`, `.../Http/Controllers/GetStudentAttendanceController.php`, `.../Http/Requests/GetStudentAttendanceRequest.php`, `.../Http/Resources/StudentAttendanceResource.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Attendance/StudentAttendanceTest.php`

**Interfaces:**
- Produces: `StudentAttendanceQuery::handle(string $studentId, ?string $classId, int $limit): array{records: Collection, counts: array<string,int>, rate: ?int}` (`records` là các `AttendanceRecord` đã eager load `lessonSession.schoolClass`, sắp `occurrence_date` giảm dần, bỏ buổi `LessonSessionStatus::Cancelled`).
- Produces: `GET /api/v1/students/{student}/attendance?class_id&limit` (limit mặc định 10, tối đa 30), route nhóm `role:admin,teacher`, authorize `Gate::inspect('view', $this->route('student'))`. Response phẳng như `ProgressResource`: `{"attendance_rate":int|null,"present_count","late_count","absent_count","excused_count","records":[{"lesson_session_id","class_id","class_name","occurrence_date","status"}]}`. `attendance_rate` theo mục "Quyết định của plan".

- [ ] **Step 1: Viết test (FAIL trước)**: `test_returns_last_ten_records_newest_first_with_counts_and_rate` (tạo 12 buổi, assert 10 dòng, thứ tự, đếm, rate), `test_cancelled_sessions_are_excluded`, `test_no_records_gives_zero_counts_empty_records_and_null_rate`, `test_class_filter_limits_records_to_that_class`, `test_teacher_not_covering_student_gets_404_not_found`, `test_parent_role_is_forbidden`.
- [ ] **Step 2: Chạy, thấy FAIL.**
- [ ] **Step 3: Implement** Query (đọc `AttendanceRecord` join `lesson_sessions`, tính rate trong Query hoặc một hàm thuần ở `Domain/` nếu có logic), Request, Controller, Resource (bật `JSON_PRESERVE_ZERO_FRACTION` theo mẫu `ProgressResource` nếu cần giữ kiểu số), route.
- [ ] **Step 4: Chạy test, PASS.**
- [ ] **Step 5: Commit** `feat: add student attendance endpoint`.

### Task 4: Model, migration, factory `StudentNote`

**Files:**
- Create: `database/migrations/2026_11_02_000001_create_student_notes_table.php`, `app/Models/StudentNote.php`, `database/factories/StudentNoteFactory.php`
- Modify: `docs/architecture/model-ownership.md` (repo docs, làm ở Task 7), `tests/.../ArchitectureTest.php` (`MODEL_OWNERS` thêm `StudentNote => ResultProgress`)
- Test: `tests/Feature/Models/StudentNoteTest.php`

**Interfaces:**
- Produces: bảng `student_notes` (`id` uuid, `student_id` FK `students` cascade, `class_id` FK `classes` nullable `nullOnDelete`, `author_user_id` FK `users`, `content` text, timestamps; index `(student_id, created_at)`). Model `App\Models\StudentNote` (`HasUuids`, `HasFactory`, `SerializesAppTime`), fillable `student_id, class_id, author_user_id, content`, quan hệ `student()`, `schoolClass()`, `author()` (User).

- [ ] **Step 1: Viết test (FAIL trước)**: `test_factory_creates_note_with_relations`, và để `ArchitectureTest` bắt thiếu owner (`test_every_model_has_an_owner_and_the_map_is_complete`).
- [ ] **Step 2: Chạy test, FAIL.**
- [ ] **Step 3: Implement** migration, Model, Factory; thêm vào `MODEL_OWNERS`.
- [ ] **Step 4: Chạy `php artisan test tests/Feature/Models/StudentNoteTest.php` và `ArchitectureTest`**, PASS.
- [ ] **Step 5: Commit** `feat: add student notes table and model`.

### Task 5: Gửi nhận xét học sinh

**Files:**
- Create: `app/Modules/ResultProgress/Actions/CreateStudentNote.php`, `.../Exceptions/StudentNoteClassInvalid.php`, `.../Http/Controllers/CreateStudentNoteController.php`, `.../Http/Requests/CreateStudentNoteRequest.php`, `.../Http/Resources/StudentNoteResource.php`
- Modify: `routes/api.php`, `docs/architecture/error-codes.md` (repo docs)
- Test: `tests/Feature/ResultProgress/CreateStudentNoteTest.php`

**Interfaces:**
- Produces: `CreateStudentNote::handle(User $actor, Student $student, string $content, ?string $classId): StudentNote`. Có `classId` thì phải thuộc `$student->activeClassIds()`; nếu actor là Teacher (không phải Admin) còn phải thuộc `$actor->teacherProfile->activeClassIds()`; sai thì `throw StudentNoteClassInvalid` (HTTP 422, code `STUDENT_NOTE_CLASS_INVALID`). Kiểm tra mã trong `error-codes.md` trước, dùng lại mã cũ nếu có sẵn điều kiện tương đương.
- Produces: `POST /api/v1/students/{student}/notes` body `{content: string (required, trim, 1–2000 ký tự), class_id?: uuid}` → 201 `{"data":{"id","student_id","class_id","class_name","content","author":{"id","name"},"created_at"}}`. Route nhóm `role:admin,teacher`; authorize `Gate::inspect('viewProgress', $this->route('student'))`. Tên tác giả lấy từ `personProfile->full_name`.

- [ ] **Step 1: Viết test (FAIL trước)**: `test_teacher_creates_note_for_covered_student_returns_201_and_saves_author`, `test_admin_can_create_note`, `test_content_blank_whitespace_or_over_2000_returns_422_and_saves_nothing`, `test_class_id_not_student_class_returns_422_student_note_class_invalid`, `test_class_id_not_teachers_class_returns_422`, `test_teacher_not_covering_student_gets_404_not_found`, `test_parent_and_student_roles_are_forbidden`, `test_note_has_no_update_or_delete_route` (PUT/PATCH/DELETE `/api/v1/notes/{id}` hoặc `/students/{id}/notes/{id}` không tồn tại: assert 404 hoặc 405).
- [ ] **Step 2: Chạy, thấy FAIL.**
- [ ] **Step 3: Implement** Action (không audit, không Event theo Global Constraints), Exception, Request (trim nội dung bằng `prepareForValidation`), Controller, Resource, route; thêm mã lỗi vào `error-codes.md`.
- [ ] **Step 4: Chạy test, PASS.**
- [ ] **Step 5: Commit** `feat: add create student note endpoint`.

### Task 6: Đọc nhận xét (Giáo viên, Phụ huynh, Học sinh)

**Files:**
- Create: `app/Modules/ResultProgress/Queries/StudentNotesQuery.php`, ba controller `ListStudentNotesController`, `ListChildNotesController`, `ListMyNotesController`, ba request tương ứng (authorize giống `GetStudentProgressRequest`, `GetChildProgressRequest`, `GetMyProgressRequest`)
- Modify: `routes/api.php`
- Test: `tests/Feature/ResultProgress/ListStudentNotesTest.php`

**Interfaces:**
- Consumes: `StudentNoteResource` (Task 5).
- Produces: `StudentNotesQuery::handle(string $studentId): Collection<int, StudentNote>` (mới nhất trước, eager load `author.personProfile`, `schoolClass`). Ba endpoint trả `{"data":[...]}` cùng cấu trúc phần tử như Task 5: `GET /api/v1/students/{student}/notes` (`role:admin,teacher`, `viewProgress`), `GET /api/v1/me/children/{studentId}/notes` (`role:parent`, `viewAsParent`, `whereUuid`), `GET /api/v1/me/notes` (`role:student`).

- [ ] **Step 1: Viết test (FAIL trước)**: `test_teacher_lists_notes_newest_first_including_other_teachers_notes`, `test_empty_list_when_no_notes`, `test_parent_sees_only_own_childs_notes_and_other_child_is_404`, `test_student_sees_only_own_notes`, `test_unlinked_parent_and_uncovered_teacher_get_404_not_found`.
- [ ] **Step 2: Chạy, thấy FAIL.**
- [ ] **Step 3: Implement** Query, ba Controller/Request, route.
- [ ] **Step 4: Chạy test, PASS.**
- [ ] **Step 5: Commit** `feat: add student notes read endpoints for teacher, parent and student`.

### Task 7: OpenAPI, tài liệu, kiểm tra toàn bộ

**Files:**
- Modify: `piclass-api/storage/api-docs/openapi.json` (export), `docs/architecture/model-ownership.md` (thêm dòng `StudentNote | ResultProgress | Nhận xét một chiều của giáo viên về học sinh; đã gửi không sửa/xóa (DEC-LRN-031).`), `docs/product/screens/api-gaps.md` (đánh dấu G3 và G8 đã xong phần Giáo viên, ghi rõ phần Phụ huynh/Học sinh chưa làm)

- [ ] **Step 1:** `docker compose exec api php artisan scramble:export` (từ `piclass-infra/local`), kiểm `git diff --stat` chỉ đổi `openapi.json` ở đúng các route mới.
- [ ] **Step 2:** `php artisan test` (cả bộ, không chạy song song), `vendor/bin/pint --test`, `composer analyse`. Expected: tất cả xanh, PHPStan không thêm lỗi.
- [ ] **Step 3:** Cập nhật hai file tài liệu ở repo docs như trên.
- [ ] **Step 4: Commit** `docs: export openapi and update ownership for student progress endpoints`.
- [ ] **Step 5:** Gọi reviewer độc lập cuối nhánh với `model: "opus"`, effort medium.

## Self-review

- Spec coverage: G8 → Task 1–2; G3 → Task 3; nhận xét một chiều, không sửa/xóa, chưa thông báo → Task 4–6; phụ huynh/học sinh đọc nhận xét → Task 6; `Nhắn phụ huynh` và "Cần chú ý" không thuộc BE.
- Nhất quán tên: `StudentRepresentativeResultsQuery` (Task 1) được Task 2 dùng; `StudentNoteResource` (Task 5) được Task 6 dùng.
