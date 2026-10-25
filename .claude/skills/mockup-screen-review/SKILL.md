---
name: mockup-screen-review
description: Dùng khi user bảo "rà soát", "kiểm tra", "màn này không giống mockup" cho một màn hình FE (chỉ định mã màn, ví dụ AD-08) hoặc toàn bộ màn hình đã port. So khớp code piclass-web với mockup chuẩn, phân loại lệch riêng-màn vs quy ước đồng nhất, sửa và kiểm chứng.
---

# Rà soát màn hình so với mockup (PiClass)

## 1. Xác định phạm vi

- **Có chỉ định** (tên màn hoặc mã AD/TC/ST/PR/CM-xx): dùng đúng mã đó.
- **Không chỉ định / "toàn bộ"**: liệt kê mã màn đã có trong `docs/product/screens/mockup-plan.md` (cột đã port, trạng thái `[x]`) và `docs/progress.md` — rà từng mã theo đúng thứ tự nhóm đang/đã làm, không nhảy sang màn chưa build.

## 2. Tìm đúng nguồn để so

- Nguồn layout/copy/state **duy nhất đáng tin**: `piclass-mockup/design-system/standalone/PiClass <Vai trò>.html`. Grep comment đánh dấu `<!-- ============ <MÃ MÀN> ... ============ -->` để nhảy đúng section (file có 2 bản render giống nhau, lấy bản nào cũng được).
- Logic JS cùng file (label theo state, format chuỗi, filter/sort, biến `xxxTabs`/`xxxSummary`...) thường nằm ở phần `<script>` cuối file — grep theo tên biến dùng trong `{{...}}` của section đó.
- `docs/product/screens/*.md` **không thay thế** việc đọc mockup — chỉ dùng để biết mục đích/API, không dùng để lấy copy chữ hay layout chính xác.
- Code FE hiện tại: `piclass-web/src/features/<module>/ui/admin|teacher|student|parent/*.tsx` — comment đầu file luôn trỏ mã màn, dùng để xác nhận đúng file.

## 3. Danh sách điểm so khớp (theo từng màn)

- Tiêu đề, dòng mô tả dưới tiêu đề (format chuỗi đúng, không rút gọn).
- Label nút, loại component filter (Tabs pill vs Select dropdown — hay lệch nhất).
- Header cột bảng, tỉ lệ cột, thứ tự cột, chữ hậu tố ("... lớp", "... học sinh").
- Variant nút (`outline`/`ghost`/`primary`), có icon hay không, đúng icon.
- Số liệu tính toán (có loại trừ trạng thái đã lưu trữ/archived không — dễ sai).
- Dialog: tiêu đề có chèn tên đối tượng không, `description` phụ dưới tiêu đề, nội dung thân, **label nút xác nhận đổi theo hành động** (không dùng "Xác nhận" chung khi mockup ghi rõ "Ngừng sử dụng"/"Dùng lại"...).
- Trạng thái tải/lỗi/rỗng: chữ chính xác, nút hành động đúng tên.

## 4. Phân loại lệch trước khi sửa

- **Lệch riêng màn này** (không xuất hiện ở màn khác cùng module) → sửa ngay.
- **Lệch đồng nhất ở nhiều màn khác** (icon tròn trạng thái lỗi/rỗng, "Hủy" thay "Hủy bỏ", thiếu `description` ở dialog...) → đây nhiều khả năng là quy ước FE đã chốt, **không tự sửa một màn rồi để màn khác lệch** — chỉ nêu trong báo cáo, hỏi user có muốn mở việc riêng rà soát toàn module không.
- Nghi ngờ mockup sai (ví dụ thiếu a11y) → ghi vào `docs/architecture/open-decisions.md`, không tự quyết.

## 5. Sửa và kiểm chứng

- Sửa trực tiếp theo giá trị đọc từ mockup, không làm tròn/suy luận (theo quy tắc "Port phải giống hệt mockup" ở `piclass-web/CLAUDE.md`).
- Cập nhật test liên quan nếu đổi text/role/label ảnh hưởng query trong test.
- Bắt buộc chạy trước khi báo xong: `pnpm typecheck`, `pnpm lint`, `pnpm vitest run <file màn>` (và cả thư mục module nếu sửa file dùng chung như dialog).

## 6. Báo cáo

Liệt kê từng điểm đã sửa (file + tóm tắt), và mục "không sửa" kèm lý do (quy ước đồng nhất toàn module). Không gộp chung thành một câu "đã khớp mockup" nếu còn mục cố ý bỏ qua.
