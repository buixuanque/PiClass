# BE: API "sửa và từ nay trở đi" + filter buổi học (ARCH-37) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm `PATCH /lesson-sessions/{id}/update-series` (sửa mẫu lịch + mọi buổi `scheduled` từ buổi đang chọn trở đi, thực sự có tác dụng lên buổi đã sinh) và mở rộng `GET /classes/{id}/lesson-sessions` với filter `teacher_id`/`status`/`review_required`, phục vụ tab "Lịch học" gộp ở FE (plan riêng, sau).

**Architecture:** Tách phần ghi `Schedule` + `ScheduleHistory` + audit dùng chung giữa `UpdateSchedule` (đã có) và `UpdateScheduleAndFollowingSessions` (mới) vào một trait, giống cách `CancelsScheduleSessions` đã dùng chung giữa `CancelLessonSessionAndFollowing` và `CancelSchedulesOnClassArchived`. `GenerateLessonSessions` nhận thêm tham số `windowStart` tùy chọn để sinh buổi mới không lùi về trước ngày của buổi đang chọn (mặc định giữ hành vi cũ — từ hôm nay). Filter buổi học thêm thẳng vào `ListLessonSessionsQuery`/`Request` hiện có.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, Eloquent.

**Spec:** `docs/superpowers/specs/2026-10-27-merge-schedule-sessions-tab-design.md` (mục "Thiết kế API (piclass-api)") — đọc cả văn bản, đặc biệt các đoạn "Quyết định (builder-check hỏi, Claude tự quyết)" đã chốt sẵn.

## Global Constraints

- Comment/docblock tiếng Việt; tên biến/hàm/class/test method/`error.code`/commit message tiếng Anh (`piclass-api/CLAUDE.md`).
- Action không biết HTTP, actor truyền qua tham số; Controller ≈10 dòng; FormRequest validate + gọi Policy.
- Ghi ≥2 bảng hoặc có audit bắt buộc → `DB::transaction`; audit lỗi thì cả thay đổi bị hủy; không kiểm tra lại quyền trong Action.
- Không literal chuỗi trùng giá trị Enum của module (R8) — dùng `Rule::enum(...)`/`->value`.
- `effective_until`/`occurrence_date` là ngày thuần (không giờ) — rule `date`, không `NormalizesAppTime`; request mới có rule `date` cho field ngày thuần phải vào `DATE_ONLY_REQUESTS` của `ArchitectureTest`.
- Mọi đổi route/FormRequest/Resource → export lại OpenAPI (`docker compose exec api php artisan scramble:export`, repo `piclass-infra/local`) trước khi coi task xong.
- `SESSION_HAS_NO_SCHEDULE` (422, đã có trong `error-codes.md`) tái dùng cho cả `cancel-series` và `update-series` — không tạo mã mới.
- Bất biến từ spec: không đụng buổi có `occurrence_date` trước buổi đang chọn; không đụng buổi đã `completed`/đã `cancelled`; rút ngắn `effective_until` phải hủy buổi rơi sau ngày mới dù còn khớp thứ.

## Review Focus

- **`effective_until` bị rút ngắn trong khi `weekdays` không đổi:** buổi rơi sau `effective_until` mới nhưng vẫn khớp thứ cũ phải bị hủy — đây là lỗi thật tìm thấy ở bản thiết kế đầu, dễ bị bỏ sót nếu chỉ test trường hợp đổi `weekdays`.
- **Buổi neo (`$session` truyền vào) đang `completed`/`cancelled`:** API vẫn phải chạy đúng (sửa mẫu + quét buổi `scheduled` từ ngày đó), không được từ chối hay bỏ qua chính buổi neo (buổi neo không nằm trong tập bị sửa trừ khi nó cũng `scheduled`).
- **Đổi `weekdays` làm xuất hiện ngày mới nằm TRƯỚC ngày buổi đang chọn** (ví dụ buổi neo ở thứ Năm, lịch gốc chỉ có thứ Năm, đổi sang thêm thứ Hai — thứ Hai của tuần đó rơi trước ngày buổi neo): không được sinh buổi mới cho ngày đó, dù nó khớp `weekdays` mới và còn trong `effective_until` — `windowStart` của `GenerateLessonSessions` phải chặn đúng ở ngày buổi neo, không phải hôm nay.
- **Buổi lẻ (`schedule_id = null`) gọi `update-series`:** phải trả 422 `SESSION_HAS_NO_SCHEDULE`, không được đụng gì tới buổi đó hay tạo `Schedule` nào.
- **Filter `status`/`review_required`/`teacher_id` kết hợp với `from`/`until` đã có:** mỗi filter áp đúng độc lập (AND), và `teacher_id` chỉ khớp buổi có giáo viên đó đang phân công **tại đúng thời điểm `starts_at`** của buổi (không phải đang phân công bây giờ) — test cần một giáo viên đã kết thúc phân công trước buổi diễn ra để phân biệt.

---

## File Structure

