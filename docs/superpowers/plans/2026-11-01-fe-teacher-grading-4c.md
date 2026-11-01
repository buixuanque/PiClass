# FE lát 4c Giáo viên (TC-11, TC-12, TC-13): chi tiết bài, bài nộp, chấm bài Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng ba màn của cổng Giáo viên: chi tiết bài (TC-11, có tab Bài nộp TC-12), chấm bài (TC-13) và hộp công bố điểm, nối vào API đã merge (`piclass-api` #75); kèm seeder dữ liệu mẫu để xem trên màn thật.

**Architecture:** Theo cấu trúc feature của `piclass-web/CLAUDE.md`. `assessment` giữ TC-11 (vòng đời bài, đối tượng, học sinh) và nhúng tab Bài nộp lấy từ `grading`; `grading` (mới, module BE SubmissionGrading) giữ danh sách bài nộp, chấm bài, nhận xét; `results` (đã có) thêm công bố/rút kết quả và hộp công bố; `files` (mới, module BE File) giữ upload, xem và gỡ file. Phụ thuộc một chiều `assessment → grading → results/files`, chỉ qua `index.ts` (F5).

**Tech Stack:** Next.js 16, React 19, TypeScript 6, TanStack Query, React Hook Form + Zod, Vitest + MSW + Testing Library.

**Spec:** `docs/product/screens/teacher-bai-danh-gia.md` TC-11…13 và mục "Cập nhật BE lát 4c"; `docs/requirements/decision-log.md` `DEC-LRN-027`; mockup đã merge `piclass-mockup` #28, file `design-system/PiClass Giáo viên.dc.html` (TC-11/12 dòng ~392-460, TC-13 ~462-560, hộp thoại ~1199-1240; logic dòng ~1330-1580, 1883-1975); OpenAPI `piclass-api/storage/api-docs/openapi.json` (không track, tạo bằng `docker compose exec api php artisan scramble:export` ở `piclass-infra/local`).

## Global Constraints

