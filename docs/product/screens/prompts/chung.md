# Bộ prompt — Nhóm chung (mọi vai trò)

Gồm 10 màn dùng chung cho mọi vai trò: khung điều hướng sau đăng nhập, các màn xác thực (đăng nhập, quên mật khẩu, đặt lại mật khẩu, kích hoạt tài khoản, chọn vai trò), hồ sơ cá nhân, thông báo, trang lỗi và xem/tải tệp đính kèm. Thứ tự vẽ gợi ý: khung điều hướng và đăng nhập trước, rồi các màn xác thực còn lại, hồ sơ, thông báo, trang lỗi, cuối cùng là xem/tải tệp. Dán từng prompt một; mỗi prompt tự đứng được.

| Mã | Màn hình | Ghi chú |
|---|---|---|
| CM-10 | Khung điều hướng theo vai trò | Màn nền bao quanh mọi màn sau đăng nhập; nêu rõ danh mục chức năng của từng vai trò |
| CM-01 | Đăng nhập | Cửa vào hằng ngày |
| CM-02 | Quên mật khẩu | Thông điệp trung tính, không lộ email có tồn tại hay không |
| CM-03 | Đặt lại mật khẩu | Mở từ liên kết trong email |
| CM-09 | Kích hoạt tài khoản lần đầu | Mở từ thư mời của trung tâm |
| CM-04 | Chọn / đổi vai trò | Chỉ dành cho tài khoản có từ 2 vai trò |
| CM-05 | Hồ sơ cá nhân | Gồm đổi mật khẩu, đăng xuất; Admin khác các vai trò còn lại |
| CM-06 | Trung tâm thông báo | Kênh thông báo duy nhất của V1 |
| CM-07 | Trang lỗi | Nhiều biến thể, mỗi biến thể cần vẽ riêng |
| CM-08 | Xem / tải tệp đính kèm | Thành phần xuất hiện trong nhiều màn khác |

---

### CM-10 — Khung điều hướng theo vai trò

```text
Màn hình: Khung điều hướng chung sau đăng nhập (cả 4 vai trò: Quản trị viên, Giáo viên, Học sinh, Phụ huynh; thiết bị ưu tiên: máy tính cho Quản trị viên/Giáo viên, điện thoại cho Học sinh/Phụ huynh, phải dùng tốt cả hai)

Tình huống: Người dùng vừa đăng nhập vào hệ thống quản lý lớp học thêm. Họ muốn đi nhanh tới đúng chức năng của vai trò mình, biết có thông báo mới hay không, và đổi vai trò hoặc đăng xuất ở cùng một chỗ. Học sinh/phụ huynh dùng điện thoại, thao tác một tay; giáo viên/quản trị viên dùng máy tính và cần thấy toàn bộ chức năng.

Thông tin cần có:
- Danh mục chức năng thay đổi theo vai trò đang chọn:
  - Quản trị viên: Tổng quan, Tài khoản, Học sinh, Phụ huynh, Giáo viên, Môn học, Lớp học, Lịch, Khóa học, Nhật ký.
  - Giáo viên: Tổng quan, Lớp của tôi, Lịch dạy, Đơn xin phép, Ngân hàng câu hỏi, Bài đánh giá, Khóa học.
  - Học sinh: Tổng quan, Lịch học, Bài tập, Bài kiểm tra, Kết quả, Tiến độ, Khóa học. Trên điện thoại chỉ 4 mục thường dùng hiện sẵn (Tổng quan, Lịch, Bài tập, Kết quả), các mục còn lại gom vào mục "Thêm".
  - Phụ huynh: Chọn con, Tổng quan, Lịch, Điểm danh, Bài tập & kết quả, Nhận xét, Tiến độ, Đơn xin phép. Tên con đang xem phải luôn nhìn thấy.
- Tên màn hình hiện tại.
- Chỉ báo số thông báo chưa đọc (0 thì ẩn, từ 100 trở lên hiện "99+").
- Thông tin người dùng: họ tên (Quản trị viên không có hồ sơ cá nhân thì hiện email đăng nhập), vai trò hiện tại.
- Lối tới: Hồ sơ của tôi, Đổi vai trò (chỉ khi tài khoản có từ 2 vai trò), Đăng xuất.
- Logo trung tâm, bấm vào về trang chủ của vai trò đang chọn.

Hành động: chuyển giữa các chức năng; mở thông báo; mở hồ sơ; đổi vai trò; đăng xuất (không cần xác nhận).

Quy tắc nghiệp vụ:
- Chỉ hiện chức năng thuộc vai trò đang chọn.
- Tài khoản vừa là Quản trị viên vừa là Giáo viên thấy hai nhóm chức năng "Giảng dạy" và "Quản trị" cùng lúc, bấm vào nhóm nào thì hệ thống tự chuyển sang vai trò đó mà người dùng không phải làm gì thêm.
- Phụ huynh chưa có con liên kết: chỉ còn "Chọn con" kèm lời nhắn "Chưa có con nào được liên kết. Hãy liên hệ trung tâm."
- Nếu vai trò hiện tại vừa bị gỡ: đưa người dùng về màn chọn vai trò.

Tình huống đặc biệt: đang khởi tạo (chưa biết vai trò nên chưa hiện danh mục); mất kết nối mạng (thông báo toàn cục); phiên sắp hết hạn (cảnh báo toàn cục); chỉ báo thông báo ở 3 mức 0 / có số / 99+; danh mục thu gọn và mở rộng trên máy tính.

Dữ liệu mẫu: Cô Hương, vừa Quản trị viên vừa Giáo viên; nhóm "Giảng dạy": Lớp của tôi, Lịch dạy; nhóm "Quản trị": Tài khoản, Lớp học; số thông báo chưa đọc: 3. Phụ huynh: chị Nguyễn Thị Lan, đang xem em Nguyễn Minh Anh (Toán 9A).
```

