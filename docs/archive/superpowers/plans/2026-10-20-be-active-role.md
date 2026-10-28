# Task 0 — `Support/ActiveRole` (ARCH-21) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mọi chỗ trong `app/Modules/**` cần biết "vai trò đang hoạt động" đọc từ một nguồn duy nhất `App\Support\ActiveRole` (không đọc session/`Request`), kiểm tra liên kết phụ huynh-học sinh và "Teacher dạy học sinh này" nằm trong `ClassScope`, có rule kiến trúc canh — không đổi bất kỳ HTTP status, `error.code`, `error.message` hay key JSON nào.

**Architecture:** `ActiveRole` là một object giữ giá trị (mutable), đăng ký `scoped` trong container. Middleware `LoadActiveRole` (nhóm `api`) chép `active_role_code` từ session vào mỗi request; job/console tự `set()`. Policy và Query inject `ActiveRole` qua constructor; `ClassScope` vẫn static, nhận vai trò qua tham số tường minh và không bao giờ tự đọc vai trò. Test đặc tả hành vi viết trước trên code cũ, rồi mới đổi code.

**Tech Stack:** Laravel 11 (`^11.31`), PHP 8.2, PostgreSQL, PHPUnit, Pint. Container WSL `piclass-local-api-1`.

**Spec:** `docs/architecture/open-decisions.md` mục `ARCH-21` (hướng đã chốt), `ARCH-11` (vai trò đang hoạt động lưu ở session, đổi qua `POST /api/v1/auth/context`), `ARCH-17`, `ARCH-20` (404 luôn trả `'Request failed.'`); `piclass-api/CLAUDE.md` mục "Phân quyền"; `docs/architecture/backend-refactor-playbook.md` (bẫy giữ nguyên hành vi, mẫu test, bảng "Nợ đã biết").

---

## Quy tắc cho người thực thi (đọc trước khi làm bất cứ gì)

Phase A trước đây có 13 quyết định người thực thi tự chọn. Plan này đã chốt mọi quyết định thiết kế (mục "Quyết định đã chốt"). Vì vậy:

1. **Gặp điểm mơ hồ, hoặc cần một quyết định không có trong plan: DỪNG và báo** (nêu file, dòng, các phương án bạn thấy). Không tự chọn, không "làm tạm rồi báo sau".
2. Các tình huống bắt buộc DỪNG (không giới hạn ở đây):
   - Một test đặc tả hành vi (Task 1) **fail trên code cũ**. Không sửa kỳ vọng cho khớp code, không sửa code.
   - Một test cũ (có trước Task này) fail sau khi đổi code, hoặc bạn thấy cần sửa một test cũ. Ngoại lệ duy nhất: thêm method mới vào `tests/Feature/Support/ClassScopeTest.php` (Task 4).
   - `grep` ở Task 3 Step 1 tìm thấy chỗ đọc `active_role_code`/`session()` trong `app/Modules/**` **ngoài** danh sách của plan.
   - Rule R6/R7 (Task 5) báo vi phạm ở file mà plan không nhắc tới.
   - Pint báo lỗi ở file bạn không sửa (4 file test đang lỗi sẵn trên `main` là bình thường, bỏ qua — xem Global Constraints).
   - Container `piclass-local-api-1` không chạy. **Không** bật Docker Desktop trên Windows.
