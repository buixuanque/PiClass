# Khóa học có nội dung học, lát 6 (TC-17: video và điểm dừng) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Giáo viên thêm mục video vào khóa, tải tệp MP4 theo phần hoặc dán link YouTube/Vimeo, xem lại video, đặt và xác nhận điểm dừng câu hỏi.

**Architecture:** BE bổ sung trước trong một PR riêng (Task 0). FE thêm vào `features/course-content`: lớp `api/` cho video và điểm dừng (gồm bộ tải theo phần, không phụ thuộc giao diện), trình phát ở `ui/shared/` để lát 7 dùng lại, màn TC-17 ở `ui/teacher/`. Phụ thuộc mới một chiều: `course-content → question-bank` (`question-bank → assessment` đã có, cả hai không import `course-content`).

**Tech Stack:** Laravel 13; Next.js 16.4.6, React 19, TanStack Query, React Hook Form + Zod, Vitest + Testing Library + MSW. Không thêm thư viện.

**Thực hiện:** làm trực tiếp trong phiên (superpowers:executing-plans); Task 0 review opus riêng, cuối nhánh web review opus.

**Lệch khi làm:** `course-content` không import `question-bank` (tạo vòng phụ thuộc); xem spec mục 18. Task 3 và 4 chung một commit.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 4, 5, 7, 8, 9, 13, 14); `DEC-LRN-052`, `DEC-LRN-053`. Mockup: `piclass-mockup/design-system/PiClass Giáo viên.dc.html` dòng 1092–1150 (màn), 1261–1283 (hộp chọn câu), 2300–2358 (hành vi và dòng danh sách mục).

## Global Constraints

- Task 0: repo `piclass-api`, nhánh `feat/course-content-video-editor-api`. Task 1..5: repo `piclass-web`, nhánh `feat/course-content-video`. Cả hai từ `origin/main` sau `git fetch`. Đọc `CLAUDE.md` của repo trước khi sửa; web đọc thêm `docs/rules/design-system.md`, `docs/rules/testing.md`, `docs/rules/pitfalls.md`.
- Video: chỉ `video/mp4`, tối đa 1 GB (1073741824 byte); phần tải dùng `part_size` BE trả (5 MB). Thời lượng nguyên 1..86400 giây. Điểm dừng cách nhau tối thiểu 3 giây, tối đa 50 điểm.
- Loại câu của điểm dừng, đúng sáu loại: `single_choice`, `multiple_choice`, `true_false`, `fill_in_the_blank`, `matching`, `ordering`. Không dùng `AUTO_TYPES` của `question-bank` (có `short_answer`).
- Port đúng từng giá trị style, bố cục và chữ từ mockup. Hàng "Xem thử trạng thái tệp" chỉ để demo trong mockup, **không** port. Chỗ mockup không vẽ thì làm theo "Đã chọn thay mockup".
- F1..F8; mỗi file một component chính, khoảng 200 dòng. Chữ giao diện tiếng Việt; lỗi 422 hiện nguyên văn của BE; rẽ nhánh theo `error.code`.
- Chỉ chạy test đúng phạm vi task: test mới, test của module/feature có file bị sửa, test kiến trúc. Không chạy cả bộ.
- Web kiểm mỗi Task: `pnpm vitest run <thư mục đã sửa> src/architecture.test.ts`, `pnpm lint`, `pnpm typecheck`. API: `docker compose exec -T api php artisan test <đường dẫn>` từ `piclass-infra/local`, thêm Pint.

## Đã quyết (user, 2026-11-28, `DEC-LRN-057`): cả ba theo đề xuất

