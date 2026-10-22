# Bộ prompt — Nhóm chung (mọi vai trò)

Gồm 10 màn (kèm 1 prompt Ghép cuối file) dùng chung cho mọi vai trò: khung điều hướng sau đăng nhập, các màn xác thực (đăng nhập, quên mật khẩu, đặt lại mật khẩu, kích hoạt tài khoản, chọn vai trò), hồ sơ cá nhân, thông báo, trang lỗi và xem/tải tệp đính kèm. Thứ tự vẽ gợi ý: khung điều hướng và đăng nhập trước, rồi các màn xác thực còn lại, hồ sơ, thông báo, trang lỗi, cuối cùng là xem/tải tệp, rồi prompt Ghép để nối các màn thành một luồng liền mạch. Dán từng prompt một; mỗi prompt tự đứng được.

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
| CM-10-G | Ghép: vào ứng dụng và chuyển vai trò | Gộp Đăng nhập, Chọn vai trò, Khung điều hướng, Thông báo, Hồ sơ thành luồng liền mạch |

---

### CM-10 — Khung điều hướng theo vai trò

```text
Màn hình: Khung điều hướng chung sau đăng nhập (cả 4 vai trò: Quản trị viên, Giáo viên, Học sinh, Phụ huynh; responsive: Học sinh/Phụ huynh dùng tốt cả trên điện thoại lẫn trên web máy tính; Quản trị viên/Giáo viên ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Người dùng vừa đăng nhập vào hệ thống quản lý lớp học thêm. Họ muốn đi nhanh tới đúng chức năng của vai trò mình, biết có thông báo mới hay không, và đổi vai trò hoặc đăng xuất ở cùng một chỗ. Học sinh/phụ huynh thường mở bằng điện thoại nhưng cũng hay mở web trên máy tính ở nhà, cần chạm một lần là tới nơi; giáo viên/quản trị viên chủ yếu dùng máy tính và cần thấy toàn bộ chức năng. Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Thông tin cần có:
- Danh mục chức năng thay đổi theo vai trò đang chọn:
  - Quản trị viên: Tổng quan, Tài khoản, Học sinh, Phụ huynh, Giáo viên, Môn học, Lớp học, Lịch, Khóa học, Nhật ký.
  - Giáo viên: Tổng quan, Lớp của tôi, Lịch dạy, Đơn xin phép, Ngân hàng câu hỏi, Bài đánh giá, Khóa học.
  - Học sinh: Tổng quan, Lịch học, Bài tập, Bài kiểm tra, Kết quả, Tiến độ, Khóa học. Ở khổ màn hình hẹp chỉ 4 mục thường dùng hiện sẵn (Tổng quan, Lịch, Bài tập, Kết quả), các mục còn lại gom vào mục "Thêm"; ở khổ rộng thấy đủ mọi mục.
  - Phụ huynh: Chọn con, Tổng quan, Lịch, Điểm danh, Bài tập & kết quả, Nhận xét, Tiến độ, Đơn xin phép. Tên con đang xem phải luôn nhìn thấy.
- Tên màn hình hiện tại.
- Chỉ báo số thông báo chưa đọc (0 thì ẩn, từ 100 trở lên hiện "99+").
- Thông tin người dùng: họ tên (Quản trị viên không có hồ sơ cá nhân thì hiện email đăng nhập), vai trò hiện tại.
- Lối tới: Hồ sơ của tôi, Đổi vai trò (chỉ khi tài khoản có từ 2 vai trò), Đăng xuất.
- Logo trung tâm, bấm vào về trang chủ của vai trò đang chọn.

Hành động: chuyển giữa các chức năng; mở thông báo; mở hồ sơ; đổi vai trò; đăng xuất (không cần xác nhận).

Liên kết:
- Vào từ: ngay sau khi đăng nhập thành công (nếu tài khoản có một vai trò thì vào thẳng trang chủ của vai trò đó; nếu có nhiều vai trò thì đi qua màn Chọn / đổi vai trò trước). Bao quanh mọi màn sau đăng nhập.
- Chọn một chức năng thì mở màn tương ứng của vai trò đang chọn (nếu màn đó đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới).
- Bấm chỉ báo thông báo thì mở nhanh các thông báo mới nhất, hoặc đi tới màn Trung tâm thông báo.
- Bấm thông tin người dùng thì đi tới màn Hồ sơ cá nhân hoặc màn Chọn / đổi vai trò; đăng xuất thì về màn Đăng nhập.
- Bấm logo thì về trang chủ của vai trò đang chọn. Vai trò đang chọn và con đang xem (với phụ huynh) được mang theo mọi màn để người dùng không phải chọn lại.

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
Màn hình: Đăng nhập (mọi vai trò, chưa đăng nhập; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính cho học sinh/phụ huynh, ưu tiên web máy tính cho quản trị viên/giáo viên)

Tình huống: Một phụ huynh (bằng điện thoại hoặc máy tính) hoặc giáo viên mở hệ thống của trung tâm lớp học thêm để xem lịch học, bài tập hoặc điểm danh lớp đầu giờ. Cần vào nhanh, ít thao tác, đọc rõ khi gặp lỗi. Không có chức năng đăng ký tự do: tài khoản chỉ do trung tâm tạo và gửi lời mời.

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

Liên kết:
- Vào từ: địa chỉ gốc khi chưa có phiên; mọi màn khi phiên hết hạn; màn Đặt lại mật khẩu sau khi đặt xong; màn Kích hoạt tài khoản sau khi kích hoạt xong.
- "Quên mật khẩu?" mở màn Quên mật khẩu (nếu màn đó đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới).
- Đăng nhập thành công: tài khoản có một vai trò thì vào thẳng trang chủ của vai trò đó (qua Khung điều hướng); có nhiều vai trò thì mở màn Chọn / đổi vai trò. Nếu bị đưa ra do hết phiên thì quay lại đúng nơi đang làm dở.

Quy tắc nghiệp vụ:
- Sai email, sai mật khẩu hay tài khoản đã bị vô hiệu đều chỉ báo chung một câu, không tiết lộ lý do và không đánh dấu riêng ô nào: "Thông tin đăng nhập không đúng."
- Bỏ trống thì báo ngay tại ô: "Vui lòng nhập email" / "Vui lòng nhập mật khẩu".
- Sai 5 lần trong 60 giây thì bị khóa tạm: "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau {n} giây." Trong lúc khóa, hành động đăng nhập không dùng được và có đếm ngược.
- Khi đang gửi, hành động đăng nhập bị vô hiệu và có chỉ báo đang xử lý.
- Nếu người dùng bị đưa ra do hết phiên, hiện thông báo "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại." và sau khi đăng nhập quay lại đúng nơi đang làm dở.
- Sau khi đặt lại mật khẩu thành công, màn này hiện thông báo "Đã đặt lại mật khẩu, hãy đăng nhập".
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: mặc định; đang gửi; sai thông tin; bị tạm khóa có đếm ngược; mất mạng hoặc lỗi hệ thống ("Có lỗi xảy ra phía hệ thống. Hãy thử lại sau ít phút." kèm thử lại); thành công (đang chuyển trang); hết phiên; vừa đặt lại mật khẩu.

Dữ liệu mẫu: email co.huong@trungtam-pi.vn, mật khẩu ẩn; khóa mẫu: "Thử lại sau 42 giây".
```

