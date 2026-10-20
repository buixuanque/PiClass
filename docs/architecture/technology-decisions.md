# Technology decisions

Tài liệu này phân biệt quyết định đã được chốt ở các Step trước với các lựa chọn kiến trúc còn cần user review. Mục “đề xuất” là hướng khuyến nghị, không tự trở thành quyết định.

| Chủ đề | Trạng thái | Giá trị hiện tại / cần làm |
|---|---|---|
| DBMS | **DECIDED — Step 6** | PostgreSQL đã chốt; kiến trúc kế thừa `docs/database/*`. |
| Primary key | **DECIDED — Step 6** | UUID cho entity nghiệp vụ. |
| Data model / history | **DECIDED — Step 6** | Theo schema, quan hệ, history và lifecycle Step 6; archive/inactive không xóa lịch sử. |
| Role/scope enforcement | **DECIDED — Step 7** | Backend phải thực thi Permission + Data Scope + relationship + lifecycle/visibility như Step 7. |
| Web vs mobile | **DECIDED — Product V1** | Web portals trong scope; mobile app ngoài V1. |
| Notification channel | **DECIDED — Step 2/5** | In-app V1. Email/SMS/LINE để giai đoạn sau. |
| Backend structure | **ARCHITECTURE PROPOSAL** | Modular monolith, một backend application; review ở Step 8. |
| Backend language/framework | **DECIDED** | Laravel (PHP), modular monolith theo nguyên tắc Step 8. **Phiên bản: Laravel 13 + PHP 8.4 + PHPUnit 12** (nâng từ Laravel 11 / PHP 8.2 ngày 2026-10-21 vì Laravel 11 hết vá bảo mật từ 2026-03-12; Laravel 12 chỉ còn vá bảo mật đến 2027-02-24). Hỗ trợ Laravel 13: vá lỗi đến Q3/2027, vá bảo mật đến Q1/2028. Môi trường dev (`piclass-infra`) đã chạy PHP 8.4; production phải dùng PHP ≥ 8.4. |
| Frontend framework | **DECIDED** | Next.js, deploy tách riêng khỏi backend (không dùng Inertia.js) — frontend chỉ hiển thị, backend giữ toàn bộ domain rule/permission. |
| Frontend packaging/rendering | **DECIDED** | Một Next.js app dùng chung cho các cổng theo role (Admin/Teacher, Student, Parent), gọi REST API của Laravel. Chi tiết SPA/SSR theo từng route để Development Plan quyết khi cần. |
| API style/transport | **[CẦN QUYẾT ĐỊNH — Step 9]** | Ngầm định REST JSON do Next.js gọi Laravel API tách riêng; chốt chính thức style/contract (OpenAPI, error/pagination/upload) ở Step 9. HTTPS production được yêu cầu ở mức nguyên tắc. |
| Authentication provider | **DECIDED** | Tự quản lý qua Laravel Sanctum, không dùng external IdP. Đã so sánh với JWT (`tymon/jwt-auth`); chọn Sanctum vì thu hồi quyền ngay lập tức khớp `DEC-USR-011`, `DEC-USR-012`, `DEC-USER-008` đã chốt ở Step 5 — JWT chuẩn không revoke được token trước hạn nếu không tự xây blacklist. |
| Session/token | **DECIDED** | Sanctum cookie-based session (SPA), không dùng JWT. CSRF theo cơ chế Sanctum (lấy `csrf-cookie` trước khi login); CORS/stateful domains cấu hình giữa Next.js và Laravel. Session thường 1 ngày không hoạt động, remember-me 30 ngày, nhiều session đồng thời được phép; password tối thiểu 8 ký tự; rate limit login 5 lần/phút; reset token 60 phút (`ARCH-05` đã chốt, xem Step 9 spec Identity/Auth). |
| Object/file storage | **DECIDED** | Laravel Storage abstraction, driver S3-compatible: MinIO (hoặc bên thứ ba giá rẻ) cho dev/staging, AWS S3 thật cho production — đổi qua biến môi trường, không đổi code. |
| Hosting/cloud/region | **DECIDED (interim, tối ưu chi phí)** | 1 AWS EC2 instance chạy chung Nginx + PHP-FPM (Laravel) + Node/PM2 (Next.js) + PostgreSQL self-host trên cùng máy. Không dùng ALB; TLS qua Nginx + Let's Encrypt; domain mua ở bên thứ ba (không dùng Route53). Region/instance size cụ thể chưa chọn. |
| Background jobs | **DECIDED** | Laravel Queue worker + Scheduler, chạy trên chính EC2, giám sát bằng Supervisor/systemd để tự restart khi crash; 1 dòng cron thật gọi `schedule:run` mỗi phút. |
| CI/CD | **[CẦN QUYẾT ĐỊNH]** | Đã quyết định sẽ có CI/CD; công cụ, release gates, migration rollout/rollback cụ thể thuộc Development Plan. |
| Logging/monitoring | **DECIDED (interim, tối ưu chi phí)** | V1 ghi log cục bộ trên EC2 (cần cấu hình `logrotate` vì chung máy với DB); tích hợp công cụ quan sát ngoài (vd. CloudWatch, Sentry) để giai đoạn sau, công cụ cụ thể chưa chọn. |
| Backup/restore | **DECIDED (interim) — rủi ro được chấp nhận tạm thời** | Self-host PostgreSQL trên EC2, chưa thiết kế backup/retention đầy đủ (để sau). Khuyến nghị tối thiểu: `pg_dump` cron đẩy lên object storage (MinIO/S3) ngay trong giai đoạn dev, để giảm rủi ro mất dữ liệu học viên trước khi có quy trình backup hoàn chỉnh. |
| Cache/search | **NOT REQUIRED BY CURRENT V1 DESIGN** | PostgreSQL là nguồn chuẩn; không cần cache/search service riêng trước khi có số đo hoặc yêu cầu. |
| External email/SMS/payment/AI/video/chat | **OUT OF V1** | Không tích hợp trong topology V1 hiện tại. |

## Quy tắc quyết định

- Chỉ đánh dấu DECIDED khi có artifact/approval nguồn; không biến một ví dụ stack thành quyết định.
- Framework/hosting cần chọn trước Development Plan đủ chi tiết để phân rã công việc; API style chốt trong Step 9.
- Khi quyết định technology ảnh hưởng security, file access, migration hoặc vận hành, cập nhật tài liệu architecture liên quan cùng bảng này.
