# Code Quality Assessment: Library Management System

**Scope:** all 27 PHP files (1937 lines), 2 SQL scripts, 2 CSS files and 1 JS file of the uploaded project.
**Method:** static inspection plus script-computed metrics (size, complexity, duplication, error-handling statistics). Findings cite source files, line numbers and function names. The code base has only 5 named functions; everything else is top-level script code, so "function references" for scripts name the script block (for example "transaction block, `issue.php` L31-62").
**Limits:** the code was not executed or linted (no PHP interpreter in the analysis environment). Items whose effect depends on the PHP version, `php.ini`, `sql_mode` or server configuration are marked **Likely**.
Related analyses: [`cfg/`](cfg/README.md) (control flow), [`dfg/`](dfg/README.md) (data flow), [`call-graphs/`](call-graphs/README.md) (dependencies), [`codebase-inventory.md`](codebase-inventory.md).

## 1. Executive summary

- The code is small and readable, and it gets the basics of SQL safety and output escaping right: **28 prepared statements, 0 interpolated queries, 63 `htmlspecialchars` calls**.
- The most serious problem is **access control**: `requireLogin()` is called by only 2 pages, so **16 pages that read, change or delete data are open to anyone** (QA-01), and deletions are triggered by plain GET links (QA-02).
- Default deployment is unsafe: privileged database account without a password, a published default admin login, and a debug path-disclosure switch (QA-03, QA-06).
- Data integrity relies on application code rather than the database: `books.available_copies` is a derived value with four writers and no constraints (QA-05); a blank ISBN breaks a `UNIQUE` column (QA-10); deleting a book or member cascades away loan history (QA-11).
- Design quality is limited by **mixed responsibilities** (SQL and HTML together in 18 of 27 PHP files), **duplication (20.3 % of PHP lines are repeated elsewhere)** and **no tests**.

Findings: **28** (Critical 1, High 4, Medium 12, Low 11). Section 8 is the prioritized register.

## 2. Method, severity and priority

| Severity | Meaning |
|----------|---------|
| Critical | unauthorized access or data loss possible on a default install with no special skill |
| High | serious security, integrity or functional failure with a plausible trigger |
| Medium | real weakness or defect whose impact is limited or needs a condition |
| Low | hygiene, minor defect or maintainability cost |

Priority combines severity, impact and effort: **P1** fix first (Critical/High), **P2** next (Medium with clear impact), **P3** planned, **P4** opportunistic. Confidence is **Confirmed** (proven in the code) or **Likely** (environment-dependent). Effort: S (hours), M (days), L (a week or more).

## 3. Quantitative metrics (script-computed)

### 3.1 Size by module

| Module | PHP files | Lines | Avg lines/file | Largest file |
|--------|----------:|------:|---------------:|--------------|
| project root | 3 | 177 | 59 | 74 (`index.php`) |
| `auth/` | 4 | 342 | 86 | 131 (`auth/register.php`) |
| `authors/` | 4 | 233 | 58 | 92 (`authors/edit.php`) |
| `books/` | 4 | 349 | 87 | 146 (`books/edit.php`) |
| `members/` | 4 | 276 | 69 | 104 (`members/edit.php`) |
| `issue_return/` | 3 | 294 | 98 | 120 (`issue_return/issue.php`) |
| `includes/` | 3 | 176 | 59 | 110 (`includes/header.php`) |
| `config/` | 2 | 90 | 45 | 64 (`config/paths.php`) |
| **PHP total** | 27 | 1937 | 72 | |

### 3.2 Complexity (approximate cyclomatic complexity per file)

V(G) here = number of decision keywords in the PHP code of the file (`if`, `elseif`, `while`, `foreach`, `for`, `case`, `catch`, `&&`, `||`, ternary `?`) + 1, computed over the whole file including its template part. The hand-built control flow graphs in `cfg/` use the same counting rule and agree with this for the files they cover (for example `issue.php` 15, `return.php` 12, `register.php` 19, `login_handler.php` 9, `status.php` 8, `books/delete.php` 4).

