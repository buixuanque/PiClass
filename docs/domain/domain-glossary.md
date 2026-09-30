# Step 4 — Bảng thuật ngữ Domain

**Mục đích:** Giải thích ngắn gọn các thuật ngữ và entity trong [domain-model.md](domain-model.md). Tên tiếng Anh được giữ để nhất quán với Feature Map/User Flow và có thể dùng làm tên domain trong các bước sau.  
**Lưu ý:** Mô tả này ở mức nghiệp vụ; không quy định cấu trúc database/API.

## Identity & Access

| Thuật ngữ | Giải thích |
|---|---|
| `User` — Người dùng | Danh tính đăng nhập vào hệ thống. Có thể liên kết với một hoặc nhiều vai trò nghiệp vụ; vô hiệu hóa tài khoản không xóa lịch sử học tập. |
| `Profile` — Hồ sơ | Thông tin cá nhân/liên hệ của người dùng hoặc hồ sơ nghiệp vụ. Ranh giới giữa hồ sơ chung và hồ sơ Student/Parent/Teacher cần chốt. |
| `Role` — Vai trò | Nhóm trách nhiệm như Admin, Teacher, Student, Parent. Role mô tả người dùng có thể đảm nhiệm loại công việc nào. |
| `Permission` — Quyền | Hành động được cho phép trong một phạm vi. Danh mục quyền cụ thể sẽ được xác định ở Step 5. |
| `RoleAssignment` — Gán vai trò | Quan hệ ghi nhận một User mang một Role trong một khoảng hiệu lực. Một User có thể có nhiều Role, ví dụ Admin và Teacher. |

## People

| Thuật ngữ | Giải thích |
|---|---|
| `Student` — Học viên | Hồ sơ người học, trạng thái hoạt động và lịch sử tham gia lớp, buổi học, bài làm và kết quả. |
| `Parent` — Phụ huynh | Hồ sơ người theo dõi một hoặc nhiều Student đã liên kết; không làm bài hay sửa dữ liệu học tập thay Student. |
| `Teacher` — Giáo viên | Hồ sơ người dạy được phân công vào lớp; có thể cùng User mang Role Admin. |
| `ParentStudentRelationship` — Quan hệ phụ huynh–học viên | Liên kết có hiệu lực dùng để xác định Parent được xem dữ liệu của Student nào. Gỡ liên kết thu hồi quyền hiện hành nhưng không xóa lịch sử học tập. |

## Academic

| Thuật ngữ | Giải thích |
|---|---|
| `Subject` — Môn học | Danh mục môn như Toán hoặc Tiếng Anh; dùng để phân loại lớp và nội dung học tập. |
| `Class` — Lớp học | Nhóm học tập có môn học, thành viên, giáo viên, lịch, buổi học và hoạt động được giao. Lưu trữ lớp vẫn giữ lịch sử. |
| `ClassMember` — Thành viên lớp | Tư cách một Student tham gia một Class trong khoảng thời gian xác định. Chuyển lớp kết thúc tư cách cũ và tạo tư cách mới. |
| `TeacherClassAssignment` — Phân công giáo viên vào lớp | Quan hệ có thời hạn giữa Teacher và Class, cho phép truy nguyên ai phụ trách lớp ở từng giai đoạn. |
| `Schedule` — Lịch học | Kế hoạch thời gian của lớp, có thể lặp hoặc diễn ra một lần. Đây là kế hoạch, không phải bằng chứng buổi học đã diễn ra. |
| `LessonSession` — Buổi học | Một lần học cụ thể gắn với Class; có thể phát sinh từ Schedule hoặc tạo thủ công. Là ngữ cảnh của điểm danh và nội dung buổi. |

## Attendance

| Thuật ngữ | Giải thích |
|---|---|
| `AttendanceRecord` — Bản ghi điểm danh | Trạng thái tham dự của một Student trong một LessonSession. Trạng thái dự kiến: Present (có mặt), Absent (vắng), Late (đi muộn), Excused (vắng có phép). |

## Learning Content & Question Engine

| Thuật ngữ | Giải thích |
|---|---|
| `QuestionBank` — Ngân hàng câu hỏi | Tập hợp Question được lưu độc lập với Assignment/Quiz/Exam để tìm kiếm và tái sử dụng. Quyền sở hữu/chia sẻ chưa chốt. |
| `Question` — Câu hỏi | Nội dung câu hỏi có loại, hướng dẫn trả lời, điểm gợi ý và cấu hình/đáp án chấm phù hợp. |
| `QuestionOption` — Phương án câu hỏi | Một lựa chọn trong câu hỏi chọn đáp án; không dùng cho mọi loại câu hỏi. |
| `QuestionType` — Dạng câu hỏi | Cách người học trả lời, ví dụ Single Choice, Multiple Choice, True/False, Fill in the Blank, Short Answer, Essay, Matching, Ordering, File Upload. |
| `Topic` — Chủ đề | Phân loại kiến thức trong một môn; có thể dùng để lọc câu hỏi và xem tiến độ. Cấu trúc phân cấp cần quyết định. |
| `Tag` — Nhãn | Từ khóa linh hoạt gắn với câu hỏi để tìm kiếm hoặc phân nhóm. |
| `Question Engine` — Bộ quy tắc câu hỏi | Khái niệm bao gồm quy tắc hiển thị/trả lời hợp lệ và chấm điểm của từng QuestionType. Không đồng nghĩa với bài kiểm tra hoặc bài làm. |
| `AssessmentQuestion` — Câu hỏi trong bài đánh giá | Cách một Question được dùng trong Assessment cụ thể, gồm thứ tự, điểm áp dụng và phiên bản nội dung/cấu hình chấm đã phát hành. |

