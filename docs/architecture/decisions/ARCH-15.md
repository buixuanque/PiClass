# ARCH-15 — Phạm vi quyền sửa Assessment của Teacher

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT (tạm, V1)` Phát hiện khi final-review Plan 4: `permission-matrix.md`/`data-scope.md` ghi quyền Teacher cho Assessment là "C" (scope theo Class/Course được phân công), nhưng code hiện tại (và Global Constraints của plan) chỉ cho phép đúng Teacher đã tạo Assessment (`created_by_user_id`) sửa/publish/close/cancel — đồng nghiệp cùng dạy 1 lớp không sửa được bài của nhau. Chốt tạm: giữ owner-only cho V1 (chặt hơn ma trận quyền, không phải lỗ hổng, chỉ thiếu tiện lợi); mở rộng sang Class/Course-scope cần thiết kế riêng (hành động nào nên theo scope lớp, hành động nào vẫn nên owner-only).

**Ảnh hưởng:** Assessment module; trải nghiệm co-teacher.
