# FE cổng Giáo viên — lát 1 "Vận hành lớp" (TC-02..TC-05) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giáo viên đăng nhập thấy menu riêng và dùng được bốn màn: Lớp của tôi (TC-02), Chi tiết lớp (TC-03), Lịch dạy (TC-04), Điểm danh (TC-05), gọi API thật.

**Architecture:** UI của Giáo viên nằm ở `features/<x>/ui/teacher/` (rule F4: không import `ui/admin/`). Phần Admin và Teacher cùng cần (view lịch, hộp thoại buổi học, tab Lịch học, badge lớp) được chuyển lên `features/<x>/ui/shared/` ở Task 1 trước khi dùng. Lớp dữ liệu (`features/<x>/api/`) dùng chung; thêm mới `features/attendance/api` và `features/audit/api`. Khung `AppShell` đã dùng chung cho mọi vai trò, chỉ thêm mục menu Teacher ở `config/nav.ts`.

**Tech Stack:** Next.js 16, React 19, TypeScript 6, TanStack Query, Vitest + Testing Library + MSW 3, Tailwind (token DS v2 trong `src/app/globals.css`).

**Spec:** [docs/product/screens/teacher-van-hanh.md](../../product/screens/teacher-van-hanh.md) (TC-02..TC-05 và mục "Kết quả rà mockup lát 1"); quyết định `DEC-LRN-016`, `DEC-LRN-017`, `DEC-USR-015`; mockup `piclass-mockup/design-system/PiClass Giáo viên.dc.html`; quy ước `piclass-web/CLAUDE.md`.

## Global Constraints

- Quy ước repo: `piclass-web/CLAUDE.md` (F1–F8, mục "Khi nào đọc gì" → `docs/rules/design-system.md`, `testing.md`, `pitfalls.md`). Route `page.tsx` ≤ 30 dòng, không hook dữ liệu (F1).
- Trước khi gọi API: chạy `docker compose exec api php artisan scramble:export` (repo `piclass-infra/local`) rồi đọc `piclass-api/storage/api-docs/openapi.json`.
- Mọi thời điểm hiển thị đi qua `src/lib/time.ts` (F8); so sánh giờ buổi học với hiện tại bằng `hasSessionEnded`/`sessionDisplayStatus` của `features/lesson/api/session-status.ts`.
- Port UI **đúng từng giá trị** từ mockup, không quy tròn token; mockup chưa có chỗ nào thì ghi vào `docs/architecture/open-decisions.md`, không tự đổi rồi im lặng. Chữ trên giao diện tiếng Việt gõ thẳng trong component. Mỗi Task ghi đoạn mockup cần đọc (số dòng trong `PiClass Giáo viên.dc.html`, mockup làm việc ở nhánh `main` của `piclass-mockup`).
- Comment code và mô tả test tiếng Việt; tên biến/hàm/file/test method tiếng Anh; commit message tiếng Anh.
- Nhãn trạng thái buổi: Dự kiến / Chờ hoàn tất / Hoàn tất / Đã hủy (hàm `sessionStatusBadge`). Nhãn ghi chú: "Ghi chú nội bộ (chỉ giáo viên và quản trị viên thấy)". Nút Hủy buổi ẩn khi `hasSessionEnded(ends_at)` (`DEC-LRN-016`).
- Một tài khoản một vai trò (`DEC-USR-015`): không có công tắc đổi vai trò, không nhãn "vai đang dùng".
- Quyền thật do BE: FE chỉ ẩn/hiện theo dữ liệu BE trả (F7); 404 của Teacher hiện "Không tìm thấy hoặc đã hết phân công", không nói lớp có tồn tại hay không.
- Git: nhánh từ `origin/main` (`git fetch origin` trước), tự push nhánh sau mỗi Task xong và test xanh, không force-push/`--amend` commit đã push, PR và merge chỉ khi user yêu cầu. Review cuối nhánh: opus, effort medium.
- Ngoài phạm vi lát này: tab "Bài của lớp" ở TC-03 (lát Bài đánh giá), đơn xin phép (huy hiệu "Có đơn xin phép" và liên kết ở TC-05 chờ TC-06 lát 2), Tổng quan TC-01 (lát 2), tab "Lịch sử thay đổi" của lớp, chuyên cần/biểu đồ (chưa có API: hiện "Chưa có dữ liệu").

## Review Focus

Spec nói ít về các tình huống sau; mỗi dòng có test ở Task chủ:

1. Teacher mở lớp đã hết phân công hoặc không tồn tại: BE trả 404 → trang "Không tìm thấy hoặc đã hết phân công", không rò thông tin (Task 3).
2. Teacher chưa được phân công lớp nào: danh sách rỗng "Bạn chưa được phân công lớp nào. Liên hệ quản trị viên", không có nút tạo lớp (Task 2).
3. Điểm danh lô có học sinh không còn hiệu lực tại giờ bắt đầu (422 `STUDENT_NOT_ELIGIBLE`, `error.details.student_ids`): cả lô chưa lưu, có nút bỏ các em đó rồi lưu lại (Task 7).
4. Buổi đã hủy (409 `SESSION_CANCELLED`) hoặc đã hủy sẵn: khóa chọn trạng thái và nút Lưu (Task 7).
5. Buổi đã qua giờ kết thúc vẫn `scheduled`: nhãn "Chờ hoàn tất", không có nút Hủy, vẫn Sửa/Hoàn tất/điểm danh được (Task 8).

## File Structure

| Việc | File |
|---|---|
| Chuyển lên shared (Task 1) | `src/features/lesson/ui/shared/*` (view lịch, hộp thoại buổi, `class-lesson-schedule-*`, `session-row.tsx`, `ScheduleOverviewScreen`), `src/features/academic/ui/shared/class-badges.ts` |
| Menu | `src/config/nav.ts` |
| TC-02 | `src/features/academic/ui/teacher/teacher-class-list-screen.tsx`, `teacher-class-card.tsx`, route `src/app/(portal)/teacher/classes/page.tsx` |
| TC-03 | `src/features/academic/ui/teacher/teacher-class-detail-screen.tsx`, `class-roster-tab.tsx`, `class-teachers-readonly-tab.tsx`, `class-progress-tab.tsx`, `roster-utils.ts`, route `teacher/classes/[classId]/page.tsx`; `src/features/results/api/progress.ts` (mở rộng) |
| TC-04 | `src/features/lesson/ui/teacher/teacher-schedule-screen.tsx`, route `teacher/schedule/page.tsx` |
| API mới | `src/features/attendance/api/{types,keys,attendance}.ts` + `index.ts`; `src/features/audit/api/{types,keys,audit-logs}.ts` + `index.ts` |
| TC-05 | `src/features/attendance/ui/teacher/{attendance-screen,attendance-roster,attendance-session-list,attendance-content-form,attendance-record-edit-dialog,attendance-rows}.tsx\|ts`, route `teacher/attendance/page.tsx` |

---

### Task 1: Chuyển UI dùng chung lên `ui/shared` (refactor, không đổi hành vi)

**Files:**
- Move (`git mv`, kèm file `*.test.tsx`/`*.test.ts` cùng tên) từ `src/features/lesson/ui/admin/` sang `src/features/lesson/ui/shared/`: `my-schedule-day-view`, `my-schedule-week-view`, `my-schedule-month-view`, `my-schedule-date-utils`, `today`, `session-row`, `session-detail-dialog`, `session-cancel-dialog`, `session-complete-dialog`, `session-form-dialog`, `session-edit-scope-dialog`, `schedule-create-dialog`, `schedule-add-session-dialog`, `schedule-series-edit-dialog`, `weekday-chips`, `class-lesson-schedule-flow`, `class-lesson-schedule-dialogs`, `class-lesson-schedule-tab`, `my-schedule-screen` (đổi tên file thành `schedule-overview-screen.tsx`).
- Move `src/features/academic/ui/admin/class-badges.ts` → `src/features/academic/ui/shared/class-badges.ts`.
- Create: `src/features/lesson/ui/admin/my-schedule-screen.tsx` (wrapper mỏng).
- Modify: `src/features/lesson/index.ts`, `src/features/academic/index.ts`, mọi import trỏ tới file đã chuyển (`grep -rn "ui/admin/<tên>"`).

**Interfaces:**
- Produces: `ScheduleOverviewScreen({ portal }: { portal: "admin" | "teacher" })` trong `lesson/ui/shared/schedule-overview-screen.tsx`. `portal` quyết định tiền tố đường dẫn của liên kết buổi (`/${portal}/classes/${class_id}?tab=sessions&session=${id}&month=${yyyy-mm}`), và chỉ `portal === "admin"` mới hiện bộ lọc Giáo viên (Teacher chỉ có lọc lớp, môn, "Ẩn buổi đã hủy"). `MyScheduleScreen` (Admin) = `<ScheduleOverviewScreen portal="admin" />`. Các export khác của `@/features/lesson` giữ nguyên tên: `ClassLessonScheduleTab`, `MyScheduleScreen`, `sessionStatusBadge`; thêm `ScheduleOverviewScreen`.

