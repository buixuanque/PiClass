# Review ui-ux-pro-max: mockup Phụ huynh

Trạng thái tổng: 12/15 đã xử lý (Đã sửa 12; Chờ quyết 3: PH-08, PH-14, PH-15)

## Phạm vi và cách làm
- File: `piclass-mockup/design-system/PiClass Phụ huynh.dc.html` (bản `standalone/`) ở `main` commit `9686b60` (đã gồm các sửa design system của review Học sinh và Giáo viên), design system v2. Gồm màn Chọn con và 7 mục menu: Tổng quan, Lịch học, Điểm danh, Bài tập & kết quả, Nhận xét, Tiến độ, Đơn xin phép (nhóm 0 và 2).
- Lúc review chỉ đọc, chưa sửa file nào (việc sửa ghi ở mục "Đã áp dụng" cuối file). Dùng Chrome headless (puppeteer-core viết trong scratchpad, thay cho `diag.sh`/`shot.sh`) ở **1300, 900, 500 và 375px**:
  - Chọn con và 7 mục menu ở cả 4 khổ; các màn con: chi tiết bài, kết quả, tiến độ lọc theo môn, hộp thoại Gửi đơn (cả lúc để trống lý do), chi tiết một nhận xét.
  - Đo: tràn ngang, phần tử bấm được không focus, nút icon không tên, ô nhập không nhãn, chữ <12px, chữ in hoa, contrast chữ (ước tính từ màu tính toán), `opacity`, vị trí và kích thước hộp thoại, thứ tự Tab, cây truy cập của Chrome (`accessibility.snapshot`).
  - Thanh "Xem thử trạng thái": bấm từng trạng thái ở mọi màn có thanh này.
  - Xem ảnh: Chọn con, Tổng quan, Lịch học, Điểm danh, Bài tập & kết quả, Nhận xét, Tiến độ, Đơn xin phép, hộp thoại ở 375px và 1300px.
  - `contrast.py` cho token; `audit_static.py` cho file `.dc.html`.
- Ngoại lệ đã chốt không tính là lỗi: nút primary md/lg 4.42:1.
- Đã biết, không báo lại: `Tabs` chưa có `role="tabpanel"` (GV-12); thanh "Xem thử trạng thái" của Học sinh và Phụ huynh vẫn là khung nét đứt, chưa đồng bộ theo mẫu Quản trị (CHANGES.md); công cụ trình diễn ("Giả lập cho bản mẫu", chú thích "Bản mẫu... G8") giữ nguyên vì là mockup (GV-15).
- Giá trị cho cột Trạng thái: `Chưa sửa`, `Đã sửa`, `Bỏ qua (lý do)`, `Chờ quyết`, `Không còn đúng`.

