# Decision Log — Quyết định Step 5

**Trạng thái:** Các quyết định dưới đây đã được người dùng chốt theo hướng tư vấn dựa trên vận hành thực tế. Những quy tắc có thể cấu hình theo từng Assessment/ngoại lệ nghiệp vụ được ghi như vậy; không phải quyết định còn mở. Các ngưỡng chưa được artifacts xác định (ví dụ dung lượng tệp, thời hạn lưu theo luật) không tự đặt tại Step 5.

## User, hồ sơ và quyền

| ID | Quyết định đã chốt | Trạng thái |
|---|---|---|
| DEC-USR-001 | Cho tạo hồ sơ Student/Parent/Teacher trước tài khoản; mời/kích hoạt tài khoản khi người đó cần đăng nhập. Parent chưa có tài khoản vẫn có hồ sơ. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-002 | Hồ sơ chung giữ thông tin liên hệ; dữ liệu riêng về học tập/quan hệ thuộc hồ sơ nghiệp vụ tương ứng. Chỉ thu thập dữ liệu phục vụ vận hành. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-003 | Admin quản lý toàn hệ thống; Teacher thao tác nghiệp vụ trong lớp được phân công. Ngoại lệ cần được Admin phân công/cấp quyền rõ ràng. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-004 | Không đưa nhập hàng loạt vào V1. Tên là thông tin nhận diện bắt buộc; thông tin liên hệ/tài khoản chỉ bắt buộc khi cần liên hệ hoặc đăng nhập. Khi nghi trùng, Admin xác minh trước khi gộp; không tự gộp hồ sơ. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-005 | Student, Parent và Teacher tự sửa thông tin liên hệ cơ bản của chính mình (email/điện thoại); không tự sửa tên định danh, trạng thái, hồ sơ nghiệp vụ hoặc quan hệ/phân công. Admin quản lý dữ liệu định danh/trạng thái. Đặt lại mật khẩu qua kênh liên hệ đã xác minh; chi tiết độ mạnh/giới hạn thử đăng nhập tuân theo chính sách xác thực ở bước thiết kế. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-006 | User nhiều role chọn ngữ cảnh làm việc sau đăng nhập. Không cho vô hiệu hóa Admin cuối cùng đang có quyền quản trị. | ĐÃ QUYẾT ĐỊNH |
| DEC-USER-007 | Teacher có thể phụ trách nhiều lớp; lớp có thể có nhiều Teacher. Chưa cần Teacher chính; quyền Teacher gắn với phân công hiện hành. | ĐÃ QUYẾT ĐỊNH |
| DEC-USER-008 | Khi Teacher inactive, giữ lịch sử; Admin phải chuyển phân công/lịch đang hoạt động trước khi đóng quyền Teacher đó. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-009 | Quan hệ Parent–Student là nhiều-nhiều, không đặt giới hạn số lượng và không bắt buộc mỗi Student có Parent đang liên kết. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-010 | Admin tạo và xác nhận liên kết Parent–Student; lưu loại quan hệ cơ bản nếu gia đình cung cấp. V1 không yêu cầu quy trình tải bằng chứng riêng. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-011 | Gỡ liên kết thu hồi quyền xem ngay, gồm dữ liệu lịch sử qua cổng Parent. Dữ liệu vẫn được giữ trong hồ sơ Student cho Admin/Teacher có quyền. | ĐÃ QUYẾT ĐỊNH |
| DEC-USR-012 | Inactive chặn đăng nhập/truy cập mới; hồ sơ và lịch sử được giữ. Có thể tái kích hoạt, không làm sống lại các quan hệ đã kết thúc. | ĐÃ QUYẾT ĐỊNH |

## Học vụ, lịch và điểm danh

