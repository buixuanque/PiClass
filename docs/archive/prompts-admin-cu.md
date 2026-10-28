# Bộ prompt — Cổng Admin (làm trên base mockup đã chốt)

> **Lưu ý (2026-10-23):** không còn dùng Claude Design. File này giữ làm mô tả yêu cầu từng màn; màn mới dựng trực tiếp trong Claude Code, xem `../product/screens/mockup-plan.md`.

Gồm 34 prompt (29 màn và 5 prompt Ghép) cho cổng quản trị viên. **Mockup hiện tại chưa có màn Admin**, nên mọi màn ở đây là màn mới nhưng phải trông như cùng một sản phẩm với mockup Giáo viên: mỗi prompt có dòng `Mẫu bám theo` chỉ rõ lấy màn nào của mockup làm mẫu.

## Cách dùng

1. Phiên Claude Design đã có design system và mockup: dán [00-nen.md](../product/screens/prompts/00-nen.md) một lần.
2. Dán tiếp **Mẫu chung Admin** (khối ngay dưới) một lần, để các màn Admin dùng chung cách trình bày danh sách, chi tiết, form, hộp thoại.
3. Vẽ từng màn theo thứ tự trong bảng (màn nền và dùng thường xuyên trước, thiết lập một lần sau). Mỗi prompt tự đứng được. Prompt Ghép (`-G`) dán sau khi các phần của chức năng đó đã vẽ xong.
4. Prompt chỉ nói **cần gì** (thông tin, hành động, liên kết, quy tắc, trường hợp đặc biệt, dữ liệu mẫu). Bố cục bám mẫu mockup đã chốt; chi tiết bên trong vùng nội dung để Claude Design quyết.

Đặc tả đầy đủ của từng màn (dữ liệu, API, lỗi) ở `docs/product/screens/admin.md`.

## Mẫu chung Admin (dán một lần sau prompt nền)

```text
Cổng Admin dùng chung các mẫu sau. Tất cả bám mockup đã chốt, không tạo kiểu mới.

Điều hướng (thanh bên): Tổng quan, Tài khoản, Học sinh, Phụ huynh, Giáo viên, Môn học, Lớp học, Lịch, Khóa học, Nhật ký. Tài khoản vừa là Admin vừa là Giáo viên thấy hai nhóm "Giảng dạy" (mục của mockup Giáo viên) và "Quản trị" (các mục trên); bấm nhóm nào thì chuyển sang vai trò đó.

M1. Danh sách có lọc, tìm, phân trang: đầu trang như màn "Lớp học" của Giáo viên (tiêu đề, dòng đếm "124 học sinh", nút hành động chính bên phải, tab pill và chip lọc bên dưới, ô tìm kiếm, nút "Xóa lọc"). Bảng theo kiểu bảng học sinh trong màn Chi tiết lớp. Cột đầu là tên bấm được; cột trạng thái dùng Badge chấm; dòng không hoạt động hiển thị mờ nhưng vẫn mở được; có menu "…" cho thao tác nhanh. Đổi bộ lọc thì về trang 1; quay lại từ chi tiết giữ nguyên bộ lọc; sau khi tạo/sửa, dòng vừa đổi được làm nổi. Trạng thái: tải (khung xương), rỗng hoàn toàn (kèm nút tạo mới), rỗng do lọc (kèm "Xóa lọc"), lỗi (kèm "Thử lại"), không có quyền.

M2. Form tạo/sửa: nhóm trường theo thẻ, nhãn tiếng Việt, đánh dấu trường bắt buộc; nút Lưu (chính) và Hủy; Lưu khóa khi đang gửi; rời trang khi đang sửa dở thì hỏi lại; lỗi hiện ngay dưới từng trường; lỗi nghiệp vụ hiện thanh thông báo đầu form; thành công hiện Toast ngắn "Đã lưu" rồi về chi tiết hoặc danh sách.

M3. Chi tiết nhiều tab: theo màn "Chi tiết lớp" của Giáo viên (nút quay lại ghost, thẻ tiêu đề nền nhạt kèm trạng thái và nút hành động, hàng StatTile, Card chứa tab gạch chân). Tab đang chọn giữ khi tải lại; tab hiện số lượng ("Học sinh · 18"); mỗi tab có trạng thái tải và lỗi riêng; id sai thì hiện trang "Không tìm thấy".

M4. Hộp thoại xác nhận có lý do (kết thúc thành viên, phân công, ghi danh, liên kết; hủy lịch, hủy buổi; sửa hồi tố; cấp lại lượt làm bài): Dialog của design system. Tiêu đề nêu đúng hành động và đối tượng ("Kết thúc tư cách thành viên của Nguyễn Minh Anh trong Toán 9A"); dòng cảnh báo hậu quả bằng ngôn ngữ nghiệp vụ; ô Lý do nhiều dòng (bắt buộc khi nghiệp vụ yêu cầu) kèm vài chip lý do thường gặp; nút Xác nhận màu cảnh báo cho hành động khó hoàn tác; Hủy bỏ luôn có, Esc hoặc bấm nền cũng là hủy; lỗi nghiệp vụ thì giữ hộp thoại mở, hiện lỗi trong hộp thoại và giữ nguyên lý do đã gõ.

M5. Lỗi: 403 và 404 dùng cùng một thông điệp "Bạn không có quyền hoặc không tìm thấy nội dung này"; mất mạng hoặc lỗi máy chủ hiện banner "Không kết nối được, thử lại" và giữ dữ liệu đã nhập.

M6. Nhãn trạng thái dùng thống nhất: Vai trò Quản trị viên / Giáo viên / Học sinh / Phụ huynh. Tài khoản Đang hoạt động / Đã vô hiệu hóa. Hồ sơ Đang hoạt động / Ngừng hoạt động. Lớp Đang chuẩn bị / Đang hoạt động / Đã lưu trữ. Môn Đang dùng / Ngừng sử dụng. Khóa học Đang mở / Đã lưu trữ. Thành viên, phân công, ghi danh Đang hiệu lực / Đã kết thúc. Lịch Đang áp dụng / Đã hủy / Đã kết thúc. Buổi học Dự kiến / Đã hoàn tất / Đã hủy. Thứ trong lịch: Thứ Hai … Chủ nhật.
```

## Danh sách màn

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
| AD-10-G | Ghép: Chi tiết lớp học | Gộp khung và 5 phần thành một chức năng quản lý lớp |
| AD-11 | Lịch tổng hợp | Toàn trung tâm, chỉ xem và điều hướng |
| AD-05b | Mời tạo tài khoản | Dùng cho học sinh, phụ huynh, giáo viên |
| AD-05c | Gửi lại lời mời | Khi liên kết hết hạn hoặc không nhận được thư |
| AD-05-G | Ghép: Hồ sơ học sinh | Gộp chi tiết, tạo / sửa, mời, gửi lại lời mời |
| AD-06c | Liên kết phụ huynh – học sinh | Tạo và kết thúc liên kết |
| AD-06-G | Ghép: Hồ sơ phụ huynh | Gộp chi tiết, tạo / sửa, liên kết, mời |
| AD-07-G | Ghép: Hồ sơ giáo viên | Gộp chi tiết, tạo / sửa, mời |
| AD-08 | Môn học | Một màn hình gồm xem và quản lý môn |
| AD-12 | Khóa học | Danh sách, chi tiết, tạo, sửa, lưu trữ |
| AD-12a | Ghi danh vào khóa học | Theo học sinh hoặc theo cả lớp |
| AD-12-G | Ghép: Khóa học và ghi danh | Gộp danh sách, chi tiết khóa và ghi danh |
| AD-03 | Chi tiết tài khoản | Vai trò, khóa/mở tài khoản |
| AD-13 | Cấp lại lượt làm bài | Xử lý sự cố khi học sinh đang làm bài kiểm tra |

---

### AD-01 — Tổng quan

```text
Màn hình: Tổng quan
Mẫu bám theo (mockup đã chốt): màn Tổng quan (Giáo viên): lời chào, hàng StatTile, các thẻ việc cần chú ý

Thông tin cần có:
- Lời chào và ngày hôm nay.
- Mức hoàn tất thiết lập hệ thống (chỉ khi còn bước chưa xong).
- Số liệu chung: số học sinh, số giáo viên, số lớp đang hoạt động, số lớp đang chuẩn bị.
- Các buổi học hôm nay: giờ, lớp, giáo viên, trạng thái buổi (Sắp diễn ra / Đã hoàn tất / Đã hủy), đã điểm danh chưa.
- Việc cần xử lý: buổi đã hủy nhưng đã có điểm danh cần rà soát; lớp đang chuẩn bị chưa có giáo viên; tài khoản đã mời nhưng chưa kích hoạt; đơn xin phép chờ duyệt; bài chờ chấm.
- Lối tắt tới: Tạo lớp, Thêm học sinh, Xem nhật ký audit.
- Nếu người này còn là giáo viên: cách chuyển sang phần giảng dạy.

Hành động: chủ yếu là điều hướng. Mỗi số liệu, mỗi buổi học, mỗi việc cần xử lý đều bấm để đi tới màn tương ứng. Có hành động làm mới dữ liệu (không tự cập nhật liên tục).

Liên kết: Admin đến màn này đầu tiên sau khi đăng nhập. Từ đây: bấm số học sinh thì mở màn Danh sách học sinh; số giáo viên thì mở Danh sách giáo viên; số lớp thì mở Danh sách lớp học (đã áp sẵn bộ lọc trạng thái tương ứng); bấm một buổi học hôm nay thì mở màn Chi tiết lớp: Buổi học của đúng lớp đó, mang theo lớp và buổi đã chọn; bấm một việc cần xử lý thì mở màn tương ứng với đúng đối tượng cần xử lý (lớp, tài khoản, buổi học...); lời nhắc thiết lập thì mở màn Thiết lập lần đầu; lối tắt Tạo lớp thì mở Danh sách lớp học ở thao tác tạo lớp, Thêm học sinh thì mở Tạo / sửa hồ sơ học sinh, Xem nhật ký thì mở Nhật ký audit. Với mỗi điểm đến: nếu màn đó đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Hệ thống chưa có dữ liệu nào thì không hiện các con số 0 vô nghĩa, chỉ hướng dẫn thiết lập.
- Mỗi phần tải độc lập, phần nào xong thì hiện trước.

Tình huống đặc biệt:
- Đang tải từng phần; hệ thống trống hoàn toàn; không có việc cần xử lý ("Không có việc cần xử lý"); một phần tải lỗi (có cách thử lại riêng, các phần khác vẫn dùng được); không có quyền truy cập.

Dữ liệu mẫu: 128 học sinh, 9 giáo viên, 14 lớp đang hoạt động, 2 lớp đang chuẩn bị. Hôm nay: 17:30–19:00 Toán 9A (cô Hạnh), Đã hoàn tất; 19:15–20:45 Anh 6C, Sắp diễn ra. Cần xử lý: "Buổi Văn 8B ngày 20/10 đã hủy nhưng đã có điểm danh".
```

---

### AD-15 — Thiết lập lần đầu

