# Notification + File + Audit — Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the Laravel 11 backend for Module 5 (Notification + File + Audit) in `piclass-api/` — new `AuditLog`/`Notification`/`Attachment` schema and services, a real file-upload API over S3-compatible storage, and retrofitting `AuditLogger`/`NotificationDispatcher` calls into ~20 existing controllers from Module 1-3.

**Architecture:** Three new pure services (`AuditLogger`, `NotificationDispatcher`) plus Laravel Storage-backed Attachment endpoints. Retrofitting existing controllers is grouped by source module (User Management / Attendance+Lesson / Assessment) into three tasks, since each group's controllers are edited for the same reason and share the same recipient/scope-building patterns. Module 4 (Submission/Grading) has no code yet — its retrofit (Grade/Result/Attempt events) is explicitly deferred, documented at the end of this plan, not a task here.

**Tech Stack:** Laravel 11, PostgreSQL, Laravel Storage (S3-compatible driver, same `s3` disk key used for both MinIO dev/staging and AWS S3 prod), PHPUnit (`RefreshDatabase`).

**Spec:** `docs/archive/superpowers/specs/2026-10-15-notification-file-audit-api-design.md`

## Global Constraints

- `AuditLog` is append-only: no `PATCH`/`DELETE` route or controller is ever written for it.
- `AuditLogger::record()` and `NotificationDispatcher::dispatch()` are called **inline, directly inside existing controllers** — no Model Observers/Events (decided during brainstorming: `actor`/`reason` are only reliably available in request context).
- Notification dedupe relies on the DB `unique(recipient_user_id, event_key)` constraint via `firstOrCreate` — never pre-check-then-insert (race-prone); let the unique index do the work.
- File size/type limits come from `config('attachments.max_size_kb')` / `config('attachments.allowed_mime_types')`, never hardcoded in a controller.
- Every new UUID route parameter (`attachment`, `notification`) must be added to the `Route::pattern(...)` loop in `routes/api.php`.
- No Form Request/Policy/Resource classes — inline `$request->validate()`, inline `abort_unless`/`abort_if`, raw array `response()->json([...])` — matches every existing controller.
- `reason` field names already differ across existing controllers (`reason`, `end_reason`, `cancel_reason`, `cancellation_reason`) — when retrofitting, read the existing field under its existing name and pass it to `AuditLogger::record(reason: ...)`; never rename the request field.

## Review Focus

- `UpdateClassMemberController` already validates `reason` as required (`BR-USER-017`) but currently discards it (`array_intersect_key` drops it before `fill()`) — the retrofit must wire that exact existing value into `AuditLogger::record()`, not silently keep dropping it.
- `UpdateLessonSessionController`'s `reason` is only required when `$lessonSession->status === 'completed'` — a naive retrofit that calls `AuditLogger::record(reason: $reason)` unconditionally would pass `null` for ordinary (non-completed) edits; the audit call must only fire when `$reason !== null`, matching the existing `LessonSessionHistory::create()` guard in that same controller.
- `AddAssessmentAudienceController` loops over `StudentAssessmentSync::ensureForStudent()` per student for `class_id`/`course_id` targets — Notification recipients must be built from that same student list (the ones a `StudentAssessment` was actually ensured for), not re-derived from a fresh `ClassMember`/`CourseEnrollment` query that could race with concurrent membership changes.
- A Parent whose `ParentStudentRelationship.status` is `'ended'` (even if `valid_to` is still in the future due to a data-entry quirk) must never receive a Notification or appear in an Attachment-view-permission check — gate on `status === 'active'`, not only on `valid_to`, since that's the actual column this codebase uses (confirmed in `ParentStudentRelationship.php`).
- Uploading a file larger than `config('attachments.max_size_kb')` or with a disallowed MIME type must return `422` before anything touches Storage — a careless implementation that calls `Storage::put()` first and validates after would leave an orphaned object on S3/MinIO.

---

## Task 1: AuditLog — migration, model, `AuditLogger` service

**Files:**
- Create: `database/migrations/2026_10_17_000001_create_audit_logs_table.php`
- Create: `app/Models/AuditLog.php`
- Create: `app/Services/Audit/AuditLogger.php`
- Create: `database/factories/AuditLogFactory.php`
- Test: `tests/Unit/Services/Audit/AuditLoggerTest.php`

**Interfaces:**
- Produces: `AuditLog::class` fillable `actor_user_id, action, entity_type, entity_id, occurred_at, before_data, after_data, reason, correlation_key`; `$timestamps = false`; casts `before_data`/`after_data` => `array`, `occurred_at` => `datetime`.
- Produces: `AuditLogger::record(?User $actor, string $action, string $entityType, string $entityId, ?array $before = null, ?array $after = null, ?string $reason = null, ?string $correlationKey = null): AuditLog`.

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('entity_type');
            $table->uuid('entity_id');
            $table->timestamp('occurred_at');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('reason')->nullable();
            $table->string('correlation_key')->nullable();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `cd piclass-api && php artisan migrate`
Expected: 1 new migration applied.

- [ ] **Step 3: Write the model and factory**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['actor_user_id', 'action', 'entity_type', 'entity_id', 'occurred_at', 'before_data', 'after_data', 'reason', 'correlation_key'];

    protected $casts = ['before_data' => 'array', 'after_data' => 'array', 'occurred_at' => 'datetime'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
```

```php
<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'action' => 'test.action',
            'entity_type' => 'Test',
            'entity_id' => (string) \Illuminate\Support\Str::uuid(),
            'occurred_at' => now(),
        ];
    }
}
```

- [ ] **Step 4: Write the failing test**

```php
<?php

namespace Tests\Unit\Services\Audit;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_persists_actor_action_and_before_after(): void
    {
        $actor = User::factory()->create();

        $log = (new AuditLogger())->record(
            actor: $actor,
            action: 'grade.update',
            entityType: 'Grade',
            entityId: 'a0000000-0000-0000-0000-000000000001',
            before: ['score' => 5],
            after: ['score' => 9],
            reason: 'chấm lại',
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'actor_user_id' => $actor->id,
            'action' => 'grade.update',
            'entity_type' => 'Grade',
            'reason' => 'chấm lại',
        ]);
        $this->assertSame(['score' => 5], $log->before_data);
        $this->assertSame(['score' => 9], $log->after_data);
    }

    public function test_record_accepts_null_actor_before_after_and_reason(): void
    {
        $log = (new AuditLogger())->record(
            actor: null,
            action: 'system.cleanup',
            entityType: 'Attachment',
            entityId: 'a0000000-0000-0000-0000-000000000002',
        );

        $this->assertNull($log->actor_user_id);
        $this->assertNull($log->before_data);
        $this->assertNull($log->reason);
    }
}
```

Run: `php artisan test --filter=AuditLoggerTest`
Expected: FAIL (`Class "App\Services\Audit\AuditLogger" not found`).

- [ ] **Step 5: Implement `AuditLogger`**

```php
<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    public function record(
        ?User $actor,
        string $action,
        string $entityType,
        string $entityId,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        ?string $correlationKey = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'occurred_at' => now(),
            'before_data' => $before,
            'after_data' => $after,
            'reason' => $reason,
            'correlation_key' => $correlationKey,
        ]);
    }
}
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=AuditLoggerTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_10_17_000001_create_audit_logs_table.php app/Models/AuditLog.php app/Services/Audit/AuditLogger.php database/factories/AuditLogFactory.php tests/Unit/Services/Audit/AuditLoggerTest.php
git commit -m "feat: add AuditLog schema, model, and append-only AuditLogger service"
```

---

## Task 2: AuditLog view endpoint

**Files:**
- Create: `app/Http/Controllers/Api/Audit/ListAuditLogsController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Audit/ListAuditLogsTest.php`

**Interfaces:**
- Consumes: `AuditLog::class` (Task 1).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\PersonProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_audit_logs(): void
    {
        $admin = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        AuditLog::factory()->create(['entity_type' => 'Grade']);
        AuditLog::factory()->create(['entity_type' => 'ClassMember']);
        $this->actingAs($admin)->withSession(['active_role_code' => 'admin']);

        $response = $this->getJson('/api/v1/audit-logs');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_teacher_only_sees_class_member_logs_for_their_assigned_class(): void
    {
        $teacherUser = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $teacher = Teacher::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $teacherUser->id])->id]);
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::create(['class_id' => $class->id, 'teacher_id' => $teacher->id, 'valid_from' => now()->subDay(), 'status' => 'active']);

        AuditLog::factory()->create(['entity_type' => 'ClassMember', 'entity_id' => $class->id, 'correlation_key' => $class->id]);
        AuditLog::factory()->create(['entity_type' => 'ClassMember', 'entity_id' => 'b0000000-0000-0000-0000-000000000099', 'correlation_key' => 'b0000000-0000-0000-0000-000000000099']);
        $this->actingAs($teacherUser)->withSession(['active_role_code' => 'teacher']);

        $response = $this->getJson('/api/v1/audit-logs?entity_type=ClassMember');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
```

