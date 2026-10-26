# FE: Thêm hồ sơ kèm tài khoản, đặt lại mật khẩu, nhắc đổi mật khẩu — Kế hoạch triển khai

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ghép FE Admin với BE đã merge (`piclass-api` PR #48): form thêm Học viên/Phụ huynh/Giáo viên có khối tài khoản, hộp thoại "Đặt lại mật khẩu" ở khối Tài khoản, và lời nhắc đổi mật khẩu lần đăng nhập đầu (có nút Bỏ qua) cho mọi vai trò.

**Architecture:** Lớp dữ liệu (`features/*/api`) thêm kiểu, hàm gọi API, hook, schema; lớp giao diện (`features/*/ui/admin`, `features/identity/ui/shared`) thêm component dùng chung. Không có mockup cho lát này (user bỏ qua mockup, 2026-10-27): UI dựng từ primitive `components/ui/*` và mẫu có sẵn (`InviteAccountDialog`, `RegenerateInvitationDialog`, `*-form-screen.tsx`), không thêm giá trị px/màu mới ngoài token đã dùng ở các file đó.

**Tech Stack:** Next.js 16, React 19, TypeScript, TanStack Query, React Hook Form + Zod, Vitest + Testing Library + MSW. Quy ước: `piclass-web/CLAUDE.md` (F1–F8).

**Spec:** `docs/superpowers/specs/2026-10-26-create-profile-with-account-design.md` (mục "Thiết kế FE"; `DEC-USR-021/022`). BE: `docs/superpowers/plans/2026-10-26-be-create-profile-with-account.md`.

## Hợp đồng API (đã merge ở BE, dùng nguyên văn)

- `POST /students|parents|teachers`: body thêm `account: {login_identifier: string, password: string}` (bắt buộc). Response như cũ; `account` trong response không có mật khẩu. 422 `details` có khóa dạng `account.login_identifier`, `account.password` (và `account` khi thiếu cả khối). Lỗi mật khẩu: BE yêu cầu tối thiểu 8 ký tự (hiện nguyên văn message của BE, F7).
- `POST /person-profiles/{personProfileId}/password` body `{password}` → 204 (Admin; Teacher chỉ cho học sinh). Lỗi: 409 `NO_ACCOUNT`, 403 `FORBIDDEN`, 422 `details.password`. Hiệu ứng ở BE: bật cờ đổi mật khẩu, thu hồi phiên/token của tài khoản đích, vô hiệu liên kết mời chưa dùng.
- `GET /auth/me` và `POST /auth/login`: khối `user` có thêm `must_change_password: boolean`.
- `PATCH /me/password` body `{current_password, new_password}` (đã có; tắt cờ khi thành công; 422 `details.current_password` khi sai).
- `POST /me/password/dismiss-prompt` → 204 (tắt cờ, idempotent).
- Luồng Mời (`/invite`) và Tạo lại liên kết (`/invitation`) giữ nguyên.

## Thiết kế UI (quyết định, thay cho mockup)

1. **Form thêm (create)** — AD-05a/06b/07b: dưới Card thông tin hồ sơ, thêm `Card` thứ hai tiêu đề "Tài khoản đăng nhập" (cùng cột trái, `lg:grid-cols-[2fr_1fr]` giữ nguyên). Trong card:
   - `Input` "Tên đăng nhập" `required`, hint "Dùng tên tự đặt (ví dụ khue9a) hoặc email. Không được trùng với ai khác."
   - `Input` "Mật khẩu" `required`, **hiển thị rõ** (`type="text"`, `autoComplete="off"`; Admin cần đọc để gửi qua Zalo), hint "Tối thiểu 8 ký tự." và nút `Button size="sm" variant="outline" iconLeft="refresh-cw"` "Tự sinh mật khẩu" cạnh ô.
   - Dòng chú thích dưới card: "Người dùng sẽ được nhắc đổi mật khẩu ở lần đăng nhập đầu và có thể bỏ qua."
   - Chế độ **sửa** không hiện card này. Card "Sau khi lưu" ở cột phải đổi: bullet 1 "Hiện thông tin đăng nhập để bạn sao chép gửi qua Zalo"; bullet 2 (học viên) "Gợi ý: Thêm vào lớp"; bỏ gợi ý "Mời tạo tài khoản". Bỏ câu hint cũ của ô Email ("hệ thống gửi thêm thư khi mời tạo tài khoản") → "Không bắt buộc."
2. **Sau khi lưu thành công (create)**: không chuyển trang ngay. Mở `CreatedAccountDialog` (`Dialog` tiêu đề "Đã tạo tài khoản") hiện: tên người, tên đăng nhập, mật khẩu (ô `readOnly`), nút "Sao chép thông tin đăng nhập" (copy 2 dòng `Tên đăng nhập: …\nMật khẩu: …`, đổi nhãn "Đã sao chép" sau khi bấm) và "Xong" → `router.push` tới trang chi tiết hồ sơ vừa tạo. Cảnh báo "Mật khẩu chỉ hiện ở lần này; sau này cần thì dùng Đặt lại mật khẩu." Đóng dialog bằng X/Esc cũng điều hướng như "Xong". Mật khẩu chỉ nằm trong state của component, không lưu cache/URL/log.
3. **Khối Tài khoản (AccountSection)**: khi có `account` và `account.status === "active"`, thêm nút `Button size="sm" variant="outline"` "Đặt lại mật khẩu" cạnh "Tạo lại liên kết". Hai nút độc lập, dùng theo tình huống. Hồ sơ chưa có tài khoản: giữ nút "Mời tạo tài khoản" như cũ.
4. **`ResetAccountPasswordDialog`**: tiêu đề "Đặt lại mật khẩu"; hiện Tên đăng nhập (chỉ đọc); `Input` "Mật khẩu mới" (hiển thị rõ) + "Tự sinh mật khẩu"; ghi chú "Người dùng bị đăng xuất khỏi mọi thiết bị, liên kết kích hoạt chưa dùng hết hiệu lực, và sẽ được nhắc đổi mật khẩu ở lần đăng nhập sau."; nút "Hủy bỏ"/"Đặt lại mật khẩu". Thành công: thay nội dung bằng khối kết quả giống `CreatedAccountDialog` (tên đăng nhập + mật khẩu + sao chép + "Xong"). Lỗi 422 hiện dưới ô mật khẩu; lỗi khác (403/409) hiện thông báo chung nguyên văn message của BE.
5. **Lời nhắc đổi mật khẩu lần đầu (`PasswordChangePromptDialog`)**: khi `me.user.must_change_password === true`, `PortalGuard` render dialog "Nên đổi mật khẩu" bên trong `AppShell` (sau khi đã có vai trò). Nội dung: "Mật khẩu hiện tại do người quản lý đặt. Bạn nên đổi sang mật khẩu của riêng mình." Hai ô `type="password"`: "Mật khẩu hiện tại" (`autoComplete="current-password"`), "Mật khẩu mới" (`autoComplete="new-password"`). Nút "Để sau" (gọi dismiss) và "Đổi mật khẩu". Đóng bằng X/Esc = "Để sau". Thành công một trong hai → invalidate query `['auth','me']` → cờ về false → dialog biến mất, không hiện lại. Lỗi 422 hiện theo trường (`current_password`, `new_password`), nguyên văn BE.
6. Màu/khoảng cách/bo góc/cỡ chữ: copy đúng các class đã dùng ở `invite-account-dialog.tsx`, `regenerate-invitation-dialog.tsx`, `student-form-screen.tsx` (thông báo thành công `bg-success-soft text-[var(--green-700)]`, lỗi `bg-danger-soft text-[var(--coral-700)]`, nhãn phụ `text-xs text-muted`, giá trị `text-strong`). Không tự thêm giá trị mới.

## Global Constraints

- Quy ước `piclass-web/CLAUDE.md`: F1 route mỏng; F2 chỉ `lib/api-client.ts` gọi `fetch`; F3 `features/*/api` không import UI; F4 `ui/<vai-trò>` không import vai trò khác (dùng `ui/shared`); F5 import feature khác chỉ qua `index.ts`; F7 FE không tự quyết quyền/rule (Zod chỉ kiểm dạng; 422 của BE hiện nguyên văn); file ≤ ~200 dòng; tên file/biến/test method tiếng Anh, mô tả test + comment + chữ giao diện tiếng Việt; mã màn (AD-05a…) ghi ở comment đầu file.
- Không dùng store toàn cục; dữ liệu server qua TanStack Query; sau mutation invalidate đúng key.
- Mật khẩu chỉ nằm trong state component và biến của `useMutation`: gọi `mutation.reset()` khi đóng dialog; không đưa vào cache query, không log, không đưa lên URL.
- Hồ sơ cũ chưa có tài khoản vẫn dùng `InviteAccountDialog` (không sửa).
- Tạo nhánh từ `origin/main` sau `git fetch origin`; commit message tiếng Anh, kết thúc bằng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`; PR tiêu đề/mô tả tiếng Việt.
- Chạy kiểm tra trên máy host: `pnpm test`, `pnpm lint`, `pnpm typecheck` (thư mục `piclass-web`). `architecture.test.ts` phải xanh.

## Review Focus

- Form create gửi đúng `account.{login_identifier,password}`; thiếu một trong hai → báo lỗi tại ô, không gọi API.
- 422 `account.login_identifier` (trùng) và `account.password` (yếu) hiện đúng dưới ô tương ứng, nội dung nhập vẫn giữ.
- Lưu thành công nhưng đóng dialog bằng Esc/X vẫn điều hướng tới trang chi tiết; mật khẩu không còn sau khi điều hướng.
- Chế độ sửa không hiện khối tài khoản và không gửi `account`.
- Lời nhắc: bấm "Để sau" hoặc đóng dialog gọi dismiss đúng một lần; đổi mật khẩu sai mật khẩu hiện tại hiện lỗi và không đóng; sau thành công không hiện lại.
- Nút "Đặt lại mật khẩu" không hiện với hồ sơ chưa có tài khoản hoặc tài khoản `inactive`; lỗi 409/403 hiện thông báo thay vì treo.
- Mất mạng khi lưu: nội dung đã nhập (kể cả mật khẩu) vẫn còn trong form (giữ hành vi `offlineError` hiện có).

## Cấu trúc file

| File | Loại | Việc |
| --- | --- | --- |
| `src/features/identity/api/types.ts`, `auth.ts`, `index.ts` | Sửa | cờ `must_change_password`, `changePassword`, `dismissPasswordPrompt` + hook |
| `src/features/identity/ui/shared/password-change-prompt-dialog.tsx` (+ test) | Tạo | lời nhắc đổi mật khẩu |
| `src/features/identity/ui/shared/portal-guard.tsx` (+ test) | Sửa | render lời nhắc |
| `src/features/user-management/api/types/{student,parent,teacher}.ts`, `shared.ts` | Sửa | `AccountInput`, `Create*Input.account` |
| `src/features/user-management/api/schemas.ts` | Sửa | schema create có `login_identifier`, `password` |
| `src/features/user-management/api/person-profiles.ts` | Sửa | `resetPassword` + hook `useResetAccountPassword` |
| `src/features/user-management/ui/admin/generate-password.ts` (+ test) | Tạo | sinh mật khẩu |
| `src/features/user-management/ui/admin/account-credentials-fields.tsx`, `created-account-dialog.tsx` (+ test) | Tạo | khối nhập tài khoản, dialog kết quả (dùng chung) |
| `src/features/user-management/ui/admin/{student,parent,teacher}-form-screen.tsx` (+ test) | Sửa | gắn khối tài khoản, dialog kết quả |
| `src/features/user-management/ui/admin/reset-account-password-dialog.tsx` (+ test), `account-section.tsx` (+ test) | Tạo/Sửa | đặt lại mật khẩu |

---

### Task 1: Identity — kiểu, hàm API và hook cho cờ đổi mật khẩu

**Files:**
- Modify: `src/features/identity/api/types.ts`, `src/features/identity/api/auth.ts`, `src/features/identity/index.ts`
- Test: `src/features/identity/api/auth.test.tsx` (tạo mới nếu chưa có file test cho hook; theo mẫu `features/user-management/api/*.test.tsx`)

**Interfaces:**
- Produces:
  - `AuthenticatedUser.must_change_password: boolean`; `Me`/`LoginResult` giữ nguyên chữ ký (cờ nằm trong `user`).
  - `ChangePasswordInput { current_password: string; new_password: string }`.
  - `authApi.changePassword(input): Promise<void>` → `PATCH /me/password`; `authApi.dismissPasswordPrompt(): Promise<void>` → `POST /me/password/dismiss-prompt`.
  - `useChangePassword()`, `useDismissPasswordPrompt()`: `useMutation` bọc hàm trên; `onSuccess` invalidate `identityKeys.me`. Export cả hai và `ChangePasswordInput` qua `features/identity/index.ts`.

- [ ] **Step 1: Viết test fail:** `test_change_password_sends_patch_and_refreshes_me`, `test_dismiss_prompt_posts_and_refreshes_me` (MSW: handler `PATCH /api/v1/me/password` kiểm body `{current_password,new_password}`, `POST /api/v1/me/password/dismiss-prompt` trả 204; sau thành công query `['auth','me']` được refetch — dùng handler `GET /api/v1/auth/me` đếm số lần gọi); `test_change_password_422_exposes_details` (`current_password` trong `error.details`).
- [ ] **Step 2: Chạy** `pnpm vitest run src/features/identity/api` → FAIL.
- [ ] **Step 3: Implement** theo Interfaces. Cập nhật mọi fixture `me`/`login` trong test hiện có cho đủ trường `must_change_password: false` để `pnpm typecheck` xanh.
- [ ] **Step 4: Chạy** `pnpm vitest run src/features/identity`, `pnpm typecheck`, `pnpm lint` → PASS.
- [ ] **Step 5: Commit** `feat: add must_change_password flag and change-password hooks to identity`.

### Task 2: Lời nhắc đổi mật khẩu lần đầu trong portal

**Files:**
- Create: `src/features/identity/ui/shared/password-change-prompt-dialog.tsx`, `password-change-prompt-dialog.test.tsx`
- Modify: `src/features/identity/ui/shared/portal-guard.tsx`, `portal-guard.test.tsx`

**Interfaces:**
- Consumes: `useChangePassword`, `useDismissPasswordPrompt` (Task 1); `Dialog`, `Input`, `Button` ở `components/ui`.
- Produces: `PasswordChangePromptDialog({ open: boolean })` — tự gọi hook, không nhận callback; `PortalGuard` render `<PasswordChangePromptDialog open />` bên trong `AppShell` khi `me.data.user.must_change_password` là true.

- [ ] **Step 1: Viết test fail** (mô tả tiếng Việt, theo UI ở mục "Thiết kế UI" #5): `test_shows_prompt_with_two_password_fields_and_skip_button`; `test_skip_calls_dismiss_once_and_closes` (cũng cho Esc); `test_change_password_success_closes`; `test_wrong_current_password_shows_backend_message_and_stays_open` (422 `details.current_password`); `test_guard_shows_prompt_only_when_flag_true` (flag false → không có dialog; true → có).
- [ ] **Step 2: Chạy** `pnpm vitest run src/features/identity/ui/shared` → FAIL.
- [ ] **Step 3: Implement** component theo thiết kế #5 (form RHF + Zod chỉ kiểm bắt buộc: "Vui lòng nhập mật khẩu hiện tại." / "Vui lòng nhập mật khẩu mới."); tách giữ ≤200 dòng. Sửa `PortalGuard` tối thiểu.
- [ ] **Step 4: Chạy** `pnpm vitest run src/features/identity`, `pnpm typecheck`, `pnpm lint`, `pnpm vitest run src/architecture.test.ts` → PASS.
- [ ] **Step 5: Commit** `feat: prompt users to change password on first sign-in`.

### Task 3: User-management — kiểu, schema, hàm API đặt lại mật khẩu, helper sinh mật khẩu

**Files:**
- Modify: `src/features/user-management/api/types/shared.ts`, `types/student.ts`, `types/parent.ts`, `types/teacher.ts`, `api/schemas.ts`, `api/person-profiles.ts`, `api/students.ts`/`parents.ts`/`teachers.ts` (chỉ nếu cần để mutation nhận `account`)
- Create: `src/features/user-management/ui/admin/generate-password.ts`, `generate-password.test.ts`
- Test: `src/features/user-management/api/person-profiles.test.tsx` (thêm), `src/features/user-management/api/students.test.tsx`/`parents.test.tsx`/(teachers nếu có) thêm case có `account`

**Interfaces:**
- Produces:
  - `AccountInput { login_identifier: string; password: string }` (trong `types/shared.ts`); `CreateStudentInput|CreateParentInput|CreateTeacherInput` thêm `account: AccountInput`.
  - Schema Zod: `studentCreateFormSchema`, `parentCreateFormSchema`, `teacherCreateFormSchema` = schema hiện có `.extend({ login_identifier: z.string().trim().min(1, "Vui lòng nhập tên đăng nhập."), password: z.string().min(1, "Vui lòng nhập mật khẩu.") })` và kiểu `*CreateFormValues`; defaults `*_CREATE_FORM_DEFAULTS` thêm hai trường rỗng. Schema sửa hiện có giữ nguyên.
  - `personProfilesApi.resetPassword(personProfileId: string, password: string): Promise<void>` → `POST /person-profiles/{id}/password` body `{password}`; hook `useResetAccountPassword()`: `useMutation` với biến `{ personProfileId: string; password: string }`, `onSuccess` invalidate `["user-management"]`.
  - `generatePassword(length = 12): string` trong `generate-password.ts`: dùng `crypto.getRandomValues`, bảng ký tự bỏ ký tự dễ nhầm (`0 O o 1 l I`), luôn có ít nhất một chữ thường, một chữ hoa, một chữ số; không phụ thuộc React.

- [ ] **Step 1: Viết test fail:** `test_generate_password_has_requested_length_and_required_classes` (lặp 50 lần, độ dài 12, có thường/hoa/số, không chứa ký tự bị loại); `test_generate_password_differs_between_calls`; `test_create_student_posts_account_in_body`; `test_create_parent_posts_account_in_body`; `test_reset_password_posts_to_profile_password_endpoint_and_returns_void`; `test_create_form_schema_requires_login_identifier_and_password` (thông báo tiếng Việt như trên).
- [ ] **Step 2: Chạy** các test mới → FAIL.
- [ ] **Step 3: Implement** theo Interfaces; sửa fixture/test cũ của `Create*Input` cho đủ `account` để typecheck xanh.
- [ ] **Step 4: Chạy** `pnpm vitest run src/features/user-management/api src/features/user-management/ui/admin/generate-password.test.ts`, `pnpm typecheck` → PASS (typecheck đỏ ở các form screen cũ là chấp nhận được tới Task 5; nếu đỏ thì để Task 5 sửa và ghi vào báo cáo).
- [ ] **Step 5: Commit** `feat: add account input, create schemas and reset-password api to user-management`.

### Task 4: Component dùng chung — khối nhập tài khoản và dialog kết quả

**Files:**
- Create: `src/features/user-management/ui/admin/account-credentials-fields.tsx`, `created-account-dialog.tsx` và test của từng file

**Interfaces:**
- Consumes: `generatePassword` (Task 3), `Input`, `Button`, `Card`, `Dialog`.
- Produces:
  - `AccountCredentialsFields({ register, errors, onGeneratePassword })` — thuần trình bày: nhận `register: UseFormRegister<{login_identifier: string; password: string}>`-tương thích và `errors: {login_identifier?: string; password?: string}`; render `Card` tiêu đề "Tài khoản đăng nhập" theo thiết kế #1 (hai `Input` + nút "Tự sinh mật khẩu" gọi `onGeneratePassword()`; chú thích cuối card).
  - `CredentialsResult({ loginIdentifier: string; password: string })` — khối hiển thị tên đăng nhập + mật khẩu (`Input readOnly`) + nút "Sao chép thông tin đăng nhập" (copy `Tên đăng nhập: …\nMật khẩu: …`, nhãn đổi thành "Đã sao chép") — dùng lại ở Task 6.
  - `CreatedAccountDialog({ open, displayName, loginIdentifier, password, onClose })` — `Dialog` "Đã tạo tài khoản" chứa `CredentialsResult`, cảnh báo mật khẩu chỉ hiện lần này, nút "Xong"; X/Esc/"Xong" đều gọi `onClose`.

- [ ] **Step 1: Viết test fail:** `test_fields_render_labels_hints_and_generate_button`; `test_generate_button_invokes_callback`; `test_credentials_result_copies_both_lines_to_clipboard` (mock `navigator.clipboard.writeText`, kiểm chuỗi 2 dòng và nhãn "Đã sao chép"); `test_created_dialog_shows_name_username_password_and_closes_on_done_and_escape`.
- [ ] **Step 2: Chạy** `pnpm vitest run src/features/user-management/ui/admin/account-credentials-fields.test.tsx src/features/user-management/ui/admin/created-account-dialog.test.tsx` → FAIL.
- [ ] **Step 3: Implement** theo Interfaces và thiết kế #1, #2, #6.
- [ ] **Step 4: Chạy** hai file test + `pnpm lint` + `pnpm vitest run src/architecture.test.ts` → PASS.
- [ ] **Step 5: Commit** `feat: add shared account credentials fields and result dialog`.

### Task 5: Gắn khối tài khoản vào ba form thêm (Student/Parent/Teacher)

**Files:**
- Modify: `student-form-screen.tsx`, `parent-form-screen.tsx`, `teacher-form-screen.tsx` và ba file test tương ứng (đều trong `src/features/user-management/ui/admin/`)

**Interfaces:**
- Consumes: `*CreateFormSchema`, `*_CREATE_FORM_DEFAULTS`, `CreateStudentInput…` (Task 3); `AccountCredentialsFields`, `CreatedAccountDialog` (Task 4); `generatePassword`.
- Hành vi:
  - Create: `resolver` dùng schema create; payload gửi `account: {login_identifier, password}` (đã `trim` tên đăng nhập); khối tài khoản hiện ngay dưới Card hồ sơ ở cột trái. Edit: dùng schema sửa như cũ, **không** hiện khối, **không** gửi `account`.
  - Thành công create: mở `CreatedAccountDialog` (giữ `displayName` = `full_name`, `loginIdentifier`, `password` trong `useState` local); `onClose` → `router.push` tới `/admin/students/{id}` (hoặc `/admin/parents/{id}`, `/admin/teachers/{id}`).
  - `applyFieldErrors` gán thêm khóa `account.login_identifier` → trường `login_identifier`, `account.password` → `password`, `account` (thiếu cả khối) → `login_identifier`.
  - Đổi copy: bỏ hint cũ của Email ("Không bắt buộc."), card "Sau khi lưu" theo thiết kế #1.

- [ ] **Step 1: Viết/sửa test fail** (mỗi form): `test_create_requires_login_identifier_and_password_without_calling_api`; `test_create_posts_account_and_opens_result_dialog_then_navigates_on_done`; `test_422_on_account_login_identifier_and_password_shown_under_fields_and_inputs_kept`; `test_generate_button_fills_password_field`; `test_edit_mode_hides_account_block_and_sends_no_account`; `test_closing_result_dialog_with_escape_navigates`. Sửa các test create cũ vì giờ phải nhập tên đăng nhập + mật khẩu.
- [ ] **Step 2: Chạy** `pnpm vitest run src/features/user-management/ui/admin/*-form-screen.test.tsx` → FAIL.
- [ ] **Step 3: Implement** ba form. Nếu file vượt ~200 dòng, tách phần dùng chung (ví dụ hook `useCreatedAccountDialog` hoặc hàm `applyAccountFieldErrors`) vào `ui/admin` một file nhỏ, không tạo `utils` chung chung.
- [ ] **Step 4: Chạy** `pnpm test`, `pnpm lint`, `pnpm typecheck` (toàn bộ) → PASS.
- [ ] **Step 5: Commit** `feat: create account together with student, parent and teacher profile in admin forms`.

### Task 6: Đặt lại mật khẩu ở khối Tài khoản

**Files:**
- Create: `src/features/user-management/ui/admin/reset-account-password-dialog.tsx`, `reset-account-password-dialog.test.tsx`
- Modify: `src/features/user-management/ui/admin/account-section.tsx`, `account-section.test.tsx`

**Interfaces:**
- Consumes: `useResetAccountPassword` (Task 3), `generatePassword`, `CredentialsResult` (Task 4).
- Produces: `ResetAccountPasswordDialog({ open, personProfileId, displayName, username, onClose })`; `AccountSection` thêm nút "Đặt lại mật khẩu" khi `account && account.status === "active"` (không phụ thuộc `last_login_at`); `RegenerateInvitationDialog` và `InviteAccountDialog` không đổi.

- [ ] **Step 1: Viết test fail:** `test_reset_dialog_requires_password_before_calling_api`; `test_reset_posts_new_password_and_shows_credentials_result` (MSW `POST /api/v1/person-profiles/p1/password` → 204, kiểm body); `test_reset_422_shows_backend_message_under_password`; `test_reset_409_and_403_show_backend_message`; `test_generate_button_fills_password`; `test_account_section_shows_reset_button_only_for_active_account` (không tài khoản → chỉ "Mời tạo tài khoản"; inactive → không có nút; active đã đăng nhập → có "Đặt lại mật khẩu" và không có "Tạo lại liên kết"; active chưa đăng nhập → có cả hai).
- [ ] **Step 2: Chạy** `pnpm vitest run src/features/user-management/ui/admin/reset-account-password-dialog.test.tsx src/features/user-management/ui/admin/account-section.test.tsx` → FAIL.
- [ ] **Step 3: Implement** theo thiết kế #3, #4, #6; gọi `mutation.reset()` khi đóng để không giữ mật khẩu.
- [ ] **Step 4: Chạy** `pnpm test`, `pnpm lint`, `pnpm typecheck`, `pnpm vitest run src/architecture.test.ts` → PASS (các test màn chi tiết student/parent/teacher vẫn xanh).
- [ ] **Step 5: Commit** `feat: add admin reset-password dialog to account section`.

### Task 7: Tài liệu và đồng bộ trạng thái (controller làm, repo docs)

- [ ] **Step 1:** `docs/product/screens/admin.md`: cập nhật AD-05a/06b/07b (khối "Tài khoản đăng nhập", dialog kết quả, copy mới), AD-05b (chỉ còn cho hồ sơ cũ), AD-05c (thêm "Đặt lại mật khẩu" cạnh "Tạo lại liên kết"); `docs/product/screens/common.md`: lời nhắc đổi mật khẩu lần đầu.
- [ ] **Step 2:** `docs/architecture/open-decisions.md`: ghi các điểm không có mockup (UI lát này dựng từ primitive có sẵn, chưa đối chiếu mockup; mật khẩu hiển thị rõ ở ô nhập của Admin; Teacher chưa có màn tạo học sinh/đặt lại mật khẩu vì cổng Giáo viên chưa port).
- [ ] **Step 3:** `docs/progress.md`, `docs/trang-thai-ngan.md`: lát 7 FE xong + số PR; ghi báo cáo các điểm cần user xác nhận.

---

## Self-review

- **Spec coverage:** form có khối tài khoản + kết quả để sao chép (T3-T5), nhắc đổi mật khẩu có Bỏ qua (T1-T2), đặt lại mật khẩu (T3, T6), Mời/Tạo lại liên kết giữ nguyên (không task nào sửa; T6 test giữ "Tạo lại liên kết"), tài liệu (T7). **Chưa làm (ghi vào báo cáo):** màn của Teacher (tạo học sinh/đặt lại mật khẩu) vì cổng Giáo viên chưa port; chỉ có lời nhắc đổi mật khẩu chạy cho mọi vai trò qua `PortalGuard`.
- **Tên nhất quán:** `AccountInput`, `*CreateFormSchema`, `useResetAccountPassword`, `generatePassword`, `CredentialsResult`, `CreatedAccountDialog`, `PasswordChangePromptDialog`, `useChangePassword`, `useDismissPasswordPrompt`.
- **Rủi ro mở:** UI không có mockup (xem Thiết kế UI); không có e2e Playwright cho lát này.
