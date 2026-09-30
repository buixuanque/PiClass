# Luồng dữ liệu chính

Các luồng dưới đây mô tả tương tác logic, không phải API sequence hoặc endpoint. Mọi bước backend đều kiểm tra User active, role/action permission, data scope, relationship, lifecycle và visibility theo Step 7.

## 1. Login và chọn role context

1. User gửi thông tin đăng nhập qua web client.
2. Identity/Auth xác minh credentials và trạng thái User; inactive không được truy cập mới.
3. Backend tạo session/context đăng nhập theo cơ chế sẽ được chốt; trả role context hợp lệ cho client.
4. Nếu User có nhiều role, user chọn context Admin hoặc Teacher sau login theo Step 5/7.
5. Mỗi thao tác sau đó vẫn được backend kiểm tra theo actor, context, action, scope và trạng thái dữ liệu. Biết ID hoặc gọi trực tiếp resource không thay thế authorization.

Password reset chỉ qua kênh liên hệ đã xác minh. Rate limit, MFA và session/token cụ thể còn là technology/security decision.

## 2. Teacher tạo lớp/buổi học

1. Admin tạo Subject/Class và quản lý ClassMember, TeacherClassAssignment; Teacher không tự thêm/rút Student.
2. Teacher được phân công tạo/sửa Schedule hoặc LessonSession thuộc Class scope.
3. Lesson module lưu Schedule history; lịch lặp sinh các session tương lai theo rolling window cấu hình vận hành.
4. Sửa mẫu chỉ ảnh hưởng session tương lai chưa diễn ra; exception dời/hủy áp dụng đúng một session, có reason/history và notification cho người liên quan.
5. Attendance module ghi trạng thái học viên đủ điều kiện theo membership tại thời điểm session. Sửa bởi Teacher assigned/Admin tạo history, reason và audit.

## 3. Teacher tạo và phát hành Assessment

1. Teacher tạo Assignment/Quiz/Exam trong Class được phân công; Assessment module xác thực type/policy và audience.
2. Teacher dùng câu trong Question Bank theo quyền: câu của mình hoặc câu dùng chung; không sửa câu của người khác.
3. Khi publish, backend tạo version snapshot bất biến của nội dung, đáp án, grading config và câu hỏi.
4. Hệ thống tạo/effective `StudentAssessment` duy nhất cho Student, gộp nhận qua Class/direct để tránh duplicate.
5. Notification in-app được tạo cho recipient phù hợp. Mở notification vẫn re-check permission và context.

## 4. Student làm và nộp bài

1. Student mở Assessment; backend xác nhận User active, audience, StudentAssessment, ClassMember (nếu cần), giờ mở/đóng, attempt limit và lifecycle.
2. Backend tạo Attempt gắn với AssessmentVersion đã cấp; câu trả lời autosave thuộc Attempt và được khôi phục cùng Attempt.
3. Client upload tệp trả lời chỉ trong context hợp lệ; File module xác thực permission, visibility và liên kết `AttachmentLink`.
4. Student submit hoặc timeout; backend ghi thời điểm nhận hệ thống làm căn cứ deadline, khóa nội dung theo rule và tạo tối đa một Submission cuối cho Attempt.
5. Quiz/Exam timer tiếp tục khi user rời trang/mất mạng; Exam hết giờ chung auto-submit phần đã lưu. Exam reattempt do sự cố chỉ Admin, có reason và giữ Attempt cũ.

## 5. Teacher chấm và công bố Result

1. Submission/Grading lấy các submission trong class assignment scope của Teacher hoặc quyền Admin.
2. Backend tự chấm loại câu xác định; phần cần phán đoán chờ Teacher. Teacher ghi Grade/Feedback; score bị chặn nếu vượt maximum.
3. Sửa grade đã công bố yêu cầu reason; GradeHistory/ResultHistory và AuditLog được ghi cùng thay đổi.
4. Result module tính result đại diện theo policy lượt (mặc định lần cuối; Exam mặc định lần cuối). Bài chưa chấm không tính progress trung bình; bài hủy vẫn hiện lịch sử nhưng không tính điểm.
5. Khi người tạo publish điểm/feedback hoặc visibility riêng, hệ thống ghi ResultPublication và tạo notification. Student/Parent chỉ xem sau công bố và còn quyền hiện hành.

## 6. Parent xem tiến độ

1. Parent đăng nhập; hệ thống lấy Student có ParentStudentRelationship hiện hành.
2. Mỗi truy vấn lịch, attendance, assessment, result, feedback, notification hoặc file kiểm tra relationship và visibility riêng.
3. Progress cơ bản được tính từ Result đã chấm/công bố cùng dữ kiện nguồn; không đọc bảng Progress riêng trong V1.
4. Khi Admin unlink Parent–Student, quyền truy cập portal và file/context liên quan bị thu hồi ngay, gồm dữ liệu lịch sử qua portal. Dữ liệu gốc vẫn được giữ cho Admin/Teacher có scope.
5. Parent có thể gửi AbsenceExplanation cho child/session hợp lệ; Teacher/Admin review. Giải trình không tự sửa Attendance.
