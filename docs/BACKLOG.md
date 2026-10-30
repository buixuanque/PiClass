# BACKLOG — việc chưa làm

Mục lục, một dòng một việc, **trỏ tới nguồn chứ không chép lại nội dung**. Không đọc mặc định; `/tiep` chỉ mở file này khi `STATE.md` hết việc kế tiếp. Việc xong thì xóa dòng (chi tiết nằm ở `archive/progress-log.md`). Việc phát sinh thì thêm dòng mới ngay.

Cột **Cần**: `sẵn sàng` = làm ngay được · `user quyết` = chờ quyết định · `user làm` = user tự thao tác · `phụ thuộc X` = đợi việc khác.

Thứ tự trong mỗi nhóm = ưu tiên giảm dần (🔸 đề xuất ban đầu, chờ user rà).

## A. Cổng chính tiếp theo
| ID | Repo | Việc | Nguồn | Cần |
|---|---|---|---|---|
| B-001 | web+api | Cổng Giáo viên: list chức năng → mockup → BE → FE | `process/working-flow.md`, `product/screens/teacher-*.md` | sẵn sàng |
| B-002 | web | Shell riêng cho Teacher (không có công tắc đổi vai trò, Q6 đã giải quyết) | `requirements/decision-log.md` DEC-USR-015 | phụ thuộc B-001 |
| B-003 | web | CM-05 Hồ sơ, CM-06 Thông báo, CM-07 Trang lỗi, `GET /me/profile` (tên hiển thị) | `archive/progress-log.md` (Màn Auth) | phụ thuộc B-002 |
| B-004 | api | BE cho Teacher: B1 (lộ điểm chưa công bố), B4 (kiểm dữ liệu bài), A1, A3, A4, A12 | `research/2026-10-24-api-v1-toi-thieu.md` | phụ thuộc B-001 |
| B-005 | web+api | AD-13 cấp lại lượt làm (cần màn chi tiết bài nộp TC-13) | `product/screens/mockup-plan.md` mục 6 | phụ thuộc B-001 |
| B-006 | web+api | Cổng Học sinh | `product/screens/student-parent.md` | phụ thuộc B-001 |
| B-007 | web+api | Cổng Phụ huynh | `product/screens/student-parent.md` | phụ thuộc B-006 |
| B-008 | web | Playwright (e2e) | `piclass-infra` | sẵn sàng |

## B. Chờ quyết định
| ID | Việc | Nguồn | Cần |
|---|---|---|---|
| B-020 | `ARCH-36` (a)–(f): xác nhận UI hồ sơ kèm tài khoản dựng không mockup | `architecture/open-decisions.md` | user quyết |
| B-021 | `ARCH-14`: đồng bộ `StudentAssessment` khi học viên vào lớp/Course sau khi gắn audience | `architecture/decisions/ARCH-14.md`, known-debt D-16 | user quyết |
| B-022 | `ARCH-28(n)`: tài khoản Teacher `inactive` (`users.status`) | `architecture/open-decisions.md` | user quyết |
| B-023 | `ARCH-26` phần còn lại: chuyển trạng thái buổi không bị chặn, PATCH không kiểm `ends_at`/`effective_until`... | `architecture/open-decisions.md` | user quyết |
| B-024 | `ARCH-12` retention file/audit; backup DB (`ARCH-09`); chọn công cụ CI/CD | `architecture/open-decisions.md` | user quyết |
| B-026 | Hồ sơ Student/Parent/Teacher chuyển `inactive` không khóa tài khoản (`UpdateStudent`/`UpdatePersonProfile` không đụng `User`): người đã nghỉ vẫn đăng nhập được. Chốt có chủ ý hay cần tự khóa/nhắc Admin | `piclass-api/app/Modules/UserManagement/Actions/UpdateStudent.php`, `DEC-USR-020` | user quyết |
| B-025 | Issue #60 (phát hiện nặng đối chiếu màn↔API) và các issue #21/#32/#40–#43/#46–#56 | GitHub `piclass-api` | user quyết |

