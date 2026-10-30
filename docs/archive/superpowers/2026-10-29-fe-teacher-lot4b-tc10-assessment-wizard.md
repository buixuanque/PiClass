# Lát 4b FE — TC-10 Soạn và giao bài (4 bước) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giáo viên tạo bài (`/teacher/assessments/new`), sửa bài nháp (`/teacher/assessments/{id}/edit`) qua bốn bước (Thông tin, Câu hỏi, Đối tượng, Xem trước và phát hành), mỗi bước lưu ngay vào bài nháp.

**Architecture:** Lớp dữ liệu mới trong `features/assessment/api/` (hook cho từng endpoint). Màn trong `features/assessment/ui/teacher/`: một màn vỏ `AssessmentWizardScreen` giữ bước hiện tại (`?step=1..4` trên URL) và bốn component bước. Chọn câu từ ngân hàng là một route riêng `/teacher/assessments/{id}/pick` dựng bằng `useQuestions` của `question-bank` (assessment → question-bank qua `index.ts`, không ngược lại, tránh vòng F5). Không đổi BE.

**Tech Stack:** Next.js 16, React 19, TanStack Query, React Hook Form + Zod, Vitest + MSW.

**Spec:** `docs/product/screens/teacher-bai-danh-gia.md` (TC-10, bước 1-4), `DEC-LRN-022`. Mockup: `piclass-mockup/design-system/PiClass Giáo viên.dc.html`, khối `isWizard` (dòng ~727-857) và view-model `wz`. Kiểu response: `piclass-api/app/Modules/Assessment/Http/Resources/*` (OpenAPI chỉ khai `object`).

## Global Constraints

- Port đúng giá trị từ mockup (khung hai cột `minmax(0,1fr) 340px` ở bước 1, `360px` ở bước 3-4, gap 24, thẻ `Card`, thanh bước 26px tròn); dùng component `components/ui/*` có sẵn. Mọi thời điểm qua `lib/time.ts` (F8); ô `datetime-local` chuyển qua hai hàm mới `isoToVnInput` / `vnInputToIso` (giờ Việt Nam, `DEC-SYS-002`).
- Chữ giao diện tiếng Việt; comment tiếng Việt; tên và commit tiếng Anh; comment đầu file ghi `TC-10` và `DEC-LRN-022`.
- FE không tự quyết quyền hay rule BE (F7): lỗi 422/403/409 hiện nguyên văn BE theo trường hoặc banner; Zod chỉ kiểm dạng.
- `type` không đổi được sau khi tạo (form sửa hiện chỉ đọc). `max_attempts` khi sửa không được gửi `null`. Thời lượng nhập phút, gửi `duration_seconds = phút × 60`.
- BE không kiểm: thứ tự `opens_at ≤ deadline_at ≤ closes_at`, điểm đạt ≤ điểm tối đa, tổng điểm khớp điểm tối đa, đối tượng tối thiểu 1: FE tự báo (lỗi cứng ở bước 1 cho thứ tự giờ; cảnh báo mềm ở bước 4).
- Không làm ở lát này: file đính kèm của bài (`B-068`), `result_release_at`/`late_policy` (TB-6), nút "Xem như học sinh", phát hành lại phiên bản mới và các thao tác ở TC-11 (lát 4c), **"Gắn vào buổi học"** (`lesson_session_id`; spec không nói chọn lớp nào để lấy buổi học) ghi `B-071`.
- Bài không còn nháp (`status != draft` hoặc `current_version.status != draft`): màn sửa chỉ đọc kèm banner "Bài đã phát hành, hãy soạn phiên bản mới để sửa." và liên kết về danh sách.

## Review Focus

- Rời giữa chừng sau bước 1 không mất dữ liệu: mở lại `/edit` vào đúng bước và đủ dữ liệu.
- Số lượt `max_attempts` rỗng hoặc 0, thời lượng rỗng: không gửi `0`/`NaN`; rỗng thì gửi `null` (trừ `max_attempts` khi sửa: bỏ khỏi body).
- `datetime-local` rỗng gửi `null`; giá trị có giờ đổi qua lại không lệch múi giờ (23:30 VN không nhảy sang ngày khác).
- Thêm trùng một câu hỏi hai lần vào cùng bài: câu đã có hiện nhãn "Đã thêm" ở bảng chọn và không gọi lần hai.
- Phát hành khi chưa có câu: nút khóa; BE vẫn trả 422 thì hiện nguyên văn "Bài chưa có câu hỏi nào, hãy thêm câu ở bước 2.".
- Rút đối tượng và thêm đối tượng gửi đúng một trong `class_id`/`student_id`/`course_id`.

