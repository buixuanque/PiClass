# FE lát 4d Giáo viên (TC-14 Tiến độ học sinh) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dựng màn "Tiến độ học sinh" của Giáo viên (menu "Tiến độ", `/teacher/progress`) đúng mockup, gọi API thật đã có từ BE lát 4d.

**Architecture:** Hook dữ liệu nằm ở `features/results` (tiến độ, lịch sử điểm, nhận xét) và `features/attendance` (điểm danh theo học sinh). Các khối giao diện của từng feature đặt ở `ui/teacher` của feature đó; màn ghép (chọn lớp, danh sách học sinh, tiêu đề, bộ lọc, bố cục) đặt ở `features/academic/ui/teacher` vì cần `useClasses`, `useClassMembers` và `results`/`attendance`; `results` không import `academic` (tránh vòng, F5).

**Tech Stack:** Next.js 16, React 19, TypeScript, TanStack Query, React Hook Form + Zod, Vitest + Testing Library + MSW.

**Spec:** `docs/requirements/decision-log.md` `DEC-LRN-031`; màn `docs/product/screens/teacher-van-hanh.md` mục TC-14; BE plan `docs/superpowers/plans/2026-11-02-be-teacher-progress-4d.md`; hợp đồng API đọc từ `piclass-api/storage/api-docs/openapi.json` (export lại trước khi code: `docker compose exec api php artisan scramble:export` ở `piclass-infra/local`).

## Global Constraints

- Quy ước code: `piclass-web/CLAUDE.md` (F1–F9); port UI đúng từng giá trị từ mockup, đọc `docs/rules/design-system.md` trước khi dựng UI; không dùng ảnh chụp để so style; nghi ngờ thì ghi `docs/architecture/open-decisions.md`.
- Mockup là nguồn chuẩn: `piclass-mockup/design-system/PiClass Giáo viên.dc.html`. Markup màn: dòng 910-995 (`isProgress`). Dữ liệu mẫu và nhãn: dòng 1265 (`ATT`), 1347 (`NOTES0`), 1348 (`PDATES`), 1896-1903 (`psBase`, `bars`, `strip`, `ps`), 2125 (`stepStu`), 2163 (`forbTitle`, `forbText`). Lối vào từ chi tiết lớp: dòng 1507 (`openProgress(cls, sid)`), 1714 (`cdRoster.open`), 2011 (`clsProgRows.open`). Nav: dòng 1689 (mục `progress`, icon `chart-line`).
- Thời gian hiển thị qua `lib/time.ts` (F8). Chữ hiển thị tiếng Việt gõ thẳng trong component.
- FE không tự quyết quyền (F7): ẩn/hiện theo dữ liệu BE; lỗi 404 hiện "không tìm thấy" theo `error.code`.
- Comment trong code tiếng Việt; tên biến/hàm/file/test tiếng Anh; commit message tiếng Anh. Nhánh từ `origin/main` của `piclass-web` (`git fetch origin` trước). Lệnh: `pnpm test`, `pnpm lint`, `pnpm typecheck`, `pnpm build`.
- Nút "Nhắn phụ huynh" giữ trên giao diện, bấm chỉ hiện toast `info` "Chức năng đang phát triển" (`DEC-LRN-031` mục 5). Mục "Cần chú ý" (chấm cam, tiêu đề "Cần chú ý (rê chuột để xem lý do)", huy hiệu "Cần chú ý/Ổn định" cạnh tên) **không làm** (`DEC-LRN-031` mục 5). Nhận xét đã gửi không sửa/xóa (không có nút Sửa/Xóa).
- Không làm: chip "Bài tập đã nộp" (`ps.hwLabel`, API chưa có), thang điểm /10 (API trả %), danh sách học sinh hiện "chuyên cần %" (cần N request điểm danh).

## Quyết định của plan (cần user xem)

