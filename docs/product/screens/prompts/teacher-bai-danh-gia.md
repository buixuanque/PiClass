# Bộ prompt — Cổng Teacher: câu hỏi, bài đánh giá, chấm bài

Nhóm này gồm các màn giáo viên dùng hằng ngày sau điểm danh: xem danh sách bài đã giao, soạn và giao bài, theo dõi bài đã giao, xem bài nộp, chấm bài, rồi tìm và soạn câu hỏi trong ngân hàng. Thứ tự vẽ gợi ý: danh sách bài, soạn và giao bài (năm prompt), chi tiết bài, bài nộp, chấm bài (hai prompt), ngân hàng câu hỏi, soạn câu hỏi (bốn prompt theo nhóm loại câu). Mỗi prompt tự đứng được, có thể dán ở phiên riêng.

| Mã | Màn hình | Ghi chú |
|---|---|---|
| TC-09 | Danh sách bài đánh giá | Màn vào của nhóm bài |
| TC-10a | Soạn bài: thông tin chung | Phần việc 1 của soạn và giao bài |
| TC-10b | Soạn bài: chọn câu hỏi và điểm | Phần việc 2 |
| TC-10c | Soạn bài: chọn đối tượng giao | Phần việc 3 |
| TC-10d | Soạn bài: xem trước và phát hành | Phần việc 4 |
| TC-10e | Bài đã phát hành: đóng, mở lại, hủy, phiên bản mới | Vòng đời sau phát hành |
| TC-11 | Chi tiết bài | Theo dõi tiến độ, điều chỉnh riêng từng học sinh |
| TC-12 | Bài nộp của một bài | Danh sách bài nộp, công bố hàng loạt |
| TC-13a | Chấm bài: chấm điểm | Chấm từng câu, điểm tổng, bài bị vô hiệu |
| TC-13b | Chấm bài: nhận xét và công bố kết quả | Nhận xét, công bố/rút kết quả |
| TC-07 | Ngân hàng câu hỏi | Tìm, lọc, lưu trữ câu hỏi |
| TC-08a | Soạn câu hỏi: nhóm chọn đáp án | Một đáp án, nhiều đáp án, đúng/sai |
| TC-08b | Soạn câu hỏi: nhóm trả lời bằng chữ | Điền khuyết, trả lời ngắn, tự luận |
| TC-08c | Soạn câu hỏi: nhóm cấu trúc | Nối cặp, sắp xếp thứ tự |
| TC-08d | Soạn câu hỏi: nộp tệp | Học sinh nộp ảnh hoặc tệp |

---

### TC-09 — Danh sách bài đánh giá

```text
Màn hình: Danh sách bài đánh giá (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên Toán muốn biết mình đã giao những bài nào, bài nào còn nháp, bài nào đã đóng, và tạo bài mới. Quan trọng nhất là nhìn nhanh và tìm lại đúng bài trong danh sách dài.

Thông tin cần có, mỗi bài gồm: tên bài, loại (Bài tập, Kiểm tra ngắn, Bài thi), trạng thái (Nháp, Đã phát hành, Đã đóng, Đã hủy), mốc thời gian chính (hạn nộp với bài tập; khung giờ mở và đóng với kiểm tra và bài thi), số phiên bản, dấu hiệu "có bản nháp mới" khi bài đã phát hành nhưng giáo viên đang soạn phiên bản kế tiếp. Có phân trang, mới nhất lên đầu.

Hành động:
- Lọc theo loại, trạng thái, lớp, khóa học, và "Bài tôi tạo". Tìm theo tên bài.
- Tạo bài mới (chọn loại bài).
- Mở một bài để xem chi tiết.
- Nhân bản một bài sang lớp hoặc kỳ khác.
- Đóng hoặc hủy bài ngay từ danh sách, chỉ với bài của mình hoặc quản trị viên.

Quy tắc nghiệp vụ:
- Giáo viên chỉ thấy bài do mình tạo, bài giao cho lớp mình đang dạy, hoặc bài thuộc khóa học mình phụ trách; quản trị viên thấy tất cả.
- Bài của giáo viên khác chỉ được xem, không có hành động sửa, đóng, hủy (ẩn hẳn, không chỉ làm mờ).
- Hủy bài phải nhập lý do; bài nộp và lịch sử vẫn được giữ.
- Nhân bản cần cho thấy tiến độ vì gồm nhiều thao tác nối tiếp, và cho dọn dẹp nếu dở dang.

Tình huống đặc biệt: đang tải; chưa có bài nào (mời "Tạo bài đầu tiên"); lọc không ra kết quả (gợi ý xóa bộ lọc); lỗi tải (thử lại); không có quyền; mỗi trạng thái bài có nhãn riêng; bài đã phát hành có bản nháp mới.

Dữ liệu mẫu:
- "Bài tập về nhà: Hệ phương trình" · Bài tập · Đã phát hành · Hạn 24/10 23:59
- "Kiểm tra 15 phút – Đại số chương 2" · Kiểm tra ngắn · Nháp
- "Thi giữa kỳ 1 – Toán 9" · Bài thi · Đã đóng
```

---

### TC-10a — Soạn bài: thông tin chung

