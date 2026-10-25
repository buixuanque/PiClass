# Kế hoạch mockup theo vai trò

File này là nơi theo dõi tiến độ mockup UI. Làm việc trên nhiều máy thì chỉ cần đọc file này, cập nhật ô đánh dấu và ghi chú khi xong mỗi nhóm.

## Quy ước chung

- UI của `piclass-mockup/design-system/` (design system v2, chốt 2026-10-23, xem `design-system/CHANGES.md`) là base đã chốt: chỉ thêm hoặc sửa, không vẽ lại từ đầu. Web responsive (xem `prompts/00-nen.md`). Mockup làm trong Claude Code, không dùng Claude Design.
- Màn mới dùng token (`var(--...)`) và component trong `_ds_bundle.js`, không để màu hoặc cỡ chữ rời; chữ tối thiểu 12px; viền ô nhập dùng `--border-control`. Giữ ngoại lệ nút primary md/lg 4.42:1.
- Mỗi vai trò một file `PiClass <Vai trò>.dc.html`. Admin là mẫu cho cách làm: dữ liệu mẫu nằm trong state của component, thao tác sửa dữ liệu thật trong trang, bấm qua lại được.
- Sau mỗi lần sửa file `.dc.html`, chạy lại `python3 tools/make_standalone.py` (trong `piclass-mockup`) để sinh `design-system/standalone/*.html`, rồi commit cả hai.
- Mỗi nhóm xong phải kiểm tra: `node --check` trên phần script, so khóa `{{binding}}` với `renderVals`, chạy kịch bản bấm thử bằng Chrome headless, chụp màn hình ở 500, 900 và 1300px.
- Đặc tả nguồn: `student-parent.md`, `teacher-van-hanh.md`, `teacher-bai-danh-gia.md` (cùng thư mục), khoảng trống API ở `api-gaps.md`.
- Dữ liệu bài nộp và bài làm dùng chung giữa Học sinh, Phụ huynh và Giáo viên. Dùng cùng bộ dữ liệu mẫu (lớp "Toán 9A", cùng học sinh, cùng bài) để ba file khớp nhau.

## Làm việc trên máy khác

```bash
cd PiClass && git checkout main && git pull          # repo docs (file này)
cd piclass-mockup && git checkout main && git pull   # repo mockup, là repo riêng
```

Repo mockup: `buixuanque/piclass-mockup`. Mở bằng double-click file trong `design-system/standalone/`. Khi làm xong một nhóm: commit repo mockup trước, rồi cập nhật ô bên dưới và `docs/progress.md` ở repo docs. Theo quy ước chung, mỗi repo làm trên branch riêng rồi mở PR khi người dùng yêu cầu.

## Đặt lại kế hoạch (2026-10-24)

Kế hoạch cũ (nhóm 0 đến 6, làm đủ mọi chức năng cho cả bốn vai trò) **đã thay**. Bản cũ còn trong lịch sử git (commit `fa6b719`, file này). Lý do: sau lượt review phạm vi (`../../research/2026-10-24-review-pham-vi-v1.md`, `../../research/2026-10-24-api-v1-toi-thieu.md`), mục tiêu V1 là **chức năng cơ bản nhất của một trung tâm**; mở rộng sau khi vận hành thật và hỏi giáo viên.

### Quyết định nền (user chốt 2026-10-24)

- Hướng A: mỗi trung tâm một bản triển khai riêng (`ARCH-33`).
- Giữ Course; giữ 9 loại câu hỏi; thông báo chỉ trong ứng dụng; chưa nhập học sinh hàng loạt.

### Hiện trạng mockup (làm sẵn, chưa rà lại nghiệp vụ)

Mockup dưới đây là **base UI đã dựng**, không phải "đã xong". Từng màn phải được rà lại theo phạm vi tối thiểu ở mục sau; màn nào chưa rà thì chưa coi là chốt.

| File | Phạm vi đã dựng | Review UI/UX |
|---|---|---|
| `PiClass Quản trị.dc.html` | 10 mục menu, khoảng 29 màn | Đã có (design system v2) |
| `PiClass Giáo viên.dc.html` | Lớp, điểm danh, đơn xin phép, khóa học, tiến độ, ngân hàng câu hỏi, soạn câu, soạn và giao bài, chi tiết bài, bài nộp, chấm bài | Đã review và sửa (nhóm 0 đến 4); nhóm chấm bài chưa review |
| `PiClass Học sinh.dc.html` | Làm bài, kết quả, tiến độ, khóa học | Đã có báo cáo `reviews/2026-10-23-hoc-sinh-uiux.md` |
| `PiClass Phụ huynh.dc.html` | Chọn con, tiến độ, đơn xin phép, xem kết quả | Đã review và sửa |
| `PiClass Màn chung.dc.html` | Đăng nhập, quên và đặt mật khẩu, kích hoạt, đổi vai trò, hồ sơ, thông báo, lỗi, tệp, khung điều hướng | Đã có `reviews/2026-10-24-man-chung-uiux.md` |

