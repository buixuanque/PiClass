# Open architecture decisions

Các mục dưới đây là câu hỏi kỹ thuật còn mở; chúng không làm thay đổi business rules đã chốt. Đề xuất kiến trúc modular monolith là đề xuất để review, không tự coi framework/vendor cụ thể đã được duyệt.

| ID | Chủ đề | Trạng thái / câu hỏi cần chốt | Ảnh hưởng |
|---|---|---|---|
| ARCH-01 | Backend framework | `[CẦN QUYẾT ĐỊNH]` Ngôn ngữ/framework nào phù hợp người implement, kỹ năng và deployment mục tiêu? | API layer, validation/auth, testing/dev plan. |
| ARCH-02 | Frontend framework/packaging | `[CẦN QUYẾT ĐỊNH]` Một web application theo role hay nhiều portal deployment; SSR, SPA hay hybrid; framework nào? | Routing, session, deployment và API client. |
| ARCH-03 | API contract | `[CẦN QUYẾT ĐỊNH — Step 9]` REST/OpenAPI hay kiểu khác; error/pagination/upload contract. | Client-server integration; endpoint không thuộc Step 8. |
| ARCH-04 | Authentication implementation | `[CẦN QUYẾT ĐỊNH]` Credentials do app quản lý hay external identity provider; session cookie hay token; expiry/revocation/CSRF policy? | Identity/Auth, browser security, deployment secret. |
| ARCH-05 | Authentication policy details | `[CẦN QUYẾT ĐỊNH]` Password strength, login rate limits, reset-token expiry, session concurrency/timeout. | Security acceptance criteria trước implementation. |
| ARCH-06 | File storage | `[CẦN QUYẾT ĐỊNH]` Object storage provider/region, upload route, private download mechanism, allowed size/type và malware scanning. | File module, security, cost và backup. |
| ARCH-07 | Hosting topology | `[CẦN QUYẾT ĐỊNH]` Hosting frontend/backend, managed PostgreSQL, network separation và deployment region nào? | Environments, secrets, availability, operations. |
| ARCH-08 | Background execution | `[CẦN QUYẾT ĐỊNH]` Có cần job runner/queue cho notification, schedule rolling-window generation, orphan cleanup không; nếu có, mức bảo đảm/retry? | Reliability và deployment complexity. V1 chưa yêu cầu broker. |
| ARCH-09 | Backup/restore targets | `[CẦN QUYẾT ĐỊNH]` Backup frequency/retention, mục tiêu phục hồi và cách khôi phục file cùng DB. | Khả năng phục hồi dữ liệu người học. |
| ARCH-10 | Observability | `[CẦN QUYẾT ĐỊNH]` Logging/monitoring provider, alert destination, log access/retention và hành động vận hành? | Production readiness và incident response. |
| ARCH-11 | Frontend role context | `[CẦN QUYẾT ĐỊNH — UI/API coordination]` Cách chọn và thể hiện Admin/Teacher context cho User đa role, không gộp quyền? | Client navigation và request context; policy Step 7 không đổi. |
| ARCH-12 | File operations/retention | `[CẦN QUYẾT ĐỊNH trước implementation liên quan]` Cleanup schedule orphan, operational/legal retention, quota/type policy theo use case. | Storage cost, privacy, support. |

## Đã chốt và không phải open decision của Step 8

- PostgreSQL + UUID PK đã chốt ở Step 6.
- Notification V1 là in-app; email/SMS/LINE chưa nằm trong V1.
- Permission và data scope đã chốt ở Step 7; architecture phải thực thi chứ không mở lại.
- Assessment version/snapshot, attempt/submission, history/audit và Parent unlink behavior theo Step 5–7.
- Không tạo Progress table riêng V1; tính từ Result/dữ kiện nguồn.
- Mobile app, payment/tuition, AI, video/live class, chat, advanced analytics ngoài V1.

## Điều kiện đóng Step 8

Step 8 có thể được review/chốt khi modularity, data flow, security boundaries và topology logic được chấp nhận. Các mục technology có thể tiếp tục cần chọn ở Step 9/Development Plan; đánh dấu rõ người chịu trách nhiệm và thời điểm cần chốt trước khi code hoặc production.
