# Danh sách giáo viên + lớp đang dạy (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:test-driven-development to implement this plan task-by-task.

**Goal:** `GET /teachers` (danh sách hồ sơ giáo viên, kèm liên kết tài khoản và số lớp đang dạy) và `GET /teachers/{teacher}/classes` (lớp đang dạy của một giáo viên) cho màn AD-07/AD-07a.

**Spec:** `docs/superpowers/specs/2026-10-24-teacher-list-and-classes-api-design.md`

## Global Constraints

- Chỉ-admin (middleware `role:admin`), không Policy mới.
- Comment code tiếng Việt; tên class/hàm/test/`error.code` tiếng Anh. Commit message tiếng Anh, kết thúc `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.
- `last_login_at` xuất qua `AppTime::format` (R10), không gọi `toIso8601String` trực tiếp.
- Không đổi hành vi `GET /teachers/{id}`, `GET /classes/{id}/teachers`, `GET /users?role=teacher`.
- Branch mới từ `origin/main` (`git fetch origin` trước); không commit vào `main`.

## Review Focus

- `account` suy ra đúng 4 trạng thái (none/invited/active/inactive) — test riêng từng trạng thái.
- `active_classes_count` không đếm phân công đã `ended`.
- `GET /teachers/{teacher}/classes` chỉ trả phân công `active`, giáo viên không có lớp trả `data: []`.
- Vai trò khác admin gọi hai endpoint → 403.

---

### Task 1: `Teacher::user()` + `GET /teachers` (danh sách)

**Files:**
- Modify: `app/Models/Teacher.php` (thêm `user()`)
- Create: `app/Modules/UserManagement/Queries/ListTeachersQuery.php`, `app/Modules/UserManagement/Http/Requests/ListTeachersRequest.php`, `app/Modules/UserManagement/Http/Controllers/ListTeachersController.php`, `app/Modules/UserManagement/Http/Resources/TeacherListResource.php`
- Modify: `routes/api.php` (nhóm `role:admin`, cạnh `POST /teachers`)
- Test: `tests/Feature/UserManagement/ListTeachersTest.php`

**Interfaces:**
- Produces: `Teacher::user(): HasOne` (`hasOne(User::class, 'person_profile_id', 'person_profile_id')`); `ListTeachersQuery::handle(array $filters, int $perPage): LengthAwarePaginator`.

- [x] **Step 1: Viết test (fail)** trong `ListTeachersTest` (helper `actingAsAdmin()` theo mẫu `TeacherClassAssignmentTest`):
  - `test_lists_teachers_without_an_account`: tạo Teacher chưa có User → xuất hiện trong `data`, `account` là `null`.
  - `test_filters_by_account_state_none_invited_active_inactive`: 4 Teacher ứng với 4 trạng thái (không User / User active+last_login_at null / User active+last_login_at có giá trị / User inactive) → gọi lần lượt `?account=none|invited|active|inactive`, mỗi lần chỉ đúng 1 kết quả.
  - `test_counts_only_active_class_assignments`: Teacher có 1 phân công active + 1 đã `ended` → `active_classes_count` = 1.
  - `test_filters_by_has_classes`: `?has_classes=true` chỉ trả teacher có phân công active; `?has_classes=false` chỉ trả teacher không có.
  - `test_filters_by_name_and_status`: `?name=` khớp không phân biệt hoa thường một phần họ tên; `?status=inactive` chỉ trả hồ sơ `inactive`.
  - `test_non_admin_cannot_list_teachers`: vai trò `teacher` → 403.
- [x] **Step 2: Chạy test, xác nhận fail:** `php artisan test --filter=ListTeachersTest`.
- [x] **Step 3: Implement.**
  - `Teacher::user()`: `return $this->hasOne(User::class, 'person_profile_id', 'person_profile_id');`.
  - `ListTeachersQuery::handle`: `Teacher::query()->with(['personProfile', 'user'])->withCount(['classAssignments as active_classes_count' => fn ($q) => $q->where('status', MembershipStatus::Active->value)])`; filter `name` → `whereHas('personProfile', fn ($q) => $q->where('full_name', 'ilike', "%{$name}%"))`; `status` → `where('status', $status)`; `has_classes` → so `true`/`false` chuỗi, dùng `whereHas`/`whereDoesntHave('classAssignments', fn ($q) => $q->where('status', 'active'))`; `account`:
    - `none` → `whereDoesntHave('user')`
    - `invited` → `whereHas('user', fn ($q) => $q->where('status', 'active')->whereNull('last_login_at'))`
    - `active` → `whereHas('user', fn ($q) => $q->where('status', 'active')->whereNotNull('last_login_at'))`
    - `inactive` → `whereHas('user', fn ($q) => $q->where('status', 'inactive'))`
    `->paginate($perPage)`.
  - `ListTeachersRequest`: `authorize(): bool { return true; }` (docblock "chỉ-admin"), `filters()` đọc `name/status/account/has_classes`, `perPage()` mặc định 15 — giống `ListUsersRequest`.
  - `TeacherListResource` (dựng tay, `$wrap = null`, nhận `LengthAwarePaginator`): map mỗi `Teacher` → `{id, full_name: personProfile.full_name, specialty, email: personProfile.email, phone: personProfile.phone, status: status.value, account: user ? {login_identifier, status: user.status.value, last_login_at: AppTime::format(user.last_login_at)} : null, active_classes_count}`; `meta` giống `UserListResource`.
  - Route: `Route::get('/teachers', ListTeachersController::class);` trong nhóm `role:admin`, đặt trước `GET /teachers/{teacher}` để không bị route param nuốt (Laravel ưu tiên route khai báo trước nên khai `/teachers` trước `/teachers/{teacher}`).
- [x] **Step 4: Chạy test, xác nhận pass** cùng `--filter=UserManagement` và `--filter=ArchitectureTest`.
- [x] **Step 5: Commit** `feat: add a teacher profile list endpoint with account and class count`.

### Task 2: `GET /teachers/{teacher}/classes`

**Files:**
- Create: `app/Modules/UserManagement/Http/Controllers/GetTeacherClassesController.php`, `app/Modules/UserManagement/Http/Resources/TeacherClassesResource.php`
- Modify: `app/Models/SchoolClass.php` (thêm `subject()` — chưa có quan hệ này, cần cho `subject_name`), `routes/api.php` (nhóm `role:admin`, cạnh `GET /teachers/{teacher}`)
- Test: `tests/Feature/UserManagement/GetTeacherClassesTest.php`

**Interfaces:**
- Consumes: `Teacher::classAssignments()` (đã có).

- [x] **Step 1: Viết test (fail)** trong `GetTeacherClassesTest`:
  - `test_lists_the_active_classes_of_a_teacher`: giáo viên có 2 phân công active ở 2 lớp khác môn → `data` có 2 phần tử, đúng `class_name`/`subject_name`/`valid_from`.
  - `test_excludes_ended_assignments`: phân công `ended` không xuất hiện.
  - `test_returns_an_empty_list_for_a_teacher_without_classes`: `data: []`.
  - `test_non_admin_cannot_view`: vai trò `teacher` → 403.
  - `test_unknown_teacher_returns_404`.
- [x] **Step 2: Chạy test, xác nhận fail:** `php artisan test --filter=GetTeacherClassesTest`.
- [x] **Step 3: Implement.** `GetTeacherClassesController::__invoke(Teacher $teacher)`: `$teacher->classAssignments()->where('status', MembershipStatus::Active->value)->with('schoolClass.subject')->get()`. `TeacherClassesResource` map → `{class_id, class_name: schoolClass.name, subject_name: schoolClass.subject.name, valid_from, valid_to}`. Route `Route::get('/teachers/{teacher}/classes', GetTeacherClassesController::class);` ngay sau route `GET /teachers/{teacher}`, cùng nhóm `role:admin`, không FormRequest riêng (`authorize(): bool { return true; }` ở controller docblock, theo mẫu `GetTeacherController` — không cần FormRequest nếu không có rule, Controller nhận `Teacher $teacher` trực tiếp từ route binding).
- [x] **Step 4: Chạy test, xác nhận pass** cùng toàn bộ `php artisan test` và `vendor/bin/pint`/`composer analyse`.
- [x] **Step 5: Commit** `feat: add an endpoint listing a teacher's active class assignments`.
