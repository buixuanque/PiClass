# Playbook refactor BE sang quy ước module

File này dùng **chỉ trong đợt refactor** code cũ sang quy ước ở `piclass-api/CLAUDE.md` (`ARCH-17`). Quy ước viết code nằm ở CLAUDE.md; file này chỉ chứa những gì cần khi chuyển code cũ mà **giữ nguyên hành vi**. Refactor xong 12 module thì chuyển phần còn giá trị sang CLAUDE.md và xoá file này.

Plan tổng: `superpowers/plans/2026-10-18-be-architecture-refactor.md`. Quyết định khi thực thi: `superpowers/plans/2026-10-18-be-architecture-refactor-rulings.md`.

## Quy trình cho mỗi module (một PR)

1. Chạy test hiện có, ghi lại baseline.
2. Viết test đặc tả hành vi (characterization) **trên code cũ** (xem mục dưới), thấy xanh.
3. Chuyển controller → FormRequest + Action/Query + Resource, Policy thay helper phân quyền tĩnh.
4. Chuyển chuỗi trạng thái/loại của module sang Enum + cast.
5. Thêm module vào `MODULES` trong `tests/Architecture/ArchitectureTest.php`.
6. Viết ma trận test phân quyền (vai trò × phạm vi × truy cập thẳng bằng ID) theo "Checklist test phân quyền" bên dưới, rồi **thử lỗi cố ý** (đột biến) trên bản sao ngoài working tree: bỏ một điều kiện, đổi thứ tự early-return, coi vai trò rỗng là admin. Test phải đỏ; lỗi cố ý nào test không bắt được thì bổ sung test.
7. Test cũ và mới đều xanh. Chỉ sửa test cũ khi đổi cấu trúc/namespace, không khi đổi hành vi.
8. Xử lý mục liên quan trong "Nợ đã biết" bên dưới: xoá dòng đã xong (lịch sử ở git).
9. Opus review độc lập toàn bộ branch của module trước khi merge (xem mục "Phân vai model").

## Phân vai model (chốt 2026-10-20)

- **Sonnet vừa lên plan vừa thực thi từng module.** Lý do: quyết định đúng lúc có đủ ngữ cảnh code, rẻ và nhanh hơn. Plan của module vẫn phải có: quy tắc "gặp điểm mơ hồ thì dừng và báo, không tự chọn"; test đặc tả hành vi viết trước khi sửa code; bước thử lỗi cố ý cho mọi Policy/hàm `ClassScope`; danh sách file được phép đụng.
- **Opus chỉ review một lần, ở cuối module, trên toàn bộ branch, trước khi merge.** Opus phải (1) đối chiếu các quyết định phân quyền trong plan với `docs/requirements` và `docs/access-control` (người lên plan và người viết code là cùng một model, nên không ai khác kiểm lại việc hiểu sai nghiệp vụ); (2) soát hành vi không đổi: so diff trước/sau từng Policy, thứ tự early-return, message; (3) tự thử lỗi cố ý trên bản sao tạm để tìm lỗ hổng test; (4) chỉ ra chỗ plan sai/thiếu. Người thực thi nộp kèm báo cáo gồm bảng lỗi cố ý đã thử và danh sách chỗ không chắc để Opus soi trước.
- **Module lớn thêm một điểm review giữa chừng:** UserManagement (30 controller, chia 2 PR: review từng PR) và Assessment (21 controller, ARCH-14 chưa chốt: review sau khi xong nhóm có audit và Policy). Module nhỏ review một lần ở cuối.
- **Nền tảng mới có tính phân quyền** (ví dụ `ActiveRole`, Task 0) vẫn do Opus lên plan và review từng task; sau đó không cần cho các module áp dụng lại khuôn.
- Căn cứ: ở Task 0, ba vòng review Opus đều tìm ra lỗ hổng test thật (lỗi cố ý vẫn qua test), trong đó lỗ hổng nặng nhất (phụ huynh xem mọi học sinh) do Sonnet tự bắt khi được yêu cầu thử lỗi cố ý. Vì vậy bước thử lỗi cố ý là bắt buộc, còn Opus soát lại một lần.

