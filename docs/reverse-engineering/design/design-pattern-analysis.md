# Design Pattern Analysis

**Rule applied:** a pattern is listed as *present* only when the code shows its defining structure and intent. Name resemblance is not accepted. Everything else is listed under "considered and rejected" with the reason.
The project has **no classes**, so object-oriented (GoF) patterns that require classes cannot be present.

## 1. Patterns genuinely present

| # | Pattern | Where (evidence) | Why it qualifies | Limits |
|---|---------|------------------|------------------|--------|
| P1 | **Transaction Script** (Fowler) | `issue_return/issue.php` L11-65, `issue_return/return.php` L17-60 | One procedure per business transaction (issue, return): validate, lock, write two tables, commit/rollback, in procedural code with no domain objects | Mixed with presentation; logic is copy-pasted between the two scripts |
| P2 | **Pessimistic locking** (row lock for read-modify-write) | `issue.php` L36-40 and `return.php` L29-33: `SELECT ... FOR UPDATE` inside `begin_transaction`/`commit`/`rollback` | The row is locked before the availability/status check and the counter update, preventing double issue/return races | Used in 2 places only; `books/edit.php` updates the same counters without a lock |
| P3 | **Page Controller** (Fowler) | every entry script, e.g. `books/delete.php`, `members/add.php` | Each page/URL has its own script that handles that request | Controller and view are in the same file; there is no shared base behaviour |
| P4 | **Template View** (server-side templating) | `issue.php` L67-118, `books/view.php`, `status.php`: HTML with embedded PHP loops and conditionals | Markup is the primary structure; PHP is embedded to emit dynamic parts | No separation of template from logic |
| P5 | **Layout partials (header/footer include)** | `includes/header.php`, `includes/footer.php`, included by 16 pages | Shared page chrome is reused through include files | `header.php` also holds logic (see AP-4); 2 auth pages write their own head |
| P6 | **View Helper** | `nav_active($page, $folder)` in `header.php` L17-23, called 8 times (L67-88) | A small function that computes presentation state (`active` class) for the template | Reads globals instead of parameters |
| P7 | **Flash message (read-once)** | `login_handler.php` L19/33/87 set `$_SESSION['login_error']`; `auth/index.php` L10-11 read and `unset` it | State is carried across one redirect and then discarded | Used for login only; other pages use per-request `$errors` arrays |
| P8 | **Post/Redirect/Get** (partial) | `login_handler.php` (all 5 exits), `*/delete.php`, `logout.php` | Every state change ends with a redirect | **Absent** where it matters most: `issue.php`, `return.php`, `*/add.php`, `*/edit.php` re-render after POST (CFG F-06) |
| P9 | **Guard clause / early exit** (idiom) | `login_handler.php` L8-21, L32; `books/delete.php` L6-8, L17-25 | Invalid cases exit early so the main path stays flat | Idiom, not a structural pattern |
| P10 | **Parameterized queries** (secure data-access idiom) | 28 `prepare`/`bind_param` sites; no `query()` call interpolates a variable | All user data is bound, never concatenated | Not centralized; repeated per script |
| P11 | **Idempotent configuration constant** | `paths.php` L15 `if (!defined('BASE_URL'))`, `define` at L25/L44 | Computed once, safe to include repeatedly | Mixed with a debug handler |

## 2. Patterns considered and rejected

| Pattern | Looks applicable because | Why it is **not** present (evidence) |
|---------|-------------------------|--------------------------------------|
| MVC | folders look like modules; there is a "view" part in each page | no model classes, no controller/view separation; logic and markup coexist in each file (18 of 27 files) |
| Front Controller | `BASE_URL` and a shared header exist | no single entry point, no router, no `.htaccess`; each file is a separate entry |
| Singleton | `$conn` is created once per request | it is a **global variable** created as an include side effect (`db.php` L17); no class, no private constructor, no `getInstance` |
| Repository / DAO / Data Mapper | SQL is repetitive | SQL is inline in page scripts; no data-access abstraction exists |
| Active Record | `books`, `members` tables map to pages | no objects; rows are plain arrays from `fetch_assoc()` |
| Service Layer | issue/return act like services | they are scripts that also render HTML; not callable from elsewhere |
| Factory / Builder | `new mysqli` appears once | single direct instantiation; no creation abstraction |
| Dependency Injection | `$conn` is "passed" to pages | it is resolved by global name; nothing is injected |
| Observer / Strategy / Decorator / Proxy | `requireLogin()` guards pages | it is a plain function call placed by hand, not an interceptor or proxy |
| Role-Based Access Control | `role` column and `requireRole()` exist | `requireRole()` has 0 callers; only `admin` can log in; no page restricts by role |
| Registry / Service Locator | `$_SESSION` shared state | it is the PHP session, used as a data bag, not a lookup service |

