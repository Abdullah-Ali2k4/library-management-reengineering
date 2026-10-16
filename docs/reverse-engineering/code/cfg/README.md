# Control Flow Analysis — Library Management System

Location: `docs/reverse-engineering/code/cfg/`

## 1. Method

1. **Select** representative functions from the important workflows (authentication, access control, issue/return, catalog maintenance, reporting).
2. **Construct** a Control Flow Graph for each (Mermaid `flowchart`, nodes tagged with source line numbers).
3. **Document** conditional branches, loops and return/exit paths in tables beside each graph.
4. **Identify** suspicious or unreachable code, only where the code, schema or a `grep` gives evidence. Each finding is marked **Confirmed** (proven by the code) or **Likely** (depends on runtime/environment).

**Notation:** `B` = basic block, `D` = decision, `T` = throw, `EXIT` = termination. Rounded red nodes are error/termination exits and green nodes are success exits. V(G) = decision predicates + 1, counting every `&&`/`||` operand.

## 2. Selected units

| # | Document | Source | Workflow | Why chosen | V(G) |
|---|----------|--------|----------|------------|------|
| 01 | [01_login_handler.md](01_login_handler.md) | `auth/login_handler.php` | Authentication | Guard clauses, short-circuit `&&`, optional branch, 5 exits | 9 |
| 02 | [02_issue_book.md](02_issue_book.md) | `issue_return/issue.php` | Issue book | Validation chain, transaction, `try/catch`, `throw` | 10 (15 with template) |
| 03 | [03_return_book.md](03_return_book.md) | `issue_return/return.php` | Return book | Transaction, two `throw` paths, refresh query | 7 (12) |
| 04 | [04_register.md](04_register.md) | `auth/register.php` | Registration | 10 sequential decisions, duplicate check | 13 (19) |
| 05 | [05_auth_helpers.md](05_auth_helpers.md) | `includes/auth.php` | Access control | `requireLogin`, `requireRole`, `currentUser` | 1-2 each |
| 06 | [06_book_delete.md](06_book_delete.md) | `books/delete.php` | Catalog delete | Early `die`, guard with `exit`, redirect | 4 |
| 07 | [07_issue_status.md](07_issue_status.md) | `issue_return/status.php` | Overdue reporting | Loop with 3-way branch | 8 |

## 3. Consolidated findings

| ID | Finding | Where | Status | Severity |
|----|---------|-------|--------|----------|
| F-01 | 16 pages never call `requireLogin()` (all CRUD, issue/return/status, search, test_connection). No auth node on any path. | 02, 03, 05, 06, 07 | Confirmed | High |
| F-02 | `requireRole()` is defined but never called (dead function, unreachable 403 path) | 05 | Confirmed | Low |
| F-03 | Registration creates only `staff`; login admits only `admin`. Staff login path is dead. | 01, 04 | Confirmed | High |
| F-04 | Password is trimmed at registration but not at login, so such passwords can never log in | 01, 04 | Confirmed | Medium |
| F-05 | Issue page dropdown queried before the transaction, so it shows stale copy counts after a successful issue | 02 | Confirmed | Low |
| F-06 | No Post-Redirect-Get: refreshing `issue.php` after success issues a duplicate copy (`return.php` is protected by its status check) | 02, 03 | Confirmed | Medium |
| F-07 | `return.php` L24 is the exact negation of L20 (2 of 4 path combos infeasible); L35/L38 reachable only by stale or crafted requests | 03 | Confirmed / Likely | Low |
| F-08 | Duplicate "issued records" query in `return.php` (L8-15 and L62-69) | 03 | Confirmed | Low |
| F-09 | State-changing delete over `GET`, no CSRF protection (books, authors, members) | 06 | Confirmed | High |
| F-10 | `ON DELETE CASCADE` means deleting a book erases its returned-loan history; the guard only checks active issues | 06 | Confirmed | Medium |
| F-11 | Unchecked `execute()`/`prepare()` results; behavior differs on PHP < 8.1 vs ≥ 8.1 (success redirect after failure, unreachable error branch, raw DB error shown) | 02, 04, 06 | Likely | Medium |
| F-12 | "Remember me" only extends the session cookie; server session GC may still expire it | 01 | Likely | Low |
| F-13 | Short-circuit order makes unknown-email logins faster than wrong-password logins (timing enumeration) | 01 | Likely | Low |
| F-14 | `header.php` shows "Guest" on pages that do not include `auth.php` | 05, 07 | Confirmed | Low |
| F-15 | `$book` variable shadowed in `issue.php` L92 | 02 | Confirmed | Low |
| F-16 | Overdue computed with PHP `date()` on one page and MySQL `CURDATE()` on another | 07 | Likely | Low |

## 4. How to read and export

- GitHub, GitLab and VS Code (with a Mermaid extension) render the diagrams directly.
- To export images: `mmdc -i file.mmd -o img/file.png` for each block.
- Line numbers refer to the files as uploaded in `library-management-reengineering-main.zip`.
