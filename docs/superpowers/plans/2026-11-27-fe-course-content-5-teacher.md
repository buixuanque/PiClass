# FE khóa học có nội dung học, lát 5 (Giáo viên: TC-15, TC-16, TC-18) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giáo viên quản lý mục khóa nhiều loại và cài đặt học (TC-15), soạn bài giảng kiểu Word (TC-16), quản lý và giao bài giảng độc lập (TC-18).

**Architecture:** Feature mới `features/course-content` (khớp module BE `CourseContent`), nhận phần mục khóa đang nằm ở `features/assessment`. Phụ thuộc một chiều: `academic → course-content → assessment, files`. `course-content` **không** import `academic`; danh sách lớp, học sinh, khóa được `academic` truyền vào qua props.

**Tech Stack:** Next.js 16, React 19, TanStack Query, React Hook Form + Zod, TipTap, Vitest + Testing Library + MSW.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 4, 6, 7, 9, 11, 12). Mockup: `piclass-mockup/design-system/PiClass Giáo viên.dc.html` (số dòng ghi ở từng Task).

## Global Constraints

- Repo `piclass-web`, nhánh `feat/course-content-teacher` từ `origin/main`. Đọc `piclass-web/CLAUDE.md`, `docs/rules/design-system.md`, `docs/rules/testing.md` trước khi code. Contract: `piclass-api/storage/api-docs/openapi.json`.
- Port đúng từng giá trị style, bố cục và chữ từ mockup; chỗ mockup không vẽ thì làm theo mục "Đã chọn thay mockup", không tự thêm.
- F1..F8; mỗi file một component chính, khoảng 200 dòng. Chữ giao diện tiếng Việt; lỗi 422 hiện nguyên văn của BE; rẽ nhánh theo `error.code`.
- Bài giảng: HTML tối đa 200 KB (BE chặn, FE không lặp rule). Ảnh JPEG/PNG ≤ 5 MB, upload `purpose=course_image`. Tài liệu PDF/Word/PowerPoint ≤ 50 MB, upload `purpose=course_document`.
- Thư viện mới chỉ có TipTap (`@tiptap/react`, `@tiptap/pm`, `@tiptap/starter-kit` và extension ảnh, bảng, căn lề, liên kết): ghim phiên bản chính xác, phát hành ít nhất hai tuần.
- Không dùng `dangerouslySetInnerHTML`; xem trước bằng TipTap ở chế độ chỉ đọc.
- Mỗi Task: `pnpm test`, `pnpm lint`, `pnpm typecheck` xanh rồi mới commit và push.

## Đã quyết (user, 2026-11-27, `DEC-LRN-056`): cả ba theo đề xuất, Task 0 đã làm

| # | Vấn đề | Đề xuất (plan viết theo đề xuất) | Ảnh hưởng nếu khác |
|---|---|---|---|
| Q1 | Mockup TC-16 có khung "Tệp đính kèm" nhưng API không gắn được tệp vào bài giảng: `POST /attachments/{id}/link` không có đích nội dung học, và `SyncLectureImages` coi mọi liên kết của bài giảng là ảnh trong bài | Làm BE trước (Task 0) | Hoãn thì bỏ Task 0 và khung đó, ghi backlog |
| Q2 | Mockup có tiêu đề chương trong danh sách mục nhưng hộp "Thêm mục" không có lựa chọn tạo chương | Thêm lựa chọn "Chương" ở cuối hộp thoại | Không thêm thì chỉ hiển thị chương có sẵn |
| Q3 | Mockup có điều kiện "Xong tất cả và đạt điểm tối thiểu" nhưng không có ô nhập điểm, trong khi API bắt buộc `completion_min_score` | Ô số 0..10 (tối đa 2 chữ số lẻ) hiện khi chọn điều kiện này | Không có cách nào khác để lưu được điều kiện này |

## Đã chọn thay mockup (user đổi được)

