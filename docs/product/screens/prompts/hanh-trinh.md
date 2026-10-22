# Bộ prompt — Hành trình (nối các màn thành luồng)

Các prompt dưới đây nối những màn đã vẽ thành **luồng người dùng liền mạch**. Dán vào Claude Design **sau khi đã vẽ các màn trong luồng đó** (hoặc phần lớn). Mỗi prompt tự đứng được; nếu một màn chưa có thì Claude Design chỉ nối những màn đã có.

| Mã | Hành trình | Vai trò |
|---|---|---|
| HT-01 | Lần đầu vào hệ thống (mọi vai trò) | Học sinh, phụ huynh, giáo viên vừa được trung tâm mời; người dùng mới của hệ thống |
| HT-02 | Quên mật khẩu | Mọi vai trò, chưa đăng nhập |
| HT-03 | Thiết lập hệ thống lần đầu | Quản trị viên của một trung tâm mới, hệ thống còn trống |
| HT-04 | Tiếp nhận một học viên mới | Quản trị viên |
| HT-05 | Quản lý một lớp trong tuần | Quản trị viên |
| HT-06 | Một buổi dạy từ đầu đến cuối | Giáo viên |
| HT-07 | Giao bài, chấm bài, công bố kết quả | Giáo viên |
| HT-08 | Theo dõi một lớp và học sinh | Giáo viên |
| HT-09 | Từ thông báo đến kết quả bài làm | Học sinh |
| HT-10 | Xem lịch và chuẩn bị buổi học | Học sinh |
| HT-11 | Xem tình hình của con | Phụ huynh (có thể có nhiều con) |
| HT-12 | Báo con nghỉ học | Phụ huynh |

### HT-01 — Lần đầu vào hệ thống (mọi vai trò)

