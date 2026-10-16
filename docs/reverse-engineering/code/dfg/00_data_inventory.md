# DFA 00 — Data Inventory (variables, parameters, return values, data structures)

This file covers steps **1 and 2** of the analysis: *what data exists* and *where it is defined, modified and consumed*. The per-workflow files (01-07) cover steps 3 and 4 (data paths and DFGs).

**Notation:** DEF = first assignment / creation, MOD = modification, USE-P = used in a predicate (condition), USE-C = used in a computation, query or output.

## 1. Input sources (untrusted data entering the system)

| Source | Fields | Consumed by |
|--------|--------|-------------|
| `$_POST` | `email`, `password`, `remember` | `auth/login_handler.php` |
| `$_POST` | `username`, `email`, `password`, `confirm_password` | `auth/register.php` |
| `$_POST` | `book_id`, `member_id`, `due_date` | `issue_return/issue.php` |
| `$_POST` | `issue_id` | `issue_return/return.php` |
| `$_POST` | `title`, `author_id`, `isbn`, `category`, `total_copies`, `available_copies`, `book_id` (hidden) | `books/add.php`, `books/edit.php` |
| `$_POST` | `full_name`, `email`, `phone`, `member_id` (hidden) | `members/add.php`, `members/edit.php` |
| `$_POST` | `first_name`, `last_name`, `bio`, `author_id` (hidden) | `authors/add.php`, `authors/edit.php` |
| `$_GET` | `q` | `search.php` L5 |
| `$_GET` | `id` | `books/`, `authors/`, `members/` `edit.php` and `delete.php` |
| `$_GET` | `debug_paths` | `config/paths.php` L54 (loaded by every page) |
| `$_SERVER` | `REQUEST_METHOD` | every form handler |
| `$_SERVER` | `DOCUMENT_ROOT` | `config/paths.php` L20 |
| `$_SERVER` | `PHP_SELF` | `includes/header.php` L14-15 (only compared against constants) |
| Session cookie | PHP session id | `session_start()` in `login_handler.php`, `includes/auth.php`, `auth/index.php`, `auth/logout.php` |
| Server clock | `date('Y-m-d')` | `issue.php` L24/46/112, `return.php` L42, `status.php` L5 |
| Constants | DB host/user/password/name | `config/db.php` L12-15 |

## 2. Data stores

| Store | Structure | Notes |
|-------|-----------|-------|
| `users` | `user_id, username (UNIQUE, 50), email (UNIQUE, 100), password_hash (255), role ENUM(admin, staff), created_at` | `user_schema.sql` |
| `authors` | `author_id, first_name (50), last_name (50), bio` | `library_db.sql` |
| `books` | `book_id, title (150), author_id FK (ON DELETE SET NULL), isbn (UNIQUE, 20), category (50), total_copies, available_copies` | `available_copies` is a stored, derived value |
| `members` | `member_id, full_name (100), email (UNIQUE, 100), phone (20)` | |
| `issued_books` | `issue_id, book_id FK (ON DELETE CASCADE), member_id FK (ON DELETE CASCADE), issue_date, due_date, return_date NULL, status ENUM(issued, returned)` | Loan history |
| `$_SESSION` | `user_id, username, user_email, role, login_error` | See section 4 |

## 3. Script × table access matrix (C = INSERT, R = SELECT, U = UPDATE, D = DELETE)

Built from every SQL statement in the project (`grep` of `SELECT/INSERT/UPDATE/DELETE`). No `query()` call interpolates a variable.

