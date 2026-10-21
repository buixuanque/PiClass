# Task 9c — Refactor module `Assessment` sang quy ước module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** 21 controller của `Assessment` chuyển từ `app/Http/Controllers/Api/Assessment/` sang `app/Modules/Assessment/` theo `piclass-api/CLAUDE.md` (FormRequest + Policy + Action/Query + Resource), dùng `ActiveRole`, Enum cho `status`/`type`, 4 Action có audit bắt buộc (`publish`/`close`/`reopen`/`cancel`) đưa audit vào cùng transaction với thay đổi dữ liệu (`DEC-AUD-005`), thông báo "được giao bài" chạy qua Event + Listener sau commit; không đổi HTTP status, `error.code`, `error.message`, key JSON, giá trị chuỗi lưu DB — trừ các ngoại lệ ghi ở "Quyết định đã chốt" (D5, D9). **Một PR, một lượt review Opus ở cuối** (playbook, mục "Phân vai model").

**Architecture:** Module `app/Modules/Assessment/{Http,Actions,Queries,Policies,Enums,Events,Listeners,Exceptions,Providers}`. `AssessmentPolicy` thay mười chỗ lặp `abort_unless($activeRole === 'admin' || created_by === user, 403, ...)` và ba chỗ lặp khối "phạm vi xem" (404). `StudentAssessmentSync` (đang ở `app/Support`, chỉ Assessment dùng) chuyển vào module thành Action tiêm được. Test đặc tả hành vi viết trước trên code cũ, thử đột biến bắt buộc ở cuối.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, PHPUnit 12, Pint. Container `piclass-local-api-1` (Docker Compose ở `piclass-infra/local`).

**Spec / nguồn:** `piclass-api/CLAUDE.md`; `docs/architecture/backend-refactor-playbook.md`; `docs/superpowers/specs/2026-10-15-question-bank-assessment-api-design.md` (phần Assessment); `docs/superpowers/specs/2026-10-15-notification-file-audit-api-design.md` §4 (4 action audit của Module 3, `reason` không bắt buộc ở tầng Audit); `docs/requirements/*` (`DEC-AS-*`, `DEC-AUD-001/003/004/005`); `docs/access-control/*`; `docs/architecture/model-ownership.md`, `error-codes.md`, `open-decisions.md` (`ARCH-14`); plan mẫu `2026-10-21-be-user-management.md` (Policy/Request/Resource/Action, đột biến), `2026-10-21-be-attendance.md` (Event + Listener).

---

## Quy tắc cho người thực thi (đọc trước khi làm bất cứ gì)

Áp dụng **nguyên văn** quy tắc 1–8 của plan 9b (`2026-10-21-be-user-management.md`, mục "Quy tắc cho người thực thi"): gặp điểm mơ hồ/quyết định thiếu thì DỪNG và báo; test đặc tả fail trên code cũ thì DỪNG; không đụng file ngoài danh sách; giá trị kỳ vọng trong test là chuỗi literal; commit local (tiếng Anh, kết thúc `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`), **không push/PR/merge/xóa branch**; không worktree, đột biến chỉ trên bản sao `/tmp/mut` trong container và **không chạy song song với full suite**; comment tiếng Việt; thăm dò 500 không ghim 500 (quy tắc 8). Thêm riêng cho plan này:

9. **Bẫy mã lỗi.** `abort(409|422, msg)` của code cũ được handler render thành `error.code` **`CONFLICT`** (409) và **`ERROR`** (422, mã mặc định), `abort(403|404)` thành `FORBIDDEN`/`NOT_FOUND`. Giữ NGUYÊN các mã này (đừng "đẹp hóa" thành mã mới); test đặc tả ghim cả `code` lẫn `message`.
10. **Cast làm gãy so sánh chuỗi ở module khác.** Cast `Assessment.status/type`, `AssessmentVersion.status`, `StudentAssessment.status` làm `=== 'published'`... gãy âm thầm. Danh sách đã rà ở "Bảng ảnh hưởng"; `grep` thấy chỗ đọc thuộc tính ngoài danh sách: DỪNG.

## Global Constraints

- Giữ nguyên hành vi: HTTP status, `error.code`, `error.message`, tập key JSON (kể cả các response trả nguyên Model Eloquent: tập thuộc tính phải **bằng đúng** cũ), giá trị chuỗi trong JSON/DB/audit — ngoại lệ D5, D9.
- Baseline: **1254 test pass** (suite ~40 giây). Mọi task kết thúc với 0 fail và số test = 1254 + số test mới đã thêm.
- Pint chỉ chạy `--test` trên các file `.php` branch này đổi (`routes/api.php` đã lỗi sẵn trên `main`, bỏ qua).
- Mọi case 403/404 trong test mới assert **cả** `error.code` lẫn `error.message`.
- Thứ tự `authorize → validate → state` của code cũ giữ nguyên, nhưng nhiều endpoint **kiểm quyền (403) trước validate (422)** và kiểm trạng thái (409) **trước** validate (xem bảng endpoint, cột "Thứ tự cũ"): ghim bằng test trên code cũ rồi giữ đúng.
- Vai trò đang hoạt động đọc qua `ActiveRole` (Policy/Query), không đọc session.

### Chạy test (macOS, Docker Desktop)

```bash
cd /Users/que-bui/Workspaces/PiClass/piclass-infra/local
docker compose exec -T api php artisan test tests/Feature/Assessment
docker compose exec -T api bash -lc 'vendor/bin/pint --test <files>'
docker compose exec -T api php artisan test > "<scratchpad>/full.log" 2>&1
```

### Git

BE: `cd piclass-api && git fetch origin && git checkout -b refactor/assessment origin/main && git branch --unset-upstream`. Docs: `docs/be-assessment` (branch của plan này).

