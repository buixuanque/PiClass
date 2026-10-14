# Schema logic

Thiết kế mục tiêu dùng PostgreSQL và UUID làm PK cho entity nghiệp vụ. Tên bảng/cột và kiểu dưới đây vẫn mô tả schema logic; chưa phải migration. Bảng có `created_at`, `updated_at` (instant, NOT NULL) theo quy ước trừ khi ghi khác. Không dùng soft delete để thay cho trạng thái nghiệp vụ; entity cần lịch sử có `archived_at` hoặc trạng thái inactive.

## Identity & people

| Table | Mục đích và columns chính |
|---|---|
| `PersonProfile` | Hồ sơ cá nhân dùng chung và có thể tồn tại trước tài khoản. `id` PK; `full_name` NOT NULL; `email` nullable; `phone` nullable; timestamps. Không thêm ngày sinh/địa chỉ/định danh khi chưa có requirement. Contact được yêu cầu theo nhu cầu liên hệ/đăng nhập. |
| `User` | Danh tính đăng nhập. `id` PK; `person_profile_id` FK PersonProfile nullable unique; `login_identifier` (email hoặc identifier được chọn) nullable unique; `status` enum `active/inactive`; `last_login_at` nullable; timestamps. Secret xác thực không thuộc profile và cách lưu thuộc thiết kế bảo mật. Liên kết account đổi theo thời gian phải được lưu history. |
| `Role` | Danh mục role `admin/teacher/student/parent`. `id` PK; `code` unique NOT NULL; `name` NOT NULL; timestamps. |
| `UserRole` | Lịch sử gán role. `id` PK; `user_id` FK User; `role_id` FK Role; `valid_from` NOT NULL; `valid_to` nullable; `assigned_by_user_id` FK User nullable; `ended_by_user_id` FK User nullable; `end_reason` nullable; timestamps. |
| `UserPersonProfileHistory` | Lịch sử liên kết account: `id`, `user_id` FK User, `person_profile_id` FK PersonProfile, `valid_from` instant NOT NULL, `valid_to` instant nullable, `changed_by_user_id` FK User nullable, `reason` nullable, `created_at`. |
| `Student` | Hồ sơ vai trò học viên: `id`; `person_profile_id` FK PersonProfile NOT NULL; `status` enum `active/inactive`; `notes` nullable; `archived_at` nullable; timestamps. Một PersonProfile có thể đồng thời có nhiều role profile khác loại. |
| `Parent` | Hồ sơ vai trò phụ huynh: `id`; `person_profile_id` FK PersonProfile NOT NULL; `status`; `archived_at`; timestamps. |
| `Teacher` | Hồ sơ vai trò giáo viên: `id`; `person_profile_id` FK PersonProfile NOT NULL; `specialty` nullable; `status`; `archived_at`; timestamps. |
| `ParentStudentRelationship` | Quan hệ có hiệu lực. `id` PK; `parent_id` FK Parent; `student_id` FK Student; `relationship_type` nullable; `status` enum `active/ended`; `valid_from` NOT NULL; `valid_to` nullable; `confirmed_by_user_id` FK User NOT NULL; `confirmed_at` NOT NULL; `ended_by_user_id` nullable FK User; `end_reason` nullable; timestamps. |

## Academic