| Script | users | authors | books | members | issued_books |
|--------|:----:|:------:|:-----:|:-------:|:------------:|
| `auth/login_handler.php` | R | | | | |
| `auth/register.php` | C R | | | | |
| `authors/add.php` | | C | | | |
| `authors/edit.php` | | R U | | | |
| `authors/view.php` | | R | R | | |
| `authors/delete.php` | | D | (author_id → NULL via FK) | | |
| `books/add.php` | | R | C R | | |
| `books/edit.php` | | R | R U | | |
| `books/view.php` | | R | R | | |
| `books/delete.php` | | | D | | R (+ implicit D by cascade) |
| `members/add.php` | | | | C R | |
| `members/edit.php` | | | | R U | |
| `members/view.php` | | | | R | R |
| `members/delete.php` | | | | D | R (+ implicit D by cascade) |
| `issue_return/issue.php` | | | R **U** | R | **C** |
| `issue_return/return.php` | | | R **U** | R | R **U** |
| `issue_return/status.php` | | | R | R | R |
| `search.php` | | R | R | | |
| `index.php` | | R | R | R | R |
| `test_connection.php` | | | R | | |

**Writers of `books.available_copies`:** `books/add.php` (initial value), `issue.php` (-1), `return.php` (+1), `books/edit.php` (arbitrary value). Four writers, no reconciliation (see DF-02).

## 4. Data structures

| Structure | Defined | Modified | Consumed |
|-----------|---------|----------|----------|
| `$_SESSION['user_id']` | `login_handler.php` L57 | regenerated id L54 (value unchanged) | `auth/index.php` L5 (P), `auth.php` L24 (P), L47 (C) |
| `$_SESSION['username']` | L58 | | `auth.php` L48 → `currentUser()['username']` → `header.php` L26, `index.php` L18 |
| `$_SESSION['user_email']` | L59 | | `auth.php` L49 only → `currentUser()['email']`, **never read by any page** |
| `$_SESSION['role']` | L60 | | `auth.php` L36 (`requireRole`, never called) and L50 (`currentUser()['role']`, never read) |
| `$_SESSION['login_error']` | `login_handler.php` L19, L33, L87 | | `auth/index.php` L10, then `unset` L11 (read-once flash message) |
| `$errors` (array of strings) | each handler, first lines | appended per failed validation | `empty($errors)` as gate, then `foreach` output through `htmlspecialchars` |
| `currentUser()` return (`?array`: `id, username, email, role`) | `auth.php` L46-51 | | `header.php` L25 and `index.php` L13; only `username` is read |
| DB row arrays | `fetch_assoc()` | | output loops / business checks |
| `$params` (cookie params array) | `login_handler.php` L64 | | L66-77 (`setcookie` options) |

## 5. Functions: parameters and return values

| Function | Parameters | Returns | Consumers |
|----------|-----------|---------|-----------|
| `isLoggedIn()` | none | `bool` (`isset($_SESSION['user_id'])`) | `requireLogin` L28, `currentUser` L43, `register.php` L11 |
| `requireLogin()` | none | `void` (redirects + `exit` when false) | `index.php` L4, `books/view.php` L4, `requireRole` L35 |
| `requireRole($role)` | `string $role` | `void` (403 + `die` on mismatch) | **none** (dead) |
| `currentUser()` | none | `?array` or `null` | `header.php` L25, `index.php` L13 |
| `nav_active($page, $folder = null)` | page filename, optional folder | `'active'` or `''` | `header.php` L67-88 |

## 6. Cross-cutting data paths (summary)

| Path | Entry | Validation / transformation | Core logic | Store | Output |
|------|-------|-----------------------------|-----------|-------|--------|
| Login | `$_POST email/password` | `trim(email)`, empty check | `password_verify` | reads `users` | session + redirect |
| Registration | `$_POST` 4 fields | `trim` all, email format, length ≥ 6, match, uniqueness | `password_hash`, role fixed to `'staff'` | writes `users` | flash text |
| Issue | `$_POST` 3 fields | `trim`, empty, date not past | transaction + row lock | writes `issued_books`, `books` | flash text |
| Return | `$_POST issue_id` | `trim`, empty | transaction + row lock | writes `issued_books`, `books` | flash text |
| Book create/update | `$_POST` 5-6 fields | `trim`, required, numeric, ISBN unique | copies logic | writes `books` | flash text |
| Search | `$_GET q` | `trim`, `%…%` wrapping | prepared LIKE | reads `books`, `authors` | escaped table |
| Delete | `$_GET id` | `(int)` cast, `> 0` | active-loan guard | deletes row (+ cascade) | redirect |
