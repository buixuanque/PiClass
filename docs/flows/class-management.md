# Quản lý lớp học

## 1. Mục đích
Tạo và duy trì lớp, môn học, giáo viên và danh sách học viên.

## 2. Actor
Admin; Teacher xem/quản lý trong quyền được giao; Student và Parent xem lớp liên quan.

## 3. Điều kiện trước
Môn học và các hồ sơ cần thiết đã tồn tại.

## 4. Trigger
Admin tạo/sửa/lưu trữ lớp, phân công Teacher hoặc thêm/bớt học viên.

## 5. Luồng chính
1. Admin tạo lớp, gắn môn học và thông tin mô tả.
2. Admin phân công một hoặc nhiều Teacher.
3. Admin thêm Student vào lớp và xác nhận danh sách thành viên.
4. Admin cập nhật thông tin lớp, thành viên hoặc phân công.
5. Teacher mở lớp được giao để xem thành viên và hoạt động.
6. Student/Parent xem các lớp liên quan đến mình/con mình.
7. Khi kết thúc, Admin lưu trữ lớp; lịch sử vẫn tra cứu được.

## 6. Luồng thay thế
1. Học viên chuyển lớp: kết thúc tư cách thành viên cũ và thêm vào lớp mới, không chuyển lịch sử cũ.
2. Có thể tạo lớp trước khi thêm học viên/Teacher. [CẦN QUYẾT ĐỊNH] Có cho phép lớp chưa đủ phân công hoạt động không?

## 7. Trường hợp lỗi / ngoại lệ
1. Không thêm học viên hoặc Teacher đã vô hiệu hóa.
2. Không cho sửa thành viên làm sai lệch lịch sử điểm danh/nộp bài.
3. Lớp lưu trữ không nhận hoạt động mới trừ khi được mở lại.

## 8. Kết quả cuối cùng
Lớp cùng quan hệ môn học, Teacher, Student và trạng thái được cập nhật.

## 9. Business Rules
1. Student chỉ tham gia lớp khi có thành viên hiệu lực.
2. Teacher chỉ quản lý lớp được phân công.
3. Quy tắc sức chứa, đồng phụ trách, ngày hiệu lực thành viên và xóa/lưu trữ: [CẦN QUYẾT ĐỊNH].