---

### CM-01 — Đăng nhập

```text
Màn hình: Đăng nhập (mọi vai trò, chưa đăng nhập; thiết bị ưu tiên: điện thoại cho học sinh/phụ huynh, máy tính cho quản trị viên/giáo viên; phải dùng tốt cả hai)

Tình huống: Một phụ huynh hoặc giáo viên mở hệ thống của trung tâm lớp học thêm để xem lịch học, bài tập hoặc điểm danh lớp đầu giờ. Cần vào nhanh, ít thao tác, đọc rõ khi gặp lỗi. Không có chức năng đăng ký tự do: tài khoản chỉ do trung tâm tạo và gửi lời mời.

Thông tin cần có:
- Logo và tên trung tâm.
- Ô nhập "Email" và ô nhập "Mật khẩu" (có cách hiện/ẩn mật khẩu).
- Lựa chọn "Ghi nhớ đăng nhập", mặc định tắt, kèm ghi chú nhỏ "Chỉ bật trên thiết bị của bạn".
- Lối "Quên mật khẩu?".
- Vùng báo lỗi chung cho cả màn.

Hành động:
- Đăng nhập (cũng gửi được bằng phím Enter).
- Đi tới quên mật khẩu.
- Hiện/ẩn mật khẩu.

Quy tắc nghiệp vụ:
- Sai email, sai mật khẩu hay tài khoản đã bị vô hiệu đều chỉ báo chung một câu, không tiết lộ lý do và không đánh dấu riêng ô nào: "Thông tin đăng nhập không đúng."
- Bỏ trống thì báo ngay tại ô: "Vui lòng nhập email" / "Vui lòng nhập mật khẩu".
- Sai 5 lần trong 60 giây thì bị khóa tạm: "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau {n} giây." Trong lúc khóa, hành động đăng nhập không dùng được và có đếm ngược.
- Khi đang gửi, hành động đăng nhập bị vô hiệu và có chỉ báo đang xử lý.
- Nếu người dùng bị đưa ra do hết phiên, hiện thông báo "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại." và sau khi đăng nhập quay lại đúng nơi đang làm dở.
- Sau khi đặt lại mật khẩu thành công, màn này hiện thông báo "Đã đặt lại mật khẩu, hãy đăng nhập".

Tình huống đặc biệt: mặc định; đang gửi; sai thông tin; bị tạm khóa có đếm ngược; mất mạng hoặc lỗi hệ thống ("Có lỗi xảy ra phía hệ thống. Hãy thử lại sau ít phút." kèm thử lại); thành công (đang chuyển trang); hết phiên; vừa đặt lại mật khẩu.

Dữ liệu mẫu: email co.huong@trungtam-pi.vn, mật khẩu ẩn; khóa mẫu: "Thử lại sau 42 giây".
```