---

### Task 1: Lớp dữ liệu bài đánh giá và hàm giờ

**Files:**
- Modify: `piclass-web/src/features/assessment/api/types.ts`, `assessments.ts`, `keys.ts`; `piclass-web/src/lib/time.ts` (+ `time.test.ts`)
- Create: `piclass-web/src/features/assessment/api/questions.ts`, `audience.ts`, `schema.ts` (Zod bước 1)
- Test: `assessments.test.tsx` (mở rộng), `schema.test.ts`, `time.test.ts`

**Interfaces:**
- Produces (types): `AssessmentVersion` (`id, version_number, status, title, instructions, opens_at, closes_at, deadline_at, duration_seconds, max_attempts, attempt_score_policy: "highest" | "latest", shuffle_questions, shuffle_options, passing_score, max_score, show_answers, show_explanations, show_question_scores, questions?: AssessmentQuestion[]`), `AssessmentQuestion` (`id, position, points, source_question_version_id, question_type, snapshot: { prompt: string }`), `AssessmentAudience` (`id, class_id, student_id, course_id, valid_from, valid_to`), `AssessmentWriteInput` (các trường của `POST`/`PATCH`).
- Produces (hooks, mỗi hàm gọi một endpoint): `useAssessment(id)` → `GET /assessments/{id}` đọc `assessment`; `useCreateAssessment()` → `POST /assessments`; `useUpdateAssessment(id)` → `PATCH`; `useAddAssessmentQuestion(id)`, `useUpdateAssessmentQuestion(id)`, `useRemoveAssessmentQuestion(id)`; `useAssessmentAudience(id)`, `useAddAssessmentAudience(id)`, `useRemoveAssessmentAudience(id)`; `usePublishAssessment(id)`. Mutation invalidate `assessmentKeys.detail(id)` (và `audience(id)`, `list`).
- Produces (`lib/time.ts`): `isoToVnInput(iso: string | null): string` ("2026-10-30T23:59") và `vnInputToIso(local: string): string | null` ("" → null, đổi về ISO có `+07:00`).
- Produces (`schema.ts`): `assessmentInfoSchema` (Zod: `title` bắt buộc "Vui lòng nhập tên bài"; số lượt, thời lượng là số nguyên ≥ 1 khi có; điểm là số ≥ 0 khi có) và `toWriteInput(values, mode: "create" | "edit")`.

