# BE: Thêm hồ sơ kèm tài khoản + đặt lại mật khẩu — Kế hoạch triển khai

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `POST /students|parents|teachers` tạo luôn tài khoản (tên đăng nhập + mật khẩu), có cờ "cần đổi mật khẩu lần đầu", và có endpoint đặt lại mật khẩu cho Admin/Teacher.

**Architecture:** Identity sở hữu `User` nên mọi ghi vào `users` đi qua Action của Identity (`CreateUserWithPassword`, `SetAccountPassword`); UserManagement điều phối (hồ sơ + tài khoản + vai trò + audit trong một transaction). Phiên đăng nhập là session cookie lưu bảng `sessions` (không phải token), nên "thu hồi phiên" = xóa dòng `sessions` của tài khoản đích. Luồng Mời cũ (`InvitePersonProfile`, `RegenerateInvitation`) không đổi.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL; quy ước ở `piclass-api/CLAUDE.md` và `docs/architecture/backend-gotchas.md`.

**Spec:** `docs/superpowers/specs/2026-10-26-create-profile-with-account-design.md` (`DEC-USR-021/022`).

## Global Constraints

- Tạo nhánh mới từ `origin/main` sau `git fetch origin` (nhánh hiện tại của `piclass-api` đang là nhánh việc khác). Không push/PR khi chưa được user bảo.
- Comment trong code và mô tả test bằng **tiếng Việt**; tên class/method/test method/`error.code`/commit message bằng tiếng Anh.
- Mật khẩu tối thiểu 8 ký tự (`ARCH-05`, cùng rule `ChangePasswordRequest`). Mật khẩu rõ **không** xuất hiện trong response, audit, log.
- Tên đăng nhập: `required|string`, duy nhất ở `users.login_identifier` (như `InvitePersonProfileRequest`); độc lập với email hồ sơ.
- `account` **bắt buộc** ở API tạo hồ sơ. Cờ mặc định `false`; chỉ bật khi mật khẩu do người khác đặt (lúc thêm, hoặc đặt lại). Tài khoản cũ và tài khoản qua luồng Mời không bật cờ, không backfill.
- Teacher: tạo được hồ sơ **Student** (kèm tài khoản) và đặt lại mật khẩu **chỉ tài khoản Student**, không giới hạn lớp. Parent/Teacher: chỉ Admin tạo. Đặt lại mật khẩu mọi loại: Admin.
- Audit đặt lại mật khẩu: action `user.password_reset`, ghi trong cùng transaction, **không** chứa mật khẩu.
- Mã lỗi mới phải thêm vào `docs/architecture/error-codes.md` trong cùng PR.
- OpenAPI sinh tự động bởi Scramble: không sửa tay; `OpenApiSpecTest` phải xanh.

## Review Focus

- Tên đăng nhập trùng hoặc mật khẩu < 8 ký tự: 422 và **không** còn hồ sơ/tài khoản mồ côi (rollback toàn bộ).
- Teacher gọi `POST /parents`, `POST /teachers` → 403; Teacher đặt lại mật khẩu của Teacher/Parent/Admin → 403.
- Đặt lại mật khẩu cho hồ sơ chưa có tài khoản → 409, không tạo gì.
- Đặt lại mật khẩu cho tài khoản mời chưa kích hoạt: liên kết mời cũ hết tác dụng, không đặt đè được.
- Audit và mọi response không chứa mật khẩu rõ.
- Bỏ qua lời nhắc khi cờ đã `false`: idempotent, không lỗi.
- Tài khoản tạo qua luồng Mời có `must_change_password = false`.

## Cấu trúc file

