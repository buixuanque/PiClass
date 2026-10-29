# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, mỗi mục một dòng, không văn xuôi. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-29 · bởi: xong code FE lát 3 (bước 4)

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự: Vận hành lớp (TC-02..05, xong) → Đơn xin phép & Tổng quan (TC-06, TC-01) → Khóa học (TC-15) → Bài đánh giá (TC-07..14). Lát 2 xong, đang ở lát 3.
- **Bước:** lát 2 xong đủ 4 bước, đã merge `main` (`DEC-LRN-018`; `piclass-api` #64, `piclass-web` #28; bản gốc mockup ở `piclass-mockup/design-system/standalone/original/`); lát 3 (Khóa học, TC-15) xong bước 1-3 (`DEC-LRN-019`: 3 tab Tất cả/Của tôi/Dùng chung, khóa chung chỉ xem không có tab, V1 giữ hướng A, hướng sau V1 ở `B-062`; mockup `piclass-mockup` #18 và nhánh `docs/teacher-lot3-shared-course`; API đủ, không sửa BE); mockup có thêm tab Lớp đã gắn (`DEC-LRN-019` mục 5, đã merge); FE đã code, nhánh `feat/teacher-lot3-fe` của `piclass-web` (test xanh, chờ user bấm thử rồi merge)
- **Cổng Admin:** xong toàn bộ, đã merge `main`, user đã bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 3 Giáo viên: user bấm thử `/teacher/courses` (TC-15), xong thì merge `piclass-web` `feat/teacher-lot3-fe`; sau đó lát 4 (Bài đánh giá, TC-07..14) bước 1: bàn nghiệp vụ
2. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Bấm thử trình duyệt lát 2: `/teacher` (TC-01) và `/teacher/absence-requests` (TC-06) bằng tài khoản Giáo viên; lệch mockup đã biết ở mô tả `piclass-web` #28
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
| Giáo viên | [ ] đang port: lát 1 (TC-02..05) và lát 2 (TC-06, TC-01) xong, đã merge `main`; còn 2 lát |
| Học sinh | [ ] chưa port (có mockup) |
| Phụ huynh | [ ] chưa port (có mockup) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
