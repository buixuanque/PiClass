# Lát 4b FE — TC-09 Danh sách bài đánh giá Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Màn `/teacher/assessments` liệt kê bài đánh giá của Giáo viên theo mockup TC-09, có tìm tên bài, lọc loại/trạng thái/lớp, công tắc "Bài tôi tạo", phân trang, và mục menu "Bài đánh giá".

**Architecture:** Màn mới trong `features/assessment/ui/teacher/` theo mẫu `question-bank-screen.tsx` (state lọc cục bộ, tìm debounce 300ms, TanStack Query qua `useAssessments`). Route mỏng. Không đổi BE.

**Tech Stack:** Next.js 16, React 19, TanStack Query, Vitest + Testing Library + MSW.

**Spec:** `docs/product/screens/teacher-bai-danh-gia.md` (TC-09), `DEC-LRN-022`. Mockup: `piclass-mockup/design-system/PiClass Giáo viên.dc.html`, khối `isTestList` (dòng ~545-578) và view-model `tlRows` (dòng ~1623-1645).

## Global Constraints

- Port đúng từng giá trị từ mockup (padding, gap, radius, font-size); dùng component có sẵn ở `components/ui/*` (`Card`, `Input` pill, `Tabs`, `Select` chip, `Switch`, `Badge`, `Button`, `IconButton`, `Skeleton`).
- Thời điểm hiển thị đi qua `lib/time.ts` (F8); không `toLocaleString()`.
- Chữ giao diện tiếng Việt gõ thẳng; comment tiếng Việt; tên biến/file/test tiếng Anh; comment đầu file ghi mã màn `TC-09` và `DEC-LRN-022`.
- Route `page.tsx` chỉ ghép (F1); feature khác chỉ import qua `index.ts` (F5); `ui/teacher` không import `ui/<vai trò khác>` (F4).
- Bản đầu **không có** cột đối tượng/đã nộp/chờ chấm (G9) và **không có** "Nhân bản" (`B-069`); số câu của bài cũng không có vì `GET /assessments` không trả `questions`.
- "Bài tôi tạo" lọc ở FE bằng `created_by_user_id === me` (BE chưa lọc theo người tạo).
- Lọc loại, trạng thái, lớp, tìm tên gửi lên BE (`type`, `status`, `class_id`, `search`); `page` và `per_page=20`.
- TC-11 chưa có (lát 4c): dòng bài đã phát hành, đã đóng, đã hủy chưa có nút/liên kết; chỉ bài **nháp của mình** có nút "Soạn tiếp" → `/teacher/assessments/{id}/edit` (màn này làm ở plan TC-10).

## Review Focus

- Đổi bất kỳ bộ lọc hoặc ô tìm thì về trang 1 (không kẹt ở trang không tồn tại).
- Ô tìm chỉ gồm khoảng trắng: không gửi `search`, coi như không lọc.
- `current_version` bằng `null` hoặc thiếu mốc giờ: dòng vẫn hiện đủ, không lỗi, không in "null".
- Bài do giáo viên khác tạo (Admin hoặc bài qua lớp): có nhãn "Của giáo viên khác", không có nút "Soạn tiếp".
- Công tắc "Bài tôi tạo" bật mà trang hiện tại không còn dòng nào: hiện trạng thái "không khớp bộ lọc" chứ không báo "chưa giao bài nào".

---

### Task 1: Kiểu dữ liệu và bộ lọc của danh sách bài

**Files:**
- Modify: `piclass-web/src/features/assessment/api/types.ts`
- Test: `piclass-web/src/features/assessment/api/assessments.test.ts` (mới)

**Interfaces:**
- Produces: `AssessmentCurrentVersion` có thêm `version_number: number`, `status: AssessmentStatus`, `opens_at: string | null`, `closes_at: string | null`, `duration_seconds: number | null`, `max_attempts: number | null`; `AssessmentListItem` có thêm `created_by_user_id: string`; `AssessmentListFilters` có thêm `search?: string`, `page?: number`. `useAssessments(filters)` giữ nguyên chữ ký.

- [ ] **Step 1: Viết test `assessments.test.ts`** (MSW): `assessmentsApi.list({ search: "toan", type: "quiz", page: 2, per_page: 20 })` gọi `GET /api/v1/assessments?search=toan&type=quiz&page=2&per_page=20`; khóa bị `undefined` không xuất hiện trong query.
- [ ] **Step 2: Chạy, xác nhận fail** (kiểu thiếu `search`/`page` làm `pnpm typecheck` lỗi; test chưa có kiểu).

  Run: `pnpm test assessments.test && pnpm typecheck`
  Expected: typecheck FAIL (thuộc tính chưa có).
- [ ] **Step 3: Mở rộng `types.ts`** như khối Produces; giữ `AssessmentListItem.current_version: AssessmentCurrentVersion | null`.
- [ ] **Step 4: Chạy lại** — Expected: test và typecheck PASS; các màn đang dùng `useAssessments` (tab Bài của khóa) không hỏng: `pnpm test` toàn bộ phần `course`.
- [ ] **Step 5: Commit** `feat(assessment): extend assessment list types with version fields, search and page`.

