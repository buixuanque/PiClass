# Sơ đồ kiến trúc tổng thể

Sơ đồ biểu diễn các ranh giới logic V1, không chọn framework, giao thức API, cloud vendor hoặc object storage provider.

```mermaid
flowchart LR
  subgraph Client[Client]
    Web[Web client theo role<br/>Admin / Teacher / Student / Parent]
  end

  subgraph Backend[Backend / API — một modular monolith]
    API[Presentation / API boundary]
    Auth[Identity & Access]
    App[Application use cases]
    Domain[Domain modules<br/>Academic · Attendance · Lesson<br/>Question Bank · Assessment<br/>Submission/Grading · Result/Progress]
    Cross[Cross-cutting modules<br/>User Management · Notification<br/>File · Audit]
    API --> Auth
    API --> App
    App --> Domain
    App --> Cross
    Auth --> App
  end

  DB[(PostgreSQL<br/>đã chốt Step 6)]
  Files[(File storage<br/>provider cần quyết định)]
  Notify[In-app Notification<br/>lưu và đọc qua backend]
  Web -->|HTTPS; API contract ở Step 9| API
  Domain --> DB
  Cross --> DB
  Auth --> DB
  Cross -->|File metadata và quyền theo context| Files
  Cross --> Notify
  Notify --> DB
```

## Ghi chú

- Các module backend chạy trong cùng application ở V1; đường nối là dependency logic, không phải network hop.
- File content ở storage abstraction; metadata và `AttachmentLink` nằm trong PostgreSQL. Backend kiểm tra quyền trước khi upload/download.
- Notification V1 được lưu và hiển thị trong ứng dụng; không có external email/SMS provider trong kiến trúc đã chốt.
- Permission/data scope từ Step 7 được áp dụng trong backend trên mọi đường truy cập dữ liệu, kể cả file và notification context.
