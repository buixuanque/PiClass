# CLAUDE.md

File này được Claude Code tự động nạp vào mọi session mở trong repo này. Giữ ngắn — chỉ ghi những gì không tự suy ra được từ code/docs, không lặp lại nội dung đã có trong `docs/`.

## Bắt đầu từ đâu

Đọc [docs/progress.md](docs/progress.md) trước — trạng thái hiện tại theo từng Step, stack đã chốt, việc đang làm. Đừng chỉ dựa vào lịch sử chat để biết đang ở đâu.

## Thứ tự ưu tiên tài liệu (source of truth)

Khi có mâu thuẫn giữa các tài liệu, ưu tiên theo thứ tự:

1. `docs/requirements/*` — business rules đã chốt (Step 5), có Decision Log mã số (`DEC-xxx`).
2. `docs/access-control/*` — role/permission/data scope đã chốt (Step 7).
3. `docs/database/*` — schema, quan hệ, lifecycle đã chốt (Step 6).
4. `docs/product/*`, `docs/flows/*`, `docs/domain/*` — mục tiêu, scope, flow, thuật ngữ (Step 1-4).
5. `docs/architecture/*` — cách tổ chức kỹ thuật; không được tự đổi business rule ở trên.

## Khi chốt quyết định mới

Cập nhật ngay tài liệu nguồn tương ứng (Decision Log, `open-decisions.md`, `technology-decisions.md`...) trong cùng lúc — không để quyết định chỉ tồn tại trong lịch sử chat. Nếu phát hiện mâu thuẫn giữa tài liệu, ghi vào `open-decisions.md` của tầng liên quan, không tự chọn một phía rồi im lặng.

## Ngôn ngữ

- Tài liệu (`docs/`) và trao đổi: tiếng Việt.
- Code, tên biến/hàm, comment, commit message: tiếng Anh theo convention chung của Laravel/Next.js. *(Đây là đề xuất mặc định — đổi nếu bạn muốn khác.)*

## Stack hiện tại

Laravel + PostgreSQL (self-host) + Next.js (tách riêng) + AWS EC2 (1 instance) + Laravel Sanctum cho auth. Chi tiết/lý do: `docs/architecture/technology-decisions.md`.

## Research reports

Khi dùng agent `researcher` (hoặc bất kỳ task research/so sánh công nghệ tương tự), sau khi nhận report cuối cùng, **luôn lưu lại** vào `docs/research/YYYY-MM-DD-<slug-chủ-đề>.md` — không chỉ để trong chat. Agent `researcher` không tự lưu file (chỉ có quyền đọc), người gọi nó (main session) chịu trách nhiệm lưu.

## Môi trường Claude Code

Skill (`superpowers`, `ui-ux-pro-max`, `frontend-design`) cài scope local, không commit qua git. Máy mới chạy lại theo [docs/setup/claude-skills-setup.md](docs/setup/claude-skills-setup.md), rồi `/reload-plugins` để nạp vào session đang chạy.

Khi dùng `superpowers:brainstorming` cho việc kiến trúc/API (loại "architectural"), luôn nạp trước `docs/requirements/*`, `docs/access-control/*`, `docs/database/*` làm nguồn — không để nó tự hỏi lại từ đầu hoặc viết spec lệch khỏi Decision Log đã chốt.

## Push nhiều repo (monorepo-workspace, nhiều git repo riêng)

Workspace này gồm nhiều git repo độc lập sống cùng thư mục (`piclass-api/`, `piclass-infra/`, `piclass-web` = repo hiện tại chứa docs, `piclass-mockup` riêng ngoài workspace). Mỗi khi push một trong các repo con (`piclass-api`, ...), **luôn kiểm tra `git status`/`git log origin/main..HEAD` ở repo `piclass-web` (repo docs, hiện tại) xem có commit local chưa push không** — việc code ở repo con gần như luôn kéo theo cập nhật `docs/progress.md`, spec, plan, hoặc `open-decisions.md` ở đây. Nếu có, **báo cho user luôn trong cùng lượt trả lời** (không chờ hỏi riêng) rằng repo docs cũng có thay đổi chưa push, và hỏi có muốn push cùng không.

**Quy trình git giống nhau cho mọi repo, kể cả repo docs (`piclass-web`, hiện tại):** không commit/push trực tiếp lên `main`. Tạo branch mới từ `main` đã pull mới nhất (`git checkout main && git pull && git checkout -b <branch>`), commit vào đó, push branch, mở PR (`gh pr create`), rồi merge PR (`gh pr merge`) — áp dụng kể cả cho thay đổi chỉ gồm file `.md` (spec/plan/progress.md/open-decisions.md).

## Thứ tự triển khai Step 9

Mỗi nhóm module: spec → plan BE → code BE → plan FE → code FE, rồi mới sang spec nhóm module tiếp theo. Không gộp BE+FE vào cùng 1 plan/task (khác ngôn ngữ/test tool). Không dồn spec hết 5 nhóm rồi mới code. Stitch + MASTER.md (design system) là track riêng, làm một lần, không phụ thuộc BE, có thể làm song song bất cứ lúc nào. Chi tiết trạng thái: `docs/progress.md`.
