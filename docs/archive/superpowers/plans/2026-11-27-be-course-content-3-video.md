# Khóa học có nội dung học, lát 3: video và điểm dừng câu hỏi (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Giáo viên (và Admin) tạo nội dung `video` (mục của khóa hoặc độc lập), tải tệp MP4 lên đĩa riêng tư theo phần qua backend hoặc nhập link ngoài, đặt điểm dừng câu hỏi lấy từ Ngân hàng câu hỏi, và xem trước video bằng link có chữ ký hỗ trợ Range. Học sinh xem video ở lát 4.

**Architecture:** Lớp trừu tượng `VideoStorage` (driver `local` bản đầu, `s3` là `B-096`) trong module `CourseContent`. Ba bảng mới: `content_videos` (video đang dùng của một nội dung), `content_video_uploads` (lần tải đang dở hoặc đã đóng, giữ video cũ phát được trong lúc thay tệp), `video_checkpoints` (điểm dừng kèm bản chụp câu hỏi). Phát qua một chỗ tạo link duy nhất (`GET /contents/{content}/playback`), URL ký tạm thời trỏ vào route phục vụ tệp có Range.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit 12, PostgreSQL; chạy lệnh trong `piclass-infra/local` bằng `docker compose exec -T api ...`.

**Spec:** `docs/superpowers/specs/2026-11-25-course-content-design.md` (mục 3 `content_videos`, `video_checkpoints`; mục 4 điều 4, 6, 8; mục 5 Tệp và video; mục 7 API; mục 8; mục 11, 12). Quyết định: `DEC-LRN-052` mục 2, 3, 9, 10, 11; `DEC-LRN-053` mục 3; `ARCH-39`. Quy ước code: `piclass-api/CLAUDE.md`, `docs/architecture/backend-gotchas.md`.

## Global Constraints

- Nhánh `feat/course-content-video` của `piclass-api`, cắt từ `origin/main` mới nhất. Không commit thẳng `main`.
- Video chỉ MP4 (`video/mp4`, nhận diện bằng nội dung tệp: hộp `ftyp` ở byte 4..8, không tin `Content-Type`), tối đa **1 GB** (`1073741824` byte); phần tải **5 MB** (`5242880` byte); link phát hạn **15 phút**; điểm dừng cách nhau tối thiểu **3 giây**; tải dở quá **24 giờ** bị dọn. Mọi hằng số nằm trong `config/content.php`.
- Chỉ loại câu tự chấm được làm điểm dừng: `single_choice`, `multiple_choice`, `true_false`, `fill_in_the_blank`, `matching`, `ordering` (`DEC-LRN-053`); loại khác 422 `CHECKPOINT_QUESTION_UNSUPPORTED`. Câu nguồn phải dùng được (`QuestionPolicy::useInAssessment`).
- Link ngoài chỉ nhận `https` và tên miền trong `config('content.video.external_hosts')` (YouTube, Vimeo).
- Một nội dung có tối đa một video đang dùng (`content_videos`) và tối đa một lần tải đang `uploading`; bắt đầu lần tải mới thì lần cũ bị hủy.
- Thay video (tệp mới hoặc link ngoài khi đã có video): mọi điểm dừng đặt `needs_recheck=true`, tệp cũ chỉ bị xóa khi **không còn `content_videos` nào khác** dùng chung `storage_key` (bản sao khóa dùng chung tệp, spec mục 4 điều 8), audit `content_video.replace`. Xóa vị trí xem dở của học sinh làm ở lát 4 (chưa có bảng tiến độ).
- Quyền: quản lý nội dung (`LearningContentPolicy::manage`; nội dung của khóa theo quyền quản lý khóa, nội dung độc lập là chủ hoặc Admin, người khác 404). Học sinh và Phụ huynh bị chặn ở route (`role:admin,teacher`). Việc cho học sinh xem video (đối tượng, mở khóa tuần tự) là lát 4.
- Module `CourseContent` chỉ **đọc** QuestionBank (`QuestionVersion`, `QuestionOption`, `QuestionPolicy`); ghi vào module khác qua Action. `ArchitectureTest` (R1..R11) phải xanh: khai báo `ContentVideo`, `ContentVideoUpload`, `VideoCheckpoint` trong `MODEL_OWNERS` (`CourseContent`), enum dùng chung mới trong `SHARED_ENUMS` (`QuestionBank/QuestionType`), không để literal enum (R8).
- Mã lỗi mới ghi vào `docs/architecture/error-codes.md`; tên test method, mã lỗi, commit message bằng tiếng Anh; comment và mô tả test bằng tiếng Việt.
- TDD: mỗi thay đổi hành vi có test chạy đỏ trước rồi mới xanh; cuối plan chạy cả bộ test, Pint (chỉ file đã đổi), PHPStan (không thêm lỗi mới so với baseline 30), xuất OpenAPI.

## Quyết định chốt khi viết plan (2026-11-27)

