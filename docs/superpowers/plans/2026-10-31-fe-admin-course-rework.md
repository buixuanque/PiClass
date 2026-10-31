# Làm lại mô hình Khóa học, cổng Admin (FE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Chuyển AD-12 (chi tiết khóa học của Admin) từ ghi danh và "Lớp đã gắn" sang "Đối tượng của khóa", "Học viên" và "Danh sách mục" theo OpenAPI mới.

**Architecture:** Dữ liệu đối tượng và học viên thuộc `features/academic/api`; dữ liệu mục khóa thuộc `features/assessment/api` (BE để `CourseItem` ở module Assessment, `ARCH-38`). Khối giao diện mục khóa đặt ở `features/assessment/ui/shared` để cổng Giáo viên dùng lại sau; Admin dùng qua `@/features/assessment` (F5). Không có mockup: dựng thẳng theo design system có sẵn (user chốt 2026-10-31, tiền lệ AD-12 ngày 2026-10-26).

**Tech Stack:** Next.js 16, React 19, TypeScript 6, TanStack Query, React Hook Form + Zod, Vitest + MSW + Testing Library. Quy ước: `piclass-web/CLAUDE.md`.

**Spec:** `docs/requirements/decision-log.md` `DEC-LRN-026`; `docs/architecture/decisions/ARCH-38.md`; `docs/product/screens/admin.md` mục AD-12 (được viết lại ở Task 6); OpenAPI `piclass-api/storage/api-docs/openapi.json` (đã export lại 2026-10-31).

## Global Constraints

- Nhánh `feat/admin-course-rework` từ `origin/main` của `piclass-web`. Không push `main`, không force-push, không `--amend` commit đã push.
- Comment code, mô tả test: tiếng Việt. Tên hàm/component/file/test method/commit: tiếng Anh. Chữ trên giao diện: tiếng Việt gõ thẳng trong component. PR: tiếng Việt.
- F2/F3/F5/F7/F8 của `CLAUDE.md`: chỉ `lib/api-client.ts` gọi `fetch`; `api/` không import `ui/`; feature chỉ import feature khác qua `index.ts`; FE không tự quyết quyền (ẩn/hiện theo dữ liệu BE: `course.status`); mọi ngày giờ qua `lib/time.ts`.
- **Không xóa** code ghi danh/liên kết lớp đang dùng chung với cổng Giáo viên (`api/course-enrollments.ts`, `api/course-class-links.ts`, `ui/shared/course-enrollments-tab*`, `course-linked-classes-section*`, `course-link-class-dialog`, `course-unlink-class-dialog`, `course-end-enrollment-dialog`, `ui/teacher/*`, các type cũ trong `types.ts`): endpoint cũ đã bị BE gỡ nên chúng chỉ sống tới khi làm cổng Giáo viên, lúc đó xóa. Cổng Admin chỉ xóa file chỉ Admin dùng (Task 5).
- Mỗi task cuối: `pnpm vitest run <đường dẫn>`, `pnpm lint`, `pnpm tsc --noEmit` xanh. Cuối nhánh: `pnpm test` toàn bộ (kể cả `architecture.test.ts`).
- Review cuối nhánh: subagent **model `opus`** tường minh, effort medium (memory `feedback_review_model_opus_medium`).

## Review Focus

- Khóa `archived`: ẩn nút "Thêm đối tượng" và các hành động ghi mục; vẫn xem được danh sách; BE vẫn chặn (`COURSE_ARCHIVED` 422) nếu gọi, FE hiện nguyên văn message.
- Thêm đối tượng đã có (409 `AUDIENCE_ALREADY_ADDED`) và lớp đã lưu trữ (422 `CLASS_ARCHIVED`): lỗi hiện trong hộp thoại, hộp thoại không đóng.
- Học sinh vừa trực tiếp vừa qua lớp: tab Học viên hiện một dòng với nhiều nguồn, số "Học viên" đếm một lần.
- Khóa chưa có đối tượng, học viên hay mục nào: mỗi khối có trạng thái rỗng riêng, không "0" gây hiểu nhầm lỗi tải.
- Đổi thứ tự mục ở đầu/cuối danh sách: nút "Lên" của mục đầu và "Xuống" của mục cuối không bấm được; sau khi đổi, danh sách tải lại theo `position` BE trả.

---

## File Structure

