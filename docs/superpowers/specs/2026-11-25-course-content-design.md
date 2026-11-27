# Thiết kế: Khóa học có nội dung học (bài giảng, tài liệu, video, video tương tác, tiến độ)

**Ngày:** 2026-11-25
**Phạm vi:** `piclass-api` (module mới `CourseContent`, sửa `Academic`, `Assessment`, `File`), `piclass-web` (Giáo viên, Học sinh, Admin, Phụ huynh)
**Trạng thái:** user đã duyệt (2026-11-25): TipTap, module `CourseContent` (`ARCH-39`), S3 hoãn. Kế tiếp: plan lát 1
**Nguồn quyết định:** `DEC-LRN-052`, `DEC-LRN-053`, `BR-COURSE-002`; mockup đợt một và hai (`piclass-mockup`, màn ST-09..12, TC-15..19, AD-12). Không lặp lại nội dung quyết định ở đây; file này chỉ chốt **cách hiện thực**.

## 1. Phạm vi

Làm: mục khóa gồm bài giảng chữ, tài liệu, video (S3 hoặc link ngoài), chương, bài đánh giá (đã có); video tương tác (điểm dừng câu hỏi); nội dung độc lập giao cho Lớp/Học sinh; tiến độ theo mục; mở khóa tuần tự; điều kiện hoàn thành khóa; tiến độ cho Giáo viên, Admin, Phụ huynh.

Không làm (ghi để khỏi hiểu nhầm): driver S3 (chuẩn bị sẵn chỗ, user cấu hình sau); mã hóa lại video HLS (thiết kế sẵn chỗ nâng, `DEC-LRN-052` mục 9); phụ đề, tốc độ phát tùy biến ở BE; chứng chỉ khi hoàn thành khóa (`BR-SYS-002`); thông báo khi giao nội dung (cùng `B-090`, làm sau); tìm kiếm toàn văn trong bài giảng.

## 2. Module và quyền sở hữu (đề xuất `ARCH-39`)

Module mới **`CourseContent`** (module thứ 13), đúng gợi ý cuối `ARCH-38`.

| Model | Chủ ghi | Ghi chú |
|---|---|---|
| `Course` (thêm 3 cột cài đặt) | Academic | Giữ chủ. Cài đặt học đi qua Action của Academic. |
| `CourseItem` | **CourseContent** (chuyển từ Assessment) | Mục có nhiều loại nên thuộc module giữ nội dung. Assessment chỉ còn đọc. Enum `CourseItemType` đã ở `Academic/Enums`, giữ. |
| `LearningContent` | CourseContent | Bài giảng, tài liệu, video: một bảng, phân loại bằng `type`. |
| `ContentVideo` | CourseContent | Dữ liệu riêng của video (nguồn, S3, trạng thái). |
| `ContentAudience` | CourseContent | Lớp hoặc Học sinh của nội dung độc lập. |
| `VideoCheckpoint` | CourseContent | Điểm dừng và bản chụp câu hỏi. |
| `ContentProgress` | CourseContent | Tiến độ của một học sinh trên một nội dung. |
| `CheckpointResponse` | CourseContent | Lượt trả lời đầu và số lượt của học sinh ở một điểm dừng. |
| `Attachment`, `AttachmentLink` | File | Thêm đích liên kết `learning_content` (tệp tài liệu, ảnh trong bài giảng). |

CourseContent **đọc** Academic (học viên của khóa qua `CourseLearnersQuery`), QuestionBank (câu nguồn), Assessment (bài của mục, kết quả để tính "xong" mục bài đánh giá), File. Ghi vào module khác chỉ qua Action của module đó (R4). Việc chuyển `CourseItem` làm một PR riêng, đầu tiên, không đổi hành vi.

## 3. Dữ liệu

Mọi khóa chính `uuid`; thời gian lưu UTC (`docs/rules/time.md`).

**`courses` (thêm cột):** `sequential_unlock boolean default false`, `completion_rule enum('all_items','all_items_and_min_score') default 'all_items'`, `completion_min_score decimal(5,2) null` (chỉ dùng khi rule thứ hai; kiểm tra bắt buộc có khi chọn rule đó).

**`learning_contents`:** `id`, `type enum('lecture','document','video')`, `course_id` null FK `courses` `restrict`, `owner_user_id` FK `users` (người tạo; với khóa dùng chung là Admin), `title`, `body_html text null` (chỉ `lecture`, đã làm sạch), `status enum('active','archived')`, `archived_at`, `timestamps`. Chỉ mục `(course_id)`, `(owner_user_id, type)`. **Quy tắc `course_id`** giống `BR-ASSESS-029`: `null` là nội dung độc lập (Giáo viên sở hữu, có `ContentAudience`, hiện ở TC-18); có giá trị là nội dung của khóa (không có `ContentAudience`, không hiện ở TC-18). Mọi truy vấn danh sách độc lập lọc `course_id IS NULL` qua **một scope duy nhất** có test canh (như `Assessment`).

