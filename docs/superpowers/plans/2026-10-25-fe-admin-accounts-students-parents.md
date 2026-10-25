# FE Admin — Tài khoản, Học sinh, Phụ huynh (nhóm 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:test-driven-development to implement this plan task-by-task.

**Goal:** Ghép FE thật (Next.js) cho AD-04/05/05a (Học sinh), AD-06/06a/06b/06c (Phụ huynh), AD-05b/05c (mời tài khoản dùng chung), AD-02/AD-03 (Tài khoản, Vai trò) — gọi API thật theo CLAUDE.md (`piclass-web`). Đồng thời mở khóa nút "Thêm học sinh" đang disable ở AD-10b (nhóm 2) bằng cách nối với `GET /students`.

**Spec:** `docs/product/screens/admin.md` mục `AD-02`, `AD-03`, `AD-04`, `AD-05` (+`05a/05b/05c`), `AD-06` (+`06a/06b/06c`). API thật: `piclass-api` nhánh local `feat/invite-auto-assign-role` — đã đọc code thật (Controller/Request/Action/Resource/Exception), không chỉ tin `admin.md` (vài chỗ `admin.md` mô tả "chưa phân trang" nhưng code thật của `GET /students`/`GET /parents` **có** phân trang giống `GET /teachers` — ghi rõ bên dưới).

**Bối cảnh tổ chức code:** đọc kỹ `piclass-web/CLAUDE.md` (F1–F8), đặc biệt mục "Design system" (port đúng giá trị, không làm tròn). Slice mẫu bám sát: `src/features/user-management/ui/admin/teacher-*.tsx` (list/detail/form, badge, status dialog) và `class-member-*`/`class-teacher-*` (dialog tìm-chọn, mẫu M4) vừa làm ở nhóm 1–2. `src/features/academic/` (nhóm 2) là mẫu cho cách tổ chức route `app/(portal)/admin/...` và `multiline` Input.

## Global Constraints

- **Đặt feature:** Student/Parent/PersonProfile/ParentStudentRelationship/AccountInvitation và Tài khoản-Vai trò (User/UserRole quản trị) đều thuộc module **UserManagement** theo `docs/architecture/module-boundaries.md` (dòng "User Management": "Hồ sơ `PersonProfile`, Student/Parent/Teacher profile, **role assignment quản trị**, Parent–Student relationship..."; code thật xác nhận — `ListUsersController`, `GetUserController`, `UpdateUserStatusController`, `AssignUserRoleController`, `EndUserRoleController` đều nằm vật lý trong `app/Modules/UserManagement/Http/Controllers/`, không phải `Identity`). → **toàn bộ** màn nhóm này đặt ở `src/features/user-management/`, không tạo feature mới.
- **`GET /students`, `GET /parents`, `GET /users` ĐỀU có phân trang** (`{data:[...], meta:{total,current_page,last_page}}`, giống `GET /teachers` — khác với mô tả cũ trong `admin.md` dòng quy ước chung "danh sách chưa phân trang trừ `GET /users`"; đã lệch so với code thật của `ListStudentsQuery`/`ListParentsQuery`/`ListStudentsController`/`ListParentsController` — dùng kiểu `Paginated<T>` giống `teachers.ts`, tái dùng `TeacherListTable` làm mẫu cho `StudentListTable`/`ParentListTable`/`AccountListTable`). `per_page` không có giới hạn trên ở cả 3 — FE tự cap, ví dụ `per_page=50`.
- **`GET /parent-student-relationships` KHÔNG phân trang** (`{data:[...]}` thẳng, không `meta`) — trả cả liên kết đã `ended`, kèm `parent_name`/`student_name` (C3 đã xong 2026-10-25, hết N+1).
- Không copy JSX/style trực tiếp từ mockup; mọi giá trị đọc từ `piclass-mockup/ds-v2-source/_ds_bundle.js` hoặc đối chiếu `piclass-mockup/design-system/standalone/PiClass Quản trị.html` (đã xác nhận cấu trúc dùng chung qua các biến `base.kind==='students'|'parents'`, `inviteOpen`, `linkOpen`, `openFormFor`, `openPerson` — mockup tự nó đã viết các màn Học sinh/Phụ huynh/mời-tài khoản/liên kết theo khuôn dùng chung, đúng tinh thần gộp của nhóm này).
- Comment code tiếng Việt; tên biến/hàm/file/test tiếng Anh. Commit message tiếng Anh, kết thúc `Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>`.
- Branch mới từ `origin/main` mới nhất (`git fetch origin` trước), không commit vào `main`. Không tự push/PR/merge trừ khi user yêu cầu rõ.
- F7: không tự thêm rule nghiệp vụ; mọi lỗi 422/409 hiện nguyên văn `error.message` theo `error.code` (không theo message). Danh mục mã dùng trong plan này (đã đọc `Exceptions/*.php` thật, khớp `docs/architecture/error-codes.md`):

| Mã | HTTP | Dùng ở |
|---|---|---|
| `VALIDATION_FAILED` | 422 | mọi form (có/không `details` tùy trường hợp) |
| `ALREADY_INVITED` | 409 | `POST /person-profiles/{id}/invite` — hồ sơ đã có tài khoản |
| `NOT_INVITED` | 409 | `POST /person-profiles/{id}/invitation` (tạo lại liên kết) — hồ sơ chưa từng được mời |
| `ALREADY_ACTIVATED` | 409 | tạo lại liên kết cho tài khoản đã từng đăng nhập (`last_login_at` khác null) |
| `ACCOUNT_DEACTIVATED` | 409 | tạo lại liên kết cho tài khoản đang `inactive` |
| `ALREADY_LINKED` | 409 | `POST /parent-student-relationships` — cặp đã có liên kết active |
| `ALREADY_ENDED` | 409 | `DELETE /parent-student-relationships/{id}` — liên kết đã kết thúc |
| `ROLE_ALREADY_HELD` | 409 | `POST /users/{id}/roles` — role đang giữ hiệu lực |
| `LAST_ADMIN` | 409 | `PATCH /users/{id}` (vô hiệu hóa) hoặc `DELETE /users/{id}/roles/{roleId}` (kết thúc role admin cuối cùng) |
| `ACTIVE_ASSIGNMENTS_EXIST` | 422 | `PATCH /users/{id}` hoặc `DELETE /users/{id}/roles/{roleId}` khi Teacher còn phân công lớp — kèm `active_class_ids` **ở cấp ngoài cùng JSON**, không trong `error.details` |

## Quyết định tự chọn khi đọc code (cần user xác nhận)