| File | Trách nhiệm |
| --- | --- |
| `app/Modules/Lesson/Actions/Concerns/UpdatesScheduleTemplate.php` (mới) | Trait: ghi field đổi lên `Schedule` đã khóa + `ScheduleHistory` + audit `schedule.update` — dùng chung `UpdateSchedule` và `UpdateScheduleAndFollowingSessions` |
| `app/Modules/Lesson/Actions/UpdateSchedule.php` (sửa) | Dùng trait trên, không đổi hành vi |
| `app/Modules/Lesson/Actions/GenerateLessonSessions.php` (sửa) | Thêm tham số `windowStart` tùy chọn |
| `app/Modules/Lesson/Actions/UpdateScheduleAndFollowingSessions.php` (mới) | Sửa mẫu lịch + quét/sửa/hủy buổi `scheduled` từ buổi đang chọn trở đi + sinh buổi còn thiếu |
| `app/Modules/Lesson/Http/Requests/UpdateScheduleAndFollowingSessionsRequest.php` (mới) | Validate + Policy `update` trên `LessonSession` |
| `app/Modules/Lesson/Http/Controllers/UpdateScheduleAndFollowingSessionsController.php` (mới) | Điều phối, trả `UpdatedScheduleResource` |
| `routes/api.php` (sửa) | Thêm `PATCH /lesson-sessions/{lessonSession}/update-series` |
| `tests/Architecture/ArchitectureTest.php` (sửa) | Thêm Request mới vào `DATE_ONLY_REQUESTS` |
| `app/Modules/Lesson/Http/Requests/ListLessonSessionsRequest.php` (sửa) | Thêm accessor `teacherId()`/`status()`/`reviewRequired()` + rule validate |
| `app/Modules/Lesson/Queries/ListLessonSessionsQuery.php` (sửa) | Áp 3 filter mới |
| `app/Modules/Lesson/Http/Controllers/ListLessonSessionsController.php` (sửa) | Truyền filter mới vào Query |
| `docs/architecture/error-codes.md` (sửa) | Ghi `update-series` vào mô tả `SESSION_HAS_NO_SCHEDULE` |
| `tests/Feature/Lesson/LessonScheduleBehaviorTest.php` (sửa) | Test `update-series` |
| `tests/Feature/Lesson/LessonSessionBehaviorTest.php` (sửa) | Test filter mới |

---

### Task 1: Tách trait `UpdatesScheduleTemplate` từ `UpdateSchedule`

**Files:**
- Create: `app/Modules/Lesson/Actions/Concerns/UpdatesScheduleTemplate.php`
- Modify: `app/Modules/Lesson/Actions/UpdateSchedule.php`
- Test: (không test mới — refactor thuần, test hiện có của `UpdateSchedule` phải giữ xanh)

**Interfaces:**
- Produces: `trait UpdatesScheduleTemplate { protected function applyScheduleUpdate(Schedule $locked, User $actor, array $data): void }` — giả định class dùng trait có `private AuditLogger $audit` và hằng `TRACKED` (`array<string>`, các field theo dõi history/audit). Hàm đọc/ghi `$locked` (đã khóa `lockForUpdate` ở nơi gọi), `$data` có khóa `reason` (bỏ qua khi `fill`) và các field khác khớp `TRACKED`.

- [ ] **Step 1: Viết trait, chuyển đúng nguyên logic ghi `Schedule`/`ScheduleHistory`/audit từ `UpdateSchedule::handle` vào `applyScheduleUpdate`**

Nội dung hàm giống đúng các dòng từ `$before = $locked->only(self::TRACKED);` tới hết `$this->audit->record(...)` trong `UpdateSchedule::handle` hiện tại (đọc file để copy chính xác) — không đổi bất kỳ giá trị/field nào.

- [ ] **Step 2: Sửa `UpdateSchedule` dùng trait**

`use UpdatesScheduleTemplate;`, đổi `private const TRACKED` thành hằng dùng được từ trait (giữ `private const TRACKED = [...]`, không cần đổi visibility — trait truy cập được const private của class chứa nó). `handle()` chỉ còn: khóa `$locked`, gọi `$this->applyScheduleUpdate($locked, $actor, $data)`, gọi `$this->generate->handle(...)` như cũ, dispatch `ScheduleUpdated`, return `$locked`.

- [ ] **Step 3: Chạy lại toàn bộ test của `UpdateSchedule`**

Run: `docker compose exec api php artisan test --filter=LessonScheduleBehaviorTest` (repo `piclass-infra/local`)
Expected: PASS, không giảm số test, không đổi hành vi.

- [ ] **Step 4: Commit**

```bash
git add app/Modules/Lesson/Actions/Concerns/UpdatesScheduleTemplate.php app/Modules/Lesson/Actions/UpdateSchedule.php
git commit -m "refactor: extract UpdatesScheduleTemplate trait from UpdateSchedule"
```

---

### Task 2: `GenerateLessonSessions` nhận `windowStart` tùy chọn

**Files:**
- Modify: `app/Modules/Lesson/Actions/GenerateLessonSessions.php`
- Test: `tests/Unit/Modules/Lesson/GenerateLessonSessionsTest.php` (tạo mới nếu chưa có unit test riêng cho class này — kiểm tra trước khi tạo, có thể hiện chỉ test qua `CreateSchedule`/`UpdateSchedule`)

