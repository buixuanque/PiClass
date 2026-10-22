# Bộ prompt — Cổng Học sinh

Gồm 15 prompt cho các màn hình của học sinh. Thứ tự vẽ gợi ý: tổng quan, lịch học, bài tập, bài kiểm tra, chi tiết bài, làm bài (một prompt chung rồi bốn prompt theo nhóm cách trả lời), đã nộp, kết quả, xem lại bài làm, tiến độ, khóa học. Mỗi prompt tự đứng được, có thể dán ở phiên riêng.

| Mã | Màn hình | Ghi chú |
|---|---|---|
| ST-01 | Tổng quan | Màn mở đầu mỗi ngày |
| ST-02 | Lịch học | Gồm chi tiết một buổi học |
| ST-03 | Bài tập | Danh sách bài tập được giao |
| ST-04 | Bài kiểm tra | Danh sách bài kiểm tra và bài thi |
| ST-05 | Chi tiết bài | Đọc hướng dẫn, bắt đầu hoặc tiếp tục làm bài |
| ST-06 | Làm bài — phần chung | Đồng hồ, tự lưu, điều hướng giữa các câu, nộp, mất kết nối, hết giờ |
| ST-06-A | Làm bài — câu chọn đáp án | Một đáp án, nhiều đáp án, đúng/sai |
| ST-06-B | Làm bài — câu nhập chữ | Điền chỗ trống, trả lời ngắn, tự luận |
| ST-06-C | Làm bài — câu nối cặp và sắp xếp | Nối cặp, sắp xếp thứ tự |
| ST-06-D | Làm bài — câu nộp tệp | Chọn hoặc chụp tệp bài làm |
| ST-06b | Đã nộp bài | Xác nhận nộp xong |
| ST-07 | Kết quả | Chỉ hiện sau khi giáo viên công bố |
| ST-07b | Xem lại bài làm | Đối chiếu từng câu |
| ST-08 | Tiến độ học tập | Điểm trung bình và theo môn |
| ST-09 | Khóa học của em | Khóa đã ghi danh và bài thuộc khóa |

---

### ST-01 — Tổng quan

```text
Màn hình: Tổng quan (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay)

Tình huống: Học sinh lớp 9 mở ứng dụng trên điện thoại, thường là giờ nghỉ hoặc ngay trước giờ đi học thêm. Em chỉ muốn biết trong vài giây: hôm nay có buổi học nào, bài nào sắp đến hạn, có điểm mới chưa. Quan trọng nhất là nhanh và ít phải chạm.

Thông tin cần có:
- Lời chào kèm tên em và ngày hôm nay.
- Các buổi học hôm nay theo giờ: giờ bắt đầu–kết thúc, tên lớp, môn, trạng thái (Sắp diễn ra, Đã học, Đã hủy), còn bao lâu nữa là đến giờ.
- Bài sắp đến hạn, hạn gần nhất lên trước: tên bài, loại (bài tập, kiểm tra, thi), hạn nộp tuyệt đối kèm tương đối ("Thứ Sáu 24/10 · 23:59, còn 2 ngày"), trạng thái (Chưa làm, Đang làm, Chờ chấm, Đã có điểm).
- Bài vừa được công bố điểm.
- Bài đang làm dở (nếu có), cần quay lại làm tiếp được ngay.

Hành động:
- Mở chi tiết buổi học, mở chi tiết bài, xem toàn bộ bài, xem lịch tuần.
- Tiếp tục làm bài đang dở bằng một chạm.
- Mở thông báo.
- Không có hành động chỉnh sửa gì trên màn này.

Quy tắc nghiệp vụ:
- Chỉ hiện bài đã được giao cho em; bài nháp không bao giờ xuất hiện.
- Buổi học bị hủy vẫn hiện nhưng phải nhìn là đã hủy ("Đã hủy") để em không đi nhầm.
- Bài mới công bố điểm chỉ hiện khi giáo viên đã công bố.

Tình huống đặc biệt:
- Đang tải: từng nhóm thông tin tải riêng.
- Hôm nay không có buổi học: "Hôm nay em không có buổi học" kèm gợi ý xem lịch tuần.
- Chưa có bài nào đến hạn: "Chưa có bài nào đến hạn".
- Một nhóm lỗi tải: báo lỗi và cho thử lại riêng nhóm đó, các nhóm khác vẫn dùng được.
- Có bài đang làm dở và đồng hồ vẫn chạy.

Dữ liệu mẫu:
- "Chào Minh Anh!" — Thứ Năm 23/10
- 17:30–19:00 Toán 9A, còn 3 giờ nữa
- "Bài tập Hàm số bậc nhất" — hạn 23:59 Thứ Sáu, chưa làm
- "Kiểm tra 15 phút — Căn bậc hai" — vừa có điểm 8,5/10
```

### ST-02 — Lịch học

