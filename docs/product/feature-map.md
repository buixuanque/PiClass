# Feature Map v0.1

> Tài liệu mô tả phạm vi chức năng ở mức tổng quan. Đây là **Feature Map v0.1**, chưa đi sâu vào giao diện (UI), API hoặc cơ sở dữ liệu (DB). Các chức năng trong tài liệu là bản nháp để tiếp tục review.

## 1. Tài khoản và xác thực

Nhóm chức năng dùng chung cho Admin, Teacher, Student và Parent.

### 1.1. Xác thực

- Đăng nhập
- Đăng xuất
- Quên mật khẩu
- Đặt lại mật khẩu
- Đổi mật khẩu
- Quản lý phiên đăng nhập

### 1.2. Hồ sơ người dùng

- Xem hồ sơ
- Chỉnh sửa hồ sơ
- Quản lý ảnh đại diện
- Quản lý thông tin liên hệ

### 1.3. Người dùng và vai trò

- Các vai trò: Admin, Teacher, Student, Parent
- Vai trò và quyền hạn

## 2. Cổng quản trị (Admin Site)

Admin Site là nơi quản lý hệ thống và dữ liệu.

### 2.1. Bảng điều khiển

- Tổng số học viên
- Tổng số giáo viên
- Tổng số lớp
- Lịch học hôm nay
- Buổi học hôm nay
- Tình hình điểm danh hôm nay
- Bài tập chưa chấm
- Bài tập sắp đến hạn
- Thống kê học tập cơ bản

### 2.2. Quản lý người dùng

#### Học viên

- Danh sách học viên
- Chi tiết học viên
- Tạo học viên
- Chỉnh sửa học viên
- Kích hoạt hoặc vô hiệu hóa học viên
- Lịch sử học viên

#### Phụ huynh

- Danh sách phụ huynh
- Chi tiết phụ huynh
- Tạo phụ huynh
- Chỉnh sửa phụ huynh
- Quản lý quan hệ phụ huynh và học viên

#### Giáo viên

- Danh sách giáo viên
- Chi tiết giáo viên
- Tạo giáo viên
- Chỉnh sửa giáo viên
- Kích hoạt hoặc vô hiệu hóa giáo viên
- Quản lý quan hệ giáo viên và lớp

## 3. Quản lý học vụ (Academic Management)

### 3.1. Môn học (Subject)

- Danh sách môn học
- Tạo môn học
- Chỉnh sửa môn học
- Kích hoạt hoặc vô hiệu hóa môn học

Ví dụ: Toán, Tiếng Anh, Vật lý, Hóa học.

### 3.2. Lớp học (Class)

- Danh sách lớp
- Tạo lớp
- Chỉnh sửa lớp
- Lưu trữ lớp
- Xem chi tiết lớp
- Phân công giáo viên
- Thêm hoặc xóa học viên
- Xem thành viên lớp

Ví dụ: lớp Toán 9A có một giáo viên phụ trách và 15 học viên.

### 3.3. Lịch học (Schedule)

- Xem lịch theo tuần
- Xem lịch dạng lịch tháng hoặc lịch tổng quan
- Tạo lịch học
- Chỉnh sửa lịch học
- Hủy lịch học
- Đổi lịch học
- Xem lịch theo lớp, giáo viên hoặc học viên

### 3.4. Buổi học (Lesson Session)

Một lớp có nhiều buổi học. Mỗi buổi có thể gắn với ngày, giờ và nội dung học cụ thể.

- Tạo buổi học
- Chỉnh sửa buổi học
- Hủy buổi học
- Ghi nội dung buổi học
- Ghi chú cho buổi học
- Liên kết bài tập với buổi học
- Liên kết điểm danh với buổi học
- Xem lịch sử buổi học

## 4. Điểm danh (Attendance)

### 4.1. Quản lý điểm danh

- Điểm danh theo buổi học
- Ghi nhận trạng thái: có mặt, vắng, đi muộn hoặc nghỉ có phép
- Thêm ghi chú
- Chỉnh sửa điểm danh
- Xem lịch sử điểm danh

### 4.2. Báo cáo điểm danh

- Xem theo học viên
- Xem theo lớp
- Xem theo khoảng thời gian
- Xem tổng số buổi có mặt, vắng, đi muộn và nghỉ có phép

## 5. Nội dung học tập và ngân hàng câu hỏi (Learning Content / Question Bank)

Question Bank quản lý câu hỏi độc lập với Assignment, Quiz và Exam để câu hỏi có thể được xem trước và tái sử dụng.

### 5.1. Quản lý câu hỏi