| File | Loại | Việc |
| --- | --- | --- |
| `database/migrations/2026_10_27_000001_add_must_change_password_to_users_table.php` | Tạo | cột cờ |
| `app/Models/User.php` | Sửa | fillable + cast boolean |
| `app/Modules/Identity/Actions/CreateUserWithPassword.php` | Tạo | tạo User `active` với mật khẩu cho trước, bật cờ |
| `app/Modules/Identity/Actions/SetAccountPassword.php` | Tạo | đặt mật khẩu hộ + bật cờ + thu hồi phiên/token |
| `app/Modules/Identity/Actions/DismissPasswordChangePrompt.php` | Tạo | tắt cờ khi Bỏ qua |
| `app/Modules/Identity/Actions/ChangePassword.php` | Sửa | tắt cờ khi tự đổi |
| `app/Modules/Identity/Http/{Controllers,Requests}/DismissPasswordChange*` | Tạo | `POST /me/password/dismiss-prompt` |
| `app/Modules/Identity/Http/Resources/AuthenticatedUserResource.php` | Sửa | thêm `must_change_password` |
| `app/Modules/UserManagement/Actions/CreateProfileAccount.php` | Tạo | tài khoản + vai trò cho hồ sơ vừa tạo |
| `app/Modules/UserManagement/Actions/Create{Student,Parent,Teacher}.php` | Sửa | nhận `account` + actor |
| `app/Modules/UserManagement/Actions/ResetProfilePassword.php` | Tạo | use case đặt lại mật khẩu + audit |
| `app/Modules/UserManagement/Policies/PersonProfilePolicy.php` | Tạo | `resetPassword` |
| `app/Modules/UserManagement/Exceptions/ProfileHasNoAccount.php` | Tạo | 409 `NO_ACCOUNT` |
| `app/Modules/UserManagement/Http/Requests/Concerns/AccountRules.php` | Tạo | rule `account.*` dùng chung 3 request |
| `app/Modules/UserManagement/Http/Requests/ResetProfilePasswordRequest.php`, `.../Controllers/ResetProfilePasswordController.php` | Tạo | endpoint đặt lại |
| `routes/api.php`, `.../UserManagementServiceProvider.php` | Sửa | route + đăng ký Policy |

---

### Task 1: Cột cờ `must_change_password`

**Files:**
- Create: `database/migrations/2026_10_27_000001_add_must_change_password_to_users_table.php`
- Modify: `app/Models/User.php` (fillable, casts)
- Test: `tests/Feature/Models/UserMustChangePasswordTest.php`

**Interfaces:**
- Produces: `User::$must_change_password` (bool), fillable, mặc định `false` ở DB.

- [ ] **Step 1: Viết test fail** — `test_new_user_defaults_to_not_requiring_a_password_change` (`User::factory()->create()->fresh()->must_change_password` là `false`) và `test_flag_is_cast_to_boolean_and_fillable` (tạo với `'must_change_password' => true` thì `assertTrue`).
- [ ] **Step 2: Chạy** `php artisan test --filter=UserMustChangePasswordTest` → FAIL (thiếu cột).
- [ ] **Step 3: Migration** thêm `boolean('must_change_password')->default(false)` sau cột `status`, có `down()`; thêm vào `$fillable` và `$casts` của `User`.
- [ ] **Step 4: Chạy lại** → PASS.
- [ ] **Step 5: Commit** `feat: add must_change_password flag to users`.

### Task 2: Identity Actions — tạo User có mật khẩu, đặt mật khẩu hộ, tắt cờ

**Files:**
- Create: `app/Modules/Identity/Actions/CreateUserWithPassword.php`, `SetAccountPassword.php`, `DismissPasswordChangePrompt.php`
- Modify: `app/Modules/Identity/Actions/ChangePassword.php`
- Test: `tests/Feature/Identity/AccountPasswordActionsTest.php`

**Interfaces:**
- Produces:
  - `CreateUserWithPassword::handle(string $personProfileId, string $loginIdentifier, string $password): User` — `status = active`, `must_change_password = true`.
  - `SetAccountPassword::handle(User $target, string $newPassword): void` — đặt mật khẩu, `must_change_password = true`, `remember_token` mới, xóa mọi dòng `sessions` của `$target`, xóa `$target->tokens()`, xóa token lời mời chưa dùng (`PasswordBrokerFactory` broker `invitations` → `deleteToken($target)`).
  - `DismissPasswordChangePrompt::handle(User $user): void` — `must_change_password = false`, idempotent.
  - `ChangePassword::handle(...)` giữ nguyên chữ ký, thêm `must_change_password = false` khi đổi thành công.

