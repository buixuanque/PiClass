# ARCH-20 — ID không tồn tại và ID của người khác trả message 404 khác nhau

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT (2026-10-19)` Phát hiện khi review (2026-10-18): đối tượng của người khác trả `Request failed.` (không lộ tồn tại), nhưng ID không tồn tại trả message của route-model-binding ("No query results for model ..."), nên người gọi phân biệt được hai trường hợp. Quyết định: chuẩn hóa — mọi lỗi 404 trên `api/*` đều trả `Request failed.`, bất kể nguyên nhân (route-model-binding hay Policy `denyAsNotFound()`). Sửa trong handler `HttpExceptionInterface` ở `bootstrap/app.php`.

**Ảnh hưởng:** Toàn bộ API, bảo mật enumeration.
