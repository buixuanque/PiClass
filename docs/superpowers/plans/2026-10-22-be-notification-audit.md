# Task 9i — Refactor module `Notification` + `Audit` sang quy ước module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** 3 controller cũ (`ListMyNotifications`, `MarkNotificationRead` của `Notification`; `ListAuditLogs` của `Audit`) chuyển sang `app/Modules/{Notification,Audit}` theo `piclass-api/CLAUDE.md`; việc tìm người nhận thông báo rời `ClassScope` và listener của Attendance vào `Modules/Notification` (trả nợ playbook). Không đổi HTTP status, `error.code`, `error.message`, tập key JSON, hay tập người nhận của từng sự kiện. **Một PR, một lượt review Opus ở cuối.**

**Architecture:** `Modules/Notification/{Actions,Queries,Policies,Http,Providers}` (giữ `NotificationDispatcher` ở gốc module), `Modules/Audit/{Queries,Policies,Http,Providers}` (giữ `AuditLogger` ở gốc module). `NotificationRecipientsQuery` thay hai hàm static của `ClassScope` và hai đoạn query `User` inline của Attendance; 8 listener của Lesson/Assessment/ResultProgress/Attendance inject Query này. Không audit mới (spec §4 không liệt kê), không Event mới.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, PHPUnit 12, Pint. Container `piclass-local-api-1`.

**Spec / nguồn:** `piclass-api/CLAUDE.md`; `docs/architecture/backend-refactor-playbook.md` (bảng nợ dòng người nhận thông báo, literal role code); `docs/superpowers/specs/2026-10-15-notification-file-audit-api-design.md`; `docs/requirements/notification-rules.md` (`BR-NOTIF-001..007`), `file-and-audit-rules.md` (`BR-AUD-001..007`); `docs/architecture/model-ownership.md`, `error-codes.md`, `ARCH-22(d)(3)`; plan mẫu `2026-10-22-be-file.md`.

---

## Quy tắc cho người thực thi

Áp dụng nguyên văn quy tắc của plan 9h (`2026-10-22-be-file.md`): điểm mơ hồ nhỏ thì chọn phương án đơn giản nhất và ghi `Ruling:`; điểm đổi hành vi/bảo mật thì **DỪNG**; test đặc tả viết trên code cũ trước và không sửa kỳ vọng cho khớp; giá trị kỳ vọng trong test là chuỗi literal; commit local tiếng Anh kết thúc `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`; đột biến chỉ trên bản sao trong container, **copy cả `vendor`, không symlink**; comment tiếng Việt; thăm dò 500 không ghim 500; test đổi người đăng nhập giữa chừng trả 401 nên tách mỗi test một actor. **Không push/PR/merge khi chưa được user yêu cầu rõ.**

## Global Constraints

- Giữ nguyên: HTTP status, `error.code` (mã chung `NOT_FOUND`... của `abort()` cũ), `error.message`, tập key JSON (kể cả response trả nguyên Model), `event_key`/`event_type`/`title`/`body`/`context` của từng thông báo, và **tập người nhận** của từng sự kiện (kể cả chỗ không xét `valid_to`, `ARCH-22(d)(3)`).
- Baseline: số test pass đo được ở bước chuẩn bị (kỳ vọng ≈ 2115).
- Thông báo vẫn chạy qua queue sau commit (không đụng `ShouldQueueAfterCommit`/`afterCommit` của listener).
- Mọi case 403/404 assert cả `error.code` lẫn `error.message`. Pint chỉ `--test` trên file branch đổi.
- Không thêm migration; không đổi `config/`.

### Git

BE: `git fetch origin && git checkout -b refactor/notification-audit origin/main && git branch --unset-upstream`. Docs: `docs/be-notification-audit` từ `origin/main`.