- [ ] **Step 1:** Chạy `pnpm test` và ghi số test xanh hiện tại (baseline 426).
- [ ] **Step 2:** Viết test thất bại trong `schedule-overview-screen.test.tsx` (chuyển từ `my-schedule-screen.test.tsx`): `it("portal teacher: liên kết buổi trỏ /teacher/classes/... và không có bộ lọc Giáo viên")` dựng với `portal="teacher"`, assert `href` bắt đầu `/teacher/classes/c1?tab=sessions&session=` và `queryByRole("combobox", { name: /giáo viên/i })` null; chạy xác nhận FAIL.
- [ ] **Step 3:** `git mv` các file, sửa import bằng tìm-thay (`grep`), thêm prop `portal`, wrapper Admin. Cạm bẫy: `ui/shared` không được import `ui/admin` hay `ui/teacher`; trường hợp `useTeachers` (cần cho bộ lọc Admin) giữ trong shared qua `@/features/user-management` (F5) và chỉ gọi khi `portal === "admin"` (`enabled`).
- [ ] **Step 4:** `pnpm typecheck && pnpm lint && pnpm test` — mọi test cũ vẫn xanh, test mới xanh, số test không giảm.
- [ ] **Step 5:** Commit `refactor(lesson): move shared schedule UI to ui/shared and add portal prop`, push.

---

### Task 2: TC-02 Lớp của tôi

**Mockup:** `isClasses` dòng 174–202 (tiêu đề "Lớp của tôi", tìm tên, tab trạng thái, lọc môn, thẻ lớp). **Spec:** TC-02 + "Kết quả rà mockup lát 1". So sánh thêm với `src/features/academic/ui/admin/class-list-screen.tsx` và `class-list-grid.tsx` (cùng mẫu lọc, đã sửa 2026-10-28).

**Files:**
- Create: `src/features/academic/ui/teacher/teacher-class-list-screen.tsx`, `teacher-class-card.tsx`, test cùng tên; `src/app/(portal)/teacher/classes/page.tsx`.
- Modify: `src/features/academic/index.ts` (export `TeacherClassListScreen`), `src/config/nav.ts`.

**Interfaces:**
- Consumes: `useClasses(): UseQueryResult<ClassListItem[]>`, `useSubjects()`, `CLASS_STATUS_BADGE` (`ui/shared/class-badges`).
- Produces: `TeacherClassListScreen(): JSX.Element`. Tab: Tất cả / Đang hoạt động / Đã lưu trữ (không có "Chưa có giáo viên", không công tắc lớp lưu trữ; lớp `archived` xếp cuối, thẻ mờ như Admin). Thẻ: tên (`name`), nhãn (`label`), badge trạng thái, badge môn, ba ô Giáo viên (`teacher_names.join(", ")`) / Buổi kế tiếp (`next_session_at` qua `formatDateTime`, null → "—") / Học sinh (`student_count`). Bấm thẻ → `/teacher/classes/{id}`. Không nút Tạo lớp. `NAV_ITEMS.teacher` thêm `{ label: "Lớp của tôi", href: "/teacher/classes", icon: "users" }` sau "Tổng quan".

- [ ] **Step 1: Test thất bại** (`teacher-class-list-screen.test.tsx`, MSW giả `GET /classes` và `GET /subjects`):
  - `it("hiện thẻ lớp với giáo viên, buổi kế tiếp, sĩ số và không có nút Tạo lớp")`.
  - `it("tab Tất cả hiện cả lớp lưu trữ xếp cuối; tab Đã lưu trữ chỉ lớp lưu trữ")`.
  - `it("tìm tên không phân biệt dấu và lọc môn")` ("toan" khớp "Toán 9A").
  - `it("chưa được phân công lớp nào: hiện 'Bạn chưa được phân công lớp nào. Liên hệ quản trị viên.' và không có nút tạo lớp")` (Review Focus 2).
  - `it("lỗi tải hiện nút Thử lại")`.
- [ ] **Step 2:** Chạy `pnpm vitest run src/features/academic/ui/teacher/teacher-class-list-screen.test.tsx`, expected FAIL (module chưa có).
- [ ] **Step 3:** Implement theo mockup (`text-[…px]` đúng giá trị mockup), route 4 dòng, thêm mục menu.
- [ ] **Step 4:** `pnpm test`, `pnpm typecheck`, `pnpm lint` xanh.
- [ ] **Step 5:** Commit `feat(academic): teacher class list screen (TC-02)`, push.

---

### Task 3: TC-03 Chi tiết lớp — khung, tab Học sinh, tab Giáo viên

