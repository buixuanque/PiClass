# Lát 4b FE — TC-10 Soạn và giao bài bản v2 (một trang, ba tab) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay trình hướng dẫn bốn bước của TC-10 bằng một trang ba tab (Câu hỏi / Đối tượng / Cài đặt) theo `DEC-LRN-024`: hộp "Tạo bài" hỏi tên và loại, bản nháp lưu ngay, đầu trang là đường dẫn kèm badge, câu hỏi hiện như học sinh thấy, phát hành bằng hộp xác nhận.

**Architecture:** Giữ lớp dữ liệu `features/assessment/api/` (hook, schema, types) đã có trên nhánh `feat/teacher-lot4b-fe`. Viết lại lớp giao diện `features/assessment/ui/teacher/`: một màn `AssessmentEditorScreen` thay `AssessmentWizardScreen`, ba tab dựng lại từ các bước cũ (tái dùng logic, bỏ khung bước), hộp `CreateAssessmentDialog` ở TC-09. Chọn câu từ ngân hàng vẫn là route riêng `/teacher/assessments/{id}/pick`. Lưu tự động: mỗi thay đổi ở tab Cài đặt gửi `PATCH` khi rời ô (blur), điểm và thứ tự câu gửi ngay.

**Tech Stack:** Next.js 16, React 19, TanStack Query, React Hook Form + Zod, Vitest + MSW.

**Spec:** `docs/product/screens/teacher-bai-danh-gia.md` (TC-10), `DEC-LRN-024`, `DEC-LRN-022`. Mockup chuẩn: `piclass-mockup/design-system/PiClass Giáo viên.dc.html`, khối `isWizard` (dòng ~727-905) và view-model `wz` (~1700-1800), hộp tạo bài `ndOpen` (~1076). Kiểu response: `piclass-api/app/Modules/Assessment/Http/Resources/*`.

## Global Constraints

- Port đúng giá trị từ mockup (cột phải tab Cài đặt `minmax(0,1fr) 340px`, gap 24; tab Câu hỏi và Đối tượng `max-width:780px` căn giữa; thẻ câu bo `20px`, `shadow-xs`, đệm `18px 20px`; dải xanh `blue-50` cho thông báo). Dùng component `components/ui/*` có sẵn. Mọi thời điểm qua `lib/time.ts` (F8).
- Chữ giao diện tiếng Việt, comment tiếng Việt, tên và commit tiếng Anh; comment đầu file ghi `TC-10` và `DEC-LRN-024`.
- F7: FE không tự quyết quyền hay rule BE; lỗi 422/403/409 hiện nguyên văn. Zod chỉ kiểm dạng. `type` không đổi sau khi tạo. `max_attempts` khi sửa không được gửi `null`. Thời lượng nhập phút, gửi `duration_seconds = phút × 60`.
- BE không kiểm: thứ tự `opens_at ≤ deadline_at ≤ closes_at`, điểm đạt ≤ điểm tối đa, tổng điểm khớp điểm tối đa, đối tượng tối thiểu 1: FE tự báo (lỗi cứng thứ tự giờ ở tab Cài đặt; cảnh báo mềm trong hộp phát hành).
- **Không làm:** ba cờ `show_answers`/`show_explanations`/`show_question_scores` (`B-073`, lát 4c; FE không gửi, BE mặc định tắt), file đính kèm (`B-068`), hẹn giờ công bố điểm và `late_policy` (TB-6), "Soạn phiên bản mới" và các thao tác ở TC-11 (lát 4c), "Gắn vào buổi học" (`B-071`), khung trượt soạn câu ngay trong trang (`B-070`).
- Bài không còn nháp (`status != draft` hoặc `current_version.status != draft`): màn chỉ đọc kèm "Bài đã phát hành, hãy soạn phiên bản mới để sửa." và liên kết về danh sách.
- Mỗi file một component chính, ESLint cảnh báo quá 200 dòng. Sửa `architecture.test.ts` nếu đổi luật F.

## Review Focus