- Thay các chỗ mockup dùng số mẫu bằng dữ liệu thật: điểm hiển thị theo **%** (API trả `percentage`), ví dụ "82,5%" (khớp `ClassProgressTab` hiện có), không đổi sang thang 10.
- Danh sách học sinh bên trái mỗi dòng chỉ hiện "Điểm TB x%" (dùng `useStudentsProgress` có sẵn, N request đã được chấp nhận ở V1); bỏ phần "· att%" của mockup.
- Ô "Chuyên cần" lấy `attendance_rate` (null thì "—"); ô "Buổi vắng" lấy `absent_count`; ô "Bài tập đã nộp" bỏ nên hàng này còn hai ô (lưới 3 cột giữ nguyên bề rộng, ô trống để trống).
- Biểu đồ cột "Điểm các bài gần đây": làm thật từ `GET /students/{id}/results?limit=8` (đảo thứ tự để cũ → mới), nhãn dưới cột là ngày `dd/MM` thay cho "B.1..B.8", tiêu đề bỏ chữ "bản mẫu, chờ API". Bấm cột chưa làm (chờ màn bài của TC-13).
- Bộ lọc khoảng ngày: Tất cả / 30 ngày / 7 ngày như mockup, gửi `from` = hôm nay trừ N ngày (`todayIsoVn`), bộ lọc môn gửi `subject_id`. Lọc áp cho khối số tổng quan, "Theo môn" và biểu đồ; không áp cho điểm danh và nhận xét.
- Vào màn từ chi tiết lớp: bấm tên học sinh ở tab Học sinh và hàng ở tab Tiến độ dẫn tới `/teacher/progress?classId=<lớp>&studentId=<em>`. Không có `studentId` thì chọn em đầu danh sách.
- Mục nav "Tiến độ" đặt sau "Ngân hàng câu hỏi" (đúng thứ tự mockup, dòng 1689).

## Review Focus

- Học sinh chưa có bài công bố: ô điểm TB hiện "—", câu "Chưa có điểm. Có N bài đang chờ chấm.", không hiện "Theo môn" (Task 4).
- Lớp rỗng hoặc chưa chọn được học sinh: hiện trạng thái rỗng, không lỗi `undefined` (Task 5).
- Đổi lớp phải xóa học sinh đang chọn và nội dung nhận xét đang gõ; đổi học sinh cũng xóa nháp (Task 5, 6).
- Gửi nhận xét trống/chỉ khoảng trắng: không gọi API; quá 2000 ký tự: báo lỗi; BE 422 `STUDENT_NOTE_CLASS_INVALID` hiện thông báo (Task 6).
- Sau khi gửi, nhận xét mới hiện đầu danh sách mà không tải lại trang (Task 6).
- Một trong các request (điểm danh, lịch sử, nhận xét) lỗi thì chỉ khối đó hiện lỗi, các khối khác vẫn hiển thị (Task 4, 6).
- 404 khi học sinh ngoài phạm vi: hiện thông báo "Không tìm thấy hoặc đã hết phân công" (mockup `forbTitle`/`forbText`, dòng 2163) (Task 5).

---

### Task 1: Hook dữ liệu `results`: tiến độ có bộ lọc, lịch sử điểm, nhận xét

**Files:**
- Modify: `src/features/results/api/progress.ts` (mở rộng `StudentProgressFilters`, thêm `by_subject` vào `StudentProgress`)
- Create: `src/features/results/api/history.ts`, `src/features/results/api/notes.ts`, `src/features/results/api/keys.ts`
- Modify: `src/features/results/index.ts`
- Test: `src/features/results/api/progress.test.tsx` (thêm), `history.test.tsx`, `notes.test.tsx`

**Interfaces:**
- Produces (types): `StudentProgress` thêm `by_subject: { subject_id: string | null; subject_name: string | null; average_percentage: number; graded_count: number }[]`; `StudentProgressFilters` thành `{ classId?: string; subjectId?: string; from?: string; to?: string }`; `StudentResult { result_id: string; assessment_id: string; title: string; class_id: string | null; subject_id: string | null; subject_name: string | null; score: number; max_score: number; percentage: number; received_at: string }`; `StudentNote { id: string; student_id: string; class_id: string | null; class_name: string | null; content: string; author: { id: string; name: string | null }; created_at: string }`.
- Produces (hooks): `useStudentProgress(studentId, filters)` (key theo cả bốn bộ lọc), `useStudentResults(studentId, { classId?, subjectId?, from?, to?, limit? })` → `GET /students/{id}/results` (response bọc `data`), `useStudentNotes(studentId)` → `GET /students/{id}/notes`, `useCreateStudentNote(studentId)` → `POST /students/{id}/notes` body `{ content, class_id? }`, `onSuccess` invalidate key notes của em đó. Query key gom trong `resultsKeys` ở `keys.ts` (`progress`, `results`, `notes`).
- `useStudentsProgress` giữ nguyên chữ ký và vẫn dùng cùng key với `useStudentProgress`.

