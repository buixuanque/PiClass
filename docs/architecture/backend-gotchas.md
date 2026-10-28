# Quy tắc thực hành và bẫy khi viết backend

Bổ sung cho `piclass-api/CLAUDE.md` (quy ước chính). File này là các quy tắc thực hành áp dụng **từ nay** cho mọi thay đổi backend, rút ra từ những lỗi đã gặp thật khi đưa 12 module về cùng một quy ước (`ARCH-17`). Nợ kỹ thuật đã biết: `known-debt.md`. Quyết định lúc thực thi từng module: `archive/superpowers/plans/` và `archive/superpowers/plans/2026-10-18-be-architecture-refactor-rulings.md`.

## Response và HTTP

- **Không bọc `data` khi contract không có:** Resource đặt `public static $wrap = null` và tự dựng khóa ngoài.
- **Status 201 ngầm:** Resource bọc model vừa tạo (`wasRecentlyCreated`) tự trả 201. Endpoint phải trả 200 thì ép lại trong `withResponse`.
- **Model khóa mất quan hệ đã load:** Action đọc lại model dưới `lockForUpdate` rồi trả instance đã khóa thì mất các quan hệ đã load trên model bound của route. Nếu JSON cần chúng: `$bound->setRawAttributes($locked->getAttributes(), true)` rồi trả `$bound`.
- **Thứ tự kiểm tra:** 403/404 (quyền) → 422 (input) → 409 (trạng thái). Request vừa sai input vừa sai trạng thái trả 422 (`VALIDATION_FAILED` + trường lỗi); test ghim thứ tự này.
- **`authorize()` của FormRequest chạy trước `rules()`.** Quyền theo route-model binding dùng `authorize()` bình thường (`Gate::inspect`, không `Gate::authorize` trong controller, để giữ status và message). Quyền phụ thuộc input trong body (ví dụ Parent có liên kết với `student_id` trong body) đặt trong `withValidator()->after()` và chỉ chạy khi validate đã qua.
- **Message 403/404:** `denyAsNotFound` luôn truyền `DenialMessage::NOT_FOUND`, nếu không client nhận "Not Found" (handler đổi mọi 404 về `'Request failed.'`, `ARCH-20`). `Response::deny()` không message ra `This action is unauthorized.`, nên Policy truyền message tường minh, kể cả `Response::deny('Request failed.')` khi cần message chung.
- **Policy thay helper phân quyền:** giữ đúng thứ tự early-return (kiểm tra hồ sơ trước, miễn trừ người tạo/chủ sau). Thêm một dòng ma trận test cho từng tổ hợp thoát sớm; đổi thứ tự là đổi quyền.
- **Bộ lọc danh sách:** khi `rules()` rỗng có chủ đích (để giữ 200), input lạ có thể gây 500; ghi vào `known-debt.md`, không ghim 500 trong test.

## Enum