```text
Màn hình: Lịch học (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay)

Tình huống: Học sinh muốn xem tuần này hoặc tháng này mình học gì, lúc nào, buổi nào đã đổi hoặc hủy, và mình có mặt hay vắng ở các buổi đã qua. Quan trọng nhất là đọc nhanh, không đi nhầm buổi.

Thông tin cần có:
- Kỳ đang xem (tuần hoặc tháng) và cách đổi sang kỳ trước, kỳ sau.
- Các buổi học theo ngày: giờ bắt đầu–kết thúc, tên lớp, môn, trạng thái (Sắp diễn ra, Đang diễn ra, Đã học, Đã hủy).
- Khi mở một buổi: nội dung buổi học, điểm danh của riêng em (Có mặt, Đi muộn, Vắng, Có phép, kèm ghi chú nếu có).

Hành động:
- Chuyển giữa xem theo tuần và theo tháng; đổi kỳ.
- Mở một buổi để xem chi tiết.
- Không sửa, không hủy, không điểm danh, không gửi đơn xin phép (việc xin phép do phụ huynh làm).

Quy tắc nghiệp vụ:
- Buổi bị hủy vẫn hiện, phải nhìn ra là đã hủy ("Đã hủy").
- Buổi đang diễn ra phải nổi bật hơn các buổi khác.
- Buổi đã diễn ra mà chưa có điểm danh: ghi "Chưa điểm danh".
- Chỉ thấy điểm danh của chính em, không thấy bạn cùng lớp.
- Không có thông tin về giáo viên hay phòng học thì không bịa ra.
- Không hiện lịch của lớp em đã rút hoặc đã kết thúc ngoài phạm vi.

Tình huống đặc biệt:
- Đang tải.
- Kỳ không có buổi nào: "Tuần này em không có buổi học".
- Lỗi tải: cho thử lại.
- Buổi đã hủy; buổi đang diễn ra.
- Không tìm thấy buổi học hoặc không có quyền xem: "Không tìm thấy buổi học này".

Dữ liệu mẫu:
- Thứ Ba 21/10 · 17:30–19:00 Toán 9A · Đã học, em có mặt · nội dung: "Ôn tập hệ phương trình bậc nhất hai ẩn"
- Thứ Năm 23/10 · 17:30–19:00 Văn 9A · Đã hủy
- Thứ Bảy 25/10 · 08:00–09:30 Toán 9A · Sắp diễn ra
```

### ST-03 — Bài tập

```text
Màn hình: Bài tập (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay)

Tình huống: Học sinh muốn biết mình còn bài tập nào chưa nộp, hạn khi nào, bài nào đã có điểm. Hay mở khi tối ngồi vào bàn học, nên cần thấy ngay bài nào gấp nhất.

Thông tin cần có (mỗi bài tập):
- Tên bài, hạn nộp (tuyệt đối kèm tương đối), điểm tối đa.
- Trạng thái: Chưa làm, Đang làm, Chờ chấm, Đã có điểm (kèm điểm), Quá hạn, Đã đóng, Đã hủy.
- Phân biệt rõ nhóm "Cần làm" (hạn gần lên trước) và nhóm "Đã xong".

Hành động:
- Lọc nhanh theo trạng thái: Tất cả, Chưa làm, Chờ chấm, Đã có điểm, Đã đóng hoặc hủy.
- Mở chi tiết một bài.
- Làm mới danh sách bằng thao tác vuốt kéo.
- Không có hành động nào khác trên từng bài.

Quy tắc nghiệp vụ:
- Chỉ hiện bài giao cho em; bài nháp không xuất hiện.
- "Chờ chấm" nghĩa là em đã nộp nhưng giáo viên chưa công bố điểm; lúc này không hiện điểm.
- "Quá hạn" dùng khi qua hạn nhưng vẫn còn được nộp trễ; "Đã đóng" khi đã hết thời gian nhận bài.
- Bài đã hủy không còn làm được.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Em chưa có bài tập nào. Khi giáo viên giao, bài sẽ hiện ở đây".
- Lọc ra không có bài: "Không có bài nào ở trạng thái này".
- Lỗi tải: cho thử lại.
- Bài quá hạn chưa nộp; bài đã đóng; bài đã hủy.

Dữ liệu mẫu:
- "Bài tập Hàm số bậc nhất" · hạn Thứ Sáu 24/10 23:59 · 10 điểm · Chưa làm
- "Luyện đọc hiểu: Lão Hạc" · Đã có điểm 8/10
- "Bài tập Hệ phương trình" · đã nộp, Chờ chấm
```

### ST-04 — Bài kiểm tra