| ID | Quyết định đã chốt | Trạng thái |
|---|---|---|
| DEC-LRN-001 | Có thể chuẩn bị Class trước khi đủ Teacher/Student; lớp chỉ được coi là đang vận hành khi có Teacher phụ trách. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-002 | V1 không giới hạn sức chứa lớp, không phân cấp Subject/Topic. Tên trùng được phép nếu Admin xác định là các lớp/môn khác nhau; phải có thông tin phân biệt để chọn đúng. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-003 | Chỉ Admin tạo/kết thúc/thay đổi ClassMember. Teacher chỉ xem roster lớp đang được phân công, không tự thêm/rút học viên. Chuyển/rút lớp kết thúc tư cách cũ và tạo tư cách mới; dữ liệu lịch sử giữ ở lớp phát sinh. Sửa hồi tố chỉ Admin, có lý do và audit. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-004 | LessonSession có trạng thái dự kiến, hoàn tất, hủy. Không điểm danh buổi đã hủy; nếu hủy sau khi đã ghi điểm danh thì giữ bản ghi và đánh dấu cần rà soát, không xóa. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-005 | Sửa nội dung/ghi chú LessonSession đã hoàn tất phải lưu dấu vết người sửa, thời điểm và thay đổi. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-006 | Mỗi Student có một trạng thái Attendance hiện hành cho mỗi LessonSession. Teacher được phân công và Admin được sửa; cho phép sửa khi cần, không khóa cứng theo ngày. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-007 | Mọi sửa Attendance lưu giá trị trước/sau, người sửa, thời điểm và lý do. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-008 | Parent có thể gửi giải trình vắng; đây là yêu cầu để Teacher/Admin xem xét, không tự động đổi Attendance. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-009 | Student/Parent được xem tên lớp, Subject, Schedule/LessonSession và dữ liệu học tập của chính Student/child. Không hiển thị roster hoặc thông tin cá nhân của học viên khác; Teacher xem roster trong lớp được phân công. | ĐÃ QUYẾT ĐỊNH |
| DEC-SCH-001 | Lịch lặp sinh các LessonSession tương lai. Sửa lịch mẫu chỉ ảnh hưởng buổi tương lai chưa diễn ra; ngoại lệ dời/hủy một buổi chỉ áp dụng buổi đó. Buổi đã diễn ra không bị viết lại. | ĐÃ QUYẾT ĐỊNH |
| DEC-SCH-002 | Múi giờ hiển thị V1 là giờ Việt Nam (Asia/Ho_Chi_Minh). Kiểm tra xung đột lịch đưa cảnh báo để người có quyền xử lý ngoại lệ; thay đổi/hủy lịch ghi nhận lý do và thông báo người liên quan. Không đặt hạn báo trước cứng. | ĐÃ QUYẾT ĐỊNH |
| DEC-SYS-001 | Mốc lịch và thời điểm nhận bài hiển thị theo giờ Việt Nam; thời điểm hệ thống nhận Submission là căn cứ deadline. | ĐÃ QUYẾT ĐỊNH |
| DEC-LRN-010 | Thêm `Course`: nội dung tự học thêm, tách biệt `Class` (không Schedule/LessonSession/điểm danh riêng), chỉ gán Assessment/Question Bank có sẵn. Student tham gia qua `CourseEnrollment` (lịch sử hiệu lực như ClassMember), gán theo từng Student hoặc theo roster hiện hành của 1/nhiều Class. Áp dụng cho cả quy mô trường học lớn và lớp học thêm cá nhân, vẫn trong một tenant duy nhất (không cần multi-tenant). Chưa tạo entity Khối/Grade; nhóm theo khối thực hiện bằng chọn nhiều Class cùng lúc. Quyền tạo/enroll: xem `BR-COURSE-004`. | ĐÃ QUYẾT ĐỊNH |

## Assessment, câu hỏi và bài làm

