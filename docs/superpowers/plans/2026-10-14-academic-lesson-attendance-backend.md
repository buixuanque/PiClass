# Plan triển khai Academic + Lesson + Attendance Backend

> **Dành cho agent thực thi:** REQUIRED SUB-SKILL: dùng `superpowers:subagent-driven-development` (khuyến nghị) hoặc `superpowers:executing-plans` để triển khai plan này theo từng task. Các step dùng cú pháp checkbox (`- [ ]`) để theo dõi tiến độ.

**Mục tiêu:** Triển khai endpoint cho Subject, Class (CRUD + auto status), Schedule/LessonSession (+ sinh session tự động), Attendance/AbsenceExplanation, và Course/CourseEnrollment theo spec đã duyệt, tiếp nối Plan 1 (Identity/Auth) và Plan 2 (User Management) đã xong.

**Kiến trúc:** REST API Laravel 11 dưới `/api/v1`, dùng lại Sanctum cookie session + middleware `EnsureUserIsActive`/`role:<codes>` (`EnsureRole`) đã có từ Plan 1/2. Không có code frontend — đó là Plan 3b.

**Tech Stack:** Laravel 11.31, PHP 8.2+, PostgreSQL 16, PHPUnit. Dev chạy qua Docker (`piclass-infra`).

**Spec:** `docs/superpowers/specs/2026-10-14-academic-lesson-attendance-api-design.md`.

**Vị trí code:** mọi file trong plan này nằm trong repo riêng `piclass-api/` (sibling dir của repo docs). Đường dẫn file ở dưới là tương đối so với gốc `piclass-api/`.

**Chạy lệnh (Docker, không chạy host-based):** mọi `php artisan ...` dưới đây thực thi qua:
```bash
cd piclass-infra/local && docker compose exec -u www-data api php artisan test --filter=TenTest
```
(xem `piclass-infra/local/Makefile`, target `test-api`/`migrate`). Để ngắn, các step phía dưới chỉ ghi lệnh `php artisan ...` thuần — áp dụng wrapper trên khi chạy thật. Lệnh `git`/`composer` chạy trực tiếp trong `piclass-api/` (repo riêng, không qua Docker).

## Global Constraints

- Base path API `/api/v1`; field JSON `snake_case`; response lỗi `{"error":{"code","message"}}` (đã có từ Plan 1, `bootstrap/app.php`, không sửa lại).
- Mọi entity nghiệp vụ dùng UUID PK (`HasUuids`).
- Permission gate: `EnsureUserIsActive` (đã có) → `role:<codes>` (đã có, `EnsureRole`) → scope cụ thể theo resource (kiểm tra inline trong controller, dùng `App\Support\ClassScope` cho phần dùng chung) → lifecycle/visibility. List và get-one dùng cùng scope; biết ID không cấp quyền (trả `404` chứ không `403` khi ngoài scope, theo đúng quy ước đã dùng ở `GetMyChildController` của Plan 2).
- Model PHP đặt tên tránh từ khóa dành riêng — `SchoolClass` (bảng `classes`, đã có từ Plan 2). Không có entity mới nào trong plan này trùng từ khóa PHP.
- `DEC-LRN-001`/spec §4.1: `Class.status` không phải field Admin set tay giữa `preparing`/`active` — tự động chuyển theo `TeacherClassAssignment` hiệu lực (Task 4 sửa 2 controller đã có từ Plan 2).
- `BR-CLASS-007`/spec §5.1: sửa Schedule (`PATCH`) chỉ ảnh hưởng LessonSession **chưa sinh ra sau thời điểm sửa**; session đã sinh (kể cả chưa diễn ra) giữ nguyên.
- `ARCH-13`/spec §5.1: tạo/sửa Schedule sinh ngay LessonSession cho 4 tuần kế tiếp (hoặc tới `effective_until` nếu gần hơn); Scheduler chạy job hàng ngày giữ cửa sổ 4 tuần tới luôn có sẵn.
- `DEC-SCH-002`/spec §5.2: conflict check giữa Schedule mới và Schedule khác của cùng Teacher (ở Class khác) chỉ trả `warnings`, không chặn tạo (`201` vẫn thành công).
- `DEC-LRN-004`/spec §6: hủy LessonSession đã có Attendance phải **giữ nguyên** bản ghi Attendance và set `review_required=true`, không xóa.
- `DEC-LRN-005`/`BR-CLASS-011`/spec §6: sửa LessonSession đã `completed` phải ghi `LessonSessionHistory` (`before_data`/`after_data`) và bắt buộc `reason`.
- `BR-ATT-004`/spec §7: bulk mark Attendance chỉ nhận Student có `ClassMember` hiệu lực tại thời điểm buổi học (`valid_from <= starts_at` và (`valid_to` null hoặc `> starts_at`)); request có student ngoài điều kiện này bị từ chối toàn bộ `422` (không mark một phần).
- `DEC-LRN-007`/spec §7: sửa Attendance hiện hành bắt buộc `reason`, ghi `AttendanceHistory` (`before_data`/`after_data`).
- `DEC-LRN-008`/spec §7.1: review `AbsenceExplanation` không tự đổi `AttendanceRecord`.
- `BR-COURSE-004`/spec §8: Teacher sở hữu Course không cần Admin duyệt; enroll/kết thúc CourseEnrollment cho **bất kỳ Student nào trong hệ thống**, không giới hạn Class mình phụ trách; bulk theo `class_id` cũng không giới hạn Class do Teacher đó phụ trách.
- Role code cố định `admin`/`teacher`/`student`/`parent` (seed từ Plan 1, `RoleSeeder`).
- **Không nằm trong scope plan này:** `ClassMember`/`TeacherClassAssignment` CRUD (đã xong ở Plan 2), Question Bank, Assessment, Submission/Grading, Result/Progress, Notification, File, `AuditLog` (chưa có bảng — mọi chỗ cần ghi audit trong plan này chỉ dừng ở `reason`/history riêng của entity, giống cách Plan 2 đã xử lý cho `ClassMember`).

## Review Focus

1. Hủy LessonSession đã có `AttendanceRecord` phải giữ nguyên bản ghi và set `review_required=true`, không được xóa Attendance theo cascade hay logic dọn dẹp ẩn — Task 8 (bổ sung vào `CancelLessonSessionController` đã tạo ở Task 6, sau khi `AttendanceRecord` tồn tại).
2. Sửa Schedule (`PATCH`) sau khi đã sinh LessonSession tương lai không được viết lại/xóa các LessonSession **đã sinh** (kể cả chưa diễn ra) — chỉ ảnh hưởng phần sinh ra sau thời điểm sửa — Task 7.
3. Bulk mark Attendance cho Student không có `ClassMember` hiệu lực tại thời điểm buổi học phải bị từ chối toàn bộ request (`422`), không được âm thầm bỏ qua student đó rồi mark phần còn lại — Task 8.
4. Conflict check khi tạo Schedule phải so khớp theo **cùng Teacher** ở **Class khác** (qua `TeacherClassAssignment` hiệu lực), không phải so trong cùng Class (sẽ không bao giờ có xung đột thật) — Task 5.
5. Enroll CourseEnrollment theo `class_id` (bulk) không được tạo bản ghi trùng (`active`) cho Student đã có `CourseEnrollment` hiệu lực với đúng Course đó — phải bỏ qua Student đã enrolled, không tạo thêm hàng hoặc lỗi `500` do vi phạm ràng buộc — Task 11.

---

### Task 1: Shared scope helper `App\Support\ClassScope` + `ParentProfile` scope methods

**Files:**
- Tạo: `app/Support/ClassScope.php`
- Sửa: `app/Models/ParentProfile.php` (thêm `activeChildrenIds()`, `activeChildrenClassIds()`)
- Tạo: `tests/Feature/Support/ClassScopeTest.php`

**Interfaces:**
- Produces: `App\Support\ClassScope::classIdsFor(User $user, ?string $activeRole): \Illuminate\Support\Collection` — dùng bởi Task 2 (Subject), Task 3 (Class), Task 5 (Schedule), Task 6 (LessonSession), Task 8 (Attendance), Task 9 (AbsenceExplanation).
- Produces: `ParentProfile::activeChildrenIds(): Collection` (danh sách `student_id` có `ParentStudentRelationship` hiệu lực), `ParentProfile::activeChildrenClassIds(): Collection` — dùng bởi `ClassScope` và trực tiếp bởi Task 10 (Attendance, Course list).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Support;