## Quyết định đã chốt

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | **Một PR**, 21 controller chia 3 nhóm làm tuần tự: **A** vòng đời bài (10: `CreateAssessment`, `UpdateAssessment`, `AddAssessmentQuestion`, `UpdateAssessmentQuestion`, `RemoveAssessmentQuestion`, `CreateAssessmentVersion`, `PublishAssessment`, `CloseAssessment`, `ReopenAssessment`, `CancelAssessment`), **B** giao bài (5: `AddAssessmentAudience`, `ListAssessmentAudience`, `EndAssessmentAudience`, `ListAssessmentStudents`, `UpdateAssessmentStudent`), **C** xem (6: `ListAssessments`, `GetAssessment`, `ListMyAssessments`, `GetMyAssessment`, `ListChildAssessments`, `GetChildAssessment`) | 926 dòng, nhỏ hơn PR 1 của 9b; điểm review giữa chừng của playbook ("xong nhóm có audit và Policy") đạt ngay sau nhóm A+B, nhưng review Opus một lần ở cuối | Chia 2 PR |
| D2 | **Enum** (`Modules/Assessment/Enums`): `AssessmentType` (`assignment, quiz, exam`), `AssessmentStatus` (`draft, published, closed, cancelled, archived`), `AssessmentVersionStatus` (`draft, published, withdrawn`), `StudentAssessmentStatus` (`assigned, withdrawn, completed`); cast `Assessment.type/status`, `AssessmentVersion.status`, `StudentAssessment.status`. `AttemptScorePolicy` đã có. Request dùng `Rule::enum` thay `in:` ở `type` (giá trị đầu vào **không** được nới: `in:assignment,quiz,exam` ⇔ `Rule::enum(AssessmentType)` cùng thông điệp lỗi — kiểm bằng test) | Enum phản chiếu toàn bộ giá trị CHECK | Chỉ cast `status` |
| D3 | **Sửa các chỗ ngoài module bị cast** (đúng các dòng này, không hơn): `SubmissionGrading/Actions/StartAttempt.php:33-34` (`$sa->status !== StudentAssessmentStatus::Assigned`, `$assessment->status !== AssessmentStatus::Published`), `SubmissionGrading/Http/Controllers/GetAttemptController.php:20` (`type === AssessmentType::Exam`), `SubmissionGrading/Actions/ReissueAttempt.php:34` (`type === AssessmentType::Assignment`). Mục `KNOWN_LITERALS` của `StartAttempt` (`'published'`) **xóa**; `SHARED_ENUMS['SubmissionGrading']` thêm `Assessment/AssessmentStatus`, `Assessment/AssessmentType`, `Assessment/StudentAssessmentStatus` (module import Enum của module khác phải khai báo). `ResultProgress/Queries/StudentProgressQuery.php:28` (`where('status','!=','cancelled')` query builder) **không sửa** | Giữ SubmissionGrading chạy đúng sau cast | Để literal ở SubmissionGrading |
| D4 | **`AssessmentPolicy`** (`Gate::policy(Assessment::class, ...)` ở `AssessmentServiceProvider`). Quyền quản lý: ability riêng cho từng thông điệp 403 cũ (**giữ chữ**): `update` ('Chỉ người tạo Assessment hoặc Admin được sửa.' — dùng chung cho Update/AddQuestion/UpdateQuestion/RemoveQuestion/UpdateStudent), `publish` (…được publish.), `createVersion` (…được tạo version mới.), `assign` (…được giao bài.), `withdrawAudience` (…được rút lại audience.), `close` (…được đóng bài.), `reopen` (…được mở lại bài.), `cancel` (…được hủy bài.); điều kiện chung: `isAdmin()` **hoặc** `created_by_user_id === $user->id` (người tạo qua với **mọi** vai trò, đúng cũ), còn lại deny 403. Quyền xem (404, không lộ sự tồn tại): ability `view(User, Assessment)`: admin allow; còn lại allow khi là người tạo, hoặc `ClassScope::teacherCoversAssessment` (đúng cũ: audience của version nào đó trỏ vào lớp mình đang dạy hoặc Course mình sở hữu); ngoài ra `Response::denyAsNotFound` (404 `Request failed.`). **Vai trò rỗng không là admin** (default-deny) nên ngoại lệ D5 | Mười chỗ lặp; quy ước Policy theo đối tượng | Một ability `manage` (mất thông điệp riêng từng endpoint) |
| D5 | **Ngoại lệ hành vi có chủ đích #1:** vai trò đang hoạt động **rỗng** (job/console, chưa set) không còn được coi là admin (HTTP không thấy vì route `role:admin,teacher`). Ghi vào báo cáo Opus | Quy ước default-deny (cùng D5 của 9b) | Giữ "rỗng = qua" |
| D6 | **`assign` có phạm vi theo input**: `AddAssessmentAudience` kiểm quyền người tạo (403) → validate (422) → "đúng một đích" (422 code `ERROR`) → **phạm vi đích** (403, vai trò khác admin: lớp phải nằm trong phân công, Course phải do mình sở hữu hoặc `owner_teacher_id` null, học viên phải là thành viên `active` của lớp mình) → ghi. Phạm vi đích là ability `assignTarget(User, Assessment, array $target)` gọi trong hook sau-validate của `AddAssessmentAudienceRequest` (mẫu `CreateAbsenceExplanationRequest::withValidator` của Attendance); "đúng một đích" cũng ở hook đó, **trước** phạm vi. Thông điệp 403/422 giữ nguyên chữ | Giữ thứ tự 403 → 422 → 422 → 403 | Chuyển hết vào Action (Action đọc vai trò) |
| D7 | **Exception thay `abort()`** (Domain exception, giữ `error.code` cũ theo quy tắc 9, thông điệp cố định): 409 `CONFLICT`: `AssessmentNotDraft`-nhóm (mỗi thông điệp một class: `Chỉ thêm câu hỏi khi bản phát hành còn draft.` / `Chỉ sửa câu hỏi khi bản phát hành còn draft.` / `Chỉ bỏ câu hỏi khi bản phát hành còn draft.` / `Bản phát hành hiện tại đã publish, không sửa trực tiếp được.` / `Bản phát hành không còn ở trạng thái draft.`), `AssessmentTerminated` (`…không publish được.` / `…không tạo version mới được.`), `DraftAlreadyOpen` (`Đã có bản draft đang mở.`), `AssessmentNotPublished`-kiểu cho close (`Chỉ đóng bài khi Assessment đang published.`), `AssessmentNotClosed` (`Chỉ mở lại Assessment đang closed.`); 422 `ERROR`: `EmptyAssessment` (`Không thể publish Assessment chưa có câu hỏi nào.`), `InvalidAudienceTarget` (`Chọn đúng một trong class_id, student_id, hoặc course_id.`), `InvalidReopenTarget` (`Chọn đúng một trong student_ids, class_id, hoặc course_id.`), `LessonSessionOnlyForAssignment` (`Chỉ Assignment được liên kết LessonSession.`); 403 `FORBIDDEN`: ba thông điệp phạm vi đích của D6; 404 `NOT_FOUND` `Request failed.`: câu hỏi/audience không thuộc bài (`abort_unless(..., 404)`). Người thực thi gom class theo nhóm hợp lý (không bắt buộc đúng 1 class/thông điệp, miễn `code`/`status`/`message` y hệt, có unit test bảng). Thêm vào `error-codes.md` bảng "mã chung được giữ cho tương thích" | Quy ước domain exception; không đổi contract | Mã mới (`DRAFT_REQUIRED`...) — đổi contract |
| D8 | **4 Action có audit** (`PublishAssessment`, `CloseAssessment`, `ReopenAssessment`, `CancelAssessment`): một `DB::transaction` gồm khóa dòng `assessments` (`lock('for no key update')`, vì bảng con `assessment_versions`/`student_assessments` giữ FOR KEY SHARE; bài học deadlock của 9b) → kiểm tra trạng thái → ghi dữ liệu → `AuditLogger::record` (cùng `action`/`entityType`/`after`/`reason` như cũ, `after.status` ghi `->value`). `PublishAssessment` đọc/ghi `AssessmentQuestion` snapshot + version + assessment cùng transaction; `CancelAssessment` ghi `cancellation_reason` của version hiện tại + `status`. Audit lỗi hủy cả thay đổi, có test rollback. Các Action vòng đời không audit (`CreateAssessment`, `UpdateAssessment`, `AddAssessmentQuestion`, `UpdateAssessmentQuestion`, `RemoveAssessmentQuestion`, `CreateAssessmentVersion`, `AddAssessmentAudience`, `EndAssessmentAudience`, `UpdateAssessmentStudent`) bọc `DB::transaction` khi ghi ≥ 2 bảng / ≥ 2 dòng (Create, CreateVersion, Publish, Reopen, AddAudience, EndAudience) | `DEC-AUD-005`; spec §4 chỉ liệt kê 4 action (không tự thêm audit, `DEC-AUD-005`) | Audit sau commit |
| D9 | **Ngoại lệ hành vi có chủ đích #2:** `GET /me/children/{studentId}/assessments*` khi tài khoản Parent **chưa có hồ sơ Parent** hôm nay NPE → 500; mới: kiểm liên kết qua `ClassScope::parentCoversStudent` (false khi không có hồ sơ) → 404 `Request failed.` như học viên không phải con. Không ghim 500 cũ. Ghi vào báo cáo Opus | Không ghim lỗi 500 | Giữ NPE |
| D10 | **Serialization.** Nhiều response trả **nguyên Model** (`'audience' => $audience`, `'current_version' => $version`, `'assessment_question' => $q`, `'data' => $paginator->items()`). Resource dựng mảng chứa Model đó (JSON hóa y hệt) với `$wrap = null`; test ghim **tập key đầy đủ** của từng Model (kể cả `created_at`, `*_id`, Enum thành chuỗi). 201/200 tự nhiên (model vừa tạo → 201; Resource bọc model đã tồn tại → 200) — endpoint cũ trả 200 mà tạo/ghi bản ghi mới thì ép lại; `RemoveAssessmentQuestion` trả `{ "deleted": true }` 200 (không 204) | Giữ shape | Chuẩn hóa response |
| D11 | **Query:** `ListAssessmentsQuery::handle(User, array $filters, int $perPage): LengthAwarePaginator` (đúng cách lọc cũ: phạm vi người tạo/lớp/Course cho vai trò không-admin, `status`, `type`, `class_id`, `course_id` bằng nhau; `per_page` mặc định 20); `ListAssessmentsRequest::rules()` **rỗng** (thêm validate sẽ đổi 200 → 422, như D15 của 9b); `StudentAssessmentMetadataQuery::handle(string $studentId): Collection` thay `ListMyAssessmentsController::metadataFor` (static; ẩn `draft`, giữ đúng map 9 key). Response list `{ data, meta:{total,current_page,last_page} }` không `links` | Giữ shape; xóa static gọi chéo controller | `ResourceCollection` mặc định (thêm `links`) |
| D12 | **`StudentAssessmentSync` → `Modules/Assessment/Actions/StudentAssessmentSync`** (tiêm được; giữ 3 method, thay literal bằng Enum, dùng `ClassMember`/`CourseEnrollment` chỉ đọc); xóa `app/Support/StudentAssessmentSync.php`. Test nào gọi static thì đổi sang `app(StudentAssessmentSync::class)->...` (chỉ đổi cách gọi). **`ReopenAssessment`** ghi `StudentAssessment` bằng `update()` hàng loạt: giữ trong Action (cùng module sở hữu) | Chỉ Assessment dùng; R5 (DI) | Giữ ở `Support` |
| D13 | **Thông báo "được giao bài"**: `AddAssessmentAudience` gửi Event `AssessmentAssigned(string $assessmentId, array $studentIds)` **sau commit** chỉ khi `Assessment.status = published` (đúng cũ: draft không báo); Listener `SendAssessmentAssignedNotification` (`ShouldQueue`, `afterCommit`) gọi `NotificationDispatcher::dispatch` với đúng `eventKey` (`assessment:{id}:assigned`), `eventType` (`assessment_assigned`), `title`, `body`, `context`, người nhận `ClassScope::studentAndParentRecipients($studentIds)`. Lỗi gửi thông báo không làm hỏng việc giao bài. Sau merge phải restart queue worker (đã ghi ở `progress.md`) | Quy ước "Notification" của CLAUDE.md; mẫu Attendance | Gọi dispatcher trong Action |
| D14 | **Khóa dòng** (`lock('for no key update')` trên `assessments`) cũng đặt ở các Action ghi dưới điều kiện `draft` (`UpdateAssessment`, `AddAssessmentQuestion`, `UpdateAssessmentQuestion`, `RemoveAssessmentQuestion`, `CreateAssessmentVersion`) để một request thêm câu hỏi không chen vào bản vừa được `PublishAssessment` chốt; `AddAssessmentQuestion` tính `position` trong cùng transaction | Chống race publish ↔ sửa; test kiểm câu SQL có khóa như 9b | Không khóa (giữ nguyên race cũ) |
| D15 | **Sở hữu Model / R4 / R8.** Mọi ghi vào `Assessment`/`AssessmentVersion`/`AssessmentQuestion`/`AssessmentAudience`/`StudentAssessment` là của Assessment (đúng `model-ownership`); đọc `QuestionVersion`, `ClassMember`, `CourseEnrollment`, `Course` chỉ đọc. Không `KNOWN_VIOLATIONS` mới dự kiến; R8: literal `'published'`/`'draft'`... phải hết trong module; ngoại lệ không nằm trong danh sách D3: DỪNG. Hàng "Assessment" ở bảng audit của `model-ownership.md` đổi sang tên Action | Đúng ranh giới | — |
| D16 | **Ngoài phạm vi (ghi lại, không làm):** `ARCH-14` (đồng bộ `StudentAssessment` khi học viên vào lớp/Course **sau** khi gắn audience) là đổi hành vi — giữ `[CẦN QUYẾT ĐỊNH]`, ghi lại là còn mở sau 9c, user chốt rồi làm PR riêng. Ruling cũ "không báo khi sửa bản draft" (comment trong `UpdateAssessmentController`) giữ nguyên | Refactor không đổi hành vi | Sửa luôn |

