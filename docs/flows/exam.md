# Exam — bài kiểm tra chính thức

## 1. Mục đích
Tạo và vận hành kỳ Exam có lịch thi, khung thời gian, attempt, tự lưu, nộp/hết giờ, chấm và công bố kết quả.

## 2. Actor
Admin/Teacher có quyền; đề thi và người dự thi được xác định.

## 3. Điều kiện trước
Người tạo có quyền; đề thi và người dự thi đã được xác định.

## 4. Trigger
Người tạo lập kỳ thi hoặc đến thời điểm Student dự thi.

## 5. Luồng chính
1. Teacher tạo Exam, chọn đề/câu hỏi, người dự thi và cấu hình thời gian bắt đầu/kết thúc.
2. Đặt thời lượng làm bài, số attempt, điểm và phương thức chấm; cấu hình ngẫu nhiên nếu áp dụng.
3. Teacher xem trước, xác nhận đề và phát hành Exam.
4. Student vào trong khung giờ; hệ thống xác nhận điều kiện dự thi và mở attempt.
5. Student làm bài; câu trả lời được tự lưu định kỳ/khi thay đổi theo chính sách.
6. Student chủ động nộp; hệ thống xác nhận trạng thái cuối và ngăn sửa sau nộp theo quy tắc.
7. Khi hết thời lượng, hệ thống tự nộp phần trả lời đã lưu và đánh dấu hết giờ.
8. Câu tự chấm được chấm; câu tự luận/file được đưa vào hàng chờ chấm thủ công.
9. Teacher hoàn tất chấm và công bố kết quả; Student/Parent xem khi được phép.

## 6. Luồng thay thế
1. Student có thể vào muộn trong khung thi nhưng vẫn chỉ còn thời gian được phép. [CẦN QUYẾT ĐỊNH] Có cho vào muộn không?
2. Mất kết nối: Student quay lại cùng attempt và thời gian tiếp tục trôi hoặc tạm dừng theo chính sách.
3. Admin/Teacher có thể cấp lại attempt vì sự cố được xác nhận. [CẦN QUYẾT ĐỊNH] Ai phê duyệt và có giữ attempt cũ không?
4. Exam bị hoãn/hủy: thông báo và xử lý attempt đã mở.

## 7. Trường hợp lỗi / ngoại lệ
1. Đến trước giờ/ngoài khung thi/hết lượt thì không mở attempt.
2. Nếu tự lưu thất bại, hiển thị trạng thái lưu; khi hết giờ chỉ nộp dữ liệu đã nhận được.
3. Lỗi đề/câu hỏi sau khi bắt đầu cần được xử lý có lưu vết; không âm thầm đổi đề.
4. Không thể công bố điểm khi chấm thủ công còn thiếu nếu quy tắc yêu cầu hoàn tất.

## 8. Kết quả cuối cùng
Attempt kết thúc do nộp hoặc hết giờ; bài được chấm đầy đủ hoặc ở trạng thái chờ chấm; kết quả được công bố đúng quyền.

## 9. Business Rules
1. Exam khác Quiz ở lịch thi và yêu cầu vận hành chính thức; mức khác biệt cụ thể giữa hai loại: [CẦN QUYẾT ĐỊNH].
2. Thời lượng, khung mở/đóng, số attempt, tự lưu, vào muộn, xử lý gián đoạn, cấp lại attempt và công bố điểm phải được xác định.
3. Tự nộp lúc timeout dựa trên phần trả lời đã lưu gần nhất.
4. Quy tắc giám sát/chống gian lận không được giả định ngoài phạm vi Feature Map.
