# ARCH-38 — Ranh giới module cho khóa học sau `DEC-LRN-026`

Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái:** ĐỀ XUẤT trong cùng lát sửa tài liệu `DEC-LRN-026` (2026-10-30); chốt khi viết plan BE.

**Bối cảnh:** `DEC-LRN-026` bỏ `CourseEnrollment` và `CourseClassLink`, thêm `CourseAudience`, `CourseItem` và `Assessment.course_id`. Cần chia chủ ghi giữa Academic (đang sở hữu Course) và Assessment (đang sở hữu Assessment).

**Đề xuất:**

| Model | Module chủ ghi | Ghi chú |
|---|---|---|
| `Course` | Academic | Giữ nguyên. |
| `CourseAudience` | Academic | Thêm/gỡ Lớp hoặc Student. Assessment chỉ đọc qua contract Academic để tính "học viên của khóa" (Student trực tiếp ∪ ClassMember đang hoạt động của Lớp trong danh sách). |
| `CourseItem` | Assessment | Mục trỏ tới bài nên thuộc module giữ bài; Academic chỉ đọc danh sách mục để hiển thị khóa. Khi `B-062` thêm loại mục bài giảng/video, cân nhắc tách module Course Content. |
| `Assessment.course_id` | Assessment | Tham chiếu Course qua contract; mọi truy vấn danh sách bài cá nhân lọc `course_id IS NULL` qua một scope duy nhất, có test canh. Bài có `course_id` không được có `AssessmentAudience` (test). |

**Bỏ cùng `DEC-LRN-026`:** `CourseEnrollment`, `CourseClassLink`, các action `EnrollInCourse`, `EndCourseEnrollment`, `LinkClassToCourse`, `UnlinkClassFromCourse`, `SyncCourseEnrollments*`, và các mã lỗi `ALREADY_LINKED`, `ENROLLMENT_LINKED_TO_CLASS`, `ENROLLMENT_COVERED_BY_CLASS`, `ENROLLMENT_ALREADY_VIA_CLASS` (`error-codes.md`). Mã `COURSE_ARCHIVED` và `CLASS_ARCHIVED` giữ, đổi nghĩa sang "thêm đối tượng/mục vào khóa/lớp đã lưu trữ".

**Học sinh nhận bài (liên quan `ARCH-14`):** với bài của khóa, tính eligibility khi đọc từ `CourseAudience` + `ClassMember` (phương án (b) của `ARCH-14`), không có hook ghi dòng cho từng học sinh; `StudentAssessment` tạo lười khi học sinh bắt đầu làm hoặc khi giáo viên cần xem tiến độ (chi tiết chốt khi viết plan BE).
