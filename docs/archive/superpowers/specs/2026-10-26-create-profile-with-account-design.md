# Thiết kế: Thêm hồ sơ kèm tài khoản đăng nhập + Admin đặt lại mật khẩu

**Ngày:** 2026-10-26
**Phạm vi:** `piclass-api` (UserManagement, Identity) + `piclass-web` (Admin AD-05a/06b/07b, AD-05/06a/07a, đăng nhập CM-01, hồ sơ CM-05)
**Trạng thái:** user đã duyệt 2026-10-26; chờ plan BE

## Bối cảnh và vì sao

Hiện nay thêm một Học sinh/Phụ huynh/Giáo viên là **hai bước rời nhau**: tạo hồ sơ (AD-05a/06b/07b), rồi vào chi tiết bấm "Mời tạo tài khoản" (AD-05b). Tài khoản sinh ra với mật khẩu ngẫu nhiên và người được mời phải bấm liên kết (hết hạn 7 ngày) để tự đặt mật khẩu (CM-09). Thực tế vận hành ở trung tâm: học sinh và phụ huynh thường không tự bấm liên kết được, Admin vẫn phải hướng dẫn hoặc tạo lại liên kết; và Admin dễ quên bước mời nên có hồ sơ mà không có tài khoản.

User (bàn qua `superpowers:brainstorming`, 2026-10-26) muốn: **thêm hồ sơ là tạo luôn tài khoản**, theo kiểu Admin "đăng ký sẵn" rồi chia sẻ thông tin đăng nhập (qua Zalo). Người dùng không cần đăng nhập ngay, nhưng có tài khoản sẵn là điều nên làm cho cả ba loại hồ sơ.

Đối chiếu code (2026-10-26): `CreateInvitedUser` đã tạo tài khoản `active` ngay với mật khẩu ngẫu nhiên; "kích hoạt" thực chất chỉ là đặt mật khẩu qua liên kết. Vì vậy thay đổi này chủ yếu là cho Admin nhập mật khẩu thật ngay lúc thêm, không phải đổi vòng đời tài khoản.

## Mục tiêu (Goals)

1. Form thêm Học sinh, Phụ huynh, Giáo viên có thêm khối **Tài khoản đăng nhập** gồm: tên đăng nhập (`login_identifier`, bắt buộc, duy nhất; nhập tên tự đặt hoặc email, `DEC-USR-014/017`) và mật khẩu (bắt buộc, có nút tự sinh).
2. Lưu một lần thì tạo hồ sơ + tài khoản `active` + gán vai trò suy từ loại hồ sơ (`DEC-USR-018`) trong **một giao dịch**; lỗi giữa chừng thì không còn hồ sơ mồ côi.
3. Sau khi lưu, màn hình hiện **tên đăng nhập và mật khẩu** để Admin sao chép gửi qua Zalo. Mật khẩu rõ chỉ hiện ở thời điểm này (và ở lần đặt lại mật khẩu, mục 5); không lưu lại để xem sau.
4. Lần đăng nhập đầu của tài khoản do Admin đặt mật khẩu: hiện lời nhắc **"Nên đổi mật khẩu"** có nút **Bỏ qua** (không chặn cứng). Cần một cột cờ chung kiểu "cần đổi mật khẩu lần đầu" (đặt tên khi làm plan BE, ví dụ `must_change_password`; ý nghĩa tổng quát, không gắn với Admin). Đổi mật khẩu xong hoặc Bỏ qua thì tắt cờ, không nhắc lại ở các lần sau; người dùng vẫn đổi được bất cứ lúc nào qua CM-05 như hiện nay.
5. **Admin đặt lại mật khẩu** (chức năng mới, tách riêng với "Tạo lại liên kết"): ở khối Tài khoản của AD-05/06a/07a, Admin đặt (hoặc tự sinh) mật khẩu mới cho một tài khoản, hiện để sao chép; bật lại cờ ở mục 4; ghi audit. Dùng khi cần người dùng vào được ngay (ví dụ học sinh quên mật khẩu ngay tại lớp). **Quyền (user chốt 2026-10-26):** Admin đặt lại được cho mọi loại tài khoản; **Teacher cũng được đặt lại mật khẩu, chỉ cho tài khoản Học sinh** (không giới hạn lớp, cùng tinh thần `DEC-USR-016`) — phạm vi "chỉ Học sinh" là chốt của Claude, user chưa nói rõ.
6. "Tạo lại liên kết" (AD-05c, DEC-USR-019) **giữ nguyên, không đổi hành vi**; dùng khi người dùng muốn tự đặt mật khẩu mà Admin không biết. Hai nút nằm cạnh nhau trong khối Tài khoản; Admin chọn theo tình huống.
7. Chức năng "Mời tạo tài khoản" (AD-05b, BE `POST /person-profiles/{id}/invite`) **giữ nguyên, không xóa**, nhưng luồng thêm mới không dùng tới. Nút "Mời" ở màn chi tiết vẫn chỉ hiện với hồ sơ chưa có tài khoản, nên chủ yếu còn thấy với dữ liệu cũ; FE không cần gỡ.