```text
Màn hình: Thiết lập lần đầu
Mẫu bám theo (mockup đã chốt): màn Tổng quan (Giáo viên): thẻ danh sách việc kèm ProgressBar

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

Liên kết: đến từ lời nhắc thiết lập ở màn Tổng quan hoặc từ mục bắt đầu trong điều hướng chung (ẩn khi đã xong hết). Mỗi việc dẫn tới đúng màn thao tác: tạo môn thì mở màn Môn học; thêm giáo viên thì mở Tạo / sửa hồ sơ giáo viên; tạo lớp thì mở Danh sách lớp học ở thao tác tạo lớp; phân công giáo viên thì mở Chi tiết lớp: Giáo viên; thêm học sinh thì mở Tạo / sửa hồ sơ học sinh, rồi xếp vào lớp ở Chi tiết lớp: Học sinh; tạo lịch học thì mở Chi tiết lớp: Lịch; mời phụ huynh thì mở Tạo / sửa hồ sơ phụ huynh và Liên kết phụ huynh – học sinh. Làm xong một việc, quay về màn này phải thấy tiến độ đã cập nhật. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Thứ tự chỉ là gợi ý, không chặn: có thể tạo lớp trước khi có giáo viên, khi đó lớp ở trạng thái Đang chuẩn bị.
- Khi tạo lớp phải chọn môn đang được dùng, nên cần nhắc điều này ở bước tạo lớp.

Tình huống đặc biệt:
- Đang tải; tất cả đã xong ("Hệ thống đã sẵn sàng" kèm lối quay về màn tổng quan); không xác định được một bước nào đó (hiện là "chưa rõ", không được đánh dấu xong sai).

Dữ liệu mẫu: Môn học (3: Toán, Văn, Anh) đã xong; Giáo viên (2) đã xong; Lớp (Toán 9A) đã xong; Phân công giáo viên chưa xong; Học sinh vào lớp chưa xong; Lịch học chưa xong; Phụ huynh chưa xong. Tiến độ 3/7.
```

---

### AD-09 — Danh sách lớp học

```text
Màn hình: Danh sách lớp học
Mẫu bám theo (mockup đã chốt): màn Lớp học (Giáo viên): đầu trang, tab pill, chip lọc, lưới thẻ lớp

Thông tin cần có: số lượng lớp; với mỗi lớp: tên lớp, nhãn (ví dụ "Khóa 2026", "Ca tối"), môn học, trạng thái (Đang chuẩn bị / Đang hoạt động / Đã lưu trữ), và nếu có thể: giáo viên phụ trách, sĩ số, buổi kế tiếp. Tên lớp được phép trùng nhau nên nhãn và môn là thông tin giúp phân biệt, cần nổi bật.

Hành động:
- Tìm theo tên; lọc theo trạng thái lớp và theo môn; xóa bộ lọc.
- Mặc định ẩn lớp đã lưu trữ, có cách bật để hiện.
- Mở một lớp để xem chi tiết.
- Tạo lớp: chọn môn (chỉ liệt kê môn đang dùng), nhập tên lớp (bắt buộc), nhãn, mô tả. Ghi chú rõ: môn không đổi được sau khi tạo. Lớp mới ở trạng thái Đang chuẩn bị; tạo xong đưa Admin tới phần phân công giáo viên với lời nhắc "Phân công giáo viên để lớp bắt đầu hoạt động".
- Lưu trữ một lớp ngay từ danh sách.

Liên kết: đến từ điều hướng quản trị, từ màn Tổng quan (bấm số lớp) và từ Thiết lập lần đầu. Bấm vào một lớp thì mở màn Chi tiết lớp (nếu màn này đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới), mang theo lớp đã chọn. Tạo lớp xong thì đưa tới Chi tiết lớp: Giáo viên của lớp vừa tạo. Quay lại danh sách từ chi tiết lớp thì giữ nguyên ô tìm, bộ lọc, lựa chọn hiện/ẩn lớp đã lưu trữ và vị trí đang xem; lớp vừa thay đổi được làm nổi.

Quy tắc nghiệp vụ:
- Không giới hạn sĩ số.
- Chọn môn đã ngừng sử dụng khi tạo lớp: "Môn này đã ngừng sử dụng. Chọn môn khác hoặc dùng lại môn này ở mục Môn học."
- Thiếu môn hoặc tên: "Vui lòng chọn môn." / "Vui lòng nhập tên lớp."
- Đổi bộ lọc thì quay về đầu danh sách; quay lại từ màn chi tiết vẫn giữ nguyên bộ lọc.

Tình huống đặc biệt: đang tải; chưa có lớp nào ("Chưa có lớp. Tạo lớp đầu tiên"; nếu chưa có môn nào thì gợi ý tạo môn trước); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; lớp đã lưu trữ hiển thị mờ nhưng vẫn mở được.

Dữ liệu mẫu: Toán 9A · Ca tối · Toán · Đang hoạt động; Anh 6C · Khóa hè · Tiếng Anh · Đang chuẩn bị (chưa có giáo viên); Văn 8B · — · Ngữ văn · Đã lưu trữ.
```

---

### AD-10 — Khung chi tiết lớp

```text
Màn hình: Chi tiết lớp học, phần chung của mọi nhóm nội dung
Mẫu bám theo (mockup đã chốt): màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân

Thông tin cần có:
- Đường dẫn quay về danh sách lớp.
- Tên lớp, trạng thái lớp (Đang chuẩn bị / Đang hoạt động / Đã lưu trữ), môn học, nhãn.
- Năm nhóm nội dung để chuyển qua lại: Tổng quan; Học sinh (kèm số lượng); Giáo viên (kèm số lượng); Lịch; Buổi học. Nhóm đang xem phải được ghi nhớ để tải lại màn hình vẫn đúng chỗ.
- Thông điệp theo trạng thái: lớp Đang chuẩn bị = "Lớp chưa có giáo viên phụ trách nên chưa vận hành. Hãy phân công giáo viên."; lớp Đã lưu trữ = "Lớp đã lưu trữ, lịch sử vẫn xem được."

Hành động: lưu trữ lớp (chỉ khi lớp chưa lưu trữ); chuyển nhóm nội dung; đi tới hồ sơ học sinh, hồ sơ giáo viên, nhật ký audit của lớp này.

Liên kết: đến từ Danh sách lớp học, Tổng quan, Nhật ký audit (khi đối tượng là lớp) và từ thông báo chặn ở Chi tiết tài khoản hoặc Chi tiết giáo viên (khi đó mở thẳng nhóm Giáo viên của lớp). Từ đây: tên học sinh dẫn tới Chi tiết học sinh; tên giáo viên dẫn tới Chi tiết giáo viên; mở một buổi để điểm danh thì tới màn điểm danh của buổi (màn dùng chung với giáo viên); xem nhật ký thì tới Nhật ký audit đã lọc sẵn theo lớp này. Quay về danh sách lớp thì giữ ô tìm, bộ lọc và vị trí đang xem trước đó. Mọi chuyển màn đều mang theo lớp đang xem. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Trạng thái lớp tự động theo giáo viên: có giáo viên đầu tiên thì thành Đang hoạt động; giáo viên cuối cùng bị kết thúc phân công thì về Đang chuẩn bị; Đã lưu trữ do Admin đặt tay và không tự khôi phục. Vì vậy không có hành động "kích hoạt lớp".
- Lớp đã lưu trữ không nhận thêm hoạt động mới nên các hành động ghi bị vô hiệu hóa.

Tình huống đặc biệt: đang tải; không tìm thấy lớp ("Không tìm thấy lớp học"); một nhóm nội dung lỗi nhưng phần còn lại vẫn dùng được; lớp ở cả ba trạng thái.

Dữ liệu mẫu: Toán 9A · Ca tối · Toán · Đang hoạt động · 18 học sinh · 1 giáo viên.
```

---

### AD-10a — Chi tiết lớp: Tổng quan

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Tổng quan
Mẫu bám theo (mockup đã chốt): màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân; nội dung tab là các Card thông tin

Thông tin cần có: tên lớp, nhãn, mô tả, môn học, trạng thái lớp (Đang chuẩn bị / Đang hoạt động / Đã lưu trữ); số liệu nhanh: số học sinh, số giáo viên, số buổi sắp tới; lịch sử thay đổi gần đây của lớp (ai làm gì, khi nào).

Hành động:
- Sửa tên, nhãn, mô tả (không đổi được môn; cần ghi chú điều này). Có trạng thái đang lưu.
- Lưu trữ lớp: bước xác nhận nêu rõ "Lớp sẽ không còn hoạt động. Lịch sử điểm danh, bài làm và kết quả được giữ nguyên và vẫn xem được. Không khôi phục được qua giao diện." Không cần nhập lý do. Trước đó nhắc Admin kiểm tra học sinh còn trong lớp và lịch còn hiệu lực, vì hệ thống không tự kết thúc chúng.

Liên kết: là một phần của màn Chi tiết lớp, đến từ Danh sách lớp học (mặc định mở phần này) hoặc từ chuyển qua lại các phần khác của cùng lớp. Chuyển sang Chi tiết lớp: Học sinh, Giáo viên, Lịch, Buổi học thì luôn ở lại đúng lớp đang xem. Xem lịch sử thay đổi đầy đủ thì tới Nhật ký audit đã lọc theo lớp này. Sau khi lưu trữ lớp thì ở lại lớp đó (hiện trạng thái Đã lưu trữ), quay về Danh sách lớp học thì giữ bộ lọc và vị trí. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Không có hành động chuyển trạng thái nào khác ngoài lưu trữ; trạng thái Đang chuẩn bị / Đang hoạt động tự thay đổi theo việc phân công giáo viên.
- Tên lớp không được để trống: "Vui lòng nhập tên lớp."

Tình huống đặc biệt: đang tải; lỗi tải có thử lại; chưa có lịch sử thay đổi; lớp đã lưu trữ (toàn bộ thông tin chỉ đọc, kèm thông báo); đang lưu; lưu thất bại giữ nguyên nội dung đã nhập.

Dữ liệu mẫu: Toán 9A · nhãn "Ca tối" · mô tả "Luyện thi vào lớp 10, học thứ Ba và thứ Năm" · Toán · Đang hoạt động · 18 học sinh · 1 giáo viên · 8 buổi sắp tới.
```

---

### AD-10b — Chi tiết lớp: Học sinh

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Học sinh
Mẫu bám theo (mockup đã chốt): tab Học sinh trong màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân

Thông tin cần có: tên lớp; với mỗi học sinh đang trong lớp: họ tên, ngày vào lớp, ngày hiệu lực đến (trống = không giới hạn); ghi chú "Sẽ rời lớp ngày …" nếu ngày đến ở tương lai; phần những em đã rời lớp.

Hành động:
- Tìm học sinh trong lớp; mở hồ sơ học sinh.
- Thêm học sinh: tìm và chọn (loại các em đã ở trong lớp), thêm liên tiếp nhiều em và thấy các em vừa thêm. Ngày vào lớp là thời điểm thêm.
- Kết thúc tư cách thành viên (rút khỏi lớp / chuyển lớp): bắt buộc nhập lý do. Nói rõ dữ liệu cũ (điểm danh, bài nộp, kết quả) vẫn thuộc lớp này, không chuyển sang lớp mới. Gợi ý chuyển lớp: kết thúc ở đây rồi thêm vào lớp đích.
- Sửa thời hạn thành viên (hồi tố): sửa ngày vào và/hoặc ngày ra, bắt buộc nhập lý do, thấy giá trị cũ và mới trước khi xác nhận, chỉ áp dụng cho em đang trong lớp. Cảnh báo mạnh: "Sửa ngày vào/ra ảnh hưởng việc em có được tính vào điểm danh các buổi trước hay không. Thay đổi được ghi vào nhật ký kèm lý do."

Liên kết: là một phần của màn Chi tiết lớp, đến từ chuyển qua lại các phần của cùng lớp, hoặc từ Chi tiết học sinh (bấm tên lớp). Bấm tên học sinh thì mở Chi tiết học sinh, mang theo học sinh đã chọn; quay lại thì về đúng lớp và đúng vị trí trong danh sách học sinh của lớp. Thêm học sinh chưa có hồ sơ thì dẫn tới Tạo / sửa hồ sơ học sinh rồi quay lại lớp này để xếp vào lớp. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Em đã là thành viên: "Học sinh đã là thành viên đang hoạt động của lớp này."
- Ngày ra phải sau ngày vào: "Ngày kết thúc phải sau ngày bắt đầu."
- Thiếu lý do: "Vui lòng nhập lý do."
- Thành viên không còn hiệu lực khi đang sửa: báo "Học sinh này không còn trong lớp", làm mới danh sách.

Tình huống đặc biệt: đang tải; lớp chưa có học sinh ("Lớp chưa có học sinh" kèm gợi ý thêm); lỗi tải; đang lưu; lớp đã lưu trữ (không cho thêm, sửa).

Dữ liệu mẫu: Nguyễn Minh Anh · vào 05/09/2026 · không giới hạn; Lê Gia Hân · vào 12/10/2026; lý do mẫu: "Chuyển sang lớp Toán 9B theo nguyện vọng phụ huynh."
```