| # | Vấn đề | Đề xuất | Nếu chọn khác |
|---|---|---|---|
| Q1 | Mockup dòng mục video có "Video · 08:30 · 1 điểm dừng · link ngoài" và huy hiệu trạng thái, API danh sách mục không trả | BE thêm trường `video` vào mục khóa (Task 0) | Bỏ huy hiệu và dòng phụ: bỏ 0.2 và phần dòng mục ở Task 2 |
| Q2 | "Tạo câu mới": spec mục 9 ghi dùng lại trình soạn TC-08, mockup vẽ form rút gọn (3 loại, 3 đáp án, một đáp án đúng) | Dùng lại thẻ câu TC-08, giới hạn đúng sáu loại. Form của mockup không khớp câu "Điền chỗ trống" của BE (cần đánh dấu `___` và đáp án theo từng chỗ trống) | Làm form rút gọn: thêm một component, chỉ có hai loại chọn đáp án dùng được đúng |
| Q3 | Bật lựa chọn "Video" thì giáo viên có thể bỏ dở một mục chưa có tệp. BE đang tính mục đó vào tiến độ và mở khóa tuần tự nên học sinh bị chặn vĩnh viễn ở đó | Mục video **chưa có video phát được** không tính (như bài đánh giá chưa mở): không chặn mục sau, không tính vào hoàn thành khóa | Giữ như cũ: giáo viên phải tự tải tệp hoặc gỡ mục |

## Đã chọn thay mockup (user đổi được)

- Nút "Xem trước" ở đầu trang: **chưa** làm. Nó mở màn học sinh ST-11 thuộc lát 7; trên màn TC-17 giáo viên đã xem được video trong khung soạn.
- Khung 16:9 là trình phát thật (mockup chỉ là hình giữ chỗ): thanh chọn thời điểm đi theo `currentTime`, bấm thanh thì tua video.
- Link ngoài: thêm ô "Thời lượng (phút:giây)", bắt buộc, vì không đọc được thời lượng từ YouTube/Vimeo và không có thời lượng thì không vẽ được thanh thời gian. Thời điểm chọn bằng thanh; trình nhúng không đồng bộ với thanh.
- Link ngoài lưu bằng nút "Lưu link" trong thẻ "Nguồn video" (mockup không có nút lưu cho ô link).
- Thêm, xóa, xác nhận điểm dừng lưu **ngay**. Nút "Lưu" ở đầu trang chỉ lưu tên rồi quay lại.
- Đang tải lên: thêm nút "Hủy tải lên"; rời trang thì hỏi. Tải lại trang giữa chừng thì không tải tiếp được: hiện "Đang tải" kèm nút "Chọn lại tệp".
- Dòng mục video chưa có gì (mockup không có trạng thái này): huy hiệu xám "Chưa có video".
- Điểm dừng nằm sau thời lượng (sau khi thay video ngắn hơn): huy hiệu "Vượt thời lượng", không có nút "Xác nhận", chỉ xóa (`B-111`).
- Hộp chọn câu: có ô tìm và phân trang (mockup chỉ có danh sách), chọn đúng một câu.
- TC-18: dòng video (chỉ tạo được qua API) có nút "Sửa" mở cùng màn soạn; không thêm nút tạo video độc lập.

## Review Focus

1. Mạng chập chờn giữa lúc tải 1 GB: phần lỗi được gửi lại, không phải tải lại từ đầu; hết số lần thử thì báo lỗi và chọn lại được. → Task 1.
2. Mở màn soạn quá 15 phút rồi xem tiếp: link phát hết hạn thì xin link mới và giữ đúng vị trí đang xem. → Task 3.
3. Hoàn tất tệp lớn lâu hơn 30 giây: proxy của Next không được cắt giữa chừng. → Task 1.
4. Giáo viên khác đổi câu đã dùng ở điểm dừng sang riêng tư: chủ khóa vẫn thêm, xóa được các điểm dừng khác. → Task 0.
5. Thay video ngắn hơn khi có **hai** điểm dừng vượt thời lượng: xóa được từng điểm một, không kẹt vì điểm còn lại. → Task 0.

---

### Task 0: BE bổ sung cho màn soạn video (`piclass-api`, PR riêng, đi trước; đụng phân quyền nên review opus)