- Tạo bài với tên toàn khoảng trắng: hộp báo "Vui lòng nhập tên bài", không gọi BE; bấm "Tạo bản nháp" hai lần liên tiếp chỉ tạo một bài.
- Xóa trống ô tên ở tab Cài đặt rồi rời ô: không gửi `title: ""`, hiện lỗi và đường dẫn đầu trang giữ tên cũ.
- Điểm một câu nhập rỗng hoặc âm: không gửi, khôi phục giá trị cũ; sửa điểm rồi bấm Phát hành ngay không mất thay đổi.
- Lên/xuống ở câu đầu/cuối không gọi BE; thêm cùng một câu từ ngân hàng hai lần vẫn bị chặn (nhãn "Đã thêm").
- Mở `/edit` của bài đã phát hành: chỉ đọc, không có nút lưu; bài chưa có câu: nút Phát hành mở hộp báo chặn thay vì gọi BE.
- Đối tượng: tick lớp rồi bỏ tick gửi đúng `POST` rồi `DELETE`; một học sinh vừa qua lớp vừa lẻ chỉ tính một lần ở số học sinh dự kiến.

## Điểm cần user chốt trước khi làm Task 3

Tab Câu hỏi của v2 hiện từng câu **như học sinh thấy** (có lựa chọn, ô điền, cặp nối...). `GET /assessments/{id}` hiện chỉ trả `questions[].snapshot.prompt` và `source_question_version_id`; lúc còn nháp không có lựa chọn/đáp án, và `GET /questions/{id}` chỉ trả **phiên bản hiện hành**, không trả đúng phiên bản bài đã ghim. Hai hướng:
- **(a) BE thêm** vào `GET /assessments/{id}` cho mỗi câu `source_question_version` (nội dung đúng phiên bản đã ghim: lựa chọn, cặp nối, mục sắp xếp, không kèm đáp án đúng cho câu chấm tự động nếu phía học sinh không cần; xem preview chỉ cần hiển thị). Nhỏ, chỉ người tạo bài/Admin thấy; ghi `ARCH`/`api-gaps.md`. **Khuyến nghị**, vì preview mới đúng phiên bản (BR-SYS-008).
- **(b) Không sửa BE:** FE đọc `GET /questions/{id}` rồi tra theo `source_question_version_id` không có đường nào → phải lấy câu hiện hành, preview có thể lệch khi câu nguồn đã sửa. Chỉ chấp nhận nếu muốn tránh đụng BE ở lát này.

Plan dưới đây theo (a): Task 3 mở đầu bằng phần BE.

---

### Task 1: Hộp "Tạo bài" ở TC-09 và bỏ route tạo cũ

**Files:**
- Create: `piclass-web/src/features/assessment/ui/teacher/create-assessment-dialog.tsx`, `create-assessment-dialog.test.tsx`
- Modify: `assessment-list-screen.tsx` (hai nút "Tạo bài" đang là `LinkButton href=/teacher/assessments/new` → mở hộp); `assessment-kind.ts` (thêm `desc` mô tả ngắn từng loại nếu chưa có)
- Delete: `piclass-web/src/app/(portal)/teacher/assessments/new/page.tsx`
- Test: `create-assessment-dialog.test.tsx`, `assessment-list-screen.test.tsx` (cập nhật)

**Interfaces:**
- Consumes: `useCreateAssessment()` và `toWriteInput` (Task cũ, `api/assessments.ts`, `api/schema.ts`).
- Produces: `CreateAssessmentDialog({ open: boolean; onClose: () => void; defaultType?: AssessmentType })`. Trường Tên bài (Input, nhãn "Tên bài") và Loại bài (Select kèm mô tả loại đang chọn và câu "Không đổi được sau khi tạo."). Bấm "Tạo bản nháp" gọi `POST /assessments` rồi `router.push("/teacher/assessments/{id}/edit")` và toast "Đã tạo bản nháp".