| Table | Mục đích và columns chính |
|---|---|
| `Subject` | Danh mục môn: `id`, `name` NOT NULL, `description` nullable, `status` `active/inactive`, `archived_at` nullable, timestamps. |
| `Class` | Nhóm học: `id`, `subject_id` FK Subject NOT NULL, `name` NOT NULL, `label`/`description` nullable để phân biệt tên trùng, `status` `preparing/active/archived`, `archived_at` nullable, timestamps. |
| `ClassMember` | Membership lịch sử: `id`, `class_id` FK Class, `student_id` FK Student, `status` `active/ended`, `valid_from` NOT NULL, `valid_to` nullable, `ended_by_user_id` nullable FK User, `end_reason` nullable, timestamps. |
| `TeacherClassAssignment` | Phân công giáo viên: `id`, `class_id` FK Class, `teacher_id` FK Teacher, `valid_from` NOT NULL, `valid_to` nullable, `status` `active/ended`, actor kết thúc/lý do nullable, timestamps. |
| `Schedule` | Mẫu kế hoạch: `id`, `class_id` FK Class, `status` `active/cancelled/ended`, `timezone` IANA mặc định `Asia/Ho_Chi_Minh`, `effective_from` date NOT NULL, `effective_until` date nullable, `local_start_time` time NOT NULL, `duration_minutes` integer NOT NULL, `repeat_weekly` boolean NOT NULL, `weekdays` tập ngày trong tuần NOT NULL khi lặp; với lịch một lần dùng đúng một ngày hiệu lực và weekday rỗng/không lặp; `cancel_reason` nullable; timestamps. Lịch sử đổi mẫu nằm ở ScheduleHistory. |
| `ScheduleHistory` | Lịch sử version mẫu: `id`, `schedule_id` FK, `version_number` integer, `changed_by_user_id` FK User, `changed_at` instant, `reason` nullable, `before_data`/`after_data` JSON NOT NULL. |
| `LessonSession` | Buổi cụ thể: `id`, `class_id` FK Class, `schedule_id` FK Schedule nullable, `starts_at`/`ends_at` instant NOT NULL, `status` `scheduled/completed/cancelled`, `lesson_content` nullable text, `notes` nullable text, `cancellation_reason` nullable, `review_required` boolean default false, `completed_at` nullable, `created_by_user_id` FK User, timestamps. |
| `LessonSessionHistory` | Lịch sử chỉnh nội dung/lịch: `id`, `lesson_session_id` FK, `changed_by_user_id` FK User, `changed_at` instant, `reason` nullable, `before_data`/`after_data` JSON NOT NULL. |
| `AttendanceRecord` | Trạng thái hiện hành: `id`, `lesson_session_id` FK, `student_id` FK, `status` enum `present/absent/late/excused`, `note` nullable, `recorded_by_user_id` FK User, timestamps. Một hàng hiện hành mỗi session/student. |
| `AttendanceHistory` | Mỗi sửa đổi: `id`, `attendance_record_id` FK, `changed_by_user_id` FK User, `changed_at` instant, `reason` NOT NULL, `before_data`/`after_data` JSON NOT NULL. |
| `AbsenceExplanation` | Parent gửi giải trình: `id`, `parent_id` FK Parent, `student_id` FK Student, `lesson_session_id` FK LessonSession, `attendance_record_id` FK AttendanceRecord nullable, `message` NOT NULL, `status` `submitted/under_review/accepted/rejected`, `reviewed_by_user_id` FK User nullable, `reviewed_at` nullable, `review_note` nullable, `created_at`, `updated_at`. Không tự đổi AttendanceRecord; mọi thay đổi Attendance vẫn theo quyền và audit riêng. |
| `Course` | Nội dung tự học thêm, tách biệt Class: `id`, `name` NOT NULL, `description` nullable, `subject_id` FK Subject nullable (phân loại tùy chọn), `owner_teacher_id` FK Teacher nullable (null = dùng chung toàn hệ thống, do Admin tạo), `status` `active/archived`, `archived_at` nullable, timestamps. Không có Schedule/LessonSession/AttendanceRecord. |
| `CourseEnrollment` | Membership lịch sử: `id`, `course_id` FK Course, `student_id` FK Student, `status` `active/ended`, `valid_from` NOT NULL, `valid_to` nullable, `enrolled_by_user_id` FK User, `ended_by_user_id` nullable FK User, `end_reason` nullable, timestamps. |

## Question Bank

| Table | Mục đích và columns chính |
|---|---|
| `QuestionBank` | Kho dùng chung hệ thống: `id`, `name` NOT NULL, `scope` mặc định `system`, `status`, timestamps. Ownership thực tế của câu thể hiện ở `Question.created_by_user_id`; không tạo kho cá nhân ở V1. |
| `Question` | Định danh câu tái sử dụng: `id`, `question_bank_id` FK, `subject_id` FK nullable, `created_by_user_id` FK User, `current_version_id` FK QuestionVersion nullable, `status` `active/archived`, `archived_at` nullable, timestamps. |
| `QuestionVersion` | Nội dung bất biến theo version: `id`, `question_id` FK, `version_number` integer NOT NULL, `question_type` enum gồm 9 loại đã chốt, `prompt` text NOT NULL, `instructions` nullable, `suggested_points` decimal nullable, `difficulty` nullable enum/label, `grading_mode` `automatic/manual/mixed`, `type_payload` JSON nullable (schema validate theo type), `grading_config` JSON nullable (schema validate theo type), `created_by_user_id` FK User, `created_at`. |
| `QuestionOption` | Option theo version: `id`, `question_version_id` FK, `position` integer, `content` JSON/text, `is_correct` nullable boolean, `created_at`. Không áp dụng cho mọi loại câu. |
| `Topic` / `QuestionTopic` | Topic thuộc Subject (`Topic.id`, `subject_id` FK, `name`); nối `QuestionTopic(question_version_id FK, topic_id FK)` unique cặp. Không phân cấp. |
| `Tag` / `QuestionTag` | Tag có `id`, `name`, timestamps; nối `QuestionTag(question_version_id FK, tag_id FK)` unique cặp. |

