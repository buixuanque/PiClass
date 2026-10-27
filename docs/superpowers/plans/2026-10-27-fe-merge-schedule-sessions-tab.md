# FE: Gộp tab Lịch + Buổi học thành "Lịch học" (ARCH-37) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thay 2 tab `ClassScheduleTab` (AD-10d) + `ClassSessionsTab` (AD-10e) trong `ClassDetailScreen` bằng một tab "Lịch học" duy nhất, hiển thị dạng calendar (tái dùng component AD-11), có filter giáo viên/trạng thái/rà soát, dialog hỏi phạm vi (chỉ buổi này / và từ nay trở đi) trước khi sửa buổi thuộc lịch lặp, nối "và từ nay trở đi" sang API `update-series` đã có ở BE.

**Architecture:** Genericize 3 component view Ngày/Tuần/Tháng của AD-11 để nhận một type buổi học hẹp dùng chung (`CalendarSessionItem`) cùng hành vi click linh hoạt (điều hướng cho AD-11, mở panel tại chỗ cho tab lớp); dựng tab mới quanh các view đã genericize, cộng một dialog chi tiết buổi + 2 dialog mới cho luồng sửa theo phạm vi; xóa hẳn lối vào sửa/hủy `Schedule` không qua một buổi cụ thể (nguồn gốc bug ban đầu).

**Tech Stack:** Next.js 16, React 19, TypeScript 6, TanStack Query, React Hook Form + Zod, Vitest + Testing Library + MSW.

**Spec:** `docs/superpowers/specs/2026-10-27-merge-schedule-sessions-tab-design.md` (đọc trước, đặc biệt các mục "Quyết định (builder-check hỏi, Claude tự quyết)" — đã chốt, không hỏi lại).

## Global Constraints

- Không đổi API hủy hiện có (`POST /lesson-sessions/{id}/cancel`, `POST .../cancel-series`) — `SessionCancelDialog`, `SessionCompleteDialog` giữ nguyên, tái dùng thẳng.
- Giữ nguyên giá trị nội bộ `tab=sessions` trong `ClassDetailScreen` (chỉ là key, không hiển thị) để không phá đường dẫn sâu từ AD-11 (`/admin/classes/{id}?tab=sessions&openCreate=1`, `...&session=&month=`).
- Đúng 2 lựa chọn khi sửa buổi thuộc lịch lặp — không có lựa chọn thứ 3 "tất cả": **"Chỉ buổi này"** / **"Buổi này và từ nay trở đi"**. Buổi lẻ (`schedule_id === null`) không bao giờ hỏi phạm vi.
- Dialog hỏi phạm vi hiện ra **bất kể trạng thái của buổi neo** (`scheduled`/`completed`/`cancelled`) — cùng tiền lệ `SessionCancelDialog`/`cancel-series` đã có, không thêm điều kiện theo trạng thái.
- Bỏ hẳn switch riêng "Ẩn buổi đã hủy" — chỉ dùng Select lọc trạng thái (mặc định "Tất cả", đã tự hiện buổi cần rà soát vì `review_required=true` chỉ xảy ra trên buổi `cancelled`).
- Lọc giáo viên chỉ gồm giáo viên **đang** phân công cho lớp (`valid_to === null` trong `ClassTeacherRosterItem`) — chấp nhận gap đã biết (GM-3/AD-10c), không mở rộng.
- **Quyết định (kết luận trực tiếp từ việc gộp 2 tab, không phải một dòng riêng trong spec nhưng là hệ quả bắt buộc):** bỏ hẳn lối vào sửa/hủy `Schedule` không qua một buổi cụ thể — đây chính là nguồn gốc bug ban đầu (`CancelSchedule` cũ không cascade `LessonSession`, trong khi sửa qua `PATCH /schedules/{id}` gần như vô tác dụng từ `DEC-SCH-004`). Xóa `ScheduleEditDialog`, `ScheduleCancelDialog`, `ScheduleFormDialog` (wrapper mode create/edit — chỉ còn cần "create"), cùng `useUpdateSchedule`/`useCancelSchedule`/`schedulesApi.update`/`schedulesApi.cancel`. `ScheduleCreateDialog` giữ nguyên, dùng trực tiếp (không qua wrapper). BE API `PATCH /schedules/{id}` và `POST /schedules/{id}/cancel` vẫn tồn tại ở BE (không đổi BE) — chỉ không còn lối gọi từ FE.
- F1–F8 (`piclass-web/CLAUDE.md`): route mỏng, chỉ `lib/api-client.ts` gọi `fetch`, `features/*/api/` không biết giao diện, `ui/<vai-trò>/` không import `ui/<vai-trò khác>`, mọi thời điểm hiển thị qua `lib/time.ts`. Màu/spacing/radius chỉ dùng token sẵn có — không tự thêm.
- Ngôn ngữ: chữ hiển thị tiếng Việt gõ thẳng trong component; comment tiếng Việt; tên biến/hàm/component/test tiếng Anh; PR title/description tiếng Việt (không tạo PR trong plan này — chỉ commit vào branch, theo quy trình push ở `CLAUDE.md`).
- Nhánh làm việc: tạo từ `origin/main` mới nhất của `piclass-web` (không phải từ `docs/web-openapi-first`, nhánh đó đã có PR riêng, chưa merge). Không tự push/PR/merge.
- Cập nhật `docs/product/screens/admin.md` (giữ mã màn AD-10d, xóa nội dung AD-10e để lại dòng trỏ sang AD-10d) **không nằm trong plan này** — file đó ở repo docs khác, controller phiên làm trực tiếp sau khi FE xong, không giao cho implementer.