---

### AD-10c — Chi tiết lớp: Giáo viên

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Giáo viên
Mẫu bám theo (mockup đã chốt): tab Học sinh trong màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân (bảng giáo viên thay cho bảng học sinh)

Thông tin cần có: tên lớp và trạng thái hiện tại; với mỗi giáo viên đang phụ trách: họ tên, phụ trách từ ngày nào. Một lớp có thể có nhiều giáo viên cùng phụ trách, không có "giáo viên chính".

Hành động:
- Mở hồ sơ giáo viên.
- Phân công giáo viên: chọn từ các giáo viên đang hoạt động; loại những người đã phụ trách lớp; giáo viên đã ngừng hoạt động hiển thị mờ kèm chú thích và không chọn được. Phân công thành công mà lớp đang Đang chuẩn bị thì thông báo "Lớp đã chuyển sang Đang hoạt động".
- Kết thúc phân công: bước xác nhận (không cần lý do) nêu "Giáo viên không còn thấy và quản lý lớp này; lịch sử được giữ nguyên." Nếu là giáo viên cuối cùng thì thêm: "Lớp sẽ trở về Đang chuẩn bị." Nhắc Admin kiểm tra các lịch học đang áp dụng vì chúng không tự hủy.

Liên kết: là một phần của màn Chi tiết lớp, đến từ chuyển qua lại các phần của cùng lớp, từ Chi tiết giáo viên (bấm một lớp để kết thúc phân công), từ thông báo chặn ở Chi tiết tài khoản, hoặc từ việc tạo lớp xong. Bấm tên giáo viên thì mở Chi tiết giáo viên, mang theo giáo viên đã chọn. Cần giáo viên mới chưa có hồ sơ thì dẫn tới Tạo / sửa hồ sơ giáo viên rồi quay lại lớp này. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Giáo viên ngừng hoạt động: "Giáo viên này đã ngừng hoạt động nên không phân công được."
- Đã phụ trách: "Giáo viên đã phụ trách lớp này."
- Hai thông báo trên phải hiện được kể cả khi danh sách chọn đã lọc đúng (có thể hai quản trị viên thao tác cùng lúc).
- Kết thúc phân công người không còn hiệu lực: báo và làm mới.

Tình huống đặc biệt: đang tải; lớp chưa có giáo viên ("Chưa có giáo viên phụ trách, lớp chưa vận hành" kèm hành động phân công); lỗi tải; thông báo lớp đổi trạng thái; lớp đã lưu trữ (không phân công thêm).

Dữ liệu mẫu: Nguyễn Thị Hạnh · phụ trách từ 05/09/2026; lỗi mẫu: "Cô Trần Thu Hà đã nghỉ dạy nên không thể phân công."
```

---

### AD-10d — Chi tiết lớp: Lịch

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Lịch
Mẫu bám theo (mockup đã chốt): màn Lịch dạy (Giáo viên) cho phần xem lịch, và Dialog cho tạo/sửa/hủy lịch

Thông tin cần có: các lịch của lớp; mỗi lịch gồm: các thứ trong tuần (Thứ Hai … Chủ nhật), giờ bắt đầu–kết thúc và thời lượng (ví dụ 17:30–19:00, 90 phút), thời gian áp dụng (từ ngày … đến ngày … hoặc không giới hạn), trạng thái (Đang áp dụng / Đã hủy / Đã kết thúc). Lịch không lặp chỉ có đúng một buổi vào ngày bắt đầu.

Hành động:
- Tạo lịch: ngày bắt đầu (bắt buộc), ngày kết thúc, giờ bắt đầu (bắt buộc), thời lượng phút (bắt buộc), có lặp hằng tuần hay không, chọn các thứ (bắt buộc khi lặp). Tạo xong hệ thống sinh ngay các buổi cho 4 tuần tới; phải cho thấy số buổi vừa tạo và cảnh báo trùng lịch giáo viên, ví dụ "Cô Hạnh trùng lịch với lớp Văn 8B (Thứ Ba 17:30)". Cảnh báo chỉ để biết, không chặn.
- Sửa lịch: đổi giờ, thời lượng, các thứ, ngày kết thúc; bắt buộc nhập lý do. Nhắc: chỉ ảnh hưởng các buổi chưa sinh; buổi đã sinh giữ nguyên.
- Hủy lịch: bắt buộc nhập lý do; nhắc buổi đã sinh không bị xóa, cần hủy riêng từng buổi nếu muốn; người liên quan sẽ được thông báo.

Liên kết: là một phần của màn Chi tiết lớp, đến từ chuyển qua lại các phần của cùng lớp hoặc từ Thiết lập lần đầu. Sau khi tạo lịch xong, có lối xem các buổi vừa sinh ra ở Chi tiết lớp: Buổi học, vẫn trong cùng lớp. Cảnh báo trùng lịch giáo viên có lối mở lớp đang trùng. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Thiếu thông tin: "Vui lòng chọn ngày bắt đầu." / "Ngày kết thúc phải sau hoặc bằng ngày bắt đầu." / "Thời lượng phải từ 1 phút." / "Vui lòng chọn ít nhất một ngày trong tuần." / "Vui lòng nhập lý do."
- Không kiểm tra trùng phòng học.

Tình huống đặc biệt: đang tải; lớp chưa có lịch ("Chưa có lịch học" kèm hành động tạo lịch); lỗi tải; lịch đã hủy hiển thị mờ, không sửa được; đang lưu; kết quả tạo lịch có cảnh báo trùng; không có quyền.

Dữ liệu mẫu: Thứ Ba, Thứ Năm · 17:30 · 90 phút · từ 05/09/2026; lý do sửa: "Dời sang 18:00 vì học sinh tan trường muộn."
```

---

### AD-10e — Chi tiết lớp: Buổi học

```text
Màn hình: Chi tiết lớp học, nhóm nội dung Buổi học
Mẫu bám theo (mockup đã chốt): màn Lịch dạy và màn Điểm danh (Giáo viên) cho danh sách buổi; Dialog cho thêm/sửa/hủy buổi

Thông tin cần có: khoảng ngày đang xem (mặc định tháng hiện tại); mỗi buổi: ngày, giờ (17:30–19:00), trạng thái (Sắp diễn ra / Đã hoàn tất / Đã hủy), dấu "Cần rà soát điểm danh" khi buổi đã hủy nhưng đã có điểm danh. Chi tiết một buổi có thêm nội dung bài học, ghi chú, và lý do hủy nếu có.

Hành động:
- Đổi khoảng ngày xem.
- Thêm buổi lẻ / buổi bù: giờ bắt đầu và giờ kết thúc (bắt buộc, kết thúc sau bắt đầu), nội dung, ghi chú.
- Sửa buổi: giờ, nội dung, ghi chú. Lý do bắt buộc nếu buổi đã hoàn tất, tùy chọn ở trạng thái khác.
- Hủy buổi: bắt buộc nhập lý do. Nói rõ điểm danh đã có không bị xóa mà được đánh dấu cần rà soát, và học sinh, phụ huynh sẽ được thông báo.
- Hoàn tất buổi: xác nhận đơn giản.
- Mở buổi để điểm danh và xem chi tiết (màn hình này dùng chung với giáo viên; Admin vào được mọi lớp).

Liên kết: là một phần của màn Chi tiết lớp, đến từ chuyển qua lại các phần của cùng lớp, từ Tổng quan (bấm buổi hôm nay) hoặc từ Lịch tổng hợp (bấm một buổi, mang theo lớp và buổi đã chọn). Mở một buổi để điểm danh thì tới màn điểm danh của buổi đó (màn dùng chung với giáo viên), quay lại thì về đúng lớp, đúng khoảng ngày đang xem. Tạo lịch thì sang Chi tiết lớp: Lịch của cùng lớp. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Buổi đã hủy thì không điểm danh được: "Buổi này đã hủy nên không điểm danh được."
- Lỗi mẫu: "Giờ kết thúc phải sau giờ bắt đầu." / "Vui lòng nhập lý do sửa buổi đã hoàn tất." / "Vui lòng nhập lý do hủy."
- Giờ nhập được hiểu theo giờ Việt Nam.

Tình huống đặc biệt: đang tải; không có buổi nào trong khoảng ngày ("Chưa có buổi nào trong khoảng này" kèm gợi ý thêm buổi hoặc tạo lịch); lỗi tải; buổi đã hủy hiển thị mờ; buổi cần rà soát; đang lưu; ngày lọc sai định dạng.

Dữ liệu mẫu: Thứ Ba 21/10/2026 · 17:30–19:00 · Đã hoàn tất; Thứ Năm 23/10/2026 · Đã hủy, lý do "Cô Hạnh ốm" · Cần rà soát điểm danh.
```

---

### AD-10-G — Ghép: Chi tiết lớp học

```text
Màn hình: Ghép: Chi tiết lớp học

Việc cần làm: Gộp khung chi tiết lớp và năm phần nội dung của lớp thành MỘT chức năng quản lý lớp duy nhất, mà Admin cảm nhận như đang ở trong một lớp chứ không phải đi qua nhiều màn riêng.

Các phần cần gộp (các màn đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có): Chi tiết lớp học (phần chung), Chi tiết lớp: Tổng quan, Chi tiết lớp: Học sinh, Chi tiết lớp: Giáo viên, Chi tiết lớp: Lịch, Chi tiết lớp: Buổi học. Cả sáu là một chức năng, cùng xem một đối tượng chung là MỘT lớp.

Dùng chung: tên lớp, nhãn, môn học, trạng thái lớp và thông điệp theo trạng thái; số lượng học sinh và giáo viên; hành động lưu trữ lớp; thông báo thành công / lỗi; trạng thái đang lưu; lối quay về danh sách lớp.

Được giữ lại khi chuyển qua lại giữa các phần: phần đang xem (tải lại vẫn đúng phần đó), dữ liệu đang nhập chưa lưu ở từng phần (trừ khi người dùng chủ động bỏ), ô tìm trong danh sách học sinh, khoảng ngày đang xem ở buổi học, và vị trí đang xem. Có nhập dở mà sắp rời thì hỏi lại. Danh sách lớp phía trước vẫn giữ bộ lọc khi quay về.

Điều kiện hiện / ẩn: lớp Đang chuẩn bị thì nhấn mạnh việc phân công giáo viên; lớp Đã lưu trữ thì mọi phần chỉ xem, các hành động ghi bị vô hiệu hóa kèm giải thích; trạng thái lớp tự đổi sau khi phân công hoặc kết thúc phân công thì mọi phần cập nhật theo ngay; một phần lỗi tải thì các phần còn lại vẫn dùng được.

Liên kết: mở từ các danh sách và màn tổng quan đã nêu ở từng phần; chuyển giữa các phần không được rời khỏi đối tượng đang xem. Quay về danh sách thì giữ ô tìm, bộ lọc và vị trí đang xem.
```

