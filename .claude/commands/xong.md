---
description: Kết thúc bước/session — đồng bộ STATE.md với git thật, xóa mục đã xong, lưu spec/plan vào archive
allowed-tools: Read, Edit, Write, Bash(git fetch:*), Bash(git status:*), Bash(git log:*), Bash(git branch:*), Bash(git add:*), Bash(git commit:*), Bash(git push:*), Bash(gh pr list:*)
---

Mục tiêu: lần sau mở session (máy nào cũng được) `/tiep` đọc ra đúng sự thật.

1. Chạy các lệnh git như bước 2 của `/tiep` ở cả 5 repo để biết trạng thái thật (nhánh, commit chưa push, PR mở/đã merge).
2. Sửa `docs/STATE.md` theo đúng khuôn hiện có (không thêm mục lạ, giữ ≤ 60 dòng, không văn xuôi):
   - "Đang làm": mục "Bước" chỉ ghi lát đang làm, mỗi lát một dòng theo khuôn ở `docs/process/progress-tracking.md` (quyết định → mockup → spec → code BE → code FE → review → merge, đánh dấu bước hiện tại); lát xong thì xóa.
   - "Việc kế tiếp": bỏ việc đã xong, thêm việc mới phát sinh, mục 1 là việc làm ngay.
   - "Chờ user", bảng "Cổng": khớp git. Không ghi số PR, tên nhánh hay danh sách nhánh chưa push/chưa merge (nằm ở git, `/tiep` đọc).
   - Cập nhật dòng "Cập nhật lần cuối".
3. Lát đã xong hẳn: xóa khỏi STATE.md (chi tiết đã nằm ở mô tả PR; không ghi vào `archive/progress-log.md`). Nếu có quyết định quan trọng chưa ghi ở nguồn thì ghi vào `decision-log.md`/`decisions/ARCH-xx.md`. `git mv` spec/plan của lát đó từ `docs/superpowers/` sang `docs/archive/superpowers/` và sửa link trỏ tới chúng.
4. Việc phát sinh mà chưa làm ngay: thêm một dòng vào `docs/BACKLOG.md` (có ID `B-xxx` kế tiếp, cột Cần), không viết văn xuôi.
5. Quyết định mới chốt: ghi vào `decision-log.md` / `open-decisions.md` như `CLAUDE.md` quy định, không chỉ ở STATE.md.
6. Commit lên nhánh docs (theo quy trình git ở `CLAUDE.md`: không commit thẳng `main`; nếu đang ở `main` thì tạo nhánh từ `origin/main` mới nhất). Push nhánh đó lên `origin` (không force-push, không `--amend` commit đã push) và báo một dòng: repo, nhánh, số commit. KHÔNG mở PR/merge trừ khi user yêu cầu rõ.
7. Báo lại 3-5 dòng: STATE.md đổi gì, còn repo nào chưa push. Rồi hỏi **một câu**: có đưa `STATE.md`/`BACKLOG.md` lên `main` để máy khác thấy ngay không (nhánh đã push → PR docs ngắn tiếng Việt → merge; không bao giờ push thẳng `main`). Chỉ mở PR và merge khi user trả lời có.
