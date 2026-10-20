# Ownership Model theo module

> Trạng thái: Bản nháp, chờ user duyệt.

Bảng này gán mỗi Eloquent Model trong `piclass-api/app/Models` (48 file) cho đúng một module chủ, theo [module-boundaries.md](module-boundaries.md) và `docs/database/schema.md`. Dùng cho việc đặt Policy/Query (Task 5) và enforce quyền ghi qua `ArchitectureTest` (rule R4). **Không dùng để di chuyển vị trí file** — quyết định 2026-10-19 (xem `ARCH-17` ở `open-decisions.md`) hủy việc đổi namespace Model sang `app/Modules/<Module>/`; mọi model ở lại `App\Models` vĩnh viễn, dùng chung cho mọi module. Module chủ là nơi duy nhất được ghi dữ liệu; module "chỉ đọc" chỉ truy vấn qua contract/Query của module chủ.

Ký hiệu: "[CẦN XÁC NHẬN]" = tài liệu chưa đủ rõ để chốt, đã chọn phương án phù hợp nhất, user cần duyệt.

## Bảng ownership

| Model | Module sở hữu | Ghi chú |
|---|---|---|
| `AbsenceExplanation` | Attendance | Parent gửi giải trình qua quyền phù hợp; giải trình không tự đổi attendance. |
| `Answer` | SubmissionGrading | Autosave theo câu. Chỉ đọc: ResultProgress. |
| `Assessment` | Assessment | Aggregate identity. Chỉ đọc: SubmissionGrading, ResultProgress, Notification, Lesson (liên kết tối đa một session). |
| `AssessmentAudience` | Assessment | Phạm vi giao (Class/Student/Course); tham chiếu Class, Student, Course qua contract. |
| `AssessmentQuestion` | Assessment | Snapshot đóng băng trong version. Chỉ đọc: SubmissionGrading (AttemptQuestion, Answer, Grade tham chiếu). |
| `AssessmentVersion` | Assessment | Bản phát hành bất biến. Chỉ đọc: SubmissionGrading, ResultProgress. |
| `Attachment` | File | Metadata tệp. Chỉ đọc: QuestionBank, Assessment, SubmissionGrading (qua AttachmentLink). |
| `AttachmentLink` | File | Liên kết tới question_version/assessment_version/answer/feedback; module chủ của object đích quyết định quyền. |
| `Attempt` | SubmissionGrading | Chỉ đọc: Assessment (eligibility/lượt), ResultProgress. |
| `AttemptQuestion` | SubmissionGrading | Câu thực tế và thứ tự ổn định của một Attempt. |
| `AttendanceHistory` | Attendance | Lịch sử sửa attendance có lý do. |
| `AttendanceRecord` | Attendance | Gắn LessonSession và Student. Chỉ đọc: Lesson, Academic. |
| `AuditLog` | Audit | Append-only. Mọi module ghi qua AuditLogger; truy vấn theo Admin hoặc Teacher class scope. |
| `ClassMember` | UserManagement | Chủ ghi: UserManagement. module-boundaries dòng 22 ghi "User Management/Academic use case", nên Academic cũng xuất hiện như nơi ghi qua use case của mình; đề xuất Action ghi (add/update/end) đặt trong module UserManagement, Academic chỉ gọi qua contract. Chỉ đọc: Attendance, Assessment (audience/eligibility), SubmissionGrading, ResultProgress (scope). |
| `Course` | Academic | Tự học thêm, tách biệt Class (DEC-LRN-010). Chỉ đọc: Assessment (audience Course). |
| `CourseEnrollment` | Academic | Chỉ đọc: Assessment (eligibility). |
| `Feedback` | SubmissionGrading | Có thể có Attachment qua AttachmentLink. |
| `FeedbackHistory` | SubmissionGrading | Lịch sử sửa Feedback. |
| `Grade` | SubmissionGrading | Grade chỉ đổi qua SubmissionGrading. Chỉ đọc: ResultProgress. |
| `GradeHistory` | SubmissionGrading | Lịch sử sửa Grade, bắt buộc có reason. |
| `LessonSession` | Lesson | Chỉ đọc: Attendance, Academic, Assessment, Notification. |
| `LessonSessionHistory` | Lesson | Session hủy/đã diễn ra giữ history. |
| `Notification` | Notification | Một thông báo cho một người nhận. Các module khác phát event, không ghi trực tiếp bảng. |
| `NotificationContext` | Notification | Trỏ tới đối tượng đích; không cấp quyền vào target. |
| `ParentProfile` | UserManagement | [CẦN XÁC NHẬN] schema.md gọi bảng này là `Parent`, code đặt tên `ParentProfile`; xác nhận đây là cùng entity và có đổi tên khi chuyển namespace hay không. |
| `ParentStudentRelationship` | UserManagement | Admin xác nhận; unlink thu hồi Parent portal access ngay. Chỉ đọc: Identity/ResultProgress (scope Parent). |
| `PersonProfile` | UserManagement | Hồ sơ dùng chung, có thể tồn tại trước tài khoản. Identity chỉ đọc qua liên kết User. |
| `Question` | QuestionBank | Chỉ đọc: Assessment (qua QuestionVersion). |
| `QuestionBank` | QuestionBank | Kho dùng chung; Admin quản lý toàn kho, Teacher quản lý câu mình tạo. Không có bảng riêng trong schema.md [CẦN XÁC NHẬN] cần đối chiếu migration. |
| `QuestionOption` | QuestionBank | Thuộc QuestionVersion. |
| `QuestionVersion` | QuestionBank | Assessment chỉ tiêu thụ version/snapshot, không sửa nguồn. |
| `Result` | ResultProgress | Chỉ đọc: Notification. Nguồn Progress cơ bản trong V1. |
| `ResultHistory` | ResultProgress | Mỗi lần đổi kết quả. |
| `ResultPublication` | ResultProgress | Lịch sử công bố/thu hồi. |
| `Role` | Identity | [CẦN XÁC NHẬN] Danh mục role; boundaries không nêu Role, chọn cùng chủ với UserRole (Identity). Lưu ý boundaries cũng liệt kê UserRole dưới UserManagement. |
| `Schedule` | Lesson | [CẦN XÁC NHẬN] Chọn Lesson (module-boundaries dòng 11 liệt kê Schedule trong dữ liệu Lesson), nhưng module-boundaries dòng 9 và schema.md lại liệt kê Schedule dưới Academic. Chỉ đọc: Academic. |
| `ScheduleHistory` | Lesson | [CẦN XÁC NHẬN] Theo Schedule (Lesson); cùng mâu thuẫn Lesson/Academic như dòng Schedule. Lịch sử version mẫu. |
| `SchoolClass` | Academic | Bảng/entity Class (tên PHP Class là từ khóa nên đặt SchoolClass). Chỉ đọc: hầu hết module khác. |
| `Student` | UserManagement | Hồ sơ vai trò học viên. Chỉ đọc: Academic, Attendance, Assessment, SubmissionGrading, ResultProgress. |
| `StudentAssessment` | Assessment | Đối tượng nhận hiệu lực, khử trùng đường giao. Chỉ đọc: SubmissionGrading. |
| `Subject` | Academic | Chỉ đọc: QuestionBank (Topic thuộc Subject). |
| `Submission` | SubmissionGrading | Mỗi Attempt tối đa một Submission cuối. Chỉ đọc: ResultProgress. |
| `Tag` | QuestionBank | Nối với QuestionVersion qua QuestionTag. |
| `Teacher` | UserManagement | Hồ sơ vai trò giáo viên. Chỉ đọc: Academic (owner Course), Lesson, Audit (class scope). |
| `TeacherClassAssignment` | UserManagement | Phân công theo quyền; chỉ đọc: Academic, Lesson, Attendance, Audit (Teacher class scope), Assessment. |
| `Topic` | QuestionBank | Thuộc Subject (Academic, chỉ đọc). |
| `User` | Identity | Danh tính đăng nhập, credentials/auth state. Chỉ đọc: gần như mọi module (actor, created_by...). |
| `UserRole` | Identity | Chủ dữ liệu: Identity. Use case ghi (assign/end role) do UserManagement thực hiện (role assignment quản trị) và gọi contract Identity để ghi. [CẦN XÁC NHẬN] vì boundaries liệt kê UserRole ở cả Identity và UserManagement. |