## Non-goals

- Không đổi cơ chế "Quên mật khẩu" (CM-02/03) và "Đổi mật khẩu" của chính chủ (`PATCH /me/password`).
- Không đổi liên kết kích hoạt, hạn 7 ngày, thư mời (`InvitePersonProfile`, `RegenerateInvitation`).
- Không làm giao diện chọn/đổi vai trò (`DEC-USR-015`: mỗi tài khoản một vai trò).
- Không tự gửi mật khẩu qua email, kể cả khi hồ sơ có email (mật khẩu rõ không đi qua email). Hồ sơ có email chỉ để liên hệ/quên mật khẩu như cũ.
- Không backfill: hồ sơ cũ chưa có tài khoản giữ nguyên, dùng nút "Mời" hiện có.
- Không ép quy tắc mật khẩu riêng cho mật khẩu Admin đặt: dùng đúng quy tắc độ mạnh mật khẩu đang áp ở `ChangePassword`/`ResetPassword`.

## Anti-goals

- Không để tạo xong hồ sơ mà tài khoản lỗi (hoặc ngược lại) rồi vẫn báo thành công một nửa.
- Không để mật khẩu rõ lọt vào log, audit, response của các lần đọc sau, hay thông báo.
- Không để một hồ sơ có hai tài khoản; không để một tên đăng nhập trùng.
- Không làm hỏng luồng thêm-hồ sơ-không-tài-khoản hiện có nếu phải giữ (xem Câu hỏi mở 1).

## Quyết định cần ghi vào Decision Log

- **DEC-USR-021 (mới):** Thêm hồ sơ Student/Parent/Teacher **tạo luôn tài khoản** (Admin nhập tên đăng nhập + mật khẩu, chia sẻ qua kênh ngoài); sửa phần "hồ sơ trước, mời khi cần đăng nhập" của `DEC-USR-001`/`BR-USER-001`. Lần đăng nhập đầu có lời nhắc đổi mật khẩu, được Bỏ qua. User chốt 2026-10-26.
- **DEC-USR-022 (mới):** Đặt lại mật khẩu cho tài khoản (Admin: mọi loại; Teacher: chỉ học sinh) là chức năng riêng, song song với "Tạo lại liên kết" (`DEC-USR-019`), không thay thế nhau. User chốt 2026-10-26.
- Nhắc lại `DEC-USR-014` ("đặt mật khẩu hộ" nay được hiện thực bằng chức năng ở DEC-USR-022) và `DEC-USR-016` (Teacher được tạo hồ sơ Student: quyền này kéo theo Teacher tạo cả tài khoản học sinh khi thêm và đặt lại mật khẩu học sinh).

## Thiết kế BE (tóm tắt, chi tiết ở plan BE)

- Cột cờ cần-đổi-mật-khẩu trên `users` (migration + cập nhật `docs/database/*`). `GET /auth/me` trả thêm cờ để FE biết có hiện lời nhắc; endpoint tắt cờ khi Bỏ qua; `PATCH /me/password` cũng tắt cờ.
- Mở rộng `POST /students`, `POST /parents`, `POST /teachers` (hoặc Action dùng chung) nhận thêm `account: {login_identifier, password}`; tạo hồ sơ + User + vai trò trong một transaction, bật cờ. Có audit tương đương việc gán vai trò hiện có.
- Action mới "Admin đặt lại mật khẩu" cho một tài khoản (endpoint dưới `/person-profiles/{id}/...` cho nhất quán với `/invite`, `/invitation`): đặt mật khẩu mới, bật cờ, **thu hồi các phiên/token đang đăng nhập của tài khoản đó**, ghi audit không chứa mật khẩu. Quyền: Admin (mọi loại tài khoản) và Teacher (chỉ tài khoản Student, không giới hạn lớp); route thêm `teacher` và Policy/Action kiểm loại hồ sơ đích.
- Tái dùng `CreateInvitedUser` hoặc tách Action tạo User chung: để builder quyết, miễn không phá `InvitePersonProfile`.

## Thiết kế FE (tóm tắt, chi tiết ở plan FE)

