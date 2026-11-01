# Dữ liệu mẫu để xem màn hình trên máy dev

Dữ liệu mẫu chỉ dùng ở local, không nằm trong `DatabaseSeeder`. Chạy trong `piclass-infra/local`.

## Cổng Giáo viên, lát 4c (chi tiết bài, bài nộp, chấm bài)

```
docker compose exec api php artisan migrate --force      # nếu chưa chạy migration mới (cột công bố của results)
docker compose exec api php artisan db:seed --class=TeacherGradingDemoSeeder
```

- Đăng nhập `teacher1@piclass.test`, mật khẩu `Piclass@123`. Vào "Bài đánh giá", mở bài `[Demo 4c] Kiểm tra 15 phút – Đại số chương 2`.
- Bảy học sinh ở đủ trạng thái: chưa làm, đang làm, đã nộp chưa chấm (có file bài làm), chấm dở (có nhận xét nháp), chấm xong chưa công bố (`student2@piclass.test`), đã công bố (`student1@piclass.test`, có nhận xét đã công bố và đã rút), và một lượt bị vô hiệu đã cấp lượt thay thế. Bài có thêm phiên bản 2 đang soạn.
- Chạy lại khi đã có dữ liệu mẫu thì bỏ qua. Muốn tạo lại từ đầu: `make fresh` (xóa sạch DB dev) rồi chạy lại lệnh trên.
- File bài làm lưu theo `ATTACHMENT_DISK` (mặc định `local`, thư mục `storage/app/private`).

## Cổng Giáo viên, màn Khóa học (khóa có bài cần chấm)

```
docker compose exec api php artisan db:seed --class=TeacherCourseDemoSeeder
```

- Tự chạy `TeacherGradingDemoSeeder` trước (lớp `[Demo 4c] Toán 9A` và bảy học sinh), nên không cần chạy riêng lệnh ở mục trên.
- Đăng nhập `teacher1@piclass.test`, vào "Khóa học": khóa `[Demo khóa] Ôn thi vào lớp 10` có lớp 9A trong đối tượng và ba bài của khóa. Bài 1 (ba câu) có bài nộp ở đủ trạng thái (đã công bố, chấm xong chưa công bố, hai bài chờ chấm, một bài chấm dở); bài 2 (hai câu) có hai bài chờ chấm và một bài chấm xong; bài 3 còn là bản nháp.
- Bấm "Bài nộp và chấm" ở bài đã phát hành trong khóa để vào màn bài nộp và chấm. Khóa `[Demo khóa] Luyện đề nâng cao` chưa có đối tượng và bài, để xem trạng thái trống.
- Màn "Lớp của tôi", lớp 9A, tab Học tập hiện khóa và bài kiểm tra của lớp trong một danh sách có bộ lọc.
- Chạy lại khi đã có dữ liệu mẫu thì bỏ qua. Muốn tạo lại: `make fresh` rồi chạy lại lệnh trên.
