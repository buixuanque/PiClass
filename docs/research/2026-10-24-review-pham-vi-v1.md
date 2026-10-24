# Review phạm vi V1: có quá phức tạp không, thực tế có cần không

**Ngày:** 2026-10-24
**Người dùng mục tiêu (user chốt 2026-10-24):** 2 trung tâm hiện có (kiểu nhiều lớp dạy thêm), có thể dùng cho 1 trường học; mục tiêu V1 là hướng tới **nhiều trung tâm**.
**Nguồn đã đọc:** `product/product-definition.md`, `product/feature-map.md`, `requirements/decision-log.md`, `assessment-rules.md`, `learning-rules.md`, `architecture/authentication-and-security.md`, `product/screens/mockup-plan.md`.
**Giới hạn:** Đây là đánh giá từ tài liệu và hiểu biết chung về vận hành lớp học thêm, **chưa kiểm chứng với người dùng thật** và chưa tra so sánh thị trường. Mục "Cần hỏi người dùng thật" ở cuối là phần quyết định cuối cùng.

> **Kết quả chốt (user, 2026-10-24):** hướng A (mỗi trung tâm một bản triển khai); **giữ Course** (không hoãn như đề xuất ở mục 2); giữ nguyên 9 loại câu hỏi; chưa làm nhập học sinh hàng loạt; thông báo chỉ trong ứng dụng. Mục tiêu V1 thu về chức năng cơ bản nhất của một trung tâm, mở rộng sau khi hỏi giáo viên. Danh sách API cần thêm/bỏ: `2026-10-24-api-v1-toi-thieu.md`.

## 1. Phát hiện lớn nhất: mục tiêu "nhiều trung tâm" mâu thuẫn với tài liệu đã chốt

Tài liệu hiện chốt **một tenant duy nhất**:

- `DEC-LRN-010` và `BR-COURSE-001`: "vẫn trong một tenant duy nhất (không cần multi-tenant)".
- `architecture/authentication-and-security.md`: không bổ sung multi-tenant; nếu mở rộng nhiều trung tâm thì "review tenancy/scope như quyết định mới".
- Product Definition: "V1 phục vụ các lớp học thêm hiện tại của giáo viên", nhiều giáo viên là định hướng tương lai.

Nhiều quy tắc đã chốt ngầm giả định một tổ chức duy nhất: Admin "quản lý toàn hệ thống", Question Bank "dùng chung trong phạm vi hệ thống" (`DEC-Q-002`), "Admin cuối cùng" (`DEC-USR-006`), Course dùng chung, danh sách Teacher và Student toàn cục.

Hai hướng, khác nhau rất lớn về công sức:

| | A. Mỗi trung tâm một bản triển khai riêng | B. Multi-tenant thật (một hệ thống, nhiều trung tâm) |
|---|---|---|
| Cách làm | Cùng một code, mỗi trung tâm một database, một domain (hoặc một container) | Thêm `center_id` vào hầu hết bảng, mọi truy vấn và Policy lọc theo trung tâm |
| Ảnh hưởng code đã có | Gần như không phải sửa BE; thêm việc triển khai và cấu hình | Sửa schema, 5 module BE đã merge, hơn 400 test, Question Bank, Audit, Notification, Scheduler |
| Phù hợp khi | Vài trung tâm (2 đến khoảng 10), mỗi nơi tự quản dữ liệu | Hàng chục trung tâm trở lên, hoặc một người dạy ở nhiều trung tâm cùng một tài khoản |
| Rủi ro | Phải vận hành, cập nhật, backup nhiều bản; mỗi bản một chi phí EC2 hoặc một database | Rò dữ liệu chéo trung tâm nếu quên lọc một chỗ; công việc lớn trước khi có người dùng đầu tiên |
| Giá trị với giai đoạn này | Cao: ra mắt sớm, an toàn dữ liệu (cách ly vật lý) | Thấp ở giai đoạn 2 trung tâm |

**Đề xuất:** chọn A cho V1 (mỗi trung tâm một bản triển khai, cùng một code). Giữ B cho khi có khoảng hơn 10 trung tâm hoặc có nhu cầu một tài khoản dùng nhiều trung tâm. Với A cần ghi rõ: cấu hình theo trung tâm (tên, logo, năm học) nằm ở file cấu hình hoặc bảng settings, và quy trình tạo một trung tâm mới (seed Admin đầu tiên, đã có màn "Thiết lập lần đầu"). Quyết định này cần user chốt, rồi ghi vào `architecture/open-decisions.md`.

## 2. Đánh giá từng nhóm tính năng

Quy ước: **Giữ** (cốt lõi), **Đơn giản hóa** (giữ nhưng giảm UI hoặc cấu hình), **Hoãn** (làm sau khi có người dùng yêu cầu), **Kiểm chứng** (không đủ dữ kiện, hỏi người dùng thật).

