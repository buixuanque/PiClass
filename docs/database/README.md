# Step 6 — Database Design

## Mục tiêu

Thiết kế mô hình dữ liệu V1 cho PiClass để lưu hồ sơ, tổ chức lớp, hoạt động học tập, bài làm, kết quả và lịch sử nghiệp vụ theo các quyết định đã chốt ở Step 5.

## Phạm vi

Bao gồm mô hình logic, entity/table, quan hệ, vòng đời dữ liệu, khóa và ràng buộc ở mức thiết kế. Tài liệu chưa quy định DBMS cụ thể, câu lệnh migration, ORM, API hay UI. Thiết kế không bổ sung feature ngoài V1.

## Source of truth

Thứ tự tham chiếu: `docs/requirements/*` (Step 5 đã được xác nhận) → `docs/domain/*` (Step 4) → `docs/flows/*` (Step 3) → `docs/product/*` (Step 1–2). Khi tài liệu cũ còn điểm chưa rõ, yêu cầu/decision Step 5 được ưu tiên; khoảng trống còn lại được ghi ở [open-decisions.md](open-decisions.md).

## Nguyên tắc thiết kế

- Chuẩn hóa dữ liệu nghiệp vụ; tách danh tính đăng nhập khỏi hồ sơ Student/Parent/Teacher.
- PostgreSQL là DBMS mục tiêu; dùng UUID PK. `PersonProfile` giữ tên/contact chung và tách khỏi account, các hồ sơ vai trò.
- Dùng quan hệ có thời hạn cho Parent–Student, ClassMember và TeacherClassAssignment; kết thúc quan hệ không xóa lịch sử.
- Assignment, Quiz, Exam dùng chung `Assessment` và `AssessmentVersion`; phân loại bằng type và policy.
- Giữ snapshot phát hành câu hỏi/cấu hình để sửa nội dung nguồn không đổi bài đã phát hành hoặc đã làm.
- Mỗi Attempt giữ câu trả lời đang lưu và tối đa một Submission cuối được chấm; Result/Grade có lịch sử sửa.
- Lưu trạng thái và mốc thời gian cần thiết cho truy vết; archive/inactive không đồng nghĩa xóa.
- Phân biệt entity nghiệp vụ với entity hỗ trợ như Role, Attachment, Notification và AuditLog.
- JSON dùng cho payload khác nhau theo QuestionType hoặc snapshot có cấu trúc; payload theo QuestionType cần validation theo schema tương ứng.

## Tài liệu

- [ERD](erd.md)
- [Schema](schema.md)
- [Relationships](relationships.md)
- [Indexes and Constraints](indexes-and-constraints.md)
- [Data Lifecycle](data-lifecycle.md)
- [Open Decisions](open-decisions.md)
- [Database Design Checklist](checklist.md)