1. **Bảng riêng cho lần tải** `content_video_uploads` (mang `upload_id`), thay vì `upload_id`, `status=uploading|failed` nằm trong `content_videos` như spec mục 3 bản đầu. Lý do: thay tệp video khi đang có video đang dùng không được làm video cũ ngừng phát hay mất khi lần tải mới lỗi. `content_videos` chỉ chứa video đang dùng (có dòng nghĩa là phát được); trạng thái trả ra API là suy ra: `ready` (có dòng `content_videos`), `uploading` (có lần tải đang dở), `failed` (không có video và lần tải gần nhất lỗi), `null` (chưa có gì).
2. **Tải theo phần qua backend** (`mode=chunked`), `PUT` thân thô (`application/octet-stream`), gửi lại một phần được; thời lượng do FE gửi lúc `complete` (`duration_seconds`).
3. **Phát cho người quản lý ở lát này**; route phục vụ tệp nhận chữ ký có chứa `user`, và kiểm lại quyền theo `user` đó (không cần cookie hay token vì thẻ `<video>` không gửi được `Authorization`). Lát 4 mở quyền cho học sinh qua cùng một chỗ kiểm (`ContentVideoAccess`).
4. **Điểm dừng sửa theo từng dòng**, không xóa thay cả tập: dòng có `id` được cập nhật, dòng không `id` là mới, dòng vắng mặt bị xóa. Lý do: lát 4 gắn câu trả lời của học sinh vào `checkpoint_id` (cascade), xóa và tạo lại sẽ mất lượt trả lời đầu.
5. **Sao chép video vào khóa** (`POST /contents/{content}/copy-to-course` của lát 2) chép cả `content_videos` (cùng `storage_key`) và điểm dừng; xóa nội dung hay thay tệp chỉ xóa tệp khi hết tham chiếu.
6. Lệnh dọn tải dở `php artisan content-videos:prune-uploads` chạy thủ công (scheduler chưa bật, ghi nợ kỹ thuật như spec mục 5).

## Review Focus

Các điểm kế hoạch ngầm hiểu mà test thường không chạm; người review cuối nhánh kiểm từng dòng (mỗi dòng đã có test ở task chủ):

1. Tải lên: `size_bytes` lớn hơn 1 GB, `mime_type` khác `video/mp4`, tệp ghép xong nhưng không phải MP4 (đổi đuôi `.mp4`), tổng kích thước các phần khác kích thước khai báo, thiếu phần, phần vượt 5 MB, số phần ngoài khoảng (Task 5).
2. Người khác (giáo viên khác, kể cả dạy cùng lớp; học sinh; phụ huynh) không bắt đầu, gửi phần, hoàn tất, hủy, đặt link ngoài, đặt điểm dừng hay xin link phát: 404/403 đúng chỗ, không rò id; đoán `upload_id` của nội dung khác bị 404 (Task 4–7).
3. Link phát: hết hạn thì 403; sửa `user`, `expires` hoặc chữ ký thì 403; người dùng bị khóa hoặc mất quyền sau khi xin link thì 403 khi mở; `Range` trả `206` đúng đoạn; không lộ đường dẫn đĩa (Task 7).
4. Thay tệp: video cũ vẫn phát cho tới lúc `complete` thành công; `complete` lỗi giữ nguyên video cũ; thay xong thì điểm dừng `needs_recheck`, tệp cũ bị xóa trừ khi bản sao khóa còn dùng chung (Task 5, 6, 8).
5. Điểm dừng: câu nguồn riêng tư của người khác không dùng được dù biết id; hai điểm cách dưới 3 giây (kể cả khi đổi vị trí một điểm sát điểm khác); vị trí vượt thời lượng; loại câu không tự chấm; bản chụp không đổi khi câu nguồn sau đó đổi (Task 4).
6. Xóa nội dung video (mục khóa hoặc độc lập): tệp bị xóa khi hết tham chiếu, không đụng bản sao khác; tải dở của nội dung bị xóa cũng được dọn (Task 8).

## File Structure

Tạo:
- `config/content.php`
- `database/migrations/2026_11_28_000001_create_content_videos.php`
- `app/Models/ContentVideo.php`, `ContentVideoUpload.php`, `VideoCheckpoint.php` và factory tương ứng
- `app/Modules/CourseContent/Enums/`: `VideoSource`, `VideoStorageDriver`, `VideoUploadStatus`
- `app/Modules/CourseContent/Storage/`: `VideoStorage` (interface), `LocalVideoStorage`
- `app/Modules/CourseContent/Domain/`: `Mp4Sniffer`, `ExternalVideoUrl`
- `app/Modules/CourseContent/Actions/`: `StartVideoUpload`, `StoreVideoPart`, `CompleteVideoUpload`, `CancelVideoUpload`, `SetExternalVideo`, `ReplaceContentVideo`, `SyncCheckpoints`, `ConfirmCheckpoint`, `CopyContentVideo`, `ReleaseVideoFiles`, `IssuePlaybackUrl`
- `app/Modules/CourseContent/Queries/`: `ContentVideoStateQuery`, `ListCheckpointsQuery`, `ContentVideoAccess`
- `app/Modules/CourseContent/Exceptions/`: `VideoNotReady`, `VideoUploadFailed`, `VideoUploadClosed`, `CheckpointQuestionUnsupported`, `CheckpointTooClose`
- `app/Modules/CourseContent/Console/PruneVideoUploads.php`
- Requests, Controllers, Resources tương ứng cho từng endpoint (đặt tên ở từng task)
- Tests: `tests/Feature/CourseContent/{ContentVideoSchemaTest, VideoContentTest, VideoCheckpointTest, VideoUploadTest, ExternalVideoTest, VideoPlaybackTest, VideoCopyAndReleaseTest}`, `tests/Unit/Modules/CourseContent/{LocalVideoStorageTest, Mp4SnifferTest, ExternalVideoUrlTest}`