### Cốt lõi, giữ nguyên

| Tính năng | Lý do |
|---|---|
| Đăng nhập, quên mật khẩu, hồ sơ | Bắt buộc |
| Hồ sơ Student/Parent/Teacher tạo trước tài khoản (`DEC-USR-001`) | Phụ huynh nhiều khi không dùng app, vẫn cần hồ sơ |
| Lớp, thành viên lớp, phân công giáo viên | Trục chính của trung tâm |
| Lịch học lặp và sinh buổi học | Làm tay từng buổi thì không dùng nổi |
| Điểm danh | Việc giáo viên làm hằng ngày, tần suất cao nhất |
| Bài tập, bài kiểm tra, nộp bài, chấm, kết quả | Lý do hệ thống tồn tại |
| Phụ huynh xem điểm danh, điểm, nhận xét | Giá trị bán hàng chính với phụ huynh |
| Thông báo trong ứng dụng | Tối thiểu |
| Audit cho sửa điểm và sửa điểm danh | Cần cho niềm tin giữa giáo viên, phụ huynh và trung tâm |

### Phức tạp hơn mức cần ở V1: đề xuất đơn giản hóa hoặc hoãn

| Tính năng | Vấn đề | Đề xuất |
|---|---|---|
| **Course (tự học thêm)** + `CourseEnrollment` có lịch sử hiệu lực + Course dùng chung / của giáo viên + ghi danh theo lớp hoặc theo từng học sinh | Thêm một module nguyên vẹn, thêm kiểu audience thứ 3 và logic dedupe giữa Class / Student / Course. Chưa có dữ kiện cho thấy 2 trung tâm cần | **Hoãn phần FE**: giữ BE đã xong, chưa làm mockup và FE cho Course trong lượt đầu. Bật lại khi có trung tâm hay trường yêu cầu |
| **Phiên bản hóa câu hỏi và bài đã phát hành** (`DEC-Q-004`, `DEC-AS-004`) | Đúng về dữ liệu, nhưng với giáo viên là khái niệm khó: "Soạn phiên bản mới", "bài đã giao trước đó vẫn dùng nội dung cũ" | Giữ ở BE. Ở UI ẩn thuật ngữ "phiên bản", chỉ báo "Bài đã có n em làm; sửa sẽ chỉ áp dụng cho lượt làm sau" |
| **Cấu hình công bố điểm** (hẹn giờ công bố, cờ riêng cho đáp án, lời giải, điểm từng câu) | Giáo viên thường chỉ cần "công bố ngay khi chấm xong" hoặc "công bố sau" | Đưa 2 đến 3 preset ra UI (công bố ngay / sau khi chấm xong cả lớp / hẹn giờ). Cờ chi tiết nằm trong mục "Nâng cao" thu gọn |
| **Cấp lại lượt làm Exam (reissue)**, vào Exam muộn, gia hạn theo từng học sinh | Hiếm gặp, nhưng đã có BE và mockup | Giữ, **ưu tiên thấp** ở FE; làm sau cùng trong nhóm Assessment |
| **Chín loại câu hỏi** (đặc biệt Matching, Ordering và quy tắc chuẩn hóa đáp án Fill in the Blank) | Mỗi loại thêm giao diện làm bài, soạn câu, tự chấm và test. Mockup đã ghi File Upload "chưa dùng được" | Đợt FE đầu: Single, Multiple, True/False, Fill in the Blank, Short Answer, Essay. **Kiểm chứng** File Upload (xem dưới); Matching và Ordering hoãn |
| **Quiz và Exam là hai loại riêng** | Khác nhau chủ yếu ở khung giờ và quy tắc sự cố. Feature map từng nêu nghi vấn này | Giữ ở dữ liệu (đã là một bảng chung). Ở UI có thể gộp việc chọn thành "có khung giờ thi chặt: có / không" |
| **Giải trình vắng: đổi quyết định, gợi ý mở điểm danh** | Nhiều bước hơn mức đơn xin phép thực tế cần | Giữ tạo đơn, chấp nhận / từ chối kèm ghi chú. Bỏ "đổi quyết định" ở UI đợt đầu |
| **Giao bài theo ba kiểu audience** (lớp, học sinh, khóa) | Dedupe, đồng bộ khi có học sinh vào lớp muộn (`ARCH-14` còn mở) | Đợt đầu chỉ cần giao theo lớp và theo học sinh. Giao theo khóa đi cùng Course |
| **Giao diện nhật ký audit đầy đủ** | Hữu ích cho Admin nhưng không phải việc hằng ngày | Một danh sách có lọc cơ bản là đủ; BE giữ nguyên |
| **Biểu đồ tiến độ nhiều bộ lọc** (7 / 30 ngày, theo môn, chuyển em trước và sau) | Bản mẫu còn chờ API, nhiều thao tác | Giữ bảng tổng hợp theo môn và một biểu đồ xu hướng; bỏ lọc phụ |

