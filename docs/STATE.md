# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-31 · bởi: duyệt mockup TC-08 (lát 4e), spec TC-08 đồng bộ

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự:
  - Vận hành lớp (TC-02..05) — xong
  - Đơn xin phép & Tổng quan (TC-06, TC-01)
  - Khóa học (TC-15)
  - Bài đánh giá (TC-07..14), chia nhỏ:
    - 4a: TC-07, TC-08 — xong
    - 4b: TC-09, TC-10 — xong
    - 4e (chen trước 4c): làm lại TC-08 theo `DEC-LRN-025` (cùng khung Soạn bài, soạn nhiều câu một lượt) — **đang làm**
    - 4c: TC-11, TC-12, TC-13
    - 4d: TC-14
- **Bước:**
  - Lát 4b (TC-09, TC-10): xong đủ 4 bước, đã merge `main` (`DEC-LRN-022`, `DEC-LRN-023`, `DEC-LRN-024`; `piclass-mockup` #22, #24, #25; `piclass-api` #68, #69; `piclass-web` #31, #32 và sửa Minor sau review)
  - Lát 4a (TC-07, TC-08): xong đủ 4 bước, đã merge `main` (`DEC-LRN-020`, `DEC-LRN-021`; `piclass-mockup` #21, `piclass-api` #65-67, `piclass-web` #30)
    - Chưa làm file đính kèm câu hỏi: `B-064`
    - Mockup còn lệch giao diện thật ở khối điền khuyết: `B-065`
  - Lát 3: xong đủ 4 bước, đã merge `main` (`DEC-LRN-019`; `piclass-mockup` #18-20, `piclass-web` #29; không sửa BE)
- **Cổng Admin:** xong toàn bộ, đã merge `main`.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 4e (`DEC-LRN-025`): bước 1 quyết định xong, bước 2 mockup xong (đã merge `piclass-mockup` #26), bước 3 spec TC-08 xong → **bước 4 FE** (`piclass-web`, không sửa BE): màn tạo/sửa câu hỏi cùng khung Soạn bài, soạn nhiều câu một lượt, bảng chọn câu của Soạn bài nhận nhiều câu vừa lưu → review (opus) → merge
2. Lát 4c Giáo viên (TC-11, TC-12, TC-13): bàn nghiệp vụ → mockup → plan+code BE/FE; gồm `B-073` (đưa ba cờ hiển thị đáp án/lời giải/điểm từng câu vào hộp công bố điểm)
3. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| (không có) | — | mọi repo `main` sạch (kiểm 2026-10-30, sau `piclass-web` #32 và `piclass-api` #69) |

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