Sửa: `CourseContentServiceProvider` (bind `VideoStorage`, đăng ký lệnh), `CreateCourseItemRequest`, `CreateCourseItem`, `CreateStandaloneContentRequest`, `CreateStandaloneContent`, `LearningContentDetail`, `LearningContentDetailQuery`, `LearningContentResource`, `StandaloneContentResource` (không đổi), `ListStandaloneContentsRequest` (nhận `type=video`), `CopyContentToCourse`, `RemoveCourseItem`, `DeleteStandaloneContent`, `routes/api.php`, `tests/Architecture/ArchitectureTest.php`; tài liệu: `error-codes.md`, `model-ownership.md`, `module-boundaries.md`, spec mục 13, `BACKLOG.md`, `STATE.md`.

---

### Task 1: Bảng, model, enum, cấu hình

**Files:**
- Create: `config/content.php`, migration, ba model + factory, ba enum, `tests/Feature/CourseContent/ContentVideoSchemaTest.php`
- Modify: `ArchitectureTest` (`MODEL_OWNERS`), `docs/architecture/model-ownership.md`

**Interfaces:**
- Produces:
  - `config('content.video')`: `driver` (env `CONTENT_VIDEO_DRIVER`, mặc định `local`), `disk` (`local`), `max_size_bytes`, `part_size_bytes`, `mime_type`, `playback_ttl_minutes`, `checkpoint_min_gap_seconds`, `upload_expiry_hours`, `external_hosts` (danh sách tên miền).
  - `ContentVideo` (`content_id` PK/FK cascade, `source`, `storage_driver` null, `storage_key` null, `external_url` null, `original_name`, `mime_type`, `size_bytes` null, `duration_seconds` null, timestamps; quan hệ `content()`, `checkpoints()`); CHECK `content_videos_source_matches_keys` (`upload` cần `storage_driver`, `storage_key`, `size_bytes` và không `external_url`; `external` cần `external_url` và không ba cột kia).
  - `ContentVideoUpload` (`id` uuid = `upload_id`, `content_id` FK cascade, `created_by_user_id`, `original_name`, `mime_type`, `size_bytes`, `part_size`, `parts_count`, `status` `VideoUploadStatus`, `failure_reason` null, timestamps; chỉ mục `(content_id, status)`).
  - `VideoCheckpoint` (`id` uuid, `content_id` FK `content_videos.content_id` cascade, `at_seconds` int ≥ 0, `source_question_version_id` FK `question_versions` restrict NOT NULL, `question_type`, `snapshot` json, `needs_recheck` bool default false, timestamps; unique `(content_id, at_seconds)`).
  - Enum `VideoSource` (`Upload`, `External`), `VideoStorageDriver` (`Local`, `S3`), `VideoUploadStatus` (`Uploading`, `Completed`, `Failed`, `Cancelled`). `LearningContent::video(): HasOne<ContentVideo>`, `LearningContent::videoUploads(): HasMany`.

