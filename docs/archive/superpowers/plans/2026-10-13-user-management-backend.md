# Plan triển khai User Management Backend

> **Dành cho agent thực thi:** REQUIRED SUB-SKILL: dùng `superpowers:subagent-driven-development` (khuyến nghị) hoặc `superpowers:executing-plans` để triển khai plan này theo từng task. Các step dùng cú pháp checkbox (`- [ ]`) để theo dõi tiến độ.

**Mục tiêu:** Triển khai các endpoint User Management (PersonProfile, Student/Parent/Teacher profile, User/Role quản trị, Parent–Student relationship, ClassMember, TeacherClassAssignment) theo Section 4-8 của spec đã duyệt, tiếp nối Plan 1 (Identity/Auth, đã xong).

**Kiến trúc:** REST API Laravel 11 dưới `/api/v1`, dùng lại Sanctum cookie session + `EnsureUserIsActive` middleware từ Plan 1. Thêm middleware mới `EnsureRole` để chặn theo `active_role_code` lưu trong session (đã ghi bởi `ContextController` ở Plan 1). Không có code frontend — đó là Plan 2b.

**Tech Stack:** Laravel 11.x, PHP 8.2+, PostgreSQL 16, PHPUnit. Dev chạy qua Docker (`piclass-infra`), **không** còn chạy host-based.

**Spec:** `docs/archive/superpowers/specs/2026-10-12-identity-auth-user-management-api-design.md` — Section 4-8 (Section 1-3 đã làm ở Plan 1).

**Vị trí code:** mọi file trong plan này nằm trong repo riêng `piclass-api/` (sibling dir của repo docs). Đường dẫn file ở dưới là tương đối so với gốc `piclass-api/`.

**Chạy lệnh (Docker, không chạy host-based):** mọi `php artisan ...` dưới đây thực thi qua:
```bash
cd piclass-infra/local && docker compose exec -u www-data api php artisan test --filter=TenTest
```
(xem `piclass-infra/local/Makefile`, target `test-api`/`migrate`). Để ngắn, các step phía dưới chỉ ghi lệnh `php artisan ...` thuần — áp dụng wrapper trên khi chạy thật. Lệnh `git`/`composer` chạy trực tiếp trong `piclass-api/` (repo riêng, không qua Docker).

## Global Constraints

- Base path API `/api/v1`; field JSON `snake_case`; response lỗi `{"error":{"code","message"}}` (đã có từ Plan 1, `bootstrap/app.php`).
- Mọi entity nghiệp vụ dùng UUID PK (`HasUuids`).
- Permission gate: `EnsureUserIsActive` (đã có) → `EnsureRole:<codes>` (Task 2, mới) → scope cụ thể theo resource (kiểm tra inline trong controller) → lifecycle/visibility.
- List và get-one dùng cùng scope; biết ID không cấp quyền.
- BR-USER-006: Student/Teacher/Parent tự sửa `email`/`phone` của chính mình; không tự sửa `full_name`/`status`/role profile/relationship.
- BR-USER-007: không được vô hiệu hóa Admin cuối cùng còn quyền quản trị → `409`.
- BR-USER-010: trước khi vô hiệu hóa Teacher còn `TeacherClassAssignment` hiệu lực → `422` kèm danh sách `class_id`.
- BR-USER-012/014: `ParentStudentRelationship` do Admin tạo+confirm; unlink set `status=ended`+`valid_to`+`end_reason` bắt buộc, không xóa hàng.
- BR-USER-016/017: `ClassMember` hiệu lực theo thời gian; sửa hồi tố chỉ Admin, bắt buộc `reason`.
- Role code cố định `admin`/`teacher`/`student`/`parent` (seed từ Plan 1, `RoleSeeder`).
- **Quyết định phát sinh (Task 1):** `Class`/`Subject` thuộc module Academic (nhóm tiếp theo, chưa code), nhưng `ClassMember`/`TeacherClassAssignment` cần FK tới `classes`. Task 1 tạo migration/model **tối giản** cho `Subject`/`Class` (đúng cột đã chốt ở `docs/database/schema.md`, không có endpoint/CRUD/business logic) làm prerequisite. Plan Academic sau sẽ thêm hành vi lên trên, không migrate lại.
- **Quyết định đặt tên (do PHP reserved word):** PHP không cho đặt class tên `Class` hoặc `Parent` (cả hai là từ khóa dành riêng). Model tương ứng đặt tên `App\Models\SchoolClass` (`protected $table = 'classes'`) và `App\Models\ParentProfile` (`protected $table = 'parents'`). Tên bảng, route, JSON field vẫn giữ nguyên theo spec/schema (`classes`, `parents`, `/api/v1/parents`...) — chỉ tên class PHP khác.
- **Giới hạn đã biết, ghi lại chủ ý (không phải thiếu sót):** `PATCH /classes/{class_id}/members/{student_id}` (Task 8) yêu cầu `reason` bắt buộc trong request (đúng BR-USER-017), nhưng **chưa ghi vào `AuditLog`** vì bảng đó thuộc module Audit (nhóm 5, chưa code). Khi làm nhóm Notification+File+Audit, bổ sung ghi `AuditLog` cho action này — không để quên, ghi chú lại ở `docs/progress.md` sau khi plan này chạy xong.

## Review Focus

1. Gọi endpoint role-gated khi chưa chọn `active_role_code` (chưa gọi `/auth/context`) phải trả `403 FORBIDDEN`, không phải lỗi 500 hay bỏ qua check — Task 2.
2. Role đã bị kết thúc (`UserRole.valid_to` ở quá khứ) sau khi đã chọn context vẫn phải bị chặn ở request tiếp theo — không tin session cũ — Task 2.
3. Admin cố vô hiệu hóa chính Admin cuối cùng còn quyền quản trị phải bị chặn `409`, không được thực hiện — Task 12.
4. Vô hiệu hóa Teacher còn `TeacherClassAssignment` hiệu lực phải bị chặn `422` kèm danh sách lớp, không cho qua — Task 12.
5. `GET /api/v1/me/children/{student_id}` với `student_id` không liên kết (chưa từng, hoặc đã unlink) phải trả `404`, không phải `403` — tránh lộ việc "đã từng là con của Parent này" — Task 17.

---

### Task 1: Migration/model tối giản cho Subject + Class (prerequisite FK)

**Files:**
- Tạo: `database/migrations/2026_10_13_000001_create_subjects_table.php`
- Tạo: `database/migrations/2026_10_13_000002_create_classes_table.php`
- Tạo: `app/Models/Subject.php`
- Tạo: `app/Models/SchoolClass.php`
- Tạo: `database/factories/SubjectFactory.php`
- Tạo: `database/factories/SchoolClassFactory.php`
- Tạo: `tests/Feature/Models/SubjectAndClassPrerequisiteTest.php`

**Interfaces:**
- Produces: `App\Models\Subject`, `App\Models\SchoolClass` (table `classes`, PK `id` UUID) — dùng làm FK bởi Task 7 (`TeacherClassAssignment`) và Task 8 (`ClassMember`). **Không có controller/route** — CRUD thật thuộc plan Academic sau.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Models;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectAndClassPrerequisiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_subject_and_a_class_under_it(): void
    {
        $subject = Subject::factory()->create();
        $class = SchoolClass::factory()->create(['subject_id' => $subject->id]);

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'subject_id' => $subject->id,
            'status' => 'preparing',
        ]);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=SubjectAndClassPrerequisiteTest` → FAIL (`Class "App\Models\Subject" not found`).

- [ ] **Step 3: Tạo migration `subjects`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
```

- [ ] **Step 4: Tạo migration `classes`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('description')->nullable();
            $table->enum('status', ['preparing', 'active', 'archived'])->default('preparing');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
```

- [ ] **Step 5: Tạo model `Subject`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'description', 'status', 'archived_at'];
}
```

- [ ] **Step 6: Tạo model `SchoolClass`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'classes';

    protected $fillable = ['subject_id', 'name', 'label', 'description', 'status', 'archived_at'];
}
```

- [ ] **Step 7: Tạo factory `SubjectFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'status' => 'active',
        ];
    }
}
```

- [ ] **Step 8: Tạo factory `SchoolClassFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'name' => fake()->unique()->word(),
            'status' => 'preparing',
        ];
    }
}
```

- [ ] **Step 9: Chạy migration + test**

```bash
php artisan migrate
php artisan test --filter=SubjectAndClassPrerequisiteTest
```
Kỳ vọng: PASS.

- [ ] **Step 10: Commit**

```bash
git add database app
git commit -m "feat: add minimal Subject/Class schema as a prerequisite for ClassMember/TeacherClassAssignment FKs"
```

---

### Task 2: Middleware `EnsureRole` (chặn theo active role context)

**Files:**
- Tạo: `app/Http/Middleware/EnsureRole.php`
- Sửa: `bootstrap/app.php` (đăng ký alias `role`)
- Tạo: `tests/Feature/Auth/EnsureRoleMiddlewareTest.php`

**Interfaces:**
- Dùng lại: `User::hasActiveRole(string $roleCode): bool` (Plan 1), session key `active_role_code` (ghi bởi `ContextController`, Plan 1).
- Produces: middleware alias `role:<code1>,<code2>,...` — mọi route Admin/Teacher-only ở các Task sau dùng middleware này để gate theo `active_role_code` của session hiện tại, không phải toàn bộ role mà User có.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsureRole;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', EnsureRole::class.':admin'])
            ->get('/api/v1/__test/admin-only', fn () => response()->json(['ok' => true]));
    }

    public function test_allows_user_whose_active_role_matches(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'admin'])
            ->getJson('/api/v1/__test/admin-only');

        $response->assertOk();
    }

    public function test_rejects_when_no_active_role_context_is_set(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/__test/admin-only');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_rejects_when_active_role_is_not_in_the_allowed_list(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'teacher'])
            ->getJson('/api/v1/__test/admin-only');

        $response->assertStatus(403);
    }

    public function test_rejects_when_the_role_assignment_has_since_ended(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'admin'])
            ->getJson('/api/v1/__test/admin-only');

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=EnsureRoleMiddlewareTest` → FAIL (`Class "App\Http\Middleware\EnsureRole" not found`).

