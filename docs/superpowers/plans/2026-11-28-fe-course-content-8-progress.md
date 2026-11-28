# Khóa học có nội dung học, lát FE 8 (tiến độ) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giáo viên và Admin xem được tiến độ học của khóa (TC-19), Admin soạn được nội dung khóa dùng chung (AD-12), Phụ huynh xem được tiến độ từng mục của con.

**Architecture:** Dùng ba API đã có (`GET /courses/{id}/progress`, `GET /me/children/{sid}/courses/{id}/progress`, API soạn nội dung). BE chỉ bổ sung vài trường tính sẵn vào bảng tiến độ. Phần soạn nội dung chuyển sang `ui/shared/manage/` để Giáo viên và Admin dùng chung.

**Tech Stack:** Laravel 13 (module `CourseContent`), Next.js 16, React 19, TanStack Query, Vitest + MSW.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 4, 7, 9, 15, và mục 20 thêm ở lát này); mockup `PiClass Giáo viên.dc.html` (khối `cdProgTab`), `PiClass Quản trị.dc.html` (AD-12). Phần Phụ huynh không có mockup: dựng bằng design system (user cho phép, 2026-11-28). Quyết định: `DEC-LRN-059`.

## Global Constraints

- BE theo R1–R11, FE theo F1–F8; file tối đa 200 dòng; chỉ `lib/api-client` gọi `fetch`; feature khác chỉ import qua `index.ts`.
- Comment và mô tả test tiếng Việt; tên và commit message tiếng Anh; chữ giao diện tiếng Việt.
- Chữ trong phần dùng chung Giáo viên/Admin không xưng "cô".
- Không migration, không thư viện mới. Không so sánh vai trò trong component (F7).
- Chỉ chạy test đúng phạm vi: test mới, test của thư mục bị sửa, `architecture.test.ts`; BE thêm `tests/Architecture`, Pint, PHPStan file đã sửa.

## Review Focus

1. Khóa chưa có học viên, hoặc không có mục nào đếm được: không hiện `NaN`, có trạng thái rỗng.
2. Video không có điểm dừng, hoặc điểm dừng chưa ai trả lời: không đưa vào "khó nhất", không hiện 0%.
3. Học sinh đã học hết mục nhưng chưa đạt điểm tối thiểu: không bị gắn "Lâu chưa học", hiện rõ lý do.
4. Admin ở khóa của giáo viên không có nút sửa nội dung; giáo viên ở khóa dùng chung không có tab tiến độ và không gọi API bị 403.
5. Phụ huynh đổi con khi đang ở khóa mà con kia không học: không còn số liệu của con trước.

---

### Task 1: BE — bổ sung trường tính sẵn cho bảng tiến độ khóa

**Files:**
- Create: `app/Modules/CourseContent/Enums/LearnerProgressState.php`, `app/Modules/CourseContent/Domain/LearnerProgress.php`
- Modify: `app/Modules/CourseContent/Queries/CourseProgressQuery.php`, `Http/Resources/CourseProgressResource.php`, `config/content.php`
- Test: `tests/Feature/CourseContent/CourseProgressInsightsTest.php`

**Interfaces (Produces, trong `GET /courses/{course}/progress`):**
- `learners[].state`: `completed|in_progress|stalled|not_started`; `learners[].current_item_id: string|null` (mục đếm được đầu tiên chưa xong).
- `learners[].last_activity_at` tính cả bài nộp chưa `invalidated` (`submissions.received_at`) của bài đánh giá trong khóa.
- `items[].checkpoints[].prompt: string`.
- `summary.average_progress: float` (trung bình `done/total` theo học viên, 0..1; không có thì 0.0); `summary.first_try_correct_rate: float|null` (tổng đúng lượt đầu / tổng lượt đầu; chưa ai trả lời thì null).
- `LearnerProgress::state(bool $complete, int $done, ?string $currentItemId, ?CarbonInterface $lastActivity, CarbonInterface $now, int $stalledDays): LearnerProgressState`.

Luật: `complete` → completed; `done == 0` và không có hoạt động → not_started; còn mục (`currentItemId`) và hoạt động gần nhất cách `now` từ `content.progress_stalled_days` (7) ngày trở lên → stalled; còn lại in_progress.

