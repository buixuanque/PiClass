# Kế hoạch mockup theo vai trò

File này là nơi theo dõi tiến độ mockup UI. Làm việc trên nhiều máy thì chỉ cần đọc file này, cập nhật ô đánh dấu và ghi chú khi xong mỗi nhóm.

## Quy ước chung

- UI của `piclass-mockup/design-system/` (design system v2, chốt 2026-10-23, xem `design-system/CHANGES.md`) là base đã chốt: chỉ thêm hoặc sửa, không vẽ lại từ đầu. Web responsive (xem `prompts/00-nen.md`). Mockup làm trong Claude Code, không dùng Claude Design.
- Màn mới dùng token (`var(--...)`) và component trong `_ds_bundle.js`, không để màu hoặc cỡ chữ rời; chữ tối thiểu 12px; viền ô nhập dùng `--border-control`. Giữ ngoại lệ nút primary md/lg 4.42:1.
- Mỗi vai trò một file `PiClass <Vai trò>.dc.html`. Admin đã xong và là mẫu cho cách làm: dữ liệu mẫu nằm trong state của component, thao tác sửa dữ liệu thật trong trang, bấm qua lại được.
- Sau mỗi lần sửa file `.dc.html`, chạy lại `python3 tools/make_standalone.py` (trong `piclass-mockup`) để sinh `design-system/standalone/*.html`, rồi commit cả hai.
- Mỗi nhóm xong phải kiểm tra: `node --check` trên phần script, so khóa `{{binding}}` với `renderVals`, chạy kịch bản bấm thử bằng Chrome headless, chụp màn hình ở 500, 900 và 1300px.
- Đặc tả nguồn: `student-parent.md`, `teacher-van-hanh.md`, `teacher-bai-danh-gia.md` (cùng thư mục), khoảng trống API ở `api-gaps.md`.
- Dữ liệu bài nộp và bài làm dùng chung giữa Học sinh, Phụ huynh và Giáo viên. Dùng cùng bộ dữ liệu mẫu (lớp "Toán 9A", cùng học sinh, cùng bài) để ba file khớp nhau.

## Làm việc trên máy khác

```bash
cd PiClass && git checkout main && git pull          # repo docs (file này)
cd piclass-mockup && git checkout main && git pull   # repo mockup, là repo riêng
```

Repo mockup: `buixuanque/piclass-mockup`. Mở bằng double-click file trong `design-system/standalone/`. Khi làm xong một nhóm: commit repo mockup trước, rồi cập nhật ô bên dưới và `docs/progress.md` ở repo docs. Theo quy ước chung, mỗi repo làm trên branch riêng rồi mở PR khi người dùng yêu cầu.

## Trạng thái

| Vai trò | Mockup đã có | Trạng thái |
|---|---|---|
| Admin | Đủ 10 mục menu | Xong (nhóm 1 đến 4) |
| Học sinh | 7 mục (thêm Tiến độ, Khóa học) | Xong nhóm 0 đến 2 |
| Phụ huynh | 7 mục (thêm Tiến độ, Đơn xin phép) và màn Chọn con | Xong nhóm 0 và 2; đã review và sửa 2026-10-23 (`reviews/2026-10-23-phu-huynh-uiux.md`, 15/15 mã đã sửa) |
| Giáo viên | 9 mục (Bài đánh giá thay Bài tập và Bài kiểm tra, thêm Ngân hàng câu hỏi, Đơn xin phép, Khóa học) | Xong nhóm 0, 3, 4 và 5 (nhóm 5 chưa review); đã review và sửa 2026-10-23 (`reviews/2026-10-23-giao-vien-uiux.md`, 18/18 mã đã xử lý) |

## Các nhóm

Làm theo thứ tự. Đánh dấu `[x]` và ghi ngày, máy, commit khi xong.

### Nhóm 0. Responsive cho ba file còn lại (làm trước các nhóm sau)
- [x] `PiClass Giáo viên.dc.html`, `PiClass Học sinh.dc.html`, `PiClass Phụ huynh.dc.html`: bỏ `min-width:1180px`, thu sidebar thành rail ở khoảng 1100px, ẩn sidebar và thêm nút menu ở khoảng 760px, theo mẫu `PiClass Quản trị.dc.html`. Kiểm tra không cuộn ngang ở 500px.
- Ghi chú khi xong: 2026-10-23, repo `piclass-mockup` branch `feat/responsive-3-roles` (`fbba5f1`). Công cụ `tools/make_responsive.py`. Đã kiểm 17 màn ở 500/900/1300px, không cuộn ngang; chưa thử 390px; học sinh và phụ huynh chưa có thanh điều hướng dưới.