- Tạo câu hỏi
- Chỉnh sửa câu hỏi
- Xóa hoặc lưu trữ câu hỏi
- Xem trước câu hỏi
- Sao chép câu hỏi

### 5.2. Thông tin phân loại câu hỏi

- Môn học
- Chủ đề
- Độ khó
- Nhãn (tag)
- Điểm số

### 5.3. Dạng câu hỏi

- Single Choice: chọn một đáp án
- Multiple Choice: chọn nhiều đáp án
- True/False: đúng hoặc sai
- Fill in the Blank: điền vào chỗ trống
- Short Answer: trả lời ngắn
- Essay: bài tự luận
- Matching: nối cặp
- Ordering: sắp xếp theo thứ tự
- File Upload: tải tệp lên

**Định hướng hiện tại:** Question Bank là chức năng cốt lõi của V1.

## 6. Bài tập (Assignment)

Assignment là bài tập được giao cho học viên.

- Tạo bài tập
- Chỉnh sửa bài tập
- Sao chép bài tập
- Công bố bài tập
- Gỡ công bố bài tập
- Lưu trữ bài tập
- Đặt hạn nộp
- Giao cho cả lớp
- Giao cho các học viên được chọn
- Đưa câu hỏi vào bài tập
- Có thể hỗ trợ tài liệu đính kèm

## 7. Bài kiểm tra ngắn (Quiz)

Quiz thiên về kiểm tra nhanh.

- Tạo Quiz
- Chọn câu hỏi
- Đặt giới hạn thời gian
- Đặt số lần làm bài
- Đặt điểm đạt
- Công bố Quiz
- Đóng Quiz
- Xem bài nộp
- Xem kết quả
- Có thể hỗ trợ xáo trộn câu hỏi và phương án trả lời

## 8. Bài kiểm tra chính thức (Exam)

Exam tương tự Quiz nhưng hướng tới nghiệp vụ kiểm tra chính thức hơn.

- Tạo Exam
- Chọn câu hỏi
- Đặt giới hạn thời gian
- Đặt thời gian bắt đầu và kết thúc
- Đặt số lần làm bài
- Xáo trộn câu hỏi
- Xáo trộn phương án trả lời
- Nộp bài
- Chấm điểm tự động
- Chấm điểm thủ công
- Xem kết quả Exam

**Điểm cần review:** Chưa chốt Quiz và Exam cần là hai chức năng riêng hay chỉ là các cấu hình khác nhau của cùng một loại bài kiểm tra. Sẽ làm rõ ở các bước User Flow và Domain Model.

## 9. Bài nộp (Submission)

Submission là phần nối giữa học viên và bài tập hoặc bài kiểm tra.

```text
Bài tập hoặc bài kiểm tra
          ↓
Học viên bắt đầu làm
          ↓
       Bài nộp
          ↓
    Câu trả lời
          ↓
       Nộp bài
          ↓
       Chấm điểm
```

- Bắt đầu lượt làm bài
- Lưu câu trả lời
- Tự động lưu câu trả lời
- Nộp bài
- Xem trạng thái bài nộp
- Xem lịch sử bài nộp

Các trạng thái dự kiến: chưa bắt đầu, đang làm, đã nộp, đang chấm, đã chấm, đã trả lại.

## 10. Chấm điểm (Grading)

### 10.1. Chấm điểm tự động

Dự kiến áp dụng cho:

- Single Choice
- Multiple Choice
- True/False
- Fill in the Blank
- Matching
- Ordering

### 10.2. Chấm điểm thủ công

Dự kiến áp dụng cho:

- Short Answer
- Essay
- File Upload

Giáo viên xem câu trả lời, chấm điểm, viết nhận xét và gửi kết quả chấm.

## 11. Kết quả và tiến độ học tập (Results & Learning Progress)

### 11.1. Kết quả

- Điểm số
- Tỷ lệ phần trăm
- Số câu đúng và sai
- Điểm hoặc xếp loại
- Nhận xét
- Chi tiết bài nộp

### 11.2. Tiến độ học tập

Theo dõi tiến độ theo môn học, lớp, chủ đề, Assignment, Quiz và Exam.

## 12. Thông báo (Notification)

Thông báo trong hệ thống có thể gửi đến Teacher, Student và Parent.

Các sự kiện có thể tạo thông báo:

- Bài tập được công bố
- Bài tập sắp đến hạn
- Bài tập đã được chấm
- Exam được công bố
- Có kết quả Exam
- Lịch học thay đổi
- Buổi học bị hủy
- Điểm danh được ghi nhận
- Thông báo chung