- Video: lát này **chưa** có lựa chọn "Video" khi thêm mục (tránh tạo video rỗng); mục video sẵn có vẫn hiện, không có nút "Sửa". Lát 6 bật.
- Huy hiệu trạng thái video và dòng phụ (thời lượng, số điểm dừng, số từ): API danh sách mục không trả, nên dòng phụ chỉ ghi loại mục (bài đánh giá giữ huy hiệu loại và trạng thái như hiện tại).
- TC-18 "Đã giao: …": danh sách chỉ có `audience_count`, hiện "Đã giao cho N đối tượng"; không gọi thêm API cho từng dòng.
- "Thêm vào khóa" (mockup chỉ là toast): hộp thoại chọn một khóa **của mình, chưa lưu trữ**.
- Tài liệu: "Sửa" chỉ đổi tên (API không thay được tệp, ghi `B-116`).
- Lưu trữ, khôi phục, xóa, tìm kiếm, lọc ở TC-18: mockup không có, chưa làm.
- Admin (`course-detail-screen`) tiếp tục dùng danh sách mục hiện tại, không thêm nút mới (AD-12 là lát 8).

## Review Focus

1. Mở bài giảng có ảnh rồi lưu mà không sửa: ảnh vẫn còn (`data-attachment-id` không bị trình soạn làm mất). → Task 4.
2. Rời trình soạn khi còn thay đổi chưa lưu: phải hỏi trước khi bỏ. → Task 4.
3. Dán từ Word có thẻ lạ, rồi lưu: nội dung sau lưu là bản BE đã làm sạch, trình soạn nạp lại đúng bản đó. → Task 4.
4. Sắp xếp khi người khác vừa thêm hoặc gỡ mục: `INVALID_ITEM_ORDER` hiện thông báo và tải lại danh sách, không kẹt thứ tự sai. → Task 2.
5. Tạo tài liệu thất bại sau khi đã upload tệp: tệp được xóa, không để mồ côi. → Task 3.

---

### Task 0: BE gắn tệp đính kèm cho bài giảng (**xong**)

Cột phân biệt: `attachment_links.content_usage`. Chi tiết: spec mục 16.

Repo `piclass-api`, nhánh và PR riêng, đi trước. Đụng phân quyền tệp nên review opus.

**Files:** `app/Modules/CourseContent/{Actions,Http}` (endpoint mới), `Actions/SyncLectureImages.php`, `app/Modules/File/Actions/SyncContentAttachments.php`, `routes/api.php`; test `tests/Feature/CourseContent/LectureFilesTest.php`.

**Interfaces — Produces:** `PUT /contents/{content}/attachments` body `{attachment_ids: uuid[]}` (thay cả tập tệp đính kèm, không tính ảnh trong bài), trả `LearningContentResource`; mỗi phần tử `attachments[]` thêm `kind: "image" | "file"`.

- [x] Test đỏ: `test_lecture_files_are_replaced_as_a_set`, `test_saving_body_html_keeps_lecture_files`, `test_replacing_files_keeps_inline_images`, `test_file_must_be_uploaded_by_actor_and_within_course_document_limits` (422), `test_non_manager_gets_404_or_403`, `test_only_lectures_accept_files` (422), `test_copy_to_course_copies_files`, `test_student_sees_files_of_open_lecture_only`.
- [x] Hiện thực: đồng bộ ảnh chỉ đụng liên kết ảnh trong bài, đồng bộ tệp chỉ đụng phần còn lại. Cần phân biệt hai loại liên kết một cách bền (chốt khi làm: cột trên `attachment_links` hoặc suy từ `body_html`); ghi lựa chọn vào spec.
- [x] `scramble:export`, Pint, PHPStan không thêm lỗi, cả bộ test, commit.

### Task 1: Tách mục khóa sang `features/course-content` (không đổi hành vi)

