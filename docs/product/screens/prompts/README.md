# Bộ prompt cho Claude Design — danh sách theo dõi

Mỗi mục dưới đây là một màn hình. Mở file tương ứng, copy khối prompt của màn đó, dán vào Claude Design, vẽ xong thì đổi `[ ]` thành `[x]`.

**Tổng: 86 prompt.**

## Cách dùng

1. Mở phiên Claude Design mới, đính kèm design system và mockup mẫu, rồi dán prompt trong [00-nen.md](00-nen.md) một lần.
2. Với mỗi màn: copy khối prompt trong file của cổng, dán vào phiên. Mỗi prompt tự đứng được, không cần dán cùng prompt khác.
3. Prompt chỉ nói **cần gì** (tình huống, thông tin, hành động, quy tắc, trường hợp đặc biệt, dữ liệu mẫu), không áp đặt bố cục. Mọi màn được viết như thể API đã đầy đủ.
4. Thứ tự trong mỗi nhóm là thứ tự vẽ gợi ý (màn nền và màn dùng hằng ngày trước, màn thiết lập một lần sau). Thứ tự giữa các nhóm: chung → giáo viên → học sinh → phụ huynh → Admin.
5. Các prompt đã tách (ví dụ `TC-10a…e`, `TC-08a…d`, `ST-06-A…D`) là các phần của cùng một chức năng lớn; vẽ lần lượt trong cùng một phiên để giữ nhất quán.

Đặc tả đầy đủ của từng màn (dữ liệu, API, lỗi) nằm ở thư mục cha `docs/product/screens/`; prompt chỉ là bản chắt lọc cho thiết kế.

## Chung cho mọi vai trò (10 màn) — [chung.md](chung.md)

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

## Giáo viên: vận hành lớp (9 màn) — [teacher-van-hanh.md](teacher-van-hanh.md)

- [ ] **TC-05** — Buổi học và điểm danh
- [ ] **TC-04** — Lịch dạy
- [ ] **TC-01** — Tổng quan
- [ ] **TC-02** — Lớp của tôi
- [ ] **TC-03** — Chi tiết lớp
- [ ] **TC-03b** — Lịch sử thay đổi của lớp
- [ ] **TC-06** — Đơn xin phép
- [ ] **TC-14** — Tiến độ học sinh
- [ ] **TC-15** — Khóa học của tôi

## Giáo viên: câu hỏi, bài đánh giá, chấm bài (15 màn) — [teacher-bai-danh-gia.md](teacher-bai-danh-gia.md)

- [ ] **TC-09** — Danh sách bài đánh giá
- [ ] **TC-10a** — Soạn bài: thông tin chung
- [ ] **TC-10b** — Soạn bài: chọn câu hỏi và điểm
- [ ] **TC-10c** — Soạn bài: chọn đối tượng giao
- [ ] **TC-10d** — Soạn bài: xem trước và phát hành
- [ ] **TC-10e** — Bài đã phát hành: đóng, mở lại, hủy, phiên bản mới
- [ ] **TC-11** — Chi tiết bài
- [ ] **TC-12** — Bài nộp của một bài
- [ ] **TC-13a** — Chấm bài: chấm điểm
- [ ] **TC-13b** — Chấm bài: nhận xét và công bố kết quả
- [ ] **TC-07** — Ngân hàng câu hỏi
- [ ] **TC-08a** — Soạn câu hỏi: nhóm chọn đáp án
- [ ] **TC-08b** — Soạn câu hỏi: nhóm trả lời bằng chữ
- [ ] **TC-08c** — Soạn câu hỏi: nhóm cấu trúc
- [ ] **TC-08d** — Soạn câu hỏi: nộp tệp

## Học sinh (15 màn) — [student.md](student.md)

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
- [ ] **ST-06b** — Đã nộp bài
- [ ] **ST-07** — Kết quả
- [ ] **ST-07b** — Xem lại bài làm
- [ ] **ST-08** — Tiến độ học tập
- [ ] **ST-09** — Khóa học của em

## Phụ huynh (8 màn) — [parent.md](parent.md)

- [ ] **PR-01** — Chọn con
- [ ] **PR-02** — Tổng quan của con
- [ ] **PR-03** — Lịch học của con
- [ ] **PR-04** — Điểm danh của con
- [ ] **PR-05** — Bài và kết quả của con
- [ ] **PR-06** — Nhận xét
- [ ] **PR-07** — Tiến độ của con
- [ ] **PR-08** — Đơn xin phép

## Quản trị viên (Admin) (29 màn) — [admin.md](admin.md)

- [ ] **AD-01** — Tổng quan
- [ ] **AD-15** — Thiết lập lần đầu
- [ ] **AD-09** — Danh sách lớp học
- [ ] **AD-10** — Khung chi tiết lớp
- [ ] **AD-10a** — Chi tiết lớp: Tổng quan
- [ ] **AD-10b** — Chi tiết lớp: Học sinh
- [ ] **AD-10c** — Chi tiết lớp: Giáo viên
- [ ] **AD-10d** — Chi tiết lớp: Lịch
- [ ] **AD-10e** — Chi tiết lớp: Buổi học
- [ ] **AD-11** — Lịch tổng hợp
- [ ] **AD-04** — Danh sách học sinh
- [ ] **AD-05** — Chi tiết học sinh
- [ ] **AD-05a** — Tạo / sửa hồ sơ học sinh
- [ ] **AD-05b** — Mời tạo tài khoản
- [ ] **AD-05c** — Gửi lại lời mời
- [ ] **AD-06** — Danh sách phụ huynh
- [ ] **AD-06a** — Chi tiết phụ huynh
- [ ] **AD-06b** — Tạo / sửa hồ sơ phụ huynh
- [ ] **AD-06c** — Liên kết phụ huynh – học sinh
- [ ] **AD-07** — Danh sách giáo viên
- [ ] **AD-07a** — Chi tiết giáo viên
- [ ] **AD-07b** — Tạo / sửa hồ sơ giáo viên
- [ ] **AD-08** — Môn học
- [ ] **AD-12** — Khóa học
- [ ] **AD-12a** — Ghi danh vào khóa học
- [ ] **AD-02** — Danh sách tài khoản
- [ ] **AD-03** — Chi tiết tài khoản
- [ ] **AD-13** — Cấp lại lượt làm bài
- [ ] **AD-14** — Nhật ký audit
