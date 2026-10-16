# Recovered Business Rules

**Project:** Library Management System (legacy PHP/MySQL)
**Reverse-Engineering Level:** Requirements
**Related Issue:** Abdullah-Ali2k4/library-management-reengineering#3 — Requirements-Level Reverse Engineering
**Date:** 2026-10-16
**Status:** Draft for review
**Branch:** `issue-3/requirements-reverse-engineering`

---

## 1. Purpose and Reading Guide

This document recovers the **business rules** — the policies and constraints
governing authentication, registration, book and member management, borrowing,
returns, availability, and overdue handling — from source code, database
constraints, and documentation.

**Per the issue constraints, the existing implementation is not treated as
proof that a rule is correct or intended.** Each rule is therefore classified:

| Classification | Meaning |
|---|---|
| **Verified fact** | Enforced by code or database constraint; the rule *is applied* today |
| **Inferred assumption** | Plausible intent inferred from defaults, comments, or context; **not confirmed** |
| **Absence confirmed** | The codebase was searched and no such rule exists |

**Confidence:** High / Medium / Low — confidence that the statement accurately
describes the *applied* rule (for verified facts) or the *intended* rule (for
assumptions).

Rules are cross-referenced to functional requirements (FR-###) in
`recovered-srs.md` and to source locations (file:line).

---

## 2. Authentication and Access Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-001 | A user may authenticate only if their stored role is `admin` and the submitted password matches the stored bcrypt hash for the given email address. | Verified fact | `auth/login_handler.php:47-51` | High |
| BR-002 | A failed login attempt produces one generic error message that does not distinguish an unknown email from a wrong password. | Verified fact | `auth/login_handler.php:86-90` | High |
| BR-003 | The session identifier is regenerated upon successful login. | Verified fact | `auth/login_handler.php:54` | High |
| BR-004 | If "Remember me" is checked, the session cookie expiry is extended by 30 days; no separate persistent authentication token is issued. | Verified fact | `auth/login_handler.php:63-78` | High |
| BR-005 | Logout clears and destroys the current session, but does **not** clear a remember-me cookie. | Verified fact | `auth/logout.php:1-6`; cookie parameters never reset | High |
| BR-006 | Only two pages enforce authentication (dashboard and book list); all other pages are accessible without a session. | Verified fact | `index.php:4`; `books/view.php:4`; absence elsewhere (GAP-005) | High |
| BR-007 | No role check beyond the login gate is applied anywhere in the application; the `requireRole()` helper is never used. | Verified fact (absence of enforcement) | `includes/auth.php:34-40`; zero call sites | High |
| BR-008 | There is no restriction on the number of login attempts, no account lockout, and no session timeout. | Absence confirmed | No rate-limiting/lockout/timeout code exists | High |

## 3. Registration Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-009 | Every account created through the registration form receives the role `staff`; there is no in-app way to create an `admin`. | Verified fact | `auth/register.php:51` | High |
| BR-010 | A registration requires username, email, password (≥ 6 characters), and password confirmation; the email must be syntactically valid; username and email must be unique. | Verified fact | `auth/register.php:25-46`; schema uniqueness `user_schema.sql:10-11` | High |
| BR-011 | A `staff` account can never sign in, because login admits only `admin` accounts. | Verified fact (consequence of BR-001 + BR-009) | `login_handler.php:49` + `register.php:51` | High |
| BR-012 | One default `admin` account exists in the seed data with published credentials (`admin@library.local` / `admin123`); the seed comments instruct changing the password, but no password-change feature exists. | Verified fact (seed + absence) | `user_schema.sql:26-32`; comment `:20-24`; GAP-001 | High |

> **Conflict:** BR-009/BR-011 contradict any expectation that registered users
> can use the system. See recovered-srs.md C-01. The intended policy is
> unverifiable without stakeholder input.

## 4. Book Management Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-013 | A book must have a title and a linked author; both are required on add and edit. | Verified fact | `books/add.php:19-24`; `books/edit.php:26-31` | High |
| BR-014 | `total_copies` must be an integer of at least 1. | Verified fact | `books/add.php:25-27`; `books/edit.php:32-34` | High |
| BR-015 | `available_copies` must be a non-negative integer and must never exceed `total_copies`. | Verified fact | `books/edit.php:35-40` | High |
| BR-016 | ISBN is optional; when provided it must be unique across the catalog. | Verified fact | `books/add.php:28-38`; `books/edit.php:41-51`; DB UNIQUE `library_db.sql:29` | High |
| BR-017 | A newly created book is fully available: `available_copies` is set equal to `total_copies`. | Verified fact | `books/add.php:42-43` | High |
| BR-018 | `available_copies` may be overwritten manually through the book-edit form, independent of open issue records. | Verified fact | `books/edit.php:24`, `:57-58` | High |
| BR-019 | A book with at least one record in status `issued` cannot be deleted. | Verified fact | `books/delete.php:11-25` | High |
| BR-020 | A book with no active issues can be deleted; all of its historical issue records are deleted with it by the database cascade. | Verified fact | `books/delete.php:27-31`; `ON DELETE CASCADE` `library_db.sql:52` | High |
| BR-021 | Deleting an author does not delete their books; the books remain with `author_id = NULL`. | Verified fact | `ON DELETE SET NULL` `library_db.sql:32`; `authors/delete.php:10-16` | High |
| BR-022 | No ISBN format validation is performed (only uniqueness); category is unconstrained free text. | Absence confirmed | `books/add.php`, `books/edit.php` | High |

## 5. Member Management Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-023 | A member must have a full name and a syntactically valid, unique email address; phone is optional and unvalidated. | Verified fact | `members/add.php:12-29`; `members/edit.php:18-35`; DB `library_db.sql:38-40` | High |
| BR-024 | A member with at least one record in status `issued` cannot be deleted. | Verified fact | `members/delete.php:11-25` | High |
| BR-025 | A member with no active issues can be deleted; all of their historical issue records are deleted with them by the database cascade. | Verified fact | `members/delete.php:27-31`; `ON DELETE CASCADE` `library_db.sql:53` | High |
| BR-026 | There are no member-eligibility rules of any kind: no membership status, validity period, borrow limit, or block mechanism. Any member record may be lent to at any time. | Absence confirmed | `members` schema `library_db.sql:36-41`; no checks in `issue.php` beyond dropdown membership | High |

## 6. Borrowing (Issue) Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-027 | Issuing requires the selection of a book, a member, and a due date; each missing selection is rejected. | Verified fact | `issue_return/issue.php:16-23` | High |
| BR-028 | The due date must not be earlier than the current server date. | Verified fact | `issue_return/issue.php:24-26`; HTML `min` attribute `:112` | High |
| BR-029 | A book may be issued only if `available_copies ≥ 1` at the moment of confirmation; the check is performed again inside the transaction with a row lock to prevent concurrent double-issue of the last copy. | Verified fact | `issue_return/issue.php:36-44` | High |
| BR-030 | Each successful issue atomically decrements the book's `available_copies` by exactly 1 and inserts one `issued_books` record with status `issued`; the two operations commit or roll back together. | Verified fact | `issue_return/issue.php:46-58` | High |
| BR-031 | The default loan period offered by the form is 14 days from the issue date. | Inferred assumption | Form default `issue_return/issue.php:111`; **not** enforced server-side; no stakeholder confirmation | Medium |
| BR-032 | There is no limit on the number of books a member may hold simultaneously, and no rule preventing the same member from borrowing the same title more than once. | Absence confirmed | No such checks in `issue.php` | High |
| BR-033 | The existence of the selected book and member is not verified in application code; it is ultimately enforced only by the database foreign keys. | Verified fact (design observation) | No existence checks in `issue.php`; FKs `library_db.sql:52-53` | High |

## 7. Returning Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-034 | Only records in status `issued` are returnable; selecting an already-returned record is rejected with an error. | Verified fact | `issue_return/return.php:38-40` | High |
| BR-035 | A return is always dated with the server's current date; the operator cannot enter or backdate a return date. | Verified fact | `issue_return/return.php:42` | High |
| BR-036 | Each successful return atomically sets status to `returned`, records the return date, and increments the book's `available_copies` by exactly 1. | Verified fact | `issue_return/return.php:44-52` | High |
| BR-037 | There is no rule governing partial returns, renewals, or extensions; none of these operations exist. | Absence confirmed | No renewal/extension code | High |

## 8. Availability and Overdue Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-038 | Availability of a book is *stored* as `available_copies` on the `books` table and *maintained* by issue/return transactions, but also *editable* manually (BR-018); no consistency rule between stored availability and open issue records is enforced. | Verified fact | Issue/return transactions; `books/edit.php`; C-05 | High |
| BR-039 | "Overdue" is a derived condition, not a stored state: a record is overdue if and only if its status is `issued` and its due date is strictly before the current date. | Verified fact | `index.php:11`; `issue_return/status.php:33` | High |
| BR-040 | The `issued_books.status` column accepts only `issued` or `returned`; there is no `overdue`, `lost`, or `cancelled` state, and no stored transition history. | Verified fact | ENUM `library_db.sql:51` | High |
| BR-041 | Overdue status has no operational consequence: no fine, no notification, no automatic status change, and no restriction on further borrowing. | Absence confirmed | Overdue used only for display/counters | High |
| BR-042 | The overdue test compares dates as `Y-M-D` strings (SQL `CURDATE()` on the dashboard; PHP string comparison on the status page); both are consistent for ISO dates. | Verified fact | `index.php:11`; `status.php:33` | High |

## 9. Search Rules

| ID | Rule | Classification | Evidence | Confidence |
|---|---|---|---|---|
| BR-043 | A search matches if the query string is a substring (SQL `LIKE '%q%'`) of the book title, the category, the author's first name, or the author's last name. | Verified fact | `search.php:10-20` | High |
| BR-044 | Search matching is case-insensitive in practice because the default MySQL collation for the connection/table is case-insensitive (`utf8mb4` default `_ci` collations). | Inferred assumption | `db.php:25` sets `utf8mb4`; no explicit collation defined in schema; **not verified against a live server** | Medium |
| BR-045 | An empty query returns no results (the query is not executed). | Verified fact | `search.php:8`, `:36` | High |
| BR-046 | ISBN is not searchable. | Absence confirmed | `search.php:14-17` | High |

## 10. Conflicting, Ambiguous, or Undocumented Rules

| ID | Issue | Rules involved | Description | Needs clarification? |
|---|---|---|---|---|
| CR-01 | Registration dead-end | BR-001, BR-009, BR-011 | Registration exists and creates accounts the login page will never accept. Either the login role check or the registration role assignment does not reflect actual intent. | **Yes** — stakeholder must define the intended role model |
| CR-02 | Availability double-writer | BR-018, BR-030, BR-036, BR-038 | Transactions maintain availability, but manual edits can overwrite it; nothing reconciles the two writers. The rule "what does `available_copies` mean?" is ambiguous. | **Yes** |
| CR-03 | Deletion vs history | BR-019/BR-020, BR-024/BR-025 | Active issues block deletion, but historical records are cascade-deleted. Whether history retention is required is undocumented. | **Yes** |
| CR-04 | Loan period | BR-028, BR-031 | The 14-day period appears only as a UI default; operators can set any future date. Whether 14 days is policy or convenience is unconfirmed. | **Yes** |
| CR-05 | Copy-count reduction | BR-014, BR-015, BR-029 | The edit form permits lowering `total_copies` below the number of copies currently on loan, making `available ≤ total` unsatisfiable in terms of real-world copies. | **Yes** |
| CR-06 | Admin-only vs open pages | BR-006, BR-007 | The login page claims an admin-only area and a role helper exists, yet nearly all pages are anonymous. The intended access policy is unverifiable. | **Yes** |
| CR-07 | Existence checks | BR-033 | Book/member existence relies on FK constraint violations producing a generic "Something went wrong" error rather than a domain-specific message. | Optional — quality issue, not policy |

## 11. Summary of Rules Requiring Stakeholder Decisions

| Priority | Rule(s) | Question |
|---|---|---|
| High | CR-01 | What may `staff` users do, and should registration remain? |
| High | CR-06 | Which operations require authentication; which require admin? |
| High | CR-03 | Must lending history survive book/member deletion? |
| High | CR-02 | Is `available_copies` derived or manually maintained? |
| Medium | CR-04 | Is 14 days the intended loan period; any maximum? |
| Medium | CR-05 | May `total_copies` be reduced below copies on loan? |