- [ ] **Step 3: Tạo middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $activeRole = $request->session()->get('active_role_code');

        if (! $activeRole || ! in_array($activeRole, $roles, true) || ! $request->user()->hasActiveRole($activeRole)) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Bạn không có quyền thực hiện hành động này.'],
            ], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Đăng ký alias trong `bootstrap/app.php`**

Trong `->withMiddleware(function (Middleware $middleware) { ... })`, thêm sau khối `$middleware->api(prepend: [...]);` đã có:
```php
$middleware->alias([
    'role' => \App\Http\Middleware\EnsureRole::class,
]);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=EnsureRoleMiddlewareTest`
Kỳ vọng: PASS (cả 4 test method).

- [ ] **Step 6: Commit**

```bash
git add app bootstrap
git commit -m "feat: add EnsureRole middleware gating routes by active session role"
```

---

### Task 3: Model Student/ParentProfile/Teacher + factories

**Files:**
- Tạo: `database/migrations/2026_10_13_000003_create_students_table.php`
- Tạo: `database/migrations/2026_10_13_000004_create_parents_table.php`
- Tạo: `database/migrations/2026_10_13_000005_create_teachers_table.php`
- Tạo: `app/Models/Student.php`
- Tạo: `app/Models/ParentProfile.php`
- Tạo: `app/Models/Teacher.php`
- Sửa: `app/Models/PersonProfile.php` (thêm `HasFactory`)
- Tạo: `database/factories/PersonProfileFactory.php`
- Tạo: `database/factories/StudentFactory.php`
- Tạo: `database/factories/ParentProfileFactory.php`
- Tạo: `database/factories/TeacherFactory.php`
- Tạo: `tests/Feature/Models/RoleProfileModelsTest.php`

**Interfaces:**
- Produces: `App\Models\Student`, `App\Models\ParentProfile` (table `parents`), `App\Models\Teacher`, mỗi model `belongsTo(PersonProfile::class)` qua `personProfile()`. Dùng bởi mọi Task từ 4 đến 17.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Models;

use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleProfileModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_a_student_profile_with_uuid(): void
    {
        $student = Student::factory()->create();

        $this->assertTrue(Str::isUuid($student->id));
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'active']);
    }

    public function test_can_create_a_parent_profile(): void
    {
        $parent = ParentProfile::factory()->create();

        $this->assertDatabaseHas('parents', ['id' => $parent->id, 'status' => 'active']);
    }

    public function test_can_create_a_teacher_profile_with_a_specialty(): void
    {
        $teacher = Teacher::factory()->create(['specialty' => 'Toán']);

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'specialty' => 'Toán']);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=RoleProfileModelsTest` → FAIL.

- [ ] **Step 3: Tạo 3 migration**

`database/migrations/2026_10_13_000003_create_students_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_profile_id')->constrained('person_profiles')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
```

`database/migrations/2026_10_13_000004_create_parents_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_profile_id')->constrained('person_profiles')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
```

`database/migrations/2026_10_13_000005_create_teachers_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('person_profile_id')->constrained('person_profiles')->cascadeOnDelete();
            $table->string('specialty')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
```

- [ ] **Step 4: Tạo 3 model**

`app/Models/Student.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['person_profile_id', 'status', 'notes', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function personProfile()
    {
        return $this->belongsTo(PersonProfile::class);
    }
}
```

`app/Models/ParentProfile.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'parents';

    protected $fillable = ['person_profile_id', 'status', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function personProfile()
    {
        return $this->belongsTo(PersonProfile::class);
    }
}
```

`app/Models/Teacher.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['person_profile_id', 'specialty', 'status', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function personProfile()
    {
        return $this->belongsTo(PersonProfile::class);
    }
}
```

- [ ] **Step 5: Thêm `HasFactory` vào `PersonProfile`**

Thay toàn bộ `app/Models/PersonProfile.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['full_name', 'email', 'phone'];
}
```

- [ ] **Step 6: Tạo 4 factory**

`database/factories/PersonProfileFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\PersonProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonProfileFactory extends Factory
{
    protected $model = PersonProfile::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
```

`database/factories/StudentFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\PersonProfile;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'person_profile_id' => PersonProfile::factory(),
            'status' => 'active',
        ];
    }
}
```

`database/factories/ParentProfileFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\ParentProfile;
use App\Models\PersonProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParentProfileFactory extends Factory
{
    protected $model = ParentProfile::class;

    public function definition(): array
    {
        return [
            'person_profile_id' => PersonProfile::factory(),
            'status' => 'active',
        ];
    }
}
```

`database/factories/TeacherFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\PersonProfile;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'person_profile_id' => PersonProfile::factory(),
            'specialty' => null,
            'status' => 'active',
        ];
    }
}
```

- [ ] **Step 7: Chạy migration + test**

```bash
php artisan migrate
php artisan test --filter=RoleProfileModelsTest
```
Kỳ vọng: PASS.

- [ ] **Step 8: Commit**

```bash
git add app database
git commit -m "feat: add Student, ParentProfile, Teacher role profile models"
```

---

### Task 4: `POST /api/v1/students`, `/api/v1/parents`, `/api/v1/teachers` (Admin tạo hồ sơ)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/CreateStudentController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/CreateParentController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/CreateTeacherController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/CreateRoleProfileTest.php`

**Interfaces:**
- Dùng lại: `PersonProfile`, `Student`, `ParentProfile`, `Teacher` (Task 3); middleware `role:admin` (Task 2).
- Produces: route group `Route::prefix('v1')->middleware(['auth:sanctum', EnsureUserIsActive::class])->group(...)` giờ có thêm nhánh con `->middleware('role:admin')` — mọi Task 5, 6, 7 (phần POST/DELETE), 8 (phần POST/DELETE/PATCH), 10-14, 16 nested tiếp vào đúng nhánh `role:admin` này.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRoleProfileTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        return $user;
    }

    public function test_admin_can_create_a_student_profile(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/students', [
            'full_name' => 'Nguyen Van A',
            'email' => 'a@example.com',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('student.person_profile.full_name', 'Nguyen Van A');
        $this->assertDatabaseHas('students', ['status' => 'active']);
    }

    public function test_admin_can_create_a_parent_profile(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/parents', ['full_name' => 'Tran Thi B']);

        $response->assertCreated();
        $this->assertDatabaseHas('parents', ['status' => 'active']);
    }

    public function test_admin_can_create_a_teacher_profile_with_specialty(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/teachers', [
            'full_name' => 'Le Van C',
            'specialty' => 'Toán',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('teachers', ['specialty' => 'Toán']);
    }

    public function test_non_admin_cannot_create_a_student_profile(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'teacher'])
            ->postJson('/api/v1/students', ['full_name' => 'Nguyen Van A']);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=CreateRoleProfileTest` → FAIL (route chưa tồn tại).

- [ ] **Step 3: Tạo 3 controller**

`app/Http/Controllers/Api/UserManagement/CreateStudentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\PersonProfile;
use App\Models\Student;
use Illuminate\Http\Request;

class CreateStudentController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string'],
            'email' => ['nullable', 'string', 'email'],
            'phone' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $personProfile = PersonProfile::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        $student = Student::create([
            'person_profile_id' => $personProfile->id,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json(['student' => [
            'id' => $student->id,
            'status' => $student->status,
            'notes' => $student->notes,
            'person_profile' => [
                'id' => $personProfile->id,
                'full_name' => $personProfile->full_name,
                'email' => $personProfile->email,
                'phone' => $personProfile->phone,
            ],
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/UserManagement/CreateParentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentProfile;
use App\Models\PersonProfile;
use Illuminate\Http\Request;

class CreateParentController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string'],
            'email' => ['nullable', 'string', 'email'],
            'phone' => ['nullable', 'string'],
        ]);

        $personProfile = PersonProfile::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        $parent = ParentProfile::create([
            'person_profile_id' => $personProfile->id,
            'status' => 'active',
        ]);

        return response()->json(['parent' => [
            'id' => $parent->id,
            'status' => $parent->status,
            'person_profile' => [
                'id' => $personProfile->id,
                'full_name' => $personProfile->full_name,
                'email' => $personProfile->email,
                'phone' => $personProfile->phone,
            ],
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/UserManagement/CreateTeacherController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\PersonProfile;
use App\Models\Teacher;
use Illuminate\Http\Request;

class CreateTeacherController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string'],
            'email' => ['nullable', 'string', 'email'],
            'phone' => ['nullable', 'string'],
            'specialty' => ['nullable', 'string'],
        ]);

        $personProfile = PersonProfile::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        $teacher = Teacher::create([
            'person_profile_id' => $personProfile->id,
            'specialty' => $data['specialty'] ?? null,
            'status' => 'active',
        ]);

        return response()->json(['teacher' => [
            'id' => $teacher->id,
            'status' => $teacher->status,
            'specialty' => $teacher->specialty,
            'person_profile' => [
                'id' => $personProfile->id,
                'full_name' => $personProfile->full_name,
                'email' => $personProfile->email,
                'phone' => $personProfile->phone,
            ],
        ]], 201);
    }
}
```

- [ ] **Step 4: Thêm route**

Thay toàn bộ nội dung `routes/api.php` bằng (giữ các route Plan 1, thêm nhánh `role:admin` mới):
```php
<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\UserManagement\CreateParentController;
use App\Http\Controllers\Api\UserManagement\CreateStudentController;
use App\Http\Controllers\Api\UserManagement\CreateTeacherController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// Ngoài /api/v1: dùng cho Docker healthcheck (piclass-infra) và các công cụ
// giám sát bên ngoài — không versioned, không business logic.
Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', LoginController::class);
    Route::post('/auth/password/forgot', \App\Http\Controllers\Api\Auth\ForgotPasswordController::class);
    Route::post('/auth/password/reset', \App\Http\Controllers\Api\Auth\ResetPasswordController::class);

    Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
        Route::post('/auth/logout', \App\Http\Controllers\Api\Auth\LogoutController::class);
        Route::get('/auth/me', \App\Http\Controllers\Api\Auth\MeController::class);
        Route::post('/auth/context', \App\Http\Controllers\Api\Auth\ContextController::class);
        Route::patch('/me/password', \App\Http\Controllers\Api\Auth\ChangePasswordController::class);

        Route::middleware('role:admin')->group(function () {
            Route::post('/students', CreateStudentController::class);
            Route::post('/parents', CreateParentController::class);
            Route::post('/teachers', CreateTeacherController::class);
        });
    });
});
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=CreateRoleProfileTest`
Kỳ vọng: PASS (cả 4 test method).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add admin-only endpoints to create Student/Parent/Teacher profiles"
```