---

### AD-11 — Lịch tổng hợp

```text
Màn hình: Lịch tổng hợp toàn trung tâm
Mẫu bám theo (mockup đã chốt): màn Lịch dạy (Giáo viên): lưới tuần 7 cột, thêm bộ lọc theo lớp, giáo viên, phòng

Thông tin cần có: khoảng thời gian đang xem, xem được theo ngày, tuần hoặc tháng; mỗi buổi học: giờ, tên lớp, giáo viên, trạng thái (Sắp diễn ra / Đã hoàn tất / Đã hủy), dấu cần rà soát điểm danh; dấu hiệu khi cùng một giáo viên có hai buổi trùng giờ; ý nghĩa của các trạng thái.

Hành động:
- Chuyển ngày/tuần/tháng, quay về hôm nay.
- Lọc theo lớp, giáo viên, môn; ẩn buổi đã hủy.
- Bấm một buổi để mở buổi đó (quản lý buổi học của lớp tương ứng).
- Thêm buổi: chọn lớp rồi chuyển sang màn quản lý buổi học của lớp.

Liên kết: đến từ điều hướng quản trị và từ Tổng quan (buổi hôm nay). Bấm một buổi thì mở Chi tiết lớp: Buổi học của lớp tương ứng, mang theo lớp và buổi đã chọn (hoặc mở buổi để điểm danh, màn dùng chung với giáo viên). Thêm buổi: chọn lớp rồi chuyển sang Chi tiết lớp: Buổi học của lớp đó. Quay lại lịch thì giữ nguyên ngày/tuần/tháng đang xem và các bộ lọc. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Cảnh báo trùng giờ giáo viên chỉ để thông tin, không chặn việc nào.
- Không kiểm tra trùng phòng học (hệ thống chưa quản lý địa điểm).

Tình huống đặc biệt: đang tải; tuần không có buổi ("Không có buổi học trong tuần này"); dữ liệu của một lớp tải lỗi nhưng các lớp còn lại vẫn hiện; buổi đã hủy; lớp chưa có lịch; không có quyền.

Dữ liệu mẫu: Thứ Ba: 17:30–19:00 Toán 9A (cô Hạnh); 17:30–19:00 Văn 8B (cô Hạnh) — trùng giáo viên; 19:15–20:45 Anh 6C (thầy Nam).
```

---

### AD-04 — Danh sách học sinh

```text
Màn hình: Danh sách học sinh
Mẫu bám theo (mockup đã chốt): bảng ở tab Học sinh của màn Chi tiết lớp (Giáo viên): hàng tiêu đề chữ xám 12px, dòng ngăn bằng đường mảnh, Avatar + tên đậm, Badge chấm cho trạng thái, mũi tên cuối dòng, đặt trong Card; đầu trang như màn Lớp học (tiêu đề + dòng đếm + nút chính bên phải, tab pill và chip lọc bên dưới)

Thông tin cần có: tổng số học sinh; với mỗi học sinh: họ tên, email, số điện thoại, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), tình trạng tài khoản (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa), số lớp đang học; số học sinh chưa có tài khoản (ví dụ "Chưa có tài khoản: 23").

Hành động:
- Tìm theo tên; lọc theo trạng thái hồ sơ và tình trạng tài khoản; xóa bộ lọc.
- Mở chi tiết học sinh.
- Thêm học sinh mới.
- Với từng học sinh: xem, sửa, mời tạo tài khoản (chỉ khi chưa có tài khoản).

Liên kết: đến từ điều hướng quản trị và từ Tổng quan (bấm số học sinh). Bấm vào một học sinh thì mở Chi tiết học sinh, mang theo học sinh đã chọn. Thêm học sinh mới thì mở Tạo / sửa hồ sơ học sinh; mời tạo tài khoản thì mở Mời tạo tài khoản cho đúng hồ sơ đó. Quay lại danh sách từ chi tiết hoặc sau khi tạo / sửa xong thì giữ nguyên ô tìm, bộ lọc, trang và vị trí đang xem; học sinh vừa thay đổi được làm nổi. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Hồ sơ nghi trùng (cùng tên và số điện thoại) do Admin tự kiểm tra, hệ thống không tự gộp.
- Đổi bộ lọc thì về đầu danh sách; quay lại từ chi tiết vẫn giữ bộ lọc; sau khi tạo hoặc sửa xong, học sinh vừa thay đổi được làm nổi.

Tình huống đặc biệt: đang tải; chưa có học sinh ("Chưa có học sinh. Thêm học sinh đầu tiên"); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; học sinh Ngừng hoạt động hiển thị mờ nhưng vẫn mở được.

Dữ liệu mẫu: Nguyễn Minh Anh · minhanh.parent@gmail.com · 0987 654 321 · Đang hoạt động · Chưa mời · 2 lớp; Lê Gia Hân · — · — · Đang hoạt động · Đang hoạt động · 1 lớp.
```

---

### AD-05 — Chi tiết học sinh

```text
Màn hình: Chi tiết học sinh
Mẫu bám theo (mockup đã chốt): màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân

Thông tin cần có:
- Họ tên, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động).
- Thông tin chung: email, số điện thoại, ghi chú.
- Tài khoản: chưa mời, đã mời, hoặc đã có (tên đăng nhập, trạng thái).
- Các lớp đang học: tên lớp, môn, ngày vào lớp.
- Phụ huynh liên kết: tên, quan hệ (Bố, Mẹ…), trạng thái; riêng phần liên kết đã kết thúc.
- Tiến độ học tập: điểm trung bình, số bài đã chấm, điểm trung bình theo môn.

Hành động: sửa hồ sơ; ngừng hoạt động hoặc khôi phục hồ sơ (xác nhận, không cần lý do); mời tạo tài khoản (khi chưa có); xem tài khoản; liên kết thêm phụ huynh; kết thúc liên kết phụ huynh; mở lớp hoặc hồ sơ phụ huynh; xem nhật ký thay đổi của học sinh.

Liên kết: đến từ Danh sách học sinh, từ danh sách học sinh của một lớp (Chi tiết lớp: Học sinh) và từ Chi tiết phụ huynh (bấm tên con). Từ đây: sửa thì mở Tạo / sửa hồ sơ học sinh; mời thì mở Mời tạo tài khoản; xem tài khoản thì mở Chi tiết tài khoản; liên kết hoặc kết thúc liên kết phụ huynh thì dùng Liên kết phụ huynh – học sinh; bấm tên lớp thì mở Chi tiết lớp; bấm tên phụ huynh thì mở Chi tiết phụ huynh; xem nhật ký thì mở Nhật ký audit đã lọc theo học sinh này. Mọi chuyển màn mang theo học sinh đang xem. Quay về danh sách thì giữ bộ lọc và vị trí. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Ngừng hoạt động hồ sơ không xóa việc học sinh đang ở trong lớp hay liên kết với phụ huynh; lịch sử vẫn giữ.
- Một học sinh có thể có nhiều phụ huynh.

Tình huống đặc biệt: mỗi phần tải riêng, lỗi riêng; không tìm thấy học sinh; hồ sơ Ngừng hoạt động ("Học sinh ngừng hoạt động. Lịch sử vẫn được giữ."); chưa có lớp, chưa có phụ huynh, chưa có tài khoản (mỗi chỗ trống kèm lời mời hành động).

Dữ liệu mẫu: Nguyễn Minh Anh · minhanh.parent@gmail.com · Lớp: Toán 9A (từ 05/09/2026), Anh 9B · Phụ huynh: Nguyễn Văn Hùng (Bố, đang liên kết) · Điểm trung bình 8,4 · 12 bài đã chấm · Ghi chú "Học thêm buổi tối thứ Bảy".
```

---

### AD-05a — Tạo / sửa hồ sơ học sinh

```text
Màn hình: Tạo / sửa hồ sơ học sinh
Mẫu bám theo (mockup đã chốt): trình soạn trong tab Bài kiểm tra (Giáo viên) và hộp thoại Giao bài: Input, Select, Checkbox, Switch của design system, nhóm trường theo thẻ, nút Lưu ở góc

Thông tin cần có: họ tên (bắt buộc), email, số điện thoại, ghi chú (nhiều dòng). Khi sửa có thêm trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động). Chú thích cạnh email: "Cần email nếu muốn mời tạo tài khoản."

Hành động: lưu; hủy (hỏi xác nhận nếu đã nhập dở); khi đang lưu không bấm lưu lần nữa được. Hệ thống cảnh báo nhẹ nếu trùng họ tên hoặc số điện thoại với học sinh đã có, nhưng không chặn việc lưu. Lưu xong đưa tới chi tiết học sinh vừa tạo, kèm gợi ý "Mời tạo tài khoản" và "Thêm vào lớp".

Liên kết: đến từ Danh sách học sinh (thêm mới), Chi tiết học sinh (sửa) hoặc Thiết lập lần đầu. Lưu xong thì mở Chi tiết học sinh của học sinh vừa tạo hoặc sửa, kèm gợi ý Mời tạo tài khoản và Thêm vào lớp. Hủy thì quay về nơi đã đến, và nếu đến từ Danh sách học sinh thì giữ nguyên bộ lọc và vị trí. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

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

---

### AD-05b — Mời tạo tài khoản

```text
Màn hình: Mời tạo tài khoản cho một hồ sơ (Admin). Dùng chung cho học sinh, phụ huynh và giáo viên.
Mẫu bám theo (mockup đã chốt): hộp thoại Giao bài (Giáo viên): Dialog của design system, nút chính bên phải, nút Hủy bên trái

Thông tin cần có: tên người được mời (chỉ đọc); email nhận lời mời lấy từ hồ sơ (chỉ đọc); tên đăng nhập (bắt buộc, không được trùng với bất kỳ ai, gợi ý điền sẵn bằng email); lời giải thích: "Hệ thống gửi thư chứa liên kết đặt mật khẩu. Liên kết hết hạn sau 60 phút."

Hành động: gửi lời mời (xác nhận một bước, không cần lý do); nếu hồ sơ chưa có email thì không gửi được và có lối đi bổ sung email vào hồ sơ. Sau khi thành công: thông báo "Đã gửi lời mời tới a***@gmail.com" và hiện bước kế tiếp là gán vai trò cho tài khoản, vì mời xong người đó chưa có vai trò nào.

Liên kết: đến từ danh sách và chi tiết của học sinh, phụ huynh, giáo viên (khi hồ sơ chưa có tài khoản), mang theo hồ sơ đang xem để không phải chọn lại. Thành công thì đóng lại, hồ sơ chuyển sang Đã mời, và có lối Gán vai trò ngay sang Chi tiết tài khoản của tài khoản vừa tạo. Hồ sơ đã có tài khoản hoặc đã mời thì dẫn sang Gửi lại lời mời. Thiếu email thì có lối quay về sửa hồ sơ. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Hồ sơ đã có tài khoản: "Hồ sơ này đã có tài khoản. Nếu người đó chưa đặt được mật khẩu, xem hướng dẫn gửi lại."
- Tên đăng nhập trùng hoặc bỏ trống: "Tên đăng nhập này đã được dùng." / "Vui lòng nhập tên đăng nhập."
- Tài khoản được tạo trước, thư gửi sau; thư gửi lỗi vẫn không hủy tài khoản, nên có thể hiện "đã mời" mà người nhận không có thư, khi đó hướng dẫn xem cách gửi lại lời mời.