use App\Models\ClassMember;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Support\ClassScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_scope_returns_assigned_class_ids(): void
    {
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $class->id]);
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);

        $ids = ClassScope::classIdsFor($user, 'teacher');

        $this->assertTrue($ids->contains($class->id));
    }

    public function test_student_scope_returns_own_class_ids(): void
    {
        $student = Student::factory()->create();
        $class = SchoolClass::factory()->create();
        ClassMember::factory()->create(['student_id' => $student->id, 'class_id' => $class->id]);
        $user = User::factory()->create(['person_profile_id' => $student->person_profile_id]);

        $ids = ClassScope::classIdsFor($user, 'student');

        $this->assertTrue($ids->contains($class->id));
    }

    public function test_parent_scope_returns_childrens_class_ids(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        $class = SchoolClass::factory()->create();
        ClassMember::factory()->create(['student_id' => $student->id, 'class_id' => $class->id]);
        ParentStudentRelationship::factory()->create(['parent_id' => $parent->id, 'student_id' => $student->id]);
        $user = User::factory()->create(['person_profile_id' => $parent->person_profile_id]);

        $ids = ClassScope::classIdsFor($user, 'parent');

        $this->assertTrue($ids->contains($class->id));
    }

    public function test_parent_scope_ignores_unlinked_student(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        $class = SchoolClass::factory()->create();
        ClassMember::factory()->create(['student_id' => $student->id, 'class_id' => $class->id]);
        ParentStudentRelationship::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 'ended',
            'valid_to' => now()->subDay(),
        ]);
        $user = User::factory()->create(['person_profile_id' => $parent->person_profile_id]);

        $ids = ClassScope::classIdsFor($user, 'parent');

        $this->assertFalse($ids->contains($class->id));
    }

    public function test_admin_scope_returns_empty_collection(): void
    {
        $user = User::factory()->create();

        $ids = ClassScope::classIdsFor($user, 'admin');

        $this->assertTrue($ids->isEmpty());
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ClassScopeTest` → FAIL (class `App\Support\ClassScope` không tồn tại).

- [ ] **Step 3: Sửa `ParentProfile.php` — thêm 2 method**

Thêm vào class `ParentProfile` (sau `relationships()`):
```php
public function activeChildrenIds()
{
    return $this->relationships()
        ->where('status', 'active')
        ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
        ->pluck('student_id');
}

public function activeChildrenClassIds()
{
    return \App\Models\Student::whereIn('id', $this->activeChildrenIds())
        ->get()
        ->flatMap(fn ($student) => $student->activeClassIds())
        ->unique()
        ->values();
}
```

- [ ] **Step 4: Tạo `App\Support\ClassScope`**

`app/Support/ClassScope.php`:
```php
<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class ClassScope
{
    /**
     * Danh sách class_id mà actor hiện hành (theo active_role_code) được xem —
     * dùng chung cho Subject/Class/Schedule/LessonSession list endpoints.
     * Admin trả rỗng có chủ ý: caller tự biết Admin xem toàn bộ, không lọc theo scope này.
     */
    public static function classIdsFor(User $user, ?string $activeRole): Collection
    {
        return match ($activeRole) {
            'teacher' => $user->teacherProfile?->activeClassIds() ?? collect(),
            'student' => $user->studentProfile?->activeClassIds() ?? collect(),
            'parent' => $user->parentProfile?->activeChildrenClassIds() ?? collect(),
            default => collect(),
        };
    }
}
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=ClassScopeTest`
Kỳ vọng: PASS (5 test).

- [ ] **Step 6: Commit**

```bash
git add app tests
git commit -m "feat: add ClassScope helper and ParentProfile children-scope methods"
```

---

### Task 2: Subject CRUD (`/api/v1/subjects`)

**Files:**
- Tạo: `app/Http/Controllers/Api/Academic/ListSubjectsController.php`
- Tạo: `app/Http/Controllers/Api/Academic/CreateSubjectController.php`
- Tạo: `app/Http/Controllers/Api/Academic/UpdateSubjectController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Academic/SubjectTest.php`

**Interfaces:**
- Consumes: `App\Support\ClassScope::classIdsFor()` (Task 1).
- Produces: route `/api/v1/subjects` — không có resource nào ở task sau phụ thuộc Subject CRUD trực tiếp (chỉ dùng `subject_id` làm FK, đã tồn tại).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Academic;

use App\Models\ClassMember;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    private function actingAsStudent(Student $student): User
    {
        $user = User::factory()->create(['person_profile_id' => $student->person_profile_id]);
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        return $user;
    }

    public function test_admin_sees_every_subject(): void
    {
        $this->actingAsAdmin();
        Subject::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/subjects');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_student_only_sees_subjects_reachable_through_their_classes(): void
    {
        $student = Student::factory()->create();
        $inScope = Subject::factory()->create(['name' => 'In scope']);
        $outOfScope = Subject::factory()->create(['name' => 'Out of scope']);
        $class = SchoolClass::factory()->create(['subject_id' => $inScope->id]);
        ClassMember::factory()->create(['student_id' => $student->id, 'class_id' => $class->id]);
        $this->actingAsStudent($student);

        $response = $this->getJson('/api/v1/subjects');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('In scope'));
        $this->assertFalse($names->contains('Out of scope'));
    }

    public function test_admin_can_create_a_subject(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/subjects', ['name' => 'Toán', 'description' => 'Môn Toán']);

        $response->assertCreated();
        $this->assertDatabaseHas('subjects', ['name' => 'Toán', 'status' => 'active']);
    }

    public function test_non_admin_cannot_create_a_subject(): void
    {
        $student = Student::factory()->create();
        $this->actingAsStudent($student);

        $response = $this->postJson('/api/v1/subjects', ['name' => 'Toán']);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_a_subject(): void
    {
        $this->actingAsAdmin();
        $subject = Subject::factory()->create(['name' => 'Cũ']);

        $response = $this->patchJson("/api/v1/subjects/{$subject->id}", ['name' => 'Mới', 'status' => 'inactive']);

        $response->assertOk();
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'name' => 'Mới', 'status' => 'inactive']);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=SubjectTest` → FAIL (route chưa tồn tại).

- [ ] **Step 3: Tạo 3 controller**

`app/Http/Controllers/Api/Academic/ListSubjectsController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class ListSubjectsController extends Controller
{
    public function __invoke(Request $request)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole === 'admin') {
            $subjects = Subject::orderBy('name')->get();
        } else {
            $classIds = ClassScope::classIdsFor($request->user(), $activeRole);
            $subjectIds = SchoolClass::whereIn('id', $classIds)->pluck('subject_id')->unique();
            $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
        }

        return response()->json(['data' => $subjects->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'description' => $s->description,
            'status' => $s->status,
        ])->values()]);
    }
}
```

`app/Http/Controllers/Api/Academic/CreateSubjectController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class CreateSubjectController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $subject = Subject::create($data + ['status' => 'active']);

        return response()->json(['subject' => [
            'id' => $subject->id,
            'name' => $subject->name,
            'description' => $subject->description,
            'status' => $subject->status,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/Academic/UpdateSubjectController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class UpdateSubjectController extends Controller
{
    public function __invoke(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $subject->fill($data);
        if ($subject->isDirty('status') && $subject->status === 'inactive') {
            $subject->archived_at = now();
        }
        $subject->save();

        return response()->json(['subject' => [
            'id' => $subject->id,
            'name' => $subject->name,
            'description' => $subject->description,
            'status' => $subject->status,
        ]]);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong `routes/api.php`, thêm `'subject'` vào mảng `Route::pattern` hiện có:
```php
foreach (['user', 'roleId', 'personProfile', 'parentStudentRelationship', 'student', 'parent', 'teacher', 'class', 'teacherId', 'studentId', 'subject'] as $uuidParam) {
```

Thêm route mới — `GET` cho cả 4 role, `POST`/`PATCH` chỉ Admin. Trong block `Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(...)`, thêm nhóm mới (trước nhóm `role:admin` đã có):
```php
Route::middleware('role:admin,teacher,student,parent')->group(function () {
    Route::get('/subjects', \App\Http\Controllers\Api\Academic\ListSubjectsController::class);
});
```
Và trong nhóm `Route::middleware('role:admin')->group(...)` đã có, thêm 2 dòng:
```php
Route::post('/subjects', \App\Http\Controllers\Api\Academic\CreateSubjectController::class);
Route::patch('/subjects/{subject}', \App\Http\Controllers\Api\Academic\UpdateSubjectController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=SubjectTest`
Kỳ vọng: PASS (5 test).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add Subject CRUD endpoints"
```

---

### Task 3: Class CRUD (list/get/create/patch) — `/api/v1/classes`

**Files:**
- Tạo: `app/Http/Controllers/Api/Academic/ListClassesController.php`
- Tạo: `app/Http/Controllers/Api/Academic/GetClassController.php`
- Tạo: `app/Http/Controllers/Api/Academic/CreateClassController.php`
- Tạo: `app/Http/Controllers/Api/Academic/UpdateClassController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Academic/ClassTest.php`

**Interfaces:**
- Consumes: `App\Support\ClassScope::classIdsFor()` (Task 1).
- Không định nghĩa lại `/classes/{class}/members` hay `/classes/{class}/teachers` — giữ nguyên từ Plan 2.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Academic;

use App\Models\ClassMember;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    private function actingAsTeacher(Teacher $teacher): User
    {
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    private function actingAsStudent(Student $student): User
    {
        $user = User::factory()->create(['person_profile_id' => $student->person_profile_id]);
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        return $user;
    }

    public function test_admin_can_create_a_class(): void
    {
        $this->actingAsAdmin();
        $subject = Subject::factory()->create();

        $response = $this->postJson('/api/v1/classes', [
            'subject_id' => $subject->id,
            'name' => 'Lớp 10A1',
            'label' => 'Năm 2026',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('classes', ['name' => 'Lớp 10A1', 'status' => 'preparing']);
    }

    public function test_non_admin_cannot_create_a_class(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);
        $subject = Subject::factory()->create();

        $response = $this->postJson('/api/v1/classes', ['subject_id' => $subject->id, 'name' => 'X']);

        $response->assertStatus(403);
    }

    public function test_admin_sees_every_class(): void
    {
        $this->actingAsAdmin();
        SchoolClass::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/classes');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_student_only_sees_their_own_classes(): void
    {
        $student = Student::factory()->create();
        $myClass = SchoolClass::factory()->create();
        $otherClass = SchoolClass::factory()->create();
        ClassMember::factory()->create(['student_id' => $student->id, 'class_id' => $myClass->id]);
        $this->actingAsStudent($student);

        $response = $this->getJson('/api/v1/classes');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($myClass->id));
        $this->assertFalse($ids->contains($otherClass->id));
    }

    public function test_student_cannot_view_a_class_outside_their_scope(): void
    {
        $student = Student::factory()->create();
        $otherClass = SchoolClass::factory()->create();
        $this->actingAsStudent($student);

        $response = $this->getJson("/api/v1/classes/{$otherClass->id}");

        $response->assertStatus(404);
    }

    public function test_admin_can_update_a_class(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create(['name' => 'Cũ']);

        $response = $this->patchJson("/api/v1/classes/{$class->id}", ['name' => 'Mới']);

        $response->assertOk();
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'name' => 'Mới']);
    }

    public function test_admin_can_archive_a_class(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();

        $response = $this->patchJson("/api/v1/classes/{$class->id}", ['status' => 'archived']);

        $response->assertOk();
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'status' => 'archived']);
        $this->assertNotNull($class->fresh()->archived_at);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ClassTest` → FAIL.

- [ ] **Step 3: Tạo 4 controller**

`app/Http/Controllers/Api/Academic/ListClassesController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class ListClassesController extends Controller
{
    public function __invoke(Request $request)
    {
        $activeRole = $request->session()->get('active_role_code');

        $query = SchoolClass::orderBy('name');

        if ($activeRole !== 'admin') {
            $classIds = ClassScope::classIdsFor($request->user(), $activeRole);
            $query->whereIn('id', $classIds);
        }

        return response()->json(['data' => $query->get()->map(fn ($c) => [
            'id' => $c->id,
            'subject_id' => $c->subject_id,
            'name' => $c->name,
            'label' => $c->label,
            'description' => $c->description,
            'status' => $c->status,
        ])]);
    }
}
```

`app/Http/Controllers/Api/Academic/GetClassController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class GetClassController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole !== 'admin' && ! ClassScope::classIdsFor($request->user(), $activeRole)->contains($class->id)) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy lớp.'],
            ], 404);
        }

        return response()->json(['class' => [
            'id' => $class->id,
            'subject_id' => $class->subject_id,
            'name' => $class->name,
            'label' => $class->label,
            'description' => $class->description,
            'status' => $class->status,
        ]]);
    }
}
```

`app/Http/Controllers/Api/Academic/CreateClassController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class CreateClassController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'subject_id' => ['required', 'uuid', 'exists:subjects,id'],
            'name' => ['required', 'string'],
            'label' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $class = SchoolClass::create($data + ['status' => 'preparing']);

        return response()->json(['class' => [
            'id' => $class->id,
            'subject_id' => $class->subject_id,
            'name' => $class->name,
            'label' => $class->label,
            'status' => $class->status,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/Academic/UpdateClassController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class UpdateClassController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'label' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:archived'],
        ]);

        // "status" chỉ nhận "archived" ở đây — preparing/active là tự động
        // (xem Task 4), Admin không tự set tay 2 giá trị đó.
        $class->fill($data);
        if ($class->isDirty('status') && $class->status === 'archived') {
            $class->archived_at = now();
        }
        $class->save();

        return response()->json(['class' => [
            'id' => $class->id,
            'name' => $class->name,
            'label' => $class->label,
            'description' => $class->description,
            'status' => $class->status,
        ]]);
    }
}
```

- [ ] **Step 4: Thêm route**

Thêm nhóm mới trong block `role:admin,teacher,student,parent` (tạo ở Task 2 Step 4, dùng lại):
```php
Route::middleware('role:admin,teacher,student,parent')->group(function () {
    Route::get('/subjects', \App\Http\Controllers\Api\Academic\ListSubjectsController::class);
    Route::get('/classes', \App\Http\Controllers\Api\Academic\ListClassesController::class);
    Route::get('/classes/{class}', \App\Http\Controllers\Api\Academic\GetClassController::class);
});
```
Và thêm vào nhóm `role:admin` đã có:
```php
Route::post('/classes', \App\Http\Controllers\Api\Academic\CreateClassController::class);
Route::patch('/classes/{class}', \App\Http\Controllers\Api\Academic\UpdateClassController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=ClassTest`
Kỳ vọng: PASS (7 test).

- [ ] **Step 6: Chạy lại test Plan 2 liên quan Class để chắc route không vỡ**

Chạy: `php artisan test --filter=ClassMemberTest` và `php artisan test --filter=TeacherClassAssignmentTest`
Kỳ vọng: PASS.

- [ ] **Step 7: Commit**

```bash
git add app routes
git commit -m "feat: add Class CRUD endpoints (list/get/create/update)"
```

---

### Task 4: Class status tự động chuyển `preparing ↔ active`

**Files:**
- Sửa: `app/Http/Controllers/Api/UserManagement/AssignTeacherToClassController.php`
- Sửa: `app/Http/Controllers/Api/UserManagement/EndTeacherClassAssignmentController.php`
- Tạo: `tests/Feature/Academic/ClassStatusTransitionTest.php`

**Interfaces:**
- Không thêm interface mới — chỉ thêm side-effect vào 2 controller đã có từ Plan 2.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Academic;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    public function test_assigning_the_first_teacher_activates_a_preparing_class(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create(['status' => 'preparing']);
        $teacher = Teacher::factory()->create();

        $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacher->id])->assertCreated();

        $this->assertDatabaseHas('classes', ['id' => $class->id, 'status' => 'active']);
    }

    public function test_ending_the_last_active_assignment_reverts_class_to_preparing(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create(['status' => 'preparing']);
        $teacher = Teacher::factory()->create();
        $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacher->id]);

        $this->deleteJson("/api/v1/classes/{$class->id}/teachers/{$teacher->id}")->assertNoContent();

        $this->assertDatabaseHas('classes', ['id' => $class->id, 'status' => 'preparing']);
    }

    public function test_ending_one_of_two_assignments_keeps_class_active(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create(['status' => 'preparing']);
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacherA->id]);
        $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacherB->id]);

        $this->deleteJson("/api/v1/classes/{$class->id}/teachers/{$teacherA->id}")->assertNoContent();

        $this->assertDatabaseHas('classes', ['id' => $class->id, 'status' => 'active']);
    }

    public function test_archived_class_is_not_reactivated_by_a_new_assignment(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create(['status' => 'archived', 'archived_at' => now()]);
        $teacher = Teacher::factory()->create();

        $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacher->id])->assertCreated();

        $this->assertDatabaseHas('classes', ['id' => $class->id, 'status' => 'archived']);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ClassStatusTransitionTest` → FAIL (status không tự đổi).

- [ ] **Step 3: Sửa `AssignTeacherToClassController.php`**

Thêm ngay sau khối `$assignment = TeacherClassAssignment::create([...]);`:
```php
        if ($class->status === 'preparing') {
            $class->update(['status' => 'active']);
        }
```

- [ ] **Step 4: Sửa `EndTeacherClassAssignmentController.php`**

Thêm ngay sau khối `$assignment->update([...]);`, trước câu `return response()->noContent();`:
```php
        $stillHasActiveTeacher = TeacherClassAssignment::where('class_id', $class->id)
            ->where('status', 'active')
            ->exists();

        if (! $stillHasActiveTeacher && $class->status === 'active') {
            $class->update(['status' => 'preparing']);
        }
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=ClassStatusTransitionTest`
Kỳ vọng: PASS (4 test).

- [ ] **Step 6: Chạy lại test Plan 2 liên quan để chắc không vỡ**

Chạy: `php artisan test --filter=TeacherClassAssignmentTest`
Kỳ vọng: PASS.

- [ ] **Step 7: Commit**

```bash
git add app tests
git commit -m "feat: auto-transition Class status based on active TeacherClassAssignment"
```

---

### Task 5: `Schedule` + `ScheduleHistory` — model + CRUD endpoint (chưa sinh session)

**Files:**
- Tạo: `database/migrations/2026_10_14_000001_create_schedules_table.php`
- Tạo: `database/migrations/2026_10_14_000002_create_schedule_histories_table.php`
- Tạo: `app/Models/Schedule.php`
- Tạo: `app/Models/ScheduleHistory.php`
- Sửa: `app/Models/SchoolClass.php` (thêm `schedules()`)
- Tạo: `database/factories/ScheduleFactory.php`
- Tạo: `app/Http/Controllers/Api/Lesson/ListSchedulesController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/CreateScheduleController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/UpdateScheduleController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/CancelScheduleController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Lesson/ScheduleTest.php`

**Interfaces:**
- Produces: `App\Models\Schedule` (cột: `id`, `class_id`, `status` `active/cancelled/ended`, `timezone`, `effective_from` date, `effective_until` date nullable, `local_start_time` string `HH:MM:SS`, `duration_minutes`, `repeat_weekly` boolean, `weekdays` array JSON **số ISO 1=Thứ Hai…7=Chủ Nhật** khi `repeat_weekly=true`, `cancel_reason`) — dùng bởi Task 6 (FK `schedule_id` của `LessonSession`) và Task 7 (generation service đọc các cột này).
- Produces: xung đột trả trong response `201` dưới khóa `warnings` — không phải field DB, chỉ là response shape, Task 7 không cần sửa lại việc này.
- **Chưa sinh LessonSession ở task này** — `POST`/`PATCH` ở đây chỉ lưu Schedule; việc sinh session được Task 7 nối vào 2 controller `CreateScheduleController`/`UpdateScheduleController` này.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Lesson;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    private function actingAsTeacher(Teacher $teacher): User
    {
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_admin_can_create_a_recurring_schedule(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();

        $response = $this->postJson("/api/v1/classes/{$class->id}/schedules", [
            'effective_from' => now()->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1, 3, 5],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('schedules', ['class_id' => $class->id, 'status' => 'active', 'timezone' => 'Asia/Ho_Chi_Minh']);
    }

    public function test_teacher_not_assigned_cannot_create_a_schedule(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);
        $class = SchoolClass::factory()->create();

        $response = $this->postJson("/api/v1/classes/{$class->id}/schedules", [
            'effective_from' => now()->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ]);

        $response->assertStatus(403);
    }

    public function test_detects_conflict_for_the_same_teacher_in_another_class(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create();
        $classA = SchoolClass::factory()->create();
        $classB = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $classA->id]);
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $classB->id]);
        $this->postJson("/api/v1/classes/{$classA->id}/schedules", [
            'effective_from' => now()->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ]);

        $response = $this->postJson("/api/v1/classes/{$classB->id}/schedules", [
            'effective_from' => now()->toDateString(),
            'local_start_time' => '08:30:00',
            'duration_minutes' => 60,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ]);

        $response->assertCreated();
        $this->assertNotEmpty($response->json('warnings'));
        $this->assertSame('teacher_schedule_conflict', $response->json('warnings.0.type'));
    }

    public function test_editing_a_schedule_requires_a_reason(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $created = $this->postJson("/api/v1/classes/{$class->id}/schedules", [
            'effective_from' => now()->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ])->json('schedule');

        $response = $this->patchJson("/api/v1/schedules/{$created['id']}", ['local_start_time' => '09:00:00']);

        $response->assertStatus(422);
    }

    public function test_cancelling_a_schedule_requires_a_reason(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $created = $this->postJson("/api/v1/classes/{$class->id}/schedules", [
            'effective_from' => now()->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ])->json('schedule');

        $response = $this->postJson("/api/v1/schedules/{$created['id']}/cancel", []);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ScheduleTest` → FAIL.

- [ ] **Step 3: Tạo 2 migration**

`database/migrations/2026_10_14_000001_create_schedules_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->enum('status', ['active', 'cancelled', 'ended'])->default('active');
            $table->string('timezone')->default('Asia/Ho_Chi_Minh');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->time('local_start_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('repeat_weekly');
            $table->json('weekdays')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
```

`database/migrations/2026_10_14_000002_create_schedule_histories_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignUuid('changed_by_user_id')->constrained('users');
            $table->timestamp('changed_at');
            $table->string('reason')->nullable();
            $table->json('before_data');
            $table->json('after_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_histories');
    }
};
```

- [ ] **Step 4: Tạo 2 model**

`app/Models/Schedule.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'class_id', 'status', 'timezone', 'effective_from', 'effective_until',
        'local_start_time', 'duration_minutes', 'repeat_weekly', 'weekdays', 'cancel_reason',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
        'repeat_weekly' => 'boolean',
        'weekdays' => 'array',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function lessonSessions()
    {
        return $this->hasMany(LessonSession::class);
    }

    public function histories()
    {
        return $this->hasMany(ScheduleHistory::class);
    }
}
```

`app/Models/ScheduleHistory.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'schedule_id', 'version_number', 'changed_by_user_id', 'changed_at', 'reason', 'before_data', 'after_data',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
```

- [ ] **Step 5: Sửa `SchoolClass.php` — thêm relation**

Thêm vào class `SchoolClass`:
```php
public function schedules()
{
    return $this->hasMany(Schedule::class, 'class_id');
}
```

- [ ] **Step 6: Tạo factory**

`database/factories/ScheduleFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'class_id' => SchoolClass::factory(),
            'status' => 'active',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'effective_from' => now()->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ];
    }
}
```

- [ ] **Step 7: Tạo 4 controller**

`app/Http/Controllers/Api/Lesson/ListSchedulesController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class ListSchedulesController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole !== 'admin' && ! ClassScope::classIdsFor($request->user(), $activeRole)->contains($class->id)) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy lớp.']], 404);
        }

        return response()->json(['data' => $class->schedules()->get()->map(fn ($s) => [
            'id' => $s->id,
            'status' => $s->status,
            'effective_from' => $s->effective_from->toDateString(),
            'effective_until' => $s->effective_until?->toDateString(),
            'local_start_time' => $s->local_start_time,
            'duration_minutes' => $s->duration_minutes,
            'repeat_weekly' => $s->repeat_weekly,
            'weekdays' => $s->weekdays,
        ])]);
    }
}
```

`app/Http/Controllers/Api/Lesson/CreateScheduleController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\TeacherClassAssignment;
use Illuminate\Http\Request;