---

### CM-02 — Quên mật khẩu

```text
Màn hình: Quên mật khẩu (mọi vai trò, chưa đăng nhập; thiết bị ưu tiên: điện thoại)

Tình huống: Một phụ huynh quên mật khẩu, mở màn này từ trang đăng nhập và cần nhận thư để đặt lại. Quan trọng nhất là đơn giản, ít chữ, không gây lo lắng.

Thông tin cần có:
- Tiêu đề và một câu giải thích: "Nhập email đăng nhập, chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu."
- Ô nhập "Email".
- Sau khi gửi: thông điệp "Nếu email này có tài khoản, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu. Liên kết có hiệu lực 60 phút."
- Lối quay lại đăng nhập.

Hành động:
- Gửi hướng dẫn.
- Gửi lại: chỉ dùng được sau khi chờ ít nhất 60 giây kể từ lần gửi trước (có đếm ngược).
- Quay lại đăng nhập.

Quy tắc nghiệp vụ:
- Hệ thống không bao giờ cho biết email có tồn tại hay không: kết quả luôn là thông điệp trung tính ở trên, dù email đúng hay sai.
- Bỏ trống email: "Vui lòng nhập email".
- Thao tác quá nhanh: "Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút."
- Sau khi gửi, màn chuyển sang trạng thái đã gửi, người dùng ở lại cùng màn.

Tình huống đặc biệt: mặc định; đang gửi; đã gửi (thông điệp trung tính, có đếm ngược trước khi gửi lại được); lỗi mạng hoặc lỗi hệ thống ("Có lỗi xảy ra phía hệ thống. Hãy thử lại sau ít phút."); bị giới hạn tần suất.

Dữ liệu mẫu: email phuhuynh.lan@gmail.com; đếm ngược gửi lại "Gửi lại sau 45 giây".
```

---

### CM-03 — Đặt lại mật khẩu

```text
Màn hình: Đặt lại mật khẩu (mọi vai trò, chưa đăng nhập; thiết bị ưu tiên: điện thoại)

Tình huống: Người dùng vừa bấm liên kết trong email "Đặt lại mật khẩu PiClass" và cần đặt mật khẩu mới. Quan trọng nhất là rõ ràng, không nhầm, biết ngay liên kết còn dùng được hay không.

Thông tin cần có:
- Tiêu đề "Đặt mật khẩu mới".
- Email của tài khoản đang đặt lại (chỉ đọc, không sửa được).
- Ô nhập "Mật khẩu mới" (có hiện/ẩn) kèm gợi ý "Ít nhất 8 ký tự" và chỉ báo độ dài đã nhập.
- Ô nhập "Nhập lại mật khẩu".
- Khi liên kết không dùng được: lời giải thích và lối xin liên kết mới.

Hành động:
- Lưu mật khẩu.
- Xin liên kết mới (chỉ khi liên kết hỏng hoặc hết hạn).

Quy tắc nghiệp vụ:
- Mật khẩu tối thiểu 8 ký tự, không yêu cầu chữ hoa hay số: "Mật khẩu phải có ít nhất 8 ký tự".
- Hai ô mật khẩu phải khớp nhau: "Mật khẩu nhập lại chưa khớp".
- Liên kết hết hạn sau 60 phút và chỉ dùng được một lần: "Liên kết đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu liên kết mới."
- Nếu liên kết thiếu thông tin thì không hiện phần nhập mật khẩu, báo ngay liên kết không hợp lệ.
- Đặt xong, mọi thiết bị đang đăng nhập bằng tài khoản này bị đăng xuất; người dùng không được đăng nhập tự động mà được đưa về trang đăng nhập kèm lời nhắn "Đã đặt lại mật khẩu, hãy đăng nhập".

Tình huống đặc biệt: mặc định; đang gửi; thành công (thông điệp ngắn rồi chuyển trang); liên kết không hợp lệ hoặc hết hạn; mật khẩu quá ngắn; hai mật khẩu không khớp; lỗi mạng.

Dữ liệu mẫu: email phuhuynh.lan@gmail.com; mật khẩu mới Matkhau2026.
```