**`content_videos`:** `content_id` PK/FK `learning_contents` cascade, `source enum('upload','external')`, `status enum('uploading','ready','failed')` (nguồn `external` luôn `ready`), `storage_key null` (khóa trong kho lưu trữ riêng tư, driver `local` hoặc `s3`), `storage_driver enum('local','s3')` (lưu theo từng video để đổi driver mặc định không làm mất video cũ), `external_url null`, `original_name`, `mime_type`, `size_bytes bigint`, `duration_seconds int null`, `upload_id null` (multipart S3), `failure_reason null`, `timestamps`. CHECK: `source='upload'` thì có `storage_key`, `source='external'` thì có `external_url`. Playback chỉ đọc `storage_key` qua một chỗ duy nhất (§5) để sau thêm HLS không đổi dữ liệu học sinh.

**`content_audiences`:** như `assessment_audiences`: `content_id`, `class_id` null, `student_id` null, `added_by_user_id`, `created_at`; unique `(content_id, class_id)` và `(content_id, student_id)`; CHECK đúng một đích. Test: nội dung có `course_id` không được có dòng audience.

**`course_items` (sửa):** `type enum` thêm `lecture`, `document`, `video`, `chapter` (chương, chỉ có `title`); thêm `content_id` null FK `learning_contents` `restrict` unique. CHECK theo `type`: `assessment` thì có `assessment_id`, không `content_id`; `lecture/document/video` thì có `content_id`, không `assessment_id`; `chapter` thì không có cả hai. **Chương là mục `chapter` đứng phẳng** trong danh sách mục (đúng mockup), không lồng cây: cột `parent_id` giữ nguyên nhưng **không dùng** (chốt khi viết plan 1b, 2026-11-26; đổi so với bản đầu của spec). `title` của mục bài giảng/tài liệu/video luôn bằng `learning_contents.title` (Action đồng bộ khi sửa); mục `assessment` và `chapter` giữ `title` riêng.

**`video_checkpoints`:** `id`, `content_id` FK `content_videos`, `at_seconds int`, `source_question_version_id` FK `question_versions` `restrict` NOT NULL, `question_type`, `snapshot json` (bản chụp đề, đáp án, giải thích như `assessment_questions.snapshot`), `needs_recheck boolean default false` (bật khi thay tệp video, `DEC-LRN-052` mục 11), `timestamps`. Unique `(content_id, at_seconds)`; Action chặn hai điểm cách nhau dưới 3 giây (như mockup). Chỉ nhận loại câu tự chấm được: `single_choice`, `multiple_choice`, `true_false`, `fill_in_the_blank`, `matching`, `ordering` (`DEC-LRN-053`: tự luận và nộp tệp không dùng); loại khác 422.

**`content_progress`:** `id`, `student_id`, `content_id`, `status enum('in_progress','completed')`, `last_position_seconds int default 0`, `first_opened_at`, `completed_at null`, `updated_at`. Unique `(student_id, content_id)`. Dòng được tạo **lười** khi học sinh mở nội dung lần đầu (như `StudentAssessment`, `ARCH-38`), không có hook vào/ra lớp.

**`checkpoint_responses`:** `id`, `student_id`, `checkpoint_id` FK `video_checkpoints` cascade, `first_answer json`, `first_is_correct boolean`, `attempts_count int`, `answered_correctly_at null`. Unique `(student_id, checkpoint_id)`. Lượt đầu ghi một lần, không bao giờ ghi đè (`DEC-LRN-053` mục 3); các lượt sau chỉ tăng `attempts_count` và đặt `answered_correctly_at` khi đúng.

**Tiến độ mục bài đánh giá:** không lưu thêm; tính từ `Result`/`Submission` có sẵn (đã nộp kể cả trễ thì tính xong, `DEC-LRN-052` mục 8).

## 4. Quy tắc nghiệp vụ hiện thực