Tình huống đặc biệt: trạng thái nhập; đang gửi; thành công; tên đăng nhập trùng; hồ sơ đã có tài khoản; thiếu email; gửi thư lỗi.

Dữ liệu mẫu: Người được mời "Phạm Thanh Tùng", email tung.pt2012@gmail.com, tên đăng nhập "tung.pt2012@gmail.com"; lỗi mẫu "Tên đăng nhập này đã được dùng."
```

---

### AD-05c — Gửi lại lời mời

```text
Màn hình: Gửi lại lời mời / giúp đặt mật khẩu
Mẫu bám theo (mockup đã chốt): hộp thoại Giao bài (Giáo viên): Dialog của design system, nút chính bên phải, nút Hủy bên trái

Thông tin cần có: tên người được mời, tên đăng nhập, email nhận thư, thời điểm đã mời và thời điểm liên kết hết hạn (liên kết có hiệu lực 60 phút); tình trạng "chưa đăng nhập lần nào".

Hành động:
- Gửi lại lời mời.
- Sao chép liên kết đặt mật khẩu để Admin gửi hộ qua Zalo khi thư không tới.
- Hướng dẫn hiện có: bảo người được mời mở chức năng "Quên mật khẩu" ở màn hình đăng nhập, nhập đúng tên đăng nhập hoặc email để nhận liên kết mới (cũng hết hạn sau 60 phút).

Liên kết: đến từ Chi tiết tài khoản (tài khoản vừa mời, chưa đăng nhập lần nào), từ phần tài khoản ở Chi tiết học sinh, phụ huynh, giáo viên khi trạng thái là Đã mời, hoặc từ thông báo đã mời rồi ở Mời tạo tài khoản. Mang theo người được mời và tài khoản của họ. Xong thì quay về nơi đã đến. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Không mời lần hai được vì hồ sơ đã có tài khoản.
- Người mở liên kết đã hết hạn sẽ thấy lỗi; câu hướng dẫn cho họ là "Liên hệ quản trị viên để được gửi lại."
- Vẽ cả trạng thái hướng dẫn hiện có lẫn trạng thái đầy đủ khi gửi lại trực tiếp được.

Tình huống đặc biệt: trạng thái hướng dẫn (hiện tại); đã gửi lại thành công; đã sao chép liên kết; bị giới hạn số lần gửi trong một khoảng thời gian; gửi lỗi, có thử lại.

Dữ liệu mẫu: "Trần Văn Nam (giáo viên) được mời lúc 08:10, liên kết hết hạn 09:10, chưa đăng nhập." Email nhận: nam.tv@piclass.vn.
```

---

### AD-05-G — Ghép: Hồ sơ học sinh

```text
Màn hình: Ghép: Hồ sơ học sinh

Việc cần làm: Gộp xem hồ sơ, tạo / sửa hồ sơ, mời tạo tài khoản và gửi lại lời mời thành MỘT chức năng quản lý học sinh liền mạch quanh một học sinh.

Các phần cần gộp (các màn đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có): Chi tiết học sinh, Tạo / sửa hồ sơ học sinh, Mời tạo tài khoản, Gửi lại lời mời. Cả bốn là một chức năng, cùng xem một đối tượng chung là MỘT học sinh; mời và gửi lại lời mời cũng là việc của giáo viên và phụ huynh nhưng ở đây áp dụng cho học sinh.

Dùng chung: tên học sinh, trạng thái hồ sơ, tình trạng tài khoản (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa), thông báo thành công / lỗi, trạng thái đang lưu hoặc đang gửi.

Được giữ lại khi chuyển qua lại giữa các phần: học sinh đang xem; dữ liệu đang nhập chưa lưu khi người dùng mở rồi đóng phần mời; vị trí đang xem trong hồ sơ; bộ lọc và vị trí của Danh sách học sinh để quay về. Sau khi lưu hoặc mời xong, hồ sơ hiển thị ngay thông tin mới mà không phải tải lại.

Điều kiện hiện / ẩn: Mời tạo tài khoản chỉ hiện khi chưa có tài khoản; Gửi lại lời mời chỉ hiện khi tài khoản ở trạng thái Đã mời mà chưa đăng nhập; hồ sơ Ngừng hoạt động thì có thông báo nhưng vẫn xem được; thiếu email thì chặn mời và có lối bổ sung email ngay trong sửa hồ sơ.

Liên kết: mở từ các danh sách và màn tổng quan đã nêu ở từng phần; chuyển giữa các phần không được rời khỏi đối tượng đang xem. Quay về danh sách thì giữ ô tìm, bộ lọc và vị trí đang xem.
```

---

### AD-06 — Danh sách phụ huynh

```text
Màn hình: Danh sách phụ huynh
Mẫu bám theo (mockup đã chốt): bảng ở tab Học sinh của màn Chi tiết lớp (Giáo viên): hàng tiêu đề chữ xám 12px, dòng ngăn bằng đường mảnh, Avatar + tên đậm, Badge chấm cho trạng thái, mũi tên cuối dòng, đặt trong Card; đầu trang như màn Lớp học (tiêu đề + dòng đếm + nút chính bên phải, tab pill và chip lọc bên dưới)

Thông tin cần có: tổng số phụ huynh; mỗi phụ huynh: họ tên, email, số điện thoại, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), tình trạng tài khoản (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa), số con đang liên kết; dấu cảnh báo cho phụ huynh chưa liên kết con nào.

Hành động:
- Tìm theo tên; lọc theo trạng thái hồ sơ, tình trạng tài khoản, có con liên kết hay chưa; xóa bộ lọc.
- Mở chi tiết phụ huynh; thêm phụ huynh mới.
- Với từng phụ huynh: xem, sửa, mời tạo tài khoản, liên kết với học sinh.

Liên kết: đến từ điều hướng quản trị. Bấm vào một phụ huynh thì mở Chi tiết phụ huynh, mang theo phụ huynh đã chọn. Thêm phụ huynh mới thì mở Tạo / sửa hồ sơ phụ huynh; mời tạo tài khoản thì mở Mời tạo tài khoản; liên kết với học sinh thì mở Liên kết phụ huynh – học sinh với phụ huynh đã chọn. Quay lại danh sách thì giữ nguyên ô tìm, bộ lọc, trang và vị trí đang xem; phụ huynh vừa thay đổi được làm nổi. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Không bắt buộc học sinh nào cũng có phụ huynh, nhưng phụ huynh không có liên kết thì cổng phụ huynh trống.
- Đổi bộ lọc thì về đầu danh sách; quay lại từ chi tiết giữ bộ lọc.

Tình huống đặc biệt: đang tải; chưa có phụ huynh ("Chưa có phụ huynh. Thêm phụ huynh đầu tiên"); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; hồ sơ Ngừng hoạt động hiển thị mờ; phụ huynh chưa liên kết con nào.

Dữ liệu mẫu: Nguyễn Văn Hùng · hung.nv@gmail.com · 0987 654 321 · Đang hoạt động · Đang hoạt động · 2 con; Lê Thị Mai Phương · mai.phuong@gmail.com · Đang hoạt động · Chưa mời · chưa liên kết con nào.
```

---

### AD-06a — Chi tiết phụ huynh

```text
Màn hình: Chi tiết phụ huynh
Mẫu bám theo (mockup đã chốt): màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân

Thông tin cần có: họ tên, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động); email, số điện thoại; tài khoản (chưa mời / đã mời / đã có kèm tên đăng nhập và trạng thái); các con đang liên kết: tên con, quan hệ (Bố, Mẹ, Ông bà… do gia đình cung cấp, chữ tự do), từ ngày nào; các liên kết đã kết thúc (kèm ngày kết thúc và lý do nếu có).

Hành động: sửa hồ sơ; ngừng hoạt động hoặc khôi phục hồ sơ; mời tạo tài khoản; liên kết thêm học sinh; kết thúc liên kết với một em; mở hồ sơ của con.

Liên kết: đến từ Danh sách phụ huynh và từ Chi tiết học sinh (bấm tên phụ huynh). Từ đây: sửa thì mở Tạo / sửa hồ sơ phụ huynh; mời thì mở Mời tạo tài khoản; liên kết hoặc kết thúc liên kết thì dùng Liên kết phụ huynh – học sinh; bấm tên con thì mở Chi tiết học sinh. Mọi chuyển màn mang theo phụ huynh đang xem. Quay về danh sách thì giữ bộ lọc và vị trí. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Đổi trạng thái hồ sơ không ảnh hưởng các liên kết đang có.
- Kết thúc liên kết có hiệu lực ngay, kể cả với dữ liệu lịch sử của con.

Tình huống đặc biệt: đang tải; lỗi tải; không tìm thấy phụ huynh; hồ sơ Ngừng hoạt động; chưa có con nào liên kết (kèm lời mời liên kết).

Dữ liệu mẫu: Nguyễn Văn Hùng · Bố của Nguyễn Minh Anh (liên kết từ 05/09/2026, đang hiệu lực); Mẹ của Nguyễn Gia Bảo (đã kết thúc 01/10/2026, lý do "Con chuyển trung tâm").
```

---

### AD-06b — Tạo / sửa hồ sơ phụ huynh

```text
Màn hình: Tạo / sửa hồ sơ phụ huynh
Mẫu bám theo (mockup đã chốt): trình soạn trong tab Bài kiểm tra (Giáo viên) và hộp thoại Giao bài: Input, Select, Checkbox, Switch của design system, nhóm trường theo thẻ, nút Lưu ở góc

Thông tin cần có: họ tên (bắt buộc), email, số điện thoại. Khi sửa có thêm trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động). Không có ghi chú. Chú thích cạnh email: "Cần email nếu muốn mời tạo tài khoản."

Hành động: lưu; hủy (hỏi xác nhận nếu đã nhập dở); lưu xong đưa tới chi tiết phụ huynh kèm gợi ý "Liên kết với học sinh". Cảnh báo nhẹ, không chặn, nếu trùng họ tên hoặc số điện thoại với phụ huynh đã có.

Liên kết: đến từ Danh sách phụ huynh (thêm mới), Chi tiết phụ huynh (sửa) hoặc từ phần phụ huynh ở Chi tiết học sinh. Lưu xong thì mở Chi tiết phụ huynh vừa lưu, kèm gợi ý Liên kết với học sinh. Hủy thì quay về nơi đã đến, giữ bộ lọc và vị trí nếu đến từ danh sách. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Thiếu họ tên: "Vui lòng nhập họ tên."
- Email sai định dạng: "Email không hợp lệ."
- Lỗi hiện ngay cạnh trường bị lỗi; khi sửa chỉ lưu trường đã đổi; thành công chỉ cần thông báo ngắn "Đã lưu".

Tình huống đặc biệt: chưa nhập gì khi tạo; đang lưu (không bấm lưu hai lần); lỗi theo trường; mất kết nối nhưng giữ nội dung đã nhập; cảnh báo nghi trùng hồ sơ.

Dữ liệu mẫu: Họ tên "Lê Thị Mai Phương"; email mai.phuong@gmail.com; số điện thoại 0908 223 344.
```

---

### AD-06c — Liên kết phụ huynh – học sinh

```text
Màn hình: Liên kết phụ huynh – học sinh (Admin). Dùng được từ hồ sơ phụ huynh và từ hồ sơ học sinh.
Mẫu bám theo (mockup đã chốt): hộp thoại Giao bài (Giáo viên): Dialog của design system, nút chính bên phải, nút Hủy bên trái

