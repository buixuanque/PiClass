# Vòng đời làm việc: đọc gì, ghi gì

Tổng quan nhịp làm việc giữa `/tiep`, làm theo lát và `/xong`. Các bước của một lát xem [working-flow.md](working-flow.md); quy tắc git và tiến độ ở `CLAUDE.md`.

## 1. Tổng quan

```
 ┌────────────┐     ┌─────────────────────────────────┐     ┌────────────┐
 │  /tiep     │ ──► │  LÀM VIỆC THEO LÁT              │ ──► │  /xong     │
 │ (bắt đầu)  │     │  (lặp: đơn vị việc → push)      │     │ (chốt sổ)  │
 └────────────┘     └─────────────────────────────────┘     └─────┬──────┘
   chỉ ĐỌC                                                        │
   + báo cáo                                                      ▼
                                                        hỏi: đưa lên main không?
                                                        có → PR → merge (user chốt)
```

## 2. Bên trong "làm việc theo lát"

```
 bàn nghiệp vụ → mockup → plan+code BE → plan+code FE → chạy thử cả lát → cập nhật tài liệu → ship
                          └── BE và FE là hai plan riêng, xen kẽ ──┘

 Mỗi ĐƠN VỊ VIỆC xong (test + code + tài liệu của một mục, test xanh):
     ├─► git push nhánh feature (không force, không amend, không đụng main)
     ├─► đối chiếu "Việc kế tiếp" + "Chờ user" của STATE.md
     │       └─ có mục có thể đã xong → AskUserQuestion → chỉ sửa theo mục user xác nhận
     └─► kiểm docs repo có commit chưa push → báo cùng lượt
```

Phân biệt hai nhịp: **đơn vị việc** là nhịp nhỏ, nhiều lần trong một bước, mục đích là đẩy code lên `origin`. **`/xong`** là nhịp lớn, một lần khi đóng bước hoặc session, mục đích là làm `STATE.md` khớp git thật.

## 3. Đọc gì, ghi gì

| Thời điểm | Đọc | Ghi / cập nhật |
|---|---|---|
| `/tiep` (đầu session, đổi máy) | `docs/STATE.md`; git 5 repo (`fetch`, `status`, `log origin/main..HEAD`, `gh pr list`). `BACKLOG.md` chỉ khi "Việc kế tiếp" hết. | Không ghi. Thấy lệch thì chỉ đề xuất. |
| Bàn nghiệp vụ / brainstorming | `requirements/*` → `access-control/*` → `database/*` → `product`, `flows`, `domain` → `architecture/*` | `decision-log.md`, `open-decisions.md`, `technology-decisions.md` (ghi ngay, không để chỉ trong chat) |
| Plan | Các tài liệu nguồn trên; spec màn đọc theo đoạn (`grep -n` rồi `Read` offset/limit) | `docs/superpowers/specs/`, `plans/` |
| Code BE | `piclass-api/CLAUDE.md`, `docs/rules/*`, `backend-gotchas.md`, `known-debt.md` (mục liên quan) | Code, test, `openapi.json` (export lại trước commit) |
| Code FE | `piclass-web/CLAUDE.md`, `piclass-api/storage/api-docs/openapi.json` | Code, test |
| Việc phát sinh chưa làm | | Một dòng vào `BACKLOG.md` (ID `B-xxx`) |
| Mỗi đơn vị việc xong | `STATE.md` (đối chiếu) | Push nhánh; `STATE.md` chỉ sửa theo mục user xác nhận |
| `/xong` | Git 5 repo | `STATE.md` (Đang làm, Việc kế tiếp, Chờ user, các bảng, "Cập nhật lần cuối"); `BACKLOG.md`; `decision-log.md`; lát xong thì `git mv` spec/plan sang `archive/superpowers/`; commit và push nhánh docs |
| Đưa lên `main` (chỉ khi user nói có) | | Nhánh đã push → PR tiếng Việt → merge; không xóa nhánh |

## 4. Không đọc mặc định

- `docs/BACKLOG.md`: chỉ mở khi hết việc kế tiếp.
- `docs/archive/*`: rất lớn (~2MB); chỉ mở đúng một file khi có link tới, không grep cả thư mục.
- Phiên chỉ hỏi đáp thì không đọc và không sửa `STATE.md`.

## 5. Tự động và cần user

| Tự làm | Chờ user |
|---|---|
| `fetch`, branch mới từ `origin/main`, commit | Mở PR |
| Push nhánh feature và nhánh docs | Merge |
| Hỏi xác nhận mục đã xong | Sửa mục `[user]` trong `STATE.md` (không tự suy ra là xong) |
| | Force-push, `--amend` commit đã push |
