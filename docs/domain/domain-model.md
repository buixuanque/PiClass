# Step 4 — Domain Model

**Trạng thái:** Bản domain model để review; kế thừa Product Definition, Feature Map v0.1 và User Flow hiện có.  
**Phạm vi:** Mô hình khái niệm, trách nhiệm, quan hệ, lực lượng quan hệ, vòng đời và quy tắc nghiệp vụ. Đây không phải thiết kế cơ sở dữ liệu, API hay giao diện.  
**Quy ước:** `[CẦN QUYẾT ĐỊNH]` là chính sách chưa được các bước trước chốt. Các trạng thái dưới đây là mô hình khái niệm; tên/trạng thái chính thức cần xác nhận nếu được đánh dấu.

## 1. Nguyên tắc mô hình

1. `User` là danh tính đăng nhập; hồ sơ `Student`, `Parent`, `Teacher` là vai trò nghiệp vụ của một người. Một User có thể mang nhiều Role, trong đó Admin và Teacher có thể cùng tồn tại.
2. Lịch học (`Schedule`) là kế hoạch; buổi học (`LessonSession`) là lần học cụ thể. Điểm danh thuộc về buổi học cụ thể.
3. Assessment là hoạt động đánh giá được giao. Assignment, Quiz và Exam là các hình thức/ngữ cảnh nghiệp vụ của Assessment, nhưng mức độ khác biệt cần được chốt.
4. Câu hỏi trong ngân hàng có thể tái sử dụng. Khi được đưa vào một assessment đã phát hành, nội dung, đáp án và cấu hình chấm dùng cho bài đó phải ổn định để không làm đổi ngược các bài làm đã bắt đầu.
5. Một lần làm (`Attempt`) là một phiên thực hiện; `Submission` là nội dung bài làm được nộp/ghi nhận. Hai khái niệm có thể gộp trong cách gọi hiện tại, nhưng domain tách chúng để diễn đạt đúng nhiều lần làm và trạng thái trước/sau khi nộp.
6. Điểm và kết quả là kết quả của việc đánh giá một bài làm; kết quả chưa công bố không được hiển thị cho Student/Parent.
7. Quan hệ có thời hạn như thành viên lớp, phân công giáo viên và Parent–Student cần giữ lịch sử hiệu lực. Việc kết thúc quan hệ không xóa lịch sử học tập phát sinh trước đó.
8. V1 không bao gồm thanh toán, quản lý học phí, AI, video, lớp trực tuyến hay chat.

## 2. Ranh giới domain và các khái niệm

### 2.1 Identity & Access

| Entity / khái niệm | Trách nhiệm miền |
|---|---|
| `User` | Danh tính có thể đăng nhập; thông tin xác thực, trạng thái tài khoản và liên kết tới hồ sơ nghiệp vụ. Vô hiệu hóa ngăn truy cập mới nhưng không xóa lịch sử học tập. |
| `Role` | Nhóm trách nhiệm nghiệp vụ như Admin, Teacher, Student, Parent. Role không đồng nghĩa với hồ sơ người đó. |
| `Permission` | Hành động được phép trong một phạm vi nghiệp vụ; được gán qua Role hoặc chính sách tương đương. Danh mục chi tiết thuộc Step 5. |
| `RoleAssignment` | Ghi nhận User được gán Role, người/thời điểm gán và thời gian hiệu lực nếu cần. Cho phép một User có nhiều Role và giữ dấu lịch sử thay đổi quyền. |
| `Profile` | Khái niệm chung cho thông tin cá nhân/liên hệ. Có thể là thông tin chung của User hoặc thông tin riêng theo hồ sơ Student/Parent/Teacher. Cách phân ranh trường dữ liệu: [CẦN QUYẾT ĐỊNH]. |

**Quy tắc:** User có thể có hồ sơ nghiệp vụ mà chưa có tài khoản, nếu nghiệp vụ tạo hồ sơ trước rồi mời kích hoạt. User có thể đồng thời là Admin và Teacher. Bắt buộc tài khoản cho Student/Parent/Teacher ngay khi tạo hồ sơ chưa được chốt. RoleAssignment kết thúc không xóa hành động đã thực hiện dưới quyền đó.

