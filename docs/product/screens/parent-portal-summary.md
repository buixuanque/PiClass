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

## Review độc lập và sửa (opus, `DEC-LRN-044`)
Không thấy rò quyền hay dữ liệu (IDOR theo `student_id`, liên kết hết hiệu lực, `notes`/`review_required`, kết quả chưa công bố, `questions` đều đã chặn). Các lỗi tìm ra đã sửa ở `piclass-api` #89, `piclass-web` #65:
- **Gửi đơn đồng thời** (vừa): bố và mẹ gửi cùng lúc có thể sinh hai đơn. Sửa bằng transaction khóa dòng buổi học và chỉ mục duy nhất một phần; vi phạm trả `EXPLANATION_ALREADY_EXISTS`.
- **Duyệt lại đơn cũ** phá luật một đơn còn hiệu lực: giờ trả 409; danh sách đơn có thứ tự ổn định (mới nhất trước).
- **Bài con làm dở bị hiện là "Đã nộp"** và biến khỏi thẻ Bài tập: BE thêm `has_in_progress_attempt`, FE dùng cờ này.
- **Hộp thoại gửi đơn** mang lý do và lỗi cũ sang buổi hoặc con khác: dựng lại theo `studentId:sessionId`; ô lý do giới hạn 2000 ký tự (cả BE lẫn FE).
- **Lỗi 409** không làm mới danh sách đơn: giờ làm mới và hiện trạng thái đơn. **Nút "Xin nghỉ buổi này"** chỉ hiện khi danh sách đơn đã tải xong.
- **Màn trống trơn** khi danh sách con lỗi hoặc chưa liên kết: `ChildSwitcher` hiện loading, lỗi kèm thử lại, hoặc lời báo chưa liên kết; dùng chung cho cả bốn màn.
- Query key kết quả của con chuyển vào `resultsKeys`. Bổ sung test thiếu ở BE (liên kết đã kết thúc trả 404, vai trò khác bị chặn, `student_id` bị bỏ qua với Student) và FE (con đã lưu không còn liên kết quay về con đầu).

## Mật độ và bảng cho màn rộng (user duyệt phương án A và B, 2026-11-02)

Mockup `ui_kits/parent` chỉ vẽ khổ điện thoại 390px. Trên web (từ 1024px), **chỉ ở màn rộng**, điện thoại và tablet giữ nguyên:
- **Lệch mockup có chủ ý:** thẻ gọn hơn (`Card compact`, nhãn `Badge size="auto"`); hai ô số liệu ở Tổng quan trải hết chiều ngang, xếp ngang (biểu tượng, số, nhãn); "Theo môn" và "Bài của con" ở Kết quả xếp một cột để bảng có đủ chỗ.
- **Danh sách thành bảng:** "Các buổi gần đây" ở Điểm danh (Ngày + giờ, Lớp, Trạng thái, Đơn xin phép; nút gửi đơn nằm ở cột cuối) và "Bài của con" ở Kết quả (Môn/Lớp, Bài, Hạn, Trạng thái, nút Xem kết quả). Dưới 1024px vẫn là hàng như trước.
- Màn Điểm danh chỉ chia hai cột (tóm tắt | danh sách) từ 1280px; từ 1024 đến 1279px là một cột để bảng không bị hẹp.
- Không đổi bảng màu, phông, dữ liệu hay API.

## Tồn đọng và việc cần user xem
- Chưa xem giao diện trên trình duyệt thật: mới kiểm bằng test (MSW) và test BE. Hai con mẫu của `parent4@piclass.test` chưa xếp lớp nên mọi khối rỗng; cần xếp lớp và tạo vài buổi vắng để thử gửi đơn thật.
- Chưa có chuông thông báo (CM-06) ở cổng này; tên giáo viên trong nhận xét lấy từ API khi có (`B-090`).
- Điểm danh chỉ 10 buổi gần nhất; muốn xem theo tháng cần API mới (tuỳ nhu cầu thực tế).
- Review độc lập đã chạy và sửa (mục trên). Chưa xử lý, đã ghi `BACKLOG.md`: thống nhất hai hàm lọc liên kết phụ huynh (`parentCoversStudent` và `activeChildrenIds`, `B-091`).

## Đối chiếu lại mockup (2026-11-02, user yêu cầu "xem lại mockup để sửa UI")

