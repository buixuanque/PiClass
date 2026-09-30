# Kiến trúc ứng dụng

## Mô hình triển khai logic

V1 dùng một frontend web client và một backend modular monolith làm giả định kiến trúc. Backend có thể chạy trên một instance lúc đầu; bản thân boundary không cản trở chạy nhiều instance sau này. Đây là logical architecture, không bắt buộc framework, process count, repo layout hay UI composition.

## Backend layers

### Presentation / API boundary

Nhận yêu cầu từ web client, kiểm tra cấu trúc đầu vào cơ bản, xác định actor/context và chuyển cho use case. Trả kết quả theo contract sẽ được định nghĩa tại Step 9. Không nhúng business policy vào handler và không coi client-supplied role/object ID là bằng chứng quyền.

### Application / use case

Điều phối tác vụ nghiệp vụ có ý nghĩa với người dùng: tạo lớp/buổi, phát hành assessment, bắt đầu/nộp attempt, chấm/công bố kết quả, xem tiến độ. Tại boundary này cần áp dụng transaction thích hợp, phối hợp module, kiểm tra chính sách, ghi audit và phát sinh notification theo rules.

Các thao tác liên quan nhiều entity phải được thực hiện nhất quán, ví dụ submit và nhận thời điểm hệ thống; publish version và cấp StudentAssessment; sửa attendance/grade cùng history/audit.

### Domain

Chứa khái niệm, trạng thái và quy tắc nghiệp vụ: lifecycle Class/LessonSession/Assessment/Attempt/Result; tính eligible; assessment version snapshot; result publication; quyền theo role/scope. Domain không phụ thuộc framework web, vendor storage hay client UI.

Không thiết kế aggregate/class cụ thể ở Step 8. Cấu trúc đó sẽ được quyết định trong Development Plan/implementation khi bám schema và API.

### Infrastructure

Cung cấp persistence PostgreSQL, lưu/đọc file qua storage adapter, thời gian hệ thống, phát sinh event/notification, logging và cấu hình secrets. Infrastructure không định nghĩa nghiệp vụ; lỗi hạ tầng phải được chuyển thành kết quả có kiểm soát tại application boundary.

## Frontend logical areas

Frontend là client theo role, gồm các khu vực Admin/Teacher, Student và Parent. Chúng có thể dùng chung authentication/session, component nền và API client. Route guard chỉ hỗ trợ điều hướng; backend vẫn phải tái xác thực mọi quyền.

- **Admin/Teacher:** quản lý people/class, lịch và lesson, attendance, question bank, assessment, grading, kết quả, notification và chức năng audit theo role.
- **Student:** lịch/lớp của mình, assessment được giao, attempt/answer/submit, result/feedback đã công bố.
- **Parent:** dữ liệu child có relationship hiện hành, bao gồm lịch, attendance, assessment/result/feedback đã công bố, tệp có visibility phù hợp và notification còn được phép.

Một User có Admin và Teacher chọn context làm việc sau login. Frontend không hợp nhất quyền thành bypass; backend đánh giá action trong context phù hợp theo Step 7.

## Dependency rules giữa module

1. API boundary gọi application use case, không truy vấn bảng tùy ý.
2. Application module gọi khả năng công khai của module khác, không sửa trực tiếp dữ liệu sở hữu của module đó.
3. Domain không gọi framework, database driver, object storage hay notification vendor.
4. Infrastructure implement port/interface do application/domain cần.
5. Cross-cutting concern (authz, audit, time, file) không được trở thành đường vòng bỏ qua permission hoặc lifecycle.

## Giao dịch và xử lý bất đồng bộ

Các thay đổi cần đồng bộ giữa entity nghiệp vụ và history/audit phải hoàn tất nhất quán; không trả thành công nếu phần bắt buộc thất bại. Delivery notification có thể tách khỏi thao tác chính nếu cần độ bền vận hành, nhưng V1 không yêu cầu broker. `[CẦN QUYẾT ĐỊNH]` cơ chế job/queue và bảo đảm retry sẽ được chốt nếu implementation cần tác vụ nền.

## Frontend packaging

Một SPA/server-rendered app hay nhiều app deployment theo portal chưa được Step 1–7 quy định. Đây là quyết định framework/packaging ở `technology-decisions.md`; dù cách đóng gói nào, backend và permission contract vẫn thống nhất.
