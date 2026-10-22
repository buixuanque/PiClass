# Bộ prompt — Cổng Teacher: vận hành lớp

Gồm 10 prompt (9 màn và 1 prompt Ghép) cho các màn vận hành lớp của giáo viên: buổi học và điểm danh, lịch dạy, tổng quan, lớp của tôi, chi tiết lớp (tách phần lịch sử thay đổi thành prompt riêng), đơn xin phép, tiến độ học sinh, khóa học của tôi. Thứ tự vẽ gợi ý đúng theo mục lục: màn dùng hằng ngày trước (điểm danh, lịch dạy), rồi tổng quan, danh mục lớp, chi tiết lớp (kèm prompt Ghép để gộp lại), đơn xin phép, tiến độ, và khóa học (ít dùng hơn). Các màn nối với nhau theo chuỗi dùng hằng ngày: Tổng quan → Lịch dạy → Buổi học và điểm danh → Đơn xin phép → Soạn và giao bài.

| Mã | Màn hình | Ghi chú |
|---|---|---|
| TC-05 | Buổi học và điểm danh | Dùng hằng ngày, dùng tốt trên máy tính bảng |
| TC-04 | Lịch dạy | Xếp, dời, hủy buổi và lịch lặp hằng tuần |
| TC-01 | Tổng quan | Trang đầu mỗi ngày dạy |
| TC-02 | Lớp của tôi | Tìm và mở lớp |
| TC-03 | Chi tiết lớp | Xem một lớp từ đầu đến cuối (chỉ xem) |
| TC-03b | Lịch sử thay đổi của lớp | Phần tra cứu ai đã đổi gì trong lớp; tách riêng khỏi chi tiết lớp |
| TC-03-G | Ghép: Chi tiết lớp của giáo viên | Gộp chi tiết lớp và lịch sử thay đổi thành một chức năng liền mạch |
| TC-06 | Đơn xin phép | Duyệt đơn phụ huynh gửi |
| TC-14 | Tiến độ học sinh | Chỉ xem |
| TC-15 | Khóa học của tôi | Khóa của giáo viên và khóa dùng chung |

---

### TC-05 — Buổi học và điểm danh

