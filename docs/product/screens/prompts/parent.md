# Bộ prompt — Cổng Phụ huynh

Gồm 9 prompt cho các màn hình của phụ huynh, dùng tốt cả trên điện thoại lẫn web máy tính. Thứ tự vẽ gợi ý: chọn con, tổng quan, lịch học của con, điểm danh của con, bài và kết quả, nhận xét, tiến độ, đơn xin phép, rồi prompt Ghép gộp tất cả thành một chức năng theo dõi con. Phụ huynh chỉ xem và gửi đơn xin phép; không làm bài, không thấy bài làm riêng của con, không thấy bạn cùng lớp. Mỗi prompt tự đứng được, có thể dán ở phiên riêng; prompt Ghép dán sau khi đã vẽ các màn của nhóm.

| Mã | Màn hình | Ghi chú |
|---|---|---|
| PR-01 | Chọn con | Chỉ hiện khi có từ hai con |
| PR-02 | Tổng quan của con | Màn mở đầu mỗi ngày |
| PR-03 | Lịch học của con | Gồm chi tiết một buổi học |
| PR-04 | Điểm danh của con | Chuyên cần và các buổi vắng |
| PR-05 | Bài và kết quả của con | Chỉ điểm và nhận xét đã công bố |
| PR-06 | Nhận xét | Nhận xét giáo viên đã công bố cho phụ huynh |
| PR-07 | Tiến độ của con | Điểm trung bình và theo môn |
| PR-08 | Đơn xin phép | Gửi đơn và theo dõi đơn đã gửi |
| PR-03-G | Ghép: Theo dõi con | Chọn con ở một nơi, mọi màn theo con đó, đổi con thì mọi màn đổi theo |

---

### PR-01 — Chọn con

```text
Màn hình: Chọn con (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh có từ hai con học ở trung tâm, vừa đăng nhập hoặc muốn đổi sang xem con khác. Họ thường mở web PiClass giữa giờ làm việc (bằng điện thoại hoặc web máy tính) và chỉ muốn chọn đúng con trong một thao tác. Quan trọng nhất là không nhầm con.

Thông tin cần có (mỗi con):
- Họ tên đầy đủ.
- Quan hệ của phụ huynh với con (bố, mẹ, người giám hộ).

Hành động:
- Chọn một con để xem; sau đó vào thẳng tổng quan của con đó.
- Con vừa chọn được nhớ cho lần sau.
- Không có thêm hay xóa con; việc liên kết do trung tâm quản lý.

Liên kết:
- Đến màn này từ: ngay sau khi đăng nhập (chỉ khi có từ hai con); từ tên con đang xem ở mọi màn của phụ huynh khi muốn đổi con; khi vừa mất liên kết với con đang xem.
- Chọn một con thì sang màn Tổng quan của con đó. Con vừa chọn được nhớ cho lần sau; mọi màn sau đó đều theo con này cho đến khi đổi con.
- Phụ huynh chỉ có một con thì bỏ qua màn này, vào thẳng màn Tổng quan của con.
- Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Chỉ hiện những con còn liên kết với tài khoản phụ huynh.
- Phụ huynh chỉ có một con thì không cần màn này, vào thẳng tổng quan.
- Khi liên kết với một con kết thúc, con đó biến mất khỏi danh sách ngay. Nếu phụ huynh đang xem con đó thì báo "Bạn không còn liên kết với học sinh này" rồi đưa về màn hình chọn con (hoặc màn hình trống nếu không còn con nào).

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Tài khoản của bạn chưa được liên kết với học sinh nào. Liên hệ trung tâm để được liên kết" (không có hành động tự thêm).
- Lỗi tải: cho thử lại.
- Vừa mất liên kết với một con.

Dữ liệu mẫu:
- Nguyễn Minh Anh — con gái
- Nguyễn Gia Bảo — con trai
```

### PR-02 — Tổng quan của con

