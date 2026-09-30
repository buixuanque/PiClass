# Quản lý lịch học

## 1. Mục đích
Lập, xem, điều chỉnh, hủy và sắp xếp lại lịch học của lớp.

## 2. Actor
Admin; Teacher xem lịch được phân công; Student/Parent xem lịch liên quan.

## 3. Điều kiện trước
Lớp tồn tại; người thực hiện có quyền.

## 4. Trigger
Tạo lịch định kỳ/lẻ, sửa lịch, hủy hoặc dời lịch.

## 5. Luồng chính
1. Admin chọn lớp, ngày/giờ, thời lượng và thông tin lặp nếu có.
2. Hệ thống kiểm tra trùng lịch lớp/Teacher theo chính sách.
3. Admin lưu lịch; lịch hiển thị theo lớp, Teacher và Student.
4. Khi dời lịch, Admin cập nhật thời gian và ghi nhận thay đổi.
5. Khi hủy, Admin nêu lý do nếu được yêu cầu; người liên quan được thông báo theo chính sách.
6. Người dùng xem lịch cá nhân hoặc lịch lớp trong phạm vi quyền.

## 6. Luồng thay thế
1. Lịch lặp có thể sinh các buổi học tương lai; ngoại lệ một ngày có thể dời/hủy riêng. [CẦN QUYẾT ĐỊNH] Cách quản lý lịch lặp.
2. Có thể đổi lịch một buổi mà không thay lịch các buổi còn lại.

## 7. Trường hợp lỗi / ngoại lệ
1. Trùng giờ, lớp lưu trữ hoặc Teacher không còn hoạt động khiến lịch không được xác nhận.
2. Khi thay đổi lịch đã có buổi học/điểm danh, cần giữ lịch sử thay đổi.
3. Quy tắc múi giờ và thời hạn báo trước khi hủy/dời: [CẦN QUYẾT ĐỊNH].

## 8. Kết quả cuối cùng
Lịch được tạo/cập nhật/hủy; các vai trò liên quan xem được lịch mới nhất và lịch sử cần thiết.

## 9. Business Rules
1. Lịch học và Lesson Session có liên hệ nhưng là hai nghiệp vụ riêng.
2. Hủy lịch không tự xóa dữ liệu buổi học đã diễn ra.
3. Kiểm tra xung đột và thông báo thay đổi: [CẦN QUYẾT ĐỊNH].
