# Làm lại mô hình Khóa học, cổng Giáo viên (FE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Chuyển TC-15 (khóa học của Giáo viên) từ ghi danh và "Lớp đã gắn" sang đối tượng, học viên, mục khóa (có "Soạn bài mới cho khóa" và "Lấy từ bài của tôi"); bỏ lựa chọn "Khóa học" ở TC-10; dọn code ghi danh cũ.

**Architecture:** Tái dùng khối của cổng Admin: `CourseAudienceSection`, `CourseLearnersTab` (chuyển sang `ui/shared`), `CourseItemsSection` (`canEdit`). Giáo viên không gọi được `GET /students` (403), nên hộp thoại thêm học sinh nhận một "bộ chọn học sinh" thay thế được: Admin dùng ô tìm, Giáo viên chọn trong thành viên các lớp mình dạy (G4). Việc tạo bài với `course_id` do BE tự thêm mục khóa; việc lấy từ bài cá nhân dùng `POST /courses/{id}/items/copy`.

**Tech Stack:** Next.js 16, React 19, TypeScript 6, TanStack Query, Vitest + MSW + Testing Library. Quy ước: `piclass-web/CLAUDE.md`.

**Spec:** `docs/requirements/decision-log.md` `DEC-LRN-026`, `DEC-LRN-012`; `docs/architecture/decisions/ARCH-38.md`; `docs/product/screens/teacher-van-hanh.md` TC-15 (viết lại ở Task 9); `docs/product/screens/teacher-bai-danh-gia.md` TC-10 (ghi chú `DEC-LRN-026`); OpenAPI `piclass-api/storage/api-docs/openapi.json`.

## Global Constraints

- Hai lát, mỗi lát một nhánh từ `origin/main` của `piclass-web` và một PR: **lát 1** (Task 1–6) nhánh `feat/teacher-course-rework`, **lát 2** (Task 7–8) nhánh `feat/teacher-course-cleanup`. Docs (Task 9) đi cùng nhánh docs `docs/plan-teacher-course-fe`. Không push `main`, không force-push, không `--amend` commit đã push; PR và merge chỉ khi user yêu cầu.
- Không mockup (user chốt 2026-10-31): dựng theo design system và mẫu có sẵn.
- Comment code, mô tả test: tiếng Việt. Tên hàm/component/file/test method/commit: tiếng Anh. Chữ giao diện tiếng Việt gõ thẳng, **xưng "cô" như các màn Giáo viên hiện có** (xem `teacher-course-detail-screen.tsx`).
- F2/F3/F4/F5/F7/F8 của `CLAUDE.md`: `ui/teacher` không import `ui/admin`; feature chỉ import feature khác qua `index.ts`; FE chỉ ẩn/hiện theo dữ liệu BE (`owner_teacher_id` null = khóa dùng chung), không so sánh vai trò.
- **Khóa dùng chung (Giáo viên chỉ xem):** `GET /courses/{id}` và `/items` trả 200; `/audience`, `/learners` và mọi ghi trả **403**. FE không gọi `/audience`, `/learners` cho khóa dùng chung (truyền courseId rỗng vào hook), vẫn cho xem mục ở chế độ chỉ đọc.
- Mỗi task cuối: `pnpm vitest run <đường dẫn>`, `pnpm lint`, `pnpm tsc --noEmit` xanh. Cuối mỗi lát: `pnpm test` toàn bộ, `pnpm build`.
- Review cuối mỗi lát: subagent **model `opus`** tường minh, effort medium.

## Review Focus

