# Tiến độ dự án PiClass

**Mục đích:** bản chụp **tĩnh** của dự án: các Step, stack, cấu trúc repo, phạm vi V1, track mockup. **Không chứa "đang làm gì / kế tiếp"** — việc đó ở [STATE.md](STATE.md); việc chưa làm ở [BACKLOG.md](BACKLOG.md); lịch sử từng lát ở [history/progress-log.md](history/progress-log.md). Chỉ sửa file này khi có quyết định nền mới hoặc chuyển giai đoạn.

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
| 9 | API Specification + triển khai | **Đang làm** — BE xong; FE xong cổng Admin, còn Giáo viên/Học sinh/Phụ huynh (xem `STATE.md`) |

## Stack đã chốt (Step 8)

- **Backend:** Laravel 13 + PHP 8.4 (nâng từ Laravel 11 / PHP 8.2 ngày 2026-10-21, xem `architecture/technology-decisions.md`), modular monolith.
- **Frontend:** Next.js, deploy tách riêng khỏi backend (không dùng Inertia.js). Version đã chốt 2026-10-23 (Next 16.4, React 19.3, TS 6.0, Tailwind 4.3, Node 24 LTS, pnpm): xem `architecture/technology-decisions.md`.
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
- frontend thật (Next.js) là **`piclass-web`**: repo git local đã khởi tạo 2026-10-24 với `CLAUDE.md` là quy ước code FE (`ARCH-32`); remote `buixuanque/piclass-web` (private) tạo 2026-10-24; nền đã scaffold ở PR #1 (xem mục Frontend bên dưới).

**Việc còn mở để `piclass-infra` chạy được đầy đủ:** chưa có `docs/api-docs/` (đặc tả OpenAPI viết tay, chưa viết) — service `docs` của stack sẽ phục vụ trang trắng cho tới khi có.

**Quyết định (2026-10-13): chuẩn hóa dev local trên Docker (`piclass-infra`)**, thay cho cách host-based (PHP/Composer/PostgreSQL cài trực tiếp trong WSL) đã dùng lúc làm Plan 1. `piclass-api/.env`, `.env.example`, `.env.testing`, `phpunit.xml` đã cập nhật trỏ về service `db` của container thay vì `127.0.0.1`. Đã verify thực tế: `docker compose up` lên đủ service, `GET /api/health` qua Nginx trả `{"status":"ok","db":"ok"}`, test suite 40/40 pass chạy trong container. Lúc sửa `piclass-infra` còn phát hiện và fix: 3 script `.sh` bị CRLF (Windows) làm container crash loop — đã thêm `.gitattributes` chống tái diễn; README thiếu `make` trong yêu cầu tiên quyết.

## Môi trường dev / skill Claude Code

Mỗi máy mới (hoặc sau khi dọn `.claude/`) phải tự chạy lại [setup/claude-skills-setup.md](setup/claude-skills-setup.md) — skill cài scope local, **không commit qua git**, không tự có sẵn khi đổi máy.

Skill đang dùng: `superpowers` (quy trình brainstorm→plan→TDD→review), `ui-ux-pro-max` (chỉ dùng 2 skill con `ui-styling` và `design-system`, phần còn lại là branding/marketing không liên quan), `frontend-design` (checklist chất lượng UI, dùng ở bước review cuối, không authoring từ đầu).

## Phạm vi V1 và hướng đi (đặt lại 2026-10-24)

Sau lượt review phạm vi ngày 2026-10-24 (`research/2026-10-24-review-pham-vi-v1.md`, `research/2026-10-24-api-v1-toi-thieu.md`), mục tiêu V1 là **chức năng cơ bản nhất của một trung tâm giáo dục**, chạy thật ở các trung tâm rồi hỏi giáo viên để mở rộng.

**Đã chốt (user, 2026-10-24; ghi ở `architecture/open-decisions.md`, `ARCH-33`):**
- Người dùng: 2 trung tâm hiện có (nhiều lớp dạy thêm), có thể dùng cho một trường; hướng tới nhiều trung tâm.
- Hướng A: mỗi trung tâm một bản triển khai riêng (database và domain riêng), dùng chung một code; không multi-tenant trong một database.
- Giữ Course; giữ nguyên 9 loại câu hỏi; thông báo chỉ trong ứng dụng; chưa nhập học sinh hàng loạt.
- Email hệ thống (lời mời, đặt lại mật khẩu) vẫn cần cấu hình SMTP cho mỗi bản triển khai.

