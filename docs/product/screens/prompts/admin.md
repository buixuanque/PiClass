# Bộ prompt — Cổng Admin

Gồm 29 prompt cho các màn hình của quản trị viên (Admin) trung tâm dạy thêm, tất cả ưu tiên máy tính. Thứ tự vẽ gợi ý: tổng quan và thiết lập lần đầu, lớp học và chi tiết lớp (dùng nhiều nhất), lịch và buổi học, học sinh, phụ huynh, giáo viên, môn học, khóa học, tài khoản, cấp lại lượt làm bài, nhật ký audit.
Mỗi prompt tự đứng được: dán riêng từng cái vào Claude Design, kèm design system đã có.

| Mã | Màn hình | Ghi chú |
|---|---|---|
| AD-01 | Tổng quan | Màn đầu tiên sau đăng nhập |
| AD-15 | Thiết lập lần đầu | Danh sách việc cần làm khi hệ thống trống |
| AD-09 | Danh sách lớp học | Kèm tạo lớp mới |
| AD-10 | Khung chi tiết lớp | Phần đầu màn hình chung cho 5 nhóm nội dung của lớp |
| AD-10a | Chi tiết lớp: Tổng quan | Xem, sửa thông tin, lưu trữ lớp |
| AD-10b | Chi tiết lớp: Học sinh | Thêm, rút khỏi lớp, sửa thời hạn hồi tố |
| AD-10c | Chi tiết lớp: Giáo viên | Phân công, kết thúc phân công |
| AD-10d | Chi tiết lớp: Lịch | Tạo, sửa, hủy lịch học |
| AD-10e | Chi tiết lớp: Buổi học | Thêm, sửa, hủy, hoàn tất buổi |
| AD-11 | Lịch tổng hợp | Toàn trung tâm, chỉ xem và điều hướng |
| AD-04 | Danh sách học sinh | |
| AD-05 | Chi tiết học sinh | |
| AD-05a | Tạo / sửa hồ sơ học sinh | |
| AD-05b | Mời tạo tài khoản | Dùng cho học sinh, phụ huynh, giáo viên |
| AD-05c | Gửi lại lời mời | Khi liên kết hết hạn hoặc không nhận được thư |
| AD-06 | Danh sách phụ huynh | |
| AD-06a | Chi tiết phụ huynh | |
| AD-06b | Tạo / sửa hồ sơ phụ huynh | |
| AD-06c | Liên kết phụ huynh – học sinh | Tạo và kết thúc liên kết |
| AD-07 | Danh sách giáo viên | |
| AD-07a | Chi tiết giáo viên | |
| AD-07b | Tạo / sửa hồ sơ giáo viên | |
| AD-08 | Môn học | Một màn hình gồm xem và quản lý môn |
| AD-12 | Khóa học | Danh sách, chi tiết, tạo, sửa, lưu trữ |
| AD-12a | Ghi danh vào khóa học | Theo học sinh hoặc theo cả lớp |
| AD-02 | Danh sách tài khoản | |
| AD-03 | Chi tiết tài khoản | Vai trò, khóa/mở tài khoản |
| AD-13 | Cấp lại lượt làm bài | Xử lý sự cố khi học sinh đang làm bài kiểm tra |
| AD-14 | Nhật ký audit | |

---

### AD-01 — Tổng quan

```text
Màn hình: Tổng quan (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Sáng nào Admin của trung tâm dạy thêm cũng mở hệ thống đầu tiên. Họ muốn trong vài giây biết hôm nay trung tâm có bao nhiêu lớp, buổi nào đang diễn ra, và có việc gì cần mình xử lý ngay. Quan trọng nhất là nhìn nhanh và bấm đi tiếp được tới đúng chỗ cần xử lý.

Thông tin cần có:
- Lời chào và ngày hôm nay.
- Mức hoàn tất thiết lập hệ thống (chỉ khi còn bước chưa xong).
- Số liệu chung: số học sinh, số giáo viên, số lớp đang hoạt động, số lớp đang chuẩn bị.
- Các buổi học hôm nay: giờ, lớp, giáo viên, trạng thái buổi (Sắp diễn ra / Đã hoàn tất / Đã hủy), đã điểm danh chưa.
- Việc cần xử lý: buổi đã hủy nhưng đã có điểm danh cần rà soát; lớp đang chuẩn bị chưa có giáo viên; tài khoản đã mời nhưng chưa kích hoạt; đơn xin phép chờ duyệt; bài chờ chấm.
- Lối tắt tới: Tạo lớp, Thêm học sinh, Xem nhật ký audit.
- Nếu người này còn là giáo viên: cách chuyển sang phần giảng dạy.

Hành động: chủ yếu là điều hướng. Mỗi số liệu, mỗi buổi học, mỗi việc cần xử lý đều bấm để đi tới màn tương ứng. Có hành động làm mới dữ liệu (không tự cập nhật liên tục).

Quy tắc nghiệp vụ:
- Hệ thống chưa có dữ liệu nào thì không hiện các con số 0 vô nghĩa, chỉ hướng dẫn thiết lập.
- Mỗi phần tải độc lập, phần nào xong thì hiện trước.

Tình huống đặc biệt:
- Đang tải từng phần; hệ thống trống hoàn toàn; không có việc cần xử lý ("Không có việc cần xử lý"); một phần tải lỗi (có cách thử lại riêng, các phần khác vẫn dùng được); không có quyền truy cập.

Dữ liệu mẫu: 128 học sinh, 9 giáo viên, 14 lớp đang hoạt động, 2 lớp đang chuẩn bị. Hôm nay: 17:30–19:00 Toán 9A (cô Hạnh), Đã hoàn tất; 19:15–20:45 Anh 6C, Sắp diễn ra. Cần xử lý: "Buổi Văn 8B ngày 20/10 đã hủy nhưng đã có điểm danh".
```

### AD-15 — Thiết lập lần đầu

```text
Màn hình: Thiết lập lần đầu (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin mới nhận một hệ thống còn trống, chưa biết làm gì trước, gì sau để lớp học chạy được. Họ cần một lộ trình rõ ràng, thấy mình đã làm tới đâu và bấm đi thẳng tới việc kế tiếp. Quan trọng nhất là dễ hiểu và không làm họ sợ sai.

Thông tin cần có: bảy bước theo thứ tự gợi ý, mỗi bước có tên, một câu giải thích vì sao cần, và trạng thái đã xong hay chưa (tự nhận biết từ dữ liệu thật, không phải tự đánh dấu):
1. Tạo môn học
2. Thêm giáo viên và mời tài khoản
3. Tạo lớp
4. Phân công giáo viên cho lớp (lớp sẽ chuyển sang Đang hoạt động)
5. Thêm học sinh và xếp vào lớp
6. Tạo lịch học cho lớp
7. Mời phụ huynh và liên kết với học sinh
Cùng tiến độ tổng (ví dụ 4/7).

Hành động: bấm "Làm bước này" để đi thẳng tới việc tương ứng; ẩn danh sách thiết lập này đi (việc ẩn chỉ lưu ở trình duyệt, có thể bật lại). Màn hình này không tự ghi dữ liệu gì.

Quy tắc nghiệp vụ:
- Thứ tự chỉ là gợi ý, không chặn: có thể tạo lớp trước khi có giáo viên, khi đó lớp ở trạng thái Đang chuẩn bị.
- Khi tạo lớp phải chọn môn đang được dùng, nên cần nhắc điều này ở bước tạo lớp.

Tình huống đặc biệt:
- Đang tải; tất cả đã xong ("Hệ thống đã sẵn sàng" kèm lối quay về màn tổng quan); không xác định được một bước nào đó (hiện là "chưa rõ", không được đánh dấu xong sai).

Dữ liệu mẫu: Môn học (3: Toán, Văn, Anh) đã xong; Giáo viên (2) đã xong; Lớp (Toán 9A) đã xong; Phân công giáo viên chưa xong; Học sinh vào lớp chưa xong; Lịch học chưa xong; Phụ huynh chưa xong. Tiến độ 3/7.
```

### AD-09 — Danh sách lớp học