### 2.2 People

| Entity | Trách nhiệm miền |
|---|---|
| `Student` | Hồ sơ người học, trạng thái hoạt động và lịch sử tham gia học. Có thể liên kết với User; tham gia Class qua ClassMember. |
| `Parent` | Hồ sơ người giám hộ/phụ huynh; được quyền theo dõi những Student có liên kết hiệu lực. Có thể liên kết với User. |
| `Teacher` | Hồ sơ người dạy; tham gia lớp qua phân công giáo viên. Có thể liên kết với User và đồng thời mang Role khác. |
| `ParentStudentRelationship` | Quan hệ có hiệu lực giữa Parent và Student, xác định quyền theo dõi và có thể lưu loại quan hệ/xác minh. Một Parent có thể theo dõi nhiều Student; một Student có thể có nhiều Parent. |

**Vòng đời quan hệ Parent–Student:** tạo/liên kết → hiệu lực → gỡ liên kết/kết thúc. Kết thúc liên kết thu hồi quyền xem hiện hành theo chính sách; lịch sử học tập của Student không bị xóa. Quyền xem dữ liệu cũ sau khi gỡ liên kết và yêu cầu còn ít nhất một Parent: [CẦN QUYẾT ĐỊNH].

### 2.3 Academic

| Entity | Trách nhiệm miền |
|---|---|
| `Subject` | Danh mục môn học để phân loại Class, Question và hoạt động học tập. Có trạng thái hoạt động/lưu trữ. |
| `Class` | Nhóm học tập thuộc một Subject; phạm vi tổ chức học viên, giáo viên, lịch, buổi học và hoạt động được giao. Lớp có thể được lưu trữ mà vẫn tra cứu lịch sử. |
| `ClassMember` | Tư cách Student tham gia Class, gồm thời điểm bắt đầu/kết thúc hiệu lực và trạng thái. Dùng để xác định thành viên tại một buổi học hoặc thời điểm giao bài. |
| `TeacherClassAssignment` | Phân công Teacher vào Class với thời hạn hiệu lực; hỗ trợ nhiều giáo viên cho một lớp và lịch sử phụ trách. Teacher chính hay đồng phụ trách chưa chốt. |
| `Schedule` | Quy tắc/kế hoạch thời gian cho một Class; có thể định kỳ hoặc một lần. Thay đổi lịch không viết lại sự kiện đã diễn ra. |
| `LessonSession` | Buổi học cụ thể của một Class, phát sinh từ Schedule hoặc được tạo thủ công; có thời gian, trạng thái, nội dung đã dạy và ghi chú. Là ngữ cảnh cho Attendance và có thể liên kết Assessment. |

**Cardinality cốt lõi:** Subject 1—N Class; Class 1—N ClassMember theo thời gian; Student 1—N ClassMember theo thời gian; Class N—N Teacher qua TeacherClassAssignment; Class 1—N Schedule; Class 1—N LessonSession; Schedule 0..1—N LessonSession theo cách sinh lịch chưa chốt.

**Vòng đời ClassMember:** dự kiến → hiệu lực → kết thúc/rút khỏi lớp. Việc chuyển lớp kết thúc tư cách thành viên cũ và tạo tư cách mới; không chuyển điểm danh, bài làm hoặc kết quả cũ sang lớp mới. Quy tắc ngày hiệu lực và xử lý thay đổi hồi tố: [CẦN QUYẾT ĐỊNH].

**Vòng đời Schedule:** nháp/hoạt động → sửa, dời hoặc hủy. `LessonSession` có thể được tạo theo lịch và được dời/hủy riêng; hủy Schedule không xóa buổi đã diễn ra. Mô hình lặp, múi giờ, xung đột lịch và liên kết lịch lặp–buổi: [CẦN QUYẾT ĐỊNH].

**Vòng đời LessonSession:** dự kiến → đã diễn ra/hoàn tất hoặc bị hủy. Buổi có thể tồn tại không gắn Schedule. Trạng thái chính thức, điều kiện hoàn tất, số thứ tự và cách xử lý buổi hủy sau khi đã điểm danh: [CẦN QUYẾT ĐỊNH].

