# Question Bank + Assessment Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the Laravel API for Module 3 (Question Bank + Assessment) in `piclass-api` — Question/QuestionVersion/QuestionOption/Topic/Tag CRUD, and the Assessment draft→publish→version lifecycle with Class/Student/Course audience management, StudentAssessment dedupe, and close/reopen/cancel.

**Architecture:** Follows the exact conventions already in `piclass-api` (verified against Academic/Lesson/Attendance modules): one-action-per-class invokable controllers under `app/Http/Controllers/Api/<SubModule>/`, flat `app/Models/`, inline `$request->validate()` (no FormRequest), no Policy/Gate layer (role via `role:` middleware + inline ownership checks), UUID PK via `HasUuids`, status columns as native Postgres enums via `$table->enum(...)`, "Version" rows as the audit trail (no separate History table for Question/Assessment).

**Tech Stack:** Laravel 11, PostgreSQL, Sanctum (session-based `active_role_code`), PHPUnit feature tests, run via `piclass-infra` Docker (`make test-api` from `piclass-infra/local`).

**Spec:** `docs/archive/superpowers/specs/2026-10-15-question-bank-assessment-api-design.md`

**Note on this revision (2026-10-15):** this plan was rewritten after `DEC-LRN-010` (Course/CourseEnrollment) landed in Module 2 and the first draft of this plan (written before that decision existed) was discarded. Only Tasks 8, 10, 15, 16, and 18 differ from that discarded draft — they add the `Course` audience path. Every other task is unchanged.

## Global Constraints

- Base path `/api/v1/...`, fields `snake_case`, pagination `?page=&per_page=` → `{ "data": [...], "meta": {...} }`.
- Error envelope `{ "error": { "code", "message" } }` (already wired globally in `bootstrap/app.php` — new code does not hand-roll error JSON, just `abort_unless`/`ValidationException`).
- No FormRequest classes, no `app/Policies` — match existing inline style exactly.
- UUID route params must be registered in the `Route::pattern()` loop in `routes/api.php` (`question`, `assessment`, `topic`, `tag`, `assessmentQuestion`, `audience`).
- `AssessmentVersion`/`QuestionVersion` content is immutable once `status=published` — enforced in controllers via `abort_unless`, never via DB trigger.
- Teacher ownership for Question = `created_by_user_id === $request->user()->id`. Teacher ownership/scope for Assessment = `created_by_user_id === $request->user()->id`, **or** audience Class in `Teacher::activeClassIds()`, **or** audience Course with `Course.owner_teacher_id === $request->user()->teacherProfile->id` — no new `app/Support` scope class beyond `StudentAssessmentSync` (Task 15).
- Topic/Tag: upsert-by-name, no separate create endpoint required before use.
- Close/Reopen/Cancel use `Assessment.status` directly (`draft/published/closed/cancelled/archived`) — no extra column.
- `AssessmentAudience` has exactly one of `class_id`/`student_id`/`course_id` set (`DEC-LRN-010` extended the original Class/Student pair); `StudentAssessment` records which one via `source_class_id`/`source_course_id`/direct.

## Review Focus

- Publishing an `AssessmentVersion` with zero `AssessmentQuestion` rows must be rejected (`422`) — an empty bài kiểm tra silently publishing would ship a broken exam to students.
- Editing a `Question` that is already snapshotted into a published `AssessmentVersion` must leave that snapshot untouched — only `current_version_id` moves forward; the old `QuestionVersion` row and the `AssessmentQuestion.snapshot` JSON must not change.
- A Student assigned the same Assessment through two different audience paths (e.g. a Class audience and a direct Student audience, or a Course audience and a Class audience) must end up with exactly one `StudentAssessment` row, not two (`DEC-AS-002`).
- A Teacher must not be able to publish, edit, or close an Assessment they didn't create, whose Class isn't one of their assigned classes, and whose Course isn't one they own — missing this check lets any Teacher tamper with another Teacher's exam.
- Ending an `AssessmentAudience` for a Student who still has another live audience path (e.g. still enrolled in the Course, or still a member of the Class, that grants it) must NOT withdraw their `StudentAssessment` — only when *no* audience path (Class, Student, or Course) remains live.

---

## Setup: branch

Before Task 1, create the feature branch from a freshly pulled `main` (standing workflow rule for this project — always branch from latest `main`, never from a stale local state):

```bash
cd piclass-api
git checkout main
git pull
git checkout -b feature/question-bank-assessment-backend
```

**Caveat for the implementer:** this plan assumes `User` exposes `studentProfile`/`parentProfile`/`teacherProfile` accessors (the explore pass that informed this plan directly confirmed `teacherProfile` and `Teacher::activeClassIds()`; `studentProfile`/`parentProfile` are assumed by naming symmetry, not directly observed), and that the Academic module's `Course`/`CourseEnrollment` models/tables (`courses`, `course_enrollments`, `owner_teacher_id`) already exist on `main` exactly as named in `docs/database/schema.md` (they were merged in Plan 3, PR #3). Before Task 8 (first task touching `courses`), run `php artisan tinker` or grep `app/Models/Course.php` to confirm table/column names match; adjust this plan's migrations if they differ.

---

## Task 1: Question Bank schema + models

**Files:**
- Create: `database/migrations/2026_10_15_000001_create_question_banks_table.php`
- Create: `database/migrations/2026_10_15_000002_create_questions_table.php`
- Create: `database/migrations/2026_10_15_000003_create_question_versions_table.php`
- Create: `database/migrations/2026_10_15_000004_add_current_version_fk_to_questions_table.php`
- Create: `database/migrations/2026_10_15_000005_create_question_options_table.php`
- Create: `database/migrations/2026_10_15_000006_create_topics_table.php`
- Create: `database/migrations/2026_10_15_000007_create_tags_table.php`
- Create: `database/migrations/2026_10_15_000008_create_question_topic_table.php`
- Create: `database/migrations/2026_10_15_000009_create_question_tag_table.php`
- Create: `app/Models/QuestionBank.php`, `app/Models/Question.php`, `app/Models/QuestionVersion.php`, `app/Models/QuestionOption.php`, `app/Models/Topic.php`, `app/Models/Tag.php`
- Create: `database/factories/QuestionBankFactory.php`, `QuestionFactory.php`, `QuestionVersionFactory.php`, `QuestionOptionFactory.php`, `TopicFactory.php`, `TagFactory.php`
- Test: `tests/Feature/QuestionBank/QuestionBankModelsTest.php`

**Interfaces:**
- Produces: `Question::currentVersion()` (belongsTo `QuestionVersion`), `Question::versions()` (hasMany), `Question::topics()`/`tags()` (belongsToMany via pivot, keyed off `current_version_id` — see note below), `QuestionVersion::options()` (hasMany), `QuestionVersion::question()` (belongsTo).

- [ ] **Step 1: Write the migrations**

```php
// database/migrations/2026_10_15_000001_create_question_banks_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('scope')->default('system');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
```

```php
// database/migrations/2026_10_15_000002_create_questions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignUuid('created_by_user_id')->constrained('users');
            $table->uuid('current_version_id')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
```

```php
// database/migrations/2026_10_15_000003_create_question_versions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('questions')->cascadeOnDelete();
            $table->integer('version_number');
            $table->enum('question_type', [
                'single_choice', 'multiple_choice', 'true_false', 'fill_in_the_blank',
                'short_answer', 'essay', 'matching', 'ordering', 'file_upload',
            ]);
            $table->text('prompt');
            $table->text('instructions')->nullable();
            $table->decimal('suggested_points', 8, 2)->nullable();
            $table->string('difficulty')->nullable();
            $table->enum('grading_mode', ['automatic', 'manual', 'mixed']);
            $table->json('type_payload')->nullable();
            $table->json('grading_config')->nullable();
            $table->foreignUuid('created_by_user_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_versions');
    }
};
```

```php
// database/migrations/2026_10_15_000004_add_current_version_fk_to_questions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('question_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
    }
};
```

```php
// database/migrations/2026_10_15_000005_create_question_options_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_version_id')->constrained('question_versions')->cascadeOnDelete();
            $table->integer('position');
            $table->json('content');
            $table->boolean('is_correct')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
```

```php
// database/migrations/2026_10_15_000006_create_topics_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['subject_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
```

```php
// database/migrations/2026_10_15_000007_create_tags_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
```

```php
// database/migrations/2026_10_15_000008_create_question_topic_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_topic', function (Blueprint $table) {
            $table->foreignUuid('question_version_id')->constrained('question_versions')->cascadeOnDelete();
            $table->foreignUuid('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->primary(['question_version_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_topic');
    }
};
```

```php
// database/migrations/2026_10_15_000009_create_question_tag_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_tag', function (Blueprint $table) {
            $table->foreignUuid('question_version_id')->constrained('question_versions')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['question_version_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_tag');
    }
};
```

- [ ] **Step 2: Run migrations**

Run (from `piclass-infra/local`): `docker compose exec -u www-data api php artisan migrate`
Expected: all 9 migrations run without error.

- [ ] **Step 3: Write the models**

```php
// app/Models/QuestionBank.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'scope', 'status'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
```

```php
// app/Models/Question.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'question_bank_id', 'subject_id', 'created_by_user_id',
        'current_version_id', 'status', 'archived_at',
    ];

    protected $casts = ['archived_at' => 'datetime'];

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function versions()
    {
        return $this->hasMany(QuestionVersion::class);
    }

    public function currentVersion()
    {
        return $this->belongsTo(QuestionVersion::class, 'current_version_id');
    }
}
```

```php
// app/Models/QuestionVersion.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionVersion extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'question_id', 'version_number', 'question_type', 'prompt', 'instructions',
        'suggested_points', 'difficulty', 'grading_mode', 'type_payload',
        'grading_config', 'created_by_user_id', 'created_at',
    ];

    protected $casts = [
        'type_payload' => 'array',
        'grading_config' => 'array',
        'created_at' => 'datetime',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'question_topic');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'question_tag');
    }
}
```

```php
// app/Models/QuestionOption.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['question_version_id', 'position', 'content', 'is_correct', 'created_at'];

    protected $casts = ['content' => 'array', 'is_correct' => 'boolean', 'created_at' => 'datetime'];

    public function questionVersion()
    {
        return $this->belongsTo(QuestionVersion::class);
    }
}
```

```php
// app/Models/Topic.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['subject_id', 'name'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
```

```php
// app/Models/Tag.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name'];
}
```

- [ ] **Step 4: Write the factories**

```php
// database/factories/QuestionBankFactory.php
<?php

namespace Database\Factories;

use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionBankFactory extends Factory
{
    protected $model = QuestionBank::class;

    public function definition(): array
    {
        return ['name' => 'System Bank', 'scope' => 'system', 'status' => 'active'];
    }
}
```

```php
// database/factories/QuestionFactory.php
<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'question_bank_id' => QuestionBank::factory(),
            'created_by_user_id' => User::factory(),
            'status' => 'active',
        ];
    }
}
```

```php
// database/factories/QuestionVersionFactory.php
<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionVersionFactory extends Factory
{
    protected $model = \App\Models\QuestionVersion::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'version_number' => 1,
            'question_type' => 'single_choice',
            'prompt' => fake()->sentence(),
            'grading_mode' => 'automatic',
            'created_by_user_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
```

```php
// database/factories/QuestionOptionFactory.php
<?php

namespace Database\Factories;

use App\Models\QuestionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionOptionFactory extends Factory
{
    protected $model = \App\Models\QuestionOption::class;

    public function definition(): array
    {
        return [
            'question_version_id' => QuestionVersion::factory(),
            'position' => 1,
            'content' => ['text' => fake()->word()],
            'is_correct' => false,
            'created_at' => now(),
        ];
    }
}
```

```php
// database/factories/TopicFactory.php
<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
    protected $model = \App\Models\Topic::class;

    public function definition(): array
    {
        return ['subject_id' => Subject::factory(), 'name' => fake()->unique()->word()];
    }
}
```

```php
// database/factories/TagFactory.php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = \App\Models\Tag::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->word()];
    }
}
```

- [ ] **Step 5: Write the failing test**

```php
// tests/Feature/QuestionBank/QuestionBankModelsTest.php
<?php

namespace Tests\Feature\QuestionBank;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_version_carries_options_topics_and_tags(): void
    {
        $question = Question::factory()->create();
        $version = QuestionVersion::factory()->create(['question_id' => $question->id, 'question_type' => 'single_choice']);
        $question->update(['current_version_id' => $version->id]);

        QuestionOption::factory()->create(['question_version_id' => $version->id, 'position' => 1, 'is_correct' => true]);
        QuestionOption::factory()->create(['question_version_id' => $version->id, 'position' => 2, 'is_correct' => false]);

        $topic = Topic::factory()->create();
        $tag = Tag::factory()->create();
        $version->topics()->attach($topic->id);
        $version->tags()->attach($tag->id);

        $question->refresh();

        $this->assertTrue($question->currentVersion->is($version));
        $this->assertCount(2, $version->options);
        $this->assertTrue($version->topics->contains($topic));
        $this->assertTrue($version->tags->contains($tag));
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=QuestionBankModelsTest`
Expected: FAIL (classes/tables not yet migrated in test DB, or migration missing before models existed).

- [ ] **Step 7: Run migrations in test DB and re-run test**

Run: `docker compose exec -u www-data api php artisan test --filter=QuestionBankModelsTest`
Expected: PASS (feature tests use `RefreshDatabase`, which migrates the `piclass_test` DB automatically).

