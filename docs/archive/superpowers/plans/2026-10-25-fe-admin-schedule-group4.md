# Nhóm 4 FE (Lịch học và buổi học) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the FE for AD-10d (tab Lịch), AD-10e (tab Buổi học) of class detail, and the new AD-11 "Lịch tổng hợp toàn hệ thống" screen in `piclass-web`.

**Architecture:** New feature `src/features/lesson/` (module Lesson) holding Schedule/LessonSession/MyLessonSession API hooks and types, plus admin UI components. `ClassScheduleTab`/`ClassSessionsTab` replace the existing placeholder tabs wired into `academic`'s `class-detail-screen.tsx` (cross-feature import via `lesson`'s `index.ts`, same pattern `user-management` already uses for `ClassMembersTab`/`ClassTeachersTab`). AD-11 is a new top-level screen/route reusing the same API hooks with different filters.

**Tech Stack:** Next.js 16 / React 19 / TypeScript, TanStack Query, React Hook Form + Zod, MSW + Vitest + Testing Library for tests, Tailwind design-system v2 tokens.

**Spec:**
- `docs/product/screens/admin.md` — AD-10d (lines 426-436), AD-10e (438-450), AD-11 (456-466).
- Mockup `piclass-mockup/design-system/standalone/PiClass Quản trị.html` — tab Lịch lines 677-692, tab Buổi học 694-721, AD-11 726-796, CSS `.cal-week/.cal-month/.cal-head` 46-50, calendar data derivation 1879-1894.
- BE contract already stable (no BE work in this plan): `piclass-api` Lesson module resources read directly from source during this plan's writing — exact shapes pinned below per task.
- User decisions (2026-10-25, this conversation): hide the "Điểm danh" button entirely on AD-10e (TC-05 not built yet); AD-11 session click auto-switches to the Buổi học tab and highlights/scrolls to that session; AD-11 route is `/admin/schedule` labeled "Lịch" in nav; teacher-conflict flag and the "Chưa kiểm tra trùng phòng học" caption are both kept, computed/shown on the FE.

## Global Constraints

- Mọi thời điểm hiển thị đi qua `lib/time.ts` (F8) — không gọi `toLocaleString()`/`Intl.DateTimeFormat` trực tiếp.
- `features/lesson/api/` không import `ui/`, `components/`, `next/*`, không JSX (F3).
- `ui/admin/` của `lesson` không import `ui/<vai trò khác>/` của feature khác (F4); cross-feature dùng qua `@/features/lesson` (`index.ts`, F5).
- Component tương tác khai `"use client"`; `page.tsx` chỉ ghép (F1).
- Giờ giờ vào/ra theo `Asia/Ho_Chi_Minh`; input giờ không có offset được BE hiểu là giờ VN.
- Màu/spacing/radius chỉ dùng token Tailwind đã có, không số cố định ngoài giá trị hard-code đúng từ mockup.
- FE không tự quyết rule nghiệp vụ (F7): validate zod chỉ kiểm dạng; lỗi 422 của BE hiện nguyên văn theo field.
- Mỗi file ~200 dòng là ngưỡng nên tách.

## Review Focus

- **Lịch không lặp (`repeat_weekly=false`):** BE vẫn sinh đúng 1 buổi tại `effective_from` — form tạo lịch không được ẩn/validate kiểu "không lặp thì không cần ngày" sai cách; cảnh báo xung đột vẫn hiển thị dù không lặp.
- **`weekdays` rỗng khi `repeat_weekly=true`:** phải chặn ở FE trước khi gửi (ngoài rule BE) vì trải nghiệm bấm "Tạo lịch" mà không chọn thứ nào dễ xảy ra khi người dùng quên.
- **Buổi đã `completed` sửa mà không có lý do:** BE trả 422 cụ thể cho field `reason`; dialog sửa buổi phải tự biết hiện ô lý do là "bắt buộc" khi `session.status === 'completed'`, không chỉ dựa vào lỗi 422 trả về sau khi bấm lưu.
- **AD-11 lọc `class_id/teacher_id/subject_id` đồng thời:** không được ghi đè lẫn nhau trong query string (ví dụ chọn lớp rồi đổi giáo viên phải giữ lại lớp đã chọn) — test phải dựng đủ 2+ filter cùng lúc.
- **Session trùng giờ nhưng khác giáo viên, hoặc cùng giáo viên nhưng không trùng giờ:** hàm phát hiện xung đột teacher phải không báo dương tính giả ở cả hai trường hợp biên này.

---

## Task 1: Lesson API layer — Schedule

**Files:**
- Create: `src/features/lesson/api/types.ts`
- Create: `src/features/lesson/api/keys.ts`
- Create: `src/features/lesson/api/schedules.ts`
- Create: `src/features/lesson/api/schemas.ts`
- Create: `src/features/lesson/index.ts`
- Test: `src/features/lesson/api/schedules.test.tsx`