1. ~~**GM-1 (không nối User↔hồ sơ) cho khối "Tài khoản" ở AD-05/06a/07a**~~ — **đã sửa BE 2026-10-25** (nhánh `feat/invite-auto-assign-role`, cùng nhánh BE của nhóm này): `GET /students/{id}`/`GET /parents/{id}`/`GET /teachers/{id}` giờ trả thẳng `account` (cùng shape với danh sách: `{login_identifier, status, last_login_at}` hoặc `null` khi chưa mời) — controller đã eager-load `user`. **Không cần** gọi lại API danh sách để dò nữa; màn chi tiết AD-05/06a/07a đọc trực tiếp `student.account`/`parent.account`/`teacher.account` từ response chi tiết.
2. ~~**GM-6 (thiếu `role_id`) ở AD-03**~~ — **đã sửa BE 2026-10-25**: `GET /users/{id}` giờ trả `active_roles: [{role_id, role}]` (`role_id` = `roles.id` thật, đã có test end-to-end xác nhận gọi `DELETE /users/{id}/roles/{role_id}` thành công). Nút **"Kết thúc vai trò"** ở AD-03 làm **đầy đủ chức năng** (không còn disable+tooltip) — lấy đúng `role_id` từ `active_roles[i].role_id` khi gọi `DELETE`.
3. **Thứ tự menu mới:** chèn "Tài khoản" (`/admin/accounts`), "Học sinh" (`/admin/students`), "Phụ huynh" (`/admin/parents`) vào `NAV_ITEMS.admin` ngay sau "Tổng quan" và trước "Giáo viên" — khớp đúng thứ tự mục lục `admin.md` (Tài khoản → Học viên → Phụ huynh → Giáo viên → Học vụ). Thứ tự hiện tại (`Tổng quan, Giáo viên, Môn học, Lớp học`) sẽ đổi thành `Tổng quan, Tài khoản, Học sinh, Phụ huynh, Giáo viên, Môn học, Lớp học`.
4. **AD-05c "Gửi lại lời mời":** mockup-plan mục #4 ghi là quyết định còn mở, nhưng đọc code thật thấy BE **đã có** `POST /person-profiles/{id}/invitation` (Action `RegenerateInvitation`, đổi tên "Tạo lại liên kết" theo cập nhật 2026-10-24 trong `admin.md`) — không phải API rỗng như "Gửi lại lời mời" bản cũ (gửi lại qua Quên mật khẩu). Quyết định: build đúng tính năng đã có trên BE ("Tạo lại liên kết", nút "Sao chép liên kết" + gửi thêm thư nếu có email), **không** build lại luồng cũ "hướng dẫn dùng Quên mật khẩu" (đã lạc hậu so với code). Việc mockup-plan đánh dấu "#4 còn mở" tôi hiểu là mở ở mức *tài liệu đặc tả chưa đồng bộ*, không phải blocker code — nhưng vẫn ghi lại đây để user xác nhận trước khi build, vì đây gần với một quyết định sản phẩm ("nhận AD-05c") hơn là thuần kỹ thuật.
5. **Q6 (khung điều hướng Admin+Teacher):** không ảnh hưởng plan này — theo `DEC-USR-015`, V1 không làm chọn/đổi vai trò, mỗi tài khoản một vai trò, nên không có màn nào trong nhóm 3 cần xử lý "tài khoản vừa Admin vừa Teacher". Không tự chốt gì thêm, chỉ ghi nhận không chặn.
6. **Khối "Tiến độ" ở AD-05 (điểm TB, số bài đã chấm):** API `GET /students/{id}/progress` thuộc module `ResultProgress` (khác `UserManagement`) — theo F5 cần gọi qua `@/features/results` nếu feature đó đã tồn tại; **hiện chưa có** `src/features/results/` trong code. Quyết định: tạo tối thiểu `src/features/results/api/progress.ts` (một hàm `useStudentProgress(studentId)`, không UI riêng, không chart — chỉ số tổng `average_percentage`, `graded_count`, `pending_count`) và export qua `index.ts` của feature đó; AD-05 import qua `@/features/results` (F5, không đào thư mục con). Bỏ biểu đồ theo thời gian (🟡 G8, đã đánh dấu trong `admin.md`) và điểm danh (🟡 G3) — đúng theo đánh dấu gap hiện có, không tự làm thêm.
7. **Cảnh báo trùng tên/SĐT ở AD-05a/AD-06b (`BR-USER-005`, "cảnh báo mềm"):** nhóm 1 đã quyết định bỏ hẳn cảnh báo này ở AD-07b ("chỉ là tiện ích hiển thị, không phải rule bắt buộc" — ghi trong `mockup-plan.md` dòng nhóm 1). Áp quyết định tương tự cho AD-05a/AD-06b: **không build** cảnh báo trùng, giữ nhất quán với nhóm 1.
8. **AD-04/AD-06 "chip nhắc 'Chưa có tài khoản: 23'" và cột "Lớp đang học":** `class_names`/`account` đã có sẵn trong `StudentListResource`/`ParentListResource` (A7/A8 phần học viên đã xong) — build đủ các cột này, không cắt giảm.
9. **`account-badge.ts` dùng chung:** tổng quát hóa `accountBadge()` hiện có trong `teacher-badges.ts` (vì `Account` type của Student/Parent/Teacher giống hệt `{login_identifier,status,last_login_at}`) thành một file chung `ui/admin/account-badge.ts`, dùng cho cả 3 loại hồ sơ — tránh lặp 3 lần (DRY rule của CLAUDE.md: "lặp từ 3 chỗ thì tách"). `teacher-badges.ts` giữ lại `PROFILE_BADGE` (đặc thù theo từng loại hồ sơ có nhãn khác nhau: "Ngừng hoạt động" cho Teacher/Student/Parent — nhãn thực ra giống nhau theo M6 "Hồ sơ active/inactive" → Đang hoạt động/Ngừng hoạt động, nên `PROFILE_BADGE` cũng tổng quát hóa được, đặt chung `profile-badge.ts`).
10. **`person-profiles.ts` dùng chung:** tạo `api/person-profiles.ts` với `personProfilesApi.update()` (gọi `PATCH /person-profiles/{id}`), dùng trong `students.ts`/`parents.ts`. Không sửa `teachers.ts` hiện có (đang inline gọi trực tiếp, hoạt động đúng) để tránh rủi ro chạm code đã test — chỉ ghi chú nợ kỹ thuật nhỏ (trùng lặp 2 chỗ còn lại) trong comment, không bắt buộc refactor lượt này.
11. **AD-05/AD-06a là trang một-tab (mẫu M3 không tab)**, đúng `admin.md` ghi rõ cho AD-06a ("Mẫu M3, một trang, không tab"); áp dụng tương tự cho AD-05 (không ghi rõ nhưng cấu trúc nội dung — đầu trang, thông tin chung, tài khoản, lớp, phụ huynh, tiến độ — là các khối xếp dọc, không phải tab).
12. ~~**AD-05 "Lớp đang học" (GM-2)**~~ — **đã sửa BE 2026-10-25**: `StudentListResource`/`StudentProfileResource` giờ trả `classes: [{id, name}]` thay `class_names: string[]` (lọc theo đúng cửa sổ "đang hiệu lực" như `ListStudentsQuery`). Hiện danh sách tên lớp dạng chip **bấm được**, dẫn thẳng `/admin/classes/{id}` (route đã có từ nhóm 2) — không cần N+1 dò tên nữa.

