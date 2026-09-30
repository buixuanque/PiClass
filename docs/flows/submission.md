# Submission — bài làm và nộp bài

## 1. Mục đích
Quản lý vòng đời bài làm của Student đối với Assignment, Quiz và Exam.

## 2. Actor
Student được giao assessment; assessment đang cho phép bắt đầu/nộp.

## 3. Điều kiện trước
Student được giao assessment; assessment đang cho phép bắt đầu hoặc nộp.

## 4. Trigger
Student bắt đầu, lưu, nộp hoặc attempt hết giờ.

## 5. Luồng chính
1. Student mở assessment và hệ thống tạo hoặc khôi phục Submission/attempt.
2. Student nhập câu trả lời theo từng loại câu hỏi và lưu tạm/tự lưu.
3. Student xem trạng thái hoàn thành và xác nhận trước khi nộp.
4. Hệ thống kiểm tra điều kiện nộp, ghi nhận nội dung và thời điểm nhận.
5. Submission chuyển đến chấm tự động/chấm thủ công; trạng thái được cập nhật.
6. Student xem trạng thái nộp và kết quả khi công bố.

## 6. Luồng thay thế
1. Nộp từng phần nếu assessment cho phép; [CẦN QUYẾT ĐỊNH] có hỗ trợ không.
2. Student tiếp tục bài làm còn dang dở sau khi quay lại.
3. Nộp lại tạo phiên bản mới hoặc thay thế bài trước theo chính sách.

## 7. Trường hợp lỗi / ngoại lệ
1. Không nộp nếu chưa bắt đầu, hết lượt hoặc bài đã khóa.
2. Lỗi mạng khi nộp: giữ nội dung cục bộ/đã lưu và cho thử lại; xác nhận rõ khi máy chủ đã nhận.
3. Tệp tải lên sai định dạng/kích thước hoặc tải chưa xong thì đánh dấu chưa hoàn tất.
4. Bản nộp trùng do gửi lại cần được xử lý idempotent về mặt nghiệp vụ, không nhân đôi bài.

## 8. Kết quả cuối cùng
Submission có trạng thái rõ ràng: đang làm, đã nộp, quá hạn/hết giờ, chờ chấm, đã chấm hoặc trạng thái khác đã thống nhất.

## 9. Business Rules
1. Một Student có thể có nhiều attempt nếu assessment cho phép.
2. Thời điểm hệ thống nhận Submission là căn cứ xác định đúng hạn.
3. Sửa sau nộp, nộp trễ, số lần nộp và cách giữ phiên bản: [CẦN QUYẾT ĐỊNH].
