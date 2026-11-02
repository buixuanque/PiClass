# Tổng hợp triển khai cổng Học sinh

> Ghi tiếp theo từng cụm FE. Nguồn quyết định: `DEC-LRN-037` (BE + nguyên tắc UX), `DEC-LRN-038` (cụm A). Đặc tả gốc: `student-parent.md`. Mockup: `piclass-mockup/ds-v2-source/ui_kits/student/`.

## BE (đã merge)
- `B-063` chấm câu Nối không phụ thuộc thứ tự khóa; `B1` ẩn điểm chưa công bố khỏi học sinh.
- N-A: `GET /me/assessments` (và `/{id}`) thêm hướng dẫn, thời lượng, số câu, số lượt tối đa/đã dùng, hạn riêng, lớp/môn/khóa; riêng học sinh có `in_progress_attempt_id`.
- N-E: `POST /me/assessments/{id}/attempts` trả lại lượt đang dở (200), không tốn lượt.
- N-B: `GET /me/assessments/{id}/result` thêm `questions` (bài làm của em; đáp án đúng khi `show_answers`; điểm từng câu khi `show_question_scores`), chỉ cho Student.

## Cụm A (ST-01..05)
| Màn | Route | Ghi chú |
|---|---|---|
| ST-01 Trang chủ | `/student` | lời chào theo tên, bài sắp đến hạn (tối đa 3), buổi học hôm nay |
| ST-02 Lịch học | `/student/schedule` | danh sách theo tuần nhóm theo ngày; hộp chi tiết buổi + điểm danh của em |
| ST-03 + ST-04 Bài của em | `/student/assessments` | ba tab + lọc Bài tập / Kiểm tra |
| ST-05 Chi tiết bài | `/student/assessments/[id]` | Làm bài / Làm tiếp / khóa kèm lý do; chuyển sang `/student/attempts/[id]` (cụm B) |

### Đã bỏ / gộp (lý do)
- ST-03 và ST-04 gộp một màn: học sinh tìm việc cần làm, không tìm theo loại; hai màn giống hệt cấu trúc.
- Chip "Chờ chấm / Đã có điểm" ở danh sách bỏ: phải gọi `…/result` từng bài (N+1). Điểm xem ở chi tiết.
- Trang chủ bỏ: mục tiêu hôm nay, chuỗi ngày, điểm thưởng, ô Môn học (trùng danh sách bài), thanh tìm kiếm.
- Lịch học bỏ lưới Ngày/Tuần/Tháng: một danh sách theo tuần là đủ và hợp điện thoại.
- Trang chủ hiện "Làm tiếp bài đang dở" dẫn vào chi tiết bài (nút Làm tiếp ở đó) cho tới khi cụm B có màn làm bài; cụm B đổi thành vào thẳng lượt dở.

### Lệch mockup
- ST-02 và ST-05 mockup không có ("Not built: Lịch học"; không có chi tiết bài): dựng bằng primitive design system theo bố cục các màn Học sinh khác.
- Sidebar mockup có khối "Chuỗi 6 ngày", footer hồ sơ và thanh tìm kiếm: không port (ngoài V1). Menu dùng app shell chung của dự án.
- "Hạn" ở danh sách hiện giờ tuyệt đối kèm tương đối ("còn 2 ngày") theo quy ước chung ST, mockup chỉ ghi thứ/giờ.

### Chưa làm / ghi nhận
- Chưa chạy giao diện trên trình duyệt thật với dữ liệu mẫu; kiểm bằng test (MSW) và gọi thử API thật bằng `student1@piclass.test`.
- Chuông thông báo (CM-06) và trang tìm kiếm chưa có ở cổng này.