class CreateScheduleController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $this->authorizeTeacherScope($request, $class);

        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'local_start_time' => ['required', 'date_format:H:i:s'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'repeat_weekly' => ['required', 'boolean'],
            'weekdays' => ['required_if:repeat_weekly,true', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
        ]);

        $schedule = Schedule::create($data + ['class_id' => $class->id, 'status' => 'active']);

        $warnings = $this->conflictWarnings($class, $schedule);

        return response()->json([
            'schedule' => [
                'id' => $schedule->id,
                'class_id' => $schedule->class_id,
                'status' => $schedule->status,
                'effective_from' => $schedule->effective_from->toDateString(),
                'local_start_time' => $schedule->local_start_time,
                'duration_minutes' => $schedule->duration_minutes,
            ],
            'warnings' => $warnings,
        ], 201);
    }

    private function authorizeTeacherScope(Request $request, SchoolClass $class): void
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole === 'admin') {
            return;
        }

        abort_unless(
            $request->user()->teacherProfile?->activeClassIds()->contains($class->id),
            403,
            'Không có quyền tạo lịch cho lớp này.'
        );
    }

    /**
     * DEC-SCH-002: so cùng Teacher (qua TeacherClassAssignment hiệu lực của class này)
     * với các Schedule active khác của họ ở Class khác, trùng weekday + khung giờ.
     * Chỉ cảnh báo, không chặn — xem spec §5.2.
     */
    private function conflictWarnings(SchoolClass $class, Schedule $schedule): array
    {
        $teacherIds = TeacherClassAssignment::where('class_id', $class->id)
            ->where('status', 'active')
            ->pluck('teacher_id');

        if ($teacherIds->isEmpty() || ! $schedule->repeat_weekly) {
            return [];
        }

        $otherClassIds = TeacherClassAssignment::whereIn('teacher_id', $teacherIds)
            ->where('status', 'active')
            ->where('class_id', '!=', $class->id)
            ->pluck('class_id');

        $conflicts = Schedule::whereIn('class_id', $otherClassIds)
            ->where('status', 'active')
            ->where('id', '!=', $schedule->id)
            ->where('local_start_time', '<', $this->endTime($schedule))
            ->get()
            ->filter(fn ($other) => $this->timesOverlap($schedule, $other) && $this->weekdaysOverlap($schedule, $other));

        return $conflicts->map(fn ($other) => [
            'type' => 'teacher_schedule_conflict',
            'conflicting_schedule_id' => $other->id,
            'class_id' => $other->class_id,
        ])->values()->all();
    }

    private function endTime(Schedule $schedule): string
    {
        return \Carbon\Carbon::createFromFormat('H:i:s', $schedule->local_start_time)
            ->addMinutes($schedule->duration_minutes)
            ->format('H:i:s');
    }

    private function timesOverlap(Schedule $a, Schedule $b): bool
    {
        return $a->local_start_time < $this->endTime($b) && $b->local_start_time < $this->endTime($a);
    }

    private function weekdaysOverlap(Schedule $a, Schedule $b): bool
    {
        return ! empty(array_intersect($a->weekdays ?? [], $b->weekdays ?? []));
    }
}
```

`app/Http/Controllers/Api/Lesson/UpdateScheduleController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleHistory;
use Illuminate\Http\Request;

