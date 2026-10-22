# Sổ quyết định khi thực thi Phase A — refactor kiến trúc BE

> **Tài liệu lịch sử** (đợt chuyển sang quy ước module, hoàn tất 2026-10-22). Quy ước hiện hành: `piclass-api/CLAUDE.md` và `docs/architecture/backend-gotchas.md`.

Nguồn: ledger thực thi Phase A (Task 1-6) của plan `2026-10-18-be-architecture-refactor.md`. Mỗi dòng là một quyết định controller tự đưa ra thay user trong lúc chạy, kèm lý do và chi phí nếu sai. User xem và có thể đảo lại.

| # | Quyết định | Vì sao | Chi phí nếu sai |
|---|---|---|---|
| 1 | Người khác truy cập Attempt giữ 404 qua `Response::denyAsNotFound('Request failed.')`; thiếu hồ sơ Student giữ 403 với message cũ | `authorize()` trả false sẽ thành 403 và lộ sự tồn tại của đối tượng; message phải giữ như cũ | Một status/test lệch, dễ sửa |
| 2 | Task 6 (test kiến trúc) dùng bảng ownership nháp, không chờ user duyệt | User duyệt bắt buộc trước Phase C; hằng số trong test dễ sửa | Sửa một hằng số |
| 3 | Dừng sau Task 6; Task 7 là điểm dừng chờ user | Plan quy định, user muốn quay lại bàn | Không mất gì |
| 4 | Task 2-6 gộp một branch API `refactor/phase-a-foundation` | Các task phụ thuộc nhau | PR lớn hơn, phải tách |
| 5 | Làm trực tiếp trên branch, không dùng git worktree | Docker mount đúng thư mục `piclass-api`, test không chạy được ở thư mục khác | Không ảnh hưởng |
| 6 | Implementer dùng Sonnet; reviewer Opus cho Task 4, 5a, 5b và review toàn branch; Sonnet cho review còn lại | Theo ý user (phân tích bằng Opus, thực thi bằng Sonnet) | Bỏ sót tinh vi ở task dùng reviewer yếu hơn |
| 7 | File docs của các task sau (`error-codes.md`...) nằm chung branch `docs/model-ownership` | Cùng thuộc Phase A, một PR | Phải tách branch |
| 8 | `SubmitAttempt::handle(..., bool $enforceLatePolicy = true)`; GET tự nộp bài truyền `false` | GET cũ bỏ qua kiểm tra nộp trễ, POST thì áp dụng; cần giữ cả hai hành vi | Một tham số boolean, xem lại khi chốt `ARCH-18` |
| 9 | Task 5 tách 5a (Submission) và 5b (Grading) | 11 controller trong một lần review quá lớn | Thêm một vòng review |
| 10 | Controller cập nhật `piclass-api/CLAUDE.md` sau pilot và sau Phase A | Implementer sau không được sao chép ví dụ cũ đã sai | Không đáng kể |
| 11 | **Validation-first:** FormRequest validate (422) chạy trước kiểm tra trạng thái (409) | Chuẩn Laravel, khớp rule "Validate = FormRequest", endpoint chỉ-admin, FE chưa có | Status khác với cũ ở request vừa sai input vừa sai trạng thái; đảo lại thì phải validate trong Action |
| 12 | Khôi phục key `feedback.submission` và thứ tự kiểm tra cũ của `listForAssessment` (người tạo không có hồ sơ Teacher phải 404) | Ràng buộc "giữ nguyên key và status", và thay đổi thứ tự là thay đổi quyền | Giữ lại payload lồng không chủ ý (leaky) đến khi thiết kế Resource |
| 13 | Test kiến trúc có `KNOWN_VIOLATIONS` cho ngoại lệ có chủ và có test kiểm tra mục nào hết vi phạm | Một số vi phạm chỉ sửa được khi module khác có Action | Danh sách mục nát; đang trống |

## Mục hoãn (minor) và ghi chú cho Task 8 / Task 7

- Task 7: gộp `ATTEMPT_NOT_ACCEPTING_ANSWERS` vào `ATTEMPT_NOT_OPEN`; đổi tên `ASSESSMENT_NOT_ASSIGNED`; test HTTP 409 assert `error.code`; test đếm query của list có biến thể teacher; `extra()` có thể ghi đè key `error`.
- Task 8: `SubmitAttempt` đang ghi `Result` qua `ResultCalculator` (rule 9 về bản chất); 3 controller Result dùng `Gate::authorize` trong controller; `SubmissionPolicy::manageResult` chuyển sang `ResultPolicy`; hằng message `'Request failed.'` đang lặp ở hai Policy, sẽ có bản thứ ba nên đưa vào `Support/`; test kiến trúc: kiểm tra thư mục `Actions/` và `Domain/` tồn tại, thêm pattern `CarbonImmutable::now`, `Carbon::today`, `request()`, mọi `Exceptions/*` kế thừa `DomainException`.
- Chung: Policy không inject `Request` để đọc vai trò (hỏng ngoài HTTP) — chốt một nguồn vai trò đang hoạt động; `BuildAttemptQuestions::order/optionOrderFor` là static công khai, nên chuyển sang `Domain/`; `gradingMaxScore(): mixed` và các tham số `$score` nên siết kiểu.
