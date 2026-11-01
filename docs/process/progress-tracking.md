# Theo dõi tiến độ (`STATE.md`)

Đọc file này khi: cập nhật `STATE.md`, chạy `/xong`, vừa merge PR, hoặc không chắc "đã xong" hay "chờ merge".

## Nguyên tắc

- `STATE.md` là nguồn duy nhất cho "đang ở đâu". Giữ ≤ 60 dòng, không văn xuôi, mục nhiều ý thì tách gạch con. **Không ghi số PR, tên nhánh hay "chưa push/chưa merge"** (git và `gh pr list` đã cho biết, `/tiep` đối chiếu; ghi vào thì cũ ngay). Chi tiết của một lát (PR số mấy, vì sao chốt) nằm ở mô tả PR; quyết định quan trọng ghi vào `decision-log.md` hoặc `architecture/decisions/ARCH-xx.md`. `archive/progress-log.md` là bản chụp cũ, không ghi thêm.
- Chỉ cập nhật khi trạng thái **thật sự đổi**: **xong một bước của lát (kể cả viết xong plan)**, xong lát, phát sinh hoặc xong việc kế tiếp, **vừa merge một PR**, hoặc khi user chạy `/xong`. Không sửa sau mỗi bước nhỏ. Khi trạng thái đã đổi thì cập nhật ngay, không hỏi user.
- Việc phát sinh chưa làm: thêm một dòng vào `BACKLOG.md`. Lát xong: xóa khỏi `STATE.md`, `git mv` spec/plan sang `docs/archive/superpowers/`.

## Khuôn mục "Bước"

Mục "Bước" chỉ ghi **lát đang làm**, mỗi lát một dòng, theo thứ tự `quyết định → mockup → spec → code BE → code FE → review → merge`, đánh dấu bước hiện tại bằng **đậm** và chữ "đang làm". Gạch con ghi phạm vi của bước (ví dụ "không sửa BE"). Lát xong thì xóa khỏi mục này; không giữ lịch sử lát đã xong (tra bằng git và mô tả PR).

## Ghi trạng thái

- Đã merge thì ghi xong, không giữ "chờ merge" (user chốt 2026-10-30).
- `STATE.md` **không** ghi việc bấm thử thủ công trên trình duyệt là việc chờ, cả "Việc kế tiếp" lẫn "Chờ user".
- Việc của user (`[user]`) không tự suy ra là đã xong.

## Sửa STATE.md ngay trong nhánh của lát, trước khi mở PR (user chốt 2026-11-01)

Sửa sau khi merge thì cần thêm một nhánh và một PR, nên `STATE.md` trên `main` luôn chậm một nhịp. Vì vậy:

1. Khi code (hoặc bước đang làm) xong, **sửa `STATE.md` trong chính nhánh đó** rồi mới báo cáo và mở PR. Ghi theo trạng thái sẽ có sau khi merge ("xong"), không ghi "chờ merge". Lát có nhiều repo: nhánh docs của lát chứa phần `STATE.md`.
2. PR bị từ chối hoặc đổi hướng thì sửa lại `STATE.md` trong chính nhánh đó.
3. Vừa merge xong thì không cần sửa `STATE.md` nữa. Chỉ rà nhanh: `BACKLOG.md` có việc mới không, `decision-log` có quyết định mới không, đã export OpenAPI chưa (nếu đổi API), và còn lệch gì so với git không (nếu có mới sửa, đi nhánh docs riêng).
4. Hỏi user **một câu** có đưa nhánh docs chứa `STATE.md` lên `main` không (nhánh riêng → push → PR → merge, không push thẳng `main`).

## Hai nhánh docs chạy song song

Nhiều mạch việc có thể cùng sửa `STATE.md`, `BACKLOG.md`, `decision-log.md`. Nhánh nào xong trước thì merge `main` ngay; nhánh còn lại gộp `main` vào rồi mới đi tiếp. Không để hai nhánh cùng sửa các file này lâu hơn một lát.

## Hỏi xác nhận mục đã xong (user chốt 2026-10-28)

Sau mỗi đơn vị việc hoàn thành (lúc push), đối chiếu "Việc kế tiếp" và "Chờ user" của `STATE.md` với việc vừa làm. Mục nào có thể đã xong thì hỏi user bằng câu chọn ngắn (`AskUserQuestion`), không bắt user tự đọc `STATE.md`. Chỉ sửa theo đúng mục user xác nhận. Không có mục nào đổi thì không hỏi.
