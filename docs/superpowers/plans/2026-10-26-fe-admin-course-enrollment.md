# FE Admin — Khóa học và ghi danh (AD-12, AD-12a) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Admin "Khóa học" (Course) list + detail screen (AD-12) and the enrollment roster + enroll/end dialogs (AD-12a) in `piclass-web`, calling the real BE (`piclass-api`, module `Academic`).

**Architecture:** Mirror the existing Class (AD-09/AD-10) screens in `src/features/academic/` byte-for-byte in structure — `courses.ts` (api+hooks) alongside `classes.ts`, `CourseListScreen`/`CourseDetailScreen` alongside `ClassListScreen`/`ClassDetailScreen`, tab components, create/archive dialogs. The enroll dialog (two modes: by student / by class) is new; it reuses `useTeachers`/`useClasses` already exported and adds `useStudents` to `user-management`'s public surface (F5) for the student-search mode.

**Tech Stack:** Next.js 16 / React 19 / TypeScript, TanStack Query, React Hook Form + Zod, Tailwind 4 design tokens, Vitest + Testing Library + MSW.

**Spec:** `../../product/screens/admin.md` sections `AD-12` and `AD-12a` (lines ~460–494 as of 2026-10-26). Mockup: `../../../piclass-mockup/design-system/standalone/PiClass Quản trị.html`, sections marked `AD-12 DANH SÁCH KHÓA HỌC` (~L893–915 / L3015–3037, duplicate render), `AD-12 CHI TIẾT KHÓA HỌC + AD-12a GHI DANH` (~L917–954 / L3039–3076), dialogs "tạo khóa học" (~L1279–1288), "ghi danh vào khóa học" (~L1290–1307), and their JS state at ~L1682–1688 (`doEnroll`), ~L1905–1916 (`courseRows`/`enrCandidates`/`kd`/`enrRows`), ~L2098–2116 (prop wiring: `courseStatusTabs`, `courseOwnerOptions`, `enModeTabs`, `enClassOptions`). AD-13 is explicitly **out of scope** for this plan (see `mockup-plan.md` mục 6, decision 2026-10-26) — no task here builds it.

## Global Constraints

- Code convention: `piclass-web/CLAUDE.md` (F1–F8 rules, file layout, naming). Feature name must be `academic` (BE module name, `module-boundaries.md`).
- Contract source of truth: `piclass-api/storage/api-docs/openapi.json` (re-export via `docker compose exec api php artisan scramble:export` in `piclass-infra/local` if it looks stale), cross-checked against BE source where OpenAPI under-reports (see Task 1 note on `owner_teacher_id`).
- Mockup fidelity: port exact copy, layout, spacing, component variant from the mockup HTML — no rounding to "nearest" token, no paraphrasing Vietnamese strings (`piclass-web/CLAUDE.md` mục "Port phải giống hệt mockup").
- Error display: always render the BE's own `error.message` verbatim; use `error.is("CODE")`/`error.code` only to pick *where* to show it, never to substitute custom text (confirmed existing pattern in `class-create-dialog.tsx`). Do not copy the paraphrased error strings written in `admin.md` as literal UI text.
- `DELETE /courses/{course}/enrollments/{studentId}` takes `end_reason` — send via `{ body: { end_reason } }` on `apiClient.delete`, matching the existing `useEndClassMember` pattern (`class-members.ts:19`), not a query string.
- No pagination on `/courses`, `/courses/{id}/enrollments` (plain arrays) — do not add `Paginated<T>` wrapping for these.
- F5: cross-feature imports only via `@/features/<x>` index; this plan adds `useStudents` to `user-management/index.ts` for that reason (Task 4).
- No new global state, no Redux/Zustand; local screen state via `useState`, server state via TanStack Query (existing conventions).

## Review Focus

