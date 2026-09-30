# Quản lý buổi học

## 1. Mục đích
Theo dõi từng buổi học thực tế thuộc lớp, nội dung đã dạy, ghi chú và liên kết học tập.

## 2. Actor
Admin, Teacher được phân công; Student/Parent xem thông tin được công bố.

## 3. Điều kiện trước
Lớp đã tồn tại; buổi học được tạo từ lịch hoặc tạo riêng.

## 4. Trigger
Đến lịch học, hoặc Admin/Teacher tạo/cập nhật/hủy buổi học.

## 5. Luồng chính
1. Hệ thống/Teacher mở buổi học gắn với lớp, ngày giờ và số thứ tự nếu có.
2. Teacher ghi nội dung đã dạy và ghi chú sau buổi.
3. Teacher liên kết Assignment hoặc hoạt động học tập phù hợp.
4. Teacher hoàn tất buổi; điểm danh được ghi riêng theo buổi.
5. Student/Parent xem nội dung buổi học được phép công bố.
6. Admin/Teacher tra cứu lịch sử buổi học.

## 6. Luồng thay thế
1. Buổi học có thể được tạo thủ công nếu không có lịch.
2. Buổi bị hủy vẫn giữ thông tin và lý do.
3. Có thể sửa ghi chú sau khi hoàn tất. [CẦN QUYẾT ĐỊNH] Có cần lưu lịch sử chỉnh sửa không?

## 7. Trường hợp lỗi / ngoại lệ
1. Không tạo hoạt động mới cho lớp đã lưu trữ trừ khi được mở lại.
2. Hủy buổi đã điểm danh không tự xóa điểm danh; cần xử lý rõ.
3. Trùng buổi hoặc sai lớp/ngày thì không hoàn tất.

## 8. Kết quả cuối cùng
Buổi học có trạng thái và nội dung cập nhật; lịch sử điểm danh và học tập vẫn gắn đúng buổi.

## 9. Business Rules
1. Lesson Session là lần học cụ thể; Schedule là kế hoạch thời gian.
2. Chỉ người có quyền mới sửa nội dung buổi.
3. Trạng thái buổi, số thứ tự và điều kiện hoàn tất: [CẦN QUYẾT ĐỊNH].