**Đã chốt (2026-10-24):** học sinh chưa có tài khoản thì không làm bài online; Teacher hoặc Admin tạo hồ sơ và mời kích hoạt (`DEC-USR-013`, `DEC-USR-016`); `login_identifier` luôn có lựa chọn dùng email, không bắt buộc, cho cả Student (`DEC-USR-017`). Chi tiết: `requirements/decision-log.md`, `product/screens/api-gaps.md`. **Cần một task BE riêng trước khi FE làm màn mời cho Teacher:** hiện `POST /students` và `POST /person-profiles/{id}/invite` ở `piclass-api` chỉ `role:admin`, chưa cho Teacher (`DEC-USR-016`).

## Step 9 — API Specification + triển khai

**Quy trình làm việc theo lát:** [process/working-flow.md](process/working-flow.md).

**Backend: xong và đã merge `main`.** Năm nhóm module, spec ở `superpowers/specs/`, plan ở `superpowers/plans/`, lịch sử từng task ở git và `superpowers/plans/2026-10-18-be-architecture-refactor-results.md`:

| Nhóm | Nội dung | PR chính |
|---|---|---|
| 1 | Identity/Auth + User Management | PR #1 |
| 2 | Academic + Lesson + Attendance + Course | PR #3 |
| 3 | Question Bank + Assessment | PR #4 |
| 4 | Submission/Grading + Result/Progress | PR #5 |
| 5 | Notification + File + Audit (cùng retrofit Module 4) | PR #10, #11 |

Tình trạng BE (2026-10-22): 12 module theo `app/Modules/<Module>/` (Action + FormRequest/Policy/Resource, không Repository, `ARCH-17`), `ArchitectureTest` canh cấu trúc, 2173 test pass, PHPStan mức 5 có baseline. Quy ước và bẫy: `piclass-api/CLAUDE.md`, `architecture/backend-gotchas.md`, `architecture/known-debt.md`, `architecture/model-ownership.md`, `architecture/error-codes.md`. Contract API được coi là ổn định: đổi `error.code`, shape JSON hay status phải báo trước, ghi `error-codes.md`, có test ghim.

**Frontend (`piclass-web`):** nền scaffold ở PR #1 (Next 16, design token v2, `api-client` Sanctum cookie, `PortalGuard`, app shell). Cổng Admin đã port theo từng lát (nhóm 0-7, 5b-5d); thứ tự lát và trạng thái hiện tại: `STATE.md`, chi tiết từng lát: `history/progress-log.md`.

**Vận hành:** sau khi pull/deploy BE phải restart queue worker (thông báo chạy qua queue). Máy dev: `make install && make migrate && make restart-queue` trong `piclass-infra/local`.

## Track mockup và design system

- **Base UI đã chốt:** `piclass-mockup/design-system/` (design system v2, 2026-10-23, repo `buixuanque/piclass-mockup`, nhánh `main`). Web responsive; token chính thức (primary `#1A73F0`, font Be Vietnam Pro…) lấy từ đây. Chi tiết và ngoại lệ: `design-system/CHANGES.md`. **Ngoại lệ đã biết:** nút primary md/lg đạt 4.42:1 (dưới 4.5), đừng "sửa" khi code FE. Chưa làm: nút `danger` (3.39:1), `Tooltip` aria, `BottomNav` hover; ô ngày theo locale `vi` ở FE. Component sửa thẳng trong `_ds_bundle.js`.
- **Mockup đã dựng, chưa rà lại nghiệp vụ:** Quản trị, Giáo viên, Học sinh, Phụ huynh, Màn chung (bảng chi tiết ở `product/screens/mockup-plan.md`). Xem bằng double-click `design-system/standalone/*.html`. Các màn này là base, không phải bản chốt; sẽ được rà theo phạm vi tối thiểu, bắt đầu từ Admin.
- **Báo cáo review UI/UX đã có:** `product/screens/reviews/` (Giáo viên, Học sinh, Phụ huynh, Màn chung, 2026-10-23 đến 2026-10-24). Chốt: màn Tiến độ dùng thang /10 cho cả ba vai trò.
- **Đặc tả màn hình:** `product/screen-inventory.md` (mục lục), `product/screens/*.md` (chi tiết), `product/screens/api-gaps.md` (API cần bổ sung hoặc sửa kèm 13 quyết định cần chốt).
- Ghi chú: Claude Design và Stitch không còn dùng; `docs/design/MASTER.md` đã mất, không khôi phục (`ARCH-16`).

**Cách brainstorming spec:** nạp trước các file `requirements/*`, `access-control/*`, `database/*`, `architecture/technology-decisions.md` làm nguồn, không để nó tự hỏi lại từ đầu hoặc tạo spec lệch khỏi Decision Log đã chốt.