| File | Approx. V(G) | Lines | Mixes SQL and HTML |
|------|-------------:|------:|:------------------:|
| `books/add.php` | 21 | 112 | yes |
| `books/edit.php` | 21 | 146 | yes |
| `auth/register.php` | 19 | 131 | yes |
| `issue_return/issue.php` | 15 | 120 | yes |
| `members/edit.php` | 15 | 104 | yes |
| `members/add.php` | 14 | 84 | yes |
| `authors/edit.php` | 13 | 92 | yes |
| `authors/add.php` | 12 | 71 | yes |
| `issue_return/return.php` | 12 | 113 | yes |
| `auth/login_handler.php` | 9 | 91 | no |

No file is extreme in absolute terms, but the most complex units are the add/edit form handlers, the registration script and the circulation scripts, and each mixes input handling, rules, SQL and HTML.

### 3.3 Named functions (the only functions in the code base)

| Function | File | Lines | Length | V(G) |
|----------|------|-------|-------:|-----:|
| `isLoggedIn()` | `includes/auth.php` | L23-L25 | 3 | 1 |
| `requireLogin()` | `includes/auth.php` | L27-L32 | 6 | 2 |
| `requireRole()` | `includes/auth.php` | L34-L40 | 7 | 2 |
| `currentUser()` | `includes/auth.php` | L42-L52 | 11 | 2 |
| `nav_active()` | `includes/header.php` | L17-L23 | 7 | 4 |

All five functions are tiny. **There are no large functions; the large units are whole scripts** (top-level code), the biggest being `books/edit.php` (146 lines), `auth/register.php` (131 lines), `issue_return/issue.php` (120 lines).

### 3.4 Duplication

Method: lines normalized (whitespace removed; blank, brace-only and comment lines ignored); a line counts as duplicated when it belongs to a block of at least 5 consecutive normalized lines that also occurs elsewhere in the PHP code.

- Duplicated lines: **239 of 1179 ( 20.3 % )**.

Most duplicated files:

| File | Duplicated share | Normalized lines |
|------|-----------------:|-----------------:|
| `authors/add.php` | 52 % | 42 |
| `authors/delete.php` | 45 % | 11 |
| `members/add.php` | 45 % | 53 |
| `books/add.php` | 44 % | 70 |
| `members/delete.php` | 43 % | 23 |
| `books/delete.php` | 43 % | 23 |
| `authors/edit.php` | 37 % | 54 |
| `members/edit.php` | 34 % | 64 |

Most similar file pairs (approximate shared lines):

| File A | File B | Approx. shared lines |
|--------|--------|---------------------:|
| `books/add.php` | `books/edit.php` | 24 |
| `members/add.php` | `members/edit.php` | 17 |
| `authors/add.php` | `authors/edit.php` | 15 |
| `books/add.php` | `members/add.php` | 13 |
| `authors/add.php` | `members/add.php` | 13 |
| `authors/add.php` | `books/add.php` | 13 |
| `books/edit.php` | `members/edit.php` | 11 |
| `authors/edit.php` | `members/edit.php` | 11 |

Verified clones that matter semantically:

| Clone | Locations | Why it matters |
|-------|-----------|----------------|
| Same JOIN query twice in one script | `issue_return/return.php` L8-15 and L62-69 | one change must be made twice |
| Transaction skeleton (begin, locking read, two writes, commit/rollback) | `issue_return/issue.php` L31-62, `issue_return/return.php` L25-59 | business rule for `available_copies` duplicated |
| Active-loan guard before delete | `books/delete.php` L11-25, `members/delete.php` L11-25 | same rule, two copies |
| ISBN uniqueness check | `books/add.php` L28-38, `books/edit.php` L41-51 | add and edit validation drift apart |
| Add / edit form validation | `books/`, `authors/`, `members/` `add.php` vs `edit.php` | six near-copies |

