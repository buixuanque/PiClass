# Plan: khóa tuần tự chặn bài đánh giá ở server (`B-108`, `DEC-LRN-054`)

Repo: `piclass-api`. Không đổi schema, không đổi route.

## Phạm vi chặn (mục bài đánh giá còn khóa → 403 `CONTENT_LOCKED` kèm `details.blocked_by`)

| Endpoint | Hành vi |
| --- | --- |
| `POST /me/assessments/{id}/attempts` | Chặn tạo lượt **mới**. Đang có lượt dở thì trả lại lượt đó như cũ. |
| `GET /me/assessments/{id}` | Chặn khi học sinh **chưa có lượt nào** (không tính lượt đã hủy). Đã có lượt thì vẫn xem được, để còn "Làm tiếp" và vào kết quả. |
| Lưu câu trả lời, nộp, xem lượt, xem kết quả, danh sách `GET /me/assessments`, Phụ huynh | Không đổi. |

Thứ tự ở `StartAttempt`: quyền (404/403 như cũ) → bài giao còn hiệu lực → bài đã phát hành → trả lượt dở → **khóa tuần tự** → giờ mở/đóng → hết lượt.

## Thiết kế

- `CourseContent\Queries\LearnerAssessmentLock`: `assertStartable(Assessment, Student)` và `assertViewable(Assessment, Student)`, dựa trên `CourseItemStatusesQuery` (cùng một luật với `GET /me/courses/{course}`). Bài cá nhân, khóa không bật tuần tự, bài không còn mục trong khóa: không khóa.
- `StartAttempt` (SubmissionGrading) gọi `assertStartable` trong transaction (dòng giao bài tạo lười được rollback khi bị chặn).
- `GetMyAssessmentController` gọi `assertViewable` sau khi `find` đã qua cửa 404. Đây là ngoại lệ thứ hai của quy tắc "Assessment không import Course Content" (`ArchitectureTest`, `module-boundaries.md`).

## Task

1. Test đỏ `tests/Feature/CourseContent/LockedCourseAssessmentTest.php`.
2. Query, nối vào Action và controller, sửa `ArchitectureTest`, test xanh.
3. OpenAPI export, Pint, PHPStan, cả bộ test.
4. Tài liệu: `module-boundaries.md`, `error-codes.md`, spec khóa học, `DEC-LRN-054`, `BACKLOG.md`, `STATE.md`.
5. Review độc lập cuối nhánh (opus).

## Kết quả

Task 1..5 xong. Cả bộ test xanh (3104) trước vòng sửa sau review; sau vòng sửa chạy lại các nhóm liên quan (1001). PHPStan còn 30 lỗi có sẵn như `origin/main`; bỏ một mục baseline đã hết khớp. Review opus: không Critical; hai lỗ hổng test (lọc theo học sinh và theo bài) đã bịt, kiểm bằng đột biến. Phát hiện có từ trước: `B-113..B-115`.
