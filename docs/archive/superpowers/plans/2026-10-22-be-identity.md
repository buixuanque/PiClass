# Task 9g — Refactor module `Identity` (Auth) sang quy ước module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** 7 controller của `Auth` (Login, Logout, Me, Context, ChangePassword, ForgotPassword, ResetPassword) chuyển sang `app/Modules/Identity/` theo `piclass-api/CLAUDE.md` (FormRequest + Action/Query + Resource), kèm ba middleware (`EnsureRole`, `EnsureUserIsActive`, `LoadActiveRole`), Enum `RoleCode` thay literal role code, và Action `CreateInvitedUser` để `UserManagement` hết ghi thẳng vào `users`. **Không đổi hành vi session/Sanctum/bảo mật.**

**Architecture:** `app/Modules/Identity/{Http/{Controllers,Requests,Resources,Middleware},Actions,Queries,Exceptions,Enums}`. Không có Policy (auth endpoint dùng middleware). `ActiveRole` giữ ở `app/Support` (D3). Không cần ServiceProvider riêng (không có Policy/binding).

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, Sanctum (cookie session), PHPUnit 12, Pint. Container `piclass-local-api-1`.

**Spec / nguồn:** `piclass-api/CLAUDE.md`; `docs/architecture/backend-refactor-playbook.md`; `docs/architecture/authentication-and-security.md`; `ARCH-04/05/11/21` ở `docs/architecture/open-decisions.md`; `docs/architecture/model-ownership.md` (dòng `Role`, `User`, `UserRole`); `BR-USER-002/007/008` ở `docs/requirements/user-rules.md`. Plan mẫu: `docs/archive/superpowers/plans/2026-10-21-be-academic.md`.

---

## Quy tắc cho người thực thi (đọc trước khi làm bất cứ gì)

1. **Điểm mơ hồ nhỏ, không đổi hành vi/bảo mật:** chọn phương án đơn giản nhất, ghi một dòng `Ruling:` vào ledger. **Điểm đổi hành vi, bảo mật hoặc cần quyết định không có trong plan: DỪNG task đó** và ghi vào ledger (khi chạy không có người, chuyển sang phần việc độc lập kế tiếp, không làm tiếp trên nền hỏng).
2. Các tình huống bắt buộc DỪNG:
   - Test đặc tả (Task 1) **fail trên code cũ**: không sửa kỳ vọng cho khớp code, không sửa code. Ngoại lệ: thăm dò 500 (quy tắc 8).
   - Test cũ fail sau khi đổi code vì **hành vi khác**. Chỉ được sửa test cũ khi đổi namespace/đường dẫn hoặc kiểu so sánh Enum.
   - Một thay đổi làm đổi: HTTP status, `error.code`, `error.message`, tập key JSON, tên/thuộc tính cookie, thời điểm `session()->regenerate()/invalidate()`, thứ tự middleware.
   - Rule kiến trúc báo vi phạm ở file plan không nhắc tới; Pint báo lỗi ở file không sửa; container không chạy.