```text
Màn hình: Soạn bài, phần thông tin chung (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính, dùng được trên máy tính bảng)

Tình huống: Giáo viên Toán 9 chuẩn bị bài kiểm tra 15 phút cho lớp Toán 9A. Đây là phần việc đầu tiên trong bốn phần việc soạn và giao bài (thông tin chung; câu hỏi và điểm; đối tượng; xem trước và phát hành). Người dùng cần biết mình đang ở phần nào, và rời đi giữa chừng không mất dữ liệu: ngay sau khi xong phần này bài đã được lưu thành nháp.

Thông tin cần nhập:
- Loại bài: Bài tập (có hạn nộp, làm ở nhà), Kiểm tra ngắn (tính giờ, nhiều lượt), Bài thi (khung giờ chung, chặt hơn). Chọn khi tạo, sau đó chỉ xem, không đổi được.
- Tên bài (bắt buộc), hướng dẫn cho học sinh.
- Gắn vào buổi học (chỉ với Bài tập, chọn từ các buổi của lớp; không đổi được sau khi tạo).
- Giờ mở bài, hạn nộp, giờ đóng bài.
- Thời lượng làm bài (nhập theo phút).
- Số lượt làm tối đa (mặc định 1); cách tính điểm khi làm nhiều lượt (cao nhất hoặc lần cuối), chỉ cần khi số lượt lớn hơn 1.
- Trộn thứ tự câu hỏi; trộn thứ tự đáp án (chỉ có tác dụng với câu một đáp án và nhiều đáp án).
- Điểm tối đa (bỏ trống thì bằng tổng điểm các câu), điểm đạt (bỏ trống thì không có mốc đạt).
- Cho học sinh xem đáp án đúng, xem lời giải, xem điểm từng câu (mặc định tắt; bài thi nên tắt).
- Cách công bố kết quả cho học sinh và phụ huynh: tự công bố ngay khi chấm xong; hẹn giờ công bố (chọn ngày giờ); hoặc giữ lại để giáo viên tự công bố sau. Mặc định: giáo viên tự công bố (bài thi) hoặc tự công bố khi chấm xong (bài tập, kiểm tra ngắn).
- Chính sách nộp trễ: cho nộp trễ (đánh dấu "trễ") hoặc không nhận bài sau hạn.

Hành động: lưu và tiếp tục; lưu nháp và thoát; quay lại mà không mất dữ liệu.

Quy tắc nghiệp vụ:
- Thứ tự thời gian phải là giờ mở, rồi hạn nộp, rồi giờ đóng. Sai thứ tự: "Thứ tự thời gian chưa hợp lý: giờ mở phải trước hạn nộp, hạn nộp phải trước giờ đóng."
- Điểm đạt lớn hơn điểm tối đa: "Điểm đạt đang cao hơn điểm tối đa."
- Bỏ trống tên: "Vui lòng nhập tên bài". Bỏ trống giờ mở nghĩa là mở ngay khi phát hành. Nộp sau hạn vẫn được nhận nhưng bị đánh dấu "trễ".
- Bài đã phát hành thì không sửa trực tiếp: "Bài đã phát hành, hãy soạn phiên bản mới để sửa."

Tình huống đặc biệt: bài mới trống; mở lại bài nháp đã điền; đang lưu; lỗi theo từng trường; bài đã phát hành hoặc đã hủy (chỉ đọc); giáo viên xem bài của người khác (chỉ đọc).

Dữ liệu mẫu: Kiểm tra ngắn "Kiểm tra 15 phút – Đại số chương 2" · mở 24/10 17:30, đóng 24/10 19:00 · 15 phút · 1 lượt · trộn câu · điểm đạt 5/10. Bài tập "BTVN Hệ phương trình" · hạn 25/10 23:59 · 3 lượt, tính lần cuối.
```

---

### TC-10b — Soạn bài: chọn câu hỏi và điểm

```text
Màn hình: Soạn bài, phần chọn câu hỏi và điểm (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên Toán 9 đang soạn bài (đã lưu nháp ở phần thông tin chung) và cần ghép đề từ ngân hàng câu hỏi dùng chung, hoặc soạn nhanh một câu mới. Quan trọng nhất là chọn được nhiều câu cùng lúc, thấy tổng điểm, và không phải nhập lại.

Thông tin cần có:
- Các câu đã có trong bài, theo thứ tự: số thứ tự, trích đề, loại câu, điểm của câu trong bài, phiên bản của câu được dùng ("Phiên bản 3 của câu này").
- Tổng điểm các câu, đặt cạnh điểm tối đa của bài.
- Khi chọn từ ngân hàng: trích đề, loại, môn, topic, tag, độ khó, điểm gợi ý, câu nào đã có trong bài ("Đã thêm"). Lọc được theo môn, loại câu, topic, tag, "Của tôi"; chỉ hiện câu chưa lưu trữ.

Hành động:
- Thêm nhiều câu từ ngân hàng một lần; mỗi câu có điểm riêng, mặc định lấy điểm gợi ý (hoặc 1).
- Soạn câu mới; lưu xong quay lại đây và câu mới tự được thêm vào bài.
- Sửa điểm từng câu tại chỗ, đổi thứ tự câu, bỏ câu khỏi bài (không xóa khỏi ngân hàng).
- Xem đáp án của câu đang chọn để kiểm tra.
- Đính kèm tệp đề (PDF, ảnh) cho học sinh xem.

Quy tắc nghiệp vụ:
- Bài ghi nhớ đúng phiên bản của câu tại lúc chọn; sửa câu trong ngân hàng sau đó không làm đổi bài này. Nói rõ điều này khi chọn: "Bài sẽ giữ đúng phiên bản bạn chọn, dù câu gốc được sửa sau này."
- Muốn dùng phiên bản mới hơn của một câu thì bỏ câu đó và thêm lại.
- Chỉ thêm, sửa, bỏ câu khi bài còn là nháp: "Bài đã phát hành, hãy soạn phiên bản mới để sửa."
- Điểm mỗi câu từ 0 trở lên. Câu nộp ảnh/tệp chưa dùng được: cảnh báo "Câu này học sinh chưa nộp tệp được."
- Cảnh báo mềm khi tổng điểm khác điểm tối đa.

Tình huống đặc biệt: bài chưa có câu nào (gợi ý thêm từ ngân hàng hoặc soạn mới); ngân hàng không có câu khớp bộ lọc; đang thêm nhiều câu (hiện tiến độ); lỗi tải; bài không còn là nháp (chỉ đọc); không có quyền.

Dữ liệu mẫu:
- Câu 1 · "Giải phương trình 2x + 3 = 11" · Một đáp án · 1 điểm · Phiên bản 2
- Câu 2 · "Điền từ còn thiếu: She ___ to school every day." · Điền khuyết · 1 điểm
- Câu 3 · "Phân tích cách giải hệ phương trình bằng phương pháp thế" · Tự luận · 2 điểm. Tổng 4/10 điểm.
```

---

### TC-10c — Soạn bài: chọn đối tượng giao