Chưa làm từ kế hoạch cũ: đồng bộ trực tiếp kết quả chấm sang Học sinh và Phụ huynh (ba file riêng, cần kho dữ liệu chung), thử khổ 390px thật, đồng bộ `prompts/admin.md`.

### Quy trình mới: Admin trước, bàn nghiệp vụ tại từng màn

Mỗi màn tự đề xuất giữ/đơn giản hóa/tạm bỏ theo tinh thần V1 tối thiểu, ghi kết quả ngay vào đặc tả màn (`admin.md`...) và Decision Log khi là quy tắc mới. Giải thích cả hướng kỹ thuật lẫn nghiệp vụ khi đề xuất (theo `CLAUDE.md`).

Mỗi nhóm có thêm dòng **API ghép** — các mã A/B/C từ `api-gaps.md` thuộc phạm vi Admin của nhóm đó, lấy từ cột "Màn hình" trong file đó. Làm cùng lúc với UI của nhóm (đúng bước 3 `working-flow.md`), không tách thành danh sách BE riêng. Mã nào dùng chung nhiều cổng (Giáo viên/Học sinh/Phụ huynh) thì chỉ phần Admin được ghép ở đây; phần còn lại chờ tới lượt cổng đó, xem `progress.md` mục "Việc tiếp theo".

**Thứ tự: theo luồng ngày đầu của một trung tâm, không theo thứ tự menu.**

- [x] Nhóm 0 chốt 2026-10-24 (`DEC-USR-013`, `DEC-USR-014`, `DEC-USR-015`): học sinh đều có tài khoản; `login_identifier` là tên đăng nhập, Admin sao chép liên kết kích hoạt gửi qua Zalo; mỗi tài khoản một vai trò (`DEC-USR-015`), bỏ màn chọn / đổi vai trò (CM-04) và công tắc. Cần sửa mockup: nhãn ô đăng nhập thành "Tên đăng nhập" (CM-01 đến CM-03), màn mời tài khoản hiện liên kết để sao chép (AD-05b, AD-05c).
- [x] 1. Tạo giáo viên (AD-07, AD-07a, AD-07b) — bàn xong 2026-10-24: giữ đủ bộ lọc AD-07 như mockup; giữ "lớp đang dạy" ở AD-07a, cần API mới `GET /teachers/{id}/classes` (A8/GM-2) thay cho N+1; cần API hồ sơ giáo viên riêng không phụ thuộc tài khoản (A7/G4). Mockup không cần sửa. **BE xong** (`piclass-api` branch `feat/teacher-list-and-classes`, chưa PR): `GET /teachers` (danh sách, lọc `name`/`status`/`account`/`has_classes`, kèm `active_classes_count`) và `GET /teachers/{teacher}/classes`. Spec/plan: `superpowers/specs/2026-10-24-teacher-list-and-classes-api-design.md`, `superpowers/plans/2026-10-24-be-teacher-list-and-classes.md`. **FE xong** (`piclass-web` branch `feat/admin-teacher-screens`, chưa PR): 3 màn AD-07/07a/07b, cùng các primitive `components/ui` đầu tiên (Button, Card, Input, Select, Tabs, Badge, Avatar, StatTile, Dialog, Skeleton) port từ design system v2. Đã thử qua `curl` với cookie phiên thật qua `piclass-infra` (đăng nhập, gọi `GET /teachers` ra dữ liệu thật, 4 route trả 200) — **chưa thử trên trình duyệt thật** (không có công cụ trình duyệt trong phiên làm việc này).
  - **Lệch có chủ ý so với mockup** (ghi lại để không hiểu nhầm là thiếu sót): bỏ nút "Mời tạo tài khoản" và tab "Tài khoản" ở AD-07a (để dành nhóm 3 — mockup-plan mục 3, sau khi gộp 2026-10-25); bỏ cột "Học sinh" ở bảng lớp đang dạy và stat "Học sinh phụ trách" (chưa có API đếm học sinh theo giáo viên); bỏ nút "Xem nhật ký thay đổi" (audit log chưa có màn); bỏ cảnh báo trùng tên/SĐT khi tạo hồ sơ (`form.hasDup`, chỉ là tiện ích hiển thị, không phải rule bắt buộc).
  - **API ghép** (api-gaps.md): A7 (`GET /teachers`), A8 (`GET /teachers/{id}/classes`) — đã xong, dùng để thay N+1.
