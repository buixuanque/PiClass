# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-31 · bởi: code FE lát 4e xong, đang review

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
- **Bước** (quy ước cuối file):
  - Lát 4e (TC-08, `DEC-LRN-025`): quyết định xong → mockup xong (`piclass-mockup` #26, #27) → spec xong → plan FE xong (`docs/superpowers/plans/2026-10-31-fe-question-editor-v3.md`) → code FE xong (`piclass-web` nhánh `feat/question-editor-v3`, đã push, chưa PR) → **review độc lập (đang làm)** → merge
    - Không sửa BE; ô Hiển thị dùng `Question.visibility` của `DEC-LRN-026` (BE đã có)
    - Gồm: màn tạo/sửa cùng khung Soạn bài, soạn nhiều câu một lượt, bảng chọn câu của Soạn bài nhận nhiều câu vừa lưu
  - Làm lại mô hình Khóa học (`DEC-LRN-026`, `ARCH-38`): quyết định xong → BE xong, đã review, đã merge (`piclass-api` #70, docs #156) → mockup chưa làm → FE chưa làm
- **Cổng Admin:** xong toàn bộ, đã merge `main`.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 4e: xử lý kết quả review độc lập, rồi mở PR `piclass-web` và merge (khi user yêu cầu), xem mục Bước
2. Làm lại mô hình Khóa học: mockup AD-12, TC-15, ST-09, TC-07/08/09/10 → plan+code FE (Admin, Giáo viên, Học sinh) theo OpenAPI mới
3. Lát 4c Giáo viên (TC-11, TC-12, TC-13): bàn nghiệp vụ → mockup → plan+code BE/FE; gồm `B-073` (đưa ba cờ hiển thị đáp án/lời giải/điểm từng câu vào hộp công bố điểm)
4. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| piclass-web | `feat/question-editor-v3` | đã push, chưa PR (5 commit) |

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