### 2.4 Attendance

| Entity | Trách nhiệm miền |
|---|---|
| `AttendanceRecord` | Trạng thái tham dự của một Student tại một LessonSession; giá trị nghiệp vụ dự kiến là Present, Absent, Late, Excused, kèm ghi chú và người ghi nhận. |

**Cardinality:** một LessonSession có 0..N AttendanceRecord; một Student có 0..N AttendanceRecord; mỗi bản ghi gắn chính xác với một buổi và một học viên. User flow nêu một trạng thái cho mỗi học viên mỗi buổi. Quy tắc duy nhất theo cặp buổi–học viên, sửa đổi, khóa chỉnh sửa, lưu lịch sử và điểm danh hồi tố: [CẦN QUYẾT ĐỊNH]. Không tạo điểm danh cho học viên không phải thành viên có hiệu lực tại thời điểm buổi học, trừ khi có chính sách ngoại lệ.

### 2.5 Learning Content & Question Engine

| Entity / khái niệm | Trách nhiệm miền |
|---|---|
| `QuestionBank` | Tập hợp câu hỏi có phạm vi sở hữu/chia sẻ; có thể là kho cá nhân giáo viên hoặc kho dùng chung. Phạm vi chia sẻ chưa chốt. |
| `Question` | Định nghĩa câu hỏi tái sử dụng: nội dung, loại, điểm gợi ý, độ khó, Subject/Topic/Tag và dữ liệu chấm phù hợp với loại. Có thể lưu trữ thay vì xóa khi đã được dùng. |
| `QuestionOption` | Một phương án/đơn vị lựa chọn thuộc câu hỏi dạng chọn đáp án; có thể mang nội dung và thông tin đúng/sai. Không áp dụng cho mọi QuestionType. |
| `Topic` | Chủ đề phân loại câu hỏi/tiến độ trong Subject; cấu trúc phân cấp có hay không: [CẦN QUYẾT ĐỊNH]. |
| `Tag` | Nhãn linh hoạt để tìm kiếm và phân nhóm câu hỏi; có thể dùng lại trong phạm vi được xác định. |
| `QuestionType` | Khái niệm mô tả cách trình bày và trả lời: Single Choice, Multiple Choice, True/False, Fill in the Blank, Short Answer, Essay, Matching, Ordering, File Upload. Danh sách V1 còn mở. |
| `AssessmentQuestion` | Câu hỏi được đưa vào một Assessment cụ thể, cùng thứ tự, điểm áp dụng, cấu hình chấm và phiên bản nội dung/đáp án được cố định cho Assessment. Có thể tham chiếu Question nguồn nhưng không được làm thay đổi bài đã phát hành khi nguồn được sửa. |

Question Engine là tập hợp quy tắc theo `QuestionType`: cấu trúc câu trả lời hợp lệ, cách lưu câu trả lời, điều kiện tự chấm và dữ liệu cần Teacher đánh giá. Engine không phải một Assessment hay bài làm. Tự chấm chỉ áp dụng khi có đáp án và quy tắc xác định; các câu khác chuyển chấm thủ công. Quy tắc chấm từng phần, chấp nhận nhiều đáp án, chuẩn hóa văn bản và cấu trúc dữ liệu câu hỏi: [CẦN QUYẾT ĐỊNH].

**Cardinality:** QuestionBank 1—N Question; Question N—N Tag; Question 0..N—Topic (một hay nhiều Topic: [CẦN QUYẾT ĐỊNH]); Assessment 1—N AssessmentQuestion; Question 0..N AssessmentQuestion theo tái sử dụng. AssessmentQuestion giữ cấu hình bất biến theo phiên bản phát hành.

### 2.6 Assessment: Assignment, Quiz, Exam

