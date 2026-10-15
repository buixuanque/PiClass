# Step 9 — API Specification: Question Bank + Assessment

**Trạng thái:** Spec đã được duyệt theo từng section qua brainstorming (`superpowers:brainstorming`), chờ user review file này trước khi sang `writing-plans`.
**Phạm vi:** Nhóm module thứ ba của Step 9 — `Question Bank` + `Assessment` theo ranh giới tại `docs/architecture/module-boundaries.md`. Không bao gồm `Attempt`/`Answer`/`Submission`/`Grade`/`Result`/`Feedback` (nhóm `Submission/Grading` + `Result/Progress`, Module 4 — xem §9 "Ngoài phạm vi").
**Không tạo business rule mới.** Mọi hành vi dưới đây chỉ chuyển các rule đã chốt ở `docs/requirements/assessment-rules.md`, `docs/requirements/learning-rules.md` (Course, `DEC-LRN-010`/`BR-COURSE-001..004`), `docs/access-control/*`, `docs/database/schema.md` thành endpoint cụ thể.
**Quyết định ngoại lệ về thứ tự (ghi lại theo `docs/progress.md`, 2026-10-14):** nhóm module trước (Academic + Lesson + Attendance) mới xong BE, FE (Plan 3b) chưa làm. Theo yêu cầu người dùng, spec/code BE của nhóm này được làm trước, không chờ FE nhóm trước.
**Lưu ý viết lại (2026-10-15):** bản spec/plan đầu tiên của nhóm module này bị viết trước khi `DEC-LRN-010` (Course/CourseEnrollment) được chốt ở Module 2, nên thiếu đường giao Assessment qua Course; bản spec/plan đó đã bị revert cùng lúc đồng bộ lại docs từ máy khác. Bản này viết lại đầy đủ, có tính Course ngay từ đầu.

## 1. Mục tiêu

Định nghĩa REST API cho: quản lý Question Bank dùng chung (Question/QuestionVersion/QuestionOption, Topic/Tag), và vòng đời Assessment (draft → publish → version mới, giao audience qua Class/Student/Course, StudentAssessment, close/reopen/cancel). Phục vụ Admin/Teacher soạn và vận hành bài; Student/Parent xem metadata bài được giao (không gồm nội dung câu hỏi/đáp án — việc đó thuộc Module 4 khi Attempt bắt đầu).

## 2. Quy ước chung

Kế thừa quy ước đã chốt ở spec Identity/Auth + User Management (không lặp lại ở các spec sau, chỉ ghi khác biệt nếu có):
- Base path `/api/v1/...`, field `snake_case`, pagination `?page=&per_page=` → `{ "data": [...], "meta": {...} }`.
- Error format `{ "error": { "code", "message" } }`, HTTP status theo chuẩn (`401/403/404/409/422`).
- Permission gate 4 bước ở `docs/access-control/access-rules.md` §1 áp dụng cho mọi endpoint dưới đây.

