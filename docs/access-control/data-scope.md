# Data Scope — Phạm vi dữ liệu

## Gate truy cập chung

Cho phép một action khi (1) User active và có role phù hợp, (2) role có permission cho action/resource, (3) record thuộc scope của role, (4) trạng thái/lifecycle/time window cho phép. Cùng một quy tắc scope áp dụng cho list và truy cập một record; biết ID/URL không cấp quyền.

## Theo role

### Admin

Scope toàn hệ thống trên nghiệp vụ V1. Action ngoại lệ được giới hạn bởi lifecycle và business rule; ví dụ cấp lại Attempt Exam chỉ Admin khi sự cố được xác nhận, có lý do/audit; không được xóa AuditLog hay dữ liệu lịch sử.

### Teacher

Scope chỉ gồm Class có TeacherClassAssignment đang hiệu lực. Nhiều assignment tạo hợp scope của các lớp đó, không cấp quyền sang lớp khác cùng Subject. Quyền Teacher dựa trên phân công hiện hành.

- Student/ClassMember: học viên liên quan tới lớp và membership đúng hiệu lực. Chỉ Admin tạo/kết thúc/thay đổi ClassMember; Teacher đọc roster lớp được phân công nhưng không thêm/rút học viên.
- Schedule/LessonSession/Attendance: thuộc Class được phân công. Sửa hồi tố membership chỉ Admin; Attendance sửa bởi Teacher được phân công hoặc Admin, mọi sửa lưu history/lý do.
- Assessment/Submission/Grade: assessment và bài làm trong Class scope. Teacher grade và sửa điểm đã công bố với lý do; mọi thay đổi lưu lịch sử/audit.
- Question Bank: xem/dùng câu dùng chung theo nhu cầu tạo assessment; chỉ tạo/sửa/quản lý câu mình tạo. Không sửa câu người khác.
- Audit: xem lịch sử nghiệp vụ trong Class được phân công.

### Student

Scope của chính Student: PersonProfile contact cơ bản, role profile, ClassMember, Attendance, Assessment/StudentAssessment, Attempt/Answer/Submission, Grade/Result/Feedback và Notification của mình. Student tự sửa email/điện thoại cơ bản; không sửa tên định danh, trạng thái hay role profile. Assessment cần giao cho Student trực tiếp hoặc Class membership phù hợp. View Result/Feedback/answer key bị chặn cho tới công bố tương ứng.

### Parent

Scope chỉ gồm dữ liệu của child/children có ParentStudentRelationship hiện hành. Nếu có nhiều child, check từng relationship độc lập. Khi unlink, thu hồi toàn bộ quyền xem hiện hành và lịch sử qua Parent portal; không xóa lịch sử gốc. Parent tự sửa email/điện thoại cơ bản của mình, không sửa tên định danh, trạng thái, hồ sơ nghiệp vụ hay liên kết. Parent có thể tạo AbsenceExplanation cho child/Session hợp lệ và đọc trạng thái/response được phép.

## Scope theo thời gian và dữ liệu lịch sử

- `ParentStudentRelationship`, `ClassMember`, `TeacherClassAssignment` có khoảng hiệu lực `[valid_from, valid_to)`. `valid_to` null nghĩa còn hiệu lực.
- Teacher scope dựa phân công hiện hành; Admin xử lý trước khi Teacher bị inactive và chấm dứt quyền Teacher.
- Student Class scope dựa membership hiệu lực tại thời điểm nghiệp vụ. Sửa hồi tố membership chỉ Admin, cần lý do/audit. Chỉ Admin được tạo/kết thúc/thay đổi ClassMember. Student/Parent chỉ xem tên lớp, Subject, Schedule/LessonSession và dữ liệu học tập của mình/child; không xem roster hoặc thông tin cá nhân của peer. Teacher xem roster trong Class được phân công.
- Chuyển lớp giữ Attendance/Assessment/Result ở lớp gốc; không chuyển dữ liệu đó sang lớp mới.
- Lịch sử vẫn được giữ; chỉ Admin và Teacher còn scope phù hợp được xem theo rules. Parent unlink không còn xem cả dữ liệu quá khứ.
- AssessmentAudience được hợp nhất thành một `StudentAssessment` cho mỗi cặp Assessment/Student; giao cả qua Class lẫn trực tiếp không tạo duplicate submission scope.

## Ownership và visibility

- Student owner của Attempt/Answer/Submission của chính mình; Answer chỉ được cập nhật trong Attempt còn mở; Submit chốt tối đa một Submission cuối mỗi Attempt. Resubmit theo policy tạo Attempt mới.
- Teacher/Admin là actor của Grade; quyền sửa điểm không chuyển ownership dữ liệu bài làm. Grade history phải giữ before/after, actor, reason/time.
- Result visibility được điều khiển bởi publication state; answer/explanation/question scores có thể bật riêng.
- `AttachmentLink.visibility` cùng quyền object quyết định tệp; Parent không xem tệp bài làm private.
- Notification recipient/context không tự cấp quyền vào object được dẫn chiếu.
- AuditLog chỉ ghi append-only; view permission theo Admin toàn hệ thống, Teacher lịch sử nghiệp vụ scoped.
