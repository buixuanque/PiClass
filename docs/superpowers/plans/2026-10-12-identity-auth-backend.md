# Plan triển khai Identity/Auth Backend

> **Dành cho agent thực thi:** REQUIRED SUB-SKILL: dùng `superpowers:subagent-driven-development` (khuyến nghị) hoặc `superpowers:executing-plans` để triển khai plan này theo từng task. Các step dùng cú pháp checkbox (`- [ ]`) để theo dõi tiến độ.

**Mục tiêu:** Khởi tạo backend Laravel 11 mới với PostgreSQL và triển khai các endpoint Identity/Auth (login, logout, me, đổi role context, quên/đặt lại/đổi mật khẩu) theo spec đã duyệt.

**Kiến trúc:** REST API Laravel 11 dưới `/api/v1`, dùng Laravel Sanctum cookie-based SPA session (không JWT), PostgreSQL với UUID primary key. Role context cho user đa role được lưu ở phía server trong session, không phải do client tự gửi. Plan này không có code frontend — đó là Plan 1b.

**Tech Stack:** Laravel 11.x, PHP 8.2+, PostgreSQL 16, Laravel Sanctum, PHPUnit (`RefreshDatabase` chạy trên database test riêng `piclass_test`, session driver `database`).

**Spec:** `docs/superpowers/specs/2026-10-12-identity-auth-user-management-api-design.md` — chỉ Section 1-3 (Section 4-8 thuộc User Management, nằm ở Plan 2, không thuộc phạm vi plan này).

## Global Constraints

- Base path API `/api/v1`; field JSON dùng `snake_case`.
- Response lỗi: `{ "error": { "code": "...", "message": "..." } }` kèm đúng HTTP status.
- Độ dài mật khẩu tối thiểu: 8 ký tự, không bắt buộc ký tự đặc biệt.
- Giới hạn đăng nhập: 5 lần/phút cho mỗi `login_identifier` + IP, có khóa tạm.
- Token đặt lại mật khẩu: 60 phút, dùng một lần.
- Session: 1 ngày khi `remember=false`; 30 ngày khi `remember=true`.
- Cho phép nhiều session đồng thời cho một user; logout chỉ hủy session hiện tại.
- Mọi entity nghiệp vụ dùng UUID làm primary key.
- User có `status=inactive` phải bị chặn đăng nhập, dù đúng mật khẩu (`BR-USER-008`).

## Review Focus

1. Lần thử sai mật khẩu thứ 6 trong vòng 1 phút (cùng tài khoản+IP) phải trả `429`, không được trả `422` vô hạn — xử lý ở Task 7.
2. Đăng nhập với user `status=inactive` phải thất bại dù đúng mật khẩu — xử lý ở Task 7.
3. `POST /auth/context` với `role_code` mà user không đang giữ hiệu lực (chưa từng có, hoặc `valid_to` đã ở quá khứ) phải trả `403`, không được đổi context — xử lý ở Task 4 (model) và Task 10 (endpoint).
4. Token đặt lại mật khẩu phải bị từ chối khi dùng lần thứ hai, và sau 60 phút — cả hai đều trả `422` — xử lý ở Task 11.
5. `POST /auth/password/forgot` với `login_identifier` không tồn tại vẫn phải trả `202`, để response không bao giờ lộ việc tài khoản có tồn tại hay không — xử lý ở Task 11.

---

### Task 1: Khởi tạo project Laravel + kết nối PostgreSQL

**Files:**
- Tạo: `backend/` (project Laravel 11 mới, do Composer sinh ra)
- Sửa: `backend/.env`, `backend/.env.example`
- Tạo: `backend/.env.testing`
- Sửa: `.gitignore` (gốc repo — đảm bảo `backend/.env` và `backend/.env.testing` không bị track; `backend/.gitignore` do Laravel sinh ra đã tự ignore `.env`)
- Tạo: `backend/tests/Feature/DatabaseConnectionTest.php`

**Interfaces:**
- Kết quả: một app Laravel 11 tại `backend/`, đã migrate lên PostgreSQL, có `routes/api.php` và Sanctum đã cài. Mọi task sau chạy lệnh từ trong `backend/`.

- [ ] **Step 1: Khởi tạo project**

Chạy từ gốc repo:
```bash
composer create-project laravel/laravel backend "11.*"
```