## Mức cao
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| PH-01 | Responsive | Hộp thoại "Gửi đơn xin phép" ở 375px | Hộp thoại rộng 452px, trái 20, **phải 472px** trong khung 375px: tiêu đề, nút "Đóng" và nút "Gửi đơn" bị cắt khỏi màn hình, điện thoại không gửi được đơn (ảnh `ph_dlg_375`). Trang không cuộn ngang vì lớp phủ cắt. Nguyên nhân: dòng "Giả lập cho bản mẫu" có thanh tab `nowrap` rộng, kéo cột lưới của `Dialog` dãn ra. Hộp thoại Hủy bài của Giáo viên vẫn vừa (20 đến 355px), nên lỗi do nội dung chứ không do `width` mặc định. | `Dialog`: đặt cột lưới `minmax(0,1fr)` để mọi nội dung co lại; bọc thanh giả lập trong vùng cuộn ngang. | Design system và file vai trò | Đã sửa: `Dialog` dùng cột lưới `minmax(0,1fr)`; đo lại hộp thoại "Gửi đơn" ở 375px là trái 20, phải 355 (trước 472), thấy nút Đóng và Gửi đơn (ảnh). Hộp thoại Hủy bài (Giáo viên) và Tạo lớp (Quản trị) vẫn 20 đến 355. |
| PH-02 | A11y/Responsive | Bộ chọn con ở thanh trên (mọi màn) | `<select>` không có `aria-label`; cây truy cập ghi `combobox ""` (không tên). Ở 375px rộng 58px, chỉ thấy chữ "Đ" và mũi tên: phụ huynh có 2 con không biết đang xem con nào. | Thêm `aria-label="Chọn con đang xem"`; ở ≤760px cho select chiếm chỗ trống hoặc hiện tên rút gọn (ví dụ "Minh Khuê"). | File vai trò (có thể cần `Select` nhận `aria-label`) | Đã sửa: select có `aria-label="Chọn con đang xem"` (bộ lọc môn: "Lọc theo môn"); ở 375px rộng 127px (trước 58px) và hiện tên "Đỗ Minh Khuê" (ảnh). |
| PH-03 | A11y | Màn Chọn con (2 thẻ), Bài tập & kết quả (11 hàng), Nhận xét (6 hàng), Tiến độ (3 hàng môn, bấm để lọc) | 22 phần tử bấm được là `<div onClick>`: không `role`, không `tabindex`, Enter không mở. Màn Chọn con là cửa vào của phụ huynh: không chọn con được bằng bàn phím. Thứ tự Tab hiện đi qua menu, bộ chọn con, nút Chọn con, chuông, các nút trạng thái, rồi bỏ qua mọi thẻ. Cùng lỗi HS-03 và GV-01. | `role="button"` + `tabindex="0"` + trình nghe Enter/Space dùng chung như Học sinh và Giáo viên (`audit_static.py` báo 4 chỗ trong mã). | File vai trò | Đã sửa: audit trên 32 màn (8 màn x 4 khổ) còn 0 phần tử bấm được thiếu role; Enter trên thẻ "Đỗ Bảo Vy" chọn đúng con (tiêu đề và bộ chọn con đổi theo). |
| PH-04 | A11y | Điểm danh, lưới "10 buổi gần nhất" | Mỗi ô chỉ là biểu tượng (dấu tích, đồng hồ, tài liệu) trên nền màu cùng ngày; ô không có `aria-label`, `title` hay chữ trạng thái, biểu tượng không `aria-hidden`. Người dùng đọc màn hình chỉ nghe ngày, không biết con có mặt hay vắng, đây là thông tin chính của màn. Phần chú thích bên dưới chỉ là chấm màu và chữ, không nối với ô. | Thêm `role="img"` + `aria-label="21/09: Có phép"` cho từng ô (hoặc chữ ẩn trực quan), `title` cho người dùng chuột. | File vai trò | Đã sửa: cây truy cập của Chrome hiện 10 ô `image` có tên "21/09: Có phép", "23/09: Có mặt"...; có `title` cho chuột. |

