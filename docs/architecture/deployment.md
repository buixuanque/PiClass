# Deployment và môi trường

## Topology logic

V1 có các thành phần logic sau:

```text
Browser → HTTPS → Web client → Backend application → PostgreSQL
                                               └→ File storage adapter/provider
```

Backend và frontend có thể được host chung hoặc tách deployment; chọn topology/vendor sau khi chốt stack. PostgreSQL đã được chốt ở Step 6. Có thể bắt đầu với một backend instance cho một lớp; giữ stateless request processing ở mức ứng dụng khi có thể để scale-out sau này. Không yêu cầu microservices, Kubernetes hay message broker cho V1.

## Environments

- **Development:** chạy local cho phát triển; dùng database và file bucket riêng hoặc giả lập. Dữ liệu thật của học viên không nên được sao chép vào local trừ khi được xử lý an toàn và có căn cứ.
- **Staging:** cấu hình gần production để kiểm tra tích hợp và release candidate; dữ liệu test/synthetic ưu tiên. Tách credentials và storage khỏi production.
- **Production:** dữ liệu thật, truy cập giới hạn, HTTPS, backup đã xác minh và logging/monitoring vận hành.

Tên nhà cung cấp, mức cô lập và cách tạo environment: `[CẦN QUYẾT ĐỊNH]`.

## Configuration và secrets

- Tách cấu hình theo environment; không hard-code endpoint, credentials, signing keys hoặc storage secrets trong source.
- Secret được quản lý ngoài repository và chỉ cấp quyền tối thiểu cho process cần dùng.
- Không dùng production secret ở dev/staging; xoay vòng credential theo khả năng platform.
- Có thể cấu hình locale/time zone mặc định `Asia/Ho_Chi_Minh` theo Business Rules; timestamps lưu/so sánh nhất quán theo quyết định schema.

## Release và migration

Step 8 không định nghĩa CI/CD, migration scripts hay rollback commands. Logical release cần kiểm tra compatibility giữa application và PostgreSQL schema, sao lưu trước thay đổi không tương thích và có phương án khôi phục. Chi tiết release pipeline thuộc Development Plan. Tránh thay schema phá dữ liệu lịch sử; migration phải giữ các FK/history đã định trong Step 6.

## Logging

Application logs cần timestamp, environment, severity, correlation/request identifier và kết quả lỗi phù hợp để hỗ trợ vận hành. Không log password, tokens, secrets, câu trả lời/tệp học viên đầy đủ hoặc dữ liệu cá nhân không cần thiết. AuditLog là business audit riêng, append-only; không thay thế technical logs. Log access và retention theo policy host, cần chốt trước production.

## Backup và restore

- PostgreSQL cần backup tự động theo khả năng managed platform hoặc job vận hành; tần suất, retention và điểm phục hồi mục tiêu `[CẦN QUYẾT ĐỊNH]`.
- File object store cần backup/versioning hoặc cơ chế phục hồi tương ứng; database metadata và object lifecycle phải nhất quán.
- Kiểm tra restore định kỳ trước production; backup chưa được kiểm tra restore không được xem là phương án phục hồi đã xác nhận.
- Giữ backup production tách khỏi credentials runtime nếu nền tảng cho phép.

## Monitoring cơ bản

Theo dõi availability, request/error rate, latency, database connections/storage, file storage failures, background work (nếu có), login failure bất thường và backup completion. Có cảnh báo cho lỗi ảnh hưởng login, submit, grade/publish, DB hoặc file. Ngưỡng, công cụ, on-call và thông báo vận hành `[CẦN QUYẾT ĐỊNH]`.

## Scale và vận hành

Bắt đầu đơn giản cho một tutoring class; đo đạc trước khi thêm cache, queue, read replica hoặc service tách rời. PostgreSQL là nguồn dữ liệu chuẩn; progress tính từ result nguồn, cache chỉ xem xét khi có bằng chứng hiệu năng theo Step 6. Mở rộng nhiều class/teacher trước hết bằng indexing và scale tài nguyên trong boundary hiện có.
