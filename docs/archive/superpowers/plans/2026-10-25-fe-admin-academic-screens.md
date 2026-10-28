# FE Admin — Môn học, Lớp học, Chi tiết lớp (nhóm 2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:test-driven-development to implement this plan task-by-task.

**Goal:** Ghép FE thật (Next.js) cho AD-08 (Môn học), AD-09 (Lớp học — danh sách), AD-10 khung chi tiết lớp + AD-10a (Tổng quan) + AD-10b (Học sinh) + AD-10c (Giáo viên), gọi API thật theo đúng CLAUDE.md (`piclass-web`). **Không làm** AD-10d (Lịch) và AD-10e (Buổi học) — để placeholder tab, thuộc nhóm 6.

**Spec:** `docs/product/screens/admin.md` mục `### AD-08`, `### AD-09`, `### AD-10` (+ `#### AD-10a/b/c`). API thật: `piclass-api` nhánh local `feat/invite-auto-assign-role` (module `Academic`, `UserManagement`), đã đọc code thật, không chỉ tin mô tả trong `admin.md` (một số chỗ đã lệch nhẹ so với code thật — ghi rõ trong plan này).

**Bối cảnh tổ chức code:** đọc kỹ `piclass-web/CLAUDE.md` trước khi code — đặc biệt bảng "Luồng một màn hình" (route mỏng F1, chỉ `api-client` F2, `features/*/api` không biết UI F3, `ui/<vai-trò>` không import vai trò khác F4, feature khác chỉ qua `index.ts` F5, không tự quyết nghiệp vụ F7, mọi thời điểm qua `lib/time.ts` F8) và mục "Design system" (port đúng từng giá trị, không làm tròn — xem lại quy tắc 2026-10-25 trong CLAUDE.md). Slice mẫu đã có: `src/features/user-management/` (teacher-*) — bám đúng pattern file đó (hook React Query, `applyFieldErrors` cho 422, dialog M4 tách riêng, `*-badges.ts` cho tone, Skeleton/empty/error state).

## Global Constraints