## 3. Question Bank

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/topics?search=`, `GET /api/v1/tags?search=` | Gợi ý Topic/Tag đã có (autocomplete, không bắt buộc) | Admin, Teacher |
| `GET /api/v1/questions` | List — lọc `subject_id`, `question_type`, `topic_id`, `tag_id`, `status`, `mine=true` | Admin: toàn kho. Teacher: toàn kho dùng chung (view/use) + lọc riêng câu mình tạo |
| `GET /api/v1/questions/{id}` | Chi tiết câu (current version + options + topics/tags) | Admin, Teacher |
| `POST /api/v1/questions` | Tạo Question + QuestionVersion đầu tiên (gộp 1 lần): `subject_id`, `question_type`, `prompt`, `instructions`, `suggested_points`, `difficulty`, `grading_mode`, `type_payload`, `grading_config`, `options[]` (nếu type cần), `topic_names[]`, `tag_names[]` (upsert theo tên — tạo mới nếu chưa có, không bắt buộc chọn id có sẵn) | Admin, Teacher (ghi `created_by_user_id` = actor) |
| `PATCH /api/v1/questions/{id}` | Sửa nội dung — luôn tạo `QuestionVersion` mới, cập nhật `current_version_id`; version cũ giữ nguyên cho Assessment đã snapshot (`BR-ASSESS-010`). Nhận cùng shape với POST. | Admin: mọi câu. Teacher: chỉ câu mình tạo (`created_by_user_id` = actor) → `403` nếu không phải chủ |
| `PATCH /api/v1/questions/{id}/topics`, `/tags` | Thay toàn bộ tập Topic/Tag gắn cho version hiện hành — không tạo version mới (topic/tag không phải nội dung chấm điểm, sửa tự do, không cần lịch sử) | Admin: mọi câu. Teacher: câu mình tạo |
| `DELETE /api/v1/questions/{id}` | Archive (`status=archived`, `archived_at`) — cho phép ngay cả khi đang được `AssessmentQuestion` tham chiếu (không xóa vật lý trong V1, `BR-ASSESS-010`) | Admin: mọi câu. Teacher: câu mình tạo |

**`type_payload`/`grading_config` theo 9 `question_type`** (JSON Schema chi tiết từng field để ở implementation plan, không phình spec):
- `single_choice`/`multiple_choice`/`true_false`: đáp án qua `QuestionOption.is_correct`; `grading_config` rỗng (tự chấm từ `is_correct`).
- `fill_in_the_blank`/`short_answer`: `grading_config.accepted_answers[]` (+ `case_sensitive`, `normalize` nếu được cấu hình rõ theo `BR-ASSESS-011`).
- `matching`/`ordering`: `type_payload` chứa cặp/thứ tự đúng.
- `essay`/`file_upload`: `grading_mode=manual`, không có đáp án tự chấm.

## 4. Assessment — draft, publish, versioning

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/assessments` | Tạo Assessment + `AssessmentVersion` đầu tiên ở `status=draft`: `type` (`assignment/quiz/exam`), `title`, `instructions`, policy (`opens_at`, `closes_at`, `deadline_at`, `duration_seconds`, `max_attempts`, `attempt_score_policy`, `shuffle_questions`, `shuffle_options`, `passing_score`, `max_score`, `show_answers`/`show_explanations`/`show_question_scores`), `lesson_session_id` (nullable, chỉ hợp lệ cho `assignment`, tối đa 1 — `BR-ASSESS-004`) | Admin, Teacher |
| `GET /api/v1/assessments` | List — Admin: tất cả. **Teacher: assessment do mình tạo, HOẶC có audience thuộc Class được phân công (`TeacherClassAssignment` hiện hành), HOẶC có audience thuộc Course mình sở hữu (`Course.owner_teacher_id`, không qua `TeacherClassAssignment` — theo `permission-matrix.md` dòng Course/CourseEnrollment).** Lọc `status`, `type`, `class_id`, `course_id` | Admin, Teacher (scope như trên) |
| `GET /api/v1/assessments/{id}` | Chi tiết + version hiện hành + `AssessmentQuestion` list (draft: tham chiếu sống tới Question hiện hành; published: snapshot đã đóng băng) | Admin, Teacher (scope như trên) |
| `PATCH /api/v1/assessments/{id}` | Sửa policy/title khi version hiện hành còn `draft`. `409` nếu version hiện hành đã `published` | Admin, Teacher (assessment mình tạo hoặc Class/Course trong scope) |
| `POST /api/v1/assessments/{id}/questions` | Thêm 1 câu vào draft hiện hành: `question_version_id`, `points`, `position` (optional, auto-append) | Admin, Teacher — chỉ khi version đang `draft` |
| `PATCH /api/v1/assessments/{id}/questions/{assessment_question_id}` | Sửa `points`/`position` trong draft | như trên |
| `DELETE /api/v1/assessments/{id}/questions/{assessment_question_id}` | Bỏ câu khỏi draft | như trên |
| `POST /api/v1/assessments/{id}/publish` | Chốt version hiện hành: validate tối thiểu 1 câu; `status=published`, `published_at`; snapshot từng `AssessmentQuestion.snapshot` (đóng băng prompt/options/grading_config tại thời điểm publish, `BR-ASSESS-005`) | Admin, Teacher |
| `POST /api/v1/assessments/{id}/versions` | Tạo version mới ở `draft` sau khi đã publish — copy toàn bộ câu/policy từ version hiện hành làm điểm bắt đầu, `version_number + 1` | Admin, Teacher — chỉ khi chưa có version `draft` nào đang mở |

