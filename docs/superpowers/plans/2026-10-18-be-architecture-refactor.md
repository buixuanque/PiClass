# Refactor kiến trúc Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Đưa `piclass-api` từ "logic trong controller, `new Service()`, abort rải rác" sang cấu trúc `app/Modules/<Module>/` với FormRequest → Controller → Action → Eloquent đúng `piclass-api/CLAUDE.md`, không đổi hành vi nghiệp vụ.

**Architecture:** Làm mẫu một module nhỏ trọn vẹn (SubmissionGrading) để kiểm chứng quy ước, dừng để duyệt, rồi nhân rộng từng module một PR. Model ở lại `App\Models` vĩnh viễn (quyết định 2026-10-19, xem `ARCH-17`) — không có bước đổi namespace Model sau này. Test kiến trúc mở rộng dần theo danh sách module đã refactor.

**Tech Stack:** Laravel 11, PHP 8.2, PostgreSQL, PHPUnit, Pint, Docker (`piclass-infra/local`).

**Spec:** `piclass-api/CLAUDE.md` (quy ước, nằm trên branch `docs/backend-architecture-conventions`), `docs/research/2026-10-18-laravel-backend-architecture-patterns.md`, `DEC-AUD-005`, `ARCH-17`. Business rule/phân quyền ở `docs/requirements|access-control|database` ưu tiên cao hơn.

## Trạng thái thực hiện (cập nhật 2026-10-21)

Các checkbox bên dưới là bản gốc của plan và **cố ý không được tick**, vì nhiều task đã chạy lệch plan ở một số bước (xem `2026-10-18-be-architecture-refactor-rulings.md`). Trạng thái thực tế nằm ở bảng này. Quy trình hiện hành cho mỗi module là mục "Phân vai model" trong `docs/architecture/backend-refactor-playbook.md` (Sonnet lên plan riêng và thực thi, Opus review một lần ở cuối module). Khung P1–P7 bên dưới vẫn dùng, bổ sung bắt buộc thử lỗi cố ý (đột biến) cho test phân quyền.

| Task | Trạng thái | PR (docs / BE) | Plan / ghi chú |
|---|---|---|---|
| 1–6 (Phase A) | Xong, đã merge | docs #14 / BE #15 | Bảng ownership, nền tảng lỗi nghiệp vụ, module mẫu `SubmissionGrading`, test kiến trúc |
| 7 (điểm dừng) | Đã qua, không còn việc chờ user | docs #15 / BE #16 | `ARCH-18/19/20` đã chốt, mã lỗi đã dọn; 6 dòng `[CẦN XÁC NHẬN]` trong `model-ownership.md` chốt lúc refactor module liên quan |
| 8 `ResultProgress` | Xong, đã merge | docs #15 / BE #16 | |
| 0 `ActiveRole` (thêm ngoài plan gốc) | Xong, đã merge | docs #17 / BE #18 | `2026-10-20-be-active-role.md` (`ARCH-21`) |
| 0b Enum (thêm ngoài plan gốc) | Xong, đã merge | docs #18 / BE #19 | `2026-10-20-be-enums-submission-result.md` |
| 9a `Attendance` | Xong, đã merge | docs #22 / BE #20 | `2026-10-21-be-attendance.md`; lệch với requirements ghi ở `ARCH-22` (issue #19, #20) |
| 9b `UserManagement` PR 1 (User/Role/Profile) | Xong, đã merge (Opus đã review) | docs `be-user-management-plan` / BE #21 | `2026-10-21-be-user-management.md` |
| 9b `UserManagement` PR 2 (Class/Parent) | Xong, đã merge (Opus đã review) | docs #27 / BE #23 | `2026-10-21-be-user-management.md` |
| 9c `Assessment` | Xong, đã merge (Opus đã review) | docs #28 / BE #24 | `2026-10-21-be-assessment.md` |
| 9d `QuestionBank` | Xong, đã merge (Opus đã review) | docs #29 / BE #25 | `2026-10-22-be-question-bank.md` |
| 9e `Lesson` | Xong, đã merge (Opus đã review) | docs #31 / BE #26 | `2026-10-21-be-lesson.md`; kèm giờ Việt Nam toàn hệ thống (`DEC-SYS-002`) |
| 9f `Academic` | Xong, đã merge (Opus đã review) | docs #38 / BE #27 | `2026-10-21-be-academic.md`; quyết định `ARCH-28` (issue #34–#37) |
| 9g–9i | **Chưa làm** | | Việc kế tiếp: 9g `Identity`/`Auth` |
| 10 (Phase C) | Đã hủy | | `ARCH-17` sửa đổi 2026-10-19 |
| 11 (Phase D) | Chưa làm | | Chỉ làm sau khi xong 9b–9i |