## Review Focus

- `GET /students`/`GET /parents`/`GET /users` **có** phân trang (`Paginated<T>`) — không viết nhầm kiểu mảng thẳng như `/subjects`/`/classes` của nhóm 2.
- `GET /parent-student-relationships` **không** phân trang, trả cả liên kết `ended` — FE tự chia "Đang liên kết"/"Đã kết thúc", không gọi lại API theo từng trạng thái.
- `ACTIVE_ASSIGNMENTS_EXIST` đọc `error.extra.active_class_ids` (cấp ngoài `error`, không trong `details`) — đúng pattern đã dùng ở `teacher-detail-screen.tsx`, tái dùng y nguyên cách đọc.
- Email **không bắt buộc** để mời tạo tài khoản (`DEC-USR-014`, đã bỏ chặn "cần email trước") — không tự thêm điều kiện disable nút Mời khi thiếu email.
- "Tạo lại liên kết" (AD-05c) gọi đúng `POST /person-profiles/{id}/invitation` (không phải `/invite` — dễ nhầm hai route khác nhau cho hai hành vi khác nhau).
- Role `DELETE` cần `role_id` (id bản ghi `Role`, lấy từ `active_roles[i].role_id`), không phải `user_role.id` — không được nhầm hai id này khi nối `EndRoleDialog`.
- `UpdateStudentRequest`/`UpdateParentRequest` chỉ nhận `status` (`sometimes`) — Student thêm `notes`; **không** có trường nào khác qua `/students|parents/{id}`, còn `full_name`/`email`/`phone` luôn qua `/person-profiles/{id}` riêng (hai request tuần tự, một nút Lưu — đúng mẫu `teachers.ts`).
- `CreateParentStudentRelationshipRequest` validate `exists:parents,id`/`exists:students,id` → 422 nếu id sai, không phải 404.
- Mọi ngày/giờ hiển thị qua `lib/time.ts` (F8).

---

## API thật đã đọc (tóm tắt hợp đồng dùng trong plan)

**Student (`app/Modules/UserManagement`):**
- `GET /students?status=&account=&has_classes=&name=&page=&per_page=` → `Paginated<{id, full_name, email, phone, status: "active"|"inactive", account: {login_identifier,status,last_login_at}|null, classes: {id,name}[]}>` (`ListStudentsQuery`/`StudentListResource`). `account` lọc theo 4 giá trị: `none`/`invited`/`active`/`inactive` (suy từ User, không phải field thô). `name` dùng `UnaccentSearch` (C7). **Đã sửa 2026-10-25:** field lớp là `classes: [{id,name}]` (không còn `class_names: string[]`).
- `GET /students/{id}` → `{student:{id,status,notes,person_profile:{id,full_name,email,phone},account,classes}}` — **đã sửa 2026-10-25:** giờ có cả `account` (cùng shape list, `null` nếu chưa mời) và `classes: [{id,name}]` (chỉ lớp đang hiệu lực). Route này cho cả `role:admin,teacher` (giáo viên dạy chung lớp cũng xem được) — không chỉ admin.
- `POST /students` body `{full_name, email?, phone?, notes?}` → 201 cùng shape trên (`account: null`, `classes: []` vì vừa tạo).
- `PATCH /students/{id}` body `{status?, notes?}` (chỉ 2 field, `sometimes`) → 200 `{student:{id,status,notes,person_profile,account,classes}}` (`person_profile` không đổi qua đây).
- `PATCH /person-profiles/{id}` body `{full_name?, email?, phone?}` → 200 `{person_profile:{id,full_name,email,phone}}`.

**Parent (tương tự Student, không có `notes`/`classes`):**
- `GET /parents?status=&account=&has_children=&name=&page=&per_page=` → `Paginated<{id, full_name, email, phone, status, account, children_count: number}>` (`ParentListResource` — không có danh sách lớp, chỉ đếm số con, không có tên — khác Student).
- `GET /parents/{id}` → `{parent:{id,status,person_profile,account}}` — **đã sửa 2026-10-25:** có `account` (cùng shape list).
- `POST /parents` body `{full_name,email?,phone?}` → 201 cùng shape.
- `PATCH /parents/{id}` body `{status?}` (chỉ field này, không có `notes`).

**ParentStudentRelationship (`app/Modules/UserManagement`):**
- `GET /parent-student-relationships?student_id=&parent_id=` → `{data:[{id,parent_id,parent_name,student_id,student_name,relationship_type,status:"active"|"ended"}]}` — không phân trang, trả cả `ended`, đã có tên hai bên (C3 xong).
- `POST /parent-student-relationships` body `{parent_id,student_id,relationship_type?}` → 201 `{parent_student_relationship:{id,parent_id,student_id,relationship_type}}`. 409 `ALREADY_LINKED` nếu cặp đang active. 422 nếu `parent_id`/`student_id` không tồn tại.
- `DELETE /parent-student-relationships/{id}` body `{end_reason}` → 204. 409 `ALREADY_ENDED`. 422 `end_reason` bắt buộc → "Vui lòng nhập lý do."

**AccountInvitation — mời + tạo lại liên kết (`app/Modules/UserManagement`):**
- `POST /person-profiles/{personProfileId}/invite` body `{login_identifier}` → 201 `{user:{id,login_identifier}, activation:{url,expires_at}}`. BE **tự gán vai trò** theo loại hồ sơ trong cùng transaction (`DEC-USR-018`, Student→`student`/Teacher→`teacher`/Parent→`parent`) — FE không có bước chọn vai trò. 409 `ALREADY_INVITED` nếu hồ sơ đã có User. Gửi email chỉ khi hồ sơ có email; lỗi gửi mail **không** làm hỏng request (vẫn trả 201 kèm link). `login_identifier` unique toàn hệ thống, không cần là email (`DEC-USR-014`/`017`).
- `POST /person-profiles/{personProfileId}/invitation` (không có body) → 200 cùng shape `{user, activation}` — "Tạo lại liên kết" (AD-05c). 409 `NOT_INVITED` (chưa từng mời), `ALREADY_ACTIVATED` (đã từng đăng nhập, dùng Quên mật khẩu thay), `ACCOUNT_DEACTIVATED` (tài khoản đang bị khóa, cần kích hoạt lại trước ở AD-03).
- `activation.url` chứa token thật trong query string — **không log, không hiển thị trong URL bar lâu dài**, chỉ hiện trong ô "sao chép" của dialog.

