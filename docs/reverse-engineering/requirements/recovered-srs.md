# Recovered Software Requirements Specification (SRS)

**Project:** Library Management System (legacy PHP/MySQL)
**Reverse-Engineering Level:** Requirements
**Related Issue:** Abdullah-Ali2k4/library-management-reengineering#3 — Requirements-Level Reverse Engineering
**Date:** 2026-10-16
**Status:** Draft for review
**Branch:** `issue-3/requirements-reverse-engineering`

---

## 1. Purpose and Scope

This document recovers the **functional requirements** of the existing Library
Management System from available evidence. It is part of a requirements-level
reverse-engineering effort and covers:

- implemented features expressed as testable functional requirements,
- identified user roles and their effective permissions,
- missing, incomplete, or apparently unused functionality,
- conflicts and ambiguities found between code, schema, and documentation.

It does **not** prescribe the redesigned system; forward-engineering decisions
belong to later reengineering phases.

## 2. Recovery Methodology and Evidence Sources

| Source | Artifact | Use |
|---|---|---|
| Source code | All `.php`, `.js` files in repository | Primary evidence of actual behavior |
| Database schema | `database/library_db.sql`, `database/user_schema.sql` | Structural constraints, seed data |
| Documentation | `README.md`, in-code comments | Intended features (may be stale) |
| Observed behavior | Static analysis of control flow | Verification of claims |

**Evidence rules applied (per issue constraints):**

1. The existing implementation is treated as evidence of *current behavior*,
   **not** proof that a behavior is correct or intended.
2. No requirement is invented for features that cannot be verified.
3. Uncertainty, conflicts, and unverifiable claims are recorded explicitly.
4. No stakeholder was available for this recovery; every requirement that
   would benefit from stakeholder confirmation is flagged accordingly.

**Verification status vocabulary:**

| Status | Meaning |
|---|---|
| Verified | Directly evidenced in source code or schema (location cited) |
| Partially verified | Some evidence exists, but implementation is incomplete or inconsistent |
| Conflicting | Two or more evidence sources disagree |
| Absence confirmed | Codebase-wide search found no implementation |
| Assumed | Inferred from context; requires stakeholder validation |

**Confidence:** High / Medium / Low — confidence that the recovered statement
reflects an actual requirement of the system (not merely a code artifact).

**Requirement identifier scheme:** `FR-###` (functional requirement),
`GAP-###` (missing/incomplete functionality). Business rules, non-functional
requirements, and use cases are documented in companion files with prefixes
`BR-`, `NFR-`, and `UC-`.

## 3. System Overview

A web-based library management application for small libraries. Staff use it
to maintain a catalog of books and authors, register library members, and
process the borrowing (issue) and return of books, with visibility into
currently issued and overdue items.

- **Technology:** procedural PHP (mysqli), MySQL, vanilla HTML/CSS/JS
- **Architecture:** page-per-file; no framework or router; session-based auth
- **Data entities:** `users`, `authors`, `books`, `members`, `issued_books`

## 4. Actors and Roles

| ID | Actor | Description | Evidence | Status |
|---|---|---|---|---|
| A1 | Administrator | User with `role = 'admin'` in `users`; the only role that can log in | `auth/login_handler.php:47-51`; seed account `database/user_schema.sql:26-32` | Verified |
| A2 | Staff | User with `role = 'staff'`; created by the registration page but **cannot log in** | `auth/register.php:51` vs `auth/login_handler.php:49` | Conflicting |
| A3 | Unauthenticated visitor | Browser client with no session; can currently reach most CRUD pages | Absence of `requireLogin()` in 16 of 18 pages | Verified (gap) |
| A4 | Library member (borrower) | Person who borrows books; a **data entity only**, with no system login | `database/library_db.sql:36-41` | Verified |

> **Note:** The README describes the system as having "user login, registration
> and logout"; it does not document the admin/staff distinction or the fact that
> registered staff accounts cannot authenticate (see Conflict C-01).

## 5. Feature Inventory

