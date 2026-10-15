# Submission/Grading + Result/Progress — Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the Laravel 11 backend for Module 4 (Submission/Grading + Result/Progress) in `piclass-api/` — Attempt lifecycle, auto-grading engine, manual grading, Feedback, Result publication, Progress, and Admin exam reissue.

**Architecture:** New domain controllers under `App\Http\Controllers\Api\{Submission,Grading,Result}\`, new Eloquent models/migrations for 11 tables (none exist yet — Module 3 only has Assessment/Question tables), two pure service classes (`AutoGrader`, `ResultCalculator`) that encapsulate grading/result logic so controllers stay thin and the engine is unit-testable without HTTP. Grading runs synchronously in the request (no queue), matching the codebase's existing convention of inline validation/authorization and raw JSON responses (no Form Requests, no Policies, no API Resources — see Global Constraints).

**Tech Stack:** Laravel 11, PostgreSQL, Sanctum (session-based in tests via `actingAs` + `withSession`), PHPUnit (`RefreshDatabase`), existing `role:*` middleware alias.

**Spec:** `docs/superpowers/specs/2026-10-15-submission-grading-result-progress-api-design.md`

## Global Constraints

- All new models use `HasUuids` for UUID primary keys, matching every existing Module 2/3 model.
- "Snapshot" / history-style tables (`AttemptQuestion`, `Answer`'s `saved_at`-driven rows, `Submission`, `GradeHistory`, `FeedbackHistory`, `ResultHistory`, `ResultPublication`) do **not** get full `timestamps()` — follow the exact column lists in the spec/schema (`$timestamps = false` + explicit `created_at`/`changed_at` columns), matching `AssessmentQuestion`/`AssessmentVersion` convention.
- "Entity" tables with a real lifecycle (`Attempt`, `Grade`, `Feedback`, `Result`) get full `timestamps()`.
- No Form Request classes, no Policy/Gate classes, no API Resource classes — validate inline with `$request->validate([...])`, authorize inline with `abort_unless`/`abort_if`, respond with raw arrays via `response()->json([...])`. This matches every controller in `app/Http/Controllers/Api/{Assessment,QuestionBank}/`.
- Enums are plain DB string enums validated with `in:a,b,c` — no `App\Enums\*` classes exist in this codebase; don't introduce one.
- Every new UUID route parameter name must be added to the `Route::pattern(...)` loop at the top of `routes/api.php`, or a malformed id falls through to a Postgres 500 instead of a 404.
- Grading is synchronous — no queue job, no `ShouldQueue`. Decided during brainstorming to match V1 scale (1 EC2).
- Auto-publish (per `result_release_at`) is checked only at two event points (submit, and manual grade completing `grading_status`) — no scheduled job. Decided during brainstorming.

## Review Focus

- A Student calling `submit` twice on the same Attempt (double-submit / network retry) must not create two `Submission` rows or double-apply auto-grading — `submissions.attempt_id` is unique; the second call must get a clean `409`, not a DB constraint crash.
- A Teacher trying to grade/view a Submission belonging to a Class/Course they are **not** assigned to must get `404` (matching the existing "scope leaks as 404, not 403" convention seen in `ListAssessmentStudentsController`), not see the data.
- A `multiple_choice` question where the Student selects a subset of correct options plus one wrong option must score as *not fully correct* under the default (no partial-credit config) rule — a naive "any match = full credit" implementation would over-score this.
- Publishing a `Result` whose `grading_status` is still `partial`/`pending` (an essay question not yet graded) must be rejected with `409` — a careless bulk-publish could otherwise leak partial scores as final.
- A Parent whose `ParentStudentRelationship` has ended (`valid_to` in the past) must get `404` on every read endpoint in this module (`result`, `progress`), even though the `StudentAssessment`/`Result` rows still exist — unlink must take effect immediately, not just hide new data.

---

## Task 1: Migrations + Models + Factories — Attempt, AttemptQuestion, Answer, Submission

**Files:**
- Create: `database/migrations/2026_10_16_000001_create_attempts_table.php`
- Create: `database/migrations/2026_10_16_000002_create_attempt_questions_table.php`
- Create: `database/migrations/2026_10_16_000003_create_answers_table.php`
- Create: `database/migrations/2026_10_16_000004_create_submissions_table.php`
- Create: `app/Models/Attempt.php`
- Create: `app/Models/AttemptQuestion.php`
- Create: `app/Models/Answer.php`
- Create: `app/Models/Submission.php`
- Create: `database/factories/AttemptFactory.php`
- Create: `database/factories/AnswerFactory.php`
- Test: `tests/Feature/Submission/AttemptModelTest.php`

**Interfaces:**
- Produces: `Attempt::class` with fillable `student_assessment_id, assessment_version_id, attempt_number, status, started_at, ends_at, cancel_reason, replacement_attempt_id`; relations `studentAssessment()`, `assessmentVersion()`, `attemptQuestions()` (hasMany `AttemptQuestion`, ordered by `position`), `answers()` (hasMany `Answer`), `submission()` (hasOne `Submission`), `replacementAttempt()` (belongsTo `Attempt`, `replacement_attempt_id`).
- Produces: `AttemptQuestion::class` fillable `attempt_id, assessment_question_id, position`; relations `attempt()`, `assessmentQuestion()`.
- Produces: `Answer::class` fillable `attempt_id, assessment_question_id, answer_payload, saved_at, is_submitted`; cast `answer_payload` => `array`, `saved_at` => `datetime`, `is_submitted` => `boolean`; relations `attempt()`, `assessmentQuestion()`.
- Produces: `Submission::class` fillable `attempt_id, received_at, status, is_late, submitted_by_user_id`; `$timestamps = false`; cast `received_at` => `datetime`, `is_late` => `boolean`; relations `attempt()`, `submittedBy()` (belongsTo `User`), `grades()` (hasMany `Grade`), `feedback()` (hasMany `Feedback`), `result()` (hasOne `Result`).

- [ ] **Step 1: Write migration for `attempts`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_assessment_id')->constrained('student_assessments')->cascadeOnDelete();
            $table->foreignUuid('assessment_version_id')->constrained('assessment_versions')->cascadeOnDelete();
            $table->integer('attempt_number');
            $table->enum('status', ['in_progress', 'submitted', 'timed_out', 'cancelled', 'awaiting_grading', 'graded'])->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->foreignUuid('replacement_attempt_id')->nullable()->constrained('attempts')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
```

- [ ] **Step 2: Write migrations for `attempt_questions`, `answers`, `submissions`**

```php
// database/migrations/2026_10_16_000002_create_attempt_questions_table.php
Schema::create('attempt_questions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('attempt_id')->constrained('attempts')->cascadeOnDelete();
    $table->foreignUuid('assessment_question_id')->constrained('assessment_questions')->cascadeOnDelete();
    $table->integer('position');
    $table->unique(['attempt_id', 'assessment_question_id']);
});
// down(): Schema::dropIfExists('attempt_questions');
```

```php
// database/migrations/2026_10_16_000003_create_answers_table.php
Schema::create('answers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('attempt_id')->constrained('attempts')->cascadeOnDelete();
    $table->foreignUuid('assessment_question_id')->constrained('assessment_questions')->cascadeOnDelete();
    $table->json('answer_payload')->nullable();
    $table->timestamp('saved_at');
    $table->boolean('is_submitted')->default(false);
    $table->timestamps();
    $table->unique(['attempt_id', 'assessment_question_id']);
});
// down(): Schema::dropIfExists('answers');
```

```php
// database/migrations/2026_10_16_000004_create_submissions_table.php
Schema::create('submissions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('attempt_id')->unique()->constrained('attempts')->cascadeOnDelete();
    $table->timestamp('received_at');
    $table->enum('status', ['received', 'awaiting_grading', 'graded', 'invalidated'])->default('received');
    $table->boolean('is_late')->default(false);
    $table->foreignUuid('submitted_by_user_id')->constrained('users');
    $table->timestamp('created_at')->useCurrent();
});
// down(): Schema::dropIfExists('submissions');
```

Each file follows the exact `<?php ... return new class extends Migration { public function up()/down() {} };` shape used in Step 1 above (copy the wrapper, swap the body).

- [ ] **Step 3: Run migrations**

Run: `cd piclass-api && php artisan migrate`
Expected: 4 new migrations applied, no errors.

