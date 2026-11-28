# Khóa học có nội dung học, lát 7 (Học sinh: ST-09..ST-12) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Học sinh xem khóa của mình, học bài giảng, tài liệu, video có điểm dừng câu hỏi, học bài giảng được giao, và luôn biết phải làm gì khi một mục còn khóa.

**Architecture:** BE bổ sung trước trong một PR riêng (Task 0). FE đặt màn học vào `features/course-content` (`api/` cho `/me/*`, `ui/student/` cho màn, `ui/shared/` cho trình phát và trình xem bài có sẵn). Luật "dừng ở đâu" của video là hàm thuần trong `api/`. `academic` chỉ bọc màn chi tiết khóa để truyền mô tả và tên môn. Phụ thuộc mới một chiều: `course-content → grading` (dùng lại ô trả lời của màn làm bài; `grading` chỉ phụ thuộc `files`, `results` nên không tạo vòng).

**Tech Stack:** Laravel 13; Next.js 16.4.6, React 19, TanStack Query, Vitest + Testing Library + MSW. Không thêm thư viện.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 4, 5, 8, 14, 18); `DEC-LRN-052..055`, `DEC-LRN-057`. Mockup: `piclass-mockup/design-system/PiClass Học sinh.dc.html` dòng 496–508 (ST-12), 511–558 (ST-09), 560–640 (ST-10, ST-11), 748–779 và 932–966 (hành vi).

## Global Constraints

- Task 0: repo `piclass-api`, nhánh `feat/course-content-student-api`. Task 1..5: repo `piclass-web`, nhánh `feat/course-content-student`. Cả hai từ `origin/main` sau `git fetch`. Đọc `CLAUDE.md` của repo trước khi sửa; web đọc thêm `docs/rules/design-system.md`, `docs/rules/testing.md`, `docs/rules/pitfalls.md`.
- Mọi quyền và khóa tuần tự do BE quyết (F7). FE chỉ hiện theo `locked`, `blocked_by`, `status` BE trả. Không tự tính mục nào mở.
- Không gửi hay hiện đáp án đúng. `POST /checkpoints/{id}/answers` chỉ trả `{is_correct, can_continue}`; trả lời sai được thử lại không giới hạn (`DEC-LRN-053`).
- Định dạng `answer` theo loại câu, giống bài đánh giá: chọn (kể cả đúng/sai) là mảng id; điền khuyết là mảng chuỗi theo chỗ trống; nối là map `trái → phải`; sắp xếp là mảng id.
- Video xong khi vị trí ≥ 95% thời lượng và mọi điểm dừng đã đúng; link ngoài cần học sinh gửi `completed=true`. BE tính; FE chỉ gửi vị trí.
- Không dùng `dangerouslySetInnerHTML`; bài giảng hiện bằng `LectureViewer`.
- Port đúng từng giá trị style, bố cục và chữ từ mockup. Dòng "Bản mẫu …", "Bản mẫu chạy nhanh gấp 4 lần", khung "xem trước tài liệu (bản mẫu)" **không** port.
- F1..F8; mỗi file một component chính, khoảng 200 dòng. Chữ giao diện tiếng Việt; lỗi 4xx của BE hiện nguyên văn; rẽ nhánh theo `error.code`.
- Chỉ chạy test đúng phạm vi task (test mới, test của feature/module có file bị sửa, test kiến trúc).

## Cần user chốt trước khi làm (plan viết theo đề xuất)

