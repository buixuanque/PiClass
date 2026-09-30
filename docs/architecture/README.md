# Step 8 — System Architecture

**Trạng thái:** Thiết kế kiến trúc logic V1 để làm đầu vào cho API Specification và Development Plan. Chưa phải quyết định implementation cho các mục được đánh dấu `[CẦN QUYẾT ĐỊNH]`.

## Mục tiêu

Mô tả các ứng dụng, module, luồng dữ liệu và trách nhiệm hạ tầng của PiClass đủ rõ để các bước API và kế hoạch phát triển có thể dựa vào. Kiến trúc phục vụ trước một tutoring class, đồng thời giữ khả năng mở rộng nhiều teacher, class và student mà không phải tách dịch vụ sớm.

## Phạm vi

Bao gồm system overview, kiến trúc logic frontend/backend, module boundary, luồng dữ liệu chính, authentication/security, file/notification, deployment logic, technology decisions và các câu hỏi kiến trúc còn mở. Không bao gồm API endpoint, migration, ORM, code, UI chi tiết, cấu hình cloud cụ thể hay feature ngoài V1.

## Nguyên tắc kiến trúc

1. **Ưu tiên modular monolith:** một backend application triển khai như một đơn vị; các module có trách nhiệm và giao diện nội bộ rõ. Chỉ tách dịch vụ khi có nhu cầu vận hành/scale được chứng minh.
2. **Giữ domain rules ở backend:** frontend hỗ trợ trải nghiệm; backend xác thực role, permission, scope, relationship, lifecycle và visibility trên mọi request.
3. **Tôn trọng source of truth:** business rules Step 5 quyết định hành vi; Database Design Step 6 xác định entity, quan hệ và lịch sử; Permission Step 7 giới hạn action và data scope.
4. **Lịch sử là dữ liệu nghiệp vụ:** archive/inactive/unlink không xóa lịch sử. Submission, grade, attendance, relationship, publication và audit có history theo các rule đã chốt.
5. **Module sở hữu nghiệp vụ:** module ghi/đọc dữ liệu thuộc trách nhiệm của nó qua application boundary; tránh module khác sửa bảng của nhau trực tiếp.
6. **V1 đơn giản, mở rộng bằng scale-out có điều kiện:** một frontend, một backend và PostgreSQL là topology logic ban đầu; background processing hoặc nhiều instance chỉ khi triển khai cần.
7. **Tệp và thông báo không cấp quyền:** quyền file theo object/visibility; notification/context phải kiểm tra lại quyền hiện hành.
8. **Không khóa công nghệ chưa được duyệt:** framework, host và vendor chưa chốt được đánh dấu rõ ở `technology-decisions.md`.

## Source of truth

Thứ tự khi thiết kế kỹ thuật:

1. `docs/requirements/*` — business rules Step 5 đã chốt.
2. `docs/access-control/*` — role, permission, data scope và access rules Step 7 đã chốt; phải nhất quán với Step 5.
3. `docs/database/*` — logical schema, quan hệ, constraint và lifecycle Step 6 đã chốt; không tự tạo security model mới.
4. `docs/product/*`, `docs/flows/*`, `docs/domain/*` — mục tiêu, scope, flow và thuật ngữ Step 1–4.
5. `docs/architecture/*` — cách tổ chức kỹ thuật; không được thay đổi rule nghiệp vụ ở trên.

Nếu phát hiện mâu thuẫn, ghi lại tại `open-decisions.md` và giải quyết ở artifact có thẩm quyền trước khi dùng kiến trúc làm hợp đồng implementation.

## Quyết định kiến trúc chính

| Chủ đề | Trạng thái | Quyết định |
|---|---|---|
| Kiểu backend V1 | Đề xuất kiến trúc | Modular monolith, triển khai một backend application; module boundary được mô tả riêng. |
| Client V1 | Đã chốt ở product scope | Các cổng Admin/Teacher, Student, Parent là các trải nghiệm theo role trên web; không có mobile app V1. Chi tiết tổ chức thành một hay nhiều frontend deployment còn mở. |
| Cơ sở dữ liệu | Đã chốt Step 6 | PostgreSQL, UUID PK; schema logic theo `docs/database/*`. |
| Notification | Đã chốt V1 | In-app notification; không mặc định email/SMS/LINE. |
| Lưu file | Chưa chốt vendor | Backend quản lý metadata và attachment context; nội dung file qua storage abstraction, object storage provider cần chọn. |
| Hosting/framework/auth transport | Cần quyết định | Không chọn vendor/framework, cookie hay token ở Step 8. |

## Tài liệu trong bộ này

- [System overview](system-overview.md)
- [Architecture diagram](architecture-diagram.md)
- [Application architecture](application-architecture.md)
- [Module boundaries](module-boundaries.md)
- [Data flow](data-flow.md)
- [Authentication and security](authentication-and-security.md)
- [File and notification](file-and-notification.md)
- [Deployment](deployment.md)
- [Technology decisions](technology-decisions.md)
- [Open decisions](open-decisions.md)

## Architecture Review Checklist

- [ ] Chấp nhận modular monolith cho backend V1 và boundary trong tài liệu.
- [ ] Đồng ý các cổng Admin/Teacher, Student, Parent dùng chung API và permission enforcement phía backend.
- [ ] Xác nhận PostgreSQL/UUID là quyết định từ Step 6 được kiến trúc kế thừa.
- [ ] Xác nhận notification V1 chỉ trong ứng dụng.
- [ ] Chốt tổ chức frontend và framework backend/frontend.
- [ ] Chốt phương án authentication transport/session và chính sách xác thực cụ thể.
- [ ] Chốt object storage, giới hạn file và cách phục vụ file private.
- [ ] Chốt hosting/deployment target, môi trường và quy trình release.
- [ ] Xác nhận backup, monitoring/logging và retention vận hành đáp ứng nhu cầu V1.
- [ ] Xác nhận không có module hay luồng nào vượt scope V1 hoặc mở rộng quyền Step 7.
- [ ] Sau khi duyệt, cập nhật status và quyết định vào tài liệu liên quan; chưa tự chuyển sang Step 9.