- AD-05a/06b/07b (chế độ **tạo**): thêm khối Tài khoản đăng nhập (tên đăng nhập, mật khẩu + nút tự sinh + hiện/ẩn). Chế độ **sửa** không có khối này.
- Sau khi lưu: hộp thoại/khối kết quả hiện tên đăng nhập + mật khẩu với nút sao chép; cảnh báo "mật khẩu chỉ hiện lần này".
- AD-05/06a/07a, khối Tài khoản: thêm nút **Đặt lại mật khẩu** cạnh "Tạo lại liên kết"; hộp thoại nhập/tự sinh mật khẩu, kết quả hiện để sao chép.
- Sau đăng nhập (CM-01 hoặc layout app): nếu cờ bật thì hiện lời nhắc đổi mật khẩu với "Đổi mật khẩu" (tới CM-05) và "Bỏ qua".
- Tuân quy ước màn hình: dùng component/token design system sẵn có; UI mới (khối tài khoản, hộp thoại mật khẩu, lời nhắc) cần chốt mockup theo quy trình lát (`docs/process/working-flow.md`) trước khi code FE.

## Câu hỏi mở (builder không tự quyết được, cần user)

1. Form thêm có cho **để trống** khối tài khoản (tạo hồ sơ không tài khoản) không? Theo quyết định hiện tại của user là "thêm hồ sơ thì tạo luôn tài khoản" nên mặc định **bắt buộc** (không để trống); nếu sau này cần phụ huynh không tài khoản thì mở lại.
2. ~~Quyền Teacher~~ — **đã chốt 2026-10-26:** Teacher được đặt mật khẩu lúc thêm học sinh và được đặt lại mật khẩu (chỉ cho học sinh; phạm vi "chỉ học sinh" là chốt của Claude). Nếu muốn Teacher đặt lại cho cả Phụ huynh/Teacher khác thì nói lại.
3. Mật khẩu tự sinh: độ dài/ký tự (đề xuất 12 ký tự, tránh ký tự dễ nhầm) — builder tự chọn được; chỉ cần ghi lại trong plan.

## Các điều Claude tự chốt (user kiểm tra khi đọc)

Rút ra từ builder-check; đều không đổi thứ được xây nên Claude tự quyết:

- **Đường tạo hồ sơ khác:** `account` là **bắt buộc ở API** `POST /students|parents|teachers`. Quét sơ bộ thấy chỉ có 3 route này tạo hồ sơ; seeder/factory/test dựng bằng model nên không đổi, còn test gọi qua endpoint phải thêm `account`. Plan BE rà lại toàn bộ caller (cả FE) trước khi sửa.
- **Đặt lại mật khẩu trên tài khoản mời chưa kích hoạt:** Action mới xóa luôn token lời mời còn hiệu lực của tài khoản đó (không sửa code `InvitePersonProfile`/`RegenerateInvitation`), để liên kết cũ không đặt đè mật khẩu Admin vừa đặt. Hồ sơ **chưa có tài khoản** thì không hiện nút "Đặt lại mật khẩu" (chỉ hiện "Mời").
- **Cờ cần-đổi-mật-khẩu:** mặc định `false`. Tài khoản cũ và tài khoản tạo qua luồng "Mời" **không** bật cờ (người dùng tự đặt mật khẩu rồi nên nhắc là vô nghĩa); không backfill. Chỉ bật khi Admin/Teacher đặt mật khẩu lúc thêm hoặc Admin đặt lại.
- **Thu hồi phiên:** xóa mọi token Sanctum và phiên của **tài khoản bị đặt lại**, không đụng phiên của Admin đang thao tác.
- **`login_identifier` và email hồ sơ:** độc lập, chỉ ràng buộc duy nhất ở `users.login_identifier` (như `InvitePersonProfileRequest`). "Quên mật khẩu" vẫn gửi theo email hồ sơ như hiện nay; không thêm ràng buộc đối chiếu.

## Tiêu chí hoàn thành

- Thêm mới cả ba loại hồ sơ tạo được tài khoản đăng nhập dùng được ngay bằng thông tin vừa nhập; vai trò đúng loại.
- Lỗi (tên đăng nhập trùng, mật khẩu yếu) trả về ở form và **không** tạo hồ sơ.
- Đăng nhập lần đầu thấy lời nhắc đổi mật khẩu; Bỏ qua hoặc đổi xong thì không hiện lại; Admin đặt lại mật khẩu thì hiện lại lần đăng nhập kế.
- Đặt lại mật khẩu làm mất hiệu lực phiên cũ của tài khoản đó; có audit, không lộ mật khẩu.
- "Mời" và "Tạo lại liên kết" cũ chạy như trước (test hiện có vẫn xanh).
- Decision Log, `docs/database/*`, `docs/product/screens/admin.md` (AD-05a/06b/07b, AD-05b/05c, khối Tài khoản), `progress.md` và `trang-thai-ngan.md` được cập nhật.