- [ ] **Step 1: Viết test.** `create-assessment-dialog.test.tsx`: tên rỗng/khoảng trắng hiện "Vui lòng nhập tên bài" và không gọi BE; tên hợp lệ gửi `{ type, title }` đã `trim`, đi tới `/edit` của bài vừa tạo; bấm hai lần liên tiếp chỉ gọi `POST` một lần; lỗi 422 của BE hiện nguyên văn dưới ô tên. `assessment-list-screen.test.tsx`: "Tạo bài" mở hộp, không còn liên kết tới `/new`.
- [ ] **Step 2: Chạy, xác nhận fail** — Run: `pnpm test create-assessment-dialog assessment-list-screen` — Expected: FAIL.
- [ ] **Step 3: Cài đặt `CreateAssessmentDialog`** theo mockup (`ndOpen`, dòng ~1076-1090) bằng `components/ui/dialog` và `Select`; khóa nút khi đang gửi. Xóa route `new`.
- [ ] **Step 4: Chạy test, `pnpm typecheck`, `pnpm lint`** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add create-assessment dialog replacing the new-assessment route`.

### Task 2: Vỏ trang soạn: đường dẫn, badge, ba tab, trạng thái lưu

**Files:**
- Create: `piclass-web/src/features/assessment/ui/teacher/assessment-editor-screen.tsx`, `assessment-editor-header.tsx`, `assessment-editor-screen.test.tsx`
- Modify: `piclass-web/src/app/(portal)/teacher/assessments/[assessmentId]/edit/page.tsx` (render `AssessmentEditorScreen`); `piclass-web/src/features/assessment/index.ts` (export `AssessmentEditorScreen`, bỏ `AssessmentWizardScreen`)
- Delete: `assessment-wizard-screen.tsx`, `assessment-wizard-screen.test.tsx`, `assessment-wizard-steps.tsx`

**Interfaces:**
- Consumes: `useAssessment`, `usePublishAssessment` (đã có), `buildChecks` (Task 5).
- Produces: `AssessmentEditorScreen({ assessmentId: string })`: tab nằm ở `?tab=questions|audience|settings` (mặc định `questions`), `router.replace` khi đổi tab. Đầu trang (`AssessmentEditorHeader`): đường dẫn "Bài đánh giá › {tên}" (`Bài đánh giá` là liên kết về `/teacher/assessments`; tên rỗng hiện "Bài chưa đặt tên"), badge loại bài (`neutral`) và badge phiên bản (`Bản nháp` xám, hoặc `Phiên bản N · bản nháp` cam khi `version_number > 1`, `title` của badge chứa "Bản đang phát hành giữ nguyên cho tới khi cô phát hành phiên bản N."), ghi chú "Đã lưu nháp" / "Đang lưu…" / "Chưa lưu được" (từ `useIsMutating` của các mutation `assessment`), nút "Xem trước" (Task 4) và "Phát hành" (nhãn "Phát hành vN" khi `version_number > 1`).

- [ ] **Step 1: Viết test.** Đường dẫn hiện tên bài và liên kết "Bài đánh giá"; bài chưa tên hiện "Bài chưa đặt tên"; `version_number` 2 hiện badge "Phiên bản 2 · bản nháp" và nút "Phát hành v2"; `?tab=settings` mở tab Cài đặt; mở bài đã phát hành hiện thông báo chỉ đọc; không tìm thấy bài hiện "Không tìm thấy bài".
- [ ] **Step 2: Chạy, xác nhận fail** — Run: `pnpm test assessment-editor-screen` — Expected: FAIL.
- [ ] **Step 3: Cài đặt** vỏ và đầu trang theo mockup (dòng ~737-748) dùng `Tabs variant="line"`; ba tab render component của Task 3, 4, 5 (tạm placeholder trống ở task này để test vỏ chạy được).
- [ ] **Step 4: Chạy test, typecheck, lint** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 v2 editor shell with breadcrumb header and tabs`.

### Task 3: Tab Câu hỏi (BE trả nội dung câu, thẻ như học sinh thấy)

**Files:**
- Modify (BE): `piclass-api/app/Modules/Assessment/Http/Resources/AssessmentDetailResource.php` hoặc query nạp `currentVersion.questions.sourceQuestionVersion`; `tests/Feature/Assessment/GetAssessmentDetailTest.php` (hoặc file test tương ứng); export OpenAPI `piclass-api/storage/api-docs/openapi.json`
- Modify (FE): `features/assessment/api/types.ts` (`AssessmentQuestion.source_question_version?: { prompt: string; instructions: string | null; options?: …; pairs?: …; items?: …; … }` theo đúng response BE)
- Create (FE): `ui/teacher/assessment-questions-tab.tsx`, `assessment-question-card.tsx`, tests tương ứng
- Reuse: `QuestionStudentView` của `question-bank/ui/shared/question-student-view.tsx` (qua `@/features/question-bank` index, F5: thêm export `QuestionStudentView` và type `StudentQuestionView`)
- Modify: `ui/teacher/question-picker` đã có (giữ `returnTo`), `assessment-questions-step.tsx` (xóa sau khi tab mới xong)