```text
Hành trình: Lần đầu vào hệ thống (mọi vai trò) (Học sinh, phụ huynh, giáo viên vừa được trung tâm mời; người dùng mới của hệ thống; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Kích hoạt tài khoản lần đầu
2. Đăng nhập
3. Chọn / đổi vai trò (chỉ khi tài khoản có từ 2 vai trò)
4. Khung điều hướng theo vai trò
5. Tổng quan của vai trò đó

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- thông tin đăng nhập vừa đặt
- vai trò đang dùng
- tên người dùng hiển thị ở khung điều hướng

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Link mời hết hạn: ở màn Kích hoạt tài khoản phải có đường xin gửi lại.
- Tài khoản chỉ có một vai trò: bỏ qua bước chọn vai trò.
- Đăng nhập sai nhiều lần bị tạm khóa: thông báo rõ thời gian chờ.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-02 — Quên mật khẩu

```text
Hành trình: Quên mật khẩu (Mọi vai trò, chưa đăng nhập; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Đăng nhập
2. Quên mật khẩu
3. (email nhận hướng dẫn)
4. Đặt lại mật khẩu
5. Đăng nhập

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- tên đăng nhập đã nhập ở màn Đăng nhập (điền sẵn ở Quên mật khẩu)

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Quên mật khẩu luôn báo như nhau dù tài khoản có tồn tại hay không.
- Link đặt lại hết hạn hoặc đã dùng: có đường quay về xin link mới.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-03 — Thiết lập hệ thống lần đầu

```text
Hành trình: Thiết lập hệ thống lần đầu (Quản trị viên của một trung tâm mới, hệ thống còn trống; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Tổng quan (Admin)
2. Thiết lập lần đầu
3. Môn học
4. Danh sách giáo viên → Tạo hồ sơ giáo viên
5. Danh sách học sinh → Tạo hồ sơ học sinh
6. Danh sách phụ huynh → Liên kết phụ huynh – học sinh
7. Danh sách lớp học → Chi tiết lớp (gán giáo viên, thêm học sinh, tạo lịch)
8. Lịch tổng hợp

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- môn, giáo viên, học sinh vừa tạo xuất hiện sẵn để chọn ở các bước sau
- tiến độ checklist thiết lập tự cập nhật khi mỗi việc xong

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Bỏ qua một bước vẫn quay lại được từ checklist.
- Chưa có giáo viên thì lớp ở trạng thái chuẩn bị, chưa vận hành.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-04 — Tiếp nhận một học viên mới

```text
Hành trình: Tiếp nhận một học viên mới (Quản trị viên; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Danh sách học sinh
2. Tạo / sửa hồ sơ học sinh
3. Chi tiết học sinh
4. Mời tạo tài khoản
5. Liên kết phụ huynh – học sinh
6. Chi tiết lớp (Học sinh): thêm vào lớp
7. Ghi danh vào khóa học (nếu có)

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- học sinh vừa tạo được mang sang từng bước, không chọn lại
- kết quả mời (đã gửi, lỗi thiếu email) hiển thị ở chi tiết học sinh

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Hồ sơ chưa có email thì không mời được: chỉ cho sửa hồ sơ rồi quay lại.
- Mời lần hai khi lời mời hết hạn: Gửi lại lời mời.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-05 — Quản lý một lớp trong tuần

```text
Hành trình: Quản lý một lớp trong tuần (Quản trị viên; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Danh sách lớp học
2. Khung chi tiết lớp (Tổng quan, Học sinh, Giáo viên, Lịch, Buổi học)
3. Chi tiết một buổi học
4. Lịch tổng hợp

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- lớp đang xem
- bộ lọc ở danh sách lớp khi quay lại

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Lớp đã lưu trữ: chỉ xem, không thêm học sinh/giáo viên/lịch.
- Giáo viên cuối cùng bị gỡ khỏi lớp: lớp chuyển về trạng thái chuẩn bị.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-06 — Một buổi dạy từ đầu đến cuối

```text
Hành trình: Một buổi dạy từ đầu đến cuối (Giáo viên; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Tổng quan (giáo viên)
2. Lịch dạy
3. Buổi học và điểm danh
4. Danh sách bài đánh giá → Soạn bài (giao bài về nhà gắn với buổi học)
5. Đơn xin phép (xem đơn của buổi này)

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- buổi học đang chọn
- lớp của buổi học
- danh sách học sinh của buổi
- đơn xin phép liên quan buổi này

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Buổi đã hủy: không điểm danh, vẫn xem được.
- Sửa điểm danh đã lưu phải có lý do.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-07 — Giao bài, chấm bài, công bố kết quả

```text
Hành trình: Giao bài, chấm bài, công bố kết quả (Giáo viên; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Danh sách bài đánh giá
2. Soạn bài (thông tin chung, chọn câu hỏi và điểm, chọn đối tượng giao, xem trước và phát hành)
3. Ngân hàng câu hỏi → Soạn câu hỏi (rồi quay lại bài đang soạn)
4. Chi tiết bài
5. Bài nộp của một bài
6. Chấm bài (chấm điểm, nhận xét, công bố)
7. Tiến độ học sinh

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- bài đang soạn và nội dung chưa lưu khi sang Ngân hàng câu hỏi rồi quay lại
- bài đang xem
- học sinh đang chấm; chuyển sang học sinh chưa chấm kế tiếp
- câu hỏi vừa soạn xuất hiện sẵn để thêm vào bài

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Bài đã có học sinh làm: đổi nội dung tạo phiên bản mới, không sửa bài làm đã bắt đầu.
- Chưa chấm xong thì không công bố được.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-08 — Theo dõi một lớp và học sinh

```text
Hành trình: Theo dõi một lớp và học sinh (Giáo viên; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Lớp của tôi
2. Chi tiết lớp (kèm Lịch sử thay đổi của lớp)
3. Tiến độ học sinh
4. Đơn xin phép
5. Buổi học và điểm danh

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- lớp đang xem
- học sinh đang xem

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Giáo viên không được tạo lớp hay thêm/rút học sinh; chỉ xem danh sách lớp được phân công.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-09 — Từ thông báo đến kết quả bài làm

```text
Hành trình: Từ thông báo đến kết quả bài làm (Học sinh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Trung tâm thông báo (thông báo có bài mới)
2. Chi tiết bài
3. Làm bài (phần chung, các loại câu)
4. Đã nộp bài
5. Kết quả
6. Xem lại bài làm
7. Tiến độ học tập

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- bài đang chọn
- lượt làm hiện tại và đáp án đã lưu
- điểm và nhận xét chỉ hiện khi đã công bố

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Mất kết nối giữa chừng: bài được giữ và tự lưu khi có mạng lại.
- Hết giờ: tự nộp phần đã làm rồi tới Đã nộp bài.
- Đã hết lượt: vào lại thì chỉ xem kết quả lượt trước.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-10 — Xem lịch và chuẩn bị buổi học

```text
Hành trình: Xem lịch và chuẩn bị buổi học (Học sinh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Tổng quan (học sinh)
2. Lịch học
3. Chi tiết một buổi học (nội dung buổi, điểm danh của em)
4. Bài tập
5. Chi tiết bài

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- buổi học đang chọn
- bài về nhà gắn với buổi học đó

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Buổi đã hủy hoặc đổi giờ: hiển thị rõ và có thông báo.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-11 — Xem tình hình của con

```text
Hành trình: Xem tình hình của con (Phụ huynh (có thể có nhiều con); responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Chọn con
2. Tổng quan của con
3. Lịch học của con
4. Điểm danh của con
5. Bài và kết quả của con
6. Nhận xét
7. Tiến độ của con

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- con đang xem: chọn một lần, mọi màn sau theo con đó; đổi con thì mọi màn đổi theo

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Liên kết với con đã kết thúc: mất quyền xem ngay.
- Kết quả chưa công bố: phụ huynh không thấy.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```

### HT-12 — Báo con nghỉ học

```text
Hành trình: Báo con nghỉ học (Phụ huynh; responsive: dùng tốt cả trên điện thoại lẫn trên web máy tính)

Mục tiêu: nối các màn dưới đây thành một luồng người dùng đi được từ đầu đến cuối mà không phải tìm lại hay chọn lại thông tin đã có. Hãy dựng luồng có thể bấm thử xuyên suốt giữa các màn đã vẽ trong dự án.

Các chặng theo thứ tự (bằng tên màn hình):
1. Lịch học của con (hoặc Điểm danh của con)
2. Đơn xin phép (chọn buổi, nhập lý do, gửi)
3. Trung tâm thông báo (khi giáo viên chấp nhận hoặc từ chối)
4. Điểm danh của con

Thông tin phải được mang theo giữa các chặng, người dùng không phải chọn hay nhập lại:
- con đang xem
- buổi học được chọn từ lịch
- lý do đã nhập khi gửi

Điểm rẽ và trường hợp đặc biệt cần có đường đi:
- Đơn xin phép không tự đổi điểm danh; giáo viên là người quyết định.

Yêu cầu chung: ở mỗi chặng người dùng luôn biết mình đang ở đâu trong luồng và quay lại được chặng trước mà không mất dữ liệu đã nhập; các màn dùng chung cùng một ngữ cảnh (lớp, học sinh, bài, con đang xem). Không thêm màn mới, chỉ nối và làm nhất quán những màn đã có. Nếu một chặng chưa có màn trong dự án thì để một điểm dẫn tới và ghi rõ tên chặng.
```
