# Liên kết Class–Course (BE) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Chuyển ghi danh khóa học "theo lớp" từ thao tác một lần sang liên kết sống Class↔Course (nhiều–nhiều), với đồng bộ tự động `course_enrollments` khi thành viên lớp vào/rời, và khi lớp bị lưu trữ.

**Architecture:** Bảng mới `course_class_links` (Academic) + cột `source_class_link_id` trên `course_enrollments`. Hai Action mới `LinkClassToCourse`/`UnlinkClassFromCourse` (Academic) quản lý vòng đời liên kết. Một trait dùng chung (`Concerns\SyncsCourseEnrollmentsForClassLink`) chứa logic tạo/kết-thúc-hoặc-chuyển-nguồn ghi danh theo "quy tắc đủ điều kiện" (entitlement), được 4 Action Academic dùng: `LinkClassToCourse`, `UnlinkClassFromCourse`, và hai Action đồng bộ theo sự kiện thành viên lớp (`SyncCourseEnrollmentsOnClassMemberLeft`/`...Joined`) — các Action này được gọi **trực tiếp qua DI** (không qua Event) từ `UserManagement\Actions\EndClassMember`/`AddClassMember`, cùng transaction. Lớp bị lưu trữ xử lý trong `Academic\Actions\UpdateClass` (cùng module, gọi `SyncCourseEnrollmentsOnClassArchived` nội bộ).

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL, PHPUnit 12 — theo `piclass-api/CLAUDE.md`.

**Spec:** `docs/superpowers/specs/2026-10-26-course-class-enrollment-link-design.md`

## Global Constraints

- Không dùng Event/Listener cho luồng này (quy ước chỉ dành Event cho Notification) — UserManagement gọi thẳng Action của Academic qua constructor DI; chỉ một chiều (UserManagement → Academic), không có chiều ngược nên không vi phạm "không vòng gọi Action giữa hai module".
- Giữ nguyên unique partial index hiện có `course_enrollments_course_student_active_unique` (course_id, student_id) WHERE status='active' — không có học sinh nào có 2 `course_enrollments` active cùng lúc cho 1 Course dù qua nguồn nào.
- Không update đè bản ghi `course_enrollments`/`course_class_links` khi kết thúc rồi phát sinh lại — luôn tạo dòng mới, dòng cũ giữ nguyên làm lịch sử (đúng pattern `status` + `valid_from`/`valid_to`/`linked_at`/`unlinked_at` đã có).
- Không cho "Kết thúc ghi danh" thủ công (`EndCourseEnrollment`) trên một `course_enrollments` có `source_class_link_id` khác null.
- Quy tắc đủ điều kiện (entitlement): trước khi kết thúc một enrollment nguồn-từ-lớp (do rời lớp, gỡ liên kết, hoặc lớp bị lưu trữ), luôn kiểm tra học sinh có còn active ở một lớp khác (status lớp khác `archived`) đang có liên kết active khác tới cùng Course không — nếu có thì **chuyển `source_class_link_id`** sang liên kết đó thay vì kết thúc.
- Mọi Action ghi ≥2 bảng hoặc có audit bắt buộc chạy trong `DB::transaction`, audit ghi cùng transaction (`DEC-AUD-005`).
- Enum dùng `->value`, không literal chuỗi trùng giá trị Enum (R8).
- Thời gian hiển thị/so sánh qua `App\Support\AppTime`, không gọi `Carbon`/`now()->toIso...` trực tiếp ngoài chỗ đã có.
- Comment code, mô tả test: tiếng Việt. Tên class/method/test method, commit message: tiếng Anh.
- Route mới nằm trong nhóm `role:admin,teacher` của `routes/api.php` (cùng nhóm với `POST /courses/{course}/enrollments`), `authorize()` dùng `Gate::inspect('manage', $this->route('course'))` — không Policy riêng cho `CourseClassLink`.
- Mỗi task thêm/sửa route hoặc Resource: chạy lại `docker compose exec api php artisan scramble:export` (repo `piclass-infra/local`) trước khi coi task xong.
- **Không xóa nhánh `class_id` cũ của `EnrollInCourse`/`EnrollInCourseRequest`** trong plan này — `piclass-web` (đã merge `main`) vẫn gọi nhánh này qua dialog "Ghi danh > Theo lớp"; xóa ngay sẽ phá FE đang chạy. Việc dọn nhánh này chuyển sang khi plan FE thay thế dialog đó; ghi một dòng vào `docs/architecture/known-debt.md` ở Task 2 để không quên.

## Review Focus

- Học sinh thuộc 2 lớp cùng gắn chung 1 Course, rời 1 trong 2 lớp → phải vẫn giữ ghi danh (chuyển nguồn sang lớp còn lại), không bị kết thúc oan.
- Một cặp Class–Course đã từng gắn rồi gỡ (`unlinked`) — gắn lại lần hai phải tạo được liên kết mới (dòng cũ không chặn unique index vì nó không còn `active`), không bị lỗi trùng.
- Gắn một Course vào một Class đã `archived` phải bị từ chối (422 `CLASS_ARCHIVED`), không âm thầm tạo liên kết trên lớp đã đóng.
- Gọi thẳng API `DELETE /courses/{course}/enrollments/{studentId}` (bỏ qua UI) trên một dòng ghi danh có `source_class_link_id` phải bị chặn 409, không lọt qua vì FE chỉ ẩn nút chứ không đảm bảo an toàn.
- Gắn một Class vào Course khi toàn bộ thành viên lớp đã có ghi danh cá nhân từ trước (0 dòng mới cần tạo) — `LinkClassToCourse` vẫn phải tạo được liên kết thành công (trả 201/resource liên kết), không báo lỗi chỉ vì không có ai để ghi danh thêm.

---

## Task 1: Schema và Model cho `course_class_links`

