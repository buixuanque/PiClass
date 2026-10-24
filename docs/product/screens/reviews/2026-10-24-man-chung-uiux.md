# Review ui-ux-pro-max: mockup Màn chung (CM-01 đến CM-10)

Trạng thái tổng: 9/9 đã xử lý

Ngày review: 2026-10-24. Phạm vi: `piclass-mockup/design-system/PiClass Màn chung.dc.html` (bản `standalone/`), branch `feat/mockup-common` (commit `61c2acd`). Mã phát hiện dùng tiền tố `MC`.

## 1. Phạm vi và cách làm

- Mở bản standalone bằng Chrome headless (puppeteer-core), vào từng màn bằng đường dẫn `#<màn>`: `login`, `forgot`, `reset`, `activate`, `roles`, `home`, `profile`, `notifs`, `files`, `e401`, `e404`.
- Đo tràn ngang ở **375, 500, 900, 1300px** cho 10 màn (login, forgot, reset, activate, roles, home, profile, notifs, files, e404).
- Đo bằng DOM thật: contrast tính từ màu chữ và nền thực của từng phần tử (login, home, notifs, profile, reset, roles, e401, e404, files, forgot, activate, hộp thoại Đổi vai trò), vùng chạm ở 375px (login, roles) và 1300px (home), `aria-*`, vai trò của banner, vị trí focus sau thao tác bàn phím (Tab, Esc, ArrowDown), focus ring.
- Chụp ảnh: login (mặc định, bị khóa), home, hồ sơ, thông báo, tệp, menu người dùng ở 375px, thông báo ở 375px.
- `audit_static.py`: 0 hex rời, 0 chữ dưới 12px, 0 chữ in hoa, 0 `min-width` từ 500px, 0 ô nhập thiếu nhãn. Đã đọc nguồn component trong `_ds_bundle.js` (Input, IconButton, Dialog, ProgressBar) trước khi kết luận.
- Truy vấn tiêu chí ui-ux-pro-max: `error summary validation`, `live region countdown timer`, `popover menu focus management` (tìm thấy quy tắc "Focusable Error Summary" và "Contextual Live Badge Updates"; truy vấn popover không có kết quả riêng, dùng quy tắc bàn phím chung).
- Không đề xuất đổi style, màu hay font.

## 2. Phát hiện

### Cao

| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| MC-01 | Accessibility (thông báo động) | CM-01 Đăng nhập, tab "Bị tạm khóa" và khi sai 5 lần | Banner khóa có `role="alert"` và chữ đổi mỗi giây ("Vui lòng thử lại sau 41 giây" rồi 40…), trình đọc màn hình sẽ đọc lại 42 lần. Đo: `.banner` role=`alert`, nội dung chứa số giây đếm ngược | Banner khóa dùng `role="status"` chỉ đặt lời nhắn tĩnh ("Thử lại sau ít phút", hoặc đọc mốc 42 giây một lần), số giây đếm ngược để ở nút (nút đã có) và `aria-hidden` hoặc không nằm trong vùng live | File vai trò | Đã sửa: banner khóa đổi sang `role="status"`, chữ tĩnh, số giây chỉ còn ở nút (đo: role=status, chữ không đổi sau 2 giây) |

### Trung bình

| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| MC-02 | Accessibility (form và lỗi) | CM-01, CM-02, CM-03, CM-09 và hồ sơ (lưu, đổi mật khẩu) | Bấm gửi khi có lỗi chỉ hiện lỗi cạnh ô, không chuyển focus tới ô lỗi đầu tiên hoặc tóm tắt lỗi. Đo: sau gửi trống ở login có 2 ô `aria-invalid=true`, 2 lỗi `role=alert`, nhưng `activeElement` không nằm trong ô lỗi; hồ sơ đổi mật khẩu có 2 ô lỗi, focus không chuyển. Tiêu chí: "Focusable Error Summary" | Sau `setState` có lỗi, focus vào ô lỗi đầu tiên (hoặc vào khối tóm tắt có liên kết tới ô); giữ lỗi theo ô | File vai trò | Đã sửa: sau gửi lỗi focus vào ô lỗi đầu tiên (đo: login trống focus vào ô email `aria-invalid=true`; hồ sơ đổi mật khẩu focus vào ô) |
| MC-03 | Accessibility (bàn phím) | CM-10 menu người dùng (`role="menu"`) và bảng chuông (`role="dialog"`) | Gắn `role="menu"`/`menuitem` nhưng không có điều hướng bằng phím mũi tên và focus không vào menu khi mở. Đo: bấm mở menu thì focus vẫn ở nút, nhấn ArrowDown không đổi focus (Tab mới nhảy tới "Hồ sơ của tôi"). Bảng chuông `role="dialog"` nhưng không chuyển focus vào trong | Hoặc bỏ `role=menu/menuitem/dialog` và dùng mẫu "nút mở rộng" (`aria-expanded` đã có) với các nút thường; hoặc làm đủ bàn phím (mũi tên, Home/End, Esc trả focus) | File vai trò | Đã sửa: bỏ `role=menu/menuitem/dialog`, dùng nút mở rộng `aria-expanded` + `aria-controls` (đo: 0 phần tử role menu) |
| MC-04 | Accessibility (focus) | CM-10 menu người dùng và hộp thoại "Đổi vai trò" | Đóng bằng Esc khi focus đang ở mục menu thì focus rơi về `body` (đo: `activeElement` = null). Mở hộp thoại Đổi vai trò từ menu rồi đóng, focus cũng rơi về `body` vì phần tử gọi đã bị gỡ khỏi trang | Khi đóng menu hoặc hộp thoại, đưa focus về nút người dùng (đặt `ref` rồi `.focus()`) | File vai trò | Đã sửa: Esc trả focus về nút người dùng; đóng hộp thoại Đổi vai trò trả focus về nút gọi (đo: `activeElement` = nút "Menu người dùng") |
| MC-05 | Accessibility (cấu trúc trang) | CM-01 đến CM-04, CM-09 và trang lỗi toàn trang | Màn chưa đăng nhập không có vùng `main` (đo: 0 phần tử `main` ở login). Người dùng đọc màn hình không nhảy được tới nội dung | Bọc thẻ nội dung của màn auth và trang lỗi toàn trang bằng `<main>`; để tên thương hiệu ngoài `main` | File vai trò | Đã sửa: nội dung màn auth và trang lỗi toàn trang nằm trong `main` (đo: 1 `main` ở login) |
| MC-06 | Vùng chạm | CM-01 và CM-04 ở 375px | Liên kết dạng chữ cao 24px ("Quên mật khẩu?", "Đăng xuất" ở màn chọn vai trò); hàng "Hiện mật khẩu" và "Ghi nhớ đăng nhập" cao 22px (đo bằng `getBoundingClientRect` ở 375px). Quy tắc `pointer:coarse` ở `base.css` chỉ nâng nút, tab, select và nhãn công tắc, không nâng hai loại này | Cho `.linkbtn` `min-height:44px` (padding dọc); nâng nhãn bao `Checkbox` lên tối thiểu 44px ở `pointer:coarse` | Design system (Checkbox) + File vai trò (`.linkbtn`) | Đã sửa: `.linkbtn` cao 44px (đo 44); thêm quy tắc `pointer:coarse` nâng nhãn Checkbox/Radio lên 44px ở `base.css` (chưa đo vì headless không giả lập màn cảm ứng) |
| MC-07 | State | Hồ sơ: ô "Họ và tên", "Email đăng nhập" (chỉ đọc) | Ô `disabled` trông như ô sửa được: nền trắng, chữ đậm `rgb(15,27,61)`, `opacity` 1, `cursor:default` (đo). Người dùng không phân biệt ô sửa được và ô chỉ đọc. Component Select đã có kiểu `disabled`, Input thì chưa | Thêm kiểu disabled cho `Input` (nền `--surface-muted`, chữ `--text-body`, viền nhạt) mà vẫn đạt 4.5:1. Ảnh hưởng mọi vai trò | Design system | Đã sửa: `Input` disabled dùng nền `--surface-muted`, viền nhạt, không bóng, chữ `--text-body`, con trỏ `not-allowed` (đo: nền rgb(247,249,253), chữ rgb(43,54,85)) |
| MC-08 | Accessibility (bàn phím) | Ngăn kéo menu ở ≤760px (cả file Màn chung và 4 mockup vai trò) | Ngăn kéo không có `role="dialog"`, `aria-modal`, không đóng bằng Esc, không bẫy focus. Đo ở 375px: `.drawer` role=null, aria-modal=null, Esc không đóng. Chưa được ghi trong `CHANGES.md` | Thêm `role="dialog"` `aria-modal="true"` có nhãn, đóng bằng Esc, đưa focus vào ngăn kéo và trả về nút "Mở menu". Sửa chung bằng một mẫu cho 5 file | File vai trò (5 file) | Đã sửa ở 5 file: `.drawer` có `role="dialog"` `aria-modal`, Esc đóng, Tab bị giữ trong ngăn kéo, focus vào mục đầu và trả về nút "Mở menu" (đo ở 375px cả 5 file) |

### Thấp

| Mã | Nhóm | Vị trí | Vấn đề | Cách sửa | Nơi sửa | Trạng thái |
|---|---|---|---|---|---|---|
| MC-09 | Accessibility (thông báo động, focus) | CM-06 danh sách thông báo; CM-08 hàng tệp | Danh sách thông báo nằm trong một vùng `aria-live="polite"` (đo: 1 vùng, 4 con), đổi trạng thái đọc có thể khiến đọc lại cả danh sách. Hàng tệp bị `disabled` khi đang lấy liên kết nên mất focus bàn phím (suy từ mã, chưa đo bằng bàn phím thật) | Bỏ `aria-live` khỏi danh sách thông báo, dùng một dòng `role="status"` riêng cho kết quả ("Đã đánh dấu đã đọc"). Với hàng tệp, dùng `aria-disabled` thay cho `disabled` và giữ focus | File vai trò | Đã sửa: bỏ `aria-live` khỏi danh sách thông báo; hàng tệp dùng `aria-disabled` thay `disabled` (đo: aria-disabled=true, disabled=false khi đang tải) |

