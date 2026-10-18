# Báo cáo: Impeccable (pbakaus/impeccable) — có nên dùng cho PiClass?

## 1. Impeccable là gì, giải quyết vấn đề gì

Impeccable là một bộ công cụ mã nguồn mở của Paul Bakaus, được đóng gói thành **skill** (bộ hướng dẫn + lệnh) cho các AI coding agent (Claude Code, Cursor, GitHub Copilot, Codex, Grok Build...). Mô tả chính thức: "ngôn ngữ thiết kế giúp AI harness của bạn làm thiết kế tốt hơn".

Vấn đề nó giải quyết: AI hiện nay khi tạo UI thường lặp lại một số "khuôn mẫu AI" rất dễ nhận ra (font Inter mặc định, gradient tím-xanh, card lồng trong card, chữ xám nhạt trên nền màu...). Impeccable cung cấp một bộ 59 "luật detector" xác định (deterministic — tức kiểm tra bằng code, không phải chỉ AI "cảm nhận") để tự động phát hiện các mẫu này, cộng với các lệnh hướng dẫn AI tự sửa (critique, polish, harden...).

Lưu ý: đây **không phải** agent hỏi-đáp nghiệp vụ hay công cụ sinh business logic — nó chỉ tập trung vào phần **thẩm mỹ/chất lượng giao diện (UI polish)**, không liên quan đến code backend, security hay accessibility sâu (dù có lệnh `harden` hỗ trợ một phần accessibility cơ bản).

## 2. Cơ chế hoạt động chính

- **Skill + 24 lệnh** dạng `/impeccable <lệnh> <đối tượng>`, ví dụ: `init` (tạo file `PRODUCT.md` mô tả sản phẩm — bắt buộc chạy trước), `shape` (định hình layout), `craft`, `critique` (AI tự chê bai thiết kế của chính mình), `audit`, `polish`, `harden` (tăng độ tương phản/accessibility cơ bản), `bolder`, `quieter`, `distill`, `colorize`, `typeset`, `layout`, `animate`, `delight`, `adapt`...
- **Hooks**: trên Claude Code và các tool khác, cài đặt sẽ gắn thêm hook tự động chạy bộ detector sau mỗi lần AI sửa file UI — một số tool (như Cursor) còn chặn ghi file nếu phát hiện mẫu xấu. Cần xác nhận tin cậy (trust) hook khi cài vào Claude Code.
- **CLI riêng** (`npx impeccable detect`): quét một thư mục, file HTML, hoặc URL để tìm 59 mẫu lỗi, xuất JSON — dùng được trong CI pipeline để chặn PR có UI "nhìn giống AI làm".
- **Agent phụ**: có một subagent tên `asset-producer` trong thư mục `agents/` của skill (README không mô tả chi tiết).
- **Không có MCP server** — repo không đề cập cơ chế MCP.
- **Telemetry**: mặc định gửi một bản ghi ẩn danh khi bắt đầu thiết kế mới; tắt được bằng `npx impeccable telemetry off` hoặc biến môi trường `DO_NOT_TRACK=1` — cần lưu ý nếu công ty có chính sách không gửi dữ liệu ra ngoài.

## 3. Cài đặt & dùng cơ bản

- **Cách khuyến nghị (CLI)**: chạy `npx impeccable install` ở gốc dự án, cập nhật bằng `npx impeccable update`.
- **Plugin cho Claude Code**: `/plugin marketplace add pbakaus/impeccable` (giống cách PiClass đang cài `superpowers`/`ui-ux-pro-max` hiện nay).
- Cách khác: git submodule + `npx impeccable link`, hoặc tải ZIP thủ công từ impeccable.style.
- Dùng: chạy `/impeccable init` trước (tạo `PRODUCT.md`), sau đó gọi các lệnh như `/impeccable audit`, `/impeccable critique`, `/impeccable polish` lên component/trang cụ thể.

## 4. Ưu điểm — Nhược điểm

**Ưu điểm:**
- Giải quyết đúng một nỗi đau rất thật: UI do AI tạo "nhìn giống AI làm", dễ nhận ra, kém chuyên nghiệp.
- Detector chạy bằng code (deterministic) thay vì chỉ dựa cảm nhận của AI → kết quả lặp lại được, có thể gắn vào CI.
- Tích hợp hook tự động, không cần nhớ chạy tay mỗi lần.
- License Apache-2.0 (dùng tự do, kể cả thương mại).