- [ ] **Step 8: Commit**

```bash
git add database/migrations database/factories app/Models/QuestionBank.php app/Models/Question.php app/Models/QuestionVersion.php app/Models/QuestionOption.php app/Models/Topic.php app/Models/Tag.php tests/Feature/QuestionBank/QuestionBankModelsTest.php
git commit -m "feat: add Question Bank schema and models"
```

---

## Task 2: Topic/Tag search endpoints + upsert-by-name helper

**Files:**
- Create: `app/Http/Controllers/Api/QuestionBank/ListTopicsController.php`
- Create: `app/Http/Controllers/Api/QuestionBank/ListTagsController.php`
- Create: `app/Support/TopicTagResolver.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/QuestionBank/TopicTagSearchTest.php`

**Interfaces:**
- Produces: `TopicTagResolver::resolveTopics(string $subjectId, array $names): array<string>` (returns topic ids, creating missing ones), `TopicTagResolver::resolveTags(array $names): array<string>` (returns tag ids, creating missing ones). Task 3 consumes both.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/QuestionBank/TopicTagSearchTest.php
<?php

namespace Tests\Feature\QuestionBank;

use App\Models\Role;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicTagSearchTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_search_topics_filters_by_name(): void
    {
        $this->actingAsTeacher();
        Topic::factory()->create(['name' => 'Đại số']);
        Topic::factory()->create(['name' => 'Hình học']);

        $response = $this->getJson('/api/v1/topics?search=Đại');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Đại số', $response->json('data.0.name'));
    }

    public function test_search_tags_filters_by_name(): void
    {
        $this->actingAsTeacher();
        Tag::factory()->create(['name' => 'ôn-thi']);
        Tag::factory()->create(['name' => 'nâng-cao']);

        $response = $this->getJson('/api/v1/tags?search=ôn');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=TopicTagSearchTest`
Expected: FAIL with 404 (routes don't exist yet).

- [ ] **Step 3: Write the resolver**

```php
// app/Support/TopicTagResolver.php
<?php

namespace App\Support;

use App\Models\Tag;
use App\Models\Topic;

class TopicTagResolver
{
    public static function resolveTopics(string $subjectId, array $names): array
    {
        return array_map(
            fn (string $name) => Topic::firstOrCreate(
                ['subject_id' => $subjectId, 'name' => $name]
            )->id,
            $names
        );
    }

    public static function resolveTags(array $names): array
    {
        return array_map(
            fn (string $name) => Tag::firstOrCreate(['name' => $name])->id,
            $names
        );
    }
}
```

- [ ] **Step 4: Write the controllers**

```php
// app/Http/Controllers/Api/QuestionBank/ListTopicsController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;

class ListTopicsController extends Controller
{
    public function __invoke(Request $request)
    {
        $topics = Topic::query()
            ->when($request->query('search'), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->when($request->query('subject_id'), fn ($q, $subjectId) => $q->where('subject_id', $subjectId))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'subject_id', 'name']);

        return response()->json(['data' => $topics]);
    }
}
```

```php
// app/Http/Controllers/Api/QuestionBank/ListTagsController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class ListTagsController extends Controller
{
    public function __invoke(Request $request)
    {
        $tags = Tag::query()
            ->when($request->query('search'), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json(['data' => $tags]);
    }
}
```

- [ ] **Step 5: Wire the routes**

In `routes/api.php`, inside the existing `Route::middleware('role:admin,teacher')->group(...)` block (reuse it; create if the Academic section doesn't already have one scoped to exactly these two roles — check first, add a new one otherwise):

```php
Route::middleware('role:admin,teacher')->group(function () {
    // ... existing admin+teacher routes ...
    Route::get('/topics', \App\Http\Controllers\Api\QuestionBank\ListTopicsController::class);
    Route::get('/tags', \App\Http\Controllers\Api\QuestionBank\ListTagsController::class);
});
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=TopicTagSearchTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/QuestionBank/ListTopicsController.php app/Http/Controllers/Api/QuestionBank/ListTagsController.php app/Support/TopicTagResolver.php routes/api.php tests/Feature/QuestionBank/TopicTagSearchTest.php
git commit -m "feat: add Topic/Tag search endpoints and upsert resolver"
```

---

## Task 3: Create Question endpoint

**Files:**
- Create: `app/Http/Controllers/Api/QuestionBank/CreateQuestionController.php`
- Modify: `routes/api.php` (add `question` to UUID pattern list; add `POST /questions`)
- Test: `tests/Feature/QuestionBank/CreateQuestionTest.php`

**Interfaces:**
- Consumes: `TopicTagResolver::resolveTopics()`/`resolveTags()` (Task 2).
- Produces: `POST /api/v1/questions` → `{ "question": { "id", "question_bank_id", "subject_id", "status", "current_version": { "id", "version_number", "question_type", "prompt", "options": [...] } } }`, status `201`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/QuestionBank/CreateQuestionTest.php
<?php

namespace Tests\Feature\QuestionBank;

use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateQuestionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_teacher_creates_a_single_choice_question_with_options_and_new_topic(): void
    {
        $teacher = $this->actingAsTeacher();
        $subject = Subject::factory()->create();

        $response = $this->postJson('/api/v1/questions', [
            'subject_id' => $subject->id,
            'question_type' => 'single_choice',
            'prompt' => '2 + 2 = ?',
            'grading_mode' => 'automatic',
            'options' => [
                ['content' => ['text' => '3'], 'is_correct' => false],
                ['content' => ['text' => '4'], 'is_correct' => true],
            ],
            'topic_names' => ['Số học'],
            'tag_names' => ['lớp-1'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('question.current_version.prompt', '2 + 2 = ?');
        $this->assertDatabaseHas('questions', ['created_by_user_id' => $teacher->id, 'subject_id' => $subject->id]);
        $this->assertDatabaseHas('question_options', ['content' => json_encode(['text' => '4']), 'is_correct' => true]);
        $this->assertDatabaseHas('topics', ['name' => 'Số học', 'subject_id' => $subject->id]);
        $this->assertDatabaseHas('tags', ['name' => 'lớp-1']);
    }

    public function test_essay_question_requires_no_options(): void
    {
        $this->actingAsTeacher();

        $response = $this->postJson('/api/v1/questions', [
            'question_type' => 'essay',
            'prompt' => 'Phân tích bài thơ.',
            'grading_mode' => 'manual',
        ]);

        $response->assertCreated();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=CreateQuestionTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controller**

```php
// app/Http/Controllers/Api/QuestionBank/CreateQuestionController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Support\TopicTagResolver;
use Illuminate\Http\Request;

class CreateQuestionController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'subject_id' => ['nullable', 'uuid', 'exists:subjects,id'],
            'question_type' => ['required', 'in:single_choice,multiple_choice,true_false,fill_in_the_blank,short_answer,essay,matching,ordering,file_upload'],
            'prompt' => ['required', 'string'],
            'instructions' => ['nullable', 'string'],
            'suggested_points' => ['nullable', 'numeric'],
            'difficulty' => ['nullable', 'string'],
            'grading_mode' => ['required', 'in:automatic,manual,mixed'],
            'type_payload' => ['nullable', 'array'],
            'grading_config' => ['nullable', 'array'],
            'options' => ['array'],
            'options.*.content' => ['required', 'array'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'topic_names' => ['array'],
            'topic_names.*' => ['string'],
            'tag_names' => ['array'],
            'tag_names.*' => ['string'],
        ]);

        $bankId = QuestionBank::query()->value('id') ?? QuestionBank::create(['name' => 'System Bank'])->id;

        $question = Question::create([
            'question_bank_id' => $bankId,
            'subject_id' => $data['subject_id'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'status' => 'active',
        ]);

        $version = QuestionVersion::create([
            'question_id' => $question->id,
            'version_number' => 1,
            'question_type' => $data['question_type'],
            'prompt' => $data['prompt'],
            'instructions' => $data['instructions'] ?? null,
            'suggested_points' => $data['suggested_points'] ?? null,
            'difficulty' => $data['difficulty'] ?? null,
            'grading_mode' => $data['grading_mode'],
            'type_payload' => $data['type_payload'] ?? null,
            'grading_config' => $data['grading_config'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        foreach ($data['options'] ?? [] as $position => $option) {
            QuestionOption::create([
                'question_version_id' => $version->id,
                'position' => $position + 1,
                'content' => $option['content'],
                'is_correct' => $option['is_correct'] ?? null,
                'created_at' => now(),
            ]);
        }

        if (! empty($data['topic_names']) && $data['subject_id'] ?? null) {
            $version->topics()->attach(TopicTagResolver::resolveTopics($data['subject_id'], $data['topic_names']));
        }
        if (! empty($data['tag_names'])) {
            $version->tags()->attach(TopicTagResolver::resolveTags($data['tag_names']));
        }

        $question->update(['current_version_id' => $version->id]);
        $version->load('options', 'topics', 'tags');

        return response()->json(['question' => [
            'id' => $question->id,
            'question_bank_id' => $question->question_bank_id,
            'subject_id' => $question->subject_id,
            'status' => $question->status,
            'current_version' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'question_type' => $version->question_type,
                'prompt' => $version->prompt,
                'options' => $version->options,
                'topics' => $version->topics,
                'tags' => $version->tags,
            ],
        ]], 201);
    }
}
```

- [ ] **Step 4: Wire the route**

In `routes/api.php`, add `'question'` to the `Route::pattern()` UUID loop array, then inside `Route::middleware('role:admin,teacher')->group(...)`:

```php
Route::post('/questions', \App\Http\Controllers\Api\QuestionBank\CreateQuestionController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=CreateQuestionTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/QuestionBank/CreateQuestionController.php routes/api.php tests/Feature/QuestionBank/CreateQuestionTest.php
git commit -m "feat: add POST /questions creating Question + first QuestionVersion"
```

---

## Task 4: List/Get Question endpoints

**Files:**
- Create: `app/Http/Controllers/Api/QuestionBank/ListQuestionsController.php`
- Create: `app/Http/Controllers/Api/QuestionBank/GetQuestionController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/QuestionBank/ListAndGetQuestionTest.php`

**Interfaces:**
- Produces: `GET /api/v1/questions` (paginated, filters `subject_id`, `question_type`, `topic_id`, `tag_id`, `status`, `mine=true`), `GET /api/v1/questions/{id}`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/QuestionBank/ListAndGetQuestionTest.php
<?php

namespace Tests\Feature\QuestionBank;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListAndGetQuestionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_mine_filter_returns_only_own_questions(): void
    {
        $teacher = $this->actingAsTeacher();
        $own = Question::factory()->create(['created_by_user_id' => $teacher->id]);
        Question::factory()->create();

        $response = $this->getJson('/api/v1/questions?mine=true');

        $response->assertOk();
        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$own->id], $ids);
    }

    public function test_get_question_returns_current_version(): void
    {
        $this->actingAsTeacher();
        $question = Question::factory()->create();
        $version = QuestionVersion::factory()->create(['question_id' => $question->id]);
        $question->update(['current_version_id' => $version->id]);

        $response = $this->getJson("/api/v1/questions/{$question->id}");

        $response->assertOk();
        $response->assertJsonPath('question.current_version.id', $version->id);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=ListAndGetQuestionTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controllers**

```php
// app/Http/Controllers/Api/QuestionBank/ListQuestionsController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class ListQuestionsController extends Controller
{
    public function __invoke(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);

        $questions = Question::query()
            ->with('currentVersion')
            ->when($request->boolean('mine'), fn ($q) => $q->where('created_by_user_id', $request->user()->id))
            ->when($request->query('subject_id'), fn ($q, $v) => $q->where('subject_id', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('question_type'), fn ($q, $v) => $q->whereHas('currentVersion', fn ($vq) => $vq->where('question_type', $v)))
            ->when($request->query('topic_id'), fn ($q, $v) => $q->whereHas('currentVersion.topics', fn ($tq) => $tq->where('topics.id', $v)))
            ->when($request->query('tag_id'), fn ($q, $v) => $q->whereHas('currentVersion.tags', fn ($tq) => $tq->where('tags.id', $v)))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $questions->items(),
            'meta' => [
                'total' => $questions->total(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
            ],
        ]);
    }
}
```

```php
// app/Http/Controllers/Api/QuestionBank/GetQuestionController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Question;

class GetQuestionController extends Controller
{
    public function __invoke(Question $question)
    {
        $question->load('currentVersion.options', 'currentVersion.topics', 'currentVersion.tags');

        return response()->json(['question' => [
            'id' => $question->id,
            'question_bank_id' => $question->question_bank_id,
            'subject_id' => $question->subject_id,
            'status' => $question->status,
            'created_by_user_id' => $question->created_by_user_id,
            'current_version' => $question->currentVersion,
        ]]);
    }
}
```

- [ ] **Step 4: Wire the routes**

```php
Route::middleware('role:admin,teacher')->group(function () {
    Route::get('/questions', \App\Http\Controllers\Api\QuestionBank\ListQuestionsController::class);
    Route::get('/questions/{question}', \App\Http\Controllers\Api\QuestionBank\GetQuestionController::class);
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=ListAndGetQuestionTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/QuestionBank/ListQuestionsController.php app/Http/Controllers/Api/QuestionBank/GetQuestionController.php routes/api.php tests/Feature/QuestionBank/ListAndGetQuestionTest.php
git commit -m "feat: add GET /questions list and detail endpoints"
```

---

## Task 5: Update Question (new version) + ownership guard

**Files:**
- Create: `app/Http/Controllers/Api/QuestionBank/UpdateQuestionController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/QuestionBank/UpdateQuestionTest.php`

**Interfaces:**
- Produces: `PATCH /api/v1/questions/{id}` → creates new `QuestionVersion`, moves `current_version_id` forward, old version/snapshots untouched (Review Focus item 2).

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/QuestionBank/UpdateQuestionTest.php
<?php

namespace Tests\Feature\QuestionBank;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateQuestionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_owner_edit_creates_new_version_and_keeps_old_one_intact(): void
    {
        $teacher = $this->actingAsTeacher();
        $question = Question::factory()->create(['created_by_user_id' => $teacher->id]);
        $v1 = QuestionVersion::factory()->create(['question_id' => $question->id, 'version_number' => 1, 'prompt' => 'Bản cũ']);
        $question->update(['current_version_id' => $v1->id]);

        $response = $this->patchJson("/api/v1/questions/{$question->id}", [
            'question_type' => 'short_answer',
            'prompt' => 'Bản mới',
            'grading_mode' => 'manual',
        ]);

        $response->assertOk();
        $question->refresh();
        $this->assertNotSame($v1->id, $question->current_version_id);
        $this->assertDatabaseHas('question_versions', ['id' => $v1->id, 'prompt' => 'Bản cũ']);
        $this->assertDatabaseHas('question_versions', ['question_id' => $question->id, 'prompt' => 'Bản mới', 'version_number' => 2]);
    }

    public function test_non_owner_teacher_cannot_edit(): void
    {
        $owner = $this->actingAsTeacher();
        $question = Question::factory()->create(['created_by_user_id' => $owner->id]);

        $otherTeacher = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $otherTeacher->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($otherTeacher)->withSession(['active_role_code' => 'teacher']);

        $response = $this->patchJson("/api/v1/questions/{$question->id}", [
            'question_type' => 'essay', 'prompt' => 'Hack', 'grading_mode' => 'manual',
        ]);

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=UpdateQuestionTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controller**

```php
// app/Http/Controllers/Api/QuestionBank/UpdateQuestionController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Support\TopicTagResolver;
use Illuminate\Http\Request;

class UpdateQuestionController extends Controller
{
    public function __invoke(Request $request, Question $question)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $question->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo câu hỏi hoặc Admin được sửa.'
        );

        $data = $request->validate([
            'question_type' => ['required', 'in:single_choice,multiple_choice,true_false,fill_in_the_blank,short_answer,essay,matching,ordering,file_upload'],
            'prompt' => ['required', 'string'],
            'instructions' => ['nullable', 'string'],
            'suggested_points' => ['nullable', 'numeric'],
            'difficulty' => ['nullable', 'string'],
            'grading_mode' => ['required', 'in:automatic,manual,mixed'],
            'type_payload' => ['nullable', 'array'],
            'grading_config' => ['nullable', 'array'],
            'options' => ['array'],
            'options.*.content' => ['required', 'array'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'topic_names' => ['array'],
            'tag_names' => ['array'],
        ]);

        $nextVersionNumber = $question->versions()->max('version_number') + 1;

        $version = QuestionVersion::create([
            'question_id' => $question->id,
            'version_number' => $nextVersionNumber,
            'question_type' => $data['question_type'],
            'prompt' => $data['prompt'],
            'instructions' => $data['instructions'] ?? null,
            'suggested_points' => $data['suggested_points'] ?? null,
            'difficulty' => $data['difficulty'] ?? null,
            'grading_mode' => $data['grading_mode'],
            'type_payload' => $data['type_payload'] ?? null,
            'grading_config' => $data['grading_config'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        foreach ($data['options'] ?? [] as $position => $option) {
            QuestionOption::create([
                'question_version_id' => $version->id,
                'position' => $position + 1,
                'content' => $option['content'],
                'is_correct' => $option['is_correct'] ?? null,
                'created_at' => now(),
            ]);
        }

        if (! empty($data['topic_names']) && $question->subject_id) {
            $version->topics()->attach(TopicTagResolver::resolveTopics($question->subject_id, $data['topic_names']));
        }
        if (! empty($data['tag_names'])) {
            $version->tags()->attach(TopicTagResolver::resolveTags($data['tag_names']));
        }

        $question->update(['current_version_id' => $version->id]);

        return response()->json(['question' => [
            'id' => $question->id,
            'current_version' => $version->load('options', 'topics', 'tags'),
        ]]);
    }
}
```

- [ ] **Step 4: Wire the route**

```php
Route::patch('/questions/{question}', \App\Http\Controllers\Api\QuestionBank\UpdateQuestionController::class);
```
(inside the same `role:admin,teacher` group as Task 3/4's writes)

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=UpdateQuestionTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/QuestionBank/UpdateQuestionController.php routes/api.php tests/Feature/QuestionBank/UpdateQuestionTest.php
git commit -m "feat: add PATCH /questions/{id} creating a new version, owner-only"
```

---

## Task 6: PATCH topics/tags on current version (no new version)

**Files:**
- Create: `app/Http/Controllers/Api/QuestionBank/UpdateQuestionTopicsController.php`
- Create: `app/Http/Controllers/Api/QuestionBank/UpdateQuestionTagsController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/QuestionBank/UpdateQuestionTopicsTagsTest.php`

**Interfaces:**
- Produces: `PATCH /api/v1/questions/{id}/topics` body `{ "topic_names": [...] }`, `PATCH /api/v1/questions/{id}/tags` body `{ "tag_names": [...] }` — both replace the full set on `question.currentVersion`, do not touch `version_number`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/QuestionBank/UpdateQuestionTopicsTagsTest.php
<?php

namespace Tests\Feature\QuestionBank;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateQuestionTopicsTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacing_tags_does_not_bump_version_number(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        $question = Question::factory()->create(['created_by_user_id' => $user->id]);
        $version = QuestionVersion::factory()->create(['question_id' => $question->id, 'version_number' => 1]);
        $question->update(['current_version_id' => $version->id]);
        $oldTag = Tag::factory()->create(['name' => 'cũ']);
        $version->tags()->attach($oldTag->id);

        $response = $this->patchJson("/api/v1/questions/{$question->id}/tags", ['tag_names' => ['mới']]);

        $response->assertOk();
        $version->refresh();
        $this->assertSame(1, $version->version_number);
        $this->assertCount(1, $version->tags);
        $this->assertSame('mới', $version->tags->first()->name);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=UpdateQuestionTopicsTagsTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controllers**

```php
// app/Http/Controllers/Api/QuestionBank/UpdateQuestionTopicsController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Support\TopicTagResolver;
use Illuminate\Http\Request;

class UpdateQuestionTopicsController extends Controller
{
    public function __invoke(Request $request, Question $question)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $question->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo câu hỏi hoặc Admin được sửa.'
        );
        abort_if(! $question->subject_id, 422, 'Câu hỏi chưa gắn Subject nên không gắn được Topic.');

        $data = $request->validate(['topic_names' => ['array'], 'topic_names.*' => ['string']]);

        $version = $question->currentVersion;
        $version->topics()->sync(TopicTagResolver::resolveTopics($question->subject_id, $data['topic_names'] ?? []));

        return response()->json(['topics' => $version->topics()->get(['topics.id', 'name'])]);
    }
}
```

```php
// app/Http/Controllers/Api/QuestionBank/UpdateQuestionTagsController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Support\TopicTagResolver;
use Illuminate\Http\Request;

class UpdateQuestionTagsController extends Controller
{
    public function __invoke(Request $request, Question $question)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $question->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo câu hỏi hoặc Admin được sửa.'
        );

        $data = $request->validate(['tag_names' => ['array'], 'tag_names.*' => ['string']]);

        $version = $question->currentVersion;
        $version->tags()->sync(TopicTagResolver::resolveTags($data['tag_names'] ?? []));

        return response()->json(['tags' => $version->tags()->get(['tags.id', 'name'])]);
    }
}
```

- [ ] **Step 4: Wire the routes**

```php
Route::patch('/questions/{question}/topics', \App\Http\Controllers\Api\QuestionBank\UpdateQuestionTopicsController::class);
Route::patch('/questions/{question}/tags', \App\Http\Controllers\Api\QuestionBank\UpdateQuestionTagsController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=UpdateQuestionTopicsTagsTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/QuestionBank/UpdateQuestionTopicsController.php app/Http/Controllers/Api/QuestionBank/UpdateQuestionTagsController.php routes/api.php tests/Feature/QuestionBank/UpdateQuestionTopicsTagsTest.php
git commit -m "feat: add PATCH /questions/{id}/topics and /tags (no new version)"
```

---

## Task 7: Archive Question

**Files:**
- Create: `app/Http/Controllers/Api/QuestionBank/ArchiveQuestionController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/QuestionBank/ArchiveQuestionTest.php`

**Interfaces:**
- Produces: `DELETE /api/v1/questions/{id}` → `status=archived`, `archived_at` set, row kept (no physical delete, `BR-ASSESS-010`).

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/QuestionBank/ArchiveQuestionTest.php
<?php

namespace Tests\Feature\QuestionBank;

use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveQuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_teacher_archives_own_question(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        $question = Question::factory()->create(['created_by_user_id' => $user->id]);

        $response = $this->deleteJson("/api/v1/questions/{$question->id}");

        $response->assertOk();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'status' => 'archived']);
        $this->assertNotNull($question->refresh()->archived_at);
    }

    public function test_non_owner_teacher_cannot_archive(): void
    {
        $owner = User::factory()->create();
        $question = Question::factory()->create(['created_by_user_id' => $owner->id]);

        $other = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $other->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($other)->withSession(['active_role_code' => 'teacher']);

        $response = $this->deleteJson("/api/v1/questions/{$question->id}");

        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=ArchiveQuestionTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controller**

```php
// app/Http/Controllers/Api/QuestionBank/ArchiveQuestionController.php
<?php

namespace App\Http\Controllers\Api\QuestionBank;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class ArchiveQuestionController extends Controller
{
    public function __invoke(Request $request, Question $question)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $question->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo câu hỏi hoặc Admin được lưu trữ.'
        );

        $question->update(['status' => 'archived', 'archived_at' => now()]);

        return response()->json(['question' => ['id' => $question->id, 'status' => $question->status]]);
    }
}
```

- [ ] **Step 4: Wire the route**

```php
Route::delete('/questions/{question}', \App\Http\Controllers\Api\QuestionBank\ArchiveQuestionController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=ArchiveQuestionTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/QuestionBank/ArchiveQuestionController.php routes/api.php tests/Feature/QuestionBank/ArchiveQuestionTest.php
git commit -m "feat: add DELETE /questions/{id} archiving owner-only"
```

---

## Task 8: Assessment schema + models (Class/Student/Course audience)

**Files:**
- Create: `database/migrations/2026_10_15_000010_create_assessments_table.php`
- Create: `database/migrations/2026_10_15_000011_create_assessment_versions_table.php`
- Create: `database/migrations/2026_10_15_000012_add_current_version_fk_to_assessments_table.php`
- Create: `database/migrations/2026_10_15_000013_create_assessment_questions_table.php`
- Create: `database/migrations/2026_10_15_000014_create_assessment_audiences_table.php`
- Create: `database/migrations/2026_10_15_000015_create_student_assessments_table.php`
- Create: `app/Models/Assessment.php`, `app/Models/AssessmentVersion.php`, `app/Models/AssessmentQuestion.php`, `app/Models/AssessmentAudience.php`, `app/Models/StudentAssessment.php`
- Create: `database/factories/AssessmentFactory.php`, `AssessmentVersionFactory.php`, `AssessmentQuestionFactory.php`, `AssessmentAudienceFactory.php`, `StudentAssessmentFactory.php`
- Test: `tests/Feature/Assessment/AssessmentModelsTest.php`

**Interfaces:**
- Consumes: `Question`/`QuestionVersion` (Task 1), `SchoolClass`/`Student`/`LessonSession` (existing models), **`Course` (existing model from Academic module, `courses` table with `owner_teacher_id`; confirm exact name per the Setup caveat)**.
- Produces: `Assessment::versions()`, `Assessment::currentVersion()`, `AssessmentVersion::questions()`, `AssessmentVersion::audiences()`, `AssessmentVersion::assessment()`, `Assessment::studentAssessments()`. These relationship names are used by every later task in this plan — do not rename.
- `AssessmentAudience` carries exactly one of `class_id`/`student_id`/`course_id` (`DEC-LRN-010`); `StudentAssessment` carries `source_class_id`/`source_course_id` to record which path produced it.

- [ ] **Step 1: Write the migrations**

```php
// database/migrations/2026_10_15_000010_create_assessments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['assignment', 'quiz', 'exam']);
            $table->foreignUuid('created_by_user_id')->constrained('users');
            $table->enum('status', ['draft', 'published', 'closed', 'cancelled', 'archived'])->default('draft');
            $table->uuid('current_version_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
```

```php
// database/migrations/2026_10_15_000011_create_assessment_versions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->enum('status', ['draft', 'published', 'withdrawn'])->default('draft');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('max_attempts')->default(1);
            $table->enum('attempt_score_policy', ['highest', 'latest'])->default('latest');
            $table->json('late_policy')->nullable();
            $table->boolean('per_student_extensions')->default(false);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->decimal('passing_score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->timestamp('result_release_at')->nullable();
            $table->boolean('show_answers')->default(false);
            $table->boolean('show_explanations')->default(false);
            $table->boolean('show_question_scores')->default(false);
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignUuid('lesson_session_id')->nullable()->constrained('lesson_sessions')->nullOnDelete();
            $table->foreignUuid('created_by_user_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_versions');
    }
};
```

```php
// database/migrations/2026_10_15_000012_add_current_version_fk_to_assessments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('assessment_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });
    }
};
```

```php
// database/migrations/2026_10_15_000013_create_assessment_questions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('assessment_version_id')->constrained('assessment_versions')->cascadeOnDelete();
            $table->foreignUuid('source_question_version_id')->nullable()->constrained('question_versions')->nullOnDelete();
            $table->integer('position');
            $table->decimal('points', 8, 2);
            $table->enum('question_type', [
                'single_choice', 'multiple_choice', 'true_false', 'fill_in_the_blank',
                'short_answer', 'essay', 'matching', 'ordering', 'file_upload',
            ]);
            $table->json('snapshot');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
    }
};
```

```php
// database/migrations/2026_10_15_000014_create_assessment_audiences_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_audiences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('assessment_version_id')->constrained('assessment_versions')->cascadeOnDelete();
            $table->foreignUuid('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
            $table->foreignUuid('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->timestamp('valid_from');
            $table->timestamp('valid_to')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_audiences');
    }
};
```

```php
// database/migrations/2026_10_15_000015_create_student_assessments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('assigned_version_id')->nullable()->constrained('assessment_versions')->nullOnDelete();
            $table->foreignUuid('source_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignUuid('source_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignUuid('source_audience_id')->nullable()->constrained('assessment_audiences')->nullOnDelete();
            $table->enum('status', ['assigned', 'withdrawn', 'completed'])->default('assigned');
            $table->timestamp('individual_deadline_at')->nullable();
            $table->integer('attempt_limit_override')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assessments');
    }
};
```

- [ ] **Step 2: Run migrations**

Run: `docker compose exec -u www-data api php artisan migrate`
Expected: all 6 new migrations run without error.

- [ ] **Step 3: Write the models**

```php
// app/Models/Assessment.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['type', 'created_by_user_id', 'status', 'current_version_id', 'archived_at'];

    protected $casts = ['archived_at' => 'datetime'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function versions()
    {
        return $this->hasMany(AssessmentVersion::class);
    }

    public function currentVersion()
    {
        return $this->belongsTo(AssessmentVersion::class, 'current_version_id');
    }

    public function studentAssessments()
    {
        return $this->hasMany(StudentAssessment::class);
    }
}
```

```php
// app/Models/AssessmentVersion.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentVersion extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'assessment_id', 'version_number', 'title', 'instructions', 'status',
        'opens_at', 'closes_at', 'deadline_at', 'duration_seconds', 'max_attempts',
        'attempt_score_policy', 'late_policy', 'per_student_extensions',
        'shuffle_questions', 'shuffle_options', 'passing_score', 'max_score',
        'result_release_at', 'show_answers', 'show_explanations', 'show_question_scores',
        'cancellation_reason', 'published_at', 'lesson_session_id', 'created_by_user_id', 'created_at',
    ];

    protected $casts = [
        'opens_at' => 'datetime', 'closes_at' => 'datetime', 'deadline_at' => 'datetime',
        'late_policy' => 'array', 'per_student_extensions' => 'boolean',
        'shuffle_questions' => 'boolean', 'shuffle_options' => 'boolean',
        'result_release_at' => 'datetime', 'show_answers' => 'boolean',
        'show_explanations' => 'boolean', 'show_question_scores' => 'boolean',
        'published_at' => 'datetime', 'created_at' => 'datetime',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('position');
    }

    public function audiences()
    {
        return $this->hasMany(AssessmentAudience::class);
    }

    public function lessonSession()
    {
        return $this->belongsTo(LessonSession::class);
    }
}
```

```php
// app/Models/AssessmentQuestion.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentQuestion extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'assessment_version_id', 'source_question_version_id', 'position',
        'points', 'question_type', 'snapshot', 'created_at',
    ];

    protected $casts = ['snapshot' => 'array', 'created_at' => 'datetime'];

    public function assessmentVersion()
    {
        return $this->belongsTo(AssessmentVersion::class);
    }

    public function sourceQuestionVersion()
    {
        return $this->belongsTo(QuestionVersion::class, 'source_question_version_id');
    }
}
```

```php
// app/Models/AssessmentAudience.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentAudience extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['assessment_version_id', 'class_id', 'student_id', 'course_id', 'valid_from', 'valid_to', 'created_at'];

    protected $casts = ['valid_from' => 'datetime', 'valid_to' => 'datetime', 'created_at' => 'datetime'];

    public function assessmentVersion()
    {
        return $this->belongsTo(AssessmentVersion::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
```

```php
// app/Models/StudentAssessment.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAssessment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'assessment_id', 'student_id', 'assigned_version_id', 'source_class_id',
        'source_course_id', 'source_audience_id', 'status', 'individual_deadline_at', 'attempt_limit_override',
    ];

    protected $casts = ['individual_deadline_at' => 'datetime'];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assignedVersion()
    {
        return $this->belongsTo(AssessmentVersion::class, 'assigned_version_id');
    }

    public function sourceCourse()
    {
        return $this->belongsTo(Course::class, 'source_course_id');
    }
}
```

- [ ] **Step 4: Write the factories**

```php
// database/factories/AssessmentFactory.php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    protected $model = \App\Models\Assessment::class;

    public function definition(): array
    {
        return ['type' => 'quiz', 'created_by_user_id' => User::factory(), 'status' => 'draft'];
    }
}
```

```php
// database/factories/AssessmentVersionFactory.php
<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentVersionFactory extends Factory
{
    protected $model = \App\Models\AssessmentVersion::class;

    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(3),
            'status' => 'draft',
            'max_attempts' => 1,
            'attempt_score_policy' => 'latest',
            'created_by_user_id' => User::factory(),
            'created_at' => now(),
        ];
    }
}
```

```php
// database/factories/AssessmentQuestionFactory.php
<?php

namespace Database\Factories;

use App\Models\AssessmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentQuestionFactory extends Factory
{
    protected $model = \App\Models\AssessmentQuestion::class;

    public function definition(): array
    {
        return [
            'assessment_version_id' => AssessmentVersion::factory(),
            'position' => 1,
            'points' => 10,
            'question_type' => 'single_choice',
            'snapshot' => ['prompt' => fake()->sentence()],
            'created_at' => now(),
        ];
    }
}
```

```php
// database/factories/AssessmentAudienceFactory.php
<?php

namespace Database\Factories;

use App\Models\AssessmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentAudienceFactory extends Factory
{
    protected $model = \App\Models\AssessmentAudience::class;

    public function definition(): array
    {
        return ['assessment_version_id' => AssessmentVersion::factory(), 'valid_from' => now(), 'created_at' => now()];
    }
}
```

```php
// database/factories/StudentAssessmentFactory.php
<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentAssessmentFactory extends Factory
{
    protected $model = \App\Models\StudentAssessment::class;

    public function definition(): array
    {
        return ['assessment_id' => Assessment::factory(), 'student_id' => Student::factory(), 'status' => 'assigned'];
    }
}
```

- [ ] **Step 5: Write the failing test**

```php
// tests/Feature/Assessment/AssessmentModelsTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentAudience;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Student;
use App\Models\StudentAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_version_carries_questions_and_audiences(): void
    {
        $assessment = Assessment::factory()->create();
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $assessment->update(['current_version_id' => $version->id]);

        AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'position' => 1]);
        $student = Student::factory()->create();
        AssessmentAudience::factory()->create(['assessment_version_id' => $version->id, 'student_id' => $student->id]);

        $studentAssessment = StudentAssessment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'assigned_version_id' => $version->id,
        ]);

        $assessment->refresh();

        $this->assertTrue($assessment->currentVersion->is($version));
        $this->assertCount(1, $version->questions);
        $this->assertCount(1, $version->audiences);
        $this->assertTrue($assessment->studentAssessments->contains($studentAssessment));
    }

    public function test_audience_can_be_sourced_from_a_course(): void
    {
        $course = \App\Models\Course::factory()->create();
        $assessment = Assessment::factory()->create();
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $assessment->update(['current_version_id' => $version->id]);

        $audience = AssessmentAudience::factory()->create(['assessment_version_id' => $version->id, 'course_id' => $course->id, 'student_id' => null]);

        $this->assertTrue($audience->course->is($course));
        $this->assertNull($audience->class_id);
        $this->assertNull($audience->student_id);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=AssessmentModelsTest`
Expected: PASS (RefreshDatabase migrates `piclass_test` automatically).

- [ ] **Step 7: Commit**

```bash
git add database/migrations database/factories app/Models/Assessment.php app/Models/AssessmentVersion.php app/Models/AssessmentQuestion.php app/Models/AssessmentAudience.php app/Models/StudentAssessment.php tests/Feature/Assessment/AssessmentModelsTest.php
git commit -m "feat: add Assessment schema and models with Class/Student/Course audience"
```

---

## Task 9: Create Assessment (draft) + ownership rule

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/CreateAssessmentController.php`
- Modify: `routes/api.php` (add `assessment` to UUID pattern list)
- Test: `tests/Feature/Assessment/CreateAssessmentTest.php`

**Interfaces:**
- Produces: `POST /api/v1/assessments` → `201` `{ "assessment": { "id", "type", "status", "current_version": {...} } }`. Establishes the ownership rule every later task reuses: `$assessment->created_by_user_id === $request->user()->id` for Teacher write access (no new Support class — see Global Constraints).

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/CreateAssessmentTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\LessonSession;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_teacher_creates_a_draft_quiz(): void
    {
        $teacher = $this->actingAsTeacher();

        $response = $this->postJson('/api/v1/assessments', [
            'type' => 'quiz',
            'title' => 'Kiểm tra 15 phút',
            'max_attempts' => 2,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('assessment.status', 'draft');
        $response->assertJsonPath('assessment.current_version.title', 'Kiểm tra 15 phút');
        $this->assertDatabaseHas('assessments', ['created_by_user_id' => $teacher->id, 'type' => 'quiz']);
        $this->assertDatabaseHas('assessment_versions', ['title' => 'Kiểm tra 15 phút', 'max_attempts' => 2, 'status' => 'draft']);
    }

    public function test_assignment_can_link_one_lesson_session(): void
    {
        $this->actingAsTeacher();
        $session = LessonSession::factory()->create();

        $response = $this->postJson('/api/v1/assessments', [
            'type' => 'assignment',
            'title' => 'Bài tập về nhà',
            'lesson_session_id' => $session->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('assessment_versions', ['lesson_session_id' => $session->id]);
    }

    public function test_quiz_cannot_link_a_lesson_session(): void
    {
        $this->actingAsTeacher();
        $session = LessonSession::factory()->create();

        $response = $this->postJson('/api/v1/assessments', [
            'type' => 'quiz',
            'title' => 'Kiểm tra',
            'lesson_session_id' => $session->id,
        ]);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=CreateAssessmentTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controller**

```php
// app/Http/Controllers/Api/Assessment/CreateAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentVersion;
use Illuminate\Http\Request;

class CreateAssessmentController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:assignment,quiz,exam'],
            'title' => ['required', 'string'],
            'instructions' => ['nullable', 'string'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date'],
            'duration_seconds' => ['nullable', 'integer', 'min:1'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
            'attempt_score_policy' => ['nullable', 'in:highest,latest'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'passing_score' => ['nullable', 'numeric'],
            'max_score' => ['nullable', 'numeric'],
            'show_answers' => ['nullable', 'boolean'],
            'show_explanations' => ['nullable', 'boolean'],
            'show_question_scores' => ['nullable', 'boolean'],
            'lesson_session_id' => ['nullable', 'uuid', 'exists:lesson_sessions,id'],
        ]);

        abort_if(
            ! empty($data['lesson_session_id']) && $data['type'] !== 'assignment',
            422,
            'Chỉ Assignment được liên kết LessonSession.'
        );

        $assessment = Assessment::create([
            'type' => $data['type'],
            'created_by_user_id' => $request->user()->id,
            'status' => 'draft',
        ]);

        $version = AssessmentVersion::create([
            'assessment_id' => $assessment->id,
            'version_number' => 1,
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'status' => 'draft',
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? 1,
            'attempt_score_policy' => $data['attempt_score_policy'] ?? 'latest',
            'shuffle_questions' => $data['shuffle_questions'] ?? false,
            'shuffle_options' => $data['shuffle_options'] ?? false,
            'passing_score' => $data['passing_score'] ?? null,
            'max_score' => $data['max_score'] ?? null,
            'show_answers' => $data['show_answers'] ?? false,
            'show_explanations' => $data['show_explanations'] ?? false,
            'show_question_scores' => $data['show_question_scores'] ?? false,
            'lesson_session_id' => $data['lesson_session_id'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        $assessment->update(['current_version_id' => $version->id]);

        return response()->json(['assessment' => [
            'id' => $assessment->id,
            'type' => $assessment->type,
            'status' => $assessment->status,
            'current_version' => $version,
        ]], 201);
    }
}
```

- [ ] **Step 4: Wire the route**

Add `'assessment'` to the `Route::pattern()` UUID loop in `routes/api.php`, then inside `Route::middleware('role:admin,teacher')->group(...)`:

```php
Route::post('/assessments', \App\Http\Controllers\Api\Assessment\CreateAssessmentController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=CreateAssessmentTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/CreateAssessmentController.php routes/api.php tests/Feature/Assessment/CreateAssessmentTest.php
git commit -m "feat: add POST /assessments creating Assessment + draft AssessmentVersion"
```

---

## Task 10: List/Get Assessment with scope (Class / Course / own)

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/ListAssessmentsController.php`
- Create: `app/Http/Controllers/Api/Assessment/GetAssessmentController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/ListAndGetAssessmentTest.php`

**Interfaces:**
- Consumes: `Teacher::activeClassIds()` (existing, Academic module), `Course` model (`owner_teacher_id` column, existing, Academic module).
- Produces: `GET /api/v1/assessments` (Admin: all; Teacher: own, OR audience class in their active classes, OR audience course they own), `GET /api/v1/assessments/{id}` (same scope, `404` if out of scope — do not leak existence via `403`).

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/ListAndGetAssessmentTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentAudience;
use App\Models\AssessmentVersion;
use App\Models\Course;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListAndGetAssessmentTest extends TestCase
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

    public function test_teacher_sees_own_class_assigned_and_course_owned_but_not_unrelated_assessments(): void
    {
        $teacher = Teacher::factory()->create();
        $actingUser = $this->actingAsTeacher($teacher);

        $own = Assessment::factory()->create(['created_by_user_id' => $actingUser->id]);

        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::create(['class_id' => $class->id, 'teacher_id' => $teacher->id, 'status' => 'active', 'valid_from' => now()->subDay()]);
        $classAssigned = Assessment::factory()->create();
        $classVersion = AssessmentVersion::factory()->create(['assessment_id' => $classAssigned->id, 'status' => 'published']);
        $classAssigned->update(['current_version_id' => $classVersion->id]);
        AssessmentAudience::factory()->create(['assessment_version_id' => $classVersion->id, 'class_id' => $class->id, 'valid_from' => now()]);

        $course = Course::factory()->create(['owner_teacher_id' => $teacher->id]);
        $courseAssigned = Assessment::factory()->create();
        $courseVersion = AssessmentVersion::factory()->create(['assessment_id' => $courseAssigned->id, 'status' => 'published']);
        $courseAssigned->update(['current_version_id' => $courseVersion->id]);
        AssessmentAudience::factory()->create(['assessment_version_id' => $courseVersion->id, 'course_id' => $course->id, 'valid_from' => now()]);

        $unrelated = Assessment::factory()->create();

        $response = $this->getJson('/api/v1/assessments');

        $response->assertOk();
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($own->id, $ids);
        $this->assertContains($classAssigned->id, $ids);
        $this->assertContains($courseAssigned->id, $ids);
        $this->assertNotContains($unrelated->id, $ids);
    }

    public function test_get_assessment_out_of_scope_returns_404(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);
        $other = Assessment::factory()->create();

        $response = $this->getJson("/api/v1/assessments/{$other->id}");

        $response->assertStatus(404);
    }

    public function test_teacher_cannot_see_assessment_via_a_course_owned_by_someone_else(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAsTeacher($teacher);

        $otherTeacher = Teacher::factory()->create();
        $course = Course::factory()->create(['owner_teacher_id' => $otherTeacher->id]);
        $assessment = Assessment::factory()->create();
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'published']);
        $assessment->update(['current_version_id' => $version->id]);
        AssessmentAudience::factory()->create(['assessment_version_id' => $version->id, 'course_id' => $course->id, 'valid_from' => now()]);

        $response = $this->getJson("/api/v1/assessments/{$assessment->id}");

        $response->assertStatus(404);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=ListAndGetAssessmentTest`
Expected: FAIL with 404 (routes don't exist yet — same status code as the assertion, but for the right reason; check it's a route-not-found response, not a false pass).

- [ ] **Step 3: Write the controllers**

```php
// app/Http/Controllers/Api/Assessment/ListAssessmentsController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Http\Request;

class ListAssessmentsController extends Controller
{
    public function __invoke(Request $request)
    {
        $activeRole = $request->session()->get('active_role_code');
        $perPage = (int) $request->query('per_page', 20);

        $query = Assessment::query()->with('currentVersion');

        if ($activeRole !== 'admin') {
            $teacherUserId = $request->user()->id;
            $classIds = $request->user()->teacherProfile?->activeClassIds() ?? collect();
            $teacherId = $request->user()->teacherProfile?->id;
            $ownedCourseIds = $teacherId ? Course::where('owner_teacher_id', $teacherId)->pluck('id') : collect();

            $query->where(function ($q) use ($teacherUserId, $classIds, $ownedCourseIds) {
                $q->where('created_by_user_id', $teacherUserId)
                    ->orWhereHas('versions.audiences', fn ($aq) => $aq->whereIn('class_id', $classIds))
                    ->orWhereHas('versions.audiences', fn ($aq) => $aq->whereIn('course_id', $ownedCourseIds));
            });
        }

        $query
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->query('class_id'), fn ($q, $v) => $q->whereHas('versions.audiences', fn ($aq) => $aq->where('class_id', $v)))
            ->when($request->query('course_id'), fn ($q, $v) => $q->whereHas('versions.audiences', fn ($aq) => $aq->where('course_id', $v)));

        $assessments = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $assessments->items(),
            'meta' => [
                'total' => $assessments->total(),
                'current_page' => $assessments->currentPage(),
                'last_page' => $assessments->lastPage(),
            ],
        ]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/GetAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Http\Request;

class GetAssessmentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');

        if ($activeRole !== 'admin') {
            $classIds = $request->user()->teacherProfile?->activeClassIds() ?? collect();
            $teacherId = $request->user()->teacherProfile?->id;
            $ownedCourseIds = $teacherId ? Course::where('owner_teacher_id', $teacherId)->pluck('id') : collect();

            $inScope = $assessment->created_by_user_id === $request->user()->id
                || $assessment->versions()->whereHas('audiences', fn ($q) => $q->whereIn('class_id', $classIds))->exists()
                || $assessment->versions()->whereHas('audiences', fn ($q) => $q->whereIn('course_id', $ownedCourseIds))->exists();
            abort_unless($inScope, 404);
        }

        $assessment->load('currentVersion.questions');

        return response()->json(['assessment' => [
            'id' => $assessment->id,
            'type' => $assessment->type,
            'status' => $assessment->status,
            'created_by_user_id' => $assessment->created_by_user_id,
            'current_version' => $assessment->currentVersion,
        ]]);
    }
}
```

- [ ] **Step 4: Wire the routes**

```php
Route::middleware('role:admin,teacher')->group(function () {
    Route::get('/assessments', \App\Http\Controllers\Api\Assessment\ListAssessmentsController::class);
    Route::get('/assessments/{assessment}', \App\Http\Controllers\Api\Assessment\GetAssessmentController::class);
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=ListAndGetAssessmentTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/ListAssessmentsController.php app/Http/Controllers/Api/Assessment/GetAssessmentController.php routes/api.php tests/Feature/Assessment/ListAndGetAssessmentTest.php
git commit -m "feat: add GET /assessments list and detail, scoped to owner/class/owned-course"
```

---

## Task 11: Update Assessment draft

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/UpdateAssessmentController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/UpdateAssessmentTest.php`

**Interfaces:**
- Produces: `PATCH /api/v1/assessments/{id}` — edits `currentVersion` in place while `status=draft`; `409` once `currentVersion.status=published`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/UpdateAssessmentTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_owner_edits_draft_title(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'title' => 'Cũ', 'status' => 'draft']);
        $assessment->update(['current_version_id' => $version->id]);

        $response = $this->patchJson("/api/v1/assessments/{$assessment->id}", ['title' => 'Mới']);

        $response->assertOk();
        $this->assertDatabaseHas('assessment_versions', ['id' => $version->id, 'title' => 'Mới']);
    }

    public function test_cannot_edit_once_published(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'published']);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'published']);
        $assessment->update(['current_version_id' => $version->id]);

        $response = $this->patchJson("/api/v1/assessments/{$assessment->id}", ['title' => 'Mới']);

        $response->assertStatus(409);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=UpdateAssessmentTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controller**

```php
// app/Http/Controllers/Api/Assessment/UpdateAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;

class UpdateAssessmentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được sửa.'
        );

        $version = $assessment->currentVersion;
        abort_if($version->status !== 'draft', 409, 'Bản phát hành hiện tại đã publish, không sửa trực tiếp được.');

        $data = $request->validate([
            'title' => ['sometimes', 'string'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'opens_at' => ['sometimes', 'nullable', 'date'],
            'closes_at' => ['sometimes', 'nullable', 'date'],
            'deadline_at' => ['sometimes', 'nullable', 'date'],
            'duration_seconds' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_attempts' => ['sometimes', 'integer', 'min:1'],
            'attempt_score_policy' => ['sometimes', 'in:highest,latest'],
            'shuffle_questions' => ['sometimes', 'boolean'],
            'shuffle_options' => ['sometimes', 'boolean'],
            'passing_score' => ['sometimes', 'nullable', 'numeric'],
            'max_score' => ['sometimes', 'nullable', 'numeric'],
            'show_answers' => ['sometimes', 'boolean'],
            'show_explanations' => ['sometimes', 'boolean'],
            'show_question_scores' => ['sometimes', 'boolean'],
        ]);

        $version->fill($data);
        $version->save();

        return response()->json(['assessment' => ['id' => $assessment->id, 'current_version' => $version]]);
    }
}
```

- [ ] **Step 4: Wire the route**

```php
Route::patch('/assessments/{assessment}', \App\Http\Controllers\Api\Assessment\UpdateAssessmentController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=UpdateAssessmentTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/UpdateAssessmentController.php routes/api.php tests/Feature/Assessment/UpdateAssessmentTest.php
git commit -m "feat: add PATCH /assessments/{id} editing draft version, owner-only"
```

---

## Task 12: Assessment questions sub-resource (draft only)

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/AddAssessmentQuestionController.php`
- Create: `app/Http/Controllers/Api/Assessment/UpdateAssessmentQuestionController.php`
- Create: `app/Http/Controllers/Api/Assessment/RemoveAssessmentQuestionController.php`
- Modify: `routes/api.php` (add `assessmentQuestion` to UUID pattern list)
- Test: `tests/Feature/Assessment/AssessmentQuestionsTest.php`

**Interfaces:**
- Consumes: `Question::currentVersion` (Task 1) for the content shown when adding; the actual `snapshot` freeze happens later, in Task 13 (Publish) — rows added here reference `source_question_version_id` live, no snapshot yet (`snapshot` column left as a lightweight placeholder of the current prompt so `GET /assessments/{id}` has something to show pre-publish; Task 13 overwrites it at publish time).

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/AssessmentQuestionsTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    private function draftAssessment(User $teacher): Assessment
    {
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'draft']);
        $assessment->update(['current_version_id' => $version->id]);

        return $assessment;
    }

    public function test_add_question_auto_appends_position(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftAssessment($teacher);
        $question = Question::factory()->create();
        $qv = QuestionVersion::factory()->create(['question_id' => $question->id, 'question_type' => 'short_answer', 'prompt' => 'Hỏi gì']);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/questions", [
            'question_version_id' => $qv->id,
            'points' => 5,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('assessment_questions', [
            'assessment_version_id' => $assessment->currentVersion->id,
            'source_question_version_id' => $qv->id,
            'position' => 1,
            'points' => 5,
        ]);
    }

    public function test_update_points_and_remove_question(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftAssessment($teacher);
        $aq = AssessmentQuestion::factory()->create(['assessment_version_id' => $assessment->currentVersion->id, 'points' => 5]);

        $update = $this->patchJson("/api/v1/assessments/{$assessment->id}/questions/{$aq->id}", ['points' => 8]);
        $update->assertOk();
        $this->assertDatabaseHas('assessment_questions', ['id' => $aq->id, 'points' => 8]);

        $delete = $this->deleteJson("/api/v1/assessments/{$assessment->id}/questions/{$aq->id}");
        $delete->assertOk();
        $this->assertDatabaseMissing('assessment_questions', ['id' => $aq->id]);
    }

    public function test_cannot_modify_questions_once_published(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftAssessment($teacher);
        $assessment->currentVersion->update(['status' => 'published']);
        $question = Question::factory()->create();
        $qv = QuestionVersion::factory()->create(['question_id' => $question->id]);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/questions", ['question_version_id' => $qv->id, 'points' => 5]);

        $response->assertStatus(409);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=AssessmentQuestionsTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controllers**

```php
// app/Http/Controllers/Api/Assessment/AddAssessmentQuestionController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\QuestionVersion;
use Illuminate\Http\Request;

class AddAssessmentQuestionController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được sửa.'
        );

        $version = $assessment->currentVersion;
        abort_if($version->status !== 'draft', 409, 'Chỉ thêm câu hỏi khi bản phát hành còn draft.');

        $data = $request->validate([
            'question_version_id' => ['required', 'uuid', 'exists:question_versions,id'],
            'points' => ['required', 'numeric', 'min:0'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        $sourceVersion = QuestionVersion::findOrFail($data['question_version_id']);
        $nextPosition = $data['position'] ?? ($version->questions()->max('position') + 1);

        $assessmentQuestion = AssessmentQuestion::create([
            'assessment_version_id' => $version->id,
            'source_question_version_id' => $sourceVersion->id,
            'position' => $nextPosition,
            'points' => $data['points'],
            'question_type' => $sourceVersion->question_type,
            'snapshot' => ['prompt' => $sourceVersion->prompt],
            'created_at' => now(),
        ]);

        return response()->json(['assessment_question' => $assessmentQuestion], 201);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/UpdateAssessmentQuestionController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;

class UpdateAssessmentQuestionController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment, AssessmentQuestion $assessmentQuestion)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được sửa.'
        );

        $version = $assessment->currentVersion;
        abort_if($version->status !== 'draft', 409, 'Chỉ sửa câu hỏi khi bản phát hành còn draft.');
        abort_unless($assessmentQuestion->assessment_version_id === $version->id, 404);

        $data = $request->validate([
            'points' => ['sometimes', 'numeric', 'min:0'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ]);

        $assessmentQuestion->fill($data);
        $assessmentQuestion->save();

        return response()->json(['assessment_question' => $assessmentQuestion]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/RemoveAssessmentQuestionController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;

class RemoveAssessmentQuestionController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment, AssessmentQuestion $assessmentQuestion)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được sửa.'
        );

        $version = $assessment->currentVersion;
        abort_if($version->status !== 'draft', 409, 'Chỉ bỏ câu hỏi khi bản phát hành còn draft.');
        abort_unless($assessmentQuestion->assessment_version_id === $version->id, 404);

        $assessmentQuestion->delete();

        return response()->json(['deleted' => true]);
    }
}
```

- [ ] **Step 4: Wire the routes**

Add `'assessmentQuestion'` to the `Route::pattern()` UUID loop, then:

```php
Route::post('/assessments/{assessment}/questions', \App\Http\Controllers\Api\Assessment\AddAssessmentQuestionController::class);
Route::patch('/assessments/{assessment}/questions/{assessmentQuestion}', \App\Http\Controllers\Api\Assessment\UpdateAssessmentQuestionController::class);
Route::delete('/assessments/{assessment}/questions/{assessmentQuestion}', \App\Http\Controllers\Api\Assessment\RemoveAssessmentQuestionController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=AssessmentQuestionsTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/AddAssessmentQuestionController.php app/Http/Controllers/Api/Assessment/UpdateAssessmentQuestionController.php app/Http/Controllers/Api/Assessment/RemoveAssessmentQuestionController.php routes/api.php tests/Feature/Assessment/AssessmentQuestionsTest.php
git commit -m "feat: add draft-only add/update/remove AssessmentQuestion endpoints"
```

---

## Task 13: Publish Assessment (freeze snapshot)

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/PublishAssessmentController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/PublishAssessmentTest.php`

**Interfaces:**
- Produces: `POST /api/v1/assessments/{id}/publish` → `version.status=published`, `published_at` set, `assessment.status=published`; each `AssessmentQuestion.snapshot` rewritten to the full frozen content (prompt, options, grading_config) pulled from `source_question_version_id` at this instant. Covers Review Focus item 1 (reject empty) and item 2 (freeze — later edits to the source `Question` never touch this row again, proven in Task 5's existing test plus the new one below).

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/PublishAssessmentTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_publish_freezes_snapshot_and_survives_later_question_edits(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'draft']);
        $assessment->update(['current_version_id' => $version->id]);

        $question = Question::factory()->create(['created_by_user_id' => $teacher->id]);
        $qv = QuestionVersion::factory()->create(['question_id' => $question->id, 'question_type' => 'single_choice', 'prompt' => 'Gốc']);
        QuestionOption::factory()->create(['question_version_id' => $qv->id, 'is_correct' => true]);
        $question->update(['current_version_id' => $qv->id]);
        AssessmentQuestion::factory()->create([
            'assessment_version_id' => $version->id,
            'source_question_version_id' => $qv->id,
            'question_type' => 'single_choice',
            'snapshot' => ['prompt' => 'Gốc'],
        ]);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/publish");

        $response->assertOk();
        $this->assertDatabaseHas('assessment_versions', ['id' => $version->id, 'status' => 'published']);
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'status' => 'published']);
        $frozenSnapshot = $version->questions()->first()->snapshot;
        $this->assertSame('Gốc', $frozenSnapshot['prompt']);

        // Teacher edits the source question AFTER publish — must not change the frozen snapshot.
        $this->patchJson("/api/v1/questions/{$question->id}", [
            'question_type' => 'single_choice', 'prompt' => 'Đã sửa', 'grading_mode' => 'automatic',
        ]);

        $version->refresh();
        $stillFrozen = $version->questions()->first()->snapshot;
        $this->assertSame('Gốc', $stillFrozen['prompt']);
    }

    public function test_publish_with_no_questions_is_rejected(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'draft']);
        $assessment->update(['current_version_id' => $version->id]);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/publish");

        $response->assertStatus(422);
        $this->assertDatabaseHas('assessment_versions', ['id' => $version->id, 'status' => 'draft']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=PublishAssessmentTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controller**

```php
// app/Http/Controllers/Api/Assessment/PublishAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\QuestionVersion;
use Illuminate\Http\Request;

class PublishAssessmentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được publish.'
        );

        $version = $assessment->currentVersion;
        abort_if($version->status !== 'draft', 409, 'Bản phát hành không còn ở trạng thái draft.');

        $questions = $version->questions;
        abort_if($questions->isEmpty(), 422, 'Không thể publish Assessment chưa có câu hỏi nào.');

        foreach ($questions as $assessmentQuestion) {
            if (! $assessmentQuestion->source_question_version_id) {
                continue;
            }
            $sourceVersion = QuestionVersion::with('options')->find($assessmentQuestion->source_question_version_id);
            if (! $sourceVersion) {
                continue;
            }
            $assessmentQuestion->update(['snapshot' => [
                'prompt' => $sourceVersion->prompt,
                'instructions' => $sourceVersion->instructions,
                'options' => $sourceVersion->options->map(fn ($o) => [
                    'content' => $o->content,
                    'is_correct' => $o->is_correct,
                ])->all(),
                'grading_config' => $sourceVersion->grading_config,
            ]]);
        }

        $version->update(['status' => 'published', 'published_at' => now()]);
        $assessment->update(['status' => 'published']);

        return response()->json(['assessment' => [
            'id' => $assessment->id,
            'status' => $assessment->status,
            'current_version' => $version->fresh('questions'),
        ]]);
    }
}
```

- [ ] **Step 4: Wire the route**

```php
Route::post('/assessments/{assessment}/publish', \App\Http\Controllers\Api\Assessment\PublishAssessmentController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=PublishAssessmentTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/PublishAssessmentController.php routes/api.php tests/Feature/Assessment/PublishAssessmentTest.php
git commit -m "feat: add POST /assessments/{id}/publish freezing question snapshots"
```

---

## Task 14: New AssessmentVersion after publish

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/CreateAssessmentVersionController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/CreateAssessmentVersionTest.php`

**Interfaces:**
- Produces: `POST /api/v1/assessments/{id}/versions` → copies policy fields + `AssessmentQuestion` rows (re-pointing to the same `source_question_version_id`, fresh non-frozen `snapshot` = current source content again, ready to edit) from current version into a new `draft` version, `version_number + 1`; `assessment.current_version_id` moves to it. `409` if current version is already `draft`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/CreateAssessmentVersionTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAssessmentVersionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_new_version_copies_policy_and_questions_from_published_version(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'published']);
        $v1 = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'version_number' => 1, 'status' => 'published', 'title' => 'V1']);
        $assessment->update(['current_version_id' => $v1->id]);
        AssessmentQuestion::factory()->create(['assessment_version_id' => $v1->id, 'points' => 5]);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/versions");

        $response->assertCreated();
        $assessment->refresh();
        $this->assertNotSame($v1->id, $assessment->current_version_id);
        $newVersion = $assessment->currentVersion;
        $this->assertSame(2, $newVersion->version_number);
        $this->assertSame('draft', $newVersion->status);
        $this->assertSame('V1', $newVersion->title);
        $this->assertCount(1, $newVersion->questions);
    }

    public function test_cannot_create_new_version_while_a_draft_is_already_open(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'draft']);
        $assessment->update(['current_version_id' => $version->id]);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/versions");

        $response->assertStatus(409);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=CreateAssessmentVersionTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controller**

```php
// app/Http/Controllers/Api/Assessment/CreateAssessmentVersionController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use Illuminate\Http\Request;

class CreateAssessmentVersionController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được tạo version mới.'
        );

        $current = $assessment->currentVersion;
        abort_if($current->status === 'draft', 409, 'Đã có bản draft đang mở.');

        $fields = $current->only([
            'title', 'instructions', 'opens_at', 'closes_at', 'deadline_at', 'duration_seconds',
            'max_attempts', 'attempt_score_policy', 'late_policy', 'per_student_extensions',
            'shuffle_questions', 'shuffle_options', 'passing_score', 'max_score', 'result_release_at',
            'show_answers', 'show_explanations', 'show_question_scores', 'lesson_session_id',
        ]);

        $newVersion = AssessmentVersion::create($fields + [
            'assessment_id' => $assessment->id,
            'version_number' => $current->version_number + 1,
            'status' => 'draft',
            'created_by_user_id' => $request->user()->id,
            'created_at' => now(),
        ]);

        foreach ($current->questions as $question) {
            AssessmentQuestion::create([
                'assessment_version_id' => $newVersion->id,
                'source_question_version_id' => $question->source_question_version_id,
                'position' => $question->position,
                'points' => $question->points,
                'question_type' => $question->question_type,
                'snapshot' => $question->snapshot,
                'created_at' => now(),
            ]);
        }

        $assessment->update(['current_version_id' => $newVersion->id]);

        return response()->json(['assessment' => [
            'id' => $assessment->id,
            'current_version' => $newVersion->load('questions'),
        ]], 201);
    }
}
```

- [ ] **Step 4: Wire the route**

```php
Route::post('/assessments/{assessment}/versions', \App\Http\Controllers\Api\Assessment\CreateAssessmentVersionController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=CreateAssessmentVersionTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/CreateAssessmentVersionController.php routes/api.php tests/Feature/Assessment/CreateAssessmentVersionTest.php
git commit -m "feat: add POST /assessments/{id}/versions creating draft copy after publish"
```

---

## Task 15: Audience assign + list (Class / Student / Course), with StudentAssessment dedupe

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/AddAssessmentAudienceController.php`
- Create: `app/Http/Controllers/Api/Assessment/ListAssessmentAudienceController.php`
- Create: `app/Support/StudentAssessmentSync.php`
- Modify: `routes/api.php` (add `audience` to UUID pattern list)
- Test: `tests/Feature/Assessment/AssessmentAudienceTest.php`

**Interfaces:**
- Consumes: `Course` model (existing, Academic module), `CourseEnrollment` model (existing, Academic module — confirm exact column names per Setup caveat; this plan assumes `course_id`, `student_id`, `status` enum including `active`, matching `database/schema.md`).
- Produces: `StudentAssessmentSync::ensureForStudent(Assessment $assessment, string $studentId, AssessmentAudience $audience): void` — called by this task and reused unchanged by Task 16 (ending audience). `POST /api/v1/assessments/{id}/audience` body with **exactly one** of `class_id` / `student_id` / `course_id`. `GET /api/v1/assessments/{id}/audience`.
- Covers Review Focus item 3 (same Student via two audience paths → exactly one `StudentAssessment` row) across all three path combinations, not just Class+Student.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/AssessmentAudienceTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\ClassMember;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentAudienceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    private function draftFor(User $teacher): Assessment
    {
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $assessment->update(['current_version_id' => $version->id]);

        return $assessment;
    }

    public function test_assigning_a_class_creates_one_student_assessment_per_member(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftFor($teacher);

        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();
        ClassMember::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay()]);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['class_id' => $class->id]);

        $response->assertCreated();
        $this->assertDatabaseHas('student_assessments', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'status' => 'assigned', 'source_class_id' => $class->id]);
    }

    public function test_assigning_a_course_creates_one_student_assessment_per_enrollment(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftFor($teacher);

        $course = Course::factory()->create(['owner_teacher_id' => null]);
        $student = Student::factory()->create();
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay(), 'enrolled_by_user_id' => $teacher->id]);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['course_id' => $course->id]);

        $response->assertCreated();
        $this->assertDatabaseHas('student_assessments', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'status' => 'assigned', 'source_course_id' => $course->id]);
    }

    public function test_student_assigned_via_class_and_course_gets_exactly_one_student_assessment(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftFor($teacher);

        $class = SchoolClass::factory()->create();
        $course = Course::factory()->create(['owner_teacher_id' => null]);
        $student = Student::factory()->create();
        ClassMember::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay()]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay(), 'enrolled_by_user_id' => $teacher->id]);

        $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['class_id' => $class->id])->assertCreated();
        $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['course_id' => $course->id])->assertCreated();

        $this->assertSame(1, StudentAssessment::where('assessment_id', $assessment->id)->where('student_id', $student->id)->count());
    }

    public function test_rejects_more_than_one_target_in_one_call(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftFor($teacher);
        $class = SchoolClass::factory()->create();
        $student = Student::factory()->create();

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['class_id' => $class->id, 'student_id' => $student->id]);

        $response->assertStatus(422);
    }

    public function test_rejects_zero_targets(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = $this->draftFor($teacher);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/audience", []);

        $response->assertStatus(422);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=AssessmentAudienceTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the sync helper**

```php
// app/Support/StudentAssessmentSync.php
<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentAudience;
use App\Models\StudentAssessment;

class StudentAssessmentSync
{
    public static function ensureForStudent(Assessment $assessment, string $studentId, AssessmentAudience $audience): void
    {
        $existing = StudentAssessment::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existing) {
            if ($existing->status === 'withdrawn') {
                $existing->update([
                    'status' => 'assigned',
                    'source_audience_id' => $audience->id,
                    'source_class_id' => $audience->class_id ?? $existing->source_class_id,
                    'source_course_id' => $audience->course_id ?? $existing->source_course_id,
                ]);
            }

            return;
        }

        StudentAssessment::create([
            'assessment_id' => $assessment->id,
            'student_id' => $studentId,
            'assigned_version_id' => $assessment->current_version_id,
            'source_class_id' => $audience->class_id,
            'source_course_id' => $audience->course_id,
            'source_audience_id' => $audience->id,
            'status' => 'assigned',
        ]);
    }
}
```

- [ ] **Step 4: Write the controllers**

```php
// app/Http/Controllers/Api/Assessment/AddAssessmentAudienceController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAudience;
use App\Models\ClassMember;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Support\StudentAssessmentSync;
use Illuminate\Http\Request;

class AddAssessmentAudienceController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được giao bài.'
        );

        $data = $request->validate([
            'class_id' => ['nullable', 'uuid', 'exists:classes,id'],
            'student_id' => ['nullable', 'uuid', 'exists:students,id'],
            'course_id' => ['nullable', 'uuid', 'exists:courses,id'],
        ]);

        $targets = array_filter([$data['class_id'] ?? null, $data['student_id'] ?? null, $data['course_id'] ?? null]);
        abort_unless(count($targets) === 1, 422, 'Chọn đúng một trong class_id, student_id, hoặc course_id.');

        if (! empty($data['course_id']) && $activeRole !== 'admin') {
            $course = Course::findOrFail($data['course_id']);
            $teacherId = $request->user()->teacherProfile?->id;
            abort_unless(
                $course->owner_teacher_id === null || $course->owner_teacher_id === $teacherId,
                403,
                'Course này không thuộc quyền sử dụng của bạn.'
            );
        }

        $audience = AssessmentAudience::create([
            'assessment_version_id' => $assessment->current_version_id,
            'class_id' => $data['class_id'] ?? null,
            'student_id' => $data['student_id'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'valid_from' => now(),
            'created_at' => now(),
        ]);

        if (! empty($data['student_id'])) {
            StudentAssessmentSync::ensureForStudent($assessment, $data['student_id'], $audience);
        } elseif (! empty($data['class_id'])) {
            $studentIds = ClassMember::where('class_id', $data['class_id'])
                ->where('status', 'active')
                ->pluck('student_id');
            foreach ($studentIds as $studentId) {
                StudentAssessmentSync::ensureForStudent($assessment, $studentId, $audience);
            }
        } else {
            $studentIds = CourseEnrollment::where('course_id', $data['course_id'])
                ->where('status', 'active')
                ->pluck('student_id');
            foreach ($studentIds as $studentId) {
                StudentAssessmentSync::ensureForStudent($assessment, $studentId, $audience);
            }
        }

        return response()->json(['audience' => $audience], 201);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/ListAssessmentAudienceController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;

class ListAssessmentAudienceController extends Controller
{
    public function __invoke(Assessment $assessment)
    {
        $audiences = \App\Models\AssessmentAudience::whereIn(
            'assessment_version_id',
            $assessment->versions()->pluck('id')
        )->orderByDesc('created_at')->get();

        return response()->json(['data' => $audiences]);
    }
}
```

- [ ] **Step 5: Wire the routes**

Add `'audience'` to the `Route::pattern()` UUID loop, then:

```php
Route::post('/assessments/{assessment}/audience', \App\Http\Controllers\Api\Assessment\AddAssessmentAudienceController::class);
Route::get('/assessments/{assessment}/audience', \App\Http\Controllers\Api\Assessment\ListAssessmentAudienceController::class);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=AssessmentAudienceTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/AddAssessmentAudienceController.php app/Http/Controllers/Api/Assessment/ListAssessmentAudienceController.php app/Support/StudentAssessmentSync.php routes/api.php tests/Feature/Assessment/AssessmentAudienceTest.php
git commit -m "feat: add POST/GET /assessments/{id}/audience (Class/Student/Course) with StudentAssessment dedupe"
```

---

## Task 16: End audience, withdraw StudentAssessment only when no live path remains

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/EndAssessmentAudienceController.php`
- Modify: `app/Support/StudentAssessmentSync.php` (add `hasLivePath` + `withdrawIfNoLivePath`)
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/EndAssessmentAudienceTest.php`

**Interfaces:**
- Consumes: `StudentAssessmentSync` (Task 15).
- Produces: `DELETE /api/v1/assessments/{id}/audience/{audience_id}` → ends that one audience row (`valid_to = now`), then calls `StudentAssessmentSync::withdrawIfNoLivePath(Assessment $assessment, string $studentId): void`. `hasLivePath` now checks all three paths: direct Student audience, Class audience + active `ClassMember`, Course audience + active `CourseEnrollment`. Covers Review Focus item 5 across all three.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/EndAssessmentAudienceTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentAudience;
use App\Models\AssessmentVersion;
use App\Models\ClassMember;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndAssessmentAudienceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_ending_only_audience_path_withdraws_student_assessment(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $assessment->update(['current_version_id' => $version->id]);
        $student = Student::factory()->create();

        $audienceResponse = $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['student_id' => $student->id]);
        $audienceId = $audienceResponse->json('audience.id');

        $response = $this->deleteJson("/api/v1/assessments/{$assessment->id}/audience/{$audienceId}");

        $response->assertOk();
        $this->assertDatabaseHas('student_assessments', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'status' => 'withdrawn']);
        $this->assertNotNull(AssessmentAudience::find($audienceId)->valid_to);
    }

    public function test_ending_class_path_keeps_assigned_while_course_path_still_live(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $assessment->update(['current_version_id' => $version->id]);

        $class = SchoolClass::factory()->create();
        $course = Course::factory()->create(['owner_teacher_id' => null]);
        $student = Student::factory()->create();
        ClassMember::create(['class_id' => $class->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay()]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay(), 'enrolled_by_user_id' => $teacher->id]);

        $classAudience = $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['class_id' => $class->id]);
        $this->postJson("/api/v1/assessments/{$assessment->id}/audience", ['course_id' => $course->id]);

        $response = $this->deleteJson("/api/v1/assessments/{$assessment->id}/audience/{$classAudience->json('audience.id')}");

        $response->assertOk();
        $this->assertDatabaseHas('student_assessments', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'status' => 'assigned']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=EndAssessmentAudienceTest`
Expected: FAIL with 404.

- [ ] **Step 3: Extend the sync helper**

```php
// app/Support/StudentAssessmentSync.php — add these two methods inside the existing class (Task 15 already created the file and the `ensureForStudent` method; keep it, add below it)
    public static function hasLivePath(Assessment $assessment, string $studentId): bool
    {
        $versionIds = $assessment->versions()->pluck('id');

        $liveDirect = AssessmentAudience::whereIn('assessment_version_id', $versionIds)
            ->where('student_id', $studentId)
            ->whereNull('valid_to')
            ->exists();
        if ($liveDirect) {
            return true;
        }

        $liveClassIds = AssessmentAudience::whereIn('assessment_version_id', $versionIds)
            ->whereNotNull('class_id')
            ->whereNull('valid_to')
            ->pluck('class_id');
        if ($liveClassIds->isNotEmpty() && \App\Models\ClassMember::where('student_id', $studentId)
            ->whereIn('class_id', $liveClassIds)
            ->where('status', 'active')
            ->exists()) {
            return true;
        }

        $liveCourseIds = AssessmentAudience::whereIn('assessment_version_id', $versionIds)
            ->whereNotNull('course_id')
            ->whereNull('valid_to')
            ->pluck('course_id');
        if ($liveCourseIds->isEmpty()) {
            return false;
        }

        return \App\Models\CourseEnrollment::where('student_id', $studentId)
            ->whereIn('course_id', $liveCourseIds)
            ->where('status', 'active')
            ->exists();
    }

    public static function withdrawIfNoLivePath(Assessment $assessment, string $studentId): void
    {
        if (self::hasLivePath($assessment, $studentId)) {
            return;
        }

        StudentAssessment::where('assessment_id', $assessment->id)
            ->where('student_id', $studentId)
            ->update(['status' => 'withdrawn']);
    }