## 3. Anti-patterns, smells and inappropriate abstractions

| ID | Smell / anti-pattern | Evidence | Consequence |
|----|----------------------|----------|-------------|
| AP-1 | **Smart UI** (logic in the view layer) | 18 of 27 PHP files mix SQL and markup; `issue.php` (120 lines) does input, validation, locking, SQL and HTML | no reuse, no testability |
| AP-2 | **Copy-paste programming** | the transaction skeleton in `issue.php`/`return.php`; the same JOIN twice in `return.php` (L8-15, L62-69); active-loan guard in `books/delete.php` and `members/delete.php`; add/edit validations repeated | defects must be fixed in several places |
| AP-3 | **Global state / common coupling** | `$conn`, `global $current_page, $current_folder` (`header.php` L18), `$_SESSION` keys written in one file and read in another | hidden dependencies, order-sensitive includes |
| AP-4 | **Mixed-responsibility include** (kitchen-sink file) | `header.php`: path loading, function definition, user lookup, full page markup | cannot change layout without touching logic |
| AP-5 | **Speculative generality / dead code** | `requireRole()` (0 callers); `validate.js` sections for `#userMenu` and `.password-toggle` match no markup; `?deleted=1` produced by 3 scripts and read by none; `users.created_at` referenced in no PHP file; `test_connection.php` | misleads readers about what the system does |
| AP-6 | **Hard-coded configuration** | `db.php` L11-14 (`root`, empty password); default admin credentials published | unsafe defaults, no environment separation |
| AP-7 | **Magic strings** for domain states | `'issued'` 10 occurrences, `'returned'` 3, `'admin'`/`'staff'` in separate files | a rename or new status must be found by search |
| AP-8 | **Redundant derived data** | `books.available_copies` stored alongside `total_copies` and the loan table; 4 writers (see ERD SC-1) | counters can drift from reality |
| AP-9 | **Leaky / incomplete abstraction** | `header.php` L25 uses `function_exists('currentUser')` instead of declaring the dependency; `requireLogin()` is opt-in so most pages bypass it | the abstraction exists but does not provide its guarantee |
| AP-10 | **Missing abstraction** | no data-access layer, no circulation functions, no shared form/validation helpers | all duplication above |
| AP-11 | **Debug code in shared config** | `paths.php` L49-63 `?debug_paths=1` | runs on every request that loads layout |

## 4. Tightly coupled components (verified)

| Pair | Coupling type | Evidence |
|------|--------------|----------|
| every page ↔ `mysqli` and SQL text | external / content coupling to the driver and schema | 28 `prepare` + 16 `query` sites in 20 files |
| every DB page ↔ global `$conn` | common (global) coupling | `db.php` L17 |
| `header.php` ↔ `auth.php` | hidden (non-include) dependency | `header.php` L25; 14 of 16 layout pages lack `auth.php` |
| `login_handler.php` ↔ `auth.php` | contract by convention (session keys) | writes at `login_handler.php` L57-60, reads at `auth.php` L24-50 |
| `register.php` ↔ `login_handler.php` | split business rule (role `staff` vs only `admin` may log in) | `register.php` L51, `login_handler.php` L49 |
| `validate.js` ↔ `header.php`/page markup | DOM id/class coupling | 4 JS sections, 2 unmatched |
| `issue.php` ↔ `return.php` ↔ `books/edit.php` | shared mutable rule (`available_copies`) with no single owner | writes at `issue.php` L53, `return.php` L49, `books/edit.php` L55-58 |
| every page ↔ folder depth | relative include paths | 54 of 60 include statements are relative |

## 5. Summary

The code base is a small, readable procedural application. The patterns that are genuinely present are **Transaction Script, Page Controller, Template View, layout partials, pessimistic locking and parameterized queries**. No object-oriented design pattern is present because there are no classes. The dominant anti-patterns are **Smart UI, copy-paste, global state and dead code**.
