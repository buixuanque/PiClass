# Review ui-ux-pro-max: mockup Giáo viên

Trạng thái tổng: 0/15 đã xử lý (Chưa sửa 14, Chờ quyết 1: GV-07)

## Phạm vi và cách làm
- File: `piclass-mockup/design-system/PiClass Giáo viên.dc.html` (bản `standalone/`) ở `main` commit `063979e`, design system v2. Gồm nhóm 0, 3, 4 (vận hành lớp, soạn nội dung); chưa có nhóm 5 chấm bài.
- Lúc review chỉ đọc, chưa sửa file nào. Dùng Chrome headless (puppeteer-core) ở 1300px và 375px:
  - 9 mục menu, rồi các màn con: chi tiết lớp và 5 tab, điểm danh một buổi, wizard Tạo bài (bước 1 và 2), soạn câu hỏi, chi tiết đơn xin phép, chi tiết khóa học, tiến độ một học sinh, hộp thoại Hủy bài.
  - Đo: tràn ngang, phần tử bấm được không focus được, nút icon không tên, ô nhập không nhãn, chữ dưới 12px, chữ in hoa, contrast chữ (ước tính từ màu nền gần nhất), vùng chạm.
  - Xem ảnh các màn Tổng quan, Lịch dạy, Điểm danh, Tiến độ, Đơn xin phép, Tạo bài (1300 và 375px), Soạn câu hỏi, Chi tiết lớp (375px).
- Ghi chú về đo contrast: ngoại lệ đã biết (nút primary md/lg 4.42:1, nút `danger` 3.39:1) không tính là lỗi mới. Số liệu contrast ở đây lấy từ màu tính toán, chưa chạy `contrast.py` chính thức.
- Giá trị cho cột Trạng thái: `Chưa sửa`, `Đã sửa`, `Bỏ qua (lý do)`, `Chờ quyết`, `Không còn đúng`.

## Mức cao
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| GV-01 | A11y | Thẻ lớp ở Tổng quan (4), danh sách Lớp học (4), thẻ buổi ở Lịch dạy (10), danh sách buổi ở Điểm danh (5), hàng câu hỏi ở Ngân hàng (8 trở lên), hàng học sinh ở Tiến độ (12 trở lên, lớp có 18 em), hàng đơn ở Đơn xin phép (3), thẻ ở Khóa học (3) | Ít nhất 49 phần tử bấm được là `<div onClick>`: không có `role`, không `tabindex`, không focus được bằng phím, Enter không mở. Cùng lỗi HS-03 của Học sinh. | `role="button"` + `tabindex="0"` + xử lý Enter/Space bằng trình nghe chung như Học sinh đã làm, hoặc đổi sang `<button>`. | File vai trò | Chưa sửa |
| GV-02 | A11y | Điểm danh, 4 nút Có mặt / Đi muộn / Vắng / Có phép cho mỗi học sinh (56 nút ở lớp 14 em) | Không có `aria-pressed` hoặc `role="radio"`, không nằm trong nhóm, nhãn trùng nhau (14 nút "Có mặt" giống hệt). Người đọc màn hình không biết nút nào của em nào, nút nào đang chọn. Chưa kiểm tra trạng thái chọn có ngoài màu hay không. | Mỗi hàng là `role="radiogroup"` gắn nhãn tên học sinh, nút là `role="radio"` + `aria-checked`, hoặc `aria-label="Có mặt, Cao Quốc Bảo"` + `aria-pressed`. | File vai trò | Chưa sửa |
| GV-03 | A11y | 8 công tắc: "Bài tôi tạo" (Bài đánh giá), "Của tôi" (Ngân hàng), 5 công tắc ở bước 1 wizard (Trộn thứ tự câu hỏi, Trộn thứ tự đáp án, Hiện đáp án đúng, Hiện lời giải, Hiện điểm từng câu), 1 ở chi tiết khóa học | `role="switch"` không có `aria-label`/`aria-labelledby`; chữ bên cạnh là phần tử anh em, không nối với công tắc. Công tắc cao 24px, rộng 40px, dưới mức chạm 44px trên điện thoại. | Thêm `aria-label` hoặc bọc trong `<label>`; sửa ở component `Switch` trong `_ds_bundle.js` để cả 4 vai trò nhận. Nâng vùng chạm trên màn cảm ứng. | Design system | Chưa sửa |

