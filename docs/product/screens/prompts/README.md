# Bộ prompt cho Claude Design — danh sách theo dõi

Mỗi mục dưới đây là một prompt. Mở file tương ứng, copy khối prompt, dán vào Claude Design, vẽ xong thì đổi `[ ]` thành `[x]`.

**Tổng: 111 prompt** (gồm prompt Ghép và Hành trình).

## Cách dùng

**Chế độ làm việc (chốt 2026-10-23):** UI của mockup hiện tại là base đã chốt, làm tiếp bằng cách sửa và bổ sung trên đó. Với màn mockup đã có, chỉ lấy từ prompt các phần còn thiếu (thông tin, quy tắc, trạng thái, tình huống đặc biệt) và yêu cầu Claude Design sửa trên màn đang có; với màn chưa có, dùng nguyên prompt và dặn bám kiểu màn tương tự trong mockup. Điều hướng của `CM-10` có chỗ khác mockup (ví dụ giáo viên: mockup có "Điểm danh", "Bài tập", "Bài kiểm tra" riêng); khi khác thì theo mockup.

**Hướng đã chốt:** PiClass V1 là **web app responsive** (một giao diện web co giãn từ máy tính xuống điện thoại); bản app điện thoại riêng sẽ thiết kế lại sau. Design system và mockup mẫu trong repo `piclass-mockup` (thư mục `design-system`) là chuẩn bắt buộc về khung, kiểu thẻ và điều hướng.

1. Mở phiên Claude Design mới, đính kèm design system và mockup mẫu (token, component, mockup Giáo viên/Học sinh/Phụ huynh), rồi dán prompt trong [00-nen.md](00-nen.md) một lần.
2. Với mỗi màn: copy khối prompt trong file của cổng, dán vào phiên. Mỗi prompt tự đứng được, không cần dán cùng prompt khác.
3. Prompt chỉ nói **cần gì** (tình huống, thông tin, hành động, liên kết, quy tắc, trường hợp đặc biệt, dữ liệu mẫu), không áp đặt bố cục bên trong vùng nội dung (khung ngoài và kiểu trình bày bám mockup). Mọi màn được viết như thể API đã đầy đủ. Giao diện là web **responsive** cho cả bốn nhóm theo một quy tắc co giãn duy nhất ghi trong prompt nền (học sinh và phụ huynh dùng cả điện thoại lẫn máy tính).
4. Thứ tự trong mỗi nhóm là thứ tự vẽ gợi ý (màn nền và màn dùng hằng ngày trước, màn thiết lập một lần sau). Thứ tự giữa các nhóm: chung → giáo viên → học sinh → phụ huynh → Admin → hành trình.
5. **Gắn kết các màn:** mỗi prompt có mục `Liên kết` (đến từ đâu, dẫn tới màn nào, mang theo thông tin gì). Các chức năng lớn đã tách thành nhiều prompt (chi tiết lớp, soạn bài, soạn câu hỏi, làm bài…) có thêm một prompt **Ghép** (mã kết thúc bằng `-G`): dán sau khi đã vẽ các phần để gộp chúng thành một chức năng liền mạch. Cuối cùng, các prompt **Hành trình** (`HT-…`) nối các màn thành luồng bấm thử được từ đầu đến cuối.

Đặc tả đầy đủ của từng màn (dữ liệu, API, lỗi) nằm ở thư mục cha `docs/product/screens/`; prompt chỉ là bản chắt lọc cho thiết kế.

## Chung cho mọi vai trò (11 prompt) — [chung.md](chung.md)

- [ ] **CM-10** — Khung điều hướng theo vai trò
- [ ] **CM-01** — Đăng nhập
- [ ] **CM-02** — Quên mật khẩu
- [ ] **CM-03** — Đặt lại mật khẩu
- [ ] **CM-09** — Kích hoạt tài khoản lần đầu
- [ ] **CM-04** — Chọn / đổi vai trò
- [ ] **CM-05** — Hồ sơ cá nhân
- [ ] **CM-06** — Trung tâm thông báo
- [ ] **CM-07** — Trang lỗi
- [ ] **CM-08** — Xem / tải tệp đính kèm
- [ ] **CM-10-G** — Ghép: vào ứng dụng và chuyển vai trò

## Giáo viên: vận hành lớp (10 prompt) — [teacher-van-hanh.md](teacher-van-hanh.md)

- [ ] **TC-05** — Buổi học và điểm danh
- [ ] **TC-04** — Lịch dạy
- [ ] **TC-01** — Tổng quan
- [ ] **TC-02** — Lớp của tôi
- [ ] **TC-03** — Chi tiết lớp
- [ ] **TC-03b** — Lịch sử thay đổi của lớp
- [ ] **TC-06** — Đơn xin phép
- [ ] **TC-14** — Tiến độ học sinh
- [ ] **TC-15** — Khóa học của tôi
- [ ] **TC-03-G** — Ghép: Chi tiết lớp của giáo viên

## Giáo viên: câu hỏi, bài đánh giá, chấm bài (18 prompt) — [teacher-bai-danh-gia.md](teacher-bai-danh-gia.md)

