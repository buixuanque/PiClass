# Task 9b — Refactor module `UserManagement` sang quy ước module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** 30 controller của `UserManagement` chuyển từ `app/Http/Controllers/Api/UserManagement/` sang `app/Modules/UserManagement/` theo `piclass-api/CLAUDE.md` (FormRequest + Policy + Action/Query + Resource), dùng `ActiveRole`, Enum cho các cột trạng thái, 10 controller có audit bắt buộc đưa audit vào cùng transaction với thay đổi dữ liệu (`DEC-AUD-005`); không đổi HTTP status, `error.code`, `error.message`, key JSON, giá trị chuỗi lưu DB — trừ các ngoại lệ ghi ở mục "Quyết định đã chốt". **Làm thành 2 PR, mỗi PR một lượt review Opus riêng** (playbook, mục "Phân vai model").

**Architecture:** Module `app/Modules/UserManagement/{Http,Actions,Queries,Policies,Enums,Exceptions,Providers}`, không Event/Listener (module không gửi thông báo). Policy theo đối tượng thay các kiểm tra inline đọc `session('active_role_code')`. `StudentPolicy` chuyển từ `ResultProgress` về module này (đúng chủ sở hữu Model). Mọi endpoint ghi có Action; 10 Action có audit bọc `DB::transaction` gồm dữ liệu + audit. Test đặc tả hành vi viết trước trên code cũ, thử đột biến bắt buộc ở cuối mỗi PR.

**Tech Stack:** Laravel 11 (`^11.31`), PHP 8.2, PostgreSQL, PHPUnit, Pint. Container `piclass-local-api-1` (Docker Compose ở `piclass-infra/local`).

**Spec / nguồn:** `piclass-api/CLAUDE.md`; `docs/architecture/backend-refactor-playbook.md` (phân vai model, bẫy giữ nguyên hành vi, checklist test phân quyền, nợ đã biết); `docs/superpowers/specs/2026-10-12-identity-auth-user-management-api-design.md` (Section 4-8); `docs/superpowers/specs/2026-10-15-notification-file-audit-api-design.md` §4 (10 action audit của Module 1, tất cả `reason` không bắt buộc ở tầng Audit, riêng `class_member.retroactive_update` đã có `reason` bắt buộc ở request theo `BR-USER-017`); `docs/requirements/user-rules.md` (`BR-USER-001..017`), `decision-log.md` (`DEC-USR-011/012`, `DEC-USER-008`, `DEC-AUD-001/003/004/005`); `docs/access-control/*` (`resource-permissions.md` dòng User/Student/ClassMember, `data-scope.md`); `docs/architecture/model-ownership.md`, `error-codes.md`; plan mẫu `2026-10-21-be-attendance.md` (cấu trúc file, mẫu Policy/Request/Resource/Action).

---

## Quy tắc cho người thực thi (đọc trước khi làm bất cứ gì)

1. **Gặp điểm mơ hồ, hoặc cần một quyết định không có trong plan: DỪNG và báo** (nêu file, dòng, các phương án). Không tự chọn, không "làm tạm rồi báo sau".
2. Các tình huống bắt buộc DỪNG (không giới hạn ở đây):
   - Một test đặc tả (Task 1, Task 8) **fail trên code cũ**. Không sửa kỳ vọng cho khớp code, không sửa code. Ngoại lệ duy nhất: **thăm dò 500** ở quy tắc 8.
   - Một test cũ (có trước PR này) fail sau khi đổi code vì **hành vi khác**. Chỉ được sửa test cũ khi đổi namespace/đường dẫn hoặc kiểu so sánh (Enum `->value`); mọi trường hợp khác: DỪNG.
   - `grep` ở "Bảng ảnh hưởng" tìm thấy chỗ đọc/so sánh cột bị cast **ngoài** danh sách của plan.
   - Một giá trị trong DB (migration, factory, seeder, test) không nằm trong Enum tương ứng (`ValueError`).
   - Rule kiến trúc báo vi phạm ở file mà plan không nhắc tới, hoặc `KNOWN_LITERALS` cần nhiều mục hơn danh sách D14.
   - Pint báo lỗi ở file bạn không sửa (nhiều file lỗi sẵn trên `main`, ví dụ `database/factories/*`, `app/Http/Controllers/**` chưa chuyển, `tests/Unit/Modules/{Audit,Notification}` — bỏ qua, chỉ chạy `--test` trên file branch này đổi).
   - Container `piclass-local-api-1` không chạy.
