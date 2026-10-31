# Relationships và cardinality

## Identity và hồ sơ

- `PersonProfile` 1—0..1 `User` tại một thời điểm; User có thể chưa gắn profile đăng nhập. Liên kết account đổi theo thời gian được lưu ở `UserPersonProfileHistory`.
- `PersonProfile` 1—N role profiles loại Student/Parent/Teacher; một người có thể giữ nhiều role profile và dùng chung contact. Profile nghiệp vụ có thể tồn tại trước User.
- `User` N—N `Role` qua `UserRole`; mỗi lần gán có thời điểm bắt đầu/kết thúc để giữ lịch sử. Admin/Teacher có thể cùng gắn với một User.

## Parent–Student

- `Parent` N—N `Student` qua `ParentStudentRelationship` có ngày hiệu lực, loại quan hệ, trạng thái xác nhận, actor xác nhận và thời điểm kết thúc.
- Không bắt buộc Student có Parent. Gỡ liên kết thu hồi quyền Parent xem ngay, bao gồm dữ liệu cũ; bản ghi quan hệ vẫn được giữ.

## Class–ClassMember–Teacher

- `Subject` 1—N `Class`.
- `Class` N—N `Student` qua `ClassMember` có thời gian hiệu lực. Chuyển lớp kết thúc membership cũ và tạo membership mới; Attendance/Assessment/Result cũ vẫn gắn với lớp và học viên phát sinh.
- `Class` N—N `Teacher` qua `TeacherClassAssignment` có thời gian hiệu lực. Không có Teacher chính bắt buộc. Lớp có thể chuẩn bị trước khi được phân công; hoạt động khi có Teacher phụ trách.

## Schedule, LessonSession, Attendance

- `Class` 1—N `Schedule`, đồng thời 1—N `LessonSession`. Một session có thể sinh từ 0..1 Schedule hoặc tạo riêng.
- `Schedule` lưu recurrence hàng tuần bằng field cấu trúc và có `ScheduleHistory` cho mỗi sửa đổi mẫu. LessonSession giữ mốc thời gian cụ thể và không bị ghi đè khi mẫu thay đổi.
- Sửa Schedule mẫu chỉ tác động session tương lai chưa diễn ra. Exception dời/hủy session không sửa các buổi khác. Session hủy vẫn giữ nội dung và Attendance cũ để rà soát.
- `LessonSession` 1—N `AttendanceRecord`; `Student` 1—N `AttendanceRecord`. Mỗi cặp session/student chỉ có một trạng thái hiện hành. Lịch sử sửa được ghi trong `AttendanceHistory`.
- `Parent` 1—N `AbsenceExplanation`; một giải trình gắn với Student và LessonSession, có thể tham chiếu AttendanceRecord hiện có hoặc chờ bản ghi điểm danh. Teacher/Admin xem xét và ghi quyết định riêng; giải trình không tự cập nhật Attendance.

## Assessment và người nhận

- `Assessment` 1—N `AssessmentVersion`; mỗi lần phát hành có version snapshot bất biến chứa policy, thông tin bài và các `AssessmentQuestion` snapshot.
- `Assessment` có type Assignment/Quiz/Exam. Policy khác nhau nằm trong thuộc tính cấu hình; không có ba bảng bài tập/quiz/exam riêng.
- `AssessmentVersion` của **bài cá nhân** N—N `Class` và/hoặc `Student` qua `AssessmentAudience`. **Bài của khóa** (`Assessment.course_id`) không có `AssessmentAudience`; đối tượng lấy từ `CourseAudience` của khóa (`DEC-LRN-026`). Bảng `StudentAssessment` là effective assignment duy nhất cho mỗi cặp Assessment/Student, tổng hợp cách nhận qua lớp, direct assignment và khóa. Nhờ đó một Student nhận qua hai đường vẫn chỉ có một lịch sử bài/lượt.
- Một Assignment liên kết 0..1 `LessonSession`; Quiz/Exam không mặc định có liên kết session.

## Course

