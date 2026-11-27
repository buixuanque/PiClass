# Khóa học có nội dung học, lát 4b: tiến độ khóa cho Giáo viên, Admin, Phụ huynh (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giáo viên chủ khóa và Admin xem bảng tiến độ của cả khóa (từng học viên × từng mục, hoàn thành khóa, thống kê lượt trả lời đầu của điểm dừng); Phụ huynh xem tiến độ từng mục của con trong một khóa, không có tệp hay video.

**Architecture:** Không thêm bảng. Dựng trên `CourseItemStatusesQuery` và `MyCourseViewQuery` của lát 4a: tách thành bản tính cho nhiều học sinh một lượt (không N+1), rồi hai endpoint mới đọc từ đó. Phần điểm trung bình dùng `StudentRepresentativeResultsQuery` (thêm bản nhiều học sinh). Quyền đi qua `CoursePolicy` (`manage` cho Giáo viên/Admin) và `StudentPolicy::viewAsParent` + học viên của khóa (Phụ huynh).

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL; chạy lệnh trong `piclass-infra/local` bằng `docker compose exec -T api ...`.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 3 `content_progress`, `checkpoint_responses`; mục 4 điều 3, 5; mục 7 "Tiến độ khóa" và "Phụ huynh"; mục 8; mục 14 ghi chú lát 4a). Quyết định: `DEC-LRN-052` mục 5, 10; `DEC-LRN-053` mục 1 (TC-19, AD-12, chi tiết khóa của Phụ huynh) và mục 3 (lượt đầu làm kết quả điểm dừng, cho thống kê TC-19); `DEC-LRN-047`, `048`, `049` (Phụ huynh xem như học sinh, chỉ không làm bài). Quy ước code: `piclass-api/CLAUDE.md`, `docs/architecture/backend-gotchas.md`. **Ngoài lát này:** FE (lát 5..8), `B-108` (khóa tuần tự cho `/me/assessments*`, chờ user quyết), `GET /me/courses` (danh sách), tệp và video cho Phụ huynh (không làm, `DEC-LRN-052` mục 10).

## Global Constraints