**Interfaces:**
- Produces: `GenerateLessonSessions::handle(Schedule $schedule, Carbon $windowEnd, ?Carbon $windowStart = null): int` — khi `$windowStart` là `null`, hành vi giống hệt hiện tại (mốc dưới là `AppTime::today()`); khi có giá trị, mốc dưới là `max(effective_from, $windowStart)` thay cho `max(effective_from, AppTime::today())`.

- [ ] **Step 1: Viết test (feature, vì class không tự chứa DB access ngoài Eloquent — kiểm qua `LessonSession::factory` + gọi trực tiếp Action, giống style Domain nhưng class này đụng DB nên đặt ở `tests/Feature/Lesson/`, không phải `tests/Unit`)**

```php
public function test_handle_with_explicit_window_start_does_not_backfill_before_it(): void
{
    Carbon::setTestNow(Carbon::parse('2026-10-20 00:00:00', 'UTC')); // hôm nay = 2026-10-20 giờ VN
    $schedule = Schedule::factory()->create([
        'effective_from' => '2026-10-05', 'effective_until' => '2026-11-30',
        'repeat_weekly' => true, 'weekdays' => [1], // Thứ Hai
        'local_start_time' => '08:00:00',
    ]);

    app(GenerateLessonSessions::class)->handle($schedule, Carbon::parse('2026-11-30'), Carbon::parse('2026-10-26'));

    // Thứ Hai 2026-10-19 (trước windowStart 2026-10-26) KHÔNG được sinh, dù sau effective_from và trước "hôm nay".
    $this->assertFalse(LessonSession::where('schedule_id', $schedule->id)->where('occurrence_date', '2026-10-19')->exists());
    // Thứ Hai 2026-10-26 (đúng windowStart, cũng là một Thứ Hai) được sinh.
    $this->assertTrue(LessonSession::where('schedule_id', $schedule->id)->where('occurrence_date', '2026-10-26')->exists());
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `docker compose exec api php artisan test --filter=test_handle_with_explicit_window_start_does_not_backfill_before_it`
Expected: FAIL (tham số thứ 3 chưa tồn tại — lỗi kiểu/arity).

- [ ] **Step 3: Sửa `GenerateLessonSessions::handle`**

Đổi signature thành `handle(Schedule $schedule, Carbon $windowEnd, ?Carbon $windowStart = null): int`. Dòng `$windowStart = Carbon::parse($schedule->effective_from)->max(AppTime::today());` đổi thành dùng tham số mới làm mốc dưới thay `AppTime::today()` khi được truyền (đặt tên biến local khác để không trùng tên tham số, ví dụ `$floor = $windowStart ?? AppTime::today(); $rangeStart = Carbon::parse($schedule->effective_from)->max($floor);`), phần còn lại của method không đổi.

- [ ] **Step 4: Chạy lại test mới + toàn bộ `LessonScheduleBehaviorTest`**

Run: `docker compose exec api php artisan test --filter=LessonScheduleBehaviorTest`
Expected: PASS toàn bộ (test cũ của `CreateSchedule`/`UpdateSchedule` không gọi tham số mới nên giữ đúng hành vi cũ).

- [ ] **Step 5: Commit**

```bash
git add app/Modules/Lesson/Actions/GenerateLessonSessions.php tests/Feature/Lesson/LessonScheduleBehaviorTest.php
git commit -m "feat: let GenerateLessonSessions take an explicit window start"
```

---

### Task 3: Action `UpdateScheduleAndFollowingSessions` — sửa mẫu, hủy buổi lệch thứ/quá hạn mới

**Files:**
- Modify: `tests/Feature/Lesson/LessonScheduleBehaviorTest.php` (thêm test, cùng khu vực với test `cancel-series` hiện có)
- Create: `app/Modules/Lesson/Actions/UpdateScheduleAndFollowingSessions.php`

**Interfaces:**
- Consumes: `UpdatesScheduleTemplate::applyScheduleUpdate` (Task 1); `AuditLogger::record` (chữ ký như các Action khác trong module); `LessonSessionCancelled`, `LessonSessionUpdated`, `ScheduleUpdated` (`Modules/Lesson/Events`, `dispatch($model, int $timestamp)`); `SessionHasNoSchedule` (`Modules/Lesson/Exceptions`, đã có).
- Produces: `UpdateScheduleAndFollowingSessions::handle(LessonSession $session, User $actor, array $data): Schedule` — `$data` có khóa `reason` (string, bắt buộc) và tùy chọn `local_start_time`, `duration_minutes`, `weekdays`, `effective_until` (cùng kiểu `UpdateSchedule::handle`'s `$data`). Trả về `Schedule` đã cập nhật (để Controller bọc `UpdatedScheduleResource`, giống `UpdateSchedule`).

- [ ] **Step 1: Viết test "hủy buổi rơi sau effective_until mới dù vẫn khớp thứ cũ" (Review Focus #1)**

```php
public function test_update_series_cancels_sessions_past_the_new_shorter_effective_until(): void
{
    [$class, $teacher, $schedule] = $this->scheduleWithRoster(['effective_until' => '2026-12-31']);
    $chosen = LessonSession::factory()->create(['class_id' => $class->id, 'schedule_id' => $schedule->id, 'occurrence_date' => '2026-10-26', 'status' => 'scheduled']);
    $keptAfter = LessonSession::factory()->create(['class_id' => $class->id, 'schedule_id' => $schedule->id, 'occurrence_date' => '2026-11-02', 'status' => 'scheduled']);
    $pastNewEnd = LessonSession::factory()->create(['class_id' => $class->id, 'schedule_id' => $schedule->id, 'occurrence_date' => '2026-11-09', 'status' => 'scheduled']);
    $this->signIn($teacher, 'teacher');

    $response = $this->patchJson("/api/v1/lesson-sessions/{$chosen->id}/update-series", [
        'effective_until' => '2026-11-05', 'reason' => 'rút ngắn học kỳ',
    ]);

    $response->assertOk();
    $this->assertSame('2026-11-05', $schedule->fresh()->effective_until->toDateString());
    $this->assertSame('cancelled', $pastNewEnd->fresh()->status->value); // rơi sau 2026-11-05, vẫn khớp Thứ Hai cũ — PHẢI hủy
    $this->assertSame('scheduled', $keptAfter->fresh()->status->value); // 2026-11-02 <= 2026-11-05, giữ
}
```

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `docker compose exec api php artisan test --filter=test_update_series_cancels_sessions_past_the_new_shorter_effective_until`
Expected: FAIL (route/Action chưa tồn tại — 404 hoặc lỗi resolve).

- [ ] **Step 3: Viết `UpdateScheduleAndFollowingSessions`**

```php
final class UpdateScheduleAndFollowingSessions
{
    use UpdatesScheduleTemplate;