| File | Việc |
|---|---|
| `src/features/academic/api/types.ts` | Thêm `CourseAudienceItem`, `AddCourseAudienceInput`, `CourseLearner`, `CourseLearnerSource` |
| `src/features/academic/api/keys.ts` | Thêm `courseAudience`, `courseLearners` |
| `src/features/academic/api/course-audience.ts` | Hàm gọi API + hook đối tượng |
| `src/features/academic/api/course-learners.ts` | `useCourseLearners` |
| `src/features/academic/ui/shared/course-audience-section.tsx` | Khối "Đối tượng của khóa" |
| `src/features/academic/ui/shared/course-add-audience-dialog.tsx` | Hộp thoại thêm Lớp hoặc Học sinh |
| `src/features/academic/ui/shared/course-remove-audience-dialog.tsx` | Xác nhận gỡ đối tượng |
| `src/features/academic/ui/admin/course-learners-tab.tsx` | Tab "Học viên" |
| `src/features/assessment/api/types.ts`, `keys.ts`, `course-items.ts` | Type, key, hook mục khóa |
| `src/features/assessment/ui/shared/course-items-section.tsx` | Khối "Danh sách mục" (có/không có quyền sửa theo prop) |
| `src/features/academic/ui/admin/course-detail-screen.tsx` | Ghép lại: tab Tổng quan, Học viên, Danh sách mục |
| `docs/product/screens/admin.md`, `docs/STATE.md` | Task 6 |

---

### Task 1: Lớp dữ liệu đối tượng và học viên (Academic)

**Files:**
- Modify: `src/features/academic/api/types.ts`, `src/features/academic/api/keys.ts`
- Create: `src/features/academic/api/course-audience.ts`, `src/features/academic/api/course-learners.ts`
- Test: `src/features/academic/api/course-audience.test.tsx`, `src/features/academic/api/course-learners.test.tsx` (mẫu: `course-class-links.test.tsx`, MSW + `renderHook`)

**Interfaces:**
- Produces (types):
  - `CourseAudienceItem { id: string; class_id: string | null; class_name: string | null; student_id: string | null; student_name: string | null; created_at: string | null }` (đúng một trong hai cặp `class_*`/`student_*` khác null)
  - `AddCourseAudienceInput { class_id?: string; student_id?: string }` (đúng một trường)
  - `CourseLearnerSource { type: "direct" | "class"; class_id: string | null; class_name: string | null }`
  - `CourseLearner { student_id: string; student_name: string | null; sources: CourseLearnerSource[] }`
- Produces (keys): `academicKeys.courseAudience(courseId)`, `academicKeys.courseLearners(courseId)`
- Produces (hooks): `useCourseAudience(courseId)`, `useAddCourseAudience(courseId)`, `useRemoveCourseAudience(courseId)` (nhận `audienceId: string`), `useCourseLearners(courseId)`. Hai mutation khi thành công invalidate **cả** `courseAudience` và `courseLearners`.

- [ ] **Step 1: Viết test hook (thất bại)**
  - `useCourseAudience`: `GET /api/v1/courses/k1/audience` trả `{data:[{id:"a1",class_id:"c1",class_name:"Toán 9A",student_id:null,student_name:null,created_at:null}]}` → `data[0].class_name === "Toán 9A"`.
  - `useAddCourseAudience`: `POST .../audience` body `{class_id:"c1"}` đúng; sau thành công cả hai query key bị invalidate (spy `client.invalidateQueries`).
  - `useRemoveCourseAudience`: `DELETE .../audience/a1` → 204, invalidate cả hai key.
  - `useCourseLearners`: trả `sources[0].type === "direct"`.
  - `courseId` rỗng: query không chạy (`fetchStatus === "idle"`).
- [ ] **Step 2: Chạy để thấy thất bại** — `pnpm vitest run src/features/academic/api/course-audience.test.tsx` → FAIL (module chưa có).
- [ ] **Step 3: Cài đặt** theo mẫu `course-class-links.ts` (object `courseAudienceApi` với `list/add/remove` + hook; `enabled: Boolean(courseId)`). `add` trả `CourseAudienceItem` (BE bọc `{data}`, `apiClient` đã bỏ lớp bọc như `class-links`).
- [ ] **Step 4: Chạy test** — PASS cả hai file.
- [ ] **Step 5: Commit** — `feat(academic): course audience and learners data layer`.

---

### Task 2: Lớp dữ liệu mục khóa (Assessment)

