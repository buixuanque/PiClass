# Report: Đánh giá plugin Caveman (JuliusBrussee/caveman) cho Claude Code

## 1. Caveman là gì, giải quyết vấn đề gì

Caveman là một **plugin/skill cho các coding agent AI** (Claude Code, Codex, Gemini CLI, và hơn 30 agent khác), không chỉ riêng Claude Code. Mục đích duy nhất: **giảm số token** (đơn vị tính phí/giới hạn của model AI) mà agent dùng, bằng cách:

- Làm agent **trả lời ngắn gọn** kiểu "người tiền sử" (câu cụt, bỏ phần giải thích dư) — nhưng **không đụng vào code hay lệnh thực thi**, chỉ ngắn phần giải thích bằng lời.
- **Nén log/JSON/CSV/output test** trước khi đưa cho model đọc, để model tốn ít token đầu vào hơn.

Tác giả gọi khẩu hiệu: "why use many token when few token do trick" — tức tiết kiệm chi phí gọi API model, không phải thêm năng lực kỹ thuật mới.

## 2. Cơ chế kỹ thuật

Repo gồm 6 "mảnh" chức năng:

| Tính năng | Mô tả |
|---|---|
| **Say less** | Rút ngắn câu trả lời bằng lời, giữ nguyên code/lệnh |
| **Read less** | Nén log/JSON/YAML/output test trước khi model đọc |
| **Auto** | Tự chọn model + mức "effort" cho từng yêu cầu — cần tài khoản Caveman (miễn phí), dữ liệu được gửi ra ngoài (xem mục 4) |
| **Blocks** | Lưu lại script agent hay viết để tái dùng — đang ở bản **pre-release** (v0.1.0-rc.5), chỉ hỗ trợ Python, chưa có sandbox |
| **Waste fixes** | Tìm chỗ "lãng phí" token — tác giả tự ghi là **chưa đo được hiệu quả** |
| **Browse** | Nén trang web trước khi trả cho agent — tác giả tự ghi là thua 2.3 lần với trang/form nhỏ |

Về hạ tầng: repo có `hooks/`, `mcp/` (MCP server tên `caveman-shrink`), `agents/`, `commands/`, `skills/`, và một `proxy/` viết bằng Go (`go.mod`), cùng `supabase/` (có vẻ backend cho tính năng Auto/Cloud trả phí). Có các slash command: `/caveman`, `/ultracave`, `/megacave` (3 mức ngắn gọn), `/caveman-commit` (tạo commit message 1 dòng theo Conventional Commits), `/caveman-review`, `/caveman-compress <file>` (nén file như `CLAUDE.md`), `/caveman-stats`, `/caveman-help`. Có sẵn một skill con tên **cavecrew**: các subagent tự tìm/sửa/review code rồi báo cáo ngắn gọn để đỡ tốn context chính.

## 3. Cài đặt & dùng cơ bản

- Yêu cầu Node.js ≥ 22.13, npm ≥ 12.
- Claude Code: `claude plugin marketplace add JuliusBrussee/caveman` rồi `claude plugin install caveman@caveman` (hoặc gõ `/plugin` trong Claude Code).
- Hoặc cài tự động mọi agent có trên máy: `npx @caveman-ai/cli@latest`.
- Chỉ lấy skill (không cần toàn plugin): `npx skills add JuliusBrussee/caveman -g`.
- Gỡ: `npx -y github:JuliusBrussee/caveman -- --uninstall`.
- **Plugin tự bật ở mọi session tương tác, kể cả subagent**, ngay khi cài — không phải thứ phải gọi thủ công mỗi lần.

## 4. Ưu điểm, nhược điểm, giới hạn

**Ưu điểm:**
- Dễ cài, hỗ trợ đa agent, có lệnh tiện (tạo commit message, xem thống kê token).
- README minh bạch bất thường — tự liệt kê rõ các điểm yếu/số liệu chưa đo (hiếm thấy ở project quảng cáo mạnh).

**Nhược điểm / giới hạn (tự thừa nhận trong README, không phải suy diễn):**
- Số liệu tiết kiệm token là **tự đo, chưa kiểm chứng độc lập**: README ghi 33.2% token đầu vào trên 54 phiên; trang quảng cáo từng ghi 75%, sau đó hạ xuống "khoảng 65% token output". Các con số từ blog bên thứ ba (35–90%) đều dẫn lại số của chính tác giả.
- Không tiết kiệm gì nếu trả phí theo request cố định (ví dụ GitHub Copilot).
- HTML chưa có bộ nén (làm việc tệ hơn 9.9%); Browse thua form nhỏ 2.3 lần; Blocks còn pre-release, không sandbox; nhiều benchmark (Auto, warm cache...) được chính tác giả ghi "chưa đo lại/chưa đủ dữ liệu".
- **Telemetry bật mặc định** (tên lệnh, số token, OS, IP, ID cài đặt ngẫu nhiên — không gửi prompt/code/path). Tắt được qua `caveman telemetry off`.
- **Tính năng Auto gửi dữ liệu ra ngoài theo mặc định**: câu hỏi mới nhất, lượt hội thoại trước, cuối câu trả lời của agent, và **các file đính kèm như CLAUDE.md** được gửi tới dịch vụ Caveman để chọn model/effort. Gói miễn phí có thể giữ lại dữ liệu này để cải thiện định tuyến. Tắt được qua `caveman off routing`.
- Lệnh `/caveman-compress` gửi nguyên file bạn nén (ví dụ CLAUDE.md) tới provider model đã cấu hình.

