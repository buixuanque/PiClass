# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, mỗi mục một dòng, không văn xuôi. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-28 · bởi: xong bước 2 lát Vận hành lớp (rà mockup)

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự: Vận hành lớp (TC-02..05) → Đơn xin phép & Tổng quan (TC-06, TC-01) → Khóa học (TC-15) → Bài đánh giá (TC-07..14). Đang ở lát 1.
- **Bước:** lát 1 đã xong bước 1 (`DEC-LRN-016`, `DEC-LRN-017`, A1) và bước 2 (rà mockup TC-01..TC-05, user đã xem standalone); kế tiếp bước 3: rà API còn thiếu cho TC-02..TC-05
- **Cổng Admin:** xong toàn bộ, đã merge `main`, user đã bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 1 Giáo viên, bước 3: rà API còn thiếu cho TC-02..TC-05 (đã biết: C11 `cancellation_reason` ở `GET /lesson-sessions/{id}`; lịch sử sửa điểm danh G5; xem `product/screens/api-gaps.md`), chốt cái nào làm ở V1, rồi plan và code BE
2. Lát 1: dựng shell riêng cho Teacher (mỗi tài khoản một vai trò, không có công tắc đổi vai trò, `DEC-USR-015`)
3. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| (không có) | — | mọi repo `main` sạch, không có PR mở (kiểm 2026-10-28, sau #62/#26/#120) |

## Cổng
| Cổng | FE |
|---|---|
| Admin | [x] xong |
| Giáo viên | [ ] chưa port (có mockup) |
| Học sinh | [ ] chưa port (có mockup) |
| Phụ huynh | [ ] chưa port (có mockup) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
