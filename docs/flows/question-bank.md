# Ngân hàng câu hỏi

## 1. Mục đích
Tạo, tổ chức, xem trước, sao chép và lưu trữ câu hỏi dùng cho Assignment, Quiz, Exam.

## 2. Actor
Admin, Teacher được quyền tạo nội dung.

## 3. Điều kiện trước
Người tạo có quyền với môn học/lớp hoặc kho câu hỏi.

## 4. Trigger
Tạo/sửa/sao chép/lưu trữ câu hỏi hoặc chọn câu hỏi cho assessment.

## 5. Luồng chính
1. Người tạo nhập nội dung câu hỏi, loại câu hỏi, đáp án/tiêu chí chấm và điểm.
2. Gắn môn học, chủ đề, độ khó, tag khi phù hợp.
3. Xem trước câu hỏi như người học sẽ thấy.
4. Lưu câu hỏi vào kho; có thể tìm kiếm và lọc.
5. Sao chép câu hỏi để tạo biến thể.
6. Khi dùng câu hỏi trong assessment, nội dung dùng cho lần đó được giữ ổn định theo chính sách phiên bản.

## 6. Luồng thay thế
1. Hỗ trợ loại: Single Choice, Multiple Choice, True/False, Fill in the Blank, Short Answer, Essay, Matching, Ordering, File Upload.
2. Câu hỏi có thể không có đáp án mẫu nếu chấm thủ công.
3. Teacher có thể dùng câu hỏi của mình hoặc kho dùng chung. [CẦN QUYẾT ĐỊNH] Quyền sở hữu/phạm vi chia sẻ.

## 7. Trường hợp lỗi / ngoại lệ
1. Thiếu đáp án/tiêu chí chấm bắt buộc theo loại câu hỏi thì không thể dùng để chấm tự động.
2. Câu hỏi đang được assessment đã phát hành sử dụng không bị sửa ngược vào bài đã làm.
3. Loại câu hỏi không hỗ trợ ở một assessment phải được báo trước.

## 8. Kết quả cuối cùng
Câu hỏi được tạo/cập nhật/lưu trữ và có thể chọn trong assessment đủ điều kiện.

## 9. Business Rules
1. Question Bank độc lập với Assignment/Quiz/Exam.
2. Câu hỏi đã sử dụng cần bảo toàn nội dung và đáp án tại thời điểm phát hành.
3. Quy tắc chỉnh sửa câu hỏi đang sử dụng, xóa/lưu trữ và loại câu hỏi V1: [CẦN QUYẾT ĐỊNH].
