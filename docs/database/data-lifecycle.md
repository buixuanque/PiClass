# Data lifecycle

## User

PersonProfile (tên/contact chung) và hồ sơ vai trò Student/Parent/Teacher có thể được tạo trước User. Khi cần đăng nhập, liên kết tối đa một User với PersonProfile; thay đổi liên kết ghi UserPersonProfileHistory. User inactive chặn truy cập mới nhưng không xóa profile, role history, nội dung do người đó tạo, bài làm, điểm hay audit. Reactivate User không tự mở lại role/relationship đã kết thúc. Không cho vô hiệu hóa Admin cuối cùng còn quyền quản trị.

## Student và Class membership

Student có trạng thái active/inactive/archive; inactive không xóa lịch sử. Tham gia lớp tạo ClassMember với ngày hiệu lực; chuyển/rút lớp đóng membership cũ và tạo membership mới. Sửa hồi tố chỉ Admin, ghi lý do và AuditLog. Attendance, Assignment nhận, Attempt, Submission, Result giữ nguyên lớp/assessment gốc.

## Class

Class bắt đầu ở preparing, có thể hoạt động khi có Teacher phụ trách và được lưu archive. Archive không xóa Subject, membership, lịch, session, attendance hay assessment liên quan. Các entity mới không nên được tạo cho lớp archive, ngoại trừ thao tác lịch sử/ngoại lệ được phép.

## LessonSession

Được sinh từ Schedule hoặc tạo riêng; chuyển scheduled → completed/cancelled. Session lưu mốc thời gian cụ thể, còn timezone IANA và field recurrence hàng tuần nằm ở Schedule mẫu có cấu trúc. Mỗi lần sửa mẫu tạo ScheduleHistory; chỉ các session tương lai chưa diễn ra có thể được cập nhật theo mẫu. Sinh trước theo rolling window cấu hình vận hành. Session hủy không xóa nội dung/Attendance; nếu đã có Attendance, bật `review_required` để rà soát. Thay nội dung session hoàn tất thêm LessonSessionHistory (before/after, actor, thời điểm); dời/hủy lưu lý do và lịch sử.

## Assessment và Question

Assessment tạo draft; phát hành tạo AssessmentVersion bất biến cùng AssessmentQuestion snapshot và audience. Sửa sau phát hành tạo version/lần phát hành mới; Student đã nhận tiếp tục trỏ tới version được cấp. Close ngăn lượt mới, giữ Attempt đang chạy theo điều kiện đã cấp; reopen chỉ điều chỉnh người/nhóm cần tiếp tục. Cancel/withdraw ghi lý do, giữ submission/result/history. Archive ẩn khỏi danh sách vận hành nhưng không xóa.

Question sửa tạo QuestionVersion mới. Question đã được dùng trong release chỉ archive, không xóa; snapshot nguồn luôn còn nguyên. Topic/Tag được giữ nếu còn tham chiếu. QuestionBank là kho dùng chung; quyền sửa câu dựa người tạo/Admin, không dựa chỉ vào việc được dùng.

## Attempt và Submission

Tạo Attempt khi Student bắt đầu; khi đó lượt được tính. Autosave Answer theo attempt. Nộp hoặc timeout tạo duy nhất một Submission cuối, ghi `received_at` từ hệ thống, và khóa câu trả lời theo chính sách. Attempt Exam cấp lại do sự cố tạo Attempt mới liên kết Attempt cũ; attempt cũ giữ trạng thái hủy do sự cố và không tính là bài nộp hợp lệ. Nộp lại theo yêu cầu tạo attempt mới; mỗi attempt vẫn tối đa một Submission cuối được chấm.

## Parent absence explanation

Parent gửi AbsenceExplanation gắn với Student và LessonSession; bản ghi đi qua trạng thái submitted/under_review/accepted/rejected cùng reviewer và ghi chú. Kết quả review không tự sửa Attendance. Nếu Teacher/Admin quyết định sửa Attendance, thao tác đó tạo AttendanceHistory/AuditLog theo quy tắc riêng.

## Result

Grade có thể ở trạng thái tự chấm/chờ Teacher/chấm một phần/hoàn tất. Feedback là entity riêng, có thể chung hoặc theo câu/grade, gắn tệp và có history. Result tổng hợp theo Submission; chiến lược nhiều lượt chọn cao nhất hoặc lần cuối theo Assessment, mặc định lần cuối. Điểm chưa chấm không tính progress trung bình. Công bố và thu hồi công bố có event history; thu hồi ngăn truy cập từ thời điểm đó trở đi, nhưng không xóa lịch sử đã xem. Sửa điểm sau công bố bắt buộc lý do, before/after, actor và audit. Assessment bị hủy không tính điểm nhưng vẫn xuất hiện trong lịch sử. Progress được tính từ dữ liệu Result đã công bố, không lưu bảng tổng hợp riêng trong V1.

## Attachment, Notification, Audit

Attachment lưu metadata và checksum nếu storage hỗ trợ; không bị xóa khi đối tượng được archive hoặc khi còn được tham chiếu. Cleanup file mồ côi theo policy vận hành/pháp lý. Quyền xem/tải luôn theo quyền đối tượng và visibility. Notification giữ lịch sử, read/unread theo người nhận; context có FK cụ thể và sự kiện trùng cho cùng recipient không tạo bản ghi mới. AuditLog append-only ở nghiệp vụ, giữ cùng thời gian lưu hồ sơ học tập; không đặt hạn retention ngắn ở thiết kế này. AuditLog không thay thế business history chuyên biệt.