- [ ] **Step 1: Viết test schema** (`ContentVideoSchemaTest`): `test_source_must_match_its_keys` (cả hai chiều vi phạm CHECK; bọc từng lần trong `DB::transaction` để giữ savepoint), `test_two_checkpoints_cannot_share_a_second_in_one_video`, `test_deleting_a_content_cascades_video_uploads_and_checkpoints`, `test_source_enum_values_match_the_check_constraint` (đọc `pg_get_constraintdef`, như `CourseContentSchemaTest`).
- [ ] **Step 2: Chạy, thấy đỏ.** Run: `docker compose exec -T api php artisan test --filter=ContentVideoSchemaTest`. Expected: FAIL (bảng chưa có).
- [ ] **Step 3: Viết migration, model, factory (state `external()` và `upload()` cho `ContentVideoFactory`), enum, `config/content.php`; khai báo `MODEL_OWNERS`, cập nhật `model-ownership.md`.**
- [ ] **Step 4: Chạy lại** test schema và `tests/Architecture`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: content video tables, models and config`.

---

### Task 2: `VideoStorage` và driver `local`, nhận diện MP4

**Files:**
- Create: `Storage/VideoStorage`, `Storage/LocalVideoStorage`, `Domain/Mp4Sniffer`, `tests/Unit/Modules/CourseContent/{LocalVideoStorageTest, Mp4SnifferTest}.php`
- Modify: `CourseContentServiceProvider` (bind `VideoStorage` theo `config('content.video.driver')`: `local` → `LocalVideoStorage`, driver khác ném lỗi cấu hình rõ ràng)

**Interfaces:**
- Produces:
  - `VideoStorage::driver(): VideoStorageDriver`; `mode(): string` (`chunked` cho `local`).
  - `beginUpload(string $uploadId): void`; `putPart(string $uploadId, int $partNumber, string $contents): void` (ghi đè nếu gửi lại); `partSizes(string $uploadId): array<int,int>` (số phần → kích thước đã ghi); `assemble(string $uploadId, string $storageKey, int $partsCount): int` (ghép theo thứ tự bằng luồng, không đọc cả tệp vào bộ nhớ, trả kích thước tệp, xóa phần tạm); `abort(string $uploadId): void` (xóa phần tạm, bỏ qua nếu không có); `delete(string $storageKey): void`; `exists(string $storageKey): bool`; `streamPath(string $storageKey): string` (đường dẫn tuyệt đối để `BinaryFileResponse` phục vụ, chỉ driver `local`); `size(string $storageKey): int`; `header(string $storageKey, int $length): string` (đọc `$length` byte đầu để `Mp4Sniffer` kiểm).
  - `Mp4Sniffer::isMp4(string $firstBytes): bool` (`true` khi byte 4..8 là `ftyp` và có ít nhất 12 byte).
  - Khóa lưu: `videos/{content_id}/{uuid}.mp4`; phần tạm: `video-parts/{upload_id}/{n}`; đĩa `config('content.video.disk')`.

- [ ] **Step 1: Viết test unit** dùng `Storage::fake('local')`: `test_parts_are_assembled_in_order_and_temp_parts_are_removed`, `test_resending_a_part_overwrites_it`, `test_assemble_streams_without_loading_the_whole_file` (ba phần 1 KB, kiểm nội dung ghép byte-chính-xác), `test_abort_removes_parts_and_is_safe_twice`, `test_delete_removes_the_file`, `test_unsupported_driver_in_config_fails_loudly`; `Mp4SnifferTest`: `ftyp` hợp lệ, tệp PDF, tệp rỗng, tệp ngắn hơn 12 byte, đuôi `.mp4` nhưng nội dung là văn bản.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** (ghép bằng `fopen`/`stream_copy_to_stream` trên đường dẫn thật của đĩa `local`; không dùng `Storage::get` cho tệp lớn).
- [ ] **Step 4: Chạy lại** hai test unit. Expected: PASS.
- [ ] **Step 5: Commit** `feat: video storage abstraction with local driver`.

---

### Task 3: Tạo nội dung `video` và hiển thị trạng thái video

**Files:**
- Create: `Queries/ContentVideoStateQuery`, `tests/Feature/CourseContent/VideoContentTest.php`
- Modify: `CreateCourseItemRequest`, `CreateCourseItem`, `AddCourseVideo` (mới, mẫu `AddCourseLecture`), `CreateStandaloneContentRequest`, `CreateStandaloneContent`, `ListStandaloneContentsRequest`, `LearningContentDetail`, `LearningContentDetailQuery`, `LearningContentResource`, `CourseItemsResource` (nếu có trường video)

**Interfaces:**
- Consumes: `AppendCourseItem`, `ContentVideo`, `ContentVideoUpload`.
- Produces: `POST /courses/{course}/items` và `POST /contents` nhận `type=video` chỉ cần `title` (nội dung rỗng, chưa có video). `ContentVideoStateQuery::handle(LearningContent): ?array` trả `{status: ready|uploading|failed, source, original_name, size_bytes, duration_seconds, external_url, checkpoint_count, failure_reason?}` hoặc `null` khi chưa có gì (suy ra theo quyết định 1). `LearningContentResource` thêm khóa `video` (`null` với bài giảng, tài liệu và video chưa có gì); nội dung video không có `body_html`.

- [ ] **Step 1: Viết test đỏ** (`VideoContentTest`): `test_teacher_adds_a_video_item_to_the_end_of_a_course`, `test_teacher_creates_a_standalone_video`, `test_video_content_has_no_body_html_and_a_null_video_state`, `test_state_is_ready_when_a_video_exists_uploading_when_an_upload_is_open_failed_when_only_a_failed_upload_exists` (tạo dòng bằng factory), `test_standalone_list_accepts_type_video`, `test_get_content_key_set_adds_video_for_all_types` (cập nhật các test key-set của lát 1b chỉ ở chỗ thêm `video`).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** như mục Interfaces; `PATCH /contents/{content}` đổi tên video vẫn đồng bộ tên mục.
- [ ] **Step 4: Chạy lại** `VideoContentTest`, `LearningContentTest`, `StandaloneContentTest`, `CreateCourseItemTest`, `ListStandaloneContentsTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: video contents and their state`.

---

### Task 4: Điểm dừng câu hỏi (`GET|PUT /contents/{content}/checkpoints`, `POST /checkpoints/{checkpoint}/confirm`)

**Files:**
- Create: `Actions/SyncCheckpoints`, `Actions/ConfirmCheckpoint`, `Queries/ListCheckpointsQuery`, exceptions `VideoNotReady` (409 `VIDEO_NOT_READY`), `CheckpointQuestionUnsupported` (422 `CHECKPOINT_QUESTION_UNSUPPORTED`), `CheckpointTooClose` (422 `CHECKPOINT_TOO_CLOSE`), `Http/Requests/{ManageCheckpointsRequest, SyncCheckpointsRequest, ConfirmCheckpointRequest}`, controllers, `Resources/VideoCheckpointResource`, `tests/Feature/CourseContent/VideoCheckpointTest.php`
- Modify: `routes/api.php`, `ArchitectureTest` (`SHARED_ENUMS['CourseContent'][] = 'QuestionBank/QuestionType'`), `error-codes.md`

**Interfaces:**
- Consumes: `ContentVideo`, `QuestionVersion` (+ `options`), `QuestionPolicy::useInAssessment`, `AuditLogger`.
- Produces: `SyncCheckpoints::handle(LearningContent $content, array $items, User $actor): Collection<VideoCheckpoint>` với `$items` là `list<array{id?: string, at_seconds: int, question_version_id: string}>`: khóa dòng `content_videos`; video chưa có → `VideoNotReady`; mỗi `at_seconds` từ 0 tới `duration_seconds` (nếu biết); mọi cặp điểm (kể cả với điểm không đổi) cách nhau ≥ 3 giây → nếu không `CheckpointTooClose`; câu nguồn thuộc sáu loại tự chấm → nếu không `CheckpointQuestionUnsupported`; dòng có `id` phải thuộc nội dung này (không thì 404); đổi `question_version_id` hoặc tạo mới thì chụp lại `snapshot` theo đúng dạng của `PublishAssessment` (`prompt`, `instructions`, `type_payload`, `grading_mode`, `grading_config`, `options[id,position,content,is_correct]`) và đặt `needs_recheck=false`; dòng không đổi câu thì giữ nguyên `snapshot` và `needs_recheck`; dòng vắng bị xóa; trả danh sách theo `at_seconds` tăng dần. `ConfirmCheckpoint::handle(VideoCheckpoint, User): VideoCheckpoint` đặt `needs_recheck=false` (idempotent). `GET` trả toàn bộ gồm `snapshot` (người quản lý thấy đáp án). Body `PUT`: `{checkpoints: [{id?, at_seconds, question_version_id}]}` (mảng, tối đa 50 phần tử; rỗng được, nghĩa là xóa hết). Resource: `id, at_seconds, question_version_id, question_type, snapshot, needs_recheck`.

- [ ] **Step 1: Viết test đỏ** (`VideoCheckpointTest`): `test_teacher_sets_checkpoints_and_reads_them_in_time_order_with_snapshots`; `test_snapshot_has_the_publish_shape_with_options_and_correct_flags`; `test_snapshot_does_not_change_when_the_source_question_changes_later`; `test_update_keeps_ids_and_snapshot_of_unchanged_rows_and_deletes_missing_ones`; `test_changing_the_question_of_a_row_resnapshots_and_clears_needs_recheck`; `test_confirm_clears_needs_recheck_and_is_idempotent`; `test_rows_closer_than_three_seconds_are_rejected_including_with_unchanged_rows`; `test_position_beyond_the_duration_is_rejected_but_any_position_is_allowed_when_duration_is_unknown`; `test_unsupported_question_types_are_rejected` (`short_answer`, `essay`, `file_upload`); `test_private_question_of_another_teacher_cannot_be_used_even_by_id`; `test_content_without_a_video_gives_video_not_ready`; `test_checkpoint_id_of_another_content_is_404`; `test_more_than_50_or_malformed_items_are_422`; `test_other_teacher_gets_404_student_and_parent_403`; `test_set_is_rolled_back_when_any_item_is_invalid` (một mục sai thì không dòng nào đổi). Thứ tự lỗi: quyền → định dạng 422 → `VIDEO_NOT_READY` → vị trí/loại câu 422.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; dùng `QuestionVersion::with('options')` một lượt (không N+1); mã lỗi vào `error-codes.md`.
- [ ] **Step 4: Chạy lại** `VideoCheckpointTest`, `ArchitectureTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: video checkpoints with question snapshots`.

---

### Task 5: Tải video theo phần (`video/upload`, `parts/{n}`, `complete`, `DELETE upload`)

**Files:**
- Create: `Actions/{StartVideoUpload, StoreVideoPart, CompleteVideoUpload, CancelVideoUpload, ReplaceContentVideo}`, exceptions `VideoUploadFailed` (422 `VIDEO_UPLOAD_FAILED`), `VideoUploadClosed` (409 `VIDEO_UPLOAD_CLOSED`), requests (`StartVideoUploadRequest`, `StoreVideoPartRequest`, `CompleteVideoUploadRequest`, `ManageVideoUploadRequest`), 4 controller, `Resources/VideoUploadResource`, `tests/Feature/CourseContent/VideoUploadTest.php`
- Modify: `routes/api.php`, `error-codes.md`

**Interfaces:**
- Consumes: `VideoStorage`, `Mp4Sniffer`, `ContentVideo`, `ContentVideoUpload`, `AuditLogger`, `SyncCheckpoints` (đặt cờ `needs_recheck`).
- Produces:
  - `POST /contents/{content}/video/upload` `{filename, size_bytes, mime_type}` → `201 {data: {upload_id, mode: 'chunked', part_size, parts_count}}`; kiểm `mime_type = video/mp4` (422), `1 ≤ size_bytes ≤ 1 GB` (422), nội dung phải là `video` (422 trên `type`); hủy lần tải `uploading` trước đó của nội dung (xóa phần tạm); `parts_count = ceil(size_bytes / part_size)`.
  - `PUT /contents/{content}/video/upload/{upload}/parts/{n}` (thân thô, tối đa `part_size` byte; `n` từ 1 tới `parts_count`) → `204`; lần tải không còn `uploading` → 409 `VIDEO_UPLOAD_CLOSED`; phần vượt `part_size` hoặc `n` ngoài khoảng → 422; lần tải thuộc nội dung khác → 404.
  - `POST /contents/{content}/video/complete` `{upload_id, duration_seconds}` (`duration_seconds` nguyên 1..86400): kiểm đủ phần, mỗi phần đúng `part_size` trừ phần cuối, tổng đúng `size_bytes`; ghép; kiểm `Mp4Sniffer`; thành công thì (một transaction) tạo hoặc thay `content_videos` (`ReplaceContentVideo`: đặt mọi điểm dừng `needs_recheck=true`, audit `content_video.replace` khi thay, xóa tệp cũ chỉ khi không còn `content_videos` khác dùng chung — việc xóa chạy sau commit), lần tải thành `completed` → `200 {data: video state}`; lỗi thì xóa tệp ghép dở, lần tải thành `failed` với `failure_reason`, video cũ giữ nguyên, trả 422 `VIDEO_UPLOAD_FAILED`.
  - `DELETE /contents/{content}/video/upload/{upload}` → `204`, lần tải thành `cancelled`, xóa phần tạm; lần tải đã đóng → 409 `VIDEO_UPLOAD_CLOSED`.

- [ ] **Step 1: Viết test đỏ** (`VideoUploadTest`, dùng `Storage::fake('local')`, tệp MP4 giả có tiêu đề `\0\0\0\x18ftypmp42` và phần nhỏ bằng cách đặt `content.video.part_size_bytes` thấp trong test): `test_full_upload_creates_the_video_and_reads_back_as_ready`; `test_size_over_1_gb_and_non_mp4_mime_are_rejected_at_start`; `test_resending_a_part_is_allowed_and_the_last_write_wins`; `test_missing_part_wrong_part_size_or_total_mismatch_fails_complete_and_keeps_the_old_video`; `test_file_that_is_not_mp4_by_content_fails_even_with_mp4_name_and_mime` (`failed`, tệp ghép bị xóa); `test_part_over_part_size_and_part_number_out_of_range_are_rejected`; `test_starting_a_new_upload_cancels_the_open_one_and_removes_its_parts`; `test_old_video_keeps_playing_state_ready_while_a_replacement_uploads`; `test_replacing_marks_checkpoints_needs_recheck_deletes_the_old_file_and_audits`; `test_replacing_keeps_the_old_file_when_a_copy_shares_the_storage_key`; `test_cancel_and_closed_upload_conflicts`; `test_upload_id_of_another_content_is_404`; `test_other_teacher_gets_404_student_and_parent_403`; `test_non_video_content_is_422`; `test_complete_failure_after_audit_failure_rolls_back` (ép `AuditLogger` ném lỗi: video cũ và điểm dừng không đổi, tệp mới bị dọn).
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; khóa dòng nội dung khi bắt đầu và hoàn tất để hai lần `complete` song song chỉ một lần thắng (lần sau thấy lần tải đã đóng).
- [ ] **Step 4: Chạy lại** `VideoUploadTest`, `VideoCheckpointTest`, `LocalVideoStorageTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: chunked video upload through the backend`.

---

### Task 6: Link video ngoài (`PUT /contents/{content}/video/external`)

**Files:**
- Create: `Domain/ExternalVideoUrl`, `Actions/SetExternalVideo`, `Http/Requests/SetExternalVideoRequest`, controller, `tests/Unit/Modules/CourseContent/ExternalVideoUrlTest.php`, `tests/Feature/CourseContent/ExternalVideoTest.php`
- Modify: `routes/api.php`

**Interfaces:**
- Produces: `ExternalVideoUrl::normalize(string $url): ?string` (chỉ `https`, host nằm trong `external_hosts` so khớp chính xác hoặc là tên miền con của mục cho phép; bỏ phần userinfo/cổng lạ; trả `null` nếu không hợp lệ). `PUT /contents/{content}/video/external` `{url, duration_seconds?}` → `200 {data: video state}`: tạo hoặc thay `content_videos` với `source=external`; thay video đang có thì cùng hậu quả như Task 5 (`ReplaceContentVideo`); hủy lần tải đang mở. Link không hợp lệ → 422 trên `url`.

- [ ] **Step 1: Viết test đỏ**: `ExternalVideoUrlTest`: URL YouTube/youtu.be/Vimeo hợp lệ; `http://`, `javascript:`, `https://evil.com/youtube.com`, `https://youtube.com.evil.com`, `https://user:pw@youtube.com/x`, chuỗi rỗng, URL dài trên 2048 ký tự đều `null`; `ExternalVideoTest`: `test_teacher_sets_an_external_video_and_state_is_ready_external`; `test_invalid_host_scheme_or_length_is_422`; `test_replacing_an_uploaded_video_with_a_link_deletes_the_file_marks_checkpoints_and_audits`; `test_setting_the_same_link_twice_does_not_audit_a_replacement`; `test_open_upload_is_cancelled_when_a_link_is_set`; `test_other_teacher_404_student_parent_403`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt** dùng chung `ReplaceContentVideo` của Task 5.
- [ ] **Step 4: Chạy lại** hai test trên và `VideoUploadTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: external video links`.