| Entity / khái niệm | Trách nhiệm miền |
|---|---|
| `Assessment` | Hoạt động đánh giá được tạo, cấu hình, giao cho lớp/học viên, phát hành/đóng và chấm. Gồm tiêu đề, hướng dẫn, thời gian, số lượt, thang điểm, tiêu chí đạt, quy tắc hiển thị và tập AssessmentQuestion phù hợp. |
| `AssessmentType` | Phân loại nghiệp vụ Assignment, Quiz hoặc Exam. Mô hình khuyến nghị ở miền: một Assessment có một type và type quy định tập cấu hình hợp lệ; cần quyết định nếu loại này chỉ là cấu hình linh hoạt hay là các subtype riêng. |
| `AssessmentAudience` | Tập người nhận: Class, Student được chọn, hoặc cả hai theo quy tắc đã chốt. Xác định học viên đủ điều kiện làm và thời điểm hiệu lực. |
| `AssessmentRelease` | Sự kiện/phiên bản phát hành đóng băng nội dung và đối tượng nhận; cần giữ mốc phát hành để bài làm tham chiếu đúng nội dung. Cách quản lý phiên bản phát hành: [CẦN QUYẾT ĐỊNH]. |

#### Phân biệt nghiệp vụ dự kiến

- **Assignment:** trọng tâm là giao bài và hạn nộp; có thể có câu hỏi hoặc tài liệu; cần chính sách nộp trễ, nộp lại và gia hạn. Có thể gắn LessonSession.
- **Quiz:** kiểm tra nhanh; cấu hình thời lượng, số attempt, điểm đạt, chọn/xáo trộn câu hỏi và thời điểm công bố kết quả.
- **Exam:** kỳ kiểm tra chính thức; ngoài cấu hình bài làm có khung thời gian thi, điều kiện vào thi, tự lưu, hết giờ và xử lý gián đoạn/cấp lại lượt.

Đây là phân biệt dựa trên Feature Map/User Flow, chưa phải quyết định rằng chúng là ba aggregate/entity riêng. Đề xuất domain hiện tại là giữ chung khái niệm `Assessment`, dùng `AssessmentType` và các chính sách/cấu hình khác nhau; không nhân bản ba bộ vòng đời trừ khi quy tắc pháp lý/vận hành yêu cầu. **[CẦN QUYẾT ĐỊNH]** Chốt mô hình hợp nhất hay subtype, thuộc tính bắt buộc riêng của từng loại, và khác biệt nào là quy tắc bắt buộc.

**Vòng đời Assessment:** nháp → phát hành/mở → đóng hoặc kết thúc → lưu trữ. Có thể thu hồi phát hành hoặc mở lại theo quyền/chính sách. Sửa sau phát hành không được âm thầm thay đổi nội dung mà Student đang làm hoặc bài đã nộp. Cách tác động đến Attempt đang diễn ra khi đóng, thu hồi, sửa hoặc hủy: [CẦN QUYẾT ĐỊNH].

**Cardinality:** Assessment 1—N AssessmentQuestion; Assessment 1—N AssessmentAudience; Assessment 1—N Attempt; Assessment có 0..1 LessonSession liên kết theo flow Assignment hiện tại (có cho liên kết nhiều buổi hay không: [CẦN QUYẾT ĐỊNH]); Class 0..N Assessment. Assessment có thể giao trực tiếp cho Student hoặc thông qua Class. Nếu cùng học viên nhận qua cả hai đường, quy tắc chống giao trùng: [CẦN QUYẾT ĐỊNH].

### 2.7 Attempt, Submission, Answer

| Entity | Trách nhiệm miền |
|---|---|
| `Attempt` | Một lượt Student bắt đầu một Assessment; theo dõi số lượt, thời điểm bắt đầu/kết thúc, trạng thái, hạn/thời lượng áp dụng, nguyên nhân kết thúc (nộp, timeout, hủy/cấp lại) và bộ câu hỏi cụ thể nếu có chọn ngẫu nhiên. |
| `Submission` | Bản nộp gắn với Attempt, đại diện nội dung được hệ thống tiếp nhận tại thời điểm nộp. Hỗ trợ lưu nháp trong Attempt và chốt snapshot khi nộp; với assignment có nhiều lần nộp, mỗi phiên bản cần quy tắc chọn bản có hiệu lực. |
| `Answer` | Câu trả lời cho một AssessmentQuestion trong một Attempt/Submission; lưu giá trị trả lời theo QuestionType và thông tin phục vụ chấm. |

