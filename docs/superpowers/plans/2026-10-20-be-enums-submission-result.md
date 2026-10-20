# Task 0b — Backed Enum cho `SubmissionGrading` và `ResultProgress` Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (hoặc superpowers:subagent-driven-development) để thực thi plan này theo từng task. Các bước dùng checkbox (`- [ ]`).

**Goal:** Mọi chuỗi trạng thái/loại do code quản lý mà hai module `SubmissionGrading` và `ResultProgress` dùng (trạng thái attempt/submission/feedback/result, `grading_method`, `visibility`, `question_type`, `attempt_score_policy`, đối tượng xem kết quả `student`/`parent`) được thay bằng backed Enum đặt ở `Enums/` của module sở hữu và cast trong Model — không đổi bất kỳ HTTP status, `error.code`, `error.message`, key JSON, giá trị chuỗi lưu DB hay giá trị chuỗi trong JSON/audit nào.

**Architecture:** Mỗi Enum phản chiếu **đúng toàn bộ** tập giá trị của cột DB tương ứng (kể cả giá trị module chưa dùng, vì cast trên cột mà DB có giá trị ngoài Enum sẽ ném `ValueError` khi đọc). Enum sống ở module sở hữu Model (`docs/architecture/model-ownership.md`); module khác import. Cast Enum có hiệu lực toàn app, nên mọi nơi ngoài hai module đọc/so sánh các cột này đều được liệt kê trong "Bảng ảnh hưởng" và có cách xử lý tường minh. Test đặc tả hành vi viết trước trên code cũ, rồi mới đổi code; sau cùng thử đột biến trên bản sao ngoài working tree.

**Tech Stack:** Laravel 11 (`^11.31`), PHP 8.2, PostgreSQL, PHPUnit, Pint. Container WSL `piclass-local-api-1`.

**Spec:** `piclass-api/CLAUDE.md` (mục "Mặc định nên theo → Enum", "Rule cốt lõi", "Test", "Git"); `docs/architecture/open-decisions.md` `ARCH-17` (làm rõ (a): 2 module đã refactor bổ sung Enum bằng một PR riêng); `docs/architecture/backend-refactor-playbook.md` (phân vai model, bẫy giữ nguyên hành vi, mẫu test, bảng "Nợ đã biết": dòng "Chuỗi trạng thái thay vì Enum" là task này); `docs/database/*` (giá trị hợp lệ của từng cột); `docs/architecture/model-ownership.md`.

---

## Quy tắc cho người thực thi (đọc trước khi làm bất cứ gì)

Plan này đã chốt mọi quyết định thiết kế (mục "Quyết định đã chốt"). Vì vậy:

1. **Gặp điểm mơ hồ, hoặc cần một quyết định không có trong plan: DỪNG và báo** (nêu file, dòng, các phương án). Không tự chọn, không "làm tạm rồi báo sau".
2. Các tình huống bắt buộc DỪNG (không giới hạn ở đây):
   - Một test đặc tả hành vi (Task 1) **fail trên code cũ**. Không sửa kỳ vọng cho khớp code, không sửa code.
   - Một test cũ (có trước task này) fail sau khi đổi code vì **hành vi khác** (status, message, key, giá trị). Chỉ được sửa test cũ trong hai trường hợp ghi ở mục "Test cũ được phép sửa"; mọi trường hợp khác: DỪNG.
   - `grep` ở "Bảng ảnh hưởng" tìm thấy chỗ đọc/so sánh cột bị cast **ngoài** danh sách của plan (app, database, routes, config).
   - Một giá trị trong DB (migration, factory, seeder, test) không nằm trong Enum tương ứng (`ValueError`).
   - Pint báo lỗi ở file bạn không sửa (4 file test đang lỗi sẵn trên `main` — `TeacherClassAssignmentTest`, `AuditLoggerTest`, `NotificationDispatcherTest`, `LessonSessionGeneratorTest` — là bình thường, bỏ qua).
   - Container `piclass-local-api-1` không chạy. **Không** bật Docker Desktop trên Windows.
3. Không đụng file ngoài danh sách "Bản đồ file". Đặc biệt **không sửa**: controller cũ trong `app/Http/Controllers/Api/**` (kể cả `CreateAssessmentVersionController`, `AddAssessmentQuestionController`, `LinkAttachmentController`, controller QuestionBank), `app/Support/**`, factory, seeder, migration.
4. Trong test, giá trị kỳ vọng luôn viết **chuỗi literal** (`'in_progress'`, `'Request failed.'`...), không dùng case của Enum — để nếu ai đổi giá trị Enum thì test bắt được. Ngoại lệ duy nhất: test của chính Enum và test cast (Task 2, 3).
5. Mỗi task kết thúc bằng commit local ở repo BE. **Tuyệt đối không push, không mở PR, không merge, không xóa branch.**
6. Không dùng git worktree: container mount đúng thư mục `C:\Workspaces\home\PiClass\piclass-api`. Thử đột biến chỉ làm trên **bản sao** `/tmp/mut` bên trong container (Task 7); không bao giờ sửa working tree gốc để thử đột biến.

## Global Constraints

- Giữ nguyên hành vi: HTTP status, `error.code`, `error.message`, tập key JSON, **giá trị chuỗi** trong JSON, trong cột DB, trong `audit_logs.before_data/after_data`, `*_histories.before_data/after_data` của mọi endpoint không đổi.
- Baseline: **694 test pass** (PHPUnit, full suite ~265 giây). Mọi task kết thúc với 0 fail và số test = 694 + số test mới đã thêm tới lúc đó.
- Pint chỉ chạy `--test` trên các file `.php` mà branch này đổi.
- Mọi case 403/404 trong test mới assert **cả** `error.code` lẫn `error.message`.
- Code, tên biến, comment, commit message: tiếng Anh. Tài liệu: tiếng Việt.
- Commit message kết thúc bằng dòng `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`.

### Chạy test (backend trong container WSL)

Chạy từ Git Bash. Container mount `/mnt/c/Workspaces/home/PiClass/piclass-api` → `/var/www/html`.

```bash
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker ps --format "{{.Names}}"'                                   # phải thấy piclass-local-api-1
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test tests/Feature/X.php'
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test --filter=ClassName'
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 vendor/bin/pint --test <files>'
# Full suite (~265 giây): chạy nền (run_in_background), ghi log ra scratchpad, đọc dòng "Tests:" cuối log
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 php artisan test' > "<scratchpad>/full.log" 2>&1
```

Chỉ truyền đường dẫn test **tương đối** (`tests/...`).

### Git