**User/Role — Tài khoản, Vai trò (AD-02/AD-03, `app/Modules/UserManagement`):**
- `GET /users?status=&role=&name=&email=&page=&per_page=` → `Paginated<{id,login_identifier,status,full_name,roles: string[]}>` (`roles` chỉ role **còn hiệu lực**). `name` dùng `ilike` thô (chưa `UnaccentSearch` — ghi "gõ có dấu" theo quy ước `admin.md`), `email` khớp vào `login_identifier`.
- `GET /users/{id}` → `{user:{id,login_identifier,status,full_name,active_roles: [{role_id,role}], role_history:[{role,valid_from,valid_to}]}}` — **đã sửa 2026-10-25:** `active_roles` giờ là mảng object kèm `role_id` (= `roles.id` thật, test end-to-end xác nhận dùng được thẳng cho `DELETE` dưới đây). `role_history` không đổi (không cần id vì không xóa được role đã kết thúc).
- `PATCH /users/{id}` body `{status:"active"|"inactive"}` → `{user:{id,status}}`. 409 `LAST_ADMIN`, 422 `ACTIVE_ASSIGNMENTS_EXIST` (kèm `active_class_ids` ngoài `error`) khi vô hiệu hóa.
- `POST /users/{id}/roles` body `{role_code}` → 201 `{user_role:{id,role,valid_from}}`. 409 `ROLE_ALREADY_HELD`.
- `DELETE /users/{id}/roles/{roleId}` (`roleId` = `active_roles[i].role_id` từ `GET /users/{id}`) → 204. 409 `LAST_ADMIN`, 422 `ACTIVE_ASSIGNMENTS_EXIST`.

---

### Task 1: Nền — types/keys/schemas cho Student, Parent, PersonProfile dùng chung

**Files:**
- Modify: `src/features/user-management/api/types.ts` — thêm `Account` (generic, thay cho `TeacherAccount` — đổi tên dần, giữ `TeacherAccount` làm alias để không phải sửa file khác ngay), `AccountState`, `StudentListItem`, `StudentDetail`, `CreateStudentInput`, `UpdateStudentInput`, `ParentListItem`, `ParentDetail`, `CreateParentInput`, `UpdateParentInput`.
- Modify: `src/features/user-management/api/keys.ts` — thêm `students(filters)`, `student(id)`, `parents(filters)`, `parent(id)`.
- Modify: `src/features/user-management/api/schemas.ts` — thêm `studentFormSchema` (`full_name` required, `email` optional hợp lệ, `phone` optional, `notes` optional multiline), `parentFormSchema` (giống, không có `notes`).
- Create: `src/features/user-management/api/person-profiles.ts` (`personProfilesApi.update(id, input)`).
- Create: `src/features/user-management/api/students.ts`, `src/features/user-management/api/parents.ts` + test MSW tương ứng.

**Hành vi (students.ts, parents.ts tương tự):**
```ts
export const studentsApi = {
  list: (filters: StudentListFilters) => apiClient.get<Paginated<StudentListItem>>("/students", { query: filters }),
  get: (id: string) => apiClient.get<{ student: StudentDetail }>(`/students/${id}`).then((r) => r.student),
  create: (input: CreateStudentInput) => apiClient.post<{ student: StudentDetail }>("/students", input).then((r) => r.student),
  updateStatus: (id: string, data: { status?: "active" | "inactive"; notes?: string | null }) =>
    apiClient.patch<{ student: StudentDetail }>(`/students/${id}`, data).then((r) => r.student),
};
```

- [ ] **Step 1:** Test trước (MSW): `GET /students` trả `Paginated`, `GET /students/{id}` trả shape không có `account`/`class_names`; `POST` 201; `PATCH` 200 với `status`/`notes` riêng lẻ (chỉ gửi field đổi); 422 `full_name` bắt buộc. Tương tự cho parents.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Pass, `pnpm typecheck`.
- [ ] **Step 5:** Commit `feat: add student and parent profile API layer`.

---

### Task 2: Nền — ParentStudentRelationship + AccountInvitation API

**Files:**
- Modify: `types.ts` — `ParentStudentLink` (`{id,parent_id,parent_name,student_id,student_name,relationship_type,status}`), `CreateLinkInput`, `EndLinkInput`, `AccountInvitation` (`{user:{id,login_identifier}, activation:{url,expires_at}}`).
- Modify: `keys.ts` — `parentStudentLinks(filters)`.
- Modify: `schemas.ts` — `inviteFormSchema` (`login_identifier` required string), `linkRelationshipSchema` (`relationship_type` optional free text, gợi ý Bố/Mẹ/Ông/Bà/Người giám hộ ở UI, không enum ở schema — F7).
- Create: `src/features/user-management/api/parent-student-relationships.ts`, `src/features/user-management/api/invitations.ts` + test.

```ts
// invitations.ts
export const invitationsApi = {
  invite: (personProfileId: string, loginIdentifier: string) =>
    apiClient.post<AccountInvitation>(`/person-profiles/${personProfileId}/invite`, { login_identifier: loginIdentifier }),
  regenerate: (personProfileId: string) =>
    apiClient.post<AccountInvitation>(`/person-profiles/${personProfileId}/invitation`),
};
export function useInvitePersonProfile() { /* mutation, invalidate theo caller truyền key */ }
export function useRegenerateInvitation() { /* mutation */ }
```

- [ ] **Step 1:** Test trước: `list` trả mảng không `meta`, gồm cả `ended`; `create` 201, 409 `ALREADY_LINKED`; `end` 204 gửi đúng `end_reason`, 409 `ALREADY_ENDED`; `invite` 201 đúng shape, 409 `ALREADY_INVITED`; `regenerate` 200, 409 `NOT_INVITED`/`ALREADY_ACTIVATED`/`ACCOUNT_DEACTIVATED` (assert đúng `error.code` từng case).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Pass, typecheck.
- [ ] **Step 5:** Commit `feat: add parent-student relationship and account invitation API`.

---

### Task 3: Hộp thoại dùng chung AD-05b (Mời tài khoản) + AD-05c (Tạo lại liên kết) — và retrofit AD-07a