### Danh sách Enum

| Enum | Namespace | Giá trị | Cột được cast |
|---|---|---|---|
| `AssessmentType` | `App\Modules\Assessment\Enums` | `assignment, quiz, exam` | `Assessment.type` |
| `AssessmentStatus` | idem | `draft, published, closed, cancelled, archived` | `Assessment.status` |
| `AssessmentVersionStatus` | idem | `draft, published, withdrawn` | `AssessmentVersion.status` |
| `StudentAssessmentStatus` | idem | `assigned, withdrawn, completed` | `StudentAssessment.status` |

## Bảng endpoint (21)

"Thứ tự cũ" = thứ tự kiểm tra của code cũ, ghim bằng test đặc tả và giữ nguyên. `Q` = tên Policy ability / Query.

| # | Nhóm | Endpoint | Authorize | Thứ tự cũ | Action / Query | Audit | Resource (shape giữ nguyên) |
|---|---|---|---|---|---|---|---|
| 1 | A | `POST /assessments` | `true` (route `role:admin,teacher`) | validate → `LessonSessionOnlyForAssignment` 422 | `CreateAssessment` | — | `{assessment:{id,type,status,current_version}}` 201 |
| 2 | A | `PATCH /assessments/{a}` | `update` | 403 → 409 (version không draft) → validate | `UpdateAssessment` | — | `{assessment:{id,current_version}}` |
| 3 | A | `POST /assessments/{a}/questions` | `update` | 403 → 409 → validate → `findOrFail` | `AddAssessmentQuestion` | — | `{assessment_question}` 201 |
| 4 | A | `PATCH …/questions/{q}` | `update` | 403 → 409 → 404 (câu hỏi không thuộc version) → validate | `UpdateAssessmentQuestion` | — | `{assessment_question}` |
| 5 | A | `DELETE …/questions/{q}` | `update` | 403 → 409 → 404 | `RemoveAssessmentQuestion` | — | `{deleted:true}` 200 |
| 6 | A | `POST …/versions` | `createVersion` | 403 → 409 (đã hủy/lưu trữ) → 409 (draft đang mở) | `CreateAssessmentVersion` | — | `{assessment:{id,current_version(+questions)}}` 201 |
| 7 | A | `POST …/publish` | `publish` | 403 → 409 → 409 → 422 (không câu hỏi) | `PublishAssessment` | `assessment.publish`, `Assessment`, `after:{version_id,status}` | `{assessment:{id,status,current_version(+questions)}}` |
| 8 | A | `POST …/close` | `close` | 403 → 409 | `CloseAssessment` | `assessment.close`, `after:{status}` | `{assessment:{id,status}}` |
| 9 | A | `POST …/reopen` | `reopen` | 403 → 409 → validate → 422 (đúng một đích) | `ReopenAssessment` | `assessment.reopen`, `after:{status,student_ids}` | `{assessment:{id,status}}` |
| 10 | A | `POST …/cancel` | `cancel` | 403 → validate (`reason`) | `CancelAssessment` | `assessment.cancel`, `after:{status}`, `reason` | `{assessment:{id,status}}` |
| 11 | B | `POST …/audience` | `assign` + `assignTarget` (D6) | 403 → validate → 422 → 403 (phạm vi) | `AddAssessmentAudience` (+ Event D13) | — | `{audience}` 201 |
| 12 | B | `GET …/audience` | `view` | 404 | Eloquent | — | `{data:[audience]}` |
| 13 | B | `DELETE …/audience/{aud}` | `withdrawAudience` | 403 → 404 (audience không thuộc bài) | `EndAssessmentAudience` | — | `{audience}` 200 |
| 14 | B | `GET …/students` | `view` | 404 | Eloquent | — | `{data:[student_assessment]}` |
| 15 | B | `PATCH …/students/{studentId}` | `update` | 403 → `firstOrFail` 404 → validate | `UpdateAssessmentStudent` | — | `{student_assessment}` |
| 16 | C | `GET /assessments` | `true` | — | `ListAssessmentsQuery` (D11) | — | `{data,meta}` |
| 17 | C | `GET /assessments/{a}` | `view` | 404 | Eloquent | — | `{assessment:{id,type,status,created_by_user_id,current_version(+questions)}}` |
| 18 | C | `GET /me/assessments` | `true` (route `role:student`) | — | `StudentAssessmentMetadataQuery` | — | `{data:[9 key]}` |
| 19 | C | `GET /me/assessments/{a}` | `true` | 404 nếu chưa được giao | Query | — | `{assessment:{9 key}}` |
| 20 | C | `GET /me/children/{studentId}/assessments` | `viewAsParent` (D9) | 404 | Query | — | `{data:[9 key]}` |
| 21 | C | `GET /me/children/{studentId}/assessments/{a}` | `viewAsParent` | 404 (liên kết) → 404 (chưa được giao) | Query | — | `{assessment:{9 key}}` |

