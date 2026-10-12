# Tiến độ dự án PiClass

**Mục đích:** một file duy nhất để biết "đang ở đâu" — đọc file này trước khi tiếp tục làm việc, dù trên máy nào hoặc session Claude Code nào. Cập nhật file này mỗi khi có quyết định mới hoặc chuyển giai đoạn, không chỉ dựa vào lịch sử chat.

## Trạng thái theo Step

| Step | Nội dung | Trạng thái |
|---|---|---|
| 1-2 | Product Definition, Feature Map | Hoàn chỉnh |
| 3 | User Flow | Hoàn chỉnh |
| 4 | Domain Model + Glossary | Hoàn chỉnh |
| 5 | Business Rules | Đã chốt toàn bộ — xem `requirements/decision-log.md` |
| 6 | Database Design | Đã chốt |
| 7 | Access Control | Đã chốt phần lớn |
| 8 | System Architecture | Stack đã chốt (xem dưới); còn vài mục mở |
| 9 | API Specification | **Đang làm** — xem kế hoạch dưới |

## Stack đã chốt (Step 8)

- **Backend:** Laravel (PHP), modular monolith.
- **Frontend:** Next.js, deploy tách riêng khỏi backend (không dùng Inertia.js).
- **Database:** PostgreSQL, self-host trên EC2 cho V1 (chưa có backup đầy đủ — rủi ro tạm chấp nhận, xem `ARCH-09`).
- **Auth:** Laravel Sanctum, cookie-based session cho Next.js SPA — **không dùng JWT**. Lý do: cần revoke quyền ngay lập tức theo `DEC-USR-011`, `DEC-USR-012`, `DEC-USER-008` đã chốt ở Step 5; JWT chuẩn không làm được điều này nếu không tự xây blacklist.
- **File storage:** Laravel Storage abstraction, driver S3-compatible — MinIO/bên thứ ba rẻ cho dev/staging, AWS S3 thật cho production (đổi qua env, không đổi code).
- **Hosting:** 1 AWS EC2 instance duy nhất (Nginx + PHP-FPM + Node/PM2 + PostgreSQL self-host chung máy) — không dùng ALB, TLS qua Nginx + Let's Encrypt, domain mua bên thứ ba.
- **Background jobs:** Laravel Queue + Scheduler, giám sát bằng Supervisor/systemd, cron thật gọi `schedule:run` mỗi phút.

Chi tiết đầy đủ: [architecture/technology-decisions.md](architecture/technology-decisions.md), [architecture/open-decisions.md](architecture/open-decisions.md).

## Còn mở ở Step 8

- `ARCH-03` API contract — dự định chốt trong Step 9.
- `ARCH-05` Chi tiết chính sách auth (password strength, rate limit, session timeout).
- `ARCH-11` Frontend role context cho user đa role — dự định chốt trong lúc làm Step 9.
- `ARCH-12` File retention chi tiết.
- CI/CD: đã quyết định sẽ có, công cụ/pipeline cụ thể chưa chọn.

## Môi trường dev / skill Claude Code

Mỗi máy mới (hoặc sau khi dọn `.claude/`) phải tự chạy lại [setup/claude-skills-setup.md](setup/claude-skills-setup.md) — skill cài scope local, **không commit qua git**, không tự có sẵn khi đổi máy.

Skill đang dùng: `superpowers` (quy trình brainstorm→plan→TDD→review), `ui-ux-pro-max` (chỉ dùng 2 skill con `ui-styling` và `design-system`, phần còn lại là branding/marketing không liên quan), `frontend-design` (checklist chất lượng UI, dùng ở bước review cuối, không authoring từ đầu).

## Step 9 — API Specification (đang làm)

Review theo **nhóm module**, không duyệt một lượt toàn bộ — theo đúng thứ tự phụ thuộc ở `architecture/module-boundaries.md`, và khớp cơ chế "duyệt từng section" của skill `superpowers:brainstorming`.

**Checklist nhóm** (cập nhật khi mỗi nhóm được duyệt):

- [ ] 1. Identity/Auth + User Management
- [ ] 2. Academic + Lesson + Attendance
- [ ] 3. Question Bank + Assessment
- [ ] 4. Submission/Grading + Result/Progress
- [ ] 5. Notification + File + Audit

**Cách làm:** dùng `superpowers:brainstorming`, nạp trước các file `requirements/*`, `access-control/*`, `database/*`, `architecture/technology-decisions.md` làm nguồn — không để nó tự hỏi lại từ đầu hoặc tạo spec lệch khỏi Decision Log đã chốt.

## Khi quay lại (máy khác / session mới)

1. Đọc file này trước.
2. Nếu máy chưa có skill Claude Code, chạy `setup/claude-skills-setup.md`.
3. Xem checklist Step 9 đang ở nhóm nào, tiếp tục từ đó — không cần hỏi lại từ đầu.