- [ ] **Step 1: Viết test (MSW) trước**, mỗi hook một `describe`: tiến độ gửi đủ `class_id/subject_id/from/to` qua query string và trả `by_subject`; lịch sử gửi `limit` và đọc `data`; `useStudentNotes` đọc `data`; `useCreateStudentNote` POST đúng body và sau khi thành công danh sách nhận xét được tải lại (handler GET trả thêm một phần tử ở lần gọi thứ hai).
- [ ] **Step 2: Chạy `pnpm test src/features/results`**, Expected: FAIL (hook/field chưa có).
- [ ] **Step 3: Implement** types, hàm gọi trong object `resultsApi`, hook và `resultsKeys`; export thêm trong `index.ts` (hook, type, `StudentResult`, `StudentNote`).
- [ ] **Step 4: Chạy lại test + `pnpm typecheck`**, Expected: PASS; `class-progress-tab.test.tsx` cũ vẫn PASS.
- [ ] **Step 5: Commit** `feat(results): add student results history and notes hooks`.

### Task 2: Hook dữ liệu `attendance`: điểm danh theo học sinh

**Files:**
- Modify: `src/features/attendance/api/attendance.ts`, `keys.ts`, `types.ts`, `src/features/attendance/index.ts`
- Test: `src/features/attendance/api/attendance.test.tsx` (thêm)

**Interfaces:**
- Produces: `StudentAttendance { attendance_rate: number | null; present_count: number; late_count: number; absent_count: number; excused_count: number; records: { lesson_session_id: string; class_id: string; class_name: string | null; starts_at: string; status: AttendanceStatus }[] }` (response phẳng, không bọc `data`); `useStudentAttendance(studentId, { classId?, limit? })` → `GET /students/{id}/attendance`; `attendanceKeys.student(studentId, classId, limit)`.

- [ ] **Step 1: Viết test (MSW)**: hook gửi `class_id` và `limit` qua query, trả đúng các trường; học sinh chưa có điểm danh (`attendance_rate: null`, `records: []`) không lỗi.
- [ ] **Step 2: Chạy `pnpm test src/features/attendance/api`**, Expected: FAIL.
- [ ] **Step 3: Implement** type, `attendanceApi.student`, hook, key; export hook và type từ `index.ts`.
- [ ] **Step 4: Chạy lại test + typecheck**, Expected: PASS.
- [ ] **Step 5: Commit** `feat(attendance): add student attendance hook`.

### Task 3: Khối "Điểm danh 10 buổi gần nhất" (attendance/ui/teacher)

**Files:**
- Create: `src/features/attendance/ui/teacher/student-attendance-card.tsx`
- Test: `src/features/attendance/ui/teacher/student-attendance-card.test.tsx`
- Modify: `src/features/attendance/index.ts` (export `StudentAttendanceCard`)

**Interfaces:**
- Consumes: `useStudentAttendance` (Task 2).
- Produces: `<StudentAttendanceCard studentId={string} />` render thẻ theo mockup dòng 965-979 (ba ô thống kê hàng "Chuyên cần %" / "Buổi vắng" thuộc màn ghép nên **không** nằm ở đây; component chỉ gồm thẻ "Điểm danh 10 buổi gần nhất" (dòng 965-979): lưới 10 ô, mỗi ô nền `soft` + icon theo trạng thái, ngày `dd/MM` bên dưới, hàng chú thích bốn màu). Nhãn, icon và màu từng trạng thái đọc từ `ATT` ở mockup dòng 1265. Ngày qua `formatDate` rút gọn `dd/MM` (hàm helper nội bộ trong file, dùng `lib/time.ts`). Mockup dùng thứ tự cũ → mới theo `PDATES`; API trả mới → cũ nên đảo lại.