### 3.5 Error-handling statistics

| Metric | Value | Note |
|--------|------:|------|
| `->execute()` calls | 28 | |
| ...of which bare statements (result ignored) | 21 | 6 of them sit inside the `try` blocks of `issue.php` and `return.php` |
| `->prepare()` calls | 28 | |
| ...of which explicitly tested for failure | 1 | |
| `die()` calls | 11 | print raw text to the browser |
| `exit` statements | 14 | mostly after `header("Location")` |
| Files with `try/catch` | 2 (`issue_return/issue.php`, `issue_return/return.php`) | |
| `throw` statements | 3 | |
| `error_reporting` / `ini_set` / `mysqli_report` / error handlers | 0 | none configured |
| Logging calls (`error_log`) | 0 | no logging |

## 4. Security concerns

| ID | Concern | Evidence (file / function) | Severity | Confidence |
|----|---------|----------------------------|----------|------------|
| QA-01 | **Missing authentication.** `requireLogin()` (`includes/auth.php` L27-32) is called only by `index.php` L4 and `books/view.php` L4. Unguarded: `authors/{add,delete,edit,view}.php`, `members/{add,delete,edit,view}.php`, `books/{add,delete,edit}.php`, `issue_return/{issue,return,status}.php`, `search.php`, `test_connection.php` (16 pages, `grep`-verified). `header.php` L5 documents the convention but the pages do not follow it. | **Critical** | Confirmed |
| QA-02 | **State-changing GET without CSRF defence.** `books/delete.php`, `authors/delete.php`, `members/delete.php` read `$_GET['id']` and delete on a plain link. | High | Confirmed |
| QA-03 | **Hard-coded privileged credentials.** `config/db.php` L11-14: user `root`, empty password. Default admin account and password published in `README.md` and `database/user_schema.sql`. | High | Confirmed |
| QA-06 | **Information disclosure.** `config/paths.php` L54-63 prints server paths to anyone with `?debug_paths=1`; `config/db.php` L21 sends the raw connection error to the browser; `test_connection.php` is public and lists books; exception text is echoed at `issue.php` L62 and `return.php` L58. | Medium | Confirmed (exception text: Likely, PHP ≥ 8.1) |
| QA-07 | **No CSRF tokens** on any POST form (`grep csrf` finds nothing). | Medium | Confirmed |
| QA-08 | **No brute-force protection; weak password rule.** No failed-attempt counter in `login_handler.php`; minimum password length 6 (`register.php` L31). | Medium | Confirmed |
| QA-22 | **Session cookie hardening is left to `php.ini`.** No `session_set_cookie_params`; only the optional remember-me cookie sets `samesite=Lax` and copies `secure`/`httponly` from the defaults (`login_handler.php` L66-77). | Low | Likely |
| QA-23 | **User enumeration and open sign-up.** `register.php` tells whether a username or e-mail is already registered (L44); anyone can create accounts. | Low | Confirmed |
| QA-24 | **Third-party asset without Subresource Integrity.** Font Awesome from a CDN (`header.php` L35, `register.php` L78). | Low | Confirmed |

**Positive security findings (verified):** all user data reaches SQL through prepared statements; no `query()` call interpolates a variable; every user or database string is passed through `htmlspecialchars` or an `(int)` cast before output; `session_regenerate_id(true)` after login (`login_handler.php` L54); passwords stored with `password_hash`. No SQL-injection or XSS path was found.

## 5. Functional and data-integrity defects

