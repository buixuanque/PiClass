# Task 9d — Refactor module `QuestionBank` sang quy ước module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** 9 controller của `QuestionBank` (`CreateQuestion`, `UpdateQuestion`, `ArchiveQuestion`, `UpdateQuestionTopics`, `UpdateQuestionTags`, `GetQuestion`, `ListQuestions`, `ListTopics`, `ListTags`) chuyển từ `app/Http/Controllers/Api/QuestionBank/` sang `app/Modules/QuestionBank/` theo `piclass-api/CLAUDE.md` (FormRequest + Policy + Action/Query + Resource), dùng `ActiveRole`, Enum cho `question_type`/`grading_mode`/`status`; không đổi HTTP status, `error.code`, `error.message`, key JSON, giá trị chuỗi lưu DB — trừ các ngoại lệ ghi ở "Quyết định đã chốt". **Một PR, một lượt review Opus ở cuối.**

**Architecture:** Module `app/Modules/QuestionBank/{Http,Actions,Queries,Policies,Enums,Exceptions,Providers}`. `QuestionPolicy` thay bốn chỗ lặp `abort_unless($activeRole === 'admin' || created_by === user, 403, ...)`. `TopicTagResolver` (đang ở `app/Support`, chỉ QuestionBank dùng) chuyển vào module thành Action tiêm được. Không audit (spec §4 không liệt kê action nào của QuestionBank), không Event/Listener.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, PHPUnit 12, Pint. Container `piclass-local-api-1`.

