# Quản lý giáo viên

## 1. Mục đích
Quản lý hồ sơ Teacher, trạng thái hoạt động và phân công lớp.

## 2. Actor
Admin; Teacher xem hồ sơ và lớp được phân công.

## 3. Điều kiện trước
Admin đã đăng nhập và có quyền quản lý giáo viên.

## 4. Trigger
Admin tạo/cập nhật Teacher, kích hoạt/vô hiệu hóa hoặc phân công lớp.

## 5. Luồng chính
1. Admin tạo hồ sơ Teacher và tài khoản tương ứng hoặc liên kết user có sẵn.
2. Admin cập nhật thông tin hồ sơ, chuyên môn/liên hệ.
3. Admin phân công Teacher vào một hay nhiều lớp.
4. Teacher đăng nhập, xem hồ sơ và danh sách lớp được phân công.
5. Khi Teacher ngừng hoạt động, Admin rà soát phân công và cập nhật người phụ trách.

## 6. Luồng thay thế
1. Một user có thể đồng thời có role Admin và Teacher.
2. Lớp có thể có nhiều Teacher theo phạm vi mở rộng đã định hướng.

## 7. Trường hợp lỗi / ngoại lệ
1. Không cho Teacher truy cập lớp chưa phân công.
2. Vô hiệu hóa Teacher không được âm thầm làm mất lịch sử chấm/điểm danh.
3. Cách xử lý lớp/lịch tương lai khi Teacher bị vô hiệu hóa: [CẦN QUYẾT ĐỊNH].

## 8. Kết quả cuối cùng
Hồ sơ và phân công Teacher được cập nhật; các quyền truy cập phù hợp với phân công.

## 9. Business Rules
1. Teacher là role riêng, có thể cùng user mang role Admin.
2. Phân công lớp có hiệu lực đối với danh sách, buổi học và hoạt động liên quan.
3. Có bắt buộc chỉ định Teacher chính hay không: [CẦN QUYẾT ĐỊNH].