| # | Vấn đề | Đề xuất | Nếu chọn khác |
|---|---|---|---|
| Q1 | Mockup "Khóa học của em" có mô tả, môn, số mục. Chưa có `GET /me/courses`; "Ghi danh từ …" không còn dữ liệu từ `DEC-LRN-026` | BE thêm `GET /me/courses` kèm môn và số mục đã xong/tổng. Dòng ghi "{done}/{total} mục đã xong"; bỏ "Ghi danh từ" | Dùng `GET /courses` sẵn có: chỉ tên, mô tả, giáo viên; bỏ số mục |
| Q2 | Link YouTube/Vimeo: web không biết đang xem tới giây nào và không dừng được video | Hiện video nhúng, bên dưới là danh sách "Câu hỏi của video" (kèm mốc thời gian) để trả lời theo bất kỳ thứ tự; trả lời đúng hết mới bấm được "Đánh dấu đã xem xong" | Gắn SDK của YouTube và Vimeo để dừng đúng lúc: thêm script bên thứ ba, hai cách tích hợp riêng, nhiều việc hơn |
| Q3 | Điểm dừng nằm sau thời lượng (video bị thay bằng bản ngắn hơn) vẫn gửi cho học sinh, họ không bao giờ tới được nên không xong video (`B-111`) | Phía học sinh BE bỏ qua các điểm này: không gửi, không chặn tua, không tính vào hoàn thành. Giáo viên vẫn thấy để xóa | Giữ như cũ: học sinh kẹt tới khi giáo viên xóa |
| Q4 | Mockup có dòng phụ (thời lượng, số câu hỏi, dung lượng tệp) và "Cô Lan · Lớp 9A2" ở bài được giao; API chưa trả | BE bổ sung: mục khóa của học sinh thêm `video {duration_seconds, checkpoint_count}`, `file {media_type, size_bytes}`; bài được giao thêm `assigned_by`. "8 phút đọc" bỏ | Dòng phụ chỉ ghi loại mục; bỏ dòng người giao |

## Đã chọn thay mockup (user đổi được)

- Khung video là trình phát thật (điều khiển của trình duyệt); thanh có chấm điểm dừng nằm dưới khung như mockup, bấm để tua.
- Đang toàn màn hình mà tới điểm dừng: thoát toàn màn hình để hiện câu hỏi (lớp phủ không nằm được trên video toàn màn hình).
- Câu hỏi điểm dừng dùng ô trả lời của màn làm bài (đủ sáu loại; mockup chỉ vẽ chọn một đáp án).
- Giải thích sau khi đúng: chỉ "Đúng rồi." vì câu chưa có lời giải (`B-087`).
- Tài liệu: chỉ có "Tải xuống", không có khung xem trước.
- Bài giảng được giao mở màn học không có cột "Các mục", không có "Mục trước/sau".
- Mục đánh giá trong khóa mở màn chi tiết bài sẵn có (ST-05).
- Thẻ "Thông tin" bỏ dòng "Bắt đầu từ"; nhãn "Đang học" đổi thành "Đã hoàn thành" khi khóa xong.
- `B-114`: màn chi tiết bài khi bị khóa hiện "Bài này chưa mở. Em cần học xong «tên mục» trước." và nút "Vào khóa học". "Bài của em" không thêm dấu khóa (cờ `locked` chưa được chốt, `DEC-LRN-055`).
- Thêm mục menu "Bài giảng" cho Học sinh (mockup có).

## Review Focus

1. Đang xem video mà tiến độ được lưu hay vừa trả lời xong một câu: trình phát không bị dựng lại, không nhảy về đầu (lỗi từng gặp ở lát 6). → Task 4.
2. Vào lại video đang xem dở đúng ở giây của điểm dừng chưa trả lời: câu hỏi hiện ngay, không lọt qua. → Task 4.
3. Câu sắp xếp học sinh thấy thứ tự đúng rồi bấm "Kiểm tra" mà không đổi gì: phải gửi đúng thứ tự đang hiện, không gửi rỗng. → Task 4.
4. Mất mạng khi đang xem: video vẫn xem tiếp; vị trí được gửi lại ở lần lưu sau; xong video không bị mất vì một lần lưu lỗi. → Task 4.
5. Mở trang quá 10 phút rồi mới bấm "Tải xuống": link ký đã hết hạn, phải lấy link mới. → Task 3.

---

### Task 0: BE bổ sung cho phía Học sinh (`piclass-api`, PR riêng, đi trước; đụng phạm vi dữ liệu nên review opus)

**Files:**
- Create: `app/Modules/CourseContent/{Queries/ListMyCoursesQuery.php,Http/Controllers/ListMyCoursesController.php,Http/Requests/ListMyCoursesRequest.php,Http/Resources/MyCourseSummaryResource.php}`
- Modify: `routes/api.php`, `Queries/{MyContentDetailQuery,ListMyContentsQuery,MyCourseViewQuery,CourseItemStatusesQuery}.php`, `Actions/{UpdateContentProgress,AnswerCheckpoint}.php`, `Http/Resources/{MyCourseResource,MyContentSummaryResource,CheckpointAnswerResource}.php`, `Http/Requests/AnswerCheckpointRequest.php`, `docs/architecture/error-codes.md` nếu đổi
- Test: `tests/Feature/CourseContent/{MyCourseListTest,BeyondDurationCheckpointTest,MyCourseViewTest,MyContentReadTest}.php`