    private const TRACKED = ['local_start_time', 'duration_minutes', 'weekdays', 'effective_until'];

    public function __construct(private AuditLogger $audit, private GenerateLessonSessions $generate) {}

    public function handle(LessonSession $session, User $actor, array $data): Schedule
    {
        throw_if($session->schedule_id === null, new SessionHasNoSchedule);

        return DB::transaction(function () use ($session, $actor, $data) {
            $locked = Schedule::whereKey($session->schedule_id)->lockForUpdate()->firstOrFail();
            $this->applyScheduleUpdate($locked, $actor, $data);

            $future = LessonSession::where('schedule_id', $locked->id)
                ->where('occurrence_date', '>=', $session->occurrence_date->toDateString())
                ->where('status', LessonSessionStatus::Scheduled->value)
                ->lockForUpdate()->get();

            foreach ($future as $s) {
                // thân hàm: xem thuật toán dưới
            }

            $windowStart = $session->occurrence_date->copy()->max(AppTime::today());
            $this->generate->handle($locked, Carbon::parse($locked->effective_until), $windowStart);

            ScheduleUpdated::dispatch($locked, now()->timestamp);

            return $locked;
        });
    }
}
```

Thuật toán thân vòng `foreach` (viết đúng, đây là phần quyết định của task, không để builder đoán):

```php
$matchesWeekday = ! $locked->repeat_weekly || in_array($s->occurrence_date->isoWeekday(), $locked->weekdays ?? [], true);
$withinNewEnd = $s->occurrence_date->lte($locked->effective_until);

if (! $matchesWeekday || ! $withinNewEnd) {
    $s->update([
        'status' => LessonSessionStatus::Cancelled,
        'cancellation_reason' => $data['reason'],
        'review_required' => $s->attendanceRecords()->exists(),
    ]);
    $this->audit->record(actor: $actor, action: 'lesson_session.cancel', entityType: 'LessonSession',
        entityId: $s->id, after: ['status' => LessonSessionStatus::Cancelled->value],
        reason: $data['reason'], correlationKey: $s->class_id);
    LessonSessionCancelled::dispatch($s, now()->timestamp);
    continue;
}