### Nhóm 1. Học sinh A: làm bài và kết quả (file `PiClass Học sinh.dc.html`)
- [x] ST-05 Chi tiết bài (điều kiện làm, số lượt, hạn)
- [x] ST-06 Làm bài, đủ 9 loại câu hỏi, đếm giờ, lưu nháp, xác nhận nộp
- [x] ST-06b Đã nộp bài
- [x] ST-07 Chi tiết kết quả
- [x] ST-07b Xem lại bài làm
- [x] Nối từ các mục Bài tập, Bài kiểm tra, Kết quả hiện có sang các màn trên
- Ghi chú khi xong: 2026-10-23, repo `piclass-mockup` branch `feat/responsive-3-roles` (`a8f409b`). Danh sách Bài tập và Bài kiểm tra dùng chung một mô hình bài (loại, khung giờ, số lượt, trạng thái suy ra như `student-parent.md`). Có hộp thoại bắt đầu, đồng hồ đếm ngược, tự lưu, mất mạng, cảnh báo 5 phút và 1 phút, tự nộp khi hết giờ (nút giả lập ở cuối màn làm bài), hộp thoại danh sách câu và xác nhận nộp. Đủ 9 loại câu; câu nộp file hiện ở trạng thái khóa. Kết quả ẩn khối chi tiết theo cờ `show_*`. Mỗi trang có thanh Xem thử trạng thái (tải, rỗng, lỗi, không có quyền hoặc chưa công bố). Dữ liệu bài nằm trong file Học sinh (`ASM`, `ATT0`, `RES0`), nhóm 2 và 5 cần tách ra dùng chung cho Phụ huynh và Giáo viên. Bài nộp mới của học sinh chưa có giáo viên chấm nên không bao giờ có điểm trong bản mẫu.

### Nhóm 2. Học sinh B và Phụ huynh (hai file `Học sinh`, `Phụ huynh`)
- [x] ST-08 Tiến độ học tập
- [x] ST-09 Khóa học của em
- [x] PR-01 Chọn con (nhiều con, mang ngữ cảnh con đang xem)
- [x] PR-07 Tiến độ của con
- [x] PR-08 Đơn xin phép, giải trình vắng (tạo, theo dõi; không có hủy hay sửa đơn vì BE chưa có endpoint, theo `student-parent.md`)
- [x] Phụ huynh xem kết quả của con dùng chung dữ liệu với Học sinh (nhóm 1)
- Ghi chú khi xong: 2026-10-23, repo `piclass-mockup` branch `feat/mockup-group2`. Dữ liệu mẫu chung nằm ở `design-system/pc-data.js` (lớp, bài, lượt làm, kết quả, hai con); Học sinh và Phụ huynh đều nạp file này, Giáo viên (nhóm 3, 5) sẽ nạp tiếp. Tiến độ tính từ kết quả đã công bố (mỗi bài một lần, bài hủy và bài chờ chấm không tính; bài chờ chấm đếm riêng), có lọc 7/30 ngày và môn, bấm môn để lọc danh sách bài; biểu đồ theo ngày là bản mẫu chờ G8. Khóa học học sinh: danh sách và chi tiết, bài thuộc khóa là bản mẫu chờ N-A. Phụ huynh: màn chọn con có trạng thái chưa liên kết và mất quyền xem con; Bài tập & kết quả xem được điểm và nhận xét, không xem bài làm; nhận xét mức private không bao giờ hiện, mức riêng cho phụ huynh chỉ phụ huynh thấy; đơn xin phép có biểu mẫu kiểm lỗi, giả lập lỗi máy chủ và `STUDENT_NOT_ELIGIBLE`. Đơn của phụ huynh nằm trong bộ nhớ trang nên chưa sang Giáo viên (TC-06 ở nhóm 3 dùng dữ liệu mẫu riêng từ `pc-data.js` nếu cần).

