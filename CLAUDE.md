# CLAUDE.md

Nạp tự động mọi session ở root workspace. Giữ ngắn: chỉ ghi thứ không suy ra được từ code và docs. Chi tiết nằm ở file riêng; **làm việc nào thì chỉ đọc file của việc đó**.

## Luôn nhớ (vi phạm là lỗi)

1. **Không commit hay push thẳng `main`.** Nhánh từ `origin/main`, push nhánh, PR, merge. Không xóa branch, không force-push, không `--amend` commit đã push.
2. **Mở PR và merge chỉ khi user nói rõ.** "Okie" với một đề xuất chưa phải lệnh merge. **Mỗi PR một lệnh `gh pr merge` riêng.**
3. **Review code bằng subagent chỉ dùng `model: "opus"`**, truyền tường minh; không dùng `fable`, kể cả khi skill ghi "model mạnh nhất".
4. **Vừa merge hoặc đổi trạng thái thì sửa `STATE.md` ngay trong cùng lượt**, rồi hỏi user một câu có đưa lên `main` không. Chi tiết: `docs/process/progress-tracking.md`.
5. **Quyết định mới chốt thì ghi ngay vào tài liệu nguồn** (`decision-log.md`, `open-decisions.md`...), không để chỉ trong chat.
6. **Không đọc `docs/archive/*`** (~2MB) trừ khi được link tới đúng một file; không đọc cả file đặc tả lớn (dùng `grep -n` rồi `Read` theo `offset`/`limit`).
7. Trước khi sửa code `piclass-api/` hoặc `piclass-web/`, đọc `CLAUDE.md` của repo đó.

## Việc gì thì đọc file nào

| Việc | Đọc |
|---|---|
| Bắt đầu làm việc, hỏi tiến độ, "tiếp theo là gì" | `docs/STATE.md` hoặc chạy `/tiep`. Phiên chỉ trao đổi hỏi đáp thì không đọc và không sửa `STATE.md`. |
| Cập nhật `STATE.md`, `/xong`, vừa merge PR | `docs/process/progress-tracking.md` |
| Commit, push, PR, merge, xung đột | `docs/process/git-rules.md` |
| Mở lát mới, hỏi "làm lát thế nào", cỡ việc (nhỏ hay lớn), brainstorming | `docs/process/working-flow.md` |
| Tài liệu mâu thuẫn, chốt quyết định, đọc đặc tả lớn, hỏi user chốt thiết kế, research | `docs/process/docs-conventions.md` |
| Code backend (`piclass-api/`) | `piclass-api/CLAUDE.md`, bẫy và cách viết test: `docs/architecture/backend-gotchas.md` |
| Code frontend (`piclass-web/`) | `piclass-web/CLAUDE.md`; gọi API: đọc `piclass-api/storage/api-docs/openapi.json` trước |
| Mockup | `piclass-mockup/README.md` |
| Cài skill và agent trên máy mới | `docs/setup/claude-skills-setup.md`, rồi `/reload-plugins` |
| Chọn công nghệ, stack | `docs/architecture/technology-decisions.md` (Laravel + PostgreSQL + Next.js tách riêng + AWS EC2 + Sanctum) |

## Việc nhỏ thì làm gọn

Việc nhỏ, ít rủi ro (ví dụ thêm một tham số lọc): test-first, chạy test nhóm liên quan, PHPStan và Pint, rồi PR; không viết plan riêng, không gọi reviewer. Việc lớn hoặc rủi ro cao (nhiều module, đổi schema hay phân quyền, màn hình mới): đi đủ plan, review độc lập cuối branch, chạy cả bộ test. Đầy đủ: `docs/process/working-flow.md`.

## Ngôn ngữ

- Tài liệu (mọi file `docs/`) và chat: tiếng Việt, trừ nội dung buộc tiếng Anh hoặc thuật ngữ kỹ thuật không có bản dịch.
- Comment trong code và mô tả test: tiếng Việt; comment tiếng Anh cũ trong file đang sửa thì dịch luôn (không dịch hàng loạt).
- Tên biến, hàm, class, tên test method, `error.code`, commit message: tiếng Anh.
- PR title và description: tiếng Việt.

## Cấu trúc workspace

Các repo độc lập sống cùng thư mục: `piclass-api/`, `piclass-web/`, `piclass-infra/`, `piclass-mockup/`; repo gốc này là `piclass-docs`. Skill (`superpowers`, `ui-ux-pro-max`, `frontend-design`) và agent cài local, không commit; riêng `.claude/commands/` (`/tiep`, `/xong`) được track.