## Global Constraints

- Baseline: **402 test pass** (chạy `docker compose exec -T api php artisan test` trong `piclass-infra/local`, khoảng 12 giây). Mọi task kết thúc với số test ≥ baseline và không test cũ nào bị sửa hành vi; chỉ sửa test khi đổi namespace/đường dẫn.
- HTTP status và `message` của mọi endpoint giữ nguyên. `error.code` được phép đổi từ mã chung (`CONFLICT`, `FORBIDDEN`...) sang mã riêng (`ATTEMPT_NOT_OPEN`...) vì FE chưa bắt đầu và test hiện không ghim `CONFLICT`; mỗi mã mới phải ghi vào `docs/architecture/error-codes.md` cùng task.
- **Ngoại lệ đã chấp nhận (Ruling 11, 2026-10-18):** validate input bằng FormRequest chạy trước kiểm tra trạng thái nghiệp vụ, nên request vừa sai input vừa sai trạng thái trả 422 (`VALIDATION_FAILED`) thay vì 409 như trước; mỗi endpoint bị ảnh hưởng có test ghim thứ tự mới.
- Cấu trúc JSON response giữ nguyên key (kể cả key ngoài envelope như `active_class_ids` ở `UpdateUserStatusController`).
- Chạy lệnh trong container: tiền tố `docker compose exec -T api` tại `piclass-infra/local`. Format: `vendor/bin/pint` (chạy `--test` để kiểm).
- Audit bắt buộc (`DEC-AUD-001`) nằm trong cùng transaction; audit lỗi thì hủy thay đổi (`DEC-AUD-005`). Không tự thêm audit cho event chưa có trong spec Module 5 §4.
- Model giữ `App\Models` vĩnh viễn (không có Phase C đổi namespace — hủy theo quyết định 2026-10-19, xem `ARCH-17`). Routes giữ ở `routes/api.php`.
- Git: mỗi task một branch từ `origin/main` đã `git fetch`; commit local; chỉ push/PR/merge khi user yêu cầu; không xóa branch.
- Tài liệu tiếng Việt; code/commit tiếng Anh. Cập nhật `docs/progress.md` và `error-codes.md` (repo docs) khi xong mỗi module.

## Review Focus

1. **Thứ tự 422 vs 403.** `FormRequest::authorize()` chạy trước validate; hành vi cũ có thể validate trước rồi mới 403 (hoặc ngược lại). Với đầu vào vừa sai vừa bị cấm, status phải đúng như hiện tại: mỗi endpoint đã chuyển có một test cho case này.
2. **404 vs 403 khi truy cập đối tượng người khác bằng ID.** Hiện nhiều endpoint trả 404 (student xem attempt của bạn). Policy không được đổi thành 403 làm lộ sự tồn tại của đối tượng.
3. **Event bắn trong transaction bị rollback.** Listener `ShouldQueueAfterCommit` không được chạy khi Action ném lỗi sau khi dispatch; test với `Event::fake()` + audit giả ném lỗi.
4. **Nộp bài đồng thời / nộp bài qua GET.** `GetAttemptController` hiện gọi `SubmitAttemptController::submit()` để tự nộp khi quá hạn (một GET có tác dụng ghi, controller gọi controller). Hai request cùng lúc (GET + POST submit) chỉ được tạo đúng một Submission.
5. **N+1 sau khi chuyển sang Resource.** Resource không tự query; mỗi list endpoint đã chuyển có test đếm query (hoặc eager load tường minh) vì Resource lazy-load là lỗi hiệu năng im lặng.

## Quy trình chuẩn cho một module (P)

Mọi task "refactor module X" thực hiện đúng các bước sau, kèm bảng riêng của module ở Task 9.

