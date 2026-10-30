# Open architecture decisions

Các mục dưới đây là câu hỏi kỹ thuật còn mở; chúng không làm thay đổi business rules đã chốt. Đề xuất kiến trúc modular monolith là đề xuất để review, không tự coi framework/vendor cụ thể đã được duyệt.

**Cách dùng:** đây là mục lục. Code và tài liệu trích dẫn `ARCH-xx`; mục dài nằm ở `decisions/ARCH-xx.md` (chỉ mở đúng file cần). ARCH-01–12 là quyết định stack ngắn, giữ nguyên trong bảng cuối. Mục mới: thêm một dòng vào bảng "Tất cả" và file `decisions/ARCH-xx.md` nếu dài hơn khoảng 600 ký tự. Việc chưa làm/nợ kỹ thuật: [`../BACKLOG.md`](../BACKLOG.md), [`known-debt.md`](known-debt.md).

## Còn mở

| ID | Chủ đề | Trạng thái | Chi tiết |
|---|---|---|---|
| ARCH-03 | API contract | [CẦN QUYẾT ĐỊNH — Step 9] REST/OpenAPI hay kiểu khác; error/pagination/upload contract. Next.js↔Laravel ngầm đ | (dòng đầy đủ bên dưới) |
| ARCH-12 | File operations/retention | [CẦN QUYẾT ĐỊNH trước implementation liên quan] Cleanup schedule orphan, operational/legal retention, quota/ty | (dòng đầy đủ bên dưới) |
| ARCH-14 | Sync ClassMember mới → StudentAssessment (sau `DEC-LRN-026`: bài của khóa đã xử lý lười ở `ARCH-38`; còn bài cá nhân giao theo lớp) | **MỞ** một phần — bài cá nhân, xem `B-021` | [ARCH-14](decisions/ARCH-14.md) |
| ARCH-23 | Khoảng cách tài liệu và nợ phát hiện khi refactor UserManagement (Task 9b) | **MỞ** — không chặn tiến độ | [ARCH-23](decisions/ARCH-23.md) |
| ARCH-24 | Khoảng cách code–tài liệu và nợ phát hiện khi refactor Assessment (Task 9c) | **MỞ** — không chặn tiến độ | [ARCH-24](decisions/ARCH-24.md) |
| ARCH-25 | Khoảng cách code–tài liệu và nợ phát hiện khi refactor QuestionBank (Task 9d) | **MỞ** — không chặn tiến độ | [ARCH-25](decisions/ARCH-25.md) |
| ARCH-26 | Lỗi có sẵn và khoảng cách code–tài liệu phát hiện khi refactor Lesson (Task 9e) | **MỞ một phần** — (a)(b)(g) đã sửa, còn lại mở | [ARCH-26](decisions/ARCH-26.md) |
| ARCH-28 | Quyết định và nợ phát hiện khi lên plan refactor Academic (Task 9f) | **MỞ một phần** — (n) phần Teacher/`users.status` còn mở | [ARCH-28](decisions/ARCH-28.md) |
| ARCH-36 | UI lát "thêm hồ sơ kèm tài khoản" dựng không qua mockup (FE `piclass-web`, 2026-10-27) | **CHỜ XÁC NHẬN** | [ARCH-36](decisions/ARCH-36.md) |
| OD-FE-01 | Xưng hô trong UI Giáo viên: mockup viết "cô", FE đang dùng "bạn" (không giả định giới tính giáo viên); bỏ bộ đếm "x/y" ở danh sách buổi TC-05 (tránh N request) | **CHỜ XÁC NHẬN** (2026-10-29) | [`teacher-van-hanh.md`](../product/screens/teacher-van-hanh.md) mục "Lệch có chủ ý khi dựng FE lát 1" |

## Tất cả (01–12 đầy đủ; 13 trở đi xem file chi tiết)