```text
Màn hình: Bài kiểm tra và bài thi (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay)

Tình huống: Học sinh muốn biết kỳ kiểm tra hoặc bài thi nào sắp mở, mở lúc nào, đã làm chưa. Khác với bài tập, kiểm tra và thi chỉ làm được trong một khung giờ nên em phải thấy rõ giờ mở và giờ đóng để không lỡ.

Thông tin cần có (mỗi bài):
- Tên bài, loại (Kiểm tra hoặc Thi).
- Khung giờ mở–đóng, nổi bật hơn các thông tin khác.
- Trạng thái: Chưa mở, Đang mở, Đang làm, Chờ chấm, Đã có điểm (kèm điểm), Đã đóng nhưng em không làm, Đã hủy.
- Điểm tối đa.

Hành động:
- Lọc nhanh theo trạng thái và theo loại (Kiểm tra, Thi).
- Mở chi tiết bài để đọc lưu ý rồi mới bắt đầu làm.
- Không bắt đầu làm bài trực tiếp từ danh sách.

Quy tắc nghiệp vụ:
- Bài chưa mở vẫn xem được chi tiết nhưng chưa làm được.
- Bài thi hết giờ sẽ tự nộp phần em đã làm.
- Chỉ hiện bài giao cho em; "Chờ chấm" không hiện điểm vì giáo viên chưa công bố.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Em chưa có bài kiểm tra nào. Khi giáo viên giao, bài sẽ hiện ở đây".
- Lỗi tải: cho thử lại.
- Chưa mở; đang mở; đã đóng em không làm; đã hủy.

Dữ liệu mẫu:
- "Kiểm tra 15 phút — Căn bậc hai" · mở 18:00–18:30 ngày 25/10 · Chưa mở
- "Thi giữa kỳ I — Toán 9" · 08:00–09:30 Thứ Bảy 01/11 · Chưa làm
- "Kiểm tra Chương 1 — Văn 9" · Đã có điểm 7/10
```

### ST-05 — Chi tiết bài

```text
Màn hình: Chi tiết bài (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay)

Tình huống: Học sinh vừa chọn một bài tập, kiểm tra hoặc bài thi. Em cần đọc hướng dẫn, biết còn mấy lượt, điểm lượt trước, rồi bắt đầu làm. Quan trọng nhất là hiểu rõ điều kiện trước khi bấm bắt đầu, vì bắt đầu là tính một lượt và đồng hồ chạy ngay.

Thông tin cần có:
- Tên bài, loại, môn và lớp, trạng thái hiện tại.
- Giờ mở, giờ đóng, hạn nộp; hạn riêng của em nếu giáo viên đã gia hạn.
- Hướng dẫn làm bài; thời lượng làm bài; số câu; điểm tối đa.
- Số lượt tối đa, số lượt còn lại; chính sách nộp trễ; cách tính điểm khi làm nhiều lượt (lấy cao nhất hoặc lần cuối).
- Điểm các lượt trước (chỉ lượt đã được công bố).
- Tệp đề đính kèm nếu có.

Hành động:
- "Làm bài": bấm vào thì phải xác nhận trước, nêu rõ "Bắt đầu sẽ tính 1 lượt và đồng hồ chạy ngay. Em có thể thoát và quay lại, nhưng đồng hồ không dừng".
- Nếu em đang có một lượt làm dở: hành động chính là "Tiếp tục làm bài", không tạo lượt mới.
- "Xem kết quả" khi đã có kết quả công bố.
- Mở tệp đính kèm.

Quy tắc nghiệp vụ (hành động bị khóa phải kèm lý do):
- Chưa tới giờ mở: "Chưa tới giờ mở bài, mở lúc 18:00".
- Quá giờ đóng: "Đã quá giờ đóng bài".
- Hết lượt: "Em đã dùng hết số lượt".
- Bài đã đóng hoặc đã hủy: "Bài này chưa mở hoặc đã đóng".
- Bài không còn được giao: "Bài không còn được giao cho em".
- Mọi lỗi hiện thành thông báo ngắn rồi cập nhật lại trạng thái màn hình.

Tình huống đặc biệt:
- Đang tải; lỗi tải; bài không giao cho em ("Không tìm thấy bài này").
- Chưa mở; đang mở; hết lượt; đã đóng; đã hủy; đã nộp chờ chấm; đã có điểm.

Dữ liệu mẫu:
- "Kiểm tra 15 phút — Căn bậc hai" · mở 18:00, đóng 18:30 · 15 phút · 10 câu · còn 1/1 lượt
- "Em có thể thoát và quay lại, đồng hồ vẫn chạy."
```

### ST-06 — Làm bài (phần chung)

