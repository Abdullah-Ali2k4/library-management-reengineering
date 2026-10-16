# Design Assessment

**Scope:** assessment of the *existing* design only. **No change is implemented in this issue.** Candidate redesign areas (section 8) are proposals for future issues.
**Method:** static inspection plus the earlier control-flow, data-flow and call-graph analyses. Nothing was executed; PHP/MySQL versions are unknown, so version-dependent items are marked **Likely**.

## 1. Scorecard

| Quality attribute | Rating | One-line justification |
|-------------------|--------|------------------------|
| Cohesion | **Weak** at file level, **Adequate** at folder level | folders group by entity, but each script mixes input, rules, SQL and HTML |
| Coupling | **Weak** | global `$conn`, hidden `header.php` → `auth.php` dependency, SQL in 20 pages, shared mutable rule (`available_copies`) |
| Separation of concerns | **Weak** | 18 of 27 PHP files mix SQL and markup; no controller/service/data layers |
| Maintainability | **Adequate today, declining with growth** | small code base and readable style, but heavy duplication, no tests, scattered rules |
| Data design | **Adequate** | sound keys and foreign keys; weaknesses are derived data, missing CHECKs, nullable-unique ISBN misuse |
| Consistency of conventions | **Weak** | 3 page-head variants, 2 include path styles, guard applied to 2 of 18 pages |
| Strengths worth keeping | see section 6 | prepared statements, escaping, locked transactions, clean folder structure |

## 2. Cohesion

| Component | Cohesion | Evidence |
|-----------|----------|----------|
| `includes/auth.php` | Strong (functional) | 4 small related functions plus session bootstrap; V(G) ≤ 2 each (CFG 05) |
| `config/db.php` | Adequate | connection only, but also prints the raw error with `die` (L21) |
| `config/paths.php` | Weak | URL-base computation **and** a debug page-output handler (L15-44 vs L49-63) |
| `includes/header.php` | Weak (coincidental) | path loading, function definition, user lookup and full page markup (L12-27, L29+) |
| Page scripts (20) | Weak | each performs request parsing, validation, business rules, SQL and rendering |
| `assets/js/validate.js` | Weak | 4 unrelated behaviours in one file; 2 match no markup |
| Database schema | Strong | one table per entity, one purpose each |
| Module folders (`books/`, `authors/`, `members/`, `issue_return/`) | Adequate | consistent add/edit/delete/view set per entity |

## 3. Coupling

| Kind | Instance | Evidence |
|------|----------|----------|
| Common (global data) | `$conn`; `global $current_page, $current_folder`; `$_SESSION` keys | `db.php` L17; `header.php` L18; `login_handler.php` L57-60 / `auth.php` L24-50 |
| External (to a driver and schema) | every page ↔ `mysqli` and table/column names | 28 prepare + 16 query sites; `category` appears in 4 files, `available_copies` in 6 |
| Hidden / temporal | layout needs `auth.php` loaded first but does not include it | `header.php` L25; 14 of 16 layout pages show "Guest" |
| Contract by convention | session-key names; role strings `'admin'`/`'staff'`; status strings `'issued'` (10 occurrences) / `'returned'` (3) | scattered literals |
| Structural | include paths assume one-level folder depth | 54 of 60 include statements are relative |
| Data (healthy) | arrays `$errors`, `$_SESSION`, `fetch_assoc()` rows passed between steps | low risk |
| Fan-in hotspots | `db.php` 20, `header.php` 16, `footer.php` 16 | a change to these files affects most of the system |
| Dependency structure | include graph is acyclic; folders depend only downward | CG-01, `uml/component-and-package-diagram.md` |

## 4. Separation of concerns

| Concern | Where it lives today | Should be isolated? |
|---------|---------------------|---------------------|
| Routing | file system path | acceptable at this size |
| Authentication / authorization | `auth.php` (+ per-page opt-in) | yes: enforced centrally |
| Request parsing and validation | top of every page; add/edit duplicate rules | yes |
| Business rules (circulation, guards) | `issue.php`, `return.php`, `*/delete.php`, `books/edit.php` | yes |
| Data access | inline SQL in 20 scripts | yes |
| Presentation | bottom of every page + 3 head variants | yes |
| Configuration | `db.php`, `paths.php` | yes (and without debug code) |
| Error handling / logging | `die`, arrays, exceptions; no log | yes |