**Files:**
- Modify: `app/Modules/CourseContent/Actions/{IssuePlaybackUrl,SyncCheckpoints,ConfirmCheckpoint}.php`, `Http/Requests/SyncCheckpointsRequest.php`, `Http/Resources/CourseItemResource.php`, `Queries/CourseItemStatusesQuery.php`, `app/Models/CourseItem.php`, `routes/api.php`
- Modify: `app/Modules/QuestionBank/Queries/ListQuestionsQuery.php` (và Request nếu cần khai tham số cho OpenAPI), `app/Modules/QuestionBank/Enums/QuestionType.php`
- Test: `tests/Feature/CourseContent/{VideoPlaybackTest,CheckpointTest hoặc file điểm dừng hiện có,CourseItemVideoSummaryTest,EmptyVideoProgressTest}.php`, test danh sách câu hỏi hiện có

**Interfaces — Produces:**
- `GET /contents/{content}/playback` → `data.url` của video tải lên là đường dẫn **tương đối** `/api/v1/content-videos/{id}/stream?...` (`B-120`).
- Mục khóa thêm `video: null | {status: "ready"|"uploading"|"failed"|null, source: "upload"|"external"|null, duration_seconds: int|null, checkpoint_count: int}`; `null` khi mục không phải video. Cùng quy tắc suy trạng thái với `ContentVideoStateQuery`.
- `GET /questions?auto_gradable=1` → chỉ câu có loại (bản hiện tại) thuộc sáu loại ở Global Constraints. Đưa danh sách sáu loại về một chỗ (`QuestionType::autoGradableForCheckpoint(): list<self>`), `SyncCheckpoints` dùng cùng chỗ đó.
- `PUT /contents/{content}/checkpoints`: chỉ kiểm quyền dùng câu với dòng **mới** hoặc **đổi** `question_version_id`; dòng giữ nguyên `id` và `at_seconds` được ở lại dù vượt thời lượng.
- `POST /checkpoints/{checkpoint}/confirm`: điểm vượt thời lượng → 422 `VALIDATION_FAILED` trường `at_seconds`, cờ giữ nguyên.

- [x] **0.1 URL phát tương đối.** Test `test_playback_url_is_relative_and_works_on_any_host`: `assertStringStartsWith('/api/v1/content-videos/', $url)`, gọi URL đó trên host `http://web.example` → 200, kèm `Range: bytes=0-3` → 206; sửa chữ ký → 403. Chạy thấy đỏ. Hiện thực: `URL::temporarySignedRoute(..., absolute: false)` và middleware `signed:relative`. Các test cũ trong `VideoPlaybackTest`, `StudentPlaybackTest`, `VideoCopyAndReleaseTest` tự ký URL thì ký cùng kiểu. Commit `fix: return relative signed video playback urls`.
- [x] **0.2 Tóm tắt video trong mục khóa (Q1).** Test: `test_manager_sees_video_summary_for_each_state` (chưa có gì → `status null`; phiên đang mở → `uploading`; phiên mới nhất lỗi → `failed`; có video tải lên thời lượng 510 và 1 điểm dừng → `ready, upload, 510, 1`; link ngoài → `external`); mục bài giảng có `video === null`; `test_item_list_query_count_does_not_grow_with_video_items` (đếm truy vấn với 1 và 4 mục video bằng nhau). Hiện thực: thêm eager load vào `CourseItem::DISPLAY_RELATIONS` (hoặc danh sách riêng cho danh sách mục), tách phần suy trạng thái của `ContentVideoStateQuery` thành hàm nhận quan hệ đã tải. Commit.
- [x] **0.3 Lọc câu tự chấm.** Test `test_auto_gradable_filter_returns_only_checkpoint_types`: tạo đủ chín loại, `?auto_gradable=1` trả đúng sáu; không gửi thì trả chín; câu riêng tư của người khác vẫn bị ẩn. Commit.
- [x] **0.4 Lưu điểm dừng khi có dòng cũ.** Test:
  - `test_unchanged_checkpoint_with_now_private_question_does_not_block_saving` (câu của giáo viên khác chuyển `private` sau khi đã dùng; PUT giữ dòng đó và thêm dòng mới → 200);
  - `test_new_or_changed_checkpoint_with_private_question_is_still_forbidden` (dòng mới → 403; dòng có `id` cũ nhưng đổi sang câu riêng tư → 403);
  - `test_two_over_duration_checkpoints_can_be_removed_one_at_a_time` (thời lượng 60, điểm 70 và 80 giữ nguyên; PUT bỏ 80 → 200);
  - `test_new_or_moved_checkpoint_beyond_duration_is_rejected` → 422 `checkpoints.0.at_seconds`;
  - `test_confirm_rejects_checkpoint_beyond_duration` → 422, `needs_recheck` vẫn `true`.
  Chạy đỏ rồi hiện thực. Commit.