| Feature | Module | Implemented? | Notes |
|---|---|---|---|
| Login (email + password) | `auth/` | Yes | Admin role only |
| Registration | `auth/` | Yes | Creates `staff` accounts that cannot log in (C-01) |
| Logout | `auth/` | Yes | Destroys session; does not clear remember-me cookie |
| Remember me | `auth/` | Partial | Extends session cookie only; no persistent token |
| Dashboard with counters | `index.php` | Yes | 5 statistics; login enforced |
| Book catalog list | `books/view.php` | Yes | Login enforced |
| Add / edit / delete books | `books/` | Yes | **No login enforcement** |
| Author list / add / edit / delete | `authors/` | Yes | **No login enforcement** |
| Member list / add / edit / delete | `members/` | Yes | **No login enforcement** |
| Issue book | `issue_return/issue.php` | Yes | Transactional; **no login enforcement** |
| Return book | `issue_return/return.php` | Yes | Transactional; **no login enforcement** |
| Issued/overdue monitor | `issue_return/status.php` | Yes | **No login enforcement** |
| Search books | `search.php` | Yes | Title/category/author-name; **no login enforcement** |
| Password change / reset | — | **No** | Absence confirmed; documentation refers to it (GAP-001) |
| User management UI | — | **No** | Absence confirmed (GAP-002) |
| Role-based authorization in app | — | **No** | `requireRole()` defined but never called (GAP-003) |
| Overdue fines / notifications | — | **No** | Absence confirmed (GAP-008) |
| Audit logging | — | **No** | Absence confirmed (GAP-011) |
| Connection test utility | `test_connection.php` | Yes | Documented as temporary; still committed (GAP-010) |

## 6. Functional Requirements

Statements use "shall" and describe **verified current capabilities** unless
marked otherwise. Evidence locations refer to the repository at the time of
analysis.

### 6.1 Authentication (`auth/`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-001 | The system shall allow a user to authenticate with an email address and password submitted via the login form. | `auth/index.php:63-94`; `auth/login_handler.php:13-50` | Verified | High |
| FR-002 | The system shall permit authentication **only** for users whose role is `admin`. | `auth/login_handler.php:47-51` | Verified | High |
| FR-003 | A failed login attempt shall display a single generic error ("Invalid email or password.") that does not reveal whether the email or the password was wrong. | `auth/login_handler.php:84-90` | Verified | High |
| FR-004 | The system shall allow any visitor to register a new account; new accounts shall be assigned the role `staff`. | `auth/register.php:19-62` (role forced at `:51`) | Verified — conflicts with FR-002 (C-01) | High |
| FR-005 | Registration shall require a username, an email address, and a password of at least 6 characters; the password shall be confirmed; the email shall be syntactically valid; the username and email shall be unique. | `auth/register.php:25-46`; schema uniqueness `database/user_schema.sql:10-11` | Verified | High |
| FR-006 | The system shall log a user out, clear the session data, and destroy the session. | `auth/logout.php:1-6` | Verified | High |
| FR-007 | Upon successful login the system shall regenerate the session identifier (session-fixation protection). | `auth/login_handler.php:54` | Verified | High |
| FR-008 | If "Remember me" is selected, the session cookie lifetime shall be extended to approximately 30 days. | `auth/login_handler.php:63-78` | Verified (limited implementation — no persistent token; cookie not cleared on logout) | High |
| FR-009 | The login page shall provide a control to show or hide the entered password. | `auth/index.php:78-83`, `:105-111` | Verified | High |
| FR-010 | Every page that requires authentication shall redirect an unauthenticated visitor to the login page. | Helper defined `includes/auth.php:27-32`; invoked only in `index.php:4` and `books/view.php:4` | Partially verified — enforced on 2 of 18 pages (GAP-005) | High |

### 6.2 Dashboard (`index.php`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-011 | The dashboard shall display the total number of books in the catalog. | `index.php:7`, `:23-27` | Verified | High |
| FR-012 | The dashboard shall display the total number of authors. | `index.php:8`, `:28-32` | Verified | High |
| FR-013 | The dashboard shall display the total number of registered members. | `index.php:9`, `:33-37` | Verified | High |
| FR-014 | The dashboard shall display the number of books currently issued (records with status `issued`). | `index.php:10`, `:38-42` | Verified | High |
| FR-015 | The dashboard shall display the number of overdue books (status `issued` and due date earlier than the current date). | `index.php:11`, `:43-47` | Verified | High |
| FR-016 | The dashboard shall provide quick-action links to add a book, view books, issue a book, return a book, and search. | `index.php:50-69` | Verified | High |

