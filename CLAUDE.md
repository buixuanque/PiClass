# CLAUDE.md

Nạp tự động mọi session ở root workspace. Giữ ngắn: chỉ ghi thứ không suy ra được từ code/docs; chi tiết nằm ở `docs/`.

## Bắt đầu từ đâu

Khi user **bắt đầu làm việc, hỏi tiến độ hoặc "tiếp theo là gì"**: đọc [docs/STATE.md](docs/STATE.md) hoặc chạy `/tiep` (đọc STATE + đối chiếu git thật ở mọi repo); không dựa vào lịch sử chat. Phiên chỉ trao đổi/hỏi đáp thì **không** đọc STATE và không sửa nó. Không đọc mặc định: `docs/BACKLOG.md` (việc chưa làm), **`docs/archive/*`** (spec/plan của lát đã xong, `progress-log.md` lịch sử, prompt cũ — rất lớn, ~2MB; chỉ mở đúng một file khi được link tới, tuyệt đối không grep/Explore quét cả thư mục).

## Theo dõi tiến độ

- `STATE.md` là nguồn duy nhất cho "đang ở đâu". Giữ ≤ 60 dòng, không văn xuôi, mục nhiều ý thì tách gạch con (không nhồi một dòng); khuôn và quy ước bước ở cuối file đó.
- Chỉ cập nhật `STATE.md` khi trạng thái **thật sự đổi** (chuyển bước, xong lát, phát sinh/xong việc kế tiếp), cùng commit với thay đổi docs liên quan, hoặc khi user chạy `/xong`. Không sửa sau mỗi bước nhỏ. Khi trạng thái đã đổi thì **cập nhật ngay, không hỏi user** (đừng chờ lệnh merge); cuối mỗi bước, sau khi push, tự rà một lượt: `STATE.md` đổi chưa, `BACKLOG.md` có việc mới không, `decision-log` có quyết định mới không, đã export OpenAPI chưa (nếu đổi API). Chỉ việc mở PR và merge mới phải chờ user yêu cầu. Việc phát sinh chưa làm: thêm một dòng vào `BACKLOG.md`. Lát xong: xóa khỏi `STATE.md`, `git mv` spec/plan sang `docs/archive/superpowers/`.
- **Nhánh đã push thì máy khác thấy, nhưng `main` thì chưa.** Khi cập nhật `STATE.md` mà user có thể đổi máy, hỏi một câu: có mở PR và merge để `STATE.md` lên `main` không — **vẫn theo luồng nhánh riêng → push nhánh → PR → merge, không bao giờ push thẳng `main`**. User trả lời có mới mở PR.
- **Hỏi xác nhận mục đã xong** (user chốt 2026-10-28): sau mỗi đơn vị việc hoàn thành (lúc push), đối chiếu "Việc kế tiếp" và "Chờ user" của `STATE.md` với việc vừa làm; nếu có mục có thể đã xong thì hỏi user bằng câu chọn ngắn (`AskUserQuestion`, nhiều lựa chọn), **không bắt user tự đọc STATE**. Chỉ sửa `STATE.md` theo đúng mục user xác nhận; việc của user (`[user]`) không tự suy ra là đã xong. Không có mục nào đổi thì không hỏi.
- Chi tiết của một lát (PR số mấy, vì sao chốt) nằm ở mô tả PR; quyết định quan trọng ghi vào `decision-log.md`/`decisions/ARCH-xx.md`. Không ghi dài vào `STATE.md`; `archive/progress-log.md` là bản chụp cũ, không ghi thêm.

## Thứ tự ưu tiên tài liệu (source of truth)

Khi mâu thuẫn, ưu tiên:

1. `docs/requirements/*` — business rules đã chốt (Step 5), Decision Log `DEC-xxx`.
2. `docs/access-control/*` — role/permission/data scope (Step 7).
3. `docs/database/*` — schema, quan hệ, lifecycle (Step 6).
4. `docs/product/*`, `docs/flows/*`, `docs/domain/*` — mục tiêu, scope, flow, thuật ngữ.
5. `docs/architecture/*` — tổ chức kỹ thuật; không được tự đổi business rule ở trên.

Chốt quyết định mới: cập nhật ngay tài liệu nguồn (Decision Log, `open-decisions.md`, `technology-decisions.md`...), không để chỉ trong chat. Phát hiện mâu thuẫn giữa tài liệu: ghi vào `open-decisions.md` của tầng liên quan, không tự chọn một phía rồi im lặng.

## Đọc file lớn theo đoạn