## 5. Maintainability

**Size and shape**

27 PHP files, 1937 PHP lines; 904 CSS lines; 137 JS lines (physical lines). Largest scripts:

| File | Lines | Mixes SQL and HTML |
|------|------:|:------------------:|
| `books/edit.php` | 146 | yes |
| `auth/register.php` | 131 | yes |
| `issue_return/issue.php` | 120 | yes |
| `auth/index.php` | 114 | no |
| `issue_return/return.php` | 113 | yes |
| `books/add.php` | 112 | yes |
| `includes/header.php` | 110 | no |
| `members/edit.php` | 104 | yes |

| Measure | Value | Source |
|---------|-------|--------|
| Highest cyclomatic complexity (logic part) | `register.php` 13 (19 with template); `issue.php` 10 (15); `login_handler.php` 9 | CFG reports |
| Duplicated logic hotspots | transaction skeleton (issue/return); same JOIN twice in `return.php`; active-loan guard (books/members delete); add/edit validation pairs | CG-11, CG-12, CG-18 |
| Change impact: add or rename a book attribute such as `category` | edits in 4 PHP files + schema | `grep` |
| Change impact: change how copy availability works | 6 PHP files reference `available_copies`, 4 write it | ERD SC-1 |
| Change impact: change database credentials | 1 file (`db.php`), good | `db.php` L11-14 |
| Change impact: add a new status value | schema ENUM + at least 13 literal occurrences | `grep` |
| Automated tests | none | no `tests/`, no `phpunit.xml` |
| Testability | low: global connection, side effects at include time, logic inside pages | AL-4 |
| Documentation | README (setup, features); docblocks in `includes/*` and `config/*`; no architecture docs before this issue | files |
| Dead code | `requireRole`, two JS sections, `?deleted=1`, `users.created_at`, `test_connection.php`, debug tool | AP-5, AP-11 |

## 6. Strengths (keep during any redesign)

| Strength | Evidence |
|----------|----------|
| All user data reaches SQL through prepared statements; no interpolated queries | 28 prepared statements, 0 interpolated `query()` |
| Output is escaped consistently | 63 `htmlspecialchars` calls; no unescaped user data found (DFA DF-07/08) |
| Circulation uses real transactions with row locks | `issue.php` L31-62, `return.php` L25-59 |
| Password handling uses `password_hash`/`password_verify`; session id regenerated at login | `register.php` L50, `login_handler.php` L50, L54 |
| Clean, predictable folder structure and naming | `books/`, `authors/`, `members/` each with add/edit/delete/view |
| No circular dependencies | include graph acyclic (CG-01) |
| Schema has explicit keys, UNIQUE constraints and FK actions; seed data is consistent with the counter rule | `library_db.sql` L16-54, L79-84 |

## 7. Gaps between the existing design and sound software engineering practice

| Practice | Current state | Gap | Evidence |
|----------|---------------|-----|----------|
| Layered architecture / separation of concerns | none | UI, logic and data access interleaved | AL-1 |
| Single enforcement point for security | per-page opt-in | 16 pages unguarded; `requireRole` unused | CFG F-01, AL-2 |
| DRY | extensive copy-paste | duplicated queries, guards, validations | AP-2 |
| Single owner for each business rule | `available_copies` has 4 writers | no authoritative owner | SC-1 |
| Dependency management / injection | globals | cannot substitute or mock the database | AL-4 |
| Configuration management | literals in source | no environments; debug code in config | AL-5, AP-6, AP-11 |
| Database integrity | keys and uniques only | no CHECKs; derived data; nullable-unique misuse; destructive cascade; no audit column | SC-1, SC-3, SC-5, SC-8 |
| Schema lifecycle | two manual scripts | no migrations; re-run drops data; users separate | AL-6, SC-10 |
| Idempotent, safe request handling | PRG only on delete/login; deletes over GET; no CSRF tokens | refresh re-issues a book; cross-site deletion possible | CFG F-06, F-09, DFA DF-05 |
| Error handling and observability | mixed `die`/arrays/exceptions; no logs | inconsistent user messages, no diagnostics | AL-7 |
| Automated testing | none | no regression safety net | section 5 |
| Consistent coding conventions | partial | 3 head variants, 2 include styles | CG-04, CG-08 |
| Role-based authorization | schema and helper exist, unusable | staff cannot log in | AL-8 |
| Dead-code hygiene | present | misleading artefacts | AP-5 |