**Files:**
- Create: `database/migrations/2026_10_26_000001_create_course_class_links_table.php`
- Create: `database/migrations/2026_10_26_000002_add_source_class_link_id_to_course_enrollments_table.php`
- Create: `app/Modules/Academic/Enums/CourseClassLinkStatus.php`
- Create: `app/Models/CourseClassLink.php`
- Create: `database/factories/CourseClassLinkFactory.php`
- Modify: `app/Models/CourseEnrollment.php` (thêm quan hệ `sourceClassLink()` và cột fillable)
- Modify: `app/Models/Course.php` (thêm quan hệ `classLinks()`)
- Modify: `app/Models/SchoolClass.php` (thêm quan hệ `courseLinks()`)
- Modify: `docs/architecture/model-ownership.md`
- Test: `tests/Feature/Academic/CourseClassLinkTest.php`

**Interfaces:**
- Produces: `App\Models\CourseClassLink` — cột `id, course_id, class_id, status (CourseClassLinkStatus), linked_by_user_id, linked_at, unlinked_by_user_id, unlinked_at`; quan hệ `course(): BelongsTo`, `class(): BelongsTo` (khóa ngoài `class_id` → `classes`, model `SchoolClass`), factory `CourseClassLink::factory()` (mặc định `status => active`, `linked_at => now()`, `linked_by_user_id => User::factory()`).
- Produces: `CourseEnrollment::sourceClassLink(): BelongsTo` (null khi ghi danh cá nhân); cột `source_class_link_id` nullable trong `$fillable`.
- Produces: unique partial index `course_class_links_course_class_active_unique` trên `(course_id, class_id) WHERE status = 'active'`.

- [ ] **Step 1: Viết test thất bại cho unique partial index**

```php
public function test_database_rejects_a_second_active_link_for_the_same_course_and_class(): void
{
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    CourseClassLink::factory()->create(['course_id' => $course->id, 'class_id' => $class->id, 'status' => 'active']);

    $this->expectException(\Illuminate\Database\QueryException::class);

    CourseClassLink::factory()->create(['course_id' => $course->id, 'class_id' => $class->id, 'status' => 'active']);
}

public function test_a_previously_unlinked_pair_can_be_linked_again(): void
{
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    CourseClassLink::factory()->create(['course_id' => $course->id, 'class_id' => $class->id, 'status' => 'unlinked']);

    $link = CourseClassLink::factory()->create(['course_id' => $course->id, 'class_id' => $class->id, 'status' => 'active']);

    $this->assertDatabaseHas('course_class_links', ['id' => $link->id, 'status' => 'active']);
}
```

- [ ] **Step 2: Chạy test, xác nhận lỗi vì model/bảng chưa tồn tại**

Run: `docker compose exec api php artisan test --filter=CourseClassLinkTest`
Expected: FAIL (class `CourseClassLink` không tồn tại / bảng không tồn tại)

- [ ] **Step 3: Viết migration, Enum, Model, Factory**

`create_course_class_links_table`: theo đúng cột ở Interfaces, unique partial index đặt bằng `DB::statement` (theo đúng mẫu ở `2026_10_14_000009_create_course_enrollments_table.php`), `foreignUuid('course_id')->constrained('courses')->cascadeOnDelete()`, `foreignUuid('class_id')->constrained('classes')->cascadeOnDelete()`, `foreignUuid('linked_by_user_id')->constrained('users')`, `foreignUuid('unlinked_by_user_id')->nullable()->constrained('users')->nullOnDelete()`.

`add_source_class_link_id_to_course_enrollments_table`: `$table->foreignUuid('source_class_link_id')->nullable()->after('student_id')->constrained('course_class_links')->nullOnDelete();`.

`CourseClassLinkStatus`: enum `Active = 'active'`, `Unlinked = 'unlinked'`, docblock tiếng Việt (mẫu theo `CourseEnrollmentStatus`).

`CourseClassLink` model: `use HasFactory, HasUuids, SerializesAppTime;`, `$fillable` đủ các cột, `$casts = ['status' => CourseClassLinkStatus::class, 'linked_at' => 'datetime', 'unlinked_at' => 'datetime']`, `course(): BelongsTo`, `class(): BelongsTo` (method tên `class()` — chú ý `class` là từ khóa PHP nên gọi được bình thường vì đây là tên method, không phải định danh class).

Modify `CourseEnrollment`: thêm `'source_class_link_id'` vào `$fillable`, thêm method `sourceClassLink(): BelongsTo { return $this->belongsTo(CourseClassLink::class); }`.

Modify `Course`: thêm `classLinks(): HasMany { return $this->hasMany(CourseClassLink::class); }`.

Modify `SchoolClass`: thêm `courseLinks(): HasMany { return $this->hasMany(CourseClassLink::class, 'class_id'); }`.

`CourseClassLinkFactory`: theo mẫu `CourseEnrollmentFactory`, `definition()` trả `course_id => Course::factory(), class_id => SchoolClass::factory(), status => 'active', linked_by_user_id => User::factory(), linked_at => now()`.

Cập nhật `docs/architecture/model-ownership.md`: thêm dòng `CourseClassLink | Academic | Chủ ghi: Academic — LinkClassToCourse, UnlinkClassFromCourse. Chỉ đọc: không module nào khác.` ngay dưới dòng `CourseEnrollment`; sửa dòng `CourseEnrollment` thành `Chủ ghi: Academic — EnrollInCourse, EndCourseEnrollment, LinkClassToCourse, UnlinkClassFromCourse, SyncCourseEnrollmentsOnClassMemberLeft/Joined/OnClassArchived.`

- [ ] **Step 4: Chạy migration và test, xác nhận pass**

Run: `docker compose exec api php artisan migrate && docker compose exec api php artisan test --filter=CourseClassLinkTest`
Expected: PASS (2 test)

- [ ] **Step 5: Commit**

```bash
git add database/migrations database/factories/CourseClassLinkFactory.php app/Models/CourseClassLink.php app/Models/CourseEnrollment.php app/Models/Course.php app/Models/SchoolClass.php app/Modules/Academic/Enums/CourseClassLinkStatus.php tests/Feature/Academic/CourseClassLinkTest.php ../docs/architecture/model-ownership.md
git commit -m "feat(academic): add course_class_links table and model"
```

---

## Task 2: `LinkClassToCourse` + liệt kê liên kết