**Interfaces — Produces:**
- `GET /me/courses` (`role:student`) → `data: [{id, name, description, subject_name, teacher_name, done, total, complete}]`, theo tên; đúng tập khóa mà `GET /courses` đang trả cho học sinh (không thêm luật ẩn mới).
- Mục của `GET /me/courses/{course}` thêm `video: null | {duration_seconds, checkpoint_count}` và `file: null | {media_type, size_bytes}` (tệp tài liệu đầu tiên học sinh được xem). Mục bị khóa vẫn trả hai trường này, không kèm link.
- `GET /me/contents` mỗi dòng thêm `assigned_by: {teacher_name: string|null, class_name: string|null}`; `class_name` null khi giao riêng. Được giao theo cả hai cách thì ưu tiên giao riêng.
- Điểm dừng có `at_seconds > duration_seconds` (biết thời lượng): không có trong `video.checkpoints`, không tính vào `checkpoint_count` phía học sinh, không chặn tua, không tính vào hoàn thành; `POST …/answers` cho điểm đó trả 404.
- OpenAPI của `POST /checkpoints/{checkpoint}/answers` khai `answer` là kiểu tự do và response là boolean.

- [ ] **0.1 `GET /me/courses` (Q1).** Test: `test_lists_only_courses_the_student_belongs_to` (khóa của lớp khác, khóa của con người khác không hiện); `test_counts_match_course_view` (`done/total/complete` bằng `GET /me/courses/{id}`); `test_parent_teacher_admin_are_forbidden`; `test_query_count_does_not_grow_per_course` hoặc, nếu phải tính từng khóa, ghi rõ số truy vấn mỗi khóa trong docblock và khóa bằng test.
- [ ] **0.2 Dòng phụ và người giao (Q4).** Test: video có/không có tệp; tài liệu; mục khóa tuần tự vẫn có `video/file`; `assigned_by` cho giao lớp, giao riêng, cả hai; không lộ tên tệp, `storage_key`, link.
- [ ] **0.3 Điểm dừng vượt thời lượng (Q3).** Test: `test_beyond_duration_checkpoint_is_hidden_and_does_not_block_completion` (thời lượng 60, điểm 30 và 70; trả lời đúng điểm 30, gửi vị trí 60 → `completed`); `test_seek_clamp_ignores_beyond_duration`; `test_answering_hidden_checkpoint_is_404`; điểm đúng bằng thời lượng vẫn tính; link ngoài không biết thời lượng thì giữ mọi điểm; góc nhìn giáo viên không đổi.
- [ ] **0.4** Chạy `tests/Feature/CourseContent`, `tests/Feature/Assessment`, `tests/Unit/Modules/CourseContent`, `tests/Architecture`, Pint, PHPStan file sửa; export OpenAPI. Push, review opus, sửa.

---

### Task 1: Lớp dữ liệu phía Học sinh và thông báo bài bị khóa (`B-114`)

**Files:**
- Modify: `src/lib/api-error.ts` (+ test), `features/course-content/api/{types,keys}.ts`, `features/assessment/{index.ts,ui/student/student-assessment-detail-screen.tsx}` (+ test)
- Create: `features/assessment/api/content-lock.ts` (+ test); `features/course-content/api/{my-courses.ts,my-contents.ts,video-gate.ts,video-gate.test.ts,my-contents.test.tsx}`

**Interfaces — Produces:**

