# MASTER.md — PiClass Design System

Nguồn gốc: hệ thống hóa từ bộ token/màn hình do Stitch sinh ra tại
`piclass-mockup/stitch_piclass_learning_management_system/` (10 màn hình Identity/Auth +
Admin User Management + 4 Dashboard theo role, cùng file `design-system/DESIGN.md`).

File này là **nguồn chính thức duy nhất** cho design token của PiClass. Khi code FE
(Next.js), tham chiếu theo `MASTER.md`, không hardcode giá trị thô.

## Quyết định đã chốt (Decision Log của track UI/UX)

| Mã | Quyết định | Lý do |
|----|------------|-------|
| UI-DEC-001 | Màu primary chính thức: `#1E40AF` | `DESIGN.md` gốc do Stitch sinh có mâu thuẫn: frontmatter YAML ghi `#00288e`, phần mô tả prose ghi `#1E40AF`. Chọn `#1E40AF` vì toàn bộ spec component (button, input, checkbox...) đều dùng giá trị này — nhất quán hơn. |
| UI-DEC-002 | Font chữ: `Plus Jakarta Sans` cho toàn hệ thống | Hỗ trợ tốt dấu tiếng Việt (ệ, ở, ứ...), độ phân biệt ký tự cao (1, l, I), phù hợp người dùng không chuyên IT (giáo viên, phụ huynh). |
| UI-DEC-003 | Bo góc theo cấp độ "Rounded (Level 2)", không dùng góc vuông 90° | Tạo cảm giác thân thiện, giảm cảm giác "khô cứng/đáng sợ" cho phụ huynh/học sinh. |

---

## 1. Nguyên tắc thiết kế

PiClass phục vụ hệ sinh thái nhiều vai trò (Admin, Teacher, Student, Parent) với mức độ
thành thạo công nghệ khác nhau. Định hướng thị giác: **Modern Academic & Human-Centric**.

- **Cấu trúc rõ ràng, dễ đọc**: khối nội dung gọn, nhiều khoảng trắng, điều hướng tự giải
  thích — giáo viên không cần training manual để quản lý lớp.
- **Chuyên nghiệp nhưng gần gũi**: đủ uy tín để phụ huynh tin tưởng (liên quan học phí),
  nhưng không gây áp lực/lo lắng cho học sinh.
- **Rõ ràng khi chạm (tactile clarity)**: ranh giới rõ, trạng thái tương tác dễ đoán, kích
  thước chạm đủ lớn — dùng tốt từ dashboard chấm điểm trên desktop đến portal phụ huynh
  trên mobile.

---

## 2. Color Tokens

### 2.1 Primitive (giá trị thô)

| Token | Hex |
|-------|-----|
| `blue-900` | `#1E3A8A` |
| `blue-800` | `#1E40AF` |
| `blue-700` | `#1D4ED8` |
| `blue-600` | `#2563EB` |
| `sky-400` | `#38BDF8` |
| `sky-100` | `#BAE6FD` |
| `sky-50`  | `#F0F9FF` |
| `amber-500` | `#F59E0B` |
| `amber-100` | `#FEF3C7` |
| `amber-800` | `#92400E` |
| `emerald-500` | `#10B981` |
| `emerald-100` | `#D1FAE5` |
| `emerald-800` | `#065F46` |
| `rose-500` | `#EF4444` |
| `rose-100` | `#FEE2E2` |
| `rose-300` | `#FCA5A5` |
| `rose-700` | `#B91C1C` |
| `rose-800` | `#991B1B` |
| `cyan-50` | `#E0F2FE` |
| `cyan-700` | `#0369A1` |
| `slate-50` | `#F8FAFC` |
| `slate-100` | `#F1F5F9` |
| `slate-200` | `#E2E8F0` |
| `slate-300` | `#CBD5E1` |
| `slate-400` | `#94A3B8` |
| `slate-600` | `#334155` |
| `slate-900` | `#0F172A` |
| `white` | `#FFFFFF` |

### 2.2 Semantic (vai trò màu)

