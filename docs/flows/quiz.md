# Quiz — bài kiểm tra nhanh

## 1. Mục đích
Tạo và vận hành bài Quiz có giới hạn thời gian, số lần làm, câu hỏi chọn lọc/ngẫu nhiên, chấm và trả kết quả.

## 2. Actor
Admin/Teacher có quyền; câu hỏi phù hợp đã có; người học thuộc nhóm được giao.

## 3. Điều kiện trước
Người tạo có quyền; câu hỏi phù hợp đã có; người học thuộc nhóm được giao.

## 4. Trigger
Người tạo tạo Quiz hoặc Student bắt đầu Quiz đã mở.

## 5. Luồng chính
1. Teacher tạo Quiz, chọn câu hỏi hoặc nhóm câu hỏi, đặt thời gian làm, số lần thử, điểm đạt và lịch mở/đóng.
2. Teacher cấu hình xáo trộn câu hỏi/đáp án hoặc lấy ngẫu nhiên nếu áp dụng.
3. Teacher xem trước và phát hành Quiz.
4. Student mở Quiz trong khung thời gian cho phép; hệ thống tạo một attempt.
5. Student trả lời câu hỏi; lựa chọn được lưu trong quá trình làm theo chính sách.
6. Student nộp bài hoặc hết giờ; attempt chuyển sang trạng thái đã nộp/hết giờ.
7. Hệ thống chấm tự động câu có đáp án xác định; câu cần đánh giá thủ công được đánh dấu chờ chấm.
8. Kết quả hiển thị theo thời điểm và mức chi tiết đã cấu hình.

## 6. Luồng thay thế
1. Student làm lại nếu còn lượt; mỗi lần là attempt riêng.
2. Student thoát giữa chừng có thể tiếp tục trong thời gian còn lại nếu chính sách cho phép.
3. Quiz có thể chọn ngẫu nhiên một số câu từ ngân hàng; mỗi attempt có bộ câu riêng.
4. Teacher đóng sớm Quiz hoặc mở lại. [CẦN QUYẾT ĐỊNH] Ảnh hưởng attempt đang diễn ra.

## 7. Trường hợp lỗi / ngoại lệ
1. Không bắt đầu ngoài khung mở/đóng hoặc khi đã dùng hết lượt.
2. Mất kết nối: khôi phục câu trả lời đã lưu; chưa lưu có thể mất theo giới hạn khả năng.
3. Hết giờ: tự động nộp phần đã lưu hoặc xử lý khác theo chính sách.
4. Nếu thiếu đáp án chấm tự động, chuyển sang chấm thủ công hoặc báo cấu hình không hợp lệ.

## 8. Kết quả cuối cùng
Mỗi attempt có câu trả lời, thời điểm kết thúc, trạng thái chấm và kết quả khi sẵn sàng.

## 9. Business Rules
1. Số lần thử, thời gian, điểm đạt, xáo trộn câu hỏi/đáp án và công bố kết quả là cấu hình nghiệp vụ.
2. Quy tắc tính điểm khi làm nhiều lần (cao nhất/lần cuối/trung bình), tính điểm đạt và xử lý câu bỏ trống: [CẦN QUYẾT ĐỊNH].
3. Mức hiển thị đáp án đúng, lời giải và điểm từng câu: [CẦN QUYẾT ĐỊNH].