**Quan hệ và cardinality:** Student 1—N Attempt; Assessment 1—N Attempt; Attempt 1—0..1 Submission cuối cùng theo mô hình đề xuất; Attempt 1—N Answer; mỗi Answer gắn một AssessmentQuestion và một Attempt. Nếu hỗ trợ nhiều phiên bản nộp, Attempt 1—N SubmissionVersion và chỉ một bản được xác định là bản chấm chính. Quan hệ chính thức giữa Attempt và Submission cần chốt theo chính sách nộp lại.

**Phân biệt trọng tâm:** Attempt mô tả lượt làm và đồng hồ/trạng thái; Submission mô tả dữ liệu được gửi/tiếp nhận; Answer là chi tiết theo câu. Khi Student bắt đầu Quiz/Exam, tạo Attempt. Khi trả lời, Answer thay đổi trong vùng bài làm đang mở và được lưu theo chính sách. Khi nộp hoặc timeout, nội dung đã nhận được chốt thành Submission. Nộp lại có thể là Attempt mới hoặc phiên bản Submission mới; User Flow hiện chưa chốt.

**Vòng đời Attempt dự kiến:** chưa bắt đầu → đang làm → đã nộp/hết giờ → đang chấm → đã chấm → kết quả đã công bố; có thể bị hủy/thu hồi trong trường hợp ngoại lệ. Đây là các trạng thái khái niệm, không nhất thiết là một chuỗi tuyến tính duy nhất. Quy tắc tiếp tục sau thoát, đồng hồ khi mất kết nối, autosave, timeout và cấp lại Attempt: [CẦN QUYẾT ĐỊNH].

**Quy tắc:** số Attempt được phép phụ thuộc Assessment; mỗi Attempt chỉ tính một lần theo chính sách. Hệ thống dùng thời điểm tiếp nhận Submission để xác định đúng hạn. Student không thể ghi Answer sau khi Attempt đã khóa, trừ quy trình mở lại được ghi nhận. Phụ huynh chỉ theo dõi, không tạo hoặc sửa Attempt/Submission cho Student.

### 2.8 Grading, Result, Feedback

| Entity / khái niệm | Trách nhiệm miền |
|---|---|
| `Grade` | Đánh giá điểm cho một Answer hoặc toàn Submission; có thể do hệ thống tự chấm hoặc Teacher chấm, kèm người/phương thức chấm và lịch sử sửa nếu cần. |
| `Result` | Tổng hợp điểm/đạt-không đạt/trạng thái hoàn tất cho một Submission đã chấm. Gắn với Attempt/Submission cụ thể; chỉ có ý nghĩa được người học nhìn thấy khi công bố. |
| `Feedback` | Nhận xét tổng quát hoặc theo câu của Teacher/hệ thống, có thể gồm tệp đính kèm và trạng thái riêng tư/công bố. |
| `GradingPolicy` | Quy tắc chấm như trọng số, điểm tối đa, điểm từng phần, cách làm tròn và điểm đạt. Một phần cấu hình gắn với Assessment; các chính sách chưa chốt không được tự đặt. |
| `ResultPublication` | Sự kiện công bố/trả kết quả để phân biệt bài đã chấm với bài người học được quyền xem. Thời điểm và phạm vi hiển thị cần chốt. |

**Cardinality:** Submission 0..N Grade (theo câu và tổng); Submission 0..1 Result hiện hành, với lịch sử phiên bản nếu điểm sửa; Grade 0..N Feedback; Assessment quy định GradingPolicy; Result được công bố qua ResultPublication. Nhiều giáo viên chấm, duyệt điểm và rubric: [CẦN QUYẾT ĐỊNH].

**Vòng đời chấm:** chờ chấm → chấm một phần/đầy đủ → kết quả sẵn sàng → đã công bố; điểm công bố có thể được sửa chỉ theo quyền và có lý do/lịch sử. Không hiển thị điểm chưa công bố cho Student/Parent. Nếu câu bắt buộc chấm tay còn thiếu, không công bố kết quả tổng trừ khi có chính sách cho phép.

