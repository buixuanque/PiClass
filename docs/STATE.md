# STATE — đang ở đâu, làm gì tiếp

Không ghi số PR hay nhánh chưa push/chưa merge (đọc từ git bằng `/tiep`). File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-11-02 · bởi: cổng Phụ huynh cụm P1 (DEC-LRN-042)

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự:
  - Vận hành lớp (TC-02..05) — xong
  - Đơn xin phép & Tổng quan (TC-06, TC-01)
  - Khóa học (TC-15)
  - Bài đánh giá (TC-07..14), chia nhỏ:
    - 4a: TC-07, TC-08 — xong
    - 4b: TC-09, TC-10 — xong
    - 4e: TC-08 soạn câu hỏi cùng khung Soạn bài, soạn nhiều câu — xong
    - 4c: TC-11, TC-12, TC-13 — xong, đã merge; nghiệp vụ `DEC-LRN-027`; dữ liệu mẫu: `docs/setup/dev-demo-data.md`
    - Chấm bài kiểu mới (`DEC-LRN-029`): chấm cả bài một lần lưu, tab Trao đổi — xong, đã merge, chưa chạy review độc lập; đã chỉnh UX lần 2 và 3 (`DEC-LRN-029` mục 7, `DEC-LRN-030`: nhận xét như lời phê chỉ lưu khi Lưu nháp hoặc Công bố, bỏ tab Trao đổi, Chấm lại), xong, đã merge; user đang tự test; mockup chưa cập nhật
    - Dữ liệu mẫu khóa học (`TeacherCourseDemoSeeder`, `docs/setup/dev-demo-data.md`) và tab Học tập của lớp dạng danh sách lọc Tất cả / Khóa học / Bài kiểm tra — xong, đã merge
    - 4d: TC-14 — xong, đã merge; màn `/teacher/progress`, nhận xét học sinh một chiều (`DEC-LRN-031`), tiến độ có lọc ngày và môn, điểm danh, biểu đồ điểm; chưa so khớp mockup bằng mắt
    - Màn điểm danh TC-05 chỉ để điểm danh (`DEC-LRN-032`, `DEC-LRN-033`): bỏ khối nội dung/ghi chú và Hủy buổi (còn ở TC-04), giữ nút Hoàn tất buổi bằng tay — xong, đã merge; mockup đã cập nhật; xem lại quy tắc hoàn tất sau khi vận hành thực tế
    - Ghi chú điểm danh (`DEC-LRN-034`): ô ghi chú tùy chọn lúc điểm danh, hộp thoại Sửa chỉ còn ghi chú, lý do sửa mặc định ở FE — xong, đã merge; mockup đã cập nhật
    - Danh sách buổi ở màn điểm danh (`DEC-LRN-035`): mọi buổi tới hết hôm nay, buổi mới ở trên, lọc theo lớp — xong (FE), đã merge; mockup chưa cập nhật
    - Gắn khóa học vào lớp từ tab Học tập (`DEC-LRN-036`): quyết định xong → code BE xong → code FE xong → review độc lập xong, đã sửa → chờ merge; mockup chưa cập nhật
    - Cải tiến sau 4c (`DEC-LRN-028`): gọn màn chấm TC-13, bỏ %, "Hủy công bố", nút "Bài nộp và chấm" ở mục bài của khóa, tab Học tập ở chi tiết lớp — xong, đã merge; user đang tự test; mockup chưa cập nhật
- **Bước** (quy ước cuối file):
  - Làm lại mô hình Khóa học (`DEC-LRN-026`, `ARCH-38`): quyết định xong → BE xong, đã review, đã merge  → không làm mockup (user chốt 2026-10-31), FE làm từng cổng: Admin xong, đã merge , Giáo viên xong, đã merge → Học sinh xong, đã merge
- **Lát:** cổng Học sinh (ST-01..09) — xong toàn bộ, đã merge: quyết định `DEC-LRN-037..040`, BE, FE 3 cụm, review opus và sửa (`DEC-LRN-041`); chưa xem bằng trình duyệt thật; minor còn lại `B-088`, `B-089`; tổng hợp: `docs/product/screens/student-portal-summary.md`
- **Lát:** cổng Phụ huynh (PR-01..08), cùng cách làm cổng Học sinh:
  - Quyết định (`DEC-LRN-042`) — xong
  - Cụm P1: BE (lớp của con, lịch theo con, điểm danh của con) + FE PR-01, PR-02 — xong, đã merge; chưa xem bằng trình duyệt thật; tổng hợp: `docs/product/screens/parent-portal-summary.md`
  - Cụm P2 (PR-03 Lịch, PR-04 Điểm danh, PR-05..07 Kết quả, PR-08 Đơn xin phép) → review opus → tổng hợp cuối
- **Cổng Admin:** xong toàn bộ, đã merge `main`.

## Việc kế tiếp (làm từ trên xuống)
1. Cổng Phụ huynh cụm P2 (PR-03..08), sau đó review độc lập (opus)
2. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Bộ lọc môn ở màn Tiến độ (TC-14) gần như thừa vì đã lọc theo lớp (môn suy ra từ lớp): giữ như mockup hay bỏ
- Quyết định về PHPStan: 30 lỗi mới cùng loại D-22 ở `piclass-api` (cho cập nhật baseline hay làm riêng việc gõ kiểu relation)
- Scheduler: user chốt 2026-11-01 chưa cần chạy `schedule:run`; hẹn giờ công bố điểm (BE đã có) chưa hoạt động. FE lát 4c vẫn hiện tùy chọn "Hẹn giờ công bố" nhưng chỉ báo "đang phát triển", không gửi `publish_at`
- BE có thêm cờ `can_manage` cho màn chi tiết bài (TC-11) không
- 12 minor FE chưa sửa (liệt kê ở mô tả PR `piclass-web` lát 4c) và 12 minor BE chưa sửa (mô tả các PR `piclass-api` lát 4c)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Cổng
| Cổng | FE |
|---|---|
| Admin | [x] xong |
| Giáo viên | [x] xong, đã merge `main` |
| Học sinh | [x] xong, đã merge `main` (đã review độc lập và sửa) |
| Phụ huynh | [ ] đang làm: PR-01, PR-02 xong, còn PR-03..08 |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