### Nhóm 3. Giáo viên A: vận hành lớp (file `PiClass Giáo viên.dc.html`)
- [x] TC-03 Chi tiết lớp
- [x] TC-05 Buổi học và điểm danh (mở rộng từ mục Điểm danh hiện có)
- [x] TC-06 Đơn xin phép (nhận từ phụ huynh nhóm 2)
- [x] TC-15 Khóa học của tôi
- [x] TC-14 Tiến độ học sinh (bổ sung điểm danh và biểu đồ)
- Ghi chú khi xong: 2026-10-23, repo `piclass-mockup` branch `feat/mockup-group3`. Giáo viên: bỏ nút Tạo lớp (chỉ Admin tạo lớp); chi tiết lớp thêm tab Giáo viên, Lịch, Tiến độ, tìm học sinh và nhóm Đã rời lớp, buổi học chia Sắp tới và Đã diễn ra; điểm danh có nội dung buổi học, hoàn tất và hủy buổi (lý do bắt buộc, hủy sau khi có điểm danh thì đánh dấu cần rà soát), sửa bản ghi bắt buộc lý do kèm lịch sử, lô bị từ chối khi có em không còn trong lớp (`STUDENT_NOT_ELIGIBLE`) có nút bỏ các em đó rồi lưu lại; màn Đơn xin phép (lọc theo trạng thái, chấp nhận hoặc từ chối kèm ghi chú, đổi quyết định, gợi ý mở điểm danh, chấp nhận không tự đổi điểm danh); Khóa học (của tôi và dùng chung, tạo, sửa, lưu trữ, ghi danh theo học sinh hoặc theo lớp, kết thúc ghi danh có lý do, khóa dùng chung chỉ xem); Tiến độ có lọc 7 hoặc 30 ngày và môn, theo môn, chuyển em trước và sau. Chưa làm: tab Lịch sử thay đổi của lớp (🔵 chưa chốt). Dữ liệu của file Giáo viên vẫn là bộ riêng (lớp, học sinh sinh ra trong file), chỉ Đỗ Minh Khuê và hai đơn xin phép của em khớp với Phụ huynh. Ngày hiện tại trong file Giáo viên là 14/10 còn Học sinh và Phụ huynh là 15/10 (cần thống nhất ở nhóm 5 khi nối bài nộp và chấm bài qua `pc-data.js`).

### Nhóm 4. Giáo viên B: soạn nội dung
- [x] TC-07 Ngân hàng câu hỏi
- [x] TC-08 Soạn câu hỏi (9 loại)
- [x] TC-10 Soạn và giao bài (nhiều bước)
- [x] TC-09 Danh sách bài đánh giá (bổ sung đối tượng, số đã nộp, chờ chấm)
- Ghi chú khi xong: 2026-10-23, repo `piclass-mockup` branch `feat/mockup-group4`. Menu Giáo viên: "Bài tập" và "Bài kiểm tra" gộp thành "Bài đánh giá" (TC-09, lọc theo loại, trạng thái, lớp, "Bài tôi tạo", phân trang; mỗi dòng có đối tượng, đã nộp, chờ chấm, và thao tác Soạn tiếp, Soạn phiên bản mới, Đóng bài, Nhân bản, Hủy bài kèm lý do; bài của giáo viên khác chỉ có Xem), thêm "Ngân hàng câu hỏi" (TC-07, lọc môn, loại, topic, của tôi, tìm không dấu, lưu trữ có xác nhận, chế độ chọn câu cho bài với nhãn Đã thêm). TC-08 đủ 9 loại với kiểm tra phía giao diện theo `teacher-bai-danh-gia.md` (đúng 1 đáp án đúng, tối thiểu 2 lựa chọn, đáp án chấp nhận, 2 cặp, 2 mục), chế độ xem chỉ đọc cho câu của người khác, câu đã lưu trữ, đổi loại có hộp xác nhận, lưu tạo phiên bản mới kèm dòng "Bài đã giao trước đó vẫn dùng nội dung cũ", đính file mô phỏng, xem trước theo góc nhìn học sinh, loại nộp file có cảnh báo chưa dùng được. TC-10 bốn bước (Thông tin, Câu hỏi, Đối tượng, Xem trước và phát hành), lưu bản nháp ngay khi xong bước 1 và mở lại đúng bước, kiểm lỗi thứ tự giờ, chọn câu từ ngân hàng hoặc soạn câu mới rồi quay lại, mỗi câu hiện "Phiên bản n của câu này", giao theo lớp, học sinh hoặc khóa (không trùng, rút được), bảng kiểm lỗi mềm và chặn phát hành khi thiếu câu hoặc thiếu đối tượng, phiên bản mới của bài đã phát hành. Chưa làm: màn chi tiết bài (nhóm 5, nút Xem hiện thông báo bản mẫu); mở lại bài; đính file đề cho bài (TB-4); "Xem như học sinh" (🔵); tab đối tượng chưa mô phỏng lỗi 403; ô Điểm trong chế độ chọn câu dùng điểm gợi ý, sửa ở bước 2. Dữ liệu bài và câu của file Giáo viên vẫn là bộ riêng (chưa nối `pc-data.js`), việc nối với Học sinh và Phụ huynh để nhóm 5 làm cùng bài nộp.