- Khóa dùng chung: không có request nào tới `/audience` hoặc `/learners` (MSW `onUnhandledFrame: "error"` canh), không có nút ghi, mục hiện chỉ đọc.
- Khóa `archived` của tôi: ẩn "Thêm đối tượng", "Soạn bài mới cho khóa", "Lấy từ bài của tôi"; BE vẫn trả `COURSE_ARCHIVED`, FE hiện nguyên văn.
- Chọn học sinh để thêm: lớp không có học sinh nào chưa thuộc đối tượng thì hiện "Không còn học sinh để chọn", không bấm Thêm được; học sinh đã là đối tượng trực tiếp không nằm trong danh sách.
- "Lấy từ bài của tôi": bài đã là bài của khóa không xuất hiện (danh sách `GET /assessments` chỉ có bài cá nhân); sao chép xong mục mới hiện trong danh sách mục, bài gốc không đổi.
- "Soạn bài mới cho khóa": tạo xong mở trình soạn của bài; trình soạn của bài thuộc khóa **không có tab Đối tượng** (BE trả 422 `COURSE_ASSESSMENT_NO_AUDIENCE` nếu giao) và có đường quay về khóa.
- Xóa code ghi danh cũ không làm vỡ màn nào khác (`architecture.test.ts`, `tsc`, grep không còn tham chiếu).

---

## File Structure

| File | Việc |
|---|---|
| `academic/ui/shared/course-add-audience-dialog.tsx`, `course-audience-section.tsx` | Nhận prop `studentPicker` (mặc định ô tìm của Admin) |
| `academic/ui/shared/course-audience-roster-picker.tsx` (mới) | Bộ chọn học sinh theo lớp mình dạy cho Giáo viên |
| `academic/ui/admin/course-learners-tab.tsx` → `academic/ui/shared/` | Chuyển để Giáo viên dùng (F4) |
| `academic/ui/teacher/teacher-course-detail-screen.tsx` | Ghép lại: tab Đối tượng, Học viên, Mục của khóa |
| `assessment/api/course-items.ts`, `types.ts` | Thêm `useCopyAssessmentToCourse`; `Assessment.course_id` |
| `assessment/ui/teacher/course-item-actions.tsx` (mới) | Nút "Soạn bài mới cho khóa", "Lấy từ bài của tôi" và hộp thoại |
| `assessment/ui/teacher/create-assessment-dialog.tsx` | Nhận `courseId` tùy chọn |
| `assessment/ui/teacher/assessment-editor-screen.tsx`, `assessment-audience-tab.tsx` | Bài của khóa: ẩn tab Đối tượng; bài cá nhân: bỏ thẻ Khóa học |
| Xóa ở Task 7 | `api/course-enrollments*`, `api/course-class-links*`, `ui/shared/course-enrollments-tab*`, `course-end-enrollment-dialog`, `course-linked-classes-section*`, `course-link-class-dialog`, `course-unlink-class-dialog`, `ui/teacher/teacher-course-enroll-dialog`, `teacher-course-assessments-tab`, type cũ trong `types.ts`, `useCourseOptions` |
| `docs/product/screens/teacher-van-hanh.md`, `teacher-bai-danh-gia.md`, `docs/STATE.md` | Task 9 |

---

## LÁT 1 — TC-15

### Task 1: Bộ chọn học sinh thay thế được trong hộp thoại thêm đối tượng

**Files:**
- Modify: `src/features/academic/ui/shared/course-add-audience-dialog.tsx`, `course-audience-section.tsx` (+ test hai file)
- Create: `src/features/academic/ui/shared/course-audience-roster-picker.tsx` (+ test)

**Interfaces:**
- Produces: `type StudentPickerProps = { excludedStudentIds: string[]; value: string | null; onChange: (studentId: string) => void }`; `CourseAddAudienceDialog` và `CourseAudienceSection` nhận thêm `studentPicker?: ComponentType<StudentPickerProps>` (mặc định `CourseAudienceStudentPicker`); `CourseAudienceRosterPicker: ComponentType<StudentPickerProps>`.
- Consumes: `useClasses()` (`academic/api/classes`), `useClassMembers(classId, {}, {enabled})` từ `@/features/user-management` (trả `{student_id, full_name}[]`, như `teacher-course-enroll-dialog.tsx`).