V1 dự kiến chỉ có thông báo trong ứng dụng. Email, SMS và LINE để xem xét ở giai đoạn sau.

## 13. Cổng học viên (Student Site)

Học viên chỉ xem dữ liệu liên quan đến chính mình.

### Bảng điều khiển

- Lịch học hôm nay
- Bài tập sắp đến hạn
- Exam sắp diễn ra
- Kết quả gần đây
- Thông báo
- Tiến độ học tập

### Lớp học của tôi

- Danh sách lớp
- Chi tiết lớp
- Giáo viên
- Lịch học
- Lịch sử buổi học

### Lịch học

- Xem lịch dạng lịch tổng quan
- Xem các buổi học sắp tới
- Xem các buổi học đã qua

### Bài tập

- Danh sách và chi tiết bài tập
- Bắt đầu làm bài
- Trả lời câu hỏi
- Lưu câu trả lời
- Nộp bài
- Xem lịch sử bài nộp

### Quiz và Exam

- Xem bài có thể làm
- Xem bài sắp diễn ra
- Xem bài đang làm
- Xem bài đã hoàn thành
- Xem kết quả

### Kết quả và thông báo

- Xem điểm, nhận xét, lịch sử và tiến độ
- Xem danh sách thông báo và trạng thái đã đọc hoặc chưa đọc

## 14. Cổng phụ huynh (Parent Site)

Parent có thể theo dõi một hoặc nhiều học viên là con của mình. Phụ huynh chỉ theo dõi, không làm bài thay học viên.

### Bảng điều khiển

- Tổng quan các con
- Lịch học hôm nay
- Tình hình điểm danh gần đây
- Kết quả gần đây
- Bài tập đang chờ
- Thông báo

### Thông tin và lịch học của con

- Hồ sơ học viên
- Lớp học
- Giáo viên
- Lịch học sắp tới và lịch dạng lịch tổng quan

### Điểm danh

- Lịch sử điểm danh
- Tổng hợp điểm danh

### Bài tập

- Trạng thái bài tập
- Trạng thái nộp bài
- Điểm số
- Nhận xét

### Kết quả và thông báo

- Xem điểm, môn học, chủ đề, lịch sử và tiến độ
- Xem thông báo của lớp hoặc trường, kết quả bài tập, điểm danh và thay đổi lịch

## 15. Chức năng dùng chung (Cross-cutting)

### Tìm kiếm (Search)

- Học viên
- Lớp
- Bài tập
- Câu hỏi

### Bộ lọc (Filter)

- Môn học
- Lớp
- Trạng thái
- Ngày
- Giáo viên

### Phân trang (Pagination)

- Phân trang cho các danh sách có nhiều dữ liệu

### Quản lý tệp (File Management)

Phục vụ tài liệu bài tập, bài nộp của học viên, câu hỏi dạng tải tệp lên và tệp đính kèm trong nhận xét của giáo viên.

### Nhật ký hoạt động (Audit / Activity Log)

Ghi lại các thao tác quan trọng, ví dụ tạo bài tập, cập nhật điểm hoặc vô hiệu hóa học viên.

**Định hướng hiện tại:** Audit Log nằm trong nền tảng V1; giao diện có thể đơn giản.

## 16. Tổng thể Feature Map

```text
LMS
│
├── Tài khoản và xác thực
│
├── Quản lý người dùng
│   ├── Admin
│   ├── Teacher
│   ├── Student
│   └── Parent
│
├── Quản lý học vụ
│   ├── Subject
│   ├── Class
│   ├── Schedule
│   └── Lesson Session
│
├── Attendance
│
├── Học tập
│   ├── Question Bank
│   ├── Assignment
│   ├── Quiz
│   ├── Exam
│   ├── Submission
│   └── Grading
│
├── Theo dõi học tập
│   ├── Results
│   └── Learning Progress
│
├── Notification
├── Student Site
├── Parent Site
│
└── Chức năng dùng chung
    ├── Search
    ├── Filter
    ├── Pagination
    ├── File Management
    └── Audit / Activity Log
```

## Các điểm cần review

1. **Quiz và Exam:** xác định hai loại bài kiểm tra khác nhau về nghiệp vụ đến mức nào.
2. **Lesson Session:** xác nhận có giữ khái niệm buổi học riêng gắn với lớp, điểm danh, nội dung buổi học và bài tập hay không.
3. **Question Bank:** đang được xem là chức năng cốt lõi của V1.
4. **Audit Log:** đang được đưa vào nền tảng V1 để ghi nhận các thao tác quan trọng; giao diện có thể đơn giản.
