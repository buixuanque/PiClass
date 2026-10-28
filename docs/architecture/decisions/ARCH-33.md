# ARCH-33 — Mô hình nhiều trung tâm (tenancy)

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT (2026-10-24)` Hướng A: mỗi trung tâm một bản triển khai riêng (database và domain riêng), dùng chung một code; không multi-tenant trong một database. Giữ nguyên `DEC-LRN-010`/`BR-COURSE-001` (một tenant trong mỗi bản triển khai). Hướng B (multi-tenant thật với `center_id`) chỉ xem lại khi có hơn khoảng 10 trung tâm hoặc cần một tài khoản dùng nhiều trung tâm. Cần làm: cấu hình theo trung tâm (tên, logo) và quy trình tạo trung tâm mới (seed Admin đầu tiên), chưa có plan. Phạm vi V1 còn lại cùng ngày: giữ nguyên 9 loại câu hỏi; giữ Course (không hoãn); thông báo chỉ trong ứng dụng (giữ `DEC-NOTIF-002`, không thêm email thông báo); chưa làm nhập học sinh hàng loạt (giữ `DEC-USR-004`), làm sau khi ổn định. Nguồn: `research/2026-10-24-review-pham-vi-v1.md`.

**Ảnh hưởng:** Hosting, cấu hình, vận hành nhiều bản.