- [ ] **P1 Baseline.** `git fetch origin && git checkout -b refactor/<module> origin/main`. Chạy test module (`php artisan test tests/Feature/<Dir>`), ghi số test. Liệt kê controller + route của module.
- [ ] **P2 Đặc tả hành vi hiện tại bằng test (viết và chạy trên code CŨ, trước khi chuyển code).** Tạo `tests/Feature/<Dir>/AuthorizationMatrixTest.php`: với từng endpoint nhạy cảm, các vai trò (admin, teacher đúng lớp, teacher lớp khác, student chủ, student khác, parent có liên kết, parent không liên kết) × truy cập bằng ID của đối tượng thuộc phạm vi khác; khẳng định status và `error.code` đúng. Trước khi viết, đọc test 403/404 sẵn có của module và chỉ thêm phần chưa được phủ. Với mỗi endpoint có audit bắt buộc, thêm test rollback (bind `AuditLogger` giả ném lỗi → không có thay đổi nghiệp vụ). Chạy trên code cũ: test **pass** thì đó là đặc tả hành vi cần giữ; test **fail** nghĩa là code cũ có lỗ hổng/lỗi thật — dừng, báo user (không tự sửa im lặng, không tự đổi kỳ vọng cho khớp code); test rollback audit được phép fail trên code cũ vì `DEC-AUD-005` là quyết định mới, đánh dấu `markTestSkipped` kèm lý do đến P4.
- [ ] **P3 Chuyển từng use case.** Tạo trong `app/Modules/<Module>/`: `Http/Requests/*Request` (rule chuyển từ validate inline; `authorize()` gọi Policy), `Http/Resources/*Resource` (cùng key JSON hiện tại), `Actions/<Verb><Noun>` (một `handle()`, chỉ cho use case ghi theo rule 2), `Queries/` cho đọc có phạm vi `ClassScope` (rule 10), `Policies/` cho quyền theo đối tượng, `Exceptions/` thay mọi `abort*()`/`response()->json(['error'...])` bằng `DomainException` có `errorCode`+`status`. Controller còn khoảng 10 dòng. Mỗi use case: chạy test module sau khi chuyển, phải xanh (kể cả ma trận ở P2).
- [ ] **P4 Transaction và audit.** Action ghi ≥2 bảng hoặc ghi + audit bắt buộc bọc `DB::transaction`; `AuditLogger::record` gọi bên trong. Bỏ `markTestSkipped` ở các test rollback của P2; chúng phải pass.
- [ ] **P5 DI.** Thay mọi `new X()` trong module bằng constructor injection; không `app()`/facade lấy dependency trong thân method.
- [ ] **P6 Khóa lại.** Thêm tên module vào danh sách trong `tests/Architecture/ArchitectureTest.php` (từ Task 6). Chạy full suite + `pint --test`; số test ≥ baseline.
- [ ] **P7 Ghi nhận.** Cập nhật `docs/architecture/error-codes.md` và dòng module trong `docs/progress.md` (repo docs). Commit local ở cả hai repo, báo user có commit chưa push.

---

## Phase A — Nền tảng và mẫu

### Task 1: Bảng ownership Model

**Files:**
- Create: `docs/architecture/model-ownership.md` (repo docs)

**Interfaces:**
- Produces: bảng "Model → module sở hữu" dùng cho Task 5 (đặt Policy/Query) và cho `ArchitectureTest` rule R4 (enforce "ai được ghi"; không còn dùng để đổi namespace — xem Phase C đã hủy); danh sách giá trị `entity_type` thực tế mà các nơi gọi `AuditLogger` truyền vào.