`viewAsParent(User, string $studentId)` là ability của `AssessmentPolicy` gọi dạng `Gate::inspect('viewAsParent', [Assessment::class, $studentId])`: `ClassScope::parentCoversStudent` → allow, ngoài ra `denyAsNotFound`.

## Bảng ảnh hưởng

| File:dòng | Đọc/ghi gì | Xử lý |
|---|---|---|
| `app/Models/{Assessment,AssessmentVersion,StudentAssessment}.php` | `$casts` | **Sửa** (D2) |
| `SubmissionGrading/Actions/StartAttempt.php:33-34`, `Http/Controllers/GetAttemptController.php:20`, `Actions/ReissueAttempt.php:34` | so sánh thuộc tính đã cast | **Sửa** (D3) |
| `tests/Architecture/ArchitectureTest.php` | `MODULES`, `SHARED_ENUMS`, `KNOWN_LITERALS` (xóa mục StartAttempt) | **Sửa** |
| `app/Support/StudentAssessmentSync.php` | chuyển vào module (D12) | **Chuyển/xóa** |
| `app/Support/ClassScope.php` (`teacherCoversAssessment`, `parentCoversStudent`, `studentAndParentRecipients`, `ownedCourseIdsFor`) | dùng lại, **không sửa** | — |
| `ResultProgress/Queries/StudentProgressQuery.php:28`, các `where('status', …)` query builder | cast không ảnh hưởng điều kiện SQL | **Không sửa** |
| `database/factories/{Assessment,AssessmentVersion,StudentAssessment}Factory.php`, seeder | `'status' => '…'` chuỗi hợp lệ gán vào thuộc tính cast | **Không sửa** |
| `routes/api.php` | 21 dòng | **Sửa** |
| Test hiện có (`tests/Feature/Assessment/*`, `Audit/AssessmentAuditTest`, SubmissionGrading/ResultProgress) | chỉ đổi `assertSame('x', $model->status)` → `->value` hoặc `getRawOriginal`, và cách gọi `StudentAssessmentSync` | **Chỉ** theo quy tắc 2 của 9b |