- [x] **0.5 Video chưa phát được không tính (Q3).** Test `test_video_without_playable_video_neither_blocks_nor_counts` (khóa tuần tự, thứ tự: video rỗng → bài giảng: bài giảng không `locked`; `completion.total` không gồm video rỗng; tiến độ khóa của giáo viên cũng vậy) và `test_ready_video_still_blocks_next_item`. Hiện thực trong `CourseItemStatusesQuery::countsFor` (nhớ eager load, không N+1). Commit.
- [x] **0.6** Chạy test `tests/Feature/CourseContent`, `tests/Feature/QuestionBank`, `tests/Unit/Modules/CourseContent`, `tests/Architecture`, Pint; `php artisan scramble:export`, kiểm `openapi.json` có `video` và `auto_gradable`. Push. Review opus cả nhánh, sửa.

---

### Task 1: Lớp dữ liệu video, điểm dừng và bộ tải theo phần

**Files:**
- Modify: `src/lib/api-client.ts`, `src/lib/api-client.test.ts`, `src/features/course-content/api/{types,keys}.ts`, `next.config.ts`, `src/next-config.test.ts`
- Create: `src/features/course-content/api/{video.ts,video-upload.ts,checkpoints.ts,video-upload.test.ts}`

**Interfaces — Produces** (trong `api/`):

```ts
type ContentVideoState = { status: "ready" | "uploading" | "failed"; source: "upload" | "external" | null; original_name: string | null; size_bytes: number | null; duration_seconds: number | null; external_url: string | null; checkpoint_count: number; failure_reason?: string | null };
// LearningContent thêm: video: ContentVideoState | null
// CourseItem thêm: video: { status: ContentVideoState["status"] | null; source: ContentVideoState["source"]; duration_seconds: number | null; checkpoint_count: number } | null
// CreateCourseContentInput.type thêm "video"
type Checkpoint = { id: string; at_seconds: number; question_version_id: string; question_type: string; snapshot: { prompt: string }; needs_recheck: boolean };
type CheckpointInput = { id?: string; at_seconds: number; question_version_id: string };
type Playback = { source: "upload" | "external"; url: string; expires_at: string | null };

uploadVideoFile(args: { contentId: string; file: File; durationSeconds: number; signal: AbortSignal; onProgress: (percent: number) => void; retryDelaysMs?: number[] }): Promise<ContentVideoState>
useSetExternalVideo(contentId)      // mutate({ url, duration_seconds })
usePlayback(contentId, enabled)     // không cache lại link cũ: staleTime 0, gcTime 0
useCheckpoints(contentId, enabled)  // sắp theo at_seconds
useSaveCheckpoints(contentId)       // mutate(CheckpointInput[])
useConfirmCheckpoint(contentId)     // mutate(checkpointId)
toCheckpointInputs(list: Checkpoint[]): CheckpointInput[]
```

`apiClient.put` nhận `body: Blob`: gửi nguyên thân, `Content-Type: application/octet-stream`, giữ XSRF và thử lại 419 như cũ.