Các file đặc tả rất dài (`product/screens/admin.md` ~100KB, `teacher-bai-danh-gia.md`, `student-parent.md`, `requirements/decision-log.md`, `architecture/known-debt.md`): **không `Read` cả file**. `grep -n "AD-10"` / `grep -n "ARCH-37"` / `grep -n "DEC-SCH-005"` lấy số dòng, rồi `Read` với `offset`+`limit` đúng đoạn. Đầu file đặc tả màn có "Mục lục" theo mã màn. `architecture/known-debt.md` nhóm theo module (`grep -n "^## Lesson"`, ID `D-xx`; chạm tới code có trong đó thì cân nhắc xử lý và xóa dòng). Quyết định kiến trúc: `architecture/open-decisions.md` chỉ là mục lục; `ARCH-xx` (từ 13) đọc ở `architecture/decisions/ARCH-xx.md`.

## Quy ước code

**Trước khi sửa code trong `piclass-api/` hoặc `piclass-web/`, đọc `CLAUDE.md` của repo đó nếu chưa được nạp** (có bảng "khi nào đọc file `docs/rules/*`"); đừng bỏ qua dù user chỉ nói "sửa màn X" từ root.

- Backend `piclass-api/`: `piclass-api/CLAUDE.md` (module `app/Modules/<Module>/`, `ArchitectureTest` canh tự động). Bẫy và cách viết test: `docs/architecture/backend-gotchas.md`.
- Frontend `piclass-web/`: `piclass-web/CLAUDE.md` (`ARCH-32`). Gọi API: đọc `piclass-api/storage/api-docs/openapi.json` trước; sửa API ở BE thì export lại trước khi commit.
- Stack: Laravel + PostgreSQL + Next.js (tách riêng) + AWS EC2 + Sanctum. Chi tiết: `docs/architecture/technology-decisions.md`.

## Quy trình làm việc theo lát

Spec/plan đang làm nằm ở `docs/superpowers/specs|plans/` (lát xong: xem mục "Theo dõi tiến độ").

Mỗi nhóm màn hình đi hết vòng: bàn nghiệp vụ → mockup → plan+code BE → plan+code FE → chạy thử cả lát → cập nhật tài liệu → ship. BE và FE là hai plan riêng, xen kẽ trong cùng lát. V1 = chức năng cơ bản nhất của một trung tâm; mở rộng sau khi hỏi giáo viên. Chi tiết: [docs/process/working-flow.md](docs/process/working-flow.md).

`superpowers:brainstorming` cho việc kiến trúc/API: nạp trước `docs/requirements/*`, `docs/access-control/*`, `docs/database/*` làm nguồn; không để nó hỏi lại từ đầu hay viết spec lệch Decision Log.

**Co quy trình theo cỡ việc** (user chốt 2026-10-29): việc nhỏ, ít rủi ro (ví dụ thêm một tham số lọc vào một Query) thì làm gọn: test-first, chạy test nhóm liên quan, PHPStan + Pint, rồi PR; không viết plan riêng, không gọi reviewer độc lập, không chạy cả bộ test. Việc lớn hoặc rủi ro cao (nhiều module, đổi schema/phân quyền, màn hình mới) mới đi đủ plan, review độc lập cuối branch, chạy cả bộ test. Quy tắc cứng (nhánh từ `origin/main`, PR, PHPStan không thêm lỗi, cập nhật `STATE.md`/spec khi trạng thái đổi) luôn giữ. Không chạy test song song với subagent trên cùng DB test.

## Git (nhiều repo độc lập sống cùng thư mục)

`piclass-api/`, `piclass-web/` (Next.js), `piclass-infra/`, `piclass-mockup/` là repo riêng; repo gốc này là `piclass-docs`.

- Không commit/push thẳng `main`. `git fetch origin`, branch mới từ `origin/main`, commit, push, `gh pr create`, `gh pr merge` — áp dụng cả thay đổi chỉ gồm `.md`. **Không xóa branch** sau merge (không `--delete-branch`, không tự xóa local/remote).
- **Tự push nhánh feature** (user chốt 2026-10-28): sau mỗi đơn vị việc hoàn thành (test + code + tài liệu của một mục, test xanh) thì push nhánh đang làm lên `origin`; commit làm dở hoặc test đỏ thì chưa push. Chỉ push nhánh feature, **không bao giờ push `main`** (đang ở `main` thì tạo nhánh từ `origin/main` trước khi commit). **Không force-push, không `--amend` commit đã push** (sửa bằng commit mới) trừ khi user bảo. Mỗi lần push báo một dòng: repo, nhánh, số commit.
- **Mở PR và merge chỉ khi user yêu cầu rõ.**
- Khi push một repo con, kiểm `git status`/`git log origin/main..HEAD` ở repo docs; có commit chưa push thì báo trong cùng lượt trả lời.

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
