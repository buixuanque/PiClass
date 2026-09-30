# Access Rules — Quy tắc truy cập đặc biệt

## 1. Chuỗi quyết định truy cập

Yêu cầu được phép khi User active + action permission hợp lệ + resource đúng scope + relationship hiệu lực + lifecycle/visibility cho phép. List và get-one dùng cùng scope. Không dựa riêng vào ID/URL.

## 2. Đa role và Teacher nhiều lớp

- User nhiều role chọn context sau login. Mỗi action được đánh giá theo role đó; không tự động dùng Admin để bỏ qua quy tắc nếu User thực hiện trong Teacher context.
- Teacher có thể phụ trách nhiều lớp, một lớp có thể nhiều Teacher. Union scope chỉ gồm assignment hiện hành.
- Teacher inactive cần Admin chuyển assignment/lịch đang hoạt động trước khi đóng quyền Teacher; nội dung/history đã tạo vẫn giữ.
- Ngoại lệ Teacher ngoài scope phải do Admin phân công/cấp quyền rõ, có audit nếu nhạy cảm; không suy luận từ cùng Subject hay từ việc đã từng dạy lớp.

## 3. Parent–Student

- Admin tạo/xác nhận relationship; xác nhận lưu actor/time. Một Parent có thể liên kết nhiều Student; Student không bắt buộc có Parent.
- Parent scope yêu cầu relationship active và context visibility cho Parent. Unlink thu hồi xem ngay cả lịch sử qua Parent portal; mọi Notification/File link cũng kiểm tra lại quyền.
- Parent được gửi AbsenceExplanation của child cho LessonSession phù hợp. Teacher/Admin review; không tự đổi Attendance.
- Parent không làm bài, trả lời, submit, sửa Attendance/Grade/profile Student, hoặc truy cập bài làm/tệp private.

## 4. Class membership / lịch sử

- Student attendance chỉ ghi khi là ClassMember tại thời điểm LessonSession, trừ ngoại lệ Admin được audit.
- Chỉ Admin tạo/kết thúc/thay đổi ClassMember. Teacher xem roster trong lớp phụ trách nhưng không tự thêm/rút học viên. Student/Parent xem tên lớp, Subject, lịch/buổi và dữ liệu học tập của mình/child; roster và thông tin cá nhân peer bị ẩn.
- Chuyển/rút lớp đóng membership cũ, tạo membership mới; Attendance/Assessment/Result cũ giữ Class gốc.
- Sửa hồi tố membership chỉ Admin và phải ghi reason/audit.
- Class archived không cho tạo nghiệp vụ mới ngoài thao tác history/ngoại lệ cho phép; archive không xóa dữ liệu.

## 5. Question Bank và phiên bản

- Kho câu dùng chung: Admin quản lý tất cả; Teacher quản lý câu do mình tạo. Quyền dùng câu cho Assessment không bao gồm quyền sửa/xóa nguồn.
- Sửa câu tạo QuestionVersion; câu đã được dùng chỉ archive, không xóa.
- Publish Assessment tạo AssessmentVersion/Question snapshot bất biến. StudentAssessment giữ version được cấp; version mới không đổi bài đang làm/đã nộp.

## 6. Audience, Attempt, Submission

- Assessment có audience Class hoặc Student; direct + class assignment cùng Student được hợp nhất thành một StudentAssessment.
- Student cần audience/StudentAssessment hợp lệ, ClassMember phù hợp, cửa sổ thời gian, status và lượt còn lại mới được start.
- Attempt được tính khi start. Assignment có thể lưu nháp và nộp lại khi Teacher yêu cầu hoặc bài cho phép; resubmit tạo Attempt mới. Deadline căn cứ received_at của hệ thống.
- Quiz/Exam timer tiếp tục khi rời trang/mất mạng; auto-save phục hồi cùng Attempt. Exam được vào muộn trong khung giờ, hết giờ chung thì auto-submit phần đã lưu.
- Chỉ Admin cấp lại Exam Attempt khi sự cố được xác nhận; giữ Attempt cũ trong history và loại khỏi bài nộp hợp lệ.

## 7. Grade, Result, publication

- Teacher được phân công hoặc Admin grade Submission. Một Teacher là đủ; không có approval/multi-grader bắt buộc.
- Teacher/Admin được sửa grade đã công bố khi ghi lý do; lưu GradeHistory/ResultHistory và AuditLog.
- Result chỉ đọc sau công bố, khi người xem còn quyền với Student/Assessment. Parent chỉ xem result/feedback/file được công bố cho Parent.
- Publish điểm/feedback được chọn theo Assessment; đáp án/lời giải/điểm từng câu công bố riêng. Exam mặc định giấu đáp án cho tới khi được phép. `withdrawn` chặn đọc từ thời điểm thu hồi, nhưng giữ lịch sử xem/sự kiện.

## 8. Notification, File, AuditLog

- Notification chỉ tới Student/Teacher liên quan; Parent chỉ nhận nếu relationship và context access còn hiệu lực. Mở Notification đánh dấu read nhưng không cấp quyền resource.
- File access kế thừa quyền của object/context và `visibility`; Parent không xem bài nộp private. Không xóa file đang được tham chiếu.
- AuditLog append-only. Admin xem toàn bộ; Teacher xem business history trong Class scope. Ghi event bắt buộc và reason cho hành động nhạy cảm; log không thay thế entity history riêng.