- [x] 2. Môn học và tạo lớp, gán giáo viên (AD-08, AD-09, AD-10) — bàn xong 2026-10-25. Spec AD-08/AD-09/AD-10(a-e) ở `admin.md` đã đủ chi tiết từ trước, chỉ cần chốt 3 mục API ghép:
  - **C3 — làm luôn:** `GET /classes` trả kèm `teacher_names`, `student_count`, `next_session_at` (đóng G9 ở AD-09); tránh N+1 khi Admin rà danh sách lớp hằng ngày.
  - **C12 — tạm bỏ ở V1:** không thêm địa điểm/phòng học cho Class/LessonSession đợt này; Admin tạm ghi phòng vào ô Nhãn/Mô tả, quyết lại sau khi hỏi giáo viên vận hành thật (đồng bộ với "nên có, làm sau" ở `research/2026-10-24-api-v1-toi-thieu.md`).
  - **C7 — làm cơ chế chung:** viết hàm tìm không dấu (unaccent) một lần ở BE, áp cho `/classes` trước (và `/teachers` — đã làm), tái dùng cho `/subjects`, `/students`, `/parents` ở nhóm 3 — không viết riêng từng danh sách.
- [ ] 3. Tài khoản, Học sinh, Phụ huynh (gộp nhóm 3+4+5 cũ, chốt 2026-10-25) — ba việc này gắn chặt nhau (tạo hồ sơ → liên kết → mời tài khoản dùng chung một hộp thoại AD-05b), gộp thành một nhóm để bàn và build một lần, tránh dở dang. Màn: AD-04/05/05a (học sinh), AD-06/06a/06b/06c (phụ huynh), AD-05b/05c/AD-02/AD-03 (mời tài khoản, tài khoản, vai trò). Thứ tự trong nhóm: tạo học sinh → tạo phụ huynh và liên kết → mời tài khoản (dùng chung cho cả hai).
  - **Mở khóa luôn phần đang để trống ở nhóm 2:** AD-10b (tab Học sinh của AD-10) đang ẩn/disable nút "Thêm học sinh" vì cần `GET /students` tìm kiếm — nhóm này làm xong thì nối nút đó, không phải sửa lại cấu trúc tab đã build.
  - **API ghép — BE xong 2026-10-25** (`piclass-api` nhánh `feat/invite-auto-assign-role`, chưa PR): A7 phần Student/Parent (`GET /students` cho AD-04, `GET /parents` cho AD-06 — cùng khuôn mẫu `GET /teachers`: lọc `status`/`account`/`name`, kèm `classes: [{id,name}]` cho Student và `children_count` cho Parent); A8 phần học viên (lớp đang học, nay có cả `id` để link, trong `classes`); A2/GM-6 xong — `GET /users/{id}` trả `active_roles: [{role_id,role}]`, mở khóa hẳn "Kết thúc vai trò" ở AD-03 (không cần workaround nào); GM-1 phần "Tài khoản" ở màn chi tiết xong — `GET /students|parents|teachers/{id}` giờ trả thẳng `account`, không cần gọi lại API danh sách để dò; C7 (`UnaccentSearch` tái dùng từ nhóm 2, áp cho cả hai danh sách). C3 phần liên kết phụ huynh–học sinh — **xong 2026-10-25**: `GET /parent-student-relationships` trả kèm `parent_name`/`student_name`, hết N+1 ở AD-05 "Phụ huynh liên kết" và AD-06c. **Còn mở, chưa làm:** A9 (điểm danh theo học sinh, nếu AD-05 quyết định hiển thị) — chờ chốt nghiệp vụ, không chặn FE.
  - **Rà lại mockup 2026-10-25 (sau khi user phát hiện FE lệch mockup ở AD-02/03/05b/05c/06c):** đã sửa — gộp 1 ô tìm ở AD-02, nút "Mời" ở 3 bảng danh sách (cần `person_profile_id`, đã thêm), tab thật cho AD-05/05a.../06a/07a (plan cũ đọc nhầm admin.md thành "một trang không tab"), nút "Xem nhật ký thay đổi" khóa ở tab Thông tin, nút tắt "Mời tạo tài khoản" ở khung đầu trang (hero) của AD-05/06a/07a (component chung `PersonHeroActions`, đối chiếu lại code 2026-10-25 thấy đã làm xong trước đó, mục này từng ghi nhầm thành "chưa làm"). **Còn 1 việc chưa làm, chốt làm theo mockup 2026-10-25:** tab "Lớp học" của AD-05 nên là bảng đầy đủ (Lớp | Môn | Giáo viên | Vào lớp từ) thay vì chip tên — cần BE trả thêm tên môn/giáo viên theo từng lớp của học sinh (hiện `classes` chỉ có `id`/`name`).
  - **Quyết định đã chốt:** #13 — BE tự gán vai trò ngay khi mời theo loại hồ sơ (`DEC-USR-018`, thay cho A2/nút "Gán vai trò ngay" ở AD-03).
  - **Quyết định mở còn lại:** #4 (nhận AD-05c "gửi lại lời mời" không), Q6 (khung điều hướng cho tài khoản vừa Admin vừa Teacher).