```text
Màn hình: Buổi học và điểm danh (giáo viên, cũng dùng được khi tài khoản đồng thời là quản trị viên; responsive: dùng tốt trên máy tính bảng, vẫn dùng được trên máy tính và điện thoại)

Tình huống: Giáo viên vừa vào lớp, cả lớp khoảng 30 em đang ngồi chờ. Cô/thầy phải điểm danh thật nhanh, ít chạm nhất có thể. Cuối buổi ghi lại nội dung đã dạy rồi đánh dấu hoàn tất. Nếu lỡ nhập nhầm thì sửa lại và nói rõ lý do. Quan trọng nhất: nhanh và không nhầm.

Thông tin cần có:
- Lớp và môn, giờ bắt đầu - kết thúc, trạng thái buổi (Đã lên lịch, Hoàn tất, Đã hủy).
- Nội dung buổi học (học sinh và phụ huynh sẽ thấy, phải nói rõ điều này) và ghi chú của giáo viên.
- Từng học sinh trong lớp: tên, trạng thái điểm danh hiện tại (Có mặt, Đi muộn, Vắng, Có phép, hoặc Chưa điểm danh), ghi chú ngắn, và dấu hiệu nếu em có đơn xin phép của phụ huynh cho buổi này.
- Số đếm tổng: có mặt, đi muộn, vắng, có phép, chưa điểm danh.
- Lịch sử các lần sửa điểm danh và sửa buổi học: trước/sau, thời điểm, lý do.

Hành động:
- Đặt "Tất cả có mặt" cho các em chưa có bản ghi, rồi chỉnh riêng từng em bằng một chạm; thêm ghi chú tùy chọn.
- Lưu điểm danh, chỉ cho phép khi mọi học sinh đã có trạng thái.
- Sửa điểm danh đã lưu: chọn trạng thái mới và bắt buộc nhập lý do (ví dụ "Em đến muộn 10 phút, nhập nhầm là vắng").
- Sửa nội dung/ghi chú buổi; với buổi đã hoàn tất thì bắt buộc nhập lý do.
- Đánh dấu hoàn tất buổi; nếu còn em chưa điểm danh thì cảnh báo nhẹ, vẫn cho tiếp tục.
- Hủy buổi (bắt buộc nhập lý do).
- Đi tới đơn xin phép của buổi này.

Liên kết:
- Đến từ: Tổng quan (bấm một buổi dạy hôm nay hoặc việc "buổi chưa điểm danh"), Lịch dạy (bấm một buổi rồi chọn điểm danh), Chi tiết lớp (phần các buổi học). Buổi đang chọn và lớp của nó được mang theo, giáo viên không phải chọn lại.
- Đi tới: quay về Lịch dạy hoặc Chi tiết lớp nơi vừa đến, giữ nguyên tuần/tháng hoặc lớp đang xem; "Đơn xin phép của buổi này" mở màn Đơn xin phép, đã lọc sẵn theo buổi đang xem.
- Lịch sử sửa điểm danh và sửa buổi của lớp xem đầy đủ ở màn Lịch sử thay đổi của lớp.
- Chuỗi dùng hằng ngày: Tổng quan → Lịch dạy → Buổi học và điểm danh → Đơn xin phép; từ Đơn xin phép quay lại đúng buổi này. Sau khi điểm danh xong, cho đường đi tới Soạn và giao bài, lớp này đã chọn sẵn, để giao bài về nhà cho lớp (sang phần bài đánh giá).
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Chỉ liệt kê học sinh đang thuộc lớp vào giờ bắt đầu buổi.
- Buổi đã hủy: không điểm danh mới được, vẫn xem và sửa bản ghi có sẵn. Thông báo: "Buổi học đã hủy, không thể điểm danh."
- Buổi hủy sau khi đã có điểm danh phải nhắc: "Buổi này đã hủy sau khi có điểm danh. Hãy rà soát lại các bản ghi."
- Nếu có em không còn thuộc lớp vào giờ đó, cả lượt lưu bị từ chối; phải chỉ rõ đúng những em đó ("Phạm Đức Anh không còn trong lớp vào giờ này"), cho bỏ các em này rồi lưu lại, giữ nguyên lựa chọn của các em còn lại.
- Đơn xin phép của phụ huynh không tự đổi điểm danh; giáo viên tự quyết.

Tình huống đặc biệt: đang tải; lớp không có học sinh hiệu lực ("Không có học sinh nào trong lớp ở buổi này"); chưa điểm danh; điểm danh một phần; lưu thành công; buổi đã hủy; buổi đã hủy cần rà soát; buổi đã hoàn tất; lỗi lưu (giữ nguyên dữ liệu đã chọn); mất kết nối; không có quyền hoặc không tìm thấy buổi.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: Toán 9A, 17:30–19:00, Thứ Hai 26/10. Nguyễn Minh Khôi (Có mặt); Trần Thu Hà (Đi muộn, "Muộn 10 phút"); Lê Gia Bảo (Vắng, có đơn xin phép chờ duyệt). Nội dung buổi: "Ôn tập chương 2: hệ phương trình".
```

### TC-04 — Lịch dạy