- Nhánh từ `origin/main`: FE `feat/teacher-grading-4c-fe` (`piclass-web`), BE seeder `feat/teacher-grading-demo-seed` (`piclass-api`, Task 12), docs `docs/teacher-grading-4c-fe`. Không push `main`, không mở PR hay merge khi user chưa nói.
- Quy tắc F1–F8 của `piclass-web/CLAUDE.md`: route mỏng, chỉ `lib/api-client.ts` gọi `fetch`, `api/` không biết UI, `ui/teacher` không import `ui/admin`, feature khác chỉ qua `index.ts`, FE không tự quyết quyền (ẩn/hiện theo dữ liệu BE), thời gian qua `lib/time.ts`, mỗi file một component chính, cảnh báo ESLint khi quá 200 dòng.
- Port UI **đúng từng giá trị** từ mockup (đọc `docs/rules/design-system.md` trước); nghi ngờ chỗ nào ghi vào `docs/architecture/open-decisions.md`, không tự làm tròn.
- Comment code và mô tả test tiếng Việt; tên hàm, component, file, test method, commit tiếng Anh; chữ giao diện tiếng Việt, xưng "cô" như các màn Giáo viên hiện có.
- **"Hẹn giờ công bố" hiện nhưng chưa dùng được** (user chốt 2026-11-01): scheduler chưa chạy, nên hộp công bố vẫn có lựa chọn "Hẹn giờ công bố" như mockup; chọn vào thì không mở ô ngày giờ, chỉ hiện thông báo "Tính năng hẹn giờ đang phát triển" và giữ nguyên "Công bố ngay". FE không bao giờ gửi `publish_at`. Khi có scheduler thì bật ô ngày giờ và gửi `publish_at`.
- Tham số boolean của query đã gửi `1`/`0` (api-client, `piclass-web` #45); body JSON gửi boolean thật.
- Giới hạn file bài làm và nhận xét (1 file, 5 MB, ảnh/PDF/Word) do BE canh (`ATTACHMENT_LIMIT_EXCEEDED`, `ATTACHMENT_NOT_ALLOWED`); FE chỉ gợi ý trong chú thích và hiện nguyên văn lỗi BE, rẽ nhánh theo `error.code`.
- Phát hiện BE thiếu hoặc bất tiện: ghi vào `docs/product/screens/api-gaps.md`, không vá bằng FE phức tạp.
- Mỗi task cuối: `pnpm vitest run <đường dẫn>`, `pnpm lint`, `pnpm tsc --noEmit` xanh. Cuối plan: `pnpm test`, `pnpm build`. Review cuối nhánh: subagent **model `opus`** tường minh.

## Review Focus

- Giáo viên không phải người tạo bài và không phụ trách: BE trả 404 thì mọi màn hiện "không tìm thấy", không hiện nút ghi; bài của người khác chỉ xem (BE trả quyền qua dữ liệu, FE không so sánh `created_by_user_id` với người dùng).
- Hộp công bố: không bao giờ gửi `publish_at`, chọn "Hẹn giờ công bố" chỉ hiện thông báo đang phát triển và không đổi cách công bố; ba cờ gửi đúng giá trị công tắc; công bố hàng loạt hiện đúng "đã công bố / bỏ qua" từ `published`/`skipped`; kết quả đã công bố sẵn không lỗi (BE idempotent).
- Sửa điểm khi kết quả đã `published`: bắt buộc ô lý do, gửi `reason`, hiện cảnh báo; chưa công bố thì ẩn ô lý do.
- Điểm tổng thay điểm từng câu: hiện cảnh báo "Điểm tổng đang thay cho điểm từng câu"; điểm ngoài khoảng 0…tối đa hiện lỗi BE theo trường `score`, không gọi trùng.
- Bài nộp `invalidated`: trang chỉ đọc, ẩn mọi nút chấm/công bố, có liên kết sang lượt thay thế.
- File bài làm: nút "Xem file" lấy URL có chữ ký lúc bấm (không lưu URL), mở tab mới; 404 hiện "Không xem được file"; nhận xét kèm file chỉ cho 1 file và cho gỡ khi còn nháp.
- Danh sách học sinh theo bài: dòng ảo của khóa (`id` null) vẫn hiện tên và "Chưa làm", không có nút điều chỉnh (chưa có `student_assessment`).
- Đề hiển thị là đề đúng phiên bản học sinh đã làm (`submission.version`), không lấy từ `GET /assessments/{id}`.

---

### Task 1: Tầng dữ liệu vòng đời bài và học sinh theo bài (assessment)

**Files:**
- Modify: `src/features/assessment/api/types.ts`, `keys.ts`, `assessments.ts`, `index.ts`
- Create: `src/features/assessment/api/students.ts`
- Test: `src/features/assessment/api/lifecycle.test.tsx`, `students.test.tsx`

**Interfaces:**
- Produces (types): `AssessmentStudent` = `{ id: string | null; assessment_id; student_id; assigned_version_id; status: "assigned" | "withdrawn" | "completed"; individual_deadline_at: string | null; attempt_limit_override: number | null; student: { id; full_name }; attempts_used: number; progress: "not_started" | "in_progress" | "submitted"; latest_submission_id: string | null }`; `StudentOverrideInput` = `{ individual_deadline_at?: string | null; attempt_limit_override?: number | null }`; `ReopenInput` = đúng một trong `{ student_ids: string[] } | { class_id: string } | { course_id: string }`.
- Produces (hooks, mỗi cái làm mới `assessmentKeys.all()`): `useCloseAssessment(id)`, `useReopenAssessment(id)` (nhận `ReopenInput`), `useCancelAssessment(id)` (nhận `{ reason: string }`), `useCreateAssessmentVersion(id)`; `useAssessmentStudents(id)`; `useUpdateAssessmentStudent(id)` (nhận `{ studentId, input }`). Endpoint: `POST /assessments/{id}/close|reopen|cancel|versions`, `GET /assessments/{id}/students`, `PATCH /assessments/{id}/students/{studentId}`.

- [ ] **Step 1: Viết test MSW cho từng hook** (đúng URL, method, body; `cancel` gửi `reason`; `reopen` gửi đúng một khóa; `students` trả `data[]` có `student.full_name`, `progress`).
- [ ] **Step 2: Chạy `pnpm vitest run src/features/assessment/api`, mong đợi FAIL (hook chưa có).**
- [ ] **Step 3: Viết type, key `students(id)`, hàm gọi và hook theo Interfaces; export hook cần cho nhánh khác ở `index.ts`.**
- [ ] **Step 4: Chạy lại, mong đợi PASS; chạy `pnpm tsc --noEmit`.**
- [ ] **Step 5: Commit** `feat(assessment): lifecycle and students data layer for TC-11`.

### Task 2: Khung TC-11, route, tab Tổng quan và Câu hỏi

**Files:**
- Create: `src/app/(portal)/teacher/assessments/[assessmentId]/page.tsx`, `src/features/assessment/ui/teacher/assessment-detail-screen.tsx`, `assessment-detail-header.tsx`, `assessment-overview-tab.tsx`, `assessment-detail-questions-tab.tsx`
- Modify: `src/features/assessment/ui/teacher/assessment-list-row.tsx` (dòng bài không phải nháp của mình, và bài của người khác, dẫn tới TC-11; nháp của mình vẫn "Soạn tiếp"), `src/features/assessment/index.ts`
- Test: `assessment-detail-screen.test.tsx`

**Interfaces:**
- Consumes: `useAssessment(id)` (đã có).
- Produces: `AssessmentDetailScreen({ assessmentId }: { assessmentId: string })`; route `/teacher/assessments/[assessmentId]` ghép mỏng (F1). Tab hiện qua `Tabs` với giá trị `overview | questions | audience | students | submissions`; Task 3, 4, 8 gắn nội dung vào các tab còn lại.

- [ ] **Step 1: Viết test**: tải xong hiện tên bài, nhãn loại, nhãn trạng thái và phiên bản; tab Tổng quan có hàng "Công bố điểm" (giữ lại, công bố ở tab Bài nộp) và **không** có hàng "Học sinh được xem"; tab Câu hỏi liệt kê câu theo `position` với điểm; 404 của BE hiện thông báo không tìm thấy; bài `cancelled` hiện lý do hủy; bản nháp mới hiện banner kèm câu "học sinh đã được giao giữ bản cũ" (`DEC-LRN-027` mục 6).
- [ ] **Step 2: Chạy test, mong đợi FAIL.**
- [ ] **Step 3: Dựng header, hai tab và màn theo mockup (`isAsmDet`, `awInfo`, `awQRows`); thêm route; sửa dòng danh sách để dẫn tới TC-11.**
- [ ] **Step 4: Chạy test, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(assessment): TC-11 shell with overview and questions tabs`.

### Task 3: Nút vòng đời và hộp thoại (Sửa, Phiên bản mới, Đóng, Mở lại, Hủy)

**Files:**
- Create: `src/features/assessment/ui/teacher/assessment-lifecycle-actions.tsx`, `assessment-reopen-dialog.tsx`, `assessment-cancel-dialog.tsx`
- Modify: `assessment-detail-header.tsx`
- Test: `assessment-lifecycle-actions.test.tsx`

**Interfaces:**
- Consumes: các hook Task 1; `useAssessment` cho trạng thái.
- Produces: `AssessmentLifecycleActions({ assessment }: { assessment: Assessment })`. Nút hiện theo `status` và `current_version.status` của bài (nháp: "Sửa và phát hành"; đã phát hành hoặc đóng, không có nháp mới: "Soạn phiên bản mới" gọi `useCreateAssessmentVersion` rồi chuyển `/teacher/assessments/{id}/edit`; `published`: "Đóng bài" với xác nhận; `closed`: "Mở lại" chọn nhóm một trong học sinh / một lớp / một khóa; mọi trạng thái trừ `cancelled`: "Hủy bài" bắt buộc lý do). Quyền ghi do BE quyết: lỗi 403/409 hiện nguyên văn `error.message`.

- [ ] **Step 1: Viết test**: mỗi trạng thái hiện đúng tập nút; Đóng gửi `POST …/close` sau khi xác nhận; Mở lại gửi đúng một trong ba khóa; Hủy bỏ trống lý do thì hiện lỗi trường và không gọi API, có lý do thì gửi `reason`; 409 "Đã có bản draft đang mở." hiện nguyên văn; thành công thì làm mới chi tiết.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo Interfaces, hộp thoại dùng `components/ui/dialog` như `assessment-publish-dialog.tsx`.**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(assessment): TC-11 lifecycle actions`.

### Task 4: Tab Đối tượng và tab Học sinh (kèm gia hạn / điều chỉnh)

**Files:**
- Create: `src/features/assessment/ui/teacher/assessment-detail-audience-tab.tsx`, `assessment-students-tab.tsx`, `assessment-student-adjust-dialog.tsx`
- Test: `assessment-students-tab.test.tsx`, `assessment-student-adjust-dialog.test.tsx`

**Interfaces:**
- Consumes: `useAssessmentAudience` (đã có, `audience.ts`), `useAssessmentStudents`, `useUpdateAssessmentStudent` (Task 1).
- Produces: `AssessmentStudentsTab({ assessmentId })`; mỗi dòng: tên, nhãn tiến độ (`not_started` "Chưa làm", `in_progress` "Đang làm", `submitted` "Đã nộp"), "Đã dùng n lượt", nhãn "Đã rút" khi `status = withdrawn`; nút "Gia hạn / điều chỉnh" chỉ khi `id` khác null và `status = assigned`, mở hộp thoại hai trường tùy chọn (hạn nộp riêng, số lượt tối đa riêng; xóa ô = gửi `null`); lỗi `422` hiện dưới trường tương ứng ("Số lượt tối thiểu là 1"); ghi chú "hạn riêng không kéo dài giờ đóng bài".

- [ ] **Step 1: Viết test**: dòng ảo của khóa có tên, "Chưa làm", không có nút điều chỉnh; dòng `withdrawn` không có nút; hộp thoại gửi `PATCH …/students/{student_id}` (không phải `id` của dòng) với đúng trường đã đổi và `null` khi xóa; 404/422 hiện lỗi; tab Đối tượng rỗng hiện "Chưa giao cho ai".
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo mockup (`awTabAud`, `awTabStu`, `awStuRows`, `adj`); gắn hai tab vào `AssessmentDetailScreen`.**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(assessment): TC-11 audience and students tabs`.

### Task 5: Feature `files`: xem, gỡ và gắn file

**Files:**
- Create: `src/features/files/api/types.ts`, `keys.ts`, `attachments.ts`, `src/features/files/ui/shared/attachment-chip.tsx`, `src/features/files/index.ts`
- Modify: `src/architecture.test.ts` (nếu khai danh sách feature)
- Test: `attachments.test.tsx`, `attachment-chip.test.tsx`

**Interfaces:**
- Produces: `AttachmentFile` = `{ attachment_id: string; file_name: string; media_type: string; size_bytes: number }`; `attachmentsApi.upload(file: File)` (`POST /attachments`, multipart, trả `{ attachment }`), `attachmentsApi.link(attachmentId, target: { feedback_id: string; visibility: FeedbackVisibility } | { answer_id: string; visibility: "private" | "parent_if_published" })` (`POST /attachments/{id}/link`), `attachmentsApi.remove(id)` (`DELETE /attachments/{id}`), `attachmentsApi.downloadUrl(id)` (`GET /attachments/{id}`, trả `download_url`); hook `useUploadAndLinkAttachment()`, `useDeleteAttachment()`; component `AttachmentChip({ file, onRemove? })` hiện tên, dung lượng, nút "Xem file" (lấy URL lúc bấm, `window.open` tab mới, lỗi 404 hiện toast "Không xem được file") và nút gỡ khi có `onRemove`.
- Cần `lib/api-client.ts` gửi `FormData` không đặt `Content-Type`; nếu chưa hỗ trợ, thêm kèm test trong task này.

- [ ] **Step 1: Viết test**: upload gửi multipart; link gửi đúng khóa đích; `AttachmentChip` bấm "Xem file" gọi `GET /attachments/{id}` đúng một lần và mở URL trả về; 404 hiện thông báo; không render URL ra DOM.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo Interfaces; kiểm `architecture.test.ts` cho feature mới.**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(files): attachment data layer and chip`.

### Task 6: Công bố kết quả (results) và hộp công bố

**Files:**
- Create: `src/features/results/api/publish.ts`, `src/features/results/ui/teacher/publish-results-dialog.tsx`
- Modify: `src/features/results/api/types.ts` (hoặc tạo), `keys.ts`, `src/features/results/index.ts`
- Test: `publish.test.tsx`, `publish-results-dialog.test.tsx`

**Interfaces:**
- Produces: `PublishFlags` = `{ show_answers: boolean; show_explanations: boolean; show_question_scores: boolean }`; `BulkPublishOutcome` = `{ published: string[]; scheduled: string[]; skipped: { id: string; reason: string }[] }`; hook `usePublishResult()` (nhận `{ resultId, flags }`, `POST /results/{id}/publish`), `useWithdrawResult()` (`POST /results/{id}/withdraw`), `useBulkPublishResults(assessmentId)` (nhận `flags`, `POST /assessments/{id}/results/publish`); mỗi mutation làm mới các key bài nộp và chi tiết bài nộp (key do `grading` quản lý: nhận `onSuccess` từ nơi gọi hoặc dùng `queryClient.invalidateQueries` theo tiền tố đã thống nhất `["grading"]`). Component `PublishResultsDialog({ open, onClose, title, description, defaultFlags, onConfirm })` với ba công tắc (đáp án đúng, lời giải, điểm từng câu) khởi tạo từ `defaultFlags`, chú thích "Chọn theo từng lần công bố. Bài thi nên mở đáp án sau khi cả lớp thi xong."; có `Select` "Khi nào công bố" gồm "Công bố ngay" và "Hẹn giờ công bố"; chọn "Hẹn giờ công bố" thì không đổi giá trị, hiện dòng "Tính năng hẹn giờ đang phát triển" (và `onConfirm` luôn công bố ngay), không có ô ngày giờ.

- [ ] **Step 1: Viết test**: hook gửi đúng body ba cờ và **không** có `publish_at`; hộp thoại khởi tạo công tắc từ `defaultFlags`, bấm xác nhận gọi `onConfirm(flags)` với giá trị đã đổi; chọn "Hẹn giờ công bố" hiện "Tính năng hẹn giờ đang phát triển", không render ô ngày giờ, và xác nhận vẫn công bố ngay không có `publish_at`.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo Interfaces và mockup (hộp thoại `dPub`, `dPubAll`, khối công tắc).**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(results): publish and withdraw results with display flags`.

### Task 7: Tầng dữ liệu bài nộp và chấm bài (grading)

**Files:**
- Create: `src/features/grading/api/types.ts`, `keys.ts`, `submissions.ts`, `grades.ts`, `feedback.ts`, `src/features/grading/index.ts`
- Test: `submissions.test.tsx`, `grades.test.tsx`, `feedback.test.tsx`

**Interfaces:**
- Produces (types, theo OpenAPI và mục "Cập nhật BE lát 4c"): `SubmissionRow` (cột bài nộp + `result: SubmissionResult | null` + `student {id, full_name}` + `student_assessment_id` + `attempt_number`); `SubmissionResult` = `{ id; score; max_score; percentage; passed; grading_status: "pending" | "partial" | "complete"; publication_status: "unpublished" | "published"; show_answers; show_explanations; show_question_scores; scheduled_publish_at }`; `SubmissionDetail` (`attempt` có `answers[]` kèm `files[]`, `grades[]`, `result`, `feedback[]` kèm `author {id,name}` và `files[]`, `student`, `version {id, version_number, title, max_score, questions[]}`).
- Produces (hooks): `useSubmissions(assessmentId, { grading_status? })` → `GET /assessments/{id}/submissions`; `useSubmission(id)` → `GET /submissions/{id}`; `useGradeQuestion(submissionId)` (`PUT /submissions/{id}/questions/{assessmentQuestionId}/grade`, body `{ score, reason? }`, trả `{ grade, result }`); `useGradeSubmission(submissionId)` (`PUT /submissions/{id}/grade`); `useCreateFeedback(submissionId)` (`POST /submissions/{id}/feedback` `{ body, visibility, assessment_question_id? }`), `useUpdateFeedback()` (`PUT /feedback/{id}` `{ body }`), `useSetFeedbackStatus()` (`PATCH /feedback/{id}` `{ status: "published" | "withdrawn" }`). Mọi mutation làm mới `["grading"]`.

- [ ] **Step 1: Viết test MSW cho từng hook** (URL, method, body, key làm mới; `reason` chỉ gửi khi có).
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Viết type, key, hàm và hook theo Interfaces.**
- [ ] **Step 4: Chạy, mong đợi PASS; `pnpm tsc --noEmit`.**
- [ ] **Step 5: Commit** `feat(grading): submissions, grading and feedback data layer`.

### Task 8: Tab Bài nộp (TC-12) trong TC-11

**Files:**
- Create: `src/features/grading/ui/teacher/assessment-submissions-tab.tsx`, `submission-row.tsx`
- Modify: `src/features/grading/index.ts` (export `AssessmentSubmissionsTab`), `assessment-detail-screen.tsx` (gắn tab, đếm số ở nhãn tab nếu mockup có)
- Test: `assessment-submissions-tab.test.tsx`

**Interfaces:**
- Consumes: `useSubmissions` (Task 7), `useBulkPublishResults`, `usePublishResult`, `useWithdrawResult`, `PublishResultsDialog` (Task 6) qua `@/features/results`.
- Produces: `AssessmentSubmissionsTab({ assessmentId }: { assessmentId: string })`. Lọc trạng thái chấm (gửi `grading_status`) và trạng thái công bố (lọc ở FE theo `result.publication_status`); nút "Công bố tất cả đã chấm xong (n)" mở hộp công bố, kết quả hiện "Đã công bố x kết quả. Bỏ qua y bài vì chưa chấm xong."; dòng hiện tên, giờ nộp, nhãn "Nộp trễ", trạng thái bài nộp, điểm và phần trăm, Đạt/Chưa đạt, trạng thái chấm, trạng thái công bố, nút "Công bố"/"Rút" theo từng dòng; bấm dòng đi `/teacher/assessments/{assessmentId}/submissions/{id}`; dòng `invalidated` mờ, ghi "Đã cấp lại lượt".

- [ ] **Step 1: Viết test**: lọc gửi đúng query; công bố tất cả gửi ba cờ, hiện tóm tắt từ `published`/`skipped`; nút "Công bố" chỉ khi `grading_status = complete` và chưa công bố; rỗng ("Chưa có bài nộp") và rỗng sau lọc; dòng vô hiệu không có nút.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo mockup (`awTabSubs`, `subTblRows`, `bulkAsk`); gắn vào TC-11.**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(grading): TC-12 submissions tab`.

### Task 9: TC-13 phần đọc: route, đầu trang, bài làm từng câu, xem file

**Files:**
- Create: `src/app/(portal)/teacher/assessments/[assessmentId]/submissions/[submissionId]/page.tsx`, `src/features/grading/ui/teacher/grading-screen.tsx`, `grading-header.tsx`, `grading-question-card.tsx`, `answer-view.tsx`
- Modify: `src/features/grading/index.ts`
- Test: `grading-screen.test.tsx`, `answer-view.test.tsx`

**Interfaces:**
- Consumes: `useSubmission` (Task 7), `AttachmentChip` (Task 5).
- Produces: `GradingScreen({ assessmentId, submissionId })`; `AnswerView({ question, answer })` hiện đáp án học sinh đúng từng loại câu (lựa chọn, đúng/sai, điền khuyết, trả lời ngắn, nối, sắp xếp, tự luận, nộp file) kèm dấu đúng/sai và dòng đáp án đúng; câu bỏ trống "Không trả lời — 0 điểm"; câu `file_upload` hiện `AttachmentChip` cho `answer.files[0]`; đầu trang: tên học sinh, tên bài, "Lượt n", giờ nộp, nhãn nộp trễ, trạng thái chấm và công bố, điểm tổng; bài `invalidated` hiện biểu ngữ lý do hủy và liên kết lượt thay thế, chỉ đọc.

- [ ] **Step 1: Viết test**: câu trắc nghiệm đúng/sai hiển thị đúng; câu của phiên bản cũ hiện đúng đề từ `submission.version` khi bài đã có phiên bản mới (MSW trả hai phiên bản khác nhau); file hiện chip và không hiện URL; `invalidated` ẩn vùng chấm; 404 hiện không tìm thấy.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo mockup (`isGrade`, `gQRows`) và thêm route mỏng.**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(grading): TC-13 read view with answers and files`.

### Task 10: TC-13 chấm điểm, công bố, rút, "Lưu và chấm bài kế"

**Files:**
- Create: `src/features/grading/ui/teacher/grading-score-card.tsx`, `question-score-form.tsx`
- Modify: `grading-screen.tsx`, `grading-question-card.tsx`
- Test: `grading-score-card.test.tsx`, `question-score-form.test.tsx`

**Interfaces:**
- Consumes: `useGradeQuestion`, `useGradeSubmission`, `useSubmissions` (để tìm bài kế), `usePublishResult`, `useWithdrawResult`, `PublishResultsDialog`.
- Produces: ô điểm mỗi câu (số, bước 0,5, khoảng 0…điểm câu; lỗi BE theo `score` hiện dưới ô); ô điểm tổng với cảnh báo "Điểm tổng đang thay cho điểm từng câu" khi `grades` có điểm tổng; khi `result.publication_status = published` ô "Lý do sửa điểm" bắt buộc kèm cảnh báo "Học sinh và phụ huynh đang thấy điểm này. Việc sửa được ghi lại." và gửi `reason`; ngược lại ẩn ô; sau mỗi lần lưu cập nhật thanh điểm từ `result` trong phản hồi; "Công bố kết quả" chỉ bật khi `complete`, mở `PublishResultsDialog` khởi tạo công tắc từ `result.show_*`; "Rút kết quả" có xác nhận; "Lưu và chấm bài kế" đi tới bài chưa chấm xong kế tiếp trong danh sách bài nộp, hết thì hiện "Đã chấm hết. Công bố cả lớp?" có nút mở hộp công bố hàng loạt.

- [ ] **Step 1: Viết test**: lưu điểm câu gửi `{ score }`, đã công bố mà thiếu lý do thì không gọi API và hiện lỗi, có lý do thì gửi `reason`; điểm ngoài khoảng hiện lỗi 422 `score`; điểm tổng hiện cảnh báo; công bố gửi ba cờ từ hộp thoại; rút gọi `withdraw`; "bài kế" chọn đúng bài; hết bài hiện lời nhắc.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo mockup (`gv`, `saveTotal`, `pubAsk`, `saveNext`).**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(grading): TC-13 scoring, publish and next-submission flow`.

### Task 11: TC-13 nhận xét kèm file

**Files:**
- Create: `src/features/grading/ui/teacher/feedback-section.tsx`, `feedback-item.tsx`, `feedback-form.tsx`
- Modify: `grading-screen.tsx`
- Test: `feedback-section.test.tsx`

**Interfaces:**
- Consumes: `useCreateFeedback`, `useUpdateFeedback`, `useSetFeedbackStatus` (Task 7); `useUploadAndLinkAttachment`, `useDeleteAttachment`, `AttachmentChip` (Task 5).
- Produces: danh sách nhận xét nhóm "Chung cho cả bài" và theo từng câu, mỗi nhận xét có nhãn trạng thái (Nháp / Đã công bố / Đã rút), nhãn quyền xem (ba giá trị bằng lời), tên người viết (`author.name`), file đính kèm; "Công bố nhận xét" khi nháp (kèm chú thích học sinh chỉ thấy khi cả kết quả đã công bố), "Rút lại" có xác nhận, "Sửa" chỉ nội dung; biểu mẫu: quyền xem chọn lúc tạo và khóa khi sửa, "Gắn với" (chung / một câu), nội dung bắt buộc, "Đính kèm file" (tối đa 1 file, chú thích 5 MB, ảnh/PDF/Word) thực hiện upload rồi link vào nhận xét vừa tạo, lỗi `ATTACHMENT_*` hiện nguyên văn; nhận xét nháp cho gỡ file, đã công bố thì không.

- [ ] **Step 1: Viết test**: tạo nhận xét gửi đúng `visibility` và `assessment_question_id`; đính file chạy tuần tự create → upload → link (`feedback_id`), lỗi giới hạn không để lại nhận xét treo trạng thái sai (hiện lỗi, giữ nhận xét đã tạo); quyền xem khóa khi sửa; công bố / rút gọi `PATCH` đúng; nhận xét đã rút không còn nút công bố lại.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Cài đặt theo mockup (`gFb`, `fbd`, `attachFb`).**
- [ ] **Step 4: Chạy, mong đợi PASS.**
- [ ] **Step 5: Commit** `feat(grading): TC-13 feedback with attachments`.

### Task 12: Seeder dữ liệu mẫu cho 4c (`piclass-api`)

**Files:**
- Create: `piclass-api/database/seeders/TeacherGradingDemoSeeder.php`, `piclass-api/tests/Feature/Seeders/TeacherGradingDemoSeederTest.php`
- Modify: `piclass-api/database/seeders/DatabaseSeeder.php` (không tự chạy; chỉ chạy bằng `--class`), tài liệu ngắn trong `docs/setup/` hoặc `piclass-infra/local/README.md` ghi lệnh

**Interfaces:**
- Produces: lệnh `docker compose exec api php artisan db:seed --class=TeacherGradingDemoSeeder` chạy lại được (xóa và tạo lại dữ liệu demo của chính nó, nhận diện bằng tiền tố tên "[Demo 4c]", không đụng dữ liệu khác). Dùng tài khoản `DevUserSeeder` (`teacher1@piclass.test`, `student1@piclass.test`, `student2@piclass.test`, mật khẩu `Piclass@123`), tạo thêm học sinh để đủ trạng thái. Dữ liệu: một lớp có `teacher1` phụ trách; một bài kiểm tra đã phát hành giao cho lớp gồm câu trắc nghiệm, đúng/sai, tự luận và một câu nộp file (`file_upload`); các học sinh ở sáu trạng thái: chưa làm, đang làm, đã nộp chưa chấm (có file thật trong đĩa `ATTACHMENT_DISK`), chấm dở, chấm xong chưa công bố, đã công bố; có nhận xét ở trạng thái nháp, đã công bố, đã rút; một bài có phiên bản mới; một bài nộp bị vô hiệu kèm lượt thay thế. Hàm công khai `run(): void`.

- [ ] **Step 1: Viết test** chạy seeder rồi assert: có đủ sáu trạng thái học sinh; có ít nhất một kết quả `published` và một `unpublished` đã `complete`; chạy lần hai không nhân đôi dữ liệu; `GET /assessments/{id}/students` của `teacher1` trả đủ `progress` mong đợi.
- [ ] **Step 2: Chạy, mong đợi FAIL.**
- [ ] **Step 3: Viết seeder dùng factory và Action có sẵn nếu có (không ghi tay các bảng chấm điểm khi đã có Action), đặt file mẫu vào đĩa lưu trữ qua `AttachmentStorage`.**
- [ ] **Step 4: Chạy test và chạy seeder thật trên DB dev; ghi lại kết quả đếm bản ghi.**
- [ ] **Step 5: Commit** `feat(seed): demo data for teacher grading screens`.

### Task 13: Tài liệu, kiểm tra tổng và review

**Files:**
- Modify: `docs/product/screens/teacher-bai-danh-gia.md` (đánh dấu FE TC-11…13 đã port, ghi lệch so với mockup nếu có), `docs/product/screens/api-gaps.md` (nếu phát hiện), `docs/STATE.md`

- [ ] **Step 1: Chạy `pnpm test`, `pnpm lint`, `pnpm tsc --noEmit`, `pnpm build` ở `piclass-web`, mong đợi xanh.**
- [ ] **Step 2: Đi tay một vòng bằng seeder (đăng nhập `teacher1@piclass.test`): mở bài, xem học sinh theo tiến độ, tab Bài nộp, chấm một bài, công bố một bài và công bố hàng loạt, nhận xét kèm file; ghi kết quả và lỗi tìm thấy.**
- [ ] **Step 3: Cập nhật tài liệu như danh sách Files.**
- [ ] **Step 4: Review độc lập cuối nhánh bằng subagent `model: "opus"`, sửa phát hiện Critical và Important có test.**
- [ ] **Step 5: Cập nhật `STATE.md` (bước `review` → `chờ merge`), hỏi user có mở PR và merge không.**