class UpdateScheduleController extends Controller
{
    public function __invoke(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'effective_until' => ['sometimes', 'nullable', 'date'],
            'local_start_time' => ['sometimes', 'date_format:H:i:s'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'weekdays' => ['sometimes', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'reason' => ['required', 'string'],
        ]);

        $before = $schedule->only(['local_start_time', 'duration_minutes', 'weekdays', 'effective_until']);

        $schedule->fill(array_diff_key($data, ['reason' => null]));
        $schedule->save();

        ScheduleHistory::create([
            'schedule_id' => $schedule->id,
            'version_number' => $schedule->histories()->count() + 1,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
            'reason' => $data['reason'],
            'before_data' => $before,
            'after_data' => $schedule->only(['local_start_time', 'duration_minutes', 'weekdays', 'effective_until']),
        ]);

        return response()->json(['schedule' => [
            'id' => $schedule->id,
            'local_start_time' => $schedule->local_start_time,
            'duration_minutes' => $schedule->duration_minutes,
            'weekdays' => $schedule->weekdays,
        ]]);
    }
}
```

`app/Http/Controllers/Api/Lesson/CancelScheduleController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class CancelScheduleController extends Controller
{
    public function __invoke(Request $request, Schedule $schedule)
    {
        $data = $request->validate(['cancel_reason' => ['required', 'string']]);

        $schedule->update(['status' => 'cancelled', 'cancel_reason' => $data['cancel_reason']]);

        return response()->noContent();
    }
}
```

- [ ] **Step 8: Thêm route**

Thêm `'schedule'` vào mảng `Route::pattern`:
```php
foreach (['user', 'roleId', 'personProfile', 'parentStudentRelationship', 'student', 'parent', 'teacher', 'class', 'teacherId', 'studentId', 'subject', 'schedule'] as $uuidParam) {
```

Thêm vào nhóm `role:admin,teacher,student,parent`:
```php
Route::get('/classes/{class}/schedules', \App\Http\Controllers\Api\Lesson\ListSchedulesController::class);
```

Thêm vào nhóm `role:admin,teacher` đã có từ Plan 2 (trước đó chỉ chứa route đọc — `GET /students/{student}`, `GET /classes/{class}/teachers`, `GET /classes/{class}/members`; giờ thêm route ghi vào cùng nhóm vì middleware điều kiện giống nhau, scope cụ thể do từng controller tự kiểm tra inline):
```php
Route::post('/classes/{class}/schedules', \App\Http\Controllers\Api\Lesson\CreateScheduleController::class);
Route::patch('/schedules/{schedule}', \App\Http\Controllers\Api\Lesson\UpdateScheduleController::class);
Route::post('/schedules/{schedule}/cancel', \App\Http\Controllers\Api\Lesson\CancelScheduleController::class);
```
(`CreateScheduleController` tự kiểm tra lại scope Teacher trong thân hàm vì middleware chỉ biết role, không biết class cụ thể — xem `authorizeTeacherScope` ở Step 7.)

- [ ] **Step 9: Chạy lại test**

Chạy: `php artisan test --filter=ScheduleTest`
Kỳ vọng: PASS (5 test).

- [ ] **Step 10: Commit**

```bash
git add app database routes
git commit -m "feat: add Schedule model and CRUD endpoints with conflict warnings"
```

---

### Task 6: `LessonSession` + `LessonSessionHistory` — model + CRUD thủ công

**Files:**
- Tạo: `database/migrations/2026_10_14_000003_create_lesson_sessions_table.php`
- Tạo: `database/migrations/2026_10_14_000004_create_lesson_session_histories_table.php`
- Tạo: `app/Models/LessonSession.php`
- Tạo: `app/Models/LessonSessionHistory.php`
- Sửa: `app/Models/SchoolClass.php` (thêm `lessonSessions()`)
- Tạo: `database/factories/LessonSessionFactory.php`
- Tạo: `app/Http/Controllers/Api/Lesson/ListLessonSessionsController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/GetLessonSessionController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/CreateLessonSessionController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/UpdateLessonSessionController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/CancelLessonSessionController.php`
- Tạo: `app/Http/Controllers/Api/Lesson/CompleteLessonSessionController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Lesson/LessonSessionTest.php`

**Interfaces:**
- Produces: `App\Models\LessonSession` (cột: `id`, `class_id`, `schedule_id` nullable, `starts_at`/`ends_at` instant, `status` `scheduled/completed/cancelled`, `lesson_content`, `notes`, `cancellation_reason`, `review_required` boolean, `completed_at`, `created_by_user_id`) — dùng bởi Task 7 (generation service tạo hàng loạt), Task 8 (Attendance gắn `lesson_session_id`).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Lesson;

use App\Models\LessonSession;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSessionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    public function test_admin_can_create_a_manual_session(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();

        $response = $this->postJson("/api/v1/classes/{$class->id}/lesson-sessions", [
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHour()->toIso8601String(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('lesson_sessions', ['class_id' => $class->id, 'schedule_id' => null, 'status' => 'scheduled']);
    }

    public function test_editing_a_completed_session_requires_a_reason_and_writes_history(): void
    {
        $this->actingAsAdmin();
        $session = LessonSession::factory()->create(['status' => 'completed', 'completed_at' => now()]);

        $response = $this->patchJson("/api/v1/lesson-sessions/{$session->id}", ['notes' => 'Ghi chú mới']);
        $response->assertStatus(422);

        $response = $this->patchJson("/api/v1/lesson-sessions/{$session->id}", [
            'notes' => 'Ghi chú mới',
            'reason' => 'Bổ sung nội dung đã dạy',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('lesson_session_histories', ['lesson_session_id' => $session->id]);
    }

    public function test_cancelling_a_session_sets_status_and_reason(): void
    {
        // Hành vi "giữ Attendance + review_required=true khi đã có AttendanceRecord"
        // (DEC-LRN-004) được thêm và test ở Task 8, sau khi AttendanceRecord tồn tại —
        // ở đây chỉ xác nhận nhánh cơ bản (chưa có Attendance nào).
        $this->actingAsAdmin();
        $session = LessonSession::factory()->create(['status' => 'scheduled']);

        $response = $this->postJson("/api/v1/lesson-sessions/{$session->id}/cancel", ['cancellation_reason' => 'Giáo viên nghỉ đột xuất']);

        $response->assertNoContent();
        $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'status' => 'cancelled', 'review_required' => false]);
    }

    public function test_completing_a_session_sets_completed_at(): void
    {
        $this->actingAsAdmin();
        $session = LessonSession::factory()->create(['status' => 'scheduled']);

        $response = $this->postJson("/api/v1/lesson-sessions/{$session->id}/complete");

        $response->assertOk();
        $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'status' => 'completed']);
        $this->assertNotNull($session->fresh()->completed_at);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=LessonSessionTest` → FAIL.

- [ ] **Step 3: Tạo 2 migration**

`database/migrations/2026_10_14_000003_create_lesson_sessions_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignUuid('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->text('lesson_content')->nullable();
            $table->text('notes')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->boolean('review_required')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_sessions');
    }
};
```

`database/migrations/2026_10_14_000004_create_lesson_session_histories_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_session_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignUuid('changed_by_user_id')->constrained('users');
            $table->timestamp('changed_at');
            $table->string('reason')->nullable();
            $table->json('before_data');
            $table->json('after_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_session_histories');
    }
};
```

- [ ] **Step 4: Tạo 2 model**

`app/Models/LessonSession.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'class_id', 'schedule_id', 'starts_at', 'ends_at', 'status', 'lesson_content',
        'notes', 'cancellation_reason', 'review_required', 'completed_at', 'created_by_user_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'review_required' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function histories()
    {
        return $this->hasMany(LessonSessionHistory::class);
    }
}
```

`app/Models/LessonSessionHistory.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonSessionHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lesson_session_id', 'changed_by_user_id', 'changed_at', 'reason', 'before_data', 'after_data',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class);
    }
}
```

- [ ] **Step 5: Sửa `SchoolClass.php` — thêm relation**

```php
public function lessonSessions()
{
    return $this->hasMany(LessonSession::class, 'class_id');
}
```

- [ ] **Step 6: Tạo factory**

`database/factories/LessonSessionFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\LessonSession;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonSessionFactory extends Factory
{
    protected $model = LessonSession::class;

    public function definition(): array
    {
        $start = now()->addDay()->setTime(8, 0);

        return [
            'class_id' => SchoolClass::factory(),
            'schedule_id' => null,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(90),
            'status' => 'scheduled',
        ];
    }
}
```

- [ ] **Step 7: Tạo 6 controller**

`app/Http/Controllers/Api/Lesson/ListLessonSessionsController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class ListLessonSessionsController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole !== 'admin' && ! ClassScope::classIdsFor($request->user(), $activeRole)->contains($class->id)) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy lớp.']], 404);
        }

        $query = $class->lessonSessions()->orderBy('starts_at');

        if ($request->filled('from')) {
            $query->where('starts_at', '>=', $request->date('from'));
        }
        if ($request->filled('until')) {
            $query->where('starts_at', '<=', $request->date('until'));
        }

        return response()->json(['data' => $query->get()->map(fn ($s) => [
            'id' => $s->id,
            'starts_at' => $s->starts_at->toIso8601String(),
            'ends_at' => $s->ends_at->toIso8601String(),
            'status' => $s->status,
            'review_required' => $s->review_required,
        ])]);
    }
}
```

`app/Http/Controllers/Api/Lesson/GetLessonSessionController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class GetLessonSessionController extends Controller
{
    public function __invoke(Request $request, LessonSession $lessonSession)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole !== 'admin' && ! ClassScope::classIdsFor($request->user(), $activeRole)->contains($lessonSession->class_id)) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy buổi học.']], 404);
        }

        return response()->json(['lesson_session' => [
            'id' => $lessonSession->id,
            'class_id' => $lessonSession->class_id,
            'starts_at' => $lessonSession->starts_at->toIso8601String(),
            'ends_at' => $lessonSession->ends_at->toIso8601String(),
            'status' => $lessonSession->status,
            'lesson_content' => $lessonSession->lesson_content,
            'notes' => $lessonSession->notes,
            'review_required' => $lessonSession->review_required,
        ]]);
    }
}
```

`app/Http/Controllers/Api/Lesson/CreateLessonSessionController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class CreateLessonSessionController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole !== 'admin') {
            abort_unless($request->user()->teacherProfile?->activeClassIds()->contains($class->id), 403);
        }

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'lesson_content' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = LessonSession::create($data + [
            'class_id' => $class->id,
            'schedule_id' => null,
            'status' => 'scheduled',
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['lesson_session' => [
            'id' => $session->id,
            'class_id' => $session->class_id,
            'starts_at' => $session->starts_at->toIso8601String(),
            'ends_at' => $session->ends_at->toIso8601String(),
            'status' => $session->status,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/Lesson/UpdateLessonSessionController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use App\Models\LessonSessionHistory;
use Illuminate\Http\Request;

class UpdateLessonSessionController extends Controller
{
    public function __invoke(Request $request, LessonSession $lessonSession)
    {
        $rules = [
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'lesson_content' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];

        if ($lessonSession->status === 'completed') {
            $rules['reason'] = ['required', 'string'];
        }

        $data = $request->validate($rules);
        $reason = $data['reason'] ?? null;
        unset($data['reason']);

        $before = $lessonSession->only(['starts_at', 'ends_at', 'lesson_content', 'notes']);

        $lessonSession->fill($data);
        $lessonSession->save();

        if ($reason !== null) {
            LessonSessionHistory::create([
                'lesson_session_id' => $lessonSession->id,
                'changed_by_user_id' => $request->user()->id,
                'changed_at' => now(),
                'reason' => $reason,
                'before_data' => $before,
                'after_data' => $lessonSession->only(['starts_at', 'ends_at', 'lesson_content', 'notes']),
            ]);
        }

        return response()->json(['lesson_session' => [
            'id' => $lessonSession->id,
            'status' => $lessonSession->status,
            'notes' => $lessonSession->notes,
        ]]);
    }
}
```

`app/Http/Controllers/Api/Lesson/CancelLessonSessionController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use Illuminate\Http\Request;

class CancelLessonSessionController extends Controller
{
    public function __invoke(Request $request, LessonSession $lessonSession)
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string']]);

        // DEC-LRN-004 (kiểm tra AttendanceRecord đã có hay chưa): thêm ở Task 8,
        // sau khi model AttendanceRecord tồn tại. Ở đây review_required luôn false.
        $lessonSession->update([
            'status' => 'cancelled',
            'cancellation_reason' => $data['cancellation_reason'],
            'review_required' => false,
        ]);

        return response()->noContent();
    }
}
```

`app/Http/Controllers/Api/Lesson/CompleteLessonSessionController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use Illuminate\Http\Request;

class CompleteLessonSessionController extends Controller
{
    public function __invoke(Request $request, LessonSession $lessonSession)
    {
        $lessonSession->update(['status' => 'completed', 'completed_at' => now()]);

        return response()->json(['lesson_session' => ['id' => $lessonSession->id, 'status' => $lessonSession->status]]);
    }
}
```

- [ ] **Step 8: Thêm route**

Thêm `'lessonSession'` vào mảng `Route::pattern`:
```php
foreach (['user', 'roleId', 'personProfile', 'parentStudentRelationship', 'student', 'parent', 'teacher', 'class', 'teacherId', 'studentId', 'subject', 'schedule', 'lessonSession'] as $uuidParam) {
```

Thêm vào nhóm `role:admin,teacher,student,parent`:
```php
Route::get('/classes/{class}/lesson-sessions', \App\Http\Controllers\Api\Lesson\ListLessonSessionsController::class);
Route::get('/lesson-sessions/{lessonSession}', \App\Http\Controllers\Api\Lesson\GetLessonSessionController::class);
```

Thêm vào nhóm `role:admin,teacher`:
```php
Route::post('/classes/{class}/lesson-sessions', \App\Http\Controllers\Api\Lesson\CreateLessonSessionController::class);
Route::patch('/lesson-sessions/{lessonSession}', \App\Http\Controllers\Api\Lesson\UpdateLessonSessionController::class);
Route::post('/lesson-sessions/{lessonSession}/cancel', \App\Http\Controllers\Api\Lesson\CancelLessonSessionController::class);
Route::post('/lesson-sessions/{lessonSession}/complete', \App\Http\Controllers\Api\Lesson\CompleteLessonSessionController::class);
```

- [ ] **Step 9: Chạy lại test**

Chạy: `php artisan test --filter=LessonSessionTest`
Kỳ vọng: PASS (4 test).

- [ ] **Step 10: Commit**

```bash
git add app database routes
git commit -m "feat: add LessonSession model and manual CRUD endpoints"
```

---

### Task 7: Sinh LessonSession tự động từ Schedule (cửa sổ rolling 4 tuần + Scheduler job)

**Files:**
- Tạo: `app/Services/Lesson/LessonSessionGenerator.php`
- Sửa: `app/Http/Controllers/Api/Lesson/CreateScheduleController.php` (gọi generator sau khi tạo Schedule)
- Sửa: `app/Http/Controllers/Api/Lesson/UpdateScheduleController.php` (gọi generator sau khi sửa Schedule)
- Tạo: `app/Console/Commands/GenerateUpcomingLessonSessions.php`
- Sửa: `routes/console.php` (đăng ký job chạy hàng ngày)
- Tạo: `tests/Unit/Services/LessonSessionGeneratorTest.php`
- Tạo: `tests/Feature/Console/GenerateUpcomingLessonSessionsTest.php`

**Interfaces:**
- Produces: `App\Services\Lesson\LessonSessionGenerator::generateWindow(Schedule $schedule, \Carbon\Carbon $windowEnd): int` (trả số session mới tạo) — dùng bởi `CreateScheduleController`, `UpdateScheduleController`, và `GenerateUpcomingLessonSessions` command.
- Consumes: `App\Models\Schedule` (Task 5), `App\Models\LessonSession` (Task 6).

- [ ] **Step 1: Viết test thất bại trước (service, unit-level qua DB thật vì cần Eloquent)**

```php
<?php

namespace Tests\Unit\Services;

use App\Models\LessonSession;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Services\Lesson\LessonSessionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSessionGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_a_session_for_each_matching_weekday_in_the_window(): void
    {
        $class = SchoolClass::factory()->create();
        $monday = \Carbon\Carbon::parse('next monday')->startOfDay();
        $schedule = Schedule::factory()->create([
            'class_id' => $class->id,
            'effective_from' => $monday->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1, 3], // Thứ Hai, Thứ Tư
        ]);

        // windowEnd = Chủ Nhật cuối tuần thứ 2 (13 ngày sau Thứ Hai gốc) — đúng
        // 2 tuần trọn vẹn, tránh lệch 1 vì vòng lặp generateWindow() là "<=" nên
        // addWeeks(2) (rơi đúng vào Thứ Hai tuần kế) sẽ tính dư 1 lần nữa.
        $created = (new LessonSessionGenerator())->generateWindow($schedule, $monday->copy()->addDays(13));

        $this->assertSame(4, $created); // 2 tuần x 2 ngày/tuần (Thứ Hai + Thứ Tư mỗi tuần)
        $this->assertDatabaseCount('lesson_sessions', 4);
    }

    public function test_does_not_duplicate_sessions_already_generated(): void
    {
        $class = SchoolClass::factory()->create();
        $monday = \Carbon\Carbon::parse('next monday')->startOfDay();
        $schedule = Schedule::factory()->create([
            'class_id' => $class->id,
            'effective_from' => $monday->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ]);
        // Lần 1: windowEnd = đúng Thứ Hai gốc (effective_from) — chỉ sinh 1 session.
        (new LessonSessionGenerator())->generateWindow($schedule, $monday->copy());

        // Lần 2: windowEnd lùi xa hơn 1 tuần — generateWindow() luôn quét lại từ
        // effective_from (không nhớ điểm dừng lần trước), nên sẽ gặp lại Thứ Hai gốc
        // (đã tồn tại, bị createIfMissing() bỏ qua) rồi mới tới Thứ Hai tuần kế (mới).
        $createdAgain = (new LessonSessionGenerator())->generateWindow($schedule, $monday->copy()->addWeek());

        $this->assertSame(1, $createdAgain); // chỉ Thứ Hai tuần kế là mới
        $this->assertSame(2, LessonSession::where('schedule_id', $schedule->id)->count());
    }

    public function test_stops_at_effective_until(): void
    {
        $class = SchoolClass::factory()->create();
        $monday = \Carbon\Carbon::parse('next monday')->startOfDay();
        $schedule = Schedule::factory()->create([
            'class_id' => $class->id,
            'effective_from' => $monday->toDateString(),
            'effective_until' => $monday->copy()->addDays(3)->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1, 3, 5],
        ]);

        $created = (new LessonSessionGenerator())->generateWindow($schedule, $monday->copy()->addWeeks(4));

        $this->assertSame(2, $created); // chỉ Thứ Hai + Thứ Tư nằm trong effective_until, Thứ Sáu đã quá hạn
    }

    public function test_does_not_generate_for_a_cancelled_schedule(): void
    {
        $class = SchoolClass::factory()->create();
        $schedule = Schedule::factory()->create([
            'class_id' => $class->id,
            'status' => 'cancelled',
            'effective_from' => now()->toDateString(),
            'repeat_weekly' => true,
            'weekdays' => [1],
        ]);

        $created = (new LessonSessionGenerator())->generateWindow($schedule, now()->addWeeks(4));

        $this->assertSame(0, $created);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=LessonSessionGeneratorTest` → FAIL (class chưa tồn tại).

- [ ] **Step 3: Tạo `LessonSessionGenerator`**

`app/Services/Lesson/LessonSessionGenerator.php`:
```php
<?php

namespace App\Services\Lesson;

use App\Models\LessonSession;
use App\Models\Schedule;
use Carbon\Carbon;

class LessonSessionGenerator
{
    /**
     * Sinh LessonSession còn thiếu cho Schedule từ effective_from (hoặc hôm nay
     * nếu đã qua) tới $windowEnd, giới hạn bởi effective_until nếu có. Idempotent:
     * không tạo lại session đã tồn tại cho đúng ngày đó (ARCH-13).
     */
    public function generateWindow(Schedule $schedule, Carbon $windowEnd): int
    {
        if ($schedule->status !== 'active' || ! $schedule->repeat_weekly) {
            return 0;
        }

        $windowStart = Carbon::parse($schedule->effective_from)->max(Carbon::today());
        $effectiveUntil = $schedule->effective_until ? Carbon::parse($schedule->effective_until) : null;
        $limit = $effectiveUntil ? $windowEnd->min($effectiveUntil) : $windowEnd;

        if ($windowStart->gt($limit)) {
            return 0;
        }

        $created = 0;
        $cursor = $windowStart->copy();

        while ($cursor->lte($limit)) {
            if (in_array($cursor->isoWeekday(), $schedule->weekdays ?? [], true)) {
                $created += $this->createIfMissing($schedule, $cursor);
            }
            $cursor->addDay();
        }

        return $created;
    }

    private function createIfMissing(Schedule $schedule, Carbon $date): int
    {
        $startsAt = Carbon::parse(
            $date->toDateString().' '.$schedule->local_start_time,
            $schedule->timezone
        )->utc();

        // So đúng instant (không phải whereDate) để tránh lệch ngày khi giờ local
        // gần nửa đêm UTC làm offset timezone đẩy sang ngày UTC liền kề.
        $exists = LessonSession::where('schedule_id', $schedule->id)
            ->where('starts_at', $startsAt)
            ->exists();

        if ($exists) {
            return 0;
        }

        LessonSession::create([
            'class_id' => $schedule->class_id,
            'schedule_id' => $schedule->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($schedule->duration_minutes),
            'status' => 'scheduled',
        ]);

        return 1;
    }
}
```

- [ ] **Step 4: Chạy lại test service**

Chạy: `php artisan test --filter=LessonSessionGeneratorTest`
Kỳ vọng: PASS (4 test).

- [ ] **Step 5: Nối generator vào `CreateScheduleController`**

Thêm `use App\Services\Lesson\LessonSessionGenerator;` ở đầu file, và thêm dòng sau ngay sau `$warnings = $this->conflictWarnings($class, $schedule);`, trước câu `return response()->json([...]);`:
```php
        (new LessonSessionGenerator())->generateWindow($schedule, now()->addWeeks(4));
```

- [ ] **Step 6: Nối generator vào `UpdateScheduleController`**

Thêm `use App\Services\Lesson\LessonSessionGenerator;` ở đầu file, và thêm dòng sau ngay sau khối `ScheduleHistory::create([...]);`, trước câu `return response()->json([...]);`:
```php
        // BR-CLASS-007/spec §5.1: chỉ sinh thêm phần còn thiếu từ hôm nay tới 4 tuần
        // tới — session đã sinh trước đó (kể cả tương lai) không bị generator đụng vào,
        // vì createIfMissing() bỏ qua ngày đã có hàng.
        (new LessonSessionGenerator())->generateWindow($schedule, now()->addWeeks(4));
```

- [ ] **Step 7: Viết test thất bại cho test tích hợp controller (xác nhận sinh ngay khi tạo Schedule)**

Thêm vào `tests/Feature/Lesson/ScheduleTest.php` (file đã có từ Task 5):
```php
    public function test_creating_a_schedule_immediately_generates_upcoming_sessions(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();

        $this->postJson("/api/v1/classes/{$class->id}/schedules", [
            'effective_from' => \Carbon\Carbon::parse('next monday')->toDateString(),
            'local_start_time' => '08:00:00',
            'duration_minutes' => 90,
            'repeat_weekly' => true,
            'weekdays' => [1],
        ])->assertCreated();

        $this->assertDatabaseHas('lesson_sessions', ['class_id' => $class->id]);
    }
```

- [ ] **Step 8: Chạy để xác nhận test mới cũng fail trước khi nối (nếu Step 5-6 đã làm thì bỏ qua, chạy để xác nhận pass luôn)**

Chạy: `php artisan test --filter=ScheduleTest`
Kỳ vọng: PASS (6 test, gồm test mới).

- [ ] **Step 9: Tạo Artisan command cho Scheduler job hàng ngày**

`app/Console/Commands/GenerateUpcomingLessonSessions.php`:
```php
<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Services\Lesson\LessonSessionGenerator;
use Illuminate\Console\Command;

class GenerateUpcomingLessonSessions extends Command
{
    protected $signature = 'lesson-sessions:generate-upcoming';

    protected $description = 'ARCH-13: giữ cửa sổ 4 tuần LessonSession sắp tới luôn có sẵn cho mọi Schedule active.';

    public function handle(LessonSessionGenerator $generator): int
    {
        $windowEnd = now()->addWeeks(4);
        $total = 0;

        Schedule::where('status', 'active')->each(function (Schedule $schedule) use ($generator, $windowEnd, &$total) {
            $total += $generator->generateWindow($schedule, $windowEnd);
        });

        $this->info("Đã sinh {$total} LessonSession mới.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 10: Viết test thất bại cho command**

```php
<?php

namespace Tests\Feature\Console;

use App\Models\Schedule;
use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateUpcomingLessonSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_sessions_for_every_active_schedule(): void
    {
        $class = SchoolClass::factory()->create();
        Schedule::factory()->create([
            'class_id' => $class->id,
            'effective_from' => \Carbon\Carbon::parse('next monday')->toDateString(),
            'repeat_weekly' => true,
            'weekdays' => [1],
        ]);

        $this->artisan('lesson-sessions:generate-upcoming')->assertSuccessful();

        $this->assertDatabaseCount('lesson_sessions', 1);
    }
}
```

- [ ] **Step 11: Chạy để xác nhận**

Chạy: `php artisan test --filter=GenerateUpcomingLessonSessionsTest`
Kỳ vọng: PASS.

- [ ] **Step 12: Đăng ký lịch chạy hàng ngày trong `routes/console.php`**

Thêm vào cuối file `routes/console.php`:
```php
use Illuminate\Support\Facades\Schedule as ConsoleSchedule;