```text
Màn hình: Làm bài — phần chung cho mọi loại câu (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay, phải dùng tốt khi xoay ngang và khi bàn phím ảo bật lên che màn hình)

Tình huống: Học sinh đang làm bài kiểm tra hoặc bài tập trên điện thoại, thường mạng yếu hoặc chập chờn. Em chỉ nghĩ đến việc trả lời đúng; điều em sợ nhất là mất bài, hết giờ mà không biết, hoặc lỡ tay nộp khi chưa xong. Quan trọng nhất là cảm giác an toàn: luôn thấy bài đã được lưu và còn bao lâu.

Thông tin cần có:
- Tên bài; thời gian còn lại; trạng thái lưu (Đang lưu, Đã lưu lúc 17:42, Chưa lưu được — đang thử lại).
- Câu hiện tại: số thứ tự trên tổng số câu, điểm của câu, nội dung câu, ghi chú nếu có, vùng trả lời (xem prompt riêng theo từng loại câu).
- Tổng quát tất cả câu: mỗi câu là Đã trả lời, Chưa trả lời hoặc Đã đánh dấu "Xem lại sau".
- Bài không có thời lượng thì thay đồng hồ bằng hạn nộp.

Hành động:
- Câu trước, câu sau, nhảy thẳng tới một câu bất kỳ từ phần xem tổng quát; đánh dấu "Xem lại sau".
- Ở câu cuối, hành động chuyển thành "Xem lại và nộp".
- Nộp bài: trước khi nộp phải xác nhận, nêu số câu đã trả lời trên tổng số, số câu chưa trả lời và câu đã đánh dấu, cho nhảy tới các câu đó. Ví dụ: "Em còn 3 câu chưa làm. Nộp bài? Nộp rồi không sửa được." với hai lựa chọn "Quay lại làm" và "Nộp bài".

Quy tắc nghiệp vụ:
- Mọi câu trả lời tự lưu, em không phải bấm lưu: chọn là lưu ngay; gõ chữ thì lưu khi dừng gõ khoảng 1 giây, khi rời ô và khi chuyển câu.
- Đồng hồ tính theo giờ máy chủ, không dừng khi mất mạng hay thoát trang. Còn 5 phút và còn 1 phút phải báo nổi bật nhưng không chặn làm bài. Vào muộn thì thời gian ngắn hơn thời lượng: "Bài đóng lúc 18:30 nên em chỉ còn 12 phút".
- Không được nộp khi còn câu chưa lưu được: "Còn câu chưa lưu — Thử lại".
- Mất kết nối: báo "Mất kết nối — bài của em vẫn được giữ trên máy, sẽ tự lưu khi có mạng lại"; khóa việc nộp; khi có mạng lại báo "Đã kết nối lại, đã lưu".
- Hết giờ: khóa nhập, bài tự nộp phần đã lưu, báo "Hết giờ, bài của em đã được nộp". Nếu bài không cho nộp trễ và đã quá hạn: "Đã hết thời gian nộp bài" kèm đường về chi tiết bài. Câu đang gõ dở sau giờ không được lưu và phải báo rõ.
- Quá hạn nộp: "Đã quá hạn nộp bài, các câu đã lưu được giữ lại. Em liên hệ giáo viên nhé".
- Bài đã nộp hoặc không còn mở (ví dụ mở ở thiết bị khác): "Bài này đã được nộp hoặc không còn mở".
- Rời trang giữa chừng phải hỏi: "Em có chắc muốn rời trang? Bài vẫn đang mở và đồng hồ vẫn chạy."
- Tải lại trang vẫn về đúng câu đang làm.

Tình huống đặc biệt: đang tải bài; vừa bắt đầu; đang lưu, đã lưu, lưu lỗi; mất kết nối; còn 5 phút, còn 1 phút; hết giờ đang nộp; xác nhận nộp có và không có câu bỏ trống; đang nộp; bài không còn mở; phiên đăng nhập hết hạn (giữ lại phần đang làm và quay lại đúng bài sau khi đăng nhập).

Dữ liệu mẫu: "Kiểm tra 15 phút — Căn bậc hai · Câu 3/10 · 1 điểm" · còn 12:40 · "Đã lưu 18:07".
```

### ST-06-A — Làm bài: câu chọn đáp án

```text
Màn hình: Làm bài — các câu chọn đáp án: một đáp án, nhiều đáp án, đúng/sai (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay)

Tình huống: Học sinh đang làm bài và gặp câu trắc nghiệm. Em cần chọn nhanh, chọn bằng một chạm, ngón cái bấm trúng và biết chắc mình đã chọn gì. Đang trong bài có đồng hồ đếm ngược và trạng thái tự lưu, câu trả lời được lưu ngay khi chọn.

Thông tin cần có:
- Số câu trên tổng số câu, điểm của câu, nội dung câu, ghi chú đi kèm nếu có.
- Các đáp án theo đúng thứ tự hệ thống đưa ra (em không tự đổi thứ tự).
- Đáp án em đang chọn.
- Trạng thái lưu của câu này và đồng hồ.

Hành động:
- Câu một đáp án và câu đúng/sai: chạm một đáp án để chọn; chạm đáp án khác thì đổi chọn, đáp án cũ tự bỏ.
- Câu nhiều đáp án: chạm để chọn hoặc bỏ chọn từng đáp án, chọn được nhiều.
- Với câu đúng/sai: hai lựa chọn "Đúng" và "Sai" phải đủ lớn để chạm chính xác.
- Đánh dấu "Xem lại sau"; sang câu trước, câu sau.

Quy tắc nghiệp vụ:
- Câu nhiều đáp án luôn nhắc ngay dưới câu hỏi: "Chọn tất cả đáp án đúng. Phải chọn đúng cả tập mới được điểm, không tính điểm từng phần."
- Không hiện đáp án đúng trong lúc làm.
- Bỏ trống được; câu chưa chọn tính là chưa trả lời.
- Lưu lỗi thì báo "Chưa lưu được — đang thử lại" và vẫn giữ lựa chọn của em trên màn hình.

Tình huống đặc biệt: chưa trả lời; đã chọn; đã đánh dấu xem lại; đang lưu, đã lưu, lưu lỗi; mất kết nối; hết giờ (khóa chọn); bài đã nộp (khóa chọn); đáp án rất dài hoặc có công thức toán.

Dữ liệu mẫu:
- Một đáp án: "√49 bằng bao nhiêu?" với 5, 7, 9, 14
- Nhiều đáp án: "Những số nào là nghiệm của x² = 9?" với −3, 0, 3, 9
- Đúng/sai: "Hàm số y = 2x + 1 là hàm số bậc nhất." Đúng / Sai
```

