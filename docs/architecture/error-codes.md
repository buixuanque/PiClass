# Danh mục mã lỗi nghiệp vụ (error codes)

Mọi lỗi nghiệp vụ của API trả về theo envelope `{ "error": { "code", "message", "details"? } }`. File này là danh mục các `code` do domain exception phát ra (xem rule 3 và 4 trong `piclass-api/CLAUDE.md`). FE rẽ nhánh theo `code`; `message` chỉ dùng để hiển thị.

| Code | HTTP | Module | Khi nào |
| --- | --- | --- | --- |

## Cách thêm một dòng

- Mỗi code mới phải được thêm vào bảng trên trong cùng task/PR với class exception giới thiệu nó.
- `Code` viết UPPER_SNAKE và ổn định: không đổi tên sau khi FE đã dùng; cần đổi thì thêm code mới và loại bỏ code cũ có chủ đích.
- `Module` là module sở hữu exception (ví dụ `SubmissionGrading`); `Khi nào` mô tả ngắn điều kiện nghiệp vụ gây lỗi.
- FE chỉ rẽ nhánh theo `code`, không parse `message`.