```text
Màn hình: Soạn bài, phần chọn đối tượng giao (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính, dùng được trên máy tính bảng)

Tình huống: Giáo viên đã chọn xong câu hỏi và cần chọn ai sẽ nhận bài: cả lớp Toán 9A, một khóa học, hoặc vài học sinh riêng lẻ (ví dụ học sinh nghỉ bù). Quan trọng nhất là chọn nhanh, biết chính xác bao nhiêu học sinh sẽ nhận bài, và không giao nhầm.

Thông tin cần có:
- Các đối tượng đã thêm: loại (lớp, học sinh riêng lẻ, khóa học), tên, thời điểm giao, nhãn "Đã rút" nếu đã rút.
- Số học sinh sẽ nhận bài (đếm theo học sinh thực tế, không đếm trùng).
- Nguồn để chọn: các lớp giáo viên đang dạy, các khóa học giáo viên phụ trách, và học sinh trong các lớp mình dạy.

Hành động:
- Mỗi lần thêm đúng một đối tượng: một lớp, hoặc một học sinh, hoặc một khóa học; thêm nhiều lần để giao cho nhiều nhóm.
- Rút một đối tượng đã thêm.
- Lưu nháp và thoát, quay lại, tiếp tục.

Quy tắc nghiệp vụ:
- Giáo viên chỉ giao được cho lớp mình dạy, khóa học mình phụ trách, học sinh đang học lớp mình; quản trị viên không giới hạn. Thông điệp khi vượt quyền: "Lớp này không thuộc phân công của bạn." / "Khóa học này không thuộc quyền sử dụng của bạn." / "Học sinh này không thuộc lớp bạn phụ trách."
- Một học sinh nhận bài qua hai đường (cả lớp và cá nhân) vẫn chỉ có một bài.
- Chỉ học sinh đang học trong lớp hoặc khóa tại thời điểm giao mới nhận bài; học sinh vào lớp sau đó không tự có bài.
- Thêm đối tượng khi bài còn nháp thì học sinh chưa thấy và chưa được báo; thêm khi bài đã phát hành thì học sinh nhận ngay và được báo.
- Rút một đối tượng: học sinh không còn đường nào nhận bài sẽ bị rút khỏi bài, nhưng bài đã nộp vẫn được giữ.
- Bài chưa giao cho ai thì chưa nên phát hành: nhắc rõ "Chưa giao cho ai".

Tình huống đặc biệt: chưa có đối tượng nào; lớp không có học sinh; lỗi tải; đang thêm; không có quyền với lớp được chọn; bài đã phát hành (thêm đối tượng có hiệu lực ngay); bài chỉ đọc.

Dữ liệu mẫu:
- Lớp Toán 9A · 25 học sinh · giao 22/10 20:15
- Học sinh Nguyễn Minh Khôi (Toán 9B) · giao riêng
- Khóa Ôn thi vào 10 · 18 học sinh · đã rút
```

---

### TC-10d — Soạn bài: xem trước và phát hành

```text
Màn hình: Soạn bài, phần xem trước và phát hành (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên đã điền xong thông tin, câu hỏi, đối tượng và muốn rà lại lần cuối trước khi phát hành. Quan trọng nhất là chính xác: phát hành xong thì nội dung bị cố định và học sinh được báo ngay, nên phải thấy rõ còn lỗi gì chưa ổn.

Thông tin cần có, tóm tắt toàn bài:
- Thông tin chung: tên, loại, giờ mở, hạn nộp, giờ đóng, thời lượng, số lượt, trộn câu, trộn đáp án, cho xem đáp án.
- Các câu kèm điểm từng câu và tổng điểm.
- Các đối tượng kèm số học sinh sẽ nhận.
- Danh sách điều cần xem lại: chưa giao cho ai; tổng điểm khác điểm tối đa; điểm đạt cao hơn điểm tối đa; mốc thời gian sai thứ tự; có câu nộp ảnh/tệp chưa dùng được. Mỗi điều cần chỉ rõ chỗ phải sửa.

Hành động:
- Xem bài như học sinh sẽ thấy.
- Quay lại sửa từng phần.
- Phát hành.
- Lưu nháp và thoát.

Quy tắc nghiệp vụ:
- Trước khi phát hành phải xác nhận: "Phát hành cho 25 học sinh của Toán 9A. Nội dung câu hỏi và đáp án sẽ được cố định, muốn sửa phải tạo phiên bản mới. Hệ thống sẽ báo cho học sinh."
- Bài chưa có câu hỏi thì không phát hành được: "Bài chưa có câu hỏi nào, hãy thêm câu trước khi phát hành."
- Bài đã phát hành hoặc đã hủy thì không phát hành lại được: "Bài này không còn ở trạng thái nháp nên không phát hành được."
- Bài chưa giao cho ai vẫn về mặt kỹ thuật phát hành được, nhưng phải cảnh báo rõ "Chưa giao cho ai" và yêu cầu người dùng chủ động xác nhận.

Tình huống đặc biệt: tóm tắt đầy đủ không còn điều cần xem lại; còn nhiều điều cần xem lại; đang phát hành; phát hành thành công (cho biết học sinh đã được báo); phát hành lỗi; bài đã phát hành hoặc đã hủy (chỉ đọc); không có quyền (giáo viên xem bài của người khác).

Dữ liệu mẫu: "Kiểm tra 15 phút – Đại số chương 2" · mở 24/10 17:30, đóng 24/10 19:00 · 15 phút · 10 câu, tổng 10 điểm · giao cho Toán 9A (25 học sinh) · điểm đạt 5/10. Một điều cần xem lại: "Điểm tối đa của bài là 12 nhưng các câu cộng lại là 10."
```

---

### TC-10e — Bài đã phát hành: đóng, mở lại, hủy, phiên bản mới

```text
Màn hình: Quản lý vòng đời bài đã phát hành (giáo viên là người tạo bài, hoặc quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Bài kiểm tra đã phát hành và giáo viên cần xử lý tình huống thực tế: đóng bài khi hết giờ, mở lại cho vài học sinh nghỉ ốm, hủy bài đưa nhầm, hoặc sửa đề bằng cách soạn phiên bản mới. Quan trọng nhất là hiểu rõ hậu quả trước khi bấm và không thao tác nhầm.

Thông tin cần có: trạng thái bài (Đã phát hành, Đã đóng, Đã hủy); phiên bản hiện tại và trạng thái của nó; có bản nháp đang soạn hay không; lý do hủy nếu đã hủy; các lớp, khóa học, học sinh có thể chọn khi mở lại.

Hành động, kèm điều kiện:
- Đóng bài: chỉ khi bài đang Đã phát hành. Xác nhận: "Học sinh sẽ không bắt đầu lượt mới được. Lượt đang làm vẫn tiếp tục."
- Mở lại: chỉ khi bài đang Đã đóng. Phải chọn đúng một nhóm: một số học sinh cụ thể, hoặc một lớp, hoặc một khóa học. Học sinh chưa hoàn thành thuộc nhóm đó được làm lại.
- Hủy bài: được ở mọi trạng thái trừ đã hủy. Bắt buộc nhập lý do. Xác nhận: "Bài nộp và lịch sử vẫn được giữ; bài không còn tính điểm."
- Soạn phiên bản mới: chỉ khi bài chưa hủy và chưa có bản nháp đang mở. Sao chép cấu hình và câu hỏi từ bản hiện tại thành bản nháp mới để sửa, rồi phát hành.

Quy tắc nghiệp vụ:
- Mở lại không kéo dài giờ đóng bài. Nếu giờ đóng đã qua thì học sinh vẫn không bắt đầu được; muốn kéo dài phải soạn phiên bản mới với giờ đóng mới. Nói rõ điều này khi mở lại, không hứa điều khác.
- Mở lại đưa cả bài về Đã phát hành, nên học sinh khác còn được giao bài cũng có thể làm lại nếu còn trong giờ.
- Soạn phiên bản mới chưa hứa chắc học sinh đã được giao có nhận bản sửa; đừng đưa lời hứa này vào thông điệp.
- Các hành động ẩn hẳn khi người dùng không phải người tạo bài hoặc quản trị viên, hoặc khi sai trạng thái.
- Lỗi thân thiện: "Chỉ đóng được bài đang phát hành." / "Chỉ mở lại được bài đang đóng." / "Đã có bản nháp đang soạn, hãy hoàn tất bản đó trước." / "Vui lòng nhập lý do hủy" / "Chọn đúng một nhóm: học sinh, lớp hoặc khóa học."

Tình huống đặc biệt: bài đã đóng, bài đã hủy (chỉ đọc, hiện lý do), bài có bản nháp mới, đang xử lý, thất bại, không có quyền.

Dữ liệu mẫu: "Kiểm tra 15 phút – Đại số chương 2" · Đã đóng · mở lại cho Nguyễn Minh Khôi và Trần Thu Hà. Lý do hủy: "Đề in nhầm chương 3".
```

