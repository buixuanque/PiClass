# Review ui-ux-pro-max: mockup Học sinh

Trạng thái tổng: 18/19 đã xử lý (Đã sửa 17, Bỏ qua 1, Chờ quyết 1: HS-09)

## Phạm vi và cách làm
- File: `piclass-mockup/design-system/PiClass Học sinh.dc.html` ở `main` (đã có nhóm 1 và nhóm 2), design system v2.
- Lúc review chỉ đọc, chưa sửa file nào (việc sửa ghi ở mục "Đã áp dụng" cuối file). Chụp ảnh và đo tràn ngang bằng Chrome headless ở 375, 900 và 1300px cho 7 mục menu; xem ảnh danh sách bài kiểm tra, chi tiết bài, màn làm bài (1100px và 375px), Tiến độ; đọc mã nguồn các nhánh làm bài.
- Giá trị cho cột Trạng thái: `Chưa sửa`, `Đã sửa`, `Bỏ qua (lý do)`, `Chờ quyết`, `Không còn đúng`.

## Mức cao
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| HS-01 | A11y | Màn làm bài, câu chọn một và chọn nhiều | Mỗi đáp án là `<div onClick>`: không có role radio/checkbox, không focus được bằng phím, không có `aria-checked`. | Dùng `Radio`/`Checkbox` có sẵn hoặc `<button role="radio">` kèm `aria-checked`, bọc trong `role="radiogroup"` gắn nhãn là nội dung câu hỏi. | File vai trò | Đã sửa: đo lại thấy 4 `role=radio` trong `radiogroup` có nhãn, focus được, `aria-checked` đổi đúng sau khi bấm. Chưa có phím mũi tên trong nhóm, mỗi đáp án vào bằng Tab. |
| HS-02 | A11y | Menu rail (dưới 1100px), cả 4 vai trò | Nút menu dạng icon không có `aria-label`, chữ bị `display:none`, nên không có tên truy cập. Đã đo: 7 nút đầu không có attr và không có chữ hiển thị. Lượt review Admin trước đã bỏ sót lỗi này. | Thêm `aria-label`, `title`, `aria-current="page"` ở `NavRow` trong `_ds_bundle.js`. | Design system | Đã sửa: 7/7 nút menu rail có `aria-label`, mục đang chọn có `aria-current`; đo cả Quản trị, Giáo viên, Phụ huynh (đủ tên). |
| HS-03 | A11y | Danh sách Bài tập, Bài kiểm tra, Kết quả, Khóa học | Các hàng bấm được là `<div onClick>` (tổng 10 chỗ gồm hàng danh sách, đáp án và nền mờ của menu): không focus, không Enter. | Chuyển sang `<button>` hoặc `role="link"` kèm `tabindex="0"` và xử lý Enter. | File vai trò | Đã sửa: 8 hàng có `role=button` + `tabindex=0`; focus được và Enter mở được chi tiết (đo bằng sự kiện phím). |
| HS-04 | A11y | Câu điền, tự luận, câu ghép (5 ô Input/Select) | Không có nhãn, chỉ có placeholder. Câu ghép có 3 ô Select giống hệt nhau, người đọc màn hình không biết ô nào ghép với mục nào. | Thêm `aria-label`, ví dụ "Câu trả lời câu 1" và "Ghép với {{p.left}}". | File vai trò | Đã sửa: đi hết 6 câu, không còn ô nhập hay Select thiếu nhãn (ô ghép cặp: "Ghép với …"). |
| HS-05 | A11y | Đồng hồ đếm ngược | Không có `role="timer"` hay vùng `aria-live`. Cảnh báo mốc (còn 5 phút, còn 1 phút, mất mạng) chỉ hiện bằng chữ và màu. | Thêm vùng `role="status"` riêng cho cảnh báo mốc. Không đặt live cho đồng hồ chạy từng giây. | File vai trò | Đã sửa: `role=timer` có nhãn; bấm "Còn 5 phút" thì cảnh báo xuất hiện trong `role=alert`. |