### ST-06-B — Làm bài: câu nhập chữ

```text
Màn hình: Làm bài — các câu nhập chữ: điền chỗ trống, trả lời ngắn, tự luận (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay, bàn phím ảo luôn bật)

Tình huống: Học sinh đang làm bài và phải gõ câu trả lời bằng bàn phím ảo trên điện thoại. Bàn phím chiếm nửa màn hình, nên điều quan trọng nhất là vẫn thấy được câu hỏi và chỗ đang gõ, và không bao giờ mất chữ đã gõ dù mạng yếu hay lỡ chuyển câu.

Thông tin cần có:
- Số câu trên tổng số câu, điểm của câu, nội dung câu, ghi chú đi kèm nếu có.
- Chỗ nhập câu trả lời:
  • điền chỗ trống: nội dung câu có chỗ trống, nhập ngay tại câu một đoạn ngắn;
  • trả lời ngắn: nhập một dòng;
  • tự luận: soạn nhiều dòng, tự giãn theo độ dài, có số ký tự đã gõ.
- Trạng thái lưu của câu này và đồng hồ.

Hành động:
- Gõ, sửa, xóa câu trả lời.
- Đánh dấu "Xem lại sau"; sang câu trước, câu sau.

Quy tắc nghiệp vụ:
- Tự lưu khi em ngừng gõ khoảng 1 giây, khi rời ô nhập và khi chuyển câu; không bao giờ chỉ lưu khi em bấm lưu.
- Bàn phím ảo không được che chỗ đang gõ.
- Câu tự luận sẽ được giáo viên chấm tay: sau khi nộp trạng thái là "Chờ chấm". Câu điền chỗ trống và trả lời ngắn được chấm tự động so với các đáp án giáo viên chấp nhận, nên không hiện đáp án đúng khi làm.
- Để trống được; câu chưa gõ gì tính là chưa trả lời.
- Hết giờ thì khóa nhập; phần đang gõ dở sau giờ không được lưu và phải báo rõ.

Tình huống đặc biệt: chưa trả lời; đã gõ; đang lưu, đã lưu, lưu lỗi ("Chưa lưu được — đang thử lại", chữ vẫn được giữ); mất kết nối; hết giờ; bài đã nộp (khóa nhập); đoạn tự luận rất dài; câu có công thức hoặc ký hiệu toán.

Dữ liệu mẫu:
- Điền chỗ trống: "Nghiệm của phương trình 2x − 6 = 0 là x = ____"
- Trả lời ngắn: "Tên tác giả truyện ngắn Lão Hạc?" Đáp án em gõ: "Nam Cao"
- Tự luận: "Trình bày cảm nhận của em về nhân vật lão Hạc." (đã gõ 312 ký tự)
```

### ST-06-C — Làm bài: câu nối cặp và sắp xếp