- BE (`piclass-api`): `git fetch origin`; working tree sạch; `git checkout -b refactor/enums-submission-result origin/main` (đã có `ActiveRole`, PR #18).
- Docs (`C:\Workspaces\home\PiClass`): `git fetch origin`; `git checkout -b docs/enums-submission-result origin/main`. Commit plan ngay sau khi viết xong, trước khi thực thi.
- Commit local nhỏ theo nhóm Enum; không push/PR/merge; không xóa branch.

## Quyết định đã chốt

| # | Quyết định | Lý do | Phương án đã loại |
|---|---|---|---|
| D1 | Mỗi Enum chứa **toàn bộ** giá trị của cột DB (lấy từ migration), không chỉ giá trị code hiện dùng | Cast ném `ValueError` khi đọc giá trị ngoài Enum; schema là nguồn sự thật | Enum chỉ gồm giá trị đang dùng (hỏng khi dữ liệu có giá trị khác) |
| D2 | Danh sách Enum và nơi đặt (xem bảng dưới) | `CLAUDE.md`: Enum ở module sở hữu Model; module khác import; không có thư mục Enum cấp cao | Một thư mục `app/Enums` chung |
| D3 | **Không cast** `Assessment.status`, `AssessmentVersion.status`, `StudentAssessment.status` (và `Assessment.type`) | Chúng thuộc module `Assessment` chưa refactor; ~25 controller cũ so sánh `=== 'published'`, `in_array(...)`, `->where('status', ...)` trên các cột này nên cast sẽ gãy âm thầm; hai module chỉ dùng 4 chỗ literal (xem bảng "Literal còn lại") | Cast + sửa ~25 controller cũ (ngoài phạm vi, rủi ro cao) |
| D4 | **Có cast** `AssessmentQuestion.question_type` → `QuestionType` và `AssessmentVersion.attempt_score_policy` → `AttemptScorePolicy`, dù hai Model thuộc `Assessment` | Hai module đọc trực tiếp (AutoGrader, BuildAttemptQuestions, 2 Query); đã kiểm tra mọi chỗ ngoài đọc/ghi hai cột này chỉ **chép giá trị** (`create([... => $x->col])`, `only([...])`, `response()->json`) — Eloquent nhận Enum lẫn chuỗi hợp lệ khi gán và serialize ra `->value`, nên không cần sửa file cũ. Có test đặc tả cho các đường đó (Task 1) | Để literal (rule R8 phải miễn trừ nhiều chỗ, mất ý nghĩa) |
| D5 | `QuestionType` đặt ở `Modules/QuestionBank/Enums/` (nguồn gốc của giá trị: `QuestionVersion.question_type`); `AttemptScorePolicy` ở `Modules/Assessment/Enums/`. Chỉ cast ở `AssessmentQuestion`/`AssessmentVersion`; **không cast `QuestionVersion`** (QuestionBank chưa refactor, hai module không đọc nó) | Đúng "Enum ở module sở hữu"; thư mục module chỉ chứa `Enums/` cho tới khi module đó được refactor (không thêm Provider) | Cast cả `QuestionVersion` (kéo theo controller QuestionBank cũ) |
| D6 | Ranh giới array/JSON/audit: mọi chỗ ghi giá trị Enum vào **mảng audit hoặc mảng dựng tay** dùng `->value` tường minh; trả Model/`only()` qua Resource thì để Laravel serialize (cho ra `->value`) và có test pin chuỗi | Audit `before/after` hôm nay lưu chuỗi; một Enum object rò vào `{$x}` (nội suy chuỗi) là lỗi nghiêm trọng (`Object of class ... could not be converted to string`) | Tin vào json_encode ở mọi nơi |
| D7 | Nội suy chuỗi: `FeedbackStatusTransitionInvalid::between(string, string)` giữ chữ ký chuỗi; `SetFeedbackStatus` truyền `->value` | Giữ nguyên message `Không thể chuyển Feedback từ 'draft' sang 'published'.`; test Task 1 pin message | Đổi chữ ký exception sang Enum |
| D8 | `SetFeedbackStatus::handle(Feedback, FeedbackStatus $status)`; `SetFeedbackStatusController` gọi `FeedbackStatus::from($request->validated('status'))`. Bảng chuyển trạng thái nằm trong Enum: `FeedbackStatus::next(): ?self` (draft→published→withdrawn→null), thay hằng `NEXT_STATUS` | Typed API; bỏ mảng khóa chuỗi | Giữ tham số `string` + `from()` trong Action |
| D9 | Validation `in:private,learner,parent_if_published` và `in:draft,published,withdrawn` đổi thành `Rule::enum(FeedbackVisibility::class)` / `Rule::enum(FeedbackStatus::class)`; test Task 1 pin `error.code` + message. Nếu message khác `in:` thì dùng `Rule::in(array_column(Enum::cases(), 'value'))` | Một nguồn giá trị; phải cùng tập giá trị và cùng thông báo | Giữ `in:` hardcode (lệch Enum khi thêm case) |
| D10 | Bộ lọc tự do `?grading_status=` của `ListSubmissions` **giữ kiểu `?string`**, không chuyển sang Enum | Hợp đồng hiện tại: "không validate, giá trị không khớp trả danh sách rỗng" (docblock của `ListSubmissionsRequest`); `GradingStatus::from()` sẽ ném lỗi | Validate/`tryFrom` (đổi hành vi) |
| D11 | `ResultAudience` (`student`, `parent`) là Enum của `ResultProgress`, tham số của `StudentAssessmentResultQuery::handle(..., ResultAudience $audience = ResultAudience::Student)`; ánh xạ audience → danh sách `FeedbackVisibility` là một `match` trong Query | Chuỗi `'student'/'parent'` là hằng do code quản lý; không phải vai trò đang hoạt động (cùng lý do D13 của plan ActiveRole) | Để chuỗi |
| D12 | `ResultCalculator::calculate()` trả `grading_status` là `GradingStatus` (không còn chuỗi) | Domain thuần, Enum là PHP thuần; `$result->update($computed)` nhận Enum | Trả chuỗi rồi cast ở Action |
| D13 | Factory/seeder tiếp tục dùng chuỗi | Eloquent nhận chuỗi hợp lệ khi gán vào thuộc tính cast Enum; không sửa file ngoài phạm vi | Đổi factory sang Enum |
| D14 | Test cũ được phép sửa chỉ khi đổi **kiểu so sánh**: `assertSame('x', $model->col)` → `assertSame('x', $model->col->value)` (kỳ vọng literal giữ nguyên), và 5 lời gọi `SetFeedbackStatus::handle($feedback, 'published')` → `FeedbackStatus::Published` (đổi chữ ký D8), và `ResultCalculatorTest` (`$result['grading_status']` → `->value`) | Playbook: chỉ sửa test cũ khi đổi cấu trúc/kiểu, không khi đổi hành vi | Sửa kỳ vọng |
| D15 | Rule kiến trúc **R8**: trong module thuộc `MODULES`, không được có **literal chuỗi** trùng giá trị của một Enum trong `app/Modules/*/Enums/` (trừ thư mục `Enums/`, comment, docblock). Ngoại lệ khớp nguyên văn `file + literal` ở `KNOWN_LITERALS`, tự hết hạn (test fail khi ngoại lệ không còn vi phạm) | Chặn việc quay lại chuỗi trạng thái; mẫu ngoại lệ tự hết hạn đã dùng cho R6/R7 | Cấm theo tên cột (nhiều false positive) |

### Danh sách Enum

| Enum | Namespace | Giá trị (theo migration) | Cột được cast | Nguồn |
|---|---|---|---|---|
| `AttemptStatus` | `App\Modules\SubmissionGrading\Enums` | `in_progress`, `submitted`, `timed_out`, `cancelled`, `awaiting_grading`, `graded` | `Attempt.status` | `2026_10_16_000001` |
| `SubmissionStatus` | `...SubmissionGrading\Enums` | `received`, `awaiting_grading`, `graded`, `invalidated` | `Submission.status` | `2026_10_16_000004` |
| `GradingMethod` | `...SubmissionGrading\Enums` | `automatic`, `manual` | `Grade.grading_method` | `2026_10_16_000005` |
| `FeedbackVisibility` | `...SubmissionGrading\Enums` | `private`, `learner`, `parent_if_published` | `Feedback.visibility` | `2026_10_16_000007` |
| `FeedbackStatus` | `...SubmissionGrading\Enums` | `draft`, `published`, `withdrawn` (+ `next()`) | `Feedback.status` | `2026_10_16_000007` |
| `GradingStatus` | `App\Modules\ResultProgress\Enums` | `pending`, `partial`, `complete` | `Result.grading_status` | `2026_10_16_000009` |
| `PublicationStatus` | `...ResultProgress\Enums` | `unpublished`, `published` | `Result.publication_status` | `2026_10_16_000009` |
| `ResultPublicationAction` | `...ResultProgress\Enums` | `published`, `withdrawn` | `ResultPublication.action` | `2026_10_16_000011` |
| `ResultAudience` | `...ResultProgress\Enums` | `student`, `parent` | (tham số Query, không phải cột) | — |
| `QuestionType` | `App\Modules\QuestionBank\Enums` | `single_choice`, `multiple_choice`, `true_false`, `fill_in_the_blank`, `short_answer`, `essay`, `matching`, `ordering`, `file_upload` | `AssessmentQuestion.question_type` | `2026_10_15_000013` |
| `AttemptScorePolicy` | `App\Modules\Assessment\Enums` | `highest`, `latest` | `AssessmentVersion.attempt_score_policy` | `2026_10_15_000011` |

Tên case: PascalCase của giá trị (`InProgress`, `AwaitingGrading`, `ParentIfPublished`, `FillInTheBlank`...).

### Literal còn lại sau task (không phải của Enum nào ở trên; xử lý bằng `KNOWN_LITERALS` của R8)

| File | Literal | Lý do |
|---|---|---|
| `SubmissionGrading/Actions/StartAttempt.php` | `'published'` (`$assessment->status !== 'published'`) | Trạng thái `Assessment` (D3), chờ Enum của module `Assessment` |
| `ResultProgress/Actions/PublishResult.php` | `'manual'` (`'trigger' => $auto ? 'auto_release' : 'manual'`) | Giá trị audit `trigger`, không phải `GradingMethod`; trùng giá trị chữ |
| `ResultProgress/Queries/StudentProgressQuery.php` | `'cancelled'` (`assessment.status != 'cancelled'`) | Trạng thái `Assessment` (D3), trùng giá trị chữ với `AttemptStatus::Cancelled` |
| `ResultProgress/Actions/BulkPublishResults.php` | `'published'` (khóa JSON `['published' => ..., 'skipped' => ...]`) | Tên khóa response, không phải `PublicationStatus` (phát hiện khi chạy R8) |
| `ResultProgress/Http/Requests/GetStudentProgressRequest.php` | `'student'` (`$this->route('student')`) | Tên route parameter, không phải `ResultAudience` (phát hiện khi chạy R8) |

(`StartAttempt` còn `'assigned'` của `StudentAssessment` — không trùng giá trị Enum nào nên không cần ngoại lệ; ghi vào "Nợ" của playbook.)

## Bảng ảnh hưởng (kết quả grep `app/ database/ routes/ config/ tests/`)

Quét: `grep -rnE "attempts|->result\b|submissions|->submission\b|grading_status|publication_status|grading_method|feedbacks|in_progress|awaiting_grading|invalidated|timed_out|parent_if_published|ResultPublication|->grades|question_type|attempt_score_policy"` và mọi file chứa class `Attempt|Submission|Grade|Feedback|Result|ResultPublication|AttemptQuestion|AssessmentQuestion|AssessmentVersion` ngoài hai module.

### Code ngoài hai module

| File:dòng | Đọc/ghi gì | Xử lý |
|---|---|---|
| `app/Models/Attempt.php:52` (`isTimedOut`) | so sánh `$this->status === 'in_progress'` | **Sửa** → `AttemptStatus::InProgress` (Task 3). Model dùng chung, bắt buộc |
| `app/Models/Attempt.php:78` (`attemptQuestionsForStudent`) | `'question_type' => $question->question_type` vào mảng trả JSON | **Sửa** → `->value` (Task 5), để JSON giữ chuỗi dù serialize cũng ra giá trị |
| `app/Models/{Attempt,Submission,Grade,Feedback,Result,ResultPublication,AssessmentQuestion,AssessmentVersion}.php` | khai báo `$casts` | **Sửa** (thêm cast) — đây là bản chất task |
| `app/Http/Controllers/Api/Assessment/CreateAssessmentVersionController.php:28` | `$current->only([..., 'attempt_score_policy', ...])` rồi `AssessmentVersion::create($fields + ...)` | **Không sửa**: `only()` trả Enum object, `create()` nhận Enum. Test đặc tả Task 1 (X2) |
| `.../CreateAssessmentVersionController.php:47` | `'question_type' => $question->question_type` vào `AssessmentQuestion::create` | **Không sửa**: nhận Enum. Test X2 |
| `.../CreateAssessmentVersionController.php:65` | `response()->json(['current_version' => $newVersion->load('questions')])` | **Không sửa**: Model serialize `attempt_score_policy`/`question_type` ra giá trị chuỗi. Test X2 |
| `.../AddAssessmentQuestionController.php:39` | `'question_type' => $sourceVersion->question_type` (chuỗi từ `QuestionVersion`, không cast) | **Không sửa**: chuỗi hợp lệ gán vào cast Enum. Test X3 |
| `.../CreateAssessmentController.php:23,57` | validate `in:highest,latest`, ghi `$data['attempt_score_policy'] ?? 'latest'` vào `AssessmentVersion` | **Không sửa**: chuỗi hợp lệ. Test X4 |
| `.../UpdateAssessmentController.php:31` | validate `in:highest,latest` rồi cập nhật | **Không sửa**: chuỗi hợp lệ |
| `.../GetAssessmentController.php:32-34` | `response()->json(['current_version' => $assessment->currentVersion])` | **Không sửa**: serialize ra chuỗi. Test X1 |
| `app/Http/Controllers/Api/File/LinkAttachmentController.php:21` | `'visibility' => in:private,learner,parent_if_published` cho `AttachmentLink` | **Không đụng**: model `AttachmentLink` (module File) không cast; cùng tập giá trị nhưng khác cột |
| `app/Http/Controllers/Api/QuestionBank/{CreateQuestion,UpdateQuestion,ListQuestions}Controller.php` | `in:single_choice,...` và `where('question_type', ...)` trên `QuestionVersion` | **Không đụng** (D5: không cast `QuestionVersion`) |
| `database/factories/{Attempt,Submission,Grade,Feedback,Result,AssessmentQuestion,AssessmentVersion,QuestionVersion}Factory.php` | giá trị mặc định là chuỗi hợp lệ | **Không sửa** (D13) |
| `app/Support/StudentAssessmentSync.php`, controller Assessment đọc `Assessment.status`/`StudentAssessment.status`/`AssessmentVersion.status` | trạng thái module Assessment | **Không đụng** (D3, không cast) |
| `app/Modules/Audit/AuditLogger.php`, `app/Modules/Notification/**`, `app/Providers/**`, `routes/api.php`, `config/**` | không đọc cột bị cast | **Không đụng** |
| `database/seeders/**` | không tạo dữ liệu các bảng này | **Không đụng** |

Kết luận: ngoài hai module, **chỉ hai Model dùng chung** (`Attempt.php`: dòng 52 và 78; các `$casts`) cần sửa. Không file controller/Service/Resource/Listener/factory/seeder cũ nào cần sửa.

### Test chạm vào các cột này (sửa theo D14)

- `assertSame('<literal>', $model->col)` với `col ∈ {status, publication_status, grading_status, grading_method}` ở: `Feature/Audit/ReissueAttemptAuditTest`, `Feature/Authorization/ActiveRoleMultiRoleTest`, `Feature/Result/PublishResultTest`, `Feature/ResultProgress/{RecalculateResultActionTest,ResultAuthorizationMatrixTest,ResultPublishRollbackTest}`, `Feature/Submission/{GetAttemptTest,SubmitAttemptTest}`, `Feature/SubmissionGrading/{AttemptAuthorizationMatrixTest,AttemptEndpointsBehaviorTest,FeedbackActionsTest,GradeActionsTest,GradingAuthorizationMatrixTest,GradingEndpointsBehaviorTest,ReissueAttemptActionTest,StartAttemptActionTest,SubmitAttemptActionTest}`, `Unit/Modules/ResultProgress/ResultCalculatorTest`. Cách sửa: thêm `->value` vào vế model; **kỳ vọng literal giữ nguyên**. Danh sách dòng chính xác lấy bằng `grep` ở Task 3/4 (mỗi task sửa phần cột của mình); `assertSame` đọc `$response->json(...)`, `assertDatabaseHas(...)`, `assertEquals` không đổi.
- `AttemptEndpointsBehaviorTest:225` (`assertSame($status, $att->fresh()->status)`, tham số hóa) → `->value`.
- Test gán chuỗi vào `Factory::create([...])`, `Model::create([...])`, `->update([...])` **không đổi** (Eloquent nhận chuỗi hợp lệ).
- `tests/Unit/Modules/SubmissionGrading/AutoGraderTest.php`, `BuildAttemptQuestionsTest.php`: dựng `new AssessmentQuestion(['question_type' => 'essay'])` bằng chuỗi → cast tự chuyển; không đổi.

## Review Focus (cho Opus)

1. **Enum object rò vào chuỗi/mảng:** nội suy (`"{$from}"`), `json_encode` thủ công, `implode`, `in_array(string, [...])`, so sánh `===`/`!==` với chuỗi còn sót, `match` trên chuỗi, mảng khóa là giá trị Enum. Rà `grep -rn "status\b" app/Modules` sau khi đổi.
2. **Audit/History giữ chuỗi:** `attempt.submit`, `attempt.reissue`, `result.publish`, `result.withdraw`, `result_histories.before_data/after_data`. Test: `StatusValueContractTest`, `ResultValueContractTest` (Task 1).
3. **Cast toàn app:** các đường chép giá trị trong `app/Http/Controllers/Api/Assessment` (X1-X4) và đường mặc định `?? 'latest'`.
4. **`Result` dùng ở cả hai module:** `SubmissionGrading` đọc `result?->publication_status` (GradeQuestion, ReissueAttempt, hai FormRequest) — phải đổi cùng lúc với cast `Result` (Task 4).
5. **Validation giữ thông báo:** `visibility`, `status` của feedback (test `test_create_feedback_rejects_unknown_visibility...`).
6. **Rule R8 không false positive:** `KNOWN_LITERALS` chỉ 5 ngoại lệ (đã tìm thấy lúc chạy: 3 dự kiến + khóa JSON `'published'` ở `BulkPublishResults`, tên route param `'student'` ở `GetStudentProgressRequest`); test hết hạn.
7. **Đột biến (Task 7):** bảng lỗi cố ý, cái nào sống sót và test bổ sung.

---

## Bản đồ file

| File | Trách nhiệm | Task |
|---|---|---|
| `tests/Feature/SubmissionGrading/StatusValueContractTest.php` (mới) | Ghim giá trị chuỗi trong JSON/audit/DB của attempt, submission, grade, feedback | 1 |
| `tests/Feature/ResultProgress/ResultValueContractTest.php` (mới) | Ghim giá trị chuỗi của result, publication, audit, history, visibility theo audience | 1 |
| `tests/Feature/Assessment/EnumColumnsJsonTest.php` (mới) | Ghim `question_type`, `attempt_score_policy` qua endpoint Assessment cũ | 1 |
| `app/Modules/SubmissionGrading/Enums/{AttemptStatus,SubmissionStatus,GradingMethod,FeedbackVisibility,FeedbackStatus}.php` (mới) | Enum | 2 |
| `app/Modules/ResultProgress/Enums/{GradingStatus,PublicationStatus,ResultPublicationAction,ResultAudience}.php` (mới) | Enum | 2 |
| `app/Modules/QuestionBank/Enums/QuestionType.php`, `app/Modules/Assessment/Enums/AttemptScorePolicy.php` (mới) | Enum dùng chung | 2 |
| `tests/Unit/Modules/{SubmissionGrading,ResultProgress,QuestionBank,Assessment}/Enums/*Test.php` (mới) | Tập giá trị từng Enum | 2 |
| `tests/Feature/Models/EnumCastsTest.php` (mới) | Cast round-trip + Enum khớp ràng buộc CHECK của DB | 3, 4, 5 |
| `app/Models/{Attempt,Submission,Grade,Feedback}.php` | Cast + `isTimedOut` | 3 |
| `app/Modules/SubmissionGrading/**` (Actions, Http/Requests, Http/Controllers) và `ResultProgress/Queries` (phần `Submission.status`, `Feedback.*`) | Dùng Enum nhóm attempt/submission/grade/feedback | 3 |
| `app/Models/{Result,ResultPublication}.php`, mọi code đọc `Result` ở hai module | Dùng Enum nhóm result | 4 |
| `app/Models/{AssessmentQuestion,AssessmentVersion}.php`, `AutoGrader`, `BuildAttemptQuestions`, 2 Query | Dùng `QuestionType`, `AttemptScorePolicy` | 5 |
| `tests/Architecture/ArchitectureTest.php` | R8 | 6 |
| `piclass-api/CLAUDE.md`; docs: playbook, `open-decisions.md`, `progress.md` | Ghi nhận | 8 |

---

## Chuẩn bị (trước Task 1)

- [ ] **P1: Kiểm tra container.** Lệnh `docker ps` ở trên. Expected: có `piclass-local-api-1`. Không có: DỪNG.
- [ ] **P2: Tạo branch.** Docs: đã tạo `docs/enums-submission-result` từ `origin/main`; commit plan này. BE: `git status --short` (rỗng), `git fetch origin`, `git checkout -b refactor/enums-submission-result origin/main`.
- [ ] **P3: Baseline.** Chạy full suite (nền). Expected: `694 passed`. Khác 694: DỪNG và báo con số.

---

### Task 1: Test đặc tả hành vi trên code cũ

Mục đích: ghim hành vi hiện tại của mọi đường mà Task 3-5 chạm vào mà chưa có test: **giá trị chuỗi** trong JSON, audit, history, DB và các message nội suy chuỗi. **Tất cả test của task này phải xanh trên code CŨ** (chưa có Enum nào). Test đỏ: DỪNG và báo.

**Files:**
- Create: `tests/Feature/SubmissionGrading/StatusValueContractTest.php`
- Create: `tests/Feature/ResultProgress/ResultValueContractTest.php`
- Create: `tests/Feature/Assessment/EnumColumnsJsonTest.php`

**Interfaces:** Consumes endpoint hiện có; Produces không có code sản phẩm. Fixture theo mẫu `AttemptEndpointsBehaviorTest`, `GradingEndpointsBehaviorTest`, `GetMyResultTest`.

- [ ] **Step 1: `StatusValueContractTest`** (đã có test cho phần này: shape/DB của vài giá trị; bảng dưới chỉ gồm chỗ **chưa** được ghim giá trị chuỗi). Mọi assertion dùng literal.

| Test | Thiết lập | Assertion |
|---|---|---|
| `test_start_attempt_response_and_row_carry_in_progress` | student, assessment published, SA `assigned` | 201; `attempt.status` = `'in_progress'`; row `attempts.status` = `'in_progress'` |
| `test_get_attempt_json_carries_status_and_question_type` | attempt `in_progress` có 1 `AttemptQuestion` của `AssessmentQuestion` `question_type = 'multiple_choice'` | `attempt.status` = `'in_progress'`; `attempt.attempt_questions.0.question_type` = `'multiple_choice'` |
| `test_start_limit_ignores_cancelled_attempts` | `max_attempts = 1`, đã có 1 attempt `cancelled` | start → 201 (ghim `status != cancelled`) |
| `test_submit_auto_gradable_quiz_marks_everything_graded` | 1 câu `single_choice` có đáp án đúng, answer đúng | 200; `submission.status` = `'graded'`; `result.grading_status` = `'complete'`; `result.publication_status` = `'published'`; row `attempts.status` = `'graded'`; row `grades.grading_method` = `'automatic'`; audit `attempt.submit`: `before_data` = `['status' => 'in_progress']`, `after_data['status']` = `'graded'` |
| `test_submit_with_a_manual_question_leaves_everything_awaiting_grading` | 1 câu `essay`, answer chuỗi không rỗng | 200; `submission.status` = `'awaiting_grading'`; `result.grading_status` = `'pending'`; row `attempts.status` = `'awaiting_grading'`; audit `after_data['status']` = `'awaiting_grading'` |
| `test_reissue_response_carries_in_progress_and_old_attempt_is_cancelled` | admin, attempt `exam` `in_progress` có submission `graded` | 200; `attempt.status` = `'in_progress'`; old row `'cancelled'`; submission `'invalidated'` |
| `test_grade_question_response_carries_manual_method` | admin chấm essay | 200; `grade.grading_method` = `'manual'`; `result.publication_status` = `'unpublished'` |
| `test_grade_submission_response_carries_manual_method` | admin chấm tổng | 200; `grade.grading_method` = `'manual'` |
| `test_create_feedback_echoes_each_visibility` (DataProvider `private`, `learner`, `parent_if_published`) | admin | 201; `feedback.visibility` = giá trị gửi; `feedback.status` = `'draft'` |
| `test_create_feedback_rejects_unknown_visibility` | `visibility = 'everyone'` | 422, `error.code` = `'VALIDATION_FAILED'`, `error.details.visibility` = mảng message hiện tại (copy chính xác từ kết quả chạy trên code cũ) |
| `test_set_status_rejects_unknown_status` | `status = 'archived'` | 422, `error.code` = `'VALIDATION_FAILED'`, `error.details.status` = mảng message hiện tại |
| `test_set_status_draft_to_draft_is_a_transition_error_with_the_values_in_the_message` | feedback `draft`, gửi `draft` | 409, `error.code` = `'FEEDBACK_STATUS_TRANSITION_INVALID'`, `error.message` = `"Không thể chuyển Feedback từ 'draft' sang 'draft'."` |
| `test_set_status_withdrawn_back_to_published_is_a_transition_error` | feedback `withdrawn`, gửi `published` | 409, message `"Không thể chuyển Feedback từ 'withdrawn' sang 'published'."` |
| `test_submission_detail_json_carries_every_status_value` | admin; submission `awaiting_grading`, attempt `awaiting_grading`, 1 grade `manual`, result `partial`/`unpublished`, feedback `parent_if_published`/`published` | `submission.status`, `submission.attempt.status`, `submission.grades.0.grading_method`, `submission.result.grading_status`, `submission.result.publication_status`, `submission.feedback.0.visibility`, `submission.feedback.0.status` đúng các literal trên |
| `test_submission_list_json_carries_status_values` | cùng fixture | `data.0.status`, `data.0.result.grading_status`, `data.0.result.publication_status` |

- [ ] **Step 2: `ResultValueContractTest`**

| Test | Thiết lập | Assertion |
|---|---|---|
| `test_publish_response_and_publication_row` | admin, result `complete`/`unpublished` | 200; `result.publication_status` = `'published'`, `result.grading_status` = `'complete'`; row `result_publications.action` = `'published'`; audit `result.publish`: `after_data` = `['publication_status' => 'published', 'trigger' => 'manual']` (so sánh mảng chính xác) |
| `test_withdraw_response_publication_row_and_audit` | result `published` | 200; `result.publication_status` = `'unpublished'`; row mới `action = 'withdrawn'`; audit `result.withdraw` `after_data` = `['publication_status' => 'unpublished']` |
| `test_bulk_publish_skips_every_non_complete_result_with_the_legacy_reason` | 3 result: `complete`, `partial`, `pending` (3 submission) | 200; `published` = [id complete]; `skipped` gồm 2 phần tử `reason` = `'grading chưa hoàn tất'`; 2 result kia vẫn `unpublished` |
| `test_regrading_a_published_result_writes_history_with_status_strings` | result `complete`/`published` + grade tổng; admin chấm lại tổng bằng `reason` | `result_histories.before_data['grading_status']` = `'complete'`, `after_data['grading_status']` = `'complete'`; `before_data['score']`/`after_data['score']` khác nhau |
| `test_withdrawn_result_is_hidden_from_the_student` | result `published`, student GET `/me/assessments/{id}/result` → 200; admin withdraw; GET lần 2 | lần 2: 404, `error.code` = `'NOT_FOUND'`, `error.message` = `'Request failed.'` |
| `test_student_never_sees_draft_or_withdrawn_feedback` | result published; feedback `learner` với `draft`, `withdrawn`, `published` | chỉ 1 feedback (`published`) |
| `test_parent_never_sees_private_draft_or_withdrawn_feedback` | parent liên kết (mẫu `GetChildResultAndProgressTest`); feedback: `learner/published`, `parent_if_published/published`, `private/published`, `learner/draft`, `parent_if_published/withdrawn` | đúng 2 feedback (hai bản `published` của `learner` và `parent_if_published`) |
| `test_progress_pending_count_counts_unpublished_incomplete_results_only` | student có 1 result `complete`/`published`, 1 `partial`/`unpublished`, 1 `pending`/`unpublished` (submission `received`), 1 `complete` nhưng submission `invalidated` | `pending_count` = 2, `graded_count` = 1 |

- [ ] **Step 3: `EnumColumnsJsonTest`** (endpoint Assessment cũ)

| Test | Thiết lập | Assertion |
|---|---|---|
| `X1 test_get_assessment_json_carries_policy_and_question_type` | teacher/admin; version `attempt_score_policy = 'highest'`, 1 question `matching` | `assessment.current_version.attempt_score_policy` = `'highest'`; `assessment.current_version.questions.0.question_type` = `'matching'` |
| `X2 test_new_version_copies_policy_and_question_types_into_the_json` | như `CreateAssessmentVersionTest` nhưng version gốc `highest` + question `ordering` | 201; `assessment.current_version.attempt_score_policy` = `'highest'`; `...questions.0.question_type` = `'ordering'`; row mới trong DB cùng giá trị |
| `X3 test_adding_a_question_copies_the_question_type_from_the_source_version` | `QuestionVersion` `short_answer` thêm vào version draft | 201; `assessment_question.question_type` = `'short_answer'` |
| `X4 test_creating_an_assessment_stores_the_requested_policy` | `POST /assessments` với `attempt_score_policy = 'highest'`, rồi không gửi (mặc định) | row version `highest`; lần không gửi `latest` |

(Nếu endpoint thật đòi thêm trường bắt buộc, copy fixture từ `CreateAssessmentTest`/`AssessmentQuestionsTest`.)

- [ ] **Step 4: Chạy trên code cũ.** `php artisan test tests/Feature/SubmissionGrading/StatusValueContractTest.php tests/Feature/ResultProgress/ResultValueContractTest.php tests/Feature/Assessment/EnumColumnsJsonTest.php`. Expected: tất cả xanh. Đỏ: DỪNG.
- [ ] **Step 5: Pint + commit.**

```bash
git add tests/Feature
git commit -m "test: characterize status string values before introducing enums

Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>"
```

**Tiêu chí xong:** các test mới xanh trên code cũ; không file `app/` nào đổi.

---

### Task 2: Tạo Enum và unit test tập giá trị (chưa dùng ở đâu)

**Files:** các file Enum ở "Bản đồ file"; `tests/Unit/Modules/<Module>/Enums/<Enum>Test.php` (mỗi Enum một file, kế thừa `PHPUnit\Framework\TestCase`, không DB).

- [ ] **Step 1: Viết unit test trước.** Mỗi Enum có:
  - `test_has_exactly_the_database_values`: `assertSame([...literal theo bảng "Danh sách Enum", đúng thứ tự migration...], array_column(Enum::cases(), 'value'))`.
  - `test_from_rejects_an_unknown_value`: `assertNull(Enum::tryFrom('bogus'))` và `tryFrom` chấp nhận từng giá trị.
  - `FeedbackStatusTest::test_next_follows_draft_published_withdrawn_only`: `Draft->next() === Published`, `Published->next() === Withdrawn`, `Withdrawn->next() === null`.
- [ ] **Step 2: Chạy để thấy đỏ** (`Class ... not found`).
- [ ] **Step 3: Tạo Enum.** Mẫu:

```php
<?php

namespace App\Modules\SubmissionGrading\Enums;

/** Lifecycle of an Attempt (attempts.status). */
enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case TimedOut = 'timed_out';
    case Cancelled = 'cancelled';
    case AwaitingGrading = 'awaiting_grading';
    case Graded = 'graded';
}
```

  `FeedbackStatus` thêm `public function next(): ?self { return match ($this) { self::Draft => self::Published, self::Published => self::Withdrawn, self::Withdrawn => null }; }`. Các Enum còn lại chỉ có case. `Private` là tên case hợp lệ (từ khóa được phép làm tên hằng); nếu `php -l` từ chối thì dùng `PrivateOnly` và báo trong báo cáo.
- [ ] **Step 4: Chạy test Enum**, Expected: xanh. Full suite: `694 + test Task 1 + test Task 2`.
- [ ] **Step 5: Pint + commit** (`feat: add backed enums for submission grading and result progress values`).

---

### Task 3: Nhóm Attempt / Submission / Grade / Feedback

**Files (sửa):**
- Models: `app/Models/Attempt.php`, `Submission.php`, `Grade.php`, `Feedback.php`.
- `app/Modules/SubmissionGrading/Actions/`: `CreateAttempt`, `CreateFeedback`, `GradeQuestion` (chỉ `grading_method`), `GradeSubmission`, `ReissueAttempt` (chỉ attempt/submission), `SaveAnswer`, `SetFeedbackStatus`, `StartAttempt` (chỉ `cancelled` đếm lượt), `SubmitAttempt`, `UpdateFeedback`.
- `app/Modules/SubmissionGrading/Http/Requests/{CreateFeedbackRequest,SetFeedbackStatusRequest}.php`, `Http/Controllers/SetFeedbackStatusController.php`.
- `app/Modules/ResultProgress/Queries/{PublishableResultsQuery,StudentAssessmentResultQuery,StudentProgressQuery}.php` (chỉ `Submission.status` = invalidated và `Feedback.status/visibility`).
- Test: `tests/Feature/Models/EnumCastsTest.php` (mới; phần nhóm này), sửa test cũ theo D14.

**Interfaces:** Consumes Enum Task 2. Produces: `Attempt::$status` là `AttemptStatus`, `Submission::$status` là `SubmissionStatus`, `Grade::$grading_method` là `GradingMethod`, `Feedback::$visibility`/`$status`.

- [ ] **Step 1: Viết `EnumCastsTest` cho nhóm này (đỏ).** Với mỗi (Model, cột, Enum, giá trị mẫu):
  - `test_attribute_is_cast_to_the_enum`: tạo bằng factory với **chuỗi**, `->refresh()`, `assertInstanceOf(Enum::class, $m->col)`, `assertSame(Enum::X, $m->col)`.
  - `test_enum_is_stored_as_its_string_value`: gán Enum object, lưu, đọc `DB::table(...)->value(col)` = literal; `getRawOriginal(col)` là chuỗi.
  - `test_enum_cases_match_the_database_check_constraint` (Postgres): đọc `pg_get_constraintdef` của ràng buộc CHECK trên cột (query `pg_constraint` theo `conrelid` và `pg_get_constraintdef(oid)` chứa tên cột), bắt mọi chuỗi trong dấu nháy đơn bằng regex `'([a-z_]+)'::character varying`, `assertEqualsCanonicalizing($dbValues, array_column(Enum::cases(), 'value'))`. Nếu ràng buộc CHECK không tồn tại/không parse được cho một cột: DỪNG và báo.
  - `test_unknown_value_is_rejected_on_assignment`: gán `'bogus'` → `ValueError` (ghim hành vi cast, vì validate nằm ở Request).
- [ ] **Step 2: Thêm cast vào 4 Model.** `Attempt`: `'status' => AttemptStatus::class` (giữ `started_at`, `ends_at`). `Submission`: `'status' => SubmissionStatus::class`. `Grade`: thêm `protected $casts = ['grading_method' => GradingMethod::class];`. `Feedback`: `'visibility' => FeedbackVisibility::class, 'status' => FeedbackStatus::class`. `Attempt::isTimedOut()` so với `AttemptStatus::InProgress`.
- [ ] **Step 3: Chạy full suite để thấy đỏ ở đâu.** Mục đích: nhìn danh sách fail để đối chiếu "Bảng ảnh hưởng" (các fail dự kiến: so sánh `===` với chuỗi trong Action, `assertSame` của test cũ). Fail ngoài hai dạng này: DỪNG.
- [ ] **Step 4: Sửa code theo bảng** (mỗi dòng: trước → sau; không đổi thứ tự điều kiện):

| File:dòng (trước khi sửa) | Sau |
|---|---|
| `Actions/CreateAttempt.php:32` | `'status' => AttemptStatus::InProgress` |
| `Actions/CreateFeedback.php:45` | `'status' => FeedbackStatus::Draft` |
| `Actions/GradeQuestion.php:34,51` | `GradingMethod::Manual` |
| `Actions/GradeSubmission.php:34` | `GradingMethod::Manual` |
| `Actions/ReissueAttempt.php:32` | `$old->status === AttemptStatus::Cancelled` |
| `Actions/ReissueAttempt.php:34-37` | `$statusBefore = $old->status;` giữ; audit `before: ['status' => $statusBefore->value]`; `submission->update(['status' => SubmissionStatus::Invalidated])` |
| `Actions/ReissueAttempt.php:51,64` | `AttemptStatus::Cancelled`; audit `after: ['status' => AttemptStatus::Cancelled->value, ...]` |
| `Actions/SaveAnswer.php:18` | `$attempt->status !== AttemptStatus::InProgress` |
| `Actions/SetFeedbackStatus.php` | chữ ký `FeedbackStatus $status`; bỏ `NEXT_STATUS`; `if ($locked->status->next() !== $status) throw FeedbackStatusTransitionInvalid::between($locked->status->value, $status->value);` `published_at` khi `$status === FeedbackStatus::Published` |
| `Actions/StartAttempt.php:41` | `->where('status', '!=', AttemptStatus::Cancelled)` (giữ nguyên các dòng 32-33, xem "Literal còn lại") |
| `Actions/SubmitAttempt.php:33,42` | `!== AttemptStatus::InProgress` |
| `Actions/SubmitAttempt.php:51` | `SubmissionStatus::Received` |
| `Actions/SubmitAttempt.php:58-59,67` | `$awaiting = $hasPendingManual;` `submission->update(['status' => $awaiting ? SubmissionStatus::AwaitingGrading : SubmissionStatus::Graded])`; `attempt->update(['status' => $awaiting ? AttemptStatus::AwaitingGrading : AttemptStatus::Graded])`; audit `before: ['status' => AttemptStatus::InProgress->value]`, `after: ['status' => $attempt->status->value, ...]` |
| `Actions/SubmitAttempt.php:123` | `GradingMethod::Automatic` |
| `Actions/UpdateFeedback.php:19` | `$locked->status === FeedbackStatus::Published` |
| `Http/Requests/CreateFeedbackRequest.php:20` | `['required', Rule::enum(FeedbackVisibility::class)]` |
| `Http/Requests/SetFeedbackStatusRequest.php:18` | `['required', Rule::enum(FeedbackStatus::class)]` |
| `Http/Controllers/SetFeedbackStatusController.php` | `$action->handle($feedback, FeedbackStatus::from($request->validated('status')))` |
| `ResultProgress/Queries/PublishableResultsQuery.php:27` | `->where('status', '!=', SubmissionStatus::Invalidated)` |
| `ResultProgress/Queries/StudentAssessmentResultQuery.php:20` | như trên |
| `ResultProgress/Queries/StudentAssessmentResultQuery.php:37-42` | `->where('status', FeedbackStatus::Published)`; `$allowedVisibility` giữ chuỗi `'student'/'parent'` ở task này, chuyển sang `ResultAudience` ở Task 4; trong task này thay danh sách bằng `[FeedbackVisibility::Learner]` / `[FeedbackVisibility::Learner, FeedbackVisibility::ParentIfPublished]` |
| `ResultProgress/Queries/StudentProgressQuery.php:14` | `SubmissionStatus::Invalidated` |
| `app/Models/Attempt.php:52` | `$this->status === AttemptStatus::InProgress` |

  `where('col', '!=', Enum)` hợp lệ vì query builder cast `BackedEnum` thành `->value` (Laravel ≥ 10); nếu bản đang dùng không hỗ trợ, test sẽ đỏ — khi đó thêm `->value` tại chỗ và ghi vào báo cáo.
- [ ] **Step 5: Sửa test cũ theo D14** cho cột `status` (attempt/submission/feedback) và `grading_method`: `grep -rnE "assertSame\(.*(->status|->grading_method)" tests`, thêm `->value` vào vế model; `SetFeedbackStatus::handle` trong `FeedbackActionsTest` (5 chỗ) → `FeedbackStatus::Published/Withdrawn`. Liệt kê từng file đã sửa trong báo cáo.
- [ ] **Step 6: Chạy:** `EnumCastsTest`, Task 1 (xanh trên code mới), toàn bộ `tests/Feature/SubmissionGrading tests/Feature/Submission tests/Feature/Grading tests/Feature/Result tests/Feature/ResultProgress tests/Feature/Audit tests/Feature/Notification tests/Feature/Authorization`.
- [ ] **Step 7: Full suite (nền).** Expected: 0 fail. Pint `--test` trên mọi file đã đổi. Commit `refactor: use enums for attempt, submission, grade and feedback values`.

---

### Task 4: Nhóm Result / ResultPublication / ResultAudience

**Files (sửa):** `app/Models/{Result,ResultPublication}.php`; `ResultProgress/Actions/{BulkPublishResults,PublishResult,RecalculateResult,WithdrawResult}.php`; `ResultProgress/Domain/ResultCalculator.php`; `ResultProgress/Queries/{PublishableResultsQuery,StudentAssessmentResultQuery,StudentProgressQuery}.php`; `ResultProgress/Http/Controllers/GetChildResultController.php`; `SubmissionGrading/Actions/{GradeQuestion,ReissueAttempt}.php` (đọc `Result.publication_status`); `SubmissionGrading/Http/Requests/{GradeQuestionRequest,GradeSubmissionRequest}.php`; test.

- [ ] **Step 1: Mở rộng `EnumCastsTest`** (đỏ) cho `Result.grading_status`, `Result.publication_status`, `ResultPublication.action`; thêm `Result` tạo bằng `Result::firstOrCreate([...], ['grading_status' => 'pending', ...])` vẫn lưu chuỗi.
- [ ] **Step 2: Cast.** `Result`: thêm `'grading_status' => GradingStatus::class, 'publication_status' => PublicationStatus::class` (giữ các cast hiện có). `ResultPublication`: `'action' => ResultPublicationAction::class`.
- [ ] **Step 3: Sửa code:**

| File:dòng | Sau |
|---|---|
| `Actions/BulkPublishResults.php:25` | `$result->grading_status !== GradingStatus::Complete` (giữ `'reason' => 'grading chưa hoàn tất'`) |
| `Actions/PublishResult.php:26` | `=== PublicationStatus::Published` |
| `Actions/PublishResult.php:30` | `!== GradingStatus::Complete` |
| `Actions/PublishResult.php:33,36` | `PublicationStatus::Published`; `ResultPublicationAction::Published` |
| `Actions/PublishResult.php:46` | `after: ['publication_status' => PublicationStatus::Published->value, 'trigger' => ...]` (giữ `'manual'`/`'auto_release'`, xem "Literal còn lại") |
| `Actions/RecalculateResult.php:35` | `['grading_status' => GradingStatus::Pending, 'publication_status' => PublicationStatus::Unpublished]` |
| `Actions/RecalculateResult.php:40,51` | `=== PublicationStatus::Published`, `=== GradingStatus::Complete`, `=== PublicationStatus::Unpublished`. `$before`/`after_data` là mảng `only([...])` có Enum: JSON ra chuỗi (đã có test Task 1); `$before !== $result->only(...)` so sánh mảng chứa Enum: case Enum là singleton nên `!==` đúng nghĩa |
| `Actions/WithdrawResult.php:20,25,28,38` | `PublicationStatus::Unpublished`, `ResultPublicationAction::Withdrawn`, audit `after: ['publication_status' => PublicationStatus::Unpublished->value]` |
| `Domain/ResultCalculator.php` | `gradingStatus()` trả `GradingStatus`; `calculate()` so sánh `!== GradingStatus::Complete`; docblock `grading_status: GradingStatus` |
| `Queries/PublishableResultsQuery.php:35` | `->where('publication_status', PublicationStatus::Unpublished)` |
| `Queries/StudentAssessmentResultQuery.php:22` | `->where('publication_status', PublicationStatus::Published)`; chữ ký `ResultAudience $audience = ResultAudience::Student`; `$allowedVisibility = match ($audience) { ResultAudience::Parent => [Learner, ParentIfPublished], ResultAudience::Student => [Learner] }` |
| `Queries/StudentProgressQuery.php:33,36` | `PublicationStatus::Published`; `->where('grading_status', '!=', GradingStatus::Complete)` |
| `Http/Controllers/GetChildResultController.php:16` | `$query->handle($assessment, $studentId, ResultAudience::Parent)` |
| `SubmissionGrading/Actions/GradeQuestion.php:30` | `=== PublicationStatus::Published` (import Enum của ResultProgress) |
| `SubmissionGrading/Actions/ReissueAttempt.php:42` | `=== PublicationStatus::Published` |
| `SubmissionGrading/Http/Requests/GradeQuestionRequest.php:24`, `GradeSubmissionRequest.php:22` | `=== PublicationStatus::Published` |

  `ListSubmissions` **không đổi** (D10).
- [ ] **Step 4: Sửa test cũ theo D14** cho `publication_status`, `grading_status` (model) và `ResultCalculatorTest` (`$result['grading_status']->value`).
- [ ] **Step 5: Chạy** `EnumCastsTest`, Task 1, và các thư mục test liên quan như Task 3. Full suite (nền). Pint. Commit `refactor: use enums for result grading, publication and audience values`.

---

### Task 5: `QuestionType` và `AttemptScorePolicy`

**Files (sửa):** `app/Models/{AssessmentQuestion,AssessmentVersion,Attempt}.php`; `SubmissionGrading/Domain/AutoGrader.php`; `SubmissionGrading/Actions/BuildAttemptQuestions.php`; `ResultProgress/Queries/{StudentAssessmentResultQuery,StudentProgressQuery}.php`; test.

- [ ] **Step 1: `EnumCastsTest`** (đỏ) cho `AssessmentQuestion.question_type` (9 giá trị: mỗi giá trị tạo được, đọc ra đúng case) và `AssessmentVersion.attempt_score_policy`. Test CHECK-constraint cũng áp dụng.
- [ ] **Step 2: Cast** `AssessmentQuestion`: `'question_type' => QuestionType::class`; `AssessmentVersion`: `'attempt_score_policy' => AttemptScorePolicy::class`.
- [ ] **Step 3: Sửa code:**

| File:dòng | Sau |
|---|---|
| `Domain/AutoGrader.php:17-22` | `match ($question->question_type) { QuestionType::SingleChoice, QuestionType::TrueFalse, QuestionType::MultipleChoice => gradeChoice; QuestionType::FillInTheBlank, QuestionType::ShortAnswer => gradeText; QuestionType::Matching, QuestionType::Ordering => gradeStructure; default => null }` (giữ comment `essay, file_upload`) |
| `Actions/BuildAttemptQuestions.php:41` | `in_array($question->question_type, [QuestionType::SingleChoice, QuestionType::MultipleChoice], true)` |
| `Queries/StudentAssessmentResultQuery.php:30-31` | `$policy === AttemptScorePolicy::Highest` |
| `Queries/StudentProgressQuery.php:48,50` | `... ->attempt_score_policy ?? AttemptScorePolicy::Latest`; `$policy === AttemptScorePolicy::Highest` |
| `app/Models/Attempt.php:78` | `'question_type' => $question->question_type->value` |

- [ ] **Step 4: Chạy** `EnumCastsTest`, `EnumColumnsJsonTest` (X1-X4 vẫn xanh **không sửa controller Assessment**), `AutoGraderTest`, `BuildAttemptQuestionsTest`, Result/Progress tests. Full suite (nền). Pint. Commit `refactor: use enums for question type and attempt score policy`.

---

### Task 6: Rule kiến trúc R8 (không literal giá trị Enum trong module)

**Files:** `tests/Architecture/ArchitectureTest.php`.

- [ ] **Step 1: Viết sanity test cho scanner (đỏ):** `test_literal_scanner_finds_enum_values_in_code_but_not_in_comments_or_docblocks`: mã giả chứa `if ($x === 'in_progress')`, `['status' => 'graded']`, `"published"` (dấu nháy kép không nội suy) → bắt đúng số dòng; chuỗi trong comment/docblock, chuỗi dài hơn giá trị (`'in_progress_foo'`, `'required|in:private,learner'`), tên khóa không trùng (`'score'`) → không bắt.
- [ ] **Step 2: Cài scanner.** Dùng `token_get_all` lấy token `T_CONSTANT_ENCAPSED_STRING`, bỏ dấu nháy, so sánh **bằng nguyên** với tập giá trị đọc từ mọi `app/Modules/*/Enums/*.php` (đọc bằng regex `case \w+ = '([^']+)'` trên source, không cần autoload). Quét mọi file `.php` của module trong `MODULES` **trừ** thư mục `Enums/`. Tập giá trị hợp lệ rỗng → test fail (guard "không quét gì").
- [ ] **Step 3: `KNOWN_LITERALS`** gồm đúng các mục ở bảng "Literal còn lại" (5 mục sau khi chạy thật) (`[file, literal, lý do]`), khớp theo `file + literal`. Test `test_known_literals_are_still_violations` fail với message "remove it" khi mục không còn vi phạm.
- [ ] **Step 4: Chạy** `--filter=ArchitectureTest`. Vi phạm ngoài danh sách đó: DỪNG (đó là chuỗi còn sót ở Task 3-5). Cập nhật docblock đầu class: thêm dòng R8.
- [ ] **Step 5: Chứng minh bắt được thật:** tạm thêm `$x = 'in_progress';` vào `Actions/SaveAnswer.php` trong **bản sao** (xem Task 7 cách làm), chạy ArchitectureTest → đỏ nêu `[R8]`.
- [ ] **Step 6: Pint + commit** `test: forbid raw enum value literals in refactored modules`.

---

### Task 7: Đột biến trên bản sao ngoài working tree

Container mount trực tiếp working tree, nên **không** sửa file gốc. Mỗi lần: tạo bản sao sạch, sửa **bản sao**, chạy test liên quan (hoặc full), ghi kết quả, xóa.

```bash
# Tạo bản sao (một lần, kể cả vendor) trong container
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 bash -lc "rm -rf /tmp/mut && cp -r /var/www/html /tmp/mut"'
# Sửa 1 dòng trong bản sao (sed) rồi chạy test ở đó
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec -w /tmp/mut piclass-local-api-1 php artisan test --filter=X'
# Hoàn tác bằng cách copy lại file từ /var/www/html; xóa cuối cùng
wsl.exe -d Ubuntu-22.04 -- bash -lc 'docker exec piclass-local-api-1 rm -rf /tmp/mut'
```

(DB test dùng chung với container: chạy tuần tự, không chạy song song với full suite.)

- [ ] **Step 1: Chạy từng lỗi cố ý sau; kỳ vọng test đỏ.** Ghi vào bảng báo cáo: lỗi, test đỏ đầu tiên, sống sót hay không.

| # | Lỗi cố ý (trong bản sao) | Kỳ vọng đỏ ở |
|---|---|---|
| M1 | `AttemptStatus::Graded = 'gradeed'` | `AttemptStatusTest`, `EnumCastsTest` (CHECK) |
| M2 | `SubmissionStatus::Invalidated = 'invalid'` | `SubmissionStatusTest` |
| M3 | Bỏ cast `Attempt.status` | `EnumCastsTest` |
| M4 | Bỏ cast `Feedback.visibility` | `EnumCastsTest`, `StatusValueContractTest` |
| M5 | `SaveAnswer`: `!== InProgress` → `!== Submitted` | `SaveAnswerActionTest`, `AttemptEndpointsBehaviorTest` |
| M6 | `SubmitAttempt`: đổi chỗ `AwaitingGrading` và `Graded` trong phép chọn trạng thái | `StatusValueContractTest`, `SubmitAttemptTest` |
| M7 | `SubmitAttempt`: audit `before` dùng `AttemptStatus::Submitted->value` | `StatusValueContractTest` (audit) |
| M8 | `ReissueAttempt`: audit `after` bỏ `->value` (Enum object) — kỳ vọng **sống sót** (json_encode cho cùng chuỗi): ghi nhận là đột biến tương đương, không bổ sung test | — |
| M9 | `ReissueAttempt`: `Invalidated` → `Received` | `ReissueAttemptActionTest`, `AttemptEndpointsBehaviorTest` |
| M10 | `SetFeedbackStatus`/`FeedbackStatus::next()`: `Published => Draft` | `FeedbackActionsTest`, `StatusValueContractTest` |
| M11 | `FeedbackStatusTransitionInvalid` nhận `$locked->status` object (bỏ `->value`) | `StatusValueContractTest` (message nội suy) |
| M12 | Bỏ `FeedbackVisibility::Private` | `FeedbackVisibilityTest`, `StatusValueContractTest` (data provider) |
| M13 | `PublishResult`: `ResultPublicationAction::Published` → `Withdrawn` | `ResultValueContractTest` |
| M14 | `WithdrawResult`: audit `after` đổi thành `PublicationStatus::Published->value` | `ResultValueContractTest` |
| M15 | `RecalculateResult`: `=== PublicationStatus::Published` → `=== PublicationStatus::Unpublished` (ghi history sai) | `ResultValueContractTest` (history), `RecalculateResultActionTest` |
| M16 | `ResultCalculator`: hoán đổi `Partial`/`Pending` | `ResultCalculatorTest` |
| M17 | `BulkPublishResults`: `!== Complete` → `!== Partial` | `ResultValueContractTest` (bulk) |
| M18 | `StudentAssessmentResultQuery`: audience `Parent` bỏ `ParentIfPublished` | `GetChildResultAndProgressTest`, `ResultValueContractTest` |
| M19 | `StudentAssessmentResultQuery`: `Student` thêm `ParentIfPublished` | `GetMyResultTest` |
| M20 | `StudentAssessmentResultQuery`/`StudentProgressQuery`: `Highest` → `Latest` | `GetMyResultTest`, `ProgressTest` |
| M21 | `AutoGrader`: bỏ `QuestionType::TrueFalse` khỏi nhánh choice | `AutoGraderTest` hoặc test mới (bổ sung nếu sống sót) |
| M22 | `BuildAttemptQuestions`: bỏ `MultipleChoice` khỏi danh sách xáo option | `BuildAttemptQuestionsTest` (bổ sung nếu sống sót) |
| M23 | `Attempt::isTimedOut`: `InProgress` → `Submitted` | `GetAttemptTest`/`SaveAnswerActionTest` |
| M24 | `StartAttempt`: đếm lượt `!= Cancelled` → `!= Graded` | `StatusValueContractTest::test_start_limit_ignores_cancelled_attempts` |
| M25 | `PublishableResultsQuery`: `Unpublished` → `Published` | `ResultAuthorizationMatrixTest`, `ResultValueContractTest` |
| M26 | Thêm `$x = 'graded';` vào một Action | `ArchitectureTest` (R8) |
| M27 | `AssessmentQuestion` bỏ cast `question_type` | `EnumCastsTest`, `AutoGraderTest` |

- [ ] **Step 2: Với mỗi đột biến sống sót (trừ M8):** thêm test vào chính bộ test liên quan (trên working tree gốc), chạy lại đột biến, thấy đỏ. Cập nhật bảng.
- [ ] **Step 3: Xóa `/tmp/mut`.** Xác nhận `git status` working tree gốc chỉ có thay đổi dự kiến (không dấu vết đột biến).
- [ ] **Step 4: Commit** test bổ sung (nếu có): `test: close enum mutation gaps`.

---

### Task 8: Kiểm tra cuối và cập nhật tài liệu

**Files:** `piclass-api/CLAUDE.md` (repo BE); docs: `docs/architecture/backend-refactor-playbook.md`, `docs/architecture/open-decisions.md` (ARCH-17), `docs/progress.md`.

- [ ] **Step 1: Pint** trên mọi file `.php` đã đổi: `FILES=$(git diff --name-only origin/main -- '*.php' | tr '\n' ' ')` rồi `vendor/bin/pint --test $FILES`. Expected: PASS.
- [ ] **Step 2: `piclass-api/CLAUDE.md`:**
  - Mục "Mặc định nên theo → Enum": thêm câu "Đã chuyển: `SubmissionGrading`, `ResultProgress` (Enum nằm ở `Enums/` của module sở hữu Model; Enum dùng chung đặt ở module sở hữu Model đó, module khác import; Enum phản chiếu toàn bộ giá trị cột DB). Rule R8 cấm literal giá trị Enum trong module đã refactor."
  - Câu `` `AttemptStatus` là Enum minh họa (code hiện tại còn dùng chuỗi, sẽ chuyển theo mục Enum). `` → `` `AttemptStatus` (`Modules/SubmissionGrading/Enums`) là Enum thật, cast trong `Attempt`. ``
  - Mục "Rule cốt lõi": `R1`-`R7` → `R1`-`R8`.
  - Commit: `docs: record enum conversion of SubmissionGrading and ResultProgress`.
- [ ] **Step 3: Full suite lần cuối (nền).** Ghi con số `Tests:`.
- [ ] **Step 4: Repo docs (branch `docs/enums-submission-result`):**
  - `backend-refactor-playbook.md`: xóa dòng nợ "Chuỗi trạng thái thay vì Enum"; sửa dòng "Query dựng mảng JSON và chứa rule chọn kết quả `highest/latest`" → sửa khi nào (Enum đã có, vẫn còn việc dời rule chọn sang `Domain/`); thêm dòng nợ mới: "Trạng thái module `Assessment` còn là literal trong `StartAttempt` (`'published'`, `'assigned'`) và `StudentProgressQuery` (`'cancelled'`); `PublishResult` có `'manual'` (audit trigger)" | `StartAttempt`, `PublishResult`, `StudentProgressQuery` | "Khi refactor Assessment (Enum `AssessmentStatus`, `StudentAssessmentStatus`) → xóa mục tương ứng trong `KNOWN_LITERALS`"; thêm dòng: "`QuestionVersion.question_type` và `AttachmentLink.visibility` chưa cast Enum" | `QuestionVersion`, `AttachmentLink` | "Khi refactor QuestionBank / File". Mục quy trình bước 4 vẫn giữ. Thêm vào "Giữ nguyên hành vi: các bẫy đã gặp" các bẫy mới: nội suy chuỗi với Enum; `Rule::enum` giữ message; Enum phải gồm toàn bộ giá trị cột; audit dùng `->value`; cast Enum trên Model dùng chung kéo theo test `assertSame('x', $m->col)`.
  - `open-decisions.md` ARCH-17: ở "Làm rõ thêm (a)" ghi "(đã thực hiện 2026-10-20, branch `refactor/enums-submission-result`)".
  - `progress.md`: ghi kết quả (số Enum, số test, branch local chưa push), "Việc kế tiếp: Task 9a refactor `Attendance`."
  - Commit: `docs: record enum conversion and clear the enum refactor debt`.
- [ ] **Step 5: Báo cáo cho user và DỪNG.** Số test cuối, danh sách commit ở cả hai repo (`git log --oneline origin/main..HEAD`), bảng ảnh hưởng đã chạm, bảng đột biến, điểm DỪNG/không chắc, xác nhận chưa push; nhắc repo docs có commit chưa push.

**Tiêu chí xong:** toàn bộ test xanh, số test ≥ 694 + test mới; `pint --test` sạch trên file đã đổi; R8 xanh với đúng 5 ngoại lệ; mọi đột biến đỏ (trừ M8 tương đương); hai branch có commit local, chưa push.

---

## Self-review (người viết plan đã chạy)

- **Phạm vi:** 11 Enum (9 của hai module + `QuestionType` + `AttemptScorePolicy`); không đụng Enum trạng thái `Assessment` (D3), `QuestionVersion`, `AttachmentLink`.
- **Rủi ro cast toàn app:** bảng ảnh hưởng đã grep `app/ database/ routes/ config/ tests/`; ngoài hai module chỉ `Attempt.php` (2 dòng) và `$casts` của 8 Model được sửa; mọi đường chép giá trị trong controller Assessment cũ có test Task 1 (X1-X4).
- **JSON/audit/validation:** pin bằng literal ở Task 1; ranh giới mảng dùng `->value` (D6); validation giữ thông báo (D9, có test).
- **Chữ ký nhất quán:** `SetFeedbackStatus::handle(Feedback, FeedbackStatus)`; `StudentAssessmentResultQuery::handle(Assessment, string, ResultAudience = Student)`; `ResultCalculator::calculate(...)['grading_status']: GradingStatus`; `FeedbackStatusTransitionInvalid::between(string, string)`.
- **Đếm test:** 694 → +N1 (Task 1) → +N2 (Task 2, unit Enum) → +N3.. (cast, R8, test bổ sung đột biến). Ghi con số thực tế từng task; yêu cầu: 0 fail và không test cũ nào bị xóa.

---

## Kết quả thực thi (2026-10-20)

- Test: baseline 694 → Task 1 (+26) → Task 2 (+23) → Task 3 (+25) = 768 → Task 4 783 → Task 5 + R8 → Task 7 bổ sung 813; 0 fail.
- **Lệch so với plan, đã xử lý tại chỗ:** (1) R8 tìm thấy 2 literal ngoài 3 dự kiến (khóa JSON `'published'`, route param `'student'`) → thêm vào `KNOWN_LITERALS` (5 mục). (2) `AutoGraderTest::test_unknown_question_type_needs_manual_grading` dùng loại câu `'drawing'` (không bao giờ hợp lệ trong DB, ràng buộc CHECK chặn) nên cast `QuestionType` ném `ValueError` khi dựng câu hỏi; test được thay bằng `test_a_question_type_outside_the_enum_cannot_be_built` (hai loại thủ công thật `essay`/`file_upload` đã có test riêng). (3) Test `assertNotSame('in_progress', $attempt->refresh()->status)` (GetAttemptTest) và `pluck('status')` (GradingAuthorizationMatrixTest) cần sửa thêm ngoài danh sách `assertSame` trong plan. (4) Task 1 bỏ test chuyển trạng thái feedback và `pending_count` vì đã có (`GradingEndpointsBehaviorTest`); riêng `pending_count` hóa ra chưa phân biệt được nên được bổ sung ở Task 7 (`test_progress_pending_count_counts_unpublished_incomplete_results_only`). (5) Pint báo thêm `AttemptAuthorizationMatrixTest.php` lỗi sẵn trên `main` (fully_qualified_strict_types); file này có đổi nên đã chạy `pint` và chấp nhận thay đổi style một dòng.
- **Đột biến (Task 7):** 40 lỗi cố ý trên bản sao `/tmp/mut` trong container (đã xóa). Lần đầu 7 sống sót ngoài 2 đột biến tương đương (M8/M8b: bỏ `->value` ở audit, `json_encode` ra cùng chuỗi): M20/M20b/M20c (`highest` vs `latest`: fixture cũ điểm cao nhất trùng lượt cuối; thiếu nhánh `?? Latest` khi SA không có version), M22/M22b (xáo option theo loại câu), M34 (`pending_count`), M40 (idempotent publish). Đã bổ sung test (`ResultValueContractTest`, `BuildAttemptQuestionsTest`) và chạy lại: đều đỏ.
