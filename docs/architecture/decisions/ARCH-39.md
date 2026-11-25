# ARCH-39 — Module `CourseContent` và chuyển `CourseItem` khỏi Assessment

Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái:** ĐÃ CHỐT (user duyệt 2026-11-25); chưa làm ở BE (lát 1 trong `superpowers/specs/2026-11-25-course-content-design.md`).

**Bối cảnh:** `DEC-LRN-052` thêm bài giảng, tài liệu, video và tiến độ vào khóa. `ARCH-38` giao `CourseItem` cho Assessment vì khi đó mục chỉ trỏ tới bài, và đã ghi sẵn "cân nhắc tách module Course Content" khi làm `B-062`.

**Quyết định:**
- Thêm module thứ 13 **`CourseContent`** (thêm vào `MODULES` của `ArchitectureTest`, dòng mới trong `model-ownership.md`).
- `CourseItem` chuyển chủ ghi từ Assessment sang CourseContent, làm bằng một PR riêng đầu tiên, không đổi hành vi. Assessment chỉ còn đọc (liệt kê bài của khóa, tính tiến độ mục bài đánh giá).
- CourseContent sở hữu `LearningContent`, `ContentVideo`, `ContentAudience`, `VideoCheckpoint`, `ContentProgress`, `CheckpointResponse`. `Course` và `CourseAudience` vẫn của Academic; cài đặt học của khóa (`sequential_unlock`, `completion_rule`, `completion_min_score`) ghi qua Action của Academic.
- Thư viện soạn bài giảng ở FE: **TipTap** (user chọn theo đề xuất, 2026-11-25), thay cho CKEditor 5 vì giấy phép.

**Lý do:** mục khóa có nhiều loại nội dung, tiến độ và video không thuộc ngữ cảnh làm bài/chấm điểm của Assessment; tách module giữ ranh giới R4 rõ (ghi dữ liệu module khác chỉ qua Action).
