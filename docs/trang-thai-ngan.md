# Trạng thái ngắn

Đọc nhanh, không cần đọc `progress.md`/`mockup-plan.md` đầy đủ. Chi tiết nằm ở hai file đó.

## Backend
- [x] Xong, đã merge `main`.

## Frontend — theo nhóm màn (cổng Admin)
- [x] 0. Liên kết kích hoạt
- [x] 1. Tạo giáo viên
- [x] 2. Môn học, tạo lớp, gán giáo viên
- [x] 3. Tài khoản, Học sinh, Phụ huynh — xong, đã merge `main` (bảng lớp học AD-05: `piclass-api` PR #44, `piclass-web` PR #8)
- [x] 4. Lịch học và buổi học — xong, đã merge `main` (`piclass-api` PR #45, `piclass-web` PR #9)
- [x] 5. Tổng quan Admin (AD-01), bỏ AD-15 — xong, đã merge `main` (`piclass-mockup` PR #12, `piclass-web` PR #10)
- [x] 5b. Lịch học của lớp (gộp AD-10d/AD-10e, ARCH-37) — FE đã merge `main` (`piclass-web` PR #16). `admin.md` đã cập nhật (2026-10-28). Còn nợ nhỏ: xem `progress.md` mục 5b.
- [x] 5c. Dọn gap API còn sót (GM-1/GM-3/GM-4/G11, rà lại 2026-10-28) — **GM-1, GM-3, GM-4 đều xong hoàn toàn, đã merge `main`** (GM-1: `piclass-api` PR #54, `piclass-web` PR #17; GM-3: `piclass-api` PR #55, `piclass-web` PR #18; GM-4: `piclass-api` PR #56/#57, `piclass-web` PR #19; docs PR #111/#112/#113). Chi tiết: `progress.md` mục 5c.
- [~] 5d. Buổi học đã hủy vẫn cho phép sửa (bug phát hiện qua dùng thật, 2026-10-28, một phần `ARCH-26(c)`) — **xong cả BE+FE, chưa mở PR** (`piclass-api` nhánh `fix/block-editing-cancelled-session`, `piclass-web` nhánh `fix/block-editing-cancelled-session-fe`): buổi `cancelled` là trạng thái cuối, 409 `SESSION_CANCELLED` khi sửa, FE ẩn nút Sửa. Chi tiết: `progress.md` mục 5d.
- [x] 6. Khóa học/ghi danh (AD-12/12a) — FE đã merge `main` (`piclass-api` PR #46, `piclass-web` PR #11). Cấp lại lượt làm (AD-13) hoãn tới khi có màn chi tiết bài nộp (TC-13, cổng Giáo viên/Học sinh); nhật ký audit (AD-14) không làm UI ở V1. **Mở rộng liên kết Class–Course sống — BE + FE xong, đã merge `main`** (`piclass-api` PR #47; `piclass-web` PR #12). `ARCH-35` cùng toàn bộ `DEC-LRN-014`/`DEC-USR-020` (chặn lớp archived nhận thêm học sinh/giáo viên/lịch, chặn học sinh inactive vào lớp), `effective_until` Schedule bắt buộc + bỏ cron (`DEC-SCH-004`), hủy lịch kiểu Google Calendar 2 lựa chọn (`DEC-SCH-005`) — **BE đã merge `main`** (`piclass-api` PR #49 rồi #50). **FE khớp theo (sửa form Tạo/Sửa lịch bắt `effective_until`, dựng UI 2 lựa chọn hủy buổi) — đã code, test xanh, chưa mở PR** (`piclass-web` nhánh `fix/schedule-effective-until-and-cancel-series`). Thêm field `schedule_id` ở API buổi học để FE dùng — **đã code, test xanh, chưa mở PR** (`piclass-api` nhánh `feat/lesson-session-schedule-id`). 6 lỗi minor từ review cuối còn để sau (xem `progress.md`).

- [x] 7. Thêm hồ sơ kèm tài khoản + Admin/Teacher đặt lại mật khẩu + nhắc đổi mật khẩu lần đầu (`DEC-USR-021/022`) — BE `piclass-api` PR #48 và FE `piclass-web` PR #13 đã merge `main`. FE dựng không mockup, các điểm cần user xác nhận ở `ARCH-36`; Teacher chưa có UI (cổng Giáo viên chưa port).

## Frontend — các cổng khác
- [ ] Giáo viên (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Học sinh (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Phụ huynh (chưa bắt đầu port FE, chỉ có mockup)

## Đang làm tiếp theo
Tổng hợp cổng Admin đã chốt 2026-10-26 (gần xong). Đã làm 2026-10-27: chặn toàn bộ `DEC-LRN-014`, học sinh ngừng hoạt động vào lớp (`DEC-USR-020`), ghi danh cá nhân khi còn trong lớp đã gắn khóa (`DEC-LRN-015`, `ARCH-35`), `effective_until` Schedule bắt buộc + bỏ cron (`DEC-SCH-004`), hủy lịch kiểu Google Calendar 2 lựa chọn + tự động khi lớp archived (`DEC-SCH-005`), FE khớp theo (form lịch bắt buộc `effective_until`, UI 2 lựa chọn hủy buổi), `schedule_id` thêm vào API buổi học — **tất cả đã merge `main`** (`piclass-api` PR #49/#50/#51, `piclass-web` PR #14, docs PR #105). **Gộp tab Lịch+Buổi học (`ARCH-37`) đã xong (2026-10-28):** BE `piclass-api` PR #53, FE `piclass-web` PR #16, docs PR #107/#108 (spec, plan), `admin.md` đã cập nhật. **Dọn gap API còn sót xong (2026-10-28, xem mục 5c):** GM-1, GM-3, GM-4 đều xong hoàn toàn, đã merge `main`. **Bug "sửa buổi đã hủy" (mục 5d) — xong cả BE+FE, chưa mở PR.** Còn trước khi đóng hẳn Admin: bấm thử Admin trên trình duyệt (user tự làm). Sau đó chuyển sang cổng Giáo viên (port FE, hiện chỉ có mockup). Quyết định mở không chặn còn lại: Q6 (khung điều hướng Admin+Teacher, đã chốt công tắc đổi vai trò nhưng CM-10 chưa dựng), phần tài khoản Teacher `inactive` (`users.status`) của `ARCH-28(n)` — chưa code.

---
`[x]` xong · `[~]` gần xong, còn chốt nhỏ · `[ ]` chưa làm
