# Thiết kế: Ghi danh khóa học qua liên kết Lớp–Khóa (Course–Class Link)

**Ngày:** 2026-10-26
**Phạm vi:** `piclass-api` (Academic, UserManagement) + `piclass-web` (Admin — AD-12/AD-12a)
**Trạng thái:** chờ duyệt (review xong, chờ builder-check rồi tới user)

## Bối cảnh và vì sao

Tính năng Ghi danh khóa học (AD-12a) vừa build xong và merge vào `main` (PR piclass-web #11): ghi danh "theo lớp" hiện là **thao tác một lần** (snapshot) — Admin bấm "Ghi danh" ở tab Theo lớp, hệ thống đọc danh sách thành viên đang active của lớp **tại đúng thời điểm đó** rồi tạo bản ghi `course_enrollments`. Không có liên kết sống nào giữa lớp và khóa sau đó.

User đã tự tay gặp đúng vấn đề này khi thử: thêm một học sinh mới vào lớp đang học dở một khóa, học sinh đó **không tự động** xuất hiện trong danh sách ghi danh của khóa — phải vào lại dialog Ghi danh và chọn lại đúng lớp để bổ sung thủ công. Với một trung tâm có nhiều lớp chạy song song nhiều khóa, bước thủ công này dễ bị quên.

Quyết định (bàn qua `superpowers:brainstorming`, 2026-10-26): chuyển mô hình ghi danh-theo-lớp từ "snapshot một lần" sang **"Course gắn với Class là một liên kết sống"**. Học sinh thuộc lớp có khóa gắn với lớp đó sẽ tự động có khóa, không cần thao tác lại mỗi khi lớp đổi thành viên. Ghi danh cá nhân theo từng học sinh (không qua lớp) giữ nguyên song song, không đổi.

## Mục tiêu (Goals)

1. Gắn một Course với một hoặc nhiều Class, và một Class gắn được nhiều Course (**nhiều–nhiều**, user chốt).
2. Học sinh đang là thành viên active của một Class có liên kết với Course → tự động có `course_enrollments` active cho Course đó.
3. Học sinh **rời Class** (bị gỡ khỏi `class_members`, đổi trạng thái non-active, hoặc Class bị lưu trữ) → enrollment do liên kết đó sinh ra **tự động kết thúc** (lý do tự động, ghi rõ nguồn gốc).
4. **Gỡ liên kết** Class–Course → toàn bộ enrollment đang active do liên kết đó sinh ra, của mọi học sinh trong lớp, **tự động kết thúc**.
5. Học sinh **quay lại** Class (được thêm lại, active) mà Class vẫn đang gắn Course → **tự động ghi danh lại** (bản ghi enrollment mới, active).
6. Ghi danh cá nhân theo học sinh (không qua lớp) giữ nguyên hành vi hiện có (nút "Kết thúc ghi danh" thủ công, bắt buộc lý do).
7. Tiến độ học tập (bài làm, điểm) của học sinh trong khóa **không mất** qua các lần kết thúc/ghi danh lại.

## Đã xác minh: không cần đổi gì cho mục tiêu 7

Đọc schema thật (`piclass-api/database/migrations/*`):

- `attempts.student_assessment_id` → `student_assessments` khóa theo **cặp `(assessment_id, student_id)`** (unique), **không hề có cột tham chiếu tới `course_enrollments`**.
- `results.submission_id` → cũng không liên quan gì tới bản ghi ghi danh.

Nghĩa là tiến độ học tập đã tách biệt hoàn toàn khỏi vòng đời của một bản ghi `course_enrollments` ngay từ thiết kế ban đầu. Tạo thêm bản ghi `course_enrollments` mới (kết thúc rồi ghi danh lại) **không ảnh hưởng gì** tới dữ liệu đã có. → Không cần đổi schema của nhánh Assessment/Submission/Result cho thiết kế này.

## Non-goals

- Không xử lý/backfill dữ liệu ghi danh-theo-lớp cũ đã tạo bởi tính năng one-off (user chốt: dự án đang dev, DB chưa có dữ liệu thật đáng giữ, chưa cần quan tâm).
- Không tự động đồng bộ **Assessment audience** khi học sinh join/leave lớp — đó là quyết định treo riêng (`ARCH-14`/`ARCH-28(g)` ở `docs/architecture/open-decisions.md`), cùng gốc vấn đề nhưng khác module, không nằm trong scope lần này.
- Không vẽ mockup chuẩn mới cho UI "Gắn lớp vào khóa" (user chốt: code trực tiếp bằng component/token design system sẵn có, không qua bước dựng HTML mockup riêng).
- Không đổi hành vi ghi danh cá nhân theo học sinh.

## Anti-goals

- Không được để một học sinh có 2 bản ghi `course_enrollments` **active** cùng lúc cho cùng một Course (giữ nguyên unique partial index hiện có).
- Không xóa/ghi đè lịch sử: mỗi lần kết thúc/ghi danh lại là **một bản ghi mới** (đúng pattern hiện có: `status` + `valid_from`/`valid_to` + `end_reason` + `ended_by_user_id`), không update đè bản ghi cũ.
- **Không cho "Kết thúc ghi danh" thủ công** trên một enrollment có nguồn từ liên kết lớp (user chốt: tránh chồng chéo) — chỉ kết thúc được qua (a) học sinh rời lớp, hoặc (b) gỡ liên kết lớp–khóa. Chặn cả ở FE (ẩn nút) lẫn BE (Policy/Action từ chối nếu gọi thẳng API).

## Mô hình dữ liệu

### Bảng mới `course_class_links`

| Cột | Kiểu | Ghi chú |
| --- | --- | --- |
| `id` | uuid, PK | |
| `course_id` | FK `courses`, cascade delete | |
| `class_id` | FK `classes`, cascade delete | |
| `status` | enum `active`/`unlinked`, default `active` | |
| `linked_by_user_id` | FK `users` | |
| `linked_at` | timestamp | |
| `unlinked_by_user_id` | FK `users`, nullable | |
| `unlinked_at` | timestamp, nullable | |

Unique **partial** index `(course_id, class_id)` where `status = 'active'` — chặn gắn trùng lặp đang active cho cùng cặp course–class (cùng tinh thần với unique index hiện có của `course_enrollments`). Một lớp có thể có nhiều liên kết **lịch sử** (gắn/gỡ/gắn lại) với cùng một khóa theo thời gian — giữ như lịch sử, không xóa.

### `course_enrollments` — thêm cột

`source_class_link_id`: FK `course_class_links`, **nullable**, `nullOnDelete`.

- `null` → ghi danh **cá nhân** (qua dialog "Theo học sinh") — giữ nguyên hành vi hiện tại y hệt, có nút "Kết thúc ghi danh".
- khác `null` → ghi danh **sinh ra từ liên kết lớp–khóa** — ẩn nút "Kết thúc ghi danh" riêng; chỉ kết thúc được tự động theo hai luồng ở mục Actions bên dưới.

## Luồng nghiệp vụ (BE, module Academic + UserManagement)

### `LinkClassToCourse` (Action mới, Academic)
1. Validate: Course và Class đang active (không cho gắn Course/Class đã lưu trữ); chưa có liên kết active trùng cặp.
2. Tạo `course_class_links` (status active).
3. Với mỗi `ClassMember` đang active của lớp: nếu học sinh **chưa có** `course_enrollments` active cho Course đó (bất kể nguồn nào) → tạo mới (active, `source_class_link_id` = liên kết vừa tạo). Nếu đã có (ví dụ ghi danh cá nhân từ trước) → **giữ nguyên, không đổi nguồn** của bản ghi đó (tôn trọng quyết định ghi danh cá nhân trước đó, tránh làm nó trở nên "dễ vỡ" một cách âm thầm khi học sinh rời lớp sau này).
4. Transaction + audit theo `DEC-AUD-001`.

### `UnlinkClassFromCourse` (Action mới, Academic)
1. Set `course_class_links.status = 'unlinked'`, `unlinked_by_user_id`, `unlinked_at`.
2. Kết thúc (status → `ended`, `end_reason` tự động như `"Gỡ khóa khỏi lớp {tên lớp}"`, `ended_by_user_id` = actor) toàn bộ `course_enrollments` đang active có `source_class_link_id` = liên kết này — **trừ** học sinh nào vẫn còn quyền học khóa đó qua một liên kết active khác (xem "Quy tắc đủ điều kiện" bên dưới).
3. Transaction + audit.

### Quy tắc đủ điều kiện (entitlement) — sửa sau builder-check

Một học sinh **được coi là đủ điều kiện** học một Course qua đường lớp khi: học sinh đang là thành viên active của **ít nhất một** Class đang có liên kết active với Course đó. Một học sinh có thể ở trong nhiều lớp cùng gắn chung một Course (ví dụ lớp A và lớp B cùng học khóa X) — khi đó chỉ có **một** bản ghi `course_enrollments` active (theo đúng unique index hiện có), với `source_class_link_id` trỏ tới một trong các liên kết hợp lệ (liên kết tạo ra nó đầu tiên).

Mọi hành động có thể làm mất quyền này (rời lớp, gỡ liên kết) đều phải tính lại entitlement trước khi kết thúc:
- **Trước khi kết thúc** một `course_enrollments` có `source_class_link_id` = X (vì lớp bị rời, hoặc liên kết X bị gỡ): kiểm tra thêm — học sinh còn active ở lớp nào khác **cũng** đang có liên kết active với cùng Course đó không?
  - Nếu **có** → không kết thúc; nếu bản ghi đang trỏ đúng liên kết vừa mất hiệu lực, cập nhật `source_class_link_id` sang liên kết hợp lệ còn lại (để nếu sau này lớp kia cũng rời/gỡ, luồng kiểm tra vẫn đúng).
  - Nếu **không** → kết thúc như mô tả.

Điều này áp dụng cho cả hai luồng dưới đây và cho `UnlinkClassFromCourse`.

### Đồng bộ khi thành viên lớp thay đổi hoặc lớp bị lưu trữ (gọi Action trực tiếp, không qua Event)

Rà lại theo `piclass-api/CLAUDE.md`: codebase hiện **chưa có Event/Listener nào** ngoài quy ước dành riêng cho Notification ("Event = Notification, không dùng cho luồng chính hay audit bắt buộc"). Đây là luồng chính có audit bắt buộc → **không dùng Event**. Đồng thời rule R4/R5 ("không tạo vòng gọi Action giữa hai module") chỉ cấm **hai chiều** (A gọi Action của B *và* B gọi Action của A); ở đây chỉ có một chiều (UserManagement → Academic, Academic không gọi ngược lại UserManagement), nên **gọi thẳng Action của Academic qua DI là hợp lệ**, không cần Event:

- **Rời lớp** (gỡ khỏi `class_members`, đổi trạng thái non-active): Action `EndClassMember`/tương đương của UserManagement, sau khi ghi thay đổi, gọi Action `SyncCourseEnrollmentsOnClassMemberLeft` của Academic (inject qua constructor) **trong cùng transaction** — áp dụng quy tắc đủ điều kiện ở trên, lý do tự động `"Rời khỏi lớp {tên lớp}"`, `ended_by_user_id` = actor.
- **Vào lại lớp** (`class_members` mới hoặc active trở lại): Action `AddClassMember`/`UpdateClassMember` gọi Action `SyncCourseEnrollmentsOnClassMemberJoined` của Academic, cùng transaction — với mỗi liên kết active của lớp đó mà học sinh chưa đủ điều kiện (chưa có enrollment active cho Course đó qua đường nào) → tạo enrollment mới (active, nguồn = liên kết đó).
- **Lớp bị lưu trữ** (`UpdateClass`, đã nằm trong Academic — cùng module sở hữu `CourseClassLink`, không phải gọi chéo module): sau khi set `classes.status = archived`, cùng transaction: (a) với mỗi liên kết active của lớp, gỡ liên kết (`status = unlinked`, giống `UnlinkClassFromCourse`, actor = người lưu trữ lớp) — tránh để lại liên kết "active" treo trên một lớp đã lưu trữ; (b) áp dụng quy tắc đủ điều kiện để kết thúc các enrollment không còn ai giữ chỗ qua lớp khác. `class_members` của lớp archived **không đổi** (lưu trữ lớp hiện tại không cascade xuống `class_members`, giữ nguyên hành vi hiện có) — chỉ phần liên kết/ghi danh Course bị ảnh hưởng.

## API / FE (Admin, AD-12/AD-12a)

- Course detail: thêm khu vực **"Lớp đã gắn"** — danh sách lớp đang linked với khóa, nút **"Gắn lớp"** (chọn 1 lớp chưa gắn, trong các lớp chưa lưu trữ) và **"Gỡ lớp"** mỗi dòng (dialog xác nhận, không cần lý do — cùng tinh thần dialog Lưu trữ khóa hiện có).
- Dialog "Ghi danh vào khóa học": **bỏ hẳn tab "Theo lớp"** — dialog chỉ còn tab ghi danh cá nhân theo học sinh. Việc gắn lớp chuyển hẳn sang khu vực "Lớp đã gắn" ở trên, vì giờ đây nó là một hành động khác về bản chất (tạo liên kết sống, không phải ghi danh một lần).
- Tab "Ghi danh" (danh sách học sinh): mỗi dòng hiện thêm nguồn ghi danh khi đến từ lớp (ví dụ nhãn nhỏ `"Qua lớp {tên lớp}"`), để Admin hiểu vì sao dòng đó **không có** nút "Kết thúc ghi danh" (chỉ dòng ghi danh cá nhân mới có nút này).

## Việc để lại cho người triển khai (builder)

- Tên chính xác của 2 Action Academic mới (`SyncCourseEnrollmentsOnClassMemberLeft`/`...Joined` chỉ là tên gợi ý) và cách inject vào Action hiện có của UserManagement (`EndClassMember`/`UpdateClassMember`/`AddClassMember` — đọc đúng tên file thật trước khi code, tên trên chỉ là phỏng đoán).
- Plan BE cần ma trận test: gắn lớp có sẵn thành viên / gỡ lớp / học sinh rời lớp / học sinh quay lại lớp / học sinh có ghi danh cá nhân từ trước khi lớp được gắn (không bị đổi nguồn) / thử gọi API "Kết thúc ghi danh" thẳng lên một enrollment nguồn-từ-lớp (phải bị chặn) / **học sinh thuộc 2 lớp cùng gắn chung 1 khóa, rời 1 trong 2 lớp → vẫn giữ ghi danh qua lớp còn lại** / lớp bị lưu trữ → liên kết tự gỡ, enrollment tương ứng tự kết thúc trừ khi học sinh còn được giữ qua lớp khác.
- Copy tiếng Việt chính xác cho "Gắn lớp"/"Gỡ lớp" và nhãn nguồn ghi danh — lấy giọng văn nhất quán với các dialog Archive/End hiện có (Enroll dialog, Archive dialog), vì không có mockup HTML riêng cho phần này.
- BE trước, FE sau, theo đúng quy trình "từng lát" đã áp dụng cho AD-12/AD-12a (plan BE riêng, plan FE riêng).

## Quyết định đã chốt bởi user (2026-10-26)

1. Học sinh rời lớp → tự động kết thúc ghi danh khóa gắn với lớp đó; chuyển lớp giữa chừng khi gần hết khóa là chuyện khác (chấp nhận bị kết thúc).
2. Một lớp gắn được nhiều khóa, một khóa gắn được nhiều lớp — quan hệ nhiều–nhiều.
3. Gỡ liên kết lớp–khóa → toàn bộ học sinh trong lớp bị kết thúc ghi danh khóa đó.
4. Học sinh quay lại lớp → tự động có lại các khóa đang gắn với lớp (đúng theo liên kết hiện tại, không phục hồi các liên kết đã gỡ trước đó).
5. Không có nút "Kết thúc ghi danh" riêng cho enrollment sinh từ liên kết lớp, để tránh chồng chéo — muốn bỏ một em ra thì phải đưa em ra khỏi lớp hoặc gỡ cả liên kết.
6. Tiến độ học tập (điểm, bài làm, lịch sử) không phụ thuộc bản ghi ghi danh nào — đã xác minh qua schema thật, không cần đổi.
7. Không cần xử lý/backfill dữ liệu ghi danh-theo-lớp cũ (dự án đang dev, chưa có dữ liệu thật cần giữ).
8. Không cần vẽ mockup mới cho UI "Gắn lớp" — code trực tiếp theo design system sẵn có.