## C. Nợ nhỏ / lỗi nhỏ (làm khi chạm tới code liên quan)
| ID | Repo | Việc | Nguồn |
|---|---|---|---|
| B-040 | web | Detail dialog hiện giờ cũ ~30s sau "sửa từ nay trở đi"; `EditSessionDialogLoader` dead code | `archive/progress-log.md` mục 5b |
| B-041 | api | `PATCH /schedules/{id}` gần như vô tác dụng lên buổi đã sinh (FE không gọi, route giữ ở BE) và buổi `cancelled` không sống lại được | known-debt D-32, D-33 |
| B-042 | api | `EnrollInCourse` nhánh `class_id` là dead code; race/transaction/khóa của Class–Course link | known-debt D-26..D-29 |
| B-043 | api | `make lint` (Pint) đỏ ở ~41 file test cũ; PHPStan baseline còn nhiều dòng (số liệu cũ, cần kiểm) | known-debt D-22, D-24 |
| B-044 | api | 500 do bộ lọc không validate (`?name[]=x`, `?from=abc`, `student_id=abc`...) | known-debt D-08, D-11, D-15, D-17, D-20 |
| B-045 | api | Notification/Audit: không phân trang, không xét `valid_to`, dispatcher không transaction | known-debt D-20 (issue #53–#56) |
| B-046 | web | Minor hoãn của PR #13 (test thiếu, focus trap Dialog); 6 lỗi minor lát 6 | mô tả PR #13/#48; `archive/progress-log.md` |
| B-047 | docs | Catalog hành động audit thiếu `course_class_link.*`, `course_enrollment.relink` | `product/screens/admin.md` |
| B-048 | mockup | Nút `danger` 3.39:1, Tooltip aria, BottomNav hover, ô ngày locale `vi` | `piclass-mockup/design-system/CHANGES.md` |
| B-052 | api | Máy có PHP: chạy `vendor/bin/pint --test`, `composer analyse`, `scramble:export` để chốt số liệu D-22, D-23, D-24 và chạy thử các 500 chưa xác nhận (D-08, D-11, D-14, D-17, D-25) | known-debt (đã rà 2026-10-28, chưa chạy được) |
| B-051 | api | Comment trong `ArchitectureTest.php`/Listener còn trỏ "CLAUDE.md" cho quy ước Notification, nay nằm ở `docs/rules/defaults.md` | sửa khi chạm file |
| B-053 | web | Minor hoãn của lát 1 Giáo viên (review cuối): bỏ hết em trong lô lưu im lặng; nút "Bỏ các em này" tự style; 422 theo trường ở dialog sửa/nội dung chỉ hiện toast; ẩn nút thêm lịch khi lớp archived; TC-03 báo lỗi khi members/teachers tải lỗi; thiếu test 409 `SESSION_CANCELLED` | PR piclass-web #27, ledger `.superpowers/sdd` |
| B-049 | all | Dịch comment tiếng Anh còn lại sang tiếng Việt (chỉ file đang sửa) | `CLAUDE.md` |
| B-063 | api | `AutoGrader::gradeStructure` so `$answerPayload === $correct` nhạy thứ tự khóa với câu Nối (map): cổng Học sinh nộp đúng nhưng khác thứ tự khóa sẽ bị chấm sai; sửa trước khi làm cổng Học sinh | `app/Modules/SubmissionGrading/Domain/AutoGrader.php`, `DEC-LRN-020` |
| B-064 | web+api | File đính kèm cho câu hỏi (TC-08): `apiClient` chưa hỗ trợ multipart; cần `POST /attachments` rồi `POST /attachments/{id}/link` với phiên bản vừa lưu và gắn lại mỗi lần sửa; làm cùng `B-068` ở một lát riêng sau lát 4d (`DEC-LRN-022`) | `product/screens/teacher-bai-danh-gia.md` mục TC-08, TB-4 |
| B-065 | mockup | Mockup TC-07 còn dòng "Ô tìm đang lọc trên danh sách đã tải (chờ backend)", nay BE đã tìm không dấu; xóa dòng và bộ lọc topic nên là ô tìm (hiện FE dùng Select tối đa 20 topic) | `PiClass Giáo viên.dc.html` khối `isBank` |
| B-066 | mockup | Mockup TC-08 lệch giao diện thật: khối đáp án Điền khuyết là một danh sách chung (thật: một khối cho mỗi `___`, `DEC-LRN-021`), còn dòng cảnh báo "một chỗ trống"; gợi ý chỗ trống chỉ nên hiện ở Điền khuyết; khối lựa chọn chỉ cho Một/Nhiều đáp án | `PiClass Giáo viên.dc.html` dòng ~637, ~658 |
| B-067 | api+web | Hẹn giờ công bố điểm (`result_release_at`) và chính sách nộp trễ (`late_policy`, "không nhận bài trễ"): BE chưa nhận hai trường khi tạo/sửa bài (TB-6, Q-A); thêm vào Request/Action/test/OpenAPI rồi hai ô ở TC-10 bước 1, và nút "công bố sau" ở TC-13 | `product/screens/teacher-bai-danh-gia.md` TB-6, Q-A, `DEC-LRN-022` |
| B-068 | web | File đính kèm của **bài** (đề PDF/ảnh, bước 2 TC-10): cùng việc với `B-064` (multipart trong `apiClient`, `POST /attachments` rồi `/link` với `assessment_version_id`); làm một lát riêng sau lát 4d, nên chờ cổng Học sinh có chỗ xem file | `product/screens/teacher-bai-danh-gia.md` TC-10 bước 2, TB-4, `DEC-LRN-022` |
| B-069 | api+web | Nhân bản bài sang lớp/kỳ khác (menu dòng ở TC-09): thêm endpoint nhân bản ở BE (nguyên tử) rồi nút ở FE; không làm ghép nhiều lời gọi ở FE (N4, G14) | `product/screens/teacher-bai-danh-gia.md` TC-09, `DEC-LRN-022` |
| B-070 | web | TC-10 v2 còn thiếu so với Google Form: khung trượt soạn câu ngay trong trang (hiện chuyển sang màn soạn rồi quay lại); lệch nhỏ đã biết của FE cũ (đếm học sinh hai lần, thời lượng làm tròn phút, `returnTo` đã có `?`) đã kiểm lại khi làm lại FE (2026-10-30): còn lại khung trượt soạn câu | `docs/superpowers/plans/2026-10-29-teacher-lot4b-fe-tc10-wizard.md` |
| B-071 | web | Gắn bài tập vào buổi học (`lesson_session`) ở TC-10: spec chưa nói lấy buổi học của lớp nào khi bài giao nhiều lớp | `product/screens/teacher-bai-danh-gia.md` |
| B-072 | web | Hộp xem đáp án khi thêm câu vào bài ở bảng chọn câu (tab Câu hỏi của TC-10 v2 đã hiện câu như học sinh thấy, nhưng bảng chọn từ ngân hàng thì chưa) | `docs/superpowers/plans/2026-10-29-teacher-lot4b-fe-tc10-wizard.md` |
| B-073 | be, web | Chuyển ba cờ hiện đáp án đúng, lời giải, điểm từng câu (`show_answers`, `show_explanations`, `show_question_scores`) từ lúc soạn bài sang lúc công bố điểm, đúng `BR-ASSESS-025` (user chốt 2026-10-30; bài thi cần mở đáp án sau khi cả lớp thi xong). Mockup TC-10 v2 đã bỏ ba công tắc khỏi Cài đặt; làm cùng lát 4c (TC-13): thêm vào hộp công bố điểm, sửa BE (cờ theo lần công bố), spec TC-10/TC-13, màn kết quả học sinh/phụ huynh; mục "Học sinh được xem" ở tổng quan bài (TC-11) cũng đổi theo | `requirements/assessment-rules.md` `BR-ASSESS-025` |

## D. Sau V1 / hỏi giáo viên rồi mới quyết
| ID | Việc | Nguồn |
|---|---|---|
| B-060 | Chức năng tạm bỏ (BE giữ nguyên, chưa làm UI) | `research/2026-10-24-api-v1-toi-thieu.md` mục 3 |
| B-061 | Nhập học sinh hàng loạt; thông báo ngoài ứng dụng; nhiều trung tâm dùng chung một DB | `architecture/open-decisions.md` `ARCH-33` |
| B-062 | Mở rộng `Course` thành khóa học online: B = mục nội dung nhiều loại (bài giảng chữ, tài liệu, link video ngoài, bài tập, test); C = video tự lưu/tương tác, tiến độ từng bài, mở khóa tuần tự. Làm sau khi đủ khung chức năng 4 vai trò; phải sửa `BR-COURSE-002` và `BR-SYS-002` trước | `requirements/learning-rules.md` BR-COURSE-002 |