- [ ] **Step 4: Write the models**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_assessment_id', 'assessment_version_id', 'attempt_number',
        'status', 'started_at', 'ends_at', 'cancel_reason', 'replacement_attempt_id',
    ];

    protected $casts = ['started_at' => 'datetime', 'ends_at' => 'datetime'];

    public function studentAssessment()
    {
        return $this->belongsTo(StudentAssessment::class);
    }

    public function assessmentVersion()
    {
        return $this->belongsTo(AssessmentVersion::class);
    }

    public function attemptQuestions()
    {
        return $this->hasMany(AttemptQuestion::class)->orderBy('position');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function submission()
    {
        return $this->hasOne(Submission::class);
    }

    public function replacementAttempt()
    {
        return $this->belongsTo(Attempt::class, 'replacement_attempt_id');
    }

    public function isTimedOut(): bool
    {
        return $this->status === 'in_progress' && $this->ends_at !== null && $this->ends_at->isPast();
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttemptQuestion extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['attempt_id', 'assessment_question_id', 'position'];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    public function assessmentQuestion()
    {
        return $this->belongsTo(AssessmentQuestion::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['attempt_id', 'assessment_question_id', 'answer_payload', 'saved_at', 'is_submitted'];

    protected $casts = ['answer_payload' => 'array', 'saved_at' => 'datetime', 'is_submitted' => 'boolean'];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    public function assessmentQuestion()
    {
        return $this->belongsTo(AssessmentQuestion::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['attempt_id', 'received_at', 'status', 'is_late', 'submitted_by_user_id'];

    protected $casts = ['received_at' => 'datetime', 'is_late' => 'boolean', 'created_at' => 'datetime'];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function result()
    {
        return $this->hasOne(Result::class);
    }
}
```

- [ ] **Step 5: Write factories**

```php
<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\StudentAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttemptFactory extends Factory
{
    protected $model = Attempt::class;

    public function definition(): array
    {
        $sa = StudentAssessment::factory()->create();

        return [
            'student_assessment_id' => $sa->id,
            'assessment_version_id' => $sa->assigned_version_id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
        ];
    }
}
```

```php
<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\AssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition(): array
    {
        return [
            'attempt_id' => Attempt::factory(),
            'assessment_question_id' => AssessmentQuestion::factory(),
            'answer_payload' => null,
            'saved_at' => now(),
            'is_submitted' => false,
        ];
    }
}
```

- [ ] **Step 3b: Write failing test asserting relations wire up**

```php
<?php

namespace Tests\Feature\Submission;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttemptModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_relations_resolve(): void
    {
        $attempt = Attempt::factory()->create();
        $aq = AttemptQuestion::factory()->create(['attempt_id' => $attempt->id]);
        $answer = Answer::factory()->create(['attempt_id' => $attempt->id, 'assessment_question_id' => $aq->assessment_question_id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);

        $this->assertTrue($attempt->attemptQuestions->contains($aq));
        $this->assertTrue($attempt->answers->contains($answer));
        $this->assertSame($submission->id, $attempt->submission->id);
    }
}
```

`AttemptQuestion` and `Submission` also need `::factory()` support — add `use HasFactory;` (already in Step 4 models) and matching factory classes `AttemptQuestionFactory`/`SubmissionFactory` with the obvious `definition()` (mirror `AnswerFactory`'s shape: `attempt_id` from `Attempt::factory()`, `assessment_question_id` from `AssessmentQuestion::factory()`, plus `position`/`received_at`/`status`/`submitted_by_user_id` => `User::factory()`).

Run: `php artisan test --filter=AttemptModelTest`
Expected: FAIL (`Class "Database\Factories\AttemptQuestionFactory" not found` or similar).

- [ ] **Step 4b: Add the two missing factories, then run again**

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_10_16_00000{1,2,3,4}_*.php app/Models/Attempt.php app/Models/AttemptQuestion.php app/Models/Answer.php app/Models/Submission.php database/factories/Attempt*.php database/factories/AnswerFactory.php database/factories/SubmissionFactory.php tests/Feature/Submission/AttemptModelTest.php
git commit -m "feat: add Attempt/AttemptQuestion/Answer/Submission schema and models"
```

---

## Task 2: Migrations + Models + Factories — Grade, GradeHistory, Feedback, FeedbackHistory, Result, ResultHistory, ResultPublication

**Files:**
- Create: `database/migrations/2026_10_16_000005_create_grades_table.php`
- Create: `database/migrations/2026_10_16_000006_create_grade_histories_table.php`
- Create: `database/migrations/2026_10_16_000007_create_feedbacks_table.php`
- Create: `database/migrations/2026_10_16_000008_create_feedback_histories_table.php`
- Create: `database/migrations/2026_10_16_000009_create_results_table.php`
- Create: `database/migrations/2026_10_16_000010_create_result_histories_table.php`
- Create: `database/migrations/2026_10_16_000011_create_result_publications_table.php`
- Create: `app/Models/Grade.php`, `app/Models/GradeHistory.php`, `app/Models/Feedback.php`, `app/Models/FeedbackHistory.php`, `app/Models/Result.php`, `app/Models/ResultHistory.php`, `app/Models/ResultPublication.php`
- Create: `database/factories/GradeFactory.php`, `database/factories/ResultFactory.php`
- Test: `tests/Feature/Submission/GradeResultModelTest.php`

**Interfaces:**
- Consumes: `Submission::class` (Task 1).
- Produces: `Grade::class` fillable `submission_id, assessment_question_id, score, max_score, grading_method, graded_by_user_id`; relations `submission()`, `assessmentQuestion()`, `gradedBy()`, `history()` (hasMany `GradeHistory`).
- Produces: `Result::class` fillable `submission_id, score, max_score, percentage, passed, grading_status, publication_status`; relations `submission()`, `history()`, `publications()` (hasMany `ResultPublication`).
- Produces: `Feedback::class` fillable `submission_id, grade_id, assessment_question_id, author_user_id, body, visibility, status, published_at`; relations `submission()`, `grade()`, `assessmentQuestion()`, `author()`, `history()`.

- [ ] **Step 1: Write the 7 migrations**

```php
// database/migrations/2026_10_16_000005_create_grades_table.php
Schema::create('grades', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('submission_id')->constrained('submissions')->cascadeOnDelete();
    $table->foreignUuid('assessment_question_id')->nullable()->constrained('assessment_questions')->nullOnDelete();
    $table->decimal('score', 8, 2);
    $table->decimal('max_score', 8, 2);
    $table->enum('grading_method', ['automatic', 'manual']);
    $table->foreignUuid('graded_by_user_id')->nullable()->constrained('users');
    $table->timestamps();
});
// down(): Schema::dropIfExists('grades');
```

```php
// database/migrations/2026_10_16_000006_create_grade_histories_table.php
Schema::create('grade_histories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('grade_id')->constrained('grades')->cascadeOnDelete();
    $table->json('before_data');
    $table->json('after_data');
    $table->foreignUuid('changed_by_user_id')->constrained('users');
    $table->timestamp('changed_at');
    $table->string('reason');
});
// down(): Schema::dropIfExists('grade_histories');
```

```php
// database/migrations/2026_10_16_000007_create_feedbacks_table.php
Schema::create('feedbacks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('submission_id')->constrained('submissions')->cascadeOnDelete();
    $table->foreignUuid('grade_id')->nullable()->constrained('grades')->nullOnDelete();
    $table->foreignUuid('assessment_question_id')->nullable()->constrained('assessment_questions')->nullOnDelete();
    $table->foreignUuid('author_user_id')->nullable()->constrained('users');
    $table->text('body');
    $table->enum('visibility', ['private', 'learner', 'parent_if_published']);
    $table->enum('status', ['draft', 'published', 'withdrawn'])->default('draft');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
// down(): Schema::dropIfExists('feedbacks');
```

```php
// database/migrations/2026_10_16_000008_create_feedback_histories_table.php
Schema::create('feedback_histories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('feedback_id')->constrained('feedbacks')->cascadeOnDelete();
    $table->json('before_data');
    $table->json('after_data');
    $table->foreignUuid('changed_by_user_id')->constrained('users');
    $table->timestamp('changed_at');
    $table->string('reason')->nullable();
});
// down(): Schema::dropIfExists('feedback_histories');
```

```php
// database/migrations/2026_10_16_000009_create_results_table.php
Schema::create('results', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('submission_id')->unique()->constrained('submissions')->cascadeOnDelete();
    $table->decimal('score', 8, 2)->nullable();
    $table->decimal('max_score', 8, 2)->nullable();
    $table->decimal('percentage', 5, 2)->nullable();
    $table->boolean('passed')->nullable();
    $table->enum('grading_status', ['pending', 'partial', 'complete'])->default('pending');
    $table->enum('publication_status', ['unpublished', 'published'])->default('unpublished');
    $table->timestamps();
});
// down(): Schema::dropIfExists('results');
```

```php
// database/migrations/2026_10_16_000010_create_result_histories_table.php
Schema::create('result_histories', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('result_id')->constrained('results')->cascadeOnDelete();
    $table->json('before_data');
    $table->json('after_data');
    $table->foreignUuid('changed_by_user_id')->constrained('users');
    $table->timestamp('changed_at');
    $table->string('reason')->nullable();
});
// down(): Schema::dropIfExists('result_histories');
```

```php
// database/migrations/2026_10_16_000011_create_result_publications_table.php
Schema::create('result_publications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('result_id')->constrained('results')->cascadeOnDelete();
    $table->enum('action', ['published', 'withdrawn']);
    $table->timestamp('published_at');
    $table->foreignUuid('actor_user_id')->constrained('users');
    $table->timestamp('created_at')->useCurrent();
});
// down(): Schema::dropIfExists('result_publications');
```

- [ ] **Step 2: Run migrations**

Run: `php artisan migrate`
Expected: 7 new migrations applied.

- [ ] **Step 3: Write the 7 models**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['submission_id', 'assessment_question_id', 'score', 'max_score', 'grading_method', 'graded_by_user_id'];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function assessmentQuestion()
    {
        return $this->belongsTo(AssessmentQuestion::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by_user_id');
    }

    public function history()
    {
        return $this->hasMany(GradeHistory::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeHistory extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['grade_id', 'before_data', 'after_data', 'changed_by_user_id', 'changed_at', 'reason'];

    protected $casts = ['before_data' => 'array', 'after_data' => 'array', 'changed_at' => 'datetime'];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['submission_id', 'grade_id', 'assessment_question_id', 'author_user_id', 'body', 'visibility', 'status', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function assessmentQuestion()
    {
        return $this->belongsTo(AssessmentQuestion::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function history()
    {
        return $this->hasMany(FeedbackHistory::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackHistory extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['feedback_id', 'before_data', 'after_data', 'changed_by_user_id', 'changed_at', 'reason'];

    protected $casts = ['before_data' => 'array', 'after_data' => 'array', 'changed_at' => 'datetime'];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['submission_id', 'score', 'max_score', 'percentage', 'passed', 'grading_status', 'publication_status'];

    protected $casts = ['passed' => 'boolean'];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function history()
    {
        return $this->hasMany(ResultHistory::class);
    }

    public function publications()
    {
        return $this->hasMany(ResultPublication::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultHistory extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['result_id', 'before_data', 'after_data', 'changed_by_user_id', 'changed_at', 'reason'];

    protected $casts = ['before_data' => 'array', 'after_data' => 'array', 'changed_at' => 'datetime'];

    public function result()
    {
        return $this->belongsTo(Result::class);
    }
}
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPublication extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['result_id', 'action', 'published_at', 'actor_user_id'];

    protected $casts = ['published_at' => 'datetime', 'created_at' => 'datetime'];

    public function result()
    {
        return $this->belongsTo(Result::class);
    }
}
```

- [ ] **Step 4: Write factories**

```php
<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'assessment_question_id' => null,
            'score' => 10,
            'max_score' => 10,
            'grading_method' => 'automatic',
        ];
    }
}
```

```php
<?php

namespace Database\Factories;

use App\Models\Result;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'grading_status' => 'pending',
            'publication_status' => 'unpublished',
        ];
    }
}
```

- [ ] **Step 5: Write the failing test**

```php
<?php

namespace Tests\Feature\Submission;

use App\Models\Feedback;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\Result;
use App\Models\ResultPublication;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeResultModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_history_and_result_publication_relations_resolve(): void
    {
        $submission = Submission::factory()->create();
        $grade = Grade::factory()->create(['submission_id' => $submission->id]);
        $actor = User::factory()->create();
        GradeHistory::create([
            'grade_id' => $grade->id,
            'before_data' => ['score' => 5],
            'after_data' => ['score' => 10],
            'changed_by_user_id' => $actor->id,
            'changed_at' => now(),
            'reason' => 'typo lúc chấm',
        ]);
        $result = Result::factory()->create(['submission_id' => $submission->id]);
        ResultPublication::create([
            'result_id' => $result->id,
            'action' => 'published',
            'published_at' => now(),
            'actor_user_id' => $actor->id,
        ]);
        Feedback::factory()->create(['submission_id' => $submission->id, 'grade_id' => $grade->id, 'body' => 'Làm tốt', 'visibility' => 'learner']);

        $this->assertCount(1, $grade->history);
        $this->assertCount(1, $result->publications);
        $this->assertCount(1, $submission->grades);
        $this->assertCount(1, $submission->feedback);
    }
}
```

`Feedback` needs a `FeedbackFactory` too — add one mirroring `GradeFactory` (`submission_id` => `Submission::factory()`, `body` default sentence, `visibility` default `'learner'`, `status` default `'draft'`).

Run: `php artisan test --filter=GradeResultModelTest`
Expected: FAIL until `FeedbackFactory` exists, then PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_10_16_0000{05,06,07,08,09,10,11}_*.php app/Models/Grade*.php app/Models/Feedback*.php app/Models/Result*.php database/factories/GradeFactory.php database/factories/ResultFactory.php database/factories/FeedbackFactory.php tests/Feature/Submission/GradeResultModelTest.php
git commit -m "feat: add Grade/Feedback/Result schema and models with history + publication"
```

---

## Task 3: Auto-grading engine service (`AutoGrader`)

**Files:**
- Create: `app/Services/Grading/AutoGrader.php`
- Test: `tests/Unit/Services/Grading/AutoGraderTest.php`

**Interfaces:**
- Consumes: `AssessmentQuestion::class` (its `snapshot` array, as built by `PublishAssessmentController` — keys `prompt`, `instructions`, `type_payload`, `grading_mode`, `grading_config`, `options[]` each `{id, position, content, is_correct}`).
- Produces: `AutoGrader::grade(AssessmentQuestion $question, mixed $answerPayload): ?array` returning `['score' => float, 'max_score' => float]` when the question type is auto-gradable (`single_choice`, `multiple_choice`, `true_false`, `fill_in_the_blank`, `short_answer`, `matching`, `ordering`), or `null` when it is `essay`/`file_upload` (caller must leave grading to a human) **and** the answer is non-empty. When `$answerPayload` is empty/null, `AutoGrader::grade()` always returns `['score' => 0.0, 'max_score' => (float) $question->points]` regardless of type (blank answer rule applies to every question type, §4 of the spec).

- [ ] **Step 1: Write the failing test for blank answers (applies to every type)**

```php
<?php

namespace Tests\Unit\Services\Grading;

use App\Models\AssessmentQuestion;
use App\Services\Grading\AutoGrader;
use Tests\TestCase;

class AutoGraderTest extends TestCase
{
    public function test_blank_answer_scores_zero_for_every_question_type(): void
    {
        $grader = new AutoGrader();
        $question = AssessmentQuestion::factory()->make([
            'question_type' => 'essay',
            'points' => 10,
            'snapshot' => ['grading_mode' => 'manual'],
        ]);

        $result = $grader->grade($question, null);

        $this->assertSame(['score' => 0.0, 'max_score' => 10.0], $result);
    }
}
```

Run: `php artisan test --filter=AutoGraderTest`
Expected: FAIL (`Class "App\Services\Grading\AutoGrader" not found`).

- [ ] **Step 2: Write minimal implementation for blank-answer case**

```php
<?php

namespace App\Services\Grading;

use App\Models\AssessmentQuestion;

class AutoGrader
{
    public function grade(AssessmentQuestion $question, mixed $answerPayload): ?array
    {
        $maxScore = (float) $question->points;

        if ($this->isBlank($answerPayload)) {
            return ['score' => 0.0, 'max_score' => $maxScore];
        }

        return null;
    }

    private function isBlank(mixed $answerPayload): bool
    {
        return $answerPayload === null || $answerPayload === [] || $answerPayload === '';
    }
}
```

Run: `php artisan test --filter=AutoGraderTest`
Expected: PASS.

- [ ] **Step 3: Write failing tests for single_choice/true_false and multiple_choice (full + partial)**

```php
    public function test_single_choice_full_credit_when_selecting_the_correct_option(): void
    {
        $grader = new AutoGrader();
        $question = AssessmentQuestion::factory()->make([
            'question_type' => 'single_choice',
            'points' => 4,
            'snapshot' => ['options' => [
                ['id' => 'opt-a', 'is_correct' => true],
                ['id' => 'opt-b', 'is_correct' => false],
            ]],
        ]);

        $this->assertSame(['score' => 4.0, 'max_score' => 4.0], $grader->grade($question, ['opt-a']));
        $this->assertSame(['score' => 0.0, 'max_score' => 4.0], $grader->grade($question, ['opt-b']));
    }

    public function test_multiple_choice_requires_exact_set_for_full_credit_by_default(): void
    {
        $grader = new AutoGrader();
        $question = AssessmentQuestion::factory()->make([
            'question_type' => 'multiple_choice',
            'points' => 6,
            'snapshot' => ['grading_config' => [], 'options' => [
                ['id' => 'opt-a', 'is_correct' => true],
                ['id' => 'opt-b', 'is_correct' => true],
                ['id' => 'opt-c', 'is_correct' => false],
            ]],
        ]);

        $this->assertSame(['score' => 6.0, 'max_score' => 6.0], $grader->grade($question, ['opt-a', 'opt-b']));
        // selecting both correct options PLUS a wrong one must NOT be full credit (Review Focus case)
        $this->assertSame(['score' => 0.0, 'max_score' => 6.0], $grader->grade($question, ['opt-a', 'opt-b', 'opt-c']));
        $this->assertSame(['score' => 0.0, 'max_score' => 6.0], $grader->grade($question, ['opt-a']));
    }
```

Run: `php artisan test --filter=AutoGraderTest`
Expected: FAIL (both new tests).

- [ ] **Step 4: Implement choice-matching grading**

```php
<?php

namespace App\Services\Grading;

use App\Models\AssessmentQuestion;

class AutoGrader
{
    public function grade(AssessmentQuestion $question, mixed $answerPayload): ?array
    {
        $maxScore = (float) $question->points;

        if ($this->isBlank($answerPayload)) {
            return ['score' => 0.0, 'max_score' => $maxScore];
        }

        return match ($question->question_type) {
            'single_choice', 'true_false', 'multiple_choice' => $this->gradeChoice($question, $answerPayload, $maxScore),
            'fill_in_the_blank', 'short_answer' => $this->gradeText($question, $answerPayload, $maxScore),
            'matching', 'ordering' => $this->gradeStructure($question, $answerPayload, $maxScore),
            default => null, // essay, file_upload — manual only
        };
    }

    private function gradeChoice(AssessmentQuestion $question, array $selectedIds, float $maxScore): array
    {
        $options = $question->snapshot['options'] ?? [];
        $correctIds = array_values(array_map(
            fn ($o) => $o['id'],
            array_filter($options, fn ($o) => $o['is_correct'] === true)
        ));
        $selected = array_values($selectedIds);

        sort($correctIds);
        sort($selected);

        $isFullyCorrect = $correctIds === $selected;

        return ['score' => $isFullyCorrect ? $maxScore : 0.0, 'max_score' => $maxScore];
    }

    private function gradeText(AssessmentQuestion $question, string $text, float $maxScore): array
    {
        $config = $question->snapshot['grading_config'] ?? [];
        $accepted = $config['accepted_answers'] ?? [];
        $caseSensitive = $config['case_sensitive'] ?? false;

        $normalize = fn (string $s) => $caseSensitive ? trim($s) : mb_strtolower(trim($s));
        $normalizedText = $normalize($text);

        $isCorrect = in_array($normalizedText, array_map($normalize, $accepted), true);

        return ['score' => $isCorrect ? $maxScore : 0.0, 'max_score' => $maxScore];
    }

    private function gradeStructure(AssessmentQuestion $question, array $answerPayload, float $maxScore): array
    {
        $correct = $question->snapshot['type_payload']['correct'] ?? null;

        $isCorrect = $correct !== null && $answerPayload === $correct;

        return ['score' => $isCorrect ? $maxScore : 0.0, 'max_score' => $maxScore];
    }

    private function isBlank(mixed $answerPayload): bool
    {
        return $answerPayload === null || $answerPayload === [] || $answerPayload === '';
    }
}
```

Run: `php artisan test --filter=AutoGraderTest`
Expected: PASS (all 4 tests).

- [ ] **Step 5: Write failing tests for text matching and essay/file_upload returning null**

```php
    public function test_fill_in_the_blank_matches_case_insensitively_by_default(): void
    {
        $grader = new AutoGrader();
        $question = AssessmentQuestion::factory()->make([
            'question_type' => 'fill_in_the_blank',
            'points' => 2,
            'snapshot' => ['grading_config' => ['accepted_answers' => ['Paris']]],
        ]);

        $this->assertSame(['score' => 2.0, 'max_score' => 2.0], $grader->grade($question, 'paris'));
        $this->assertSame(['score' => 0.0, 'max_score' => 2.0], $grader->grade($question, 'london'));
    }

    public function test_essay_and_file_upload_return_null_when_answer_present(): void
    {
        $grader = new AutoGrader();
        $essay = AssessmentQuestion::factory()->make(['question_type' => 'essay', 'points' => 10, 'snapshot' => []]);
        $upload = AssessmentQuestion::factory()->make(['question_type' => 'file_upload', 'points' => 10, 'snapshot' => []]);

        $this->assertNull($grader->grade($essay, 'bài làm của em...'));
        $this->assertNull($grader->grade($upload, ['file_id' => 'abc']));
    }
```

Run: `php artisan test --filter=AutoGraderTest`
Expected: PASS — these are covered by Step 4's implementation already; this step pins the behavior with explicit tests so a future change can't silently break it.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Grading/AutoGrader.php tests/Unit/Services/Grading/AutoGraderTest.php
git commit -m "feat: add AutoGrader service for 9 question_type auto-grading"
```

---

## Task 4: Result calculation + auto-publish service (`ResultCalculator`)

**Files:**
- Create: `app/Services/Grading/ResultCalculator.php`
- Test: `tests/Unit/Services/Grading/ResultCalculatorTest.php`

**Interfaces:**
- Consumes: `Submission::class`, `Grade::class`, `Result::class`, `ResultPublication::class` (Tasks 1–2); `AssessmentVersion::class` (`result_release_at`, `passing_score`, `max_score`).
- Produces: `ResultCalculator::recalculate(Submission $submission): Result` — reads all `AssessmentQuestion`s of the Attempt's `AssessmentVersion` and all existing `Grade`s for the Submission, computes `grading_status` (`pending`/`partial`/`complete` per §4 of the spec: `complete` only when every question has a `Grade` row), and `score`/`max_score`/`percentage`/`passed` (left `null` unless `complete`). Persists via `firstOrCreate(['submission_id' => ...])` + `update`, writing a `ResultHistory` row when values changed after a prior `complete` state.
- Produces: `ResultCalculator::maybeAutoPublish(Result $result, User $actor): void` — if `$result->grading_status === 'complete'` and `$result->publication_status === 'unpublished'` and (`result_release_at` is null or in the past), calls `publish()`.
- Produces: `ResultCalculator::publish(Result $result, User $actor): void` and `ResultCalculator::withdraw(Result $result, User $actor): void`, each creating the matching `ResultPublication` row and updating `publication_status`.

- [ ] **Step 1: Write the failing test for `grading_status` transitions**

```php
<?php

namespace Tests\Unit\Services\Grading;

use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\Grade;
use App\Models\Submission;
use App\Services\Grading\ResultCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_grading_status_is_pending_until_every_question_has_a_grade(): void
    {
        $version = AssessmentVersion::factory()->create();
        AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'points' => 5]);
        $essay = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'points' => 5, 'question_type' => 'essay']);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);

        $result = (new ResultCalculator())->recalculate($submission);
        $this->assertSame('pending', $result->grading_status);
        $this->assertNull($result->score);

        Grade::factory()->create(['submission_id' => $submission->id, 'assessment_question_id' => $essay->id, 'score' => 3, 'max_score' => 5, 'grading_method' => 'manual']);

        $result = (new ResultCalculator())->recalculate($submission);
        $this->assertSame('partial', $result->grading_status);
    }
}
```

Run: `php artisan test --filter=ResultCalculatorTest`
Expected: FAIL (class not found).

- [ ] **Step 2: Implement `recalculate()`**

```php
<?php

