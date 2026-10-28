# ARCH-34 — Viền ô nhập/dropdown dùng `--border-subtle` hay `--border-control`

Chuyển nguyên văn từ `open-decisions.md` (2026-10-28). Mục lục và trạng thái: [../open-decisions.md](../open-decisions.md).

**Trạng thái / nội dung:** `ĐÃ CHỐT (2026-10-24)` Nguyên nhân mâu thuẫn: `ds-v2-source/components/forms/Input.jsx`/`Select.jsx` là bản **cũ**, chưa áp kết quả review a11y ở `design-system/CHANGES.md` (bản v2, chốt 2026-10-23). Bản **hiện hành** là `_ds_bundle.js` (biên dịch, dùng trong `design-system/standalone/*.html`) — đã đối chiếu trực tiếp: `Input`/`Select`/`Checkbox`/`Radio`/`Switch` đều dùng `--border-control` (đạt 3:1), không phải `--border-subtle`. Quyết định: `--border-control` là đúng, đã trả `input.tsx`/`select.tsx`/`checkbox.tsx`/`radio.tsx`/`switch.tsx` về `border-control`. Đã đồng bộ lại `ds-v2-source/*.jsx` theo `_ds_bundle.js` (2026-10-24) để không lặp lại nhầm lẫn này.

**Ảnh hưởng:** `components/ui/input.tsx`, `select.tsx`, `checkbox.tsx`, `radio.tsx`, `switch.tsx`; `piclass-mockup/ds-v2-source/components/**`.