1. **Học viên được thấy nội dung:** nội dung của khóa: là học viên của khóa (`CourseLearnersQuery::isLearner`). Nội dung độc lập: có trong `content_audiences` trực tiếp hoặc là thành viên đang hoạt động của một Lớp trong đó. Quyền tính khi cần, không lưu dòng ghi danh (`DEC-LRN-026`).
2. **Mở khóa tuần tự:** khi `courses.sequential_unlock`, mục thứ n chỉ mở khi mọi mục trước (theo `position`, bỏ qua `chapter`) đã xong. Server kiểm ở mọi endpoint học sinh đọc nội dung hoặc ghi tiến độ; mục chưa mở trả 403 `CONTENT_LOCKED` kèm tên mục cần xong trước. Danh sách mục trả cờ `locked` để FE hiển thị. Nội dung độc lập không có thứ tự nên không khóa.
3. **Mục xong khi:** bài giảng, tài liệu: học sinh bấm "đánh dấu đã học" (bỏ đánh dấu được). Video: đã xem tới ≥ 95% thời lượng **và** mọi điểm dừng đã có `answered_correctly_at`. Video link ngoài không biết thời lượng thật thì chỉ cần mọi điểm dừng đúng và học sinh bấm "xong". Bài đánh giá: đã nộp.
4. **Chặn tua qua điểm dừng:** server tính "vị trí tối đa được phép" = giây của điểm dừng chưa trả lời đúng đầu tiên; `PUT progress` với vị trí vượt mức đó bị kẹp xuống mức đó (không báo lỗi). Với link ngoài FE không chặn được, server vẫn không đánh dấu xong khi còn điểm dừng chưa đúng.
5. **Hoàn thành khóa:** tính khi cần, không lưu: rule `all_items` thì mọi mục (trừ chương) đã xong; rule thứ hai thì thêm điểm trung bình các bài đánh giá của khóa ≥ `completion_min_score` (bài chưa chấm không tính, `BR-ASSESS-026`).
6. **Sửa sau khi có người học:** bài giảng/tài liệu sửa trực tiếp, không phiên bản. Video: thay tệp thì xóa `last_position_seconds` của mọi học sinh, đặt `needs_recheck` cho mọi điểm dừng, **không** xóa tiến độ đã `completed`. Giáo viên xác nhận lại từng điểm (`POST .../confirm`) hoặc xóa.
7. **Xóa, lưu trữ:** nội dung đã có `content_progress` chỉ lưu trữ, không xóa (như `BR-ASSESS-010`). Nội dung đang là mục của khóa muốn gỡ khỏi khóa thì xóa dòng `course_items`; nếu nội dung thuộc khóa (`course_id`) và chưa ai học thì xóa luôn, đã có người học thì lưu trữ. Khóa `archived` không nhận thêm mục (`BR-COURSE-006`).
8. **Sao chép:** "thêm vào khóa" từ nội dung độc lập tạo bản mới (`course_id` có giá trị): chép `title`, `body_html`, bản chụp tệp đính kèm (tạo `AttachmentLink` mới trỏ cùng `Attachment`), với video chép cả `ContentVideo` **tham chiếu cùng `storage_key`** (không nhân đôi tệp S3; đếm tham chiếu khi dọn, §5) và điểm dừng. Hai bản độc lập sau đó.
9. **Sao chép khóa dùng chung cho Teacher:** ngoài phạm vi (không có trong `DEC-LRN-052`).

## 5. Tệp và video

**Tài liệu và tệp đính kèm bài giảng:** dùng luồng `File` hiện có (qua backend). Giới hạn 50 MB, PDF/Word/PowerPoint (`DEC-LRN-052` mục 10); kiểm MIME thật bằng nội dung, không tin phần mở rộng.

**Lưu trữ video: chưa dùng S3, cấu hình sau (user chốt 2026-11-25).** Backend chỉ làm việc với một lớp trừu tượng `VideoStorage` (interface nhỏ: bắt đầu tải, nhận phần, hoàn tất, hủy, tạo link phát, xóa), chọn driver bằng cấu hình `CONTENT_VIDEO_DRIVER=local|s3`. Bản đầu chỉ viết driver **`local`** (đĩa riêng tư của Laravel, ngoài thư mục công khai); driver `s3` viết khi user có bucket, không phải đổi dữ liệu, API hay FE ngoài việc dùng đúng `mode` dưới đây.

Hợp đồng tải lên (cùng một bộ endpoint cho cả hai driver, FE đọc `mode`):

1. `POST /contents/{id}/video/upload` `{filename, size_bytes, mime_type}`: kiểm `mime=video/mp4`, `size ≤ 1 GB`, quyền sở hữu; tạo bản ghi `status=uploading`; trả `{upload_id, mode, part_size, parts?}`.
   - `mode=chunked` (driver `local`): FE cắt tệp thành phần 5 MB, gửi `PUT /contents/{id}/video/upload/{upload_id}/parts/{n}` qua backend, có thể gửi lại một phần (tải lại khi mất mạng). Backend ghi từng phần vào thư mục tạm.
   - `mode=direct` (driver `s3`, làm sau): trả danh sách URL ký cho từng phần; FE tải thẳng lên S3 (cần CORS cho miền web).
