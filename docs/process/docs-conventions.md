# Quy ước tài liệu và cách đọc

Đọc file này khi: có mâu thuẫn giữa các tài liệu, cần chốt quyết định mới, cần đọc một đặc tả lớn, hoặc làm research.

## Thứ tự ưu tiên tài liệu (source of truth)

Khi mâu thuẫn, ưu tiên:

1. `docs/requirements/*`: business rules đã chốt, Decision Log `DEC-xxx`.
2. `docs/access-control/*`: role, permission, data scope.
3. `docs/database/*`: schema, quan hệ, lifecycle.
4. `docs/product/*`, `docs/flows/*`, `docs/domain/*`: mục tiêu, scope, flow, thuật ngữ.
5. `docs/architecture/*`: tổ chức kỹ thuật; không được tự đổi business rule ở trên.

Chốt quyết định mới: cập nhật ngay tài liệu nguồn (Decision Log, `open-decisions.md`, `technology-decisions.md`...), không để chỉ trong chat. Phát hiện mâu thuẫn giữa tài liệu: ghi vào `open-decisions.md` của tầng liên quan, không tự chọn một phía rồi im lặng.

## Đọc file lớn theo đoạn

Các file rất dài (`product/screens/admin.md` ~100KB, `teacher-bai-danh-gia.md`, `student-parent.md`, `requirements/decision-log.md`, `architecture/known-debt.md`): không `Read` cả file. `grep -n "AD-10"` (hoặc `ARCH-37`, `DEC-SCH-005`) lấy số dòng rồi `Read` với `offset` và `limit`. Đầu file đặc tả màn có "Mục lục" theo mã màn. `known-debt.md` nhóm theo module (`grep -n "^## Lesson"`, ID `D-xx`); chạm tới code có trong đó thì cân nhắc xử lý và xóa dòng. `ARCH-xx` từ 13 đọc ở `architecture/decisions/ARCH-xx.md`; `open-decisions.md` chỉ là mục lục.

**Không đọc mặc định:** `docs/BACKLOG.md` (việc chưa làm) và `docs/archive/*` (~2MB). Chỉ mở đúng một file archive khi được link tới; không grep hay Explore quét cả thư mục.

## Hỏi để chốt thiết kế

Khi hỏi user chốt rule hoặc thiết kế không thuần code (hiển thị theo role, business logic ảnh hưởng người dùng cuối, phân quyền): giải thích **cả hai hướng**, kỹ thuật và nghiệp vụ thực tế (ai làm gì, vì sao, ảnh hưởng tới người dùng cuối). Câu thuần kỹ thuật thì không cần.

## Research reports

Dùng agent `researcher` (hoặc research, so sánh công nghệ tương tự): sau report cuối, luôn lưu vào `docs/research/YYYY-MM-DD-<slug>.md`. Agent không tự lưu; main session chịu trách nhiệm.
