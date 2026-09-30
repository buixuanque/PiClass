# Điểm danh

## 1. Mục đích
Ghi nhận và tra cứu tình trạng tham dự của học viên theo từng buổi học.

## 2. Actor
Teacher phụ trách; Admin; Student/Parent xem lịch sử của mình/con mình.

## 3. Điều kiện trước
Buổi học và danh sách thành viên lớp đã có.

## 4. Trigger
Teacher mở điểm danh cho buổi học.

## 5. Luồng chính
1. Teacher chọn buổi học và xem danh sách học viên có hiệu lực tại buổi đó.
2. Teacher đặt trạng thái từng học viên: có mặt, vắng, đi muộn hoặc vắng có phép.
3. Teacher thêm ghi chú khi cần và lưu điểm danh.
4. Teacher có thể chỉnh sửa theo quyền; hệ thống lưu trạng thái mới.
5. Admin xem báo cáo theo học viên, lớp hoặc khoảng thời gian.
6. Student/Parent xem lịch sử điểm danh được liên kết.

## 6. Luồng thay thế
1. Vắng có phép có thể ghi nhận lý do sau buổi học.
2. Có thể đánh dấu hàng loạt rồi điều chỉnh từng trường hợp.
3. Phụ huynh gửi yêu cầu giải trình. [CẦN QUYẾT ĐỊNH] Có hỗ trợ không?

## 7. Trường hợp lỗi / ngoại lệ
1. Không điểm danh buổi đã hủy hoặc học viên không thuộc lớp tại thời điểm đó.
2. Nếu buổi học/học viên thay đổi sau khi điểm danh, phải giữ lịch sử và báo rõ dữ liệu cần rà soát.
3. Sai trạng thái hoặc không lưu được thì không xác nhận hoàn tất.

## 8. Kết quả cuối cùng
Trạng thái hiện diện theo buổi được ghi nhận và có thể xem trong lịch sử/báo cáo.

## 9. Business Rules
1. Mỗi học viên có một trạng thái điểm danh cho mỗi buổi theo quy tắc nghiệp vụ.
2. Các trạng thái: Present, Absent, Late, Excused (tên nghiệp vụ hiển thị tiếng Việt).
3. Ai được sửa, thời gian khóa chỉnh sửa và có cần audit log: [CẦN QUYẾT ĐỊNH].