- Subject/Class là dữ liệu của module **Academic** → đặt ở `src/features/academic/` (feature mới, đã nằm trong danh sách `FEATURES` hợp lệ của `architecture.test.ts`). ClassMember và TeacherClassAssignment thuộc module **UserManagement** (`docs/architecture/module-boundaries.md`) → đặt ở `src/features/user-management/` (feature đã có, tái dùng `teachers.ts` cho danh sách giáo viên ở AD-10c).
- `GET /subjects`, `GET /classes` **không phân trang** (`AnonymousResourceCollection` trả `{data:[...]}` thẳng, không có `meta`) — khác `GET /teachers`. `apiClient` bóc `data` tự động vì thân trả không có khóa `meta` cạnh `data`; hàm api khai kiểu trả về là `T[]` thẳng, không dùng `Paginated<T>`.
- Không copy JSX/style trực tiếp từ mockup; mọi giá trị (padding, font-size, màu, radius) phải đọc từ `piclass-mockup/ds-v2-source/_ds_bundle.js` (grep `^function <Tên>`) hoặc đối chiếu `piclass-mockup/design-system/standalone/PiClass Quản trị.html` (đã build, state đầy đủ) trước khi viết. Tham khảo thêm (không copy) `piclass-mockup/ds-v2-source/ui_kits/admin/admin-classes.js` — **lưu ý file này rất ngắn/có vẻ chưa đồng bộ** so với bản standalone HTML (dòng 490–1990 của `PiClass Quản trị.html` mới là nơi có đủ logic AD-08/09/10 thật: biến `cc`/`cd`/`cdv`/`sjOpen`...), nên **standalone HTML là nguồn tham chiếu chính** cho luồng/state của 3 màn này, `ui_kits` chỉ để đọc thêm nếu cần.
- Comment code tiếng Việt; tên biến/hàm/file/test tiếng Anh. Commit message tiếng Anh, kết thúc `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.
- Branch mới từ `origin/main` mới nhất (`git fetch origin` trước), không commit vào `main`. Không tự push/PR/merge trừ khi user yêu cầu rõ.
- F7: không tự thêm rule nghiệp vụ. Mọi lỗi 422/409 hiển thị nguyên văn `error.message` của BE theo `code` (rẽ nhánh theo `error.code`, không theo message) — danh mục mã dùng trong plan này: `SUBJECT_INACTIVE` (422), `VALIDATION_FAILED` (422, có hoặc không có `details`), `ALREADY_A_MEMBER` (409), `ALREADY_ASSIGNED` (409), `TEACHER_NOT_ACTIVE` (422).

## Quyết định tự chọn khi đọc code (cần user xác nhận — xem tóm tắt cuối báo cáo)

1. **Input hỗ trợ `multiline`:** `components/ui/input.tsx` hiện chưa có chế độ nhiều dòng (comment "chưa cần multiline ở lát này"). AD-08/AD-09/AD-10a đều có trường "Mô tả" nhiều dòng. Cần port thêm từ `_ds_bundle.js` (`components/forms/Input.jsx`): khi `multiline`, render `<textarea rows={rows ?? 3}>`, `align-items: flex-start`, padding `10px 14px` (khác input một dòng dùng `px` cố định theo `shape`). Làm ở Task 1, trước khi chạm màn nào.
2. **Dropdown chọn giáo viên ở AD-10c không phân trang được qua UI chọn đơn giản:** `GET /teachers` có phân trang (`per_page`, mặc định 15). Quyết định: gọi `useTeachers({status:"active", per_page:100})` (đã đủ cho quy mô V1 — 1 trung tâm), có ô tìm tên debounce giống AD-07 nếu danh sách giáo viên hoạt động vượt một trang (ít khả năng với trung tâm nhỏ, nhưng cứ thêm ô tìm cho chắc). Không thêm "chọn nhiều trang" phức tạp. (Sửa lại ở quyết định #6 bên dưới: không lọc `status` để còn thấy giáo viên inactive mà làm mờ.)
3. **AD-10a "số buổi sắp tới"**: cần ghép từ tab Lịch/Buổi học (nhóm 6, chưa làm). Quyết định: card "số liệu nhanh" ở AD-10a chỉ hiện 2 ô (Học sinh, Giáo viên) lấy từ `useClassMembers`/`useClassTeachers` (độ dài mảng); bỏ ô "buổi sắp tới" ở lượt này, không query `next_session_at` riêng (API chi tiết `GET /classes/{id}` không trả field đó — chỉ `GET /classes` danh sách có; không hợp lý gọi riêng danh sách chỉ để lấy 1 field).
4. **AD-10a "Lịch sử thay đổi gần đây" (🔵 GM-7, lọc audit theo lớp)**: chưa có API lọc audit theo lớp ở lượt BE hiện tại — bỏ hẳn khối này ở lượt này (giữ nguyên trạng thái 🔵 chưa làm, không build UI rỗng chờ data).
5. **Tab Lịch/Buổi học (AD-10d/AD-10e)**: hiện tab trong dải tab của khung AD-10 (đúng mô tả "khung 5 tab") nhưng nội dung là thông báo tĩnh "Lịch và Buổi học sẽ có ở lượt kế tiếp" — không gọi bất kỳ API `/schedules`, `/lesson-sessions` nào, không đếm số lượng cạnh tên tab (để trống, không hiện `(0)` gây hiểu nhầm).
6. **Banner lưu ý "BE hiện không chặn thêm học viên/giáo viên vào lớp đã lưu trữ"**: theo AD-10 mô tả "cần xác nhận khi dựng" — quyết định FE: vẫn hiện nút "Thêm học sinh"/"Phân công giáo viên" dù lớp `archived` (không tự chặn — F7, BE là nguồn sự thật), nhưng thêm banner cảnh báo tĩnh ở đầu hai tab khi lớp đã lưu trữ: "Lớp đã lưu trữ; thêm thành viên mới không được khuyến khích." Không ẩn nút.
7. **Nút "Thêm học sinh" ở AD-10b**: ẩn hẳn thanh hành động chính, thay bằng nút `disabled` + `Tooltip` "Cần màn tìm học sinh (nhóm 3, sắp có)" — theo đúng yêu cầu đề bài, dùng component `components/ui/tooltip.tsx` đã có sẵn.

## Review Focus

- `GET /subjects`/`GET /classes` không phân trang — không viết nhầm kiểu `Paginated<T>`.
- `SUBJECT_INACTIVE` hiện đúng dòng dưới ô "Môn học" kèm link AD-08 (không phải toast chung).
- Lớp không gửi `subject_id` khi `PATCH /classes/{id}` (input type không có field này — TypeScript tự chặn).
- Trạng thái lớp **không có nút "Kích hoạt lớp" tay** — chỉ đổi khi phân công/kết thúc giáo viên (phản ứng theo response của BE, không tự suy đoán ở FE).
- `ALREADY_A_MEMBER`, `ALREADY_ASSIGNED` (409), `TEACHER_NOT_ACTIVE` (422) hiện đúng thông điệp map theo `admin.md`, không đứng im lặng.
- Sau khi phân công/kết thúc phân công giáo viên thành công, tab Tổng quan (chip trạng thái lớp ở đầu khung AD-10) phải tự cập nhật — bằng `refetch()` chủ động từ `ClassDetailScreen`, không viết cách invalidate query key xuyên feature thủ công.
- Mọi ngày/giờ hiển thị (`valid_from`, `valid_to`, `next_session_at`...) qua `lib/time.ts` (F8), không gọi `toLocaleDateString` trực tiếp.

---

## API thật đã đọc (tóm tắt hợp đồng dùng trong plan)

**Subject (`app/Modules/Academic`):**
- `GET /subjects` → `{ data: [{ id, name, description, status: "active"|"inactive" }] }` (admin: toàn bộ, không phân trang, sort theo tên).
- `POST /subjects` body `{ name, description? }` → 201 `{ subject: { id, name, description, status } }`. Rule: `name` required string; không check trùng tên.
- `PATCH /subjects/{id}` body `{ name?, description?, status? }` (status: `active`|`inactive`) → 200 `{ subject: {...} }` (cùng field với GET).

**Class (`app/Modules/Academic`):**
- `GET /classes` → `{ data: [{ id, subject_id, name, label, description, status: "preparing"|"active"|"archived", teacher_names: string[], student_count: number, next_session_at: string|null }] }` — **field mở rộng (C3) luôn có mặt trên danh sách**, không có ở chi tiết.
- `GET /classes/{id}` → `{ class: { id, subject_id, name, label, description, status } }` — **không có** `teacher_names`/`student_count`/`next_session_at`.
- `POST /classes` body `{ subject_id, name, label?, description? }` → 201 `{ class: { id, subject_id, name, label, status } }` — **không có `description` trong response tạo** (field đã gửi, chỉ không trả lại — không phải bug, không hiển thị sai).
- `PATCH /classes/{id}` body `{ name?, label?, description?, status? }` — `status` chỉ nhận `"archived"` (422 nếu gửi `preparing`/`active`). Response 200 `{ class: { id, name, label, description, status } }` — **không có `subject_id`**.
- `SUBJECT_INACTIVE` (422) khi `POST /classes` với `subject_id` đang `inactive`. Message BE: "Môn học đã ngừng hoạt động, không thể tạo lớp mới."

**ClassMember (`app/Modules/UserManagement`, route gắn dưới `classes/{class}`):**
- `GET /classes/{id}/members` → `{ data: [{ student_id, full_name, valid_from, valid_to }] }` (chỉ thành viên `active`).
- `POST /classes/{id}/members` body `{ student_id }` → 201 `{ class_member: { id, class_id, student_id, valid_from } }`. 409 `ALREADY_A_MEMBER` nếu học viên đã là thành viên active.
- `DELETE /classes/{id}/members/{studentId}` body `{ end_reason }` → 204.
- `PATCH /classes/{id}/members/{studentId}` body `{ valid_from?, valid_to?, reason }` → 200 `{ class_member: { id, valid_from, valid_to } }`. `reason` luôn bắt buộc (dùng chung — `end_reason` riêng cho DELETE). `VALIDATION_FAILED` không kèm `details` khi "valid_to phải sau valid_from" — hiện trực tiếp `error.message` dưới ô "Đến ngày".

**TeacherClassAssignment (`app/Modules/UserManagement`):**
- `GET /classes/{id}/teachers` → `{ data: [{ teacher_id, full_name, valid_from, valid_to }] }` (chỉ `active`).
- `POST /classes/{id}/teachers` body `{ teacher_id }` → 201 `{ teacher_class_assignment: { id, class_id, teacher_id, valid_from } }`. 422 `TEACHER_NOT_ACTIVE` nếu hồ sơ Teacher `inactive`; 409 `ALREADY_ASSIGNED` nếu đã có phân công active. **Side effect quan trọng**: BE tự `ActivateClass` (lớp `preparing`→`active`) trong transaction — FE chỉ cần refetch `GET /classes/{id}` sau khi thành công, không tự tính trạng thái.
- `DELETE /classes/{id}/teachers/{teacherId}` → 204 (không nhận lý do). Nếu là giáo viên cuối cùng, BE tự chuyển lớp về `preparing` — cũng chỉ cần refetch.
- `GET /teachers` (đã có, nhóm 1) hỗ trợ `per_page` (query, mặc định 15) — dùng cho dropdown chọn giáo viên.

---

### Task 1: Thêm `multiline` cho `Input` (primitive, tiền đề)

**Files:**
- Modify: `src/components/ui/input.tsx`

**Interfaces:** `InputProps` thêm `multiline?: boolean; rows?: number` (mặc định `rows = 3` khi `multiline`).

- [ ] **Step 1:** Mở `piclass-mockup/ds-v2-source/_ds_bundle.js`, grep `^function Input` để lấy đúng giá trị khi `multiline`: `Tag = multiline ? 'textarea' : 'input'`, `alignItems: multiline ? 'flex-start' : 'center'`, `padding: multiline ? '10px 14px' : ...`. Đối chiếu thêm khối "Mô tả" ở `PiClass Quản trị.html` (dòng ~1277 khu vực dialog Môn học) để chắc chắn không có style khác khi dùng trong dialog.
- [ ] **Step 2:** Sửa `input.tsx`: khi `multiline`, render `<textarea rows={rows} className="min-w-0 flex-1 resize-none bg-transparent text-[14px] text-strong outline-none ...">` thay `<input>`; `<span>` bọc ngoài đổi `items-center` → `items-start` và padding theo đúng giá trị port (`py-2.5 px-3.5` ứng với `10px 14px`, không dùng `h-[42px]` cố định khi multiline).
- [ ] **Step 3:** Viết test nhỏ (nếu repo có test cho `Input`, bổ sung case `multiline` render `<textarea>`; nếu chưa có file test cho `Input`, không bắt buộc thêm — ưu tiên test ở màn dùng nó).
- [ ] **Step 4:** `pnpm lint && pnpm typecheck`.
- [ ] **Step 5:** Commit `feat: support multiline mode in the Input primitive`.

---

### Task 2: `features/academic` — nền (types, keys, schemas, api Subject)

**Files:**
- Create: `src/features/academic/api/types.ts`, `src/features/academic/api/keys.ts`, `src/features/academic/api/schemas.ts`, `src/features/academic/api/subjects.ts`
- Create test: `src/features/academic/api/subjects.test.ts` (mock `apiClient` qua MSW, theo mẫu nếu `teachers.ts` có test tương ứng — nếu không có file test hook riêng ở `user-management`, viết test ở tầng screen thay vì tầng hook, giữ nhất quán với slice mẫu).

**Nội dung `types.ts` (Subject phần, Class sẽ thêm ở Task 4):**
```ts
export type SubjectStatus = "active" | "inactive";
export interface Subject {
  id: string;
  name: string;
  description: string | null;
  status: SubjectStatus;
}
export interface CreateSubjectInput {
  name: string;
  description?: string | null;
}
export interface UpdateSubjectInput {
  name?: string;
  description?: string | null;
  status?: SubjectStatus;
}
```

**`schemas.ts` (phần Subject):** `subjectFormSchema = z.object({ name: z.string().trim().min(1, "Vui lòng nhập tên môn."), description: z.string().trim() })` — chỉ kiểm dạng (F7), không thêm rule trùng tên.

**`keys.ts`:** `academicKeys = { subjects: () => ["academic","subjects"] as const, classes: () => ["academic","classes"] as const, class: (id: string) => ["academic","class", id] as const }`.

**`subjects.ts`:**
```ts
export const subjectsApi = {
  list: () => apiClient.get<Subject[]>("/subjects"),
  create: (input: CreateSubjectInput) =>
    apiClient.post<{ subject: Subject }>("/subjects", input).then((r) => r.subject),
  update: (id: string, input: UpdateSubjectInput) =>
    apiClient.patch<{ subject: Subject }>(`/subjects/${id}`, input).then((r) => r.subject),
};
export function useSubjects() { return useQuery({ queryKey: academicKeys.subjects(), queryFn: subjectsApi.list }); }
export function useCreateSubject() { /* invalidate academicKeys.subjects() */ }
export function useUpdateSubject() { /* mutationFn: (vars: {id, input}) => ...; invalidate subjects() */ }
```

- [ ] **Step 1:** Viết test cho `useSubjects`/`useCreateSubject`/`useUpdateSubject` qua MSW (`GET /api/v1/subjects` trả mảng mẫu 2 môn; `POST` trả 201; `PATCH` trả 200 và trường hợp 422 `name` rỗng → `isApiError` đúng `details.name`).
- [ ] **Step 2:** Chạy test, xác nhận fail (chưa có implementation).
- [ ] **Step 3:** Implement theo nội dung trên.
- [ ] **Step 4:** Test pass. `pnpm typecheck`.
- [ ] **Step 5:** Commit `feat: add the academic feature's subject API layer`.