| ID | Defect | Evidence (file / function) | Severity | Confidence |
|----|--------|----------------------------|----------|------------|
| QA-04 | **Staff accounts can register but never log in.** Registration fixes `role = 'staff'` (`register.php` L51); login accepts only `'admin'` (`login_handler.php` L49). `requireRole()` is never called. | High | Confirmed |
| QA-05 | **`available_copies` has four writers and no guard.** Initial value `books/add.php` L43, `-1` in `issue.php` L53, `+1` in `return.php` L49, any value in `books/edit.php` L55-58; no CHECK constraint (`library_db.sql` L30-31); `return.php` has no upper bound; `edit.php` can set `total_copies` below copies on loan. | High | Confirmed |
| QA-09 | **No Post/Redirect/Get after a successful issue.** `issue.php` re-renders after the commit (L58-72); a browser refresh re-submits and issues another copy. (`return.php` is protected by its status check, L38.) | Medium | Confirmed |
| QA-10 | **Blank ISBN breaks the UNIQUE column.** The uniqueness check is skipped for `''` but `''` is still inserted (`books/add.php` L28-46, `books/edit.php` L41-58); `isbn` is `UNIQUE` (`library_db.sql` L28), so a second book without an ISBN fails with a generic error. | Medium | Confirmed |
| QA-11 | **Cascade silently deletes loan history.** `issued_books` FKs are `ON DELETE CASCADE` (`library_db.sql` L52-53); the guards in `books/delete.php` L11-25 and `members/delete.php` L11-25 block only active loans. | Medium | Confirmed |
| QA-12 | **Password transformed differently on two paths.** Trimmed at registration (`register.php` L22) but not at login (`login_handler.php` L14); such passwords can never verify. | Medium | Confirmed |
| QA-18 | **No server-side length validation** for `VARCHAR` columns (title 150, category 50, isbn 20, phone 20, username 50, email 100); only the password length is checked; no `maxlength` in the HTML. | Low | Confirmed (absence) |
| QA-19 | **Stale dropdown after issue.** The available-books list is read at `issue.php` L8-9, before the update at L53; `return.php` re-queries (L62-69). | Low | Confirmed |
| QA-26 | **Two sources of "today".** PHP `date()` (`status.php` L5) vs SQL `CURDATE()` (`index.php` L11) can disagree around midnight if time zones differ. | Low | Likely |
| QA-28 | **"Remember me" extends only the cookie**; the server-side session still obeys `session.gc_maxlifetime` (`login_handler.php` L63-77). | Low | Likely |

## 6. Error handling, dead code and design-level quality

### 6.1 Error-handling weaknesses

| ID | Weakness | Evidence | Severity | Confidence |
|----|----------|----------|----------|------------|
| QA-13 | **DB call results are largely unchecked.** 21 of 28 `->execute()` calls are bare statements (6 of them inside the `try` blocks of `issue.php` and `return.php`); 1 of 28 `prepare` calls is tested for failure. The app sets no `mysqli_report`, `error_reporting`, `ini_set` or error handler, so a failure either goes unnoticed (PHP < 8.1) or becomes an uncaught exception (PHP ≥ 8.1). Example: `books/delete.php` redirects with `deleted=1` after an unchecked delete (L30-35). | Medium | Confirmed (absence) / Likely (effect) |
| QA-14 | **Inconsistent strategies, no logging.** 11 `die()` calls print raw text; form pages collect messages in `$errors`; `try/catch` exists only in `issue.php` and `return.php`; nothing is logged. | Medium | Confirmed |

### 6.2 Dead or misleading code

| ID | Item | Evidence | Severity |
|----|------|----------|----------|
| QA-21 | `requireRole()` has no caller (`includes/auth.php` L34-40) | `grep` | Low |
| QA-21 | `assets/js/validate.js` sections for `#userMenu` / `#userMenuTrigger` and `.password-toggle` match no markup in any PHP or CSS file; the login page uses its own inline script for `#togglePassword` | `grep` | Low |
| QA-21 | `?deleted=1` is added by three delete scripts and read by no file | `grep` | Low |
| QA-21 | `users.created_at` is referenced by no PHP file | `grep` | Low |
| QA-21 | `test_connection.php` and the `config/paths.php` L49-63 debug tool are leftovers | file text | Low |

### 6.3 Mixed responsibilities, duplication and structure

