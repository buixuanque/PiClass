# API hiện có so với chức năng cơ bản của một trung tâm (V1 tối thiểu)

**Ngày:** 2026-10-24. **Nguồn:** `piclass-api/routes/api.php` (khoảng 130 endpoint), `product/screens/api-gaps.md`, `2026-10-24-review-pham-vi-v1.md`.
**Nguyên tắc user chốt:** chỉ làm chức năng cơ bản nhất, chạy thật rồi hỏi giáo viên để mở rộng. Giữ Course, giữ 9 loại câu hỏi, thông báo chỉ trong ứng dụng, chưa nhập hàng loạt. "Tạm bỏ" nghĩa là chưa làm giao diện / chưa sửa API; backend đã có thì giữ, không xóa.

## 1. API hiện chạy được

| Việc của trung tâm | Trạng thái |
|---|---|
| Đăng nhập, quên và đổi mật khẩu, hồ sơ cá nhân, chọn vai trò | Đủ |
| Admin tạo hồ sơ học sinh / phụ huynh / giáo viên, mời tài khoản, gán vai trò, liên kết phụ huynh–học sinh | Có, thiếu danh sách hồ sơ (A7) và gửi lại lời mời (A10) |
| Môn học, lớp, phân công giáo viên, thêm / rút học viên | Đủ |
| Lịch lặp, sinh buổi học, hủy / hoàn tất buổi, điểm danh cả lớp, giải trình vắng | Có, thiếu id bản ghi điểm danh (A1) và lịch theo người (A6) |
| Khóa học và ghi danh | Đủ |
| Ngân hàng câu hỏi (9 loại, chủ đề, thẻ), tạo / sửa / lưu trữ | Đủ, riêng `file_upload` chưa dùng được (A11) |
| Tạo bài (Assignment, Quiz, Exam), thêm câu, phát hành, giao cho lớp / học sinh / khóa, đóng / mở lại / hủy | Đủ |
| Học sinh làm bài: bắt đầu, tự lưu, nộp, tự chấm | Có, thiếu chi tiết bài (A3) và tìm lại lượt đang làm (A4) |
| Giáo viên chấm từng câu / cả bài, nhận xét, công bố điểm | Có, danh sách chỉ có mã học sinh (A12) |
| Học sinh và phụ huynh xem điểm, tiến độ; phụ huynh xem nhiều con | Có |
| Thông báo trong ứng dụng, tệp đính kèm, audit | Có |

## 2. Đề xuất thêm hoặc sửa (nhỏ gọn, chặn vòng chính)

Mã A/B/C theo `api-gaps.md`.

**Phải có trước khi có người dùng thật**
- **B1** Sửa lộ điểm chưa công bố cho học sinh khi nộp bài (đang trái `BR-ASSESS-028`; cần viết test xác nhận trước).
- **B4** Kiểm tra phía BE: thứ tự mốc giờ, điểm đạt ≤ điểm tối đa, tổng điểm câu, bài phải có đối tượng trước khi phát hành.
- **A7** Danh sách hồ sơ học sinh / phụ huynh / giáo viên có tìm kiếm và phân trang, liên kết hai chiều với tài khoản.
- **A8** Danh sách lớp theo từng người (lớp của con, lớp của học sinh / giáo viên).
- **A6** Lịch theo người (`/me/lesson-sessions`), buổi học kèm tên lớp và môn.
- **A9** Điểm danh theo học sinh (`/students/{id}/attendance`, `/me/attendance`).
- **A1** Trả id bản ghi điểm danh để sửa từng em.
- **A3** Chi tiết bài cho học sinh / phụ huynh (hướng dẫn, thời lượng, số lượt, hạn).
- **A4** Tìm lại lượt đang làm dở, trả `attempt.id` trong lịch sử (mất là mất bài).
- **A12** Danh sách bài nộp và danh sách học sinh theo bài có tên, số lượt, trạng thái.
- **A10** Lời mời sống lâu hơn 60 phút và gửi lại lời mời (phụ huynh mở thư hôm sau gặp link hết hạn).
- **C2 (phần phân trang)** và **C7 (tìm tên không dấu)**: danh sách lớn và tìm tên tiếng Việt là việc hằng ngày.
- **A2** Trả `role_id` hoặc kết thúc vai trò theo mã (nhỏ, nhưng chặn thao tác Admin).