2. `POST /contents/{id}/video/complete` `{upload_id, parts?}`: `local` ghép các phần, kiểm kích thước và nhận diện MP4 bằng nội dung tệp (không tin `Content-Type`); `s3` gọi `CompleteMultipartUpload` rồi `HeadObject`. Thành công thì `status=ready`; lỗi thì `failed` kèm lý do. Thời lượng do FE đọc khi chọn tệp và gửi lên (kiểm lại bằng `ffprobe` ở bản sau nếu cần).
3. `DELETE /contents/{id}/video/upload/{upload_id}`: hủy và xóa phần tạm.
4. Dọn tải dở: lệnh theo lịch xóa thư mục tạm quá 24 giờ (driver `local`). Lịch chạy phụ thuộc scheduler, mà scheduler hiện chưa bật (`STATE.md`, Chờ user), nên bản đầu cho phép dọn thủ công bằng `php artisan` và ghi vào nợ kỹ thuật; driver `s3` dùng lifecycle rule của bucket thay thế.

**Phát:** một endpoint duy nhất `GET /contents/{id}/playback` (học sinh có quyền, mục đã mở) trả `{url, expires_at}`, hạn 15 phút; FE xin lại khi hết hạn.
- `local`: URL là route của backend có chữ ký tạm thời (`temporarySignedRoute`), phục vụ tệp **có hỗ trợ Range** để tua được (`BinaryFileResponse`); route kiểm lại quyền, không chỉ tin chữ ký.
- `s3`: link GET ký của S3.
- Nguồn `external`: trả `external_url` đã kiểm tên miền cho phép (YouTube, Vimeo).
Phát video qua PHP tốn worker và băng thông máy chủ; chấp nhận được khi chưa vận hành chính thức, và đây là lý do driver `s3`/CDN là bước sau cần làm trước khi có nhiều học sinh xem cùng lúc. Chuyển HLS sau này chỉ đổi thân endpoint này và bước `complete`.

**Quyền xem tệp** (`DEC-LRN-052` mục 10): học sinh thuộc đối tượng, giáo viên sở hữu, Admin. Phụ huynh không xem tệp và video, chỉ xem tiến độ. Mở rộng `AssessmentFileAccessQuery` thành truy vấn quyền tệp cho `learning_content`.

**Ảnh trong bài giảng:** tải qua `File` (≤ 5 MB, ảnh), lưu `AttachmentLink`; `body_html` chứa `<img data-attachment-id="…">`, Resource thay bằng link ký lúc đọc (HTML trong DB không chứa URL hết hạn).

## 6. Bài giảng soạn kiểu Word

- Lưu HTML. **Làm sạch ở backend** bằng danh sách cho phép (HTMLPurifier): `h2 h3 p b strong i em u s ul ol li blockquote table thead tbody tr th td a img br`; thuộc tính `href` (chỉ `http`, `https`, `mailto`), `data-attachment-id`, `alt`, `style` chỉ `text-align`. Mọi thứ khác bị bỏ, đặc biệt `script`, `iframe`, `on*`. Làm sạch **lúc ghi** và lúc **hiển thị** (phòng dữ liệu cũ).
- Giới hạn 200 KB HTML. Test phải có chuỗi tấn công XSS thường gặp.
- Thư viện soạn ở FE: xem điểm hỏi 1.

## 7. API (đề xuất; chốt tên khi viết plan, theo `docs/rules/openapi.md`)

Giáo viên/Admin (quyền theo chủ sở hữu, như `BR-COURSE-004/005`):

| Việc | Endpoint |
|---|---|
| Cài đặt khóa | `PATCH /courses/{id}` thêm `sequential_unlock`, `completion_rule`, `completion_min_score` |
| Mục khóa | `GET /courses/{id}/items`, `POST /courses/{id}/items` (`type` + nội dung), `PATCH /course-items/{id}`, `DELETE /course-items/{id}`, `PUT /courses/{id}/items/order` (sắp xếp, đổi chương) |
| Nội dung | `GET /contents?type=&q=` (độc lập của tôi), `POST /contents`, `GET/PATCH/DELETE /contents/{id}`, `PUT /contents/{id}/audience`, `POST /contents/{id}/copy-to-course` |
| Video | `POST /contents/{id}/video/upload`, `POST /contents/{id}/video/complete`, `DELETE /contents/{id}/video/upload` (hủy), `PUT /contents/{id}/video/external` |
| Điểm dừng | `GET/PUT /contents/{id}/checkpoints` (thay cả tập), `POST /checkpoints/{id}/confirm` |
| Tiến độ khóa | `GET /courses/{id}/progress` (Giáo viên chủ khóa, Admin) |

Học sinh:

