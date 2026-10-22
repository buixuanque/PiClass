# Prompt nền (dán một lần ở đầu phiên Claude Design)

Đính kèm design system và các mockup mẫu sẵn có cùng với prompt này. Các prompt từng màn hình trong thư mục này không lặp lại phần dưới đây.

```text
Bạn đang thiết kế giao diện cho PiClass, một hệ thống quản lý lớp học thêm ở Việt Nam. Toàn bộ giao diện bằng tiếng Việt.

Có 4 nhóm người dùng, mỗi nhóm có cổng riêng:
- Quản trị viên (Admin): thiết lập người dùng, lớp, môn học, lịch; làm một lần rồi ít dùng hằng ngày.
- Giáo viên: dạy học hằng ngày, gồm điểm danh, giao bài, chấm bài, theo dõi học sinh. Một người có thể vừa là quản trị viên vừa là giáo viên.
- Học sinh: xem lịch học, làm bài, xem kết quả. Dùng cả điện thoại và web máy tính.
- Phụ huynh: theo dõi con (một phụ huynh có thể có nhiều con), xem lịch, điểm danh, kết quả, gửi đơn xin phép cho con nghỉ. Dùng cả điện thoại và web máy tính.

Vòng việc hằng ngày của một lớp học thêm: chuẩn bị buổi học, điểm danh, giao bài, học sinh làm bài, giáo viên chấm bài, phụ huynh xem kết quả.

Quy ước cho mọi màn hình:
- Dùng đúng design system và các mockup mẫu đã đính kèm. Không tự tạo kiểu mới khi design system đã có.
- Bố cục do bạn quyết định, các yêu cầu bên dưới chỉ nói thông tin cần có, hành động và quy tắc.
- Các màn hình thuộc cùng một chức năng hoặc cùng một luồng sử dụng phải nối với nhau (có lối đi qua lại, mang theo ngữ cảnh đang xem như lớp, học sinh, bài, con); nếu tôi gửi nhiều prompt cho cùng một chức năng thì hãy xem chúng là các phần của một chức năng.
- Mỗi màn hình phải có các trạng thái: đang tải, rỗng (kèm gợi ý hành động tiếp theo), lỗi (có cách thử lại), không có quyền xem, hết phiên đăng nhập.
- Giao diện responsive cho cả bốn nhóm: dùng tốt trên điện thoại, máy tính bảng và web máy tính, không mất chức năng nào khi đổi khổ màn hình. Học sinh và phụ huynh dùng cả điện thoại lẫn web; giáo viên và quản trị viên chủ yếu dùng web máy tính, riêng điểm danh và chấm bài phải dùng tốt trên máy tính bảng. Phải dùng được cả bằng chạm lẫn bằng chuột và bàn phím.
- Thời gian theo giờ Việt Nam (ví dụ 17:30, thứ Ba 21/10/2026). Điểm có thể là thang 10 hoặc phần trăm.
- Dùng dữ liệu mẫu tiếng Việt thực tế (tên người, lớp "Toán 9A", môn Toán, Ngữ văn, Tiếng Anh).
- Thông điệp lỗi và cảnh báo viết thân thiện, ngắn gọn, nói rõ người dùng cần làm gì tiếp.
```
