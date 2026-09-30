# File and Audit Rules — Tệp và dấu vết

## Attachment

- **BR-FILE-001 — ĐÃ QUYẾT ĐỊNH:** Attachment dùng cho tài liệu Assignment/Question, File Upload trong bài làm và Feedback theo dạng câu hỏi được hỗ trợ.
- **BR-FILE-002 — ĐÃ QUYẾT ĐỊNH:** Người dùng chỉ xem/tải tệp trong ngữ cảnh họ có quyền. Parent không xem tệp bài làm riêng tư trừ khi nội dung được công bố cho Parent.
- **BR-FILE-003 — ĐÃ QUYẾT ĐỊNH:** Lưu trữ đối tượng không xóa tệp còn được tham chiếu hoặc lịch sử. Không xóa tệp đang được dùng.
- **BR-FILE-004 — ĐÃ QUYẾT ĐỊNH:** Quy định định dạng/dung lượng theo chính sách vận hành được công bố; Step 5 không tự đặt ngưỡng số khi chưa có căn cứ.

## AuditLog và business history

- **BR-AUD-001 — ĐÃ QUYẾT ĐỊNH:** AuditLog ghi actor, thời điểm, đối tượng và thay đổi trước/sau cho hành động nhạy cảm.
- **BR-AUD-002 — ĐÃ QUYẾT ĐỊNH:** Event bắt buộc gồm role/trạng thái User, Parent–Student, membership/phân công, Attendance, phát hành/thu hồi Assessment, Submission, chấm/công bố/sửa điểm, lịch và tệp bài nộp.
- **BR-AUD-003 — ĐÃ QUYẾT ĐỊNH:** Sửa nghiệp vụ nhạy cảm cần ghi lý do; tối thiểu gồm sửa Attendance, điểm, membership và lịch.
- **BR-AUD-004 — ĐÃ QUYẾT ĐỊNH:** Admin xem toàn bộ AuditLog; Teacher xem lịch sử nghiệp vụ trong lớp được phân công.
- **BR-AUD-005 — ĐÃ QUYẾT ĐỊNH:** Lưu audit cùng thời gian lưu hồ sơ học tập; không đặt thời hạn xóa ngắn tại Step 5.
- **BR-AUD-006 — ĐÃ QUYẾT ĐỊNH:** Lịch sử riêng được giữ cho Attendance, Grade/Result, Schedule/LessonSession, membership/phân công Teacher và Parent–Student. AuditLog không thay thế business history.
- **BR-AUD-007 — ĐÃ QUYẾT ĐỊNH:** `before_data`, `after_data` và `reason` có thể nullable trên AuditLog nói chung vì không phải event nào cũng cần đủ cả ba. Với action nhạy cảm mà business rule yêu cầu lý do hoặc thay đổi trước/sau, các giá trị tương ứng là bắt buộc tại action đó; không ghi AuditLog của action thành công nếu thiếu dữ liệu bắt buộc.