**Interfaces:**
- Produces (in `types.ts`): `ScheduleStatus = "active" | "cancelled" | "ended"`; `Schedule { id, status: ScheduleStatus, effective_from: string, effective_until: string | null, local_start_time: string, duration_minutes: number, repeat_weekly: boolean, weekdays: number[] }`; `CreateScheduleInput { effective_from: string, effective_until?: string | null, local_start_time: string, duration_minutes: number, repeat_weekly: boolean, weekdays?: number[] }`; `ScheduleConflictWarning { type: string, conflicting_schedule_id: string, class_id: string }`; `CreatedSchedule { id, class_id, status: ScheduleStatus, effective_from, local_start_time, duration_minutes }`; `CreateScheduleResult { schedule: CreatedSchedule, warnings: ScheduleConflictWarning[] }`; `UpdateScheduleInput { effective_until?: string | null, local_start_time?: string, duration_minutes?: number, weekdays?: number[], reason: string }`; `UpdatedSchedule { id, local_start_time, duration_minutes, weekdays: number[] }`.
- Produces (in `keys.ts`): `lessonKeys.schedules(classId: string)`.
- Produces (in `schedules.ts`): `useSchedules(classId: string)`, `useCreateSchedule(classId: string)`, `useUpdateSchedule(classId: string)` (mutate takes `{ scheduleId: string; input: UpdateScheduleInput }`), `useCancelSchedule(classId: string)` (mutate takes `{ scheduleId: string; cancel_reason: string }`).
- Produces (in `schemas.ts`): `WEEKDAY_LABELS: Record<number, string>` = `{1:"T2",2:"T3",3:"T4",4:"T5",5:"T6",6:"T7",7:"CN"}` (ISO weekday, 1 = Thứ Hai — khớp BE `isoWeekday()`); `createScheduleFormSchema` (zod) and `ScheduleCreateFormValues`; `editScheduleFormSchema` and `ScheduleEditFormValues`.
- Consumes: none (first task).

Exact BE shapes to pin (read from `piclass-api` source, already stable, no BE work needed):
- `GET /classes/{id}/schedules` → `{ data: Schedule[] }` (field names exactly as `Schedule` above — `ScheduleListResource`).
- `POST /classes/{id}/schedules` → 201, body = `CreateScheduleResult` exactly (no `data` wrapper — `CreatedScheduleResource`, `$wrap = null`).
- `PATCH /schedules/{id}` (body `UpdateScheduleInput`) → `{ schedule: UpdatedSchedule }` (`UpdatedScheduleResource`).
- `POST /schedules/{id}/cancel` body `{ cancel_reason: string }` → 204 no content.

- [ ] **Step 1: Write the failing test** in `schedules.test.tsx` (same pattern as `src/features/academic/api/classes.test.tsx`: `setupServer`, `wrapper` with fresh `QueryClient`, `document.cookie = "XSRF-TOKEN=test-token"` in `beforeEach`). Cover with `renderHook`:
  - `useSchedules("c1")` against `GET /api/v1/classes/c1/schedules` returning one `Schedule` row; assert `result.current.data?.[0]` equals that row.
  - `useCreateSchedule("c1")` against `POST /api/v1/classes/c1/schedules` returning `{ schedule: {...}, warnings: [{type:"teacher_schedule_conflict", conflicting_schedule_id:"sc2", class_id:"c2"}] }`; assert `result.current.data?.warnings` has length 1.
  - `useUpdateSchedule("c1")` against `PATCH /api/v1/schedules/sc1`, mutate with `{ scheduleId: "sc1", input: { duration_minutes: 90, reason: "Đổi giờ" } }`; assert resolved value equals the `schedule` object.
  - `useCancelSchedule("c1")` against `POST /api/v1/schedules/sc1/cancel` returning 204; mutate with `{ scheduleId: "sc1", cancel_reason: "Nghỉ lễ" }`; assert `isSuccess`.
- [ ] **Step 2: Run tests to verify they fail** — `pnpm vitest run src/features/lesson/api/schedules.test.tsx` — expected FAIL (module not found).
- [ ] **Step 3: Implement `types.ts`, `keys.ts`, `schedules.ts`** per the Interfaces block. `schedulesApi.create`/`update`/`cancel` invalidate `lessonKeys.schedules(classId)` on success (mirror `useCreateClass`/`useUpdateClass` in `academic/api/classes.ts`).
- [ ] **Step 4: Implement `schemas.ts`** — `createScheduleFormSchema`: `effective_from` required string, `effective_until` optional string, `local_start_time` required `HH:mm` string, `duration_minutes` coerced positive int, `repeat_weekly` boolean, `weekdays` `number[]`; `.refine` that `repeat_weekly=false || weekdays.length>0` with message "Vui lòng chọn ít nhất một thứ." on path `weekdays` (Review Focus item 2). `editScheduleFormSchema`: `local_start_time`, `duration_minutes`, `weekdays`, `effective_until` all optional, `reason` required min 1 char ("Vui lòng nhập lý do.").
- [ ] **Step 5: Create `src/features/lesson/index.ts`** re-exporting nothing yet beyond a comment header (`// Cửa ngõ công khai của feature lesson (F5).`) — later tasks add exports here.
- [ ] **Step 6: Run tests to verify they pass.**
- [ ] **Step 7: Commit** — `feat: add Lesson feature's Schedule API layer`.

---

## Task 2: ClassScheduleTab (AD-10d list) wired into class detail

**Files:**
- Create: `src/features/lesson/ui/admin/class-schedule-tab.tsx`
- Modify: `src/features/academic/ui/admin/class-detail-screen.tsx` (swap `ClassSchedulePlaceholderTab` for `ClassScheduleTab`, remove the placeholder import)
- Delete: `src/features/academic/ui/admin/class-schedule-placeholder-tab.tsx`
- Modify: `src/features/lesson/index.ts` (export `ClassScheduleTab`, `useSchedules`)
- Test: `src/features/lesson/ui/admin/class-schedule-tab.test.tsx`

**Interfaces:**
- Consumes: `useSchedules` from Task 1.
- Produces: `ClassScheduleTab({ classId }: { classId: string })` — self-fetches via `useSchedules(classId)` (mirrors `ClassOverviewTab` self-fetching `useSubjects()` despite receiving props). No create/edit/cancel actions yet (Tasks 3-4 add the dialogs and wire the buttons into this file).

