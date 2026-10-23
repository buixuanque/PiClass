# Prompt nền (dán một lần ở đầu phiên Claude Design)

Đính kèm design system và các mockup mẫu sẵn có (thư mục `design-system` trong repo `piclass-mockup`: token, bộ component, mockup Giáo viên, Học sinh, Phụ huynh) cùng với prompt này. Các prompt từng màn hình trong thư mục này không lặp lại phần dưới đây.

Hướng đã chốt: **PiClass V1 là web app responsive** (một giao diện web co giãn từ máy tính xuống điện thoại). Bản app điện thoại riêng sẽ thiết kế lại sau, nên không vẽ theo kiểu app (khung điện thoại, status bar, thanh tab cố định kiểu native).

```text
Bạn đang thiết kế giao diện web cho PiClass, một hệ thống quản lý lớp học thêm ở Việt Nam. Toàn bộ giao diện bằng tiếng Việt. Đây là web app responsive chạy trên trình duyệt (máy tính, máy tính bảng, điện thoại), không phải app điện thoại: không vẽ khung thiết bị, status bar hay thanh điều hướng kiểu native.

Có 4 nhóm người dùng, mỗi nhóm có cổng riêng:
- Quản trị viên (Admin): thiết lập người dùng, lớp, môn học, lịch; làm một lần rồi ít dùng hằng ngày. Chủ yếu dùng web máy tính.
- Giáo viên: dạy học hằng ngày, gồm điểm danh, giao bài, chấm bài, theo dõi học sinh. Một người có thể vừa là quản trị viên vừa là giáo viên. Chủ yếu dùng web máy tính; điểm danh và chấm bài phải dùng tốt trên máy tính bảng.
- Học sinh: xem lịch học, làm bài, xem kết quả. Dùng cả điện thoại và máy tính.
- Phụ huynh: theo dõi con (một phụ huynh có thể có nhiều con), xem lịch, điểm danh, kết quả, gửi đơn xin phép cho con nghỉ. Dùng cả điện thoại và máy tính.

Vòng việc hằng ngày của một lớp học thêm: chuẩn bị buổi học, điểm danh, giao bài, học sinh làm bài, giáo viên chấm bài, phụ huynh xem kết quả.

Design system và mockup mẫu là chuẩn bắt buộc:
- Dùng đúng token, component và cách trình bày của các mockup mẫu (nền xanh nhạt, thẻ trắng không viền đổ bóng xanh, nút và tag dạng pill, icon Lucide, font Be Vietnam Pro). Không tự tạo kiểu mới khi design system đã có; không dùng màu hex thô, chỉ dùng token.
- Khung ở khổ máy tính giữ như mockup: thanh bên trái cố định (232px), thanh trên (72px) có ô tìm kiếm, chỉ báo thông báo và thông tin người dùng, vùng nội dung chính, cột phụ bên phải khi cần. Các màn mới phải trông như cùng một sản phẩm với mockup; mockup đã vẽ màn nào thì giữ bố cục màn đó, chỉ bổ sung phần còn thiếu.
- Quy tắc co giãn (dùng thống nhất cho mọi màn và mọi vai trò):
  - Máy tính (từ 1180px): thanh bên mở rộng như mockup.
  - Máy tính bảng: thanh bên thu gọn thành cột icon; bảng dữ liệu giữ dạng bảng, cột phụ chuyển xuống dưới nội dung chính.
  - Điện thoại: thanh bên thành ngăn kéo mở từ nút menu ở thanh trên; bảng dữ liệu chuyển thành danh sách thẻ; cột phụ xếp dọc; nút hành động chính cố định ở cuối màn khi form dài. Riêng học sinh và phụ huynh được thêm thanh điều hướng dưới với tối đa 4 mục thường dùng và mục "Thêm", nhưng đó vẫn là giao diện web.
  - Không mất chức năng nào khi đổi khổ màn hình. Phải dùng được bằng chạm lẫn bằng chuột và bàn phím; vùng bấm tối thiểu 40px trên màn cảm ứng.
- Các màn hình thuộc cùng một chức năng hoặc cùng một luồng sử dụng phải nối với nhau (có lối đi qua lại, mang theo ngữ cảnh đang xem như lớp, học sinh, bài, con); nếu tôi gửi nhiều prompt cho cùng một chức năng thì hãy xem chúng là các phần của một chức năng.
- Mỗi màn hình phải có các trạng thái: đang tải, rỗng (kèm gợi ý hành động tiếp theo), lỗi (có cách thử lại), không có quyền xem, hết phiên đăng nhập.
- Các prompt chỉ nói thông tin cần có, hành động và quy tắc. Bố cục chi tiết bên trong vùng nội dung do bạn quyết định, nhưng phải bám khung và kiểu trình bày của mockup.

Quy tắc nội dung (theo design system):
- Tiếng Việt đủ dấu. Chữ hoa đầu câu (sentence case) cho nút, tiêu đề, tab; không viết hoa toàn bộ. Nút là động từ ("Điểm danh", "Giao bài", "Lưu").
- Xưng hô theo vai trò: giáo viên là "cô/thầy" ("Chào buổi sáng, cô Lan"), học sinh là "em", phụ huynh là "anh/chị", quản trị viên dùng giọng trung tính, vận hành. Hệ thống không tự xưng "tôi". Giọng ấm, ngắn gọn; chỉ tối đa một dấu chấm than mỗi màn.
- Từ vựng trạng thái cố định: Có mặt / Đi muộn / Vắng / Có phép; Chưa nộp / Chờ chấm / Đã chấm / Nộp muộn / Quá hạn; Sắp tới / Đang học. Dùng màu điểm danh riêng của design system cho bốn trạng thái điểm danh.
- Ngày dạng 14/10 (đầy đủ khi cần: thứ Ba 21/10/2026), giờ 24 giờ (17:30), theo giờ Việt Nam. Dấu "·" ngăn các thông tin phụ ("Lớp 9A · Toán · P.204"). Phân số cho tiến độ ("24/32 đã nộp").
- Điểm hiển thị dấu chấm thập phân, mặc định thang 10 (8.5/10); bài dùng thang phần trăm thì hiện phần trăm.
- Không dùng emoji; dùng icon Lucide. Không có logo: đặt tên "PiClass" bằng chữ, không tự vẽ logo.
- Dùng dữ liệu mẫu tiếng Việt thực tế (tên người, lớp "Toán 9A", môn Toán, Ngữ văn, Tiếng Anh).
- Thông điệp lỗi và cảnh báo viết thân thiện, ngắn gọn, nói rõ người dùng cần làm gì tiếp.
```