### Còn thiếu: có thể quan trọng hơn mọi thứ ở trên khi hướng tới nhiều trung tâm

Đây là những chỗ tài liệu hiện **loại ra** nhưng dễ là thứ trung tâm hỏi đầu tiên. Chưa đề xuất thêm vào, chỉ đưa ra để user chốt có chủ ý.

| Khoảng trống | Vì sao đáng lo |
|---|---|
| **Nhập học sinh hàng loạt** (`DEC-USR-004` loại khỏi V1) | Một trung tâm 100 đến 300 học sinh mà phải tạo từng hồ sơ bằng tay thì khó bắt đầu dùng. Nhập CSV cơ bản có thể là điều kiện để dùng được |
| **Học phí** (ngoài phạm vi V1) | Với lớp dạy thêm, theo dõi học phí thường là nhu cầu số một. Không có nó, hệ thống chỉ giải quyết phần dạy và học, chưa giải quyết phần vận hành trung tâm |
| **Thông báo chỉ trong ứng dụng** (`DEC-NOTIF-002`) | Phụ huynh ở Việt Nam thường không mở app mà chờ tin qua Zalo hoặc SMS. Thông báo vắng mặt và điểm mới có thể không tới nơi. Ít nhất cân nhắc email, hoặc Zalo ZNS sau này |
| **Một giáo viên dạy ở nhiều trung tâm** | Không thành vấn đề với hướng A (mỗi nơi một tài khoản), thành vấn đề thật với hướng B |

## 3. Rủi ro về quy mô công việc

BE đã xong 5 nhóm module, nhưng **FE chưa làm nhóm nào** và mockup có hơn 100 màn. Làm đủ rộng mới ra mắt dễ kéo dài lâu và chỉ biết đúng sai khi có người dùng thật.

**Đề xuất đi theo lát dọc nhỏ (vertical slice), thử với một trung tâm trước:**

1. Đăng nhập, Admin tạo học sinh, giáo viên, lớp, lịch.
2. Giáo viên điểm danh; phụ huynh xem điểm danh.
3. Giáo viên tạo bài (6 loại câu hỏi), giao cho lớp; học sinh làm; giáo viên chấm; học sinh và phụ huynh xem điểm.
4. Thông báo cơ bản.

Các phần còn lại (Course, Matching/Ordering, reissue, audit UI đầy đủ, báo cáo tiến độ) bổ sung theo phản hồi.

## 4. Cần hỏi người dùng thật (2 trung tâm) trước khi chốt

1. Một ngày thường giáo viên làm những việc nào trên giấy hoặc Zalo/Excel hiện nay? Việc nào tốn thời gian nhất?
2. Bài tập hiện giao dưới hình thức nào: phiếu giấy, ảnh chụp bài làm gửi Zalo, hay làm online? (Quyết định File Upload và độ ưu tiên của làm bài trực tuyến.)
3. Loại câu hỏi nào thật sự dùng: trắc nghiệm, điền từ, tự luận, nối, sắp xếp? Môn nào (Toán, Anh…)?
4. Phụ huynh hiện nhận tin kiểu gì (Zalo, SMS, gọi điện)? Có chấp nhận phải tải hoặc mở web mới biết con vắng hay điểm bao nhiêu không?
5. Học phí hiện quản lý ra sao và có muốn gộp vào cùng hệ thống không?
6. Có bao nhiêu học sinh đang theo học (để cân nhắc nhập CSV), và có cần chuyển dữ liệu cũ sang không?
7. Có "kiểm tra có giờ thi chặt" (Exam) không, hay chủ yếu bài tập về nhà và kiểm tra miệng/giấy?

## 5. Việc cần user quyết định (chưa ghi vào Decision Log)

1. Hướng tenancy: A (mỗi trung tâm một bản triển khai) hay B (multi-tenant thật). Đề xuất A.
2. Có hoãn FE của Course ở đợt đầu không. Đề xuất hoãn.
3. Đợt FE đầu dùng 6 hay 9 loại câu hỏi (phụ thuộc câu trả lời 2 và 3 ở mục 4).
4. Nhập CSV học sinh có đưa vào V1 không (đảo `DEC-USR-004`).
5. Thông báo ngoài ứng dụng (email) có đưa vào V1 không (đảo `DEC-NOTIF-002`).

Khi user chốt, ghi từng quyết định vào `requirements/decision-log.md` hoặc `architecture/open-decisions.md` theo quy ước của repo.
