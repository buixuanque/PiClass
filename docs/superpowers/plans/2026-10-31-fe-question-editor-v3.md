# FE màn Soạn câu hỏi (TC-08) cùng khung Soạn bài, soạn nhiều câu một lượt — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Làm lại màn tạo/sửa câu hỏi của ngân hàng (`piclass-web`) theo mockup đã duyệt: khung giống Soạn bài (hai tab Câu hỏi / Cài đặt, Xem trước là một chế độ), tạo mới soạn nhiều câu một lượt với cài đặt chung, có ô Hiển thị.

**Architecture:** Mỗi thẻ câu là một component có `useForm` riêng (tái dùng nguyên các khối nhập đã có: đáp án, lựa chọn, cấu trúc, điền khuyết). Cài đặt chung (môn, topic, tag, độ khó, điểm gợi ý chung, hiển thị) là một form thứ hai ở màn ngoài, ghép vào từng thẻ lúc lưu. Lưu nhiều câu gọi `POST /questions` tuần tự, không nguyên tử. Không sửa BE.

**Tech Stack:** Next.js 16, React 19 (React Compiler lint: không đọc ref lúc render, không `form.watch`), React Hook Form + Zod, TanStack Query, Vitest + Testing Library + MSW.

**Spec:** `docs/product/screens/teacher-bai-danh-gia.md` mục TC-08 (đoạn "Giao diện mới" và "Lưu nhiều câu"); `DEC-LRN-025`, `DEC-LRN-026` trong `docs/requirements/decision-log.md`. Mockup: `piclass-mockup/design-system/PiClass Giáo viên.dc.html` — UI dòng 617-760 (`qe.pv`, `qe.notPv`, `qe.showContent`, `qe.showClassify`), logic dòng 1544-1615 (`openQ`, `addCard`, `dupCard`, `delCard`, `saveQ`). API: `piclass-api/storage/api-docs/openapi.json` (`CreateQuestionRequest.visibility`, `QuestionDetailResource.question.visibility`, `PATCH /questions/{question}/visibility`).

## Global Constraints

- Quy ước repo: `piclass-web/CLAUDE.md` (F1–F9). Comment tiếng Việt, tên và test name tiếng Anh, không file nào quá 200 dòng (ESLint cảnh báo).
- F5: `question-bank` chỉ import feature khác qua `index.ts`; không tạo vòng với `assessment`.
- F7: không tự quyết quyền hay rule nghiệp vụ ở FE; chỉ ẩn/hiện theo dữ liệu BE (chủ câu: `created_by_user_id` so với `useMe`, như hiện tại).
- Tên tab đúng chữ: **Câu hỏi**, **Cài đặt**. Nút: **Xem trước**, **Thoát xem trước**, **Thêm câu**, **Lưu tất cả (N câu)**, **Lưu câu hỏi**, **Lưu v{n}**, **Lưu trữ**. Dải xem trước: "Đang xem trước như học sinh".
- Đầu trang theo khuôn các màn chi tiết khác: `LinkButton variant="ghost" size="sm" iconLeft="chevron-left"` (nhãn "Ngân hàng câu hỏi" hoặc "Bài đang soạn" khi có `returnTo`), `h1` 28px kèm badge **cùng hàng**, nút bên phải. Tiêu đề: "Soạn câu hỏi" / "Soạn N câu hỏi" (N>1) / "Sửa câu hỏi" / "Câu hỏi" (chỉ xem).
- Không tự lưu. `PATCH /questions/{id}` mỗi lần tạo phiên bản mới (BR-SYS-008). Riêng **Hiển thị** của câu đã có đổi bằng `PATCH /questions/{id}/visibility` ngay khi chọn (không tạo phiên bản).
- Chế độ tạo mới: môn, topic, tag, độ khó, hiển thị, điểm gợi ý **chung**; **điểm** sửa được trên từng thẻ, thẻ để trống thì lấy điểm chung. Hiển thị mặc định `shared`.
- Lưu tất cả: gọi `POST /questions` tuần tự từng thẻ chưa lưu, không nguyên tử; thẻ lưu được hiện badge "Đã lưu" và bị khóa, thẻ lỗi giữ nguyên kèm lỗi theo trường, không lưu trùng thẻ đã lưu. Tất cả thẻ đã lưu → quay về: có `returnTo` thì `${returnTo}?select=id1,id2,...`, không thì `/teacher/question-bank`.
- Ngoài phạm vi: file đính kèm câu hỏi (`B-064`), nhập từ Excel/Word (`B-075`), API tạo hàng loạt (`B-076`), cột/bộ lọc "Của tôi / Dùng chung" ở danh sách TC-07 (thuộc việc làm lại mô hình Khóa học). Ô chọn loại vẫn không liệt kê `file_upload` trừ khi câu đang là loại đó.