## Review Focus

1. Đường dẫn sâu cũ từ AD-11 (`?tab=sessions&session=&month=&openCreate=1`) phải vẫn mở đúng buổi/tháng/form tạo sau khi gộp — spec không nói rõ "nếu cả `session` và `openCreate` cùng có" nhưng thực tế không xảy ra cùng lúc (ScheduleAddSessionDialog chỉ gửi `openCreate`, Day/Week/Month view AD-11 chỉ gửi `session`+`month`).
2. Dialog hỏi phạm vi phải hiện cho buổi `completed`/`cancelled` thuộc lịch lặp, không chỉ `scheduled` — dễ bị lập trình viên thêm nhầm điều kiện "chỉ hỏi khi `scheduled`" theo trực giác.
3. Mặc định Select trạng thái "Tất cả" phải hiện buổi cần rà soát điểm danh — không có switch nào khác vô tình ẩn mất.
4. Buổi lẻ (`schedule_id = null`) bấm "Sửa" phải vào thẳng form sửa hiện có, không hiện dialog hỏi phạm vi.
5. "Buổi này và từ nay trở đi" phải prefill đúng giá trị hiện có của `Schedule` (giống `ScheduleEditDialog` cũ) — đặc biệt `effective_until` vẫn bắt buộc theo rule cũ, không được gửi rỗng khi user không đổi field này.

---

## Task 1: Genericize 3 component view AD-11 (Day/Week/Month) + `CalendarSessionItem`

**Files:**
- Modify: `src/features/lesson/api/types.ts` (thêm `CalendarSessionItem`)
- Modify: `src/features/lesson/ui/admin/my-schedule-day-view.tsx`
- Modify: `src/features/lesson/ui/admin/my-schedule-week-view.tsx`
- Modify: `src/features/lesson/ui/admin/my-schedule-month-view.tsx`
- Modify: `src/features/lesson/ui/admin/my-schedule-screen.tsx` (chỉ phần truyền prop vào 3 view, không đổi logic filter/data AD-11)
- Test: `src/features/lesson/ui/admin/my-schedule-day-view.test.tsx`, `my-schedule-week-view.test.tsx` (tạo mới — chưa có), `my-schedule-month-view.test.tsx`

**Interfaces:**
- Produces (dùng ở Task 5):
  ```ts
  export interface CalendarSessionItem {
    id: string;
    starts_at: string;
    ends_at: string;
    status: LessonSessionStatus;
    review_required: boolean;
  }
  ```
  `LessonSessionListItem` và `MyLessonSession` (đã có ở `types.ts`) đều thỏa cấu trúc này (superset) — không cần `extends` tường minh, TypeScript tự khớp theo structural typing.
- Produces — props mới của 3 view (generic `T extends CalendarSessionItem`), thay hẳn props cũ:
  ```ts
  // my-schedule-day-view.tsx, my-schedule-week-view.tsx
  {
    sessions: T[];
    conflictIds: Set<string>;
    day: string;            // chỉ DayView
    weekStart: string;      // chỉ WeekView
    onPickDay?: (day: string) => void; // chỉ WeekView, giữ nguyên
    renderLabel: (session: T) => { title: React.ReactNode; subtitle?: React.ReactNode };
    badgeExtra?: (session: T) => React.ReactNode;
    getHref?: (session: T) => string;     // dùng Link khi có
    onSelect?: (session: T) => void;      // dùng button onClick khi có (thay cho getHref)
  }
  // my-schedule-month-view.tsx
  {
    sessions: T[];
    conflictIds: Set<string>;
    month: string;
    onPickDay: (day: string) => void;
    renderCompactLabel: (session: T) => React.ReactNode; // 1 dòng sau giờ, thay `session.class_name` cứng cũ
    getHref?: (session: T) => string;
    onSelect?: (session: T) => void;
  }
  ```
  Đúng một trong `getHref`/`onSelect` được truyền ở mỗi nơi gọi — component render `<Link href={getHref(session)}>` khi có `getHref`, ngược lại `<button type="button" onClick={() => onSelect?.(session)}>` giữ cùng className/style hiện có (chỉ đổi thẻ bọc ngoài, không đổi layout bên trong).
- Consumes: không có (task nền).

- [ ] **Step 1: Viết test cho `MyScheduleWeekView` (chưa có file test) — hành vi hiện tại trước khi đổi props**

Tạo `my-schedule-week-view.test.tsx` giống cấu trúc `my-schedule-day-view.test.tsx` (session mẫu `MyLessonSession`, render với `weekStart`, assert hiện `class_name`/`teacher_names`, trạng thái rỗng khi `sessions=[]`). Dùng tạm props cũ (trước khi đổi ở Step 3) để có baseline.

- [ ] **Step 2: Chạy test mới, xác nhận PASS với props cũ**

Run: `pnpm test my-schedule-week-view.test.tsx` — Expected: PASS (ghi lại hành vi hiện tại trước khi refactor).

- [ ] **Step 3: Thêm `CalendarSessionItem` vào `types.ts`, sửa 3 view theo Interfaces block trên**

Giữ nguyên toàn bộ className/style/cấu trúc JSX hiện có trong mỗi view — chỉ đổi: (a) type `sessions` từ `MyLessonSession[]` sang generic `T[]`; (b) thay `session.class_name`/`session.teacher_names.join(", ")` cứng bằng gọi `renderLabel(session)`/`renderCompactLabel(session)`; (c) thẻ bọc click đổi theo `getHref`/`onSelect` như trên; (d) thêm render `badgeExtra?.(session)` cạnh các `Badge` hiện có (DayView, WeekView — MonthView không có khối badge, bỏ qua `badgeExtra` ở đây).

