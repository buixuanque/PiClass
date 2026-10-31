# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-31 · bởi: lát 2 cổng Giáo viên đã merge (web #40)

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự:
  - Vận hành lớp (TC-02..05) — xong
  - Đơn xin phép & Tổng quan (TC-06, TC-01)
  - Khóa học (TC-15)
  - Bài đánh giá (TC-07..14), chia nhỏ:
    - 4a: TC-07, TC-08 — xong
    - 4b: TC-09, TC-10 — xong
    - 4e: TC-08 soạn câu hỏi cùng khung Soạn bài, soạn nhiều câu — xong
    - 4c: TC-11, TC-12, TC-13
    - 4d: TC-14
- **Bước** (quy ước cuối file):
  - Làm lại mô hình Khóa học (`DEC-LRN-026`, `ARCH-38`): quyết định xong → BE xong, đã review, đã merge (`piclass-api` #70, docs #156) → không làm mockup (user chốt 2026-10-31), FE làm từng cổng: Admin code xong, đã review độc lập, đã mở PR chờ merge (`piclass-web` `feat/admin-course-rework`, plan `docs/superpowers/plans/2026-10-31-fe-admin-course-rework.md`) → Giáo viên, Học sinh chưa làm
- **Cổng Admin:** xong toàn bộ, đã merge `main`.

## Việc kế tiếp (làm từ trên xuống)
1. Làm lại mô hình Khóa học, FE từng cổng theo OpenAPI mới, không mockup: Giáo viên (TC-15, rồi dọn TC-10/09/07/08) theo plan 2 lát: lát 1 xong và đã merge (gồm màn soạn bài trong khóa, ô soạn câu inline, API hàng loạt); lát 2 xong và đã merge (web #40) (bỏ thẻ "Khóa học" ở đối tượng TC-10, xóa code ghi danh/liên kết lớp cũ, gộp tab Đối tượng & học viên, Xem trước hiện thẻ đang soạn) → Học sinh (ST-09)
2. Lát 4c Giáo viên (TC-11, TC-12, TC-13): bàn nghiệp vụ → mockup → plan+code BE/FE; gồm `B-073` (đưa ba cờ hiển thị đáp án/lời giải/điểm từng câu vào hộp công bố điểm)
3. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| `piclass-docs` | `docs/teacher-course-lat2` | TC-15 viết lại, tab gộp, B-081; chưa mở PR |

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