| Việc | Endpoint |
|---|---|
| Chi tiết khóa kèm tiến độ và khóa | `GET /me/courses/{id}` (mở rộng `GET /courses/{id}` hiện có) |
| Nội dung được giao | `GET /me/contents` |
| Đọc một nội dung | `GET /me/contents/{id}` |
| Ghi tiến độ | `PUT /me/contents/{id}/progress` `{position_seconds?, completed?}` |
| Phát video | `GET /contents/{id}/playback` |
| Trả lời điểm dừng | `POST /checkpoints/{id}/answers` → `{is_correct, explanation?, can_continue}` |

Phụ huynh: `GET /me/children/{studentId}/courses/{course}/progress` (đổi từ `/students/{id}/courses/{courseId}/progress` ở lát 4b để theo quy ước các endpoint Phụ huynh dưới `/me/children/`) (ghép vào mục "Lớp & khóa học", `DEC-LRN-047/048`), không có endpoint nội dung.

Lỗi mới (thêm vào `error-codes.md` khi làm): `CONTENT_LOCKED` (403), `VIDEO_NOT_READY` (409), `VIDEO_UPLOAD_FAILED` (422), `CHECKPOINT_QUESTION_UNSUPPORTED` (422), `CHECKPOINT_TOO_CLOSE` (422), `CONTENT_IN_USE` (409, xóa nội dung đã có người học).

## 8. Phân quyền và kiểm thử trọng yếu

Theo checklist `backend-gotchas.md`: fixture có dữ liệu thật cho từng nhóm "người khác" (giáo viên khác, học sinh ngoài khóa, phụ huynh); vai trò rỗng; thứ tự early-return; thử đột biến. Các ca bắt buộc:

- Học sinh ngoài đối tượng không đọc được nội dung, không xin được link phát, không ghi được tiến độ (kể cả đoán `id`).
- Mục bị khóa tuần tự: đọc, ghi tiến độ, xin link phát đều 403 `CONTENT_LOCKED`; mở lại khi mục trước xong; học sinh rời lớp mất quyền ngay.
- Chặn tua: `PUT progress` vượt điểm dừng chưa đúng bị kẹp; trả lời sai nhiều lần chỉ ghi nhận lượt đầu; điểm dừng đúng rồi không ghi đè lượt đầu.
- Video không `ready` không phát được; thay tệp xóa vị trí xem dở nhưng giữ `completed`.
- `course_id`: nội dung của khóa không hiện ở danh sách độc lập; không có audience; bản sao độc lập với bản gốc.
- Làm sạch HTML: các chuỗi XSS; ảnh đính kèm của người khác không chèn được vào bài của mình.
- Audit: thêm/gỡ đối tượng nội dung (`content_audience.add/remove`, như `course_audience`), đổi cài đặt khóa, thay tệp video, xóa/lưu trữ nội dung.

## 9. Chia lát (mỗi lát một nhánh, plan riêng, review opus ở cuối lát lớn)

| # | Lát | Nội dung | Phụ thuộc |
|---|---|---|---|
| 1 | BE nền | Chuyển `CourseItem` sang `CourseContent` (không đổi hành vi); migration `learning_contents`, cột cài đặt khóa, mục khóa nhiều loại, chương; CRUD bài giảng + tài liệu (có làm sạch HTML, ảnh, tệp); sắp xếp | — |
| 2 | BE độc lập | `ContentAudience`, giao Lớp/Học sinh, sao chép vào khóa, danh sách TC-18, lưu trữ/xóa | 1 |
| 3 | BE video | `VideoStorage` + driver `local` (tải theo phần qua backend, link phát có chữ ký và Range), `ready/failed`, link ngoài, điểm dừng (chụp câu, `needs_recheck`); driver `s3` để sau | 1 |
| 4 | BE học sinh | Đọc nội dung theo quyền, tiến độ, mở khóa tuần tự, trả lời điểm dừng, hoàn thành khóa, tiến độ cho Giáo viên/Admin/Phụ huynh | 1–3 |
| 5 | FE Giáo viên | TC-15 tab Nội dung và cài đặt, TC-16 soạn bài giảng, TC-18 | 1–2 |
| 6 | FE video | TC-17 (tải lên, điểm dừng, tạo câu mới dùng lại TC-08) | 3, 5 |
| 7 | FE Học sinh | ST-09 chi tiết khóa, ST-10, ST-11, ST-12 | 4 |
| 8 | FE tiến độ | TC-19, AD-12 tab Nội dung, tiến độ của con (Phụ huynh) | 4, 5, 7 |

Lát BE và FE chạy xen kẽ theo `working-flow.md` bước 3 và 4 khi hình dạng API đã rõ. Lát 1 nên làm trước vì mọi lát khác dựa vào nó. Mockup phần Lớp và Học viên của TC-15, AD-12 còn theo mô hình ghi danh cũ nên **không** dùng làm nguồn cho hai tab đó (đối chiếu màn thật đang chạy).

## 10. Đã duyệt (user, 2026-11-25)