```text
Màn hình: Tổng quan của con (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh bận rộn mở web PiClass, bằng điện thoại hoặc web máy tính, và muốn trả lời ngay: hôm nay con có học không, có bài nào sắp đến hạn không, có kết quả hoặc nhận xét mới của cô không. Hệ thống chỉ có thông báo trong web nên đây là nơi phụ huynh biết "có gì mới". Quan trọng nhất là nhanh và nhìn là hiểu.

Thông tin cần có:
- Tên con đang xem (và cách đổi sang con khác nếu có nhiều con).
- Hôm nay con học gì: giờ, tên lớp, môn, trạng thái buổi (Sắp diễn ra, Đã học, Đã hủy, có đổi giờ không).
- Bài sắp đến hạn của con: tên bài, loại, hạn tuyệt đối kèm tương đối, con đã làm chưa.
- Kết quả mới được công bố: tên bài, điểm.
- Nhận xét mới nhất của giáo viên đã công bố cho phụ huynh.
- Con vắng buổi gần đây (nếu có), kèm tình trạng đơn xin phép.

Hành động:
- Đi tới lịch học, bài và kết quả, nhận xét, tiến độ của con.
- Gửi đơn xin phép cho một buổi sắp tới ngay từ đây.
- Mở thông báo.
- Không có hành động làm bài hay chỉnh sửa gì thay con.

Liên kết:
- Đến màn này từ: trang đầu sau đăng nhập (sau màn Chọn con nếu có nhiều con); từ thông báo.
- Tên con đang xem mở màn Chọn con để đổi sang con khác.
- Đi tới: Lịch học của con, Bài và kết quả của con (bấm một bài thì mở đúng bài đó), Nhận xét (bấm nhận xét mới nhất thì mở đúng nhận xét), Tiến độ của con, Đơn xin phép (từ một buổi sắp tới hoặc buổi con vừa vắng, mang theo buổi đó).
- Mọi nơi đến đều mang theo con đang xem; không phải chọn lại con.
- Nếu màn đích chưa có trong dự án, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Chỉ hiện thông tin của con đang chọn; không hiện bạn cùng lớp.
- Chỉ hiện điểm và nhận xét đã được giáo viên công bố; không hiện bài làm của con.
- Buổi bị hủy phải nhìn ra là đã hủy ("Đã hủy").
- Khi mất liên kết với con: "Bạn không còn liên kết với học sinh này" và đưa về chọn con.

Tình huống đặc biệt:
- Đang tải: từng nhóm tải riêng.
- Hôm nay không có buổi học: "Hôm nay con không có buổi học".
- Chưa có bài đến hạn: "Con chưa có bài nào sắp đến hạn".
- Chưa có kết quả hay nhận xét mới.
- Một nhóm lỗi tải: thử lại riêng nhóm đó, các nhóm khác vẫn dùng được.
- Mất quyền xem con.

Dữ liệu mẫu:
- "Hôm nay Minh Anh có 1 buổi: Toán 9A 17:30–19:00"
- "Bài Hàm số bậc nhất — hạn Thứ Sáu 23:59, chưa làm"
- "Cô vừa nhận xét bài Căn bậc hai"
```

### PR-03 — Lịch học của con

```text
Màn hình: Lịch học của con (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh muốn biết tuần này con học khi nào, buổi nào đổi giờ hoặc bị hủy để sắp xếp đưa đón, và xem con có mặt hay vắng ở các buổi đã qua. Nếu con sắp nghỉ, họ muốn gửi đơn xin phép ngay từ buổi đó. Quan trọng nhất là đọc nhanh, không nhầm giờ, không nhầm con.

Thông tin cần có:
- Tên con đang xem; kỳ đang xem (tuần hoặc tháng).
- Các buổi học theo ngày: giờ bắt đầu–kết thúc, tên lớp, môn, trạng thái (Sắp diễn ra, Đang diễn ra, Đã học, Đã hủy).
- Khi mở một buổi: nội dung buổi học; điểm danh của con ở buổi đó (Có mặt, Đi muộn, Vắng, Có phép, kèm ghi chú); tình trạng đơn xin phép của buổi (nếu có).

Hành động:
- Chuyển giữa xem theo tuần và theo tháng; đổi kỳ.
- Mở một buổi xem chi tiết.
- Gửi đơn xin phép cho buổi sắp diễn ra, hoặc cho buổi con vừa vắng. Không gửi cho buổi đã hủy.
- Không sửa lịch, không sửa điểm danh.

Liên kết:
- Đến màn này từ: mục Lịch học của con; từ màn Tổng quan của con; từ thông báo về lịch học hoặc buổi học.
- Bấm một buổi thì mở chi tiết buổi đó ngay trong màn này, không mất kỳ đang xem.
- Từ chi tiết buổi, "Gửi đơn xin phép" mở màn Đơn xin phép với buổi đó đã chọn sẵn (chỉ cho buổi sắp diễn ra hoặc buổi con vừa vắng, không cho buổi đã hủy).
- Con đang xem được mang theo khi chuyển màn; không phải chọn lại con.
- Nếu màn đích chưa có trong dự án, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Buổi bị hủy vẫn hiện, phải nhìn ra là đã hủy ("Đã hủy").
- Buổi đang diễn ra phải nổi bật.
- Chỉ thấy điểm danh của con, tuyệt đối không thấy bạn cùng lớp.
- Nếu chưa có thông tin giáo viên hay phòng học thì không bịa ra.
- Khi nhiều con học nhiều lớp, mỗi buổi phải thể hiện rõ thuộc về con nào đang xem.
- Mất liên kết với con: "Bạn không còn liên kết với học sinh này" và đưa về chọn con.

Tình huống đặc biệt:
- Đang tải.
- Không có buổi nào: "Tuần này con không có buổi học".
- Lỗi tải: thử lại.
- Buổi đã hủy; buổi đang diễn ra; buổi chưa có điểm danh ("Chưa điểm danh").
- Không tìm thấy buổi học: "Không tìm thấy buổi học này".

Dữ liệu mẫu:
- Thứ Ba 21/10 · 17:30–19:00 Toán 9A · con có mặt
- Thứ Năm 23/10 · 17:30–19:00 Văn 9A · Đã hủy
- Thứ Bảy 25/10 · 08:00–09:30 Toán 9A · Sắp diễn ra
```