Thông tin cần có:
- Khi tạo liên kết: người còn lại cần chọn (tìm theo tên, nếu đang ở hồ sơ phụ huynh thì chọn học sinh, và ngược lại); loại quan hệ (tùy chọn, chữ tự do, gợi ý nhanh: Bố, Mẹ, Ông, Bà, Người giám hộ).
- Khi kết thúc liên kết: tên hai bên, loại quan hệ, ô lý do.

Hành động:
- Liên kết (không cần lý do).
- Kết thúc liên kết: bắt buộc nhập lý do. Cảnh báo: "Phụ huynh sẽ không xem được dữ liệu của con ngay lập tức, kể cả lịch sử. Muốn khôi phục phải tạo liên kết mới."
- Cho thấy liên kết đã kết thúc ở dạng mờ, kèm lý do.

Liên kết: dùng được từ Chi tiết phụ huynh (liên kết thêm học sinh) và từ Chi tiết học sinh (liên kết thêm phụ huynh); bên đã đang xem được mang theo sẵn, người dùng chỉ chọn bên còn lại. Xong thì quay về đúng màn chi tiết đã đến, danh sách liên kết được làm mới và liên kết vừa thay đổi được làm nổi. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Một học sinh có nhiều phụ huynh, một phụ huynh có nhiều con.
- Đã liên kết rồi: "Hai người này đã được liên kết."
- Liên kết đã kết thúc trước đó: "Liên kết này đã kết thúc rồi." (làm mới danh sách).
- Thiếu lý do: "Vui lòng nhập lý do."

Tình huống đặc biệt: trạng thái nhập; đang lưu; trùng liên kết; liên kết đã kết thúc; lỗi tải kết quả tìm; không có quyền.

Dữ liệu mẫu: Nguyễn Văn Hùng ↔ Nguyễn Minh Anh, quan hệ "Bố"; lý do kết thúc mẫu: "Gia đình đề nghị gỡ vì con đã nghỉ học."
```

---

### AD-06-G — Ghép: Hồ sơ phụ huynh

```text
Màn hình: Ghép: Hồ sơ phụ huynh

Việc cần làm: Gộp xem hồ sơ, tạo / sửa hồ sơ, liên kết với học sinh và mời tài khoản thành MỘT chức năng quản lý phụ huynh liền mạch.

Các phần cần gộp (các màn đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có): Chi tiết phụ huynh, Tạo / sửa hồ sơ phụ huynh, Liên kết phụ huynh – học sinh, Mời tạo tài khoản. Cả bốn là một chức năng, cùng xem một đối tượng chung là MỘT phụ huynh.

Dùng chung: tên phụ huynh, trạng thái hồ sơ, tình trạng tài khoản, danh sách con đang liên kết và đã kết thúc, thông báo thành công / lỗi, trạng thái đang lưu.

Được giữ lại khi chuyển qua lại giữa các phần: phụ huynh đang xem; dữ liệu đang nhập chưa lưu; học sinh vừa chọn khi liên kết; vị trí đang xem; bộ lọc và vị trí của Danh sách phụ huynh để quay về. Liên kết vừa tạo hoặc kết thúc hiện ngay trong hồ sơ.

Điều kiện hiện / ẩn: Mời tạo tài khoản chỉ hiện khi chưa có tài khoản; kết thúc liên kết bắt buộc lý do và chỉ với liên kết đang hiệu lực; phụ huynh chưa liên kết con nào thì nhắc liên kết; hồ sơ Ngừng hoạt động thì có thông báo nhưng vẫn xem được.

Liên kết: mở từ các danh sách và màn tổng quan đã nêu ở từng phần; chuyển giữa các phần không được rời khỏi đối tượng đang xem. Quay về danh sách thì giữ ô tìm, bộ lọc và vị trí đang xem.
```

---

### AD-07 — Danh sách giáo viên

```text
Màn hình: Danh sách giáo viên
Mẫu bám theo (mockup đã chốt): bảng ở tab Học sinh của màn Chi tiết lớp (Giáo viên): hàng tiêu đề chữ xám 12px, dòng ngăn bằng đường mảnh, Avatar + tên đậm, Badge chấm cho trạng thái, mũi tên cuối dòng, đặt trong Card; đầu trang như màn Lớp học (tiêu đề + dòng đếm + nút chính bên phải, tab pill và chip lọc bên dưới)

Thông tin cần có: tổng số giáo viên; mỗi giáo viên: họ tên, chuyên môn, email, số điện thoại, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), tình trạng tài khoản (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa), số lớp đang dạy.

Hành động:
- Tìm theo tên; lọc theo trạng thái hồ sơ, tình trạng tài khoản, có lớp hay chưa có lớp; xóa bộ lọc.
- Mở chi tiết giáo viên; thêm giáo viên mới.
- Với từng giáo viên: xem, sửa, mời tạo tài khoản.

Liên kết: đến từ điều hướng quản trị và từ Tổng quan (bấm số giáo viên). Bấm vào một giáo viên thì mở Chi tiết giáo viên, mang theo giáo viên đã chọn. Thêm giáo viên mới thì mở Tạo / sửa hồ sơ giáo viên; mời tạo tài khoản thì mở Mời tạo tài khoản. Quay lại danh sách thì giữ nguyên ô tìm, bộ lọc, trang và vị trí đang xem; giáo viên vừa thay đổi được làm nổi. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Giáo viên Ngừng hoạt động không được phân công vào lớp mới.
- Đổi bộ lọc thì về đầu danh sách; quay lại từ chi tiết giữ bộ lọc; sau khi tạo hoặc sửa xong, giáo viên vừa thay đổi được làm nổi.

Tình huống đặc biệt: đang tải; chưa có giáo viên ("Chưa có giáo viên. Thêm giáo viên đầu tiên"); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; giáo viên Ngừng hoạt động hiển thị mờ nhưng vẫn mở được.

Dữ liệu mẫu: Nguyễn Thị Hạnh · Toán · hanh.nt@piclass.vn · Đang hoạt động · Đang hoạt động · 3 lớp; Trần Văn Nam · Tiếng Anh · — · Ngừng hoạt động · Chưa mời · 0 lớp.
```

---

### AD-07a — Chi tiết giáo viên

```text
Màn hình: Chi tiết giáo viên
Mẫu bám theo (mockup đã chốt): màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân

Thông tin cần có: họ tên, trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động), chuyên môn; email, số điện thoại; tài khoản (chưa mời / đã mời / đã có kèm tên đăng nhập và trạng thái); các lớp đang dạy: tên lớp, môn, phụ trách từ ngày nào.

Hành động: sửa hồ sơ; ngừng hoạt động hồ sơ (xác nhận, không cần lý do) hoặc khôi phục; mời tạo tài khoản; mở một lớp để kết thúc phân công.

Liên kết: đến từ Danh sách giáo viên và từ Chi tiết lớp: Giáo viên (bấm tên giáo viên). Từ đây: sửa thì mở Tạo / sửa hồ sơ giáo viên; mời thì mở Mời tạo tài khoản; bấm một lớp đang dạy thì mở Chi tiết lớp: Giáo viên của lớp đó để kết thúc phân công, mang theo lớp và giáo viên. Quay về danh sách thì giữ bộ lọc và vị trí. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Giáo viên còn phân công lớp đang hoạt động thì không ngừng hoạt động được: "Cô Hạnh còn dạy Toán 9A, Toán 8B. Hãy kết thúc phân công trước." Thông báo liệt kê các lớp, mỗi lớp là lối đi tới phần Giáo viên của lớp đó.
- Ngừng hoạt động hồ sơ giáo viên khác với vô hiệu hóa tài khoản đăng nhập; cả hai đều bị chặn khi còn phân công. Cần có chú thích giúp Admin không nhầm hai việc này.

Tình huống đặc biệt: mỗi phần tải riêng, lỗi riêng; không tìm thấy giáo viên; hồ sơ Ngừng hoạt động; chưa dạy lớp nào (kèm gợi ý phân công); bị chặn khi ngừng hoạt động.

Dữ liệu mẫu: Nguyễn Thị Hạnh · Toán THCS · Lớp: Toán 9A, Toán 8B (phụ trách từ 05/09/2026) · Tài khoản đang hoạt động.
```

---

### AD-07b — Tạo / sửa hồ sơ giáo viên

```text
Màn hình: Tạo / sửa hồ sơ giáo viên
Mẫu bám theo (mockup đã chốt): trình soạn trong tab Bài kiểm tra (Giáo viên) và hộp thoại Giao bài: Input, Select, Checkbox, Switch của design system, nhóm trường theo thẻ, nút Lưu ở góc

Thông tin cần có: họ tên (bắt buộc), email, số điện thoại, chuyên môn (chữ tự do). Khi sửa có thêm trạng thái hồ sơ (Đang hoạt động / Ngừng hoạt động). Chú thích cạnh email: "Cần email nếu muốn mời tạo tài khoản."

Hành động: lưu; hủy (hỏi xác nhận nếu đã nhập dở); lưu xong đưa tới chi tiết giáo viên kèm gợi ý "Mời tạo tài khoản" rồi "Phân công vào lớp".

Liên kết: đến từ Danh sách giáo viên (thêm mới), Chi tiết giáo viên (sửa) hoặc Thiết lập lần đầu. Lưu xong thì mở Chi tiết giáo viên vừa lưu, kèm gợi ý Mời tạo tài khoản rồi Phân công vào lớp (dẫn tới Chi tiết lớp: Giáo viên). Khi bị chặn ngừng hoạt động, tên mỗi lớp dẫn tới Chi tiết lớp: Giáo viên của lớp đó. Hủy thì quay về nơi đã đến, giữ bộ lọc và vị trí nếu đến từ danh sách. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Thiếu họ tên: "Vui lòng nhập họ tên."
- Email sai định dạng: "Email không hợp lệ."
- Chuyển sang Ngừng hoạt động có thể thất bại khi giáo viên còn dạy lớp: "Giáo viên còn phụ trách lớp đang hoạt động. Hãy kết thúc phân công trước." kèm tên các lớp.
- Lỗi hiện ngay cạnh trường bị lỗi; khi sửa chỉ lưu trường đã đổi; thành công chỉ cần thông báo ngắn "Đã lưu".

Tình huống đặc biệt: chưa nhập gì khi tạo; đang lưu (không bấm lưu hai lần); lỗi theo trường; bị chặn khi ngừng hoạt động; mất kết nối nhưng giữ nội dung đã nhập.

Dữ liệu mẫu: Họ tên "Vũ Hoàng Long"; chuyên môn "Ngữ văn THCS"; email long.vh@piclass.vn; số điện thoại 0933 445 566.
```

---

### AD-07-G — Ghép: Hồ sơ giáo viên

```text
Màn hình: Ghép: Hồ sơ giáo viên

Việc cần làm: Gộp xem hồ sơ, tạo / sửa hồ sơ và mời tạo tài khoản thành MỘT chức năng quản lý giáo viên liền mạch, nối tiếp tới việc phân công vào lớp.

Các phần cần gộp (các màn đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có): Chi tiết giáo viên, Tạo / sửa hồ sơ giáo viên, Mời tạo tài khoản. Cả ba là một chức năng, cùng xem một đối tượng chung là MỘT giáo viên.

