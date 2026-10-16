# Recovered Non-Functional Requirements (NFR)

**Project:** Library Management System (legacy PHP/MySQL)
**Reverse-Engineering Level:** Requirements
**Related Issue:** Abdullah-Ali2k4/library-management-reengineering#3 — Requirements-Level Reverse Engineering
**Date:** 2026-10-16
**Status:** Draft for review
**Branch:** `issue-3/requirements-reverse-engineering`

---

## 1. Purpose and Reading Rules

This document recovers the **non-functional requirements** — quality attributes
covering security, usability, performance, reliability, maintainability, and
portability — from the existing system.

Per the issue constraints, the following distinctions are maintained
throughout:

| Label | Meaning |
|---|---|
| **Current (evidence)** | Observed, measurable fact about the implementation today, with cited evidence |
| **Proposed target** | A quality goal **not currently met** (or not fully met). These are **not** confirmed requirements — no stakeholder was available. Each needs stakeholder agreement before adoption |
| **Documented** | Stated in project documentation (README or in-code comments) |

Where a measurable criterion is recorded, it is only asserted when evidence or
arithmetic supports it; otherwise the criterion is marked *TBD (needs
agreement)* rather than invented.

**Confidence:** High / Medium / Low — confidence in the accuracy of the
*current-state* statement.

---

## 2. Security

| ID | Quality goal | Current (evidence) | Proposed target | Confidence |
|---|---|---|---|---|
| NFR-001 | **Password protection.** Passwords shall be stored only as salted hashes. | bcrypt hashes via `password_hash(PASSWORD_DEFAULT)` at registration (`auth/register.php:50`) and verification via `password_verify` at login (`auth/login_handler.php:50`); seed hash is bcrypt (`database/user_schema.sql:30`). **Target met.** | Maintain bcrypt (or stronger) hashing. | High |
| NFR-002 | **SQL-injection resistance.** User input shall not be able to alter SQL statement structure. | All user-influenced queries use `prepare`/`bind_param`; no string-concatenated SQL found in a full read of the codebase (e.g., `search.php:10-20`, `auth/login_handler.php:25-38`). **Target met** for observed code. *Documented conflict:* the README still warns about SQL-injection exposure (`README.md`, Usage Notes) — either the documentation is stale or residual risk is perceived (C-07). | Keep parameterized queries; resolve the documentation conflict. | High |
| NFR-003 | **XSS resistance.** Output derived from stored data shall be context-escaped. | `htmlspecialchars` applied at essentially all output sites (e.g., `books/view.php:33-37`, `includes/header.php:96-99`). Two low-risk exceptions: `author_id`/`book_id` interpolated unescaped into `<option value>` attributes (`authors/add.php:87-89`, `issue_return/issue.php:93-94`) — values are DB integers, so exploitability is low. **Substantially met.** | Escape/cast all interpolations uniformly. | High |
| NFR-004 | **Access control.** Non-public functionality shall require authentication; role restrictions shall be enforced. | **Not met.** `requireLogin()` enforced on 2 of 18 pages (`index.php:4`, `books/view.php:4`); `requireRole()` defined but never called (`includes/auth.php:34-40`); the login page advertises an "administrators only" area (`auth/index.php:51-57`). 16 pages — including all mutations except book viewing — are anonymous (GAP-005). | All non-public pages behind login; role matrix defined and enforced (needs stakeholder definition — see open question OQ-2 in recovered-srs.md §9). | High |
| NFR-005 | **CSRF resistance.** State-changing operations shall require an unpredictable, session-bound token. | **Not met.** No CSRF token generation or validation anywhere; deletions are triggered by plain GET links (`books/view.php:40-42`, `authors/view.php:34-36`, `members/view.php:37-39`) and logout by GET (`auth/logout.php`). The README acknowledges CSRF as a known weakness (`README.md`, Usage Notes) — **documented**. | CSRF tokens on all mutating forms; mutations restricted to POST. | High |
| NFR-006 | **Session hygiene.** Sessions shall be protected against fixation and unbounded lifetime. | Session id regenerated on login (`auth/login_handler.php:54`) — **met**. No idle or absolute session timeout exists — **not met**. Remember-me cookie (30 days, `:63-78`) is not cleared on logout (`auth/logout.php:1-6`) — **not met**. | Defined idle/absolute timeouts (*values TBD*); logout clears the remember-me cookie. | High |
| NFR-007 | **Brute-force resistance.** Repeated authentication attempts shall be rate-limited or locked out. | **Not met.** No rate limiting, delay, CAPTCHA, or lockout exists anywhere (absence confirmed). | Rate limiting / temporary lockout after *N* failed attempts (*N TBD*). | High |
| NFR-008 | **No debug exposure in deployable builds.** Diagnostic output that reveals internals shall not be reachable. | **Not met.** `?debug_paths=1` prints filesystem paths on any page that includes `paths.php` (`config/paths.php:54-63`, self-labeled "TEMPORARY DEBUG TOOL"); `test_connection.php` remains in the tree despite its own instruction to delete it (`test_connection.php:9-12`). Both are **documented** as temporary. | Debug affordances removed or gated for non-local environments. | High |
| NFR-009 | **Security headers.** Responses shall carry hardening headers (e.g., frame-ancestors/X-Frame-Options, X-Content-Type-Options, Referrer-Policy). | **Not met.** No such `header()` calls exist (absence confirmed). | Header set defined (*specific values TBD*). | High |
| NFR-010 | **Configuration secrecy.** Database credentials shall not be hardcoded in versioned files for shared/production deployments. | Hardcoded in `config/db.php:11-14` (`root`/blank password). Acceptable for the **documented** local XAMPP/WAMP context (`README.md`, Prerequisites); **not met** for any shared deployment. | Environment-variable or external-config support (*scope TBD*). | High |