- [ ] **Step 2: Cấu hình kết nối database cho dev**

Sửa `backend/.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=piclass
DB_USERNAME=piclass
DB_PASSWORD=piclass_local_dev
SESSION_DRIVER=database
```

Chép cùng các key đó (giá trị placeholder) vào `backend/.env.example` để máy khác biết cần set gì.

- [ ] **Step 3: Cài API scaffolding + Sanctum**

```bash
cd backend
php artisan install:api
```
Xác nhận "Yes" khi được hỏi cài Laravel Sanctum. Lệnh này tạo `routes/api.php`, thêm package `laravel/sanctum`, và gắn API routing vào `bootstrap/app.php`.

- [ ] **Step 4: Tạo database test riêng và `.env.testing`**

Tạo một database PostgreSQL local thứ hai tên `piclass_test` (cùng credential với `piclass`). Tạo `backend/.env.testing`:
```env
APP_ENV=testing
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=piclass_test
DB_USERNAME=piclass
DB_PASSWORD=piclass_local_dev
SESSION_DRIVER=array
```
(`SESSION_DRIVER=array` cho test — không cần lưu session vào DB khi test.)

- [ ] **Step 5: Viết test thất bại trước**

Tạo `backend/tests/Feature/DatabaseConnectionTest.php`:
```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_can_connect_to_postgres(): void
    {
        $result = DB::select('select 1 as ok');

        $this->assertSame(1, $result[0]->ok);
    }
}
```

- [ ] **Step 6: Chạy migration, rồi chạy test**

```bash
php artisan migrate
php artisan test --filter=DatabaseConnectionTest
```
Kỳ vọng: PASS (xác nhận app kết nối được PostgreSQL `piclass_test` với credential đã cấu hình).

- [ ] **Step 7: Commit**

Từ gốc repo:
```bash
git add backend .gitignore
git commit -m "feat: scaffold Laravel 11 API project with PostgreSQL connection"
```

---

### Task 2: Migration + model PersonProfile

**Files:**
- Tạo: `backend/database/migrations/2026_10_12_000001_create_person_profiles_table.php`
- Tạo: `backend/app/Models/PersonProfile.php`
- Tạo: `backend/tests/Feature/Models/PersonProfileTest.php`

**Interfaces:**
- Kết quả: `App\Models\PersonProfile` (UUID PK, `full_name`, `email` nullable, `phone` nullable), được dùng bởi model `User` ở Task 4 và các model hồ sơ vai trò ở Plan 2.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Models;

use App\Models\PersonProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_person_profile_with_uuid(): void
    {
        $profile = PersonProfile::create([
            'full_name' => 'Nguyen Van A',
            'email' => 'a@example.com',
        ]);

        $this->assertTrue(Str::isUuid($profile->id));
        $this->assertDatabaseHas('person_profiles', [
            'id' => $profile->id,
            'full_name' => 'Nguyen Van A',
        ]);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=PersonProfileTest`
Kỳ vọng: FAIL (`Class "App\Models\PersonProfile" not found`).

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
        Schema::create('person_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_profiles');
    }
};
```

- [ ] **Step 4: Tạo model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PersonProfile extends Model
{
    use HasUuids;

    protected $fillable = ['full_name', 'email', 'phone'];
}
```

- [ ] **Step 5: Chạy migration + test**

```bash
php artisan migrate
php artisan test --filter=PersonProfileTest
```
Kỳ vọng: PASS.

- [ ] **Step 6: Commit**

```bash
git add backend
git commit -m "feat: add PersonProfile model and migration"
```

---

### Task 3: Migration + model + seeder cho Role

**Files:**
- Tạo: `backend/database/migrations/2026_10_12_000002_create_roles_table.php`
- Tạo: `backend/app/Models/Role.php`
- Tạo: `backend/database/factories/RoleFactory.php`
- Tạo: `backend/database/seeders/RoleSeeder.php`
- Sửa: `backend/database/seeders/DatabaseSeeder.php`
- Tạo: `backend/tests/Feature/Models/RoleSeederTest.php`

**Interfaces:**
- Kết quả: `App\Models\Role` với 4 giá trị `code` cố định `admin`/`teacher`/`student`/`parent`, chỉ seed một lần. Task 4 (`UserRole`) và Task 7/9/10 (endpoint auth) phụ thuộc đúng các mã này.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Models;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_four_fixed_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['code' => 'admin']);
        $this->assertDatabaseHas('roles', ['code' => 'teacher']);
        $this->assertDatabaseHas('roles', ['code' => 'student']);
        $this->assertDatabaseHas('roles', ['code' => 'parent']);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=RoleSeederTest` → FAIL.

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
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
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

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['code', 'name'];
}
```

- [ ] **Step 5: Tạo factory** (cần cho test ở các task sau, không cần cho seeder)

```php
<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->word(),
            'name' => fake()->word(),
        ];
    }
}
```

- [ ] **Step 6: Tạo seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'admin', 'name' => 'Admin'],
            ['code' => 'teacher', 'name' => 'Teacher'],
            ['code' => 'student', 'name' => 'Student'],
            ['code' => 'parent', 'name' => 'Parent'],
        ] as $role) {
            Role::firstOrCreate(['code' => $role['code']], $role);
        }
    }
}
```

