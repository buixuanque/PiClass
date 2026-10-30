# ARCH-38 — Ranh giới module cho khóa học sau `DEC-LRN-026`

Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái:** ĐÃ CHỐT VÀ ĐÃ LÀM ở BE (2026-10-30, nhánh `feat/course-model-rework` của `piclass-api`).

**Bối cảnh:** `DEC-LRN-026` bỏ `CourseEnrollment` và `CourseClassLink`, thêm `CourseAudience`, `CourseItem` và `Assessment.course_id`. Cần chia chủ ghi giữa Academic (đang sở hữu Course) và Assessment (đang sở hữu Assessment).

**Đề xuất:**

| Model | Module chủ ghi | Ghi chú |
|---|---|---|
| `Course` | Academic | Giữ nguyên. |
| `CourseAudience` | Academic | Thêm/gỡ Lớp hoặc Student. Assessment chỉ đọc qua contract Academic để tính "học viên của khóa" (Student trực tiếp ∪ ClassMember đang hoạt động của Lớp trong danh sách). |
| `CourseItem` | Assessment | Mục trỏ tới bài nên thuộc module giữ bài (tạo/sao chép/sửa/gỡ/liệt kê mục đều ở Assessment). Enum `CourseItemType` đặt ở `Academic/Enums` (tránh luật R8 trùng literal `assessment` với tên route parameter trong Assessment); model `CourseItem` có giá trị mặc định `type`. Khi `B-062` thêm loại mục bài giảng/video, cân nhắc tách module Course Content. |
| `Assessment.course_id` | Assessment | Tham chiếu Course qua contract; mọi truy vấn danh sách bài cá nhân lọc `course_id IS NULL` qua một scope duy nhất, có test canh. Bài có `course_id` không được có `AssessmentAudience` (test). |

**Bỏ cùng `DEC-LRN-026`:** `CourseEnrollment`, `CourseClassLink`, các action `EnrollInCourse`, `EndCourseEnrollment`, `LinkClassToCourse`, `UnlinkClassFromCourse`, `SyncCourseEnrollments*`, và các mã lỗi `ALREADY_LINKED`, `ENROLLMENT_LINKED_TO_CLASS`, `ENROLLMENT_COVERED_BY_CLASS`, `ENROLLMENT_ALREADY_VIA_CLASS` (`error-codes.md`). Mã `COURSE_ARCHIVED` và `CLASS_ARCHIVED` giữ, đổi nghĩa sang "thêm đối tượng/mục vào khóa/lớp đã lưu trữ".

**Học sinh nhận bài (chốt 2026-10-30 sau khi đọc code):** với **bài của khóa**, "thấy bài" tính từ việc học sinh là học viên của khóa (`CourseLearnersQuery::isLearner`); dòng `StudentAssessment` chỉ được tạo **khi học sinh bắt đầu làm** (`StartAttempt`), không có hook khi vào/ra lớp hay publish. Các chỗ đọc phải chấp nhận học sinh chưa có dòng: `StartAttempt`/`AttemptPolicy` (tạo dòng nếu là học viên), `StudentAssessmentMetadataQuery` (danh sách/chi tiết "bài của em" thêm bài của các khóa em thuộc), `ListAssessmentStudents` (học viên chưa có dòng hiện "chưa bắt đầu"). Học sinh rời khóa thì mất quyền thấy và làm bài mới; dòng và bài làm đã có giữ nguyên. **Bài cá nhân** giao theo lớp giữ cơ chế ghi sẵn như cũ; việc đồng bộ khi học sinh vào lớp sau là phần còn lại của `B-021`, làm riêng.

**Kết quả thực hiện:** `CourseLearnersQuery` (Academic) là nguồn duy nhất cho "học viên của khóa" (`isLearner`, `courseIdsFor`, danh sách kèm nguồn). `AttemptPolicy::start` với bài của khóa luôn dựa vào việc còn là học viên, kể cả khi đã có dòng `StudentAssessment` (học sinh bị gỡ không bắt đầu được lượt mới; bài đã làm giữ nguyên). Bài của khóa chưa có dòng giao bài hiện ở "bài của em" với trạng thái `assigned`, và ở danh sách học sinh của giáo viên dưới dạng dòng ảo (`id` null).