### 6.3 Book Management (`books/`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-017 | The system shall list all books with title, author name, ISBN, category, and available/total copy counts. | `books/view.php:8-49` | Verified | High |
| FR-018 | An editor shall be able to add a book; title and author shall be mandatory; ISBN and category shall be optional. | `books/add.php:10-54`, `:78-107` | Verified | High |
| FR-019 | A newly added book shall start with `available_copies` equal to `total_copies`. | `books/add.php:42-43` | Verified | High |
| FR-020 | An editor shall be able to modify all book fields, including a manual adjustment of `available_copies`, subject to validation (see FR-021, FR-022). | `books/edit.php:18-66` | Verified | High |
| FR-021 | `total_copies` shall be an integer of at least 1, and `available_copies` shall be a non-negative integer that does not exceed `total_copies`. | `books/edit.php:32-40`; `books/add.php:25-27` | Verified | High |
| FR-022 | If an ISBN is provided, it shall be unique across the catalog (uniqueness checked excluding the book being edited). | `books/add.php:28-38`; `books/edit.php:41-51`; DB constraint `database/library_db.sql:29` | Verified | High |
| FR-023 | A book shall not be deletable while it has at least one active (unreturned) issue record; the system shall explain why deletion is refused. | `books/delete.php:10-25` | Verified | High |
| FR-024 | Deleting a book shall remove it from the catalog; historical issue records for the book shall also be removed by the database (cascade). | `database/library_db.sql:52` (`ON DELETE CASCADE`); app guard covers only active issues | Verified — questionable intent (C-04) | High |

### 6.4 Author Management (`authors/`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-025 | The system shall list all authors with full name, a bio excerpt (truncated to 80 characters), and the number of books written. | `authors/view.php:5-44` | Verified | High |
| FR-026 | An editor shall be able to add an author; first and last name shall be mandatory; the bio shall be optional. | `authors/add.php:7-30` | Verified | High |
| FR-027 | An editor shall be able to modify an author's first name, last name, and bio. | `authors/edit.php:13-36` | Verified | High |
| FR-028 | An author shall be deletable at any time; the author's books shall remain in the catalog with no linked author. | `authors/delete.php:10-16`; FK `ON DELETE SET NULL` `database/library_db.sql:32` | Verified | High |

### 6.5 Member Management (`members/`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-029 | The system shall list all members with full name, email, phone, and their count of currently issued books. | `members/view.php:5-46` | Verified | High |
| FR-030 | An editor shall be able to add a member; full name and a syntactically valid, unique email shall be mandatory; phone shall be optional. | `members/add.php:7-42`; DB constraints `database/library_db.sql:38-40` | Verified | High |
| FR-031 | An editor shall be able to modify a member's details; the email shall remain unique among other members. | `members/edit.php:13-48` | Verified | High |
| FR-032 | A member shall not be deletable while they have at least one active issue record; the system shall explain why deletion is refused. | `members/delete.php:10-25` | Verified | High |
| FR-033 | Deleting a member shall remove them from the system; their historical issue records shall also be removed by the database (cascade). | `database/library_db.sql:53` (`ON DELETE CASCADE`); app guard covers only active issues | Verified — questionable intent (C-04) | High |

### 6.6 Borrowing — Issue (`issue_return/issue.php`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-034 | The issue form shall offer only books that currently have at least one available copy, and all registered members. | `issue_return/issue.php:8-9`, `:88-107` | Verified | High |
| FR-035 | An editor shall be able to issue a selected book to a selected member by specifying a due date. | `issue_return/issue.php:11-65` | Verified | High |
| FR-036 | The due date shall not be earlier than the current date; the form shall default to today + 14 days. | `issue_return/issue.php:22-26`, `:109-112` | Verified (14-day default is an assumption about intent — see BR-031) | High |
| FR-037 | Issuing shall be atomic: the system shall re-check availability under a row lock, insert the issue record, and decrement `available_copies`, committing or rolling back as a unit. | `issue_return/issue.php:28-64` | Verified | High |
| FR-038 | If the selected book has no available copy at the moment of confirmation, the system shall refuse the issue and roll back. | `issue_return/issue.php:36-44`, `:60-62` | Verified | High |

