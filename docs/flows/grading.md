# Chấm điểm và phản hồi

## 1. Mục đích
Tự động chấm câu có đáp án xác định, hỗ trợ Teacher chấm thủ công và trả feedback.

## 2. Actor
Submission đã nộp; người chấm có quyền với assessment.

## 3. Điều kiện trước
Submission đã nộp; người chấm có quyền với assessment.

## 4. Trigger
Bài được nộp hoặc Teacher mở hàng chờ chấm.

## 5. Luồng chính
1. Hệ thống chấm các câu có đáp án và quy tắc đã cấu hình.
2. Câu tự luận, câu trả lời ngắn cần đánh giá hoặc file được đưa vào hàng chờ Teacher.
3. Teacher mở Submission, xem câu trả lời và tiêu chí/đáp án tham khảo.
4. Teacher nhập điểm từng câu hoặc tổng điểm, thêm nhận xét/feedback.
5. Teacher lưu điểm; khi hoàn tất, hệ thống tính tổng theo thang điểm và quy tắc.
6. Teacher công bố/trả bài; Student và Parent xem theo quyền.
7. Teacher chỉnh sửa điểm đã trả chỉ khi được phép và có lý do/lưu lịch sử theo chính sách.

## 6. Luồng thay thế
1. Có thể lưu nháp điểm trước khi hoàn tất.
2. Nhiều Teacher cùng chấm hoặc chấm ẩn danh. [CẦN QUYẾT ĐỊNH] Phạm vi V1.
3. Điểm có thể chấm theo rubric nếu được cấu hình. [CẦN QUYẾT ĐỊNH] Rubric có thuộc V1 không?

## 7. Trường hợp lỗi / ngoại lệ
1. Điểm ngoài khoảng hợp lệ không được lưu.
2. Không công bố kết quả tự động nếu còn câu bắt buộc chấm tay.
3. Nếu đáp án chuẩn thay đổi sau khi có bài làm, cần xác định cách chấm lại và lưu dấu thay đổi.
4. Không sửa điểm âm thầm sau khi đã công bố.

## 8. Kết quả cuối cùng
Submission có điểm, phản hồi, trạng thái chấm và thời điểm công bố phù hợp.

## 9. Business Rules
1. Auto grading áp dụng cho câu có thể so khớp theo quy tắc xác định.
2. Manual grading do Teacher có quyền thực hiện.
3. Cách làm tròn, điểm từng phần, trọng số, rubric, duyệt điểm và quyền sửa điểm sau trả: [CẦN QUYẾT ĐỊNH].