## Review Focus

- Lưu tất cả khi một nửa số thẻ lỗi 422: thẻ đã lưu bị khóa, bấm Lưu lần hai chỉ gửi thẻ còn lại, không tạo trùng.
- Lưu tất cả khi thẻ thứ 2 bị lỗi mạng giữa chừng: thẻ 1 giữ "Đã lưu", thẻ 3 chưa gửi, không điều hướng.
- Xóa thẻ đã lưu hoặc thẻ cuối cùng: thẻ cuối không xóa được; thẻ đã lưu không còn nút xóa/nhân bản gây hiểu nhầm.
- Nhân bản thẻ có đáp án Nối/Sắp xếp: thẻ mới phải có mã vế/mục mới, không trùng mã với thẻ gốc.
- Đổi tab Cài đặt ↔ Câu hỏi và vào/ra Xem trước không làm mất dữ liệu đã gõ (mọi thẻ luôn mounted).
- `returnTo` ngoài cổng Giáo viên vẫn bị bỏ; `select` nhiều id trên trang chọn câu có id không còn tồn tại hoặc trùng: bỏ qua, không lỗi trắng trang.
- Điểm chung không phải số hoặc âm: chặn lưu, mở tab Cài đặt và hiện lỗi.

---

### Task 1: Lớp dữ liệu — hiển thị, ghép cài đặt chung, lưu tuần tự

**Files:**
- Modify: `piclass-web/src/features/question-bank/api/types.ts`, `api/question-form.ts`, `api/question-schema.ts`, `api/questions.ts`
- Create: `piclass-web/src/features/question-bank/api/save-questions.ts`
- Test: `api/question-form.test.ts` (sửa), `api/save-questions.test.ts` (mới)

**Interfaces:**
- Produces:
  - `type Visibility = "shared" | "private"` (types.ts); `QuestionDetail.visibility: Visibility`; `QuestionWriteInput.visibility?: Visibility`.
  - `QuestionFormValues.visibility: Visibility` (mặc định `"shared"` trong `emptyForm`; `fromQuestion` đọc từ `question.visibility`).
  - `interface SharedSettings { visibility: Visibility; subject_id: string; difficulty: string; suggested_points: string; topics: string; tags: string }`, `sharedFromValues(v: QuestionFormValues): SharedSettings`, `emptySharedSettings(): SharedSettings`.
  - `applyShared(card: QuestionFormValues, shared: SharedSettings, perCardPoints: boolean): QuestionFormValues`: ghi đè `visibility, subject_id, difficulty, topics, tags`; `suggested_points` = điểm của thẻ nếu `perCardPoints` và khác rỗng, ngược lại điểm chung.
  - `sharedSettingsSchema` (zod) trong `question-schema.ts`: `suggested_points` rỗng hoặc số ≥ 0, thông báo "Điểm gợi ý phải là số không âm".
  - `useSetQuestionVisibility(id: string)` trong `questions.ts`: mutation `PATCH /questions/{id}/visibility` body `{ visibility }`, invalidate `questionBankKeys.all()`.
  - `saveCardsInOrder(cards: { key: string; values: QuestionFormValues }[], create: (body: QuestionWriteInput) => Promise<{ id: string }>): Promise<{ key: string; id?: string; error?: unknown }[]>`: gọi `create(toWriteInput(values, "create"))` tuần tự theo thứ tự mảng; lỗi của một thẻ được ghi vào kết quả của thẻ đó, **vẫn gửi các thẻ sau**.