- [ ] **Step 1: Viết test fail**, mỗi test một hành vi:
  - `test_create_user_with_password_sets_flag_and_hashes_password` (đăng nhập được bằng mật khẩu đã đặt, cờ `true`, `status` active).
  - `test_set_account_password_revokes_sessions_and_invitation_token` (tạo vài dòng `sessions` cho user đích và một user khác; token `invitations` của user đích; sau khi gọi: dòng của user đích mất, dòng user khác còn, `broker('invitations')->tokenExists($target)` là `false`, mật khẩu mới dùng được, cờ `true`).
  - `test_dismiss_prompt_clears_flag_and_is_idempotent`.
  - `test_change_password_clears_flag` (user có cờ `true`, đổi mật khẩu đúng → cờ `false`).
- [ ] **Step 2: Chạy** → FAIL.
- [ ] **Step 3: Implement** theo chữ ký trên; `SetAccountPassword` chạy trong `DB::transaction`; `CreateUserWithPassword` đứng cạnh `CreateInvitedUser` (không sửa `CreateInvitedUser`).
- [ ] **Step 4: Chạy** → PASS, và `php artisan test tests/Feature/Identity` vẫn xanh.
- [ ] **Step 5: Commit** `feat: add Identity actions for password-bearing accounts`.

### Task 3: Phơi cờ ra API và endpoint "Bỏ qua lời nhắc"

**Files:**
- Modify: `app/Modules/Identity/Http/Resources/AuthenticatedUserResource.php`, `routes/api.php`
- Create: `app/Modules/Identity/Http/Controllers/DismissPasswordChangePromptController.php`
- Test: `tests/Feature/Identity/PasswordChangePromptTest.php` (và sửa các test cũ đang assert đúng-key của khối `user` ở Login/Me nếu có)

**Interfaces:**
- Consumes: `DismissPasswordChangePrompt::handle(User)` (Task 2).
- Produces: khối `user` của `POST /auth/login` và `GET /auth/me` thành `{id, login_identifier, must_change_password: bool}`; `POST /api/v1/me/password/dismiss-prompt` (trong nhóm `auth:sanctum`) → 204.

- [ ] **Step 1: Viết test fail:** `test_me_returns_must_change_password_flag`; `test_login_returns_the_flag`; `test_dismiss_prompt_clears_flag_and_returns_204`; `test_dismiss_prompt_requires_authentication` (401).
- [ ] **Step 2: Chạy** → FAIL.
- [ ] **Step 3: Implement** resource, controller mỏng (≤10 dòng, `response()->noContent()`), route cạnh `PATCH /me/password`.
- [ ] **Step 4: Chạy** `php artisan test tests/Feature/Identity tests/Feature/Auth` → PASS.
- [ ] **Step 5: Commit** `feat: expose must_change_password and dismiss prompt endpoint`.

### Task 4: Tạo hồ sơ kèm tài khoản (Student/Parent/Teacher)

**Files:**
- Create: `app/Modules/UserManagement/Actions/CreateProfileAccount.php`, `app/Modules/UserManagement/Http/Requests/Concerns/AccountRules.php`
- Modify: `Create{Student,Parent,Teacher}.php`, `Create{Student,Parent,Teacher}Request.php`, `Create{Student,Parent,Teacher}Controller.php`, `routes/api.php`, và các test hiện có gọi `POST /students|parents|teachers` (`tests/Feature/UserManagement/CreateRoleProfileTest.php`, `RoleProfileBehaviorTest.php`, và nơi khác `grep` ra) thêm `account`
- Test: `tests/Feature/UserManagement/CreateProfileWithAccountTest.php`

