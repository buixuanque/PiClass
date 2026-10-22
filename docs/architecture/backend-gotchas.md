# Bẫy khi viết và sửa backend (Laravel module)

Rút ra từ đợt refactor 12 module sang quy ước `piclass-api/CLAUDE.md` (`ARCH-17`, hoàn tất 2026-10-22). Quy ước viết code nằm ở CLAUDE.md; file này giữ những bẫy đã gặp thật và cách viết test để không lặp lại. Lịch sử từng module: `superpowers/plans/` và `progress.md`; quyết định khi thực thi: `superpowers/plans/2026-10-18-be-architecture-refactor-rulings.md`.

## Bẫy khi giữ nguyên hành vi (đổi cấu trúc, đổi kiểu)

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
- **Bắt vi phạm unique trong vòng lặp ghi (Task 9f, `EnrollInCourse`):** Postgres hủy cả transaction đang mở khi một câu lệnh lỗi, nên mỗi `create` có thể vi phạm index unique phải bọc `DB::transaction` riêng (savepoint khi đã trong transaction ngoài, như `RefreshDatabase`), rồi mới `catch (QueryException)` và kiểm tên index. Bất biến là "mỗi `create` có savepoint riêng"; một transaction ngoài bọc cả vòng lặp vẫn đúng miễn là bất biến đó giữ. Test đua: dùng `DB::listen` chèn dòng trùng đúng sau câu kiểm tra trước (không dùng event `creating`, vì dòng chèn bị cuốn vào savepoint rồi bị hủy theo).
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

## Ghi trạng thái trong tài liệu (tránh lỗi thời)

- Trong `docs/progress.md` và bảng trạng thái của plan tổng **chỉ ghi trạng thái cuối** ("đã merge `main`" kèm số PR BE/docs), **không ghi trạng thái trung gian** ("chờ review", "chờ merge", "branch local"): lúc merge xong sẽ không ai quay lại sửa và tài liệu lệch thực tế.
- Việc cuối cùng của mỗi module, sau khi merge, là một commit tài liệu nhỏ: điền số PR và đổi "việc kế tiếp" sang module tiếp theo.
- Trước khi báo trạng thái cho user hoặc cho phiên khác, đối chiếu bằng `git log --merges` và `gh pr list`, không chỉ đọc `progress.md`.

## Quy trình đã dùng cho mỗi module (tham khảo khi sửa lớn một module)

1. Chạy test hiện có, ghi baseline. 2. Viết test đặc tả hành vi trên code cũ, thấy xanh. 3. Chuyển code, test cũ và mới đều xanh (chỉ sửa test cũ khi đổi cấu trúc/namespace, không khi đổi hành vi). 4. Ma trận test phân quyền rồi thử đột biến trên bản sao ngoài working tree (bản sao phải copy cả `vendor`, không symlink, vì autoload sẽ trỏ về code thật). 5. Một lượt review độc lập (model mạnh nhất) trên toàn branch trước khi merge. Với module lớn, review giữa chừng.
