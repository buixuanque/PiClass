# Danh mục mã lỗi nghiệp vụ (error codes)

Mọi lỗi nghiệp vụ của API trả về theo envelope `{ "error": { "code", "message", "details"? } }`. File này là danh mục các `code` do domain exception phát ra (xem rule 3 và 4 trong `piclass-api/CLAUDE.md`). FE rẽ nhánh theo `code`; `message` chỉ dùng để hiển thị.

| Code | HTTP | Module | Khi nào |
| --- | --- | --- | --- |
| `ATTEMPT_NOT_OPEN` | 409 | SubmissionGrading | Attempt đã nộp hoặc không còn mở (không ở trạng thái `in_progress`) khi nộp bài. |
| `ATTEMPT_NOT_ACCEPTING_ANSWERS` | 409 | SubmissionGrading | Lưu câu trả lời khi Attempt không còn mở. Trùng điều kiện với `ATTEMPT_NOT_OPEN` — dự kiến gộp ở checkpoint Task 7. |
| `ATTEMPT_DEADLINE_PASSED` | 409 | SubmissionGrading | Nộp bài sau hạn khi `late_policy.allow_late` là false. |
| `ATTEMPT_TIMED_OUT` | 409 | SubmissionGrading | Lưu câu trả lời khi Attempt đã hết giờ làm bài. |
| `ATTEMPT_LIMIT_REACHED` | 409 | SubmissionGrading | Bắt đầu Attempt mới khi đã dùng hết số lượt cho phép. |
| `ATTEMPT_ALREADY_CANCELLED` | 409 | SubmissionGrading | Cấp lại một Attempt đã bị hủy/cấp lại trước đó. |
| `REISSUE_NOT_APPLICABLE` | 409 | SubmissionGrading | Cấp lại Attempt cho Assessment không phải Quiz/Exam. |
| `ASSESSMENT_NOT_ASSIGNED` | 409 | SubmissionGrading | Bắt đầu Attempt khi bài không còn ở trạng thái được giao (tên dễ gây hiểu nhầm, xem checkpoint Task 7). |
| `ASSESSMENT_NOT_PUBLISHED` | 409 | SubmissionGrading | Bắt đầu Attempt khi Assessment chưa publish hoặc đã đóng. |
| `ASSESSMENT_NOT_OPEN_YET` | 409 | SubmissionGrading | Bắt đầu Attempt trước giờ mở bài (`opens_at`). |
| `ASSESSMENT_CLOSED` | 409 | SubmissionGrading | Bắt đầu Attempt sau giờ đóng bài (`closes_at`). |
| `FEEDBACK_GRADE_NOT_IN_SUBMISSION` | 422 | SubmissionGrading | Gắn Feedback vào `grade_id` không thuộc Submission này. |
| `FEEDBACK_QUESTION_NOT_IN_VERSION` | 422 | SubmissionGrading | Gắn Feedback vào câu hỏi không thuộc version của Attempt. |
| `FEEDBACK_STATUS_TRANSITION_INVALID` | 409 | SubmissionGrading | Chuyển trạng thái Feedback không hợp lệ (message nêu trạng thái từ/sang). |

## Cách thêm một dòng

- Mỗi code mới phải được thêm vào bảng trên trong cùng task/PR với class exception giới thiệu nó.
- `Code` viết UPPER_SNAKE và ổn định: không đổi tên sau khi FE đã dùng; cần đổi thì thêm code mới và loại bỏ code cũ có chủ đích.
- `Module` là module sở hữu exception (ví dụ `SubmissionGrading`); `Khi nào` mô tả ngắn điều kiện nghiệp vụ gây lỗi.
- FE chỉ rẽ nhánh theo `code`, không parse `message`.
