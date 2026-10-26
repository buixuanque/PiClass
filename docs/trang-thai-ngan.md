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
- [~] 6. Khóa học/ghi danh (AD-12/12a) — FE đã merge `main` (`piclass-api` PR #46, `piclass-web` PR #11). Cấp lại lượt làm (AD-13) hoãn tới khi có màn chi tiết bài nộp (TC-13, cổng Giáo viên/Học sinh); nhật ký audit (AD-14) không làm UI ở V1. **Đang mở rộng (2026-10-26):** chuyển ghi danh "theo lớp" từ một lần sang liên kết sống Class–Course — BE xong, chờ PR (`piclass-api` PR #47); plan FE (port UI "Gắn lớp vào khóa" thay dialog "Theo lớp" cũ) chưa làm. Một quyết định nghiệp vụ còn mở: `ARCH-35` (kết thúc ghi danh cá nhân có thể làm học sinh mất khóa dù vẫn ở lớp đang gắn khóa đó).

## Frontend — các cổng khác
- [ ] Giáo viên (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Học sinh (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Phụ huynh (chưa bắt đầu port FE, chỉ có mockup)

## Đang làm tiếp theo
Chờ merge `piclass-api` PR #47 (liên kết Class–Course sống). Sau đó: lên plan FE cho tính năng này (thay dialog "Theo lớp" bằng UI "Gắn lớp"), rồi tổng hợp danh sách "giữ/đơn giản/tạm bỏ" của cổng Admin, chuyển sang cổng Giáo viên (port FE, hiện chỉ có mockup). Quyết định mở không chặn còn lại: #4 (AD-05c "gửi lại lời mời"), Q6 (khung điều hướng Admin+Teacher), `ARCH-35` (kết thúc ghi danh cá nhân khi vẫn đủ điều kiện qua lớp).

---
`[x]` xong · `[~]` gần xong, còn chốt nhỏ · `[ ]` chưa làm