## Mức trung bình
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| HS-06 | Responsive | Thanh trên cùng ở 375px | Ô tìm kiếm bị ép thành nút nhỏ "Tìm". Lỗi giống Admin đã được sửa riêng trong v2 (chưa kiểm tra Giáo viên và Phụ huynh). | Ẩn thanh đệm `flex:1` và cho ô tìm kiếm chiếm chỗ trống; cân nhắc gộp thành một quy tắc dùng chung. | Design system (hoặc file vai trò) | Đã sửa: ô tìm kiếm ở 375px rộng 191px (trước đó là nút nhỏ). Chỉ sửa trong file Học sinh; Giáo viên và Phụ huynh chưa kiểm tra. |
| HS-07 | Responsive/UX | Hàng lọc "Chưa làm / Chờ chấm / Đã có điểm / Đã đóng/hủy" ở 375px | Bị cắt ở mép phải ("Đã co…"), cuộn ngang được nhưng không có dấu hiệu. Trang không tràn (`scrollW=375`). | Thêm hiệu ứng mờ dần ở mép phải, hoặc cho hàng xuống dòng. | Design system (`base.css`) hoặc file vai trò | Đã sửa: `mask` mờ dần + `padding-right:28px` ở ≤760px, `scrollWidth > clientWidth`; xem ảnh 375px thấy "Lỗi" và "Đã c…" mờ dần. |
| HS-08 | Token/Nội dung | "CẦN LÀM", "ĐÃ XONG" ở Bài tập, Bài kiểm tra | `text-transform:uppercase`, trái quy tắc sentence case của design system. | Bỏ `uppercase`, giữ độ đậm và màu. | File vai trò | Đã sửa: `audit_static` báo 0 chỗ `uppercase`; ảnh cho thấy "Cần làm", "Đã xong" viết thường. |
| HS-09 | State/UX | Màn làm bài | Giữ nguyên sidebar, ô tìm kiếm, chuông: có thể bấm nhầm rời bài khi đồng hồ chạy. Có "Thoát, làm tiếp sau". Có thể là chủ ý. | Ẩn điều hướng chung trong chế độ làm bài, chỉ giữ tên bài, đồng hồ, danh sách câu. | File vai trò | Chờ quyết (có thể là chủ ý; chưa thay đổi). |
| HS-10 | A11y | Biểu đồ cột ở Tiến độ | Cột `--blue-400` đạt 3.0:1 trên trắng (vừa chạm ngưỡng). Cột dưới 7 điểm màu `--orange-500` chỉ 2.36:1 (ngưỡng 3:1). Giá trị có ghi số nên thông tin không chỉ phụ thuộc màu. | `--blue-500` cho cột thường, `--orange-700` cho cột dưới 7. | File vai trò | Đã sửa: `--blue-500` 4.42:1 và `--orange-700` 7.47:1 trên nền trắng, đều ≥3:1 (`contrast.py`); đổi cả `pc-data.js` nên Phụ huynh cũng nhận. |
| HS-11 | A11y | `ProgressBar`, `ProgressRing` (component) | Không có `role="progressbar"` hay `aria-valuenow`. Giá trị % có hiển thị bằng chữ bên cạnh. | Thêm role và aria-value vào component. | Design system | Đã sửa: `ProgressRing` ở Tổng quan có `role=progressbar` 3/5 "Mục tiêu hôm nay"; thanh theo môn ở Tiến độ là `div` tự vẽ nên đặt `aria-hidden` (giá trị đã có chữ bên cạnh). |

## Mức thấp
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| HS-12 | State | Hàng "Đã đóng, chưa nộp" | Làm mờ kèm chữ đỏ; contrast thực tế thấp hơn 4.5 nhưng chưa đo chính xác. | Làm mờ bằng màu thay vì `opacity`. | File vai trò | Đã sửa: đo trước đó chữ phụ ở `opacity:0.75` chỉ 3.27:1; giờ `opacity=1`, nền `--surface-muted`, chữ phụ 5.18:1. |
| HS-13 | Token | 1 chỗ `font-size:11px` | Dưới mức tối thiểu 12px đã chốt ở v2. | Nâng lên 12px. | File vai trò | Đã sửa: `audit_static` báo 0 chữ <12px; `text-subtle` làm chữ còn 0. |
| HS-14 | Nội dung | Số thập phân | Mockup dùng dấu phẩy ("8,5/10", "81,3%"), `readme.md` của design system ghi dấu chấm ("8.5/10"). Mâu thuẫn tài liệu, không phải lỗi mockup; dấu phẩy là chuẩn tiếng Việt. | Sửa `readme.md` theo dấu phẩy. | Design system | Đã sửa: `readme.md` ghi dấu phẩy. |
| HS-15 | Mockup | "Xem thử trạng thái", "Giả lập cho bản mẫu" | Là công cụ trình diễn (Admin cũng có). | Đánh dấu rõ hoặc ẩn khi bàn giao cho FE. | File vai trò | Bỏ qua (giao diện đã gắn nhãn rõ "Xem thử trạng thái", "Giả lập cho bản mẫu"; xử lý khi bàn giao cho FE). |