- [ ] **Step 1:** Liệt kê 48 file `piclass-api/app/Models`, ghép với `docs/database/*` và `docs/architecture/module-boundaries.md` thành bảng `Model | Module sở hữu | Ghi chú` (model dùng chung như `User`, `Student`, `ClassMember` ghi rõ module chủ và module chỉ đọc).
- [ ] **Step 2:** Quét 25 nơi gọi `AuditLogger::record` (`grep -rn "entityType:" piclass-api/app`) và ghi vào cuối file danh sách `entity_type` đang dùng; đánh dấu giá trị nào là tên class đầy đủ (nếu có) vì đổi namespace Model sẽ phá dữ liệu cũ.
- [ ] **Step 3:** Verify đủ 48 model, mỗi model đúng một dòng: `for m in $(ls piclass-api/app/Models | sed 's/.php//'); do grep -c "\`$m\`" docs/architecture/model-ownership.md; done | sort | uniq -c` → chỉ có giá trị `1`.
- [ ] **Step 4:** Báo user duyệt bảng (đây là quyết định ranh giới, dùng để enforce quyền ghi qua `ArchitectureTest`). Commit.

### Task 2: Nền tảng lỗi nghiệp vụ

**Files:**
- Create: `piclass-api/app/Support/Exceptions/DomainException.php`
- Modify: `piclass-api/bootstrap/app.php`
- Create: `piclass-api/tests/Feature/DomainExceptionTest.php`
- Create: `docs/architecture/error-codes.md` (repo docs)

**Interfaces:**
- Produces: `abstract class DomainException extends \RuntimeException { public string $errorCode; public int $status; public function extra(): array }` — `extra()` mặc định `[]`, được gộp vào JSON ở cấp trên cùng cạnh `error` (giữ shape `active_class_ids`). Handler render theo envelope hiện có `{ "error": { "code", "message", "details"? } }`, chỉ cho request `api/*`.

- [ ] **Step 1: Write the failing test** `DomainExceptionTest`: đăng ký route thử trong test ném một subclass ẩn danh có `errorCode='SAMPLE_CODE'`, `status=409`, `extra()=['k'=>1]`; assert JSON `error.code=SAMPLE_CODE`, `error.message` đúng, status 409, top-level `k=1`.
- [ ] **Step 2:** Chạy `php artisan test tests/Feature/DomainExceptionTest.php`; Expected: FAIL (class chưa tồn tại).
- [ ] **Step 3:** Tạo `DomainException` và thêm một `$exceptions->render(...)` trong `bootstrap/app.php`, đặt **trước** handler `HttpExceptionInterface` hiện có.
- [ ] **Step 4:** Chạy test mới + `ErrorEnvelopeTest`; Expected: PASS.
- [ ] **Step 5:** Tạo `error-codes.md` với bảng `Code | HTTP | Module | Khi nào` (trống, kèm hướng dẫn thêm dòng). Commit cả hai repo.

### Task 3: Đưa AuditLogger và NotificationDispatcher về module (cơ học)

**Files:**
- Move: `app/Services/Audit/AuditLogger.php` → `app/Modules/Audit/AuditLogger.php`
- Move: `app/Services/Notification/NotificationDispatcher.php` → `app/Modules/Notification/NotificationDispatcher.php`
- Modify: mọi file đang `use`/gọi hai class này (`grep -rln "Services\\\\Audit\|Services\\\\Notification" app tests`)

**Interfaces:**
- Consumes: không đổi chữ ký; `AuditLogger::record(?User $actor, string $action, string $entityType, string $entityId, ?array $before, ?array $after, ?string $reason, ?string $correlationKey): AuditLog`.
- Produces: namespace `App\Modules\Audit\AuditLogger`, `App\Modules\Notification\NotificationDispatcher` làm class công khai của module (rule 16).

- [ ] **Step 1:** `git mv` hai file, đổi `namespace`, thay toàn bộ tham chiếu cũ (kể cả dạng `new \App\Services\Audit\AuditLogger()` trong thân controller).
- [ ] **Step 2:** `composer dump-autoload`; chạy full suite; Expected: 402 pass.
- [ ] **Step 3:** `grep -rn "App\\\\Services\\\\\(Audit\|Notification\)" piclass-api` → không còn kết quả. Commit.

### Task 4: Mẫu — use case SubmitAttempt

