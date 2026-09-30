# Database Design Checklist

Review và đánh dấu mỗi mục: `[ ] Chốt` / `[ ] Cần chỉnh`.

## Source và phạm vi

- [ ] Database phản ánh requirements Step 5 đã xác nhận; không thêm feature ngoài V1.
- [ ] Step 6 chỉ là thiết kế logic; chưa có migration, ORM, API hoặc UI.
- [ ] Đã tách business entity và technical/support entity phù hợp.

## Identity và quan hệ

- [ ] PersonProfile dùng chung tên/contact; các role profile có thể tồn tại trước account; account link có history.
- [ ] Role nhiều-nhiều và lịch sử gán role hỗ trợ User đồng thời Admin + Teacher.
- [ ] Parent–Student many-to-many có hiệu lực theo thời gian; gỡ liên kết thu hồi quyền nhưng giữ lịch sử.
- [ ] ClassMember và TeacherClassAssignment lưu khoảng hiệu lực, kết thúc không xóa lịch sử.

## Academic và attendance

- [ ] Subject–Class, Schedule–LessonSession và AttendanceRecord khớp domain.
- [ ] Một Student có tối đa một Attendance hiện hành mỗi LessonSession; sửa có before/after, actor, thời điểm, lý do.
- [ ] Parent absence explanation được lưu và review riêng, không tự đổi Attendance.
- [ ] Hủy session không xóa Attendance/nội dung; buổi hoàn tất sửa có lịch sử.
- [ ] Schedule dùng recurrence tuần có cấu trúc, ScheduleHistory và khoảng hiệu lực nửa mở cho các quan hệ.

## Question và assessment

- [ ] Question Bank dùng chung; Question có version, Topic/Tag nhiều-nhiều và lưu trữ thay vì xóa.
- [ ] PostgreSQL/UUID là target logical; JSON theo question type được validate và snapshot phát hành bất biến.
- [ ] Đủ 9 question types Step 5 chốt; payload theo type chưa bị đóng khung quá đặc thù.
- [ ] Assignment/Quiz/Exam dùng chung Assessment + policy.
- [ ] AssessmentVersion đóng băng câu, đáp án, chấm và cấu hình phát hành.
- [ ] Giao qua Class/direct được hợp nhất thành một StudentAssessment cho mỗi học viên/bài.
- [ ] Assignment liên kết tối đa một LessonSession.

## Attempt, Submission, Result

- [ ] Attempt là lượt; Answer autosave; Submission là bản cuối duy nhất được chấm cho Attempt.
- [ ] Deadline dùng thời điểm máy chủ nhận; timeout nộp phần đã lưu.
- [ ] Cấp lại Exam tạo Attempt mới và giữ attempt cũ có lý do/liên kết.
- [ ] Grade/Result lưu lịch sử trước/sau khi sửa điểm đã công bố.
- [ ] Feedback riêng hỗ trợ chung/theo câu, tệp, visibility và history.
- [ ] Result chưa công bố không hiển thị; progress chỉ dựa trên Result đã chấm/công bố.

## Constraints, lifecycle, resolved decisions

- [ ] FK/unique/check/indexes bảo vệ các invariant đã chốt; không cascade-delete lịch sử.
- [ ] Inactive/archive/kết thúc quan hệ giữ history và không tự kích hoạt lại quan hệ cũ.
- [ ] Attachment quyền theo đối tượng; Notification idempotent theo recipient/event; Audit append-only và không thay business history.
- [ ] Attachment checksum nếu storage hỗ trợ; NotificationContext dùng FK cụ thể; progress không lưu snapshot trong V1.
- [ ] Các quyết định schema trong open-decisions đã được cập nhật nhất quán ở các tài liệu liên quan.

## Kết luận review

- Trạng thái Step 6: `[ ] Chốt` / `[ ] Cần chỉnh`
- Quyết định hoặc chỉnh sửa cần ghi lại:
  - …