```ts
// lib/api-error: ApiError thêm `readonly context: Record<string, unknown>` = `error.details` nguyên dạng khi là object ({} nếu không).
//   `details` giữ nguyên nghĩa cũ (chỉ khi mọi giá trị là mảng chuỗi).
lockedBy(error: unknown): { item_id: string; title: string } | null   // assessment, export ở index

type ItemStatus = "not_started" | "in_progress" | "completed";
type MyCourseSummary = { id; name; description: string | null; subject_name: string | null; teacher_name: string | null; done: number; total: number; complete: boolean };
type MyCourseItem = { id; position: number; type: CourseItemType; title; content_id: string | null; assessment_id: string | null; status: ItemStatus; locked: boolean; blocked_by: { item_id; title } | null; video: { duration_seconds: number | null; checkpoint_count: number } | null; file: { media_type: string; size_bytes: number } | null };
type MyCourse = { id; name; sequential_unlock: boolean; completion_rule; completion_min_score: number | null; items: MyCourseItem[]; completion: { complete: boolean; done: number; total: number; average_score: number | null } };
type MyCheckpoint = { id; at_seconds: number; question_type: string; question: AttemptQuestion["snapshot"]; answered_correctly: boolean };
type MyContent = { id; type: ContentType; course_id: string | null; title; body_html: string | null; attachments: { id; file_name; media_type; size_bytes; download_url }[]; video: { status: "ready"; source: VideoSource; duration_seconds: number | null; checkpoints: MyCheckpoint[] } | null; progress: { status: ItemStatus; last_position_seconds: number; completed_at: string | null } };

useMyCourses(); useMyCourse(courseId); useMyContents(page); useMyContent(contentId)   // useMyContent: gcTime 0, không tự tải lại khi quay lại cửa sổ
useSetDone(contentId, courseId)      // mutate(boolean) → PUT progress {completed}
useSaveVideoProgress(contentId, courseId) // mutate({ position_seconds, completed? }) → { status, last_position_seconds, max_allowed_position_seconds }
useAnswerCheckpoint()                // mutate({ checkpointId, answer }) → { is_correct, can_continue }

// video-gate.ts (thuần)
gateAt(position: number, checkpoints: MyCheckpoint[], passed: ReadonlySet<string>): MyCheckpoint | null
//   điểm chưa đúng sớm nhất có at_seconds <= position; điểm answered_correctly hoặc trong `passed` coi như qua
isPassed(checkpoint, passed): boolean
resumePosition(last: number, duration: number | null): number   // 0 khi last >= duration - 2
```

Lưu tiến độ **không** làm mới `useMyContent` hay link phát; chỉ làm mới `useMyCourse`/`useMyCourses` khi `status` trả về khác lần trước.

- [ ] **Step 1: Test đỏ.** `api-error`: `keeps object details in context` (`{blocked_by:{item_id,title}}` → `context.blocked_by.title`), `field errors keep working`. `content-lock`: trả `blocked_by` chỉ khi `code === "CONTENT_LOCKED"` và đúng dạng, còn lại `null`. `video-gate`: `stops at the earliest unanswered checkpoint at or before the position`; `passed ones do not stop`; `checkpoint exactly at the position stops`; `checkpoint at 0 stops at 0`; `resume restarts near the end`. `my-contents`: `saving progress does not refetch the content or playback`; `refreshes the course only when the status changes`. Màn chi tiết bài: `locked assessment says which item to finish and links to the course` (403 `CONTENT_LOCKED`; `course_id` lấy từ `GET /me/assessments`; không có thì dẫn `/student/courses`); `starting a locked assessment shows the blocker name`.
- [ ] **Step 2:** chạy → FAIL. **Step 3:** hiện thực. **Step 4:** `pnpm vitest run src/lib src/features/assessment src/features/course-content src/architecture.test.ts`, typecheck, lint → PASS.
- [ ] **Step 5:** commit `feat: add student course content data layer and locked assessment notice`, push.

---

### Task 2: ST-09 danh sách khóa và chi tiết khóa

**Files:**
- Create: `features/course-content/ui/student/{my-course-outline.tsx,my-course-item-row.tsx,my-item-view.ts}` (+ test)
- Modify: `features/course-content/index.ts`, `features/academic/ui/student/{student-courses-screen,student-course-detail-screen}.tsx` (+ test)

**Interfaces — Produces:** `MyCourseOutline({ courseId, description, subjectName })` (hai cột như mockup); `myItemView(item: MyCourseItem, assessment?: MyAssessment): { icon, tile, meta, badge: { tone, label } }`; `itemHref(courseId, item): string` (nội dung → `/student/courses/{courseId}/contents/{content_id}`; bài đánh giá → `/student/assessments/{assessment_id}`).

Nhãn: mục khóa `Chưa mở` (neutral); nội dung `Đã học` (green) / `Đang học` (blue) / `Chưa học` (neutral); bài đánh giá dùng `ASSESSMENT_STATE_LABEL`. Nút: `Bắt đầu học` khi `done === 0`, `Tiếp tục học` khi còn mục mở chưa xong, ngược lại `Xem lại khóa`; đích là mục đầu tiên chưa xong và không `locked`, không có thì mục đầu.

