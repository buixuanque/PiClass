# Thiết kế: Template repo quy trình phát triển dự án (dùng lại cho dự án sau)

**Ngày:** 2026-10-15
**Trạng thái:** Đã duyệt thiết kế (brainstorming), chờ viết implementation plan.

## Mục đích

PiClass đã chạy quy trình Step 1-9 (Product Definition → Feature Map → User Flow →
Domain Model → Business Rules → Database → Access Control → Architecture → API
Specification) được một thời gian, có `CLAUDE.md`, `docs/progress.md`, decision log
mã số, và từ Step 9 có pattern triển khai theo nhóm module: spec (brainstorming) →
plan BE → code BE → plan FE → code FE, song song với track design system riêng.

Mục tiêu: đúc kết cách làm việc này thành một **template repo tổng quát**, dùng lại
khi bắt đầu dự án mới — không phải để chứa nội dung nghiệp vụ của PiClass, mà để
thống nhất cách làm việc + quy trình.

## Phạm vi

- **Tổng quát, không gắn stack**: không có Laravel/Next.js/PostgreSQL cụ thể. Phần
  "Stack hiện tại" trong `CLAUDE.md` mẫu để trống, điền riêng cho từng dự án.
- **Không chứa nội dung nghiệp vụ** của PiClass: không có decision log thật, schema
  thật, business rule thật. Những thứ đó ở lại trong `piclass-docs`.
- **Đứng riêng** — một repo mới, không phải fork/nhánh của `piclass-docs`.
- Pattern BE→FE ở Step 9 **được đưa vào ngay**, nhưng đánh dấu rõ phần FE là
  "chưa verify thực tế" (PiClass chưa hoàn thành FE nhóm module nào tại thời điểm
  viết tài liệu này) — cần quay lại rà soát template sau khi PiClass hoàn thành ít
  nhất 1 nhóm module FE.

## Nội dung template repo

Tên gợi ý: `project-docs-template`.

### 1. `CLAUDE.md` mẫu

Giữ cấu trúc hiện tại của `piclass/CLAUDE.md`:
- Điểm bắt đầu đọc: `docs/progress.md`.
- Thứ tự ưu tiên tài liệu khi có mâu thuẫn (requirements → access-control →
  database → product/flows/domain → architecture) — generalize tên nhóm tài liệu
  cho phù hợp dự án khác, giữ nguyên logic "business rule thắng kiến trúc".
- Quy tắc cập nhật ngay tài liệu nguồn khi chốt quyết định mới (không để quyết định
  chỉ tồn tại trong lịch sử chat).
- Quy ước ngôn ngữ (tài liệu/trao đổi vs code/commit) — giữ dạng câu hỏi mở, không
  hard-code tiếng Việt/tiếng Anh.
- Phần "Stack hiện tại": để `[TODO: điền stack dự án này]`.
- Phần "Thứ tự triển khai Step 9": giữ nguyên pattern nhóm module, dẫn sang
  `docs/METHODOLOGY.md` để biết chi tiết.

### 2. `docs/METHODOLOGY.md` (mới — chưa tồn tại ở PiClass)

Tài liệu đúc kết quy trình, viết một lần, generic cho mọi dự án:

- **Step 1-9**: mục đích từng Step, input/output, tài liệu nào được tạo ra.
- **Decision log**: lý do cần nó (tránh quyết định "mồ côi" chỉ nằm trong chat),
  cách đặt mã (`DEC-xxx`), khi nào ghi vào `open-decisions.md` thay vì chốt luôn.
- **Pattern triển khai Step 9 theo nhóm module**: spec → plan BE → code BE → plan FE
  → code FE, tuần tự theo nhóm, không dồn hết spec 1 lượt. Ghi rõ box cảnh báo:
  > Phần FE của pattern này dựa trên quyết định đã thống nhất ở dự án PiClass,
  > **chưa được kiểm chứng qua thực tế code FE**. Rà lại sau khi dự án nguồn hoàn
  > thành ít nhất 1 nhóm module FE.
- **Track song song không phụ thuộc BE**: design system / UI exploration (Stitch
  hoặc tương đương) — làm một lần, không lặp lại mỗi nhóm module.
- **Vai trò các skill Claude Code** (superpowers: brainstorming/writing-plans/
  executing-plans/code-review) trong từng giai đoạn — mô tả ở mức "skill nào dùng ở
  đâu", không copy nội dung skill.

### 3. Khung thư mục `docs/` rỗng + README stub

Mỗi thư mục có 1 file `README.md` ngắn giải thích mục đích, dựa 1-1 theo cấu trúc
PiClass đang dùng:

```
docs/
  product/README.md        — mục tiêu, scope, Step 1-2
  flows/README.md           — user flow, Step 3
  domain/README.md          — domain model + glossary, Step 4
  requirements/README.md    — business rules + decision log, Step 5
  database/README.md        — schema, quan hệ, lifecycle, Step 6
  access-control/README.md  — role/permission/data scope, Step 7
  architecture/README.md    — technology-decisions, open-decisions, Step 8
  superpowers/specs/.gitkeep
  superpowers/plans/.gitkeep
```

### 4. `docs/progress.md` mẫu

Bảng trạng thái theo Step (rỗng), cộng hướng dẫn ngắn: cập nhật file này mỗi khi có
quyết định mới hoặc chuyển giai đoạn, không chỉ dựa vào lịch sử chat — copy nguyên
tinh thần dòng mục đích đang có ở `piclass/docs/progress.md`.

### 5. `docs/requirements/decision-log.md` mẫu + `docs/architecture/open-decisions.md` mẫu

File rỗng có sẵn format bảng (mã quyết định, nội dung, ngày, lý do) để không phải
nhớ lại format mỗi lần bắt đầu dự án mới.

## Không nằm trong phạm vi

- Không setup CI/CD mẫu, không Docker Compose mẫu (đó là lựa chọn hạ tầng riêng
  từng dự án, không phải "quy trình làm tài liệu/code").
- Không copy nguyên văn các skill `superpowers`/`ui-ux-pro-max` — những skill đó cài
  riêng theo máy (xem `docs/setup/claude-skills-setup.md` của PiClass), template chỉ
  *tham chiếu* tới chúng.
- Không đồng bộ tự động giữa `piclass-docs` và template này — khi PiClass học được
  điều chỉnh quy trình mới, phải **cập nhật tay** vào template (ghi chú ở
  `docs/progress.md` của PiClass để không quên).

## Cách dùng khi bắt đầu dự án mới

1. Clone/degit `project-docs-template` thành repo docs của dự án mới.
2. Điền phần "Stack hiện tại" trong `CLAUDE.md`.
3. Bắt đầu Step 1 theo `docs/METHODOLOGY.md`, điền dần vào khung thư mục có sẵn.

## Việc cần làm sau (không chặn version đầu)

- Sau khi PiClass hoàn thành Plan 1b/2b (FE Auth + User Management), rà lại mục
  "Pattern triển khai Step 9" trong `METHODOLOGY.md` của template, bỏ cảnh báo
  "chưa verify" nếu pattern đúng như mô tả, hoặc sửa lại nếu thực tế khác.