- [ ] **Step 1: Viết test.** `time.test.ts`: `vnInputToIso("2026-10-30T23:59")` = `"2026-10-30T23:59:00+07:00"`, `isoToVnInput("2026-10-30T16:59:00Z")` = `"2026-10-30T23:59"`, `""` và `null` → `null` / `""`. `schema.test.ts`: tên rỗng lỗi; phút `15` ra `duration_seconds: 900`; `max_attempts` rỗng bị bỏ khỏi body ở `edit`; thứ tự giờ sai (hạn nộp trước giờ mở) lỗi ở trường tương ứng. `assessments.test.tsx`: mỗi mutation gọi đúng URL, method và body (MSW).
- [ ] **Step 2: Chạy, xác nhận fail** — Run: `pnpm test time schema assessments` — Expected: FAIL (hàm/hook chưa có).
- [ ] **Step 3: Cài đặt** theo khối Interfaces. Response của `POST/PATCH/GET/publish` bọc trong `{ assessment }` (PATCH trả bản phiên bản `{assessment}` theo `AssessmentVersionEnvelopeResource`); câu trả về `{ assessment_question }`, đối tượng `{ audience }` / `{ data }`.
- [ ] **Step 4: Chạy lại + `pnpm typecheck && pnpm lint`** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add assessment write API hooks, wizard schema and VN time helpers`.

### Task 2: Vỏ trình hướng dẫn và bước 1 (Thông tin)

**Files:**
- Create: `piclass-web/src/features/assessment/ui/teacher/assessment-wizard-screen.tsx`, `assessment-wizard-steps.tsx` (thanh bước), `assessment-info-step.tsx`
- Create: `piclass-web/src/app/(portal)/teacher/assessments/new/page.tsx`, `piclass-web/src/app/(portal)/teacher/assessments/[assessmentId]/edit/page.tsx`
- Modify: `piclass-web/src/features/assessment/index.ts` (export `AssessmentWizardScreen`)
- Test: `assessment-wizard-screen.test.tsx`

**Interfaces:**
- Consumes: hooks và `assessmentInfoSchema`/`toWriteInput` của Task 1.
- Produces: `AssessmentWizardScreen({ assessmentId?: string })`: không có `assessmentId` thì tạo mới; bước đọc từ `?step=` (mặc định 1, chặn nhảy tới bước 2-4 khi chưa có bài). Mỗi bước nhận `{ assessment: Assessment; onNext: () => void }`. Thanh bước và nút "Quay lại" / "Tiếp tục" / "Lưu nháp và thoát" theo mockup. Bước 1 "Lưu và tiếp tục": tạo mới → `POST` rồi `router.replace(/teacher/assessments/{id}/edit?step=2)`; sửa → `PATCH`, rồi sang bước 2.

- [ ] **Step 1: Viết test** (MSW): tạo mới gửi đúng body (loại thẻ chọn, `duration_seconds` từ phút) rồi chuyển `?step=2`; thiếu tên hiện "Vui lòng nhập tên bài" và không gọi BE; hạn nộp trước giờ mở hiện lỗi thứ tự; lỗi 422 của BE hiện theo trường; mở `/edit` của bài nháp điền sẵn dữ liệu, loại chỉ đọc, `PATCH` không có `type` và không có `max_attempts: null`; bài đã phát hành hiện banner chỉ đọc và không có nút lưu.
- [ ] **Step 2: Chạy, xác nhận fail** — Run: `pnpm test assessment-wizard` — Expected: FAIL.
- [ ] **Step 3: Cài đặt** vỏ, thanh bước và bước 1 theo mockup (`s1`): thẻ "Loại bài và nội dung" (ba thẻ loại, tên, hướng dẫn), "Thời gian và số lượt" (giờ mở, hạn nộp, giờ đóng, thời lượng chỉ hiện khi loại kiểm tra/thi, số lượt, cách tính điểm khi số lượt > 1), cột phải "Điểm" và "Cách làm và cách hiện kết quả". Gợi ý trộn câu bật sẵn cho Bài thi. Routes chỉ ghép (F1).
- [ ] **Step 4: Chạy test, typecheck, lint** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 wizard shell and info step`.

### Task 3: Bước 2 (Câu hỏi) và bảng chọn câu

**Files:**
- Create: `piclass-web/src/features/assessment/ui/teacher/assessment-questions-step.tsx`, `assessment-question-picker-screen.tsx`
- Create: `piclass-web/src/app/(portal)/teacher/assessments/[assessmentId]/pick/page.tsx`
- Modify: `piclass-web/src/features/question-bank/index.ts` (export `useQuestions`, `QuestionListItem`, `QUESTION_TYPE_BADGE` nếu chưa có); `QuestionEditorScreen` nhận `returnTo` từ `?returnTo=` (chỉ nhận đường dẫn bắt đầu bằng `/teacher/`) để quay về sau khi lưu
- Test: `assessment-questions-step.test.tsx`, `assessment-question-picker-screen.test.tsx`

**Interfaces:**
- Consumes: Task 1 (`useAddAssessmentQuestion`, `useUpdateAssessmentQuestion`, `useRemoveAssessmentQuestion`, `useAssessment`), `useQuestions` của `question-bank` qua index.
- Produces: danh sách câu theo `position` (số thứ tự, trích đề `snapshot.prompt`, nhãn loại, điểm sửa tại chỗ gửi `PATCH {points}` khi rời ô, nút lên/xuống gửi `PATCH {position}`, nút bỏ khỏi bài `DELETE`), tổng điểm so với `max_score` ở chân bảng; "Thêm từ ngân hàng" → `/teacher/assessments/{id}/pick`; "Soạn câu mới" → `/teacher/question-bank/new?returnTo=/teacher/assessments/{id}/pick`. Màn chọn: bộ lọc tìm/môn/loại, tick nhiều câu, ô điểm mặc định `suggested_points` hoặc 1, nút "Thêm n câu vào bài" gọi tuần tự `POST …/questions {question_version_id: current_version.id, points}`, câu đã có trong bài nhãn "Đã thêm" và không chọn được, câu lưu trữ ẩn.