---

### Task 3: AD-08 — màn Môn học

**Files:**
- Create: `src/features/academic/ui/admin/subject-badges.ts` (tone cho `active`/`inactive`, giống `teacher-badges.ts`)
- Create: `src/features/academic/ui/admin/subject-list-screen.tsx`
- Create: `src/features/academic/ui/admin/subject-form-dialog.tsx` (dùng chung tạo/sửa, M2 trong hộp thoại — theo mockup `sjOpen`/`sjTitle` ở `PiClass Quản trị.html` dòng ~1277)
- Create: `src/features/academic/ui/admin/subject-status-dialog.tsx` (M4 "Ngừng sử dụng"/"Dùng lại", mô tả tĩnh theo `admin.md`, không có ô lý do)
- Create tests: `subject-list-screen.test.tsx`, `subject-form-dialog.test.tsx` (hoặc gộp test tạo/sửa vào test của list-screen nếu dialog luôn render cùng màn — theo đúng cách `teacher-status-dialog` được test gián tiếp qua `teacher-detail-screen.test.tsx`, kiểm lại file test hiện có trước khi quyết định tách/gộp)
- Modify: `src/features/academic/index.ts` — export `SubjectListScreen`
- Create route: `src/app/(portal)/admin/subjects/page.tsx` (import `SubjectListScreen`, F1: chỉ render)
- Modify: `src/config/nav.ts` — thêm `{ label: "Môn học", href: "/admin/subjects", icon: "book-open" }` vào mảng `admin`, ngay sau "Giáo viên" (khớp thứ tự `navItems` ở mockup: teachers → subjects → classes).