### 6.7 Returning — Return (`issue_return/return.php`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-039 | The return form shall list only records currently in status `issued`, showing book title, borrower name, and due date. | `issue_return/return.php:8-15`, `:92-108` | Verified | High |
| FR-040 | An editor shall be able to mark a selected issue record as returned; the system shall atomically set status to `returned`, record today's return date, and increment the book's `available_copies`. | `issue_return/return.php:24-59` | Verified | High |
| FR-041 | A record that is already returned shall not be returnable again; the system shall reject the attempt. | `issue_return/return.php:38-40` | Verified | High |

### 6.8 Issued/Overdue Monitoring (`issue_return/status.php`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-042 | The status page shall list all issue records with book, member, issue date, due date, return date, and a status label (`Issued`, `Overdue`, or `Returned`). | `issue_return/status.php:7-55` | Verified | High |
| FR-043 | Records shall be ordered with currently issued records first, then by due date ascending. | `issue_return/status.php:12-14` | Verified | High |
| FR-044 | A currently issued record whose due date has passed shall be labeled "Overdue" and visually highlighted. | `issue_return/status.php:33-35`, `:44-45` | Verified | High |

### 6.9 Search (`search.php`)

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-045 | A user shall be able to search the catalog by a substring of the book title, category, author first name, or author last name. | `search.php:8-23` | Verified | High |
| FR-046 | Search results shall show title, author, category, available/total copies, and a link to edit the book. | `search.php:36-64` | Verified | High |

### 6.10 Utilities

| ID | Requirement | Evidence | Status | Confidence |
|---|---|---|---|---|
| FR-047 | A connection-test page shall report whether the database connection works and list stored book titles. | `test_connection.php:15-30` | Verified — documented as temporary; still present (GAP-010) | High |

## 7. Conflicts and Ambiguities

| ID | Conflict / Ambiguity | Evidence | Impact |
|---|---|---|---|
| C-01 | **Registration vs login policy.** Registration creates `staff` accounts (`auth/register.php:51`), but login accepts only `admin` accounts (`auth/login_handler.php:49`). A registered user can never sign in. | Both files | Registration is effectively dead functionality; actor A2 is unusable |
| C-02 | **Documentation vs capability.** The seed file instructs users to "Change this password after your first login" (`database/user_schema.sql:20-24`), but no password-change feature exists. | `user_schema.sql`; absence confirmed codebase-wide | Documented workflow cannot be performed (GAP-001) |
| C-03 | **README vs login form.** The README tells users to log in with "Username: admin" (`README.md`, Getting Started), while the login form accepts email only. | `README.md`; `auth/index.php:69` | Documentation drift; minor usability confusion |
| C-04 | **Deletion semantics.** The application blocks deletion of books/members with *active* issues, yet the schema cascades deletion of *all* historical issue records. Whether history should survive deletion is unverifiable from code. | `books/delete.php:11-25`; `members/delete.php:11-25`; `library_db.sql:52-53` | Potential silent data loss; needs stakeholder decision |
| C-05 | **Availability model.** `available_copies` is maintained by issue/return transactions, yet the book-edit form lets an editor overwrite it manually, with no reconciliation against open issue records. | `issue_return/issue.php:53-56`; `books/edit.php:24`, `:57-58` | Counts can drift from reality; intent ambiguous |
| C-06 | **Access-control intent.** The login page states "This area is restricted to administrators only" and `requireRole()` exists (`includes/auth.php:34-40`), but no page ever calls `requireRole()`, and 16 of 18 pages require no login at all. | `auth/index.php:51-57`; `includes/auth.php:34`; absence of calls | Original authorization intent is unverifiable |
| C-07 | **README security claims.** The README warns about SQL-injection and CSRF exposure, yet the code consistently uses prepared statements for SQL. Either the documentation is stale, or residual risk exists that the author considered unaddressed (e.g., CSRF). | `README.md` (Usage Notes); prepared statements throughout | Documentation may understate or misstate the security posture |
| C-08 | **Copy-count reduction.** The edit form allows `total_copies` to be lowered below the number of copies currently on loan (only `available ≤ total` is validated), which can make the availability model unsatisfiable. | `books/edit.php:32-40` | Data-integrity edge case; rule intent unclear |

