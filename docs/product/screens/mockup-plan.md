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

Repo mockup: `buixuanque/piclass-mockup`. Mở bằng double-click file trong `design-system/standalone/`. Khi làm xong một nhóm: commit repo mockup trước, rồi cập nhật ô bên dưới và `docs/STATE.md` ở repo docs. Theo quy ước chung, mỗi repo làm trên branch riêng rồi mở PR khi người dùng yêu cầu.

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

Mỗi nhóm có thêm dòng **API ghép** — các mã A/B/C từ `api-gaps.md` thuộc phạm vi Admin của nhóm đó, lấy từ cột "Màn hình" trong file đó. Làm cùng lúc với UI của nhóm (đúng bước 3 `working-flow.md`), không tách thành danh sách BE riêng. Mã nào dùng chung nhiều cổng (Giáo viên/Học sinh/Phụ huynh) thì chỉ phần Admin được ghép ở đây; phần còn lại chờ tới lượt cổng đó, xem `STATE.md` và `BACKLOG.md`.

**Thứ tự: theo luồng ngày đầu của một trung tâm, không theo thứ tự menu.**

- [x] Nhóm 0 chốt 2026-10-24 (`DEC-USR-013`, `DEC-USR-014`, `DEC-USR-015`): học sinh đều có tài khoản; `login_identifier` là tên đăng nhập, Admin sao chép liên kết kích hoạt gửi qua Zalo; mỗi tài khoản một vai trò (`DEC-USR-015`), bỏ màn chọn / đổi vai trò (CM-04) và công tắc. Cần sửa mockup: nhãn ô đăng nhập thành "Tên đăng nhập" (CM-01 đến CM-03), màn mời tài khoản hiện liên kết để sao chép (AD-05b, AD-05c).
- [x] 1. Tạo giáo viên (AD-07, AD-07a, AD-07b) — bàn xong 2026-10-24: giữ đủ bộ lọc AD-07 như mockup; giữ "lớp đang dạy" ở AD-07a, cần API mới `GET /teachers/{id}/classes` (A8/GM-2) thay cho N+1; cần API hồ sơ giáo viên riêng không phụ thuộc tài khoản (A7/G4). Mockup không cần sửa. **BE xong** (`piclass-api` branch `feat/teacher-list-and-classes`, chưa PR): `GET /teachers` (danh sách, lọc `name`/`status`/`account`/`has_classes`, kèm `active_classes_count`) và `GET /teachers/{teacher}/classes`. Spec/plan: `archive/superpowers/specs/2026-10-24-teacher-list-and-classes-api-design.md`, `archive/superpowers/plans/2026-10-24-be-teacher-list-and-classes.md`. **FE xong** (`piclass-web` branch `feat/admin-teacher-screens`, chưa PR): 3 màn AD-07/07a/07b, cùng các primitive `components/ui` đầu tiên (Button, Card, Input, Select, Tabs, Badge, Avatar, StatTile, Dialog, Skeleton) port từ design system v2. Đã thử qua `curl` với cookie phiên thật qua `piclass-infra` (đăng nhập, gọi `GET /teachers` ra dữ liệu thật, 4 route trả 200) — **chưa thử trên trình duyệt thật** (không có công cụ trình duyệt trong phiên làm việc này).
  - **Lệch có chủ ý so với mockup** (ghi lại để không hiểu nhầm là thiếu sót): bỏ nút "Mời tạo tài khoản" và tab "Tài khoản" ở AD-07a (để dành nhóm 3 — mockup-plan mục 3, sau khi gộp 2026-10-25); bỏ cột "Học sinh" ở bảng lớp đang dạy và stat "Học sinh phụ trách" (chưa có API đếm học sinh theo giáo viên); bỏ nút "Xem nhật ký thay đổi" (audit log chưa có màn); bỏ cảnh báo trùng tên/SĐT khi tạo hồ sơ (`form.hasDup`, chỉ là tiện ích hiển thị, không phải rule bắt buộc).
  - **API ghép** (api-gaps.md): A7 (`GET /teachers`), A8 (`GET /teachers/{id}/classes`) — đã xong, dùng để thay N+1.
