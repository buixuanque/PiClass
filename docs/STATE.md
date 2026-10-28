# STATE — đang ở đâu, làm gì tiếp

File duy nhất cho câu hỏi "đang làm gì / kế tiếp là gì". Giữ ≤ 60 dòng, mỗi mục một dòng, không văn xuôi. Chi tiết: `history/progress-log.md` (lịch sử), `BACKLOG.md` (việc chưa làm). Đổi máy/session mới: chạy `/tiep`.

Cập nhật lần cuối: 2026-10-28 · bởi: tái cấu trúc quản lý tiến độ

## Đang làm
- **Lát:** Tái cấu trúc quản lý tiến độ (STATE/BACKLOG/`/tiep`/`/xong`)
- **Bước:** code xong cả 3 repo (docs, api, web), đang chờ user duyệt
- **Repo/nhánh:** `piclass-docs` · `docs/restructure-state-tracking`; `piclass-api` · `docs/slim-claude-md`; `piclass-web` · `docs/slim-claude-md`
- **Cổng Admin:** xong toàn bộ, đã merge `main` (kể cả 5d: `piclass-api` #58, `piclass-web` #20, docs #114). Chỉ còn user bấm thử trình duyệt.

## Việc kế tiếp (làm từ trên xuống)
1. Duyệt và merge 3 nhánh tái cấu trúc (docs, api, web) — user yêu cầu push thì mới push; thử `/tiep` ở session mới
2. [user] Bấm thử cổng Admin trên trình duyệt; lỗi tìm ra thì thêm vào `BACKLOG.md`
3. Bắt đầu lát **cổng Giáo viên**, bước 1 `working-flow.md`: list chức năng từ API có sẵn + rà mockup (`product/screens/teacher-van-hanh.md`, `teacher-bai-danh-gia.md`, `mockup-plan.md`)
4. Cùng lát Giáo viên: chốt khung điều hướng Admin+Teacher (Q6, CM-10) vì Teacher chưa có shell
5. Rà lại thứ tự ưu tiên `BACKLOG.md` lần đầu cùng user

## Chờ user (Claude không tự làm được)
- Bấm thử Admin trên trình duyệt (xem mục 2)
- Xác nhận `ARCH-36` (a)–(f): UI "thêm hồ sơ kèm tài khoản" dựng không qua mockup
- Duyệt thứ tự ưu tiên `BACKLOG.md`

## Chưa push / chưa merge
| Repo | Nhánh | Trạng thái |
|---|---|---|
| piclass-docs | `docs/restructure-state-tracking` | đã commit local, chưa push |
| piclass-api | `docs/slim-claude-md` | đã commit local, chưa push |
| piclass-web | `docs/slim-claude-md` | đã commit local, chưa push |
| piclass-infra, piclass-mockup | — | `main` sạch, không có PR mở (kiểm 2026-10-28) |

## Cổng
| Cổng | FE |
|---|---|
| Admin | [x] xong |
| Giáo viên | [ ] chưa port (có mockup) |
| Học sinh | [ ] chưa port (có mockup) |
| Phụ huynh | [ ] chưa port (có mockup) |
BE: [x] xong, đã merge `main`.

---
Quy ước bước: `plan xong` → `code BE` → `code FE` → `review` → `chờ merge` → `xong`. Xong lát: chuyển chi tiết sang `history/progress-log.md`, xóa khỏi file này.