Hành vi `CourseAudienceRosterPicker`: ô chọn Lớp (lớp không `archived`, mặc định lớp đầu), ô chọn Học sinh liệt kê thành viên lớp đó trừ `excludedStudentIds`; đổi lớp thì bỏ chọn học sinh; không còn ai thì placeholder "Không còn học sinh để chọn". Chọn học sinh gọi `onChange(student_id)`.

- [ ] **Step 1: Viết test (thất bại)** — roster picker: hiện học sinh của lớp đầu trừ người đã loại; đổi lớp tải thành viên lớp khác và xóa lựa chọn; lớp rỗng hiện placeholder. Dialog: truyền `studentPicker` tùy biến ở tab Học sinh thì gọi component đó và `POST` đúng `{student_id}`; mặc định vẫn dùng ô tìm (test cũ không đổi).
- [ ] **Step 2: Chạy thấy thất bại** — `pnpm vitest run src/features/academic/ui/shared`.
- [ ] **Step 3: Cài đặt** theo mô tả; `CourseAudienceSection` chỉ chuyển tiếp prop xuống dialog.
- [ ] **Step 4: Chạy test, lint, tsc** — xanh.
- [ ] **Step 5: Commit** — `feat(academic): pluggable student picker for course audience dialog`.

---

### Task 2: Chuyển tab Học viên lên `ui/shared`

**Files:** Move `academic/ui/admin/course-learners-tab.tsx` (+ test) → `academic/ui/shared/`; sửa import trong `course-detail-screen.tsx`.

- [ ] **Step 1:** `git mv` hai file, sửa import; chạy `pnpm vitest run src/features/academic` và `pnpm tsc --noEmit` — xanh (không đổi hành vi, không cần test mới).
- [ ] **Step 2: Commit** — `refactor(academic): move course learners tab to shared`.

---

### Task 3: Hook sao chép bài vào khóa

**Files:** Modify `assessment/api/course-items.ts`, `assessment/api/types.ts`, thêm test vào `course-items.test.tsx`.

**Interfaces:**
- Produces: `useCopyAssessmentToCourse(courseId)` — mutation nhận `assessmentId: string`, gọi `POST /courses/{id}/items/copy` body `{assessment_id}`, trả `CourseItem` (chuẩn hóa `position`), invalidate `assessmentKeys.courseItems(courseId)`; `Assessment` (type chi tiết bài) có thêm `course_id: string | null`.

- [ ] **Step 1: Viết test (thất bại)** — body đúng `{assessment_id:"a1"}`, item trả `position` số, danh sách mục bị invalidate; 422 `COURSE_ARCHIVED` ném `ApiError` giữ nguyên message.
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt** theo mẫu `useUpdateCourseItem`. Kiểm mọi chỗ dựng đối tượng `Assessment` trong test/fixture có sẵn (tsc sẽ chỉ ra) và thêm `course_id: null`.
- [ ] **Step 4: Chạy test, tsc** — xanh.
- [ ] **Step 5: Commit** — `feat(assessment): copy assessment into course hook`.

---

### Task 4: "Soạn bài mới cho khóa" và "Lấy từ bài của tôi"

**Files:**
- Modify: `assessment/ui/teacher/create-assessment-dialog.tsx` (+ test), `assessment/index.ts`
- Create: `assessment/ui/teacher/course-item-actions.tsx` (+ test)

**Interfaces:**
- Produces: `CreateAssessmentDialog` nhận `courseId?: string`; khi có thì gửi `course_id` trong body tạo bài. `CourseItemActions({ courseId: string })` (export qua `@/features/assessment`) render hai nút "Soạn bài mới cho khóa" (mở `CreateAssessmentDialog courseId`) và "Lấy từ bài của tôi" (mở hộp thoại chọn bài).
- Consumes: Task 3 `useCopyAssessmentToCourse`; `useAssessments({ per_page: 100 })` (chỉ bài cá nhân, `course_id` null do BE); `toWriteInput`.