---

### CM-09 — Kích hoạt tài khoản lần đầu

```text
Màn hình: Kích hoạt tài khoản lần đầu (học sinh, phụ huynh hoặc giáo viên vừa được trung tâm mời; thiết bị ưu tiên: điện thoại, vì thường mở từ email)

Tình huống: Trung tâm lớp học thêm đã tạo tài khoản và gửi thư mời. Người nhận bấm liên kết trong thư để lần đầu đặt mật khẩu và bắt đầu dùng hệ thống. Cần thân thiện, ngắn gọn, ít thao tác. Trước khi đặt mật khẩu, tài khoản chưa thể đăng nhập.

Thông tin cần có:
- Lời chào "Chào mừng đến với PiClass" và câu "Tài khoản của bạn đã được trung tâm tạo. Hãy đặt mật khẩu để bắt đầu."
- Email đăng nhập (chỉ đọc); nếu biết thì kèm tên và lớp của người được mời.
- Ô nhập "Mật khẩu" (ít nhất 8 ký tự) và ô nhập "Nhập lại mật khẩu".
- Lựa chọn "Tôi đồng ý điều khoản sử dụng".

Hành động:
- Kích hoạt tài khoản.
- Sau khi thành công: đi tới đăng nhập.
- Khi lời mời hỏng hoặc hết hạn: xin liên kết mới, hoặc đi tới đăng nhập/quên mật khẩu tùy trường hợp.

Quy tắc nghiệp vụ:
- Mật khẩu tối thiểu 8 ký tự: "Mật khẩu phải có ít nhất 8 ký tự"; hai ô phải khớp: "Mật khẩu nhập lại chưa khớp".
- Chỉ kích hoạt được khi đã đồng ý điều khoản.
- Lời mời chỉ có hiệu lực trong một thời gian và chỉ dùng được một lần.
- Chưa kích hoạt thì không thể đăng nhập.

Tình huống đặc biệt: mặc định; đang gửi; thành công ("Tài khoản đã sẵn sàng" kèm lối Đăng nhập); lời mời đã hết hạn (tiêu đề "Lời mời đã hết hạn", giải thích "Liên kết mời chỉ có hiệu lực trong thời gian ngắn", lối "Nhận liên kết mới"); liên kết đã dùng hoặc không hợp lệ ("Liên kết đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu liên kết mới."); tài khoản đã kích hoạt trước đó (gợi ý Đăng nhập hoặc Quên mật khẩu); lỗi mạng.

Dữ liệu mẫu: email hs.minhanh@gmail.com; tiêu đề "Chào mừng em Nguyễn Minh Anh (Toán 9A)".
```

---

### CM-04 — Chọn / đổi vai trò

```text
Màn hình: Chọn / đổi vai trò (tài khoản có từ 2 vai trò trở lên, thường là Quản trị viên + Giáo viên, hiếm hơn là Giáo viên + Phụ huynh; thiết bị ưu tiên: cả điện thoại và máy tính)

Tình huống: Ngay sau khi đăng nhập, hoặc khi đang dùng hệ thống và muốn chuyển, người dùng chọn mình đang vào với tư cách ai. Ví dụ cô Hương vừa là giáo viên vừa là quản trị viên, cần chuyển nhanh giữa việc giảng dạy và việc quản trị. Quan trọng nhất là chọn nhanh bằng một chạm và không nhầm vai trò.

Thông tin cần có: mỗi vai trò mà tài khoản đang giữ, gồm tên vai trò và một dòng mô tả:
- Quản trị viên: Quản lý tài khoản, lớp, môn học.
- Giáo viên: Lịch dạy, điểm danh, chấm bài.
- Học sinh: Lịch học, bài tập, kết quả.
- Phụ huynh: Theo dõi việc học của con.
Vai trò đang hoạt động phải được đánh dấu rõ. Vai trò đã hết hạn không xuất hiện.

Hành động:
- Chọn một vai trò để vào trang chủ của vai trò đó. Khi đổi giữa phiên, dữ liệu của vai trò cũ được làm mới hoàn toàn.
- Bấm lại vai trò đang hoạt động thì chỉ đóng, không đổi gì.
- Đăng xuất (trong trường hợp không có vai trò nào).

Quy tắc nghiệp vụ:
- Tài khoản chỉ có một vai trò thì không hiện màn này, hệ thống tự vào.
- Vai trò vừa bị gỡ: "Tài khoản của bạn không còn vai trò này." rồi tự làm mới danh sách.
- Tài khoản chưa được cấp vai trò nào: "Tài khoản chưa được cấp quyền truy cập. Vui lòng liên hệ quản trị viên."

Tình huống đặc biệt: đang tải danh sách vai trò; đang đổi vai trò; lỗi khi đổi; không có vai trò nào.

Dữ liệu mẫu: tài khoản của cô Hương có 2 vai trò, Quản trị viên và Giáo viên; "Giáo viên — Lịch dạy, điểm danh, chấm bài" đang được chọn.
```