Run: `php artisan test --filter=ListAuditLogsTest`
Expected: FAIL (route not found).

- [ ] **Step 2: Register route**

```php
// routes/api.php, inside the existing role:admin,teacher group (create it if the entity needs both — here we add a new combined route reachable by both roles)
Route::get('/audit-logs', \App\Http\Controllers\Api\Audit\ListAuditLogsController::class);
```

Add this line inside the `role:admin,teacher` group (the one already used by Assessment/Lesson/Attendance routes) so both roles can reach it; the controller itself narrows what a Teacher actually sees.

- [ ] **Step 3: Write the controller**

Note on scoping: business-history entities (`ClassMember`, `TeacherClassAssignment`, `Schedule`, `LessonSession`, `AttendanceRecord`, `Grade`) carry a `correlation_key` set to the relevant `class_id` at write time (wired in Tasks 5-7) so a Teacher's view can filter by it without joining back through 5 different tables.

```php
<?php

namespace App\Http\Controllers\Api\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ListAuditLogsController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = AuditLog::query();

        if ($entityType = $request->query('entity_type')) {
            $query->where('entity_type', $entityType);
        }
        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id', $entityId);
        }
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole !== 'admin') {
            $teacher = $request->user()->teacherProfile;
            abort_unless($teacher, 404);
            $classIds = $teacher->activeClassIds();
            $query->whereIn('correlation_key', $classIds);
        }

        return response()->json(['data' => $query->orderByDesc('occurred_at')->get()]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ListAuditLogsTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Audit/ListAuditLogsController.php routes/api.php tests/Feature/Audit/ListAuditLogsTest.php
git commit -m "feat: add GET /audit-logs scoped by role"
```

---

## Task 3: Notification + NotificationContext — migrations, models, `NotificationDispatcher`

**Files:**
- Create: `database/migrations/2026_10_17_000002_create_notifications_table.php`
- Create: `database/migrations/2026_10_17_000003_create_notification_contexts_table.php`
- Create: `app/Models/Notification.php`, `app/Models/NotificationContext.php`
- Create: `app/Services/Notification/NotificationDispatcher.php`
- Create: `database/factories/NotificationFactory.php`
- Test: `tests/Unit/Services/Notification/NotificationDispatcherTest.php`

**Interfaces:**
- Produces: `Notification::class` fillable `recipient_user_id, event_key, event_type, title, body, read_at`; relations `recipient()`, `context()` (hasOne `NotificationContext`).
- Produces: `NotificationContext::class` fillable `notification_id, assessment_id, lesson_session_id, result_id, schedule_id`; `$timestamps = false`.
- Produces: `NotificationDispatcher::dispatch(\Illuminate\Support\Collection $recipients, string $eventKey, string $eventType, string $title, string $body, array $context): void` — `$context` must have exactly one non-null key among `assessment_id`/`lesson_session_id`/`result_id`/`schedule_id`.

- [ ] **Step 1: Write the migrations**

```php
// database/migrations/2026_10_17_000002_create_notifications_table.php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('recipient_user_id')->constrained('users')->cascadeOnDelete();
    $table->string('event_key');
    $table->string('event_type');
    $table->string('title');
    $table->text('body');
    $table->timestamp('created_at')->useCurrent();
    $table->timestamp('read_at')->nullable();
    $table->unique(['recipient_user_id', 'event_key']);
});
// down(): Schema::dropIfExists('notifications');
```

```php
// database/migrations/2026_10_17_000003_create_notification_contexts_table.php
Schema::create('notification_contexts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('notification_id')->constrained('notifications')->cascadeOnDelete();
    $table->foreignUuid('assessment_id')->nullable()->constrained('assessments')->cascadeOnDelete();
    $table->foreignUuid('lesson_session_id')->nullable()->constrained('lesson_sessions')->cascadeOnDelete();
    $table->uuid('result_id')->nullable();
    $table->foreignUuid('schedule_id')->nullable()->constrained('schedules')->cascadeOnDelete();
});
// down(): Schema::dropIfExists('notification_contexts');
```

`result_id` is a plain `uuid()` column (no FK constraint) because the `results` table does not exist yet (Module 4 unmigrated) — adding a `constrained()` here would fail `php artisan migrate` today. When Module 4's migrations land, a follow-up migration should add the FK constraint; this plan does not do that since it's out of this module's scope.

- [ ] **Step 2: Run migrations**

Run: `php artisan migrate`
Expected: 2 new migrations applied.

- [ ] **Step 3: Write the models**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['recipient_user_id', 'event_key', 'event_type', 'title', 'body', 'read_at'];

    protected $casts = ['created_at' => 'datetime', 'read_at' => 'datetime'];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function context()
    {
        return $this->hasOne(NotificationContext::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationContext extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['notification_id', 'assessment_id', 'lesson_session_id', 'result_id', 'schedule_id'];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
```

- [ ] **Step 4: Write factory**

```php
<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'recipient_user_id' => User::factory(),
            'event_key' => Str::uuid()->toString(),
            'event_type' => 'test_event',
            'title' => 'Test',
            'body' => 'Test body',
        ];
    }
}
```

- [ ] **Step 5: Write the failing test**

```php
<?php

namespace Tests\Unit\Services\Notification;

use App\Models\Assessment;
use App\Models\User;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_creates_one_notification_per_recipient_with_context(): void
    {
        $assessment = Assessment::factory()->create();
        $recipients = collect([User::factory()->create(), User::factory()->create()]);

        (new NotificationDispatcher())->dispatch(
            recipients: $recipients,
            eventKey: "assessment:{$assessment->id}:assigned",
            eventType: 'assessment_assigned',
            title: 'Bạn được giao bài mới',
            body: 'Kiểm tra lịch thi.',
            context: ['assessment_id' => $assessment->id],
        );

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('notification_contexts', 2);
        foreach ($recipients as $recipient) {
            $this->assertDatabaseHas('notifications', ['recipient_user_id' => $recipient->id, 'event_key' => "assessment:{$assessment->id}:assigned"]);
        }
    }

    public function test_dispatch_does_not_duplicate_for_the_same_event_key_and_recipient(): void
    {
        $recipient = User::factory()->create();

        $dispatcher = new NotificationDispatcher();
        $dispatcher->dispatch(collect([$recipient]), 'event:1', 'type', 'Title', 'Body', ['schedule_id' => null]);
        $dispatcher->dispatch(collect([$recipient]), 'event:1', 'type', 'Title (lại)', 'Body (lại)', ['schedule_id' => null]);

        $this->assertDatabaseCount('notifications', 1);
    }
}
```

Run: `php artisan test --filter=NotificationDispatcherTest`
Expected: FAIL (class not found).

- [ ] **Step 6: Implement `NotificationDispatcher`**

```php
<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\NotificationContext;
use Illuminate\Support\Collection;