## Review Focus (cho Opus)

1. **Phân quyền:** D4/D6 — chỉ người tạo hoặc Admin ghi (kể cả người tạo là Teacher đã bị gỡ lớp); Teacher chỉ xem bài thuộc lớp/Course của mình hoặc do mình tạo (404, không 403); phạm vi đích của `assign` (lớp mình, Course mình hoặc không chủ, học viên thuộc lớp mình); Parent chỉ xem bài của con còn liên kết; `routes/api.php` là chốt chặn theo vai trò.
2. **Giữ nguyên hành vi:** thứ tự kiểm tra từng endpoint (bảng "Thứ tự cũ"); mã `CONFLICT`/`ERROR` được giữ; mọi message; shape Model trả nguyên; `ListAssessments` không phân trang `links`; `ListMyAssessments` ẩn draft.
3. **Ngoại lệ có chủ đích:** D5, D9, D3 (sửa SubmissionGrading — đường bắt đầu làm bài, `exam` hiển thị, cấp lại lượt) — soi kỹ vì đây là code của module khác.
4. **Audit & transaction:** D8/D14 — audit lỗi hủy cả thay đổi (kể cả snapshot câu hỏi khi publish); `FOR NO KEY UPDATE`; publish ↔ sửa không chen nhau; `Reopen` ghi hàng loạt `StudentAssessment` + `after.student_ids`.
5. **Thông báo:** D13 — chỉ khi `published`; chạy sau commit; recipient đúng; draft không báo.
6. **Đột biến (Task 6):** cái nào sống sót và test bổ sung.

## Danh sách file được phép đụng

| File | Trách nhiệm |
|---|---|
| `tests/Feature/Assessment/{AssessmentLifecycleBehaviorTest,AssessmentAudienceBehaviorTest,AssessmentViewBehaviorTest}.php` (mới) + `Concerns/AssessmentFixtures.php` (mới) | Đặc tả hành vi trên code cũ |
| `tests/Feature/Assessment/{AssessmentPolicyTest,AssessmentAuditRollbackTest,AssessmentActionsTest,AssessmentOutsideHttpTest}.php` (mới) | Phân quyền, rollback, Action, Query ngoài HTTP |
| `app/Modules/Assessment/{Enums,Http,Actions,Queries,Policies,Events,Listeners,Exceptions,Providers}/**` (mới) | Module |
| `tests/Unit/Modules/Assessment/**` (mới) | Enum, Exception |
| `app/Models/{Assessment,AssessmentVersion,StudentAssessment}.php` | `$casts` |
| 3 file SubmissionGrading ở D3; `tests/Feature/Models/EnumCastsTest.php` | Sửa tối thiểu |
| `bootstrap/providers.php`, `routes/api.php`, `tests/Architecture/ArchitectureTest.php` | Đăng ký, route, rule |
| `app/Http/Controllers/Api/Assessment/*` | **Xóa** (21 file); `app/Support/StudentAssessmentSync.php` xóa |
| `piclass-api/CLAUDE.md`; docs: playbook, `error-codes.md`, `model-ownership.md`, `open-decisions.md`, `progress.md`, plan tổng | Ghi nhận |