- [ ] **Step 1: Viết test**: render 3 bản ghi → 3 ô theo thứ tự cũ → mới với đúng `aria-label`/text trạng thái; chưa có điểm danh → câu "Chưa có điểm danh"; request lỗi → thông báo lỗi trong thẻ; đang tải → skeleton.
- [ ] **Step 2: Chạy test, Expected: FAIL.**
- [ ] **Step 3: Implement** component bám mockup dòng 965-979 (đọc đoạn này, không chỉ đặc tả).
- [ ] **Step 4: Chạy test + lint, Expected: PASS.**
- [ ] **Step 5: Commit** `feat(attendance): add student attendance card`.

### Task 4: Các khối số liệu của `results/ui/teacher`

**Files:**
- Create: `src/features/results/ui/teacher/student-progress-summary.tsx`, `recent-scores-card.tsx`
- Test: `student-progress-summary.test.tsx`, `recent-scores-card.test.tsx`
- Modify: `src/features/results/index.ts`

**Interfaces:**
- Consumes: `useStudentProgress`, `useStudentResults` (Task 1), `StatTile`, `Card` từ `components/ui`.
- Produces: `<StudentProgressSummary studentId filters />` gồm ba ô `StatTile` ("Điểm trung bình (đã công bố)" icon `star` màu reward, "Bài đã có điểm" icon `clipboard-check` màu success, "Bài đang chờ chấm" icon `hourglass` màu warning), thẻ "Theo môn" (mockup dòng 946-948: mỗi môn tên, "N bài", %, thanh tiến độ màu cam khi dưới 70%) hoặc, khi `average_percentage === null`, khối "Chưa có điểm. Có N bài đang chờ chấm." (mockup dòng 945); `<RecentScoresCard studentId filters />` là thẻ biểu đồ cột (mockup dòng 954-964: cột cao theo %, màu `coral` dưới 65, `green` từ 80, còn lại `blue`; số % phía trên cột, ngày phía dưới), lấy tối đa 8 bài gần nhất và đảo cũ → mới; rỗng thì "Chưa có bài nào được công bố". Mỗi khối tự xử lý đang tải (skeleton) và lỗi (chỉ khối đó).

- [ ] **Step 1: Viết test** cho từng khối với MSW: có dữ liệu (ba ô, "Theo môn" hai dòng, 3 cột biểu đồ đúng thứ tự cũ → mới và màu); `average_percentage: null` + `pending_count: 1` → câu rỗng và không có "Theo môn"; request lỗi 500 → chỉ khối đó báo lỗi.
- [ ] **Step 2: Chạy test, Expected: FAIL.**
- [ ] **Step 3: Implement** hai component bám mockup dòng 940-964; định dạng % qua helper dùng chung với `ClassProgressTab` (chuyển `formatPercent` ra `results/ui/teacher/format-percent.ts` và import ở cả hai nơi; không nhân đôi hàm).
- [ ] **Step 4: Chạy test + lint + typecheck, Expected: PASS** (kể cả `class-progress-tab.test.tsx`).
- [ ] **Step 5: Commit** `feat(results): add student progress summary and recent scores cards`.

### Task 5: Khối "Nhận xét gửi phụ huynh" (results/ui/teacher)

**Files:**
- Create: `src/features/results/ui/teacher/student-notes-card.tsx`
- Modify: `src/features/results/api/schemas.ts` (Zod `studentNoteSchema`: `content` trim, bắt buộc, tối đa 2000), `index.ts`
- Test: `student-notes-card.test.tsx`