ConsoleSchedule::command('lesson-sessions:generate-upcoming')->daily();
```

- [ ] **Step 13: Chạy lại toàn bộ test nhóm Lesson để chắc không vỡ**

Chạy: `php artisan test --filter=ScheduleTest` và `php artisan test --filter=LessonSessionTest`
Kỳ vọng: PASS.

- [ ] **Step 14: Commit**

```bash
git add app routes tests
git commit -m "feat: generate upcoming LessonSessions from Schedule (rolling 4-week window)"
```

---

### Task 8: `AttendanceRecord` + `AttendanceHistory` — bulk mark + sửa có lịch sử

**Files:**
- Tạo: `database/migrations/2026_10_14_000005_create_attendance_records_table.php`
- Tạo: `database/migrations/2026_10_14_000006_create_attendance_histories_table.php`
- Tạo: `app/Models/AttendanceRecord.php`
- Tạo: `app/Models/AttendanceHistory.php`
- Sửa: `app/Models/Student.php` (thêm `attendanceRecords()`)
- Tạo: `database/factories/AttendanceRecordFactory.php`
- Tạo: `app/Http/Controllers/Api/Attendance/GetSessionAttendanceController.php`
- Tạo: `app/Http/Controllers/Api/Attendance/BulkMarkAttendanceController.php`
- Tạo: `app/Http/Controllers/Api/Attendance/UpdateAttendanceRecordController.php`
- Sửa: `app/Http/Controllers/Api/Lesson/CancelLessonSessionController.php` (thêm kiểm tra `AttendanceRecord` đã có — `DEC-LRN-004`, hoãn từ Task 6 vì model này chưa tồn tại lúc đó)
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Attendance/AttendanceTest.php`