**Đọc design system trước khi code:** mở `piclass-mockup/design-system/standalone/PiClass Quản trị.html`, tìm đoạn `subjSummary`/`openSubjNew` (dòng ~874 và ~1277 khu vực dialog `sjOpen`) để xem đúng bố cục bảng (không phân trang), hộp thoại tạo/sửa, và style "môn ngừng sử dụng mờ". Đối chiếu token cụ thể (font-size, màu `--text-muted`, border) với `_ds_bundle.js` nếu nghi ngờ.

**Hành vi cần dựng:**
- Bảng M1 không phân trang (cột Tên, Mô tả, Trạng thái, Số lớp dùng môn — số lớp **FE tự đếm** bằng cách gọi `useClasses()` (Task 5) và `filter(c => c.subject_id === subject.id).length`; đây là tính đếm hiển thị thuần, không phải quyết định nghiệp vụ, chấp nhận theo đúng mô tả "🟡 FE đếm từ GET /classes theo subject_id").
- Bộ lọc trạng thái (tabs hoặc Select, theo mẫu `STATUS_OPTIONS` của AD-07) — lọc client-side (danh sách không phân trang, không cần gọi lại API).
- Nút "Thêm môn học" mở `SubjectFormDialog` ở mode tạo; click dòng (hoặc nút "Sửa") mở cùng dialog ở mode sửa, prefill `name`/`description`.
- Mỗi dòng có menu/nút "Ngừng sử dụng"/"Dùng lại" mở `SubjectStatusDialog` — mô tả tĩnh đúng theo `admin.md`: "Không thể tạo lớp mới cho môn này; các lớp đã có vẫn chạy và sửa bình thường."
- Trạng thái: tải (Skeleton theo mẫu AD-07), rỗng ("Chưa có môn học. Thêm môn đầu tiên" + nút), lỗi (banner wifi-off + nút Thử lại), môn `inactive` hiện mờ (`opacity-75`, giống dòng teacher inactive).
- Lỗi 422 `name` bắt buộc → gán vào field qua `applyFieldErrors`-style (copy pattern từ `teacher-form-screen.tsx`, map `"Vui lòng nhập tên môn."` nguyên văn).

- [ ] **Step 1:** Viết test trước (`subject-list-screen.test.tsx`): tải/rỗng/lỗi/hiện danh sách/mở dialog tạo/tạo thành công gọi đúng `POST /subjects`/lỗi 422 hiện dưới ô Tên/mở dialog trạng thái và xác nhận gọi đúng `PATCH {status}`.
- [ ] **Step 2:** Xác nhận fail.
- [ ] **Step 3:** Implement `subject-badges.ts`, `subject-list-screen.tsx`, `subject-form-dialog.tsx`, `subject-status-dialog.tsx`.
- [ ] **Step 4:** Nối route + `index.ts` + `nav.ts`.
- [ ] **Step 5:** Test pass, `pnpm lint && pnpm typecheck`.
- [ ] **Step 6:** Commit `feat: add the AD-08 subject management screen`.

---

### Task 4: `features/academic` — nền Class (types/keys/schemas/api)

**Files:**
- Modify: `src/features/academic/api/types.ts` (thêm `ClassStatus`, `ClassListItem`, `ClassDetail`, `CreateClassInput`, `CreatedClass`, `UpdateClassInput`, `UpdatedClass` — đúng field theo từng response ở mục "API thật đã đọc" trên, **không gộp chung một `Class` type** vì ba response (list/detail/created/updated) có field khác nhau thật — khai đúng từng interface để TypeScript tự chặn nhầm field, ví dụ không viết `subject_id` vào `UpdatedClass`)
- Modify: `src/features/academic/api/schemas.ts` (thêm `classCreateFormSchema` — `subject_id` required, `name` required, `label`/`description` optional; `classOverviewFormSchema` — `name` required, `label`/`description` optional, **không có field `subject_id`**)
- Create: `src/features/academic/api/classes.ts`
- Create test: `src/features/academic/api/classes.test.ts`

```ts
export type ClassStatus = "preparing" | "active" | "archived";

export const classesApi = {
  list: () => apiClient.get<ClassListItem[]>("/classes"),
  get: (id: string) => apiClient.get<{ class: ClassDetail }>(`/classes/${id}`).then((r) => r.class),
  create: (input: CreateClassInput) =>
    apiClient.post<{ class: CreatedClass }>("/classes", input).then((r) => r.class),
  update: (id: string, input: UpdateClassInput) =>
    apiClient.patch<{ class: UpdatedClass }>(`/classes/${id}`, input).then((r) => r.class),
};
export function useClasses() { ... }
export function useClass(id: string) { ... }
export function useCreateClass() { /* invalidate classes() */ }
export function useUpdateClass(id: string) { /* invalidate classes() + class(id) */ }
```

- [ ] **Step 1:** Test trước (MSW: `GET /classes` trả mảng có `teacher_names`/`student_count`/`next_session_at`; `GET /classes/:id` trả object **không có** 3 field đó — assert kiểu/field đúng; `POST` 201; `PATCH` 200; `PATCH` với `status:"preparing"` giả lập 422 để test nhánh lỗi không crash FE).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Pass + typecheck.
- [ ] **Step 5:** Commit `feat: add the academic feature's class API layer`.

---

### Task 5: AD-09 — màn Lớp học (danh sách) + hộp thoại Tạo lớp

**Files:**
- Create: `src/features/academic/ui/admin/class-badges.ts` (tone theo 3 trạng thái: `preparing` = vàng/cam, `active` = xanh lá, `archived` = trung tính mờ — đọc đúng tone ở mockup, không tự chọn)
- Create: `src/features/academic/ui/admin/class-list-screen.tsx`
- Create: `src/features/academic/ui/admin/class-list-table.tsx`
- Create: `src/features/academic/ui/admin/class-create-dialog.tsx`
- Create tests tương ứng
- Modify: `src/features/academic/index.ts` — export `ClassListScreen`
- Create route: `src/app/(portal)/admin/classes/page.tsx`
- Modify: `src/config/nav.ts` — thêm `{ label: "Lớp học", href: "/admin/classes", icon: "layout-grid" }` sau "Môn học".