---

### Task 5: `PATCH /api/v1/person-profiles/{id}` (Admin sửa full_name/contact chung)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/UpdatePersonProfileController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/UpdatePersonProfileTest.php`

**Interfaces:**
- Dùng lại: `PersonProfile` (Task 3), nhánh route `role:admin` (Task 4).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePersonProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_full_name_and_contact(): void
    {
        $admin = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $personProfile = PersonProfile::factory()->create(['full_name' => 'Old Name']);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_code' => 'admin'])
            ->patchJson("/api/v1/person-profiles/{$personProfile->id}", [
                'full_name' => 'New Name',
                'phone' => '0900000000',
            ]);

        $response->assertOk();
        $response->assertJsonPath('person_profile.full_name', 'New Name');
        $this->assertDatabaseHas('person_profiles', ['id' => $personProfile->id, 'full_name' => 'New Name']);
    }

    public function test_non_admin_cannot_update_a_person_profile(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $personProfile = PersonProfile::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'teacher'])
            ->patchJson("/api/v1/person-profiles/{$personProfile->id}", ['full_name' => 'New Name']);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=UpdatePersonProfileTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\PersonProfile;
use Illuminate\Http\Request;

class UpdatePersonProfileController extends Controller
{
    public function __invoke(Request $request, PersonProfile $personProfile)
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'nullable', 'string', 'email'],
            'phone' => ['sometimes', 'nullable', 'string'],
        ]);

        $personProfile->fill($data)->save();

        return response()->json(['person_profile' => [
            'id' => $personProfile->id,
            'full_name' => $personProfile->full_name,
            'email' => $personProfile->email,
            'phone' => $personProfile->phone,
        ]]);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `Route::middleware('role:admin')->group(function () { ... })` ở `routes/api.php` (thêm ở Task 4), thêm:
```php
Route::patch('/person-profiles/{personProfile}', \App\Http\Controllers\Api\UserManagement\UpdatePersonProfileController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=UpdatePersonProfileTest`
Kỳ vọng: PASS.

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add admin-only PATCH /api/v1/person-profiles/{id}"
```

---

### Task 6: `PATCH /api/v1/students/{id}`, `/parents/{id}`, `/teachers/{id}` (Admin sửa field riêng vai trò)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/UpdateStudentController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/UpdateParentController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/UpdateTeacherController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/UpdateRoleProfileTest.php`

**Interfaces:**
- Dùng lại: `Student`, `ParentProfile`, `Teacher` (Task 3), nhánh route `role:admin` (Task 4).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateRoleProfileTest extends TestCase
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

    public function test_admin_can_update_student_status_and_notes(): void
    {
        $this->actingAsAdmin();
        $student = Student::factory()->create(['status' => 'active']);

        $response = $this->patchJson("/api/v1/students/{$student->id}", [
            'status' => 'inactive',
            'notes' => 'Chuyển trường',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'inactive', 'notes' => 'Chuyển trường']);
    }

    public function test_admin_can_update_parent_status(): void
    {
        $this->actingAsAdmin();
        $parent = ParentProfile::factory()->create(['status' => 'active']);

        $response = $this->patchJson("/api/v1/parents/{$parent->id}", ['status' => 'inactive']);

        $response->assertOk();
        $this->assertDatabaseHas('parents', ['id' => $parent->id, 'status' => 'inactive']);
    }

    public function test_admin_can_update_teacher_specialty(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create(['specialty' => 'Toán']);

        $response = $this->patchJson("/api/v1/teachers/{$teacher->id}", ['specialty' => 'Lý']);

        $response->assertOk();
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'specialty' => 'Lý']);
    }

    public function test_non_admin_cannot_update_a_student_profile(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $student = Student::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'student'])
            ->patchJson("/api/v1/students/{$student->id}", ['status' => 'inactive']);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=UpdateRoleProfileTest` → FAIL.

- [ ] **Step 3: Tạo 3 controller**

`app/Http/Controllers/Api/UserManagement/UpdateStudentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class UpdateStudentController extends Controller
{
    public function __invoke(Request $request, Student $student)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:active,inactive'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $student->fill($data)->save();

        return response()->json(['student' => [
            'id' => $student->id,
            'status' => $student->status,
            'notes' => $student->notes,
        ]]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/UpdateParentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentProfile;
use Illuminate\Http\Request;

class UpdateParentController extends Controller
{
    public function __invoke(Request $request, ParentProfile $parent)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $parent->fill($data)->save();

        return response()->json(['parent' => [
            'id' => $parent->id,
            'status' => $parent->status,
        ]]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/UpdateTeacherController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class UpdateTeacherController extends Controller
{
    public function __invoke(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:active,inactive'],
            'specialty' => ['sometimes', 'nullable', 'string'],
        ]);

        $teacher->fill($data)->save();

        return response()->json(['teacher' => [
            'id' => $teacher->id,
            'status' => $teacher->status,
            'specialty' => $teacher->specialty,
        ]]);
    }
}
```

**Lưu ý route-model binding:** route param `{parent}` sẽ tự resolve qua `App\Models\ParentProfile` chỉ nếu Laravel biết ánh xạ — vì tên model không khớp tên biến theo convention mặc định (`parent` → `Parent`, không phải `ParentProfile`), cần binding rõ ràng ở Step 4 bằng `Route::model('parent', ParentProfile::class)` (xem dưới), không type-hint suông.

- [ ] **Step 4: Thêm route (và binding `parent` → `ParentProfile`)**

Trong `routes/api.php`, ngay sau `Route::prefix('v1')->group(function () {`, thêm:
```php
Route::model('parent', \App\Models\ParentProfile::class);
```

Trong block `Route::middleware('role:admin')->group(function () { ... })`, thêm:
```php
Route::patch('/students/{student}', \App\Http\Controllers\Api\UserManagement\UpdateStudentController::class);
Route::patch('/parents/{parent}', \App\Http\Controllers\Api\UserManagement\UpdateParentController::class);
Route::patch('/teachers/{teacher}', \App\Http\Controllers\Api\UserManagement\UpdateTeacherController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=UpdateRoleProfileTest`
Kỳ vọng: PASS (cả 4 test method).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add admin-only PATCH endpoints for Student/Parent/Teacher role fields"
```

---

### Task 7: `TeacherClassAssignment` — model + endpoint (`/classes/{class_id}/teachers`)

**Files:**
- Tạo: `database/migrations/2026_10_13_000006_create_teacher_class_assignments_table.php`
- Tạo: `app/Models/TeacherClassAssignment.php`
- Sửa: `app/Models/Teacher.php` (thêm `classAssignments()`, `activeClassIds()`)
- Sửa: `app/Models/SchoolClass.php` (thêm `teacherClassAssignments()`)
- Sửa: `app/Models/User.php` (thêm `teacherProfile()`, `studentProfile()`, `parentProfile()`)
- Sửa: `routes/api.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/GetClassTeachersController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/AssignTeacherToClassController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/EndTeacherClassAssignmentController.php`
- Tạo: `tests/Feature/UserManagement/TeacherClassAssignmentTest.php`

**Interfaces:**
- Produces: `Teacher::activeClassIds(): \Illuminate\Support\Collection` (danh sách `class_id` đang hiệu lực) — dùng bởi Task 8 (roster scope), Task 9 (Student detail scope), Task 12 (BR-USER-010 guard). `User::teacherProfile()`/`studentProfile()`/`parentProfile()` — dùng bởi Task 8, 9, 12, 15, 17.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherClassAssignmentTest extends TestCase
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

    public function test_admin_can_assign_a_teacher_to_a_class(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $teacher = Teacher::factory()->create();

        $response = $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacher->id]);

        $response->assertCreated();
        $this->assertDatabaseHas('teacher_class_assignments', [
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_end_a_teacher_assignment(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $teacher = Teacher::factory()->create();
        $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacher->id]);

        $response = $this->deleteJson("/api/v1/classes/{$class->id}/teachers/{$teacher->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('teacher_class_assignments', [
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'status' => 'ended',
        ]);
    }

    public function test_assigned_teacher_can_view_their_own_class_assignments(): void
    {
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        $this->actingAsAdmin();
        $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacher->id]);

        $this->actingAsTeacher($teacher);
        $response = $this->getJson("/api/v1/classes/{$class->id}/teachers");

        $response->assertOk();
        $response->assertJsonPath('data.0.teacher_id', $teacher->id);
    }

    public function test_teacher_not_assigned_cannot_view_another_classs_assignments(): void
    {
        $teacher = Teacher::factory()->create();
        $otherClass = SchoolClass::factory()->create();

        $this->actingAsTeacher($teacher);
        $response = $this->getJson("/api/v1/classes/{$otherClass->id}/teachers");

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_assign_a_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        $this->actingAsTeacher($teacher);

        $response = $this->postJson("/api/v1/classes/{$class->id}/teachers", ['teacher_id' => $teacher->id]);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=TeacherClassAssignmentTest` → FAIL.

- [ ] **Step 3: Tạo migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_class_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignUuid('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->foreignUuid('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_class_assignments');
    }
};
```

- [ ] **Step 4: Tạo model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherClassAssignment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'class_id', 'teacher_id', 'valid_from', 'valid_to', 'status', 'ended_by_user_id', 'end_reason',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
```

- [ ] **Step 5: Sửa `Teacher.php` — thêm relation + scope helper**

Thêm vào class `Teacher` (sau `personProfile()`):
```php
public function classAssignments()
{
    return $this->hasMany(TeacherClassAssignment::class);
}

public function activeClassIds()
{
    return $this->classAssignments()
        ->where('status', 'active')
        ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
        ->pluck('class_id');
}
```

- [ ] **Step 6: Sửa `SchoolClass.php` — thêm relation**

Thêm vào class `SchoolClass`:
```php
public function teacherClassAssignments()
{
    return $this->hasMany(TeacherClassAssignment::class, 'class_id');
}
```

- [ ] **Step 7: Sửa `User.php` — thêm 3 relation tới role profile**

Thêm vào class `User` (sau `userRoles()`):
```php
public function teacherProfile()
{
    return $this->hasOne(Teacher::class, 'person_profile_id', 'person_profile_id');
}

public function studentProfile()
{
    return $this->hasOne(Student::class, 'person_profile_id', 'person_profile_id');
}

public function parentProfile()
{
    return $this->hasOne(ParentProfile::class, 'person_profile_id', 'person_profile_id');
}
```

- [ ] **Step 8: Tạo factory `TeacherClassAssignmentFactory`**

`database/factories/TeacherClassAssignmentFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherClassAssignmentFactory extends Factory
{
    protected $model = TeacherClassAssignment::class;

    public function definition(): array
    {
        return [
            'class_id' => SchoolClass::factory(),
            'teacher_id' => Teacher::factory(),
            'valid_from' => now()->subDay(),
            'status' => 'active',
        ];
    }
}
```

- [ ] **Step 9: Tạo 3 controller**

`app/Http/Controllers/Api/UserManagement/GetClassTeachersController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class GetClassTeachersController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole === 'teacher') {
            $teacher = $request->user()->teacherProfile;

            if (! $teacher || ! $teacher->activeClassIds()->contains($class->id)) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'Không có quyền xem phân công của lớp này.'],
                ], 403);
            }
        }

        $assignments = $class->teacherClassAssignments()
            ->where('status', 'active')
            ->with('teacher.personProfile')
            ->get()
            ->map(fn ($a) => [
                'teacher_id' => $a->teacher_id,
                'full_name' => $a->teacher->personProfile->full_name,
                'valid_from' => $a->valid_from,
                'valid_to' => $a->valid_to,
            ]);

        return response()->json(['data' => $assignments]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/AssignTeacherToClassController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\TeacherClassAssignment;
use Illuminate\Http\Request;

class AssignTeacherToClassController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'uuid', 'exists:teachers,id'],
        ]);

        $assignment = TeacherClassAssignment::create([
            'class_id' => $class->id,
            'teacher_id' => $data['teacher_id'],
            'valid_from' => now(),
            'status' => 'active',
        ]);

        return response()->json(['teacher_class_assignment' => [
            'id' => $assignment->id,
            'class_id' => $assignment->class_id,
            'teacher_id' => $assignment->teacher_id,
            'valid_from' => $assignment->valid_from,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/UserManagement/EndTeacherClassAssignmentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\TeacherClassAssignment;
use Illuminate\Http\Request;

class EndTeacherClassAssignmentController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class, string $teacherId)
    {
        $assignment = TeacherClassAssignment::where('class_id', $class->id)
            ->where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->firstOrFail();

        $assignment->update([
            'status' => 'ended',
            'valid_to' => now(),
            'ended_by_user_id' => $request->user()->id,
        ]);

        return response()->noContent();
    }
}
```

- [ ] **Step 10: Thêm route**

Trong `routes/api.php`, bên trong `Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () { ... })`, thêm (ngang hàng với block `role:admin` đã có):
```php
Route::prefix('classes/{class}')->group(function () {
    Route::middleware('role:admin,teacher')->get('/teachers', \App\Http\Controllers\Api\UserManagement\GetClassTeachersController::class);
});

