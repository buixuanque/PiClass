# Ranh giới module

Các module dưới đây là ranh giới nghiệp vụ logic trong cùng backend application V1. Chúng không phải microservices hay cam kết một module tương ứng một thư mục/bảng. Bảng/entity chi tiết và ownership được kiểm tra với `docs/database/*`.

| Module | Trách nhiệm | Dữ liệu sở hữu / ranh giới | Phụ thuộc chính |
|---|---|---|---|
| **Identity/Auth** | Đăng nhập/đăng xuất, mật khẩu, reset, session lifecycle; nhận diện actor và role context. | `User`, credentials/auth state, `UserRole` và liên kết account/profile theo schema. Không quyết định data scope một mình. | User Management; PostgreSQL; policy/access evaluator. |
| **User Management** | Hồ sơ `PersonProfile`, Student/Parent/Teacher profile, role assignment quản trị, Parent–Student relationship, ClassMember và TeacherClassAssignment theo quyền. | Hồ sơ, relationship và lịch sử hiệu lực tương ứng. Relationship do Admin xác nhận; unlink thu hồi Parent portal access ngay. | Identity/Auth; Academic; Audit. |
| **Academic** | Subject, Class, thành viên/phân công ở mức tích hợp, Schedule và quy tắc cấu trúc học vụ. | Subject/Class; dùng hợp đồng User Management cho membership/assignment, không tự tạo bản sao. | User Management; Lesson; Audit; Notification. |
| **Attendance** | Ghi/xem trạng thái attendance, sửa có lịch sử/lý do; nhận AbsenceExplanation từ Parent qua quyền phù hợp. | AttendanceRecord, AttendanceHistory, AbsenceExplanation. Attendance gắn LessonSession và Student; giải trình không tự đổi attendance. | Academic/Lesson; User Management; Audit; Notification. |
| **Lesson** | LessonSession, lịch mẫu liên quan, sinh session tương lai, exception dời/hủy, nội dung session và history. | Schedule, ScheduleHistory, LessonSession, LessonSessionHistory theo database model. Session hủy/đã diễn ra giữ history. | Academic; Attendance; Assessment (liên kết Assignment tối đa một session); Audit; Notification. |
| **Question Bank** | QuestionBank dùng chung, Question/QuestionVersion, QuestionOption, Topic/Tag; câu được tái sử dụng. | Nguồn câu hỏi và versions. Admin quản lý toàn kho; Teacher quản lý câu mình tạo. Assessment chỉ tiêu thụ version/snapshot, không sửa nguồn. | User Management (actor); File; Audit. |
| **Assessment** | Assignment/Quiz/Exam chung theo type/policy; draft/publish/version, audience, StudentAssessment và eligibility. | Assessment, AssessmentVersion, AssessmentAudience, StudentAssessment, AssessmentQuestion snapshots. Bản phát hành bất biến. | Question Bank; Academic/ClassMember; Lesson; User Management; Audit; Notification. |
| **Submission/Grading** | Attempt, autosaved Answer, Submission cuối, chấm tự động/thủ công, Grade/Feedback history; quản lý timer/timeout theo policy. | Attempt, AttemptQuestion, Answer, Submission, Grade, Feedback và history. Mỗi Attempt tối đa một Submission cuối để chấm. | Assessment; File; User Management (actor/scope); Audit; Notification. |
| **Result/Progress** | Tính Result đại diện theo chính sách lượt; công bố/thu hồi result; cung cấp progress cơ bản từ Result nguồn. | Result, ResultHistory, ResultPublication và dữ kiện dẫn xuất. Không tạo bảng Progress nguồn trong V1. | Submission/Grading; Assessment; User Management (scope); Notification. |
| **Notification** | Tạo, lưu, truy vấn in-app notification theo recipient/event/context, read state và dedup. | Notification, NotificationContext. Không cấp quyền vào target; mỗi lần mở lại kiểm tra context và scope hiện hành. | Các module phát sinh event; User Management; PostgreSQL. |
| **File** | Metadata, attachment link, upload/download lifecycle, visibility và kết nối storage. | Attachment, AttachmentLink và trạng thái file. Quyền kế thừa object/context; không xóa file còn reference. | Storage adapter; module sở hữu object; Authorization; Audit khi rule yêu cầu. |
| **Audit** | Append-only AuditLog cho event nhạy cảm; truy vấn theo Admin hoặc Teacher class scope. | AuditLog; không cho sửa/xóa log qua chức năng nghiệp vụ. Business history riêng vẫn ở module sở hữu. | Tất cả module tạo event; User Management/authorization cho quyền tra cứu. |

## Quy tắc phối hợp

- Module phát sinh thay đổi sở hữu module đó; ví dụ grade chỉ qua Submission/Grading, ClassMember chỉ qua User Management/Academic use case được phép.
- Application use case có thể gọi nhiều module để hoàn thành một nghiệp vụ, nhưng cần xác định một module điều phối và transaction boundary ở API/Development Plan.
- Các event như AssessmentPublished, SubmissionReceived, ResultPublished, LessonRescheduled là tên khái niệm để mô tả flow; event schema/API chưa được thiết kế.
- Identity/Auth xác thực actor; authorization evaluator kết hợp action permission + role context + scope + lifecycle + visibility theo Step 7.
- Module boundaries không tạo quyền mới. Nếu module cần field/action chưa được permission cho phép, dừng và xử lý ở source of truth thích hợp.