## Quyết định đã chốt

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | **Một PR**, 3 nhóm tuần tự: **A** người nhận (nội bộ, không HTTP), **B** Notification HTTP (2 endpoint), **C** Audit HTTP (1 endpoint) | Nhỏ; A là nợ playbook gắn với module này | Tách A thành PR riêng |
| D2 | **`Modules/Notification/Queries/NotificationRecipientsQuery`** với 4 method giữ đúng tập người nhận cũ: `studentsAndTheirParents(iterable $studentIds)` (= `ClassScope::studentAndParentRecipients`: Student luôn nhận, Parent chỉ khi `ParentStudentRelationship.status = active`), `classStudentsAndTeachers(string $classId)` (= `activeRecipientsFor`: `ClassMember` và `TeacherClassAssignment` `status = active`, **không xét `valid_to`**), `classTeachers(string $classId)` (query inline của `SendAbsenceExplanationSubmittedNotification`), `studentAndLinkedParent(string $studentId, string $parentId)` (đoạn inline của `SendAbsenceExplanationReviewedNotification`: Student + Parent đã lọc bằng `ClassScope::parentCoversStudent`). Xóa hai hàm static khỏi `ClassScope` (và test của chúng chuyển theo). Literal `'active'` → `MembershipStatus::Active->value` (khai `UserManagement/MembershipStatus` vào `SHARED_ENUMS['Notification']`) | Playbook: dời việc tìm người nhận vào `Modules/Notification`; mô hình "module sở hữu người nhận" | Giữ ở `ClassScope` |
| D3 | **Listener** của Lesson (4), Assessment (1), ResultProgress (1), Attendance (2) inject `NotificationRecipientsQuery`; chỉ đổi nguồn người nhận, giữ nguyên `eventKey/eventType/title/body/context`. Listener ở module nào vẫn ở module đó | Module phát event sở hữu nội dung thông báo | Gom listener vào Notification |
| D4 | **Notification HTTP:** `MyNotificationsQuery::handle(User, ?string $read): Collection` (lọc `read=true` → `read_at` không null, `read=false` → null, giá trị khác bỏ qua; mới nhất trước; nạp `context`; cột `id,event_type,title,body,created_at,read_at`); `MarkNotificationRead::handle(Notification): Notification` (đặt `read_at = now()` nếu đang null, idempotent, không audit); `NotificationPolicy@markRead` (chỉ người nhận, ngược lại `denyAsNotFound(DenialMessage::NOT_FOUND)`). `ListMyNotificationsRequest::rules()` **rỗng** (thêm validate đổi 200 → 422). Resource: `NotificationListResource` `{data:[{id,event_type,title,body,created_at,read_at,context:{type,id}|null}]}` (hàm `contextFor` chuyển vào Resource: duyệt `assessment_id`, `lesson_session_id`, `result_id`, `schedule_id` theo thứ tự đó, `type` = tên cột bỏ `_id`); `NotificationResource` `{notification:<Model>}` (tập key Model đo trên code cũ rồi ghim) | Giữ shape; BR-NOTIF-007: chỉ trả tham chiếu context | Validate `read` (đổi hành vi) |
| D5 | **Audit HTTP:** `AuditLogPolicy@viewAny` (Admin qua; Teacher có hồ sơ Teacher qua; ngược lại `denyAsNotFound`, đúng `abort_unless($teacher, 404)` cũ; vai trò đọc qua `ActiveRole`, không đọc session); `AuditLogsQuery::handle(User, array $filters): Collection` (bộ lọc `entity_type`, `entity_id`, `action` chỉ áp khi giá trị truthy như cũ; Admin xem hết; còn lại `whereIn('correlation_key', ClassScope::classIdsFor($user, 'teacher'))` — bằng `Teacher::activeClassIds()` cũ nên giữ nguyên cả việc không xét `valid_from`, `ARCH-23(i)`; `occurred_at` giảm dần). `ListAuditLogsRequest::rules()` rỗng. Resource `AuditLogListResource` `{data:[<AuditLog Model>]}` (đủ cột kể cả `before_data`/`after_data`) | Giữ shape và phạm vi | Pagination (đổi hành vi) |
| D6 | **Không tạo Enum** cho `event_type` hay `AuditLog.action`: giá trị mở rộng theo từng sự kiện/Action, không có CHECK DB; không cast thêm Model | YAGNI; Enum phản chiếu CHECK | Enum `NotificationEventType` |
| D7 | **Ngoài phạm vi (ghi `ARCH-31`):** (a) hai endpoint danh sách không phân trang (thông báo của người dùng lâu năm, audit toàn hệ thống với Admin); (b) người nhận thông báo Attendance/Lesson không xét `valid_to` (giữ nguyên, `ARCH-22(d)(3)`); (c) `GET /audit-logs` của Teacher chỉ thấy log có `correlation_key` là lớp họ đang dạy — log `absence_explanation.review` không có `correlation_key` nên Teacher không thấy (`ARCH-22(d)(4)`); (d) `?action[]=x`/`?entity_id[]=x` (bộ lọc mảng) — thăm dò, không ghim nếu 500; (e) `NotificationDispatcher::firstOrCreate` + `NotificationContext::create` không trong một transaction, hai tiến trình đồng thời cùng `event_key` có thể để lại thông báo chưa có context (khóa duy nhất `(recipient_user_id, event_key)` chỉ chống trùng dòng) | Refactor không đổi hành vi | Sửa luôn |