Hành vi:
- Hộp thoại "Lấy từ bài của tôi": danh sách bài (tên phiên bản hiện hành, loại, trạng thái), có ô tìm theo tên (tham số `search` có sẵn), chọn một bài rồi "Thêm vào khóa" → `useCopyAssessmentToCourse`; thành công thì đóng và toast "Đã thêm bản sao vào khóa"; lỗi BE hiện nguyên văn trong hộp thoại và không đóng; rỗng hiện "Cô chưa có bài nào để lấy.".
- Ghi chú trong hộp thoại: "Bản sao độc lập với bài gốc: sửa bài gốc không đổi bài trong khóa."
- Tạo bài mới cho khóa: sau khi tạo mở `/teacher/assessments/{id}/edit` như hiện tại (BE tự thêm mục khóa).

- [ ] **Step 1: Viết test (thất bại)** — Create dialog có `courseId` gửi `course_id` trong `POST /assessments`; không có thì không gửi trường này. Hộp thoại lấy bài: chọn bài rồi bấm gửi `POST /courses/k1/items/copy {assessment_id}`; 422 `COURSE_ARCHIVED` hiện message, hộp thoại còn mở; rỗng đúng câu; ô tìm gửi `search`.
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt**; tách hộp thoại chọn bài thành file riêng nếu `course-item-actions.tsx` vượt 200 dòng.
- [ ] **Step 4: Chạy test, lint, tsc** — xanh, gồm `architecture.test.ts`.
- [ ] **Step 5: Commit** — `feat(assessment): add new or copied assessment to course from teacher`.

---

### Task 5: Trình soạn của bài thuộc khóa

**Files:** Modify `assessment/ui/teacher/assessment-editor-screen.tsx` (+ test), `assessment-editor-header.tsx` nếu cần.

Hành vi: khi `assessment.course_id` có giá trị: (a) bỏ tab "Đối tượng" khỏi `TABS` (BE không cho giao riêng, `COURSE_ASSESSMENT_NO_AUDIENCE`); (b) ở đầu trang hiện liên kết "‹ Khóa học" tới `/teacher/courses/{course_id}` thay vì danh sách bài, và một dòng "Bài này thuộc khóa, đối tượng là học viên của khóa."; bài cá nhân giữ nguyên.

- [ ] **Step 1: Viết test (thất bại)** — bài có `course_id`: không có tab "Đối tượng", có liên kết về `/teacher/courses/k1`; bài `course_id: null`: giữ tab "Đối tượng" và liên kết về danh sách bài.
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt.**
- [ ] **Step 4: Chạy test, lint, tsc** — xanh.
- [ ] **Step 5: Commit** — `feat(assessment): hide audience tab and link back to course for course assessments`.

---

### Task 6: Ghép lại màn chi tiết khóa của Giáo viên

**Files:** Modify `academic/ui/teacher/teacher-course-detail-screen.tsx`, `teacher-course-screens.test.tsx` (hoặc tách `teacher-course-detail-screen.test.tsx`).

**Interfaces:** Consumes Task 1 (`CourseAudienceSection studentPicker={CourseAudienceRosterPicker}`), Task 2 (`CourseLearnersTab` từ shared), Task 4 (`CourseItemActions`), `CourseItemsSection` từ `@/features/assessment`.