---

### CM-02 — Quên mật khẩu

```text
Màn hình: Quên mật khẩu (mọi vai trò, chưa đăng nhập; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

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

Liên kết:
- Vào từ: lối "Quên mật khẩu?" ở màn Đăng nhập; lối xin liên kết mới ở màn Đặt lại mật khẩu và màn Kích hoạt tài khoản lần đầu.
- "Quay lại đăng nhập" mở màn Đăng nhập (nếu màn đó đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới).
- Email nhận được chứa liên kết mở màn Đặt lại mật khẩu; email đã nhập ở đây được mang sang các bước tiếp theo.

Quy tắc nghiệp vụ:
- Hệ thống không bao giờ cho biết email có tồn tại hay không: kết quả luôn là thông điệp trung tính ở trên, dù email đúng hay sai.
- Bỏ trống email: "Vui lòng nhập email".
- Thao tác quá nhanh: "Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút."
- Sau khi gửi, màn chuyển sang trạng thái đã gửi, người dùng ở lại cùng màn.
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: mặc định; đang gửi; đã gửi (thông điệp trung tính, có đếm ngược trước khi gửi lại được); lỗi mạng hoặc lỗi hệ thống ("Có lỗi xảy ra phía hệ thống. Hãy thử lại sau ít phút."); bị giới hạn tần suất.

Dữ liệu mẫu: email phuhuynh.lan@gmail.com; đếm ngược gửi lại "Gửi lại sau 45 giây".
```