namespace App\Services\Grading;

use App\Models\Grade;
use App\Models\Result;
use App\Models\ResultPublication;
use App\Models\Submission;
use App\Models\User;

class ResultCalculator
{
    public function recalculate(Submission $submission): Result
    {
        $attempt = $submission->attempt;
        $questions = $attempt->assessmentVersion->questions;
        $grades = Grade::where('submission_id', $submission->id)->get()->keyBy('assessment_question_id');

        $gradedQuestionCount = $questions->filter(fn ($q) => $grades->has($q->id))->count();
        $totalQuestionCount = $questions->count();

        $gradingStatus = match (true) {
            $gradedQuestionCount === 0 => 'pending',
            $gradedQuestionCount < $totalQuestionCount => 'partial',
            default => 'complete',
        };

        $result = Result::firstOrCreate(['submission_id' => $submission->id], ['grading_status' => 'pending', 'publication_status' => 'unpublished']);
        $before = $result->only(['score', 'max_score', 'percentage', 'passed', 'grading_status']);

        if ($gradingStatus === 'complete') {
            $score = (float) $grades->sum('score');
            $maxScore = (float) $grades->sum('max_score');
            $percentage = $maxScore > 0 ? round($score / $maxScore * 100, 2) : 0.0;
            $passingScore = $attempt->assessmentVersion->passing_score;
            $passed = $passingScore !== null ? $score >= (float) $passingScore : null;
        } else {
            $score = $maxScore = $percentage = $passed = null;
        }

        $result->update(compact('score', 'maxScore', 'percentage', 'passed') + [
            'max_score' => $maxScore,
            'grading_status' => $gradingStatus,
        ]);

        if ($result->publication_status === 'published' && $before !== $result->only(['score', 'max_score', 'percentage', 'passed', 'grading_status'])) {
            $result->history()->create([
                'before_data' => $before,
                'after_data' => $result->only(['score', 'max_score', 'percentage', 'passed', 'grading_status']),
                'changed_by_user_id' => $grades->first()?->graded_by_user_id ?? $submission->submitted_by_user_id,
                'changed_at' => now(),
            ]);
        }

        return $result->refresh();
    }

    public function maybeAutoPublish(Result $result, User $actor): void
    {
        if ($result->grading_status !== 'complete' || $result->publication_status !== 'unpublished') {
            return;
        }

        $releaseAt = $result->submission->attempt->assessmentVersion->result_release_at;
        if ($releaseAt === null || $releaseAt->isPast()) {
            $this->publish($result, $actor);
        }
    }

