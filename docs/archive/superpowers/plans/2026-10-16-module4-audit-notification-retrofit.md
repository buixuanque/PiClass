# Module 4 Audit + Notification Retrofit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gắn `AuditLogger::record()`/`NotificationDispatcher::dispatch()` (đã có từ Module 5) vào 6 controller Module 4 (Grading/Result/Submission) đúng theo bảng spec §4/§6, và nâng 2 cột uuid "lỏng" (`notification_contexts.result_id`, `attachment_links.answer_id`/`feedback_id`) lên FK thật giờ các bảng đích (`results`, `answers`, `feedbacks`) đã tồn tại.

**Architecture:** Audit cho Grade/Attempt gọi trực tiếp inline trong controller (giống mọi retrofit Module 1-3 đã làm). Audit+Notification cho Result publish/withdraw đặt trong `ResultCalculator::publish()`/`::withdraw()` (service dùng chung) thay vì lặp lại ở 3 controller riêng — xem Ruling ở Task 2. FK bổ sung qua 1 migration `Schema::table(...)->foreign(...)`.

**Tech Stack:** Laravel 11 + PostgreSQL, Sanctum, PHPUnit (`php artisan test`), Docker (chạy qua `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test ..."`).

**Spec:** `docs/archive/superpowers/specs/2026-10-15-notification-file-audit-api-design.md` §4 (bảng Audit, 3 dòng Module 4), §6 (bảng Notification, 1 dòng Module 4: `PublishResultController`/`BulkPublishResultsController`).

## Global Constraints

- Validate+authorize inline trong controller/service hiện có — không Form Request/Policy/Resource (convention chung cả repo).
- `AuditLogger`/`NotificationDispatcher` gọi trực tiếp inline (không qua Model Observer/Event) — quyết định đã chốt ở spec Module 5 §8, giữ nguyên cho retrofit này.
- Notification dedupe qua `Notification::firstOrCreate(['recipient_user_id'=>...,'event_key'=>...])` bên trong `NotificationDispatcher::dispatch()` — không bao giờ tự pre-check rồi insert thủ công.
- Parent chỉ là recipient khi `ParentStudentRelationship.status === 'active'` — đúng điều kiện `GetChildResultController`/`ClassScope::studentAndParentRecipients()` đã dùng, không phát sinh điều kiện mới.
- `reason` bắt buộc đúng theo bảng spec §4: Grade khi Result của Submission đã `publication_status=published` → bắt buộc; `PublishResultController`/`WithdrawResultController`/`BulkPublishResultsController` → không bắt buộc; `ReissueAttemptController` → bắt buộc (request đã validate sẵn, chỉ truyền lại vào Audit).
- Không đổi hành vi nghiệp vụ hiện tại của 6 controller/service đã có — chỉ thêm lệnh ghi Audit/Notification ngay sau khi action chính đã thành công, không chặn/thay đổi response hiện tại.

## Review Focus

1. **Auto-publish qua `ResultCalculator::maybeAutoPublish()`** (gọi từ `GradeQuestionController`/`GradeSubmissionController` khi `result_release_at` đã qua, không đi qua `PublishResultController`) vẫn phải ghi đúng `AuditLog` (`result.publish`) + `Notification` như publish thủ công — nếu đặt logic Audit/Notification trong controller thay vì `ResultCalculator::publish()` sẽ bị sót case này hoàn toàn. Task 2 pin bằng test gọi grade qua `GradeQuestionController`, không gọi `/results/{id}/publish`.
2. **`BulkPublishResultsController` publish nhiều Result trong 1 request** — mỗi Result publish được phải có `AuditLog`+`Notification` RIÊNG (không phải 1 log tổng cho cả batch), và các Result nằm trong `$skipped` (chưa chấm xong) KHÔNG được ghi log/notify. Task 2 pin bằng test có cả result published và result skipped trong cùng 1 request.
3. **Audit cho Grade khi Result đã `published`** — `before`/`after` phải là giá trị điểm cũ/mới thật (không phải ghi `after` hai lần), và action phải fail trước nếu thiếu `reason` (request validate đã có từ trước, Audit chỉ ăn theo). Task 1 pin bằng test ghi điểm lần 2 có `reason` và so khớp đúng `before`/`after` trong `audit_logs`.
4. **`ReissueAttemptController` phải ghi `entity_id` = Attempt CŨ bị thay thế**, không phải Attempt mới tạo — để truy vết đúng "attempt nào bị huỷ và vì sao". Task 3 pin bằng test so khớp `entity_id` với `$oldAttempt->id`, không phải attempt mới.
5. **Notification "Công bố kết quả" khi Result bị publish lần 2 sau khi đã withdraw trước đó** — vì `event_key` dùng chung `result_id` không đổi, `firstOrCreate` sẽ coi lần publish thứ 2 là trùng sự kiện cũ (không tạo `Notification` mới, không báo lại cho Student/Parent). Đây là hành vi được CHẤP NHẬN có chủ đích cho V1 (xem Ruling Task 2), không phải bug — Task 2 không viết test cho case này vì kết quả mong đợi (im lặng) trùng với test "withdraw không notify", nhưng plan ghi rõ để reviewer không báo nhầm là thiếu test.