---

### CM-03 — Đặt lại mật khẩu

```text
Màn hình: Đặt lại mật khẩu (mọi vai trò, chưa đăng nhập; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

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

Liên kết:
- Vào từ: liên kết trong email "Đặt lại mật khẩu PiClass" (liên kết mang theo email của tài khoản và mã dùng một lần, người dùng không thấy mã).
- Đặt xong thì mở màn Đăng nhập kèm lời nhắn "Đã đặt lại mật khẩu, hãy đăng nhập" (nếu màn đó đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới).
- Liên kết hỏng hoặc hết hạn: lối xin liên kết mới mở màn Quên mật khẩu.

Quy tắc nghiệp vụ:
- Mật khẩu tối thiểu 8 ký tự, không yêu cầu chữ hoa hay số: "Mật khẩu phải có ít nhất 8 ký tự".
- Hai ô mật khẩu phải khớp nhau: "Mật khẩu nhập lại chưa khớp".
- Liên kết hết hạn sau 60 phút và chỉ dùng được một lần: "Liên kết đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu liên kết mới."
- Nếu liên kết thiếu thông tin thì không hiện phần nhập mật khẩu, báo ngay liên kết không hợp lệ.
- Đặt xong, mọi thiết bị đang đăng nhập bằng tài khoản này bị đăng xuất; người dùng không được đăng nhập tự động mà được đưa về trang đăng nhập kèm lời nhắn "Đã đặt lại mật khẩu, hãy đăng nhập".
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: mặc định; đang gửi; thành công (thông điệp ngắn rồi chuyển trang); liên kết không hợp lệ hoặc hết hạn; mật khẩu quá ngắn; hai mật khẩu không khớp; lỗi mạng.

Dữ liệu mẫu: email phuhuynh.lan@gmail.com; mật khẩu mới Matkhau2026.
```

---

### CM-09 — Kích hoạt tài khoản lần đầu

```text
Màn hình: Kích hoạt tài khoản lần đầu (học sinh, phụ huynh hoặc giáo viên vừa được trung tâm mời; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính; thường mở từ email trên điện thoại nhưng cũng có thể mở trên máy tính)

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

Liên kết:
- Vào từ: liên kết trong thư mời của trung tâm (mang theo email và người được mời).
- Kích hoạt xong thì mở màn Đăng nhập (nếu màn đó đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới).
- Lời mời hết hạn thì xin liên kết mới qua màn Quên mật khẩu; tài khoản đã kích hoạt trước đó thì gợi ý đi tới màn Đăng nhập hoặc màn Quên mật khẩu.

Quy tắc nghiệp vụ:
- Mật khẩu tối thiểu 8 ký tự: "Mật khẩu phải có ít nhất 8 ký tự"; hai ô phải khớp: "Mật khẩu nhập lại chưa khớp".
- Chỉ kích hoạt được khi đã đồng ý điều khoản.
- Lời mời chỉ có hiệu lực trong một thời gian và chỉ dùng được một lần.
- Chưa kích hoạt thì không thể đăng nhập.
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: mặc định; đang gửi; thành công ("Tài khoản đã sẵn sàng" kèm lối Đăng nhập); lời mời đã hết hạn (tiêu đề "Lời mời đã hết hạn", giải thích "Liên kết mời chỉ có hiệu lực trong thời gian ngắn", lối "Nhận liên kết mới"); liên kết đã dùng hoặc không hợp lệ ("Liên kết đã hết hạn hoặc không hợp lệ. Vui lòng yêu cầu liên kết mới."); tài khoản đã kích hoạt trước đó (gợi ý Đăng nhập hoặc Quên mật khẩu); lỗi mạng.

Dữ liệu mẫu: email hs.minhanh@gmail.com; tiêu đề "Chào mừng em Nguyễn Minh Anh (Toán 9A)".
```

---

### CM-04 — Chọn / đổi vai trò