**Spec / nguồn:** `piclass-api/CLAUDE.md`; `docs/architecture/backend-refactor-playbook.md` (validation-first rulings #11, bẫy cast Enum); `docs/superpowers/specs/2026-10-15-question-bank-assessment-api-design.md`; `docs/architecture/model-ownership.md`, `error-codes.md`; plan mẫu `2026-10-21-be-assessment.md`.

---

## Quy tắc cho người thực thi

Áp dụng nguyên văn quy tắc 1–10 của plan 9c (`2026-10-21-be-assessment.md`, gồm quy tắc 1–8 của 9b): gặp điểm mơ hồ/quyết định thiếu thì **tự đề xuất, ghi ledger `Ruling:` và tổng hợp trong mô tả PR** (user đã ủy quyền, sẽ kiểm tra trong PR); test đặc tả viết trên code cũ trước; giá trị kỳ vọng trong test là chuỗi literal; commit local tiếng Anh kết thúc `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`; đột biến chỉ trên bản sao `/tmp/mut` và không chạy cùng full suite; comment tiếng Việt; thăm dò 500 không ghim 500.

## Global Constraints

- Giữ nguyên hành vi: HTTP status, `error.code` (kể cả mã chung `CONFLICT`/`ERROR`/`FORBIDDEN`/`NOT_FOUND` của `abort()` cũ), `error.message`, tập key JSON (kể cả response trả nguyên Model: tập thuộc tính phải bằng đúng cũ), giá trị chuỗi trong JSON/DB — ngoại lệ D5, D6.
- Baseline: **1478 test pass**.
- Thứ tự validation-first (playbook rulings #11): 403 → 422 → trạng thái/đối tượng. Chỗ code cũ kiểm trạng thái trước validate (`UpdateQuestionTopics`: 422 "chưa gắn Subject" trước `validate`) đổi theo.
- Mọi case 403/404 trong test mới assert cả `error.code` lẫn `error.message`. Pint chỉ chạy `--test` trên file branch đổi (`routes/api.php` đã lỗi sẵn).

### Git

BE: `git fetch origin && git checkout -b refactor/question-bank origin/main && git branch --unset-upstream`. Docs: `docs/be-question-bank`.

## Quyết định đã chốt

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | **Một PR**, 9 controller, 2 nhóm tuần tự: **A** ghi (5: `CreateQuestion`, `UpdateQuestion`, `ArchiveQuestion`, `UpdateQuestionTopics`, `UpdateQuestionTags`), **B** đọc (4: `GetQuestion`, `ListQuestions`, `ListTopics`, `ListTags`) | 358 dòng, nhỏ | Chia PR |
| D2 | **Enum** (`Modules/QuestionBank/Enums`): `QuestionType` đã có; thêm `GradingMode` (`automatic, manual, mixed`) và `QuestionStatus` (`active, archived`). Cast mới: `QuestionVersion.question_type` → `QuestionType`, `QuestionVersion.grading_mode` → `GradingMode`, `Question.status` → `QuestionStatus`. `Rule::enum` thay `in:` ở `question_type`/`grading_mode` (cùng thông điệp lỗi, test ghim) | Enum phản chiếu CHECK; `EnumCastsTest` đối chiếu | Chỉ cast `status` |
| D3 | **Chỗ ngoài module bị cast ảnh hưởng** (đã rà `grep`): `Modules/Assessment/Actions/PublishAssessment` ghi `grading_mode` của `QuestionVersion` vào snapshot (đổi sang `->value`); `Modules/Assessment/Actions/AddAssessmentQuestion` truyền `$sourceVersion->question_type` vào `AssessmentQuestion` (đã cast `QuestionType`, nhận Enum an toàn); `SubmissionGrading` đọc `AssessmentQuestion.question_type`, không đọc `QuestionVersion`. Chỗ khác thấy bằng `grep` ngoài danh sách: DỪNG | Giữ đúng snapshot | — |
| D4 | **`QuestionPolicy`** (`Gate::policy(Question::class, ...)`): `update` (message `Chỉ người tạo câu hỏi hoặc Admin được sửa.`, dùng cho `UpdateQuestion`, `UpdateQuestionTopics`, `UpdateQuestionTags`) và `archive` (`Chỉ người tạo câu hỏi hoặc Admin được lưu trữ.`); điều kiện: `isAdmin()` hoặc `created_by_user_id === $user->id` (người tạo qua với mọi vai trò, đúng cũ). **Ngoại lệ hành vi D5:** vai trò rỗng không còn là admin. Không có ability xem: kho câu hỏi dùng chung, mọi Admin/Teacher (route `role:admin,teacher`) xem được mọi câu (đúng cũ) | Quy ước Policy; spec "Admin quản lý toàn kho, Teacher quản lý câu mình tạo" | Giới hạn xem theo người tạo (đổi hành vi) |
| D5 | Vai trò rỗng (job/console) không là admin (default-deny); HTTP không thấy (route chặn) | Cùng D5 của 9b/9c | Giữ "rỗng = qua" |
| D6 | **Validation-first:** `UpdateQuestionTopics` hôm nay trả 422 `ERROR` `Câu hỏi chưa gắn Subject nên không gắn được Topic.` TRƯỚC khi validate body; mới: validate trước (422 `VALIDATION_FAILED`), rồi Action ném `QuestionHasNoSubject` (422, mã `ERROR`, cùng message). Ghi vào PR như ngoại lệ có chủ đích | Playbook rulings #11 | Giữ thứ tự cũ |
| D7 | **Action** (mọi endpoint ghi; transaction khi ghi ≥ 2 bảng): `CreateQuestion::handle(User, array): Question` (bank mặc định: `QuestionBank::query()->value('id') ?? QuestionBank::create(['name' => 'System Bank'])->id`; ghi `Question` + `QuestionVersion` + `QuestionOption` + gắn Topic/Tag + đặt `current_version_id`); `UpdateQuestion::handle(Question, User, array): QuestionVersion` (tạo **version mới**, `version_number = max + 1` tính dưới khóa dòng `questions` `FOR NO KEY UPDATE` để hai yêu cầu đồng thời không trùng số); `ArchiveQuestion::handle(Question): Question` (`status = archived`, `archived_at = now()`); `UpdateQuestionTopics::handle(Question, array $names): Collection`; `UpdateQuestionTags`. `TopicTagResolver` chuyển thành `Actions/ResolveTopicsAndTags` (tiêm được, 2 method `topics(string $subjectId, array $names): array`, `tags(array $names): array`); xóa `app/Support/TopicTagResolver.php` | Controller không ghi DB; chống trùng số version | Giữ static ở `Support` |
| D8 | **Query:** `ListQuestionsQuery::handle(User, array $filters, int $perPage): LengthAwarePaginator` (đúng cách lọc cũ: `mine` bằng `boolean`, `subject_id`, `status`, `question_type` qua `currentVersion`, `topic_id`/`tag_id` qua `currentVersion.topics|tags`; mới nhất trước; `per_page` mặc định 20); `ListQuestionsRequest::rules()` **rỗng** (thêm validate đổi 200 → 422); `SearchTopicsQuery`/`SearchTagsQuery` (`ilike %search%`, `subject_id`, sắp theo `name`, `limit 20`, cột `id,subject_id,name` / `id,name`) | Giữ shape | Validate bộ lọc (đổi hành vi) |
| D9 | **Response giữ nguyên:** Create trả `{question:{id,question_bank_id,subject_id,status,current_version:{id,version_number,question_type,prompt,options,topics,tags}}}` 201 (mảng dựng tay); Update trả `{question:{id,current_version:<version + options,topics,tags>}}` 200 (version là model vừa tạo nên Resource ép 200); Archive `{question:{id,status}}`; Get `{question:{id,question_bank_id,subject_id,status,created_by_user_id,current_version:<version + options,topics,tags>}}`; List `{data:[question+currentVersion], meta:{total,current_page,last_page}}` (không `links`); topics/tags `{topics:[{id,name}]}`/`{tags:[{id,name}]}`; ListTopics/ListTags `{data:[...]}`. Test ghim tập key đầy đủ của từng Model nhúng | Giữ shape | `ResourceCollection` mặc định |
| D10 | **Ngoài phạm vi (ghi lại):** (a) chưa có khóa duy nhất cho `topics (subject_id, name)`/`tags (name)` nên hai yêu cầu đồng thời có thể tạo trùng qua `firstOrCreate` (race có sẵn); (b) `UpdateQuestionTags/Topics` với câu hỏi chưa có `current_version` (không xảy ra qua API: luôn đặt khi tạo) — thăm dò, không ghim | Refactor không đổi hành vi | Sửa luôn |

### Danh sách Enum

| Enum | Giá trị | Cột được cast |
|---|---|---|
| `QuestionType` (có sẵn) | `single_choice, multiple_choice, true_false, fill_in_the_blank, short_answer, essay, matching, ordering, file_upload` | `AssessmentQuestion.question_type`, **thêm** `QuestionVersion.question_type` |
| `GradingMode` | `automatic, manual, mixed` | `QuestionVersion.grading_mode` |
| `QuestionStatus` | `active, archived` | `Question.status` |

## Bảng endpoint (9)

| # | Nhóm | Endpoint | Authorize | Action / Query | Resource |
|---|---|---|---|---|---|
| 1 | A | `POST /questions` | `true` (route `role:admin,teacher`) | `CreateQuestion` | `CreatedQuestionResource` 201 |
| 2 | A | `PATCH /questions/{q}` | `update` | `UpdateQuestion` | `UpdatedQuestionResource` |
| 3 | A | `PATCH /questions/{q}/topics` | `update` | `UpdateQuestionTopics` (+ `QuestionHasNoSubject`) | `QuestionTopicsResource` |
| 4 | A | `PATCH /questions/{q}/tags` | `update` | `UpdateQuestionTags` | `QuestionTagsResource` |
| 5 | A | `DELETE /questions/{q}` | `archive` | `ArchiveQuestion` | `ArchivedQuestionResource` |
| 6 | B | `GET /questions/{q}` | `true` | Eloquent (`load`) | `QuestionDetailResource` |
| 7 | B | `GET /questions` | `true` | `ListQuestionsQuery` | `QuestionPageResource` |
| 8 | B | `GET /topics` | `true` | `SearchTopicsQuery` | `TopicListResource` |
| 9 | B | `GET /tags` | `true` | `SearchTagsQuery` | `TagListResource` |

## Review Focus (cho Opus)

1. **Phân quyền:** D4/D5 — chỉ người tạo hoặc Admin sửa/lưu trữ (kể cả topics/tags); kho dùng chung xem mở cho Admin/Teacher (không phải lỗ hổng); `routes/api.php` là chốt chặn theo vai trò (Student/Parent không vào).
2. **Giữ nguyên hành vi:** key JSON từng response (Model nhúng), `version_number` tăng đúng, mã/thông điệp 403/404/422, `Rule::enum` cùng thông điệp; D6 là ngoại lệ có chủ đích.
3. **Cast Enum:** D3 — `PublishAssessment` snapshot, `AddAssessmentQuestion`, `AutoGrader`/`BuildAttemptQuestions` (đọc `AssessmentQuestion.question_type`) không gãy; không còn so sánh chuỗi trên `QuestionVersion.question_type|grading_mode`, `Question.status`.
4. **Transaction/khóa:** `UpdateQuestion` khóa `questions` để `version_number` không trùng; `CreateQuestion` ghi nhiều bảng trong một transaction (hỏng giữa chừng không để lại câu hỏi mồ côi).
5. Bảng đột biến: cái nào sống sót và test bổ sung.

## Danh sách file được phép đụng

| File | Trách nhiệm |
|---|---|
| `tests/Feature/QuestionBank/{QuestionBankLifecycleBehaviorTest,QuestionBankReadBehaviorTest}.php` + `Concerns/QuestionBankFixtures.php` (mới) | Đặc tả hành vi trên code cũ |
| `tests/Feature/QuestionBank/{QuestionPolicyTest,QuestionBankActionsTest,QuestionBankOutsideHttpTest,QuestionBankBoundaryCasesTest}.php` (mới) | Phân quyền, Action, Query, ca biên |
| `app/Modules/QuestionBank/{Enums,Http,Actions,Queries,Policies,Exceptions,Providers}/**` | Module |
| `tests/Unit/Modules/QuestionBank/**` | Enum, Exception |
| `app/Models/{Question,QuestionVersion}.php`, `tests/Feature/Models/EnumCastsTest.php` | `$casts` |
| `app/Modules/Assessment/Actions/PublishAssessment.php` | `->value` (D3) |
| `bootstrap/providers.php`, `routes/api.php`, `tests/Architecture/ArchitectureTest.php` | Đăng ký, route, rule |
| `app/Http/Controllers/Api/QuestionBank/*` (xóa 9), `app/Support/TopicTagResolver.php` (xóa) | — |
| `piclass-api/CLAUDE.md`; docs: playbook, `error-codes.md`, `model-ownership.md`, `open-decisions.md`, `progress.md`, plan tổng | Ghi nhận |

---

### Task 1: Test đặc tả hành vi trên code cũ
- [ ] `QuestionBankLifecycleBehaviorTest` (endpoint 1–5): key response từng endpoint, 201/200, mọi nhánh lỗi (code+message), `Create` mặc định (bank tự tạo 'System Bank' khi chưa có; dùng bank đầu tiên khi có; `subject_id` null; topic chỉ gắn khi có Subject; tag luôn gắn; option `position` 1..n, `is_correct` null mặc định), `Update` tạo version mới (`version_number` +1, version cũ giữ nguyên, option/topic/tag chỉ của version mới, `question_type` ghi lại), `Archive` (`archived_at`, status), `Topics` (sync, tạo topic mới theo `subject_id`; câu hỏi không Subject → 422 `ERROR` message cũ — ghim thứ tự **cũ** 422 trước validate trên code cũ, sẽ đổi ở Task 4 theo D6), `Tags` (sync, tạo tag mới); người tạo/Admin được, Teacher khác 403 message đúng; Student/Parent 403 middleware; 404 id sai/không phải uuid.
- [ ] `QuestionBankReadBehaviorTest` (endpoint 6–9): shape đầy đủ, mọi bộ lọc (`mine`, `subject_id`, `status`, `question_type`, `topic_id`, `tag_id`), phân trang/`per_page`, thứ tự mới nhất trước, `ListTopics/Tags` tìm `ilike`, `limit 20`, thứ tự theo tên, cột chọn đúng; mọi Teacher xem được câu của người khác.
- [ ] Thăm dò 500: `?subject_id=x`, `?topic_id=x`, `?tag_id=x`, `?per_page=abc`, `POST` `subject_id` sai; không ghim 500.
- [ ] Chạy trên code cũ, Pint. **Commit:** `test: pin QuestionBank endpoint behavior before the module refactor`.

### Task 2: Enum + cast
- [ ] Unit test 2 Enum + `EnumCastsTest` thêm 3 cột → RED; tạo `GradingMode`, `QuestionStatus`, cast; sửa `PublishAssessment` (D3); sửa test cũ chỉ ở kiểu so sánh; các controller cũ của module so sánh thuộc tính đã cast thì sửa tại chỗ. Full suite xanh. **Commit:** `feat: add question bank enums and cast the question columns`.

### Task 3: Hạ tầng — Policy, Exception, Query, Action phụ trợ, Provider
- [ ] `QuestionPolicy`, `QuestionHasNoSubject` (+unit test), `ResolveTopicsAndTags`, 3 Query, Provider (`Gate::policy`), `bootstrap/providers.php`. **Commit:** `feat: add QuestionBank policy, queries and topic/tag resolver`.

### Task 4: Chuyển endpoint
- [ ] **4a** nhóm A (D6/D7). **Commit.** **4b** nhóm B (D8/D9). **Commit.** **4c** xóa thư mục controller cũ và `TopicTagResolver`; `grep -rn "Controllers\\\\Api\\\\QuestionBank" app routes tests` rỗng. Test thứ tự cũ của `Topics` ghim lại theo D6 (RED → GREEN).

### Task 5: Rule kiến trúc
- [ ] `'QuestionBank'` vào `MODULES` (và `SHARED_ENUMS['QuestionBank'] = []`); sửa vi phạm do code tôi viết (không thêm `KNOWN_*` ngoài dự kiến). **Commit.**

### Task 6: Test phân quyền, Action, Query, ca biên
- [ ] `QuestionPolicyTest` (admin; người tạo mọi vai trò; Teacher khác; vai trò rỗng; Student/Parent) cho `update`, `archive`; `QuestionBankActionsTest` (khóa dòng + trong transaction, `version_number` từ DB dưới khóa, Create nguyên tử bằng trigger Postgres, `ResolveTopicsAndTags`); `QuestionBankOutsideHttpTest` (Query không session); `QuestionBankBoundaryCasesTest` (luật validate từng Request, thứ tự validation-first). **Commit.**

### Task 7: Thử đột biến
- [ ] Bản sao `/tmp/mut`; bộ tối thiểu: mọi ability Policy (always allow / bỏ admin / rỗng=admin / bỏ người tạo / đổi message); Action (bỏ transaction, bỏ khóa, version_number cố định, không đặt `current_version_id`, option `position`, topic gắn khi không Subject, tag không gắn, bank mặc định); Query (bỏ từng bộ lọc, đổi thứ tự, đổi limit, `ilike`→`like`); Resource (đổi/bỏ từng key); Request (bỏ từng luật); Enum (thêm/bớt case); Assessment `PublishAssessment` snapshot `grading_mode`. Sống sót → bổ sung test rồi chạy lại.

### Task 8: Tài liệu và nghiệm thu
- [ ] Docs (`error-codes.md`: mã chung giữ + `QuestionHasNoSubject`; `model-ownership.md`; `open-decisions.md` `ARCH-25` khoảng cách; playbook nợ; `progress.md`; plan tổng; `CLAUDE.md` mục Enum), full suite, Pint, Opus review (D5, D6 ngoại lệ), sửa Critical/Important, push/PR/merge theo chỉ đạo của user (đã ủy quyền) với phần "điểm cần bạn kiểm tra" trong mô tả PR.

## Tiêu chí xong

- 9 controller cũ xóa, thay bằng module; `ArchitectureTest` R1–R8 xanh với `QuestionBank`; test đặc tả xanh trước/sau (ngoại lệ D5, D6); đột biến không còn sống sót (hoặc giải thích tương đương); tài liệu cập nhật; hai repo đã merge.

## Ghi chú thực thi (điền sau khi xong)

**Khác plan (rulings):** (1) `ReadQuestionBankRequest` dùng chung cho cả 4 endpoint đọc thay vì 4 Request rỗng riêng. (2) `UpdatedQuestionResource` ép 200 trong `withResponse` (version vừa tạo mặc định 201). (3) Controller đọc cũ không phải so sánh thuộc tính đã cast nên không cần sửa tại chỗ ở Task 2. (4) `SearchTopicsQuery`/`SearchTagsQuery` nhận `mixed` để giữ hành vi 500 có sẵn với `search[]` (không ghim).

**Kết quả:** test 1478 → 1582; 132 đột biến: 127 đỏ ngay, 5 sống sót đã bổ sung test và đều đỏ.