Route::middleware('role:admin')->group(function () {
    // ... các route admin đã có (students/parents/teachers POST+PATCH, person-profiles) ...

    Route::prefix('classes/{class}')->group(function () {
        Route::post('/teachers', \App\Http\Controllers\Api\UserManagement\AssignTeacherToClassController::class);
        Route::delete('/teachers/{teacherId}', \App\Http\Controllers\Api\UserManagement\EndTeacherClassAssignmentController::class);
    });
});
```
(Giữ nguyên các route admin đã thêm ở Task 4-6 trong cùng block `role:admin` — chỉ bổ sung `Route::prefix('classes/{class}')->group(...)` mới vào trong đó.)

- [ ] **Step 11: Chạy lại test**

Chạy: `php artisan test --filter=TeacherClassAssignmentTest`
Kỳ vọng: PASS (cả 5 test method).

- [ ] **Step 12: Commit**

```bash
git add app database routes
git commit -m "feat: add TeacherClassAssignment model and /classes/{id}/teachers endpoints"
```

---

### Task 8: `ClassMember` — model + endpoint (`/classes/{class_id}/members`)

**Files:**
- Tạo: `database/migrations/2026_10_13_000007_create_class_members_table.php`
- Tạo: `app/Models/ClassMember.php`
- Sửa: `app/Models/Student.php` (thêm `classMembers()`, `activeClassIds()`)
- Sửa: `app/Models/SchoolClass.php` (thêm `classMembers()`)
- Sửa: `routes/api.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/GetClassMembersController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/AddClassMemberController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/EndClassMemberController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/UpdateClassMemberController.php`
- Tạo: `tests/Feature/UserManagement/ClassMemberTest.php`

**Interfaces:**
- Dùng lại: `Teacher::activeClassIds()` (Task 7) cho scope check.
- Produces: `Student::activeClassIds(): \Illuminate\Support\Collection` — dùng bởi Task 9 (Teacher xem Student profile cùng lớp).
- **Giới hạn đã biết:** `UpdateClassMemberController` validate `reason` bắt buộc nhưng chưa ghi `AuditLog` (xem Global Constraints).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassMemberTest extends TestCase
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

    public function test_admin_can_add_a_student_to_a_class(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();

        $response = $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

        $response->assertCreated();
        $this->assertDatabaseHas('class_members', ['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active']);
    }

    public function test_ending_a_membership_requires_a_reason(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

        $response = $this->deleteJson("/api/v1/classes/{$class->id}/members/{$student->id}", []);

        $response->assertStatus(422);
    }

    public function test_admin_can_end_a_membership_with_a_reason(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

        $response = $this->deleteJson("/api/v1/classes/{$class->id}/members/{$student->id}", ['end_reason' => 'Chuyển lớp']);

        $response->assertNoContent();
        $this->assertDatabaseHas('class_members', ['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'ended']);
    }

    public function test_admin_can_retroactively_edit_membership_dates_with_a_reason(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

        $response = $this->patchJson("/api/v1/classes/{$class->id}/members/{$student->id}", [
            'valid_from' => now()->subMonth()->toIso8601String(),
            'reason' => 'Sửa ngày vào lớp cho đúng thực tế',
        ]);

        $response->assertOk();
    }

    public function test_retroactive_edit_without_a_reason_is_rejected(): void
    {
        $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

        $response = $this->patchJson("/api/v1/classes/{$class->id}/members/{$student->id}", [
            'valid_from' => now()->subMonth()->toIso8601String(),
        ]);

        $response->assertStatus(422);
    }

    public function test_teacher_assigned_to_the_class_can_view_the_roster(): void
    {
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        TeacherClassAssignment::factory()->create(['class_id' => $class->id, 'teacher_id' => $teacher->id]);
        $this->actingAsAdmin();
        $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

        $this->actingAsTeacher($teacher);
        $response = $this->getJson("/api/v1/classes/{$class->id}/members");

        $response->assertOk();
        $response->assertJsonPath('data.0.student_id', $student->id);
    }

    public function test_teacher_not_assigned_cannot_view_the_roster(): void
    {
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        $this->actingAsTeacher($teacher);

        $response = $this->getJson("/api/v1/classes/{$class->id}/members");

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_add_a_student_to_a_class(): void
    {
        $teacher = Teacher::factory()->create();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        $this->actingAsTeacher($teacher);

        $response = $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ClassMemberTest` → FAIL.

- [ ] **Step 3: Tạo migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->foreignUuid('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_members');
    }
};
```

- [ ] **Step 4: Tạo model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassMember extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'class_id', 'student_id', 'status', 'valid_from', 'valid_to', 'ended_by_user_id', 'end_reason',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
```

- [ ] **Step 5: Sửa `Student.php` — thêm relation + scope helper**

Thêm vào class `Student` (sau `personProfile()`):
```php
public function classMembers()
{
    return $this->hasMany(ClassMember::class);
}

public function activeClassIds()
{
    return $this->classMembers()
        ->where('status', 'active')
        ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
        ->pluck('class_id');
}
```

- [ ] **Step 6: Sửa `SchoolClass.php` — thêm relation**

Thêm vào class `SchoolClass`:
```php
public function classMembers()
{
    return $this->hasMany(ClassMember::class, 'class_id');
}
```

- [ ] **Step 7: Tạo factory `ClassMemberFactory`**

`database/factories/ClassMemberFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassMemberFactory extends Factory
{
    protected $model = ClassMember::class;

    public function definition(): array
    {
        return [
            'class_id' => SchoolClass::factory(),
            'student_id' => Student::factory(),
            'valid_from' => now()->subDay(),
            'status' => 'active',
        ];
    }
}
```

- [ ] **Step 8: Tạo 4 controller**

`app/Http/Controllers/Api/UserManagement/GetClassMembersController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class GetClassMembersController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole === 'teacher') {
            $teacher = $request->user()->teacherProfile;

            if (! $teacher || ! $teacher->activeClassIds()->contains($class->id)) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'Không có quyền xem roster của lớp này.'],
                ], 403);
            }
        }

        $members = $class->classMembers()
            ->where('status', 'active')
            ->with('student.personProfile')
            ->get()
            ->map(fn ($m) => [
                'student_id' => $m->student_id,
                'full_name' => $m->student->personProfile->full_name,
                'valid_from' => $m->valid_from,
                'valid_to' => $m->valid_to,
            ]);

        return response()->json(['data' => $members]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/AddClassMemberController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class AddClassMemberController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class)
    {
        $data = $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
        ]);

        $member = ClassMember::create([
            'class_id' => $class->id,
            'student_id' => $data['student_id'],
            'valid_from' => now(),
            'status' => 'active',
        ]);

        return response()->json(['class_member' => [
            'id' => $member->id,
            'class_id' => $member->class_id,
            'student_id' => $member->student_id,
            'valid_from' => $member->valid_from,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/UserManagement/EndClassMemberController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class EndClassMemberController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class, string $studentId)
    {
        $data = $request->validate([
            'end_reason' => ['required', 'string'],
        ]);

        $member = ClassMember::where('class_id', $class->id)
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->firstOrFail();

        $member->update([
            'status' => 'ended',
            'valid_to' => now(),
            'ended_by_user_id' => $request->user()->id,
            'end_reason' => $data['end_reason'],
        ]);

        return response()->noContent();
    }
}
```

