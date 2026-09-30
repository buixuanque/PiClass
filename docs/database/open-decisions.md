# Decisions resolved for Step 6

User đã chốt toàn bộ đề xuất trong tài liệu này. Các quyết định dưới đây hoàn tất phần open decisions về schema của Step 6; đây là quyết định thiết kế dữ liệu, không thay thế hay sửa business rules Step 5.

| Chủ đề | Quyết định đã chốt |
|---|---|
| DBMS và ID | Chọn PostgreSQL và UUID làm PK cho entity nghiệp vụ. Tên không dùng làm định danh; tìm kiếm không dấu xử lý ở tầng truy vấn/index khi implementation. |
| Trường hồ sơ | V1 lưu tên bắt buộc, email/điện thoại theo nhu cầu liên hệ/đăng nhập; specialty Teacher tùy chọn. Không thêm ngày sinh, địa chỉ, giấy tờ định danh hoặc liên hệ khẩn cấp nếu chưa có requirement. |
| PersonProfile và account | Tách `PersonProfile` dùng chung cho tên/contact; Student/Parent/Teacher role profile tham chiếu PersonProfile. Một PersonProfile có tối đa một User account tại một thời điểm; liên kết/đổi account phải giữ lịch sử. Người có nhiều role dùng chung hồ sơ cá nhân. |
| Schedule lặp | Dùng field có cấu trúc cho recurrence hàng tuần: ngày trong tuần, giờ địa phương, thời lượng, khoảng hiệu lực và IANA timezone. Schedule có version/history; LessonSession lưu thời điểm cụ thể; sinh trước theo rolling window cấu hình vận hành, không cố định số ngày trong schema. |
| Ngày hiệu lực | Quan hệ dùng khoảng nửa mở `[valid_from, valid_to)` theo instant; `valid_to` là thời điểm quan hệ hết hiệu lực, null nghĩa là còn hiệu lực. Ngày nhập theo giờ Việt Nam được chuyển nhất quán sang instant. |
| Question payload | Dùng cột chung, QuestionOption quan hệ cho lựa chọn và JSON payload có schema validation theo từng question type. Answer payload cũng là JSON có validate; Assessment snapshot đóng băng nội dung và grading config. Không tạo bảng riêng cho mỗi type. |
| Feedback | Tách entity `Feedback` liên kết Submission và tùy chọn Grade/AssessmentQuestion. Hỗ trợ nhiều feedback, Attachment và visibility/công bố; chỉnh sửa có history. Grade chỉ giữ điểm và phương thức chấm. |
| Version/công bố | Mỗi lần phát hành tạo version bất biến. StudentAssessment giữ version được cấp; version mới không thay bài làm đang diễn ra/đã nộp. Publish/withdraw có event history; withdraw có hiệu lực từ thời điểm đó về sau, không xóa sự kiện hoặc khả năng người học đã xem trước đó. |
| File metadata/retention | Lưu storage key, tên, media type, kích thước, trạng thái, thời điểm và checksum nếu storage hỗ trợ. Không xóa tệp còn reference; cleanup file mồ côi theo policy vận hành/pháp lý, không đặt hạn retention trong schema. |
| Notification context | Tạo `NotificationContext` với FK cụ thể tới các ngữ cảnh V1: Assessment, LessonSession, Result hoặc Schedule; đúng một target. Notification không cấp quyền; quyền đọc được kiểm tra lại theo entity/ngữ cảnh hiện hành. Unique recipient/event key chống trùng. |
| Progress | Không tạo bảng Progress riêng V1; tính từ Result đã chấm/công bố và dữ kiện nguồn. Cache chỉ cân nhắc sau nếu đo đạc hiệu năng cho thấy cần thiết; dữ liệu tổng hợp không là source of truth. |

## Chính sách vận hành chưa quyết định tại Step 6

Các mục sau không còn là open schema decisions: số ngày rolling window sinh LessonSession; giới hạn dung lượng/định dạng file; retention pháp lý và thời gian cleanup file không còn tham chiếu; schema validation chi tiết cho payload từng QuestionType. Xác định ở policy vận hành hoặc trước implementation tương ứng, không tự thêm vào thiết kế logic hiện tại.