- Nhánh `feat/course-content-progress` của `piclass-api` (đã cắt từ `origin/main`). Không commit thẳng `main`. Chỉ đọc: lát này không ghi bảng nào, không migration.
- **Giáo viên/Admin:** `GET /courses/{course}/progress`, nhóm route `role:admin,teacher`, `ManageCourseRequest` (`CoursePolicy::manage`): Admin; Giáo viên chủ khóa. Giáo viên khác 403, khóa dùng chung (`owner_teacher_id` null) chỉ Admin (đúng như các endpoint quản lý khóa hiện có). Học sinh, Phụ huynh bị chặn ở route.
- **Phụ huynh:** `GET /me/children/{studentId}/courses/{course}/progress`, nhóm `role:parent`, `->whereUuid('studentId')`. **Khác spec mục 7** (`/students/{id}/courses/{courseId}/progress`): các endpoint Phụ huynh hiện có đều dưới `/me/children/{studentId}/...` nên theo quy ước đó; ghi vào spec mục 14 và OpenAPI. Quyền: `Gate::inspect('viewAsParent', [Student::class, $studentId])` (con không thuộc phụ huynh → 404), rồi `CourseLearnersQuery::isLearner($course->id, $studentId)` (con không phải học viên của khóa → 404 `DenialMessage::NOT_FOUND`, không phân biệt với khóa không tồn tại). Khóa `archived` vẫn xem được (BE không lọc, như `GET /courses`).
- **Đối tượng tiến độ = học viên của khóa** (`CourseLearnersQuery::handle`, nguồn trực tiếp + lớp đang hoạt động, DEC-LRN-026). Học sinh đã rời lớp/khóa không còn trong bảng (dữ liệu `content_progress` giữ nguyên, không xóa).
- **Mục nào đếm:** cùng luật lát 4a (`CourseItemStatusesQuery`): chương không đếm; bài đánh giá không phải `published`/`closed` không đếm (Giáo viên vẫn thấy mục trong danh sách `items` với `counts=false`, Phụ huynh không thấy, như học sinh). Trạng thái ô: `not_started` | `in_progress` | `completed`. Bài đánh giá: `completed` khi có bài nộp chưa `invalidated` (nộp trễ vẫn tính).
- **Điểm trung bình** quy về thang 10 như `MyCourseViewQuery` (trung bình `percentage`/10, chỉ kết quả đã công bố và chấm xong của các bài đang mở của khóa); `is_complete` qua `CourseCompletion::isComplete` (không viết lại luật). Một nguồn sự thật cho số của học sinh và của Giáo viên: test đối chiếu hai đường.
- **Không N+1:** số truy vấn của `GET /courses/{id}/progress` không phụ thuộc số học viên (test đếm truy vấn với 2 và 8 học viên cho cùng số truy vấn).
- **Không lộ:** Phụ huynh không nhận `content_id` của video để xin link phát (route phát đã chặn Phụ huynh); resource Phụ huynh không có đường dẫn tệp, link, nội dung bài giảng, đáp án điểm dừng. Giáo viên cũng không nhận đáp án câu trả lời của học sinh (chỉ số lượt đầu đúng/sai, không `first_answer`).
- Hằng số/enum: không literal trùng giá trị enum (R8); thời gian qua `now()`/`AppTime` (R9..R11). `ArchitectureTest` xanh; enum module khác dùng thêm khai vào `SHARED_ENUMS['CourseContent']`.
- Mã lỗi mới: không (dùng 403 chung và 404 `NOT_FOUND`). Tên test method, mã lỗi, commit message tiếng Anh; comment và mô tả test tiếng Việt.
- TDD: test đỏ trước rồi mới xanh. Cuối plan: cả bộ test chạy một mình (không chạy song song, sẽ deadlock DB test; kiểm `ps aux | grep artisan` trước), Pint (file đã đổi), PHPStan (không thêm lỗi so với baseline 30), xuất OpenAPI, cập nhật `docs/architecture/model-ownership.md` nếu thêm class đáng ghi (không có model mới).

## Review Focus

1. **Học sinh ở nhiều nguồn:** học viên có cả dòng trực tiếp lẫn qua lớp, hoặc qua hai lớp, chỉ một hàng trong bảng (`CourseLearnersQuery` đã gộp; test khóa lại).
2. **Rời lớp giữa chừng:** học sinh đã có `content_progress` rồi bị kết thúc thành viên lớp không còn hàng trong bảng và không lọt vào thống kê điểm dừng (thống kê chỉ đếm học viên hiện tại).
3. **Mục đổi sau khi có tiến độ:** bài đánh giá bị hủy/lưu trữ hoặc mục bị gỡ sau khi học sinh đã học không làm sai số `done`/`total` của hàng (mục đã gỡ không còn trong `items`; không đếm tiến độ mồ côi).
4. **Khóa rỗng / chưa có học viên:** trả 200 với `learners: []`, `summary` toàn 0, `is_complete=false` cho khóa không có mục đếm được; không chia cho 0.
5. **Phụ huynh đoán id:** con của phụ huynh khác, khóa con không học, khóa không tồn tại đều cùng 404 (không phân biệt được); phụ huynh đã hết liên kết (`effective()` sai) cũng 404.

---

## File Structure

