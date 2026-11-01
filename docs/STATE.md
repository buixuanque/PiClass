# STATE — đang ở đâu, làm gì tiếp

Không ghi số PR hay nhánh chưa push/chưa merge (đọc từ git bằng `/tiep`). File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-11-01 · bởi: cải tiến sau 4c (màn chấm, khóa, tab Học tập) đã merge

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
    - Chấm bài kiểu mới (`DEC-LRN-029`): chấm cả bài một lần lưu, tab Trao đổi — code BE (`PUT /submissions/{id}/grades`) và FE xong, chờ review độc lập rồi merge; mockup chưa cập nhật
    - 4d: TC-14
    - Cải tiến sau 4c (`DEC-LRN-028`): gọn màn chấm TC-13, bỏ %, "Hủy công bố", nút "Bài nộp và chấm" ở mục bài của khóa, tab Học tập ở chi tiết lớp — xong, đã merge; user đang tự test; mockup chưa cập nhật
- **Bước** (quy ước cuối file):
  - Làm lại mô hình Khóa học (`DEC-LRN-026`, `ARCH-38`): quyết định xong → BE xong, đã review, đã merge  → không làm mockup (user chốt 2026-10-31), FE làm từng cổng: Admin xong, đã merge , Giáo viên xong, đã merge → Học sinh chưa làm (hoãn đến sau khi xong cổng Giáo viên)
- **Cổng Admin:** xong toàn bộ, đã merge `main`.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 4d Giáo viên (TC-14), xong thì cổng Giáo viên đóng
2. Làm lại mô hình Khóa học, FE cổng Học sinh (ST-09) theo OpenAPI mới, không mockup
3. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
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
| Giáo viên | [ ] đang port: lát 1 (TC-02..05), lát 2 (TC-06, TC-01) và lát 3 (TC-15) xong, đã merge `main`; Bài đánh giá còn 4d (TC-14); 4a, 4b, 4c, 4e xong |
| Học sinh | [ ] chưa port (có mockup) |
| Phụ huynh | [ ] chưa port (có mockup) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