---

### TC-11 — Chi tiết bài

```text
Màn hình: Chi tiết bài đánh giá (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên Toán mở một bài đã giao để xem tiến độ: ai chưa nộp, ai cần gia hạn riêng, rồi đóng bài hoặc chuyển sang chấm. Quan trọng nhất là nắm tình hình cả lớp trong một lần xem, và thao tác đúng với từng học sinh.

Thông tin cần có:
- Tên bài, loại, trạng thái bài (Nháp, Đã phát hành, Đã đóng, Đã hủy), phiên bản hiện tại và trạng thái của nó, người tạo, lý do hủy (nếu đã hủy).
- Cấu hình: hướng dẫn, giờ mở, hạn nộp, giờ đóng, thời lượng, số lượt, cách tính điểm, trộn câu/đáp án, điểm tối đa và điểm đạt, cho xem đáp án/lời giải/điểm từng câu, buổi học gắn kèm.
- Các câu hỏi theo thứ tự: loại, điểm, trích đề, phiên bản của câu.
- Các đối tượng đã giao (lớp, học sinh, khóa học) kèm thời điểm giao, nhãn "Đã rút".
- Học sinh của bài: tên, "Đã giao" hoặc "Đã rút", hạn nộp riêng, số lượt riêng.
- Tóm tắt tiến độ: đã nộp bao nhiêu trên tổng số, chờ chấm bao nhiêu, đã công bố bao nhiêu.

Hành động, kèm điều kiện (chỉ người tạo bài hoặc quản trị viên; ẩn hẳn với người khác):
- Sửa và phát hành khi bài đang Nháp; soạn phiên bản mới khi đã phát hành hoặc đã đóng.
- Đóng, mở lại, hủy bài (hủy phải nhập lý do).
- Giao thêm, rút đối tượng.
- Điều chỉnh riêng cho một học sinh đang được giao: đặt hạn nộp riêng (nộp sau hạn chung nhưng trước hạn riêng thì không bị tính trễ), đặt số lượt riêng (cũng là cách cho học sinh làm lại bài tập cần sửa), hoặc xóa điều chỉnh.
- Xem bài nộp, chuyển sang chấm.

Quy tắc nghiệp vụ:
- Hạn riêng chỉ ảnh hưởng nhãn "nộp trễ", không kéo dài giờ đóng bài chung. Nói rõ: "Hạn riêng không kéo dài giờ đóng bài."
- Số lượt riêng tối thiểu 1: "Số lượt tối thiểu là 1".
- Giáo viên khác chỉ có lớp trong đối tượng thì xem và chấm được nhưng không sửa.

Tình huống đặc biệt: đang tải; bài nháp (chỉ đọc); đã đóng; đã hủy kèm lý do; có bản nháp mới trong khi bài đang chạy; chưa giao cho ai; chưa có học sinh; bài của người khác; lỗi tải; không có quyền hoặc bài không còn tồn tại.

Dữ liệu mẫu: "Kiểm tra 15 phút – Đại số chương 2" · Đã phát hành · Toán 9A · 25 học sinh. Nguyễn Minh Khôi: hạn riêng 26/10 20:00, 2 lượt. Trần Thu Hà: đã rút (chuyển lớp).
```

---

### TC-12 — Bài nộp của một bài

```text
Màn hình: Bài nộp của một bài (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính, dùng được trên máy tính bảng)

Tình huống: Học sinh đã nộp bài kiểm tra. Giáo viên muốn biết ai đã nộp, ai nộp trễ, bài nào còn chờ chấm, rồi chấm lần lượt hoặc công bố điểm cả loạt. Quan trọng nhất là xem được cả lớp 30 em mà không cuộn quá nhiều và nhận ra ngay bài cần chú ý.

Thông tin cần có, mỗi bài nộp gồm: tên học sinh, lượt làm thứ mấy, giờ nộp, nhãn "Nộp trễ", trạng thái bài nộp (Đã nhận, Chờ chấm, Đã chấm, Bị vô hiệu), trạng thái chấm (Chưa chấm, Chấm dở, Chấm xong), điểm trên điểm tối đa và phần trăm (để trống khi chưa chấm xong), nhãn Đạt hoặc Chưa đạt (chỉ khi bài có điểm đạt), trạng thái công bố (Chưa công bố, Đã công bố).

Hành động:
- Lọc theo trạng thái chấm và trạng thái công bố.
- Mở một bài nộp để chấm.
- Công bố ngay từng kết quả; rút kết quả đã công bố.
- Công bố hàng loạt mọi kết quả đã chấm xong mà chưa công bố.

Quy tắc nghiệp vụ:
- Giáo viên chỉ thấy bài nộp của học sinh thuộc lớp hoặc khóa mình; người tạo bài và quản trị viên thấy hết.
- Công bố hàng loạt phải xác nhận trước: "Học sinh và phụ huynh sẽ xem được điểm. Hệ thống sẽ báo cho họ." Kết quả sau đó nêu rõ số đã công bố và số bị bỏ qua, ví dụ "Đã công bố 18 kết quả. Bỏ qua 3 bài vì chưa chấm xong." Bài bị vô hiệu không bao giờ được công bố.
- Công bố một bài chưa chấm xong không được: "Còn câu chưa chấm, hãy chấm xong rồi công bố." Công bố lại bài đã công bố không báo lỗi.
- Kết quả được công bố theo cách giáo viên đã chọn khi soạn bài (tự động khi chấm xong, hẹn giờ, hoặc giáo viên tự công bố); mỗi kết quả phải cho biết rõ đang chờ công bố theo cách nào (ví dụ "Sẽ công bố lúc 20:00 ngày 26/10").

Tình huống đặc biệt: đang tải; chưa ai nộp (nhắc hạn nộp); lọc không ra kết quả; lỗi tải; không có quyền; bài bị vô hiệu (làm mờ, ghi "Đã cấp lại lượt"); đang công bố hàng loạt; kết quả công bố hàng loạt.

Dữ liệu mẫu:
- Nguyễn Minh Khôi · Lượt 1 · nộp 24/10 18:12 · Chờ chấm · điểm trống
- Lê Gia Hân · Lượt 1 · nộp 24/10 18:40 · Đã chấm · 8,5/10 (85%) · Đạt · Chưa công bố
- Phạm Quốc Bảo · nộp trễ 25/10 00:20 · Đã chấm · 6/10 · Đã công bố
```

