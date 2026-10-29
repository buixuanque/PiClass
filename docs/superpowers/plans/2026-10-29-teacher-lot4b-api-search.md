# Lát 4b — BE: tìm theo tên bài cho `GET /assessments` Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `GET /assessments` nhận tham số `search` tìm tên bài không dấu, không phân biệt hoa thường, để TC-09 có ô tìm tên bài.

**Architecture:** Thêm một điều kiện vào `ListAssessmentsQuery` dùng lại `App\Support\UnaccentSearch` (cùng cách `ListQuestionsQuery` làm ở lát 4a). Tìm trên `title` của phiên bản hiện hành (`currentVersion`). Không migration, không endpoint mới.

**Tech Stack:** Laravel 13, PHP 8.4, PostgreSQL (`unaccent`), PHPUnit 12.

**Spec:** `docs/product/screens/teacher-bai-danh-gia.md` (TC-09, TB-1), `DEC-LRN-020` mục 3, `DEC-LRN-022` mục 2. Mockup: `PiClass Giáo viên.dc.html` (TC-09, ô tìm tên bài).

## Global Constraints

- `search` chỉ khớp **tên bài** (`assessment_versions.title` của `currentVersion`); không khớp mô tả, tên câu hỏi hay lớp (`DEC-LRN-022` mục 2).
- Không phân biệt dấu và hoa thường: "kiem tra" khớp "Kiểm tra Toán".
- Giá trị rỗng hoặc toàn khoảng trắng bị bỏ qua (giống `ListAssessmentsQuery` với `status`/`type`: giá trị rỗng không lọc).
- Không đổi phạm vi dữ liệu: Teacher vẫn chỉ thấy bài của mình/lớp mình dạy/Course mình sở hữu; `ListAssessmentsRequest::rules()` giữ rỗng (không đổi 200 thành 422).
- Comment trong code tiếng Việt; tên test method tiếng Anh; commit message tiếng Anh; PR tiếng Việt.
- Đổi tham số API thì export OpenAPI trước khi commit (`docker compose exec api php artisan scramble:export` ở `piclass-infra/local`).

## Review Focus

- `search` chỉ có khoảng trắng hoặc rỗng: trả đủ danh sách như không lọc (không lỗi, không trả rỗng).
- `search[]=x` (mảng) hoặc kiểu không phải chuỗi: bỏ qua, không 500.
- `search` kết hợp `status`/`type`: cả hai điều kiện cùng áp dụng (AND).
- Teacher tìm tên trùng với bài của giáo viên khác không liên quan: không thấy bài đó (tìm không mở rộng phạm vi).
- Bài đã có phiên bản mới đổi tên: tìm theo tên hiện hành, tên cũ không khớp.

---

### Task 1: `search` trong `ListAssessmentsQuery`

**Files:**
- Modify: `piclass-api/app/Modules/Assessment/Queries/ListAssessmentsQuery.php`
- Test: `piclass-api/tests/Feature/Assessment/ListAssessmentsSearchTest.php` (mới)

**Interfaces:**
- Consumes: `App\Support\UnaccentSearch::apply(Builder $query, string $column, string $value): Builder`; quan hệ `Assessment::currentVersion`.
- Produces: `ListAssessmentsQuery::handle(User $user, array $filters, int $perPage)` nhận thêm khóa `search` trong `$filters` (cập nhật docblock `@param`).

- [ ] **Step 1: Viết test (lớp `ListAssessmentsSearchTest`, dùng lại cách dựng Teacher/Assessment của `ListAndGetAssessmentTest`)**
  - `test_search_matches_title_without_diacritics_and_case`: bài "Kiểm tra Toán chương 1" của chính giáo viên; `?search=kiem tra toan` và `?search=KIỂM TRA` đều trả bài đó, `?search=van` không trả.
  - `test_blank_or_array_search_is_ignored`: `?search=%20%20` và `?search[]=x` đều trả đủ các bài của giáo viên, status 200.
  - `test_search_combines_with_type_filter`: hai bài cùng khớp tên nhưng khác `type`; `?search=toan&type=quiz` chỉ trả bài `quiz`.
  - `test_search_does_not_widen_teacher_scope`: bài của giáo viên khác không liên quan có tên khớp; không xuất hiện trong kết quả.
  - `test_search_uses_current_version_title_only`: bài có phiên bản cũ tên "Đề cũ" và `currentVersion` tên "Đề mới"; `?search=de moi` trả bài, `?search=de cu` không trả.

- [ ] **Step 2: Chạy test, xác nhận fail**

  Run: `docker compose exec api php artisan test --filter=ListAssessmentsSearchTest`
  Expected: FAIL (tham số `search` chưa có tác dụng: các test lọc trả cả bài không khớp).

- [ ] **Step 3: Thêm điều kiện `search` vào `ListAssessmentsQuery::handle`**

  Khi `$filters['search']` là chuỗi và `trim()` không rỗng: `$query->whereHas('currentVersion', fn ($vq) => UnaccentSearch::apply($vq, 'title', trim($filters['search'])))`. Đặt cạnh các `->when(...)` hiện có; cập nhật docblock lớp và `@param` (thêm `search?: mixed`, ghi "tìm tên bài không dấu").

- [ ] **Step 4: Chạy test, xác nhận pass; chạy cả nhóm Assessment**

  Run: `docker compose exec api php artisan test --filter=Assessment`
  Expected: PASS, không test cũ nào hỏng.

- [ ] **Step 5: Commit**

  ```bash
  git add app/Modules/Assessment/Queries/ListAssessmentsQuery.php tests/Feature/Assessment/ListAssessmentsSearchTest.php
  git commit -m "feat(assessment): add unaccent title search to GET /assessments"
  ```

### Task 2: OpenAPI và tài liệu

**Files:**
- Modify: `piclass-api/storage/api-docs/openapi.json` (export lại)
- Modify: `docs/product/screens/teacher-bai-danh-gia.md` (TC-09 và dòng TB-1: `GET /assessments` đã có `search`)

**Interfaces:**
- Consumes: Task 1 (tham số `search` của `GET /assessments`).
- Produces: `openapi.json` có tham số query `search` cho `GET /assessments`, để FE đọc khi làm lát 4b FE.

- [ ] **Step 1: Export OpenAPI**

  Run: `docker compose exec api php artisan scramble:export` (ở `piclass-infra/local`).
  Expected: `openapi.json` đổi đúng phần `GET /assessments`, không đổi chỗ khác.

- [ ] **Step 2: Sửa spec**

  Ở TC-09 ghi `search` (tên bài, không dấu) đã có; ở TB-1 đánh dấu phần `GET /assessments` đã giải quyết (phần `GET /questions` đã xong ở lát 4a).

- [ ] **Step 3: Chạy toàn bộ test BE và commit**

  Run: `docker compose exec api php artisan test`
  Expected: PASS.

  ```bash
  git add storage/api-docs/openapi.json
  git commit -m "docs(api): export OpenAPI with assessments search"
  ```
  (Thay đổi spec ở repo docs commit riêng, nhánh docs.)
