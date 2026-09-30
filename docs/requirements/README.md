# Step 5 — Business Rules / Requirements

## Mục đích và phạm vi

Bộ tài liệu này chuyển Product Definition, Feature Map, User Flows và Domain Model/Glossary của Step 1–4 thành các quy tắc nghiệp vụ đã được người dùng chốt cho V1. Đây là source of truth cho business rules V1, chưa thiết kế database, API, giao diện hay cách triển khai. Chi tiết lựa chọn đã chốt nằm trong Decision Log.

## Cách sử dụng

Đọc README này để hiểu ưu tiên; dùng các file theo chủ đề làm căn cứ cho DB/API/UI ở các bước sau. Decision Log ghi các quyết định cùng mã ổn định để truy vết. Khi cách vận hành thay đổi, cập nhật Decision Log và rule liên quan đồng thời.

## Thứ tự ưu tiên source of truth

1. Các quyết định được chốt trong Step 5 và ghi tại đây.
2. Product Definition Step 1 về mục tiêu, nguyên tắc, phạm vi và ngoài phạm vi V1.
3. Feature Map Step 2 về chức năng dự kiến.
4. User Flows Step 3 về luồng và ngoại lệ.
5. Domain Model/Glossary Step 4 về khái niệm và quan hệ.

Nếu có mâu thuẫn, ưu tiên quyết định Step 5. Nếu quy trình vận hành thay đổi, sửa decision và rule tương ứng trước khi dùng tài liệu làm căn cứ thiết kế.

## Phạm vi V1

V1 phục vụ lớp học thêm: quản lý người dùng/vai trò, học viên, phụ huynh, giáo viên, môn/lớp, lịch/buổi học, điểm danh, Question Bank, Assignment, Quiz, Exam, nộp/chấm bài, kết quả/tiến độ cơ bản và thông báo học tập. V1 không gồm AI tutor, chấm/sinh câu hỏi/đề xuất bằng AI, video, lớp trực tuyến, chat, payment/học phí, ứng dụng di động, gamification, chứng chỉ hoặc phân tích nâng cao.

## Danh mục

- [Business Rules](business-rules.md): nguyên tắc và quy tắc cấp hệ thống.
- [User Rules](user-rules.md): tài khoản, vai trò, hồ sơ, quan hệ và lịch sử.
- [Learning Rules](learning-rules.md): môn, lớp, thành viên, lịch, buổi và điểm danh.
- [Assessment Rules](assessment-rules.md): hoạt động đánh giá, câu hỏi, lượt làm, chấm và kết quả.
- [Notification Rules](notification-rules.md): vòng đời, đối tượng nhận và sự kiện.
- [File and Audit Rules](file-and-audit-rules.md): tệp và dấu vết hoạt động.
- [Decision Log](decision-log.md): các quyết định đã chốt theo nhóm để tra cứu.