## 5. Độ trưởng thành project

- **Tạo:** 04/04/2026. **Commit gần nhất:** 15/10/2026 (gần như hoạt động hàng ngày — rất active).
- **Stars:** ~113.000. **Forks:** ~6.500. **Open issues:** 74.
- **License:** Apache-2.0 (kèm file `LICENSE-MIT`, có thể một phần code dual-license).
- Bản hiện tại: v4.0.1 — module Blocks còn pre-release.
- Nhận định: tăng trưởng star/fork cực nhanh so với tuổi repo (~6 tháng) — dấu hiệu dự án viral/được truyền thông mạnh (có bài blog, Substack viết riêng), nhưng đi kèm rất nhiều tính năng tự nhận "chưa đo" hoặc "pre-release". Đây là dự án **mới, phát triển nhanh nhưng chưa ổn định/kiểm chứng lâu dài**, không phải công cụ đã qua thử thách thời gian.

## 6. Có nên dùng cho PiClass không?

**Không nên cài cho dự án PiClass ở thời điểm hiện tại.** Hai lý do cụ thể, không phải "còn tùy":

1. **Mâu thuẫn trực tiếp với quy tắc đã chốt trong `CLAUDE.md` của PiClass**: dự án yêu cầu khi hỏi/chốt rule thiết kế hay business logic phải **giải thích đầy đủ cả hướng kỹ thuật và hướng nghiệp vụ** (ai làm gì, ảnh hưởng người dùng cuối ra sao), không liệt kê trừu tượng. Cơ chế cốt lõi của Caveman là "trả lời càng ngắn càng tốt" — đi ngược lại đúng yêu cầu này. Nếu bật plugin toàn cục, Claude Code sẽ có xu hướng cắt ngắn giải thích ngay cả ở các lúc dự án cần chi tiết nhất (brainstorm kiến trúc, chốt Decision Log).
2. **Rủi ro rò dữ liệu nội bộ ra ngoài**: tính năng Auto (mặc định cần tài khoản) gửi nội dung file đính kèm như `CLAUDE.md` ra dịch vụ bên thứ ba để định tuyến model. `CLAUDE.md`/docs của PiClass chứa toàn bộ ngữ cảnh business rule, access control, stack đã chốt — không phải dữ liệu học viên thật, nhưng vẫn là thông tin nội bộ dự án không nên gửi ra ngoài mặc định.

Lợi ích tiết kiệm token (vốn là lý do duy nhất để dùng) lại dựa trên số liệu tự đo, chưa kiểm chứng độc lập, và không gắn gì đặc thù với Laravel/Next.js (đây là tool generic cho mọi agent/ngôn ngữ).

**Nếu vẫn muốn thử có chọn lọc:** chỉ dùng lệnh đơn lẻ `/caveman-commit` (tạo commit message ngắn theo Conventional Commits, khớp convention code tiếng Anh PiClass đang dùng) mà không cài toàn plugin, tắt hẳn Auto (`caveman off routing`) và telemetry — tránh dùng trong các phase brainstorm/spec/clarify. Nhưng nhìn chung, giá trị thêm cho PiClass hiện không đủ bù rủi ro lệch hướng giải thích và rò thông tin nội bộ.

---

### Nguồn tham khảo
- [github.com/juliusbrussee/caveman](https://github.com/juliusbrussee/caveman) — README, mô tả tính năng, cài đặt, caveat (tài liệu chính thức của tác giả)
- [raw.githubusercontent.com/.../README.md](https://raw.githubusercontent.com/JuliusBrussee/caveman/main/README.md) — nội dung README đầy đủ về command, giới hạn, telemetry
- [api.github.com/repos/juliusbrussee/caveman](https://api.github.com/repos/juliusbrussee/caveman) — stars/forks/license/ngày commit (dữ liệu GitHub API, đáng tin)
- [The Complete Guide to Caveman (aifordevelopers.substack.com)](https://aifordevelopers.substack.com/p/the-complete-guide-to-caveman) — nguồn cộng đồng, có nhắc số liệu tiết kiệm token (75% ban đầu, sau hạ xuống), **chỉ nên coi là tham khảo, không phải xác nhận độc lập**
- [Claude Code Caveman Mode (pasqualepillitteri.it, tiếng Đức)](https://pasqualepillitteri.it/de/news/849/claude-code-caveman-mode-token-sparen) — nguồn cộng đồng, nhắc số star ~89k tại thời điểm viết (thấp hơn số hiện tại do repo tăng nhanh) — **chưa xác minh độc lập**
