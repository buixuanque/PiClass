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
- [x] 6. Khóa học/ghi danh (AD-12/12a) — FE đã merge `main` (`piclass-api` PR #46, `piclass-web` PR #11). Cấp lại lượt làm (AD-13) hoãn tới khi có màn chi tiết bài nộp (TC-13, cổng Giáo viên/Học sinh); nhật ký audit (AD-14) không làm UI ở V1. **Mở rộng liên kết Class–Course sống — BE + FE xong, đã merge `main`** (`piclass-api` PR #47; `piclass-web` PR #12). Một quyết định nghiệp vụ còn mở: `ARCH-35` (kết thúc ghi danh cá nhân có thể làm học sinh mất khóa dù vẫn ở lớp đang gắn khóa đó). 6 lỗi minor từ review cuối còn để sau (xem `progress.md`).

- [~] 7. Thêm hồ sơ kèm tài khoản + Admin/Teacher đặt lại mật khẩu (`DEC-USR-021/022`) — spec đã duyệt 2026-10-26 (`superpowers/specs/2026-10-26-create-profile-with-account-design.md`), code BE xong (nhánh `feat/create-profile-with-account`), đã merge `main` (`piclass-api` PR #48); không deploy BE đứng riêng, chờ FE; mockup khối Tài khoản cần chốt trước plan FE.

## Frontend — các cổng khác
- [ ] Giáo viên (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Học sinh (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Phụ huynh (chưa bắt đầu port FE, chỉ có mockup)

## Đang làm tiếp theo
Lát 7 (thêm hồ sơ kèm tài khoản, đặt lại mật khẩu): spec đã duyệt, code BE xong, BE đã merge; tiếp theo mockup khối Tài khoản rồi plan FE. Tổng hợp cổng Admin đã chốt 2026-10-26 (gần xong). Còn trước khi đóng Admin: chặn thêm vào lớp lưu trữ và học sinh ngừng hoạt động (`DEC-LRN-014`, `DEC-USR-020`), bấm thử Admin trên trình duyệt, chặn ghi danh cá nhân khi còn trong lớp đã gắn khóa (`DEC-LRN-015`). Sau đó chuyển sang cổng Giáo viên (port FE, hiện chỉ có mockup). Quyết định mở không chặn còn lại: #4 (AD-05c "gửi lại lời mời"), Q6 (khung điều hướng Admin+Teacher), `ARCH-35` (kết thúc ghi danh cá nhân khi vẫn đủ điều kiện qua lớp).

---
`[x]` xong · `[~]` gần xong, còn chốt nhỏ · `[ ]` chưa làm
