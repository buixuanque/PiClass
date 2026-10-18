# Báo cáo: claude-mem (thedotmack) — có nên dùng cho PiClass không

## 1. Plugin này là gì, giải quyết vấn đề gì

`claude-mem` là một plugin/agent bên thứ ba cho Claude Code (và vài công cụ AI coding khác như Codex, Gemini CLI, Copilot...), tự giới thiệu là **"Persistent Context Across Sessions for Every Agent"**.

Ý tưởng: Claude Code bình thường "quên" hết khi đóng session. `claude-mem` tự động ghi lại mọi hành động dùng công cụ (đọc file, chạy lệnh, sửa code...) trong lúc làm việc, dùng AI để **tóm tắt ngữ nghĩa** (semantic summary) các quan sát đó, lưu lại, rồi ở session sau tự "nạp" lại ngữ cảnh liên quan cho Claude — mục tiêu là đỡ phải giải thích lại từ đầu mỗi lần mở session mới.

## 2. Cơ chế kỹ thuật

- **Hooks**: gắn vào 6 "điểm vòng đời" của Claude Code — SessionStart, UserPromptSubmit, PreToolUse, PostToolUse, Stop, SessionEnd. Nghĩa là **mỗi lần Claude dùng một công cụ (đọc file, chạy bash...) đều chạy qua hook này** để ghi nhận.
- **Worker service**: một tiến trình nền riêng (chạy bằng Bun — một runtime JavaScript khác Node), có cả web viewer để xem lại bộ nhớ.
- **MCP tools** (MCP = Model Context Protocol, cách Claude gọi "công cụ ngoài"): `search`, `timeline`, `get_observations` — để Claude tự truy vấn lại bộ nhớ cũ.
- **Skill** `mem-search`: cho phép hỏi bộ nhớ bằng ngôn ngữ tự nhiên.
- **Lưu trữ**: SQLite (cơ sở dữ liệu nhẹ, dạng file) cho sessions/observations/summaries, cộng thêm **Chroma** (vector database — DB chuyên tìm kiếm theo "ý nghĩa" câu chữ) để tìm kiếm ngữ nghĩa. Cấu hình ở `~/.claude-mem/settings.json`.
- **Cloud Sync (tùy chọn)**: đồng bộ bộ nhớ lên server của họ (cmem.ai).
- Dự án có mô hình **"open-core"**: phần lõi (engine, CLI, MCP tools...) mã nguồn mở Apache-2.0, còn các phần như hosted cloud, sync cho team/tổ chức, SSO, admin dashboard... là **thương mại/trả phí**, không nằm trong repo public.

## 3. Cài đặt và dùng cơ bản

- Cách khuyến nghị: trong Claude Code chạy `/plugin marketplace add thedotmack/claude-mem` rồi `/plugin install claude-mem`, khởi động lại Claude Code.
- Hoặc `npx claude-mem install` (lưu ý: `npm install -g claude-mem` chỉ cài thư viện, **không** đăng ký hook/worker — dễ cài nhầm).
- Yêu cầu Node.js ≥ 20; tự cài thêm Bun và `uv` nếu máy chưa có.
- Bước cài có yêu cầu **đăng nhập bằng email** để dùng "observer" của họ (bản dùng thử 30 ngày), sau đó nếu không đăng ký trả phí thì việc tóm tắt bộ nhớ sẽ **tự chuyển sang dùng gói Anthropic của chính bạn** (tức tốn thêm lượt gọi API/token của bạn), hoặc bạn có thể tự cấu hình key OpenRouter/Gemini riêng.
- Có thẻ `<private>` để loại nội dung nhạy cảm khỏi việc lưu trữ.

## 4. Ưu điểm, nhược điểm, giới hạn

**Ưu điểm:**
- Giải quyết đúng vấn đề thực tế: tự động nhớ lại ngữ cảnh cũ mà không cần người dùng tự viết ghi chú.
- Có tìm kiếm ngữ nghĩa (semantic search) mạnh hơn so với tìm chuỗi text đơn giản.
- Mã nguồn lõi mở (Apache-2.0).

**Nhược điểm / giới hạn đáng lo:**
- **Chạy hook trên MỌI lượt dùng công cụ** (PreToolUse/PostToolUse) → mỗi hành động của Claude đều bị "chặn lại" để ghi log, cộng thêm một tiến trình nền (Bun worker) luôn chạy trên máy → tốn thêm độ trễ và tài nguyên.
- **Tốn thêm token/chi phí**: việc tóm tắt ngữ nghĩa phải gọi lại model AI để nén dữ liệu — sau 30 ngày thử miễn phí, chi phí này đổ về gói Anthropic của bạn hoặc API key riêng bạn tự trả.
- **Yêu cầu đăng nhập/tài khoản** để dùng phần "observer" hosted — tức có một phần luồng hoạt động phụ thuộc server bên ngoài, dù phần lưu trữ chính là local (SQLite).
- **Dấu hiệu đáng ngờ về uy tín**: README của dự án có nhắc đến một **token tiền mã hoá tên "CMEM"** do bên thứ ba tạo, được tác giả dự án "chính thức ủng hộ" (officially embraced). Đây là chi tiết bất thường và là cờ đỏ (red flag) đối với một công cụ chạy nền, có quyền quan sát toàn bộ hành vi coding của bạn (đọc file, lệnh chạy...). Không thể xác minh đầy đủ mục đích/tính hợp pháp của token này, chỉ ghi nhận là có tồn tại qua nhiều nguồn độc lập (README chính + vài trang mirror/blog) — khuyến nghị xem đây là lý do để dè chừng.
- **Chỉ có 1 maintainer chính** (Alex Newman) — rủi ro nếu người này ngừng duy trì, trong khi plugin lại chạm vào dữ liệu nhạy cảm (toàn bộ nội dung code/file bạn làm việc).
- **Không thấy README đề cập gì về xung đột với memory system có sẵn của Claude Code** — nghĩa là chưa có cam kết chính thức rằng nó tương thích tốt, phải tự kiểm chứng.

