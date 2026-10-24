# Liên kết kích hoạt tài khoản — thiết kế (BE nhóm 0)

**Ngày:** 2026-10-24. **Nguồn quyết định:** `DEC-USR-013`, `DEC-USR-014`, `DEC-USR-015` (`requirements/decision-log.md`); gap A10 của `product/screens/api-gaps.md`; màn CM-09, AD-05b, AD-05c.

## Vấn đề

`POST /person-profiles/{id}/invite` tạo User rồi chỉ gửi email chứa token đặt mật khẩu. Hiện tại:
- Token **không trả về** cho Admin, nên Admin không sao chép được để gửi qua Zalo; hồ sơ không có email thì lời mời mất hút.
- Token dùng chung broker với quên mật khẩu nên chỉ sống **60 phút** (`config/auth.php`).
- Mời lần hai bị từ chối (`ALREADY_INVITED`), không có cách tạo lại liên kết.

## Quyết định thiết kế

1. **Broker riêng `invitations`** (bảng riêng `invitation_tokens`, hiệu lực **7 ngày** = 10080 phút, cấu hình bằng `AUTH_INVITATION_EXPIRE_MINUTES`). Tách riêng để lời mời sống lâu mà token quên mật khẩu vẫn 60 phút, và hai loại token không dùng lẫn của nhau.
2. **Endpoint kích hoạt mới `POST /auth/activate`** (công khai) nhận `{login_identifier, token, password}`, kiểm token bằng broker `invitations`, đặt mật khẩu, xóa mọi session cũ (đúng như `ResetPassword`). `POST /auth/password/reset` giữ nguyên, chỉ nhận token của broker mặc định.
3. **`POST /person-profiles/{id}/invite`** vẫn tạo User (mật khẩu ngẫu nhiên), nhưng token do broker `invitations` sinh và phản hồi **trả kèm `activation`** `{url, expires_at}`. Chỉ gửi email khi hồ sơ có email.
4. **`POST /person-profiles/{id}/invitation`** (mới, Admin) tạo lại liên kết cho hồ sơ đã có tài khoản **nhưng chưa từng đăng nhập** (`users.last_login_at` rỗng); token cũ hết tác dụng vì mỗi User chỉ có một token. Có email thì gửi thêm thư.
5. URL kích hoạt: `{FRONTEND_URL}/activate?token=…&login_identifier=…` (màn CM-09 của FE đọc hai tham số này).

## Hợp đồng API

| Endpoint | Quyền | Thành công | Lỗi |
|---|---|---|---|
| `POST /auth/activate` | công khai | 200 `{}` | 422 `token` (sai, hết hạn hoặc đã dùng), 422 `password` (dưới 8 ký tự) |
| `POST /person-profiles/{id}/invite` | Admin | 201 `{user:{id, login_identifier}, activation:{url, expires_at}}` | 409 `ALREADY_INVITED`, 422 `login_identifier` (thiếu hoặc trùng) |
| `POST /person-profiles/{id}/invitation` | Admin | 200 `{user:{id, login_identifier}, activation:{url, expires_at}}` | 409 `NOT_INVITED` (hồ sơ chưa có tài khoản), 409 `ALREADY_ACTIVATED` (tài khoản đã đăng nhập), 409 `ACCOUNT_DEACTIVATED` (tài khoản đang bị vô hiệu hóa; chỉ cần kích hoạt lại tài khoản) |

`expires_at` là ISO 8601 `+07:00` (`DEC-SYS-002`). Hai mã lỗi mới thêm vào `architecture/error-codes.md`.

## Không làm trong lượt này

- "Đặt mật khẩu hộ" (Admin đặt mật khẩu trực tiếp): dời sang nhóm 5.
- Ghi audit cho việc tạo liên kết: `DEC-AUD-001` không liệt kê; ghi nhận ở đây, quyết định riêng nếu cần.
- Tự động dọn token hết hạn của bảng `invitation_tokens`.
- Đổi định dạng `login_identifier`: BE đã chỉ yêu cầu chuỗi duy nhất, không bắt email.

## Rủi ro đã biết

- Token xuất hiện trong phản hồi API và có thể nằm trong log truy cập nếu bị ghi nhầm; không log body phản hồi của hai endpoint này.
- Tài khoản mời trước thay đổi này có token 60 phút nằm ở `password_reset_tokens`; Admin dùng endpoint tạo lại liên kết để thay bằng liên kết mới.
