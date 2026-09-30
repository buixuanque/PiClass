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
| Backend language/framework | **[CẦN QUYẾT ĐỊNH]** | Chọn framework phù hợp với người implement và hosting. Không mặc định Node, Python, PHP, Java, .NET hay framework cụ thể. |
| Frontend framework | **[CẦN QUYẾT ĐỊNH]** | Chọn framework web sau khi cân nhắc kỹ năng, forms/assessment flows, accessibility, deployment. |
| Frontend packaging/rendering | **[CẦN QUYẾT ĐỊNH]** | Một web app theo role hay deployment portal riêng; SPA hay SSR/hybrid. Backend/API contract vẫn dùng chung. |
| API style/transport | **[CẦN QUYẾT ĐỊNH — Step 9]** | REST/OpenAPI hay lựa chọn khác; chưa định nghĩa endpoint tại Step 8. HTTPS production được yêu cầu ở mức nguyên tắc. |
| Authentication provider | **[CẦN QUYẾT ĐỊNH]** | Tự quản lý credentials hay provider; reset password qua verified contact đã chốt, provider chưa chọn. |
| Session/token | **[CẦN QUYẾT ĐỊNH]** | Cookie-based session hay bearer token; expiration, revocation, rotation, CSRF/CORS theo framework/deployment. |
| Object/file storage | **[CẦN QUYẾT ĐỊNH]** | Dùng abstraction; chọn managed object storage/local development adapter, private access mechanism và lifecycle. |
| Hosting/cloud/region | **[CẦN QUYẾT ĐỊNH]** | Chọn web hosting, application runtime, managed PostgreSQL, object storage và vùng lưu trữ phù hợp dữ liệu người học. |
| Background jobs | **[CẦN QUYẾT ĐỊNH]** | V1 không đòi broker. Quyết định nếu cần schedule generation, notification processing hoặc orphan cleanup đáng tin cậy. |
| CI/CD | **[CẦN QUYẾT ĐỊNH]** | Công cụ, release gates, migration rollout/rollback thuộc Development Plan. |
| Logging/monitoring | **[CẦN QUYẾT ĐỊNH]** | Công cụ, alert, access và technical log retention theo platform; cần trước production. |
| Backup/restore | **[CẦN QUYẾT ĐỊNH]** | Tần suất, retention, RPO/RTO, file recovery và lịch kiểm tra restore. |
| Cache/search | **NOT REQUIRED BY CURRENT V1 DESIGN** | PostgreSQL là nguồn chuẩn; không cần cache/search service riêng trước khi có số đo hoặc yêu cầu. |
| External email/SMS/payment/AI/video/chat | **OUT OF V1** | Không tích hợp trong topology V1 hiện tại. |

## Quy tắc quyết định

- Chỉ đánh dấu DECIDED khi có artifact/approval nguồn; không biến một ví dụ stack thành quyết định.
- Framework/hosting cần chọn trước Development Plan đủ chi tiết để phân rã công việc; API style chốt trong Step 9.
- Khi quyết định technology ảnh hưởng security, file access, migration hoặc vận hành, cập nhật tài liệu architecture liên quan cùng bảng này.