**Interfaces:**
- Produces: `App\Models\AttendanceRecord` — dùng bởi Task 9 (`AbsenceExplanation.attendance_record_id` nullable FK) và bởi bản sửa `CancelLessonSessionController` trong chính task này (quan hệ `LessonSession::attendanceRecords()` đã định nghĩa sẵn từ Task 6 Step 4, chỉ chưa dùng tới cho đến khi model này tồn tại).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Attendance;

use App\Models\ClassMember;
use App\Models\LessonSession;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    private function actingAsTeacher(Teacher $teacher): User
    {
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_teacher_can_bulk_mark_attendance_for_the_roster(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $class->id]);
        $session = LessonSession::factory()->create(['class_id' => $class->id, 'starts_at' => now()->addDay()]);
        $student = Student::factory()->create();
        ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id, 'valid_from' => now()->subWeek()]);

        $this->actingAsTeacher($teacher);
        $response = $this->putJson("/api/v1/lesson-sessions/{$session->id}/attendance", [
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendance_records', ['lesson_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);
    }

    public function test_bulk_mark_rejects_a_student_without_active_membership_at_session_time(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $class->id]);
        $session = LessonSession::factory()->create(['class_id' => $class->id, 'starts_at' => now()->addDay()]);
        $student = Student::factory()->create(); // không có ClassMember cho lớp này

        $this->actingAsTeacher($teacher);
        $response = $this->putJson("/api/v1/lesson-sessions/{$session->id}/attendance", [
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('attendance_records', ['lesson_session_id' => $session->id, 'student_id' => $student->id]);
    }

    public function test_updating_a_record_requires_a_reason_and_writes_history(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $class->id]);
        $session = LessonSession::factory()->create(['class_id' => $class->id, 'starts_at' => now()->addDay()]);
        $student = Student::factory()->create();
        ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id, 'valid_from' => now()->subWeek()]);
        $this->actingAsTeacher($teacher);
        $this->putJson("/api/v1/lesson-sessions/{$session->id}/attendance", [
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);
        $recordId = \App\Models\AttendanceRecord::where('lesson_session_id', $session->id)->first()->id;

        $response = $this->patchJson("/api/v1/attendance-records/{$recordId}", ['status' => 'absent']);
        $response->assertStatus(422);

        $response = $this->patchJson("/api/v1/attendance-records/{$recordId}", ['status' => 'absent', 'reason' => 'Học sinh xin nghỉ sau khi đã điểm danh']);
        $response->assertOk();
        $this->assertDatabaseHas('attendance_records', ['id' => $recordId, 'status' => 'absent']);
        $this->assertDatabaseHas('attendance_histories', ['attendance_record_id' => $recordId]);
    }

    public function test_cancelling_a_session_with_attendance_keeps_records_and_flags_review(): void
    {
        // DEC-LRN-004 — bổ sung ở task này vì cần AttendanceRecord đã tồn tại;
        // nhánh "chưa có Attendance" đã test ở Task 6 (LessonSessionTest).
        $this->actingAsAdmin();
        $session = LessonSession::factory()->create(['status' => 'scheduled']);
        $student = Student::factory()->create();
        $record = \App\Models\AttendanceRecord::factory()->create(['lesson_session_id' => $session->id, 'student_id' => $student->id]);

        $response = $this->postJson("/api/v1/lesson-sessions/{$session->id}/cancel", ['cancellation_reason' => 'Giáo viên nghỉ đột xuất']);

        $response->assertNoContent();
        $this->assertDatabaseHas('lesson_sessions', ['id' => $session->id, 'status' => 'cancelled', 'review_required' => true]);
        $this->assertDatabaseHas('attendance_records', ['id' => $record->id]);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=AttendanceTest` → FAIL.

- [ ] **Step 3: Tạo 2 migration**

`database/migrations/2026_10_14_000005_create_attendance_records_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'late', 'excused']);
            $table->string('note')->nullable();
            $table->foreignUuid('recorded_by_user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['lesson_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
```

`database/migrations/2026_10_14_000006_create_attendance_histories_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->foreignUuid('changed_by_user_id')->constrained('users');
            $table->timestamp('changed_at');
            $table->string('reason');
            $table->json('before_data');
            $table->json('after_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_histories');
    }
};
```

- [ ] **Step 4: Tạo 2 model**

`app/Models/AttendanceRecord.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['lesson_session_id', 'student_id', 'status', 'note', 'recorded_by_user_id'];

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function histories()
    {
        return $this->hasMany(AttendanceHistory::class);
    }
}
```

`app/Models/AttendanceHistory.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'attendance_record_id', 'changed_by_user_id', 'changed_at', 'reason', 'before_data', 'after_data',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
```

- [ ] **Step 5: Sửa `Student.php` — thêm relation**

```php
public function attendanceRecords()
{
    return $this->hasMany(AttendanceRecord::class);
}
```

- [ ] **Step 6: Tạo factory**

`database/factories/AttendanceRecordFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'lesson_session_id' => LessonSession::factory(),
            'student_id' => Student::factory(),
            'status' => 'present',
            'recorded_by_user_id' => User::factory(),
        ];
    }
}
```

- [ ] **Step 7: Tạo 3 controller**

`app/Http/Controllers/Api/Attendance/GetSessionAttendanceController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class GetSessionAttendanceController extends Controller
{
    public function __invoke(Request $request, LessonSession $lessonSession)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole !== 'admin' && ! ClassScope::classIdsFor($request->user(), $activeRole)->contains($lessonSession->class_id)) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy buổi học.']], 404);
        }

        $records = $lessonSession->attendanceRecords()->with('student.personProfile')->get();

        if (in_array($activeRole, ['student', 'parent'], true)) {
            $myStudentIds = $activeRole === 'student'
                ? [$request->user()->studentProfile?->id]
                : $request->user()->parentProfile?->activeChildrenIds()->all() ?? [];
            $records = $records->whereIn('student_id', $myStudentIds);
        }

        return response()->json(['data' => $records->map(fn ($r) => [
            'student_id' => $r->student_id,
            'full_name' => $r->student->personProfile->full_name,
            'status' => $r->status,
            'note' => $r->note,
        ])->values()]);
    }
}
```

`app/Http/Controllers/Api/Attendance/BulkMarkAttendanceController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\ClassMember;
use App\Models\LessonSession;
use Illuminate\Http\Request;

class BulkMarkAttendanceController extends Controller
{
    public function __invoke(Request $request, LessonSession $lessonSession)
    {
        $data = $request->validate([
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'uuid', 'exists:students,id'],
            'records.*.status' => ['required', 'in:present,absent,late,excused'],
            'records.*.note' => ['nullable', 'string'],
        ]);

        // BR-ATT-004: chỉ nhận Student có ClassMember hiệu lực tại thời điểm buổi học —
        // từ chối toàn bộ request nếu có ít nhất một student không đủ điều kiện.
        $studentIds = collect($data['records'])->pluck('student_id');
        $eligibleIds = ClassMember::where('class_id', $lessonSession->class_id)
            ->whereIn('student_id', $studentIds)
            ->where('valid_from', '<=', $lessonSession->starts_at)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', $lessonSession->starts_at))
            ->pluck('student_id');

        $ineligible = $studentIds->diff($eligibleIds);
        if ($ineligible->isNotEmpty()) {
            return response()->json([
                'error' => [
                    'code' => 'STUDENT_NOT_ELIGIBLE',
                    'message' => 'Một số học viên không thuộc lớp này tại thời điểm buổi học.',
                    'details' => ['student_ids' => $ineligible->values()],
                ],
            ], 422);
        }

        foreach ($data['records'] as $record) {
            AttendanceRecord::updateOrCreate(
                ['lesson_session_id' => $lessonSession->id, 'student_id' => $record['student_id']],
                [
                    'status' => $record['status'],
                    'note' => $record['note'] ?? null,
                    'recorded_by_user_id' => $request->user()->id,
                ]
            );
        }

        return response()->json(['data' => $lessonSession->attendanceRecords()->get()->map(fn ($r) => [
            'student_id' => $r->student_id,
            'status' => $r->status,
        ])]);
    }
}
```

`app/Http/Controllers/Api/Attendance/UpdateAttendanceRecordController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AttendanceHistory;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class UpdateAttendanceRecordController extends Controller
{
    public function __invoke(Request $request, AttendanceRecord $attendanceRecord)
    {
        $data = $request->validate([
            'status' => ['required', 'in:present,absent,late,excused'],
            'note' => ['sometimes', 'nullable', 'string'],
            'reason' => ['required', 'string'],
        ]);

        $before = $attendanceRecord->only(['status', 'note']);

        $attendanceRecord->update([
            'status' => $data['status'],
            'note' => $data['note'] ?? $attendanceRecord->note,
            'recorded_by_user_id' => $request->user()->id,
        ]);

        AttendanceHistory::create([
            'attendance_record_id' => $attendanceRecord->id,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
            'reason' => $data['reason'],
            'before_data' => $before,
            'after_data' => $attendanceRecord->only(['status', 'note']),
        ]);

        return response()->json(['attendance_record' => [
            'id' => $attendanceRecord->id,
            'status' => $attendanceRecord->status,
            'note' => $attendanceRecord->note,
        ]]);
    }
}
```

- [ ] **Step 8: Sửa `CancelLessonSessionController.php` — thêm kiểm tra AttendanceRecord**

Thay toàn bộ nội dung `app/Http/Controllers/Api/Lesson/CancelLessonSessionController.php` (đã tạo ở Task 6) bằng:
```php
<?php

namespace App\Http\Controllers\Api\Lesson;

use App\Http\Controllers\Controller;
use App\Models\LessonSession;
use Illuminate\Http\Request;

class CancelLessonSessionController extends Controller
{
    public function __invoke(Request $request, LessonSession $lessonSession)
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string']]);

        // DEC-LRN-004: hủy buổi đã có Attendance không xóa Attendance, chỉ đánh dấu
        // review_required để Admin/Teacher rà soát lại sau.
        $hasAttendance = $lessonSession->attendanceRecords()->exists();

        $lessonSession->update([
            'status' => 'cancelled',
            'cancellation_reason' => $data['cancellation_reason'],
            'review_required' => $hasAttendance,
        ]);