```text
Màn hình: Danh sách lớp học (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin cần thấy mọi lớp của trung tâm, tìm nhanh một lớp để mở ra quản lý, hoặc tạo lớp mới khi có khóa mới. Quan trọng nhất là phân biệt được các lớp trùng tên và thấy lớp nào còn thiếu giáo viên.

Thông tin cần có: số lượng lớp; với mỗi lớp: tên lớp, nhãn (ví dụ "Khóa 2026", "Ca tối"), môn học, trạng thái (Đang chuẩn bị / Đang hoạt động / Đã lưu trữ), và nếu có thể: giáo viên phụ trách, sĩ số, buổi kế tiếp. Tên lớp được phép trùng nhau nên nhãn và môn là thông tin giúp phân biệt, cần nổi bật.

Hành động:
- Tìm theo tên; lọc theo trạng thái lớp và theo môn; xóa bộ lọc.
- Mặc định ẩn lớp đã lưu trữ, có cách bật để hiện.
- Mở một lớp để xem chi tiết.
- Tạo lớp: chọn môn (chỉ liệt kê môn đang dùng), nhập tên lớp (bắt buộc), nhãn, mô tả. Ghi chú rõ: môn không đổi được sau khi tạo. Lớp mới ở trạng thái Đang chuẩn bị; tạo xong đưa Admin tới phần phân công giáo viên với lời nhắc "Phân công giáo viên để lớp bắt đầu hoạt động".
- Lưu trữ một lớp ngay từ danh sách.

Quy tắc nghiệp vụ:
- Không giới hạn sĩ số.
- Chọn môn đã ngừng sử dụng khi tạo lớp: "Môn này đã ngừng sử dụng. Chọn môn khác hoặc dùng lại môn này ở mục Môn học."
- Thiếu môn hoặc tên: "Vui lòng chọn môn." / "Vui lòng nhập tên lớp."
- Đổi bộ lọc thì quay về đầu danh sách; quay lại từ màn chi tiết vẫn giữ nguyên bộ lọc.

Tình huống đặc biệt: đang tải; chưa có lớp nào ("Chưa có lớp. Tạo lớp đầu tiên"; nếu chưa có môn nào thì gợi ý tạo môn trước); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; lớp đã lưu trữ hiển thị mờ nhưng vẫn mở được.

Dữ liệu mẫu: Toán 9A · Ca tối · Toán · Đang hoạt động; Anh 6C · Khóa hè · Tiếng Anh · Đang chuẩn bị (chưa có giáo viên); Văn 8B · — · Ngữ văn · Đã lưu trữ.
```

### AD-10 — Khung chi tiết lớp

```text
Màn hình: Chi tiết lớp học, phần chung của mọi nhóm nội dung (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin quản lý một lớp từ đầu đến cuối ở một chỗ: thông tin lớp, ai học, ai dạy, khi nào học. Phần chung này luôn có mặt khi Admin xem bất kỳ nội dung nào của lớp, giúp họ biết mình đang ở lớp nào, lớp đang ở trạng thái gì, và chuyển qua lại các nhóm nội dung dễ dàng.

Thông tin cần có:
- Đường dẫn quay về danh sách lớp.
- Tên lớp, trạng thái lớp (Đang chuẩn bị / Đang hoạt động / Đã lưu trữ), môn học, nhãn.
- Năm nhóm nội dung để chuyển qua lại: Tổng quan; Học sinh (kèm số lượng); Giáo viên (kèm số lượng); Lịch; Buổi học. Nhóm đang xem phải được ghi nhớ để tải lại màn hình vẫn đúng chỗ.
- Thông điệp theo trạng thái: lớp Đang chuẩn bị = "Lớp chưa có giáo viên phụ trách nên chưa vận hành. Hãy phân công giáo viên."; lớp Đã lưu trữ = "Lớp đã lưu trữ, lịch sử vẫn xem được."

Hành động: lưu trữ lớp (chỉ khi lớp chưa lưu trữ); chuyển nhóm nội dung; đi tới hồ sơ học sinh, hồ sơ giáo viên, nhật ký audit của lớp này.

Quy tắc nghiệp vụ:
- Trạng thái lớp tự động theo giáo viên: có giáo viên đầu tiên thì thành Đang hoạt động; giáo viên cuối cùng bị kết thúc phân công thì về Đang chuẩn bị; Đã lưu trữ do Admin đặt tay và không tự khôi phục. Vì vậy không có hành động "kích hoạt lớp".
- Lớp đã lưu trữ không nhận thêm hoạt động mới nên các hành động ghi bị vô hiệu hóa.

Tình huống đặc biệt: đang tải; không tìm thấy lớp ("Không tìm thấy lớp học"); một nhóm nội dung lỗi nhưng phần còn lại vẫn dùng được; lớp ở cả ba trạng thái.

Dữ liệu mẫu: Toán 9A · Ca tối · Toán · Đang hoạt động · 18 học sinh · 1 giáo viên.
```

