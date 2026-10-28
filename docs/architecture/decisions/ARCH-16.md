# ARCH-16 — Nguồn design system (MASTER.md) sau khi mất file

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT (2026-10-16)` Phát hiện: `docs/design/MASTER.md` (design system chốt từ batch Stitch đầu) đã bị xoá từ commit `cf93fb9` (2026-10-13) và không được khôi phục — `progress.md` vẫn ghi "đã chốt" nhưng thực tế file không còn tồn tại. Quyết định: không khôi phục bản cũ; design system sẽ được build lại từ batch mockup mới đang làm trên Claude Design (claude.ai/design), không tiếp tục dùng Stitch. `piclass-mockup` đã `git init` lại từ đầu (xoá sạch `.git` cũ, không giữ lịch sử Stitch/ui-ux-pro-max) để làm sạch cho batch mockup mới.

**Ảnh hưởng:** Frontend plan FE (mọi tham chiếu "MASTER.md" ở `progress.md`/`CLAUDE.md` cần viết lại khi batch mockup mới chốt xong design system). **Cập nhật 2026-10-23:** design system v2 đã chốt (`piclass-mockup/design-system/`, token ở `tokens/*.css`, component ở `_ds_bundle.js`); mockup làm trong Claude Code thay cho Claude Design. Các tham chiếu MASTER.md ở `progress.md` và `CLAUDE.md` đã viết lại.