```text
Màn hình: Chọn / đổi vai trò (tài khoản có từ 2 vai trò trở lên, thường là Quản trị viên + Giáo viên, hiếm hơn là Giáo viên + Phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Ngay sau khi đăng nhập, hoặc khi đang dùng hệ thống và muốn chuyển, người dùng chọn mình đang vào với tư cách ai. Ví dụ cô Hương vừa là giáo viên vừa là quản trị viên, cần chuyển nhanh giữa việc giảng dạy và việc quản trị. Quan trọng nhất là chọn nhanh bằng một thao tác (chạm hoặc bấm chuột) và không nhầm vai trò.

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

Liên kết:
- Vào từ: ngay sau khi đăng nhập (chỉ khi tài khoản có từ 2 vai trò); hoặc từ thông tin người dùng trong Khung điều hướng và từ màn Hồ sơ cá nhân khi muốn đổi vai trò giữa phiên.
- Chọn một vai trò thì mở trang chủ (tổng quan) của vai trò đó; vai trò mới được dùng cho mọi màn tiếp theo và dữ liệu của vai trò cũ được làm mới hoàn toàn.
- Đăng xuất thì về màn Đăng nhập. Bị đưa về màn này khi vai trò đang dùng vừa bị gỡ.

Quy tắc nghiệp vụ:
- Tài khoản chỉ có một vai trò thì không hiện màn này, hệ thống tự vào.
- Vai trò vừa bị gỡ: "Tài khoản của bạn không còn vai trò này." rồi tự làm mới danh sách.
- Tài khoản chưa được cấp vai trò nào: "Tài khoản chưa được cấp quyền truy cập. Vui lòng liên hệ quản trị viên."
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: đang tải danh sách vai trò; đang đổi vai trò; lỗi khi đổi; không có vai trò nào.

Dữ liệu mẫu: tài khoản của cô Hương có 2 vai trò, Quản trị viên và Giáo viên; "Giáo viên — Lịch dạy, điểm danh, chấm bài" đang được chọn.
```

---

### CM-05 — Hồ sơ cá nhân

```text
Màn hình: Hồ sơ cá nhân, gồm đổi mật khẩu và đăng xuất (Giáo viên, Học sinh, Phụ huynh xem và sửa đủ; Quản trị viên chỉ có phần tài khoản; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính cho học sinh/phụ huynh, ưu tiên web máy tính cho giáo viên/quản trị viên)

Tình huống: Một phụ huynh muốn cập nhật số điện thoại để trung tâm liên hệ được, đổi mật khẩu, hoặc đăng xuất khỏi thiết bị. Cần rõ cái gì sửa được, cái gì không, và an toàn khi đổi mật khẩu.

Thông tin cần có, gồm các nhóm:
- Thông tin cá nhân: họ tên (chỉ đọc, kèm ghi chú "Muốn đổi họ tên, hãy liên hệ trung tâm"), email liên hệ (sửa được), số điện thoại (sửa được), trạng thái hồ sơ theo vai trò đang dùng (chỉ đọc). Phải nói rõ email này khác với email dùng để đăng nhập, đổi email liên hệ không làm đổi cách đăng nhập.
- Tài khoản: email đăng nhập (chỉ đọc), các vai trò đang giữ, vai trò đang hoạt động.
- Đổi mật khẩu: mật khẩu hiện tại, mật khẩu mới (ít nhất 8 ký tự), nhập lại mật khẩu mới.
- Phiên đăng nhập: đăng xuất.

Hành động: lưu thay đổi hồ sơ; đổi mật khẩu; đăng xuất (không cần xác nhận); đổi vai trò (chỉ khi có từ 2 vai trò).

Liên kết:
- Vào từ: thông tin người dùng trong Khung điều hướng ("Hồ sơ của tôi").
- "Đổi vai trò" mở màn Chọn / đổi vai trò (chỉ khi có từ 2 vai trò); "Đăng xuất" về màn Đăng nhập.
- Rời màn khi còn thay đổi chưa lưu thì hỏi xác nhận trước khi đi tiếp.

Quy tắc nghiệp vụ:
- Email liên hệ không hợp lệ: "Email không hợp lệ".
- Mật khẩu hiện tại sai: "Mật khẩu hiện tại không đúng."
- Mật khẩu mới ngắn: "Mật khẩu mới phải có ít nhất 8 ký tự"; nhập lại không khớp: "Mật khẩu nhập lại chưa khớp".
- Trước khi đổi mật khẩu phải cảnh báo "Đổi mật khẩu sẽ đăng xuất các thiết bị khác" và xin xác nhận nhẹ; thiết bị đang dùng vẫn giữ đăng nhập. Đổi xong, xóa sạch các ô mật khẩu.
- Rời màn khi có thay đổi chưa lưu thì hỏi xác nhận.
- Quản trị viên chỉ có vai trò Quản trị viên thì không có phần thông tin cá nhân, thay vào đó có dòng "Tài khoản quản trị không có hồ sơ cá nhân". Nếu đồng thời là Giáo viên thì thấy đầy đủ như giáo viên.
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: đang tải; đang lưu; lưu thành công (thông báo ngắn); lỗi theo từng ô; mật khẩu hiện tại sai; tài khoản quản trị không có hồ sơ; lỗi mạng.

Dữ liệu mẫu: Nguyễn Thị Lan · lan.nguyen@gmail.com · 0912 345 678 · vai trò Phụ huynh đang hoạt động · hồ sơ "Đang hoạt động"; email đăng nhập phuhuynh.lan@gmail.com.
```

