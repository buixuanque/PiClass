# Open Decisions — Điểm cần review và checklist

## Trạng thái quyết định

Các permission rule Step 7 dưới đây đã có căn cứ ở `docs/requirements/*` và được đối chiếu với `docs/database/*`. Không còn giả định rằng các nhóm nêu dưới đây đều chưa chốt: nhiều quyền trước đây ghi mở thực tế đã được chốt tại project Piclass.

| Chủ đề | Trạng thái theo nguồn Piclass | Tham chiếu |
|---|---|---|
| Admin toàn hệ thống; Teacher theo assignment hiện hành; multi-role chọn context | Đã chốt | `requirements/user-rules.md`, `business-rules.md` |
| Teacher nhiều lớp; inactive phải chuyển assignment/lịch | Đã chốt | `requirements/user-rules.md`, `database/relationships.md`, `data-lifecycle.md` |
| Student/Parent/Teacher self-edit contact giới hạn; Parent link do Admin xác nhận; unlink mất cả quyền lịch sử portal | Đã chốt | `requirements/user-rules.md`, `decision-log.md` |
| Attendance sửa bởi Teacher assigned/Admin; audit/history; Parent absence explanation | Đã chốt | `requirements/learning-rules.md`, `database/schema.md` |
| Question Bank shared; Teacher quản lý câu mình tạo; Admin quản lý toàn kho | Đã chốt | `requirements/assessment-rules.md`, `database/data-lifecycle.md` |
| Version/publish/reopen/close/cancel, resubmit/Exam reattempt | Đã chốt theo từng loại rule | `requirements/assessment-rules.md`, `database/data-lifecycle.md` |
| Teacher/Admin sửa điểm published có reason/history | Đã chốt | `requirements/assessment-rules.md`, `database/schema.md` |
| Admin xem toàn AuditLog; Teacher xem lịch sử nghiệp vụ trong class scope; append-only | Đã chốt | `requirements/file-and-audit-rules.md`, `database/schema.md` |
| Parent chỉ xem/tải file theo context và publication visibility | Đã chốt | `requirements/file-and-audit-rules.md` |

## Các đề xuất Step 7 vừa được chốt

Các mục sau không còn open decisions; người dùng đã chốt phương án đề xuất và các tài liệu permission/source đã được đồng bộ:

1. ClassMember: chỉ Admin tạo/kết thúc/thay đổi; Teacher xem roster lớp được phân công nhưng không thêm/rút Student.
2. Contact profile: Student, Parent và Teacher tự sửa email/điện thoại cơ bản của mình; không tự sửa tên định danh, trạng thái, hồ sơ nghiệp vụ hay quan hệ/phân công.
3. Class/Subject visibility: Student/Parent xem tên lớp, môn, lịch/buổi và dữ liệu học tập của mình/child; không xem roster hoặc thông tin cá nhân peer. Teacher xem roster trong lớp được phân công.
4. AuditLog: schema cho phép before/after/reason nullable nói chung; action nhạy cảm bắt buộc trường tương ứng theo business rule trước khi ghi thành công.
5. Schema-to-permission alignment là tiêu chí kiểm tra cho bước thiết kế tiếp theo, không còn là quyết định permission Step 7.

## Database alignment notes

Step 6 có các entity quan trọng cho enforcement: `UserRole`, `ParentStudentRelationship`, `ClassMember`, `TeacherClassAssignment`, `AssessmentAudience`, `StudentAssessment`, `AssessmentVersion`, `Attempt`, `Submission`, `Result`, `ResultPublication`, `AttachmentLink`, `NotificationContext`, `AuditLog` cùng history tables. Khoảng hiệu lực dùng `[valid_from, valid_to)`; các FK/context không tự cấp authorization. Đây là model enforcement context, không thay đổi business rule.

## Permission Review Checklist — chốt Step 7 một lượt

| # | Checklist | Đã review |
|---:|---|:---:|
| 1 | Admin toàn hệ thống; Teacher chỉ assignment hiện hành; multi-role context | ☐ |
| 2 | Teacher nhiều Class/nhiều Teacher mỗi Class; Teacher inactive handoff | ☐ |
| 3 | Student/Parent/Teacher sửa contact cơ bản của mình; Parent chỉ child; unlink thu hồi cả lịch sử portal | ☐ |
| 4 | Admin xác nhận Parent-child; Parent gửi AbsenceExplanation, không tự sửa Attendance | ☐ |
| 5 | Admin-only ClassMember changes; Teacher xem roster assigned class; Attendance Teacher assigned/Admin với history | ☐ |
| 6 | Question Bank shared: Teacher quản lý câu mình tạo, Admin toàn kho; use không cho edit source | ☐ |
| 7 | Assessment version snapshot; publish/close/reopen/cancel, audience và duplicate assignment | ☐ |
| 8 | Attempt eligibility; resubmit tạo Attempt mới; Admin-only Exam reattempt khi sự cố xác nhận | ☐ |
| 9 | Teacher/Admin grade; sửa điểm công bố phải reason/history/audit | ☐ |
| 10 | Result/Feedback/answers visibility theo publish state và Parent visibility | ☐ |
| 11 | Notification không cấp quyền; File theo object/visibility; Parent không xem private submission | ☐ |
| 12 | AuditLog append-only; Admin xem toàn bộ, Teacher lịch sử nghiệp vụ trong assigned class | ☐ |
| 13 | Các quyết định bổ sung về ClassMember, contact self-edit, roster visibility và AuditLog được ghi đúng trong Step 5/7/6 | ☐ |
| 14 | Xác nhận Step 7 hoàn tất sau khi review, chưa chuyển Step 8 | ☐ |

### Kết quả review

Sau khi người dùng xác nhận hoặc chỉnh các mục trên, cập nhật đồng bộ `roles.md`, `permission-matrix.md`, `data-scope.md`, `resource-permissions.md`, `access-rules.md` và rules nguồn nếu policy thay đổi. Dừng tại Step 7 cho tới khi người dùng yêu cầu bước tiếp theo.