Hành vi:
- Giữ header (tên, badge trạng thái, "Của tôi"/"Dùng chung", mô tả · môn), nút Sửa/Lưu trữ của khóa mình, `TeacherCourseFormDialog`, `CourseArchiveDialog`.
- Khóa của tôi: ba tab **Đối tượng** (`CourseAudienceSection`), **Học viên · N** (`CourseLearnersTab`), **Mục của khóa · M** (`CourseItemActions` ở đầu + `CourseItemsSection canEdit`). Số đếm lấy từ `useCourseLearners`/`useCourseItems`; lỗi tải thì không hiện số. Khóa `archived`: banner "Khóa đã lưu trữ: không thêm đối tượng hay mục mới được nữa, vẫn xem được." và ẩn `CourseItemActions` (truyền `courseArchived` xuống các khối đã có).
- Khóa dùng chung: giữ ghi chú "Khóa dùng chung do quản trị viên quản lý… Chỉ quản trị viên xem được học sinh ghi danh." đổi thành "…Chỉ quản trị viên xem được đối tượng và học viên."; một khối **Mục của khóa** chỉ đọc (`CourseItemsSection canEdit={false}`); không gọi `/audience`, `/learners`.
- Bỏ import `course-enrollments`, `course-class-links`, `CourseEnrollmentsTab`, `CourseLinkedClassesSection`, `TeacherCourseEnrollDialog`, `TeacherCourseAssessmentsTab`.

- [ ] **Step 1: Viết/sửa test (thất bại)** — khóa của tôi: ba tab đúng tên và số đếm, không còn "Ghi danh"/"Lớp đã gắn"; thêm học sinh qua bộ chọn theo lớp gửi `POST audience {student_id}`; khóa lưu trữ ẩn "Soạn bài mới cho khóa" và "Thêm đối tượng"; khóa dùng chung: ghi chú mới, **không** có request `/audience` hay `/learners` (MSW báo lỗi nếu có), mục hiện chỉ đọc không nút ghi.
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt.**
- [ ] **Step 4: Chạy** `pnpm test`, `pnpm lint`, `pnpm tsc --noEmit`, `pnpm build` — xanh. Gọi API thật bằng tài khoản `teacher1@piclass.test` (đăng nhập, `POST /auth/context {role_code:"teacher"}`): tạo khóa, thêm học sinh/lớp, tạo bài với `course_id` rồi `GET items`, sao chép một bài cá nhân; ghi kết quả.
- [ ] **Step 5: Commit** — `feat(academic): teacher course detail with audience, learners and items`.
- [ ] **Step 6:** Review cuối lát 1 (subagent opus, effort medium; gói review từ `review-package`), sửa Critical/Important bằng TDD, push nhánh.

---

## LÁT 2 — Dọn TC-10, TC-09 và code cũ

### Task 7: Bỏ lựa chọn "Khóa học" ở bước đối tượng của TC-10 và dọn code ghi danh cũ

**Files:**
- Modify: `assessment/ui/teacher/assessment-audience-tab.tsx` (+ test), `assessment/api/audience.ts`, `audience-options.ts`, `audience-count.ts` (+ test), `assessment/api/types.ts` (`AssessmentAudience.course_id`), `keys.ts` (`courseOptions`)
- Delete: danh sách ở bảng "Xóa ở Task 7" phía trên (cùng test của chúng), type `CourseEnrollment*`, `CourseClassLink`, `EnrollInCourseInput`, `EnrollmentResult`, `EndCourseEnrollmentInput`, `LinkClassToCourseInput` trong `academic/api/types.ts`, `keys.courseEnrollments`, `keys.courseClassLinks`.

Hành vi: tab Đối tượng của bài cá nhân chỉ còn thẻ **Lớp** và **Học sinh** (bỏ khối Khóa học); `AudienceInput` chỉ còn `{class_id}` hoặc `{student_id}`; `expectedStudents` không còn xử lý `course_id`. `AssessmentAudience.course_id` bỏ khỏi type nếu OpenAPI mới không trả (kiểm `GET /assessments/{id}/audience` trong OpenAPI trước khi xóa).

- [ ] **Step 1: Viết/sửa test (thất bại)** — audience tab không còn nhãn "Khóa học" và không gọi `GET /courses`; `audience-count` không còn trường hợp khóa.
- [ ] **Step 2: Chạy thấy thất bại.**
- [ ] **Step 3: Cài đặt và xóa**; `grep -rn "enrollment\|class-link\|ClassLink\|useCourseOptions\|courseOptions" src` không còn kết quả ngoài tài liệu/comment lịch sử đã dịch.
- [ ] **Step 4: Chạy** `pnpm test`, `pnpm lint`, `pnpm tsc --noEmit`, `pnpm build` — xanh.
- [ ] **Step 5: Commit** — `refactor: remove course enrollment code and course audience option`.

