## Research: Graphify (github.com/Graphify-Labs/graphify)

**Lưu ý quan trọng trước khi vào nội dung:** Đây **không phải** là một "Claude Code plugin" đúng nghĩa (không nằm trong hệ thống plugin marketplace chính thức của Claude Code). Graphify là một **công cụ CLI độc lập** (viết bằng Python, cài qua PyPI), tự đăng ký vào Claude Code (và cả Cursor, Codex, Gemini CLI, Aider...) dưới dạng một **skill** (`/graphify`), và có thêm một chế độ tùy chọn chạy như **MCP server**. Cần biết rõ điều này vì nó ảnh hưởng tới cách cài đặt (không phải `/plugin install` mà là cài Python package riêng) và mức độ "chính thức" của tích hợp.

---

### 1. Graphify là gì, giải quyết vấn đề gì

Graphify biến một codebase (kèm tài liệu, schema SQL, file cấu hình, PDF, ảnh) thành một **knowledge graph** — một bản đồ các khái niệm và quan hệ giữa chúng (file nào gọi file nào, model nào liên kết bảng nào, concept nào xuất hiện trong tài liệu nào...). Mục đích: để AI coding assistant (Claude Code...) **truy vấn graph** (query/path/explain) thay vì phải grep/đọc lại nhiều file mỗi lần cần hiểu cấu trúc dự án — giúp tiết kiệm token và trả lời nhanh hơn với các câu hỏi dạng "A liên quan gì tới B", "cái gì phụ thuộc vào module này".

### 2. Cơ chế kỹ thuật

- **Code**: parse cục bộ bằng tree-sitter (AST — cây cú pháp) + phân tích call-graph, **không dùng LLM**, không rời máy. Hỗ trợ ~40 ngôn ngữ (có PHP, JS/TS nên tương thích Laravel + Next.js).
- **Tài liệu/PDF/ảnh**: cần dùng model của AI assistant đang chạy hoặc API key tự cấu hình để trích xuất ngữ nghĩa (tức có chi phí LLM ở bước này, không hoàn toàn offline cho phần non-code).
- **Nhãn độ tin cậy mỗi quan hệ**: `EXTRACTED` (thấy trực tiếp trong code) vs `INFERRED`/`AMBIGUOUS` (suy luận) — giúp người dùng biết cái nào chắc, cái nào là suy đoán.
- **Phân cụm**: dùng thuật toán Leiden để nhóm các phần liên quan thành cụm chức năng.
- **Đầu ra**: thư mục `graphify-out/` gồm `graph.html` (xem trực quan), `graph.json` (dữ liệu graph, dùng lại giữa các lần chạy), `GRAPH_REPORT.md` (tóm tắt node quan trọng, kết nối bất ngờ).
- **Lệnh chính**: `/graphify <path>` (dựng graph), `--update` (chỉ xử lý file đổi), `--watch` (tự đồng bộ), `query`/`path`/`explain` (truy vấn), `add <url>` (thêm paper/video vào graph), `hook install` (tự rebuild graph khi commit/đổi branch), `--mcp` (chạy như MCP stdio server), `export callflow-html` (xuất sơ đồ gọi hàm).
- Trên Claude Code có thêm một **hook** chạy trước khi assistant tìm kiếm, để "nhắc" nó ưu tiên tra graph trước.

### 3. Cài đặt và dùng cơ bản

1. Cần Python 3.10+ và `uv` (hoặc `pipx`/`pip`).
2. `uv tool install graphifyy` — chú ý tên package PyPI là **"graphifyy" (2 chữ y)**, nhưng lệnh CLI vẫn là `graphify`. Đây là tên tạm, có rủi ro gây nhầm lẫn/lỗi PATH khi cài (ghi nhận từ nguồn cộng đồng).
3. `graphify install` — tự nhận diện assistant đang dùng và đăng ký skill (thêm `--project` để cài theo scope project, file skill nằm ở `.claude/skills/graphify/SKILL.md`).
4. Trong Claude Code: gõ `/graphify .` để dựng graph cho thư mục hiện tại; sau đó dùng `/graphify query "..."`, `/graphify path "A" "B"`, `/graphify explain "X"` để hỏi.
5. Tùy chọn: `graphify hook install` để tự rebuild graph mỗi khi commit.