**Mockup:** `isClassDetail` dòng 203–288 (đầu trang, hai ô số Học sinh/Buổi đã học, `Tabs variant="line"`, tab Học sinh: ô "Tìm học sinh theo tên", ghi chú "Chỉ xem. Thêm hoặc bớt học sinh do quản trị viên làm.", nhóm "Đã rời lớp (N)" thu gọn; tab Giáo viên: ghi chú "Chỉ xem. Phân công giáo viên do quản trị viên làm."); trang không tìm thấy: `forbTitle`/`forbText` (tìm "Không tìm thấy hoặc đã hết phân công"). **Spec:** TC-03. Đối chiếu code Admin `class-detail-screen.tsx`, `class-members-tab.tsx`, `class-teachers-tab.tsx`.

**Files:**
- Create: `src/features/academic/ui/teacher/{teacher-class-detail-screen,class-roster-tab,class-teachers-readonly-tab,roster-utils}.tsx|ts` + test; `src/app/(portal)/teacher/classes/[classId]/page.tsx`.
- Modify: `src/features/academic/index.ts`.

**Interfaces:**
- Consumes: `useClass(id)`, `useClassMembers(classId)` → `ClassMemberRosterItem[]`, `useClassTeachers(classId)` → `ClassTeacherRosterItem[]` (cả hai qua `@/features/user-management`), `isApiError` (`@/lib/api-error`).
- Produces:
  - `partitionRoster<T extends { valid_to: string | null }>(items: T[], now?: Date): { current: T[]; ended: T[] }` (`valid_to` null hoặc sau `now` là current).
  - `TeacherClassDetailScreen({ classId }: { classId: string })`; tab điều khiển bằng `?tab=students|teachers|sessions|progress` (mặc định `students`); tab `sessions` và `progress` hoàn thiện ở Task 4 (ở Task này render placeholder `null`).
  - Đầu trang: tên, badge trạng thái, `label`; nút quay lại "Lớp của tôi"; không địa điểm, không nút Lưu trữ, không nút Giao bài ở lát này. Hai ô số: Học sinh (số học sinh current) và Buổi đã học (đếm `sessions` đã `completed` của `useLessonSessions(classId, {})`).
  - Tab Học sinh: bảng Học sinh | Vào lớp từ (`formatDate(valid_from)`), ô tìm không dấu; nhóm "Đã rời lớp (N)" với nút "Hiện/Ẩn học sinh đã rời lớp".

- [ ] **Step 1: Test thất bại:** `roster-utils.test.ts` (`partitionRoster` với `valid_to` null/tương lai/quá khứ, `now` truyền vào); `teacher-class-detail-screen.test.tsx`: `it("hiện danh sách học sinh còn học, nhóm Đã rời lớp thu gọn")`, `it("tab Giáo viên chỉ xem, hiện ghi chú 'Chỉ xem. Phân công giáo viên do quản trị viên làm.'")`, `it("lớp ngoài phạm vi (404 'Request failed.') hiện 'Không tìm thấy hoặc đã hết phân công' và không hiện tên lớp")` (Review Focus 1).
- [ ] **Step 2:** Chạy các test, expected FAIL.
- [ ] **Step 3:** Implement; chữ lỗi 404 lấy từ mockup `forbTitle`/`forbText`, **đổi xưng hô "cô" thành "bạn"** (mockup xưng theo persona "cô Lan", FE thật không biết giới tính người dùng) và ghi lệch này vào `docs/architecture/open-decisions.md`.
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint`.
- [ ] **Step 5:** Commit `feat(academic): teacher class detail with roster and teachers tabs (TC-03)`, push.

---

### Task 4: TC-03 — tab Lịch học và tab Tiến độ

**Mockup:** `isClassDetail` khối tab Tiến độ (`clsProgRows`, lưới 5 cột Học sinh / Điểm TB / Đã chấm / Chờ chấm / Chuyên cần, dòng "Bản mẫu: …G3"); tab Lịch học dùng lại UI Admin (không có mockup Giáo viên riêng; Admin đã gộp Lịch + Buổi học thành một tab, `ARCH-37`).

**Files:**
- Modify: `src/features/results/api/progress.ts` (mở rộng), `teacher-class-detail-screen.tsx`, `src/features/results/index.ts`.
- Create: `src/features/academic/ui/teacher/class-progress-tab.tsx` + test.

**Interfaces:**
- Produces (results): `useStudentProgress(studentId: string, filters?: { classId?: string })` gọi `GET /students/{id}/progress?class_id=` (key gồm `classId`, test cập nhật); `useStudentsProgress(studentIds: string[], filters?: { classId?: string }): UseQueryResult<StudentProgress>[]` bằng `useQueries`.
- Produces (ui): tab Lịch học = `<ClassLessonScheduleTab classId highlightSessionId initialMonth autoOpenCreate />` (props từ `?session=`, `?month=`, `?openCreate=1`, đúng cách `class-detail-screen.tsx` của Admin); tab `ClassProgressTab({ classId }: { classId: string })`: mỗi học sinh current một dòng, cột Điểm TB (`average_percentage` → "N%" hoặc "—"), Đã chấm (`graded_count`), Chờ chấm (`pending_count`), Chuyên cần luôn "Chưa có dữ liệu" (G3); mỗi dòng tải độc lập (skeleton từng dòng, lỗi từng dòng không phá bảng). Bấm dòng chưa làm gì (TC-14 ở lát sau).

- [ ] **Step 1: Test thất bại:** `progress.test.tsx`: `it("gửi class_id khi có bộ lọc lớp")`; `class-progress-tab.test.tsx`: `it("mỗi học sinh một dòng với điểm, số bài đã chấm, chờ chấm; chuyên cần 'Chưa có dữ liệu'")`, `it("một học sinh lỗi tải không làm hỏng các dòng khác")`; `teacher-class-detail-screen.test.tsx`: `it("tab sessions hiện lịch học của lớp và mở đúng buổi theo ?session=")`.
- [ ] **Step 2:** Chạy, expected FAIL.
- [ ] **Step 3:** Implement; nối tab vào màn chi tiết.
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint`.
- [ ] **Step 5:** Commit `feat(academic): teacher class schedule and progress tabs (TC-03)`, push.