## Assessment engine

| Table | Mục đích và columns chính |
|---|---|
| `Assessment` | Aggregate identity: `id`, `type` enum `assignment/quiz/exam`, `created_by_user_id` FK User, `status` `draft/published/closed/cancelled/archived`, `current_version_id` FK nullable, `archived_at` nullable, timestamps. |
| `AssessmentVersion` | Nội dung/policy của lần phát hành: `id`, `assessment_id` FK, `version_number` integer, `title` NOT NULL, `instructions` nullable, `status` `draft/published/withdrawn`, `opens_at` nullable, `closes_at` nullable, `deadline_at` nullable, `duration_seconds` nullable, `max_attempts` integer default 1, `attempt_score_policy` `highest/latest` default latest, `late_policy` JSON nullable, `per_student_extensions` boolean default false, `shuffle_questions` boolean default false, `shuffle_options` boolean default false, `passing_score` decimal nullable, `max_score` decimal nullable, `result_release_at` nullable, `show_answers`/`show_explanations`/`show_question_scores` boolean default false, `cancellation_reason` nullable, `published_at` nullable, `created_by_user_id` FK User, `created_at`. JSON chỉ dành cho policy cấu hình chi tiết, không thay cột truy vấn chính. Version đã được cấp cho StudentAssessment không bị sửa nội dung/policy; thay đổi tạo version mới. |
| `AssessmentQuestion` | Nội dung được đóng băng trong version: `id`, `assessment_version_id` FK, `source_question_version_id` FK nullable, `position` integer, `points` decimal NOT NULL, `question_type` enum, `snapshot` JSON NOT NULL (prompt, options, answer/grading config), `created_at`. |
| `AssessmentAudience` | Phạm vi giao: `id`, `assessment_version_id` FK, `class_id` FK nullable, `student_id` FK nullable, `course_id` FK Course nullable, `valid_from` NOT NULL, `valid_to` nullable, `created_at`. Chính xác một trong class/student/course phải có giá trị; giao qua course áp dụng cho CourseEnrollment hiện hành tại thời điểm giao. |
| `StudentAssessment` | Đối tượng nhận hiệu lực, khử trùng đường giao: `id`, `assessment_id` FK, `student_id` FK, `assigned_version_id` FK AssessmentVersion, `source_class_id` FK Class nullable, `source_course_id` FK Course nullable, `source_audience_id` FK AssessmentAudience nullable, `status` `assigned/withdrawn/completed`, `individual_deadline_at` nullable, `attempt_limit_override` nullable, `created_at`, `updated_at`. Unique assessment/student cho assignment hiện hành; đổi release chỉ áp dụng theo policy được ghi nhận. |

## Attempt, answer, grading, result

