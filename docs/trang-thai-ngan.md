# Trạng thái ngắn

Đọc nhanh, không cần đọc `progress.md`/`mockup-plan.md` đầy đủ. Chi tiết nằm ở hai file đó.

## Backend
- [x] Xong, đã merge `main`.

## Frontend — theo nhóm màn (cổng Admin)
- [x] 0. Liên kết kích hoạt
- [x] 1. Tạo giáo viên
- [x] 2. Môn học, tạo lớp, gán giáo viên
- [x] 3. Tài khoản, Học sinh, Phụ huynh — xong, đã merge `main` (bảng lớp học AD-05: `piclass-api` PR #44, `piclass-web` PR #8)
- [x] 4. Lịch học và buổi học — xong (chưa PR): BE `GET /me/lesson-sessions` (nhánh `feat/my-lesson-sessions-endpoint`); FE tab Lịch/Buổi học (AD-10d/e) + màn mới AD-11 `/admin/schedule` (nhánh `feat/admin-schedule-group4`)
- [ ] 5. Tổng quan Admin + thiết lập lần đầu
- [ ] 6. Khóa học/ghi danh, nhật ký audit, cấp lại lượt làm

## Frontend — các cổng khác
- [ ] Giáo viên (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Học sinh (chưa bắt đầu port FE, chỉ có mockup)
- [ ] Phụ huynh (chưa bắt đầu port FE, chỉ có mockup)

## Đang làm tiếp theo
Nhóm 4 xong cả BE+FE (chưa push/PR — hỏi user trước khi push). Tiếp theo: chọn nhóm 5 (Tổng quan Admin + thiết lập lần đầu) hoặc nhóm 6. Quyết định mở không chặn còn lại: #4 (AD-05c "gửi lại lời mời"), Q6 (khung điều hướng Admin+Teacher).

---
`[x]` xong · `[~]` gần xong, còn chốt nhỏ · `[ ]` chưa làm