**Files:**
- Move từ `features/assessment/` sang `features/course-content/`: `api/course-items.ts` (+test), `ui/shared/course-items-section.tsx` (+test), `ui/shared/course-item-dialogs.tsx`, `ui/teacher/course-item-actions.tsx` (+test), `ui/teacher/course-item-pick-dialog.tsx`; type `CourseItem`, `UpdateCourseItemInput` sang `api/types.ts`; key `courseItems` sang `api/keys.ts`.
- Modify: `features/assessment/index.ts` (bỏ ba export; export thêm `useAssessments`, `CreateAssessmentDialog`, `ASSESSMENT_KIND`, `ASSESSMENT_STATUS`, type `AssessmentType`, `AssessmentStatus`), hai màn chi tiết khóa ở `features/academic`, `src/architecture.test.ts` (`FEATURES` thêm `course-content`).

**Interfaces — Produces:** `@/features/course-content` export `CourseItemsSection`, `CourseItemActions`, `useCourseItems`, type `CourseItem`.

- [ ] Chuyển file và sửa import; không sửa nội dung test ngoài đường dẫn.
- [ ] `pnpm test`, `pnpm lint`, `pnpm typecheck` xanh, F5 không báo vòng. Commit `refactor: move course items into course-content feature`.

### Task 2: TC-15 tab Nội dung và cài đặt học

Mockup dòng 1214–1236 (markup), 2301–2323 (`ci`).

**Files:** Create `course-content/api/{course-settings.ts,item-kind.ts}`, `ui/teacher/{course-learning-settings.tsx,course-content-tab.tsx,course-content-row.tsx}` và test `course-content-tab.test.tsx`; Modify `api/course-items.ts`, `api/types.ts`, `academic/ui/teacher/teacher-course-detail-screen.tsx` (tab "Bài học" → "Nội dung"), `academic/api/types.ts` (`CourseDetail` thêm ba trường cài đặt).

**Interfaces — Produces:**
- `type CourseItemType = "assessment" | "lecture" | "document" | "video" | "chapter"`; `CourseItem` thêm `type: CourseItemType`, `content_id: string | null`, `content_type`.
- `courseItemsApi.reorder(courseId: string, itemIds: string[]): Promise<CourseItem[]>` → `PUT /courses/{id}/items/order {item_ids}`; hook `useReorderCourseItems(courseId)`.
- `type CompletionRule = "all_items" | "all_items_and_min_score"`; `useUpdateLearningSettings(courseId)` → `PATCH /courses/{id}` `{sequential_unlock?, completion_rule?, completion_min_score?}`; thành công thì gọi callback `onSaved` để `academic` làm mới query của khóa.
- `<CourseContentTab courseId settings={{sequential_unlock, completion_rule, completion_min_score}} onSettingsSaved readOnly editHref={(item) => string | null} gradingHref={(item) => string | null} />`.

- [ ] Test đỏ: `renders chapters as headers and numbers no chapter`; `moving an item sends the full id order`; `reloads the list and shows the message on INVALID_ITEM_ORDER`; `toggling sequential unlock patches only that field`; `choosing the score rule asks for a minimum score before saving` (Q3); `archived course hides add, move and remove controls`; `count line shows item and video totals without chapters`; `assessment rows keep grading link`.
- [ ] Hiện thực; `CourseItemsSection` cũ chỉ còn Admin và khóa dùng chung dùng (hiện đủ năm loại mục không vỡ).
- [ ] Xanh, so mockup dòng 1214–1236, commit.

### Task 3: Hộp "Thêm mục vào khóa" và tạo tài liệu

Mockup dòng 1251–1259, 2314–2323 (`addOpts`: icon, màu, chữ).

**Files:** Create `api/contents.ts`, `ui/teacher/{add-item-dialog.tsx,add-document-dialog.tsx,add-chapter-dialog.tsx,copy-lecture-dialog.tsx}` + test `add-item-dialog.test.tsx`; Modify `features/files/api/attachments.ts` (`upload(file, purpose?: "course_document" | "course_image")`), `files/index.ts` (export `attachmentsApi`), `course-item-actions.tsx` (thay bằng một nút "Thêm mục"; Admin vẫn thấy hai nút cũ).