| ID | Issue | Evidence | Severity | Confidence |
|----|-------|----------|----------|------------|
| QA-15 | **No separation of layers.** 18 of 27 PHP files contain both SQL and HTML; every form handler does parsing, validation, business rules, SQL and rendering; `includes/header.php` also defines a function and looks up the current user; `config/paths.php` mixes URL logic with a debug output handler. | Medium | Confirmed |
| QA-16 | **Duplication** (section 3.4): 20.3 % of PHP lines are repeated. | Medium | Confirmed |
| QA-20 | **Hidden dependency.** `header.php` L25 calls `currentUser()` behind `function_exists` without including `auth.php`; 14 of 16 pages that include `header.php` show "Guest" instead of the user. | Low | Confirmed |
| QA-25 | **Inconsistent conventions.** Three page-head variants (`header.php`, `auth/index.php`, `register.php`); two include styles (`__DIR__` 6, relative 54); status strings `'issued'` (10 occurrences) and `'returned'` (3) scattered. | Low | Confirmed |
| QA-17 | **No tests, manifest or `.gitignore`.** | Medium | Confirmed |
| QA-27 | **Largest and most complex units** are the form handlers (section 3.2), where the business rules live. | Low | Confirmed |

## 7. Strengths

| Strength | Evidence |
|----------|----------|
| Consistent prepared statements and output escaping | 28 prepared statements, 63 `htmlspecialchars` calls |
| Real transactions with row locks for circulation | `issue.php` L31-62, `return.php` L25-59 |
| Session id regenerated on login; `password_hash`/`password_verify` | `login_handler.php` L50, L54; `register.php` L50 |
| Clear folder-per-entity layout | `books/`, `authors/`, `members/`, `issue_return/` |
| No circular include dependencies | `call-graphs/01_include_dependency_graph.md` |
| Schema with explicit PK/FK/UNIQUE and FK actions; seed data consistent with the counter rule | `database/library_db.sql` |

## 8. Prioritized findings register

Sorted by priority, then severity. **Source** points to the detailed analysis (CFG F-, DFG DF-, call-graph CG-, design AL-/AP-/SC- ids).