### Nhóm 5. Giáo viên C: chấm bài
- [x] TC-11 Chi tiết bài
- [x] TC-12 Bài nộp của một bài
- [x] TC-13 Chấm bài
- [~] Kiểm tra vòng khép kín: giao bài, làm bài, chấm, xem kết quả (xong phần dữ liệu khớp, chưa đồng bộ trực tiếp giữa các file, xem ghi chú)
- Ghi chú khi xong: 2026-10-24, repo `piclass-mockup` branch `feat/mockup-group5` (`8d25ef3`), chỉ sửa file `PiClass Giáo viên.dc.html`. Thay màn chấm bài cũ (một bài Writing, một ô điểm) bằng ba màn theo `teacher-bai-danh-gia.md`.
  - **TC-11:** bấm "Xem" ở danh sách bài đánh giá (bản nháp có thêm "Xem chi tiết"). Đầu trang có loại, trạng thái, phiên bản, thanh tóm tắt "Đã nộp x/y · Chờ chấm n · Đã công bố m" và nút theo vòng đời: Sửa và phát hành (nháp), Soạn phiên bản mới, Đóng bài (có xác nhận), Mở lại (chọn nhóm, ghi chú không kéo dài giờ đóng), Hủy bài (bắt buộc lý do); bài của giáo viên khác chỉ xem. Năm tab: Tổng quan, Câu hỏi, Đối tượng (Rút chỉ là thông báo bản mẫu), Học sinh (Đã giao, Đã rút, Đã nộp, Chưa nộp, hộp thoại Gia hạn / điều chỉnh với hạn riêng và số lượt riêng, kiểm lỗi "Số lượt tối thiểu là 1"), Bài nộp. Có các trạng thái bản nháp, đã hủy (lý do), bản nháp mới khi đang chạy, rỗng đối tượng.
  - **TC-12:** tab Bài nộp. Lọc theo trạng thái chấm và trạng thái công bố, nhãn Nộp trễ, điểm và phần trăm, Đạt hoặc Chưa đạt, công bố hoặc rút nhanh trên dòng, "Công bố tất cả đã chấm xong (n)" có xác nhận và hộp kết quả "Đã công bố n kết quả. Bỏ qua m bài vì chưa chấm xong". Có trạng thái rỗng ("Chưa có bài nộp. Hạn nộp: ..."), rỗng sau lọc, dòng bị vô hiệu ("Đã cấp lại lượt").
  - **TC-13:** đủ 9 loại câu (bài làm của học sinh, đáp án đúng, đúng hoặc sai kèm biểu tượng và chữ, câu bỏ trống, câu file chưa xem được vì G6), chấm từng câu (kiểm lỗi "Điểm phải từ 0 đến n"; câu tự chấm sửa được sau khi bấm "Sửa điểm câu này"), điểm tổng thủ công kèm cảnh báo "Điểm tổng đang thay cho điểm từng câu", lý do sửa bắt buộc khi kết quả đã công bố, chấm xong thì tự công bố như backend (BR-SYS-007, Q-A), nhận xét ba trạng thái (Nháp, Đã công bố, Đã rút theo một chiều) và ba quyền xem, công bố hoặc rút kết quả có xác nhận, bài trước/sau và "Lưu và chấm bài kế" ("Đã chấm hết. Công bố cả lớp?"), bài bị vô hiệu chỉ đọc có liên kết sang lượt thay thế, dòng "Cần cấp lại lượt? Nhờ Admin xử lý" (bài tập thì chuyển sang tăng số lượt riêng).
  - **Dữ liệu mẫu:** `SUBM0` sinh bài nộp cho mọi bài đã phát hành, đóng hoặc hủy theo danh sách lớp. Số "6 bài chờ chấm" khớp Unit 3 — Writing; huy hiệu menu, tổng quan và danh sách đều tính từ cùng nguồn. Khớp với Học sinh và Phụ huynh: Đỗ Minh Khuê được 8,5/10 ở "Kiểm tra 15 phút: Căn bậc hai" (đã công bố, kèm 3 nhận xét đã công bố và 1 nháp riêng tư giống màn Phụ huynh) và 7,0/10 ở "Đề luyện số 4"; chưa nộp "Phiếu bài tập: Hệ thức Vi-ét".
  - **Chưa làm:** đồng bộ trực tiếp kết quả công bố sang file Học sinh và Phụ huynh (ba file riêng, cần kho dữ liệu chung như `localStorage`, chưa làm); lịch sử sửa điểm và tab Lịch sử thay đổi (🔵); giao thêm đối tượng; tải file bài làm và file đề (G6, TB-4); mở lại theo đúng nhóm đã chọn chỉ là hộp thoại mô phỏng; trạng thái đang tải và lỗi dùng khối chung nên chưa có `role="alert"`. Chưa review ui-ux-pro-max cho ba màn mới. Dữ liệu ngày của file Giáo viên vẫn lệch một ngày so với Học sinh và Phụ huynh (14/10 so với 15/10).

