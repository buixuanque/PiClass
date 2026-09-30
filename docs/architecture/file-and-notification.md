# Kiến trúc File và Notification

## File

### Logical components

- **File module** nhận yêu cầu upload/download từ các module nghiệp vụ và kiểm tra user/action/scope/context.
- **Attachment metadata** lưu trong PostgreSQL theo schema Step 6: storage key, original name, media type, size, trạng thái, thời điểm và checksum nếu provider hỗ trợ.
- **AttachmentLink** liên kết tệp với ngữ cảnh nghiệp vụ có FK cụ thể và `visibility`; không dựa vào ID tùy ý để cấp quyền.
- **Storage adapter** lưu nội dung nhị phân ngoài DB qua interface thống nhất. Object storage provider/host `[CẦN QUYẾT ĐỊNH]`.

### Flow

1. Module nghiệp vụ yêu cầu gắn file trong context hợp lệ.
2. Backend xác minh permission và điều kiện loại file/context theo policy được chọn.
3. Nội dung được lưu qua storage adapter; metadata và link context được ghi vào PostgreSQL.
4. Khi đọc/tải, backend re-check quyền đối tượng và visibility. Parent unlink/result withdraw có hiệu lực ngay ở authorization check.
5. File còn được tham chiếu không bị xóa. File orphan được cleanup theo policy vận hành/pháp lý, không đặt thời hạn tại Step 8.

Không lưu file binary lớn trực tiếp trong PostgreSQL theo giả định kiến trúc; đây là đề xuất kỹ thuật và cần được xác nhận nếu constraint triển khai khác. Kích thước tối đa, loại file, malware scanning, direct upload hay upload qua backend còn cần chốt trước implementation. File scan chỉ là quyết định vận hành/security, không thêm feature người dùng.

## Notification

### V1 channel

Notification V1 là in-app. Backend lưu notification theo recipient và event; context có FK cụ thể tới Assessment, LessonSession, Result hoặc Schedule. Unique recipient/event key chống tạo trùng. Read/unread thuộc từng recipient. Email/SMS/LINE không nằm trong V1 theo Feature Map/Requirements.

### Flow và quyền

1. Module nghiệp vụ phát sinh event, ví dụ Assessment publish, Result publish, Schedule change/cancel, attendance hoặc deadline theo rule.
2. Notification module xác định recipient phù hợp theo business rule, role/scope và relationship tại thời điểm gửi.
3. Notification/context được lưu bền; client lấy danh sách qua backend.
4. Mở notification đánh dấu read theo rule, rồi backend xác minh lại quyền với resource/context hiện hành.
5. Parent unlink hoặc publication withdrawal có thể thu hồi quyền xem target/file; thông báo cũ không vượt qua kiểm tra đó.

Notification là bản ghi dẫn hướng, không chứa bản sao dữ liệu riêng tư không cần thiết và không tự cấp authorization. Delivery retry/dedup được bảo vệ bằng event key; queue/broker chưa cần ở logical V1 và chỉ chọn nếu độ tin cậy vận hành yêu cầu.

## Câu hỏi cần chốt

Vendor storage, private download mechanism, upload limits/types, malware scanning, orphan cleanup schedule, notification event list chi tiết và background job mechanism được ghi ở `open-decisions.md`.