### 4. Ưu điểm / nhược điểm / giới hạn

**Ưu điểm:**
- Phân tích code hoàn toàn cục bộ, không gửi code lên cloud (trừ phần non-code cần LLM).
- Không cần hạ tầng riêng (không vector DB, không Neo4j, không server) — chạy ngay trên máy dev.
- Phân biệt rõ quan hệ "chắc chắn" vs "suy đoán" — tránh AI tự tin sai.
- Hỗ trợ nhiều assistant (Claude Code, Cursor, Codex, Gemini CLI...), không bị khóa vào một hệ sinh thái.
- License permissive (Apache-2.0).

**Nhược điểm / giới hạn:**
- Đây là **thêm một công cụ/dependency ngoài** cần cài, maintain, cập nhật riêng (Python package) — không nằm trong hệ plugin chính thức của Claude Code, nên không có bảo đảm hỗ trợ dài hạn từ Anthropic.
- Giá trị tiết kiệm token ("71.5 lần" theo README) là benchmark do chính nhà phát hành tự đo trên bộ dữ liệu nhỏ (52 file) — chưa được bên thứ ba kiểm chứng độc lập. Với codebase nhỏ, lợi ích gần như không đáng kể.
- Phần xử lý tài liệu/ảnh vẫn tốn LLM call, không miễn phí hoàn toàn.
- Có mô hình kinh doanh kèm theo "Graphify Cloud" (dịch vụ trả phí, bản thử 14 ngày) — README có khá nhiều nội dung quảng bá cho bản trả phí này, dù bản CLI/skill miễn phí vẫn dùng được độc lập, không bắt buộc Cloud.
- Một nguồn cộng đồng (chưa kiểm chứng chính thức) ghi nhận repo có cả file LICENSE (Apache-2.0) và LICENSE-MIT — tức có thể có phần code dùng license khác nhau trong cùng repo, cần lưu ý nếu công ty có yêu cầu compliance license chặt.
- Project còn khá non: ngày tạo repo là **2026-04-03**, tức mới khoảng 6 tháng tuổi tại thời điểm research (10/2026). Lượng sao tăng rất nhanh (127k sao, 12k fork chỉ trong ~6 tháng) — đây là tốc độ tăng trưởng bất thường, nên cân nhắc thận trọng, chưa đủ thời gian để đánh giá độ ổn định/ bảo trì dài hạn qua thực tế sử dụng.

### 5. Độ trưởng thành của project

- **Sao (stars):** ~127.164 | **Fork:** ~12.265 | **Open issues:** ~1.528 (dữ liệu lấy trực tiếp từ GitHub API, 16/10/2026).
- **Ngày tạo repo:** 03/04/2026 (rất mới, ~6 tháng tuổi).
- **Commit gần nhất (`pushed_at`):** 14/10/2026 — chỉ 2 ngày trước thời điểm research → **maintainer đang active**, cập nhật thường xuyên.
- **License:** Apache License 2.0 (theo GitHub API); có ghi nhận cộng đồng về khả năng tồn tại file LICENSE-MIT song song — chưa xác minh sâu, nên nếu cần dùng production, kiểm tra lại license chi tiết trước khi quyết định.
- Số liệu sao tăng rất nhanh trong thời gian ngắn là điểm **cần lưu ý thận trọng** — không khẳng định có vấn đề, nhưng độ "chín" thực tế (bao nhiêu công ty dùng lâu dài, bao nhiêu bug đã được fix qua thời gian) chưa thể đánh giá đầy đủ với lịch sử 6 tháng.