`app/Http/Controllers/Api/UserManagement/UpdateClassMemberController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class UpdateClassMemberController extends Controller
{
    public function __invoke(Request $request, SchoolClass $class, string $studentId)
    {
        $data = $request->validate([
            'valid_from' => ['sometimes', 'date'],
            'valid_to' => ['sometimes', 'nullable', 'date'],
            'reason' => ['required', 'string'],
        ]);

        $member = ClassMember::where('class_id', $class->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        // "reason" được bắt buộc theo BR-USER-017 nhưng chưa có bảng AuditLog để
        // ghi lại (thuộc module Audit, nhóm sau) — xem Global Constraints của plan này.
        $member->fill(array_intersect_key($data, array_flip(['valid_from', 'valid_to'])))->save();

        return response()->json(['class_member' => [
            'id' => $member->id,
            'valid_from' => $member->valid_from,
            'valid_to' => $member->valid_to,
        ]]);
    }
}
```

- [ ] **Step 9: Thêm route**

Trong `routes/api.php`, bổ sung vào block `Route::prefix('classes/{class}')` đã tồn tại từ Task 7 (bên trong `role:admin,teacher` cho GET, bên trong `role:admin` cho các method khác):
```php
Route::middleware('role:admin,teacher')->group(function () {
    Route::prefix('classes/{class}')->group(function () {
        Route::get('/teachers', \App\Http\Controllers\Api\UserManagement\GetClassTeachersController::class);
        Route::get('/members', \App\Http\Controllers\Api\UserManagement\GetClassMembersController::class);
    });
});

Route::middleware('role:admin')->group(function () {
    // ... route admin khác đã có ...

    Route::prefix('classes/{class}')->group(function () {
        Route::post('/teachers', \App\Http\Controllers\Api\UserManagement\AssignTeacherToClassController::class);
        Route::delete('/teachers/{teacherId}', \App\Http\Controllers\Api\UserManagement\EndTeacherClassAssignmentController::class);
        Route::post('/members', \App\Http\Controllers\Api\UserManagement\AddClassMemberController::class);
        Route::delete('/members/{studentId}', \App\Http\Controllers\Api\UserManagement\EndClassMemberController::class);
        Route::patch('/members/{studentId}', \App\Http\Controllers\Api\UserManagement\UpdateClassMemberController::class);
    });
});
```
(Gộp 2 route GET của Task 7+8 vào cùng nhánh `role:admin,teacher`; gộp các route write vào cùng nhánh `role:admin` — thay thế cấu trúc tạm ở Task 7 Step 10 bằng cấu trúc đầy đủ này.)

- [ ] **Step 10: Chạy lại test**

Chạy: `php artisan test --filter=ClassMemberTest`
Kỳ vọng: PASS (cả 8 test method).

- [ ] **Step 11: Chạy lại toàn bộ test của Task 7 để chắc route không bị vỡ**

Chạy: `php artisan test --filter=TeacherClassAssignmentTest`
Kỳ vọng: PASS.

- [ ] **Step 12: Commit**

```bash
git add app database routes
git commit -m "feat: add ClassMember model and /classes/{id}/members endpoints"
```

---

### Task 9: `GET /api/v1/students/{id}`, `/parents/{id}`, `/teachers/{id}` (đọc hồ sơ, Teacher scoped qua Class)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/GetStudentController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/GetParentController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/GetTeacherController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/GetRoleProfileTest.php`