- [ ] **Step 4: Sửa `my-schedule-screen.tsx` để khớp props mới — hành vi AD-11 không đổi**

Di chuyển hàm `sessionLink` (hiện đang lặp lại trong `my-schedule-day-view.tsx` và `my-schedule-week-view.tsx`) lên `my-schedule-screen.tsx`, truyền xuống cả 3 view qua `getHref={sessionLink}`. Truyền `renderLabel={(s) => ({ title: s.class_name, subtitle: s.teacher_names.join(", ") })}` cho Day/Week, `renderCompactLabel={(s) => s.class_name}` cho Month. Không truyền `onSelect`/`badgeExtra` (AD-11 không cần).

- [ ] **Step 5: Chạy lại test Day/Week/Month view + test cũ của `MyScheduleScreen` (nếu có) — xác nhận không có test nào hỏng**

Run: `pnpm test my-schedule-day-view.test.tsx my-schedule-week-view.test.tsx my-schedule-month-view.test.tsx` — Expected: PASS, cùng assertion nội dung như trước khi refactor (Step 1-2 là baseline cho Week; Day/Month đã có test cũ, so khớp không đổi kết quả).

- [ ] **Step 6: Commit**

```bash
git add src/features/lesson/api/types.ts src/features/lesson/ui/admin/my-schedule-day-view.tsx src/features/lesson/ui/admin/my-schedule-week-view.tsx src/features/lesson/ui/admin/my-schedule-month-view.tsx src/features/lesson/ui/admin/my-schedule-screen.tsx src/features/lesson/ui/admin/my-schedule-week-view.test.tsx
git commit -m "refactor: genericize AD-11 calendar views for reuse by class-scoped tab"
```

---

## Task 2: Lớp dữ liệu — filter `GET lesson-sessions` + API `update-series`

**Files:**
- Modify: `src/features/lesson/api/types.ts` (thêm `ListLessonSessionsFilters`)
- Modify: `src/features/lesson/api/keys.ts` (đổi `sessions()` nhận filters object)
- Modify: `src/features/lesson/api/lesson-sessions.ts` (filter mới + hook `useUpdateScheduleAndFollowingSessions`)
- Modify: `src/features/lesson/api/schedules.ts` (export `invalidateScheduleAndSessions`; xóa `update`/`useUpdateSchedule`/`cancel`/`useCancelSchedule` — thuộc Global Constraints, xóa ở đây vì cùng file)
- Test: `src/features/lesson/api/lesson-sessions.test.tsx`, `src/features/lesson/api/schedules.test.tsx` (sửa, bỏ test của `update`/`cancel` đã xóa)

**Interfaces:**
- Consumes: không (API đã có ở BE, xem `piclass-api/storage/api-docs/openapi.json` — `GET /classes/{class}/lesson-sessions` có query `teacher_id` (uuid), `status` (`LessonSessionStatus`), `review_required` (boolean); `PATCH /lesson-sessions/{lessonSession}/update-series` nhận `UpdateScheduleAndFollowingSessionsRequest` (`local_start_time?`, `duration_minutes?`, `weekdays?`, `effective_until?`, `reason` bắt buộc) trả `UpdatedScheduleResource` = `{schedule: {id, local_start_time, duration_minutes, weekdays}}` — đúng khớp type `UpdateScheduleInput`/`UpdatedSchedule` đã có trong `types.ts`, không cần type mới cho input/output của API này).
- Produces (dùng ở Task 3, 5):
  ```ts
  export interface ListLessonSessionsFilters {
    from?: string;
    until?: string;
    teacher_id?: string;
    status?: LessonSessionStatus;
    review_required?: boolean;
  }
  ```
  `useLessonSessions(classId: string, filters: ListLessonSessionsFilters)` — thay cho `range: {from?, until?}` cũ (tương thích ngược, các field cũ vẫn optional).
  `useUpdateScheduleAndFollowingSessions(classId: string)` — `useMutation` nhận `{ sessionId: string; input: UpdateScheduleInput }`, gọi `PATCH /lesson-sessions/{sessionId}/update-series`, trả `UpdatedSchedule`, `onSuccess` gọi `invalidateScheduleAndSessions(queryClient, classId)` (export từ `schedules.ts`).
  `lessonKeys.sessions(classId: string, filters: ListLessonSessionsFilters)` trả `["lesson", "sessions", classId, filters] as const` (giữ nguyên tiền tố `["lesson","sessions",classId]` để các lệnh `invalidateQueries` hiện có vẫn khớp).

- [ ] **Step 1: Viết test cho `useLessonSessions` với 3 filter mới (MSW assert query string)**

Thêm test trong `lesson-sessions.test.tsx`: gọi `useLessonSessions("c1", { teacher_id: "t1", status: "scheduled", review_required: true })`, MSW handler đọc `request.url`, assert chứa `teacher_id=t1&status=scheduled&review_required=true` (decode URL trước khi assert, theo mẫu test `until=...23:59:59` đã có ở `class-sessions-tab.test.tsx`).

- [ ] **Step 2: Chạy test, xác nhận FAIL (filter chưa được truyền đi)**

Run: `pnpm test lesson-sessions.test.tsx` — Expected: FAIL, request không có 3 query mới.