---

### Task 7: Phát video (`GET /contents/{content}/playback` và route phục vụ có Range)

**Files:**
- Create: `Queries/ContentVideoAccess`, `Actions/IssuePlaybackUrl`, `Http/Controllers/{PlaybackController, StreamVideoController}`, `Http/Requests/ManageLearningContentRequest` (dùng lại), `Resources/PlaybackResource`, `tests/Feature/CourseContent/VideoPlaybackTest.php`
- Modify: `routes/api.php` (route stream đặt **ngoài** nhóm `auth:sanctum`, có `signed` và giới hạn tốc độ), `error-codes.md` nếu thêm mã

**Interfaces:**
- Produces:
  - `ContentVideoAccess::canWatch(User $user, LearningContent $content): bool` — lát này: người quản lý nội dung (qua `Gate::forUser($user)->allows('manage', $content)`), người dùng phải đang `active`; lát 4 thêm học sinh thuộc đối tượng và mục đã mở trong cùng hàm này.
  - `GET /contents/{content}/playback` → `{data: {source: upload|external, url, expires_at}}`: video chưa có hoặc đang `uploading` mà chưa có video → 409 `VIDEO_NOT_READY`; `upload` trả URL ký (`URL::temporarySignedRoute('content-videos.stream', now()->addMinutes(15), ['content' => id, 'user' => userId])`); `external` trả `external_url` và `expires_at` null.
  - `GET /content-videos/{content}/stream` (tên route `content-videos.stream`, middleware `signed`): tải `user` từ tham số đã ký, kiểm `ContentVideoAccess::canWatch`, lấy `content_videos` (không có hoặc nguồn `external` → 404), phục vụ bằng `response()->file($path, ['Content-Type' => 'video/mp4', 'Accept-Ranges' => 'bytes', 'Cache-Control' => 'private, no-store'])` — `BinaryFileResponse` tự xử lý `Range` (206) và `If-Range`; không bao giờ trả đường dẫn đĩa.