```text
Màn hình: Lịch dạy (giáo viên, cũng dùng được khi tài khoản đồng thời là quản trị viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Giáo viên dạy nhiều lớp ở trung tâm, cần xếp lịch dạy cố định hằng tuần, thêm buổi lẻ hoặc buổi dạy bù, dời hoặc hủy buổi khi bận, và để học sinh, phụ huynh biết lý do. Quan trọng nhất: nhìn ra mình dạy gì, lúc nào, và biết chắc thay đổi nào sẽ ảnh hưởng ai.

Thông tin cần có:
- Các buổi học trong tuần hoặc trong tháng của tất cả lớp mình dạy (xem được từng lớp hoặc tất cả): giờ, lớp, trạng thái (Đã lên lịch, Hoàn tất, Đã hủy), dấu hiệu "Cần rà soát điểm danh".
- Chi tiết một buổi: giờ, trạng thái, nội dung buổi (học sinh và phụ huynh sẽ thấy, phải ghi rõ), ghi chú buổi học, lý do hủy nếu đã hủy.
- Các lịch lặp hằng tuần của lớp: thứ, giờ bắt đầu, thời lượng, từ ngày, đến ngày.

Hành động:
- Chuyển giữa xem tuần và xem tháng, đi tới/lui, về hôm nay, lọc theo lớp.
- Thêm buổi lẻ/buổi bù: lớp, ngày, giờ bắt đầu, giờ kết thúc, nội dung và ghi chú (tùy chọn). Không có ô lý do.
- Tạo lịch lặp: ngày bắt đầu hiệu lực, ngày kết thúc (tùy chọn), giờ bắt đầu, thời lượng phút (gợi ý 45/60/90/120 hoặc tự nhập), lặp hằng tuần hay không, chọn các thứ trong tuần.
- Sửa lịch lặp (bắt buộc nhập lý do) và hủy lịch lặp (bắt buộc nhập lý do).
- Sửa một buổi (dời giờ, đổi nội dung/ghi chú), hủy một buổi (bắt buộc nhập lý do), đánh dấu hoàn tất.
- Đi tới điểm danh của buổi.

Liên kết:
- Đến từ: Tổng quan (bấm "Lịch cả tuần" hoặc một buổi dạy), Chi tiết lớp (phần lịch và các buổi; khi đó chỉ hiện lớp đó), Buổi học và điểm danh (quay lại).
- Đi tới: bấm một buổi rồi chọn điểm danh thì mở màn Buổi học và điểm danh của buổi đó (buổi và lớp được mang theo); bấm tên lớp thì mở màn Chi tiết lớp; sau khi dạy xong chuỗi tiếp theo là Đơn xin phép của buổi và Soạn và giao bài về nhà cho lớp (sang phần bài đánh giá, lớp đã chọn sẵn).
- Chuỗi dùng hằng ngày: Tổng quan → Lịch dạy → Buổi học và điểm danh → Đơn xin phép → Soạn và giao bài.
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Giờ kết thúc phải sau giờ bắt đầu; lịch lặp hằng tuần phải chọn ít nhất một thứ. Lỗi: "Giờ kết thúc phải sau giờ bắt đầu", "Hãy chọn ít nhất một ngày trong tuần", "Bắt buộc nhập lý do".
- Sau khi tạo lịch lặp, hệ thống tự tạo sẵn các buổi cho 4 tuần tới; báo kết quả ("Đã tạo lịch và 12 buổi trong 4 tuần tới").
- Nếu trùng với lịch lặp khác của chính giáo viên, chỉ cảnh báo, lịch vẫn được tạo: "Trùng với lịch lớp Toán 9B (Thứ 4, 17:00–18:30)"; cho giữ nguyên, sửa, hoặc hủy lịch vừa tạo.
- Sửa hoặc hủy lịch lặp chỉ ảnh hưởng buổi chưa tạo; các buổi đã tạo không tự đổi, phải nói rõ điều này trước khi xác nhận ("Các buổi đã tạo vẫn còn; hủy từng buổi nếu không dạy").
- Sửa buổi: lý do tùy chọn nếu buổi chưa diễn ra nhưng nên khuyến khích; bắt buộc nếu buổi đã hoàn tất. Đổi giờ thì học sinh và phụ huynh được thông báo.
- Hủy buổi đã có điểm danh: "Điểm danh đã ghi vẫn được giữ, buổi sẽ được đánh dấu cần rà soát."
- Buổi đã hủy không dời giờ, không hoàn tất lại được; lớp đã lưu trữ chỉ xem.

Tình huống đặc biệt: đang tải; tuần/tháng chưa có buổi ("Chưa có buổi nào trong tuần này", gợi ý thêm buổi hoặc tạo lịch lặp); chưa được phân công lớp nào; lỗi tải; lỗi nhập từng ô; cảnh báo trùng lịch; buổi đã hủy (kèm lý do); buổi cần rà soát; lớp đã lưu trữ; mất quyền với lớp giữa chừng ("Không có quyền thao tác với lớp này", tải lại).

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: Lịch lặp Toán 9A: Thứ 2-4-6, 17:30, 90 phút, từ 05/10/2026. Buổi bù Văn 8B, Chủ nhật 01/11, 08:00–09:30, nội dung "Ôn tập chương 2". Lý do hủy: "Giáo viên bị ốm, sẽ dạy bù vào Chủ nhật."
```

### TC-01 — Tổng quan

