# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, không văn xuôi; mục có nhiều ý thì tách thành gạch con, không nhồi một dòng. Việc chưa làm: `BACKLOG.md`. Lịch sử: mô tả PR + `archive/`. Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-11-01 · bởi: code FE lát 4c Giáo viên xong (TC-11…13 + seeder dữ liệu mẫu)

## Đang làm
- **Lát:** cổng Giáo viên, chia 4 lát nhỏ theo thứ tự:
  - Vận hành lớp (TC-02..05) — xong
  - Đơn xin phép & Tổng quan (TC-06, TC-01)
  - Khóa học (TC-15)
  - Bài đánh giá (TC-07..14), chia nhỏ:
    - 4a: TC-07, TC-08 — xong
    - 4b: TC-09, TC-10 — xong
    - 4e: TC-08 soạn câu hỏi cùng khung Soạn bài, soạn nhiều câu — xong
    - 4c: TC-11, TC-12, TC-13 — nghiệp vụ đã chốt (`DEC-LRN-027`), mockup đã sửa, đã merge (`piclass-mockup` #28); plan BE xong (`docs/superpowers/plans/2026-11-01-be-teacher-grading-4c.md`), code BE xong (`piclass-api` `feat/teacher-grading-4c`, 10 task, đã push), đã review độc lập (opus), sửa 5 phát hiện, đã merge (`piclass-api` #75); plan FE xong, code FE xong (`piclass-web` `feat/teacher-grading-4c-fe`) kèm seeder dữ liệu mẫu (`piclass-api` `feat/teacher-grading-demo-seed`, cách chạy: `docs/setup/dev-demo-data.md`), đã review độc lập (opus) và sửa 5 phát hiện Important, đã merge (`piclass-api` #76, #77, `piclass-web` #46); bước kế: lát 4d (TC-14)
    - 4d: TC-14
- **Bước** (quy ước cuối file):
  - Làm lại mô hình Khóa học (`DEC-LRN-026`, `ARCH-38`): quyết định xong → BE xong, đã review, đã merge (`piclass-api` #70, docs #156) → không làm mockup (user chốt 2026-10-31), FE làm từng cổng: Admin xong, đã merge (`piclass-web` #36), Giáo viên xong, đã merge → Học sinh chưa làm (hoãn đến sau khi xong cổng Giáo viên)
- **Cổng Admin:** xong toàn bộ, đã merge `main`.

## Việc kế tiếp (làm từ trên xuống)
1. Lát 4c Giáo viên (TC-11, TC-12, TC-13): nghiệp vụ đã chốt, mockup đã sửa, đã merge, code BE xong và đã merge, code FE xong, đã review độc lập, đã merge; gồm `B-073` (đưa ba cờ hiển thị đáp án/lời giải/điểm từng câu vào hộp công bố điểm). User chốt 2026-10-31: hoàn thiện cổng Giáo viên trước
2. Lát 4d Giáo viên (TC-14), xong thì cổng Giáo viên đóng
3. Làm lại mô hình Khóa học, FE cổng Học sinh (ST-09) theo OpenAPI mới, không mockup
4. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Quyết định về PHPStan: 30 lỗi mới cùng loại D-22 ở `piclass-api` (cho cập nhật baseline hay làm riêng việc gõ kiểu relation)
- Scheduler: user chốt 2026-11-01 chưa cần chạy `schedule:run`, nên hẹn giờ công bố điểm (BE đã có) chưa hoạt động; FE lát 4c phải ẩn hoặc vô hiệu tùy chọn "Hẹn giờ công bố" cho tới khi có scheduler
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| — | — | không có nhánh chờ |

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