## 3. Điểm đạt (đã xác minh)

- **Tràn ngang:** `scrollWidth` bằng `clientWidth` ở 375, 500, 900 và 1300px cho cả 10 màn đã đo.
- **Contrast:** mọi chữ đo được đạt 4.5:1 (hoặc 3:1 với chữ lớn), chỉ còn nút primary md/lg 4.42:1 là ngoại lệ đã chốt. Gồm banner bốn tông, huy hiệu chuông (chữ trắng trên `--danger-text`), thẻ vai trò, trạng thái tệp, dòng thông báo.
- **Cỡ chữ:** 0 chữ dưới 12px; 0 chữ in hoa; 0 hex rời.
- **Tên truy cập:** 0 nút không tên và 0 ô nhập không nhãn ở các màn đã đo (375px). Chuông có nhãn động "Thông báo, 3 chưa đọc" và con số trên huy hiệu `aria-hidden`.
- **Form:** ô lỗi có `aria-invalid` và `aria-describedby`, lỗi mới có `role="alert"`; `autocomplete` đúng (`username`, `current-password`; ô mật khẩu mới `new-password`); form dùng `noValidate` nên không hiện khung báo tiếng Anh của trình duyệt.
- **Focus ring:** 3px solid khi Tab.
- **Hộp thoại (DS):** focus vào hộp thoại khi mở, đóng bằng Esc.
- **Trạng thái đủ theo spec:** login (sai, khóa, mạng, hết phiên, đã đặt lại), forgot (đã gửi, giới hạn, mạng), reset và kích hoạt (hết hạn, đã dùng, đã kích hoạt, thành công, mạng), vai trò (đang tải, vai trò bị gỡ, chưa có vai trò), khung (khởi tạo, mất mạng, 0/số/99+), hồ sơ (đang tải, mạng, không có hồ sơ), thông báo (đang tải, rỗng, lỗi, đích không khả dụng), tệp (đang lấy, hết hạn, lỗi, 404, ảnh hỏng).
- **Hoạt động đúng:** hộp thoại rời trang khi chưa lưu, đếm ngược 60 giây của "Gửi lại" không nằm trong vùng live.

## 4. Chưa kiểm tra

- Không dùng trình đọc màn hình thật (NVDA, VoiceOver); các kết luận về live region và focus dựa trên DOM và mã.
- Ảnh chụp mới xem login, home, hồ sơ, thông báo, tệp, menu 375px; chưa xem ảnh từng trạng thái còn lại (đặt lại, kích hoạt, trang lỗi, hộp thoại xem ảnh, đang tải).
- Vùng chạm đo ở 375px cho login và roles, 1300px cho home; chưa đo hồ sơ, thông báo, tệp. Headless không giả lập `pointer:coarse`, nên quy tắc nâng 44px của `base.css` chưa được thử.
- Tab thứ tự toàn trang chỉ thử ở login và menu người dùng.
- Thanh "Xem thử trạng thái" chiếm khoảng 170px ở 375px; là công cụ trình diễn, đã biết (GV-15, `CHANGES.md`), không tính lỗi.
- Ô ngày không có trong màn này.

## 5. Đính chính

Không có phát hiện nào của lượt review trước bị sai; đây là lượt đầu cho file này. Lưu ý khi đo: `puppeteer` bấm bằng `click()` lập trình không đưa focus vào nút, nên MC-02 và MC-03 đã kiểm thêm bằng chuột và phím thật (ở menu người dùng, 375px).

## 6. Gợi ý thứ tự sửa

1. Design system (một lần cho mọi vai trò): MC-07 (Input disabled), phần Checkbox của MC-06.
2. File Màn chung: MC-01 (banner khóa), MC-02 (focus vào ô lỗi), MC-03 và MC-04 (menu, focus trả về), MC-05 (`main`), phần `.linkbtn` của MC-06, MC-09.
3. MC-08: một mẫu ngăn kéo dùng chung, áp dụng cho 5 file.
4. Sau khi sửa, chạy `mockup-review-fix` và đo lại bằng đúng các lệnh puppeteer ở mục 1.

## 7. Đã áp dụng ngày 2026-10-24

Sửa ở repo `piclass-mockup` branch `feat/mockup-common`. Đã sinh lại `standalone/`, `node --check` các khối script của 5 file (đều đạt), chạy lại luồng đăng nhập, vai trò, hồ sơ, thông báo, tệp, đăng xuất và đo tràn ngang ở 375px (không lỗi). Design system đổi: `Input` (kiểu disabled) và `base.css` (vùng chạm Checkbox/Radio ở màn cảm ứng), đã ghi vào `CHANGES.md`.