---

### Task 5: TC-04 Lịch dạy

**Mockup:** `isSchedule` dòng 289–314 chỉ vẽ lưới tuần; phần còn lại theo Admin (AD-11, đã port ở `schedule-overview-screen.tsx`). **Spec:** TC-04.

**Files:**
- Create: `src/features/lesson/ui/teacher/teacher-schedule-screen.tsx` + test; `src/app/(portal)/teacher/schedule/page.tsx`.
- Modify: `src/features/lesson/index.ts` (export `TeacherScheduleScreen`), `src/config/nav.ts`.

**Interfaces:**
- Produces: `TeacherScheduleScreen()` = `<ScheduleOverviewScreen portal="teacher" />` kèm tiêu đề "Lịch dạy"; menu `{ label: "Lịch dạy", href: "/teacher/schedule", icon: "calendar-days" }`. Bộ lọc lớp lấy từ `useClasses()` (đã theo phạm vi Teacher); "Thêm buổi" dẫn tới `/teacher/classes/{id}?tab=sessions&openCreate=1` sau khi chọn lớp (cùng hành vi Admin).

- [ ] **Step 1: Test thất bại:** `it("hiện lịch tuần của giáo viên, không có bộ lọc Giáo viên, bấm buổi dẫn tới tab Lịch học của lớp")`, `it("buổi scheduled đã qua giờ hiện 'Chờ hoàn tất'")` (giờ hệ thống đóng băng bằng `vi.useFakeTimers({ toFake: ['Date'] })`).
- [ ] **Step 2:** Chạy, expected FAIL.
- [ ] **Step 3:** Implement wrapper, route, menu.
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint`.
- [ ] **Step 5:** Commit `feat(lesson): teacher schedule screen (TC-04)`, push.

---

### Task 6: Lớp dữ liệu điểm danh và audit

**Spec/API:** đọc `openapi.json` mục `/lesson-sessions/{id}/attendance`, `/attendance-records/{id}`, `/audit-logs`. Không UI ở Task này.

**Files:**
- Create: `src/features/attendance/api/{types,keys,attendance}.ts`, `src/features/attendance/index.ts`; `src/features/audit/api/{types,keys,audit-logs}.ts`, `src/features/audit/index.ts`; test `attendance.test.tsx`, `audit-logs.test.tsx`. Cập nhật `src/architecture.test.ts` nếu danh sách feature được khai báo cứng.

**Interfaces:**
- Produces (`attendance`):
  - `type AttendanceStatus = "present" | "late" | "absent" | "excused"`.
  - `interface AttendanceEntry { id: string; lesson_session_id: string; student_id: string; full_name: string; status: AttendanceStatus; note: string | null }` (từ `GET`), `interface MarkedAttendance { id: string; lesson_session_id: string; student_id: string; status: AttendanceStatus }` (từ `PUT`).
  - `attendanceApi.list(sessionId)`, `.bulk(sessionId, { records: { student_id: string; status: AttendanceStatus; note?: string | null }[] })`, `.update(recordId, { status: AttendanceStatus; note?: string | null; reason: string })`.
  - `useSessionAttendance(sessionId: string)`, `useBulkMarkAttendance(sessionId: string)`, `useUpdateAttendanceRecord(sessionId: string)` (mutation invalidate key điểm danh của buổi và, với bulk, `lessonKeys` danh sách buổi nếu cần cờ).
  - `error.details.student_ids` đọc từ `ApiError` hiện có (`isApiError`); thêm helper `notEligibleStudentIds(error: unknown): string[]`.
- Produces (`audit`): `interface AuditLogItem { id: string; actor_user_id: string | null; actor_name: string | null; action: string; entity_type: string; entity_id: string; occurred_at: string; before_data: Record<string, unknown> | null; after_data: Record<string, unknown> | null; reason: string | null; correlation_key: string | null }`; `useAuditLogs(filters: { entity_type?: string; entity_id?: string; action?: string }, options?: { enabled?: boolean })`.

- [ ] **Step 1: Test thất bại** (MSW): `it("list trả id và lesson_session_id của từng bản ghi")`, `it("bulk gửi đúng body records và invalidate điểm danh của buổi")`, `it("update gửi reason bắt buộc")`, `it("notEligibleStudentIds đọc error.details.student_ids từ lỗi 422 STUDENT_NOT_ELIGIBLE, trả [] với lỗi khác")`, `it("useAuditLogs gửi entity_type và entity_id làm query và trả actor_name")`.
- [ ] **Step 2:** Chạy, expected FAIL.
- [ ] **Step 3:** Implement theo mẫu `features/lesson/api/lesson-sessions.ts` (hàm api + hook + key riêng).
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint` (kể cả `architecture.test.ts`).
- [ ] **Step 5:** Commit `feat(attendance,audit): attendance and audit-log API layer`, push.

