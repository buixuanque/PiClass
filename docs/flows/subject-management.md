# Quản lý môn học

## 1. Mục đích
Quản lý danh mục môn học để phân loại lớp và nội dung học tập.

## 2. Actor
Admin; Teacher xem môn học liên quan đến lớp được giao.

## 3. Điều kiện trước
Admin có quyền quản lý dữ liệu học vụ.

## 4. Trigger
Admin tạo, sửa, kích hoạt/vô hiệu hóa môn học.

## 5. Luồng chính
1. Admin xem danh sách môn học.
2. Admin tạo môn học với tên và mô tả phù hợp.
3. Admin chỉnh sửa thông tin khi cần.
4. Admin vô hiệu hóa môn không còn sử dụng; lớp và nội dung lịch sử vẫn tham chiếu được.
5. Teacher chọn/xem môn học trong phạm vi công việc được giao.

## 6. Luồng thay thế
1. Môn học đã dùng có thể được đổi tên nhưng cần giữ ý nghĩa lịch sử. [CẦN QUYẾT ĐỊNH] Có lưu tên môn tại thời điểm phát sinh không?

## 7. Trường hợp lỗi / ngoại lệ
1. Không tạo môn trùng theo quy tắc nhận dạng.
2. Không xóa môn đang được lớp hoặc nội dung học tập sử dụng; dùng vô hiệu hóa.

## 8. Kết quả cuối cùng
Danh mục môn học sẵn sàng để gắn với lớp và nội dung.

## 9. Business Rules
1. Chỉ môn đang hoạt động được chọn cho hoạt động mới.
2. Quy tắc tên trùng và khả năng phân cấp môn/chủ đề: [CẦN QUYẾT ĐỊNH].
