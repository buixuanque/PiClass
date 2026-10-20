# Task 9a — Refactor module `Attendance` sang quy ước module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** 6 controller của `Attendance` (`BulkMarkAttendance`, `UpdateAttendanceRecord`, `GetSessionAttendance`, `CreateAbsenceExplanation`, `ListAbsenceExplanations`, `ReviewAbsenceExplanation`) chuyển từ `app/Http/Controllers/Api/Attendance/` sang `app/Modules/Attendance/` theo `piclass-api/CLAUDE.md` (FormRequest + Policy + Action/Query + Resource), dùng `ActiveRole`, Enum cho trạng thái điểm danh/giải trình, audit chuyển vào cùng transaction với thay đổi dữ liệu (`DEC-AUD-005`), thông báo chạy qua Event + Listener sau commit; không đổi HTTP status, `error.code`, `error.message`, key JSON, giá trị chuỗi lưu DB — trừ các ngoại lệ ghi tường minh ở "Quyết định đã chốt" (D7, D8).

**Architecture:** Module `app/Modules/Attendance/{Http,Actions,Queries,Policies,Enums,Events,Listeners,Exceptions,Providers}`. Hai Policy (`AttendanceRecordPolicy`, `AbsenceExplanationPolicy`) thay `ClassScope::authorizeTeacherWrite` và các kiểm tra inline, đọc vai trò qua `ActiveRole`. Hai Action ghi có audit (`UpdateAttendanceRecord`, `ReviewAbsenceExplanation`) bọc `DB::transaction` gồm dữ liệu + history + audit. Test đặc tả hành vi viết trước trên code cũ; rule kiến trúc R8 chuyển từ "gộp Enum của mọi module" sang "Enum của chính module + Enum dùng chung khai báo tường minh" trước khi thêm `Attendance` vào `MODULES`.

**Tech Stack:** Laravel 11 (`^11.31`), PHP 8.2, PostgreSQL, PHPUnit, Pint. Container WSL `piclass-local-api-1`.

**Spec / nguồn:** `piclass-api/CLAUDE.md`; `docs/architecture/backend-refactor-playbook.md` (phân vai model, bẫy giữ nguyên hành vi, checklist test phân quyền, nợ đã biết); `docs/superpowers/specs/2026-10-14-academic-lesson-attendance-api-design.md` §7, §7.1; `docs/superpowers/specs/2026-10-15-notification-file-audit-api-design.md` §4 (danh sách audit: `attendance_record.update` bắt buộc `reason`; `absence_explanation.review` không bắt buộc `reason`) và §6 (thông báo); `docs/requirements/learning-rules.md` (`BR-ATT-001..005`), `decision-log.md` (`DEC-LRN-004..009`, `DEC-AUD-001/003/004/005`); `docs/access-control/*` (`resource-permissions.md`, `permission-matrix.md`, `data-scope.md`); `docs/database/schema.md` (CHECK `status`); `docs/architecture/model-ownership.md`; `ARCH-17`, `ARCH-20`, `ARCH-21`.

---

## Quy tắc cho người thực thi (đọc trước khi làm bất cứ gì)

1. **Gặp điểm mơ hồ, hoặc cần một quyết định không có trong plan: DỪNG và báo** (nêu file, dòng, các phương án). Không tự chọn, không "làm tạm rồi báo sau".
2. Các tình huống bắt buộc DỪNG (không giới hạn ở đây):
   - Một test đặc tả (Task 1) **fail trên code cũ**. Không sửa kỳ vọng cho khớp code, không sửa code.
   - Một test cũ (có trước task này) fail sau khi đổi code vì **hành vi khác**. Chỉ được sửa test cũ khi đổi namespace/đường dẫn hoặc kiểu so sánh (Enum `->value`); mọi trường hợp khác: DỪNG.
   - `grep` ở "Bảng ảnh hưởng" tìm thấy chỗ đọc/so sánh cột bị cast **ngoài** danh sách của plan.
   - Một giá trị trong DB (migration, factory, seeder, test) không nằm trong Enum tương ứng (`ValueError`).
   - Rule kiến trúc báo vi phạm ở file mà plan không nhắc tới.
   - Pint báo lỗi ở file bạn không sửa (5 file test lỗi sẵn trên `main`: `TeacherClassAssignmentTest`, `AuditLoggerTest`, `NotificationDispatcherTest`, `LessonSessionGeneratorTest`, `AttemptAuthorizationMatrixTest` — bỏ qua).
   - Container `piclass-local-api-1` không chạy. **Không** bật Docker Desktop trên Windows.