- [ ] **Step 7: Đăng ký trong `DatabaseSeeder`**

Trong `backend/database/seeders/DatabaseSeeder.php`, thay nội dung method `run()`:
```php
public function run(): void
{
    $this->call(RoleSeeder::class);
}
```

- [ ] **Step 8: Chạy migration + test**

```bash
php artisan migrate
php artisan test --filter=RoleSeederTest
```
Kỳ vọng: PASS.

- [ ] **Step 9: Commit**

```bash
git add backend
git commit -m "feat: add Role model, migration, and seeder for the 4 fixed roles"
```

---

### Task 4: Migration, model và factory cho User + UserRole

**Files:**
- Sửa: `backend/database/migrations/0001_01_01_000000_create_users_table.php` (chỉ thay block `users`; giữ nguyên block `password_reset_tokens` và `sessions` Laravel đã sinh sẵn trong cùng file)
- Tạo: `backend/database/migrations/2026_10_12_000003_create_user_roles_table.php`
- Sửa: `backend/app/Models/User.php` (thay toàn bộ)
- Tạo: `backend/app/Models/UserRole.php`
- Sửa: `backend/database/factories/UserFactory.php` (thay toàn bộ)
- Tạo: `backend/tests/Feature/Models/UserRoleTest.php`

**Interfaces:**
- Kết quả: `App\Models\User::hasActiveRole(string $roleCode): bool` — dùng ở Task 10 (`ContextController`) để từ chối role mà user không đang giữ. `App\Models\UserRole` có `valid_from`/`valid_to`; `null` hoặc `valid_to` ở tương lai nghĩa là còn hiệu lực.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Models;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_active_role_when_valid_to_is_null(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $this->assertTrue($user->hasActiveRole('teacher'));
    }

    public function test_user_does_not_have_active_role_when_valid_to_in_past(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->subDay(),
        ]);

        $this->assertFalse($user->hasActiveRole('teacher'));
    }

    public function test_user_does_not_have_a_role_it_never_held(): void
    {
        $user = User::factory()->create();
        Role::factory()->create(['code' => 'admin']);

        $this->assertFalse($user->hasActiveRole('admin'));
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=UserRoleTest` → FAIL (`User::factory()` chưa dùng được / chưa có method `hasActiveRole`).

- [ ] **Step 3: Thay block `users` trong migration mặc định**

Trong `backend/database/migrations/0001_01_01_000000_create_users_table.php`, chỉ thay block `Schema::create('users', ...)` đầu tiên (giữ nguyên block `password_reset_tokens` và `sessions`):
```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('person_profile_id')->nullable()->unique()
        ->constrained('person_profiles')->nullOnDelete();
    $table->string('login_identifier')->nullable()->unique();
    $table->string('password');
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamp('last_login_at')->nullable();
    $table->timestamps();
});
```
`down()` giữ nguyên (đã sẵn `Schema::dropIfExists('users')`).

- [ ] **Step 4: Tạo migration `user_roles`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->foreignUuid('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
```

