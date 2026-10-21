# Task 9f — Refactor module `Academic` sang quy ước module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** 14 controller của `Academic` (Subject 3, Class 4, Course 4, CourseEnrollment 3) chuyển sang `app/Modules/Academic/` theo `piclass-api/CLAUDE.md` (FormRequest + Policy + Action/Query + Resource), dùng `ActiveRole`, Enum cho `status` của `Subject`/`SchoolClass`/`Course`/`CourseEnrollment`, và nhận lại việc đổi trạng thái lớp (`preparing` ↔ `active`) đang nằm sai chỗ ở `UserManagement`. Không đổi HTTP status, `error.code`, `error.message`, key JSON, giá trị chuỗi lưu DB — trừ ngoại lệ D5. **Một PR, một lượt review Opus ở cuối.**

**Architecture:** Module `app/Modules/Academic/{Http,Actions,Queries,Policies,Enums,Providers}`. Hai Policy (`ClassPolicy`, `CoursePolicy`) thay các khối `match`/`abort_unless` đọc `active_role_code` từ session. Bốn Query cho bốn endpoint danh sách có phạm vi. Hai Action `ActivateClass`/`RevertClassToPreparing` là nơi duy nhất ghi `SchoolClass.status` giữa `preparing` và `active`; hai Action của `UserManagement` gọi chúng qua DI (thay cho ghi trực tiếp Model của Academic, nợ `ARCH-23(n)`). Academic không có audit, không có thông báo, không có exception nghiệp vụ riêng.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, PHPUnit 12, Pint. Container `piclass-local-api-1`.

**Spec / nguồn:** `piclass-api/CLAUDE.md`; `docs/architecture/backend-refactor-playbook.md` (bẫy giữ nguyên hành vi, checklist test phân quyền, nợ đã biết); `docs/superpowers/specs/2026-10-14-academic-lesson-attendance-api-design.md` §3 (Subject), §4 + §4.1 (Class, tự chuyển trạng thái), phần Course/CourseEnrollment; `docs/requirements/learning-rules.md` (`BR-CLASS-001..004`, `BR-COURSE-001..004`), `decision-log.md` (`DEC-LRN-001`, `DEC-LRN-010`, `DEC-AUD-001/005`); `docs/access-control/*` (`resource-permissions.md`, `data-scope.md`, `permission-matrix.md`); `docs/database/schema.md`; `ARCH-14`, `ARCH-17`, `ARCH-20`, `ARCH-21`, `ARCH-23(n)`; plan mẫu `2026-10-21-be-lesson.md` (khung một PR, Policy dùng chung trait, Enum, đột biến) và `2026-10-22-be-question-bank.md`.

---

## Quy tắc cho người thực thi (đọc trước khi làm bất cứ gì)

1. **Điểm mơ hồ nhỏ, không đổi hành vi/phân quyền/audit:** chọn phương án đơn giản nhất, ghi một dòng `Ruling:` vào báo cáo cuối task. **Điểm đổi hành vi, phân quyền hoặc audit, hoặc cần quyết định không có trong plan: DỪNG và báo** (nêu file, dòng, các phương án).
2. Các tình huống bắt buộc DỪNG (không giới hạn ở đây):
   - Một test đặc tả (Task 1) **fail trên code cũ**. Không sửa kỳ vọng cho khớp code, không sửa code. Ngoại lệ duy nhất: **thăm dò 500** (quy tắc 8).
   - Một test cũ fail sau khi đổi code vì **hành vi khác**. Chỉ được sửa test cũ khi đổi namespace/đường dẫn hoặc kiểu so sánh (Enum `->value`); mọi trường hợp khác: DỪNG.
   - `grep` ở "Bảng ảnh hưởng" thấy chỗ đọc/so sánh `status` của bốn Model **ngoài** danh sách của plan.
   - Một giá trị `status` trong DB (migration, factory, seeder, test) không nằm trong Enum tương ứng (`ValueError`).
   - Rule kiến trúc báo vi phạm ở file mà plan không nhắc tới, hoặc cần thêm mục `KNOWN_*` mới.
   - Pint báo lỗi ở file bạn không sửa (chỉ chạy `--test` trên file branch này đổi).
   - Container `piclass-local-api-1` không chạy.