- [ ] **Step 1: Viết test đỏ** (`VideoPlaybackTest`, `Storage::fake('local')` với tệp thật nhiều byte): `test_manager_gets_a_signed_url_that_streams_the_whole_file_with_accept_ranges`; `test_range_request_returns_206_with_the_exact_slice_and_content_range`; `test_open_ended_and_suffix_ranges_work_and_invalid_range_is_416`; `test_expired_url_tampered_user_or_signature_is_403`; `test_url_of_a_user_who_became_inactive_or_lost_access_is_403_on_open`; `test_external_video_returns_the_link_without_a_signed_url`; `test_content_without_a_ready_video_is_409_video_not_ready_even_if_an_upload_is_open`; `test_response_never_contains_a_filesystem_path`; `test_other_teacher_404_student_parent_403_on_playback`; `test_stream_works_without_any_session_cookie_or_token`; `test_stream_of_an_external_or_missing_video_is_404`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; `Content-Disposition` không đặt tên thật của tệp (dùng `inline`).
- [ ] **Step 4: Chạy lại** `VideoPlaybackTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: signed video playback with range support`.

---

### Task 8: Sao chép video, dọn tệp khi xóa, lệnh dọn tải dở

**Files:**
- Create: `Actions/{CopyContentVideo, ReleaseVideoFiles}`, `Console/PruneVideoUploads`, `tests/Feature/CourseContent/VideoCopyAndReleaseTest.php`
- Modify: `CopyContentToCourse`, `RemoveCourseItem`, `DeleteStandaloneContent`, `CourseContentServiceProvider` (đăng ký lệnh), `docs/BACKLOG.md`