- [ ] **Step 1:** Viết test: `test_state_and_current_item_per_learner` (bốn trạng thái, biên đúng 7 ngày là stalled, 6 ngày 23 giờ là in_progress), `test_finished_all_items_but_below_min_score_is_in_progress_not_stalled`, `test_submission_counts_as_activity_and_invalidated_does_not`, `test_checkpoint_prompt_and_summary_rates`, `test_empty_course_has_zero_progress_and_null_first_try_rate`. Dùng `Carbon::setTestNow`.
- [ ] **Step 2:** Chạy file test. Expected: FAIL (thiếu khóa `state`).
- [ ] **Step 3:** Hiện thực. Hoạt động từ bài nộp lấy bằng một truy vấn gộp (join `attempts`, `student_assessments`), không phụ thuộc số học viên. Cập nhật docblock `@return` của Resource để OpenAPI đúng.
- [ ] **Step 4:** Chạy `tests/Feature/CourseContent tests/Architecture`, Pint, PHPStan file đã sửa, `scramble:export`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: add learner state and checkpoint insights to course progress`.

### Task 2: FE — chuyển phần soạn nội dung sang `ui/shared/manage`

**Files:**
- Move (`git mv`): mọi file trong `features/course-content/ui/teacher/` (cả test) trừ `lecture-list*` và `content-*-dialog.tsx` → `features/course-content/ui/shared/manage/`
- Modify: `features/course-content/index.ts`, import tương đối

**Interfaces (Produces):**
- `CourseContentTabProps` thêm `courseHref: string` (đường dẫn gốc của khóa theo cổng) và `canUsePersonalLibrary: boolean` (hiện "Bài đánh giá" và "Lấy từ bài giảng của tôi").
- `LectureEditScreen` và `VideoEditScreen` thêm `courseHref?: string`; nút quay lại về `courseHref ?? "/teacher/lectures"`.

- [ ] **Step 1:** Chuyển file, sửa import. Chạy `pnpm vitest run src/features/course-content src/architecture.test.ts`. Expected: PASS như trước khi chuyển. Commit `refactor: move course content editors to shared manage folder`.
- [ ] **Step 2:** Viết test trong `add-item-dialog.test.tsx`: `without the personal library only offers new content` (không có nút "Bài đánh giá", "Lấy từ bài giảng của tôi"; có "Video", "Bài giảng chữ", "Tài liệu", "Chương") và `opens the editor under the given course path` (`router.push("/admin/courses/k1/contents/c1/video")`). Thêm test nút quay lại của hai trình soạn trỏ `courseHref`. Expected: FAIL.
- [ ] **Step 3:** Hiện thực props; bỏ chữ "cô" trong `shared/manage` (giữ nghĩa, viết không xưng hô). Cập nhật màn Giáo viên trong `academic` truyền `courseHref`, `canUsePersonalLibrary`.
- [ ] **Step 4:** Chạy `src/features/course-content src/features/academic src/features/question-bank src/architecture.test.ts`, typecheck. Expected: PASS.
- [ ] **Step 5:** Commit `feat: let content editors serve any course portal`.

### Task 3: FE — lớp dữ liệu và tab "Tiến độ học" (TC-19)

**Files:**
- Create: `features/course-content/api/course-progress.ts`; `ui/shared/progress/{course-progress-tab,progress-stats,hard-checkpoints,learner-progress-row}.tsx`, `progress-view.ts`
- Modify: `api/types.ts`, `api/keys.ts`, `index.ts`, `lib/time.ts` (+test)
- Test: `ui/shared/progress/course-progress-tab.test.tsx`

**Interfaces:**
- Consumes: Task 1.
- Produces: `useCourseProgress(courseId)` (`refetchOnMount: "always"`); `<CourseProgressTab courseId={string} />` export qua index; `daysAgoLabel(iso: string, now?: Date): string` trong `lib/time.ts` → `"hôm nay" | "hôm qua" | "N ngày trước"` theo ngày lịch giờ Việt Nam.

Chữ theo mockup: ba ô "Hoàn thành khóa" (`x/y`), "Tiến độ trung bình" (`%`), "Đúng ngay lượt đầu" (`%` hoặc "—"); mục "Điểm dừng khó nhất (đúng ngay lượt đầu)": tối đa 3 điểm có người trả lời, tỷ lệ thấp trước (bằng nhau thì nhiều lượt hơn trước), dòng phụ `<tên video> · tại m:ss`, badge cam khi < 60%, còn lại xanh lá; mục "Từng học sinh": tên, `<đang ở> · <lần học cuối>`, thanh %, badge "Hoàn thành" (green) / "Đang học" (blue) / "Lâu chưa học" (orange) / "Chưa bắt đầu" (neutral). `đang ở`: completed "Đã hoàn thành khóa"; not_started "Chưa bắt đầu"; có `current_item_id` "Đang ở: <tên mục>"; không còn mục "Đã học hết mục, chưa đạt điểm tối thiểu". Lần học cuối rỗng: "chưa vào học". Khóa đặt điểm tối thiểu thì thêm `· Điểm TB 7,5` (hoặc "chưa có").

- [ ] **Step 1:** Test: ba ô số; thứ tự và loại trừ điểm dừng khó; bốn badge và dòng phụ; học hết mục thiếu điểm; không học viên → "Khóa chưa có học viên nào." và không có `NaN`; không điểm dừng nào có lượt → không có mục "khó nhất"; lỗi tải → nút "Thử lại". Test `daysAgoLabel` ở biên nửa đêm giờ Việt Nam. Expected: FAIL.
- [ ] **Step 2:** Hiện thực.
- [ ] **Step 3:** Chạy test thư mục, `lib`, kiến trúc. Expected: PASS. Commit `feat: add course progress tab`.

### Task 4: FE — nối vào TC-15 và AD-12, route soạn của Admin

**Files:**
- Create: `app/(portal)/admin/courses/[courseId]/contents/[contentId]/{edit,video}/page.tsx`; `features/academic/ui/admin/{admin-course-content-tab,admin-course-editor-screens}.tsx`
- Modify: `features/academic/ui/{teacher/teacher-course-detail-screen,admin/course-detail-screen}.tsx` (+test), `features/academic/index.ts`

**Interfaces:** Consumes `CourseContentTab`, `CourseProgressTab`, `LectureEditScreen`, `VideoEditScreen`, `checkpointQuestionTools` (route truyền vào).

Hành vi: Giáo viên, khóa của tôi: thêm tab "Tiến độ học"; khóa dùng chung không đổi. Admin: tab "Bài học · N" đổi thành "Nội dung · N" dùng `CourseContentTab` (`canUsePersonalLibrary=false`, `gradingHref` null); sửa được chỉ khi `owner_teacher_id === null` và khóa chưa lưu trữ; khóa của giáo viên `readOnly` kèm dòng "Khóa của giáo viên do chủ khóa soạn. Quản trị viên chỉ xem."; `editHref` chỉ cho bài giảng và video khi sửa được. Thêm tab "Tiến độ học". Ô số "Bài trong khóa" đổi thành "Mục trong khóa", không tính chương.

- [ ] **Step 1:** Test: giáo viên khóa của tôi mở tab thấy bảng; khóa chung không có tab và không có request `/progress`; Admin khóa chung có "Thêm mục", link "Sửa" bài giảng trỏ `/admin/courses/<id>/contents/<contentId>/edit`; Admin khóa của giáo viên không có "Thêm mục", "Gỡ khỏi khóa", có dòng giải thích; Admin có tab "Tiến độ học". Expected: FAIL.
- [ ] **Step 2:** Hiện thực.
- [ ] **Step 3:** Chạy `src/features/academic src/architecture.test.ts`, typecheck, lint. Expected: PASS. Commit `feat: wire course content and progress into teacher and admin courses`.

### Task 5: FE — tiến độ của con (Phụ huynh)

**Files:**
- Create: `features/course-content/api/child-course-progress.ts`; `ui/parent/{child-course-progress,child-course-item-row}.tsx`; `ui/shared/item-status.ts`
- Modify: `ui/student/my-item-view.ts` (dùng `STATUS_BADGE`, `completionRuleLabel` từ shared), `ui/student/my-course-info.tsx`, `index.ts`, `features/academic/ui/parent/parent-course-detail-screen.tsx` (+test)
- Test: `ui/parent/child-course-progress.test.tsx`

**Interfaces (Produces):** `<ChildCourseProgress studentId={string} courseId={string} />` export qua index.

Hành vi: thẻ tiến độ (`x/y mục · %`, thanh, badge "Đã hoàn thành"/"Đang học", "Cách học", "Hoàn thành khi", "Điểm trung bình" khi có); danh sách mục theo `position`, chương là tiêu đề; mục nội dung là dòng không bấm được (phụ huynh không xem tệp, video) badge "Đã học"/"Đang học"/"Chưa học"; mục bài đánh giá là liên kết `/parent/assessments/<assessment_id>`, badge theo `assessmentState` của bài con (không có thì "Đã nộp"/"Chưa làm"); mục khóa: ổ khóa, "Chưa mở", dòng phụ "Con cần học xong «X» trước". Không có mục đếm được: "Khóa này chưa có mục nào.". 404: "Không xem được tiến độ khóa này.". Lỗi khác: "Thử lại". Đầu trang (tên khóa, giáo viên) và thông báo "Con không học khóa này." giữ như cũ.

- [ ] **Step 1:** Test cho từng hành vi trên, thêm test đổi `studentId` không còn mục của con trước. Sửa test màn cha sang API mới. Expected: FAIL.
- [ ] **Step 2:** Hiện thực.
- [ ] **Step 3:** Chạy `src/features/course-content src/features/academic src/architecture.test.ts`, typecheck, lint. Expected: PASS. Commit `feat: show child course progress to parents`.

### Task 6: Kiểm chứng trên trình duyệt, tài liệu, review

- [ ] **Step 1:** Chạy Chrome headless trên database tạm (không đụng dữ liệu của user): Giáo viên mở "Tiến độ học"; Admin tạo bài giảng và video link ngoài trong khóa dùng chung, thêm một điểm dừng; Phụ huynh mở khóa của con. Kiểm 390px không tràn ngang.
- [ ] **Step 2:** Viết spec mục 20, `DEC-LRN-059`, `STATE.md`, `BACKLOG.md` (`B-098`).
- [ ] **Step 3:** Review cả nhánh bằng subagent `model: "opus"`; sửa Critical và Important trong một lượt, mỗi lỗi có test đỏ trước.
