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
