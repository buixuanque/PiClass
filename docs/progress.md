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

- `ARCH-03` API contract — đang chốt trong Step 9 (xem dưới).
- `ARCH-12` File retention chi tiết.
- CI/CD: đã quyết định sẽ có, công cụ/pipeline cụ thể chưa chọn.

Đã chốt thêm trong lúc làm Step 9 (cập nhật ngược vào `open-decisions.md`/`technology-decisions.md`):
- `ARCH-05` Chính sách auth: password tối thiểu 8 ký tự, rate limit login 5 lần/phút, reset token 60 phút, session thường 1 ngày, remember-me 30 ngày, nhiều session đồng thời.
- `ARCH-11` Role context: lưu trạng thái ở server qua `POST /api/v1/auth/context`. Giữ multi-role trên 1 account (đã cân nhắc lại, giữ nguyên `BR-USER-002`).

## Cấu trúc repo (đã tách)

- `piclass-api/` → repo riêng (git nested, branch `master`, sống trong cùng thư mục workspace, không track trong repo docs này — xem `.gitignore`). Trước đây là `backend/`, đã đổi tên thư mục khớp tên repo.
- `piclass-mockup/` (sibling `C:\Workspaces\home\piclass-mockup`) → repo riêng cho export Stitch.
- `piclass-infra/` → repo riêng cho hạ tầng local dev (Docker Compose: Postgres, Laravel qua Nginx/PHP-FPM, Next.js, Swagger UI cho `docs/api-docs`, Mailpit, Playwright). Xem `piclass-infra/README.md` để dựng môi trường.
- Repo hiện tại (docs + plan/spec/ledger) → **`piclass-docs`**.
- frontend thật (Next.js, chưa khởi tạo) sẽ là **`piclass-web`** khi bắt đầu Plan 1b.

**Việc còn mở để `piclass-infra` chạy được đầy đủ:** chưa có `docs/api-docs/` (đặc tả OpenAPI viết tay, chưa viết) — service `docs` của stack sẽ phục vụ trang trắng cho tới khi có.

**Quyết định (2026-10-13): chuẩn hóa dev local trên Docker (`piclass-infra`)**, thay cho cách host-based (PHP/Composer/PostgreSQL cài trực tiếp trong WSL) đã dùng lúc làm Plan 1. `piclass-api/.env`, `.env.example`, `.env.testing`, `phpunit.xml` đã cập nhật trỏ về service `db` của container thay vì `127.0.0.1`. Đã verify thực tế: `docker compose up` lên đủ service, `GET /api/health` qua Nginx trả `{"status":"ok","db":"ok"}`, test suite 40/40 pass chạy trong container. Lúc sửa `piclass-infra` còn phát hiện và fix: 3 script `.sh` bị CRLF (Windows) làm container crash loop — đã thêm `.gitattributes` chống tái diễn; README thiếu `make` trong yêu cầu tiên quyết.

## Môi trường dev / skill Claude Code

Mỗi máy mới (hoặc sau khi dọn `.claude/`) phải tự chạy lại [setup/claude-skills-setup.md](setup/claude-skills-setup.md) — skill cài scope local, **không commit qua git**, không tự có sẵn khi đổi máy.

Skill đang dùng: `superpowers` (quy trình brainstorm→plan→TDD→review), `ui-ux-pro-max` (chỉ dùng 2 skill con `ui-styling` và `design-system`, phần còn lại là branding/marketing không liên quan), `frontend-design` (checklist chất lượng UI, dùng ở bước review cuối, không authoring từ đầu).

## Step 9 — API Specification + triển khai (đang làm)

Review spec theo **nhóm module**, không duyệt một lượt toàn bộ — theo đúng thứ tự phụ thuộc ở `architecture/module-boundaries.md`, khớp cơ chế "duyệt từng section" của skill `superpowers:brainstorming`. Spec đã duyệt lưu ở `docs/superpowers/specs/`, plan triển khai lưu ở `docs/superpowers/plans/`.

**Cách triển khai mỗi nhóm module (quyết định 2026-10-12):**

1. Spec nhóm module (brainstorming, duyệt từng section) → lưu `docs/superpowers/specs/`.
2. Plan backend (writing-plans) → code (Laravel) → test.
3. Plan frontend (writing-plans) → code (Next.js, dùng design system MASTER.md) → test.
4. Chỉ sau khi cả BE+FE của nhóm module đó xong mới sang spec nhóm tiếp theo — không dồn hết spec/code 5 nhóm rồi mới làm FE, và không gộp BE+FE vào cùng 1 plan/task (khác ngôn ngữ, khác test tool, giữ task sạch theo 1 công nghệ).
5. **Song song, không phụ thuộc BE:** Stitch (phác ý tưởng UI 4 cổng) + `ui-ux-pro-max-skill` (chốt MASTER.md design system) — làm một lần, không lặp lại mỗi nhóm module. Có thể bắt đầu bất cứ lúc nào, không cần chờ backend.

**Ngoại lệ (quyết định 2026-10-14):** chủ động bỏ qua bước 4 — sang spec/BE nhóm 2 (Academic + Lesson + Attendance) trước khi làm Plan 1b/2b (FE nhóm 1). Lý do: ưu tiên dồn BE các nhóm module liên tiếp trước. FE nhóm 1 (Auth + User Management) vẫn còn nợ, cần quay lại làm trước khi dự án coi là xong Step 9.

**Checklist nhóm module:**