**Files:**
- Modify: `src/features/assessment/api/types.ts`, `src/features/assessment/api/keys.ts`
- Create: `src/features/assessment/api/course-items.ts`
- Test: `src/features/assessment/api/course-items.test.tsx`

**Interfaces:**
- Produces: `CourseItem { id: string; course_id: string; position: number; type: "assessment"; title: string; assessment_id: string | null; assessment_type: AssessmentType | null; assessment_status: AssessmentStatus | null }` (`AssessmentType`/`AssessmentStatus` có sẵn trong `types.ts`; nếu `type` BE trả chuỗi khác `assessment` thì giữ `string`).
- Produces (keys): `assessmentKeys.courseItems(courseId)`.
- Produces (hooks): `useCourseItems(courseId)`; `useUpdateCourseItem(courseId)` nhận `{ itemId: string; title?: string; position?: number }` (`PATCH /courses/{course}/items/{itemId}`); `useRemoveCourseItem(courseId)` nhận `itemId: string` (`DELETE`, 204). Hai mutation invalidate `courseItems(courseId)`.

- [ ] **Step 1: Viết test (thất bại)** — list trả `data` có `position`; update gửi body chỉ gồm trường được truyền (`{position:1}` không kèm `title`); remove gọi đúng URL; cả hai invalidate `courseItems`.
- [ ] **Step 2: Chạy thấy thất bại** — `pnpm vitest run src/features/assessment/api/course-items.test.tsx`.
- [ ] **Step 3: Cài đặt** theo mẫu `assessments.ts`; `position` có thể về dạng chuỗi số từ BE: chuẩn hóa `Number(...)` trong hàm `list`.
- [ ] **Step 4: Chạy test** — PASS.
- [ ] **Step 5: Commit** — `feat(assessment): course items data layer`.

---

### Task 3: Khối "Đối tượng của khóa" và hai hộp thoại

**Files:**
- Create: `src/features/academic/ui/shared/course-audience-section.tsx`, `course-add-audience-dialog.tsx`, `course-remove-audience-dialog.tsx`
- Test: `course-audience-section.test.tsx`, `course-add-audience-dialog.test.tsx` cùng thư mục (mẫu: `course-linked-classes-section.test.tsx`, `course-enroll-dialog.test.tsx`)

**Interfaces:**
- Consumes: Task 1 hooks; `useClasses()` (`academic/api/classes`); `useStudents({status:"active", per_page:100, name})` từ `@/features/user-management` (Admin gọi được; cổng Giáo viên sẽ truyền nguồn khác ở plan của họ nên hộp thoại nhận `renderStudentPicker` KHÔNG làm bây giờ, YAGNI).
- Produces: `CourseAudienceSection({ courseId: string; courseArchived: boolean })`; `CourseAddAudienceDialog({ open; courseId; courseArchived; existing: CourseAudienceItem[]; onClose })`; `CourseRemoveAudienceDialog({ open; courseId; target: CourseAudienceItem | null; onClose })`.

Hành vi cố định:
- Mỗi dòng: biểu tượng + tên (lớp: `class_name` kèm nhãn "Lớp"; học sinh: `student_name` kèm nhãn "Học sinh") + nút "Gỡ". Ngày thêm hiện qua `formatDate(created_at)` khi khác null.
- Nút "Thêm đối tượng" ẩn khi `courseArchived`; nút "Gỡ" vẫn hiện (spec cũ giữ gỡ khi lưu trữ; BE không chặn gỡ).
- Hộp thoại thêm có hai tab "Lớp" (Select, loại lớp `archived` và lớp đã có trong `existing`) và "Học sinh" (ô tìm theo tên debounce 300ms như `course-enroll-dialog.tsx`, loại học sinh đã có trực tiếp trong `existing`); gửi đúng một trường. Lỗi BE (409, 422) hiện nguyên văn `error.message`, hộp thoại không đóng; thành công thì đóng và reset.
- Hộp thoại gỡ: "Gỡ {tên} khỏi khóa?"; với lớp ghi thêm "Học sinh chỉ thuộc lớp này sẽ không còn thấy khóa; bài đã làm được giữ"; không có ô lý do.
- Trạng thái: đang tải (Skeleton), rỗng ("Khóa này chưa có đối tượng nào."), lỗi tải (thẻ danger như `course-linked-classes-section.tsx`).