Mockup reference: list block lines 677-692 (header icon `calendar-days`, row = `daysText` / `timeText · rangeText`, `Badge` status, empty state "Chưa có lịch học" / "Tạo lịch để hệ thống sinh buổi học cho 4 tuần tới."). `daysText` derivation (mockup line 1866): lặp → thứ trong `weekdays` nối bằng `, ` qua `WEEKDAY_LABELS`; không lặp → `"Một buổi · " + formatDate(effective_from)`. `timeText`: `local_start_time` (HH:mm, bỏ giây) + "–" + giờ kết thúc (`local_start_time + duration_minutes`, tính bằng phép cộng phút thủ công — không có sẵn hàm BE, viết hàm nhỏ `addMinutesToTime` trong tab này) + " · " + `duration_minutes + " phút"`. `rangeText`: lặp → `"Từ " + formatDate(effective_from) + " · " + (effective_until ? "đến " + formatDate(effective_until) : "không giới hạn")`; không lặp → `"Không lặp"`.

- [ ] **Step 1: Write the failing test** in `class-schedule-tab.test.tsx` (pattern: `class-overview-tab.test.tsx`). Cases:
  - Renders one active schedule row showing days/time/range text and a "Đang áp dụng" badge.
  - Empty state shows "Chưa có lịch học".
