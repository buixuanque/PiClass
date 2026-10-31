# Ownership Model theo module

> Trạng thái: **đã chốt đủ** (cập nhật 2026-10-22, sau khi 12 module hoàn tất). Không còn dòng nào chờ xác nhận.

Bảng này gán mỗi Eloquent Model trong `piclass-api/app/Models` (48 file) cho đúng một module chủ, theo [module-boundaries.md](module-boundaries.md) và `docs/database/schema.md`. Dùng cho việc đặt Policy/Query và enforce quyền ghi qua `ArchitectureTest` (rule R4: module không tự ghi Model của module khác; hằng `MODEL_OWNERS` phải khớp bảng này). **Không dùng để di chuyển vị trí file** — mọi Model ở lại `App\Models` vĩnh viễn, dùng chung cho mọi module (`ARCH-17`, sửa đổi 2026-10-19). Module chủ là nơi duy nhất được ghi dữ liệu; module "chỉ đọc" chỉ truy vấn qua contract/Query của module chủ.

**Khi thêm Model mới:** thêm một dòng vào bảng này và vào `MODEL_OWNERS` trong cùng PR (`test_every_model_has_an_owner_and_the_map_is_complete` bắt thiếu). Điểm đã chốt cần nhớ: `Role` (danh mục 4 vai trò, chỉ đọc) thuộc Identity, Enum `RoleCode` ở `Modules/Identity/Enums`; `UserRole` do UserManagement ghi (hai Action gán/kết thúc role); `ParentProfile` là cùng entity với bảng `Parent` trong `schema.md` (tên Model giữ nguyên).

## Bảng ownership

| Model | Module sở hữu | Ghi chú |
|---|---|---|
| `AbsenceExplanation` | Attendance | Parent gửi giải trình qua quyền phù hợp; giải trình không tự đổi attendance. |
| `Answer` | SubmissionGrading | Autosave theo câu. Chỉ đọc: ResultProgress. |
| `Assessment` | Assessment | Aggregate identity. Chỉ đọc: SubmissionGrading, ResultProgress, Notification, Lesson (liên kết tối đa một session). |
| `AssessmentAudience` | Assessment | Phạm vi giao của bài cá nhân (Class/Student); tham chiếu Class, Student qua contract. Bài của khóa không có audience (`DEC-LRN-026`). |
| `CourseItem` | Assessment | Mục của khóa, V1 trỏ tới bài của khóa (`ARCH-38`, đề xuất). Chỉ đọc: Academic (hiển thị khóa). |
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
| `Course` | Academic | Tự học thêm, tách biệt Class (DEC-LRN-010). Chuỗi trạng thái đã thành Enum `CourseStatus` (cast, Task 9f). Chỉ đọc: Assessment (bài của khóa qua `Assessment.course_id`). |
| `CourseAudience` | Academic | Chủ ghi: Academic — thêm/gỡ Lớp hoặc Student của khóa (`DEC-LRN-026`, thay `CourseEnrollment` và `CourseClassLink`). Chỉ đọc: Assessment (eligibility của bài khóa). |
| ~~`CourseEnrollment`, `CourseClassLink`~~ | — | Bỏ ở `DEC-LRN-026`; code cũ xóa khi làm lại. |
| `Feedback` | SubmissionGrading | Có thể có Attachment qua AttachmentLink. |
| `FeedbackHistory` | SubmissionGrading | Lịch sử sửa Feedback. |
| `Grade` | SubmissionGrading | Grade chỉ đổi qua SubmissionGrading. Chỉ đọc: ResultProgress. |
| `GradeHistory` | SubmissionGrading | Lịch sử sửa Grade, bắt buộc có reason. |
| `LessonSession` | Lesson | Chỉ đọc: Attendance, Academic, Assessment, Notification. |
| `LessonSessionHistory` | Lesson | Session hủy/đã diễn ra giữ history. |
| `Notification` | Notification | Một thông báo cho một người nhận. Các module khác phát event, không ghi trực tiếp bảng. |
| `NotificationContext` | Notification | Trỏ tới đối tượng đích; không cấp quyền vào target. |
| `ParentProfile` | UserManagement | Đã chốt: `schema.md` gọi bảng này là `Parent`, code đặt tên `ParentProfile`; cùng một entity, tên Model giữ nguyên. |
| `ParentStudentRelationship` | UserManagement | Admin xác nhận; unlink thu hồi Parent portal access ngay. Chỉ đọc: Identity/ResultProgress (scope Parent). |
| `PersonProfile` | UserManagement | Hồ sơ dùng chung, có thể tồn tại trước tài khoản. Identity chỉ đọc qua liên kết User. |
| `Question` | QuestionBank | Chỉ đọc: Assessment (qua QuestionVersion). |
| `QuestionBank` | QuestionBank | Kho dùng chung; Admin quản lý toàn kho, Teacher quản lý câu mình tạo. Đã đối chiếu ở Task 9d (2026-10-22): `schema.md` và migration đều có bảng `question_banks` (`name`, `scope` mặc định `system`, `status`); chỉ `CreateQuestion` ghi bảng này (tạo kho "System Bank" khi chưa có kho nào). |
| `QuestionOption` | QuestionBank | Thuộc QuestionVersion. |
| `QuestionVersion` | QuestionBank | Assessment chỉ tiêu thụ version/snapshot, không sửa nguồn. |
| `Result` | ResultProgress | Chỉ đọc: Notification. Nguồn Progress cơ bản trong V1. |
| `ResultHistory` | ResultProgress | Mỗi lần đổi kết quả. |
| `ResultPublication` | ResultProgress | Lịch sử công bố/thu hồi. |
| `Role` | Identity | Danh mục role (chỉ đọc, do seeder tạo; `RoleCode` Enum phủ đúng 4 giá trị, có test đối chiếu). Đã chốt ở Task 9g (2026-10-22). `UserRole` do UserManagement ghi (chốt 9b). |
| `Schedule` | Lesson | Đã chốt (2026-10-21): module-boundaries dòng 11 liệt kê Schedule trong dữ liệu Lesson; dòng 9 và schema.md xếp dưới Academic chỉ là nhóm tài liệu. Toàn bộ code ghi `Schedule` nằm ở Lesson (`CreateSchedule`, `UpdateSchedule`, `CancelSchedule`, `GenerateLessonSessions`). Chỉ đọc: Academic. |
| `ScheduleHistory` | Lesson | Theo Schedule (Lesson), đã chốt cùng Task 9e. Lịch sử version mẫu. |
| `SchoolClass` | Academic | Bảng/entity Class (tên PHP Class là từ khóa nên đặt SchoolClass). Enum `ClassStatus` (cast, Task 9f). Chủ ghi `status`: Academic — `CreateClass`, `UpdateClass`, `ActivateClass`, `RevertClassToPreparing` (UserManagement gọi hai Action cuối qua DI khi phân công/kết thúc phân công Teacher, `ARCH-23(n)`). Chỉ đọc: hầu hết module khác. |
| `Student` | UserManagement | Hồ sơ vai trò học viên. Chỉ đọc: Academic, Attendance, Assessment, SubmissionGrading, ResultProgress. |
| `StudentAssessment` | Assessment | Đối tượng nhận hiệu lực, khử trùng đường giao. Chỉ đọc: SubmissionGrading. |
| `Subject` | Academic | Enum `SubjectStatus` (cast, Task 9f). Chỉ đọc: QuestionBank (Topic thuộc Subject). |
| `Submission` | SubmissionGrading | Mỗi Attempt tối đa một Submission cuối. Chỉ đọc: ResultProgress. |
| `Tag` | QuestionBank | Nối với QuestionVersion qua QuestionTag. |
| `Teacher` | UserManagement | Hồ sơ vai trò giáo viên. Chỉ đọc: Academic (owner Course), Lesson, Audit (class scope). |
| `TeacherClassAssignment` | UserManagement | Phân công theo quyền; chỉ đọc: Academic, Lesson, Attendance, Audit (Teacher class scope), Assessment. |
| `Topic` | QuestionBank | Thuộc Subject (Academic, chỉ đọc). |
| `User` | Identity | Danh tính đăng nhập, credentials/auth state. Chỉ đọc: gần như mọi module (actor, created_by...). |
| `UserRole` | UserManagement | Chủ ghi: UserManagement — `AssignUserRole`, `EndUserRole` là nơi duy nhất ghi bảng này (chốt Task 9b, 2026-10-21; trước đó ghi Identity). Chỉ đọc: Identity (`User::hasActiveRole`, `anotherActiveAdminExists`, middleware `EnsureRole`, chọn ngữ cảnh vai trò). |

