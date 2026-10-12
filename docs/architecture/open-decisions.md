# Open architecture decisions

Các mục dưới đây là câu hỏi kỹ thuật còn mở; chúng không làm thay đổi business rules đã chốt. Đề xuất kiến trúc modular monolith là đề xuất để review, không tự coi framework/vendor cụ thể đã được duyệt.

| ID | Chủ đề | Trạng thái / câu hỏi cần chốt | Ảnh hưởng |
|---|---|---|---|
| ARCH-01 | Backend framework | `ĐÃ CHỐT` Laravel (PHP). | API layer, validation/auth, testing/dev plan. |
| ARCH-02 | Frontend framework/packaging | `ĐÃ CHỐT` Next.js, deploy tách riêng khỏi Laravel (không Inertia), dùng chung 1 app cho các cổng theo role. SSR/SPA theo từng route để sau. | Routing, session, deployment và API client. |
| ARCH-03 | API contract | `[CẦN QUYẾT ĐỊNH — Step 9]` REST/OpenAPI hay kiểu khác; error/pagination/upload contract. Next.js↔Laravel ngầm định REST JSON qua app riêng. | Client-server integration; endpoint không thuộc Step 8. |
| ARCH-04 | Authentication implementation | `ĐÃ CHỐT` Laravel Sanctum, cookie-based session cho Next.js SPA (không JWT) — chọn vì cần revoke ngay theo `DEC-USR-011/012`, `DEC-USER-008`. CSRF/CORS theo cơ chế Sanctum. | Identity/Auth, browser security, deployment secret. |
| ARCH-05 | Authentication policy details | `[CẦN QUYẾT ĐỊNH]` Password strength, login rate limits, reset-token expiry, session concurrency/timeout — chưa bàn, dù provider (Sanctum) đã chốt. | Security acceptance criteria trước implementation. |
| ARCH-06 | File storage | `ĐÃ CHỐT` Laravel Storage + driver S3-compatible: MinIO/bên thứ ba rẻ cho dev/staging, AWS S3 cho production, đổi qua env. Allowed size/type, malware scanning chi tiết chưa chốt. | File module, security, cost và backup. |
| ARCH-07 | Hosting topology | `ĐÃ CHỐT (interim, tối ưu chi phí)` 1 AWS EC2 instance chạy chung Nginx + Laravel (PHP-FPM) + Next.js (PM2) + PostgreSQL self-host; không ALB, TLS qua Nginx/Let's Encrypt, domain bên thứ ba. Region/instance size chưa chọn. | Environments, secrets, availability, operations. |
| ARCH-08 | Background execution | `ĐÃ CHỐT` Laravel Queue + Scheduler trên cùng EC2, giám sát bằng Supervisor/systemd, cron thật gọi `schedule:run` mỗi phút. | Reliability và deployment complexity. V1 chưa yêu cầu broker. |
| ARCH-09 | Backup/restore targets | `ĐÃ CHỐT (interim) — rủi ro tạm chấp nhận` Self-host Postgres trên EC2, chưa có quy trình backup đầy đủ; khuyến nghị tối thiểu `pg_dump` cron lên object storage ngay trong giai đoạn dev. Frequency/retention/RPO-RTO chính thức để sau. | Khả năng phục hồi dữ liệu người học. |
| ARCH-10 | Observability | `ĐÃ CHỐT (interim, tối ưu chi phí)` Ghi log cục bộ trên EC2 (cần `logrotate`); tích hợp CloudWatch/Sentry... để giai đoạn sau, công cụ chưa chọn. | Production readiness và incident response. |
| ARCH-11 | Frontend role context | `[CẦN QUYẾT ĐỊNH — UI/API coordination]` Cách chọn và thể hiện Admin/Teacher context cho User đa role, không gộp quyền? | Client navigation và request context; policy Step 7 không đổi. |
| ARCH-12 | File operations/retention | `[CẦN QUYẾT ĐỊNH trước implementation liên quan]` Cleanup schedule orphan, operational/legal retention, quota/type policy theo use case. | Storage cost, privacy, support. |

## Đã chốt và không phải open decision của Step 8

- PostgreSQL + UUID PK đã chốt ở Step 6.
- Notification V1 là in-app; email/SMS/LINE chưa nằm trong V1.
- Permission và data scope đã chốt ở Step 7; architecture phải thực thi chứ không mở lại.
- Assessment version/snapshot, attempt/submission, history/audit và Parent unlink behavior theo Step 5–7.
- Không tạo Progress table riêng V1; tính từ Result/dữ kiện nguồn.
- Mobile app, payment/tuition, AI, video/live class, chat, advanced analytics ngoài V1.
- Stack V1 đã chốt: Laravel + PostgreSQL (self-host) + Next.js (tách riêng) + AWS EC2 (1 instance, không ALB) + Sanctum cho auth. Chi tiết ở `technology-decisions.md` và ARCH-01/02/04/06/07/08/09/10 trên.

## Điều kiện đóng Step 8

Step 8 có thể được review/chốt khi modularity, data flow, security boundaries và topology logic được chấp nhận. Các mục technology có thể tiếp tục cần chọn ở Step 9/Development Plan; đánh dấu rõ người chịu trách nhiệm và thời điểm cần chốt trước khi code hoặc production.