| ID | Chủ đề | Trạng thái / câu hỏi cần chốt | Ảnh hưởng |
|---|---|---|---|
| ARCH-01 | Backend framework | `ĐÃ CHỐT` Laravel (PHP). | API layer, validation/auth, testing/dev plan. |
| ARCH-02 | Frontend framework/packaging | `ĐÃ CHỐT` Next.js, deploy tách riêng khỏi Laravel (không Inertia), dùng chung 1 app cho các cổng theo role. SSR/SPA theo từng route để sau. | Routing, session, deployment và API client. |
| ARCH-03 | API contract | `[CẦN QUYẾT ĐỊNH — Step 9]` REST/OpenAPI hay kiểu khác; error/pagination/upload contract. Next.js↔Laravel ngầm định REST JSON qua app riêng. | Client-server integration; endpoint không thuộc Step 8. |
| ARCH-04 | Authentication implementation | `ĐÃ CHỐT` Laravel Sanctum, cookie-based session cho Next.js SPA (không JWT) — chọn vì cần revoke ngay theo `DEC-USR-011/012`, `DEC-USER-008`. CSRF/CORS theo cơ chế Sanctum. | Identity/Auth, browser security, deployment secret. |
| ARCH-05 | Authentication policy details | `ĐÃ CHỐT` Password tối thiểu 8 ký tự, không bắt buộc ký tự đặc biệt. Rate limit đăng nhập 5 lần/phút/tài khoản+IP, throttle tăng dần. Reset token 60 phút, dùng một lần. Session thường 1 ngày không hoạt động; remember-me 30 ngày. Cho phép nhiều session đồng thời (không giới hạn 1 thiết bị). Xem Step 9 spec Identity/Auth. | Security acceptance criteria trước implementation. |
| ARCH-06 | File storage | `ĐÃ CHỐT` Laravel Storage + driver S3-compatible: MinIO/bên thứ ba rẻ cho dev/staging, AWS S3 cho production, đổi qua env. Allowed size/type, malware scanning chi tiết chưa chốt. | File module, security, cost và backup. |
| ARCH-07 | Hosting topology | `ĐÃ CHỐT (interim, tối ưu chi phí)` 1 AWS EC2 instance chạy chung Nginx + Laravel (PHP-FPM) + Next.js (PM2) + PostgreSQL self-host; không ALB, TLS qua Nginx/Let's Encrypt, domain bên thứ ba. Region/instance size chưa chọn. | Environments, secrets, availability, operations. |
| ARCH-08 | Background execution | `ĐÃ CHỐT` Laravel Queue + Scheduler trên cùng EC2, giám sát bằng Supervisor/systemd, cron thật gọi `schedule:run` mỗi phút. | Reliability và deployment complexity. V1 chưa yêu cầu broker. |
| ARCH-09 | Backup/restore targets | `ĐÃ CHỐT (interim) — rủi ro tạm chấp nhận` Self-host Postgres trên EC2, chưa có quy trình backup đầy đủ; khuyến nghị tối thiểu `pg_dump` cron lên object storage ngay trong giai đoạn dev. Frequency/retention/RPO-RTO chính thức để sau. | Khả năng phục hồi dữ liệu người học. |
| ARCH-10 | Observability | `ĐÃ CHỐT (interim, tối ưu chi phí)` Ghi log cục bộ trên EC2 (cần `logrotate`); tích hợp CloudWatch/Sentry... để giai đoạn sau, công cụ chưa chọn. | Production readiness và incident response. |
| ARCH-11 | Frontend role context | `ĐÃ CHỐT` Context lưu trạng thái ở server (session), chọn/đổi qua `POST /api/v1/auth/context`; mọi request sau tự áp theo context đã chọn, không gửi lại mỗi request. Giữ multi-role trên 1 account (không tách account theo role) — đã cân nhắc lại và giữ nguyên `BR-USER-002`. Xem Step 9 spec Identity/Auth. | Client navigation và request context; policy Step 7 không đổi. |
| ARCH-12 | File operations/retention | `[CẦN QUYẾT ĐỊNH trước implementation liên quan]` Cleanup schedule orphan, operational/legal retention, quota/type policy theo use case. | Storage cost, privacy, support. |