Dùng chung: tên giáo viên, chuyên môn, trạng thái hồ sơ, tình trạng tài khoản, các lớp đang dạy, thông báo thành công / lỗi, trạng thái đang lưu.

Được giữ lại khi chuyển qua lại giữa các phần: giáo viên đang xem; dữ liệu đang nhập chưa lưu; vị trí đang xem; bộ lọc và vị trí của Danh sách giáo viên để quay về. Sau khi lưu hoặc mời xong, hồ sơ hiển thị ngay thông tin mới.

Điều kiện hiện / ẩn: Mời tạo tài khoản chỉ hiện khi chưa có tài khoản; ngừng hoạt động bị chặn khi còn phụ trách lớp và thông báo liệt kê các lớp, mỗi lớp dẫn tới nơi kết thúc phân công; hồ sơ Ngừng hoạt động thì có thông báo nhưng vẫn xem được; giáo viên chưa dạy lớp nào thì gợi ý phân công.

Liên kết: mở từ các danh sách và màn tổng quan đã nêu ở từng phần; chuyển giữa các phần không được rời khỏi đối tượng đang xem. Quay về danh sách thì giữ ô tìm, bộ lọc và vị trí đang xem.
```

---

### AD-08 — Môn học

```text
Màn hình: Môn học
Mẫu bám theo (mockup đã chốt): bảng ở tab Học sinh của màn Chi tiết lớp (Giáo viên): hàng tiêu đề chữ xám 12px, dòng ngăn bằng đường mảnh, Avatar + tên đậm, Badge chấm cho trạng thái, mũi tên cuối dòng, đặt trong Card; đầu trang như màn Lớp học (tiêu đề + dòng đếm + nút chính bên phải, tab pill và chip lọc bên dưới); thêm/sửa môn trong Dialog

Thông tin cần có: các môn; mỗi môn: tên, mô tả, trạng thái (Đang dùng / Ngừng sử dụng), số lớp đang dùng môn.

Hành động:
- Lọc theo trạng thái.
- Thêm môn: tên (bắt buộc), mô tả.
- Sửa tên, mô tả.
- Ngừng sử dụng hoặc dùng lại một môn. Khi ngừng, giải thích: "Không thể tạo lớp mới cho môn này; các lớp đã có vẫn chạy và sửa bình thường."
- Không có hành động xóa môn.

Liên kết: đến từ điều hướng quản trị và từ Thiết lập lần đầu; khi chưa có môn nào thì cũng là nơi được gợi ý từ Danh sách lớp học. Màn này tự gồm cả xem và thêm / sửa môn nên không có màn chi tiết riêng. Không có điểm đến sâu hơn; quay lại màn trước đó thì giữ bộ lọc và vị trí mà người dùng đang xem ở đó.

Quy tắc nghiệp vụ:
- Thiếu tên: "Vui lòng nhập tên môn."
- Không kiểm tra trùng tên môn.
- Môn ngừng sử dụng không xuất hiện khi chọn môn lúc tạo lớp.

Tình huống đặc biệt: đang tải; chưa có môn nào ("Chưa có môn học. Thêm môn đầu tiên", kèm giải thích chưa có môn thì chưa tạo được lớp); lỗi tải có thử lại; môn ngừng sử dụng hiển thị mờ; đang lưu.

Dữ liệu mẫu: Toán · "Chương trình THCS" · Đang dùng · 6 lớp; Tin học · — · Ngừng sử dụng · 0 lớp.
```

---

### AD-12 — Khóa học

```text
Màn hình: Khóa học, gồm danh sách và chi tiết một khóa
Mẫu bám theo (mockup đã chốt): bảng ở tab Học sinh của màn Chi tiết lớp (Giáo viên): hàng tiêu đề chữ xám 12px, dòng ngăn bằng đường mảnh, Avatar + tên đậm, Badge chấm cho trạng thái, mũi tên cuối dòng, đặt trong Card; đầu trang như màn Lớp học (tiêu đề + dòng đếm + nút chính bên phải, tab pill và chip lọc bên dưới); chi tiết khóa học theo màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân

Thông tin cần có:
- Danh sách: tên khóa, mô tả, chủ sở hữu (tên giáo viên, hoặc "Dùng chung" nếu không thuộc ai), trạng thái (Đang mở / Đã lưu trữ).
- Chi tiết một khóa: tên, mô tả, môn học (có thể trống), chủ sở hữu, trạng thái; học sinh đang ghi danh; các bài đánh giá thuộc khóa.

Hành động:
- Lọc theo trạng thái, theo chủ sở hữu; mở một khóa.
- Tạo khóa: tên (bắt buộc), mô tả, môn (tùy chọn), chủ sở hữu (chọn giáo viên hoặc để trống = dùng chung; chỉ Admin có lựa chọn này). Khóa mới ở trạng thái Đang mở.
- Sửa tên, mô tả, môn.
- Lưu trữ khóa (không cần lý do): "Không ghi danh thêm được nữa; vẫn xem lịch sử và kết thúc ghi danh được." Không có hành động mở lại khóa qua giao diện.
- Đi tới việc ghi danh học sinh.

Liên kết: đến từ điều hướng quản trị. Bấm vào một khóa thì mở chi tiết khóa ngay trong cùng chức năng, mang theo khóa đã chọn; việc ghi danh dẫn tới Ghi danh vào khóa học của đúng khóa đó. Bấm tên học sinh trong khóa thì mở Chi tiết học sinh. Quay lại danh sách khóa từ chi tiết hoặc sau khi tạo / sửa / lưu trữ thì giữ nguyên bộ lọc theo trạng thái, theo chủ sở hữu và vị trí đang xem; khóa vừa thay đổi được làm nổi. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Thiếu tên: "Vui lòng nhập tên khóa học."
- Khóa đã lưu trữ thì khóa việc ghi danh thêm.

Tình huống đặc biệt: đang tải; chưa có khóa ("Chưa có khóa học" kèm hành động tạo khóa); không có kết quả khớp bộ lọc; lỗi tải có thử lại; khóa đã lưu trữ kèm thông báo; không tìm thấy khóa; không có quyền.

Dữ liệu mẫu: "Luyện thi vào 10 — Toán" · chủ sở hữu cô Hạnh · Đang mở · 12 học sinh; "Ngữ pháp Anh cơ bản" · Dùng chung · Đã lưu trữ.
```

---

### AD-12a — Ghi danh vào khóa học

```text
Màn hình: Ghi danh vào khóa học (Admin). Nằm trong phần chi tiết của một khóa học.
Mẫu bám theo (mockup đã chốt): hộp thoại Giao bài (Giáo viên): Dialog của design system, nút chính bên phải, nút Hủy bên trái

Thông tin cần có: học sinh trong khóa: họ tên, từ ngày, đến ngày, trạng thái (Đang hiệu lực / Đã kết thúc); mặc định chỉ hiện các em đang hiệu lực, có cách xem cả em đã kết thúc. Việc ghi danh có hai cách: theo từng học sinh (tìm chọn một em) hoặc theo cả lớp (chọn một lớp), không chọn cả hai cùng lúc.

Hành động:
- Ghi danh theo học sinh hoặc theo lớp. Theo lớp: ghi danh tất cả thành viên đang trong lớp tại thời điểm bấm, bỏ qua em đã ghi danh; học sinh vào lớp sau đó không tự được ghi danh, cần nhắc rõ điều này.
- Hiện kết quả: "Đã ghi danh 14 học sinh"; nếu là 0 thì "Tất cả đã được ghi danh từ trước" (không phải lỗi).
- Kết thúc ghi danh một em: bắt buộc nhập lý do; em rời khóa nhưng lịch sử giữ.
- Mở hồ sơ học sinh.

Liên kết: là một phần trong chi tiết của một khóa học, đến từ phần học sinh ghi danh của khóa; khóa đang xem được mang theo, không phải chọn lại. Bấm tên học sinh thì mở Chi tiết học sinh; quay lại thì về đúng khóa. Muốn ghi danh theo lớp thì chọn lớp từ danh sách lớp hiện có. Sau khi ghi danh hoặc kết thúc ghi danh, danh sách học sinh trong khóa được làm mới và giữ vị trí đang xem. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Khóa đã lưu trữ: "Khóa học đã lưu trữ nên không ghi danh thêm được." (vẫn kết thúc ghi danh được).
- Chưa chọn học sinh hay lớp, hoặc chọn cả hai: "Hãy chọn một học sinh hoặc một lớp."
- Thiếu lý do: "Vui lòng nhập lý do."

Tình huống đặc biệt: đang tải; khóa chưa có học sinh ("Chưa có học sinh trong khóa" kèm hành động ghi danh); lỗi tải; khóa đã lưu trữ; kết quả không có em nào mới.

Dữ liệu mẫu: ghi danh theo lớp Toán 9A, kết quả "Đã ghi danh 16 học sinh (2 em đã có từ trước)"; lý do kết thúc: "Phụ huynh xin nghỉ khóa luyện thi."
```

---

### AD-12-G — Ghép: Khóa học và ghi danh

```text
Màn hình: Ghép: Khóa học và ghi danh

Việc cần làm: Gộp danh sách khóa, chi tiết khóa, tạo / sửa khóa và ghi danh học sinh thành MỘT chức năng quản lý khóa học liền mạch quanh một khóa.

Các phần cần gộp (các màn đã vẽ trong dự án; nếu chưa vẽ đủ thì chỉ ghép những phần đã có): Khóa học (danh sách và chi tiết), Ghi danh vào khóa học. Hai màn là một chức năng, cùng xem một đối tượng chung là MỘT khóa học.

Dùng chung: tên khóa, chủ sở hữu, trạng thái khóa, số học sinh ghi danh, thông báo thành công / lỗi, trạng thái đang lưu.

Được giữ lại khi chuyển qua lại giữa các phần: khóa đang xem; dữ liệu đang nhập chưa lưu; lựa chọn học sinh hoặc lớp đang chọn để ghi danh; lựa chọn xem cả em đã kết thúc; bộ lọc và vị trí của danh sách khóa để quay về.

Điều kiện hiện / ẩn: khóa Đã lưu trữ thì khóa việc ghi danh thêm nhưng vẫn cho kết thúc ghi danh và xem lịch sử; chỉ Admin có lựa chọn khóa dùng chung; kết quả ghi danh theo lớp cho biết số em mới và số em đã có từ trước.

Liên kết: mở từ các danh sách và màn tổng quan đã nêu ở từng phần; chuyển giữa các phần không được rời khỏi đối tượng đang xem. Quay về danh sách thì giữ ô tìm, bộ lọc và vị trí đang xem.
```

---

### AD-02 — Danh sách tài khoản

```text
Màn hình: Danh sách tài khoản đăng nhập
Mẫu bám theo (mockup đã chốt): bảng ở tab Học sinh của màn Chi tiết lớp (Giáo viên): hàng tiêu đề chữ xám 12px, dòng ngăn bằng đường mảnh, Avatar + tên đậm, Badge chấm cho trạng thái, mũi tên cuối dòng, đặt trong Card; đầu trang như màn Lớp học (tiêu đề + dòng đếm + nút chính bên phải, tab pill và chip lọc bên dưới)

Thông tin cần có: tổng số tài khoản; mỗi tài khoản: họ tên (nếu chưa gắn hồ sơ thì hiện "(chưa có hồ sơ)"), tên đăng nhập, các vai trò đang giữ (Quản trị viên / Giáo viên / Học sinh / Phụ huynh, có thể nhiều), trạng thái (Đang hoạt động / Đã vô hiệu hóa).