---

### Task 1: Audit cho Grade (GradeQuestionController + GradeSubmissionController)

**Files:**
- Modify: `app/Http/Controllers/Api/Grading/GradeQuestionController.php:19-49`
- Modify: `app/Http/Controllers/Api/Grading/GradeSubmissionController.php:17-29`
- Test: `tests/Feature/Audit/GradingAuditTest.php` (file mới)

**Interfaces:**
- Consumes: `(new \App\Services\Audit\AuditLogger())->record(actor: ?User, action: string, entityType: string, entityId: string, before: ?array = null, after: ?array = null, reason: ?string = null, correlationKey: ?string = null): AuditLog` — signature đã có, không đổi.
- Produces: không có interface mới cho task sau.

- [ ] **Step 1: Viết test RED cho GradeQuestionController**

Tạo file `tests/Feature/Audit/GradingAuditTest.php`:

```php
<?php

namespace Tests\Feature\Audit;

use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\Grade;
use App\Models\Result;
use App\Models\Role;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingAuditTest extends TestCase
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

    public function test_grading_a_question_for_the_first_time_writes_a_grade_create_audit_log(): void
    {
        $admin = $this->actingAsAdmin();
        $version = AssessmentVersion::factory()->create();
        $essay = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'question_type' => 'essay', 'points' => 10]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);

        $this->putJson("/api/v1/submissions/{$submission->id}/questions/{$essay->id}/grade", ['score' => 8])->assertOk();

        $grade = Grade::where('submission_id', $submission->id)->where('assessment_question_id', $essay->id)->first();
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'grade.create',
            'entity_type' => 'Grade',
            'entity_id' => $grade->id,
            'reason' => null,
        ]);
    }

    public function test_regrading_a_published_results_question_requires_a_reason_and_writes_a_grade_update_audit_log(): void
    {
        $admin = $this->actingAsAdmin();
        $version = AssessmentVersion::factory()->create();
        $essay = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'question_type' => 'essay', 'points' => 10]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $grade = Grade::factory()->create(['submission_id' => $submission->id, 'assessment_question_id' => $essay->id, 'score' => 6, 'max_score' => 10, 'grading_method' => 'manual']);
        Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $this->putJson("/api/v1/submissions/{$submission->id}/questions/{$essay->id}/grade", ['score' => 9, 'reason' => 'chấm lại theo thang điểm mới'])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'grade.update',
            'entity_type' => 'Grade',
            'entity_id' => $grade->id,
            'reason' => 'chấm lại theo thang điểm mới',
        ]);
    }

    public function test_grading_a_submission_directly_writes_a_grade_create_audit_log(): void
    {
        $admin = $this->actingAsAdmin();
        $version = AssessmentVersion::factory()->create(['max_score' => 20]);
        $attempt = Attempt::factory()->create(['assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);

        $this->putJson("/api/v1/submissions/{$submission->id}/grade", ['score' => 15])->assertOk();

        $grade = Grade::where('submission_id', $submission->id)->whereNull('assessment_question_id')->first();
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'grade.create',
            'entity_type' => 'Grade',
            'entity_id' => $grade->id,
        ]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter=GradingAuditTest"`
Expected: 3 failed — không có row nào trong `audit_logs` (2 controller chưa gọi `AuditLogger`).

- [ ] **Step 3: Sửa `GradeQuestionController.php`**

Thay nguyên đoạn dòng 19-49 (từ `$existing = Grade::where(...)` tới hết nhánh `else`) bằng:

```php
        $existing = Grade::where('submission_id', $submission->id)->where('assessment_question_id', $assessmentQuestion->id)->first();
        $alreadyPublished = optional($submission->result)->publication_status === 'published';

        $data = $request->validate([
            'score' => 'required|numeric|min:0|max:'.$assessmentQuestion->points,
            'reason' => $alreadyPublished ? 'required|string' : 'nullable|string',
        ]);

        $auditBefore = $existing?->only(['score', 'max_score']);

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

        (new \App\Services\Audit\AuditLogger())->record(
            actor: $request->user(),
            action: $existing ? 'grade.update' : 'grade.create',
            entityType: 'Grade',
            entityId: $grade->id,
            before: $auditBefore,
            after: $grade->only(['score', 'max_score']),
            reason: $data['reason'] ?? null,
        );
```

- [ ] **Step 4: Sửa `GradeSubmissionController.php`**

Thay nguyên đoạn dòng 17-29 (từ `$alreadyPublished = ...` tới hết `Grade::updateOrCreate(...)`) bằng:

```php
        $alreadyPublished = optional($submission->result)->publication_status === 'published';
        $version = $submission->attempt->assessmentVersion;
        $maxScore = $version->max_score ?? $version->questions->sum('points');

        $data = $request->validate([
            'score' => "required|numeric|min:0|max:{$maxScore}",
            'reason' => $alreadyPublished ? 'required|string' : 'nullable|string',
        ]);

        $existing = Grade::where('submission_id', $submission->id)->where('assessment_question_id', null)->first();
        $auditBefore = $existing?->only(['score', 'max_score']);

        $grade = Grade::updateOrCreate(
            ['submission_id' => $submission->id, 'assessment_question_id' => null],
            ['score' => $data['score'], 'max_score' => $maxScore, 'grading_method' => 'manual', 'graded_by_user_id' => $request->user()->id]
        );

        (new \App\Services\Audit\AuditLogger())->record(
            actor: $request->user(),
            action: $existing ? 'grade.update' : 'grade.create',
            entityType: 'Grade',
            entityId: $grade->id,
            before: $auditBefore,
            after: $grade->only(['score', 'max_score']),
            reason: $data['reason'] ?? null,
        );
```

- [ ] **Step 5: Chạy test, xác nhận PASS**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter=GradingAuditTest"`
Expected: 3 passed.

- [ ] **Step 6: Chạy toàn bộ test Grading hiện có để chắc không phá hành vi cũ**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter='GradeQuestionTest|GradeSubmissionTest|GradeUniquenessTest'"`
Expected: tất cả PASS (không có test nào trước đó assert số lượng query/response shape bị ảnh hưởng bởi audit log thêm vào).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Api/Grading/GradeQuestionController.php app/Http/Controllers/Api/Grading/GradeSubmissionController.php tests/Feature/Audit/GradingAuditTest.php
git commit -m "feat: retrofit AuditLog into Grade create/update controllers"
```

---

### Task 2: Audit + Notification cho Result publish/withdraw (trong `ResultCalculator`)

**Files:**
- Modify: `app/Services/Grading/ResultCalculator.php:87-107`
- Test: `tests/Feature/Audit/ResultAuditTest.php` (file mới)
- Test: `tests/Feature/Notification/ResultNotificationTest.php` (file mới)

**Interfaces:**
- Consumes: `AuditLogger::record(...)` (như Task 1); `(new \App\Services\Notification\NotificationDispatcher())->dispatch(recipients: Collection<User>, eventKey: string, eventType: string, title: string, body: string, context: array): void`; `\App\Support\ClassScope::studentAndParentRecipients(iterable $studentIds): Collection` — cả hai đã có từ Module 5, không đổi signature.
- Produces: không có interface mới cho task sau.

**Ruling (ghi ngay, không chờ review cuối):** spec §4/§6 liệt kê action theo TÊN CONTROLLER (`PublishResultController`, `WithdrawResultController`, `BulkPublishResultsController`), nhưng cả 3 controller này chỉ gọi `ResultCalculator::publish()`/`::withdraw()` — và `ResultCalculator::maybeAutoPublish()` (gọi từ `GradeQuestionController`/`GradeSubmissionController` khi auto-release) cũng gọi `publish()` cùng một hàm. Đặt Audit+Notification bên trong `ResultCalculator::publish()`/`::withdraw()` (service dùng chung) thay vì lặp lại ở 3 controller riêng + bỏ sót auto-publish — vì tinh thần của spec là "mọi lần Result được publish phải audit+notify", tên controller trong bảng chỉ là nơi hành động THƯỜNG xuất phát, không phải giới hạn duy nhất. Cách này cũng tránh lặp code 3 lần (DRY, không phải style — cả 3 controller gọi đúng 1 hàm). Cost nếu sai: nếu business thực ra chỉ muốn audit khi publish THỦ CÔNG (không phải auto-publish), sẽ có audit log/notification dư cho auto-publish — sửa bằng cách thêm 1 flag `$viaAutoPublish` vào `maybeAutoPublish()` để tắt riêng, không cần đổi kiến trúc.

**Ruling 2:** `event_key` cho Notification publish dùng `"result:{$result->id}:published"` (không kèm timestamp) — nghĩa là nếu 1 Result bị withdraw rồi publish lại, lần publish thứ 2 KHÔNG tạo Notification mới (do `firstOrCreate` trùng `event_key`, xem Review Focus #5). Chấp nhận cho V1 vì publish-withdraw-republish là edge case hiếm (thường chỉ xảy ra khi phát hiện lỗi chấm điểm), và Student/Parent đã biết "có kết quả" từ lần publish đầu. Cost nếu sai: Parent/Student không được báo lại khi Result được publish lại sau khi sửa — nếu phát sinh nhu cầu thật, thêm timestamp vào `event_key` sau.

- [ ] **Step 1: Viết test RED cho Audit**

Tạo file `tests/Feature/Audit/ResultAuditTest.php`:

```php
<?php