**Interfaces:**
- Consumes: `useStudentNotes`, `useCreateStudentNote` (Task 1), `Input` (multiline), `Button`, `toast`.
- Produces: `<StudentNotesCard studentId classId />` theo mockup dòng 981-993: danh sách nhận xét (cột ngày `dd/MM` rộng 56px, nội dung), ô nhập 2 dòng placeholder "Viết nhận xét về tiến độ của em trong tuần", nút "Gửi nhận xét" (icon `send`) căn phải. Gửi kèm `class_id: classId`. Không có Sửa/Xóa. Chỉ gửi khi nội dung đã trim khác rỗng; thành công thì xóa ô nhập và toast `success` "Đã gửi nhận xét". Lỗi BE `VALIDATION_FAILED` hiện ở dưới ô nhập; `STUDENT_NOTE_CLASS_INVALID` hiện toast lỗi với message BE. Component nhận `key={studentId}` từ màn cha để xóa nháp khi đổi em.

- [ ] **Step 1: Viết test**: danh sách hiện mới nhất trên cùng; gõ chữ rồi bấm gửi → POST đúng `{content, class_id}` và nhận xét mới xuất hiện đầu danh sách, ô nhập rỗng; chỉ gõ khoảng trắng → không có request; 2001 ký tự → thông báo lỗi dưới ô nhập, không request; BE trả 422 `STUDENT_NOTE_CLASS_INVALID` → toast lỗi, nội dung giữ nguyên; chưa có nhận xét → câu rỗng; danh sách lỗi tải → thông báo lỗi trong thẻ.
- [ ] **Step 2: Chạy test, Expected: FAIL.**
- [ ] **Step 3: Implement** component bám mockup dòng 981-993; form bằng React Hook Form + `studentNoteSchema`.
- [ ] **Step 4: Chạy test + lint, Expected: PASS.**
- [ ] **Step 5: Commit** `feat(results): add student notes card`.

### Task 6: Màn ghép, route, nav và lối vào từ chi tiết lớp

**Files:**
- Create: `src/features/academic/ui/teacher/teacher-student-progress-screen.tsx`, `student-progress-roster.tsx`, `student-progress-header.tsx`, `src/app/(portal)/teacher/progress/page.tsx`
- Modify: `src/features/academic/index.ts` (export `TeacherStudentProgressScreen`), `src/config/nav.ts` (mục "Tiến độ", icon `chart-line`, `href: "/teacher/progress"`), `src/features/academic/ui/teacher/class-roster-tab.tsx`, `class-progress-tab.tsx` (tên/dòng thành liên kết tới `/teacher/progress?classId=&studentId=`)
- Test: `teacher-student-progress-screen.test.tsx`, cập nhật `class-roster-tab`/`class-progress-tab` test, `src/config/nav.test.ts` nếu có

**Interfaces:**
- Consumes: `useClasses`, `useClassMembers` (user-management), `partitionRoster`, `useStudentsProgress`, `StudentProgressSummary`, `RecentScoresCard`, `StudentNotesCard` (results), `StudentAttendanceCard` và `useStudentAttendance` (attendance).
- Produces: `<TeacherStudentProgressScreen />` (đọc `classId`, `studentId` từ `useSearchParams`, ghi lại bằng `router.replace` khi đổi; giống cách `tab=` của chi tiết lớp) với bố cục mockup dòng 910-995: tiêu đề "Tiến độ học sinh" + dòng phụ + ô chọn lớp (`Select`) bên phải; cột trái 300px thẻ danh sách học sinh còn học (`partitionRoster(...).current`, avatar 32, tên, "Điểm TB x%", dòng chọn nền `surface-selected`); cột phải gồm: tiêu đề học sinh (avatar 56, tên 22px, tên lớp và nút `Nhắn phụ huynh` bấm báo "Chức năng đang phát triển"), hàng điều hướng ("Em trước" / "i/N" / "Em sau"; nút ở đầu/cuối vòng lại như mockup `stepStu`), Tabs pill khoảng ngày + `Select` chip môn, `StudentProgressSummary`, hàng hai ô thống kê (Chuyên cần %, Buổi vắng) lấy từ `useStudentAttendance`, `RecentScoresCard`, `StudentAttendanceCard`, `StudentNotesCard` (`key={studentId}`). Danh sách môn của bộ lọc lấy từ `by_subject` của em đang xem cộng "Mọi môn". Đổi lớp xóa `studentId`. 404 từ BE ở bất kỳ request nào của em → khối thay bằng thông báo "Không tìm thấy hoặc đã hết phân công" (mockup dòng 2163). Route `page.tsx` chỉ ghép màn (F1).

