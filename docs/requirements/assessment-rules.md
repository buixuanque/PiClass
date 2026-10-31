# Assessment Rules — Đánh giá, bài làm và kết quả

Các quy tắc đã được chốt theo vận hành V1. Xem [Decision Log](decision-log.md) để biết đầy đủ chi tiết từng quyết định.

## Loại bài, người nhận và phát hành

- **BR-ASSESS-001 — ĐÃ QUYẾT ĐỊNH:** Dùng Assessment chung, phân biệt Assignment (bài tập), Quiz (kiểm tra nhanh), Exam (kỳ thi) bằng loại và policy.
- **BR-ASSESS-002 — ĐÃ QUYẾT ĐỊNH:** Assignment có deadline và lưu nháp; Quiz hỗ trợ thời gian/số lượt; Exam có khung giờ chung và quy tắc sự cố/cấp lại chặt hơn.
- **BR-ASSESS-003 — ĐÃ QUYẾT ĐỊNH (sửa 2026-10-30, `DEC-LRN-026`):** Bài cá nhân giao cho Class hoặc Student (một bài giao nhiều lớp vẫn là một bản ghi, nhiều dòng audience). Bài của khóa không giao qua Class/Student riêng mà theo đối tượng khóa (`BR-ASSESS-029`). Nếu cùng Student nhận qua hai đường trong cùng một bài, chỉ có một bài cần làm.
- **BR-ASSESS-004 — ĐÃ QUYẾT ĐỊNH:** Một Assignment liên kết tối đa một LessonSession, có thể để trống.
- **BR-ASSESS-005 — ĐÃ QUYẾT ĐỊNH:** Bản phát hành giữ nguyên nội dung, đáp án và cấu hình chấm. Sửa sau phát hành tạo phiên bản mới; bài làm tham chiếu phiên bản đã nhận.
- **BR-ASSESS-006 — ĐÃ QUYẾT ĐỊNH:** Đóng bài ngăn lượt mới; lượt đang làm theo điều kiện đã cấp. Reopen chỉ cấp cho người/nhóm cần làm tiếp. Hủy/thu hồi giữ bài nộp và lịch sử, kèm lý do.

## Question Bank và Question Engine

- **BR-ASSESS-007 — ĐÃ QUYẾT ĐỊNH:** V1 gồm Single Choice, Multiple Choice, True/False, Fill in the Blank, Short Answer, Essay, Matching, Ordering và File Upload.
- **BR-ASSESS-008 — ĐÃ QUYẾT ĐỊNH (sửa 2026-10-30, `DEC-LRN-026`):** Question Bank là nơi duy nhất chứa câu hỏi; mọi câu trong bài đều có câu nguồn trong ngân hàng. Mỗi câu có `visibility`: `shared` (mặc định, mọi Teacher dùng được) hoặc `private` (chỉ người tạo và Admin). Teacher quản lý câu mình tạo, Admin quản lý toàn kho. Quyền dùng câu không cho phép sửa câu của người khác; muốn đổi thì sao chép thành biến thể. Đổi `shared` ↔ `private` được bất kỳ lúc nào và không ảnh hưởng bài đã dùng câu đó (bài giữ bản chụp).
- **BR-ASSESS-009 — ĐÃ QUYẾT ĐỊNH:** Topic không phân cấp; Question được gắn nhiều Topic/Tag.
- **BR-ASSESS-010 — ĐÃ QUYẾT ĐỊNH:** Câu đã dùng trong bản phát hành giữ nguyên nội dung/đáp án lịch sử. Chỉnh sửa tạo phiên bản mới; câu đang được tham chiếu chỉ được lưu trữ, không xóa.
- **BR-ASSESS-011 — ĐÃ QUYẾT ĐỊNH:** Tự chấm chỉ áp dụng khi đáp án và quy tắc xác định. Câu cần phán đoán do Teacher chấm. Câu bỏ trống được 0; chuẩn hóa/biến thể đáp án chỉ khi được cấu hình rõ.
- **BR-ASSESS-012 — ĐÃ QUYẾT ĐỊNH:** Điểm câu không vượt quá điểm tối đa; cho điểm từng phần khi quy tắc câu xác định. Không thêm rubric workflow/chấm ẩn danh/duyệt chéo trong V1.
- **BR-ASSESS-013 — ĐÃ QUYẾT ĐỊNH:** Exam có thể xáo trộn câu và phương án; thứ tự ổn định trong cùng Attempt. Quiz có tùy chọn xáo trộn. Không bắt buộc rút ngẫu nhiên tập con câu hỏi.