---

### CM-05 — Hồ sơ cá nhân

```text
Màn hình: Hồ sơ cá nhân, gồm đổi mật khẩu và đăng xuất (Giáo viên, Học sinh, Phụ huynh xem và sửa đủ; Quản trị viên chỉ có phần tài khoản; thiết bị ưu tiên: điện thoại cho học sinh/phụ huynh)

Tình huống: Một phụ huynh muốn cập nhật số điện thoại để trung tâm liên hệ được, đổi mật khẩu, hoặc đăng xuất khỏi thiết bị. Cần rõ cái gì sửa được, cái gì không, và an toàn khi đổi mật khẩu.

Thông tin cần có, gồm các nhóm:
- Thông tin cá nhân: họ tên (chỉ đọc, kèm ghi chú "Muốn đổi họ tên, hãy liên hệ trung tâm"), email liên hệ (sửa được), số điện thoại (sửa được), trạng thái hồ sơ theo vai trò đang dùng (chỉ đọc). Phải nói rõ email này khác với email dùng để đăng nhập, đổi email liên hệ không làm đổi cách đăng nhập.
- Tài khoản: email đăng nhập (chỉ đọc), các vai trò đang giữ, vai trò đang hoạt động.
- Đổi mật khẩu: mật khẩu hiện tại, mật khẩu mới (ít nhất 8 ký tự), nhập lại mật khẩu mới.
- Phiên đăng nhập: đăng xuất.

Hành động: lưu thay đổi hồ sơ; đổi mật khẩu; đăng xuất (không cần xác nhận); đổi vai trò (chỉ khi có từ 2 vai trò).

Quy tắc nghiệp vụ:
- Email liên hệ không hợp lệ: "Email không hợp lệ".
- Mật khẩu hiện tại sai: "Mật khẩu hiện tại không đúng."
- Mật khẩu mới ngắn: "Mật khẩu mới phải có ít nhất 8 ký tự"; nhập lại không khớp: "Mật khẩu nhập lại chưa khớp".
- Trước khi đổi mật khẩu phải cảnh báo "Đổi mật khẩu sẽ đăng xuất các thiết bị khác" và xin xác nhận nhẹ; thiết bị đang dùng vẫn giữ đăng nhập. Đổi xong, xóa sạch các ô mật khẩu.
- Rời màn khi có thay đổi chưa lưu thì hỏi xác nhận.
- Quản trị viên chỉ có vai trò Quản trị viên thì không có phần thông tin cá nhân, thay vào đó có dòng "Tài khoản quản trị không có hồ sơ cá nhân". Nếu đồng thời là Giáo viên thì thấy đầy đủ như giáo viên.

Tình huống đặc biệt: đang tải; đang lưu; lưu thành công (thông báo ngắn); lỗi theo từng ô; mật khẩu hiện tại sai; tài khoản quản trị không có hồ sơ; lỗi mạng.

Dữ liệu mẫu: Nguyễn Thị Lan · lan.nguyen@gmail.com · 0912 345 678 · vai trò Phụ huynh đang hoạt động · hồ sơ "Đang hoạt động"; email đăng nhập phuhuynh.lan@gmail.com.
```

---

### CM-06 — Trung tâm thông báo