- [ ] **TC-09** — Danh sách bài đánh giá
- [ ] **TC-10a** — Soạn bài: thông tin chung
- [ ] **TC-10b** — Soạn bài: chọn câu hỏi và điểm
- [ ] **TC-10c** — Soạn bài: chọn đối tượng giao
- [ ] **TC-10d** — Soạn bài: xem trước và phát hành
- [ ] **TC-10e** — Bài đã phát hành: đóng, mở lại, hủy, phiên bản mới
- [ ] **TC-10-G** — Ghép: soạn và giao bài liền mạch
- [ ] **TC-11** — Chi tiết bài
- [ ] **TC-12** — Bài nộp của một bài
- [ ] **TC-13a** — Chấm bài: chấm điểm
- [ ] **TC-13b** — Chấm bài: nhận xét và công bố kết quả
- [ ] **TC-13-G** — Ghép: chấm điểm, nhận xét và công bố cho một học sinh
- [ ] **TC-07** — Ngân hàng câu hỏi
- [ ] **TC-08a** — Soạn câu hỏi: nhóm chọn đáp án
- [ ] **TC-08b** — Soạn câu hỏi: nhóm trả lời bằng chữ
- [ ] **TC-08c** — Soạn câu hỏi: nhóm cấu trúc
- [ ] **TC-08d** — Soạn câu hỏi: nộp tệp
- [ ] **TC-08-G** — Ghép: trình soạn câu hỏi thống nhất cho 9 loại

## Học sinh (17 prompt) — [student.md](student.md)

- [ ] **ST-01** — Tổng quan
- [ ] **ST-02** — Lịch học
- [ ] **ST-03** — Bài tập
- [ ] **ST-04** — Bài kiểm tra
- [ ] **ST-05** — Chi tiết bài
- [ ] **ST-06** — Làm bài (phần chung)
- [ ] **ST-06-A** — Làm bài: câu chọn đáp án
- [ ] **ST-06-B** — Làm bài: câu nhập chữ
- [ ] **ST-06-C** — Làm bài: câu nối cặp và sắp xếp
- [ ] **ST-06-D** — Làm bài: câu nộp tệp
- [ ] **ST-06-G** — Ghép: Làm bài liền mạch
- [ ] **ST-06b** — Đã nộp bài
- [ ] **ST-07** — Kết quả
- [ ] **ST-07b** — Xem lại bài làm
- [ ] **ST-07-G** — Ghép: Kết quả và xem lại bài làm
- [ ] **ST-08** — Tiến độ học tập
- [ ] **ST-09** — Khóa học của em

## Phụ huynh (9 prompt) — [parent.md](parent.md)

- [ ] **PR-01** — Chọn con
- [ ] **PR-02** — Tổng quan của con
- [ ] **PR-03** — Lịch học của con
- [ ] **PR-04** — Điểm danh của con
- [ ] **PR-05** — Bài và kết quả của con
- [ ] **PR-06** — Nhận xét
- [ ] **PR-07** — Tiến độ của con
- [ ] **PR-08** — Đơn xin phép
- [ ] **PR-03-G** — Ghép: Theo dõi con

## Quản trị viên (Admin) (34 prompt) — [admin.md](admin.md)

- [x] **AD-01** — Tổng quan
- [x] **AD-15** — Thiết lập lần đầu
- [x] **AD-09** — Danh sách lớp học
- [x] **AD-10** — Khung chi tiết lớp
- [x] **AD-10a** — Chi tiết lớp: Tổng quan
- [x] **AD-10b** — Chi tiết lớp: Học sinh
- [x] **AD-10c** — Chi tiết lớp: Giáo viên
- [x] **AD-10d** — Chi tiết lớp: Lịch
- [x] **AD-10e** — Chi tiết lớp: Buổi học
- [ ] **AD-10-G** — Ghép: Chi tiết lớp học
- [x] **AD-11** — Lịch tổng hợp
- [x] **AD-04** — Danh sách học sinh
- [x] **AD-05** — Chi tiết học sinh
- [x] **AD-05a** — Tạo / sửa hồ sơ học sinh
- [x] **AD-05b** — Mời tạo tài khoản
- [x] **AD-05c** — Gửi lại lời mời
- [ ] **AD-05-G** — Ghép: Hồ sơ học sinh
- [x] **AD-06** — Danh sách phụ huynh
- [x] **AD-06a** — Chi tiết phụ huynh
- [x] **AD-06b** — Tạo / sửa hồ sơ phụ huynh
- [x] **AD-06c** — Liên kết phụ huynh – học sinh
- [ ] **AD-06-G** — Ghép: Hồ sơ phụ huynh
- [x] **AD-07** — Danh sách giáo viên
- [x] **AD-07a** — Chi tiết giáo viên
- [x] **AD-07b** — Tạo / sửa hồ sơ giáo viên
- [ ] **AD-07-G** — Ghép: Hồ sơ giáo viên
- [x] **AD-08** — Môn học
- [x] **AD-12** — Khóa học
- [x] **AD-12a** — Ghi danh vào khóa học
- [ ] **AD-12-G** — Ghép: Khóa học và ghi danh
- [x] **AD-02** — Danh sách tài khoản
- [x] **AD-03** — Chi tiết tài khoản
- [x] **AD-13** — Cấp lại lượt làm bài
- [x] **AD-14** — Nhật ký audit

## Hành trình (nối các màn thành luồng) (12 prompt) — [hanh-trinh.md](hanh-trinh.md)

- [ ] **HT-01** — Lần đầu vào hệ thống (mọi vai trò)
- [ ] **HT-02** — Quên mật khẩu
- [ ] **HT-03** — Thiết lập hệ thống lần đầu
- [ ] **HT-04** — Tiếp nhận một học viên mới
- [ ] **HT-05** — Quản lý một lớp trong tuần
- [ ] **HT-06** — Một buổi dạy từ đầu đến cuối
- [ ] **HT-07** — Giao bài, chấm bài, công bố kết quả
- [ ] **HT-08** — Theo dõi một lớp và học sinh
- [ ] **HT-09** — Từ thông báo đến kết quả bài làm
- [ ] **HT-10** — Xem lịch và chuẩn bị buổi học
- [ ] **HT-11** — Xem tình hình của con
- [ ] **HT-12** — Báo con nghỉ học