**Interfaces:**
- Consumes: `CreateUserWithPassword::handle(...)` (Task 2); `AssignUserRole::handle(User $target, User $actor, string $roleCode)`; `RoleCode::{Student,Parent,Teacher}`.
- Produces:
  - `CreateProfileAccount::handle(PersonProfile $profile, array $account, User $actor, RoleCode $role): User` — tạo User (qua Identity) rồi gán vai trò (qua `AssignUserRole`), tự mở transaction.
  - `AccountRules::accountRules(): array` — `account` required array; `account.login_identifier` required string unique:users,login_identifier; `account.password` required string min:8.
  - `Create{Student,Parent,Teacher}::handle(array $data, User $actor)` — `$data['account']` bắt buộc; trong cùng transaction với hồ sơ; trả model với relation `user` đã set.
- Route: `POST /students` chuyển sang nhóm `role:admin,teacher`; `POST /parents`, `POST /teachers` giữ `role:admin`.
- Response: các Resource hiện có, khối `account` giờ có giá trị (không trả mật khẩu); controller bỏ `setRelation('user', null)`.

- [ ] **Step 1: Viết test fail** (`CreateProfileWithAccountTest`):
  - `test_admin_creates_student_with_account_and_role` (201; `users` có `login_identifier`, `status = active`, `must_change_password = true`; có `user_roles` role `student`; response `student.account.login_identifier`; response JSON không chứa chuỗi mật khẩu).
  - tương tự `test_admin_creates_parent_with_account_and_role`, `test_admin_creates_teacher_with_account_and_role`.
  - `test_teacher_can_create_student_with_account`; `test_teacher_cannot_create_parent`; `test_teacher_cannot_create_teacher` (403).
  - `test_duplicate_login_identifier_returns_422_and_creates_no_profile` (đếm `person_profiles`, `students` không đổi).
  - `test_short_password_returns_422_and_creates_no_profile`; `test_missing_account_returns_422`.
  - `test_failure_assigning_role_rolls_back_profile_and_user` (ví dụ không seed role `student` → lỗi → không còn hồ sơ/user).
  - `test_created_account_can_log_in_with_given_password`.
- [ ] **Step 2: Chạy** → FAIL.
- [ ] **Step 3: Implement** các Interfaces trên. `AccountRules` là trait để cả 3 Request dùng chung. Rà `grep -rn "CreateStudent\|CreateParent\|CreateTeacher\|'/students'\|/parents\|/teachers" app tests database` để sửa mọi caller còn gọi theo chữ ký cũ.
- [ ] **Step 4: Chạy** `php artisan test tests/Feature/UserManagement` rồi toàn bộ `php artisan test` → PASS (mọi test cũ gọi endpoint tạo đã thêm `account`).
- [ ] **Step 5: Commit** `feat: create account together with student/parent/teacher profile`.

### Task 5: Đặt lại mật khẩu cho một hồ sơ

**Files:**
- Create: `app/Modules/UserManagement/Actions/ResetProfilePassword.php`, `Policies/PersonProfilePolicy.php`, `Exceptions/ProfileHasNoAccount.php`, `Http/Requests/ResetProfilePasswordRequest.php`, `Http/Controllers/ResetProfilePasswordController.php`
- Modify: `UserManagementServiceProvider.php` (`Gate::policy(PersonProfile::class, PersonProfilePolicy::class)`), `routes/api.php`
- Test: `tests/Feature/UserManagement/ResetProfilePasswordTest.php`

**Interfaces:**
- Consumes: `SetAccountPassword::handle(User $target, string $newPassword)` (Task 2); `AuditLogger::record(...)`; `ActiveRole`.
- Produces:
  - `ResetProfilePassword::handle(PersonProfile $profile, string $newPassword, User $actor): void` — tìm User theo `person_profile_id` (không có → `ProfileHasNoAccount`), gọi `SetAccountPassword`, ghi audit `user.password_reset` (entityType `User`, `after` chỉ chứa `{user_id}`), tất cả trong một transaction.
  - `ProfileHasNoAccount` — `errorCode = 'NO_ACCOUNT'`, 409, message "Hồ sơ này chưa có tài khoản."
  - `PersonProfilePolicy::resetPassword(User $user, PersonProfile $profile): Response` — Admin allow; Teacher allow chỉ khi `Student::where('person_profile_id', $profile->id)->exists()`; còn lại `Response::deny('Bạn không có quyền đặt lại mật khẩu cho hồ sơ này.')` (403). Vai trò đọc qua `ActiveRole`.
  - `POST /api/v1/person-profiles/{personProfile}/password` body `{password}` (required|string|min:8) → 204; nhóm route `role:admin,teacher`.