        return response()->noContent();
    }
}
```

- [ ] **Step 9: Thêm route**

Thêm `'attendanceRecord'` vào mảng `Route::pattern`:
```php
foreach (['user', 'roleId', 'personProfile', 'parentStudentRelationship', 'student', 'parent', 'teacher', 'class', 'teacherId', 'studentId', 'subject', 'schedule', 'lessonSession', 'attendanceRecord'] as $uuidParam) {
```

Thêm vào nhóm `role:admin,teacher,student,parent`:
```php
Route::get('/lesson-sessions/{lessonSession}/attendance', \App\Http\Controllers\Api\Attendance\GetSessionAttendanceController::class);
```

Thêm vào nhóm `role:admin,teacher`:
```php
Route::put('/lesson-sessions/{lessonSession}/attendance', \App\Http\Controllers\Api\Attendance\BulkMarkAttendanceController::class);
Route::patch('/attendance-records/{attendanceRecord}', \App\Http\Controllers\Api\Attendance\UpdateAttendanceRecordController::class);
```

- [ ] **Step 10: Chạy lại test**

Chạy: `php artisan test --filter=AttendanceTest`
Kỳ vọng: PASS (4 test).

- [ ] **Step 11: Chạy lại test Task 6 để chắc nhánh "chưa có Attendance" vẫn đúng**

Chạy: `php artisan test --filter=LessonSessionTest`
Kỳ vọng: PASS.

- [ ] **Step 12: Commit**

```bash
git add app database routes
git commit -m "feat: add AttendanceRecord bulk mark and history-tracked updates"
```

---

### Task 9: `AbsenceExplanation`

**Files:**
- Tạo: `database/migrations/2026_10_14_000007_create_absence_explanations_table.php`
- Tạo: `app/Models/AbsenceExplanation.php`
- Tạo: `database/factories/AbsenceExplanationFactory.php`
- Tạo: `app/Http/Controllers/Api/Attendance/CreateAbsenceExplanationController.php`
- Tạo: `app/Http/Controllers/Api/Attendance/ListAbsenceExplanationsController.php`
- Tạo: `app/Http/Controllers/Api/Attendance/ReviewAbsenceExplanationController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Attendance/AbsenceExplanationTest.php`

**Interfaces:**
- Không có task sau phụ thuộc trực tiếp vào output của task này.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Attendance;

use App\Models\LessonSession;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceExplanationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsParent(ParentProfile $parent): User
    {
        $user = User::factory()->create(['person_profile_id' => $parent->person_profile_id]);
        $role = Role::factory()->create(['code' => 'parent']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'parent']);

        return $user;
    }

    private function actingAsTeacher(Teacher $teacher): User
    {
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_parent_can_submit_an_explanation_for_a_linked_child(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        ParentStudentRelationship::factory()->create(['parent_id' => $parent->id, 'student_id' => $student->id]);
        $session = LessonSession::factory()->create();
        $this->actingAsParent($parent);

        $response = $this->postJson('/api/v1/absence-explanations', [
            'student_id' => $student->id,
            'lesson_session_id' => $session->id,
            'message' => 'Con bị sốt, xin phép nghỉ học.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('absence_explanations', ['student_id' => $student->id, 'status' => 'submitted']);
    }

    public function test_parent_cannot_submit_for_an_unlinked_student(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        $session = LessonSession::factory()->create();
        $this->actingAsParent($parent);

        $response = $this->postJson('/api/v1/absence-explanations', [
            'student_id' => $student->id,
            'lesson_session_id' => $session->id,
            'message' => 'Xin phép nghỉ.',
        ]);

        $response->assertStatus(403);
    }

    public function test_teacher_in_scope_can_review_an_explanation(): void
    {
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $class->id]);
        $session = LessonSession::factory()->create(['class_id' => $class->id]);
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        ParentStudentRelationship::factory()->create(['parent_id' => $parent->id, 'student_id' => $student->id]);
        $this->actingAsParent($parent);
        $created = $this->postJson('/api/v1/absence-explanations', [
            'student_id' => $student->id,
            'lesson_session_id' => $session->id,
            'message' => 'Xin phép nghỉ.',
        ])->json('absence_explanation');

        $this->actingAsTeacher($teacher);
        $response = $this->patchJson("/api/v1/absence-explanations/{$created['id']}", [
            'status' => 'accepted',
            'review_note' => 'Đã xác nhận với phụ huynh.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('absence_explanations', ['id' => $created['id'], 'status' => 'accepted']);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=AbsenceExplanationTest` → FAIL.

- [ ] **Step 3: Tạo migration**

`database/migrations/2026_10_14_000007_create_absence_explanations_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_explanations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('lesson_session_id')->constrained('lesson_sessions')->cascadeOnDelete();
            $table->foreignUuid('attendance_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $table->text('message');
            $table->enum('status', ['submitted', 'under_review', 'accepted', 'rejected'])->default('submitted');
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_explanations');
    }
};
```

- [ ] **Step 4: Tạo model**

`app/Models/AbsenceExplanation.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsenceExplanation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'parent_id', 'student_id', 'lesson_session_id', 'attendance_record_id', 'message',
        'status', 'reviewed_by_user_id', 'reviewed_at', 'review_note',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function parent()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class);
    }
}
```

- [ ] **Step 5: Tạo factory**

`database/factories/AbsenceExplanationFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\AbsenceExplanation;
use App\Models\LessonSession;
use App\Models\ParentProfile;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsenceExplanationFactory extends Factory
{
    protected $model = AbsenceExplanation::class;

    public function definition(): array
    {
        return [
            'parent_id' => ParentProfile::factory(),
            'student_id' => Student::factory(),
            'lesson_session_id' => LessonSession::factory(),
            'message' => fake()->sentence(),
            'status' => 'submitted',
        ];
    }
}
```

- [ ] **Step 6: Tạo 3 controller**

`app/Http/Controllers/Api/Attendance/CreateAbsenceExplanationController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AbsenceExplanation;
use Illuminate\Http\Request;

class CreateAbsenceExplanationController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'lesson_session_id' => ['required', 'uuid', 'exists:lesson_sessions,id'],
            'message' => ['required', 'string'],
        ]);

        $parent = $request->user()->parentProfile;
        abort_unless(
            $parent && $parent->activeChildrenIds()->contains($data['student_id']),
            403,
            'Không có quyền gửi giải trình cho học viên này.'
        );

        $explanation = AbsenceExplanation::create($data + ['parent_id' => $parent->id, 'status' => 'submitted']);

        return response()->json(['absence_explanation' => [
            'id' => $explanation->id,
            'student_id' => $explanation->student_id,
            'lesson_session_id' => $explanation->lesson_session_id,
            'status' => $explanation->status,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/Attendance/ListAbsenceExplanationsController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AbsenceExplanation;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class ListAbsenceExplanationsController extends Controller
{
    public function __invoke(Request $request)
    {
        $activeRole = $request->session()->get('active_role_code');
        $query = AbsenceExplanation::query();

        if ($activeRole === 'parent') {
            $query->whereIn('student_id', $request->user()->parentProfile?->activeChildrenIds() ?? []);
        } elseif ($activeRole === 'teacher') {
            $classIds = ClassScope::classIdsFor($request->user(), 'teacher');
            $query->whereHas('lessonSession', fn ($q) => $q->whereIn('class_id', $classIds));
        }
        // Admin: không lọc, xem toàn bộ.

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->string('student_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->get()->map(fn ($e) => [
            'id' => $e->id,
            'student_id' => $e->student_id,
            'lesson_session_id' => $e->lesson_session_id,
            'message' => $e->message,
            'status' => $e->status,
        ])]);
    }
}
```

`app/Http/Controllers/Api/Attendance/ReviewAbsenceExplanationController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\AbsenceExplanation;
use App\Support\ClassScope;
use Illuminate\Http\Request;

class ReviewAbsenceExplanationController extends Controller
{
    public function __invoke(Request $request, AbsenceExplanation $absenceExplanation)
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole === 'teacher') {
            $classId = $absenceExplanation->lessonSession->class_id;
            abort_unless(ClassScope::classIdsFor($request->user(), 'teacher')->contains($classId), 403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:under_review,accepted,rejected'],
            'review_note' => ['nullable', 'string'],
        ]);

        $absenceExplanation->update($data + [
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['absence_explanation' => [
            'id' => $absenceExplanation->id,
            'status' => $absenceExplanation->status,
            'review_note' => $absenceExplanation->review_note,
        ]]);
    }
}
```

- [ ] **Step 7: Thêm route**

Thêm `'absenceExplanation'` vào mảng `Route::pattern`:
```php
foreach (['user', 'roleId', 'personProfile', 'parentStudentRelationship', 'student', 'parent', 'teacher', 'class', 'teacherId', 'studentId', 'subject', 'schedule', 'lessonSession', 'attendanceRecord', 'absenceExplanation'] as $uuidParam) {
```

Thêm vào nhóm `role:admin,teacher,student,parent`:
```php
Route::get('/absence-explanations', \App\Http\Controllers\Api\Attendance\ListAbsenceExplanationsController::class);
```

Thêm vào nhóm `role:parent`:
```php
Route::post('/absence-explanations', \App\Http\Controllers\Api\Attendance\CreateAbsenceExplanationController::class);
```

Thêm vào nhóm `role:admin,teacher`:
```php
Route::patch('/absence-explanations/{absenceExplanation}', \App\Http\Controllers\Api\Attendance\ReviewAbsenceExplanationController::class);
```

- [ ] **Step 8: Chạy lại test**

Chạy: `php artisan test --filter=AbsenceExplanationTest`
Kỳ vọng: PASS (3 test).

- [ ] **Step 9: Commit**

```bash
git add app database routes
git commit -m "feat: add AbsenceExplanation submit/list/review endpoints"
```

---

### Task 10: `Course` — model + CRUD

**Files:**
- Tạo: `database/migrations/2026_10_14_000008_create_courses_table.php`
- Tạo: `app/Models/Course.php`
- Sửa: `app/Models/Teacher.php` (thêm `ownedCourses()`)
- Tạo: `database/factories/CourseFactory.php`
- Tạo: `app/Http/Controllers/Api/Academic/ListCoursesController.php`
- Tạo: `app/Http/Controllers/Api/Academic/GetCourseController.php`
- Tạo: `app/Http/Controllers/Api/Academic/CreateCourseController.php`
- Tạo: `app/Http/Controllers/Api/Academic/UpdateCourseController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Academic/CourseTest.php`

**Interfaces:**
- Produces: `App\Models\Course` (cột: `id`, `name`, `description`, `subject_id` nullable, `owner_teacher_id` nullable, `status` `active/archived`, `archived_at`) — dùng bởi Task 11 (`CourseEnrollment.course_id`).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Academic;

use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    private function actingAsTeacher(Teacher $teacher): User
    {
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_teacher_can_create_an_owned_course_without_approval(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);

        $response = $this->postJson('/api/v1/courses', ['name' => 'Luyện thi học kỳ']);

        $response->assertCreated();
        $this->assertDatabaseHas('courses', ['name' => 'Luyện thi học kỳ', 'owner_teacher_id' => $teacher->id, 'status' => 'active']);
    }

    public function test_admin_can_create_a_shared_course(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/courses', ['name' => 'Ôn tập chung']);

        $response->assertCreated();
        $this->assertDatabaseHas('courses', ['name' => 'Ôn tập chung', 'owner_teacher_id' => null]);
    }

    public function test_teacher_cannot_edit_a_course_owned_by_another_teacher(): void
    {
        $owner = Teacher::factory()->create();
        $other = Teacher::factory()->create();
        $this->actingAsTeacher($owner);
        $course = $this->postJson('/api/v1/courses', ['name' => 'Riêng'])->json('course');

        $this->actingAsTeacher($other);
        $response = $this->patchJson("/api/v1/courses/{$course['id']}", ['name' => 'Đổi tên']);

        $response->assertStatus(403);
    }

    public function test_student_only_sees_courses_they_are_enrolled_in(): void
    {
        $student = Student::factory()->create();
        $enrolledCourse = Course::factory()->create(['name' => 'Đang học']);
        $otherCourse = Course::factory()->create(['name' => 'Không liên quan']);
        \App\Models\CourseEnrollment::factory()->create(['course_id' => $enrolledCourse->id, 'student_id' => $student->id]);
        $user = \App\Models\User::factory()->create(['person_profile_id' => $student->person_profile_id]);
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $response = $this->getJson('/api/v1/courses');

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Đang học'));
        $this->assertFalse($names->contains('Không liên quan'));
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=CourseTest` → FAIL (test cuối cũng fail vì `CourseEnrollment` chưa tồn tại — sẽ được Task 11 tạo; ở bước này tạm bỏ qua test đó bằng cách chạy riêng 3 test đầu).

Chạy: `php artisan test --filter=CourseTest --filter="test_teacher_can_create_an_owned_course_without_approval|test_admin_can_create_a_shared_course|test_teacher_cannot_edit_a_course_owned_by_another_teacher"` → FAIL (route chưa tồn tại).

- [ ] **Step 3: Tạo migration**

`database/migrations/2026_10_14_000008_create_courses_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignUuid('owner_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
```

- [ ] **Step 4: Tạo model**

`app/Models/Course.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'description', 'subject_id', 'owner_teacher_id', 'status', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function ownerTeacher()
    {
        return $this->belongsTo(Teacher::class, 'owner_teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
```

- [ ] **Step 5: Sửa `Teacher.php` — thêm relation**

```php
public function ownedCourses()
{
    return $this->hasMany(Course::class, 'owner_teacher_id');
}
```

- [ ] **Step 6: Tạo factory**

`database/factories/CourseFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'status' => 'active',
        ];
    }
}
```

- [ ] **Step 7: Tạo 4 controller**

`app/Http/Controllers/Api/Academic/ListCoursesController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;

class ListCoursesController extends Controller
{
    public function __invoke(Request $request)
    {
        $activeRole = $request->session()->get('active_role_code');
        $user = $request->user();

        $query = match ($activeRole) {
            'admin' => Course::query(),
            'teacher' => Course::where('owner_teacher_id', $user->teacherProfile?->id)
                ->orWhereNull('owner_teacher_id'),
            'student' => Course::whereIn('id', $this->activeEnrollmentCourseIds([$user->studentProfile?->id])),
            'parent' => Course::whereIn('id', $this->activeEnrollmentCourseIds(
                $user->parentProfile?->activeChildrenIds()->all() ?? []
            )),
            default => Course::whereRaw('1 = 0'),
        };

        return response()->json(['data' => $query->orderBy('name')->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'description' => $c->description,
            'owner_teacher_id' => $c->owner_teacher_id,
            'status' => $c->status,
        ])]);
    }

    private function activeEnrollmentCourseIds(array $studentIds)
    {
        return CourseEnrollment::whereIn('student_id', array_filter($studentIds))
            ->where('status', 'active')
            ->pluck('course_id')
            ->unique();
    }
}
```

`app/Http/Controllers/Api/Academic/GetCourseController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class GetCourseController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $activeRole = $request->session()->get('active_role_code');
        $user = $request->user();

        $inScope = match ($activeRole) {
            'admin' => true,
            'teacher' => $course->owner_teacher_id === $user->teacherProfile?->id || $course->owner_teacher_id === null,
            'student' => $course->enrollments()->where('student_id', $user->studentProfile?->id)->where('status', 'active')->exists(),
            'parent' => $course->enrollments()->whereIn('student_id', $user->parentProfile?->activeChildrenIds() ?? [])->where('status', 'active')->exists(),
            default => false,
        };

        if (! $inScope) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy Course.']], 404);
        }

        return response()->json(['course' => [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'subject_id' => $course->subject_id,
            'owner_teacher_id' => $course->owner_teacher_id,
            'status' => $course->status,
        ]]);
    }
}
```

`app/Http/Controllers/Api/Academic/CreateCourseController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CreateCourseController extends Controller
{
    public function __invoke(Request $request)
    {
        $activeRole = $request->session()->get('active_role_code');

        $rules = [
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'uuid', 'exists:subjects,id'],
        ];
        if ($activeRole === 'admin') {
            $rules['owner_teacher_id'] = ['nullable', 'uuid', 'exists:teachers,id'];
        }

        $data = $request->validate($rules);

        // BR-COURSE-004: Teacher tự tạo luôn là chủ sở hữu, không tin owner_teacher_id
        // từ client; Admin được chọn owner_teacher_id hoặc để trống (dùng chung).
        $ownerTeacherId = $activeRole === 'teacher'
            ? $request->user()->teacherProfile->id
            : ($data['owner_teacher_id'] ?? null);

        $course = Course::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
            'owner_teacher_id' => $ownerTeacherId,
            'status' => 'active',
        ]);

        return response()->json(['course' => [
            'id' => $course->id,
            'name' => $course->name,
            'owner_teacher_id' => $course->owner_teacher_id,
            'status' => $course->status,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/Academic/UpdateCourseController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class UpdateCourseController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole === 'teacher') {
            abort_unless($course->owner_teacher_id === $request->user()->teacherProfile?->id, 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'subject_id' => ['sometimes', 'nullable', 'uuid', 'exists:subjects,id'],
            'status' => ['sometimes', 'in:archived'],
        ]);

        $course->fill($data);
        if ($course->isDirty('status') && $course->status === 'archived') {
            $course->archived_at = now();
        }
        $course->save();

        return response()->json(['course' => [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'status' => $course->status,
        ]]);
    }
}
```

- [ ] **Step 8: Thêm route**

Thêm `'course'` vào mảng `Route::pattern`:
```php
foreach (['user', 'roleId', 'personProfile', 'parentStudentRelationship', 'student', 'parent', 'teacher', 'class', 'teacherId', 'studentId', 'subject', 'schedule', 'lessonSession', 'attendanceRecord', 'absenceExplanation', 'course'] as $uuidParam) {
```

Thêm vào nhóm `role:admin,teacher,student,parent`:
```php
Route::get('/courses', \App\Http\Controllers\Api\Academic\ListCoursesController::class);
Route::get('/courses/{course}', \App\Http\Controllers\Api\Academic\GetCourseController::class);
```

Thêm vào nhóm `role:admin,teacher`:
```php
Route::post('/courses', \App\Http\Controllers\Api\Academic\CreateCourseController::class);
Route::patch('/courses/{course}', \App\Http\Controllers\Api\Academic\UpdateCourseController::class);
```

- [ ] **Step 9: Chạy lại test (chỉ 3 test đầu, test thứ 4 cần Task 11)**

Chạy: `php artisan test --filter=CourseTest --filter="test_teacher_can_create_an_owned_course_without_approval|test_admin_can_create_a_shared_course|test_teacher_cannot_edit_a_course_owned_by_another_teacher"`
Kỳ vọng: PASS (3 test).

- [ ] **Step 10: Commit**

```bash
git add app database routes
git commit -m "feat: add Course model and CRUD endpoints"
```

---

### Task 11: `CourseEnrollment`

**Files:**
- Tạo: `database/migrations/2026_10_14_000009_create_course_enrollments_table.php`
- Tạo: `app/Models/CourseEnrollment.php`
- Sửa: `app/Models/Student.php` (thêm `courseEnrollments()`)
- Tạo: `database/factories/CourseEnrollmentFactory.php`
- Tạo: `app/Http/Controllers/Api/Academic/ListCourseEnrollmentsController.php`
- Tạo: `app/Http/Controllers/Api/Academic/EnrollInCourseController.php`
- Tạo: `app/Http/Controllers/Api/Academic/EndCourseEnrollmentController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/Academic/CourseEnrollmentTest.php`

**Interfaces:**
- Consumes: `App\Models\Course` (Task 10), `App\Models\ClassMember` (Plan 2, dùng cho enroll bulk theo `class_id`).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Academic;

use App\Models\ClassMember;
use App\Models\Course;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(Teacher $teacher): User
    {
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_owner_teacher_can_enroll_any_student_not_just_their_own_class(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);
        $course = $this->postJson('/api/v1/courses', ['name' => 'Luyện thi'])->json('course');
        $unrelatedStudent = Student::factory()->create(); // không thuộc lớp nào của teacher này

        $response = $this->postJson("/api/v1/courses/{$course['id']}/enrollments", ['student_id' => $unrelatedStudent->id]);

        $response->assertCreated();
        $this->assertDatabaseHas('course_enrollments', ['course_id' => $course['id'], 'student_id' => $unrelatedStudent->id, 'status' => 'active']);
    }

    public function test_non_owner_teacher_cannot_enroll_into_someone_elses_course(): void
    {
        $owner = Teacher::factory()->create();
        $other = Teacher::factory()->create();
        $this->actingAsTeacher($owner);
        $course = $this->postJson('/api/v1/courses', ['name' => 'Riêng'])->json('course');
        $student = Student::factory()->create();

        $this->actingAsTeacher($other);
        $response = $this->postJson("/api/v1/courses/{$course['id']}/enrollments", ['student_id' => $student->id]);

        $response->assertStatus(403);
    }

    public function test_bulk_enroll_by_class_id_skips_students_already_actively_enrolled(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);
        $course = $this->postJson('/api/v1/courses', ['name' => 'Luyện thi'])->json('course');
        $class = SchoolClass::factory()->create();
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();
        ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $studentA->id]);
        ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $studentB->id]);
        $this->postJson("/api/v1/courses/{$course['id']}/enrollments", ['student_id' => $studentA->id]);

        $response = $this->postJson("/api/v1/courses/{$course['id']}/enrollments", ['class_id' => $class->id]);

        $response->assertCreated();
        $this->assertSame(1, $response->json('created_count'));
        $this->assertSame(1, \App\Models\CourseEnrollment::where('course_id', $course['id'])
            ->where('student_id', $studentA->id)->count());
    }

    public function test_ending_an_enrollment_requires_a_reason(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);
        $course = $this->postJson('/api/v1/courses', ['name' => 'Luyện thi'])->json('course');
        $student = Student::factory()->create();
        $this->postJson("/api/v1/courses/{$course['id']}/enrollments", ['student_id' => $student->id]);

        $response = $this->deleteJson("/api/v1/courses/{$course['id']}/enrollments/{$student->id}", []);
        $response->assertStatus(422);

        $response = $this->deleteJson("/api/v1/courses/{$course['id']}/enrollments/{$student->id}", ['end_reason' => 'Học xong khóa']);
        $response->assertNoContent();
        $this->assertDatabaseHas('course_enrollments', ['course_id' => $course['id'], 'student_id' => $student->id, 'status' => 'ended']);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=CourseEnrollmentTest` → FAIL.

- [ ] **Step 3: Tạo migration**

`database/migrations/2026_10_14_000009_create_course_enrollments_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->foreignUuid('enrolled_by_user_id')->constrained('users');
            $table->foreignUuid('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
```

- [ ] **Step 4: Tạo model**

`app/Models/CourseEnrollment.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'course_id', 'student_id', 'status', 'valid_from', 'valid_to',
        'enrolled_by_user_id', 'ended_by_user_id', 'end_reason',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
```

- [ ] **Step 5: Sửa `Student.php` — thêm relation**

```php
public function courseEnrollments()
{
    return $this->hasMany(CourseEnrollment::class);
}
```

- [ ] **Step 6: Tạo factory**

`database/factories/CourseEnrollmentFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseEnrollmentFactory extends Factory
{
    protected $model = CourseEnrollment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'student_id' => Student::factory(),
            'status' => 'active',
            'valid_from' => now(),
            'enrolled_by_user_id' => User::factory(),
        ];
    }
}
```

- [ ] **Step 7: Tạo 3 controller**

`app/Http/Controllers/Api/Academic/ListCourseEnrollmentsController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class ListCourseEnrollmentsController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $activeRole = $request->session()->get('active_role_code');
        $user = $request->user();

        $query = $course->enrollments();

        if ($activeRole === 'teacher') {
            abort_unless($course->owner_teacher_id === $user->teacherProfile?->id, 403);
        } elseif ($activeRole === 'student') {
            $query->where('student_id', $user->studentProfile?->id);
        } elseif ($activeRole === 'parent') {
            $query->whereIn('student_id', $user->parentProfile?->activeChildrenIds() ?? []);
        }
        // Admin: không lọc thêm.

        return response()->json(['data' => $query->get()->map(fn ($e) => [
            'student_id' => $e->student_id,
            'status' => $e->status,
            'valid_from' => $e->valid_from->toIso8601String(),
            'valid_to' => $e->valid_to?->toIso8601String(),
        ])]);
    }
}
```

`app/Http/Controllers/Api/Academic/EnrollInCourseController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;

class EnrollInCourseController extends Controller
{
    public function __invoke(Request $request, Course $course)
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole === 'teacher') {
            abort_unless($course->owner_teacher_id === $request->user()->teacherProfile?->id, 403);
        }

        $data = $request->validate([
            'student_id' => ['required_without:class_id', 'prohibits:class_id', 'uuid', 'exists:students,id'],
            'class_id' => ['required_without:student_id', 'prohibits:student_id', 'uuid', 'exists:classes,id'],
        ]);

        $studentIds = isset($data['student_id'])
            ? [$data['student_id']]
            : ClassMember::where('class_id', $data['class_id'])->where('status', 'active')->pluck('student_id')->all();

        // BR-COURSE-004: không giới hạn Student/Class thuộc phạm vi Teacher — bất kỳ
        // student_id/class_id hợp lệ nào cũng được chấp nhận cho Teacher sở hữu Course.
        $alreadyEnrolled = CourseEnrollment::where('course_id', $course->id)
            ->where('status', 'active')
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id');

        $toCreate = array_diff($studentIds, $alreadyEnrolled->all());
        $createdCount = 0;

        foreach ($toCreate as $studentId) {
            CourseEnrollment::create([
                'course_id' => $course->id,
                'student_id' => $studentId,
                'status' => 'active',
                'valid_from' => now(),
                'enrolled_by_user_id' => $request->user()->id,
            ]);
            $createdCount++;
        }

        return response()->json(['created_count' => $createdCount], 201);
    }
}
```

`app/Http/Controllers/Api/Academic/EndCourseEnrollmentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;

class EndCourseEnrollmentController extends Controller
{
    public function __invoke(Request $request, Course $course, string $studentId)
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole === 'teacher') {
            abort_unless($course->owner_teacher_id === $request->user()->teacherProfile?->id, 403);
        }

        $data = $request->validate(['end_reason' => ['required', 'string']]);

        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->firstOrFail();

        $enrollment->update([
            'status' => 'ended',
            'valid_to' => now(),
            'ended_by_user_id' => $request->user()->id,
            'end_reason' => $data['end_reason'],
        ]);

        return response()->noContent();
    }
}
```

- [ ] **Step 8: Thêm route**

Trong nhóm `role:admin,teacher,student,parent`:
```php
Route::get('/courses/{course}/enrollments', \App\Http\Controllers\Api\Academic\ListCourseEnrollmentsController::class);
```

Trong nhóm `role:admin,teacher`:
```php
Route::post('/courses/{course}/enrollments', \App\Http\Controllers\Api\Academic\EnrollInCourseController::class);
Route::delete('/courses/{course}/enrollments/{studentId}', \App\Http\Controllers\Api\Academic\EndCourseEnrollmentController::class);
```

- [ ] **Step 9: Chạy lại test**

Chạy: `php artisan test --filter=CourseEnrollmentTest`
Kỳ vọng: PASS (4 test).

- [ ] **Step 10: Chạy lại test thứ 4 còn thiếu của `CourseTest` (Task 10) — giờ `CourseEnrollment` đã tồn tại**

Chạy: `php artisan test --filter=CourseTest`
Kỳ vọng: PASS (4 test).

- [ ] **Step 11: Chạy toàn bộ test suite của plan này + Plan 1/2 để chắc không có gì vỡ**

Chạy: `php artisan test`
Kỳ vọng: toàn bộ PASS.

- [ ] **Step 12: Commit**

```bash
git add app database routes
git commit -m "feat: add CourseEnrollment with individual and class-bulk enroll"
```

---