```text
Màn hình: Tổng quan (giáo viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Sáng đến trung tâm, giáo viên mở trang này đầu tiên để biết ngay hôm nay dạy lớp nào, mấy giờ, và còn việc gì tồn đọng. Quan trọng nhất: liếc một lần là nắm được, rồi đi thẳng tới nơi cần xử lý. Màn này chỉ xem và đi tới, không sửa dữ liệu.

Thông tin cần có:
- Lời chào kèm tên giáo viên và ngày hôm nay.
- Việc cần xử lý, mỗi việc có số đếm: bài chờ chấm, đơn xin phép chờ duyệt, buổi đã qua nhưng chưa điểm danh.
- Các buổi dạy hôm nay theo giờ: giờ bắt đầu - kết thúc, lớp và môn, trạng thái buổi (Đã lên lịch, Hoàn tất, Đã hủy), dấu hiệu cần rà soát điểm danh, và đường tắt vào điểm danh.
- Lịch cả tuần này: mỗi ngày có các buổi rút gọn.
- Bài sắp đến hạn: tên bài, lớp, hạn nộp, số em đã nộp trên số em được giao.
- Chuyên cần trung bình của các lớp.

Hành động:
- Bấm vào một việc cần xử lý để đến nơi xử lý tương ứng (chấm bài, duyệt đơn, điểm danh).
- Bấm vào một buổi để vào điểm danh, hoặc vào lịch dạy để xem cả tuần.
- Bấm vào bài sắp đến hạn để xem bài.
- Làm mới dữ liệu (dữ liệu không tự cập nhật theo thời gian thực).

Liên kết:
- Đến từ: là trang đầu sau khi đăng nhập với vai giáo viên.
- Đi tới: việc "bài chờ chấm" mở màn Bài nộp cần chấm của bài đó; "đơn xin phép chờ duyệt" mở màn Đơn xin phép (đã lọc Chờ duyệt); "buổi chưa điểm danh" và từng buổi dạy hôm nay mở màn Buổi học và điểm danh của buổi đó; "Lịch cả tuần" mở màn Lịch dạy; "bài sắp đến hạn" mở màn Danh sách bài đánh giá tại bài đó. Thông tin được mang theo: buổi, đơn hoặc bài vừa chọn.
- Chuỗi dùng hằng ngày: Tổng quan → Lịch dạy → Buổi học và điểm danh → Đơn xin phép → Soạn và giao bài (sang phần bài đánh giá).
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- "Buổi chưa điểm danh" là buổi đã qua giờ kết thúc, không bị hủy, mà chưa điểm danh đủ học sinh.
- Không hiển thị số liệu chuyên cần giả; khi chưa có dữ liệu thì ghi "Chưa có dữ liệu".
- Giáo viên không tạo lớp ở đây; không có hành động nào tạo lớp.
- Nếu tài khoản đồng thời là quản trị viên, cần cho biết đang xem ở vai giáo viên.

Tình huống đặc biệt: đang tải (từng phần tải độc lập nhau); chưa được phân công lớp nào ("Bạn chưa được phân công lớp nào. Liên hệ quản trị viên."); hôm nay không có buổi ("Hôm nay không có buổi dạy", gợi ý thêm buổi bù); không còn việc cần xử lý ("Đã xử lý hết"); lỗi riêng từng phần có hành động thử lại; hết phiên đăng nhập.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: "Chào cô Lan · Thứ Hai 26/10". Cần xử lý: 7 bài chờ chấm · 2 đơn xin phép · 1 buổi chưa điểm danh. Hôm nay: 17:30–19:00 Toán 9A (Đã lên lịch); 19:15–20:45 Văn 8B. Bài sắp hạn: "Kiểm tra 15 phút - Chương 2", Toán 9A, hạn 28/10, đã nộp 22/28.
```

### TC-02 — Lớp của tôi

```text
Màn hình: Lớp của tôi (giáo viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Giáo viên muốn tìm nhanh một lớp mình đang dạy và mở vào lớp đó. Có thể dạy vài lớp hoặc hơn chục lớp, tên gần giống nhau (Toán 9A, Toán 9B). Quan trọng nhất: tìm ra đúng lớp bằng ít thao tác, kể cả khi gõ không dấu.

Thông tin cần có:
- Mỗi lớp: tên lớp, nhãn phân biệt (ví dụ "Thứ 2-4-6"), môn, trạng thái (Đang chuẩn bị, Đang học, Đã lưu trữ), mô tả rút gọn.
- Số liệu nhanh của mỗi lớp: sĩ số, giáo viên cùng dạy, buổi kế tiếp, số bài chờ chấm.

Hành động:
- Tìm theo tên lớp, tìm không cần gõ dấu.
- Lọc theo trạng thái và theo môn.
- Mặc định ẩn lớp đã lưu trữ; có lựa chọn hiện chúng.
- Mở một lớp để xem chi tiết.

Liên kết:
- Đến từ: mục "Lớp của tôi" trong khu giảng dạy của giáo viên; cũng là nơi quay về từ Chi tiết lớp.
- Đi tới: bấm một lớp thì mở màn Chi tiết lớp của lớp đó (lớp đang chọn được mang theo). Không có đường dẫn tới việc tạo lớp.
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Chỉ lớp mà giáo viên đang được phân công mới xuất hiện; hết phân công thì lớp biến khỏi danh sách.
- Giáo viên không tạo lớp; không có hành động tạo lớp ở màn này.
- Lớp đang chuẩn bị là lớp chưa bắt đầu vận hành, cần phân biệt rõ với lớp đang học. Lớp đã lưu trữ chỉ xem lại lịch sử.

Tình huống đặc biệt: đang tải; chưa được phân công lớp nào ("Bạn chưa được phân công lớp nào"); không có lớp khớp bộ lọc ("Không có lớp khớp", gợi ý xóa bộ lọc); lỗi tải có thử lại; lớp đang chuẩn bị; lớp đã lưu trữ.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: "Toán 9A · Thứ 2-4-6 · Toán · Đang học"; "Văn 8B · Thứ 3-5 · Ngữ văn · Đang học"; "Anh giao tiếp K12 · Cuối tuần · Tiếng Anh · Đang chuẩn bị".
```

