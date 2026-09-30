# Cổng phụ huynh

## 1. Mục đích
Cho phép Parent theo dõi lịch học, điểm danh, bài tập, kết quả, feedback và thông báo của con đã liên kết.

## 2. Actor
Parent đã đăng nhập và có ít nhất một liên kết Parent–Student hiệu lực.

## 3. Điều kiện trước
Parent đã đăng nhập và có liên kết Parent–Student hiệu lực.

## 4. Trigger
Parent mở cổng hoặc nhận thông báo về học viên liên kết.

## 5. Luồng chính
1. Parent chọn một học viên nếu có nhiều con.
2. Parent xem lớp, lịch học sắp tới và nội dung buổi học được công bố.
3. Parent xem điểm danh theo từng buổi và lịch sử.
4. Parent xem Assignment và trạng thái hoàn thành/hạn nộp.
5. Parent xem điểm, feedback và tiến độ khi Teacher/Admin công bố.
6. Parent xem thông báo liên quan đến học viên.
7. Parent chuyển giữa các học viên đã liên kết.

## 6. Luồng thay thế
1. Nếu Parent có nhiều học viên, hệ thống yêu cầu chọn hoặc hiển thị tổng quan từng học viên.
2. Nếu liên kết vừa bị gỡ, quyền xem dữ liệu mới bị thu hồi theo chính sách. [CẦN QUYẾT ĐỊNH] Có giữ quyền xem lịch sử không?

## 7. Trường hợp lỗi / ngoại lệ
1. Parent không được sửa bài làm, điểm danh hoặc điểm của Student.
2. Không hiển thị kết quả chưa công bố hoặc dữ liệu của học viên chưa liên kết.
3. Liên kết không hợp lệ/tài khoản chưa kích hoạt thì không mở dữ liệu học tập.

## 8. Kết quả cuối cùng
Parent xem đúng dữ liệu của học viên được liên kết và nhận các thông báo được phép.

## 9. Business Rules
1. Phạm vi bao gồm lịch, buổi học, điểm danh, Assignment, kết quả, feedback, tiến độ cơ bản và thông báo.
2. Thời điểm Parent xem điểm, quyền sau khi gỡ liên kết và ai xác minh quan hệ: [CẦN QUYẾT ĐỊNH].