$before = $s->only(['starts_at', 'ends_at']);
$startsAt = Carbon::parse($s->occurrence_date->toDateString().' '.$locked->local_start_time, $locked->timezone)->utc();
$s->update(['starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addMinutes($locked->duration_minutes)]);
$after = $s->only(['starts_at', 'ends_at']);
$this->audit->record(actor: $actor, action: 'lesson_session.update', entityType: 'LessonSession',
    entityId: $s->id, before: $before, after: $after, reason: $data['reason'], correlationKey: $s->class_id);
LessonSessionUpdated::dispatch($s, now()->timestamp);
```

- [ ] **Step 4: Chạy lại test mới**

Run: `docker compose exec api php artisan test --filter=test_update_series_cancels_sessions_past_the_new_shorter_effective_until`
Expected: FAIL tiếp (chưa có route/Controller/Request — Task 4 làm phần đó) — ghi nhận lỗi đổi từ "class not found" sang "route not found"/404, xác nhận Action tự nó compile đúng bằng cách chạy PHPStan riêng file này nếu muốn, nhưng không bắt buộc PASS ở step này.

- [ ] **Step 5: Commit (cho phép commit code chưa có route — vẫn nằm trong nhánh làm việc)**

```bash
git add app/Modules/Lesson/Actions/UpdateScheduleAndFollowingSessions.php tests/Feature/Lesson/LessonScheduleBehaviorTest.php
git commit -m "feat: add UpdateScheduleAndFollowingSessions action"
```

---

### Task 4: Request + Controller + route cho `PATCH /lesson-sessions/{id}/update-series`

**Files:**
- Create: `app/Modules/Lesson/Http/Requests/UpdateScheduleAndFollowingSessionsRequest.php`
- Create: `app/Modules/Lesson/Http/Controllers/UpdateScheduleAndFollowingSessionsController.php`
- Modify: `routes/api.php`
- Modify: `tests/Architecture/ArchitectureTest.php`
- Modify: `tests/Feature/Lesson/LessonScheduleBehaviorTest.php` (hoàn thiện test Task 3, thêm test validate/authorization/SESSION_HAS_NO_SCHEDULE/Review Focus #2, #3, #4)

**Interfaces:**
- Consumes: `UpdateScheduleAndFollowingSessions::handle` (Task 3); `UpdatedScheduleResource` (đã có, `app/Modules/Lesson/Http/Resources/UpdatedScheduleResource.php`); `LessonSessionPolicy::update` ability (đã có).

- [ ] **Step 1: Hoàn thiện test Task 3 (giờ phải PASS) + thêm 4 test sau, viết hết rồi chạy cùng lúc**

```php
public function test_update_series_applies_from_a_completed_or_cancelled_anchor_session_too(): void
{
    // Review Focus #2: buổi neo đã completed/cancelled không bị chặn, vẫn sửa đúng mẫu + buổi SAU nó.
    [$class, $teacher, $schedule] = $this->scheduleWithRoster();
    $anchor = LessonSession::factory()->create(['class_id' => $class->id, 'schedule_id' => $schedule->id, 'occurrence_date' => '2026-10-19', 'status' => 'completed', 'completed_at' => now()]);
    $after = LessonSession::factory()->create(['class_id' => $class->id, 'schedule_id' => $schedule->id, 'occurrence_date' => '2026-10-26', 'status' => 'scheduled']);
    $this->signIn($teacher, 'teacher');

    $response = $this->patchJson("/api/v1/lesson-sessions/{$anchor->id}/update-series", ['duration_minutes' => 45, 'reason' => 'rút ngắn giờ']);

    $response->assertOk();
    $this->assertSame(45, $schedule->fresh()->duration_minutes);
    $this->assertSame('completed', $anchor->fresh()->status->value); // buổi neo không bị sửa (không scheduled)
    $this->assertSame(45, $after->fresh()->ends_at->diffInMinutes($after->fresh()->starts_at));
}

public function test_update_series_does_not_backfill_a_new_weekday_before_the_anchor_date(): void
{
    // Review Focus #3: weekdays mới khớp một ngày TRƯỚC ngày buổi neo thì không được sinh buổi cho ngày đó.
    [$class, $teacher, $schedule] = $this->scheduleWithRoster(['effective_from' => '2026-10-05', 'weekdays' => [4]]); // chỉ Thứ Năm
    $anchor = LessonSession::factory()->create(['class_id' => $class->id, 'schedule_id' => $schedule->id, 'occurrence_date' => '2026-10-22', 'status' => 'scheduled']); // một Thứ Năm
    $this->signIn($teacher, 'teacher');

    $this->patchJson("/api/v1/lesson-sessions/{$anchor->id}/update-series", ['weekdays' => [1, 4], 'reason' => 'thêm Thứ Hai'])->assertOk();

    // Thứ Hai 2026-10-19 nằm TRƯỚC ngày buổi neo (2026-10-22) — không được sinh, dù khớp weekdays mới.
    $this->assertFalse(LessonSession::where('schedule_id', $schedule->id)->where('occurrence_date', '2026-10-19')->exists());
    // Thứ Hai 2026-10-26 (sau ngày buổi neo) PHẢI được sinh.
    $this->assertTrue(LessonSession::where('schedule_id', $schedule->id)->where('occurrence_date', '2026-10-26')->exists());
}

public function test_update_series_on_a_session_without_a_schedule_is_rejected(): void
{
    $class = SchoolClass::factory()->create();
    $teacher = $this->makeTeacher($class);
    $session = $this->makeSession($class); // schedule_id = null
    $this->signIn($teacher, 'teacher');

    $response = $this->patchJson("/api/v1/lesson-sessions/{$session->id}/update-series", ['reason' => 'x']);

    $this->assertError($response, 422, 'SESSION_HAS_NO_SCHEDULE', 'Buổi học này không thuộc một lịch lặp, không thể hủy cùng các buổi tiếp theo.');
}

public function test_update_series_validation_and_authorization(): void
{
    [$class, $teacher, $schedule] = $this->scheduleWithRoster();
    $session = LessonSession::factory()->create(['class_id' => $class->id, 'schedule_id' => $schedule->id, 'occurrence_date' => '2026-10-26']);
    $url = "/api/v1/lesson-sessions/{$session->id}/update-series";

    $this->signIn($teacher, 'teacher');
    $missing = $this->patchJson($url, []);
    $missing->assertStatus(422);
    $this->assertSame(['reason'], array_keys($missing->json('error.details')));

    $this->signIn($this->makeTeacher(SchoolClass::factory()->create()), 'teacher');
    $this->assertError($this->patchJson($url, ['reason' => 'x']), 403, 'FORBIDDEN', self::FORBIDDEN_CLASS);
}
```

- [ ] **Step 2: Chạy toàn bộ test `update_series`, xác nhận FAIL vì thiếu route**

Run: `docker compose exec api php artisan test --filter=update_series`
Expected: FAIL (404/`RouteNotFoundException`).

- [ ] **Step 3: Viết `UpdateScheduleAndFollowingSessionsRequest`**

```php
class UpdateScheduleAndFollowingSessionsRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('lessonSession'));
    }

    public function rules(): array
    {
        return [
            'effective_until' => ['sometimes', 'date'],
            'local_start_time' => ['sometimes', 'date_format:H:i:s'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'weekdays' => ['sometimes', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'reason' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 4: Viết `UpdateScheduleAndFollowingSessionsController`**

```php
class UpdateScheduleAndFollowingSessionsController extends Controller
{
    public function __invoke(UpdateScheduleAndFollowingSessionsRequest $request, LessonSession $lessonSession, UpdateScheduleAndFollowingSessions $action): UpdatedScheduleResource
    {
        return new UpdatedScheduleResource($action->handle($lessonSession, $request->user(), $request->validated()));
    }
}
```

- [ ] **Step 5: Đăng ký route** trong `routes/api.php`, nhóm `role:admin,teacher`, ngay sau dòng `cancel-series`:

```php
Route::patch('/lesson-sessions/{lessonSession}/update-series', UpdateScheduleAndFollowingSessionsController::class);
```

- [ ] **Step 6: Thêm `UpdateScheduleAndFollowingSessionsRequest.php` vào `DATE_ONLY_REQUESTS`** của `tests/Architecture/ArchitectureTest.php` (cùng mẫu `UpdateScheduleRequest.php`, vì `effective_until` ở đây cũng là rule `date` không `NormalizesAppTime`).

- [ ] **Step 7: Chạy toàn bộ test Lesson + Architecture**

Run: `docker compose exec api php artisan test --filter=LessonScheduleBehaviorTest`
Run: `docker compose exec api php artisan test tests/Architecture`
Expected: PASS toàn bộ.

- [ ] **Step 8: Export OpenAPI, cập nhật `error-codes.md`, format, phân tích tĩnh**

Run: `docker compose exec api php artisan scramble:export` (repo `piclass-infra/local`)
Sửa dòng `SESSION_HAS_NO_SCHEDULE` ở `docs/architecture/error-codes.md`: thêm `update-series` vào danh sách endpoint gây lỗi này (cùng dòng với `cancel-series`).
Run: `docker compose exec api vendor/bin/pint --dirty`
Run: `docker compose exec api vendor/bin/phpstan analyse --memory-limit=1G`
Expected: export không lỗi; Pint sạch; PHPStan "No errors" (không thêm lỗi mới).

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Lesson/Http/Requests/UpdateScheduleAndFollowingSessionsRequest.php app/Modules/Lesson/Http/Controllers/UpdateScheduleAndFollowingSessionsController.php routes/api.php tests/Architecture/ArchitectureTest.php tests/Feature/Lesson/LessonScheduleBehaviorTest.php
git add -p ../docs/architecture/error-codes.md   # nếu docs là submodule/đường dẫn khác, xem ghi chú dưới
git commit -m "feat: add PATCH /lesson-sessions/{id}/update-series endpoint"
```

Ghi chú đường dẫn docs: `docs/architecture/error-codes.md` nằm ở repo `piclass-docs` (thư mục gốc workspace), không ở `piclass-api` — commit riêng ở repo đó theo quy trình branch/PR của `piclass-docs` (không gộp commit 2 repo).

---

### Task 5: Filter `teacher_id`/`status`/`review_required` cho `GET /classes/{id}/lesson-sessions`

**Files:**
- Modify: `app/Modules/Lesson/Http/Requests/ListLessonSessionsRequest.php`
- Modify: `app/Modules/Lesson/Queries/ListLessonSessionsQuery.php`
- Modify: `app/Modules/Lesson/Http/Controllers/ListLessonSessionsController.php`
- Test: `tests/Feature/Lesson/LessonSessionBehaviorTest.php`

**Interfaces:**
- Produces: `ListLessonSessionsRequest::teacherId(): ?string`, `::status(): ?string`, `::reviewRequired(): ?bool` (đọc từ `$this->input(...)`, validate trước qua `rules()`). `ListLessonSessionsQuery::handle(SchoolClass $class, ?CarbonInterface $from, ?CarbonInterface $until, ?string $teacherId, ?string $status, ?bool $reviewRequired): Collection` — 3 tham số mới nối sau 2 tham số cũ, tất cả tùy chọn.

- [ ] **Step 1: Đọc Controller hiện tại để biết đúng cách gọi Query (giữ thứ tự tham số nhất quán)**

Đọc `app/Modules/Lesson/Http/Controllers/ListLessonSessionsController.php` trước khi sửa.

- [ ] **Step 2: Viết test filter (viết cả 4 test rồi chạy cùng lúc)**

Dùng đúng helper thật đã có trong `LessonFixtures` (`makeTeacher(?SchoolClass $class, array $assignment = []): User`, `makeSession(SchoolClass $class, array $overrides = []): LessonSession`; `User::teacherProfile()` có sẵn để lấy id Teacher):

```php
public function test_list_filters_by_status(): void
{
    $class = SchoolClass::factory()->create();
    $teacher = $this->makeTeacher($class);
    $this->makeSession($class, ['status' => 'completed', 'completed_at' => now()]);
    $cancelled = $this->makeSession($class, ['status' => 'cancelled', 'cancellation_reason' => 'x']);
    $this->signIn($teacher, 'teacher');

    $response = $this->getJson("/api/v1/classes/{$class->id}/lesson-sessions?status=cancelled");

    $response->assertOk();
    $this->assertSame([$cancelled->id], $response->json('data.*.id'));
}

public function test_list_filters_by_review_required(): void
{
    $class = SchoolClass::factory()->create();
    $teacher = $this->makeTeacher($class);
    $needsReview = $this->makeSession($class, ['status' => 'cancelled', 'cancellation_reason' => 'x', 'review_required' => true]);
    $this->makeSession($class, ['status' => 'cancelled', 'cancellation_reason' => 'x', 'review_required' => false]);
    $this->signIn($teacher, 'teacher');

    $response = $this->getJson("/api/v1/classes/{$class->id}/lesson-sessions?review_required=1");

    $response->assertOk();
    $this->assertSame([$needsReview->id], $response->json('data.*.id'));
}

public function test_list_filters_by_teacher_id_at_the_time_the_session_happened(): void
{
    // Review Focus #5: teacher_id khớp theo thời điểm starts_at, không phải phân công hiện tại.
    $class = SchoolClass::factory()->create();
    $teacherA = $this->makeTeacher($class, ['valid_from' => now()->subMonths(2), 'valid_to' => now()->subMonth()]);
    $teacherB = $this->makeTeacher($class, ['valid_from' => now()->subMonth()]);
    $duringA = $this->makeSession($class, ['starts_at' => now()->subMonths(2)->addDay(), 'ends_at' => now()->subMonths(2)->addDay()->addHour()]);
    $duringB = $this->makeSession($class, ['starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHour()]);
    $this->signIn($teacherB, 'teacher');

    $response = $this->getJson("/api/v1/classes/{$class->id}/lesson-sessions?teacher_id={$teacherA->teacherProfile->id}");

    $response->assertOk();
    $this->assertSame([$duringA->id], $response->json('data.*.id'));
}

public function test_list_filter_validation(): void
{
    $class = SchoolClass::factory()->create();
    $teacher = $this->makeTeacher($class);
    $this->signIn($teacher, 'teacher');

    $this->getJson("/api/v1/classes/{$class->id}/lesson-sessions?status=not-a-status")->assertStatus(422);
    $this->getJson("/api/v1/classes/{$class->id}/lesson-sessions?teacher_id=not-a-uuid")->assertStatus(422);
    $this->getJson("/api/v1/classes/{$class->id}/lesson-sessions?review_required=maybe")->assertStatus(422);
}
```

- [ ] **Step 3: Chạy test, xác nhận FAIL**

Run: `docker compose exec api php artisan test --filter=test_list_filters_by_status`
Expected: FAIL (lọc chưa tồn tại — trả về cả 2 buổi, hoặc 422 không validate nên không có lỗi).

- [ ] **Step 4: Sửa `ListLessonSessionsRequest`** — thêm `rules()` cho 3 field mới (giữ `from`/`until` không validate như cũ) và 3 accessor:

```php
public function rules(): array
{
    return [
        'teacher_id' => ['sometimes', 'uuid'],
        'status' => ['sometimes', Rule::enum(LessonSessionStatus::class)],
        'review_required' => ['sometimes', 'boolean'],
    ];
}

public function teacherId(): ?string { return $this->input('teacher_id'); }
public function statusFilter(): ?string { return $this->input('status'); }
public function reviewRequired(): ?bool { return $this->filled('review_required') ? $this->boolean('review_required') : null; }
```

(Đặt tên `statusFilter()` thay `status()` để không đụng `FormRequest::status()` thật của Symfony/HTTP response status nếu có — kiểm tra lại khi viết, nếu không trùng thì dùng `status()` cho gọn.)

- [ ] **Step 5: Sửa `ListLessonSessionsQuery::handle`** — thêm 3 tham số, áp filter:

```php
public function handle(SchoolClass $class, ?CarbonInterface $from, ?CarbonInterface $until, ?string $teacherId = null, ?string $status = null, ?bool $reviewRequired = null): Collection
{
    $query = $class->lessonSessions()->orderBy('starts_at');

    if ($from !== null) $query->where('starts_at', '>=', $from);
    if ($until !== null) $query->where('starts_at', '<=', $until);
    if ($status !== null) $query->where('status', $status);
    if ($reviewRequired !== null) $query->where('review_required', $reviewRequired);
    if ($teacherId !== null) {
        $query->whereExists(function ($q) use ($teacherId) {
            $q->selectRaw('1')->from('teacher_class_assignments')
                ->whereColumn('teacher_class_assignments.class_id', 'lesson_sessions.class_id')
                ->where('teacher_class_assignments.teacher_id', $teacherId)
                ->whereColumn('teacher_class_assignments.valid_from', '<=', 'lesson_sessions.starts_at')
                ->where(fn ($q2) => $q2->whereNull('teacher_class_assignments.valid_to')
                    ->orWhereColumn('teacher_class_assignments.valid_to', '>', 'lesson_sessions.starts_at'));
        });
    }

    return $query->get();
}
```

- [ ] **Step 6: Sửa `ListLessonSessionsController`** truyền 3 giá trị mới từ Request vào Query (giữ đúng thứ tự tham số đã định nghĩa ở Step 5).

- [ ] **Step 7: Chạy lại 4 test + toàn bộ `LessonSessionBehaviorTest`**

Run: `docker compose exec api php artisan test --filter=LessonSessionBehaviorTest`
Expected: PASS toàn bộ.

- [ ] **Step 8: Export OpenAPI, Pint, PHPStan**

Run: `docker compose exec api php artisan scramble:export`
Run: `docker compose exec api vendor/bin/pint --dirty`
Run: `docker compose exec api vendor/bin/phpstan analyse --memory-limit=1G`
Expected: như Task 4 Step 8.

- [ ] **Step 9: Commit**

```bash
git add app/Modules/Lesson/Http/Requests/ListLessonSessionsRequest.php app/Modules/Lesson/Queries/ListLessonSessionsQuery.php app/Modules/Lesson/Http/Controllers/ListLessonSessionsController.php tests/Feature/Lesson/LessonSessionBehaviorTest.php
git commit -m "feat: filter GET /classes/{id}/lesson-sessions by teacher, status, review_required"
```

---

### Task 6: Chạy toàn bộ, đồng bộ tài liệu

**Files:**
- Modify: `docs/progress.md`, `docs/trang-thai-ngan.md` (repo `piclass-docs`, nhánh riêng)

- [ ] **Step 1: Chạy toàn bộ test suite + Pint + PHPStan một lần cuối**

Run: `docker compose exec api php artisan test`
Run: `docker compose exec api vendor/bin/pint --dirty`
Run: `docker compose exec api vendor/bin/phpstan analyse --memory-limit=1G`
Expected: tất cả PASS/sạch, không hồi quy so với trước khi bắt đầu plan.

- [ ] **Step 2: Cập nhật `docs/progress.md`/`docs/trang-thai-ngan.md`**

Ghi: BE của `ARCH-37` xong (route mới, filter mới), tên nhánh, chưa mở PR (chờ user yêu cầu theo quy ước git). Không tự push/PR/merge.

- [ ] **Step 3: Commit (repo docs)**

```bash
git add docs/progress.md docs/trang-thai-ngan.md
git commit -m "docs: BE cho ARCH-37 (update-series + filter buổi học) xong"
```

---

## Self-Review

**Spec coverage:** Task 3–4 phủ "API mới: sửa buổi này và từ nay trở đi" (route, Action, 2 nhánh hủy/sửa, sinh thêm buổi, bất biến rút ngắn `effective_until`). Task 5 phủ "API đổi: `GET /classes/{id}/lesson-sessions` thêm filter" (3 field, join `teacher_class_assignments` đúng tại thời điểm buổi). Task 1–2 là 2 refactor nền cần trước (trait dùng chung, `windowStart` tùy chọn) mà spec có nêu nhưng để builder tự quyết cách làm — đã quyết cụ thể ở đây. Phần UI/FE không thuộc phạm vi plan này (đúng theo yêu cầu chỉ làm BE).

**Step scan:** mỗi step chỉ có một việc kiểm được; thân thuật toán ở Task 3 Step 3 và filter ở Task 5 Step 5 có code vì đây là phần tự signature/test không xác định được (thuật toán 2 nhánh hủy/sửa, câu join SQL) — giữ lại theo đúng ngoại lệ "thân hàm chỉ viết khi test/signature không quyết định được".

**Type consistency:** `UpdateScheduleAndFollowingSessions::handle` trả `Schedule` khớp `UpdatedScheduleResource` nhận `Schedule` (constructor `JsonResource` nhận bất kỳ model, không cần khớp kiểu tường minh, nhưng field đọc trong Resource — `local_start_time`, `duration_minutes`, `weekdays`, `id` — đều có trên `Schedule`, khớp). `ListLessonSessionsQuery::handle` tham số mới cùng tên/kiểu ở Request accessor và Controller.

**Review Focus:** cả 5 dòng đều có task/test sở hữu — #1 Task 3, #2 và #4 Task 4, #3 Task 4 (test riêng), #5 Task 5.

**Proportion:** plan dài hơn spec vì có 2 đoạn thuật toán cụ thể (bắt buộc, spec cố ý để builder tự viết nhưng đây là phần dễ sai nhất đã được tôi quyết blockquote rõ) và nhiều khối test — còn lại là tên file/signature/lệnh chạy, không lặp lại mô tả spec.

---

## Review Focus — nhắc lại khi review

Khi review PR của plan này, ưu tiên xem đúng 5 điểm ở mục "Review Focus" phía trên còn bị bỏ sót không, trước khi xem style/convention.