### TC-03 — Chi tiết lớp

```text
Màn hình: Chi tiết lớp (giáo viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Giáo viên muốn xem một lớp từ đầu đến cuối: ai đang học, lịch dạy, các buổi đã và sắp diễn ra, bài đã giao, em nào đang yếu. Đây là nơi tra cứu và đi tới các việc khác; giáo viên không quản lý nhân sự của lớp ở đây.

Thông tin cần có các nhóm sau:
- Thông tin chung của lớp: tên, nhãn phân biệt, môn, trạng thái, mô tả (có chỗ cho địa điểm/phòng học, chưa có dữ liệu thì để trống, không điền giả).
- Học sinh: tên, ngày vào lớp; nhóm "Đã rời lớp" kèm ngày rời. Có tìm theo tên.
- Giáo viên của lớp: tên, ngày bắt đầu phân công.
- Lịch lặp hằng tuần: thứ, giờ, từ ngày (ví dụ "Thứ 2, 4, 6 · 17:30–19:00 · từ 05/10").
- Các buổi học, chia sắp tới và đã diễn ra, lọc theo tháng; mỗi buổi có giờ, trạng thái, dấu hiệu cần rà soát.
- Bài của lớp: tên bài, loại, trạng thái, hạn, số đã nộp và chờ chấm.
- Tiến độ từng học sinh: điểm trung bình, số bài đã chấm, số bài chờ chấm, chuyên cần.
- Lịch sử thay đổi của lớp (nằm ở màn riêng, chỉ cần có lối đi tới).

Hành động:
- Chuyển giữa các nhóm thông tin trên (nhớ nhóm xem gần nhất); tìm học sinh.
- Đi tới quản lý lịch, điểm danh một buổi, xem bài, giao bài cho lớp, xem tiến độ một học sinh.

Liên kết:
- Đến từ: Lớp của tôi (bấm một lớp), Tổng quan, hoặc thông báo liên quan tới lớp.
- Đi tới (lớp đang xem được mang theo): phần các buổi học và từng buổi mở màn Buổi học và điểm danh; phần lịch mở màn Lịch dạy, chỉ hiện lớp này; phần bài của lớp mở màn Danh sách bài đánh giá, lọc theo lớp này; "giao bài cho lớp" mở màn Soạn và giao bài với lớp đã chọn sẵn; bấm tên học sinh mở màn Tiến độ học sinh của em đó, trong lớp này; phần lịch sử thay đổi mở màn Lịch sử thay đổi của lớp.
- Quay về Lớp của tôi.
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Giáo viên chỉ xem: không thêm, bớt học sinh, không phân công giáo viên, không sửa thông tin lớp. Khi cần, ghi "Liên hệ quản trị viên".
- Không hiển thị email, số điện thoại học sinh.
- Lớp đã lưu trữ chỉ xem, khóa mọi thao tác thêm buổi, thêm lịch. Lớp đang chuẩn bị xem được, kèm ghi chú "Lớp chưa bắt đầu vận hành".

Tình huống đặc biệt: đang tải từng nhóm thông tin; chưa có học sinh ("Quản trị viên chưa thêm học sinh"); chưa có lịch lặp ("Chưa có lịch lặp", gợi ý thêm lịch); chưa có buổi; chưa giao bài (gợi ý giao bài, lớp này đã chọn sẵn); lỗi tải; hết phân công hoặc không tìm thấy lớp (thông báo chung, không tiết lộ lớp có tồn tại); lớp đã lưu trữ.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: Toán 9A, 28 học sinh. Nguyễn Minh Khôi, Trần Thu Hà, Lê Gia Bảo; đã rời lớp: Phạm Đức Anh (đến 30/09). Giáo viên: Cô Lan (từ 01/09).
```

### TC-03b — Lịch sử thay đổi của lớp