| Pri | ID | Finding | Category | Severity | Impact | Confidence | Effort | Source |
|-----|----|---------|----------|----------|--------|------------|--------|--------|
| P1 | QA-01 | No authentication on 16 pages | Security | **Critical** | anyone can read, change, delete library data | Confirmed | S-M | CFG F-01, DF-06, CG-02 |
| P1 | QA-02 | Destructive GET deletes, no CSRF | Security | High | one click or image tag deletes records | Confirmed | S | CFG F-09 |
| P1 | QA-03 | Root/empty DB password; published default admin | Security | High | full DB compromise on default install | Confirmed | S | DF-11 |
| P1 | QA-04 | Staff role can register but never log in | Functional | High | sign-up flow and role feature broken | Confirmed | S | CFG F-03, CG-10 |
| P1 | QA-05 | `available_copies` drift (4 writers, no constraint) | Data integrity | High | wrong availability, blocked or over-issued loans | Confirmed | M | DF-02, SC-1 |
| P2 | QA-06 | Path, DB-error and diagnostic disclosure | Security | Medium | attacker reconnaissance | Confirmed | S | DF-10 |
| P2 | QA-07 | No CSRF tokens on POST forms | Security | Medium | forged state changes | Confirmed | S-M | DF-05 |
| P2 | QA-08 | No login throttling; password minimum 6 | Security | Medium | credential guessing | Confirmed | S | DF-13 |
| P2 | QA-09 | No PRG: refresh re-issues a book | Functional | Medium | duplicate loans | Confirmed | S | CFG F-06 |
| P2 | QA-10 | Blank ISBN violates UNIQUE | Data integrity | Medium | second ISBN-less book cannot be saved | Confirmed | S | DF-03, SC-3 |
| P2 | QA-11 | Cascade deletes loan history | Data integrity | Medium | irreversible audit loss | Confirmed | S-M | CFG F-10, SC-5 |
| P2 | QA-12 | Password trim mismatch | Functional | Medium | some accounts locked out | Confirmed | S | DF-14 |
| P2 | QA-13 | Unchecked DB results, no error configuration | Error handling | Medium | silent failure or uncaught exception | Confirmed / Likely | S-M | DF-11 note |
| P2 | QA-14 | Inconsistent error handling, no logging | Error handling | Medium | hard to diagnose | Confirmed | M | AL-7 |
| P2 | QA-15 | SQL and HTML mixed in 18 files | Design | Medium | high cost of change, untestable | Confirmed | L | AL-1, CG-17 |
| P2 | QA-16 | 20.3 % duplicated lines | Maintainability | Medium | defects fixed in several places | Confirmed | M | CG-11, CG-12, CG-18 |
| P2 | QA-17 | No tests, manifest, `.gitignore` | Process | Medium | no regression safety | Confirmed | M | AL-4 |
| P3 | QA-18 | No length validation | Validation | Low | truncation or generic failures | Confirmed | S | DF-01, SC-6 |
| P3 | QA-19 | Stale issue dropdown | Functional | Low | confusing availability display | Confirmed | S | DF-09 |
| P3 | QA-20 | Hidden `header.php` → `auth.php` dependency | Design | Low | "Guest" shown on 14 pages | Confirmed | S | CG-02 |
| P3 | QA-21 | Dead code (function, JS, flag, column, pages) | Maintainability | Low | misleads readers | Confirmed | S | CG-22..26 |
| P3 | QA-22 | Session cookie flags left to `php.ini` | Security | Low | depends on server config | Likely | S | none |
| P3 | QA-23 | User enumeration; open registration | Security | Low | account probing | Confirmed | S | none |
| P3 | QA-27 | Large, complex form-handler scripts | Complexity | Low | rules hard to follow | Confirmed | M | CFG metrics |
| P4 | QA-24 | CDN asset without SRI | Security | Low | supply-chain exposure | Confirmed | S | CG-20 |
| P4 | QA-25 | Inconsistent conventions, magic strings | Maintainability | Low | slower changes | Confirmed | S-M | CG-04, CG-08 |
| P4 | QA-26 | PHP date vs SQL `CURDATE()` | Functional | Low | possible midnight mismatch | Likely | S | DF-16 |
| P4 | QA-28 | Remember-me bounded by session GC | Functional | Low | feature may not last 30 days | Likely | S | F-12 |

## 9. Recommended direction (not implemented)

| Order | Action | Fixes |
|-------|--------|-------|
| 1 | Enforce login centrally for every non-public page; make deletes POST-only with CSRF tokens | QA-01, QA-02, QA-07 |
| 2 | Remove the debug handler and diagnostic page; move DB settings out of source; stop publishing default credentials | QA-03, QA-06 |
| 3 | Decide the role model (admin and staff) and fix registration or login | QA-04 |
| 4 | Give `available_copies` a single owner plus database constraints; treat blank ISBN as NULL; keep loan history on delete | QA-05, QA-10, QA-11 |
| 5 | Redirect after every successful POST; unify the password handling; add login throttling | QA-09, QA-12, QA-08 |
| 6 | Introduce one error-handling policy, logging, and a data-access layer; extract the shared transaction and validation code | QA-13..QA-16 |
| 7 | Add tests for circulation rules and validation; add `.gitignore` and a dependency manifest; clean up dead code | QA-17, QA-21 |

## 10. Traceability

| Topic | Document |
|-------|----------|
| Control flow, branches, unreachable paths | [`cfg/README.md`](cfg/README.md) |
| Data paths, taint (SQL/XSS) check, def-use tables | [`dfg/README.md`](dfg/README.md) |
| Include graph, call graphs, DB access points, coupling | [`call-graphs/README.md`](call-graphs/README.md) |
| Repository inventory | [`codebase-inventory.md`](codebase-inventory.md) |