1. Thư viện soạn văn bản: **TipTap**.
2. Module mới `CourseContent` và chuyển `CourseItem` khỏi Assessment: `ARCH-39`.
3. Hạ tầng S3: hoãn; bản đầu driver `local`, driver `s3` là `B-096`.

## 11. Ghi chú hiện thực lát 1b (2026-11-26)

- Điểm tối thiểu gửi lên khi điều kiện hoàn thành là `all_items` không bị từ chối, chỉ bị xóa (Action đặt `null`).
- `GET|PATCH /contents/{content}`: người không xem được khóa nhận 404, xem được nhưng không quản lý nhận 403.
- Ảnh trong bài giảng: phải do người sửa upload hoặc đã gắn sẵn vào bài; mã ảnh hợp lệ nhưng không tồn tại bị từ chối (`LECTURE_IMAGE_NOT_ALLOWED`), mã sai định dạng bị bỏ qua. Mọi liên kết tệp của một bài giảng chính là các ảnh của nó ở lát này.
- Upload có `purpose` (`course_document` | `course_image`) dùng bộ giới hạn riêng trong `config/attachments.php`; tài liệu phải do người thêm (hoặc Admin) upload và chưa gắn nơi khác.
- Xóa nội dung đã làm là xóa hẳn; nhánh "đã có người học thì chỉ lưu trữ" làm cùng bảng tiến độ ở lát 4. Tệp bị gỡ chỉ mất liên kết (dọn tệp: `B-099`).
- Học sinh chưa thấy chương, bài giảng, tài liệu ở lát này (danh sách mục của học viên vẫn chỉ gồm bài đánh giá đã mở) và chưa xem được tệp (`AttachmentPolicy` chỉ cho người quản lý khóa): lát 4.

## 12. Ghi chú hiện thực lát 2 (2026-11-26)

- Nội dung độc lập (`course_id` null) tạo bằng `POST /contents` (bài giảng hoặc tài liệu; video ở lát 3), quản lý qua `GET|PATCH /contents/{content}`; người khác (không phải Admin) nhận 404.
- Danh sách "Bài giảng của tôi" (`GET /contents`) chỉ gồm nội dung của chính mình, Admin thấy tất cả; mặc định chỉ `active`, bộ lọc `type`, `status`, `search`, `class_id`.
- Giao đối tượng theo mẫu khóa: `GET|POST /contents/{content}/audience`, `DELETE /contents/{content}/audience/{audienceId}` (không dùng `PUT` thay cả tập như mục 7 bản đầu). Giáo viên chỉ giao cho lớp mình dạy hoặc học sinh `active` trong các lớp đó; audit `content_audience.add|remove`.
- Sao chép vào khóa: `POST /contents/{content}/copy-to-course` `{course_id}`; bản chép dùng chung `Attachment` (link riêng), nên ảnh dùng lại giữa các khóa là cố ý.
- Lưu trữ và khôi phục là hai endpoint riêng (`POST .../archive`, `POST .../restore`, idempotent); xóa hẳn nội dung độc lập bằng `DELETE /contents/{content}`. Nhánh "đã có người học thì chỉ lưu trữ" làm cùng bảng tiến độ ở lát 4.
- Audit bổ sung của lát 1b: `course.settings_update`, `course_item.remove` (bài giảng/tài liệu), `content.archive|restore|delete`. Mã lỗi mới: `CONTENT_HAS_NO_AUDIENCE`, `CONTENT_ARCHIVED`, `CONTENT_AUDIENCE_ALREADY_ADDED`, `CONTENT_BELONGS_TO_COURSE`.
- Mọi Action đổi mục khóa khóa dòng khóa học trước các dòng mục (`Concerns/LocksCourse`); tệp tài liệu được khóa và kiểm lại "chưa gắn nơi khác" trong Action (`Concerns/ClaimsDocumentAttachment`).

## 13. Ghi chú hiện thực lát 3 (2026-11-27)

