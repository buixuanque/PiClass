# Step 9 — API Specification: Identity/Auth + User Management

**Trạng thái:** Spec đã được duyệt theo từng section qua brainstorming (`superpowers:brainstorming`), chờ user review file này trước khi sang `writing-plans`.
**Phạm vi:** Nhóm module đầu tiên của Step 9 (theo thứ tự review ở `docs/progress.md`) — `Identity/Auth` + `User Management` theo ranh giới tại `docs/architecture/module-boundaries.md`. Không bao gồm `Class` (Academic — nhóm tiếp theo), `Attendance`, `Assessment`, hay bất kỳ module nào khác.
**Không tạo business rule mới.** Mọi hành vi dưới đây chỉ chuyển các rule đã chốt ở `docs/requirements/user-rules.md`, `docs/access-control/*`, `docs/database/schema.md` thành endpoint cụ thể.

## 1. Mục tiêu

Định nghĩa REST API giữa Next.js (frontend tách riêng) và Laravel (backend), phục vụ: đăng nhập/đăng xuất/đổi-quên mật khẩu, chọn role context cho user đa role, quản trị User/Role, hồ sơ Student/Parent/Teacher, Parent–Student relationship, và 2 sub-resource ClassMember/TeacherClassAssignment.

## 2. Quy ước chung (áp dụng cho mọi module Step 9, không riêng nhóm này)

- **Base path:** `/api/v1/...`.
- **Field casing:** `snake_case`, khớp tên cột trong `database/schema.md`.
- **Pagination:** kiểu "page" — query `?page=&per_page=`; response bọc `{ "data": [...], "meta": { "total", "current_page", "last_page" } }`.
- **Error format:** `{ "error": { "code": "...", "message": "..." } }`, dùng đúng HTTP status (`401` chưa đăng nhập, `403` không đủ quyền, `404` không tìm thấy/không có quyền biết là có tồn tại, `409` xung đột trạng thái, `422` validation).
- **Permission gate:** mọi endpoint áp dụng chuỗi kiểm tra ở `docs/access-control/access-rules.md` §1 — User active → role/permission hợp lệ cho action → resource đúng scope → relationship hiệu lực → lifecycle/visibility. List và get-one dùng cùng scope; biết ID không cấp quyền.

## 3. Identity/Auth

### 3.1 Role context cho user đa role

Quyết định (`ARCH-11`, đã ghi vào `architecture/open-decisions.md`): context lưu trạng thái ở server, không phải header/param client tự quản. User chọn role sau khi login; context hiện hành ảnh hưởng mọi request sau đó cho tới khi đổi lại. Giữ nguyên multi-role trên 1 account (`BR-USER-002`, đã cân nhắc lại phương án tách account riêng và quyết định không đổi, vì user chính của V1 cần đổi vai liên tục trong một buổi làm việc, và Sanctum cookie-session không cho 2 account cùng lúc trong 1 browser).

### 3.2 Endpoint

| Method & Path | Việc gì | Request | Response / lưu ý |
|---|---|---|---|
| `GET /sanctum/csrf-cookie` | Lấy CSRF cookie bắt buộc trước login | — | Set cookie `XSRF-TOKEN` |
| `POST /api/v1/auth/login` | Đăng nhập | `login_identifier`, `password`, `remember` (boolean) | `200`: `{ user: {...}, available_roles: [...] }`. `422` sai thông tin. `429` nếu vượt rate limit. |
| `POST /api/v1/auth/logout` | Đăng xuất session hiện hành | — | `204` |
| `GET /api/v1/auth/me` | Thông tin user hiện tại | — | `{ user, active_role, available_roles }`. `401` nếu chưa login. |
| `POST /api/v1/auth/context` | Chọn/đổi role context | `role_code` (`admin`/`teacher`/`student`/`parent`) | `200` cập nhật context trong session. `403` nếu user không có role đó. |
| `POST /api/v1/auth/password/forgot` | Gửi email đặt lại mật khẩu | `login_identifier` | `202` luôn (không lộ tài khoản có tồn tại hay không) |
| `POST /api/v1/auth/password/reset` | Đặt mật khẩu mới bằng token | `token`, `password` | `200` / `422` token sai hoặc hết hạn (60 phút, dùng một lần) |
| `PATCH /api/v1/me/password` | Đổi mật khẩu khi đã đăng nhập | `current_password`, `new_password` | `200` / `422` sai mật khẩu cũ |