```text
Màn hình: Lịch sử thay đổi của lớp (giáo viên, cũng dùng khi quản trị viên kiểm tra; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Phụ huynh khiếu nại "sao buổi học bị đổi giờ?" hoặc "con tôi hôm đó có mặt mà". Giáo viên mở lịch sử của lớp mình dạy để biết ai đã đổi gì, lúc nào, vì sao, rồi trả lời phụ huynh có căn cứ. Quan trọng nhất: đọc được nhanh, thông tin trước/sau dễ so sánh.

Thông tin cần có:
- Danh sách thay đổi, mới nhất trước. Mỗi mục: thời điểm, hành động bằng tiếng Việt (Sửa buổi học, Hủy buổi học, Sửa lịch lặp, Hủy lịch lặp, Điểm danh, Sửa điểm danh), đối tượng bị đổi, lý do, người thực hiện, và nội dung trước và sau khi đổi (xem chi tiết khi cần).

Hành động:
- Lọc theo loại hành động và theo loại đối tượng (buổi học, lịch lặp, điểm danh), lọc theo khoảng ngày.
- Mở xem nội dung trước/sau của một mục.

Liên kết:
- Đến từ: Chi tiết lớp (phần lịch sử thay đổi), Buổi học và điểm danh (xem các lần sửa điểm danh/sửa buổi), với lớp đang xem được mang theo.
- Đi tới: quay về Chi tiết lớp; từ một mục về buổi học hoặc điểm danh thì mở màn Buổi học và điểm danh của buổi đó.
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Chỉ xem, không sửa hay xóa được lịch sử.
- Chỉ thấy lịch sử của lớp mình đang được phân công.
- Mục nào không ghi lý do thì ghi rõ "Không có lý do".
- Khi chưa biết tên người thực hiện thì hiện "Người dùng #" kèm số; không bịa tên.

Tình huống đặc biệt: đang tải; lớp chưa có thay đổi nào ("Chưa có thay đổi nào được ghi lại"); không có mục khớp bộ lọc (gợi ý xóa lọc); lỗi tải có thử lại; không có quyền hoặc hết phân công lớp.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: "26/10 16:05 · Sửa buổi học · Toán 9A · đổi giờ từ 17:30–19:00 sang 18:00–19:30 · Lý do: Giáo viên có việc đột xuất". "26/10 19:12 · Sửa điểm danh · Lê Gia Bảo · Vắng → Có phép · Lý do: Phụ huynh xin phép, đã chấp nhận".
```

### TC-06 — Đơn xin phép

```text
Màn hình: Đơn xin phép (giáo viên, cũng dùng khi tài khoản đồng thời là quản trị viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Phụ huynh gửi đơn xin cho con nghỉ một buổi. Giáo viên đọc lý do, quyết định chấp nhận hoặc từ chối, để điểm danh phản ánh đúng. Quan trọng nhất: đọc nhanh, quyết định nhanh, và không nhầm sang việc đổi điểm danh (việc này làm riêng).

Thông tin cần có:
- Mỗi đơn: tên học sinh, lớp, buổi học (ngày giờ), lý do phụ huynh viết, thời điểm gửi, trạng thái (Chờ duyệt, Đang xem xét, Đã chấp nhận, Đã từ chối).
- Điểm danh hiện tại của em đó ở buổi ấy.
- Ghi chú của giáo viên khi đã duyệt.
- Số đơn đang chờ duyệt.

Hành động:
- Lọc theo trạng thái (mặc định Chờ duyệt, có cả Tất cả) và theo lớp.
- Đọc đầy đủ một đơn.
- Chấp nhận, Từ chối, hoặc đánh dấu Đang xem xét; kèm ghi chú tùy chọn (nên có khi từ chối, ví dụ "Cần giấy xác nhận của trường"). Phụ huynh được thông báo kết quả.
- Đổi quyết định với đơn đã duyệt.
- Đi tới điểm danh của buổi đó.

Liên kết:
- Đến từ: mục "Đơn xin phép" (có số đơn chờ), Tổng quan (việc đơn chờ duyệt), thông báo "có đơn xin phép mới", Buổi học và điểm danh ("Đơn xin phép của buổi này", đã lọc sẵn theo buổi).
- Đi tới: từ một đơn mở màn Buổi học và điểm danh của buổi đó (buổi và học sinh của đơn được mang theo). Sau khi chấp nhận đơn, gợi ý đổi điểm danh sẽ dẫn tới chính màn đó để giáo viên xác nhận.
- Chuỗi dùng hằng ngày: Tổng quan → Lịch dạy → Buổi học và điểm danh → Đơn xin phép.
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Chấp nhận đơn KHÔNG tự đổi điểm danh. Sau khi chấp nhận, gợi ý "Đổi điểm danh của em thành Có phép?" với lý do điền sẵn "Phụ huynh xin phép, đã chấp nhận"; giáo viên tự xác nhận. Nếu em chưa có điểm danh thì dẫn tới điểm danh buổi đó trước.
- Giáo viên chỉ thấy đơn của buổi thuộc lớp mình dạy.
- Học sinh không có quyền vào màn này.

Tình huống đặc biệt: đang tải; không có đơn chờ duyệt ("Không có đơn nào chờ duyệt"); không có đơn khớp bộ lọc; lỗi tải có thử lại; đơn của buổi đã hủy (cảnh báo, vẫn duyệt được); đơn đã chấp nhận hoặc đã từ chối kèm ghi chú; đang xử lý; hết phân công lớp thì đơn biến mất.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: "Lê Gia Bảo · Toán 9A · 26/10 17:30 · 'Con bị sốt, xin phép cho con nghỉ buổi hôm nay.' · Chờ duyệt". "Phạm Thu Trang · Văn 8B · 27/10 19:15 · 'Gia đình có việc, xin cho con nghỉ.' · Đã từ chối: 'Cần giấy xác nhận của trường'".
```