3. Không đụng file ngoài "Danh sách file được phép đụng". Không sửa migration, factory, seeder, `AuditLogger`, `NotificationDispatcher`.
4. Trong test, giá trị kỳ vọng viết **chuỗi literal** (`'ROLE_NOT_HELD'`, `'admin'`...), không dùng case Enum. Ngoại lệ: test của chính Enum.
5. Mỗi task kết thúc bằng commit local (message **tiếng Anh**, kết thúc `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`). Branch BE `refactor/identity`, branch docs `docs/be-identity`, cả hai tạo từ `origin/main` đã `git fetch`. **User đã cho phép push → PR → merge** cho task này (2026-10-22): sau khi Task 9 xong và Opus review sạch, chạy skill `ship-prs`. PR tiêu đề/mô tả **tiếng Việt**. Không xóa branch, không force push.
6. Không dùng git worktree. Thử đột biến chỉ trên **bản sao** `/tmp/mut` trong container, không chạy song song với full suite.
7. Comment code và mô tả test **tiếng Việt**; tên biến/hàm/class/test method và `error.code` tiếng Anh. Comment tiếng Anh cũ trong file đã sửa thì dịch (kể cả `LoadActiveRole`, `ActiveRole`).
8. **Thăm dò 500:** thử `login_identifier`/`password` kiểu mảng, `role_code` kiểu mảng, `token` kiểu mảng, body rỗng, `remember` không phải boolean. Nếu 500: không ghim, không sửa, bỏ khỏi test, ghi vào bảng nợ ở playbook.
9. **Thứ tự kiểm tra từng endpoint chép đúng code cũ** (validate → throttle → xác thực...; xem "Bảng endpoint"). Không "sửa cho hợp lý".
10. **Giữ nguyên message tiếng Anh/Việt hiện có** (issue #43). Chép nguyên từng chữ.

## Global Constraints

- Giữ nguyên: HTTP status, `error.code`, `error.message`, tập key JSON, cookie (`laravel_session`/`remember_web_*`), việc ghi bảng `sessions` và `users.last_login_at`/`remember_token`.
- Baseline: **1929 test pass** (main sau PR #28–#32; chạy full suite ở bước Chuẩn bị, nếu khác thì dùng số đo được). Mọi task kết thúc 0 fail, số test = baseline + test mới.
- Mọi case 4xx trong test mới assert **cả** `error.code` lẫn `error.message` (hoặc key lỗi validate).
- Không thêm migration, không đổi `config/`, `.env*`.

## Quyết định đã chốt

| # | Quyết định | Lý do |
|---|---|---|
| D1 | Identity có Action `CreateInvitedUser`; `InvitePersonProfile` gọi qua DI, giữ transaction + kiểm tra `ProfileAlreadyInvited` + tạo token + gửi mail ở UserManagement. Xóa mục `KNOWN_VIOLATIONS` của file này. | User chốt 2026-10-22 (câu 1). |
| D2 | `Role` (danh mục, chỉ đọc) thuộc Identity; `UserRole` vẫn do UserManagement ghi. Cập nhật `model-ownership.md` (bỏ `[CẦN XÁC NHẬN]`). | User chốt (câu 2). |
| D3 | Chuyển 3 middleware vào `Modules/Identity/Http/Middleware`; `ActiveRole` giữ `app/Support`. Cập nhật `bootstrap/app.php`, `routes/api.php`, test, comment `ActiveRole`. Ghi vào `ARCH-21`. | User chốt (câu 3). |
| D4 | 4 điểm code lệch tài liệu **giữ nguyên** trong 9g, đã có issue: #40 (remember-me 400 ngày, phải 30), #41 (throttle không tăng dần), #42 (quên mật khẩu không giới hạn tần suất), #43 (message lẫn ngôn ngữ). Ghi `ARCH-29`. | User chốt (câu 4). |
| D5 | `RoleCode` Enum (`Admin`, `Teacher`, `Student`, `Parent`) ở `Modules/Identity/Enums`. Thay literal ở: `ActiveRole::isAdmin`, `User` (`anotherActiveAdminExists`), `SetContextRequest` (`Rule::enum`), UserManagement (`AssignUserRole`, `EndUserRole`, `MyProfileQuery`), Academic (`CoursePolicy`, `ListCoursesQuery`, `ListCourseEnrollmentsQuery`, `CreateCourseRequest`). **Không** đổi literal ở module khác (Attendance, Assessment, Lesson, File, Audit, `ClassScope`): ghi vào bảng nợ. `Rule::enum` phải cho cùng message validate như `in:` (test ghim). | Gộp hai dòng nợ playbook; phạm vi vừa phải. |
| D6 | `ContextController` gọi `ActiveRole::set($roleCode)` cùng lúc `session()->put` (nợ playbook dòng 80). | Nợ playbook. |
| D7 | Cách chạy: user cho phép push/PR/merge (2026-10-22). Dừng toàn chuỗi 9g→9i nếu có test đỏ không sửa được hoặc review có Critical không xử lý nổi. | User chốt (câu 5). |

## Bảng endpoint (7)

| Endpoint | Controller cũ | Middleware | Thứ tự kiểm tra hiện tại | Kết quả |
|---|---|---|---|---|
| `POST /auth/login` | `LoginController` | không auth | validate → throttle (429 `TOO_MANY_ATTEMPTS`) → tra User → sai/inactive: `RateLimiter::hit` + 422 `login_identifier` | `Auth::login($user, remember)`, `session()->regenerate()`, `last_login_at`; JSON `{user:{id,login_identifier}, available_roles:[codes]}` |
| `POST /auth/password/forgot` | `ForgotPasswordController` | không auth | validate → `sendResetLink` | luôn 202 `[]` (bỏ qua kết quả broker) |
| `POST /auth/password/reset` | `ResetPasswordController` | không auth | validate (`password` min 8) → broker `reset` | 200 `[]`; thất bại 422 `token`; thành công xóa **mọi** hàng `sessions` của user, đổi `remember_token` |
| `POST /auth/logout` | `LogoutController` | `auth:sanctum`, `EnsureUserIsActive` | — | 204; guard `web` logout, `invalidate()`, `regenerateToken()`, `Auth::shouldUse('web')` |
| `GET /auth/me` | `MeController` | như trên | — | JSON `{user, active_role (session), available_roles}` |
| `POST /auth/context` | `ContextController` | như trên | validate (`role_code` in 4 role) → `hasActiveRole` (403 `ROLE_NOT_HELD`) | ghi session `active_role_code`; JSON `{active_role}` |
| `PATCH /me/password` | `ChangePasswordController` | như trên | validate (`current_password`, `new_password` min 8) → `Hash::check` (422 `current_password`) | xóa hàng `sessions` khác session hiện tại, đổi password + `remember_token`; 200 `[]` |

`available_roles` (Login và Me) dùng chung một truy vấn → Query `ListAvailableRolesQuery` (lọc `valid_to` null hoặc tương lai; **không** lọc `valid_from`, chép đúng).

### Danh sách Enum
| Enum | Case |
|---|---|
| `RoleCode` (mới) | `Admin='admin'`, `Teacher='teacher'`, `Student='student'`, `Parent='parent'`; test đối chiếu với bảng `roles` (seed) |
| `UserStatus` (có sẵn) | giữ nguyên |

## Bảng ảnh hưởng

Trước khi sửa, chạy `grep -rn "'admin'\|'teacher'\|'student'\|'parent'" app` và `grep -rn "Http\\\\Middleware" app routes bootstrap tests` rồi đối chiếu với D3/D5. File ngoài danh sách D3/D5 mà dính literal role code → ghi vào bảng nợ, không sửa.

## Review Focus (cho Opus)

1. Bảo mật: `session()->regenerate()` sau `Auth::login`; logout `invalidate` + `regenerateToken` + `shouldUse('web')`; đổi/đặt lại mật khẩu xóa đúng các session (đổi: giữ session hiện tại; reset: xóa hết).
2. Không lộ tài khoản tồn tại: quên mật khẩu luôn 202; đăng nhập sai và tài khoản `inactive` cùng message, cùng `RateLimiter::hit`; throttle key `lower(identifier)|ip`, `clear` sau đăng nhập đúng.
3. Tài khoản bị vô hiệu hóa giữa phiên bị chặn 403 `ACCOUNT_INACTIVE` ở mọi route trong nhóm `EnsureUserIsActive`; `EnsureRole` kiểm tra lại `hasActiveRole` mỗi request.
4. `LoadActiveRole` luôn ghi đè (kể cả `null`) nên không rò vai trò giữa các request; `ContextController` cập nhật cả `ActiveRole`.
5. `CreateInvitedUser`: không đổi mật khẩu ngẫu nhiên 32 ký tự, `status = active`, vẫn trong cùng transaction với kiểm tra trùng và tạo token; lỗi gửi mail không hủy tài khoản.
6. `RoleCode`: không literal role code còn sót ở các file thuộc D5; `Rule::enum` không đổi message validate.

## Danh sách file được phép đụng

- Tạo: `app/Modules/Identity/{Http/Controllers/*,Http/Requests/*,Http/Resources/*,Http/Middleware/*,Actions/*,Queries/ListAvailableRolesQuery.php,Exceptions/*,Enums/RoleCode.php}`; test mới trong `tests/Feature/Identity/`, `tests/Unit/Modules/Identity/`.
- Xóa (sau khi chuyển): 7 controller ở `app/Http/Controllers/Api/Auth/`, 2 request ở `app/Http/Requests/Auth/`, 3 middleware ở `app/Http/Middleware/`.
- Sửa: `routes/api.php`, `bootstrap/app.php`, `app/Models/User.php` (literal `'admin'`), `app/Support/ActiveRole.php` (`isAdmin`, comment), `tests/Feature/Auth/*` (chỉ namespace/import), `tests/Feature/Http/LoadActiveRoleMiddlewareTest.php`, `tests/Architecture/ArchitectureTest.php`, `tests/Feature/Models/EnumCastsTest.php` (nếu cần).
- Sửa (chỉ literal role code hoặc gọi Action, D1/D5): `UserManagement/Actions/{InvitePersonProfile,AssignUserRole,EndUserRole}`, `UserManagement/Queries/MyProfileQuery`, `Academic/{Policies/CoursePolicy,Queries/ListCoursesQuery,Queries/ListCourseEnrollmentsQuery,Http/Requests/CreateCourseRequest}`.
- Docs (Task 9): `docs/architecture/{open-decisions,model-ownership,backend-refactor-playbook}.md`, `docs/progress.md`, bảng master plan, `piclass-api/CLAUDE.md` (liệt kê `RoleCode`).

## Chuẩn bị (trước Task 1)

- [ ] `git fetch origin`; BE: `git checkout -b refactor/identity origin/main`; docs: `git checkout -b docs/be-identity origin/main`.
- [ ] Tạo ledger: `scripts/sdd-workspace docs/archive/superpowers/plans/2026-10-22-be-identity.md`.
- [ ] Container chạy; full suite xanh, ghi baseline vào ledger.

### Task 1: Test đặc tả hành vi trên code cũ

Viết trong `tests/Feature/Identity/IdentityBehaviorTest.php` (+ `IdentityBoundaryCasesTest.php`) **chỉ những ca chưa có** trong 14 file `tests/Feature/Auth` (đọc chúng trước, ghi danh sách phủ vào ledger). Chạy trên code cũ phải xanh. Các ca bắt buộc:

- Login: key JSON chính xác (`user`, `available_roles`; `user` có đúng `id`, `login_identifier`); `available_roles` loại role đã kết thúc và giữ role `valid_to` tương lai; `last_login_at` được ghi; session id đổi sau đăng nhập (regenerate); `remember=false` không có cookie `remember_web_*`; sai mật khẩu và tài khoản không tồn tại và `inactive` **cùng** message `Thông tin đăng nhập không đúng.` và cùng key `login_identifier`; throttle key không phân biệt hoa/thường; đăng nhập đúng `clear` bộ đếm; 429 có `error.code=TOO_MANY_ATTEMPTS` và message chứa số giây; lần thứ 5 vẫn là 422, lần thứ 6 là 429; thiếu `login_identifier`/`password` là 422.
- Me: `active_role` là `null` khi chưa chọn; có giá trị sau `/auth/context`; 401 khi chưa đăng nhập.
- Context: `role_code` ngoài 4 role là 422 (message validate chính xác của `in:`); kiểu mảng là 422; thiếu là 422; role đang giữ trả `{active_role}`; role chưa giữ 403 `ROLE_NOT_HELD` với message tiếng Anh nguyên văn.
- ChangePassword: `new_password` ngắn hơn 8 là 422 trước khi kiểm tra mật khẩu hiện tại; mật khẩu mới trùng mật khẩu cũ **được chấp nhận** (ghim); đổi xong `remember_token` khác trước; session khác bị xóa, session hiện tại còn.
- Forgot: thiếu `login_identifier` là 422; tài khoản `inactive` vẫn 202; luôn body `[]`.
- Reset: token sai/hết hạn 422 key `token` message nguyên văn; thành công xóa mọi session; mật khẩu mới đăng nhập được, mật khẩu cũ không.
- Logout: sau logout request kế tiếp là 401; session cũ không dùng lại được.
- Middleware: `EnsureRole` 403 khi không có `active_role_code`, khi role không nằm trong danh sách, khi role đã bị kết thúc giữa phiên; `EnsureUserIsActive` 403 `ACCOUNT_INACTIVE` trên cả 4 route auth có bảo vệ; `LoadActiveRole` ghi đè `null` khi không có session.
- Thăm dò 500 (quy tắc 8).

- [ ] Viết test, chạy trên code cũ: tất cả xanh. Commit `test: pin the Identity behavior before the module refactor`.

### Task 2: `RoleCode` Enum và thay literal (D5)

- [ ] RED: `tests/Unit/Modules/Identity/Enums/RoleCodeTest` (4 case, giá trị chuỗi, đối chiếu với `Role::pluck('code')` của seed); test architecture/message validate cho `SetContextRequest` và `AssignUserRole` không đổi message.
- [ ] Tạo `RoleCode`; thay literal theo D5; `Rule::enum(RoleCode::class)` thay `in:` (kiểm tra message giống hệt); `ActiveRole::isAdmin` so với `RoleCode::Admin->value`.
- [ ] `ArchitectureTest`: thêm `RoleCode` vào `SHARED_ENUMS` cho `UserManagement` và `Academic` (và `'Identity'` vào `MODULES` ở Task 6).
- [ ] Full suite xanh, commit.

### Task 3: Hạ tầng Identity — Query, Action, Exception, Middleware

- [ ] `ListAvailableRolesQuery` (chép truy vấn `available_roles`).
- [ ] Exception: `TooManyLoginAttempts` (429, `TOO_MANY_ATTEMPTS`, message có `{$seconds}`), `RoleNotHeld` (403, `ROLE_NOT_HELD`); `InvalidCredentials` giữ `ValidationException::withMessages` (không đổi status 422/shape).
- [ ] Action: `AuthenticateUser` (throttle + tra + kiểm tra + `hit`/`clear`, trả `User`), `ChangePassword` (nhận `User`, mật khẩu, `keepSessionId`), `SendPasswordResetLink`, `ResetPassword`, `CreateInvitedUser`. Đăng nhập/regenerate/`last_login_at` ở controller hay Action: chép đúng thứ tự cũ (`Auth::login` → `regenerate()` → `last_login_at`).
- [ ] Chuyển 3 middleware sang `Modules/Identity/Http/Middleware` (đổi namespace, dịch comment), cập nhật `bootstrap/app.php`, `routes/api.php`, test import.
- [ ] Test Action gọi trực tiếp; full suite xanh; commit.

### Task 4: Chuyển 7 endpoint

- [ ] Controller mỏng gọi Request → Action/Query → Resource; Request giữ rule cũ nguyên văn (`LoginRequest`, `SetContextRequest`, thêm `ForgotPasswordRequest`, `ResetPasswordRequest`, `ChangePasswordRequest` với đúng rule đang `$request->validate`). `ContextController` gọi `ActiveRole::set` (D6).
- [ ] Cập nhật `routes/api.php`; xóa file cũ. Task 1 test vẫn xanh, không sửa kỳ vọng.
- [ ] Commit.

### Task 5: `CreateInvitedUser` cho `InvitePersonProfile` (D1)

- [ ] RED: test `InvitePersonProfile` hiện có vẫn xanh; test mới: `CreateInvitedUser` tạo User `active`, mật khẩu băm, `person_profile_id`, `login_identifier`; `InvitePersonProfile` không còn `User::create`.
- [ ] Sửa `InvitePersonProfile` gọi `CreateInvitedUser` trong transaction hiện có; xóa mục `KNOWN_VIOLATIONS`; commit.

### Task 6: Rule kiến trúc

- [ ] Thêm `'Identity'` vào `MODULES`; xóa `KNOWN_VIOLATIONS` của `InvitePersonProfile`; `SHARED_ENUMS` cho `RoleCode`; không còn `App\Http\Middleware` cho 3 class đã chuyển; test_modules guard xanh.
- [ ] Commit.

### Task 7: Test bổ sung và ca biên

- [ ] Action gọi trực tiếp (`AuthenticateUser` clear/hit, `ChangePassword` giữ đúng session, `ResetPassword` xóa tất cả); `ListAvailableRolesQuery` (role `valid_to` đúng lúc `now()` bị loại); `ContextController` cập nhật `ActiveRole` trong cùng request.
- [ ] Full suite xanh; Pint `--test` chỉ trên file đã đổi; commit.

### Task 8: Thử đột biến (bắt buộc) — bản sao `/tmp/mut` trong container

Đột biến tối thiểu (mỗi cái phải làm ít nhất một test đỏ): bỏ `regenerate()`; bỏ `RateLimiter::hit`; bỏ `RateLimiter::clear`; bỏ điều kiện `status !== Active` ở đăng nhập; bỏ xóa session khác khi đổi mật khẩu; đổi `!=` thành `==` ở session giữ lại; reset không xóa session; reset không đổi `remember_token`; quên mật khẩu trả 404 cho tài khoản lạ; bỏ `invalidate()` hoặc `regenerateToken()` ở logout; `EnsureRole` bỏ `hasActiveRole`; `EnsureUserIsActive` cho qua `inactive`; `LoadActiveRole` bỏ ghi đè `null`; `available_roles` bỏ lọc `valid_to`; `Context` bỏ `hasActiveRole`; `CreateInvitedUser` đổi status; `RoleCode` đổi một giá trị. Mutant sống → thêm test kiến nó chết (tên test mô tả điều kiện), ghi vào ledger.

- [ ] Chạy, xử lý, commit.

### Task 9: Review Opus, tài liệu, ship

- [ ] Review toàn branch bằng subagent model `opus` (gói `review-package`, Review Focus ở trên). Critical/Important: sửa theo TDD một lượt; Minor: ghi `ARCH-29`.
- [ ] Docs trên branch `docs/be-identity`: `ARCH-29` (quyết định D1–D7, bốn lệch D4 kèm #40–#43, Minor của review), `ARCH-21` (middleware đã chuyển), `model-ownership.md` (`Role` → Identity, bỏ `[CẦN XÁC NHẬN]`), playbook (xóa 3 dòng nợ đã xong, thêm nợ literal role code ở module khác và bẫy mới nếu có), `progress.md`, bảng master plan (9g Xong), `piclass-api/CLAUDE.md`.
- [ ] Chạy skill `ship-prs`: push, PR tiếng Việt, merge BE trước rồi docs, điền số PR; `main` local cập nhật.

## Tiêu chí xong

- 7 controller + 3 middleware nằm trong `app/Modules/Identity`; thư mục cũ không còn file.
- Task 1 chạy xanh trên cả code cũ và code mới, không sửa kỳ vọng.
- Full suite xanh (baseline + test mới), rule kiến trúc xanh, `KNOWN_VIOLATIONS` rỗng.
- Không còn literal role code ở các file thuộc D5.
- Mutant đều chết; Opus review không còn Critical/Important.
- Docs đồng bộ; hai PR đã merge; issue #40–#43 vẫn mở để làm riêng.

## Ghi chú thực thi (điền sau khi xong)

- Thực thi 2026-10-22 inline (executing-plans), branch BE `refactor/identity`. Task 1: 38 test đặc tả xanh trên code cũ (bỏ khẳng định "request sau logout trả 401" vì client test giữ guard trong cùng tiến trình; thay bằng đổi session id + `assertGuest('web')`). Task 2: R8 báo thêm 14 literal khi `RoleCode` vào `SHARED_ENUMS`; thay 5 literal thật ở UserManagement, thêm 8 mục `KNOWN_LITERALS` cho khóa bọc JSON/route parameter. Task 4: R7 miễn tầng Http của Identity. Task 8: 58 đột biến; lần chạy đầu bộ chạy đếm nhầm mọi đột biến là bị diệt (tên test chứa chữ "failed"), đã sửa và kiểm chứng bằng chạy bản gốc; 11 sống sót, 10 bị diệt sau khi thêm test, 1 tương đương. Task 9: Opus review 0 Critical, 0 Important, 7 Minor ghi ở `ARCH-29(e)`.
- Rulings: (1) R7 miễn tầng Http của Identity (rủi ro: so `'/Http/'` trên đường dẫn tuyệt đối, ghi ở Minor); (2) thay literal role code ở 5 chỗ ngoài danh sách D5 của UserManagement và thêm `KNOWN_LITERALS` vì R8 báo khi `RoleCode` vào `SHARED_ENUMS`; (3) test Action viết sau khi chép logic từ controller cũ (hành vi đã ghim trước ở Task 1; Action test được kiểm chứng bằng đột biến); (4) đột biến `! $activeRole` ở `EnsureRole` là tương đương, để sống.
