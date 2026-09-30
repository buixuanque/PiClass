# Tổng quan hệ thống

## Bối cảnh

PiClass là hệ thống quản lý hoạt động dạy học cho tutoring class. V1 hỗ trợ Admin, Teacher, Student và Parent qua các cổng theo role; một User có thể đồng thời mang nhiều role. V1 phục vụ lớp hiện tại nhưng dữ liệu và scope có thể chứa nhiều teacher, class, student.

## Thành phần ứng dụng

### Web client

Giao diện web cho các nhóm người dùng. Admin/Teacher làm công việc quản trị và giảng dạy; Student làm và nộp bài, theo dõi lịch/kết quả; Parent xem dữ liệu child được liên kết và công bố. Một người có nhiều role chọn context sau đăng nhập. Client không tự quyết định quyền truy cập; dữ liệu chỉ hiển thị sau phản hồi được backend cho phép.

Cách đóng gói thành một frontend hay nhiều frontend cùng dùng backend: `[CẦN QUYẾT ĐỊNH]`.

### Backend/API application

Một backend application modular monolith chịu trách nhiệm authentication, authorization, validation, use case, domain rules và kết nối hạ tầng. API contract thuộc Step 9. Backend là nơi bắt buộc kiểm tra User active, role/action permission, data scope, relationship, lifecycle và visibility cho cả list lẫn truy cập một record.

### PostgreSQL

Nguồn dữ liệu nghiệp vụ V1 đã chốt ở Step 6. Backend truy cập qua persistence/infrastructure boundary. Database chứa các entity theo schema Step 6, bao gồm dữ liệu nghiệp vụ và lịch sử; không thêm Progress table riêng V1.

### File storage

Lưu nội dung tệp ngoài relational database theo abstraction. Metadata, ownership/context, visibility và trạng thái lưu tại hệ thống theo Database Design. Vendor/chế độ triển khai: `[CẦN QUYẾT ĐỊNH]`.

### Notification

Thông báo trong ứng dụng V1 được tạo từ sự kiện nghiệp vụ, lưu theo recipient/context, hiển thị qua web client. Notification không cấp quyền mở nội dung được liên kết. Email, SMS và LINE không thuộc delivery V1 đã chốt.

### Audit

Các hành động nhạy cảm tạo AuditLog append-only cùng business history chuyên biệt khi rule yêu cầu. Admin được xem toàn bộ; Teacher chỉ xem lịch sử nghiệp vụ trong class scope. Student/Parent không có quyền xem AuditLog.

## External services

Không có external business service bắt buộc trong V1 đã chốt. Object/file storage là hạ tầng ngoài tiến trình backend nếu được chọn, nhưng vendor còn mở. Email/SMS, payment, AI, video/live class và chat không được đưa vào sơ đồ V1.

## Ranh giới triển khai logic

```text
Web client → Backend/API modular monolith → PostgreSQL
                                      ├→ File storage abstraction
                                      └→ In-app notifications (DB-backed)
```

V1 không cần message broker hay microservices theo tài liệu hiện có. Tác vụ nền như gửi/hiển thị notification hoặc dọn file mồ côi chỉ được bổ sung ở deployment khi yêu cầu kỹ thuật cần; cách chạy cụ thể còn mở.
