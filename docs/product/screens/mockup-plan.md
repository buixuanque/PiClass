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
| Phụ huynh | 7 mục (thêm Tiến độ, Đơn xin phép) và màn Chọn con | Xong nhóm 0 và 2 |
| Giáo viên | 7 mục (Tổng quan, Lớp học, Lịch dạy, Điểm danh, Bài tập, Bài kiểm tra, Tiến độ) | Chưa bổ sung |

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
- [ ] TC-03 Chi tiết lớp
- [ ] TC-05 Buổi học và điểm danh (mở rộng từ mục Điểm danh hiện có)
- [ ] TC-06 Đơn xin phép (nhận từ phụ huynh nhóm 2)
- [ ] TC-15 Khóa học của tôi
- [ ] TC-14 Tiến độ học sinh (bổ sung điểm danh và biểu đồ)
- Ghi chú khi xong:

### Nhóm 4. Giáo viên B: soạn nội dung
- [ ] TC-07 Ngân hàng câu hỏi
- [ ] TC-08 Soạn câu hỏi (9 loại)
- [ ] TC-10 Soạn và giao bài (nhiều bước)
- [ ] TC-09 Danh sách bài đánh giá (bổ sung đối tượng, số đã nộp, chờ chấm)
- Ghi chú khi xong:

### Nhóm 5. Giáo viên C: chấm bài
- [ ] TC-11 Chi tiết bài
- [ ] TC-12 Bài nộp của một bài (dùng bài nộp từ nhóm 1)
- [ ] TC-13 Chấm bài, kết quả hiện lại bên Học sinh và Phụ huynh
- [ ] Kiểm tra vòng khép kín: giao bài, làm bài, chấm, xem kết quả
- Ghi chú khi xong:

### Sau cùng
- [ ] Màn chung (`common.md`) cho cả bốn vai trò
- [ ] Đồng bộ lại `prompts/admin.md` với mockup Admin cuối (giữ làm mô tả yêu cầu; không làm prompt cho vai trò khác vì không còn dùng Claude Design)
- [ ] Thử đúng khổ 390px trên điện thoại thật hoặc devtools (Chrome headless không xuống dưới khoảng 500px)