| ID | Quyết định đã chốt | Trạng thái |
|---|---|---|
| DEC-AS-001 | Dùng Assessment chung, phân loại Assignment/Quiz/Exam với policy tương ứng. Assignment là bài tập; Quiz kiểm tra nhanh; Exam là kỳ kiểm tra có khung giờ và xử lý sự cố chặt hơn. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-002 | Assessment giao qua lớp và giao trực tiếp không tạo hai bài trùng cho cùng Student. Một Assignment liên kết tối đa một LessonSession; có thể không liên kết. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-003 | Đóng bài ngăn lượt mới; lượt đang làm theo điều kiện đã cấp. Reopen áp dụng cho người/nhóm cần làm tiếp nếu phù hợp, không tự mở lại toàn lớp. Hủy/thu hồi giữ bài đã nộp và lịch sử; ghi lý do. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-004 | Nội dung/đáp án/cấu hình chấm của bản phát hành được giữ ổn định. Sửa sau phát hành tạo phiên bản/lần phát hành mới; bài làm tham chiếu phiên bản đã nhận. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-005 | Số Attempt cấu hình theo Assessment. Lượt được tính khi Student bắt đầu; lượt chưa bắt đầu không tính. Mặc định một lượt nếu người tạo không chọn khác. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-006 | Assignment lưu nháp; cho nộp lại khi Teacher yêu cầu sửa hoặc cấu hình cho phép. Nộp trễ/gia hạn được cấu hình theo bài hoặc từng Student; thời điểm nhận trên hệ thống xác định đúng hạn. Một Submission cuối được chấm cho mỗi Attempt. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-007 | Exam có thể xáo trộn câu/phương án; thứ tự ổn định trong cùng Attempt. Quiz có thể bật xáo trộn. Không bắt buộc rút ngẫu nhiên tập con câu hỏi trong V1. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-008 | Bài làm trực tuyến tự lưu và khôi phục cùng Attempt. Với Quiz/Exam, thời gian tiếp tục chạy khi rời trang/mất kết nối; sự cố Exam đã xác nhận có thể được Admin cấp lại lượt với lý do, giữ lượt cũ trong lịch sử. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-009 | Cho vào Exam muộn khi còn trong khung thi; thời lượng còn lại tính tới giờ kết thúc chung. Hết giờ tự nộp phần đã lưu. Hoãn/hủy Exam thông báo người dự thi; Attempt đang chạy được giữ để Admin xử lý, không âm thầm xóa. | ĐÃ QUYẾT ĐỊNH |
| DEC-AS-010 | Admin phê duyệt cấp lại Attempt Exam khi sự cố được xác nhận; lượt cũ được giữ, không tính như bài nộp hợp lệ nếu bị hủy do sự cố. | ĐÃ QUYẾT ĐỊNH |
| DEC-Q-001 | V1 hỗ trợ Single Choice, Multiple Choice, True/False, Fill in the Blank, Short Answer, Essay, Matching, Ordering và File Upload như Product Definition liệt kê. | ĐÃ QUYẾT ĐỊNH |
| DEC-Q-002 | Question Bank dùng chung trong phạm vi hệ thống. Teacher quản lý câu mình tạo; Admin quản lý toàn kho. Dùng câu hỏi không đồng nghĩa được sửa câu nguồn của người khác. | ĐÃ QUYẾT ĐỊNH |
| DEC-Q-003 | Topic không phân cấp; một câu có thể gắn nhiều Topic/Tag. | ĐÃ QUYẾT ĐỊNH |
| DEC-Q-004 | Câu hỏi đã dùng trong bản phát hành giữ nguyên bản nội dung/đáp án đó. Sửa tạo phiên bản mới; xóa câu đang được tham chiếu là không được phép, chỉ lưu trữ. | ĐÃ QUYẾT ĐỊNH |
| DEC-GRD-001 | Tự chấm khi có đáp án/quy tắc xác định; câu cần phán đoán do Teacher chấm. Điểm phần được phép nếu quy tắc câu hỏi quy định; điểm tổng không vượt tối đa. Câu bỏ trống nhận 0; chuẩn hóa/chấp nhận biến thể chỉ bật khi người tạo cấu hình rõ. | ĐÃ QUYẾT ĐỊNH |
| DEC-GRD-002 | Assessment chọn chính sách kết quả nhiều lượt: điểm cao nhất hoặc lần cuối. Nếu không cấu hình, dùng lần cuối; Exam mặc định lần cuối. | ĐÃ QUYẾT ĐỊNH |
| DEC-GRD-003 | Một Teacher có quyền chấm; không yêu cầu rubric, chấm ẩn danh hoặc duyệt chéo trong V1. Rubric/tiêu chí có thể ghi trong hướng dẫn câu hỏi, không tạo workflow riêng. | ĐÃ QUYẾT ĐỊNH |
| DEC-GRD-004 | Teacher có quyền sửa điểm đã công bố; phải ghi lý do và lưu lịch sử điểm trước/sau. Admin cũng có thể sửa với audit. | ĐÃ QUYẾT ĐỊNH |
| DEC-GRD-005 | Người tạo chọn thời điểm công bố điểm/feedback. Đáp án/lời giải/điểm từng câu là cấu hình công bố riêng; Exam mặc định ẩn đáp án cho tới khi người phụ trách cho phép. | ĐÃ QUYẾT ĐỊNH |
| DEC-GRD-006 | Progress cơ bản tính từ Result đã chấm và công bố; bài chưa chấm không tính trung bình. Assessment hủy không tính điểm nhưng vẫn hiện trạng thái lịch sử. | ĐÃ QUYẾT ĐỊNH |
| DEC-GRD-007 | Không có chức năng xuất báo cáo riêng trong V1; dùng báo cáo cơ bản trong hệ thống. | ĐÃ QUYẾT ĐỊNH |

## Notification, File, Audit