| Việc | File |
|---|---|
| Tính trạng thái nhiều học sinh một lượt | Sửa `app/Modules/CourseContent/Queries/CourseItemStatusesQuery.php` (thêm `forStudents`, `handle` gọi lại nó) |
| Kết quả đại diện nhiều học sinh | Sửa `app/Modules/ResultProgress/Queries/StudentRepresentativeResultsQuery.php` (thêm `handleForStudents`) |
| Tiến độ khóa cho Giáo viên/Admin | Tạo `Queries/CourseProgressQuery.php`, `Http/Controllers/GetCourseProgressController.php`, `Http/Resources/CourseProgressResource.php` |
| Tiến độ khóa cho Phụ huynh | Tạo `Http/Controllers/GetChildCourseProgressController.php`, `Http/Requests/ViewChildCourseProgressRequest.php`, `Http/Resources/ChildCourseProgressResource.php`; dùng lại `MyCourseViewQuery` |
| Route | Sửa `routes/api.php` |
| Test | `tests/Feature/CourseContent/CourseProgressTest.php`, `ChildCourseProgressTest.php`; `tests/Unit/...` cho `forStudents` nếu cần |
| Docs | Sửa spec mục 14 (ghi lát 4b), `docs/STATE.md`, `docs/BACKLOG.md` nếu phát sinh nợ |

(Mọi đường dẫn BE tính từ `piclass-api/`; `CourseContent` = `app/Modules/CourseContent`.)

---

### Task 1: Tính trạng thái và điểm cho nhiều học sinh một lượt

**Files:**
- Modify: `app/Modules/CourseContent/Queries/CourseItemStatusesQuery.php`
- Modify: `app/Modules/ResultProgress/Queries/StudentRepresentativeResultsQuery.php`
- Test: `tests/Feature/CourseContent/CourseItemStatusesTest.php` (thêm ca), `tests/Feature/ResultProgress/` (thêm ca cho bản nhiều học sinh; tìm test hiện có của `StudentRepresentativeResultsQuery` để đặt cạnh)

**Interfaces:**
- Consumes: `SequentialUnlock::evaluate`, `Course::items()`, `CourseItem::DISPLAY_RELATIONS`, `ContentProgress`, `Submission` (join như hiện tại).
- Produces:
  - `CourseItemStatusesQuery::forStudents(Course $course, array $studentIds): array` trả `array<string, list<row>>` khóa theo `student_id`, mỗi `row` đúng shape hiện tại (`item`, `counts`, `status`, `done`, `locked`, `blocked_by`); học sinh không có tiến độ vẫn có đủ các hàng `not_started`. Danh sách `items` của khóa tải **một lần** dùng chung.
  - `CourseItemStatusesQuery::handle(Course, Student)` giữ nguyên chữ ký và kết quả, gọi `forStudents($course, [$student->id])[$student->id]`.
  - `StudentRepresentativeResultsQuery::handleForStudents(array $studentIds, array $filters = []): Collection` trả `Collection<string, Collection<int, Result>>` khóa theo `student_id`, cùng luật chọn Result đại diện với `handle` (`handle($id, $f)` phải bằng `handleForStudents([$id], $f)[$id] ?? collect()` về tập Result). `baseQuery` nhận thêm biến thể `whereIn` hoặc tách phần lọc dùng chung, không nhân đôi luật lọc.