---

## Chuẩn bị

- [ ] **P1:** `docker ps` thấy `piclass-local-api-1`. **P2:** tạo branch BE `refactor/assessment` từ `origin/main`. **P3:** full suite, Expected `1254 passed`; khác: DỪNG và báo.

### Task 1: Test đặc tả hành vi trên code cũ

**Tất cả phải xanh trên code CŨ.** Fixture `AssessmentFixtures` (trait: dựng Assessment + version + câu hỏi từ QuestionVersion thật, audience lớp/Course/học viên, user admin/teacher/student/parent, đăng nhập với `active_role_code`, `assertError` kiểm `code`+`message`; tham khảo `UserManagementFixtures`).

- [ ] **Step 1: `AssessmentLifecycleBehaviorTest`** (dòng 1–10): với mỗi endpoint — key response (`sortedKeys`), status 200/201, `code`+`message` của MỌI nhánh lỗi trong bảng "Thứ tự cũ" (kể cả **thứ tự**: ví dụ request vừa sai quyền vừa sai input → 403 chứ không 422; version đã publish + input sai → 409 trước 422); `Create`: `lesson_session_id` với `type != assignment` → 422 `ERROR`, giá trị mặc định (`max_attempts=1`, `attempt_score_policy='latest'`, các cờ false), `type` ngoài tập → 422 `details`; `Update`/câu hỏi chỉ khi draft, người tạo là Teacher đã mất lớp vẫn sửa được, Teacher khác 403, Admin sửa bài của người khác được; `AddQuestion` `position` tự tăng, snapshot ban đầu chỉ `prompt`, `question_type` copy; `RemoveQuestion` câu của version khác → 404; `CreateVersion` copy mọi field + câu hỏi, `version_number+1`, bài `cancelled/archived` → 409, đã có draft → 409; `Publish` chụp snapshot đầy đủ (`prompt, instructions, type_payload, grading_mode, grading_config, options[]`), bài `closed` giữ `closed` sau republish, audit `after.version_id/status`; `Close` chỉ từ `published`; `Reopen` đúng một đích (`student_ids` rỗng không tính), chỉ đổi `StudentAssessment` chưa `completed` của đúng nhóm, `after.student_ids`; `Cancel` ghi `cancellation_reason` vào version hiện tại + audit `reason`. Audit của 4 endpoint: `action`/`entity_type`/`entity_id`/`actor_user_id`/`before_data` null/`after_data`/`reason`/`correlation_key` null.
- [ ] **Step 2: `AssessmentAudienceBehaviorTest`** (dòng 11–15): `POST audience` ma trận người tạo/Admin/Teacher khác × đích lớp/Course/học viên (phạm vi: lớp ngoài phân công → 403 `'Lớp này không thuộc phân công của bạn.'`, Course của teacher khác → 403 `'Course này không thuộc quyền sử dụng của bạn.'`, Course không chủ → qua, học viên ngoài lớp → 403 `'Học sinh này không thuộc lớp bạn phụ trách.'`), 0 hoặc ≥2 đích → 422 `ERROR`, `StudentAssessment` được tạo cho đúng học viên (lớp: thành viên `active`; Course: ghi danh `active`), học viên `withdrawn` được kích hoạt lại, không nhân đôi; thông báo chỉ khi bài `published` (kiểm bảng thông báo + người nhận gồm Parent, `event_key`, `title`, `body`, `context`) và **không** khi `draft`; `EndAudience`: audience của bài khác → 404, `valid_to` đặt, học viên còn đường khác giữ `assigned`, học viên mất hết đường → `withdrawn` (cả học viên đã rời lớp từ trước); `List*` quyền xem 404 (Teacher ngoài phạm vi) / Admin / người tạo / Teacher có lớp; `UpdateStudent` `whereUuid`, `firstOrFail` 404, chỉ `individual_deadline_at`/`attempt_limit_override`.
- [ ] **Step 3: `AssessmentViewBehaviorTest`** (dòng 16–21): `GET /assessments` ma trận vai trò × bộ lọc (`status`,`type`,`class_id`,`course_id`) + phân trang (`per_page`, `meta`), không có `links`; `GET /assessments/{a}` shape, 404 ngoài phạm vi; `GET /me/assessments` ẩn `draft`, đúng 9 key, chỉ bài được giao; `GET /me/assessments/{a}` chưa giao → 404; Parent: liên kết `ended`/không liên kết → 404, chưa giao → 404, đúng shape; vai trò khác → 403 middleware.
- [ ] **Step 4: Thăm dò 500** (quy tắc 8 của 9b): `GET /assessments?per_page=abc`, `?class_id=x`, `?course_id=x`, `?status[]=x`; `POST audience` id sai; `me/children/{id}/assessments` với Parent không hồ sơ (kỳ vọng 500 hôm nay, **không ghim**, D9). Trả 500 thì bỏ case khỏi test và ghi nợ.
- [ ] **Step 5:** Chạy `tests/Feature/Assessment tests/Feature/Audit` trên code cũ, Pint, **Commit:** `test: pin Assessment endpoint behavior before the module refactor`.

### Task 2: Enum + cast + sửa chỗ ngoài module

