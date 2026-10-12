# Research: Superpowers plugin — vận hành thực tế

**Ngày:** 2026-10-12
**Bối cảnh:** Đã cài plugin `superpowers@claude-plugins-official` (scope local, xem `docs/setup/claude-skills-setup.md`). Report này đọc trực tiếp toàn bộ 15 file `SKILL.md` đã cài trên máy, để biết thật sự nó chạy ra sao khi dùng cho việc phát triển PiClass (vd Step 9 API spec, các module trong `architecture/module-boundaries.md`).

---

## 1. Cái gì chạy đầu tiên

`using-superpowers` là "cổng chặn" đầu tiên — bắt AI phải tự hỏi "có skill nào áp dụng không?" trước khi trả lời bất cứ gì, dù chỉ 1% khả năng áp dụng cũng phải dùng. Với yêu cầu kiểu "xây dựng X" / "thêm tính năng Y", skill tiếp theo gần như luôn là `brainstorming`.

## 2. Bước phân loại ban đầu

`brainstorming` xếp mọi yêu cầu vào 1 trong 3 loại, và phải nói rõ loại đó cho người dùng biết:

- **Spike** — câu hỏi khả thi ("liệu có làm được không"), không giữ code.
- **Bounded** — sửa/thêm nhỏ trên code đã có, chỉ cần vài câu duyệt trong chat.
- **Architectural** — dự án/module mới, đổi cách các phần ghép với nhau.

Dù loại nào: **không viết code trước khi người dùng duyệt bước thiết kế** — gọi là "hard gate", không có đường vòng. Phát hiện việc phức tạp hơn giữa đường thì phải nâng loại lên, không được hạ xuống.

## 3. Trình tự đầy đủ cho việc "architectural" (vd Step 9 API spec)

1. **Brainstorming** — hỏi từng câu về mục đích/giới hạn, đề xuất 2-3 cách làm, trình bày thiết kế theo từng phần, xin duyệt từng phần.
2. **Viết spec** → lưu `docs/superpowers/specs/...`, tự rà soát, commit.
3. **Người dùng duyệt spec** — cổng chặn thứ nhất.
4. **Writing-plans** — chỉ sau khi spec duyệt mới viết kế hoạch chi tiết (chia Task nhỏ, có code mẫu/cách test) → `docs/superpowers/plans/...`.
5. **Người dùng duyệt plan + chọn cách thực thi**:
   - **Subagent-driven**: mỗi Task giao 1 "phiên AI con" độc lập làm + 1 phiên khác review riêng trước khi sang Task kế — kỹ nhất, tốn chi phí nhất.
   - **Native**: tự làm hết trong phiên hiện tại, cuối cùng mới review 1 lần — rẻ, nhanh, ít kiểm tra chéo giữa chừng.
6. **TDD** — viết test trước, chạy cho "thất bại" (vì chưa có code thật), rồi viết code tới khi test "đậu".
7. **Code review** — AI khác đóng vai reviewer, tối đa 5 vòng sửa/review mỗi Task.
8. **Verification** — cấm nói "xong rồi" nếu chưa thực sự chạy lệnh kiểm tra và thấy kết quả ngay trong lượt đó.
9. **Finishing branch** — chọn 1 trong 3: gộp ngay, tạo PR, hoặc giữ nhánh riêng. Xóa bỏ công việc chỉ xảy ra khi gõ đúng chữ "discard".

Toàn bộ tốn vài chục phút đến vài giờ AI làm việc, dừng chờ người dùng **ít nhất 3 lần** (duyệt spec, duyệt plan + chọn cách làm, chọn cách gộp code).

## 4. Skill phụ (không nằm luồng chính)

- `using-git-worktrees` — tạo bản sao code riêng để làm việc không đụng code chính, tự gọi trước khi triển khai plan.
- `dispatching-parallel-agents` — 2+ việc độc lập hoàn toàn thì chạy song song.
- `systematic-debugging` — bật khi có bug, cấm sửa bừa theo kiểu đoán.
- `diagnosing-superpowers` — khi chính quy trình này chạy sai, muốn biết tại sao.
- `writing-skills` — tạo/sửa skill mới.

## 5. Cần biết trước khi dùng thật

- **Tốn token theo cấp số** ở cách subagent-driven — mỗi Task ít nhất 2 phiên AI mới, mỗi phiên phải đọc lại code từ đầu vì không có bộ nhớ chung.
- **Không tự đọc docs có sẵn của repo** nếu không được nhắc — cần chủ động trỏ nó vào `docs/requirements/*`, `docs/access-control/*`, `docs/database/*` trước khi dùng cho Step 9, không để nó tự hỏi lại từ đầu.
- **Mỗi phê duyệt chỉ có giá trị cho đúng bước đó** — đồng ý ý tưởng không phải đồng ý spec, đồng ý spec không phải đồng ý plan.
- **Việc nhỏ cũng không được bỏ qua bước duyệt** — không có ngoại lệ vì "nhìn đơn giản quá".
- **Ít hỏi lại giữa lúc thực thi plan** — tự quyết các mâu thuẫn nhỏ (ghi "Ruling" vào nhật ký tiến độ), chỉ dừng hẳn khi gặp việc không thể đảo ngược hoặc nhạy cảm.

## Nguồn

Toàn bộ 15 file `SKILL.md` tại `C:\Users\Que.Bui\.claude\plugins\cache\claude-plugins-official\superpowers\6.4.1\skills\{brainstorming,diagnosing-superpowers,dispatching-parallel-agents,executing-plans,finishing-a-development-branch,receiving-code-review,requesting-code-review,subagent-driven-development,systematic-debugging,test-driven-development,using-git-worktrees,using-superpowers,verification-before-completion,writing-plans,writing-skills}\SKILL.md` — đọc trực tiếp, không dùng nguồn bên ngoài.
