# Chặn tải tệp đề của bài đang khóa tuần tự (`B-113`) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Học sinh không tải được tệp đính kèm của đề và câu hỏi khi mọi bài chứa tệp đó đang bị khóa tuần tự.

**Architecture:** Dùng lại đúng luật "được xem bài" của màn chi tiết (`LearnerAssessmentLock`): bị khóa khi mục đứng trước chưa xong **và** chưa có lượt làm nào chưa hủy. `AttachmentPolicy` áp luật này cho vai Học sinh trên các liên kết tệp của đề và câu hỏi.

**Tech Stack:** Laravel 13, PHPUnit.

**Spec:** `docs/requirements/decision-log.md` `DEC-LRN-054`, `DEC-LRN-055`; `docs/superpowers/specs/2026-11-25-course-content-design.md` mục 4.

## Global Constraints

- Không migration, không đổi response thành công. Lỗi khóa: 403 `CONTENT_LOCKED` kèm `details.blocked_by {item_id, title}`.
- Còn một bài mở được chứa tệp thì cho tải. Phụ huynh, giáo viên, Admin không đổi.
- Không lộ gì cho người vốn không thấy tệp: vẫn 404 `Request failed.` (gồm liên kết `private`).
- Comment, mô tả test tiếng Việt. Chỉ chạy test `File`, `CourseContent`, `Architecture` và thư mục có file bị sửa.

## Review Focus

1. Đang có lượt làm dở trên bài vừa bị khóa lại: tệp vẫn phải tải được (màn làm bài cần).
2. Câu hỏi dùng chung nhiều bài: chỉ chặn khi mọi bài đều khóa, bất kể thứ tự duyệt.
3. Học sinh đã có dòng giao bài (chưa làm lượt nào) không được lách qua đường "bài đã giao".
4. Lượt làm của người khác hoặc của bài khác không mở được tệp.
5. Người ngoài khóa không biết tệp hay mục chặn có tồn tại.

---

### Task 1: Chặn ở `AttachmentPolicy`

**Files:**
- Modify: `app/Modules/CourseContent/Queries/LearnerAssessmentLock.php`, `app/Modules/File/Queries/AssessmentFileAccessQuery.php`, `app/Modules/File/Policies/AttachmentPolicy.php`
- Test: `tests/Feature/CourseContent/LockedAssessmentFilesTest.php`

**Interfaces:**
- `LearnerAssessmentLock::viewBlockedBy(Assessment $assessment, Student $student): ?array{item_id: string, title: string}` (null = được xem); `assertViewable` dùng hàm này.
- `AssessmentFileAccessQuery::assessmentsFor(string $studentId, AttachmentLink $link): Collection<int, Assessment>` (hợp của đường "bài đã giao" và "bài của khóa", đã nạp `course`, bài không cần xét khóa đứng trước).

- [ ] **Step 1:** Viết test theo Review Focus và Global Constraints, cho cả tệp câu hỏi và tệp đề. Chạy: Expected FAIL (200 thay vì 403).
- [ ] **Step 2:** Hiện thực.
- [ ] **Step 3:** Chạy `tests/Feature/File tests/Feature/CourseContent tests/Feature/Assessment tests/Architecture`, Pint, PHPStan file đã sửa. Expected PASS. Commit.
- [ ] **Step 4:** Thử đột biến trên bản sao trong container: bỏ điều kiện khóa; đổi "còn một bài mở" thành "bài đầu tiên mở"; áp khóa cho cả Phụ huynh. Mỗi đột biến phải làm test đỏ.
- [ ] **Step 5:** Cập nhật `error-codes.md`, `DEC-LRN-055`, `BACKLOG.md`, `STATE.md`; review độc lập `model: "opus"`.