**Đọc design system trước khi code:** `PiClass Quản trị.html` khu vực `clsSummary`/`cc` (tạo lớp, dòng ~2635 và lân cận — bản thứ hai trong cùng file, vì mockup gộp nhiều biến thể; chọn khối gần `ccSubjOptions`/`onCcSubj` dòng ~3280 làm tham chiếu dialog tạo lớp) và đoạn kiểm tra lỗi `err.subj` dòng ~1601 (đúng message "Môn này đã ngừng sử dụng. Chọn môn khác hoặc dùng lại môn này ở mục Môn học." — khớp với BE `SUBJECT_INACTIVE`, FE chỉ hiển thị lại message BE, **không tự validate** môn inactive ở FE trừ việc lọc option hiển thị).

**Hành vi:**
- Bảng M1 không phân trang. Cột: Tên lớp, Nhãn, Môn (ghép tên từ `subject_id` qua `useSubjects()`), Trạng thái (badge 3 tone), cột nâng cao G9 **đã có dữ liệu thật** nên làm luôn: Giáo viên (`teacher_names.join(", ")` hoặc "—"), Sĩ số (`student_count`), Buổi kế tiếp (`next_session_at` qua `formatDateTime` từ `lib/time.ts`, hoặc "—" khi null).
- Bộ lọc: tìm tên (debounce, client-side vì không phân trang — **không cần gọi API mỗi lần gõ**, chỉ filter mảng đã tải, bỏ qua gợi ý "tìm không dấu ở BE" vì FE tự lọc chuỗi con không dấu bằng cách chuẩn hóa `normalize("NFD")` — ghi rõ trong comment đây là lọc hiển thị, không phải gọi lại `/classes`); trạng thái (tabs); môn (Select, options từ `useSubjects()`). Mặc định ẩn lớp `archived` (switch "Hiện lớp đã lưu trữ", `components/ui/switch.tsx` đã có).
- Nút "Tạo lớp" mở `ClassCreateDialog`: Select Môn (chỉ liệt kê môn `status==="active"`, lọc client — nếu chưa có môn active nào, disable nút tạo kèm hint dẫn AD-08), Input Tên* , Input Nhãn, Input Mô tả (multiline). Submit `POST /classes` → thành công thì **chuyển hướng `/admin/classes/{id}?tab=teachers`** (query param gợi mở thẳng tab Giáo viên, theo đúng "sau khi tạo dẫn vào AD-10c với gợi ý Phân công giáo viên").
- Trạng thái: tải, rỗng (chưa có lớp — nếu chưa có môn nào thì dẫn AD-08 thay vì nút tạo), rỗng do lọc, lỗi, dòng `archived` mờ.
- Lỗi 422 `SUBJECT_INACTIVE` trong dialog tạo → hiện dưới ô Select Môn với message BE + link `/admin/subjects`. 422 `subject_id`/`name` bắt buộc → gán field.

- [ ] **Step 1:** Test trước: tải/rỗng/lỗi/lọc tên-trạng-thái-môn/switch lớp lưu trữ/tạo lớp thành công điều hướng đúng URL/tạo lớp lỗi `SUBJECT_INACTIVE` hiện đúng chỗ.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối route/nav/index.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-09 class list screen with the create-class dialog`.

---

### Task 6: `features/user-management` — nền ClassMember + TeacherClassAssignment

**Files:**
- Modify: `src/features/user-management/api/types.ts` — thêm:
```ts
export interface ClassMemberRosterItem { student_id: string; full_name: string; valid_from: string; valid_to: string | null; }
export interface ClassMemberAdded { id: string; class_id: string; student_id: string; valid_from: string; }
export interface ClassMemberPeriod { id: string; valid_from: string; valid_to: string | null; }
export interface AddClassMemberInput { student_id: string; }
export interface EndClassMemberInput { end_reason: string; }
export interface UpdateClassMemberInput { valid_from?: string; valid_to?: string | null; reason: string; }