**Quy tắc điểm:** điểm từng câu không vượt quá điểm tối đa; điểm tổng dùng thang và trọng số của Assessment; cách làm tròn, điểm phần, quy đổi phần trăm/xếp loại và kết quả khi một Attempt có nhiều lần làm: [CẦN QUYẾT ĐỊNH]. `Result` không đồng nghĩa với mọi câu trả lời hay tiến độ tổng hợp của học viên; các báo cáo tiến độ có thể tính từ Result đã công bố và dữ kiện liên quan.

### 2.9 Notification, File, Audit

| Entity | Trách nhiệm miền |
|---|---|
| `Notification` | Thông điệp nội bộ sinh từ sự kiện hoặc tạo bởi người có quyền; có người nhận, ngữ cảnh liên quan, thời điểm và trạng thái đọc. V1 dự kiến in-app. |
| `Attachment` | Tài liệu/tệp được gắn vào Assessment, câu hỏi, Answer dạng File Upload hoặc Feedback. Domain theo dõi quyền sở hữu, ngữ cảnh sử dụng và trạng thái sẵn sàng; cách lưu vật lý không thuộc tài liệu này. |
| `AuditLog` | Dấu vết hành động quan trọng: actor, loại hành động, đối tượng nghiệp vụ, thời điểm, lý do và thay đổi cần truy nguyên. Không thay thế lịch sử nghiệp vụ của Grade, Schedule, ClassMember hay liên kết. |

**Notification:** User 1—N Notification; notification có thể tham chiếu một sự kiện/ngữ cảnh (Assessment, Result, Schedule, LessonSession, Attendance) nhưng không sở hữu dữ liệu nghiệp vụ đó. Thu hồi liên kết Parent–Student phải ngăn gửi nội dung mới không còn được phép. Kênh ngoài ứng dụng, thời hạn lưu và quy tắc gộp/chống trùng: [CẦN QUYẾT ĐỊNH].

**Attachment:** có thể được dùng bởi nhiều loại đối tượng miền theo quy tắc chia sẻ; quyền truy cập tệp kế thừa/được giới hạn bởi quyền truy cập đối tượng chủ. Xóa tệp đang được dùng, giới hạn loại/kích thước và thời gian lưu: [CẦN QUYẾT ĐỊNH].

**AuditLog:** ghi tối thiểu các thay đổi nhạy cảm như quyền, trạng thái User, thành viên/phân công, điểm danh, phát hành Assessment, nộp bài, chấm/công bố/sửa điểm và thay đổi liên kết Parent–Student. Phạm vi bắt buộc, ai được xem và thời hạn lưu: [CẦN QUYẾT ĐỊNH].

## 3. Sơ đồ quan hệ ở mức domain

Sơ đồ thể hiện quan hệ nghiệp vụ và lực lượng khái niệm, không thể hiện bảng, khóa, cột hay kiểu dữ liệu.