- [ ] **Step 3: Thêm `ListLessonSessionsFilters` vào `types.ts`; sửa `lessonSessionsApi.list` + `useLessonSessions` nhận filters đầy đủ; sửa `lessonKeys.sessions`**

`apiClient.get<LessonSessionListItem[]>(`/classes/${classId}/lesson-sessions`, { query: filters })` — `apiClient` đã tự bỏ qua field `undefined` khi build query string (hành vi hiện có, xem `buildUrl` trong `lib/api-client.ts`).

- [ ] **Step 4: Chạy lại test Step 1 — xác nhận PASS**

Run: `pnpm test lesson-sessions.test.tsx` — Expected: PASS.

- [ ] **Step 5: Viết test cho `useUpdateScheduleAndFollowingSessions`**

MSW `patch(`${origin}/api/v1/lesson-sessions/q1/update-series`, ...)` trả `{schedule: {id: "sc1", local_start_time: "18:00:00", duration_minutes: 90, weekdays: [2,4]}}`; gọi mutation với `{sessionId: "q1", input: {effective_until: "2027-01-01", reason: "Dời giờ"}}`; assert `result.current.data` khớp schedule trả về; assert sau khi thành công, cache `lessonKeys.schedules("c1")` bị invalidate (theo mẫu test invalidate đã có trong `schedules.test.tsx` cho `useUpdateSchedule` cũ, trước khi bị xóa ở Step 7).

- [ ] **Step 6: Chạy test Step 5, xác nhận FAIL (hook chưa tồn tại)**

Run: `pnpm test lesson-sessions.test.tsx` — Expected: FAIL, "updateScheduleAndFollowingSessions is not a function" hoặc tương đương.

- [ ] **Step 7: Export `invalidateScheduleAndSessions` từ `schedules.ts`; xóa `update`/`useUpdateSchedule`/`cancel`/`useCancelSchedule` khỏi `schedules.ts` và test tương ứng khỏi `schedules.test.tsx`; thêm `updateSeries` + `useUpdateScheduleAndFollowingSessions` vào `lesson-sessions.ts`**

```ts
// schedules.ts
export function invalidateScheduleAndSessions(queryClient: ReturnType<typeof useQueryClient>, classId: string): void
```
(đổi từ hàm nội bộ `function` thành `export function`, giữ nguyên thân hàm). `schedulesApi` chỉ còn `list`/`create`. `useSchedules`/`useCreateSchedule` giữ nguyên.

```ts
// lesson-sessions.ts
updateSeries: (sessionId: string, input: UpdateScheduleInput) =>
  apiClient.patch<{ schedule: UpdatedSchedule }>(`/lesson-sessions/${sessionId}/update-series`, input).then((r) => r.schedule),

export function useUpdateScheduleAndFollowingSessions(classId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ sessionId, input }: { sessionId: string; input: UpdateScheduleInput }) =>
      lessonSessionsApi.updateSeries(sessionId, input),
    onSuccess: () => invalidateScheduleAndSessions(queryClient, classId),
  });
}
```

- [ ] **Step 8: Chạy toàn bộ test của `lesson-sessions.test.tsx` + `schedules.test.tsx` — xác nhận PASS, không còn test nào tham chiếu `update`/`cancel` đã xóa**