**Interfaces:**
- Dùng lại: `Teacher::activeClassIds()` (Task 7), `Student::activeClassIds()` (Task 8).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\ClassMember;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetRoleProfileTest extends TestCase
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

    public function test_admin_can_view_any_student_profile(): void
    {
        $this->actingAsAdmin();
        $student = Student::factory()->create();

        $response = $this->getJson("/api/v1/students/{$student->id}");

        $response->assertOk();
        $response->assertJsonPath('student.id', $student->id);
    }

    public function test_teacher_can_view_a_student_sharing_an_active_class(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['class_id' => $class->id, 'teacher_id' => $teacher->id]);
        ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->actingAsTeacher($teacher);
        $response = $this->getJson("/api/v1/students/{$student->id}");

        $response->assertOk();
    }

    public function test_teacher_cannot_view_a_student_outside_their_classes(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();

        $this->actingAsTeacher($teacher);
        $response = $this->getJson("/api/v1/students/{$student->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_a_parent_profile(): void
    {
        $this->actingAsAdmin();
        $parent = ParentProfile::factory()->create();

        $response = $this->getJson("/api/v1/parents/{$parent->id}");

        $response->assertOk();
    }

    public function test_admin_can_view_a_teacher_profile(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create();

        $response = $this->getJson("/api/v1/teachers/{$teacher->id}");

        $response->assertOk();
    }

    public function test_teacher_cannot_view_a_parent_profile(): void
    {
        $teacher = Teacher::factory()->create();
        $parent = ParentProfile::factory()->create();
        $this->actingAsTeacher($teacher);

        $response = $this->getJson("/api/v1/parents/{$parent->id}");

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=GetRoleProfileTest` → FAIL.

- [ ] **Step 3: Tạo 3 controller**

`app/Http/Controllers/Api/UserManagement/GetStudentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class GetStudentController extends Controller
{
    public function __invoke(Request $request, Student $student)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole === 'teacher') {
            $teacher = $request->user()->teacherProfile;
            $sharesClass = $teacher && $teacher->activeClassIds()->intersect($student->activeClassIds())->isNotEmpty();

            if (! $sharesClass) {
                return response()->json([
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'Không có quyền xem hồ sơ học viên này.'],
                ], 403);
            }
        }

        $student->load('personProfile');

        return response()->json(['student' => [
            'id' => $student->id,
            'status' => $student->status,
            'notes' => $student->notes,
            'person_profile' => [
                'id' => $student->personProfile->id,
                'full_name' => $student->personProfile->full_name,
                'email' => $student->personProfile->email,
                'phone' => $student->personProfile->phone,
            ],
        ]]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/GetParentController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentProfile;

class GetParentController extends Controller
{
    public function __invoke(ParentProfile $parent)
    {
        $parent->load('personProfile');

        return response()->json(['parent' => [
            'id' => $parent->id,
            'status' => $parent->status,
            'person_profile' => [
                'id' => $parent->personProfile->id,
                'full_name' => $parent->personProfile->full_name,
                'email' => $parent->personProfile->email,
                'phone' => $parent->personProfile->phone,
            ],
        ]]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/GetTeacherController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

class GetTeacherController extends Controller
{
    public function __invoke(Teacher $teacher)
    {
        $teacher->load('personProfile');

        return response()->json(['teacher' => [
            'id' => $teacher->id,
            'status' => $teacher->status,
            'specialty' => $teacher->specialty,
            'person_profile' => [
                'id' => $teacher->personProfile->id,
                'full_name' => $teacher->personProfile->full_name,
                'email' => $teacher->personProfile->email,
                'phone' => $teacher->personProfile->phone,
            ],
        ]]);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong `routes/api.php`, thêm route `students/{id}` vào nhánh `role:admin,teacher` đã có (cạnh `classes/{class}/members`, `classes/{class}/teachers`), và `parents/{id}` + `teachers/{id}` vào nhánh `role:admin`:
```php
Route::middleware('role:admin,teacher')->group(function () {
    Route::get('/students/{student}', \App\Http\Controllers\Api\UserManagement\GetStudentController::class);

    Route::prefix('classes/{class}')->group(function () {
        Route::get('/teachers', \App\Http\Controllers\Api\UserManagement\GetClassTeachersController::class);
        Route::get('/members', \App\Http\Controllers\Api\UserManagement\GetClassMembersController::class);
    });
});

Route::middleware('role:admin')->group(function () {
    // ... route admin khác đã có ...

    Route::get('/parents/{parent}', \App\Http\Controllers\Api\UserManagement\GetParentController::class);
    Route::get('/teachers/{teacher}', \App\Http\Controllers\Api\UserManagement\GetTeacherController::class);
});
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=GetRoleProfileTest`
Kỳ vọng: PASS (cả 6 test method).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add GET endpoints for Student/Parent/Teacher profiles with teacher class scope"
```

---

### Task 10: `GET /api/v1/users` (danh sách, lọc role/status/name/email)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/ListUsersController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/ListUsersTest.php`

**Interfaces:**
- Dùng lại: `User::userRoles()`, `User::personProfile()` (Plan 1 + Task 3).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListUsersTest extends TestCase
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

    public function test_admin_can_list_all_users(): void
    {
        $admin = $this->actingAsAdmin();
        User::factory()->create();

        $response = $this->getJson('/api/v1/users');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_can_filter_users_by_status(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/users?status=inactive');

        $response->assertOk();
        foreach ($response->json('data') as $row) {
            $this->assertSame('inactive', $row['status']);
        }
    }

    public function test_can_filter_users_by_role(): void
    {
        $this->actingAsAdmin();
        $teacherUser = User::factory()->create();
        $teacherRole = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $teacherRole->id, 'valid_from' => now()->subDay()]);

        $response = $this->getJson('/api/v1/users?role=teacher');

        $response->assertOk();
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($teacherUser->id, $ids);
    }

    public function test_can_filter_users_by_name(): void
    {
        $this->actingAsAdmin();
        $personProfile = PersonProfile::factory()->create(['full_name' => 'Nguyen Thi Searchable']);
        User::factory()->create(['person_profile_id' => $personProfile->id]);

        $response = $this->getJson('/api/v1/users?name=Searchable');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'student'])
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ListUsersTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ListUsersController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = User::query()->with(['personProfile', 'userRoles.role']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($role = $request->query('role')) {
            $query->whereHas(
                'userRoles',
                fn ($q) => $q->whereHas('role', fn ($r) => $r->where('code', $role))
                    ->where(fn ($q2) => $q2->whereNull('valid_to')->orWhere('valid_to', '>', now()))
            );
        }

        if ($name = $request->query('name')) {
            $query->whereHas('personProfile', fn ($q) => $q->where('full_name', 'ilike', "%{$name}%"));
        }

        if ($email = $request->query('email')) {
            $query->where('login_identifier', 'ilike', "%{$email}%");
        }

        $perPage = (int) $request->query('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->getCollection()->map(fn ($u) => [
                'id' => $u->id,
                'login_identifier' => $u->login_identifier,
                'status' => $u->status,
                'full_name' => $u->personProfile?->full_name,
                'roles' => $u->userRoles->pluck('role.code'),
            ]),
            'meta' => [
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `role:admin` ở `routes/api.php`, thêm:
```php
Route::get('/users', \App\Http\Controllers\Api\UserManagement\ListUsersController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=ListUsersTest`
Kỳ vọng: PASS (cả 5 test method).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add admin-only GET /api/v1/users with role/status/name/email filters"
```

---

### Task 11: `GET /api/v1/users/{id}` (chi tiết + lịch sử role)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/GetUserController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/GetUserTest.php`

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_detail_with_role_history(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $adminRole->id, 'valid_from' => now()->subDay()]);

        $target = User::factory()->create();
        $teacherRole = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $target->id,
            'role_id' => $teacherRole->id,
            'valid_from' => now()->subYear(),
            'valid_to' => now()->subMonth(),
        ]);
        UserRole::create(['user_id' => $target->id, 'role_id' => $adminRole->id, 'valid_from' => now()->subDay()]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role_code' => 'admin'])
            ->getJson("/api/v1/users/{$target->id}");

        $response->assertOk();
        $response->assertJsonPath('user.active_roles', ['admin']);
        $response->assertJsonCount(2, 'user.role_history');
    }

    public function test_non_admin_cannot_view_user_detail(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'student'])
            ->getJson("/api/v1/users/{$user->id}");

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=GetUserTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;

class GetUserController extends Controller
{
    public function __invoke(User $user)
    {
        $user->load(['personProfile', 'userRoles.role']);

        return response()->json(['user' => [
            'id' => $user->id,
            'login_identifier' => $user->login_identifier,
            'status' => $user->status,
            'full_name' => $user->personProfile?->full_name,
            'active_roles' => $user->userRoles
                ->filter(fn ($ur) => $ur->valid_to === null || $ur->valid_to->isFuture())
                ->pluck('role.code')
                ->values(),
            'role_history' => $user->userRoles->map(fn ($ur) => [
                'role' => $ur->role->code,
                'valid_from' => $ur->valid_from,
                'valid_to' => $ur->valid_to,
            ]),
        ]]);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `role:admin` ở `routes/api.php`, thêm:
```php
Route::get('/users/{user}', \App\Http\Controllers\Api\UserManagement\GetUserController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=GetUserTest`
Kỳ vọng: PASS.

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add admin-only GET /api/v1/users/{id} with role history"
```

---

### Task 12: `PATCH /api/v1/users/{id}` (status, với guard BR-USER-007 + BR-USER-010)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/UpdateUserStatusController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/UpdateUserStatusTest.php`

**Interfaces:**
- Dùng lại: `User::hasActiveRole()` (Plan 1), `User::teacherProfile()` (Task 7), `Teacher::activeClassIds()` (Task 7).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUserStatusTest extends TestCase
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

    public function test_admin_can_deactivate_a_regular_user(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $response = $this->patchJson("/api/v1/users/{$target->id}", ['status' => 'inactive']);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'inactive']);
    }

    public function test_cannot_deactivate_the_last_active_admin(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->patchJson("/api/v1/users/{$admin->id}", ['status' => 'inactive']);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'LAST_ADMIN');
    }

    public function test_can_deactivate_an_admin_when_another_active_admin_exists(): void
    {
        $admin = $this->actingAsAdmin();
        $adminRole = Role::where('code', 'admin')->first();
        $otherAdmin = User::factory()->create();
        UserRole::create(['user_id' => $otherAdmin->id, 'role_id' => $adminRole->id, 'valid_from' => now()->subDay()]);

        $response = $this->patchJson("/api/v1/users/{$admin->id}", ['status' => 'inactive']);

        $response->assertOk();
    }

    public function test_cannot_deactivate_a_teacher_with_active_class_assignments(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create();
        $teacherUser = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $teacherRole = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $teacherRole->id, 'valid_from' => now()->subDay()]);
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $class->id]);

        $response = $this->patchJson("/api/v1/users/{$teacherUser->id}", ['status' => 'inactive']);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'ACTIVE_ASSIGNMENTS_EXIST');
    }

    public function test_can_deactivate_a_teacher_with_no_active_assignments(): void
    {
        $this->actingAsAdmin();
        $teacher = Teacher::factory()->create();
        $teacherUser = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $teacherRole = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $teacherRole->id, 'valid_from' => now()->subDay()]);

        $response = $this->patchJson("/api/v1/users/{$teacherUser->id}", ['status' => 'inactive']);

        $response->assertOk();
    }

    public function test_non_admin_cannot_update_user_status(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'student'])
            ->patchJson("/api/v1/users/{$user->id}", ['status' => 'inactive']);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=UpdateUserStatusTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class UpdateUserStatusController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        if ($data['status'] === 'inactive') {
            if ($user->hasActiveRole('admin') && ! $this->anotherActiveAdminExists($user)) {
                return response()->json([
                    'error' => ['code' => 'LAST_ADMIN', 'message' => 'Không thể vô hiệu hóa Admin cuối cùng còn quyền quản trị.'],
                ], 409);
            }

            if ($user->hasActiveRole('teacher')) {
                $teacher = $user->teacherProfile;
                $activeClassIds = $teacher ? $teacher->activeClassIds() : collect();

                if ($activeClassIds->isNotEmpty()) {
                    return response()->json([
                        'error' => ['code' => 'ACTIVE_ASSIGNMENTS_EXIST', 'message' => 'Teacher còn phân công lớp đang hoạt động, cần xử lý trước khi vô hiệu hóa.'],
                        'active_class_ids' => $activeClassIds->values(),
                    ], 422);
                }
            }
        }

        $user->update(['status' => $data['status']]);

        return response()->json(['user' => ['id' => $user->id, 'status' => $user->status]]);
    }

    private function anotherActiveAdminExists(User $excluding): bool
    {
        return UserRole::whereHas('role', fn ($q) => $q->where('code', 'admin'))
            ->where('user_id', '!=', $excluding->id)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->exists();
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `role:admin` ở `routes/api.php`, thêm:
```php
Route::patch('/users/{user}', \App\Http\Controllers\Api\UserManagement\UpdateUserStatusController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=UpdateUserStatusTest`
Kỳ vọng: PASS (cả 6 test method).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add PATCH /api/v1/users/{id} with last-admin and active-teacher-assignment guards"
```

---

### Task 13: `POST /api/v1/users/{id}/roles` ; `DELETE /api/v1/users/{id}/roles/{role_id}`

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/AssignUserRoleController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/EndUserRoleController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/UserRoleAssignmentTest.php`

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
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

    public function test_admin_can_assign_a_new_role_to_a_user(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();
        Role::factory()->create(['code' => 'teacher']);

        $response = $this->postJson("/api/v1/users/{$target->id}/roles", ['role_code' => 'teacher']);

        $response->assertCreated();
        $this->assertDatabaseHas('user_roles', ['user_id' => $target->id, 'valid_to' => null]);
    }

    public function test_cannot_assign_a_role_the_user_already_actively_holds(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $target->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);

        $response = $this->postJson("/api/v1/users/{$target->id}/roles", ['role_code' => 'teacher']);

        $response->assertStatus(409);
    }

    public function test_admin_can_end_a_role_assignment(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        $userRole = UserRole::create(['user_id' => $target->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);

        $response = $this->deleteJson("/api/v1/users/{$target->id}/roles/{$role->id}");

        $response->assertNoContent();
        $this->assertNotNull($userRole->refresh()->valid_to);
    }

    public function test_non_admin_cannot_assign_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $target = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'student'])
            ->postJson("/api/v1/users/{$target->id}/roles", ['role_code' => 'teacher']);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=UserRoleAssignmentTest` → FAIL.

- [ ] **Step 3: Tạo 2 controller**

`app/Http/Controllers/Api/UserManagement/AssignUserRoleController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class AssignUserRoleController extends Controller
{
    public function __invoke(Request $request, User $user)
    {
        $data = $request->validate(['role_code' => ['required', 'in:admin,teacher,student,parent']]);

        if ($user->hasActiveRole($data['role_code'])) {
            return response()->json([
                'error' => ['code' => 'ROLE_ALREADY_HELD', 'message' => 'User đã có role này.'],
            ], 409);
        }

        $role = Role::where('code', $data['role_code'])->firstOrFail();

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now(),
            'assigned_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['user_role' => [
            'id' => $userRole->id,
            'role' => $role->code,
            'valid_from' => $userRole->valid_from,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/UserManagement/EndUserRoleController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class EndUserRoleController extends Controller
{
    public function __invoke(Request $request, User $user, string $roleId)
    {
        $userRole = UserRole::where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
            ->firstOrFail();

        $userRole->update([
            'valid_to' => now(),
            'ended_by_user_id' => $request->user()->id,
        ]);

        return response()->noContent();
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `role:admin` ở `routes/api.php`, thêm:
```php
Route::post('/users/{user}/roles', \App\Http\Controllers\Api\UserManagement\AssignUserRoleController::class);
Route::delete('/users/{user}/roles/{roleId}', \App\Http\Controllers\Api\UserManagement\EndUserRoleController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=UserRoleAssignmentTest`
Kỳ vọng: PASS (cả 4 test method).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add admin-only role assignment and role-ending endpoints for users"
```

---

### Task 14: `POST /api/v1/person-profiles/{id}/invite` (tạo User + gửi email đặt mật khẩu)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/InvitePersonProfileController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/InvitePersonProfileTest.php`

**Interfaces:**
- Dùng lại: `App\Notifications\ResetPasswordNotification` (Plan 1) — gửi lại cùng notification cho luồng "đặt mật khẩu lần đầu" vì cơ chế giống nhau (token + link frontend).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvitePersonProfileTest extends TestCase
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

    public function test_admin_can_invite_a_profile_without_an_account_yet(): void
    {
        $this->actingAsAdmin();
        Notification::fake();
        $personProfile = PersonProfile::factory()->create();

        $response = $this->postJson("/api/v1/person-profiles/{$personProfile->id}/invite", [
            'login_identifier' => 'new.teacher@example.com',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'person_profile_id' => $personProfile->id,
            'login_identifier' => 'new.teacher@example.com',
        ]);
        Notification::assertSentTo(User::where('person_profile_id', $personProfile->id)->first(), ResetPasswordNotification::class);
    }

    public function test_cannot_invite_a_profile_that_already_has_an_account(): void
    {
        $this->actingAsAdmin();
        $personProfile = PersonProfile::factory()->create();
        User::factory()->create(['person_profile_id' => $personProfile->id]);

        $response = $this->postJson("/api/v1/person-profiles/{$personProfile->id}/invite", [
            'login_identifier' => 'another@example.com',
        ]);

        $response->assertStatus(409);
    }

    public function test_non_admin_cannot_invite_a_profile(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $personProfile = PersonProfile::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'teacher'])
            ->postJson("/api/v1/person-profiles/{$personProfile->id}/invite", ['login_identifier' => 'x@example.com']);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=InvitePersonProfileTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\PersonProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class InvitePersonProfileController extends Controller
{
    public function __invoke(Request $request, PersonProfile $personProfile)
    {
        $data = $request->validate(['login_identifier' => ['required', 'string']]);

        if (User::where('person_profile_id', $personProfile->id)->exists()) {
            return response()->json([
                'error' => ['code' => 'ALREADY_INVITED', 'message' => 'Hồ sơ này đã có tài khoản.'],
            ], 409);
        }

        $user = User::create([
            'person_profile_id' => $personProfile->id,
            'login_identifier' => $data['login_identifier'],
            'password' => Str::random(32),
            'status' => 'active',
        ]);

        $token = Password::broker()->createToken($user);
        $user->sendPasswordResetNotification($token);

        return response()->json(['user' => [
            'id' => $user->id,
            'login_identifier' => $user->login_identifier,
        ]], 201);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `role:admin` ở `routes/api.php`, thêm:
```php
Route::post('/person-profiles/{personProfile}/invite', \App\Http\Controllers\Api\UserManagement\InvitePersonProfileController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=InvitePersonProfileTest`
Kỳ vọng: PASS (cả 3 test method).

- [ ] **Step 6: Commit**

```bash
git add app routes
git commit -m "feat: add admin-only invite endpoint creating a first-time User account"
```

---

### Task 15: `GET` + `PATCH /api/v1/me/profile` (self-service)

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/GetMyProfileController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/UpdateMyProfileController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/MyProfileTest.php`

**Interfaces:**
- Dùng lại: `User::studentProfile()`/`parentProfile()`/`teacherProfile()` (Task 7).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_own_profile_including_role_profile(): void
    {
        $teacher = Teacher::factory()->create();
        $user = User::factory()->create(['person_profile_id' => $teacher->person_profile_id]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'teacher'])
            ->getJson('/api/v1/me/profile');

        $response->assertOk();
        $response->assertJsonPath('role_profile.id', $teacher->id);
    }

    public function test_can_update_own_email_and_phone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
            'email' => 'new@example.com',
            'phone' => '0911111111',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('person_profiles', ['id' => $user->person_profile_id, 'email' => 'new@example.com']);
    }

    public function test_cannot_update_own_full_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', ['full_name' => 'Hacked Name']);

        $response->assertStatus(403);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/me/profile');

        $response->assertStatus(401);
    }
}
```

**Lưu ý:** test trên cần `User` có `person_profile_id` thực (không null) để `PATCH /me/profile` ghi được vào `person_profiles` — sửa `UserFactory` (Plan 1) để luôn tạo kèm `PersonProfile`:

- [ ] **Step 2: Sửa `UserFactory` để luôn gắn kèm `PersonProfile`**

Thay toàn bộ `database/factories/UserFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\PersonProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'person_profile_id' => PersonProfile::factory(),
            'login_identifier' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => 'active',
        ];
    }
}
```

- [ ] **Step 3: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=MyProfileTest` → FAIL (route chưa tồn tại).

- [ ] **Step 4: Tạo 2 controller**

`app/Http/Controllers/Api/UserManagement/GetMyProfileController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetMyProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user()->load('personProfile');
        $activeRole = $request->session()->get('active_role_code');

        $roleProfile = match ($activeRole) {
            'student' => $request->user()->studentProfile,
            'parent' => $request->user()->parentProfile,
            'teacher' => $request->user()->teacherProfile,
            default => null,
        };

        return response()->json([
            'person_profile' => [
                'id' => $user->personProfile->id,
                'full_name' => $user->personProfile->full_name,
                'email' => $user->personProfile->email,
                'phone' => $user->personProfile->phone,
            ],
            'role_profile' => $roleProfile ? ['id' => $roleProfile->id, 'status' => $roleProfile->status] : null,
        ]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/UpdateMyProfileController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UpdateMyProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->has('full_name') || $request->has('status')) {
            return response()->json([
                'error' => ['code' => 'FORBIDDEN', 'message' => 'Không thể tự sửa full_name hoặc status.'],
            ], 403);
        }

        $data = $request->validate([
            'email' => ['sometimes', 'nullable', 'string', 'email'],
            'phone' => ['sometimes', 'nullable', 'string'],
        ]);

        $personProfile = $request->user()->personProfile;
        $personProfile->fill($data)->save();

        return response()->json(['person_profile' => [
            'id' => $personProfile->id,
            'full_name' => $personProfile->full_name,
            'email' => $personProfile->email,
            'phone' => $personProfile->phone,
        ]]);
    }
}
```

- [ ] **Step 5: Thêm route**

Trong `routes/api.php`, bên trong `Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () { ... })` nhưng **ngoài** mọi nhánh `role:...` (mọi role đã đăng nhập đều gọi được), cạnh `patch('/me/password', ...)` đã có:
```php
Route::get('/me/profile', \App\Http\Controllers\Api\UserManagement\GetMyProfileController::class);
Route::patch('/me/profile', \App\Http\Controllers\Api\UserManagement\UpdateMyProfileController::class);
```

- [ ] **Step 6: Chạy lại test**

Chạy: `php artisan test --filter=MyProfileTest`
Kỳ vọng: PASS (cả 4 test method).

- [ ] **Step 7: Chạy lại toàn bộ test suite để chắc `UserFactory` mới không vỡ test cũ**

Chạy: `php artisan test`
Kỳ vọng: toàn bộ PASS (Plan 1 + Task 1-15 của plan này).

- [ ] **Step 8: Commit**

```bash
git add app database routes
git commit -m "feat: add GET/PATCH /api/v1/me/profile; always attach a PersonProfile in UserFactory"
```

---

### Task 16: `ParentStudentRelationship` — model + endpoint

**Files:**
- Tạo: `database/migrations/2026_10_13_000008_create_parent_student_relationships_table.php`
- Tạo: `app/Models/ParentStudentRelationship.php`
- Sửa: `app/Models/ParentProfile.php` (thêm `relationships()`)
- Sửa: `routes/api.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/CreateParentStudentRelationshipController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/EndParentStudentRelationshipController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/ListParentStudentRelationshipsController.php`
- Tạo: `tests/Feature/UserManagement/ParentStudentRelationshipTest.php`

**Interfaces:**
- Produces: `ParentProfile::relationships(): HasMany` — dùng bởi Task 17 (`GET /me/children`).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentStudentRelationshipTest extends TestCase
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

    public function test_admin_can_create_a_relationship(): void
    {
        $this->actingAsAdmin();
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();

        $response = $this->postJson('/api/v1/parent-student-relationships', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'relationship_type' => 'mother',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('parent_student_relationships', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);
    }

    public function test_ending_a_relationship_requires_a_reason(): void
    {
        $this->actingAsAdmin();
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        $created = $this->postJson('/api/v1/parent-student-relationships', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
        ])->json('parent_student_relationship');

        $response = $this->deleteJson("/api/v1/parent-student-relationships/{$created['id']}", []);

        $response->assertStatus(422);
    }

    public function test_admin_can_end_a_relationship_with_a_reason(): void
    {
        $this->actingAsAdmin();
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        $created = $this->postJson('/api/v1/parent-student-relationships', [
            'parent_id' => $parent->id,
            'student_id' => $student->id,
        ])->json('parent_student_relationship');

        $response = $this->deleteJson("/api/v1/parent-student-relationships/{$created['id']}", ['end_reason' => 'Gia đình yêu cầu']);

        $response->assertNoContent();
        $this->assertDatabaseHas('parent_student_relationships', ['id' => $created['id'], 'status' => 'ended']);
    }

    public function test_admin_can_filter_relationships_by_student(): void
    {
        $this->actingAsAdmin();
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        $this->postJson('/api/v1/parent-student-relationships', ['parent_id' => $parent->id, 'student_id' => $student->id]);

        $response = $this->getJson("/api/v1/parent-student-relationships?student_id={$student->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_non_admin_cannot_create_a_relationship(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'teacher'])
            ->postJson('/api/v1/parent-student-relationships', ['parent_id' => $parent->id, 'student_id' => $student->id]);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ParentStudentRelationshipTest` → FAIL.

- [ ] **Step 3: Tạo migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('relationship_type')->nullable();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->foreignUuid('confirmed_by_user_id')->constrained('users');
            $table->timestamp('confirmed_at');
            $table->foreignUuid('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student_relationships');
    }
};
```

- [ ] **Step 4: Tạo model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentStudentRelationship extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'parent_id', 'student_id', 'relationship_type', 'status',
        'valid_from', 'valid_to', 'confirmed_by_user_id', 'confirmed_at',
        'ended_by_user_id', 'end_reason',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
```

