# Step 7 — Permission & Access Control

## Mục tiêu

Mô tả ai được thực hiện action nào trên resource nào, trong data scope/ownership nào. Mô hình luôn kiểm tra đồng thời **Role → Permission → Data Scope/Ownership**; role đơn lẻ không cấp quyền trên mọi bản ghi.

## Phạm vi

Phân quyền nghiệp vụ V1 cho User và role profiles, Subject, Class, ClassMember, Schedule, LessonSession, Attendance, Question Bank/Question, Assignment/Quiz/Exam, Attempt/Submission, Grade/Result/Feedback, Notification, Attachment và AuditLog. Không thiết kế API/UI hay implementation code.

## Nguyên tắc

1. Mặc định từ chối; cần có role, action permission, scope và lifecycle hợp lệ.
2. Quyền Teacher gắn với phân công hiện hành; một Teacher có thể phụ trách nhiều Class và một Class có thể có nhiều Teacher.
3. Student chỉ thao tác với bài làm của mình. Parent chỉ thao tác trong giới hạn child đang liên kết; Parent không làm bài hoặc sửa dữ liệu học tập thay Student.
4. Parent unlink thu hồi quyền xem ngay, kể cả lịch sử qua Parent portal; dữ liệu lịch sử vẫn được giữ cho Admin/Teacher có quyền.
5. Result chỉ hiển thị sau công bố và khi người xem còn quyền với Student/Assessment. Công bố điểm, feedback, đáp án, lời giải và điểm từng câu có thể độc lập.
6. Assessment/Question đã phát hành hoặc được tham chiếu giữ snapshot/version; chỉnh sửa tạo version mới, không đổi ngược lịch sử.
7. Attachment không cấp quyền: luôn kiểm tra quyền trên object/context và visibility.
8. AuditLog append-only; role chỉ có quyền xem theo chính sách, không được update/delete log.
9. Permission nhạy cảm (reopen, cấp Attempt Exam, sửa điểm đã công bố, sửa hồi tố) phải theo rule cụ thể và ghi audit/lý do.

## Source of truth và ưu tiên

1. Quyết định đã chốt trong `docs/requirements/*` (Step 5).
2. Database Design đã chốt trong `docs/database/*` (Step 6) dùng để đối chiếu quan hệ, trạng thái, ownership và lịch sử; nếu schema gợi ra policy chưa có ở Step 5 thì không tự mở rộng business permission.
3. `docs/product/*`, `docs/flows/*`, `docs/domain/*` làm ngữ cảnh cho scope V1 và terminology.

## Tài liệu

- [roles.md](roles.md) — trách nhiệm và phạm vi từng role.
- [permission-matrix.md](permission-matrix.md) — ma trận Role × Resource × Action.
- [data-scope.md](data-scope.md) — scope, ownership và time-bound relationships.
- [resource-permissions.md](resource-permissions.md) — rule theo từng resource.
- [access-rules.md](access-rules.md) — rule đặc biệt và lifecycle.
- [open-decisions.md](open-decisions.md) — điểm cần review/chốt và checklist.
