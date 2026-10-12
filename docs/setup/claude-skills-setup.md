# Thiết lập skill/plugin Claude Code cho PiClass

**Mục đích:** Ghi lại chính xác các bước đã dùng để cài bộ skill hỗ trợ phát triển (Superpowers) và thiết kế UI/UX (`ui-ux-pro-max`, `frontend-design`) cho repo này, để chạy lại khi cần — máy mới, người mới, hoặc sau khi dọn `.claude/`.

**Phạm vi:** Tất cả cài đặt dưới đây ở **scope local/project-only**, ghi vào `.claude/` trong repo này. `.claude/` đã được thêm vào `.gitignore` — **không commit lên git**. Mỗi người, mỗi máy clone repo này phải tự chạy lại các bước này một lần.

**Dành cho AI agent chạy file này:** Thực thi tuần tự từng bước bằng shell. Trước mỗi bước có mục "Kiểm tra trước" — nếu điều kiện đó đã đúng thì bỏ qua bước đó (idempotent), không hỏi lại người dùng. Toàn bộ lệnh chạy từ thư mục gốc repo.

## 0. Yêu cầu

- Claude Code CLI đã cài (`claude --version` chạy được).
- Thư mục hiện tại là gốc repo PiClass (có file `.gitignore` chứa dòng `.claude/`).

## 1. Superpowers (quy trình phát triển: brainstorm → plan → TDD → subagent review)

**Marketplace:** `claude-plugins-official` — thường đã có sẵn mặc định, không cần add.

**Kiểm tra trước:**
```sh
claude plugin marketplace list
```
Nếu thấy `claude-plugins-official` trong danh sách → bỏ qua lệnh add ở dưới.

**Cài đặt:**
```sh
claude plugin install superpowers@claude-plugins-official --scope local
```

## 2. ui-ux-pro-max (hệ thống thiết kế: style/palette/typography theo loại sản phẩm)

**Marketplace:** `ui-ux-pro-max-skill` (repo `nextlevelbuilder/ui-ux-pro-max-skill`) — cần add riêng, không có sẵn mặc định.

**Kiểm tra trước:**
```sh
claude plugin marketplace list
```
Nếu thấy `ui-ux-pro-max-skill` → bỏ qua lệnh add.

**Cài đặt:**
```sh
claude plugin marketplace add nextlevelbuilder/ui-ux-pro-max-skill
claude plugin install ui-ux-pro-max@ui-ux-pro-max-skill --scope local
```

## 3. frontend-design (checklist chất lượng UI, chống giao diện "AI-generic")

Đây **không** cài qua plugin/marketplace — lý do: skill này nằm trong plugin gộp `example-skills` của repo `anthropics/skills`, nếu cài qua marketplace sẽ kéo theo 4 skill khác không liên quan (`document-skills`, `claude-api`, `academy-guide`, `discernment-nudge`), tốn context mỗi turn một cách không cần thiết. Thay vào đó tải trực tiếp 2 file của riêng skill này vào `.claude/skills/frontend-design/`.

**Kiểm tra trước:**
```sh
test -f .claude/skills/frontend-design/SKILL.md && echo "đã có, bỏ qua"
```

**Cài đặt:**
```sh
mkdir -p .claude/skills/frontend-design
curl -fsSL -o .claude/skills/frontend-design/SKILL.md \
  https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/SKILL.md
curl -fsSL -o .claude/skills/frontend-design/LICENSE.txt \
  https://raw.githubusercontent.com/anthropics/skills/main/skills/frontend-design/LICENSE.txt
```

## 4. Xác nhận sau khi cài

```sh
cat .claude/settings.local.json
```
Kỳ vọng thấy cả hai:
```json
{
  "enabledPlugins": {
    "superpowers@claude-plugins-official": true,
    "ui-ux-pro-max@ui-ux-pro-max-skill": true
  }
}
```

```sh
ls .claude/skills/frontend-design/
```
Kỳ vọng thấy `SKILL.md` và `LICENSE.txt`.

**Nạp lại trong session đang chạy** bằng lệnh slash `/reload-plugins` (gõ trực tiếp trong Claude Code, không phải lệnh shell) — không cần thoát/mở lại. Kết quả mong đợi dạng `Reloaded: N plugins · N skills · ...`. Nếu đổi cả MCP server/LSP mà không thấy áp dụng, dùng `/reload-plugins --force`.

## Ghi chú

- Lệnh `plugin marketplace add` luôn ghi marketplace vào config **user-level** (`~/.claude/`) của máy đang chạy, dù plugin cài với `--scope local` — đây là hành vi mặc định của Claude Code, không tránh được và vô hại (chỉ là "biết nguồn", chưa bật gì cho project khác).
- `Stitch` (stitch.withgoogle.com) không nằm trong danh sách cài ở đây vì đây là web app độc lập của Google, không chạy trong Claude Code — dùng trực tiếp trên trình duyệt để phác ý tưởng UI, không cần thiết lập gì trong repo.
- Nếu `.gitignore` của repo bị sửa và dòng `.claude/` biến mất, thêm lại dòng đó trước khi commit để tránh lỡ tay đưa cấu hình skill cá nhân lên git.