    public function publish(Result $result, User $actor): void
    {
        $result->update(['publication_status' => 'published']);
        ResultPublication::create([
            'result_id' => $result->id,
            'action' => 'published',
            'published_at' => now(),
            'actor_user_id' => $actor->id,
        ]);
    }

    public function withdraw(Result $result, User $actor): void
    {
        $result->update(['publication_status' => 'unpublished']);
        ResultPublication::create([
            'result_id' => $result->id,
            'action' => 'withdrawn',
            'published_at' => now(),
            'actor_user_id' => $actor->id,
        ]);
    }
}
```

Run: `php artisan test --filter=ResultCalculatorTest`
Expected: PASS.

- [ ] **Step 3: Write failing tests for `complete` scoring, `maybeAutoPublish`, `publish`, `withdraw`**

```php
    public function test_complete_grading_computes_score_percentage_and_passed(): void
    {
        $version = AssessmentVersion::factory()->create(['passing_score' => 6]);
        $q1 = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'points' => 5]);
        $q2 = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'points' => 5]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        Grade::factory()->create(['submission_id' => $submission->id, 'assessment_question_id' => $q1->id, 'score' => 5, 'max_score' => 5]);
        Grade::factory()->create(['submission_id' => $submission->id, 'assessment_question_id' => $q2->id, 'score' => 2, 'max_score' => 5]);

        $result = (new ResultCalculator())->recalculate($submission);

        $this->assertSame('complete', $result->grading_status);
        $this->assertEquals(7.0, $result->score);
        $this->assertEquals(70.0, $result->percentage);
        $this->assertTrue($result->passed);
    }

    public function test_maybe_auto_publish_publishes_when_release_at_is_in_the_past(): void
    {
        $version = AssessmentVersion::factory()->create(['result_release_at' => now()->subMinute()]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = \App\Models\Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete']);
        $actor = \App\Models\User::factory()->create();

        (new ResultCalculator())->maybeAutoPublish($result, $actor);

        $this->assertSame('published', $result->refresh()->publication_status);
        $this->assertSame(1, $result->publications()->count());
    }

    public function test_maybe_auto_publish_does_nothing_when_release_at_is_in_the_future(): void
    {
        $version = AssessmentVersion::factory()->create(['result_release_at' => now()->addHour()]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = \App\Models\Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete']);

        (new ResultCalculator())->maybeAutoPublish($result, \App\Models\User::factory()->create());

        $this->assertSame('unpublished', $result->refresh()->publication_status);
    }
```

Run: `php artisan test --filter=ResultCalculatorTest`
Expected: FAIL (new assertions).

- [ ] **Step 4: Confirm implementation from Step 2 already satisfies them**

Run: `php artisan test --filter=ResultCalculatorTest`
Expected: PASS (the Step 2 implementation already covers these — this step is verification, not new code).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Grading/ResultCalculator.php tests/Unit/Services/Grading/ResultCalculatorTest.php
git commit -m "feat: add ResultCalculator service for grading_status/score and auto-publish"
```

---

## Task 5: Start Attempt

**Files:**
- Create: `app/Http/Controllers/Api/Submission/StartAttemptController.php`
- Modify: `routes/api.php` — add `attempt` to the UUID pattern loop; add route under the existing `role:student` group
- Test: `tests/Feature/Submission/StartAttemptTest.php`

**Interfaces:**
- Consumes: `StudentAssessment::class`, `AssessmentVersion::class`, `Attempt::class`, `AttemptQuestion::class` (Task 1).
- Produces: `POST /api/v1/me/assessments/{assessment}/attempts` → `201` with `{"attempt": {...}}`, or `404`/`409`/`422`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Submission;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartAttemptTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStudent(): array
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $student = Student::factory()->create(['person_profile_id' => \App\Models\PersonProfile::factory()->create(['user_id' => $user->id])->id]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        return [$user, $student];
    }

    public function test_starting_an_attempt_creates_attempt_questions_from_snapshot(): void
    {
        [$user, $student] = $this->actingAsStudent();
        $assessment = Assessment::factory()->create(['status' => 'published']);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'published', 'max_attempts' => 1]);
        $assessment->update(['current_version_id' => $version->id]);
        $q1 = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'position' => 1]);
        $sa = StudentAssessment::factory()->create([
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'assigned_version_id' => $version->id,
            'status' => 'assigned',
        ]);

        $response = $this->postJson("/api/v1/me/assessments/{$assessment->id}/attempts");

        $response->assertCreated();
        $this->assertDatabaseHas('attempts', ['student_assessment_id' => $sa->id, 'attempt_number' => 1, 'status' => 'in_progress']);
        $this->assertDatabaseHas('attempt_questions', ['assessment_question_id' => $q1->id, 'position' => 1]);
    }

    public function test_cannot_start_a_second_attempt_beyond_max_attempts(): void
    {
        [$user, $student] = $this->actingAsStudent();
        $assessment = Assessment::factory()->create(['status' => 'published']);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'status' => 'published', 'max_attempts' => 1]);
        $sa = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'assigned_version_id' => $version->id]);
        \App\Models\Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id, 'attempt_number' => 1]);

        $response = $this->postJson("/api/v1/me/assessments/{$assessment->id}/attempts");

        $response->assertStatus(409);
    }
}
```

Run: `php artisan test --filter=StartAttemptTest`
Expected: FAIL (route not found / 404).

- [ ] **Step 2: Add `attempt` to the UUID pattern loop and register the route**

In `routes/api.php` line 18, add `'attempt'` to the array. Inside the existing `Route::middleware('role:student')->group(...)` block (line 146-149):

```php
Route::post('/me/assessments/{assessment}/attempts', \App\Http\Controllers\Api\Submission\StartAttemptController::class);
```

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\StudentAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StartAttemptController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $studentId = $request->user()->studentProfile->id;
        $sa = StudentAssessment::where('assessment_id', $assessment->id)->where('student_id', $studentId)->first();
        abort_unless($sa, 404);
        abort_if($sa->status !== 'assigned', 409, 'Bài không còn ở trạng thái được giao.');
        abort_if($assessment->status !== 'published', 409, 'Assessment chưa được publish hoặc đã đóng.');

        $version = $sa->assignedVersion;
        $now = now();
        abort_if($version->opens_at && $now->lt($version->opens_at), 409, 'Chưa tới giờ mở bài.');
        abort_if($version->closes_at && $now->gt($version->closes_at), 409, 'Đã quá giờ đóng bài.');

        $maxAttempts = $sa->attempt_limit_override ?? $version->max_attempts;
        $usedAttempts = Attempt::where('student_assessment_id', $sa->id)->where('status', '!=', 'cancelled')->count();
        abort_if($usedAttempts >= $maxAttempts, 409, 'Đã dùng hết số lượt làm bài cho phép.');

        $attempt = DB::transaction(function () use ($sa, $version, $usedAttempts) {
            $endsAt = null;
            if ($version->duration_seconds !== null) {
                $endsAt = now()->addSeconds($version->duration_seconds);
                if ($version->closes_at && $endsAt->gt($version->closes_at)) {
                    $endsAt = $version->closes_at;
                }
            }

            $attempt = Attempt::create([
                'student_assessment_id' => $sa->id,
                'assessment_version_id' => $version->id,
                'attempt_number' => $usedAttempts + 1,
                'status' => 'in_progress',
                'started_at' => now(),
                'ends_at' => $endsAt,
            ]);

            foreach ($version->questions as $question) {
                $attempt->attemptQuestions()->create([
                    'assessment_question_id' => $question->id,
                    'position' => $question->position,
                ]);
            }

            return $attempt;
        });

        return response()->json(['attempt' => $attempt->load('attemptQuestions.assessmentQuestion')], 201);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=StartAttemptTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Submission/StartAttemptController.php routes/api.php tests/Feature/Submission/StartAttemptTest.php
git commit -m "feat: add POST /me/assessments/{assessment}/attempts to start an Attempt"
```

---

## Task 6: Autosave Answer

**Files:**
- Create: `app/Http/Controllers/Api/Submission/SaveAnswerController.php`
- Modify: `routes/api.php` — add `attempt`, `assessmentQuestion` (already present) to UUID loop if missing; add route
- Test: `tests/Feature/Submission/SaveAnswerTest.php`

**Interfaces:**
- Consumes: `Attempt::class`, `Answer::class` (Task 1).
- Produces: `PUT /api/v1/me/attempts/{attempt}/answers/{assessmentQuestion}` → `200` with `{"answer": {...}}`, or `404`/`409`/`422`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Submission;

use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Models\AssessmentQuestion;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveAnswerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_autosave_an_answer_on_their_own_in_progress_attempt(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $profile = \App\Models\PersonProfile::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create(['person_profile_id' => $profile->id]);
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'status' => 'in_progress']);
        $aq = AttemptQuestion::factory()->create(['attempt_id' => $attempt->id]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $response = $this->putJson("/api/v1/me/attempts/{$attempt->id}/answers/{$aq->assessment_question_id}", [
            'answer_payload' => ['opt-a'],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('answers', [
            'attempt_id' => $attempt->id,
            'assessment_question_id' => $aq->assessment_question_id,
            'is_submitted' => false,
        ]);
    }

    public function test_cannot_autosave_on_an_attempt_that_is_no_longer_in_progress(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $profile = \App\Models\PersonProfile::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create(['person_profile_id' => $profile->id]);
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'status' => 'submitted']);
        $aq = AttemptQuestion::factory()->create(['attempt_id' => $attempt->id]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $response = $this->putJson("/api/v1/me/attempts/{$attempt->id}/answers/{$aq->assessment_question_id}", [
            'answer_payload' => ['opt-a'],
        ]);

        $response->assertStatus(409);
    }
}
```

Run: `php artisan test --filter=SaveAnswerTest`
Expected: FAIL (route not found).

- [ ] **Step 2: Register route (add `attempt` param if not already added by Task 5)**

```php
// inside role:student group
Route::put('/me/attempts/{attempt}/answers/{assessmentQuestion}', \App\Http\Controllers\Api\Submission\SaveAnswerController::class);
```

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\AssessmentQuestion;
use App\Models\Attempt;
use Illuminate\Http\Request;

class SaveAnswerController extends Controller
{
    public function __invoke(Request $request, Attempt $attempt, AssessmentQuestion $assessmentQuestion)
    {
        $studentId = $request->user()->studentProfile->id;
        abort_unless($attempt->studentAssessment->student_id === $studentId, 404);
        abort_if($attempt->status !== 'in_progress', 409, 'Attempt không còn mở để lưu bài.');
        abort_if($attempt->isTimedOut(), 409, 'Đã hết giờ làm bài.');

        $data = $request->validate(['answer_payload' => 'nullable']);

        $answer = \App\Models\Answer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'assessment_question_id' => $assessmentQuestion->id],
            ['answer_payload' => $data['answer_payload'] ?? null, 'saved_at' => now(), 'is_submitted' => false]
        );

        return response()->json(['answer' => $answer]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=SaveAnswerTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Submission/SaveAnswerController.php routes/api.php tests/Feature/Submission/SaveAnswerTest.php
git commit -m "feat: add PUT /me/attempts/{attempt}/answers/{assessmentQuestion} autosave"
```

---

## Task 7: Submit Attempt

**Files:**
- Create: `app/Http/Controllers/Api/Submission/SubmitAttemptController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Submission/SubmitAttemptTest.php`

**Interfaces:**
- Consumes: `AutoGrader::grade()` (Task 3), `ResultCalculator::recalculate()`/`maybeAutoPublish()` (Task 4), `Submission::class`, `Grade::class` (Tasks 1–2).
- Produces: `POST /api/v1/me/attempts/{attempt}/submit` → `200` with `{"submission": {...}, "result": {...}}`, or `404`/`409`.

- [ ] **Step 1: Write the failing test — full auto-gradable quiz auto-publishes immediately**

