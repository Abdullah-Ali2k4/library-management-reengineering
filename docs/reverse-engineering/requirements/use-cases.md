# Recovered Use Cases and User Workflows

**Project:** Library Management System (legacy PHP/MySQL)
**Reverse-Engineering Level:** Requirements
**Related Issue:** Abdullah-Ali2k4/library-management-reengineering#3 — Requirements-Level Reverse Engineering
**Date:** 2026-10-16
**Status:** Draft for review
**Branch:** `issue-3/requirements-reverse-engineering`

---

## 1. Purpose

This document identifies the actors and documents the **major verified
workflows** of the existing Library Management System as use cases, including
normal flows, alternative flows, error conditions, and gaps between intended
behavior (where inferable) and the observed implementation.

Per the issue constraints, only workflows verifiable in the source code are
documented; gaps and uncertainties are recorded explicitly rather than
smoothed over.

## 2. Actors

| ID | Actor | Description | Login capability |
|---|---|---|---|
| A1 | **Administrator** | Database user with role `admin`; the seeded account plus any account whose role is changed directly in the database | Yes — the only role accepted by the login handler (`auth/login_handler.php:49`) |
| A2 | **Staff** | Account with role `staff`, created by the registration page | **No** — registration creates accounts the login page rejects (conflict C-01) |
| A3 | **Unauthenticated visitor** | Any browser client without a session | Not applicable; can currently reach 16 of 18 pages (gap GAP-005) |
| A4 | **Library member** | A borrower stored in the `members` table | No — members are data entities only; they never use the system |

> Throughout this document, "editor" means any actor who can reach a given
> form. Because authentication is enforced on only two pages (BR-006), an
> editor is effectively any visitor for most workflows — a documented gap,
> not an endorsement of that state.

## 3. Use Case Index

| ID | Name | Primary actor(s) | FR references |
|---|---|---|---|
| UC-01 | Log in | A1 | FR-001…FR-003, FR-007…FR-009 |
| UC-02 | Log out | A1 | FR-006, FR-008 |
| UC-03 | Register a staff account | A3 | FR-004, FR-005 |
| UC-04 | View the dashboard | A1 | FR-010…FR-016 |
| UC-05 | List books | A1 | FR-017 |
| UC-06 | Add a book | A3 (intended: staff/admin) | FR-018, FR-019, FR-022 |
| UC-07 | Edit a book | A3 (intended: staff/admin) | FR-020…FR-022 |
| UC-08 | Delete a book | A3 (intended: staff/admin) | FR-023, FR-024 |
| UC-09 | List authors | A3 (intended: staff/admin) | FR-025 |
| UC-10 | Add or edit an author | A3 (intended: staff/admin) | FR-026, FR-027 |
| UC-11 | Delete an author | A3 (intended: staff/admin) | FR-028 |
| UC-12 | List members | A3 (intended: staff/admin) | FR-029 |
| UC-13 | Add or edit a member | A3 (intended: staff/admin) | FR-030, FR-031 |
| UC-14 | Delete a member | A3 (intended: staff/admin) | FR-032, FR-033 |
| UC-15 | Issue a book to a member | A3 (intended: staff/admin) | FR-034…FR-038 |
| UC-16 | Process a book return | A3 (intended: staff/admin) | FR-039…FR-041 |
| UC-17 | Monitor issued and overdue books | A3 (intended: staff/admin) | FR-042…FR-044 |
| UC-18 | Search the catalog | A3 (intended: any authenticated user) | FR-045, FR-046 |
| UC-19 | Verify the installation (connection test) | A3 | FR-047 |

## 4. Use Cases

### UC-01 — Log in

| Field | Content |
|---|---|
| **Actor** | A1 Administrator (A2 Staff blocked — see gaps) |
| **Goal** | Obtain an authenticated session |
| **Preconditions** | The actor has a valid `admin` account; no active session exists (otherwise redirect to dashboard, `auth/index.php:5-8`) |
| **Trigger** | Submitting the login form on `auth/index.php` |

