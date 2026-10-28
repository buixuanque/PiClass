# Liên kết kích hoạt tài khoản (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lời mời tài khoản trả liên kết kích hoạt (7 ngày) cho Admin sao chép, có endpoint kích hoạt riêng và endpoint tạo lại liên kết.

**Architecture:** Thêm broker mật khẩu `invitations` (bảng `invitation_tokens`, hiệu lực 7 ngày) tách khỏi broker quên mật khẩu. `ResetPassword` nhận tên broker để dùng chung cho `ActivateAccount`. `InvitePersonProfile` sinh token bằng broker mới và trả `AccountInvitation` (DTO) cho controller; thêm Action `RegenerateInvitation`.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL. Quy ước code: `piclass-api/CLAUDE.md` (R1–R11 do `ArchitectureTest` canh).

**Spec:** `docs/archive/superpowers/specs/2026-10-24-account-activation-link-design.md`

## Global Constraints

- Hiệu lực liên kết kích hoạt: **10080 phút** (7 ngày), env `AUTH_INVITATION_EXPIRE_MINUTES`; token quên mật khẩu giữ **60 phút**, không đổi.
- Mật khẩu mới tối thiểu 8 ký tự (`ARCH-05`).
- `expires_at` xuất ISO 8601 `+07:00` qua `App\Support\AppTime::format` (R10).
- Comment code tiếng Việt; tên class, hàm, test, `error.code` tiếng Anh. Commit message tiếng Anh, kết thúc bằng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`.
- Mã lỗi mới ghi vào `docs/architecture/error-codes.md` cùng PR.
- Không đổi hành vi `POST /auth/password/reset` và `POST /auth/password/forgot`.
- Làm trên branch mới từ `origin/main` của `piclass-api` (`git fetch origin` trước); không commit vào `main`.

## Review Focus

- Token quên mật khẩu bị dùng ở `/auth/activate` và ngược lại: phải 422.
- Tạo lại liên kết thì liên kết cũ không còn dùng được.
- Hồ sơ không có email: mời vẫn 201, không gửi mail, vẫn trả liên kết.
- Tạo lại liên kết cho tài khoản đã đăng nhập: 409 `ALREADY_ACTIVATED`; cho hồ sơ chưa có tài khoản: 409 `NOT_INVITED`.
- Liên kết đúng 7 ngày: còn dùng được ở 7 ngày trừ 1 phút, hết ở 7 ngày cộng 1 phút.

---

### Task 1: Broker `invitations` và `POST /auth/activate`

**Files:**
- Modify: `config/auth.php` (thêm `passwords.invitations`), `.env.example` (thêm `AUTH_INVITATION_EXPIRE_MINUTES=10080`)
- Create: `database/migrations/2026_10_24_000001_create_invitation_tokens_table.php`
- Modify: `app/Modules/Identity/Actions/ResetPassword.php`
- Create: `app/Modules/Identity/Actions/ActivateAccount.php`, `app/Modules/Identity/Http/Requests/ActivateAccountRequest.php`, `app/Modules/Identity/Http/Controllers/ActivateAccountController.php`
- Modify: `routes/api.php` (cạnh `/auth/password/reset`)
- Test: `tests/Feature/Identity/ActivateAccountTest.php`

**Interfaces:**
- Produces: `ResetPassword::handle(string $loginIdentifier, string $token, string $password, ?string $broker = null): void`; `ActivateAccount::handle(string $loginIdentifier, string $token, string $password): void`; broker `Password::broker('invitations')`.

- [ ] **Step 1: Viết test (fail)** trong `ActivateAccountTest`, dùng `Password::broker('invitations')->createToken($user)` để tạo token. Các test:
  - `test_activation_sets_the_password_and_clears_sessions`: POST `/api/v1/auth/activate` `{login_identifier, token, password:'matkhau-moi-1'}` → 200; đăng nhập bằng mật khẩu mới được; bảng `sessions` của user trống.
  - `test_activation_token_is_single_use`: gọi lần hai cùng token → 422 có lỗi `token`.
  - `test_activation_token_lasts_seven_days`: `travelTo(now()->addDays(7)->subMinute())` → 200; làm lại với token mới và `travelTo(now()->addDays(7)->addMinute())` → 422 `token`.
  - `test_password_reset_token_is_rejected_for_activation`: token từ `Password::broker()->createToken($user)` → 422 `token`.
  - `test_activation_token_is_rejected_for_password_reset`: token từ broker `invitations` gửi tới `/api/v1/auth/password/reset` → 422 `token`.
  - `test_activation_rejects_a_short_password`: `password:'1234567'` → 422 `password`.
- [ ] **Step 2: Chạy test, xác nhận fail:** `php artisan test --filter=ActivateAccountTest` (ở môi trường dev của repo, xem `piclass-infra`). Mong đợi: FAIL (route chưa có).
- [ ] **Step 3: Implement.** Migration tạo `invitation_tokens` cùng cột với `password_reset_tokens` (`email` string primary, `token`, `created_at` nullable). `config/auth.php`: broker `invitations` dùng `provider: users`, `table: invitation_tokens`, `expire: env('AUTH_INVITATION_EXPIRE_MINUTES', 10080)`, `throttle: 0`. `ResetPassword::handle` thêm tham số `$broker` và gọi `Password::broker($broker)`. `ActivateAccount::handle` gọi `ResetPassword` với broker `'invitations'` (inject qua constructor). `ActivateAccountRequest` cùng rules với `ResetPasswordRequest`. Controller single-action trả `response()->json([], 200)`. Route công khai, cạnh `/auth/password/reset`.
- [ ] **Step 4: Chạy test, xác nhận pass** cùng toàn bộ `tests/Feature/Identity` và `ArchitectureTest`: `php artisan test --filter=Identity` và `--filter=ArchitectureTest`. Mong đợi: PASS.
- [ ] **Step 5: Commit** `feat: add the invitations password broker and the activation endpoint`.

### Task 2: Mời tài khoản trả liên kết kích hoạt

**Files:**
- Create: `app/Modules/UserManagement/Data/AccountInvitation.php`, `app/Notifications/AccountInvitationNotification.php`
- Modify: `app/Modules/UserManagement/Actions/InvitePersonProfile.php`, `app/Modules/UserManagement/Http/Resources/InvitedUserResource.php`, `app/Modules/UserManagement/Http/Controllers/InvitePersonProfileController.php`
- Test: `tests/Feature/UserManagement/InvitePersonProfileTest.php` (sửa), `tests/Feature/UserManagement/UserManagementActionsTest.php` (sửa nếu gọi `InvitePersonProfile::handle`)

**Interfaces:**
- Consumes: broker `invitations` (Task 1).
- Produces: `final readonly class AccountInvitation { public function __construct(public User $user, public string $token, public CarbonImmutable $expiresAt) {} public function activationUrl(): string }` (URL `{config('app.frontend_url')}/activate?token=…&login_identifier=…`); `InvitePersonProfile::handle(PersonProfile $profile, string $loginIdentifier): AccountInvitation`; `AccountInvitationNotification(AccountInvitation $invitation)` gửi mail tiếng Việt chứa `activationUrl()` và dòng "hết hạn sau 7 ngày".

- [ ] **Step 1: Viết test (fail)** trong `InvitePersonProfileTest`:
  - `test_invite_returns_an_activation_link_valid_for_seven_days`: 201, JSON có `user.id`, `user.login_identifier`, `activation.url` bắt đầu bằng `config('app.frontend_url').'/activate?token='` và chứa `login_identifier=`, `activation.expires_at` là ISO có `+07:00`.
  - `test_the_returned_link_activates_the_account`: lấy `token` và `login_identifier` từ `activation.url`, POST `/api/v1/auth/activate` → 200.
  - `test_invite_without_a_profile_email_still_succeeds_and_sends_no_mail`: hồ sơ `email => null`, `Notification::fake()`, 201 và `Notification::assertNothingSent()`.
  - `test_invite_with_a_profile_email_sends_the_invitation_mail`: `Notification::assertSentTo($user, AccountInvitationNotification::class)`.
  - Cập nhật test cũ đang kỳ vọng `ResetPasswordNotification` sang `AccountInvitationNotification`; giữ test `ALREADY_INVITED` và test trùng `login_identifier`.
- [ ] **Step 2: Chạy test, xác nhận fail:** `php artisan test --filter=InvitePersonProfileTest`.
- [ ] **Step 3: Implement.** `InvitePersonProfile` sinh token bằng `Password::broker('invitations')->createToken($user)` trong transaction hiện có, tính `expiresAt = now()->addMinutes(config('auth.passwords.invitations.expire'))`, trả `AccountInvitation`; sau commit chỉ `notify` khi `$user->routeNotificationFor('mail')` khác rỗng. Mail dùng `AccountInvitationNotification`. `InvitedUserResource` nhận `AccountInvitation` và xuất `{user:{id, login_identifier}, activation:{url, expires_at}}` (`expires_at` qua `AppTime::format`); controller truyền DTO vào resource, giữ status 201.
- [ ] **Step 4: Chạy test, xác nhận pass** cùng `--filter=UserManagement` và `ArchitectureTest`.
- [ ] **Step 5: Commit** `feat: return an activation link when inviting a person profile`.

### Task 3: `POST /person-profiles/{id}/invitation` (tạo lại liên kết)

**Files:**
- Create: `app/Modules/UserManagement/Actions/RegenerateInvitation.php`, `app/Modules/UserManagement/Exceptions/ProfileNotInvited.php`, `app/Modules/UserManagement/Exceptions/AccountAlreadyActivated.php`, `app/Modules/UserManagement/Http/Controllers/RegenerateInvitationController.php`
- Modify: `routes/api.php` (nhóm `role:admin`, cạnh `/invite`), `docs/architecture/error-codes.md` (repo docs), `docs/archive/superpowers/specs/2026-10-12-identity-auth-user-management-api-design.md` không cần sửa
- Test: `tests/Feature/UserManagement/RegenerateInvitationTest.php`

**Interfaces:**
- Consumes: `AccountInvitation`, `AccountInvitationNotification` (Task 2), broker `invitations` (Task 1).
- Produces: `RegenerateInvitation::handle(PersonProfile $profile): AccountInvitation`; `ProfileNotInvited` (409, `NOT_INVITED`), `AccountAlreadyActivated` (409, `ALREADY_ACTIVATED`).

- [ ] **Step 1: Viết test (fail)** trong `RegenerateInvitationTest` (Admin đã đăng nhập như `InvitePersonProfileTest`):
  - `test_admin_regenerates_the_link_for_an_invited_account`: mời rồi POST `/api/v1/person-profiles/{id}/invitation` → 200 với `activation.url` mới.
  - `test_the_old_link_stops_working_after_regeneration`: token cũ gửi `/auth/activate` → 422; token mới → 200.
  - `test_regeneration_fails_for_a_profile_without_an_account`: 409 `NOT_INVITED`.
  - `test_regeneration_fails_for_an_account_that_has_logged_in`: user có `last_login_at` khác null → 409 `ALREADY_ACTIVATED`.
  - `test_regeneration_without_a_profile_email_sends_no_mail`: `Notification::assertNothingSent()`, vẫn 200.
  - `test_only_admin_can_regenerate`: vai trò `teacher` → 403.
- [ ] **Step 2: Chạy test, xác nhận fail:** `php artisan test --filter=RegenerateInvitationTest`.
- [ ] **Step 3: Implement.** `RegenerateInvitation`: tìm `User` theo `person_profile_id`; không có thì ném `ProfileNotInvited`; `last_login_at` khác null thì ném `AccountAlreadyActivated`; ngược lại `createToken` bằng broker `invitations` (tự thay token cũ), dựng `AccountInvitation`, gửi mail nếu có email, trả DTO. Controller trả `InvitedUserResource` với status 200 (`->response()->setStatusCode(200)`). Ghi hai mã lỗi vào `docs/architecture/error-codes.md` (cột Module: UserManagement).
- [ ] **Step 4: Chạy test, xác nhận pass** cùng toàn bộ bộ test: `php artisan test`. Mong đợi: tất cả pass, `ArchitectureTest` pass.
- [ ] **Step 5: Commit** `feat: regenerate the activation link for an account that never logged in`.