## Quét giá trị `entity_type` của AuditLogger

Quét `grep -rn "entityType:" piclass-api/app`: 25 chỗ gọi `AuditLogger::record` (23 trong Controller, 2 trong `ResultCalculator`), tất cả truyền tham số có tên `entityType:`. Không có chỗ gọi nào truyền vị trí (positional) cho đối số thứ 3.

| entity_type | Nơi gọi | Loại giá trị |
|---|---|---|
| AttendanceRecord | Attendance/Actions/UpdateAttendanceRecord | Chuỗi literal |
| AbsenceExplanation | Attendance/Actions/ReviewAbsenceExplanation | Chuỗi literal |
| Grade | Grading/GradeSubmissionController, Grading/GradeQuestionController | Chuỗi literal |
| ParentStudentRelationship | UserManagement/EndParentStudentRelationshipController, CreateParentStudentRelationshipController | Chuỗi literal |
| User | UserManagement/UpdateUserStatusController | Chuỗi literal |
| ClassMember | UserManagement/EndClassMemberController, AddClassMemberController, UpdateClassMemberController | Chuỗi literal |
| UserRole | UserManagement/EndUserRoleController, AssignUserRoleController | Chuỗi literal |
| TeacherClassAssignment | UserManagement/AssignTeacherToClassController, EndTeacherClassAssignmentController | Chuỗi literal |
| Assessment | Assessment/CloseAssessmentController, CancelAssessmentController, PublishAssessmentController, ReopenAssessmentController | Chuỗi literal |
| Schedule | Lesson/UpdateScheduleController, CancelScheduleController | Chuỗi literal |
| LessonSession | Lesson/UpdateLessonSessionController, CancelLessonSessionController | Chuỗi literal |
| Attempt | Submission/ReissueAttemptController | Chuỗi literal |
| Result | Services/Grading/ResultCalculator (2 chỗ) | Chuỗi literal |

Kết luận:

- 13 giá trị phân biệt, tất cả là chuỗi literal dạng short name. Không có giá trị nào là tên class đầy đủ (FQCN), `::class`, hay tính từ `get_class`. Rủi ro "đổi namespace Model phá dữ liệu audit cũ" không tồn tại với code hiện tại.
- Chỉ `ListAuditLogsController` lọc `entity_type`, so sánh bằng với query param người dùng truyền vào (`where('entity_type', ...)`); không map theo class. Không có morphMap/morph relation trong `app`.
- Ràng buộc cho Phase C: các giá trị này đã là dữ liệu đã lưu và là phần của API lọc, nên phải giữ nguyên literal khi chuyển namespace (không đổi sang `::class`).