**Main flow (normal):**
1. Actor opens the login page (`auth/index.php`).
2. Actor enters email and password; optionally checks "Remember me".
3. System validates both fields are non-empty (`auth/login_handler.php:18-22`).
4. System looks up the user by email (`:25-44`).
5. System verifies the role is `admin` **and** the password matches the hash (`:47-51`).
6. System regenerates the session id (`:54`) and stores `user_id`, `username`, `user_email`, `role` in the session (`:57-60`).
7. If "Remember me" was checked, the session cookie expiry is extended ~30 days (`:63-78`).
8. System redirects to the dashboard (`:81-82`). **Postcondition:** actor is authenticated.

**Alternative flows:**
- **3a. Empty fields:** system stores a login error in the session and redirects back to the login page (`:18-22`).
- **6a. Remember me unchecked:** the cookie lifetime remains the session default.

**Error conditions:**
- **E1. Wrong email or password, or role ≠ admin:** system stores the generic message "Invalid email or password." and redirects to the login page (`:84-90`). No distinction between causes is revealed.
- **E2. Database prepare fails:** generic "Something went wrong." message (`:32-36`).

**Gaps / notes:**
- A2 (staff) can never complete this use case (conflict C-01).
- No rate limiting or lockout exists (BR-008) — repeated attempts are unrestricted.

---

### UC-02 — Log out

| Field | Content |
|---|---|
| **Actor** | A1 (any authenticated user) |
| **Goal** | Terminate the session |
| **Preconditions** | An active session exists |
| **Trigger** | Clicking the sidebar Logout link (`includes/header.php:102-104`) |

**Main flow:**
1. System clears `$_SESSION`, destroys the session, and redirects to the login page (`auth/logout.php:1-6`).

**Error conditions / gaps:**
- The remember-me cookie (if any) is **not** cleared (BR-005) — the stale cookie remains until expiry.
- Logout is a GET request; it can be triggered by any link or embedded image (related to GAP-006).

---

### UC-03 — Register a staff account

| Field | Content |
|---|---|
| **Actor** | A3 Unauthenticated visitor |
| **Goal** | Create a login account |
| **Preconditions** | No active session (logged-in users are redirected away, `auth/register.php:11-14`) |
| **Trigger** | Submitting the registration form (`auth/register.php:106-122`) |

**Main flow:**
1. Actor opens `auth/register.php`.
2. Actor enters username, email, password, confirmation.
3. System validates presence of all fields, email syntax, password ≥ 6 chars, and password match (`:25-36`).
4. System checks username/email uniqueness (`:38-47`).
5. System hashes the password and inserts the user with role `staff` (`:49-61`).
6. System displays "Account created!" with a link to the login page (`:89-93`). **Postcondition:** a `staff` account exists.

**Alternative flows:**
- **3a. Validation failure:** all failing checks are collected and displayed together (`:95-103`).
- **4a. Duplicate username/email:** "That username or email is already registered." (`:43-45`).

**Gaps / notes:**
- **The created account cannot be used to log in** (BR-011, conflict C-01). The stated goal "create a login account" is therefore not achieved in practice.
- No email verification, CAPTCHA, or CSRF protection exists.

---

### UC-04 — View the dashboard

| Field | Content |
|---|---|
| **Actor** | A1 |
| **Goal** | Obtain an overview of library activity |
| **Preconditions** | Authenticated session (redirected to login otherwise, `index.php:4`) |
| **Trigger** | Navigating to `index.php` (or logging in) |

**Main flow:**
1. System counts total books, authors, members, currently issued books, and overdue books (`index.php:7-11`).
2. System renders five statistic cards (`:22-48`) and quick-action links (`:50-69`).

**Error conditions:**
- If the database is unreachable, the page dies with a connection error (`config/db.php:20-22`); counters have no fallback.

**Gaps / notes:**
- Five separate sequential COUNT queries are issued per page view (GAP-007 territory — unbounded as data grows; no caching).
- The HTML layout is emitted **before** the queries run (`include 'includes/header.php'` at `:5`), so a mid-page database error would appear after output has started.

---

### UC-05 — List books

| Field | Content |
|---|---|
| **Actor** | A1 (login enforced at `books/view.php:4`) |
| **Goal** | Review the catalog |
| **Preconditions** | Authenticated session |
| **Trigger** | Navigating to `books/view.php` |

**Main flow:**
1. System retrieves all books joined with author names (`books/view.php:8-13`).
2. System renders a table with title, author, ISBN, category, available/total copies, and Edit/Delete actions (`:20-49`).

