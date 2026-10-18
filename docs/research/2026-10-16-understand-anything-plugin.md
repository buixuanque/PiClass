# Báo cáo: Plugin "Understand-Anything" (Egonex-AI) — có nên dùng cho PiClass?

## 1. Plugin này là gì, giải quyết vấn đề gì

**Understand-Anything** là một plugin cho Claude Code (và một số công cụ AI coding khác như Cursor, VS Code + Copilot, Codex, Gemini CLI...). Mục đích: biến một codebase (hoặc tài liệu/wiki) thành một **"đồ thị tri thức"** (knowledge graph) — một bản đồ các file, hàm, class, lời gọi, kế thừa, cùng mô tả bằng ngôn ngữ tự nhiên — rồi cho xem qua dashboard web hoặc hỏi đáp trực tiếp.

Vấn đề nó nhắm tới: người mới (dev mới, hoặc cả người không rành code) phải đọc code "mù" rất lâu mới hiểu hệ thống lớn làm gì, module nào gọi module nào, business logic nằm ở đâu. Slogan của project: *"Graphs that teach > graphs that impress"* (đồ thị để dạy, không phải để khoe).

## 2. Cách hoạt động

Cơ chế lai hai lớp:
- **Tree-sitter** (công cụ phân tích cú pháp tĩnh, không cần AI) — đọc code để lấy ra import/export, định nghĩa hàm/class, lời gọi hàm, quan hệ kế thừa. Kết quả này chính xác, tái lập được.
- **LLM** (model AI) — bổ sung phần "hiểu nghĩa": mô tả file làm gì, gắn tag, phân lớp kiến trúc (vd. controller/service/model), ánh xạ sang domain nghiệp vụ, tạo "tour" học codebase.

Pipeline chạy qua **10 agent chuyên biệt** (project-scanner, file-analyzer, architecture-analyzer, domain-analyzer, tour-builder, graph-reviewer, article-analyzer, assemble-reviewer, design-analyzer, knowledge-graph-guide), xử lý song song tối đa 5 worker, theo batch 20-30 file/lần, và chỉ phân tích lại file đã đổi (incremental) ở các lần chạy sau.

**Commands chính** (gọi bằng `/` trong Claude Code): `/understand` (build graph), `/understand-dashboard` (mở dashboard), `/understand-chat` (hỏi đáp), `/understand-diff` (phân tích tác động thay đổi hiện tại), `/understand-explain` (giải thích sâu 1 file/hàm), `/understand-onboard` (tạo hướng dẫn onboarding), `/understand-domain` (trích domain/flow nghiệp vụ), `/understand-knowledge` (phân tích wiki).

**Hooks** (đọc trực tiếp file `hooks.json` trong repo, không phải từ README tóm tắt):
- `PostToolUse` hook: chạy sau mỗi lần Claude Code gọi Bash, kiểm tra xem commit git hiện tại có khác với lúc build graph không.
- `SessionStart` hook: nếu phát hiện graph đã "cũ" so với HEAD hiện tại và config `autoUpdate = true`, nó **tự ra lệnh cho Claude cập nhật lại graph ngay, không hỏi xác nhận người dùng**. Điểm cần lưu ý khi bật auto-update — nó chủ động chạy phân tích (tốn token) mà không chờ đồng ý từng lần.
- **Không có MCP server** trong plugin này.

**Hỗ trợ ngôn ngữ/framework** (kiểm tra trực tiếp danh sách file trong repo): có file cấu hình riêng cho **PHP** (`configs/php.ts`) ở mức cú pháp, nhưng **không có extractor nhận diện framework Laravel** — trong khi framework đã có extractor riêng gồm Django, Express, FastAPI, Flask, Gin, **Next.js**, Rails, React, Spring, Vue. Tức là: PHP được tree-sitter đọc đúng cú pháp (hàm, class...), nhưng không hiểu "đây là route Laravel", "đây là Eloquent model/relationship" — phần đó dựa vào LLM suy đoán chứ không có cấu trúc chuẩn như Next.js được.

## 3. Cài đặt và dùng cơ bản

- Trong Claude Code: `/plugin marketplace add Egonex-AI/Understand-Anything`, sau đó `/plugin install understand-anything`.
- Cursor/VS Code+Copilot: tự nhận diện khi clone repo. Các nền tảng khác chạy `install.sh`/`install.ps1`.
- Luồng dùng: chạy `/understand` một lần (build graph, lưu vào `.ua/knowledge-graph.json`) → `/understand-dashboard` để duyệt trực quan, hoặc `/understand-chat` để hỏi đáp. Có thể xem dashboard không cần Claude Code bằng `npx` (cần Node ≥ 18).
- Team có thể commit `.ua/` vào git để chia sẻ graph chung (trừ `intermediate/` và `diff-overlay.json`).

## 4. Ưu điểm, nhược điểm, giới hạn

**Ưu điểm**
- Mã nguồn mở, MIT license, miễn phí — chi phí thực tế chỉ là token AI dùng (đã nằm trong subscription Claude Code sẵn có).
- Đa nền tảng, không khóa vào riêng Claude Code.
- Cập nhật incremental (lần đầu tốn token, các lần sau rẻ hơn vì chỉ phân tích file đổi).
- Có extractor riêng cho **Next.js** — khớp frontend PiClass.
- Có lệnh riêng cho domain/business flow (`/understand-domain`) và tạo tour onboarding — đúng kiểu việc PiClass hay cần (giải thích cho người không chuyên).