- `Course` 1—N `CourseAudience` (mỗi dòng một `Class` hoặc một `Student`); học viên của khóa = Student trực tiếp ∪ `ClassMember` đang hoạt động của các Class trong danh sách, tính khi cần (không có bảng ghi danh).
- `Course` 1—N `CourseItem` có thứ tự; mục `type = assessment` trỏ tới một `Assessment` có `course_id` của khóa đó (quan hệ 1—1 giữa mục và bài). Bài của khóa là bản riêng, không dùng chung giữa các khóa.
- `Course` 1—N `Assessment` (bài của khóa). Bài cá nhân (`course_id` null) và bài của khóa dùng chung engine Attempt/Submission/Grade.
- `Question.visibility` quyết định Teacher nào chọn được câu vào bài; không ảnh hưởng bài đã phát hành (có snapshot).

## Assessment–Question

- `QuestionBank` 1—N `Question`; Question N—N Topic và Tag qua bảng nối.
- `Question` 1—N `QuestionVersion`; sửa câu đã dùng tạo version mới. `QuestionOption` thuộc version cụ thể.
- `AssessmentVersion` 1—N `AssessmentQuestion`. Mỗi dòng giữ thứ tự, điểm, cấu hình chấm và snapshot nội dung/đáp án/version nguồn. AssessmentQuestion không lệ thuộc việc câu nguồn còn active.
- `AssessmentQuestion` N—N `Attempt` qua `AttemptQuestion` để lưu chính xác thứ tự đã hiển thị (ổn định trong attempt; không yêu cầu random pool V1).

## Attempt, Submission, Answer

- `StudentAssessment` 1—N `Attempt`. Attempt gắn với chính xác một `AssessmentVersion`, để chính sách và nội dung không đổi giữa lượt.
- `Attempt` 1—N `Answer`, mỗi Answer thuộc một `AssessmentQuestion` trong bản phát hành đó. Payload có thể là cấu trúc dữ liệu theo QuestionType; file trả lời được nối qua AttachmentLink.
- `Attempt` 1—0..1 `Submission` cuối cùng. Answer autosave thuộc Attempt; khi nộp/timeout, Submission ghi thời điểm nhận và trạng thái. Nộp lại theo yêu cầu tạo Attempt mới, không tạo nhiều bản nộp cuối cho một Attempt.
- Exam cấp lại lượt tạo Attempt mới; Attempt cũ còn lại, có trạng thái bị hủy do sự cố và liên kết tới Attempt thay thế.

## Grade, Result

- `Submission` 1—N `Grade` (điểm tổng hoặc theo AssessmentQuestion); sửa điểm tạo `GradeHistory` trước/sau, actor, lý do, thời điểm.
- `Submission` 1—N `Feedback`; feedback có thể là chung hoặc gắn Grade/AssessmentQuestion, có visibility, công bố/thu hồi và `FeedbackHistory`. Tệp feedback nối qua AttachmentLink.
- `Submission` 1—0..1 `Result` hiện hành. Result giữ tổng điểm, tối đa, tỷ lệ, đạt/không đạt khi được xác định, trạng thái chấm và cờ/chính sách công bố. `ResultHistory` giữ các sửa đổi tổng hợp.
- `ResultPublication` ghi sự kiện/thời điểm công bố điểm/feedback; cấu hình công bố đáp án, lời giải, điểm từng câu được lưu riêng theo AssessmentVersion.
- Assessment chọn kết quả đại diện nhiều lượt: điểm cao nhất hoặc lần cuối; mặc định lần cuối, Exam mặc định lần cuối. Result của từng Submission vẫn giữ lịch sử.

## Entity hỗ trợ

- `Attachment` N—N ngữ cảnh nghiệp vụ qua `AttachmentLink`, không dùng polymorphic FK không kiểm soát. Tệp không bị xóa khi chủ thể được archive.
- `Notification` thuộc một User nhận; unique recipient/event key ngăn cùng sự kiện gửi lặp. `NotificationContext` liên kết bằng FK cụ thể tới Assessment, LessonSession, Result hoặc Schedule; notification không tự cấp quyền đọc ngữ cảnh.
- `AuditLog` ghi actor, thời điểm, đối tượng, before/after và lý do. Business history chuyên biệt vẫn nằm ở AttendanceHistory, GradeHistory, membership và các phiên bản/lịch sử lịch.