**Interfaces — Produces:**
- `contentsApi.createItem(courseId, {type: "lecture"|"document"|"chapter", title: string, body_html?: string, attachment_id?: string}): Promise<CourseItem>`
- `contentsApi.listStandalone({type?, search?, per_page?}): Promise<Paginated<StandaloneContent>>`, `StandaloneContent = {id, type, title, status, owner_user_id, audience_count, created_at, updated_at}`
- `contentsApi.copyToCourse(contentId, courseId): Promise<CourseItem>`
- Lựa chọn theo thứ tự: Bài giảng chữ, Tài liệu, Bài đánh giá (hai lối: soạn mới, lấy từ bài của tôi, dùng lại hộp thoại cũ), Lấy từ bài giảng của tôi, Chương (Q2).

- [ ] Test đỏ: `offers no video option yet`; `creating a lecture opens its editor route`; `document uploads with purpose course_document then creates the item`; `deletes the uploaded file when creating the item fails and shows the server message`; `copy from my lectures calls copy-to-course with this course id`; `adds a chapter with only a title`.
- [ ] Hiện thực, xanh, so mockup, commit.

### Task 4: TC-16 soạn bài giảng

Mockup dòng 1040–1072 (markup, hộp xem trước), 73 trở đi (CSS `.lec-doc`, `.lec-tb`), 2381–2399 (`tools`, `le`).

**Files:** Create `api/lecture.ts`, `ui/shared/{lecture-editor.tsx,lecture-extensions.ts,lecture-viewer.tsx,lecture-content.css}`, `ui/teacher/{lecture-edit-screen.tsx,lecture-toolbar.tsx,lecture-files-card.tsx,use-unsaved-guard.ts}` + test `lecture-edit-screen.test.tsx`, `lecture-extensions.test.ts`; routes `app/(portal)/teacher/courses/[courseId]/contents/[contentId]/edit/page.tsx`, `app/(portal)/teacher/lectures/[contentId]/edit/page.tsx`, `app/(portal)/teacher/lectures/new/page.tsx`; Modify `package.json`.

**Interfaces — Produces:**
- `useLearningContent(id)` → `GET /contents/{id}`: `{id, type, course_id, title, body_html: string|null, attachments: {id, file_name, media_type, size_bytes, download_url, kind}[], video}`
- `useUpdateLearningContent(id)` → `PATCH /contents/{id} {title?, body_html?}`; `useCreateStandaloneContent()` → `POST /contents`; `useSetLectureFiles(id)` → `PUT /contents/{id}/attachments`
- `lectureExtensions(): Extensions` (đúng danh sách thẻ BE giữ: `h2 h3 p b i u s ul ol li blockquote table a img br`, `text-align`; tiêu đề chỉ cấp 2, 3; ảnh giữ thuộc tính `data-attachment-id`, `alt`, `src`)
- `<LectureViewer html />` (TipTap chỉ đọc; lát 7 dùng lại)
- `<LectureEditScreen contentId={string|null} courseId={string|null} />`

- [ ] Test đỏ cho extensions (hàm thuần): `round trips image data-attachment-id`; `drops script, iframe and h1`; `keeps text-align and tables`.
- [ ] Test đỏ màn hình: `loads title and body from the server`; `save sends title and html then reloads the sanitized body`; `shows LECTURE_TOO_LARGE and LECTURE_IMAGE_NOT_ALLOWED messages and keeps the draft`; `empty title is rejected before calling the api`; `inserting an image uploads with purpose course_image`; `asks before leaving with unsaved changes`; `files card attaches and removes through the set endpoint` (Q1); `new standalone lecture is created on first save and moves to its edit route`.
- [ ] Ảnh và tệp trên bài **chưa lưu lần đầu**: khung "Tệp đính kèm" tắt cho tới khi bài được tạo; ảnh chèn trước đó được gắn khi `POST /contents` gửi `body_html`.
- [ ] Hiện thực, `pnpm build` xanh, so mockup, commit.