**Lưu ý khớp rule:** `DEC-AS-004`/`BR-ASSESS-005` — version đã publish bất biến tuyệt đối; mọi sửa đổi sau publish đi qua version mới, không PATCH trực tiếp.

## 5. Audience và StudentAssessment (Class / Student / Course)

Theo `database/schema.md` (đã cập nhật khi chốt `DEC-LRN-010`): `AssessmentAudience` có 3 cột loại trừ nhau `class_id`/`student_id`/`course_id` — đúng một có giá trị. `StudentAssessment` có thêm `source_course_id` bên cạnh `source_class_id`/`source_audience_id` đã có.

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/assessments/{id}/audience` | Giao assessment: **đúng một trong `class_id` / `student_id` / `course_id`** (`422` nếu 0 hoặc ≥2 giá trị). Tạo `AssessmentAudience` + đồng bộ `StudentAssessment`: <br>— `student_id`: tạo/khôi phục `StudentAssessment` cho đúng Student đó.<br>— `class_id`: tạo cho mọi Student có `ClassMember.status=active` hiện hành.<br>— `course_id`: tạo cho mọi Student có `CourseEnrollment.status=active` hiện hành.<br>Dedupe theo `assessment_id`/`student_id` (`DEC-AS-002`, không cần logic riêng cho Course theo `access-control/open-decisions.md` — unique constraint hiện có đủ xử lý): nếu Student đã có `StudentAssessment` hiệu lực qua đường khác, chỉ gắn thêm `source_audience_id` phụ, không tạo bản ghi trùng. Cho phép gọi incremental bất cứ lúc nào, kể cả sau publish | Admin, Teacher (Class phải thuộc `TeacherClassAssignment` hiện hành nếu là Teacher; Course phải có `owner_teacher_id` = actor hoặc `owner_teacher_id=null` dùng chung) |
| `DELETE /api/v1/assessments/{id}/audience/{audience_id}` | Kết thúc 1 audience (`valid_to = now`, không xóa hàng). Nếu Student không còn audience hiệu lực nào cho assessment đó qua **bất kỳ đường nào** (direct, Class, Course) → `StudentAssessment.status = withdrawn` | Admin, Teacher (scope như trên) |
| `GET /api/v1/assessments/{id}/audience` | List audience hiện hành + lịch sử (cả 3 loại) | Admin, Teacher |
| `GET /api/v1/assessments/{id}/students` | List `StudentAssessment` của assessment (status, `individual_deadline_at`/`attempt_limit_override` nếu có, và nguồn gán — `source_class_id`/`source_course_id`/trực tiếp) | Admin, Teacher |
| `PATCH /api/v1/assessments/{id}/students/{student_id}` | Override cá nhân: `individual_deadline_at`, `attempt_limit_override` | Admin, Teacher |

**Integration point với Module Academic (Course):** khi có `CourseEnrollment` mới được tạo (endpoint thuộc Module 2, đã code xong) cho một Course đang có audience hiệu lực ở Assessment nào, Student đó phải tự nhận `StudentAssessment` — giống cách hoạt động với `ClassMember` mới trong Class đã có audience. Vì `CourseEnrollment` được tạo ở module khác (Academic, đã merge `main`), implementation plan của Module 3 cần thêm một bước đồng bộ (event/hook sau khi `CourseEnrollment` được tạo, hoặc tính lại tại thời điểm đọc nếu đơn giản hơn — quyết định cụ thể để ở plan, không chặn spec này). Cùng nguyên tắc áp dụng ngược lại cho `ClassMember` mới (đã có từ spec trước, giữ nguyên).

## 6. Close / Reopen / Cancel

Dùng trực tiếp `Assessment.status` (`draft/published/closed/cancelled/archived`) đã có trong schema — không thêm cột mới.

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/assessments/{id}/close` | `Assessment.status = closed`. Chặn Attempt mới ở Module 4; Attempt đang `in_progress` không bị ảnh hưởng (`BR-ASSESS-006`) | Admin, Teacher |
| `POST /api/v1/assessments/{id}/reopen` | Nhận `student_ids[]`, `class_id`, hoặc `course_id` (bắt buộc chọn đúng một trong ba, không reopen toàn bộ ngầm định — `BR-ASSESS-006`). `class_id`/`course_id` mở rộng ra toàn bộ Student thuộc membership/enrollment hiện hành của Class/Course đó. `Assessment.status` quay lại `published` nếu đang `closed`; các `StudentAssessment` liên quan quay lại `assigned`. `409` nếu `status` là `cancelled`/`archived` | Admin, Teacher |
| `POST /api/v1/assessments/{id}/cancel` | `reason` bắt buộc. `Assessment.status = cancelled`. Giữ nguyên Submission/lịch sử đã có (`BR-ASSESS-006`) | Admin, Teacher |