- [ ] **Step 5: Sửa `ParentProfile.php` — thêm relation**

Thêm vào class `ParentProfile`:
```php
public function relationships()
{
    return $this->hasMany(ParentStudentRelationship::class, 'parent_id');
}
```

- [ ] **Step 6: Tạo factory `ParentStudentRelationshipFactory`**

`database/factories/ParentStudentRelationshipFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParentStudentRelationshipFactory extends Factory
{
    protected $model = ParentStudentRelationship::class;

    public function definition(): array
    {
        return [
            'parent_id' => ParentProfile::factory(),
            'student_id' => Student::factory(),
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'confirmed_by_user_id' => User::factory(),
            'confirmed_at' => now()->subDay(),
        ];
    }
}
```

- [ ] **Step 7: Tạo 3 controller**

`app/Http/Controllers/Api/UserManagement/CreateParentStudentRelationshipController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\Request;

class CreateParentStudentRelationshipController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['required', 'uuid', 'exists:parents,id'],
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'relationship_type' => ['nullable', 'string'],
        ]);

        $relationship = ParentStudentRelationship::create([
            'parent_id' => $data['parent_id'],
            'student_id' => $data['student_id'],
            'relationship_type' => $data['relationship_type'] ?? null,
            'status' => 'active',
            'valid_from' => now(),
            'confirmed_by_user_id' => $request->user()->id,
            'confirmed_at' => now(),
        ]);

        return response()->json(['parent_student_relationship' => [
            'id' => $relationship->id,
            'parent_id' => $relationship->parent_id,
            'student_id' => $relationship->student_id,
            'relationship_type' => $relationship->relationship_type,
        ]], 201);
    }
}
```