- [ ] **Step 1: Viết test hỏng** — `question-form.test.ts`: (a) `toWriteInput(values, "create")` gửi `visibility`; chế độ `"edit"` không gửi `visibility` và không gửi `subject_id`; (b) `applyShared` với `perCardPoints=true`: thẻ có điểm "2" giữ "2", thẻ rỗng lấy điểm chung "1"; với `perCardPoints=false` điểm chung luôn thắng; luôn ghi đè môn, topic, tag, độ khó, hiển thị; (c) `fromQuestion` đọc `visibility: "private"`. `save-questions.test.ts`: thẻ 1 ok, thẻ 2 `create` ném lỗi, thẻ 3 ok → kết quả `[{key:"a",id:"q1"},{key:"b",error},{key:"c",id:"q3"}]`, thứ tự gọi đúng a, b, c, và `create` được gọi đúng 3 lần.
- [ ] **Step 2: Chạy, xác nhận hỏng**: `docker compose exec web pnpm vitest run src/features/question-bank/api` (từ `piclass-infra/local`). Expected: FAIL (thiếu export).
- [ ] **Step 3: Cài đặt** theo Interfaces ở trên. `emptyForm` thêm `visibility: "shared"`; `toWriteInput` chỉ gán `body.visibility` khi `mode === "create"`.
- [ ] **Step 4: Chạy lại** cùng lệnh. Expected: PASS, các test cũ trong `question-form.test.ts` vẫn xanh (cập nhật literal `QuestionFormValues` nếu thiếu trường mới).
- [ ] **Step 5: Commit** `feat(question-bank): visibility, shared settings merge and sequential save helpers`.

### Task 2: Trang chọn câu nhận nhiều câu vừa lưu

**Files:**
- Modify: `piclass-web/src/features/question-bank/ui/teacher/question-picker-screen.tsx`, `piclass-web/src/app/(portal)/teacher/assessments/[assessmentId]/pick/page.tsx`
- Test: `question-picker-screen.test.tsx`

**Interfaces:**
- Consumes: `useQuestion`, `useQuestions` (đã có).
- Produces: `QuestionPickerScreen` đổi prop `selectQuestionId?: string` thành `selectQuestionIds?: string[]`; `page.tsx` truyền `select?.split(",").filter(Boolean)`. Dùng `useQueries` (TanStack) tải chi tiết các id chưa nằm trong trang đang xem; id không tải được (404) bị bỏ qua.

- [ ] **Step 1: Test hỏng** — thay test "câu vừa soạn (selectQuestionId) được tick sẵn" bằng: truyền `selectQuestionIds={["q1","q9"]}` (q1 trong danh sách, q9 ngoài trang phải tải riêng qua `GET /questions/q9`) → cả hai tick sẵn, nút thêm ghi "Thêm 2 câu vào bài" và gửi 2 `POST …/questions` đúng `question_version_id`; thêm test id không tồn tại (`404`) → bị bỏ qua, các câu còn lại vẫn tick sẵn, không lỗi.
- [ ] **Step 2: Chạy, xác nhận hỏng**: `pnpm vitest run src/features/question-bank/ui/teacher/question-picker-screen.test.tsx`. Expected: FAIL.
- [ ] **Step 3: Cài đặt**: thay logic `preVersion` đơn bằng dựng `picked` từ danh sách id (câu có trong `items` lấy phiên bản từ đó, còn lại từ `useQueries`); giữ nguyên cơ chế `changes` (bỏ tick vẫn ghi đè).
- [ ] **Step 4: Chạy lại** file test. Expected: PASS toàn file.
- [ ] **Step 5: Commit** `feat(question-bank): picker preselects several newly created questions`.

### Task 3: Thẻ câu hỏi có form riêng

**Files:**
- Create: `piclass-web/src/features/question-bank/ui/teacher/question-card.tsx`
- Modify: `question-preview.tsx` (thêm prop `defaultPoints?: string`: khi điểm của thẻ rỗng thì xem trước dùng điểm chung)
- Test: `question-card.test.tsx` (mới)