class NotificationDispatcher
{
    public function dispatch(Collection $recipients, string $eventKey, string $eventType, string $title, string $body, array $context): void
    {
        foreach ($recipients as $recipient) {
            $notification = Notification::firstOrCreate(
                ['recipient_user_id' => $recipient->id, 'event_key' => $eventKey],
                ['event_type' => $eventType, 'title' => $title, 'body' => $body, 'created_at' => now()]
            );

            if ($notification->wasRecentlyCreated) {
                NotificationContext::create(['notification_id' => $notification->id] + $context);
            }
        }
    }
}
```

- [ ] **Step 7: Run tests**

Run: `php artisan test --filter=NotificationDispatcherTest`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_10_17_0000{02,03}_*.php app/Models/Notification.php app/Models/NotificationContext.php app/Services/Notification/NotificationDispatcher.php database/factories/NotificationFactory.php tests/Unit/Services/Notification/NotificationDispatcherTest.php
git commit -m "feat: add Notification/NotificationContext schema and dedupe-safe NotificationDispatcher"
```

---

## Task 4: Notification view endpoints

**Files:**
- Create: `app/Http/Controllers/Api/Notification/ListMyNotificationsController.php`
- Create: `app/Http/Controllers/Api/Notification/MarkNotificationReadController.php`
- Modify: `routes/api.php` — add `notification` to UUID loop
- Test: `tests/Feature/Notification/NotificationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_lists_only_their_own_notifications_and_can_mark_one_read(): void
    {
        $user = User::factory()->create();
        $mine = Notification::factory()->create(['recipient_user_id' => $user->id]);
        Notification::factory()->create(['recipient_user_id' => User::factory()->create()->id]);
        $this->actingAs($user);

        $list = $this->getJson('/api/v1/me/notifications');
        $list->assertOk();
        $list->assertJsonCount(1, 'data');

        $read = $this->patchJson("/api/v1/notifications/{$mine->id}/read");
        $read->assertOk();
        $this->assertNotNull($mine->refresh()->read_at);
    }

    public function test_cannot_mark_someone_elses_notification_as_read(): void
    {
        $user = User::factory()->create();
        $other = Notification::factory()->create(['recipient_user_id' => User::factory()->create()->id]);
        $this->actingAs($user);

        $this->patchJson("/api/v1/notifications/{$other->id}/read")->assertStatus(404);
    }
}
```

Run: `php artisan test --filter=NotificationTest`
Expected: FAIL (route not found).

- [ ] **Step 2: Add `notification` to UUID loop, register routes (no role restriction — any authenticated active user)**

```php
// routes/api.php, inside the top-level auth:sanctum+EnsureUserIsActive group, alongside /me/profile
Route::get('/me/notifications', \App\Http\Controllers\Api\Notification\ListMyNotificationsController::class);
Route::patch('/notifications/{notification}/read', \App\Http\Controllers\Api\Notification\MarkNotificationReadController::class);
```

- [ ] **Step 3: Write the controllers**

```php
<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ListMyNotificationsController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->user()->notifications()->getQuery() ?? \App\Models\Notification::where('recipient_user_id', $request->user()->id);

        if ($request->query('read') === 'true') {
            $query->whereNotNull('read_at');
        } elseif ($request->query('read') === 'false') {
            $query->whereNull('read_at');
        }

        return response()->json(['data' => $query->orderByDesc('created_at')->get(['id', 'event_type', 'title', 'body', 'created_at', 'read_at'])]);
    }
}
```

Add the matching relation to `app/Models/User.php` (so `$request->user()->notifications()` works cleanly instead of the awkward fallback above):

```php
// app/Models/User.php, alongside personProfile()/teacherProfile()
public function notifications()
{
    return $this->hasMany(\App\Models\Notification::class, 'recipient_user_id');
}
```

Then simplify the controller to:

```php
<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ListMyNotificationsController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = $request->user()->notifications();

        if ($request->query('read') === 'true') {
            $query->whereNotNull('read_at');
        } elseif ($request->query('read') === 'false') {
            $query->whereNull('read_at');
        }

        return response()->json(['data' => $query->orderByDesc('created_at')->get(['id', 'event_type', 'title', 'body', 'created_at', 'read_at'])]);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class MarkNotificationReadController extends Controller
{
    public function __invoke(Request $request, Notification $notification)
    {
        abort_unless($notification->recipient_user_id === $request->user()->id, 404);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['notification' => $notification]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=NotificationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Notification/ListMyNotificationsController.php app/Http/Controllers/Api/Notification/MarkNotificationReadController.php app/Models/User.php routes/api.php tests/Feature/Notification/NotificationTest.php
git commit -m "feat: add GET /me/notifications and PATCH /notifications/{notification}/read"
```

---

## Task 5: Attachment/AttachmentLink — migrations, models, config, upload/link/view/delete endpoints

**Files:**
- Create: `database/migrations/2026_10_17_000004_create_attachments_table.php`
- Create: `database/migrations/2026_10_17_000005_create_attachment_links_table.php`
- Create: `app/Models/Attachment.php`, `app/Models/AttachmentLink.php`
- Create: `config/attachments.php`
- Modify: `.env.example` — add `AWS_ENDPOINT=`, `AWS_URL=` (currently missing even though `config/filesystems.php` reads `env('AWS_ENDPOINT')`)
- Create: `app/Http/Controllers/Api/File/UploadAttachmentController.php`
- Create: `app/Http/Controllers/Api/File/LinkAttachmentController.php`
- Create: `app/Http/Controllers/Api/File/ViewAttachmentController.php`
- Create: `app/Http/Controllers/Api/File/DeleteAttachmentController.php`
- Modify: `routes/api.php` — add `attachment` to UUID loop
- Test: `tests/Feature/File/AttachmentTest.php`

**Interfaces:**
- Produces: `Attachment::class` fillable `uploaded_by_user_id, storage_key, file_name, media_type, size_bytes, checksum, status`; `$timestamps = false`, only `created_at`.
- Produces: `AttachmentLink::class` fillable `attachment_id, question_version_id, assessment_version_id, answer_id, feedback_id, visibility`; `$timestamps = false`, only `created_at`.

- [ ] **Step 1: Write the migrations**

```php
// database/migrations/2026_10_17_000004_create_attachments_table.php
Schema::create('attachments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('uploaded_by_user_id')->nullable()->constrained('users');
    $table->string('storage_key')->unique();
    $table->string('file_name');
    $table->string('media_type');
    $table->unsignedBigInteger('size_bytes');
    $table->string('checksum')->nullable();
    $table->enum('status', ['uploading', 'ready', 'failed', 'archived'])->default('ready');
    $table->timestamp('created_at')->useCurrent();
});
// down(): Schema::dropIfExists('attachments');
```

```php
// database/migrations/2026_10_17_000005_create_attachment_links_table.php
Schema::create('attachment_links', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('attachment_id')->constrained('attachments')->cascadeOnDelete();
    $table->foreignUuid('question_version_id')->nullable()->constrained('question_versions')->cascadeOnDelete();
    $table->foreignUuid('assessment_version_id')->nullable()->constrained('assessment_versions')->cascadeOnDelete();
    $table->uuid('answer_id')->nullable();
    $table->uuid('feedback_id')->nullable();
    $table->enum('visibility', ['private', 'learner', 'parent_if_published']);
    $table->timestamp('created_at')->useCurrent();
});
// down(): Schema::dropIfExists('attachment_links');
```

`answer_id`/`feedback_id` are plain `uuid()` columns (no FK) for the same reason as `NotificationContext.result_id` in Task 3 — the `answers`/`feedbacks` tables don't exist yet (Module 4 unmigrated). A follow-up migration adds the FK constraints once Module 4 ships; out of scope here.

- [ ] **Step 2: Run migrations**

Run: `php artisan migrate`
Expected: 2 new migrations applied.

