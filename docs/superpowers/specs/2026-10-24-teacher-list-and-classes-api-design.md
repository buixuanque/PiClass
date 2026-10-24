# Danh sách giáo viên + lớp đang dạy — thiết kế (BE lát 1 Admin)

**Ngày:** 2026-10-24. **Nguồn quyết định:** bàn nghiệp vụ lát 1 (`product/screens/mockup-plan.md`, mục "1. Tạo giáo viên"); gap A7/G4, A8/GM-2 của `product/screens/api-gaps.md`; màn AD-07, AD-07a.

## Vấn đề

- `GET /users?role=teacher` chỉ trả giáo viên **đã có tài khoản**; AD-07 (danh sách giáo viên) cần thấy cả hồ sơ chưa mời. Không có endpoint danh sách hồ sơ `Teacher` (chỉ có `GET /teachers/{id}` — một bản ghi).
- AD-07a (chi tiết) và cột "Số lớp đang dạy" ở AD-07 cần "lớp đang dạy của một giáo viên"; hiện phải duyệt `GET /classes` rồi gọi `GET /classes/{id}/teachers` cho từng lớp (N+1).

## Quyết định thiết kế

1. **`GET /teachers`** (mới, chỉ-admin — cùng mẫu `GetTeacherController`, không có Policy theo đối tượng). Trả hồ sơ `Teacher` kèm liên kết tài khoản (nếu có) và số lớp đang dạy, không phụ thuộc đã có `User` hay chưa.
   - Bộ lọc: `name` (ilike theo `person_profile.full_name`), `status` (hồ sơ: `active`/`inactive`), `account` (`none`/`invited`/`active`/`inactive` — suy ra từ `User`, xem bảng dưới), `has_classes` (`true`/`false`, theo `TeacherClassAssignment.status=active`). Không validate giá trị lạ (giữ hành vi cũ của `ListUsersQuery`: giá trị lạ → rỗng, không phải 422). Phân trang `per_page` (mặc định 15), giống `ListUsersQuery`.
   - Số lớp đang dạy tính bằng `withCount` (một truy vấn, không N+1).
   - Thêm quan hệ `Teacher::user()` (`hasOne(User::class, 'person_profile_id', 'person_profile_id')`, cùng mẫu `User::teacherProfile()` đã có) để giải quyết liên kết tài khoản ↔ hồ sơ cho riêng Teacher (`GM-1`, phạm vi hẹp: chỉ Teacher, chưa làm cho Student/Parent — để dành cho lát của màn đó).

   | `account` | Điều kiện |
   |---|---|
   | `none` (chưa mời) | không có `User` |
   | `invited` (đã mời, chưa đăng nhập) | có `User`, `status=active`, `last_login_at` rỗng |
   | `active` (đang hoạt động) | có `User`, `status=active`, `last_login_at` khác rỗng |
   | `inactive` (đã vô hiệu hóa) | có `User`, `status=inactive` |

2. **`GET /teachers/{teacher}/classes`** (mới, chỉ-admin, cùng mẫu `GetTeacherController`). Trả các `TeacherClassAssignment` đang `active` của giáo viên đó, kèm tên lớp/môn — đảo chiều của `GetClassTeachersController` (lọc theo `teacher_id` thay vì `class_id`). Không thêm Policy mới vì chỉ Admin dùng ở lát này (Giáo viên tự xem lớp của mình đã có qua `GET /classes` được `ClassScope` lọc theo vai trò — không trùng lặp).

## Hợp đồng API

| Endpoint | Quyền | Thành công | Lỗi |
|---|---|---|---|
| `GET /teachers` | Admin | 200 `{data:[{id, full_name, specialty, email, phone, status, account: {login_identifier, status, last_login_at}\|null, active_classes_count}], meta:{total, current_page, last_page}}` | — |
| `GET /teachers/{teacher}/classes` | Admin | 200 `{data:[{class_id, class_name, subject_name, valid_from, valid_to}]}` | 404 không tìm thấy giáo viên |

`email`/`phone` lấy từ `person_profile`. `last_login_at` qua `AppTime::format` (ISO 8601 `+07:00`), null giữ null.

## Không làm trong lượt này

- Không sửa `GET /teachers/{id}` (AD-07a hiện tại) để thêm khối "Tài khoản" — `GM-1` ở chi tiết giáo viên để lại cho lượt sau nếu cần (không có trong 2 câu hỏi đã chốt của lát này).
- Không làm chung cho Student/Parent (A7/GM-1 phạm vi rộng hơn) — để dành cho lát Học sinh/Phụ huynh.
- Không cho vai trò Teacher gọi `GET /teachers/{teacher}/classes` (họ đã có đường khác qua `GET /classes`).
- Không thêm tìm kiếm không dấu (bỏ dấu tiếng Việt) cho `name` — giữ `ilike` như `ListUsersQuery` hiện tại; nếu cần thì là việc riêng (ghi ở `api-gaps.md` nếu phát sinh).
