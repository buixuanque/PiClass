# Step 9 — API Specification: Submission/Grading + Result/Progress

**Trạng thái:** Spec đã được duyệt theo từng section qua brainstorming (`superpowers:brainstorming`), chờ user review file này trước khi sang `writing-plans`.
**Phạm vi:** Nhóm module thứ tư của Step 9 — `Submission/Grading` + `Result/Progress` theo ranh giới tại `docs/architecture/module-boundaries.md`. Tiếp nối trực tiếp Module 3 (Question Bank + Assessment, đã merge `main` qua PR #4) — build trên `Assessment`/`AssessmentVersion`/`AssessmentQuestion`/`StudentAssessment` đã có, không sửa lại các bảng đó.
**Không tạo business rule mới.** Mọi hành vi dưới đây chỉ chuyển các rule đã chốt ở `docs/requirements/assessment-rules.md` (`BR-ASSESS-011..028`), `docs/requirements/decision-log.md` (`DEC-AS-*`, `DEC-GRD-*`), `docs/access-control/*`, `docs/database/schema.md` thành endpoint cụ thể.
**Lưu ý quan trọng:** schema cho `Attempt`/`AttemptQuestion`/`Answer`/`Submission`/`Grade`/`GradeHistory`/`Feedback`/`FeedbackHistory`/`Result`/`ResultHistory`/`ResultPublication` đã được chốt đầy đủ ở Step 6 (`database/schema.md`). Spec này KHÔNG thiết kế bảng mới — chỉ thiết kế endpoint + luồng nghiệp vụ dựa trên schema có sẵn.

## 1. Mục tiêu

Định nghĩa REST API cho: học sinh làm bài (bắt đầu lượt, lưu tạm, nộp), tự động chấm theo `question_type`, giáo viên chấm thủ công câu cần phán đoán + sửa điểm, feedback, công bố/thu hồi kết quả, và theo dõi tiến độ học tập (tính runtime, không có bảng tổng hợp riêng).

## 2. Quy ước chung

Kế thừa quy ước đã chốt ở các spec trước (không lặp lại, chỉ ghi khác biệt nếu có):
- Base path `/api/v1/...`, field `snake_case`, pagination `?page=&per_page=` → `{ "data": [...], "meta": {...} }`.
- Error format `{ "error": { "code", "message" } }`, HTTP status theo chuẩn (`401/403/404/409/422`).
- Permission gate 4 bước ở `docs/access-control/access-rules.md` §1 áp dụng cho mọi endpoint dưới đây.
- Grading đồng bộ (synchronous): mọi tính toán chấm điểm/cập nhật `Result` chạy ngay trong transaction của request gây ra nó (submit, chấm tay) — không dùng queue job riêng, khớp quy mô V1 (1 EC2) ở `architecture/technology-decisions.md`.
- Action tách lớp rõ ràng, không gộp (theo note "Những bảng/action không tương đương" ở `access-control/resource-permissions.md`): `submit` (Student) ≠ `grade` (Teacher/Admin) ≠ `publish result` (Teacher/Admin) ≠ `show_answers`/`show_explanations`/`show_question_scores` (cấu hình ở `AssessmentVersion`, thuộc Module 3, không phải action của module này).

## 3. Attempt lifecycle — bắt đầu, làm bài, nộp

Hành vi khác nhau theo `AssessmentVersion.type`:

| | `assignment` | `quiz` / `exam` |
|---|---|---|
| `duration_seconds` | `null` — không tính giờ làm bài | có giá trị — tính giờ từ lúc bắt đầu |
| `Attempt.ends_at` | không set (chỉ ràng buộc bởi `deadline_at` chung, không tự nộp theo giờ) | `started_at + duration_seconds`, không vượt giờ đóng chung (`closes_at`; với `exam`, vào muộn vẫn kết thúc đúng giờ đóng chung — `BR-ASSESS-019`) |
| Rời trang / mất mạng | không ảnh hưởng | đồng hồ vẫn chạy tiếp, không dừng (`BR-ASSESS-018`) |
| Hết giờ | không có khái niệm timeout — quá `deadline_at` thì áp `late_policy` (nộp trễ/không cho nộp) | tự nộp phần đã lưu khi `ends_at` đã qua |

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/me/assessments/{student_assessment_id}/attempts` | Bắt đầu Attempt. Validate: `StudentAssessment.status=assigned`, `Assessment.status=published`, trong khung `opens_at`/`closes_at`, chưa vượt `max_attempts` (áp `attempt_limit_override` nếu có, không tính các Attempt bị `cancelled` do sự cố — §8). Lấy `assigned_version_id`, snapshot câu hỏi từ `AssessmentQuestion.snapshot`, tạo `AttemptQuestion` theo thứ tự (áp `shuffle_questions`/`shuffle_options` nếu bật). Trả đề bài, KHÔNG kèm đáp án đúng | Student (owner) |
| `GET /api/v1/me/attempts/{id}` | Xem đề + đáp án đã lưu. Nếu đang `in_progress` với `type=quiz/exam` và `ends_at` đã qua → lazy-trigger tự nộp (chạy luồng `submit` §3, với dữ liệu Answer hiện có) trước khi trả response | Student (owner) |
| `PUT /api/v1/me/attempts/{id}/answers/{assessment_question_id}` | Autosave 1 câu trả lời: `answer_payload` (validate theo `question_type`). Chỉ khi Attempt còn `in_progress` (và chưa quá `ends_at` nếu có tính giờ). Set `Answer.is_submitted=false` | Student (owner) |
| `POST /api/v1/me/attempts/{id}/submit` | Nộp bài. Transaction: set `is_submitted=true` cho toàn bộ Answer, tạo `Submission` (`received_at`=now, `submitted_by_user_id`=actor, `is_late` theo so với `deadline_at`/`individual_deadline_at`), chạy auto-grading (§4), tạo/cập nhật `Grade` + `Result`, tính `grading_status`, nếu `complete` → kiểm tra auto-publish (§6). `Attempt.status` → `submitted` rồi `graded`/`awaiting_grading` theo `grading_status` | Student (owner) |

## 4. Auto-grading engine (theo 9 `question_type`)

Chạy trong transaction `submit` (§3), cho từng `Answer` của Attempt:

| Nhóm | `question_type` | Cách chấm |
|---|---|---|
| So khớp lựa chọn | `single_choice`, `multiple_choice`, `true_false` | So `answer_payload` với `QuestionOption.is_correct` trong snapshot. `single_choice`/`true_false`: đúng đáp án đúng duy nhất → full điểm, sai → 0. `multiple_choice`: mặc định phải chọn đúng và chỉ đúng toàn bộ `is_correct=true` mới full điểm; cho điểm từng phần nếu `grading_config` quy định rõ (`BR-ASSESS-012`) |
| So khớp văn bản | `fill_in_the_blank`, `short_answer` | So `answer_payload` với `grading_config.accepted_answers[]`, áp `case_sensitive`/`normalize` nếu cấu hình. Khớp 1 trong các answer → full điểm, không khớp → 0 |
| So khớp cấu trúc | `matching`, `ordering` | So `answer_payload` với đáp án đúng trong `type_payload`. Toàn-hoặc-không hoặc điểm từng phần theo số cặp/vị trí đúng, tùy `grading_config` |
| Cần giáo viên chấm | `essay`, `file_upload` | Không tự chấm. KHÔNG tạo `Grade` row cho câu này tại lúc submit (tránh `score=null` bị hiểu nhầm là 0) — `Grade` chỉ được tạo khi Teacher chấm thủ công (§5) |

**Quy tắc chung:**
- Câu bỏ trống (không có `Answer` hoặc `answer_payload=null`) → **0 điểm ngay**, áp cho mọi loại kể cả `essay`/`file_upload` (`BR-ASSESS-011`) — không tạo trạng thái "chờ chấm" cho câu bỏ trống.
- Điểm 1 câu không vượt `AssessmentQuestion.points`.
- `Result.grading_status` sau khi auto-grade:
  - Còn câu `essay`/`file_upload` **có bài làm** (không bỏ trống) chưa có `Grade` → `pending` (nếu toàn bộ câu còn chờ) hoặc `partial` (nếu đã có ít nhất 1 câu được chấm).
  - Mọi câu đã có điểm (tự động hoặc bỏ trống=0) → `complete`.
- `Result.score`/`percentage`/`passed` tính được đầy đủ khi `complete`; khi `pending`/`partial`, để `null` (không hiển thị điểm tổng cho tới khi chấm xong hết, tránh gây hiểu nhầm điểm tạm là điểm cuối).

## 5. Chấm thủ công (Teacher) + sửa điểm + Feedback

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/assessments/{id}/submissions` | List bài nộp của assessment, lọc `grading_status=pending/partial/complete` | Admin: toàn bộ. Teacher: chỉ Class (`TeacherClassAssignment` hiện hành) hoặc Course (`Course.owner_teacher_id`=actor) mình phụ trách |
| `GET /api/v1/submissions/{id}` | Chi tiết 1 bài nộp: Answer + Grade hiện có theo từng câu | Admin, Teacher (scope như trên) |
| `PUT /api/v1/submissions/{id}/questions/{assessment_question_id}/grade` | Chấm/sửa điểm 1 câu. Body: `score`, `reason` (**bắt buộc** nếu `Result.publication_status=published` — `GradeHistory.reason NOT NULL`). Chưa có `Grade` → tạo mới (`grading_method=manual`, `graded_by_user_id`=actor). Đã có (kể cả `automatic`) → update + ghi `GradeHistory` (before/after/actor/reason/time) | Admin, Teacher (scope như trên) |
| `PUT /api/v1/submissions/{id}/grade` | Chấm điểm tổng trực tiếp (trường hợp không chấm theo từng câu, `Grade.assessment_question_id=null`) | như trên |
| `POST /api/v1/submissions/{id}/feedback` | Tạo Feedback: `body`, `visibility` (`private/learner/parent_if_published`), target tùy chọn (`grade_id` hoặc `assessment_question_id`, để trống = feedback chung) | Admin, Teacher (scope như trên) |
| `PUT /api/v1/feedback/{id}` | Sửa nội dung Feedback. Nếu đang `published` → ghi `FeedbackHistory` (`reason` nullable) | như trên |
| `PATCH /api/v1/feedback/{id}` | Đổi `status`: `draft→published→withdrawn`. Thu hồi không xóa, chỉ chặn Student/Parent xem tiếp | như trên |

**Sau mỗi lần tạo/sửa `Grade`:** tính lại `Result.grading_status` (quy tắc §4) và `score`/`percentage`/`passed` nếu `complete`, đồng bộ ngay trong request đó. Nếu `grading_status` vừa chuyển sang `complete` → kiểm tra auto-publish (§6). Nếu `Result` đã từng `published` và giá trị thay đổi sau khi Teacher sửa điểm → ghi `ResultHistory` (before/after/actor/reason nếu có/time).

## 6. Result & công bố điểm/feedback

`publication_status` (công bố **điểm**, hành động theo từng `Result`) và `show_answers`/`show_explanations`/`show_question_scores` (công bố **đáp án/lời giải/điểm từng câu**, cấu hình ở `AssessmentVersion`, thuộc Module 3) là hai việc **tách biệt hoàn toàn**.

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/results/{id}/publish` | Set `publication_status=published`, tạo `ResultPublication(action=published, actor_user_id, published_at)`. Chỉ cho phép khi `grading_status=complete` | Admin, Teacher (scope như §5) |
| `POST /api/v1/assessments/{id}/results/publish` | Publish tất cả `Result` thuộc Assessment có `grading_status=complete` và `publication_status=unpublished` (trong scope Teacher). Bỏ qua (không lỗi) các Result chưa `complete`, trả danh sách đã publish + danh sách bị skip kèm lý do | Admin, Teacher (scope như §5) |
| `POST /api/v1/results/{id}/withdraw` | Set `publication_status=unpublished`, ghi `ResultPublication(action=withdrawn)`. Không xóa dữ liệu, chỉ chặn xem tiếp từ thời điểm đó | Admin, Teacher (scope như §5) |

**Auto-publish theo `AssessmentVersion.result_release_at`:** kiểm tra tại 2 thời điểm sự kiện (không dùng job nền định kỳ — quyết định rõ trong lúc brainstorm, giáo viên tự publish tay nếu cần, học sinh sẽ nhắc nếu quên):
1. Lúc `submit` (§3): nếu auto-grade cho `grading_status=complete` ngay và `result_release_at` là `null` hoặc đã qua thời điểm hiện tại → publish luôn trong transaction đó.
2. Lúc Teacher chấm xong câu cuối làm `grading_status` chuyển `partial`/`pending` → `complete` (§5): kiểm tra lại `result_release_at`, nếu đã qua → publish luôn trong transaction chấm đó.

Nếu `result_release_at` chưa tới ở cả 2 thời điểm trên, `Result` giữ `unpublished` chờ: tới giờ hẹn mà không có event nào kích hoạt lại check (không ai nộp/chấm gì sau đó) thì vẫn ở trạng thái chờ — giáo viên chủ động gọi API publish khi cần, đây là lựa chọn đã chốt (không cần lưới an toàn bằng Scheduler job).

**Nhiều lượt (`attempt_score_policy`):** mỗi Attempt có `Submission`/`Result` riêng, không gộp. Result "đại diện" cho Student/Parent xem (§7a) và cho Progress (§7b) được chọn **lúc đọc** (runtime, không lưu cờ): trong các `Result` đã `published` của `StudentAssessment` đó, chọn theo `attempt_score_policy` (`highest`: điểm cao nhất; `latest`: Result của Attempt mới nhất).

## 7. Student/Parent — xem Result + Progress

**7a. Xem Result của 1 Assessment:**

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/me/assessments/{student_assessment_id}/result` | Trả Result "đại diện" (§6) nếu có ≥1 Result `published`; kèm danh sách toàn bộ lượt khác (điểm, đã publish hay chưa — giữ lịch sử theo `BR-ASSESS-026`). Trả đáp án đúng/điểm từng câu/lời giải chỉ khi `show_answers`/`show_question_scores`/`show_explanations` (từ `AssessmentVersion`) cho phép | Student (owner) |
| `GET /api/v1/me/children/{student_id}/assessments/{student_assessment_id}/result` | Như trên, thêm check `ParentStudentRelationship` hiệu lực (unlink mất quyền ngay); Feedback chỉ trả nếu `visibility` cho phép Parent | Parent |

**7b. Progress (tính runtime, không có bảng riêng — `data-lifecycle.md`):**

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `GET /api/v1/me/progress` | Lọc `subject_id`, `class_id`/`course_id`, khoảng thời gian. Tính trên toàn bộ `Result` của Student có `publication_status=published` **và** `Assessment.status != cancelled` (`BR-ASSESS-026`): điểm trung bình tổng, trung bình theo từng Subject, số bài đã có kết quả vs số bài đang chờ chấm (chỉ đếm, không tính vào trung bình) | Student (owner) |
| `GET /api/v1/me/children/{student_id}/progress` | Như trên, check link hiệu lực | Parent |
| `GET /api/v1/students/{id}/progress` | Như trên, dùng cho giáo viên theo dõi (không qua `/me/`) | Admin: toàn bộ. Teacher: chỉ Student trong Class/Course mình phụ trách |

## 8. Admin cấp lại Attempt Exam do sự cố

Chỉ áp dụng Assessment `type=quiz/exam` (không áp dụng `assignment` — không có khái niệm "sự cố thi giờ", `assignment` dùng `late_policy` bình thường cho nộp trễ).

| Method & Path | Việc gì | Quyền |
|---|---|---|
| `POST /api/v1/attempts/{id}/reissue` | Body bắt buộc `reason`. Transaction: Attempt cũ → `status=cancelled`, `cancel_reason`=reason; nếu đã có `Submission`/`Grade`/`Result` → `Submission.status=invalidated` (không tính vào Progress). Tạo Attempt mới cùng `student_assessment_id`/`assessment_version_id`, `attempt_number` tăng tiếp nhưng **không tính vào `max_attempts`** (`BR-ASSESS-020`). Attempt cũ ghi `replacement_attempt_id` = id Attempt mới | Admin |

## 9. Quyết định phát sinh trong lúc thiết kế

- Grading chạy đồng bộ (synchronous) trong request, không dùng queue job — khớp quy mô V1 (1 EC2), không phải business rule mới, chỉ là lựa chọn kỹ thuật.
- Câu `essay`/`file_upload` chưa chấm KHÔNG tạo `Grade` row (thay vì tạo với `score=null`) — tránh nhầm "đã có bản ghi nhưng chưa có điểm" với "đã chấm = 0". Áp dụng ngược: câu bỏ trống luôn có `Grade.score=0` ngay, kể cả `essay`/`file_upload`.
- Auto-publish theo `result_release_at` chỉ kiểm tra tại 2 điểm sự kiện (submit, chấm xong câu cuối) — không dùng Scheduler job định kỳ quét `Result` quá hạn. Quyết định rõ trong lúc brainstorm: chấp nhận rủi ro "complete rồi im luôn không ai động tới sau giờ hẹn" vì thực tế học sinh sẽ nhắc giáo viên, giáo viên tự publish tay khi cần.
- `Result.score`/`percentage`/`passed` để `null` khi `grading_status` chưa `complete` (không hiển thị điểm tạm trên phần đã chấm) — tránh học sinh/giáo viên nhầm điểm tạm là điểm cuối khi còn câu tự luận chưa chấm.

## 10. Ngoài phạm vi spec này

- `Notification` khi công bố kết quả, `AuditLog` cho các action nhạy cảm của module này (chấm, sửa điểm, publish/withdraw) — thuộc Module 5, implementation plan của module đó sẽ nối vào khi tới lượt. Module 4 chỉ cần đảm bảo `GradeHistory`/`ResultHistory`/`FeedbackHistory` đã được ghi đầy đủ như schema định (business history riêng, không phải AuditLog) để Module 5 có dữ liệu bắn Notification/Audit dựa trên đó.
- Cơ chế upload file thực tế cho `Answer`/`Feedback` (chỉ dùng FK `AttachmentLink.answer_id`/`feedback_id` đã có sẵn trong schema) — cơ chế upload chung thuộc Module 5.
- Plan FE (Submission/Grading/Result/Progress) — theo quy trình chuẩn, làm sau khi BE xong.
- Chi tiết JSON Schema validation `answer_payload` theo từng `question_type`, rate-limit, cấu hình CORS — thuộc `writing-plans`/implementation.
- Thuật toán chi tiết điểm-từng-phần cho `multiple_choice`/`matching`/`ordering` khi `grading_config` bật — nguyên tắc đã nêu ở §4, công thức cụ thể để ở implementation plan (task breakdown).

## 11. Nguồn tham khảo

`docs/requirements/assessment-rules.md` (`BR-ASSESS-011..028`), `docs/requirements/decision-log.md` (nhóm `DEC-AS-*`, `DEC-GRD-*`), `docs/access-control/permission-matrix.md`, `resource-permissions.md`, `data-scope.md`, `docs/database/schema.md` (section "Attempt, answer, grading, result"), `relationships.md`, `data-lifecycle.md`, `docs/architecture/module-boundaries.md`, `docs/architecture/technology-decisions.md`, `docs/superpowers/specs/2026-10-15-question-bank-assessment-api-design.md`, `docs/progress.md` (checklist mục 4).