Run: `pnpm test lesson-sessions.test.tsx schedules.test.tsx` — Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add src/features/lesson/api/types.ts src/features/lesson/api/keys.ts src/features/lesson/api/lesson-sessions.ts src/features/lesson/api/schedules.ts src/features/lesson/api/lesson-sessions.test.tsx src/features/lesson/api/schedules.test.tsx
git commit -m "feat: add lesson-session list filters and update-series API call"
```

---

## Task 3: Dialog hỏi phạm vi + dialog sửa "và từ nay trở đi"

**Files:**
- Create: `src/features/lesson/ui/admin/session-edit-scope-dialog.tsx`
- Create: `src/features/lesson/ui/admin/schedule-series-edit-dialog.tsx`
- Delete: `src/features/lesson/ui/admin/schedule-edit-dialog.tsx`, `schedule-edit-dialog.test.tsx` (nếu có — kiểm tra trước khi xóa)
- Test: `src/features/lesson/ui/admin/session-edit-scope-dialog.test.tsx`, `schedule-series-edit-dialog.test.tsx`

**Interfaces:**
- Consumes: `useUpdateScheduleAndFollowingSessions` (Task 2); `editScheduleFormSchema`/`ScheduleEditFormValues`/`WEEKDAY_LABELS` (có sẵn, `api/schemas.ts`); `WeekdayChips` (có sẵn); `Schedule`/`LessonSessionDetail`/`UpdateScheduleInput` (`types.ts`).
- Produces (dùng ở Task 5):
  ```ts
  export function SessionEditScopeDialog({
    open, session, onClose, onPick,
  }: {
    open: boolean;
    session: LessonSessionDetail | null;
    onClose: () => void;
    onPick: (scope: "this" | "following") => void;
  }): JSX.Element | null

  export function ScheduleSeriesEditDialog({
    open, classId, session, schedule, onClose,
  }: {
    open: boolean;
    classId: string;
    session: LessonSessionDetail | null;
    schedule: Schedule | null;
    onClose: () => void;
  }): JSX.Element | null
  ```
  `SessionEditScopeDialog` không gọi API — chỉ 2 `Radio` (nhãn "Chỉ buổi này" / "Buổi này và từ nay trở đi", mô tả ở lựa chọn thứ 2: "Áp dụng cho buổi này và mọi buổi dự kiến từ ngày này trở đi.") + nút "Tiếp tục" gọi `onPick(scope)`, nút "Đóng" gọi `onClose()`. Component luôn hiện cả 2 lựa chọn khi `open` (không kiểm tra `session.status` — Review Focus #2); nơi gọi (Task 5) tự quyết không mount component này khi `session.schedule_id === null` (Review Focus #4).

- [ ] **Step 1: Viết test cho `SessionEditScopeDialog`**

`session-edit-scope-dialog.test.tsx`: render với `session` mẫu `status: "completed"` (cố ý, đúng Review Focus #2) + `schedule_id: "sc1"`; assert cả 2 `Radio` hiện; chọn "Buổi này và từ nay trở đi" rồi bấm "Tiếp tục"; assert `onPick` được gọi với `"following"`. Test thứ hai: mặc định chọn "this", bấm "Tiếp tục" ngay → `onPick("this")`.

- [ ] **Step 2: Chạy test, xác nhận FAIL (component chưa tồn tại)**

Run: `pnpm test session-edit-scope-dialog.test.tsx` — Expected: FAIL, module not found.

- [ ] **Step 3: Viết `SessionEditScopeDialog` theo mẫu UI của `SessionCancelDialog` (2 `Radio`, bỏ phần `Input` lý do và mutation)**

- [ ] **Step 4: Chạy lại test Step 1 — PASS**

Run: `pnpm test session-edit-scope-dialog.test.tsx` — Expected: PASS.

- [ ] **Step 5: Viết test cho `ScheduleSeriesEditDialog`**

`schedule-series-edit-dialog.test.tsx`, mẫu MSW giống `schedule-edit-dialog.test.tsx` cũ (đọc lại trước khi xóa ở Step 9 để lấy đúng case test cần giữ tinh thần): (a) prefill đúng `local_start_time`/`duration_minutes`/`weekdays`/`effective_until` từ prop `schedule`; (b) submit gọi `PATCH /lesson-sessions/{session.id}/update-series` (không phải `/schedules/{id}`) với body đủ `reason`; (c) 422 `VALIDATION_FAILED` gán lỗi đúng field (theo `applyFieldErrors` mẫu `ScheduleEditDialog` cũ); (d) không nhập lý do → lỗi "Vui lòng nhập lý do." (schema cũ `editScheduleFormSchema` đã có rule này, chỉ cần test hành vi submit).

- [ ] **Step 6: Chạy test, xác nhận FAIL**

Run: `pnpm test schedule-series-edit-dialog.test.tsx` — Expected: FAIL, module not found.

- [ ] **Step 7: Viết `ScheduleSeriesEditDialog`**

Sao chép cấu trúc form của `ScheduleEditDialog` cũ (đọc file trước khi xóa), đổi: `useUpdateSchedule(classId)` → `useUpdateScheduleAndFollowingSessions(classId)`; `mutate({scheduleId, input})` → `mutate({sessionId: session.id, input})`; title dialog "Sửa lịch" → "Sửa buổi này và từ nay trở đi"; defaultValues lấy từ prop `schedule` (không phải `schedule` tự fetch — component nhận sẵn từ nơi gọi, xem Task 5).

- [ ] **Step 8: Chạy lại test Step 5 — PASS**

Run: `pnpm test schedule-series-edit-dialog.test.tsx` — Expected: PASS.

- [ ] **Step 9: Xóa `schedule-edit-dialog.tsx` + test của nó (đã đọc lại nội dung ở Step 5/7 trước khi xóa)**

- [ ] **Step 10: Chạy toàn bộ test của thư mục `ui/admin` — xác nhận không còn import nào tới `schedule-edit-dialog`**

Run: `pnpm test src/features/lesson` — Expected: PASS (các file còn import `ScheduleEditDialog`/`ScheduleFormDialog` sẽ bị sửa ở Task 5, có thể còn đỏ ở bước này nếu task review chạy riêng từng task — ghi rõ trong report nếu vậy, không phải lỗi của Task 3).

- [ ] **Step 11: Commit**

```bash
git add src/features/lesson/ui/admin/session-edit-scope-dialog.tsx src/features/lesson/ui/admin/session-edit-scope-dialog.test.tsx src/features/lesson/ui/admin/schedule-series-edit-dialog.tsx src/features/lesson/ui/admin/schedule-series-edit-dialog.test.tsx
git rm src/features/lesson/ui/admin/schedule-edit-dialog.tsx src/features/lesson/ui/admin/schedule-edit-dialog.test.tsx
git commit -m "feat: add scope-choice dialog and series edit dialog for recurring sessions"
```

---

## Task 4: Dialog chi tiết buổi học + dọn `session-row.tsx`

**Files:**
- Create: `src/features/lesson/ui/admin/session-detail-dialog.tsx`
- Modify: `src/features/lesson/ui/admin/session-row.tsx` (xóa hàm `SessionRow`, giữ `SESSION_STATUS_BADGE` + `EditSessionDialogLoader`)
- Test: `src/features/lesson/ui/admin/session-detail-dialog.test.tsx`
- Modify: `src/features/lesson/ui/admin/session-row.test.tsx` nếu có test riêng cho `SessionRow` (kiểm tra trước — nếu chỉ test qua `class-sessions-tab.test.tsx` thì không có file riêng, bỏ qua)

**Interfaces:**
- Consumes: `useLessonSession` (có sẵn, `api/lesson-sessions.ts`); `Schedule`/`LessonSessionDetail` (`types.ts`); `SESSION_STATUS_BADGE`, `EditSessionDialogLoader`'s loading/error pattern (tái dùng cấu trúc, không tái dùng trực tiếp component vì phạm vi khác — xem Step 3).
- Produces (dùng ở Task 5):
  ```ts
  export function SessionDetailDialog({
    classId, sessionId, schedules, onClose, onEdit, onCancel, onComplete,
  }: {
    classId: string;
    sessionId: string | null;
    schedules: Schedule[];
    onClose: () => void;
    onEdit: (session: LessonSessionDetail) => void;
    onCancel: (session: LessonSessionDetail) => void;
    onComplete: (session: LessonSessionDetail) => void;
  }): JSX.Element | null
  ```
  Tự fetch chi tiết qua `useLessonSession(sessionId ?? "")` (giống `EditSessionDialogLoader`: `sessionId=null` → `return null`; đang tải → overlay "Đang tải…"; lỗi → overlay lỗi + nút "Đóng"). Khi có data: hiện ngày/giờ (`formatDate`/`formatTime` từ `lib/time.ts`), badge trạng thái (`SESSION_STATUS_BADGE`), `lesson_content`/`notes`, badge "Cần rà soát điểm danh" nếu `review_required`. Nếu `session.schedule_id !== null`: tìm `schedules.find((s) => s.id === session.schedule_id)` — có thì hiện dòng "Thuộc lịch lặp: {days} · {time} · đến {until} ({statusLabel})" (viết lại 4 hàm `daysText`/`timeText`/`rangeText`/`addMinutesToTime` ngay trong file này, theo đúng logic đã có ở `class-schedule-tab.tsx` trước khi nó bị xóa ở Task 5 — đọc file đó trước khi viết); không có (trường hợp hiếm, cache chưa kịp) thì bỏ qua dòng này, không lỗi. Nút hành động theo rule cũ (`session-row.tsx`): "Sửa" luôn hiện, gọi `onEdit(session)`; "Hoàn tất" hiện khi `status === "scheduled"`, gọi `onComplete(session)`; "Hủy" hiện khi `status !== "cancelled"`, gọi `onCancel(session)`.

- [ ] **Step 1: Viết test cho `SessionDetailDialog`**

`session-detail-dialog.test.tsx`: MSW `GET /lesson-sessions/q1` trả `LessonSessionDetail` có `schedule_id: "sc1"`, `lesson_content`, `notes`, `review_required: true`; render với `schedules=[{id:"sc1", status:"active", effective_from:"2026-09-05", effective_until:"2027-06-30", local_start_time:"17:30:00", duration_minutes:90, repeat_weekly:true, weekdays:[2,4]}]`; assert hiện nội dung, ghi chú, badge "Cần rà soát điểm danh", dòng "Thuộc lịch lặp" chứa "Thứ Ba, Thứ Năm" và "Đang áp dụng". Test thứ hai: `status: "cancelled"` → nút "Hoàn tất" không hiện, nút "Hủy" không hiện (đã huỷ rồi), nút "Sửa" vẫn hiện. Test thứ ba: lỗi tải chi tiết → overlay lỗi có nút "Đóng" gọi được `onClose`.

- [ ] **Step 2: Chạy test, xác nhận FAIL**

Run: `pnpm test session-detail-dialog.test.tsx` — Expected: FAIL, module not found.

- [ ] **Step 3: Viết `SessionDetailDialog` + xóa `SessionRow` khỏi `session-row.tsx` (giữ `SESSION_STATUS_BADGE`, `EditSessionDialogLoader`)**

- [ ] **Step 4: Chạy lại test Step 1 — PASS**

Run: `pnpm test session-detail-dialog.test.tsx` — Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/features/lesson/ui/admin/session-detail-dialog.tsx src/features/lesson/ui/admin/session-detail-dialog.test.tsx src/features/lesson/ui/admin/session-row.tsx
git commit -m "feat: add session detail dialog showing recurring schedule info"
```