## Bảng endpoint (3)

| # | Nhóm | Endpoint | Middleware | Authorize | Action / Query | Resource |
|---|---|---|---|---|---|---|
| 1 | B | `GET /me/notifications` | `auth` | `true` (chỉ dữ liệu của chính mình) | `MyNotificationsQuery` | `NotificationListResource` |
| 2 | B | `PATCH /notifications/{notification}/read` | `auth` | `markRead` | `MarkNotificationRead` | `NotificationResource` |
| 3 | C | `GET /audit-logs` | `role:admin,teacher` | `viewAny` | `AuditLogsQuery` | `AuditLogListResource` |

## Review Focus (cho Opus)

1. **Tập người nhận không đổi:** mỗi sự kiện (assessment giao, kết quả công bố, đổi/hủy lịch, đổi/hủy buổi học, đơn xin phép nộp/duyệt) giữ đúng người nhận cũ — Parent chỉ khi liên kết `active`, Teacher/Student chỉ khi `active`, không nhận trùng, không nhận thừa; không ai bị mất thông báo.
2. **Phân quyền:** người dùng chỉ đánh dấu đã đọc thông báo của chính mình (của người khác → 404 cùng message với id không tồn tại); Teacher chỉ thấy audit log của lớp mình đang dạy; Teacher không có hồ sơ → 404; không còn đọc session ngoài `ActiveRole`.
3. **Giữ nguyên hành vi:** key JSON từng response (Model nhúng), thứ tự, bộ lọc, `context` null/loại; `read_at` không bị ghi lại khi đã đọc.
4. **Queue/after-commit:** listener vẫn `afterCommit` và queued; đổi nguồn người nhận không làm truy vấn người nhận chạy ngoài ngữ cảnh queue (worker không có session/`ActiveRole`).
5. **Rule kiến trúc:** `Notification` và `Audit` vào `MODULES`; file gốc `NotificationDispatcher`/`AuditLogger` không vi phạm R1–R8.
6. Bảng đột biến: cái nào sống sót và test bổ sung.

## Danh sách file được phép đụng

| File | Trách nhiệm |
|---|---|
| `tests/Feature/Notification/{NotificationEndpointsBehaviorTest,NotificationPolicyTest,NotificationRecipientsTest,NotificationBoundaryCasesTest}.php`, `tests/Feature/Audit/{AuditLogsBehaviorTest,AuditLogPolicyTest,AuditLogsQueryTest}.php` + `Concerns/*Fixtures.php` (mới); `tests/Feature/{Notification,Audit}/*` cũ (giữ, sửa chỉ khi đổi namespace) | Đặc tả hành vi, Policy, Query, người nhận, ca biên |
| `app/Modules/Notification/{Actions,Queries,Policies,Http,Providers}/**`, `app/Modules/Audit/{Queries,Policies,Http,Providers}/**` | Module |
| `app/Modules/{Lesson,Assessment,ResultProgress,Attendance}/Listeners/*` (8 file) | Đổi nguồn người nhận |
| `app/Support/ClassScope.php`, test của hai hàm bị xóa | Xóa `studentAndParentRecipients`, `activeRecipientsFor` |
| `bootstrap/providers.php`, `routes/api.php`, `tests/Architecture/ArchitectureTest.php` | Đăng ký, route, rule |
| `app/Http/Controllers/Api/{Notification,Audit}/*` (xóa 3) | — |
| `piclass-api/CLAUDE.md`; docs: playbook, `error-codes.md` (nếu có mã mới), `model-ownership.md`, `open-decisions.md` (`ARCH-31`), `progress.md`, plan tổng | Ghi nhận |

---