## 5. Độ trưởng thành của project

- Repo tạo ngày 2025-08-31, đến nay (16/10/2026) đã có **~101.000 sao, ~8.900 fork**, 99 issue đang mở, license Apache-2.0, commit gần nhất là hôm nay (16/10/2026) — về mặt hoạt động là rất tích cực.
- Cần lưu ý: nhiều bài viết thứ ba (chủ yếu từ một domain duy nhất, augmentcode.com) đưa ra các mốc sao tăng dần theo thời gian (65k → 74k → 83.9k → 101k qua các bài khác nhau) trông giống nội dung SEO tự động hơn là phân tích độc lập — số liệu dùng trong báo cáo này lấy trực tiếp từ GitHub API tại thời điểm research, không dựa vào các bài blog đó.
- Không tìm thấy thảo luận nào trên Reddit/Hacker News để đối chiếu đánh giá cộng đồng — độ phổ biến thực tế (ngoài số sao GitHub) chưa được xác minh độc lập.

## 6. Đánh giá: có nên dùng cho PiClass không

**Khuyến nghị: KHÔNG dùng claude-mem cho PiClass ở thời điểm hiện tại.**

Lý do:

1. **Claude Code đã có cơ chế memory riêng, đủ dùng**: Claude Code hiện có Auto-Memory tích hợp sẵn — file markdown (`MEMORY.md`) lưu ở `~/.claude/projects/<project>/memory/`, tự ghi và tự nạp lại giữa các session, hoàn toàn local, không cần đăng nhập, không tốn thêm API call riêng, không chạy tiến trình nền. Đây chính là cơ chế đang chạy thật trong workspace PiClass (đã thấy nội dung `MEMORY.md` thực tế của user trong context). Nhu cầu "nhớ giữa các session" mà claude-mem giải quyết, về cơ bản đã được đáp ứng bởi tính năng gốc.
2. **claude-mem là một hệ thống nặng hơn nhiều so với cái đang cần**: thêm một tiến trình nền, thêm 2 loại database (SQLite + Chroma), thêm hook chạy trên mọi tool call, thêm chi phí token để tóm tắt ngữ nghĩa — là sự đánh đổi hợp lý cho ai cần tìm kiếm ngữ nghĩa phức tạp qua rất nhiều project/nhiều agent, nhưng PiClass là một dự án đơn, một người làm chính, quy trình đã có kỷ luật ghi tài liệu rõ ràng (Decision Log, `open-decisions.md`, `progress.md`) — tức "bộ nhớ chính thức" của dự án đã nằm trong `docs/` theo chủ đích, không cần một lớp AI tự động tóm tắt thêm.
3. **Rủi ro niềm tin/privacy không đáng đánh đổi**: chi tiết về token tiền mã hoá "CMEM" được tác giả chính thức ủng hộ là một cờ đỏ rõ ràng cho một plugin có quyền quan sát toàn bộ hành vi coding (bao gồm có thể cả nội dung liên quan dữ liệu học sinh/giáo viên trong PiClass khi làm việc với code/migration/seed data thực). Không có lý do đủ mạnh để chấp nhận rủi ro này khi nhu cầu đã được giải quyết bởi tính năng gốc.
4. **Chi phí vận hành không cần thiết**: thêm Bun, thêm `uv`, thêm tiến trình nền trên máy dev — không mang lại giá trị tương xứng cho một dự án quy mô một người, một stack đã chốt gọn (Laravel + Next.js + 1 EC2).

Nếu sau này nhu cầu thay đổi (nhiều người cùng làm, cần tìm kiếm ngữ nghĩa qua lượng lịch sử coding rất lớn, muốn nhiều AI agent chia sẻ bộ nhớ), có thể xem lại — nhưng nên chờ dự án minh bạch hơn về "token CMEM" và có đánh giá cộng đồng độc lập rõ ràng hơn, thay vì chỉ dựa vào số sao GitHub hoặc các bài blog SEO.

### Nguồn tham khảo
- GitHub repo thedotmack/claude-mem: https://github.com/thedotmack/claude-mem (README chính thức)
- GitHub API — số liệu repo: https://api.github.com/repos/thedotmack/claude-mem
- docs/ip-boundary.md trong repo: https://github.com/thedotmack/claude-mem/blob/main/docs/ip-boundary.md (ranh giới open-core/thương mại)
- Claude Code Memory Files (morphllm.com): https://www.morphllm.com/claude-code-memory-files — bài viết cộng đồng, chưa xác minh độc lập, về Auto-Memory gốc của Claude Code
- Claude Code Memory, Explained (blog.memoryplugin.com): https://blog.memoryplugin.com/claude-code-memory/ — bài viết cộng đồng, chưa xác minh độc lập
- Các bài trên augmentcode.com và mdskills.ai có số liệu sao GitHub không đồng nhất theo thời gian — chỉ dùng tham khảo, không dùng làm số liệu chính vì có dấu hiệu là nội dung SEO tự động.

**Lưu ý minh bạch (chưa xác minh đầy đủ)**: phần nói về token "CMEM" chỉ được xác nhận qua README chính thức + vài trang mirror/blog, chưa có nguồn tin tức/báo chí độc lập xác minh bản chất hay mức độ liên quan thực sự của token này với dự án. Đủ để khuyến nghị thận trọng, nhưng không khẳng định đây là lừa đảo.