Mọi mutation đổi video hoặc điểm dừng làm mới `courseContentKeys.content(contentId)`, `checkpoints(contentId)` và mọi danh sách mục (tiền tố `["assessment","course-items"]`).

- [x] **Step 1: Test đỏ.**
  - `sends a blob body untouched as octet-stream` (`api-client.test.ts`).
  - `video-upload.test.ts` (MSW, tệp 12 byte, `part_size: 5`, `retryDelaysMs: [0, 0]`):
    - `uploads every part in order then completes`: ba PUT `parts/1..3` dài 5, 5, 2 byte; complete nhận `{upload_id, duration_seconds: 90}`; `onProgress` kết thúc ở 100 và không giảm.
    - `retries only the failed part`: phần 2 trả 500 một lần → số lần gọi mỗi phần là 1, 2, 1.
    - `gives up after the retries and cancels the session`: phần 2 luôn lỗi mạng → 3 lần, rồi `DELETE .../video/upload/{id}`, promise reject.
    - `does not retry a 4xx` → 1 lần, hủy phiên, giữ nguyên `message` của BE.
    - `aborting stops sending and cancels the session`: abort sau phần 1 → không có phần 3, có DELETE, reject `AbortError`.
    - `surfaces VIDEO_UPLOAD_FAILED from complete without cancelling` → reject giữ `code`, không gọi DELETE.
  - `next-config.test.ts`: `keeps long uploads alive through the proxy`: `expect(config.experimental?.proxyTimeout).toBe(300_000)` (mặc định của Next là 30 giây).
- [x] **Step 2:** chạy `pnpm vitest run src/features/course-content src/lib src/next-config.test.ts` → FAIL.
- [x] **Step 3:** hiện thực. Gửi tuần tự từng phần (`file.slice`); thử lại khi `status === 0` hoặc `>= 500`, mặc định `retryDelaysMs = [1000, 3000]`; lỗi khi hủy phiên bị nuốt, ném lỗi gốc. `uploadVideoFile` gọi `POST .../video/upload` với `{filename, size_bytes, mime_type: "video/mp4"}`; `mode !== "chunked"` thì reject với thông báo "Máy chủ yêu cầu cách tải chưa được hỗ trợ." (driver `s3` là `B-096`).
- [x] **Step 4:** chạy lại → PASS; lint, typecheck.
- [x] **Step 5:** commit `feat: add course video data layer and chunked upload`, push.

---

### Task 2: Thêm mục video, dòng danh sách, màn soạn và thẻ "Nguồn video"

**Files:**
- Modify: `features/course-content/ui/teacher/{add-item-dialog,course-content-row,course-content-tab,lecture-list-row}.tsx`, `ui/teacher/use-unsaved-guard.ts` (nhận thêm câu hỏi xác nhận), `api/contents.ts` (đổi tên làm mới cả danh sách mục), `index.ts`, `features/academic/ui/teacher/teacher-course-detail-screen.tsx`
- Create: `features/course-content/ui/shared/timecode.ts`; `ui/teacher/{video-edit-screen,video-source-card,video-upload-panel,video-link-panel,use-video-upload}.tsx|ts`; `ui/teacher/read-video-duration.ts`; `features/academic/ui/teacher/teacher-course-video-screen.tsx`; `app/(portal)/teacher/courses/[courseId]/contents/[contentId]/video/page.tsx`; `app/(portal)/teacher/lectures/[contentId]/video/page.tsx`
- Test: `ui/shared/timecode.test.ts`, `ui/teacher/video-edit-screen.test.tsx`, bổ sung `add-item-dialog.test.tsx`, `course-content-tab.test.tsx`, `lecture-list.test.tsx`