---

### CM-06 — Trung tâm thông báo

```text
Màn hình: Trung tâm thông báo (mọi vai trò; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính cho học sinh/phụ huynh, ưu tiên web máy tính cho quản trị viên/giáo viên)

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

Liên kết:
- Vào từ: chỉ báo thông báo trong Khung điều hướng (mở nhanh vài thông báo mới nhất, hoặc "Xem tất cả" để mở màn này).
- Mở một thông báo thì đánh dấu đã đọc rồi đi tới nội dung liên quan; nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới:
  - Bài mới giao: học sinh mở màn Chi tiết bài của học sinh; phụ huynh mở màn Chi tiết bài của con (mang theo con đang xem; nếu không xác định được con thì về danh sách bài của con).
  - Kết quả được công bố: học sinh mở màn Kết quả của học sinh; phụ huynh mở màn Kết quả của con; mở đến danh sách kết quả để người dùng chọn bài.
  - Buổi học đổi hoặc hủy: mở màn Chi tiết buổi học của đúng vai trò đang dùng (giáo viên, học sinh hoặc phụ huynh); buổi đã hủy hiện trạng thái "đã hủy".
  - Lịch thay đổi: mở màn Lịch của vai trò đang dùng ở tuần hiện tại.
  - Đơn xin phép mới gửi (giáo viên): mở màn Đơn xin phép với bộ lọc đang chờ duyệt, hoặc màn Chi tiết buổi học của buổi đó.
  - Đơn xin phép đã được xét (học sinh/phụ huynh): mở màn Đơn xin phép của con, thấy trạng thái "Đã chấp nhận" hoặc "Đã từ chối".
- Thông báo không có nội dung liên quan thì chỉ để đọc, không đi đâu. Nếu người dùng không còn quyền xem đích thì mở Trang lỗi với lời nhắn "Không tìm thấy nội dung hoặc bạn không có quyền xem."
- Thông tin được mang theo: bài, kết quả, buổi học hoặc lịch của thông báo, và con đang xem với phụ huynh, để người dùng không phải chọn lại.

Quy tắc nghiệp vụ:
- Đích đến theo loại thông báo: bài mới giao tới chi tiết bài; kết quả được công bố tới danh sách kết quả; buổi học đổi hoặc hủy tới chi tiết buổi học (buổi đã hủy hiện trạng thái "đã hủy"); lịch đổi tới màn lịch tuần hiện tại; đơn xin phép mới gửi (giáo viên nhận) tới đơn đang chờ duyệt; đơn xin phép đã được xét (học sinh/phụ huynh nhận) tới trạng thái đơn, ghi bằng tiếng Việt "Đã chấp nhận" hoặc "Đã từ chối".
- Có thông báo không dẫn đi đâu, chỉ để đọc. Loại thông báo lạ vẫn hiện bình thường nhưng không điều hướng.
- Nếu người dùng không còn quyền xem nội dung đích, hiện: "Không tìm thấy nội dung hoặc bạn không có quyền xem."
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: đang tải; lọc "Chưa đọc" mà hết thông báo ("Bạn đã xem hết thông báo"); chưa có thông báo nào ("Chưa có thông báo. Khi có bài mới, đổi lịch hoặc kết quả, bạn sẽ thấy ở đây"); lỗi tải có thử lại; đang đánh dấu đã đọc; nội dung đích không còn khả dụng.

Dữ liệu mẫu:
- "Bạn được giao bài mới" — "Có một bài kiểm tra/bài tập mới được giao cho bạn." — hôm nay 16:05, chưa đọc.
- "Buổi học đã bị hủy" — "Một buổi học của lớp bạn tham gia vừa bị hủy." — hôm qua, chưa đọc.
- "Kết quả bài làm đã được công bố" — 3 ngày trước, đã đọc.
```