namespace Tests\Feature\Audit;

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

class ResultAuditTest extends TestCase
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

    public function test_publishing_a_result_writes_an_audit_log(): void
    {
        $admin = $this->actingAsAdmin();
        $attempt = Attempt::factory()->create();
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete']);

        $this->postJson("/api/v1/results/{$result->id}/publish")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'result.publish',
            'entity_type' => 'Result',
            'entity_id' => $result->id,
        ]);
    }

    public function test_withdrawing_a_result_writes_an_audit_log(): void
    {
        $admin = $this->actingAsAdmin();
        $attempt = Attempt::factory()->create();
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $this->postJson("/api/v1/results/{$result->id}/withdraw")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'result.withdraw',
            'entity_type' => 'Result',
            'entity_id' => $result->id,
        ]);
    }

    public function test_bulk_publishing_writes_one_audit_log_per_published_result_and_none_for_skipped_ones(): void
    {
        $admin = $this->actingAsAdmin();
        $assessment = Assessment::factory()->create();
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id]);
        $sa1 = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'assigned_version_id' => $version->id]);
        $sa2 = StudentAssessment::factory()->create(['assessment_id' => $assessment->id, 'assigned_version_id' => $version->id]);
        $attempt1 = Attempt::factory()->create(['student_assessment_id' => $sa1->id, 'assessment_version_id' => $version->id]);
        $attempt2 = Attempt::factory()->create(['student_assessment_id' => $sa2->id, 'assessment_version_id' => $version->id]);
        $sub1 = Submission::factory()->create(['attempt_id' => $attempt1->id]);
        $sub2 = Submission::factory()->create(['attempt_id' => $attempt2->id]);
        $complete = Result::factory()->create(['submission_id' => $sub1->id, 'grading_status' => 'complete']);
        $skipped = Result::factory()->create(['submission_id' => $sub2->id, 'grading_status' => 'partial']);

        $this->postJson("/api/v1/assessments/{$assessment->id}/results/publish")->assertOk();

        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'Result', 'entity_id' => $complete->id, 'action' => 'result.publish']);
        $this->assertDatabaseMissing('audit_logs', ['entity_type' => 'Result', 'entity_id' => $skipped->id]);
    }
}
```

- [ ] **Step 2: Viết test RED cho Notification**

Tạo file `tests/Feature/Notification/ResultNotificationTest.php`:

```php
<?php

namespace Tests\Feature\Notification;

use App\Models\AssessmentQuestion;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
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