3. Không đụng file ngoài "Danh sách file được phép đụng". Đặc biệt **không sửa**: controller `Lesson/*` (kể cả khi chúng gọi `ClassScope::authorizeTeacherWrite`), `Assessment/*`, `ClassScope.php`, `NotificationDispatcher`, `AuditLogger`, migration, factory, seeder.
4. Trong test, giá trị kỳ vọng luôn viết **chuỗi literal** (`'present'`, `'Request failed.'`...), không dùng case của Enum hay hằng `DenialMessage` — để ai đổi giá trị thì test bắt được. Ngoại lệ duy nhất: test của chính Enum và test cast (Task 2).
5. Mỗi task kết thúc bằng commit local ở repo BE (message tiếng Anh, kết thúc bằng dòng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`). **Tuyệt đối không push, không mở PR, không merge, không xóa branch.**
6. Không dùng git worktree: container mount đúng thư mục `C:\Workspaces\home\PiClass\piclass-api`. Thử đột biến chỉ làm trên **bản sao** `/tmp/mutN` bên trong container; không bao giờ sửa working tree gốc để thử. **Không chạy test đột biến song song với full suite** (cùng một DB test).
7. **Comment trong code (docblock, comment dòng, mô tả trong test) viết tiếng Việt**; tên biến/hàm/class/test method và `error.code` tiếng Anh. Comment tiếng Anh cũ nằm trong file đã sửa thì dịch luôn (không dịch file không đụng tới).

## Global Constraints

- Giữ nguyên hành vi: HTTP status, `error.code`, `error.message`, tập key JSON, giá trị chuỗi trong JSON/DB/audit/history/notification (`event_key`, `title`, `body`) của mọi endpoint không đổi — ngoại lệ D7 (message 404 của `GET .../attendance`) và D8 (nếu có) ghi rõ.
- Baseline: **814 test pass** (full suite ~5 phút). Mọi task kết thúc với 0 fail và số test = 814 + số test mới đã thêm.
- Pint chỉ chạy `--test` trên các file `.php` mà branch này đổi.
- Mọi case 403/404 trong test mới assert **cả** `error.code` lẫn `error.message`.
- Một user nhiều vai trò được tính quyền theo **vai trò đang hoạt động** (`BR-USER-002`, `ARCH-11`).

### Chạy test (backend trong container WSL)

```bash
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker ps --format "{{.Names}}"'                                   # phải thấy piclass-local-api-1
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test tests/Feature/Attendance'
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test --filter=ClassName'
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 vendor/bin/pint --test <files>'
# Full suite (~5 phút): chạy nền, ghi log ra scratchpad, đọc dòng "Tests:" cuối log
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test' > "<scratchpad>/full.log" 2>&1
```

Chỉ truyền đường dẫn test **tương đối** (`tests/...`).

### Git

- BE: `git fetch origin`; working tree sạch; `git checkout -b refactor/attendance origin/main` (đã có Task 0, 0b: `ActiveRole`, Enum).
- Docs: `git fetch origin`; `git checkout -b docs/be-attendance-refactor origin/main`. Commit plan này ngay sau khi viết, trước khi thực thi.
- Hai commit đầu mỗi repo đổi quy ước ngôn ngữ (comment tiếng Việt) — đã làm.

## Quyết định đã chốt

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | Cấu trúc module: `Http/{Controllers,Requests,Resources}`, `Actions/{BulkMarkAttendance,UpdateAttendanceRecord,SubmitAbsenceExplanation,ReviewAbsenceExplanation}`, `Queries/{SessionAttendanceQuery,AbsenceExplanationsQuery,StudentsEnrolledAtSessionQuery}`, `Policies/{AttendanceRecordPolicy,AbsenceExplanationPolicy}`, `Enums/{AttendanceStatus,AbsenceExplanationStatus,AbsenceExplanationDecision}`, `Events/{AbsenceExplanationSubmitted,AbsenceExplanationReviewed}`, `Listeners/{SendAbsenceExplanationSubmittedNotification,SendAbsenceExplanationReviewedNotification}`, `Exceptions/StudentNotEligible`, `Providers/AttendanceServiceProvider` (đăng ký ở `bootstrap/providers.php`) | Mỗi use case một Action; 4 Action vì 4 endpoint ghi/có nghiệp vụ; 2 endpoint đọc là Query | Gộp Create+Review thành một Action (hai ý nghĩa nghiệp vụ khác nhau) |
| D2 | **Hai Policy.** `AttendanceRecordPolicy`: `markForSession(User, LessonSession)` (bulk), `update(User, AttendanceRecord)`, `viewForSession(User, LessonSession)`. `AbsenceExplanationPolicy`: `submit(User, string $studentId)`, `review(User, AbsenceExplanation)`. Gọi dạng `Gate::inspect('markForSession', [AttendanceRecord::class, $session])` (cùng mẫu `SubmissionPolicy::listForAssessment`). `ClassScope::authorizeTeacherWrite` **giữ nguyên** (Lesson còn dùng, xem D14) | Mỗi Policy gắn với model của module; ability "ghi điểm danh của buổi này" thuộc `AttendanceRecord` dù đối tượng là `LessonSession` | Policy cho `LessonSession` (model của Lesson, module khác) |
| D3 | Quy tắc ghi điểm danh (giữ đúng thứ tự cũ): `isAdmin()` → allow; ngược lại `ClassScope::classIdsFor($user, 'teacher')->contains($classId)` → allow; ngược lại `Response::deny('Không có quyền thao tác với lớp này.')` (403, `FORBIDDEN`). Không dùng `denyAsNotFound` (cũ là 403). Vai trò rỗng (job/console chưa `set()`) = không phải admin → rơi vào kiểm tra teacher, **không** coi là admin | `abort_unless(..., 403, msg)` cũ; `ClassScope::classIdsFor($user,'teacher')` ≡ `$user->teacherProfile?->activeClassIds() ?? collect()` | Thêm hàm `ClassScope::teacherCoversClass` (đã có `classIdsFor`) |
| D4 | `review`: `isAdmin()` → allow; ngược lại teacher phải phủ lớp của `lessonSession`, nếu không `Response::deny('Request failed.')` (403 `FORBIDDEN`, message là chuỗi rỗng → `'Request failed.'` của handler cũ; Laravel thay message rỗng bằng `This action is unauthorized.` nên phải truyền tường minh). Cũ chỉ kiểm tra khi vai trò = `teacher` (vai trò khác lọt qua) — hai vai trò khác (`student`/`parent`/rỗng) không tới được endpoint vì `role:admin,teacher`; plan chọn **default-deny** cho mọi vai trò không-admin (đúng quy ước "chưa set = không quyền"), ghi vào báo cáo | Giữ message; an toàn mặc định | Giữ "vai trò khác lọt" (lỗ hổng tiềm ẩn nếu route đổi) |
| D5 | `viewForSession`: admin allow; ngược lại `ClassScope::classIdsFor($user, $activeRole->code())->contains($classId)` → allow, nếu không `Response::denyAsNotFound(DenialMessage::NOT_FOUND)` | Cũ: `$activeRole !== 'admin' && !classIdsFor(...)->contains(...)` → 404 | — |
| D6 | `submit(User, string $studentId)`: `$user->parentProfile` và `->activeChildrenIds()->contains($studentId)` (lọc `status='active'` **và** `valid_to` rỗng/tương lai — **khác** `ClassScope::parentCoversStudent`, chỉ lọc `status`) → allow; ngược lại `Response::deny('Không có quyền gửi giải trình cho học viên này.')` (403). Không đọc `ActiveRole` (route `role:parent`) | Giữ đúng điều kiện cũ (`activeChildrenIds`) | Dùng `parentCoversStudent` (sẽ đổi hành vi: liên kết hết hạn `valid_to` nhưng `status` còn `active` được qua) |
| D7 | **Ngoại lệ hành vi 1 — message 404 của `GET /lesson-sessions/{id}/attendance`.** Cũ trả JSON tự dựng `NOT_FOUND` / `'Không tìm thấy buổi học.'` cho buổi ngoài phạm vi, trong khi ID không tồn tại trả `'Request failed.'` (handler `ARCH-20`): hai message khác nhau cho phép người gọi phân biệt "không tồn tại" với "của lớp khác" (chính là lỗ enumeration mà `ARCH-20` đã chốt xóa). Sau refactor Policy `denyAsNotFound(DenialMessage::NOT_FOUND)` → handler luôn trả `'Request failed.'`. `error.code` (`NOT_FOUND`) và status (404) giữ nguyên. Test đặc tả (Task 1) ghim message cũ; ở Task 5 sửa **đúng một** kỳ vọng này và ghi lý do trong commit. `GetLessonSessionController` (Lesson) còn cùng tồn dư → ghi vào "Nợ đã biết", sửa ở 9e | Áp dụng quyết định đã chốt `ARCH-20`, không phải business rule mới | Giữ message cũ bằng `DomainException` (giữ nguyên lỗ enumeration; Policy không ném được) |
| D8 | `CreateAbsenceExplanationRequest::authorize()` trả `true` (docblock nêu `role:parent` ở route); kiểm tra "Parent có liên kết với student này" chạy trong `withValidator()->after()` **chỉ khi validate đã qua** (`Gate::inspect('submit', [AbsenceExplanation::class, $studentId])->authorize()`). Lý do: cũ chạy `validate` (422) **trước** kiểm tra liên kết (403); `authorize()` chạy trước `rules()` nên chuyển thẳng vào đó sẽ đổi thứ tự 422/403 cho request vừa sai input vừa sai quyền. Ba endpoint còn lại có Policy theo route-model nên `authorize()` chạy trước validate **đúng như cũ** | Giữ thứ tự cũ (không nằm trong ngoại lệ đã chấp nhận) | Gọi Policy trong `authorize()` và chấp nhận 403-trước-422 (đổi hành vi) |
| D9 | **Audit trong transaction** (`DEC-AUD-005`). `UpdateAttendanceRecord`: một `DB::transaction` gồm khóa dòng (`lockForUpdate`), `update` record, `AttendanceHistory::create`, `AuditLogger::record` (`attendance_record.update`, bắt buộc `reason`, `correlationKey = class_id`). `ReviewAbsenceExplanation`: một `DB::transaction` gồm `update` + `AuditLogger::record` (`absence_explanation.review`, không `reason`, `after = ['status' => ...]`, không `before`) + dispatch event. Trường bắt buộc (`reason`, `status`) validate bằng FormRequest **trước** khi ghi (`DEC-AUD-004`); `before`/`after` snapshot trong Action, ghi `->value` (không để Enum vào mảng). `BulkMarkAttendance` **không audit/history** (không có trong danh sách spec §4; chỉ tạo mới, không sửa) nhưng bọc transaction để một lô thất bại không để lại một nửa | Danh sách audit ở spec §4 là nguồn; `DEC-AUD-005` không cho "ghi thử rồi bỏ qua lỗi" | Thêm audit cho bulk mark (event ngoài danh sách bắt buộc, chưa có quyết định) |
| D10 | **Thông báo qua Event + Listener sau commit** (CLAUDE.md "Notification"): `AbsenceExplanationSubmitted($explanation)` → listener gửi cho Teacher có `TeacherClassAssignment` `status='active'` của lớp (đúng query cũ); `AbsenceExplanationReviewed($explanation, AbsenceExplanationStatus $status)` → listener gửi cho Student luôn + Parent đã nộp **chỉ khi** liên kết còn hiệu lực. Giữ nguyên `event_key`, `event_type`, `title`, `body`, `context`. Listener `ShouldQueue` + `$afterCommit = true`, event dùng `SerializesModels`. Hệ quả: lỗi gửi thông báo không còn làm hỏng nghiệp vụ chính (đúng quy ước); trong môi trường test (`sync`) hành vi quan sát được giữ nguyên | Quy ước + mẫu `ResultProgress` | Giữ `NotificationDispatcher` gọi trực tiếp trong Action (vi phạm quy ước Notification) |
| D11 | Kiểm tra liên kết Parent–Student của `ReviewAbsenceExplanation` (inline trong controller cũ) chuyển vào listener `SendAbsenceExplanationReviewedNotification` và dùng **`ClassScope::parentCoversStudent($parentUser, $studentId)`** (điều kiện y hệt cũ: `parent_id` + `student_id` + `status='active'`, không lọc `valid_to`): lấy các `User` có `person_profile_id` của Parent đã nộp rồi `filter` bằng hàm này. Đây là bộ lọc **người nhận thông báo**, không phải kiểm tra quyền | Hành vi giữ nguyên; xóa dòng nợ "3 controller cũ kiểm tra liên kết inline" cho Attendance | `StudentPolicy::viewAsParent` (trả `Response`, không hợp lọc người nhận) |
| D12 | Enum (đúng CHECK trong migration/`database/schema.md`): `AttendanceStatus` = `present, absent, late, excused` (cast `AttendanceRecord.status`); `AbsenceExplanationStatus` = `submitted, under_review, accepted, rejected` (cast `AbsenceExplanation.status`); `AbsenceExplanationDecision` = `under_review, accepted, rejected` (giá trị Reviewer được gửi; `Rule::enum` ở request review; `toStatus(): AbsenceExplanationStatus`). Validation `in:...` → `Rule::enum(...)` (test ghim `error.details` giữ nguyên message) | Quy ước Enum; `submitted` do hệ thống đặt, Reviewer không được gửi | Một Enum duy nhất kèm `in:` hardcode |
| D13 | Lỗi `STUDENT_NOT_ELIGIBLE` (422): một `DomainException` `StudentNotEligible` cho cả hai nơi dùng (cùng điều kiện "student không phải ClassMember hiệu lực tại thời điểm buổi học", `BR-ATT-004`), message ghi đè qua constructor/factory: bulk → `'Một số học viên không thuộc lớp này tại thời điểm buổi học.'` kèm `error.details.student_ids`; giải trình → `'Học viên không thuộc lớp của buổi học này.'` (không có `details`). Cần `DomainException` hỗ trợ `details()` (mặc định `null`; handler thêm `error.details` khi khác `null`) — thay đổi nhỏ ở `app/Support/Exceptions/DomainException.php` + `bootstrap/app.php`, có test | Giữ nguyên code/message/shape; envelope `CLAUDE.md` đã có `details?` | Hai mã lỗi khác nhau (đổi `error.code` không cần thiết); nhét `details` qua `extra()` (đặt ở top-level, sai vị trí) |
| D14 | **`ClassScope::authorizeTeacherWrite` và 3 entry `KNOWN_SUPPORT_VIOLATIONS` GIỮ NGUYÊN**: 5 controller Lesson (`CancelLessonSession`, `CancelSchedule`, `CompleteLessonSession`, `UpdateLessonSession`, `UpdateSchedule`) còn gọi nó. Chỉ sửa chữ "owner task" của 3 entry từ "Attendance/Lesson" thành "Lesson (Task 9e)" | Kế hoạch tổng: Lesson refactor ở 9e | Dời hàm ngay (phải sửa controller Lesson) |
| D15 | `StudentsEnrolledAtSessionQuery::handle(LessonSession, iterable $studentIds): Collection` (`ClassMember` cùng `class_id`, `valid_from <= starts_at`, `valid_to` rỗng hoặc `> starts_at`; không lọc `status` — đúng cũ) dùng chung cho bulk và giải trình (hai nơi cùng lý do `BR-ATT-004`) | DRY khi 2 nơi chắc chắn cùng lý do đổi | Lặp điều kiện ở hai Action |
| D16 | `UpdateAttendanceRecord` đọc lại record dưới `lockForUpdate` trong transaction để `before` luôn khớp dữ liệu thật (`DEC-LRN-007`); trả về instance đã khóa (Resource chỉ cần `id,status,note`). `note`: `$data['note'] ?? $locked->note` (gửi `null`/bỏ qua = giữ ghi chú cũ, như cũ). `recorded_by_user_id` = actor | Tránh `before` cũ khi hai request cùng lúc | Dùng model bound không khóa |
| D17 | **R8 chuyển sang theo module**: tập giá trị để canh literal của module M = giá trị Enum trong `app/Modules/M/Enums/` + các Enum dùng chung khai báo ở hằng `SHARED_ENUMS` (`module => [Enum module khác]`). Hai test canh: mọi `use App\Modules\X\Enums\Y` (X khác M) trong M phải có trong `SHARED_ENUMS[M]`, và mọi mục `SHARED_ENUMS[M]` phải còn được import. Hệ quả cho 2 module cũ: 2 mục `KNOWN_LITERALS` hết vi phạm (`'manual'` ở `PublishResult` — `GradingMethod` không được `ResultProgress` import; `'cancelled'` ở `StudentProgressQuery` — `AttemptStatus` không được import) → xóa (test tự hết hạn bắt buộc xóa). Giữ cơ chế `KNOWN_LITERALS` khớp theo dòng | Loại báo nhầm khi thêm module (role code `'student'`, `'parent'`, `'pending'`...) mà vẫn canh Enum thật sự dùng; đề xuất đã nêu ở playbook | Giữ gộp toàn cục + ngoại lệ thủ công (mỗi module thêm một loạt ngoại lệ) |
| D18 | Không đổi ở lần này (ghi vào `architecture/open-decisions.md` mục mới `ARCH-22`, **không** sửa hành vi): (a) `BR-ATT-004`/`database/indexes-and-constraints.md` yêu cầu "không điểm danh buổi đã hủy trừ ngoại lệ Admin có audit" — code hiện **không** chặn buổi `cancelled`; (b) kiểm tra `ClassMember` hiệu lực chỉ dùng `valid_from`/`valid_to`, không lọc `status`. Đây là khoảng cách code–tài liệu, cần quyết định riêng | Refactor giữ nguyên hành vi; không tự chọn | Sửa luôn (đổi hành vi ngoài phạm vi) |

### Danh sách Enum

| Enum | Namespace | Giá trị (theo migration) | Cột được cast | Nguồn |
|---|---|---|---|---|
| `AttendanceStatus` | `App\Modules\Attendance\Enums` | `present`, `absent`, `late`, `excused` | `AttendanceRecord.status` | `2026_10_14_000005` |
| `AbsenceExplanationStatus` | `...Attendance\Enums` | `submitted`, `under_review`, `accepted`, `rejected` | `AbsenceExplanation.status` | `2026_10_14_000007` |
| `AbsenceExplanationDecision` | `...Attendance\Enums` | `under_review`, `accepted`, `rejected` | (không phải cột; giá trị Reviewer được gửi) | — |

### Literal còn lại sau task (`KNOWN_LITERALS` của R8)

Dự kiến rỗng cho `Attendance` (không literal nào trùng giá trị Enum của chính module; `'student'`/`'parent'`/`'teacher'`/`'active'`/`'ended'` không phải Enum của `Attendance`). Nếu R8 báo thêm: DỪNG và báo.

## Bảng ảnh hưởng (kết quả grep `app/ database/ routes/ config/ tests/`)

Quét: `grep -rniE "attendance|absence_explanation|AbsenceExplanation"` trên `app database routes config tests`.

### Code ngoài module `Attendance`

| File:dòng | Đọc/ghi gì | Xử lý |
|---|---|---|
| `app/Models/AttendanceRecord.php`, `app/Models/AbsenceExplanation.php` | khai báo `$casts` | **Sửa** (thêm cast) — bản chất task |
| `app/Models/{LessonSession.php:36, Student.php:35}` | quan hệ `attendanceRecords()` | **Không sửa** (chỉ quan hệ) |
| `app/Http/Controllers/Api/Lesson/CancelLessonSessionController.php:20` | `$lessonSession->attendanceRecords()->exists()` | **Không sửa** (không đọc cột `status`) |
| `database/factories/{AttendanceRecord,AbsenceExplanation}Factory.php` | `'status' => 'present'` / `'submitted'` | **Không sửa** (chuỗi hợp lệ gán vào thuộc tính cast Enum) |
| `database/seeders/**` | không tạo dữ liệu hai bảng | **Không đụng** |
| `app/Support/ClassScope.php` | `authorizeTeacherWrite` (Lesson còn dùng) | **Không sửa** (D14) |
| `routes/api.php` (6 dòng) | tham chiếu controller cũ | **Sửa** (trỏ sang controller mới) |
| `bootstrap/providers.php`, `bootstrap/app.php` | provider; handler `details` | **Sửa** (D1, D13) |
| `app/Support/Exceptions/DomainException.php` | thêm `details()` | **Sửa** (D13) |
| `app/Modules/{Audit,Notification}/**` | không đọc cột bị cast | **Không đụng** |
| Test chạm cột: `tests/Feature/Attendance/*`, `tests/Feature/Audit/AttendanceLessonAuditTest.php`, `tests/Feature/Notification/AttendanceLessonNotificationTest.php`, `tests/Feature/Lesson/LessonSessionTest.php` | chỉ `assertDatabaseHas(... 'status' => 'x')`, `assertSame([], json)` — không `assertSame('x', $model->status)` | **Không sửa** (đã rà: không có `assertSame/NotSame` trên thuộc tính model, không `pluck('status')`) |

Kết luận: ngoài module, **chỉ hai Model** (`AttendanceRecord`, `AbsenceExplanation`: `$casts`) + routes + provider + `DomainException`/handler cần sửa. Không controller/Service/Resource/Listener/factory/seeder cũ nào cần sửa.

## Review Focus (cho Opus)

1. **Phân quyền (đối chiếu `docs/access-control` + `requirements`):** D3-D6 — admin/teacher được phân công/Parent liên kết hiệu lực; Student không thấy giải trình; Student/Parent chỉ thấy bản ghi của mình/child (`DEC-LRN-009`); Teacher hết phân công mất quyền.
2. **Giữ nguyên hành vi:** thứ tự `authorize → validate → state` mỗi endpoint (D8 là chỗ duy nhất phải xử lý đặc biệt); message 403/404; `note` giữ khi gửi `null` (D16); `review_note` chỉ ghi khi key có mặt.
3. **Hai ngoại lệ có chủ đích:** D7 (message 404), D17 (hai mục `KNOWN_LITERALS` bị xóa — R8 yếu đi ở hai chỗ này; so với việc loại báo nhầm).
4. **Audit:** D9 — audit lỗi hủy cả thay đổi (cả history), `before`/`after` là chuỗi, `correlation_key`, `reason` bắt buộc ở `attendance_record.update`.
5. **Thông báo (D10):** đổi từ gọi trực tiếp sang Event + Listener sau commit — đúng quy ước nhưng đổi thời điểm gửi trên production (queue); test ghim `event_key/title/body/context`.
6. **Bảng đột biến (Task 8):** cái nào sống sót và test bổ sung.

---

## Danh sách file được phép đụng

| File | Trách nhiệm | Task |
|---|---|---|
| `tests/Feature/Attendance/Concerns/AttendanceFixtures.php` (mới) | Trait dựng fixture dùng chung cho test Attendance | 1 |
| `tests/Feature/Attendance/{AttendanceEndpointsBehaviorTest,AbsenceExplanationBehaviorTest}.php` (mới) | Test đặc tả hành vi trên code cũ | 1 |
| `app/Modules/Attendance/Enums/{AttendanceStatus,AbsenceExplanationStatus,AbsenceExplanationDecision}.php` (mới) | Enum | 2 |
| `tests/Unit/Modules/Attendance/Enums/*Test.php` (mới) | Tập giá trị Enum | 2 |
| `app/Models/{AttendanceRecord,AbsenceExplanation}.php` | Cast | 2 |
| `app/Modules/Attendance/Http/{Controllers,Requests,Resources}/*.php` (mới) | Lớp HTTP | 4-5 |
| `app/Modules/Attendance/{Actions,Queries,Policies,Events,Listeners,Exceptions,Providers}/**` (mới) | Nghiệp vụ | 3-5 |
| `app/Support/Exceptions/DomainException.php`, `bootstrap/app.php` | `details()` | 3 |
| `bootstrap/providers.php`, `routes/api.php` | Đăng ký, route | 4-5 |
| `app/Http/Controllers/Api/Attendance/*` | **Xóa** (6 file) | 5 |
| `tests/Feature/Models/EnumCastsTest.php` | Thêm 2 cột cast | 2 |
| `tests/Feature/DomainExceptionTest.php` | Test `details()` | 3 |
| `tests/Architecture/ArchitectureTest.php` | R8 theo module, `MODULES`, dịch comment | 6 |
| `tests/Feature/Attendance/{AttendancePolicyTest,AbsenceExplanationPolicyTest,AttendanceOutsideHttpTest,AttendanceAuditRollbackTest,AttendanceActionsTest}.php` (mới) | Phân quyền, rollback, Action | 7 |
| `piclass-api/CLAUDE.md`; docs: playbook, `error-codes.md`, `model-ownership.md`, `architecture/open-decisions.md`, `progress.md` | Ghi nhận | 9 |

---

## Chuẩn bị (trước Task 1)

- [ ] **P1: Kiểm tra container.** Lệnh `docker ps` ở trên. Expected: có `piclass-local-api-1`. Không có: DỪNG.
- [ ] **P2: Tạo branch.** Docs: `docs/be-attendance-refactor` từ `origin/main` (đã tạo); commit plan này. BE: `refactor/attendance` từ `origin/main` (đã tạo, working tree sạch).
- [ ] **P3: Baseline.** Full suite (nền). Expected: `814 passed`. Khác 814: DỪNG và báo con số.

---

### Task 1: Test đặc tả hành vi trên code cũ

Mục đích: ghim hành vi hiện tại của cả 6 endpoint **trước khi sửa**, gồm toàn bộ tập key response, status, `error.code` + `error.message` của mọi 403/404/422, giá trị chuỗi trong JSON/DB/audit/history/notification. **Tất cả phải xanh trên code CŨ.** Test đỏ: DỪNG và báo.

**Files:** Create `tests/Feature/Attendance/Concerns/AttendanceFixtures.php`, `AttendanceEndpointsBehaviorTest.php`, `AbsenceExplanationBehaviorTest.php`. Mẫu fixture: `AttendanceTest`, `AbsenceExplanationTest`, `AttendanceLessonAuditTest` (đừng sửa các file cũ).

`AttendanceFixtures` (trait): `signIn(User, string $role)` (ghi `UserRole` + `withSession(['active_role_code' => $role])`), `teacherUserFor(SchoolClass, bool $active = true)`, `adminUser()`, `parentUserLinkedTo(Student, array $relationshipOverrides = [])`, `studentUser()`, `sessionWithRoster(SchoolClass, int $students)` trả `LessonSession` + `Student` (mỗi student có `ClassMember` hiệu lực trước `starts_at`). Mọi hàm tạo dữ liệu **thật** cho lớp/người khác (không fixture rỗng).

- [ ] **Step 1: `AttendanceEndpointsBehaviorTest`** (mọi kỳ vọng là literal)

| Test | Thiết lập | Assertion |
|---|---|---|
| `test_get_returns_data_with_exact_item_keys_for_admin` | admin; session có 2 record (`present` có note, `late` note null) | 200; keys cấp trên = `['data']`; mỗi item keys = `['full_name','note','status','student_id']`; `status` literal; `full_name` đúng |
| `test_get_teacher_assigned_sees_all_records` / `..._teacher_other_class_gets_404_with_legacy_message` | teacher của lớp A / teacher của lớp B (lớp B có session + record thật) | 200 đủ record / 404, `error.code` `'NOT_FOUND'`, `error.message` `'Không tìm thấy buổi học.'` (message **cũ**, sửa ở Task 5 theo D7) |
| `test_get_teacher_with_ended_assignment_gets_404` | `TeacherClassAssignment` `status='ended'` (`valid_to` quá khứ) | 404, code+message cũ |
| `test_get_student_sees_only_own_record` | student thuộc lớp, session có record của student và của bạn học | 200; chỉ 1 item đúng student_id; `data` là list (key 0) |
| `test_get_student_outside_class_gets_404` / `test_get_parent_sees_only_active_children` / `test_get_parent_of_child_in_other_class_gets_404` / `test_get_parent_with_ended_link_gets_404` | tương ứng; có record của child khác trong cùng lớp | đúng tập record / 404 (message cũ) |
| `test_get_unknown_session_returns_request_failed` | uuid không tồn tại | 404, code `'NOT_FOUND'`, message `'Request failed.'` |
| `test_get_empty_roster_returns_empty_list` | session chưa có record | 200, `data` = `[]` |
| `test_bulk_response_lists_every_record_of_the_session` | admin; có sẵn record student B, gửi student A | 200; keys `['data']`; item keys `['status','student_id']`; chứa cả A và B |
| `test_bulk_stores_note_status_and_recorder` | gửi `note` | DB: `status`, `note`, `recorded_by_user_id` = actor; omit note → `null` |
| `test_bulk_forbidden_for_unassigned_or_ended_teacher` | teacher lớp khác / assignment `ended` | 403, code `'FORBIDDEN'`, message `'Không có quyền thao tác với lớp này.'` |
| `test_bulk_forbidden_wins_over_validation` | teacher lớp khác + body rỗng | 403 (không phải 422) |
| `test_bulk_dual_role_user_acting_as_teacher_without_assignment_is_forbidden` | user admin+teacher, `active_role_code='teacher'`, không phân công | 403 như trên |
| `test_bulk_validation_errors` (DataProvider) | thiếu `records`, `records=[]`, thiếu `student_id`, `student_id` không phải uuid / không tồn tại, `status='bogus'`, thiếu `status`, `note` không phải chuỗi | 422, code `'VALIDATION_FAILED'`; `error.details` đúng key và message hiện tại (copy từ lần chạy code cũ) |
| `test_bulk_ineligible_students_reject_whole_batch_with_details` | 1 hợp lệ + 1 chưa là ClassMember + 1 `valid_from` sau `starts_at` + 1 `valid_to` trước `starts_at` | 422; code `'STUDENT_NOT_ELIGIBLE'`; message `'Một số học viên không thuộc lớp này tại thời điểm buổi học.'`; `error.details.student_ids` = đúng 3 id sai (list); không record nào được tạo |
| `test_bulk_member_who_left_after_the_session_started_is_eligible` | `valid_to` > `starts_at` | 200 |
| `test_bulk_validation_wins_over_eligibility` | `status='bogus'` + student chưa là member | 422 `VALIDATION_FAILED` |
| `test_bulk_does_not_overwrite_existing_record` | record có sẵn `present` note 'a', gửi `absent` note 'b' | DB giữ `present`/'a'; không có history/audit |
| `test_bulk_duplicate_student_in_one_request_creates_one_record` | 2 dòng cùng student | 1 record |
| `test_bulk_writes_no_audit_log` | bulk thành công | `audit_logs` rỗng (spec §4 không liệt kê) |
| `test_patch_updates_record_history_and_audit` | teacher assigned; record `present` note 'cũ' | 200; keys `['attendance_record']` → `['id','note','status']`; DB `status`,`note`,`recorded_by_user_id`; `attendance_histories`: `reason`, `before_data` = `{"status":"present","note":"cũ"}`, `after_data` = `{"status":"absent","note":"cũ"}`, `changed_by_user_id`, `changed_at` không rỗng; `audit_logs`: `action='attendance_record.update'`, `entity_type='AttendanceRecord'`, `entity_id`, `reason`, `correlation_key` = class_id, `actor_user_id`, `before_data`/`after_data` như history |
| `test_patch_note_semantics` (DataProvider) | note omit / `null` / `'mới'` | omit & null giữ note cũ; chuỗi thay note; history `after_data.note` tương ứng |
| `test_patch_same_status_still_writes_history_and_audit` | gửi lại `present` | 200, 1 history, 1 audit |
| `test_patch_validation_errors_leave_everything_untouched` (DataProvider) | thiếu `reason`, `reason` rỗng, thiếu `status`, `status='bogus'`, `note` không phải chuỗi | 422 `VALIDATION_FAILED` + `details` literal; record/history/audit không đổi |
| `test_patch_forbidden_for_unassigned_or_ended_teacher_and_before_validation` | teacher lớp khác (body sai) / assignment ended | 403 `'FORBIDDEN'`, `'Không có quyền thao tác với lớp này.'` |
| `test_patch_unknown_record_returns_request_failed` | uuid lạ | 404 `'NOT_FOUND'`, `'Request failed.'` |
| `test_patch_admin_can_update_any_class` | admin | 200 |
| `test_role_middleware_blocks_student_and_parent_on_writes` | student / parent gọi PUT, PATCH | 403, code `'FORBIDDEN'`, message `'Bạn không có quyền thực hiện hành động này.'` |

- [ ] **Step 2: `AbsenceExplanationBehaviorTest`**

| Test | Thiết lập | Assertion |
|---|---|---|
| `test_post_creates_submitted_explanation_with_exact_keys` | parent liên kết hợp lệ, student là ClassMember hiệu lực | 201; keys `['absence_explanation']` → `['id','lesson_session_id','status','student_id']`; `status='submitted'`; DB `parent_id`, `message`, `attendance_record_id` null |
| `test_post_links_existing_attendance_record` | có record của student/session | `attendance_record_id` = id đó |
| `test_post_forbidden_when_not_linked` (DataProvider) | không liên kết / liên kết `status='ended'` / `status='active'` nhưng `valid_to` quá khứ / con của parent khác / user vai trò parent không có hồ sơ Parent | 403, code `'FORBIDDEN'`, message `'Không có quyền gửi giải trình cho học viên này.'` |
| `test_post_validation_errors` (DataProvider) | thiếu field, uuid sai, uuid không tồn tại | 422 `VALIDATION_FAILED` + `details` literal |
| `test_post_validation_wins_over_forbidden` | body sai + student không liên kết | 422 (giữ thứ tự cũ, D8) |
| `test_post_student_not_enrolled_at_session_time` | không ClassMember / `valid_to` trước `starts_at` | 422; code `'STUDENT_NOT_ELIGIBLE'`; message `'Học viên không thuộc lớp của buổi học này.'`; `error` chỉ có hai key `code`,`message` |
| `test_post_member_who_left_after_the_session_started_is_eligible` | `valid_to` > `starts_at` | 201 |
| `test_post_role_middleware` | teacher/student/admin POST | 403 `'Bạn không có quyền thực hiện hành động này.'` |
| `test_post_notifies_active_teachers_of_the_class_only` | 2 teacher của lớp (1 `ended`), teacher lớp khác | notification cho teacher active: `event_key` = `absence_explanation:{id}:submitted`, `event_type='absence_explanation_submitted'`, `title='Có đơn xin phép mới cần duyệt'`, `body='Phụ huynh vừa nộp đơn xin phép vắng cho một học sinh trong lớp bạn.'`, context `lesson_session_id`; không có cho hai người còn lại (đếm tổng = 1) |
| `test_list_item_keys_and_filters` | admin; 3 giải trình khác student/status | 200; keys `['data']`; item keys `['id','lesson_session_id','message','status','student_id']`; lọc `student_id`, `status`, cả hai; `status=bogus` → `[]`; `student_id=` rỗng bị bỏ qua |
| `test_list_teacher_scope` / `..._ended_assignment_gets_empty` | teacher lớp A; giải trình của lớp A và B | chỉ lớp A / `[]` |
| `test_list_parent_scope` | parent; giải trình của child (kể cả do parent khác nộp), của student khác; link ended | chỉ của child đang liên kết / `[]` khi link ended |
| `test_list_student_gets_empty` | student; có giải trình thật | 200, `[]` |
| `test_review_updates_and_returns_exact_keys` | teacher assigned | 200; keys `['absence_explanation']` → `['id','review_note','status']`; DB: `status`, `review_note`, `reviewed_by_user_id`, `reviewed_at` không rỗng; `audit_logs`: `action='absence_explanation.review'`, `entity_type='AbsenceExplanation'`, `before_data` null, `after_data` = `{"status":"accepted"}`, `reason` null, `actor_user_id` |
| `test_review_each_allowed_decision` (DataProvider) | `under_review`, `accepted`, `rejected` | 200 |
| `test_review_review_note_semantics` | note có sẵn; omit / `null` / `'mới'` | omit giữ; `null` xóa; chuỗi thay |
| `test_review_can_change_a_previous_decision` | đã `accepted` → `rejected` | 200 (không có kiểm tra trạng thái) |
| `test_review_validation_errors` (DataProvider) | thiếu `status`, `status='submitted'`, `'bogus'`, `review_note` không phải chuỗi | 422 `VALIDATION_FAILED` + `details` literal; DB không đổi |
| `test_review_forbidden_for_out_of_scope_teacher_before_validation` | teacher lớp khác (body sai) / assignment ended | 403 `'FORBIDDEN'`, message `'Request failed.'` |
| `test_review_admin_any_class` / `test_review_unknown_id_404` / `test_review_role_middleware` | | 200 / 404 `'NOT_FOUND'`,`'Request failed.'` / parent+student 403 middleware |
| `test_review_notifies_student_and_active_parent` | link active | notification student + parent: `event_key=absence_explanation:{id}:reviewed:accepted`, `event_type='absence_explanation_reviewed'`, `title='Đơn xin phép đã được xử lý'`, `body='Trạng thái: accepted.'`, context `lesson_session_id` |
| `test_review_with_ended_link_notifies_only_student` | link `ended` | chỉ student |
| `test_review_repeat_same_decision_does_not_duplicate_notification` / `..._new_decision_notifies_again` | review 2 lần | số notification 1 / 2 (event_key chứa status) |

- [ ] **Step 3: Chạy trên code cũ.** `php artisan test tests/Feature/Attendance`. Expected: tất cả xanh (test cũ + mới). Test mới đỏ: DỪNG, không sửa kỳ vọng. `details` literal lấy bằng cách chạy một lần rồi chép chính xác.
- [ ] **Step 4: Pint `--test`** trên 3 file mới. **Commit:** `test: pin Attendance endpoint behavior before the module refactor`.

---

### Task 2: Enum + cast

**Files:** Create 3 Enum + 3 unit test; Modify `AttendanceRecord.php`, `AbsenceExplanation.php`, `tests/Feature/Models/EnumCastsTest.php`.

- [ ] **Step 1:** Viết 3 Enum (`string` backed, case PascalCase: `Present, Absent, Late, Excused`; `Submitted, UnderReview, Accepted, Rejected`; `UnderReview, Accepted, Rejected` + `toStatus()`), docblock tiếng Việt. Unit test (mẫu `ResultAudienceTest`): đúng tập giá trị theo thứ tự, `tryFrom` nhận mọi giá trị và từ chối `'bogus'`/`''`, `Decision::toStatus()` ánh xạ đúng và mọi case `Decision` tồn tại trong `AbsenceExplanationStatus`.
- [ ] **Step 2:** `$casts` — `AttendanceRecord.status => AttendanceStatus::class`, `AbsenceExplanation.status => AbsenceExplanationStatus::class` (giữ `reviewed_at`).
- [ ] **Step 3:** `EnumCastsTest::castColumns()` thêm `'AttendanceRecord.status'`, `'AbsenceExplanation.status'` (factory đã có; `make()` mặc định dùng factory). Dịch comment tiếng Anh của file sang tiếng Việt.
- [ ] **Step 4:** Chạy `tests/Feature/Attendance` + `EnumCastsTest` + `tests/Unit/Modules/Attendance`. Expected: xanh. **Lưu ý:** các controller cũ vẫn chạy với cast: `AttendanceRecord::firstOrCreate(... 'status' => $string)`, `$r->status` trả Enum → `response()->json` serialize ra giá trị chuỗi; `$attendanceRecord->only(['status','note'])` đưa Enum vào mảng audit/history → `json_encode` ra chuỗi đúng; nội suy `"Trạng thái: {$absenceExplanation->status}."` **sẽ lỗi** (Enum không đổi sang chuỗi) → sửa tạm controller cũ dùng `->value` ở đúng dòng đó (controller sẽ bị xóa ở Task 5; nếu chạy Task 1 vẫn xanh thì không cần sửa). Test Task 1 xanh = hành vi giữ nguyên. Đỏ: DỪNG.
- [ ] **Step 5:** Pint; **Commit:** `feat: add Attendance enums and cast the status columns`.

---

### Task 3: Hạ tầng dùng chung nhỏ — `DomainException::details()` + `StudentNotEligible`

**Files:** `app/Support/Exceptions/DomainException.php`, `bootstrap/app.php` (dịch comment tiếng Anh trong hai file), `tests/Feature/DomainExceptionTest.php` (thêm test), `app/Modules/Attendance/Exceptions/StudentNotEligible.php`.

- [ ] **Step 1:** Test đỏ trước: `test_domain_exception_renders_details_inside_the_error_object` — exception ẩn danh trả `details()` = `['k' => 1]` → response `error.details.k === 1`, không có `k` ở top-level; `test_domain_exception_without_details_has_no_details_key` — `error` chỉ có `code`,`message`.
- [ ] **Step 2:** `DomainException::details(): ?array { return null; }`; handler: `'error' => ['code'=>..., 'message'=>...] + ($e->details() !== null ? ['details' => $e->details()] : [])`, giữ `...$e->extra()`.
- [ ] **Step 3:** `StudentNotEligible`: `errorCode='STUDENT_NOT_ELIGIBLE'`, `status=422`; `public static function forSessionMembership(): self` (message giải trình), `public static function inBatch(array $studentIds): self` (message bulk + `details(): ['student_ids' => $ids]`). Unit/feature test nhỏ cho hai factory.
- [ ] **Step 4:** Chạy `DomainExceptionTest` + `ErrorEnvelopeTest` + `tests/Feature/Attendance`. Pint. **Commit:** `feat: let DomainException expose error details`.

---

### Task 4: Module skeleton — Policy, Query, Provider (chưa nối route)

**Files:** Create `Policies/{AttendanceRecordPolicy,AbsenceExplanationPolicy}.php`, `Queries/{SessionAttendanceQuery,AbsenceExplanationsQuery,StudentsEnrolledAtSessionQuery}.php`, `Providers/AttendanceServiceProvider.php`; Modify `bootstrap/providers.php`. Test phân quyền viết ở Task 7 (cần cả hai phía); ở đây chỉ viết khung và chạy test cũ.

- [ ] **Step 1: Policy** theo D2-D6. `AttendanceRecordPolicy` inject `ActiveRole`; private `canWrite(User, string $classId): Response` dùng chung cho `markForSession` (class_id của session) và `update` (`$record->lessonSession->class_id`).
- [ ] **Step 2: Query** — `SessionAttendanceQuery::handle(LessonSession, User): Collection` (eager `student.personProfile`; vai trò `student` → lọc theo `studentProfile?->id`; `parent` → `parentProfile?->activeChildrenIds()` rồi `->all()`; còn lại không lọc; trả `values()`); `AbsenceExplanationsQuery::handle(User, ?string $studentId, ?string $status): Collection` (4 nhánh vai trò đúng cũ, mặc định `whereRaw('1 = 0')`); `StudentsEnrolledAtSessionQuery` theo D15. Inject `ActiveRole`.
- [ ] **Step 3: Provider** `Gate::policy(AttendanceRecord::class, ...)`, `Gate::policy(AbsenceExplanation::class, ...)`; đăng ký ở `bootstrap/providers.php`.
- [ ] **Step 4:** Chạy full `tests/Feature/Attendance` (controller cũ vẫn chạy) + `ArchitectureTest`. Pint. **Commit:** `feat: add Attendance policies, queries and provider`.

---

### Task 5: Action, Controller, Request, Resource, route — từng endpoint

Mỗi bước: viết FormRequest/Resource/Controller/Action mới, đổi route sang controller mới, xóa controller cũ, chạy `tests/Feature/Attendance` + `Audit` + `Notification` + `Lesson` — **toàn bộ xanh (trừ đúng một kỳ vọng D7 ở bước 5a)**. Chỉ commit khi xanh.

- [ ] **5a `GetSessionAttendance`** (`SessionAttendanceQuery`; `GetSessionAttendanceRequest::authorize` → `Gate::inspect('viewForSession', [AttendanceRecord::class, $this->route('lessonSession')])`; `AttendanceEntryResource::collection(...)`, `$wrap = 'data'`). Sửa kỳ vọng D7: message `'Không tìm thấy buổi học.'` → `'Request failed.'` ở **các test GET của Task 1** (giữ `error.code='NOT_FOUND'`, 404), commit message nêu ARCH-20. **Commit:** `refactor: move GetSessionAttendance into the Attendance module`.
- [ ] **5b `ListAbsenceExplanations`** (`ListAbsenceExplanationsRequest`: `authorize(): true` + docblock nêu `role:admin,teacher,student,parent`; helper `studentIdFilter()/statusFilter()` dùng `filled` + `string()` y như cũ; `AbsenceExplanationSummaryResource::collection`). **Commit.**
- [ ] **5c `BulkMarkAttendance`** (Request: rules với `Rule::enum(AttendanceStatus::class)`; Action `handle(LessonSession, User, array $records): Collection` — `StudentsEnrolledAtSessionQuery` → `StudentNotEligible::inBatch(...)`; `DB::transaction` + `firstOrCreate`; trả records của session; Resource `MarkedAttendanceResource::collection`). **Commit.**
- [ ] **5d `UpdateAttendanceRecord`** (D9, D16; Resource `AttendanceRecordResource` `$wrap = null`). **Commit:** `refactor: move UpdateAttendanceRecord into the module and audit in its transaction`.
- [ ] **5e `SubmitAbsenceExplanation`** (D8: `CreateAbsenceExplanationRequest`; D10: event `AbsenceExplanationSubmitted` + listener; Resource `SubmittedAbsenceExplanationResource` ép 201 trong `withResponse`). **Commit.**
- [ ] **5f `ReviewAbsenceExplanation`** (D4, D9, D10, D11; `ReviewAbsenceExplanationRequest` `Rule::enum(AbsenceExplanationDecision::class)`, `review_note` `nullable|string`; Action nhận `$request->validated()` — ghi `review_note` **chỉ khi key có mặt**; audit trong transaction; event `AbsenceExplanationReviewed` mang `->status`). **Commit.**
- [ ] **5g:** Xóa `app/Http/Controllers/Api/Attendance/` (còn rỗng), dọn `use` thừa; routes đã trỏ controller mới. Kiểm tra `grep -rn "Controllers\\\\Api\\\\Attendance" app routes tests` rỗng.

---

### Task 6: Rule kiến trúc

**Files:** `tests/Architecture/ArchitectureTest.php` (dịch toàn bộ comment/docblock tiếng Anh sang tiếng Việt).

- [ ] **Step 1 (đỏ trước):** test sanity `test_attendance_literals_are_not_flagged_by_unrelated_enums` — `enumValues('Attendance')` không chứa `in_progress`, `pending`, `draft`, `private`, `student`, `parent`; chứa `present`, `under_review`. Đỏ vì `enumValues()` hiện không nhận tham số.
- [ ] **Step 2:** `enumValues(string $module)` = giá trị Enum của `app/Modules/{module}/Enums` + Enum trong `SHARED_ENUMS[module]`. Hằng `SHARED_ENUMS`: `ResultProgress => ['Assessment/AttemptScorePolicy', 'SubmissionGrading/FeedbackStatus', 'SubmissionGrading/FeedbackVisibility', 'SubmissionGrading/SubmissionStatus']`, `SubmissionGrading => ['QuestionBank/QuestionType', 'ResultProgress/PublicationStatus']`, `Attendance => []` (lấy đúng từ grep `use App\Modules\X\Enums\Y`). `scanModule` truyền `enumValues($module)`.
- [ ] **Step 3:** Hai test canh `SHARED_ENUMS`: `test_shared_enums_match_the_imports` (mọi import Enum module khác có trong hằng; mọi mục hằng còn được import). Sửa `test_enum_value_set_is_read_from_the_enum_folders` thành theo module.
- [ ] **Step 4:** Chạy; hai mục `KNOWN_LITERALS` (`PublishResult 'manual'`, `StudentProgressQuery 'cancelled'`) báo hết hạn → xóa (D17). Thêm `'Attendance'` vào `MODULES`. Cập nhật chữ "owner task" của 3 entry `KNOWN_SUPPORT_VIOLATIONS` thành "Lesson (Task 9e)" (D14). R1-R8 xanh cho 3 module. Có vi phạm khác: DỪNG.
- [ ] **Step 5:** Pint; **Commit:** `test: scope the R8 enum literal rule per module and add Attendance to MODULES`.

---

### Task 7: Test phân quyền, rollback, Action

Theo "Checklist test phân quyền" của playbook. Mọi test dùng fixture có dữ liệu thật ở lớp/người khác; 403/404 assert `error.code` + `error.message` literal.

- [ ] **`AttendancePolicyTest`** (gọi `Gate::forUser($user)->inspect(...)` và `app(AttendanceRecordPolicy::class)` trực tiếp, đặt `ActiveRole` tường minh): ma trận `markForSession`/`update`/`viewForSession` × {admin, teacher được phân công, teacher lớp khác (lớp khác có session+record+member thật), teacher `ended`, teacher `valid_to` quá khứ, student thuộc lớp, student lớp khác, parent link active, parent link `ended`, user admin+teacher đang ở vai trò teacher không phân công} → `allowed()`/`status()` (403 vs 404)/`message()`; vai trò rỗng cho user admin-không-hồ-sơ-teacher → bị từ chối; vai trò rỗng cho user có hồ sơ teacher được phân công → ghi được (khớp cũ: giá trị rỗng không phải admin, kiểm tra teacher).
- [ ] **`AbsenceExplanationPolicyTest`**: `submit` × {parent link active; parent hồ sơ có nhưng student của parent khác; link `ended`; link `active` + `valid_to` quá khứ; không có hồ sơ Parent}; `review` × {admin, teacher phủ lớp, teacher lớp khác (dữ liệu thật), teacher ended, vai trò rỗng, admin+teacher nhập vai teacher}.
- [ ] **`AttendanceOutsideHttpTest`**: `SessionAttendanceQuery`, `AbsenceExplanationsQuery` (vai trò rỗng ⇒ rỗng/default-deny, không lộ dữ liệu; sau khi `set('student')` chỉ thấy của mình), `StudentsEnrolledAtSessionQuery` chạy không cần session/Request; Policy gọi trực tiếp không qua queue.
- [ ] **`AttendanceAuditRollbackTest`** (mẫu `ResultPublishRollbackTest`): bind `AuditLogger` giả ném `RuntimeException('boom')`; `UpdateAttendanceRecord` → `assertSame('boom', ...)`, record giữ `status`/`note`/`recorded_by_user_id` cũ, `attendance_histories` = 0, `audit_logs` = 0; `ReviewAbsenceExplanation` → giải trình giữ `submitted`, `reviewed_by_user_id` null, không event (`Event::fake`), không notification; thêm điểm lỗi thứ hai: `AttendanceHistory` insert lỗi (ví dụ record bị xóa giữa chừng bằng listener `saving`) → audit cũng không ghi. Case thành công: audit + history + dữ liệu cùng tồn tại.
- [ ] **`AttendanceActionsTest`**: `UpdateAttendanceRecord` gọi trực tiếp — `before_data`/`after_data` là chuỗi (`'present'`, không phải object Enum) ở cả `attendance_histories` và `audit_logs`; hai lần cập nhật liên tiếp: `before` của lần 2 = `after` của lần 1; `BulkMarkAttendance` — lỗi giữa lô (một student không eligible) không tạo bản ghi nào, lô thành công tạo đủ; `SubmitAbsenceExplanation` dispatch `AbsenceExplanationSubmitted` đúng một lần sau commit; `ReviewAbsenceExplanation` dispatch `AbsenceExplanationReviewed` mang đúng `status`.
- [ ] Chạy toàn bộ test mới; Pint; **Commit** theo nhóm (policy / rollback / actions).

---

### Task 8: Thử đột biến (bắt buộc) — trên bản sao ngoài working tree

Quy trình: trong container `mkdir /tmp/mutN && cp -a /var/www/html/. /tmp/mutN/` (loại `storage/logs`), sửa **bản sao**, chạy test liên quan ở bản sao (`cd /tmp/mutN && php artisan test <filter>`), ghi test đỏ, xóa `/tmp/mutN`. Không chạy song song với full suite. Lỗi cố ý nào test không bắt: bổ sung test ở Task 7, chạy lại.

| # | Đột biến | Test kỳ vọng đỏ |
|---|---|---|
| 1 | `markForSession`: bỏ kiểm tra phân công (luôn allow) | Policy + Behavior (403 teacher lớp khác) |
| 2 | `markForSession`: vai trò rỗng coi như admin | Policy / OutsideHttp |
| 3 | `markForSession`: bỏ nhánh `isAdmin()` | Policy (admin không hồ sơ teacher) |
| 4 | `update`: dùng teacher "có bất kỳ phân công" thay vì đúng lớp | Policy (teacher lớp khác dữ liệu thật) |
| 5 | `viewForSession`: luôn allow | Policy + Behavior |
| 6 | `viewForSession`: dùng `'teacher'` cố định thay vì `code()` | Policy (student/parent) |
| 7 | `submit`: bỏ `contains` | Policy + Behavior (không liên kết) |
| 8 | `submit`: dùng `parentCoversStudent` thay `activeChildrenIds` | Policy (`valid_to` quá khứ) |
| 9 | `review`: bỏ kiểm tra teacher | Policy + Behavior |
| 10 | `review`: vai trò rỗng coi như admin | Policy / OutsideHttp |
| 11 | `review`: đổi message deny | Behavior (message literal) |
| 12 | `SessionAttendanceQuery`: bỏ lọc `student` | Behavior |
| 13 | `SessionAttendanceQuery`: bỏ lọc `parent` | Behavior |
| 14 | `AbsenceExplanationsQuery`: nhánh mặc định thành "không lọc" | Behavior (student) + OutsideHttp (vai trò rỗng) |
| 15 | `AbsenceExplanationsQuery`: bỏ lọc teacher / bỏ lọc parent / bỏ lọc `status` / bỏ lọc `student_id` | Behavior (4 đột biến riêng) |
| 16 | `StudentsEnrolledAtSessionQuery`: bỏ `valid_to` / bỏ `valid_from` / bỏ `class_id` | Behavior bulk + giải trình (3 đột biến riêng) |
| 17 | `UpdateAttendanceRecord`: audit sau transaction | Rollback |
| 18 | `UpdateAttendanceRecord`: history sau transaction | Rollback |
| 19 | `UpdateAttendanceRecord`: `before` lấy sau khi update | Behavior + Actions |
| 20 | `UpdateAttendanceRecord`: `note` luôn ghi đè | Behavior (note semantics) |
| 21 | `UpdateAttendanceRecord`: audit thiếu `reason` / `correlation_key` | Behavior |
| 22 | `ReviewAbsenceExplanation`: audit sau transaction | Rollback |
| 23 | `ReviewAbsenceExplanation`: `review_note` luôn ghi đè (kể cả key vắng) | Behavior |
| 24 | `ReviewAbsenceExplanation`: bỏ `reviewed_by_user_id` | Behavior |
| 25 | Listener review: bỏ lọc `parentCoversStudent` / bỏ student | Behavior (link ended) / Behavior |
| 26 | Listener submitted: bỏ lọc `status='active'` của phân công | Behavior |
| 27 | `SubmitAbsenceExplanation`: bỏ liên kết `attendance_record_id` / đổi status mặc định / bỏ kiểm tra enrollment | Behavior |
| 28 | `BulkMarkAttendance`: `updateOrCreate` thay `firstOrCreate` / bỏ qua student không đủ điều kiện thay vì từ chối cả lô / sai `recorded_by_user_id` | Behavior |
| 29 | `CreateAbsenceExplanationRequest`: bỏ điều kiện "validate đã qua" (403 trước 422) | Behavior (validation wins) |
| 30 | `BulkMarkAttendanceRequest::authorize` → `true` | Behavior + Policy |
| 31 | `AttendanceStatus`: thêm/bớt một case | EnumCastsTest (CHECK) + Enum unit |
| 32 | R8: quay lại `enumValues()` toàn cục | test sanity R8 (Task 6) |
| 33 | Bỏ `Gate::policy` của `AttendanceRecord` | toàn bộ Behavior 403 |

- [ ] Ghi bảng kết quả (đột biến → test đỏ / sống sót + test bổ sung) vào báo cáo cuối và `docs/progress.md`.

---

### Task 9: Tài liệu và nghiệm thu

- [ ] **P-docs:** `docs/architecture/error-codes.md` thêm `STUDENT_NOT_ELIGIBLE` (422, Attendance; hai message theo ngữ cảnh). `backend-refactor-playbook.md`: xóa dòng nợ "3 controller cũ kiểm tra liên kết inline" phần Attendance (còn 2 controller Assessment), sửa dòng nợ `ClassScope` (chỉ Lesson còn gọi `authorizeTeacherWrite`), bỏ mục "Trước khi thêm module mới vào `MODULES` (Task 9a Attendance trở đi), R8 cần xử lý" và dòng `StudentProgressQuery 'cancelled'` trong nợ Assessment, thêm nợ mới: `GetLessonSessionController` còn message 404 `'Không tìm thấy buổi học.'` lệch `ARCH-20` (sửa ở 9e), hai chỗ chưa audit/enforce (xem `ARCH-22`); cập nhật mục R8. `model-ownership.md` bảng `entity_type`: hai dòng Attendance trỏ Action mới. `architecture/open-decisions.md`: `ARCH-22` (D18). `piclass-api/CLAUDE.md`: danh sách module "Đã chuyển" thêm `Attendance`, mô tả R8 mới (`SHARED_ENUMS`). `docs/progress.md`: kết quả Task 9a + việc kế tiếp (Task 9b `UserManagement`; ghi chú: `GetLessonSessionController` nên gộp vào 9e).
- [ ] **Full suite** (nền): Expected `Tests:` = 814 + số test mới, 0 fail. `vendor/bin/pint --test` trên mọi file `.php` đã đổi. `git log --oneline origin/main..HEAD` ở cả hai repo; kiểm tra `git status` sạch; **xác nhận chưa push**.

## Tiêu chí xong

- 6 controller cũ đã xóa, 6 controller + Request + Resource mới trong `app/Modules/Attendance`, route trỏ đúng; `ArchitectureTest` R1-R8 xanh với `Attendance` trong `MODULES`.
- Test đặc tả Task 1 xanh trước và sau refactor (chỉ khác kỳ vọng D7 ghi tường minh); full suite xanh, số test ≥ 814.
- Audit + history + dữ liệu cùng transaction, có test rollback; mọi 403/404 test assert `code` + `message`.
- Bảng đột biến: không còn lỗi cố ý sống sót (hoặc sống sót được giải thích là tương đương).
- Tài liệu cập nhật; hai repo commit local, chưa push.

## Ghi chú thực thi (điền sau khi xong)

- **Khác plan:** (1) `SessionAttendanceQuery` mặc định từ chối với vai trò không phải admin/teacher/student/parent (plan Task 4 ghi "còn lại không lọc"); HTTP không đổi vì route đã chặn vai trò, nhưng vai trò rỗng ở job/console không còn thấy toàn bộ roster. (2) Thêm test `test_every_module_with_controllers_is_guarded` (đột biến "quên thêm module vào `MODULES`" sống sót) và test `lockForUpdate`/listener `afterCommit` (hai đột biến sống sót khác). (3) `routes/api.php` và `bootstrap/providers.php` đã lỗi Pint từ `main` (viết tên class đầy đủ không import); chỉ sửa đúng các dòng liên quan, không định dạng lại.
- **Kết quả:** 983 test pass (814 + 169); 84 đột biến: 83 đỏ, 1 tương đương (bỏ `->value` ở Resource, JSON của backed Enum không đổi). Bảng chi tiết ở báo cáo Task 9a.