---

### TC-13a — Chấm bài: chấm điểm

```text
Màn hình: Chấm bài của một học sinh, phần chấm điểm (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính hoặc máy tính bảng)

Tình huống: Giáo viên Toán đọc bài làm của một học sinh, cho điểm các câu tự luận, rồi chuyển ngay sang bài kế tiếp. Quan trọng nhất là đọc đề, bài làm và đáp án đúng cùng lúc để chấm chính xác và nhanh; mỗi điểm lưu ngay khi nhập.

Thông tin cần có:
- Học sinh, tên bài, lượt thứ mấy, giờ nộp, nhãn "Nộp trễ", trạng thái bài nộp.
- Trạng thái kết quả: Chấm xong, Chấm dở hoặc Chưa chấm; Đã công bố hoặc Chưa công bố; điểm tổng, phần trăm, Đạt hoặc Chưa đạt (để trống khi chưa chấm xong).
- Lời nhắc cố định: "Học sinh và phụ huynh chỉ thấy điểm khi kết quả đã công bố."
- Với từng câu của đúng phiên bản bài học sinh đã làm: thứ tự, loại câu, điểm tối đa, đề bài, bài làm của học sinh, điểm hiện có và cách chấm (Tự động hoặc Giáo viên chấm), đáp án đúng để đối chiếu.
- Điểm tổng chấm tay cho cả bài (dùng khi chấm một lần, không theo câu).

Hành động:
- Nhập hoặc sửa điểm từng câu; câu đã tự chấm vẫn sửa được (chuyển thành Giáo viên chấm). Điểm tổng và trạng thái chấm cập nhật ngay sau mỗi lần lưu.
- Nhập điểm tổng thay cho điểm từng câu.
- Lưu và sang bài chưa chấm xong kế tiếp; hết bài thì hiện "Đã chấm hết. Công bố cả lớp?".
- Quản trị viên cấp lại lượt cho bài kiểm tra hoặc bài thi, bắt buộc nhập lý do. Giáo viên thấy dòng "Cần cấp lại lượt? Nhờ quản trị viên xử lý".

Quy tắc nghiệp vụ:
- Điểm mỗi câu từ 0 đến điểm tối đa của câu: "Điểm phải từ 0 đến {điểm tối đa}".
- Đã có điểm tổng thì điểm tổng là kết quả cuối, điểm từng câu không còn được cộng. Cảnh báo: "Điểm tổng đang thay cho điểm từng câu." Chọn một cách chấm cho mỗi bài, không trộn.
- Sửa điểm khi kết quả đã công bố thì bắt buộc nhập lý do sửa, kèm cảnh báo "Học sinh và phụ huynh đang thấy điểm này. Việc sửa được ghi lại." Chưa công bố thì lý do tùy chọn. Thiếu lý do: "Vui lòng nhập lý do sửa điểm".
- Câu bỏ trống tự được 0 điểm: hiện "Không trả lời — 0 điểm".
- Chỉ chấm được câu chấm tay đã có bài làm.
- Kết quả có thể tự công bố ngay khi chấm xong câu cuối hoặc theo giờ hẹn của bài; nhãn công bố phải thể hiện đúng cách công bố của bài đó.

Tình huống đặc biệt: đang tải; chưa chấm câu nào; chấm dở; chấm xong; bài nộp trễ; bài bị vô hiệu (chỉ đọc, hiện "Lượt này đã bị hủy: <lý do>" cùng đường dẫn tới lượt thay thế, mọi hành động chấm bị ẩn); câu nộp tệp chưa xem được ("Chưa xem được tệp bài làm"); điểm ngoài khoảng; lỗi lưu; không có quyền.

Dữ liệu mẫu: Lê Gia Hân · Lượt 1 · nộp 24/10 18:40 · Câu 7 (Tự luận, 2 điểm) "Phân tích cách giải hệ phương trình bằng phương pháp thế" · bài làm 4 dòng · điểm 1,5/2. Tổng 8,5/10 · Đạt · Chưa công bố.
```

---

### TC-13b — Chấm bài: nhận xét và công bố kết quả

