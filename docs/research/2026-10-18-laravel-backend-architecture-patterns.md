# Report: Pattern/kiến trúc cho backend Laravel của PiClass

Ngày: 2026-10-18. Nguồn: agent `researcher` (không sửa file nào). Quy ước nhãn: **[Chính thức]** = docs Laravel; **[Cộng đồng]** = blog/forum/sách; **[Ý kiến tác giả]** = đánh giá của người viết report.

## 1. Kết luận trước

**Đề xuất:** Action classes thuần (plain PHP, không cài package) + tổ chức code theo module/domain trong cùng 1 app Laravel (modular monolith "tự tổ chức", không dùng nwidart) + FormRequest, Policy, API Resource. Event/Listener/Queue chỉ dùng cho phần *phụ* (notification, audit không bắt buộc đồng bộ). Eloquent dùng thẳng, không Repository. Không DDD/Hexagonal đầy đủ, không CQRS.

Lý do: hiện trạng PiClass đã là "mỗi endpoint một class" (137 single-action controller), nên Action class là bước tiếp theo tự nhiên — kéo logic nghiệp vụ ra khỏi controller vào Action, có chỗ chung cho transaction + audit + notification. Chi phí thấp, đúng với tài liệu kiến trúc đã chốt (modular monolith, tầng "Application/use case" tách khỏi controller).

## 2. Bối cảnh khớp với docs PiClass

- `docs/architecture/application-architecture.md` đã định nghĩa 4 tầng: API boundary, **Application/use case** (transaction, phối hợp module, audit, notification), Domain, Infrastructure.
- `module-boundaries.md` chia 12 module, "một module điều phối + transaction boundary".
- `technology-decisions.md` đã chốt Laravel Queue + Scheduler trên EC2 → Job/Listener có sẵn hạ tầng.
- Code hiện tại (logic trong controller) đang lệch với tài liệu, không phải ngược lại.
- Docs viết "Domain không phụ thuộc framework". Với Eloquent (Active Record) điều này không thực tế ở quy mô này; nên hiểu là "quy tắc nghiệp vụ nằm trong Action/Model, không nằm trong controller". Nên ghi chú vào `open-decisions.md` để tránh hiểu nhầm.

## 3. So sánh 6 hướng

### (1) Fat controller + Service class
- Là gì: controller mỏng hơn, đẩy logic sang `XxxService` gom nhiều method theo entity.
- Ưu: quen thuộc; ít file.
- Nhược: service dễ phình thành "God class" [Cộng đồng: nabilhassen.com].
- Phổ biến: rất phổ biến nhưng Laravel không cung cấp sẵn (không có `make:service`) [Cộng đồng].
- Chuyển đổi: thấp. Nhưng PiClass đã chọn 1 controller = 1 hành động, service gộp nhiều method lệch phong cách.
- Hợp PiClass: tạm được, không tối ưu. Service vẫn hợp cho thứ bao tích hợp ngoài (storage, mail).

### (2) Action classes
- Là gì: mỗi use case là 1 class, 1 hành động (`SubmitAttempt`, `PublishResult`), gọi từ controller/job/command. Hai cách: class PHP thường, hoặc `lorisleiva/laravel-actions`.
- Ưu: tên class = tên nghiệp vụ; dễ test; không phình; hợp single-action controller; transaction đặt đúng 1 chỗ.
- Nhược: nhiều file nhỏ; không có chuẩn bắt buộc, team tự giữ quy ước (Action không gọi Action lung tung, trả về gì...).
- Phổ biến: khá phổ biến, được Spatie/Brent Roose đưa vào "Laravel Beyond CRUD" [Cộng đồng]. `laravel-actions` ~2.9k sao; chưa xác minh được ngày release gần nhất.
- Chuyển đổi: thấp–trung (cắt thân controller vào Action, làm dần).
- Hợp PiClass: **rất hợp**. [Ý kiến tác giả] dùng plain Action, không cài package.

### (3) Service layer + Repository
- Là gì: thêm lớp Repository bọc truy vấn DB.
- Ưu: hợp đồng ổn định để mock; gom truy vấn phức tạp lặp lại.
- Nhược: với Eloquent, repository thường chỉ chép lại hàm Eloquent; lý do "đổi DB" yếu; ORM vẫn "rò" ra ngoài [Cộng đồng: Laracasts, dev.to, ucodesoft].
- Đồng thuận: không phải mặc định cho Laravel; chỉ cân nhắc cho model có truy vấn phức tạp/lặp lại.
- Chuyển đổi: cao, lợi ích thấp.
- Hợp PiClass: **không**. Dùng Eloquent thẳng + local scope/query object (ví dụ `ClassScope`).