**Interfaces:**
- Consumes: mọi thứ ở Task 1.
- Produces: `formatTimecode(seconds: number): string` (`510 → "8:30"`, `3725 → "1:02:05"`); `parseTimecode(text: string): number | null` (`"8:30" → 510`, `"90" → 90`; sai dạng, 0 hoặc > 86400 → `null`); `readVideoDuration(file: File): Promise<number>` (đọc `loadedmetadata` qua object URL, làm tròn xuống, tối thiểu 1, thu hồi URL; không đọc được thì reject); `VideoEditScreen({ contentId, courseId?, courseName? })`. `VideoEditScreen` chừa vùng trái (thẻ "Điểm dừng câu hỏi") cho Task 3: chưa `ready` thì hiện chữ của mockup, `ready` thì để trống.

Chữ và bố cục theo mockup dòng 1092–1150. "Thêm mục" → "Video" đứng **đầu** danh sách (mockup dòng 2315): tạo `{type:"video", title:"Video chưa đặt tên"}` rồi mở màn soạn. Dòng mục: `Video · 8:30 · 1 điểm dừng`, thêm `· link ngoài`; chưa `ready` thì chỉ `Video`; huy hiệu `Sẵn sàng` (green), `Đang xử lý` (blue), `Lỗi tải lên` (coral), `Chưa có video` (neutral).

- [x] **Step 1: Test đỏ.**
  - `timecode.test.ts`: các giá trị ở Interfaces, thêm `"8:75" → null`, `"" → null`.
  - `add-item-dialog`: `adding a video creates the item and opens its editor` (body POST và đường dẫn `/teacher/courses/k1/contents/{content_id}/video`).
  - `course-content-tab`: `video row shows duration, checkpoint count and state badge` (bốn trạng thái; nút "Sửa" là link tới `editHref`; thay test cũ "video has none").
  - `lecture-list`: `video row links to the video editor`.
  - `video-edit-screen` (mock `readVideoDuration` trả 510):
    - `rejects a non-mp4 or oversize file before any request` (hiện "Chỉ nhận MP4 (H.264), tối đa 1 GB.", không có POST);
    - `reports a file the browser cannot read` ("Không đọc được video này. Hãy chọn tệp MP4 (H.264).");
    - `shows progress then the ready file` (`ProgressBar` "Đang tải lên 40%"; xong hiện tên tệp, `MP4 · 182 MB · 8:30`, "Sẵn sàng", nút "Thay tệp video");
    - `failed upload keeps the previous video and shows the reason` (BE message);
    - `cancel button aborts the upload`;
    - `leaving during an upload asks first` (`window.confirm` được gọi khi bấm liên kết);
    - `replacing a video with checkpoints asks to confirm them again` (toast "Đã thay tệp video");
    - `saving a link requires url and duration` rồi PUT `{url, duration_seconds: 510}`; lỗi 422 hiện nguyên văn;
    - `save renames the content and returns to the course`; tên rỗng thì không gửi.
- [x] **Step 2:** chạy → FAIL.
- [x] **Step 3:** hiện thực. Mỗi lần chọn tệp một `AbortController`; rời màn (unmount) thì abort. `use-unsaved-guard` dùng khi đang tải, câu hỏi: "Video đang được tải lên. Rời trang và hủy tải?". Đổi tab "Tải lên"/"Link ngoài" khi đang tải thì khóa tab.
- [x] **Step 4:** `pnpm vitest run src/features/course-content src/features/academic src/architecture.test.ts` → PASS; lint, typecheck.
- [x] **Step 5:** commit `feat: add video items and video source editor`, push.

---

### Task 3: Trình phát, thanh thời gian và danh sách điểm dừng

**Files:**
- Create: `features/course-content/ui/shared/{video-embed.ts,video-player.tsx}`; `ui/teacher/{checkpoint-card.tsx,checkpoint-timeline.tsx,checkpoint-list.tsx}`
- Modify: `ui/teacher/video-edit-screen.tsx`
- Test: `ui/shared/video-embed.test.ts`, `ui/shared/video-player.test.tsx`, `ui/teacher/checkpoint-card.test.tsx`