### PR-04 — Điểm danh của con

```text
Màn hình: Điểm danh của con (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh muốn biết con đi học đều không, buổi nào con vắng, và nếu vắng có lý do thì giải trình với giáo viên. Đây là việc phụ huynh thường làm lúc rảnh buổi tối. Quan trọng nhất là thấy ngay buổi nào vắng chưa có đơn để xử lý.

Thông tin cần có:
- Tên con đang xem.
- Tỷ lệ chuyên cần (có mặt, đi muộn và có phép so với tổng số buổi đã điểm danh).
- Các buổi gần nhất: ngày, lớp, môn, trạng thái (Có mặt, Đi muộn, Vắng, Có phép), ghi chú của giáo viên.
- Với buổi vắng: nổi bật hơn, kèm tình trạng đơn xin phép (chưa có đơn, Đã gửi, Đang xem xét, Đã chấp nhận, Bị từ chối).

Hành động:
- Mở một buổi để xem chi tiết.
- Gửi đơn giải trình cho buổi vắng chưa có đơn; buổi có đơn bị từ chối có thể gửi lại.
- Không có hành động sửa điểm danh.

Liên kết:
- Đến màn này từ: mục Điểm danh của con; từ màn Tổng quan của con.
- Bấm một buổi thì xem chi tiết buổi đó ngay trong màn này.
- Buổi vắng chưa có đơn (hoặc có đơn bị từ chối): "Gửi đơn giải trình" mở màn Đơn xin phép với buổi vắng đó đã chọn sẵn.
- Con đang xem được mang theo khi chuyển màn.
- Nếu màn đích chưa có trong dự án, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Đơn của phụ huynh chỉ là yêu cầu để giáo viên xem xét và không tự đổi điểm danh. Phải nói rõ trên màn: "Đơn của bạn là yêu cầu để giáo viên xem xét, không tự đổi điểm danh."
- Buổi đã hủy không tính vào chuyên cần.
- Chỉ thấy điểm danh của con, không thấy bạn cùng lớp.
- Mất liên kết với con: "Bạn không còn liên kết với học sinh này" và đưa về chọn con.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Con chưa có buổi nào được điểm danh".
- Lỗi tải: thử lại.
- Con không vắng buổi nào; có buổi vắng chưa có đơn; đơn bị từ chối.

Dữ liệu mẫu:
- Chuyên cần 90% (9/10 buổi)
- Thứ Hai 13/10 Toán 9A: Vắng — chưa có đơn
- Thứ Tư 15/10 Văn 9A: Có phép, ghi chú "Gia đình báo trước"
```

### PR-05 — Bài và kết quả của con