- [ ] **Step 1: Test đỏ cho `forStudents`** — hai học sinh trong cùng khóa có tiến độ khác nhau (một đã `completed` mục 1, một chưa học gì) với `sequential_unlock` bật: `forStudents($course, [$a->id, $b->id])` trả khóa đúng hai id; hàng của A có `done=true` ở mục 1 và `locked=false` ở mục 2; hàng của B có `locked=true` ở mục 2 và `blocked_by.item_id` là mục 1. Thêm ca bằng nhau: với từng học sinh, `forStudents(...)[$id]` bằng `handle($course, $student)`.
- [ ] **Step 2: Test đỏ cho `handleForStudents`** — hai học sinh có Result đã công bố ở cùng bài nhưng khác lượt; policy `highest` và `latest` đều chọn đúng một Result đại diện cho mỗi học sinh; Result chưa công bố và bài bị vô hiệu bị loại như `handle`; học sinh không có kết quả không có khóa trong kết quả hoặc trả `Collection` rỗng (chọn một, ghi trong docblock, test khẳng định).
- [ ] **Step 3: Chạy hai nhóm test, xác nhận đỏ** (`php artisan test --filter=CourseItemStatusesTest`, nhóm ResultProgress tương ứng).
- [ ] **Step 4: Cài `StudentRepresentativeResultsQuery::handleForStudents`** — tách phần lọc của `baseQuery` thành hàm dùng chung theo tập `student_id` (một truy vấn, `whereIn`), nhóm theo `student_id` rồi áp đúng luật `highest/latest` hiện tại; `handle` giữ chữ ký, gọi bản mới hoặc giữ nguyên nếu tách sạch hơn. Không đổi kết quả của `handle` (toàn bộ test ResultProgress hiện có phải vẫn xanh).
- [ ] **Step 5: Cài `CourseItemStatusesQuery::forStudents`** — `whereIn('student_id', $studentIds)` cho `ContentProgress` và `student_assessments`, nhóm theo học sinh (`pluck`/`groupBy`), tính `counts` một lần cho cả khóa, `SequentialUnlock::evaluate` mỗi học sinh một lần (thuần, không truy vấn). `handle` ủy quyền.
- [ ] **Step 6: Chạy lại test đã đỏ, rồi `--filter=MyCourseView` và nhóm ResultProgress, xác nhận xanh** (hành vi lát 4a không đổi).
- [ ] **Step 7: Commit** `refactor: compute course item statuses and representative results for many students`

---

### Task 2: Tiến độ khóa cho Giáo viên và Admin

**Files:**
- Create: `app/Modules/CourseContent/Queries/CourseProgressQuery.php`
- Create: `app/Modules/CourseContent/Http/Controllers/GetCourseProgressController.php`
- Create: `app/Modules/CourseContent/Http/Resources/CourseProgressResource.php`
- Modify: `routes/api.php` (trong nhóm `role:admin,teacher`, cạnh `GET /courses/{course}/learners`)
- Test: `tests/Feature/CourseContent/CourseProgressTest.php`

**Interfaces:**
- Consumes: Task 1 (`CourseItemStatusesQuery::forStudents`, `StudentRepresentativeResultsQuery::handleForStudents`), `CourseLearnersQuery::handle(Course)` (hàng `{learner: Student, sources}`), `CourseCompletion`, `ManageCourseRequest`, `CheckpointResponse`, `VideoCheckpoint`.
- Produces: `CourseProgressQuery::handle(Course $course): array{items: list<array>, learners: list<array>, summary: array}` và route `GET /courses/{course}/progress` → `{ data: { course, items, learners, summary } }`:
  - `course`: `{id, name, sequential_unlock, completion_rule, completion_min_score}` (điểm tối thiểu thang 10, cùng kiểu số của `MyCourseResource`).
  - `items`: mọi mục theo `position` (kể cả chương và bài đánh giá chưa mở), mỗi mục `{id, position, type, title, content_id, assessment_id, counts, completed_count, checkpoints}`. `completed_count` = số học viên hiện tại đã `completed` mục đó (0 cho chương và mục không đếm). `checkpoints` chỉ có cho mục video tự lưu hoặc link ngoài có điểm dừng, các mục khác `[]`; mỗi điểm dừng `{id, at_seconds, needs_recheck, answered_count, first_try_correct_count}` (đếm trên học viên hiện tại: `answered_count` = có dòng `checkpoint_responses`, `first_try_correct_count` = `first_is_correct`).
  - `learners`: sắp theo tên (thứ tự của `CourseLearnersQuery`), mỗi hàng `{student_id, student_name, sources, done, total, is_complete, average_score, last_activity_at, statuses}`; `statuses` là map `item_id → 'not_started'|'in_progress'|'completed'` **chỉ cho mục đếm được**; `last_activity_at` = `max(content_progress.updated_at)` của học sinh trong các nội dung của khóa (null khi chưa học gì; bài đánh giá không tính).
  - `summary`: `{learner_count, completed_count, completion_rate}`; `completion_rate` = `completed_count / learner_count` (0 khi không có học viên).

