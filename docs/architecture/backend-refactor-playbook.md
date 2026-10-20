# Playbook refactor BE sang quy ước module

File này dùng **chỉ trong đợt refactor** code cũ sang quy ước ở `piclass-api/CLAUDE.md` (`ARCH-17`). Quy ước viết code nằm ở CLAUDE.md; file này chỉ chứa những gì cần khi chuyển code cũ mà **giữ nguyên hành vi**. Refactor xong 12 module thì chuyển phần còn giá trị sang CLAUDE.md và xoá file này.

Plan tổng: `superpowers/plans/2026-10-18-be-architecture-refactor.md`. Quyết định khi thực thi: `superpowers/plans/2026-10-18-be-architecture-refactor-rulings.md`.

## Quy trình cho mỗi module (một PR)

1. Chạy test hiện có, ghi lại baseline.
2. Viết test đặc tả hành vi (characterization) **trên code cũ** (xem mục dưới), thấy xanh.
3. Chuyển controller → FormRequest + Action/Query + Resource, Policy thay helper phân quyền tĩnh.
4. Chuyển chuỗi trạng thái/loại của module sang Enum + cast.
5. Thêm module vào `MODULES` trong `tests/Architecture/ArchitectureTest.php`.
6. Viết ma trận test phân quyền (vai trò × phạm vi × truy cập thẳng bằng ID).
7. Test cũ và mới đều xanh. Chỉ sửa test cũ khi đổi cấu trúc/namespace, không khi đổi hành vi.
8. Xử lý mục liên quan trong "Nợ đã biết" bên dưới, gạch mục đã xong.

## Giữ nguyên hành vi: các bẫy đã gặp

- **Shape JSON cũ không bọc `data`:** Resource đặt `public static $wrap = null`.
- **Status 201 ngầm:** Resource bọc model vừa tạo (`wasRecentlyCreated`) tự trả 201. Endpoint cũ trả 200 thì ép lại trong `withResponse`.
- **Model khóa mất quan hệ đã load:** Action đọc lại model dưới `lockForUpdate` rồi trả instance đã khóa thì mất các quan hệ đã load trên model bound của route. Nếu JSON cũ chứa chúng: `$bound->setRawAttributes($locked->getAttributes(), true)` rồi trả `$bound`.
- **Thay helper phân quyền tĩnh bằng Policy:** chép **đúng thứ tự** early-return của helper (kiểm tra hồ sơ trước, miễn trừ người tạo/chủ sau). Thêm một dòng ma trận test cho từng tổ hợp thoát sớm. Đổi thứ tự là đổi quyền.
- **`denyAsNotFound` luôn truyền `DenialMessage::NOT_FOUND`**, nếu không client nhận "Not Found". Test vẫn ghim chuỗi literal `'Request failed.'`.
- **Validation-first:** sau khi chuyển sang FormRequest, 422 chạy trước 409. Endpoint có request vừa sai input vừa sai trạng thái: test ghim thứ tự mới (`VALIDATION_FAILED` + trường lỗi). Đã chấp nhận toàn cục (rulings #11).
- **Endpoint cũ dùng `Gate::authorize` trong controller:** chuyển vào `authorize()` của FormRequest bằng `Gate::inspect` để giữ status và message.

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
| `ClassScope` chứa việc ngoài phạm vi dữ liệu: `authorizeTeacherWrite` (dùng `abort`) và tìm người nhận thông báo (`studentAndParentRecipients`, `activeRecipientsFor`) | `app/Support/ClassScope.php` | `authorizeTeacherWrite` → Policy khi refactor Attendance/Lesson (3 dòng của `authorizeTeacherWrite` đang là ngoại lệ trong `KNOWN_SUPPORT_VIOLATIONS` của `ArchitectureTest`; xóa entry khi dời hàm); người nhận → `Modules/Notification` khi refactor Notification |
| Controller cũ đọc `active_role_code` thẳng từ session | `app/Http/Controllers/Api/**`, `EnsureRole`, `MeController` (`ContextController` là nơi ghi, giữ) | Khi refactor module tương ứng: dùng `ActiveRole` (R7 tự canh khi module vào `MODULES`) |
| `ContextController` ghi session `active_role_code` sau khi `LoadActiveRole` đã đọc, nên trong chính request đổi vai trò `ActiveRole` còn giá trị cũ; hôm nay vô hại (không gọi Policy sau khi ghi) | `app/Http/Controllers/Api/Auth/ContextController` | Khi refactor Identity: cho nó gọi `ActiveRole::set($roleCode)` cùng lúc với `session()->put` |
| 3 controller cũ kiểm tra liên kết phụ huynh–học sinh inline | `Assessment/GetChildAssessmentController`, `Assessment/ListChildAssessmentsController`, `Attendance/ReviewAbsenceExplanationController` (trong `app/Http/Controllers/Api/`) | Khi refactor Assessment / Attendance: dùng `ClassScope::parentCoversStudent` hoặc `StudentPolicy::viewAsParent` |
| Chuỗi trạng thái thay vì Enum (`'in_progress'`, `'graded'`...) | `SubmissionGrading`, `ResultProgress` | Một PR bổ sung Enum cho 2 module này |
| Query dựng mảng JSON và chứa rule chọn kết quả `highest/latest` | `StudentAssessmentResultQuery` | Khi bổ sung Enum cho `ResultProgress`: rule chọn → `Domain/`, định dạng → Resource |
| Tên Query thiếu hậu tố `Query` | `GetSubmission`, `ListSubmissions` | Lần sửa tới của `SubmissionGrading` |
| DTO nằm trong `Actions/` | `SubmissionGrading/Actions/GradingOutcome.php` | Lần sửa tới của `SubmissionGrading`: chuyển sang `Data/` |
| Class nghiệp vụ static nằm ở `app/Support/` | `StudentAssessmentSync` (ghi `StudentAssessment` của Assessment), `TopicTagResolver` (chỉ QuestionBank dùng) | Khi refactor Assessment / QuestionBank: chuyển thành Action/class của module đó |
| `app/Services/Lesson/LessonSessionGenerator` | `app/Services` | Khi refactor Lesson (Phase D bỏ `app/Services`) |
| Static công khai nên ở `Domain/` | `BuildAttemptQuestions::order/optionOrderFor` | Lần sửa tới của `SubmissionGrading` |