```text
Màn hình: Bài và kết quả của con (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh muốn biết con có những bài tập, bài kiểm tra, bài thi nào, con đã nộp chưa và kết quả ra sao. Họ chỉ quan sát và nhắc con, không làm bài thay. Quan trọng nhất là biết nhanh bài nào cần nhắc và bài nào đã có điểm.

Thông tin cần có:
- Tên con đang xem.
- Mỗi bài của con: tên bài, loại (bài tập, kiểm tra, thi), hạn nộp hoặc khung giờ mở–đóng, điểm tối đa, trạng thái (Chưa làm, Chờ chấm, Đã có điểm, Đã đóng, Đã hủy).
- Chi tiết một bài: các thông tin chung của bài nói trên.
- Kết quả (chỉ khi đã công bố): điểm trên điểm tối đa, phần trăm, Đạt hoặc Chưa đạt; điểm các lượt; nhận xét của giáo viên dành cho học sinh và cho phụ huynh.

Hành động:
- Lọc theo loại: Bài tập, Kiểm tra, Thi.
- Mở một bài; mở kết quả khi có.
- Không có làm bài, nộp bài hay sửa gì.

Liên kết:
- Đến màn này từ: mục Bài tập của con; từ màn Tổng quan của con; từ thông báo về bài hoặc kết quả; từ màn Nhận xét (bấm một nhận xét thì mở đúng bài tương ứng).
- Bấm một bài thì mở chi tiết bài của con ngay trong màn này; từ đó mở kết quả khi đã công bố.
- Con đang xem được mang theo khi chuyển màn; không phải chọn lại con. Khi quay lại phải giữ bộ lọc loại bài.
- Nếu màn nối tới chưa có trong dự án, để một điểm dẫn tới.

Quy tắc nghiệp vụ (quyền riêng tư):
- Phụ huynh không thấy câu hỏi, câu trả lời hay bài làm riêng của con, không có đồng hồ, không biết con đã dùng mấy lượt. Chỉ thấy điểm và nhận xét.
- Chỉ thấy kết quả khi giáo viên đã công bố và liên kết với con còn hiệu lực. Chưa công bố thì không hiện điểm.
- Ghi chú nội bộ của giáo viên không bao giờ hiện cho phụ huynh.
- Mất liên kết với con: "Bạn không còn liên kết với học sinh này" và đưa về chọn con.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Con chưa có bài nào".
- Lỗi tải: thử lại.
- Kết quả chưa công bố: "Chưa có kết quả, cô sẽ công bố sau".
- Bài đã hủy; bài đã đóng con không làm; bài không giao cho con ("Không tìm thấy bài này").

Dữ liệu mẫu:
- "Bài tập Hàm số bậc nhất" · hạn Thứ Sáu 24/10 · Chưa làm
- "Kiểm tra 15 phút — Căn bậc hai" · 8,5/10 · Đạt
- "Thi giữa kỳ I — Toán 9" · Thứ Bảy 01/11 08:00–09:30 · Chưa làm
```

### PR-06 — Nhận xét

```text
Màn hình: Nhận xét của giáo viên (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh muốn đọc giáo viên nhận xét gì về con. Đây là kênh chính để biết tình hình học vì hệ thống không có tin nhắn trao đổi. Họ đọc kỹ, đọc lại nhiều lần, nên cần chữ dễ đọc và biết nhận xét thuộc bài nào, ngày nào.

Thông tin cần có:
- Tên con đang xem.
- Các nhận xét đã công bố, mới nhất trước. Mỗi nhận xét gồm: tên bài, ngày nhận xét, nội dung, nhãn "Nhận xét chung" hoặc "Về một câu hỏi trong bài".
- Cách mở bài tương ứng để xem điểm.

Hành động:
- Đọc nhận xét; mở bài tương ứng.
- Không có trả lời hay nhắn lại giáo viên.

Liên kết:
- Đến màn này từ: mục Nhận xét; từ màn Tổng quan của con (nhận xét mới nhất); từ thông báo về kết quả.
- Bấm một nhận xét thì mở màn Bài và kết quả của con tại đúng bài tương ứng (nếu màn này đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới).
- Con đang xem được mang theo khi chuyển màn.

Quy tắc nghiệp vụ (quyền riêng tư):
- Chỉ hiện nhận xét giáo viên đã công bố cho học sinh hoặc cho phụ huynh. Ghi chú nội bộ của giáo viên không bao giờ hiện.
- Nhận xét nháp hoặc đã bị rút lại thì biến mất, không để lại dấu vết.
- Nhận xét về một câu hỏi chỉ nói "Về một câu hỏi trong bài", không hiện nội dung câu hỏi vì phụ huynh không xem đề và bài làm.
- Mất liên kết với con: "Bạn không còn liên kết với học sinh này" và đưa về chọn con.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Chưa có nhận xét nào từ giáo viên".
- Lỗi tải: thử lại.
- Nhận xét vừa bị rút (biến mất).
- Nhận xét dài.

Dữ liệu mẫu:
- "Kiểm tra 15 phút — Căn bậc hai · 22/10 · Nhận xét chung: Minh Anh làm tốt phần căn bậc hai, cần chú ý bước đặt điều kiện."
- "Bài tập Hàm số bậc nhất · 18/10 · Về một câu hỏi trong bài: Con cần trình bày rõ hơn bước tìm hệ số góc."
```