- [ ] **Step 1: Test đỏ — phân quyền** (`CourseProgressTest`, fixture đủ "người khác" theo `backend-gotchas.md`): chủ khóa 200; Admin 200 (cả khóa dùng chung); Giáo viên không phải chủ 403; Giáo viên với khóa dùng chung 403; Giáo viên chưa có hồ sơ 403; học sinh là học viên của khóa, Phụ huynh có con trong khóa, vai rỗng đều bị chặn ở route; khóa không tồn tại 404. Mỗi ca khẳng định body của ca bị chặn không chứa tên học viên (không lộ).
- [ ] **Step 2: Test đỏ — nội dung bảng:** khóa gồm một chương, một bài giảng, một video có hai điểm dừng, một bài đánh giá `published`, một bài đánh giá `draft`; ba học viên (một trực tiếp, một qua lớp, một có cả hai nguồn). Học viên A xong hết (bài giảng bấm xong, video xong, đã nộp bài), B chỉ mở bài giảng (`in_progress`), C chưa học. Khẳng định: `items` có 5 mục theo `position`, chương và `draft` có `counts=false`; `learners` có 3 hàng (học viên hai nguồn chỉ một hàng, `sources` có cả hai); A `done=total`, `is_complete=true`; B `done=0`, `statuses` bài giảng `in_progress`, `last_activity_at` khác null; C `last_activity_at=null`; `statuses` không có khóa chương hay mục `draft`; `completed_count` của bài giảng = 1; `summary.completed_count=1`, `learner_count=3`.
- [ ] **Step 3: Test đỏ — thống kê điểm dừng:** A trả lời đúng ngay lượt đầu, B trả lời sai rồi đúng (lượt đầu sai), C chưa trả lời. Điểm dừng 1: `answered_count=2`, `first_try_correct_count=1`; JSON không có `first_answer` hay đáp án nào. Học sinh đã rời lớp (kết thúc thành viên) có `checkpoint_responses` không được đếm.
- [ ] **Step 4: Test đỏ — điểm và hoàn thành:** khóa luật `all_items_and_min_score` với ngưỡng 7: A đã xong mọi mục, điểm trung bình thang 10 ≥ 7 → `is_complete=true`; D xong mọi mục nhưng điểm 6 → `is_complete=false`; `average_score` của từng học viên bằng đúng `MyCourseViewQuery->handle($course, $student)['average_score']` của học viên đó (đối chiếu hai đường, một nguồn sự thật).
- [ ] **Step 5: Test đỏ — số truy vấn và biên:** (a) `DB::getQueryLog()` cho khóa 2 học viên và khóa 8 học viên cho **cùng số truy vấn**. (b) Khóa không có học viên: 200, `learners=[]`, `summary` toàn 0. (c) Khóa chỉ có chương: mọi `is_complete=false`. (d) Bài đánh giá bị hủy sau khi học viên đã nộp: bài đó không đếm vào `done`/`total` (khớp `MyCourseViewQuery`). (e) Mục bị gỡ sau khi học sinh có tiến độ: không còn trong `items` và `statuses`.
- [ ] **Step 6: Chạy `--filter=CourseProgressTest`, xác nhận đỏ đúng lý do** (route 404, chưa phải lỗi cú pháp).
- [ ] **Step 7: Cài `CourseProgressQuery::handle`** — lấy học viên bằng `CourseLearnersQuery::handle`; `forStudents` và `handleForStudents` đúng một lần cho cả tập; một truy vấn `max(updated_at)` nhóm theo học sinh trên `content_progress` (chỉ `content_id` của khóa); một truy vấn thống kê `checkpoint_responses` nhóm theo `checkpoint_id` lọc `student_id` thuộc học viên hiện tại và `checkpoint_id` thuộc video của khóa (`whereIn`, không lặp theo điểm dừng); điểm trung bình theo cùng công thức `MyCourseViewQuery::averageScore` (tách hàm dùng chung nếu cần, không chép công thức); `is_complete` qua `CourseCompletion`. Không chạy `MyCourseViewQuery` lặp theo học sinh.
- [ ] **Step 8: Cài controller + resource + route** (`ManageCourseRequest`, nhóm `role:admin,teacher`). Resource thuần định dạng, không truy vấn.
- [ ] **Step 9: Chạy lại `CourseProgressTest`, rồi `--filter=CourseContent` và `ArchitectureTest`, xác nhận xanh.**
- [ ] **Step 10: Commit** `feat: course progress table for teachers and admins`