### TC-14 — Tiến độ học sinh

```text
Màn hình: Tiến độ học sinh (giáo viên, cũng dùng khi tài khoản đồng thời là quản trị viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Giáo viên chuẩn bị nói chuyện với phụ huynh hoặc quyết định có cần phụ đạo cho một em: điểm trung bình thế nào, còn nợ bài nào, đi học có đều không. Có lúc duyệt lần lượt cả lớp. Màn này chỉ xem. Quan trọng nhất: nắm tình hình một em trong vài giây.

Thông tin cần có:
- Tên học sinh, lớp đang xem.
- Điểm trung bình (%), số bài đã công bố điểm, số bài chưa chấm xong.
- Điểm trung bình và số bài theo từng môn ("Khác" cho bài giao không qua lớp hay khóa học).
- Diễn biến điểm theo thời gian và các bài gần đây (tên bài, ngày, %).
- Điểm danh 10 buổi gần nhất và tỷ lệ chuyên cần.

Hành động:
- Lọc theo lớp, môn, khóa học, khoảng ngày nhận bài.
- Chuyển sang học sinh trước/sau trong cùng lớp.
- Quay lại lớp.

Liên kết:
- Đến từ: Chi tiết lớp (bấm tên một học sinh trong lớp); học sinh và lớp đang chọn được mang theo.
- Đi tới: "học sinh trước/sau" vẫn ở màn này, trong cùng lớp; "Quay lại lớp" mở màn Chi tiết lớp. Bấm một bài gần đây thì mở màn chấm hoặc xem bài đó (nếu màn này đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới).

Quy tắc nghiệp vụ:
- Chỉ tính kết quả đã công bố; bài đã hủy hoặc bị vô hiệu không tính; bài làm nhiều lượt chỉ tính một lần theo cách tính điểm của bài.
- Giáo viên chỉ xem được học sinh thuộc lớp mình phụ trách; nếu không, hiện thông báo chung "Không tìm thấy", không tiết lộ em có tồn tại.
- Không bịa số liệu: phần chưa có dữ liệu hiện "Chưa có dữ liệu".

Tình huống đặc biệt: đang tải; chưa có bài nào được công bố ("Chưa có điểm. Có 3 bài đang chờ chấm"); chưa có dữ liệu điểm danh; chưa có dữ liệu diễn biến điểm; lỗi tải có thử lại; không có quyền hoặc em không thuộc phạm vi.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: Nguyễn Minh Khôi · Toán 9A: trung bình 82,5% · 6 bài đã chấm · 1 bài chờ chấm; theo môn: Toán 85%, Khác 70%.
```

### TC-15 — Khóa học của tôi