| ID | Quyết định đã chốt | Trạng thái |
|---|---|---|
| DEC-NOTIF-001 | Event tối thiểu: giao/cập nhật Assessment, đổi/hủy lịch hoặc buổi, công bố kết quả, và thông tin học tập cần hành động. Recipient là người học/Teacher liên quan; Parent chỉ nhận khi liên kết còn hiệu lực và có quyền với nội dung. | ĐÃ QUYẾT ĐỊNH |
| DEC-NOTIF-002 | V1 dùng thông báo trong ứng dụng; không thêm email/SMS/push. | ĐÃ QUYẾT ĐỊNH |
| DEC-NOTIF-003 | Read/unread theo từng người nhận. Mở thông báo đánh dấu đã đọc; một sự kiện không tạo thông báo lặp cho cùng người nhận. | ĐÃ QUYẾT ĐỊNH |
| DEC-NOTIF-004 | Gửi ngay các sự kiện cần hành động; thay đổi/hủy gửi thông báo cập nhật. | ĐÃ QUYẾT ĐỊNH |
| DEC-NOTIF-005 | Giữ thông báo như lịch sử nghiệp vụ; nội dung chỉ mở được khi người nhận còn quyền với ngữ cảnh. Không đặt thời hạn xóa riêng tại Step 5. | ĐÃ QUYẾT ĐỊNH |
| DEC-FILE-001 | Attachment hỗ trợ tài liệu bài tập/câu hỏi, bài nộp và feedback theo QuestionType được bật. Giới hạn định dạng/dung lượng được công bố theo chính sách vận hành; không xóa tệp còn được tham chiếu. | ĐÃ QUYẾT ĐỊNH |
| DEC-FILE-002 | Lưu trữ đối tượng không xóa tệp hay lịch sử; quyền truy cập tệp vẫn theo quyền đối tượng chủ. | ĐÃ QUYẾT ĐỊNH |
| DEC-FILE-003 | Student/Parent/Teacher chỉ tải/xem tệp trong ngữ cảnh họ có quyền; Parent không được xem tệp bài làm riêng tư nếu không được công bố cho Parent. | ĐÃ QUYẾT ĐỊNH |
| DEC-AUD-001 | Audit bắt buộc cho role/trạng thái User, Parent–Student, membership/phân công, Attendance, phát hành/thu hồi Assessment, Submission, chấm/công bố/sửa điểm, lịch và tệp liên quan bài nộp. Lưu actor, thời điểm, đối tượng, thay đổi trước/sau và lý do khi sửa nghiệp vụ nhạy cảm. | ĐÃ QUYẾT ĐỊNH |
| DEC-AUD-002 | Admin xem toàn bộ AuditLog; Teacher xem lịch sử nghiệp vụ trong lớp được phân công. Giữ audit cùng thời gian lưu hồ sơ học tập; không tự đặt hạn xóa ngắn. | ĐÃ QUYẾT ĐỊNH |
| DEC-AUD-003 | Lưu business history riêng cho Attendance, Grade/Result, Schedule/LessonSession, Membership/Teacher assignment và Parent–Student; AuditLog không thay thế các lịch sử này. | ĐÃ QUYẾT ĐỊNH |
| DEC-AUD-004 | AuditLog cho phép before_data/after_data/reason nullable ở mức schema; theo action, các trường mà business rule yêu cầu (ví dụ sửa Attendance/Grade/membership/lịch cần reason và before/after) phải được xác thực bắt buộc trước khi ghi log thành công. | ĐÃ QUYẾT ĐỊNH |
| DEC-AUD-005 | Với event audit bắt buộc (`DEC-AUD-001`), AuditLog được ghi trong cùng giao dịch với thay đổi dữ liệu. Nếu ghi audit thất bại thì toàn bộ thay đổi bị hủy và người dùng nhận lỗi để thử lại — không có trường hợp thao tác thành công mà thiếu audit. Event ngoài danh sách bắt buộc chưa có quy định; không tự thêm kiểu "ghi audit thử rồi bỏ qua lỗi" khi chưa có quyết định riêng. | ĐÃ QUYẾT ĐỊNH |

## Checklist hoàn tất

Toàn bộ nhóm User; Parent–Student; Class/membership/Attendance; Schedule/LessonSession; Assessment/Attempt/Submission; Question Bank; grading/result; Notification; Attachment; Audit đã được chốt theo bảng trên. Các ngưỡng định lượng phụ thuộc chính sách vận hành (như dung lượng tệp, mật khẩu) được quy định ở bước thiết kế/vận hành thích hợp, không còn là khoảng trống nghiệp vụ của Step 5.
