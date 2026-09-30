# Step 3 — User Flow

Bộ tài liệu này mô tả **cách Admin, Teacher, Student và Parent thực hiện các chức năng trong Feature Map**. Đây là đặc tả nghiệp vụ để review trước khi chuyển sang Domain Model. Nội dung không quyết định cấu trúc dữ liệu, API, giao diện hay công nghệ triển khai.

## Cách đọc

Mỗi file theo cùng chín mục: mục đích, actor, điều kiện trước, trigger, luồng chính, luồng thay thế, lỗi/ngoại lệ, kết quả cuối cùng và quy tắc nghiệp vụ. Các chính sách chưa được chốt được đánh dấu **[CẦN QUYẾT ĐỊNH]** để rà soát ở lượt review. Các thuật ngữ như Assignment, Quiz, Exam, Submission và role giữ tiếng Anh khi cần để khớp tên chức năng/entity.

## Danh sách flow

| Nhóm | Tài liệu | Nội dung |
|---|---|---|
| Tài khoản | [authentication.md](authentication.md) | Đăng nhập, đăng xuất, khôi phục mật khẩu, user, role và trạng thái tài khoản |
| Người dùng | [student-management.md](student-management.md) | Hồ sơ, trạng thái, lịch sử học viên |
| Người dùng | [parent-management.md](parent-management.md) | Hồ sơ Parent và liên kết với học viên |
| Người dùng | [teacher-management.md](teacher-management.md) | Hồ sơ Teacher và phân công lớp |
| Học vụ | [subject-management.md](subject-management.md) | Danh mục môn học |
| Học vụ | [class-management.md](class-management.md) | Lớp, thành viên và Teacher phụ trách |
| Học vụ | [schedule-management.md](schedule-management.md) | Lịch học, dời và hủy lịch |
| Học vụ | [lesson-session.md](lesson-session.md) | Buổi học thực tế, nội dung và ghi chú |
| Học vụ | [attendance.md](attendance.md) | Điểm danh và báo cáo lịch sử |
| Nội dung học | [question-bank.md](question-bank.md) | Câu hỏi, phân loại, xem trước và tái sử dụng |
| Đánh giá | [assignment.md](assignment.md) | Tạo, phát hành, làm, lưu, nộp, chấm và trả Assignment |
| Đánh giá | [quiz.md](quiz.md) | Attempt, thời lượng, câu hỏi ngẫu nhiên, chấm tự động và kết quả |
| Đánh giá | [exam.md](exam.md) | Lịch thi, thời lượng, attempt, tự lưu, nộp/hết giờ, chấm và kết quả |
| Đánh giá | [submission.md](submission.md) | Vòng đời bài làm và trạng thái nộp |
| Đánh giá | [grading.md](grading.md) | Chấm tự động/thủ công, điểm và feedback |
| Theo dõi | [result-and-progress.md](result-and-progress.md) | Kết quả, lịch sử và tiến độ cơ bản |
| Thông tin | [notification.md](notification.md) | Tạo và phân phối thông báo |
| Cổng người dùng | [student-portal.md](student-portal.md) | Tác vụ và dữ liệu học viên tự xem |
| Cổng người dùng | [parent-portal.md](parent-portal.md) | Theo dõi học viên đã liên kết |

## Phạm vi và giả định

- Các role chính: Admin, Teacher, Student, Parent. Một tài khoản có thể đồng thời mang nhiều role, ví dụ Admin và Teacher.
- Hệ thống hỗ trợ mở rộng nhiều Teacher, nhiều lớp và nhiều nhóm học viên.
- V1 bao gồm quản lý học vụ, điểm danh, Question Bank, Assignment, Quiz, Exam, Submission, chấm điểm, tiến độ cơ bản và thông báo.
- Thanh toán và quản lý học phí không thuộc V1; thanh toán thực hiện bên ngoài hệ thống.
- Question types dự kiến: Single Choice, Multiple Choice, True/False, Fill in the Blank, Short Answer, Essay, Matching, Ordering và File Upload. Danh sách/khả năng từng loại cần được xác nhận khi chốt nghiệp vụ.
- Các flow chỉ mô tả nghiệp vụ. Không suy ra DB, API, UI hoặc cơ chế kỹ thuật từ nội dung này.

## Các quyết định cần ưu tiên review

1. Quyền sửa/nộp lại, hạn trễ và thời điểm công bố kết quả của Assignment.
2. Số attempt và cách tính điểm Quiz/Exam; hành vi khi hết giờ hoặc mất kết nối.
3. Chính sách tự lưu và khôi phục Exam, cho vào muộn/cấp lại attempt khi sự cố.
4. Quyền sửa điểm sau khi trả bài, điểm từng phần và cách làm tròn.
5. Phạm vi truy cập lịch sử của Parent sau khi gỡ liên kết.
6. Quy tắc lịch lặp, xung đột lịch, quyền chỉnh sửa điểm danh và thông báo.