## 3. Usability

| ID | Quality goal | Current (evidence) | Proposed target | Confidence |
|---|---|---|---|---|
| NFR-011 | **Responsive layout.** The UI shall remain usable on small screens. | Mobile topbar with hamburger toggle, off-canvas sidebar, and overlay implemented (`includes/header.php:41-51`; `assets/js/validate.js:56-89`; sidebar CSS in `assets/css/style.css`). **Implemented** (mechanism verified; *usability untested — no user-testing evidence exists*). | Retain responsiveness; add user testing if quality must be demonstrated. | Medium |
| NFR-012 | **Client-side input assistance.** Forms shall catch basic input errors before submission. | Generic validator on `.validate-form` checks required fields, email pattern, and non-negative numbers (`assets/js/validate.js:10-54`); server-side validation also present on all forms. **Met.** | Keep dual-layer validation. | High |
| NFR-013 | **Consistent navigation.** A persistent navigation structure shall be available on all application pages. | Shared sidebar with active-state highlighting (`includes/header.php:65-91`, `nav_active()` `:17-23`) on all pages using the header. **Met.** | — | High |
| NFR-014 | **Safe feedback after actions.** Successful mutations shall not be repeatable by refreshing the browser. | **Partially met.** Success messages are rendered in the same POST response (e.g., `books/add.php:62-66` after handling POST at `:10`) — a browser refresh re-submits the form (no Post/Redirect/Get pattern). Issue/return pages similarly render results on the POST response (`issue_return/issue.php:72-75`). | Post/Redirect/Get on all mutating handlers. | High |
| NFR-015 | **Accessibility.** UI controls shall carry appropriate accessible names/states. | **Largely not met.** `aria-label` on the hamburger (`includes/header.php:42`) and `aria-expanded` handling in the (unused) dropdown code (`assets/js/validate.js:96-99`) are the only ARIA usage; forms lack labeled error association; color-only status encoding in `status.php:42-48` (text labels do accompany colors — partially compensating). | Accessibility baseline defined (*standard TBD, e.g., WCAG 2.1 AA*). | Medium |

## 4. Performance and Scalability