| Table | Mục đích và columns chính |
|---|---|
| `Attempt` | Một lượt làm: `id`, `student_assessment_id` FK, `assessment_version_id` FK, `attempt_number` integer, `status` `in_progress/submitted/timed_out/cancelled/awaiting_grading/graded`, `started_at` NOT NULL, `ends_at` nullable, `cancel_reason` nullable, `replacement_attempt_id` FK Attempt nullable, `created_at`, `updated_at`. |
| `AttemptQuestion` | Câu thực tế/thứ tự ổn định: `attempt_id` FK, `assessment_question_id` FK, `position` integer; PK hoặc unique cặp. |
| `Answer` | Autosave theo câu: `id`, `attempt_id` FK, `assessment_question_id` FK, `answer_payload` JSON nullable (validate theo QuestionType), `saved_at` NOT NULL, `is_submitted` boolean default false, timestamps. Unique attempt/question. |
| `Submission` | Snapshot nộp cuối: `id`, `attempt_id` FK unique, `received_at` NOT NULL, `status` `received/awaiting_grading/graded/invalidated`, `is_late` boolean, `submitted_by_user_id` FK User, `created_at`. |
| `Grade` | Điểm hiện hành theo câu hoặc tổng: `id`, `submission_id` FK, `assessment_question_id` FK nullable (null=tổng), `score` decimal NOT NULL, `max_score` decimal NOT NULL, `grading_method` `automatic/manual`, `graded_by_user_id` FK User nullable (null khi auto), `updated_at`. Unique submission/question theo quy tắc câu/tổng. Feedback nằm ở entity riêng. |
| `Feedback` | Nhận xét chung/theo câu: `id`, `submission_id` FK, `grade_id` FK nullable, `assessment_question_id` FK nullable, `author_user_id` FK User nullable (null khi hệ thống), `body` text NOT NULL, `visibility` `private/learner/parent_if_published`, `status` `draft/published/withdrawn`, `published_at` nullable, timestamps. Chọn tối đa một target cụ thể trong Grade/AssessmentQuestion hoặc để trống cho feedback chung. |
| `FeedbackHistory` | Lịch sử sửa: `id`, `feedback_id` FK, `before_data`/`after_data` JSON NOT NULL, `changed_by_user_id` FK User, `changed_at` instant, `reason` nullable. |
| `GradeHistory` | Lịch sử sửa: `id`, `grade_id` FK, `before_data`/`after_data` JSON NOT NULL, `changed_by_user_id` FK User, `changed_at` instant, `reason` NOT NULL. |
| `Result` | Tổng hợp hiện hành: `id`, `submission_id` FK unique, `score` nullable, `max_score` nullable, `percentage` nullable, `passed` nullable boolean, `grading_status` `pending/partial/complete`, `publication_status` `unpublished/published`, `updated_at`. |
| `ResultHistory` | Mỗi lần đổi kết quả: `id`, `result_id` FK, `before_data`/`after_data` JSON, `changed_by_user_id` FK User, `changed_at`, `reason` nullable. |
| `ResultPublication` | Lịch sử công bố/thu hồi: `id`, `result_id` FK, `action` `published/withdrawn`, `published_at`, `actor_user_id` FK User, `created_at`. |

## Support entities

| Table | Mục đích và columns chính |
|---|---|
| `Attachment` | Metadata tệp: `id`, `uploaded_by_user_id` FK User nullable, `storage_key` NOT NULL unique, `file_name`, `media_type`, `size_bytes`, `checksum` nullable, `status` `uploading/ready/failed/archived`, `created_at`. Không lưu nội dung binary; cleanup không xóa tệp còn reference và theo policy vận hành/pháp lý. |
| `AttachmentLink` | Quan hệ FK cụ thể: `id`, `attachment_id` FK, một FK nullable trong các cột `question_version_id`, `assessment_version_id`, `answer_id`, `feedback_id`; đúng một target; `visibility` `private/learner/parent_if_published`; `created_at`. |
| `Notification` | Một thông báo cho một người nhận: `id`, `recipient_user_id` FK User, `event_key` NOT NULL, `event_type`, `title`, `body`, `created_at`, `read_at` nullable. Unique recipient/event_key; không lưu context polymorphic ở đây. |
| `NotificationContext` | Liên kết notification tới đúng một đối tượng có FK: `id`, `notification_id` FK, một FK nullable trong `assessment_id`, `lesson_session_id`, `result_id`, `schedule_id`; đúng một target. Context chỉ điều hướng; luôn kiểm tra quyền theo đối tượng/ngữ cảnh hiện hành. |
| `AuditLog` | Dấu vết nhạy cảm: `id`, `actor_user_id` FK User nullable, `action`, `entity_type`, `entity_id`, `occurred_at`, `before_data`/`after_data` JSON nullable, `reason` nullable, `correlation_key` nullable. Audit append-only theo nghiệp vụ. Nullable cho phép event không cần dữ liệu đó; domain phải bắt buộc các trường tương ứng theo action (ví dụ sửa Attendance/Grade/membership/lịch cần reason và before/after) trước khi ghi log. |

### Enum và thời gian

Các enum ở trên là tập logical theo Step 5. `instant` lưu mốc tuyệt đối; hiển thị lịch theo `Asia/Ho_Chi_Minh`. Quan hệ có hiệu lực dùng khoảng nửa mở `[valid_from, valid_to)` theo instant; Schedule giữ ngày/giờ địa phương và timezone IANA để sinh session đúng lịch. Tất cả FK có index phù hợp; chi tiết ở [indexes-and-constraints.md](indexes-and-constraints.md).