---

### Task 7: TC-05 màn Điểm danh — danh sách buổi, bảng điểm danh, lưu

**Mockup:** `isAttendance` dòng 315–374 (cột trái 280px danh sách buổi, bảng bên phải: bộ đếm trạng thái, nút "Tất cả có mặt", chọn nhanh 4 trạng thái, "Lưu điểm danh", banner lỗi `hasNes` và nút "Bỏ các em này khỏi lô rồi lưu lại"). Bỏ phần "Xem thử trạng thái" (tab mô phỏng `simTabs3`) vì chỉ phục vụ xem mockup. Màu và nhãn trạng thái: hằng `ATT` trong script mockup (Có mặt/Đi muộn/Vắng/Có phép). **Spec:** TC-05.

**Files:**
- Create: `src/features/attendance/ui/teacher/{attendance-screen,attendance-session-list,attendance-roster,attendance-rows}.tsx|ts` + test; `src/app/(portal)/teacher/attendance/page.tsx`.
- Modify: `src/features/attendance/index.ts`, `src/config/nav.ts` (`{ label: "Điểm danh", href: "/teacher/attendance", icon: "user-check" }`).

**Interfaces:**
- Consumes: `useMyLessonSessions(filters)` (`@/features/lesson`), `useLessonSession(id)`, `useClassMembers(classId)`, Task 6 hooks.
- Produces:
  - `eligibleAt<T extends { valid_from: string; valid_to: string | null }>(items: T[], startsAtIso: string): T[]` (`valid_from <= starts_at` và `valid_to` null hoặc `> starts_at`), trong `attendance-rows.ts`.
  - `interface AttendanceRow { studentId: string; fullName: string; recordId: string | null; savedStatus: AttendanceStatus | null; savedNote: string | null }`; `buildAttendanceRows(roster: ClassMemberRosterItem[], entries: AttendanceEntry[], startsAtIso: string): AttendanceRow[]` (roster hiệu lực tại giờ bắt đầu, ghép bản ghi đã có, sắp theo tên).
  - `AttendanceScreen()`: cột trái liệt kê buổi của giáo viên từ 14 ngày trước tới 7 ngày sau (`useMyLessonSessions({ from, until })`), buổi chọn qua `?session=<id>` (mặc định buổi hôm nay gần nhất, nếu không có thì buổi mới nhất đã qua); mỗi mục: tên lớp, ngày giờ, chip `sessionStatusBadge`, "đã điểm danh x/y".
  - Bảng: dòng đã lưu hiển thị trạng thái đã lưu (sửa qua Task 9); dòng chưa lưu chọn nhanh 4 trạng thái (state cục bộ `Record<studentId, AttendanceStatus>`); "Tất cả có mặt" chỉ đặt cho dòng chưa lưu và chưa chọn; "Lưu điểm danh" gửi `bulk` với các dòng đã chọn; bộ đếm Có mặt/Đi muộn/Vắng/Có phép/Chưa điểm danh (từ đã lưu + nháp).
  - Lỗi 422 `STUDENT_NOT_ELIGIBLE`: banner "N học sinh không còn trong lớp vào thời điểm buổi học. Cả lô chưa được lưu." + nút "Bỏ các em này khỏi lô rồi lưu lại" (loại `notEligibleStudentIds` khỏi nháp rồi gửi lại). 409 `SESSION_CANCELLED`: refetch buổi và hiện toast lỗi.
  - Buổi `cancelled`: khóa nút chọn trạng thái và nút Lưu.