### Task 5: TC-18 "Bài giảng của tôi"

Mockup dòng 1022–1038, 1244–1250 (hộp "Giao"), 2360–2380 (`lt`).

**Files:** Create `ui/teacher/{lecture-list.tsx,lecture-list-row.tsx,content-assign-dialog.tsx,content-copy-to-course-dialog.tsx}` + test `lecture-list.test.tsx`; `api/content-audience.ts`; `academic/ui/teacher/teacher-lectures-screen.tsx` (ghép dữ liệu lớp, học sinh, khóa); route `app/(portal)/teacher/lectures/page.tsx`; Modify `config/nav.ts` (giáo viên thêm `{label: "Bài giảng", href: "/teacher/lectures", icon: "presentation"}`, vị trí theo menu của mockup).

**Interfaces:**
- Consumes: `useClasses`, `useCourses` (academic), `useMembersOfClasses` (user-management), `contentsApi` (Task 3).
- Produces: `contentAudienceApi.list(id)`, `.add(id, {class_id} | {student_id})`, `.remove(id, audienceId)`; dòng: `{id, class_id, class_name, student_id, student_name, created_at}`.
- `<LectureList targets={AssignTarget[]} courses={{id, name}[]} />`, `AssignTarget = {kind: "class"|"student", id: string, label: string}`.

- [ ] Test đỏ: `lists standalone contents with type icon and assignment badge`; `paginates`; `assign dialog preselects current audience`; `saving the dialog adds new targets and removes unchecked ones`; `shows the server message when one add fails and keeps the dialog in sync with the server`; `copy to course lists only own active courses`; `document row edit only renames`; `empty state offers writing a lecture`.
- [ ] Lưu đối tượng là nhiều request tuần tự (API không thay cả tập): dừng ở lỗi đầu, tải lại danh sách đối tượng.
- [ ] Hiện thực, xanh, so mockup, commit.

### Task 6: Kiểm cuối nhánh

- [ ] `pnpm test && pnpm lint && pnpm typecheck && pnpm build`.
- [ ] Tự so JSX với từng đoạn mockup đã ghi; chạy thật trên `localhost:8000` luồng: thêm bài giảng có ảnh vào khóa → sắp xếp → bật học theo thứ tự → soạn bài độc lập → giao lớp → thêm vào khóa.
- [ ] Review độc lập cả nhánh (opus, effort medium); sửa; cập nhật `STATE.md`, `api-gaps.md`, spec.

---

## Lát sau (viết plan chi tiết khi tới lượt)

| Lát | Nội dung | Ràng buộc đã biết |
|---|---|---|
| 6 | TC-17: tải video theo phần 5 MB, link ngoài, phát thử, điểm dừng, xác nhận lại sau khi thay tệp; tạo câu mới dùng lại trình soạn TC-08 | Bật lựa chọn "Video" ở Task 3. Dòng mục khóa cần trạng thái, thời lượng, số điểm dừng: hoặc BE bổ sung, hoặc bỏ huy hiệu. Xem `B-105..B-107`, `B-111` |
| 7 | ST-09, ST-10, ST-11, ST-12 | **`B-114` (user chốt 2026-11-27):** bài đánh giá bị khóa phải cho học sinh thấy cần làm mục nào trước (`error.code=CONTENT_LOCKED`, `details.blocked_by`) ở mọi lối vào, kể cả từ "Bài của tôi" (ST-05), không hiện lỗi chung. `GET /me/assessments` chưa có cờ `locked`. Dùng lại `LectureViewer`. Không có `GET /me/courses` (danh sách) |
| 8 | TC-19, AD-12 tab Nội dung, tiến độ của con cho Phụ huynh | Phần Lớp, Học viên của mockup TC-15 và AD-12 theo mô hình cũ, không dùng. Chưa có mockup khóa cho Phụ huynh (`B-098`) |
