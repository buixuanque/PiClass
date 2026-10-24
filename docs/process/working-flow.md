# Quy trình làm việc theo lát (từ 2026-10-24)

Quy trình này thay cách làm cũ "BE xong cả 5 nhóm module rồi mới làm FE". Cập nhật file này khi quy trình đổi; `progress.md` chỉ ghi "đang ở đâu".

## Nguyên tắc

1. **Phạm vi V1 là chức năng cơ bản nhất của một trung tâm.** Mở rộng sau khi vận hành thật và hỏi giáo viên (xem `research/2026-10-24-review-pham-vi-v1.md`). Chức năng chưa cần thì ghi vào danh sách "tạm bỏ", không xóa backend.
2. **Làm theo lát (vertical slice):** một nhóm màn hình đi hết vòng bàn nghiệp vụ, mockup, BE, FE, chạy thử, rồi mới sang nhóm kế.
3. **Bắt đầu từ API đã có.** Mỗi lát: liệt kê API sẵn có, dựng màn bằng chúng, chỉ thêm hoặc sửa API khi thiếu thật (`product/screens/api-gaps.md`, `research/2026-10-24-api-v1-toi-thieu.md`).
4. **Chốt trực quan bằng mockup trước, không bằng code FE.** Sửa mockup rẻ hơn sửa FE, nên mọi thay đổi chức năng chốt trên mockup rồi mới viết code thật.
5. **BE và FE là hai plan riêng, hai bộ test riêng**, nhưng chạy xen kẽ trong cùng một lát (không gộp vào một plan hay một task).
6. **Quyết định nào chốt thì ghi vào tài liệu nguồn ngay lúc đó**, không để trong lịch sử chat: `requirements/decision-log.md` (`DEC-xxx`), `architecture/open-decisions.md` (`ARCH-xx`), đặc tả màn `product/screens/*.md`.
7. **Khi hỏi để chốt, giải thích cả hướng nghiệp vụ lẫn kỹ thuật** (theo `CLAUDE.md`), kèm đề xuất.

## Vòng lặp cho mỗi lát

| Bước | Việc | Ra gì | Công cụ / skill gợi ý |
|---|---|---|---|
| 1. Bàn nghiệp vụ | Với mỗi màn hỏi 3 câu: ai dùng, bao lâu một lần; bỏ màn này thì trung tâm có làm tay được không; giữ, đơn giản hóa hay tạm bỏ. Nêu API đã có và chỗ thiếu | Quyết định giữ / đơn giản / bỏ, ghi vào Decision Log và đặc tả màn | Nạp `requirements/*`, `access-control/*`, `database/*` trước; `superpowers:brainstorming` chỉ khi có tính năng mới ngoài Decision Log |
| 2. Chốt trực quan | Sửa mockup (`.dc.html`), chạy `tools/make_standalone.py`, user xem bản `standalone/` và chốt | Mockup khớp quyết định | `mockup-review-uiux`, `mockup-review-fix` sau khi dựng nhóm lớn; `ui-ux-pro-max` (a11y, responsive), `frontend-design` (review nhất quán) |
| 3. BE | Spec ngắn nếu có hành vi mới, rồi plan BE, code theo TDD, review | API đủ cho lát, test xanh, ghi `error-codes.md` nếu thêm mã lỗi | `superpowers:writing-plans`, `test-driven-development`, `subagent-driven-development`, `requesting-code-review`; quy ước ở `piclass-api/CLAUDE.md` |
| 4. FE | Plan FE, code Next.js bám design system v2, gọi API thật | Màn chạy được, test xanh | `writing-plans`, `ui-ux-pro-max:ui-styling` và `design-system` (khi code), `frontend-design` (review cuối); quy ước ở `piclass-web/CLAUDE.md` |
| 5. Chạy thử cả lát | Làm đúng kịch bản thật từ đầu đến cuối trên màn thật (ví dụ: Admin tạo hồ sơ, sao chép liên kết, người đó kích hoạt và đăng nhập) | Kết quả chạy thử, lỗi phát sinh được sửa tại chỗ | `superpowers:verification-before-completion` |
| 6. Cập nhật tài liệu | Đánh dấu lát xong trong `product/screens/mockup-plan.md` và `progress.md` | Tài liệu theo dõi đúng hiện trạng | |
| 7. Ship | Branch, PR, merge (xem dưới) | PR đã merge | `ship-prs` |

Bước 3 và 4 có thể chạy song song khi hình dạng API đã rõ (ví dụ: BE chỉ thêm một trường). Nếu API chưa rõ thì làm BE trước.

## Thứ tự các lát

Mockup đã dựng đủ bốn cổng và màn chung nhưng **chưa rà nghiệp vụ**. Rà theo thứ tự trong `product/screens/mockup-plan.md`: Admin trước, theo luồng ngày đầu của một trung tâm; sau Admin bàn các chủ đề dùng chung (Tiến độ, Bài đánh giá, Ngân hàng câu hỏi, Khóa học, UX học sinh).

Lát 0 (nền): nhóm 0 của Admin, gồm dựng nền `piclass-web`, đăng nhập, kích hoạt tài khoản, quên mật khẩu, khung giao diện, và BE liên kết kích hoạt (A10).

## Git và PR

- Không commit hay push thẳng `main`. Mỗi thay đổi một branch tạo từ `origin/main` mới nhất (luôn `git fetch origin` trước).
- Workspace có nhiều repo độc lập: docs (thư mục gốc), `piclass-api`, `piclass-infra`, `piclass-mockup`, `piclass-web`. Khi push một repo, kiểm tra các repo còn lại có commit chưa push không.
- **Chỉ push, tạo PR, merge khi user yêu cầu rõ** ("push đi"). Commit local thì được.
- PR: tiêu đề và mô tả tiếng Việt; commit message tiếng Anh. `gh pr merge N --merge`; **không xóa branch** sau khi merge.
- Thứ tự khi nhiều repo: code trước, docs sau cùng.

## Ngôn ngữ

Tài liệu và trao đổi: tiếng Việt. Comment trong code: tiếng Việt. Tên biến, hàm, class, tên test, `error.code`, commit message: tiếng Anh.

## Các nguồn chính

| Cần biết gì | Xem file |
|---|---|
| Đang ở đâu, việc tiếp theo | `docs/progress.md` |
| Kế hoạch và hiện trạng mockup | `docs/product/screens/mockup-plan.md` |
| Phạm vi V1, danh sách API cần thêm và tạm bỏ | `docs/research/2026-10-24-review-pham-vi-v1.md`, `docs/research/2026-10-24-api-v1-toi-thieu.md` |
| Quy tắc nghiệp vụ đã chốt | `docs/requirements/decision-log.md` |
| Quyền và phạm vi dữ liệu | `docs/access-control/*` |
| Schema | `docs/database/*` |
| Quyết định kỹ thuật, tenancy | `docs/architecture/open-decisions.md` (`ARCH-33`) |
| Đặc tả từng màn, khoảng trống API | `docs/product/screens/*.md` |
