# Danh mục mã lỗi nghiệp vụ (error codes)

Mọi lỗi nghiệp vụ của API trả về theo envelope `{ "error": { "code", "message", "details"? } }`. File này là danh mục các `code` do domain exception phát ra (xem rule cốt lõi "Lỗi nghiệp vụ = domain exception" trong `piclass-api/CLAUDE.md`). FE rẽ nhánh theo `code`; `message` chỉ dùng để hiển thị.

| Code | HTTP | Module | Khi nào |
| --- | --- | --- | --- |
| `ATTEMPT_NOT_OPEN` | 409 | SubmissionGrading | Attempt không ở trạng thái `in_progress` (đã nộp hoặc không còn mở) khi nộp bài hoặc lưu câu trả lời. Trước đây có 2 code trùng điều kiện (`ATTEMPT_NOT_ACCEPTING_ANSWERS` cho lưu câu trả lời) — đã gộp còn 1 code duy nhất (2026-10-19). |
| `ATTEMPT_DEADLINE_PASSED` | 409 | SubmissionGrading | Nộp bài sau hạn khi `late_policy.allow_late` là false. |
| `ATTEMPT_TIMED_OUT` | 409 | SubmissionGrading | Lưu câu trả lời khi Attempt đã hết giờ làm bài. |
| `ATTEMPT_LIMIT_REACHED` | 409 | SubmissionGrading | Bắt đầu Attempt mới khi đã dùng hết số lượt cho phép. |
| `ATTEMPT_ALREADY_CANCELLED` | 409 | SubmissionGrading | Cấp lại một Attempt đã bị hủy/cấp lại trước đó. |
| `REISSUE_NOT_APPLICABLE` | 409 | SubmissionGrading | Cấp lại Attempt cho Assessment không phải Quiz/Exam. |
| `STUDENT_ASSESSMENT_NOT_ASSIGNED` | 409 | SubmissionGrading | Bắt đầu Attempt khi `StudentAssessment` của Student này không còn ở trạng thái `assigned` (đã làm xong/bị hủy...). Đổi tên từ `ASSESSMENT_NOT_ASSIGNED` (2026-10-19) — tên cũ dễ hiểu nhầm là "Assessment chưa giao cho Student" (vấn đề audience/eligibility), trong khi đây là trạng thái của bản ghi giao bài. |
| `ASSESSMENT_NOT_PUBLISHED` | 409 | SubmissionGrading | Bắt đầu Attempt khi Assessment chưa publish hoặc đã đóng. |
| `ASSESSMENT_NOT_OPEN_YET` | 409 | SubmissionGrading | Bắt đầu Attempt trước giờ mở bài (`opens_at`). |
| `ASSESSMENT_CLOSED` | 409 | SubmissionGrading | Bắt đầu Attempt sau giờ đóng bài (`closes_at`). |
| `FEEDBACK_GRADE_NOT_IN_SUBMISSION` | 422 | SubmissionGrading | Gắn Feedback vào `grade_id` không thuộc Submission này. |
| `FEEDBACK_QUESTION_NOT_IN_VERSION` | 422 | SubmissionGrading | Gắn Feedback vào câu hỏi không thuộc version của Attempt. |
| `FEEDBACK_STATUS_TRANSITION_INVALID` | 409 | SubmissionGrading | Chuyển trạng thái Feedback không hợp lệ (message nêu trạng thái từ/sang). |
| `STUDENT_NOT_ELIGIBLE` | 422 | Attendance | Học viên không phải ClassMember hiệu lực tại thời điểm bắt đầu buổi học (`BR-ATT-004`). Dùng ở hai nơi cùng điều kiện: điểm danh hàng loạt (message `Một số học viên không thuộc lớp này tại thời điểm buổi học.`, kèm `error.details.student_ids` là danh sách học viên vi phạm; cả lô bị từ chối, không ghi bản ghi nào) và gửi giải trình vắng (message `Học viên không thuộc lớp của buổi học này.`, không có `details`). Mã đã tồn tại từ trước refactor; ghi vào danh mục ở Task 9a. |
| `LAST_ADMIN` | 409 | UserManagement | Vô hiệu hóa User hoặc kết thúc role `admin` của Admin cuối cùng còn quyền quản trị (`BR-USER-007`). Dùng ở `PATCH /users/{user}` và `DELETE /users/{user}/roles/{roleId}`. |
| `ACTIVE_ASSIGNMENTS_EXIST` | 422 | UserManagement | Vô hiệu hóa Teacher (qua `PATCH /users/{user}`, `PATCH /teachers/{teacher}` hoặc kết thúc role `teacher`) khi còn phân công lớp đang hoạt động (`BR-USER-010`). Kèm khóa `active_class_ids` ở cấp cao nhất của JSON, cạnh `error` (không nằm trong `error.details`). |
| `ROLE_ALREADY_HELD` | 409 | UserManagement | Gán một role mà User đang giữ hiệu lực. |
| `ALREADY_INVITED` | 409 | UserManagement | Mời một hồ sơ cá nhân đã có tài khoản đăng nhập. |
| `NO_PROFILE` | 404 | UserManagement | User tự sửa hồ sơ (`PATCH /me/profile`) nhưng tài khoản chưa liên kết hồ sơ cá nhân. |
| `ALREADY_A_MEMBER` | 409 | UserManagement | Thêm học viên vào lớp khi đã là thành viên đang hoạt động (`POST /classes/{class}/members`). Học viên đã `ended` được thêm lại bằng dòng mới. |
| `ALREADY_ASSIGNED` | 409 | UserManagement | Phân công teacher vào lớp khi đã có phân công đang hoạt động (`POST /classes/{class}/teachers`). |
| `ALREADY_LINKED` | 409 | UserManagement | Tạo liên kết Parent–Student khi cặp này đã có liên kết đang hoạt động (`POST /parent-student-relationships`). |
| `ALREADY_ENDED` | 409 | UserManagement | Kết thúc một liên kết Parent–Student đã kết thúc (`DELETE /parent-student-relationships/{id}`). |
| `VALIDATION_FAILED` (message `valid_to phải sau valid_from.`) | 422 | UserManagement | Sửa hồi tố thành viên lớp (`PATCH /classes/{class}/members/{studentId}`) với `valid_to` không sau `valid_from`; so với `valid_from` đang lưu nên ném từ Action (`InvalidMembershipPeriod`), không đặt trong `rules()`. Dùng lại mã chung, không có `error.details`. |
| `NOT_FOUND` (message `Không tìm thấy.`) | 404 | UserManagement | Parent xem `GET /me/children/{studentId}` một học viên không phải con còn liên kết hiệu lực (không tồn tại, của Parent khác, liên kết đã kết thúc, hoặc không có hồ sơ Parent đều cùng thông báo, nên không lộ sự tồn tại). Khác message `Request failed.` mặc định của `ARCH-20`; giữ nguyên hành vi cũ. |

## Cách thêm một dòng

- Mỗi code mới phải được thêm vào bảng trên trong cùng task/PR với class exception giới thiệu nó.
- `Code` viết UPPER_SNAKE và ổn định: không đổi tên sau khi FE đã dùng; cần đổi thì thêm code mới và loại bỏ code cũ có chủ đích.
- `Module` là module sở hữu exception (ví dụ `SubmissionGrading`); `Khi nào` mô tả ngắn điều kiện nghiệp vụ gây lỗi.
- FE chỉ rẽ nhánh theo `code`, không parse `message`.