**Interfaces — Produces:**
- `embedUrl(url: string): string | null`: YouTube (`watch?v=`, `youtu.be/ID`, `/shorts/ID`, `/embed/ID`) → `https://www.youtube-nocookie.com/embed/ID`; Vimeo (`vimeo.com/ID`, `player.vimeo.com/video/ID`) → `https://player.vimeo.com/video/ID`; `ID` chỉ gồm `[A-Za-z0-9_-]` (Vimeo chỉ số); còn lại `null`. So khớp host chính xác sau khi bỏ `www.`/`m.`.
- `VideoPlayer({ contentId, title, onTimeChange?, ref? })` với `ref: { seek(seconds: number): void }`. Dùng lại ở lát 7.
- `CheckpointCard({ contentId, durationSeconds, source, onAddAt(seconds: number) })`: `onAddAt` mở hộp của Task 4.

- [x] **Step 1: Test đỏ.**
  - `video-embed.test.ts`: từng dạng link ở trên; `https://youtube.com.evil.com/watch?v=x`, `https://vimeo.com/abc`, `javascript:alert(1)` → `null`.
  - `video-player.test.tsx`: `asks for a fresh url when playback fails and restores the position` (đặt `currentTime = 42`, phát sự kiện `error` → gọi playback lần hai, sau `loadedmetadata` `currentTime === 42`); `stops retrying after one failed refresh` (hiện "Không phát được video." và nút "Thử lại", tổng số lần gọi playback là 2); `renders an embed iframe for an external link`; `offers a plain link when the url cannot be embedded` (`target="_blank" rel="noopener noreferrer"`).
  - `checkpoint-card.test.tsx` (thời lượng 600):
    - `lists checkpoints in time order with numbered markers` ("Tại 1:30 · <nhãn loại>");
    - `empty state` (chữ mockup);
    - `clicking the timeline picks the time` (bấm giữa thanh → "Thêm điểm dừng tại 5:00", `onAddAt(300)`);
    - `arrow keys move the picked time` (thanh có `role="slider"`, `aria-valuenow/min/max`; phải/trái ±1 giây, PageUp/Down ±10, Home/End);
    - `refuses a time closer than 3 seconds to another checkpoint` (toast "Đã có điểm dừng ở đây", `onAddAt` không gọi);
    - `removing sends the remaining set with ids` (body PUT đúng);
    - `confirm is shown only for needs_recheck` và gọi `/checkpoints/{id}/confirm`;
    - `over-duration checkpoint shows the badge without confirm`;
    - `a failed removal keeps the row and shows the message`.
- [x] **Step 2:** chạy → FAIL.
- [x] **Step 3:** hiện thực theo mockup dòng 1101–1128. Iframe có `title`, `allow="fullscreen; picture-in-picture"`, `sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"`, `referrerPolicy="strict-origin-when-cross-origin"`. Dải "Điểm dừng n: …" hiện khi thời điểm đang chọn cách một điểm dưới 3 giây.
- [x] **Step 4:** test `src/features/course-content` và kiến trúc → PASS; lint, typecheck.
- [x] **Step 5:** commit `feat: add video player and checkpoint timeline`, push.

---

### Task 4: Hộp "Câu hỏi cho điểm dừng"

**Files:**
- Create: `features/question-bank/ui/teacher/single-question-composer.tsx` (+ test); `features/course-content/ui/teacher/{checkpoint-question-dialog.tsx,checkpoint-bank-list.tsx,checkpoint-question-dialog.test.tsx}`
- Modify: `features/question-bank/{index.ts,api/types.ts,ui/teacher/question-card.tsx}`, `features/course-content/ui/teacher/{video-edit-screen,checkpoint-card}.tsx`

**Interfaces:**
- Consumes: `useSaveCheckpoints`, `toCheckpointInputs`, `onAddAt` (Task 3); `GET /questions?auto_gradable=1` (Task 0).
- Produces (export từ `@/features/question-bank`): `QuestionListFilters.auto_gradable?: boolean`; `QUESTION_TYPE_BADGE`; `SingleQuestionComposer({ allowedTypes: readonly QuestionType[], submitLabel: string, onSaved: (versionId: string) => Promise<void>, onCancel: () => void })`. `QuestionCard` thêm prop `allowedTypes?: readonly QuestionType[]` (không truyền thì như cũ).

