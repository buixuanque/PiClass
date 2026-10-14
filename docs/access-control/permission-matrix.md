# Permission Matrix — Ma trận quyền

Ký hiệu: **✓** cho phép nếu scope và lifecycle hợp lệ; **R** chỉ đọc; **S** chỉ dữ liệu bản thân/child; **C** chỉ record thuộc scope; **—** không cấp. “Delete” không đồng nghĩa xóa vật lý; history/archive rules được giữ. Các action không có business use áp dụng ghi `—`.

| Resource | Role | View / List | Create | Update | Delete | Publish | Submit | Grade | Manage |
|---|---|---|---|---|---|---|---|---|---|
| User, role, PersonProfile | Admin | ✓ | ✓ | ✓ | — | — | — | — | ✓ |
| User, role, PersonProfile | Teacher | C read | — | own basic contact | — | — | — | — | — |
| User/PersonProfile | Student | S | — | contact only | — | — | — | — | — |
| Parent profile | Parent | own | — | own basic contact | — | — | — | — | — |
| Student profile | Parent | child | — | — | — | — | — | — | — |
| Subject | Admin | ✓ | ✓ | ✓ | — | — | — | — | ✓ |
| Subject | Teacher | C read | — | — | — | — | — | — | — |
| Class / ClassMember | Admin | ✓ | ✓ | ✓ | — | — | — | — | ✓ |
| Class / ClassMember | Teacher | assigned C / roster | — | — | — | — | — | — | assigned C learning operations |
| Class / ClassMember | Student | own membership | — | — | — | — | — | — | — |
| Class / ClassMember | Parent | child membership; no peer roster | — | — | — | — | — | — | — |
| Course / CourseEnrollment | Admin | ✓ | ✓ | ✓ | — | — | — | — | ✓ |
| Course / CourseEnrollment | Teacher | owned Course / assigned C scope | ✓ scope | ✓ scope | — | — | — | — | assigned scope |
| Course / CourseEnrollment | Student | own enrollment | — | — | — | — | — | — | — |
| Course / CourseEnrollment | Parent | child enrollment; no peer roster | — | — | — | — | — | — | — |
| Schedule / LessonSession | Admin | ✓ | ✓ | ✓ | — | — | — | — | ✓ |
| Schedule / LessonSession | Teacher | assigned C | ✓ C | ✓ C | — | — | — | — | ✓ C |
| Schedule / LessonSession | Student | own Class | — | — | — | — | — | — | — |
| Schedule / LessonSession | Parent | child Class | — | — | — | — | — | — | — |
| Attendance | Admin | ✓ | ✓ | ✓ + history/reason | — | — | — | — | ✓ |
| Attendance | Teacher | assigned C | ✓ C | ✓ C + history/reason | — | — | — | — | ✓ C |
| Attendance | Student | own | — | — | — | — | — | — | — |
| AttendanceRecord | Student | own | — | — | — | — | — | — | — |
| AttendanceRecord | Parent | child | — | — | — | — | — | — | — |
| AbsenceExplanation | Admin | ✓ | review/create decision | review status/note | — | — | — | — | ✓ all |
| AbsenceExplanation | Teacher | assigned C | review/create decision | review status/note | — | — | — | — | ✓ assigned C |
| AbsenceExplanation | Student | — | — | — | — | — | — | — | — |
| AbsenceExplanation | Parent | child | submit | — | — | — | — | — | — |
| Question Bank / Question | Admin | ✓ | ✓ | all | archive only | — | — | — | all |
| Question Bank / Question | Teacher | shared bank/use; own | ✓ | own question/version | archive own; never physically delete referenced version | — | — | — | own questions |
| Question Bank / Question | Student / Parent | — except published assessment content | — | — | — | — | — | — | — |
| Assessment / Version | Admin | ✓ | ✓ | draft; published creates version | — | ✓ | — | — | ✓; close/reopen/cancel |
| Assessment / Version | Teacher | assigned C | ✓ C | draft; published creates version | — | ✓ C | — | — | close/reopen/cancel C |
| Assessment | Student | audience eligible | — | — | — | — | — | — | — |
| Assessment | Parent | child, allowed fields | — | — | — | — | — | — | — |
| Attempt / Answer / Submission | Admin | ✓ | exception workflow only | operational only; no rewrite of submission | — | — | — | — | Exam reattempt if confirmed incident |
| Attempt / Answer / Submission | Teacher | assigned C | — | may request Assignment resubmission; cannot edit answers/submission | — | — | — | — | assigned C |
| Attempt / Answer / Submission | Student | own | eligible Attempt | own draft Answer | — | — | own eligible Attempt | — | — |
| Attempt / Answer / Submission | Parent | — unless explicitly published context | — | — | — | — | — | — | — |
| Grade / Result / Feedback | Admin | ✓ | grade/result per workflow | grade published: reason + history | — | publish/withdraw per policy | — | ✓ | ✓ |
| Grade / Result / Feedback | Teacher | assigned C | Grade/Feedback C | published grade: reason + history | — | publish configured result/feedback | — | ✓ C | ✓ C |
| Grade / Result / Feedback | Student | own published items | — | — | — | — | — | — | — |
| Grade / Result / Feedback | Parent | child items published to Parent | — | — | — | — | — | — | — |
| Notification | Admin | ✓ | event-generated only; no manual create | own read state | — | — | — | — | operations per event policy |
| Notification | Teacher | related C | generated/system | own read state | — | — | — | — | — |
| Notification | Student | received | — | own read state | — | — | — | — | — |
| Notification | Parent | received if link/context allowed | — | own read state | — | — | — | — | — |
| Attachment / File | Admin | if context allowed | valid context | metadata policy | no delete while referenced | — | — | — | — |
| Attachment / File | Teacher | assigned context | valid class/assessment/feedback context | — | no delete while referenced | — | — | — | — |
| Attachment / File | Student | own published/assigned context | own eligible File Upload | — | — | — | within own Attempt | — | — |
| Attachment / File | Parent | child context with Parent visibility only | — | — | — | — | — | — | — |
| AuditLog | Admin | all | auto-recorded by audited action | — | — | — | — | — | read all |
| AuditLog | Teacher | business history in assigned Class | auto-recorded by audited action | — | — | — | — | — | scoped read |
| AuditLog | Student / Parent | — | auto-recorded by audited action | — | — | — | — | — | — |

Action không được cấp rõ trong ma trận mặc định bị từ chối. Chỉ Admin tạo/kết thúc/thay đổi ClassMember; Teacher chỉ xem roster assigned Class. Parent không xem Submission/Answer private; chỉ xem nếu field/result/feedback/file được publish/visibility cho Parent.

## Permission nghiệp vụ khác ownership/scope

Ví dụ: Teacher có `Grade`, nhưng chỉ Submission thuộc Class được phân công; Student có `Submit`, nhưng chỉ Attempt của chính mình còn hợp lệ; Parent có `View Result`, nhưng phải có link hiện hành, result được publish và visibility phù hợp. `Manage` không vượt quyền action cụ thể hay lifecycle. Assessment publish phiên bản mới không làm thay đổi version đã cấp cho Student.