- [ ] **Step 3: Write the models, config, and `.env.example` update**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['uploaded_by_user_id', 'storage_key', 'file_name', 'media_type', 'size_bytes', 'checksum', 'status'];

    protected $casts = ['created_at' => 'datetime'];

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function links()
    {
        return $this->hasMany(AttachmentLink::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttachmentLink extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['attachment_id', 'question_version_id', 'assessment_version_id', 'answer_id', 'feedback_id', 'visibility'];

    protected $casts = ['created_at' => 'datetime'];

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }
}
```

```php
<?php
// config/attachments.php

return [
    'max_size_kb' => env('ATTACHMENTS_MAX_SIZE_KB', 10240),
    'allowed_mime_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'application/zip',
    ],
];
```

Add to `.env.example` (after the existing `AWS_BUCKET=` line):
```
AWS_ENDPOINT=
AWS_URL=
```

- [ ] **Step 4: Write the failing test (upload + link + view + delete, happy path and the two Review Focus cases)**

```php
<?php

namespace Tests\Feature\File;

use App\Models\PersonProfile;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        Teacher::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $user->id])->id]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_teacher_uploads_links_and_views_a_question_attachment(): void
    {
        Storage::fake('s3');
        $teacher = $this->actingAsTeacher();
        $questionVersion = QuestionVersion::factory()->create(['created_by_user_id' => $teacher->id]);
        $file = UploadedFile::fake()->create('de-bai.pdf', 500, 'application/pdf');

        $upload = $this->postJson('/api/v1/attachments', ['file' => $file]);
        $upload->assertCreated();
        $attachmentId = $upload->json('attachment.id');

        $link = $this->postJson("/api/v1/attachments/{$attachmentId}/link", [
            'question_version_id' => $questionVersion->id,
            'visibility' => 'learner',
        ]);
        $link->assertCreated();

        $view = $this->getJson("/api/v1/attachments/{$attachmentId}");
        $view->assertOk();
        $this->assertNotNull($view->json('download_url'));
    }

    public function test_upload_rejects_a_file_over_the_configured_size_limit_without_touching_storage(): void
    {
        Storage::fake('s3');
        $this->actingAsTeacher();
        config(['attachments.max_size_kb' => 100]);
        $tooLarge = UploadedFile::fake()->create('huge.pdf', 200, 'application/pdf');

        $response = $this->postJson('/api/v1/attachments', ['file' => $tooLarge]);

        $response->assertStatus(422);
        Storage::disk('s3')->assertDirectoryEmpty('attachments');
    }

    public function test_cannot_delete_an_attachment_that_already_has_a_link(): void
    {
        Storage::fake('s3');
        $teacher = $this->actingAsTeacher();
        $questionVersion = QuestionVersion::factory()->create(['created_by_user_id' => $teacher->id]);
        $file = UploadedFile::fake()->create('de-bai.pdf', 100, 'application/pdf');
        $attachmentId = $this->postJson('/api/v1/attachments', ['file' => $file])->json('attachment.id');
        $this->postJson("/api/v1/attachments/{$attachmentId}/link", ['question_version_id' => $questionVersion->id, 'visibility' => 'learner']);

        $this->deleteJson("/api/v1/attachments/{$attachmentId}")->assertStatus(409);
    }
}
```

Run: `php artisan test --filter=AttachmentTest`
Expected: FAIL (routes not found).

- [ ] **Step 5: Add `attachment` to UUID loop and register routes**

```php
// routes/api.php, inside the top-level auth:sanctum+EnsureUserIsActive group
Route::post('/attachments', \App\Http\Controllers\Api\File\UploadAttachmentController::class);
Route::post('/attachments/{attachment}/link', \App\Http\Controllers\Api\File\LinkAttachmentController::class);
Route::get('/attachments/{attachment}', \App\Http\Controllers\Api\File\ViewAttachmentController::class);
Route::delete('/attachments/{attachment}', \App\Http\Controllers\Api\File\DeleteAttachmentController::class);
```

- [ ] **Step 6: Write `UploadAttachmentController`**

```php
<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadAttachmentController extends Controller
{
    public function __invoke(Request $request)
    {
        $maxSizeKb = config('attachments.max_size_kb');
        $allowedMimes = implode(',', array_map(
            fn ($mime) => str_contains($mime, '/') ? $mime : $mime,
            config('attachments.allowed_mime_types')
        ));

        $data = $request->validate([
            'file' => ['required', 'file', "max:{$maxSizeKb}", function ($attribute, $value, $fail) use ($allowedMimes) {
                if (! in_array($value->getMimeType(), config('attachments.allowed_mime_types'), true)) {
                    $fail('Định dạng file không được hỗ trợ.');
                }
            }],
        ]);

        $file = $data['file'];
        $storageKey = 'attachments/'.Str::uuid().'_'.$file->getClientOriginalName();
        $path = Storage::disk('s3')->put($storageKey, file_get_contents($file->getRealPath()));
        abort_if($path === false, 500, 'Không upload được file.');

        $attachment = Attachment::create([
            'uploaded_by_user_id' => $request->user()->id,
            'storage_key' => $storageKey,
            'file_name' => $file->getClientOriginalName(),
            'media_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'status' => 'ready',
        ]);

        return response()->json(['attachment' => $attachment], 201);
    }
}
```

- [ ] **Step 7: Write `LinkAttachmentController`**

```php
<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\AttachmentLink;
use App\Models\AssessmentVersion;
use App\Models\QuestionVersion;
use Illuminate\Http\Request;

