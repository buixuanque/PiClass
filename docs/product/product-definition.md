# Step 1 — Định nghĩa sản phẩm

**Trạng thái:** Tài liệu định nghĩa sản phẩm chính thức của Step 1.  
**Tên sản phẩm:** Tên tạm thời, sẽ quyết định sau.

## Tổng quan sản phẩm

Một hệ thống quản lý học tập dành cho các lớp học thêm. Hệ thống hỗ trợ giáo viên quản lý học viên, phụ huynh, lớp học, lịch học, buổi học, điểm danh, bài tập, bài kiểm tra, chấm điểm và tiến độ học tập. Học viên và phụ huynh có cổng riêng để theo dõi hoạt động và kết quả học tập.

V1 phục vụ các lớp học thêm hiện tại của giáo viên. Về sau, sản phẩm được định hướng mở rộng để hỗ trợ nhiều giáo viên, lớp học và nhóm học viên.

## Tầm nhìn sản phẩm

Hỗ trợ toàn bộ quá trình học tập, từ tổ chức lớp đến theo dõi tiến độ:

```text
Học viên → Lớp học → Lịch học và buổi học → Điểm danh
         → Bài tập và bài kiểm tra → Nộp bài
         → Chấm điểm và nhận xét → Kết quả và tiến độ học tập
```

Sản phẩm có bốn vai trò người dùng: Admin, Teacher, Student và Parent. Teacher là một vai trò riêng. Trong V1, một tài khoản có thể đồng thời mang vai trò Admin và Teacher.

## Người dùng mục tiêu

### Admin

Quản lý người dùng và hoạt động học tập của hệ thống, bao gồm học viên, phụ huynh, giáo viên, lớp học, môn học, lịch học, nội dung học tập, bài tập và bài kiểm tra, kết quả và thông báo.

### Teacher

Trực tiếp giảng dạy và quản lý các lớp được phân công. Công việc gồm quản lý học viên và buổi học, ghi nhận điểm danh, tạo bài tập và bài kiểm tra, quản lý câu hỏi, chấm bài, nhận xét và theo dõi tiến độ học viên.

Trong V1, một tài khoản có thể đồng thời mang vai trò Admin và Teacher.

### Student

Xem lớp học, lịch học, buổi học, bài tập và bài kiểm tra; làm và nộp bài; xem điểm, nhận xét và lịch sử học tập.

### Parent

Theo dõi các con được liên kết với tài khoản và xem lịch học, điểm danh, bài tập, kết quả, nhận xét và thông báo. Một phụ huynh có thể được liên kết với nhiều con.

## Mô hình học tập cốt lõi

Sản phẩm tổ chức hoạt động học tập theo trình tự sau:

1. Người dùng được gắn với lớp học và môn học.
2. Lớp học có lịch học và các buổi học cụ thể.
3. Điểm danh được ghi nhận theo từng buổi học.
4. Giáo viên giao hoạt động học tập và bài kiểm tra cho lớp hoặc học viên.
5. Học viên hoàn thành hoặc nộp bài.
6. Giáo viên chấm bài và đưa ra nhận xét.
7. Học viên và phụ huynh theo dõi kết quả và tiến độ học tập.

V1 hỗ trợ nhiều dạng câu hỏi và trả lời:

- Chọn một đáp án (Single Choice)
- Chọn nhiều đáp án (Multiple Choice)
- Đúng/Sai (True/False)
- Điền vào chỗ trống (Fill in the Blank)
- Trả lời ngắn (Short Answer)
- Tự luận (Essay)
- Nối cặp (Matching)
- Sắp xếp thứ tự (Ordering)
- Tải tệp lên (File Upload)

Danh sách dạng câu hỏi này vẫn mở để xem xét ở bước sau, chưa phải danh sách cuối cùng hoặc đầy đủ.

## Phạm vi V1

V1 tập trung vào các hoạt động cốt lõi của lớp học thêm:

- Quản lý người dùng và các vai trò Admin, Teacher, Student, Parent.
- Quản lý học viên, phụ huynh, giáo viên, môn học và lớp học.
- Liên kết phụ huynh với con và học viên với lớp học.
- Quản lý lịch học và buổi học.
- Ghi nhận và xem lại điểm danh.
- Tạo và quản lý bài tập, bài kiểm tra, kỳ thi và câu hỏi.
- Cho phép học viên làm hoặc nộp bài theo các dạng được hỗ trợ.
- Chấm bài và đưa ra nhận xét.
- Cho phép học viên và phụ huynh xem lịch học, điểm danh, bài tập, kết quả và tiến độ phù hợp.
- Gửi thông báo cơ bản liên quan đến hoạt động học tập.

## Ngoài phạm vi V1

Các nội dung sau không thuộc V1:

- Thanh toán và thanh toán trực tuyến.
- Quản lý học phí. Đây chỉ là nội dung có thể xem xét trong tương lai, không thuộc V1.
- Các tính năng AI.
- Bài giảng video, lớp học trực tuyến và trò chuyện.
- Ứng dụng di động.
- Trò chơi hóa.
- Chứng chỉ.
- Phân tích dữ liệu nâng cao.

## Định hướng tương lai

Sản phẩm được định hướng mở rộng từ giáo viên và các lớp hiện tại để hỗ trợ nhiều giáo viên, lớp học và nhóm học viên. Hỗ trợ nhiều giáo viên là định hướng tương lai; V1 có thể do một người vận hành với cả vai trò Admin và Teacher.

Quản lý học phí có thể được xem xét ở giai đoạn sau. Thanh toán và thanh toán trực tuyến không thuộc phạm vi sản phẩm hiện tại.

## Nguyên tắc sản phẩm

- Ưu tiên quy trình dạy và học thực tế của các lớp học thêm.
- Hoàn thiện các hoạt động cốt lõi về lớp học, điểm danh, bài tập, kiểm tra, chấm điểm và theo dõi tiến độ trước khi bổ sung tính năng mở rộng.
- Hỗ trợ nhiều dạng hoạt động học tập và câu hỏi; xem xét lại danh sách dạng câu hỏi khi yêu cầu rõ hơn.
- Giữ Admin và Teacher là hai vai trò riêng, đồng thời cho phép một người đảm nhiệm cả hai vai trò trong V1.
- Sẵn sàng mở rộng để hỗ trợ nhiều giáo viên, lớp học và nhóm học viên.
- Giữ V1 tập trung vào hoạt động học tập; thanh toán, quản lý học phí và các tính năng mở rộng đã liệt kê nằm ngoài phạm vi V1.
