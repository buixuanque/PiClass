# Nợ kỹ thuật — chi tiết đầy đủ

Nguyên văn các dòng dài của [`known-debt.md`](known-debt.md) (chuyển 2026-10-28). Mục lục và tóm tắt ở file đó; mở đúng mục cần (`grep -n "^### D-33"`).

### D-07

**Nợ:** Nhánh teacher trong `AttendanceRecordPolicy` (ghi) và `AbsenceExplanationPolicy::review` bỏ qua vai trò đang hoạt động: user có hồ sơ teacher nhưng đang ở vai trò `parent` vẫn `allowed` (không tới được qua HTTP vì route `role:admin,teacher`; giống `authorizeTeacherWrite` cũ), lệch `ARCH-11` ở tầng Policy

**Ở đâu:** `Attendance/Policies/{AttendanceRecordPolicy,AbsenceExplanationPolicy}.php`

**Sửa khi:** Hướng: yêu cầu `code() === 'teacher'` (hoặc rỗng cho job), hoặc tách site theo vai trò. Thảo luận ở issue #21

### D-14

**Cập nhật 2026-10-28 (rà lỗi thời):** chỉ còn ý cuối (`POST /courses` của teacher không hồ sơ trả 500); #34 (`TeacherNotActive`), #35 (audit `course_enrollment.enroll`), #36 (`CourseArchived`/`SubjectInactive`), #37 (`CoursePolicy` bắt buộc hồ sơ Teacher) đã xong. Nguyên văn cũ dưới đây.