**Nhược điểm/giới hạn:**
- Chỉ xử lý phần "nhìn" (visual polish) — không đụng tới business logic, kiến trúc, hay accessibility toàn diện.
- Thêm một tầng hook/hộp công cụ vào workflow hiện có của Claude Code — cần test không xung đột với các skill đang dùng (`superpowers`, `ui-ux-pro-max`, `frontend-design`).
- Có gửi telemetry mặc định (tắt được, nhưng cần nhớ tắt nếu cần).
- Dự án còn khá non (mới tạo 11/2025), nên API/lệnh có thể còn thay đổi nhanh — cần theo dõi khi `npx impeccable update`.

## 5. Độ trưởng thành

- **Tạo**: 16/11/2025. **Commit gần nhất**: 16/10/2026 (hôm nay, tại thời điểm research) → maintainer vẫn đang hoạt động rất tích cực.
- **~2.471 commit** trong khoảng 11 tháng → tốc độ phát triển rất nhanh (có thể hiểu là vẫn đang thay đổi nhiều, chưa ổn định hoàn toàn).
- **~81.9k sao, ~4.9k fork, 44 issue mở** → độ phổ biến cực lớn trong giới AI-coding, đáng tin về mặt cộng đồng dùng.
- **License**: Apache-2.0 (nguồn mở, cho phép dùng thương mại).
- Tác giả Paul Bakaus có uy tín lâu năm trong ngành (cựu Google, nhiều dự án UI/design nổi tiếng) — không phải tác giả vô danh.

## 6. Khuyến nghị cho PiClass

**Nên dùng — nhưng chỉ ở vai trò bổ sung cho bước "polish" UI, không thay thế quy trình design hiện tại.**

Lý do dựa trên ngữ cảnh PiClass hiện tại (`docs/progress.md`):
- PiClass đang ở giai đoạn làm lại mockup/design system (`MASTER.md`) bằng Claude Design, và FE của 5 nhóm module (Step 9) vẫn chưa code. Đây đúng là thời điểm Impeccable phát huy giá trị nhất — chạy `/impeccable audit`/`critique`/`polish` trên mockup hoặc trang Next.js mới code, để bắt sớm các lỗi "nhìn giống AI" (font Inter mặc định, gradient tím-xanh...) trước khi chốt `MASTER.md`.
- PiClass đã có 2 skill liên quan thiết kế: `ui-ux-pro-max` (chốt design system) và `frontend-design` (checklist review cuối). Impeccable không trùng vai trò với 2 skill này — nó là lớp kiểm tra tự động, kỹ thuật hơn (detector chạy bằng code), nên có thể dùng **sau** khi `ui-ux-pro-max` đã chốt token/design system, làm bước kiểm tra chất lượng bổ sung ở review cuối cùng cùng với `frontend-design`.
- Không nên dùng cho code quality backend (Laravel) hay business logic — nằm ngoài phạm vi của tool này.
- Khuyến nghị cụ thể: cài thử (plugin marketplace, scope local giống các skill khác, không commit git theo quy ước `docs/setup/claude-skills-setup.md`), tắt telemetry (`DO_NOT_TRACK=1`) để tránh gửi dữ liệu dự án ra ngoài, và chỉ dùng các lệnh `audit`/`critique`/`polish`/`harden` ở bước review UI cuối của mỗi nhóm module FE (Plan Nb), không bắt buộc chạy mọi lệnh trong 24 lệnh có sẵn.

## Nguồn tham khảo

- README chính thức: https://github.com/pbakaus/impeccable
- GitHub API (số liệu sao/fork/license/ngày): https://api.github.com/repos/pbakaus/impeccable
- Trang chủ dự án: https://impeccable.style (chưa fetch trực tiếp, chỉ nhắc tới qua README)
- Nguồn cộng đồng (chưa xác minh đầy đủ, chỉ dùng tham khảo bổ sung, số liệu có thể lệch do cache cũ): claude-codex.fr (https://claude-codex.fr/fr/skills/impeccable), skillselion.com, zerotomastery.io blog "best claude code plugins", news.hada.io — các trang này có đưa số liệu sao/lệnh không khớp hoàn toàn với GitHub API hiện tại, nên ưu tiên số liệu từ GitHub API ở trên.