**Interfaces:**
- Produces: `interface QuestionCardHandle { validate(): Promise<QuestionFormValues | null>; getValues(): QuestionFormValues; setServerErrors(messages: string[]): void }`.
  `QuestionCard(props: { ref?: Ref<QuestionCardHandle>; number: number; showHead: boolean; initial: QuestionFormValues; savedId?: string; readOnly: boolean; preview: boolean; perCardPoints: boolean; pointsPlaceholder?: string; defaultPoints?: string; typeLocked: boolean /* sửa câu cũ: đổi loại cần xác nhận */; onDuplicate?: () => void; onDelete?: () => void })`.
  Thẻ gồm: đầu thẻ ("Câu {n}", badge "Đã lưu" khi có `savedId`, ô điểm `aria-label="Điểm câu {n}"` khi `perCardPoints`, nút nhân bản `aria-label="Nhân bản câu {n}"`, nút xóa `aria-label="Xóa câu {n}"`), ô chọn loại + mô tả, Đề bài, các khối `QuestionChoiceFields`/`QuestionAnswerFields`/`QuestionStructureFields` (giữ nguyên), "Hướng dẫn làm bài"/"Hướng dẫn / tiêu chí chấm" (`instructions`), vùng lỗi máy chủ. `preview` bật thì chỉ render `QuestionPreview`. `readOnly` hoặc `savedId` khóa mọi ô và ẩn nhân bản/xóa của thẻ đã lưu. Đổi loại: tạo mới thì `switchType` ngay (giữ đề bài, hướng dẫn, điểm), sửa thì hộp xác nhận như hiện tại.
  `validate()` chạy `form.trigger()` + zod `questionFormSchema`, trả giá trị khi hợp lệ, `null` khi có lỗi (lỗi hiện theo trường).
  Nhân bản: `onDuplicate` do màn ngoài xử lý bằng `getValues()`; hàm `cloneForDuplicate(v: QuestionFormValues): QuestionFormValues` (export từ `api/question-form.ts`) sinh **mã mới** cho `pairs` (`leftId`, `rightId`) và `items` (`id`) bằng `newId()`, giữ nguyên phần còn lại.

- [ ] **Step 1: Test hỏng** (`question-card.test.tsx`, render trong `QueryClientProvider`): (a) thẻ trống `validate()` → `null` và hiện "Vui lòng nhập đề bài"; (b) điền đề + 2 lựa chọn + 1 đúng → `validate()` trả giá trị có `prompt`; (c) `savedId="q1"` hiện "Đã lưu", ô đề bài `disabled`, không có nút xóa/nhân bản; (d) `preview` ẩn ô đề bài, hiện đề bài dạng học sinh; (e) `perCardPoints` hiện ô điểm với placeholder "Chung: 1"; (f) `cloneForDuplicate` của câu Nối: `leftId`/`rightId` khác bản gốc, nội dung giống; (g) `setServerErrors(["Lỗi X"])` hiện `role="alert"` có "Lỗi X".
- [ ] **Step 2: Chạy, xác nhận hỏng**: `pnpm vitest run src/features/question-bank/ui/teacher/question-card.test.tsx`. Expected: FAIL.
- [ ] **Step 3: Cài đặt** `QuestionCard` bằng cách chuyển phần thân của `QuestionEditorForm` hiện tại (chọn loại, đề bài, ba khối, hướng dẫn, lỗi máy chủ, hộp xác nhận đổi loại) vào component mới; dùng `useImperativeHandle` cho `QuestionCardHandle`; không đọc ref lúc render. Bố cục thẻ theo mockup dòng 659-700 (thẻ trắng bo 20px, `p-[18px_20px]`, các khối cách nhau 14px).
- [ ] **Step 4: Chạy lại** file test. Expected: PASS.
- [ ] **Step 5: Commit** `feat(question-bank): self-contained question card with own form`.

### Task 4: Tab Cài đặt và đầu trang

**Files:**
- Create: `piclass-web/src/features/question-bank/ui/teacher/question-settings-tab.tsx`, `question-editor-header.tsx`
- Delete: `question-classify-card.tsx` (nội dung chuyển vào `question-settings-tab.tsx`)
- Test: `question-settings-tab.test.tsx`, `question-editor-header.test.tsx` (mới)

