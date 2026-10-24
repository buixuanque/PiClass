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

Mỗi màn hỏi đúng ba câu, ghi kết quả ngay vào đặc tả màn (`admin.md`...) và Decision Log khi là quy tắc mới:

1. Ai dùng, bao lâu một lần?
2. Bỏ màn này thì trung tâm có làm tay được không?
3. Giữ, đơn giản hóa hay tạm bỏ?

Mỗi màn cần giải thích cả hướng kỹ thuật lẫn nghiệp vụ khi hỏi (theo `CLAUDE.md`).

**Thứ tự: theo luồng ngày đầu của một trung tâm, không theo thứ tự menu.**

- [x] Nhóm 0 chốt 2026-10-24 (`DEC-USR-013`, `DEC-USR-014`, `DEC-USR-015`): học sinh đều có tài khoản; `login_identifier` là tên đăng nhập, Admin sao chép liên kết kích hoạt gửi qua Zalo; mỗi tài khoản một vai trò (`DEC-USR-015`), bỏ màn chọn / đổi vai trò (CM-04) và công tắc. Cần sửa mockup: nhãn ô đăng nhập thành "Tên đăng nhập" (CM-01 đến CM-03), màn mời tài khoản hiện liên kết để sao chép (AD-05b, AD-05c).
- [x] 1. Tạo giáo viên (AD-07, AD-07a, AD-07b) — bàn xong 2026-10-24: giữ đủ bộ lọc AD-07 như mockup; giữ "lớp đang dạy" ở AD-07a, cần API mới `GET /teachers/{id}/classes` (A8/GM-2) thay cho N+1; cần API hồ sơ giáo viên riêng không phụ thuộc tài khoản (A7/G4). Mockup không cần sửa. **BE xong** (`piclass-api` branch `feat/teacher-list-and-classes`, chưa PR): `GET /teachers` (danh sách, lọc `name`/`status`/`account`/`has_classes`, kèm `active_classes_count`) và `GET /teachers/{teacher}/classes`. Spec/plan: `superpowers/specs/2026-10-24-teacher-list-and-classes-api-design.md`, `superpowers/plans/2026-10-24-be-teacher-list-and-classes.md`. Tiếp theo: ghép FE (bước 4) cho AD-07/AD-07a.
- [ ] 2. Môn học và tạo lớp, gán giáo viên (AD-08, AD-09, AD-10)
- [ ] 3. Tạo học sinh, thêm vào lớp (AD-04, AD-05, AD-05a)
- [ ] 4. Tạo phụ huynh và liên kết với con (AD-06, AD-06a, AD-06b, AD-06c)
- [ ] 5. Mời tài khoản, gửi lại lời mời, tài khoản và vai trò (AD-05b, AD-05c, AD-02, AD-03)
- [ ] 6. Lịch học và buổi học (AD-10 tab Lịch và Buổi học, AD-11)
- [ ] 7. Tổng quan Admin (AD-01) và thiết lập lần đầu (AD-15)
- [ ] 8. Khóa học và ghi danh (AD-12, AD-12a), nhật ký audit (AD-14), cấp lại lượt làm (AD-13): phần này nhiều khả năng đơn giản hóa hoặc tạm bỏ, bàn cuối cùng
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
