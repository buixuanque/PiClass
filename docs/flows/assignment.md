# Assignment — bài tập

## 1. Mục đích
Cho phép Admin/Teacher tạo, giao, phát hành và quản lý bài tập; kết nối với Submission và Grading.

## 2. Actor
Admin hoặc Teacher có quyền với lớp; lớp, người nhận và câu hỏi/tài liệu đã sẵn sàng.

## 3. Điều kiện trước
Người tạo có quyền với lớp; lớp, người nhận và câu hỏi/tài liệu cần thiết đã sẵn sàng.

## 4. Trigger
Người tạo bắt đầu tạo Assignment.

## 5. Luồng chính
1. Người tạo nhập tiêu đề, hướng dẫn, nội dung/câu hỏi và điểm.
2. Chọn lớp hoặc học viên nhận bài; có thể liên kết buổi học.
3. Đặt thời điểm mở, hạn nộp và chính sách nộp nếu cần.
4. Lưu bản nháp; xem trước nội dung.
5. Phát hành Assignment; người học đủ điều kiện nhìn thấy bài.
6. Student mở bài, đọc hướng dẫn và bắt đầu làm; hệ thống tạo/khôi phục bài làm.
7. Student trả lời và lưu tạm; có thể quay lại trước khi nộp.
8. Student gửi bài; hệ thống ghi nhận thời gian/trạng thái và chuyển sang chấm tự động hoặc chờ chấm thủ công.
9. Teacher chấm câu cần chấm thủ công, phản hồi và công bố kết quả theo chính sách.
10. Student và Parent xem kết quả khi được phép công bố.

## 6. Luồng thay thế
1. Bản nháp được sửa; nội dung đã phát hành có thể được sửa hạn chế. [CẦN QUYẾT ĐỊNH] Có cho sửa sau phát hành và ảnh hưởng người đã bắt đầu?
2. Cho phép làm trễ, nộp lại hoặc xin gia hạn theo cấu hình/quyết định.
3. Assignment giao cho cả lớp hoặc nhóm học viên chọn.
4. Student rời bài giữa chừng thì tiếp tục từ bản lưu gần nhất.
5. Teacher có thể thu hồi phát hành; bài làm đã có cần giữ nguyên.

## 7. Trường hợp lỗi / ngoại lệ
1. Không cho bắt đầu trước giờ mở hoặc sau thời hạn nếu chính sách không cho phép.
2. Nộp không thành công: giữ bản nháp và cho gửi lại khi có thể.
3. Câu hỏi không đủ cấu hình chấm thì chuyển chấm tay hoặc chặn phát hành.
4. Nếu phát hành nhầm hoặc lớp thay đổi, cần xác định người nhận và bài làm bị ảnh hưởng.

## 8. Kết quả cuối cùng
Assignment được lưu ở trạng thái nháp/đã phát hành/đóng/lưu trữ; mỗi Student có Submission và kết quả tương ứng.

## 9. Business Rules
1. Assignment có thể chứa câu hỏi và tài liệu đính kèm.
2. Đối tượng nhận: lớp hoặc học viên chọn.
3. Cho phép sửa/nộp lại sau submit; hạn trễ; số lần nộp; thời điểm công bố điểm; thu hồi sau khi đã có bài: [CẦN QUYẾT ĐỊNH].
4. Mặc định không tự giả định quyền sửa sau submit; cần chốt trước triển khai.
