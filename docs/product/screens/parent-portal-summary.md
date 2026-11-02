# Tổng hợp triển khai cổng Phụ huynh

> Ghi tiếp theo từng cụm. Nguồn quyết định: `DEC-LRN-042` (nguyên tắc chung + cụm P1). Đặc tả gốc: `student-parent.md` (PR-01..08). Mockup: `piclass-mockup/ds-v2-source/ui_kits/parent/` (chỉ có Tổng quan, Điểm danh, Kết quả; chưa dựng Lịch học, Tin nhắn).

## Nguyên tắc
- Phụ huynh chỉ xem tình hình học của con và gửi đơn xin phép; không làm bài, không thấy câu hỏi, đáp án hay bài làm (`DEC-FILE-003`).
- Nhận xét giáo viên một chiều (`DEC-LRN-031`): bỏ "Trả lời giáo viên" và "Tin nhắn".
- Menu: Tổng quan, Lịch học, Điểm danh, Kết quả. Mỗi màn một việc chính, không nhồi chức năng.

## BE (đã merge)
- `GET /me/children` trả thêm `classes: [{id, name}]` (G9).
- `GET /me/lesson-sessions?student_id=` cho Parent: chỉ lớp của con đó; con không thuộc mình trả 404 (G2).
- `GET /me/children/{studentId}/attendance` (G3): tỷ lệ chuyên cần, đếm theo trạng thái, 10 buổi gần nhất; dùng chung `StudentAttendanceQuery`.

## Cụm P1 (PR-01, PR-02)
| Màn | Route | Ghi chú |
|---|---|---|
| PR-01 Chọn con | (thẻ ở đầu mọi trang `/parent/*`) | một con thì không phải chọn; từ hai con bấm thẻ để đổi, nhớ cục bộ |
| PR-02 Tổng quan của con | `/parent` | chuyên cần, điểm trung bình, buổi hôm nay của con kèm điểm danh, tối đa 3 bài sắp đến hạn, nhận xét mới nhất |

### Đã bỏ / gộp (lý do)
- PR-01 không có màn riêng: phụ huynh phần lớn có một đến hai con, một thẻ đổi nhanh đủ và hợp điện thoại.
- Ô chuyên cần theo tháng của mockup: API trả 10 buổi gần nhất, không theo tháng.
- Điểm từng bài ở thẻ Bài tập: phải gọi kết quả từng bài (N+1); thẻ chỉ hiện trạng thái, điểm xem ở màn Kết quả (P2).
- Nút "Trả lời giáo viên" và mục "Tin nhắn": nhận xét một chiều.

### Lệch mockup
- Mockup là khung điện thoại 390px với thanh dưới; cổng dùng app shell chung của dự án (sidebar/menu), nội dung tối đa 640px.
- Lời chào dùng tên riêng lấy từ hồ sơ, không có xưng hô (chưa có dữ liệu).
- Điểm trung bình hiện theo % (như cổng Học sinh), không phải thang 10 như mockup.

### Chưa làm / ghi nhận
- Chưa xem giao diện trên trình duyệt thật: kiểm bằng test (MSW) và gọi thử API thật bằng `parent4@piclass.test` (hai con, con của người khác trả 404). Hai con mẫu chưa xếp lớp nên mọi khối rỗng.
- Chuông thông báo (CM-06) chưa có ở cổng này.

## Cụm P2 (dự kiến)
- PR-03 Lịch học, PR-04 Điểm danh, PR-05..07 Kết quả, PR-08 Đơn xin phép; hướng chốt ở `DEC-LRN-042` mục 7.