**Interfaces:**
- Consumes: `SharedSettings`, `sharedSettingsSchema` (Task 1); `Subject` từ `@/features/academic`.
- Produces:
  - `QuestionSettingsTab(props: { form: UseFormReturn<SharedSettings>; subjects: Subject[]; isNew: boolean; readOnly: boolean; multi: boolean; onVisibilityChange?: (v: Visibility) => void })`. Hai thẻ theo mockup dòng 732-760: **"Môn và phân loại"** (Môn: chọn khi `isNew`, chữ "Môn: X (không đổi được sau khi tạo)" khi không; Topic bị khóa khi chưa chọn môn, hint "Chọn môn để gắn topic"; Tag; **Hiển thị** Select với hai lựa chọn "Chung (mọi giáo viên dùng được)" / "Riêng tư (chỉ mình tôi và Admin)" và dòng gợi ý bên dưới: tạo mới "Áp dụng cho mọi câu trong lượt này. Câu chung thì giáo viên khác dùng và sao chép được nhưng không sửa được. Đổi lại bất kỳ lúc nào; bài đã dùng câu này không đổi.", câu đã có "Đổi hiển thị không tạo phiên bản mới. Bài đã dùng câu này không đổi.") và **"Độ khó và điểm"** (Độ khó; Điểm gợi ý, `error` từ form). Dòng thông báo xanh trên cùng: `multi` thì "Cài đặt chung áp dụng cho mọi câu trong lượt này. Điểm của từng câu sửa riêng được ở thẻ câu.", ngược lại "Mỗi lần lưu tạo một phiên bản mới của câu này." Chọn Hiển thị gọi `form.setValue` rồi `onVisibilityChange?.(v)`.
  - `QuestionEditorHeader(props: { title: string; backHref: string; backLabel: string; badges: { label: string; tone: BadgeTone }[]; canArchive: boolean; onArchive: () => void; onPreview: () => void; onAddCard?: () => void; saveLabel?: string; saving: boolean; saveDisabled?: boolean; onSave?: () => void; description?: string })`. Khi `onAddCard` có thì hiện nút "Thêm câu" (outline, `iconLeft="plus"`) trước "Xem trước"; `saveLabel` rỗng thì không có nút Lưu (chỉ xem). `description` hiện dưới hàng `h1`.

- [ ] **Step 1: Test hỏng** — settings: (a) `isNew` hiện Select Môn, topic bị khóa tới khi chọn môn; (b) `!isNew` hiện chữ môn không đổi được; (c) đổi Hiển thị sang "Riêng tư" gọi `onVisibilityChange("private")`; (d) `multi` hiện dòng thông báo "Cài đặt chung…"; (e) điểm không hợp lệ hiện lỗi từ `form`. Header: (f) `h1` và các badge cùng một phần tử cha; (g) có `onAddCard` thì có nút "Thêm câu", không có thì không; (h) `canArchive` hiện "Lưu trữ" và gọi `onArchive`; (i) `backHref`/`backLabel` hiện đúng ở liên kết "‹ …".
- [ ] **Step 2: Chạy, xác nhận hỏng**: `pnpm vitest run src/features/question-bank/ui/teacher/question-settings-tab.test.tsx src/features/question-bank/ui/teacher/question-editor-header.test.tsx`. Expected: FAIL.
- [ ] **Step 3: Cài đặt** hai component; dùng `Card`, `Select`, `Input`, `LinkButton`, `Badge` đã có; xóa `question-classify-card.tsx` và sửa import ở nơi dùng.
- [ ] **Step 4: Chạy lại** hai file test. Expected: PASS.
- [ ] **Step 5: Commit** `feat(question-bank): settings tab and detail-style header`.

### Task 5: Màn soạn — tạo nhiều câu, sửa, xem trước, quay về

**Files:**
- Modify (viết lại): `piclass-web/src/features/question-bank/ui/teacher/question-editor-screen.tsx`
- Test: `question-editor-screen.test.tsx` (sửa), `question-editor-return.test.tsx` (sửa), `question-editor-preview.test.tsx` (sửa)

**Interfaces:**
- Consumes: Task 1 (`applyShared`, `saveCardsInOrder`, `useSetQuestionVisibility`, `cloneForDuplicate`), Task 3 (`QuestionCard`, `QuestionCardHandle`), Task 4 (`QuestionSettingsTab`, `QuestionEditorHeader`), `Tabs` (`variant="line"`).
- Produces: `QuestionEditorScreen({ questionId?, returnTo? })` giữ nguyên chữ ký công khai (export ở `index.ts`). Trạng thái cục bộ: `cards: { key: string; initial: QuestionFormValues; savedId?: string }[]` (tạo mới bắt đầu với 1 thẻ `single_choice`), `tab: "content" | "settings"`, `preview: boolean`; form chung `useForm<SharedSettings>` (khởi tạo từ câu hiện có khi sửa/xem, `emptySharedSettings()` khi tạo mới).

