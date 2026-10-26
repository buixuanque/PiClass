# FE Course–Class Link (Admin, AD-12/AD-12a) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the one-off "Theo lớp" enrollment mode in `piclass-web`'s Course detail screen (AD-12/AD-12a) with the live Class–Course link feature the BE already ships (`piclass-api` PR #47, merged `main`): a "Lớp đã gắn" management area (link/unlink a class), a single merged enrollment list that labels and filters by source, and an enroll dialog reduced to individual enrollment only.

**Architecture:** Add one new data file (`course-class-links.ts`, mirroring `course-enrollments.ts`'s shape exactly) and three new UI files for the link-management area, then modify the three existing AD-12/AD-12a files (`course-detail-screen.tsx`, `course-enroll-dialog.tsx`, `course-enrollments-tab.tsx`) and their existing tests in place. No BE changes.

**Tech Stack:** Next.js 16 / React 19 / TypeScript, TanStack Query, Tailwind 4 design tokens, Vitest + Testing Library + MSW.

**Spec:** `../specs/2026-10-26-course-class-enrollment-link-design.md` (sections "API / FE (Admin, AD-12/AD-12a)" and "Quyết định đã chốt bởi user" — updated 2026-10-26 after a follow-up chat that finalized FE display as one merged enrollment list with a source filter, not split screens).

## Global Constraints

- Code convention: `piclass-web/CLAUDE.md` (F1–F8, file layout, naming). Feature stays `academic` (already the module name for `courses.ts`/`classes.ts`).
- Contract source of truth for the 3 new BE routes (confirmed this session by reading `piclass-api` source directly, OpenAPI not re-exported in this session — re-export via `docker compose exec api php artisan scramble:export` in `piclass-infra/local` before Task 1 if it looks stale):
  - `GET /courses/{course}/class-links` → `CourseClassLinkResource::collection`, only `status=active` links.
  - `POST /courses/{course}/class-links` body `{ class_id: uuid }` → `CourseClassLinkResource`, HTTP 201.
  - `DELETE /courses/{course}/class-links/{classId}` → no body, HTTP 204.
  - `CourseClassLinkResource` fields: `{ id, class_id, class_name, linked_at }`.
  - `GET /courses/{course}/enrollments` (`CourseEnrollmentListResource`) now additionally returns `enrolled_via_class_id: string | null` and `enrolled_via_class_name: string | null` (null for individual enrollment).
  - Error codes (already in `docs/architecture/error-codes.md`): `ALREADY_LINKED` (409, pair already has an active link), `CLASS_ARCHIVED` (422, linking an archived class), `COURSE_ARCHIVED` (422, same code the enroll dialog already handles). Render `error.message` verbatim (F7) — no custom copy per code.
  - Policy: same `CoursePolicy::manage` as every other course-mutating action already on this screen (Admin always; Teacher only if course owner). The screen does **not** currently do any extra ownership-based hiding for "Ghi danh"/"Lưu trữ khóa" (they show whenever `!isArchived`, relying on BE's 403 as the real gate) — follow that exact existing convention for "Gắn lớp"/"Gỡ lớp": show whenever `!isArchived`, no new permission-checking code.
- Response envelope: confirm at implementation time whether `POST /courses/{course}/class-links` wraps its single-resource response in `{ "data": ... }` (standard Laravel Resource default) before writing `courseClassLinksApi.link` — read `LinkClassToCourseController.php` / `CourseClassLinkResource.php` (`piclass-api/app/Modules/Academic/Http/Controllers/`, `.../Resources/`) for a `$wrap = null` override (like `EnrollmentResultResource` has) before assuming either shape.
- Placement: the "Lớp đã gắn" section renders inside the existing "Tổng quan" tab of `course-detail-screen.tsx`, stacked below `CourseOverviewTab`, as its own component — not merged into `course-overview-tab.tsx` (that file stays scoped to the editable course fields form) and not a new top-level tab.
- No new mockup exists for this UI (design doc explicitly skips drawing one — `../specs/2026-10-26-course-class-enrollment-link-design.md` non-goals). Copy and layout for the new components are this plan's own decisions (see each task), styled with existing `components/ui/*` primitives and the same tone/spacing as `course-archive-dialog.tsx`/`course-enroll-dialog.tsx` — do not invent new visual patterns.
- `CourseEnrollDialog`'s existing `enrolledStudentIds` prop (passed from `course-detail-screen.tsx` as every **active** enrollment's `student_id`, regardless of source) already excludes students who are enrolled via a class — `activeEnrollments` filters only on `status === "active"`, not on source. **No new exclusion logic is needed for this**; Task 3 only removes the "Theo lớp" tab, it does not touch the exclusion behavior.

## Review Focus

- **Unlinking a class whose students still qualify through another linked class.** The BE only ends enrollments that actually lose entitlement; a student in two classes both linked to the same course keeps their enrollment (with `source_class_link_id` silently repointed) after one class unlinks. The "Gỡ lớp" confirm dialog must not claim a fixed headcount will be removed — Task 2's copy says "tối đa" (at most), not an exact number, precisely because of this case.
- **Linking a class into a course that already has individual enrollments for some of its members.** BE keeps those as individual (does not reassign `source_class_link_id`), so after linking, some of the class's students show "Cá nhân" and others show "Qua lớp {tên}" in the same list — Task 4's row rendering must key the label off each row's own `enrolled_via_class_id`, never off "this student is in a linked class" inferred client-side.
- **Archived course: linking blocked, unlinking still allowed.** Mirrors the existing enroll/end-enrollment asymmetry already on this screen (`COURSE_ARCHIVED` only fires on the create-type action). Task 2 hides "Gắn lớp" when `isArchived` but always shows "Gỡ lớp" per existing link row, same pattern as `course-enrollments-tab.tsx`'s "Kết thúc ghi danh" staying enabled on an archived course.
- **A class with zero currently-linkable classes left (all already linked or archived).** The "Gắn lớp" dialog's class picker must render its own empty state ("Không còn lớp nào để gắn.") instead of an empty `Select` with no options and no feedback — same empty-candidate pattern `CourseEnrollDialog`'s student tab already uses ("Không còn học sinh để chọn.").
- **Filter interaction with the existing "Hiện cả em đã kết thúc ghi danh" switch.** The new source filter (Tất cả/Cá nhân/Qua lớp) and the existing ended/active switch are independent axes applied together (AND, not OR) — Task 4's tests must cover a combination (e.g., switch on + filter "Qua lớp" hides an ended individual row but keeps an ended via-class row).

---

## File Structure

```
src/features/academic/
├── api/
│   ├── course-class-links.ts            NEW — courseClassLinksApi + hooks
│   ├── types.ts                         MODIFY — add CourseClassLink, LinkClassToCourseInput; extend CourseEnrollmentListItem
│   └── keys.ts                          MODIFY — add courseClassLinks(courseId)
├── ui/admin/
│   ├── course-linked-classes-section.tsx   NEW — "Lớp đã gắn" list + Gắn lớp/Gỡ lớp buttons
│   ├── course-link-class-dialog.tsx        NEW — pick-a-class-to-link dialog
│   ├── course-unlink-class-dialog.tsx      NEW — confirm-unlink dialog
│   ├── course-detail-screen.tsx            MODIFY — render the section in tab "Tổng quan"
│   ├── course-enroll-dialog.tsx            MODIFY — remove "Theo lớp" mode
│   ├── course-enroll-dialog.test.tsx       MODIFY — drop by-class tests
│   ├── course-enrollments-tab.tsx          MODIFY — source label, hide end-button for via-class rows, source filter
│   └── course-enrollments-tab.test.tsx     MODIFY — add source label/filter tests
Test (new files):
├── api/course-class-links.test.tsx         NEW
└── ui/admin/course-linked-classes-section.test.tsx  NEW

docs/ (repo root, not piclass-web)
├── architecture/known-debt.md              MODIFY — close out the "EnrollInCourse class_id vẫn còn vì FE còn gọi" line
├── product/screens/admin.md                MODIFY — AD-12/AD-12a sections: drop "Theo cả lớp" tab description, add "Lớp đã gắn" + source filter
├── product/screens/mockup-plan.md          MODIFY — record this slice's completion + the "no new mockup" deviation
├── trang-thai-ngan.md, progress.md         MODIFY — mark nhóm 6 mở rộng xong (per CLAUDE.md working-flow)
```

---

### Task 1: Course–Class link data layer

**Files:**
- Create: `src/features/academic/api/course-class-links.ts`
- Test: `src/features/academic/api/course-class-links.test.tsx`
- Modify: `src/features/academic/api/types.ts`
- Modify: `src/features/academic/api/keys.ts`

**Interfaces:**
- Consumes: `apiClient` (`@/lib/api-client`), `academicKeys` pattern in `keys.ts`.
- Produces: types `CourseClassLink`, `LinkClassToCourseInput`; extended `CourseEnrollmentListItem` (adds `enrolled_via_class_id: string | null`, `enrolled_via_class_name: string | null`); `courseClassLinksApi`, `useCourseClassLinks(courseId)`, `useLinkClassToCourse(courseId)`, `useUnlinkClassFromCourse(courseId)` — same hook shape as `course-enrollments.ts`'s `useCourseEnrollments`/`useEnrollInCourse`.

- [ ] **Step 1: Add `courseClassLinks(courseId)` key**

In `src/features/academic/api/keys.ts`, add one entry alongside `courseEnrollments`:
```ts
courseClassLinks: (courseId: string) => ["academic", "course", courseId, "class-links"] as const,
```
(Match the existing array-literal style in the file exactly — it is not using a shared `all` base array, confirm before adding.)

- [ ] **Step 2: Write failing tests for the three hooks**

```tsx
// course-class-links.test.tsx — mirror course-enrollments.test.tsx's MSW/QueryClient setup exactly
test("useCourseClassLinks trả danh sách lớp đã gắn từ GET /courses/{id}/class-links", async () => { /* mock {data:[{id,class_id,class_name,linked_at}]} */ });
test("useLinkClassToCourse gọi POST với class_id và invalidate class-links lẫn enrollments", async () => { /* assert both query keys refetched/invalidated */ });
test("useUnlinkClassFromCourse gọi DELETE /courses/{id}/class-links/{classId} và invalidate class-links lẫn enrollments", async () => { /* ... */ });
```
Use exact field names from the Global Constraints section. Assert the invalidate-both behavior by spying on `queryClient.invalidateQueries` or by asserting both queries refetch (follow whichever assertion style `course-enrollments.test.tsx` already uses for its own invalidation test, if it has one — if not, assert via a second `useQuery` reading `academicKeys.courseEnrollments` id and checking it refetches after the mutation).

- [ ] **Step 3: Run tests, verify fail**

Run: `pnpm vitest run src/features/academic/api/course-class-links.test.tsx`
Expected: FAIL — module has no exports yet.

- [ ] **Step 4: Add types to `types.ts`**

Append near the `CourseEnrollment*` section:
```ts
/** GET /courses/{id}/class-links — một lớp đang gắn (CourseClassLinkResource, chỉ status active). */
export interface CourseClassLink {
  id: string;
  class_id: string;
  class_name: string;
  linked_at: string;
}

/** POST /courses/{id}/class-links */
export interface LinkClassToCourseInput {
  class_id: string;
}
```
And extend the existing `CourseEnrollmentListItem` interface (do not duplicate it) with:
```ts
  enrolled_via_class_id: string | null;
  enrolled_via_class_name: string | null;
```
Update its docblock to drop the now-stale implication that enrollment rows have no source info.

- [ ] **Step 5: Implement `course-class-links.ts`**

```ts
export const courseClassLinksApi = {
  list: (courseId: string) => apiClient.get<CourseClassLink[]>(`/courses/${courseId}/class-links`),
  link: (courseId: string, input: LinkClassToCourseInput) =>
    apiClient.post<CourseClassLink>(`/courses/${courseId}/class-links`, input),
  unlink: (courseId: string, classId: string) =>
    apiClient.delete<void>(`/courses/${courseId}/class-links/${classId}`),
};

export function useCourseClassLinks(courseId: string) { /* useQuery(academicKeys.courseClassLinks(courseId), ...) */ }

export function useLinkClassToCourse(courseId: string) {
  /* useMutation; onSuccess invalidates BOTH academicKeys.courseClassLinks(courseId) AND academicKeys.courseEnrollments(courseId) — linking creates enrollments */
}

export function useUnlinkClassFromCourse(courseId: string) {
  /* useMutation((classId: string) => courseClassLinksApi.unlink(courseId, classId)); onSuccess invalidates the same two keys — unlinking ends enrollments */
}
```
Before writing `link`'s return type, resolve the Global Constraints note on `$wrap` — if `CourseClassLinkResource` has no `$wrap = null`, the real body is `{ data: {...} }` and `apiClient.post<CourseClassLink>` already unwraps it (same as `classesApi.create`); if it does have `$wrap = null` (like `EnrollmentResultResource`), the body is the bare object. Write the MSW mock in Step 2 to match whichever is true, not both.

- [ ] **Step 6: Run tests, verify pass**

Run: `pnpm vitest run src/features/academic/api/course-class-links.test.tsx`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add src/features/academic/api/course-class-links.ts src/features/academic/api/course-class-links.test.tsx src/features/academic/api/types.ts src/features/academic/api/keys.ts
git commit -m "feat(academic): add Course-Class link data layer"
```

---

### Task 2: "Lớp đã gắn" section + link/unlink dialogs

**Files:**
- Create: `src/features/academic/ui/admin/course-linked-classes-section.tsx`
- Create: `src/features/academic/ui/admin/course-link-class-dialog.tsx`
- Create: `src/features/academic/ui/admin/course-unlink-class-dialog.tsx`
- Test: `src/features/academic/ui/admin/course-linked-classes-section.test.tsx`
- Modify: `src/features/academic/ui/admin/course-detail-screen.tsx`

**Interfaces:**
- Consumes: `useCourseClassLinks`, `useLinkClassToCourse`, `useUnlinkClassFromCourse` (Task 1); `useClasses()` (`./classes.ts`, already imported elsewhere in this feature); `CourseClassLink` type.
- Produces: `CourseLinkedClassesSection({ courseId, courseArchived })`, `CourseLinkClassDialog({ open, courseId, linkedClassIds, onClose })`, `CourseUnlinkClassDialog({ open, courseId, classId, className, onClose })`.

- [ ] **Step 1: Write failing tests for `CourseLinkedClassesSection`**

```tsx
// course-linked-classes-section.test.tsx — MSW + QueryClient wrapper, mirror course-enrollments-tab.test.tsx's structure
test("hiện danh sách lớp đã gắn với tên lớp và ngày gắn", ...); // GET class-links → render class_name + formatted linked_at via lib/time
test("rỗng hiện 'Chưa gắn lớp nào vào khóa này.'", ...);
test("khóa đã lưu trữ ẩn nút 'Gắn lớp', vẫn hiện nút 'Gỡ lớp' trên từng dòng", ...);
test("bấm 'Gắn lớp' mở dialog chọn lớp, chọn một lớp rồi xác nhận gọi POST với class_id", ...);
test("dialog chọn lớp loại trừ lớp đã gắn và lớp đã lưu trữ khỏi danh sách chọn", ...);
test("không còn lớp nào để gắn thì dialog hiện 'Không còn lớp nào để gắn.'", ...);
test("bấm 'Gỡ lớp' mở dialog xác nhận, xác nhận gọi DELETE đúng classId", ...);
test("dialog Gỡ lớp hiện 'Tối đa {N} học sinh' theo đúng student_count của lớp đó, không khẳng định số chắc chắn", ...); // Review Focus: cross-class entitlement means it's an upper bound, not a guaranteed count
test("lỗi ALREADY_LINKED/CLASS_ARCHIVED từ BE hiện message gốc trong dialog", ...);
```
Use `formatDate` from `@/lib/time` for `linked_at` (F8 — no direct `Intl`/`toLocaleString`), same as `course-enrollments-tab.tsx`'s `period` formatting.

- [ ] **Step 2: Run tests, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-linked-classes-section.test.tsx`
Expected: FAIL.

- [ ] **Step 3: Implement `course-unlink-class-dialog.tsx`**

Structure mirrors `course-archive-dialog.tsx` (no reason field, `variant="danger"` confirm button) but takes `classId`/`className` instead of course fields and calls `useUnlinkClassFromCourse(courseId).mutate(classId, { onSuccess: onClose })`. Body copy (this plan's own wording, no mockup to match — keep it short and in the same two-paragraph shape as `course-archive-dialog.tsx`):
```
"Tối đa {N} học sinh của lớp {className} sẽ bị kết thúc ghi danh khóa này — trừ em nào vẫn đang học khóa qua một lớp khác cũng đang gắn với khóa này."
"Không cần nhập lý do cho thao tác này."
```
`{N}` is the linked class's current member count — pass it in as a prop (`memberCount: number`) computed by the caller (Task 2 Step 5) from `useClasses()`'s `student_count` field for that `class_id`, not fetched again here.

- [ ] **Step 4: Implement `course-link-class-dialog.tsx`**

Single-`Select` dialog (label "Lớp", placeholder "Chọn lớp"), structured like the "by-class" branch being removed from `course-enroll-dialog.tsx` in Task 3 (same `Select` + helper-text shape) but simpler: no `cid` default beyond empty string, options = `useClasses()` data filtered to `status !== "archived"` AND `id` not already in `linkedClassIds` (prop). When the filtered list is empty, render "Không còn lớp nào để gắn." instead of the `Select`. On submit with no class picked, local error "Hãy chọn một lớp." (mirror `course-enroll-dialog.tsx`'s guard pattern). Calls `useLinkClassToCourse(courseId).mutate({ class_id })`; on error render `error.message` verbatim in the same danger-toned banner pattern as `course-enroll-dialog.tsx`. On success, call `onClose()` (unlike `CourseEnrollDialog`, which stays open — linking one class at a time with an immediate visible result in the list below is enough feedback; document this as the deliberate difference from the enroll dialog's multi-step staying-open flow).

- [ ] **Step 5: Implement `course-linked-classes-section.tsx`**

Props `{ courseId, courseArchived }`. `useCourseClassLinks(courseId)` for the list, `useClasses()` for member counts (lookup by `class_id`, fallback `0` if not found — a class could theoretically be deleted, though no hard-delete flow exists yet per `known-debt.md`). Render: heading "Lớp đã gắn", "Gắn lớp" button (hidden when `courseArchived`, same `!isArchived` convention as the screen's other action buttons — no extra permission check), list rows (class name, "Gắn lúc {formatDate(linked_at)}", "Gỡ lớp" button opening `CourseUnlinkClassDialog` with that row's `memberCount`), empty state "Chưa gắn lớp nào vào khóa này." Local `useState` for which dialog is open (link dialog boolean, unlink dialog target `CourseClassLink | null`), same pattern as `course-enrollments-tab.tsx`'s `endTarget` state.

- [ ] **Step 6: Run tests, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-linked-classes-section.test.tsx`
Expected: PASS.

- [ ] **Step 7: Wire the section into `course-detail-screen.tsx`**

In the `tab === "overview"` branch (`course-detail-screen.tsx:137`), render `<CourseLinkedClassesSection courseId={courseId} courseArchived={isArchived} />` directly below `<CourseOverviewTab courseDetail={course} />` (both inside the same `Card`, stacked — read the current JSX first to match its existing spacing convention, e.g. whether sibling blocks inside that `Card` use a wrapping `div` with a gap class).

- [ ] **Step 8: Commit**

```bash
git add src/features/academic/ui/admin/course-linked-classes-section.tsx src/features/academic/ui/admin/course-linked-classes-section.test.tsx src/features/academic/ui/admin/course-link-class-dialog.tsx src/features/academic/ui/admin/course-unlink-class-dialog.tsx src/features/academic/ui/admin/course-detail-screen.tsx
git commit -m "feat(academic): add 'Lop da gan' class-link management section"
```

---

### Task 3: Enroll dialog — drop "Theo lớp" mode

**Files:**
- Modify: `src/features/academic/ui/admin/course-enroll-dialog.tsx`
- Modify: `src/features/academic/ui/admin/course-enroll-dialog.test.tsx`

**Interfaces:**
- Consumes: unchanged (`useEnrollInCourse`, `useStudents`) — this task only removes the `by-class` branch and its dependency on `useClasses`.
- Produces: `CourseEnrollDialog` keeps its exact existing prop signature (`{ open, courseId, courseArchived, enrolledStudentIds, onClose }`) — no caller changes needed beyond this file and its test.

- [ ] **Step 1: Update the test file first (TDD on the deletion)**

In `course-enroll-dialog.test.tsx`:
- Delete the `http.get(${origin}/api/v1/classes` MSW handler from `beforeEach` (no longer called by this dialog).
- Rewrite `"có Tabs 'Theo học sinh'/'Theo cả lớp'..."` → remove the Tabs assertions entirely (there is only one mode now, so no `Tabs` component renders) — keep the "submit with nothing picked shows the error" assertion, just without switching tabs first.
- Delete `"tab Theo cả lớp: chọn lớp từ Select..."` entirely.
- Rewrite `"ghi danh thành công hiện..."` and `"created_count=0 hiện..."` and `"lỗi COURSE_ARCHIVED..."` to submit via the student picker (`userEvent.click(screen.getByText("Đỗ Bảo Vy"))` then click "Ghi danh") instead of selecting a class.
- Keep `"khóa đã lưu trữ hiện cảnh báo..."`, `"tab Theo học sinh: tìm theo tên..."`, and `"chỉ gọi /students với status=active..."` as-is (student-mode behavior unchanged).

- [ ] **Step 2: Run tests, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enroll-dialog.test.tsx`
Expected: FAIL — component still renders the Tabs/by-class UI the rewritten tests no longer expect, and the class-select-based submit tests no longer find a `Select`.

- [ ] **Step 3: Remove the by-class mode from `course-enroll-dialog.tsx`**

Delete: `MODE_TABS` constant, the `mode`/`cid` state, the `useClasses` import and `classesQuery`/`classOptions`, the `<Tabs .../>` element, the `changeMode` function, and the `mode === "by-class"` branch (the `<Select label="Lớp" ...>` block and its helper paragraph). Keep everything in the `mode === "by-student"` branch but drop the now-unconditional ternary — render that JSX unconditionally. Simplify `submit()`'s guard to just `if (!sid)` and its mutate call to always `{ student_id: sid as string }`. Simplify `close()`/the dialog's `description` prop (currently "Ghi danh theo từng học sinh hoặc theo cả lớp, không chọn cả hai cùng lúc." — change to a single-mode description, e.g. "Tìm và chọn một học sinh để ghi danh.").

- [ ] **Step 4: Run tests, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enroll-dialog.test.tsx`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/features/academic/ui/admin/course-enroll-dialog.tsx src/features/academic/ui/admin/course-enroll-dialog.test.tsx
git commit -m "refactor(academic): drop by-class enroll mode, superseded by class links"
```

---

### Task 4: Enrollments tab — source label and filter

**Files:**
- Modify: `src/features/academic/ui/admin/course-enrollments-tab.tsx`
- Modify: `src/features/academic/ui/admin/course-enrollments-tab.test.tsx`

**Interfaces:**
- Consumes: `CourseEnrollmentListItem`'s new `enrolled_via_class_id`/`enrolled_via_class_name` fields (Task 1).
- Produces: `CourseEnrollmentsTab`'s existing prop signature unchanged (`{ courseId, courseStatus, onEnrollClick }`).

- [ ] **Step 1: Add failing tests for the new behavior**

Add to `course-enrollments-tab.test.tsx` (extend the `rows` fixture with a third, via-class row, e.g. `{ student_id: "st3", student_name: "Lê An", status: "active", valid_from: "...", valid_to: null, enrolled_via_class_id: "c1", enrolled_via_class_name: "Toán 9A" }`; add `enrolled_via_class_id: null, enrolled_via_class_name: null` to the two existing fixture rows):
```tsx
test("dòng ghi danh qua lớp hiện nhãn 'Qua lớp {tên lớp}' và không có nút Kết thúc ghi danh", ...);
test("dòng ghi danh cá nhân không hiện nhãn nguồn, có nút Kết thúc ghi danh", ...);
test("bộ lọc nguồn: 'Tất cả'/'Cá nhân'/'Qua lớp' lọc đúng danh sách", ...);
test("bộ lọc nguồn kết hợp với Switch hiện cả đã kết thúc (AND, không phải OR)", ...); // Review Focus item
```

- [ ] **Step 2: Run tests, verify fail**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enrollments-tab.test.tsx`
Expected: FAIL.

- [ ] **Step 3: Implement the source label and button gating**

In the row-rendering block (`course-enrollments-tab.tsx:72-90`), add a small muted-text label next to or below the existing status `Badge` when `row.enrolled_via_class_id` is not null: `` `Qua lớp ${row.enrolled_via_class_name}` `` (no label element at all for individual rows — per the finalized spec decision, not even an explicit "Cá nhân" tag, to keep individual rows visually unchanged). Change the `row.status === "active" && (<Button ...>Kết thúc ghi danh</Button>)` condition to `row.status === "active" && row.enrolled_via_class_id === null && (...)`.

- [ ] **Step 4: Implement the source filter**

Add `const [sourceFilter, setSourceFilter] = useState<"all" | "individual" | "class">("all")`. Render a `Tabs variant="pill"` (same component/variant as `MODE_TABS` used elsewhere in this feature) with options `[{value:"all",label:"Tất cả"},{value:"individual",label:"Cá nhân"},{value:"class",label:"Qua lớp"}]`, placed next to the existing `Switch` in the top row. Extend the existing `rows` filter chain: `all.filter(status).filter(source)` where the source predicate is `sourceFilter === "all" ? true : sourceFilter === "individual" ? e.enrolled_via_class_id === null : e.enrolled_via_class_id !== null`.

- [ ] **Step 5: Run tests, verify pass**

Run: `pnpm vitest run src/features/academic/ui/admin/course-enrollments-tab.test.tsx`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/features/academic/ui/admin/course-enrollments-tab.tsx src/features/academic/ui/admin/course-enrollments-tab.test.tsx
git commit -m "feat(academic): show enrollment source and add source filter in roster tab"
```

---

### Task 5: Manual verification + docs

**Files:**
- Modify: `docs/architecture/known-debt.md`
- Modify: `docs/product/screens/admin.md`
- Modify: `docs/product/screens/mockup-plan.md`
- Modify: `docs/trang-thai-ngan.md`, `docs/progress.md`

- [ ] **Step 1: Run the full FE check suite**

Run: `pnpm lint && pnpm typecheck && pnpm vitest run && pnpm build`
Expected: all green, zero new ESLint warnings, zero TS errors.

- [ ] **Step 2: Manually walk the feature in the browser against real data**

Logged in as Admin: open a course's "Tổng quan" tab, link a class (verify its active members instantly appear in "Học sinh ghi danh" with "Qua lớp {tên}" and no end button), unlink it (verify those same enrollments end, except any student also in another linked class), add a new student to a linked class and confirm they auto-enroll, remove a student from a linked class and confirm their enrollment auto-ends, try the enroll dialog (single mode now) and confirm a student already enrolled via a class doesn't appear in the picker.

- [ ] **Step 3: Update `docs/architecture/known-debt.md`**

Find the row: "`EnrollInCourse`/`EnrollInCourseRequest` vẫn còn nhánh ghi danh theo `class_id`... chưa xóa vì `piclass-web` (dialog Ghi danh > Theo lớp) còn gọi tới". Rewrite it to reflect that FE no longer calls the `class_id` branch as of this plan (it is now fully dead code, not just superseded), and change the "khi nào xử lý" column to point at a future BE cleanup task (remove the `class_id` branch from `EnrollInCourse`/`EnrollInCourseRequest`) rather than "khi plan FE..." (that trigger has now happened).

- [ ] **Step 4: Update `docs/product/screens/admin.md`**

In the AD-12 section (~line 467): note the new "Lớp đã gắn" block under the course detail description. In AD-12a (~line 483): replace "Hộp thoại ghi danh có hai chế độ (tab): Theo học sinh... và Theo cả lớp..." with a description of the single-mode dialog, plus the new source label/filter on the roster list and the "Lớp đã gắn" management block (link/unlink), cross-referencing `../../superpowers/specs/2026-10-26-course-class-enrollment-link-design.md` for the business rules. Update the summary table row (~line 558) if its one-line description ("Ghi danh theo học sinh hoặc theo lớp") no longer matches.

- [ ] **Step 5: Update `docs/product/screens/mockup-plan.md` mục 6**

Record this slice as done, noting the deliberate deviation of building the "Lớp đã gắn" UI without a drawn mockup (per the design doc's non-goals), and the final FE display decision (merged list + source filter, not split screens).

- [ ] **Step 6: Update `docs/trang-thai-ngan.md` and `docs/progress.md`**

Per `CLAUDE.md`'s working-flow instructions: mark nhóm 6's Class–Course link expansion as FE done (reference this plan's file and the PR number once opened), update "Đang làm tiếp theo" to the next piece of work (tổng hợp danh sách giữ/đơn giản/tạm bỏ của cổng Admin, chuyển sang cổng Giáo viên).

- [ ] **Step 7: Commit the doc updates**

```bash
git add docs/architecture/known-debt.md docs/product/screens/admin.md docs/product/screens/mockup-plan.md docs/trang-thai-ngan.md docs/progress.md
git commit -m "docs: record FE course-class link build (AD-12/AD-12a)"
```
(Confirm which repo root these paths resolve to before committing — this is the `piclass-docs` repo, separate from `piclass-web`'s own commits in Tasks 1–4.)