```php
<?php

namespace Tests\Feature\Submission;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmitAttemptTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStudent(): Student
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $profile = PersonProfile::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create(['person_profile_id' => $profile->id]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        return $student;
    }

    public function test_submitting_a_fully_auto_gradable_quiz_computes_and_auto_publishes_result(): void
    {
        $student = $this->actingAsStudent();
        $version = AssessmentVersion::factory()->create(['result_release_at' => null, 'passing_score' => 1]);
        $q1 = AssessmentQuestion::factory()->create([
            'assessment_version_id' => $version->id,
            'question_type' => 'single_choice',
            'points' => 5,
            'snapshot' => ['options' => [['id' => 'opt-a', 'is_correct' => true], ['id' => 'opt-b', 'is_correct' => false]]],
        ]);
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id, 'assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id, 'status' => 'in_progress']);
        $attempt->attemptQuestions()->create(['assessment_question_id' => $q1->id, 'position' => 1]);
        Answer::create(['attempt_id' => $attempt->id, 'assessment_question_id' => $q1->id, 'answer_payload' => ['opt-a'], 'saved_at' => now()]);

        $response = $this->postJson("/api/v1/me/attempts/{$attempt->id}/submit");

        $response->assertOk();
        $this->assertDatabaseHas('submissions', ['attempt_id' => $attempt->id, 'status' => 'graded']);
        $this->assertDatabaseHas('grades', ['assessment_question_id' => $q1->id, 'score' => 5, 'grading_method' => 'automatic']);
        $this->assertDatabaseHas('results', ['score' => 5, 'grading_status' => 'complete', 'publication_status' => 'published']);
        $this->assertSame('graded', $attempt->refresh()->status);
    }

    public function test_submitting_twice_returns_409_on_the_second_call(): void
    {
        $student = $this->actingAsStudent();
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'status' => 'in_progress']);

        $this->postJson("/api/v1/me/attempts/{$attempt->id}/submit")->assertOk();
        $this->postJson("/api/v1/me/attempts/{$attempt->id}/submit")->assertStatus(409);
    }
}
```

Run: `php artisan test --filter=SubmitAttemptTest`
Expected: FAIL (route not found).

- [ ] **Step 2: Register route**

```php
// inside role:student group
Route::post('/me/attempts/{attempt}/submit', \App\Http\Controllers\Api\Submission\SubmitAttemptController::class);
```

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Grade;
use App\Models\Submission;
use App\Services\Grading\AutoGrader;
use App\Services\Grading\ResultCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmitAttemptController extends Controller
{
    public function __invoke(Request $request, Attempt $attempt)
    {
        $studentId = $request->user()->studentProfile->id;
        abort_unless($attempt->studentAssessment->student_id === $studentId, 404);
        abort_if($attempt->status !== 'in_progress', 409, 'Attempt đã nộp hoặc không còn mở.');

        $submission = DB::transaction(function () use ($attempt, $request) {
            \App\Models\Answer::where('attempt_id', $attempt->id)->update(['is_submitted' => true]);

            $deadline = $attempt->studentAssessment->individual_deadline_at ?? $attempt->assessmentVersion->deadline_at;
            $isLate = $deadline !== null && now()->gt($deadline);

            $submission = Submission::create([
                'attempt_id' => $attempt->id,
                'received_at' => now(),
                'status' => 'received',
                'is_late' => $isLate,
                'submitted_by_user_id' => $request->user()->id,
            ]);

            $grader = new AutoGrader();
            $hasPendingManual = false;
            foreach ($attempt->attemptQuestions as $aq) {
                $question = $aq->assessmentQuestion;
                $answer = $attempt->answers->firstWhere('assessment_question_id', $question->id);
                $graded = $grader->grade($question, $answer?->answer_payload);

                if ($graded === null) {
                    $hasPendingManual = true;

                    continue;
                }

                Grade::create([
                    'submission_id' => $submission->id,
                    'assessment_question_id' => $question->id,
                    'score' => $graded['score'],
                    'max_score' => $graded['max_score'],
                    'grading_method' => 'automatic',
                ]);
            }

            $submission->update(['status' => $hasPendingManual ? 'awaiting_grading' : 'graded']);
            $attempt->update(['status' => $hasPendingManual ? 'awaiting_grading' : 'graded']);

            $calculator = new ResultCalculator();
            $result = $calculator->recalculate($submission);
            $calculator->maybeAutoPublish($result, $request->user());

            return $submission;
        });

        return response()->json([
            'submission' => $submission,
            'result' => $submission->result()->first(),
        ]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=SubmitAttemptTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Submission/SubmitAttemptController.php routes/api.php tests/Feature/Submission/SubmitAttemptTest.php
git commit -m "feat: add POST /me/attempts/{attempt}/submit with synchronous auto-grading"
```

---

## Task 8: Get Attempt (with lazy timeout auto-submit)

**Files:**
- Create: `app/Http/Controllers/Api/Submission/GetAttemptController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Submission/GetAttemptTest.php`

**Interfaces:**
- Consumes: `SubmitAttemptController`'s submit logic — **refactor the submit body from Task 7 into a reusable method** `SubmitAttemptController::submit(Attempt $attempt, User $actor): Submission` so this task can call it when lazily detecting a timeout, instead of duplicating the transaction.

- [ ] **Step 1: Refactor Task 7's controller to expose a reusable static-free method**

```php
// app/Http/Controllers/Api/Submission/SubmitAttemptController.php
class SubmitAttemptController extends Controller
{
    public function __invoke(Request $request, Attempt $attempt)
    {
        $studentId = $request->user()->studentProfile->id;
        abort_unless($attempt->studentAssessment->student_id === $studentId, 404);
        abort_if($attempt->status !== 'in_progress', 409, 'Attempt đã nộp hoặc không còn mở.');

        $submission = $this->submit($attempt, $request->user());

        return response()->json(['submission' => $submission, 'result' => $submission->result()->first()]);
    }

    public function submit(Attempt $attempt, \App\Models\User $actor): Submission
    {
        return DB::transaction(function () use ($attempt, $actor) {
            // ... exact body from Task 7 Step 3, using $actor in place of $request->user()
        });
    }
}
```

(Keep the transaction body identical to Task 7 Step 3 — only the signature changes: extract it into this public `submit()` method and have `__invoke` call it.)

- [ ] **Step 2: Run Task 7's existing tests to confirm the refactor didn't break anything**

Run: `php artisan test --filter=SubmitAttemptTest`
Expected: PASS (no behavior change).

- [ ] **Step 3: Write the failing test for `GetAttemptController`**

```php
<?php

namespace Tests\Feature\Submission;

use App\Models\Attempt;
use App\Models\AssessmentVersion;
use App\Models\PersonProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_a_timed_out_attempt_auto_submits_it(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $profile = PersonProfile::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create(['person_profile_id' => $profile->id]);
        $version = AssessmentVersion::factory()->create(['type' => 'quiz', 'duration_seconds' => 60]);
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id, 'assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create([
            'student_assessment_id' => $sa->id,
            'assessment_version_id' => $version->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->subMinute(),
        ]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $response = $this->getJson("/api/v1/me/attempts/{$attempt->id}");

        $response->assertOk();
        $this->assertDatabaseHas('submissions', ['attempt_id' => $attempt->id]);
        $this->assertNotSame('in_progress', $attempt->refresh()->status);
    }

    public function test_viewing_an_attempt_still_within_time_does_not_auto_submit(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $profile = PersonProfile::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create(['person_profile_id' => $profile->id]);
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'status' => 'in_progress', 'ends_at' => now()->addMinutes(10)]);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $this->getJson("/api/v1/me/attempts/{$attempt->id}")->assertOk();

        $this->assertDatabaseMissing('submissions', ['attempt_id' => $attempt->id]);
        $this->assertSame('in_progress', $attempt->refresh()->status);
    }
}
```

Run: `php artisan test --filter=GetAttemptTest`
Expected: FAIL (route not found).

- [ ] **Step 4: Register route and write the controller**

```php
// routes/api.php, inside role:student group
Route::get('/me/attempts/{attempt}', \App\Http\Controllers\Api\Submission\GetAttemptController::class);
```

```php
<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Illuminate\Http\Request;

class GetAttemptController extends Controller
{
    public function __construct(private SubmitAttemptController $submitController)
    {
    }

