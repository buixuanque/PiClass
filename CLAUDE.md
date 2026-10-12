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