- **Cast Enum trên Model dùng chung làm gãy âm thầm mọi `=== 'chuỗi'` ở nơi khác:** grep toàn `app/ database/ routes/ tests/` cho từng cột trước khi cast, lập bảng ảnh hưởng. Với test: `assertSame('x', $m->col)` đỏ (sửa bằng `->value`, kỳ vọng literal giữ nguyên), nhưng `assertNotSame('x', $m->col)` thành đúng luôn (hết tác dụng, phải sửa cùng), và `pluck('col')` trả Enum.
- **Enum phải gồm toàn bộ giá trị cột DB**, kể cả giá trị code chưa dùng (cast ném `ValueError` khi đọc giá trị lạ). `EnumCastsTest` đối chiếu Enum với ràng buộc CHECK của Postgres.
- **Enum không rò vào chuỗi/mảng:** nội suy `"{$status}"` là lỗi nghiêm trọng; mảng audit/mảng dựng tay dùng `->value` tường minh; so sánh bằng `===` với case, không `==` với chuỗi. Exception có nội suy nhận chuỗi (truyền `->value`).
- **Validate:** dùng `Rule::enum(...)`, không `in:a,b` hardcode (không có test máy canh). `Rule::enum` giữ message của `in:` (`The selected visibility is invalid.`) chỉ khi dự án chưa có file `lang/` riêng ghi đè `validation.enum`/`validation.in`; test ghim message nên sẽ đỏ nếu điều kiện này đổi.
- **R8 và `SHARED_ENUMS`:** R8 chỉ so literal với Enum trong `app/Modules/<Module>/Enums` của chính module cộng Enum dùng chung khai báo ở hằng `SHARED_ENUMS` của `ArchitectureTest` (`module => ['<ModuleSởHữu>/<TênEnum>']`), nên chuỗi vô can trùng chữ (`'student'`, `'pending'`, `'draft'`...) không bị báo nhầm. Module nào import Enum của module khác (`use App\Modules\X\Enums\Y`) phải khai báo vào `SHARED_ENUMS`; hai test `test_shared_enums_match_the_imports` đối chiếu hằng với import thật theo cả hai chiều. Hệ quả: chuỗi chỉ được canh khi module import đúng Enum chứa giá trị đó (module so literal với cột đã cast mà không import Enum thì R8 không bắt được, xem `known-debt.md`).

## Thông báo và ghi DB

- **Thông báo = Event + Listener `ShouldQueue` + `$afterCommit`:** `event_key/event_type/title/body/context` được ghim bằng test; event mang theo giá trị cần cho `event_key` (ví dụ trạng thái lúc duyệt) thay vì đọc lại từ DB khi listener chạy; lỗi gửi thông báo không hủy nghiệp vụ chính. Người nhận lấy qua `NotificationRecipientsQuery`; test ghim chính xác ai nhận ở chỗ nối listener với Query (gọi nhầm method sẽ làm thừa hoặc mất thông báo).
- **Bắt vi phạm unique trong vòng lặp ghi:** Postgres hủy cả transaction đang mở khi một câu lệnh lỗi, nên mỗi `create` có thể vi phạm index unique phải bọc `DB::transaction` riêng (savepoint khi đã trong transaction ngoài, như `RefreshDatabase`), rồi mới `catch (QueryException)` và kiểm tên index. Bất biến: "mỗi `create` có savepoint riêng". Test đua dùng `DB::listen` chèn dòng trùng đúng sau câu kiểm tra trước (không dùng event `creating`, vì dòng chèn bị cuốn vào savepoint rồi bị hủy theo).
- **Khóa dòng:** dùng `FOR NO KEY UPDATE` (`->lock('for no key update')`), không `FOR UPDATE` trên dòng cha của khóa ngoại (gây deadlock giữa hai thao tác chéo). Kiểm tra điều kiện **sau** khi đã có khóa; test ghim thứ tự khóa trước, kiểm tra/ghi sau.

## Test

- **Ghim contract response:** so sánh **toàn bộ tập key** cấp trên (mảng key), không chỉ `assertJsonStructure` (không bắt được key bị mất); ghim status, `error.code` và `error.message` cho mọi case 403/404/409/422. Khi đổi một endpoint đang chạy, viết test ghim hành vi hiện tại trước, thấy xanh, rồi mới sửa; không sửa kỳ vọng cho khớp code.
- **Test rollback audit:** bind `AuditLogger` giả ném lỗi với message cố định; `assertSame` đúng message đó (để lỗi nghiệp vụ xảy ra sớm không làm test pass sai lý do); assert dữ liệu trước/sau không đổi; thêm điểm lỗi thứ hai ở bước tính lại (ví dụ `RecalculateResult`).
- **Đổi người đăng nhập giữa một test** (`actingAs` lần hai) trả 401: tách mỗi test một actor.
- **Checklist cho mỗi Policy / hàm `ClassScope`.** Các lỗ hổng dưới đây từng lọt qua test "xanh":
  - Đối tượng của người/nhóm khác, với fixture có dữ liệu thật (fixture rỗng khiến điều kiện `student_id` bị bỏ mà test vẫn xanh).
  - Vai trò rỗng (job/console không gọi `set()`) cho MỌI nơi dùng `ActiveRole` (Policy lẫn Query).
  - Liên kết/enrollment có `status != 'active'` phải bị từ chối.
  - Gọi Policy/Query trực tiếp, không qua queue `sync` (`sync` dùng chung instance `ActiveRole` của caller, production dùng worker riêng thấy `null`).
  - Không dùng assert yếu kiểu "endpoint trả 404 vì không có dữ liệu" để chứng minh phân quyền: dựng dữ liệu hợp lệ rồi mới assert bị chặn.
  - Chạy full suite khi kiểm tra thứ tự early-return: các test ghim thứ tự nằm ở `StudentProgressAuthorizationTest` và `GradingAuthorizationMatrixTest`, không nằm trong thư mục `Authorization`.
