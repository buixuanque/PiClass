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
- **BR-CLASS-008 — ĐÃ QUYẾT ĐỊNH:** Mốc lịch hiển thị và nhập theo giờ Việt Nam (Asia/Ho_Chi_Minh): giờ không kèm offset được hiểu là giờ Việt Nam, giờ kèm offset được quy đổi đúng (`DEC-SCH-003`). Kiểm tra xung đột tạo cảnh báo để người có quyền xử lý ngoại lệ; không chặn cứng mọi trường hợp.
- **BR-CLASS-009 — ĐÃ QUYẾT ĐỊNH:** Thay đổi/hủy lịch ghi nhận lý do và thông báo người liên quan. Không đặt thời hạn báo trước cứng. **Hủy Schedule (mẫu lịch) không tự hủy các LessonSession đã sinh** — buổi đã sinh giữ nguyên trạng thái, Admin/Teacher tự hủy riêng từng buổi nếu cần (chốt 2026-10-25, giữ nguyên hành vi `CancelSchedule` đã code từ trước, đóng quyết định mở #9 ở `product/screens/api-gaps.md`).
- **BR-CLASS-010 — ĐÃ QUYẾT ĐỊNH:** LessonSession có trạng thái dự kiến, hoàn tất, hủy. Hủy buổi đã diễn ra không xóa nội dung hoặc Attendance; nếu đã điểm danh thì cần rà soát. **Không hủy được buổi đã qua `ends_at`** (`DEC-LRN-016`, sửa một phần quy tắc này): cần rà soát chỉ còn với buổi đang diễn ra bị hủy.
- **BR-CLASS-011 — ĐÃ QUYẾT ĐỊNH:** Sửa giờ/nội dung/ghi chú buổi học lưu người sửa, thời điểm và phần thay đổi (lịch sử + AuditLog) cho mọi trạng thái buổi, không chỉ buổi hoàn tất (`DEC-LRN-011`); lý do bắt buộc khi buổi đã hoàn tất, tùy chọn ở trạng thái khác.

## Attendance

- **BR-ATT-001 — ĐÃ QUYẾT ĐỊNH:** Attendance ghi trạng thái Student tại LessonSession cụ thể, gồm Present, Absent, Late, Excused.
- **BR-ATT-002 — ĐÃ QUYẾT ĐỊNH:** Một trạng thái hiện hành cho mỗi Student mỗi buổi. Chỉ Teacher được phân công hoặc Admin sửa; cho phép sửa khi cần, không khóa cứng theo ngày.
- **BR-ATT-003 — ĐÃ QUYẾT ĐỊNH:** Mọi sửa Attendance lưu giá trị trước/sau, người sửa, thời điểm và lý do.
- **BR-ATT-004 — ĐÃ QUYẾT ĐỊNH:** Không điểm danh buổi đã hủy hoặc người không phải thành viên có hiệu lực tại thời điểm buổi học, trừ khi Admin ghi nhận ngoại lệ.
- **BR-ATT-005 — ĐÃ QUYẾT ĐỊNH:** Parent được gửi giải trình vắng; đây là yêu cầu để Teacher/Admin xem xét, không tự động đổi trạng thái điểm danh.

## Learning lifecycle

- **BR-CLASS-012 — ĐÃ QUYẾT ĐỊNH:** Lịch sử học tập gắn với lớp/buổi thực tế phát sinh. Kết thúc membership, phân công hoặc lưu trữ lớp không xóa dữ liệu trước đó.
- **BR-CLASS-013 — ĐÃ QUYẾT ĐỊNH:** Một Assignment liên kết tối đa một LessonSession; có thể không liên kết. Assessment giao qua lớp và trực tiếp không tạo bản trùng cho cùng Student.

## Course (tự học thêm)

- **BR-COURSE-001 — ĐÃ QUYẾT ĐỊNH:** Course là nội dung tự học thêm, tách biệt Class; không có Schedule/LessonSession/Attendance riêng. Áp dụng chung cho cả quy mô trường học lớn và lớp học thêm cá nhân, trong một tenant duy nhất.
- **BR-COURSE-002 — ĐÃ QUYẾT ĐỊNH (sửa 2026-10-30, `DEC-LRN-026`):** Khóa chứa danh sách mục (`CourseItem`). V1 mỗi mục là một **bài đánh giá của khóa**: bài thuộc về khóa, được tạo trong khóa hoặc sao chép từ bài cá nhân của Giáo viên (hai bản độc lập). Chưa có nội dung bài học riêng (text/video/tài liệu) trong V1. Hướng sau V1 (user chốt 2026-10-29, `B-062`): thêm các loại mục bài giảng, tài liệu, link video, video tự lưu cùng tiến độ và mở khóa tuần tự vào đúng cấu trúc mục này; làm sau khi đủ khung chức năng 4 vai trò, khi đó sửa rule này và `BR-SYS-002`.
- **BR-COURSE-003 — ĐÃ QUYẾT ĐỊNH (thay 2026-10-30, `DEC-LRN-026`):** Khóa có **đối tượng** (`CourseAudience`): mỗi dòng là một Lớp hoặc một Student, không trạng thái, không ngày hiệu lực; gỡ = xóa dòng (có AuditLog). Student thuộc khóa nếu nằm trong danh sách trực tiếp hoặc là thành viên đang hoạt động của một Lớp trong danh sách; quyền được tính khi cần, không lưu dòng ghi danh. Student rời lớp thì mất quyền qua lớp ngay; vào lớp thì thấy khóa ngay. Chưa có khái niệm Khối/Grade riêng; nhóm theo khối bằng chọn nhiều Class.
- **BR-COURSE-004 — ĐÃ QUYẾT ĐỊNH (chốt 2026-10-14, đọc lại theo `DEC-LRN-026`):** Teacher được tạo Course thuộc sở hữu riêng (`owner_teacher_id`), không cần Admin duyệt trước khi dùng. Teacher sở hữu Course được thêm/gỡ đối tượng khóa là **bất kỳ Student nào trong hệ thống** hoặc bất kỳ Class nào, không giới hạn Student thuộc Class mình đang phụ trách — khác hẳn ClassMember (Admin-only, `BR-CLASS-014`), vì Course phục vụ cả kịch bản dạy thêm cá nhân ngoài lớp chính thức. Admin vẫn quản lý toàn hệ thống và có thể tạo Course dùng chung (`owner_teacher_id=null`).
- **BR-COURSE-005 — ĐÃ QUYẾT ĐỊNH (2026-10-21, `DEC-LRN-012`, đọc lại theo `DEC-LRN-026`):** Course dùng chung (`owner_teacher_id = null`) chỉ Admin quản lý (sửa, thêm/gỡ đối tượng, thêm/sửa mục). Teacher chỉ xem. Ví dụ: Admin tạo Course chung "Ôn thi tốt nghiệp Toán 12"; cô Lan (Teacher) thấy Course trong danh sách nhưng không sửa, không thêm được học sinh.
- **BR-COURSE-006 — ĐÃ QUYẾT ĐỊNH (2026-10-21, `DEC-LRN-013`, đọc lại theo `DEC-LRN-026`):** Không thêm đối tượng hoặc mục vào Course `archived` và không tạo lớp cho Subject `inactive`.
- **BR-COURSE-007 — ĐÃ QUYẾT ĐỊNH (2026-10-30, `DEC-LRN-026`):** Bài của khóa tách khỏi bài cá nhân: không hiện ở danh sách bài đánh giá của Teacher (TC-09), không giao qua Lớp/Student riêng lẻ mà theo đối tượng khóa, kết quả làm bài lưu riêng theo từng Student như mọi bài. Student làm cùng nội dung qua lớp và qua khóa có hai chuỗi lịch sử độc lập. Xóa hoặc lưu trữ khóa thì nội dung đi theo khóa; bài đã có bài làm chỉ lưu trữ, không xóa (`BR-ASSESS-010`).