## Mức trung bình
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| PH-05 | State | Tổng quan, Lịch học, Điểm danh, Nhận xét | 4 trong 7 màn không có thanh "Xem thử trạng thái": không có trạng thái đang tải, rỗng, lỗi (có cách thử lại), không có quyền. Bài tập & kết quả, Tiến độ, Đơn xin phép đã có. Con mới liên kết chưa có buổi học hay nhận xét thì các màn này không có hình dung. | Thêm thanh trạng thái và các khối đang tải, rỗng, lỗi theo mẫu ba màn đã có. | File vai trò | Đã sửa: Tổng quan (Bình thường, Đang tải, Lỗi), Lịch học, Điểm danh và Nhận xét (thêm Rỗng) đều có thanh trạng thái; đã bấm từng trạng thái ở cả 7 màn. Tổng quan không có Rỗng vì là màn tổng hợp (khối con đã có trạng thái rỗng riêng). |
| PH-06 | State/A11y | Trạng thái "Lỗi" ở Bài tập & kết quả, Tiến độ, Đơn xin phép | Khối "Chưa tải được dữ liệu... Thử lại" không có `role="alert"` (đo: 0 ở cả 3 màn), người dùng đọc màn hình không được báo; màn lỗi mất `h1` nên không còn tiêu đề trang. Chưa đo `aria-busy` ở trạng thái đang tải. | Thêm `role="alert"` cho khối lỗi, giữ tiêu đề trang; `aria-busy` cho vùng đang tải. | File vai trò | Đã sửa: trạng thái Lỗi có `role=alert` ở cả 7 màn (đo, trước 0), Đang tải có `role=status` + `aria-busy`; cả hai giữ `h1` (ẩn bằng `.sr-only`); khối rỗng của Bài tập & kết quả cũng có `h1`. |
| PH-07 | Nội dung | Bài tập & kết quả, dòng mô tả "Đỗ Minh Khuê · Điểm trung bình 8.1/10" | Dấu chấm thập phân, trong khi các điểm cùng màn là `8,5/10` và HS-14 đã chốt dấu phẩy. Nguồn: biến `avgScore` ghép chuỗi trong `asgSummary` của file Phụ huynh. | Định dạng bằng dấu phẩy (`8,1/10`). | File vai trò | Đã sửa: dòng mô tả hiện "Điểm trung bình 8,1/10". |
| PH-08 | Nghiệp vụ/UX | Tiến độ: ô "Điểm trung bình 81,3 %" và điểm theo môn "77,5%", "85%"; Bài tập & kết quả: "8.1/10" | Cùng một điểm trung bình hiện hai thang: 81,3% ở Tiến độ và 8.1/10 ở Bài tập & kết quả. Ở Giáo viên đã chốt (GV-07) dùng thang /10. Phụ huynh chưa quyết. | Đề xuất áp dụng đúng quyết định GV-07: dùng `8,1/10` ở Tiến độ, điểm theo môn `8,5/10`, giữ phần trăm ở chiều rộng thanh. Cần người dùng quyết vì đụng `pc-data.js` dùng chung với Học sinh. | Cả hai (`pc-data.js`) | Chờ quyết (chưa đổi gì). |
| PH-09 | A11y | Lịch học: nhãn ngày hôm nay "T5" (chữ trắng 85% trên `--primary`) và ô "Trống" | "T5" 4.42:1 (chữ 12px, tính theo trắng đặc nên giá trị thật còn thấp hơn); "Trống" dùng `--text-subtle` trên nền xanh nhạt 4.18:1. Cùng cách sửa đã làm ở Giáo viên (GV-08): chữ trắng đặc, `--text-muted`. | Đổi `rgba(255,255,255,.85)` thành `var(--white)`; "Trống" dùng `--text-muted`. | File vai trò | Đã sửa: audit không còn chữ nào dưới 4.5:1 ngoài chữ trắng trên nền `--primary` 4.42:1 (nhãn "T5" nay là trắng đặc, cùng mức với ngoại lệ nút primary; muốn đạt 4.5 thì phải đổi nền ô hôm nay sang `--blue-600`, chưa làm để khớp Giáo viên); "Trống" dùng `--text-muted`. |
| PH-10 | Responsive | Tiến độ ở 375px, khối "Theo môn" | Giá trị phần trăm ("77,5%", "85%") rơi xuống dòng riêng dưới tên môn, thụt lề, không thẳng với thanh tiến độ như ở 1300px (ảnh `p_375_5`). | Hàng môn dùng lưới 2 cột ở ≤760px (tên + giá trị, thanh xuống dòng dưới) thay vì để tự rớt dòng. | File vai trò | Đã sửa: ở 375px điểm theo môn nằm cùng dòng với tên môn, thanh tiến độ xuống dưới và rộng hết hàng (ảnh). |