- **Thử đột biến** sau khi viết test phân quyền hoặc logic có điều kiện: bỏ một điều kiện, đổi thứ tự early-return, coi `null` là admin, gọi nhầm method ở chỗ nối. Làm trên **bản sao** ngoài working tree trong container (copy cả `vendor`, không symlink, vì autoload sẽ trỏ về code thật). Test phải đỏ; còn xanh thì bổ sung test. Đột biến tương đương (không đổi hành vi) ghi lại, không cần test.
- **Fixture phải phân biệt được hai nhánh:** `highest` vs `latest` chỉ phân biệt được khi điểm cao nhất KHÔNG phải lượt làm cuối; `pending_count` chỉ phân biệt được khi số result hoàn tất khác số chưa hoàn tất. Fixture trùng ngẫu nhiên làm lỗi cố ý lọt.
- **Review độc lập** (model Opus, effort medium; ngữ cảnh mới) trên toàn branch trước khi merge các thay đổi lớn hoặc đụng phân quyền; người review tự thử đột biến, không chỉ đọc diff. Việc của reviewer: đối chiếu quyết định phân quyền với `docs/requirements` và `docs/access-control`, soát hành vi không đổi, tìm lỗ hổng test.
- **Toàn bộ test đỏ đồng loạt (hàng nghìn test, lỗi "Session store not set") khi chạy trong container, dù code không đổi:** `SANCTUM_STATEFUL_DOMAINS` của biến môi trường container `api` (`piclass-infra`) đổi theo `WEB_PORT` thực tế (ví dụ `localhost:8000`), trong khi `TestCase::setUp()` luôn gửi `Referer: http://localhost:3000`. Khi hai giá trị lệch nhau, Sanctum không coi request của test là stateful nữa và session bị bỏ qua hoàn toàn. `phpunit.xml` không ghi đè được biến này (biến Docker thắng). Đã sửa cố định trong `tests/TestCase.php` (`config(['sanctum.stateful' => ['localhost:3000']])`), không phụ thuộc container nữa — nếu gặp lại kiểu lỗi này sau khi đổi cấu hình `piclass-infra`, kiểm tra chỗ này trước khi nghi ngờ code nghiệp vụ.

## Ghi trạng thái trong tài liệu (tránh lỗi thời)

- Trong `docs/STATE.md` và các bảng trạng thái của plan **chỉ ghi trạng thái cuối** ("đã merge `main`" kèm số PR BE/docs), **không ghi trạng thái trung gian** ("chờ review", "chờ merge", "branch local"): lúc merge xong sẽ không ai quay lại sửa và tài liệu lệch thực tế.
- Việc cuối cùng của mỗi đầu việc, sau khi merge, là một commit tài liệu nhỏ: điền số PR và đổi "việc kế tiếp".
- Trước khi báo trạng thái cho user hoặc cho phiên khác, đối chiếu bằng `git log --merges` và `gh pr list`, không chỉ đọc `STATE.md`.
