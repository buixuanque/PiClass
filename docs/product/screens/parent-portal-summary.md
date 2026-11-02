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

## Cụm P2 (PR-03..08) — `DEC-LRN-043`
| Màn | Route | Ghi chú |
|---|---|---|
| PR-03 Lịch học của con | `/parent/schedule` | danh sách theo tuần của con đang chọn (dùng chung `WeekScheduleList` với cổng Học sinh); bấm buổi xem nội dung; buổi sắp tới có "Xin nghỉ buổi này" |
| PR-04 Điểm danh + PR-08 Đơn xin phép | `/parent/attendance` | vòng tỷ lệ chuyên cần + số buổi theo trạng thái; 10 buổi gần nhất (buổi vắng: nút "Gửi đơn xin phép" / trạng thái đơn / "Gửi lại đơn"); thẻ "Đơn xin phép đã gửi" kèm lý do duyệt và ngày gửi |
| PR-05/06/07 Kết quả | `/parent/results` | hai tab: Điểm (số liệu, theo môn, bài của con, mở kết quả từng bài) và Nhận xét (một chiều) |

Menu cổng chốt 4 mục: Tổng quan, Lịch học, Điểm danh, Kết quả.

### BE (đã merge)
- `POST /absence-explanations`: buổi đã hủy trả 409 `SESSION_CANCELLED`; buổi đã có đơn đang chờ hoặc đã chấp nhận trả 409 `EXPLANATION_ALREADY_EXISTS` (mã mới, ghi ở `error-codes.md`); đơn bị từ chối thì được gửi lại.
- Danh sách đơn đã đủ lớp, giờ buổi, ngày gửi, `review_note` từ `DEC-LRN-018` nên không đổi.

### Đã bỏ / gộp (lý do)
- Menu "Đơn xin phép" riêng: đơn đi từ buổi vắng hoặc buổi sắp tới nên gộp vào Lịch học và Điểm danh; danh sách đơn đã gửi nằm cuối màn Điểm danh.
- PR-05, PR-06, PR-07 gộp một màn "Kết quả": phụ huynh chỉ cần điểm, môn nào mạnh yếu và nhận xét; ba màn riêng là trùng nhau.
- Lịch tháng của mockup Điểm danh: API trả 10 buổi gần nhất, không theo tháng; danh sách 10 buổi thay chỗ, và có sẵn chỗ gửi đơn.
- Mũi tên tăng giảm điểm theo môn: chưa có dữ liệu theo thời gian (G8).
- Bộ lọc thời gian/môn của tiến độ; xem câu hỏi, đáp án, bài làm của con (`DEC-FILE-003`); sửa hoặc thu hồi đơn (không có endpoint); "Trả lời giáo viên".

### Lệch mockup
- Mockup chưa dựng Lịch học và danh sách đơn xin phép: dựng bằng thành phần design system có sẵn, cùng kiểu dòng với cổng Học sinh.
- Điểm danh: danh sách 10 buổi thay lịch tháng; vòng tỷ lệ và chấm màu theo đúng mockup (`--att-*`).
- Kết quả: thêm danh sách "Bài của con" để mở kết quả từng bài (mockup chỉ có điểm theo môn).

### Luật gửi đơn (người dùng thấy)
- Buổi đã hủy: không có nút xin nghỉ.
- Đã có đơn đang chờ hoặc đã chấp nhận: hiện trạng thái đơn thay cho nút.
- Bị từ chối: hiện lý do và nút "Gửi lại đơn".
- Lỗi của BE hiện nguyên văn trong hộp thoại, giữ nội dung đã gõ.

### Kiểm thử
- BE: 2520 test pass (chỉ `TeacherGradingDemoSeederTest` chập chờn, `B-086`), PHPStan 33 lỗi bằng `main`.
- FE: 1014 test pass, lint và typecheck sạch; test mới ở `parent-portal-p2.test.tsx` và `parent-results-screen.test.tsx`.

## Tồn đọng và việc cần user xem
- Chưa xem giao diện trên trình duyệt thật: mới kiểm bằng test (MSW) và test BE. Hai con mẫu của `parent4@piclass.test` chưa xếp lớp nên mọi khối rỗng; cần xếp lớp và tạo vài buổi vắng để thử gửi đơn thật.
- Chưa có chuông thông báo (CM-06) ở cổng này; tên giáo viên trong nhận xét lấy từ API khi có (`B-090`).
- Điểm danh chỉ 10 buổi gần nhất; muốn xem theo tháng cần API mới (tuỳ nhu cầu thực tế).
- Chưa chạy review độc lập (opus) cho cổng Phụ huynh.