**Files:**
- Create: `src/features/user-management/ui/admin/account-badge.ts` (gộp `accountBadge`/`PROFILE_BADGE` tổng quát — quyết định #9).
- Create: `src/features/user-management/ui/admin/invite-account-dialog.tsx` (AD-05b — dùng chung, nhận `personProfileId`, `displayName`, `email`, `onInvited`).
- Create: `src/features/user-management/ui/admin/regenerate-invitation-dialog.tsx` (AD-05c).
- Create tests tương ứng.
- Modify: `src/features/user-management/ui/admin/teacher-detail-screen.tsx` — thêm lại khối "Tài khoản" + nút "Mời tạo tài khoản"/"Tạo lại liên kết" (nhóm 1 đã lược, nay làm — dùng kỹ thuật quyết định #1 để lấy `account` từ `GET /teachers?name=`).
- Modify: `src/features/user-management/ui/admin/teacher-badges.ts` — xóa `accountBadge`/`PROFILE_BADGE`, import lại từ `account-badge.ts` (không phá vỡ chỗ gọi cũ, chỉ đổi nguồn).
- Modify: `index.ts` — export hai dialog trên để Student/Parent detail (Task 6, 8) dùng lại.

**Hành vi:**
- `InviteAccountDialog`: form một ô "Tên đăng nhập" (gợi ý điền sẵn = email nếu có, theo `admin.md`), dòng giải thích "liên kết hiệu lực 7 ngày", nút "Gửi lời mời" → `invitationsApi.invite`. Thành công: hiện `activation.url` trong ô chỉ-đọc + nút "Sao chép", dòng "Đã gửi thêm thư tới {email đã che một phần}" nếu có email (che kiểu `a***@gmail.com`, viết hàm nhỏ `maskEmail()` cạnh dialog, không phải quy tắc nghiệp vụ — chỉ hiển thị). Lỗi `ALREADY_INVITED` → message BE + gợi ý mở `RegenerateInvitationDialog`. 422 `login_identifier` → gán field.
- `RegenerateInvitationDialog`: không form, chỉ nút "Tạo liên kết mới" → `invitationsApi.regenerate`. Thành công hiện lại `activation.url` để sao chép. Lỗi 409 (`NOT_INVITED`/`ALREADY_ACTIVATED`/`ACCOUNT_DEACTIVATED`) hiện nguyên `error.message`.
- Khối "Tài khoản" ở `TeacherDetailScreen` (và sau đó Student/Parent): BE đã sửa 2026-10-25 nên `GET /teachers/{id}`/`/students/{id}`/`/parents/{id}` **trả thẳng `account`** trong response chi tiết — đọc `teacher.account`/`student.account`/`parent.account` trực tiếp từ `useTeacher(id)`/`useStudent(id)`/`useParent(id)`, **không cần gọi thêm API danh sách để dò**. Hiện theo `accountBadge`: chưa mời → nút "Mời tạo tài khoản"; đã mời chưa đăng nhập → tên đăng nhập + badge "Đã mời" + nút "Tạo lại liên kết"; đã hoạt động → tên đăng nhập + badge "Đang hoạt động" (không có nút tạo lại — `ALREADY_ACTIVATED` sẽ chặn ở BE nếu lỡ bấm, nhưng ẩn nút cho sạch); đã vô hiệu hóa → badge "Đã vô hiệu hóa" + liên kết sang AD-03 (route Task 9 mới có, tạm text thường nếu Task 9 chưa xong khi code tới đây — theo thứ tự task dưới đây Task 9 nằm sau nên sẽ là text thường tạm thời, ghi TODO).

- [ ] **Step 1:** Test trước cho 2 dialog (mở form/gửi đúng payload/hiện activation link/lỗi theo code) và test bổ sung ở `teacher-detail-screen.test.tsx` (khối Tài khoản hiện đúng theo 4 trạng thái).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối `index.ts`.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the shared account invitation dialogs and wire them into the teacher detail screen`.

---

### Task 4: AD-04 — Học viên (danh sách)

**Files:**
- Create: `src/features/user-management/ui/admin/student-list-screen.tsx`, `student-list-table.tsx` (mẫu `TeacherListTable`).
- Create test tương ứng.
- Modify: `index.ts` — export `StudentListScreen`.
- Create route: `src/app/(portal)/admin/students/page.tsx`.
- Modify: `config/nav.ts` — thêm mục "Học sinh" (quyết định #3).

**Hành vi:** bảng cột Họ tên, Email, SĐT, Trạng thái hồ sơ (badge), Tài khoản (badge từ `account-badge.ts`), Lớp đang học (`class_names.join(", ")` hoặc "—"). Bộ lọc: tìm tên (debounce 300ms, gọi lại API — khác AD-08/09 vì có phân trang server), trạng thái hồ sơ, tài khoản (tabs 5 giá trị giống AD-07), "Có lớp/chưa có lớp". Chip đầu danh sách "Chưa có tài khoản: N" tính từ `data.meta.total` của một query phụ `useStudents({account:"none", per_page:1})` (tương tự cách AD-01 ghép tạm bằng `meta.total`). Nút "Thêm học viên" → `/admin/students/new`. Trạng thái tải/rỗng/lỗi theo mẫu AD-07.

- [ ] **Step 1:** Test trước.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối route/nav/index.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-04 student list screen`.

---

### Task 5: AD-05a — Học viên (tạo / sửa)

**Files:**
- Create: `src/features/user-management/ui/admin/student-form-screen.tsx` (mẫu `teacher-form-screen.tsx`).
- Create test.
- Modify: `index.ts`.
- Create routes: `src/app/(portal)/admin/students/new/page.tsx`, `src/app/(portal)/admin/students/[studentId]/edit/page.tsx`.

**Hành vi:** giống `TeacherFormScreen` nhưng field "Ghi chú" dùng `Input multiline` (đã có từ nhóm 2), không có "Chuyên môn". Sửa: gọi `personProfilesApi.update` rồi `studentsApi.updateStatus` tuần tự (hai request, một nút Lưu, gộp lỗi — đúng mẫu `teachers.ts`). Tạo: chỉ `studentsApi.create`. Lưu xong → `/admin/students/{id}` (Task 6), card "Sau khi lưu" gợi ý "Mời tạo tài khoản" + "Thêm vào lớp" (đúng `admin.md`, không phải chip tĩnh — dùng `Tip` component có sẵn). **Không** build cảnh báo trùng tên/SĐT (quyết định #7).

- [ ] **Step 1:** Test trước (tạo thành công điều hướng đúng URL, sửa chỉ gửi field đổi, 422 `full_name`/`email` gán field).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối route/index.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-05a student create/edit form`.

---

### Task 6: AD-06 — Phụ huynh (danh sách) + AD-06b — Phụ huynh (tạo / sửa)

**Files:**
- Create: `src/features/user-management/ui/admin/parent-list-screen.tsx`, `parent-list-table.tsx`, `parent-form-screen.tsx`.
- Create tests.
- Modify: `index.ts`.
- Create routes: `src/app/(portal)/admin/parents/page.tsx`, `.../new/page.tsx`, `.../[parentId]/edit/page.tsx`.
- Modify: `config/nav.ts` — thêm "Phụ huynh".

**Hành vi:** gần như sao chép Task 4+5 nhưng: cột "Số con liên kết" thay "Lớp đang học" (`children_count`); thêm bộ lọc "Có con liên kết/chưa có" (`has_children`); chip cảnh báo riêng dòng "chưa liên kết con nào" (badge cam cạnh dòng, không chặn gì — đúng `admin.md` "cổng Phụ huynh sẽ trống"); form không có "Ghi chú". Gộp làm một task vì lượng code nhỏ và lặp cấu trúc Student, để tránh rã quá nhiều task nhỏ giống nhau.

- [ ] **Step 1:** Test trước cho cả list và form.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối route/nav/index.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-06 parent list screen and the AD-06b create/edit form`.

---

### Task 7: AD-06c — Liên kết phụ huynh–học sinh (hộp thoại dùng chung)

**Files:**
- Create: `src/features/user-management/ui/admin/link-parent-student-dialog.tsx` (dùng chung 2 hướng — mẫu `ClassTeacherAssignDialog`: ô tìm + danh sách chọn).
- Create: `src/features/user-management/ui/admin/end-link-dialog.tsx` (M4, lý do bắt buộc).
- Create tests.
- Modify: `index.ts`.

**Hành vi:**
```ts
<LinkParentStudentDialog
  open fromKind="student" // hoặc "parent"
  fromId={student.id} fromName={student.full_name}
  existingLinkedIds={[...]} // id phía đối diện đã liên kết active, loại khỏi danh sách chọn
  onClose onLinked
/>
```
- `fromKind="student"`: gọi `useParents({per_page:100, name:debouncedSearch})`, chọn một phụ huynh, ô "Loại quan hệ" (free text, gợi ý chip Bố/Mẹ/Ông/Bà/Người giám hộ điền sẵn vào ô — vẫn sửa được, đúng mẫu M4). Submit → `createLink({parent_id: picked, student_id: fromId, relationship_type})`.
- `fromKind="parent"`: ngược lại, gọi `useStudents`.
- Lỗi `ALREADY_LINKED` hiện nguyên message BE trong dialog, giữ dialog mở. 422 `parent_id`/`student_id` không tồn tại → hiếm khi xảy ra qua UI (chỉ chọn từ danh sách có thật) nhưng vẫn hiện banner lỗi chung nếu có.
- `EndLinkDialog`: tên hai bên + ô lý do bắt buộc → `endLink({id, end_reason})`. Lỗi `ALREADY_ENDED` → message BE + tự `refetch()` danh sách liên kết (ai đó đã kết thúc trước).

- [ ] **Step 1:** Test trước (2 hướng, loại trừ id đã liên kết, lỗi `ALREADY_LINKED`/`ALREADY_ENDED`/422 lý do bắt buộc).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối `index.ts`.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the shared AD-06c parent-student link dialogs`.

---

### Task 8: AD-05 — Học viên (chi tiết)

**Files:**
- Create: `src/features/user-management/ui/admin/student-detail-screen.tsx`.
- Create test.
- Modify: `index.ts`.
- Create route: `src/app/(portal)/admin/students/[studentId]/page.tsx`.
- Modify: `src/features/results/` (mới, quyết định #6) — `api/progress.ts`, `index.ts`.

**Hành vi (mẫu M3 một trang, không tab — quyết định #11):**
- Khối 1: tên, chip trạng thái hồ sơ, nút "Sửa" (→ Task 5 route edit), nút chuyển trạng thái (M4 không lý do, mẫu `TeacherStatusDialog` tổng quát hóa thành `ProfileStatusDialog` dùng chung — tách file mới `profile-status-dialog.tsx`, Student/Parent/Teacher đều dùng được vì nội dung chỉ khác tên+đại từ, truyền qua props).
- Khối 2: Email, SĐT, Ghi chú.
- Khối 3 "Tài khoản": đọc thẳng `student.account` từ `useStudent(studentId)` (đã có sẵn trong response chi tiết, không cần request phụ — quyết định #1 cập nhật 2026-10-25), dùng `InviteAccountDialog`/`RegenerateInvitationDialog` (Task 3) — liên kết "Xem chi tiết tài khoản" trỏ `/admin/accounts/{userId}` nếu đã có User (Task 9 có route này).
- Khối 4 "Lớp đang học": `student.classes` (`[{id,name}]`) dạng chip **bấm được**, mỗi chip link `/admin/classes/{classId}` (quyết định #12 cập nhật 2026-10-25).
- Khối 5 "Phụ huynh liên kết": `useParentStudentLinks({student_id: studentId})`, tách "Đang liên kết" (`status==='active'`)/"Đã kết thúc"; nút "Liên kết phụ huynh" mở `LinkParentStudentDialog fromKind="student"`; mỗi dòng active có nút "Kết thúc liên kết" mở `EndLinkDialog`. Tên phụ huynh bấm được → `/admin/parents/{parent_id}` (route này do Task 9 tạo — chấp nhận link "tới trước" trong cùng nhánh/PR, xem ghi chú ở Task 9).
- Khối 6 "Tiến độ": `useStudentProgress(studentId)` (feature `results` mới) — 3 số (điểm TB, số bài đã chấm, số bài chờ chấm), không chart.

- [ ] **Step 1:** Test trước (tải/lỗi/404/hồ sơ inactive banner/khối rỗng từng mục/mở 2 dialog liên kết+mời).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement (gồm `profile-status-dialog.tsx` dùng chung và `features/results` tối giản).
- [ ] **Step 4:** Nối route/index. **Không** sửa `teacher-detail-screen.tsx` để dùng `ProfileStatusDialog` chung ngay (tránh rủi ro hồi quy không cần thiết cho lượt này) — ghi nợ kỹ thuật trong comment.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-05 student detail screen`.

---

### Task 9: AD-06a — Phụ huynh (chi tiết)

**Files:**
- Create: `src/features/user-management/ui/admin/parent-detail-screen.tsx`.
- Create test.
- Modify: `index.ts`.
- Create route: `src/app/(portal)/admin/parents/[parentId]/page.tsx`.

**Hành vi:** gương Task 8 nhưng: không có khối "Tiến độ"/"Ghi chú"; khối "Các con liên kết" dùng `LinkParentStudentDialog fromKind="parent"`, tên học sinh bấm được → `/admin/students/{student_id}` (đã có từ Task 8). Vì Task 8 tạo trước route Student detail, và Task 9 tạo route Parent detail — tên phụ huynh ở Task 8 link tới route Task 9 tạo: **chấp nhận link "tới trước" trong 1 commit, không chặn** vì cả hai route nằm trong cùng PR/nhánh trước khi merge, không có trạng thái trung gian deploy riêng lẻ.

- [ ] **Step 1:** Test trước.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối route/index.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-06a parent detail screen`.

---

### Task 10: Nền — User/Role API (AD-02/AD-03)

**Files:**
- Modify: `types.ts` — `UserListItem`, `UserDetail`, `UserListFilters`, `AssignRoleInput`.
- Modify: `keys.ts` — `users(filters)`, `user(id)`.
- Create: `src/features/user-management/api/users.ts` + test.

```ts
export const usersApi = {
  list: (filters: UserListFilters) => apiClient.get<Paginated<UserListItem>>("/users", { query: filters }),
  get: (id: string) => apiClient.get<{ user: UserDetail }>(`/users/${id}`).then((r) => r.user),
  updateStatus: (id: string, status: "active" | "inactive") =>
    apiClient.patch<{ user: Pick<UserDetail, "id" | "status"> }>(`/users/${id}`, { status }),
  assignRole: (id: string, roleCode: string) =>
    apiClient.post<{ user_role: { id: string; role: string; valid_from: string } }>(`/users/${id}/roles`, { role_code: roleCode }),
};
```

- [ ] **Step 1:** Test trước: list phân trang; detail đúng shape `active_roles`/`role_history`; `updateStatus` 409 `LAST_ADMIN`/422 `ACTIVE_ASSIGNMENTS_EXIST`; `assignRole` 409 `ROLE_ALREADY_HELD`.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Pass, typecheck.
- [ ] **Step 5:** Commit `feat: add the user account and role API layer`.

---

### Task 11: AD-02 — Danh sách tài khoản + AD-03 — Chi tiết tài khoản

**Files:**
- Create: `src/features/user-management/ui/admin/account-list-screen.tsx`, `account-list-table.tsx`, `account-detail-screen.tsx`, `role-assign-dialog.tsx`, `end-role-dialog.tsx` (M4, không lý do — gọi `DELETE /users/{id}/roles/{role_id}`).
- Create tests.
- Modify: `index.ts`.
- Create routes: `src/app/(portal)/admin/accounts/page.tsx`, `src/app/(portal)/admin/accounts/[userId]/page.tsx`.
- Modify: `config/nav.ts` — thêm "Tài khoản".

**Hành vi AD-02:** bảng Họ tên (`full_name` có thể null → "(chưa có hồ sơ)"), Tên đăng nhập, Vai trò (mỗi vai trò một `Badge`), Trạng thái. Bộ lọc tìm tên/tên đăng nhập, dropdown vai trò (4 giá trị cố định `admin/teacher/student/parent`, nhãn theo M6), dropdown trạng thái. Không có nút tạo. Menu "…": Xem chi tiết (đi tới AD-03).

**Hành vi AD-03:**
- Đầu trang: họ tên, tên đăng nhập, chip trạng thái, nút "Vô hiệu hóa"/"Kích hoạt lại" (M4 không lý do, cảnh báo thêm "bạn sẽ bị đăng xuất" nếu `user.id === me.id` — đọc từ query `['auth','me']` đã có sẵn trong app, không tạo cách đọc vai trò mới).
- Khối "Vai trò đang giữ": `active_roles` (`[{role_id,role}]`) map badge; nút "Gán vai trò" mở `RoleAssignDialog` (dropdown 4 role, ẩn role đã giữ) → `assignRole`; nút "Kết thúc vai trò" mỗi thẻ: **đầy đủ chức năng** (quyết định #2 cập nhật 2026-10-25, BE đã sửa) — mở `EndRoleDialog` (M4, không lý do), gọi `DELETE /users/{id}/roles/{role_id}` với đúng `role_id` của thẻ đó.
- Khối "Lịch sử vai trò": bảng `role_history`, dòng có `valid_to` hiển thị mờ.
- `LAST_ADMIN` → message BE, đổi câu theo ngữ cảnh (vô hiệu hóa tài khoản vs sẽ không cần vì nút kết thúc vai trò đã disable — chỉ áp dụng cho nhánh "Vô hiệu hóa"). `ACTIVE_ASSIGNMENTS_EXIST` → liệt kê tên lớp (cần `GET /classes` để tra tên theo id — gọi `useClasses()` từ `@/features/academic`, đúng F5 qua `index.ts`).
- Không build khối "Hồ sơ gắn tài khoản" (GM-1) — ghi TODO comment trỏ về quyết định #1 (khối đó chỉ khả thi từ chiều Student/Parent/Teacher→User, không có chiều ngược User→hồ sơ nào đủ dữ liệu để đoán đúng 100%; không đoán khi không chắc).

- [ ] **Step 1:** Test trước cho cả 2 màn (tải/rỗng/lỗi/lọc/gán vai trò thành công+409/vô hiệu hóa thành công+`LAST_ADMIN`+`ACTIVE_ASSIGNMENTS_EXIST` hiện tên lớp/kết thúc vai trò thành công gọi đúng `DELETE .../roles/{role_id}`+`LAST_ADMIN` khi kết thúc role admin cuối).
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Nối route/nav/index.
- [ ] **Step 5:** Pass, lint, typecheck.
- [ ] **Step 6:** Commit `feat: add the AD-02 account list and AD-03 account detail screens`.

---

### Task 12: Mở khóa nút "Thêm học sinh" ở AD-10b

**Files:**
- Create: `src/features/user-management/ui/admin/student-picker-dialog.tsx` (mẫu `ClassTeacherAssignDialog`: ô tìm debounce + `useStudents({per_page:100, name})`, loại trừ `excludeStudentIds`).
- Modify: `src/features/user-management/ui/admin/class-member-add-button.tsx` — bỏ `disabled`/`Tooltip`, nhận props `classId`, `excludeStudentIds`, mở `StudentPickerDialog` khi bấm.
- Modify: `src/features/user-management/ui/admin/class-members-tab.tsx` — truyền `excludeStudentIds={members.map(m => m.student_id)}` xuống nút; dùng lại **đúng** `useAddClassMember(classId)` đã có từ nhóm 2 (không viết lại hook) khi chọn một học sinh trong dialog.
- Modify tests: `class-member-add-button.test.tsx` (mới, hoặc gộp vào `class-members-tab.test.tsx` nếu chưa có file riêng — kiểm lại trước khi quyết định tách), `class-members-tab.test.tsx` (case mới: chọn học sinh → gọi đúng `POST /classes/{id}/members`, lỗi `ALREADY_A_MEMBER` hiện trong dialog không phải toast).

**Hành vi:**
- `StudentPickerDialog`: giống `ClassTeacherAssignDialog` nhưng chọn nguồn `/students` (chỉ hồ sơ `status==="active"` hiện bình thường, `inactive` hiện mờ — theo đúng phát hiện ở `admin.md` "code không chặn thêm học viên inactive vào lớp, nhưng flows/class-management.md nói không nên" → quyết định FE: **không tự chặn** (F7, BE là nguồn sự thật), chỉ làm mờ + để admin tự quyết, giống cách AD-10c xử lý giáo viên inactive).
- Chọn một học sinh → `useAddClassMember(classId).mutate({student_id})`. Thành công: đóng dialog, danh sách tự invalidate (hook đã làm). Lỗi `ALREADY_A_MEMBER` (409, đã đọc từ nhóm 2) → hiện trong dialog, giữ dialog mở để chọn người khác.

- [ ] **Step 1:** Test trước: dialog hiện đúng danh sách (loại trừ đã có, inactive mờ), chọn → gọi đúng API, lỗi `ALREADY_A_MEMBER` hiện trong dialog, nút "Thêm học sinh" không còn disabled.
- [ ] **Step 2:** Fail.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Pass, lint, typecheck.
- [ ] **Step 5:** Commit `feat: unlock the AD-10b add-student button with a GET /students picker dialog`.

---

### Task 13: Test thủ công qua `piclass-infra` thật

Không viết code — chạy tay theo bước 5 `working-flow.md`. `docker compose build web && docker compose up -d` trước khi test.

- [ ] Tạo học sinh mới ở AD-05a → vào AD-05, mời tạo tài khoản (AD-05b) → sao chép liên kết, mở ẩn danh/trình duyệt khác dán vào `/activate` → đặt mật khẩu → đăng nhập bằng tên đăng nhập vừa tạo, xác nhận vai trò `student` tự có (không cần AD-03 gán tay).
- [ ] Ở AD-03 của tài khoản vừa tạo, xác nhận `active_roles` hiện "Học sinh" ngay, nút "Kết thúc vai trò" bị khóa kèm tooltip đúng nội dung.
- [ ] Tạo lại liên kết (AD-05c) cho một hồ sơ đã mời nhưng chưa đăng nhập lần nào → xác nhận liên kết cũ hết tác dụng (dán link cũ vào `/activate` phải lỗi).
- [ ] Thử tạo lại liên kết cho một tài khoản đã từng đăng nhập → xác nhận lỗi `ALREADY_ACTIVATED` hiện đúng, không tạo được link mới.
- [ ] Tạo phụ huynh mới (AD-06b) → liên kết với học sinh vừa tạo ở AD-06c → xác nhận AD-05 của học sinh đó hiện đúng tên phụ huynh, bấm vào mở đúng AD-06a.
- [ ] Kết thúc liên kết vừa tạo (lý do bắt buộc) → xác nhận chuyển sang "Đã kết thúc" ở cả hai chiều (AD-05 và AD-06a), không xóa dòng.
- [ ] Ở AD-02, lọc theo vai trò "Học sinh" → thấy đúng tài khoản vừa tạo; vô hiệu hóa tài khoản đó → xác nhận AD-05 (khối Tài khoản) hiện đúng trạng thái mới (có thể cần tải lại trang do dùng query riêng theo tên — ghi nhận hành vi thật).
- [ ] Thử vô hiệu hóa Admin cuối cùng (chính tài khoản đang đăng nhập nếu là Admin duy nhất) → xác nhận `LAST_ADMIN` hiện đúng, không mất quyền.
- [ ] Ở AD-10b (lớp đã tạo ở nhóm 2), bấm "Thêm học sinh" (không còn khóa) → tìm và chọn học sinh vừa tạo → xác nhận roster cập nhật ngay không cần tải lại.
- [ ] Thử thêm lại đúng học sinh đó lần hai (ép qua devtools hoặc mở 2 tab) → xác nhận `ALREADY_A_MEMBER` hiện trong dialog.
- [ ] Mở khối "Tài khoản" ở AD-07a (giáo viên đã tạo ở nhóm 1 nhưng chưa mời) → mời tạo tài khoản → xác nhận vai trò `teacher` tự gán, không cần bước AD-03 riêng.
- [ ] Kiểm tra trên khổ máy tính bảng (DevTools) các màn AD-02/03/04/05/06/06a đều không tràn ngang.

## Tóm tắt các quyết định tự chọn cần user xác nhận

1. ~~Khối "Tài khoản" ở AD-05/06a/07a: lấy `account` bằng cách gọi lại danh sách rồi khớp theo id~~ — **không cần nữa**: BE đã sửa 2026-10-25, `GET /students|parents|teachers/{id}` trả thẳng `account`.
2. ~~Nút "Kết thúc vai trò" ở AD-03: khóa + tooltip~~ — **không cần nữa**: BE đã sửa 2026-10-25, `active_roles` có `role_id` dùng được thẳng, nút làm đầy đủ chức năng.
3. Thứ tự menu mới: `Tổng quan, Tài khoản, Học sinh, Phụ huynh, Giáo viên, Môn học, Lớp học`.
4. AD-05c build đúng tính năng BE đã có ("Tạo lại liên kết" qua `POST /person-profiles/{id}/invitation"), không build lại hướng dẫn "Quên mật khẩu" lạc hậu trong mô tả cũ của `admin.md`.
5. Khối "Tiến độ" AD-05: chỉ 3 số tổng hợp qua feature `results` mới tạo tối giản, không chart/điểm danh (giữ nguyên gap 🟡 G8/G3).
6. Không build cảnh báo trùng tên/SĐT ở AD-05a/AD-06b (nhất quán với quyết định đã bỏ ở AD-07b, nhóm 1).
7. ~~"Lớp đang học" ở AD-05 là chip tĩnh không click được~~ — **không cần nữa**: BE đã sửa 2026-10-25, `classes: [{id,name}]` cho phép link thẳng `/admin/classes/{id}`.
8. Gộp `accountBadge`/`PROFILE_BADGE` của Teacher thành file chung `account-badge.ts`/`profile-badge.ts`, dùng lại cho Student/Parent (không viết 3 lần).
9. Task 8 (AD-05) link sang route Parent detail do Task 9 tạo, trước khi Task 9 chạy — chấp nhận vì cùng một PR/nhánh, không có trạng thái deploy trung gian.

**Cập nhật 2026-10-25:** quyết định #1, #2, #7 đã được thay bằng sửa BE thật (3 commit trên `feat/invite-auto-assign-role`: thêm `account` vào Resource chi tiết, thêm `role_id` vào `active_roles`, đổi `class_names` thành `classes: [{id,name}]`) — không còn là workaround FE nữa, giữ lại ở đây để biết lý do các Task 3/8/9/11 không còn nhánh "disabled/gọi thêm API" như bản nháp đầu.

Toàn bộ quyết định trên có thể đổi dễ dàng nếu user muốn khác — chưa có code nào được viết (đây là plan, chưa triển khai).

### Các file quan trọng nhất đã đọc để lập plan này

- `docs/product/screens/admin.md` (mục AD-02, AD-03, AD-04, AD-05/05a/05b/05c, AD-06/06a/06b/06c)
- `docs/product/screens/mockup-plan.md` (mục 3), `docs/requirements/decision-log.md` (`DEC-USR-013`…`018`), `docs/product/screens/api-gaps.md` (A7, A8, A2, A9, C3, C7, GM-1, GM-6)
- `piclass-api/app/Modules/UserManagement/` toàn bộ phần Student/Parent/PersonProfile/ParentStudentRelationship/Invite/RegenerateInvitation/User/Role (Queries/Resources/Actions/Controllers/Requests/Exceptions)
- `piclass-web/src/features/user-management/` toàn bộ (mẫu teacher-*, class-member-*, class-teacher-*)
- `piclass-web/CLAUDE.md`, `docs/architecture/module-boundaries.md`, `docs/architecture/error-codes.md`

### Critical Files for Implementation

- piclass-web/src/features/user-management/api/types.ts
- piclass-web/src/features/user-management/api/students.ts (và parents.ts, invitations.ts, parent-student-relationships.ts, users.ts — cùng thư mục)
- piclass-web/src/features/user-management/ui/admin/student-detail-screen.tsx (và parent-detail-screen.tsx, account-detail-screen.tsx)
- piclass-web/src/features/user-management/ui/admin/class-member-add-button.tsx
- piclass-api/app/Modules/UserManagement/Actions/InvitePersonProfile.php (hợp đồng mời + tự gán vai trò, `DEC-USR-018`)
