---
description: Bắt đầu session — đọc STATE.md, đối chiếu git thật ở mọi repo, báo "đang ở đâu / làm gì tiếp"
allowed-tools: Read, Bash(git fetch:*), Bash(git status:*), Bash(git log:*), Bash(git branch:*), Bash(gh pr list:*)
---

Chỉ ĐỌC và BÁO CÁO, không sửa file, không checkout, không push. Dùng khi bắt đầu làm việc hoặc đổi máy; phiên chỉ trao đổi thì không cần.

1. Đọc `docs/STATE.md`. Chỉ mở `docs/BACKLOG.md` nếu mục "Việc kế tiếp" đã hết; không mở `docs/archive/*` trừ khi cần tra đúng một chi tiết cụ thể.
2. Với mỗi repo trong `.`, `piclass-api`, `piclass-web`, `piclass-infra`, `piclass-mockup`: chạy `git fetch origin`, rồi lấy nhánh hiện tại, `git status -s`, `git log origin/main..HEAD --oneline`, và `gh pr list --state open` (nếu `gh` dùng được).
3. Quét chạm nhau: với nhánh docs đang mở khác nhánh hiện tại (`git branch -r`, `gh pr list`), chạy `git diff --stat origin/main...origin/<nhánh>`; nhánh nào sửa cùng file `STATE.md`/`decision-log.md`/spec màn với việc "Làm ngay" thì báo.
4. Đối chiếu với STATE.md: nhánh/PR/commit nào có thật mà STATE.md không ghi, hoặc STATE.md ghi nhưng git không có.
5. Trả lời ngắn (≤ 15 dòng), tiếng Việt, đúng khuôn:
   - **Đang ở:** lát + bước (theo STATE.md)
   - **Làm ngay:** mục 1 của "Việc kế tiếp"
   - **Chờ user:** các mục có (nếu không có thì bỏ dòng này)
   - **Lệch giữa STATE.md và git, nhánh docs chạm nhau:** liệt kê, hoặc "không lệch"
   - **Repo có thay đổi chưa push:** liệt kê, hoặc bỏ dòng
6. Nếu có lệch, đề xuất cách sửa STATE.md nhưng chờ user đồng ý. Hỏi một câu: có làm việc "Làm ngay" không.
