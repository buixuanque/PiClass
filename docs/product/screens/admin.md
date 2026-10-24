# Đặc tả màn hình — Cổng Admin (AD)

> Tài liệu này mô tả **mỗi màn hình của cổng Admin có những khối nội dung và chức năng gì**, để người thiết kế UI vẽ mockup. Không bàn màu sắc, kiểu dáng. Gốc là `docs/product/screen-inventory.md` (mã màn hình, ký hiệu ✅🟡🔵⛔🎨); trường dữ liệu lấy từ code thật của `piclass-api` (Resource, Request, Action, Policy), nghiệp vụ từ `docs/requirements/*` và `docs/architecture/error-codes.md`.
> Admin là người thiết lập một lần rồi ít dùng hằng ngày (người dùng, môn, lớp, lịch, ghi danh) nên màn hình ưu tiên **rõ ràng, an toàn khi thao tác nhạy cảm** (vô hiệu hóa, kết thúc, sửa hồi tố) hơn là nhanh. Responsive: ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
> Khi một tài khoản giữ cả Admin và Teacher, giả định một khung điều hướng chung có hai nhóm "Giảng dạy" và "Quản trị" (Q6, chưa chốt chính thức). Các màn dùng chung với Teacher (buổi học và điểm danh, đơn xin phép, ngân hàng câu hỏi, bài đánh giá) đặc tả ở file Teacher, không lặp ở đây.

## Mục lục