## Mức thấp
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| PH-11 | Token | Tiến độ, nhãn bên dưới biểu đồ "Điểm theo ngày công bố" (tên bài) | `font-size:11px` (4 nhãn), dưới mức tối thiểu 12px đã chốt ở v2; đã được ghi trong "Chưa làm" của CHANGES.md ("Phụ huynh còn 1 chỗ 11px"), nay xác nhận lại ở 375px và 1300px. | Nâng lên 12px, nhãn rút gọn bằng dấu … và `title`. | File vai trò | Đã sửa: audit chữ <12px = 0 (trước 4 nhãn). |
| PH-12 | Responsive | Chọn con ở 375px | Tên con bị bẻ giữa cụm ("Đỗ Minh / Khuê"), dòng "Con gái · Lớp 9A2 · 3 lớp" chiếm 3 dòng vì badge "Đang xem" chiếm chỗ; thẻ thứ hai cao khác thẻ đầu. | Cho badge xuống dưới tên ở ≤760px hoặc rút gọn thành dấu tích. | File vai trò | Đã sửa: ở 375px badge "Đang xem" xuống dòng dưới, mũi tên ẩn, tên con không bị bẻ giữa cụm (ảnh). |

## Phát hiện thêm ở bước 3 (frontend-design, so với mockup Quản trị và Giáo viên)
Nguồn: so ảnh chụp Phụ huynh với Quản trị và Giáo viên ở 1300px (Tổng quan, Lịch học, Điểm danh, Tiến độ).
| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| PH-13 | A11y/Phân cấp | Điểm danh, icon trong 10 ô gần nhất | Icon dùng `--att-*` (cấp 500): xanh lá 2,77:1 và cam 2,36:1 trên nền nhạt, dưới 3:1 của thành phần UI. | Icon dùng `--badge-<tone>-fg`, gộp với PH-04. | File vai trò | Đã sửa: đo màu tính toán của các ô: xanh lá `rgb(20,104,74)`, cam `rgb(122,74,6)`, tím `rgb(109,63,217)` (cặp Badge đã đạt ≥4.5:1 ở `contrast.py`). |
| PH-14 | Nhất quán | Điểm danh, hàng 4 ô thống kê | Chỉ có Chuyên cần, Có mặt, Đi muộn, Vắng: 7 + 1 + 0 = 8 trên 10 buổi, 2 buổi "Có phép" không có số nào, trong khi chú thích bên dưới có 4 trạng thái. | Thêm ô "Có phép" (thành 5 ô) hoặc đổi bố cục; cần quyết vì đổi lưới 4 cột. | File vai trò | Chờ quyết |
| PH-15 | Nhất quán | Lịch học | Giáo viên có nút Hôm nay, tuần trước, tuần sau; Phụ huynh chỉ có "Tuần 12/10 – 18/10", không xem được tuần khác. Có thể là chủ ý (phạm vi nhóm 0 và 2). | Thêm điều hướng tuần giống Giáo viên, hoặc ghi rõ là ngoài phạm vi. | File vai trò | Chờ quyết |

## Điểm đạt (đã xác minh)
- Không tràn ngang: Chọn con, 7 mục menu, chi tiết bài, kết quả, tiến độ lọc theo môn, ở cả 4 khổ 1300, 900, 500, 375px (`scrollWidth` = `clientWidth`); riêng hộp thoại xem PH-01.
- Mỗi màn đúng một `h1`; không ảnh thiếu `alt`; không chữ in hoa; không ô nhập thiếu nhãn; không `opacity` làm mờ chữ (ngoài nút disabled); không lỗi JS.
- Menu rail ở ≤1100px và menu trượt ở ≤760px có tên truy cập (sửa HS-02 có hiệu lực); nút Mở menu, Chọn con, Thông báo có `aria-label`.
- Hộp thoại Gửi đơn: `aria-labelledby`, `aria-modal`, focus vào hộp thoại, Esc đóng, nút cao 44px ở khổ cảm ứng; gửi để trống lý do thì hiện lỗi `role="alert"` "Vui lòng nhập lý do", `aria-invalid` và `aria-describedby` đúng, ô Lý do có dấu `*`.
- Nhãn của Select "Buổi học" và ô "Lý do" nối đúng.
- Không có chữ nào dưới 4.5:1 ngoài các chỗ ở PH-09 và nút primary 4.42:1 (ngoại lệ).
- Quy tắc riêng tư (nhận xét mức private không hiện, phụ huynh không xem bài làm) có ghi rõ trên màn chi tiết bài.