- [ ] **Step 1: Viết test màn** (MSW + mock `next/navigation` theo `teacher-class-detail-screen.test.tsx`): mở với `classId` và `studentId` → hiện tên em, ba ô số, các khối con; không có `studentId` → chọn em đầu; bấm em khác → URL đổi (`router.replace` được gọi với `studentId` mới) và nội dung nhận xét đang gõ bị xóa; "Em sau" ở em cuối vòng về em đầu; đổi lớp → danh sách đổi, `studentId` bị xóa; lớp không có học sinh còn học → trạng thái rỗng; 404 của em → thông báo "Không tìm thấy hoặc đã hết phân công"; bấm "Nhắn phụ huynh" → toast "Chức năng đang phát triển" và không có request nào; chọn bộ lọc "7 ngày" → request tiến độ có `from` bằng hôm nay trừ 7 ngày.
- [ ] **Step 2: Cập nhật test tab lớp** (Học sinh, Tiến độ): bấm tên/dòng là liên kết có `href` đúng `/teacher/progress?classId=<lớp>&studentId=<em>`; thêm test nav có mục "Tiến độ".
- [ ] **Step 3: Chạy test, Expected: FAIL.**
- [ ] **Step 4: Implement** màn và ba component con bám mockup dòng 910-995 (đọc đoạn này; port đúng khoảng cách và font size từ markup), route, nav, liên kết ở hai tab. Mỗi file giữ dưới 200 dòng (tách `student-progress-roster.tsx`, `student-progress-header.tsx`).
- [ ] **Step 5: Chạy `pnpm test`, `pnpm lint`, `pnpm typecheck`, `pnpm build`, Expected: tất cả xanh** (gồm `architecture.test.ts`: F5 không vòng `results` ↔ `academic`).
- [ ] **Step 6: Commit** `feat(academic): add teacher student progress screen`.

### Task 7: So khớp mockup, tài liệu, review

**Files:** `docs/STATE.md`, `docs/product/screens/mockup-plan.md` (đánh dấu TC-14 xong), `docs/superpowers/plans/` (dời plan của lát vào `docs/archive/superpowers/` khi lát xong)

- [ ] **Step 1: Chạy ứng dụng thật** (Makefile `piclass-infra/local`), đăng nhập giáo viên có dữ liệu mẫu (`docs/setup/dev-demo-data.md`), mở `/teacher/progress`, làm đúng kịch bản: chọn lớp, đổi em, đổi khoảng ngày và môn, gửi một nhận xét, kiểm tra học sinh chưa có điểm và học sinh chưa có điểm danh.
- [ ] **Step 2: So khớp từng khối với đúng đoạn mockup** (dòng 910-995) bằng cách mở `piclass-mockup/design-system/standalone/PiClass Giáo viên.html` cạnh màn thật; ghi lệch (nếu có) vào `docs/architecture/open-decisions.md`, lệch nhỏ thì sửa tại chỗ.
- [ ] **Step 3:** Cập nhật `STATE.md` (4d xong, cổng Giáo viên đóng) và `mockup-plan.md`.
- [ ] **Step 4:** Gọi reviewer độc lập cuối nhánh với `model: "opus"`, effort medium, giao đủ Review Focus của plan này.

## Self-review

- Spec coverage: số tổng quan, theo môn → Task 4; bài gần đây/biểu đồ (G8) → Task 4; điểm danh (G3) → Task 2-3; nhận xét → Task 5; bộ lọc, điều hướng em trước/sau, nút Nhắn phụ huynh, nav, lối vào → Task 6; mục "Cần chú ý" và chip "Bài tập đã nộp" ghi rõ là không làm.
- Nhất quán tên: `useStudentProgress`/`useStudentResults`/`useStudentNotes`/`useCreateStudentNote` (Task 1) được Task 4-6 dùng; `useStudentAttendance` (Task 2) được Task 3 và 6 dùng; `StudentAttendanceCard` (Task 3) được Task 6 dùng.