- [Quy ước trình bày](#quy-ước-trình-bày)
- [Mẫu chung](#mẫu-chung) (M1 bảng danh sách · M2 form tạo/sửa · M3 chi tiết nhiều tab · M4 hộp thoại xác nhận có lý do · M5 trạng thái và lỗi dùng chung · M6 nhãn tiếng Việt)
- [Khoảng trống BE riêng cổng Admin (mã GM)](#khoảng-trống-be-riêng-cổng-admin-mã-gm)
- **Tổng quan và thiết lập:** [AD-01 Tổng quan](#ad-01--tổng-quan) · [AD-15 Thiết lập lần đầu](#ad-15--thiết-lập-lần-đầu-checklist)
- **Tài khoản:** [AD-02 Danh sách tài khoản](#ad-02--danh-sách-tài-khoản) · [AD-03 Chi tiết tài khoản](#ad-03--chi-tiết-tài-khoản)
- **Học viên:** [AD-04 Danh sách](#ad-04--học-viên-danh-sách) · [AD-05 Chi tiết](#ad-05--học-viên-chi-tiết) · [AD-05a Form tạo/sửa](#ad-05a--học-viên-tạo--sửa) · [AD-05b Mời tài khoản](#ad-05b--mời-tạo-tài-khoản-hộp-thoại-dùng-chung-3-loại-hồ-sơ) · [AD-05c Gửi lại lời mời](#ad-05c--gửi-lại-lời-mời--đặt-mật-khẩu-hộ)
- **Phụ huynh:** [AD-06 Danh sách](#ad-06--phụ-huynh-danh-sách) · [AD-06a Chi tiết](#ad-06a--phụ-huynh-chi-tiết) · [AD-06b Form tạo/sửa](#ad-06b--phụ-huynh-tạo--sửa) · [AD-06c Liên kết phụ huynh–học sinh](#ad-06c--liên-kết-phụ-huynhhọc-sinh)
- **Giáo viên:** [AD-07 Danh sách](#ad-07--giáo-viên-danh-sách) · [AD-07a Chi tiết](#ad-07a--giáo-viên-chi-tiết) · [AD-07b Form tạo/sửa](#ad-07b--giáo-viên-tạo--sửa)
- **Học vụ:** [AD-08 Môn học](#ad-08--môn-học) · [AD-09 Danh sách lớp](#ad-09--lớp-học-danh-sách) · [AD-10 Chi tiết lớp (khung tab)](#ad-10--chi-tiết-lớp-khung-nhiều-tab) → [10a Tổng quan](#ad-10a--tab-tổng-quan) · [10b Học sinh](#ad-10b--tab-học-sinh) · [10c Giáo viên](#ad-10c--tab-giáo-viên) · [10d Lịch](#ad-10d--tab-lịch) · [10e Buổi học](#ad-10e--tab-buổi-học)
- **Lịch và khóa học:** [AD-11 Lịch tổng hợp](#ad-11--lịch-tổng-hợp-toàn-hệ-thống) · [AD-12 Khóa học](#ad-12--khóa-học-danh-sách-và-chi-tiết) · [AD-12a Ghi danh](#ad-12a--ghi-danh-vào-khóa-học)
- **Kiểm soát:** [AD-13 Cấp lại lượt làm bài](#ad-13--cấp-lại-lượt-làm-bài) · [AD-14 Nhật ký audit](#ad-14--nhật-ký-audit)
- [Danh sách thay đổi mã màn hình so với `screen-inventory.md`](#thay-đổi-mã-màn-hình-so-với-screen-inventorymd)

## Quy ước trình bày

- `GET /x` viết tắt cho `/api/v1/x`. Mọi endpoint Admin dưới đây thuộc nhóm `role:admin`, trừ khi ghi khác (đọc lớp/môn/khóa học/buổi/lịch dùng chung với vai trò khác nhưng Admin luôn xem được tất cả).
- Thời gian hiển thị giờ Việt Nam (+07:00), định dạng ngày `dd/MM/yyyy`, giờ `HH:mm`.
- Danh sách chưa phân trang trừ `GET /users` (`meta.total`, `meta.current_page`, `meta.last_page`; tham số `page`, `per_page`, mặc định 15, **không có giới hạn trên** do BE không validate). Với danh sách khác, FE tự lọc/tìm/sắp xếp trên toàn bộ dữ liệu đã tải (N9), nên mockup vẫn vẽ ô tìm và bộ lọc.
- **Tìm kiếm không dấu:** BE dùng `ilike`, gõ "nguyen" không ra "Nguyễn". Mọi ô tìm tên bên dưới nên vẽ kèm chú thích "gõ có dấu" cho tới khi BE xử lý (đề xuất BE xử lý). Với danh sách FE tự lọc thì FE có thể bỏ dấu khi so khớp.
- Mã trạng thái 🟡 trỏ tới mã G ở `screen-inventory.md`/brief; khoảng trống chỉ riêng Admin dùng mã **GM** ở mục sau.
- Dữ liệu mẫu: lớp "Toán 9A", "Văn 8B", "Anh 6C"; giờ 17:30–19:00; trung tâm dạy thêm ở Việt Nam.

---

## Mẫu chung

### M1 — Bảng danh sách có lọc / tìm / phân trang

Dùng cho AD-02, 04, 06, 07, 08, 09, 12 và các bảng con trong tab.

- **Khung trên:** tiêu đề + số lượng ("124 học viên"); nút hành động chính bên phải (Tạo mới…); thanh công cụ gồm ô tìm theo tên, các bộ lọc (chip hoặc dropdown), nút "Xóa lọc".
- **Bảng:** cột đầu là tên bấm được (mở chi tiết); cột trạng thái dùng chip nhãn tiếng Việt (M6); cột cuối là menu "…" cho thao tác nhanh; dòng có trạng thái không hoạt động (inactive/archived) hiển thị mờ nhưng vẫn bấm được; sắp xếp mặc định theo tên (A→Z) trừ audit (mới nhất trước).
- **Phân trang:** `GET /users` phân trang phía server (hiện "Trang 2/8 · 124 kết quả", chọn số dòng). Các danh sách còn lại hiện đủ rồi cắt trang ở FE (20 dòng) cho tới khi BE bổ sung (G5).
- **Trạng thái:** tải (khung xương các dòng); rỗng hoàn toàn (hình + câu "Chưa có … " + nút tạo mới); rỗng do lọc ("Không có kết quả khớp", nút Xóa lọc); lỗi (thông điệp + nút Thử lại); không có quyền (403/404 cùng "Request failed.", dẫn về trang chủ).
- **Hành vi:** đổi bộ lọc quay về trang 1; giữ bộ lọc trong URL để bấm "Quay lại" từ chi tiết còn nguyên; sau khi tạo/sửa xong, quay lại danh sách và làm nổi dòng vừa đổi.

### M2 — Form tạo / sửa

- Dạng trang (hoặc ngăn trượt bên phải) với nhóm trường, nhãn tiếng Việt, đánh dấu trường bắt buộc.
- Nút **Lưu** (chính) và **Hủy**; Lưu vô hiệu hóa khi đang gửi (tránh bấm đúp); rời trang khi đã sửa dở thì hỏi xác nhận.
- Lỗi 422 hiện ngay dưới từng trường từ `error.details.<trường>`; lỗi nghiệp vụ (`error.code`) hiện thanh thông báo đầu form kèm thông điệp tiếng Việt.
- Form **sửa**: chỉ gửi trường thay đổi (BE dùng `sometimes`, trường vắng là không đổi; gửi `null` là xóa giá trị với trường nullable).
- Thành công: thông báo ngắn ("Đã lưu") rồi về chi tiết/danh sách; không dùng hộp thoại chặn.

### M3 — Trang chi tiết nhiều tab

- **Đầu trang:** breadcrumb ("Lớp học › Toán 9A"), tên + chip trạng thái, dòng phụ (môn, nhãn), cụm nút hành động chính (Sửa, Lưu trữ…).
- **Dải tab:** tab đang chọn lưu trong URL (`?tab=`) để tải lại vẫn đúng tab; tab hiện số lượng nếu có ("Học sinh · 18"); dữ liệu tab tải lười khi mở lần đầu, có khung xương riêng và trạng thái lỗi riêng (một tab lỗi không làm hỏng cả trang).
- **Nội dung tab:** các khối xếp dọc; hành động của khối nằm góc phải tiêu đề khối.
- 404 của cả đối tượng (id sai/không tồn tại): trang "Không tìm thấy" (CM-07), không hiện khung rỗng.

### M4 — Hộp thoại xác nhận có lý do

Dùng cho: kết thúc thành viên/phân công/ghi danh/liên kết, hủy lịch, hủy buổi, sửa hồi tố, cấp lại lượt làm bài, sửa lịch.

- Tiêu đề nêu **đúng hành động và đối tượng** ("Kết thúc tư cách thành viên của Nguyễn Minh Anh trong Toán 9A").
- Dòng cảnh báo hậu quả bằng ngôn ngữ nghiệp vụ (ai bị ảnh hưởng, có hoàn tác được không).
- Ô **Lý do** nhiều dòng, bắt buộc khi BE bắt buộc (`required`): để trống hiện lỗi dưới ô, không gửi. Gợi ý nhanh bằng vài chip lý do thường gặp (điền sẵn vào ô, vẫn sửa được).
- Nút "Xác nhận" màu cảnh báo cho hành động phá hủy/khó hoàn tác; nút "Hủy bỏ" luôn có; Esc/bấm nền = hủy bỏ.
- Khi BE trả lỗi nghiệp vụ: giữ hộp thoại mở, hiện lỗi trong hộp thoại, giữ nguyên nội dung lý do đã gõ.

### M5 — Trạng thái và lỗi dùng chung

- 401: về đăng nhập (CM-01). 403/404: cùng thông điệp "Request failed." → hiển thị "Bạn không có quyền hoặc không tìm thấy nội dung này" (không để lộ sự tồn tại).
- 429 `TOO_MANY_ATTEMPTS` (chỉ ở đăng nhập): "Bạn thử quá nhiều lần, vui lòng đợi rồi thử lại."
- 5xx/mất mạng: banner "Không kết nối được, thử lại" + giữ dữ liệu đã nhập.
- Lỗi nghiệp vụ trả `{error:{code,message}}`; FE rẽ nhánh theo `code`, `message` chỉ để hiển thị. Riêng `ACTIVE_ASSIGNMENTS_EXIST` có thêm khóa `active_class_ids` **ở cấp ngoài cùng của JSON, cạnh `error`**.

### M6 — Nhãn tiếng Việt dùng thống nhất

| Giá trị BE | Nhãn |
|---|---|
| `admin` / `teacher` / `student` / `parent` | Quản trị viên / Giáo viên / Học sinh / Phụ huynh |
| User `active` / `inactive` | Đang hoạt động / Đã vô hiệu hóa |
| Hồ sơ `active` / `inactive` | Đang hoạt động / Ngừng hoạt động |
| Lớp `preparing` / `active` / `archived` | Đang chuẩn bị / Đang hoạt động / Đã lưu trữ |
| Môn `active` / `inactive` | Đang dùng / Ngừng sử dụng |
| Khóa học `active` / `archived` | Đang mở / Đã lưu trữ |
| Thành viên, phân công, ghi danh `active` / `ended` | Đang hiệu lực / Đã kết thúc |
| Lịch `active` / `cancelled` / `ended` | Đang áp dụng / Đã hủy / Đã kết thúc |
| Buổi `scheduled` / `completed` / `cancelled` | Dự kiến / Đã hoàn tất / Đã hủy |
| Thứ trong lịch `1…7` | ISO: 1 = Thứ Hai … 7 = Chủ nhật |

---

## Khoảng trống BE riêng cổng Admin (mã GM)

Phát hiện khi đối chiếu code; ngoài danh sách G1–G14. Các mục này ảnh hưởng nhiều màn nên gom lại, màn hình bên dưới chỉ trỏ mã.

| Mã | Khoảng trống | Ảnh hưởng | Cách ghép tạm |
|---|---|---|---|
| GM-1 | **Không nối được tài khoản (User) với hồ sơ.** `GET /users/{id}` không trả `person_profile_id` hay id hồ sơ Student/Parent/Teacher; `GET /students|parents|teachers/{id}` không trả tài khoản (login, trạng thái) | AD-03 không mở được hồ sơ tương ứng; AD-05/06a/07a không hiện được khối "Tài khoản" thật | Tìm `GET /users?name=` rồi khớp theo tên (không chắc chắn nếu trùng tên); không có cách chính xác |
| GM-2 | **Không có truy vấn ngược "lớp / khóa học của một người".** Chi tiết học viên/giáo viên không trả lớp; `GET /students/{id}/progress` chỉ trả điểm | AD-05, AD-07a không liệt kê được lớp đang học/dạy | Duyệt `GET /classes` rồi `GET /classes/{id}/members` hoặc `/teachers` từng lớp (N+1); khóa học: `GET /courses` + `…/enrollments` từng khóa |
| GM-3 | **Không xem được lịch sử đã kết thúc.** `GET /classes/{id}/members` và `/teachers` chỉ trả dòng `active` (ClassMember/phân công đã `ended` không đọc ra được); `GET /courses/{id}/enrollments` thì trả cả đã kết thúc | AD-10b/10c không có phần "Đã rời lớp / đã kết thúc phân công" | Dùng nhật ký audit (`class_member.end`, `teacher_class_assignment.end`) để hiện lịch sử tạm |
| GM-4 | **Danh sách thiếu tên.** `GET /parent-student-relationships` chỉ trả `parent_id`, `student_id`; `GET /courses/{id}/enrollments` chỉ trả `student_id`; `GET /courses` chỉ trả `owner_teacher_id`; `GET /classes` chỉ trả `subject_id` | Mọi bảng liên kết cần tên người/môn | Ghép từ `GET /subjects` (một lần), `GET /students|parents|teachers/{id}` (từng id, cache); tên học sinh của một lớp lấy từ `…/members` |
| GM-5 | **Mời tài khoản không tự gán vai trò.** Action mời chỉ tạo User (không có UserRole) và gửi email tới `email` của hồ sơ; hồ sơ không có email thì không gửi được (BE không chặn) | Sau khi mời, người được mời có thể đăng nhập nhưng chưa có vai trò | Sau khi mời thành công, UI dẫn tiếp tới bước "Gán vai trò" (AD-03); chặn nút Mời khi hồ sơ chưa có email |
| GM-6 | **Không có id của Role.** `DELETE /users/{id}/roles/{roleId}` nhận **id bản ghi Role** (không phải id `user_role`), nhưng `role_history`/`active_roles` chỉ trả mã (`admin`…), `POST …/roles` trả `user_role.id` | Không kết thúc vai trò từ UI được nếu không biết id 4 role | Cần BE trả `role_id` (hoặc cho phép gửi mã role); tạm thời FE phải lấy id 4 role từ cấu hình/seed cố định (dễ vỡ) |
| GM-7 | **Không lọc audit theo lớp/ngày/người.** `GET /audit-logs` chỉ lọc `entity_type`, `entity_id`, `action`; không phân trang, không giới hạn số dòng (G5) | AD-14 và tab lịch sử của lớp tải toàn bộ log | Lọc `correlation_key` (=id lớp hoặc id khóa học) và ngày ở FE trên dữ liệu đã tải |

---

## Tổng quan và thiết lập

### AD-01 — Tổng quan   🟡 (G1)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** mở app buổi sáng và biết ngay hôm nay trung tâm có bao nhiêu lớp/buổi học, có việc gì cần Admin xử lý.
- **Vào từ / đi tới:** trang đầu sau đăng nhập khi vai trò Admin. Bấm thẻ số → danh sách tương ứng (AD-04, AD-07, AD-09). Bấm buổi hôm nay → AD-10e của lớp đó. Bấm mục "cần xử lý" → màn đích tương ứng. Banner thiết lập lần đầu → AD-15.
- **Khối nội dung & dữ liệu hiển thị:**
  1. Lời chào + ngày hôm nay (giờ VN). Nếu có vai trò Teacher: chip chuyển nhóm "Giảng dạy".
  2. Banner "Hoàn tất thiết lập" (chỉ khi AD-15 còn bước chưa xong) với thanh tiến độ.
  3. Thẻ số: học viên, giáo viên, lớp đang hoạt động, lớp đang chuẩn bị. Dự kiến: API tổng quan (G1).
  4. Danh sách "Buổi học hôm nay": giờ, lớp, giáo viên, trạng thái buổi (Dự kiến/Hoàn tất/Hủy), đã điểm danh chưa.
  5. "Cần xử lý": buổi hủy cần rà soát điểm danh (`review_required = true`), lớp đang chuẩn bị chưa có giáo viên, tài khoản được mời nhưng chưa kích hoạt (nếu BE cung cấp), đơn xin phép chờ duyệt, bài chờ chấm (đều theo G1).
  6. Lối tắt: Tạo lớp, Tạo học viên, Xem nhật ký audit.
- **Chức năng & hành động:** chỉ điều hướng; nút "Làm mới" kéo lại dữ liệu (không realtime). Mỗi thẻ/mục là liên kết.
- **Trạng thái cần vẽ:** tải (khung xương từng khối, khối nào xong hiện trước); rỗng toàn hệ thống (chưa có dữ liệu → chỉ hiện banner AD-15, không hiện số 0 vô nghĩa); khối "cần xử lý" rỗng ("Không có việc cần xử lý"); lỗi từng khối; không có quyền.
- **Quy tắc & lỗi nghiệp vụ:** không có lỗi nghiệp vụ riêng.
- **API:** 🟡 thiếu: API tổng quan theo cổng Admin kèm hàng đợi "cần xử lý" (G1). Ghép tạm: `GET /users?role=student&per_page=1` → `meta.total` (đếm **tài khoản** học sinh, không phải hồ sơ: G4); tương tự `role=teacher`; `GET /classes` (đếm theo `status`); buổi hôm nay cần duyệt từng lớp bằng `GET /classes/{id}/lesson-sessions?from=&until=` (N+1, G2), buổi chỉ có `id,starts_at,ends_at,status,review_required`, không có tên lớp (FE tự ghép từ `GET /classes`).
- **Dữ liệu mẫu gợi ý:** 128 học viên · 9 giáo viên · 14 lớp hoạt động · 2 lớp đang chuẩn bị; hôm nay: 17:30–19:00 Toán 9A (cô Hạnh) — Đã hoàn tất; 19:15–20:45 Anh 6C — Dự kiến; cần xử lý: "Buổi Văn 8B ngày 20/10 đã hủy nhưng đã có điểm danh".

### AD-15 — Thiết lập lần đầu (checklist)   🔵

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** Admin mới nhận hệ thống trống, cần biết làm gì trước, gì sau để lớp chạy được.
- **Vào từ / đi tới:** banner ở AD-01 hoặc mục "Bắt đầu" trong menu (ẩn khi đã xong hết). Mỗi bước dẫn tới màn thao tác (AD-08, AD-07b, AD-09, AD-04/05a, AD-10b, AD-10d).
- **Khối nội dung & dữ liệu hiển thị:** danh sách 7 bước có tick tự động, mỗi bước một dòng mô tả "vì sao cần": (1) Tạo môn học · (2) Thêm giáo viên và mời tài khoản · (3) Tạo lớp · (4) Phân công giáo viên cho lớp (lớp chuyển sang Đang hoạt động) · (5) Thêm học viên và xếp vào lớp · (6) Tạo lịch học cho lớp · (7) Mời phụ huynh và liên kết với học sinh. Thanh tiến độ "4/7".
- **Chức năng & hành động:** "Làm bước này" dẫn thẳng tới form; "Ẩn checklist" (lưu ở trình duyệt, tùy chọn); không có thao tác ghi dữ liệu trên màn này.
- **Trạng thái cần vẽ:** tải; tất cả xong ("Hệ thống đã sẵn sàng", nút tới AD-01); lỗi tải một bước (hiện bước ở trạng thái "không rõ", không tick sai).
- **Quy tắc & lỗi nghiệp vụ:** thứ tự là gợi ý, không chặn (BE cho tạo lớp trước khi có giáo viên: lớp chỉ ở Đang chuẩn bị, `DEC-LRN-001`). Bước 3 lưu ý: môn phải đang dùng (`SUBJECT_INACTIVE`).
- **API:** 🔵 ngoài phạm vi đã chốt (cần qua Decision Log). Tính hoàn toàn ở FE từ API sẵn có: `GET /subjects`, `GET /users?role=teacher&per_page=1`, `GET /classes` (có lớp `active` chưa?), `GET /users?role=student&per_page=1`; bước 6, 7 cần kiểm tra từng lớp/liên kết (`GET /classes/{id}/schedules`, `GET /parent-student-relationships`). Hồ sơ chưa có tài khoản không đếm được (G4).
- **Dữ liệu mẫu gợi ý:** ✓ Môn học (3: Toán, Văn, Anh) · ✓ Giáo viên (2) · ✓ Lớp (Toán 9A) · ✗ Phân công giáo viên · ✗ Học viên vào lớp · ✗ Lịch học · ✗ Phụ huynh.

---

## Tài khoản

### AD-02 — Danh sách tài khoản   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** tìm một tài khoản đăng nhập (vd phụ huynh báo "không vào được") và xem nó còn hoạt động không.
- **Vào từ / đi tới:** menu Quản trị › Tài khoản. Bấm dòng → AD-03.
- **Khối nội dung & dữ liệu hiển thị:** mẫu M1. Bộ lọc: ô tìm tên (`name`), ô tìm tên đăng nhập/email (`email`, thực chất khớp `login_identifier`), dropdown vai trò (`role`: 4 giá trị, chỉ khớp vai trò còn hiệu lực), dropdown trạng thái (`status`). Cột (từ `GET /users` → `data[]`): Họ tên (`full_name`, có thể `null` nếu tài khoản chưa nối hồ sơ → hiện "(chưa có hồ sơ)"), Tên đăng nhập (`login_identifier`), Vai trò (`roles[]`, mỗi vai trò một chip), Trạng thái (`status`).
- **Chức năng & hành động:** lọc/tìm/đổi trang; bấm dòng mở chi tiết. Không có nút tạo tài khoản: tài khoản chỉ sinh từ lời mời (AD-05b). Menu "…" mỗi dòng: Xem chi tiết.
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có tài khoản nào. Hãy tạo hồ sơ rồi mời" + nút tới AD-04); rỗng do lọc; lỗi; không có quyền; dòng vô hiệu hóa mờ.
- **Quy tắc & lỗi nghiệp vụ:** không có lỗi nghiệp vụ. `per_page` không có giới hạn trên, FE nên giới hạn (vd tối đa 100).
- **API:** `GET /users?status=&role=&name=&email=&page=&per_page=` → `{data:[{id,login_identifier,status,full_name,roles}], meta:{total,current_page,last_page}}`. Tìm không dấu: xem quy ước.
- **Dữ liệu mẫu gợi ý:** "Nguyễn Thị Hạnh" · hanh.nt@piclass.vn · [Giáo viên][Quản trị viên] · Đang hoạt động; "Trần Quốc Bảo" · 0912345678 · [Phụ huynh] · Đã vô hiệu hóa.

### AD-03 — Chi tiết tài khoản   ✅ (có 🟡 GM-1, GM-6)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** xem một người đang giữ vai trò nào, gán thêm/kết thúc vai trò, khóa hoặc mở lại tài khoản.
- **Vào từ / đi tới:** AD-02 (bấm dòng); sau khi mời tài khoản thành công (AD-05b) dẫn thẳng vào đây để gán vai trò. Đi tới: về AD-02; liên kết sang hồ sơ (AD-05/06a/07a) 🟡 GM-1.
- **Khối nội dung & dữ liệu hiển thị:**
  1. Đầu trang: họ tên (`user.full_name`), tên đăng nhập (`login_identifier`), chip trạng thái (`status`), nút "Vô hiệu hóa" hoặc "Kích hoạt lại" theo trạng thái.
  2. **Vai trò đang giữ** (`user.active_roles[]`): mỗi vai trò một thẻ kèm "Kết thúc vai trò"; nút "Gán vai trò" (dropdown 4 vai trò).
  3. **Lịch sử vai trò** (`user.role_history[]`: `role`, `valid_from`, `valid_to`): bảng thời gian; vai trò đã kết thúc (`valid_to` có giá trị) hiển thị mờ. Không có người gán/kết thúc trong response; xem trong audit (`user_role.assign` / `user_role.end`).
  4. Liên kết "Xem nhật ký thay đổi" → AD-14 lọc `entity_type=User`, `entity_id=<id>`.
  5. Hồ sơ gắn tài khoản (tên, liên kết sang hồ sơ): 🟡 GM-1.
- **Chức năng & hành động:**
  - **Gán vai trò:** chọn vai trò chưa giữ → xác nhận (M4 không bắt lý do) → thêm thẻ. Vai trò đã giữ thì ẩn khỏi dropdown. Nhắc: gán Teacher/Student/Parent chỉ có tác dụng khi người này có hồ sơ tương ứng (cần xác nhận khi dựng: BE không kiểm tra hồ sơ lúc gán).
  - **Kết thúc vai trò:** xác nhận; hiệu lực ngay, người dùng mất quyền ở lần gọi API tiếp theo. 🟡 GM-6: BE cần id của Role.
  - **Vô hiệu hóa:** hộp thoại giải thích "Người này sẽ không đăng nhập được; hồ sơ và lịch sử vẫn giữ nguyên, có thể kích hoạt lại; liên kết/phân công đã kết thúc không tự khôi phục" (`BR-USER-008`). Không bắt lý do. **Kích hoạt lại:** xác nhận đơn giản.
  - Tự vô hiệu hóa chính mình: không bị chặn riêng (trừ `LAST_ADMIN`); nên cảnh báo thêm "bạn sẽ bị đăng xuất".
- **Trạng thái cần vẽ:** tải; lỗi; 404 ("Không tìm thấy tài khoản"); tài khoản đã vô hiệu hóa (banner đầu trang, nút chính đổi thành "Kích hoạt lại"); người chưa có vai trò nào (khối vai trò rỗng + lời mời "Gán vai trò đầu tiên", điển hình ngay sau khi mời: GM-5); đang gửi.
- **Quy tắc & lỗi nghiệp vụ:**
  - `LAST_ADMIN` (409) "Không thể vô hiệu hóa Admin cuối cùng còn quyền quản trị." → hiển thị: "Đây là quản trị viên cuối cùng. Hãy gán quyền quản trị cho người khác trước." Cùng mã khi kết thúc vai trò Admin cuối; BE dùng chung một thông điệp nên FE nên thay câu theo ngữ cảnh ("…kết thúc vai trò Quản trị viên của người cuối cùng…").
  - `ACTIVE_ASSIGNMENTS_EXIST` (422) "Teacher còn phân công lớp đang hoạt động, cần xử lý trước khi vô hiệu hóa." kèm `active_class_ids` (ngoài `error`) → hộp thoại liệt kê tên các lớp (tên lấy từ `GET /classes` theo id), mỗi lớp là liên kết tới AD-10c để kết thúc phân công. Áp dụng cả khi vô hiệu hóa và khi kết thúc vai trò Teacher (`BR-USER-010`).
  - `ROLE_ALREADY_HELD` (409) "User đã có role này." → "Người này đang giữ vai trò này rồi."
  - 404 khi kết thúc một vai trò không còn hiệu lực/không tồn tại.
  - 422 `status`/`role_code` không hợp lệ (không xảy ra nếu dùng dropdown).
- **API:** `GET /users/{id}`; `PATCH /users/{id}` body `{status: "active"|"inactive"}` → `{user:{id,status}}`; `POST /users/{id}/roles` body `{role_code}` → `{user_role:{id,role,valid_from}}`; `DELETE /users/{id}/roles/{roleId}` → 204. 🟡 GM-1 (nối hồ sơ), GM-6 (role id).
- **Dữ liệu mẫu gợi ý:** Nguyễn Thị Hạnh · hanh.nt@piclass.vn · Đang hoạt động · Vai trò: Giáo viên (từ 05/09/2026), Quản trị viên (từ 12/10/2026); lỗi: "Cô Hạnh còn dạy Toán 9A, Văn 8B."

---

## Học viên

### AD-04 — Học viên (danh sách)   🟡 (G4)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** tìm một học sinh, thấy ai chưa có lớp hoặc chưa có tài khoản, thêm học sinh mới.
- **Vào từ / đi tới:** menu Quản trị › Học viên. Bấm dòng → AD-05. Nút "Thêm học viên" → AD-05a.
- **Khối nội dung & dữ liệu hiển thị:** mẫu M1. Bộ lọc dự kiến: tìm theo tên; trạng thái hồ sơ (Đang hoạt động/Ngừng hoạt động); "Tài khoản" (Chưa mời / Đã mời / Đang hoạt động / Đã vô hiệu hóa). Cột dự kiến: Họ tên (`person_profile.full_name`), Email, Số điện thoại, Trạng thái hồ sơ (`status`), Tài khoản, Lớp đang học (số lớp). Đầu danh sách có chip nhắc "Chưa có tài khoản: 23".
- **Chức năng & hành động:** tìm/lọc; "Thêm học viên"; menu "…" mỗi dòng: Xem, Sửa, Mời tạo tài khoản (chỉ khi chưa có tài khoản).
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có học viên. Thêm học viên đầu tiên") có nút; rỗng do lọc; lỗi; không có quyền; dòng "Ngừng hoạt động" mờ.
- **Quy tắc & lỗi nghiệp vụ:** hồ sơ nghi trùng (cùng tên + SĐT) cần Admin tự xác minh, hệ thống không tự gộp (`BR-USER-005`): vẽ cảnh báo mềm ở AD-05a, không chặn.
- **API:** 🟡 thiếu: danh sách hồ sơ học viên có tìm kiếm, phân trang, kèm "có tài khoản?" và số lớp (G4, G9). `GET /users?role=student` chỉ liệt kê học viên **đã có tài khoản**, không có id hồ sơ (GM-1), nên không dùng được làm danh sách chính. Ghép tạm rất hạn chế: gom học viên từ `GET /classes/{id}/members` từng lớp (chỉ gồm học viên đang trong lớp, trả `student_id`, `full_name`, `valid_from`, `valid_to`).
- **Dữ liệu mẫu gợi ý:** Nguyễn Minh Anh · minhanh.parent@gmail.com · 0987 654 321 · Đang hoạt động · Chưa mời · 2 lớp; Lê Gia Hân · — · — · Đang hoạt động · Đang hoạt động · 1 lớp.

### AD-05 — Học viên (chi tiết)   ✅ (khối lớp/phụ huynh/tài khoản có 🟡)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Cấu trúc theo M3.
- **Người dùng muốn làm gì:** xem toàn cảnh một học sinh: thông tin, lớp, phụ huynh, tiến độ; xử lý tài khoản.
- **Vào từ / đi tới:** AD-04; từ roster của lớp (AD-10b); từ liên kết phụ huynh (AD-06a). Đi tới: AD-05a (sửa), AD-05b (mời), AD-06c (liên kết phụ huynh), AD-10 (lớp), AD-14 (nhật ký, lọc `entity_type=Student`).
- **Khối nội dung & dữ liệu hiển thị:**
  1. Đầu trang: họ tên (`student.person_profile.full_name`), chip trạng thái hồ sơ (`student.status`), nút Sửa.
  2. **Thông tin chung:** email, số điện thoại (`person_profile.email/phone`), ghi chú (`student.notes`).
  3. **Tài khoản:** chưa mời → nút "Mời tạo tài khoản"; đã có → tên đăng nhập + trạng thái + liên kết AD-03. 🟡 GM-1.
  4. **Lớp đang học:** tên lớp, môn, ngày vào lớp (`valid_from`). 🟡 GM-2.
  5. **Phụ huynh liên kết:** tên, loại quan hệ (`relationship_type`), trạng thái; nút "Liên kết phụ huynh". Nguồn: `GET /parent-student-relationships?student_id=` → `{id,parent_id,student_id,relationship_type,status}`; tên phụ huynh phải lấy thêm `GET /parents/{parent_id}` (GM-4). Trả cả liên kết đã kết thúc (`status=ended`): hiển thị trong mục "Đã kết thúc".
  6. **Tiến độ:** điểm trung bình, số bài đã chấm, trung bình theo môn (`GET /students/{id}/progress`; chỉ các số tổng hợp, ✅). Biểu đồ theo thời gian: 🟡 G8. Điểm danh: 🟡 G3.
- **Chức năng & hành động:** Sửa (→ AD-05a); Ngừng/khôi phục hoạt động hồ sơ (chip chuyển trạng thái, xác nhận M4 không lý do; **không bị chặn** bởi lớp hay liên kết phụ huynh); Mời tài khoản; Liên kết/Kết thúc liên kết phụ huynh (AD-06c).
- **Trạng thái cần vẽ:** tải theo khối; lỗi theo khối; 404; hồ sơ Ngừng hoạt động (banner: "Học viên ngừng hoạt động. Lịch sử vẫn giữ."); học viên chưa có lớp/phụ huynh/tài khoản (khối rỗng có lời mời hành động).
- **Quy tắc & lỗi nghiệp vụ:** đổi trạng thái hồ sơ không xóa lớp hoặc liên kết đang có (`UpdateStudent` không kiểm tra). Học viên Ngừng hoạt động vẫn có thể còn trong roster: cần xác nhận khi dựng xem `AddClassMember` có chặn thêm học viên inactive không (code hiện không chặn, trong khi `flows/class-management.md` §7 nói "không thêm học viên đã vô hiệu hóa").
- **API:** `GET /students/{id}` → `{student:{id,status,notes,person_profile:{id,full_name,email,phone}}}` (Admin/Teacher); `PATCH /students/{id}` (`status`,`notes`) → `{student:{id,status,notes}}`; `GET /parent-student-relationships?student_id=`; `GET /students/{id}/progress`. 🟡 GM-1, GM-2, GM-4, G3, G8.
- **Dữ liệu mẫu gợi ý:** Nguyễn Minh Anh · minhanh.parent@gmail.com · Lớp: Toán 9A (từ 05/09/2026), Anh 9B; Phụ huynh: Nguyễn Văn Hùng (Bố, đang liên kết); TB 8.4, 12 bài; ghi chú "Học thêm buổi tối thứ Bảy".

### AD-05a — Học viên (tạo / sửa)   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Mẫu M2.
- **Người dùng muốn làm gì:** nhập hồ sơ học sinh mới ngay khi phụ huynh đăng ký, hoặc chỉnh thông tin liên hệ.
- **Vào từ / đi tới:** AD-04 ("Thêm học viên"), AD-05 ("Sửa"), AD-15 bước 5. Lưu xong → AD-05 của học viên vừa tạo, kèm gợi ý "Mời tạo tài khoản" và "Thêm vào lớp".
- **Khối nội dung & dữ liệu hiển thị:** các trường — Họ tên* (`full_name`), Email, Số điện thoại, Ghi chú (`notes`, nhiều dòng). Khi sửa có thêm Trạng thái hồ sơ (`status`). **Sửa tách hai nơi**: họ tên/email/SĐT lưu qua `PATCH /person-profiles/{person_profile.id}`, còn trạng thái/ghi chú qua `PATCH /students/{id}`; FE gọi tuần tự và gộp lỗi, người dùng chỉ thấy một nút Lưu.
- **Chức năng & hành động:** Lưu / Hủy. Cảnh báo mềm khi cùng họ tên hoặc cùng SĐT đã có (cần G4 để so sánh; ghép tạm bằng danh sách đã tải). Email hiển thị chú thích "Cần email nếu muốn mời tạo tài khoản" (GM-5).
- **Trạng thái cần vẽ:** form trống; đang lưu; lỗi 422 theo trường; lỗi mạng giữ nguyên nội dung; thành công.
- **Quy tắc & lỗi nghiệp vụ:** 422 `full_name` bắt buộc → "Vui lòng nhập họ tên."; 422 `email` sai định dạng → "Email không hợp lệ."; email/SĐT không bắt buộc, không kiểm tra trùng. Học viên tự sửa được email/SĐT của mình (CM-05) nhưng không sửa được tên (`BR-USER-006`).
- **API:** `POST /students` body `{full_name, email?, phone?, notes?}` → `{student:{id,status,notes,person_profile}}`; `PATCH /person-profiles/{id}` (`full_name`,`email`,`phone`, trả `{person_profile:{…}}`); `PATCH /students/{id}`.
- **Dữ liệu mẫu gợi ý:** Họ tên "Phạm Thanh Tùng"; email phụ huynh "tung.pt2012@gmail.com"; SĐT "0905 112 233"; ghi chú "Chuyển từ trung tâm khác, yếu hình học".

### AD-05b — Mời tạo tài khoản (hộp thoại dùng chung 3 loại hồ sơ)   ✅ (G11, GM-5)

> **Cập nhật 2026-10-24 (`DEC-USR-014`):** hồ sơ không có email vẫn mời được (bỏ chặn "bổ sung email trước"). Sau khi bấm "Tạo tài khoản", hộp thoại hiện **liên kết kích hoạt để sao chép** (gửi qua Zalo), hiệu lực 7 ngày; có email thì hệ thống gửi thêm một thư. Ô "Tên đăng nhập" không cần là email. Cần BE: trả liên kết trong phản hồi của `POST /person-profiles/{id}/invite` (gap A10).

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** cho một học sinh/phụ huynh/giáo viên đăng nhập được: tạo tài khoản và gửi email để họ tự đặt mật khẩu.
- **Vào từ / đi tới:** nút "Mời tạo tài khoản" ở AD-04/05, AD-06/06a, AD-07/07a. Thành công → đóng hộp thoại, hồ sơ chuyển "Đã mời"; hiện nút "Gán vai trò ngay" → AD-03 (GM-5).
- **Khối nội dung & dữ liệu hiển thị:** tên hồ sơ (chỉ đọc); email nhận lời mời (lấy từ hồ sơ, chỉ đọc; nếu trống thì chặn nút Mời và hiện liên kết "Bổ sung email" sang form sửa); ô **Tên đăng nhập** (`login_identifier`, bắt buộc, duy nhất toàn hệ thống; gợi ý điền sẵn bằng email); dòng giải thích: "Hệ thống gửi email chứa liên kết đặt mật khẩu. **Liên kết hết hạn sau 60 phút.**"
- **Chức năng & hành động:** "Gửi lời mời" (xác nhận một bước; M4 không có lý do). Không có ô chọn vai trò: BE không gán vai trò khi mời (GM-5), nên UI hiển thị bước kế tiếp. Không có audit cho thao tác mời.
- **Trạng thái cần vẽ:** form; đang gửi; thành công ("Đã gửi lời mời tới a***@gmail.com"); tên đăng nhập trùng; hồ sơ đã có tài khoản; thiếu email; email gửi lỗi (BE tạo tài khoản trước, gửi mail sau khi commit, **lỗi gửi mail không hủy tài khoản**: người dùng có thể thấy "đã mời" nhưng không nhận mail → hướng dẫn dùng AD-05c).
- **Quy tắc & lỗi nghiệp vụ:** `ALREADY_INVITED` (409) "Hồ sơ này đã có tài khoản." → hiển thị: "Hồ sơ này đã có tài khoản. Nếu người đó chưa đặt được mật khẩu, xem hướng dẫn gửi lại." (dẫn tới AD-05c). 422 `login_identifier`: bắt buộc / đã tồn tại → "Tên đăng nhập này đã được dùng."
- **API:** `POST /person-profiles/{personProfileId}/invite` body `{login_identifier}` → `{user:{id,login_identifier}}`. Lưu ý id là **id hồ sơ chung** (`person_profile.id`), không phải id học viên/phụ huynh/giáo viên. 🟡 G11: thời hạn token 60 phút dùng chung với đặt lại mật khẩu; nội dung email là mẫu "Đặt lại mật khẩu" (tiêu đề "Đặt lại mật khẩu PiClass"), chưa có mẫu "Chào mừng/Kích hoạt".
- **Dữ liệu mẫu gợi ý:** Tên đăng nhập "tung.pt2012@gmail.com"; lỗi "Tên đăng nhập này đã được dùng".

### AD-05c — Gửi lại lời mời / đặt mật khẩu hộ   🔵🟡 (G11)

> **Cập nhật 2026-10-24:** đổi tên thành **"Tạo lại liên kết"**. Tạo liên kết mới (liên kết cũ hết tác dụng), sao chép gửi qua Zalo, gửi thêm thư nếu có email. "Đặt mật khẩu hộ" dời sang nhóm 5.

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** người được mời báo "link hết hạn" hoặc "không nhận được mail"; Admin cần gửi lại hoặc tự đưa họ một đường vào.
- **Vào từ / đi tới:** AD-03 (tài khoản vừa mời, chưa đăng nhập lần nào), AD-05/06a/07a (khối tài khoản trạng thái "Đã mời"), hoặc từ lỗi `ALREADY_INVITED` ở AD-05b.
- **Khối nội dung & dữ liệu hiển thị:** tên + tên đăng nhập + email nhận; dòng giải thích hạn 60 phút; nút "Gửi lại lời mời" (dự kiến) và nút "Sao chép liên kết đặt mật khẩu" (dự kiến, Admin gửi hộ qua Zalo khi mail không tới).
- **Chức năng & hành động:** hiện **chưa có API** để gửi lại hay lấy liên kết; mời lần hai bị từ chối vì hồ sơ đã có tài khoản. Cách tạm hiện có: người được mời mở "Quên mật khẩu" (CM-02), nhập đúng tên đăng nhập/email để nhận liên kết mới (cùng hạn 60 phút). Hộp thoại nên ghi rõ hướng dẫn này.
- **Trạng thái cần vẽ:** hướng dẫn tạm (trạng thái hiện tại); trạng thái đầy đủ khi BE bổ sung: đã gửi lại, đã sao chép, giới hạn số lần gửi.
- **Quy tắc & lỗi nghiệp vụ:** `ALREADY_INVITED` ở endpoint mời. Lời mời hết hạn, người dùng bấm link thấy lỗi ở CM-03: nên có dòng "Liên hệ quản trị viên để được gửi lại".
- **API:** 🟡 thiếu: "gửi lại lời mời" hoặc "Admin gửi/tạo link đặt mật khẩu hộ", màn kích hoạt riêng với thời hạn dài hơn (G11). Ghép tạm: CM-02 (`POST /auth/password/forgot`).
- **Dữ liệu mẫu gợi ý:** "Trần Văn Nam (giáo viên) được mời lúc 08:10, liên kết hết hạn 09:10 — chưa đăng nhập."

---

## Phụ huynh

### AD-06 — Phụ huynh (danh sách)   🟡 (G4)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Mẫu M1.
- **Người dùng muốn làm gì:** tìm phụ huynh, kiểm tra họ đã liên kết với con chưa.
- **Vào từ / đi tới:** menu Quản trị › Phụ huynh. Dòng → AD-06a; nút "Thêm phụ huynh" → AD-06b.
- **Khối nội dung & dữ liệu hiển thị:** bộ lọc dự kiến: tìm tên; trạng thái hồ sơ; "Tài khoản"; "Có con liên kết / chưa có". Cột: Họ tên, Email, SĐT, Trạng thái hồ sơ, Tài khoản, Số con liên kết.
- **Chức năng & hành động:** như AD-04; menu "…": Xem, Sửa, Mời tạo tài khoản, Liên kết với học sinh.
- **Trạng thái cần vẽ:** như M1; thêm "phụ huynh chưa liên kết con nào" (chip cảnh báo vì họ sẽ không thấy gì khi đăng nhập).
- **Quy tắc & lỗi nghiệp vụ:** không bắt buộc học sinh nào cũng có phụ huynh, nhưng phụ huynh không có liên kết thì cổng Phụ huynh trống (`BR-USER-011`).
- **API:** 🟡 thiếu: danh sách hồ sơ phụ huynh (G4). Chỉ có `GET /users?role=parent` (đã có tài khoản). Số con: ghép từ `GET /parent-student-relationships?parent_id=` (N+1).
- **Dữ liệu mẫu gợi ý:** Nguyễn Văn Hùng · hung.nv@gmail.com · 0987 654 321 · Đang hoạt động · Đang hoạt động · 2 con.

### AD-06a — Phụ huynh (chi tiết)   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Mẫu M3 (một trang, không tab).
- **Người dùng muốn làm gì:** xem phụ huynh này là phụ huynh của những em nào và xử lý tài khoản.
- **Vào từ / đi tới:** AD-06, AD-05 (bấm tên phụ huynh). Đi tới: AD-06b (sửa), AD-05b (mời), AD-06c (liên kết), AD-05 (bấm tên con).
- **Khối nội dung & dữ liệu hiển thị:** đầu trang (họ tên `parent.person_profile.full_name`, chip `parent.status`); thông tin liên hệ (email, SĐT); khối Tài khoản (🟡 GM-1); **Các con liên kết** từ `GET /parent-student-relationships?parent_id=` → `{id,parent_id,student_id,relationship_type,status}`: tên con (GET `/students/{student_id}`, GM-4), loại quan hệ (Bố/Mẹ/Ông bà… do gia đình cung cấp, chữ tự do), trạng thái; liên kết đã kết thúc ở mục riêng.
- **Chức năng & hành động:** Sửa; Ngừng/khôi phục hoạt động; Mời tài khoản; "Liên kết thêm học sinh" và "Kết thúc liên kết" (AD-06c). Gỡ liên kết thu hồi quyền xem của phụ huynh **ngay** (kể cả lịch sử), xem AD-06c.
- **Trạng thái cần vẽ:** như M3; hồ sơ Ngừng hoạt động; chưa có con liên kết (khối rỗng + nút liên kết).
- **Quy tắc & lỗi nghiệp vụ:** đổi trạng thái hồ sơ không ảnh hưởng liên kết. Audit: `parent.status_update`.
- **API:** `GET /parents/{id}` → `{parent:{id,status,person_profile}}`; `PATCH /parents/{id}` (`status`) → `{parent:{id,status}}`; `GET /parent-student-relationships?parent_id=`.
- **Dữ liệu mẫu gợi ý:** Nguyễn Văn Hùng · Bố của Nguyễn Minh Anh (đang liên kết từ 05/09/2026), Mẹ của Nguyễn Gia Bảo (đã kết thúc 01/10/2026: "Con chuyển trung tâm").

### AD-06b — Phụ huynh (tạo / sửa)   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Mẫu M2.
- **Người dùng muốn làm gì:** thêm phụ huynh khi đăng ký cho con, sửa liên hệ.
- **Vào từ / đi tới:** AD-06, AD-06a, AD-05 (khối phụ huynh). Lưu xong → AD-06a, gợi ý "Liên kết với học sinh".
- **Khối nội dung & dữ liệu hiển thị:** Họ tên*, Email, SĐT; khi sửa thêm Trạng thái hồ sơ. Giống AD-05a: tên/email/SĐT qua `/person-profiles/{id}`, trạng thái qua `/parents/{id}`. Không có trường ghi chú.
- **Chức năng & hành động:** Lưu/Hủy; cảnh báo trùng mềm như AD-05a.
- **Trạng thái cần vẽ:** như M2.
- **Quy tắc & lỗi nghiệp vụ:** 422 `full_name` bắt buộc; `email` sai định dạng.
- **API:** `POST /parents` body `{full_name,email?,phone?}` → `{parent:{id,status,person_profile}}`; `PATCH /person-profiles/{id}`; `PATCH /parents/{id}`.
- **Dữ liệu mẫu gợi ý:** "Lê Thị Mai Phương", mai.phuong@gmail.com, 0908 223 344.

### AD-06c — Liên kết phụ huynh–học sinh   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Hộp thoại/ngăn trượt dùng từ AD-05 (khối phụ huynh) và AD-06a (khối con).
- **Người dùng muốn làm gì:** xác nhận "người này là phụ huynh của em này" để phụ huynh xem được dữ liệu của con; hoặc gỡ khi không còn quan hệ.
- **Vào từ / đi tới:** nút "Liên kết phụ huynh" (AD-05) / "Liên kết học sinh" (AD-06a); nút "Kết thúc liên kết" từng dòng.
- **Khối nội dung & dữ liệu hiển thị:** (a) *Tạo:* ô chọn người còn lại (tìm theo tên trong danh sách hồ sơ: G4), ô "Loại quan hệ" (tự do, tùy chọn, gợi ý Bố/Mẹ/Ông/Bà/Người giám hộ); (b) *Kết thúc:* tên hai bên + ô lý do.
- **Chức năng & hành động:** "Liên kết" (không cần lý do); "Kết thúc liên kết" qua M4, **lý do bắt buộc** (`end_reason`); cảnh báo: "Phụ huynh sẽ không xem được dữ liệu của con ngay lập tức, kể cả lịch sử. Muốn khôi phục phải tạo liên kết mới" (`BR-USER-014`). Một học sinh có nhiều phụ huynh và ngược lại (`BR-USER-011`).
- **Trạng thái cần vẽ:** form; đang lưu; trùng liên kết; liên kết đã kết thúc (hiển thị mờ, kèm lý do nếu BE trả — danh sách hiện không trả `end_reason`, cần xác nhận khi dựng).
- **Quy tắc & lỗi nghiệp vụ:** `ALREADY_LINKED` (409) "Liên kết Parent-Student này đang hoạt động." → "Hai người này đã được liên kết." `ALREADY_ENDED` (409) "Liên kết này đã kết thúc trước đó." → "Liên kết này đã kết thúc rồi." (tải lại danh sách). 422: `parent_id`/`student_id` không tồn tại; `end_reason` bắt buộc → "Vui lòng nhập lý do."
- **API:** `POST /parent-student-relationships` body `{parent_id, student_id, relationship_type?}` → `{parent_student_relationship:{id,parent_id,student_id,relationship_type}}`; `DELETE /parent-student-relationships/{id}` body `{end_reason}` → 204; `GET /parent-student-relationships?student_id=&parent_id=`. Audit: `parent_student_relationship.create/end`. 🟡 GM-4 (danh sách thiếu tên).
- **Dữ liệu mẫu gợi ý:** Nguyễn Văn Hùng ↔ Nguyễn Minh Anh, "Bố"; lý do kết thúc: "Gia đình đề nghị gỡ vì con đã nghỉ học."

---

## Giáo viên

### AD-07 — Giáo viên (danh sách)   🟡 (G4)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Mẫu M1.
- **Người dùng muốn làm gì:** xem ai đang dạy, ai chưa có lớp, ai nghỉ.
- **Vào từ / đi tới:** menu Quản trị › Giáo viên. Dòng → AD-07a; "Thêm giáo viên" → AD-07b.
- **Khối nội dung & dữ liệu hiển thị:** bộ lọc: tìm tên; trạng thái hồ sơ; "Tài khoản"; "Có lớp / chưa có lớp". Cột: Họ tên, Chuyên môn (`specialty`), Email, SĐT, Trạng thái, Tài khoản, Số lớp đang dạy.
- **Chức năng & hành động:** như AD-04.
- **Trạng thái cần vẽ:** như M1; giáo viên Ngừng hoạt động hiển thị mờ.
- **Quy tắc & lỗi nghiệp vụ:** Teacher `inactive` không được phân công vào lớp mới (`TEACHER_NOT_ACTIVE`).
- **API:** 🟡 thiếu: danh sách hồ sơ giáo viên (G4); `GET /users?role=teacher` chỉ gồm đã có tài khoản. Số lớp: GM-2.
- **Dữ liệu mẫu gợi ý:** Nguyễn Thị Hạnh · Toán · hanh.nt@piclass.vn · Đang hoạt động · 3 lớp; Trần Văn Nam · Tiếng Anh · — · Ngừng hoạt động · 0 lớp.

### AD-07a — Giáo viên (chi tiết)   ✅ (lớp đang dạy 🟡 GM-2)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** xem giáo viên dạy những lớp nào, trước khi cho nghỉ hoặc đổi người dạy.
- **Vào từ / đi tới:** AD-07, từ tab giáo viên của lớp (AD-10c). Đi tới: AD-07b, AD-05b, AD-10 (bấm lớp).
- **Khối nội dung & dữ liệu hiển thị:** đầu trang (họ tên, chip `teacher.status`, chuyên môn `teacher.specialty`); thông tin liên hệ; khối Tài khoản (GM-1); **Lớp đang dạy** (tên lớp, môn, từ ngày): GM-2.
- **Chức năng & hành động:** Sửa; Ngừng hoạt động hồ sơ (xác nhận M4 không lý do) / Khôi phục; Mời tài khoản. **Chặn:** khi còn phân công thì Ngừng hoạt động thất bại, hộp thoại liệt kê lớp để Admin vào AD-10c kết thúc phân công trước (`BR-USER-010`). Nhắc: vô hiệu hóa *hồ sơ* Teacher khác vô hiệu hóa *tài khoản* (AD-03); cả hai đều bị chặn bởi phân công.
- **Trạng thái cần vẽ:** như M3; hồ sơ Ngừng hoạt động; chưa có lớp.
- **Quy tắc & lỗi nghiệp vụ:** `ACTIVE_ASSIGNMENTS_EXIST` (422) kèm `active_class_ids` → liệt kê lớp, mỗi lớp là liên kết tới AD-10c. Audit: `teacher.status_update`.
- **API:** `GET /teachers/{id}` → `{teacher:{id,status,specialty,person_profile}}`; `PATCH /teachers/{id}` (`status`,`specialty`) → `{teacher:{id,status,specialty}}`. 🟡 GM-1, GM-2.
- **Dữ liệu mẫu gợi ý:** Nguyễn Thị Hạnh · Toán THCS · Lớp: Toán 9A, Toán 8B (từ 05/09/2026); lỗi: "Cô Hạnh còn dạy Toán 9A, Toán 8B. Hãy kết thúc phân công trước."

### AD-07b — Giáo viên (tạo / sửa)   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Mẫu M2.
- **Người dùng muốn làm gì:** thêm giáo viên mới và ghi chuyên môn.
- **Vào từ / đi tới:** AD-07, AD-07a, AD-15 bước 2. Lưu xong → AD-07a, gợi ý "Mời tài khoản" rồi "Phân công vào lớp".
- **Khối nội dung & dữ liệu hiển thị:** Họ tên*, Email, SĐT, Chuyên môn (`specialty`, chữ tự do); khi sửa thêm Trạng thái hồ sơ. Họ tên/email/SĐT qua `/person-profiles/{id}`, trạng thái/chuyên môn qua `/teachers/{id}`.
- **Chức năng & hành động:** Lưu/Hủy; đổi trạng thái sang Ngừng hoạt động có thể thất bại (xem AD-07a).
- **Trạng thái cần vẽ:** như M2.
- **Quy tắc & lỗi nghiệp vụ:** 422 như AD-05a; `ACTIVE_ASSIGNMENTS_EXIST` khi lưu `status=inactive`.
- **API:** `POST /teachers` body `{full_name,email?,phone?,specialty?}` → `{teacher:{…}}`; `PATCH /person-profiles/{id}`; `PATCH /teachers/{id}`.
- **Dữ liệu mẫu gợi ý:** "Vũ Hoàng Long", chuyên môn "Ngữ văn THCS", long.vh@piclass.vn.

---

## Học vụ

### AD-08 — Môn học   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** quản lý danh mục môn (Toán, Văn, Anh…) mà các lớp gắn vào.
- **Vào từ / đi tới:** menu Quản trị › Môn học. Một trang: bảng + ngăn trượt/hộp thoại tạo/sửa (không cần trang chi tiết riêng).
- **Khối nội dung & dữ liệu hiển thị:** mẫu M1 (không phân trang, đủ ít). Cột: Tên (`name`), Mô tả (`description`), Trạng thái (`status`: Đang dùng/Ngừng sử dụng), Số lớp dùng môn (🟡 không có; FE đếm từ `GET /classes` theo `subject_id`). Bộ lọc: trạng thái.
- **Chức năng & hành động:** "Thêm môn học" (Tên*, Mô tả); Sửa (tên, mô tả); **Ngừng sử dụng / Dùng lại** (đổi `status`). Hộp thoại ngừng sử dụng giải thích: "Không thể tạo lớp mới cho môn này; các lớp đã có vẫn chạy và sửa bình thường" (`DEC-LRN-013`). Không có thao tác xóa. `archived_at` được ghi lần đầu ngưng dùng, dùng lại không xóa nó (không hiển thị).
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có môn học. Thêm môn đầu tiên" — chặn tạo lớp); lỗi; môn ngừng sử dụng mờ.
- **Quy tắc & lỗi nghiệp vụ:** 422 `name` bắt buộc → "Vui lòng nhập tên môn." Không kiểm tra trùng tên. Lỗi liên quan: `SUBJECT_INACTIVE` xuất hiện ở AD-09 khi tạo lớp.
- **API:** `GET /subjects` → `{data:[{id,name,description,status}]}`; `POST /subjects` body `{name,description?}` → 201 `{subject:{…}}`; `PATCH /subjects/{id}` (`name`,`description`,`status` = `active|inactive`) → `{subject:{…}}`.
- **Dữ liệu mẫu gợi ý:** Toán · "Chương trình THCS" · Đang dùng · 6 lớp; Tin học · — · Ngừng sử dụng · 0 lớp.

### AD-09 — Lớp học (danh sách)   ✅ (G9 cho cột nâng cao)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** xem mọi lớp, tìm lớp, mở lớp hoặc tạo lớp mới.
- **Vào từ / đi tới:** menu Quản trị › Lớp học; AD-01, AD-15. Dòng → AD-10 (tab Tổng quan). Nút "Tạo lớp" mở hộp thoại tạo (ngay trên màn này).
- **Khối nội dung & dữ liệu hiển thị:** mẫu M1. Bộ lọc: tìm tên; trạng thái lớp (Đang chuẩn bị/Đang hoạt động/Đã lưu trữ); môn (dropdown từ `GET /subjects`). Cột: Tên lớp (`name`), Nhãn (`label`, vd "Khóa 2026", "Ca tối"), Môn (tên, ghép từ `subject_id`), Trạng thái (`status`). Dự kiến thêm (G9): giáo viên, sĩ số, buổi kế tiếp. Mặc định ẩn lớp đã lưu trữ (có công tắc "Hiện lớp đã lưu trữ"); BR-CLASS-003: tên trùng được phép nên cột Nhãn/Môn là thông tin phân biệt, hiện đậm.
- **Chức năng & hành động:** *Tạo lớp* (hộp thoại): Môn* (chỉ liệt kê môn **Đang dùng**), Tên lớp*, Nhãn, Mô tả → lớp mới ở "Đang chuẩn bị", sau khi tạo dẫn vào AD-10c với gợi ý "Phân công giáo viên để lớp bắt đầu hoạt động". Menu "…": Mở, Lưu trữ (xem AD-10a).
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có lớp. Tạo lớp đầu tiên", nút; nếu chưa có môn thì dẫn AD-08); rỗng do lọc; lỗi; lớp đã lưu trữ mờ.
- **Quy tắc & lỗi nghiệp vụ:** `SUBJECT_INACTIVE` (422) "Môn học đã ngừng hoạt động, không thể tạo lớp mới." → "Môn này đã ngừng sử dụng. Chọn môn khác hoặc dùng lại môn này ở Môn học." (với link AD-08). 422 `subject_id`/`name` bắt buộc. Không đặt sĩ số tối đa (`BR-CLASS-003`). Lớp không đổi môn được sau khi tạo (`PATCH` không nhận `subject_id`): ghi chú ở form tạo.
- **API:** `GET /classes` → `{data:[{id,subject_id,name,label,description,status}]}` (không có tên môn/giáo viên/sĩ số: G9); `POST /classes` body `{subject_id,name,label?,description?}` → 201 `{class:{id,subject_id,name,label,status}}`. Lọc theo trạng thái/môn làm ở FE.
- **Dữ liệu mẫu gợi ý:** Toán 9A · Ca tối · Toán · Đang hoạt động; Anh 6C · Khóa hè · Tiếng Anh · Đang chuẩn bị (chưa có giáo viên); Văn 8B · — · Ngữ văn · Đã lưu trữ.

### AD-10 — Chi tiết lớp (khung nhiều tab)   ✅

Tách thành khung + 5 tab: **AD-10a Tổng quan · 10b Học sinh · 10c Giáo viên · 10d Lịch · 10e Buổi học**. Dùng mẫu M3.

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** quản lý một lớp từ A đến Z ở một chỗ: thông tin, ai học, ai dạy, khi nào học.
- **Vào từ / đi tới:** AD-09; AD-01; liên kết từ lỗi `ACTIVE_ASSIGNMENTS_EXIST` (AD-03/07a, mở thẳng tab Giáo viên); từ AD-14 (khi entity là lớp). Đi tới: AD-05, AD-07a (bấm tên), TC-05 (điểm danh từng buổi, dùng chung với Teacher), AD-14.
- **Khối nội dung & dữ liệu hiển thị (đầu trang):** tên lớp (`class.name`), chip trạng thái (`status`), môn (tên từ `subject_id`), nhãn (`label`), nút "Lưu trữ lớp" (khi chưa lưu trữ). Dải tab: Tổng quan · Học sinh (số) · Giáo viên (số) · Lịch · Buổi học. Banner theo trạng thái: *Đang chuẩn bị* = "Lớp chưa có giáo viên phụ trách nên chưa vận hành; phân công giáo viên ở tab Giáo viên"; *Đã lưu trữ* = "Lớp đã lưu trữ, lịch sử vẫn xem được".
- **Chức năng & hành động:** chuyển tab; các hành động thuộc từng tab. Lớp đã lưu trữ: ẩn/vô hiệu nút ghi theo ý đồ nghiệp vụ ("không nhận hoạt động mới"); **cần xác nhận khi dựng**: BE hiện không chặn thêm học viên/giáo viên/lịch vào lớp đã lưu trữ.
- **Trạng thái cần vẽ:** như M3; lớp ở ba trạng thái; 404.
- **Quy tắc & lỗi nghiệp vụ:** trạng thái lớp là **tự động** theo phân công giáo viên: `preparing` → `active` khi có giáo viên đầu tiên; `active` → `preparing` khi giáo viên cuối cùng bị kết thúc phân công; `archived` do Admin đặt tay và **không bao giờ tự khôi phục** (`DEC-LRN-001`). Không có nút "Kích hoạt lớp".
- **API:** `GET /classes/{id}` → `{class:{id,subject_id,name,label,description,status}}`; xem từng tab.
- **Dữ liệu mẫu gợi ý:** Toán 9A · Ca tối · Toán · Đang hoạt động · 18 học sinh · 1 giáo viên.

#### AD-10a — Tab Tổng quan   ✅

- **Khối nội dung & dữ liệu hiển thị:** Thông tin lớp (tên, nhãn, mô tả, môn, trạng thái); số liệu nhanh (số học sinh = độ dài `…/members`, số giáo viên, số buổi sắp tới: ghép từ các tab khác); mục "Lịch sử thay đổi gần đây" (🔵, lọc audit theo lớp: GM-7).
- **Chức năng & hành động:**
  - **Sửa** (tên, nhãn, mô tả; không đổi được môn): form M2 nội tuyến; lưu `PATCH /classes/{id}` → `{class:{id,name,label,description,status}}`.
  - **Lưu trữ lớp:** M4, giải thích "Lớp sẽ không còn hoạt động; lịch sử điểm danh, bài làm, kết quả giữ nguyên và vẫn xem được; **không khôi phục được qua giao diện**" (`BR-CLASS-004, 012`). Gửi `PATCH {status:"archived"}`; BE không nhận `preparing`/`active` (422). Không có ô lý do (BE không nhận). Lưu trữ lần hai không đổi `archived_at`.
  - Trước khi lưu trữ, nên nhắc kiểm tra học sinh còn trong lớp và lịch còn hiệu lực (BE không tự kết thúc): cần xác nhận với nghiệp vụ nên có tự động kết thúc hay không.
- **Trạng thái cần vẽ:** xem mẫu; sửa đang lưu; lớp đã lưu trữ (form chỉ đọc).
- **Quy tắc & lỗi nghiệp vụ:** 422 `name` không được rỗng; 422 `status` chỉ nhận `archived`.
- **API:** `GET/PATCH /classes/{id}`.

#### AD-10b — Tab Học sinh   ✅ (đã rời lớp 🟡 GM-3)

- **Khối nội dung & dữ liệu hiển thị:** bảng M1 thu gọn từ `GET /classes/{id}/members` → `{data:[{student_id,full_name,valid_from,valid_to}]}` (chỉ thành viên **đang hiệu lực**). Cột: Họ tên (bấm → AD-05), Ngày vào lớp (`valid_from`), Ngày hiệu lực đến (`valid_to`, trống = không giới hạn), menu "…". Ô tìm tên (FE). Mục thu gọn "Đã rời lớp" 🟡 GM-3.
- **Chức năng & hành động (chỉ Admin, `BR-CLASS-014`):**
  - **Thêm học sinh:** ô tìm chọn học viên (nguồn: danh sách hồ sơ học viên, G4; loại các em đã trong lớp). Ngày vào lớp = thời điểm bấm (BE không nhận ngày); muốn lùi ngày thì dùng "Sửa thời hạn" sau đó. Có thể thêm nhiều em lần lượt (hộp thoại giữ mở, hiện danh sách vừa thêm).
  - **Kết thúc tư cách thành viên** (rút khỏi lớp/chuyển lớp): M4, **lý do bắt buộc** (`end_reason`); ghi `valid_to` = bây giờ; dữ liệu cũ (điểm danh, bài nộp, kết quả) vẫn thuộc lớp này, **không chuyển sang lớp mới** (`BR-CLASS-004`). Gợi ý "Chuyển lớp": kết thúc ở đây rồi mở hộp thoại thêm ở lớp đích.
  - **Sửa thời hạn thành viên (hồi tố):** M4, `valid_from` và/hoặc `valid_to` (ngày giờ), **lý do bắt buộc** (`reason`). Cảnh báo mạnh: "Sửa ngày vào/ra ảnh hưởng học sinh có được tính vào điểm danh các buổi trước đó hay không. Thay đổi được ghi vào nhật ký kèm lý do" (`BR-CLASS-005`, `BR-USER-017`). Chỉ sửa được thành viên đang hoạt động; hiển thị giá trị cũ → mới trước khi xác nhận. `valid_to` phải sau `valid_from`.
- **Trạng thái cần vẽ:** tải; rỗng ("Lớp chưa có học sinh. Thêm học sinh" + nút); lỗi; đang lưu từng dòng; dòng có `valid_to` ở tương lai (nhãn "Sẽ rời lớp ngày …").
- **Quy tắc & lỗi nghiệp vụ:** `ALREADY_A_MEMBER` (409) "Học viên đã là thành viên đang hoạt động của lớp này." → hiển thị đúng thông điệp, không thêm lại; học viên đã `ended` thì thêm được lại (tạo dòng mới). `VALIDATION_FAILED` 422 "valid_to phải sau valid_from." (không có `details`, hiển thị dưới ô "Đến ngày"). 422 `reason`/`end_reason` bắt buộc → "Vui lòng nhập lý do." 404 khi sửa/kết thúc thành viên không còn hiệu lực. Điểm danh sau này kiểm tra học sinh là thành viên hiệu lực tại giờ bắt đầu buổi (`STUDENT_NOT_ELIGIBLE`) — nên nhắc ở cảnh báo sửa hồi tố.
- **API:** `GET /classes/{id}/members`; `POST /classes/{id}/members` body `{student_id}` → 201 `{class_member:{id,class_id,student_id,valid_from}}`; `DELETE /classes/{id}/members/{studentId}` body `{end_reason}` → 204; `PATCH /classes/{id}/members/{studentId}` body `{valid_from?,valid_to?,reason}` → `{class_member:{id,valid_from,valid_to}}`. Audit: `class_member.add|end|retroactive_update` (kèm `reason`, `before_data/after_data`, `correlation_key` = id lớp). Lưu ý URL dùng `studentId`, không phải id dòng thành viên.
- **Dữ liệu mẫu gợi ý:** Nguyễn Minh Anh · vào 05/09/2026 · không giới hạn; Lê Gia Hân · vào 12/10/2026; lý do: "Chuyển sang lớp Toán 9B theo nguyện vọng phụ huynh."

#### AD-10c — Tab Giáo viên   ✅ (đã kết thúc 🟡 GM-3)

- **Khối nội dung & dữ liệu hiển thị:** bảng từ `GET /classes/{id}/teachers` → `{data:[{teacher_id,full_name,valid_from,valid_to}]}` (chỉ phân công đang hiệu lực): Họ tên (bấm → AD-07a), Phụ trách từ. Không có "giáo viên chính" (`BR-USER-009`: nhiều giáo viên cùng phụ trách).
- **Chức năng & hành động:**
  - **Phân công giáo viên:** chọn từ danh sách giáo viên **đang hoạt động** (loại giáo viên đã phân công; các em Ngừng hoạt động bị làm mờ kèm chú thích, không cho chọn). Thành công thì lớp `preparing` tự chuyển `active`: UI cập nhật chip và hiện thông báo "Lớp đã chuyển sang Đang hoạt động".
  - **Kết thúc phân công:** M4 (BE không nhận lý do). Cảnh báo: "Giáo viên không còn thấy và quản lý lớp này; lịch sử giữ nguyên." Nếu là giáo viên **cuối cùng** thì cảnh báo thêm: "Lớp sẽ trở về Đang chuẩn bị." (tự động). Lịch đang áp dụng không tự hủy: nhắc kiểm tra tab Lịch.
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có giáo viên phụ trách, lớp chưa vận hành" + nút phân công); lỗi; hậu quả đổi trạng thái lớp.
- **Quy tắc & lỗi nghiệp vụ:** `TEACHER_NOT_ACTIVE` (422) "Teacher không còn hoạt động, không thể phân công vào lớp." → "Giáo viên này đã ngừng hoạt động nên không phân công được." `ALREADY_ASSIGNED` (409) "Teacher đã được phân công đang hoạt động cho lớp này." → "Giáo viên đã phụ trách lớp này." 404 khi kết thúc phân công không còn hiệu lực. Cả hai lỗi này cần hiển thị ngay cả khi danh sách đã lọc đúng (chống cuộc đua giữa hai Admin).
- **API:** `GET /classes/{id}/teachers`; `POST /classes/{id}/teachers` body `{teacher_id}` → 201 `{teacher_class_assignment:{id,class_id,teacher_id,valid_from}}`; `DELETE /classes/{id}/teachers/{teacherId}` → 204. Danh sách giáo viên để chọn: G4 (tạm `GET /users?role=teacher`, thiếu `teacher_id`: GM-1). Audit: `teacher_class_assignment.assign|end`.
- **Dữ liệu mẫu gợi ý:** Nguyễn Thị Hạnh · phụ trách từ 05/09/2026; lỗi "Cô Trần Thu Hà đã nghỉ dạy nên không thể phân công."

#### AD-10d — Tab Lịch   ✅

- **Khối nội dung & dữ liệu hiển thị:** danh sách mẫu lịch (`GET /classes/{id}/schedules` → `{data:[{id,status,effective_from,effective_until,local_start_time,duration_minutes,repeat_weekly,weekdays}]}`). Mỗi thẻ: "Thứ Ba, Thứ Năm · 17:30–19:00 (90 phút)" (giờ kết thúc = bắt đầu + `duration_minutes`), "Áp dụng từ 05/09/2026 đến 30/06/2027" (hoặc "không giới hạn"), chip trạng thái (Đang áp dụng/Đã hủy/Đã kết thúc). Lịch không lặp (`repeat_weekly=false`): chỉ một buổi vào ngày `effective_from`.
- **Chức năng & hành động (Admin và Teacher của lớp cùng làm; đặc tả đầy đủ ở file Teacher TC-04):**
  - **Tạo lịch:** ngày bắt đầu*, ngày kết thúc, giờ bắt đầu*, thời lượng (phút)*, lặp hằng tuần* (bật/tắt), chọn các thứ (bắt buộc khi lặp). Tạo xong BE **sinh ngay các buổi cho 4 tuần tới**; hiển thị số buổi tạo và **cảnh báo xung đột** (`warnings[]`: `type = teacher_schedule_conflict`, `conflicting_schedule_id`, `class_id`) dưới dạng hộp vàng "Giáo viên Hạnh trùng lịch với lớp Văn 8B (Thứ Ba 17:30)", **không chặn** (`DEC-SCH-002`).
  - **Sửa lịch:** giờ bắt đầu, thời lượng, các thứ, ngày kết thúc; **lý do bắt buộc**. Nhắc: "Chỉ ảnh hưởng buổi chưa sinh/ tương lai; buổi đã sinh giữ nguyên" (`BR-CLASS-007`).
  - **Hủy lịch:** M4, **lý do bắt buộc** (`cancel_reason`). Buổi đã sinh không bị xóa: nhắc hủy riêng từng buổi ở tab Buổi học nếu cần. Thông báo gửi cho người liên quan (`BR-CLASS-009`).
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có lịch học" + nút Tạo lịch); lỗi; lịch đã hủy mờ (ẩn nút sửa); đang lưu; kết quả tạo với cảnh báo xung đột.
- **Quy tắc & lỗi nghiệp vụ:** 422: `effective_from` bắt buộc; `effective_until` ≥ `effective_from`; `local_start_time` định dạng `HH:mm:ss`; `duration_minutes` ≥ 1; `weekdays` 1–7 bắt buộc khi lặp; `reason`/`cancel_reason` bắt buộc. 403 `FORBIDDEN` "Không có quyền thao tác với lớp này." (Teacher ngoài lớp; Admin không gặp). Lịch chỉ cảnh báo xung đột giáo viên, **không** kiểm tra xung đột phòng (G13 chưa có địa điểm).
- **API:** `GET /classes/{id}/schedules`; `POST /classes/{id}/schedules` → 201 `{schedule:{id,class_id,status,effective_from,local_start_time,duration_minutes},warnings:[…]}`; `PATCH /schedules/{id}` (kèm `reason`) → `{schedule:{id,local_start_time,duration_minutes,weekdays}}`; `POST /schedules/{id}/cancel` body `{cancel_reason}`. Audit: `schedule.update|cancel`.
- **Dữ liệu mẫu gợi ý:** Thứ Ba, Thứ Năm · 17:30 · 90 phút · từ 05/09/2026; cảnh báo xung đột với Văn 8B; lý do sửa: "Dời sang 18:00 vì học sinh tan trường muộn."

#### AD-10e — Tab Buổi học   ✅ (G12, G13)

- **Khối nội dung & dữ liệu hiển thị:** bộ lọc khoảng ngày (`from`, `until`, mặc định tháng hiện tại). Danh sách từ `GET /classes/{id}/lesson-sessions` → `{data:[{id,starts_at,ends_at,status,review_required}]}`: Ngày, Giờ (17:30–19:00), chip trạng thái (Dự kiến/Đã hoàn tất/Đã hủy), cờ "Cần rà soát điểm danh" (`review_required`, hiện ở buổi đã hủy nhưng đã có điểm danh). Nội dung và ghi chú chỉ có ở chi tiết (`GET /lesson-sessions/{id}` → `lesson_content`, `notes`). Không có tên giáo viên/địa điểm trên buổi (G13).
- **Chức năng & hành động:**
  - **Thêm buổi lẻ / buổi bù:** giờ bắt đầu*, giờ kết thúc* (sau giờ bắt đầu), nội dung, ghi chú. Buổi tạo tay không gắn lịch.
  - **Sửa buổi:** giờ, nội dung, ghi chú. **Lý do bắt buộc khi buổi đã hoàn tất**, tùy chọn ở trạng thái khác (`BR-CLASS-011`): ô lý do chuyển sang "bắt buộc" theo trạng thái buổi.
  - **Hủy buổi:** M4, **lý do bắt buộc** (`cancellation_reason`). Hủy buổi đã điểm danh không xóa điểm danh mà gắn `review_required`; thông báo cho học sinh/phụ huynh. Buổi đã hủy: nút điểm danh bị khóa (`SESSION_CANCELLED`, không ngoại lệ Admin).
  - **Hoàn tất buổi:** xác nhận đơn giản.
  - **Mở buổi** → TC-05 (điểm danh, chi tiết buổi) dùng chung với Teacher; Admin được vào mọi lớp.
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có buổi nào trong khoảng này" + nút Thêm buổi hoặc Tạo lịch); lỗi; buổi đã hủy (mờ, hiện lý do trong chi tiết); buổi cần rà soát (chấm cảnh báo); đang lưu.
- **Quy tắc & lỗi nghiệp vụ:** 422 `starts_at`, `ends_at` bắt buộc, `ends_at` sau `starts_at`; 422 `reason` bắt buộc khi sửa buổi `completed`. Giờ không kèm múi giờ được hiểu là giờ Việt Nam. 403 như AD-10d. Lọc `from/until` không validate (giá trị sai gây lỗi 500 phía BE): FE phải chặn định dạng.
- **API:** `GET /classes/{id}/lesson-sessions?from=&until=`; `GET /lesson-sessions/{id}`; `POST /classes/{id}/lesson-sessions` → 201 `{lesson_session:{id,class_id,starts_at,ends_at,status}}`; `PATCH /lesson-sessions/{id}` (`starts_at,ends_at,lesson_content,notes,reason`) → `{lesson_session:{id,status,notes}}`; `POST …/cancel` body `{cancellation_reason}`; `POST …/complete` → `{lesson_session:{id,status}}`. Audit `lesson_session.update|cancel`. 🟡 G12 (`notes` và `review_required` đang trả cả Student/Parent), G13 (địa điểm).
- **Dữ liệu mẫu gợi ý:** Thứ Ba 21/10/2026 · 17:30–19:00 · Đã hoàn tất; Thứ Năm 23/10 · Đã hủy ("Cô Hạnh ốm") · Cần rà soát.

---

## Lịch và khóa học

### AD-11 — Lịch tổng hợp toàn hệ thống   🟡 (G2)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại.
- **Người dùng muốn làm gì:** nhìn cả trung tâm trong một tuần: lớp nào học khi nào, giáo viên nào bận, chỗ nào trống/trùng.
- **Vào từ / đi tới:** menu Quản trị › Lịch; AD-01 (buổi hôm nay). Bấm một buổi → AD-10e của lớp (hoặc ngăn chi tiết buổi → TC-05).
- **Khối nội dung & dữ liệu hiển thị:** chuyển Tuần/Tháng/Ngày; lưới lịch; mỗi buổi là một ô: giờ, tên lớp, giáo viên, trạng thái (Dự kiến/Hoàn tất/Hủy), cờ cần rà soát. Bộ lọc: lớp, giáo viên, môn, ẩn buổi đã hủy. Chú giải màu theo trạng thái (không chọn màu ở đây). Dự kiến đánh dấu các ô trùng giờ cùng giáo viên.
- **Chức năng & hành động:** điều hướng tuần/tháng, lọc, bấm mở buổi; nút "Thêm buổi" chọn lớp rồi vào AD-10e. Chỉ xem và điều hướng, thao tác ghi làm ở AD-10.
- **Trạng thái cần vẽ:** tải; tuần rỗng ("Không có buổi học trong tuần này"); lỗi từng lớp (một lớp lỗi vẫn hiện các lớp còn lại); buổi đã hủy; lớp chưa có lịch.
- **Quy tắc & lỗi nghiệp vụ:** không có lỗi nghiệp vụ riêng. Cảnh báo xung đột chỉ thông tin (`DEC-SCH-002`).
- **API:** 🟡 thiếu: lịch toàn hệ thống theo khoảng ngày, mỗi buổi kèm tên lớp/môn/giáo viên (G2, G9). Ghép tạm: `GET /classes` rồi `GET /classes/{id}/lesson-sessions?from=&until=` từng lớp (N+1, chỉ nên dùng cho vài chục lớp), tên giáo viên từ `GET /classes/{id}/teachers` (thêm một vòng N+1). Buổi trong response không có tên lớp nên FE ghép bằng id lớp đang lặp.
- **Dữ liệu mẫu gợi ý:** Thứ Ba: 17:30–19:00 Toán 9A (cô Hạnh); 17:30–19:00 Văn 8B (cô Hạnh) → cảnh báo trùng giáo viên; 19:15–20:45 Anh 6C (thầy Nam).

### AD-12 — Khóa học (danh sách và chi tiết)   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Một màn gồm danh sách và trang chi tiết (M1, M3).
- **Người dùng muốn làm gì:** quản lý các khóa (nhóm bài đánh giá và học sinh tham gia, kể cả dạy kèm ngoài lớp chính thức) và biết ai đang học khóa nào.
- **Vào từ / đi tới:** menu Quản trị › Khóa học. Dòng → chi tiết (cùng màn). "Ghi danh" → AD-12a.
- **Khối nội dung & dữ liệu hiển thị:**
  - *Danh sách* (`GET /courses`): Tên (`name`), Mô tả, Chủ sở hữu (`owner_teacher_id`: tên giáo viên, hoặc "Dùng chung" khi `null`; GM-4), Trạng thái (`status`: Đang mở/Đã lưu trữ). Bộ lọc: trạng thái, chủ sở hữu. Môn (`subject_id`) chỉ có ở chi tiết, không có ở danh sách.
  - *Chi tiết* (`GET /courses/{id}`): tên, mô tả, môn (có thể trống), chủ sở hữu, trạng thái; khối **Học sinh ghi danh** (xem AD-12a); khối "Bài đánh giá thuộc khóa" 🟡 (nội dung khóa là các bài giao qua khóa, `BR-COURSE-002`; không có endpoint liệt kê bài theo khóa cho Admin ngoài `GET /assessments`, **cần xác nhận khi dựng** khả năng lọc theo khóa).
- **Chức năng & hành động:**
  - **Tạo khóa:** Tên*, Mô tả, Môn (tùy chọn), Chủ sở hữu (Admin chọn giáo viên hoặc để trống = dùng chung; chỉ Admin thấy ô này). Trạng thái ban đầu Đang mở.
  - **Sửa:** tên, mô tả, môn.
  - **Lưu trữ khóa:** M4 (BE không nhận lý do). Giải thích: "Không ghi danh thêm được nữa; xem lịch sử và kết thúc ghi danh vẫn được" (`DEC-LRN-013`). Gửi `PATCH {status:"archived"}`; không có thao tác mở lại qua giao diện.
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có khóa học" + nút Tạo khóa); rỗng do lọc; lỗi; khóa đã lưu trữ (banner, nút Ghi danh bị khóa).
- **Quy tắc & lỗi nghiệp vụ:** 422 `name` bắt buộc; `subject_id`, `owner_teacher_id` phải tồn tại. Teacher cũng tạo được khóa (luôn là chủ sở hữu) và ghi danh học sinh bất kỳ (`BR-COURSE-004`); Admin thấy mọi khóa.
- **API:** `GET /courses` → `{data:[{id,name,description,owner_teacher_id,status}]}`; `GET /courses/{id}` → `{course:{id,name,description,subject_id,owner_teacher_id,status}}`; `POST /courses` body `{name,description?,subject_id?,owner_teacher_id?}` → 201 `{course:{id,name,owner_teacher_id,status}}`; `PATCH /courses/{id}` (`name,description,subject_id,status`) → `{course:{id,name,description,status}}`.
- **Dữ liệu mẫu gợi ý:** "Luyện thi vào 10 — Toán" · Chủ sở hữu: cô Hạnh · Đang mở · 12 học sinh; "Ngữ pháp Anh cơ bản" · Dùng chung · Đã lưu trữ.

### AD-12a — Ghi danh vào khóa học   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. Khối trong chi tiết khóa + hộp thoại.
- **Người dùng muốn làm gì:** đưa một học sinh, hoặc cả một lớp, vào khóa để nhận bài của khóa; hoặc cho em rời khóa.
- **Vào từ / đi tới:** AD-12 (khối Học sinh ghi danh). Tên học sinh → AD-05.
- **Khối nội dung & dữ liệu hiển thị:** bảng từ `GET /courses/{id}/enrollments` → `{data:[{student_id,status,valid_from,valid_to}]}` (Admin/Teacher thấy cả dòng đã kết thúc): Họ tên (ghép từ `GET /students/{id}`: GM-4), Từ ngày, Đến ngày, chip trạng thái (Đang hiệu lực/Đã kết thúc); bộ lọc "Chỉ đang hiệu lực" (mặc định bật). Hộp thoại ghi danh có hai chế độ (tab): **Theo học sinh** (ô tìm một học sinh) và **Theo cả lớp** (chọn một lớp).
- **Chức năng & hành động:**
  - **Ghi danh theo học sinh:** gửi `student_id`; **theo lớp:** gửi `class_id`, BE ghi danh toàn bộ thành viên **đang hiệu lực** của lớp tại thời điểm bấm, bỏ qua em đã ghi danh; học sinh vào lớp sau này **không tự được ghi danh** (cần nhắc rõ: đây là ảnh chụp lúc ghi danh). Chọn đồng thời cả hai bị từ chối.
  - Kết quả hiện "Đã ghi danh 14 học sinh" (từ `created_count`; 0 = "Tất cả đã được ghi danh từ trước", không phải lỗi).
  - **Kết thúc ghi danh:** M4, **lý do bắt buộc** (`end_reason`); em rời khóa nhưng lịch sử giữ.
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có học sinh trong khóa" + nút Ghi danh); lỗi; khóa đã lưu trữ (nút Ghi danh bị khóa, vẫn kết thúc được); kết quả `created_count = 0`.
- **Quy tắc & lỗi nghiệp vụ:** `COURSE_ARCHIVED` (422) "Course đã lưu trữ, không thể ghi danh thêm." → "Khóa học đã lưu trữ nên không ghi danh thêm được." 422: chọn cả hoặc không chọn `student_id`/`class_id`; `end_reason` bắt buộc. 404 khi kết thúc ghi danh không còn hiệu lực. Mỗi ghi danh có một dòng audit riêng (`course_enrollment.enroll|end`, `correlation_key` = id khóa).
- **API:** `GET /courses/{id}/enrollments`; `POST /courses/{id}/enrollments` body `{student_id}` hoặc `{class_id}` → 201 `{created_count}`; `DELETE /courses/{id}/enrollments/{studentId}` body `{end_reason}` → 204. 🟡 GM-4 (tên học sinh).
- **Dữ liệu mẫu gợi ý:** Ghi danh theo lớp "Toán 9A" → "Đã ghi danh 16 học sinh (2 em đã có từ trước)"; kết thúc: "Phụ huynh xin nghỉ khóa luyện thi."

---

## Kiểm soát

### AD-13 — Cấp lại lượt làm bài   ✅

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại (không có ở Teacher).
- **Người dùng muốn làm gì:** học sinh làm bài kiểm tra/thi nhưng gặp sự cố hệ thống (mất mạng, lỗi máy) nên cần một lượt làm mới, không bị tính lượt cũ.
- **Vào từ / đi tới:** từ chi tiết bài nộp (`GET /submissions/{id}`, màn chấm bài TC-13) khi vai trò Admin, nút "Cấp lại lượt làm bài" cạnh thông tin lượt; không có mục menu riêng. Sau khi cấp: về chi tiết bài với lượt mới.
- **Khối nội dung & dữ liệu hiển thị:** hộp thoại M4: học sinh, tên bài, **lượt cần hủy** (số lượt `attempt_number`, trạng thái, giờ nộp, điểm nếu đã chấm), cảnh báo hậu quả; ô lý do. Ghi rõ hậu quả: lượt cũ bị **hủy**, bài nộp của lượt đó chuyển thành không hợp lệ, **kết quả đã công bố bị thu hồi** (Student/Parent không còn thấy và không tính vào tiến độ), học sinh nhận một lượt mới. Mã lượt (`attempt_id`) lấy từ bài nộp (`submission.attempt_id`; cần xác nhận khi dựng vì Resource trả nguyên bản ghi).
- **Chức năng & hành động:** "Cấp lại" (chỉ bật khi đã nhập lý do). Chỉ áp dụng cho **Quiz/Exam**, không áp dụng cho bài tập (Assignment). Sau khi thành công: hiện lượt mới (`attempt.id`, `attempt_number`).
- **Trạng thái cần vẽ:** hộp thoại; đang gửi; thành công; các lỗi nghiệp vụ; nút bị ẩn cho Assignment và với lượt đã hủy.
- **Quy tắc & lỗi nghiệp vụ:** 422 `reason` bắt buộc → "Vui lòng nhập lý do cấp lại." `REISSUE_NOT_APPLICABLE` (409) → "Chỉ cấp lại được cho bài trắc nghiệm hoặc bài thi." `ATTEMPT_ALREADY_CANCELLED` (409) → "Lượt này đã bị hủy hoặc đã được cấp lại trước đó." Cấp lại không kiểm tra giờ đóng bài: mở lượt mới vẫn tuân theo giờ đóng hiện tại của bài (cần xác nhận khi dựng với nghiệp vụ). Audit `attempt.reissue` (kèm `replacement_attempt_id`) và, nếu có, `result.withdraw`.
- **API:** `POST /attempts/{id}/reissue` body `{reason}` → 200 `{attempt:{…toàn bộ cột}}` (lượt thay thế). Dùng `GET /assessments/{id}/submissions`, `GET /submissions/{id}` để chọn lượt.
- **Dữ liệu mẫu gợi ý:** Lê Gia Hân · "Kiểm tra 15 phút — Đại số" · Lượt 1 (đã nộp 19:02) · Lý do: "Mất điện đúng giờ làm bài, đã xác nhận với phụ huynh."

### AD-14 — Nhật ký audit   ✅ (G5, GM-7)

- **Vai trò · responsive:** Admin · ưu tiên web máy tính, vẫn dùng được trên máy tính bảng và điện thoại. (Teacher cũng gọi được endpoint này nhưng chỉ thấy log có `correlation_key` là lớp mình dạy; xem 🔵 ở cuối mục.)
- **Người dùng muốn làm gì:** trả lời "ai đã đổi cái này, khi nào, vì sao" (vd sửa hồi tố thành viên lớp, hủy buổi, công bố điểm).
- **Vào từ / đi tới:** menu Quản trị › Nhật ký; liên kết "Xem nhật ký" từ AD-03, AD-05, AD-10; từ thông báo cho Admin. Từ một dòng, bấm "Mở đối tượng" tới màn tương ứng (User → AD-03, Class entity → AD-10…) khi suy ra được.
- **Khối nội dung & dữ liệu hiển thị:**
  1. Bộ lọc (BE hỗ trợ): Loại đối tượng (`entity_type`), Mã đối tượng (`entity_id`), Hành động (`action`) — dropdown giá trị đã biết (bảng dưới). Bộ lọc **FE tự thêm** trên dữ liệu đã tải: khoảng ngày, người thực hiện, lớp/khóa (`correlation_key`) (GM-7).
  2. Bảng, mới nhất trước: Thời điểm (`occurred_at`, giờ VN), Người thực hiện (`actor_user_id`: chỉ có mã, **chưa có tên**, G5 — FE tự tra tên từ `GET /users/{id}` và cache), Hành động (nhãn tiếng Việt từ `action`), Đối tượng (`entity_type` + `entity_id` rút gọn), Lý do (`reason`, trống nếu không có).
  3. **Ngăn chi tiết** khi bấm dòng: đọc theo thứ tự: *Ai* → *Làm gì* (hành động) → *Với cái gì* (loại + mã đối tượng, `correlation_key` = lớp/khóa liên quan) → *Vì sao* (`reason`) → *Thay đổi*: bảng đối chiếu **Trước** (`before_data`) và **Sau** (`after_data`), mỗi khóa JSON một dòng, khóa đổi giá trị tô nổi, khóa chỉ có ở một bên ghi "(mới)" / "(bỏ)"; nút "Xem JSON gốc". `before_data` có thể `null` (hành động tạo mới) và `after_data` có thể chỉ chứa một phần.
- **Chức năng & hành động:** lọc/xóa lọc; mở/đóng ngăn chi tiết; sao chép mã; không có sửa/xóa log. Xuất file: 🔵 N3 (hoãn).
- **Trạng thái cần vẽ:** tải; rỗng ("Chưa có hoạt động nào" / "Không có bản ghi khớp bộ lọc"); lỗi; không có quyền; nhật ký rất lớn (cảnh báo "đang tải toàn bộ, có thể chậm" cho tới khi có phân trang: G5); dòng thiếu lý do khi hành động thường phải có.
- **Quy tắc & lỗi nghiệp vụ:** không có `error.code` riêng. Bộ lọc giá trị rỗng hoặc `"0"` bị bỏ qua. Bộ lọc không validate nên sai giá trị chỉ cho ra danh sách rỗng, không lỗi 422. Audit và dữ liệu ghi cùng một giao dịch nên log luôn khớp dữ liệu (`BR-AUD-008`); log không được xóa (`BR-AUD-005`).
- **API:** `GET /audit-logs?entity_type=&entity_id=&action=` → `{data:[{id,actor_user_id,action,entity_type,entity_id,occurred_at,before_data,after_data,reason,correlation_key}]}`. 🟡 G5 / GM-7: không phân trang, không lọc ngày/người/lớp, chưa có tên người; `entity_id` không kèm tên đối tượng.
- **Bảng hành động đang được ghi (nhãn gợi ý):**

| `action` | `entity_type` | Nhãn |
|---|---|---|
| `user.status_update` | User | Đổi trạng thái tài khoản |
| `user_role.assign` / `user_role.end` | UserRole | Gán / kết thúc vai trò |
| `student.status_update` · `parent.status_update` · `teacher.status_update` | Student · ParentProfile · Teacher | Đổi trạng thái hồ sơ |
| `parent_student_relationship.create` / `.end` | ParentStudentRelationship | Liên kết / kết thúc liên kết phụ huynh–học sinh |
| `class_member.add` / `.end` / `.retroactive_update` | ClassMember | Thêm / kết thúc / sửa hồi tố thành viên lớp (hồi tố luôn có lý do và trước/sau) |
| `teacher_class_assignment.assign` / `.end` | TeacherClassAssignment | Phân công / kết thúc phân công giáo viên |
| `schedule.update` / `schedule.cancel` | Schedule | Sửa / hủy lịch |
| `lesson_session.update` / `lesson_session.cancel` | LessonSession | Sửa / hủy buổi học |
| `attendance_record.create` / `.update` | AttendanceRecord | Tạo / sửa điểm danh |
| `absence_explanation.review` | AbsenceExplanation | Duyệt đơn xin phép |
| `course_enrollment.enroll` / `.end` | CourseEnrollment | Ghi danh / kết thúc ghi danh |
| `assessment.publish` / `.close` / `.reopen` / `.cancel` | Assessment | Phát hành / đóng / mở lại / hủy bài |
| `attempt.submit` / `attempt.reissue` | Attempt | Nộp bài / cấp lại lượt làm bài |
| `result.publish` / `result.withdraw` | Result | Công bố / thu hồi kết quả |
| `grade.create` / `grade.update` | Grade | Chấm / sửa điểm |

- **Dữ liệu mẫu gợi ý:** 20/10/2026 09:12 · Admin (mã `8f2c…`) · Sửa hồi tố thành viên lớp · ClassMember `a41b…` · lớp `c9d0…` · Lý do "Phụ huynh báo con đã vào lớp từ 01/10" · Trước `valid_from: 05/10/2026` → Sau `valid_from: 01/10/2026`.
- **🔵 Mở rộng đề xuất (Teacher):** Teacher đọc được log của lớp mình qua cùng endpoint (`BR-AUD-004`); có thể tái dùng khối "Ngăn chi tiết" thành tab "Lịch sử thay đổi" ở buổi học/bài chấm của Teacher. Không thuộc cổng Admin, ghi để thống nhất thành phần.

---

## Thay đổi mã màn hình so với `screen-inventory.md`

| Mã mới | Loại | Lý do |
|---|---|---|
| AD-10a…AD-10e | Tách AD-10 | Mỗi tab có dữ liệu, hành động và lỗi riêng; AD-10 giữ vai trò khung |
| AD-05a | Tách AD-05 | Form tạo/sửa học viên tách khỏi trang chi tiết |
| AD-05b | Thêm (✅) | Hộp thoại mời tạo tài khoản, dùng chung cho 3 loại hồ sơ |
| AD-05c | Thêm (🔵🟡 G11) | Gửi lại lời mời / đặt mật khẩu hộ |
| AD-06a, 06b, 06c | Tách AD-06 | Chi tiết, form, liên kết phụ huynh–học sinh |
| AD-07a, 07b | Tách AD-07 | Chi tiết và form giáo viên |
| AD-12a | Tách AD-12 | Ghi danh theo học sinh hoặc theo lớp |
| AD-15 | Thêm (🔵) | Checklist thiết lập lần đầu |
