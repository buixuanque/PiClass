# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, mỗi mục một dòng, không văn xuôi. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-29 · bởi: xong code FE lát Vận hành lớp

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự: Vận hành lớp (TC-02..05) → Đơn xin phép & Tổng quan (TC-06, TC-01) → Khóa học (TC-15) → Bài đánh giá (TC-07..14). Đang ở lát 1.
- **Bước:** lát 1 đã xong bước 1 (`DEC-LRN-016`, `DEC-LRN-017`, A1), bước 2 (rà mockup TC-01..TC-05) và bước 3 (API: `cancellation_reason` ở chi tiết buổi, `actor_name` ở audit); bước 4 xong code FE TC-02..05 (đã review cuối, nhánh `feat/teacher-lot1-operations`); kế tiếp bước 5: bấm thử trình duyệt, rồi bước 6
- **Cổng Admin:** xong toàn bộ, đã merge `main`, user đã bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 1 Giáo viên, bước 5–6: sau khi user bấm thử trình duyệt, sửa lỗi phát sinh, merge PR web #27 và docs, rồi `git mv` plan sang `archive/superpowers/plans/` (plan nằm ở nhánh `docs/plan-teacher-lot1-fe`, chưa merge)
2. Lát 2 Giáo viên: Đơn xin phép & Tổng quan (TC-06, TC-01), bước 1 bàn chức năng
3. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Bấm thử trình duyệt lát 1 Giáo viên: `/teacher/classes`, `/teacher/schedule`, `/teacher/attendance` (bước 5)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| piclass-web | `feat/teacher-lot1-operations` | `chờ merge` — PR #27 mở, đã push |
| piclass (docs) | `docs/teacher-lot1-fe-closeout` | đã push, chưa PR (ghi lệch lát 1, STATE) |
| piclass (docs) | `docs/plan-teacher-lot1-fe` | đã push, chưa PR (plan FE lát 1) |

## Cổng
| Cổng | FE |
|---|---|
| Admin | [x] xong |
| Giáo viên | [ ] đang port: lát 1 TC-02..05 code xong, chờ merge |
| Học sinh | [ ] chưa port (có mockup) |
| Phụ huynh | [ ] chưa port (có mockup) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