```

- [ ] **Step 4: Write the controller**

```php
// app/Http/Controllers/Api/Assessment/EndAssessmentAudienceController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAudience;
use App\Models\ClassMember;
use App\Models\CourseEnrollment;
use App\Support\StudentAssessmentSync;
use Illuminate\Http\Request;

class EndAssessmentAudienceController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment, AssessmentAudience $audience)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được rút lại audience.'
        );
        abort_unless(
            $assessment->versions()->pluck('id')->contains($audience->assessment_version_id),
            404
        );

        $audience->update(['valid_to' => now()]);

        if ($audience->student_id) {
            StudentAssessmentSync::withdrawIfNoLivePath($assessment, $audience->student_id);
        } elseif ($audience->class_id) {
            $studentIds = ClassMember::where('class_id', $audience->class_id)->where('status', 'active')->pluck('student_id');
            foreach ($studentIds as $studentId) {
                StudentAssessmentSync::withdrawIfNoLivePath($assessment, $studentId);
            }
        } elseif ($audience->course_id) {
            $studentIds = CourseEnrollment::where('course_id', $audience->course_id)->where('status', 'active')->pluck('student_id');
            foreach ($studentIds as $studentId) {
                StudentAssessmentSync::withdrawIfNoLivePath($assessment, $studentId);
            }
        }

        return response()->json(['audience' => $audience]);
    }
}
```

- [ ] **Step 5: Wire the route**

```php
Route::delete('/assessments/{assessment}/audience/{audience}', \App\Http\Controllers\Api\Assessment\EndAssessmentAudienceController::class);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=EndAssessmentAudienceTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/EndAssessmentAudienceController.php app/Support/StudentAssessmentSync.php routes/api.php tests/Feature/Assessment/EndAssessmentAudienceTest.php
git commit -m "feat: add DELETE /assessments/{id}/audience/{id}, withdraw only if no live path (Class/Student/Course) remains"
```

---

## Task 17: StudentAssessment list + per-student override

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/ListAssessmentStudentsController.php`
- Create: `app/Http/Controllers/Api/Assessment/UpdateAssessmentStudentController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/AssessmentStudentsTest.php`

