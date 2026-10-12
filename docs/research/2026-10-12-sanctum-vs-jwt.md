# Research: Laravel Sanctum vs JWT cho PiClass

**Ngày:** 2026-10-12
**Bối cảnh:** Chọn cơ chế auth cho backend Laravel + frontend Next.js (tách riêng). Liên quan quyết định `ARCH-04`/`ARCH-05` trong `docs/architecture/open-decisions.md`.
**Kết luận đã áp dụng:** Sanctum — xem `docs/architecture/technology-decisions.md`.

---

## 1. Hai thứ này là gì?

**Laravel Sanctum**: có sẵn trong Laravel, dùng cách đăng nhập "truyền thống" — khi user đăng nhập, server tạo một **session** và lưu trong database, gửi cho trình duyệt một **cookie** nhỏ để nhớ user ở các lần gọi sau. Server luôn tra lại được database để biết session còn sống hay đã bị hủy.

**JWT** (ví dụ gói `tymon/jwt-auth`): server phát ra một **token** chứa sẵn thông tin user, đã được ký để chống giả mạo, gọi là **"stateless"** (không trạng thái) — server **không lưu lại** token đó ở đâu cả, chỉ cần token còn hợp lệ và chưa hết hạn là cho qua.

## 2. Vấn đề quan trọng nhất: THU HỒI QUYỀN NGAY LẬP TỨC

PiClass đã chốt 3 rule cần khóa quyền **ngay**: khóa tài khoản phải chặn truy cập ngay, gỡ liên kết Phụ huynh–Học sinh phải mất quyền xem ngay, đổi/ngừng Giáo viên phải chuyển giao lớp rồi mới khóa quyền.

- **JWT**: vì server không lưu token, **không thể "xóa" một token đã phát ra** — nó vẫn hợp lệ tới khi tự hết hạn, dù tài khoản đã bị khóa trong DB. Muốn chặn ngay phải tự thêm "danh sách đen" (blacklist) — tức là tự xây lại đúng phần "lưu trạng thái ở server" mà JWT ban đầu muốn tránh, code phức tạp hơn.
- **Sanctum**: xóa dòng session/token trong database → ngay request tiếp theo user đó mất quyền. Không cần cơ chế phụ nào.

→ Sanctum khớp đúng kiểu "tắt là tắt ngay" PiClass cần; JWT chuẩn thì không, trừ khi tự xây thêm.

## 3. Khác biệt thực tế khác (Laravel + Next.js tách riêng)

- **Cookie vs Token**: Sanctum dùng cookie tự động, Next.js + Laravel chỉ cần cùng domain cha (`app.piclass.com` + `api.piclass.com` vẫn OK). JWT không ràng buộc domain — hợp hơn cho mobile/bên thứ ba.
- **CSRF**: Sanctum có sẵn cơ chế chống giả mạo request qua cookie, Laravel lo giúp. JWT không bị kiểu này nhưng phải tự lo chống đánh cắp token (XSS).
- **Độ phức tạp**: Sanctum là package chính chủ Laravel, ít cấu hình. JWT muốn "khóa ngay" phải tự thêm blacklist + nơi lưu riêng — tốn công vận hành thêm, không hợp với chủ trương 1 server tiết kiệm chi phí.
- **Nếu sau này có mobile app/bên thứ ba gọi API**: đây là lúc JWT có lợi thế thật. Nhưng Sanctum **cũng hỗ trợ sẵn Bearer token** riêng cho mobile/third-party, dùng song song với cookie cho web — không bị khóa cứng một hướng.

## 4. Đề xuất

**Chọn Sanctum.** Next.js là frontend nhà mình, chưa có mobile app, và các rule thu hồi quyền ngay đã chốt — Sanctum đáp ứng đúng mà không cần code thêm, JWT chuẩn sẽ vướng đúng chỗ quan trọng nhất. Sau này có mobile/third-party thì bổ sung Bearer token của chính Sanctum, không cần đổi hệ thống auth.

*Lưu ý: phần claim về Sanctum dựa trên doc chính thức Laravel 12.x; phần nói JWT cần tự xây blacklist dựa trên nguồn cộng đồng (Laracasts, package README bên thứ ba), không phải doc chính thức của gói jwt-auth.*

## Nguồn tham khảo

- [Laravel Sanctum – Official Docs](https://laravel.com/docs/12.x/sanctum)
- [Laracasts — tymon/jwt-auth vs Sanctum](https://laracasts.com/discuss/channels/general-discussion/tymonjwt-auth-vs-sanctum)
- [kamoca/laravel-jwt-database-blacklist](https://packalyst.com/packages/package/kamoca/laravel-jwt-database-blacklist)
- [DEV Community — Laravel+React auth comparison](https://dev.to/addwebsolutionpvtltd/laravel-react-authentication-the-right-way-sanctum-jwt-or-passport-a-developers-comparison-38h6)
- [prateeksha.com — API auth comparison](https://prateeksha.com/blog/api-authentication-laravel-sanctum-jwt-oauth-what-to-choose-and-why)