## Quét giá trị `entity_type` của AuditLogger

Quét `grep -rn "entityType:" piclass-api/app`: 25 chỗ gọi `AuditLogger::record` (23 trong Controller, 2 trong `ResultCalculator`), tất cả truyền tham số có tên `entityType:`. Không có chỗ gọi nào truyền vị trí (positional) cho đối số thứ 3.

| entity_type | Nơi gọi | Loại giá trị |
|---|---|---|
| AttendanceRecord | Attendance/Actions/UpdateAttendanceRecord | Chuỗi literal |
| AbsenceExplanation | Attendance/Actions/ReviewAbsenceExplanation | Chuỗi literal |
| Grade | Grading/GradeSubmissionController, Grading/GradeQuestionController | Chuỗi literal |
| ParentStudentRelationship | UserManagement/Actions/EndParentStudentRelationship, CreateParentStudentRelationship | Chuỗi literal |
| User | UserManagement/Actions/UpdateUserStatus | Chuỗi literal |
| ClassMember | UserManagement/Actions/EndClassMember, AddClassMember, UpdateClassMember | Chuỗi literal |
| UserRole | UserManagement/Actions/EndUserRole, AssignUserRole | Chuỗi literal |
| TeacherClassAssignment | UserManagement/Actions/AssignTeacherToClass, EndTeacherClassAssignment | Chuỗi literal |
| Assessment | Assessment/Actions/CloseAssessment, CancelAssessment, PublishAssessment, ReopenAssessment | Chuỗi literal |
| Attempt | Submission/ReissueAttemptController | Chuỗi literal |
| Result | Services/Grading/ResultCalculator (2 chỗ) | Chuỗi literal |

Kết luận:

- 13 giá trị phân biệt, tất cả là chuỗi literal dạng short name. Không có giá trị nào là tên class đầy đủ (FQCN), `::class`, hay tính từ `get_class`. Rủi ro "đổi namespace Model phá dữ liệu audit cũ" không tồn tại với code hiện tại.
- Chỉ `ListAuditLogsController` lọc `entity_type`, so sánh bằng với query param người dùng truyền vào (`where('entity_type', ...)`); không map theo class. Không có morphMap/morph relation trong `app`.
- Ràng buộc cho Phase C: các giá trị này đã là dữ liệu đã lưu và là phần của API lọc, nên phải giữ nguyên literal khi chuyển namespace (không đổi sang `::class`).