- [ ] **Step 1: Viết test (thất bại)**
  - Section: hiện một dòng Lớp và một dòng Học sinh từ MSW; rỗng hiện đúng câu; `courseArchived` ẩn "Thêm đối tượng" nhưng còn "Gỡ".
  - Gỡ: bấm "Gỡ" → xác nhận → `DELETE .../audience/a1` được gọi, không có textbox lý do.
  - Dialog: tab Lớp chọn lớp → `POST` body `{class_id:"c1"}`; tab Học sinh chọn em → body `{student_id:"s1"}`; lớp `archived` và lớp đã có không nằm trong options; 409 `AUDIENCE_ALREADY_ADDED` hiện message và `Dialog` vẫn mở.
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt** ba component theo mẫu `course-linked-classes-section.tsx`, `course-link-class-dialog.tsx`, `course-enroll-dialog.tsx` (dùng `Tabs` của `components/ui/tabs`).
- [ ] **Step 4: Chạy test, lint, tsc** — xanh.
- [ ] **Step 5: Commit** — `feat(academic): course audience section and dialogs`.

---

### Task 4: Khối "Danh sách mục" (Assessment, dùng chung)

**Files:**
- Create: `src/features/assessment/ui/shared/course-items-section.tsx`
- Modify: `src/features/assessment/index.ts` (export `CourseItemsSection`)
- Test: `src/features/assessment/ui/shared/course-items-section.test.tsx`

**Interfaces:**
- Consumes: Task 2 hooks.
- Produces: `CourseItemsSection({ courseId: string; canEdit: boolean; courseArchived: boolean })`. `canEdit` do màn gọi truyền (Admin: true; cổng Giáo viên quyết sau); FE không so sánh vai trò (F7).

Hành vi cố định:
- Mỗi dòng: số thứ tự (`position`), `title`, badge loại bài (`assessment_type`: reuse nhãn đã có trong `assessment` feature) và badge trạng thái (`assessment_status`).
- Khi `canEdit && !courseArchived`: mỗi dòng có "Lên", "Xuống" (gọi `useUpdateCourseItem` với `position` ± 1; "Lên" của mục đầu và "Xuống" của mục cuối `disabled`), "Đổi tên" (hộp thoại một ô tên, bắt buộc, lỗi 422 hiện nguyên văn) và "Gỡ" (xác nhận: "Bài của mục sẽ được lưu trữ, không xóa; bài làm đã có được giữ").
- Khi `courseArchived` hoặc `!canEdit`: chỉ xem.
- Rỗng: "Khóa này chưa có mục nào."; có chú thích một dòng "Giáo viên soạn bài trong phần Bài đánh giá" (giữ ý câu cũ ở tab Bài đánh giá).

- [ ] **Step 1: Viết test (thất bại)** — hiển thị theo `position`; "Lên" ở mục đầu disabled, "Xuống" ở mục cuối disabled; bấm "Xuống" mục 1 gửi `PATCH ... {position:2}`; đổi tên gửi `{title}`; gỡ gọi `DELETE`; `canEdit=false` không có nút nào; rỗng đúng câu.
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt** (có thể tách hộp thoại đổi tên và gỡ thành hai file nhỏ cùng thư mục nếu file vượt 200 dòng).
- [ ] **Step 4: Chạy test, lint, tsc** — xanh, gồm `architecture.test.ts` (F4/F5).
- [ ] **Step 5: Commit** — `feat(assessment): course items section`.

---

### Task 5: Ghép lại màn chi tiết khóa (Admin) và dọn file chỉ Admin dùng

**Files:**
- Modify: `src/features/academic/ui/admin/course-detail-screen.tsx`, `course-detail-screen.test.tsx`
- Create: `src/features/academic/ui/admin/course-learners-tab.tsx` (+ `course-learners-tab.test.tsx`)
- Delete: `src/features/academic/ui/admin/course-enroll-dialog.tsx`, `course-enroll-dialog.test.tsx`
- Modify nếu còn tham chiếu: `course-overview-tab.tsx` (+ test)

**Interfaces:**
- Consumes: `CourseAudienceSection`, `CourseAddAudienceDialog` (Task 3), `CourseItemsSection` từ `@/features/assessment` (Task 4), `useCourseLearners`, `useCourseAudience`, `useCourseItems`.