### Task 2: Màn TC-09, route và menu

**Files:**
- Create: `piclass-web/src/features/assessment/ui/teacher/assessment-list-screen.tsx`, `assessment-list-row.tsx`, `assessment-kind.ts`
- Create: `piclass-web/src/app/(portal)/teacher/assessments/page.tsx`
- Modify: `piclass-web/src/features/assessment/index.ts` (export `AssessmentListScreen`), `piclass-web/src/config/nav.ts` (mục "Bài đánh giá", icon `clipboard-list`, `/teacher/assessments`, đặt sau "Khóa học" hoặc trước "Ngân hàng câu hỏi" theo thứ tự mockup)
- Test: `piclass-web/src/features/assessment/ui/teacher/assessment-list-screen.test.tsx`

**Interfaces:**
- Consumes: `useAssessments`, `AssessmentListItem` (Task 1); `useClasses` từ `@/features/academic`; `useMe` từ `@/features/identity`; `formatDateTime` từ `@/lib/time`.
- Produces: `AssessmentListScreen(): JSX.Element`; `ASSESSMENT_KIND: Record<AssessmentType, { label: string; icon: IconName }>` (assignment: "Bài tập"/`file-text`, quiz: "Kiểm tra"/`file-check`, exam: "Bài thi"/`shield-check`); `ASSESSMENT_STATUS: Record<AssessmentStatus, { label: string; tone: BadgeTone }>` (draft: "Bản nháp"/neutral, published: "Đã phát hành"/blue, closed: "Đã đóng"/green, cancelled: "Đã hủy"/coral; `archived` hiển thị "Đã lưu trữ"/neutral).

- [ ] **Step 1: Viết test màn** (MSW `GET /api/v1/assessments`, `/auth/me`, `/classes`), tên test:
  - `renders_rows_with_type_status_and_time_bits`: dòng "Kiểm tra 15 phút" hiện nhãn "Kiểm tra", "Đã phát hành", "Hạn dd/MM HH:mm", "15 phút", "v2", và **không** có "câu".
  - `own_draft_has_continue_link_others_have_none`: bài nháp của mình có liên kết "Soạn tiếp" tới `/teacher/assessments/{id}/edit`; bài của người khác có nhãn "Của giáo viên khác" và không có liên kết.
  - `new_draft_badge_when_published_with_draft_version`: `status=published` và `current_version.status=draft` hiện "Có bản nháp mới".
  - `filters_are_sent_to_the_api_and_reset_page`: chọn tab "Kiểm tra" gửi `type=quiz`; chọn trạng thái gửi `status=draft`; chọn lớp gửi `class_id`; gõ "toan" (chờ debounce) gửi `search=toan`; ở trang 2 rồi đổi lọc thì request kế tiếp có `page=1`.
  - `blank_search_is_not_sent`: gõ khoảng trắng không thêm `search`.
  - `mine_switch_filters_client_side_and_empty_states`: bật "Bài tôi tạo" chỉ còn bài của mình; không còn dòng nào thì hiện "Không có bài nào khớp" kèm "Xóa bộ lọc"; danh sách rỗng và không lọc hiện "Cô chưa giao bài nào" kèm "Tạo bài đầu tiên" liên kết `/teacher/assessments/new`.
  - `null_current_version_still_renders`: `current_version: null` không lỗi, không in "null".
  - `pagination_uses_meta`: `meta.last_page = 3` hiện "Trang 1/3 · n bài" và nút trang sau gọi `page=2`.
- [ ] **Step 2: Chạy, xác nhận fail** (module chưa có).

  Run: `pnpm test assessment-list-screen`
  Expected: FAIL (không import được màn).
- [ ] **Step 3: Cài đặt** `assessment-kind.ts`, `assessment-list-row.tsx` (một dòng: ô icon 44×44 bo 12 nền xanh nhạt, tên + nhãn loại + nhãn phụ, dòng meta các mốc giờ + phút + `v{version_number}`, nhãn trạng thái, nút "Soạn tiếp" khi là nháp của mình) và `assessment-list-screen.tsx` theo mockup `isTestList` và mẫu `question-bank-screen.tsx` (tiêu đề "Bài đánh giá", mô tả `{total} bài`, nút "Tạo bài" → `/teacher/assessments/new`, thanh lọc theo thứ tự mockup: ô tìm pill, `Tabs` pill loại, `Select` chip trạng thái, `Select` chip lớp, `Switch` "Bài tôi tạo"). Các giá trị padding/gap/radius lấy đúng từ mockup, không làm tròn. Route chỉ `return <AssessmentListScreen />`.
- [ ] **Step 4: Chạy test, typecheck, lint**

  Run: `pnpm test assessment architecture && pnpm typecheck && pnpm lint`
  Expected: PASS.
- [ ] **Step 5: Xem trên trình duyệt** (`http://localhost:8001/teacher/assessments`): có menu, lọc, trạng thái rỗng; ghi lệch mockup nếu có vào mô tả PR. Cần restart container `web` nếu thư mục mới không được nhận.
- [ ] **Step 6: Commit** `feat(assessment): add TC-09 assessment list screen and menu item`.