**Files:**
- Create: `app/Modules/Academic/Exceptions/ClassArchived.php`
- Create: `app/Modules/Academic/Exceptions/AlreadyLinked.php`
- Create: `app/Modules/Academic/Actions/Concerns/SyncsCourseEnrollmentsForClassLink.php`
- Create: `app/Modules/Academic/Actions/LinkClassToCourse.php`
- Create: `app/Modules/Academic/Http/Requests/LinkClassToCourseRequest.php`
- Create: `app/Modules/Academic/Http/Requests/ListCourseClassLinksRequest.php`
- Create: `app/Modules/Academic/Http/Resources/CourseClassLinkResource.php`
- Create: `app/Modules/Academic/Http/Controllers/LinkClassToCourseController.php`
- Create: `app/Modules/Academic/Http/Controllers/ListCourseClassLinksController.php`
- Modify: `app/Modules/Academic/Http/Resources/CourseEnrollmentListResource.php`
- Modify: `app/Modules/Academic/Queries/ListCourseEnrollmentsQuery.php`
- Modify: `routes/api.php`
- Modify: `docs/architecture/error-codes.md`
- Modify: `docs/architecture/known-debt.md`
- Test: `tests/Feature/Academic/LinkClassToCourseTest.php`

**Interfaces:**
- Consumes: `CourseClassLink` model (Task 1).
- Produces: trait `SyncsCourseEnrollmentsForClassLink` với `protected function enrollEligibleMembers(CourseClassLink $link, iterable $studentIds, User $actor): void` — dùng bởi Task 2 và Task 5.
- Produces: `LinkClassToCourse::handle(Course $course, SchoolClass $class, User $actor): CourseClassLink`.
- Produces: route `POST /courses/{course}/class-links` body `{class_id}`, trả `CourseClassLinkResource` (201). Route `GET /courses/{course}/class-links` trả collection `CourseClassLinkResource`.
- Produces: `CourseEnrollmentListResource` thêm field `enrolled_via_class_id`/`enrolled_via_class_name` (null nếu ghi danh cá nhân) — Task sau dùng lại nguyên trạng, không đổi thêm.

- [ ] **Step 1: Viết test thất bại cho Action + endpoint**

```php
public function test_admin_can_link_a_class_to_a_course_and_it_enrolls_active_members(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $enrolledAlready = Student::factory()->create();
    $newMember = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $enrolledAlready->id]);
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $newMember->id]);
    CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $enrolledAlready->id]); // ghi danh cá nhân có sẵn

    $response = $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $response->assertCreated();
    $this->assertDatabaseHas('course_class_links', ['course_id' => $course->id, 'class_id' => $class->id, 'status' => 'active']);
    $newEnrollment = CourseEnrollment::where('student_id', $newMember->id)->where('course_id', $course->id)->sole();
    $this->assertNotNull($newEnrollment->source_class_link_id);
    $oldEnrollment = CourseEnrollment::where('student_id', $enrolledAlready->id)->where('course_id', $course->id)->sole();
    $this->assertNull($oldEnrollment->source_class_link_id); // không đổi nguồn ghi danh cá nhân có sẵn
}

public function test_linking_an_archived_course_is_rejected(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create(['status' => 'archived']);
    $class = SchoolClass::factory()->create();

    $response = $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $response->assertStatus(422)->assertJsonPath('error.code', 'COURSE_ARCHIVED');
}

public function test_linking_an_archived_class_is_rejected(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create(['status' => 'archived']);

    $response = $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $response->assertStatus(422)->assertJsonPath('error.code', 'CLASS_ARCHIVED');
}

public function test_linking_an_already_actively_linked_pair_is_rejected(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    CourseClassLink::factory()->create(['course_id' => $course->id, 'class_id' => $class->id]);

    $response = $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $response->assertStatus(409)->assertJsonPath('error.code', 'ALREADY_LINKED');
}

public function test_enrollments_list_shows_enrolled_via_class_name(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create(['name' => 'Toán 9A']);
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $response = $this->getJson("/api/v1/courses/{$course->id}/enrollments");

    $response->assertJsonFragment(['student_id' => $student->id, 'enrolled_via_class_name' => 'Toán 9A']);
}
```

Dùng helper `actingAsAdmin()` nếu đã có trong `TestCase`/test khác của Academic; nếu chưa có, thêm helper riêng trong `LinkClassToCourseTest` theo đúng mẫu `actingAsTeacher()` của `CourseEnrollmentTest.php` (role `admin`).

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `docker compose exec api php artisan test --filter=LinkClassToCourseTest`
Expected: FAIL (route 404 / class không tồn tại)

- [ ] **Step 3: Viết 2 exception mới**

`ClassArchived` (`app/Modules/Academic/Exceptions/ClassArchived.php`): `errorCode = 'CLASS_ARCHIVED'`, `status = 422`, message mặc định `'Lớp đã lưu trữ, không thể gắn khóa học.'` — theo đúng mẫu `CourseArchived`.

`AlreadyLinked` (`app/Modules/Academic/Exceptions/AlreadyLinked.php`): `errorCode = 'ALREADY_LINKED'` (dùng lại mã đã có ở UserManagement, theo đúng quy ước "cùng điều kiện thì dùng lại mã cũ"), `status = 409`, message `'Lớp này đã gắn với khóa học rồi.'`.

- [ ] **Step 4: Viết trait `SyncsCourseEnrollmentsForClassLink::enrollEligibleMembers()`**

```php
trait SyncsCourseEnrollmentsForClassLink
{
    /** @param iterable<string> $studentIds */
    protected function enrollEligibleMembers(CourseClassLink $link, iterable $studentIds, User $actor): void
    {
        $alreadyEnrolled = CourseEnrollment::where('course_id', $link->course_id)
            ->where('status', CourseEnrollmentStatus::Active->value)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        foreach (array_diff([...$studentIds], $alreadyEnrolled) as $studentId) {
            $enrollment = CourseEnrollment::create([
                'course_id' => $link->course_id,
                'student_id' => $studentId,
                'source_class_link_id' => $link->id,
                'status' => CourseEnrollmentStatus::Active,
                'valid_from' => now(),
                'enrolled_by_user_id' => $actor->id,
            ]);

            $this->audit->record(
                actor: $actor,
                action: 'course_enrollment.enroll',
                entityType: 'CourseEnrollment',
                entityId: $enrollment->id,
                after: ['course_id' => $link->course_id, 'student_id' => $studentId, 'source_class_link_id' => $link->id],
                correlationKey: $link->course_id,
            );
        }
    }
}
```

