# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-30 · bởi: user duyệt mockup TC-10 v2 (`DEC-LRN-024`)

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
  - Lát 4b (TC-09, TC-10): xong bước 1-3, đang ở bước 4 FE (`DEC-LRN-022`; `piclass-mockup` #22; BE `search` tên bài cho `GET /assessments`, `piclass-api` #68; đã merge `main`; TC-08 (soạn câu hỏi) đã đơn giản hóa giao diện theo `DEC-LRN-023`, đã merge ở `piclass-web`, `piclass-mockup`, docs)
    - TC-09 + TC-10 bản wizard 4 bước đã code, qua review, nhánh `piclass-web` `feat/teacher-lot4b-fe` (chưa PR); user bấm thử thấy rối (stepper đổi số thành ✓, thêm đối tượng trùng, thiếu xem trước)
    - TC-10 v2 (một trang, tab Câu hỏi/Đối tượng/Cài đặt, danh sách tick, Xem trước luôn hiện, kiểu Google Form): mockup `piclass-mockup` #24, #25 (v2 là mockup chính thức), user đã duyệt (`DEC-LRN-024`); chờ làm lại FE theo v2
  - Lát 4a (TC-07, TC-08): xong đủ 4 bước, đã merge `main` (`DEC-LRN-020`, `DEC-LRN-021`; `piclass-mockup` #21, `piclass-api` #65-67, `piclass-web` #30)
    - Chưa làm file đính kèm câu hỏi: `B-064`
    - Mockup còn lệch giao diện thật ở khối điền khuyết: `B-065`
  - Lát 3: xong đủ 4 bước, đã merge `main` (`DEC-LRN-019`; `piclass-mockup` #18-20, `piclass-web` #29; không sửa BE)
- **Cổng Admin:** xong toàn bộ, đã merge `main`, user đã bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 4b bước 4: sửa spec TC-10 theo `DEC-LRN-024` → làm lại FE TC-10 theo v2 trên `feat/teacher-lot4b-fe` (giữ hook API, logic kiểm tra phát hành; B-070..072) → review → PR
2. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Bấm thử trình duyệt lát 2: `/teacher` (TC-01) và `/teacher/absence-requests` (TC-06) bằng tài khoản Giáo viên; lệch mockup đã biết ở mô tả `piclass-web` #28
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| `piclass-web` | `feat/teacher-lot4b-fe` | 9 commit, đã push, chưa PR; TC-10 trong nhánh là wizard cũ, sẽ làm lại theo v2 rồi mới PR (TC-09 dùng được) |

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