**Interfaces:**
- Produces:
  - `CopyContentVideo::handle(LearningContent $source, LearningContent $copy): void`: chép `content_videos` (cùng `storage_driver`, `storage_key`, nguồn, thời lượng…) và điểm dừng (cùng `snapshot`, `needs_recheck` giữ nguyên); `CopyContentToCourse` gọi khi nguồn là `video`.
  - `ReleaseVideoFiles::collect(LearningContent): list<array{driver: string, key: string}>` (gọi **trước** khi xóa nội dung, cũng hủy lần tải đang mở và dọn phần tạm) và `::purge(list $files): void` (gọi **sau commit**: xóa tệp nào không còn `content_videos` nào dùng); `RemoveCourseItem` và `DeleteStandaloneContent` dùng cho nội dung `video` (qua `DB::afterCommit`).
  - `php artisan content-videos:prune-uploads` đánh dấu `cancelled` các lần tải `uploading` quá `upload_expiry_hours` và xóa phần tạm; in số lượng; không đụng lần tải còn mới.

- [ ] **Step 1: Viết test đỏ**: `test_copying_a_standalone_video_copies_the_video_row_and_checkpoints_with_the_same_storage_key`; `test_copy_of_a_video_without_a_video_copies_the_empty_content`; `test_deleting_the_copy_keeps_the_file_for_the_source`; `test_deleting_the_source_keeps_the_file_for_the_copy_and_deleting_the_last_one_removes_it`; `test_removing_a_course_video_item_removes_content_file_and_open_upload_parts`; `test_file_deletion_failure_does_not_fail_the_request` (kho giả ném lỗi khi xóa: yêu cầu vẫn 204, dữ liệu đã xóa); `test_rollback_of_a_failed_removal_does_not_delete_the_file` (transaction thất bại thì tệp còn); `test_prune_cancels_only_old_open_uploads_and_removes_their_parts`.
- [ ] **Step 2: Chạy, thấy đỏ.**
- [ ] **Step 3: Cài đặt**; ghi vào `BACKLOG.md` nợ kỹ thuật "lệnh `content-videos:prune-uploads` chưa có lịch chạy (scheduler chưa bật)" nếu chưa có.
- [ ] **Step 4: Chạy lại** `VideoCopyAndReleaseTest`, `CopyContentToCourseTest`, `RemoveCourseItemTest`, `ArchiveDeleteContentTest`. Expected: PASS.
- [ ] **Step 5: Commit** `feat: copy videos, release files on removal, prune stale uploads`.