- [ ] **Step 1: Test đỏ.** `course list shows subject, description and finished items`; `empty list` (chữ mockup); `outline renders chapters, items and statuses in order`; `locked item explains what to finish instead of navigating` (toast "Mục này chưa mở", "Em cần học xong «X» trước.", không có link); `progress bar shows done of total and percent` (0 mục → 0%, không chia cho 0); `resume button picks the first unfinished open item`; `info card lists subject, count, study mode and completion rule` (cả điều kiện điểm tối thiểu: "Xong tất cả và đạt trung bình từ 6,5"); `sequential note only for sequential courses`; `unknown course shows not found`.
- [ ] **Step 2..4:** đỏ → hiện thực theo mockup dòng 511–558 → test `src/features/course-content src/features/academic src/architecture.test.ts`, typecheck, lint xanh.
- [ ] **Step 5:** commit `feat: add student course list and course outline`, push.

---

### Task 3: Màn học (ST-10) và bài giảng được giao (ST-12)

**Files:**
- Create: `features/course-content/ui/student/{learn-item-screen.tsx,learn-lecture.tsx,learn-document.tsx,learn-footer.tsx,learn-side-list.tsx,my-lecture-list.tsx}` (+ test); `app/(portal)/student/courses/[courseId]/contents/[contentId]/page.tsx`; `app/(portal)/student/lectures/page.tsx`; `app/(portal)/student/lectures/[contentId]/page.tsx`
- Modify: `config/nav.ts` (+ test hiện có), `features/course-content/index.ts`

**Interfaces — Produces:** `LearnItemScreen({ contentId, courseId: string | null, renderVideo?: (content: MyContent) => ReactNode })` (Task 4 truyền hoặc đặt sẵn trình học video); `MyLectureList()`.

- [ ] **Step 1: Test đỏ.** `shows the lecture with images and attached files`; `mark done toggles and unlocks the next item` (PUT `{completed:true}` rồi tải lại khóa; bấm lại gửi `false`, nhãn "Bỏ đánh dấu đã học"); `document shows name, size and downloads with a fresh url` (bấm "Tải xuống" gọi lại `GET /me/contents/{id}` và dùng `download_url` mới); `previous and next skip chapters, disabled at the ends`; `next onto a locked item warns and stays`; `next onto an assessment goes to its detail page`; `locked content says what to finish` (403 `CONTENT_LOCKED` khi mở thẳng URL, nút về khóa); `content not visible shows not found` (404); `standalone content has no side list or prev/next`; `assigned list shows type, who assigned it and status` + phân trang + trạng thái rỗng.
- [ ] **Step 2..4:** đỏ → hiện thực (mockup 496–508, 560–566, 603–640) → test `src/features/course-content src/config src/architecture.test.ts`, typecheck, lint xanh.
- [ ] **Step 5:** commit `feat: add student lecture, document and assigned lecture screens`, push.

---

### Task 4: ST-11 video tải lên và điểm dừng

**Files:**
- Modify: `features/course-content/ui/shared/video-player.tsx` (+ test), `features/grading/index.ts`
- Create: `features/course-content/ui/student/{learn-video.tsx,use-video-session.ts,checkpoint-overlay.tsx,video-progress-bar.tsx}` (+ test)

**Interfaces:**
- `VideoPlayer` thêm: `startAt?: number` (đặt một lần khi có metadata), `onPause?`, `onEnded?`, `onSeeking?(seconds)`, `className?`; handle thêm `pause()`, `play()`, `currentTime()`. Mặc định không đổi hành vi ở màn giáo viên.
- Export từ `@/features/grading`: `AttemptAnswerInput`, kiểu `AttemptQuestion`, `AnswerValue`, và hàm dựng giá trị mặc định đang hiện (thứ tự câu sắp xếp).
- `useVideoSession(content, courseId)`: giữ vị trí, tập điểm đã qua, điểm đang mở, ghi chú chặn tua; mọi quyết định dừng đi qua `gateAt`.

