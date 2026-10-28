# CLAUDE.md

Nạp tự động mọi session ở root workspace. Giữ ngắn: chỉ ghi thứ không suy ra được từ code/docs; chi tiết nằm ở `docs/`.

## Bắt đầu từ đâu

Đọc [docs/STATE.md](docs/STATE.md) (đang làm gì, kế tiếp là gì) hoặc chạy `/tiep` (đọc STATE + đối chiếu git thật ở mọi repo). Không dựa vào lịch sử chat. Không đọc mặc định: `docs/BACKLOG.md` (việc chưa làm), `docs/history/*` (lịch sử, tra khi cần), **`docs/archive/*`** (spec/plan của lát đã xong, prompt cũ — rất lớn, ~2MB; chỉ mở đúng một file khi được link tới, tuyệt đối không grep/Explore quét cả thư mục).

## Theo dõi tiến độ

- `STATE.md` là nguồn duy nhất cho "đang ở đâu". Giữ ≤ 60 dòng, mỗi mục một dòng, không văn xuôi; khuôn và quy ước bước ở cuối file đó.
- Cập nhật `STATE.md` ngay khi xong một bước nhỏ (không chờ user nhắc), cùng commit với thay đổi docs liên quan; hoặc chạy `/xong`. Việc phát sinh chưa làm: thêm một dòng vào `BACKLOG.md`. Lát xong: dời chi tiết sang `history/progress-log.md`.
- Chi tiết mới (PR số mấy, vì sao chốt) ghi vào `history/progress-log.md`, không ghi vào `STATE.md`.

## Thứ tự ưu tiên tài liệu (source of truth)

Khi mâu thuẫn, ưu tiên:

1. `docs/requirements/*` — business rules đã chốt (Step 5), Decision Log `DEC-xxx`.
2. `docs/access-control/*` — role/permission/data scope (Step 7).
3. `docs/database/*` — schema, quan hệ, lifecycle (Step 6).
4. `docs/product/*`, `docs/flows/*`, `docs/domain/*` — mục tiêu, scope, flow, thuật ngữ.
5. `docs/architecture/*` — tổ chức kỹ thuật; không được tự đổi business rule ở trên.

Chốt quyết định mới: cập nhật ngay tài liệu nguồn (Decision Log, `open-decisions.md`, `technology-decisions.md`...), không để chỉ trong chat. Phát hiện mâu thuẫn giữa tài liệu: ghi vào `open-decisions.md` của tầng liên quan, không tự chọn một phía rồi im lặng.

## Đọc file lớn theo đoạn

Các file đặc tả rất dài (`product/screens/admin.md` ~100KB, `teacher-bai-danh-gia.md`, `student-parent.md`, `requirements/decision-log.md`, `architecture/known-debt.md`): **không `Read` cả file**. `grep -n "AD-10"` / `grep -n "ARCH-37"` / `grep -n "DEC-SCH-005"` lấy số dòng, rồi `Read` với `offset`+`limit` đúng đoạn. Đầu file đặc tả màn có "Mục lục" theo mã màn. Quyết định kiến trúc: `architecture/open-decisions.md` chỉ là mục lục; `ARCH-xx` (từ 13) đọc ở `architecture/decisions/ARCH-xx.md`.

## Quy ước code

- Backend `piclass-api/`: `piclass-api/CLAUDE.md` (module `app/Modules/<Module>/`, `ArchitectureTest` canh tự động). Bẫy và cách viết test: `docs/architecture/backend-gotchas.md`.
- Frontend `piclass-web/`: `piclass-web/CLAUDE.md` (`ARCH-32`).
- Nợ kỹ thuật: `docs/architecture/known-debt.md` — chạm tới code có trong đó thì cân nhắc xử lý và xóa dòng.
- Gọi API (làm FE, tích hợp, tra response): đọc `piclass-api/storage/api-docs/openapi.json` trước (chi tiết ở `piclass-web/CLAUDE.md`). Sửa API ở BE thì export lại trước khi commit (`piclass-api/CLAUDE.md`, mục "Tài liệu API").
- Stack: Laravel + PostgreSQL + Next.js (tách riêng) + AWS EC2 + Sanctum. Chi tiết: `docs/architecture/technology-decisions.md`.

## Quy trình làm việc theo lát

Spec/plan đang làm nằm ở `docs/superpowers/specs|plans/`; lát xong thì `git mv` sang `docs/archive/superpowers/...` (và sửa link nếu có).

Mỗi nhóm màn hình đi hết vòng: bàn nghiệp vụ → mockup → plan+code BE → plan+code FE → chạy thử cả lát → cập nhật tài liệu → ship. BE và FE là hai plan riêng, xen kẽ trong cùng lát. V1 = chức năng cơ bản nhất của một trung tâm; mở rộng sau khi hỏi giáo viên. Chi tiết: [docs/process/working-flow.md](docs/process/working-flow.md).

`superpowers:brainstorming` cho việc kiến trúc/API: nạp trước `docs/requirements/*`, `docs/access-control/*`, `docs/database/*` làm nguồn; không để nó hỏi lại từ đầu hay viết spec lệch Decision Log.

## Git (nhiều repo độc lập sống cùng thư mục)

`piclass-api/`, `piclass-web/` (Next.js), `piclass-infra/`, `piclass-mockup/` là repo riêng; repo gốc này là `piclass-docs`.

- Không commit/push thẳng `main`. `git fetch origin`, branch mới từ `origin/main`, commit, push, `gh pr create`, `gh pr merge` — áp dụng cả thay đổi chỉ gồm `.md`. **Không xóa branch** sau merge (không `--delete-branch`, không tự xóa local/remote).
- **Không tự push/mở PR/merge.** Commit vào branch local là được; chỉ push/PR/merge khi user yêu cầu rõ.
- Khi push một repo con, luôn kiểm `git status`/`git log origin/main..HEAD` ở repo docs; có commit chưa push thì báo ngay trong cùng lượt trả lời và hỏi có push cùng không.

## Ngôn ngữ

- Tài liệu (mọi file `docs/`) và chat: tiếng Việt, trừ nội dung buộc tiếng Anh hoặc thuật ngữ kỹ thuật không có bản dịch.
- Comment trong code và mô tả test: tiếng Việt; comment tiếng Anh cũ trong file đang sửa thì dịch luôn (không dịch hàng loạt).
- Tên biến/hàm/class, tên test method, `error.code`, commit message: tiếng Anh.
- PR title/description: tiếng Việt.

## Hỏi để chốt thiết kế

Khi hỏi user chốt rule/thiết kế không thuần code (hiển thị theo role, business logic ảnh hưởng người dùng cuối, phân quyền): giải thích **cả hai hướng** — kỹ thuật VÀ nghiệp vụ thực tế (ai làm gì, vì sao, ảnh hưởng tới người dùng cuối). Không cần cho câu hỏi thuần kỹ thuật.

## Research reports

Dùng agent `researcher` (hoặc research/so sánh công nghệ tương tự): sau report cuối, **luôn lưu** vào `docs/research/YYYY-MM-DD-<slug>.md`. Agent không tự lưu; main session chịu trách nhiệm.

## Môi trường Claude Code

Skill (`superpowers`, `ui-ux-pro-max`, `frontend-design`) và agent cài local, không commit. Máy mới: [docs/setup/claude-skills-setup.md](docs/setup/claude-skills-setup.md), rồi `/reload-plugins`. Riêng `.claude/commands/` (`/tiep`, `/xong`) được track trong repo.