### 3.3 Giá trị chính sách (`ARCH-05`, đã chốt)

| Chính sách | Giá trị |
|---|---|
| Độ mạnh mật khẩu | Tối thiểu 8 ký tự, không bắt buộc ký tự đặc biệt |
| Rate limit login | 5 lần sai/phút/tài khoản+IP, throttle tăng dần |
| Hạn reset token | 60 phút, dùng một lần |
| Session thường (`remember=false`) | Hết hạn sau 1 ngày không hoạt động |
| Session remember-me (`remember=true`) | 30 ngày, hoặc tới khi logout/đổi mật khẩu |
| Nhiều session đồng thời | Cho phép; logout chỉ hủy session hiện tại |

## 4. User & Role Management (Admin)

Tách rõ "tạo hồ sơ" khỏi "tạo tài khoản đăng nhập", đúng `BR-USER-001` (hồ sơ có thể tồn tại trước tài khoản).

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/users` | Danh sách User (lọc role/status/tên/email) | Admin |
| `GET /api/v1/users/{id}` | Chi tiết User + role hiện hành + lịch sử `UserRole` | Admin |
| `PATCH /api/v1/users/{id}` | Sửa `status` (`active`/`inactive`) | Admin. Chặn vô hiệu hóa Admin cuối cùng còn quyền quản trị (`BR-USER-007`) → `409`. Nếu target là Teacher còn `TeacherClassAssignment`/`Schedule` hoạt động → `422` kèm danh sách assignment cần xử lý trước (`BR-USER-010`). |
| `POST /api/v1/users/{id}/roles` | Gán thêm Role (`UserRole` mới) | Admin |
| `DELETE /api/v1/users/{id}/roles/{role_id}` | Kết thúc Role — set `valid_to`, không xóa hàng | Admin |
| `POST /api/v1/students`, `/api/v1/parents`, `/api/v1/teachers` | Tạo PersonProfile + hồ sơ vai trò tương ứng — **chưa tạo User** | Admin |
| `GET /api/v1/students/{id}` (tương tự parents/teachers) | Xem field riêng vai trò (`status`, `notes`, `specialty`...) | Admin toàn bộ; Teacher chỉ khi Student thuộc Class đang được phân công (theo `resource-permissions.md`) |
| `PATCH /api/v1/students/{id}` (tương tự parents/teachers) | Sửa field riêng vai trò | Chỉ Admin |
| `PATCH /api/v1/person-profiles/{id}` | Sửa `full_name`/contact chung (dùng chung giữa các vai trò của 1 người) | Admin |
| `POST /api/v1/person-profiles/{id}/invite` | Tạo User + gửi email đặt mật khẩu lần đầu | Admin |

## 5. Profile self-service (Student/Teacher/Parent)

Theo `BR-USER-006`: tự sửa contact cơ bản, không tự sửa định danh/trạng thái/hồ sơ nghiệp vụ/quan hệ.

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/me/profile` | Xem hồ sơ của chính mình (PersonProfile + role profile theo context hiện hành) | Mọi role đã đăng nhập |
| `PATCH /api/v1/me/profile` | Sửa `email`/`phone` của chính mình | Student, Teacher, Parent. `403` nếu payload có `full_name`/`status`. |