**Files:**
- Create: `app/Modules/SubmissionGrading/Actions/SubmitAttempt.php`
- Create: `app/Modules/SubmissionGrading/Http/Controllers/SubmitAttemptController.php`, `Http/Requests/SubmitAttemptRequest.php`, `Http/Resources/SubmissionResultResource.php`, `Policies/AttemptPolicy.php`
- Create: `app/Modules/SubmissionGrading/Exceptions/AttemptNotOpen.php`, `AttemptDeadlinePassed.php`
- Modify: `app/Http/Controllers/Api/Submission/GetAttemptController.php` (dùng Action thay vì gọi controller), `routes/api.php:178`
- Delete: `app/Http/Controllers/Api/Submission/SubmitAttemptController.php`
- Test: `tests/Feature/SubmissionGrading/SubmitAttemptActionTest.php`, `AttemptAuthorizationMatrixTest.php`; `tests/Feature/Submission/SubmitAttemptTest.php` giữ nguyên

**Interfaces:**
- Produces: `SubmitAttempt::handle(Attempt $attempt, User $actor): Submission` (bọc `DB::transaction`, giữ `lockForUpdate`); `AttemptNotOpen` (409, `ATTEMPT_NOT_OPEN`), `AttemptDeadlinePassed` (409, `ATTEMPT_DEADLINE_PASSED`); `AttemptPolicy::submit(User $user, Attempt $attempt): bool` (student sở hữu attempt).
- Consumes: `AutoGrader`, `ResultCalculator` (vẫn nằm `App\Services\Grading`, inject qua constructor; chuyển ở Task 8).

- [ ] **Step 1: Write failing tests.** `SubmitAttemptActionTest`: (a) attempt đã nộp → `AttemptNotOpen`; (b) quá hạn không cho nộp trễ → `AttemptDeadlinePassed`; (c) bind `AuditLogger` giả ném lỗi *nếu* bước 2 xác định nộp bài thuộc audit bắt buộc → không có `Submission`, attempt vẫn `in_progress`; (d) gọi `handle` hai lần song song logic (mô phỏng attempt đã bị khóa) chỉ tạo một `Submission`. `AttemptAuthorizationMatrixTest`: student chủ → 200; student khác → 404; user không có hồ sơ Student → 403; teacher/admin → 403; chưa đăng nhập → 401; đầu vào sai + student khác → status giống hiện tại (Review Focus 1, 2).
- [ ] **Step 2:** Đối chiếu spec Module 5 §4 (`2026-10-15-notification-file-audit-api-design.md`): `submit` có nằm trong bảng audit không. Nếu có: thêm audit trong transaction ở Step 4 + test (c). Nếu không: bỏ test (c), ghi một dòng vào `open-decisions.md` hỏi user vì `DEC-AUD-001` liệt kê "Submission".
- [ ] **Step 3:** Chạy các test mới; Expected: FAIL.
- [ ] **Step 4:** Implement theo P3/P4: toàn bộ logic của `submit()` cũ chuyển vào Action (giữ thứ tự: khóa attempt → đánh dấu answers → tạo Submission → chấm → tính Result → auto-publish); kiểm tra deadline và trạng thái chuyển thành exception; `GetAttemptController` inject `SubmitAttempt`, hành vi tự nộp khi quá hạn giữ nguyên.
- [ ] **Step 5:** Ghi `ARCH-18` vào `docs/architecture/open-decisions.md`: "GET attempt tự động nộp khi quá hạn (GET có tác dụng ghi)" trạng thái `[CẦN QUYẾT ĐỊNH]`, không đổi hành vi ở task này.
- [ ] **Step 6:** Chạy `php artisan test`; Expected: ≥ 402 + test mới, tất cả pass; `pint --test` sạch. Commit.

### Task 5: Hoàn tất module SubmissionGrading

**Files:**
- Modify/Create/Move: toàn bộ controller `Api/Submission/*` (còn 4) và `Api/Grading/*` (7) vào `app/Modules/SubmissionGrading/` theo P2.
- Move: `app/Services/Grading/AutoGrader.php` → `app/Modules/SubmissionGrading/Domain/AutoGrader.php` (thuần, không DB); `AttemptQuestionBuilder` → `Actions/BuildAttemptQuestions` (có ghi DB).
- Test: `tests/Unit/SubmissionGrading/AutoGraderTest.php` (không DB), `tests/Feature/SubmissionGrading/AuthorizationMatrixTest.php`

**Interfaces:**
- Consumes: `SubmitAttempt`, `AttemptPolicy` (Task 4). Giữ `ResultCalculator` tại chỗ.

