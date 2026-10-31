# ARCH-14 — Sync ClassMember/CourseEnrollment mới → StudentAssessment

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `[CẦN QUYẾT ĐỊNH]` Phát hiện khi final-review Plan 4 (Module 3 BE): nếu Assessment đã có audience gắn vào một Class/Course, Student join lớp/course đó **sau** thời điểm gắn audience hiện không tự nhận `StudentAssessment` — không có hook nào gọi `StudentAssessmentSync::ensureForStudent` khi `ClassMember`/`CourseEnrollment` mới được tạo (2 controller đó thuộc Module 2, đã merge `main` trước khi quyết định Course audience ra đời). Cần chọn: (a) hook trong `AddClassMemberController`/`EnrollInCourseController` gọi sync cho mọi Assessment có audience đang hiệu lực của Class/Course đó, hay (b) tính lại tại thời điểm đọc (`GET /me/assessments`) thay vì ghi `StudentAssessment` trước. Chưa fix trong Plan 4 vì đụng sang controller của module khác đã merge.

**Cập nhật 2026-10-30 (`DEC-LRN-026`):** `CourseEnrollment` đã bỏ; còn hai nguồn là `ClassMember` và đối tượng khóa. Đề xuất cho bài của khóa: tính eligibility khi đọc, xem `ARCH-38`.

**Ảnh hưởng:** Module 3 (Assessment)+Module 2 (ClassMember/CourseEnrollment); Student/Parent trải nghiệm thiếu bài được giao.
