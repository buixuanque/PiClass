# Thông báo

## 1. Mục đích
Thông báo sự kiện học tập và vận hành tới đúng người dùng liên quan.

## 2. Actor
Sự kiện phát sinh hoặc Admin/Teacher có quyền tạo thông báo.

## 3. Điều kiện trước
Có sự kiện phát sinh hoặc người dùng có quyền tạo thông báo.

## 4. Trigger
Có thay đổi lịch, bài tập/kiểm tra, kết quả, điểm danh hoặc thông báo chủ động.

## 5. Luồng chính
1. Sự kiện hoặc người có quyền tạo thông báo.
2. Hệ thống xác định đối tượng nhận dựa trên lớp, tài khoản hoặc liên kết Parent–Student.
3. Thông báo được tạo với nội dung và liên kết ngữ cảnh phù hợp.
4. Người nhận xem danh sách và mở thông báo.
5. Hệ thống ghi nhận đã đọc nếu hỗ trợ.
6. Admin/Teacher xem trạng thái gửi ở mức nghiệp vụ nếu được hỗ trợ.

## 6. Luồng thay thế
1. Thông báo gửi ngay hoặc gom nhóm theo cấu hình. [CẦN QUYẾT ĐỊNH] Kênh và tần suất.
2. Người dùng có thể đánh dấu đã đọc/chưa đọc.
3. Thông báo có thể được gửi cho Parent về hoạt động của con theo chính sách.

## 7. Trường hợp lỗi / ngoại lệ
1. Không gửi nội dung riêng tư cho người không còn quyền liên kết.
2. Lỗi gửi ngoài hệ thống không được làm mất sự kiện nội bộ; có thể thử lại theo chính sách.
3. Thông báo trùng phải được hạn chế.

## 8. Kết quả cuối cùng
Thông báo được lưu/hiển thị cho đúng nhóm người nhận và có trạng thái phù hợp.

## 9. Business Rules
1. Kênh V1: thông báo trong hệ thống theo phạm vi Feature Map; email/SMS/push: [CẦN QUYẾT ĐỊNH].
2. Sự kiện cần thông báo, tùy chọn người dùng, thời hạn lưu và trạng thái đã đọc: [CẦN QUYẾT ĐỊNH].