```text
Màn hình: Trung tâm thông báo (mọi vai trò; thiết bị ưu tiên: điện thoại cho học sinh/phụ huynh, máy tính cho quản trị viên/giáo viên)

Tình huống: Người dùng muốn biết có gì mới (bài mới giao, đổi lịch, buổi học bị hủy, đã có kết quả, đơn xin phép) và mở thẳng nội dung đó. Phiên bản này chỉ có thông báo trong ứng dụng, không có tin nhắn hay email thay thế, nên đây là kênh duy nhất. Quan trọng nhất là nhìn ra ngay cái nào chưa đọc và bấm một lần là tới đúng nơi.

Thông tin cần có:
- Danh sách thông báo, mới nhất trước. Mỗi thông báo gồm: loại sự kiện (nhận ra được qua biểu tượng riêng), tiêu đề, nội dung ngắn, thời gian tương đối theo giờ Việt Nam, trạng thái đã đọc hay chưa.
- Bộ lọc "Tất cả / Chưa đọc / Đã đọc".
- Số thông báo chưa đọc.

Hành động:
- Lọc theo trạng thái đọc.
- Mở một thông báo: tự đánh dấu đã đọc rồi đi tới nội dung liên quan. Thông báo đã đọc vẫn đi tới như cũ.
- Đánh dấu tất cả đã đọc.
- Làm mới (thông báo không tự cập nhật, chỉ làm mới khi mở màn, khi quay lại ứng dụng hoặc khi người dùng yêu cầu).

Quy tắc nghiệp vụ:
- Đích đến theo loại thông báo: bài mới giao tới chi tiết bài; kết quả được công bố tới danh sách kết quả; buổi học đổi hoặc hủy tới chi tiết buổi học (buổi đã hủy hiện trạng thái "đã hủy"); lịch đổi tới màn lịch tuần hiện tại; đơn xin phép mới gửi (giáo viên nhận) tới đơn đang chờ duyệt; đơn xin phép đã được xét (học sinh/phụ huynh nhận) tới trạng thái đơn, ghi bằng tiếng Việt "Đã chấp nhận" hoặc "Đã từ chối".
- Có thông báo không dẫn đi đâu, chỉ để đọc. Loại thông báo lạ vẫn hiện bình thường nhưng không điều hướng.
- Nếu người dùng không còn quyền xem nội dung đích, hiện: "Không tìm thấy nội dung hoặc bạn không có quyền xem."

Tình huống đặc biệt: đang tải; lọc "Chưa đọc" mà hết thông báo ("Bạn đã xem hết thông báo"); chưa có thông báo nào ("Chưa có thông báo. Khi có bài mới, đổi lịch hoặc kết quả, bạn sẽ thấy ở đây"); lỗi tải có thử lại; đang đánh dấu đã đọc; nội dung đích không còn khả dụng.

Dữ liệu mẫu:
- "Bạn được giao bài mới" — "Có một bài kiểm tra/bài tập mới được giao cho bạn." — hôm nay 16:05, chưa đọc.
- "Buổi học đã bị hủy" — "Một buổi học của lớp bạn tham gia vừa bị hủy." — hôm qua, chưa đọc.
- "Kết quả bài làm đã được công bố" — 3 ngày trước, đã đọc.
```

---

### CM-07 — Trang lỗi