- [x] 2. Môn học và tạo lớp, gán giáo viên (AD-08, AD-09, AD-10) — bàn xong 2026-10-25. Spec AD-08/AD-09/AD-10(a-e) ở `admin.md` đã đủ chi tiết từ trước, chỉ cần chốt 3 mục API ghép:
  - **C3 — làm luôn:** `GET /classes` trả kèm `teacher_names`, `student_count`, `next_session_at` (đóng G9 ở AD-09); tránh N+1 khi Admin rà danh sách lớp hằng ngày.
  - **C12 — tạm bỏ ở V1:** không thêm địa điểm/phòng học cho Class/LessonSession đợt này; Admin tạm ghi phòng vào ô Nhãn/Mô tả, quyết lại sau khi hỏi giáo viên vận hành thật (đồng bộ với "nên có, làm sau" ở `research/2026-10-24-api-v1-toi-thieu.md`).
  - **C7 — làm cơ chế chung:** viết hàm tìm không dấu (unaccent) một lần ở BE, áp cho `/classes` trước (và `/teachers` — đã làm), tái dùng cho `/subjects`, `/students`, `/parents` ở nhóm 3 — không viết riêng từng danh sách.
- [x] 3. Tài khoản, Học sinh, Phụ huynh (gộp nhóm 3+4+5 cũ, chốt 2026-10-25) — ba việc này gắn chặt nhau (tạo hồ sơ → liên kết → mời tài khoản dùng chung một hộp thoại AD-05b), gộp thành một nhóm để bàn và build một lần, tránh dở dang. Màn: AD-04/05/05a (học sinh), AD-06/06a/06b/06c (phụ huynh), AD-05b/05c/AD-02/AD-03 (mời tài khoản, tài khoản, vai trò). Thứ tự trong nhóm: tạo học sinh → tạo phụ huynh và liên kết → mời tài khoản (dùng chung cho cả hai). **Xong và merge `main` 2026-10-25** (`piclass-api` PR #43, #44; `piclass-web` PR #8).
  - **Mở khóa luôn phần đang để trống ở nhóm 2:** AD-10b (tab Học sinh của AD-10) đang ẩn/disable nút "Thêm học sinh" vì cần `GET /students` tìm kiếm — nhóm này làm xong thì nối nút đó, không phải sửa lại cấu trúc tab đã build.
  - **API ghép — BE xong, PR #43 merge:** A7 phần Student/Parent (`GET /students` cho AD-04, `GET /parents` cho AD-06 — cùng khuôn mẫu `GET /teachers`: lọc `status`/`account`/`name`, kèm `classes: [{id,name}]` cho Student và `children_count` cho Parent); A8 phần học viên (lớp đang học, nay có cả `id` để link, trong `classes`); A2/GM-6 xong — `GET /users/{id}` trả `active_roles: [{role_id,role}]`, mở khóa hẳn "Kết thúc vai trò" ở AD-03 (không cần workaround nào); GM-1 phần "Tài khoản" ở màn chi tiết xong — `GET /students|parents|teachers/{id}` giờ trả thẳng `account`, không cần gọi lại API danh sách để dò; C7 (`UnaccentSearch` tái dùng từ nhóm 2, áp cho cả hai danh sách). C3 phần liên kết phụ huynh–học sinh: `GET /parent-student-relationships` trả kèm `parent_name`/`student_name`, hết N+1 ở AD-05 "Phụ huynh liên kết" và AD-06c. **Còn mở, chưa làm:** A9 (điểm danh theo học sinh, nếu AD-05 quyết định hiển thị) — chờ chốt nghiệp vụ, không chặn FE.
  - **Rà lại mockup 2026-10-25 (sau khi user phát hiện FE lệch mockup ở AD-02/03/05b/05c/06c):** đã sửa — gộp 1 ô tìm ở AD-02, nút "Mời" ở 3 bảng danh sách (cần `person_profile_id`, đã thêm), tab thật cho AD-05/05a.../06a/07a (plan cũ đọc nhầm admin.md thành "một trang không tab"), nút "Xem nhật ký thay đổi" khóa ở tab Thông tin, nút tắt "Mời tạo tài khoản" ở khung đầu trang (hero) của AD-05/06a/07a (component chung `PersonHeroActions`, đối chiếu lại code 2026-10-25 thấy đã làm xong trước đó, mục này từng ghi nhầm thành "chưa làm"). **Tab "Lớp học" của AD-05 — xong, `piclass-api` PR #44 + `piclass-web` PR #8:** đổi chip tên lớp thành bảng (Lớp | Môn | Giáo viên | Vào lớp từ) theo mockup — BE thêm `subject_name`/`teacher_names`/`enrolled_from` vào `classes` của `GET /students/{id}`; FE component `StudentClassesTab` mới. Lệch có chủ ý: bỏ icon tròn màu ngẫu nhiên theo lớp (dữ liệu giả trong mockup, không có field BE tương ứng) — theo cùng mẫu `teacher-classes-tab.tsx` (AD-07a) đã làm trước đó, không phải lệch mới.
  - **Phát hiện kèm lúc làm bảng lớp học, PR #8:** `globals.css` có rule `a { font-weight: var(--fw-semibold) }` cho link chữ thường — mọi bảng/card bọc cả dòng trong `<Link>` (AD-02, AD-04, AD-06, AD-07, AD-09, và bảng mới AD-05) bị kế thừa độ đậm này cho cả các cột không cố ý đậm. Đã sửa bằng `font-normal` ở `<Link>` của từng dòng/card.
  - **Quyết định đã chốt:** #13 — BE tự gán vai trò ngay khi mời theo loại hồ sơ (`DEC-USR-018`, thay cho A2/nút "Gán vai trò ngay" ở AD-03).
  - **Quyết định mở còn lại:** #4 (nhận AD-05c "gửi lại lời mời" không), Q6 (khung điều hướng cho tài khoản vừa Admin vừa Teacher).
- [x] 4. Lịch học và buổi học (AD-10 tab Lịch và Buổi học, AD-11) — xong cả BE+FE 2026-10-25, chưa PR
  - **AD-10 (5 tab: Tổng quan/Học sinh/Giáo viên/Lịch/Buổi học): BE đã xong sẵn trước đó** (đánh dấu ✅ trong `admin.md`) — chỉ cần build FE, không cần thêm API.
  - **API ghép AD-11 — xong 2026-10-25** (`piclass-api` nhánh `feat/my-lesson-sessions-endpoint`, chưa PR): A6 — `GET /me/lesson-sessions` dùng chung cho mọi vai trò (Admin thấy toàn hệ thống, dùng ngay cho AD-11; Teacher/Student/Parent tái dùng sau), lọc `from/until/class_id/teacher_id/subject_id/hide_cancelled`, kèm `class_name/subject_name/teacher_names`. C12 (địa điểm buổi học) đã chốt tạm bỏ ở nhóm 2. C11 (`cancellation_reason`, `lesson_content`, `schedule_id`) không cần cho ô lịch AD-11 (chỉ hiện giờ/tên lớp/giáo viên/trạng thái); bấm mở buổi thì điều hướng sang AD-10e dùng API đã có.
  - **Quyết định đã chốt 2026-10-25:** #9 (hủy Schedule không hủy hàng loạt buổi đã sinh, giữ nguyên code — `BR-CLASS-009`), #12 (`weekdays` theo ISO, `repeat_weekly=false` vẫn sinh 1 buổi — `database/schema.md`).
  - **Phát hiện kèm lúc làm A6:** sửa vài model (`LessonSession`, `SchoolClass`, `TeacherClassAssignment`, `Teacher`) thêm type hint quan hệ (`BelongsTo`/`HasMany` + generic) để Larastan đọc đúng chuỗi quan hệ lồng — tiện tay dọn luôn vài lỗi PHPStan giả (false positive) đã baseline từ trước ở các Resource khác, `phpstan-baseline.neon` nhỏ đi.
  - **FE xong 2026-10-25** (`piclass-web` nhánh `feat/admin-schedule-group4`, chưa PR): feature mới `src/features/lesson/` (API + UI); tab AD-10d "Lịch" (`ClassScheduleTab` + dialog tạo/sửa/hủy), tab AD-10e "Buổi học" (`ClassSessionsTab` + dialog tạo/sửa/hủy/hoàn tất, hỗ trợ deep-link `?session=&month=` highlight dòng); màn mới AD-11 "Lịch tổng hợp" tại `/admin/schedule` (`MyScheduleScreen`, 3 view Ngày/Tuần/Tháng, cờ "▲ Trùng giờ" tính ở FE qua `detectTeacherConflicts`, hộp thoại "Thêm buổi" điều hướng kèm `?openCreate=1`).
  - **Lệch mockup có chủ ý, đã ghi vào `admin.md`:** không có nút "Điểm danh" ở AD-10e (TC-05 điểm danh chưa port FE cổng Giáo viên); dòng buổi học không hiện snippet nội dung (`lesson_content` chỉ có ở API chi tiết, danh sách không có, tránh gọi N+1); AD-11 giữ nguyên dòng chú thích "Chưa kiểm tra trùng phòng học" nhưng không tự tô màu theo lớp (`hue` trong mockup là dữ liệu giả, không có field BE tương ứng); hộp thoại tạo lịch chỉ báo "Đã tạo lịch." + cảnh báo trùng giáo viên dạng rút gọn theo mã lớp (API không trả số buổi sinh ra hay tên lớp/giáo viên trùng).
  - **Phát hiện kỹ thuật kèm lúc làm FE:** `academic` (màn chi tiết lớp) và `lesson` (tab Lịch/Buổi học) buộc phải phụ thuộc lẫn nhau một chiều (`academic` → `lesson`) theo đúng mẫu cross-feature đã dùng ở nhóm 3; AD-11 cần danh sách lớp/môn cho bộ lọc nhưng không thể import `academic`'s `useClasses`/`useSubjects` vì sẽ tạo vòng phụ thuộc (`architecture.test.ts` chặn) — giải quyết bằng cách `lesson` tự gọi `GET /classes`/`GET /subjects` riêng (`lesson/api/filter-options.ts`) thay vì tái dùng hook của `academic`.
- [x] 5. Tổng quan Admin (AD-01) và thiết lập lần đầu (AD-15) — bàn xong 2026-10-25.
  - **Quyết định đã chốt 2026-10-25:** #4 — bỏ hẳn AD-15 (checklist thiết lập lần đầu); mỗi trung tâm một bản triển khai riêng (`ARCH-33`) nên "thiết lập lần đầu" chỉ xảy ra một lần, Admin đầu tiên tự làm tuần tự qua các màn đã có hoặc seed Laravel, không cần một màn UI hướng dẫn riêng. Bỏ theo: banner ở AD-01, mục menu "Bắt đầu".
  - **C1 (khối "Cần xử lý" ở AD-01) — chốt phạm vi V1:** chỉ 2 mục — "buổi hủy cần rà soát điểm danh" (`review_required`) và "lớp đang chuẩn bị chưa có giáo viên" (`status=preparing`). Không nhận ở V1: "tài khoản mời chưa kích hoạt" (thiếu field BE, gap G4), "đơn xin phép chờ duyệt" và "bài chờ chấm" (thuộc việc của Giáo viên, không phải Admin — để dành bàn ở chủ đề tổng quan Giáo viên).
  - **API ghép:** không cần API mới — cả 2 mục "cần xử lý" và các thẻ số đều tính được từ `GET /classes`, `GET /me/lesson-sessions` (đã có từ nhóm 4), `GET /users?role=...&per_page=1`. AD-01 chuyển thẳng sang bước build FE (bước 4 `working-flow.md`), không cần bước 3.
  - Chi tiết đặc tả đã cập nhật ở `admin.md` (mục AD-01, AD-15).
  - **Mockup — xong, đã merge `main` 2026-10-25** (`piclass-mockup` PR #12): xóa hẳn màn AD-15 và banner "Thiết lập hệ thống" ở AD-01 khỏi `PiClass Quản trị.dc.html`; cắt mẫu `todoItems` còn đúng 2 loại đã chốt.
  - **FE — xong, đã merge `main` 2026-10-25** (`piclass-web` PR #10): màn `AdminOverviewScreen` (`features/academic/ui/admin/`) dùng lại `useClasses`, `useUsers`, `useMyLessonSessions` đã có — không cần BE mới, đúng như chốt ở trên. Thêm `formatWeekdayDate`/`greetingPeriod` vào `lib/time.ts`, prop `iconColor` cho `StatTile`. **Hoãn khỏi nhóm 5, ghi lại ở `admin.md`:** chip "Giảng dạy" (chờ Q6), cột "đã điểm danh chưa" ở buổi hôm nay (cần N+1, không có trong `GET /me/lesson-sessions`), lối tắt "Xem nhật ký audit" (AD-14 chưa có route FE), lối tắt không tự mở dialog tạo (chỉ điều hướng tới danh sách). Đã thử qua `curl` với cookie phiên thật qua `piclass-infra` (đăng nhập, `GET /classes`/`GET /me/lesson-sessions` ra dữ liệu thật, route `/admin` trả 200, Next dev compile không lỗi) — **chưa thử trên trình duyệt thật** (không có công cụ trình duyệt trong phiên làm việc này). **Nhóm 5 đã xong.**
- [x] 6. Khóa học và ghi danh (AD-12, AD-12a), nhật ký audit (AD-14), cấp lại lượt làm (AD-13) — bàn xong 2026-10-26.
  - **Quyết định đã chốt 2026-10-26:**
    - AD-12/AD-12a (Khóa học + ghi danh): giữ nguyên như đặc tả, BE đã đủ. Làm thêm C3 phần Course/Enrollment (API `GET /courses/{id}/enrollments` trả kèm tên học sinh, tránh phải gọi `GET /students/{id}` từng dòng).
    - AD-13 (Cấp lại lượt làm): **giữ, build UI** — khác với gợi ý tạm bỏ ở `api-v1-toi-thieu.md`, user chốt vẫn cần màn hình cho Admin dùng khi có sự cố Quiz/Exam, không xử tay qua API. BE đã có sẵn `POST /attempts/{id}/reissue`, không cần API mới.
    - AD-14 (Nhật ký audit): **bỏ UI ở V1**, theo đúng gợi ý `api-v1-toi-thieu.md` — BE vẫn ghi log đầy đủ (`AuditLog`, không đổi), chỉ tạm không dựng màn hình đọc lại. Không làm C2 (phân trang + enrichment tên actor/entity) ở nhóm này vì không có UI dùng tới; quay lại khi có nhu cầu đọc audit thật.
  - **FE — phạm vi lát này chỉ làm AD-12/AD-12a (chốt 2026-10-26 lúc lập plan FE):** AD-13 cần lối vào từ màn chi tiết bài nộp (`GET /submissions/{id}`, TC-13) — màn này chưa dựng ở `piclass-web` (Teacher/Student chưa tới lượt). Hoãn AD-13 sang khi làm nhóm có TC-13, lúc đó mới có chỗ gắn nút "Cấp lại lượt làm bài" thật; tránh xây hộp thoại treo không có lối vào. AD-14 giữ nguyên quyết định bỏ UI ở trên.
  - **API ghép — C3 phần Course/Enrollment, BE xong và đã merge `main`** (`piclass-api` PR #46): `GET /courses/{course}/enrollments` trả kèm `student_name` (ghép `student.personProfile`), tránh FE phải gọi `GET /students/{id}` từng dòng. Thêm return type generic (`BelongsTo<...>`) cho `CourseEnrollment::student()/course()` và `Student::personProfile()` để Larastan suy luận đúng — kéo theo tạo lại `phpstan-baseline.neon` (1093 → 1063 dòng, không thêm lỗi mới, 2249 test vẫn pass).
  - **Phạm vi ảnh hưởng của thay đổi C3 này:** chỉ thêm field mới (`student_name`), không đổi field cũ hay response shape khác → không breaking. Màn/luồng dùng tới:
    - **AD-12a (Admin — Học sinh ghi danh)**: màn duy nhất đang dùng `GET /courses/{id}/enrollments` ở V1, build FE theo field mới này.
    - **Teacher/Student/Parent xem Course**: cùng endpoint, cùng `ListCourseEnrollmentsQuery` (Admin/Teacher thấy mọi dòng kể cả đã kết thúc; Student/Parent chỉ thấy dòng của mình/con) — chưa có UI riêng cho các vai trò này ở V1, nhưng khi làm sau thì tự có `student_name`, không cần sửa BE lại.
    - **Không ảnh hưởng** `POST`/`DELETE /courses/{id}/enrollments` (ghi danh/kết thúc) — hai action này dùng Resource/Action khác, không đổi.
  - **API ghép — không cần làm ở nhóm này:** C2 (audit, hoãn theo quyết định bỏ AD-14 UI), C15 (reissue cho Assignment — ngoài phạm vi AD-13 hiện tại, giữ nguyên đề xuất "làm sau" của api-gaps.md).
  - **FE AD-12/AD-12a — xong 2026-10-26** (`piclass-web` nhánh `feat/admin-course-enrollment`, chưa PR), 7 task TDD: data layer (`src/features/academic/api/courses.ts`, `course-enrollments.ts`), màn danh sách (`CourseListScreen`) tại `/admin/courses`, màn chi tiết (`CourseDetailScreen`, route động `/admin/courses/[courseId]`) với tab Tổng quan/Học sinh ghi danh/Bài đánh giá, hộp thoại tạo/lưu trữ khóa, tab ghi danh (`CourseEnrollmentsTab` + hộp thoại kết thúc ghi danh), hộp thoại ghi danh hai chế độ theo học sinh/theo lớp (`CourseEnrollDialog`). `lint`/`typecheck`/`pnpm build` sạch, 72 file test / 335 test pass (gồm `architecture.test.ts`).
  - **Lệch mockup có chủ ý (đối chiếu `PiClass Quản trị.html` L893–1307):**
    - Danh sách khóa không có cột "Học sinh" và dòng phụ không có tên môn: `CourseListResource` thật chỉ trả `id,name,description,owner_teacher_id,status` (không có `subject_id` lẫn số học sinh — mockup tính cả hai từ dữ liệu mẫu cục bộ); gọi `GET /courses/{id}/enrollments` cho từng dòng danh sách sẽ là N+1.
    - Dòng ghi danh không hiện lý do kết thúc (mockup: "đến {ngày} ({lý do})"): `CourseEnrollmentListResource` không trả `end_reason` dù cột đã có trên bảng — ghi `api-gaps.md` C16, không chặn thao tác (lý do vẫn lưu được, chỉ không đọc lại ở danh sách).
    - `StatTile` "Bài đánh giá" luôn hiện "—": không có endpoint cho Admin liệt kê bài đánh giá theo khóa (đã ghi trong `admin.md` AD-12 🟡 từ trước); tab "Bài đánh giá" hiện cố định dòng trống của mockup, không tự suy từ `GET /assessments`.
    - Nút "Ghi danh" vẫn bấm được khi khóa đã lưu trữ (mockup cũng không khóa nút này ở hộp thoại, chỉ hiện cảnh báo phía trên); để BE là nguồn chặn thật qua lỗi `COURSE_ARCHIVED` thay vì FE tự khóa trước.
  - **Caveat kiểm thử:** phiên làm việc này không có công cụ trình duyệt tương tác; đã xác nhận qua `pnpm build` (route `/admin/courses`, `/admin/courses/[courseId]` build thành công, không lỗi TypeScript) và `curl` không đăng nhập (hai route trả 200, không crash máy chủ) — **chưa bấm thử bằng trình duyệt với tài khoản Admin thật** (tài khoản `admin@test.local` có sẵn trong CSDL local nhưng không rõ mật khẩu, không tự đặt lại).
  - **Mở rộng: liên kết Class–Course sống (bàn và xong 2026-10-26, cùng ngày với lát AD-12/AD-12a ở trên).** User tự tay gặp vấn đề ghi danh "theo lớp" một lần (không tự áp dụng cho học sinh vào lớp sau) khi thử nghiệm FE vừa xong; bàn qua `superpowers:brainstorming`, chốt chuyển sang liên kết sống Class↔Course — thiết kế đầy đủ ở `../../archive/superpowers/specs/2026-10-26-course-class-enrollment-link-design.md`. **BE xong, đã merge `main`** (`piclass-api` PR #47). **FE xong** (`piclass-web` nhánh `feat/fe-course-class-link`, plan `../../archive/superpowers/plans/2026-10-26-fe-course-class-link.md`, 5 task TDD): data layer (`course-class-links.ts`), khối "Lớp đã gắn" trong tab Tổng quan (`CourseLinkedClassesSection` + 2 dialog gắn/gỡ lớp), bỏ hẳn chế độ "Theo lớp" khỏi `CourseEnrollDialog`, thêm nhãn nguồn + bộ lọc Tất cả/Cá nhân/Qua lớp trong `CourseEnrollmentsTab` (giữ một danh sách chung, quyết định chốt sau khi bàn lại chi tiết hiển thị với user — không tách 2 khu vực như bàn ban đầu). `lint`/`typecheck`/`pnpm vitest run`/`pnpm build` sạch (355 test pass). Không có mockup HTML riêng cho UI này (user chốt code trực tiếp theo design system sẵn có). Đã kiểm chứng hợp đồng API thật bằng `curl` qua `piclass-infra` (gắn lớp → tự ghi danh đúng học sinh với `enrolled_via_class_id`, không đụng ghi danh cá nhân có sẵn; gắn trùng → 409 `ALREADY_LINKED`; gỡ lớp → ghi danh qua lớp tự kết thúc) — **chưa bấm thử bằng trình duyệt** (không có công cụ trình duyệt trong phiên này).
- [~] Tổng hợp cổng Admin — bàn xong 2026-10-26 (gần xong: còn build và xác nhận bên dưới)
  - **Giữ:** AD-01 đến AD-12a (gồm AD-05c, xem quyết định mới), AD-10a–e, AD-11.
  - **Hoãn UI:** AD-13 (cấp lại lượt làm), chờ màn chấm bài TC-13 của cổng Giáo viên để có chỗ gắn nút.
  - **Đơn giản hóa:** AD-10e chưa có nút điểm danh (chờ TC-05); AD-12 bỏ cột học sinh và tab bài đánh giá cố định (C16 chờ BE); AD-11 không tô màu theo lớp, không kiểm tra trùng phòng; AD-01 bỏ chip "Giảng dạy" — Q6 đã được `DEC-USR-015` giải quyết (mỗi tài khoản một vai trò, người vừa quản trị vừa dạy dùng hai tài khoản), nên chip này không còn cần.
  - **Bỏ ở V1:** UI nhật ký audit AD-14 (BE vẫn ghi log), AD-15 (đã bỏ từ trước), cảnh báo trùng tên/SĐT, xuất file.
  - **Quyết định mới 2026-10-26:** `DEC-USR-019` (AD-05c làm ở V1; đã có sẵn trong code), `DEC-USR-020` (học sinh ngừng hoạt động không vào lớp mới), `DEC-LRN-014` (lớp lưu trữ không nhận thêm thành viên, phân công, lịch).
  - **Còn lại trước khi đóng Admin:** (1) AD-05c đã có sẵn cả BE lẫn FE từ nhóm 3 (không cần build, chỉ sửa lại mô tả cũ ở `admin.md`); (2) ~~BE chặn lớp lưu trữ và học sinh ngừng hoạt động~~ — xong (`piclass-api` nhánh `feat/block-archived-class-inactive-student`, chưa PR; mã lỗi đã ghi `error-codes.md`), còn FE hiện lỗi 422 trong hộp thoại thêm học sinh/phân công/tạo lịch/buổi học; (3) chặn kết thúc ghi danh cá nhân khi học sinh còn trong lớp đã gắn khóa, và báo lỗi khi thêm ghi danh cá nhân trùng dòng qua lớp (`DEC-LRN-015`, `ARCH-35` đã chốt); (4) bấm thử toàn bộ Admin trên trình duyệt với tài khoản Admin (hiện chưa làm, chỉ có `curl` và `pnpm build`); (5) cập nhật `admin.md`: các mã GM-1, GM-2, GM-4, GM-6 đã xử lý một phần qua PR #43/#44 nhưng vẫn ghi 🟡.
- [ ] Sang nhóm dưới: cổng Giáo viên (port FE, hiện chỉ có mockup)
  - **Cổng Giáo viên, lát 1 Vận hành lớp (TC-02..TC-05): FE xong và đã merge** (`piclass-web` PR #27): lệch có chủ ý ghi ở `teacher-van-hanh.md`; chưa bấm thử trình duyệt. Còn 3 lát: Đơn xin phép & Tổng quan, Khóa học, Bài đánh giá.

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
- [x] ~~Đồng bộ lại `prompts/admin.md` với mockup Admin cuối~~ — bỏ (2026-10-28): file đã chuyển `archive/prompts-admin-cu.md`; nguồn thật là `piclass-mockup/ds-v2-source`