- Bảng `content_video_uploads` tách riêng khỏi `content_videos` (mục 3 bản đầu để `upload_id`, `status` trong `content_videos`): thay tệp khi đang có video không làm video cũ ngừng phát. Trạng thái trả ra API suy ra: `ready` (có dòng `content_videos`), `uploading`, `failed` (chưa có video và lần tải gần nhất lỗi), `null`.
- Tải theo phần qua backend (`mode=chunked`): `POST .../video/upload`, `PUT .../video/upload/{upload}/parts/{part}` (thân thô, tối đa 5 MB, cần `Content-Length`), `POST .../video/complete` (`duration_seconds` 1..86400), `DELETE .../video/upload/{upload}`; link ngoài `PUT .../video/external`; phát `GET .../playback` và `GET /content-videos/{content}/stream` (URL ký 15 phút, hỗ trợ Range, kiểm lại quyền theo `user` trong chữ ký). Video ngoài lưu `original_name` = tên miền, `mime_type` = `video/external`.
- Điểm dừng sửa theo từng dòng (`id` có thì cập nhật, không có thì tạo, vắng thì xóa) để lát 4 gắn câu trả lời vào `checkpoint_id`; vị trí tối đa 86400 giây (`content.video.max_duration_seconds`); lỗi vị trí vượt thời lượng là `VALIDATION_FAILED` theo trường `checkpoints.N.at_seconds`.
- Thay video (tệp mới hoặc link): mọi điểm dừng `needs_recheck=true`, tệp cũ xóa sau commit và chỉ khi không bản sao nào dùng chung `storage_key`; gửi lại cùng link với thời lượng ngắn hơn chỉ đánh dấu các điểm dừng vượt thời lượng mới. Sao chép vào khóa chép cả video (dùng chung tệp) và điểm dừng.
- Khóa: nội dung (`FOR NO KEY UPDATE`) → dòng lần tải → `content_videos` (`FOR UPDATE`; bản sao dùng `FOR SHARE`) → điểm dừng; khóa khóa học đứng trước (`RemoveCourseItem`).
- Chưa làm (lát 4 trở đi): học sinh xem video (đối tượng, mở khóa tuần tự), tiến độ, trả lời điểm dừng, chặn tua, xóa vị trí xem dở khi thay video. Nợ: `B-105..B-107` (lịch dọn tải dở, khóa dòng khi ghép tệp 1 GB, kho khác `local`). Triển khai: link ký cần cấu hình trusted proxies và `APP_URL` đúng sau nginx; `client_max_body_size` cỡ phần tải (5 MB) cho route `PUT parts`.

## 14. Ghi chú hiện thực lát 4a (2026-11-27)

- **API học sinh:** `GET /me/courses/{course}` (mục kèm `status`, `locked`, `blocked_by`; `completion {complete, done, total, average_score}`), `GET /me/contents` (nội dung độc lập được giao), `GET /me/contents/{content}` (kèm `progress`, `video.checkpoints`), `PUT /me/contents/{content}/progress`, `POST /checkpoints/{checkpoint}/answers`; `GET /contents/{content}/playback` dùng được cho học sinh. Không có `GET /me/courses` (danh sách).
- **Một chỗ kiểm quyền:** `LearnerContentAccess` (`Ok`, `Hidden`, `Locked`): không thuộc đối tượng, nội dung lưu trữ, nội dung của khóa không còn mục nào trỏ tới → 404; mục bị khóa tuần tự → 403 `CONTENT_LOCKED` kèm `details.blocked_by`; không có hồ sơ học sinh → 403. Luồng phát đã ký kiểm lại quyền mỗi lần mở theo `user` trong chữ ký. Tệp đính kèm của nội dung chỉ hiện cho học sinh khi liên kết có `visibility=learner`.
- **Tiến độ:** tạo lười khi mở lần đầu. Bài giảng/tài liệu do học sinh bấm xong; video xong khi vị trí ≥ 95% thời lượng và mọi điểm dừng đúng, hoặc (link ngoài, không biết thời lượng) học sinh gửi `completed=true` và mọi điểm dừng đúng; `completed` không bao giờ bị hạ. Vị trí bị kẹp tại điểm dừng chưa đúng đầu tiên (kèm `max_allowed_position_seconds`). Thay video đặt `last_position_seconds = 0` cho mọi học sinh, giữ `completed`.
- **Điểm dừng:** lượt đầu ghi một lần; chấm bằng `AutoGrader` trên bản chụp (đúng khi `score == max_score`). Câu sắp xếp và nối cặp được **xáo thứ tự hiển thị cố định theo (học sinh, điểm dừng)** bằng HMAC với khóa bí mật của server (`content.checkpoint_order_secret`, mặc định `APP_KEY`), luôn khác thứ tự lưu (vì thứ tự lưu chính là đáp án, `DEC-LRN-041`). `explanation` để trống tới khi `B-087` xong. Điểm dừng `needs_recheck` vẫn phát cho học sinh.
- **Hoàn thành khóa (tính khi cần):** `average_score` và `completion_min_score` cùng **thang 10** (trung bình phần trăm chia 10, làm tròn 2 chữ số; chỉ kết quả đã công bố và chấm xong, mỗi bài một kết quả đại diện); khóa không có mục tính điểm thì không bao giờ hoàn thành; bằng đúng ngưỡng thì đạt.
- **Xóa và lưu trữ (quy tắc 7):** gỡ mục khỏi khóa mà nội dung đã có tiến độ → xóa dòng mục, lưu trữ nội dung (giữ tệp); xóa nội dung độc lập đã có tiến độ → 409 `CONTENT_IN_USE` (chỉ lưu trữ được).
- **Chưa làm / nợ:** khóa tuần tự chặn server cho bài đánh giá làm sau (`DEC-LRN-054`, `LearnerAssessmentLock`: mở lượt mới và xem chi tiết khi chưa có lượt; còn tệp đính kèm của đề là `B-113`); khe đua hẹp, id mục lộ thứ tự, điểm dừng quá thời lượng mới (`B-109..B-111`); lát 4b: tiến độ khóa cho Giáo viên, Admin (`GET /courses/{id}/progress`) và Phụ huynh (`GET /students/{id}/courses/{courseId}/progress`).