### (4) Modular monolith
- Là gì: 1 app chia theo module nghiệp vụ, quy định module nào được gọi module nào.
- Hai cách: tự tổ chức theo domain (kiểu Spatie Beyond CRUD) hoặc package `nwidart/laravel-modules` (~6.2k sao, mạnh ở bật/tắt module runtime, hợp CMS) / `internachi/modular` (nhẹ, bám chuẩn Laravel hơn; so sánh chủ yếu từ phía internachi, chưa có đánh giá độc lập).
- Ưu: khớp `module-boundaries.md`; mỗi nhóm module làm riêng (đúng quy trình Step 9); giảm xung đột khi nhiều người sửa.
- Nhược: package thêm quy ước riêng; chia 12 module quá chi tiết dễ phụ thuộc vòng.
- Chuyển đổi: trung (di chuyển file, đổi namespace). Tự tổ chức rẻ nhất.
- Hợp PiClass: hợp ở dạng **thư mục theo module trong `app/`**, không cần package [Ý kiến tác giả]. Laravel không ép cấu trúc thư mục [Chính thức: Directory Structure].

### (5) DDD / Hexagonal / Clean đầy đủ
- Là gì: domain thuần, repository interface, adapter, aggregate, value object, mapper.
- Ưu: test domain sạch; độc lập framework.
- Nhược: Eloquent là Active Record nên không hợp DDD thuần; nhiều boilerplate; Brent Roose: với dự án lớn-nhưng-không-khổng-lồ, các paradigm này "gần như luôn là overkill" [Cộng đồng].
- Chuyển đổi: rất cao (gần như viết lại).
- Hợp PiClass: **không** ở giai đoạn này. Chỉ mượn từ vựng: ranh giới module, Ubiquitous Language (đã có trong `docs/domain`).

### (6) CQRS / Event-driven nhẹ
- CQRS đầy đủ = tách model ghi/đọc. "Nhẹ" = Event/Listener/Job của Laravel cho việc phụ.
- [Chính thức] listener queued; event/listener chạy sau commit (`ShouldDispatchAfterCommit`, `ShouldQueueAfterCommit`) — tránh gửi notification cho thay đổi bị rollback.
- Nhược: luồng khó theo dõi; lạm dụng thì khó debug.
- Hợp PiClass: hợp có chọn lọc; CQRS đầy đủ thì không.

## 4. Pattern bổ trợ (đều nên làm, nằm sẵn trong Laravel)

| Pattern | Vấn đề hiện tại | Lợi ích |
|---|---|---|
| FormRequest | validate inline ở ~60 controller, chỉ 2 FormRequest | Validate + authorize tách khỏi controller; test riêng; dùng lại |
| Policy/Gate | quyền rải: middleware role, `abort_if`, static `ClassScope` | Một chỗ xem "ai được làm gì". Giữ `ClassScope` làm query scope cho *phạm vi dữ liệu*, Policy cho *quyền hành động trên 1 object* |
| API Resource | chưa có, response tự dựng | JSON nhất quán, sửa 1 chỗ; hợp FE Next.js bám contract OpenAPI (Step 9) |
| DI qua container | 7 service tạo bằng `new` | Mock được; đổi implementation không sửa nơi gọi; gần như miễn phí |
| DB::transaction ở tầng use case | chỉ 3 controller dùng transaction | Mỗi Action ghi nhiều bảng bọc 1 transaction; khớp docs "không trả thành công nếu phần bắt buộc thất bại" |
| DTO (spatie/laravel-data) | chưa có | Hữu ích nhưng **chưa cần ngay** [Ý kiến tác giả]; dùng `$request->validated()` trước. Chưa xác minh trạng thái hiện tại của package |

## 5. Áp dụng cho "nộp bài → chấm → tính result → publish"

- Mỗi bước là 1 Action (`SubmitAttempt`, `AutoGradeSubmission`, `CalculateResult`, `PublishResult`).
- Ghi dữ liệu + **audit bắt buộc** nằm **trong cùng transaction** (docs yêu cầu đồng bộ); audit append-only là một phần của giao dịch, không đẩy qua queue.
- **Notification** qua event + queued listener, sau commit: gửi lỗi không làm hỏng việc nộp bài, có retry. Cần chốt cơ chế retry (mục `[CẦN QUYẾT ĐỊNH]` trong docs).
- Chấm tự động nặng có thể chuyển Job sau này; hiện chưa cần.

## 6. Ai được lợi gì