## Attempt, Submission và deadline

- **BR-ASSESS-014 — ĐÃ QUYẾT ĐỊNH:** Attempt là một lượt làm; Submission là nội dung được hệ thống nhận khi nộp/timeout; Answer là câu trả lời trong lượt.
- **BR-ASSESS-015 — ĐÃ QUYẾT ĐỊNH:** Số lượt cấu hình theo Assessment, mặc định một. Lượt được tính khi Student bắt đầu.
- **BR-ASSESS-016 — ĐÃ QUYẾT ĐỊNH:** Assignment cho lưu nháp; có thể nộp lại khi Teacher yêu cầu sửa hoặc bài cho phép. Nộp trễ/gia hạn cấu hình theo bài hoặc từng Student.
- **BR-ASSESS-017 — ĐÃ QUYẾT ĐỊNH:** Mỗi Attempt có một Submission cuối được chấm. Thời điểm hệ thống nhận là căn cứ deadline.
- **BR-ASSESS-018 — ĐÃ QUYẾT ĐỊNH:** Bài làm trực tuyến tự lưu và khôi phục cùng Attempt. Với Quiz/Exam, đồng hồ tiếp tục khi rời trang/mất mạng.
- **BR-ASSESS-019 — ĐÃ QUYẾT ĐỊNH:** Exam cho vào muộn trong khung giờ; thời lượng còn lại kết thúc tại giờ đóng chung. Timeout tự nộp phần đã lưu.
- **BR-ASSESS-020 — ĐÃ QUYẾT ĐỊNH:** Admin cấp lại Attempt Exam khi sự cố được xác nhận, ghi lý do và giữ Attempt cũ trong lịch sử. Lượt cũ bị hủy do sự cố không tính là bài nộp hợp lệ.
- **BR-ASSESS-021 — ĐÃ QUYẾT ĐỊNH:** Khi Exam hoãn/hủy, thông báo người dự thi; Attempt đang chạy được giữ để Admin xử lý, không âm thầm xóa.

## Grading, Result và progress

- **BR-ASSESS-022 — ĐÃ QUYẾT ĐỊNH:** Một Teacher có quyền chấm; chưa có quy trình bắt buộc nhiều người chấm, rubric riêng hoặc duyệt chéo.
- **BR-ASSESS-023 — ĐÃ QUYẾT ĐỊNH:** Chính sách nhiều lượt chọn theo Assessment: điểm cao nhất hoặc lần cuối; mặc định lần cuối, Exam mặc định lần cuối.
- **BR-ASSESS-024 — ĐÃ QUYẾT ĐỊNH:** Teacher được sửa điểm đã công bố khi ghi lý do; Admin cũng có thể sửa. Lưu lịch sử điểm trước/sau.
- **BR-ASSESS-025 — ĐÃ QUYẾT ĐỊNH:** Người tạo chọn lúc công bố điểm/feedback. Đáp án, lời giải, điểm từng câu là lựa chọn công bố riêng; Exam mặc định ẩn đáp án cho tới khi được cho phép.
- **BR-ASSESS-026 — ĐÃ QUYẾT ĐỊNH:** Progress cơ bản dựa trên Result đã chấm/công bố. Bài chưa chấm không tính trung bình; Assessment hủy không tính điểm nhưng vẫn hiện trong lịch sử.
- **BR-ASSESS-027 — ĐÃ QUYẾT ĐỊNH:** Không có chức năng xuất báo cáo riêng trong V1.
- **BR-ASSESS-028 — ĐÃ QUYẾT ĐỊNH:** Student/Parent chỉ xem Result sau công bố và khi còn quyền với Student/Assessment.
- **BR-ASSESS-029 — ĐÃ QUYẾT ĐỊNH (2026-10-30, `DEC-LRN-026`):** Bài đánh giá có hai loại, cùng bảng và cùng engine làm bài/chấm điểm, phân biệt bằng `course_id`: **bài cá nhân** (`course_id` null, Teacher sở hữu, giao cho Class/Student, hiện ở danh sách bài của Teacher) và **bài của khóa** (`course_id` có giá trị, khóa sở hữu, không có audience riêng, chỉ hiện trong khóa). Bài đánh giá không có `visibility`. Dùng lại bài cá nhân trong khóa là sao chép, hai bản độc lập; sửa bản gốc không đổi bản trong khóa. Mọi truy vấn danh sách bài cá nhân phải lọc `course_id IS NULL`.
