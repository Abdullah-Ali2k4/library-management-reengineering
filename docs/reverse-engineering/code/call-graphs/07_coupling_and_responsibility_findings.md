# CG 07: Circular Dependencies, Tight Coupling and Unclear Responsibilities

Only items verified in the code are listed. Each states **Confirmed** (proven by source/`grep`/script) or **Likely** (environment-dependent).

## 1. Circular dependencies

| Check | Method | Result |
|-------|--------|--------|
| Include cycles between files | script-generated include graph, cycle detection | **None.** The graph is acyclic (CG-01). |
| Function call cycles | `grep` of the 5 user-defined functions | **None.** The call graph is a DAG: `requireRole → requireLogin → isLoggedIn`, `currentUser → isLoggedIn`. |
| Documentation cycle | docblocks in `auth.php` L10-15 and `header.php` L5 | `auth.php` says it is "required by header.php"; `header.php` says "include AFTER auth.php", but **no include edge exists** in either direction (CG-02, CG-03). **Confirmed** |
| Request-level redirect cycle | `auth/index.php` L5 (logged in → dashboard), `requireLogin` (not logged in → `auth/index.php`) | The two redirects test **opposite** conditions on the same key, so they cannot loop. Benign. |

## 2. Tight coupling

| Ref | Coupling | Evidence |
|-----|----------|----------|
| TC-1 | **Pages ↔ database driver.** Every page script calls `mysqli` directly. | 28 `prepare` + 16 `query` call sites across 20 files (CG-17) |
| TC-2 | **Pages ↔ global `$conn`.** The connection is a global variable created by `db.php` on include. | `db.php` L18; used by name in 20 scripts |
| TC-3 | **`header.php` ↔ include order.** Output depends on whether the caller loaded `auth.php` first. | CG-02: 14 of 16 pages show "Guest" |
| TC-4 | **`nav_active()` ↔ globals.** Reads `$current_page`/`$current_folder` through `global` set by the same file's top-level code. | `header.php` L14-22 |
| TC-5 | **Folder depth ↔ includes.** Relative include paths assume every page is one directory below the project root. | CG-04 |
| TC-6 | **Session-key contract by convention.** `login_handler.php` writes the keys, `auth.php` reads them, nothing enforces the contract. | CG-07 |
| TC-7 | **`footer.php` ↔ `validate.js` ↔ header markup.** JS binds to element ids/classes produced by other files; two of four JS sections match nothing. | `00_function_inventory.md` section 4 |

## 3. Unclear or mixed responsibilities

| Ref | File | Mixed responsibilities | Evidence |
|-----|------|------------------------|----------|
| UR-1 | every page script | request handling + validation + SQL + business rules + HTML | e.g. `issue.php` (120 lines) does all five |
| UR-2 | `includes/header.php` | layout markup, navigation-state function definition, current-user lookup | L14-27 logic, L29+ markup |
| UR-3 | `includes/auth.php` | session bootstrap (L19-21) and authorization helpers | |
| UR-4 | `config/paths.php` | URL-base computation **and** a debug page-output handler that runs on every request | L15-45 vs L54-63 |
| UR-5 | `config/db.php` | configuration constants, connection creation, error output (`die`) to the browser | L12-25 |
| UR-6 | `auth/register.php` vs `auth/login_handler.php` | role assignment and role gating split across files (CG-10) | |
| UR-7 | `auth/index.php` | page, session start, redirect-if-logged-in, flash-message read and an inline script | L3, L5-6, L10-11, L105-110 |

## 4. Dead or orphaned code on the call graph

| Ref | Item | Evidence | Status |
|-----|------|----------|--------|
| CG-22 | `requireRole()` | 0 callers | **Confirmed** |
| CG-23 | `validate.js` section 3 (`userMenu`, `userMenuTrigger`) | ids exist in no PHP/CSS file | **Confirmed** |
| CG-24 | `validate.js` section 4 (`.password-toggle`) | class exists in no PHP/CSS file; login page uses its own inline script for `#togglePassword` | **Confirmed** |
| CG-25 | `?deleted=1` flag | produced by 3 scripts, read by none (CG-14) | **Confirmed** |
| CG-26 | `test_connection.php` | no inbound include or link; its own header says to delete it | **Confirmed** |

## 5. Summary of coupling hotspots

| Hotspot | Fan-in | Why it matters |
|---------|-------:|----------------|
| `config/db.php` | 20 | one change to credentials or driver touches every page |
| `includes/header.php` | 16 | layout, navigation and user display in one file |
| `includes/footer.php` | 16 | also the only loader of `validate.js` |
| `config/paths.php` | 5 direct + all `header.php` includers transitively | includes a debug output path |
| `includes/auth.php` | 3 | the only access-control code, used by 2 of the 16 layout-based pages |