- [ ] Viết trước: unit test 4 Enum (tập giá trị đúng như bảng) + `EnumCastsTest` thêm 4 cột → RED; tạo Enum, cast 3 Model → GREEN.
- [ ] Sửa 3 file SubmissionGrading (D3) và `StudentAssessmentSync` (tạm sửa trong `Support` để xanh, sẽ chuyển ở Task 4); `grep -rn "'published'\|'draft'\|'closed'\|'assigned'\|'withdrawn'\|'completed'\|'exam'\|'assignment'" app` ngoài danh sách đã rà: DỪNG. Sửa test cũ chỉ ở kiểu so sánh Enum.
- [ ] Chạy `tests/Feature` toàn bộ module Assessment/SubmissionGrading/ResultProgress/Audit/Models/Authorization; Pint; **Commit:** `feat: add assessment enums and cast the assessment status columns`.

### Task 3: Hạ tầng — Policy, Exception, Query, Event, Provider

- [ ] **Exceptions** (D7) + unit test bảng (`code`, `status`, `message`). **Policy** `AssessmentPolicy` (D4, D6, D9), Provider (`Gate::policy`, `Event::listen`), đăng ký `bootstrap/providers.php`. **Queries** `ListAssessmentsQuery`, `StudentAssessmentMetadataQuery`. **Event/Listener** (D13). **`StudentAssessmentSync`** chuyển vào `Actions` (D12), xóa bản `Support`, đổi test gọi static.
- [ ] Chạy test module + `ArchitectureTest` (chưa thêm vào `MODULES`); Pint; **Commit:** `feat: add Assessment policy, queries, exceptions and assigned-notification listener`.

### Task 4: Chuyển từng endpoint (bảng endpoint)

Mỗi nhóm: FormRequest + Action + Resource + Controller (mẫu 9b), sửa dòng `routes/api.php`, xóa controller cũ; **xanh toàn bộ** `tests/Feature/Assessment tests/Feature/Audit tests/Feature/SubmissionGrading tests/Feature/ResultProgress` trước mỗi commit.

- [ ] **4a Nhóm A vòng đời** (1–10; D8, D14: 4 Action audit trong transaction, khóa dòng). **Commit.**
- [ ] **4b Nhóm B giao bài** (11–15; D6, D13). **Commit.**
- [ ] **4c Nhóm C xem** (16–21; D9, D11). **Commit.**
- [ ] **4d:** `app/Http/Controllers/Api/Assessment/` phải rỗng và xóa; `grep -rn "Controllers\\\\Api\\\\Assessment" app routes tests` rỗng.

### Task 5: Rule kiến trúc

- [ ] Thêm `'Assessment'` vào `MODULES`; `SHARED_ENUMS['SubmissionGrading']` thêm 3 Enum (D3); xóa mục `KNOWN_LITERALS` của `StartAttempt`. Chạy `ArchitectureTest`: vi phạm nằm ngoài dự kiến (R4/R5/R7/R8 ở file plan không nhắc): DỪNG. Pint; **Commit:** `test: enforce architecture rules on the Assessment module`.

### Task 6: Test phân quyền, rollback, Action

- [ ] **`AssessmentPolicyTest`** (Gate trực tiếp, không HTTP): mọi ability × {Admin; người tạo (Teacher, kể cả đã mất lớp); Teacher khác; vai trò rỗng → deny (D5); Admin nhập vai teacher không phải người tạo → deny; Student/Parent}; `view` × {người tạo; Teacher có lớp trong audience (lớp ended thì không); Teacher sở hữu Course trong audience; Teacher khác → 404}; `assignTarget` ma trận phạm vi (lớp/Course/học viên của người khác **có dữ liệu thật**); `viewAsParent` (liên kết active/ended/của Parent khác/không hồ sơ).
- [ ] **`AssessmentAuditRollbackTest`**: audit giả ném lỗi → `Publish` (snapshot câu hỏi, status version và assessment giữ nguyên), `Close`, `Reopen` (StudentAssessment giữ nguyên), `Cancel` (version không có `cancellation_reason`); `audit_logs` = 0; qua HTTP lỗi lan ra và dữ liệu giữ.
- [ ] **`AssessmentActionsTest`**: snapshot audit là chuỗi (không Enum); khóa dòng (`for no key update` trên `assessments`) ở 4 Action audit + Action sửa draft (kiểm `DB::getQueryLog`, như `UserManagementMembershipActionsTest`); `AddAssessmentQuestion` position trong transaction; `StudentAssessmentSync` (ensure/withdraw/hasLivePath) trực tiếp; listener thông báo chạy sau commit và không làm hỏng giao bài khi dispatcher ném lỗi.
- [ ] **`AssessmentOutsideHttpTest`**: Query chạy không session/Request; `StudentAssessmentMetadataQuery` ẩn draft.
- [ ] Chạy; Pint; **Commit** theo nhóm.

### Task 7: Thử đột biến (bắt buộc) — bản sao ngoài working tree

Quy trình như 9b/9c-trước: copy `/var/www/html` → `/tmp/mut` trong container, driver `mutate2.py`/`muts_*.json` ở scratchpad, **không chạy cùng full suite**. Bộ tối thiểu (mỗi dòng một đột biến riêng, tất cả phải đỏ; sống sót → bổ sung test rồi chạy lại cả nhóm sống sót với test mới):