- **Founder/PM:** sửa rule ít vỡ chỗ khác; ước lượng dễ ("sửa rule nộp bài = sửa 1 file"); onboard dev mới nhanh.
- **Dev:** biết logic ở đâu; test từng use case; review PR nhỏ.
- **Vận hành:** lỗi notification không làm mất bài nộp; audit và dữ liệu luôn nhất quán.
- **Admin/Giáo viên:** quyền kiểm tra nhất quán một chỗ → ít lỗ hổng "xem được dữ liệu lớp khác".

## 7. Lộ trình từng bước nhỏ (mỗi bước merge riêng)

1. **Quy ước:** 1 trang trong `docs/architecture` (Action ở đâu, đặt tên, ai gọi ai, transaction ở Action). Chưa đổi code.
2. **Làm mẫu 1 use case** đáng giá nhất (nộp bài/chấm/publish): Action + transaction + audit trong transaction + FormRequest.
3. **Inject thay `new`** cho 7 service.
4. **Notification qua event + queued listener sau commit** cho các luồng chính.
5. **Policy** cho module nhạy cảm trước (Grading, Result, Attendance, Parent access), giữ `ClassScope` làm query scope.
6. **API Resource** khi chốt contract Step 9, theo module, đi kèm FE.
7. **Thư mục theo module** khi thêm code mới theo từng nhóm; không di chuyển hàng loạt.
8. **"Đi đến đâu sửa đến đó":** chạm controller cũ thì refactor theo mẫu; không làm đợt refactor lớn riêng.

## 8. KHÔNG nên làm (over-engineering)

- Repository cho mọi bảng.
- DDD/Hexagonal đầy đủ (aggregate, value object, domain thuần, mapper).
- CQRS tách read/write model, Event Sourcing.
- Cài `nwidart/laravel-modules` chỉ để "có module" (trừ khi cần bật/tắt module runtime).
- Microservices / message broker (docs V1 đã nói không cần).
- Interface cho mọi service khi chỉ có 1 implementation.
- Dùng event cho luồng chính hoặc cho audit bắt buộc.
- Refactor 137 controller một lượt.

## 9. Mức độ chắc chắn

- **Chính thức (xác minh):** Laravel không ép cấu trúc thư mục; có Events/Listeners/Jobs/Policies/FormRequest; event/listener có cơ chế sau-commit.
- **Cộng đồng (nhất quán nhiều nguồn):** Repository không nên là mặc định với Eloquent; DDD đầy đủ thường overkill với team nhỏ; Action và Service đều phổ biến, không có "chuẩn".
- **Chưa xác minh:** ngày release gần nhất của `laravel-actions`; trạng thái `spatie/laravel-data`; nội dung chương Actions của Beyond CRUD (chỉ qua tóm tắt tìm kiếm); không có khảo sát độ phổ biến 2025–2026 (dùng số sao/độ nhất quán bài viết để ước lượng).
- **Ý kiến tác giả:** plain Action thay vì package; chưa cần DTO ngay; thư mục theo module thay vì package module; thứ tự lộ trình.

## Nguồn

- Laravel 12 Directory Structure: https://laravel.com/docs/12.x/structure
- Laravel 12 Events (after-commit): https://laravel.com/docs/12.x/events
- Action vs Service: https://nabilhassen.com/action-pattern-in-laravel-concept-benefits-best-practices ; https://nabilhassen.com/laravel-service-pattern-issues ; https://laraveldaily.com/lesson/design-patterns/action-command-pattern ; https://laracasts.com/discuss/channels/laravel/what-is-the-difference-between-actions-and-services
- Repository + Eloquent: https://ucodesoft.substack.com/p/the-repository-pattern-in-laravel ; https://laraveldaily.com/lesson/structure-laravel-projects/repositories-why-not-use-them ; https://laracasts.com/discuss/channels/laravel/using-repository-pattern-in-laravel
- Laravel Beyond CRUD: https://spatie.be/courses/laravel-beyond-crud ; https://dev.to/brendt/laravel-beyond-crud-21ej
- laravel-actions: https://github.com/lorisleiva/laravel-actions
- nwidart/laravel-modules: https://github.com/nWidart/laravel-modules
- internachi/modular: https://packagist.org/packages/internachi/modular ; https://www.mintlify.com/internachi/modular/migration/from-nwidart
- DDD/Hexagonal trong Laravel: https://dev.to/mattnmoore/scaling-laravel-architecture-2ec ; https://medium.com/@mohamadshahkhajeh/hexagonal-architecture-in-laravel-scaling-beyond-mvc-b26b4975bf92
- Tài liệu nội bộ: docs/architecture/application-architecture.md ; docs/architecture/module-boundaries.md ; docs/architecture/technology-decisions.md