**Gaps / notes:**
- No pagination, sorting controls, or filters (GAP-007).
- Delete is a plain GET link with a JS confirm dialog (`:40-42`) — no CSRF token (GAP-006).

---

### UC-06 — Add a book

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Insert a new title into the catalog |
| **Preconditions** | At least one author exists (otherwise the author dropdown is empty) |
| **Trigger** | Submitting the add-book form (`books/add.php:78-107`) |

**Main flow:**
1. Actor opens `books/add.php`; the author dropdown lists all authors (`:8`).
2. Actor enters title, author, optional ISBN/category, and total copies.
3. System validates: title required, author required, copies ≥ 1, ISBN unique if provided (`:19-38`).
4. System inserts the book with `available_copies = total_copies` (`:41-53`).
5. System displays "Book added successfully!" (`:62-66`). **Postcondition:** book is listed in the catalog.

**Alternative flows:**
- **3a. Validation failure:** errors are listed above the form; entered values are preserved (`:68-76`, `:81`).

**Error conditions:**
- **E1. Duplicate ISBN:** rejected with a message (`:34-36`).
- **E2. Insert failure:** generic error message (`:50-52`).

**Gaps / notes:**
- Reachable without login (GAP-005).
- No CSRF protection (GAP-006); no ISBN format validation (GAP-014).

---

### UC-07 — Edit a book

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Correct or update catalog data, including copy counts |
| **Preconditions** | The book exists (`books/edit.php:76-78` otherwise "Book not found") |
| **Trigger** | Submitting the edit form (`books/edit.php:109-141`) |

**Main flow:**
1. Actor opens `edit.php?id=…`; the form is pre-filled with current values (`:69-86`).
2. Actor modifies fields, including possibly `available_copies` and `total_copies`.
3. System validates as in UC-06 plus: available ≤ total, ISBN uniqueness excluding this book (`:32-51`).
4. System updates the row (`:53-66`) and displays a success message.

**Alternative flows / error conditions:** as UC-06, plus:
- **E1. Invalid or missing id:** "Invalid book ID." (`:10-12`).
- **E2. Book does not exist:** "Book not found." (`:76-78`).

**Gaps / notes:**
- Manual `available_copies` edits can desynchronize availability from open issue records (conflict C-05).
- `total_copies` may be reduced below the number of copies currently on loan (conflict C-08).

---

### UC-08 — Delete a book

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Remove a title from the catalog |
| **Preconditions** | The book exists |
| **Trigger** | Clicking the Delete link in the book list (`books/view.php:40-42`) |

**Main flow:**
1. Actor clicks Delete; the browser shows a JS confirmation (`books/view.php:41-42`).
2. System checks for active issue records for the book (`books/delete.php:11-15`).
3. If none exist, system deletes the book (`:27-31`) and redirects to the list with `?deleted=1` (`:34-35`). **Postcondition:** book and **all its historical issue records** are removed (cascade, BR-020).

**Alternative flows:**
- **2a. Active issues exist:** deletion is refused with an explanation and a link back to the list (`:17-25`).

**Error conditions:**
- **E1. Invalid id:** "Invalid book ID." (`:6-8`).

**Gaps / notes:**
- Trigger is a GET request without CSRF protection; a single cross-site request can delete data (GAP-006).
- Historical lending records are destroyed (conflict C-03/CR-03).

---

### UC-09 — List authors

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Review authors and their book counts |
| **Trigger** | Navigating to `authors/view.php` |

**Main flow:**
1. System retrieves each author with a count of linked books (`authors/view.php:5-11`).
2. System renders name, bio excerpt (80 chars), book count, and Edit/Delete actions (`:18-44`).

**Gaps / notes:** No pagination; Delete is a GET link (GAP-006, GAP-007).

---

### UC-10 — Add or edit an author

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Maintain author records |
| **Trigger** | Submitting the add form (`authors/add.php`) or edit form (`authors/edit.php`) |

**Main flow (add):**
1. Actor enters first name, last name, optional bio.
2. System requires both names (`authors/add.php:12-17`).
3. System inserts the author (`:19-28`) and shows a success message.

**Main flow (edit):**
1. Actor opens `edit.php?id=…` (404-equivalent "Author not found." if missing, `authors/edit.php:45-47`).
2. Form is pre-filled; actor modifies and saves (`:49-51`, `:74-86`).
3. System updates the record (`:25-34`).