class LinkAttachmentController extends Controller
{
    public function __invoke(Request $request, Attachment $attachment)
    {
        $data = $request->validate([
            'question_version_id' => ['nullable', 'uuid', 'exists:question_versions,id'],
            'assessment_version_id' => ['nullable', 'uuid', 'exists:assessment_versions,id'],
            'answer_id' => ['nullable', 'uuid'],
            'feedback_id' => ['nullable', 'uuid'],
            'visibility' => ['required', 'in:private,learner,parent_if_published'],
        ]);

        $targets = array_filter([
            $data['question_version_id'] ?? null,
            $data['assessment_version_id'] ?? null,
            $data['answer_id'] ?? null,
            $data['feedback_id'] ?? null,
        ]);
        abort_unless(count($targets) === 1, 422, 'Chọn đúng một target cho AttachmentLink.');

        $activeRole = $request->session()->get('active_role_code');

        if (! empty($data['question_version_id'])) {
            $qv = QuestionVersion::findOrFail($data['question_version_id']);
            abort_unless($activeRole === 'admin' || $qv->created_by_user_id === $request->user()->id, 403);
        } elseif (! empty($data['assessment_version_id'])) {
            $av = AssessmentVersion::findOrFail($data['assessment_version_id']);
            abort_unless($activeRole === 'admin' || $av->assessment->created_by_user_id === $request->user()->id, 403);
        }
        // answer_id/feedback_id scope checks are added once Module 4 ships those tables —
        // see "Deferred work" at the end of this plan.

        $link = AttachmentLink::create($data + ['attachment_id' => $attachment->id]);

        return response()->json(['attachment_link' => $link], 201);
    }
}
```

- [ ] **Step 8: Write `ViewAttachmentController` and `DeleteAttachmentController`**

```php
<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Models\AssessmentVersion;
use App\Models\Attachment;
use App\Models\QuestionVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ViewAttachmentController extends Controller
{
    public function __invoke(Request $request, Attachment $attachment)
    {
        $activeRole = $request->session()->get('active_role_code');
        $canView = $attachment->links->contains(function ($link) use ($request, $activeRole) {
            if ($link->question_version_id) {
                $qv = QuestionVersion::find($link->question_version_id);

                return $qv && ($activeRole === 'admin' || $activeRole === 'teacher');
            }
            if ($link->assessment_version_id) {
                $av = AssessmentVersion::find($link->assessment_version_id);

                return $av && ($activeRole === 'admin' || $av->assessment->created_by_user_id === $request->user()->id);
            }

            // answer_id/feedback_id checks land once Module 4 ships — see Deferred work.
            return false;
        });
        abort_unless($canView, 404);

        return response()->json([
            'attachment' => $attachment,
            'download_url' => Storage::disk('s3')->temporaryUrl($attachment->storage_key, now()->addMinutes(10)),
        ]);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\File;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeleteAttachmentController extends Controller
{
    public function __invoke(Request $request, Attachment $attachment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless($activeRole === 'admin' || $attachment->uploaded_by_user_id === $request->user()->id, 403);
        abort_if($attachment->links()->exists(), 409, 'File còn được tham chiếu, không xóa được.');

        Storage::disk('s3')->delete($attachment->storage_key);
        $attachment->delete();

        return response()->noContent();
    }
}
```

- [ ] **Step 9: Run tests**

Run: `php artisan test --filter=AttachmentTest`
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add database/migrations/2026_10_17_0000{04,05}_*.php app/Models/Attachment.php app/Models/AttachmentLink.php config/attachments.php .env.example app/Http/Controllers/Api/File/*.php routes/api.php tests/Feature/File/AttachmentTest.php
git commit -m "feat: add Attachment upload/link/view/delete API over S3-compatible storage"
```

---

## Task 6: Retrofit User Management controllers — AuditLog only

**Files:**
- Modify: `app/Http/Controllers/Api/UserManagement/UpdateUserStatusController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/AssignUserRoleController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/EndUserRoleController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/CreateParentStudentRelationshipController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/EndParentStudentRelationshipController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/AddClassMemberController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/EndClassMemberController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/UpdateClassMemberController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/AssignTeacherToClassController.php`
- Modify: `app/Http/Controllers/Api/UserManagement/EndTeacherClassAssignmentController.php`
- Test: `tests/Feature/Audit/UserManagementAuditTest.php`

**Interfaces:**
- Consumes: `AuditLogger::record()` (Task 1).

- [ ] **Step 1: Write the failing test — covers the `UpdateClassMemberController` debt specifically**

```php
<?php

namespace Tests\Feature\Audit;

use App\Models\ClassMember;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\PersonProfile;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAuditTest extends TestCase
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

    public function test_updating_a_class_member_retroactively_writes_the_audit_log_with_its_reason(): void
    {
        $admin = $this->actingAsAdmin();
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        $member = ClassMember::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDays(10)]);

        $response = $this->patchJson("/api/v1/classes/{$class->id}/members/{$student->id}", [
            'valid_from' => now()->subDays(5)->toDateString(),
            'reason' => 'sửa lại ngày vào lớp theo hồ sơ giấy',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'ClassMember',
            'entity_id' => $member->id,
            'action' => 'class_member.retroactive_update',
            'reason' => 'sửa lại ngày vào lớp theo hồ sơ giấy',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_updating_user_status_writes_an_audit_log(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create(['status' => 'active']);

        $this->patchJson("/api/v1/users/{$target->id}", ['status' => 'inactive'])->assertOk();

        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'User', 'entity_id' => $target->id, 'action' => 'user.status_update']);
    }
}
```

Run: `php artisan test --filter=UserManagementAuditTest`
Expected: FAIL (no audit logs written).

- [ ] **Step 2: Retrofit `UpdateClassMemberController` (the specific debt) and `UpdateUserStatusController`**

```php
// app/Http/Controllers/Api/UserManagement/UpdateClassMemberController.php
// Replace the comment-and-discard line:
//     // "reason" được bắt buộc theo BR-USER-017 nhưng chưa có bảng AuditLog để
//     // ghi lại (thuộc module Audit, nhóm sau) — xem Global Constraints của plan này.
//     $member->fill(array_intersect_key($data, array_flip(['valid_from', 'valid_to'])))->save();
// with:

$before = $member->only(['valid_from', 'valid_to']);
$member->fill(array_intersect_key($data, array_flip(['valid_from', 'valid_to'])))->save();

(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(),
    action: 'class_member.retroactive_update',
    entityType: 'ClassMember',
    entityId: $member->id,
    before: $before,
    after: $member->only(['valid_from', 'valid_to']),
    reason: $data['reason'],
    correlationKey: $class->id,
);
```

```php
// app/Http/Controllers/Api/UserManagement/UpdateUserStatusController.php
// Replace:
//     $user->update(['status' => $data['status']]);
// with:

$before = $user->only('status');
$user->update(['status' => $data['status']]);

(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(),
    action: 'user.status_update',
    entityType: 'User',
    entityId: $user->id,
    before: $before,
    after: $user->only('status'),
);
```

- [ ] **Step 3: Run tests, confirm both pass**

Run: `php artisan test --filter=UserManagementAuditTest`
Expected: PASS.

- [ ] **Step 4: Retrofit the remaining 8 controllers (same pattern, no `reason` required for any of them)**

```php
// AssignUserRoleController.php — after $userRole = UserRole::create([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'user_role.assign', entityType: 'UserRole', entityId: $userRole->id,
    after: ['user_id' => $user->id, 'role_code' => $data['role_code']],
);

// EndUserRoleController.php — after $userRole->update([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'user_role.end', entityType: 'UserRole', entityId: $userRole->id,
    after: ['valid_to' => $userRole->valid_to],
);

// CreateParentStudentRelationshipController.php — after $relationship = ParentStudentRelationship::create([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'parent_student_relationship.create', entityType: 'ParentStudentRelationship', entityId: $relationship->id,
    after: ['parent_id' => $relationship->parent_id, 'student_id' => $relationship->student_id],
);

// EndParentStudentRelationshipController.php — after $parentStudentRelationship->update([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'parent_student_relationship.end', entityType: 'ParentStudentRelationship', entityId: $parentStudentRelationship->id,
    after: ['status' => 'ended'], reason: $data['end_reason'],
);

// AddClassMemberController.php — after $member = ClassMember::create([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'class_member.add', entityType: 'ClassMember', entityId: $member->id,
    after: ['class_id' => $member->class_id, 'student_id' => $member->student_id], correlationKey: $class->id,
);

// EndClassMemberController.php — after $member->update([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'class_member.end', entityType: 'ClassMember', entityId: $member->id,
    after: ['status' => 'ended'], reason: $data['end_reason'], correlationKey: $class->id,
);

// AssignTeacherToClassController.php — after $assignment = TeacherClassAssignment::create([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'teacher_class_assignment.assign', entityType: 'TeacherClassAssignment', entityId: $assignment->id,
    after: ['class_id' => $assignment->class_id, 'teacher_id' => $assignment->teacher_id], correlationKey: $class->id,
);

// EndTeacherClassAssignmentController.php — after $assignment->update([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'teacher_class_assignment.end', entityType: 'TeacherClassAssignment', entityId: $assignment->id,
    after: ['status' => 'ended'], correlationKey: $class->id,
);
```

Add `use App\Services\Audit\AuditLogger;` to each file's imports (or keep the fully-qualified inline call shown above — either is fine, pick one style and apply it consistently across all 10 files in this task).

- [ ] **Step 5: Write one more assertion per remaining controller into the same test file, then run the full file**

Add assertions like the two existing tests for each of the 8 controllers above (`assertDatabaseHas('audit_logs', [...])` after each corresponding request), following the same `actingAsAdmin()` helper already in the test class.

Run: `php artisan test --filter=UserManagementAuditTest`
Expected: PASS (all assertions).

- [ ] **Step 6: Run the full Module 1 Feature test suite to confirm no regressions**

Run: `php artisan test --testsuite=Feature --filter=UserManagement`
Expected: PASS — the retrofit only adds a write after existing logic, never changes existing response shapes or status codes.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/UserManagement/*.php tests/Feature/Audit/UserManagementAuditTest.php
git commit -m "feat: retrofit AuditLog into 10 User Management controllers"
```

---

## Task 7: Retrofit Attendance/Lesson controllers — AuditLog + Notification

**Files:**
- Modify: `app/Http/Controllers/Api/Attendance/UpdateAttendanceRecordController.php`
- Modify: `app/Http/Controllers/Api/Attendance/ReviewAbsenceExplanationController.php`
- Modify: `app/Http/Controllers/Api/Attendance/CreateAbsenceExplanationController.php`
- Modify: `app/Http/Controllers/Api/Lesson/UpdateScheduleController.php`
- Modify: `app/Http/Controllers/Api/Lesson/CancelScheduleController.php`
- Modify: `app/Http/Controllers/Api/Lesson/UpdateLessonSessionController.php`
- Modify: `app/Http/Controllers/Api/Lesson/CancelLessonSessionController.php`
- Test: `tests/Feature/Audit/AttendanceLessonAuditTest.php`
- Test: `tests/Feature/Notification/AttendanceLessonNotificationTest.php`

**Interfaces:**
- Consumes: `AuditLogger::record()` (Task 1), `NotificationDispatcher::dispatch()` (Task 3).

- [ ] **Step 1: Write the failing Audit test**

```php
<?php

namespace Tests\Feature\Audit;

use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLessonAuditTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacherOf(SchoolClass $class): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $teacher = Teacher::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $user->id])->id]);
        TeacherClassAssignment::create(['class_id' => $class->id, 'teacher_id' => $teacher->id, 'valid_from' => now()->subDay(), 'status' => 'active']);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_updating_attendance_writes_audit_log_with_reason(): void
    {
        $class = SchoolClass::factory()->create();
        $this->actingAsTeacherOf($class);
        $session = LessonSession::factory()->create(['class_id' => $class->id]);
        $record = AttendanceRecord::factory()->create(['lesson_session_id' => $session->id, 'status' => 'present']);

        $this->patchJson("/api/v1/attendance-records/{$record->id}", ['status' => 'absent', 'reason' => 'phụ huynh xác nhận vắng sau'])->assertOk();

        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'AttendanceRecord', 'entity_id' => $record->id, 'action' => 'attendance_record.update', 'reason' => 'phụ huynh xác nhận vắng sau']);
    }

    public function test_updating_a_non_completed_lesson_session_writes_no_audit_log_since_reason_is_optional(): void
    {
        $class = SchoolClass::factory()->create();
        $this->actingAsTeacherOf($class);
        $session = LessonSession::factory()->create(['class_id' => $class->id, 'status' => 'scheduled']);

        $this->patchJson("/api/v1/lesson-sessions/{$session->id}", ['notes' => 'ghi chú nhanh'])->assertOk();

        $this->assertDatabaseMissing('audit_logs', ['entity_type' => 'LessonSession', 'entity_id' => $session->id]);
    }

    public function test_updating_a_completed_lesson_session_requires_reason_and_writes_audit_log(): void
    {
        $class = SchoolClass::factory()->create();
        $this->actingAsTeacherOf($class);
        $session = LessonSession::factory()->create(['class_id' => $class->id, 'status' => 'completed']);

        $this->patchJson("/api/v1/lesson-sessions/{$session->id}", ['notes' => 'sửa lại', 'reason' => 'ghi nhầm buổi trước'])->assertOk();

        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'LessonSession', 'entity_id' => $session->id, 'reason' => 'ghi nhầm buổi trước']);
    }
}
```

Run: `php artisan test --filter=AttendanceLessonAuditTest`
Expected: FAIL.

- [ ] **Step 2: Retrofit Audit for all 7 controllers**

```php
// UpdateAttendanceRecordController.php — after AttendanceHistory::create([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'attendance_record.update', entityType: 'AttendanceRecord', entityId: $attendanceRecord->id,
    before: $before, after: $attendanceRecord->only(['status', 'note']), reason: $data['reason'],
    correlationKey: $attendanceRecord->lessonSession->class_id,
);

// ReviewAbsenceExplanationController.php — after $absenceExplanation->update([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'absence_explanation.review', entityType: 'AbsenceExplanation', entityId: $absenceExplanation->id,
    after: ['status' => $absenceExplanation->status],
);

// UpdateScheduleController.php — after ScheduleHistory::create([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'schedule.update', entityType: 'Schedule', entityId: $schedule->id,
    before: $before, after: $schedule->only(['local_start_time', 'duration_minutes', 'weekdays', 'effective_until']),
    reason: $data['reason'], correlationKey: $schedule->class_id,
);

// CancelScheduleController.php — after $schedule->update([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'schedule.cancel', entityType: 'Schedule', entityId: $schedule->id,
    after: ['status' => 'cancelled'], reason: $data['cancel_reason'], correlationKey: $schedule->class_id,
);

// UpdateLessonSessionController.php — after the existing `if ($reason !== null) { LessonSessionHistory::create([...]); }` block, inside that same if:
if ($reason !== null) {
    LessonSessionHistory::create([...]); // existing code, unchanged

    (new \App\Services\Audit\AuditLogger())->record(
        actor: $request->user(), action: 'lesson_session.update', entityType: 'LessonSession', entityId: $lessonSession->id,
        before: $before, after: $lessonSession->only(['starts_at', 'ends_at', 'lesson_content', 'notes']),
        reason: $reason, correlationKey: $lessonSession->class_id,
    );
}

// CancelLessonSessionController.php — after $lessonSession->update([...]); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'lesson_session.cancel', entityType: 'LessonSession', entityId: $lessonSession->id,
    after: ['status' => 'cancelled'], reason: $data['cancellation_reason'], correlationKey: $lessonSession->class_id,
);
```

(`CreateAbsenceExplanationController` has no row in the spec's Audit table — it only needs Notification, added in Step 4 below.)

- [ ] **Step 3: Run Audit tests**

Run: `php artisan test --filter=AttendanceLessonAuditTest`
Expected: PASS.

- [ ] **Step 4: Write the failing Notification test**

```php
<?php

namespace Tests\Feature\Notification;

use App\Models\AbsenceExplanation;
use App\Models\AttendanceRecord;
use App\Models\ClassMember;
use App\Models\LessonSession;
use App\Models\Notification;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLessonNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_a_schedule_notifies_the_classs_active_students_and_teacher(): void
    {
        $teacherUser = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $teacher = Teacher::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $teacherUser->id])->id]);
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::create(['class_id' => $class->id, 'teacher_id' => $teacher->id, 'valid_from' => now()->subDay(), 'status' => 'active']);

        $studentUser = User::factory()->create();
        $studentRole = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $studentUser->id, 'role_id' => $studentRole->id, 'valid_from' => now()->subDay()]);
        $student = Student::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $studentUser->id])->id]);
        ClassMember::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay()]);

        $schedule = Schedule::factory()->create(['class_id' => $class->id]);
        $this->actingAs($teacherUser)->withSession(['active_role_code' => 'teacher']);

        $this->postJson("/api/v1/schedules/{$schedule->id}/cancel", ['cancel_reason' => 'nghỉ lễ'])->assertOk();

        $this->assertDatabaseHas('notifications', ['recipient_user_id' => $studentUser->id, 'event_type' => 'schedule_cancelled']);
        $this->assertDatabaseHas('notifications', ['recipient_user_id' => $teacherUser->id, 'event_type' => 'schedule_cancelled']);
    }

    public function test_reviewing_an_absence_explanation_notifies_only_the_submitting_parent_with_an_active_link(): void
    {
        $parentUser = User::factory()->create();
        $parentRole = Role::factory()->create(['code' => 'parent']);
        UserRole::create(['user_id' => $parentUser->id, 'role_id' => $parentRole->id, 'valid_from' => now()->subDay()]);
        $parent = ParentProfile::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $parentUser->id])->id]);
        $student = Student::factory()->create();
        ParentStudentRelationship::create(['parent_id' => $parent->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay()]);
        $session = LessonSession::factory()->create();
        $explanation = AbsenceExplanation::factory()->create(['parent_id' => $parent->id, 'student_id' => $student->id, 'lesson_session_id' => $session->id, 'status' => 'submitted']);

        $teacherUser = User::factory()->create();
        $teacherRole = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $teacherRole->id, 'valid_from' => now()->subDay()]);
        $teacher = Teacher::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $teacherUser->id])->id]);
        TeacherClassAssignment::create(['class_id' => $session->class_id, 'teacher_id' => $teacher->id, 'valid_from' => now()->subDay(), 'status' => 'active']);
        $this->actingAs($teacherUser)->withSession(['active_role_code' => 'teacher']);

        $this->patchJson("/api/v1/absence-explanations/{$explanation->id}", ['status' => 'accepted'])->assertOk();

        $this->assertDatabaseHas('notifications', ['recipient_user_id' => $parentUser->id, 'event_type' => 'absence_explanation_reviewed']);
    }

    public function test_an_unlinked_parent_never_receives_the_notification(): void
    {
        $parentUser = User::factory()->create();
        $parent = ParentProfile::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $parentUser->id])->id]);
        $student = Student::factory()->create();
        ParentStudentRelationship::create(['parent_id' => $parent->id, 'student_id' => $student->id, 'status' => 'ended', 'valid_from' => now()->subMonth(), 'valid_to' => now()->subDay()]);
        $session = LessonSession::factory()->create();
        $explanation = AbsenceExplanation::factory()->create(['parent_id' => $parent->id, 'student_id' => $student->id, 'lesson_session_id' => $session->id, 'status' => 'submitted']);

        $teacherUser = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $teacher = Teacher::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $teacherUser->id])->id]);
        TeacherClassAssignment::create(['class_id' => $session->class_id, 'teacher_id' => $teacher->id, 'valid_from' => now()->subDay(), 'status' => 'active']);
        $this->actingAs($teacherUser)->withSession(['active_role_code' => 'teacher']);

        $this->patchJson("/api/v1/absence-explanations/{$explanation->id}", ['status' => 'accepted'])->assertOk();

        $this->assertDatabaseMissing('notifications', ['recipient_user_id' => $parentUser->id]);
    }
}
```

Run: `php artisan test --filter=AttendanceLessonNotificationTest`
Expected: FAIL.

- [ ] **Step 5: Retrofit Notification for schedule/lesson-session/absence-explanation controllers**

```php
// UpdateScheduleController.php and CancelScheduleController.php — after the Audit call added in Step 2, add:
$recipients = \App\Models\User::whereIn('person_profile_id', function ($q) use ($schedule) {
        $q->select('person_profile_id')->from('students')
            ->whereIn('id', \App\Models\ClassMember::where('class_id', $schedule->class_id)->where('status', 'active')->pluck('student_id'));
    })
    ->orWhereIn('person_profile_id', function ($q) use ($schedule) {
        $q->select('person_profile_id')->from('teachers')
            ->whereIn('id', \App\Models\TeacherClassAssignment::where('class_id', $schedule->class_id)->where('status', 'active')->pluck('teacher_id'));
    })
    ->get();

(new \App\Services\Notification\NotificationDispatcher())->dispatch(
    recipients: $recipients,
    eventKey: "schedule:{$schedule->id}:".(/* 'updated' in UpdateScheduleController, 'cancelled' in CancelScheduleController */ 'cancelled').":".now()->timestamp,
    eventType: 'schedule_cancelled', // 'schedule_updated' in UpdateScheduleController
    title: 'Lịch học đã thay đổi',
    body: 'Lịch học của lớp bạn tham gia vừa được cập nhật.',
    context: ['schedule_id' => $schedule->id],
);

