# Business Rules — Quy tắc nghiệp vụ cấp hệ thống

Quy ước: mỗi rule có ID ổn định; các quyết định đã được người dùng chốt được ghi **ĐÃ QUYẾT ĐỊNH**. Chi tiết từng lựa chọn nằm tại [Decision Log](decision-log.md).

## Nguyên tắc sản phẩm và dữ liệu

- **BR-SYS-001 — ĐÃ QUYẾT ĐỊNH:** V1 ưu tiên quy trình học tập thực tế của lớp học thêm và các hoạt động cốt lõi đã nêu trong Product Definition.
- **BR-SYS-002 — ĐÃ QUYẾT ĐỊNH:** Không mở rộng V1 sang AI tutor/chấm/sinh câu hỏi/đề xuất, video, lớp trực tuyến, chat, payment/học phí, mobile app, gamification, chứng chỉ hoặc phân tích nâng cao.
- **BR-SYS-003 — ĐÃ QUYẾT ĐỊNH:** Admin và Teacher là hai vai trò riêng; một User được đồng thời có cả hai.
- **BR-SYS-004 — ĐÃ QUYẾT ĐỊNH:** Dữ liệu lịch sử học tập không bị xóa chỉ vì tài khoản hoặc quan hệ nghiệp vụ bị vô hiệu hóa/kết thúc.
- **BR-SYS-005 — ĐÃ QUYẾT ĐỊNH:** Quyền đọc tệp đính kèm không vượt quá quyền đọc đối tượng nghiệp vụ chứa tệp.
- **BR-SYS-006 — ĐÃ QUYẾT ĐỊNH:** Hành động nhạy cảm cần audit; event, quyền xem và lưu giữ được quy định tại DEC-AUD-001–003.
- **BR-SYS-007 — ĐÃ QUYẾT ĐỊNH:** Kết quả chưa công bố không hiển thị cho Student/Parent.
- **BR-SYS-008 — ĐÃ QUYẾT ĐỊNH:** Thay đổi câu nguồn không được âm thầm thay đổi nội dung đã phát hành hoặc bài làm đã bắt đầu.
- **BR-SYS-009 — ĐÃ QUYẾT ĐỊNH:** Quy tắc nghiệp vụ không ấn định cấu trúc DB, endpoint, component UI hay chi tiết triển khai.
- **BR-SYS-010 — ĐÃ QUYẾT ĐỊNH:** V1 dùng giờ Việt Nam (Asia/Ho_Chi_Minh); thời điểm hệ thống nhận bài làm căn cứ deadline.
- **BR-SYS-011 — ĐÃ QUYẾT ĐỊNH:** Hồ sơ nghi trùng do Admin xác minh; không tự gộp để tránh ghép sai lịch sử.

## Nguyên tắc ra quyết định

- **BR-SYS-012 — ĐÃ QUYẾT ĐỊNH:** Không suy rộng chức năng ngoài Feature Map và Product Definition V1.
- **BR-SYS-013 — ĐÃ QUYẾT ĐỊNH:** Không tự coi đề xuất là policy; quyết định vận hành đã chốt được ghi tại Step 5.
- **BR-SYS-014 — ĐÃ QUYẾT ĐỊNH:** Admin xử lý ngoại lệ hệ thống; Teacher xử lý trong lớp được phân công. Ngoại lệ nhạy cảm phải có lý do và dấu vết.
