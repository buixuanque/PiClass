# Learning Rules — Học vụ và điểm danh

## Subject, Class và membership

- **BR-CLASS-001 — ĐÃ QUYẾT ĐỊNH:** Subject phân loại Class và nội dung học; V1 không cần phân cấp Subject/Topic.
- **BR-CLASS-002 — ĐÃ QUYẾT ĐỊNH:** Class tổ chức Student, Teacher, Schedule, LessonSession và hoạt động được giao. Có thể chuẩn bị lớp trước khi đủ người; lớp bắt đầu vận hành khi có Teacher phụ trách.
- **BR-CLASS-003 — ĐÃ QUYẾT ĐỊNH:** V1 không đặt sức chứa lớp. Tên trùng được phép nếu là các lớp/môn khác nhau; phải có thông tin phân biệt để người vận hành chọn đúng.
- **BR-CLASS-004 — ĐÃ QUYẾT ĐỊNH:** Lưu trữ Class không xóa lịch sử. Chuyển/rút học viên kết thúc ClassMember cũ, tạo tư cách mới; dữ liệu cũ không chuyển sang lớp mới.
- **BR-CLASS-005 — ĐÃ QUYẾT ĐỊNH:** ClassMember có ngày hiệu lực. Sửa hồi tố chỉ Admin, có lý do và AuditLog.
- **BR-CLASS-014 — ĐÃ QUYẾT ĐỊNH:** Chỉ Admin được tạo, kết thúc hoặc thay đổi ClassMember. Teacher được xem roster của lớp đang được phân công nhưng không tự thêm/rút học viên. Student/Parent chỉ xem thông tin lớp, môn, lịch/buổi và dữ liệu học tập thuộc bản thân/child; không xem roster hoặc thông tin cá nhân của học viên khác.

## Schedule và LessonSession

- **BR-CLASS-006 — ĐÃ QUYẾT ĐỊNH:** Schedule là kế hoạch; LessonSession là buổi cụ thể, có thể sinh từ lịch hoặc tạo riêng.
- **BR-CLASS-007 — ĐÃ QUYẾT ĐỊNH:** Lịch lặp sinh buổi tương lai. Sửa lịch mẫu chỉ ảnh hưởng buổi tương lai chưa diễn ra; dời/hủy riêng một buổi không làm đổi các buổi khác.
- **BR-CLASS-008 — ĐÃ QUYẾT ĐỊNH:** Mốc lịch hiển thị theo giờ Việt Nam (Asia/Ho_Chi_Minh). Kiểm tra xung đột tạo cảnh báo để người có quyền xử lý ngoại lệ; không chặn cứng mọi trường hợp.
- **BR-CLASS-009 — ĐÃ QUYẾT ĐỊNH:** Thay đổi/hủy lịch ghi nhận lý do và thông báo người liên quan. Không đặt thời hạn báo trước cứng.
- **BR-CLASS-010 — ĐÃ QUYẾT ĐỊNH:** LessonSession có trạng thái dự kiến, hoàn tất, hủy. Hủy buổi đã diễn ra không xóa nội dung hoặc Attendance; nếu đã điểm danh thì cần rà soát.
- **BR-CLASS-011 — ĐÃ QUYẾT ĐỊNH:** Sửa nội dung/ghi chú buổi hoàn tất lưu người sửa, thời điểm và phần thay đổi.

## Attendance

- **BR-ATT-001 — ĐÃ QUYẾT ĐỊNH:** Attendance ghi trạng thái Student tại LessonSession cụ thể, gồm Present, Absent, Late, Excused.
- **BR-ATT-002 — ĐÃ QUYẾT ĐỊNH:** Một trạng thái hiện hành cho mỗi Student mỗi buổi. Chỉ Teacher được phân công hoặc Admin sửa; cho phép sửa khi cần, không khóa cứng theo ngày.
- **BR-ATT-003 — ĐÃ QUYẾT ĐỊNH:** Mọi sửa Attendance lưu giá trị trước/sau, người sửa, thời điểm và lý do.
- **BR-ATT-004 — ĐÃ QUYẾT ĐỊNH:** Không điểm danh buổi đã hủy hoặc người không phải thành viên có hiệu lực tại thời điểm buổi học, trừ khi Admin ghi nhận ngoại lệ.
- **BR-ATT-005 — ĐÃ QUYẾT ĐỊNH:** Parent được gửi giải trình vắng; đây là yêu cầu để Teacher/Admin xem xét, không tự động đổi trạng thái điểm danh.

## Learning lifecycle

- **BR-CLASS-012 — ĐÃ QUYẾT ĐỊNH:** Lịch sử học tập gắn với lớp/buổi thực tế phát sinh. Kết thúc membership, phân công hoặc lưu trữ lớp không xóa dữ liệu trước đó.
- **BR-CLASS-013 — ĐÃ QUYẾT ĐỊNH:** Một Assignment liên kết tối đa một LessonSession; có thể không liên kết. Assessment giao qua lớp và trực tiếp không tạo bản trùng cho cùng Student.