## Phát hiện thêm ở bước 3 (frontend-design, so với Quản trị và Giáo viên)
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| HS-16 | Phân cấp thông tin | Kết quả, biểu đồ "Điểm các bài gần đây" | Cả bốn cột cao đúng 116px dù điểm 8,5 và 7,0: chiều cao `%` bị flex ép co, biểu đồ nói sai dữ liệu. | Chiều cao tính bằng px, cột `flex:none`. | File vai trò | Đã sửa: đo lại 102, 84, 102, 102px (đúng tỉ lệ), xem ảnh. |
| HS-17 | Tiếng Việt | Kết quả và Tiến độ, nhãn cột | Nhãn bị cắt giữa chữ ("Đề luyện s", "Unit 2 Tes") do `slice(0,10)`. | Bỏ cắt cứng, rút gọn bằng dấu … và `title`. | File vai trò + `pc-data.js` | Đã sửa: ảnh hiện đủ "Đề luyện số 4: Hình học"; Phụ huynh nhận cùng sửa. |
| HS-18 | Nhất quán | Kết quả | Hai thẻ dính nhau (0px) trong khi chỗ khác cách 24px: `style="margin-bottom"` trên `x-import Card` không có tác dụng. | Bọc thẻ trong `div` có margin. | File vai trò | Đã sửa: đo lại 24px. |
| HS-19 | Tiếng Việt | Màn Đã nộp, danh sách lượt làm | Trạng thái "Chờ giáo viên chấm", trong khi từ vựng cố định là "Chờ chấm". | Đổi thành "Chờ chấm". | File vai trò | Đã sửa: màn Đã nộp hiện "Chờ chấm". |

Đã xem ảnh và thấy nhất quán với mẫu: Tổng quan, Lịch học, Bài tập, Bài kiểm tra, Khóa học, chi tiết bài, làm bài, hộp thoại nộp bài, Đã nộp, chi tiết kết quả.

## Điểm đạt (đã xác minh)
- Không có màu hex rời (chỉ trong thumbnail), toàn bộ màu dùng token v2.
- Trang không tràn ngang ở cả 21 tổ hợp (7 màn × 3 độ rộng).
- Có đủ trạng thái đang tải, lỗi, không có quyền, rỗng (qua thanh "Xem thử trạng thái"), cùng Dialog và Toast.
- Luồng làm bài hiển thị tốt ở 1100px và 375px.
- `IconButton` có `aria-label` (khác với menu rail ở HS-02).

## Chưa kiểm tra
- Chưa xem ảnh: Tổng quan, Lịch học, Khóa học, Đã nộp, Xem lại bài (chỉ đo tràn ngang).
- Chưa đo chính xác contrast của hàng làm mờ bằng `opacity` (HS-12).
- Chưa kiểm tra bàn phím thật (Tab, Enter) mà chỉ dựa vào mã nguồn và cấu trúc DOM.

## Đã áp dụng
Ngày 2026-10-23, repo `piclass-mockup` branch `fix/student-review`, commit `e04199c`, đã merge vào `main` bằng PR #6 (commit merge `063979e`). Cách xác minh ghi ở cột Trạng thái của từng mã: đo bằng `diag.sh` (JS trong trang), `contrast.py`, `audit_static.py`, và xem ảnh chụp sau khi sửa. Chi tiết thay đổi: `piclass-mockup/design-system/CHANGES.md` mục "Sửa theo review Học sinh".

**Chưa kiểm tra sau khi sửa:** bàn phím thật (Tab, mũi tên) ngoài phép thử bằng sự kiện; Giáo viên và Phụ huynh chưa kiểm tra lỗi giống HS-06; các màn Xem lại bài làm và Khóa học chi tiết chưa chụp lại.