**Interfaces:**
- Produces: `GET /api/v1/assessments/{id}/students`, `PATCH /api/v1/assessments/{id}/students/{student_id}` body `{ "individual_deadline_at"?, "attempt_limit_override"? }`.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/AssessmentStudentsTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentStudentsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_list_student_assessments(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $student = Student::factory()->create();
        StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $student->id]);

        $response = $this->getJson("/api/v1/assessments/{$assessment->id}/students");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_override_individual_deadline(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id]);
        $student = Student::factory()->create();
        StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $student->id]);

        $response = $this->patchJson("/api/v1/assessments/{$assessment->id}/students/{$student->id}", [
            'individual_deadline_at' => now()->addDays(3)->toISOString(),
            'attempt_limit_override' => 3,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('student_assessments', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'attempt_limit_override' => 3]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=AssessmentStudentsTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controllers**

```php
// app/Http/Controllers/Api/Assessment/ListAssessmentStudentsController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;

class ListAssessmentStudentsController extends Controller
{
    public function __invoke(Assessment $assessment)
    {
        return response()->json(['data' => $assessment->studentAssessments()->orderBy('created_at')->get()]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/UpdateAssessmentStudentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;

class UpdateAssessmentStudentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment, string $studentId)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được sửa.'
        );

        $studentAssessment = $assessment->studentAssessments()->where('student_id', $studentId)->firstOrFail();

        $data = $request->validate([
            'individual_deadline_at' => ['sometimes', 'nullable', 'date'],
            'attempt_limit_override' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $studentAssessment->fill($data);
        $studentAssessment->save();

        return response()->json(['student_assessment' => $studentAssessment]);
    }
}
```

- [ ] **Step 4: Wire the routes**

```php
Route::get('/assessments/{assessment}/students', \App\Http\Controllers\Api\Assessment\ListAssessmentStudentsController::class);
Route::patch('/assessments/{assessment}/students/{studentId}', \App\Http\Controllers\Api\Assessment\UpdateAssessmentStudentController::class)
    ->whereUuid('studentId');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=AssessmentStudentsTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/ListAssessmentStudentsController.php app/Http/Controllers/Api/Assessment/UpdateAssessmentStudentController.php routes/api.php tests/Feature/Assessment/AssessmentStudentsTest.php
git commit -m "feat: add GET /assessments/{id}/students and per-student override"
```

---

## Task 18: Close / Reopen / Cancel

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/CloseAssessmentController.php`
- Create: `app/Http/Controllers/Api/Assessment/ReopenAssessmentController.php`
- Create: `app/Http/Controllers/Api/Assessment/CancelAssessmentController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/CloseReopenCancelTest.php`

**Interfaces:**
- Produces: `POST /assessments/{id}/close`, `POST /assessments/{id}/reopen` (**exactly one** of `student_ids[]` / `class_id` / `course_id`, required), `POST /assessments/{id}/cancel` (`reason` required).

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/CloseReopenCancelTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloseReopenCancelTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTeacher(): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'teacher']);

        return $user;
    }

    public function test_close_sets_status_closed(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'published']);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/close");

        $response->assertOk();
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'status' => 'closed']);
    }

    public function test_reopen_requires_exactly_one_target_and_resets_named_students(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'closed']);
        $student = Student::factory()->create();
        $sa = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'status' => 'withdrawn']);

        $missingTarget = $this->postJson("/api/v1/assessments/{$assessment->id}/reopen");
        $missingTarget->assertStatus(422);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/reopen", ['student_ids' => [$student->id]]);

        $response->assertOk();
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'status' => 'published']);
        $this->assertDatabaseHas('student_assessments', ['id' => $sa->id, 'status' => 'assigned']);
    }

    public function test_reopen_by_course_resets_all_enrolled_students(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'closed']);
        $course = Course::factory()->create(['owner_teacher_id' => null]);
        $student = Student::factory()->create();
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active', 'valid_from' => now()->subDay(), 'enrolled_by_user_id' => $teacher->id]);
        $sa = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'status' => 'withdrawn']);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/reopen", ['course_id' => $course->id]);

        $response->assertOk();
        $this->assertDatabaseHas('student_assessments', ['id' => $sa->id, 'status' => 'assigned']);
    }

    public function test_reopen_rejects_more_than_one_target(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'closed']);
        $course = Course::factory()->create();
        $student = Student::factory()->create();

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/reopen", ['student_ids' => [$student->id], 'course_id' => $course->id]);

        $response->assertStatus(422);
    }

    public function test_cannot_reopen_a_cancelled_assessment(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'cancelled']);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/reopen", ['student_ids' => ['00000000-0000-0000-0000-000000000000']]);

        $response->assertStatus(409);
    }

    public function test_cancel_requires_reason(): void
    {
        $teacher = $this->actingAsTeacher();
        $assessment = Assessment::factory()->create(['created_by_user_id' => $teacher->id, 'status' => 'published']);

        $missingReason = $this->postJson("/api/v1/assessments/{$assessment->id}/cancel");
        $missingReason->assertStatus(422);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/cancel", ['reason' => 'Trùng lịch với hoạt động trường']);

        $response->assertOk();
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'status' => 'cancelled']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=CloseReopenCancelTest`
Expected: FAIL with 404.

- [ ] **Step 3: Write the controllers**

```php
// app/Http/Controllers/Api/Assessment/CloseAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;

class CloseAssessmentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được đóng bài.'
        );

        $assessment->update(['status' => 'closed']);

        return response()->json(['assessment' => ['id' => $assessment->id, 'status' => $assessment->status]]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/ReopenAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassMember;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;

class ReopenAssessmentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được mở lại bài.'
        );
        abort_if(in_array($assessment->status, ['cancelled', 'archived'], true), 409, 'Không thể mở lại Assessment đã hủy/lưu trữ.');

        $data = $request->validate([
            'student_ids' => ['sometimes', 'array'],
            'student_ids.*' => ['uuid', 'exists:students,id'],
            'class_id' => ['sometimes', 'uuid', 'exists:classes,id'],
            'course_id' => ['sometimes', 'uuid', 'exists:courses,id'],
        ]);

        $targets = array_filter([
            isset($data['student_ids']) && $data['student_ids'] !== [] ? $data['student_ids'] : null,
            $data['class_id'] ?? null,
            $data['course_id'] ?? null,
        ]);
        abort_unless(count($targets) === 1, 422, 'Chọn đúng một trong student_ids, class_id, hoặc course_id.');

        if (! empty($data['class_id'])) {
            $studentIds = ClassMember::where('class_id', $data['class_id'])->where('status', 'active')->pluck('student_id')->all();
        } elseif (! empty($data['course_id'])) {
            $studentIds = CourseEnrollment::where('course_id', $data['course_id'])->where('status', 'active')->pluck('student_id')->all();
        } else {
            $studentIds = $data['student_ids'];
        }

        $assessment->studentAssessments()->whereIn('student_id', $studentIds)->update(['status' => 'assigned']);

        if ($assessment->status === 'closed') {
            $assessment->update(['status' => 'published']);
        }

        return response()->json(['assessment' => ['id' => $assessment->id, 'status' => $assessment->status]]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/CancelAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;

class CancelAssessmentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $activeRole = $request->session()->get('active_role_code');
        abort_unless(
            $activeRole === 'admin' || $assessment->created_by_user_id === $request->user()->id,
            403,
            'Chỉ người tạo Assessment hoặc Admin được hủy bài.'
        );

        $data = $request->validate(['reason' => ['required', 'string']]);

        $assessment->currentVersion?->update(['cancellation_reason' => $data['reason']]);
        $assessment->update(['status' => 'cancelled']);

        return response()->json(['assessment' => ['id' => $assessment->id, 'status' => $assessment->status]]);
    }
}
```

- [ ] **Step 4: Wire the routes**

```php
Route::post('/assessments/{assessment}/close', \App\Http\Controllers\Api\Assessment\CloseAssessmentController::class);
Route::post('/assessments/{assessment}/reopen', \App\Http\Controllers\Api\Assessment\ReopenAssessmentController::class);
Route::post('/assessments/{assessment}/cancel', \App\Http\Controllers\Api\Assessment\CancelAssessmentController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=CloseReopenCancelTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/CloseAssessmentController.php app/Http/Controllers/Api/Assessment/ReopenAssessmentController.php app/Http/Controllers/Api/Assessment/CancelAssessmentController.php routes/api.php tests/Feature/Assessment/CloseReopenCancelTest.php
git commit -m "feat: add close/reopen (student_ids/class_id/course_id)/cancel Assessment actions"
```

---

## Task 19: Student/Parent metadata view

**Files:**
- Create: `app/Http/Controllers/Api/Assessment/ListMyAssessmentsController.php`
- Create: `app/Http/Controllers/Api/Assessment/GetMyAssessmentController.php`
- Create: `app/Http/Controllers/Api/Assessment/ListChildAssessmentsController.php`
- Create: `app/Http/Controllers/Api/Assessment/GetChildAssessmentController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Assessment/StudentParentAssessmentViewTest.php`

**Interfaces:**
- Produces: `GET /api/v1/me/assessments`, `GET /api/v1/me/assessments/{id}`, `GET /api/v1/me/children/{student_id}/assessments`, `GET /api/v1/me/children/{student_id}/assessments/{assessment_id}` — metadata only (`title`, `type`, `opens_at/closes_at/deadline_at`, `max_score`, `status`), never `AssessmentQuestion`/snapshot content (spec §7). Not affected by the Course audience addition — `StudentAssessment` is already the single effective-assignment source regardless of which of the three paths produced it, so this task's query (`where student_id = ...`) needs no change.

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Assessment/StudentParentAssessmentViewTest.php
<?php

namespace Tests\Feature\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentParentAssessmentViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_own_assigned_assessments_metadata_only(): void
    {
        $student = Student::factory()->create();
        $user = User::factory()->create(['person_profile_id' => $student->person_profile_id]);
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $assessment = Assessment::factory()->create(['status' => 'published']);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'title' => 'Kiểm tra giữa kỳ', 'status' => 'published']);
        $assessment->update(['current_version_id' => $version->id]);
        StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'assigned_version_id' => $version->id]);

        $response = $this->getJson('/api/v1/me/assessments');

        $response->assertOk();
        $this->assertSame('Kiểm tra giữa kỳ', $response->json('data.0.title'));
        $this->assertArrayNotHasKey('questions', $response->json('data.0'));
    }

    public function test_parent_sees_only_linked_child_assessments(): void
    {
        $parentProfile = ParentProfile::factory()->create();
        $user = User::factory()->create(['person_profile_id' => $parentProfile->person_profile_id]);
        $role = Role::factory()->create(['code' => 'parent']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($user)->withSession(['active_role_code' => 'parent']);

        $child = Student::factory()->create();
        ParentStudentRelationship::create([
            'parent_id' => $parentProfile->id, 'student_id' => $child->id, 'status' => 'active',
            'valid_from' => now()->subDay(), 'confirmed_by_user_id' => $user->id, 'confirmed_at' => now(),
        ]);
        $notMyChild = Student::factory()->create();

        $assessment = Assessment::factory()->create(['status' => 'published']);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'published']);
        $assessment->update(['current_version_id' => $version->id]);
        StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $child->id]);

        $ok = $this->getJson("/api/v1/me/children/{$child->id}/assessments");
        $ok->assertOk();
        $this->assertCount(1, $ok->json('data'));

        $forbidden = $this->getJson("/api/v1/me/children/{$notMyChild->id}/assessments");
        $forbidden->assertStatus(404);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -u www-data api php artisan test --filter=StudentParentAssessmentViewTest`
Expected: FAIL with 404 route-not-found (before controllers exist).

- [ ] **Step 3: Write a shared metadata shaper and the controllers**

```php
// app/Http/Controllers/Api/Assessment/ListMyAssessmentsController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ListMyAssessmentsController extends Controller
{
    public function __invoke(Request $request)
    {
        $studentId = $request->user()->studentProfile->id;

        return response()->json(['data' => self::metadataFor($studentId)]);
    }

    public static function metadataFor(string $studentId)
    {
        return \App\Models\StudentAssessment::where('student_id', $studentId)
            ->with('assignedVersion')
            ->get()
            ->map(fn ($sa) => [
                'id' => $sa->assessment_id,
                'title' => $sa->assignedVersion?->title,
                'type' => $sa->assessment->type,
                'opens_at' => $sa->assignedVersion?->opens_at,
                'closes_at' => $sa->assignedVersion?->closes_at,
                'deadline_at' => $sa->assignedVersion?->deadline_at,
                'max_score' => $sa->assignedVersion?->max_score,
                'status' => $sa->status,
            ]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/GetMyAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\StudentAssessment;
use Illuminate\Http\Request;

class GetMyAssessmentController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $studentId = $request->user()->studentProfile->id;
        $sa = StudentAssessment::where('assessment_id', $assessment->id)->where('student_id', $studentId)->first();
        abort_unless($sa, 404);

        return response()->json(['assessment' => ListMyAssessmentsController::metadataFor($studentId)->firstWhere('id', $assessment->id)]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/ListChildAssessmentsController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\Request;

class ListChildAssessmentsController extends Controller
{
    public function __invoke(Request $request, string $studentId)
    {
        $parentId = $request->user()->parentProfile->id;
        $linked = ParentStudentRelationship::where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->exists();
        abort_unless($linked, 404);

        return response()->json(['data' => ListMyAssessmentsController::metadataFor($studentId)]);
    }
}
```

```php
// app/Http/Controllers/Api/Assessment/GetChildAssessmentController.php
<?php

namespace App\Http\Controllers\Api\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ParentStudentRelationship;
use App\Models\StudentAssessment;
use Illuminate\Http\Request;

class GetChildAssessmentController extends Controller
{
    public function __invoke(Request $request, string $studentId, Assessment $assessment)
    {
        $parentId = $request->user()->parentProfile->id;
        $linked = ParentStudentRelationship::where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->exists();
        abort_unless($linked, 404);

        $sa = StudentAssessment::where('assessment_id', $assessment->id)->where('student_id', $studentId)->first();
        abort_unless($sa, 404);

        return response()->json(['assessment' => ListMyAssessmentsController::metadataFor($studentId)->firstWhere('id', $assessment->id)]);
    }
}
```

- [ ] **Step 4: Wire the routes**

```php
Route::middleware('role:student')->group(function () {
    Route::get('/me/assessments', \App\Http\Controllers\Api\Assessment\ListMyAssessmentsController::class);
    Route::get('/me/assessments/{assessment}', \App\Http\Controllers\Api\Assessment\GetMyAssessmentController::class);
});

Route::middleware('role:parent')->group(function () {
    Route::get('/me/children/{studentId}/assessments', \App\Http\Controllers\Api\Assessment\ListChildAssessmentsController::class)->whereUuid('studentId');
    Route::get('/me/children/{studentId}/assessments/{assessment}', \App\Http\Controllers\Api\Assessment\GetChildAssessmentController::class)->whereUuid('studentId');
});
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -u www-data api php artisan test --filter=StudentParentAssessmentViewTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Assessment/ListMyAssessmentsController.php app/Http/Controllers/Api/Assessment/GetMyAssessmentController.php app/Http/Controllers/Api/Assessment/ListChildAssessmentsController.php app/Http/Controllers/Api/Assessment/GetChildAssessmentController.php routes/api.php tests/Feature/Assessment/StudentParentAssessmentViewTest.php
git commit -m "feat: add Student/Parent metadata-only assessment view endpoints"
```

---

## Final step: full test run + PR

- [ ] **Step 1: Run the whole suite**

Run (from `piclass-infra/local`): `make test-api`
Expected: all existing tests (Auth, User Management, Academic/Lesson/Attendance) plus every test added in this plan pass — no regression in prior modules.

- [ ] **Step 2: Push and open PR**

After the final commit (Task 19) and a clean full-suite run, push the branch created in "Setup: branch" and open a PR against `main`:

```bash
git push -u origin feature/question-bank-assessment-backend
gh pr create --title "Question Bank + Assessment backend (Plan 4)" --body "Implements docs/archive/superpowers/specs/2026-10-15-question-bank-assessment-api-design.md"
```

**Before creating the PR, `git fetch origin` and confirm the base branch is `origin/main` at its current tip** — Module 2's PR history (`docs/progress.md`, "Sự cố merge 2026-10-15") records a real incident where a stale local branch cache caused a PR to target the wrong base. Don't repeat it.

- [ ] **Step 3: Update `docs/progress.md`**

Back in the `piclass-web` (docs) repo, tick Module 3 BE as done in the checklist, following the same note style already used for Module 2 — this is a docs-repo change, not part of this `piclass-api` plan's commits.

---