Trait giả định class dùng nó có property `AuditLogger $audit` (constructor promotion cùng tên). Đặt ở `app/Modules/Academic/Actions/Concerns/`.

- [ ] **Step 5: Viết `LinkClassToCourse::handle()`**

```php
final class LinkClassToCourse
{
    use SyncsCourseEnrollmentsForClassLink;

    public function __construct(private AuditLogger $audit) {}

    public function handle(Course $course, SchoolClass $class, User $actor): CourseClassLink
    {
        throw_if($course->status === CourseStatus::Archived, new CourseArchived);
        throw_if($class->status === ClassStatus::Archived, new ClassArchived);

        return DB::transaction(function () use ($course, $class, $actor) {
            try {
                $link = CourseClassLink::create([
                    'course_id' => $course->id,
                    'class_id' => $class->id,
                    'status' => CourseClassLinkStatus::Active,
                    'linked_by_user_id' => $actor->id,
                    'linked_at' => now(),
                ]);
            } catch (QueryException $e) {
                throw str_contains($e->getMessage(), 'course_class_links_course_class_active_unique') ? new AlreadyLinked : $e;
            }

            $this->audit->record(actor: $actor, action: 'course_class_link.link', entityType: 'CourseClassLink', entityId: $link->id,
                after: ['course_id' => $course->id, 'class_id' => $class->id], correlationKey: $course->id);

            $studentIds = ClassMember::where('class_id', $class->id)->where('status', MembershipStatus::Active->value)->pluck('student_id');
            $this->enrollEligibleMembers($link, $studentIds, $actor);

            return $link;
        });
    }
}
```

Docblock tiếng Việt mô tả hành vi (theo mẫu `EnrollInCourse`), trích `BR` nếu spec/`docs/requirements` đã có mã liên quan — nếu chưa có thì bỏ qua trích dẫn, không tự bịa mã BR mới.

- [ ] **Step 6: Viết `LinkClassToCourseRequest`, `ListCourseClassLinksRequest`, `CourseClassLinkResource`, hai Controller, route**

`LinkClassToCourseRequest`: `authorize()` → `Gate::inspect('manage', $this->route('course'))`; `rules()` → `['class_id' => ['required', 'uuid', 'exists:classes,id']]`.

`ListCourseClassLinksRequest`: cùng `authorize()`; `rules()` → `[]`.

`CourseClassLinkResource`: `{ id, class_id, class_name: $this->resource->class->name, linked_at: AppTime::format(...) }`.

`LinkClassToCourseController::__invoke(LinkClassToCourseRequest $request, Course $course, LinkClassToCourse $action)`: resolve `$class = SchoolClass::findOrFail($request->validated('class_id'))`, gọi `$action->handle($course, $class, $request->user())`, trả `new CourseClassLinkResource($link)`.

`ListCourseClassLinksController::__invoke(ListCourseClassLinksRequest $request, Course $course)`: `CourseClassLinkResource::collection($course->classLinks()->with('class')->where('status', 'active')->get())`.

Route (`routes/api.php`, trong nhóm `role:admin,teacher` cạnh `enrollments`):
```php
Route::post('/courses/{course}/class-links', LinkClassToCourseController::class);
Route::get('/courses/{course}/class-links', ListCourseClassLinksController::class);
```

- [ ] **Step 7: Mở rộng `CourseEnrollmentListResource` và `ListCourseEnrollmentsQuery`**

`ListCourseEnrollmentsQuery::handle()`: đổi `->with('student.personProfile')` thành `->with(['student.personProfile', 'sourceClassLink.class'])`.

`CourseEnrollmentListResource::toArray()`: thêm hai khóa
```php
'enrolled_via_class_id' => $this->resource->sourceClassLink?->class_id,
'enrolled_via_class_name' => $this->resource->sourceClassLink?->class?->name,
```
Cập nhật docblock của Resource (liệt kê 2 field mới).

- [ ] **Step 8: Ghi `error-codes.md` và `known-debt.md`**

Thêm 2 dòng vào `docs/architecture/error-codes.md` (theo đúng format bảng hiện có):
- `ALREADY_LINKED | 409 | Academic | Gắn lớp vào khóa học khi cặp (course, class) đã có liên kết đang hoạt động (POST /courses/{course}/class-links).`
- `CLASS_ARCHIVED | 422 | Academic | Gắn một lớp đã lưu trữ vào khóa học.`

Thêm 1 dòng vào `docs/architecture/known-debt.md`: `EnrollInCourse/EnrollInCourseRequest vẫn còn nhánh ghi danh theo class_id (một lần, không phải liên kết sống) — đã bị thay thế về nghiệp vụ bởi LinkClassToCourse nhưng chưa xóa vì piclass-web (dialog Ghi danh > Theo lớp) còn gọi tới | app/Modules/Academic/{Actions/EnrollInCourse.php, Http/Requests/EnrollInCourseRequest.php} | Khi plan FE thay dialog "Theo lớp" bằng UI gắn/gỡ lớp mới (xem docs/superpowers/specs/2026-10-26-course-class-enrollment-link-design.md)`.

- [ ] **Step 9: Chạy test, xác nhận pass**

Run: `docker compose exec api php artisan test --filter=LinkClassToCourseTest`
Expected: PASS (5 test)

- [ ] **Step 10: Xuất lại OpenAPI**

Run: `docker compose exec api php artisan scramble:export`
Expected: chạy không lỗi (cảnh báo "Cannot infer the resource model" cũ bỏ qua được)

- [ ] **Step 11: Commit**

```bash
git add app/Modules/Academic docs/architecture/error-codes.md docs/architecture/known-debt.md routes/api.php tests/Feature/Academic/LinkClassToCourseTest.php
git commit -m "feat(academic): add LinkClassToCourse action and class-links endpoints"
```

---

