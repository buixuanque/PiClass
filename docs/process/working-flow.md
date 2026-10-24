# Quy trình làm việc theo lát

Mỗi lát là một nhóm màn hình, đi hết các bước dưới đây rồi mới sang lát kế. Thứ tự lát và hiện trạng: `product/screens/mockup-plan.md`; đang ở đâu: `progress.md`.

## Nguyên tắc

- V1 là chức năng cơ bản nhất của một trung tâm; mở rộng sau khi hỏi giáo viên.
- Bắt đầu từ API đã có; chỉ thêm hoặc sửa API khi thiếu thật.
- Chốt chức năng bằng mockup trước, rồi mới viết code thật.
- BE và FE là hai plan riêng, hai bộ test riêng, chạy xen kẽ trong cùng lát.
- Quyết định chốt thì ghi ngay vào `requirements/decision-log.md` (`DEC-xxx`), `architecture/open-decisions.md` (`ARCH-xx`) hoặc đặc tả màn.
- Một phiên Claude cho mỗi lát; trạng thái nằm trong tài liệu, không nằm trong chat.

## Các bước mỗi lát

1. **Bàn nghiệp vụ.** Mỗi màn hỏi: ai dùng, bao lâu một lần; bỏ đi thì trung tâm làm tay được không; giữ, đơn giản hóa hay tạm bỏ. Liệt kê API đã có và chỗ thiếu. Giải thích cả nghiệp vụ lẫn kỹ thuật. Ghi quyết định vào tài liệu.
2. **Chốt trực quan.** Sửa mockup, chạy `python3 tools/make_standalone.py` (repo `piclass-mockup`), user xem bản `standalone/`.
   - Sửa nhỏ hoặc màn mới theo mẫu có sẵn: tự kiểm, không dùng skill.
   - Xong cả một nhóm lớn hoặc có kiểu giao diện mới: review một lần ở cuối bằng `mockup-review-uiux`, rồi `mockup-review-fix`.
3. **BE.** Spec ngắn nếu có hành vi mới, plan (`superpowers:writing-plans`), code theo TDD, review một lần ở cuối branch (opus, effort medium). Quy ước: `piclass-api/CLAUDE.md`.
4. **FE.** Plan, code bám design system v2 và mockup, gọi API thật, test. Quy ước: `piclass-web/CLAUDE.md`. Chỉ dùng `ui-ux-pro-max:ui-styling` và `design-system` một lần khi port `components/ui/*`.
5. **Chạy thử cả lát.** Làm đúng kịch bản thật trên màn thật, sửa lỗi phát sinh tại chỗ (`superpowers:verification-before-completion`).
6. **Cập nhật tài liệu.** Đánh dấu lát xong ở `mockup-plan.md` và `progress.md`.
7. **Ship.** Chỉ khi user yêu cầu rõ ("push đi"), dùng skill `ship-prs`.

Bước 3 và 4 có thể chạy song song khi hình dạng API đã rõ; chưa rõ thì làm BE trước.

## Git

- Không commit hay push thẳng `main`: branch từ `origin/main` mới nhất (`git fetch origin` trước), PR, merge, không xóa branch.
- Nhiều repo: code trước, docs sau cùng; khi push một repo, kiểm tra các repo còn lại có commit chờ không.
- PR tiêu đề và mô tả tiếng Việt; commit message tiếng Anh.
