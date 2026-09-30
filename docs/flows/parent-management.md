# Quản lý phụ huynh và liên kết với học viên

## 1. Mục đích
Quản lý hồ sơ Parent và xác lập quyền theo dõi đúng học viên được liên kết.

## 2. Actor
Admin; Parent xem hồ sơ và học viên đã liên kết.

## 3. Điều kiện trước
Người thực hiện đã đăng nhập; Admin có quyền quản lý người dùng.

## 4. Trigger
Admin tạo/cập nhật Parent hoặc liên kết/bỏ liên kết học viên.

## 5. Luồng chính
1. Admin tạo hoặc mở hồ sơ Parent và cập nhật thông tin liên hệ.
2. Admin tìm học viên, xác nhận quan hệ và liên kết Parent với một hay nhiều Student.
3. Admin kiểm tra danh sách học viên đã liên kết.
4. Parent đăng nhập và chỉ xem dữ liệu học viên được liên kết.
5. Khi quan hệ kết thúc, Admin gỡ liên kết; lịch sử trước đó vẫn thuộc hồ sơ học viên.

## 6. Luồng thay thế
1. Một học viên có thể liên kết nhiều Parent và một Parent theo dõi nhiều học viên. [CẦN QUYẾT ĐỊNH] Giới hạn và loại quan hệ cần ghi nhận.
2. Parent chưa có tài khoản vẫn được lưu hồ sơ rồi mời kích hoạt sau.

## 7. Trường hợp lỗi / ngoại lệ
1. Không cho liên kết nhầm học viên đã bị lưu trữ nếu chính sách không cho phép.
2. Parent không được tự xem học viên chưa liên kết.
3. Không thể gỡ liên kết nếu thao tác gây mất quyền giám sát cần thiết. [CẦN QUYẾT ĐỊNH] Có yêu cầu ít nhất một Parent không?

## 8. Kết quả cuối cùng
Hồ sơ Parent và liên kết được cập nhật; quyền truy cập cổng Parent phản ánh liên kết hiện hành.

## 9. Business Rules
1. Quyền Parent giới hạn theo liên kết Parent–Student.
2. Việc gỡ liên kết không xóa dữ liệu học tập và thông báo đã phát sinh.
3. Cách xác minh quan hệ và lưu loại quan hệ: [CẦN QUYẾT ĐỊNH].