Quy tắc: đang phát hay tua mà `gateAt(vị trí)` có điểm thì tạm dừng, kẹp vị trí về `at_seconds`, thoát toàn màn hình, mở lớp phủ; đóng lớp phủ chỉ bằng "Xem tiếp" sau khi `can_continue`. Lưu vị trí mỗi 10 giây khi đang phát, khi tạm dừng, khi tới điểm dừng, khi hết video (gửi đúng `duration_seconds`) và **ngay sau khi trả lời đúng** (điểm cuối cùng đúng khi đã xem hết thì video mới được tính xong). Lưu lỗi thì giữ vị trí lớn nhất để lần sau gửi, không chặn việc xem.

- [ ] **Step 1: Test đỏ** (giả `currentTime`, phát sự kiện `timeupdate`/`seeking`/`ended` trên thẻ `<video>`):
  - `resumes where the student stopped`;
  - `reaching a checkpoint pauses and shows the question` ("Câu hỏi tại 3:10 · điểm dừng 1/2");
  - `resuming exactly on an unanswered checkpoint shows it at once`;
  - `seeking past an unanswered checkpoint snaps back with a note` ("Em cần trả lời câu hỏi ở điểm dừng trước thì mới tua qua được.");
  - `wrong answer can be retried and says the first try is recorded`;
  - `correct answer offers continue and does not stop there again`;
  - `ordering question sends the shown order when untouched`;
  - `check is disabled until something is answered` (loại khác sắp xếp);
  - `exits fullscreen before asking` (`document.exitFullscreen` được gọi khi `fullscreenElement` có giá trị);
  - `saves every ten seconds, on pause and at the end`; `saves again right after the last correct answer`;
  - `a failed save is retried with the latest position and playback goes on`;
  - `the player element survives saving and answering` (cùng một thẻ `<video>`, `playback` gọi đúng 1 lần);
  - `completing the video says so once and unlocks the next item` (toast "Em đã xem hết video");
  - `a checkpoint removed by the teacher reloads the content` (trả lời → 404);
  - `timeline marks are green when answered and orange otherwise`.
- [ ] **Step 2..4:** đỏ → hiện thực (mockup 567–601, 759–777) → test `src/features/course-content src/features/grading src/architecture.test.ts`, typecheck, lint xanh.
- [ ] **Step 5:** commit `feat: add student video player with checkpoints`, push.

---

### Task 5: Video link ngoài (Q2)

**Files:** Create `features/course-content/ui/student/{learn-external-video.tsx,external-checkpoint-list.tsx}` (+ test); modify `learn-video.tsx`.

- [ ] **Step 1: Test đỏ.** `shows the embed with the video questions listed by time`; `questions can be answered in any order`; `mark watched is disabled until every question is correct` kèm lý do; `without checkpoints mark watched is enabled`; `marking sends completed true`; `completed video shows done and keeps the questions answered`.
- [ ] **Step 2..4:** đỏ → hiện thực, dùng lại phần câu hỏi của `checkpoint-overlay` (tách thân câu hỏi ra `checkpoint-question.tsx`) → test xanh.
- [ ] **Step 5:** commit `feat: add student external video with question list`, push.

---

### Task 6: Kiểm cuối nhánh

- [ ] `pnpm lint && pnpm typecheck && pnpm build`; test các feature đã sửa và kiến trúc.
- [ ] Chạy trên Chrome thật bằng tài khoản học sinh seed: khóa học theo thứ tự có bài giảng → video có hai điểm dừng → tài liệu → bài đánh giá; học hết, thử tua qua điểm dừng, tải lại giữa chừng; bề rộng 390px (cả thanh menu dưới sau khi thêm mục "Bài giảng"). Không chạy được thì ghi rõ.
- [ ] So JSX với các đoạn mockup đã ghi.
- [ ] Review độc lập cả nhánh (opus, effort medium), sửa một lượt.
- [ ] Docs trong nhánh docs của lát: `STATE.md`, spec thêm mục "Ghi chú hiện thực FE lát 7", `BACKLOG.md` đóng `B-114` và `B-111`, `error-codes.md` nếu đổi, `decision-log` nếu Q1..Q4 được chốt khác đề xuất.

---

## Không làm ở lát này

Chặn tải tệp đề của bài đang khóa (`B-113`, việc BE riêng); cờ `locked` ở `GET /me/assessments`; lời giải câu hỏi (`B-087`); lưu vị trí khi đóng tab bằng `keepalive`; phụ đề, tốc độ phát tùy biến; tiến độ cho Giáo viên, Admin, Phụ huynh (lát 8).