**Interfaces:**
- Consumes: `useAddAssessmentQuestion`, `useUpdateAssessmentQuestion`, `useRemoveAssessmentQuestion` (đã có).
- Produces: `AssessmentQuestionsTab({ assessment: Assessment })`. Mỗi câu là thẻ: "Câu N", badge loại, ô điểm (số, gửi `PATCH {points}` khi rời ô), nút lên/xuống (`PATCH {position}`), nút bỏ khỏi bài (`DELETE`), phần thân vẽ bằng `QuestionStudentView` ở chế độ `preview` (không tương tác), ghi chú phiên bản "Phiên bản N của câu này". Chân trang: nút "Thêm từ ngân hàng" (→ `/teacher/assessments/{id}/pick`), "Soạn câu mới" (→ `/teacher/question-bank/new?returnTo=…`), dòng "Tổng điểm các câu {tổng}" kèm "Điểm tối đa {max_score}" nếu khác. Bài chưa có câu hiện "Bài chưa có câu hỏi nào".
- Hàm thuần: `toStudentView(q: AssessmentQuestion): StudentQuestionView` đặt trong `assessment-question-card.tsx` (map từ `source_question_version`, không đưa đáp án đúng vào view).

- [ ] **Step 1 (BE): Viết test** trong `GetAssessmentDetailTest`: người tạo bài nhận `current_version.questions[].source_question_version` đúng phiên bản đã ghim (sửa câu nguồn sau khi thêm vào bài thì bài vẫn thấy bản cũ); người không có quyền vẫn 403 như trước. Chạy `docker compose exec api php artisan test --filter=GetAssessmentDetail` — Expected: FAIL.
- [ ] **Step 2 (BE): Cài đặt** nạp quan hệ `sourceQuestionVersion` (đã có trên model `AssessmentQuestion`); chạy test + `vendor/bin/phpstan` + `scramble:export`; Expected: PASS, `openapi.json` đổi.
- [ ] **Step 3 (BE): Commit trong `piclass-api`** (nhánh riêng từ `origin/main`) `feat(assessment): include pinned source question version in assessment detail`; mở PR khi user yêu cầu.
- [ ] **Step 4 (FE): Viết test** `assessment-questions-tab.test.tsx`: sửa điểm gửi `PATCH`, điểm rỗng/âm không gửi và khôi phục; lên/xuống gửi `position` đúng, câu đầu "lên" và câu cuối "xuống" bị khóa; bỏ khỏi bài gửi `DELETE`; thẻ câu chọn một đáp án hiện các lựa chọn từ `source_question_version`; tổng điểm đúng; bài chưa có câu hiện trạng thái rỗng. `assessment-question-card.test.ts`: `toStudentView` không chứa cờ đáp án đúng.
- [ ] **Step 5 (FE): Chạy, xác nhận fail** — Run: `pnpm test assessment-questions-tab assessment-question-card` — Expected: FAIL.
- [ ] **Step 6 (FE): Cài đặt** theo mockup (dòng ~747-800); xóa `assessment-questions-step.tsx` và test cũ khi test mới xanh.
- [ ] **Step 7: Chạy test, typecheck, lint** ở cả hai repo — Expected: PASS.
- [ ] **Step 8: Commit FE** `feat(assessment): add TC-10 v2 questions tab with student-style cards`.

### Task 4: Tab Đối tượng (danh sách tick)

**Files:**
- Create: `ui/teacher/assessment-audience-tab.tsx`, `assessment-audience-tab.test.tsx`
- Delete: `assessment-audience-step.tsx`, `assessment-audience-step.test.tsx` (sau khi tab mới xanh)

**Interfaces:**
- Consumes: `useAssessmentAudience`, `useAddAssessmentAudience`, `useRemoveAssessmentAudience`, `useClassOptions`, hook khóa học và học sinh trong `api/audience-options.ts` (đã có).
- Produces: `AssessmentAudienceTab({ assessment: Assessment })` — ba thẻ: "Lớp" (tick giao, bỏ tick rút; chỉ lớp mình dạy), "Khóa học" (khóa mình sở hữu; "Cô chưa sở hữu khóa học nào đang mở." khi rỗng), "Học sinh riêng lẻ" (danh sách đã giao + nút mở chọn lớp rồi tick học sinh; học sinh đã nhận qua lớp hiện tick khóa kèm "qua lớp"). Cuối trang dải xanh "Dự kiến N học sinh nhận bài" (đếm duy nhất, BR-ASSESS-003). Tick gọi `POST /assessments/{id}/audience` đúng một khóa; bỏ tick gọi `DELETE …/audience/{audienceId}`; lỗi 403/422 hiện nguyên văn dưới thẻ.

