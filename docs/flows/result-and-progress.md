# Kết quả và tiến độ học tập

## 1. Mục đích
Tổng hợp lịch sử học tập, điểm, điểm danh và tiến độ cơ bản cho các vai trò có quyền.

## 2. Actor
Đã có dữ liệu lớp, điểm danh, bài làm hoặc kết quả.

## 3. Điều kiện trước
Đã có dữ liệu lớp, điểm danh, bài làm hoặc kết quả.

## 4. Trigger
Admin/Teacher/Student/Parent mở trang kết quả hoặc báo cáo.

## 5. Luồng chính
1. Người dùng mở tổng quan hoặc hồ sơ học viên.
2. Hệ thống hiển thị kết quả Assignment/Quiz/Exam đã được công bố, feedback và trạng thái.
3. Hiển thị lịch sử tham gia lớp, buổi học và điểm danh theo thời gian.
4. Admin/Teacher lọc theo lớp, học viên, môn hoặc khoảng thời gian trong phạm vi quyền.
5. Student xem kết quả của mình; Parent xem kết quả của học viên đã liên kết.
6. Người dùng mở chi tiết một hoạt động để xem thông tin được phép.

## 6. Luồng thay thế
1. Nếu chưa có kết quả, hiển thị trạng thái chưa có/chưa công bố thay vì điểm rỗng.
2. Teacher xem tiến độ lớp hoặc từng học viên.
3. Xuất báo cáo. [CẦN QUYẾT ĐỊNH] Có thuộc V1 không?

## 7. Trường hợp lỗi / ngoại lệ
1. Không hiển thị điểm chưa công bố cho Student/Parent.
2. Không tổng hợp dữ liệu lớp/học viên ngoài phạm vi quyền.
3. Dữ liệu thiếu hoặc assessment bị hủy phải thể hiện trạng thái rõ, không tính sai trung bình.

## 8. Kết quả cuối cùng
Người dùng thấy bức tranh tiến độ và kết quả phù hợp quyền, với trạng thái công bố rõ ràng.

## 9. Business Rules
1. V1 chỉ cần theo dõi tiến độ cơ bản; không bao gồm phân tích nâng cao.
2. Cách tính trung bình, tiến độ, xếp loại, điểm đạt và xử lý bài chưa chấm: [CẦN QUYẾT ĐỊNH].
3. Kết quả hiển thị theo trạng thái công bố của từng assessment.