- [ ] **Step 1: Test thất bại:** `attendance-rows.test.ts` (`eligibleAt` ở biên bằng `starts_at`, học sinh rời lớp trước giờ bắt đầu bị loại, `buildAttendanceRows` ghép đúng `recordId`); `attendance-screen.test.tsx`: `it("Tất cả có mặt chỉ đặt cho dòng chưa lưu rồi Lưu gửi đúng body")`, `it("lỗi STUDENT_NOT_ELIGIBLE hiện banner và nút bỏ các em đó rồi lưu lại gửi lô đã loại")` (Review Focus 3), `it("buổi đã hủy khóa nút trạng thái và nút Lưu")` (Review Focus 4).
- [ ] **Step 2:** Chạy, expected FAIL.
- [ ] **Step 3:** Implement. Giờ hiện tại dùng `new Date()` tại một chỗ, truyền vào hàm thuần để test.
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint`.
- [ ] **Step 5:** Commit `feat(attendance): teacher attendance screen with roster and bulk save (TC-05)`, push.

---

### Task 8: TC-05 — nội dung/ghi chú, hoàn tất, hủy, banner

**Mockup:** `isAttendance` dòng 315–374 (khối "Nội dung buổi học": hai ô `contentVal`/`notesVal`, ô lý do khi buổi đã hoàn tất, nút Hoàn tất, Hủy buổi học, banner `hasAttBanner`); hộp thoại Hoàn tất dòng 1049, Hủy dòng 1053. Nhãn ô theo `Global Constraints` (đã sửa trong mockup).

**Files:**
- Create: `src/features/attendance/ui/teacher/attendance-content-form.tsx` + test.
- Modify: `attendance-screen.tsx`.

**Interfaces:**
- Consumes: `useUpdateLessonSession(classId)`, `SessionCompleteDialog`, `SessionCancelDialog` (cả hai từ `ui/shared` qua một export công khai mới ở `@/features/lesson`: thêm `SessionCompleteDialog`, `SessionCancelDialog` vào `index.ts`), `sessionStatusBadge`, `hasSessionEnded`.
- Produces: `AttendanceContentForm({ session }: { session: LessonSessionDetail })` — `lesson_content` (nhãn "Nội dung đã dạy (học sinh và phụ huynh sẽ thấy)"), `notes` (nhãn "Ghi chú nội bộ (chỉ giáo viên và quản trị viên thấy)", hint "Học sinh và phụ huynh không thấy ghi chú này."), ô "Lý do sửa (bắt buộc vì buổi đã hoàn tất)" chỉ khi `status === "completed"`, thiếu lý do báo "Nhập lý do sửa". Nút Hoàn tất khi `status === "scheduled"`, hộp thoại hỏi "Còn N học sinh chưa điểm danh. Vẫn hoàn tất buổi học?" khi N > 0, ngược lại "Xác nhận hoàn tất buổi học này?". Nút "Hủy buổi học" chỉ khi `status !== "cancelled" && !hasSessionEnded(session.ends_at)`. Banner: `review_required` → "Buổi này đã hủy sau khi có điểm danh. Hãy rà soát lại các bản ghi. Lý do hủy: {cancellation_reason}"; đã hủy không cờ → "Buổi này đã hủy. Lý do hủy: {cancellation_reason}".

- [ ] **Step 1: Test thất bại:** `it("buổi đã qua giờ vẫn scheduled: chip Chờ hoàn tất, không có nút Hủy buổi học, vẫn có Hoàn tất")` (Review Focus 5), `it("lưu nội dung buổi đã hoàn tất yêu cầu lý do")`, `it("banner hiện lý do hủy và lời nhắc rà soát khi review_required")`, `it("nhãn ghi chú là 'Ghi chú nội bộ (chỉ giáo viên và quản trị viên thấy)'")`, `it("hoàn tất khi còn học sinh chưa điểm danh hỏi lại với số lượng")`.
- [ ] **Step 2:** Chạy, expected FAIL.
- [ ] **Step 3:** Implement; truyền chi tiết buổi cho hai hộp thoại dùng chung (kiểu `LessonSessionListItem` là tập con của `LessonSessionDetail`).
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint`.
- [ ] **Step 5:** Commit `feat(attendance): session content, complete and cancel on attendance screen (TC-05)`, push.