Hành động:
- Tìm theo tên; tìm theo tên đăng nhập hoặc email; lọc theo vai trò; lọc theo trạng thái; xóa bộ lọc. Có chú thích tìm tên cần gõ có dấu.
- Chọn số tài khoản mỗi lần xem (có giới hạn hợp lý, tối đa 100), chuyển trang, biết đang ở trang mấy trên tổng bao nhiêu và tổng số kết quả.
- Mở chi tiết tài khoản.

Liên kết: đến từ điều hướng quản trị; khi chưa có tài khoản nào có lối tới Danh sách học sinh để tạo hồ sơ rồi mời. Bấm vào một tài khoản thì mở Chi tiết tài khoản, mang theo tài khoản đã chọn. Quay lại danh sách thì giữ nguyên ô tìm, bộ lọc vai trò và trạng thái, số tài khoản mỗi lần xem, trang đang ở và vị trí đang xem. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Lọc theo vai trò chỉ tính vai trò còn hiệu lực.
- Đổi bộ lọc thì về trang đầu; quay lại từ chi tiết giữ bộ lọc.

Tình huống đặc biệt: đang tải; chưa có tài khoản ("Chưa có tài khoản nào. Hãy tạo hồ sơ rồi mời" kèm lối tới danh sách học sinh); không có kết quả khớp bộ lọc; lỗi tải có thử lại; không có quyền; tài khoản đã vô hiệu hóa hiển thị mờ.

Dữ liệu mẫu: Nguyễn Thị Hạnh · hanh.nt@piclass.vn · Giáo viên, Quản trị viên · Đang hoạt động; Trần Quốc Bảo · 0912345678 · Phụ huynh · Đã vô hiệu hóa.
```

---

### AD-03 — Chi tiết tài khoản

```text
Màn hình: Chi tiết tài khoản
Mẫu bám theo (mockup đã chốt): màn Chi tiết lớp (Giáo viên): nút quay lại dạng ghost, thẻ tiêu đề nền nhạt có avatar và nút hành động, hàng StatTile, Card chứa tab gạch chân

Thông tin cần có: họ tên, tên đăng nhập, trạng thái (Đang hoạt động / Đã vô hiệu hóa); các vai trò đang giữ; lịch sử vai trò (vai trò, từ ngày, đến ngày; vai trò đã kết thúc hiển thị mờ); hồ sơ gắn với tài khoản (tên, lối tới hồ sơ); lối xem nhật ký thay đổi của tài khoản.

Hành động:
- Gán vai trò: chọn trong bốn vai trò (Quản trị viên / Giáo viên / Học sinh / Phụ huynh), ẩn vai trò đã giữ, có bước xác nhận. Nhắc: vai trò Giáo viên, Học sinh, Phụ huynh chỉ có tác dụng khi người này có hồ sơ tương ứng.
- Kết thúc vai trò: xác nhận; có hiệu lực ngay, người dùng mất quyền ở lần thao tác tiếp theo.
- Vô hiệu hóa tài khoản: "Người này sẽ không đăng nhập được. Hồ sơ và lịch sử vẫn giữ nguyên, có thể kích hoạt lại. Các liên kết và phân công đã kết thúc không tự khôi phục." Không cần lý do. Kích hoạt lại: xác nhận đơn giản. Nếu tự vô hiệu hóa chính mình thì cảnh báo thêm "Bạn sẽ bị đăng xuất."

Liên kết: đến từ Danh sách tài khoản (bấm một dòng) và, sau khi mời tạo tài khoản thành công, từ Mời tạo tài khoản (để gán vai trò ngay). Từ đây: về Danh sách tài khoản (giữ bộ lọc và vị trí); sang hồ sơ gắn với tài khoản (Chi tiết học sinh, Chi tiết phụ huynh hoặc Chi tiết giáo viên tùy loại); xem nhật ký thì mở Nhật ký audit đã lọc theo tài khoản này; tài khoản đã mời mà chưa đăng nhập thì có lối sang Gửi lại lời mời; khi bị chặn vì giáo viên còn dạy lớp, tên mỗi lớp dẫn tới Chi tiết lớp: Giáo viên của lớp đó. Mọi chuyển màn mang theo tài khoản đang xem. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Quản trị viên cuối cùng: "Đây là quản trị viên cuối cùng. Hãy gán quyền quản trị cho người khác trước." (khi kết thúc vai trò thì đổi câu cho đúng việc).
- Giáo viên còn dạy lớp đang hoạt động: "Cô Hạnh còn dạy Toán 9A, Văn 8B. Hãy kết thúc phân công trước." Liệt kê các lớp, mỗi lớp dẫn tới phần phân công giáo viên của lớp.
- Đã giữ vai trò: "Người này đang giữ vai trò này rồi."

Tình huống đặc biệt: đang tải; lỗi tải; không tìm thấy tài khoản; tài khoản đã vô hiệu hóa (thông báo, hành động chính thành "Kích hoạt lại"); chưa có vai trò nào (lời mời "Gán vai trò đầu tiên"); đang gửi.

Dữ liệu mẫu: Nguyễn Thị Hạnh · hanh.nt@piclass.vn · Đang hoạt động · Giáo viên (từ 05/09/2026), Quản trị viên (từ 12/10/2026).
```

---

### AD-13 — Cấp lại lượt làm bài

```text
Màn hình: Cấp lại lượt làm bài (Admin). Chỉ Admin có chức năng này, giáo viên không có.
Mẫu bám theo (mockup đã chốt): hộp thoại Giao bài (Giáo viên): Dialog của design system, nút chính bên phải, nút Hủy bên trái; tìm bài làm bằng bảng như danh sách

Thông tin cần có: học sinh; tên bài; lượt sẽ bị hủy (số lượt, trạng thái, giờ nộp, điểm nếu đã chấm); các hậu quả: lượt cũ bị hủy, bài nộp của lượt đó thành không hợp lệ, kết quả đã công bố bị thu hồi (học sinh và phụ huynh không còn thấy và không tính vào tiến độ), học sinh nhận một lượt mới; ô lý do.

Hành động: cấp lại (chỉ dùng được khi đã nhập lý do); hủy bỏ. Thành công thì hiện lượt mới và quay về chi tiết bài nộp. Hành động chỉ xuất hiện với bài trắc nghiệm hoặc bài thi, không áp dụng cho bài tập, và không có với lượt đã hủy.

Liên kết: chỉ đến được từ chi tiết bài nộp của học sinh (màn chấm bài, dùng chung với giáo viên) khi người xem là Admin và bài là bài kiểm tra hoặc bài thi; không có lối riêng trong điều hướng. Mang theo học sinh, bài và lượt làm đang xem. Cấp lại thành công thì quay về chi tiết bài nộp với lượt mới; hủy bỏ thì quay về chi tiết bài nộp như cũ. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Thiếu lý do: "Vui lòng nhập lý do cấp lại."
- Bài không phải kiểm tra hoặc thi: "Chỉ cấp lại được cho bài trắc nghiệm hoặc bài thi."
- Lượt đã hủy hoặc đã được cấp lại: "Lượt này đã bị hủy hoặc đã được cấp lại trước đó."
- Lượt mới vẫn theo giờ đóng hiện tại của bài; cần nói điều này để Admin không hiểu nhầm là được thêm thời gian.
- Khi lỗi, giữ nguyên lý do đã gõ.

Tình huống đặc biệt: trạng thái nhập; đang gửi; thành công; các lỗi trên; bài tập (không hiện hành động); lượt đã hủy (không hiện hành động).

Dữ liệu mẫu: Lê Gia Hân · "Kiểm tra 15 phút — Đại số" · Lượt 1, nộp 19:02 · Lý do: "Mất điện đúng giờ làm bài, đã xác nhận với phụ huynh."
```

---

### AD-14 — Nhật ký audit

```text
Màn hình: Nhật ký audit
Mẫu bám theo (mockup đã chốt): bảng ở tab Học sinh của màn Chi tiết lớp (Giáo viên): hàng tiêu đề chữ xám 12px, dòng ngăn bằng đường mảnh, Avatar + tên đậm, Badge chấm cho trạng thái, mũi tên cuối dòng, đặt trong Card; đầu trang như màn Lớp học (tiêu đề + dòng đếm + nút chính bên phải, tab pill và chip lọc bên dưới); thêm bộ lọc theo thời gian, người thao tác, loại đối tượng

Thông tin cần có:
- Bộ lọc: loại đối tượng, mã đối tượng, hành động, khoảng ngày, người thực hiện, lớp hoặc khóa học liên quan.
- Mỗi sự kiện: thời điểm (giờ Việt Nam), người thực hiện (tên), hành động bằng nhãn tiếng Việt (ví dụ Đổi trạng thái tài khoản, Gán / kết thúc vai trò, Đổi trạng thái hồ sơ, Liên kết / kết thúc liên kết phụ huynh – học sinh, Thêm / kết thúc / sửa hồi tố thành viên lớp, Phân công / kết thúc phân công giáo viên, Sửa / hủy lịch, Sửa / hủy buổi học, Tạo / sửa điểm danh, Duyệt đơn xin phép, Ghi danh / kết thúc ghi danh, Phát hành / đóng / mở lại / hủy bài, Nộp bài / cấp lại lượt làm bài, Công bố / thu hồi kết quả, Chấm / sửa điểm), đối tượng bị tác động, lý do (có thể trống).
- Chi tiết một sự kiện, đọc theo thứ tự: ai, làm gì, với cái gì (kèm lớp hoặc khóa liên quan), vì sao, và phần thay đổi so sánh giá trị trước với sau cho từng thuộc tính (thuộc tính đổi giá trị được làm nổi; thuộc tính chỉ có một phía ghi "(mới)" hoặc "(bỏ)"); có cách xem dữ liệu gốc.

Hành động: lọc, xóa lọc; mở và đóng chi tiết một sự kiện; sao chép mã; mở đối tượng liên quan (tài khoản, lớp…) khi xác định được. Sắp xếp mới nhất trước.

Liên kết: đến từ điều hướng quản trị, từ lối Xem nhật ký ở Chi tiết tài khoản, Chi tiết học sinh, Chi tiết lớp (khi đó đã lọc sẵn theo đối tượng đang xem) và từ thông báo gửi cho Admin. Từ một sự kiện, mở đối tượng liên quan khi xác định được: tài khoản thì mở Chi tiết tài khoản, lớp thì mở Chi tiết lớp, học sinh thì mở Chi tiết học sinh. Quay lại nhật ký thì giữ nguyên bộ lọc, sự kiện đang mở và vị trí đang xem. Nếu màn đích đã có trong dự án, hãy nối tới nó; nếu chưa, để một điểm dẫn tới.

Quy tắc nghiệp vụ:
- Nhật ký luôn khớp dữ liệu thật và không bao giờ bị xóa.
- Lọc sai giá trị chỉ cho kết quả rỗng, không báo lỗi.
- Sự kiện tạo mới không có giá trị "trước".

Tình huống đặc biệt: đang tải; chưa có hoạt động nào; không có bản ghi khớp bộ lọc; lỗi tải có thử lại; không có quyền; nhật ký rất lớn (cảnh báo "Đang tải toàn bộ nhật ký, có thể chậm"); sự kiện lẽ ra phải có lý do nhưng thiếu.

Dữ liệu mẫu: 20/10/2026 09:12 · Nguyễn Thị Hạnh (Admin) · Sửa hồi tố thành viên lớp · Toán 9A · Lý do "Phụ huynh báo con đã vào lớp từ 01/10" · Trước: ngày vào 05/10/2026, sau: ngày vào 01/10/2026.
```