### Nhóm 6. Màn chung (`common.md`)
- [x] CM-01 Đăng nhập, CM-02 Quên mật khẩu, CM-03 Đặt lại mật khẩu, CM-09 Kích hoạt tài khoản
- [x] CM-04 Chọn và đổi vai trò, CM-05 Hồ sơ cá nhân, CM-06 Trung tâm thông báo
- [x] CM-07 Trang lỗi (7 biến thể), CM-08 Xem và tải tệp, CM-10 Khung điều hướng
- Ghi chú khi xong: 2026-10-24, repo `piclass-mockup` branch `feat/mockup-common`, file mới `design-system/PiClass Màn chung.dc.html` (và bản `standalone/`). Chưa review ui-ux-pro-max.
  - **Cách dùng:** hộp "Đi tới màn" và các thẻ trạng thái ở thanh trên cùng để xem từng màn. Có tài khoản mẫu (điền sẵn): Lê Thị Hương (Quản trị + Giáo viên), Quản trị hệ thống (không có hồ sơ), Nguyễn Thị Lan, Đỗ Minh Khuê, Đỗ Thu Hương, và một tài khoản chưa được cấp vai trò. Mật khẩu tùy ý, nhập "sai" để thử lỗi.
  - **Đăng nhập:** kiểm lỗi từng ô, sai 5 lần thì khóa có đếm ngược 42 giây, lỗi mạng có "Thử lại", banner hết phiên và "đã đặt lại mật khẩu". Sau đăng nhập tự vào trang chủ nếu có một vai trò, ngược lại sang màn chọn vai trò.
  - **Quên và đặt mật khẩu, kích hoạt:** thông điệp trung tính, "Gửi lại" khóa 60 giây, chỉ báo độ dài mật khẩu, liên kết hết hạn, đã dùng, đã kích hoạt, thành công. Kích hoạt dùng cùng form với đặt lại nhưng văn bản khác (theo đề xuất G11).
  - **Khung điều hướng:** menu đúng theo từng vai trò (lấy từ bốn mockup), chuông có huy hiệu (0, số, 99+), menu người dùng (Hồ sơ, Đổi vai trò khi có hơn một vai trò, Đăng xuất), trạng thái đang khởi tạo và mất mạng. Tài khoản Quản trị + Giáo viên dùng phương án "đổi vai trò" (không dựng khung gộp hai nhóm vì Q6 chưa chốt).
  - **Hồ sơ:** sửa email và số điện thoại, họ tên chỉ đọc, đổi mật khẩu có xác nhận "đăng xuất thiết bị khác", rời trang khi chưa lưu thì hỏi, biến thể Admin không có hồ sơ.
  - **Thông báo:** lọc Tất cả, Chưa đọc, Đã đọc, đánh dấu tất cả, bấm dòng thì đọc và chuyển tới màn đích (có thông báo dẫn tới nội dung không còn khả dụng).
  - **Tệp:** các trạng thái đang lấy liên kết, liên kết hết hạn tự tạo lại, tải lỗi có thử lại, 404, ảnh xem trước, ảnh không xem trước được.
  - **Nối với bốn mockup:** chuông và avatar trên thanh trên của Quản trị, Giáo viên, Học sinh, Phụ huynh mở thẳng thông báo và hồ sơ ở file Màn chung, có nút "Quay lại mockup …".
  - **Chưa làm:** điều hướng giữa các mục menu thật (menu chỉ mở stub dẫn về mockup của vai trò), thanh dưới trên điện thoại (dùng ngăn kéo như các mockup khác), điều khoản sử dụng ở màn kích hoạt (chưa có trong requirements), khung gộp Giảng dạy và Quản trị (chờ Q6).

### Sau cùng
- [ ] Đồng bộ lại `prompts/admin.md` với mockup Admin cuối (giữ làm mô tả yêu cầu; không làm prompt cho vai trò khác vì không còn dùng Claude Design)
- [ ] Thử đúng khổ 390px trên điện thoại thật hoặc devtools (Chrome headless không xuống dưới khoảng 500px)