**Nợ:** Khoảng cách/nợ phát hiện khi lên plan Academic (đã có issue): Teacher thiếu hồ sơ khớp `null === null` với Course dùng chung (#37); chặn thêm mới vào Course `archived`/Subject `inactive` (#36); audit ghi danh Course (#35); chỉ phân công Teacher `active`, đóng `ARCH-23(p)` (#34); `POST /courses` của tài khoản vai trò `teacher` không có hồ sơ Teacher trả 500 (`teacherProfile->id` trên null, không ghim trong test)

**Ở đâu:** `Academic/Policies/CoursePolicy`, `Academic/Actions/EnrollInCourse`, `Academic/Actions/CreateClass`, `UserManagement/Actions/AssignTeacherToClass`, `Academic/Http/Requests/CreateCourseRequest`

**Sửa khi:** PR đổi hành vi riêng sau Task 9f — xem `ARCH-28` ở `open-decisions.md`

### D-32

**Cập nhật 2026-10-28 (rà lỗi thời):** nhánh `update-series` đã merge (PR #53), FE không còn gọi `PATCH /schedules/{id}`; user chốt giữ route (2026-10-28). Phần "đang giải quyết" và "xóa dòng này khi nhánh merge" dưới đây đã lỗi thời.

**Nợ:** `PATCH /schedules/{id}` (sửa mẫu lịch) gần như không có tác dụng lên buổi đã sinh: `UpdateSchedule` chỉ sinh thêm phần **còn thiếu** (generator bỏ qua ngày đã có buổi), nhưng từ khi `effective_until` bắt buộc và sinh đủ toàn bộ ngay lúc tạo/sửa (`DEC-SCH-004`), hầu như không còn phần thiếu nào — đổi giờ/thời lượng/thứ trên mẫu lịch không cập nhật lại các `LessonSession` đã tồn tại. Phát hiện 2026-10-27 khi bàn `ARCH-37`; **đang giải quyết bằng `UpdateScheduleAndFollowingSessions` (`PATCH /lesson-sessions/{id}/update-series`, nhánh `feat/merge-schedule-sessions-update-series`, chưa merge)**, không vá được bằng cách sửa nhỏ `UpdateSchedule`; xóa dòng này khi nhánh đó merge `main`

**Ở đâu:** `piclass-api/app/Modules/Lesson/Actions/UpdateSchedule.php`

**Sửa khi:** Cùng lúc làm `ARCH-37` (gộp tab Lịch/Buổi học)

### D-33

**Nợ:** `UpdateScheduleAndFollowingSessions` (2026-10-27, đã merge PR #53) không thể làm sống lại một buổi đã `cancelled` bởi chính nó: `GenerateLessonSessions` dedupe theo `occurrence_date` không xét `status`, cộng với unique index `(schedule_id, occurrence_date)`. Không chỉ riêng trường hợp đổi `weekdays` qua lại — **kích hoạt đơn giản nhất chỉ cần 1 lần sửa**: rút ngắn `effective_until` (các buổi sau ngày đó bị hủy), rồi nới dài lại `effective_until` (qua `update-series` hoặc `PATCH /schedules/{id}` thường) — các buổi đã hủy đó **vĩnh viễn không được sinh lại** dù ngày đó vẫn khớp `effective_until`/`weekdays` mới — lớp thiếu buổi cho tới khi ai đó tạo tay. Việc đổi `weekdays` qua lại (A→B→A) là một kích hoạt khác của cùng gốc. Phát hiện ở review Task 3; mở rộng phạm vi mô tả sau review toàn nhánh (opus, review trước đó mô tả hẹp hơn thực tế). Chốt chấp nhận làm nợ (không sửa ngay vì sửa đúng cần đánh dấu được "buổi bị hủy vì lệch lịch/rút ngắn" khác với "buổi bị hủy có chủ đích", hoặc đổi unique index — cả hai đều lớn hơn phạm vi task)

**Ở đâu:** `piclass-api/app/Modules/Lesson/Actions/{UpdateScheduleAndFollowingSessions,GenerateLessonSessions}.php`

**Sửa khi:** Nếu gặp thật (không hiếm như nghĩ ban đầu — chỉ cần rút ngắn rồi nới dài lại `effective_until` một lần): thêm cột đánh dấu nguồn hủy (vd. `cancelled_by = 'series_update'` vs `'manual'`) để sweep biết buổi nào được phép "sống lại"; FE nên cảnh báo khi rút ngắn `effective_until`

### D-22

**Cập nhật 2026-10-28 (rà lỗi thời):** baseline hiện 146 mục / 204 lượt lỗi, ~104/124 method quan hệ trong `app/Models` vẫn thiếu kiểu trả về; số "188" và "592 ở mức 6" dưới đây là số cũ.

**Nợ:** PHPStan mức 5 còn **188 lỗi trong `phpstan-baseline.neon`**, gần như toàn bộ do cùng một nguyên nhân: các method quan hệ Eloquent trong `app/Models` chưa khai báo kiểu trả về (`: HasOne`, `: BelongsTo`...) nên Larastan không nhận ra thuộc tính quan hệ (`User::\$studentProfile`...) và tên quan hệ trong `with()/whereHas()`; còn lại ~8 lỗi nhỏ (`?->` thừa trước `??`, một kiểu trả về `Collection`)

**Ở đâu:** `app/Models/*`, `phpstan-baseline.neon`, `StudentAssessmentMetadataQuery`, `RecalculateResult`, `StudentProgressQuery`

**Sửa khi:** Task riêng (không đổi hành vi): thêm kiểu trả về cho quan hệ, sửa 8 lỗi nhỏ, tạo lại baseline cho nhỏ đi, rồi nâng mức 5 → 6 (hiện 592 lỗi ở mức 6, chủ yếu thiếu generic)

### D-23

**Cập nhật 2026-10-28 (rà lỗi thời):** docblock Resource gần như xong (còn 4 file), `#[QueryParameter]` chưa có chỗ nào, `openapi.json` còn 13 chỗ `items: {}`; số cảnh báo chưa kiểm lại. Nguyên văn cũ dưới đây.

**Nợ:** OpenAPI sinh tự động còn khoảng trống: khoảng 10 response 2xx yếu (các `*ListResource`, `ProgressResource`, `ResultResource`... suy ra `data: string`/`items: {}`; `active_role`/`available_roles` không kiểu; endpoint body rỗng bị mô tả mảng rỗng); phân trang và bộ lọc query không suy ra (controller đọc `$request->query` trực tiếp); thiếu danh mục `error.code`/409 (nguồn vẫn là `error-codes.md`); 25 cảnh báo khi export (21 `JR001`, 4 `VR002` ở `GradeQuestionRequest`/`GradeSubmissionRequest`)

**Ở đâu:** `piclass-api/app/Providers/OpenApiServiceProvider.php`, `app/Http/Resources`, các Resource/Request trên

**Sửa khi:** Task riêng không đổi hành vi: docblock `@mixin`/`@property` cho ~20 Resource, `#[QueryParameter]` cho query