**Nguồn mockup đúng:** `piclass-mockup/design-system/PiClass Phụ huynh.dc.html` (bản dựng `standalone/PiClass Phụ huynh.html`): desktop có sidebar, menu 7 mục (Tổng quan, Lịch học, Điểm danh, Bài tập & kết quả, Nhận xét, Tiến độ, Đơn xin phép). Các cụm P1, P2 ở trên chủ yếu đọc `ds-v2-source/ui_kits/parent` (bộ kit cũ, khung điện thoại 390px).

**Nguyên tắc:** như cổng Học sinh. Phần "mật độ và bảng" bị hoàn lại; bỏ `AttendanceTable` và bảng "Bài của con".

| Màn | Lệch so với mockup | Xử lý |
|---|---|---|
| Mọi màn | h1 24px; thiếu dòng phụ | Sửa: `PageHeading` |
| Tổng quan | Thẻ con trắng và lời chào tách rời; mockup là banner gradient bo 24, avatar 80 viền xanh, "Chào …", tên con 28, dòng lớp và "N buổi học hôm nay" | Sửa: `ChildSwitcher variant="hero"` |
| Tổng quan | Hai ô số liệu; mockup cột phải có vòng "Chuyên cần" và "Nhận xét mới nhất" | Sửa. Điểm trung bình bỏ khỏi Tổng quan (xem ở Kết quả, kèm "từ N bài") |
| Tổng quan | "Hôm nay" là dòng chữ; mockup thẻ giờ như học sinh | Sửa: `SessionCard` |
| Tổng quan | Bài tập: môn là dòng chính; mockup tên bài là dòng chính, dòng phụ môn và hạn, ô biểu tượng theo loại bài | Sửa (hoàn lại thay đổi "môn là dòng chính" của vòng engagement) |
| Điểm danh | Vòng + bảng; mockup là hàng 5 số liệu + dải 10 ô theo buổi (có mặt, muộn, vắng, có phép) kèm ngày | Sửa. Buổi vắng liệt kê riêng để gửi đơn (mockup để ở menu "Đơn xin phép", ta không có menu này, `DEC-LRN-043`) |
| Kết quả | Bảng "Bài của con"; mockup thẻ bài bo 18, huy hiệu, điểm | Sửa: `ListRow` kèm nút "Xem kết quả" |
| Kết quả | Hàng số liệu bọc thẻ | Sửa: để trơn |
| Lịch học | Danh sách giờ-tiêu đề nhỏ | Sửa: `SessionCard` như học sinh |
| Menu 7 mục, Tiến độ riêng, Nhận xét riêng, Đơn xin phép riêng | Ta chỉ có 4 mục | Giữ (`DEC-LRN-042`, `DEC-LRN-043`) |
| Nút "Xin phép nghỉ" ở thẻ lịch hôm nay | Không có | Giữ bỏ: gửi đơn từ Lịch học hoặc Điểm danh |
| Chuông, chọn con ở thanh trên | Chọn con nằm trong nội dung | Giữ: khung chung với mọi cổng |

**Cần user quyết:** có muốn khôi phục menu "Đơn xin phép" riêng và "Nhận xét" riêng như mockup (7 mục) không, hay giữ 4 mục gọn như đã chốt.

## Đợt chỉnh 2026-11-03 (`DEC-LRN-046`)

| Màn | Thay đổi | Ghi chú |
|---|---|---|
| Thanh trên | Ô chọn con ở góc trái (viên thuốc 220px + biểu tượng gia đình) như mockup; bỏ thẻ chọn con trong từng màn | Khung chung chỉ chừa chỗ `#topbar-start`, ô con gắn vào bằng portal từ layout `/parent` |
| Lịch học | Lịch Ngày/Tuần/Tháng dùng lại view Admin/Giáo viên, mặc định Tuần, lọc theo con (`student_id`) | Buổi đã qua có chip điểm danh; không hiện nhãn "Chờ hoàn tất" |
| Điểm danh | "Cần xử lý (n)", danh sách theo tháng có lọc, nhãn đơn trên dòng buổi vắng, "Lịch sử đơn" thu gọn; hộp thoại chi tiết đơn | BE thêm `from`/`until`. Nhãn đơn: Chờ duyệt / Đã chấp nhận / Bị từ chối |
| Kết quả, tab Nhận xét | Mỗi nhận xét một thẻ như mockup, nhóm theo tháng khi nhiều tháng | Thẻ dùng chung với cổng Học sinh |

**Còn chờ user:** mục "Lớp & khóa học" của con (chỉ xem, không hiện danh sách bạn cùng lớp, chỉ sĩ số) và xem bài làm của con kèm kết quả cô chấm (đổi `DEC-FILE-003`, `DEC-LRN-042`).

