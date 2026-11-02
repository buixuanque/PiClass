# STATE — đang ở đâu, làm gì tiếp

Không ghi số PR hay nhánh chưa push/chưa merge (đọc từ git bằng `/tiep`). File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-11-03 · bởi: review đợt hai và sửa cổng Phụ huynh

## Đang làm
- **Cổng Giáo viên:** xong toàn bộ, đã merge `main` (vận hành lớp, đơn xin phép và tổng quan, khóa học, bài đánh giá TC-07..14, gắn khóa vào lớp `DEC-LRN-036`)
  - Còn: mockup chưa cập nhật cho `DEC-LRN-028`, `035`, `036`; user đang tự test; chấm bài kiểu mới (`DEC-LRN-029`) chưa chạy review độc lập
- **Cổng Học sinh (ST-01..09):** xong toàn bộ, đã merge: quyết định `DEC-LRN-037..041`, BE, FE, review opus và sửa; chưa xem bằng trình duyệt thật; minor còn lại `B-088`, `B-089`; tổng hợp: `docs/product/screens/student-portal-summary.md`
- **Cổng Phụ huynh:** xong toàn bộ, đã merge: quyết định `DEC-LRN-042..047` (lịch calendar, điểm danh, ô chọn con ở thanh trên, nhận xét, bài làm của con, mục "Lớp & khóa học"); review opus đã chạy cho P1, P2 và đợt hai (`DEC-LRN-046`, `DEC-LRN-047`), đã sửa và merge
  - Chưa xem bằng trình duyệt thật (chỉ test và ảnh chụp Chromium); chưa xem bằng mắt: Lịch học ở 390px, chi tiết buổi trong calendar; minor còn lại `B-090..B-095`; tổng hợp: `docs/product/screens/parent-portal-summary.md`
  - `DEC-LRN-047` dùng mặc định đề xuất cho bốn câu user chưa trả lời (hiện sĩ số không hiện tên, menu thứ 5, mọi lời phê theo visibility, không xem tệp bài làm): user đổi được
- **Cổng Admin:** xong toàn bộ, đã merge `main`.

## Việc kế tiếp (làm từ trên xuống)
1. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user
2. Thông báo trong ứng dụng (chuông CM-06, `B-090`, `DEC-LRN-045`): user muốn rà soát riêng, làm sau khi hoàn thiện chức năng các cổng

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
| Phụ huynh | [x] xong, đã merge `main` (đã review độc lập hai đợt và sửa) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
