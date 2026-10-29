# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-29 · bởi: lát 4b xong bước 3 (BE)

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự:
  - Vận hành lớp (TC-02..05) — xong
  - Đơn xin phép & Tổng quan (TC-06, TC-01)
  - Khóa học (TC-15)
  - Bài đánh giá (TC-07..14), chia nhỏ:
    - 4a: TC-07, TC-08 — xong
    - 4b: TC-09, TC-10 — **đang làm**
    - 4c: TC-11, TC-12, TC-13
    - 4d: TC-14
- **Bước:**
  - Lát 4b (TC-09, TC-10): xong bước 1-3 (`DEC-LRN-022`; `piclass-mockup` #22; BE `search` tên bài cho `GET /assessments`, `piclass-api` #68; đã merge `main`; TC-08 (soạn câu hỏi) đã đơn giản hóa giao diện theo `DEC-LRN-023`, đã merge ở `piclass-web`, `piclass-mockup`, docs)
  - Lát 4a (TC-07, TC-08): xong đủ 4 bước, đã merge `main` (`DEC-LRN-020`, `DEC-LRN-021`; `piclass-mockup` #21, `piclass-api` #65-67, `piclass-web` #30)
    - Chưa làm file đính kèm câu hỏi: `B-064`
    - Mockup còn lệch giao diện thật ở khối điền khuyết: `B-065`
  - Lát 3: xong đủ 4 bước, đã merge `main` (`DEC-LRN-019`; `piclass-mockup` #18-20, `piclass-web` #29; không sửa BE)
- **Cổng Admin:** xong toàn bộ, đã merge `main`, user đã bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 4b Giáo viên bước 4: FE (TC-09 ô tìm tên bài dùng `search`, bỏ Nhân bản và cột đã nộp; TC-10 soạn và giao bài)
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
| Giáo viên | [ ] đang port: lát 1 (TC-02..05), lát 2 (TC-06, TC-01) và lát 3 (TC-15) xong, đã merge `main`; còn 1 lát (Bài đánh giá) |
| Học sinh | [ ] chưa port (có mockup) |
| Phụ huynh | [ ] chưa port (có mockup) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: xóa khỏi file này (chi tiết đã ở mô tả PR), chuyển spec/plan sang `archive/`.