- [ ] 4. Lịch học và buổi học (AD-10 tab Lịch và Buổi học, AD-11)
  - **API ghép:** A6 (lịch theo người, cho AD-11), C12 (địa điểm buổi học), C11 (`cancellation_reason`, `lesson_content`, `schedule_id` trong response buổi học, nếu AD-11 hiển thị).
  - **Quyết định mở:** #9 (hủy lịch lặp có hủy hàng loạt buổi đã sinh không), #12 (quy ước `weekdays`, `repeat_weekly = false` có sinh buổi không).
- [ ] 5. Tổng quan Admin (AD-01) và thiết lập lần đầu (AD-15)
  - **API ghép:** C1 (API tổng quan theo cổng, hàng đợi cần xử lý — cần bàn có áp dụng cho Admin không hay chỉ Giáo viên).
  - **Quyết định mở:** #4 (nhận AD-15 hay bỏ — `api-v1-toi-thieu.md` đề xuất bỏ, Admin đầu tiên tạo bằng seed/lệnh).
- [ ] 6. Khóa học và ghi danh (AD-12, AD-12a), nhật ký audit (AD-14), cấp lại lượt làm (AD-13): phần này nhiều khả năng đơn giản hóa hoặc tạm bỏ, bàn cuối cùng
  - **API ghép:** C2 (phân trang, audit trả tên người/đối tượng, cho AD-14), C3 (khóa học/ghi danh làm giàu danh sách tên), C15 (cấp lại lượt làm — Admin reissue, hiện chỉ Quiz/Exam).
  - **Lưu ý:** `api-v1-toi-thieu.md` đề xuất tạm bỏ giao diện audit đầy đủ và cấp lại lượt làm do sự cố (Admin xử tay được) — chốt cuối cùng tại nhóm này là giữ tối thiểu hay bỏ hẳn.
- [ ] Tổng hợp danh sách "giữ / đơn giản / tạm bỏ" của cổng Admin, rồi sang nhóm dưới

### Việc cần bàn tiếp sau Admin (từ ghi chú review của user, 2026-10-24)

Bàn một lần cho mọi cổng, không bàn riêng từng cổng.

| Chủ đề | Cổng liên quan | Ghi chú |
|---|---|---|
| **Tiến độ** | Giáo viên, Phụ huynh, Học sinh | Một quy tắc tính dùng chung (tính từ điểm đã công bố, theo môn, thang /10); chỉ khác phạm vi xem |
| **Việc cần xử lý ở màn tổng quan** | Giáo viên | Hàng đợi: bài chờ chấm, đơn chờ duyệt, buổi chưa điểm danh (`api-gaps.md` C1) |
| **Bài đánh giá** | Giáo viên | Cần trao đổi lại chức năng; gồm vòng đời, đối tượng, phiên bản, công bố điểm |
| **Ngân hàng câu hỏi** | Giáo viên | Cần trao đổi lại; gồm 9 loại, chủ đề, lưu trữ |
| **Khóa học** | Giáo viên, Học sinh, Admin | Giữ, nhưng cần trao đổi lại chức năng và phạm vi quyền |
| **UX bài tập, bài kiểm tra, kết quả** | Học sinh | Cải thiện luồng làm bài và xem kết quả |

### Sau cùng

- [ ] Đồng bộ kết quả chấm giữa Giáo viên, Học sinh, Phụ huynh (kho dữ liệu chung) sau khi chốt các màn trên
- [ ] Thử khổ 390px trên điện thoại thật hoặc devtools (Chrome headless không xuống dưới khoảng 500px)
- [ ] Đồng bộ lại `prompts/admin.md` với mockup Admin cuối