```text
Màn hình: Chấm bài của một học sinh, phần nhận xét và công bố kết quả (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính hoặc máy tính bảng)

Tình huống: Giáo viên Toán đã chấm điểm cho Lê Gia Hân và muốn viết nhận xét cho từng câu hoặc cho cả bài, rồi công bố điểm để học sinh và phụ huynh xem. Quan trọng nhất là không hiểu nhầm: "đã công bố nhận xét" không có nghĩa học sinh đã thấy.

Thông tin cần có:
- Tóm tắt kết quả: điểm tổng, trạng thái chấm (Chấm xong, Chấm dở, Chưa chấm), trạng thái công bố (Đã công bố, Chưa công bố), Đạt hoặc Chưa đạt.
- Các nhận xét của bài nộp, nhóm theo "Chung cho cả bài" và theo từng câu. Mỗi nhận xét: nội dung, người viết, trạng thái (Nháp, Đã công bố, Đã rút), quyền xem (Chỉ giáo viên, Học sinh và phụ huynh, Chỉ phụ huynh), giờ công bố.

Hành động:
- Thêm nhận xét: chọn gắn với cả bài hoặc một câu; nhập nội dung; chọn quyền xem. Nhận xét mới luôn là Nháp.
- Sửa nội dung nhận xét (sửa nhận xét đã công bố được ghi lại lịch sử).
- Công bố nhận xét (khi đang Nháp).
- Rút lại nhận xét (khi đã công bố).
- Công bố kết quả (chỉ khi đã chấm xong).
- Rút kết quả (khi đã công bố).

Quy tắc nghiệp vụ:
- Quyền xem chọn lúc tạo và không đổi được sau đó. Giải thích ba lựa chọn bằng lời: "Chỉ giáo viên (ghi chú nội bộ)", "Học sinh và phụ huynh", "Chỉ phụ huynh (học sinh không thấy)".
- Trạng thái nhận xét chỉ đi một chiều: Nháp, rồi Đã công bố, rồi Đã rút. Nhận xét đã rút là cuối cùng, muốn đăng lại phải viết nhận xét mới. Rút lại cần xác nhận: "Học sinh và phụ huynh sẽ không còn thấy nhận xét này."
- Học sinh và phụ huynh chỉ thấy nhận xét khi nhận xét đã công bố, kết quả đã công bố, và quyền xem phù hợp. Ghi chú này đặt ngay cạnh hành động công bố nhận xét.
- Kết quả chưa công bố thì học sinh và phụ huynh không thấy điểm.
- Công bố kết quả phải xác nhận: "Học sinh và phụ huynh sẽ xem được điểm và nhận xét đã công bố. Hệ thống sẽ báo cho họ." Rút kết quả phải xác nhận: "Điểm sẽ biến mất khỏi màn hình của học sinh và phụ huynh và không còn tính vào tiến độ."
- Chưa chấm xong thì không công bố được: "Còn câu chưa chấm, hãy chấm xong rồi công bố." Nhận xét thiếu nội dung hoặc quyền xem: không lưu được. Chuyển trạng thái sai: "Nhận xét này không thể chuyển sang trạng thái đó."
- Kết quả có thể đã tự công bố ngay khi chấm xong câu cuối; nhãn công bố phải theo thực tế.

Tình huống đặc biệt: chưa có nhận xét; nhận xét ở cả ba trạng thái; kết quả chưa chấm xong (không công bố được); đã công bố; đã rút; bài bị vô hiệu (chỉ đọc); đang lưu; lỗi; không có quyền.

Dữ liệu mẫu: nhận xét nháp ở Câu 7: "Lập luận đúng, thiếu phần thử lại nghiệm" · quyền xem Học sinh và phụ huynh. Nhận xét chung đã công bố: "Cố gắng lắm, tuần sau em ôn lại hệ phương trình nhé" · chỉ giáo viên: "Gia đình cần nhắc em kiểm tra lại bài".
```

---

### TC-07 — Ngân hàng câu hỏi

```text
Màn hình: Ngân hàng câu hỏi (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên Toán 9 muốn tìm lại một câu đã soạn từ học kỳ trước để dùng cho bài kiểm tra tuần này, hoặc soạn câu mới khi chưa có. Màn này cũng dùng khi đang ghép đề, lúc đó giáo viên cần chọn nhiều câu một lần. Quan trọng nhất là tìm nhanh trong kho lớn dùng chung.

Thông tin cần có, mỗi câu gồm: trích đề (cắt khoảng hai dòng), loại câu, môn, topic, tag, độ khó, điểm gợi ý, số phiên bản, nhãn "Của tôi", nhãn "Đã lưu trữ", người tạo. Có phân trang.

Hành động:
- Tìm theo nội dung đề, gõ không dấu cũng khớp ("phuong trinh" tìm ra "phương trình").
- Lọc theo môn, loại câu (Một đáp án, Nhiều đáp án, Đúng/Sai, Điền khuyết, Trả lời ngắn, Tự luận, Nối, Sắp xếp, Nộp ảnh hoặc tệp), topic, tag (gõ để gợi ý), "Của tôi", trạng thái (đang dùng hoặc đã lưu trữ; mặc định đang dùng).
- Soạn câu hỏi mới; mở một câu để xem hoặc sửa.
- Lưu trữ một câu ngay trên danh sách, chỉ với câu của mình hoặc quản trị viên.
- Khi mở để ghép đề: chọn nhiều câu cùng lúc, đặt điểm cho từng câu (mặc định lấy điểm gợi ý hoặc 1), thêm n câu vào bài; câu đã có trong bài hiện "Đã thêm"; câu đã lưu trữ không được chọn.

Quy tắc nghiệp vụ:
- Mọi giáo viên và quản trị viên xem được toàn bộ ngân hàng; chỉ người tạo hoặc quản trị viên mới sửa, gắn topic/tag, lưu trữ.
- Lưu trữ phải xác nhận: "Câu hỏi sẽ không còn xuất hiện khi chọn câu cho bài mới. Các bài đã dùng vẫn giữ nguyên." Không có xóa hẳn. Lưu trữ câu của người khác: "Bạn chỉ lưu trữ được câu do mình tạo."
- Sửa một câu tạo ra phiên bản mới; bài đã giao trước đó vẫn giữ nội dung cũ.

Tình huống đặc biệt: đang tải; chưa có câu nào (mời "Soạn câu hỏi đầu tiên"); lọc không ra kết quả ("Không có câu nào khớp. Xóa bộ lọc"); lỗi tải; không có quyền; câu đã lưu trữ; đang ở chế độ chọn nhiều.

Dữ liệu mẫu:
- "Giải phương trình 2x + 3 = 11" · Một đáp án · Toán · Phương trình bậc nhất · "Học kỳ 1", "Cơ bản" · v2 · 1 điểm · Của tôi
- "Điền từ còn thiếu: She ___ to school every day." · Điền khuyết · Anh · v1
- "Phân tích nhân vật Lão Hạc (khoảng 15 dòng)" · Tự luận · Văn 9 · 3 điểm
```

---

### TC-08a — Soạn câu hỏi: nhóm chọn đáp án

