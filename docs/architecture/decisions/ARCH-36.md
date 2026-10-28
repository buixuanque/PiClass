# ARCH-36 — UI lát "thêm hồ sơ kèm tài khoản" dựng không qua mockup (FE `piclass-web`, 2026-10-27)

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `CHỜ XÁC NHẬN` User bỏ bước mockup cho lát này; UI dựng từ primitive có sẵn, copy class từ `InviteAccountDialog`/`RegenerateInvitationDialog`/form hiện có, chưa đối chiếu mockup chuẩn. Điểm cần user xem/chốt: (a) mật khẩu **hiển thị rõ** ở ô nhập của Admin (để đọc và gửi Zalo); (b) hộp thoại kết quả hiện mật khẩu một lần, nếu `navigator.clipboard` không có (HTTP thuần) thì chỉ còn chép tay; (c) hint "Tối thiểu 8 ký tự." là bản sao tĩnh rule BE (nợ nhỏ, xem `known-debt.md`); (d) Dialog chưa có focus trap nên Tab/Enter có thể chạm form phía sau hộp thoại kết quả (BE chặn trùng bằng 422); (e) cổng Giáo viên chưa port nên Teacher chưa có UI tạo học sinh / đặt lại mật khẩu (BE đã cho phép).