- [ ] **Step 1: Viết test**: tick lớp gửi `{class_id}`; bỏ tick gửi `DELETE` đúng `audienceId`; học sinh đã nhận qua lớp bị khóa tick; học sinh lẻ gửi `{student_id}`; số học sinh dự kiến không đếm trùng; lỗi 403 "Lớp này không thuộc phân công của bạn." hiện và tick quay về trạng thái cũ.
- [ ] **Step 2: Chạy, xác nhận fail** — Run: `pnpm test assessment-audience-tab` — Expected: FAIL.
- [ ] **Step 3: Cài đặt** theo mockup (dòng ~802-825), tái dùng logic của `assessment-audience-step.tsx`.
- [ ] **Step 4: Chạy test, typecheck, lint** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 v2 audience tab with tick lists`.

### Task 5: Tab Cài đặt, tự lưu và đổi tên bài

**Files:**
- Create: `ui/teacher/assessment-settings-tab.tsx`, `assessment-settings-tab.test.tsx`
- Modify: `api/schema.ts` (bỏ ba cờ hiển thị khỏi `AssessmentInfoValues`/`toWriteInput`; `type` thành chỉ đọc khi sửa; thêm `titleOnly` nếu cần cho `PATCH` đổi tên)
- Delete: `assessment-info-step.tsx` (sau khi tab mới xanh)

**Interfaces:**
- Consumes: `useUpdateAssessment(id)`, `assessmentInfoSchema`, `fromAssessment`, `toWriteInput(values, "edit")`, `isoToVnInput`/`vnInputToIso`.
- Produces: `AssessmentSettingsTab({ assessment: Assessment })`. Thẻ "Tên, loại bài và hướng dẫn" (ô Tên bài, dòng "Loại bài: … (không đổi được sau khi tạo)", Hướng dẫn), "Thời gian và số lượt" (giờ mở, hạn nộp, giờ đóng, thời lượng chỉ hiện với kiểm tra/thi, số lượt, cách tính điểm khi số lượt > 1), "Cách làm bài" (hai công tắc trộn câu, trộn đáp án; **không có** ba công tắc hiển thị kết quả), cột phải "Điểm" (điểm tối đa, điểm đạt). Tự lưu: rời ô (blur) và form hợp lệ thì gửi `PATCH` chỉ các trường đã đổi; lỗi 422 hiện theo trường (bảng `FIELD_OF` của file cũ); lỗi thứ tự giờ hiện cứng và không gửi.

- [ ] **Step 1: Viết test**: sửa tên rồi rời ô gửi `PATCH {title}` và đường dẫn đầu trang đổi theo; xóa trống tên rồi rời ô hiện "Vui lòng nhập tên bài", không gửi; `max_attempts` rỗng bị bỏ khỏi body; phút `15` gửi `duration_seconds: 900`; hạn nộp trước giờ mở hiện lỗi thứ tự và không gửi; giờ `23:30` đổi qua lại không lệch ngày; body không bao giờ chứa `show_answers`/`show_explanations`/`show_question_scores`.
- [ ] **Step 2: Chạy, xác nhận fail** — Run: `pnpm test assessment-settings-tab schema` — Expected: FAIL.
- [ ] **Step 3: Cài đặt** theo mockup (dòng ~826-890); đổi test `schema.test.ts` cho khớp việc bỏ ba cờ.
- [ ] **Step 4: Chạy test, typecheck, lint** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 v2 settings tab with autosave`.

### Task 6: Xem trước như học sinh và hộp xác nhận phát hành

**Files:**
- Create: `ui/teacher/assessment-preview-banner.tsx`, `assessment-publish-dialog.tsx`, tests tương ứng
- Modify: `ui/teacher/assessment-checks.ts` (+ test): đổi thông báo "ở bước 2" thành "ở tab Câu hỏi"; `assessment-editor-screen.tsx` nối nút "Xem trước" và "Phát hành"
- Delete: `assessment-publish-step.tsx`, `assessment-publish-step.test.tsx`