```text
Màn hình: Trang lỗi (mọi vai trò; thiết bị ưu tiên: cả điện thoại và máy tính)

Tình huống: Có sự cố khiến cả màn hình không dùng được. Người dùng cần hiểu chuyện gì đã xảy ra bằng lời đơn giản và luôn có lối ra rõ ràng, tuyệt đối không bị kẹt. Lỗi chỉ xảy ra ở một phần nhỏ của màn (ví dụ một danh sách không tải được) không dùng trang này mà có thông báo lỗi kèm thử lại ngay tại chỗ; lỗi nhập sai ở ô nhập cũng không dùng trang này.

Thông tin cần có: mỗi biến thể gồm tiêu đề, một đến hai dòng giải thích, các lối ra, và ở cuối một mã nhỏ kèm giờ xảy ra để người dùng đọc cho quản trị viên khi cần hỗ trợ.

Cần vẽ đủ các biến thể sau:
1. Hết phiên: "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại." Lối ra: Đăng nhập (sau đó quay lại nơi đang làm dở).
2. Không tìm thấy hoặc không có quyền (hệ thống cố ý không phân biệt hai trường hợp): "Không tìm thấy nội dung hoặc bạn không có quyền xem." Lối ra: Về trang chủ; nếu tài khoản có vai trò khác thì thêm Đổi vai trò.
3. Sai vai trò: "Chức năng này không dành cho vai trò đang chọn." Lối ra: Đổi vai trò (nếu có từ 2 vai trò), Về trang chủ.
4. Tài khoản bị vô hiệu giữa phiên: "Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ trung tâm." Không có lối thử lại. Lối ra: Đăng xuất hoặc về đăng nhập.
5. Lỗi hệ thống hoặc mất mạng: "Có lỗi xảy ra phía hệ thống. Hãy thử lại sau ít phút." Lối ra: Thử lại, Về trang chủ.
6. Thao tác quá nhanh: "Bạn thao tác quá nhanh, vui lòng đợi một lát." Lối ra: Thử lại.
7. Đường dẫn không tồn tại: nội dung như biến thể 2, lối ra: Về trang chủ.

Quy tắc nghiệp vụ: "Về trang chủ" luôn dẫn tới trang chủ của vai trò đang chọn. Các biến thể phải khác nhau rõ về nội dung và lối ra.

Dữ liệu mẫu: mã nhỏ "Mã hỗ trợ: 404-17A2 · 22/10/2026 17:41".
```

---

### CM-08 — Xem / tải tệp đính kèm

```text
Màn hình: Xem / tải tệp đính kèm (thành phần xuất hiện bên trong các màn khác như chi tiết bài tập/bài kiểm tra, câu hỏi, soạn bài; dùng cho Quản trị viên, Giáo viên, Học sinh, Phụ huynh; thiết bị ưu tiên: điện thoại cho học sinh/phụ huynh)

Tình huống: Học sinh hoặc phụ huynh muốn mở đề bài PDF hoặc xem hình minh họa của một câu hỏi; giáo viên kiểm tra tệp đã đính kèm. Không có trang "kho tệp" riêng. Cần nhận ra ngay đó là tệp gì, nặng bao nhiêu, và mở được chỉ với một chạm.

Thông tin cần có cho mỗi tệp: biểu tượng theo loại tệp, tên tệp, dung lượng (đổi sang KB hoặc MB), loại tệp.

Hành động: bấm vào tệp để mở hoặc tải:
- Ảnh (jpg, png): xem trước ngay trong ứng dụng, không rời màn.
- PDF: mở ra xem.
- Word (doc, docx) và zip: tải về.

Quy tắc nghiệp vụ:
- Liên kết tải được tạo vào đúng lúc bấm và chỉ dùng được trong 10 phút; hết hạn thì tự tạo lại một lần, kèm lời nhắn "Liên kết tải đã hết hạn, đang tạo lại…".
- Học sinh và phụ huynh chỉ thấy tệp mà họ được phép xem, và chỉ khi bài đó đã giao cho học sinh và đã đến giờ mở; tệp không được phép xem thì không hiện, không báo là bị ẩn. Có tệp chỉ học sinh xem được, phụ huynh không xem được.
- Tệp chưa tải lên xong hoặc bị lỗi không được hiện.
- Học sinh và phụ huynh chỉ xem và tải, không tải lên hay xóa tệp.
- Tệp không tìm thấy hoặc không có quyền xem: "Không tìm thấy tệp hoặc bạn không có quyền xem".
- Giới hạn khi tải lên (nhắc người soạn bài): tối đa 10 MB; định dạng pdf, doc/docx, jpg, png, zip.

Tình huống đặc biệt: tệp bình thường; đang lấy liên kết; tải thất bại (có thử lại); liên kết hết hạn đang tự tạo lại; tệp không còn khả dụng; ảnh không xem trước được thì chuyển sang chỉ cho tải về.

Dữ liệu mẫu: De-kiem-tra-Toan-9A-tuan-5.pdf · 1,2 MB · PDF; Hinh-minh-hoa-cau-3.png · 340 KB · ảnh.
```