---

## Task 5: Tab "Lịch học" mới + dọn tab cũ + nối `ClassDetailScreen`

**Files:**
- Create: `src/features/lesson/ui/admin/class-lesson-schedule-tab.tsx`
- Delete: `src/features/lesson/ui/admin/class-schedule-tab.tsx`, `class-schedule-tab.test.tsx`, `class-sessions-tab.tsx`, `class-sessions-tab.test.tsx`, `schedule-cancel-dialog.tsx`, `schedule-cancel-dialog.test.tsx`, `schedule-form-dialog.tsx`, `schedule-form-dialog.test.tsx`
- Modify: `src/features/lesson/index.ts`
- Modify: `src/features/academic/ui/admin/class-detail-screen.tsx`
- Test: `src/features/lesson/ui/admin/class-lesson-schedule-tab.test.tsx`

**Interfaces:**
- Consumes: mọi thứ từ Task 1-4 (`MyScheduleDayView`/`WeekView`/`MonthView` genericized, `CalendarSessionItem`, `useLessonSessions(classId, filters)`, `useSchedules`, `useClassTeachers` (từ `@/features/user-management`), `SessionDetailDialog`, `SessionEditScopeDialog`, `ScheduleSeriesEditDialog`, `ScheduleCreateDialog`, `SessionFormDialog`, `EditSessionDialogLoader`, `SessionCancelDialog`, `SessionCompleteDialog`, `rangeFor`/`endOfDay`/`titleFor`/`addDaysIso`/`WEEKDAY_NAMES` (`my-schedule-date-utils.ts`), `todayIso` (`today.ts`).
- Produces (export công khai, dùng ở `class-detail-screen.tsx`):
  ```ts
  export function ClassLessonScheduleTab({
    classId, highlightSessionId, initialMonth, autoOpenCreate,
  }: {
    classId: string;
    highlightSessionId?: string;
    initialMonth?: string;
    autoOpenCreate?: boolean;
  }): JSX.Element
  ```
  Giữ nguyên chữ ký prop của `ClassSessionsTab` cũ — `class-detail-screen.tsx` chỉ đổi tên component gọi, không đổi cách truyền prop.

- [ ] **Step 1: Viết test cho `ClassLessonScheduleTab` — các case Review Focus 1-5**

`class-lesson-schedule-tab.test.tsx`, theo mẫu MSW của `class-sessions-tab.test.tsx` cũ (đọc lại trước khi xóa ở Step 6):
- `truyền highlightSessionId thì mở dialog chi tiết đúng buổi đó ngay khi vào tab` — MSW `GET lesson-sessions` trả 2 buổi, `GET lesson-sessions/q2` trả chi tiết; render với `highlightSessionId="q2"`; assert dialog chi tiết hiện đúng nội dung buổi q2 (Review Focus #1).
- `truyền autoOpenCreate thì tự mở form Thêm buổi lẻ` — giữ hành vi cũ (Review Focus #1).
- `bấm Sửa trên buổi schedule_id != null, trạng thái completed thì vẫn hiện dialog hỏi phạm vi` — MSW trả buổi `status: "completed"`, `schedule_id: "sc1"`; mở chi tiết, bấm "Sửa"; assert `SessionEditScopeDialog` hiện (Review Focus #2).
- `bấm Sửa trên buổi schedule_id = null thì vào thẳng form sửa, không hỏi phạm vi` — assert không có 2 `Radio` xuất hiện, có ngay heading "Sửa buổi học" (Review Focus #4).
- `mặc định Select trạng thái "Tất cả" vẫn hiện buổi cancelled có review_required` — MSW trả buổi `status: "cancelled", review_required: true`; assert badge "Cần rà soát điểm danh" hiện mà không cần đổi filter nào (Review Focus #3).
- `chọn "Buổi này và từ nay trở đi" thì mở ScheduleSeriesEditDialog với giá trị prefill từ Schedule hiện có` — MSW `GET classes/c1/schedules` trả 1 schedule khớp `schedule_id` của buổi; assert input "Ngày kết thúc" có value đúng `effective_until` của schedule đó (Review Focus #5).
- `nút Tạo lịch lặp mở ScheduleCreateDialog, nút Thêm buổi lẻ mở SessionFormDialog mode create`.
- `lọc giáo viên chỉ liệt kê giáo viên đang phân công (valid_to null)` — MSW `GET classes/c1/teachers` trả 1 giáo viên `valid_to: null` + 1 giáo viên `valid_to: "2026-01-01"`; assert Select giáo viên chỉ có 1 option (ngoài "Mọi giáo viên").
- `buổi có schedule_id != null hiện icon 🔁 trong ô lịch`.

- [ ] **Step 2: Chạy test, xác nhận FAIL (component chưa tồn tại)**

Run: `pnpm test class-lesson-schedule-tab.test.tsx` — Expected: FAIL, module not found.

- [ ] **Step 3: Viết `ClassLessonScheduleTab`**

State: `view` (mặc định `initialMonth ? "month" : "week"`), `anchor` (mặc định `initialMonth ? `${initialMonth}-01` : todayIso()`), `teacherId`/`statusFilter`/`reviewRequiredOnly`, `detailSessionId` (mặc định `highlightSessionId ?? null`), `creatingSchedule`/`creatingSession` (mặc định `creatingSession = Boolean(autoOpenCreate)`), `editingSession: LessonSessionDetail | null` + `editScopeFor: LessonSessionDetail | null` (buổi đang hỏi phạm vi) + `editingSeriesFor: LessonSessionDetail | null` (buổi đang sửa "và từ nay trở đi"), `cancelling`/`completing: LessonSessionListItem | null` (tái dùng thẳng `SessionCancelDialog`/`SessionCompleteDialog` như cũ).

Data: `useSchedules(classId)`, `useClassTeachers(classId)` (lọc `valid_to === null` khi đổ vào `Select`), `useLessonSessions(classId, { ...rangeFor(view, anchor) khớp endOfDay(until), teacher_id: teacherId || undefined, status: statusFilter || undefined, review_required: reviewRequiredOnly || undefined })`.

Toolbar: view tabs Ngày/Tuần/Tháng + nút lướt + "Hôm nay" (sao chép đúng cấu trúc từ `my-schedule-screen.tsx`) · `Select` giáo viên · `Select` trạng thái (options: Tất cả/Dự kiến/Đã hoàn tất/Đã hủy, map sang `scheduled`/`completed`/`cancelled`) · `Switch` "Chỉ buổi cần rà soát điểm danh" · nút "Tạo lịch lặp" (`iconLeft="plus"`, mở `creatingSchedule`) · nút "Thêm buổi lẻ" (mở `creatingSession`).

Render view: `onSelect={(s) => setDetailSessionId(s.id)}`, `renderLabel`/`renderCompactLabel` trả nội dung tối giản (không có `class_name`/`teacher_names` ở ngữ cảnh 1 lớp — ví dụ chỉ hiện badge trạng thái, không cần `title` text), `badgeExtra={(s) => s.schedule_id ? <span aria-label="Thuộc lịch lặp">🔁</span> : null}` cho Day/Week.

Luồng Sửa (nút "Sửa" trong `SessionDetailDialog.onEdit`): đóng detail dialog; nếu `session.schedule_id === null` → mở `EditSessionDialogLoader`-style trực tiếp (dùng lại `SessionFormDialog mode="edit"` với `session` đã có sẵn từ `LessonSessionDetail`, không cần load lại); nếu khác `null` → set `editScopeFor = session`, mở `SessionEditScopeDialog`; `onPick("this")` → đóng scope dialog, mở `SessionFormDialog mode="edit"` với session đó; `onPick("following")` → đóng scope dialog, set `editingSeriesFor = session`, mở `ScheduleSeriesEditDialog` với `schedule = schedules.find((s) => s.id === session.schedule_id) ?? null`.

Luồng Hủy/Hoàn tất (`onCancel`/`onComplete` từ `SessionDetailDialog`): đóng detail dialog, set `cancelling`/`completing`, mở `SessionCancelDialog`/`SessionCompleteDialog` như cũ (không đổi 2 component này).

- [ ] **Step 4: Chạy lại test Step 1 — PASS**

Run: `pnpm test class-lesson-schedule-tab.test.tsx` — Expected: PASS.

- [ ] **Step 5: Sửa `features/lesson/index.ts`**

Xóa export `ClassScheduleTab`, `ClassSessionsTab`; thêm `export { ClassLessonScheduleTab } from "./ui/admin/class-lesson-schedule-tab";`.

- [ ] **Step 6: Xóa `class-schedule-tab.tsx`/test, `class-sessions-tab.tsx`/test, `schedule-cancel-dialog.tsx`/test, `schedule-form-dialog.tsx`/test (đã đọc lại nội dung các file này ở Task 3/4/5 trước khi xóa)**

- [ ] **Step 7: Sửa `class-detail-screen.tsx`**

Thay 2 phần tử `{ value: "schedule", label: "Lịch" }` + `{ value: "sessions", label: "Buổi học" }` trong mảng `tabs` bằng đúng một phần tử `{ value: "sessions", label: "Lịch học" }` (giữ nguyên `value: "sessions"` — Global Constraints). Thay `{tab === "schedule" && <ClassScheduleTab .../>}` + khối `{tab === "sessions" && <ClassSessionsTab .../>}` bằng một khối `{tab === "sessions" && <ClassLessonScheduleTab classId={classId} highlightSessionId={sessionParam ?? undefined} initialMonth={monthParam ?? undefined} autoOpenCreate={openCreateParam} />}`. Đổi import `ClassScheduleTab, ClassSessionsTab` → `ClassLessonScheduleTab`. Dòng khởi tạo `useState(searchParams.get("tab") ?? (sessionParam || openCreateParam ? "sessions" : "overview"))` giữ nguyên (không đổi).

- [ ] **Step 8: Chạy toàn bộ test suite của `piclass-web` + typecheck + lint**

Run: `pnpm test`, `pnpm typecheck`, `pnpm lint` — Expected: tất cả PASS, không còn file nào import `ClassScheduleTab`/`ClassSessionsTab`/`ScheduleEditDialog`/`ScheduleCancelDialog`/`ScheduleFormDialog`/`useUpdateSchedule`/`useCancelSchedule`.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat: replace AD-10d/AD-10e tabs with single Lịch học calendar tab"
```

---

## Self-Review

**1. Spec coverage:** Mục tiêu 1 (tab gộp, calendar) → Task 5. Mục tiêu 2 (filter) → Task 2 + Task 5. Mục tiêu 3 (toolbar Tạo lịch lặp/Thêm buổi lẻ) → Task 5. Mục tiêu 4 (chi tiết buổi + thông tin mẫu lịch) → Task 4. Mục tiêu 5 (2 lựa chọn phạm vi khi sửa) → Task 3 + Task 5. Mục tiêu 6 (API mới) → Task 2 (API đã có sẵn ở BE, chỉ cần nối). Mục tiêu 7 (deep-link AD-11) → Task 5 Step 1/3 + Global Constraints. "Khoảng trống kỹ thuật" (genericize view) → Task 1, chọn đúng hướng (a) được khuyến nghị trong spec. Quyết định bỏ switch "Ẩn buổi đã hủy" → Global Constraints + Review Focus #3. Ảnh hưởng AD-11 (giữ `tab=sessions`) → Global Constraints + Task 5 Step 7.

**2. Step scan:** mỗi step trong plan đều có một việc kiểm được (viết test cụ thể / chạy lệnh cụ thể / implement theo chữ ký đã cho) — không có step dạng "xử lý phù hợp" chung chung.

**3. Type consistency:** `CalendarSessionItem` (Task 1) được 3 view dùng xuyên suốt tới Task 5; `UpdateScheduleInput`/`UpdatedSchedule`/`Schedule`/`LessonSessionDetail` dùng nhất quán tên đúng như đã có trong `types.ts`, không đặt tên mới trùng lặp. `ListLessonSessionsFilters` (Task 2) dùng lại ở `useLessonSessions` của Task 5.

**4. Review Focus:** cả 5 mục đều có test riêng gắn vào Task 3 (#2, #5) hoặc Task 5 (#1, #3, #4).

**5. Proportion:** spec dài ~100 dòng tập trung quyết định nghiệp vụ/UI; plan này dài hơn vì liệt kê đủ 5 task FE với test case cụ thể cho từng Review Focus — chữ ký đã thay full code (trừ 2 đoạn ngắn minh họa cách dùng `invalidateScheduleAndSessions`/`updateSeries` vì đó là những dòng spec đã quyết sẵn giá trị, không phải chỗ implementer tự quyết).
