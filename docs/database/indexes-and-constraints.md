# Indexes and constraints

Các constraint dưới đây là yêu cầu logic cho PostgreSQL mục tiêu; mô tả không phải migration.

## Primary key và foreign key

- Mọi entity có PK ổn định; bảng nối thuần có thể dùng PK ghép nếu cần.
- FK bắt buộc cho mọi quan hệ sở hữu: role profile→PersonProfile; User→PersonProfile nullable; membership→Class/Student; Attendance→Session/Student; version→aggregate; Attempt→StudentAssessment/AssessmentVersion; Submission→Attempt; Grade/Result→Submission.
- Không cascade delete qua dữ liệu học tập, bài làm, kết quả, tệp hoặc audit. Xóa vật lý chỉ dành cho dữ liệu chưa được tham chiếu và theo chính sách lưu trữ riêng chưa đặt ở Step 5.
- FK tới actor có thể nullable chỉ khi cần giữ lịch sử sau khi account bị ẩn/không còn truy cập; ưu tiên giữ User inactive thay vì xóa.

## Unique constraints

- `PersonProfile`: index theo `full_name`; email/phone hồ sơ không bắt buộc unique. `User.login_identifier` unique khi được dùng để đăng nhập; số điện thoại không mặc định unique.
- `User.person_profile_id` unique khi không null; `UserPersonProfileHistory` chỉ có một liên kết đang hiệu lực cho mỗi User và mỗi PersonProfile.
- Mỗi bảng role profile (`Student`, `Parent`, `Teacher`) unique theo `person_profile_id`, để một người có tối đa một hồ sơ cho từng role nhưng có thể có nhiều role khác nhau.
- `UserRole`: một gán role hiệu lực cho mỗi user/role; không cho phép nhiều bản ghi chồng thời gian nếu cùng một vai trò.
- `ParentStudentRelationship`, `ClassMember`, `TeacherClassAssignment`: ngăn hai khoảng hiệu lực chồng nhau cho cặp tương ứng (trừ khi tương lai có quyết định rõ).
- `AttendanceRecord`: unique `(lesson_session_id, student_id)`.
- `AbsenceExplanation`: index `(student_id, lesson_session_id, created_at)` và `(status, created_at)` để theo dõi giải trình; không unique vì có thể cần nhiều lần gửi/trao đổi nếu policy cho phép.
- `QuestionVersion`: unique `(question_id, version_number)`; QuestionTopic/QuestionTag unique theo cặp.
- `ScheduleHistory`: unique `(schedule_id, version_number)`.
- `AssessmentVersion`: unique `(assessment_id, version_number)`; `AssessmentQuestion` unique `(assessment_version_id, position)`.
- `StudentAssessment`: unique `(assessment_id, student_id)` cho assignment hiện hành, bảo đảm giao class/direct không tạo bài trùng.
- `Attempt`: unique `(student_assessment_id, attempt_number)`.
- `AttemptQuestion`: unique `(attempt_id, assessment_question_id)` và `(attempt_id, position)`.
- `Answer`: unique `(attempt_id, assessment_question_id)`; `Submission.attempt_id` unique.
- `Grade`: unique `(submission_id, assessment_question_id)` với phân biệt grade tổng null theo khả năng DBMS; bảo đảm chỉ một tổng hiện hành và một grade cho mỗi câu.
- `Result.submission_id` unique; `Attachment.storage_key` unique; `Notification(recipient_user_id,event_key)` unique.
- `Feedback`: `grade_id` và `assessment_question_id` không đồng thời được đặt; cả hai null biểu thị feedback chung. `FeedbackHistory` append-only.
- `NotificationContext`: đúng một target FK trong Assessment/LessonSession/Result/Schedule.

## Check constraints

