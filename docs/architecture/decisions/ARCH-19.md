# ARCH-19 — Nộp bài (`SubmitAttempt`) không có audit nhưng `DEC-AUD-001` liệt kê "Submission"

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT (2026-10-19)` Phát hiện khi refactor (2026-10-18): spec Module 5 §4 không liệt kê hành động nộp bài trong bảng audit; chỉ có `grade.create/update` và `attempt.reissue`. Quyết định: thêm audit `attempt.submit` (ghi trong cùng transaction với `SubmitAttempt`, theo `DEC-AUD-005`), không bắt buộc `reason`. Start/SaveAnswer vẫn không ghi audit (chưa có yêu cầu).

**Ảnh hưởng:** Audit, `SubmitAttempt`, spec Module 5 §4 (đã thêm dòng `attempt.submit`).