| ID | Chủ đề | Trạng thái | Chi tiết |
|---|---|---|---|
| ARCH-13 | LessonSession generation window | ĐÃ CHỐT (sửa 2026-10-27) | [ARCH-13](decisions/ARCH-13.md) |
| ARCH-14 | Sync ClassMember mới → StudentAssessment (sau `DEC-LRN-026`: bài của khóa đã xử lý lười ở `ARCH-38`; còn bài cá nhân giao theo lớp) | **MỞ** một phần — bài cá nhân, xem `B-021` | [ARCH-14](decisions/ARCH-14.md) |
| ARCH-15 | Phạm vi quyền sửa Assessment của Teacher | ĐÃ CHỐT (tạm, V1) | [ARCH-15](decisions/ARCH-15.md) |
| ARCH-16 | Nguồn design system (MASTER.md) sau khi mất file | ĐÃ CHỐT (2026-10-16) | [ARCH-16](decisions/ARCH-16.md) |
| ARCH-17 | Quy ước tổ chức code backend | ĐÃ CHỐT, đã áp dụng cả 12 module | [ARCH-17](decisions/ARCH-17.md) |
| ARCH-18 | `GET /me/attempts/{attempt}` tự động nộp bài khi Attempt hết giờ | ĐÃ CHỐT (2026-10-19) | [ARCH-18](decisions/ARCH-18.md) |
| ARCH-19 | Nộp bài (`SubmitAttempt`) không có audit nhưng `DEC-AUD-001` liệt kê "Submission" | ĐÃ CHỐT (2026-10-19) | [ARCH-19](decisions/ARCH-19.md) |
| ARCH-20 | ID không tồn tại và ID của người khác trả message 404 khác nhau | ĐÃ CHỐT (2026-10-19) | [ARCH-20](decisions/ARCH-20.md) |
| ARCH-21 | Nguồn đọc vai trò đang hoạt động (`active_role_code`) ở backend | ĐÃ CHỐT, đã triển khai | [ARCH-21](decisions/ARCH-21.md) |
| ARCH-22 | Khoảng cách giữa code Attendance và requirements/spec | (a)(c) ĐÃ SỬA; (b) ĐÃ ĐÓNG | [ARCH-22](decisions/ARCH-22.md) |
| ARCH-23 | Khoảng cách tài liệu và nợ phát hiện khi refactor UserManagement (Task 9b) | **MỞ** — không chặn tiến độ | [ARCH-23](decisions/ARCH-23.md) |
| ARCH-24 | Khoảng cách code–tài liệu và nợ phát hiện khi refactor Assessment (Task 9c) | **MỞ** — không chặn tiến độ | [ARCH-24](decisions/ARCH-24.md) |
| ARCH-25 | Khoảng cách code–tài liệu và nợ phát hiện khi refactor QuestionBank (Task 9d) | **MỞ** — không chặn tiến độ | [ARCH-25](decisions/ARCH-25.md) |
| ARCH-26 | Lỗi có sẵn và khoảng cách code–tài liệu phát hiện khi refactor Lesson (Task 9e) | **MỞ một phần** — (a)(b)(g) đã sửa, còn lại mở | [ARCH-26](decisions/ARCH-26.md) |
| ARCH-27 | Chiến lược múi giờ toàn hệ thống (giờ Việt Nam) | ĐÃ CHỐT, đã triển khai | [ARCH-27](decisions/ARCH-27.md) |
| ARCH-28 | Quyết định và nợ phát hiện khi lên plan refactor Academic (Task 9f) | **MỞ một phần** — (n) phần Teacher/`users.status` còn mở | [ARCH-28](decisions/ARCH-28.md) |
| ARCH-29 | Quyết định và nợ phát hiện khi refactor Identity (Task 9g) | ĐÃ CHỐT; điểm lệch giữ nguyên | [ARCH-29](decisions/ARCH-29.md) |
| ARCH-30 | Quyết định và nợ phát hiện khi refactor File (Task 9h) | ĐÃ CHỐT; điểm lệch giữ nguyên | [ARCH-30](decisions/ARCH-30.md) |
| ARCH-31 | Quyết định và nợ phát hiện khi refactor Notification + Audit (Task 9i) | ĐÃ CHỐT; điểm lệch giữ nguyên | [ARCH-31](decisions/ARCH-31.md) |
| ARCH-32 | Kiến trúc và quy ước code frontend (`piclass-web`) | ĐÃ CHỐT (2026-10-24) | [ARCH-32](decisions/ARCH-32.md) |
| ARCH-33 | Mô hình nhiều trung tâm (tenancy) | ĐÃ CHỐT (2026-10-24) | [ARCH-33](decisions/ARCH-33.md) |
| ARCH-34 | Viền ô nhập/dropdown dùng `--border-subtle` hay `--border-control` | ĐÃ CHỐT (2026-10-24) | [ARCH-34](decisions/ARCH-34.md) |
| ARCH-35 | Kết thúc ghi danh cá nhân có thể làm học sinh mất khóa dù vẫn ở lớp đang gắn khóa đó | ĐÃ CHỐT VÀ SỬA (2026-10-27); **hết hiệu lực** cùng bỏ ghi danh (`DEC-LRN-026`) | [ARCH-35](decisions/ARCH-35.md) |
| ARCH-36 | UI lát "thêm hồ sơ kèm tài khoản" dựng không qua mockup (FE `piclass-web`, 2026-10-27) | **CHỜ XÁC NHẬN** | [ARCH-36](decisions/ARCH-36.md) |
| ARCH-37 | Gộp tab Lịch (AD-10d) và Buổi học (AD-10e) thành một mặt bày duy nhất | ĐÃ CHỐT VÀ ĐÃ LÀM (2026-10-28) | [ARCH-37](decisions/ARCH-37.md) |
| ARCH-38 | Ranh giới module cho `CourseAudience`, `CourseItem`, `Assessment.course_id` sau `DEC-LRN-026` | ĐÃ CHỐT VÀ ĐÃ LÀM (2026-10-30) | [ARCH-38](decisions/ARCH-38.md) |


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