## Task 3: `UnlinkClassFromCourse` + quy tắc đủ điều kiện khi gỡ

**Files:**
- Create: `app/Modules/Academic/Http/Controllers/UnlinkClassFromCourseController.php`
- Create: `app/Modules/Academic/Http/Requests/UnlinkClassFromCourseRequest.php`
- Create: `app/Modules/Academic/Actions/UnlinkClassFromCourse.php`
- Modify: `app/Modules/Academic/Actions/Concerns/SyncsCourseEnrollmentsForClassLink.php` (thêm method)
- Modify: `routes/api.php`
- Test: `tests/Feature/Academic/UnlinkClassFromCourseTest.php`

**Interfaces:**
- Consumes: trait `SyncsCourseEnrollmentsForClassLink::enrollEligibleMembers()` (Task 2), model `CourseClassLink` (Task 1).
- Produces: trait method `protected function endOrRepointEnrollments(CourseClassLink $link, iterable $studentIds, string $endReasonTemplate, User $actor): void` — dùng bởi Task 3, 4, 6.
- Produces: `UnlinkClassFromCourse::handle(CourseClassLink $link, User $actor): void`.
- Produces: route `DELETE /courses/{course}/class-links/{classId}` → 204.

- [ ] **Step 1: Viết test thất bại**

```php
public function test_unlinking_ends_enrollments_sourced_from_that_link(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $response = $this->deleteJson("/api/v1/courses/{$course->id}/class-links/{$class->id}");

    $response->assertNoContent();
    $this->assertDatabaseHas('course_class_links', ['course_id' => $course->id, 'class_id' => $class->id, 'status' => 'unlinked']);
    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'ended']);
}

public function test_unlinking_one_of_two_links_keeps_enrollment_repointed_to_the_other(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $classA = SchoolClass::factory()->create();
    $classB = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $classA->id, 'student_id' => $student->id]);
    ClassMember::factory()->create(['class_id' => $classB->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $classA->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $classB->id]);
    $linkB = CourseClassLink::where('class_id', $classB->id)->sole();

    $this->deleteJson("/api/v1/courses/{$course->id}/class-links/{$classA->id}");

    $enrollment = CourseEnrollment::where('course_id', $course->id)->where('student_id', $student->id)->sole();
    $this->assertSame('active', $enrollment->status->value);
    $this->assertSame($linkB->id, $enrollment->source_class_link_id);
}

public function test_unlinking_does_not_touch_individually_sourced_enrollments(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $individualStudent = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $individualStudent->id]);
    CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $individualStudent->id]); // ghi danh cá nhân, trước khi gắn lớp
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $this->deleteJson("/api/v1/courses/{$course->id}/class-links/{$class->id}");

    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $individualStudent->id, 'status' => 'active']);
}

public function test_unlinking_an_already_unlinked_pair_returns_404(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();

    $response = $this->deleteJson("/api/v1/courses/{$course->id}/class-links/{$class->id}");

    $response->assertNotFound();
}
```

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `docker compose exec api php artisan test --filter=UnlinkClassFromCourseTest`
Expected: FAIL

- [ ] **Step 3: Thêm `endOrRepointEnrollments()` vào trait**

```php
/** @param iterable<string> $studentIds */
protected function endOrRepointEnrollments(CourseClassLink $link, iterable $studentIds, string $endReasonTemplate, User $actor): void
{
    foreach ($studentIds as $studentId) {
        $enrollment = CourseEnrollment::where('course_id', $link->course_id)
            ->where('student_id', $studentId)
            ->where('status', CourseEnrollmentStatus::Active->value)
            ->where('source_class_link_id', $link->id)
            ->lockForUpdate()
            ->first();

        if (! $enrollment) {
            continue;
        }

        $alternativeLink = CourseClassLink::where('course_id', $link->course_id)
            ->where('id', '!=', $link->id)
            ->where('status', CourseClassLinkStatus::Active->value)
            ->whereHas('class', fn ($q) => $q->where('status', '!=', ClassStatus::Archived->value))
            ->whereHas('class.classMembers', fn ($q) => $q->where('student_id', $studentId)->where('status', MembershipStatus::Active->value))
            ->first();

        if ($alternativeLink) {
            $enrollment->update(['source_class_link_id' => $alternativeLink->id]);
            $this->audit->record(actor: $actor, action: 'course_enrollment.relink', entityType: 'CourseEnrollment', entityId: $enrollment->id,
                before: ['source_class_link_id' => $link->id], after: ['source_class_link_id' => $alternativeLink->id], correlationKey: $link->course_id);

            continue;
        }

        $enrollment->update(['status' => CourseEnrollmentStatus::Ended, 'valid_to' => now(), 'ended_by_user_id' => $actor->id, 'end_reason' => $endReasonTemplate]);
        $this->audit->record(actor: $actor, action: 'course_enrollment.end', entityType: 'CourseEnrollment', entityId: $enrollment->id,
            before: ['status' => CourseEnrollmentStatus::Active->value], after: ['status' => CourseEnrollmentStatus::Ended->value],
            reason: $endReasonTemplate, correlationKey: $link->course_id);
    }
}
```

`$endReasonTemplate` truyền sẵn chuỗi đã nội suy tên lớp (gọi nơi dùng tự build, ví dụ `"Gỡ khóa khỏi lớp {$class->name}"`) — trait không tự truy `$link->class` để tránh query thừa khi nơi gọi đã có sẵn.

- [ ] **Step 4: Viết `UnlinkClassFromCourse::handle()`, Request, Controller, route**

```php
final class UnlinkClassFromCourse
{
    use SyncsCourseEnrollmentsForClassLink;

    public function __construct(private AuditLogger $audit) {}

    public function handle(CourseClassLink $link, User $actor): void
    {
        DB::transaction(function () use ($link, $actor) {
            $link->update(['status' => CourseClassLinkStatus::Unlinked, 'unlinked_by_user_id' => $actor->id, 'unlinked_at' => now()]);
            $this->audit->record(actor: $actor, action: 'course_class_link.unlink', entityType: 'CourseClassLink', entityId: $link->id,
                after: ['status' => CourseClassLinkStatus::Unlinked->value], correlationKey: $link->course_id);

            $studentIds = ClassMember::where('class_id', $link->class_id)->where('status', MembershipStatus::Active->value)->pluck('student_id');
            $this->endOrRepointEnrollments($link, $studentIds, "Gỡ khóa khỏi lớp {$link->class->name}", $actor);
        });
    }
}
```