```mermaid
erDiagram
    USER ||--o{ ROLE_ASSIGNMENT : "được gán"
    ROLE ||--o{ ROLE_ASSIGNMENT : "được gán cho"
    ROLE }o--o{ PERMISSION : "được cấu hình quyền"
    USER o|--o| STUDENT : "có hồ sơ"
    USER o|--o| PARENT : "có hồ sơ"
    USER o|--o| TEACHER : "có hồ sơ"
    PARENT ||--o{ PARENT_STUDENT_RELATIONSHIP : "theo dõi"
    STUDENT ||--o{ PARENT_STUDENT_RELATIONSHIP : "được theo dõi"
    SUBJECT ||--o{ CLASS : "phân loại"
    CLASS ||--o{ CLASS_MEMBER : "có thành viên theo thời gian"
    STUDENT ||--o{ CLASS_MEMBER : "tham gia theo thời gian"
    CLASS ||--o{ TEACHER_CLASS_ASSIGNMENT : "được phụ trách"
    TEACHER ||--o{ TEACHER_CLASS_ASSIGNMENT : "được phân công"
    CLASS ||--o{ SCHEDULE : "có kế hoạch"
    CLASS ||--o{ LESSON_SESSION : "có buổi học"
    SCHEDULE o|--o{ LESSON_SESSION : "có thể sinh"
    LESSON_SESSION ||--o{ ATTENDANCE_RECORD : "ghi nhận"
    STUDENT ||--o{ ATTENDANCE_RECORD : "được điểm danh"
    QUESTION_BANK ||--o{ QUESTION : "chứa"
    QUESTION ||--o{ QUESTION_OPTION : "có phương án"
    QUESTION ||--o{ ASSESSMENT_QUESTION : "được dùng qua phiên bản"
    ASSESSMENT ||--|{ ASSESSMENT_QUESTION : "gồm"
    ASSESSMENT ||--o{ ASSESSMENT_AUDIENCE : "giao cho"
    CLASS o|--o{ ASSESSMENT_AUDIENCE : "nhận qua lớp"
    STUDENT o|--o{ ASSESSMENT_AUDIENCE : "nhận trực tiếp"
    ASSESSMENT ||--o{ ATTEMPT : "được thực hiện qua"
    STUDENT ||--o{ ATTEMPT : "thực hiện"
    ATTEMPT ||--o{ ANSWER : "có câu trả lời"
    ASSESSMENT_QUESTION ||--o{ ANSWER : "được trả lời"
    ATTEMPT ||--o| SUBMISSION : "chốt thành"
    SUBMISSION ||--o{ GRADE : "được chấm"
    SUBMISSION ||--o| RESULT : "có kết quả hiện hành"
    GRADE ||--o{ FEEDBACK : "có nhận xét"
    USER ||--o{ NOTIFICATION : "nhận"
    USER ||--o{ AUDIT_LOG : "thực hiện hành động"
    ASSESSMENT o|--o{ ATTACHMENT : "có tài liệu"
    SUBMISSION o|--o{ ATTACHMENT : "có tệp nộp"
    FEEDBACK o|--o{ ATTACHMENT : "có tệp đính kèm"
```

Ghi chú: Quan hệ `ROLE`–`PERMISSION` có thể cần entity liên kết nếu quyền được gán trực tiếp và quản lý lịch sử. `ATTEMPT`–`SUBMISSION` thể hiện mô hình đề xuất một bản nộp cuối mỗi lượt; nếu cho nộp nhiều phiên bản, quan hệ sẽ là một-nhiều và phải xác định bản được chấm. Các nhãn `o|`/`o{` biểu thị tùy chọn ở mức khái niệm; điều kiện thực tế phụ thuộc chính sách ở danh sách quyết định.

## 4. Quy tắc domain tổng hợp

1. Quyền truy cập được giới hạn bởi Role/Permission và quan hệ nghiệp vụ hiện hành: Teacher theo lớp được phân công; Student theo dữ liệu của mình; Parent theo liên kết Parent–Student còn hiệu lực.
2. Hồ sơ người và tài khoản đăng nhập có vòng đời độc lập; vô hiệu hóa User không xóa hồ sơ hoặc dữ liệu học tập.
3. Quan hệ thành viên lớp, phân công Teacher và Parent–Student có thời gian hiệu lực; kết thúc quan hệ không xóa lịch sử phát sinh.
4. Attendance được ghi theo LessonSession và Student; LessonSession bị hủy không tự xóa Attendance đã ghi.
5. Assessment được giao cho Class hoặc Student; Student chỉ được bắt đầu nếu đủ điều kiện theo trạng thái, thời gian, đối tượng nhận và số lượt.
6. Nội dung AssessmentQuestion phải ổn định sau phát hành đối với Attempt đã tạo; thay đổi câu nguồn không viết ngược lịch sử.
7. Attempt tách biệt từng lượt làm; Submission chốt nội dung được hệ thống tiếp nhận; thời điểm hệ thống nhận là căn cứ xác định nộp đúng hạn.
8. Tự chấm chỉ thực hiện khi đáp án và quy tắc chấm xác định; phần cần đánh giá người dạy phải hoàn tất trước khi công bố theo chính sách.
9. Result và Feedback chỉ hiển thị cho Student/Parent sau khi công bố và trong phạm vi quyền.
10. Sửa điểm, điểm danh, phân công, lịch hoặc nội dung đã phát hành phải giữ dấu vết theo chính sách Audit/History; phạm vi lưu vết chi tiết: [CẦN QUYẾT ĐỊNH].
11. Attachment không mở rộng quyền đọc đối tượng mà nó gắn vào; người truy cập tệp phải có quyền với ngữ cảnh nghiệp vụ.
12. Không đưa Payment hoặc Tuition Management vào các khái niệm V1; chúng là hướng tương lai ngoài phạm vi domain hiện hành.

