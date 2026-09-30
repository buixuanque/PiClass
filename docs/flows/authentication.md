# Xác thực và tài khoản

## 1. Mục đích
Cho phép người dùng truy cập hệ thống an toàn, quản lý hồ sơ và trạng thái tài khoản.

## 2. Actor
Admin, Teacher, Student, Parent

## 3. Điều kiện trước
Người dùng có tài khoản; tài khoản đang hoạt động (trừ luồng kích hoạt).

## 4. Trigger
Đăng nhập, đăng xuất, yêu cầu đặt lại/đổi mật khẩu, hoặc Admin tạo và cập nhật user.

## 5. Luồng chính
1. Người dùng nhập thông tin đăng nhập và gửi yêu cầu.
2. Hệ thống xác thực thông tin và trạng thái tài khoản.
3. Nếu hợp lệ, người dùng vào khu vực phù hợp với quyền được cấp.
4. Khi đăng xuất, phiên hiện tại kết thúc.
5. Khi quên mật khẩu, người dùng yêu cầu đặt lại; hệ thống gửi hướng dẫn qua kênh liên hệ đã xác minh.
6. Admin tạo user, gán một hoặc nhiều role, liên kết hồ sơ nghiệp vụ nếu cần và gửi hướng dẫn kích hoạt.
7. Admin hoặc người có quyền cập nhật hồ sơ, kích hoạt/vô hiệu hóa user; user có thể xem và sửa thông tin cá nhân được phép.

## 6. Luồng thay thế
1. User có nhiều role chọn vai trò/khu vực làm việc phù hợp. [CẦN QUYẾT ĐỊNH] Có cần chọn role sau đăng nhập hay hệ thống tự điều hướng?
2. Đặt lại mật khẩu bằng liên kết/mã xác minh; user đăng nhập lần đầu được yêu cầu tạo mật khẩu mới. [CẦN QUYẾT ĐỊNH] Hạn dùng mã, độ mạnh mật khẩu, khóa tạm sau đăng nhập sai và chính sách phiên.

## 7. Trường hợp lỗi / ngoại lệ
1. Thông tin sai, tài khoản vô hiệu hóa hoặc chưa kích hoạt thì không cấp quyền truy cập; thông báo không tiết lộ thông tin nhạy cảm.
2. Liên kết/mã đặt lại hết hạn hoặc đã dùng thì yêu cầu gửi lại.
3. Admin không được vô hiệu hóa tài khoản cuối cùng có quyền quản trị nếu điều đó làm hệ thống không thể quản lý. [CẦN QUYẾT ĐỊNH] Có áp dụng quy tắc này không?

## 8. Kết quả cuối cùng
Người dùng đăng nhập được đúng quyền, phiên kết thúc khi đăng xuất, hoặc tài khoản/mật khẩu được cập nhật; thay đổi quyền có hiệu lực theo chính sách phiên.

## 9. Business Rules
1. Role và permission áp dụng nhất quán ở mọi khu vực.
2. Vô hiệu hóa tài khoản ngăn đăng nhập và truy cập mới.
3. Không xóa lịch sử học tập chỉ vì user bị vô hiệu hóa.
4. Kênh khôi phục tài khoản và cách xử lý user không có email/điện thoại: [CẦN QUYẾT ĐỊNH].