---

### Task 9: Đối chiếu cuối, tài liệu, OpenAPI

**Files:**
- Modify: `docs/architecture/error-codes.md`, `model-ownership.md`, `module-boundaries.md` (nếu cần), spec thêm mục 13 "Ghi chú hiện thực lát 3", `docs/BACKLOG.md`, `docs/STATE.md`; xuất lại OpenAPI

- [ ] **Step 1: Chạy cả bộ test.** Run: `docker compose exec -T api php artisan test`. Expected: tất cả PASS.
- [ ] **Step 2: Pint chỉ file đã đổi, PHPStan.** Run: `git diff --name-only origin/main...HEAD -- '*.php'` rồi Pint trên danh sách đó; `docker compose exec -T api composer analyse`. Expected: Pint sạch, PHPStan không thêm lỗi so với 30.
- [ ] **Step 3: Xuất OpenAPI** (`composer api-docs`); kiểm có các endpoint: `PUT|GET /contents/{content}/checkpoints`, `POST /checkpoints/{checkpoint}/confirm`, `POST /contents/{content}/video/upload`, `PUT .../video/upload/{upload}/parts/{n}`, `POST .../video/complete`, `DELETE .../video/upload/{upload}`, `PUT .../video/external`, `GET /contents/{content}/playback`, `GET /content-videos/{content}/stream`.
- [ ] **Step 4: Kiểm biên.** `grep -rn "Modules\\\\CourseContent" app/Modules/Assessment` — Expected: chỉ `CreateAssessmentController`; `grep -rn "whereNull('course_id')" app/Modules/CourseContent` — Expected: không dòng nào; kiểm bằng tay rằng không có chỗ nào trả `storage_key` hay đường dẫn đĩa ra response (`grep -rn "storage_key" app/Modules/CourseContent/Http`).
- [ ] **Step 5: Cập nhật tài liệu và STATE** như mục Files; commit `docs: slice 3 notes` ở repo docs; OpenAPI không commit (file bị gitignore).

---

## Self-review

- **Phủ spec:** `VideoStorage` + driver `local` + hợp đồng tải lên (Task 2, 5), `content_videos` (Task 1; tách `content_video_uploads` có ghi quyết định 1), link ngoài (Task 6), phát có Range (Task 7), điểm dừng + bản chụp + `needs_recheck` + `confirm` (Task 4, 5), sao chép video khóa dùng chung `storage_key` (Task 8), dọn tải dở thủ công (Task 8), audit thay tệp (Task 5, 6). Phần học sinh (đối tượng, mở khóa tuần tự, tiến độ, trả lời điểm dừng, chặn tua, xóa vị trí xem dở) là lát 4; driver `s3` là `B-096`.
- **Kiểu nhất quán:** `ContentVideo`, `ContentVideoUpload`, `VideoCheckpoint`, `VideoStorage`, `ReplaceContentVideo`, `ReleaseVideoFiles`, `ContentVideoAccess`, mã lỗi `VIDEO_NOT_READY`, `VIDEO_UPLOAD_FAILED`, `VIDEO_UPLOAD_CLOSED`, `CHECKPOINT_QUESTION_UNSUPPORTED`, `CHECKPOINT_TOO_CLOSE` dùng đồng nhất ở Task 1–8 và `error-codes.md`.
- **Tỷ lệ:** chín task, mỗi task có Interfaces, test đặt tên và các giá trị spec ghim; không chép thân hàm mà chữ ký và test đã quyết định.