## Giữ nguyên hành vi: các bẫy đã gặp

- **Shape JSON cũ không bọc `data`:** Resource đặt `public static $wrap = null`.
- **Status 201 ngầm:** Resource bọc model vừa tạo (`wasRecentlyCreated`) tự trả 201. Endpoint cũ trả 200 thì ép lại trong `withResponse`.
- **Model khóa mất quan hệ đã load:** Action đọc lại model dưới `lockForUpdate` rồi trả instance đã khóa thì mất các quan hệ đã load trên model bound của route. Nếu JSON cũ chứa chúng: `$bound->setRawAttributes($locked->getAttributes(), true)` rồi trả `$bound`.
- **Thay helper phân quyền tĩnh bằng Policy:** chép **đúng thứ tự** early-return của helper (kiểm tra hồ sơ trước, miễn trừ người tạo/chủ sau). Thêm một dòng ma trận test cho từng tổ hợp thoát sớm. Đổi thứ tự là đổi quyền.
- **`denyAsNotFound` luôn truyền `DenialMessage::NOT_FOUND`**, nếu không client nhận "Not Found". Test vẫn ghim chuỗi literal `'Request failed.'`.
- **Validation-first:** sau khi chuyển sang FormRequest, 422 chạy trước 409. Endpoint có request vừa sai input vừa sai trạng thái: test ghim thứ tự mới (`VALIDATION_FAILED` + trường lỗi). Đã chấp nhận toàn cục (rulings #11).
- **Endpoint cũ dùng `Gate::authorize` trong controller:** chuyển vào `authorize()` của FormRequest bằng `Gate::inspect` để giữ status và message.
- **Cast Enum trên Model dùng chung gãy âm thầm mọi `=== 'chuỗi'` cũ:** grep toàn `app/ database/ routes/ tests/` cho từng cột trước khi cast, lập bảng ảnh hưởng. Với test: `assertSame('x', $m->col)` thành so Enum với chuỗi nên đỏ (sửa bằng `->value`, kỳ vọng literal giữ nguyên), nhưng `assertNotSame('x', $m->col)` thành đúng luôn (hết tác dụng, phải sửa cùng), và `pluck('col')` trả Enum.
- **Enum phải gồm toàn bộ giá trị cột DB**, kể cả giá trị code chưa dùng (cast ném `ValueError` khi đọc giá trị lạ). Test `EnumCastsTest` đối chiếu Enum với ràng buộc CHECK của Postgres để bắt lệch.
- **Enum rò vào chuỗi/mảng:** nội suy `"{$status}"` là lỗi nghiêm trọng (Enum không đổi sang chuỗi); mảng audit/mảng dựng tay dùng `->value` tường minh; so sánh bằng `===` với case, không `==` với chuỗi. Giữ chữ ký chuỗi cho exception có nội suy (truyền `->value`).
- **`Rule::enum` giữ message của `in:`** (`The selected visibility is invalid.`) chỉ khi dự án chưa có file `lang/` riêng ghi đè `validation.enum`/`validation.in`; test ghim message và `error.code` nên sẽ đỏ nếu điều kiện này đổi.
- **R8 so literal với Enum của chính module (đã xử lý ở Task 9a):** R8 chỉ lấy giá trị Enum trong `app/Modules/<Module>/Enums` cộng Enum dùng chung khai báo ở hằng `SHARED_ENUMS` của `ArchitectureTest` (`module => ['<ModuleSởHữu>/<TênEnum>']`), nên chuỗi vô can trùng chữ (role code `'student'`/`'parent'`, `'pending'`, `'draft'`...) không bị báo nhầm ở module mới. Module nào import Enum của module khác (`use App\Modules\X\Enums\Y`) phải khai báo vào `SHARED_ENUMS`; hai test `test_shared_enums_match_the_imports` đối chiếu hằng với import thật theo cả hai chiều. Hệ quả: một chuỗi chỉ được canh khi module import đúng Enum chứa giá trị đó (hai mục `KNOWN_LITERALS` cũ `'manual'` ở `PublishResult` và `'cancelled'` ở `StudentProgressQuery` hết vi phạm và đã xóa).
- **`Response::deny()` không message ra `This action is unauthorized.`:** endpoint cũ `abort(403)` không message trả `'Request failed.'`; Policy phải truyền message tường minh (`Response::deny('Request failed.')`) để giữ nguyên chuỗi. `denyAsNotFound` luôn bị handler đổi message về `'Request failed.'` (ARCH-20), nên controller cũ tự dựng message 404 riêng không giữ được qua Policy (Task 9a đã chuẩn hóa `GET /lesson-sessions/{id}/attendance`).
- **`authorize()` chạy trước `rules()`:** endpoint cũ validate (422) rồi mới kiểm tra quyền phụ thuộc input trong body (ví dụ Parent có liên kết với `student_id` trong body, `CreateAbsenceExplanationRequest`) thì đặt kiểm tra quyền trong `withValidator()->after()` và chỉ chạy khi validate đã qua, để request vừa sai input vừa sai quyền vẫn trả 422 như cũ. Quyền theo route-model binding thì dùng `authorize()` bình thường.
- **Thông báo chuyển sang Event + Listener `ShouldQueue` + `$afterCommit`:** `event_key/event_type/title/body/context` giữ nguyên, ghim bằng test; event mang theo giá trị cần cho `event_key` (ví dụ trạng thái lúc duyệt) thay vì đọc lại từ DB khi listener chạy; lỗi gửi thông báo không còn hủy nghiệp vụ chính.
- **R8 cố ý bỏ qua chuỗi validate dạng `in:a,b`** (cả chuỗi không bằng một giá trị Enum nên không khớp): dùng `Rule::enum(...)` thay vì `in:` hardcode là quy ước, nhưng không có test máy canh.
- **Test đột biến phải có fixture phân biệt được hai nhánh:** `highest` vs `latest` chỉ phân biệt được khi điểm cao nhất KHÔNG phải lượt làm cuối; `pending_count` chỉ phân biệt được khi số result hoàn tất khác số result chưa hoàn tất. Các test cũ trùng ngẫu nhiên nên lỗi cố ý lọt.

## Test đặc tả hành vi (characterization)

- Ghim **toàn bộ tập key** cấp trên của response (so sánh mảng key), không chỉ `assertJsonStructure` (không bắt được key bị mất).
- Ghim status từng endpoint, `error.code` và `error.message` cho mọi case 403/404/409/422.
- **Test rollback audit:** bind `AuditLogger` giả ném lỗi với message cố định; `assertSame` đúng message đó (để lỗi nghiệp vụ xảy ra sớm không làm test pass sai lý do); assert dữ liệu trước/sau không đổi; thêm điểm lỗi thứ hai ở bước tính lại (ví dụ `RecalculateResult`).

## Checklist test phân quyền cho mỗi Policy / hàm ClassScope

Rút ra từ các vòng review độc lập của đợt `ActiveRole`: những lỗ hổng test dưới đây đều từng lọt qua test "xanh". Mỗi Policy hoặc hàm `ClassScope` mới cần đủ:

- **Đối tượng của người/nhóm khác, với fixture có dữ liệu thật.** Ví dụ khóa X của teacher phải có enrollment của học sinh khác; fixture rỗng khiến điều kiện `student_id` bị bỏ mà test vẫn xanh.
- **Vai trò rỗng** (job/console không gọi `set()`) cho MỌI nơi dùng `ActiveRole` (Policy lẫn Query), không chỉ một Policy.
- **Liên kết/enrollment có `status != 'active'`** (đã kết thúc, tạm dừng...) phải bị từ chối.
- **Gọi Policy/Query trực tiếp**, không qua queue `sync`: `sync` dùng chung instance `ActiveRole` của caller, còn production dùng worker riêng thấy `null`.
- **Không dùng assert yếu** kiểu "endpoint trả 404 vì không có dữ liệu" để chứng minh phân quyền: dựng dữ liệu đã công bố/hợp lệ rồi mới assert bị chặn.
- **Thử đột biến** sau khi viết test phân quyền, trên bản sao ngoài working tree: bỏ một điều kiện, đổi thứ tự early-return, coi `null` là admin. Test phải đỏ; nếu vẫn xanh thì test chưa bảo vệ gì.
- **Chạy full suite** khi kiểm tra thứ tự early-return: các test ghim thứ tự nằm ở `StudentProgressAuthorizationTest` và `GradingAuthorizationMatrixTest`, không nằm trong thư mục `Authorization`.

## Nợ đã biết (đừng chép theo)

Code đã refactor hoặc code cũ dưới đây chưa đạt quy ước. Sửa khi refactor tới module liên quan, hoặc theo task riêng ghi bên cạnh.

| Nợ | Ở đâu | Sửa khi |
| --- | --- | --- |
| `ClassScope` chứa việc ngoài phạm vi dữ liệu: `authorizeTeacherWrite` (dùng `abort`) và tìm người nhận thông báo (`studentAndParentRecipients`, `activeRecipientsFor`) | `app/Support/ClassScope.php` | `authorizeTeacherWrite` → Policy khi refactor Lesson (Task 9e; Attendance đã chuyển sang `AttendanceRecordPolicy`, chỉ còn 5 controller Lesson gọi nó; 3 dòng của hàm là ngoại lệ trong `KNOWN_SUPPORT_VIOLATIONS` của `ArchitectureTest`, xóa entry khi dời hàm); người nhận → `Modules/Notification` khi refactor Notification |
| Controller cũ đọc `active_role_code` thẳng từ session | `app/Http/Controllers/Api/**`, `EnsureRole`, `MeController` (`ContextController` là nơi ghi, giữ) | Khi refactor module tương ứng: dùng `ActiveRole` (R7 tự canh khi module vào `MODULES`) |
| `ContextController` ghi session `active_role_code` sau khi `LoadActiveRole` đã đọc, nên trong chính request đổi vai trò `ActiveRole` còn giá trị cũ; hôm nay vô hại (không gọi Policy sau khi ghi) | `app/Http/Controllers/Api/Auth/ContextController` | Khi refactor Identity: cho nó gọi `ActiveRole::set($roleCode)` cùng lúc với `session()->put` |
| 2 controller cũ kiểm tra liên kết phụ huynh–học sinh inline | `Assessment/GetChildAssessmentController`, `Assessment/ListChildAssessmentsController` (trong `app/Http/Controllers/Api/`) | Khi refactor Assessment: dùng `ClassScope::parentCoversStudent` hoặc `StudentPolicy::viewAsParent` |
| Query dựng mảng JSON và chứa rule chọn kết quả `highest/latest` (Enum `AttemptScorePolicy` đã có) | `StudentAssessmentResultQuery` | Lần sửa tới của `ResultProgress`: rule chọn → `Domain/`, định dạng → Resource |
| Tên Query thiếu hậu tố `Query` | `GetSubmission`, `ListSubmissions` | Lần sửa tới của `SubmissionGrading` |
| DTO nằm trong `Actions/` | `SubmissionGrading/Actions/GradingOutcome.php` | Lần sửa tới của `SubmissionGrading`: chuyển sang `Data/` |
| Class nghiệp vụ static nằm ở `app/Support/` | `StudentAssessmentSync` (ghi `StudentAssessment` của Assessment), `TopicTagResolver` (chỉ QuestionBank dùng) | Khi refactor Assessment / QuestionBank: chuyển thành Action/class của module đó |
| `app/Services/Lesson/LessonSessionGenerator` | `app/Services` | Khi refactor Lesson (Phase D bỏ `app/Services`) |
| Static công khai nên ở `Domain/` | `BuildAttemptQuestions::order/optionOrderFor` | Lần sửa tới của `SubmissionGrading` |
| Trạng thái của module `Assessment` còn là literal trong module đã refactor: `StartAttempt` (`'published'` của `Assessment.status`, `'assigned'` của `StudentAssessment.status`), `StudentProgressQuery` (`'cancelled'` của `Assessment.status`; R8 theo module không còn báo vì `ResultProgress` không import `AttemptStatus`, nhưng vẫn là literal cần thay), `ReissueAttempt.php:34` (`'assignment'` của `Assessment.type`, không trùng giá trị Enum nào nên R8 không báo); `Assessment.status`, `AssessmentVersion.status`, `StudentAssessment.status`, `Assessment.type` chưa cast Enum vì ~25 controller cũ so sánh `=== 'published'` | `SubmissionGrading/Actions/StartAttempt.php`, `ResultProgress/Queries/StudentProgressQuery.php`, `app/Http/Controllers/Api/Assessment/**` | Khi refactor Assessment: thêm Enum trong `Modules/Assessment/Enums`, cast, xóa mục `StartAttempt` trong `KNOWN_LITERALS` của `ArchitectureTest`, thay literal ở `StudentProgressQuery` và khai báo Enum mới vào `SHARED_ENUMS` của hai module import nó |
| `QuestionVersion.question_type` và `AttachmentLink.visibility` chưa cast Enum (`QuestionType` đã có, mới cast ở `AssessmentQuestion`); `in:single_choice,...`, `in:private,learner,parent_if_published` còn hardcode ở controller cũ | `app/Models/QuestionVersion.php`, `app/Models/AttachmentLink.php`, controller QuestionBank / `File/LinkAttachmentController` | Khi refactor QuestionBank / File: cast Enum + `Rule::enum` |
| `app/Modules/QuestionBank/Enums` và `app/Modules/Assessment/Enums` là thư mục module chỉ có Enum (chưa có Provider/Actions) | `app/Modules/{QuestionBank,Assessment}` | Khi refactor hai module đó |
| `GET /lesson-sessions/{id}` (`Lesson/GetLessonSessionController`, Task 9e) còn phân biệt được "ngoài phạm vi" (`'Không tìm thấy buổi học.'`) với "không tồn tại" (`'Request failed.'`), lệch `ARCH-20` | `app/Http/Controllers/Api/Lesson/GetLessonSessionController.php` | Sửa sớm khi vào 9e: chuyển sang Policy, message về `'Request failed.'` như `GET .../attendance` đã làm ở Task 9a |
| Listener thông báo của Attendance tự dựng danh sách người nhận bằng query `User` theo `person_profile_id` (Teacher của lớp, Student, Parent) | `Attendance/Listeners/SendAbsenceExplanation*Notification` | Khi refactor Notification: dời việc tìm người nhận vào `Modules/Notification` (cùng khoản nợ với `ClassScope::studentAndParentRecipients`) |
| Khoảng cách code–tài liệu của Attendance (không chặn điểm danh buổi `cancelled` — bug thật so với `BR-ATT-004`; bulk mark không audit; bốn khoảng cách spec↔code nhỏ) | `Attendance/Actions/BulkMarkAttendance`, `GetSessionAttendance`, `ListAbsenceExplanations`, `ReviewAbsenceExplanation` | Cần quyết định/PR đổi hành vi riêng — xem `ARCH-22` ở `open-decisions.md` |
| R8 theo module có thể bỏ sót: module so literal với cột đã cast của Model module khác mà KHÔNG import Enum đó (ví dụ `$attempt->status === 'graded'`) thì R8 không bắt (bản gộp toàn cục trước đây bắt được) | `tests/Architecture/ArchitectureTest.php` (`enumValues`, `SHARED_ENUMS`) | Hướng: R8 lấy thêm Enum của các cột được cast trên mọi Model mà module tham chiếu |
| Nhánh teacher trong `AttendanceRecordPolicy` (ghi) và `AbsenceExplanationPolicy::review` bỏ qua vai trò đang hoạt động: user có hồ sơ teacher nhưng đang ở vai trò `parent` vẫn `allowed` (không tới được qua HTTP vì route `role:admin,teacher`; giống `authorizeTeacherWrite` cũ), lệch `ARCH-11` ở tầng Policy | `Attendance/Policies/{AttendanceRecordPolicy,AbsenceExplanationPolicy}.php` | Hướng: yêu cầu `code() === 'teacher'` (hoặc rỗng cho job), hoặc tách site theo vai trò. Thảo luận ở issue #21 |
| Lỗi có sẵn, không phải hồi quy: `GET /absence-explanations?student_id=abc` → 500 (Postgres báo uuid không hợp lệ) và `?status[]=x` → 500 | `Attendance/Http/Requests/ListAbsenceExplanationsRequest`, `AbsenceExplanationsQuery` | Khi có task sửa hành vi Attendance (validate bộ lọc → 422) |
| Vận hành: thông báo Attendance (và ResultProgress) phụ thuộc queue worker (`QUEUE_CONNECTION=database`) | `Attendance/Listeners/*` | Sau khi merge phải restart worker để nạp listener mới |
| Tên case của data provider trong test Attendance còn tiếng Anh (ví dụ `AbsenceExplanationBehaviorTest`, `AttendanceEndpointsBehaviorTest`) | `tests/Feature/Attendance/*` | Chờ user quyết có tính là "mô tả trong test" (phải tiếng Việt) không |
| `InvitePersonProfile` ghi `User::create` (Model của Identity) — rule R4 báo, đang nằm trong `KNOWN_VIOLATIONS` | `UserManagement/Actions/InvitePersonProfile` | Task 9g `Identity`: Action `CreateInvitedUser` hoặc chốt lại chủ sở hữu `User`; xóa mục `KNOWN_VIOLATIONS` |
| `RoleCode` Enum (4 role code) chưa có: `AssignUserRole` còn `in:admin,teacher,student,parent`, `EndUserRole` so literal `'admin'`/`'teacher'`, `MyProfileQuery` `match` theo literal; `ActiveRole::isAdmin` so `'admin'` | `UserManagement/Actions/{AssignUserRole,EndUserRole}`, `UserManagement/Queries/MyProfileQuery`, `app/Support/ActiveRole` | Task 9g `Identity`: tạo `Modules/Identity/Enums/RoleCode`, `Rule::enum`, xóa literal |
| Lỗi có sẵn, không phải hồi quy: `GET /users?name[]=x` → 500 (bộ lọc `name` ép mảng thành chuỗi) | `UserManagement/Queries/ListUsersQuery` | Khi có task sửa hành vi: validate bộ lọc `name`/`email`/`role`/`status` là chuỗi (422) |
| Đổi trạng thái lớp (`SchoolClass.status`: `preparing` ↔ `active`) nằm trong `AssignTeacherToClass`/`EndTeacherClassAssignment` của UserManagement (ghi Model của Academic qua instance nên R4 không thấy; hai literal `'active'` trong `KNOWN_LITERALS`) | `UserManagement/Actions/{AssignTeacherToClass,EndTeacherClassAssignment}` | Task 9f `Academic`: tách Action `TransitionClassStatus` (hoặc Enum `ClassStatus` + Action đổi trạng thái lớp), UserManagement gọi qua đó, xóa hai mục `KNOWN_LITERALS` |
| `ListAssessmentsRequest::rules()` rỗng có chủ đích (thêm validate đổi 200 thành 422): `GET /assessments?class_id=x\|course_id=x` còn 500 | `Assessment/Http/Requests/ListAssessmentsRequest` | Khi có task sửa hành vi: validate bộ lọc (`uuid`, `string`) trả 422 (`ARCH-24` b) |
| Quyết định `ARCH-14` (đồng bộ `StudentAssessment` khi học viên vào lớp/Course sau khi gắn audience) chưa chốt | `Assessment/Actions/AddAssessmentAudience`, `UserManagement/Actions/AddClassMember`, `Academic/EnrollInCourseController` | User chốt hướng (hook hay tính lúc đọc), làm PR đổi hành vi riêng |
