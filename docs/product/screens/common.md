# Đặc tả màn hình — Nhóm chung (CM)

Tài liệu này mô tả nội dung, hành vi và trạng thái của các màn hình dùng chung cho mọi vai trò (Admin, Teacher, Student, Parent): đăng nhập và khôi phục mật khẩu, chọn vai trò, hồ sơ, thông báo, trang lỗi, xem/tải file, cùng khung điều hướng (app shell). Không bàn màu sắc hay kiểu dáng; chỉ nói nội dung, hành vi, trạng thái để thiết kế UI. Nguồn: `piclass-api/routes/api.php`, module Identity / Notification / File / UserManagement, `docs/architecture/error-codes.md`, `docs/requirements/*`, danh sách ở `docs/product/screen-inventory.md`.

Quy ước: ✅ BE đã có · 🟡 thiếu API (mã G) · 🔵 đề xuất ngoài phạm vi đã chốt · 🎨 có mockup mẫu. Lỗi 403/404 trả cùng message "Request failed." (404 chuẩn hóa như vậy; riêng 403 `FORBIDDEN` từ `EnsureRole` có message tiếng Việt "Bạn không có quyền thực hiện hành động này.").

## Mục lục

| Mã | Màn hình | Trạng thái |
|---|---|---|
| [CM-01](#cm-01--đăng-nhập) | Đăng nhập | ✅ |
| [CM-02](#cm-02--quên-mật-khẩu) | Quên mật khẩu | ✅ |
| [CM-03](#cm-03--đặt-lại-mật-khẩu) | Đặt lại mật khẩu | ✅ |
| [CM-04](#cm-04--chọn--đổi-vai-trò) | Chọn / đổi vai trò | ✅ |
| [CM-05](#cm-05--hồ-sơ-cá-nhân) | Hồ sơ cá nhân (gồm đổi mật khẩu, đăng xuất) | ✅ (ảnh đại diện 🔵 bỏ khỏi V1) |
| [CM-06](#cm-06--trung-tâm-thông-báo) | Trung tâm thông báo | ✅ (đếm chưa đọc, đọc tất cả 🟡 G10) |
| [CM-07](#cm-07--trang-lỗi) | Trang lỗi | ✅ (thiết kế) |
| [CM-08](#cm-08--xem--tải-file) | Xem / tải file | ✅ |
| [CM-09](#cm-09--kích-hoạt-tài-khoản-lần-đầu) | Kích hoạt tài khoản lần đầu (thêm) | 🟡 G11 |
| [CM-10](#cm-10--khung-điều-hướng-app-shell) | Khung điều hướng (app shell) theo vai trò (thêm) | ✅ + 🔵 (gộp Admin/Teacher, Q6) |

## Ghi chú nền về xác thực (áp dụng cho CM-01…CM-10)

- Xác thực bằng cookie phiên (Sanctum, cùng miền). FE phải gửi kèm cookie; không có token trong body.
- `login_identifier` là chuỗi đăng nhập; thực tế là email (email đặt lại mật khẩu dùng chính giá trị này). Nhãn trên form: "Email" (cần xác nhận với Admin khi mời: `POST /person-profiles/{id}/invite` chỉ yêu cầu chuỗi duy nhất, không bắt định dạng email, nên nếu Admin nhập không phải email thì không nhận được thư).
- **Đăng nhập xong chưa có vai trò hoạt động.** `POST /auth/login` chỉ trả `user` và `available_roles`; `active_role` chỉ có sau `POST /auth/context`. Mọi endpoint theo vai trò trả 403 `FORBIDDEN` khi chưa chọn. Vì vậy sau login, FE luôn gọi `POST /auth/context` (tự động nếu chỉ có 1 vai trò; hiện màn chọn nếu có nhiều).
- `GET /auth/me` trả `{user:{id, login_identifier}, active_role, available_roles}`. `user` không có tên hiển thị: tên lấy từ `GET /me/profile` → `person_profile.full_name` (Admin không có, xem CM-05).
- Hết phiên (1 ngày không hoạt động) → 401 `UNAUTHENTICATED` → về CM-01 kèm thông báo; có "ghi nhớ đăng nhập" thì cookie `remember_*` tự đăng nhập lại.

---

### CM-01 — Đăng nhập   ✅
- **Vai trò · thiết bị ưu tiên:** Mọi vai trò (chưa đăng nhập) · điện thoại cho Student/Parent, máy tính cho Admin/Teacher; form một cột dùng tốt ở cả hai.
- **Người dùng muốn làm gì:** Vào hệ thống để xem lịch học, bài tập hoặc điểm danh lớp đầu giờ.
- **Vào từ / đi tới:** Địa chỉ gốc khi chưa có phiên; chuyển hướng từ mọi màn khi 401. Đăng nhập thành công → nếu 1 vai trò: tự chọn, vào trang chủ vai trò đó (AD/TC/ST/PR-xx tổng quan); nhiều vai trò: CM-04 (dạng màn chọn). "Quên mật khẩu?" → CM-02. Quay lại đúng trang đang mở dở nếu bị đá ra vì hết phiên (FE giữ đường dẫn cũ).
- **Khối nội dung & dữ liệu hiển thị:** (1) Logo + tên trung tâm. (2) Form: ô "Email" (`login_identifier`), ô "Mật khẩu" có nút hiện/ẩn, ô tick "Ghi nhớ đăng nhập" (`remember`, mặc định tắt), nút "Đăng nhập", liên kết "Quên mật khẩu?". (3) Vùng báo lỗi chung phía trên form. Không có đăng ký tự do (tài khoản chỉ tạo qua lời mời, xem CM-09).
- **Chức năng & hành động:** "Đăng nhập" gọi `POST /auth/login` `{login_identifier, password, remember}` → `{user:{id, login_identifier}, available_roles:["admin","teacher",...]}`; vô hiệu nút + vòng xoay khi đang gửi. "Ghi nhớ đăng nhập" bật → cookie `remember_*` giữ đăng nhập kể cả khi phiên chính hết hạn (1 ngày); tắt → hết hạn theo phiên. Nên ghi chú nhỏ: "Chỉ bật trên thiết bị của bạn". Sau thành công: gọi `POST /auth/context` (xem ghi chú nền). Enter để gửi.
- **Trạng thái cần vẽ:** mặc định · đang gửi · sai thông tin · bị tạm khóa (đếm ngược) · tài khoản bị vô hiệu · mất mạng/500 (thử lại) · thành công (chuyển trang). Không có trạng thái rỗng/không quyền.
- **Quy tắc & lỗi nghiệp vụ:**
  - 422 `error.details.login_identifier` = "Thông tin đăng nhập không đúng." — dùng cho cả sai email, sai mật khẩu và tài khoản đã vô hiệu (BE cố ý không phân biệt). Hiện ở vùng lỗi chung, không đánh dấu riêng một trường.
  - 422 theo trường khi bỏ trống: `login_identifier`/`password` bắt buộc → "Vui lòng nhập email" / "Vui lòng nhập mật khẩu".
  - 429 `TOO_MANY_ATTEMPTS`, message gốc (tiếng Anh): "Too many login attempts. Try again in {n} seconds." Ngưỡng: sai 5 lần trong 60 giây theo cặp (email, IP) thì khóa; đăng nhập đúng xóa bộ đếm. Đề xuất hiển thị: "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau {n} giây." (FE tách số giây từ message hoặc đếm ngược cố định 60 giây; cần xác nhận khi dựng vì BE không trả số giây ở trường riêng); nút "Đăng nhập" khóa trong lúc đếm. Cũng có thể gặp 429 chung `TOO_MANY_REQUESTS`.
  - Không có thông báo "tài khoản bị vô hiệu" riêng ở bước đăng nhập (xem trên); `ACCOUNT_INACTIVE` chỉ xuất hiện giữa phiên, xem CM-07.
- **API:** `POST /auth/login`, `POST /auth/context`, `GET /auth/me` (khi mở lại app để khôi phục phiên).
- **Dữ liệu mẫu gợi ý:** `co.huong@trungtam-pi.vn` / `••••••••`; lỗi mẫu: "Thông tin đăng nhập không đúng."; khóa: "Thử lại sau 42 giây".

### CM-02 — Quên mật khẩu   ✅
- **Vai trò · thiết bị ưu tiên:** Mọi vai trò (chưa đăng nhập) · điện thoại.
- **Người dùng muốn làm gì:** Phụ huynh quên mật khẩu, cần nhận thư để đặt lại.
- **Vào từ / đi tới:** Từ CM-01 ("Quên mật khẩu?"). Gửi xong ở lại cùng màn (đổi sang trạng thái đã gửi); "Quay lại đăng nhập" → CM-01. Email có liên kết mở CM-03.
- **Khối nội dung & dữ liệu hiển thị:** (1) Tiêu đề + 1 câu giải thích ("Nhập email đăng nhập, chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu"). (2) Ô "Email" (`login_identifier`), nút "Gửi hướng dẫn". (3) Liên kết "Quay lại đăng nhập".
- **Chức năng & hành động:** `POST /auth/password/forgot` `{login_identifier}` → luôn 202 rỗng, dù tài khoản có tồn tại hay không. Sau khi gửi: thay form bằng thông điệp "Nếu email này có tài khoản, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu. Liên kết có hiệu lực 60 phút." + nút "Gửi lại" (đợi ≥ 60 giây vì BE giới hạn tần suất tạo token; xem lỗi bên dưới) + "Quay lại đăng nhập". Không bao giờ nói email có hay không có trong hệ thống.
- **Trạng thái cần vẽ:** mặc định · đang gửi · đã gửi (thông điệp trung tính) · lỗi mạng/500 · bị giới hạn tần suất.
- **Quy tắc & lỗi nghiệp vụ:** 422 `login_identifier` bắt buộc → "Vui lòng nhập email". Gửi lại trong 60 giây: broker Laravel `throttle: 60` bỏ qua im lặng (vẫn 202, không gửi thư mới), nên FE tự khóa nút "Gửi lại" 60 giây. Thư chứa liên kết `…/reset-password?token=…&login_identifier=…`, chủ đề "Đặt lại mật khẩu PiClass", hết hạn 60 phút. Chặn bởi 429 `TOO_MANY_REQUESTS` (nếu có) → "Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút".
- **API:** `POST /auth/password/forgot`.
- **Dữ liệu mẫu gợi ý:** `phuhuynh.lan@gmail.com`; thông điệp đã gửi như trên.

### CM-03 — Đặt lại mật khẩu   ✅
- **Vai trò · thiết bị ưu tiên:** Mọi vai trò (chưa đăng nhập) · điện thoại.
- **Người dùng muốn làm gì:** Đặt mật khẩu mới sau khi bấm liên kết trong email.
- **Vào từ / đi tới:** Liên kết email `/reset-password?token=…&login_identifier=…` (FE đọc cả hai từ query, ẩn token khỏi giao diện). Thành công → CM-01 kèm thông báo "Đã đặt lại mật khẩu, hãy đăng nhập". Token hỏng/hết hạn → nút "Gửi lại liên kết" → CM-02.
- **Khối nội dung & dữ liệu hiển thị:** (1) Tiêu đề "Đặt mật khẩu mới"; hiển thị email đang đặt (chỉ đọc, lấy từ `login_identifier` trong query). (2) Ô "Mật khẩu mới" (hiện/ẩn) với gợi ý "Ít nhất 8 ký tự" và chỉ báo độ dài; ô "Nhập lại mật khẩu" (kiểm tra khớp ở FE, BE không nhận trường này). (3) Nút "Lưu mật khẩu".
- **Chức năng & hành động:** `POST /auth/password/reset` `{login_identifier, token, password}` → thành công thì mọi phiên đang đăng nhập của tài khoản bị hủy (đăng xuất khắp nơi). Không tự đăng nhập sau khi đặt; chuyển CM-01. Nếu thiếu `token` hoặc `login_identifier` trên URL: hiện ngay trạng thái liên kết không hợp lệ, không hiện form.
- **Trạng thái cần vẽ:** form · đang gửi · thành công (có thể là một thông điệp rồi chuyển trang) · liên kết không hợp lệ/hết hạn · mật khẩu quá ngắn · không khớp · lỗi mạng.
- **Quy tắc & lỗi nghiệp vụ:** 422 `error.details.token` = "Token không hợp lệ hoặc đã hết hạn." → hiển thị "Liên kết đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu liên kết mới." (token hết hạn sau 60 phút, và dùng một lần). 422 `password`: bắt buộc, tối thiểu 8 ký tự → "Mật khẩu phải có ít nhất 8 ký tự". BE không có quy tắc phức tạp hơn (chữ hoa/số).
- **API:** `POST /auth/password/reset`.
- **Dữ liệu mẫu gợi ý:** `phuhuynh.lan@gmail.com`; mật khẩu mới `Matkhau2026`.

### CM-04 — Chọn / đổi vai trò   ✅
- **Vai trò · thiết bị ưu tiên:** Tài khoản giữ từ 2 vai trò trở lên (Admin + Teacher phổ biến nhất, theo `product-definition.md`; hiếm hơn Teacher + Parent) · cả hai thiết bị.
- **Người dùng muốn làm gì:** Chọn "đang vào với tư cách ai", hoặc chuyển nhanh giữa Giảng dạy và Quản trị.
- **Vào từ / đi tới:** (a) màn chọn vai trò ngay sau đăng nhập khi `available_roles` có > 1 phần tử; (b) menu người dùng trong khung điều hướng (CM-10) mở danh sách đổi vai trò. Chọn xong → trang chủ của vai trò đó (tổng quan AD/TC/ST/PR); đổi vai trò giữa phiên cũng về trang chủ vai trò mới và làm mới dữ liệu (xóa cache của vai trò cũ).
- **Khối nội dung & dữ liệu hiển thị:** Danh sách thẻ vai trò từ `available_roles` (mã `admin|teacher|student|parent`) kèm nhãn Việt ("Quản trị viên", "Giáo viên", "Học sinh", "Phụ huynh") và 1 dòng mô tả ("Quản lý tài khoản, lớp, môn học", "Lịch dạy, điểm danh, chấm bài", "Lịch học, bài tập, kết quả", "Theo dõi việc học của con"). Đánh dấu vai trò đang hoạt động (từ `GET /auth/me` → `active_role`). Vai trò đã kết thúc hạn (valid_to đã qua) không có trong danh sách. Không bắt buộc ghi nhớ lựa chọn lần trước (FE có thể lưu cục bộ để gợi ý sẵn; chọn mặc định là việc FE).
- **Chức năng & hành động:** Chọn thẻ → `POST /auth/context` `{role_code}` → `{active_role}`. Chỉ 1 vai trò: không hiện màn chọn, FE gọi context ngầm. Vai trò đang hoạt động bấm lại: chỉ đóng.
- **Trạng thái cần vẽ:** tải danh sách (từ `/auth/me`) · đang đổi · lỗi đổi · không có vai trò nào (xem lỗi).
- **Quy tắc & lỗi nghiệp vụ:** 403 `ROLE_NOT_HELD`, message gốc "User does not currently hold this role." → hiển thị "Tài khoản của bạn không còn vai trò này." rồi tải lại `/auth/me` và danh sách. `available_roles` rỗng (tài khoản chưa được gán vai trò hoặc đã hết hạn hết): hiện "Tài khoản chưa được cấp quyền truy cập. Vui lòng liên hệ quản trị viên." + nút "Đăng xuất" (trạng thái này BE không trả mã riêng, FE tự nhận biết). 422 `role_code` nếu không thuộc 4 giá trị (không xảy ra với giao diện chuẩn).
- **API:** `GET /auth/me`, `POST /auth/context`.
- **Dữ liệu mẫu gợi ý:** `available_roles: ["admin","teacher"]`; thẻ "Giáo viên — Lịch dạy, điểm danh, chấm bài" (đang chọn).

### CM-05 — Hồ sơ cá nhân   ✅ (ảnh đại diện 🔵, đề xuất bỏ khỏi V1: N8)
- **Vai trò · thiết bị ưu tiên:** Teacher, Student, Parent xem/sửa đủ; Admin chỉ có phần tài khoản (xem dưới) · điện thoại cho Student/Parent.
- **Người dùng muốn làm gì:** Cập nhật số điện thoại để trung tâm liên hệ được, đổi mật khẩu, đăng xuất.
- **Vào từ / đi tới:** Menu người dùng (CM-10) → "Hồ sơ của tôi". "Đăng xuất" → CM-01. "Đổi vai trò" → CM-04.
- **Khối nội dung & dữ liệu hiển thị:**
  1. **Thông tin cá nhân** (`GET /me/profile` → `{person_profile:{id, full_name, email, phone}, role_profile:{id, status}|null}`): họ tên (chỉ đọc), email, số điện thoại (sửa được), trạng thái hồ sơ vai trò hiện tại (`role_profile.status`, chỉ đọc; `role_profile` là hồ sơ Student/Parent/Teacher theo vai trò đang hoạt động). Ghi chú: "Muốn đổi họ tên, hãy liên hệ trung tâm". Email ở đây là email trong hồ sơ cá nhân, KHÁC email đăng nhập (`login_identifier`) nên đổi email ở đây không đổi cách đăng nhập; cần nói rõ trên màn hình.
  2. **Tài khoản** (từ `GET /auth/me`): email đăng nhập (`user.login_identifier`, chỉ đọc), vai trò đang giữ (`available_roles`) và vai trò đang hoạt động.
  3. **Đổi mật khẩu**: ô "Mật khẩu hiện tại", "Mật khẩu mới" (≥ 8), "Nhập lại mật khẩu mới" (kiểm tra khớp ở FE).
  4. **Phiên**: nút "Đăng xuất".
- **Chức năng & hành động:** "Lưu" `PATCH /me/profile` `{email?, phone?}` (cả hai tùy chọn, có thể rỗng/null để xóa; chỉ gửi trường đã đổi) → trả `{person_profile}`. Gửi `full_name` hoặc `status` bị chặn (403 "Không thể tự sửa full_name hoặc status.") nên các ô này luôn chỉ đọc. "Đổi mật khẩu" `PATCH /me/password` `{current_password, new_password}` → 204/thành công; hủy mọi phiên khác của tài khoản (phiên hiện tại giữ nguyên) nên cần cảnh báo trước nút: "Đổi mật khẩu sẽ đăng xuất các thiết bị khác" (xác nhận nhẹ), và sau khi thành công xóa các ô mật khẩu. "Đăng xuất" `POST /auth/logout` (204) → xóa cache phía FE → CM-01; không cần xác nhận. Bất kỳ "chưa lưu" khi rời trang: hỏi xác nhận.
- **Hồ sơ Admin:** Tài khoản chỉ có vai trò Admin có thể không có `person_profile` (Admin ban đầu là tài khoản hệ thống). Khi đó `GET /me/profile` trả `person_profile: null` hoặc không phù hợp và `PATCH /me/profile` trả 404 `NO_PROFILE`. Màn hình cho Admin: ẩn khối 1, chỉ còn khối 2, 3, 4 và dòng "Tài khoản quản trị không có hồ sơ cá nhân". Nếu Admin đồng thời là Teacher (có hồ sơ cá nhân) thì hiện khối 1 như Teacher. Nhận biết bằng `person_profile === null` hoặc 404 `NO_PROFILE`; cần xác nhận khi dựng mã trả về chính xác của `GET /me/profile` với Admin (đã tìm ở `MyProfileQuery`: `person_profile` lấy từ `user->personProfile`, có thể null).
- **Trạng thái cần vẽ:** tải · đang lưu · lưu thành công (toast) · lỗi 422 theo trường · mật khẩu hiện tại sai · tài khoản Admin không có hồ sơ · lỗi mạng.
- **Quy tắc & lỗi nghiệp vụ:** 422 `email`: "Email không hợp lệ" (rule `email`); 422 `current_password` = "Mật khẩu hiện tại không đúng."; 422 `new_password` ≥ 8 ký tự → "Mật khẩu mới phải có ít nhất 8 ký tự"; 404 `NO_PROFILE` (xem trên); 403 khi gửi `full_name`/`status` (FE không gửi). Đổi mật khẩu không kiểm tra mật khẩu mới khác mật khẩu cũ.
- **API:** `GET /me/profile`, `PATCH /me/profile`, `PATCH /me/password`, `POST /auth/logout`, `GET /auth/me`.
- **Dữ liệu mẫu gợi ý:** Nguyễn Thị Lan · lan.nguyen@gmail.com · 0912 345 678 · vai trò Phụ huynh (đang hoạt động, hồ sơ "Đang hoạt động").

### CM-06 — Trung tâm thông báo   ✅ (đếm chưa đọc, đọc tất cả: 🟡 G10; phân trang/lọc ngày: 🟡 G5)
- **Vai trò · thiết bị ưu tiên:** Mọi vai trò · điện thoại (Student/Parent), máy tính (Admin/Teacher).
- **Người dùng muốn làm gì:** Biết có gì mới (bài mới giao, đổi lịch, đã có kết quả, đơn xin phép) và mở thẳng nội dung đó. V1 chỉ có thông báo trong ứng dụng (BR-NOTIF-001), nên đây là kênh duy nhất.
- **Vào từ / đi tới:** Chuông trên khung điều hướng (CM-10) → bảng nhỏ (5–10 mục mới nhất) hoặc "Xem tất cả" → trang CM-06. Bấm một thông báo → đánh dấu đã đọc và mở màn đích theo `context` (bảng bên dưới).
- **Khối nội dung & dữ liệu hiển thị:** (1) Tiêu đề + bộ lọc "Tất cả / Chưa đọc / Đã đọc" (`?read=false|true`) và nút "Đánh dấu tất cả đã đọc" (🟡 G10). (2) Danh sách mới nhất trước, mỗi dòng từ `GET /me/notifications` → `{data:[{id, event_type, title, body, created_at, read_at, context:{type,id}|null}]}`: chấm chưa đọc (`read_at === null`), biểu tượng theo `event_type`, `title`, `body`, thời gian tương đối (từ `created_at`, giờ VN). Không có phân trang nên tải hết (🟡 G5). Thông báo là kéo về: không tự cập nhật; làm mới khi mở trang, khi quay lại ứng dụng, hoặc bấm "Làm mới".
- **Chức năng & hành động:**
  - Bấm dòng: `PATCH /notifications/{id}/read` (trả `{notification:{id, recipient_user_id, event_key, event_type, title, body, created_at, read_at}}`; lặp lại vô hại) rồi điều hướng. Đã đọc rồi vẫn điều hướng như cũ.
  - Nếu người dùng đã mất quyền xem đích (BR-NOTIF-006/007: thông báo không cấp quyền), màn đích trả 403/404 "Request failed." → hiện CM-07 dạng "Nội dung không còn khả dụng".
  - Đếm huy hiệu chuông: hiện chưa có endpoint đếm; ghép tạm bằng `GET /me/notifications?read=false` rồi đếm `data.length` (mỗi lần mở app và theo chu kỳ, vd 60 giây, không realtime). 🟡 G10.
  - "Đánh dấu tất cả đã đọc": chưa có endpoint (🟡 G10); ghép tạm gọi `PATCH …/read` tuần tự cho mọi mục chưa đọc (chậm khi nhiều; chỉ dùng tạm).
- **Cách mở màn đích (theo `context.type`; một thông báo có tối đa một ngữ cảnh):**

| `event_type` | `context.type` | Người nhận (điển hình) | Mở tới |
|---|---|---|---|
| `assessment_assigned` | `assessment` | Student, (Parent liên kết) | Student: ST-05 chi tiết bài (`/me/assessments/{id}`). Parent: PR-05 chi tiết bài của con, cần biết `studentId` (thông báo không có; chọn con đang xem hoặc lùi về danh sách bài của con) |
| `result_published` | `result` | Student, (Parent) | Student: ST-07 kết quả. Hiện `context.id` là id kết quả (`result`), không phải id bài, mà `GET /me/assessments/{id}/result` cần id bài: 🟡 thiếu `assessment_id` (và `student_id` cho Parent) trong context. Ghép tạm: mở danh sách kết quả ST-07, người dùng tự chọn |
| `lesson_session_updated`, `lesson_session_cancelled` | `lesson_session` | Học sinh, giáo viên lớp (và Parent) | Mọi vai trò xem buổi: chi tiết buổi học (`GET /lesson-sessions/{id}`): TC-05 (Teacher/Admin), ST-02 (Student), PR-03 (Parent). Buổi hủy hiển thị trạng thái "đã hủy" |
| `schedule_updated`, `schedule_cancelled` | `schedule` | Học sinh, giáo viên lớp | Chưa có `GET /schedules/{id}` và context không có `class_id`: 🟡 thiếu. Ghép tạm: mở màn lịch (ST-02/TC-04/PR-03) ở tuần hiện tại |
| `absence_explanation_submitted` | `lesson_session` | Giáo viên lớp (cần duyệt) | TC-06 đơn xin phép (lọc chờ duyệt) hoặc TC-05 buổi đó |
| `absence_explanation_reviewed` | `lesson_session` | Phụ huynh/Học sinh gửi đơn | PR-08 trạng thái đơn của con; `body` hiện "Trạng thái: {status}" (giá trị thô tiếng Anh, vd `accepted`/`rejected` — FE nên map sang tiếng Việt: "Đã chấp nhận"/"Đã từ chối") |

  `context` có thể là `null` (thông báo chỉ để đọc, không dẫn đi đâu). Loại `event_type` lạ (sau này thêm): hiển thị thường, không có biểu tượng riêng và không điều hướng.
- **Trạng thái cần vẽ:** tải · rỗng (lọc "Chưa đọc": "Bạn đã xem hết thông báo"; không có thông báo nào: "Chưa có thông báo. Khi có bài mới, đổi lịch hoặc kết quả, bạn sẽ thấy ở đây") · lỗi (thử lại) · đang đánh dấu · đích không khả dụng.
- **Quy tắc & lỗi nghiệp vụ:** 404 "Request failed." khi `PATCH …/read` thông báo của người khác (policy ẩn thành 404) → làm mới danh sách. Tham số `read` chỉ nhận chuỗi `true`/`false`, giá trị khác bị bỏ qua (trả tất cả).
- **API:** `GET /me/notifications?read=`, `PATCH /notifications/{id}/read`; 🟡 G10 (đếm chưa đọc, đọc tất cả); 🟡 G5 (phân trang, lọc ngày); 🟡 thiếu context đầy đủ cho `result`/`schedule` (không nằm trong G1–G14, xem báo cáo).
- **Dữ liệu mẫu gợi ý:** "Bạn được giao bài mới — Có một bài kiểm tra/bài tập mới được giao cho bạn." (hôm nay 16:05, chưa đọc); "Buổi học đã bị hủy — Một buổi học của lớp bạn tham gia vừa bị hủy." (hôm qua); "Kết quả bài làm đã được công bố" (3 ngày trước, đã đọc).

### CM-07 — Trang lỗi   ✅ (thiết kế)
- **Vai trò · thiết bị ưu tiên:** Mọi vai trò · cả hai.
- **Người dùng muốn làm gì:** Hiểu chuyện gì xảy ra và có lối ra rõ ràng (không phải "ngõ cụt").
- **Vào từ / đi tới:** FE chuyển tới khi API trả lỗi toàn trang, hoặc đường dẫn không tồn tại. Lối ra: "Về trang chủ" (theo vai trò hiện tại), "Thử lại", "Đăng nhập lại", "Đổi vai trò".
- **Khối nội dung & dữ liệu hiển thị:** Biểu tượng + tiêu đề + 1–2 dòng giải thích + nút hành động; mã lỗi nhỏ ở cuối (cho người dùng đọc cho Admin khi cần hỗ trợ). Biến thể:

| Biến thể | Khi nào | Nội dung đề xuất | Nút |
|---|---|---|---|
| Hết phiên | 401 `UNAUTHENTICATED` | "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại." | Đăng nhập (về CM-01, nhớ đường dẫn cũ) |
| Không có quyền / không tìm thấy | 403 / 404 (message "Request failed.") | "Không tìm thấy nội dung hoặc bạn không có quyền xem." (BE cố ý không phân biệt hai trường hợp, để không lộ dữ liệu tồn tại) | Về trang chủ; nếu có thể có vai trò khác: "Đổi vai trò" (CM-04) |
| Sai vai trò | 403 `FORBIDDEN` "Bạn không có quyền thực hiện hành động này." (đang ở vai trò không được dùng chức năng) hoặc đường dẫn của vai trò khác | "Chức năng này không dành cho vai trò đang chọn" | Đổi vai trò (nếu có >1), Về trang chủ |
| Tài khoản bị vô hiệu giữa phiên | 403 `ACCOUNT_INACTIVE`, message gốc "This account has been deactivated." | "Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ trung tâm." Không có nút thử lại; FE xóa cache phiên | Đăng xuất / Về đăng nhập |
| Lỗi máy chủ | 500 `ERROR`, hoặc mạng đứt | "Có lỗi xảy ra phía hệ thống. Hãy thử lại sau ít phút." | Thử lại, Về trang chủ |
| Quá tải | 429 `TOO_MANY_REQUESTS` | "Bạn thao tác quá nhanh, vui lòng đợi một lát." | Thử lại |
| Không có đường dẫn | Route FE không tồn tại | Như 404 | Về trang chủ |

- **Chức năng & hành động:** Chỉ điều hướng/thử lại. `ACCOUNT_INACTIVE` có thể xuất hiện ở BẤT KỲ lời gọi sau đăng nhập (vì `EnsureUserIsActive` chạy trên mọi route đã xác thực): bộ chặn lỗi chung của FE phải xử lý ở một chỗ.
- **Trạng thái cần vẽ:** các biến thể nêu trên (mỗi biến thể một khung). Lỗi cục bộ của một khối (vd danh sách không tải được) KHÔNG dùng trang này mà dùng trạng thái lỗi có nút "Thử lại" ngay trong khối.
- **Quy tắc & lỗi nghiệp vụ:** Xem bảng. 422 không dùng trang lỗi (hiện ở trường).
- **API:** — (không có endpoint riêng).
- **Dữ liệu mẫu gợi ý:** Mã nhỏ: `NOT_FOUND` · 22/10/2026 17:41.

### CM-08 — Xem / tải file   ✅ (G6 cho file bài làm/feedback)
- **Vai trò · thiết bị ưu tiên:** Admin, Teacher (mọi file hợp lệ theo quyền), Student, Parent (chỉ file theo `visibility`) · điện thoại cho Student/Parent.
- **Người dùng muốn làm gì:** Mở/tải đề bài PDF, hình minh họa của câu hỏi.
- **Vào từ / đi tới:** Không phải màn riêng mà là thành phần "tệp đính kèm" xuất hiện ở ST-05 (chi tiết bài), ST-06 (câu hỏi), PR-05 (bài của con), TC-08/TC-10 (soạn). Bấm tên file → mở/tải. Không có trang "kho file".
- **Khối nội dung & dữ liệu hiển thị:** Thẻ tệp: biểu tượng theo loại, `file_name`, `size_bytes` đổi sang KB/MB, `media_type`. Thông tin này lấy từ chính màn chứa file (dữ liệu của câu hỏi/bài đính kèm); `GET /attachments/{id}` trả thêm `attachment:{id, file_name, media_type, size_bytes, status, created_at, checksum, storage_key, uploaded_by_user_id, links}` và `download_url` (FE chỉ dùng `file_name`, `media_type`, `size_bytes`, `download_url`). Ảnh jpg/png: xem trước (lightbox); PDF: mở tab mới; doc/docx/zip: tải về.
- **Chức năng & hành động:** Bấm thẻ → gọi `GET /attachments/{id}` ngay lúc bấm (không gọi sẵn cả loạt) → mở `download_url` (URL ký sẵn, **hết hạn sau 10 phút**, nên không lưu lại hay chép link; mỗi lần bấm lấy link mới). Nếu link hết hạn lúc tải (lỗi 403 từ kho lưu trữ, không phải API): bấm lại để lấy link mới (FE hiển thị "Liên kết tải đã hết hạn, đang tạo lại…" rồi tự thử một lần). Với Student/Parent chỉ thấy tệp khi `visibility` cho phép: `learner` (Student xem; Parent không), `parent_if_published` (Student và Parent xem), `private` (không ai ngoài Teacher/Admin có quyền); đồng thời phải đang được giao bài đó (đã giao, bài đang `published`/`closed`, đã đến giờ mở). Vì vậy FE không phải lọc: tệp không được xem trả 404. Teacher: câu hỏi thì xem được; đề bài thì chỉ khi là người tạo hoặc dạy lớp được giao. Admin: tất cả. Xóa/gắn tệp không thuộc màn này (TC-08/TC-10).
- **Trạng thái cần vẽ:** thẻ bình thường · đang lấy liên kết · tải thất bại (thử lại) · link hết hạn (tự lấy lại) · tệp không còn khả dụng (404) · ảnh không xem trước được (rơi về nút tải). Giới hạn đã chốt: tối đa 10 MB; pdf, doc/docx, jpg, png, zip (kiểm tra ở lúc tải lên, không ở màn xem).
- **Quy tắc & lỗi nghiệp vụ:** 404 "Request failed." (không có quyền hoặc không tồn tại, cùng thông điệp) → "Không tìm thấy tệp hoặc bạn không có quyền xem". `status` khác `ready` (`uploading`/`failed`/`archived`): BE vẫn trả link; FE nên chỉ hiện tệp `ready` (cần xác nhận khi dựng). Student/Parent không có quyền tải lên/xóa (chỉ route xem). File bài làm của học sinh và file trong feedback chưa dùng được: 🟡 G6.
- **API:** `GET /attachments/{id}`; (tải lên/gắn/xóa ở Teacher/Admin: `POST /attachments`, `POST /attachments/{id}/link`, `DELETE /attachments/{id}`).
- **Dữ liệu mẫu gợi ý:** `De-kiem-tra-Toan-9A-tuan-5.pdf` · 1,2 MB · application/pdf; `Hinh-minh-hoa-cau-3.png` · 340 KB.

### CM-09 — Kích hoạt tài khoản lần đầu   🟡 G11 (thêm mới)
- **Vai trò · thiết bị ưu tiên:** Học viên, phụ huynh, giáo viên vừa được mời · điện thoại (mở từ email).
- **Người dùng muốn làm gì:** Lần đầu vào hệ thống sau khi trung tâm gửi lời mời: đặt mật khẩu và bắt đầu dùng.
- **Vào từ / đi tới:** Liên kết trong thư mời. Hiện BE dùng chung cơ chế với đặt lại mật khẩu: Admin bấm "Mời" (`POST /person-profiles/{id}/invite`, AD-05) → tạo tài khoản + gửi thư "Đặt lại mật khẩu PiClass" có liên kết `/reset-password?token&login_identifier` (cùng URL và cùng văn bản với quên mật khẩu). Màn kích hoạt riêng cần BE phân biệt lời mời (vd `/activate?token=…`). Thành công → CM-01 (hoặc tự đăng nhập, xem câu hỏi mở).
- **Khối nội dung & dữ liệu hiển thị:** Khác CM-03 ở văn bản: "Chào mừng đến với PiClass" + "Tài khoản của bạn đã được trung tâm tạo. Hãy đặt mật khẩu để bắt đầu"; hiển thị email đăng nhập (chỉ đọc); tên (nếu BE trả được; hiện thì không); ô "Mật khẩu" (≥ 8) và "Nhập lại mật khẩu"; (tùy) tick "Tôi đồng ý điều khoản sử dụng" (nội dung điều khoản chưa có trong requirements: cần xác nhận). Nút "Kích hoạt tài khoản".
- **Chức năng & hành động:** Hiện chạy tạm bằng cùng `POST /auth/password/reset` như CM-03 nhưng với văn bản kích hoạt (FE không phân biệt được lời mời với đặt lại, nên nếu dùng chung URL thì chỉ có thể dùng văn bản trung tính "Đặt mật khẩu cho tài khoản PiClass"). Đề xuất BE (G11): endpoint kích hoạt riêng hoặc tham số phân loại trong liên kết; thời hạn lời mời dài hơn 60 phút (vd 7 ngày vì người nhận có thể mở thư trễ); "Gửi lại lời mời" cho Admin (nay mời lần hai bị `409 ALREADY_INVITED`) và cho phép Admin gửi link hộ.
- **Trạng thái cần vẽ:** form · đang gửi · thành công ("Tài khoản đã sẵn sàng" + nút Đăng nhập) · **liên kết hết hạn** (tiêu đề "Lời mời đã hết hạn", giải thích "Liên kết mời chỉ có hiệu lực trong thời gian ngắn", nút "Nhận liên kết mới" → CM-02 tạm thời; đề xuất sau G11: liên hệ trung tâm để được gửi lại) · liên kết đã dùng/không hợp lệ · tài khoản đã kích hoạt (mở lại liên kết cũ → gợi ý "Đăng nhập" hoặc "Quên mật khẩu") · lỗi mạng.
- **Quy tắc & lỗi nghiệp vụ:** Như CM-03 (422 `token` "Token không hợp lệ hoặc đã hết hạn."; 422 `password` ≥ 8). Tài khoản mời có mật khẩu ngẫu nhiên 32 ký tự (không ai biết), nên cho đến khi đặt mật khẩu không thể đăng nhập; thử đăng nhập sẽ nhận "Thông tin đăng nhập không đúng.". Trạng thái tài khoản ngay khi mời đã là `active` (BE không có trạng thái "chờ kích hoạt"), nên Admin không phân biệt được ai chưa kích hoạt ở AD-02/AD-03: nếu cần, đề xuất `last_login_at`/cờ chưa kích hoạt trong danh sách (🟡 cần xác nhận với G11; không có trường này trong response hiện tại).
- **API:** `POST /auth/password/reset` (dùng tạm); 🟡 G11: kích hoạt riêng, gửi lại lời mời, Admin gửi link hộ, thời hạn lời mời.
- **Dữ liệu mẫu gợi ý:** Email `hs.minhanh@gmail.com`; tiêu đề "Chào mừng em Nguyễn Minh Anh (Toán 9A)" nếu BE trả được tên.

### CM-10 — Khung điều hướng (app shell)   ✅ + 🔵 gộp Admin/Teacher (Q6 chưa chốt) (thêm mới)
- **Vai trò · thiết bị ưu tiên:** Mọi vai trò đã đăng nhập · máy tính: thanh bên + thanh trên; điện thoại: thanh dưới (3–5 mục chính) + ngăn kéo.
- **Người dùng muốn làm gì:** Đi tới đúng chức năng của vai trò mình, biết có thông báo mới, đổi vai trò hoặc đăng xuất ở cùng một chỗ.
- **Vào từ / đi tới:** Bao quanh mọi màn sau đăng nhập. Logo → trang chủ vai trò đang chọn.
- **Khối nội dung & dữ liệu hiển thị:**
  1. **Menu theo vai trò** (đổi theo `active_role` từ `/auth/me`):
     - Admin: Tổng quan (AD-01) · Tài khoản (AD-02) · Học viên, Phụ huynh, Giáo viên (AD-04…07) · Môn học (AD-08) · Lớp học (AD-09/10) · Lịch (AD-11) · Khóa học (AD-12) · Nhật ký (AD-14).
     - Teacher: Tổng quan (TC-01) · Lớp của tôi (TC-02) · Lịch dạy (TC-04) · Đơn xin phép (TC-06) · Ngân hàng câu hỏi (TC-07) · Bài đánh giá (TC-09) · Khóa học (TC-15). Tiến độ học sinh vào từ chi tiết lớp.
     - Student: Tổng quan (ST-01) · Lịch học (ST-02) · Bài tập (ST-03) · Bài kiểm tra (ST-04) · Kết quả (ST-07) · Tiến độ (ST-08) · Khóa học (ST-09). Điện thoại: thanh dưới gồm Tổng quan, Lịch, Bài tập, Kết quả, "Thêm".
     - Parent: Chọn con (PR-01, kèm tên con đang xem luôn hiện ở thanh trên) · Tổng quan (PR-02) · Lịch (PR-03) · Điểm danh (PR-04) · Bài tập & kết quả (PR-05) · Nhận xét (PR-06) · Tiến độ (PR-07) · Đơn xin phép (PR-08).
  2. **Thanh trên:** tiêu đề màn; chuông thông báo + huy hiệu số chưa đọc (CM-06; tạm đếm bằng `?read=false`, 🟡 G10; quá 99 hiện "99+"); menu người dùng gồm tên (`person_profile.full_name` từ `GET /me/profile`; Admin không có hồ sơ → dùng `login_identifier`), nhãn vai trò hiện tại, "Hồ sơ của tôi" (CM-05), "Đổi vai trò" (chỉ khi `available_roles.length > 1`, mở CM-04), "Đăng xuất".
  3. **Vùng nội dung** + biểu ngữ trạng thái toàn cục (mất kết nối, phiên sắp hết).
- **Chức năng & hành động:**
  - Mục menu chỉ hiện theo vai trò đang chọn (việc ẩn hiện chỉ là tiện lợi; quyền thật do BE: 403 `FORBIDDEN` nếu gọi sai vai trò).
  - Tài khoản giữ cả Admin và Teacher (🔵 Q6, theo khuyến nghị "một khung điều hướng"): khung có hai nhóm "Giảng dạy" (menu Teacher) và "Quản trị" (menu Admin) cùng hiện. BE chỉ nhận một `active_role` mỗi thời điểm và route chia nhóm `role:` nên bấm vào mục thuộc nhóm khác vai trò hiện tại cần gọi `POST /auth/context` ngầm trước (đổi sang `teacher`/`admin` tương ứng) rồi mới tải màn; dữ liệu hiển thị theo vai trò ở thời điểm tải (vd lớp của Teacher khác tất cả lớp của Admin). Lựa chọn thay thế (nếu không chốt Q6): công tắc "Đang dùng: Quản trị / Giảng dạy" ở menu người dùng, đổi là về trang chủ vai trò mới. Cần user chốt trước khi dựng.
  - Đổi vai trò: gọi `POST /auth/context`, xóa cache, về trang chủ vai trò mới.
  - Đăng xuất: `POST /auth/logout`, xóa cache.
  - Mọi lời gọi 401 → CM-07 "Hết phiên" rồi CM-01; `ACCOUNT_INACTIVE` → CM-07 biến thể vô hiệu. Làm mới huy hiệu định kỳ (không realtime).
- **Trạng thái cần vẽ:** đang khởi tạo (gọi `/auth/me` trước khi vẽ menu) · bình thường · chưa chọn vai trò (`active_role` null → chuyển CM-04) · mất mạng · huy hiệu 0 / có số / 99+ · menu thu gọn/mở rộng.
- **Quy tắc & lỗi nghiệp vụ:** `ROLE_NOT_HELD` khi đổi (vai trò vừa bị gỡ) → tải lại `/auth/me`; nếu vai trò hiện tại vừa mất thì đưa về CM-04. Parent chưa có con liên kết: menu chỉ giữ "Chọn con" kèm lời mời "Chưa có con nào được liên kết. Hãy liên hệ trung tâm" (ghép với PR-01).
- **API:** `GET /auth/me`, `POST /auth/context`, `GET /me/profile` (tên), `GET /me/notifications?read=false` (huy hiệu), `POST /auth/logout`; 🟡 G10 (đếm).
- **Dữ liệu mẫu gợi ý:** Cô Hương (Admin + Teacher), nhóm "Giảng dạy": Lớp của tôi, Lịch dạy · nhóm "Quản trị": Tài khoản, Lớp học; chuông "3".

---

## Câu hỏi mở / cần xác nhận khi dựng
1. Q6: chọn khung gộp hai nhóm "Giảng dạy / Quản trị" hay công tắc đổi vai trò (CM-10).
2. Kích hoạt tài khoản (CM-09): dùng chung màn đặt lại mật khẩu tạm thời hay chờ G11; thời hạn lời mời; tự đăng nhập sau khi đặt mật khẩu.
3. `login_identifier` có luôn là email không (CM-01/02/09); nếu không, nhãn ô là "Tên đăng nhập hoặc email".
4. Mã trả về chính xác của `GET /me/profile` với Admin không có hồ sơ (CM-05).
5. Nội dung thông báo `result_published` và `schedule_*` thiếu id để mở đích (CM-06).