Hành vi cần có:
- Tạo mới: tiêu đề theo số thẻ, nút "Thêm câu" ở đầu trang **và** ô viền đứt ở cuối danh sách (cùng gọi một hàm thêm; thẻ mới cùng loại với thẻ cuối, chuyển về tab Câu hỏi). Nhân bản chèn ngay sau thẻ gốc với `cloneForDuplicate`; xóa chỉ khi còn ≥ 2 thẻ.
- Nút lưu: `Lưu câu hỏi` (1 thẻ chưa lưu) / `Lưu tất cả (N câu)` (N thẻ chưa lưu) / vô hiệu khi mọi thẻ đã lưu. Bấm: (1) validate form chung — lỗi thì chuyển sang tab Cài đặt; (2) gọi `validate()` từng thẻ chưa lưu, lỗi hiện theo trường; (3) gửi các thẻ hợp lệ bằng `saveCardsInOrder(..., create.mutateAsync)` với `applyShared(values, shared, true)`; (4) thẻ thành công → `savedId`; thẻ lỗi từ BE → `setServerErrors` (dùng `isApiError`, giống `onError` cũ); (5) còn thẻ chưa lưu → toast cảnh báo "Đã lưu x/y câu" và ở lại, ngược lại toast thành công và điều hướng theo Global Constraints.
- Sửa câu của mình: một thẻ (`perCardPoints=false`), form chung seed từ câu; Lưu → `PATCH` với `applyShared(values, shared, false)`, toast "Đã lưu phiên bản v{n}", về `/teacher/question-bank`. Đổi Hiển thị → `useSetQuestionVisibility` ngay, lỗi thì trả lại giá trị cũ và toast lỗi. Nhãn nút "Lưu v{n+1}", dòng ghi chú "Lưu sẽ tạo phiên bản v{n+1}. Bài đã giao trước đó vẫn dùng nội dung cũ."
- Chỉ xem (người khác tạo hoặc đã lưu trữ): `readOnly`, không có nút Lưu/Thêm câu, ghi chú như hiện tại ("Chỉ người tạo hoặc quản trị viên được sửa câu này." / badge "Đã lưu trữ"). "Lưu trữ" giữ nguyên hành vi hiện tại.
- Xem trước: đầu trang chỉ còn dải xanh (icon mắt, "Đang xem trước như học sinh", nút "Thoát xem trước"); tab, nút soạn ẩn; mọi `QuestionCard` nhận `preview`; vùng nội dung cột giữa rộng 780px.
- `returnTo` đi qua `safeReturnTo` như cũ; nhãn nút quay lại "Bài đang soạn" khi có `returnTo`.

- [ ] **Step 1: Test hỏng** (MSW, `onUnhandledFrame: "error"`, `next/navigation` mock `push`):
  1. tạo mới mặc định: có tab "Câu hỏi" và "Cài đặt", tiêu đề "Soạn câu hỏi", có nút "Thêm câu" ở đầu trang; bấm → tiêu đề "Soạn 2 câu hỏi", nút lưu "Lưu tất cả (2 câu)".
  2. tạo 2 câu hợp lệ: bấm "Lưu tất cả" → đúng 2 `POST /questions` theo thứ tự, cả hai body có `visibility: "shared"` và môn/độ khó chung từ tab Cài đặt, thẻ 2 để trống điểm lấy điểm chung, thẻ 1 điểm riêng thắng; `push("/teacher/question-bank")`.
  3. Một thẻ lỗi 422 (thẻ 2): thẻ 1 hiện "Đã lưu" và khóa, thẻ 2 hiện lỗi, không `push`; sửa thẻ 2 rồi bấm lại chỉ gửi 1 `POST` (tổng 3 lần, không trùng thẻ 1).
  4. Thẻ 2 chưa điền đề: không gọi BE cho thẻ 2, thẻ 1 vẫn lưu, thẻ 2 hiện "Vui lòng nhập đề bài".
  5. `returnTo="/teacher/assessments/a1/pick"`: lưu 2 câu → `push("/teacher/assessments/a1/pick?select=q1,q2")`; `returnTo="//evil.com"` → bỏ.
  6. nhân bản/xóa: nhân bản câu Nối → thẻ mới, sửa thẻ mới không đổi thẻ gốc; chỉ còn 1 thẻ thì không có nút xóa.
  7. điểm chung "abc" ở tab Cài đặt → bấm lưu hiện lỗi "Điểm gợi ý phải là số không âm", chuyển sang tab Cài đặt, không gọi BE.
  8. sửa câu của mình (giữ test cũ): `PATCH` không gửi `subject_id`/`visibility`, nhãn "Lưu v2"; đổi Hiển thị sang Riêng tư gửi `PATCH /questions/q1/visibility {"visibility":"private"}` và không gọi `PATCH /questions/q1`.
  9. chỉ xem: không có nút Lưu, không có "Thêm câu", ô Hiển thị bị khóa.
  10. Xem trước: bấm → dải "Đang xem trước như học sinh", mọi thẻ hiện dạng học sinh, tab biến mất; "Thoát xem trước" quay lại, dữ liệu đã gõ vẫn còn.
  11. giữ lại các test hiện có còn đúng: lỗi theo trường khi lưu thiếu dữ liệu, ô chọn loại không có Nộp file, điền khuyết gửi `grading_config.blanks`, đổi loại khi sửa phải xác nhận, câu Nối đọc lại và gửi lại đúng mã vế.