3. Không đụng file ngoài "Danh sách file được phép đụng". Đặc biệt **không sửa**: controller/Service của module khác (Academic, Lesson, Assessment, QuestionBank, Auth ngoài 2 dòng ở D6), `ClassScope.php`, `AuditLogger`, `NotificationDispatcher`, migration, factory, seeder.
4. Trong test, giá trị kỳ vọng luôn viết **chuỗi literal** (`'active'`, `'Request failed.'`...), không dùng case Enum hay hằng `DenialMessage` — để ai đổi giá trị thì test bắt được. Ngoại lệ: test của chính Enum và test cast.
5. Mỗi task kết thúc bằng commit local ở repo BE (message tiếng Anh, kết thúc bằng dòng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`). **Tuyệt đối không push, không mở PR, không merge, không xóa branch.**
6. Không dùng git worktree: container mount đúng thư mục `piclass-api`. Thử đột biến chỉ làm trên **bản sao** `/tmp/mutN` bên trong container; không bao giờ sửa working tree gốc để thử. **Không chạy đột biến song song với full suite** (cùng một DB test).
7. **Comment trong code (docblock, comment dòng, mô tả trong test) viết tiếng Việt**; tên biến/hàm/class/test method và `error.code` tiếng Anh. Comment tiếng Anh cũ nằm trong file đã sửa thì dịch luôn.
8. **Thăm dò 500:** các endpoint nhận ID tự do (`/users/{user}/roles/{roleId}`, `/me/children/{studentId}`, `/classes/{class}/members/{studentId}`, `/classes/{class}/teachers/{teacherId}`) và bộ lọc `GET /users` không validate (`per_page=abc`, `role[]=x`) có thể đang trả 500 (Postgres báo uuid/kiểu sai) — cùng loại lỗi có sẵn đã gặp ở Attendance. Task 1 có bước thăm dò từng case một: nếu trả 500, **không ghim 500 thành đặc tả, không sửa**; ghi vào bảng nợ ở playbook (Task 8/15) và bỏ case đó khỏi test.

## Global Constraints

- Giữ nguyên hành vi: HTTP status, `error.code`, `error.message`, tập key JSON (kể cả key ngoài envelope `active_class_ids`), giá trị chuỗi trong JSON/DB/audit của mọi endpoint — ngoại lệ ghi ở D5 (default-deny cho vai trò ngoài phạm vi route) và D13 (nếu có).
- Baseline: **992 test pass** (full suite ~30 giây trên máy này). Mọi task kết thúc với 0 fail và số test = 992 + số test mới đã thêm.
- Pint chỉ chạy `--test` trên các file `.php` mà branch này đổi.
- Mọi case 403/404 trong test mới assert **cả** `error.code` lẫn `error.message`.
- Thứ tự `authorize → validate → state` của code cũ **đã là validation-first** (mọi endpoint `$request->validate()` trước khi kiểm tra trạng thái); chỉ `PATCH /me/profile` có 403 trước validate (kiểm tra `full_name`/`status`) — giữ đúng thứ tự này.
- Một user nhiều vai trò được tính quyền theo **vai trò đang hoạt động** (`BR-USER-002`, `ARCH-11`).

### Chạy test (máy macOS, container Docker Desktop)

```bash
cd /Users/que-bui/Workspaces/PiClass/piclass-infra/local
docker ps --format '{{.Names}}'                                     # phải thấy piclass-local-api-1
docker compose exec -T api php artisan test tests/Feature/UserManagement
docker compose exec -T api php artisan test --filter=ClassName
docker compose exec -T api vendor/bin/pint --test <files>
docker compose exec -T api php artisan test > "<scratchpad>/full.log" 2>&1   # full suite, đọc dòng "Tests:" cuối log
```

Chỉ truyền đường dẫn test **tương đối** (`tests/...`). Trên máy Windows/WSL dùng tiền tố `wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 ...'` như plan Attendance.

### Git

- **PR 1:** BE `git fetch origin && git checkout -b refactor/user-management-1 origin/main` (working tree sạch). Docs `git fetch origin && git checkout -b docs/be-user-management origin/main`; plan này đã nằm trên branch docs `docs/be-user-management-plan` (chưa push) — cherry-pick/merge vào `docs/be-user-management` hoặc dùng luôn branch đó.
- **PR 2 chỉ bắt đầu sau khi PR 1 đã được user merge vào `main`** (PR 2 dựa vào Enum, Provider, rule kiến trúc của PR 1): `git fetch origin && git checkout -b refactor/user-management-2 origin/main`.
- Sau khi tạo branch, nếu git tự đặt upstream là `origin/main` thì `git branch --unset-upstream` (tránh vô tình đẩy lên `main`).

## Quyết định đã chốt

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | **Chia 2 PR.** PR 1 "User/Role/Profile" (18 controller): `ListUsers`, `GetUser`, `UpdateUserStatus`, `AssignUserRole`, `EndUserRole`, `InvitePersonProfile`, `UpdatePersonProfile`, `CreateStudent/Teacher/Parent`, `GetStudent/Teacher/Parent`, `UpdateStudent/Teacher/Parent`, `GetMyProfile`, `UpdateMyProfile`. PR 2 "Class/Parent" (12 controller): `AddClassMember`, `UpdateClassMember`, `EndClassMember`, `GetClassMembers`, `AssignTeacherToClass`, `EndTeacherClassAssignment`, `GetClassTeachers`, `CreateParentStudentRelationship`, `EndParentStudentRelationship`, `ListParentStudentRelationships`, `GetMyChildren`, `GetMyChild` | Playbook ("UserManagement: chia 2 PR, review từng PR"); PR 1 chứa Enum `User/Student/Teacher/Parent`, PR 2 chứa 7/10 Action audit và Enum membership | Một PR 30 controller |
| D2 | Cấu trúc module: `Http/{Controllers,Requests,Resources}` (tên controller giữ nguyên, thêm `<UseCase>Request`), `Actions/` (mọi endpoint ghi, xem bảng endpoint), `Queries/{ListUsersQuery,MyProfileQuery}` (PR 1) + `{MyChildrenQuery,MyChildQuery}` (PR 2), `Policies/{StudentPolicy,ClassMemberPolicy,TeacherClassAssignmentPolicy}`, `Enums/{ProfileStatus,MembershipStatus}`, `Exceptions/*`, `Providers/UserManagementServiceProvider` (đăng ký ở `bootstrap/providers.php`) | CLAUDE.md "Cấu trúc"; không Event/Listener vì module không gửi thông báo | Tách thêm `Domain/` (không có tính toán thuần) |
| D3 | **Mọi endpoint ghi có Action** (kể cả `UpdatePersonProfile`, `UpdateStudent/Teacher/Parent`) để Controller không còn `->save()/->update()/::create()`. Hai điểm gom: `CreatePersonProfile::handle(array): PersonProfile` dùng chung cho `CreateStudent/Teacher/Parent` (3 nơi); `UpdateMyProfile` gọi `UpdatePersonProfile` (cùng lý do đổi). `Create{Student,Teacher,Parent}` bọc `DB::transaction` (ghi 2 bảng: `PersonProfile` + hồ sơ vai trò); **không thêm audit** (spec Module 5 §4 không liệt kê, `DEC-AUD-005`: không tự thêm) | Quy ước "controller không ghi DB"; DRY ≥3 nơi | Controller gọi `fill()->save()` trực tiếp |
| D4 | **10 Action có audit bắt buộc** (`UpdateUserStatus`, `AssignUserRole`, `EndUserRole` ở PR 1; `AddClassMember`, `UpdateClassMember`, `EndClassMember`, `AssignTeacherToClass`, `EndTeacherClassAssignment`, `CreateParentStudentRelationship`, `EndParentStudentRelationship` ở PR 2): một `DB::transaction` gồm đọc-khóa dòng đích (`lockForUpdate`) → kiểm tra trạng thái → ghi dữ liệu → `AuditLogger::record` (cùng `action`/`entityType`/`correlationKey`/`reason` như cũ). `before`/`after` ghi Enum bằng `->value`. Audit lỗi hủy cả thay đổi, có test rollback. `AssignTeacherToClass`/`EndTeacherClassAssignment` thêm việc đổi `SchoolClass.status` **vào cùng transaction** | `DEC-AUD-005`; D16 của plan Attendance (khóa dòng để `before` khớp) | Audit sau khi commit |
| D5 | **Policy default-deny cho vai trò ngoài phạm vi.** Code cũ chỉ chặn khi vai trò đang hoạt động là `teacher` và cho qua mọi vai trò khác (admin, rỗng...). Policy mới: `isAdmin()` → allow; vai trò `teacher` → kiểm tra phạm vi; **mọi vai trò còn lại (kể cả rỗng) → deny 403** cùng message cũ. Không đổi hành vi HTTP vì route đã `role:admin,teacher`; vai trò rỗng ở job/console không còn được coi là admin (checklist playbook). Ghi vào báo cáo cho Opus | Quy ước "chưa set = không quyền"; cùng cách làm D4 của Attendance | Giữ "mọi vai trò không phải teacher đều qua" |
| D6 | **Enum.** `ProfileStatus` = `active, inactive` (cast `Student.status`, `Teacher.status`, `ParentProfile.status`); `MembershipStatus` = `active, ended` (cast `ClassMember.status`, `TeacherClassAssignment.status`, `ParentStudentRelationship.status`) — hai Enum này nằm ở `Modules/UserManagement/Enums`. `UserStatus` = `active, inactive` cast `User.status`, nằm ở **`Modules/Identity/Enums`** (module gốc của Model `User`, theo model-ownership; thư mục `Modules/Identity` mới chỉ chứa Enum, giống `QuestionBank`/`Assessment`). Vì cast `User.status` làm hai chỗ cũ gãy âm thầm (`!== 'active'`), **sửa đúng hai dòng**: `app/Http/Middleware/EnsureUserIsActive.php:15` và `app/Http/Controllers/Api/Auth/LoginController.php:33` (so với case Enum). `RoleCode` (4 role code) **không làm ở 9b**: `AssignUserRole` giữ `in:admin,teacher,student,parent`; ghi nợ cho Task 9g `Identity` | Enum phản chiếu toàn bộ giá trị CHECK; cast chỉ gãy ở 2 chỗ attribute (grep đã rà, xem Bảng ảnh hưởng) | Hoãn `UserStatus` sang 9g (UpdateUserStatus sẽ phải mượn `ProfileStatus` cho cột User) |
| D7 | **`StudentPolicy` chuyển về `Modules/UserManagement/Policies`.** Giữ nguyên hai ability của ResultProgress (`viewProgress(User, Student)`, `viewAsParent(User, string $studentId)`, mã giữ nguyên từng dòng) và thêm `view(User, Student)` cho `GetStudent`. `Gate::policy(Student::class, ...)` chuyển từ `ResultProgressServiceProvider` sang `UserManagementServiceProvider`; xóa file cũ; cập nhật docblock (nó đã ghi "revisit khi 9b"); 5 test ResultProgress/Authorization chỉ đổi `use`/namespace. **Không đổi hành vi ResultProgress** | Chủ sở hữu Model là UserManagement; Laravel chỉ cho một Policy mỗi Model | Thêm `view` vào policy cũ ở ResultProgress (sai chủ sở hữu) |
| D8 | **(Cập nhật 2026-10-21 theo quyết định của user, ARCH-23 l+m)** `StudentPolicy::view`: admin allow; teacher → `ClassScope::teacherCoversStudent($user, $student)` (lớp chung đang hoạt động HOẶC Course sở hữu mà học viên đang ghi danh, cùng phạm vi `viewProgress`); ngoài phạm vi trả `Response::denyAsNotFound(DenialMessage::NOT_FOUND)` → 404 `NOT_FOUND` `'Request failed.'`. Khác code cũ ở 2 điểm (có chủ đích, user chốt): thêm Course và 404 thay 403 | Một nguồn sự thật cho phạm vi Teacher–Student; không lộ sự tồn tại (`ARCH-20`) | Giữ 403 + chỉ lớp chung (bản gốc của plan) |
| D9 | `ClassMemberPolicy::viewRoster(User, SchoolClass)` và `TeacherClassAssignmentPolicy::viewForClass(User, SchoolClass)` (PR 2; gọi `Gate::inspect('viewRoster', [ClassMember::class, $class])`): admin allow; teacher có lớp trong `ClassScope::classIdsFor($user,'teacher')` allow; còn lại deny 403 với message cũ (`'Không có quyền xem roster của lớp này.'` / `'Không có quyền xem phân công của lớp này.'`). Ability gắn Model của module dù đối tượng truyền vào là `SchoolClass` (Model của Academic) — cùng mẫu `markForSession` của Attendance. Endpoint ghi chỉ-admin: `authorize(): bool { return true; }` + docblock nêu `role:admin` | CLAUDE.md "Endpoint chỉ-admin không có rule theo đối tượng" | Policy trên `SchoolClass` (Model của module khác) |
| D10 | **Self-service dùng Query + ActiveRole, không Policy** (tự xem hồ sơ/con của mình, không có "đối tượng của người khác"): `MyProfileQuery::handle(User): array{person_profile: ?PersonProfile, role_profile: ?Model}` inject `ActiveRole`, chọn hồ sơ theo `code()` (`student`/`parent`/`teacher`, còn lại `null`, đúng `match` cũ); `MyChildrenQuery::handle(User): Collection` (liên kết `status='active'`, `[]` khi không có hồ sơ Parent, **không lọc `valid_to`**, đúng cũ); `MyChildQuery::handle(User, string $studentId): Student` ném `ChildNotFound` (404, `NOT_FOUND`, message `'Không tìm thấy.'`) khi không có liên kết active hoặc không có hồ sơ Parent. Giữ message `'Không tìm thấy.'`: nhánh "không tồn tại" và "không liên kết" cho **cùng** message nên không có lỗ enumeration (`ARCH-20` không áp dụng) | Giữ nguyên hành vi; Query là chỗ đọc có phạm vi | Dùng `StudentPolicy::viewAsParent` (trả 403 khi không có hồ sơ Parent và message `'Request failed.'` — khác hành vi cũ) |
| D11 | **`UpdateMyProfileRequest::authorize()`** trả `Response::deny('Không thể tự sửa full_name hoặc status.')` khi `$this->has('full_name') \|\| $this->has('status')` (kể cả giá trị `null`, đúng `has()` cũ), chạy **trước** validate — đúng thứ tự cũ; ngược lại allow. Validate: `email`, `phone` như cũ. Không có hồ sơ cá nhân → `NoProfile` (404, `NO_PROFILE`) ném từ Action sau validate | Giữ thứ tự 403 → 422 → 404 | Đưa kiểm tra vào `rules()` (đổi 403 thành 422) |
| D12 | **Giữ nguyên các lỗi nghiệp vụ**, mỗi lỗi thành một `DomainException` (UPPER_SNAKE giống cũ, **không đổi mã**): `LastAdmin` 409 `LAST_ADMIN`; `ActiveAssignmentsExist` 422 `ACTIVE_ASSIGNMENTS_EXIST` với `extra()` = `['active_class_ids' => [...]]` (key ngoài envelope) — dùng chung cho `UpdateUserStatus`, `EndUserRole`, `UpdateTeacher` (3 nơi, cùng message `'Teacher còn phân công lớp đang hoạt động, cần xử lý trước khi vô hiệu hóa.'`); `RoleAlreadyHeld` 409 `ROLE_ALREADY_HELD`; `ProfileAlreadyInvited` 409 `ALREADY_INVITED`; `NoProfile` 404 `NO_PROFILE`; `ChildNotFound` 404 `NOT_FOUND` (D10); PR 2: `AlreadyAMember` 409 `ALREADY_A_MEMBER`, `AlreadyAssigned` 409 `ALREADY_ASSIGNED`, `AlreadyLinked` 409 `ALREADY_LINKED`, `LinkAlreadyEnded` 409 `ALREADY_ENDED`, `InvalidMembershipPeriod` 422 `VALIDATION_FAILED` (message `'valid_to phải sau valid_from.'`, dùng lại mã chung có sẵn vì so sánh với `valid_from` trong DB nên không đặt được vào `rules()`). Các `firstOrFail()` giữ nguyên (handler trả 404 `'Request failed.'`). Thêm các mã mới vào `error-codes.md` cùng PR | Quy ước domain exception; không đổi contract | Đổi tên mã cho "đẹp" (FE chưa bám, nhưng không có lý do) |
| D13 | **`InvitePersonProfile`**: `DB::transaction` chỉ bọc `User::create` + `createToken` (hai bảng); **gửi mail `sendPasswordResetNotification` sau khi commit, ngoài transaction** — quan sát được giống hệt cũ (lỗi gửi mail vẫn làm 500 và user + token vẫn còn). Inject `Illuminate\Contracts\Auth\PasswordBroker` qua constructor thay `Password::broker()`. Không có audit (không nằm trong spec §4) | R5 (DI) và tránh bọc I/O ngoài trong transaction | Bọc cả mail trong transaction (đổi hành vi khi mail lỗi: user bị rollback) |
| D14 | **Sở hữu Model và R4/R8.** (a) `UserRole` đổi chủ sở hữu thành **UserManagement** (`MODEL_OWNERS` + `model-ownership.md`, bỏ nhãn `[CẦN XÁC NHẬN]`): `AssignUserRole`/`EndUserRole` là hai nơi duy nhất ghi bảng này (seeder chỉ ghi `Role`). `Role` giữ Identity (chỉ đọc). (b) `InvitePersonProfile` ghi `User::create` (Model của Identity) → R4 báo; thêm **một** mục `KNOWN_VIOLATIONS` (lý do: tạo tài khoản mời thuộc Identity; Task 9g Identity chốt Action hay chủ sở hữu); `UpdateUserStatus` ghi qua instance nên R4 không thấy — ghi chung vào dòng nợ. (c) `SchoolClass.status` đổi trong `AssignTeacherToClass`/`EndTeacherClassAssignment` là ghi Model của Academic qua instance (R4 mù): giữ trong Action, ghi nợ cho Task 9f (tách `TransitionClassStatus` của Academic). (d) R8: các literal `'active'` của **`SchoolClass.status`** trùng giá trị Enum của module nên R8 báo — chỉ tối đa các mục `KNOWN_LITERALS` ở `AssignTeacherToClass` (`'active'`) và `EndTeacherClassAssignment` (`'active'` so sánh + ghi); `'preparing'` không trùng. Nhiều hơn danh sách này: DỪNG | Giữ nguyên hành vi; ghi nợ có chủ đích thay vì giấu | Bắt Academic tách Action ngay trong 9b |
| D15 | **Query cho `ListUsers`**: `ListUsersQuery::handle(array $filters, int $perPage): LengthAwarePaginator` giữ đúng cách lọc cũ (`status` bằng nhau, `role` qua `userRoles.role.code` **và** `valid_to` rỗng/tương lai, `name`/`email` `ilike`). `ListUsersRequest::rules()` **rỗng**: không thêm validate (thêm sẽ đổi 200 → 422 với giá trị lạ); `per_page` mặc định 15 như cũ. Response `{ data: [...], meta: { total, current_page, last_page } }` **không có `links`**: Resource dựng tay (`$wrap = null`), không dùng `ResourceCollection` mặc định (thêm `links`/`meta` khác) | Giữ shape | `UserResource::collection($paginator)` |
| D16 | **Resource**: một Resource cho mỗi shape JSON khác nhau; `$wrap = null` cho shape có khóa riêng (`{ "student": {...} }`), Resource bọc model vừa tạo tự trả 201, nên với endpoint cũ trả 200 mà tạo/ghi bản ghi mới thì ép lại trong `withResponse`; endpoint `DELETE` cũ trả `noContent()` (204) giữ nguyên, không qua Resource. `person_profile` lồng nhau (id, full_name, email, phone) dùng chung `PersonProfileResource`/hàm dựng chung. Tập key phải **bằng đúng** shape cũ (test ghim toàn bộ key) | Playbook "Giữ nguyên hành vi: các bẫy đã gặp" | `JsonResource` mặc định bọc `data` |
| D17 | **Khoảng cách code–tài liệu** không sửa, ghi vào `architecture/open-decisions.md` mục mới `ARCH-23` ở Task 8/15 (người thực thi xác nhận bằng cách đọc, nếu không đúng thì sửa mô tả): (a) `DEC-AUD-003` yêu cầu business history riêng cho Membership/Teacher assignment/Parent–Student, nhưng `docs/database/schema.md` và migration không có bảng `*_histories` cho ba bảng này (chỉ có AuditLog); (b) `UserPersonProfileHistory` có trong `schema.md` nhưng không có migration/Model; (c) `UpdateUserStatus` inactivate `User` nhưng không đồng bộ status hồ sơ vai trò (Student/Teacher/Parent) — đúng với `BR-USER-001` (vòng đời riêng); (d) race ở "Admin cuối cùng" và `ALREADY_*` không có ràng buộc DB — giữ nguyên | Refactor không tự chọn | Sửa luôn (đổi phạm vi) |

### Danh sách Enum

| Enum | Namespace | Giá trị (theo migration) | Cột được cast | Nguồn | PR |
|---|---|---|---|---|---|
| `ProfileStatus` | `App\Modules\UserManagement\Enums` | `active`, `inactive` | `Student.status`, `Teacher.status`, `ParentProfile.status` | `2026_10_13_000003/4/5` | 1 |
| `UserStatus` | `App\Modules\Identity\Enums` | `active`, `inactive` | `User.status` | `0001_01_01_000000` | 1 |
| `MembershipStatus` | `App\Modules\UserManagement\Enums` | `active`, `ended` | `ClassMember.status`, `TeacherClassAssignment.status`, `ParentStudentRelationship.status` | `2026_10_13_000006/7/8` | 2 |

## Bảng endpoint (30)

Cột "Audit" theo spec Module 5 §4; "Authorize" theo D5/D8/D9/D10/D11. Controller giữ tên cũ trong `App\Modules\UserManagement\Http\Controllers`.

| # | PR | Endpoint | Request::authorize | Action / Query | Audit (`action`, `entityType`, `correlationKey`) | Resource (shape giữ nguyên) | Lưu ý giữ hành vi |
|---|---|---|---|---|---|---|---|
| 1 | 1 | `GET /users` | `true` (route `role:admin`) | `ListUsersQuery` | — | `UserListResource` (`data`+`meta`) | D15; `roles` chỉ role còn hiệu lực |
| 2 | 1 | `GET /users/{user}` | `true` | Eloquent trực tiếp (`load`) | — | `UserDetailResource` (`{user}`) | `role_history` gồm cả role đã kết thúc |
| 3 | 1 | `PATCH /users/{user}` | `true` | `UpdateUserStatus` | `user.status_update`, `User`, — | `UserStatusResource` (`{user:{id,status}}`) | `in:` → `Rule::enum(UserStatus)`; chỉ khi `inactive`: `LastAdmin` rồi `ActiveAssignmentsExist` (thứ tự cũ); `before = only('status')` |
| 4 | 1 | `POST /users/{user}/roles` | `true` | `AssignUserRole` | `user_role.assign`, `UserRole`, — (`after: user_id, role_code`) | `AssignedUserRoleResource` (`{user_role:{id,role,valid_from}}`, 201) | `RoleAlreadyHeld`; `Role::where('code')->firstOrFail()`; `assigned_by_user_id` |
| 5 | 1 | `DELETE /users/{user}/roles/{roleId}` | `true` | `EndUserRole` | `user_role.end`, `UserRole`, — (`after: valid_to`) | 204 (không body) | `firstOrFail` role hiệu lực; admin → `LastAdmin`; teacher → `ActiveAssignmentsExist`; `ended_by_user_id`; **không** set `end_reason` (như cũ) |
| 6 | 1 | `POST /person-profiles/{personProfile}/invite` | `true` | `InvitePersonProfile` (D13) | — | `InvitedUserResource` (`{user:{id,login_identifier}}`, 201) | `unique:users,login_identifier` (422) trước `ProfileAlreadyInvited` (409); mail gửi sau commit |
| 7 | 1 | `PATCH /person-profiles/{personProfile}` | `true` | `UpdatePersonProfile` | — | `PersonProfileResource` (`{person_profile}`) | `sometimes`: chỉ field có mặt |
| 8-10 | 1 | `POST /students` · `/teachers` · `/parents` | `true` | `CreateStudent` · `CreateTeacher` · `CreateParent` (+ `CreatePersonProfile`) | — | `StudentProfileResource` · `TeacherProfileResource` · `ParentProfileResource` (201, kèm `person_profile`) | `status` mặc định `active` (D6: ghi Enum); `notes`/`specialty` tùy chọn |
| 11-13 | 1 | `GET /students/{student}` (role `admin,teacher`) · `/teachers/{teacher}` · `/parents/{parent}` | `Gate::inspect('view', $student)` (D8) · `true` · `true` | Eloquent (`load('personProfile')`) | — | `StudentProfileResource` · `TeacherProfileResource` · `ParentProfileResource` | Student: 403 + message cũ |
| 14-16 | 1 | `PATCH /students/{student}` · `/teachers/{teacher}` · `/parents/{parent}` | `true` | `UpdateStudent` · `UpdateTeacher` · `UpdateParent` | — | `StudentSummaryResource` (`id,status,notes`) · `TeacherSummaryResource` (`id,status,specialty`) · `ParentSummaryResource` (`id,status`) | `status`: `Rule::enum(ProfileStatus)`; `UpdateTeacher` đặt `inactive` → `ActiveAssignmentsExist` (D12) dùng `$teacher->activeClassIds()` |
| 17 | 1 | `GET /me/profile` | `true` | `MyProfileQuery` (D10) | — | `MyProfileResource` (`{person_profile, role_profile}`) | `person_profile` = `null` khi User chưa có hồ sơ; `role_profile` = `{id,status}` hoặc `null` |
| 18 | 1 | `PATCH /me/profile` | D11 | `UpdateMyProfile` → `UpdatePersonProfile` | — | `PersonProfileResource` | 403 → 422 → 404 `NO_PROFILE` |
| 19 | 2 | `POST /classes/{class}/members` | `true` | `AddClassMember` | `class_member.add`, `ClassMember`, `class_id` | `ClassMemberAddedResource` (`{class_member:{id,class_id,student_id,valid_from}}`, 201) | `exists:students,id` (422) → `AlreadyAMember` (409); `valid_from=now()`, `status` Enum |
| 20 | 2 | `PATCH /classes/{class}/members/{studentId}` | `true` | `UpdateClassMember` | `class_member.retroactive_update`, `ClassMember`, `class_id`, `reason` bắt buộc (request) | `ClassMemberPeriodResource` (`{class_member:{id,valid_from,valid_to}}`) | `firstOrFail` member `active`; `InvalidMembershipPeriod`; `before/after = only(valid_from, valid_to)`; **chỉ** `valid_from`/`valid_to` được ghi |
| 21 | 2 | `DELETE /classes/{class}/members/{studentId}` | `true` | `EndClassMember` | `class_member.end`, `ClassMember`, `class_id`, `reason = end_reason` | 204 | `end_reason` bắt buộc; `ended_by_user_id`, `valid_to=now()` |
| 22 | 2 | `GET /classes/{class}/members` (role `admin,teacher`) | `Gate::inspect('viewRoster', [ClassMember::class, $class])` (D9) | Eloquent (`classMembers()->where('status','active')`) | — | `ClassRosterResource` (`{data:[{student_id,full_name,valid_from,valid_to}]}`) | |
| 23 | 2 | `POST /classes/{class}/teachers` | `true` | `AssignTeacherToClass` | `teacher_class_assignment.assign`, `TeacherClassAssignment`, `class_id` | `TeacherAssignmentResource` (`{teacher_class_assignment:{id,class_id,teacher_id,valid_from}}`, 201) | `exists:teachers,id` → `AlreadyAssigned`; lớp `preparing` → `active` (D4, D14) |
| 24 | 2 | `DELETE /classes/{class}/teachers/{teacherId}` | `true` | `EndTeacherClassAssignment` | `teacher_class_assignment.end`, `TeacherClassAssignment`, `class_id` | 204 | Hết teacher active và lớp `active` → `preparing` (**sau** audit, như cũ; cả hai trong transaction) |
| 25 | 2 | `GET /classes/{class}/teachers` (role `admin,teacher`) | `Gate::inspect('viewForClass', [TeacherClassAssignment::class, $class])` (D9) | Eloquent | — | `ClassTeachersResource` (`{data:[{teacher_id,full_name,valid_from,valid_to}]}`) | |
| 26 | 2 | `POST /parent-student-relationships` | `true` | `CreateParentStudentRelationship` | `parent_student_relationship.create`, `ParentStudentRelationship`, — | `ParentStudentRelationshipResource` (`{parent_student_relationship:{id,parent_id,student_id,relationship_type}}`, 201) | `exists` (422) → `AlreadyLinked`; `confirmed_by_user_id`, `confirmed_at` |
| 27 | 2 | `DELETE /parent-student-relationships/{parentStudentRelationship}` | `true` | `EndParentStudentRelationship` | `parent_student_relationship.end`, `ParentStudentRelationship`, —, `reason = end_reason` | 204 | `end_reason` (422) → `LinkAlreadyEnded` (409) |
| 28 | 2 | `GET /parent-student-relationships` | `true` | Eloquent (lọc `student_id`/`parent_id`) | — | `ParentStudentRelationshipListResource` (`{data:[{id,parent_id,student_id,relationship_type,status}]}`) | validate `uuid` (422) như cũ; không phân trang |
| 29 | 2 | `GET /me/children` (role `parent`) | `true` | `MyChildrenQuery` | — | `MyChildrenResource` (`{data:[{student_id,full_name,relationship_type}]}`) | `[]` khi không có hồ sơ Parent |
| 30 | 2 | `GET /me/children/{studentId}` (role `parent`) | `true` | `MyChildQuery` (D10) | — | `MyChildResource` (`{student:{id,full_name,status}}`) | 404 `NOT_FOUND` `'Không tìm thấy.'` |

## Bảng ảnh hưởng (kết quả grep `app/ database/ routes/ config/ tests/`)

Quét: `grep -rnE "(status|->status)[[:space:]]*(===|!==|==|!=)[[:space:]]*'(active|inactive|ended)'"` và `where('status', ...)` trên `app`, cộng `StudentPolicy`, `activeClassIds`, `activeChildrenIds`, `hasActiveRole`, `UserRole::`.

| File:dòng | Đọc/ghi gì | Xử lý |
|---|---|---|
| `app/Http/Middleware/EnsureUserIsActive.php:15`, `app/Http/Controllers/Api/Auth/LoginController.php:33` | `$user->status !== 'active'` (attribute của `User`, sẽ thành Enum) | **Sửa** hai dòng (D6) — gãy âm thầm nếu quên |
| `app/Models/{Student,Teacher,ParentProfile}.php`, `app/Models/User.php:74`, `app/Support/ClassScope.php`, `app/Support/StudentAssessmentSync.php`, `app/Modules/Attendance/Listeners/*`, `app/Http/Controllers/Api/{Assessment,Academic,Lesson}/**` | `where('status', 'active')` dạng query-builder với chuỗi | **Không sửa** (cast không ảnh hưởng điều kiện SQL) |
| `app/Http/Controllers/Api/Academic/{UpdateSubjectController,UpdateClassController,UpdateCourseController}.php`, `app/Services/Lesson/LessonSessionGenerator.php`, `app/Console/Commands/GenerateUpcomingLessonSessions.php` | `status` của Subject/Class/Course/Schedule | **Không sửa** (không phải cột bị cast ở đây) |
| `app/Models/{Student,Teacher,ParentProfile,ClassMember,TeacherClassAssignment,ParentStudentRelationship,User}.php` | `$casts` | **Sửa** (thêm cast) — bản chất task |
| `app/Modules/ResultProgress/{Policies/StudentPolicy,Providers/ResultProgressServiceProvider}.php` | Policy và `Gate::policy(Student...)` | **Chuyển/sửa** (D7) |
| `tests/Feature/ResultProgress/{ChildAccessOtherStudentTest,StudentProgressAuthorizationTest,ChildAccessAuthorizationTest,StudentProgressCourseScopeTest}.php`, `tests/Feature/Authorization/ActiveRoleMultiRoleTest.php` | Nhắc `StudentPolicy` | **Chỉ đổi** `use`/namespace nếu có |
| `app/Http/Middleware/EnsureRole.php`, `app/Http/Controllers/Api/Auth/ContextController.php` | `hasActiveRole()` | **Không sửa** (method của `User`, không đổi) |
| `database/factories/**`, `database/seeders/**` | `'status' => 'active'` | **Không sửa** (chuỗi hợp lệ gán vào thuộc tính cast Enum) |
| `routes/api.php` | 30 dòng tham chiếu controller cũ | **Sửa** (trỏ sang controller mới) |
| `tests/Feature/UserManagement/*`, `tests/Feature/Audit/UserManagementAuditTest.php` | Test hiện có | **Không sửa** (phải xanh nguyên văn); chỉ sửa nếu `assertSame('x', $model->status)` (đã rà: không có) |

Kết luận: ngoài module, cần sửa **7 Model (`$casts`)**, **2 dòng Identity**, `StudentPolicy`/provider ResultProgress, routes, `bootstrap/providers.php`, `ArchitectureTest`, `EnumCastsTest`.

## Review Focus (cho Opus)

1. **Phân quyền (đối chiếu `docs/access-control` + `requirements`):** D5/D8/D9/D10 — Teacher chỉ xem Student trong lớp mình (không Course), roster/phân công chỉ lớp mình; Parent chỉ thấy con còn liên kết `active`; self-service không xem hồ sơ người khác; endpoint admin-only không có kiểm tra đối tượng nhưng route `role:admin` là chốt chặn duy nhất — soi `routes/api.php`.
2. **Giữ nguyên hành vi:** thứ tự `authorize → validate → state` (chỉ `/me/profile` có 403 trước 422); mọi message 403/404/409/422 giữ nguyên; `ACTIVE_ASSIGNMENTS_EXIST` còn `active_class_ids`; `GET /me/children/{id}` giữ message `'Không tìm thấy.'`.
3. **Các ngoại lệ có chủ đích:** D5 (default-deny vai trò rỗng/khác), D7 (di chuyển `StudentPolicy` khỏi ResultProgress), D6 (cast `User.status` sửa 2 dòng Auth — kiểm tra đường đăng nhập `inactive` và `InactiveUserMidSessionTest`), D14 (đổi chủ `UserRole`, `KNOWN_VIOLATIONS`/`KNOWN_LITERALS`).
4. **Audit:** D4 — audit lỗi hủy cả thay đổi (kể cả đổi `SchoolClass.status` ở Assign/End teacher), `before`/`after`/`reason`/`correlation_key` y hệt cũ, snapshot chuỗi không Enum.
5. **Invite:** D13 — mail gửi sau commit, lỗi mail không làm mất user (như cũ); token cũ không bị hủy khi mời lần hai (như cũ: 409 trước).
6. **Bảng đột biến (Task 7/14):** cái nào sống sót và test bổ sung.

---

## Danh sách file được phép đụng

| File | Trách nhiệm | PR / Task |
|---|---|---|
| `tests/Feature/UserManagement/Concerns/UserManagementFixtures.php` (mới) | Trait fixture dùng chung | 1 / T1 |
| `tests/Feature/UserManagement/{UserAdminBehaviorTest,RoleProfileBehaviorTest,MyProfileBehaviorTest}.php` (mới) | Đặc tả hành vi PR 1 | 1 / T1 |
| `tests/Feature/UserManagement/{ClassMembershipBehaviorTest,TeacherAssignmentBehaviorTest,ParentLinkBehaviorTest,MyChildrenBehaviorTest}.php` (mới) | Đặc tả hành vi PR 2 | 2 / T9 |
| `app/Modules/UserManagement/Enums/{ProfileStatus,MembershipStatus}.php`, `app/Modules/Identity/Enums/UserStatus.php` (mới) | Enum | 1,2 |
| `tests/Unit/Modules/UserManagement/Enums/*Test.php`, `tests/Unit/Modules/Identity/Enums/UserStatusTest.php` (mới) | Tập giá trị Enum | 1,2 |
| `app/Models/{User,Student,Teacher,ParentProfile}.php` / `{ClassMember,TeacherClassAssignment,ParentStudentRelationship}.php` | `$casts` | 1 / 2 |
| `app/Http/Middleware/EnsureUserIsActive.php`, `app/Http/Controllers/Api/Auth/LoginController.php` | **Đúng 1 dòng mỗi file** (D6) | 1 |
| `tests/Feature/Models/EnumCastsTest.php` | Thêm cột cast | 1,2 |
| `app/Modules/UserManagement/{Http,Actions,Queries,Policies,Exceptions,Providers}/**` (mới) | Module | 1,2 |
| `app/Modules/ResultProgress/{Policies/StudentPolicy.php,Providers/ResultProgressServiceProvider.php}` | Chuyển Policy (D7) | 1 |
| 5 test ở "Bảng ảnh hưởng" | Chỉ `use`/namespace | 1 |
| `bootstrap/providers.php`, `routes/api.php` | Đăng ký, route | 1,2 |
| `app/Http/Controllers/Api/UserManagement/*` | **Xóa** (18 file ở PR 1, 12 ở PR 2) | 1,2 |
| `tests/Architecture/ArchitectureTest.php` | `MODULES`, `SHARED_ENUMS`, `MODEL_OWNERS`, `KNOWN_*` | 1,2 |
| `tests/Feature/UserManagement/{StudentPolicyTest,UserManagementActionsTest,UserManagementAuditRollbackTest,...}.php` (mới) | Phân quyền, rollback, Action | 1,2 |
| `piclass-api/CLAUDE.md`; docs: playbook, `error-codes.md`, `model-ownership.md`, `open-decisions.md`, `progress.md` | Ghi nhận | T8, T15 |

---

## Chuẩn bị (trước Task 1)

- [ ] **P1: Kiểm tra container.** `docker ps` ở trên. Expected: có `piclass-local-api-1`. Không có: DỪNG.
- [ ] **P2: Tạo branch** theo mục "Git" (BE `refactor/user-management-1`; docs `docs/be-user-management`).
- [ ] **P3: Baseline.** Full suite. Expected: `992 passed`. Khác 992: DỪNG và báo con số.

---

# PHẦN 1 — PR 1: User / Role / Profile (18 controller)

### Task 1: Test đặc tả hành vi trên code cũ

Mục đích: ghim hành vi hiện tại của 18 endpoint PR 1 **trước khi sửa**. **Tất cả phải xanh trên code CŨ.** Test đỏ: DỪNG và báo. Trait `UserManagementFixtures` dựng admin (có `UserRole` admin), teacher/student/parent có hồ sơ + `User` + đăng nhập theo vai trò (đặt `active_role_code` trong session như các test hiện có), lớp, `ClassMember`, `TeacherClassAssignment`, Course của teacher (để dựng teacher chỉ có Course, không có lớp chung).

- [ ] **Step 1: `UserAdminBehaviorTest`** (users, roles, invite). Ghim cho từng endpoint: **toàn bộ tập key** response (so sánh mảng key), status, `error.code` + `error.message` literal của mọi 403/404/409/422:
  - `GET /users`: key `data[0]` (`id,login_identifier,status,full_name,roles`) và `meta` (`total,current_page,last_page`, **không** `links`); lọc `status`, `role` (role đã kết thúc không khớp), `name`/`email` (không phân biệt hoa thường), `per_page`; `roles` chỉ gồm role còn hiệu lực.
  - `GET /users/{user}`: key (`id,login_identifier,status,full_name,active_roles,role_history[].{role,valid_from,valid_to}`); user không tồn tại → 404 `'Request failed.'`.
  - `PATCH /users/{user}`: `status=inactive` cho admin cuối cùng → 409 `LAST_ADMIN`; có admin khác active → 200; teacher còn phân công → 422 `ACTIVE_ASSIGNMENTS_EXIST` kèm `active_class_ids` (key ngoài envelope); admin vừa cuối cùng vừa là teacher còn phân công → `LAST_ADMIN` trước; `status=active` bỏ qua cả hai kiểm tra; `status` sai → 422 `VALIDATION_FAILED` + `error.details.status`; audit: `action='user.status_update'`, `entity_type='User'`, `before_data={"status":"active"}`, `after_data={"status":"inactive"}`, `actor`, `correlation_key` null, `reason` null.
  - `POST /users/{user}/roles`: 201 + key (`user_role.{id,role,valid_from}`); đã có role → 409 `ROLE_ALREADY_HELD`; `role_code` sai/thiếu → 422; audit `user_role.assign` (`after_data={"user_id":...,"role_code":...}`); `assigned_by_user_id` = actor.
  - `DELETE /users/{user}/roles/{roleId}`: 204 không body; role đã kết thúc → 404; admin cuối → `LAST_ADMIN`; teacher còn phân công → `ACTIVE_ASSIGNMENTS_EXIST`; `valid_to`, `ended_by_user_id` được ghi, `end_reason` giữ `null`; audit `user_role.end` (`after_data` chứa `valid_to`).
  - `POST /person-profiles/{id}/invite`: 201 + key (`user.{id,login_identifier}`); `login_identifier` trùng → 422 trước `ALREADY_INVITED`; đã có tài khoản → 409; `Notification::fake()`/`Mail::fake()` ghim đúng một thông báo reset gửi tới user mới; **không có** dòng `audit_logs`; password lưu là hash (không phải chuỗi rỗng).
  - Mọi endpoint: student/teacher/parent → 403 `FORBIDDEN` (middleware `role:admin`), chưa đăng nhập → 401, user đăng nhập vai trò khác của cùng người → 403. Ghim `error.code` + `error.message` thực tế của middleware.
- [ ] **Step 2: `RoleProfileBehaviorTest`** (student/teacher/parent + person-profile): key đầy đủ của 3 shape (full có `person_profile` khi Create/Get; summary khi Update), 201 khi tạo, `status` mặc định `active`; `PATCH person-profiles` chỉ đổi field có mặt (`sometimes`), `email` sai → 422; `PATCH /teachers/{id}` `status=inactive` khi còn phân công → 422 `ACTIVE_ASSIGNMENTS_EXIST` + `active_class_ids`, hết phân công → 200; `PATCH /students/{id}` / `/parents/{id}` `status=inactive` không bị chặn bởi lớp/liên kết; `status` sai → 422 + `error.details.status` (message `in:`); `GET /students/{id}`: admin → 200; teacher chung lớp → 200; teacher **lớp khác** (student có lớp và member thật) → 403 `FORBIDDEN` `'Không có quyền xem hồ sơ học viên này.'`; teacher chỉ **sở hữu Course** mà student ghi danh (không chung lớp) → 403 (ghim D8); teacher có phân công `ended` → 403; teacher không có hồ sơ Teacher → 403; student/parent → 403 middleware; `GET /teachers/{id}`, `/parents/{id}` chỉ admin. Mọi Create/Update **không** ghi `audit_logs`.
- [ ] **Step 3: `MyProfileBehaviorTest`**: `GET /me/profile` theo vai trò đang hoạt động (student → `role_profile={id,status}` của Student; parent; teacher; admin → `role_profile=null`; user nhiều vai trò chỉ lấy hồ sơ của vai trò đang chọn; user không có `person_profile` → `person_profile=null`); `PATCH /me/profile`: body có `full_name` (kể cả `null`) hoặc `status` → 403 `FORBIDDEN` `'Không thể tự sửa full_name hoặc status.'` **kể cả khi** cùng lúc `email` sai (403 thắng 422); `email` sai → 422; chỉ đổi field có mặt; user không có hồ sơ → 404 `NO_PROFILE`; response key (`person_profile.{id,full_name,email,phone}`).
- [ ] **Step 4: Thăm dò 500** (quy tắc 8): từng lệnh một, ghi status thực tế; `GET /users?per_page=abc`, `?role[]=x`, `?status[]=x`, `DELETE /users/{u}/roles/not-a-uuid`. Case nào 500: bỏ khỏi test, ghi vào báo cáo.
- [ ] **Step 5: Chạy trên code cũ.** `php artisan test tests/Feature/UserManagement tests/Feature/Audit`. Expected: tất cả xanh (test cũ + mới). Test mới đỏ: DỪNG.
- [ ] **Step 6: Pint `--test`** trên file mới. **Commit:** `test: pin UserManagement user, role and profile behavior before the module refactor`.

### Task 2: Enum + cast (+ hai dòng Identity)

- [ ] **Step 1:** Viết `ProfileStatus`, `UserStatus` (backed `string`, case PascalCase `Active`, `Inactive`) với unit test tập giá trị. 
- [ ] **Step 2:** `$casts` — `User.status`, `Student.status`, `Teacher.status`, `ParentProfile.status` (giữ cast cũ có sẵn).
- [ ] **Step 3:** `EnumCastsTest::castColumns()` thêm `'User.status'`, `'Student.status'`, `'Teacher.status'`, `'ParentProfile.status'` (đối chiếu với CHECK của Postgres; xem cách test hiện tại tạo model qua factory).
- [ ] **Step 4:** Sửa đúng hai dòng Identity (D6) để so với `UserStatus::Active`; thêm/chỉnh test để ghim: user `inactive` không đăng nhập được (`LoginTest`), user bị `inactive` giữa phiên bị chặn (`InactiveUserMidSessionTest`) — chạy cả hai.
- [ ] **Step 5:** Chạy `tests/Feature/{UserManagement,Auth,Audit,Models}` + `tests/Unit/Modules`. Expected: xanh. **Lưu ý:** controller cũ gán `$user->status` vào JSON (`UpdateUserStatusController`, `ListUsers`, `GetUser`): backed Enum encode ra chuỗi nên JSON không đổi — nếu test đỏ vì kiểu so sánh, sửa bằng `->value`, mọi trường hợp khác DỪNG.
- [ ] **Step 6:** Pint; **Commit:** `feat: add profile and user status enums and cast the status columns`.

### Task 3: Hạ tầng module — Provider, StudentPolicy, Query, Exception

- [ ] **Step 1: Exceptions** (D12): `LastAdmin`, `ActiveAssignmentsExist` (với `extra()` và constructor nhận `Collection|array $classIds`), `RoleAlreadyHeld`, `ProfileAlreadyInvited`, `NoProfile`. Unit test: mỗi lớp có `errorCode`/`status`/message đúng.
- [ ] **Step 2: `StudentPolicy`** — chuyển file (D7), thêm `view` (D8) inject `ActiveRole`. Sửa docblock, import. Chạy lại **toàn bộ** `tests/Feature/ResultProgress` + `tests/Feature/Authorization` ngay sau khi chuyển (phải xanh trước khi viết `view`).
- [ ] **Step 3: Queries**: `ListUsersQuery`, `MyProfileQuery` (D10, D15).
- [ ] **Step 4: Provider** `UserManagementServiceProvider` (`Gate::policy(Student::class, StudentPolicy::class)`), đăng ký ở `bootstrap/providers.php`; bỏ dòng cũ trong `ResultProgressServiceProvider`.
- [ ] **Step 5:** Chạy `tests/Feature/UserManagement` (controller cũ vẫn chạy) + ResultProgress + Authorization + `ArchitectureTest`. Pint. **Commit:** `feat: add UserManagement policy, queries and exceptions; move StudentPolicy to its owner`.

### Task 4: Chuyển từng endpoint (bảng endpoint, 18 dòng)

Mỗi nhóm: viết FormRequest/Resource/Controller/Action mới, đổi route sang controller mới, xóa controller cũ, chạy `tests/Feature/UserManagement` + `Audit` + `Auth` + `ResultProgress` — **toàn bộ xanh, không ngoại lệ**. Chỉ commit khi xanh.

- [ ] **4a Users & roles** (dòng 1-6: `ListUsers`, `GetUser`, `UpdateUserStatus`, `AssignUserRole`, `EndUserRole`, `InvitePersonProfile`): theo D4 (3 Action audit trong transaction + `lockForUpdate` dòng đích để `before` khớp), D12, D13, D15. **Commit:** `refactor: move user, role and invite endpoints into UserManagement and audit in the transaction`.
- [ ] **4b Hồ sơ vai trò** (dòng 7-16: `UpdatePersonProfile`, `Create*`, `Get*`, `Update*`): `CreatePersonProfile` + 3 Create trong transaction; `GetStudent` dùng `Gate::inspect('view', ...)`; `UpdateTeacher` ném `ActiveAssignmentsExist`. **Commit:** `refactor: move person and role profile endpoints into UserManagement`.
- [ ] **4c Hồ sơ của tôi** (dòng 17-18: `GetMyProfile`, `UpdateMyProfile`): D10, D11. **Commit.**
- [ ] **4d:** `grep -rn "Controllers\\\\Api\\\\UserManagement" app routes tests` chỉ còn 12 controller PR 2; dọn `use` thừa.

### Task 5: Rule kiến trúc

- [ ] **Step 1 (đỏ trước):** thêm `'UserManagement'` vào `MODULES` và chạy `ArchitectureTest` — xem R1-R8 báo gì ở module mới. Mọi vi phạm không có trong D14: DỪNG.
- [ ] **Step 2:** `SHARED_ENUMS['UserManagement'] = ['Identity/UserStatus']`; `MODEL_OWNERS['UserRole'] = 'UserManagement'` (D14a); thêm mục `KNOWN_VIOLATIONS` cho `InvitePersonProfile` `User::create` (D14b). Cập nhật docblock `ArchitectureTest` (dịch tiếng Việt phần đã đụng).
- [ ] **Step 3:** Chạy; Pint; **Commit:** `test: add UserManagement to the architecture rules`.

### Task 6: Test phân quyền, rollback, Action (theo checklist playbook)

Mọi test dùng fixture có dữ liệu thật ở lớp/người khác; 403/404 assert `error.code` + `error.message` literal.

- [ ] **`StudentPolicyTest`** (gọi `Gate::forUser($user)->inspect('view', $student)` và `app(StudentPolicy::class)` trực tiếp, đặt `ActiveRole` tường minh): `view` × {admin; teacher chung lớp; teacher **lớp khác** (student có lớp + member thật); teacher chung lớp nhưng `TeacherClassAssignment` `ended`; teacher chung lớp nhưng `ClassMember` `ended`; teacher chỉ sở hữu Course (không chung lớp) → deny; teacher không có hồ sơ; vai trò rỗng → deny (D5); user admin+teacher đang ở vai trò teacher không phân công → deny; vai trò student/parent → deny}. Hai ability cũ `viewProgress`/`viewAsParent`: giữ nguyên các test hiện có của ResultProgress (không viết lại).
- [ ] **`UserManagementOutsideHttpTest`**: `MyProfileQuery` — vai trò rỗng ⇒ `role_profile=null`; sau `set('student')` chỉ hồ sơ student; `ListUsersQuery` chạy không cần session/Request; Policy gọi trực tiếp không qua queue.
- [ ] **`UserManagementAuditRollbackTest`** (mẫu `AttendanceAuditRollbackTest`): bind `AuditLogger` giả ném `RuntimeException('boom')`; với `UpdateUserStatus`, `AssignUserRole`, `EndUserRole`: `assertSame('boom', ...)`, dữ liệu trước/sau không đổi (`users.status`, `user_roles` không có dòng mới / `valid_to` vẫn null), `audit_logs` = 0. Thêm điểm lỗi thứ hai cho từng Action nếu có bước ghi thứ hai.
- [ ] **`UserManagementActionsTest`**: `UpdateUserStatus` gọi trực tiếp — `before_data`/`after_data` là chuỗi (`'active'`, không phải Enum) trong `audit_logs`; hai lần liên tiếp: `before` lần 2 = `after` lần 1; `AssignUserRole` không tạo dòng khi `RoleAlreadyHeld`; `EndUserRole` admin cuối bị chặn không đổi `valid_to`; `Create{Student,Teacher,Parent}` lỗi ở bước tạo hồ sơ vai trò (ví dụ ép exception) không để lại `PersonProfile` mồ côi (transaction); `InvitePersonProfile` lỗi gửi mail: `User` và token vẫn còn (D13).
- [ ] Chạy toàn bộ test mới; Pint; **Commit** theo nhóm (policy / rollback / actions).

### Task 7: Thử đột biến (bắt buộc) — trên bản sao ngoài working tree

Quy trình: trong container `mkdir /tmp/mutN && cp -a /var/www/html/. /tmp/mutN/` (loại `storage/logs`), sửa **bản sao**, chạy test liên quan ở bản sao (`cd /tmp/mutN && php artisan test <filter>`), ghi test đỏ, xóa `/tmp/mutN`. Không chạy song song với full suite. Lỗi cố ý nào test không bắt: bổ sung test ở Task 6, chạy lại.

| # | Đột biến | Test kỳ vọng đỏ |
|---|---|---|
| 1 | `StudentPolicy::view`: luôn allow | Policy + Behavior (403 teacher lớp khác) |
| 2 | `view`: bỏ nhánh `isAdmin()` | Policy (admin) |
| 3 | `view`: vai trò rỗng coi như admin | Policy / OutsideHttp |
| 4 | `view`: dùng `teacherCoversStudent` (thêm Course) | Policy + Behavior (teacher chỉ có Course) |
| 5 | `view`: bỏ lọc `status` của phân công/membership (dùng quan hệ không lọc) | Policy (`ended`) |
| 6 | `view`: đổi message deny | Behavior (message literal) |
| 7 | `UpdateUserStatus`: bỏ kiểm tra `LastAdmin` / bỏ `ActiveAssignmentsExist` / đảo thứ tự hai kiểm tra | Behavior (3 đột biến riêng) |
| 8 | `UpdateUserStatus`: chỉ kiểm tra khi `active` (đảo điều kiện `inactive`) | Behavior |
| 9 | `UpdateUserStatus`/`AssignUserRole`/`EndUserRole`: audit sau transaction | Rollback (3 đột biến riêng) |
| 10 | `UpdateUserStatus`: `before` lấy sau khi update / bỏ `lockForUpdate` | Behavior + Actions (cần test khóa dòng như `AttendanceBoundaryCases`) |
| 11 | `AssignUserRole`: bỏ `RoleAlreadyHeld`; sai `assigned_by_user_id` | Behavior |
| 12 | `EndUserRole`: bỏ lọc `valid_to` hiệu lực / bỏ `LastAdmin` / bỏ `ActiveAssignmentsExist` / bỏ `ended_by_user_id` | Behavior (4 đột biến riêng) |
| 13 | `InvitePersonProfile`: bỏ `ProfileAlreadyInvited`; không gửi mail; mail gửi trong transaction | Behavior / Actions |
| 14 | `ListUsersQuery`: bỏ lọc `valid_to` của role / đổi `ilike` thành `like` / bỏ lọc `status` | Behavior (3 đột biến riêng) |
| 15 | `UpdateTeacher`: bỏ kiểm tra phân công khi `inactive` | Behavior |
| 16 | `Create*`: bỏ transaction | Actions (lỗi giữa chừng) |
| 17 | `MyProfileQuery`: đảo ánh xạ vai trò → hồ sơ; vai trò rỗng trả hồ sơ student | Behavior + OutsideHttp |
| 18 | `UpdateMyProfileRequest`: bỏ kiểm tra `full_name` / bỏ kiểm tra `status` / chuyển 403 sau validate | Behavior (3 đột biến riêng) |
| 19 | Resource: bỏ một key bất kỳ / đổi tên key / thêm `links` vào `ListUsers` | Behavior (key-set) |
| 20 | `EnsureUserIsActive`/`LoginController`: đảo so sánh `UserStatus::Active` | Auth tests |
| 21 | Enum: thêm/bớt một case | EnumCastsTest (CHECK) + Enum unit |
| 22 | Bỏ `Gate::policy(Student...)` khỏi provider | Behavior 403 + ResultProgress tests |
| 23 | `Update*`: `Rule::enum` → bỏ rule `status` | Behavior (422) |
| 24 | R8: bỏ `Identity/UserStatus` khỏi `SHARED_ENUMS` | `test_shared_enums_match_the_imports` |

- [ ] Ghi bảng kết quả (đột biến → test đỏ / sống sót + test bổ sung) vào báo cáo cuối và `docs/progress.md`.

### Task 8: Tài liệu và nghiệm thu PR 1

- [ ] **Docs:** `error-codes.md` thêm các mã PR 1 (`LAST_ADMIN`, `ACTIVE_ASSIGNMENTS_EXIST`, `ROLE_ALREADY_HELD`, `ALREADY_INVITED`, `NO_PROFILE`, `NOT_FOUND` của `/me/children` — có thể để PR 2); `model-ownership.md`: `UserRole` → UserManagement, bỏ `[CẦN XÁC NHẬN]`, cập nhật bảng "Chốt khi nào"; `open-decisions.md`: thêm `ARCH-23` (D17) và nợ `RoleCode`/`UserStatus` ở 9g; playbook: xóa các dòng nợ đã xong (cast `User.status` chờ 9g — đã làm; `StudentPolicy` revisit), thêm nợ mới (`KNOWN_VIOLATIONS` `User::create`, ghi `SchoolClass.status` từ UM, các 500 đã thăm dò); `piclass-api/CLAUDE.md` mục Enum ("Đã chuyển: ... `UserManagement` (PR 1)"); `progress.md` thêm bullet "Kết quả Task 9b PR 1".
- [ ] **Full suite** (nền): Expected `Tests:` = 992 + số test mới, 0 fail. `vendor/bin/pint --test` trên mọi file `.php` đã đổi. `git log --oneline origin/main..HEAD` ở cả hai repo; `git status` sạch; **xác nhận chưa push**.
- [ ] **Báo cáo cho Opus:** diff từng Policy/Query trước-sau, bảng lỗi cố ý đã thử, danh sách chỗ không chắc (ít nhất: D5, D7, D8 so với `teacherCoversStudent`, D13, D14). **DỪNG.** Chờ review Opus → sửa → user quyết merge PR 1. Không bắt đầu Phần 2 cho tới khi PR 1 đã merge vào `main`.

---

# PHẦN 2 — PR 2: Class / Parent (12 controller)

> Chỉ bắt đầu sau khi PR 1 đã merge. Chạy lại **P1-P3** với baseline mới (số test sau PR 1) và `git checkout -b refactor/user-management-2 origin/main`. Đọc lại "Ghi chú thực thi" của PR 1 và sửa plan nếu PR 1 phát sinh quyết định khác.

### Task 9: Test đặc tả hành vi trên code cũ

**Tất cả phải xanh trên code CŨ.** Dùng lại `UserManagementFixtures` (bổ sung hàm dựng lớp có member/teacher/parent link nếu thiếu).

- [ ] **Step 1: `ClassMembershipBehaviorTest`** (dòng 19-22): key response, status, `code`+`message`; `POST members`: `student_id` không uuid/không tồn tại → 422, đã là member active → 409 `ALREADY_A_MEMBER`, member đã `ended` thêm lại → 201; audit `class_member.add` (`after_data={"class_id","student_id"}`, `correlation_key=class_id`); `PATCH members/{studentId}`: `reason` thiếu → 422 trước mọi thứ; không có member active → 404 `'Request failed.'`; `valid_to <= valid_from` (kể cả `valid_from` lấy từ DB khi body chỉ gửi `valid_to`) → 422 `VALIDATION_FAILED` `'valid_to phải sau valid_from.'`; chỉ `valid_from`/`valid_to` được ghi (không ghi `reason`); audit `class_member.retroactive_update` với `before_data`/`after_data` (key `valid_from`,`valid_to`), `reason`, `correlation_key`; `DELETE members/{studentId}`: `end_reason` thiếu → 422; 204; `status='ended'`, `valid_to`, `ended_by_user_id`, `end_reason`; audit `class_member.end` (`after_data={"status":"ended"}`, `reason=end_reason`); `GET members`: admin 200 với key `data[].{student_id,full_name,valid_from,valid_to}` chỉ member active; teacher đúng lớp 200; teacher lớp khác → 403 `FORBIDDEN` `'Không có quyền xem roster của lớp này.'`; teacher đã `ended` → 403; teacher không hồ sơ → 403; student/parent → 403 middleware.
- [ ] **Step 2: `TeacherAssignmentBehaviorTest`** (dòng 23-25): `POST teachers`: `exists:teachers` 422, đã phân công → 409 `ALREADY_ASSIGNED`; lớp `preparing` → `active` sau phân công, lớp đã `active` giữ nguyên, lớp `archived`... giữ nguyên (ghim giá trị thực tế); audit `teacher_class_assignment.assign`; `DELETE teachers/{teacherId}`: không có phân công active → 404; còn teacher khác → lớp giữ `active`; hết teacher và lớp `active` → `preparing`; hết teacher nhưng lớp khác trạng thái → không đổi; audit `teacher_class_assignment.end`; `GET teachers`: ma trận giống Step 1 với message `'Không có quyền xem phân công của lớp này.'`.
- [ ] **Step 3: `ParentLinkBehaviorTest`** (dòng 26-28): `POST`: `exists` 422, `ALREADY_LINKED` 409, liên kết `ended` thêm lại → 201; `relationship_type` tùy chọn; `confirmed_by_user_id`/`confirmed_at`; audit `parent_student_relationship.create`; `DELETE`: `end_reason` 422 trước `ALREADY_ENDED` 409; ghi `status/valid_to/ended_by_user_id/end_reason`; audit `.end` kèm `reason`; `GET` lọc theo `student_id`/`parent_id`, `uuid` sai → 422, key `data[].{id,parent_id,student_id,relationship_type,status}`, trả cả liên kết `ended`.
- [ ] **Step 4: `MyChildrenBehaviorTest`** (dòng 29-30): `GET /me/children`: parent có hồ sơ → chỉ liên kết `active`, key `data[].{student_id,full_name,relationship_type}`; **liên kết `active` nhưng `valid_to` quá khứ vẫn được liệt kê** (ghim hành vi cũ, không lọc `valid_to`); không có hồ sơ Parent → `data=[]`; `GET /me/children/{studentId}`: con còn liên kết → 200 key `student.{id,full_name,status}`; con của parent khác → 404 `NOT_FOUND` `'Không tìm thấy.'`; liên kết `ended` → 404; không tồn tại → cùng 404 và cùng message; không có hồ sơ Parent → 404 cùng message; vai trò khác → 403 middleware.
- [ ] **Step 5: Thăm dò 500** (quy tắc 8): `members/not-a-uuid`, `teachers/not-a-uuid`, `/me/children/not-a-uuid`.
- [ ] **Step 6:** Chạy trên code cũ (`tests/Feature/UserManagement tests/Feature/Audit`). Pint. **Commit:** `test: pin UserManagement class, assignment and parent link behavior before the module refactor`.

### Task 10: Enum + cast

- [ ] `MembershipStatus` (`Active`, `Ended`) + unit test; `$casts` cho 3 Model; `EnumCastsTest` thêm 3 cột; chạy `tests/Feature/{UserManagement,Models,Audit,Academic,Lesson,Assessment,Attendance,Authorization}` (các module khác đọc `status` của ba bảng này qua `where('status','active')` — không ảnh hưởng, nhưng `grep` lại `ClassMember|TeacherClassAssignment|ParentStudentRelationship` + `->status` trước khi cast; chỗ attribute ngoài danh sách: DỪNG). Pint; **Commit:** `feat: add membership status enum and cast the membership status columns`.

### Task 11: Hạ tầng PR 2 — Policy, Query, Exception

- [ ] **Exceptions** (D12 phần PR 2): `AlreadyAMember`, `AlreadyAssigned`, `AlreadyLinked`, `LinkAlreadyEnded`, `InvalidMembershipPeriod`, `ChildNotFound` + unit test.
- [ ] **Policies** `ClassMemberPolicy::viewRoster`, `TeacherClassAssignmentPolicy::viewForClass` (D9); đăng ký trong Provider.
- [ ] **Queries** `MyChildrenQuery`, `MyChildQuery` (D10).
- [ ] Chạy test module + `ArchitectureTest`; Pint; **Commit:** `feat: add UserManagement class policies and parent self-service queries`.

### Task 12: Chuyển từng endpoint (bảng endpoint, dòng 19-30)

Cùng quy tắc Task 4: xanh toàn bộ trước mỗi commit.

- [ ] **12a Membership lớp** (19-22: `AddClassMember`, `UpdateClassMember`, `EndClassMember`, `GetClassMembers`): D4 — 3 Action audit trong transaction, `lockForUpdate` dòng `ClassMember` đích; `UpdateClassMember` chỉ ghi `valid_from`/`valid_to` (`array_intersect_key` như cũ). **Commit.**
- [ ] **12b Phân công teacher** (23-25): D4 + D14c — đổi `SchoolClass.status` trong cùng transaction với audit; thứ tự cũ ở `EndTeacherClassAssignment`: update → audit → kiểm tra còn teacher active → đổi trạng thái lớp. **Commit.**
- [ ] **12c Liên kết Parent–Student** (26-28). **Commit.**
- [ ] **12d Self-service con** (29-30). **Commit.**
- [ ] **12e:** Xóa `app/Http/Controllers/Api/UserManagement/` (phải rỗng), dọn `use`; `grep -rn "Controllers\\\\Api\\\\UserManagement" app routes tests` rỗng.

### Task 13: Rule kiến trúc & phân quyền, rollback, Action

- [ ] **Rule kiến trúc:** chạy `ArchitectureTest` — `KNOWN_LITERALS` chỉ được có các mục D14d (nhiều hơn: DỪNG); cập nhật `MODEL_OWNERS` nếu cần; Pint. **Commit:** `test: record the known SchoolClass status literals in UserManagement`.
- [ ] **`ClassRosterPolicyTest`** (cho `viewRoster` và `viewForClass`, gọi trực tiếp): × {admin; teacher đúng lớp; teacher **lớp khác** (lớp khác có member + phân công thật); teacher `ended`; teacher không hồ sơ; vai trò rỗng → deny (D5); admin+teacher nhập vai teacher không phân công → deny; student/parent → deny}.
- [ ] **`MyChildrenOutsideHttpTest`**: `MyChildrenQuery`/`MyChildQuery` — parent link active; link `ended`; con của parent khác (dữ liệu thật); không có hồ sơ Parent; Query chạy không cần session; message `'Không tìm thấy.'` giữ nguyên.
- [ ] **`UserManagementMembershipRollbackTest`**: audit giả ném lỗi → `AddClassMember` (không tạo member), `UpdateClassMember` (`valid_from/valid_to` giữ), `EndClassMember` (giữ `active`), `AssignTeacherToClass` (không tạo phân công **và lớp giữ `preparing`**), `EndTeacherClassAssignment` (giữ `active`, lớp giữ `active`), `CreateParentStudentRelationship`, `EndParentStudentRelationship`: `assertSame('boom', ...)`, `audit_logs` = 0. Điểm lỗi thứ hai: đổi trạng thái lớp lỗi → phân công/audit cũng hủy.
- [ ] **`UserManagementMembershipActionsTest`**: snapshot `before/after` là chuỗi; `AddClassMember` lại sau `ended` tạo dòng mới (không sửa dòng cũ); `InvalidMembershipPeriod` không ghi gì; khóa dòng (`lockForUpdate`) có test như `AttendanceBoundaryCasesTest`.
- [ ] Chạy; Pint; **Commit** theo nhóm.

### Task 14: Thử đột biến PR 2

Cùng quy trình Task 7.

| # | Đột biến | Test kỳ vọng đỏ |
|---|---|---|
| 1 | `viewRoster`/`viewForClass`: luôn allow / bỏ nhánh admin / vai trò rỗng coi là admin | Policy + Behavior (3 đột biến × 2 policy) |
| 2 | `viewRoster`: dùng `Student` thay vì `classIdsFor('teacher')` (teacher lớp khác lọt) | Policy (dữ liệu lớp khác thật) |
| 3 | `viewRoster`/`viewForClass`: bỏ lọc `status` phân công (`ended` lọt) | Policy |
| 4 | `viewRoster`/`viewForClass`: đổi message | Behavior |
| 5 | `AddClassMember`/`AssignTeacherToClass`/`CreateParentStudentRelationship`: bỏ kiểm tra `ALREADY_*` | Behavior (3 đột biến riêng) |
| 6 | Mọi Action audit (7): audit sau transaction | Rollback (7 đột biến riêng) |
| 7 | `AssignTeacherToClass`: đổi trạng thái lớp ngoài transaction / bỏ đổi trạng thái / đổi cả khi lớp `archived` | Rollback / Behavior |
| 8 | `EndTeacherClassAssignment`: bỏ đổi `active → preparing` / đổi khi còn teacher / đổi không điều kiện trạng thái lớp | Behavior (3 đột biến riêng) |
| 9 | `UpdateClassMember`: ghi `reason` vào bản ghi / ghi thêm field ngoài `valid_*` / bỏ so sánh `valid_to` với `valid_from` DB | Behavior (3 đột biến riêng) |
| 10 | `UpdateClassMember`: audit thiếu `reason` / `before` sau update | Behavior |
| 11 | `EndClassMember`/`EndParentStudentRelationship`: bỏ `end_reason` trong audit `reason` / bỏ `ended_by_user_id` | Behavior |
| 12 | `EndParentStudentRelationship`: bỏ `LinkAlreadyEnded` | Behavior |
| 13 | `MyChildrenQuery`: bỏ lọc `status` / thêm lọc `valid_to` (đổi hành vi) | Behavior (cả hai chiều) |
| 14 | `MyChildQuery`: bỏ lọc `status`; bỏ lọc `student_id`; trả liên kết của parent khác; đổi message | Behavior + OutsideHttp |
| 15 | `ListParentStudentRelationships`: bỏ một bộ lọc | Behavior |
| 16 | `GetClassMembers`: lấy member không lọc `active` | Behavior |
| 17 | `MembershipStatus`: thêm/bớt case | EnumCastsTest + Enum unit |
| 18 | Resource: bỏ/đổi tên một key | Behavior (key-set) |

- [ ] Ghi bảng kết quả vào báo cáo và `progress.md`.

### Task 15: Tài liệu và nghiệm thu PR 2

- [ ] **Docs:** `error-codes.md` thêm các mã PR 2; playbook xóa dòng nợ "Controller cũ đọc `active_role_code` thẳng từ session" phần UserManagement, bảng ảnh hưởng ClassScope (không đổi); `progress.md`: bullet "Kết quả Task 9b" tổng hợp 2 PR + "Việc kế tiếp: Task 9c `Assessment`"; `piclass-api/CLAUDE.md` mục Enum; bảng "Trạng thái thực hiện" của plan tổng `2026-10-18-be-architecture-refactor.md`: 9b → "Xong, đã merge".
- [ ] **Full suite**, Pint trên file đã đổi, `git log origin/main..HEAD`, **chưa push**.
- [ ] **Báo cáo cho Opus** như Task 8; **DỪNG.**

## Tiêu chí xong

- 30 controller cũ đã xóa (thư mục `app/Http/Controllers/Api/UserManagement` không còn), 30 controller + Request + Resource mới trong `app/Modules/UserManagement`, route trỏ đúng; `ArchitectureTest` R1-R8 xanh với `UserManagement` trong `MODULES`.
- Test đặc tả Task 1/9 xanh trước và sau refactor, nguyên văn (không ngoại lệ hành vi nào ngoài D5 mà HTTP không thấy); full suite xanh, số test ≥ baseline.
- 10 Action audit nằm trong transaction với dữ liệu, có test rollback; mọi 403/404 assert `code` + `message`.
- Bảng đột biến: không còn lỗi cố ý sống sót (hoặc sống sót được giải thích là tương đương).
- Tài liệu cập nhật (`error-codes`, `model-ownership`, `open-decisions` `ARCH-23`, playbook, `progress`); cả hai repo commit local, chưa push.

## Ghi chú thực thi (điền sau khi xong)

_(Để trống — người thực thi điền "Khác plan", "Kết quả" và "Sau review Opus" cho từng PR.)_