- [ ] **Step 1: Viết test fail:**
  - `test_admin_resets_password_for_any_profile_kind` (student/parent/teacher: đăng nhập được bằng mật khẩu mới, cờ `true`, 204).
  - `test_teacher_resets_student_password_for_a_student_not_in_their_class`.
  - `test_teacher_cannot_reset_teacher_or_parent_password` (403, mật khẩu không đổi).
  - `test_student_and_parent_roles_get_403` (route `role:`).
  - `test_profile_without_account_returns_409_no_account`.
  - `test_reset_kills_target_sessions_but_not_actor_session`.
  - `test_reset_invalidates_pending_invitation_link` (mời hồ sơ, reset, dùng token cũ ở `/auth/activate` → lỗi; mật khẩu admin đặt vẫn dùng được).
  - `test_reset_writes_audit_without_password` (một dòng `user.password_reset`, `actor_user_id` đúng, `json_encode(after_data)` không chứa mật khẩu).
  - `test_short_password_returns_422`.
- [ ] **Step 2: Chạy** → FAIL.
- [ ] **Step 3: Implement** theo Interfaces; thêm dòng `NO_ACCOUNT` vào `docs/architecture/error-codes.md` (hoặc dùng lại `NOT_INVITED` nếu thấy trùng điều kiện hơn — nếu dùng lại thì ghi đè message qua constructor và bỏ class mới; quyết khi code, nhưng chỉ chọn một).
- [ ] **Step 4: Chạy** `php artisan test` toàn bộ (gồm `OpenApiSpecTest`, `ArchitectureTest`) → PASS.
- [ ] **Step 5: Commit** `feat: add reset-password endpoint for admin and teacher`.

### Task 6: Tài liệu và đồng bộ trạng thái

**Files:** (repo docs) `docs/database/*` (cột `users.must_change_password`), `docs/access-control/*` (Teacher tạo Student + đặt lại mật khẩu Student), `docs/architecture/error-codes.md`, `docs/progress.md`, `docs/trang-thai-ngan.md`.

- [ ] **Step 1:** Tìm file mô tả bảng `users` trong `docs/database/` và thêm cột (kiểu, mặc định, ý nghĩa); thêm dòng quyền vào `docs/access-control/` đúng chỗ liệt kê quyền Teacher.
- [ ] **Step 2:** Cập nhật `progress.md`/`trang-thai-ngan.md`: lát 7 → "code BE xong, đang review" kèm số PR nếu đã có.
- [ ] **Step 3: Commit** vào nhánh docs riêng (không commit thẳng `main`); chỉ push/PR khi user bảo.

---

## Self-review

- **Spec coverage:** form có account (Task 4), một giao dịch + rollback (Task 4), Teacher tạo Student (Task 4 route) và đặt lại cho Student (Task 5), cờ + Bỏ qua (Task 1-3), đặt lại + thu hồi phiên + token mời + audit (Task 2, 5), Mời/Tạo lại liên kết không đổi (không task nào sửa; test Identity/UserManagement cũ phải xanh), docs (Task 6). FE hiển thị mật khẩu sau khi lưu không cần BE (FE giữ mật khẩu vừa nhập/sinh; BE không trả mật khẩu).
- **Ghi chú ngoài phạm vi:** `DEC-USR-016` cũng cho Teacher gọi `POST /person-profiles/{id}/invite`; plan này **chưa** mở route đó cho Teacher (luồng thêm mới không dùng Mời). Nếu muốn, thêm riêng sau.
- **Tên nhất quán:** `must_change_password`, `CreateUserWithPassword`, `SetAccountPassword`, `DismissPasswordChangePrompt`, `CreateProfileAccount`, `ResetProfilePassword`, `PersonProfilePolicy::resetPassword`, mã lỗi `NO_ACCOUNT`.
