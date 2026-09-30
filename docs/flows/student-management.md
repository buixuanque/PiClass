# Quản lý học viên

## 1. Mục đích
Quản lý hồ sơ, trạng thái, lịch sử và liên kết lớp học của học viên.

## 2. Actor
Admin; Teacher xem học viên thuộc lớp được phân công.

## 3. Điều kiện trước
Người thực hiện đã đăng nhập và có quyền tương ứng.

## 4. Trigger
Admin tạo, sửa, kích hoạt/vô hiệu hóa hoặc tra cứu học viên.

## 5. Luồng chính
1. Admin mở danh sách, tìm kiếm/lọc học viên và mở hồ sơ.
2. Khi tạo mới, Admin nhập thông tin cần thiết, lưu hồ sơ và có thể khởi tạo tài khoản Student.
3. Admin cập nhật thông tin cá nhân/liên hệ và lưu thay đổi.
4. Admin xem lịch sử lớp, điểm danh, hoạt động học tập và kết quả.
5. Admin kích hoạt hoặc vô hiệu hóa hồ sơ/tài khoản theo tình trạng học viên.
6. Teacher xem danh sách và chi tiết học viên trong lớp mình phụ trách.

## 6. Luồng thay thế
1. Học viên chưa có tài khoản vẫn được lưu hồ sơ. [CẦN QUYẾT ĐỊNH] Tài khoản có bắt buộc ngay khi tạo hồ sơ không?
2. Học viên chuyển lớp giữ nguyên lịch sử cũ và bắt đầu xuất hiện trong phạm vi lớp mới.
3. Có thể tạo nhiều học viên theo danh sách nhập. [CẦN QUYẾT ĐỊNH] Phạm vi nhập hàng loạt V1.

## 7. Trường hợp lỗi / ngoại lệ
1. Thiếu dữ liệu bắt buộc hoặc thông tin trùng theo tiêu chí nhận dạng thì không lưu.
2. Teacher không được xem học viên ngoài lớp được phân công.
3. Không xóa cứng hồ sơ có lịch sử học tập; chuyển trạng thái hoặc lưu trữ.

## 8. Kết quả cuối cùng
Hồ sơ học viên được tạo/cập nhật và có thể truy xuất cùng lịch sử liên quan.

## 9. Business Rules
1. Chỉ Admin quản lý toàn cục; Teacher giới hạn theo lớp được phân công.
2. Quan hệ Parent–Student được quản lý ở Parent Management.
3. Trường hồ sơ bắt buộc, định danh trùng và quy tắc lưu trữ: [CẦN QUYẾT ĐỊNH].