```text
Màn hình: Làm bài — câu nối cặp và câu sắp xếp thứ tự (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay, màn hình hẹp)

Tình huống: Học sinh đang làm bài và gặp câu phải ghép các mục với nhau hoặc xếp các mục theo thứ tự. Trên điện thoại thao tác kéo thả dễ trượt và khó dùng một tay, nên em cần cách làm bằng chạm đơn giản, sửa lại dễ, và luôn thấy hiện trạng mình đã ghép hoặc xếp ra sao. Đang trong bài có đồng hồ và trạng thái tự lưu.

Thông tin cần có:
- Số câu trên tổng số câu, điểm của câu, nội dung câu, ghi chú đi kèm nếu có.
- Câu nối cặp: hai nhóm mục, một nhóm là các mục cần nối, một nhóm là các lựa chọn để nối; mục nào đã nối với lựa chọn nào; lựa chọn nào đã được dùng.
- Câu sắp xếp: các mục theo thứ tự hiện tại (ban đầu là thứ tự bị xáo), vị trí của từng mục.
- Trạng thái lưu và đồng hồ.

Hành động:
- Nối cặp: chọn một mục cần nối rồi chọn lựa chọn tương ứng; đổi hoặc bỏ cặp đã nối. Không bắt buộc kéo thả.
- Sắp xếp: đưa từng mục lên hoặc xuống một vị trí bằng chạm; kéo thả có thể có thêm nhưng không được là cách duy nhất.
- Đánh dấu "Xem lại sau"; sang câu trước, câu sau.

Quy tắc nghiệp vụ:
- Mỗi lựa chọn chỉ nối được với một mục; lựa chọn đã dùng phải nhìn ra là đã dùng.
- Câu sắp xếp: chưa chạm vào thì chưa tính là đã trả lời, dù danh sách đang hiện một thứ tự nào đó; sau khi chạm thì lưu ngay.
- Mỗi lần ghép hoặc đổi vị trí tự lưu ngay.
- Không hiện đáp án đúng trong lúc làm.

Tình huống đặc biệt: chưa trả lời; mới nối một phần; đã nối đủ; đã sắp xếp; đang lưu, đã lưu, lưu lỗi; mất kết nối; hết giờ và bài đã nộp (khóa thao tác); nội dung mục dài.

Dữ liệu mẫu:
- Nối cặp: "Nối hàm số với hệ số góc": y = 2x + 1 ↔ 2; y = −x + 4 ↔ −1; y = 3x ↔ 3
- Sắp xếp: "Xếp các bước giải phương trình theo thứ tự đúng": Chuyển vế; Thu gọn hai vế; Chia cho hệ số của x; Kết luận nghiệm
```

### ST-06-D — Làm bài: câu nộp tệp

```text
Màn hình: Làm bài — câu nộp tệp (học sinh; thiết bị ưu tiên: điện thoại, dùng một tay)

Tình huống: Học sinh đang làm bài và gặp câu yêu cầu nộp bài làm viết tay hoặc tệp đã soạn. Em thường chụp ảnh vở bằng camera điện thoại hoặc chọn tệp có sẵn. Mạng có thể yếu nên cần biết chắc tệp đã tải lên xong chưa. Đang trong bài có đồng hồ và trạng thái tự lưu.

Thông tin cần có:
- Số câu trên tổng số câu, điểm của câu, nội dung câu, ghi chú đi kèm nếu có.
- Các loại tệp được nhận (pdf, doc/docx, jpg, png, zip) và dung lượng tối đa 10 MB.
- Tệp đã gắn: tên tệp, dung lượng, tiến độ tải lên, đã tải xong hay lỗi.
- Trạng thái lưu của câu này và đồng hồ.

Hành động:
- Chọn tệp từ máy hoặc chụp ảnh trực tiếp.
- Xem tên tệp đã gắn; thay tệp khác; gỡ tệp.
- Đánh dấu "Xem lại sau"; sang câu trước, câu sau.

Quy tắc nghiệp vụ:
- Chỉ nhận loại tệp và dung lượng nêu trên. Sai loại: "Tệp này không được nhận. Em chọn pdf, doc, docx, jpg, png hoặc zip nhé". Quá dung lượng: "Tệp lớn hơn 10 MB. Em chọn tệp nhỏ hơn hoặc chụp lại nhé".
- Câu chỉ tính là đã trả lời khi tệp đã tải lên xong; việc nộp bài phải chờ tải xong.
- Câu nộp tệp do giáo viên chấm tay, nên sau khi nộp trạng thái là "Chờ chấm".
- Tải lên lỗi hoặc mất kết nối: báo rõ "Chưa tải lên được — thử lại" và cho thử lại, không làm mất các câu khác.
- Hết giờ thì khóa thay đổi; tệp đang tải dở sau giờ không được tính và phải báo rõ.

Tình huống đặc biệt: chưa có tệp; đang tải lên; đã tải xong; tải lỗi; sai loại hoặc quá dung lượng; mất kết nối; hết giờ; bài đã nộp (khóa thay đổi); không cấp quyền dùng camera (vẫn chọn được tệp có sẵn).

Dữ liệu mẫu:
- "Câu 5 — Chụp bài giải chi tiết của bài toán hình học (5 điểm)"
- Tệp đã gắn: "bai-giai-cau-5.jpg", 2,4 MB, đã tải xong
```

### ST-06b — Đã nộp bài