**Nhược điểm / giới hạn**
- **Không có extractor chuyên cho Laravel** — backend PHP chỉ phân tích ở mức cú pháp chung + suy đoán LLM, không nắm chính xác cấu trúc đặc thù Laravel (route, Eloquent relationship, policy, queue job) như phần Next.js. Rủi ro: thông tin graph về backend có thể sai lệch/hời hợt hơn frontend.
- Lần chạy `/understand` đầu tiên trên project lớn **"có thể tốn lượng token đáng kể"** (ghi rõ trong README).
- Hook tự động cập nhật graph khi vào session mới mà không hỏi xác nhận — cần kiểm soát config `autoUpdate` nếu không muốn nó tự chạy ngầm.
- Project còn khá non: tạo ngày 15/3/2026, mới ~7 tháng tuổi. Lượng sao GitHub (86.6k) rất lớn so với tuổi đời — khả năng là hiệu ứng viral/launch hơn là minh chứng đã kiểm nghiệm lâu dài trong production; nên giữ góc nhìn thận trọng với số liệu này.

## 5. Độ trưởng thành (kiểm tra qua GitHub API trực tiếp)

| Chỉ số | Giá trị |
|---|---|
| Stars | 86,591 |
| Forks | 7,300 |
| Open issues | 325 |
| Ngày tạo repo | 15/03/2026 |
| Commit gần nhất | 12/10/2026 (4 ngày trước thời điểm research) |
| Tổng commit | 858 |
| License | MIT (Yuxiang Lin / Infinite Universe, Inc.) |

Hoạt động commit gần đây dày đặc (nhiều commit/PR trong tuần đầu tháng 10/2026, kể cả từ contributor ngoài, mới thêm bản dịch tiếng Việt) → **maintainer đang active**, không phải project bị bỏ rơi. Tuy nhiên tuổi đời ngắn (~7 tháng) nên chưa có dữ liệu dài hạn về độ ổn định/độ tin cậy của graph sinh ra.

## 6. Đánh giá cho PiClass (Laravel + Next.js)

**Khuyến nghị: nên thử dùng, nhưng chỉ cho mục đích hỗ trợ, không dùng thay thế tài liệu đã chốt.**

Lý do: Next.js (frontend PiClass) được hỗ trợ tốt (có extractor riêng), nhưng Laravel (backend, nơi PiClass đặt toàn bộ business rule/permission theo `docs/architecture/technology-decisions.md`) chỉ được phân tích ở mức cú pháp PHP chung — không đủ tin cậy làm nguồn chân lý cho business rule. PiClass đã có quy trình riêng rất chặt (Decision Log `DEC-xxx`, `docs/requirements/*`, `docs/access-control/*`...) để chốt nghiệp vụ — một graph tự sinh bởi LLM (có thể sai với phần Laravel) không nên ghi đè hay thay thế nguồn này.

**Dùng cụ thể cho việc gì:**
- `/understand-dashboard` + `/understand-chat`: giúp dev mới vào team (hoặc cả founder) có cái nhìn nhanh tổng quan code hiện có — tiết kiệm thời gian đọc code tay khi onboarding.
- `/understand-diff`: hỗ trợ review PR lớn, xem nhanh phạm vi tác động của một thay đổi.
- `/understand-onboard`: tạo hướng dẫn onboarding kỹ thuật nhanh cho dev mới (không thay thế tài liệu nghiệp vụ `docs/requirements`).

**Không nên dùng cho:** sinh tài liệu domain/business chính thức (`/understand-domain`) để thay `docs/requirements/*` — thiếu độ chính xác cho đặc thù Laravel; nếu dùng, chỉ coi là gợi ý tham khảo, phải có người review trước khi tin.

**Triển khai an toàn:** thử trên 1 branch/sandbox trước, theo dõi lượng token tiêu tốn ở lần chạy `/understand` đầu tiên (codebase PiClass đang lớn dần), và kiểm soát/tắt `autoUpdate` nếu không muốn nó tự chạy phân tích ngầm mỗi session.

---

### Nguồn tham khảo
- [Egonex-AI/Understand-Anything — GitHub repo chính](https://github.com/Egonex-AI/Understand-Anything)
- [README.md (raw)](https://raw.githubusercontent.com/Egonex-AI/Understand-Anything/main/README.md)
- [hooks.json (raw) — xác minh cơ chế hook thực tế](https://raw.githubusercontent.com/Egonex-AI/Understand-Anything/main/understand-anything-plugin/hooks/hooks.json)
- [GitHub API — thống kê repo (stars/forks/license/ngày commit)](https://api.github.com/repos/Egonex-AI/Understand-Anything)
- [Danh sách agent definitions (10 file, kiểm tra trực tiếp)](https://api.github.com/repos/Egonex-AI/Understand-Anything/contents/understand-anything-plugin/agents)
- [Danh sách file ngôn ngữ hỗ trợ — xác nhận có php.ts](https://api.github.com/repos/Egonex-AI/Understand-Anything/contents/understand-anything-plugin/packages/core/src/languages/configs)
- [Danh sách framework extractor — xác nhận không có Laravel](https://api.github.com/repos/Egonex-AI/Understand-Anything/contents/understand-anything-plugin/packages/core/src/languages/frameworks)

**Lưu ý về độ chắc chắn:** Toàn bộ dữ liệu số (stars, ngày commit, cấu trúc thư mục, nội dung hooks.json, danh sách extractor) lấy trực tiếp từ GitHub (README, GitHub API, raw file) — không dựa vào bài viết bên thứ ba. Một số kết quả tìm kiếm từ `instagit.com` (trang tự sinh bài viết tóm tắt repo) xuất hiện trong quá trình search nhưng **không được dùng làm căn cứ chính** cho báo cáo này vì là nguồn cộng đồng/tự động, không chính thức.