**Interfaces:**
- Consumes: `buildChecks(assessment, audience): PublishCheck[]` (đã có), `usePublishAssessment`.
- Produces: bấm "Xem trước" (`?preview=1`) ẩn các điều khiển soạn ở tab Câu hỏi, hiện dải xanh sticky "Đang xem trước như học sinh. Đây là bản chạy thử, không lưu bài làm." với nút "Thoát xem trước" và nút "Nộp bài" bị khóa. `AssessmentPublishDialog({ open; onClose; assessment })`: tiêu đề "Phát hành bài?" (bản sửa: "Phát hành phiên bản N?"), danh sách kiểm (`block` đỏ, `warn` vàng), câu "Phát hành cho N học sinh. Nội dung câu hỏi và đáp án sẽ được cố định, muốn sửa phải tạo phiên bản mới. Hệ thống sẽ báo cho học sinh." (+ "Bản đang phát hành giữ nguyên cho tới khi cô bấm Phát hành phiên bản N." với bản sửa), nút "Phát hành" khóa khi có mục `block`; thành công → `router.push("/teacher/assessments")` và toast. Lỗi 422 "Không thể publish Assessment chưa có câu hỏi nào." hiện "Bài chưa có câu hỏi nào, hãy thêm câu ở tab Câu hỏi."; 409 hiện nguyên văn.

- [ ] **Step 1: Viết test**: `?preview=1` ẩn ô điểm/lên/xuống/xóa và hiện dải xem trước, "Thoát xem trước" bỏ tham số; hộp phát hành khóa nút khi chưa có câu, hiện cảnh báo mềm (chưa giao cho ai, tổng điểm lệch, điểm đạt lớn hơn điểm tối đa, mốc giờ sai thứ tự, có câu `file_upload`) mà vẫn cho phát hành; xác nhận gọi `POST …/publish` rồi điều hướng; bản sửa hiện tiêu đề "Phát hành phiên bản 2?" và câu giữ nguyên bản đang phát hành; trước khi gọi `publish` các thay đổi chưa lưu ở tab Cài đặt được lưu xong.
- [ ] **Step 2: Chạy, xác nhận fail** — Run: `pnpm test assessment-publish-dialog assessment-preview-banner assessment-checks` — Expected: FAIL.
- [ ] **Step 3: Cài đặt** theo mockup (`pv` dòng ~730-737 và hộp `pubOpen` dòng ~1080-1090).
- [ ] **Step 4: Chạy toàn bộ `pnpm test`, `pnpm typecheck`, `pnpm lint`, cập nhật `architecture.test.ts` nếu cần** — Expected: PASS.
- [ ] **Step 5: Commit** `feat(assessment): add TC-10 v2 preview mode and publish dialog`.

### Task 7: Dọn, đối chiếu mockup và tài liệu

**Files:**
- Modify: `docs/STATE.md`, `docs/BACKLOG.md` (B-070..072 đối chiếu: B-072 coi như xong nếu Task 3 làm theo (a)); `docs/product/screens/api-gaps.md` (ghi việc BE thêm `source_question_version`); `piclass-web/src/features/assessment/ui/teacher/` (xóa file bước cũ còn sót)
- Test: `pnpm test` toàn bộ; chạy `mockup-screen-review` cho màn TC-10 và TC-09 (hộp Tạo bài)

- [ ] **Step 1:** Chạy skill `mockup-screen-review` cho TC-10, sửa các lệch riêng-màn; so sánh với `PiClass Giáo viên.html`.
- [ ] **Step 2:** Dò thủ công qua Docker: tạo bài, thêm câu, giao lớp, xem trước, phát hành, mở lại thấy chỉ đọc.
- [ ] **Step 3:** Cập nhật `STATE.md` (bước 4 xong, chờ merge), `BACKLOG.md`, `api-gaps.md`.
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint && pnpm build` — Expected: PASS. Push nhánh `feat/teacher-lot4b-fe`; **chưa mở PR** cho tới khi user yêu cầu.
- [ ] **Step 5: Commit** `docs: update STATE and API gaps after TC-10 v2 FE`.