## Assessment, bài làm và kết quả

| Thuật ngữ | Giải thích |
|---|---|
| `Assessment` — Hoạt động đánh giá | Khái niệm chung cho bài tập hoặc bài kiểm tra được giao và chấm. Có thể được phân loại thành Assignment, Quiz hoặc Exam. |
| `AssessmentType` — Loại hoạt động đánh giá | Phân biệt Assignment (bài tập), Quiz (bài kiểm tra nhanh) và Exam (kỳ kiểm tra chính thức). Việc dùng một Assessment có cấu hình hay nhiều subtype chưa chốt. |
| `AssessmentAudience` — Đối tượng nhận bài | Lớp hoặc các học viên cụ thể được giao Assessment; dùng để xác định ai đủ điều kiện làm bài. |
| `AssessmentRelease` — Lần phát hành | Mốc đưa Assessment tới người học; có thể cần giữ phiên bản nội dung đã công bố để bài làm không đổi ngược. Cách quản lý phiên bản cần quyết định. |
| `Attempt` — Lượt làm bài | Một lần Student bắt đầu và thực hiện Assessment, có trạng thái, thời điểm và giới hạn thời gian riêng. Một Assessment có thể cho phép nhiều Attempt. |
| `Submission` — Bài nộp | Nội dung bài làm được hệ thống tiếp nhận khi Student nộp hoặc khi hết giờ. Bản nháp trong lúc làm thuộc vòng đời Attempt; chính sách nộp lại chưa chốt. |
| `Answer` — Câu trả lời | Phần trả lời cho một AssessmentQuestion trong một Attempt; có thể được tự chấm hoặc chuyển Teacher chấm. |
| `Grade` — Điểm chấm | Điểm cho câu trả lời hoặc toàn bài, được tạo tự động hay do Teacher nhập; có thể cần lịch sử nếu được sửa. |
| `GradingPolicy` — Quy tắc chấm | Cách tính điểm, điểm tối đa, trọng số, điểm từng phần và ngưỡng đạt của Assessment. Nhiều chi tiết cần chốt. |
| `Result` — Kết quả | Tổng hợp điểm/trạng thái đạt của một bài nộp sau khi chấm. Kết quả có thể đã có nhưng chưa được công bố cho Student/Parent. |
| `Feedback` — Nhận xét | Phản hồi chung hoặc theo câu từ Teacher/hệ thống; có thể đi kèm điểm hoặc tệp. |
| `ResultPublication` — Công bố kết quả | Sự kiện đánh dấu kết quả có thể hiển thị cho người học/phụ huynh theo chính sách. |

### Phân biệt nhanh

```text
Assessment  = hoạt động được giao
Attempt     = một lượt thực hiện
Answer      = câu trả lời trong lượt đó
Submission  = nội dung đã được nộp/tiếp nhận
Grade       = điểm chấm
Result      = kết quả tổng hợp
Feedback    = nhận xét
```

## Các khái niệm dùng chung

| Thuật ngữ | Giải thích |
|---|---|
| `Notification` — Thông báo | Thông điệp gắn với một sự kiện học tập/vận hành và gửi tới User phù hợp; V1 dự kiến dùng trong ứng dụng. |
| `Attachment` — Tệp đính kèm | Tệp được sử dụng trong tài liệu bài tập, câu hỏi tải tệp, bài nộp hoặc phản hồi. Quyền đọc đi theo ngữ cảnh nghiệp vụ. |
| `AuditLog` — Nhật ký kiểm toán/hoạt động | Dấu vết actor, hành động, đối tượng, thời điểm và thay đổi quan trọng để truy nguyên. Không thay thế lịch sử riêng của nghiệp vụ như điểm hoặc phân công. |
| `Lifecycle` — Vòng đời | Các trạng thái và chuyển đổi hợp lệ của một entity, ví dụ Assessment từ nháp sang phát hành rồi đóng. |
| `Cardinality` — Lực lượng quan hệ | Số lượng đối tượng có thể liên hệ, ví dụ một Class có nhiều LessonSession hoặc một Student có nhiều ClassMember theo thời gian. |
| `Domain Model` — Mô hình miền | Mô tả khái niệm nghiệp vụ, trách nhiệm và quan hệ giữa chúng; chưa phải thiết kế bảng cơ sở dữ liệu. |

## Các điểm còn mở

Những nội dung đánh dấu `[CẦN QUYẾT ĐỊNH]` trong tài liệu chính cần được chốt trước Step 5, đặc biệt: ranh giới Quiz/Exam/Assignment; chính sách Attempt/Submission; quy tắc chấm và công bố Result; thời hạn quan hệ Parent–Student và ClassMember; vòng đời Schedule/LessonSession; phạm vi quyền QuestionBank, Notification, Attachment và AuditLog.