```text
Màn hình: Đã nộp bài (học sinh; thiết bị ưu tiên: điện thoại)

Tình huống: Học sinh vừa bấm nộp hoặc bài vừa tự nộp vì hết giờ. Em cần được trấn an ngay: bài đã nộp thành công, nộp lúc nào, và bước tiếp theo là gì. Nhẹ nhàng, rõ ràng, không gây hoang mang về điểm.

Thông tin cần có:
- Dấu hiệu rõ ràng là đã nộp thành công.
- Tên bài; giờ hệ thống nhận bài.
- Nhãn "Nộp trễ" nếu nộp sau hạn.
- Tình trạng chấm: đã chấm tự động, chờ giáo viên công bố điểm; hoặc có câu tự luận hay nộp tệp đang chờ giáo viên chấm.
- Câu giải thích: "Điểm sẽ hiện khi giáo viên công bố".
- Còn lượt làm lại hay không.

Hành động:
- Về chi tiết bài; về trang chủ.
- Xem kết quả, chỉ khi giáo viên đã công bố.
- Làm lượt khác, chỉ khi bài còn lượt.

Quy tắc nghiệp vụ:
- Tuyệt đối không hiện điểm hay số câu đúng ở màn này, dù hệ thống đã chấm xong phần tự động, vì giáo viên chưa công bố.
- Nộp do hết giờ phải nói rõ là bài tự nộp theo phần em đã làm.

Tình huống đặc biệt: nộp thành công; nộp trễ; nộp do hết giờ; có câu chờ giáo viên chấm tay; chấm tự động xong, chờ công bố.

Dữ liệu mẫu:
- "Em đã nộp bài lúc 18:14."
- "Bài có 2 câu tự luận, cô sẽ chấm và công bố điểm."
- "Kiểm tra 15 phút — Căn bậc hai"
```

### ST-07 — Kết quả

```text
Màn hình: Kết quả bài (học sinh; thiết bị ưu tiên: điện thoại)

Tình huống: Học sinh nhận thông báo cô đã công bố điểm và mở ra xem. Em muốn biết ngay được bao nhiêu điểm, đạt hay chưa, cô nhận xét gì, và có xem được đáp án không. Với nhiều em đây là khoảnh khắc hồi hộp, cần rõ ràng và tôn trọng, nhất là khi chưa đạt.

Thông tin cần có:
- Điểm đại diện của bài (theo chính sách cao nhất hoặc lần cuối): điểm trên điểm tối đa, phần trăm, nhãn Đạt hoặc Chưa đạt (nếu bài không đặt điểm đạt thì không hiện nhãn).
- Các lượt đã làm và đã được công bố: số lượt, điểm, phần trăm; lượt nào đang được tính.
- Nhận xét của giáo viên: nhận xét chung cho bài và nhận xét cho từng câu, có ngày.
- Nếu giáo viên cho phép: điểm từng câu, đáp án đúng, giải thích; mỗi nhóm bật hoặc tắt riêng.

Hành động:
- Xem điểm các lượt khác nhau; chi tiết từng câu theo lượt đại diện.
- Mở xem lại bài làm.
- Không có khiếu nại hay yêu cầu sửa điểm; việc sửa điểm do giáo viên.

Quy tắc nghiệp vụ:
- Chỉ hiện sau khi giáo viên công bố. Nhóm nào giáo viên không cho phép (điểm từng câu, đáp án, giải thích) thì ẩn hẳn, không để chỗ trống, không ghi "không có".
- Bài thi mặc định ẩn đáp án cho tới khi giáo viên cho phép.
- Giáo viên rút công bố thì kết quả biến mất, trở về trạng thái chưa công bố, không để lại dấu vết.
- Nhận xét bị rút cũng biến mất.

Tình huống đặc biệt:
- Đang tải.
- Chưa công bố: không phải lỗi, hiện "Kết quả chưa được công bố. Cô sẽ báo khi có điểm".
- Lỗi mạng: cho thử lại.
- Đạt; chưa đạt (giọng động viên); nhiều lượt; có và không có nhận xét; các nhóm thông tin bị ẩn.

Dữ liệu mẫu:
- "8,5 / 10 · 85% · Đạt"
- Lượt 1: 6/10; lượt 2: 8,5/10 (được tính)
- Nhận xét: "Em làm tốt phần căn bậc hai, cần chú ý bước đặt điều kiện."
```

### ST-07b — Xem lại bài làm