**Gaps / notes:** No uniqueness rule for author names; no authentication; no CSRF protection.

---

### UC-11 — Delete an author

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Remove an author record |
| **Trigger** | Clicking Delete in the author list (`authors/view.php:34-36`) |

**Main flow:**
1. System deletes the author unconditionally (`authors/delete.php:13-16`).
2. The database sets `author_id` to NULL on the author's books (`ON DELETE SET NULL`, BR-021). **Postcondition:** books remain, displayed with "—" as author (`books/view.php:34`).

**Notes:**
- Unlike books/members, **no active-issue check applies** — an author of books currently on loan can be deleted. The confirm dialog even advertises this: "Their books will remain but show no author."
- GET-based, CSRF-exposed (GAP-006).

---

### UC-12 — List members

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Review members and their current borrowings |
| **Trigger** | Navigating to `members/view.php` |

**Main flow:**
1. System retrieves members with a count of active issues (`members/view.php:5-12`).
2. System renders name, email, phone, active-issue count, and Edit/Delete actions (`:19-46`).

---

### UC-13 — Add or edit a member

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Maintain the member registry |
| **Trigger** | Submitting the add form (`members/add.php`) or edit form (`members/edit.php`) |

**Main flow (add):**
1. Actor enters full name, email, optional phone.
2. System validates presence, email syntax, and email uniqueness (`members/add.php:12-29`).
3. System inserts the member (`:31-40`).

**Main flow (edit):**
1. Actor opens `edit.php?id=…` ("Member not found." if missing, `members/edit.php:57-59`).
2. System validates as above with email uniqueness excluding the member itself (`:26-35`).
3. System updates the record (`:37-47`).

**Gaps / notes:** Phone format unvalidated; no member status concept (BR-026).

---

### UC-14 — Delete a member

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Remove a member from the registry |
| **Trigger** | Clicking Delete in the member list (`members/view.php:37-39`) |

**Main flow:**
1. System checks for the member's active issues (`members/delete.php:11-15`).
2. If none exist, deletes the member (`:27-31`); historical issue records cascade away (BR-025).

**Alternative flows:**
- **1a. Active issues exist:** deletion refused with explanation (`:17-25`).

---

### UC-15 — Issue a book to a member

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Lend one available copy of a book to a member |
| **Preconditions** | At least one book has `available_copies > 0`; at least one member exists |
| **Trigger** | Submitting the issue form (`issue_return/issue.php:88-115`) |

**Main flow (normal):**
1. Actor opens `issue.php`; the book dropdown lists only books with available copies (`:8`), the member dropdown lists all members (`:9`).
2. Actor selects book, member, and a due date (default: today + 14 days; minimum: today).
3. System validates all three selections and that the due date is not in the past (`:16-26`).
4. System opens a transaction and re-reads the book's availability with a row lock (`:31-40`).
5. If a copy is available, system inserts the issue record with status `issued` and today's issue date (`:46-51`), then decrements `available_copies` (`:53-56`).
6. System commits and reports success (`:58-59`); link offered to the status page (`:72-75`). **Postcondition:** one copy is on loan; availability decreased by 1.

**Alternative flows:**
- **4a. No copies available at confirmation time** (another user issued the last copy between page load and submit): transaction rolls back; actor sees "This book is no longer available." (`:42-44`, `:60-62`). This is the designed race-condition defense (BR-029).
- **3a. Past due date selected:** rejected before the transaction (`:24-26`).

**Error conditions:**
- **E1. Missing selection(s):** corresponding validation messages (`:16-23`).
- **E2. Nonexistent book/member id posted directly:** the foreign key rejects the insert; the actor sees the generic "Something went wrong" message (BR-033).

**Gaps / notes:**
- No limit on simultaneous loans per member; no same-title restriction (BR-032).
- No authentication, no CSRF protection, and the operator's identity is not recorded anywhere (GAP-005, GAP-006, GAP-011).
- 14 days is only a UI default (assumption BR-031).

---

### UC-16 — Process a book return

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | Mark an issued record as returned and restore availability |
| **Preconditions** | At least one record has status `issued` (otherwise: "No books are currently issued.", `issue_return/return.php:106-108`) |
| **Trigger** | Submitting the return form (`:92-105`) |