### PR-07 — Tiến độ của con

```text
Màn hình: Tiến độ của con (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh muốn biết điểm trung bình của con, môn nào con mạnh và môn nào yếu để có định hướng nhắc nhở hoặc cho con học thêm. Họ xem qua nhanh, cần con số rõ và dễ hiểu, không cần đi sâu từng bài.

Thông tin cần có:
- Tên con đang xem.
- Điểm trung bình chung (phần trăm).
- Số bài đã có điểm; số bài đang chờ chấm hoặc chờ công bố.
- Theo từng môn: tên môn, điểm trung bình, số bài đã có điểm. Có nhóm "Khác" cho bài không gắn với lớp hay khóa nào.
- Điểm theo thời gian (xu hướng), tỷ lệ chuyên cần.

Hành động:
- Đổi khoảng thời gian và môn muốn xem.
- Chọn một môn để xem các bài của môn đó.

Liên kết:
- Đến màn này từ: mục Tiến độ của con; từ màn Tổng quan của con.
- Chọn một môn thì xem các bài của môn đó trong màn Bài và kết quả của con (mang theo môn đã chọn và khoảng thời gian đang xem).
- Con đang xem được mang theo khi chuyển màn.
- Nếu màn đích chưa có trong dự án, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Mỗi bài chỉ tính một lần dù con làm nhiều lượt.
- Bài chưa công bố hoặc đã hủy không tính vào điểm trung bình.
- Chưa có bài nào được công bố thì điểm trung bình ghi "Chưa có điểm".
- Chỉ có một bài thì không vẽ xu hướng.
- Không hiện so sánh với bạn cùng lớp.
- Mất liên kết với con: "Bạn không còn liên kết với học sinh này" và đưa về chọn con.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Con chưa có bài nào được chấm. Điểm sẽ hiện khi cô công bố".
- Lỗi tải: thử lại.
- Chỉ một bài; có bài chờ chấm; bộ lọc cho kết quả rỗng; mất quyền xem con.

Dữ liệu mẫu:
- Điểm trung bình 78,5%
- Toán 82% (4 bài); Văn 72% (2 bài)
- 1 bài đang chờ chấm
```

### PR-08 — Đơn xin phép