| Token | Giá trị | Vai trò |
|-------|---------|---------|
| `color-primary` | `blue-800` (`#1E40AF`) | Màu chủ đạo — điều hướng chính, nút hành động chính, tiêu đề |
| `color-primary-hover` | `blue-600` (`#2563EB`) | Trạng thái hover/active/focus trên nền primary |
| `color-primary-active` | `blue-700` (`#1D4ED8`) | Trạng thái nhấn (pressed) |
| `color-secondary` | `sky-400` (`#38BDF8`) | Điểm nhấn phụ, badge thành tích học sinh, progress bar |
| `color-tertiary` | `amber-500` (`#F59E0B`) | Thông báo nhắc nhở điểm danh, hạn nộp bài, cảnh báo nhẹ |
| `color-success` | `emerald-500` (`#10B981`) | Chấm bài đạt, điểm danh hoàn tất, đã thanh toán học phí |
| `color-destructive` | `rose-500` (`#EF4444`) | Cảnh báo nghiêm trọng, thi trượt/vắng, xoá vĩnh viễn |
| `color-border` | `slate-200` (`#E2E8F0`) | Viền phân vùng giữa các khối nội dung |
| `color-surface-base` | `slate-50` (`#F8FAFC`) | Nền canvas tổng (layer 0) |
| `color-surface-card` | `white` (`#FFFFFF`) | Nền thẻ/nội dung (layer 1+) |
| `color-text-primary` | `slate-900` (`#0F172A`) | Chữ nội dung chính |
| `color-text-muted` | `slate-400` (`#94A3B8`) | Placeholder, chữ phụ |

### 2.3 Component tokens (áp dụng cụ thể — xem chi tiết tại mục 6)

Xem token riêng cho từng component ở mục **6. Component Specs**.

---

## 3. Typography

Font: **Plus Jakarta Sans** cho toàn bộ cấp độ.

| Token | Size | Weight | Line-height | Letter-spacing | Dùng cho |
|-------|------|--------|-------------|-----------------|----------|
| `display-lg` | 40px (mobile 30px) | 800 | 48px (38px) | -0.02em | Tiêu đề trang chủ, landing |
| `headline-lg` | 32px (mobile 26px) | 700 | 40px (34px) | -0.015em | Tiêu đề trang lớn |
| `headline-md` | 24px | 700 | 32px | -0.01em | Tiêu đề section |
| `headline-sm` | 20px | 600 | 28px | — | Tiêu đề card/module |
| `title-md` | 18px | 600 | 26px | — | Tiêu đề phụ |
| `body-lg` | 16px | 400 | 26px | — | Nội dung chính |
| `body-md` | 15px | 400 | 24px | — | Nội dung mặc định |
| `body-sm` | 14px | 400 | 20px | — | Nội dung phụ |
| `label-lg` | 15px | 600 | 20px | 0.01em | Label form, nút bấm |
| `label-md` | 13px | 600 | 18px | 0.02em | Label nhỏ, tag |
| `caption` | 12px | 500 | 16px | — | Chú thích, timestamp |

**Quy tắc bắt buộc (vì tiếng Việt có dấu):**
- Line-height tối thiểu **1.5× font-size** cho body text, **1.25×** cho display/heading —
  tránh dấu bị cắt/đè (ệ, ở, ứ, ẵ...).
- Label, trạng thái, nút bấm dùng weight semi-bold (`600`) kèm letter-spacing nhẹ để dễ đọc
  trên màn hình nhỏ.

---

## 4. Spacing & Grid

| Token | Giá trị |
|-------|---------|
| `space-xs` | 0.25rem (4px) |
| `space-sm` | 0.5rem (8px) |
| `space-md` | 1rem (16px) |
| `space-lg` | 1.5rem (24px) |
| `space-xl` | 2.5rem (40px) |
| `gutter` (desktop) | 1.5rem (24px) |
| `gutter` (mobile) | 1rem (16px) |
| `margin` (desktop) | 2rem (32px) |
| `margin` (mobile) | 1rem (16px) |

### Breakpoints

| Thiết bị | Độ rộng | Số cột | Gutter | Margin |
|----------|---------|--------|--------|--------|
| Desktop | ≥ 1024px (canvas tối đa 1440px) | 12 | 24px | 32px |
| Tablet | 768–1023px | 8 | 20px | 24px |
| Mobile | < 768px | 4 (xếp dọc 1 cột) | 16px | 16px |

**Quy tắc chạm (touch target):** mọi phần tử tương tác (nút, input, dòng danh sách, ô ngày
trên lịch) phải có vùng chạm tối thiểu **44×44px** — phục vụ giáo viên điểm danh nhanh và
phụ huynh không rành công nghệ dùng điện thoại.

---

## 5. Hình khối & Độ nổi (Shape & Elevation)

### 5.1 Bo góc (Rounded — Level 2, không dùng góc vuông)

| Token | Giá trị | Áp dụng cho |
|-------|---------|-------------|
| `radius-sm` | 0.25rem (4px) | — |
| `radius-base` | 0.5rem (8px) | Input, dropdown, nút nhỏ, pill thông báo |
| `radius-lg` | 1rem (16px) | Card nội dung, widget dashboard, bảng, bottom sheet mobile |
| `radius-xl` | 1.5rem (24px) | Modal, banner lớp học, card thông báo phụ huynh |
| `radius-full` | 9999px (pill) | Avatar học sinh, badge trạng thái, nút icon nổi |

### 5.2 Độ nổi (Elevation)

