# Authentication và bảo mật

## Authentication

Authentication xác minh User là ai; Authorization quyết định User được làm gì, trên record nào và trong lifecycle nào. Hai việc này có boundary riêng nhưng phối hợp ở backend.

V1 cần các khả năng nghiệp vụ đã nêu: login, logout, quên/đặt lại/đổi mật khẩu và quản lý phiên. User inactive bị chặn truy cập mới; reset mật khẩu chỉ qua kênh liên hệ đã xác minh. User có nhiều role chọn context sau login. Cách lưu credentials, session/token, logout/revocation và transport còn `[CẦN QUYẾT ĐỊNH]`; xem `technology-decisions.md`.

## Authorization integration với Step 7

Mỗi request cần thỏa đồng thời:

1. User active và đã xác thực.
2. Có role/context phù hợp với action/resource.
3. Resource thuộc data scope hiện hành của role.
4. Relationship có hiệu lực (ClassMember, TeacherClassAssignment, ParentStudentRelationship) khi nghiệp vụ yêu cầu.
5. Lifecycle, thời điểm, audience và visibility cho phép.

Cùng một scope áp dụng cho list và xem một record; ID/URL không cấp quyền. Teacher scope theo assignment hiện hành; Student self-scope; Parent theo child đang liên kết và publication visibility; Admin có scope hệ thống nhưng không được xóa lịch sử/AuditLog. Nhiều role không tạo bypass; action dùng role context hợp lệ.

Backend kiểm tra quyền cho mọi request API và file. Frontend route guard chỉ giúp điều hướng và không phải security boundary. Notification không cấp quyền target.

## Password và session security

- Không lưu mật khẩu dạng rõ; dùng cơ chế password hashing phù hợp của framework/provider sau khi chọn stack.
- Không ghi password, secret, reset token hoặc credential vào application log.
- Reset password phải ràng buộc vào kênh liên hệ đã xác minh; token/flow có thời hạn và dùng một lần là quyết định implementation cần chốt.
- Giới hạn thử đăng nhập, chống dò tài khoản và yêu cầu độ mạnh password cần policy ở Step 9/Development Plan hoặc quyết định trước implementation.
- MFA không có trong yêu cầu V1; không tự thêm như prerequisite.
- Session cookie hay bearer token, thời hạn, rotation, CSRF và session concurrency cần được chọn cùng framework/auth design.

## Tệp và dữ liệu nhạy cảm

- File được phục vụ private theo mặc định về mặt kiến trúc; backend kiểm tra quyền object/context và `AttachmentLink.visibility` trước khi upload/download.
- Không dùng public, đoán được URL làm quyền truy cập. Cách cấp quyền truy cập storage tạm thời phụ thuộc provider và cần quyết định.
- Student/Parent chỉ thấy tệp trong object/context mà họ có quyền; Parent không xem submission private.
- Giữ file còn reference; cleanup orphan theo policy vận hành/pháp lý, chưa ấn định retention/dung lượng/loại file tại kiến trúc.
- Chỉ thu thập dữ liệu hồ sơ cần vận hành theo Step 5; tránh đưa nội dung học sinh/credentials vào log không cần thiết.

## Audit và lịch sử

AuditLog append-only. Các event nhạy cảm theo Step 5/6/7 cần actor, action, object, time và reason/before-after khi rule yêu cầu. Business history chuyên biệt vẫn được lưu ở AttendanceHistory, GradeHistory, versions và relationship history. Audit quyền xem: Admin toàn hệ thống; Teacher theo class scope; Student/Parent không xem.

## Bảo vệ giao tiếp và cấu hình

Production/staging traffic cần được bảo vệ khi truyền; keys/passwords/secrets không được commit vào source. Secrets tách theo environment, quyền truy cập tối thiểu. HTTPS, secure headers, CORS/origin policy và data encryption at rest được chọn theo deployment; host/vendor cụ thể chưa chốt.

## Nguyên tắc V1

Áp dụng least privilege, validate input, xử lý lỗi không lộ secret, server-side authorization, private file access, append-only audit và history bảo toàn. Không bổ sung multi-tenant organization/SSO/MFA hay security service phức tạp khi chưa có yêu cầu; nếu mở rộng nhiều trung tâm sau này cần review tenancy/scope như quyết định mới.