```text
Màn hình: Đơn xin phép nghỉ / giải trình vắng (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh cần báo trước việc con sắp nghỉ một buổi, hoặc giải trình sau khi con đã vắng, và muốn biết giáo viên đã chấp nhận chưa. Họ thường làm việc này nhanh khi đang bận, bằng điện thoại hoặc máy tính, nên quan trọng nhất là ít thao tác, không mất nội dung đã gõ, và biết chắc đơn đã gửi.

Thông tin cần có:
- Các đơn đã gửi cho con đang chọn: buổi học (ngày giờ, lớp, môn), lý do đã viết, trạng thái (Đã gửi, Đang xem xét, Đã chấp nhận, Bị từ chối).
- Để gửi đơn mới: con (đã cố định theo con đang xem), buổi học muốn xin nghỉ (ưu tiên buổi sắp tới và buổi vừa qua), lý do (bắt buộc, nhiều dòng).
- Lời giải thích: "Đơn là yêu cầu để giáo viên xem xét, không tự đổi điểm danh."

Hành động:
- Chọn buổi, nhập lý do, gửi đơn.
- Xem lại các đơn đã gửi theo trạng thái.
- Không sửa hay thu hồi đơn đã gửi.
- Không có thao tác duyệt đơn (đó là việc của giáo viên).

Liên kết:
- Đến màn này từ: mục Đơn xin phép; từ màn Lịch học của con (gửi đơn cho một buổi, mang theo buổi đó); từ màn Điểm danh của con (giải trình một buổi vắng, mang theo buổi đó); từ màn Tổng quan của con.
- Gửi xong thì quay lại danh sách đơn của con, đơn mới ở đầu; có thể quay về màn Lịch học hoặc Điểm danh nơi đã bắt đầu.
- Con đang xem được mang theo khi chuyển màn; không phải chọn lại con.
- Nếu màn đích chưa có trong dự án, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Đơn không tự đổi điểm danh. Với đơn được chấp nhận ghi: "Giáo viên đã chấp nhận; điểm danh sẽ do giáo viên cập nhật".
- Lý do bắt buộc; để trống thì báo ngay dưới ô: "Vui lòng nhập lý do". Chưa chọn buổi: "Vui lòng chọn buổi học".
- Chọn buổi không thuộc lớp của con: "Buổi này không thuộc lớp của con. Vui lòng chọn buổi khác."
- Gửi thành công: "Đã gửi đơn. Giáo viên sẽ xem xét", đơn mới hiện đầu danh sách.
- Gửi thất bại: báo lỗi và giữ nguyên nội dung đã gõ.
- Mất liên kết với con: "Bạn không còn liên kết với học sinh này" và đưa về chọn con.
- Theo quy tắc co giãn của web responsive trong prompt nền, dùng tốt ở khổ hẹp lẫn rộng; không để mất chức năng nào khi thu nhỏ. Bàn phím ảo (khi có) không được che ô viết lý do.
- Chỉ phụ huynh gửi đơn; học sinh không thấy và không gửi được.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Chưa có đơn nào", kèm hành động gửi đơn.
- Lỗi tải: thử lại.
- Đang gửi; gửi thành công; gửi thất bại.
- Đơn ở từng trạng thái; buổi đã hủy.

Dữ liệu mẫu:
- Toán 9A, Thứ Ba 21/10 17:30 — "Con bị sốt, xin phép nghỉ" — Đã chấp nhận
- Văn 9A, Thứ Năm 23/10 — "Gia đình có việc" — Đã gửi
```

### PR-03-G — Ghép: Theo dõi con

```text
Màn hình: Ghép: Theo dõi con (phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Tình huống: Phụ huynh có một hoặc nhiều con và theo dõi con ở nhiều mặt: lịch học, điểm danh, bài và kết quả, nhận xét, tiến độ, đơn xin phép. Với họ đây là MỘT việc: "đang xem con nào, thì mọi thứ là của con đó". Điều tối quan trọng là không bao giờ nhầm con.

Yêu cầu: hãy gộp các màn Chọn con, Tổng quan của con, Lịch học của con, Điểm danh của con, Bài và kết quả của con, Nhận xét, Tiến độ của con và Đơn xin phép (đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có) thành một chức năng liền mạch của phụ huynh, xoay quanh một đối tượng chung: con đang xem.

Dùng chung: tên con đang xem luôn thấy được ở mọi màn, cách đổi con, thông báo, thông điệp "Bạn không còn liên kết với học sinh này".

Con được chọn ở MỘT nơi duy nhất. Mọi màn sau đó đều tự theo con đó, không bao giờ hỏi lại. Khi đổi con, TẤT CẢ các màn đổi theo con mới cùng lúc: lịch, điểm danh, bài, nhận xét, tiến độ, đơn xin phép đều chuyển sang dữ liệu của con mới, không để sót dữ liệu của con cũ. Đổi con thì quay về màn đang xem (cùng loại màn) của con mới, không văng về đầu. Mở lại liên kết, quay lại hay tải lại vẫn đúng con. Con vừa chọn được nhớ cho lần sau.

Giữ lại khi chuyển giữa các màn: con đang xem, kỳ lịch đang xem, bộ lọc, buổi hoặc bài đang chọn, nội dung đơn đang viết dở.

Điều kiện: phụ huynh một con thì ẩn phần chọn con, chỉ hiện tên con. Mất liên kết với con đang xem thì mọi màn báo "Bạn không còn liên kết với học sinh này" và về chọn con (hoặc màn trống nếu không còn con nào). Chỉ phụ huynh mới gửi đơn.

Liên kết: vào từ trang đầu sau đăng nhập hoặc thông báo. Nếu màn đích chưa có trong dự án, để một điểm dẫn tới.

Theo quy tắc co giãn của web responsive trong prompt nền, dùng tốt ở khổ hẹp lẫn rộng; không để mất chức năng nào khi thu nhỏ.
```