export interface ClassTeacherRosterItem { teacher_id: string; full_name: string; valid_from: string; valid_to: string | null; }
export interface TeacherAssignmentCreated { id: string; class_id: string; teacher_id: string; valid_from: string; }
export interface AssignTeacherInput { teacher_id: string; }
```
  Đồng thời thêm `per_page?: number` vào `TeacherListFilters` (để AD-10c gọi `useTeachers({status:"active", per_page:100})`).
- Modify: `src/features/user-management/api/keys.ts` — thêm `classMembers: (classId: string) => [...] `, `classTeachers: (classId: string) => [...]`.
- Create: `src/features/user-management/api/class-members.ts`
- Create: `src/features/user-management/api/class-teacher-assignments.ts`
- Create tests: `class-members.test.ts`, `class-teacher-assignments.test.ts`

```ts
// class-members.ts
export const classMembersApi = {
  list: (classId: string) => apiClient.get<ClassMemberRosterItem[]>(`/classes/${classId}/members`),
  add: (classId: string, input: AddClassMemberInput) =>
    apiClient.post<{ class_member: ClassMemberAdded }>(`/classes/${classId}/members`, input).then((r) => r.class_member),
  end: (classId: string, studentId: string, input: EndClassMemberInput) =>
    apiClient.delete<void>(`/classes/${classId}/members/${studentId}`, { body: input }),
  update: (classId: string, studentId: string, input: UpdateClassMemberInput) =>
    apiClient.patch<{ class_member: ClassMemberPeriod }>(`/classes/${classId}/members/${studentId}`, input).then((r) => r.class_member),
};
export function useClassMembers(classId: string) { ... }
export function useAddClassMember(classId: string) { ... }
export function useEndClassMember(classId: string) { ... }
export function useUpdateClassMember(classId: string) { ... }
```
(Tương tự cho `class-teacher-assignments.ts` với `assign`/`end`.)

- [ ] **Step 1:** Test trước (MSW): roster trả đúng field; `add` 201 rồi invalidate; `add` 409 `ALREADY_A_MEMBER` → `isApiError().is("ALREADY_A_MEMBER")` đúng; `end` 204 gửi đúng `end_reason` trong body DELETE; `update` 422 không có `details` (`VALIDATION_FAILED`, "valid_to phải sau valid_from.") → `error.details` là `undefined`, `error.message` đúng chuỗi. Tương tự `assign` 409 `ALREADY_ASSIGNED`, 422 `TEACHER_NOT_ACTIVE`.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Pass, typecheck (chú ý `apiClient.delete` nhận `options.body` — kiểm `lib/api-client.ts` đã hỗ trợ, không cần sửa).
- [ ] **Step 5:** Commit `feat: add class-member and teacher-assignment API hooks to user-management`.

---

### Task 7: AD-10 khung + AD-10a (Tổng quan)

**Files:**
- Create: `src/features/academic/ui/admin/class-detail-screen.tsx` (khung: header, banner trạng thái, dải tab, điều phối 5 tab)
- Create: `src/features/academic/ui/admin/class-overview-tab.tsx` (AD-10a: form sửa inline M2 + nút Lưu trữ)
- Create: `src/features/academic/ui/admin/class-archive-dialog.tsx` (M4)
- Create: `src/features/academic/ui/admin/class-schedule-placeholder-tab.tsx`, `class-sessions-placeholder-tab.tsx` (nội dung tĩnh cho AD-10d/AD-10e — quyết định #5 ở trên)
- Create tests tương ứng
- Modify: `src/features/academic/index.ts` — export `ClassDetailScreen`
- Create route: `src/app/(portal)/admin/classes/[classId]/page.tsx`

**Đọc design system trước:** `PiClass Quản trị.html` khu vực `cdv` (chi tiết lớp, dòng ~617/~2755 — Input "Môn học" disabled với hint "Môn học không đổi được sau khi tạo lớp.") và `d.tabs`/banner theo trạng thái (tương tự cấu trúc `TeacherDetailScreen` đã có — `teacher-detail-screen.tsx` là tham chiếu kết cấu gần nhất có sẵn trong code thật, ưu tiên bám cấu trúc file đó hơn là mockup JS thô).

**Hành vi khung (`class-detail-screen.tsx`):**
- `classQuery = useClass(classId)`; `membersQuery`, `teachersQuery` gọi ở Task 8/9 nhưng **đếm** (`members.length`, `teachers.length`) cần có ở đây để hiện số cạnh tên tab "Học sinh (n)"/"Giáo viên (n)" — gọi `useClassMembers(classId)`/`useClassTeachers(classId)` ngay tại `ClassDetailScreen` (không gọi lại trong từng tab) rồi truyền `data`/`isLoading` xuống qua props — tránh double-fetch, và cho phép AD-10c gọi `classQuery.refetch()` sau khi assign/end (truyền callback xuống qua prop `onClassMayChange`).
- Banner theo `status`: `preparing` → "Lớp chưa có giáo viên phụ trách nên chưa vận hành; phân công giáo viên ở tab Giáo viên" (có thể kèm link chuyển tab); `archived` → "Lớp đã lưu trữ, lịch sử vẫn xem được".
- Nút "Lưu trữ lớp" ở header (ẩn khi đã `archived`) mở `class-archive-dialog.tsx`.
- Đọc `?tab=` từ query string (Next `useSearchParams`) để set tab ban đầu — hỗ trợ điều hướng từ Task 5 (`?tab=teachers` sau khi tạo lớp). **Route vẫn mỏng (F1)**: việc đọc `searchParams` nằm trong Client Component `ClassDetailScreen` (dùng `useSearchParams` của `next/navigation`, không phải đọc ở `page.tsx`).
- 404: `classQuery.isError` với `error.status === 404` (hoặc đơn giản `isError` chung, theo đúng mẫu `teacher-detail-screen.tsx` không phân biệt 404 riêng — giữ nhất quán, không thêm case đặc biệt nếu slice mẫu chưa làm).

**AD-10a (`class-overview-tab.tsx`):**
- Hiện tên, nhãn, mô tả, môn (tên tra từ `useSubjects()`), trạng thái (badge). Môn hiện **chỉ-đọc** (Input `disabled` + hint "Môn học không đổi được sau khi tạo lớp.").
- Form sửa (`classOverviewFormSchema`): `name`, `label`, `description` — submit `PATCH /classes/{id}` (**không gửi `subject_id`/`status`**). Khi `classQuery.data.status === "archived"`, toàn form chỉ đọc (disable input, ẩn nút Lưu).
- `class-archive-dialog.tsx`: M4, nội dung tĩnh đúng `admin.md` ("Lớp sẽ không còn hoạt động...không khôi phục được qua giao diện"), không có ô lý do, gửi `PATCH { status: "archived" }`.
- Lỗi 422 `name` rỗng → field; `status` chỉ nhận `archived` (không cần test FE tự gửi sai, chỉ đảm bảo type không cho phép).

**Placeholder Lịch/Buổi học:** card tĩnh "Lịch và buổi học của lớp sẽ có ở lượt làm tiếp theo (nhóm Lịch học)." — không gọi API nào, không đếm badge.

- [ ] **Step 1:** Test trước cho `class-detail-screen.test.tsx` (tải/lỗi/404 chung/hiện 3 tab đúng số đếm/đọc `?tab=` ban đầu/banner theo từng status) và `class-overview-tab.test.tsx` (hiện form/submit PATCH đúng payload không có subject_id/lưu trữ gọi đúng endpoint/form readonly khi archived).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement toàn bộ file trên.
- [ ] **Step 4:** Nối route + `index.ts`.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-10 class detail frame with the overview tab`.

---

### Task 8: AD-10b — Tab Học sinh

