# ARCH-39 — Module `CourseContent` và chuyển `CourseItem` khỏi Assessment

Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái:** ĐÃ CHỐT (user duyệt 2026-11-25); lát 1a (chuyển `CourseItem`, module `CourseContent`) đã làm ở BE (nhánh `refactor/course-content-module` của `piclass-api`); lát 1b trở đi chưa làm (`superpowers/specs/2026-11-25-course-content-design.md`).

**Hướng gọi (chốt khi làm 1a):** chỉ `CourseContent → Assessment` (Action). Assessment không ghi `course_items`: sao chép bài vào khóa và tạo bài trong khóa đi qua `CourseContent\Actions\AddAssessmentToCourse`, `AddAssessmentsToCourse`, `CreateCourseAssessment`; gỡ mục gọi `Assessment\Actions\ArchiveCourseAssessment`. `operationId` và tag OpenAPI của các endpoint mục khóa đổi từ `assessment.*` sang `courseContent.*` (đường dẫn và JSON không đổi).

**Bối cảnh:** `DEC-LRN-052` thêm bài giảng, tài liệu, video và tiến độ vào khóa. `ARCH-38` giao `CourseItem` cho Assessment vì khi đó mục chỉ trỏ tới bài, và đã ghi sẵn "cân nhắc tách module Course Content" khi làm `B-062`.

**Quyết định:**
- Thêm module thứ 13 **`CourseContent`** (thêm vào `MODULES` của `ArchitectureTest`, dòng mới trong `model-ownership.md`).
- `CourseItem` chuyển chủ ghi từ Assessment sang CourseContent, làm bằng một PR riêng đầu tiên, không đổi hành vi. Assessment chỉ còn đọc (liệt kê bài của khóa, tính tiến độ mục bài đánh giá).
- CourseContent sở hữu `LearningContent`, `ContentVideo`, `ContentAudience`, `VideoCheckpoint`, `ContentProgress`, `CheckpointResponse`. `Course` và `CourseAudience` vẫn của Academic; cài đặt học của khóa (`sequential_unlock`, `completion_rule`, `completion_min_score`) ghi qua Action của Academic.
- Thư viện soạn bài giảng ở FE: **TipTap** (user chọn theo đề xuất, 2026-11-25), thay cho CKEditor 5 vì giấy phép.

**Lý do:** mục khóa có nhiều loại nội dung, tiến độ và video không thuộc ngữ cảnh làm bài/chấm điểm của Assessment; tách module giữ ranh giới R4 rõ (ghi dữ liệu module khác chỉ qua Action).