// UpdateLessonSessionController.php and CancelLessonSessionController.php — same recipient-building expression,
// swapping $schedule->class_id for $lessonSession->class_id and context key to ['lesson_session_id' => $lessonSession->id].

// CreateAbsenceExplanationController.php — after $explanation = AbsenceExplanation::create([...]); add:
$teacherRecipients = \App\Models\User::whereIn('person_profile_id', function ($q) use ($session) {
    $q->select('person_profile_id')->from('teachers')
        ->whereIn('id', \App\Models\TeacherClassAssignment::where('class_id', $session->class_id)->where('status', 'active')->pluck('teacher_id'));
})->get();

(new \App\Services\Notification\NotificationDispatcher())->dispatch(
    recipients: $teacherRecipients,
    eventKey: "absence_explanation:{$explanation->id}:submitted",
    eventType: 'absence_explanation_submitted',
    title: 'Có đơn xin phép mới cần duyệt',
    body: 'Phụ huynh vừa nộp đơn xin phép vắng cho một học sinh trong lớp bạn.',
    context: ['lesson_session_id' => $session->id],
);

// ReviewAbsenceExplanationController.php — after the Audit call added in Step 2, add:
$parentActive = \App\Models\ParentStudentRelationship::where('parent_id', $absenceExplanation->parent_id)
    ->where('student_id', $absenceExplanation->student_id)->where('status', 'active')->exists();