## Mức trung bình
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| GV-04 | Responsive | Thanh trên cùng ở 375px, mọi màn | Ô tìm kiếm bị ép thành nút nhỏ chỉ thấy "Tin" (cắt chữ placeholder). Cùng lỗi HS-06; báo cáo Học sinh ghi "Giáo viên chưa kiểm tra", nay xác nhận là có. | Ẩn thanh đệm `flex:1`, cho ô tìm kiếm chiếm chỗ trống. Nên gộp thành một quy tắc dùng chung cho cả 4 file thay vì sửa từng file. | Design system hoặc file vai trò | Chưa sửa |
| GV-05 | Nội dung | Điểm danh ở 375px | Dòng mô tả vẫn ghi "Chọn buổi học ở bên trái", trong khi điện thoại chỉ có một cột (không có bên trái). | Đổi câu thành không phụ thuộc vị trí ("Chọn một buổi học để điểm danh") hoặc ẩn dòng ở ≤900px. | File vai trò | Chưa sửa |
| GV-06 | Nội dung | Lớp học (cột Điểm TB), Tiến độ (danh sách và thẻ "8.1/10") | Số thập phân dùng dấu chấm (7.8, 8.1, 8.9, 6.6...), trong khi HS-14 đã chốt dấu phẩy như chuẩn tiếng Việt. Ngay cùng màn Tiến độ có "78,8%" (phẩy) và "8.1/10" (chấm). | Đổi sang dấu phẩy ở toàn file Giáo viên và dữ liệu mẫu liên quan. | File vai trò | Chưa sửa |
| GV-07 | Nghiệp vụ/UX | Tiến độ học sinh, hàng số liệu đầu | Một màn có hai "điểm trung bình": "Điểm trung bình (đã công bố) 78,8%" và "Điểm trung bình 8.1/10" (cùng con số 8.1 ở danh sách bên trái). Giáo viên không biết số nào là điểm của em; 8.1/10 tương ứng 81%, không bằng 78,8%. | Cần chốt: chỉ hiện một điểm trung bình, hoặc đặt tên khác nhau (ví dụ "Điểm TB đã công bố" và "Điểm TB theo môn"). Theo `student-parent.md` và `teacher-van-hanh.md` xem lại công thức. | Cả hai | Chờ quyết |
| GV-08 | A11y | Chữ trên nền màu nhạt | Đo: chữ cái đầu ở avatar nền cam 3.45:1, nền xanh lá 3.82:1, nền coral 4.40:1 (Điểm danh, Tiến độ). Số đếm trên nền xanh nhạt ở tab "Chờ duyệt 3" đạt 3.63:1, số "1" trên nền xám 4.44:1. Chữ phụ trên nền hàng đang chọn (`--blue-100`) 4.49:1. "Trống" ở Lịch dạy 4.18:1. Chấm đỏ đếm "6" và "3" trên menu (trắng trên coral) 3.39:1, cùng gốc với ngoại lệ nút `danger`. | Dùng cặp `--badge-<tone>-fg` đã có trong token cho avatar và badge đếm; nâng chữ phụ trên nền chọn lên `--text-body` hoặc đổi nền chọn. | Design system và file vai trò | Chưa sửa |
| GV-09 | State | Lịch dạy, cột ngày đã qua (T2, T3) | Làm mờ bằng `opacity:0.6` cả thẻ, gồm giờ, tên lớp và địa điểm. Cùng lỗi HS-12. Chưa đo chính xác nhưng chữ phụ vốn chỉ 5.18:1 nên sau khi mờ chắc chắn dưới 4.5:1. | Dùng nền phẳng `--surface-muted` và giữ chữ ở màu thường thay cho `opacity`. | File vai trò | Chưa sửa |
| GV-10 | A11y/State | Tiến độ, chấm cam cạnh tên 6 học sinh (Trịnh Ngọc Diệp, Trần Bảo Ngọc, Bùi Phương Thảo, Đỗ Khánh Linh...) | Chấm màu không có chú thích, không có chữ hay `aria-label`: không ai biết nghĩa là gì (cần chú ý? chờ chấm?), và chỉ phân biệt bằng màu. | Thêm chú thích (legend) và `title`/`aria-label`, hoặc đổi thành badge có chữ. | File vai trò | Chưa sửa |
| GV-11 | Token/Nội dung | Chi tiết lớp, tab Buổi học, tiêu đề nhóm "SẮP TỚI", "ĐÃ DIỄN RA" (đo ở 375px) | `text-transform:uppercase`, trái quy tắc sentence case của design system (cùng HS-08). | Bỏ `uppercase`, giữ độ đậm và màu. | File vai trò | Chưa sửa |