## 8. Missing, Incomplete, or Unused Functionality

| ID | Gap | Evidence of absence | Notes |
|---|---|---|---|
| GAP-001 | No password change or password-reset feature | Codebase-wide search; no such file or form | Documentation tells users to change the default password (C-02) |
| GAP-002 | No user-management interface (list/create/disable users) | `users` table touched only by login, registration, seed | Role field exists but is not manageable in-app |
| GAP-003 | `requireRole()` is never invoked | Defined `includes/auth.php:34-40`; zero call sites | Role-based authorization is vestigial |
| GAP-004 | Staff accounts cannot authenticate | C-01 | Either registration or the login role check must change |
| GAP-005 | 16 of 18 pages perform no authentication check | Only `index.php:4` and `books/view.php:4` call `requireLogin()` | All author/member/issue/return/search pages, plus book add/edit/delete, are reachable by anonymous visitors |
| GAP-006 | No CSRF protection; deletions are triggered by GET links | No token generation/validation anywhere; `books/view.php:40-42`, `authors/view.php:34-36`, `members/view.php:37-39` | Mutations are forgeable cross-site; deletes are one-click |
| GAP-007 | No pagination or row limits on any list page | All list queries lack `LIMIT` | Unbounded growth of pages/queries |
| GAP-008 | No overdue handling beyond display: no fines, notifications, escalations, or status transitions | Overdue only derived for display (`status.php:33`; `index.php:11`) | Overdue is informational only |
| GAP-009 | No loan-period policy enforcement | Due date only validated as "not in the past" (`issue.php:24-26`); 14 days is a form default | Maximum/minimum loan length undefined |
| GAP-010 | Temporary artifacts committed: `test_connection.php` and the `?debug_paths=1` debug panel | `test_connection.php:9-12` (self-declared temporary); `config/paths.php:54-63` | Debug panel leaks server filesystem paths |
| GAP-011 | No audit trail (who issued/returned/deleted what, and when) | No logging code or audit tables | Accountability not supported |
| GAP-012 | No reconciliation mechanism for `available_copies` vs open issue records | C-05 | Drift can only be fixed by manual edits |
| GAP-013 | Historical issue records are destroyed when a book or member is deleted | C-04; app guards only active issues | Lending history is not durable |
| GAP-014 | No input-format validation beyond email syntax (ISBN format, phone format, category vocabulary) | `books/add.php`, `members/add.php`; category is free text | Data quality relies on operator discipline |
| GAP-015 | No concept of member eligibility (membership status, expiry, blocks) | `members` table has only name/email/phone | Any registered member record can be lent to, indefinitely |

## 9. Open Questions for Stakeholders

1. Should registration exist at all, and if so, what may a `staff` user do
   (resolve C-01, C-06, GAP-004)?
2. Should issue/return/delete operations be restricted to authenticated staff,
   and should any operations be admin-only (GAP-005, GAP-003)?
3. Should deleting a book or member preserve historical issue records
   (C-04, GAP-013)?
4. Is manual editing of `available_copies` intended, or should availability be
   derived exclusively from issue records (C-05, GAP-012)?
5. Is 14 days the intended standard loan period, and is there any maximum
   (BR-031, GAP-009)?
6. Are there intended member-eligibility rules (membership validity, borrow
   limits, blocks) that were never implemented (GAP-015)?
7. Should overdue items trigger any action (fines, notifications) beyond
   display (GAP-008)?
8. Must lending history survive deletion of books or members for reporting or
   legal reasons (GAP-013)?

## 10. Companion Documents

- `business-rules.md` — recovered business rules (BR-###)
- `use-cases.md` — actors, use cases, and workflows (UC-##)
- `non-functional-requirements.md` — quality requirements (NFR-###)
- `traceability-matrix.csv` — requirement-to-evidence traceability