| # | Đột biến | Test kỳ vọng đỏ |
|---|---|---|
| 1 | Mỗi ability quản lý của `AssessmentPolicy`: luôn allow / bỏ nhánh admin / vai trò rỗng = admin / bỏ nhánh người tạo / đổi message (×9) | Policy + Behavior |
| 2 | `view`: luôn allow / bỏ nhánh người tạo / bỏ Course / bỏ lớp / đổi 404 thành 403 / dùng `studentProfile` thay `teacher` | Policy + Behavior |
| 3 | `assignTarget`: bỏ từng nhánh (lớp, Course, `owner_teacher_id` null, học viên) / đổi thứ tự trước "đúng một đích" | Policy + Behavior |
| 4 | `viewAsParent`: bỏ lọc `status` / luôn allow | Policy + Behavior |
| 5 | 4 Action audit: audit sau transaction (4 đột biến riêng); bỏ `reason` ở cancel; ghi `after` sai; bỏ `student_ids` ở reopen | Rollback + Behavior |
| 6 | `PublishAssessment`: bỏ snapshot; bỏ kiểm tra rỗng; republish mở lại bài `closed`; bỏ kiểm `cancelled/archived` | Behavior |
| 7 | Mọi kiểm tra trạng thái draft (Update/AddQ/UpdateQ/RemoveQ/Publish/CreateVersion): bỏ từng cái | Behavior (6 đột biến riêng) |
| 8 | `CreateAssessmentVersion`: bỏ copy câu hỏi / bỏ một field / không tăng `version_number` / bỏ kiểm draft đang mở | Behavior |
| 9 | `ReopenAssessment`: bỏ lọc `completed` / mở cả lớp khi gửi `student_ids` / bỏ kiểm "đúng một đích" / bỏ `status=published` | Behavior |
| 10 | `AddAssessmentAudience`: bỏ kích hoạt lại `withdrawn` / đổi nguồn học viên (lớp ↔ Course) / thông báo cả khi draft / thông báo ngoài hàng đợi sau commit / bỏ Parent khỏi người nhận | Behavior + Actions |
| 11 | `EndAssessmentAudience`: chỉ rút học viên còn trong lớp (bỏ nhánh "đã rời lớp") / bỏ kiểm audience thuộc bài | Behavior |
| 12 | `StudentAssessmentSync`: bỏ một nhánh `hasLivePath` (trực tiếp, lớp, Course) / bỏ lọc `valid_to` | Actions + Behavior |
| 13 | Query: `StudentAssessmentMetadataQuery` bỏ ẩn draft / đổi một key; `ListAssessmentsQuery` bỏ một bộ lọc / bỏ phạm vi Course / bỏ phạm vi người tạo | Behavior |
| 14 | Khóa: `for no key update` → `for update`; bỏ khóa ở từng Action (≥7) | Actions |
| 15 | Resource: đổi/bỏ từng key (mỗi Resource ≥2 đột biến) | Behavior (key-set) |
| 16 | Request: bỏ `exists:`/`uuid`/`required`/`min`; `Rule::enum` → `in:` thiếu giá trị | Behavior |
| 17 | Enum: thêm/bớt case/đổi giá trị | Enum unit + EnumCastsTest |
| 18 | Ba chỗ SubmissionGrading ở D3: đảo điều kiện | test SubmissionGrading hiện có (nếu sống sót: thêm test) |

- [ ] Ghi bảng kết quả vào báo cáo và `progress.md`.

### Task 8: Tài liệu và nghiệm thu

- [ ] **Docs:** `error-codes.md` (bảng mã chung được giữ, D7); `model-ownership.md` (hàng audit "Assessment" sang tên Action); `open-decisions.md`: `ARCH-14` ghi còn mở sau 9c + mục khoảng cách phát hiện (mới `ARCH-24` nếu có: ví dụ lỗi 500 có sẵn ở bộ lọc `GET /assessments`, D9); playbook (nợ mới); `progress.md` bullet "Kết quả Task 9c" + "Việc kế tiếp: Task 9d `QuestionBank`"; `piclass-api/CLAUDE.md` mục Enum; bảng "Trạng thái thực hiện" của plan tổng.
- [ ] **Full suite**, Pint trên file đã đổi, `git log origin/main..HEAD`, **chưa push**.
- [ ] **Báo cáo cho Opus** (D5, D9, D3 là ngoại lệ có chủ đích; Review Focus), sửa Critical/Important (mỗi cái test RED→GREEN, suite xanh), minor vào ledger. **DỪNG**, hỏi user trước khi push.

## Tiêu chí xong

- 21 controller cũ đã xóa, 21 controller + Request + Resource mới trong `app/Modules/Assessment`, route đúng; `ArchitectureTest` R1–R8 xanh với `Assessment` trong `MODULES`.
- Test đặc tả Task 1 xanh trước và sau refactor nguyên văn (không ngoại lệ hành vi ngoài D5, D9 mà HTTP không thấy / D9 là 500 → 404); full suite xanh, số test ≥ baseline.
- 4 Action audit trong transaction với dữ liệu, có test rollback; mọi 403/404 assert `code` + `message`.
- Bảng đột biến: không còn lỗi cố ý sống sót (hoặc sống sót được giải thích là tương đương).
- Tài liệu cập nhật; hai repo commit local, chưa push.

## Ghi chú thực thi (điền sau khi xong)

**Khác plan (rulings):** (1) D9 mở rộng: Student chưa có hồ sơ cũng 404 (cùng nguyên nhân NPE → 500). (2) Thêm `->whereUuid('studentId')` cho hai route Parent của bài (route kế bên đã có) để id sai là 404 thay vì 500 của Postgres. (3) Các kiểm tra 409/404 phải đứng trước validate đặt ở `FormRequest::authorize()` sau Gate và lặp lại trong Action dưới khóa dòng. (4) Controller cũ của chính module so sánh thuộc tính đã cast nên được sửa tại chỗ trong Task 2 (thay thế ở Task 4). (5) `StudentAssessmentSync` bản `Support` giữ đến 4b rồi xóa. (6) `AssessmentStateConflict`/`InvalidAssessmentRequest`/`AudienceTargetOutOfScope`/`AssessmentChildNotFound` gom theo nhóm thay vì một class mỗi thông điệp (D7 cho phép).

**Kết quả:** test 1254 → 1477; 214 đột biến: 184 đỏ ngay, 28 sống sót đã bổ sung test và đều đỏ, 2 tương đương (`6j` bỏ `continue` khi câu hỏi không có nguồn vì `find(null)` đã `continue`; `9h` danh sách `student_ids` rỗng vốn bị `array_filter` loại).