**Nên có, làm ngay sau vòng chính**
- **A5** Kết quả chi tiết theo câu cho học sinh / phụ huynh (xem lại bài, đáp án đúng, theo cờ `show_*`).
- **A11** File bài làm (ảnh chụp bài viết tay là cách làm bài rất thường gặp). Vì giữ 9 loại câu hỏi, cần làm; trong lúc chờ thì ẩn loại `file_upload` khỏi bài giao.
- **B6** Chốt schema cho câu nối / sắp xếp / điền nhiều chỗ (form soạn câu phụ thuộc).
- **B7** Chốt lớp `archived` (có chặn thêm học viên không).
- **C6 (đếm chưa đọc)** và id đích trong thông báo (bấm thông báo mở đúng trang).
- **C12** Địa điểm / phòng học của lớp và buổi (rẻ, trung tâm nào cũng hỏi).

**Quyết định còn treo, cần chốt trước khi làm cổng học sinh**
- **Q4** Học sinh chưa có tài khoản thì làm bài thế nào (ảnh hưởng toàn bộ cổng học sinh; học sinh nhỏ tuổi thường không có email).
- `login_identifier` có luôn là email không.

## 3. Tạm bỏ (chưa làm giao diện, chưa sửa API; backend giữ nguyên)

| Chức năng | Lý do |
|---|---|
| Giao diện nhật ký audit đầy đủ, tab "Lịch sử thay đổi" của giáo viên | BE vẫn ghi; chưa ai cần đọc ở tuần đầu |
| Cấp lại lượt làm Exam do sự cố (`reissue`), vào thi muộn, "yêu cầu nộp lại" (C15) | Hiếm gặp, Admin xử tay được |
| Hẹn giờ công bố điểm và cờ công bố riêng (B3) | Cơ bản: chấm xong công bố ngay hoặc công bố cả lớp một lần (`bulk publish` đã có) |
| Gia hạn từng học sinh, mở lại bài kéo dài giờ (B5) | Làm sau khi hỏi giáo viên có thật sự dùng |
| Giao diện phiên bản bài / câu hỏi (C9), nhân bản bài (C13) | Giữ cơ chế phiên bản ở BE, ẩn thuật ngữ ở UI |
| Thẻ (tag) của câu hỏi | Chủ đề đủ để phân loại |
| Nhận xét nhiều mức hiển thị (riêng tư / riêng cho phụ huynh) | Đợt đầu: nhận xét hiển thị cho học sinh và phụ huynh, hoặc không |
| "Đổi quyết định" đơn xin phép, gợi ý đổi điểm danh sau khi chấp nhận | Đơn giản: chấp nhận / từ chối kèm ghi chú |
| Hủy lịch lặp kèm hủy hàng loạt buổi (quyết định 9) | Hủy từng buổi đã đủ |
| Bảng điều khiển có hàng đợi "cần xử lý" (C1), lịch sử điểm cho biểu đồ (C4), danh sách nhận xét (C5), xem thành viên đã kết thúc (C10), bỏ cờ rà soát (B8), giờ máy chủ cho đồng hồ (C14) | Tiện dụng; làm khi có phản hồi. C14 nên làm nếu có Exam |
| Bộ lọc phụ ở Tiến độ (7 / 30 ngày, trước và sau) | Giữ bảng theo môn |
| Checklist thiết lập lần đầu (AD-15) | Admin đầu tiên tạo bằng seed / lệnh |
| Nhập hàng loạt, học phí, email / SMS / Zalo thông báo | Đã chốt không làm ở V1 |

**Lưu ý email:** thông báo chỉ trong ứng dụng, nhưng **email hệ thống vẫn cần cấu hình** cho lời mời tài khoản và đặt lại mật khẩu (đây là email giao dịch, khác email thông báo). Cần chọn dịch vụ gửi thư (SMTP) cho mỗi bản triển khai.