    public function __invoke(Request $request, Attempt $attempt)
    {
        $studentId = $request->user()->studentProfile->id;
        abort_unless($attempt->studentAssessment->student_id === $studentId, 404);

        if ($attempt->isTimedOut()) {
            $this->submitController->submit($attempt, $request->user());
            $attempt->refresh();
        }

        return response()->json(['attempt' => $attempt->load('attemptQuestions.assessmentQuestion', 'answers', 'submission.result')]);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=GetAttemptTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Submission/GetAttemptController.php app/Http/Controllers/Api/Submission/SubmitAttemptController.php routes/api.php tests/Feature/Submission/GetAttemptTest.php
git commit -m "feat: add GET /me/attempts/{attempt} with lazy timeout auto-submit"
```

---

## Task 9: List submissions needing grading + get submission detail

**Files:**
- Create: `app/Http/Controllers/Api/Grading/ListSubmissionsController.php`
- Create: `app/Http/Controllers/Api/Grading/GetSubmissionController.php`
- Modify: `routes/api.php` — add `submission` to UUID loop
- Test: `tests/Feature/Grading/ListSubmissionsTest.php`

**Interfaces:**
- Consumes: `Submission::class`, teacher-scope pattern from `ListAssessmentStudentsController` (Class via `activeClassIds()`, Course via `owner_teacher_id`).
- Produces: `GET /api/v1/assessments/{assessment}/submissions?grading_status=` → `{"data": [...]}`; `GET /api/v1/submissions/{submission}` → `{"submission": {...}}`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Grading;

use App\Models\Assessment;
use App\Models\AssessmentAudience;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\PersonProfile;
use App\Models\Result;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\TeacherClassAssignment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListSubmissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_only_submissions_in_their_assigned_class(): void
    {
        $teacherUser = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create(['user_id' => $teacherUser->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $teacher = Teacher::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $teacherUser->id])->id]);
        $class = SchoolClass::factory()->create();
        TeacherClassAssignment::create(['class_id' => $class->id, 'teacher_id' => $teacher->id, 'valid_from' => now()->subDay(), 'status' => 'active']);

        $assessment = Assessment::factory()->create();
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        AssessmentAudience::factory()->create(['assessment_version_id' => $version->id, 'class_id' => $class->id]);
        $sa = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'source_class_id' => $class->id, 'assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'partial']);

        $otherClass = SchoolClass::factory()->create();
        $otherSa = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'source_class_id' => $otherClass->id, 'assigned_version_id' => $version->id]);
        $otherAttempt = Attempt::factory()->create(['student_assessment_id' => $otherSa->id, 'assessment_version_id' => $version->id]);
        Submission::factory()->create(['attempt_id' => $otherAttempt->id]);

        $this->actingAs($teacherUser)->withSession(['active_role_code' => 'teacher']);

        $response = $this->getJson("/api/v1/assessments/{$assessment->id}/submissions");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $submission->id);
    }
}
```

Run: `php artisan test --filter=ListSubmissionsTest`
Expected: FAIL (route not found).

- [ ] **Step 2: Register routes (inside `role:admin,teacher` group) and add `submission` to the UUID loop**

```php
Route::get('/assessments/{assessment}/submissions', \App\Http\Controllers\Api\Grading\ListSubmissionsController::class);
Route::get('/submissions/{submission}', \App\Http\Controllers\Api\Grading\GetSubmissionController::class);
```

- [ ] **Step 3: Write the controllers**

```php
<?php

namespace App\Http\Controllers\Api\Grading;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;

class ListSubmissionsController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $this->authorizeScope($request, $assessment);

        $query = Submission::query()
            ->whereHas('attempt.studentAssessment', fn ($q) => $q->where('assessment_id', $assessment->id))
            ->with('result');

        if ($status = $request->query('grading_status')) {
            $query->whereHas('result', fn ($q) => $q->where('grading_status', $status));
        }

        return response()->json(['data' => $query->get()]);
    }

    public static function authorizeScope(Request $request, Assessment $assessment): void
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole === 'admin') {
            return;
        }

        $teacher = $request->user()->teacherProfile;
        abort_unless($teacher, 404);
        $classIds = $teacher->activeClassIds();
        $ownedCourseIds = Course::where('owner_teacher_id', $teacher->id)->pluck('id');

        $inScope = $assessment->created_by_user_id === $request->user()->id
            || $assessment->versions()->whereHas('audiences', fn ($q) => $q->whereIn('class_id', $classIds))->exists()
            || $assessment->versions()->whereHas('audiences', fn ($q) => $q->whereIn('course_id', $ownedCourseIds))->exists();

        abort_unless($inScope, 404);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Grading;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class GetSubmissionController extends Controller
{
    public function __invoke(Request $request, Submission $submission)
    {
        $assessment = $submission->attempt->studentAssessment->assessment;
        ListSubmissionsController::authorizeScope($request, $assessment);

        return response()->json(['submission' => $submission->load('attempt.answers', 'grades', 'result', 'feedback')]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ListSubmissionsTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Grading/ListSubmissionsController.php app/Http/Controllers/Api/Grading/GetSubmissionController.php routes/api.php tests/Feature/Grading/ListSubmissionsTest.php
git commit -m "feat: add GET /assessments/{assessment}/submissions and GET /submissions/{submission}"
```

---

## Task 10: Manual grading (per-question + total) with GradeHistory

**Files:**
- Create: `app/Http/Controllers/Api/Grading/GradeQuestionController.php`
- Create: `app/Http/Controllers/Api/Grading/GradeSubmissionController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Grading/GradeQuestionTest.php`

**Interfaces:**
- Consumes: `ListSubmissionsController::authorizeScope()` (Task 9), `ResultCalculator` (Task 4), `Grade`/`GradeHistory` (Task 2).
- Produces: `PUT /api/v1/submissions/{submission}/questions/{assessmentQuestion}/grade`, `PUT /api/v1/submissions/{submission}/grade`.

- [ ] **Step 1: Write the failing test — grading the last pending question flips Result to `complete` and requires `reason` after publish**

```php
<?php

namespace Tests\Feature\Grading;

use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\Grade;
use App\Models\PersonProfile;
use App\Models\Result;
use App\Models\Role;
use App\Models\StudentAssessment;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeQuestionTest extends TestCase
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

    public function test_grading_the_last_pending_essay_question_completes_the_result(): void
    {
        $this->actingAsAdmin();
        $version = AssessmentVersion::factory()->create(['result_release_at' => now()->subMinute()]);
        $essay = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'question_type' => 'essay', 'points' => 10]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'pending']);

        $response = $this->putJson("/api/v1/submissions/{$submission->id}/questions/{$essay->id}/grade", ['score' => 8]);

        $response->assertOk();
        $this->assertDatabaseHas('grades', ['assessment_question_id' => $essay->id, 'score' => 8, 'grading_method' => 'manual']);
        $this->assertDatabaseHas('results', ['submission_id' => $submission->id, 'grading_status' => 'complete', 'publication_status' => 'published']);
    }

    public function test_regrading_a_published_result_requires_a_reason(): void
    {
        $actor = $this->actingAsAdmin();
        $version = AssessmentVersion::factory()->create();
        $essay = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'question_type' => 'essay', 'points' => 10]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $grade = Grade::factory()->create(['submission_id' => $submission->id, 'assessment_question_id' => $essay->id, 'score' => 6, 'max_score' => 10, 'grading_method' => 'manual']);
        Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $missingReason = $this->putJson("/api/v1/submissions/{$submission->id}/questions/{$essay->id}/grade", ['score' => 9]);
        $missingReason->assertStatus(422);

        $withReason = $this->putJson("/api/v1/submissions/{$submission->id}/questions/{$essay->id}/grade", ['score' => 9, 'reason' => 'chấm lại theo thang điểm mới']);
        $withReason->assertOk();
        $this->assertDatabaseHas('grade_histories', ['grade_id' => $grade->id, 'reason' => 'chấm lại theo thang điểm mới']);
    }
}
```

Run: `php artisan test --filter=GradeQuestionTest`
Expected: FAIL (route not found).

- [ ] **Step 2: Register routes**

```php
// routes/api.php, inside role:admin,teacher group
Route::put('/submissions/{submission}/questions/{assessmentQuestion}/grade', \App\Http\Controllers\Api\Grading\GradeQuestionController::class);
Route::put('/submissions/{submission}/grade', \App\Http\Controllers\Api\Grading\GradeSubmissionController::class);
```

- [ ] **Step 3: Write `GradeQuestionController`**

```php
<?php

namespace App\Http\Controllers\Api\Grading;

use App\Http\Controllers\Controller;
use App\Models\AssessmentQuestion;
use App\Models\Grade;
use App\Models\Submission;
use App\Services\Grading\ResultCalculator;
use Illuminate\Http\Request;

class GradeQuestionController extends Controller
{
    public function __invoke(Request $request, Submission $submission, AssessmentQuestion $assessmentQuestion)
    {
        $assessment = $submission->attempt->studentAssessment->assessment;
        ListSubmissionsController::authorizeScope($request, $assessment);

        $existing = Grade::where('submission_id', $submission->id)->where('assessment_question_id', $assessmentQuestion->id)->first();
        $alreadyPublished = optional($submission->result)->publication_status === 'published';

        $data = $request->validate([
            'score' => 'required|numeric|min:0|max:'.$assessmentQuestion->points,
            'reason' => $alreadyPublished ? 'required|string' : 'nullable|string',
        ]);

        if ($existing) {
            $before = $existing->only(['score', 'max_score']);
            $existing->update(['score' => $data['score'], 'max_score' => $assessmentQuestion->points, 'grading_method' => 'manual', 'graded_by_user_id' => $request->user()->id]);
            if ($alreadyPublished) {
                $existing->history()->create([
                    'before_data' => $before,
                    'after_data' => $existing->only(['score', 'max_score']),
                    'changed_by_user_id' => $request->user()->id,
                    'changed_at' => now(),
                    'reason' => $data['reason'],
                ]);
            }
            $grade = $existing;
        } else {
            $grade = Grade::create([
                'submission_id' => $submission->id,
                'assessment_question_id' => $assessmentQuestion->id,
                'score' => $data['score'],
                'max_score' => $assessmentQuestion->points,
                'grading_method' => 'manual',
                'graded_by_user_id' => $request->user()->id,
            ]);
        }

        $calculator = new ResultCalculator();
        $result = $calculator->recalculate($submission);
        $calculator->maybeAutoPublish($result, $request->user());

        return response()->json(['grade' => $grade, 'result' => $result]);
    }
}
```

- [ ] **Step 4: Write `GradeSubmissionController` (total-score variant)**

```php
<?php

namespace App\Http\Controllers\Api\Grading;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Submission;
use App\Services\Grading\ResultCalculator;
use Illuminate\Http\Request;

class GradeSubmissionController extends Controller
{
    public function __invoke(Request $request, Submission $submission)
    {
        $assessment = $submission->attempt->studentAssessment->assessment;
        ListSubmissionsController::authorizeScope($request, $assessment);

        $alreadyPublished = optional($submission->result)->publication_status === 'published';
        $maxScore = $submission->attempt->assessmentVersion->max_score ?? 100;

        $data = $request->validate([
            'score' => "required|numeric|min:0|max:{$maxScore}",
            'reason' => $alreadyPublished ? 'required|string' : 'nullable|string',
        ]);

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id, 'assessment_question_id' => null],
            ['score' => $data['score'], 'max_score' => $maxScore, 'grading_method' => 'manual', 'graded_by_user_id' => $request->user()->id]
        );

        $calculator = new ResultCalculator();
        $result = $calculator->recalculate($submission);
        $calculator->maybeAutoPublish($result, $request->user());

        return response()->json(['grade' => $grade, 'result' => $result]);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=GradeQuestionTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Grading/GradeQuestionController.php app/Http/Controllers/Api/Grading/GradeSubmissionController.php routes/api.php tests/Feature/Grading/GradeQuestionTest.php
git commit -m "feat: add manual grading endpoints with GradeHistory on post-publish regrade"
```

---

## Task 11: Feedback create/update + publish/withdraw

**Files:**
- Create: `app/Http/Controllers/Api/Grading/CreateFeedbackController.php`
- Create: `app/Http/Controllers/Api/Grading/UpdateFeedbackController.php`
- Create: `app/Http/Controllers/Api/Grading/SetFeedbackStatusController.php`
- Modify: `routes/api.php` — add `feedback` to UUID loop
- Test: `tests/Feature/Grading/FeedbackTest.php`

**Interfaces:**
- Consumes: `Feedback`/`FeedbackHistory` (Task 2), `ListSubmissionsController::authorizeScope()` (Task 9).
- Produces: `POST /api/v1/submissions/{submission}/feedback`, `PUT /api/v1/feedback/{feedback}`, `PATCH /api/v1/feedback/{feedback}`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Grading;

use App\Models\Attempt;
use App\Models\Feedback;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
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

    public function test_create_update_and_publish_feedback(): void
    {
        $this->actingAsAdmin();
        $attempt = Attempt::factory()->create();
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);

        $create = $this->postJson("/api/v1/submissions/{$submission->id}/feedback", [
            'body' => 'Làm bài tốt, chú ý câu 3',
            'visibility' => 'learner',
        ]);
        $create->assertCreated();
        $feedbackId = $create->json('feedback.id');

        $this->putJson("/api/v1/feedback/{$feedbackId}", ['body' => 'Làm bài tốt, chú ý lại câu 3'])->assertOk();

        $publish = $this->patchJson("/api/v1/feedback/{$feedbackId}", ['status' => 'published']);
        $publish->assertOk();
        $this->assertDatabaseHas('feedbacks', ['id' => $feedbackId, 'status' => 'published']);

        $this->putJson("/api/v1/feedback/{$feedbackId}", ['body' => 'Sửa lại sau khi publish'])->assertOk();
        $this->assertDatabaseHas('feedback_histories', ['feedback_id' => $feedbackId]);
    }
}
```

Run: `php artisan test --filter=FeedbackTest`
Expected: FAIL.

- [ ] **Step 2: Register routes and add `feedback` to UUID loop**

```php
Route::post('/submissions/{submission}/feedback', \App\Http\Controllers\Api\Grading\CreateFeedbackController::class);
Route::put('/feedback/{feedback}', \App\Http\Controllers\Api\Grading\UpdateFeedbackController::class);
Route::patch('/feedback/{feedback}', \App\Http\Controllers\Api\Grading\SetFeedbackStatusController::class);
```

- [ ] **Step 3: Write the three controllers**

```php
<?php

namespace App\Http\Controllers\Api\Grading;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Submission;
use Illuminate\Http\Request;

