# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-30 · bởi: lát 4b FE TC-10 v2 code xong, chờ review và PR

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
  - Lát 4b (TC-09, TC-10): xong bước 1-4 code, chờ review và PR (`DEC-LRN-022`; `piclass-mockup` #22; BE `search` tên bài cho `GET /assessments`, `piclass-api` #68; đã merge `main`; TC-08 (soạn câu hỏi) đã đơn giản hóa giao diện theo `DEC-LRN-023`, đã merge ở `piclass-web`, `piclass-mockup`, docs)
    - TC-09 + TC-10 v2 (một trang ba tab) đã code trên `piclass-web` nhánh `feat/teacher-lot4b-fe` (chưa PR; test 613 xanh); BE thêm `source_question_version` vào `GET /assessments/{id}`: `piclass-api` nhánh `feat/lot4b-assessment-detail-source-version` (chưa PR, phải merge trước FE); bản wizard 4 bước cũ đã thay hết
    - TC-10 v2: mockup `piclass-mockup` #24, #25, user đã duyệt (`DEC-LRN-024`)
  - Lát 4a (TC-07, TC-08): xong đủ 4 bước, đã merge `main` (`DEC-LRN-020`, `DEC-LRN-021`; `piclass-mockup` #21, `piclass-api` #65-67, `piclass-web` #30)
    - Chưa làm file đính kèm câu hỏi: `B-064`
    - Mockup còn lệch giao diện thật ở khối điền khuyết: `B-065`
  - Lát 3: xong đủ 4 bước, đã merge `main` (`DEC-LRN-019`; `piclass-mockup` #18-20, `piclass-web` #29; không sửa BE)
- **Cổng Admin:** xong toàn bộ, đã merge `main`, user đã bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 4b: user bấm thử TC-09 + TC-10 trên trình duyệt (Tạo bài → thêm câu → giao lớp → xem trước → phát hành), rồi mở PR `piclass-api` (merge trước) và `piclass-web` `feat/teacher-lot4b-fe`
2. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Bấm thử trình duyệt lát 2: `/teacher` (TC-01) và `/teacher/absence-requests` (TC-06) bằng tài khoản Giáo viên; lệch mockup đã biết ở mô tả `piclass-web` #28
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| `piclass-web` | `feat/teacher-lot4b-fe` | 15 commit, đã push, chưa PR; TC-09 + TC-10 v2 đã xong code, chờ user bấm thử rồi PR (sau PR của `piclass-api`) |
| `piclass-api` | `feat/lot4b-assessment-detail-source-version` | 1 commit, đã push, chưa PR; thêm `source_question_version` vào `GET /assessments/{id}` |

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