---

### Task 8: TC-09 và TC-07/08 rà lại

**Files:** chỉ sửa nếu phát hiện lệch.

- [ ] **Step 1:** Rà TC-09 (`assessment-list-screen.tsx`): bộ lọc/ghi chú còn nhắc "bài của khóa" hay `course_id` thì bỏ (BE đã tự lọc `course_id` null); xác nhận bằng gọi `GET /assessments` thật không lẫn bài của khóa vừa tạo ở Task 6.
- [ ] **Step 2:** Rà TC-07/08 theo `DEC-LRN-026` mục 2: câu `shared` của người khác không sửa được (nút "Sao chép thành biến thể của tôi" nếu BE có endpoint, nếu chưa có ghi `BACKLOG.md`), câu `private` của người khác không hiện; trường hiển thị Chung/Riêng đã có ở `question-settings-tab.tsx`. Chỉ ghi lệch + việc cần làm; sửa nếu nhỏ, còn lại thêm dòng vào `BACKLOG.md`.
- [ ] **Step 3:** Review cuối lát 2 (subagent opus, effort medium), sửa Critical/Important bằng TDD, push nhánh.

---

### Task 9: Tài liệu (nhánh docs)

**Files:** `docs/product/screens/teacher-van-hanh.md` (TC-15), `docs/product/screens/teacher-bai-danh-gia.md` (TC-10 ghi chú), `docs/STATE.md`, `docs/BACKLOG.md` nếu phát sinh.

- [ ] **Step 1:** Viết lại TC-15 theo mô hình mới (header, ba tab, khóa dùng chung chỉ xem mục, lỗi `COURSE_ARCHIVED`/`AUDIENCE_ALREADY_ADDED`/`CLASS_ARCHIVED`, API mới), bỏ khối cảnh báo "bản cũ"; TC-10: xác nhận chế độ mở từ khóa và tab Đối tượng ẩn cho bài của khóa. Dùng `grep -n "TC-15"` rồi `Read` theo `offset`+`limit`.
- [ ] **Step 2:** `STATE.md`: Giáo viên xong (hoặc từng lát), việc kế tiếp: Học sinh (ST-09) rồi lát 4c; ≤ 60 dòng.
- [ ] **Step 3:** Commit `docs: rewrite TC-15 for course audience model`, push nhánh docs.

---

## Self-Review

- Spec coverage: đối tượng (Task 1, 6), học viên (Task 2, 6), mục khóa với soạn mới/sao chép (Task 3, 4, 6), trình soạn bài khóa (Task 5), khóa dùng chung chỉ xem (Task 6), bỏ thẻ Khóa học ở TC-10 (Task 7), TC-09/07/08 (Task 8), tài liệu (Task 9). Chưa làm có chủ đích: màn Học sinh ST-09; chọn học sinh ngoài lớp mình dạy (G4); "Sao chép câu hỏi chung thành biến thể" nếu BE chưa có (ghi BACKLOG ở Task 8).
- Nhất quán tên: `StudentPickerProps`, `CourseAudienceRosterPicker`, `useCopyAssessmentToCourse`, `CourseItemActions`, `CreateAssessmentDialog courseId` dùng giống nhau ở mọi task.
- Rủi ro đã biết: `useClassMembers` có tham số `enabled` (đã dùng ở dialog cũ); xóa type cũ ở Task 7 có thể chạm test fixture của các màn Admin/Giáo viên khác (tsc chỉ ra); BE không có thao tác sắp lại/giao riêng cho bài của khóa nên FE chỉ ẩn tab.
