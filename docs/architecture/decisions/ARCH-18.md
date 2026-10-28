# ARCH-18 — `GET /me/attempts/{attempt}` tự động nộp bài khi Attempt hết giờ

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT (2026-10-19)` Phát hiện khi refactor `SubmitAttempt` (2026-10-18): một request GET có tác dụng ghi (tự tạo Submission, chấm, tính Result) và bỏ qua kiểm tra `late_policy` mà POST nộp bài áp dụng. Quyết định: tôn trọng đúng logic đã chốt theo loại Assessment — Exam hết giờ luôn tự nộp (`BR-ASSESS-019`, khung giờ chung, không có khái niệm nộp trễ); Quiz/Assignment áp dụng cùng `late_policy` như nộp bài qua POST (`BR-ASSESS-016`) — nếu `allow_late=false` và đã quá hạn, GET không tự nộp, Attempt giữ nguyên `in_progress` để Student vẫn xem được (không ném lỗi ra GET). Không chuyển sang hướng Scheduler/Job vì thay đổi lớn hơn mức cần cho vấn đề đã phát hiện.

**Ảnh hưởng:** `SubmitAttempt`, `GetAttemptController`.