Chữ theo mockup dòng 1261–1283: tiêu đề, mô tả, hai tab "Chọn từ ngân hàng" / "Tạo câu mới", nút "Thêm điểm dừng" / "Tạo và thêm điểm dừng", dòng nhắc câu mới ở chế độ Chung chép nguyên văn mockup. Rộng 600px.

- [x] **Step 1: Test đỏ.**
  - `single-question-composer.test.tsx`: `offers only the allowed types`; `does not call the api when the card is invalid`; `creates a shared question and passes its version id`; `retrying after onSaved fails does not create a second question` (POST `/questions` đúng 1 lần, `onSaved` 2 lần cùng `versionId`; thẻ bị khóa sau khi đã tạo).
  - `checkpoint-question-dialog.test.tsx`: `lists only auto gradable active questions` (query `auto_gradable=1&status=active`); `requires a selection` ("Chọn một câu từ ngân hàng."); `adds the picked question at the chosen time keeping existing checkpoints` (body PUT gồm dòng cũ có `id` và dòng mới `{at_seconds: 300, question_version_id}`); `shows the backend message and stays open` (422 `CHECKPOINT_TOO_CLOSE`); `new question tab creates then adds` (toast "Đã tạo câu và thêm điểm dừng").
- [x] **Step 2:** chạy → FAIL.
- [x] **Step 3:** hiện thực. `SingleQuestionComposer` dùng một `QuestionCard` (`showHead={false}`), cùng đường dữ liệu với `AssessmentQuestionComposer` (`applyShared(values, emptySharedSettings(), true)` → `toWriteInput(…, "create")`), lỗi BE qua `setServerErrors`. Đóng hộp rồi mở lại là trạng thái sạch.
- [x] **Step 4:** `pnpm vitest run src/features/course-content src/features/question-bank src/architecture.test.ts` → PASS; lint, typecheck.
- [x] **Step 5:** commit `feat: add checkpoint question dialog`, push.

---

### Task 5: Kiểm cuối nhánh

- [x] (lint còn một cảnh báo có từ trước ở `grading-scoring.test.tsx`) `pnpm lint && pnpm typecheck && pnpm build`; test các feature đã sửa và kiến trúc.
- [x] (đã chạy bằng tệp 25 MB chỉ có header MP4, và MP4 H.264 thật cỡ 250 KB trên Chrome) Chạy thật qua web (`localhost:8000`, api đã có Task 0), bằng HTTP: tải một MP4 thật lớn hơn 20 MB theo phần → complete 200; playback trả URL tương đối; `curl -r 0-1023` URL đó qua web → 206; ghi lại thời gian bước complete (0,08 giây với 25 MB).
- [x] Chạy trên trình duyệt thật: xem và tua video tải lên, link YouTube nhúng, thêm điểm dừng, rời trang khi đang tải. Không chạy được thì ghi rõ trong báo cáo.
- [x] So JSX với từng đoạn mockup đã ghi (kể cả màn hẹp 390px: lưới `g-split` về một cột).
- [x] Review độc lập cả nhánh (opus, effort medium), sửa một lượt.
- [x] Docs trong nhánh docs của lát: `STATE.md`, spec thêm mục "Ghi chú hiện thực FE lát 6" và sửa mục 5, 7, 13 theo Task 0; `BACKLOG.md` đóng `B-120`, phần FE của `B-111`; `error-codes.md` nếu có đổi; tick checkbox plan này.

---

## Không làm ở lát này

Driver S3 và `mode=direct` (`B-096`); tải tiếp sau khi đóng trang; HLS, phụ đề, ảnh đại diện video; kiểm codec H.264 ở server; tạo video độc lập từ TC-18; màn học sinh xem video (lát 7); dọn tải dở theo lịch (`B-105`).
