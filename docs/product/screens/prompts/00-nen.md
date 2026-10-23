# Prompt nền (dán một lần ở đầu phiên Claude Design)

Design system và mockup mẫu (thư mục `design-system` trong repo `piclass-mockup`) đã được tải lên project Claude Design, nên prompt nền chỉ giữ những gì design system **không** có: bối cảnh sản phẩm, hướng web responsive, trạng thái bắt buộc, cách nối màn. Token, component, quy tắc nội dung (xưng hô, từ vựng trạng thái, ngày giờ, không emoji, không logo) đã nằm trong design system, không lặp lại ở đây.

Hướng đã chốt: **PiClass V1 là web app responsive**. Bản app điện thoại riêng sẽ thiết kế lại sau.

**UI của mockup hiện tại là base đã chốt.** Mọi màn là sửa hoặc bổ sung trên base đó, không thiết kế lại từ đầu. Điều hướng từng vai trò theo mockup; prompt từng màn trong thư mục này chỉ dùng để lấy phần **thiếu** (thông tin, quy tắc, trạng thái, tình huống đặc biệt) cho màn đã có, hoặc làm màn mới theo đúng kiểu mockup.

```text
Bạn đang thiết kế giao diện web cho PiClass, hệ thống quản lý lớp học thêm ở Việt Nam, bằng tiếng Việt. Đây là web app responsive chạy trên trình duyệt (máy tính, máy tính bảng, điện thoại), không phải app điện thoại: không vẽ khung thiết bị, status bar hay thanh điều hướng kiểu native. Phần "mobile app" trong readme của design system không áp dụng cho bản này.

Bốn nhóm người dùng, mỗi nhóm một cổng riêng:
- Quản trị viên (Admin): thiết lập người dùng, lớp, môn, lịch; làm một lần rồi ít dùng hằng ngày. Chủ yếu dùng máy tính.
- Giáo viên: điểm danh, giao bài, chấm bài, theo dõi học sinh; có thể đồng thời là quản trị viên. Chủ yếu dùng máy tính; điểm danh và chấm bài phải dùng tốt trên máy tính bảng.
- Học sinh: xem lịch, làm bài, xem kết quả. Dùng cả điện thoại và máy tính.
- Phụ huynh: theo dõi con (có thể nhiều con), xem lịch, điểm danh, kết quả, gửi đơn xin phép. Dùng cả điện thoại và máy tính.

Vòng việc hằng ngày: chuẩn bị buổi học, điểm danh, giao bài, học sinh làm bài, giáo viên chấm bài, phụ huynh xem kết quả.

Quy ước cho mọi màn:
- Mockup hiện tại là UI đã chốt: giữ nguyên bố cục, thứ tự và tên mục điều hướng, kiểu thẻ và bảng của các màn đã vẽ; chỉ bổ sung hoặc sửa theo yêu cầu. Màn chưa có trong mockup thì thêm mục điều hướng mới theo cùng kiểu. Bám design system và mockup mẫu: giữ khung (sidebar, top bar, thẻ) và kiểu trình bày của mockup; màn nào mockup đã vẽ thì giữ bố cục đó, chỉ bổ sung phần còn thiếu. Không tự tạo kiểu mới khi design system đã có. Bố cục chi tiết bên trong vùng nội dung do bạn quyết định.
- Quy tắc co giãn thống nhất cho mọi màn và mọi vai trò:
  - Máy tính (từ 1180px): sidebar mở rộng như mockup.
  - Máy tính bảng: sidebar thu gọn thành cột icon; bảng dữ liệu giữ dạng bảng; cột phụ chuyển xuống dưới nội dung chính.
  - Điện thoại: sidebar thành ngăn kéo mở từ nút menu ở top bar; bảng thành danh sách thẻ; cột phụ xếp dọc; nút hành động chính cố định ở cuối màn khi form dài. Học sinh và phụ huynh được thêm thanh điều hướng dưới (tối đa 4 mục thường dùng và "Thêm"), vẫn là giao diện web.
  - Không mất chức năng nào khi đổi khổ. Dùng được bằng chạm, chuột và bàn phím; vùng bấm tối thiểu 40px trên màn cảm ứng.
- Các màn thuộc cùng một chức năng hoặc luồng phải nối với nhau (lối đi qua lại, mang theo ngữ cảnh đang xem: lớp, học sinh, bài, con). Nếu tôi gửi nhiều prompt cho cùng một chức năng, hãy xem chúng là các phần của một chức năng.
- Mỗi màn có đủ trạng thái: đang tải, rỗng (kèm gợi ý hành động tiếp theo), lỗi (có cách thử lại), không có quyền xem, hết phiên đăng nhập.
- Điểm mặc định thang 10 (8.5/10); bài dùng thang phần trăm thì hiện phần trăm.
- Dữ liệu mẫu tiếng Việt thực tế (lớp "Toán 9A", môn Toán, Ngữ văn, Tiếng Anh). Thông điệp lỗi và cảnh báo thân thiện, ngắn, nói rõ bước tiếp theo.
```