**Files:**
- Create: `src/features/user-management/ui/admin/class-members-tab.tsx`
- Create: `src/features/user-management/ui/admin/class-member-add-button.tsx` (nút disabled + Tooltip, theo quyết định #7)
- Create: `src/features/user-management/ui/admin/class-member-end-dialog.tsx` (M4, lý do bắt buộc)
- Create: `src/features/user-management/ui/admin/class-member-edit-period-dialog.tsx` (M4, `valid_from`/`valid_to` + lý do bắt buộc, hiện giá trị cũ → mới)
- Create tests tương ứng
- Modify: `src/features/user-management/index.ts` — export `ClassMembersTab`

**Đọc design system trước:** không có khối riêng rõ trong mockup cho roster lớp (phần "Học sinh" của AD-10 nằm lẫn trong file JS lớn, biến `clsLog`/`AUD(...)` chỉ là audit mẫu) — dùng cấu trúc bảng tương tự `teacher-classes-tab.tsx` (đã có trong code thật, ưu tiên tham chiếu) và mẫu M1/M4 chung đã mô tả ở `admin.md`. Nút/dialog style lấy từ `components/ui/button.tsx`, `dialog.tsx` đã port đúng.

**Hành vi:**
- Props nhận `classId`, `classStatus` (để hiện banner khi `archived`, theo quyết định #6), `members: ClassMemberRosterItem[]`, `isLoading` (nhận từ `ClassDetailScreen`, không tự gọi `useClassMembers` lần hai).
- Bảng: Họ tên (link tới `/admin/students/{id}` — **chưa có route đó ở nhóm này**; theo quyết định bổ sung: nếu route chưa tồn tại (nhóm 3 làm sau), hiện text thường không phải link, ghi comment TODO trỏ AD-05 — không tạo link chết).
- Cột Ngày vào lớp / Ngày hiệu lực đến qua `formatDate` (F8); `valid_to` ở tương lai → nhãn "Sẽ rời lớp ngày …" cạnh giá trị.
- Ô tìm tên (client-side, filter mảng đã tải — roster không phân trang).
- Mục "Đã rời lớp" (đã `ended`) — API hiện chỉ trả thành viên `active` (GM-3 ghi nhận ở `admin.md` là 🟡 chưa có) → **không build mục thu gọn này ở lượt này** (không có dữ liệu để hiện), bỏ qua đúng theo đánh dấu 🟡 của spec.
- Nút "Thêm học sinh": `disabled`, bọc `Tooltip` nội dung "Cần màn tìm học sinh theo tên (sắp có ở lượt sau)".
- Menu "…" mỗi dòng: "Kết thúc tư cách thành viên" → `ClassMemberEndDialog` (ô lý do bắt buộc, gọi `useEndClassMember(classId).mutate({studentId, end_reason})`); "Sửa thời hạn" → `ClassMemberEditPeriodDialog` (hiện giá trị cũ, input `valid_from`/`valid_to` kiểu `datetime-local` hoặc theo Input date đã có trong design system — kiểm `components/ui/*` có sẵn input ngày chưa, nếu chưa có thì dùng `<input type="datetime-local">` thuần bọc trong `Input`-style wrapper tối giản, ghi rõ nợ kỹ thuật nếu phải tự chế).
- Lỗi: `ALREADY_A_MEMBER` không áp dụng ở tab này (chỉ áp dụng khi "Thêm", đang disabled) — nhưng vẫn viết nhánh xử lý trong `class-members-tab.tsx`/hook phòng khi nối nút ở lượt sau, không cần UI riêng bây giờ. `VALIDATION_FAILED` không `details` ("valid_to phải sau valid_from.") → hiện dưới ô "Đến ngày" trong `ClassMemberEditPeriodDialog`. 422 `reason`/`end_reason` bắt buộc → "Vui lòng nhập lý do." dưới ô lý do. 404 khi sửa/kết thúc thành viên không còn hiệu lực → banner lỗi chung trong dialog + đóng dialog sau khi người dùng bấm "Đã hiểu" (không có field nào để gán).
- Trạng thái: tải (Skeleton), rỗng ("Lớp chưa có học sinh" — nút Thêm vẫn disabled theo quyết định #7, không có CTA khác), lỗi, đang lưu từng dòng (disable menu dòng đang xử lý).

- [ ] **Step 1:** Test trước: hiện roster/rỗng/lỗi/tìm tên lọc client/nút Thêm disabled có tooltip đúng nội dung/kết thúc tư cách thành công gọi đúng DELETE + body/lỗi 422 reason bắt buộc/sửa thời hạn thành công gọi đúng PATCH + hiện giá trị cũ→mới/lỗi `VALIDATION_FAILED` không details hiện đúng dưới ô Đến ngày.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối vào `ClassDetailScreen` (Task 7) + `index.ts`.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-10b class members tab`.

---

### Task 9: AD-10c — Tab Giáo viên

**Files:**
- Create: `src/features/user-management/ui/admin/class-teachers-tab.tsx`
- Create: `src/features/user-management/ui/admin/class-teacher-assign-dialog.tsx`
- Create: `src/features/user-management/ui/admin/class-teacher-end-dialog.tsx`
- Create tests tương ứng
- Modify: `src/features/user-management/index.ts` — export `ClassTeachersTab`

**Hành vi:**
- Props: `classId`, `classStatus`, `teachers: ClassTeacherRosterItem[]`, `isLoading`, `onClassMayChange: () => void` (gọi `classQuery.refetch()` ở `ClassDetailScreen` sau khi assign/end thành công — theo Review Focus).
- Bảng: Họ tên (text thường, không link — route `/admin/teachers/{id}` **đã có** từ nhóm 1, nên **có thể link thật**, khác AD-10b), "Phụ trách từ" (`formatDate`). Không có "giáo viên chính".
- Nút "Phân công giáo viên" mở `ClassTeacherAssignDialog`: gọi `useTeachers({per_page:100, name: debouncedSearch})` (**không lọc `status`** — quyết định #6 bên dưới, để còn thấy và làm mờ giáo viên inactive), loại client-side các `teacher_id` đã có trong `teachers` hiện tại (đang active ở lớp này). Giáo viên hồ sơ `inactive` hiện mờ + `disabled` trong danh sách chọn kèm chú thích "Đã ngừng hoạt động" (đúng mô tả "các em Ngừng hoạt động bị làm mờ... không cho chọn"); chia nhóm hiển thị "Đang hoạt động" lên trên, "Ngừng hoạt động" (disabled) ở dưới.
- Submit → `useAssignTeacher(classId).mutate({teacher_id})`; `onSuccess`: đóng dialog, gọi `onClassMayChange()`, hiện thông báo chung "Đã phân công giáo viên" (không so sánh trạng thái cũ/mới để quyết định câu thông báo — để banner ở khung AD-10 tự phản ánh đổi trạng thái qua `onClassMayChange`, tránh so sánh phức tạp ở tầng tab).
- Lỗi trong dialog: `TEACHER_NOT_ACTIVE` (422) và `ALREADY_ASSIGNED` (409) → hiện đúng `error.message` **gốc của BE**, không viết lại theo câu gợi ý "thân thiện hơn" trong `admin.md` (F7 yêu cầu hiện nguyên văn theo field, không tự dịch lại câu chữ — áp dụng nhất quán với các màn đã làm ở AD-07).
- Menu "…" mỗi dòng: "Kết thúc phân công" → `ClassTeacherEndDialog` (M4, không có ô lý do vì BE không nhận) — cảnh báo tĩnh theo `admin.md`: "Giáo viên không còn thấy và quản lý lớp này; lịch sử giữ nguyên." + nếu `teachers.length === 1` (đây là giáo viên cuối) thêm dòng cảnh báo "Lớp sẽ trở về Đang chuẩn bị." Submit → `useEndTeacherAssignment(classId).mutate({teacherId})`, `onSuccess` gọi `onClassMayChange()`.
- Trạng thái: tải, rỗng ("Chưa có giáo viên phụ trách, lớp chưa vận hành" + nút Phân công), lỗi, đang lưu.

- [ ] **Step 1:** Test trước: hiện roster/rỗng/lỗi/mở dialog phân công hiện đúng danh sách (teacher inactive mờ+disabled, teacher đã phân công bị loại)/phân công thành công gọi `onClassMayChange`/lỗi `TEACHER_NOT_ACTIVE` và `ALREADY_ASSIGNED` hiện nguyên message BE/kết thúc phân công gọi đúng DELETE, cảnh báo "giáo viên cuối" chỉ hiện khi `teachers.length===1`.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối vào `ClassDetailScreen` (Task 7, truyền `onClassMayChange={() => classQuery.refetch()}`) + `index.ts`.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-10c class teachers tab`.

---

### Task 10: Test thủ công qua `piclass-infra` thật (không chỉ unit test)

Không viết code ở task này — chạy tay qua trình duyệt thật (hoặc công cụ trình duyệt nếu phiên có), theo đúng bước 5 `working-flow.md`. Trước khi chạy: `docker compose build web` nếu Dockerfile `web` đổi, `docker compose up -d` cả `web`/`api`/`nginx` trong `piclass-infra/local`.

- [ ] Tạo môn mới ở AD-08, kiểm tra xuất hiện ngay trong dropdown "Môn" ở hộp thoại tạo lớp AD-09 (không cần tải lại trang nếu đã tạo trước khi mở dialog — nếu tải lại mới thấy, chấp nhận vì là query riêng, ghi chú lại hành vi thật).
- [ ] Ngừng sử dụng một môn ở AD-08 → thử tạo lớp mới chọn chính môn đó (phải không xuất hiện trong dropdown; nếu ép chọn qua DevTools để test 422 `SUBJECT_INACTIVE`, xác nhận message hiện đúng dưới ô Select).
- [ ] Tạo lớp mới ở AD-09 → xác nhận điều hướng sang AD-10 tab Giáo viên, trạng thái lớp là "Đang chuẩn bị".
- [ ] Phân công một giáo viên đang hoạt động → xác nhận banner/chip đổi "Đang hoạt động" **không cần tải lại trang** (refetch tự động).
- [ ] Kết thúc phân công giáo viên duy nhất → xác nhận lớp về "Đang chuẩn bị" tự động.
- [ ] Thử phân công lại một giáo viên đã `inactive` (đổi trạng thái giáo viên đó ở AD-07a trước) → xác nhận bị mờ/disabled trong danh sách chọn, không gọi được API.
- [ ] Mở hai tab trình duyệt, cùng phân công một giáo viên vào cùng lớp gần như đồng thời → xác nhận tab chậm hơn nhận lỗi `ALREADY_ASSIGNED` đúng thông điệp (test cuộc đua giữa hai Admin theo đúng `admin.md`).
- [ ] Ở AD-10b: xác nhận nút "Thêm học sinh" bị khóa + tooltip hiện đúng; xem roster mẫu có sẵn (nếu seed có dữ liệu) hoạt động, kết thúc tư cách một học sinh và sửa thời hạn hồi tố một học sinh khác, kiểm tra cả hai qua `GET /audit-logs` (nếu có màn Nhật ký) thấy đúng action `class_member.end`/`class_member.retroactive_update`.
- [ ] Lưu trữ một lớp ở AD-10a → xác nhận form Tổng quan chuyển chỉ đọc, banner "Đã lưu trữ" hiện, lớp biến mất khỏi danh sách AD-09 mặc định (chỉ hiện lại khi bật switch "Hiện lớp đã lưu trữ").
- [ ] Kiểm tra trên màn hình nhỏ (máy tính bảng giả lập DevTools) ba màn đều dùng được (không bị tràn ngang).

## Tóm tắt các quyết định tự chọn cần user xác nhận

1. **Input hỗ trợ `multiline`**: phải port thêm (chưa có), dùng cho trường "Mô tả" ở Môn học/Lớp học/Tổng quan lớp.
2. **Dropdown chọn giáo viên AD-10c**: gọi `GET /teachers?per_page=100` (không lọc `status` để còn thấy và làm mờ giáo viên `inactive`), có ô tìm tên — không build "chọn nhiều trang".
3. **AD-10a "số buổi sắp tới" và "Lịch sử thay đổi"**: bỏ hẳn ở lượt này (API chi tiết lớp không trả `next_session_at`; chưa có API lọc audit theo lớp — 🔵 GM-7).
4. **Tab Lịch/Buổi học (AD-10d/AD-10e)**: hiện tab nhưng nội dung là thông báo tĩnh "sẽ có ở lượt sau", không gọi API nào, không đếm số cạnh tên tab.
5. **Lớp đã lưu trữ**: không tự chặn thêm học sinh/giáo viên ở FE (đúng F7, vì BE hiện không chặn) — chỉ thêm banner cảnh báo, không ẩn nút.
6. **Thông điệp lỗi 422/409 ở AD-10c** (`TEACHER_NOT_ACTIVE`, `ALREADY_ASSIGNED`): hiện nguyên văn `error.message` gốc của BE, không viết lại theo câu gợi ý "thân thiện hơn" trong `admin.md`, để nhất quán với rule F7 và các màn đã làm (AD-07).
7. **Tên học sinh ở AD-10b**: chưa có route `/admin/students/{id}` (thuộc nhóm 3) nên hiển thị text thường, không phải link, kèm TODO comment.

Toàn bộ các quyết định trên đều có thể đổi dễ dàng nếu user muốn khác — chưa có code nào được viết (đây là plan, chưa triển khai).

### Các file quan trọng nhất đã đọc để lập plan này (tham chiếu nhanh)
- `piclass-web/CLAUDE.md`
- `docs/product/screens/admin.md` (mục AD-08, AD-09, AD-10/10a/10b/10c)
- `piclass-web/src/features/user-management/` (toàn bộ, làm mẫu pattern)
- `piclass-api/app/Modules/Academic/` (Resources/Requests/Controllers/Queries cho Subject, Class)
- `piclass-api/app/Modules/UserManagement/` (ClassMember, TeacherClassAssignment: Resources/Requests/Controllers/Actions)
- `piclass-mockup/design-system/standalone/PiClass Quản trị.html`
- `docs/architecture/module-boundaries.md`, `docs/architecture/error-codes.md`