if ($parentActive) {
    $parentUser = \App\Models\User::whereIn('person_profile_id', function ($q) use ($absenceExplanation) {
        $q->select('person_profile_id')->from('parents')->where('id', $absenceExplanation->parent_id);
    })->get();

    (new \App\Services\Notification\NotificationDispatcher())->dispatch(
        recipients: $parentUser,
        eventKey: "absence_explanation:{$absenceExplanation->id}:reviewed:{$absenceExplanation->status}",
        eventType: 'absence_explanation_reviewed',
        title: 'Đơn xin phép của bạn đã được xử lý',
        body: "Trạng thái: {$absenceExplanation->status}.",
        context: ['lesson_session_id' => $absenceExplanation->lesson_session_id],
    );
}
```

Note the `->status === 'active'` gate (not `valid_to`) on `ParentStudentRelationship` — this is the Review Focus case: the column this codebase actually checks is `status`, confirmed in `ParentStudentRelationship.php`.

- [ ] **Step 6: Run Notification tests**

Run: `php artisan test --filter=AttendanceLessonNotificationTest`
Expected: PASS.

- [ ] **Step 7: Run the full Attendance/Lesson Feature suites to confirm no regressions**

Run: `php artisan test --testsuite=Feature --filter=Attendance` and `php artisan test --testsuite=Feature --filter=Lesson`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/Attendance/*.php app/Http/Controllers/Api/Lesson/*.php tests/Feature/Audit/AttendanceLessonAuditTest.php tests/Feature/Notification/AttendanceLessonNotificationTest.php
git commit -m "feat: retrofit AuditLog + Notification into Attendance/Lesson controllers"
```

---

## Task 8: Retrofit Assessment controllers — AuditLog + Notification

**Files:**
- Modify: `app/Http/Controllers/Api/Assessment/PublishAssessmentController.php`
- Modify: `app/Http/Controllers/Api/Assessment/CloseAssessmentController.php`
- Modify: `app/Http/Controllers/Api/Assessment/ReopenAssessmentController.php`
- Modify: `app/Http/Controllers/Api/Assessment/CancelAssessmentController.php`
- Modify: `app/Http/Controllers/Api/Assessment/UpdateAssessmentController.php`
- Modify: `app/Http/Controllers/Api/Assessment/AddAssessmentAudienceController.php`
- Test: `tests/Feature/Audit/AssessmentAuditTest.php`
- Test: `tests/Feature/Notification/AssessmentNotificationTest.php`

**Interfaces:**
- Consumes: `AuditLogger::record()` (Task 1), `NotificationDispatcher::dispatch()` (Task 3).

- [ ] **Step 1: Write the failing Audit test**

```php
<?php

namespace Tests\Feature\Audit;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_close_reopen_and_cancel_each_write_an_audit_log(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'admin']);

        $assessment = Assessment::factory()->create(['created_by_user_id' => $user->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'draft']);
        $assessment->update(['current_version_id' => $version->id]);
        \App\Models\AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id]);

        $this->postJson("/api/v1/assessments/{$assessment->id}/publish")->assertOk();
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'Assessment', 'entity_id' => $assessment->id, 'action' => 'assessment.publish']);

        $this->postJson("/api/v1/assessments/{$assessment->id}/close")->assertOk();
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'Assessment', 'entity_id' => $assessment->id, 'action' => 'assessment.close']);

        $this->postJson("/api/v1/assessments/{$assessment->id}/reopen", ['student_ids' => []])->assertStatus(422);

        $cancel = $this->postJson("/api/v1/assessments/{$assessment->id}/cancel", ['reason' => 'sai đề']);
        $cancel->assertOk();
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'Assessment', 'entity_id' => $assessment->id, 'action' => 'assessment.cancel', 'reason' => 'sai đề']);
    }
}
```