---

### Task 9: TC-05 — sửa từng bản ghi và lịch sử sửa

**Mockup:** hộp thoại "Sửa điểm danh" dòng 1039 (chọn trạng thái, ô Ghi chú, ô "Lý do sửa" `required`, lỗi "Nhập lý do sửa", khối "Các lần sửa trước" dạng "Từ → Đến: lý do"). **Spec:** TC-05 mục lịch sử sửa (`actor_name` + thời điểm, chốt 2026-10-28).

**Files:**
- Create: `src/features/attendance/ui/teacher/attendance-record-edit-dialog.tsx` + test.
- Modify: `attendance-roster.tsx` (dòng đã lưu mở hộp thoại).

**Interfaces:**
- Consumes: `useUpdateAttendanceRecord(sessionId)`, `useAuditLogs({ entity_type: "AttendanceRecord", entity_id }, { enabled: open })`.
- Produces: `AttendanceRecordEditDialog({ open, sessionId, row, onClose }: { open: boolean; sessionId: string; row: AttendanceRow; onClose: () => void })`. Khi bấm Lưu: bắt buộc lý do (client báo "Nhập lý do sửa"), `PATCH` body `{ status, note, reason }`. Khối lịch sử: mỗi log một dòng "{nhãn trước} → {nhãn sau}: {reason} · {actor_name ?? "—"} · {formatDateTime(occurred_at)}" lấy từ `before_data.status`/`after_data.status` (snapshot BE là `{ status, note }`), sắp mới nhất trước. Buổi `cancelled` (409): hiện lỗi từ `error.message`.

- [ ] **Step 1: Test thất bại:** `it("thiếu lý do thì báo 'Nhập lý do sửa' và không gửi PATCH")`, `it("gửi PATCH với status, note và reason rồi đóng hộp thoại")`, `it("hiện các lần sửa trước với tên người sửa, thời điểm và lý do")`.
- [ ] **Step 2:** Chạy, expected FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** `pnpm test && pnpm typecheck && pnpm lint`.
- [ ] **Step 5:** Commit `feat(attendance): edit a single attendance record with edit history (TC-05)`, push.

---

### Task 10: Chạy thử trên màn thật và đóng lát

**Files:** `docs/product/screens/teacher-van-hanh.md`, `docs/product/screens/mockup-plan.md`, `docs/architecture/open-decisions.md` (nếu có chỗ mockup lệch), `docs/STATE.md`.

- [ ] **Step 1:** Dựng môi trường (`piclass-infra/local`: `docker compose ps`), đăng nhập bằng tài khoản Teacher có phân công lớp, đi hết kịch bản thật: mở Lớp của tôi → chi tiết lớp (4 tab) → Lịch dạy → Điểm danh một buổi (lưu, sửa một em kèm lý do, xem lịch sử) → hoàn tất buổi → hủy một buổi dự kiến. Dùng `curl` với cookie phiên khi không có trình duyệt; ghi rõ nếu chưa xem trên trình duyệt thật.
- [ ] **Step 2:** Tự so khớp từng màn đã code với đúng đoạn mockup (số dòng ở các Task), không chỉ chạy test; ghi mọi chỗ lệch có chủ ý vào `teacher-van-hanh.md` (như đã làm ở Admin): tab Lịch học gộp Lịch + Buổi học (`ARCH-37`), chưa có tab "Bài của lớp", bảng Học sinh chỉ hai cột, chưa có huy hiệu đơn xin phép ở TC-05.
- [ ] **Step 3:** Review cuối nhánh (opus, effort medium) trước khi báo xong; xử lý góp ý.
- [ ] **Step 4:** Đánh dấu lát 1 xong ở `mockup-plan.md`; hỏi user xác nhận các mục `STATE.md` đã xong rồi cập nhật (lát 1 chuyển sang lát 2: Đơn xin phép & Tổng quan); dời spec/plan của lát sang `docs/archive/superpowers/` khi user xác nhận.
- [ ] **Step 5:** Commit docs, push; hỏi user có mở PR và merge không.