`UnlinkClassFromCourseRequest`: `authorize()` → `Gate::inspect('manage', $this->route('course'))`; `rules()` → `[]`.

`UnlinkClassFromCourseController::__invoke(UnlinkClassFromCourseRequest $request, Course $course, string $classId, UnlinkClassFromCourse $action)`: `$link = CourseClassLink::where('course_id', $course->id)->where('class_id', $classId)->where('status', 'active')->firstOrFail();` rồi `$action->handle($link, $request->user());` trả `response()->noContent()`.

Route: `Route::delete('/courses/{course}/class-links/{classId}', UnlinkClassFromCourseController::class);` (cùng nhóm `role:admin,teacher`).

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `docker compose exec api php artisan test --filter=UnlinkClassFromCourseTest`
Expected: PASS (4 test)

- [ ] **Step 6: Xuất lại OpenAPI**

Run: `docker compose exec api php artisan scramble:export`

- [ ] **Step 7: Commit**

```bash
git add app/Modules/Academic routes/api.php tests/Feature/Academic/UnlinkClassFromCourseTest.php
git commit -m "feat(academic): add UnlinkClassFromCourse with entitlement-aware ending"
```

---

## Task 4: Đồng bộ khi học sinh rời lớp

**Files:**
- Create: `app/Modules/Academic/Actions/SyncCourseEnrollmentsOnClassMemberLeft.php`
- Modify: `app/Modules/UserManagement/Actions/EndClassMember.php`
- Create: `tests/Feature/Academic/CourseEnrollmentClassSyncTest.php` (test Academic sống trong thư mục Academic dù trigger HTTP đi qua route của UserManagement; Task 5 và 6 mở rộng file này)

**Interfaces:**
- Consumes: trait `endOrRepointEnrollments()` (Task 3).
- Produces: `SyncCourseEnrollmentsOnClassMemberLeft::handle(ClassMember $member, User $actor): void`, gọi bởi `EndClassMember` ngay sau khi cập nhật trạng thái thành viên, trong cùng transaction.

- [ ] **Step 1: Viết test thất bại**

```php
namespace Tests\Feature\Academic;
// ... use tương tự LinkClassToCourseTest, thêm EndClassMemberController helper gọi qua HTTP DELETE /classes/{class}/members/{studentId}

public function test_leaving_a_class_ends_the_enrollment_sourced_from_its_link(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $this->deleteJson("/api/v1/classes/{$class->id}/members/{$student->id}", ['end_reason' => 'Nghỉ học']);

    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'ended']);
}

public function test_leaving_a_class_does_not_end_an_individually_sourced_enrollment(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);
    CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]); // cá nhân, không qua link

    $this->deleteJson("/api/v1/classes/{$class->id}/members/{$student->id}", ['end_reason' => 'Nghỉ học']);

    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active']);
}

public function test_leaving_one_of_two_linked_classes_keeps_enrollment_via_the_remaining_class(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $classA = SchoolClass::factory()->create();
    $classB = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $classA->id, 'student_id' => $student->id]);
    ClassMember::factory()->create(['class_id' => $classB->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $classA->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $classB->id]);
    $linkB = CourseClassLink::where('class_id', $classB->id)->sole();

    $this->deleteJson("/api/v1/classes/{$classA->id}/members/{$student->id}", ['end_reason' => 'Chuyển lớp']);

    $enrollment = CourseEnrollment::where('course_id', $course->id)->where('student_id', $student->id)->sole();
    $this->assertSame('active', $enrollment->status->value);
    $this->assertSame($linkB->id, $enrollment->source_class_link_id);
}
```

Kiểm tra route thật của `EndClassMember` trước khi viết test (đọc `EndClassMemberController`/`routes/api.php` — phần trên chỉ là phỏng đoán `DELETE /classes/{class}/members/{studentId}`; sửa lại đúng path/verb/body nếu khác).

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentClassSyncTest`
Expected: FAIL

- [ ] **Step 3: Viết `SyncCourseEnrollmentsOnClassMemberLeft::handle()`**

```php
final class SyncCourseEnrollmentsOnClassMemberLeft
{
    use SyncsCourseEnrollmentsForClassLink;

    public function __construct(private AuditLogger $audit) {}

    public function handle(ClassMember $member, User $actor): void
    {
        $links = CourseClassLink::where('class_id', $member->class_id)->where('status', CourseClassLinkStatus::Active->value)->with('class')->get();

        foreach ($links as $link) {
            $this->endOrRepointEnrollments($link, [$member->student_id], "Rời khỏi lớp {$link->class->name}", $actor);
        }
    }
}
```

- [ ] **Step 4: Gọi Action mới từ `EndClassMember`**

Thêm `private SyncCourseEnrollmentsOnClassMemberLeft $courseSync` vào constructor của `EndClassMember` (cạnh `AuditLogger $audit`); trong `handle()`, ngay sau khối `$this->audit->record(...)` (vẫn trong `DB::transaction`), gọi `$this->courseSync->handle($member, $actor);`.

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentClassSyncTest`
Expected: PASS (3 test)

Run thêm để không hồi quy: `docker compose exec api php artisan test --filter=ClassMemberTest`
Expected: PASS (toàn bộ test cũ vẫn xanh)

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Academic/Actions/SyncCourseEnrollmentsOnClassMemberLeft.php app/Modules/UserManagement/Actions/EndClassMember.php tests/Feature/Academic/CourseEnrollmentClassSyncTest.php
git commit -m "feat(academic): end course enrollment when student leaves a linked class"
```

---

## Task 5: Đồng bộ khi học sinh vào/quay lại lớp

**Files:**
- Create: `app/Modules/Academic/Actions/SyncCourseEnrollmentsOnClassMemberJoined.php`
- Modify: `app/Modules/UserManagement/Actions/AddClassMember.php`
- Modify: `tests/Feature/Academic/CourseEnrollmentClassSyncTest.php` (thêm case)

**Interfaces:**
- Consumes: trait `enrollEligibleMembers()` (Task 2).
- Produces: `SyncCourseEnrollmentsOnClassMemberJoined::handle(ClassMember $member, User $actor): void`.

- [ ] **Step 1: Viết test thất bại**

```php
public function test_joining_a_linked_class_creates_an_active_enrollment(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);
    $newStudent = Student::factory()->create();

    $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $newStudent->id]);

    $enrollment = CourseEnrollment::where('course_id', $course->id)->where('student_id', $newStudent->id)->sole();
    $this->assertSame('active', $enrollment->status->value);
    $this->assertNotNull($enrollment->source_class_link_id);
}