class ResultNotificationTest extends TestCase
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

    public function test_publishing_a_result_notifies_the_student_and_their_active_linked_parent(): void
    {
        $this->actingAsAdmin();

        $studentProfile = PersonProfile::factory()->create();
        $studentUser = User::factory()->create(['person_profile_id' => $studentProfile->id]);
        $student = Student::factory()->create(['person_profile_id' => $studentProfile->id]);

        $parentProfile = PersonProfile::factory()->create();
        $parentUser = User::factory()->create(['person_profile_id' => $parentProfile->id]);
        $parent = ParentProfile::factory()->create(['person_profile_id' => $parentProfile->id]);
        ParentStudentRelationship::create([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'status' => 'active',
            'valid_from' => now()->subDay(), 'confirmed_by_user_id' => $parentUser->id, 'confirmed_at' => now(),
        ]);

        $version = AssessmentVersion::factory()->create();
        $studentAssessment = StudentAssessment::factory()->create(['student_id' => $student->id, 'assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $studentAssessment->id, 'assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete']);

        $this->postJson("/api/v1/results/{$result->id}/publish")->assertOk();

        $this->assertDatabaseHas('notifications', ['recipient_user_id' => $studentUser->id, 'event_type' => 'result_published']);
        $this->assertDatabaseHas('notifications', ['recipient_user_id' => $parentUser->id, 'event_type' => 'result_published']);
        $this->assertDatabaseHas('notification_contexts', ['result_id' => $result->id]);
    }

    public function test_withdrawing_a_published_result_does_not_notify_anyone(): void
    {
        $this->actingAsAdmin();

        $studentProfile = PersonProfile::factory()->create();
        $studentUser = User::factory()->create(['person_profile_id' => $studentProfile->id]);
        $student = Student::factory()->create(['person_profile_id' => $studentProfile->id]);

        $version = AssessmentVersion::factory()->create();
        $studentAssessment = StudentAssessment::factory()->create(['student_id' => $student->id, 'assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $studentAssessment->id, 'assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete', 'publication_status' => 'published']);

        $this->postJson("/api/v1/results/{$result->id}/withdraw")->assertOk();

        $this->assertDatabaseMissing('notifications', ['recipient_user_id' => $studentUser->id, 'event_type' => 'result_published']);
    }

    public function test_auto_publishing_a_result_via_grading_still_notifies_the_student(): void
    {
        $this->actingAsAdmin();

        $studentProfile = PersonProfile::factory()->create();
        $studentUser = User::factory()->create(['person_profile_id' => $studentProfile->id]);
        $student = Student::factory()->create(['person_profile_id' => $studentProfile->id]);

        $version = AssessmentVersion::factory()->create(['result_release_at' => now()->subMinute()]);
        $essay = AssessmentQuestion::factory()->create(['assessment_version_id' => $version->id, 'question_type' => 'essay', 'points' => 10]);
        $studentAssessment = StudentAssessment::factory()->create(['student_id' => $student->id, 'assigned_version_id' => $version->id]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $studentAssessment->id, 'assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);

        $this->putJson("/api/v1/submissions/{$submission->id}/questions/{$essay->id}/grade", ['score' => 8])->assertOk();

        $this->assertDatabaseHas('notifications', ['recipient_user_id' => $studentUser->id, 'event_type' => 'result_published']);
    }
}
```

- [ ] **Step 3: Chạy cả 2 file test, xác nhận FAIL**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter='ResultAuditTest|ResultNotificationTest'"`
Expected: 6 failed (3 ResultAuditTest + 3 ResultNotificationTest) — `ResultCalculator` chưa gọi `AuditLogger`/`NotificationDispatcher`.

- [ ] **Step 4: Sửa `ResultCalculator.php`**

Thay nguyên đoạn dòng 87-107 (2 method `publish`/`withdraw`) bằng:

```php
    public function publish(Result $result, User $actor): void
    {
        $result->update(['publication_status' => 'published']);
        ResultPublication::create([
            'result_id' => $result->id,
            'action' => 'published',
            'published_at' => now(),
            'actor_user_id' => $actor->id,
        ]);

        (new \App\Services\Audit\AuditLogger())->record(
            actor: $actor,
            action: 'result.publish',
            entityType: 'Result',
            entityId: $result->id,
            after: ['publication_status' => 'published'],
        );

        $studentId = $result->submission->attempt->studentAssessment->student_id;
        (new \App\Services\Notification\NotificationDispatcher())->dispatch(
            recipients: \App\Support\ClassScope::studentAndParentRecipients([$studentId]),
            eventKey: "result:{$result->id}:published",
            eventType: 'result_published',
            title: 'Kết quả bài làm đã được công bố',
            body: 'Kết quả của bạn đã được công bố, hãy vào xem chi tiết.',
            context: ['result_id' => $result->id],
        );
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

        (new \App\Services\Audit\AuditLogger())->record(
            actor: $actor,
            action: 'result.withdraw',
            entityType: 'Result',
            entityId: $result->id,
            after: ['publication_status' => 'unpublished'],
        );
    }
```

- [ ] **Step 5: Chạy test, xác nhận PASS**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter='ResultAuditTest|ResultNotificationTest'"`
Expected: 6 passed.

- [ ] **Step 6: Chạy toàn bộ test Result/Grading hiện có để chắc không phá hành vi cũ**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter='PublishResultTest|GetChildResultAndProgressTest|GetMyResultTest|GradeQuestionTest|GradeSubmissionTest'"`
Expected: tất cả PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Grading/ResultCalculator.php tests/Feature/Audit/ResultAuditTest.php tests/Feature/Notification/ResultNotificationTest.php
git commit -m "feat: retrofit AuditLog + Notification into ResultCalculator publish/withdraw"
```

---

### Task 3: Audit cho ReissueAttemptController

**Files:**
- Modify: `app/Http/Controllers/Api/Submission/ReissueAttemptController.php:13-68`
- Test: `tests/Feature/Audit/ReissueAttemptAuditTest.php` (file mới)

**Interfaces:**
- Consumes: `AuditLogger::record(...)` (như Task 1), không đổi.
- Produces: không có interface mới cho task sau.

- [ ] **Step 1: Viết test RED**

Tạo file `tests/Feature/Audit/ReissueAttemptAuditTest.php`:

```php
<?php

namespace Tests\Feature\Audit;

use App\Models\Assessment;
use App\Models\AssessmentVersion;
use App\Models\Attempt;
use App\Models\Role;
use App\Models\StudentAssessment;
use App\Models\Submission;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReissueAttemptAuditTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create(['user_id' => $admin->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        $this->actingAs($admin)->withSession(['active_role_code' => 'admin']);

        return $admin;
    }

    public function test_reissuing_an_attempt_writes_an_audit_log_against_the_old_attempt_with_the_reason(): void
    {
        $admin = $this->actingAsAdmin();

        $assessment = Assessment::factory()->create(['type' => 'exam']);
        $version = AssessmentVersion::factory()->create(['assessment_id' => $assessment->id, 'max_attempts' => 1]);
        $sa = StudentAssessment::factory()->create(['assigned_version_id' => $version->id]);
        $oldAttempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id, 'attempt_number' => 1, 'status' => 'in_progress']);
        Submission::factory()->create(['attempt_id' => $oldAttempt->id, 'status' => 'graded']);

        $response = $this->postJson("/api/v1/attempts/{$oldAttempt->id}/reissue", ['reason' => 'mất mạng giữa giờ thi']);
        $response->assertOk();
        $newAttemptId = $response->json('attempt.id');

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'attempt.reissue',
            'entity_type' => 'Attempt',
            'entity_id' => $oldAttempt->id,
            'reason' => 'mất mạng giữa giờ thi',
        ]);
        $this->assertDatabaseMissing('audit_logs', ['entity_type' => 'Attempt', 'entity_id' => $newAttemptId]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter=ReissueAttemptAuditTest"`
Expected: 1 failed — không có row trong `audit_logs`.

- [ ] **Step 3: Sửa `ReissueAttemptController.php`**

Thay nguyên đoạn dòng 13-68 (toàn bộ method `__invoke`) bằng:

```php
    public function __invoke(Request $request, Attempt $attempt)
    {
        abort_if($attempt->assessmentVersion->assessment->type === 'assignment', 409, 'Chỉ áp dụng cho Quiz/Exam.');
        abort_if($attempt->status === 'cancelled', 409, 'Attempt này đã bị hủy/cấp lại trước đó.');

        $data = $request->validate(['reason' => 'required|string']);
        $statusBefore = $attempt->status;

        $newAttempt = DB::transaction(function () use ($attempt, $data) {
            if ($attempt->submission) {
                $attempt->submission->update(['status' => 'invalidated']);
                // Kết quả của Attempt bị hủy do sự cố không được coi là hợp lệ —
                // nếu đã publish, thu hồi ngay để không lộ/tính vào Progress (review fix #7b).
                $attempt->submission->result?->update(['publication_status' => 'unpublished']);
            }

            $version = $attempt->assessmentVersion;
            $endsAt = null;
            if ($version->duration_seconds !== null) {
                $endsAt = now()->addSeconds($version->duration_seconds);
                if ($version->closes_at && $endsAt->gt($version->closes_at)) {
                    $endsAt = $version->closes_at;
                }
            }

            // max(attempt_number)+1, không count(not cancelled)+1 — xem ruling
            // tương ứng ở StartAttemptController (cùng loại lỗi trùng số thứ tự).
            $nextAttemptNumber = (Attempt::where('student_assessment_id', $attempt->student_assessment_id)->max('attempt_number') ?? 0) + 1;

            $newAttempt = Attempt::create([
                'student_assessment_id' => $attempt->student_assessment_id,
                'assessment_version_id' => $attempt->assessment_version_id,
                'attempt_number' => $nextAttemptNumber,
                'status' => 'in_progress',
                'started_at' => now(),
                'ends_at' => $endsAt,
            ]);

            $attempt->update([
                'status' => 'cancelled',
                'cancel_reason' => $data['reason'],
                'replacement_attempt_id' => $newAttempt->id,
            ]);

            $questions = AttemptQuestionBuilder::order($version->questions, (bool) $version->shuffle_questions);
            foreach ($questions->values() as $index => $question) {
                $newAttempt->attemptQuestions()->create([
                    'assessment_question_id' => $question->id,
                    'position' => $index + 1,
                    'option_order' => AttemptQuestionBuilder::optionOrderFor($question, (bool) $version->shuffle_options),
                ]);
            }

            return $newAttempt;
        });

        (new \App\Services\Audit\AuditLogger())->record(
            actor: $request->user(),
            action: 'attempt.reissue',
            entityType: 'Attempt',
            entityId: $attempt->id,
            before: ['status' => $statusBefore],
            after: ['status' => 'cancelled', 'replacement_attempt_id' => $newAttempt->id],
            reason: $data['reason'],
        );

        return response()->json(['attempt' => $newAttempt]);
    }
```

- [ ] **Step 4: Chạy test, xác nhận PASS**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter=ReissueAttemptAuditTest"`
Expected: 1 passed.

- [ ] **Step 5: Chạy toàn bộ test Reissue/Submission hiện có để chắc không phá hành vi cũ**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter=ReissueAttemptTest"`
Expected: tất cả PASS, bao gồm `test_attempt_numbers_do_not_collide_after_a_reissue` (gọi controller trực tiếp với request không có user resolver — `$request->user()` trả `null`, `AuditLogger::record()` nhận `actor: null` hợp lệ vì tham số `?User $actor`).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Submission/ReissueAttemptController.php tests/Feature/Audit/ReissueAttemptAuditTest.php
git commit -m "feat: retrofit AuditLog into ReissueAttemptController"
```

---

### Task 4: Nâng `notification_contexts.result_id` và `attachment_links.answer_id`/`feedback_id` lên FK thật

**Files:**
- Create: `database/migrations/2026_10_18_000001_add_fk_to_notification_contexts_and_attachment_links.php`
- Test: `tests/Unit/Migrations/DeferredForeignKeyConstraintsTest.php` (file mới)

**Interfaces:**
- Consumes: không có.
- Produces: không có interface mới — chỉ là ràng buộc schema bổ sung, không model/service nào cần đổi vì các cột này đã `nullable()` + đã tồn tại trong `$fillable` từ Module 5.

**Ngữ cảnh:** Khi viết spec/plan Module 5, bảng `results`/`answers`/`feedbacks` chưa tồn tại (Module 4 mới chỉ có plan, chưa code), nên `notification_contexts.result_id` và `attachment_links.answer_id`/`feedback_id` được tạo là `uuid()` KHÔNG có FK (soft reference). Giờ Module 4 đã code xong, 3 bảng đích đã tồn tại với `id` cùng kiểu `uuid()->primary()` — nâng lên FK thật được, không cần đổi kiểu dữ liệu.

- [ ] **Step 1: Viết test RED**

Tạo file `tests/Unit/Migrations/DeferredForeignKeyConstraintsTest.php`:

```php
<?php

namespace Tests\Unit\Migrations;

use App\Models\Attachment;
use App\Models\AttachmentLink;
use App\Models\Notification;
use App\Models\NotificationContext;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeferredForeignKeyConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_contexts_result_id_rejects_a_nonexistent_result(): void
    {
        $notification = Notification::factory()->create();

        $this->expectException(QueryException::class);
        NotificationContext::create(['notification_id' => $notification->id, 'result_id' => (string) Str::uuid()]);
    }

    public function test_attachment_links_answer_id_rejects_a_nonexistent_answer(): void
    {
        $user = User::factory()->create();
        $attachment = Attachment::create([
            'uploaded_by_user_id' => $user->id,
            'storage_key' => 'attachments/test.pdf',
            'file_name' => 'test.pdf',
            'media_type' => 'application/pdf',
            'size_bytes' => 1024,
            'status' => 'ready',
        ]);

        $this->expectException(QueryException::class);
        AttachmentLink::create([
            'attachment_id' => $attachment->id,
            'answer_id' => (string) Str::uuid(),
            'visibility' => 'private',
        ]);
    }

    public function test_attachment_links_feedback_id_rejects_a_nonexistent_feedback(): void
    {
        $user = User::factory()->create();
        $attachment = Attachment::create([
            'uploaded_by_user_id' => $user->id,
            'storage_key' => 'attachments/test2.pdf',
            'file_name' => 'test2.pdf',
            'media_type' => 'application/pdf',
            'size_bytes' => 1024,
            'status' => 'ready',
        ]);

        $this->expectException(QueryException::class);
        AttachmentLink::create([
            'attachment_id' => $attachment->id,
            'feedback_id' => (string) Str::uuid(),
            'visibility' => 'private',
        ]);
    }
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter=DeferredForeignKeyConstraintsTest"`
Expected: 3 failed — không có `QueryException` nào được ném ra, vì cột chưa có FK (uuid "lạ" vẫn insert được bình thường).

- [ ] **Step 3: Viết migration**

Tạo file `database/migrations/2026_10_18_000001_add_fk_to_notification_contexts_and_attachment_links.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_contexts', function (Blueprint $table) {
            $table->foreign('result_id')->references('id')->on('results')->cascadeOnDelete();
        });

        Schema::table('attachment_links', function (Blueprint $table) {
            $table->foreign('answer_id')->references('id')->on('answers')->cascadeOnDelete();
            $table->foreign('feedback_id')->references('id')->on('feedbacks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notification_contexts', function (Blueprint $table) {
            $table->dropForeign(['result_id']);
        });

        Schema::table('attachment_links', function (Blueprint $table) {
            $table->dropForeign(['answer_id']);
            $table->dropForeign(['feedback_id']);
        });
    }
};
```

- [ ] **Step 4: Chạy migration trong container rồi chạy lại test, xác nhận PASS**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan migrate"`
Expected: migration mới chạy OK, không lỗi (không có row cũ nào trong `notification_contexts`/`attachment_links` chứa uuid "lạ" không khớp — nếu có, migration sẽ fail ngay tại đây và cần dọn dữ liệu trước, nhưng ở V1 chưa có traffic thật nên không xảy ra).

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter=DeferredForeignKeyConstraintsTest"`
Expected: 3 passed (PHPUnit tự `migrate` trên DB test riêng qua `RefreshDatabase`, không cần chạy `artisan migrate` thủ công cho DB test — lệnh trên chỉ để xác nhận migration chạy sạch trên DB dev).

- [ ] **Step 5: Chạy toàn bộ test Notification/File hiện có để chắc FK mới không phá dữ liệu test cũ**

Run: `wsl.exe -- bash -c "cd /mnt/c/Workspaces/home/PiClass/piclass-infra/local && docker compose exec -T api php artisan test --filter='NotificationTest|AssessmentNotificationTest|AttendanceLessonNotificationTest|ResultNotificationTest|AttachmentTest'"`
Expected: tất cả PASS — không có test nào trước đó tạo `NotificationContext`/`AttachmentLink` với `result_id`/`answer_id`/`feedback_id` trỏ tới uuid không tồn tại (mọi test hiện có chỉ dùng `assessment_id`/`lesson_session_id`/`schedule_id`, hoặc không set `answer_id`/`feedback_id`).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_10_18_000001_add_fk_to_notification_contexts_and_attachment_links.php tests/Unit/Migrations/DeferredForeignKeyConstraintsTest.php
git commit -m "feat: add FK constraints to notification_contexts.result_id and attachment_links.answer_id/feedback_id"
```

---

## Ngoài phạm vi plan này

- **Cho phép thật việc link Attachment vào `answer_id`/`feedback_id`** — `LinkAttachmentController` (Module 5) hiện vẫn trả `422` cho 2 target này ("chờ retrofit Module 4"). Plan này CHỈ thêm FK constraint cho 2 cột (Task 4), KHÔNG bật lại tính năng — bật lại cần implement đúng logic phân quyền ở spec §7 ("Kiểm tra quyền xem theo loại target": Student owner của Attempt, Teacher/Admin trong scope Submission, Parent nếu `visibility=parent_if_published` và Result đã publish) cho cả `POST .../link` và `GET /attachments/{attachment}` — đây là một quyết định/thiết kế phân quyền riêng, không phải việc của Audit/Notification retrofit, nên tách thành follow-up riêng nếu cần.
- **`PublishAssessmentController` khi republish version đã có Student** (`spec §6` dòng: "`UpdateAssessmentController`, `PublishAssessmentController` (khi republish version đã có Student)" → event "Cập nhật Assessment đã giao") — đã ghi là deferred Ruling trong ledger Module 5 (`Final: Ruling` của plan `2026-10-15-notification-file-audit-backend`), không thuộc phạm vi Module 4 retrofit, không lặp lại ở đây.
- Thay đổi hành vi nghiệp vụ của 6 controller/service đã sửa (điều kiện publish/withdraw, công thức tính điểm, logic reissue...) — plan này chỉ thêm Audit/Notification, không đổi gì khác.
- FE cho Audit/Notification — vẫn chờ tới lượt Plan FE của nhóm module 5 theo đúng thứ tự Step 9 (`CLAUDE.md`).

## Tự rà soát (self-review)

1. **Spec coverage:** §4 bảng Module 4 có 3 dòng (`GradeQuestionController`/`GradeSubmissionController`; `PublishResultController`/`WithdrawResultController`/`BulkPublishResultsController`; `ReissueAttemptController`) → Task 1, 2, 3 tương ứng đủ cả 3. §6 bảng Module 4 có 1 dòng (`PublishResultController`/`BulkPublishResultsController` → notify) → nằm trong Task 2 (qua `ResultCalculator::publish()`, dùng chung cho cả 2 controller + auto-publish). FK deferred → Task 4.
2. **Placeholder scan:** không còn "TBD"/"tương tự Task N" nào — mỗi step có code đầy đủ, copy nguyên văn từ file thật đã đọc.
3. **Type consistency:** `AuditLogger::record()`/`NotificationDispatcher::dispatch()`/`ClassScope::studentAndParentRecipients()` dùng đúng signature đã xác nhận trực tiếp từ source (Explore agent đọc nguyên file), không suy đoán.
4. **Review Focus:** 5 dòng, mỗi dòng đều có task + test pin cụ thể (xem từng Task's Step 1).

---

Plan hoàn tất và đã lưu tại `docs/archive/superpowers/plans/2026-10-16-module4-audit-notification-retrofit.md`. Vui lòng xem lại plan. Bạn muốn chọn cách thực thi nào?

- **Subagent-driven** — Mỗi task do 1 subagent mới implement, 1 reviewer mới review trước khi sang task kế, cộng review toàn branch cuối cùng. Kỹ nhất; tốn context mới cho mỗi task + mỗi review.
- **Native** — Tôi tự thực thi toàn bộ task trong session này, rồi 1 reviewer mới (model mạnh nhất) review toàn branch cuối cùng. Rẻ và nhanh nhất; không có review độc lập giữa các task.

Plan này chỉ có 4 task, phần lớn độc lập (Task 1/2/3 sửa 3 file khác nhau không chồng lấn, Task 4 là migration riêng), và mỗi task đã có code đầy đủ sẵn từ việc đọc trực tiếp source — tôi đề xuất **Native**, giống 5 plan trước trong session này, vì rủi ro sai lệch giữa task thấp (không có interface mới cần 1 task truyền cho task khác đoán) và chi phí review-mỗi-task không tương xứng với quy mô nhỏ của plan.