3. Không đụng file ngoài "Danh sách file được phép đụng". Đặc biệt **không sửa**: controller của module khác, `AuditLogger`, `NotificationDispatcher`, migration, factory, seeder. Ở `UserManagement` chỉ được sửa **hai** Action `AssignTeacherToClass`, `EndTeacherClassAssignment` (D6) và test của chúng nếu đổi namespace.
4. Trong test, giá trị kỳ vọng luôn viết **chuỗi literal** (`'archived'`, `'Request failed.'`...), không dùng case Enum hay hằng `DenialMessage`. Ngoại lệ: test của chính Enum và test cast.
5. Mỗi task kết thúc bằng commit local ở repo BE (message **tiếng Anh**, kết thúc bằng dòng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`). **Không push, không mở PR, không merge, không xóa branch** — chỉ làm khi user yêu cầu. Khi được yêu cầu mở PR: **tiêu đề và mô tả PR viết tiếng Việt** (user chốt 2026-10-21, ghi đè dòng "PR bằng tiếng Anh" trong CLAUDE.md).
6. Không dùng git worktree. Thử đột biến chỉ trên **bản sao** `/tmp/mut` bên trong container, **không chạy song song với full suite** (cùng một DB test).
7. Comment trong code và mô tả test viết **tiếng Việt**; tên biến/hàm/class/test method và `error.code` tiếng Anh. Comment tiếng Anh cũ nằm trong file đã sửa thì dịch luôn.
8. **Thăm dò 500:** endpoint nhận tham số tự do hoặc phụ thuộc hồ sơ có thể đang trả 500. Task 1 thăm dò từng case: nếu 500, **không ghim 500, không sửa**, bỏ case khỏi test và ghi vào bảng nợ ở playbook (Task 8). Các case cần thăm dò: `POST /courses` bởi tài khoản vai trò `teacher` **không có hồ sơ Teacher**; `POST /courses` với `owner_teacher_id` không phải uuid; mọi `status` truyền mảng/object; `name[]=x`.
9. **Mã lỗi:** `abort(403|404)` cũ được handler render thành `FORBIDDEN`/`NOT_FOUND`; giữ nguyên. Policy `Response::deny()` phải truyền message tường minh (`'Request failed.'`, vì `abort(403)` cũ không message trả chuỗi đó); `denyAsNotFound` luôn truyền `DenialMessage::NOT_FOUND`.
10. **Thứ tự kiểm tra:** cả 14 endpoint hôm nay kiểm quyền trước, validate sau (`abort_unless` đứng trước `$request->validate`). Giữ `authorize → validate → state`.
11. **Chép đúng biểu thức quyền, kể cả chỗ lạ:** `$course->owner_teacher_id === $user->teacherProfile?->id` (có `?->`, nên `null === null` là đúng khi Course dùng chung và Teacher không có hồ sơ). Không "sửa cho hợp lý" trong PR này; ghi vào `ARCH-28`.

## Global Constraints

- Giữ nguyên hành vi: HTTP status, `error.code`, `error.message`, tập key JSON (từng endpoint có tập key **khác nhau**, xem "Bảng endpoint"), giá trị chuỗi trong JSON/DB — ngoại lệ D5.
- Baseline: **1731 test pass** (số cuối của PR `fix/lesson-vst-and-session-audit`; chạy full suite ở bước Chuẩn bị để xác nhận, nếu khác thì dùng số đo được). Mọi task kết thúc với 0 fail và số test = baseline + số test mới.
- Mọi case 403/404 trong test mới assert **cả** `error.code` lẫn `error.message`.
- Một user nhiều vai trò được tính quyền theo **vai trò đang hoạt động** (`BR-USER-002`, `ARCH-11`); vai trò rỗng (job/console) **không** là admin.
- Giờ ra qua `AppTime::format` (đã dùng ở `ListCourseEnrollmentsController`), không `toIso8601String`/`setTimezone` (R10).

### Chạy test

```bash
docker ps --format '{{.Names}}'                                         # phải thấy piclass-local-api-1
docker exec piclass-local-api-1 php artisan test tests/Feature/Academic
docker exec piclass-local-api-1 php artisan test --filter=ClassName
docker exec piclass-local-api-1 vendor/bin/pint --test <files>
# Full suite: chạy nền, ghi log ra scratchpad, đọc dòng "Tests:" cuối log
docker exec piclass-local-api-1 php artisan test > "<scratchpad>/full.log" 2>&1
```

Chỉ truyền đường dẫn test **tương đối** (`tests/...`).

### Git

BE: `git fetch origin`; working tree sạch; `git checkout -b refactor/academic origin/main && git branch --unset-upstream`. Docs: branch `docs/be-academic` đã tạo từ `origin/main` (plan này nằm ở đó, commit ngay sau khi viết, trước khi thực thi).

## Quyết định đã chốt

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | **Một PR**, 14 controller, 3 nhóm tuần tự: **A** Subject (3), **B** Class (4, kèm D6), **C** Course + CourseEnrollment (7). Cấu trúc: `Http/{Controllers,Requests,Resources}` (14 Controller, 14 Request); `Actions/{CreateSubject,UpdateSubject,CreateClass,UpdateClass,ActivateClass,RevertClassToPreparing,CreateCourse,UpdateCourse,EnrollInCourse,EndCourseEnrollment}`; `Queries/{ListSubjectsQuery,ListClassesQuery,ListCoursesQuery,ListCourseEnrollmentsQuery}`; `Policies/{ClassPolicy,CoursePolicy}`; `Enums/{SubjectStatus,ClassStatus,CourseStatus,CourseEnrollmentStatus}`; `Providers/AcademicServiceProvider` (đăng ký ở `bootstrap/providers.php`). Không có `Exceptions/`, `Events/`, `Domain/`, `Data/` | 520 dòng controller, phần lớn là CRUD; chỉ có Action khi có ghi (Create/Update/Enroll/End) hoặc nghiệp vụ trạng thái (D6); đọc không phạm vi dùng Eloquent trực tiếp | Chia PR; thêm Action cho endpoint chỉ đọc |
| D2 | **Enum** (`Modules/Academic/Enums`, đúng CHECK/`enum` trong migration): `SubjectStatus` = `active, inactive` (cast `Subject.status`); `ClassStatus` = `preparing, active, archived` (cast `SchoolClass.status`); `CourseStatus` = `active, archived` (cast `Course.status`); `CourseEnrollmentStatus` = `active, ended` (cast `CourseEnrollment.status`). Chỗ so sánh chuỗi cũ phải đổi tại chỗ ở Task 2: `AssignTeacherToClass` (`=== 'preparing'`), `EndTeacherClassAssignment` (`=== 'active'`) | Quy ước Enum; `EnumCastsTest` đối chiếu với DB | Chỉ cast một vài cột |
| D3 | **Policy đọc vai trò qua `ActiveRole`**, inject constructor. `ClassPolicy@view(User, SchoolClass)`: `isAdmin()` → allow; ngược lại `ClassScope::classIdsFor($user, $activeRole->code())->contains($class->id)` → allow; ngược lại `denyAsNotFound(DenialMessage::NOT_FOUND)`. `CoursePolicy@view(User, Course)`: `isAdmin()` → allow; `teacher` → `owner_teacher_id === teacherProfile?->id \|\| owner_teacher_id === null`; `student` → có `CourseEnrollment` `active` của `studentProfile?->id`; `parent` → có enrollment `active` của một trong `parentProfile?->activeChildrenIds()`; còn lại (kể cả vai trò rỗng) → 404 (`denyAsNotFound`). `CoursePolicy@manage(User, Course)` (dùng cho update, enroll, end): `isAdmin()` → allow; `code() === 'teacher'` → allow nếu `owner_teacher_id === teacherProfile?->id`, ngược lại `Response::deny('Request failed.')`; vai trò khác/rỗng → `deny('Request failed.')`. `CoursePolicy@viewEnrollments`: như `manage` nhưng `student`/`parent` → allow (phạm vi hàng do Query lo). Subject và Create* chỉ-admin/`role:`-only: `authorize(): bool { return true; }` kèm docblock nêu middleware ở route | Giữ đúng biểu thức quyền cũ (quy tắc 11). Khác cũ duy nhất: vai trò rỗng không còn "đi qua" `manage`/`viewEnrollments` (cũ: `$activeRole === 'teacher'` sai nên bỏ qua kiểm tra, tức coi như admin); route đã chặn nên HTTP không đổi (giống D5 của 9e) | Một Policy chung; kiểm tra trong Action |
| D4 | **Query** (đọc có phạm vi): `ListClassesQuery` (Admin: tất cả; còn lại `whereIn('id', ClassScope::classIdsFor(...))`; `orderBy('name')`); `ListSubjectsQuery` (Admin: tất cả; còn lại Subject của các lớp trong phạm vi, `distinct`, `orderBy('name')`, **kể cả Subject `inactive`**); `ListCoursesQuery` (Admin: tất cả; `teacher`: `owner_teacher_id = hồ sơ` **hoặc** `null`; `student`/`parent`: Course có enrollment `active` của chính Student/các con; vai trò khác/rỗng: rỗng; `orderBy('name')`); `ListCourseEnrollmentsQuery(Course)` (Admin/Teacher: tất cả enrollment của Course; `student`: chỉ của chính mình; `parent`: chỉ của các con; **không** `orderBy`, như cũ; vai trò rỗng: rỗng). Chỉ đọc `status` Enum bằng `->value`/case, không literal | Phạm vi dữ liệu nằm ở Query (rule 6) | Giữ `match` trong controller |
| D5 | **Ngoại lệ hành vi — message 404 của `GET /classes/{class}` và `GET /courses/{course}` (ARCH-20).** Cũ: đối tượng ngoài phạm vi trả `'Không tìm thấy lớp.'` / `'Không tìm thấy Course.'`, trong khi ID không tồn tại trả `'Request failed.'`: hai message khác nhau cho phép phân biệt "không tồn tại" với "của người khác". Mới: `denyAsNotFound` nên handler luôn trả `'Request failed.'`; `error.code` `NOT_FOUND` và status 404 giữ nguyên. Test đặc tả ghim message cũ; ở task chuyển endpoint tương ứng sửa đúng **hai** kỳ vọng này và ghi lý do trong commit | Áp dụng quyết định đã chốt `ARCH-20`, không phải business rule mới | Giữ message cũ bằng `DomainException` |
| D6 | **Đổi trạng thái lớp nằm ở Academic.** `ActivateClass::handle(SchoolClass $class): void` và `RevertClassToPreparing::handle(SchoolClass $class): void`; mỗi Action chạy `DB::transaction`, đọc lại dòng lớp bằng `lock('for no key update')` (cùng kiểu khóa của hai Action UserManagement, nên khi gọi trong transaction của chúng là khóa lại cùng một dòng, không deadlock), rồi: `ActivateClass` chuyển `preparing` → `active` (lớp đang `active`/`archived` thì không làm gì, **archived không bao giờ được kích hoạt lại**); `RevertClassToPreparing` chuyển `active` → `preparing` (lớp `preparing`/`archived` thì không làm gì). Không audit, không thông báo (spec §4.1 không yêu cầu; `DEC-AUD-001` không liệt kê trạng thái lớp). Hai Action UserManagement: `AssignTeacherToClass` gọi `ActivateClass` thay cho khối `if ($locked->status === 'preparing')`; `EndTeacherClassAssignment` gọi `RevertClassToPreparing` khi không còn Teacher `active` (quyết định "còn Teacher hay không" vẫn ở UserManagement, thứ tự ghi audit vs đổi trạng thái giữ như cũ). Xóa hai mục `KNOWN_LITERALS` (D8) và hai dòng "Nợ (ARCH-23, Task 9f)" trong docblock | Hết ghi Model của Academic từ module khác; không có vòng gọi Action (Academic không gọi UserManagement) | Giữ nguyên ghi trực tiếp; một Action có tham số boolean |
| D7 | **`EnrollInCourse` giữ nguyên cơ chế chống trùng đua** (check-then-insert + bắt vi phạm unique index `course_enrollments_course_student_active_unique`). Postgres hủy cả transaction đang mở khi một câu lệnh lỗi, nên **mỗi lần `create` bọc riêng `DB::transaction`** (khi chạy trong test hoặc transaction ngoài thì Laravel dùng savepoint, vi phạm unique chỉ hủy savepoint đó) và `QueryException` có tên index trên thì bỏ qua, lỗi khác ném lại. **Không** bọc cả vòng lặp trong một transaction (một vi phạm sẽ hủy các dòng đã tạo, đổi `created_count`). Roster của `class_id` đọc `ClassMember` (Model của UserManagement, chỉ đọc) lọc `status = MembershipStatus::Active->value` (cũ chỉ lọc `status`, không lọc `valid_to`, giữ nguyên). Trả `int $createdCount`; `201` kể cả khi `0`. Không audit (ngoài danh sách bắt buộc đã chốt, xem D10) | Giữ `created_count`; test đua được | Một transaction cho cả vòng lặp; khóa dòng Course |
| D8 | **R8 và rule kiến trúc:** thêm `'Academic'` vào `MODULES` và `SHARED_ENUMS['Academic'] = ['UserManagement/MembershipStatus']` (Academic import `MembershipStatus` cho `ClassMember.status`; tập giá trị `active/ended` của Academic trùng chữ với Enum đó nên khai báo để R8 không báo nhầm và canh được). Xóa hai mục `KNOWN_LITERALS` của `AssignTeacherToClass`/`EndTeacherClassAssignment`. Dự kiến **không** thêm mục `KNOWN_*` mới | Tránh báo nhầm khi tập giá trị trùng chữ | Gộp Enum toàn cục |
| D9 | **`UpdateClass`/`UpdateCourse`/`UpdateSubject`:** `status` chỉ nhận `archived` (Class, Course) hoặc `active,inactive` (Subject) qua `Rule::in([...Enum->value])` hoặc `Rule::enum(...)->only([...])` với **đúng message `The selected status is invalid.`** (ghim bằng test); `archived_at = now()` chỉ khi `status` **thật sự đổi** sang `archived` (Class, Course) / `inactive` (Subject), tức archive lần hai **không** đổi `archived_at`, và đặt Subject lại `active` **không** xóa `archived_at` (đều giữ nguyên cũ, ghim bằng test, ghi `ARCH-28`). Body rỗng trả 200 không đổi gì | Giữ nguyên hành vi | "Sửa" `archived_at` |
| D10 | **Ngoài phạm vi refactor — đã chốt với user 2026-10-21, ghi `ARCH-28`, làm ở PR đổi hành vi riêng sau 9f:** (a) Course dùng chung chỉ Admin quản lý, Teacher chỉ xem (`DEC-LRN-012`) — hành vi hiện tại đã đúng nên refactor giữ nguyên, ghim bằng test; (b) chặn ghi danh vào Course `archived` và tạo lớp cho Subject `inactive` (`DEC-LRN-013`); (c) audit `course_enrollment.enroll|end` (`DEC-AUD-007`); (d) chỉ Teacher `active` được phân công (`DEC-USER-009`, đóng `ARCH-23(p)`). **Còn mở, giữ nguyên trong refactor:** (e) Teacher thiếu hồ sơ khớp `null === null` (quy tắc 11); (f) phạm vi/roster chỉ lọc `status`, không `valid_to`; (g) `ARCH-14`, điểm gắn hook là `EnrollInCourse`; (h) mọi 500 phát hiện ở thăm dò; (i) hành vi `archived_at` ở D9 | Refactor không đổi hành vi; mỗi điểm đổi hành vi tách PR riêng, có test RED trước | Sửa luôn trong refactor |

### Danh sách Enum

| Enum | Giá trị (theo migration) | Cột được cast |
|---|---|---|
| `SubjectStatus` | `active`, `inactive` | `Subject.status` |
| `ClassStatus` | `preparing`, `active`, `archived` | `SchoolClass.status` |
| `CourseStatus` | `active`, `archived` | `Course.status` |
| `CourseEnrollmentStatus` | `active`, `ended` | `CourseEnrollment.status` |

## Bảng endpoint (14)

Tất cả Resource đặt `public static $wrap = null`; Resource tự dựng khóa ngoài (`subject`/`class`/`course`/`data`). Endpoint tạo ép `201` tường minh ở `withResponse`; endpoint trả model đã tồn tại mặc định 200. Mỗi endpoint một tập key **riêng** (ghim bằng so sánh mảng key):

| # | Nhóm | Endpoint (route middleware) | Authorize | Action / Query | Resource / status / key |
|---|---|---|---|---|---|
| 1 | A | `GET /subjects` (4 vai trò) | không | `ListSubjectsQuery` | `{data:[{id,name,description,status}]}` 200 |
| 2 | A | `POST /subjects` (admin) | `true` (docblock) | `CreateSubject` (`status = active`) | `{subject:{id,name,description,status}}` 201 |
| 3 | A | `PATCH /subjects/{subject}` (admin) | `true` | `UpdateSubject` (D9) | `{subject:{id,name,description,status}}` 200 |
| 4 | B | `GET /classes` (4 vai trò) | không | `ListClassesQuery` | `{data:[{id,subject_id,name,label,description,status}]}` 200 |
| 5 | B | `GET /classes/{class}` | `ClassPolicy@view` → 404 (D5) | Eloquent (model đã bind) | `{class:{id,subject_id,name,label,description,status}}` 200 |
| 6 | B | `POST /classes` (admin) | `true` | `CreateClass` (`status = preparing`) | `{class:{id,subject_id,name,label,status}}` 201 |
| 7 | B | `PATCH /classes/{class}` (admin) | `true` | `UpdateClass` (D9) | `{class:{id,name,label,description,status}}` 200 |
| 8 | C | `GET /courses` (4 vai trò) | không | `ListCoursesQuery` | `{data:[{id,name,description,owner_teacher_id,status}]}` 200 |
| 9 | C | `GET /courses/{course}` | `CoursePolicy@view` → 404 (D5) | Eloquent | `{course:{id,name,description,subject_id,owner_teacher_id,status}}` 200 |
| 10 | C | `POST /courses` (admin, teacher) | `true` (docblock) | `CreateCourse` | `{course:{id,name,owner_teacher_id,status}}` 201 |
| 11 | C | `PATCH /courses/{course}` (admin, teacher) | `CoursePolicy@manage` → 403 `Request failed.` | `UpdateCourse` (D9) | `{course:{id,name,description,status}}` 200 |
| 12 | C | `GET /courses/{course}/enrollments` (4 vai trò) | `CoursePolicy@viewEnrollments` → 403 | `ListCourseEnrollmentsQuery` | `{data:[{student_id,status,valid_from,valid_to}]}` 200, giờ qua `AppTime::format` |
| 13 | C | `POST /courses/{course}/enrollments` (admin, teacher) | `CoursePolicy@manage` → 403 | `EnrollInCourse` (D7) | `{created_count}` 201 |
| 14 | C | `DELETE /courses/{course}/enrollments/{studentId}` (admin, teacher) | `CoursePolicy@manage` → 403 | `EndCourseEnrollment` | 204 |

Chi tiết cần giữ, từng endpoint:

- **#10 `POST /courses`:** rule `owner_teacher_id` (`nullable|uuid|exists:teachers,id`) chỉ có khi vai trò đang hoạt động là admin (Teacher gửi trường này: bị bỏ qua, **không** validate). Chủ sở hữu: vai trò `teacher` → luôn `teacherProfile->id` (không tin body, `BR-COURSE-004`); admin → `owner_teacher_id` từ body hoặc `null` (dùng chung). `rules(ActiveRole $activeRole)` dùng method injection của FormRequest; một method `ownerTeacherId(): ?string` trên Request tính chủ sở hữu, Controller truyền vào `CreateCourse::handle(array $data, ?string $ownerTeacherId): Course`. Giữ `teacherProfile->id` (không `?->`) như cũ (quy tắc 8: nếu 500 thì không ghim).
- **#12:** Teacher không sở hữu → 403 **trước** mọi thứ (kể cả Course dùng chung); Student/Parent không bị chặn theo Course, chỉ lọc hàng nên Course họ không ghi danh trả `200 {data: []}`.
- **#13 body:** `student_id` (`required_without:class_id|prohibits:class_id|uuid|exists:students,id`) hoặc `class_id` (`required_without:student_id|prohibits:student_id|uuid|exists:classes,id`); validate **sau** kiểm tra quyền. Học viên đã có enrollment `active` thì bỏ qua (không đếm). Teacher ghi danh bất kỳ Student/Class nào (`BR-COURSE-004`, không giới hạn phạm vi).
- **#14:** `end_reason` bắt buộc (422), không có enrollment `active` của cặp (course, student) → 404 (`firstOrFail`); ghi `status = ended`, `valid_to = now()`, `ended_by_user_id`, `end_reason`; khóa dòng `lockForUpdate` trong transaction (Ruling: hai yêu cầu kết thúc đồng thời, yêu cầu sau nhận 404 thay vì ghi đè `valid_to`).

## Bảng ảnh hưởng (kết quả grep `app/ database/ routes/ config/ tests/`)

Quét: `grep -rnE "SchoolClass|Subject\b|Course\b|CourseEnrollment" app database routes config tests` rồi lọc chỗ đọc/so sánh `status`.

| File | Đọc/ghi gì | Xử lý |
|---|---|---|
| `app/Models/{SchoolClass,Subject,Course,CourseEnrollment}.php` | `$casts` | **Sửa** (thêm cast) — bản chất task |
| `app/Modules/UserManagement/Actions/{AssignTeacherToClass,EndTeacherClassAssignment}.php` | `$locked->status === 'preparing'/'active'`, `update(['status' => ...])` | **Sửa** (Task 2 đổi sang Enum, Task 4b dùng Action của D6) |
| `app/Support/ClassScope.php` | `where('status','active')` trên `course_enrollments` (query literal) | **Không sửa** (so sánh trong SQL, không đọc thuộc tính cast; R8 không quét `app/Support`) |
| `app/Modules/Assessment/Actions/ReopenAssessment.php` | `CourseEnrollment::where('status','active')` (query literal) | **Không sửa** |
| `routes/api.php` (14 dòng), `bootstrap/providers.php` | tham chiếu controller cũ, provider | **Sửa** |
| `database/factories/{SchoolClass,Subject,Course,CourseEnrollment}Factory.php` | `'status' => 'preparing'/'active'` | **Không sửa** (chuỗi hợp lệ gán vào thuộc tính cast Enum) |
| Test chạm cột: `tests/Feature/{Academic,UserManagement,Assessment,Lesson,Attendance,Result,ResultProgress,Support,SubmissionGrading,QuestionBank}/**`, `tests/Feature/Models/EnumCastsTest.php` | `assertDatabaseHas(... 'status' => 'x')`, tạo qua factory | **Không sửa** nội dung nếu không có `assertSame/assertNotSame` trên `->status` hay `pluck('status')` của bốn Model (kiểm bằng `grep` ở Chuẩn bị; nếu có thì sửa theo kiểu so sánh `->value` và liệt kê trong báo cáo) |

Kết luận: ngoài module chỉ cần sửa bốn Model, hai Action UserManagement, routes, provider. `grep` thấy chỗ đọc `status` ngoài bảng: DỪNG.

## Review Focus (cho Opus)

1. **Quyền sở hữu Course (đối chiếu `BR-COURSE-004`, `BR-COURSE-005`/`DEC-LRN-012` đã chốt 2026-10-21):** Teacher thấy Course dùng chung (`GET`, danh sách) nhưng không sửa/ghi danh/xem ghi danh được (403) — đúng quyết định, soi xem code còn lệch ở đâu; `null === null` của Teacher thiếu hồ sơ giữ nguyên có chủ đích (`ARCH-28(e)`); vai trò rỗng không còn bỏ qua kiểm tra (D3). Test: Task 1 (hành vi cũ) + Task 6 (`AcademicPolicyTest`).
2. **Phạm vi đọc:** Student/Parent chỉ thấy Class/Course/enrollment của mình/child; Student gọi `GET /courses/{id}/enrollments` của Course không ghi danh nhận `200 []` (không phải 404/403); enrollment `ended` không tính. Test: Task 1 + Task 6.
3. **Race ghi danh:** hai yêu cầu đồng thời vào cùng cặp (course, student) không làm hỏng `created_count` và không 500 (D7); `EndCourseEnrollment` hai lần. Test: `AcademicActionsTest`.
4. **`archived_at`:** archive lần hai không đổi `archived_at`; Subject đặt lại `active` không xóa `archived_at` (D9). Test: Task 1 với `Carbon::setTestNow` hai mốc khác nhau.
5. **Đổi trạng thái lớp (D6):** `archived` không bị kích hoạt lại; `ActivateClass`/`RevertClassToPreparing` idempotent; hai Action UserManagement vẫn một transaction với audit (rollback test cũ phải xanh không sửa). Test: `ClassStatusTransitionTest` cũ + `AcademicActionsTest`.
6. Bảng đột biến (Task 7): cái nào sống sót và test bổ sung.

---

## Danh sách file được phép đụng

| File | Trách nhiệm |
|---|---|
| `tests/Feature/Academic/Concerns/AcademicFixtures.php`, `tests/Feature/Academic/{AcademicSubjectBehaviorTest,AcademicClassBehaviorTest,AcademicCourseBehaviorTest}.php` (mới) | Đặc tả hành vi trên code cũ |
| `tests/Feature/Academic/{AcademicPolicyTest,AcademicActionsTest,AcademicOutsideHttpTest,AcademicBoundaryCasesTest}.php` (mới) | Phân quyền, Action, Query ngoài HTTP, ca biên |
| `tests/Feature/Academic/{SubjectTest,ClassTest,CourseTest,CourseEnrollmentTest,ClassStatusTransitionTest}.php` | Giữ nguyên (chỉ sửa nếu đổi namespace) |
| `tests/Unit/Modules/Academic/**` (mới) | Enum |
| `app/Modules/Academic/**` (mới) | Module |
| `app/Models/{SchoolClass,Subject,Course,CourseEnrollment}.php`, `tests/Feature/Models/EnumCastsTest.php` | `$casts` |
| `app/Modules/UserManagement/Actions/{AssignTeacherToClass,EndTeacherClassAssignment}.php` | Gọi Action của Academic (D6) |
| `tests/Architecture/ArchitectureTest.php` | `MODULES`, `SHARED_ENUMS`, xóa 2 `KNOWN_LITERALS` |
| `bootstrap/providers.php`, `routes/api.php` | Đăng ký, route |
| `app/Http/Controllers/Api/Academic/*` (xóa 14) | — |
| `piclass-api/CLAUDE.md`; docs: playbook, `error-codes.md`, `model-ownership.md`, `open-decisions.md`, `progress.md`, plan tổng | Ghi nhận |

---

## Chuẩn bị (trước Task 1)

- [ ] Tạo branch BE theo mục Git; commit plan này ở branch docs `docs/be-academic`. `docker ps` thấy `piclass-local-api-1`.
- [ ] Chạy full suite nền, ghi số `Tests:` làm baseline thực (so với 1731).
- [ ] Chạy `grep -rnE "assert(Not)?Same\(.*->status|pluck\('status'\)" tests` và lọc chỗ liên quan bốn Model; ghi kết quả vào báo cáo (bảng ảnh hưởng).
- [ ] Kiểm tra `docs/superpowers/plans/2026-10-18-be-architecture-refactor.md` bảng trạng thái (dòng "9e–9i Chưa làm" đang lỗi thời so với `progress.md`): sửa ở Task 8.

### Task 1: Test đặc tả hành vi trên code cũ

Viết trên code cũ, phải **xanh** trước khi đổi code. `AcademicFixtures` (trait): đăng nhập admin/teacher/student/parent kèm `active_role_code`; Teacher có Course riêng, Course dùng chung (`owner_teacher_id = null`), Course của Teacher khác; Student có enrollment `active` và một `ended`; Parent có liên kết `active` và một đã `ended`; lớp có `ClassMember`/`TeacherClassAssignment` `active` và `ended`; một lớp/Course/Subject **khác có dữ liệu thật** (không để rỗng).

- [ ] `AcademicSubjectBehaviorTest` (#1–3): ghim **toàn bộ tập key** cấp trên và key lồng (so sánh mảng key), status 200/201; `POST` `status = active`; validation 422 (`name` thiếu/mảng, `status` ngoài `active,inactive`, message `The selected status is invalid.`); `PATCH`: đặt `inactive` ghi `archived_at`, đặt lại `active` **giữ** `archived_at` (D9), `inactive` lần hai không đổi `archived_at` (`Carbon::setTestNow` hai mốc); body rỗng 200; `GET`: Admin thấy tất cả (kể cả `inactive`), Teacher/Student/Parent chỉ Subject của lớp trong phạm vi (kể cả Subject `inactive`), sắp theo `name`, không trùng; vai trò rỗng không phải admin (trả `[]`); Student/Parent/Teacher gọi `POST`/`PATCH`: 403 từ middleware (ghim message thực tế), chưa đăng nhập 401, id không phải uuid 404.
- [ ] `AcademicClassBehaviorTest` (#4–7): key-set từng response (khác nhau, xem bảng); `POST` `status = preparing`, `subject_id` không tồn tại 422; `PATCH` `status` chỉ nhận `archived` (`preparing`/`active` → 422 `The selected status is invalid.`), archive ghi `archived_at`, archive lần hai **không** đổi `archived_at`; `GET` danh sách theo phạm vi từng vai trò (Teacher: phân công `active`, **không** `ended`; Student: `ClassMember` `active`; Parent: lớp của con có liên kết `active`; lớp thứ hai có dữ liệu thật không lọt), sắp theo `name`; `GET /classes/{id}` ngoài phạm vi 404 với message **cũ** `Không tìm thấy lớp.`, id không tồn tại 404 `Request failed.`; **ghim trạng thái tự chuyển** (spec §4.1, đã có ở `ClassStatusTransitionTest`; thêm ca archived không bị kích hoạt lại bởi phân công thứ hai và ca kết thúc phân công của lớp `archived` giữ `archived`).
- [ ] `AcademicCourseBehaviorTest` (#8–14): key-set từng response; `POST /courses` Teacher: chủ sở hữu luôn là Teacher dù body gửi `owner_teacher_id` khác; Admin: `owner_teacher_id` từ body (422 nếu không tồn tại), hoặc `null`; `PATCH` Teacher sở hữu OK, Teacher khác **và Course dùng chung**: 403 `Request failed.` trước 422 khi body sai; Admin OK; `status` chỉ `archived` (D9); `GET` danh sách: Admin tất cả, Teacher (riêng + dùng chung, không của Teacher khác), Student/Parent (enrollment `active`, không `ended`), sắp theo `name`; `GET /courses/{id}`: ngoài phạm vi 404 với message **cũ** `Không tìm thấy Course.`; Teacher thấy Course dùng chung; Teacher khác không thấy Course riêng; `GET enrollments`: Admin/Teacher sở hữu thấy tất cả hàng (kể cả `ended`), Teacher không sở hữu (kể cả Course dùng chung) 403, Student chỉ hàng của mình, Parent chỉ hàng của các con, Student/Parent với Course không ghi danh nhận `200 {data: []}`, giờ có offset `+07:00`; `POST enrollments` bằng `student_id`, bằng `class_id` (chỉ `ClassMember` `active`), cả hai/không có trường nào → 422, học viên đã `active` bị bỏ qua (`created_count` 0, vẫn 201), Teacher ghi danh Student/Class ngoài phạm vi mình vẫn được; `DELETE`: ghi `status`, `valid_to`, `ended_by_user_id`, `end_reason`, 204; thiếu `end_reason` 422; không có enrollment `active` 404; Teacher không sở hữu 403 trước 422.
- [ ] Thăm dò (quy tắc 8, không ghim 500).
- [ ] Chạy trên code cũ, Pint `--test` file mới. **Commit:** `test: pin Academic endpoint behavior before the module refactor`.

### Task 2: Enum + cast

- [ ] Unit test 4 Enum (`tests/Unit/Modules/Academic/Enums/`: tập giá trị) và 4 cột vào `EnumCastsTest` (đối chiếu với `enum` của migration) → RED. Tạo 4 Enum; thêm cast vào 4 Model; sửa tại chỗ hai Action UserManagement (D2) bằng `ClassStatus::Preparing`/`ClassStatus::Active` để test cũ vẫn chạy. Full suite xanh. **Commit:** `feat: add Academic enums and cast the subject, class, course and enrollment status`.

### Task 3: Hạ tầng — Policy, Query, Action trạng thái lớp, Provider

- [ ] `ClassPolicy` (`view`), `CoursePolicy` (`view`, `manage`, `viewEnrollments`) theo D3; 4 Query theo D4; `ActivateClass`, `RevertClassToPreparing` theo D6 (có test Action ngay: `preparing → active`, `active`/`archived` giữ nguyên; `active → preparing`, `preparing`/`archived` giữ nguyên); `AcademicServiceProvider` (`Gate::policy` cho `SchoolClass`, `Course`) + `bootstrap/providers.php`. **Commit:** `feat: add Academic policies, queries and class status actions`.

### Task 4: Chuyển endpoint

- [ ] **4a Subject (#1–3):** `CreateSubject`, `UpdateSubject`, Request/Resource/Controller, routes. Test đặc tả Task 1 vẫn xanh. **Commit.**
- [ ] **4b Class (#4–7):** `CreateClass`, `UpdateClass`, Request/Resource/Controller, routes; **hai Action UserManagement gọi `ActivateClass`/`RevertClassToPreparing`** (D6); sửa kỳ vọng message 404 của `GET /classes/{class}` theo D5 (RED → GREEN), commit ghi lý do. `ClassStatusTransitionTest` và các test rollback/Behavior của UserManagement xanh **không sửa**. **Commit.**
- [ ] **4c Course (#8–11):** `CreateCourse`, `UpdateCourse`, Request/Resource/Controller, routes; sửa kỳ vọng message 404 của `GET /courses/{course}` theo D5. **Commit.**
- [ ] **4d CourseEnrollment (#12–14):** `EnrollInCourse` (D7), `EndCourseEnrollment`, Request/Resource/Controller, routes. **Commit.**
- [ ] **4e Dọn:** xóa 14 controller cũ và thư mục `app/Http/Controllers/Api/Academic`. `grep -rn "Controllers\\\\Api\\\\Academic" app routes tests` rỗng. **Commit.**

### Task 5: Rule kiến trúc

- [ ] `'Academic'` vào `MODULES`, `SHARED_ENUMS['Academic']` (D8), xóa hai mục `KNOWN_LITERALS` của UserManagement. Sửa vi phạm do code mình viết; không thêm `KNOWN_*` ngoài dự kiến. `ArchitectureTest` xanh (kể cả R4: Academic không ghi `ClassMember`; `ClassStatus` không còn ghi từ UserManagement qua `update(['status' => ...])`). **Commit.**

### Task 6: Test phân quyền, Action, ca biên

- [ ] `AcademicPolicyTest`: gọi `Gate::inspect` trực tiếp (không qua queue `sync`) cho từng ability × {Admin, Teacher sở hữu, Teacher khác (Course/lớp đó có dữ liệu thật), Teacher thiếu hồ sơ với Course dùng chung (ghim `null === null`), Teacher phân công `ended`, Student thành viên/ghi danh, Student `ended`, Parent của học viên, Parent liên kết `ended`, vai trò rỗng}; assert `code` + message (đúng checklist playbook).
- [ ] `AcademicActionsTest`: `CreateCourse` (chủ sở hữu theo tham số, không đọc session); `UpdateClass`/`UpdateCourse`/`UpdateSubject` `archived_at` chỉ khi đổi thật; `EnrollInCourse`: `created_count`, bỏ qua trùng, **đua**: một listener `CourseEnrollment::creating` chèn trước dòng `active` của cùng cặp bằng `DB::table` thì Action trả `0`, không ném lỗi, các dòng đã tạo trước đó còn nguyên; lỗi `QueryException` khác tên index được ném lại; `EndCourseEnrollment` khóa dòng (`for update` trong log truy vấn), lần hai ném `ModelNotFoundException`; `ActivateClass`/`RevertClassToPreparing` idempotent và tôn trọng `archived`.
- [ ] `AcademicOutsideHttpTest`: 4 Query chạy không session/request: Admin qua `ActiveRole::set('admin')`, vai trò rỗng trả rỗng cho mọi Query, Teacher/Student/Parent đúng phạm vi.
- [ ] `AcademicBoundaryCasesTest`: luật validate từng Request (`required`, `uuid`, `exists`, `prohibits`, `required_without`, `in`), thứ tự 403 trước 422 cho `PATCH`/`POST`/`DELETE` của Course và enrollment; `status` sai giữ message `The selected status is invalid.`.
- [ ] Full suite xanh. **Commit.**

### Task 7: Thử đột biến (bắt buộc) — bản sao ngoài working tree

Copy dự án sang `/tmp/mut` trong container, **không chạy cùng full suite**. Bộ tối thiểu (mỗi dòng một đột biến riêng, tất cả phải đỏ; sống sót → bổ sung test rồi chạy lại nhóm đó):

| # | Đột biến | Test kỳ vọng đỏ |
|---|---|---|
| 1 | Mỗi ability của hai Policy: luôn allow / bỏ nhánh admin / vai trò rỗng = admin / đổi message / `deny` ↔ `denyAsNotFound` | Policy + Behavior |
| 2 | `CoursePolicy@view`: bỏ nhánh Course dùng chung của Teacher; bỏ lọc `status = active` của enrollment (Student/Parent); Parent dùng sai danh sách con | Policy + Behavior |
| 3 | `CoursePolicy@manage`/`viewEnrollments`: đổi `===` thành `!==`; bỏ `?->`; cho Course dùng chung đi qua; vai trò `student`/`parent` được allow | Policy + Behavior |
| 4 | `ClassPolicy@view`: bỏ `contains`; dùng vai trò khác `code()` | Policy + Behavior |
| 5 | 4 Query: bỏ lọc phạm vi / bỏ `orderBy('name')` / đổi `orWhereNull` / Student thấy hàng người khác / Parent thấy hàng không phải của con / bỏ `distinct` | Behavior + OutsideHttp |
| 6 | `CreateCourse`/`CreateCourseRequest`: Teacher tin `owner_teacher_id` của body; Admin bỏ `owner_teacher_id`; thêm rule `owner_teacher_id` cho Teacher | Behavior + Actions |
| 7 | `UpdateClass`/`UpdateCourse`/`UpdateSubject`: ghi `archived_at` mỗi lần; không ghi; xóa `archived_at` khi đặt `active`; mở rộng `in:` thêm `active`/`preparing` | Behavior + Boundary |
| 8 | `EnrollInCourse`: bỏ bắt `QueryException`; bọc cả vòng lặp trong một transaction; bỏ dedupe; roster lấy cả `ended`; `created_count` đếm cả dòng bỏ qua; bỏ kiểm tra tên index | Actions + Behavior |
| 9 | `EndCourseEnrollment`: bỏ `lockForUpdate`; bỏ lọc `active`; không ghi `valid_to`/`ended_by_user_id`/`end_reason` | Actions + Behavior |
| 10 | `ActivateClass`/`RevertClassToPreparing`: bỏ điều kiện trạng thái (kích hoạt lại `archived`); bỏ khóa dòng; đổi hướng chuyển | Actions + `ClassStatusTransitionTest` |
| 11 | UserManagement: bỏ gọi `ActivateClass`; luôn gọi `RevertClassToPreparing` dù còn Teacher; gọi sau commit | `ClassStatusTransitionTest` + rollback test |
| 12 | Resource: đổi/bỏ từng key (mỗi Resource ≥ 2 đột biến); bỏ `$wrap = null`; đổi 201 thành 200 | Behavior (key-set) |
| 13 | Request: bỏ từng luật (`required`, `uuid`, `exists`, `prohibits`, `required_without`, `in`) | Boundary |
| 14 | Enum: thêm/bớt case/đổi giá trị | Enum unit + EnumCastsTest |

- [ ] Ghi bảng kết quả vào báo cáo và `progress.md`.

### Task 8: Tài liệu và nghiệm thu

- [ ] Docs: `open-decisions.md` `ARCH-28` đã ghi từ lúc lên plan: bổ sung kết quả thăm dò 500 ở (h) và các ruling phát sinh; mở issue cho 4 việc đã chốt (a)–(d) nếu chưa mở; `model-ownership.md` cập nhật dòng `SchoolClass`/`Subject`/`Course`/`CourseEnrollment` ("Chuỗi literal" thành Enum); `error-codes.md` (không mã mới); playbook "Nợ đã biết": xóa dòng "Đổi trạng thái lớp" (D6) và cập nhật `ARCH-23(n)`; thêm nợ phát sinh (thăm dò 500, `RoleCode` Enum cho `'teacher'` ở Policy/Request, Task 9g); `progress.md` + bảng trạng thái plan tổng (sửa luôn dòng "9e–9i Chưa làm" lỗi thời); `piclass-api/CLAUDE.md` danh sách Enum đã chuyển (thêm `Academic`); `docs/architecture/backend-refactor-playbook.md` ghi rõ bẫy mới: `DB::transaction` mỗi lần `create` khi bắt unique violation (D7).
- [ ] Full suite = baseline + test mới, 0 fail; `pint --test` trên file branch đổi; `ArchitectureTest` R1–R11 xanh với `Academic`.
- [ ] Báo cáo cho Opus review (D3, D5 là ngoại lệ có chủ đích; Review Focus; bảng đột biến; danh sách chỗ không chắc), sửa Critical/Important (mỗi cái test RED → GREEN, suite xanh), Minor vào ledger. **DỪNG, hỏi user trước khi push/PR/merge** (PR tiêu đề và mô tả bằng tiếng Việt). Sau khi merge, commit tài liệu nhỏ: điền số PR và đổi "việc kế tiếp" sang 9g `Identity`/`Auth`.

## Tiêu chí xong

- 14 controller cũ đã xóa, thay bằng module; `UserManagement` không còn ghi `SchoolClass.status` trực tiếp, hai mục `KNOWN_LITERALS` đã xóa; `ArchitectureTest` xanh với `Academic`; test đặc tả xanh trước/sau (ngoại lệ D5); đột biến không còn sống sót (hoặc giải thích tương đương); tài liệu cập nhật; hai repo đã merge theo chỉ đạo của user.

## Ghi chú thực thi (điền sau khi xong)

**Khác plan (rulings):** _(điền)_

**Kết quả:** _(điền: số test, bảng đột biến, kết quả review Opus)_