class CreateFeedbackController extends Controller
{
    public function __invoke(Request $request, Submission $submission)
    {
        ListSubmissionsController::authorizeScope($request, $submission->attempt->studentAssessment->assessment);

        $data = $request->validate([
            'body' => 'required|string',
            'visibility' => 'required|in:private,learner,parent_if_published',
            'grade_id' => 'nullable|uuid|exists:grades,id',
            'assessment_question_id' => 'nullable|uuid|exists:assessment_questions,id',
        ]);

        $feedback = Feedback::create($data + [
            'submission_id' => $submission->id,
            'author_user_id' => $request->user()->id,
            'status' => 'draft',
        ]);

        return response()->json(['feedback' => $feedback], 201);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Grading;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class UpdateFeedbackController extends Controller
{
    public function __invoke(Request $request, Feedback $feedback)
    {
        ListSubmissionsController::authorizeScope($request, $feedback->submission->attempt->studentAssessment->assessment);

        $data = $request->validate(['body' => 'required|string']);
        $wasPublished = $feedback->status === 'published';
        $before = $feedback->only('body');

        $feedback->update($data);

        if ($wasPublished) {
            $feedback->history()->create([
                'before_data' => $before,
                'after_data' => $feedback->only('body'),
                'changed_by_user_id' => $request->user()->id,
                'changed_at' => now(),
            ]);
        }

        return response()->json(['feedback' => $feedback]);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Grading;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class SetFeedbackStatusController extends Controller
{
    public function __invoke(Request $request, Feedback $feedback)
    {
        ListSubmissionsController::authorizeScope($request, $feedback->submission->attempt->studentAssessment->assessment);

        $data = $request->validate(['status' => 'required|in:draft,published,withdrawn']);

        $feedback->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? now() : $feedback->published_at,
        ]);

        return response()->json(['feedback' => $feedback]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=FeedbackTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Grading/CreateFeedbackController.php app/Http/Controllers/Api/Grading/UpdateFeedbackController.php app/Http/Controllers/Api/Grading/SetFeedbackStatusController.php routes/api.php tests/Feature/Grading/FeedbackTest.php
git commit -m "feat: add Feedback create/update/publish/withdraw endpoints"
```

---

## Task 12: Result publish/withdraw (explicit, single + bulk)

**Files:**
- Create: `app/Http/Controllers/Api/Result/PublishResultController.php`
- Create: `app/Http/Controllers/Api/Result/WithdrawResultController.php`
- Create: `app/Http/Controllers/Api/Result/BulkPublishResultsController.php`
- Modify: `routes/api.php` — add `result` to UUID loop
- Test: `tests/Feature/Result/PublishResultTest.php`

**Interfaces:**
- Consumes: `ResultCalculator::publish()`/`withdraw()` (Task 4), `ListSubmissionsController::authorizeScope()` (Task 9).
- Produces: `POST /api/v1/results/{result}/publish`, `POST /api/v1/results/{result}/withdraw`, `POST /api/v1/assessments/{assessment}/results/publish`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Result;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\Result;
use App\Models\Role;
use App\Models\StudentAssessment;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishResultTest extends TestCase
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

    public function test_cannot_publish_a_result_that_is_not_yet_complete(): void
    {
        $this->actingAsAdmin();
        $attempt = Attempt::factory()->create();
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'partial']);

        $this->postJson("/api/v1/results/{$result->id}/publish")->assertStatus(409);
    }

    public function test_bulk_publish_publishes_complete_results_and_skips_the_rest(): void
    {
        $this->actingAsAdmin();
        $assessment = Assessment::factory()->create();
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $sa1 = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'assigned_version_id' => $version->id]);
        $sa2 = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'assigned_version_id' => $version->id]);
        $attempt1 = Attempt::factory()->create(['student_assessment_id' => $sa1->id, 'assessment_version_id' => $version->id]);
        $attempt2 = Attempt::factory()->create(['student_assessment_id' => $sa2->id, 'assessment_version_id' => $version->id]);
        $sub1 = Submission::factory()->create(['attempt_id' => $attempt1->id]);
        $sub2 = Submission::factory()->create(['attempt_id' => $attempt2->id]);
        $complete = Result::factory()->create(['submission_id' => $sub1->id, 'grading_status' => 'complete']);
        $partial = Result::factory()->create(['submission_id' => $sub2->id, 'grading_status' => 'partial']);

        $response = $this->postJson("/api/v1/assessments/{$assessment->id}/results/publish");

        $response->assertOk();
        $response->assertJsonCount(1, 'published');
        $response->assertJsonCount(1, 'skipped');
        $this->assertSame('published', $complete->refresh()->publication_status);
        $this->assertSame('unpublished', $partial->refresh()->publication_status);
    }
}
```

Run: `php artisan test --filter=PublishResultTest`
Expected: FAIL.

- [ ] **Step 2: Register routes and add `result` to UUID loop**

```php
Route::post('/results/{result}/publish', \App\Http\Controllers\Api\Result\PublishResultController::class);
Route::post('/results/{result}/withdraw', \App\Http\Controllers\Api\Result\WithdrawResultController::class);
Route::post('/assessments/{assessment}/results/publish', \App\Http\Controllers\Api\Result\BulkPublishResultsController::class);
```

- [ ] **Step 3: Write the controllers**

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Grading\ListSubmissionsController;
use App\Models\Result;
use App\Services\Grading\ResultCalculator;
use Illuminate\Http\Request;

class PublishResultController extends Controller
{
    public function __invoke(Request $request, Result $result)
    {
        ListSubmissionsController::authorizeScope($request, $result->submission->attempt->studentAssessment->assessment);
        abort_if($result->grading_status !== 'complete', 409, 'Chưa chấm xong, không thể công bố.');

        (new ResultCalculator())->publish($result, $request->user());

        return response()->json(['result' => $result->refresh()]);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Grading\ListSubmissionsController;
use App\Models\Result;
use App\Services\Grading\ResultCalculator;
use Illuminate\Http\Request;

class WithdrawResultController extends Controller
{
    public function __invoke(Request $request, Result $result)
    {
        ListSubmissionsController::authorizeScope($request, $result->submission->attempt->studentAssessment->assessment);

        (new ResultCalculator())->withdraw($result, $request->user());

        return response()->json(['result' => $result->refresh()]);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Grading\ListSubmissionsController;
use App\Models\Assessment;
use App\Models\Result;
use App\Services\Grading\ResultCalculator;
use Illuminate\Http\Request;

class BulkPublishResultsController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        ListSubmissionsController::authorizeScope($request, $assessment);

        $results = Result::whereHas('submission.attempt.studentAssessment', fn ($q) => $q->where('assessment_id', $assessment->id))
            ->where('publication_status', 'unpublished')
            ->get();

        $calculator = new ResultCalculator();
        $published = [];
        $skipped = [];

        foreach ($results as $result) {
            if ($result->grading_status !== 'complete') {
                $skipped[] = ['id' => $result->id, 'reason' => 'grading chưa hoàn tất'];

                continue;
            }
            $calculator->publish($result, $request->user());
            $published[] = $result->id;
        }

        return response()->json(['published' => $published, 'skipped' => $skipped]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=PublishResultTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Result/PublishResultController.php app/Http/Controllers/Api/Result/WithdrawResultController.php app/Http/Controllers/Api/Result/BulkPublishResultsController.php routes/api.php tests/Feature/Result/PublishResultTest.php
git commit -m "feat: add explicit Result publish/withdraw and bulk-publish endpoints"
```

---

## Task 13: Student/Parent view Result

**Files:**
- Create: `app/Http/Controllers/Api/Result/GetMyResultController.php`
- Create: `app/Http/Controllers/Api/Result/GetChildResultController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Result/GetMyResultTest.php`

**Interfaces:**
- Consumes: `Result`, `ResultPublication` (Task 2), `AssessmentVersion.attempt_score_policy`/`show_answers`/`show_explanations`/`show_question_scores`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Result;

use App\Models\Attempt;
use App\Models\AssessmentVersion;
use App\Models\PersonProfile;
use App\Models\Result;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMyResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_see_an_unpublished_result(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $student = Student::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $user->id])->id]);
        $version = AssessmentVersion::factory()->create(['attempt_score_policy' => 'highest']);
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id, 'assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete', 'publication_status' => 'unpublished']);
        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $this->getJson("/api/v1/me/assessments/{$sa->assessment_id}/result")->assertStatus(404);
    }

    public function test_student_sees_highest_scoring_published_result_among_multiple_attempts(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $student = Student::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $user->id])->id]);
        $version = AssessmentVersion::factory()->create(['attempt_score_policy' => 'highest']);
        $sa = StudentAssessment::factory()->create(['student_id' => $student->id, 'assigned_version_id' => $version->id]);

        $attemptLow = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id, 'attempt_number' => 1]);
        $subLow = Submission::factory()->create(['attempt_id' => $attemptLow->id]);
        Result::factory()->create(['submission_id' => $subLow->id, 'score' => 5, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $attemptHigh = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id, 'attempt_number' => 2]);
        $subHigh = Submission::factory()->create(['attempt_id' => $attemptHigh->id]);
        Result::factory()->create(['submission_id' => $subHigh->id, 'score' => 9, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $response = $this->getJson("/api/v1/me/assessments/{$sa->assessment_id}/result");

        $response->assertOk();
        $response->assertJsonPath('result.score', 9);
        $response->assertJsonCount(2, 'attempts_history');
    }
}
```

Run: `php artisan test --filter=GetMyResultTest`
Expected: FAIL.

- [ ] **Step 2: Register routes**

```php
// role:student group
Route::get('/me/assessments/{assessment}/result', \App\Http\Controllers\Api\Result\GetMyResultController::class);
// role:parent group
Route::get('/me/children/{studentId}/assessments/{assessment}/result', \App\Http\Controllers\Api\Result\GetChildResultController::class)->whereUuid('studentId');
```

- [ ] **Step 3: Write a shared resolver + the two controllers**

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Models\Assessment;
use App\Models\Result;
use App\Models\StudentAssessment;

class ResolveStudentAssessmentResult
{
    public static function forStudent(Assessment $assessment, string $studentId): ?array
    {
        $sa = StudentAssessment::where('assessment_id', $assessment->id)->where('student_id', $studentId)->first();
        if (! $sa) {
            return null;
        }

        $published = Result::query()
            ->whereHas('submission.attempt', fn ($q) => $q->where('student_assessment_id', $sa->id))
            ->where('publication_status', 'published')
            ->with('submission.attempt')
            ->get();

        if ($published->isEmpty()) {
            return null;
        }

        $policy = $sa->assignedVersion->attempt_score_policy;
        $representative = $policy === 'highest'
            ? $published->sortByDesc('score')->first()
            : $published->sortByDesc(fn ($r) => $r->submission->attempt->attempt_number)->first();

        $version = $sa->assignedVersion;

        return [
            'result' => array_merge($representative->only(['id', 'score', 'max_score', 'percentage', 'passed']), [
                'show_answers' => $version->show_answers,
                'show_explanations' => $version->show_explanations,
                'show_question_scores' => $version->show_question_scores,
            ]),
            'attempts_history' => $published->map(fn ($r) => [
                'attempt_number' => $r->submission->attempt->attempt_number,
                'score' => $r->score,
                'percentage' => $r->percentage,
            ])->values(),
        ];
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\Request;

class GetMyResultController extends Controller
{
    public function __invoke(Request $request, Assessment $assessment)
    {
        $studentId = $request->user()->studentProfile->id;
        $data = ResolveStudentAssessmentResult::forStudent($assessment, $studentId);
        abort_unless($data, 404);

        return response()->json($data);
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\Request;

class GetChildResultController extends Controller
{
    public function __invoke(Request $request, string $studentId, Assessment $assessment)
    {
        $parentId = $request->user()->parentProfile->id;
        $linked = ParentStudentRelationship::where('parent_id', $parentId)->where('student_id', $studentId)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
            ->exists();
        abort_unless($linked, 404);

        $data = ResolveStudentAssessmentResult::forStudent($assessment, $studentId);
        abort_unless($data, 404);

        return response()->json($data);
    }
}
```

Adjust `ParentStudentRelationship` column names (`parent_id`/`student_id`/`valid_to`) to match the actual model from Module 1 if they differ — check `app/Models/ParentStudentRelationship.php` before writing this step; the spec's §7a access rule ("unlink mất quyền ngay") is what the `valid_to` check must enforce regardless of exact column names.

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=GetMyResultTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Result/*.php routes/api.php tests/Feature/Result/GetMyResultTest.php
git commit -m "feat: add Student/Parent Result view with attempt_score_policy resolution"
```

---

## Task 14: Progress (Student/Parent/Teacher)

**Files:**
- Create: `app/Http/Controllers/Api/Result/GetMyProgressController.php`
- Create: `app/Http/Controllers/Api/Result/GetChildProgressController.php`
- Create: `app/Http/Controllers/Api/Result/GetStudentProgressController.php`
- Create: `app/Services/Grading/ProgressCalculator.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Result/ProgressTest.php`

**Interfaces:**
- Consumes: `Result`, `Assessment.status` (Module 3).
- Produces: `ProgressCalculator::forStudent(string $studentId, array $filters = []): array` returning `['average_percentage' => ?float, 'by_subject' => [...], 'graded_count' => int, 'pending_count' => int]`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Result;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\PersonProfile;
use App\Models\Result;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_averages_only_published_results_of_non_cancelled_assessments(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'student']);
        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $student = Student::factory()->create(['person_profile_id' => PersonProfile::factory()->create(['user_id' => $user->id])->id]);

        $counted = Assessment::factory()->create(['status' => 'published']);
        $countedVersion = AssessmentVersion::factory()->create(['assessment_id' => $counted->id]);
        $countedSa = StudentAssessment::factory()->create(['assessment_id' => $counted->id, 'student_id' => $student->id, 'assigned_version_id' => $countedVersion->id]);
        $countedAttempt = Attempt::factory()->create(['student_assessment_id' => $countedSa->id, 'assessment_version_id' => $countedVersion->id]);
        $countedSub = Submission::factory()->create(['attempt_id' => $countedAttempt->id]);
        Result::factory()->create(['submission_id' => $countedSub->id, 'percentage' => 80, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $cancelled = Assessment::factory()->create(['status' => 'cancelled']);
        $cancelledVersion = AssessmentVersion::factory()->create(['assessment_id' => $cancelled->id]);
        $cancelledSa = StudentAssessment::factory()->create(['assessment_id' => $cancelled->id, 'student_id' => $student->id, 'assigned_version_id' => $cancelledVersion->id]);
        $cancelledAttempt = Attempt::factory()->create(['student_assessment_id' => $cancelledSa->id, 'assessment_version_id' => $cancelledVersion->id]);
        $cancelledSub = Submission::factory()->create(['attempt_id' => $cancelledAttempt->id]);
        Result::factory()->create(['submission_id' => $cancelledSub->id, 'percentage' => 20, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $pending = Assessment::factory()->create(['status' => 'published']);
        $pendingVersion = AssessmentVersion::factory()->create(['assessment_id' => $pending->id]);
        $pendingSa = StudentAssessment::factory()->create(['assessment_id' => $pending->id, 'student_id' => $student->id, 'assigned_version_id' => $pendingVersion->id]);
        $pendingAttempt = Attempt::factory()->create(['student_assessment_id' => $pendingSa->id, 'assessment_version_id' => $pendingVersion->id]);
        $pendingSub = Submission::factory()->create(['attempt_id' => $pendingAttempt->id]);
        Result::factory()->create(['submission_id' => $pendingSub->id, 'grading_status' => 'partial', 'publication_status' => 'unpublished']);

        $this->actingAs($user)->withSession(['active_role_code' => 'student']);

        $response = $this->getJson('/api/v1/me/progress');

        $response->assertOk();
        $response->assertJsonPath('average_percentage', 80.0);
        $response->assertJsonPath('pending_count', 1);
    }
}
```

Run: `php artisan test --filter=ProgressTest`
Expected: FAIL.

- [ ] **Step 2: Write `ProgressCalculator`**

```php
<?php

namespace App\Services\Grading;

use App\Models\Result;

class ProgressCalculator
{
    public function forStudent(string $studentId, array $filters = []): array
    {
        $base = Result::query()
            ->whereHas('submission.attempt.studentAssessment', function ($q) use ($studentId, $filters) {
                $q->where('student_id', $studentId)
                    ->whereHas('assessment', fn ($aq) => $aq->where('status', '!=', 'cancelled'));
                if (! empty($filters['class_id'])) {
                    $q->where('source_class_id', $filters['class_id']);
                }
                if (! empty($filters['course_id'])) {
                    $q->where('source_course_id', $filters['course_id']);
                }
            });

        $published = (clone $base)->where('publication_status', 'published')->get();
        $pendingCount = (clone $base)->where('grading_status', '!=', 'complete')->count();

        $averagePercentage = $published->isNotEmpty() ? round((float) $published->avg('percentage'), 2) : null;

        return [
            'average_percentage' => $averagePercentage,
            'graded_count' => $published->count(),
            'pending_count' => $pendingCount,
        ];
    }
}
```

- [ ] **Step 3: Register routes and write the three controllers**

```php
// role:student group
Route::get('/me/progress', \App\Http\Controllers\Api\Result\GetMyProgressController::class);
// role:parent group
Route::get('/me/children/{studentId}/progress', \App\Http\Controllers\Api\Result\GetChildProgressController::class)->whereUuid('studentId');
// role:admin,teacher group
Route::get('/students/{student}/progress', \App\Http\Controllers\Api\Result\GetStudentProgressController::class);
```

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Services\Grading\ProgressCalculator;
use Illuminate\Http\Request;

class GetMyProgressController extends Controller
{
    public function __invoke(Request $request)
    {
        $studentId = $request->user()->studentProfile->id;
        $filters = $request->only(['class_id', 'course_id']);

        return response()->json((new ProgressCalculator())->forStudent($studentId, $filters));
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentRelationship;
use App\Services\Grading\ProgressCalculator;
use Illuminate\Http\Request;

class GetChildProgressController extends Controller
{
    public function __invoke(Request $request, string $studentId)
    {
        $parentId = $request->user()->parentProfile->id;
        $linked = ParentStudentRelationship::where('parent_id', $parentId)->where('student_id', $studentId)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
            ->exists();
        abort_unless($linked, 404);

        return response()->json((new ProgressCalculator())->forStudent($studentId, $request->only(['class_id', 'course_id'])));
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api\Result;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Grading\ListSubmissionsController;
use App\Models\Course;
use App\Models\Student;
use App\Services\Grading\ProgressCalculator;
use Illuminate\Http\Request;

class GetStudentProgressController extends Controller
{
    public function __invoke(Request $request, Student $student)
    {
        $activeRole = $request->session()->get('active_role_code');
        if ($activeRole !== 'admin') {
            $teacher = $request->user()->teacherProfile;
            abort_unless($teacher, 404);
            $inScope = $teacher->activeClassIds()->intersect($student->activeClassIds())->isNotEmpty()
                || Course::where('owner_teacher_id', $teacher->id)->whereHas('enrollments', fn ($q) => $q->where('student_id', $student->id)->where('status', 'active'))->exists();
            abort_unless($inScope, 404);
        }

        return response()->json((new ProgressCalculator())->forStudent($student->id, $request->only(['class_id', 'course_id'])));
    }
}
```

Again, verify `ParentStudentRelationship`'s actual column names against `app/Models/ParentStudentRelationship.php` before finalizing this step (same caveat as Task 13).

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ProgressTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Grading/ProgressCalculator.php app/Http/Controllers/Api/Result/GetMyProgressController.php app/Http/Controllers/Api/Result/GetChildProgressController.php app/Http/Controllers/Api/Result/GetStudentProgressController.php routes/api.php tests/Feature/Result/ProgressTest.php
git commit -m "feat: add Progress endpoints for Student/Parent/Teacher"
```

---

## Task 15: Admin — reissue Attempt due to incident

**Files:**
- Create: `app/Http/Controllers/Api/Submission/ReissueAttemptController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Submission/ReissueAttemptTest.php`

**Interfaces:**
- Consumes: `Attempt::class` (Task 1), `Submission::class` (Task 1 — for `invalidated` status).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Submission;

use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\Role;
use App\Models\StudentAssessment;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReissueAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reissues_an_exam_attempt_without_consuming_max_attempts(): void
    {
        $admin = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($admin)->withSession(['active_role_code' => 'admin']);

        $version = AssessmentVersion::factory()->create(['type' => 'exam', 'max_attempts' => 1]);
        $sa = StudentAssessment::factory()->create(['assigned_version_id' => $version->id]);
        $oldAttempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id, 'attempt_number' => 1, 'status' => 'in_progress']);
        $submission = Submission::factory()->create(['attempt_id' => $oldAttempt->id, 'status' => 'graded']);

        $response = $this->postJson("/api/v1/attempts/{$oldAttempt->id}/reissue", ['reason' => 'mất mạng giữa giờ thi, đã xác nhận với giáo viên']);

        $response->assertOk();
        $this->assertDatabaseHas('attempts', ['id' => $oldAttempt->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('submissions', ['id' => $submission->id, 'status' => 'invalidated']);
        $newAttemptId = $response->json('attempt.id');
        $this->assertDatabaseHas('attempts', ['id' => $newAttemptId, 'student_assessment_id' => $sa->id, 'attempt_number' => 2]);
        $this->assertDatabaseHas('attempts', ['id' => $oldAttempt->id, 'replacement_attempt_id' => $newAttemptId]);
    }

    public function test_reissue_requires_a_reason(): void
    {
        $admin = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($admin)->withSession(['active_role_code' => 'admin']);

        $version = AssessmentVersion::factory()->create(['type' => 'exam']);
        $sa = StudentAssessment::factory()->create(['assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id]);

        $this->postJson("/api/v1/attempts/{$attempt->id}/reissue")->assertStatus(422);
    }

    public function test_reissue_rejects_assignment_type_attempts(): void
    {
        $admin = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($admin)->withSession(['active_role_code' => 'admin']);

        $version = AssessmentVersion::factory()->create(['type' => 'assignment']);
        $sa = StudentAssessment::factory()->create(['assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id]);

        $this->postJson("/api/v1/attempts/{$attempt->id}/reissue", ['reason' => 'sự cố'])->assertStatus(409);
    }
}
```

Run: `php artisan test --filter=ReissueAttemptTest`
Expected: FAIL (route not found).

- [ ] **Step 2: Register route (new `role:admin` entry)**

```php
// routes/api.php, inside the existing role:admin group
Route::post('/attempts/{attempt}/reissue', \App\Http\Controllers\Api\Submission\ReissueAttemptController::class);
```

- [ ] **Step 3: Write the controller**

```php
<?php

namespace App\Http\Controllers\Api\Submission;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReissueAttemptController extends Controller
{
    public function __invoke(Request $request, Attempt $attempt)
    {
        abort_if($attempt->assessmentVersion->type === 'assignment', 409, 'Chỉ áp dụng cho Quiz/Exam.');

        $data = $request->validate(['reason' => 'required|string']);

        $newAttempt = DB::transaction(function () use ($attempt, $data) {
            if ($attempt->submission) {
                $attempt->submission->update(['status' => 'invalidated']);
            }

            $usedAttempts = Attempt::where('student_assessment_id', $attempt->student_assessment_id)->where('status', '!=', 'cancelled')->count();

            $newAttempt = Attempt::create([
                'student_assessment_id' => $attempt->student_assessment_id,
                'assessment_version_id' => $attempt->assessment_version_id,
                'attempt_number' => $usedAttempts + 1,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            $attempt->update([
                'status' => 'cancelled',
                'cancel_reason' => $data['reason'],
                'replacement_attempt_id' => $newAttempt->id,
            ]);

            foreach ($attempt->assessmentVersion->questions as $question) {
                $newAttempt->attemptQuestions()->create(['assessment_question_id' => $question->id, 'position' => $question->position]);
            }

            return $newAttempt;
        });

        return response()->json(['attempt' => $newAttempt]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ReissueAttemptTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/Submission/ReissueAttemptController.php routes/api.php tests/Feature/Submission/ReissueAttemptTest.php
git commit -m "feat: add Admin POST /attempts/{attempt}/reissue for exam incidents"
```

---

## Task 16: Full test suite run + final review

**Files:** none (verification task).

- [ ] **Step 1: Run the entire suite**

Run: `php artisan test`
Expected: all tests pass (Module 1–3 tests unaffected, all new Module 4 tests green).

- [ ] **Step 2: Request a fresh-context review**

Use `superpowers:requesting-code-review` (fresh reviewer, strongest available model) against the full diff of this feature branch — same process used for Plans 1–4. Fix any Critical/Important findings with TDD (failing test first), then re-run the full suite.

- [ ] **Step 3: Commit any review fixes, then update `docs/progress.md`**

Mark Module 4 BE as done in the Step 9 checklist (same format as Modules 2/3's entries), noting the final test count and any deferred Minor findings.