- [ ] **Step 2: Run test, verify FAIL.**
- [ ] **Step 3: Implement `class-schedule-tab.tsx`** with the row/empty states per mockup reference above; status badge tones: `active` → green, `cancelled`/`ended` → neutral (reuse an existing `Badge` tone mapping style like `CLASS_STATUS_BADGE` in `class-badges.tsx` for the pattern, define a local `SCHEDULE_STATUS_BADGE` map in this file since it's schedule-specific).
- [ ] **Step 4: Wire into `class-detail-screen.tsx`**: replace `{tab === "schedule" && <ClassSchedulePlaceholderTab />}` with `{tab === "schedule" && <ClassScheduleTab classId={classId} />}`; remove the old import and delete `class-schedule-placeholder-tab.tsx`.
- [ ] **Step 5: Export from `src/features/lesson/index.ts`.**
- [ ] **Step 6: Run full test suite for touched files, verify PASS**, including `class-detail-screen.test.tsx` still green.
- [ ] **Step 7: Commit** — `feat: show real schedule list in AD-10d tab`.

---

## Task 3: ScheduleFormDialog (create + edit)

**Files:**
- Create: `src/features/lesson/ui/admin/schedule-form-dialog.tsx`
- Modify: `src/features/lesson/ui/admin/class-schedule-tab.tsx` (add "Tạo lịch" button, "Sửa" button per row, open dialog)
- Test: `src/features/lesson/ui/admin/schedule-form-dialog.test.tsx`

**Interfaces:**
- Consumes: `useCreateSchedule`, `useUpdateSchedule`, `createScheduleFormSchema`, `editScheduleFormSchema`, `WEEKDAY_LABELS` from Task 1.
- Produces: `ScheduleFormDialog({ open, classId, mode, schedule, onClose }: { open: boolean; classId: string; mode: "create" | "edit"; schedule: Schedule | null; onClose: () => void })`. Mount with `key={schedule?.id ?? "create"}` from the parent (same reset-on-remount pattern as `ClassMemberEditPeriodDialog`'s doc comment).

Mockup reference: form fields line 430 (tạo) — ngày bắt đầu*, ngày kết thúc, giờ bắt đầu*, thời lượng (phút)*, lặp hằng tuần* (`Switch`), chọn các thứ (7 toggle chips, bắt buộc khi lặp — render inline as a row of `Button` with `variant={selected ? "primary" : "outline"}` toggling membership in a local `weekdays` array, no new shared primitive needed since used only here). Result state after create: "đã tạo lịch và sinh N buổi cho 4 tuần tới" + warnings rendered as a `bg-warning-soft` box per warning, text: "Giáo viên ... trùng lịch với lớp ..." — since the API only returns `conflicting_schedule_id`/`class_id` (no names), the message shown is simplified to `"Lịch trùng với một lịch khác (mã lớp " + warning.class_id + ")."` (document this simplification in a code comment; full class-name lookup is out of scope here, YAGNI — AD-11 already shows real conflicts with names from richer data). Edit form (line 431): giờ bắt đầu, thời lượng, các thứ, ngày kết thúc, lý do bắt buộc.

- [ ] **Step 1: Write the failing test.** Cases:
  - Create flow: fill required fields, toggle `repeat_weekly` on, select 2 weekday chips, submit; assert the `POST` body sent to MSW has `weekdays: [2,4]` (or whichever picked) and `repeat_weekly: true`; assert success state shows "Đã tạo lịch và sinh 4 buổi cho 4 tuần tới." when the mocked response returns a session count — wait, the API response has no `count` field (only `schedule` + `warnings`); **read `admin.md` line 430 again**: the "số buổi tạo" comes from the response, but the pinned `CreatedScheduleResource` shape in Task 1 has no count field. Resolve this by NOT showing a session count (BE doesn't return one) — show only "Đã tạo lịch." plus any warnings. Write the test to assert that simpler message.
  - Validation: `repeat_weekly=true` with no weekday selected shows "Vui lòng chọn ít nhất một thứ." and does not submit.
  - Create flow with `repeat_weekly` left off (Switch unchecked, the default): submit with only `effective_from`/`local_start_time`/`duration_minutes` filled; assert the `POST` body has `repeat_weekly: false` and no `weekdays` key (or an empty array — match whatever the implementation naturally produces, just assert it submits successfully without requiring a weekday pick) — pins Review Focus item 1 (BE still generates one session at `effective_from` for this case; the FE's only job is to not block or require weekdays here).
  - Edit flow: dialog pre-fills from `schedule` prop, submit without `reason` shows "Vui lòng nhập lý do." (zod error), fill reason, submit; assert `PATCH` body includes `reason`.
- [ ] **Step 2: Run test, verify FAIL.**
- [ ] **Step 3: Implement `schedule-form-dialog.tsx`** with `useForm` + `zodResolver`, switching schema by `mode`. Reuse `Input`, `Switch`, `Button`, `Dialog` primitives.
- [ ] **Step 4: Wire "Tạo lịch"/"Sửa" buttons into `class-schedule-tab.tsx`** with local `useState` for dialog open/mode/target schedule.
- [ ] **Step 5: Run tests, verify PASS.**
- [ ] **Step 6: Commit** — `feat: add create/edit schedule dialog (AD-10d)`.

---

## Task 4: ScheduleCancelDialog

**Files:**
- Create: `src/features/lesson/ui/admin/schedule-cancel-dialog.tsx`
- Modify: `src/features/lesson/ui/admin/class-schedule-tab.tsx` (wire "Hủy lịch" button)
- Test: `src/features/lesson/ui/admin/schedule-cancel-dialog.test.tsx`

**Interfaces:**
- Consumes: `useCancelSchedule` from Task 1.
- Produces: `ScheduleCancelDialog({ open, classId, schedule, onClose }: { open: boolean; classId: string; schedule: Schedule | null; onClose: () => void })`.

Mockup/spec reference: admin.md line 432 — M4, lý do bắt buộc (`cancel_reason`), copy: "Buổi đã sinh không bị xóa: nhắc hủy riêng từng buổi ở tab Buổi học nếu cần."

- [ ] **Step 1: Write the failing test** — submit without reason shows "Vui lòng nhập lý do." and blocks; fill reason, submit, assert `POST /schedules/{id}/cancel` body `{ cancel_reason }` sent and dialog closes on success.
- [ ] **Step 2: Run test, verify FAIL.**
- [ ] **Step 3: Implement `schedule-cancel-dialog.tsx`** (manual required-reason check, same style as `ClassMemberEditPeriodDialog`, no zod needed for a single field).
- [ ] **Step 4: Wire into `class-schedule-tab.tsx`.**
- [ ] **Step 5: Run tests, verify PASS.**
- [ ] **Step 6: Commit** — `feat: add cancel schedule dialog (AD-10d)`.

---

## Task 5: Lesson API layer — LessonSession

**Files:**
- Create: `src/features/lesson/api/lesson-sessions.ts`
- Modify: `src/features/lesson/api/types.ts` (add LessonSession types)
- Modify: `src/features/lesson/api/keys.ts` (add session keys)
- Modify: `src/features/lesson/api/schemas.ts` (add session form schemas)
- Test: `src/features/lesson/api/lesson-sessions.test.tsx`

**Interfaces:**
- Produces (types): `LessonSessionStatus = "scheduled" | "completed" | "cancelled"`; `LessonSessionListItem { id, starts_at: string, ends_at: string, status: LessonSessionStatus, review_required: boolean }`; `LessonSessionDetail extends LessonSessionListItem { class_id: string, lesson_content: string | null, notes: string | null }`; `CreateLessonSessionInput { starts_at: string, ends_at: string, lesson_content?: string | null, notes?: string | null }`; `CreatedLessonSession { id, class_id, starts_at, ends_at, status: LessonSessionStatus }`; `UpdateLessonSessionInput { starts_at?: string, ends_at?: string, lesson_content?: string | null, notes?: string | null, reason?: string }`; `UpdatedLessonSession { id, status: LessonSessionStatus, notes: string | null }`.
- Produces (keys): `lessonKeys.sessions(classId: string, from?: string, until?: string)`, `lessonKeys.session(id: string)`.
- Produces (`lesson-sessions.ts`): `useLessonSessions(classId: string, range: { from?: string; until?: string })`, `useLessonSession(id: string)` (detail, enabled only when `id` truthy), `useCreateLessonSession(classId: string)`, `useUpdateLessonSession(classId: string)` (mutate `{ sessionId, input }`), `useCancelLessonSession(classId: string)` (mutate `{ sessionId, cancellation_reason }`), `useCompleteLessonSession(classId: string)` (mutate `sessionId`).
- Produces (schemas): `createSessionFormSchema`/`CreateSessionFormValues` (`date`, `start_time`, `end_time`, `lesson_content`, `notes`); `editSessionFormSchema`/`EditSessionFormValues` (same fields, `reason` optional string — requiredness enforced in the dialog component per session status, not in the static schema, matching `ClassMemberEditPeriodDialog`'s manual-check style since it depends on a prop not a form field).
- Consumes: none new (independent of Task 1-4's Schedule layer, same feature).

Exact BE shapes:
- `GET /classes/{id}/lesson-sessions?from=&until=` → `{ data: LessonSessionListItem[] }`.
- `GET /lesson-sessions/{id}` → `{ lesson_session: LessonSessionDetail }`.
- `POST /classes/{id}/lesson-sessions` body `CreateLessonSessionInput` → 201 `{ lesson_session: CreatedLessonSession }`.
- `PATCH /lesson-sessions/{id}` body `UpdateLessonSessionInput` → `{ lesson_session: UpdatedLessonSession }`.
- `POST /lesson-sessions/{id}/cancel` body `{ cancellation_reason: string }` → 204.
- `POST /lesson-sessions/{id}/complete` → `{ lesson_session: { id, status } }`.

- [ ] **Step 1: Write the failing test** — same `renderHook` + MSW pattern as Task 1. Cover: list with `from`/`until` query params reaching the right URL; detail fetch; create (assert body shape sent); update (assert `reason` included when passed); cancel (204 → `isSuccess`); complete.
- [ ] **Step 2: Run test, verify FAIL.**
- [ ] **Step 3: Implement `lesson-sessions.ts`** mirroring `schedules.ts`'s invalidation pattern, invalidating both `lessonKeys.sessions(classId, ...)` (all ranges — use a predicate match on the query key prefix, `queryClient.invalidateQueries({ queryKey: ["lesson", "sessions", classId] })`) and `lessonKeys.session(id)` on mutate success.
- [ ] **Step 4: Add types/keys/schemas** per Interfaces block.
- [ ] **Step 5: Run tests, verify PASS.**
- [ ] **Step 6: Commit** — `feat: add Lesson feature's LessonSession API layer`.

---

## Task 6: ClassSessionsTab (AD-10e list) wired into class detail, with highlight support

**Files:**
- Create: `src/features/lesson/ui/admin/class-sessions-tab.tsx`
- Modify: `src/features/academic/ui/admin/class-detail-screen.tsx` (swap placeholder; read `session`/`month` search params; pass down)
- Delete: `src/features/academic/ui/admin/class-sessions-placeholder-tab.tsx`
- Modify: `src/features/lesson/index.ts`
- Test: `src/features/lesson/ui/admin/class-sessions-tab.test.tsx`

**Interfaces:**
- Consumes: `useLessonSessions` from Task 5.
- Produces: `ClassSessionsTab({ classId, highlightSessionId, initialMonth }: { classId: string; highlightSessionId?: string; initialMonth?: string })`. `initialMonth` is `"YYYY-MM"`; defaults to the current month (`AppTime`-safe: use `formatDate`/local `Date` via a small `lib/time.ts`-routed helper — do not call `new Date()` raw for "today", read how other tabs get "current month" if any exist, otherwise add a tiny `currentYearMonth()` helper in this file using `Intl`/`Date` consistent with F8 by routing through `lib/time.ts`'s existing exports only for display, and plain `Date` for the *default* month value since there is no business "today" concept at risk here — pin: implementer may use `new Date()` for the default-month-on-mount only, never for displayed text).

Mockup reference: lines 694-721. Header: month nav (`chevron-left`/`chevron-right` `IconButton`, "Tháng này" `Button variant="ghost"`), "Thêm buổi lẻ / bù" button. Row: date+time column (width ~128px), status `Badge` + "Cần rà soát điểm danh" badge + `lesson_content` text (closest available field: list response has no `lesson_content`, only detail does — mockup's `topic` field in the row list actually needs the detail view; **since list response (`LessonSessionListItem`) has no content field, show only date/time/status/review-flag in the row and drop the inline content snippet** — this is a deliberate deviation from the mockup documented here, not silent; fetching detail per-row would be N+1). Buttons per row: ~~Điểm danh~~ (removed per user decision), Sửa (`canEdit` always true for non-cancelled rows — mirror mockup's `canEdit:!ro`), Hoàn tất (only when `status === "scheduled"`), Hủy (only when `status !== "cancelled"`). Empty state: "Chưa có buổi nào trong khoảng này" / "Thêm buổi lẻ hoặc tạo lịch ở tab Lịch."

Highlight behavior: when `highlightSessionId` is set, the matching row gets `className` background `bg-[var(--blue-50)]` (mockup line 707's `q.bg` pattern) and the tab scrolls it into view on mount (`useEffect` + `ref.scrollIntoView({ block: "center" })`).

- [ ] **Step 1: Write the failing test.** Cases:
  - Renders rows for the active month, with date/time/status.
  - A row with `review_required: true` shows "Cần rà soát điểm danh".
  - Passing `highlightSessionId` matching a rendered row applies the highlight class (assert via `toHaveClass` or style check, not via scroll — `scrollIntoView` is not implemented in jsdom, so stub it: `Element.prototype.scrollIntoView = vi.fn()` in the test file's top-level setup).
  - Empty state renders the "Chưa có buổi nào..." copy.
- [ ] **Step 2: Run test, verify FAIL.**
- [ ] **Step 3: Implement `class-sessions-tab.tsx`** with internal `month` state (`useState(initialMonth ?? currentYearMonth())`), deriving `from`/`until` as the first/last day of that month for `useLessonSessions`.
- [ ] **Step 4: Wire into `class-detail-screen.tsx`**: read `searchParams.get("session")` and `searchParams.get("month")`, pass as `highlightSessionId`/`initialMonth` to `ClassSessionsTab`; also make sure `tab` state already read from `searchParams.get("tab")` (it already is, per existing code) switches to `"sessions"` when `session` param is present even if `tab` param is absent (so a bare `?session=X` link still lands on the right tab) — add this as a one-line fallback in the existing `useState` initializer.
- [ ] **Step 5: Delete the old placeholder file, remove its import, export new tab from `index.ts`.**
- [ ] **Step 6: Run tests, verify PASS**, including `class-detail-screen.test.tsx`.
- [ ] **Step 7: Commit** — `feat: show real session list in AD-10e tab, support deep-link highlight`.

---

## Task 7: SessionFormDialog (create + edit)

**Files:**
- Create: `src/features/lesson/ui/admin/session-form-dialog.tsx`
- Modify: `src/features/lesson/ui/admin/class-sessions-tab.tsx` (wire "Thêm buổi lẻ / bù" and "Sửa" buttons)
- Test: `src/features/lesson/ui/admin/session-form-dialog.test.tsx`

**Interfaces:**
- Consumes: `useCreateLessonSession`, `useUpdateLessonSession`, `createSessionFormSchema`, `editSessionFormSchema` from Task 5.
- Produces: `SessionFormDialog({ open, classId, mode, session, onClose }: { open: boolean; classId: string; mode: "create" | "edit"; session: LessonSessionDetail | null; onClose: () => void })`. For `mode === "edit"`, the parent must have already fetched the session's detail (via `useLessonSession`) before opening this dialog, since `lesson_content`/`notes` aren't on the list item — the parent passes that detail object in as `session`.

Mockup reference: admin.md line 442-443. Fields: ngày*, giờ bắt đầu*, giờ kết thâu* (sau giờ bắt đầu), nội dung, ghi chú; lý do bắt buộc khi buổi đã `completed` (edit only). Combine `date` + `start_time`/`end_time` into `starts_at`/`ends_at` as `"{date}T{time}:00"` strings before calling the mutation (no timezone offset ⇒ BE interprets as VN time per `DEC-SCH-003`).

- [ ] **Step 1: Write the failing test.** Cases:
  - Create: fill date/start/end/content, submit; assert `POST` body has `starts_at`/`ends_at` combined correctly and `ends_at > starts_at` client validation blocks submit when end ≤ start ("Giờ kết thúc phải sau giờ bắt đầu.").
  - Edit, `session.status === "scheduled"`: reason field not marked required, submit without reason succeeds.
  - Edit, `session.status === "completed"`: submitting without reason shows "Vui lòng nhập lý do." and blocks.
- [ ] **Step 2: Run test, verify FAIL.**
- [ ] **Step 3: Implement `session-form-dialog.tsx`.**
- [ ] **Step 4: Wire into `class-sessions-tab.tsx`** — "Sửa" button triggers `useLessonSession(id)` lazily (only fetch detail when a row's edit is clicked; simplest approach: on click, set `editingId`, render `SessionFormDialog` conditionally once `useLessonSession(editingId)` resolves, showing a small loading state in the dialog's place if not yet resolved — document this as the implementer's call, a spinner or disabled dialog body both acceptable).
- [ ] **Step 5: Run tests, verify PASS.**
- [ ] **Step 6: Commit** — `feat: add create/edit lesson session dialog (AD-10e)`.

---

## Task 8: SessionCancelDialog + SessionCompleteDialog

**Files:**
- Create: `src/features/lesson/ui/admin/session-cancel-dialog.tsx`
- Create: `src/features/lesson/ui/admin/session-complete-dialog.tsx`
- Modify: `src/features/lesson/ui/admin/class-sessions-tab.tsx` (wire "Hủy"/"Hoàn tất" buttons)
- Test: `src/features/lesson/ui/admin/session-cancel-dialog.test.tsx`
- Test: `src/features/lesson/ui/admin/session-complete-dialog.test.tsx`

**Interfaces:**
- Consumes: `useCancelLessonSession`, `useCompleteLessonSession` from Task 5.
- Produces: `SessionCancelDialog({ open, classId, session, onClose }: { open: boolean; classId: string; session: LessonSessionListItem | null; onClose: () => void })`; `SessionCompleteDialog({ open, classId, session, onClose }: { open: boolean; classId: string; session: LessonSessionListItem | null; onClose: () => void })` (simple M4 confirm, no reason field — admin.md line 445 "xác nhận đơn giản").

- [ ] **Step 1: Write the failing tests** for both dialogs (cancel requires reason; complete is a one-button confirm that calls `POST /lesson-sessions/{id}/complete` on confirm).
- [ ] **Step 2: Run tests, verify FAIL.**
- [ ] **Step 3: Implement both dialogs.**
- [ ] **Step 4: Wire into `class-sessions-tab.tsx`.**
- [ ] **Step 5: Run tests, verify PASS.**
- [ ] **Step 6: Commit** — `feat: add cancel/complete session dialogs (AD-10e)`.

---

## Task 9: Lesson API layer — MyLessonSessions + teacher-conflict helper

**Files:**
- Create: `src/features/lesson/api/my-lesson-sessions.ts`
- Modify: `src/features/lesson/api/types.ts` (add `MyLessonSession`, `MyLessonSessionFilters`)
- Modify: `src/features/lesson/api/keys.ts`
- Create: `src/features/lesson/api/conflicts.ts`
- Test: `src/features/lesson/api/my-lesson-sessions.test.tsx`
- Test: `src/features/lesson/api/conflicts.test.ts`

**Interfaces:**
- Produces (types): `MyLessonSessionFilters { from?: string; until?: string; class_id?: string; teacher_id?: string; subject_id?: string; hide_cancelled?: boolean }`; `MyLessonSession { id, starts_at: string, ends_at: string, status: LessonSessionStatus, review_required: boolean, class_id: string, class_name: string | null, subject_name: string | null, teacher_names: string[] }`.
- Produces (keys): `lessonKeys.mySessions(filters: MyLessonSessionFilters)`.
- Produces (`my-lesson-sessions.ts`): `useMyLessonSessions(filters: MyLessonSessionFilters)` — builds the query string from all present filter keys, `GET /me/lesson-sessions`.
- Produces (`conflicts.ts`): `detectTeacherConflicts(sessions: MyLessonSession[]): Set<string>` — pure function, no React/DOM. Returns the set of session `id`s that overlap in time (`starts_at < other.ends_at && other.starts_at < ends_at`) with another session sharing at least one teacher name (`teacher_names` intersection non-empty), excluding comparing a session against itself and excluding `cancelled` sessions from the comparison (a cancelled session cannot conflict nor be flagged).

Exact BE shape: `GET /me/lesson-sessions?from&until&class_id&teacher_id&subject_id&hide_cancelled` → `{ data: MyLessonSession[] }` (`MyLessonSessionResource`, already shipped in `piclass-api` branch `feat/my-lesson-sessions-endpoint`).

- [ ] **Step 1: Write the failing test for `my-lesson-sessions.test.tsx`** — assert the request URL includes all filters when provided, and omits absent ones; assert `data` mapped through untouched.
- [ ] **Step 2: Write the failing test for `conflicts.test.ts` (plain Vitest, no MSW/DOM)** covering the Review Focus cases:
  - Two sessions, same teacher, overlapping time → both ids in the returned set.
  - Two sessions, same teacher, non-overlapping time → neither id in the set.
  - Two sessions, overlapping time, no shared teacher → neither id in the set.
  - A cancelled session overlapping a scheduled one with the same teacher → neither flagged.
- [ ] **Step 3: Run both tests, verify FAIL.**
- [ ] **Step 4: Implement `my-lesson-sessions.ts` and `conflicts.ts`.**
- [ ] **Step 5: Run tests, verify PASS.**
- [ ] **Step 6: Commit** — `feat: add GET /me/lesson-sessions hook and teacher-conflict detector`.

---

## Task 10: AD-11 screen shell, filters, and Week view

**Files:**
- Create: `src/app/(portal)/admin/schedule/page.tsx`
- Modify: `src/config/nav.ts` (add `{ label: "Lịch", href: "/admin/schedule", icon: "calendar-days" }` to the `admin` array, after "Lớp học")
- Create: `src/features/lesson/ui/admin/my-schedule-screen.tsx`
- Create: `src/features/lesson/ui/admin/my-schedule-week-view.tsx`
- Modify: `src/features/lesson/index.ts` (export `MyScheduleScreen`)
- Test: `src/features/lesson/ui/admin/my-schedule-screen.test.tsx`

**Interfaces:**
- Consumes: `useMyLessonSessions`, `detectTeacherConflicts` from Task 9; `useClasses`/`useSubjects` (from `academic` feature, already exported) for the lớp/môn filter options; a teacher-options source — **check `user-management`'s exports for an existing `useTeachers`-style list hook before adding a new one; if one already exists, reuse it via `@/features/user-management`, do not duplicate.**
- Produces: `MyScheduleScreen()` — owns `view` (`"day" | "week" | "month"`), `anchor` (an ISO date string, default today), and the 4 filter states (`classId`, `teacherId`, `subjectId`, `hideCancelled`), computing `from`/`until` for the API call from `view`+`anchor` (week: Monday-Sunday of the week containing `anchor`; day: that single day; month: first-last day of `anchor`'s month — Task 11 adds the Day/Month rendering, this task only needs the `from`/`until` math to be shared-ready). Renders `MyScheduleWeekView` when `view === "week"`; Day/Month are stubbed as `null` for now (replaced in Task 11 — do not leave a broken view state, show "Đang phát triển" placeholders in-tree so the screen never crashes if a filter/view combination is reached mid-task, replaced before Task 11 commits).
- Produces: `MyScheduleWeekView({ sessions, conflictIds, weekStart }: { sessions: MyLessonSession[]; conflictIds: Set<string>; weekStart: string })` — 7-column grid (mockup lines 758-767, CSS `.cal-week`), each day column showing that day's sessions as small cards (time, class name, teacher names joined, status badge, "Cần rà soát" badge if `review_required`, "▲ Trùng giờ" badge if the session's id is in `conflictIds`), each card links to `/admin/classes/{class_id}?tab=sessions&session={id}&month={YYYY-MM of starts_at}`.

Mockup reference: header/nav/filters lines 728-750 (title "Lịch tổng hợp", subtitle "Toàn trung tâm · chỉ xem và điều hướng, thao tác buổi học ở trang lớp", prev/next `IconButton`, "Hôm nay" `Button variant="outline" size="sm"`, view `Tabs variant="pill"` with `{value:"day",label:"Ngày"},{value:"week",label:"Tuần"},{value:"month",label:"Tháng"}`, filter row with 3 `Select variant="chip"` (lớp/giáo viên/môn, each with a leading "Mọi ..." option) + `Switch label="Ẩn buổi đã hủy"`, legend row line 750 — 4 color dots + the "Chưa kiểm tra trùng phòng học" caption kept per user decision, exact copy and colors: `●` blue-600 "Sắp diễn ra", `●` att-present "Đã hoàn tất", `●` danger "Đã hủy", `▲` warning "Giáo viên trùng giờ (chỉ để biết, không chặn)", plain text "Chưa kiểm tra trùng phòng học"). Week CSS: `.cal-week{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:10px}` collapsing to 4 cols <1100px, 1 col <760px (lines 46-50) — port these exact breakpoints as Tailwind arbitrary-variant classes or a small `<style jsx>`-free utility class added to `globals.css` under `@layer base` if Tailwind's default grid utilities can't express the 3-breakpoint collapse directly (check first; `grid-cols-7 max-[1100px]:grid-cols-4 max-[760px]:grid-cols-1` may already cover it without a new CSS rule).

- [ ] **Step 1: Write the failing test** for `my-schedule-screen.test.tsx`. Cases:
  - Renders with default view "Tuần", calls `GET /me/lesson-sessions` with `from`/`until` spanning the current week (Monday-Sunday).
  - Changing the lớp filter re-fetches with `class_id` in the query string while keeping previously-set `teacher_id` (Review Focus item 4) — set teacher filter first, then class filter, assert the second request URL has both params.
  - A session whose `teacher_names` overlaps another same-time session shows the "▲ Trùng giờ" badge; a non-conflicting one does not.
  - Legend renders all 4 dots plus the room-conflict caption text.
- [ ] **Step 2: Run test, verify FAIL.**
- [ ] **Step 3: Implement `my-schedule-week-view.tsx` and `my-schedule-screen.tsx`.**
- [ ] **Step 4: Add the nav entry and the route `page.tsx`** (`export default function AdminSchedulePage() { return <MyScheduleScreen />; }`).
- [ ] **Step 5: Export `MyScheduleScreen` from `index.ts`.**
- [ ] **Step 6: Run tests, verify PASS.**
- [ ] **Step 7: Commit** — `feat: add AD-11 schedule screen shell with week view`.

---

## Task 11: AD-11 Day view and Month view

**Files:**
- Create: `src/features/lesson/ui/admin/my-schedule-day-view.tsx`
- Create: `src/features/lesson/ui/admin/my-schedule-month-view.tsx`
- Modify: `src/features/lesson/ui/admin/my-schedule-screen.tsx` (replace the Day/Month stubs with real rendering)
- Test: `src/features/lesson/ui/admin/my-schedule-day-view.test.tsx`
- Test: `src/features/lesson/ui/admin/my-schedule-month-view.test.tsx`

**Interfaces:**
- Consumes: same `sessions`/`conflictIds` shape as `MyScheduleWeekView` (Task 10), plus `anchor` (day view) or the month's cells.
- Produces: `MyScheduleDayView({ sessions, conflictIds, day }: { sessions: MyLessonSession[]; conflictIds: Set<string>; day: string })` — full-width cards (mockup lines 771-779); `MyScheduleMonthView({ sessions, conflictIds, month }: { sessions: MyLessonSession[]; conflictIds: Set<string>; month: string })` — `.cal-month` grid of day cells, each showing up to 3 session lines + "+N buổi" overflow (mockup lines 785-793), clicking a cell switches the parent to Day view at that date (callback prop `onPickDay: (day: string) => void`).

- [ ] **Step 1: Write the failing tests.** Day view: renders one full-width card per session for that day, empty state "Không có buổi học trong ngày này." Month view: a day with 4 sessions shows 3 + "+1 buổi"; clicking a day cell calls `onPickDay` with that date; empty month shows "Không có buổi học trong tháng này."
- [ ] **Step 2: Run tests, verify FAIL.**
- [ ] **Step 3: Implement both view components.**
- [ ] **Step 4: Wire into `my-schedule-screen.tsx`**, including `onPickDay` switching `view` to `"day"` and updating `anchor`.
- [ ] **Step 5: Run tests, verify PASS.**
- [ ] **Step 6: Commit** — `feat: add AD-11 day and month views`.

---

## Task 12: "Thêm buổi" dialog and session-click deep link

**Files:**
- Create: `src/features/lesson/ui/admin/schedule-add-session-dialog.tsx`
- Modify: `src/features/lesson/ui/admin/my-schedule-screen.tsx` (wire the "Thêm buổi" header button)
- Test: `src/features/lesson/ui/admin/schedule-add-session-dialog.test.tsx`

**Interfaces:**
- Consumes: `useClasses` (from `academic`, already exported).
- Produces: `ScheduleAddSessionDialog({ open, onClose }: { open: boolean; onClose: () => void })` — a single `Select` of active (non-archived) classes + "Chọn lớp" placeholder, a "Vui lòng chọn lớp." error if submitted empty, and on confirm navigates (via `next/navigation`'s `useRouter().push`) to `/admin/classes/{classId}?tab=sessions&openCreate=1` and closes.

Mockup reference: lines 734 (header button), `calAddGo` (line 2089) — mockup navigates straight into the class's session form; this plan's FE equivalent opens the class detail page with `tab=sessions` and a `openCreate=1` marker. **Modify `ClassSessionsTab` (Task 6/7's file) to read `searchParams.get("openCreate")` via the same prop-drilling path already used for `session`/`month`** (`class-detail-screen.tsx` passes an `openCreateOnMount?: boolean` prop) and auto-open `SessionFormDialog` in create mode on mount when true — this is a small addition to `class-detail-screen.tsx` and `class-sessions-tab.tsx`, listed here because it only matters once this dialog exists.

- [ ] **Step 1: Write the failing test** for the dialog (pick a class, confirm, assert `router.push` called with the right URL — mock `next/navigation`'s `useRouter` per existing test conventions in the repo, check `teacher-detail-screen.test.tsx` or similar for how navigation mocks are already set up before inventing a new pattern) and for the auto-open behavior (`class-sessions-tab.test.tsx`: passing `autoOpenCreate` renders `SessionFormDialog` already open).
- [ ] **Step 2: Run tests, verify FAIL.**
- [ ] **Step 3: Implement `schedule-add-session-dialog.tsx`.**
- [ ] **Step 4: Wire the header button in `my-schedule-screen.tsx`.**
- [ ] **Step 5: Implement the `openCreate` passthrough** in `class-detail-screen.tsx` → `class-sessions-tab.tsx`.
- [ ] **Step 6: Run the full `lesson` + touched `academic` test files, verify PASS.**
- [ ] **Step 7: Run `pnpm lint && pnpm typecheck && pnpm test`** for the whole repo — expected all green, zero new warnings.
- [ ] **Step 8: Self-check against mockup** per `piclass-web/CLAUDE.md`'s rule: re-open the 3 mockup sections (677-692, 694-721, 726-796) side by side with the final JSX of `class-schedule-tab.tsx`, `class-sessions-tab.tsx`, `my-schedule-*-view.tsx` and confirm copy/structure match (documented deviations: no Điểm danh button, no per-row lesson_content snippet, no room-conflict checking, simplified conflict-warning text in the create-schedule result).
- [ ] **Step 9: Commit** — `feat: add AD-11 add-session dialog and deep-link auto-open`.
- [ ] **Step 10: Update docs** — mark nhóm 4 FE done in `docs/trang-thai-ngan.md`, `docs/product/screens/mockup-plan.md` (close the "[~] 4." line), note the documented deviations above in `docs/product/screens/api-gaps.md` or `admin.md` as appropriate.
