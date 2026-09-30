# Roles — Vai trò và trách nhiệm

Role mô tả nhóm trách nhiệm; permission xác định action; scope xác định record/action cụ thể. User có thể có nhiều role, ví dụ Admin và Teacher. Người có nhiều role chọn ngữ cảnh làm việc; khi thực hiện action, chỉ dùng quyền phù hợp và áp scope của action đó.

| Role | Trách nhiệm V1 | Data scope mặc định |
|---|---|---|
| **Admin** | Quản trị toàn hệ thống: User/role/profile, danh mục và học vụ, phân công/liên kết, assessment, xử lý ngoại lệ, xem toàn bộ AuditLog. Có thể sửa công bố/điểm và các thao tác quản trị theo business rules; sensitive changes phải có lý do/audit nếu rule yêu cầu. | Toàn hệ thống. Lịch sử giữ nguyên; Admin không xóa vật lý dữ liệu học tập/audit chỉ vì có quyền quản trị. |
| **Teacher** | Quản lý hoạt động dạy học, roster chỉ đọc, lớp được phân công, lịch/buổi, điểm danh, assessment và chấm. Không tự thêm/rút học viên. Quản lý câu Question do mình tạo; sửa điểm đã công bố cần lý do; tự sửa email/điện thoại cơ bản. | Các Class có TeacherClassAssignment hiện hành. Quyền của nhiều assignment được hợp lại; kết thúc assignment thì mất scope hiện hành. Lịch sử nghiệp vụ của các Class được phân công được xem theo policy audit. |
| **Student** | Tham gia Class, xem Assessment được giao, tạo Attempt, trả lời và nộp bài; xem Result/Feedback của bản thân sau công bố; tự sửa email/điện thoại cơ bản. | Profile/Student record, Class membership, Attendance, assessment assignment, Attempt/Submission/Result/Notification của chính mình; các trạng thái/thời hạn vẫn phải hợp lệ. Không xem roster hoặc dữ liệu bạn học. |
| **Parent** | Theo dõi Student được liên kết; gửi AbsenceExplanation để Teacher/Admin xem xét; xem nội dung/Result đã công bố cho Parent; tự sửa email/điện thoại cơ bản của mình. Không làm bài hoặc sửa dữ liệu học tập thay Student. | Child/children có ParentStudentRelationship còn hiệu lực. Unlink lập tức thu hồi cả quyền xem lịch sử qua Parent portal. Parent chỉ xem/tải file được công bố cho Parent, không xem roster/chi tiết peer. |

## Ranh giới

- Quyền toàn hệ thống của Admin không biến AuditLog thành resource sửa/xóa được; log append-only.
- Teacher có thể làm việc trên nhiều lớp nhưng quyền từng resource vẫn bị chặn theo assignment hiện hành và Class context.
- Question Bank dùng chung không đồng nghĩa Teacher có quyền sửa câu của người khác. Teacher quản lý câu do mình tạo; Admin quản lý toàn kho.
- Quyền View/List không hàm ý Create/Update/Delete/Publish/Submit/Grade/Manage.