- Mọi khoảng hiệu lực dùng `[valid_from, valid_to)` theo instant: `valid_to IS NULL OR valid_to > valid_from`.
- Schedule: thời lượng dương; lịch lặp tuần phải có ít nhất một weekday; `effective_until` không trước `effective_from`. LessonSession: `ends_at > starts_at`; AssessmentVersion: duration/max_attempts dương nếu có; opens/closes/deadline nhất quán khi được khai báo.
- `AssessmentAudience`: đúng một trong `class_id`, `student_id` có giá trị.
- `AssessmentQuestion.points >= 0`; `Grade.score >= 0`, `max_score >= 0`, `score <= max_score`.
- `Result.percentage` trong [0,100] nếu có; `passed` nullable cho đến khi threshold có giá trị.
- Attendance status thuộc enum bốn trạng thái; LessonSession chỉ ba trạng thái đã chốt; Assessment type chỉ ba loại đã chốt.
- AttachmentLink: đúng một target FK có giá trị.
- `Feedback`: `grade_id` và `assessment_question_id` không đồng thời được đặt; cả hai null biểu thị feedback chung.
- `NotificationContext`: đúng một target FK có giá trị.
- `QuestionOption.is_correct` có thể null cho dạng không có lựa chọn; yêu cầu cụ thể theo type được kiểm tra bởi domain validation vì cấu trúc khác nhau.

## Indexes cho truy vấn nghiệp vụ

- `User(status)`, `PersonProfile(full_name)`, `Student(status, person_profile_id)`, `Parent(status, person_profile_id)`, `Teacher(status, person_profile_id)` cho quản lý hồ sơ.
- `ParentStudentRelationship(parent_id, status, valid_from, valid_to)` và đảo chiều theo student.
- `Class(subject_id, status)`; `ClassMember(class_id, status, valid_from, valid_to)` và `(student_id, status, valid_from, valid_to)`; tương tự TeacherClassAssignment theo class/teacher.
- `LessonSession(class_id, starts_at, status)`; `Schedule(class_id, status, effective_from)`; `AttendanceRecord(student_id, lesson_session_id)`.
- `Question(status, subject_id, created_by_user_id)`; `QuestionVersion(question_type, question_id)`; join indexes ngược trên Topic/Tag.
- `Assessment(status, type, created_by_user_id)`; `AssessmentVersion(assessment_id, published_at)`; `AssessmentAudience(class_id, assessment_version_id)` / `(student_id, assessment_version_id)`.
- `StudentAssessment(student_id, status, assessment_id)`; `Attempt(student_assessment_id, status, started_at)`; `Submission(received_at, status)`; `Result(publication_status, updated_at)`.
- `Notification(recipient_user_id, read_at, created_at)`; `AuditLog(entity_type, entity_id, occurred_at)` và `(actor_user_id, occurred_at)`.
- AttachmentLink theo từng FK target và Attachment status/uploader; `Feedback(submission_id, status)`; `NotificationContext` theo các FK target.

## Ràng buộc nghiệp vụ cần thực thi ở transaction/domain layer

- Attendance chỉ được ghi cho session không hủy và Student là ClassMember hiệu lực tại thời điểm buổi, trừ ngoại lệ Admin được audit.
- Bắt đầu Attempt chỉ khi đủ điều kiện, còn lượt, và lấy snapshot version được cấp cho StudentAssessment. Attempt mới chỉ tăng số lượt khi bắt đầu.
- Submission cuối chỉ chốt một lần mỗi Attempt; timestamp máy chủ là mốc deadline. Timeout chốt phần Answer đã lưu.
- AttemptQuestion chỉ tham chiếu AssessmentQuestion thuộc đúng AssessmentVersion của Attempt.
- Grade tổng/câu và Result không vượt điểm tối đa; chưa hoàn tất chấm tay thì Result không được coi là hoàn tất/công bố theo policy.
- Gỡ Parent–Student thu hồi quyền hiện tại; Notification/Attachment không cấp quyền độc lập.
- Không xóa QuestionVersion/AssessmentVersion đã được tham chiếu; sửa tạo version mới.
- Kiểm tra chồng lấn thời gian membership/phân công nên dùng exclusion constraint/transaction phù hợp PostgreSQL.
