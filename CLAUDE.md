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

## Quy ước code backend

Code backend (`piclass-api/`) viết theo `piclass-api/CLAUDE.md` (module `app/Modules/<Module>/`, `ArchitectureTest` canh tự động). Bẫy thực hành và cách viết test: [docs/architecture/backend-gotchas.md](docs/architecture/backend-gotchas.md). Code frontend (`piclass-web/`) viết theo `piclass-web/CLAUDE.md` (`ARCH-32`). Nợ kỹ thuật đã biết: [docs/architecture/known-debt.md](docs/architecture/known-debt.md) — khi chạm tới code có trong đó thì cân nhắc xử lý và xóa dòng.

## Khi chốt quyết định mới

Cập nhật ngay tài liệu nguồn tương ứng (Decision Log, `open-decisions.md`, `technology-decisions.md`...) trong cùng lúc — không để quyết định chỉ tồn tại trong lịch sử chat. Nếu phát hiện mâu thuẫn giữa tài liệu, ghi vào `open-decisions.md` của tầng liên quan, không tự chọn một phía rồi im lặng.

## Ngôn ngữ

- Tài liệu (mọi file trong `docs/`, kể cả `docs/superpowers/specs|plans`, `progress.md`, `open-decisions.md`...) và trao đổi trong chat: tiếng Việt. Trừ nội dung buộc phải tiếng Anh (xem dòng dưới) hoặc thuật ngữ kỹ thuật không có bản dịch tiếng Việt tương đương.
- **Comment trong code** (docblock, comment dòng, mô tả/ghi chú trong test): tiếng Việt. Comment tiếng Anh cũ nằm trong file đang sửa thì dịch luôn (chỉ trong file đã đụng tới, không dịch hàng loạt).
- Tên biến/hàm/class, tên test method, `error.code`, commit message: tiếng Anh theo convention chung của Laravel/Next.js.
- **PR title/description: tiếng Việt** (user chốt 2026-10-21, ghi đè quy ước tiếng Anh trước đó). Commit message vẫn tiếng Anh.

## Giải thích khi hỏi thiết kế/clarify

Khi hỏi user để chốt rule/thiết kế mà nội dung không chỉ là code thuần (ví dụ: rule hiển thị dữ liệu theo role, business logic ảnh hưởng người dùng cuối, phân quyền), luôn giải thích **cả hai hướng**: hướng kỹ thuật VÀ hướng nghiệp vụ thực tế (ai làm gì, vì sao, ảnh hưởng ra sao tới người dùng cuối) — không chỉ liệt kê field/logic trừu tượng. Không cần áp dụng cho câu hỏi thuần kỹ thuật (chọn thư viện, format file, convention code).

## Stack hiện tại

Laravel + PostgreSQL (self-host) + Next.js (tách riêng) + AWS EC2 (1 instance) + Laravel Sanctum cho auth. Chi tiết/lý do: `docs/architecture/technology-decisions.md`.

## Research reports

Khi dùng agent `researcher` (hoặc bất kỳ task research/so sánh công nghệ tương tự), sau khi nhận report cuối cùng, **luôn lưu lại** vào `docs/research/YYYY-MM-DD-<slug-chủ-đề>.md` — không chỉ để trong chat. Agent `researcher` không tự lưu file (chỉ có quyền đọc), người gọi nó (main session) chịu trách nhiệm lưu.

## Môi trường Claude Code

Skill (`superpowers`, `ui-ux-pro-max`, `frontend-design`) cài scope local, không commit qua git. Máy mới chạy lại theo [docs/setup/claude-skills-setup.md](docs/setup/claude-skills-setup.md), rồi `/reload-plugins` để nạp vào session đang chạy.

Khi dùng `superpowers:brainstorming` cho việc kiến trúc/API (loại "architectural"), luôn nạp trước `docs/requirements/*`, `docs/access-control/*`, `docs/database/*` làm nguồn — không để nó tự hỏi lại từ đầu hoặc viết spec lệch khỏi Decision Log đã chốt.

## Push nhiều repo (monorepo-workspace, nhiều git repo riêng)

Workspace này gồm nhiều git repo độc lập sống cùng thư mục (`piclass-api/`, `piclass-infra/`, `piclass-web/` = frontend Next.js, `piclass-mockup/`; repo hiện tại chứa docs, tên `piclass-docs`). Mỗi khi push một trong các repo con (`piclass-api`, ...), **luôn kiểm tra `git status`/`git log origin/main..HEAD` ở repo docs (hiện tại) xem có commit local chưa push không** — việc code ở repo con gần như luôn kéo theo cập nhật `docs/progress.md`, spec, plan, hoặc `open-decisions.md` ở đây. Nếu có, **báo cho user luôn trong cùng lượt trả lời** (không chờ hỏi riêng) rằng repo docs cũng có thay đổi chưa push, và hỏi có muốn push cùng không.

**Quy trình git giống nhau cho mọi repo, kể cả repo docs (hiện tại):** không commit/push trực tiếp lên `main`. Tạo branch mới từ `main` đã pull mới nhất (`git checkout main && git pull && git checkout -b <branch>`), commit vào đó, push branch, mở PR (`gh pr create`), rồi merge PR (`gh pr merge`) — áp dụng kể cả cho thay đổi chỉ gồm file `.md` (spec/plan/progress.md/open-decisions.md). **Không xóa branch sau khi merge** (không dùng `--delete-branch`/`-d` trên `gh pr merge`, và không tự xóa branch local/remote sau đó) — giữ lại để tham khảo lịch sử.

**Không tự động push/mở PR/merge.** Commit vào branch local là được, nhưng chỉ push/tạo PR/merge khi user yêu cầu rõ ràng ("push đi", "xong rồi, push nhé"...) — không tự làm ngay sau khi code/docs xong, dù đã có branch sẵn.

## Quy trình làm việc theo lát (từ 2026-10-24)

Làm theo từng nhóm màn hình, mỗi nhóm đi hết vòng: bàn nghiệp vụ → chốt trực quan bằng mockup → plan và code BE → plan và code FE → chạy thử cả lát → cập nhật tài liệu → ship. BE và FE là hai plan riêng (không gộp vào một plan hay task), nhưng xen kẽ trong cùng một lát. Phạm vi V1 là chức năng cơ bản nhất của một trung tâm; mở rộng sau khi hỏi giáo viên. Chi tiết, thứ tự lát và skill dùng ở mỗi bước: [docs/process/working-flow.md](docs/process/working-flow.md). Trạng thái hiện tại: `docs/progress.md`.