### 6. Đánh giá cho PiClass (Laravel + Next.js, đang ở Step 9 code module)

**Khuyến nghị: Chưa cần dùng ngay, có thể thử nghiệm sau nhưng không ưu tiên.**

Lý do:
- PiClass đang code từng module một (theo quy trình spec → plan → code đã chốt trong CLAUDE.md), codebase hiện còn nhỏ và được chia module rõ ràng theo tài liệu `docs/requirements`, `docs/access-control`, `docs/database` đã chốt sẵn — tức nguồn sự thật về business rule đã có sẵn ở dạng tài liệu Markdown có Decision Log, không cần một graph tool để "suy luận" quan hệ nghiệp vụ. Business rule luôn phải lấy từ `docs/` theo đúng thứ tự ưu tiên đã quy định, Graphify không thể và không nên thay thế vai trò đó (nó chỉ đọc code hiện có, không biết decision log/ADR).
- Lợi ích chính của Graphify (tiết kiệm token khi dò quan hệ code) chỉ rõ rệt khi **codebase đủ lớn** (nhiều file, nhiều module liên kết chéo). Với quy mô hiện tại của PiClass (mới có Module 4, 5 BE xong), lợi ích thực tế còn thấp.
- PiClass là **multi-repo** (`piclass-api`, `piclass-web`, `piclass-infra`, docs riêng ở `piclass-web`) — Graphify build graph theo từng thư mục chạy, nên muốn có giá trị thật sự (vd hiểu quan hệ API Laravel ↔ call Next.js) cần cấu hình chạy cross-repo, tăng thêm độ phức tạp vận hành.
- Đây là thêm một dependency Python/CLI ngoài stack chính (Laravel/Next.js), cần cài đặt riêng trên máy mỗi dev, không nằm trong `docs/setup/claude-skills-setup.md` hiện tại — nếu muốn dùng chính thức cho team, cần ghi vào tài liệu setup và có người chịu trách nhiệm maintain việc update.

**Nếu vẫn muốn thử:** giá trị khả thi nhất là dùng cho việc **onboarding/tìm hiểu nhanh một module BE Laravel lớn và phức tạp sau khi code xong nhiều module** (vd hỏi "Model X liên quan tới controller/route nào", "thay đổi bảng Y ảnh hưởng tới đâu") — một công cụ hỗ trợ cá nhân khi cần explore code nhanh, không phải công cụ quản lý business rule chính thức của dự án. Có thể review lại khi codebase PiClass lớn hơn (sau khi xong nhiều module Step 9) và khi Graphify có thêm thời gian kiểm chứng độ ổn định.

---

### Nguồn tham khảo
- [github.com/Graphify-Labs/graphify](https://github.com/Graphify-Labs/graphify) — README chính thức (đọc qua WebFetch, kể cả bản raw `v8/README.md`)
- GitHub REST API (`api.github.com/repos/Graphify-Labs/graphify`) — số liệu stars/forks/issues/license/ngày tạo/commit gần nhất, lấy trực tiếp 16/10/2026
- [claudelog.com/claude-code-mcps/graphify](https://claudelog.com/claude-code-mcps/graphify) — nguồn cộng đồng, dùng để đối chiếu cách mô tả cơ chế/lệnh, **chưa kiểm chứng chính thức**
- Ghi chú: các số liệu stars từ WebSearch (79.8k, 116.5k) khác với số lấy trực tiếp từ GitHub API (127.164) do thời điểm snapshot khác nhau của các bài viết cộng đồng — số liệu trong báo cáo này lấy từ GitHub API tại thời điểm research nên đáng tin hơn.
- Context PiClass đã đọc để gắn kết đánh giá: `/Users/que-bui/Workspaces/PiClass/docs/architecture/technology-decisions.md`, `/Users/que-bui/Workspaces/PiClass/CLAUDE.md`