```text
Màn hình: Khóa học của tôi (giáo viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Tình huống: Giáo viên tự quản lý nhóm bài tự học riêng của mình (ví dụ "Luyện đề vào 10"): ai được học, ai đã kết thúc; đồng thời xem các khóa dùng chung do quản trị viên quản lý. Nội dung của một khóa chính là các bài đánh giá gán cho khóa đó. Quan trọng nhất: biết rõ khóa nào là của mình (được sửa) và khóa nào chỉ xem.

Thông tin cần có:
- Danh sách khóa: tên, mô tả, trạng thái (Đang mở, Đã lưu trữ), nguồn ("Của tôi" hoặc "Dùng chung"), số học sinh, số bài.
- Chi tiết khóa: tên, mô tả, môn, trạng thái.
- Học sinh ghi danh của khóa (khóa của tôi): tên, trạng thái (Đang học, Đã kết thúc), ngày bắt đầu, ngày kết thúc.
- Các bài thuộc khóa.

Hành động:
- Tạo khóa: tên (bắt buộc), mô tả, môn (tùy chọn). Khóa tạo ra luôn thuộc về giáo viên đó.
- Sửa tên, mô tả, môn của khóa của tôi (kể cả khi đã lưu trữ).
- Lưu trữ khóa (xác nhận: "Khóa lưu trữ không nhận ghi danh mới; các ghi danh hiện có vẫn xem được"; không có cách bỏ lưu trữ).
- Ghi danh theo từng học sinh hoặc theo cả lớp (chọn một trong hai, không cả hai). Sau khi xong cho biết kết quả, ví dụ "Đã ghi danh 24 học sinh (4 em đã có từ trước)".
- Kết thúc ghi danh một học sinh, bắt buộc nhập lý do.
- Giao bài cho khóa; mở xem một bài của khóa.
- Hiện thêm các ghi danh đã kết thúc.

Liên kết:
- Đến từ: mục "Khóa học của tôi" trong khu giảng dạy.
- Đi tới: mở một khóa để xem chi tiết khóa ngay trong màn này (ghi danh và các bài); "Giao bài cho khóa" mở màn Soạn và giao bài với khóa đã chọn sẵn; mở một bài của khóa thì mở màn Danh sách bài đánh giá tại bài đó (lọc theo khóa này).
- Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Khóa dùng chung chỉ xem, ghi "Do quản trị viên quản lý"; không có sửa, ghi danh, kết thúc ghi danh; không xem được danh sách ghi danh, hiện "Chỉ quản trị viên xem được học sinh ghi danh".
- Khóa đã lưu trữ không ghi danh thêm được: "Khóa học đã lưu trữ, không thể ghi danh thêm."; sửa tên, xem và kết thúc ghi danh vẫn được.
- Ghi danh theo lớp là ảnh chụp tại thời điểm bấm; học sinh vào lớp sau không tự được ghi danh, cần nhắc điều này.
- Giáo viên được ghi danh học sinh hoặc lớp bất kỳ, không giới hạn ở lớp mình dạy.
- Lỗi: "Bắt buộc nhập lý do kết thúc"; "Học sinh này không còn ghi danh trong khóa" khi hai người cùng thao tác.

Tình huống đặc biệt: đang tải; chưa có khóa nào của tôi ("Bạn chưa có khóa nào. Tạo khóa đầu tiên", vẫn hiện khóa dùng chung nếu có); khóa chưa có ghi danh; khóa chưa có bài; khóa đã lưu trữ; khóa dùng chung chỉ xem; lỗi tải; không có quyền hoặc khóa không còn trong phạm vi.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.

Dữ liệu mẫu: "Luyện đề vào 10 (Của tôi) · Đang mở · 24 học sinh"; "Ôn thi tốt nghiệp THPT (Dùng chung, quản trị viên quản lý)". Lý do kết thúc ghi danh: "Học sinh chuyển sang nhóm khác."
```

### TC-03-G — Ghép: Chi tiết lớp của giáo viên

```text
Màn hình: Ghép: Chi tiết lớp của giáo viên (giáo viên; responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại)

Yêu cầu: Áp dụng cho các màn có tên "Chi tiết lớp" và "Lịch sử thay đổi của lớp" đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có. Hãy gộp chúng thành MỘT chức năng liền mạch: xem một lớp của giáo viên từ đầu đến cuối.

Đối tượng chung: một lớp duy nhất. Tên lớp, nhãn phân biệt, môn và trạng thái (Đang chuẩn bị, Đang học, Đã lưu trữ) luôn hiện ở phần đầu và dùng chung cho mọi phần: thông tin chung, học sinh, giáo viên, lịch lặp, các buổi học, bài của lớp, tiến độ học sinh, lịch sử thay đổi. Ghi chú "Lớp chưa bắt đầu vận hành" (lớp đang chuẩn bị) và "chỉ xem" (lớp đã lưu trữ) là thông báo chung, hiện một lần cho cả chức năng. Lịch sử thay đổi là một phần của chính lớp này, không phải một nơi khác.

Giữ lại khi chuyển qua lại giữa các phần: phần đang xem gần nhất, từ khóa tìm học sinh, bộ lọc tháng của các buổi, bộ lọc của lịch sử thay đổi, vị trí cuộn. Chuyển giữa các phần không được rời khỏi lớp đang xem và không phải chọn lại lớp.

Hiện/ẩn: giáo viên chỉ xem, không có thao tác thêm bớt học sinh hay sửa lớp ở bất kỳ phần nào (ghi "Liên hệ quản trị viên"); lớp đã lưu trữ khóa mọi thao tác thêm buổi, thêm lịch; khi giáo viên hết phân công hoặc lớp không tìm thấy thì cả chức năng hiện một thông báo chung. Khi một phần lỗi hoặc đang tải thì các phần khác vẫn dùng được.

Liên kết ra ngoài chức năng (lớp đang xem được mang theo): Lớp của tôi (quay về), Buổi học và điểm danh, Lịch dạy, Danh sách bài đánh giá, Soạn và giao bài, Tiến độ học sinh. Nếu màn kia đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Yêu cầu chung: phải dùng tốt ở khổ màn hình hẹp và rộng; không để mất chức năng nào khi thu nhỏ.
```