```text
Màn hình: Soạn câu hỏi chọn đáp án, gồm ba loại: Một đáp án đúng, Nhiều đáp án đúng, Đúng/Sai (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên Toán 9 soạn một câu trắc nghiệm để lưu vào ngân hàng và dùng lại nhiều bài. Quan trọng nhất là nhập đáp án nhanh và không thể lưu một câu thiếu đáp án đúng, vì lưu sai thì mọi học sinh bị 0 điểm.

Thông tin cần nhập, chung cho cả ba loại: loại câu (chọn khi tạo mới); môn (tùy chọn, không đổi được sau khi tạo); đề bài (bắt buộc, xuống dòng được); hướng dẫn làm bài (tùy chọn, học sinh thấy); điểm gợi ý; độ khó (Dễ, Trung bình, Khó); topic và tag (gõ để gợi ý, gõ tên chưa có thì tự tạo mới; topic chỉ gắn khi đã chọn môn, chưa chọn môn thì nhắc "Chọn môn để gắn topic"); tệp đính kèm tùy chọn (hình minh họa, đề PDF/ảnh; PDF, Word, JPEG, PNG, ZIP, tối đa 10 MB). Cách chấm hiển thị là "Tự động", không sửa tay. Có phần xem trước theo góc nhìn học sinh.

Theo từng loại:
- Một đáp án: các lựa chọn (nhập, thêm, xóa, đổi thứ tự), chọn đúng một đáp án đúng. Tối thiểu 2 lựa chọn.
- Nhiều đáp án: như trên nhưng đánh dấu một hoặc nhiều đáp án đúng, ít nhất 1. Ghi chú: "Học sinh phải chọn đúng và đủ mới được điểm; chưa có điểm từng phần."
- Đúng/Sai: hai lựa chọn "Đúng" và "Sai" tự có sẵn, giáo viên chỉ chọn "Đáp án là: Đúng / Sai".

Hành động: lưu; xem câu hỏi ở chế độ chỉ đọc nếu của người khác; lưu trữ câu của mình; tải tệp đính kèm.

Quy tắc nghiệp vụ:
- Trước khi lưu một câu đã có: "Lưu sẽ tạo phiên bản mới (v3). Bài đã giao trước đó vẫn dùng nội dung cũ." Tệp đính kèm phải gắn lại cho phiên bản mới.
- Chặn lưu khi thiếu lựa chọn, thiếu đáp án đúng, hoặc có hơn một đáp án đúng ở loại Một đáp án. Lỗi gắn đúng chỗ: "Vui lòng chọn loại câu hỏi", "Vui lòng nhập đề bài", "Lựa chọn thứ 3 đang trống".
- Sửa câu của người khác không được: "Chỉ người tạo câu hỏi hoặc quản trị viên được sửa."
- Đổi loại câu khi đang sửa làm mất đáp án, cần xác nhận.
- Định dạng tệp sai: "Định dạng tệp không được hỗ trợ."

Tình huống đặc biệt: trống theo từng loại; đang lưu; lỗi theo trường; chỉ đọc; đã lưu trữ; đang tải tệp; đã đính tệp; không có quyền.

Dữ liệu mẫu: Một đáp án: "Nghiệm của 2x + 3 = 11 là?" · A. 3 · B. 4 (đúng) · C. 5 · D. 7. Đúng/Sai: "Số 17 là số nguyên tố" · đáp án Đúng.
```

---

### TC-08b — Soạn câu hỏi: nhóm trả lời bằng chữ

```text
Màn hình: Soạn câu hỏi trả lời bằng chữ, gồm ba loại: Điền khuyết, Trả lời ngắn, Tự luận (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên soạn câu mà học sinh phải tự gõ câu trả lời, để lưu vào ngân hàng dùng lại. Với Điền khuyết và Trả lời ngắn, hệ thống tự chấm bằng cách so với các đáp án chấp nhận; với Tự luận, giáo viên chấm tay. Quan trọng nhất là nhập đủ các cách viết đúng để học sinh không bị chấm oan.

Thông tin cần nhập, chung cho cả ba loại: loại câu (chọn khi tạo mới); môn (tùy chọn, không đổi được sau khi tạo); đề bài (bắt buộc, xuống dòng được); hướng dẫn làm bài (tùy chọn); điểm gợi ý; độ khó (Dễ, Trung bình, Khó); topic và tag (gõ để gợi ý, tên chưa có thì tự tạo; topic chỉ gắn khi đã chọn môn); tệp đính kèm tùy chọn (PDF, Word, JPEG, PNG, ZIP, tối đa 10 MB). Cách chấm hiển thị là "Tự động" hoặc "Giáo viên chấm", không sửa tay. Có phần xem trước theo góc nhìn học sinh.

Theo từng loại:
- Điền khuyết: đề bài đánh dấu chỗ trống bằng "___"; các đáp án chấp nhận (mỗi đáp án một dòng, thêm/xóa được, ít nhất 1); lựa chọn "Phân biệt chữ hoa/chữ thường" (mặc định tắt). Cảnh báo cố định: "Mỗi câu chỉ nên có một chỗ trống."
- Trả lời ngắn: như Điền khuyết, nhãn "Các câu trả lời được chấp nhận (thêm cả các cách viết khác nhau)", gợi ý thêm biến thể như "x = 2" và "x=2".
- Tự luận: không có đáp án. Hướng dẫn làm bài được học sinh nhìn thấy khi làm, nên đó là hướng dẫn chứ không phải đáp án mẫu; tiêu chí chấm cho giáo viên có thể ghi trong hướng dẫn. Ghi chú: "Giáo viên chấm tay."

Hành động: lưu; chỉ đọc nếu câu của người khác; lưu trữ câu của mình; tải tệp đính kèm.

Quy tắc nghiệp vụ:
- Câu trả lời của học sinh được so với đáp án chấp nhận sau khi cắt khoảng trắng đầu và cuối; chưa bỏ dấu tiếng Việt khi so, nên nên thêm cả cách viết có dấu và không dấu.
- Chặn lưu khi đáp án chấp nhận rỗng. Lỗi gắn đúng chỗ: "Vui lòng nhập đề bài", "Cần ít nhất một đáp án chấp nhận".
- Trước khi lưu câu đã có: "Lưu sẽ tạo phiên bản mới (v2). Bài đã giao trước đó vẫn dùng nội dung cũ."
- Sửa câu của người khác không được: "Chỉ người tạo câu hỏi hoặc quản trị viên được sửa."

Tình huống đặc biệt: trống theo từng loại; đang lưu; lỗi theo trường; chỉ đọc; đã lưu trữ; đang tải tệp; đã đính tệp; không có quyền.

Dữ liệu mẫu: Điền khuyết: "The capital of Viet Nam is ___." · chấp nhận "Hanoi", "Ha Noi", "Hà Nội". Trả lời ngắn: "Nghiệm của x - 2 = 0?" · chấp nhận "x = 2", "x=2", "2". Tự luận: "Phân tích nhân vật Lão Hạc (khoảng 15 dòng)" · Văn 9.
```

