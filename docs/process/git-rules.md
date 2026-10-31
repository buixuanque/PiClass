# Git: nhánh, push, PR, merge

Đọc file này khi: commit, push, mở PR, merge, xử lý xung đột, hoặc tạo nhánh mới.

Các repo `piclass-api/`, `piclass-web/`, `piclass-infra/`, `piclass-mockup/` là repo riêng; repo gốc là `piclass-docs`.

## Nhánh và push

- Không commit hoặc push thẳng `main`. `git fetch origin`, nhánh mới từ `origin/main`, commit, push, `gh pr create`. Áp dụng cả thay đổi chỉ gồm `.md`.
- Không xóa branch sau merge (không `--delete-branch`, không tự xóa local hay remote).
- **Tự push nhánh feature** (user chốt 2026-10-28) sau mỗi đơn vị việc hoàn thành (test, code, tài liệu của một mục, test xanh). Commit làm dở hoặc test đỏ thì chưa push. Không force-push, không `--amend` commit đã push (sửa bằng commit mới) trừ khi user bảo. Mỗi lần push báo một dòng: repo, nhánh, số commit.
- Khi push một repo con, kiểm `git status` và `git log origin/main..HEAD` ở repo docs; có commit chưa push thì báo trong cùng lượt trả lời.
- Nhiều repo trong một lát: code trước, docs sau cùng.

## PR và merge

- **Mở PR và merge chỉ khi user yêu cầu rõ.** "Okie" với một đề xuất về thứ tự làm việc chưa phải lệnh merge; hỏi lại khi không chắc.
- **Mỗi PR một lệnh `gh pr merge` riêng**, không gộp nhiều PR trong một lệnh.
- PR tiêu đề và mô tả tiếng Việt; commit message tiếng Anh.
- Sau mỗi lần merge: làm theo mục "Sau mỗi lần merge PR" ở `docs/process/progress-tracking.md`.

## Xung đột

- Gộp `main` vào nhánh (không rebase nhánh đã push). Xung đột ở `STATE.md`, `BACKLOG.md`, `decision-log.md`: giữ nội dung cả hai bên rồi sửa cho khớp thực tế.