## 5. Các quyết định cần chốt trước Step 5

1. **Quiz vs Exam vs Assignment:** dùng một `Assessment` với type/cấu hình hay các subtype nghiệp vụ; chính sách nào bắt buộc riêng cho từng loại; Assignment có thời hạn/nộp lại khác Quiz/Exam ra sao.
2. **LessonSession và Schedule:** trạng thái chính thức của LessonSession; điều kiện hoàn tất; quy tắc tạo buổi từ lịch lặp, dời/hủy riêng, số thứ tự và xử lý điểm danh khi hủy.
3. **Class & membership:** ngày hiệu lực khi thêm/rút/chuyển lớp; cho phép lớp thiếu Teacher/học viên không; Teacher chính hay đồng phụ trách; quy tắc lịch sử hồi tố.
4. **Parent–Student:** loại quan hệ cần lưu, cách xác minh, giới hạn số Parent/Student, yêu cầu tối thiểu một Parent, quyền xem dữ liệu lịch sử sau khi gỡ liên kết.
5. **User & Profile:** hồ sơ nào bắt buộc có User; có thể tạo Student/Parent/Teacher chưa có account trong V1 không; ranh giới dữ liệu Profile chung và hồ sơ nghiệp vụ.
6. **Attendance:** xác nhận một bản ghi mỗi Student mỗi LessonSession; ai được sửa, thời hạn khóa, sửa hồi tố, lưu lịch sử, lý do Excused và có cho Parent gửi giải trình không.
7. **Question Engine:** chốt các QuestionType V1; quyền sở hữu/chia sẻ QuestionBank; Topic đơn hay phân cấp; quy tắc tự chấm, chấm từng phần, chuẩn hóa câu trả lời và thay đổi câu hỏi đang được dùng.
8. **Assessment release:** phát hành có tạo phiên bản bất biến không; sửa/thu hồi/mở lại Assessment ảnh hưởng người đã bắt đầu và đã nộp thế nào; đối tượng giao trùng qua Class và Student được xử lý ra sao.
9. **Attempt & Submission:** số lượt và cách tính lượt; nộp lại là Attempt mới hay phiên bản Submission; cho nộp trễ/nộp từng phần; tiếp tục sau thoát; autosave, timeout, mất kết nối và cấp lại lượt Exam.
10. **Grading & Result:** chọn điểm cao nhất/lần cuối/trung bình khi nhiều lượt; trọng số, điểm phần, làm tròn, điểm đạt, xếp loại; rubric, nhiều người chấm/duyệt, quyền sửa điểm đã công bố và lịch sử phiên bản.
11. **Publication & progress:** thời điểm Student/Parent được xem điểm, đáp án và feedback; công thức tiến độ/tổng hợp; cách xử lý bài chưa chấm hoặc Assessment bị hủy.
12. **Notification:** loại sự kiện V1, người nhận Parent theo quan hệ hiện hành, trạng thái đọc, kênh ngoài in-app, gộp/chống trùng và thời hạn lưu.
13. **Attachment:** giới hạn nội dung, vòng đời tệp, quyền xóa/thay thế và quy tắc lưu giữ khi đối tượng nghiệp vụ bị lưu trữ.
14. **AuditLog:** hành động nào bắt buộc ghi, ai được xem, lưu bao lâu và mức chi tiết thay đổi cần giữ.
15. **Chuẩn hóa thuật ngữ:** xác nhận dùng `Attempt`, `Submission` hay “Bài làm/Bài nộp” trong tài liệu và nghiệp vụ; xác nhận tên trạng thái hiển thị tiếng Việt.

Khi các quyết định này được chốt, Step 5 có thể chuyển domain đã thống nhất thành mô hình dữ liệu mà không phải tự suy diễn chính sách nghiệp vụ.