---

### Task 3: Tiến độ khóa của con cho Phụ huynh

**Files:**
- Create: `app/Modules/CourseContent/Http/Controllers/GetChildCourseProgressController.php`
- Create: `app/Modules/CourseContent/Http/Requests/ViewChildCourseProgressRequest.php`
- Create: `app/Modules/CourseContent/Http/Resources/ChildCourseProgressResource.php`
- Modify: `routes/api.php` (nhóm `role:parent`)
- Test: `tests/Feature/CourseContent/ChildCourseProgressTest.php`

**Interfaces:**
- Consumes: `MyCourseViewQuery::handle(Course, Student)`, `StudentPolicy::viewAsParent`, `CourseLearnersQuery::isLearner`, `Student` (tìm theo `studentId` sau khi qua quyền).
- Produces: route `GET /me/children/{studentId}/courses/{course}/progress` → `{ data: { id, name, completion_rule, completion_min_score, sequential_unlock, items: [{id, position, type, title, status, locked, blocked_by}], completion: {complete, done, total, average_score} } }`. **Không có** `content_id` hay `assessment_id` cho mục không phải bài đánh giá; `assessment_id` giữ cho bài đánh giá để FE dẫn sang `/parent/assessments/{id}` (`DEC-LRN-049`). Chương hiện để giữ thứ tự, không đếm.

- [ ] **Step 1: Test đỏ — phân quyền:** phụ huynh có con là học viên của khóa 200; phụ huynh của con khác (có dữ liệu thật ở khóa đó) 404; con có liên kết đã kết thúc (`effective()` false) 404; con không học khóa này 404; khóa không tồn tại 404 (cùng body với các ca trên); phụ huynh không có hồ sơ 403; Giáo viên, học sinh, Admin ở route này 403 (route `role:parent`); `studentId` không phải UUID 404.
- [ ] **Step 2: Test đỏ — nội dung:** cùng fixture Task 2; trạng thái của con khớp đúng `MyCourseViewQuery` (so sánh với đường học sinh `GET /me/courses/{course}` của chính con, mỗi mục `status`/`locked`/`blocked_by` bằng nhau, `completion` bằng nhau). Bài đánh giá `draft` không hiện; JSON không có `content_id` của bài giảng/video/tài liệu, không có chuỗi `storage_key`, `url`, `body`, `html`, `external`; con xong khóa `completion.complete=true`.
- [ ] **Step 3: Test đỏ — không rò thông tin con khác:** phụ huynh có hai con, chỉ một con học khóa; truy vấn con kia 404; kết quả của con thứ nhất không chứa dữ liệu của con thứ hai (đổi fixture để hai con có tiến độ khác nhau, khẳng định số khớp đúng con được hỏi).
- [ ] **Step 4: Chạy `--filter=ChildCourseProgressTest`, xác nhận đỏ.**
- [ ] **Step 5: Cài request** (`authorize`: `Gate::inspect('viewAsParent', [Student::class, $this->route('studentId')])`, sau đó kiểm `isLearner` → `Response::denyAsNotFound(DenialMessage::NOT_FOUND)`; thứ tự: phụ huynh-con trước, học viên của khóa sau, để hai ca đều 404 cùng message), controller (`Student::findOrFail` sau quyền), resource (shape trên, không nhận `Course` quan hệ nặng), route.
- [ ] **Step 6: Chạy lại test, rồi nhóm `CourseContent`, `ArchitectureTest`, xác nhận xanh.**
- [ ] **Step 7: Commit** `feat: child course progress for parents`