public function test_rejoining_a_class_after_leaving_creates_a_new_active_enrollment(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);
    $this->deleteJson("/api/v1/classes/{$class->id}/members/{$student->id}", ['end_reason' => 'Nghỉ tạm']);

    $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active']);
    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'ended']); // dòng lịch sử cũ vẫn còn
}

public function test_joining_does_not_duplicate_an_existing_individual_enrollment(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);
    $student = Student::factory()->create();
    CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]); // cá nhân, trước khi vào lớp

    $this->postJson("/api/v1/classes/{$class->id}/members", ['student_id' => $student->id]);

    $this->assertSame(1, CourseEnrollment::where('course_id', $course->id)->where('student_id', $student->id)->count());
}
```

Kiểm tra đúng route/body thật của `AddClassMember` (`POST /classes/{class}/members` là phỏng đoán theo mẫu REST hiện có — đọc `routes/api.php`/`AddClassMemberController` trước khi viết test, sửa nếu khác).

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentClassSyncTest`
Expected: FAIL (3 test mới)

- [ ] **Step 3: Viết `SyncCourseEnrollmentsOnClassMemberJoined::handle()`**

```php
final class SyncCourseEnrollmentsOnClassMemberJoined
{
    use SyncsCourseEnrollmentsForClassLink;

    public function __construct(private AuditLogger $audit) {}

    public function handle(ClassMember $member, User $actor): void
    {
        $links = CourseClassLink::where('class_id', $member->class_id)->where('status', CourseClassLinkStatus::Active->value)->get();

        foreach ($links as $link) {
            $this->enrollEligibleMembers($link, [$member->student_id], $actor);
        }
    }
}
```

- [ ] **Step 4: Gọi Action mới từ `AddClassMember`**

Thêm `private SyncCourseEnrollmentsOnClassMemberJoined $courseSync` vào constructor; gọi `$this->courseSync->handle($member, $actor);` sau khối `$this->audit->record(...)`, trước `return $member;`, vẫn trong `DB::transaction`.

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentClassSyncTest`
Expected: PASS (6 test, gộp cả Task 4)

Run thêm: `docker compose exec api php artisan test --filter=ClassMemberTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Academic/Actions/SyncCourseEnrollmentsOnClassMemberJoined.php app/Modules/UserManagement/Actions/AddClassMember.php tests/Feature/Academic/CourseEnrollmentClassSyncTest.php
git commit -m "feat(academic): enroll new class member into linked courses"
```

---

## Task 6: Lớp bị lưu trữ tự gỡ liên kết và kết thúc ghi danh liên quan

**Files:**
- Create: `app/Modules/Academic/Actions/SyncCourseEnrollmentsOnClassArchived.php`
- Modify: `app/Modules/Academic/Actions/UpdateClass.php`
- Test: `tests/Feature/Academic/CourseEnrollmentClassSyncTest.php` (thêm case)

**Interfaces:**
- Consumes: trait `endOrRepointEnrollments()` (Task 3).
- Produces: `SyncCourseEnrollmentsOnClassArchived::handle(SchoolClass $class, User $actor): void`.

- [ ] **Step 1: Viết test thất bại**

```php
public function test_archiving_a_class_unlinks_its_active_course_links_and_ends_enrollments(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $this->patchJson("/api/v1/classes/{$class->id}", ['status' => 'archived']);

    $this->assertDatabaseHas('course_class_links', ['course_id' => $course->id, 'class_id' => $class->id, 'status' => 'unlinked']);
    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'ended']);
}

public function test_archiving_one_of_two_linked_classes_keeps_enrollment_via_the_other(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $classA = SchoolClass::factory()->create();
    $classB = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $classA->id, 'student_id' => $student->id]);
    ClassMember::factory()->create(['class_id' => $classB->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $classA->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $classB->id]);
    $linkB = CourseClassLink::where('class_id', $classB->id)->sole();

    $this->patchJson("/api/v1/classes/{$classA->id}", ['status' => 'archived']);

    $enrollment = CourseEnrollment::where('course_id', $course->id)->where('student_id', $student->id)->sole();
    $this->assertSame('active', $enrollment->status->value);
    $this->assertSame($linkB->id, $enrollment->source_class_link_id);
}
```

Kiểm tra đúng path/body thật của `UpdateClassController` trước khi viết (`PATCH /classes/{class}` là phỏng đoán theo mẫu `UpdateCourseController`).

- [ ] **Step 2: Chạy test, xác nhận thất bại**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentClassSyncTest`
Expected: FAIL (2 test mới)

- [ ] **Step 3: Viết `SyncCourseEnrollmentsOnClassArchived::handle()`**

```php
final class SyncCourseEnrollmentsOnClassArchived
{
    use SyncsCourseEnrollmentsForClassLink;

    public function __construct(private AuditLogger $audit) {}

    public function handle(SchoolClass $class, User $actor): void
    {
        $links = CourseClassLink::where('class_id', $class->id)->where('status', CourseClassLinkStatus::Active->value)->get();
        $studentIds = ClassMember::where('class_id', $class->id)->where('status', MembershipStatus::Active->value)->pluck('student_id');

        foreach ($links as $link) {
            $link->update(['status' => CourseClassLinkStatus::Unlinked, 'unlinked_by_user_id' => $actor->id, 'unlinked_at' => now()]);
            $this->audit->record(actor: $actor, action: 'course_class_link.unlink', entityType: 'CourseClassLink', entityId: $link->id,
                after: ['status' => CourseClassLinkStatus::Unlinked->value, 'reason' => 'class_archived'], correlationKey: $link->course_id);

            $this->endOrRepointEnrollments($link, $studentIds, "Lớp {$class->name} đã lưu trữ", $actor);
        }
    }
}
```