- [ ] **Step 2: Chạy, xác nhận hỏng**: `pnpm vitest run src/features/question-bank/ui/teacher/question-editor-screen.test.tsx src/features/question-bank/ui/teacher/question-editor-return.test.tsx src/features/question-bank/ui/teacher/question-editor-preview.test.tsx`. Expected: FAIL (mỗi test mới hỏng vì thiếu tính năng, không phải lỗi cú pháp).
- [ ] **Step 3: Cài đặt** `QuestionEditorScreen` theo "Hành vi cần có". Giữ file ≤ 200 dòng bằng cách tách `useQuestionCards` (trạng thái thẻ: thêm/nhân bản/xóa/đánh dấu đã lưu) thành hook trong `ui/teacher/use-question-cards.ts`; không dùng `form.watch` (dùng `useWatch`); không đọc ref lúc render (chỉ trong handler).
- [ ] **Step 4: Chạy lại** ba file test. Expected: PASS; sau đó chạy cả thư mục `src/features/question-bank` và `src/features/assessment` (picker và tab Câu hỏi của Soạn bài) từng file một (chạy cả bộ vitest một lượt từng bị treo ở lát 4b).
- [ ] **Step 5: Commit** `feat(question-bank): multi-card question editor with shared settings and preview mode`.

### Task 6: Kiểm tra cuối và đồng bộ tài liệu

**Files:**
- Modify: `piclass-web/src/features/question-bank/ui/teacher/question-bank-screen.tsx` (nếu còn gọi `?select=` đơn), `piclass-web/src/features/assessment/ui/teacher/assessment-questions-tab.tsx` (chỉ kiểm: nút "Soạn câu mới" vẫn trỏ `new?returnTo=…`)
- Modify (docs repo): `docs/STATE.md`

- [ ] **Step 1: Kiểm** `grep -rn "selectQuestionId\|select=" piclass-web/src` không còn chỗ dùng dạng đơn.
- [ ] **Step 2: Chạy** `pnpm lint`, `pnpm typecheck` ở `piclass-web`; Expected: không lỗi mới, không file mới > 200 dòng.
- [ ] **Step 3: So khớp màn thật với mockup** (bước 5 của `docs/process/working-flow.md`): mở `PiClass Giáo viên.dc.html` dòng 617-760 và đối chiếu từng khối với màn đã dựng (đầu trang, hàng badge, tab, thẻ câu, ô Thêm câu, hai thẻ Cài đặt, ô Hiển thị, dải xem trước). Ghi chú lệch (nếu có) và sửa tại chỗ.
- [ ] **Step 4: Review độc lập** cuối nhánh: `Agent` với `model: "opus"` tường minh, đưa diff và Review Focus ở trên.
- [ ] **Step 5: Cập nhật docs**: `docs/STATE.md` mục "Bước" của lát 4e chuyển sang `review → merge`; spec TC-08 không đổi trừ khi bước 3 phát hiện chỗ lệch. Commit và push cả hai repo (không mở PR/merge khi chưa được yêu cầu).