## 7. Student/Parent — xem metadata bài được giao

Chỉ trả metadata, không trả `AssessmentQuestion`/snapshot nội dung — việc lấy đề để làm bài thuộc Module 4 (Attempt).

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/me/assessments` | List `StudentAssessment` của mình — `title`, `type`, `opens_at/closes_at/deadline_at`, `max_score`, `status` | Student |
| `GET /api/v1/me/assessments/{id}` | Chi tiết metadata 1 assessment đã giao | Student |
| `GET /api/v1/me/children/{student_id}/assessments`, `/{assessment_id}` | Tương tự, theo `ParentStudentRelationship` hiệu lực | Parent |

## 8. Quyết định phát sinh trong lúc thiết kế

- Topic/Tag dùng cơ chế upsert-theo-tên khi tạo/sửa Question — không có CRUD riêng bắt buộc đi trước. Không phải quyết định business rule mới, chỉ là lựa chọn API; không cần sửa `requirements/*`.
- Close dùng `Assessment.status` có sẵn (không thêm cột `closes_at`/cờ thủ công).
- Đã thêm `lesson_session_id` vào `AssessmentVersion` trong `database/schema.md` (gap phát hiện khi viết spec này, không phải business rule mới — xem `database/open-decisions.md`).
- Audience/StudentAssessment qua Course dùng lại đúng cơ chế dedupe hiện có của Class (unique `assessment_id`+`student_id`), theo quyết định đã ghi ở `access-control/open-decisions.md` mục "Course/CourseEnrollment — chốt khi brainstorm spec Academic". Scope Teacher cho Course KHÔNG dùng `TeacherClassAssignment` — dùng trực tiếp `Course.owner_teacher_id` (hoặc Course dùng chung `owner_teacher_id=null`), khác cơ chế Class.

## 9. Ngoài phạm vi spec này

- Toàn bộ `Attempt`/`Answer`/`Submission`/`Grade`/`Feedback`/`Result`/`ResultPublication` — thuộc Module 4 (Submission/Grading + Result/Progress), spec riêng ở lượt sau.
- `Notification`, `Attachment`/`File`, `AuditLog` cho các event của module này (ví dụ ghi AuditLog khi publish/cancel) — thuộc Module 5, implementation plan của module đó sẽ nối vào khi tới lượt.
- Plan FE (Question Bank/Assessment) — theo quy trình chuẩn, làm sau khi BE xong, trừ khi có quyết định ngoại lệ khác giống lần này.
- Chi tiết OpenAPI/JSON Schema validation từng field theo `question_type`, rate-limit, cấu hình CORS — thuộc `writing-plans`/implementation.
- Cơ chế đồng bộ chính xác (hook/event vs tính lại khi đọc) cho integration point CourseEnrollment↔StudentAssessment nêu ở §5 — quyết định kỹ thuật cụ thể để ở implementation plan.

## 10. Nguồn tham khảo

`docs/requirements/assessment-rules.md`, `docs/requirements/learning-rules.md` (Course, `BR-COURSE-001..004`), `docs/requirements/decision-log.md` (nhóm `DEC-AS-*`, `DEC-Q-*`, `DEC-LRN-010`), `docs/access-control/roles.md`, `permission-matrix.md`, `data-scope.md`, `open-decisions.md`, `docs/database/schema.md`, `relationships.md`, `open-decisions.md`, `docs/architecture/module-boundaries.md`, `docs/progress.md` (checklist mục 3).