- [ ] **Step 4: Bọc `UpdateClass` trong transaction và gọi Action mới khi archive**

```php
final class UpdateClass
{
    public function __construct(private SyncCourseEnrollmentsOnClassArchived $courseSync) {}

    public function handle(SchoolClass $class, array $data, User $actor): SchoolClass
    {
        return DB::transaction(function () use ($class, $data, $actor) {
            $class->fill($data);
            $willArchive = $class->isDirty('status') && $class->status === ClassStatus::Archived;
            if ($willArchive) {
                $class->archived_at = now();
            }
            $class->save();

            if ($willArchive) {
                $this->courseSync->handle($class, $actor);
            }

            return $class;
        });
    }
}
```

`UpdateClass::handle()` đổi chữ ký (thêm tham số `User $actor`) — cập nhật `UpdateClassController` để truyền `$request->user()`; kiểm tra có test nào gọi `UpdateClass::handle()` trực tiếp (không qua HTTP) cần sửa theo chữ ký mới không (`grep -rn "UpdateClass::" tests/`).

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentClassSyncTest`
Expected: PASS (8 test, gộp Task 4+5+6)

Run thêm toàn bộ test Academic + UserManagement đã chạm: `docker compose exec api php artisan test --filter=Academic && docker compose exec api php artisan test --filter=UserManagement`
Expected: PASS, không hồi quy

- [ ] **Step 6: Commit**

```bash
git add app/Modules/Academic/Actions/SyncCourseEnrollmentsOnClassArchived.php app/Modules/Academic/Actions/UpdateClass.php app/Modules/Academic/Http/Controllers/UpdateClassController.php tests/Feature/Academic/CourseEnrollmentClassSyncTest.php
git commit -m "feat(academic): unlink course links and end enrollments when a class is archived"
```

---

## Task 7: Chặn "Kết thúc ghi danh" thủ công trên enrollment nguồn-từ-lớp

**Files:**
- Create: `app/Modules/Academic/Exceptions/EnrollmentLinkedToClass.php`
- Modify: `app/Modules/Academic/Actions/EndCourseEnrollment.php`
- Modify: `docs/architecture/error-codes.md`
- Test: `tests/Feature/Academic/CourseEnrollmentTest.php` (thêm case)

**Interfaces:**
- Consumes: cột `source_class_link_id` (Task 1).
- Produces: không gì mới cho task khác dùng — đây là task cuối của plan này.

- [ ] **Step 1: Viết test thất bại**

```php
public function test_manually_ending_an_enrollment_sourced_from_a_class_link_is_rejected(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $class = SchoolClass::factory()->create();
    $student = Student::factory()->create();
    ClassMember::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);
    $this->postJson("/api/v1/courses/{$course->id}/class-links", ['class_id' => $class->id]);

    $response = $this->deleteJson("/api/v1/courses/{$course->id}/enrollments/{$student->id}", ['end_reason' => 'Thử']);

    $response->assertStatus(409)->assertJsonPath('error.code', 'ENROLLMENT_LINKED_TO_CLASS');
    $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'active']);
}

public function test_manually_ending_an_individually_sourced_enrollment_still_works(): void
{
    $this->actingAsAdmin();
    $course = Course::factory()->create();
    $student = Student::factory()->create();
    CourseEnrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);

    $response = $this->deleteJson("/api/v1/courses/{$course->id}/enrollments/{$student->id}", ['end_reason' => 'Nghỉ học']);

    $response->assertNoContent();
}
```

- [ ] **Step 2: Chạy test, xác nhận test đầu thất bại (test thứ hai đã xanh từ trước)**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentTest`
Expected: 1 FAIL (`test_manually_ending_an_enrollment_sourced_from_a_class_link_is_rejected`), còn lại PASS

- [ ] **Step 3: Viết exception và thêm guard vào `EndCourseEnrollment`**

`EnrollmentLinkedToClass`: `errorCode = 'ENROLLMENT_LINKED_TO_CLASS'`, `status = 409`, message mặc định `'Ghi danh này đến từ liên kết lớp, không thể kết thúc riêng lẻ. Gỡ học sinh khỏi lớp hoặc gỡ liên kết lớp-khóa.'`.

Trong `EndCourseEnrollment::handle()`, ngay sau `lockForUpdate()->firstOrFail()` và trước `$enrollment->update(...)`: `throw_if($enrollment->source_class_link_id !== null, new EnrollmentLinkedToClass);`.

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `docker compose exec api php artisan test --filter=CourseEnrollmentTest`
Expected: PASS (toàn bộ)

- [ ] **Step 5: Thêm dòng vào `error-codes.md`**

`ENROLLMENT_LINKED_TO_CLASS | 409 | Academic | Kết thúc ghi danh thủ công (DELETE /courses/{course}/enrollments/{studentId}) trên một dòng có source_class_link_id khác null — chỉ gỡ được qua rời lớp hoặc gỡ liên kết lớp-khóa.`

- [ ] **Step 6: Chạy toàn bộ test suite BE + pint + phpstan**

Run: `docker compose exec api php artisan test`
Expected: PASS toàn bộ

Run: `docker compose exec api vendor/bin/pint`
Expected: không còn file cần format (hoặc tự sửa xong)

Run: `docker compose exec api composer analyse`
Expected: không có lỗi PHPStan mới ngoài baseline hiện có

- [ ] **Step 7: Xuất lại OpenAPI lần cuối**

Run: `docker compose exec api php artisan scramble:export`

- [ ] **Step 8: Commit**

```bash
git add app/Modules/Academic/Exceptions/EnrollmentLinkedToClass.php app/Modules/Academic/Actions/EndCourseEnrollment.php docs/architecture/error-codes.md tests/Feature/Academic/CourseEnrollmentTest.php
git commit -m "feat(academic): reject manual end on enrollments sourced from a class link"
```