| Layer | Nền | Shadow | Dùng cho |
|-------|-----|--------|----------|
| Layer 0 | `#F8FAFC` | không | Canvas nền, sidebar, khung tổng |
| Layer 1 | `#FFFFFF` + viền `1px solid #E2E8F0` | `0px 1px 3px 0px rgba(15,23,42,0.04)` | Card, khối nội dung |
| Layer 2 | `#FFFFFF` | `0px 8px 24px -4px rgba(30,64,175,0.08), 0px 4px 8px -2px rgba(15,23,42,0.04)` | Floating card, dropdown, datepicker |
| Layer 3 | `#FFFFFF` + backdrop `rgba(15,23,42,0.45)` blur 4px | `0px 20px 32px -8px rgba(15,23,42,0.16)` | Modal, popup đánh giá học sinh, dialog thanh toán |

---

## 6. Component Specs

### 6.1 Button

| Loại | Background | Text | Border | Chiều cao tối thiểu | Trạng thái khác |
|------|------------|------|--------|----------------------|-------------------|
| Primary | `#1E40AF` | `#FFFFFF` | — | 48px | Hover `#2563EB` (tăng elevation); Active `#1D4ED8` |
| Secondary | `#F0F9FF` | `#0369A1` | `1px solid #BAE6FD` | 48px | Dùng cho hành động phụ, ít áp lực (vd "Lưu nháp") |
| Outline | `#FFFFFF` | `#334155` | `1.5px solid #CBD5E1` | 48px | — |
| Destructive | `#FEE2E2` | `#B91C1C` | `1px solid #FCA5A5` | 48px | — |

Bo góc `radius-base` (8px), padding ngang 24px.

### 6.2 Input Field & Selector

- Chiều cao tối thiểu 48px, bo góc 8px, padding ngang 16px, nền `#FFFFFF`,
  viền `1.5px solid #CBD5E1`.
- Placeholder: `#94A3B8`. Giá trị nhập: `#0F172A`, size 15px.
- **Focus**: viền `#2563EB` + glow ring `box-shadow: 0 0 0 3px rgba(37,99,235,0.15)`.
- **Error**: viền `#EF4444` + glow ring `rgba(239,68,68,0.15)`; helper text + icon màu
  `#DC2626` ngay dưới input.
- Label: 14px, weight 600, luôn hiển thị phía trên input (không ẩn khi focus).

### 6.3 Card (Lớp học / Học sinh)

- Nền `#FFFFFF`, viền `1px solid #E2E8F0`, bo góc `radius-lg` (16px), padding 20px
  (mobile 16px).
- Header: avatar/badge môn học + tiêu đề tiếng Việt rõ ràng (vd "Toán Học Lớp 9A") + status
  pill góc phải trên.
- Action strip: thanh phân vùng rõ giữa nội dung và hành động chính — tránh chạm nhầm.

### 6.4 Status Chip / Badge

Kích thước: cao 28px, padding ngang 12px, bo góc pill, font 13px weight 600.

| Trạng thái | Nền | Text |
|------------|-----|------|
| Có mặt / Đã điểm danh | `#D1FAE5` | `#065F46` |
| Vắng mặt | `#FEE2E2` | `#991B1B` |
| Đi muộn | `#FEF3C7` | `#92400E` |
| Chờ phản hồi | `#E0F2FE` | `#0369A1` |

### 6.5 Checkbox & Radio

- Kích thước ô 22×22px, vùng chạm bao quanh tối thiểu 44×44px.
- Trạng thái chưa chọn: viền `#94A3B8`. Trạng thái chọn: nền đặc `#1E40AF` + dấu check
  trắng (checkbox) hoặc chấm tròn đồng tâm `#1E40AF` (radio).
- Khi đi kèm văn bản đồng ý nhiều dòng (điều khoản phụ huynh), căn theo baseline trên cùng.

### 6.6 List & Roster (Danh sách học sinh)

- Chiều cao dòng tối thiểu 60px (mobile và desktop).
- Phân dòng: viền dưới `1px solid #F1F5F9`, hover nhẹ `#F8FAFC`.
- Bố cục 3 vùng: trái (avatar + tên/mã học sinh), giữa (liên hệ/progress bar),
  phải (nút hành động nhanh — Gọi phụ huynh, Điểm danh có mặt...).

---

## 7. Việc tiếp theo

- Khi code FE (Next.js), import token này thành CSS variables / Tailwind theme
  (xem skill `ui-ux-pro-max:ui-styling`) — không hardcode hex trong component.
- Các màn hình mới sinh thêm từ Stitch (cho 4 nhóm module còn lại: Academic/Lesson/
  Attendance, Question Bank/Assessment, Submission/Grading, Notification/File/Audit)
  phải tuân theo token trong file này, không để Stitch tự chọn màu/style mới mỗi lần.
