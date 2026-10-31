# Quy trình làm việc theo lát

Mỗi lát là một nhóm màn hình, đi hết các bước dưới đây rồi mới sang lát kế. Thứ tự lát và hiện trạng: `product/screens/mockup-plan.md`; đang ở đâu: `STATE.md`.

## Nguyên tắc

- V1 là chức năng cơ bản nhất của một trung tâm; mở rộng sau khi hỏi giáo viên.
- Bắt đầu từ API đã có; chỉ thêm hoặc sửa API khi thiếu thật.
- Chốt chức năng bằng mockup trước, rồi mới viết code thật.
- BE và FE là hai plan riêng, hai bộ test riêng, chạy xen kẽ trong cùng lát.
- Quyết định chốt thì ghi ngay vào `requirements/decision-log.md` (`DEC-xxx`), `architecture/open-decisions.md` (`ARCH-xx`) hoặc đặc tả màn.
- Một phiên Claude cho mỗi lát; trạng thái nằm trong tài liệu, không nằm trong chat.

## Các bước mỗi lát

0. **Quét chạm nhau (trước khi mở lát).** Chạy `gh pr list` và `git log origin/main..origin/<nhánh>` ở các repo; đọc `git diff --stat` của mọi nhánh docs đang mở. Nhánh nào đã sửa quyết định, spec hoặc mockup của màn mình sắp làm thì gộp hoặc merge nó vào `main` trước, rồi mới thiết kế. Không dựng mockup hay spec lên một quyết định sắp bị đổi.
1. **List chức năng.** Liệt kê chức năng dựa trên API đã có, cộng các chức năng bổ sung cần thêm, rồi tự đề xuất giữ/đơn giản hóa/tạm bỏ theo tinh thần V1 tối thiểu. Giải thích cả nghiệp vụ lẫn kỹ thuật khi đề xuất. Ghi quyết định vào tài liệu.
2. **Kiểm tra màn hình.** Sau khi chốt mục 1, rà lại mockup xem cần bổ sung/sửa gì. Sửa mockup, chạy `python3 tools/make_standalone.py` (repo `piclass-mockup`), user xem bản `standalone/`.
   - Sửa nhỏ hoặc màn mới theo mẫu có sẵn: tự kiểm.
   - Xong cả một nhóm lớn hoặc có kiểu giao diện mới: review một lần ở cuối.
3. **Cập nhật API.** Chốt xong UI thì bổ sung/sửa API nếu thiếu. Spec ngắn nếu có hành vi mới, plan, code theo TDD, review một lần ở cuối branch (opus, effort medium). Đổi route/FormRequest/Resource thì chạy lại `scramble:export` trước khi coi bước này xong (xem `piclass-api/CLAUDE.md`). Quy ước: `piclass-api/CLAUDE.md`.
4. **Ghép FE với API thật.** Trước khi đọc contract API, chạy `docker compose exec api php artisan scramble:export` (repo `piclass-infra/local`) rồi đọc `piclass-api/storage/api-docs/openapi.json` — nhanh hơn đọc tay từng Controller/Resource (xem mục "Tài liệu API (OpenAPI)" ở `piclass-api/CLAUDE.md`). Plan, code bám design system v2 và mockup, gọi API thật, test. Đặc tả màn (`product/screens/*.md`) rất dài: grep mã màn rồi đọc theo `offset`/`limit`, không đọc cả file. Trước khi dựng UI màn mới, mở file tương ứng ở `piclass-mockup/ds-v2-source/ui_kits/<vai-trò>/` làm tham chiếu (đọc tại chỗ, không copy nguyên — xem mục "Design system" ở `piclass-web/CLAUDE.md`). **Plan phải ghi rõ trong từng Task đoạn mockup cần đọc (dòng, biến) — không chỉ dựa vào đặc tả `admin.md`/màn hình khác (mô tả bằng lời, có thể thiếu chi tiết bố cục/tab/nút).** Quy ước: `piclass-web/CLAUDE.md`.
5. **Chạy test trên màn thật.** Làm đúng kịch bản thật trên màn thật, sửa lỗi phát sinh tại chỗ. **Trước khi báo lát xong, tự so khớp từng màn đã code với đúng đoạn mockup** (không chỉ chạy test/lint/typecheck) — đây là bước hay bị bỏ qua, gây lệch mockup mà không ai phát hiện tới khi user tự kiểm.
6. **Cập nhật tài liệu.** Đánh dấu lát xong ở `mockup-plan.md`, cập nhật `STATE.md` (hoặc chạy `/xong`), dời spec/plan của lát sang `archive/superpowers/`.

Bước 3 và 4 có thể chạy song song khi hình dạng API đã rõ; chưa rõ thì làm bước 3 trước. Push nhánh feature tự động sau mỗi đơn vị việc hoàn thành; mở PR và merge chỉ khi user yêu cầu rõ ("tạo PR và merge đi") — xem mục Git dưới đây và `docs/process/git-rules.md`.

## Co quy trình theo cỡ việc (user chốt 2026-10-29)

Việc nhỏ, ít rủi ro (ví dụ thêm một tham số lọc vào một Query): làm gọn, test-first, chạy test nhóm liên quan, PHPStan và Pint, rồi PR; không viết plan riêng, không gọi reviewer độc lập, không chạy cả bộ test. Việc lớn hoặc rủi ro cao (nhiều module, đổi schema hay phân quyền, màn hình mới): đi đủ plan, review độc lập cuối branch, chạy cả bộ test. Quy tắc cứng (nhánh từ `origin/main`, PR, PHPStan không thêm lỗi, cập nhật `STATE.md` và spec khi trạng thái đổi) luôn giữ. Không chạy test song song với subagent trên cùng DB test.

`superpowers:brainstorming` cho việc kiến trúc hoặc API: nạp trước `docs/requirements/*`, `docs/access-control/*`, `docs/database/*` làm nguồn; không để nó hỏi lại từ đầu hay viết spec lệch Decision Log.

**Review code bằng subagent chỉ chạy model `opus`** (user chốt 2026-10-30): truyền `model: "opus"` tường minh; không dùng `fable`, kể cả khi skill ghi "model mạnh nhất". Quy tắc này thắng chỉ dẫn của skill.

## Git

- Không commit hay push thẳng `main`: branch từ `origin/main` mới nhất (`git fetch origin` trước), PR, merge, không xóa branch. Nhánh feature tự push sau mỗi đơn vị việc hoàn thành (không force-push, không `--amend` commit đã push); PR và merge chờ user xác nhận. Chi tiết: `docs/process/git-rules.md`.
- Nhiều repo: code trước, docs sau cùng; khi push một repo, kiểm tra các repo còn lại có commit chờ không.
- PR tiêu đề và mô tả tiếng Việt; commit message tiếng Anh.
