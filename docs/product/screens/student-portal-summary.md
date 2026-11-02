# Tổng hợp triển khai cổng Học sinh

> Ghi tiếp theo từng cụm FE. Nguồn quyết định: `DEC-LRN-037` (BE + nguyên tắc UX), `DEC-LRN-038` (cụm A), `DEC-LRN-039` (cụm B). Đặc tả gốc: `student-parent.md`. Mockup: `piclass-mockup/ds-v2-source/ui_kits/student/`.

## BE (đã merge)
- `B-063` chấm câu Nối không phụ thuộc thứ tự khóa; `B1` ẩn điểm chưa công bố khỏi học sinh.
- N-A: `GET /me/assessments` (và `/{id}`) thêm hướng dẫn, thời lượng, số câu, số lượt tối đa/đã dùng, hạn riêng, lớp/môn/khóa; riêng học sinh có `in_progress_attempt_id`.
- N-E: `POST /me/assessments/{id}/attempts` trả lại lượt đang dở (200), không tốn lượt.
- N-B: `GET /me/assessments/{id}/result` thêm `questions` (bài làm của em; đáp án đúng khi `show_answers`; điểm từng câu khi `show_question_scores`), chỉ cho Student. Cụm B bổ sung `type_payload` (không kèm `correct`) để hiện bài làm câu Nối/Sắp xếp.

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

## Cụm B (ST-06, ST-06b, ST-07, ST-07b)
| Màn | Route | Ghi chú |
|---|---|---|
| ST-06 Làm bài | `/student/attempts/[id]?assessment=<id>` | mỗi lần một câu, tiến độ, đồng hồ, tự lưu, hộp xác nhận nộp |
| ST-06b Đã nộp | (cùng route ST-06) | hiện khi lượt không còn đang làm |
| ST-07 + ST-07b Kết quả | `/student/assessments/[id]/result` | điểm, nhận xét chung, xem lại từng câu |

### Đã bỏ / gộp (lý do)
- ST-06b không có route riêng: chỉ là thẻ thông báo, cùng URL với lượt làm bài nên vào lại lượt đã nộp cũng ra đúng màn.
- ST-07b gộp vào ST-07: xem lại bài là phần dưới của màn kết quả, không cần màn thứ hai.
- Bỏ chuyển giữa các lượt ở màn kết quả (chỉ lượt đại diện + một dòng "Các lần làm"): học sinh quan tâm điểm cuối.
- Mockup quiz hiện điểm ngay khi nộp: bỏ, vì điểm chỉ hiện sau khi giáo viên công bố (`BR-SYS-007`).
- Câu nộp tệp (`file_upload`) không hỗ trợ ở V1 (`DEC-LRN-037`); nếu vẫn lọt vào bài thì hiện dòng "bỏ qua câu này".

### Lệch mockup
- Mockup quiz chỉ có câu trắc nghiệm một đáp án (lưới 2 cột, Radio trong ô viền 1.5px, nền blue-50 khi chọn): giữ đúng cho câu chọn; các loại câu khác (nhiều đáp án, điền khuyết, ngắn, tự luận, nối, sắp xếp) bám diện mạo `QuestionStudentView` của giáo viên.
- Màn Kết quả một bài mockup không có (`student-results.js` là bảng điểm cả kỳ, thuộc ST-08): dựng bằng Card/Badge/Avatar, thẻ nhận xét theo mẫu "Nhận xét của giáo viên" của mockup. Tên giáo viên hiện "Giáo viên" vì API nhận xét chỉ trả `author_user_id`.
- Có thêm dòng trạng thái lưu ("Đang lưu… / Đã lưu / Chưa lưu được") dưới thanh tiến độ: mockup không có tự lưu.

### Chưa làm / ghi nhận
- Chưa xem giao diện bằng trình duyệt; đã chạy thật luồng bắt đầu, lưu, nộp, giáo viên chấm, công bố, học sinh xem qua API với `student2@piclass.test` (dữ liệu demo bài "Bài 2: Hệ phương trình" của student2 nay đã nộp và công bố 5,5/10).
- Dữ liệu demo "[Demo 4c]" của `student1` không có dòng câu hỏi của lượt (`attempt_questions`) nên phần "Xem lại bài" trống ở bài đó; lượt làm thật thì có.
- Điểm từng câu từ BE là chuỗi thập phân ("1.50"): FE đọc bằng `Number()`; có thể đổi BE trả số.
- Câu hỏi chưa có lời giải: `show_explanations` chưa có tác dụng (`B-087`).
- Thoát giữa chừng không có hộp xác nhận (đồng hồ vẫn chạy, bài đã tự lưu).