## Chưa kiểm tra
- Nhận xét: mới thử bấm một nhận xét (mở chi tiết bài); chưa đi qua các trạng thái "Chưa công bố" và "Không có quyền" của màn kết quả.
- Màn Chọn con ở trạng thái "Chưa liên kết" và "Mất quyền xem con" mới bấm qua, chưa đọc kỹ ảnh; chưa thử đổi sang con thứ hai (Đỗ Bảo Vy) để xem dữ liệu và bộ chọn con đổi theo.
- Bàn phím thật: mới đo thứ tự Tab ở màn đầu; chưa Tab qua các màn con và hộp thoại.
- Vùng chạm ở khổ cảm ứng thật: Chrome headless không bật `pointer:coarse`, nên chỉ thấy hộp thoại cao 44px; chưa đo thẻ và hàng danh sách.
- Số đo contrast là ước tính từ màu tính toán (không gồm alpha); `contrast.py` chỉ chạy cho token.
- Bước 3 (frontend-design) mới ở mức so ảnh các màn chính; chưa so các màn con (chi tiết bài, kết quả, hộp thoại).

## Gợi ý thứ tự sửa
1. Design system: `Dialog` (PH-01, một lần cho mọi vai trò).
2. File Phụ huynh, nhóm cao: PH-02 (bộ chọn con), PH-03 (bàn phím), PH-04 (ô điểm danh).
3. Trung bình và thấp: PH-05 đến PH-12. PH-08 cần quyết trước (cùng `pc-data.js` với Học sinh).
4. Chạy `mockup-review-fix` (bước 3 và 4) để áp dụng, so sánh nhất quán và xác minh.

## Đã áp dụng
Ngày 2026-10-23, repo `piclass-mockup` branch `fix/parent-review`, commit `8aca5c0` (chưa push, chưa mở PR). Cách xác minh ghi ở cột Trạng thái của từng mã: audit bằng puppeteer trên Chrome (tràn ngang, phần tử bấm được thiếu role, chữ nhỏ, chữ in hoa, contrast) ở Chọn con, 7 màn menu và các màn con, 4 khổ 1300, 900, 500, 375px (32 màn, 0 tràn ngang, 0 chữ nhỏ, 0 phần tử bấm thiếu role); đo hộp thoại ở 375px; cây truy cập của Chrome cho ô điểm danh; thử Enter chọn con; bấm từng trạng thái giả lập ở 7 màn; `node --check` trên script và `_ds_bundle.js`; xem ảnh sau khi sửa (Chọn con, Tiến độ, hộp thoại ở 375px, Điểm danh và khối rỗng); mở thử Học sinh, Quản trị, Giáo viên sau khi sửa `Dialog`, không lỗi JS. Chi tiết: `piclass-mockup/design-system/CHANGES.md`, mục "Sửa theo review Phụ huynh".

**Chưa kiểm tra sau khi sửa:** hộp thoại của Học sinh (xác nhận nộp bài) ở 375px sau khi đổi `Dialog`; thứ tự Tab qua các màn con; vùng chạm trên thiết bị cảm ứng thật; trạng thái "Chưa liên kết" và "Mất quyền xem con" của Chọn con; đổi sang con thứ hai ngoài việc chọn bằng Enter; số đo contrast vẫn là màu tính toán.
