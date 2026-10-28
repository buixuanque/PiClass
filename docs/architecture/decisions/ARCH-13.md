# ARCH-13 — LessonSession generation window

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT, SỬA 2026-10-27` ~~Tạo/sửa Schedule sinh ngay LessonSession cho 4 tuần kế tiếp... Laravel Scheduler chạy job hàng ngày để luôn giữ cửa sổ 4 tuần tới có sẵn~~ — thay bằng `DEC-SCH-004`: `effective_until` bắt buộc, `CreateSchedule`/`UpdateSchedule` sinh TOÀN BỘ buổi từ `effective_from` đến `effective_until` ngay lúc gọi, không còn cắt cửa sổ 4 tuần. Bỏ hẳn lệnh cron `lesson-sessions:generate-upcoming` và Scheduler job của nó — không còn lịch nào cần sinh dần. Xem Step 9 spec Academic/Lesson/Attendance (giữ nguyên để tham khảo lịch sử, không còn là hành vi hiện tại).

**Ảnh hưởng:** Lesson module.