Run: `php artisan test --filter=AssessmentAuditTest`
Expected: FAIL.

- [ ] **Step 2: Retrofit Audit for the 4 publish/close/reopen/cancel controllers**

```php
// PublishAssessmentController.php — after the existing $version->update([...]) and $assessment->update([...]) calls, before the final return:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'assessment.publish', entityType: 'Assessment', entityId: $assessment->id,
    after: ['version_id' => $version->id, 'status' => $assessment->status],
);

// CloseAssessmentController.php — after $assessment->update(['status' => 'closed']); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'assessment.close', entityType: 'Assessment', entityId: $assessment->id,
    after: ['status' => 'closed'],
);

// ReopenAssessmentController.php — after $assessment->update(['status' => 'published']); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'assessment.reopen', entityType: 'Assessment', entityId: $assessment->id,
    after: ['status' => 'published', 'student_ids' => $studentIds],
);

// CancelAssessmentController.php — after $assessment->update(['status' => 'cancelled']); add:
(new \App\Services\Audit\AuditLogger())->record(
    actor: $request->user(), action: 'assessment.cancel', entityType: 'Assessment', entityId: $assessment->id,
    after: ['status' => 'cancelled'], reason: $data['reason'],
);
```

- [ ] **Step 3: Run Audit tests**

Run: `php artisan test --filter=AssessmentAuditTest`
Expected: PASS.

- [ ] **Step 4: Write the failing Notification test**

```php
<?php

namespace Tests\Feature\Notification;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\ClassMember;
use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_a_class_audience_notifies_every_newly_assigned_student(): void
    {
        $adminUser = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $adminUser->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($adminUser)->withSession(['active_role_code' => 'admin']);

        $studentUser = User::factory()->create();
        $studentRole = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $studentUser->id, 'role_id' => $studentRole->id, 'valid_from' => now()->subDay()]);
        $student = Student::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $studentUser->id])->id]);
        $class = SchoolClass::factory()->create();
        ClassMember::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay()]);

        $assessment = Assessment::factory()->create(['created_by_user_id' => $adminUser->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $assessment->update(['current_version_id' => $version->id]);

        $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['class_id' => $class->id])->assertCreated();

        $this->assertDatabaseHas('notifications', ['recipient_user_id' => $studentUser->id, 'event_type' => 'assessment_assigned']);
    }
}
```

Run: `php artisan test --filter=AssessmentNotificationTest`
Expected: FAIL.

- [ ] **Step 5: Retrofit Notification for `AddAssessmentAudienceController` and `UpdateAssessmentController`**

```php
// AddAssessmentAudienceController.php — collect every student_id actually passed to
// StudentAssessmentSync::ensureForStudent() (per the Review Focus note: use the list the
// loop already iterated, not a fresh re-query) and notify them after the loop:

$assignedStudentIds = [];
if (! empty($data['student_id'])) {
    StudentAssessmentSync::ensureForStudent($assessment, $data['student_id'], $audience);
    $assignedStudentIds = [$data['student_id']];
} elseif (! empty($data['class_id'])) {
    $studentIds = ClassMember::where('class_id', $data['class_id'])->where('status', 'active')->pluck('student_id');
    foreach ($studentIds as $studentId) {
        StudentAssessmentSync::ensureForStudent($assessment, $studentId, $audience);
    }
    $assignedStudentIds = $studentIds->all();
} else {
    $studentIds = CourseEnrollment::where('course_id', $data['course_id'])->where('status', 'active')->pluck('student_id');
    foreach ($studentIds as $studentId) {
        StudentAssessmentSync::ensureForStudent($assessment, $studentId, $audience);
    }
    $assignedStudentIds = $studentIds->all();
}

$recipients = \App\Models\User::whereIn('person_profile_id', function ($q) use ($assignedStudentIds) {
    $q->select('person_profile_id')->from('students')->whereIn('id', $assignedStudentIds);
})->get();

(new \App\Services\Notification\NotificationDispatcher())->dispatch(
    recipients: $recipients,
    eventKey: "assessment:{$assessment->id}:assigned",
    eventType: 'assessment_assigned',
    title: 'Bạn được giao bài mới',
    body: 'Có một bài kiểm tra/bài tập mới được giao cho bạn.',
    context: ['assessment_id' => $assessment->id],
);

// UpdateAssessmentController.php — after $version->save(); add:
$assignedStudentIds = \App\Models\StudentAssessment::where('assessment_id', $assessment->id)->where('status', 'assigned')->pluck('student_id');
$recipients = \App\Models\User::whereIn('person_profile_id', function ($q) use ($assignedStudentIds) {
    $q->select('person_profile_id')->from('students')->whereIn('id', $assignedStudentIds);
})->get();

(new \App\Services\Notification\NotificationDispatcher())->dispatch(
    recipients: $recipients,
    eventKey: "assessment:{$assessment->id}:updated:".now()->timestamp,
    eventType: 'assessment_updated',
    title: 'Bài kiểm tra/bài tập đã được cập nhật',
    body: 'Thông tin của một bài đã giao cho bạn vừa thay đổi.',
    context: ['assessment_id' => $assessment->id],
);
```

Student/Parent recipient lookups in this task reuse the exact `User::whereIn('person_profile_id', ...)` pattern introduced in Task 7 — do not invent a second way to resolve a Student to its `User`.

- [ ] **Step 6: Run Notification tests**

Run: `php artisan test --filter=AssessmentNotificationTest`
Expected: PASS.

- [ ] **Step 7: Run the full Assessment Feature suite to confirm no regressions**

Run: `php artisan test --testsuite=Feature --filter=Assessment`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/*.php tests/Feature/Audit/AssessmentAuditTest.php tests/Feature/Notification/AssessmentNotificationTest.php
git commit -m "feat: retrofit AuditLog + Notification into Assessment controllers"
```

---

## Task 9: Full test suite run + final review

**Files:** none (verification task).

- [ ] **Step 1: Run the entire suite**

Run: `php artisan test`
Expected: all tests pass — Modules 1-3's original behavior unchanged, all new Module 5 tests green.

- [ ] **Step 2: Request a fresh-context review**

Use `superpowers:requesting-code-review` (fresh reviewer, strongest available model) against the full diff of this feature branch — same process used for Plans 1-4. Fix any Critical/Important findings with TDD (failing test first), then re-run the full suite.

- [ ] **Step 3: Commit any review fixes, then update `docs/progress.md`**

Mark Module 5 BE as done in the Step 9 checklist, noting final test count and any deferred Minor findings.

---

## Deferred work (not part of this plan)

Module 4 (Submission/Grading + Result/Progress) has a written plan but **no code yet** — when it is implemented, its own task list (or a short follow-up plan) must add:
- `AuditLogger::record()` calls to `SubmitAttemptController` (n/a — submit itself isn't audited per spec §4), `GradeQuestionController`/`GradeSubmissionController` (`grade.create`/`.update`, `reason` required only when `Result.publication_status === 'published'`), `PublishResultController`/`WithdrawResultController`/`BulkPublishResultsController` (`result.publish`/`.withdraw`), `ReissueAttemptController` (`attempt.reissue`, reason required).
- `NotificationDispatcher::dispatch()` calls to `PublishResultController`/`BulkPublishResultsController` (event `result_published`, context `result_id`, recipients: the Student + any active-link Parent).
- FK constraints on `notification_contexts.result_id` and `attachment_links.answer_id`/`feedback_id` once `results`/`answers`/`feedbacks` tables exist (currently plain `uuid()` columns — see Tasks 3 and 5).
- Scope checks for `answer_id`/`feedback_id` in `LinkAttachmentController`/`ViewAttachmentController` (currently `false`/not handled — see the "Deferred work" comments left in those controllers in Task 5).