### Task 1: Test đặc tả hành vi trên code cũ
- [ ] `NotificationEndpointsBehaviorTest`: list (key đầy đủ mỗi phần tử, `context` đủ bốn loại + null, bộ lọc `read=true|false|khác`, thứ tự mới nhất trước, chỉ thông báo của mình, rỗng), mark read (key Model đầy đủ, đặt `read_at`, idempotent không đổi `read_at` lần hai, của người khác 404 `NOT_FOUND` `Request failed.`, id sai/không phải uuid 404, chưa đăng nhập 401).
- [ ] `AuditLogsBehaviorTest`: Admin thấy hết, key đầy đủ mỗi log, từng bộ lọc và kết hợp, filter rỗng/`0` bị bỏ qua, thứ tự `occurred_at` giảm dần; Teacher chỉ thấy log có `correlation_key` là lớp đang dạy (không thấy log lớp khác, log không có `correlation_key`, lớp phân công đã kết thúc); Teacher không có hồ sơ → 404; Student/Parent 403 middleware.
- [ ] `NotificationRecipientsTest` (qua event thật hoặc listener gọi trực tiếp): ghim tập người nhận của **từng** sự kiện bằng fixture có đủ nhiễu (Parent liên kết đã kết thúc, ClassMember/phân công đã kết thúc, người lớp khác, Parent của học sinh khác, user có cả hai hồ sơ) — kết quả đúng như code cũ; ghi nhận chỗ không xét `valid_to`.
- [ ] Thăm dò 500: `?read[]=x`, `?action[]=x`, `?entity_id=abc`; không ghim 500.
- [ ] Chạy trên code cũ, Pint. **Commit:** `test: pin Notification and Audit endpoint and recipient behavior before the module refactor`.

### Task 2: Nhóm A — `NotificationRecipientsQuery` + chuyển listener
- [ ] Tạo Query 4 method (D2), `NotificationServiceProvider` (nếu cần binding), sửa 8 listener (D3), xóa hai hàm khỏi `ClassScope`; test Task 1 vẫn xanh nguyên văn. Full suite xanh. **Commit:** `refactor: move notification recipient lookup into the Notification module`.

### Task 3: Nhóm B — Notification HTTP
- [ ] `NotificationPolicy`, `MarkNotificationRead`, `MyNotificationsQuery`, 2 Request, 2 Resource, 2 Controller, Provider, `bootstrap/providers.php`, route; xóa `app/Http/Controllers/Api/Notification`. **Commit.**

### Task 4: Nhóm C — Audit HTTP
- [ ] `AuditLogPolicy`, `AuditLogsQuery`, Request, Resource, Controller, Provider, route; xóa `app/Http/Controllers/Api/Audit`. **Commit.**

### Task 5: Rule kiến trúc
- [ ] `'Notification'`, `'Audit'` vào `MODULES`; `SHARED_ENUMS['Notification'] = ['UserManagement/MembershipStatus']`, `SHARED_ENUMS['Audit'] = []`; không thêm `KNOWN_*` ngoài dự kiến. **Commit.**

### Task 6: Test phân quyền, Query, ca biên
- [ ] `NotificationPolicyTest`, `AuditLogPolicyTest` (admin; chủ thông báo; người khác; vai trò rỗng; Teacher có/không hồ sơ), `AuditLogsQueryTest` (ngoài HTTP), ca biên bộ lọc/`read`. **Commit.**

### Task 7: Thử đột biến
- [ ] Bản sao `/tmp/mut` trong container (copy cả vendor); bộ tối thiểu: từng điều kiện lọc người nhận (`status`, bỏ Parent, bỏ Student, bỏ Teacher, đổi `parentCoversStudent`), Policy (always allow / bỏ chủ / rỗng), Query (bỏ từng bộ lọc, đổi thứ tự, bỏ `context`), Action (ghi đè `read_at` khi đã đọc), Resource (đổi/bỏ từng key), `contextFor` (đổi thứ tự cột). Sống sót → bổ sung test rồi chạy lại.

### Task 8: Tài liệu và nghiệm thu
- [ ] Docs (`model-ownership.md`; `open-decisions.md` `ARCH-31` theo D7; playbook: xóa các dòng nợ đã trả (người nhận thông báo, session ở `{Audit,Notification}`, literal role code ở `Audit`), thêm nợ mới; `progress.md`; plan tổng 9i; `CLAUDE.md`), full suite, Pint, Opus review (D2, D5), sửa Critical/Important. **Không push** cho tới khi user yêu cầu.

## Tiêu chí xong

- 3 controller cũ xóa; hai hàm người nhận khỏi `ClassScope`; `ArchitectureTest` R1–R8 xanh với `Notification` và `Audit`; test đặc tả xanh trước/sau; đột biến không còn sống sót (hoặc giải thích tương đương); tài liệu cập nhật.