---

### TC-08c — Soạn câu hỏi: nhóm cấu trúc

```text
Màn hình: Soạn câu hỏi có cấu trúc, gồm hai loại: Nối cặp, Sắp xếp thứ tự (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên soạn câu nối các cặp (ví dụ nối công thức với tên gọi) hoặc câu sắp xếp các mục theo thứ tự đúng (ví dụ các ý của một chứng minh). Hệ thống tự chấm theo kiểu tất cả hoặc không: sai một phần là 0 điểm. Quan trọng nhất là giáo viên nhập đáp án đúng rõ ràng và biết học sinh sẽ thấy gì.

Thông tin cần nhập, chung cho cả hai loại: loại câu (chọn khi tạo mới); môn (tùy chọn, không đổi được sau khi tạo); đề bài (bắt buộc); hướng dẫn làm bài (tùy chọn); điểm gợi ý; độ khó (Dễ, Trung bình, Khó); topic và tag (gõ để gợi ý, tên chưa có thì tự tạo; topic chỉ gắn khi đã chọn môn); tệp đính kèm tùy chọn (PDF, Word, JPEG, PNG, ZIP, tối đa 10 MB). Cách chấm hiển thị là "Tự động", không sửa tay. Có phần xem trước đúng như học sinh thấy (đã xáo trộn).

Theo từng loại:
- Nối cặp: các cặp đúng, mỗi cặp gồm một vế trái và một vế phải. Mỗi vế trái nối với đúng một vế phải. Học sinh sẽ thấy hai nhóm vế được xáo trộn riêng; cặp đúng bị ẩn khỏi học sinh.
- Sắp xếp thứ tự: các mục, sắp theo thứ tự đúng (đổi thứ tự được, thêm/xóa được), ít nhất 2 mục. Học sinh thấy các mục đã xáo trộn.

Hành động: lưu; chỉ đọc nếu câu của người khác; lưu trữ câu của mình; tải tệp đính kèm.

Quy tắc nghiệp vụ:
- Chặn lưu khi một vế để trống, khi còn vế trái chưa có vế phải, hoặc khi sắp xếp có ít hơn 2 mục. Lỗi gắn đúng chỗ: "Vui lòng nhập đề bài", "Cặp thứ 2 chưa đủ hai vế", "Cần ít nhất 2 mục để sắp xếp".
- Trước khi lưu câu đã có: "Lưu sẽ tạo phiên bản mới (v2). Bài đã giao trước đó vẫn dùng nội dung cũ."
- Sửa câu của người khác không được: "Chỉ người tạo câu hỏi hoặc quản trị viên được sửa."
- Đổi loại câu khi đang sửa làm mất đáp án, cần xác nhận.

Tình huống đặc biệt: trống theo từng loại; đang lưu; lỗi theo trường; chỉ đọc; đã lưu trữ; đang tải tệp; đã đính tệp; không có quyền.

Dữ liệu mẫu: Nối cặp: "Nối hàm số với đồ thị của nó" · y = 2x → Đường thẳng đi qua gốc tọa độ; y = x² → Parabol. Sắp xếp: "Sắp xếp các việc khi giải phương trình 2x + 3 = 11" · 1) Chuyển 3 sang vế phải · 2) Tính 11 − 3 = 8 · 3) Chia hai vế cho 2 · 4) Kết luận x = 4.
```

---

### TC-08d — Soạn câu hỏi: nộp tệp

```text
Màn hình: Soạn câu hỏi nộp ảnh hoặc tệp (giáo viên, kể cả quản trị viên; thiết bị ưu tiên: máy tính)

Tình huống: Giáo viên Toán muốn học sinh chụp bài giải viết tay và tải lên, rồi tự mình chấm. Câu này không có đáp án; giáo viên chấm tay. Tính năng học sinh nộp tệp còn đang hoàn thiện, nên người dùng cần biết rõ điều đó ngay khi soạn để không tạo câu chưa dùng được.

Thông tin cần nhập: loại câu "Nộp ảnh hoặc tệp" (chọn khi tạo mới); môn (tùy chọn, không đổi được sau khi tạo); đề bài (bắt buộc); hướng dẫn nộp tệp (ví dụ "Chụp bài giải và tải lên"; học sinh thấy khi làm bài); điểm gợi ý; độ khó (Dễ, Trung bình, Khó); topic và tag (gõ để gợi ý, tên chưa có thì tự tạo; topic chỉ gắn khi đã chọn môn); tệp đính kèm tùy chọn từ phía giáo viên (hình minh họa, đề PDF/ảnh; PDF, Word, JPEG, PNG, ZIP, tối đa 10 MB). Cách chấm hiển thị là "Giáo viên chấm", không sửa tay. Có phần xem trước theo góc nhìn học sinh.

Hành động: lưu; chỉ đọc nếu câu của người khác; lưu trữ câu của mình; tải tệp đính kèm.

Quy tắc nghiệp vụ:
- Phải có thông báo rõ ngay trong lúc soạn: "Tính năng nộp tệp đang hoàn thiện, học sinh chưa nộp được tệp cho câu này." Câu loại này không nên được đưa vào bài đã giao cho học sinh cho tới khi tính năng sẵn sàng.
- Giáo viên đặt số tệp tối đa học sinh được nộp cho câu này (từ 1 đến 5 tệp); mỗi tệp tối đa 10 MB.
- Trước khi lưu câu đã có: "Lưu sẽ tạo phiên bản mới (v2). Bài đã giao trước đó vẫn dùng nội dung cũ." Tệp đính kèm phải gắn lại cho phiên bản mới.
- Lỗi gắn đúng chỗ: "Vui lòng nhập đề bài". Định dạng tệp sai: "Định dạng tệp không được hỗ trợ."; tên tệp quá dài: "Tên tệp quá dài (tối đa 255 ký tự)."
- Sửa câu của người khác không được: "Chỉ người tạo câu hỏi hoặc quản trị viên được sửa."

Tình huống đặc biệt: trống; đang lưu; lỗi theo trường; chỉ đọc; đã lưu trữ; đang tải tệp; đã đính tệp; không có quyền; trạng thái "chưa dùng được" của loại câu này.

Dữ liệu mẫu: "Giải hệ phương trình x + y = 5, x − y = 1 và chụp bài giải" · hướng dẫn: "Chụp rõ cả trang vở, ghi rõ họ tên" · Toán 9 · 2 điểm.
```