## 15. Ghi chú hiện thực lát 4b (2026-11-28)

- **Tiến độ khóa cho Giáo viên/Admin:** `GET /courses/{course}/progress` (`CoursePolicy::manage`: Admin hoặc chủ khóa; khóa dùng chung chỉ Admin). Trả `course`, `items` (mọi mục kèm `counts`, `completed_count`, `checkpoints` có `answered_count`, `first_try_correct_count`), `learners` (mỗi học viên một hàng: `done`, `total`, `is_complete`, `average_score` thang 10, `last_activity_at`, `statuses` chỉ cho mục đếm được) và `summary` (`learner_count`, `completed_count`, `completion_rate` luôn là số thực). Không có đáp án của học sinh, chỉ số lượt đầu đúng/sai; thống kê chỉ đếm học viên hiện tại.
- **Tiến độ của con cho Phụ huynh:** `GET /me/children/{studentId}/courses/{course}/progress`: cùng số liệu với `GET /me/courses/{course}` của chính học sinh, không có `content_id` (chỉ bài đánh giá giữ `assessment_id`), không tệp, link, nội dung. Con của người khác, con không học khóa, liên kết đã hết, khóa không tồn tại đều cùng 404.
- **Hiện thực:** không bảng mới. `CourseItemStatusesQuery::forStudents` và `StudentRepresentativeResultsQuery::handleForStudents` tính cho nhiều học sinh một lượt (số truy vấn không phụ thuộc số học viên); công thức điểm trung bình dùng chung ở `CourseAverageScore`.
- **Nợ:** `B-112` (truy vấn thừa nhỏ).

## 16. Tệp đính kèm của bài giảng (2026-11-27, `DEC-LRN-056`)

- `PUT /contents/{content}/attachments` `{attachment_ids}` (tối đa 20, có thể rỗng), quyền `LearningContentPolicy::manage`, trả như `GET /contents/{content}`. Chỉ bài giảng (loại khác 422). Tệp mới: người upload hoặc Admin, chưa gắn vào đâu, trong giới hạn `attachments.course_document` (tải bằng `POST /attachments` với `purpose=course_document`); tệp đã đính kèm sẵn được giữ.
- `attachment_links.content_usage` (`inline_image` | `attached_file`, mặc định `attached_file`) phân biệt ảnh trong `body_html` với tệp đính kèm; đồng bộ loại này không đụng loại kia; sao chép vào khóa giữ nguyên loại. Migration chuyển mọi liên kết cũ của bài giảng thành `inline_image`; phải chạy trước khi chuyển lưu lượng sang mã mới; rollback bị từ chối khi đã có bài giảng có tệp. Bỏ tệp chỉ mất liên kết (`B-099`).
- Học sinh thấy và tải tệp theo đúng quyền của bài (khóa tuần tự thì 403 `CONTENT_LOCKED`); phụ huynh không xem.

## 17. Ghi chú hiện thực FE lát 5 (2026-11-27)

- Feature `piclass-web/src/features/course-content`, phụ thuộc một chiều `academic → course-content → assessment, files`. TipTap 3.34.0 (ghim chính xác).
- Trình soạn chỉ nhận đúng bộ thẻ ở mục 6; tiêu đề cấp khác khi dán quy về h2 hoặc h3. Gửi `body_html` đã bỏ `src` của ảnh (URL ký không tính vào 200 KB).
- Ảnh vừa tải lên chưa gắn vào bài nên `GET /attachments/{id}` trả 404 cả với người upload: trình soạn hiện bản xem tạm từ tệp trên máy, lưu xong thì dùng URL ký BE trả.
- Khác mockup: mục đổi chỗ được qua tiêu đề chương, chương có nút gỡ (nếu không, chương mới không nhận được mục); gỡ mục có hộp xác nhận; tab "Nội dung" không số đếm; chưa có lựa chọn "Video" khi thêm mục (lát 6).
- Chặn rời trang khi chưa lưu: đóng hoặc tải lại tab, liên kết trong ứng dụng, nút Quay lại (mục lịch sử đệm). Mới kiểm bằng test, chưa chạy trên trình duyệt thật.