---

### Task 4: OpenAPI, tài liệu, kiểm toàn nhánh

**Files:**
- Modify: docblock/attribute OpenAPI của hai controller mới theo mẫu của controller cùng module; `piclass-api/storage/api-docs/openapi.json` (xuất lại)
- Modify (docs repo): `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 7 sửa đường dẫn Phụ huynh, mục 14 ghi lát 4b), `docs/STATE.md`, `docs/BACKLOG.md` (nợ phát sinh nếu có)

**Interfaces:**
- Consumes: Task 2, Task 3.
- Produces: OpenAPI mô tả hai endpoint với đủ schema (items/learners/summary, ChildCourseProgress); FE lát 8 đọc `openapi.json` để gọi.

- [ ] **Step 1: Thêm mô tả OpenAPI cho hai controller** (mẫu: `GetMyCourseController` và các controller Phụ huynh hiện có); xuất lại bằng lệnh của repo (xem `piclass-api/CLAUDE.md`), `git diff --stat` chỉ chứa phần của hai endpoint.
- [ ] **Step 2: Cả bộ test, chạy một mình** (`ps aux | grep artisan` rỗng trước khi chạy; kết quả chỉ được phép còn ca hay chập chờn `TeacherGradingDemoSeederTest`, `B-086`), Pint file đã đổi, PHPStan không thêm lỗi so với baseline 30.
- [ ] **Step 3: Review độc lập cuối nhánh** (subagent `model: "opus"`, theo `docs/process/working-flow.md`): kiểm phân quyền (đột biến: bỏ `manage`, đổi thứ tự early-return, bỏ kiểm `isLearner`), rò dữ liệu của Phụ huynh, N+1, thống kê điểm dừng có đếm học sinh đã rời không.
- [ ] **Step 4: Cập nhật docs** — spec mục 7 (đường dẫn Phụ huynh theo `/me/children/{studentId}/courses/{course}/progress`), mục 14 (ghi chú lát 4b: shape, quyết định N+1, nợ); `STATE.md` (lát 4b BE xong, kế tiếp: FE lát 5..8 hoặc quyết `B-108`); nợ mới vào `BACKLOG.md` nếu có. Chưa mở PR cho đến khi user nói rõ.
- [ ] **Step 5: Commit** `docs: course content slice 4b notes and openapi`

---

## Self-Review

1. **Spec coverage:** mục 7 "Tiến độ khóa (Giáo viên chủ khóa, Admin)" → Task 2; "Phụ huynh: tiến độ của con, không có endpoint nội dung" → Task 3 (đường dẫn đổi theo quy ước, ghi ở Global Constraints và Task 4); `DEC-LRN-053` mục 3 thống kê lượt đầu cho TC-19 → Task 2 Step 3; điều 5 hoàn thành khóa → Task 2 Step 4 qua `CourseCompletion`; mục 8 phân quyền (giáo viên khác, phụ huynh, vai rỗng, đoán id) → Step 1 của Task 2 và 3.
2. **Step scan:** mọi bước test nêu fixture và khẳng định; bước cài nêu nguồn dùng lại, không chép thân hàm.
3. **Type consistency:** `forStudents(Course, array): array<string, list<row>>` dùng ở Task 2; `handleForStudents(array, array): Collection<string, Collection<Result>>` dùng ở Task 2; `CourseProgressQuery::handle(Course)` → `CourseProgressResource`; `ChildCourseProgressResource` dùng shape `MyCourseViewQuery` của lát 4a.
4. **Review Focus:** năm dòng đều có test (1 → Task 2 Step 2; 2 → Step 3; 3 → Step 5 d/e; 4 → Step 5 b/c; 5 → Task 3 Step 1).
5. **Proportion:** bốn task, mỗi task có độ lớn một vòng review; không có thân hàm trong plan.