`app/Http/Controllers/Api/UserManagement/EndParentStudentRelationshipController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\Request;

class EndParentStudentRelationshipController extends Controller
{
    public function __invoke(Request $request, ParentStudentRelationship $parentStudentRelationship)
    {
        $data = $request->validate(['end_reason' => ['required', 'string']]);

        $parentStudentRelationship->update([
            'status' => 'ended',
            'valid_to' => now(),
            'ended_by_user_id' => $request->user()->id,
            'end_reason' => $data['end_reason'],
        ]);

        return response()->noContent();
    }
}
```

`app/Http/Controllers/Api/UserManagement/ListParentStudentRelationshipsController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\Request;

class ListParentStudentRelationshipsController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = ParentStudentRelationship::query();

        if ($studentId = $request->query('student_id')) {
            $query->where('student_id', $studentId);
        }

        if ($parentId = $request->query('parent_id')) {
            $query->where('parent_id', $parentId);
        }

        $relationships = $query->get()->map(fn ($r) => [
            'id' => $r->id,
            'parent_id' => $r->parent_id,
            'student_id' => $r->student_id,
            'relationship_type' => $r->relationship_type,
            'status' => $r->status,
        ]);

        return response()->json(['data' => $relationships]);
    }
}
```

- [ ] **Step 8: Thêm route**

Trong block `role:admin` ở `routes/api.php`, thêm:
```php
Route::post('/parent-student-relationships', \App\Http\Controllers\Api\UserManagement\CreateParentStudentRelationshipController::class);
Route::delete('/parent-student-relationships/{parentStudentRelationship}', \App\Http\Controllers\Api\UserManagement\EndParentStudentRelationshipController::class);
Route::get('/parent-student-relationships', \App\Http\Controllers\Api\UserManagement\ListParentStudentRelationshipsController::class);
```

- [ ] **Step 9: Chạy lại test**

Chạy: `php artisan test --filter=ParentStudentRelationshipTest`
Kỳ vọng: PASS (cả 5 test method).

- [ ] **Step 10: Commit**

```bash
git add app database routes
git commit -m "feat: add ParentStudentRelationship model and admin endpoints"
```

---

### Task 17: `GET /api/v1/me/children` ; `GET /api/v1/me/children/{student_id}`

**Files:**
- Tạo: `app/Http/Controllers/Api/UserManagement/GetMyChildrenController.php`
- Tạo: `app/Http/Controllers/Api/UserManagement/GetMyChildController.php`
- Sửa: `routes/api.php`
- Tạo: `tests/Feature/UserManagement/MyChildrenTest.php`

**Interfaces:**
- Dùng lại: `User::parentProfile()` (Task 7), `ParentProfile::relationships()` (Task 16).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\UserManagement;

use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyChildrenTest extends TestCase
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

    public function test_parent_can_list_their_linked_children(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        ParentStudentRelationship::factory()->create(['parent_id' => $parent->id, 'student_id' => $student->id]);
        $this->actingAsParent($parent);

        $response = $this->getJson('/api/v1/me/children');

        $response->assertOk();
        $response->assertJsonPath('data.0.student_id', $student->id);
    }

    public function test_parent_can_view_a_linked_childs_profile(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        ParentStudentRelationship::factory()->create(['parent_id' => $parent->id, 'student_id' => $student->id]);
        $this->actingAsParent($parent);

        $response = $this->getJson("/api/v1/me/children/{$student->id}");

        $response->assertOk();
        $response->assertJsonPath('student.id', $student->id);
    }

    public function test_parent_gets_404_not_403_for_an_unlinked_student(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        $this->actingAsParent($parent);

        $response = $this->getJson("/api/v1/me/children/{$student->id}");

        $response->assertStatus(404);
    }

    public function test_parent_gets_404_for_a_student_whose_link_has_ended(): void
    {
        $parent = ParentProfile::factory()->create();
        $student = Student::factory()->create();
        ParentStudentRelationship::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'status' => 'ended',
            'valid_to' => now()->subDay(),
        ]);
        $this->actingAsParent($parent);

        $response = $this->getJson("/api/v1/me/children/{$student->id}");

        $response->assertStatus(404);
    }

    public function test_non_parent_cannot_access_children_endpoints(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);

        $response = $this->actingAs($user)
            ->withSession(['active_role_code' => 'teacher'])
            ->getJson('/api/v1/me/children');

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=MyChildrenTest` → FAIL.

- [ ] **Step 3: Tạo 2 controller**

`app/Http/Controllers/Api/UserManagement/GetMyChildrenController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetMyChildrenController extends Controller
{
    public function __invoke(Request $request)
    {
        $parent = $request->user()->parentProfile;

        if (! $parent) {
            return response()->json(['data' => []]);
        }

        $children = $parent->relationships()
            ->where('status', 'active')
            ->with('student.personProfile')
            ->get()
            ->map(fn ($r) => [
                'student_id' => $r->student_id,
                'full_name' => $r->student->personProfile->full_name,
                'relationship_type' => $r->relationship_type,
            ]);

        return response()->json(['data' => $children]);
    }
}
```

`app/Http/Controllers/Api/UserManagement/GetMyChildController.php`:
```php
<?php

namespace App\Http\Controllers\Api\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetMyChildController extends Controller
{
    public function __invoke(Request $request, string $studentId)
    {
        $parent = $request->user()->parentProfile;

        $relationship = $parent
            ? $parent->relationships()->where('student_id', $studentId)->where('status', 'active')->first()
            : null;

        if (! $relationship) {
            return response()->json([
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Không tìm thấy.'],
            ], 404);
        }

        $student = $relationship->student->load('personProfile');

        return response()->json(['student' => [
            'id' => $student->id,
            'full_name' => $student->personProfile->full_name,
            'status' => $student->status,
        ]]);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong `routes/api.php`, bên trong `Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () { ... })`, thêm nhánh `role:parent` mới (ngang hàng `role:admin`, `role:admin,teacher`):
```php
Route::middleware('role:parent')->group(function () {
    Route::get('/me/children', \App\Http\Controllers\Api\UserManagement\GetMyChildrenController::class);
    Route::get('/me/children/{studentId}', \App\Http\Controllers\Api\UserManagement\GetMyChildController::class);
});
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=MyChildrenTest`
Kỳ vọng: PASS (cả 5 test method).

- [ ] **Step 6: Chạy toàn bộ test suite**

Chạy: `php artisan test`
Kỳ vọng: toàn bộ test từ Plan 1 + Task 1-17 của plan này đều PASS.

- [ ] **Step 7: Commit**

```bash
git add app routes
git commit -m "feat: add GET /api/v1/me/children and /me/children/{id} for parents"
```

---

## Self-Review (tự rà soát)

**Độ phủ so với spec (Section 4-8):**
- Section 4 (User & Role Management): `POST /students|/parents|/teachers` (T4), `PATCH /person-profiles/{id}` (T5), `PATCH /students|/parents|/teachers/{id}` (T6), `GET /students|/parents|/teachers/{id}` (T9), `GET /users` (T10), `GET /users/{id}` (T11), `PATCH /users/{id}` (T12), `POST`+`DELETE /users/{id}/roles` (T13), `POST /person-profiles/{id}/invite` (T14) — đủ.
- Section 5 (Profile self-service): `GET`+`PATCH /me/profile` (T15) — đủ.
- Section 6 (Parent xem Student): `GET /me/children`, `GET /me/children/{id}` (T17) — đủ.
- Section 7 (Parent-Student relationship): `POST`/`DELETE`/`GET /parent-student-relationships` (T16) — đủ.
- Section 8 (ClassMember + TeacherClassAssignment): toàn bộ 7 endpoint (T7 + T8) — đủ.

**Rà soát placeholder:** không còn TBD/TODO; mọi step đều có code chạy được thật. Hai điểm được ghi chú rõ là quyết định có chủ ý, không phải thiếu sót: (1) Subject/Class tối giản (Task 1); (2) `AuditLog` chưa wiring cho retroactive edit (Task 8) — cả hai đã liệt ở Global Constraints.

**Tính nhất quán kiểu dữ liệu:** `Teacher::activeClassIds()` và `Student::activeClassIds()` (cùng signature `: \Illuminate\Support\Collection` chứa `class_id`) được định nghĩa một lần (Task 7, Task 8) và dùng lại giống nhau ở Task 8 (roster scope), Task 9 (Student detail scope), Task 12 (BR-USER-010 guard) — không viết lại logic khác nhau ở nơi khác. `User::teacherProfile()/studentProfile()/parentProfile()` định nghĩa một lần ở Task 7, dùng lại ở Task 8, 9, 12, 15, 17.

**Review Focus:** cả 5 mục đều có task sở hữu và test tương ứng (Task 2 cho mục 1-2, Task 12 cho mục 3-4, Task 17 cho mục 5).

## Chuyển sang thực thi

Plan đã hoàn tất và lưu tại `docs/archive/superpowers/plans/2026-10-13-user-management-backend.md`. Mời bạn xem lại plan. Bạn muốn chọn cách thực thi nào?

- **Subagent-driven** — mỗi task được một "phiên AI con" triển khai, và một phiên khác review riêng trước khi sang task kế, cuối cùng review toàn nhánh một lượt. Kỹ nhất; tốn một phiên AI riêng cho mỗi task và mỗi lượt review (17 task ≈ 17 lượt implement + 17 lượt review).
- **Native** — mình tự triển khai hết các task trong session này, cuối cùng có một lượt review độc lập toàn nhánh bởi model mạnh nhất. Rẻ và nhanh nhất; không có review riêng từng task giữa đường.

Với plan này mình nghiêng về **Native**: 17 task phần lớn phụ thuộc tuần tự (Task 7→8→9→12, Task 3→4→6→9, Task 16→17 đều cần model/route của task trước), không độc lập với nhau, nên lợi ích chính của subagent-driven (review song song an toàn cho task độc lập) phát huy ít tác dụng — giống lý do đã chọn ở Plan 1. Plan có đúng ý bạn muốn không, và chọn cách thực thi nào?