## Mức thấp
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| GV-12 | A11y | Tab ở chi tiết lớp, Bài đánh giá, Đơn xin phép | Có `role="tablist"` và `role="tab"` (tốt) nhưng chưa có `role="tabpanel"` nào, tab không điều khiển vùng nội dung. | Thêm `role="tabpanel"` và `aria-controls` ở component `Tabs`. | Design system | Chưa sửa |
| GV-13 | Responsive | Wizard Tạo bài ở 375px | Thanh bước chia thành 3 dòng, chiếm gần nửa màn hình trước khi tới biểu mẫu. | Thu gọn thành dòng "Bước 2/4 · Câu hỏi" kèm thanh tiến độ ở ≤760px. | File vai trò | Chưa sửa |
| GV-14 | Nội dung | Ô "Tên bài" ở wizard, 375px | Gợi ý bị cắt giữa chữ ("Ví dụ: Kiểm tra 15 phút – Đại số chương 2" mất "2"). Không ảnh hưởng nhập liệu. | Rút ngắn gợi ý hoặc cho xuống dòng. | File vai trò | Chưa sửa |
| GV-15 | Mockup | "Xem thử trạng thái", "Phương án màn hình tổng quan (A/B/C)", tiêu đề "Điểm các bài gần đây (bản mẫu, chờ API lịch sử điểm G8)" | Công cụ trình diễn và ghi chú nội bộ nằm lẫn giao diện. Ở 375px thanh "Xem thử trạng thái" chiếm khoảng 100px đầu mỗi trang, đẩy tiêu đề xuống. Tổng quan có hai khối số liệu trùng nhau (thanh trên "4 lớp, 6 chờ chấm" và các thẻ). | Xử lý khi bàn giao cho FE (như HS-15); trước đó nên thu gọn thanh thử trạng thái ở điện thoại. | File vai trò | Chưa sửa |

## Điểm đạt (đã xác minh)
- Không tràn ngang: 9 màn menu và 11 màn con, ở cả 1300px và 375px (`scrollWidth` = `clientWidth`).
- Không có chữ dưới 12px, không có ô nhập hoặc Select thiếu nhãn, không có ảnh thiếu `alt`, mỗi màn đúng một `h1`, không có lỗi JS (`pageerror`).
- Nút menu rail có tên truy cập (sửa HS-02 ở design system đã có hiệu lực cho Giáo viên). Nút mở menu ở 375px có `aria-label="Mở menu"`.
- Hộp thoại (thử Hủy bài): `aria-modal`, tiêu đề được nối, focus vào hộp thoại, Esc đóng.
- Focus bàn phím có vòng viền 3px rõ ràng.
- Có đủ trạng thái đang tải, rỗng, lỗi, không có quyền qua thanh "Xem thử trạng thái".

## Chưa kiểm tra
- Wizard Tạo bài bước 3 (Đối tượng) và bước 4 (Xem trước và phát hành): cần thêm câu hỏi ở bước 2 mới đi tiếp được, chưa dựng kịch bản.
- Soạn câu hỏi: mới đo bố cục ở 375px, chưa đi qua đủ 9 loại câu; các hộp thoại khác (Đóng bài, Nhân bản, lô điểm danh bị từ chối, kết thúc ghi danh khóa học) và biểu mẫu từ chối đơn chưa mở.
- Ở 375px chưa xem phần chi tiết của một buổi điểm danh nằm ở đâu so với danh sách buổi (chỉ chụp phần đầu trang).
- Tab Buổi học ở 1300px (kịch bản bấm không bắt được tab này, số liệu chữ in hoa chỉ đo ở 375px).
- Chưa chạy các công cụ chính thức `diag.sh`, `contrast.py`, `audit_static.py` (không có trong repo), số liệu contrast là ước tính; chưa thử bàn phím thật qua nhiều màn; chưa thử khổ 390px trên thiết bị thật.
- Bước 2 (frontend-design: phân cấp thông tin, nhất quán so với Quản trị) mới ở mức xem ảnh, chưa review có hệ thống. Điểm đã thấy: ở Đơn xin phép, hành động "Đang xem xét" là chữ trần cạnh hai nút chính nên khó nhận ra là nút; mỗi hàng đơn trong tab "Chờ duyệt" đều có badge "Chờ duyệt" lặp lại.