## 6. Parent xem Student liên kết

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/me/children` | Danh sách Student có `ParentStudentRelationship.status=active` | Parent |
| `GET /api/v1/me/children/{student_id}` | Hồ sơ cơ bản 1 Student liên kết | Parent, chỉ khi relationship hiệu lực. `404` (không `403`) nếu không còn liên kết — tránh lộ "đã từng là con của Parent này" qua phân biệt mã lỗi. |

## 7. Admin quản lý Parent–Student relationship

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/parent-student-relationships` | Tạo + xác nhận liên kết, ghi `relationship_type` nếu có (`BR-USER-012`) | Admin |
| `DELETE /api/v1/parent-student-relationships/{id}` | Gỡ liên kết — `status=ended`, `valid_to`, bắt buộc `end_reason` | Admin. Thu hồi quyền Parent xem ngay kể cả lịch sử qua Parent portal (`BR-USER-014`), không xóa hàng. |
| `GET /api/v1/parent-student-relationships?student_id=&parent_id=` | Danh sách/lọc | Admin |

## 8. ClassMember + TeacherClassAssignment

`Class` thuộc module Academic (spec ở nhóm review tiếp theo); ở đây chỉ định nghĩa 2 sub-resource mà `module-boundaries.md` xác định thuộc User Management, nested dưới `class_id`.

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/classes/{class_id}/members` | Roster lớp | Admin (toàn bộ); Teacher chỉ khi có `TeacherClassAssignment` hiệu lực với lớp đó |
| `POST /api/v1/classes/{class_id}/members` | Thêm Student vào lớp | Chỉ Admin |
| `DELETE /api/v1/classes/{class_id}/members/{student_id}` | Kết thúc membership | Chỉ Admin, bắt buộc `end_reason` |
| `PATCH /api/v1/classes/{class_id}/members/{student_id}` | Sửa hồi tố `valid_from`/`valid_to` | Chỉ Admin, bắt buộc `reason` (`BR-USER-017`) |
| `GET /api/v1/classes/{class_id}/teachers` | Danh sách Teacher phụ trách | Admin; Teacher đọc được assignment của chính lớp mình |
| `POST /api/v1/classes/{class_id}/teachers` | Phân công Teacher | Chỉ Admin |
| `DELETE /api/v1/classes/{class_id}/teachers/{teacher_id}` | Kết thúc phân công | Chỉ Admin |

"Chỉ Admin" = chỉ role Admin được gọi; Teacher/Student/Parent gọi bị từ chối `403`.

## 9. Quyết định phát sinh trong lúc thiết kế (đã ghi ngược vào tài liệu nguồn)

- `ARCH-05` (chính sách auth) và `ARCH-11` (role context) được chốt trong phiên này — đã cập nhật `docs/architecture/open-decisions.md` và `docs/architecture/technology-decisions.md`.
- Việc tách account theo role (thay cho multi-role) được cân nhắc lại và **giữ nguyên quyết định cũ** (`BR-USER-002` không đổi) — không cần sửa `requirements/*`.

## 10. Ngoài phạm vi spec này

- CRUD đầy đủ cho `Class`, `Subject`, `Schedule`, `LessonSession` — thuộc nhóm module Academic/Lesson, spec riêng ở lượt review tiếp theo.
- Toàn bộ Attendance, Question Bank, Assessment, Submission/Grading, Result/Progress, Notification, File, Audit — các nhóm module sau theo thứ tự ở `docs/progress.md`.
- Chi tiết OpenAPI/schema validation từng field, rate-limit header response, cấu hình CORS cụ thể giữa domain Next.js/Laravel — thuộc `writing-plans`/implementation, không phải quyết định thiết kế ở spec này.

## 11. Nguồn tham khảo

`docs/requirements/user-rules.md`, `docs/access-control/roles.md`, `permission-matrix.md`, `data-scope.md`, `resource-permissions.md`, `access-rules.md`, `docs/database/schema.md`, `docs/architecture/module-boundaries.md`, `docs/architecture/technology-decisions.md`, `docs/architecture/authentication-and-security.md`.
