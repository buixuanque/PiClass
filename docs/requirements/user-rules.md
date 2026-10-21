# User Rules — Người dùng và quan hệ

Các quyết định ở đây đã chốt tại Step 5; chi tiết theo mã tại [Decision Log](decision-log.md).

## User, Role và hồ sơ

- **BR-USER-001 — ĐÃ QUYẾT ĐỊNH:** Hồ sơ Student/Parent/Teacher được tạo trước tài khoản; mời kích hoạt khi cần đăng nhập. Tài khoản và hồ sơ nghiệp vụ có vòng đời riêng.
- **BR-USER-002 — ĐÃ QUYẾT ĐỊNH:** User có thể có nhiều Role; Admin và Teacher vẫn là hai vai trò riêng, có thể cùng thuộc một User. User nhiều role chọn ngữ cảnh làm việc sau đăng nhập.
- **BR-USER-003 — ĐÃ QUYẾT ĐỊNH:** Admin có phạm vi toàn hệ thống; Teacher chỉ thao tác nghiệp vụ ở lớp được phân công, trừ ngoại lệ do Admin cấp rõ.
- **BR-USER-004 — ĐÃ QUYẾT ĐỊNH:** Thông tin liên hệ dùng chung nằm ở hồ sơ chung; dữ liệu nghiệp vụ nằm ở hồ sơ Student/Parent/Teacher tương ứng. Chỉ thu thập thông tin cần cho hoạt động lớp.
- **BR-USER-005 — ĐÃ QUYẾT ĐỊNH:** Không có nhập hồ sơ hàng loạt trong V1. Tên là thông tin nhận diện bắt buộc; thông tin liên hệ/tài khoản bắt buộc khi cần liên hệ/đăng nhập. Hồ sơ nghi trùng cần Admin xác minh; không tự gộp.
- **BR-USER-006 — ĐÃ QUYẾT ĐỊNH:** Student, Parent và Teacher được tự sửa thông tin liên hệ cơ bản của chính mình (email/điện thoại); không tự sửa tên định danh, trạng thái, hồ sơ nghiệp vụ hoặc quan hệ/phân công. Admin quản lý định danh và trạng thái. Đặt lại mật khẩu qua kênh liên hệ đã xác minh; chính sách bảo mật cụ thể thuộc bước thiết kế/vận hành.
- **BR-USER-007 — ĐÃ QUYẾT ĐỊNH:** Không cho vô hiệu hóa Admin cuối cùng còn quyền quản trị.
- **BR-USER-008 — ĐÃ QUYẾT ĐỊNH:** Inactive chặn đăng nhập/truy cập mới nhưng không xóa hồ sơ, lịch sử hoặc dấu vết thao tác. Có thể tái kích hoạt; quan hệ đã kết thúc không tự hoạt động lại.

## Teacher và Class

- **BR-USER-009 — ĐÃ QUYẾT ĐỊNH:** Teacher có thể phụ trách nhiều lớp; một lớp có thể có nhiều Teacher. Chưa cần Teacher chính; quyền Teacher dựa trên phân công hiện hành.
- **BR-USER-010 — ĐÃ QUYẾT ĐỊNH:** Trước khi vô hiệu hóa Teacher, Admin xử lý phân công và lịch đang hoạt động. Lịch sử và nội dung Teacher đã tạo vẫn được giữ.

## Parent–Student

- **BR-USER-011 — ĐÃ QUYẾT ĐỊNH:** Quan hệ Parent–Student là nhiều-nhiều; không giới hạn số lượng và không bắt buộc Student luôn có Parent liên kết.
- **BR-USER-012 — ĐÃ QUYẾT ĐỊNH:** Admin tạo và xác nhận liên kết; có thể ghi loại quan hệ do gia đình cung cấp. V1 không yêu cầu quy trình bằng chứng riêng.
- **BR-USER-013 — ĐÃ QUYẾT ĐỊNH:** Parent chỉ xem dữ liệu Student đang liên kết và được công bố; Parent không làm bài hoặc sửa dữ liệu học tập thay Student.
- **BR-USER-014 — ĐÃ QUYẾT ĐỊNH:** Gỡ liên kết thu hồi quyền xem ngay, kể cả lịch sử qua cổng Parent. Lịch sử không bị xóa và vẫn sẵn có cho Admin/Teacher có quyền.
- **BR-USER-015 — ĐÃ QUYẾT ĐỊNH:** Parent chỉ nhận thông báo và mở tệp nếu còn quyền với Student/ngữ cảnh đó.

## Membership và lịch sử

- **BR-USER-016 — ĐÃ QUYẾT ĐỊNH:** ClassMember có hiệu lực theo thời gian. Chuyển lớp kết thúc quan hệ cũ và tạo quan hệ mới; điểm danh, bài làm và kết quả cũ tiếp tục thuộc lớp phát sinh.
- **BR-USER-017 — ĐÃ QUYẾT ĐỊNH:** Sửa hồi tố membership chỉ Admin được làm, kèm lý do và dấu vết kiểm toán.
- **BR-USER-018 — ĐÃ QUYẾT ĐỊNH (2026-10-21, `DEC-USER-009`):** Chỉ Teacher `active` mới được phân công vào lớp. Ví dụ: cô Hoa đã nghỉ việc (hồ sơ `inactive`) thì Admin không phân công cô vào lớp mới được.
