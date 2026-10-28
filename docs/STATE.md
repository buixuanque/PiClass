# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, mỗi mục một dòng, không văn xuôi. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-29 · bởi: chốt chức năng lát 2 (bước 1)

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự: Vận hành lớp (TC-02..05, xong) → Đơn xin phép & Tổng quan (TC-06, TC-01) → Khóa học (TC-15) → Bài đánh giá (TC-07..14). Đang ở lát 2.
- **Bước:** lát 2 xong bước 1 (`DEC-LRN-018`: làm giàu `GET /absence-explanations`, thêm `attendance_count` vào `/me/lesson-sessions`, TC-01 chỉ 2 ô cần xử lý); kế tiếp bước 2: rà mockup TC-06, TC-01
- **Cổng Admin:** xong toàn bộ, đã merge `main`, user đã bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 2 Giáo viên, bước 2: rà mockup `PiClass Giáo viên.dc.html` cho TC-06 và TC-01 theo `DEC-LRN-018` (sửa mockup, chạy `make_standalone.py`, user xem `standalone/`)
2. Lát 2, bước 3: API (làm giàu đơn xin phép, `attendance_count`), rồi bước 4 FE
3. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| (không có) | — | mọi repo `main` sạch (kiểm 2026-10-29, sau web #27, docs #126); nhánh docs `docs/plan-teacher-lot1-fe` bỏ, plan đã vào archive |

## Cổng
| Cổng | FE |
|---|---|
| Admin | [x] xong |
| Giáo viên | [ ] đang port: lát 1 (TC-02..05) xong, đã merge `main`; còn 3 lát |
| Học sinh | [ ] chưa port (có mockup) |
| Phụ huynh | [ ] chưa port (có mockup) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