Hành vi cố định:
- Hero: bỏ nút "Ghi danh"; thêm "Thêm đối tượng" (mở `CourseAddAudienceDialog`) và "Lưu trữ khóa"; cả hai chỉ hiện khi khóa chưa lưu trữ (giữ cách cũ của "Lưu trữ khóa").
- Banner khóa lưu trữ: "Khóa đã lưu trữ: không thêm đối tượng hay mục mới được nữa, vẫn xem được."
- Hai `StatTile`: "Học viên" (số dòng `useCourseLearners`) và "Bài trong khóa" (số dòng `useCourseItems`); khi query lỗi hiện "—", không hiện 0.
- Ba tab: "Tổng quan" (`CourseOverviewTab` + `CourseAudienceSection`), "Học viên · N" (`CourseLearnersTab`), "Danh sách mục · M" (`CourseItemsSection canEdit courseArchived`).
- `CourseLearnersTab`: bảng Họ tên + cột Nguồn (chip "Trực tiếp" hoặc "Lớp {class_name}", nhiều chip nếu nhiều nguồn); tên học sinh → `/admin/students/{student_id}` (AD-05, như tab cũ nếu đã có link; nếu tab cũ không có link thì không thêm); rỗng "Chưa có học viên nào. Thêm lớp hoặc học sinh vào đối tượng của khóa."
- Không còn import `course-enrollments`, `course-linked-classes-section` trong màn Admin.

- [ ] **Step 1: Viết/sửa test (thất bại)** — màn: ba tab đúng tên và số đếm; không còn chữ "Ghi danh" và "Lớp đã gắn"; khóa lưu trữ ẩn "Thêm đối tượng" và "Lưu trữ khóa"; học viên vừa trực tiếp vừa qua lớp hiện một dòng hai chip (Review Focus); `useCourseLearners` lỗi thì StatTile "—".
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt**, xóa hai file ghi danh của Admin, sửa `course-overview-tab` nếu tham chiếu.
- [ ] **Step 4: Chạy** `pnpm test` toàn bộ + lint + tsc + `pnpm build` — xanh. Chạy thử trong trình duyệt với Docker local (`piclass-infra/local`): vào `/admin/courses/<id>`, thêm Lớp, thêm Học sinh, gỡ, đổi tên/thứ tự một mục (cần tạo sẵn mục qua API hoặc tinker); chụp lại hiện trạng, báo user.
- [ ] **Step 5: Commit** — `feat(academic): admin course detail with audience, learners and items`.

---

### Task 6: Tài liệu

**Files:** `docs/product/screens/admin.md` (AD-12, AD-12a), `docs/STATE.md`, `docs/BACKLOG.md` nếu phát sinh.

- [ ] **Step 1:** Viết lại AD-12 theo mô hình mới (đối tượng, học viên, danh sách mục, trạng thái, lỗi `AUDIENCE_ALREADY_ADDED`/`CLASS_ARCHIVED`/`COURSE_ARCHIVED`, API mới); bỏ khối cảnh báo "bản cũ"; thay AD-12a bằng một dòng "đã thay bởi AD-12 (`DEC-LRN-026`)" và cập nhật mục lục đầu file. Dùng `grep -n "AD-12"` rồi `Read` theo `offset`+`limit` (file rất lớn).
- [ ] **Step 2:** `STATE.md`: bước "mockup" đổi thành "không làm mockup, FE từng cổng" và ghi cổng Admin xong; việc kế tiếp: cổng Giáo viên (TC-15, TC-07, TC-09, TC-10, thêm nút Thêm bài cho Admin nếu cần), rồi Học sinh (ST-09). Còn ≤ 60 dòng.
- [ ] **Step 3:** Commit — `docs: rewrite AD-12 for course audience model`.

---

## Self-Review

- Spec coverage: đối tượng (Task 1, 3), học viên tính khi cần (Task 1, 5), danh sách mục xem/đổi tên/thứ tự/gỡ (Task 2, 4), khóa lưu trữ (Task 3, 4, 5), lỗi nghiệp vụ (Task 3), tài liệu (Task 6). Chưa làm có chủ đích: thêm/soạn mục (cần TC-10, thuộc cổng Giáo viên), `PATCH /courses` và danh sách khóa (không đổi).
- Nhất quán tên: `useCourseAudience`/`useAddCourseAudience`/`useRemoveCourseAudience`, `useCourseLearners`, `useCourseItems`/`useUpdateCourseItem`/`useRemoveCourseItem`, `CourseItemsSection`, `CourseAudienceSection` dùng giống nhau ở mọi task.
- Rủi ro đã biết: `position` BE có thể trả chuỗi (xử lý ở Task 2); OpenAPI của `assessment_type`/`assessment_status` là chuỗi trần, giữ kiểu hẹp theo type có sẵn.