- [ ] **Step 1: Viết test**: sửa điểm gửi `PATCH`; lên/xuống gửi `position` đúng; bỏ khỏi bài gửi `DELETE`; bài chưa có câu hiện "Bài chưa có câu hỏi nào"; màn chọn gọi `POST` đúng `question_version_id` và điểm mặc định (`suggested_points` hoặc 1), câu đã thêm không gọi lần hai, câu lưu trữ không hiện; lỗi 409 hiện nguyên văn.
- [ ] **Step 2: Chạy, xác nhận fail** — Expected: FAIL.
- [ ] **Step 3: Cài đặt** theo mockup `s2` (dòng ~789-809) và chế độ chọn của TC-07 (spec). Hộp xem trước đáp án ở bước này nếu làm phải đọc từ ngân hàng, không từ `snapshot`; **không làm** hộp này ở lát này, ghi vào `B-072`.
- [ ] **Step 4: Chạy test, typecheck, lint** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 questions step and bank picker`.

### Task 4: Bước 3 (Đối tượng)

**Files:**
- Create: `piclass-web/src/features/assessment/ui/teacher/assessment-audience-step.tsx`
- Test: `assessment-audience-step.test.tsx`

**Interfaces:**
- Consumes: Task 1 (`useAssessmentAudience`, `useAddAssessmentAudience`, `useRemoveAssessmentAudience`); `useClassOptions` (đã có), khóa học từ `GET /courses` và học sinh từ `GET /classes/{id}/members` đọc qua hook trong `features/assessment/api/audience-options.ts` (tự gọi `apiClient`, không import `academic`).
- Produces: thẻ "Giao cho ai" với ba tab Lớp / Học sinh / Khóa học, ô chọn, "Thêm" gửi **đúng một** trong `class_id`/`student_id`/`course_id`; thẻ "Đã giao" (loại, tên, ngày giao `valid_from`, "đã rút" khi có `valid_to`, nút "Rút"); lỗi 403/422 hiện nguyên văn.

- [ ] **Step 1: Viết test**: thêm lớp gửi `{class_id}` đúng một khóa; tab Học sinh cần chọn lớp rồi học sinh và gửi `{student_id}`; "Rút" gửi `DELETE`; đối tượng đã rút hiện nhãn và không có nút Rút; chưa chọn gì thì nút Thêm không gọi BE; lỗi 403 hiện "Lớp này không thuộc phân công của bạn.".
- [ ] **Step 2: Chạy, xác nhận fail** — Expected: FAIL.
- [ ] **Step 3: Cài đặt** theo mockup `s3` (dòng ~811-831).
- [ ] **Step 4: Chạy test, typecheck, lint** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 audience step`.

### Task 5: Bước 4 (Xem trước và phát hành)

**Files:**
- Create: `piclass-web/src/features/assessment/ui/teacher/assessment-publish-step.tsx`, `assessment-checks.ts`
- Test: `assessment-checks.test.ts`, `assessment-publish-step.test.tsx`

**Interfaces:**
- Consumes: Task 1 (`useAssessment`, `useAssessmentAudience`, `usePublishAssessment`).
- Produces: `buildChecks(assessment, audience): { level: "block" | "warn"; text: string }[]` (chặn: chưa có câu hỏi; cảnh báo: chưa giao cho ai, tổng điểm khác điểm tối đa, điểm đạt lớn hơn điểm tối đa, mốc giờ sai thứ tự, có câu `file_upload`); thẻ "Tóm tắt" (tên, loại, các mốc giờ, thời lượng, số lượt, trộn, hiện đáp án, số câu và tổng điểm, đối tượng); nút "Phát hành" khóa khi có mục chặn, mở hộp xác nhận (nội dung cố định nói câu hỏi và đáp án bị cố định, học sinh được báo), thành công về `/teacher/assessments` kèm toast.

- [ ] **Step 1: Viết test**: `buildChecks` đủ năm trường hợp và mục chặn; nút Phát hành khóa khi chưa có câu; xác nhận gọi `POST …/publish` rồi `router.push("/teacher/assessments")`; 422 "Không thể publish Assessment chưa có câu hỏi nào." hiện thành "Bài chưa có câu hỏi nào, hãy thêm câu ở bước 2.".
- [ ] **Step 2: Chạy, xác nhận fail** — Expected: FAIL.
- [ ] **Step 3: Cài đặt** theo mockup `s4` (dòng ~833-849).
- [ ] **Step 4: Chạy toàn bộ `pnpm test`, `pnpm typecheck`, `pnpm lint`** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 publish step`.