- **Course update request omits `subject_id` validation edge case:** `UpdateCourseRequest` accepts `subject_id` (nullable uuid, must exist) — a save with an invalid/removed subject must show the 422 `details.subject_id` message inline, not silently fail. Covered in Task 3 (overview tab save-error test).
- **Enroll dialog: choosing neither or both modes' inputs.** BE rejects via `required_without`/`prohibits` on `student_id`/`class_id` — but the FE dialog should never let both be set given the Tabs-switch clears the other field; still test that submitting with nothing picked in the active tab shows "Hãy chọn một học sinh hoặc một lớp." before calling the API (client-side guard), and that a genuine 422 from BE also renders. Covered in Task 6.
- **`created_count: 0` is success, not an error.** The dialog must render "Tất cả đã được ghi danh từ trước" (not an error banner) when `created_count === 0`. Covered in Task 6.
- **Archived course: enrolling blocked but ending enrollment still allowed.** `COURSE_ARCHIVED` only fires on enroll; the "Kết thúc ghi danh" button and dialog must stay enabled on an archived course. Covered in Task 5 (roster) and Task 6 (dialog disables "Ghi danh" button state but end-enrollment path untouched).
- **Owner select is Admin-only and immutable after creation.** `CreateCourseRequest` only accepts `owner_teacher_id` when the active role is Admin (Teacher's own courses silently default to themselves server-side); the FE create dialog must send the field only when a value is chosen (omit vs. `null` — confirm against Task 1's manual check), and the overview tab must render the owner as a disabled/read-only `Input`, never an editable `Select`. Covered in Task 1 (types/schema) and Task 3 (overview tab, mirroring `class-overview-tab.tsx`'s disabled subject input).

---

## File Structure

```
src/features/academic/
├── api/
│   ├── courses.ts                 NEW — courseApi + hooks (list/get/create/update)
│   ├── course-enrollments.ts      NEW — enrollment list/enroll/end hooks
│   ├── types.ts                   MODIFY — add Course*, CourseEnrollment* types
│   ├── schemas.ts                 MODIFY — add course create/overview/enroll form schemas
│   └── keys.ts                    MODIFY — add courses()/course(id)/courseEnrollments(id) keys
├── ui/admin/
│   ├── course-list-screen.tsx     NEW — AD-12 list
│   ├── course-list-table.tsx      NEW — AD-12 list rows (table, not grid — mockup uses rows)
│   ├── course-badges.ts           NEW — status Badge tone/label map (mirror class-badges.ts)
│   ├── course-create-dialog.tsx   NEW — AD-12 "Tạo khóa học" dialog
│   ├── course-detail-screen.tsx   NEW — AD-12 hero + tabs shell
│   ├── course-overview-tab.tsx    NEW — AD-12 "Tổng quan" tab (edit form)
│   ├── course-archive-dialog.tsx  NEW — AD-12 "Lưu trữ khóa" confirm
│   ├── course-enrollments-tab.tsx NEW — AD-12a roster + "Hiện cả em đã kết thúc" switch
│   ├── course-enroll-dialog.tsx   NEW — AD-12a two-mode enroll dialog
│   └── course-end-enrollment-dialog.tsx NEW — AD-12a end-enrollment confirm (reason required)
└── index.ts                        MODIFY — export CourseListScreen (route needs it)

src/features/user-management/
└── index.ts                        MODIFY — export useStudents (Task 4)

src/app/(portal)/admin/
└── courses/
    ├── page.tsx                    NEW — renders CourseListScreen
    └── [id]/page.tsx               NEW — renders CourseDetailScreen

src/config/nav.ts                   MODIFY — add "Khóa học" menu item for Admin
```

---

### Task 1: Course types, schemas, query keys, and `coursesApi` + hooks

**Files:**
- Modify: `src/features/academic/api/types.ts` (append Course section)
- Modify: `src/features/academic/api/keys.ts`
- Modify: `src/features/academic/api/schemas.ts` (append course schemas)
- Create: `src/features/academic/api/courses.ts`
- Test: `src/features/academic/api/courses.test.tsx`

**Interfaces:**
- Consumes: `apiClient` (`@/lib/api-client`), `academicKeys` pattern already in `keys.ts`.
- Produces: `CourseStatus`, `CourseListItem`, `CourseDetail`, `CreateCourseInput`, `CreatedCourse`, `UpdateCourseInput`, `UpdatedCourse` (types); `coursesApi`, `useCourses()`, `useCourse(id)`, `useCreateCourse()`, `useUpdateCourse(id)` (hooks) — same shape as the `classes.ts` equivalents; `courseOverviewFormSchema`/`CourseOverviewFormValues`, `courseCreateFormSchema`/`CourseCreateFormValues`/`COURSE_CREATE_FORM_DEFAULTS` (schemas) for Tasks 1/3.

- [ ] **Step 1: Read `academic.ts` keys and add course keys**

Open `src/features/academic/api/keys.ts`, add alongside the existing `classes()`/`class(id)` entries:
```ts
courses: () => [...academicKeys.all, "courses"] as const,
course: (id: string) => [...academicKeys.courses(), id] as const,
```
(Match whatever base-key structure the file already uses — read it first, don't guess the exact array shape.)

- [ ] **Step 2: Write failing test for `coursesApi.list` and `useCourses`**

```tsx
// courses.test.tsx — mirror classes.test.tsx's structure exactly (MSW handlers, QueryClient wrapper)
test("useCourses trả danh sách khóa học từ GET /courses", async () => { /* mock { data: [...] }, assert hook data */ });
test("useCourse trả chi tiết một khóa từ GET /courses/{id}", async () => { /* mock { course: {...} } */ });
test("useCreateCourse gọi POST /courses và invalidate danh sách", async () => { /* ... */ });
test("useUpdateCourse gọi PATCH /courses/{id} và invalidate danh sách + chi tiết", async () => { /* ... */ });
```
Use exact field values from the `CourseListResource`/`CourseResource` schemas read from `openapi.json` this session: `id, name, description, owner_teacher_id, status` (list) and additionally `subject_id` (detail).

- [ ] **Step 3: Run tests, verify fail** (`useCourses` etc. not defined)

Run: `pnpm vitest run src/features/academic/api/courses.test.tsx`
Expected: FAIL — module has no exports yet.

- [ ] **Step 4: Add Course types to `types.ts`**

Append after the Class section, matching the file's existing docblock style:
```ts
export type CourseStatus = "active" | "archived";

export interface CourseListItem {
  id: string; name: string; description: string | null;
  owner_teacher_id: string | null; status: CourseStatus;
}

export interface CourseDetail extends CourseListItem { subject_id: string | null; }

export interface CreateCourseInput {
  name: string; description?: string | null; subject_id?: string | null;
  /** Chỉ Admin được gửi; Teacher gửi thì bị BE bỏ qua (không validate) — FE chỉ hiện ô này khi active_role là admin. */
  owner_teacher_id?: string | null;
}

export interface CreatedCourse { id: string; name: string; owner_teacher_id: string | null; status: CourseStatus; }

export interface UpdateCourseInput {
  name?: string; description?: string | null; subject_id?: string | null; status?: "archived";
}

export interface UpdatedCourse { id: string; name: string; description: string | null; status: CourseStatus; }
```

- [ ] **Step 5: Add course form schemas to `schemas.ts`**

Mirror `classCreateFormSchema`/`classOverviewFormSchema` naming and empty-string-to-null handling:
```ts
export const COURSE_CREATE_FORM_DEFAULTS: CourseCreateFormValues = { name: "", description: "", subject_id: "", owner_teacher_id: "" };
export const courseCreateFormSchema = z.object({
  name: z.string().min(1, "Vui lòng nhập tên khóa học."),
  description: z.string(),
  subject_id: z.string(),
  owner_teacher_id: z.string(),
});
export type CourseCreateFormValues = z.infer<typeof courseCreateFormSchema>;

export const courseOverviewFormSchema = z.object({ name: z.string().min(1, "Vui lòng nhập tên khóa học."), description: z.string(), subject_id: z.string() });
export type CourseOverviewFormValues = z.infer<typeof courseOverviewFormSchema>;
```
(Read the file first — if `classCreateFormSchema` already imports `z` a particular way or uses a shared helper for "required string", follow that, don't introduce a second convention.)

- [ ] **Step 6: Implement `courses.ts`**

```ts
export const coursesApi = {
  list: () => apiClient.get<CourseListItem[]>("/courses"),
  get: (id: string) => apiClient.get<{ course: CourseDetail }>(`/courses/${id}`).then((r) => r.course),
  create: (input: CreateCourseInput) => apiClient.post<{ course: CreatedCourse }>("/courses", input).then((r) => r.course),
  update: (id: string, input: UpdateCourseInput) => apiClient.patch<{ course: UpdatedCourse }>(`/courses/${id}`, input).then((r) => r.course),
};
export function useCourses() { /* useQuery(academicKeys.courses(), coursesApi.list) */ }
export function useCourse(id: string) { /* useQuery(academicKeys.course(id), () => coursesApi.get(id)) */ }
export function useCreateCourse() { /* useMutation + invalidate courses() */ }
export function useUpdateCourse(id: string) { /* useMutation + invalidate courses() and course(id) */ }
```
Copy `classes.ts`'s exact invalidation pattern (`useQueryClient`, `onSuccess`).

- [ ] **Step 7: Run tests, verify pass**

Run: `pnpm vitest run src/features/academic/api/courses.test.tsx`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add src/features/academic/api/courses.ts src/features/academic/api/courses.test.tsx src/features/academic/api/types.ts src/features/academic/api/keys.ts src/features/academic/api/schemas.ts
git commit -m "feat(academic): add Course data layer (types, keys, schemas, hooks)"
```

---

### Task 2: Course list screen (AD-12 danh sách)

**Files:**
- Create: `src/features/academic/ui/admin/course-badges.ts`
- Create: `src/features/academic/ui/admin/course-list-table.tsx`
- Create: `src/features/academic/ui/admin/course-list-screen.tsx`
- Test: `src/features/academic/ui/admin/course-list-screen.test.tsx`
- Modify: `src/features/academic/index.ts` (export `CourseListScreen`)
- Create: `src/app/(portal)/admin/courses/page.tsx`
- Modify: `src/config/nav.ts`

**Interfaces:**
- Consumes: `useCourses()`, `useTeachers` (`@/features/user-management`, already exported) for the owner filter dropdown, `CourseListItem` (Task 1).
- Produces: `CourseListScreen` component (no props — route-level), `CourseListTable` (`{ courses: CourseListItem[]; teacherNameById: (id: string) => string }`), `COURSE_STATUS_BADGE: Record<CourseStatus, { tone: BadgeTone; label: string }>`.

- [ ] **Step 1: Write failing test for list screen states**

Mockup source — `PiClass Quản trị.html` L895–915 and JS L1905–1906, L2098–2103 (`courseRows`, `courseSummary`, `courseStatusTabs`, `courseOwnerOptions`, `courseHasFilter`). Test file (MSW + render), covering:
```tsx
test("hiện tiêu đề Khóa học và nút Tạo khóa", ...);
test("hiện tóm tắt '{n} khóa học · {m} đang mở'", ...); // courseSummary format, L2103: `courseAll.length+' khóa học · '+active+' đang mở'`
test("lọc theo trạng thái và chủ sở hữu, Xóa lọc chỉ hiện khi có lọc", ...);
test("rỗng hiện 'Chưa có khóa học' + nút 'Tạo khóa đầu tiên' khi chưa lọc gì", ...); // L900
test("lỗi tải hiện 'Không tải được danh sách khóa học' + Thử lại", ...); // L899
test("dòng hiện tên, mô tả, chủ sở hữu hoặc 'Dùng chung', badge trạng thái, số học sinh", ...); // L904-910
```
Exact copy strings to assert (do not paraphrase): "Khóa học" (h1), "Tạo khóa" (button), "Chưa có khóa học", "Tạo khóa đầu tiên", "Không tải được danh sách khóa học", "Thử lại", "Dùng chung" (owner null), "Xóa lọc".

- [ ] **Step 2: Run test, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-list-screen.test.tsx`
Expected: FAIL — `CourseListScreen` not defined.

- [ ] **Step 3: Implement `course-badges.ts`**

Mirror `class-badges.ts`'s `CLASS_STATUS_BADGE` shape. From mockup L1905 (`statusLabel:k.status==='archived'?'Đã lưu trữ':'Đang mở'`, `statusTone:... 'neutral':'green'`):
```ts
export const COURSE_STATUS_BADGE: Record<CourseStatus, { tone: BadgeTone; label: string }> = {
  active: { tone: "green", label: "Đang mở" },
  archived: { tone: "neutral", label: "Đã lưu trữ" },
};
```

- [ ] **Step 4: Implement `course-list-table.tsx`**

Table-row layout per mockup L904 header row (`Khóa học | Chủ sở hữu | Trạng thái | Học sinh`) and L905–910 row content — follow `student-list-table.tsx`'s `grid grid-cols-[...]` + `Link`-row pattern (but `/courses` has no pagination, so no `Paginated<T>`/page props — just a plain array prop and no footer pager). Columns: name (+ `description` truncated below name, like `student-list-table.tsx`'s email-under-name pattern), owner name (via `teacherNameById(owner_teacher_id)` or "Dùng chung" when null — column hidden below `md` per mockup's `hide-md`), status badge, student count ("{n} học sinh", hidden below `md`), chevron. Row click (whole row is a `Link` to `/admin/courses/{id}`, matching mockup's `onClick:{{k.open}}` on the full row, not a sub-button). `student_count` is **not** in `CourseListResource` — mockup computes it client-side from active enrollments it already has loaded (`this.enrollments(k.id).filter(e=>!e.endedOn).length`); this FE has no such local cache. **Decision for this task:** omit the student-count column rather than firing N calls to `GET /courses/{id}/enrollments` from the list (would be an N+1 against a non-list-shaped endpoint); note this one intentional mockup deviation in the PR description, matching the project's existing pattern of recording deliberate deviations in `admin.md`/`mockup-plan.md` rather than silently dropping it. Do not add a BE field for this without asking — flag it in `api-gaps.md` instead if it turns out to matter.

- [ ] **Step 5: Implement `course-list-screen.tsx`**

Mirror `class-list-screen.tsx` structure: `useState` for `name`/`status`/`owner`; `useCourses()` + `useTeachers({ status: "active", per_page: 100 })` for the owner filter options (`courseOwnerOptions` mockup L2101: `[{value:'all',label:'Mọi chủ sở hữu'},{value:'shared',label:'Dùng chung'}]` + active teachers). Status tabs: `[{value:"all",label:"Tất cả"},{value:"active",label:"Đang mở"},{value:"archived",label:"Đã lưu trữ"}]` (L2100). No `Switch`-for-archived like classes have (mockup has no such switch for courses — the status Tabs already include "Đã lưu trữ"). `hasFilter = status !== "all" || owner !== "all"` (no name search box in the mockup's course list — confirm by re-reading L895–897: there is no search `Input` there, only Tabs + owner Select + clear button. **Do not add a search box that isn't in the mockup.**). Loading: `Card` with skeleton rows (L898, 4 rows) — reuse the loading/error/empty `Card` block style from `class-list-screen.tsx` (adapted to rows, not grid-skeletons).

- [ ] **Step 6: Run test, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-list-screen.test.tsx`
Expected: PASS.

- [ ] **Step 7: Wire up route, nav, and feature export**

`src/app/(portal)/admin/courses/page.tsx`: thin wrapper rendering `<CourseListScreen />` (F1 — no logic). Add `CourseListScreen` to `src/features/academic/index.ts` exports. Add a "Khóa học" nav item for Admin in `src/config/nav.ts` — read the file first to match its existing item shape (icon, href, label) exactly as other Admin items do.

- [ ] **Step 8: Commit**

```bash
git add src/features/academic/ui/admin/course-badges.ts src/features/academic/ui/admin/course-list-table.tsx src/features/academic/ui/admin/course-list-screen.tsx src/features/academic/ui/admin/course-list-screen.test.tsx src/features/academic/index.ts src/app/\(portal\)/admin/courses/page.tsx src/config/nav.ts
git commit -m "feat(academic): add Course list screen (AD-12)"
```

---

### Task 3: Course create dialog + detail screen shell + overview tab (AD-12)

**Files:**
- Create: `src/features/academic/ui/admin/course-create-dialog.tsx`
- Create: `src/features/academic/ui/admin/course-overview-tab.tsx`
- Create: `src/features/academic/ui/admin/course-archive-dialog.tsx`
- Create: `src/features/academic/ui/admin/course-detail-screen.tsx`
- Test: `src/features/academic/ui/admin/course-overview-tab.test.tsx`
- Test: `src/features/academic/ui/admin/course-detail-screen.test.tsx`
- Create: `src/app/(portal)/admin/courses/[id]/page.tsx`

**Interfaces:**
- Consumes: `useCourse(id)`, `useCreateCourse()`, `useUpdateCourse(id)` (Task 1); `useSubjects()`, `useTeachers` (existing); `course-enrollments-tab.tsx`'s `CourseEnrollmentsTab` (Task 5 — this task can stub it as a placeholder `<div>` and Task 5 fills it in, OR do Task 5 first; this plan assumes Task 5 runs after Task 3 and Task 3's detail screen imports it directly since Task 5 is next in the same feature slice).
- Produces: `CourseCreateDialog({ open, onClose })`, `CourseOverviewTab({ courseDetail })`, `CourseArchiveDialog({ open, courseId, onClose })`, `CourseDetailScreen({ courseId })`.

- [ ] **Step 1: Write failing test for overview tab (edit form)**

Mockup L934–938 (fields: Tên khóa học, Mô tả, Môn học (tùy chọn), Chủ sở hữu disabled with hint "Chủ sở hữu không đổi được sau khi tạo khóa."; save button "Lưu thay đổi" only when `kd.canWrite` — always true for Admin per `CoursePolicy::manage`). Test cases:
```tsx
test("hiện form với giá trị khóa học hiện tại", ...);
test("lưu gọi PATCH với name/description/subject_id", ...);
test("lỗi 422 subject_id hiện dưới ô Môn học", ...);
test("ô Chủ sở hữu luôn disabled, hint 'Chủ sở hữu không đổi được sau khi tạo khóa.'", ...);
test("khóa đã lưu trữ: ẩn nút Lưu thay đổi, các ô thành disabled", ...); // mirrors class-overview-tab's isArchived branch
```

- [ ] **Step 2: Run test, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-overview-tab.test.tsx`
Expected: FAIL.

- [ ] **Step 3: Implement `course-overview-tab.tsx`**

Copy `class-overview-tab.tsx` almost verbatim: swap `useUpdateClass`→`useUpdateCourse`, `classOverviewFormSchema`→`courseOverviewFormSchema`, `ClassDetail`→`CourseDetail`. Fields: `name` (required), `description` (multiline), `subject_id` (`Select`, options from `useSubjects()`, value may be `""` when null — map to `null` on submit, not `""`), owner (`Input` disabled, `value={ownerName}` computed from `useTeachers`/a lookup, `hint="Chủ sở hữu không đổi được sau khi tạo khóa."`). `isArchived = courseDetail.status === "archived"`.

- [ ] **Step 4: Run test, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-overview-tab.test.tsx`
Expected: PASS.

- [ ] **Step 5: Implement `course-archive-dialog.tsx`**

Copy `class-archive-dialog.tsx` verbatim, swapping Class→Course and the body text to mockup's course-specific wording. Mockup has no dedicated standalone archive-dialog text block for courses visible in the grepped dialogs — **re-read mockup around L928 (`askArchiveCourse`) and its handler in the JS section (grep `askArchiveCourse` for the confirm copy) before writing the dialog body text**; do not invent wording. If the mockup only wires the button without a distinct confirm-dialog copy block, use the same two-sentence structure as `class-archive-dialog.tsx` adapted to "Lưu trữ khóa?" / enrollment+ bài đánh giá history phrasing consistent with `admin.md`'s explanation ("Không ghi danh thêm được nữa; xem lịch sử và kết thúc ghi danh vẫn được").

- [ ] **Step 6: Implement `course-create-dialog.tsx`**

Mockup L1280–1287. Fields: Tên khóa học (required, placeholder "VD: Luyện thi vào 10 — Toán"), Mô tả (multiline, 2 rows), two-column row (Môn học tùy chọn / Chủ sở hữu — **both Selects only shown/enabled per role; this screen is Admin-only so always show both**), helper text "Để trống chủ sở hữu = khóa dùng chung (chỉ quản trị viên có lựa chọn này)." Buttons: "Hủy bỏ" (ghost) / "Tạo khóa" (primary, iconLeft "check"). On submit, map empty-string `subject_id`/`owner_teacher_id` to omit-from-payload or `null` — **verify against `CreateCourseRequest` rules read this session: `subject_id` nullable, `owner_teacher_id` nullable** — send `null` explicitly for both when unselected (do not omit, to match `class-create-dialog.tsx`'s explicit-null convention for optional selects). On success, route to the new course's detail page (mirror `class-create-dialog.tsx`'s `router.push`), tab param not needed here (no analogous "teachers" first-stop tab for courses).

- [ ] **Step 7: Write failing test for detail screen shell**

Mockup L925–932 (hero: icon "library", name, status badge, subject + owner line, hero actions "Ghi danh"/"Lưu trữ khóa"; stat tiles "Học sinh đang ghi danh"/"Bài đánh giá"; Tabs "Tổng quan"/"Học sinh"(labelled "Học sinh ghi danh" per `admin.md` — confirm exact tab label text against mockup `kd.tabs` definition before writing, don't assume it matches the hard-coded `"Tổng quan"`/`"Học sinh"`/`"Giáo viên"` labels from classes)/"Bài đánh giá"). Archived banner (L924): "Khóa đã lưu trữ: không ghi danh thêm được, vẫn xem lịch sử và kết thúc ghi danh được." Test cases:
```tsx
test("hiện hero với tên, badge trạng thái, môn, chủ sở hữu", ...);
test("khóa lưu trữ hiện banner cảnh báo và ẩn nút Ghi danh (giữ nút Lưu trữ khóa ẩn, không có nút mở lại)", ...);
test("chuyển tab Tổng quan/Học sinh ghi danh/Bài đánh giá", ...);
test("lỗi tải hiện 'Không tải được thông tin lớp' tương đương — đổi đúng text khóa học", ...); // reuse class-detail-screen's not-found/error copy pattern but re-check mockup has no distinct "not found"/"forbidden" text block at L921-922 that differs — it does (kdNotFound/kdForbidden at L921-922): "Không tìm thấy khóa học" / "Bạn không có quyền xem khóa học này", with "Về danh sách khóa học" button — use these exact strings, not the class-detail ones.
```

- [ ] **Step 8: Run test, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-detail-screen.test.tsx`
Expected: FAIL.

- [ ] **Step 9: Implement `course-detail-screen.tsx`**

Structure mirrors `class-detail-screen.tsx`: `useState` for `tab` (default "overview"), `useCourse(courseId)`, loading/not-found/forbidden states (L920–922 exact copy above), hero block (name, `COURSE_STATUS_BADGE`, subject+owner line "{subject} · Chủ sở hữu: {ownerText}"), hero actions (`canWrite` is always true for Admin per `CoursePolicy` read this session — still gate on `course.status !== "archived"` for the "Ghi danh" button per the archived-banner rule, keep "Lưu trữ khóa" visible whenever not archived), `StatTile`s (students count from the enrollments tab's data — pass down or refetch; "Bài đánh giá" stat: **no BE endpoint lists assessments by course for Admin per `admin.md`'s 🟡 flag** — render a static "—" or omit the tile rather than fabricating a count; note this as a known gap, do not call `GET /assessments` and filter client-side without confirming scope with the user first), Tabs, `CourseOverviewTab`/`CourseEnrollmentsTab` (Task 5)/a static "no assessments UI yet" message for the third tab (mockup shows a real list at L948–951 fed by `COURSE_ASSESS` mock data — **there is no live BE source for this in V1**; render the tab with the mockup's empty-state copy "Khóa chưa có bài đánh giá nào. Giáo viên tạo bài trong phần Bài đánh giá." permanently, since no admin endpoint exists to list a course's assessments — flag this as a deliberate, documented gap rather than leaving the tab broken).

- [ ] **Step 10: Run test, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-detail-screen.test.tsx`
Expected: PASS.

- [ ] **Step 11: Wire up dynamic route**

`src/app/(portal)/admin/courses/[id]/page.tsx`: thin wrapper, `params.id` → `<CourseDetailScreen courseId={id} />` (Next 16 async `params` — check how `src/app/(portal)/admin/classes/[id]/page.tsx` awaits it and copy that exact pattern).

- [ ] **Step 12: Commit**

```bash
git add src/features/academic/ui/admin/course-create-dialog.tsx src/features/academic/ui/admin/course-overview-tab.tsx src/features/academic/ui/admin/course-overview-tab.test.tsx src/features/academic/ui/admin/course-archive-dialog.tsx src/features/academic/ui/admin/course-detail-screen.tsx src/features/academic/ui/admin/course-detail-screen.test.tsx "src/app/(portal)/admin/courses/[id]/page.tsx"
git commit -m "feat(academic): add Course create dialog and detail screen shell (AD-12)"
```

---

### Task 4: Export `useStudents` from `user-management` (F5 surface for Task 6)

**Files:**
- Modify: `src/features/user-management/index.ts`

**Interfaces:**
- Produces: `useStudents` now importable as `import { useStudents } from "@/features/user-management"`.

- [ ] **Step 1: Add the export**

```ts
export { useStudents } from "./api/students";
```
Add alongside the existing `useTeachers`/`useUsers` export lines.

- [ ] **Step 2: Verify `architecture.test.ts` still passes (F5 — no new violation)**

Run: `pnpm vitest run src/architecture.test.ts`
Expected: PASS (adding an index export doesn't change import depth elsewhere).

- [ ] **Step 3: Commit**

```bash
git add src/features/user-management/index.ts
git commit -m "feat(user-management): export useStudents for cross-feature reuse"
```

---

### Task 5: Enrollment roster tab (AD-12a list + "Hiện cả em đã kết thúc")

**Files:**
- Create: `src/features/academic/api/course-enrollments.ts`
- Modify: `src/features/academic/api/types.ts` (append `CourseEnrollmentListItem`, `EnrollInCourseInput`, `EndCourseEnrollmentInput`)
- Modify: `src/features/academic/api/keys.ts` (append `courseEnrollments(courseId)`)
- Create: `src/features/academic/ui/admin/course-enrollments-tab.tsx`
- Create: `src/features/academic/ui/admin/course-end-enrollment-dialog.tsx`
- Test: `src/features/academic/api/course-enrollments.test.tsx`
- Test: `src/features/academic/ui/admin/course-enrollments-tab.test.tsx`
- Modify: `src/features/academic/ui/admin/course-detail-screen.tsx` (wire the tab in, replacing Task 3's placeholder)

**Interfaces:**
- Consumes: `apiClient`, `academicKeys` (Task 1).
- Produces: `useCourseEnrollments(courseId)`, `useEndCourseEnrollment(courseId)` hooks; `CourseEnrollmentsTab({ courseId, courseStatus, onEnrollClick })` (the enroll button inside this tab opens `CourseEnrollDialog`, built in Task 6 — this task renders the "Ghi danh" button but Task 6 supplies the dialog; wire a stub `onClick` placeholder `useState` toggle here that Task 6's component plugs into).

- [ ] **Step 1: Write failing test for `useCourseEnrollments`/`useEndCourseEnrollment`**

```tsx
test("useCourseEnrollments trả danh sách ghi danh kèm student_name", ...); // GET /courses/{id}/enrollments → {data:[{student_id,student_name,status,valid_from,valid_to}]}
test("useEndCourseEnrollment gọi DELETE với body end_reason và invalidate danh sách", ...);
```

- [ ] **Step 2: Run test, verify fail**

Run: `pnpm vitest run src/features/academic/api/course-enrollments.test.tsx`
Expected: FAIL.

- [ ] **Step 3: Add types**

```ts
export type CourseEnrollmentStatus = "active" | "ended";
export interface CourseEnrollmentListItem {
  student_id: string; student_name: string; status: CourseEnrollmentStatus;
  valid_from: string | null; valid_to: string | null;
}
export interface EnrollInCourseInput { student_id?: string; class_id?: string; }
export interface EnrollmentResult { created_count: number; }
export interface EndCourseEnrollmentInput { end_reason: string; }
```

- [ ] **Step 4: Implement `course-enrollments.ts`**

```ts
export const courseEnrollmentsApi = {
  list: (courseId: string) => apiClient.get<CourseEnrollmentListItem[]>(`/courses/${courseId}/enrollments`),
  enroll: (courseId: string, input: EnrollInCourseInput) =>
    apiClient.post<EnrollmentResult>(`/courses/${courseId}/enrollments`, input),
  end: (courseId: string, studentId: string, input: EndCourseEnrollmentInput) =>
    apiClient.delete<void>(`/courses/${courseId}/enrollments/${studentId}`, { body: input }),
};
export function useCourseEnrollments(courseId: string) { /* useQuery */ }
export function useEnrollInCourse(courseId: string) { /* useMutation, invalidate courseEnrollments(courseId) */ }
export function useEndCourseEnrollment(courseId: string) { /* useMutation({studentId, input}), invalidate courseEnrollments(courseId) */ }
```
Note: `EnrollmentResultResource` has `$wrap = null` (confirmed in BE source this session) — response is the bare `{created_count}` object, not wrapped in `{data:...}` or `{course:...}`; `apiClient.post` must not expect an envelope here (check `api-client.ts`'s unwrap logic handles a body with no `data` key by passing it through, same as the auth endpoints).

- [ ] **Step 5: Run test, verify pass**

Run: `pnpm vitest run src/features/academic/api/course-enrollments.test.tsx`
Expected: PASS.

- [ ] **Step 6: Write failing test for roster tab**

Mockup L940–946: Switch "Hiện cả em đã kết thúc ghi danh" (default off — only active rows shown, mirrors `enrShowEnded` default false); "Ghi danh" button (hidden when `kd.canWrite` false, N/A here since Admin always canWrite); archived-course inline note "Khóa học đã lưu trữ nên không ghi danh thêm được." (only the enroll *action* is blocked, not the tab); roster rows (Avatar, name, "Từ {valid_from} · {đang hiệu lực | đến {valid_to} ({end_reason})}", status Badge, "Kết thúc ghi danh" button when `canEnd` i.e. not already ended); empty state "Chưa có học sinh trong khóa" + "Ghi danh theo từng học sinh hoặc theo cả lớp." Test cases:
```tsx
test("mặc định chỉ hiện ghi danh đang hiệu lực, bật Switch thì hiện cả đã kết thúc", ...);
test("dòng hiện 'Từ {ngày} · đang hiệu lực' hoặc 'Từ {ngày} · đến {ngày} ({lý do})'", ...);
test("khóa lưu trữ hiện dòng cảnh báo không ghi danh thêm được, nút Kết thúc ghi danh vẫn hoạt động", ...);
test("rỗng hiện 'Chưa có học sinh trong khóa'", ...);
test("bấm Kết thúc ghi danh mở dialog, xác nhận gọi DELETE với end_reason", ...);
```

- [ ] **Step 7: Run test, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enrollments-tab.test.tsx`
Expected: FAIL.

- [ ] **Step 8: Implement `course-end-enrollment-dialog.tsx`**

Copy `class-member-end-dialog.tsx`'s structure (reason required, `variant="danger"` confirm button) but confirm body text against `admin.md`'s AD-12a wording: "Em rời khóa nhưng lịch sử giữ." Button label "Kết thúc ghi danh" — **note**: `end-dialog`'s existing confirm-button label convention in `class-member-end-dialog.tsx` is generic "Xác nhận"; check mockup's own end-enrollment flow text (grepped this session at mockup L1912's inline `rd` dialog config: `confirm:'Xác nhận'`) — **the mockup itself uses "Xác nhận" here, not an action-specific label**, so this is one case where `mockup-screen-review`'s usual "nút xác nhận đổi theo hành động" rule doesn't apply because the mockup's own confirm button already says "Xác nhận" for this specific action; don't invent a different label.

- [ ] **Step 9: Implement `course-enrollments-tab.tsx`**

Mirror `class-members-tab.tsx`'s shape (Switch instead of search Input; no "Sửa thời hạn" button — AD-12a has no edit-period action, only end). Props take `courseStatus` to gate the archived-note and the "Ghi danh" button's enabled state (not its visibility — mockup keeps the button visible but shows the warning line above it; **re-read mockup L941 to confirm whether the button itself is disabled or just annotated** — it's wrapped in `sc-if value={{kd.canWrite}}` only, with the archived warning as a separate sibling line, meaning for Admin (`canWrite` always true) **the button stays clickable even when archived**, and the resulting `COURSE_ARCHIVED` 422 is what actually blocks it — do not add a client-side disable that the mockup doesn't have).

- [ ] **Step 10: Run test, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enrollments-tab.test.tsx`
Expected: PASS.

- [ ] **Step 11: Wire tab into `course-detail-screen.tsx`**

Replace Task 3's placeholder with `<CourseEnrollmentsTab courseId={courseId} courseStatus={course.status} ... />`, pass enrollment count into the "Học sinh đang ghi danh" `StatTile` from `useCourseEnrollments`'s active-count.

- [ ] **Step 12: Commit**

```bash
git add src/features/academic/api/course-enrollments.ts src/features/academic/api/course-enrollments.test.tsx src/features/academic/api/types.ts src/features/academic/api/keys.ts src/features/academic/ui/admin/course-enrollments-tab.tsx src/features/academic/ui/admin/course-enrollments-tab.test.tsx src/features/academic/ui/admin/course-end-enrollment-dialog.tsx src/features/academic/ui/admin/course-detail-screen.tsx
git commit -m "feat(academic): add Course enrollment roster tab (AD-12a)"
```

---

### Task 6: Enroll dialog (AD-12a — by student / by class, two-mode)

**Files:**
- Create: `src/features/academic/ui/admin/course-enroll-dialog.tsx`
- Test: `src/features/academic/ui/admin/course-enroll-dialog.test.tsx`
- Modify: `src/features/academic/ui/admin/course-enrollments-tab.tsx` (render the dialog, replace Task 5's stub toggle)
- Modify: `src/features/academic/ui/admin/course-detail-screen.tsx` (hero "Ghi danh" button opens the same dialog)

**Interfaces:**
- Consumes: `useEnrollInCourse(courseId)` (Task 5), `useClasses()` (same-feature, `academic/api/classes.ts`), `useStudents` (`@/features/user-management`, Task 4), `CourseEnrollmentListItem[]` (to compute already-enrolled student ids to exclude from the student-search candidates, mirroring `StudentPickerDialog`'s `excludeStudentIds`).
- Produces: `CourseEnrollDialog({ open, courseId, courseArchived, enrolledStudentIds, onClose })`.

- [ ] **Step 1: Write failing test for dialog modes and submit behavior**

Mockup L1291–1306 + JS L1682–1688 (`doEnroll`), L1916 (`enrCandidates`), L2114–2116 (`enModeTabs`, `onEnMode` resets `sid`/`cid`/`err`/`result`). Test cases:
```tsx
test("Tabs 'Theo học sinh'/'Theo cả lớp', đổi tab reset lựa chọn và lỗi/kết quả", ...);
test("khóa đã lưu trữ hiện cảnh báo 'Khóa học đã lưu trữ nên không ghi danh thêm được.'", ...); // enArchWarn — BE-driven text from COURSE_ARCHIVED on submit, not a client precheck; confirm whether mockup shows this proactively (L1294: yes, shown whenever archived, independent of submit) — render it as a static banner when courseArchived, separate from the submit-error path
test("tab Theo học sinh: tìm theo tên, chọn một học sinh (tick), bỏ chọn người đã ghi danh khỏi danh sách", ...); // excludeStudentIds = enrolledStudentIds (not just active — mockup's curIds only excludes *active* enrollments: `this.enrollments(k.id).filter(e=>!e.endedOn)`; pass only currently-active student ids as the exclude set, not ended ones, so a previously-ended student can be re-enrolled)
test("tab Theo cả lớp: chọn lớp từ Select 'Lớp · {n} học sinh'", ...); // enClassOptions format, L2115
test("bấm Ghi danh không chọn gì hiện lỗi 'Hãy chọn một học sinh hoặc một lớp.'", ...); // client-side guard before calling API — mirrors mockup's own client-side check at L1685/1686
test("ghi danh thành công hiện 'Đã ghi danh {n} học sinh' và không đóng dialog", ...); // dialog stays open per mockup — Đóng button closes it, not auto-close on success
test("created_count=0 hiện 'Tất cả đã được ghi danh từ trước' (không phải lỗi)", ...);
test("lỗi COURSE_ARCHIVED từ BE hiện message gốc của BE trong banner lỗi", ...);
```

- [ ] **Step 2: Run test, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enroll-dialog.test.tsx`
Expected: FAIL.

- [ ] **Step 3: Implement `course-enroll-dialog.tsx`**

State: `mode: "student" | "class"`, `sid: string | null`, `cid: string`, `search: string` (debounced like `student-picker-dialog.tsx`). Tabs (`variant="pill"`) value=mode, options `[{value:"student",label:"Theo học sinh"},{value:"class",label:"Theo lớp"}]` (mockup L2114). Student mode: `Input` search (icon search, pill shape, placeholder "Tìm học sinh theo tên"), candidate list (`useStudents({ name: debouncedSearch, per_page: ... })`, filter out `enrolledStudentIds`, render `Avatar` + name + sub-line of the student's class names — **the mockup's sub-line (`p.sub`) is the student's class names, which `StudentListItem` may not carry directly**; check `StudentListItem`'s shape from `types/student.ts` read this session — if it has no class-name field, fall back to omitting the sub-line rather than fetching per-student class lists, and note the gap, don't build an N+1), each row clickable to toggle `sid` (selected state = background highlight + check icon, **not** immediate submit — this differs from `StudentPickerDialog`'s immediate-add pattern; do not reuse that component's click handler). Class mode: `Select` label "Lớp", options from `useClasses()` filtered to non-archived, label `"{name} · {n học sinh}"` — **class active-member count isn't on `ClassListItem` as a plain field; check — it is (`student_count` is on `ClassListItem` per `types.ts` read in Task 1, called `student_count`)**, use it. Local client guard before calling the mutation: if (mode is student and !sid) or (mode is class and !cid) → set local error "Hãy chọn một học sinh hoặc một lớp." without calling the API. On submit, call `useEnrollInCourse(courseId).mutate({ student_id: sid } | { class_id: cid })`; on success set a local `result` string from `created_count` (format per mockup L1687, singular "Đã ghi danh N học sinh"/"Tất cả đã được ghi danh từ trước" — this dialog has no "skipped" count available since the BE only returns `created_count`, not a skipped breakdown; **omit the "(N em đã có từ trước)" parenthetical from the mockup's local-mock logic since the real API gives no such number** — show the simpler two-case message only). On error, render `error.message` verbatim in the danger-toned banner (do not close dialog). Buttons: "Đóng" (ghost, calls `onClose`, resets local state) / "Ghi danh" (primary, iconLeft "user-plus", `loading={mutation.isPending}`, disabled when `courseArchived`? — **re-check mockup L1294/1305: the button has no `disabled` binding tied to `enArchWarn` in the dialog markup grepped this session**; leave it clickable and let the 422 surface, matching the "let BE be the source of truth for business rules" convention (F7) — don't add a client-side disable the mockup doesn't have).

- [ ] **Step 4: Run test, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enroll-dialog.test.tsx`
Expected: PASS.

- [ ] **Step 5: Wire dialog into roster tab and detail-screen hero**

`course-enrollments-tab.tsx`'s "Ghi danh" button and `course-detail-screen.tsx`'s hero "Ghi danh" button both open the same `CourseEnrollDialog` (lift `open` state to `course-detail-screen.tsx` if both buttons need to control one dialog instance, or render two independent dialog instances — simplest: lift state to the detail screen, pass an `onEnrollClick` callback down to the tab per Task 5's stub).

- [ ] **Step 6: Commit**

```bash
git add src/features/academic/ui/admin/course-enroll-dialog.tsx src/features/academic/ui/admin/course-enroll-dialog.test.tsx src/features/academic/ui/admin/course-enrollments-tab.tsx src/features/academic/ui/admin/course-detail-screen.tsx
git commit -m "feat(academic): add Course enroll dialog, by student or by class (AD-12a)"
```

---

### Task 7: Manual pass against the real app + mockup side-by-side

**Files:** none (verification only).

- [ ] **Step 1: Start the stack**

`make install && make migrate && make restart-queue` in `piclass-infra/local` if not already running (it is, per this session — skip re-running `make install` unless dependencies changed).

- [ ] **Step 2: Run the full FE check suite**

Run: `pnpm lint && pnpm typecheck && pnpm vitest run && pnpm build`
Expected: all green, zero new ESLint warnings (max-warnings 0), zero TS errors.

- [ ] **Step 3: Re-export OpenAPI once more and diff against Task 1's assumptions**

Run: `docker compose exec api php artisan scramble:export` (in `piclass-infra/local`) — confirm no Course/Academic routes changed since this plan was written (if they did, note it and adjust before Step 4).

- [ ] **Step 4: Manually walk AD-12 and AD-12a in the browser against real data**, logged in as Admin: create a course (with and without owner/subject), archive one, edit an active one, open the "Học sinh ghi danh" tab, enroll by student, enroll by class, end an enrollment, confirm the archived-course enroll attempt surfaces `COURSE_ARCHIVED`'s real BE message.

- [ ] **Step 5: Self-check against mockup**

For every screen/dialog touched, re-open the exact mockup line ranges cited in each task above and diff against the rendered JSX/DOM — copy text, tab labels, button variants, column layout. Record any intentional deviation (student-count column omission, "Bài đánh giá" tab gap, owner column `hide-md`) in `docs/product/screens/admin.md`'s AD-12/AD-12a sections and in `mockup-plan.md` mục 6.

- [ ] **Step 6: Commit the doc updates**

```bash
git add docs/product/screens/admin.md docs/product/screens/mockup-plan.md
git commit -m "docs: record AD-12/AD-12a FE build notes and known deviations"
```
(This commit is likely in the `piclass-web` docs submodule path or the separate `piclass-web` docs repo depending on where `admin.md` physically lives in this checkout — confirm the repo root before committing; do not mix an `piclass-api`/`piclass-web` code commit with a docs-repo commit in one `git add`.)