---

### CM-07 — Trang lỗi

```text
Màn hình: Trang lỗi (mọi vai trò; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

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

Liên kết:
- Vào từ: bất kỳ màn nào khi gặp sự cố ở mức cả màn hình (hết phiên, không tìm thấy hoặc không có quyền, sai vai trò, tài khoản bị vô hiệu, lỗi hệ thống, thao tác quá nhanh, đường dẫn không tồn tại); và từ màn Trung tâm thông báo khi nội dung đích không còn khả dụng.
- Lối ra: "Đăng nhập" mở màn Đăng nhập (sau đó quay lại nơi đang làm dở); "Về trang chủ" mở trang chủ của vai trò đang chọn; "Đổi vai trò" mở màn Chọn / đổi vai trò; "Đăng xuất" về màn Đăng nhập; "Thử lại" tải lại màn vừa gặp lỗi. Màn gặp lỗi được ghi nhớ để quay lại khi thử lại hoặc đăng nhập lại (nếu các màn này đã có trong dự án, hãy nối tới chúng; nếu chưa, để điểm dẫn tới).

Quy tắc nghiệp vụ: "Về trang chủ" luôn dẫn tới trang chủ của vai trò đang chọn. Các biến thể phải khác nhau rõ về nội dung và lối ra.

Dữ liệu mẫu: mã nhỏ "Mã hỗ trợ: 404-17A2 · 22/10/2026 17:41".
```

---

### CM-08 — Xem / tải tệp đính kèm

```text
Màn hình: Xem / tải tệp đính kèm (thành phần xuất hiện bên trong các màn khác như chi tiết bài tập/bài kiểm tra, câu hỏi, soạn bài; dùng cho Quản trị viên, Giáo viên, Học sinh, Phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính cho học sinh/phụ huynh, ưu tiên web máy tính cho quản trị viên/giáo viên)

Tình huống: Học sinh hoặc phụ huynh muốn mở đề bài PDF hoặc xem hình minh họa của một câu hỏi; giáo viên kiểm tra tệp đã đính kèm. Không có trang "kho tệp" riêng. Cần nhận ra ngay đó là tệp gì, nặng bao nhiêu, và mở được chỉ với một thao tác (chạm hoặc bấm chuột).

Thông tin cần có cho mỗi tệp: biểu tượng theo loại tệp, tên tệp, dung lượng (đổi sang KB hoặc MB), loại tệp.

Hành động: bấm vào tệp để mở hoặc tải:
- Ảnh (jpg, png): xem trước ngay trong ứng dụng, không rời màn.
- PDF: mở ra xem.
- Word (doc, docx) và zip: tải về.

Liên kết:
- Vào từ: nằm bên trong các màn khác, không phải màn riêng: chi tiết bài tập/bài kiểm tra của học sinh và của con (phụ huynh), câu hỏi khi làm bài, màn soạn câu hỏi và soạn bài của giáo viên. Không có trang kho tệp.
- Bấm một tệp thì xem ngay hoặc tải về, người dùng vẫn ở lại màn đang xem (đối tượng đang xem như bài hay câu hỏi được giữ nguyên).
- Tệp không dùng được thì hiện lời nhắn "Không tìm thấy tệp hoặc bạn không có quyền xem" ngay tại chỗ, không chuyển sang Trang lỗi.

Quy tắc nghiệp vụ:
- Liên kết tải được tạo vào đúng lúc bấm và chỉ dùng được trong 10 phút; hết hạn thì tự tạo lại một lần, kèm lời nhắn "Liên kết tải đã hết hạn, đang tạo lại…".
- Học sinh và phụ huynh chỉ thấy tệp mà họ được phép xem, và chỉ khi bài đó đã giao cho học sinh và đã đến giờ mở; tệp không được phép xem thì không hiện, không báo là bị ẩn. Có tệp chỉ học sinh xem được, phụ huynh không xem được.
- Tệp chưa tải lên xong hoặc bị lỗi không được hiện.
- Học sinh và phụ huynh chỉ xem và tải, không tải lên hay xóa tệp.
- Tệp không tìm thấy hoặc không có quyền xem: "Không tìm thấy tệp hoặc bạn không có quyền xem".
- Giới hạn khi tải lên (nhắc người soạn bài): tối đa 10 MB; định dạng pdf, doc/docx, jpg, png, zip.
- Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Tình huống đặc biệt: tệp bình thường; đang lấy liên kết; tải thất bại (có thử lại); liên kết hết hạn đang tự tạo lại; tệp không còn khả dụng; ảnh không xem trước được thì chuyển sang chỉ cho tải về.

Dữ liệu mẫu: De-kiem-tra-Toan-9A-tuan-5.pdf · 1,2 MB · PDF; Hinh-minh-hoa-cau-3.png · 340 KB · ảnh.
```

