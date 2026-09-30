# Cổng học viên

## 1. Mục đích
Cung cấp cho Student một điểm truy cập để xem lớp, lịch, bài tập/kiểm tra, nộp bài và theo dõi kết quả.

## 2. Actor
Student đã đăng nhập và có hồ sơ/liên kết lớp phù hợp.

## 3. Điều kiện trước
Student đã đăng nhập và có hồ sơ/liên kết lớp phù hợp.

## 4. Trigger
Student mở cổng học viên.

## 5. Luồng chính
1. Student xem tổng quan các lớp đang tham gia và hoạt động sắp tới.
2. Student xem lịch học, thông tin buổi học và nội dung đã công bố.
3. Student mở danh sách Assignment/Quiz/Exam được giao và trạng thái/hạn.
4. Student bắt đầu, lưu và nộp bài theo flow tương ứng.
5. Student xem trạng thái chấm, điểm và feedback sau khi được công bố.
6. Student xem lịch sử điểm danh và tiến độ cơ bản.
7. Student xem thông báo cá nhân.

## 6. Luồng thay thế
1. Student thuộc nhiều lớp có thể chuyển phạm vi lớp.
2. Không có hoạt động cần làm thì hiển thị trạng thái trống có hướng dẫn.
3. Student cập nhật thông tin cá nhân được phép. [CẦN QUYẾT ĐỊNH] Trường nào tự sửa được?

## 7. Trường hợp lỗi / ngoại lệ
1. Không truy cập dữ liệu lớp khác hoặc Submission của học viên khác.
2. Bài chưa mở/đã đóng/đã hết lượt được hiển thị với trạng thái và hành động phù hợp.
3. Điểm chưa công bố không hiển thị.

## 8. Kết quả cuối cùng
Student tự phục vụ được các tác vụ học tập và xem thông tin của mình trong phạm vi quyền.

## 9. Business Rules
1. Cổng Student tổng hợp flow nghiệp vụ; chi tiết tương tác giao diện không thuộc tài liệu này.
2. Student chỉ xem dữ liệu của bản thân và lớp đang/có liên quan theo chính sách.
3. Có cho xem lớp cũ sau khi rời lớp, sửa hồ sơ, xem đáp án sau khi nộp: [CẦN QUYẾT ĐỊNH].