```text
Màn hình: Xem lại bài làm (học sinh; thiết bị ưu tiên: điện thoại)

Tình huống: Sau khi có điểm, học sinh muốn xem lại từng câu mình đã trả lời gì, đối chiếu với nhận xét của giáo viên để rút kinh nghiệm. Em đọc kỹ từng câu nên cần dễ lướt và dễ nhảy tới câu mình muốn xem.

Thông tin cần có (mỗi câu, theo thứ tự câu):
- Số câu, nội dung câu, loại câu, điểm tối đa.
- Câu trả lời em đã chọn hoặc đã viết (với câu nối cặp, sắp xếp, nộp tệp thì thể hiện đúng hình thức em đã trả lời).
- Nhận xét của giáo viên cho câu này nếu có.
- Nếu giáo viên cho phép: đúng hay sai, điểm câu trên điểm tối đa, đáp án đúng, giải thích; mỗi nhóm bật hoặc tắt riêng.

Hành động:
- Cuộn, nhảy tới một câu.
- Quay lại kết quả.
- Chỉ xem, không sửa được gì.

Quy tắc nghiệp vụ:
- Chỉ vào được khi bài đã có kết quả công bố.
- Nhóm thông tin nào giáo viên không cho phép thì ẩn hẳn.
- Câu bỏ trống: "Em chưa trả lời câu này".
- Chỉ xem được bài làm của chính mình.

Tình huống đặc biệt: đang tải; lỗi tải (thử lại); không tìm thấy bài làm; câu bỏ trống; câu nộp tệp; câu có nhận xét; các nhóm thông tin đáp án bị ẩn.

Dữ liệu mẫu:
- "Câu 3 — √49 bằng bao nhiêu? Em chọn: 7 (đúng) · 1/1 điểm"
- "Câu 9 — Tự luận. Em chưa trả lời câu này."
- Nhận xét câu 7: "Em xếp sai bước thu gọn, xem lại nhé."
```

### ST-08 — Tiến độ học tập

```text
Màn hình: Tiến độ học tập (học sinh; thiết bị ưu tiên: điện thoại)

Tình huống: Học sinh muốn biết điểm của mình đang ở đâu so với cả quá trình học và môn nào đang yếu để ôn thêm. Em xem qua nhanh, cần con số rõ và dễ hiểu, không rối.

Thông tin cần có:
- Điểm trung bình chung (phần trăm).
- Số bài đã có điểm; số bài đang chờ chấm hoặc chờ công bố.
- Theo từng môn: tên môn, điểm trung bình, số bài đã có điểm. Có nhóm "Khác" cho bài không gắn với lớp hay khóa nào.
- Điểm theo thời gian (xu hướng) và các bài gần đây.

Hành động:
- Đổi khoảng thời gian và môn muốn xem.
- Chọn một môn để xem các bài của môn đó.

Quy tắc nghiệp vụ:
- Mỗi bài chỉ tính một lần dù làm nhiều lượt, theo cách tính điểm của bài.
- Bài chưa công bố hoặc đã hủy không tính vào điểm trung bình.
- Chưa có bài nào được công bố thì điểm trung bình ghi "Chưa có điểm".
- Chỉ có một bài thì không vẽ xu hướng.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Chưa có bài nào được chấm. Điểm sẽ hiện khi cô công bố".
- Lỗi tải: cho thử lại.
- Chỉ một bài; có bài chờ chấm; bộ lọc cho ra kết quả rỗng.

Dữ liệu mẫu:
- Điểm trung bình 78,5%
- 6 bài đã có điểm, 1 bài chờ
- Toán 82% (4 bài); Văn 72% (2 bài)
```

### ST-09 — Khóa học của em

```text
Màn hình: Khóa học của em (học sinh; thiết bị ưu tiên: điện thoại)

Tình huống: Học sinh muốn xem mình đang ghi danh những khóa nào (ví dụ khóa luyện thi vào 10) và các bài thuộc từng khóa. Nội dung của khóa chính là các bài tập, bài kiểm tra được gán vào khóa đó. Em chỉ xem, không tự đăng ký hay rút.

Thông tin cần có:
- Danh sách khóa em đang ghi danh: tên khóa, mô tả, môn, trạng thái khóa (Đang hoạt động, Đã lưu trữ).
- Khi mở một khóa: thông tin khóa; thời gian ghi danh của em (từ ngày, đến ngày, trạng thái ghi danh); các bài thuộc khóa, mỗi bài có tên, loại, hạn, trạng thái (Chưa làm, Chờ chấm, Đã có điểm, Đã đóng, Đã hủy).

Hành động:
- Mở một khóa; mở một bài trong khóa.
- Không có đăng ký, rút khóa; việc ghi danh do quản trị viên hoặc giáo viên phụ trách khóa.

Quy tắc nghiệp vụ:
- Chỉ thấy khóa em có ghi danh còn hiệu lực. Ghi danh đã kết thúc thì khóa biến mất khỏi danh sách.
- Không thấy học sinh khác trong khóa.
- Nếu chưa có thông tin giáo viên phụ trách thì không bịa ra.

Tình huống đặc biệt:
- Đang tải.
- Rỗng: "Em chưa ghi danh khóa học nào".
- Lỗi tải: cho thử lại.
- Khóa chưa có bài nào: "Khóa này chưa có bài".
- Khóa đã lưu trữ hoặc hết hiệu lực.
- Mở khóa không thuộc em: "Không tìm thấy khóa học này".

Dữ liệu mẫu:
- "Luyện thi vào 10 — Toán" · ghi danh từ 01/09/2026
- "Ôn tập tổng hợp cuối kỳ" · Đang hoạt động
- Bài trong khóa: "Bài tập Hàm số bậc nhất" · hạn 24/10 · Chưa làm
```