- [ ] **1. Identity/Auth + User Management** — spec đã duyệt (`2026-10-12-identity-auth-user-management-api-design.md`). **Plan 1 (BE Auth) đã xong**: 12 task (Laravel 11 + PostgreSQL + Sanctum, toàn bộ endpoint Section 3 của spec), final review (fresh reviewer, model mạnh nhất) tìm 1 Critical + 6 Important, đã fix hết (TDD từng fix), test cuối 39/39 pass. **Plan 2 (BE User Management) đã xong**: 17 task (Section 4-8 của spec — PersonProfile/Student/Parent/Teacher, User/Role quản trị, ClassMember, TeacherClassAssignment, ParentStudentRelationship, self-service `/me/profile` + `/me/children`), final review (fresh reviewer, model mạnh nhất) tìm 2 Critical + 9 Important, đã fix hết (TDD từng fix, bao gồm 1 lỗi email reset/invite không gửi được từ Plan 1 bị lộ ra qua Task 14), test cuối 134/134 pass. Code nằm ở repo `piclass-api/`, ledger đầy đủ tại `.superpowers/sdd/2026-10-13-user-management-backend/progress.md`. Còn vài Minor deferred (ghi trong ledger) — không chặn nhóm module tiếp theo. Plan 1b (FE Auth), Plan 2b (FE User Management) chưa làm.
- [ ] **2. Academic + Lesson + Attendance** — spec đã duyệt (`2026-10-14-academic-lesson-attendance-api-design.md`). **Plan 3 (BE) đã xong**: 11 task (CRUD Subject/Class, auto-transition Class status, Schedule + conflict warning, sinh LessonSession rolling 4 tuần qua Scheduler job `ARCH-13`, LessonSession CRUD thủ công, AttendanceRecord bulk mark + history, AbsenceExplanation, Course/CourseEnrollment mới — `DEC-LRN-010`/`BR-COURSE-004` đã chốt dứt khoát), final review (fresh reviewer, model mạnh nhất) tìm 3 Critical + 5 Important, đã fix hết (TDD từng fix), test cuối 204/204 pass. Code nằm ở repo `piclass-api/`, branch `feature/academic-lesson-attendance-backend` (tách từ `feature/user-management-backend`, **chưa merge main** — xem ghi chú thứ tự merge bên dưới), PR: [#2](https://github.com/buixuanque/piclass-api/pull/2) (base `feature/user-management-backend`). Ledger đã xóa sau khi review sạch (lịch sử commit là record) — Minor deferred còn lại gồm: pagination chưa áp dụng cho list endpoint, conflict check Schedule chưa xét overlap effective_from/until và lịch vắt nửa đêm, `LessonSessionGenerator` dùng timezone UTC thay vì timezone Schedule, vài chỗ thiếu validate biên (ends_at>starts_at, effective_until>=effective_from) — không chặn nhóm module tiếp theo. Plan 3b (FE) chưa làm.
- **Ghi chú thứ tự merge (2026-10-15):** `feature/user-management-backend` (Plan 2) và `feature/academic-lesson-attendance-backend` (Plan 3) đều chưa merge vào `main`. Plan 3 tách từ tip Plan 2 nên PR #2 phải merge SAU khi Plan 2 có PR riêng merge vào `main` trước (hoặc merge tuần tự theo đúng thứ tự tách nhánh) — không merge PR #2 thẳng vào `main` khi `feature/user-management-backend` còn đứng riêng.
- [ ] 3. Question Bank + Assessment — nhớ đưa `AssessmentAudience.course_id`/`StudentAssessment.source_course_id` (đã thêm vào `database/schema.md`) vào spec: Assessment giao được qua Course (`DEC-LRN-010`), không chỉ Class/Student.
- [ ] 4. Submission/Grading + Result/Progress
- [ ] 5. Notification + File + Audit — **việc còn mở từ Plan 2**: `UpdateClassMemberController` (sửa hồi tố `ClassMember.valid_from`/`valid_to`) bắt buộc `reason` trong request theo BR-USER-017 nhưng hiện chưa ghi vào đâu cả (chưa có bảng `AuditLog`). Khi làm nhóm module này, nhớ bổ sung ghi `AuditLog` cho action đó — xem ledger Plan 2 (`.superpowers/sdd/2026-10-13-user-management-backend/progress.md`, mục "Final: Ruling" cuối file) để biết lý do chưa làm ngay.

**Track song song (đang chạy):** Stitch exploration + MASTER.md design system.
- Đã sinh 10 màn hình Stitch (Identity/Auth + 4 dashboard theo role). Export lưu ở repo riêng `piclass-mockup` (sibling folder `C:\Workspaces\home\piclass-mockup`, không nằm trong monorepo này).
- Primary color đã chốt: `#1E40AF` (thay giá trị `#00288e` trong frontmatter YAML của `DESIGN.md` do Stitch tự sinh — có mâu thuẫn giữa frontmatter và phần mô tả prose, dùng giá trị prose).
- Đã chốt `docs/design/MASTER.md` (design system chính thức, tiếng Việt) từ `DESIGN.md` do Stitch sinh — token màu/typography/spacing/elevation/shape + component spec (button/input/card/badge/checkbox/list). Track Stitch/MASTER.md coi như xong cho batch đầu (Identity/Auth + 4 dashboard); batch sau (4 nhóm module còn lại) sẽ tuân theo token này, không để Stitch tự chọn màu/style mới.

**Cách brainstorming spec:** nạp trước các file `requirements/*`, `access-control/*`, `database/*`, `architecture/technology-decisions.md` làm nguồn — không để nó tự hỏi lại từ đầu hoặc tạo spec lệch khỏi Decision Log đã chốt.

## Khi quay lại (máy khác / session mới)

1. Đọc file này trước.
2. Nếu máy chưa có skill Claude Code, chạy `setup/claude-skills-setup.md`.
3. Xem checklist Step 9 đang ở nhóm nào, tiếp tục từ đó — không cần hỏi lại từ đầu.