## 8. Candidate areas for future redesign (not implemented)

Priorities: **P1** security/integrity risk or root cause of other items, **P2** structural improvement, **P3** hygiene. Effort: S (hours), M (days), L (week+). These are proposals only.

| ID | Area | Problem (evidence) | Direction to evaluate | Prio | Effort | Depends on |
|----|------|--------------------|-----------------------|------|--------|-----------|
| R1 | Central request handling and access control | AL-2, CFG F-01, DFA DF-06 | one bootstrap/front controller or shared guard applied to every non-public page | P1 | M | none |
| R2 | Request safety | CFG F-06, F-09, DFA DF-05 | POST-only destructive actions, CSRF tokens, Post/Redirect/Get on all writes | P1 | S-M | R1 |
| R3 | Circulation service | AP-2, CG-11, SC-1 | one place that owns issue/return and the transaction | P2 | M | R4 |
| R4 | Data-access layer | AL-1, TC-1, TC-2 | single connection provider; per-entity query functions/classes; removes SQL from pages | P2 | L | none |
| R5 | Derived-data policy for `available_copies` | SC-1, DFA DF-02 | compute from loans, or keep stored but with one writer plus CHECK constraints | P1 | S-M | R3 |
| R6 | Schema integrity and lifecycle | SC-2, SC-3, SC-5, SC-8, SC-9, SC-10, SC-11, SC-12, AL-6 | CHECKs, NULL for blank ISBN, retain loan history on delete, audit column, charset, indexes, versioned migrations including `users` | P1 | M | none |
| R7 | Configuration and environment | AL-5, AP-6, AP-11 | externalize DB settings, remove the debug handler, stop publishing default credentials | P1 | S | none |
| R8 | Layout and view consolidation | AP-4, CG-02, CG-08 | one layout renderer, logic out of `header.php`, uniform head, correct user display | P2 | S-M | R1 |
| R9 | Error handling and logging | AL-7 | one policy, no raw DB messages in the UI, log file | P2 | S-M | R4 |
| R10 | Role model decision | AL-8, CFG F-03, CG-10 | decide admin/staff semantics, then implement or remove `requireRole` and the `staff` path | P1 | S | R1 |
| R11 | Testing baseline | AL-4, section 5 | tests for circulation rules and validation once R3/R4 exist | P2 | M | R3, R4 |
| R12 | Dead-code cleanup | AP-5 | remove unused function, JS sections, flag, column, diagnostic page | P3 | S | none |

**Suggested order (for planning only):** R7, R10, R12 (quick, low risk) → R1, R2 → R6 → R4 → R3, R5 → R8, R9, R11.

## 9. Traceability

| Finding family | Document |
|----------------|----------|
| AL-1..AL-9 | [`architecture.md`](architecture.md) section 9 |
| AP-1..AP-11, tight coupling | [`design-pattern-analysis.md`](design-pattern-analysis.md) |
| SC-1..SC-14 | [`erd.md`](erd.md) section 4 |
| Diagrams | [`uml/README.md`](uml/README.md), [`architecture-diagram.svg`](architecture-diagram.svg) |
| CFG / DFG / CG findings | `../code/cfg/`, `../code/dfg/`, `../code/call-graphs/` |

## 10. Limits of this assessment

Static analysis only. PHP and MySQL versions, `sql_mode`, session configuration and real data volumes were not available; items depending on them are marked **Likely**. No performance, usability or accessibility testing was done.
