# Data Flow Analysis: Library Management System

Location: `docs/reverse-engineering/code/dfg/`

## 1. Method

1. **Trace** important variables, parameters, return values and data structures → [00_data_inventory.md](00_data_inventory.md)
2. **Identify** where data is defined, modified and consumed (DEF / MOD / USE tables in every file).
3. **Trace data paths** between user input, application logic and database operations (path tables + taint check: is each input sanitized before each sink?).
4. **Construct DFGs** for selected workflows (Mermaid `flowchart LR`).

**Notation:** rounded = external entity, rectangle = process (with source lines), cylinder = data store (DB table / `$_SESSION`), edge label = data item. Orange = untrusted input, blue = store, green = output, red = disclosure or dead data. Findings are **Confirmed** (proven in code/schema/`grep`) or **Likely** (depends on PHP/MySQL configuration). Line numbers refer to the uploaded zip.

## 2. Documents

| # | Document | Workflow | Main data path |
|---|----------|----------|----------------|
| 00 | [00_data_inventory.md](00_data_inventory.md) | All | Input sources, stores, script × table matrix, structures, function parameters/returns |
| 01 | [01_login_dfg.md](01_login_dfg.md) | Login | form → `users` → session |
| 02 | [02_registration_dfg.md](02_registration_dfg.md) | Registration | form → validation → hash → `users` |
| 03 | [03_issue_book_dfg.md](03_issue_book_dfg.md) | Issue | form → locked read → `issued_books` + `books` |
| 04 | [04_return_book_dfg.md](04_return_book_dfg.md) | Return | form → locked read → `issued_books` + `books` |
| 05 | [05_book_crud_dfg.md](05_book_crud_dfg.md) | Book create/update | form → validation → `books` |
| 06 | [06_search_dfg.md](06_search_dfg.md) | Search | `GET q` → LIKE → HTML |
| 07 | [07_session_config_dfg.md](07_session_config_dfg.md) | Session, config, dashboard | session keys, DB constants, `BASE_URL`, counts |

## 3. Taint summary (user input → sink)

| Sink class | Result |
|------------|--------|
| **SQL** | Every user value reaches SQL through prepared statements; no `query()` call interpolates a variable. **No SQL-injection path found.** |
| **HTML** | Every user/DB string is passed through `htmlspecialchars`; unescaped echoes are integers (casts, `COUNT`, DB ids) or constants. **No XSS path found.** |
| **Session / headers** | `Location:` targets use `BASE_URL` (filesystem-derived) or constants. **No open-redirect path found.** |
| **Business logic** | Weak points are not injection but missing checks (below). |

## 4. Consolidated findings

| ID | Finding | Where | Status | Severity |
|----|---------|-------|--------|----------|
| DF-01 | No server-side or HTML length limits for any string field that maps to a bounded `VARCHAR` (only password ≥ 6 is checked) | 02, 05 | Confirmed (absence); effect depends on `sql_mode` | Medium |
| DF-02 | `books.available_copies` is a stored derived value written by four scripts (add, issue, return, edit) and never reconciled with active loans; `edit.php` can set it arbitrarily | 03, 04, 05 | Confirmed | High |
| DF-03 | Empty ISBN `''` is inserted into a `UNIQUE` column: only the first book without an ISBN can be saved | 05 | Confirmed | Medium |
| DF-04 | `author_id` / `member_id` not validated as numeric or existing; failures surface as DB errors | 03, 05 | Likely | Low |
| DF-05 | No CSRF tokens on any form or GET-delete | all | Confirmed | High |
| DF-06 | Unauthenticated data paths: 16 pages accept input and write to the database without `requireLogin()` | 03, 04, 05, 06 | Confirmed | High |
| DF-07/08 | Positive: no SQL-injection or XSS path found | 06 and all | Confirmed | n/a |
| DF-09 | Stale or duplicate definitions: dropdown read before write (`issue.php`), repeated query (`return.php`), unescaped LIKE wildcards | 03, 04, 06 | Confirmed | Low |
| DF-10 | Information disclosure: `?debug_paths=1`, raw DB connection errors, DB exception text, public `test_connection.php` | 03, 07 | Confirmed / Likely | Medium |
| DF-11 | Hard-coded `root` / empty DB password; default admin password published | 01, 07 | Confirmed | High |
| DF-12 | Dead data: `user_email`, `role` and `currentUser()` keys `id/email/role` are produced but never consumed | 01, 07 | Confirmed | Low |
| DF-13 | No failed-login counter or throttling data store | 01 | Confirmed (absence) | Medium |
| DF-14 | Password trimmed at registration but not at login (same value, two transformations) | 01, 02 | Confirmed | Medium |
| DF-15 | Deleting a book or member cascades to `issued_books` history (guard checks only active loans) | cross-ref CFG F-10 | Confirmed | Medium |
| DF-16 | `books/edit.php` re-reads the book from the DB but then overrides every field with `$_POST`: dead definition; shows untrimmed text that differs from the stored trimmed text | 05 | Confirmed | Low |

Cross-references to the Control Flow Analysis: F-01 ↔ DF-06, F-03 ↔ DF-14, F-05/F-06 ↔ DF-09, F-10 ↔ DF-15.