### AD-10a — Chi tiết lớp: Tổng quan

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Tổng quan (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin mở một lớp để xem thông tin chung, chỉnh tên hoặc mô tả, hoặc lưu trữ lớp khi lớp kết thúc. Quan trọng nhất là an toàn khi lưu trữ vì không hoàn tác được qua giao diện.

Thông tin cần có: tên lớp, nhãn, mô tả, môn học, trạng thái lớp (Đang chuẩn bị / Đang hoạt động / Đã lưu trữ); số liệu nhanh: số học sinh, số giáo viên, số buổi sắp tới; lịch sử thay đổi gần đây của lớp (ai làm gì, khi nào).

Hành động:
- Sửa tên, nhãn, mô tả (không đổi được môn; cần ghi chú điều này). Có trạng thái đang lưu.
- Lưu trữ lớp: bước xác nhận nêu rõ "Lớp sẽ không còn hoạt động. Lịch sử điểm danh, bài làm và kết quả được giữ nguyên và vẫn xem được. Không khôi phục được qua giao diện." Không cần nhập lý do. Trước đó nhắc Admin kiểm tra học sinh còn trong lớp và lịch còn hiệu lực, vì hệ thống không tự kết thúc chúng.

Quy tắc nghiệp vụ:
- Không có hành động chuyển trạng thái nào khác ngoài lưu trữ; trạng thái Đang chuẩn bị / Đang hoạt động tự thay đổi theo việc phân công giáo viên.
- Tên lớp không được để trống: "Vui lòng nhập tên lớp."

Tình huống đặc biệt: đang tải; lỗi tải có thử lại; chưa có lịch sử thay đổi; lớp đã lưu trữ (toàn bộ thông tin chỉ đọc, kèm thông báo); đang lưu; lưu thất bại giữ nguyên nội dung đã nhập.

Dữ liệu mẫu: Toán 9A · nhãn "Ca tối" · mô tả "Luyện thi vào lớp 10, học thứ Ba và thứ Năm" · Toán · Đang hoạt động · 18 học sinh · 1 giáo viên · 8 buổi sắp tới.
```

### AD-10b — Chi tiết lớp: Học sinh

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Học sinh (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin xếp học sinh vào lớp, cho em rút khỏi lớp hoặc chuyển lớp, và đôi khi phải sửa lại ngày vào/ra khi phụ huynh báo sai. Quan trọng nhất là chính xác và có lý do rõ ràng, vì sửa ngày ảnh hưởng điểm danh các buổi cũ. Chỉ Admin được làm các việc này. Cần xem được cả lớp khoảng 30 em mà không phải cuộn quá nhiều.

Thông tin cần có: tên lớp; với mỗi học sinh đang trong lớp: họ tên, ngày vào lớp, ngày hiệu lực đến (trống = không giới hạn); ghi chú "Sẽ rời lớp ngày …" nếu ngày đến ở tương lai; phần những em đã rời lớp.

Hành động:
- Tìm học sinh trong lớp; mở hồ sơ học sinh.
- Thêm học sinh: tìm và chọn (loại các em đã ở trong lớp), thêm liên tiếp nhiều em và thấy các em vừa thêm. Ngày vào lớp là thời điểm thêm.
- Kết thúc tư cách thành viên (rút khỏi lớp / chuyển lớp): bắt buộc nhập lý do. Nói rõ dữ liệu cũ (điểm danh, bài nộp, kết quả) vẫn thuộc lớp này, không chuyển sang lớp mới. Gợi ý chuyển lớp: kết thúc ở đây rồi thêm vào lớp đích.
- Sửa thời hạn thành viên (hồi tố): sửa ngày vào và/hoặc ngày ra, bắt buộc nhập lý do, thấy giá trị cũ và mới trước khi xác nhận, chỉ áp dụng cho em đang trong lớp. Cảnh báo mạnh: "Sửa ngày vào/ra ảnh hưởng việc em có được tính vào điểm danh các buổi trước hay không. Thay đổi được ghi vào nhật ký kèm lý do."

Quy tắc nghiệp vụ:
- Em đã là thành viên: "Học sinh đã là thành viên đang hoạt động của lớp này."
- Ngày ra phải sau ngày vào: "Ngày kết thúc phải sau ngày bắt đầu."
- Thiếu lý do: "Vui lòng nhập lý do."
- Thành viên không còn hiệu lực khi đang sửa: báo "Học sinh này không còn trong lớp", làm mới danh sách.

Tình huống đặc biệt: đang tải; lớp chưa có học sinh ("Lớp chưa có học sinh" kèm gợi ý thêm); lỗi tải; đang lưu; lớp đã lưu trữ (không cho thêm, sửa).

Dữ liệu mẫu: Nguyễn Minh Anh · vào 05/09/2026 · không giới hạn; Lê Gia Hân · vào 12/10/2026; lý do mẫu: "Chuyển sang lớp Toán 9B theo nguyện vọng phụ huynh."
```

### AD-10c — Chi tiết lớp: Giáo viên

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Giáo viên (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin phân công giáo viên phụ trách lớp, hoặc cho giáo viên thôi phụ trách khi đổi người dạy. Việc này quyết định lớp có vận hành hay không, nên cần thấy rõ hệ quả lên trạng thái lớp.

Thông tin cần có: tên lớp và trạng thái hiện tại; với mỗi giáo viên đang phụ trách: họ tên, phụ trách từ ngày nào. Một lớp có thể có nhiều giáo viên cùng phụ trách, không có "giáo viên chính".

Hành động:
- Mở hồ sơ giáo viên.
- Phân công giáo viên: chọn từ các giáo viên đang hoạt động; loại những người đã phụ trách lớp; giáo viên đã ngừng hoạt động hiển thị mờ kèm chú thích và không chọn được. Phân công thành công mà lớp đang Đang chuẩn bị thì thông báo "Lớp đã chuyển sang Đang hoạt động".
- Kết thúc phân công: bước xác nhận (không cần lý do) nêu "Giáo viên không còn thấy và quản lý lớp này; lịch sử được giữ nguyên." Nếu là giáo viên cuối cùng thì thêm: "Lớp sẽ trở về Đang chuẩn bị." Nhắc Admin kiểm tra các lịch học đang áp dụng vì chúng không tự hủy.

Quy tắc nghiệp vụ:
- Giáo viên ngừng hoạt động: "Giáo viên này đã ngừng hoạt động nên không phân công được."
- Đã phụ trách: "Giáo viên đã phụ trách lớp này."
- Hai thông báo trên phải hiện được kể cả khi danh sách chọn đã lọc đúng (có thể hai quản trị viên thao tác cùng lúc).
- Kết thúc phân công người không còn hiệu lực: báo và làm mới.

Tình huống đặc biệt: đang tải; lớp chưa có giáo viên ("Chưa có giáo viên phụ trách, lớp chưa vận hành" kèm hành động phân công); lỗi tải; thông báo lớp đổi trạng thái; lớp đã lưu trữ (không phân công thêm).

Dữ liệu mẫu: Nguyễn Thị Hạnh · phụ trách từ 05/09/2026; lỗi mẫu: "Cô Trần Thu Hà đã nghỉ dạy nên không thể phân công."
```

### AD-10d — Chi tiết lớp: Lịch

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Lịch (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin thiết lập khung giờ học lặp hằng tuần cho lớp (ví dụ thứ Ba và thứ Năm 17:30), sửa khi đổi giờ, hoặc hủy khi lịch không còn dùng. Giáo viên của lớp cũng thao tác được các việc này. Quan trọng nhất là thấy hậu quả: tạo lịch sinh ra bao nhiêu buổi, có trùng lịch giáo viên không.

Thông tin cần có: các lịch của lớp; mỗi lịch gồm: các thứ trong tuần (Thứ Hai … Chủ nhật), giờ bắt đầu–kết thúc và thời lượng (ví dụ 17:30–19:00, 90 phút), thời gian áp dụng (từ ngày … đến ngày … hoặc không giới hạn), trạng thái (Đang áp dụng / Đã hủy / Đã kết thúc). Lịch không lặp chỉ có đúng một buổi vào ngày bắt đầu.

Hành động:
- Tạo lịch: ngày bắt đầu (bắt buộc), ngày kết thúc, giờ bắt đầu (bắt buộc), thời lượng phút (bắt buộc), có lặp hằng tuần hay không, chọn các thứ (bắt buộc khi lặp). Tạo xong hệ thống sinh ngay các buổi cho 4 tuần tới; phải cho thấy số buổi vừa tạo và cảnh báo trùng lịch giáo viên, ví dụ "Cô Hạnh trùng lịch với lớp Văn 8B (Thứ Ba 17:30)". Cảnh báo chỉ để biết, không chặn.
- Sửa lịch: đổi giờ, thời lượng, các thứ, ngày kết thúc; bắt buộc nhập lý do. Nhắc: chỉ ảnh hưởng các buổi chưa sinh; buổi đã sinh giữ nguyên.
- Hủy lịch: bắt buộc nhập lý do; nhắc buổi đã sinh không bị xóa, cần hủy riêng từng buổi nếu muốn; người liên quan sẽ được thông báo.

Quy tắc nghiệp vụ:
- Thiếu thông tin: "Vui lòng chọn ngày bắt đầu." / "Ngày kết thúc phải sau hoặc bằng ngày bắt đầu." / "Thời lượng phải từ 1 phút." / "Vui lòng chọn ít nhất một ngày trong tuần." / "Vui lòng nhập lý do."
- Không kiểm tra trùng phòng học.

Tình huống đặc biệt: đang tải; lớp chưa có lịch ("Chưa có lịch học" kèm hành động tạo lịch); lỗi tải; lịch đã hủy hiển thị mờ, không sửa được; đang lưu; kết quả tạo lịch có cảnh báo trùng; không có quyền.

Dữ liệu mẫu: Thứ Ba, Thứ Năm · 17:30 · 90 phút · từ 05/09/2026; lý do sửa: "Dời sang 18:00 vì học sinh tan trường muộn."
```

### AD-10e — Chi tiết lớp: Buổi học

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Buổi học (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin xem các buổi học của một lớp trong tháng, thêm buổi bù hoặc buổi lẻ, sửa giờ, hủy buổi khi giáo viên ốm, hoặc đánh dấu hoàn tất. Quan trọng nhất là chính xác, và thấy ngay buổi nào cần rà soát điểm danh.

Thông tin cần có: khoảng ngày đang xem (mặc định tháng hiện tại); mỗi buổi: ngày, giờ (17:30–19:00), trạng thái (Sắp diễn ra / Đã hoàn tất / Đã hủy), dấu "Cần rà soát điểm danh" khi buổi đã hủy nhưng đã có điểm danh. Chi tiết một buổi có thêm nội dung bài học, ghi chú, và lý do hủy nếu có.

Hành động:
- Đổi khoảng ngày xem.
- Thêm buổi lẻ / buổi bù: giờ bắt đầu và giờ kết thúc (bắt buộc, kết thúc sau bắt đầu), nội dung, ghi chú.
- Sửa buổi: giờ, nội dung, ghi chú. Lý do bắt buộc nếu buổi đã hoàn tất, tùy chọn ở trạng thái khác.
- Hủy buổi: bắt buộc nhập lý do. Nói rõ điểm danh đã có không bị xóa mà được đánh dấu cần rà soát, và học sinh, phụ huynh sẽ được thông báo.
- Hoàn tất buổi: xác nhận đơn giản.
- Mở buổi để điểm danh và xem chi tiết (màn hình này dùng chung với giáo viên; Admin vào được mọi lớp).

Quy tắc nghiệp vụ:
- Buổi đã hủy thì không điểm danh được: "Buổi này đã hủy nên không điểm danh được."
- Lỗi mẫu: "Giờ kết thúc phải sau giờ bắt đầu." / "Vui lòng nhập lý do sửa buổi đã hoàn tất." / "Vui lòng nhập lý do hủy."
- Giờ nhập được hiểu theo giờ Việt Nam.

Tình huống đặc biệt: đang tải; không có buổi nào trong khoảng ngày ("Chưa có buổi nào trong khoảng này" kèm gợi ý thêm buổi hoặc tạo lịch); lỗi tải; buổi đã hủy hiển thị mờ; buổi cần rà soát; đang lưu; ngày lọc sai định dạng.

Dữ liệu mẫu: Thứ Ba 21/10/2026 · 17:30–19:00 · Đã hoàn tất; Thứ Năm 23/10/2026 · Đã hủy, lý do "Cô Hạnh ốm" · Cần rà soát điểm danh.
```

### AD-11 — Lịch tổng hợp

```text
Màn hình: Lịch tổng hợp toàn trung tâm (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin muốn nhìn cả trung tâm trong một tuần: lớp nào học khi nào, giáo viên nào đang bận, chỗ nào trùng giờ. Màn hình chỉ để xem và đi tiếp, không sửa dữ liệu tại đây. Quan trọng nhất là nhìn tổng thể nhanh và nhận ra ngay xung đột.

Thông tin cần có: khoảng thời gian đang xem, xem được theo ngày, tuần hoặc tháng; mỗi buổi học: giờ, tên lớp, giáo viên, trạng thái (Sắp diễn ra / Đã hoàn tất / Đã hủy), dấu cần rà soát điểm danh; dấu hiệu khi cùng một giáo viên có hai buổi trùng giờ; ý nghĩa của các trạng thái.

Hành động:
- Chuyển ngày/tuần/tháng, quay về hôm nay.
- Lọc theo lớp, giáo viên, môn; ẩn buổi đã hủy.
- Bấm một buổi để mở buổi đó (quản lý buổi học của lớp tương ứng).
- Thêm buổi: chọn lớp rồi chuyển sang màn quản lý buổi học của lớp.

Quy tắc nghiệp vụ:
- Cảnh báo trùng giờ giáo viên chỉ để thông tin, không chặn việc nào.
- Không kiểm tra trùng phòng học (hệ thống chưa quản lý địa điểm).

Tình huống đặc biệt: đang tải; tuần không có buổi ("Không có buổi học trong tuần này"); dữ liệu của một lớp tải lỗi nhưng các lớp còn lại vẫn hiện; buổi đã hủy; lớp chưa có lịch; không có quyền.

Dữ liệu mẫu: Thứ Ba: 17:30–19:00 Toán 9A (cô Hạnh); 17:30–19:00 Văn 8B (cô Hạnh) — trùng giáo viên; 19:15–20:45 Anh 6C (thầy Nam).
```

### AD-04 — Danh sách học sinh

```text
Màn hình: Danh sách học sinh (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Khi phụ huynh đăng ký cho con hoặc báo "con chưa vào được hệ thống", Admin tìm hồ sơ học sinh, xem em đã có lớp và tài khoản chưa, hoặc thêm học sinh mới. Quan trọng nhất là tìm nhanh và thấy ai còn thiếu tài khoản.

Thông tin cần có: tổng số học sinh; với mỗi học sinh: họ tên, email, số điện thoại, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), tình trạng tài khoản (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa), số lớp đang học; số học sinh chưa có tài khoản (ví dụ "Chưa có tài khoản: 23").

Hành động:
- Tìm theo tên; lọc theo trạng thái hồ sơ và tình trạng tài khoản; xóa bộ lọc.
- Mở chi tiết học sinh.
- Thêm học sinh mới.
- Với từng học sinh: xem, sửa, mời tạo tài khoản (chỉ khi chưa có tài khoản).

Quy tắc nghiệp vụ:
- Hồ sơ nghi trùng (cùng tên và số điện thoại) do Admin tự kiểm tra, hệ thống không tự gộp.
- Đổi bộ lọc thì về đầu danh sách; quay lại từ chi tiết vẫn giữ bộ lọc; sau khi tạo hoặc sửa xong, học sinh vừa thay đổi được làm nổi.

Tình huống đặc biệt: đang tải; chưa có học sinh ("Chưa có học sinh. Thêm học sinh đầu tiên"); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; học sinh Ngừng hoạt động hiển thị mờ nhưng vẫn mở được.

Dữ liệu mẫu: Nguyễn Minh Anh · minhanh.parent@gmail.com · 0987 654 321 · Đang hoạt động · Chưa mời · 2 lớp; Lê Gia Hân · — · — · Đang hoạt động · Đang hoạt động · 1 lớp.
```

### AD-05 — Chi tiết học sinh

```text
Màn hình: Chi tiết học sinh (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin cần toàn cảnh về một học sinh: thông tin liên hệ, đang học lớp nào, phụ huynh là ai, học lực ra sao, đã có tài khoản chưa; và xử lý các việc liên quan. Quan trọng nhất là đầy đủ và rõ phần nào còn thiếu.

Thông tin cần có:
- Họ tên, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động).
- Thông tin chung: email, số điện thoại, ghi chú.
- Tài khoản: chưa mời, đã mời, hoặc đã có (tên đăng nhập, trạng thái).
- Các lớp đang học: tên lớp, môn, ngày vào lớp.
- Phụ huynh liên kết: tên, quan hệ (Bố, Mẹ…), trạng thái; riêng phần liên kết đã kết thúc.
- Tiến độ học tập: điểm trung bình, số bài đã chấm, điểm trung bình theo môn.

Hành động: sửa hồ sơ; ngừng hoạt động hoặc khôi phục hồ sơ (xác nhận, không cần lý do); mời tạo tài khoản (khi chưa có); xem tài khoản; liên kết thêm phụ huynh; kết thúc liên kết phụ huynh; mở lớp hoặc hồ sơ phụ huynh; xem nhật ký thay đổi của học sinh.

Quy tắc nghiệp vụ:
- Ngừng hoạt động hồ sơ không xóa việc học sinh đang ở trong lớp hay liên kết với phụ huynh; lịch sử vẫn giữ.
- Một học sinh có thể có nhiều phụ huynh.

Tình huống đặc biệt: mỗi phần tải riêng, lỗi riêng; không tìm thấy học sinh; hồ sơ Ngừng hoạt động ("Học sinh ngừng hoạt động. Lịch sử vẫn được giữ."); chưa có lớp, chưa có phụ huynh, chưa có tài khoản (mỗi chỗ trống kèm lời mời hành động).

Dữ liệu mẫu: Nguyễn Minh Anh · minhanh.parent@gmail.com · Lớp: Toán 9A (từ 05/09/2026), Anh 9B · Phụ huynh: Nguyễn Văn Hùng (Bố, đang liên kết) · Điểm trung bình 8,4 · 12 bài đã chấm · Ghi chú "Học thêm buổi tối thứ Bảy".
```

### AD-05a — Tạo / sửa hồ sơ học sinh

```text
Màn hình: Tạo / sửa hồ sơ học sinh (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Phụ huynh vừa đăng ký cho con tại quầy, Admin nhập hồ sơ học sinh mới, hoặc chỉnh lại thông tin liên hệ. Quan trọng nhất là nhập nhanh, ít lỗi, và biết rằng cần có email nếu muốn mời tạo tài khoản sau này.

Thông tin cần có: họ tên (bắt buộc), email, số điện thoại, ghi chú (nhiều dòng). Khi sửa có thêm trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động). Chú thích cạnh email: "Cần email nếu muốn mời tạo tài khoản."

Hành động: lưu; hủy (hỏi xác nhận nếu đã nhập dở); khi đang lưu không bấm lưu lần nữa được. Hệ thống cảnh báo nhẹ nếu trùng họ tên hoặc số điện thoại với học sinh đã có, nhưng không chặn việc lưu. Lưu xong đưa tới chi tiết học sinh vừa tạo, kèm gợi ý "Mời tạo tài khoản" và "Thêm vào lớp".

Quy tắc nghiệp vụ:
- Thiếu họ tên: "Vui lòng nhập họ tên."
- Email sai định dạng: "Email không hợp lệ."
- Email và số điện thoại không bắt buộc, không kiểm tra trùng.
- Lỗi hiện ngay cạnh trường bị lỗi; lỗi chung hiện thành thông báo riêng, dễ thấy.
- Khi sửa, chỉ những trường thay đổi mới được lưu.
- Thành công chỉ cần thông báo ngắn "Đã lưu".

Tình huống đặc biệt: chưa nhập gì khi tạo; đang lưu; lỗi theo từng trường; mất kết nối nhưng giữ nguyên nội dung đã nhập; cảnh báo nghi trùng hồ sơ.

Dữ liệu mẫu: Họ tên "Phạm Thanh Tùng"; email "tung.pt2012@gmail.com"; số điện thoại "0905 112 233"; ghi chú "Chuyển từ trung tâm khác, yếu hình học".
```

### AD-05b — Mời tạo tài khoản

```text
Màn hình: Mời tạo tài khoản cho một hồ sơ (quản trị viên Admin; thiết bị ưu tiên: máy tính). Dùng chung cho học sinh, phụ huynh và giáo viên.

Tình huống: Hồ sơ đã nhập nhưng người đó chưa đăng nhập được. Admin tạo tài khoản và gửi thư để họ tự đặt mật khẩu. Quan trọng nhất là rõ ràng chuyện gì sẽ xảy ra tiếp theo, và không bỏ sót việc gán vai trò sau đó.

Thông tin cần có: tên người được mời (chỉ đọc); email nhận lời mời lấy từ hồ sơ (chỉ đọc); tên đăng nhập (bắt buộc, không được trùng với bất kỳ ai, gợi ý điền sẵn bằng email); lời giải thích: "Hệ thống gửi thư chứa liên kết đặt mật khẩu. Liên kết hết hạn sau 60 phút."

Hành động: gửi lời mời (xác nhận một bước, không cần lý do); nếu hồ sơ chưa có email thì không gửi được và có lối đi bổ sung email vào hồ sơ. Sau khi thành công: thông báo "Đã gửi lời mời tới a***@gmail.com" và hiện bước kế tiếp là gán vai trò cho tài khoản, vì mời xong người đó chưa có vai trò nào.

Quy tắc nghiệp vụ:
- Hồ sơ đã có tài khoản: "Hồ sơ này đã có tài khoản. Nếu người đó chưa đặt được mật khẩu, xem hướng dẫn gửi lại."
- Tên đăng nhập trùng hoặc bỏ trống: "Tên đăng nhập này đã được dùng." / "Vui lòng nhập tên đăng nhập."
- Tài khoản được tạo trước, thư gửi sau; thư gửi lỗi vẫn không hủy tài khoản, nên có thể hiện "đã mời" mà người nhận không có thư, khi đó hướng dẫn xem cách gửi lại lời mời.

Tình huống đặc biệt: trạng thái nhập; đang gửi; thành công; tên đăng nhập trùng; hồ sơ đã có tài khoản; thiếu email; gửi thư lỗi.

Dữ liệu mẫu: Người được mời "Phạm Thanh Tùng", email tung.pt2012@gmail.com, tên đăng nhập "tung.pt2012@gmail.com"; lỗi mẫu "Tên đăng nhập này đã được dùng."
```

### AD-05c — Gửi lại lời mời

```text
Màn hình: Gửi lại lời mời / giúp đặt mật khẩu (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Người được mời báo "liên kết hết hạn" hoặc "không nhận được thư". Admin cần cho họ một đường vào. Quan trọng nhất là hướng dẫn rõ ràng cách xử lý hiện có, và sẵn sàng cho cách gửi lại trực tiếp khi hệ thống hỗ trợ.

Thông tin cần có: tên người được mời, tên đăng nhập, email nhận thư, thời điểm đã mời và thời điểm liên kết hết hạn (liên kết có hiệu lực 60 phút); tình trạng "chưa đăng nhập lần nào".

Hành động:
- Gửi lại lời mời.
- Sao chép liên kết đặt mật khẩu để Admin gửi hộ qua Zalo khi thư không tới.
- Hướng dẫn hiện có: bảo người được mời mở chức năng "Quên mật khẩu" ở màn hình đăng nhập, nhập đúng tên đăng nhập hoặc email để nhận liên kết mới (cũng hết hạn sau 60 phút).

Quy tắc nghiệp vụ:
- Không mời lần hai được vì hồ sơ đã có tài khoản.
- Người mở liên kết đã hết hạn sẽ thấy lỗi; câu hướng dẫn cho họ là "Liên hệ quản trị viên để được gửi lại."
- Vẽ cả trạng thái hướng dẫn hiện có lẫn trạng thái đầy đủ khi gửi lại trực tiếp được.

Tình huống đặc biệt: trạng thái hướng dẫn (hiện tại); đã gửi lại thành công; đã sao chép liên kết; bị giới hạn số lần gửi trong một khoảng thời gian; gửi lỗi, có thử lại.

Dữ liệu mẫu: "Trần Văn Nam (giáo viên) được mời lúc 08:10, liên kết hết hạn 09:10, chưa đăng nhập." Email nhận: nam.tv@piclass.vn.
```

### AD-06 — Danh sách phụ huynh

```text
Màn hình: Danh sách phụ huynh (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin tìm một phụ huynh, kiểm tra họ đã được liên kết với con chưa, đã có tài khoản chưa. Phụ huynh chưa liên kết với em nào thì khi đăng nhập sẽ không thấy gì, nên cần phát hiện sớm.

Thông tin cần có: tổng số phụ huynh; mỗi phụ huynh: họ tên, email, số điện thoại, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), tình trạng tài khoản (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa), số con đang liên kết; dấu cảnh báo cho phụ huynh chưa liên kết con nào.

Hành động:
- Tìm theo tên; lọc theo trạng thái hồ sơ, tình trạng tài khoản, có con liên kết hay chưa; xóa bộ lọc.
- Mở chi tiết phụ huynh; thêm phụ huynh mới.
- Với từng phụ huynh: xem, sửa, mời tạo tài khoản, liên kết với học sinh.

Quy tắc nghiệp vụ:
- Không bắt buộc học sinh nào cũng có phụ huynh, nhưng phụ huynh không có liên kết thì cổng phụ huynh trống.
- Đổi bộ lọc thì về đầu danh sách; quay lại từ chi tiết giữ bộ lọc.

Tình huống đặc biệt: đang tải; chưa có phụ huynh ("Chưa có phụ huynh. Thêm phụ huynh đầu tiên"); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; hồ sơ Ngừng hoạt động hiển thị mờ; phụ huynh chưa liên kết con nào.

Dữ liệu mẫu: Nguyễn Văn Hùng · hung.nv@gmail.com · 0987 654 321 · Đang hoạt động · Đang hoạt động · 2 con; Lê Thị Mai Phương · mai.phuong@gmail.com · Đang hoạt động · Chưa mời · chưa liên kết con nào.
```

### AD-06a — Chi tiết phụ huynh

```text
Màn hình: Chi tiết phụ huynh (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin xem phụ huynh này là phụ huynh của những em nào, xử lý tài khoản của họ, và gỡ liên kết khi gia đình đề nghị. Quan trọng nhất là hiểu hậu quả: gỡ liên kết là phụ huynh mất quyền xem dữ liệu của con ngay lập tức.

Thông tin cần có: họ tên, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động); email, số điện thoại; tài khoản (chưa mời / đã mời / đã có kèm tên đăng nhập và trạng thái); các con đang liên kết: tên con, quan hệ (Bố, Mẹ, Ông bà… do gia đình cung cấp, chữ tự do), từ ngày nào; các liên kết đã kết thúc (kèm ngày kết thúc và lý do nếu có).

Hành động: sửa hồ sơ; ngừng hoạt động hoặc khôi phục hồ sơ; mời tạo tài khoản; liên kết thêm học sinh; kết thúc liên kết với một em; mở hồ sơ của con.

Quy tắc nghiệp vụ:
- Đổi trạng thái hồ sơ không ảnh hưởng các liên kết đang có.
- Kết thúc liên kết có hiệu lực ngay, kể cả với dữ liệu lịch sử của con.

Tình huống đặc biệt: đang tải; lỗi tải; không tìm thấy phụ huynh; hồ sơ Ngừng hoạt động; chưa có con nào liên kết (kèm lời mời liên kết).

Dữ liệu mẫu: Nguyễn Văn Hùng · Bố của Nguyễn Minh Anh (liên kết từ 05/09/2026, đang hiệu lực); Mẹ của Nguyễn Gia Bảo (đã kết thúc 01/10/2026, lý do "Con chuyển trung tâm").
```

### AD-06b — Tạo / sửa hồ sơ phụ huynh

```text
Màn hình: Tạo / sửa hồ sơ phụ huynh (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin thêm phụ huynh khi đăng ký cho con, hoặc sửa số điện thoại, email khi họ đổi. Quan trọng nhất là nhập nhanh và, sau khi lưu, nhớ liên kết phụ huynh với con.

Thông tin cần có: họ tên (bắt buộc), email, số điện thoại. Khi sửa có thêm trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động). Không có ghi chú. Chú thích cạnh email: "Cần email nếu muốn mời tạo tài khoản."

Hành động: lưu; hủy (hỏi xác nhận nếu đã nhập dở); lưu xong đưa tới chi tiết phụ huynh kèm gợi ý "Liên kết với học sinh". Cảnh báo nhẹ, không chặn, nếu trùng họ tên hoặc số điện thoại với phụ huynh đã có.

Quy tắc nghiệp vụ:
- Thiếu họ tên: "Vui lòng nhập họ tên."
- Email sai định dạng: "Email không hợp lệ."
- Lỗi hiện ngay cạnh trường bị lỗi; khi sửa chỉ lưu trường đã đổi; thành công chỉ cần thông báo ngắn "Đã lưu".

Tình huống đặc biệt: chưa nhập gì khi tạo; đang lưu (không bấm lưu hai lần); lỗi theo trường; mất kết nối nhưng giữ nội dung đã nhập; cảnh báo nghi trùng hồ sơ.

Dữ liệu mẫu: Họ tên "Lê Thị Mai Phương"; email mai.phuong@gmail.com; số điện thoại 0908 223 344.
```

### AD-06c — Liên kết phụ huynh – học sinh

```text
Màn hình: Liên kết phụ huynh – học sinh (quản trị viên Admin; thiết bị ưu tiên: máy tính). Dùng được từ hồ sơ phụ huynh và từ hồ sơ học sinh.

Tình huống: Admin xác nhận "người này là phụ huynh của em này" để phụ huynh xem được dữ liệu của con; hoặc gỡ khi không còn quan hệ. Quan trọng nhất là chọn đúng hai bên và hiểu hậu quả khi gỡ.

Thông tin cần có:
- Khi tạo liên kết: người còn lại cần chọn (tìm theo tên, nếu đang ở hồ sơ phụ huynh thì chọn học sinh, và ngược lại); loại quan hệ (tùy chọn, chữ tự do, gợi ý nhanh: Bố, Mẹ, Ông, Bà, Người giám hộ).
- Khi kết thúc liên kết: tên hai bên, loại quan hệ, ô lý do.

Hành động:
- Liên kết (không cần lý do).
- Kết thúc liên kết: bắt buộc nhập lý do. Cảnh báo: "Phụ huynh sẽ không xem được dữ liệu của con ngay lập tức, kể cả lịch sử. Muốn khôi phục phải tạo liên kết mới."
- Cho thấy liên kết đã kết thúc ở dạng mờ, kèm lý do.

Quy tắc nghiệp vụ:
- Một học sinh có nhiều phụ huynh, một phụ huynh có nhiều con.
- Đã liên kết rồi: "Hai người này đã được liên kết."
- Liên kết đã kết thúc trước đó: "Liên kết này đã kết thúc rồi." (làm mới danh sách).
- Thiếu lý do: "Vui lòng nhập lý do."

Tình huống đặc biệt: trạng thái nhập; đang lưu; trùng liên kết; liên kết đã kết thúc; lỗi tải kết quả tìm; không có quyền.

Dữ liệu mẫu: Nguyễn Văn Hùng ↔ Nguyễn Minh Anh, quan hệ "Bố"; lý do kết thúc mẫu: "Gia đình đề nghị gỡ vì con đã nghỉ học."
```

### AD-07 — Danh sách giáo viên

```text
Màn hình: Danh sách giáo viên (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin xem ai đang dạy, ai chưa có lớp, ai đã nghỉ, và thêm giáo viên mới khi tuyển người. Quan trọng nhất là tìm nhanh và biết giáo viên nào còn có thể nhận thêm lớp.

Thông tin cần có: tổng số giáo viên; mỗi giáo viên: họ tên, chuyên môn, email, số điện thoại, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), tình trạng tài khoản (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa), số lớp đang dạy.

Hành động:
- Tìm theo tên; lọc theo trạng thái hồ sơ, tình trạng tài khoản, có lớp hay chưa có lớp; xóa bộ lọc.
- Mở chi tiết giáo viên; thêm giáo viên mới.
- Với từng giáo viên: xem, sửa, mời tạo tài khoản.

Quy tắc nghiệp vụ:
- Giáo viên Ngừng hoạt động không được phân công vào lớp mới.
- Đổi bộ lọc thì về đầu danh sách; quay lại từ chi tiết giữ bộ lọc; sau khi tạo hoặc sửa xong, giáo viên vừa thay đổi được làm nổi.

Tình huống đặc biệt: đang tải; chưa có giáo viên ("Chưa có giáo viên. Thêm giáo viên đầu tiên"); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; giáo viên Ngừng hoạt động hiển thị mờ nhưng vẫn mở được.

Dữ liệu mẫu: Nguyễn Thị Hạnh · Toán · hanh.nt@piclass.vn · Đang hoạt động · Đang hoạt động · 3 lớp; Trần Văn Nam · Tiếng Anh · — · Ngừng hoạt động · Chưa mời · 0 lớp.
```

### AD-07a — Chi tiết giáo viên

```text
Màn hình: Chi tiết giáo viên (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Trước khi cho một giáo viên nghỉ hoặc đổi người dạy, Admin xem họ đang dạy những lớp nào. Quan trọng nhất là hiểu vì sao không thể ngừng hoạt động khi còn lớp, và đi thẳng tới nơi gỡ phân công.

Thông tin cần có: họ tên, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), chuyên môn; email, số điện thoại; tài khoản (chưa mời / đã mời / đã có kèm tên đăng nhập và trạng thái); các lớp đang dạy: tên lớp, môn, phụ trách từ ngày nào.

Hành động: sửa hồ sơ; ngừng hoạt động hồ sơ (xác nhận, không cần lý do) hoặc khôi phục; mời tạo tài khoản; mở một lớp để kết thúc phân công.

Quy tắc nghiệp vụ:
- Giáo viên còn phân công lớp đang hoạt động thì không ngừng hoạt động được: "Cô Hạnh còn dạy Toán 9A, Toán 8B. Hãy kết thúc phân công trước." Thông báo liệt kê các lớp, mỗi lớp là lối đi tới phần Giáo viên của lớp đó.
- Ngừng hoạt động hồ sơ giáo viên khác với vô hiệu hóa tài khoản đăng nhập; cả hai đều bị chặn khi còn phân công. Cần có chú thích giúp Admin không nhầm hai việc này.

Tình huống đặc biệt: mỗi phần tải riêng, lỗi riêng; không tìm thấy giáo viên; hồ sơ Ngừng hoạt động; chưa dạy lớp nào (kèm gợi ý phân công); bị chặn khi ngừng hoạt động.

Dữ liệu mẫu: Nguyễn Thị Hạnh · Toán THCS · Lớp: Toán 9A, Toán 8B (phụ trách từ 05/09/2026) · Tài khoản đang hoạt động.
```

### AD-07b — Tạo / sửa hồ sơ giáo viên

```text
Màn hình: Tạo / sửa hồ sơ giáo viên (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin thêm giáo viên mới, ghi chuyên môn, hoặc chỉnh thông tin khi giáo viên đổi liên hệ. Sau khi lưu, bước tự nhiên là mời tài khoản rồi phân công vào lớp.

Thông tin cần có: họ tên (bắt buộc), email, số điện thoại, chuyên môn (chữ tự do). Khi sửa có thêm trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động). Chú thích cạnh email: "Cần email nếu muốn mời tạo tài khoản."

Hành động: lưu; hủy (hỏi xác nhận nếu đã nhập dở); lưu xong đưa tới chi tiết giáo viên kèm gợi ý "Mời tạo tài khoản" rồi "Phân công vào lớp".

Quy tắc nghiệp vụ:
- Thiếu họ tên: "Vui lòng nhập họ tên."
- Email sai định dạng: "Email không hợp lệ."
- Chuyển sang Ngừng hoạt động có thể thất bại khi giáo viên còn dạy lớp: "Giáo viên còn phụ trách lớp đang hoạt động. Hãy kết thúc phân công trước." kèm tên các lớp.
- Lỗi hiện ngay cạnh trường bị lỗi; khi sửa chỉ lưu trường đã đổi; thành công chỉ cần thông báo ngắn "Đã lưu".

Tình huống đặc biệt: chưa nhập gì khi tạo; đang lưu (không bấm lưu hai lần); lỗi theo trường; bị chặn khi ngừng hoạt động; mất kết nối nhưng giữ nội dung đã nhập.

Dữ liệu mẫu: Họ tên "Vũ Hoàng Long"; chuyên môn "Ngữ văn THCS"; email long.vh@piclass.vn; số điện thoại 0933 445 566.
```

### AD-08 — Môn học

```text
Màn hình: Môn học (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin quản lý danh mục môn (Toán, Văn, Anh…) mà mọi lớp gắn vào. Số môn ít nên xem và quản lý ngay trong một màn hình, không cần màn chi tiết riêng. Quan trọng nhất là rõ môn nào còn dùng được để tạo lớp.

Thông tin cần có: các môn; mỗi môn: tên, mô tả, trạng thái (Đang dùng / Ngừng sử dụng), số lớp đang dùng môn.

Hành động:
- Lọc theo trạng thái.
- Thêm môn: tên (bắt buộc), mô tả.
- Sửa tên, mô tả.
- Ngừng sử dụng hoặc dùng lại một môn. Khi ngừng, giải thích: "Không thể tạo lớp mới cho môn này; các lớp đã có vẫn chạy và sửa bình thường."
- Không có hành động xóa môn.

Quy tắc nghiệp vụ:
- Thiếu tên: "Vui lòng nhập tên môn."
- Không kiểm tra trùng tên môn.
- Môn ngừng sử dụng không xuất hiện khi chọn môn lúc tạo lớp.

Tình huống đặc biệt: đang tải; chưa có môn nào ("Chưa có môn học. Thêm môn đầu tiên", kèm giải thích chưa có môn thì chưa tạo được lớp); lỗi tải có thử lại; môn ngừng sử dụng hiển thị mờ; đang lưu.

Dữ liệu mẫu: Toán · "Chương trình THCS" · Đang dùng · 6 lớp; Tin học · — · Ngừng sử dụng · 0 lớp.
```

### AD-12 — Khóa học

```text
Màn hình: Khóa học, gồm danh sách và chi tiết một khóa (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Khóa học là nhóm bài đánh giá cùng các học sinh tham gia, dùng cả cho dạy kèm ngoài lớp chính thức. Admin tạo khóa, xem ai đang học khóa nào, và lưu trữ khóa đã xong. Admin thấy mọi khóa; giáo viên cũng tự tạo được khóa của mình.

Thông tin cần có:
- Danh sách: tên khóa, mô tả, chủ sở hữu (tên giáo viên, hoặc "Dùng chung" nếu không thuộc ai), trạng thái (Đang mở / Đã lưu trữ).
- Chi tiết một khóa: tên, mô tả, môn học (có thể trống), chủ sở hữu, trạng thái; học sinh đang ghi danh; các bài đánh giá thuộc khóa.

Hành động:
- Lọc theo trạng thái, theo chủ sở hữu; mở một khóa.
- Tạo khóa: tên (bắt buộc), mô tả, môn (tùy chọn), chủ sở hữu (chọn giáo viên hoặc để trống = dùng chung; chỉ Admin có lựa chọn này). Khóa mới ở trạng thái Đang mở.
- Sửa tên, mô tả, môn.
- Lưu trữ khóa (không cần lý do): "Không ghi danh thêm được nữa; vẫn xem lịch sử và kết thúc ghi danh được." Không có hành động mở lại khóa qua giao diện.
- Đi tới việc ghi danh học sinh.

Quy tắc nghiệp vụ:
- Thiếu tên: "Vui lòng nhập tên khóa học."
- Khóa đã lưu trữ thì khóa việc ghi danh thêm.

Tình huống đặc biệt: đang tải; chưa có khóa ("Chưa có khóa học" kèm hành động tạo khóa); không có kết quả khớp bộ lọc; lỗi tải có thử lại; khóa đã lưu trữ kèm thông báo; không tìm thấy khóa; không có quyền.

Dữ liệu mẫu: "Luyện thi vào 10 — Toán" · chủ sở hữu cô Hạnh · Đang mở · 12 học sinh; "Ngữ pháp Anh cơ bản" · Dùng chung · Đã lưu trữ.
```

### AD-12a — Ghi danh vào khóa học

```text
Màn hình: Ghi danh vào khóa học (quản trị viên Admin; thiết bị ưu tiên: máy tính). Nằm trong phần chi tiết của một khóa học.

Tình huống: Admin đưa một học sinh, hoặc cả một lớp, vào khóa để các em nhận bài của khóa; hoặc cho một em rời khóa. Quan trọng nhất là hiểu việc ghi danh theo lớp chỉ chụp lại danh sách lớp tại thời điểm bấm.

Thông tin cần có: học sinh trong khóa: họ tên, từ ngày, đến ngày, trạng thái (Đang hiệu lực / Đã kết thúc); mặc định chỉ hiện các em đang hiệu lực, có cách xem cả em đã kết thúc. Việc ghi danh có hai cách: theo từng học sinh (tìm chọn một em) hoặc theo cả lớp (chọn một lớp), không chọn cả hai cùng lúc.

Hành động:
- Ghi danh theo học sinh hoặc theo lớp. Theo lớp: ghi danh tất cả thành viên đang trong lớp tại thời điểm bấm, bỏ qua em đã ghi danh; học sinh vào lớp sau đó không tự được ghi danh, cần nhắc rõ điều này.
- Hiện kết quả: "Đã ghi danh 14 học sinh"; nếu là 0 thì "Tất cả đã được ghi danh từ trước" (không phải lỗi).
- Kết thúc ghi danh một em: bắt buộc nhập lý do; em rời khóa nhưng lịch sử giữ.
- Mở hồ sơ học sinh.

Quy tắc nghiệp vụ:
- Khóa đã lưu trữ: "Khóa học đã lưu trữ nên không ghi danh thêm được." (vẫn kết thúc ghi danh được).
- Chưa chọn học sinh hay lớp, hoặc chọn cả hai: "Hãy chọn một học sinh hoặc một lớp."
- Thiếu lý do: "Vui lòng nhập lý do."

Tình huống đặc biệt: đang tải; khóa chưa có học sinh ("Chưa có học sinh trong khóa" kèm hành động ghi danh); lỗi tải; khóa đã lưu trữ; kết quả không có em nào mới.

Dữ liệu mẫu: ghi danh theo lớp Toán 9A, kết quả "Đã ghi danh 16 học sinh (2 em đã có từ trước)"; lý do kết thúc: "Phụ huynh xin nghỉ khóa luyện thi."
```

### AD-02 — Danh sách tài khoản

```text
Màn hình: Danh sách tài khoản đăng nhập (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Một phụ huynh gọi báo "không vào được hệ thống". Admin cần tìm đúng tài khoản và xem nó còn hoạt động không. Quan trọng nhất là tìm nhanh theo nhiều cách. Tài khoản chỉ được tạo từ lời mời hồ sơ nên màn này không có việc tạo tài khoản.

Thông tin cần có: tổng số tài khoản; mỗi tài khoản: họ tên (nếu chưa gắn hồ sơ thì hiện "(chưa có hồ sơ)"), tên đăng nhập, các vai trò đang giữ (Quản trị viên / Giáo viên / Học sinh / Phụ huynh, có thể nhiều), trạng thái (Đang hoạt động / Đã vô hiệu hóa).

Hành động:
- Tìm theo tên; tìm theo tên đăng nhập hoặc email; lọc theo vai trò; lọc theo trạng thái; xóa bộ lọc. Có chú thích tìm tên cần gõ có dấu.
- Chọn số tài khoản mỗi lần xem (có giới hạn hợp lý, tối đa 100), chuyển trang, biết đang ở trang mấy trên tổng bao nhiêu và tổng số kết quả.
- Mở chi tiết tài khoản.

Quy tắc nghiệp vụ:
- Lọc theo vai trò chỉ tính vai trò còn hiệu lực.
- Đổi bộ lọc thì về trang đầu; quay lại từ chi tiết giữ bộ lọc.

Tình huống đặc biệt: đang tải; chưa có tài khoản ("Chưa có tài khoản nào. Hãy tạo hồ sơ rồi mời" kèm lối tới danh sách học sinh); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; tài khoản đã vô hiệu hóa hiển thị mờ.

Dữ liệu mẫu: Nguyễn Thị Hạnh · hanh.nt@piclass.vn · Giáo viên, Quản trị viên · Đang hoạt động; Trần Quốc Bảo · 0912345678 · Phụ huynh · Đã vô hiệu hóa.
```

### AD-03 — Chi tiết tài khoản

```text
Màn hình: Chi tiết tài khoản (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin xem một người đang giữ vai trò nào, gán thêm hoặc kết thúc vai trò, khóa hoặc mở lại tài khoản. Điển hình: vừa mời xong thì tài khoản chưa có vai trò nào, phải gán ngay. Đây là thao tác nhạy cảm nên quan trọng nhất là an toàn và rõ hậu quả.

Thông tin cần có: họ tên, tên đăng nhập, trạng thái (Đang hoạt động / Đã vô hiệu hóa); các vai trò đang giữ; lịch sử vai trò (vai trò, từ ngày, đến ngày; vai trò đã kết thúc hiển thị mờ); hồ sơ gắn với tài khoản (tên, lối tới hồ sơ); lối xem nhật ký thay đổi của tài khoản.

Hành động:
- Gán vai trò: chọn trong bốn vai trò (Quản trị viên / Giáo viên / Học sinh / Phụ huynh), ẩn vai trò đã giữ, có bước xác nhận. Nhắc: vai trò Giáo viên, Học sinh, Phụ huynh chỉ có tác dụng khi người này có hồ sơ tương ứng.
- Kết thúc vai trò: xác nhận; có hiệu lực ngay, người dùng mất quyền ở lần thao tác tiếp theo.
- Vô hiệu hóa tài khoản: "Người này sẽ không đăng nhập được. Hồ sơ và lịch sử vẫn giữ nguyên, có thể kích hoạt lại. Các liên kết và phân công đã kết thúc không tự khôi phục." Không cần lý do. Kích hoạt lại: xác nhận đơn giản. Nếu tự vô hiệu hóa chính mình thì cảnh báo thêm "Bạn sẽ bị đăng xuất."

Quy tắc nghiệp vụ:
- Quản trị viên cuối cùng: "Đây là quản trị viên cuối cùng. Hãy gán quyền quản trị cho người khác trước." (khi kết thúc vai trò thì đổi câu cho đúng việc).
- Giáo viên còn dạy lớp đang hoạt động: "Cô Hạnh còn dạy Toán 9A, Văn 8B. Hãy kết thúc phân công trước." Liệt kê các lớp, mỗi lớp dẫn tới phần phân công giáo viên của lớp.
- Đã giữ vai trò: "Người này đang giữ vai trò này rồi."

Tình huống đặc biệt: đang tải; lỗi tải; không tìm thấy tài khoản; tài khoản đã vô hiệu hóa (thông báo, hành động chính thành "Kích hoạt lại"); chưa có vai trò nào (lời mời "Gán vai trò đầu tiên"); đang gửi.

Dữ liệu mẫu: Nguyễn Thị Hạnh · hanh.nt@piclass.vn · Đang hoạt động · Giáo viên (từ 05/09/2026), Quản trị viên (từ 12/10/2026).
```

### AD-13 — Cấp lại lượt làm bài

```text
Màn hình: Cấp lại lượt làm bài (quản trị viên Admin; thiết bị ưu tiên: máy tính). Chỉ Admin có chức năng này, giáo viên không có.

Tình huống: Học sinh đang làm bài kiểm tra hoặc bài thi thì gặp sự cố (mất điện, mất mạng, lỗi máy) nên cần một lượt làm mới, không bị tính lượt cũ. Admin xử lý từ chi tiết bài nộp của em. Đây là thao tác khó hoàn tác nên quan trọng nhất là Admin hiểu rõ hậu quả và ghi lại lý do.

Thông tin cần có: học sinh; tên bài; lượt sẽ bị hủy (số lượt, trạng thái, giờ nộp, điểm nếu đã chấm); các hậu quả: lượt cũ bị hủy, bài nộp của lượt đó thành không hợp lệ, kết quả đã công bố bị thu hồi (học sinh và phụ huynh không còn thấy và không tính vào tiến độ), học sinh nhận một lượt mới; ô lý do.

Hành động: cấp lại (chỉ dùng được khi đã nhập lý do); hủy bỏ. Thành công thì hiện lượt mới và quay về chi tiết bài nộp. Hành động chỉ xuất hiện với bài trắc nghiệm hoặc bài thi, không áp dụng cho bài tập, và không có với lượt đã hủy.

Quy tắc nghiệp vụ:
- Thiếu lý do: "Vui lòng nhập lý do cấp lại."
- Bài không phải kiểm tra hoặc thi: "Chỉ cấp lại được cho bài trắc nghiệm hoặc bài thi."
- Lượt đã hủy hoặc đã được cấp lại: "Lượt này đã bị hủy hoặc đã được cấp lại trước đó."
- Lượt mới vẫn theo giờ đóng hiện tại của bài; cần nói điều này để Admin không hiểu nhầm là được thêm thời gian.
- Khi lỗi, giữ nguyên lý do đã gõ.

Tình huống đặc biệt: trạng thái nhập; đang gửi; thành công; các lỗi trên; bài tập (không hiện hành động); lượt đã hủy (không hiện hành động).

Dữ liệu mẫu: Lê Gia Hân · "Kiểm tra 15 phút — Đại số" · Lượt 1, nộp 19:02 · Lý do: "Mất điện đúng giờ làm bài, đã xác nhận với phụ huynh."
```

### AD-14 — Nhật ký audit

```text
Màn hình: Nhật ký audit (quản trị viên Admin; thiết bị ưu tiên: máy tính)

Tình huống: Admin cần trả lời "ai đã đổi cái này, khi nào, vì sao", ví dụ khi phụ huynh thắc mắc về việc sửa ngày vào lớp, một buổi bị hủy, hay điểm được công bố. Nhật ký chỉ để đọc, không sửa hay xóa được. Quan trọng nhất là tìm đúng sự kiện và đọc hiểu sự thay đổi trước và sau.

Thông tin cần có:
- Bộ lọc: loại đối tượng, mã đối tượng, hành động, khoảng ngày, người thực hiện, lớp hoặc khóa học liên quan.
- Mỗi sự kiện: thời điểm (giờ Việt Nam), người thực hiện (tên), hành động bằng nhãn tiếng Việt (ví dụ Đổi trạng thái tài khoản, Gán / kết thúc vai trò, Đổi trạng thái hồ sơ, Liên kết / kết thúc liên kết phụ huynh – học sinh, Thêm / kết thúc / sửa hồi tố thành viên lớp, Phân công / kết thúc phân công giáo viên, Sửa / hủy lịch, Sửa / hủy buổi học, Tạo / sửa điểm danh, Duyệt đơn xin phép, Ghi danh / kết thúc ghi danh, Phát hành / đóng / mở lại / hủy bài, Nộp bài / cấp lại lượt làm bài, Công bố / thu hồi kết quả, Chấm / sửa điểm), đối tượng bị tác động, lý do (có thể trống).
- Chi tiết một sự kiện, đọc theo thứ tự: ai, làm gì, với cái gì (kèm lớp hoặc khóa liên quan), vì sao, và phần thay đổi so sánh giá trị trước với sau cho từng thuộc tính (thuộc tính đổi giá trị được làm nổi; thuộc tính chỉ có một phía ghi "(mới)" hoặc "(bỏ)"); có cách xem dữ liệu gốc.

Hành động: lọc, xóa lọc; mở và đóng chi tiết một sự kiện; sao chép mã; mở đối tượng liên quan (tài khoản, lớp…) khi xác định được. Sắp xếp mới nhất trước.

Quy tắc nghiệp vụ:
- Nhật ký luôn khớp dữ liệu thật và không bao giờ bị xóa.
- Lọc sai giá trị chỉ cho kết quả rỗng, không báo lỗi.
- Sự kiện tạo mới không có giá trị "trước".

Tình huống đặc biệt: đang tải; chưa có hoạt động nào; không có bản ghi khớp bộ lọc; lỗi tải có thử lại; không có quyền; nhật ký rất lớn (cảnh báo "Đang tải toàn bộ nhật ký, có thể chậm"); sự kiện lẽ ra phải có lý do nhưng thiếu.

Dữ liệu mẫu: 20/10/2026 09:12 · Nguyễn Thị Hạnh (Admin) · Sửa hồi tố thành viên lớp · Toán 9A · Lý do "Phụ huynh báo con đã vào lớp từ 01/10" · Trước: ngày vào 05/10/2026, sau: ngày vào 01/10/2026.
```