**Main flow (normal):**
1. Actor opens `return.php`; the dropdown lists outstanding loans as "title — borrowed by member (due date)" (`:8-15`).
2. Actor selects a record and submits.
3. System opens a transaction and locks the issue record (`:28-33`).
4. System verifies the record exists and is still `issued` (`:35-40`).
5. System sets status `returned` and return date = today (`:42-47`), then increments the book's availability (`:49-52`).
6. System commits, refreshes the outstanding list (`:61-69`), and reports success. **Postcondition:** record returned; availability restored by 1.

**Alternative flows:**
- **4a. Record already returned:** rolled back with "This book has already been returned." (`:38-40`) — guards against double-returns restoring availability twice.

**Error conditions:**
- **E1. No selection:** "Please select a book to return." (`:20-22`).
- **E2. Record not found:** "Issue record not found." (`:35-37`).

**Gaps / notes:**
- Return date is always "today"; backdating is impossible (BR-035).
- No renewal/partial-return concept (BR-037).

---

### UC-17 — Monitor issued and overdue books

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap) |
| **Goal** | See all lending records and identify overdue loans |
| **Trigger** | Navigating to `issue_return/status.php` |

**Main flow:**
1. System retrieves all issue records joined with book and member, ordered active-first then by due date (`status.php:7-15`).
2. For each row, system derives overdue status (status `issued` and due date < today) and renders a label — `Returned`, `Overdue`, or `Issued` — highlighting overdue rows (`:30-54`).

**Notes:**
- Overdue is purely informational (BR-041): no action can be taken from this page (no fine, no notify, no force-return).

---

### UC-18 — Search the catalog

| Field | Content |
|---|---|
| **Actor** | A3 (no authentication enforced — gap; search is the most exposed read-only workflow) |
| **Goal** | Find books by title, category, or author name |
| **Trigger** | Submitting the search form (`search.php:28-34`) |

**Main flow:**
1. Actor enters a query string.
2. System runs a parameterized `LIKE` query over title, category, author first name, and author last name (`:8-22`).
3. System renders matching books with an Edit link per row (`:36-64`).

**Alternative flows:**
- **1a. Empty query:** no query is executed; no results section is shown (`:36`).
- **2a. No matches:** "No books matched your search." (`:60-61`).

**Gaps / notes:** ISBN not searchable (BR-046); unbounded result sets (GAP-007).

---

### UC-19 — Verify the installation (connection test)

| Field | Content |
|---|---|
| **Actor** | A3 (installer/maintainer) |
| **Goal** | Confirm the database connection and seed data are present |
| **Trigger** | Opening `test_connection.php` |

**Main flow:**
1. System attempts a database connection (`test_connection.php:15`).
2. On success it prints "Connected successfully!" and lists all book titles (`:17-30`); on empty data it advises checking the seed import (`:28-29`).

**Notes:**
- The file's own header declares it temporary and instructs deletion after use (`:9-12`), yet it is committed — a hygiene gap (GAP-010), not a product requirement.

## 5. Gap Summary — Intended vs Observed Behavior

| # | Workflow area | Observed behavior | Likely intended behavior (unconfirmed) |
|---|---|---|---|
| G-1 | UC-03 vs UC-01 | Registration succeeds; login always fails for created accounts | Either staff may log in, or registration should not exist (C-01) |
| G-2 | UC-06…UC-18 | All management workflows execute without a session | Only UC-01…UC-05 appear designed to require login (GAP-005) |
| G-3 | UC-08, UC-11, UC-14 | Deletions via GET links, no CSRF token | State changes should be POST with CSRF protection (GAP-006) |
| G-4 | UC-15 | 14-day loan is a form default only | A loan-period policy may exist but is unenforced (BR-031) |
| G-5 | UC-17 | Overdue loans are displayed only | Follow-up actions (fines/notifications) may have been planned; nothing implemented (GAP-008) |
| G-6 | UC-08, UC-14 | Deleting a book/member erases lending history | History retention intent unknown (CR-03) |
| G-7 | UC-07 | Availability manually editable alongside transactional maintenance | Single source of truth intended (CR-02) |
| G-8 | All | Operator identity never recorded | Accountability may be intended; no evidence (GAP-011) |