- [ ] **Step 1:** Thực hiện P1–P5 cho 11 controller còn lại. Các Action có audit bắt buộc (chấm, sửa điểm sau công bố, cấp lại attempt) gồm test rollback theo P2/P4; `reason` bắt buộc khi sửa điểm đã công bố được validate trước khi ghi (`DEC-AUD-004`).
- [ ] **Step 2:** `AutoGraderTest` chạy không đụng DB (kiểm bằng cách không dùng `RefreshDatabase`), phủ các `QuestionType` mà `AutoGrader` hiện xử lý.
- [ ] **Step 3:** `ClassScope::authorizeTeacherWrite(Request, classId)` đang nhận `Request` và `abort` — thay trong module này bằng Policy/Exception, giữ hàm cũ cho module chưa chuyển.
- [ ] **Step 4:** P6–P7 (chưa có test kiến trúc nên bỏ P6 phần danh sách). Full suite ≥ baseline. Commit.

### Task 6: Test kiến trúc trên module mẫu

**Files:**
- Create: `piclass-api/tests/Architecture/ArchitectureTest.php`

**Interfaces:**
- Produces: danh sách `private const MODULES = ['SubmissionGrading']`; mỗi module mới thêm vào ở P6.

- [ ] **Step 1: Write failing test** (cố ý thêm tạm một file vi phạm trong module mẫu để chứng minh test bắt được, rồi xóa): với mỗi module trong `MODULES`, quét `Http/Controllers/*.php` không chứa `DB::`, `abort(`, `->validate(`; `Actions/*.php` không chứa `Illuminate\Http\Request`, `abort(`, `auth()`, `response()`; `Domain/*.php` không chứa `Facades\`, `now(`, `config(`, `env(`, `DB::`, `::create(`; module không `::create(`/`->update(` model thuộc module khác (dựa `model-ownership.md`, đưa danh sách model→module vào hằng số của test).
- [ ] **Step 2:** Chạy; Expected: FAIL khi có file vi phạm tạm, PASS sau khi xóa.
- [ ] **Step 3:** Full suite pass; `pint --test`. Commit.

### Task 7: Điểm dừng — duyệt mẫu

- [ ] **Step 1:** Báo user: số file/dòng thay đổi, đoạn Action/Controller/Resource mẫu, danh sách mã lỗi mới, các điểm vướng trong Review Focus đã gặp, thời gian thực tế của Phase A.
- [ ] **Step 2:** Nhận phản hồi; sửa `piclass-api/CLAUDE.md` và plan này nếu quy ước cần chỉnh. **Không** bắt đầu Phase B khi chưa có xác nhận.

## Phase B — Nhân rộng từng module (mỗi task = quy trình P)

### Task 8: ResultProgress (liền sau mẫu vì SubmitAttempt đang gọi `ResultCalculator`)

- [ ] **Step 1:** P1–P7 cho 9 controller `Api/Result/*`.
- [ ] **Step 2:** Tách `ResultCalculator` (142 dòng, trộn tính toán và ghi DB): phần tính thuần → `Domain/ResultCalculator`; `recalculate/publish/withdraw` → Action `RecalculateResult`, `PublishResult`, `WithdrawResult` (audit + notification theo bảng spec Module 5). `ProgressCalculator` (truy vấn có phạm vi) → `Queries/StudentProgressQuery`.
- [ ] **Step 3:** Cập nhật `SubmitAttempt` gọi `RecalculateResult` thay `ResultCalculator`; test rollback publish: audit lỗi → Result không đổi `publication_status`. `BulkPublishResults` vẫn một audit + một notification cho mỗi Result.

### Task 9: Các module còn lại (thứ tự đề xuất)

| Thứ tự | Module (thư mục controller) | Số controller | Lưu ý riêng |
|---|---|---|---|
| 9a | Attendance (`Attendance`) | 6 | 2 controller ghi audit ngoài transaction; reason khi sửa; business history riêng (`DEC-AUD-003`) |
| 9b | UserManagement (`UserManagement`) | 30 | Lớn nhất; 10 controller ghi audit ngoài transaction (role/trạng thái User, membership); chia 2 PR (User/Role, Class/Parent) |
| 9c | Assessment (`Assessment`) | 21 | `AddAssessmentAudience` 106 dòng, `CreateAssessment`; 4 controller ghi audit ngoài transaction; ARCH-14 chưa chốt — không đổi hành vi |
| 9d | QuestionBank (`QuestionBank`) | 9 | `CreateQuestion`/`UpdateQuestion` rule `in:` loại câu hỏi → Enum `QuestionType` |
| 9e | Lesson (`Lesson`) | 10 | `LessonSessionGenerator` → Action; `CreateSchedule` 111 dòng; 4 controller ghi audit ngoài transaction |
| 9f | Academic (`Academic`) | 14 | CRUD nhiều; Action chỉ khi có nghiệp vụ/audit (rule 2) |
| 9g | Identity (`Auth`) | 7 | Không đổi hành vi session/Sanctum; test `EnsureRoleMiddlewareTest`, `InactiveUserMidSessionTest` giữ nguyên |
| 9h | File (`File`) | 4 | Tích hợp storage: interface + fake cho test (rule 6b) |
| 9i | Notification + Audit (`Notification`, `Audit`) | 2 + 1 | Chỉ phần HTTP; class công khai đã chuyển ở Task 3 |

- [ ] **Step 1:** Với mỗi dòng: thực hiện P1–P7, một PR. Sau mỗi module cập nhật `docs/progress.md`.
- [ ] **Step 2:** Enum dùng chung xuất hiện nhiều module (`question_type`, trạng thái điểm danh, `active/inactive`, 4 role code) tạo ở module sở hữu và để module khác import; không tạo thư mục Enum cấp cao.

## Phase C — (đã hủy)

`Phase C — Đổi namespace Model` (di chuyển `app/Models/<X>.php` → `app/Modules/<Owner>/Models/<X>.php`) đã bị hủy theo quyết định 2026-10-19 (`ARCH-17`, sửa đổi). Model ở lại `App\Models` vĩnh viễn, dùng chung cho mọi module; `model-ownership.md` chỉ còn dùng để enforce "ai được ghi" qua `ArchitectureTest`, không dùng để dời file. Lý do: quan hệ Eloquent cắt ngang module quá nhiều, dời file không giải quyết thêm vấn đề kỹ thuật so với chi phí/rủi ro đổi namespace trên diện rộng.

## Phase D — Dọn dẹp

### Task 11: Hoàn tất

- [ ] **Step 1:** Xóa `app/Services` và thư mục `app/Http/Controllers/Api` khi rỗng (**giữ nguyên `app/Models`** — không xóa, xem Phase C đã hủy ở trên). `MODULES` trong `ArchitectureTest` gồm đủ 12 module; thêm kiểm tra không còn thư mục cấp cao theo tầng khác (ngoại trừ `app/Models`).
- [ ] **Step 2:** Tạo task riêng cài PHPStan/Larastan (level thấp, tăng dần) — chỉ làm khi user duyệt.
- [ ] **Step 3:** Cập nhật `piclass-api/CLAUDE.md` mục Trạng thái (bỏ "đích đến", ghi "đã áp dụng") và `docs/progress.md`; chuyển `ARCH-17` kèm ngày hoàn tất.

## Self-review

- Quy ước 1-18 trong CLAUDE.md: rule 1-5, 7 → P3; rule 2, 8 → P4 + Task 4; rule 6 → P5; rule 9, 10 → Task 6 và Task 9; rule 11 → Task 9d/9 Step 2; rule 12 → Task 5, 8; rule 16-17 → Task 3, 6; test phân quyền → P2 (viết trước khi chuyển code); test kiến trúc → Task 6.
- Rủi ro đã gắn task: `entity_type` (Task 1), `GetAttempt` gọi controller (Task 4), `ResultCalculator` trộn trách nhiệm (Task 8), audit ngoài transaction (Task 9a, 9b).
- Khoảng hở đã biết (22 controller: UserManagement 10, Assessment 4, Lesson 4, Attendance 2, Grading 2): đến khi module tương ứng được chuyển, audit vẫn ghi ngoài transaction (lỗi audit có thể để lại thay đổi không dấu vết). Nếu user muốn xử lý sớm, thêm một task vá nhanh bọc transaction cho 22 controller trước Task 9b.