| ID | Quality goal | Current (evidence) | Proposed target | Confidence |
|---|---|---|---|---|
| NFR-016 | **Bounded list retrieval.** List pages shall not require loading unbounded row sets. | **Not met.** No `LIMIT` or pagination on any list: books (`books/view.php:8-13`), authors (`authors/view.php:5-11`), members (`members/view.php:5-12`), status (`issue_return/status.php:7-15`), search results (`search.php:10-20`). All queries and pages grow linearly with data volume. | Paginated lists (*page size TBD*). | High |
| NFR-017 | **Indexed access paths.** Queries shall be supported by appropriate indexes. | **Partially met.** Primary keys and UNIQUE constraints exist (`library_db.sql`, `user_schema.sql`); InnoDB indexes FK columns implicitly. No composite index supports the two hottest predicates — `issued_books(status, due_date)` — used by the dashboard overdue count (`index.php:11`) and the status-page ordering (`status.php:12-14`); both will scan `issued_books` as it grows. | Composite index on `issued_books(status, due_date)`; further indexes per measured workload. | High |
| NFR-018 | **Dashboard cost.** The landing page shall render within a reasonable time at target data volumes. | Five sequential `COUNT(*)` queries per view (`index.php:7-11`), executed *after* HTML output has begun (header included at `:5`). No measurement exists; no target volume is documented. Current cost is acceptable for the small seed dataset. | Response-time target at agreed data volume (*volume and ms TBD — cannot be derived from evidence*). | Medium |
| NFR-019 | **Concurrent-issue safety.** Simultaneous issue attempts for the last copy shall not oversell. | **Met.** Row-lock recheck inside a transaction (`issue_return/issue.php:31-63`, `SELECT … FOR UPDATE` at `:36`) with rollback on failure. *Static-analysis evidence only; not load-tested.* | Keep transactional guard; add concurrency tests. | High |

## 5. Reliability and Data Integrity

| ID | Quality goal | Current (evidence) | Proposed target | Confidence |
|---|---|---|---|---|
| NFR-020 | **Atomicity of lending operations.** Issue and return shall be all-or-nothing. | **Met.** `begin_transaction`/`commit`/`rollback` around issue (`issue_return/issue.php:31-63`) and return (`return.php:25-59`). | — | High |
| NFR-021 | **Referential integrity.** Relationships between entities shall be enforced by the database. | **Met.** FKs from `books.author_id` (`SET NULL`), `issued_books.book_id`/`member_id` (`CASCADE`) (`library_db.sql:32`, `:52-53`). *Caveat:* the CASCADE rules destroy lending history (BR-020/BR-025) — integrity is enforced, but the rule itself is questionable (C-04). | Revisit cascade rules per stakeholder decision. | High |
| NFR-022 | **Graceful failure.** Database failures shall produce controlled, non-leaking error states. | **Weak.** Connection failure calls `die()` with the raw driver error (`config/db.php:20-22`); query failures are mostly unhandled (`$conn->query(...)` results used without checking for `false`, e.g., `index.php:7-11`). | Centralized error handling; user-safe messages; logged details. | High |
| NFR-023 | **Durable history.** Business records shall persist for the retention period agreed with stakeholders. | **Not met / intent unknown.** Hard deletes with `ON DELETE CASCADE` erase issue history for deleted books/members (`library_db.sql:52-53`); only active issues are guarded in application code. Retention requirement is undocumented (C-04). | Retention policy defined by stakeholder; soft-delete or history-preserving schema if required. | High |
| NFR-024 | **Auditability.** Security-relevant actions shall be attributable. | **Not met.** No logging, no audit tables, no record of which operator issued, returned, edited, or deleted anything (absence confirmed). Issue records store no staff reference (`library_db.sql:44-54`). | Audit trail for mutations (*fields and retention TBD*). | High |

## 6. Maintainability