---

### CM-10-G — Ghép: vào ứng dụng và chuyển vai trò

```text
Ghép: vào ứng dụng và chuyển vai trò (mọi vai trò; responsive: Học sinh/Phụ huynh dùng tốt cả trên điện thoại lẫn trên web máy tính; Quản trị viên/Giáo viên ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Yêu cầu: hãy gộp các màn có tên Đăng nhập, Chọn / đổi vai trò, Khung điều hướng theo vai trò, Trung tâm thông báo và Hồ sơ cá nhân (đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có) thành MỘT trải nghiệm vào và dùng ứng dụng liền mạch, không phải các màn rời nhau. Không chỉ định cách thể hiện; hãy tự quyết định bố cục, chỉ bảo đảm các điểm sau.

Đối tượng chung: một phiên đăng nhập của một người dùng với một vai trò đang chọn (và, với phụ huynh, một người con đang xem).

Luồng liền mạch: Đăng nhập; nếu tài khoản có một vai trò thì vào thẳng trang chủ của vai trò đó, nếu có từ 2 vai trò thì qua bước Chọn vai trò rồi vào trang chủ vai trò đã chọn. Sau đó Khung điều hướng bao quanh mọi màn; từ đó mở Trung tâm thông báo, mở Hồ sơ cá nhân, đổi vai trò (quay lại Chọn vai trò) hoặc đăng xuất (về Đăng nhập).

Dùng chung: vai trò đang chọn và danh mục chức năng của nó; họ tên và vai trò hiện tại; chỉ báo số thông báo chưa đọc (cùng một con số ở khung điều hướng và ở Trung tâm thông báo, đọc xong thì giảm ngay); lời nhắn toàn cục về mất mạng và phiên sắp hết hạn; đăng xuất.

Được giữ lại: nơi người dùng đang làm dở khi bị đưa ra do hết phiên (đăng nhập lại thì quay về đúng đó); con đang xem của phụ huynh; bộ lọc đọc/chưa đọc của thông báo khi mở rồi quay lại; thay đổi chưa lưu ở Hồ sơ cá nhân (hỏi xác nhận khi rời). Khi đổi vai trò thì dữ liệu của vai trò cũ được xóa sạch và về trang chủ vai trò mới.

Hiện/ẩn: Chọn vai trò và lối "Đổi vai trò" chỉ có khi tài khoản có từ 2 vai trò; Hồ sơ cá nhân của quản trị viên chỉ có phần tài khoản; vai trò vừa bị gỡ thì đưa về Chọn vai trò; phụ huynh chưa có con liên kết chỉ còn "Chọn con".

Việc chuyển giữa các phần không làm người dùng mất ngữ cảnh: luôn biết mình đang là vai trò nào và quay về trang chủ được bằng một thao tác. Phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Liên kết: Đăng nhập → (Chọn vai trò) → trang chủ vai trò; Khung điều hướng → Trung tâm thông báo / Hồ sơ cá nhân / Chọn vai trò / Đăng nhập; Trung tâm thông báo → màn nội dung liên quan (nếu có trong dự án).
```