- [ ] **Step 5: Thay model `User`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'person_profile_id', 'login_identifier', 'password', 'status', 'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function personProfile()
    {
        return $this->belongsTo(PersonProfile::class);
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasActiveRole(string $roleCode): bool
    {
        return $this->userRoles()
            ->whereHas('role', fn ($q) => $q->where('code', $roleCode))
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
            ->exists();
    }

    /**
     * Laravel's password-reset broker stores/looks up tokens by this value.
     * We don't have a column literally named "email"; login_identifier plays that role.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->login_identifier;
    }
}
```

- [ ] **Step 6: Tạo model `UserRole`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'role_id', 'valid_from', 'valid_to',
        'assigned_by_user_id', 'ended_by_user_id', 'end_reason',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 7: Thay `UserFactory`**

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'login_identifier' => fake()->unique()->safeEmail(),
            'password' => 'password', // tự được hash bởi cast 'hashed' khi save
            'status' => 'active',
        ];
    }
}
```

- [ ] **Step 8: Chạy migration + test**

```bash
php artisan migrate:fresh
php artisan test --filter=UserRoleTest
```
Kỳ vọng: PASS.

- [ ] **Step 9: Commit**

```bash
git add backend
git commit -m "feat: add User and UserRole models with role-history lookup"
```

---

### Task 5: Cấu hình Sanctum SPA session

**Files:**
- Sửa: `backend/.env`, `backend/.env.example` (thêm `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `FRONTEND_URL`)
- Sửa: `backend/config/cors.php`
- Sửa: `backend/bootstrap/app.php`
- Tạo: `backend/tests/Feature/Auth/CsrfCookieTest.php`

**Interfaces:**
- Kết quả: route `GET /sanctum/csrf-cookie` chạy được, và middleware group `api` coi request cùng site từ Next.js là "stateful" (xác thực bằng cookie) — mọi endpoint ở Task 7-12 phụ thuộc vào cấu hình này.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class CsrfCookieTest extends TestCase
{
    public function test_csrf_cookie_endpoint_sets_cookie(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertNoContent();
        $response->assertCookie('XSRF-TOKEN');
    }
}
```

- [ ] **Step 2: Chạy để xem trạng thái hiện tại**

Chạy: `php artisan test --filter=CsrfCookieTest`
Nếu Task 1 (`install:api`) đã tự cài Sanctum, test này có thể đã pass sẵn — nếu vậy vẫn tiếp tục Step 3 để chốt cấu hình rõ ràng, không bỏ qua phần config dưới đây chỉ vì test đang xanh.

- [ ] **Step 3: Cấu hình stateful domain và CORS**

Thêm vào `backend/.env` và `backend/.env.example`:
```env
FRONTEND_URL=http://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

Trong `backend/config/cors.php`, set:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
'supports_credentials' => true,
```

- [ ] **Step 4: Đảm bảo middleware stateful của Sanctum được áp vào group API**

Trong `backend/bootstrap/app.php`, trong `->withMiddleware(function (Middleware $middleware) { ... })`, thêm:
```php
$middleware->api(prepend: [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
]);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=CsrfCookieTest`
Kỳ vọng: PASS.

- [ ] **Step 6: Commit**

```bash
git add backend
git commit -m "feat: configure Sanctum SPA stateful auth and CORS for the Next.js frontend"
```

---

### Task 6: Error envelope JSON chung

**Files:**
- Sửa: `backend/bootstrap/app.php`
- Tạo: `backend/tests/Feature/ErrorEnvelopeTest.php`

**Interfaces:**
- Kết quả: mọi response lỗi trên `api/*` có dạng `{ "error": { "code", "message" } }`. Test ở Task 7-12 dựa vào hình dạng này cho các assertion lỗi.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorEnvelopeTest extends TestCase
{
    public function test_404_on_api_routes_uses_the_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response->assertStatus(404);
        $response->assertJsonStructure(['error' => ['code', 'message']]);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ErrorEnvelopeTest` → FAIL (response 404 mặc định của Laravel có dạng `{"message": "..."}`, không phải `{"error": {...}}`).

- [ ] **Step 3: Thêm xử lý render exception**

Trong `backend/bootstrap/app.php`, bên trong `->withExceptions(function (Exceptions $exceptions) { ... })`:
```php
$exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
    if (! $request->is('api/*')) {
        return null;
    }

    return response()->json([
        'error' => [
            'code' => 'VALIDATION_FAILED',
            'message' => $e->getMessage(),
            'details' => $e->errors(),
        ],
    ], 422);
});

$exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, $request) {
    if (! $request->is('api/*')) {
        return null;
    }

    $status = $e->getStatusCode();
    $code = match (true) {
        $status === 401 => 'UNAUTHENTICATED',
        $status === 403 => 'FORBIDDEN',
        $status === 404 => 'NOT_FOUND',
        $status === 409 => 'CONFLICT',
        $status === 429 => 'TOO_MANY_REQUESTS',
        default => 'ERROR',
    };

    return response()->json([
        'error' => ['code' => $code, 'message' => $e->getMessage() ?: 'Request failed.'],
    ], $status);
});
```

- [ ] **Step 4: Chạy lại test**

Chạy: `php artisan test --filter=ErrorEnvelopeTest`
Kỳ vọng: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend
git commit -m "feat: return a consistent JSON error envelope for all api/* errors"
```

---

### Task 7: `POST /api/v1/auth/login`

**Files:**
- Tạo: `backend/app/Http/Requests/Auth/LoginRequest.php`
- Tạo: `backend/app/Http/Controllers/Api/Auth/LoginController.php`
- Sửa: `backend/routes/api.php`
- Tạo: `backend/tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Dùng lại: gián tiếp `User::hasActiveRole()` qua danh sách `available_roles` (lấy từ `userRoles()`); error envelope ở Task 6.
- Kết quả: `POST /api/v1/auth/login` — thành công thì bắt đầu session stateful của Sanctum, trả `{ "user": {...}, "available_roles": [...] }`. Task 9 (`/auth/me`) và Task 10 (`/auth/context`) dùng lại cùng logic "lấy danh sách mã role đang hiệu lực" — viết một lần ở đây, copy lại (chưa cần tách helper chung tới khi có điểm gọi thứ 3, theo YAGNI).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_in_with_correct_credentials_and_lists_active_roles(): void
    {
        $user = User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.login_identifier', 'teacher@example.com');
        $response->assertJson(['available_roles' => ['teacher']]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejects_wrong_password(): void
    {
        User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_rejects_inactive_user_even_with_correct_password(): void
    {
        User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_locks_out_after_five_failed_attempts_in_a_minute(): void
    {
        User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login_identifier' => 'teacher@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=LoginTest` → FAIL (route chưa tồn tại).

- [ ] **Step 3: Tạo form request**

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
```

- [ ] **Step 4: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        $throttleKey = Str::lower($request->input('login_identifier')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'error' => [
                    'code' => 'TOO_MANY_ATTEMPTS',
                    'message' => "Too many login attempts. Try again in {$seconds} seconds.",
                ],
            ], 429);
        }

        $user = User::where('login_identifier', $request->input('login_identifier'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password) || $user->status !== 'active') {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'login_identifier' => 'Thông tin đăng nhập không đúng.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        config(['session.lifetime' => $request->boolean('remember') ? 60 * 24 * 30 : 60 * 24 * 1]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'login_identifier' => $user->login_identifier,
            ],
            'available_roles' => $user->userRoles()
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
                ->with('role')
                ->get()
                ->pluck('role.code'),
        ]);
    }
}
```

- [ ] **Step 5: Đăng ký route**

Thay toàn bộ nội dung `backend/routes/api.php` bằng:
```php
<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', LoginController::class);
});
```
(Các task sau thêm route khác vào bên trong cùng block `Route::prefix('v1')->group(...)` này.)

- [ ] **Step 6: Chạy lại test**

Chạy: `php artisan test --filter=LoginTest`
Kỳ vọng: PASS (cả 4 test method).

- [ ] **Step 7: Commit**

```bash
git add backend
git commit -m "feat: add POST /api/v1/auth/login with rate limiting and remember-me session lifetime"
```

---

### Task 8: `POST /api/v1/auth/logout`

**Files:**
- Tạo: `backend/app/Http/Controllers/Api/Auth/LogoutController.php`
- Sửa: `backend/routes/api.php`
- Tạo: `backend/tests/Feature/Auth/LogoutTest.php`

**Interfaces:**
- Dùng lại: session đã xác thực được bắt đầu ở Task 7.
- Kết quả: `POST /api/v1/auth/logout` — chỉ hủy session hiện tại; các session/thiết bị khác của cùng user vẫn đang đăng nhập (đúng yêu cầu "cho phép nhiều session đồng thời", vì mỗi trình duyệt/thiết bị có session riêng độc lập).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertNoContent();
        $this->assertGuest();
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=LogoutTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `Route::prefix('v1')->group(function () { ... })` ở `backend/routes/api.php`, thêm:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', \App\Http\Controllers\Api\Auth\LogoutController::class);
});
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=LogoutTest`
Kỳ vọng: PASS.

- [ ] **Step 6: Commit**

```bash
git add backend
git commit -m "feat: add POST /api/v1/auth/logout"
```

---

### Task 9: `GET /api/v1/auth/me`

**Files:**
- Tạo: `backend/app/Http/Controllers/Api/Auth/MeController.php`
- Sửa: `backend/routes/api.php`
- Tạo: `backend/tests/Feature/Auth/MeTest.php`

**Interfaces:**
- Kết quả: `GET /api/v1/auth/me` → `{ "user": {...}, "active_role": string|null, "available_roles": [...] }`. `active_role` đọc key session `active_role_code` mà Task 10 ghi vào.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_current_user_and_available_roles(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('user.login_identifier', 'teacher@example.com');
        $response->assertJson(['available_roles' => ['teacher']]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=MeTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'login_identifier' => $user->login_identifier,
            ],
            'active_role' => $request->session()->get('active_role_code'),
            'available_roles' => $user->userRoles()
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
                ->with('role')
                ->get()
                ->pluck('role.code'),
        ]);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `Route::middleware('auth:sanctum')->group(...)` thêm ở Task 8, thêm:
```php
Route::get('/auth/me', \App\Http\Controllers\Api\Auth\MeController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=MeTest`
Kỳ vọng: PASS.

- [ ] **Step 6: Commit**

```bash
git add backend
git commit -m "feat: add GET /api/v1/auth/me"
```

---

### Task 10: `POST /api/v1/auth/context`

**Files:**
- Tạo: `backend/app/Http/Requests/Auth/SetContextRequest.php`
- Tạo: `backend/app/Http/Controllers/Api/Auth/ContextController.php`
- Sửa: `backend/routes/api.php`
- Tạo: `backend/tests/Feature/Auth/ContextTest.php`

**Interfaces:**
- Dùng lại: `User::hasActiveRole()` từ Task 4.
- Kết quả: `POST /api/v1/auth/context` — ghi `active_role_code` vào session, được Task 9 (`/auth/me`) đọc lại. Trả `403` kèm code `ROLE_NOT_HELD` nếu user không đang giữ hiệu lực role đó.

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_switches_to_a_role_the_user_currently_holds(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/context', ['role_code' => 'admin']);

        $response->assertOk();
        $response->assertJson(['active_role' => 'admin']);
    }

    public function test_rejects_a_role_the_user_never_held(): void
    {
        $user = User::factory()->create();
        Role::factory()->create(['code' => 'admin']);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/context', ['role_code' => 'admin']);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'ROLE_NOT_HELD');
    }

    public function test_rejects_a_role_whose_assignment_has_ended(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/context', ['role_code' => 'teacher']);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ContextTest` → FAIL.

- [ ] **Step 3: Tạo form request**

```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SetContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_code' => ['required', 'string', 'in:admin,teacher,student,parent'],
        ];
    }
}
```

- [ ] **Step 4: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetContextRequest;

class ContextController extends Controller
{
    public function __invoke(SetContextRequest $request)
    {
        $roleCode = $request->input('role_code');

        if (! $request->user()->hasActiveRole($roleCode)) {
            return response()->json([
                'error' => ['code' => 'ROLE_NOT_HELD', 'message' => 'User does not currently hold this role.'],
            ], 403);
        }

        $request->session()->put('active_role_code', $roleCode);

        return response()->json(['active_role' => $roleCode]);
    }
}
```

- [ ] **Step 5: Thêm route**

Trong block `Route::middleware('auth:sanctum')->group(...)`, thêm:
```php
Route::post('/auth/context', \App\Http\Controllers\Api\Auth\ContextController::class);
```

- [ ] **Step 6: Chạy lại test**

Chạy: `php artisan test --filter=ContextTest`
Kỳ vọng: PASS.

- [ ] **Step 7: Commit**

```bash
git add backend
git commit -m "feat: add POST /api/v1/auth/context for server-side role switching"
```

---

### Task 11: Quên mật khẩu + đặt lại mật khẩu

**Files:**
- Sửa: `backend/.env`, `backend/.env.example` (mail driver cho dev)
- Tạo: `backend/app/Http/Controllers/Api/Auth/ForgotPasswordController.php`
- Tạo: `backend/app/Http/Controllers/Api/Auth/ResetPasswordController.php`
- Sửa: `backend/routes/api.php`
- Tạo: `backend/tests/Feature/Auth/PasswordResetTest.php`

**Interfaces:**
- Dùng lại: `User::getEmailForPasswordReset()` từ Task 4 (để bảng `password_reset_tokens` có sẵn của Laravel dùng `login_identifier` thay vì cột `email` thật), và `config('auth.passwords.users.expire')` mà Laravel mặc định là 60 phút (đúng với spec — xác nhận bằng test, không cần đổi).
- Kết quả: `POST /api/v1/auth/password/forgot` (luôn `202`) và `POST /api/v1/auth/password/reset` (dùng token).

- [ ] **Step 1: Set mail driver cho dev**

Thêm vào `backend/.env` và `backend/.env.example`:
```env
MAIL_MAILER=log
```
(Email đặt lại mật khẩu được ghi vào `storage/logs/laravel.log` lúc dev; nhà cung cấp SMTP/mailer thật là quyết định lúc deploy, ngoài phạm vi plan này.)

- [ ] **Step 2: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_returns_202_for_an_unknown_account(): void
    {
        $response = $this->postJson('/api/v1/auth/password/forgot', [
            'login_identifier' => 'nobody@example.com',
        ]);

        $response->assertStatus(202);
    }

    public function test_forgot_password_returns_202_for_a_real_account(): void
    {
        User::factory()->create(['login_identifier' => 'teacher@example.com']);

        $response = $this->postJson('/api/v1/auth/password/forgot', [
            'login_identifier' => 'teacher@example.com',
        ]);

        $response->assertStatus(202);
    }

    public function test_reset_password_with_a_valid_token_succeeds(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'newpassword123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'newpassword123',
        ])->assertOk();

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'anotherpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_token_expires_after_60_minutes(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        Carbon::setTestNow(now()->addMinutes(61));

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(422);

        Carbon::setTestNow();
    }

    public function test_reset_password_requires_minimum_length(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'short',
        ]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 3: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=PasswordResetTest` → FAIL (route chưa tồn tại).

- [ ] **Step 4: Tạo controller quên mật khẩu**

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['login_identifier' => ['required', 'string']]);

        // Chủ ý bỏ qua kết quả trả về của broker — luôn trả 202
        // để response không bao giờ lộ việc tài khoản có tồn tại hay không.
        Password::broker()->sendResetLink([
            'login_identifier' => $request->input('login_identifier'),
        ]);

        return response()->json([], 202);
    }
}
```

- [ ] **Step 5: Tạo controller đặt lại mật khẩu**

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'login_identifier' => ['required', 'string'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $status = Password::broker()->reset(
            [
                'login_identifier' => $request->input('login_identifier'),
                'token' => $request->input('token'),
                'password' => $request->input('password'),
            ],
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Token không hợp lệ hoặc đã hết hạn.',
            ]);
        }

        return response()->json([], 200);
    }
}
```

- [ ] **Step 6: Thêm route**

Trong block `Route::prefix('v1')->group(function () { ... })` ở ngoài cùng (ngoài group `auth:sanctum` — hai route này chạy trước khi đăng nhập), thêm:
```php
Route::post('/auth/password/forgot', \App\Http\Controllers\Api\Auth\ForgotPasswordController::class);
Route::post('/auth/password/reset', \App\Http\Controllers\Api\Auth\ResetPasswordController::class);
```

- [ ] **Step 7: Chạy lại test**

Chạy: `php artisan test --filter=PasswordResetTest`
Kỳ vọng: PASS (cả 6 test method).

- [ ] **Step 8: Commit**

```bash
git add backend
git commit -m "feat: add password forgot/reset endpoints with 60-minute single-use tokens"
```

---

### Task 12: `PATCH /api/v1/me/password`

**Files:**
- Tạo: `backend/app/Http/Controllers/Api/Auth/ChangePasswordController.php`
- Sửa: `backend/routes/api.php`
- Tạo: `backend/tests/Feature/Auth/ChangePasswordTest.php`

**Interfaces:**
- Kết quả: `PATCH /api/v1/me/password` cho user đã đăng nhập tự đổi mật khẩu (yêu cầu mật khẩu hiện tại).

- [ ] **Step 1: Viết test thất bại trước**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword123']);

        $response = $this->actingAs($user)->patchJson('/api/v1/me/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    public function test_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword123']);

        $response = $this->actingAs($user)->patchJson('/api/v1/me/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $this->assertTrue(Hash::check('oldpassword123', $user->refresh()->password));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->patchJson('/api/v1/me/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }
}
```

- [ ] **Step 2: Chạy để xác nhận thất bại**

Chạy: `php artisan test --filter=ChangePasswordTest` → FAIL.

- [ ] **Step 3: Tạo controller**

```php
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mật khẩu hiện tại không đúng.',
            ]);
        }

        $user->forceFill(['password' => $request->input('new_password')])->save();

        return response()->json([], 200);
    }
}
```

- [ ] **Step 4: Thêm route**

Trong block `Route::middleware('auth:sanctum')->group(...)`, thêm:
```php
Route::patch('/me/password', \App\Http\Controllers\Api\Auth\ChangePasswordController::class);
```

- [ ] **Step 5: Chạy lại test**

Chạy: `php artisan test --filter=ChangePasswordTest`
Kỳ vọng: PASS.

- [ ] **Step 6: Chạy toàn bộ test suite**

```bash
php artisan test
```
Kỳ vọng: toàn bộ test từ Task 1-12 đều PASS.

- [ ] **Step 7: Commit**

```bash
git add backend
git commit -m "feat: add PATCH /api/v1/me/password"
```

---

## Self-Review (tự rà soát)

**Độ phủ so với spec:** mọi endpoint ở Section 3 của spec đã duyệt đều có task tương ứng — `csrf-cookie` (Task 5), `login` (Task 7), `logout` (Task 8), `me` (Task 9), `context` (Task 10), `password/forgot` + `password/reset` (Task 11), `me/password` (Task 12). Quy ước chung (Section 2: base path, snake_case, error envelope) được xử lý ở Task 6 và route prefix của Task 7. Section 4-8 (User Management) chủ ý nằm ngoài phạm vi — thuộc Plan 2.

**Rà soát placeholder:** không còn TBD/TODO; mọi step đều có code chạy được thật.

**Tính nhất quán kiểu dữ liệu:** `hasActiveRole(string $roleCode): bool` trên `User` (Task 4) được dùng giống hệt ở `ContextController` (Task 10). Logic "lấy danh sách mã role đang hiệu lực" được viết giống nhau ở Task 7 và Task 9 (lặp lại có chủ ý — xem ghi chú Interfaces ở Task 7; chỉ tách helper chung khi có điểm gọi thứ 3, theo nguyên tắc YAGNI).

**Review Focus:** cả 5 mục đều có task sở hữu và test tương ứng (đã liệt kê phía trên mỗi task, phần Interfaces/test).

## Chuyển sang thực thi

Plan đã hoàn tất và lưu tại `docs/superpowers/plans/2026-10-12-identity-auth-backend.md`. Mời bạn xem lại plan. Bạn muốn chọn cách thực thi nào?

- **Subagent-driven** — mỗi task được một "phiên AI con" triển khai, và một phiên khác review riêng trước khi sang task kế, cuối cùng review toàn nhánh một lượt. Kỹ nhất; tốn một phiên AI riêng cho mỗi task và mỗi lượt review (12 task ≈ 12 lượt implement + 12 lượt review).
- **Native** — mình tự triển khai hết các task trong session này, cuối cùng có một lượt review độc lập toàn nhánh bởi model mạnh nhất. Rẻ và nhanh nhất; không có review riêng từng task giữa đường.

Với plan này mình nghiêng về **Native**: 12 task ở đây phần lớn phụ thuộc tuần tự nhau (task sau cần model/migration của task trước) hơn là độc lập, nên lợi ích chính của subagent-driven — review song song an toàn cho từng task độc lập — phát huy ít tác dụng ở đây, và một lượt review cuối nhánh là đủ để bắt lỗi thật. Plan có đúng ý bạn muốn không, và chọn cách thực thi nào?