| ID | Quality goal | Current (evidence) | Proposed target | Confidence |
|---|---|---|---|---|
| NFR-025 | **Code reuse.** Recurring logic shall be implemented once. | **Not met.** The validate → prepare → execute → render-errors pattern is duplicated across ~12 CRUD files with near-identical structure (compare `authors/add.php` with `members/add.php`; `books/edit.php` with `members/edit.php`). Client validation is correctly centralized (`validate.js`), but server-side logic is not. | Extracted shared helpers / data-access layer (*scope TBD*). | High |
| NFR-026 | **Separation of concerns.** Data access, business logic, and presentation shall be distinguishable. | **Not met.** SQL, validation, control flow, and HTML coexist in every page file (representative: `issue_return/issue.php` mixes transaction logic `:28-64` with markup `:70-115`). | Layered structure agreed for the reengineered system (*architecture TBD*). | High |
| NFR-027 | **Consistent bootstrapping.** Configuration and session startup shall follow one pattern. | **Not met.** Mixed include styles: `__DIR__`-based (`auth/index.php:2`, `auth/login_handler.php:2-5`) vs cwd-relative (`auth/register.php:8-9`, all module files, e.g., `books/add.php:2`); session started both via the guarded helper (`includes/auth.php:19-21`) and bare calls (`auth/index.php:3`). Works today under normal SAPI cwd behavior; fragile under CLI or include-from-elsewhere scenarios. | Single bootstrap include used everywhere. | High |
| NFR-028 | **Documentation currency.** Written documentation shall match actual behavior. | **Not met.** Known drifts: login described with a username the form does not accept (C-03); password-change instruction with no such feature (C-02); security warnings that understate the prepared-statement posture while omitting the larger access-control gap (C-07); `test_connection.php` marked for deletion but retained (GAP-010). | Documentation reviewed against this recovered requirements set. | High |

## 7. Portability and Environment

| ID | Quality goal | Current (evidence) | Proposed target | Confidence |
|---|---|---|---|---|
| NFR-029 | **Deployment-path independence.** The application shall function whether installed at the web root or in a subfolder. | **Met (mechanism).** `BASE_URL` computed from `DOCUMENT_ROOT` with case-insensitive, Windows-safe path handling and fallbacks (`config/paths.php:15-46`); all links built from the constant (e.g., `includes/header.php:68-90`). *Runtime behavior not executed during this static analysis.* | Keep dynamic base-path resolution; add automated check. | High |
| NFR-030 | **Character-set safety.** The application shall handle full Unicode text. | **Met.** Connection charset set to `utf8mb4` (`config/db.php:25`); schema uses `utf8mb4`-compatible defaults. | Keep `utf8mb4` end-to-end (including explicit column collations — currently unspecified in schema). | High |
| NFR-031 | **Platform assumptions.** The system shall run on the documented local-stack environment. | **Documented constraint:** XAMPP/WAMP/MAMP with Apache, MySQL, PHP `mysqli`, default `root`/blank-password credentials (`README.md`, Prerequisites & Installation). No version pins for PHP or MySQL are documented — an ambiguity for reproducibility. | Minimum PHP/MySQL versions documented (*values TBD*). | Medium |

---

## 8. Summary — Proposed Targets Awaiting Stakeholder Agreement

Every item below is a **proposal derived from observed deficiencies**, not a
confirmed requirement:

| Priority | NFR | Proposed target | Why it surfaced |
|---|---|---|---|
| High | NFR-004 | Enforce authentication/roles on all non-public pages | 16 of 18 pages are anonymous today |
| High | NFR-005 | CSRF tokens; POST-only mutations | Mutations forgeable; deletes are GET links (README acknowledges) |
| High | NFR-007 | Login rate limiting / lockout | No protection exists |
| High | NFR-006 | Session timeouts; clear remember-me on logout | Unbounded session lifetime today |
| High | NFR-023 / NFR-024 | Retention policy + audit trail | History destroyed on delete; no attribution |
| Medium | NFR-016 / NFR-017 | Pagination + composite index on `issued_books(status, due_date)` | Unbounded lists; known hot queries |
| Medium | NFR-014 | Post/Redirect/Get | Refresh re-submits forms |
| Medium | NFR-008 | Remove debug affordances for deployable builds | Path leak + temp file committed |
| Medium | NFR-010 | Externalize credentials | Hardcoded (fine locally, not elsewhere) |
| Low | NFR-009 / NFR-015 / NFR-031 | Security headers; accessibility baseline; version pins | No evidence of any current posture |

## 9. Explicit Non-Claims

To honor the constraint *do not invent requirements*:

- No response-time, throughput, or availability numbers are asserted — none
  are documented and no load testing exists.
- No compliance requirement (privacy law, accessibility standard) is asserted —
  none appears in any evidence source.
- No availability/redundancy requirement is asserted — the system is a
  single-instance local application by documented context.