3. Không đụng file ngoài danh sách "Files" của từng task. Đặc biệt **không sửa**: controller cũ trong `app/Http/Controllers/Api/**`, `app/Http/Middleware/EnsureRole.php`, `ContextController`, `MeController`, `ClassScope::classIdsFor`, `ClassScope::authorizeTeacherWrite`, `ClassScope::studentAndParentRecipients`, `ClassScope::activeRecipientsFor`.
4. Trong test, message luôn viết **chuỗi literal** (`'Request failed.'`, `'Tài khoản không có hồ sơ Parent.'`...), không dùng hằng `DenialMessage::NOT_FOUND` — để nếu ai đổi hằng thì test bắt được.
5. Mỗi task kết thúc bằng commit local. **Tuyệt đối không push, không mở PR, không merge, không xóa branch.**
6. Không dùng git worktree: container mount đúng thư mục `C:\Workspaces\home\PiClass\piclass-api` (rulings Phase A #5).

## Global Constraints

- Giữ nguyên hành vi: HTTP status, `error.code`, `error.message`, tập key JSON của mọi endpoint không đổi.
- Baseline: **625 test pass** (PHPUnit, full suite ~250 giây). Mọi task kết thúc với 0 test fail và số test = 625 + số test mới đã thêm tới lúc đó.
- Pint đang lỗi sẵn ở 4 file test trên `main`: không sửa, không tính vào tiêu chí. Chỉ chạy `pint --test` trên các file `.php` mà branch này đổi.
- Mọi case 403/404 trong test mới assert **cả** `error.code` lẫn `error.message`.
- Một user có nhiều vai trò được tính quyền theo **vai trò đang hoạt động** ở mọi chỗ (`BR-USER-002`, `ARCH-11`).
- Code, tên biến, comment trong code, commit message: tiếng Anh. Tài liệu: tiếng Việt.
- Commit message kết thúc bằng dòng `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>` (theo attribution của phiên đang chạy; nếu phiên thực thi có attribution khác thì dùng của phiên đó).

### Chạy test (backend trong container WSL)

Chạy từ Git Bash. Container `piclass-local-api-1` mount `/mnt/c/Workspaces/home/PiClass/piclass-api` → `/var/www/html`, nên file sửa trên Windows thấy ngay trong container.

```bash
# Kiểm tra container đang chạy (phải thấy piclass-local-api-1)
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker ps --format "{{.Names}}"'

# Một file test
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test tests/Feature/Authorization/ActiveRoleMultiRoleTest.php'

# Lọc theo tên
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test --filter=ClassScopeTest'

# Full suite (~250 giây): chạy nền (Bash tool với run_in_background), ghi log ra scratchpad, đọc dòng "Tests:" cuối log
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test' > "<scratchpad>/full-suite.log" 2>&1
```

Chỉ truyền đường dẫn test **tương đối** (`tests/...`) để Git Bash không đổi đường dẫn kiểu MSYS.

### Git

- BE (`piclass-api`): `git fetch origin && git checkout -b refactor/active-role origin/main`. Working tree phải sạch trước khi tạo branch (nếu không sạch: DỪNG và báo).
- Docs (repo gốc `C:\Workspaces\home\PiClass`): chỉ tạo ở Task 6. `git fetch origin`; nếu `origin/main` đã có file `docs/archive/superpowers/plans/2026-10-20-be-active-role.md` thì `git checkout -b docs/active-role origin/main`, nếu chưa thì `git checkout -b docs/active-role docs/plan-active-role` (để plan đi cùng).
- Commit local; không push/PR/merge; không xóa branch.

## Quyết định đã chốt

Mỗi dòng: quyết định, lý do ngắn, phương án đã loại. Người thực thi không chọn lại.

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | `ActiveRole` là object giữ giá trị (`final class`, field `?string`), đăng ký `$this->app->scoped(ActiveRole::class)` trong `AppServiceProvider::register()` | `scoped` = một instance mỗi request và mỗi job (queue worker gọi `forgetScopedInstances()` trước mỗi job), đúng `ARCH-21` | `singleton` (giá trị rò từ job này sang job khác/Octane); `bind` (mỗi lần resolve một instance mới, giá trị middleware ghi bị mất); closure lazy đọc `request()->session()` (ActiveRole phụ thuộc HTTP, console không có session thì ném lỗi; `ARCH-21` chốt middleware ghi) |
| D2 | API tối thiểu: `set(?string)`, `code(): ?string`, `isAdmin(): bool`; không validate giá trị; hằng `SESSION_KEY = 'active_role_code'` | Trong `app/Modules` chỉ cần "có phải admin không"; `ContextController` đã validate `in:admin,teacher,student,parent` trước khi ghi session | Enum vai trò (ngoài phạm vi; kéo theo `ClassScope::classIdsFor` và ~10 controller cũ); validate trong `set()` (session lạ sẽ thành 500 thay vì hành vi cũ) |
| D3 | Middleware mới `App\Http\Middleware\LoadActiveRole`, gắn `append` vào nhóm `api` trong `bootstrap/app.php`; **luôn ghi đè** (`null` khi session không có key hoặc request không có session) | Chạy sau `EnsureFrontendRequestsAreStateful` (đã start session), trước mọi FormRequest/Policy; ghi đè để không còn giá trị cũ khi cùng một app xử lý nhiều request (test, Octane) | Nạp trong `EnsureRole` (route không có `role:` như `/auth/context`, `/me` sẽ không có giá trị; trộn cổng chặn với nạp dữ liệu); middleware gắn từng route (dễ quên) |
| D4 | Không set (job/console quên set) ⇒ `code() === null`, `isAdmin() === false`: Policy/Query xử lý như người không phải admin (từ chối theo mặc định) | An toàn mặc định; Policy gọi từ job không ném lỗi | Ném exception khi chưa set (làm hỏng các kiểm tra không cần vai trò, ví dụ `AttemptPolicy`) |
| D5 | `ClassScope` giữ static, không đọc `ActiveRole`: vai trò truyền qua tham số (`classIdsFor($user, $role)`) hoặc nằm trong tên hàm (`teacherCovers*`, `parentCoversStudent`) | Đúng định nghĩa trong CLAUDE.md: "User X **với vai trò R** thấy bản ghi nào?"; không đổi chữ ký mà controller cũ đang gọi | `ClassScope` gọi `app(ActiveRole::class)` (phụ thuộc ẩn, vi phạm rule 9 DI, đổi chữ ký cho ~10 controller cũ) |
| D6 | Query inject `ActiveRole` qua constructor (`ListSubmissions`, `PublishableResultsQuery`); bỏ tham số vai trò khỏi `ListSubmissions::handle`, `PublishableResultsQuery::handle`, `BulkPublishResults::handle`; bỏ `ListSubmissionsRequest::actingAsAdmin()`; controller không còn đụng tới vai trò | Query lặp lại đúng rule của Policy (`listForAssessment`), nên cả hai phải đọc cùng một nguồn; đúng câu "Policy, ClassScope và Query đọc vai trò qua class này" của `ARCH-21` | Controller đọc `ActiveRole` rồi truyền bool/chuỗi xuống (hai đường đọc vai trò, Policy và Query có thể lệch nhau) |
| D7 | Kiểm tra phụ huynh-học sinh: thêm `StudentPolicy::viewAsParent(User $user, string $studentId): Response`, gọi bằng `Gate::inspect('viewAsParent', [Student::class, $this->route('studentId')])` trong `GetChildResultRequest`/`GetChildProgressRequest`; Policy dùng `ClassScope::parentCoversStudent` | CLAUDE.md: `authorize()` gọi Policy; gom kiểm tra hồ sơ Parent (403) đang lặp ở 2 FormRequest về một chỗ; cách gọi theo class có tiền lệ (`listForAssessment`) | FormRequest gọi thẳng `ClassScope` (kiểm tra hồ sơ vẫn lặp inline ở 2 file); đổi route sang bind `Student` (đổi đường đi 404 và chữ ký controller) |
| D8 | `ClassScope::teacherCoversStudent` chép **nguyên văn** biểu thức hiện có trong `StudentPolicy::viewProgress` (gồm `Course::where('owner_teacher_id', ...)->whereHas('enrollments', ...)`) | Giữ đúng câu SQL, không rủi ro lệch điều kiện | Viết lại qua `ownedCourseIdsFor` (đổi truy vấn, có thể lệch trạng thái enrollment) |
| D9 | Hằng `App\Support\DenialMessage::NOT_FOUND = 'Request failed.'`, dùng ở 4 Policy và nhánh 404 của handler trong `bootstrap/app.php` (`ARCH-20` buộc hai chỗ này phải bằng nhau). Fallback `($e->getMessage() ?: 'Request failed.')` cho status khác 404 giữ literal (khác mục đích). Giá trị mặc định trong `ResultProgress/Exceptions/StudentAssessmentResultNotFound.php` **không đổi** ở task này (exception, không phải Policy; ngoài phạm vi mục 3) | Một chỗ duy nhất; handler và Policy không thể lệch nhau | Helper `Deny::notFound()` (đổi kiểu gọi ở mọi Policy, không thêm lợi ích) |
| D10 | **Không đụng** `ClassScope::authorizeTeacherWrite` (nhận `Request`, đọc session, `abort`). Rule R6/R7 cho phép đúng 3 dòng của nó qua danh sách ngoại lệ khớp **nguyên dòng**, tự hết hạn (test fail khi dòng đó biến mất) | Hàm thuộc Attendance/Lesson chưa refactor (7 controller cũ gọi); dời đi bây giờ phải sửa 7 controller cũ + viết test đặc tả cho 7 endpoint, rồi lại dời lần nữa sang Policy khi refactor Attendance/Lesson. Ngoại lệ khớp nguyên dòng nên mọi chỗ đọc session/`Request` mới trong `ClassScope` vẫn bị bắt | Dời sang một class guard tạm trong `app/Http` (chạm 7 file module chưa refactor); miễn trừ cả file `ClassScope.php` (để lọt chỗ đọc mới) |
| D11 | Rule kiến trúc: **R6** — `Policies/**`, `Queries/**` của mọi module trong `MODULES` và `app/Support/ClassScope.php`: không import `Illuminate\Http\Request`, không type hint `Request $x`, không gọi `request()`. **R7** — mọi file của module trong `MODULES` và mọi file trong `app/Support/**`: không `->session(`, `session(`, `Session::` | Nơi ghi session/đọc để chặn (`LoadActiveRole`, `EnsureRole`, `ContextController`) nằm ở `app/Http`, không bị quét; controller cũ chưa thuộc `MODULES` | Cấm chuỗi `'active_role_code'` (scanner xóa string literal trước khi quét; cấm đọc session đã đủ) |
| D12 | Test đặc tả hành vi đặt ở file mới; không sửa test cũ | Playbook: chỉ sửa test cũ khi đổi cấu trúc/namespace | Thêm case vào `GetChildResultAndProgressTest`/`ListSubmissionsTest` cũ |
| D13 | Không đụng `StudentAssessmentResultQuery::handle(..., 'parent'/'student')` trong `GetChildResultController`/`GetMyResultController` | Đó là "góc nhìn" cố định theo endpoint (route đã chặn bằng `role:`), không phải vai trò đang hoạt động | — |
| D14 | `EnsureRole`, `ContextController`, `MeController` và controller cũ vẫn đọc session trực tiếp; ghi vào bảng "Nợ đã biết" của playbook (chuyển khi refactor module tương ứng) | Ngoài phạm vi; cùng một request, `EnsureRole` và `LoadActiveRole` đọc cùng một key nên không thể lệch | Sửa luôn `EnsureRole` đọc `ActiveRole` (đổi thứ tự phụ thuộc giữa middleware nhóm và middleware route, không cần cho Task 0) |

## Review Focus

1. **Giá trị vai trò cũ còn sót giữa các request trong cùng một process** (test chạy nhiều request trên một app, Octane, queue worker): user đổi vai trò qua `POST /api/v1/auth/context` thì request kế tiếp phải theo vai trò mới. Test: `test_switching_to_teacher_through_the_context_endpoint_drops_the_admin_bypass_on_the_next_request` (Task 1), `test_overwrites_a_stale_value_with_null_when_the_session_has_no_role` (Task 2), `test_forgetting_scoped_instances_starts_a_fresh_empty_holder` (Task 2).
2. **User vừa admin vừa teacher, đang ở vai trò teacher, không được hưởng quyền admin ở bất kỳ chỗ nào** (Policy lẫn Query lọc danh sách). Test: `ActiveRoleMultiRoleTest` (Task 1, HTTP) và `ActiveRoleOutsideHttpTest` (Task 3, không HTTP).
3. **Gọi Policy/Query/ClassScope ngoài HTTP (job/console) không có session**: không ném lỗi, mặc định từ chối, `set()` thì áp đúng vai trò. Test: `ActiveRoleOutsideHttpTest` (Task 3).
4. **Request API không có session** (không gửi `Referer`/`Origin`, Sanctum không bật stateful): middleware mới không được ném "Session store not set on request.". Test: `test_request_without_a_session_gets_null_and_does_not_fail` (Task 2).
5. **Message lệch khi gom hằng**: 404 luôn `'Request failed.'`, 403 thiếu hồ sơ Parent luôn `'Tài khoản không có hồ sơ Parent.'`, endpoint `/students/{student}/progress` (hiện **chưa có test nào**) giữ nguyên status/message/key. Test: `ChildAccessAuthorizationTest`, `StudentProgressAuthorizationTest` (Task 1).

---

## Bản đồ file

| File | Trách nhiệm | Task |
|---|---|---|
| `app/Support/ActiveRole.php` (mới) | Giữ vai trò đang hoạt động của request/job hiện tại | 2 |
| `app/Http/Middleware/LoadActiveRole.php` (mới) | Chép `active_role_code` từ session vào `ActiveRole` | 2 |
| `app/Providers/AppServiceProvider.php` | Đăng ký `scoped` | 2 |
| `bootstrap/app.php` | Gắn middleware vào nhóm `api` (Task 2); nhánh 404 dùng `DenialMessage::NOT_FOUND` (Task 3) | 2, 3 |
| `app/Support/DenialMessage.php` (mới) | Hằng message 404 dùng chung | 3 |
| `app/Modules/*/Policies/{ResultPolicy,StudentPolicy,SubmissionPolicy,AttemptPolicy}.php` | Đọc vai trò qua `ActiveRole`; message qua `DenialMessage` | 3, 4 |
| `app/Modules/SubmissionGrading/{Queries/ListSubmissions,Http/Requests/ListSubmissionsRequest,Http/Controllers/ListSubmissionsController}.php` | Query tự đọc `ActiveRole` | 3 |
| `app/Modules/ResultProgress/{Queries/PublishableResultsQuery,Actions/BulkPublishResults,Http/Controllers/BulkPublishResultsController}.php` | Query tự đọc `ActiveRole` | 3 |
| `app/Support/ClassScope.php` | Thêm `teacherCoversStudent`, `parentCoversStudent` | 4 |
| `app/Modules/ResultProgress/Http/Requests/{GetChildResultRequest,GetChildProgressRequest}.php` | `authorize()` gọi `StudentPolicy::viewAsParent` | 4 |
| `tests/Architecture/ArchitectureTest.php` | R6, R7 | 5 |
| `piclass-api/CLAUDE.md`, docs repo: playbook, `open-decisions.md`, `progress.md` | Ghi nhận | 6 |

---

## Chuẩn bị (trước Task 1)

- [ ] **P1: Kiểm tra container.** Chạy lệnh `docker ps` ở mục "Chạy test". Expected: có dòng `piclass-local-api-1`. Không có: DỪNG.
- [ ] **P2: Tạo branch BE.** Trong `C:\Workspaces\home\PiClass\piclass-api`: `git status --short` (phải rỗng), rồi `git fetch origin && git checkout -b refactor/active-role origin/main`.
- [ ] **P3: Baseline.** Chạy full suite (nền). Expected: dòng `Tests:` báo `625 passed`, 0 failed. Khác 625: DỪNG và báo con số.

---

### Task 1: Test đặc tả hành vi trên code cũ

Mục đích: ghim hành vi hiện tại của mọi endpoint mà Task 2-4 chạm vào, đặc biệt tình huống một user nhiều vai trò. **Tất cả test của task này phải xanh trên code CŨ.** Nếu một test đỏ: DỪNG và báo (đó là lỗ hổng hoặc hiểu sai hành vi, user quyết định).

**Files:**
- Create: `tests/Feature/Authorization/ActiveRoleMultiRoleTest.php`
- Create: `tests/Feature/ResultProgress/StudentProgressAuthorizationTest.php`
- Create: `tests/Feature/ResultProgress/ChildAccessAuthorizationTest.php`

**Interfaces:**
- Consumes: endpoint hiện có — `GET /api/v1/submissions/{submission}`, `GET /api/v1/assessments/{assessment}/submissions`, `POST /api/v1/results/{result}/publish`, `POST /api/v1/assessments/{assessment}/results/publish`, `GET /api/v1/students/{student}/progress` (route `role:admin,teacher`); `GET /api/v1/me/children/{studentId}/assessments/{assessment}/result`, `GET /api/v1/me/children/{studentId}/progress` (route `role:parent`); `POST /api/v1/auth/context` body `{role_code}`.
- Produces: không có code sản phẩm.

- [ ] **Step 1: Viết `ActiveRoleMultiRoleTest`.** Fixture và helper đúng như dưới (mẫu theo `GradingAuthorizationMatrixTest`/`ResultAuthorizationMatrixTest`):

```php
namespace Tests\Feature\Authorization;

/**
 * One user holding both admin and teacher roles (BR-USER-002, ARCH-11). While the active role is
 * teacher, every role-dependent check (SubmissionPolicy, ResultPolicy, StudentPolicy, ListSubmissions,
 * PublishableResultsQuery) must apply teacher scope, never the admin bypass. Characterization test:
 * green before ActiveRole (ARCH-21) and must stay green after.
 */
class ActiveRoleMultiRoleTest extends TestCase
{
    use RefreshDatabase;

    private const NOT_FOUND = 'Request failed.';

    private User $dual;

    private Assessment $assessment;

    /** @var array{student: Student, submission: Submission, result: Result} in class A (taught by $dual) */
    private array $mine;

    /** @var array{student: Student, submission: Submission, result: Result} in class B (not taught by $dual) */
    private array $other;

    protected function setUp(): void
    {
        parent::setUp();
        $classA = SchoolClass::factory()->create();
        $classB = SchoolClass::factory()->create();
        $this->assessment = Assessment::factory()->create(); // created by another user
        $version = AssessmentVersion::factory()->create(['assessment_id' => $this->assessment->id]);
        AssessmentAudience::factory()->create(['assessment_version_id' => $version->id, 'class_id' => $classA->id]);
        AssessmentAudience::factory()->create(['assessment_version_id' => $version->id, 'class_id' => $classB->id]);
        $this->mine = $this->gradedWorkIn($classA, $version);
        $this->other = $this->gradedWorkIn($classB, $version);

        $profile = PersonProfile::factory()->create();
        $this->dual = User::factory()->create(['person_profile_id' => $profile->id]);
        foreach (['admin', 'teacher'] as $code) {
            $role = Role::firstOrCreate(['code' => $code], Role::factory()->make(['code' => $code])->toArray());
            UserRole::create(['user_id' => $this->dual->id, 'role_id' => $role->id, 'valid_from' => now()->subDay()]);
        }
        $teacher = Teacher::factory()->create(['person_profile_id' => $profile->id]);
        TeacherClassAssignment::factory()->create(['teacher_id' => $teacher->id, 'class_id' => $classA->id]);
    }

    /** Student (active member of $class) with a submission and a complete, unpublished result. */
    private function gradedWorkIn(SchoolClass $class, AssessmentVersion $version): array
    {
        $student = Student::factory()->create();
        ClassMember::factory()->create(['student_id' => $student->id, 'class_id' => $class->id]);
        $sa = StudentAssessment::factory()->create([
            'assessment_id' => $this->assessment->id, 'student_id' => $student->id,
            'source_class_id' => $class->id, 'assigned_version_id' => $version->id,
        ]);
        $attempt = Attempt::factory()->create(['student_assessment_id' => $sa->id, 'assessment_version_id' => $version->id]);
        $submission = Submission::factory()->create(['attempt_id' => $attempt->id]);
        $result = Result::factory()->create(['submission_id' => $submission->id, 'grading_status' => 'complete', 'publication_status' => 'unpublished']);

        return compact('student', 'submission', 'result');
    }

    private function actAs(string $role): void
    {
        $this->actingAs($this->dual)->withSession(['active_role_code' => $role]);
    }

    private function assertNotFound(TestResponse $response): void
    {
        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonPath('error.message', self::NOT_FOUND);
    }
}
```

Các test (tên và assertion chốt; `$a = $this->assessment->id`):

| Test | Hành động | Assertion |
|---|---|---|
| `test_as_teacher_a_submission_of_another_class_is_404` | `actAs('teacher')`; `getJson("/api/v1/submissions/{other.submission.id}")` | `assertNotFound` |
| `test_as_admin_a_submission_of_another_class_is_visible` | `actAs('admin')`; cùng request | `assertOk()` |
| `test_as_teacher_the_submission_list_only_has_own_class` | `actAs('teacher')`; `getJson("/api/v1/assessments/{$a}/submissions")` | `assertOk()`; `assertSame([mine.submission.id], collect($r->json('data'))->pluck('id')->all())` |
| `test_as_admin_the_submission_list_has_every_class` | `actAs('admin')`; cùng request | `assertEqualsCanonicalizing([mine.submission.id, other.submission.id], ...pluck('id')->all())` |
| `test_as_teacher_publishing_another_class_result_is_404` | `actAs('teacher')`; `postJson("/api/v1/results/{other.result.id}/publish")` | `assertNotFound`; `assertSame('unpublished', other.result->refresh()->publication_status)` |
| `test_as_admin_publishing_another_class_result_succeeds` | `actAs('admin')`; cùng request | `assertOk()`; `'published'` |
| `test_as_teacher_bulk_publish_only_publishes_own_class` | `actAs('teacher')`; `postJson("/api/v1/assessments/{$a}/results/publish")` | `assertOk()`; `assertSame([mine.result.id], $r->json('published'))`; other.result vẫn `'unpublished'` |
| `test_as_admin_bulk_publish_publishes_every_class` | `actAs('admin')`; cùng request | `assertEqualsCanonicalizing([mine.result.id, other.result.id], $r->json('published'))` |
| `test_as_teacher_progress_of_another_class_student_is_404` | `actAs('teacher')`; `getJson("/api/v1/students/{other.student.id}/progress")` | `assertNotFound` |
| `test_as_admin_progress_of_another_class_student_is_visible` | `actAs('admin')`; cùng request | `assertOk()` |
| `test_switching_to_teacher_through_the_context_endpoint_drops_the_admin_bypass_on_the_next_request` | `actAs('admin')`; GET submission other → `assertOk()`; `postJson('/api/v1/auth/context', ['role_code' => 'teacher'])->assertOk()`; GET submission other lần nữa | lần 2: `assertNotFound` |
| `test_switching_to_admin_through_the_context_endpoint_restores_the_bypass` | `actAs('teacher')`; GET submission other → `assertNotFound`; `postJson('/api/v1/auth/context', ['role_code' => 'admin'])->assertOk()`; GET lần nữa | lần 2: `assertOk()` |

- [ ] **Step 2: Chạy trên code cũ.** `wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test tests/Feature/Authorization/ActiveRoleMultiRoleTest.php'`. Expected: 12 passed. Có test đỏ: DỪNG.

- [ ] **Step 3: Viết `StudentProgressAuthorizationTest`** (endpoint này hiện **không có test nào**). Helper `userWithRole(string $roleCode, bool $withTeacherProfile = false): User` chép từ `ResultAuthorizationMatrixTest` (bỏ tham số `$assignedTo`, gán lớp/khóa trong từng test). Hằng `PAYLOAD_KEYS = ['average_percentage', 'by_subject', 'graded_count', 'pending_count']`, `ROLE_DENIED = 'Bạn không có quyền thực hiện hành động này.'`. Mỗi test tạo `Student::factory()` + `ClassMember` ở một lớp `classS`.

| Test | Thiết lập | Assertion |
|---|---|---|
| `test_admin_sees_any_student_progress_with_the_flat_payload` | admin | `assertOk()`; `assertEqualsCanonicalizing(PAYLOAD_KEYS, array_keys($r->json()))` |
| `test_teacher_of_the_students_class_sees_the_progress` | teacher có `TeacherClassAssignment` ở `classS` | `assertOk()` |
| `test_teacher_owning_a_course_the_student_is_actively_enrolled_in_sees_the_progress` | teacher không dạy `classS`; `Course::factory(['owner_teacher_id' => $teacher->id])`; `CourseEnrollment::factory(['course_id', 'student_id', 'status' => 'active'])` | `assertOk()` |
| `test_teacher_whose_course_enrollment_for_the_student_ended_gets_404` | như trên nhưng enrollment `['status' => 'ended', 'valid_to' => now()->subDay()]` | 404, `NOT_FOUND`, `'Request failed.'` |
| `test_teacher_of_another_class_gets_404` | teacher dạy lớp khác | 404, `NOT_FOUND`, `'Request failed.'` |
| `test_teacher_role_without_teacher_profile_gets_404` | `userWithRole('teacher')` không hồ sơ | 404, `NOT_FOUND`, `'Request failed.'` |
| `test_student_and_parent_roles_are_blocked_by_role_middleware` (DataProvider `['student'], ['parent']`) | user có đúng vai trò đó | 403, `FORBIDDEN`, `ROLE_DENIED` |
| `test_unknown_student_id_gets_404` | admin; `Str::uuid()` | 404, `NOT_FOUND`, `'Request failed.'` |

- [ ] **Step 4: Chạy trên code cũ.** Expected: 9 passed (8 test, test DataProvider tính 2). Đỏ: DỪNG.

- [ ] **Step 5: Viết `ChildAccessAuthorizationTest`.** DataProvider `childEndpoints()` trả `['result' => ['result'], 'progress' => ['progress']]`; helper `url(string $endpoint, string $studentId): string` trả `/api/v1/me/children/{$studentId}/assessments/{$this->assessment->id}/result` hoặc `/api/v1/me/children/{$studentId}/progress` (`$this->assessment = Assessment::factory()->create()` trong `setUp`). Helper `parentUser(bool $withParentProfile = true): User` (vai trò `parent`, `withSession(['active_role_code' => 'parent'])`, hồ sơ `ParentProfile` cùng `person_profile_id`).

| Test | Thiết lập | Assertion |
|---|---|---|
| `test_a_parent_role_user_without_parent_profile_gets_403` (DataProvider) | `parentUser(false)` | 403, `FORBIDDEN`, `'Tài khoản không có hồ sơ Parent.'` |
| `test_a_parent_with_no_link_to_the_student_gets_404` (DataProvider) | parent có hồ sơ, không có `ParentStudentRelationship` | 404, `NOT_FOUND`, `'Request failed.'` |
| `test_a_parent_with_an_ended_link_gets_404` (DataProvider) | relationship `['status' => 'ended', 'valid_to' => now()->subDay()]` | 404, `NOT_FOUND`, `'Request failed.'` |
| `test_a_linked_parent_gets_the_progress_payload` | relationship `status = 'active'` | `assertOk()`; `assertEqualsCanonicalizing(['average_percentage', 'by_subject', 'graded_count', 'pending_count'], array_keys($r->json()))` |

- [ ] **Step 6: Chạy trên code cũ.** Expected: 7 passed. Đỏ: DỪNG.

- [ ] **Step 7: Format + commit.** `vendor/bin/pint` trên 3 file mới (trong container: `docker exec piclass-local-api-1 vendor/bin/pint tests/Feature/Authorization/ActiveRoleMultiRoleTest.php tests/Feature/ResultProgress/StudentProgressAuthorizationTest.php tests/Feature/ResultProgress/ChildAccessAuthorizationTest.php`).

```bash
git add tests/Feature/Authorization tests/Feature/ResultProgress/StudentProgressAuthorizationTest.php tests/Feature/ResultProgress/ChildAccessAuthorizationTest.php
git commit -m "test: characterize role-dependent authorization before ActiveRole

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

**Tiêu chí xong:** 28 test mới xanh trên code cũ; không file `app/` nào đổi. **Điểm dừng review** (Sonnet đủ): reviewer đối chiếu bảng trên, kiểm tra mọi case 403/404 có cả `error.code` và `error.message`.

---

### Task 2: `ActiveRole` + middleware `LoadActiveRole` — **cần Opus review**

**Files:**
- Create: `app/Support/ActiveRole.php`
- Create: `app/Http/Middleware/LoadActiveRole.php`
- Modify: `app/Providers/AppServiceProvider.php` (`register()`)
- Modify: `bootstrap/app.php` (khối `withMiddleware`)
- Test: `tests/Unit/Support/ActiveRoleTest.php`, `tests/Feature/Support/ActiveRoleBindingTest.php`, `tests/Feature/Http/LoadActiveRoleMiddlewareTest.php`

**Interfaces:**
- Produces: `App\Support\ActiveRole` với `public const SESSION_KEY = 'active_role_code'`, `set(?string $code): void`, `code(): ?string`, `isAdmin(): bool`; binding `scoped`; middleware `App\Http\Middleware\LoadActiveRole` trong nhóm `api`.

- [ ] **Step 1: Viết test unit** `tests/Unit/Support/ActiveRoleTest.php` (kế thừa `PHPUnit\Framework\TestCase`, không cần app):
  - `test_starts_without_a_role`: `assertNull($r->code())`, `assertFalse($r->isAdmin())`.
  - `test_admin_code_is_admin`: `set('admin')` → `assertSame('admin', $r->code())`, `assertTrue($r->isAdmin())`.
  - `test_teacher_code_is_not_admin`: `set('teacher')` → `assertFalse($r->isAdmin())`.
  - `test_setting_null_clears_the_role`: `set('admin')`, `set(null)` → `assertNull($r->code())`, `assertFalse($r->isAdmin())`.

- [ ] **Step 2: Viết test binding** `tests/Feature/Support/ActiveRoleBindingTest.php` (kế thừa `Tests\TestCase`):
  - `test_resolves_the_same_instance_within_a_scope`: `assertSame(app(ActiveRole::class), app(ActiveRole::class))`.
  - `test_forgetting_scoped_instances_starts_a_fresh_empty_holder`: `app(ActiveRole::class)->set('admin')`; `app()->forgetScopedInstances()` (mô phỏng queue worker sang job mới); `assertNull(app(ActiveRole::class)->code())`.

- [ ] **Step 3: Viết test middleware** `tests/Feature/Http/LoadActiveRoleMiddlewareTest.php`. Trong `setUp()` đăng ký route thử chỉ với nhóm `api` (không `auth`):

```php
Route::middleware('api')->get('/api/v1/__test/active-role', fn (ActiveRole $role) => response()->json(['code' => $role->code()]));
```

  - `test_copies_the_session_role_into_active_role`: `withSession(['active_role_code' => 'teacher'])->getJson(...)` → `assertOk()`, `assertJsonPath('code', 'teacher')`.
  - `test_sets_null_when_the_session_has_no_role`: `getJson(...)` → `assertJsonPath('code', null)`.
  - `test_overwrites_a_stale_value_with_null_when_the_session_has_no_role`: `app(ActiveRole::class)->set('admin')` trước; `getJson(...)` → `assertJsonPath('code', null)`.
  - `test_request_without_a_session_gets_null_and_does_not_fail`: `withoutHeader('Referer')->getJson(...)` (Sanctum không bật session) → `assertOk()`, `assertJsonPath('code', null)`.

- [ ] **Step 4: Chạy để thấy đỏ.** `... php artisan test tests/Unit/Support/ActiveRoleTest.php tests/Feature/Support/ActiveRoleBindingTest.php tests/Feature/Http/LoadActiveRoleMiddlewareTest.php`. Expected: FAIL, `Class "App\Support\ActiveRole" not found`.

- [ ] **Step 5: Tạo `app/Support/ActiveRole.php`** đúng như sau:

```php
<?php

namespace App\Support;

/**
 * The role the current actor is acting as (ARCH-11), read from one place only (ARCH-21).
 *
 * Registered as a scoped binding: every HTTP request and every queued job starts with a fresh,
 * empty instance. Web requests: App\Http\Middleware\LoadActiveRole copies the session value in.
 * Jobs/console: call set() before invoking a Policy or a Query (inject ActiveRole into handle()).
 * Unset means "no role": isAdmin() is false, so nothing is granted by default.
 *
 * Policies and Queries inject this class; they never read the session or the Request themselves.
 * ClassScope does not read it either: callers pass the role explicitly.
 */
final class ActiveRole
{
    public const SESSION_KEY = 'active_role_code';

    private ?string $code = null;

    public function set(?string $code): void
    {
        $this->code = $code;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function isAdmin(): bool
    {
        return $this->code === 'admin';
    }
}
```

- [ ] **Step 6: Tạo `app/Http/Middleware/LoadActiveRole.php`:**

```php
<?php

namespace App\Http\Middleware;

use App\Support\ActiveRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Copies the session's active role into ActiveRole for this request (ARCH-21). Always overwrites,
 * with null when the session has no role or the request has no session (non-stateful call), so a
 * value from an earlier request handled by the same application never survives.
 */
final class LoadActiveRole
{
    public function __construct(private ActiveRole $activeRole) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->activeRole->set($request->hasSession() ? $request->session()->get(ActiveRole::SESSION_KEY) : null);

        return $next($request);
    }
}
```

- [ ] **Step 7: Đăng ký.** `AppServiceProvider::register()`: thay `//` bằng `$this->app->scoped(ActiveRole::class);` (thêm `use App\Support\ActiveRole;`). `bootstrap/app.php`: đổi lời gọi hiện có thành

```php
$middleware->api(prepend: [
    EnsureFrontendRequestsAreStateful::class,
], append: [
    LoadActiveRole::class,
]);
```

  và thêm `use App\Http\Middleware\LoadActiveRole;`. Không đổi gì khác trong `bootstrap/app.php` ở task này.

- [ ] **Step 8: Chạy test task.** Cùng lệnh Step 4. Expected: 10 passed.

- [ ] **Step 9: Chạy full suite (nền).** Expected: 625 + 28 + 10 = **663 passed**, 0 failed.

- [ ] **Step 10: Pint + commit.** `pint --test` trên 7 file của task (sửa bằng `pint <file>` nếu báo). 

```bash
git add app/Support/ActiveRole.php app/Http/Middleware/LoadActiveRole.php app/Providers/AppServiceProvider.php bootstrap/app.php tests/Unit/Support tests/Feature/Support/ActiveRoleBindingTest.php tests/Feature/Http/LoadActiveRoleMiddlewareTest.php
git commit -m "feat: add scoped ActiveRole holder loaded from the session by middleware

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

**Tiêu chí xong:** 663 test xanh; chưa có code module nào dùng `ActiveRole`. **Điểm dừng: Opus review** (binding `scoped`, thứ tự middleware, ghi đè `null`).

---

### Task 3: Policy và Query đọc vai trò qua `ActiveRole`; hằng `DenialMessage` — **cần Opus review**

**Files:**
- Create: `app/Support/DenialMessage.php`
- Modify: `app/Modules/ResultProgress/Policies/ResultPolicy.php`, `app/Modules/ResultProgress/Policies/StudentPolicy.php`, `app/Modules/SubmissionGrading/Policies/SubmissionPolicy.php`, `app/Modules/SubmissionGrading/Policies/AttemptPolicy.php`
- Modify: `app/Modules/SubmissionGrading/Queries/ListSubmissions.php`, `app/Modules/SubmissionGrading/Http/Requests/ListSubmissionsRequest.php`, `app/Modules/SubmissionGrading/Http/Controllers/ListSubmissionsController.php`
- Modify: `app/Modules/ResultProgress/Queries/PublishableResultsQuery.php`, `app/Modules/ResultProgress/Actions/BulkPublishResults.php`, `app/Modules/ResultProgress/Http/Controllers/BulkPublishResultsController.php`
- Modify: `bootstrap/app.php` (một dòng nhánh 404)
- Test: `tests/Feature/Support/ActiveRoleOutsideHttpTest.php`

**Interfaces:**
- Consumes: `ActiveRole` (Task 2).
- Produces:
  - `final class App\Support\DenialMessage { public const NOT_FOUND = 'Request failed.'; private function __construct() {} }` (docblock: message of every `denyAsNotFound()` and of the 404 handler; ARCH-20 requires them to be equal).
  - `ResultPolicy::__construct(ActiveRole $activeRole)`, `StudentPolicy::__construct(ActiveRole $activeRole)`, `SubmissionPolicy::__construct(ActiveRole $activeRole)` — thay `Request $request`.
  - `ListSubmissions::__construct(ActiveRole $activeRole)`; `ListSubmissions::handle(Assessment $assessment, User $actor, ?string $gradingStatus = null): Collection`.
  - `PublishableResultsQuery::__construct(ActiveRole $activeRole)`; `PublishableResultsQuery::handle(Assessment $assessment, User $actor): Collection`.
  - `BulkPublishResults::handle(Assessment $assessment, User $actor): array` (bỏ `?string $activeRole`).
  - `ListSubmissionsRequest::actingAsAdmin()` bị xóa.

- [ ] **Step 1: Xác nhận danh sách chỗ cần đổi.** Trong `piclass-api`: `grep -rn "active_role_code\|session()" app/Modules app/Support`. Expected đúng 6 dòng: `BulkPublishResultsController.php:15`, `ResultPolicy.php:38`, `StudentPolicy.php:44`, `ListSubmissionsRequest.php:19`, `SubmissionPolicy.php:105`, `ClassScope.php:83` (cộng dòng docblock `ClassScope.php:16` chứa chữ `active_role_code`). Có dòng khác: DỪNG.

- [ ] **Step 2: Viết test ngoài HTTP** `tests/Feature/Support/ActiveRoleOutsideHttpTest.php`. Không gọi HTTP trong file này. Fixture giống `ActiveRoleMultiRoleTest` (chép `setUp`/`gradedWorkIn`; không cần `actAs`). Gọi Policy bằng `Gate::forUser($this->dual)->inspect(...)`, Query bằng `app(ListSubmissions::class)` / `app(PublishableResultsQuery::class)`, đặt vai trò bằng `app(ActiveRole::class)->set(...)`.

| Test | Assertion |
|---|---|
| `test_without_a_role_the_submission_policy_denies_another_class_as_not_found` | không set; `$res = Gate::forUser($dual)->inspect('view', other.submission)`; `assertTrue($res->denied())`, `assertSame(404, $res->status())`, `assertSame('Request failed.', $res->message())` |
| `test_with_admin_role_the_submission_policy_allows_another_class` | `set('admin')`; `assertTrue(...inspect('view', other.submission)->allowed())` |
| `test_with_teacher_role_the_submission_policy_denies_another_class` | `set('teacher')`; denied, 404, `'Request failed.'` |
| `test_result_policy_follows_the_active_role` | `set('teacher')` → `inspect('manage', other.result)` denied 404; `set('admin')` → allowed |
| `test_student_policy_follows_the_active_role` | `set('teacher')` → `inspect('viewProgress', other.student)` denied 404; `set('admin')` → allowed |
| `test_list_submissions_query_follows_the_active_role` | `set('teacher')` → `app(ListSubmissions::class)->handle($assessment, $dual)->pluck('id')->all()` `=== [mine.submission.id]`; `set('admin')` → canonical cả hai |
| `test_publishable_results_query_follows_the_active_role` | `set('teacher')` → `app(PublishableResultsQuery::class)->handle($assessment, $dual)->pluck('id')->all()` `=== [mine.result.id]`; `set('admin')` → canonical cả hai |

- [ ] **Step 3: Chạy để thấy đỏ.** Expected: FAIL — các test Policy ném `RuntimeException: Session store not set on request.`; các test Query fail vì sai số tham số / `actingAsAdmin`-bool bắt buộc (`ArgumentCountError`).

- [ ] **Step 4: Tạo `DenialMessage`** theo Interfaces. Trong `bootstrap/app.php`, nhánh 404 của handler `HttpExceptionInterface`: `$message = $status === 404 ? DenialMessage::NOT_FOUND : ($e->getMessage() ?: 'Request failed.');` (thêm `use App\Support\DenialMessage;`; fallback giữ literal — D9).

- [ ] **Step 5: Sửa 4 Policy.**
  - `ResultPolicy`, `SubmissionPolicy`, `StudentPolicy`: constructor → `private ActiveRole $activeRole`; xóa `use Illuminate\Http\Request;`; xóa private method `isAdmin()`; mọi `$this->isAdmin()` → `$this->activeRole->isAdmin()`. **Giữ nguyên thứ tự early-return** (đặc biệt `SubmissionPolicy::inScope` nạp `$sa->assessment` trước nhánh admin — có comment giải thích, không bỏ).
  - Cả 4 Policy (kể cả `AttemptPolicy`): xóa `private const NOT_FOUND = 'Request failed.';`, thay `self::NOT_FOUND` bằng `DenialMessage::NOT_FOUND`. `AttemptPolicy::NO_PROFILE` giữ nguyên.
  - `StudentPolicy::viewProgress`: chỉ đổi dòng admin ở task này (thân còn lại đổi ở Task 4).

- [ ] **Step 6: Sửa `ListSubmissions` + Request + Controller** theo Interfaces. Trong `handle()`, đọc `$isAdmin = $this->activeRole->isAdmin();` **trước** closure `whereHas`, rồi dùng `$isAdmin` trong closure như hiện tại. Controller: `$query->handle($assessment, $request->user(), $request->query('grading_status'))`. Xóa method `actingAsAdmin()` ở `ListSubmissionsRequest`.

- [ ] **Step 7: Sửa `PublishableResultsQuery` + `BulkPublishResults` + Controller** theo Interfaces. Trong Query, `$isAdmin = $this->activeRole->isAdmin();` trước closure; điều kiện thành `if (! $isAdmin && $assessment->created_by_user_id !== $actor->id)`. Action gọi `$this->query->handle($assessment, $actor)`. Controller: `$outcome = $action->handle($assessment, $request->user());`.

- [ ] **Step 8: Kiểm tra không còn đọc session trong module.** `grep -rn "session()\|active_role_code" app/Modules` và `grep -rnF 'Illuminate\Http\Request' app/Modules`. Expected: cả hai không có kết quả. `grep -rnF "'Request failed.'" app`. Expected đúng 4 dòng: `app/Support/DenialMessage.php`, `StudentAssessmentResultNotFound.php:16` (giá trị mặc định của exception, không đổi — D9), `GetChildProgressRequest.php:23`, `GetChildResultRequest.php:23` (hai dòng cuối biến mất ở Task 4).

- [ ] **Step 9: Chạy test task + đặc tả.** `... php artisan test tests/Feature/Support/ActiveRoleOutsideHttpTest.php tests/Feature/Authorization tests/Feature/ResultProgress tests/Feature/SubmissionGrading tests/Feature/Grading tests/Feature/Result`. Expected: tất cả pass.

- [ ] **Step 10: Full suite (nền).** Expected: 663 + 7 = **670 passed**, 0 failed.

- [ ] **Step 11: Pint + commit.**

```bash
git add app/Support/DenialMessage.php app/Modules bootstrap/app.php tests/Feature/Support/ActiveRoleOutsideHttpTest.php
git commit -m "refactor: read the active role through ActiveRole in policies and queries

Policies and scoped queries no longer inject Request or read the session, so they
work from jobs and console and cannot disagree on which role a multi-role user is
acting as. Shared 'Request failed.' message moves to DenialMessage.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

**Tiêu chí xong:** 670 test xanh; Step 8 sạch. **Điểm dừng: Opus review** (thứ tự early-return trong Policy, không còn đường đọc vai trò thứ hai, Query và Policy cùng nguồn).

---

### Task 4: Dời kiểm tra phụ huynh-học sinh và "Teacher dạy học sinh này" vào `ClassScope` — **cần Opus review**

**Files:**
- Modify: `app/Support/ClassScope.php` (thêm 2 method; không sửa method có sẵn)
- Modify: `app/Modules/ResultProgress/Policies/StudentPolicy.php`
- Modify: `app/Modules/ResultProgress/Http/Requests/GetChildResultRequest.php`, `GetChildProgressRequest.php`
- Test: `tests/Feature/Support/ClassScopeTest.php` (thêm method mới, không sửa method cũ)

**Interfaces:**
- Consumes: `DenialMessage::NOT_FOUND`, `StudentPolicy::__construct(ActiveRole)` (Task 3).
- Produces:
  - `ClassScope::teacherCoversStudent(User $user, Student $student): bool`
  - `ClassScope::parentCoversStudent(User $user, string $studentId): bool`
  - `StudentPolicy::viewAsParent(User $user, string $studentId): Response`, gọi bằng `Gate::inspect('viewAsParent', [Student::class, $studentId])`.

- [ ] **Step 1: Viết test ClassScope** (thêm vào `ClassScopeTest`, dùng factory như các test có sẵn trong file):
  - `test_teacher_covers_a_student_in_a_class_they_teach` → `assertTrue(ClassScope::teacherCoversStudent($user, $student))`.
  - `test_teacher_covers_a_student_actively_enrolled_in_a_course_they_own` → `assertTrue`.
  - `test_teacher_does_not_cover_a_student_whose_course_enrollment_ended` (`status 'ended'`, `valid_to` hôm qua) → `assertFalse`.
  - `test_teacher_does_not_cover_a_student_of_another_class` → `assertFalse`.
  - `test_user_without_teacher_profile_covers_no_student` → `assertFalse`.
  - `test_parent_covers_an_actively_linked_student` → `assertTrue(ClassScope::parentCoversStudent($user, $student->id))`.
  - `test_parent_does_not_cover_a_student_with_an_ended_link` → `assertFalse`.
  - `test_user_without_parent_profile_covers_no_student` → `assertFalse`.

- [ ] **Step 2: Chạy để thấy đỏ.** `--filter=ClassScopeTest`. Expected: 8 test mới FAIL `Call to undefined method App\Support\ClassScope::teacherCoversStudent()` / `parentCoversStudent()`; test cũ vẫn pass.

- [ ] **Step 3: Thêm 2 method vào `ClassScope`** (đặt ngay sau `limitStudentAssessmentsToTeacher`; thêm `use App\Models\ParentStudentRelationship;` và `use App\Models\Student;`). Thân hàm chép **nguyên văn** điều kiện cũ (D8):

```php
/**
 * Whether a Teacher covers this Student: they actively teach one of the student's active classes, or
 * own a course the student is actively enrolled in. False without a Teacher profile. Admin exemption is the caller's.
 */
public static function teacherCoversStudent(User $user, Student $student): bool
{
    $teacher = $user->teacherProfile;
    if (! $teacher) {
        return false;
    }

    return $teacher->activeClassIds()->intersect($student->activeClassIds())->isNotEmpty()
        || Course::where('owner_teacher_id', $teacher->id)
            ->whereHas('enrollments', fn ($q) => $q->where('student_id', $student->id)->where('status', 'active'))
            ->exists();
}

/**
 * Whether the Parent profile of $user has an active link to the student. False without a Parent profile.
 */
public static function parentCoversStudent(User $user, string $studentId): bool
{
    $parentId = $user->parentProfile?->id;
    if (! $parentId) {
        return false;
    }

    return ParentStudentRelationship::where('parent_id', $parentId)
        ->where('student_id', $studentId)
        ->where('status', 'active')
        ->exists();
}
```

- [ ] **Step 4: Chạy test ClassScope.** Expected: toàn bộ `ClassScopeTest` pass.

- [ ] **Step 5: Sửa `StudentPolicy`.**
  - `viewProgress`: sau nhánh admin, `return ClassScope::teacherCoversStudent($user, $student) ? Response::allow() : Response::denyAsNotFound(DenialMessage::NOT_FOUND);` (bỏ kiểm tra `$teacher` riêng — `teacherCoversStudent` trả `false` khi không có hồ sơ, kết quả vẫn là 404 như cũ). Xóa `use App\Models\Course;`, thêm `use App\Support\ClassScope;`.
  - Thêm `viewAsParent(User $user, string $studentId): Response`: không có `$user->parentProfile` → `Response::deny('Tài khoản không có hồ sơ Parent.')`; `ClassScope::parentCoversStudent($user, $studentId)` → `Response::allow()`; ngược lại `Response::denyAsNotFound(DenialMessage::NOT_FOUND)`. Không đọc `ActiveRole` (route đã chặn bằng `role:parent`; hành vi cũ không phụ thuộc vai trò).
  - Cập nhật docblock class: Policy trả lời cả "Teacher/Admin xem tiến độ" và "Parent xem kết quả/tiến độ của con".

- [ ] **Step 6: Sửa 2 FormRequest.** `GetChildResultRequest`/`GetChildProgressRequest`: `authorize(): Response { return Gate::inspect('viewAsParent', [Student::class, $this->route('studentId')]); }`. Xóa `use App\Models\ParentStudentRelationship;`, thêm `use App\Models\Student;` và `use Illuminate\Support\Facades\Gate;`. Kiểu trả về đổi từ `Response|bool` thành `Response`.

- [ ] **Step 7: Chạy test đặc tả + Result.** `... php artisan test tests/Feature/ResultProgress tests/Feature/Authorization tests/Feature/Result tests/Feature/Support`. Expected: tất cả pass (đặc biệt `ChildAccessAuthorizationTest`, `StudentProgressAuthorizationTest`, `GetChildResultAndProgressTest`).

- [ ] **Step 8: Full suite (nền).** Expected: 670 + 8 = **678 passed**, 0 failed.

- [ ] **Step 9: Pint + commit.**

```bash
git add app/Support/ClassScope.php app/Modules/ResultProgress tests/Feature/Support/ClassScopeTest.php
git commit -m "refactor: move parent link and teacher-student checks into ClassScope

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

**Tiêu chí xong:** 678 test xanh; `grep -rn "ParentStudentRelationship\|Course::" app/Modules/ResultProgress/Http app/Modules/ResultProgress/Policies` không có kết quả; `grep -rnF "'Request failed.'" app` chỉ còn `app/Support/DenialMessage.php` và `StudentAssessmentResultNotFound.php:16`. **Điểm dừng: Opus review** (nguyên văn điều kiện, 403 vs 404 của phụ huynh, cách gọi Gate theo class).

---

### Task 5: Rule kiến trúc R6/R7

**Files:**
- Modify: `tests/Architecture/ArchitectureTest.php`

**Interfaces:**
- Consumes: trạng thái code sau Task 3-4 (module sạch; `ClassScope` chỉ còn 3 dòng của `authorizeTeacherWrite`).
- Produces: rule R6, R7; hằng `KNOWN_SUPPORT_VIOLATIONS`.

- [ ] **Step 1: Viết test sanity cho scanner** (theo kiểu `test_scanner_ignores_comments_and_strings_and_catches_real_code`):

```php
public function test_scanner_catches_request_and_session_reads(): void
{
    $dirty = "<?php\nuse Illuminate\\Http\\Request;\nfunction f(Request \$r) { return \$r->session()->get('k'); }\nsession('k');\nrequest();\n";
    $this->assertSame([2, 3, 5], array_column(self::scanSource($dirty, 'x.php', ['R6' => self::requestPatterns()]), 'line'));
    $this->assertSame([3, 4], array_column(self::scanSource($dirty, 'x.php', ['R7' => self::sessionPatterns()]), 'line'));

    $clean = "<?php\n// \$request->session()\nfunction g(FormRequest \$r, ListSubmissionsRequest \$s) { return 'session(x) request()'; }\n";
    $this->assertSame([], self::scanSource($clean, 'x.php', ['R6' => self::requestPatterns(), 'R7' => self::sessionPatterns()]));
}
```

- [ ] **Step 2: Chạy để thấy đỏ.** `--filter=ArchitectureTest`. Expected: FAIL `Call to undefined method ...requestPatterns()`.

- [ ] **Step 3: Thêm pattern và quét module.**

```php
/** R6: authorization/scope code never touches the HTTP request (ARCH-21). */
private static function requestPatterns(): array
{
    return [
        'HTTP Request import' => '/Illuminate\\\\Http\\\\Request\b/',
        'Request type hint' => '/(?<![\w\\\\])Request\s+\$/',
        'request() helper' => '/(?<![\w>:$])request\s*\(/',
    ];
}

/** R7: only LoadActiveRole (app/Http) reads the session; everything else uses ActiveRole (ARCH-21). */
private static function sessionPatterns(): array
{
    return [
        '->session()' => '/->session\s*\(/',
        'session() helper' => '/(?<![\w>:$])session\s*\(/',
        'Session facade' => '/\bSession::/',
    ];
}
```

  Trong `scanModule()`, thêm trước vòng `scanForeignWrites`:

```php
foreach (array_merge(self::phpFiles($dir.'/Policies'), self::phpFiles($dir.'/Queries')) as $file) {
    $out = array_merge($out, self::scanFile($file, $module, ['R6' => self::requestPatterns()]));
}
foreach (self::phpFiles($dir) as $file) {
    $out = array_merge($out, self::scanFile($file, $module, ['R7' => self::sessionPatterns()]));
}
```

- [ ] **Step 4: Thêm quét `app/Support` với ngoại lệ khớp nguyên dòng (D10).**
  - `relative()` đổi thành `str_replace([dirname(__DIR__, 2).'/app/Modules/', dirname(__DIR__, 2).'/app/'], '', $path)` (file module giữ đường dẫn cũ; file Support thành `Support/ClassScope.php`).
  - Hằng mới:

```php
/**
 * Exact source lines in app/Support allowed to break R6/R7 until their owner task removes them.
 * Matched on file + rule + the whole trimmed line, so any other Request/session use still fails, and
 * test_known_support_violations_are_still_violations fails once a line is gone (remove the entry then).
 *
 * @var list<array{0: string, 1: string, 2: string, 3: string}> [file, rule, source line, owner task]
 */
private const KNOWN_SUPPORT_VIOLATIONS = [
    ['Support/ClassScope.php', 'R6', 'use Illuminate\Http\Request;', 'authorizeTeacherWrite -> Policy when Attendance/Lesson is refactored'],
    ['Support/ClassScope.php', 'R6', 'public static function authorizeTeacherWrite(Request $request, string $classId): void', 'authorizeTeacherWrite -> Policy when Attendance/Lesson is refactored'],
    ['Support/ClassScope.php', 'R7', "\$activeRole = \$request->session()->get('active_role_code');", 'authorizeTeacherWrite -> Policy when Attendance/Lesson is refactored'],
];
```

  - Method mới: `scanSupport(): array` — R6 trên `app/Support/ClassScope.php`, R7 trên mọi file `phpFiles(app/Support)`; `isKnownSupport(array $v): bool` — đúng khi có entry cùng `file`, cùng `rule` và `str_ends_with($v['fragment'], ': '.$line)`.
  - Test mới: `test_support_follows_role_source_rules()` (lọc `isKnownSupport`, `assertSame([], ...)` với thông điệp `'Architecture violations in app/Support'`) và `test_known_support_violations_are_still_violations()` (mỗi entry phải còn khớp ít nhất một vi phạm; nếu không, liệt kê `"{$file} [{$rule}] '{$line}' no longer violates ({$owner}); remove it from KNOWN_SUPPORT_VIOLATIONS"`).
  - Cập nhật docblock đầu class: thêm dòng R6, R7 (như D11) và câu "app/Support is scanned for R6 (ClassScope) and R7 (every file), with exact-line exceptions in KNOWN_SUPPORT_VIOLATIONS".

- [ ] **Step 5: Chạy.** `--filter=ArchitectureTest`. Expected: tất cả pass. Có vi phạm ngoài 3 dòng đã liệt kê: DỪNG.

- [ ] **Step 6: Chứng minh rule bắt được vi phạm thật.** Tạm thêm `use Illuminate\Http\Request;` vào `app/Modules/SubmissionGrading/Policies/AttemptPolicy.php` và `$x = $this->session();` vào thân `ListSubmissionsRequest::rules()` → chạy `--filter=ArchitectureTest` → Expected: FAIL nêu `[R6] HTTP Request import` và `[R7] ->session()`. Hoàn tác bằng `git checkout -- app/Modules/SubmissionGrading/Policies/AttemptPolicy.php app/Modules/SubmissionGrading/Http/Requests/ListSubmissionsRequest.php`; chạy lại → pass.

- [ ] **Step 7: Full suite (nền).** Expected: 678 + 3 = **681 passed**, 0 failed.

- [ ] **Step 8: Pint + commit.**

```bash
git add tests/Architecture/ArchitectureTest.php
git commit -m "test: forbid Request and session reads in policies, queries and support

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

**Tiêu chí xong:** 681 test xanh; Step 6 đã chứng minh đỏ rồi xanh. **Điểm dừng review** (Sonnet đủ).

---

### Task 6: Kiểm tra cuối và cập nhật tài liệu

**Files:**
- Modify: `piclass-api/CLAUDE.md` (repo BE, branch `refactor/active-role`)
- Modify (repo docs, branch `docs/active-role`): `docs/architecture/backend-refactor-playbook.md`, `docs/architecture/open-decisions.md` (dòng `ARCH-21`), `docs/progress.md`

- [ ] **Step 1: Pint trên file đã đổi.** Trong `piclass-api` (Git Bash):

```bash
FILES=$(git diff --name-only origin/main -- '*.php' | tr '\n' ' ')
wsl.exe -d Ubuntu-22.04 -- bash -lc "docker exec piclass-local-api-1 vendor/bin/pint --test $FILES"
```

  Expected: PASS. Báo lỗi ở file trong danh sách: chạy lại không `--test` cho file đó, rồi commit `style: apply pint` (kèm dòng Co-Authored-By).

- [ ] **Step 2: Sửa `piclass-api/CLAUDE.md`** (3 chỗ, giữ nguyên phần khác):
  - Mục "Rule cốt lõi", câu "Rule có gắn `R1`-`R5` được `ArchitectureTest` kiểm tra tự động." → "`R1`-`R7`".
  - Mục "Phân quyền", bullet "Policy trả `Illuminate\Auth\Access\Response`...": thay `Response::denyAsNotFound('<message>')` bằng `Response::denyAsNotFound(DenialMessage::NOT_FOUND)` và câu trong ngoặc thành "(không lộ sự tồn tại; message dùng chung `App\Support\DenialMessage::NOT_FOUND`, phải bằng message 404 của handler — `ARCH-20`)".
  - Bullet "**Vai trò đang hoạt động**..." thay bằng:

    > - **Vai trò đang hoạt động** (`active_role_code`, lưu trong session — `ARCH-11`) chỉ đọc qua `App\Support\ActiveRole` (`ARCH-21`, R6/R7): Policy và Query inject `ActiveRole` qua constructor, gọi `isAdmin()`/`code()`. Middleware `LoadActiveRole` (nhóm `api`) nạp giá trị từ session mỗi request; job/console inject `ActiveRole` vào `handle()` và `set('<role>')` trước khi gọi Policy/Query (chưa set = không có vai trò, mọi kiểm tra admin trả false). `ClassScope` không tự đọc vai trò: nhận vai trò qua tham số hoặc theo tên hàm (`teacherCovers*`, `parentCoversStudent`). Policy, Query, `ClassScope`, FormRequest, Controller không đọc session/`Request` để lấy vai trò.

  Commit ở repo BE: `docs: document ActiveRole and DenialMessage in CLAUDE.md` (kèm dòng Co-Authored-By).

- [ ] **Step 3: Full suite lần cuối (nền).** Expected: **681 passed**, 0 failed. Ghi con số thực tế để báo.

- [ ] **Step 4: Tạo branch docs** theo mục Git (Global Constraints).

- [ ] **Step 5: Sửa `backend-refactor-playbook.md`, bảng "Nợ đã biết"** (xóa dòng, không gạch — lịch sử ở git):
  - Xóa 4 dòng: "Policy inject `Request`...", "Kiểm tra liên kết phụ huynh-học sinh inline...", "Điều kiện "Teacher có dạy học sinh này"...", "Hằng `'Request failed.'` lặp...".
  - Dòng "`ClassScope` chứa việc ngoài phạm vi dữ liệu..." giữ, thêm vào cột "Sửa khi": "3 dòng của `authorizeTeacherWrite` đang là ngoại lệ trong `KNOWN_SUPPORT_VIOLATIONS` của `ArchitectureTest`; xóa entry khi dời hàm".
  - Thêm dòng mới: `Controller cũ đọc active_role_code thẳng từ session` | `app/Http/Controllers/Api/**`, `EnsureRole`, `MeController` (`ContextController` là nơi ghi, giữ) | `Khi refactor module tương ứng: dùng ActiveRole (R7 tự canh khi module vào MODULES)`.
  - Mục "Giữ nguyên hành vi: các bẫy đã gặp", bullet "`denyAsNotFound` luôn truyền message cũ..." → "**`denyAsNotFound` luôn truyền `DenialMessage::NOT_FOUND`**, nếu không client nhận "Not Found". Test vẫn ghim chuỗi literal `'Request failed.'`."

- [ ] **Step 6: Sửa `open-decisions.md` dòng `ARCH-21`:** đổi tiền tố trạng thái `` `ĐÃ CHỐT hướng (2026-10-20), chưa triển khai` `` thành `` `ĐÃ CHỐT, ĐÃ TRIỂN KHAI (2026-10-20, branch refactor/active-role)` ``; nối vào cuối ô nội dung: "**Triển khai:** `ActiveRole` (`scoped`, `set/code/isAdmin`, chưa set = không có vai trò) + middleware `LoadActiveRole` trong nhóm `api` (luôn ghi đè); Policy/Query inject `ActiveRole`; `ClassScope` nhận vai trò qua tham số, thêm `teacherCoversStudent`/`parentCoversStudent`; Parent xem con qua `StudentPolicy::viewAsParent`; hằng `DenialMessage::NOT_FOUND`; rule kiến trúc R6 (không `Request` trong Policy/Query/`ClassScope`) và R7 (không đọc session trong module đã refactor và `app/Support`). Ngoại lệ tạm: `ClassScope::authorizeTeacherWrite` (giữ đến khi refactor Attendance/Lesson). Plan: `archive/superpowers/plans/2026-10-20-be-active-role.md`."

- [ ] **Step 7: Sửa `progress.md`**, mục "Refactor kiến trúc BE — trạng thái làm việc hiện tại": đổi câu "Việc kế tiếp trước module mới: task triển khai `Support/ActiveRole` (`ARCH-21`)." thành "Task 0 `Support/ActiveRole` (`ARCH-21`) đã xong trên branch local `refactor/active-role` (BE) + `docs/active-role` (docs), chưa push; <số> test pass. Việc kế tiếp: Task 0b (Enum cho `SubmissionGrading`/`ResultProgress`)." (thay `<số>` bằng con số ở Step 3); cập nhật ngày ở tiêu đề mục thành 2026-10-20.

- [ ] **Step 8: Commit docs.**

```bash
git add docs/architecture/backend-refactor-playbook.md docs/architecture/open-decisions.md docs/progress.md
git commit -m "docs: mark ARCH-21 implemented and clear ActiveRole refactor debt

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
```

- [ ] **Step 9: Báo cáo cho user và DỪNG.** Nội dung: số test cuối, danh sách commit ở cả hai repo (`git log --oneline origin/main..HEAD` mỗi repo), mọi điểm đã DỪNG/hỏi trong lúc làm, xác nhận chưa push. Nhắc rằng repo docs có commit chưa push (theo CLAUDE.md gốc). Không push/PR/merge.

**Tiêu chí xong:** 681 test xanh; `pint --test` sạch trên file đã đổi; 4 tài liệu cập nhật; hai branch có commit local, chưa push.

---

## Self-review (người viết plan đã chạy)

- **Phạm vi:** mục 1 (ActiveRole) → Task 2; mục 2 (chuyển chỗ đọc `active_role_code`, quyết định `authorizeTeacherWrite`) → Task 3 + D10; mục 3 (ClassScope, hằng message) → Task 3 (`DenialMessage`) + Task 4; mục 4 (rule kiến trúc) → Task 5; mục 5 (tài liệu) → Task 6. Không có Enum, đổi tên Query, chuyển DTO.
- **Nhiều vai trò + không session:** `ActiveRoleMultiRoleTest` (HTTP, gồm đổi vai trò qua `/auth/context`), `ActiveRoleOutsideHttpTest` (không HTTP), `LoadActiveRoleMiddlewareTest` (không có session).
- **Kiểu nhất quán:** `ActiveRole::set/code/isAdmin`, `SESSION_KEY`; `ListSubmissions::handle(Assessment, User, ?string)`; `PublishableResultsQuery::handle(Assessment, User)`; `BulkPublishResults::handle(Assessment, User)`; `ClassScope::teacherCoversStudent(User, Student)`, `parentCoversStudent(User, string)`; `StudentPolicy::viewAsParent(User, string)` — dùng giống nhau ở mọi task.
- **Đếm test:** 625 → +28 (Task 1) → +10 (Task 2) → +7 (Task 3) → +8 (Task 4) → +3 (Task 5) = 681. Nếu con số thực tế khác do cách PHPUnit đếm DataProvider, ghi lại con số thực tế và lý do, không coi là lỗi miễn 0 failed và không test cũ nào mất.
