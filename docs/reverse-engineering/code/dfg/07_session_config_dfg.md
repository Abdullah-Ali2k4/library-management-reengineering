# DFG 07 — Session, Configuration and Dashboard Data

**Workflow:** Cross-cutting data (identity, connection, URL base, dashboard counts)  **Files:** `auth/login_handler.php`, `includes/auth.php`, `includes/header.php`, `index.php`, `auth/logout.php`, `config/db.php`, `config/paths.php`

## 1. Data Flow Graph — session lifecycle

```mermaid
flowchart LR
    LH["login_handler L54-60<br/>DEF user_id, username,<br/>user_email, role"]
    SS[("$_SESSION")]
    AI["auth/index.php L5<br/>isset user_id, redirect"]
    RL["requireLogin L28<br/>isset user_id"]
    CU["currentUser L46-51<br/>builds array id, username, email, role"]
    HD["header.php L25-27<br/>uses username only"]
    ID["index.php L13, L18<br/>uses username only"]
    RR["requireRole L36<br/>reads role<br/>NEVER CALLED"]
    LO["logout.php L3-4<br/>session = empty, destroy"]
    OUT(["Browser<br/>Welcome name, sidebar"])

    LH --> SS
    SS -- "user_id" --> AI
    SS -- "user_id" --> RL
    SS -- "all 4 keys" --> CU
    CU -- "username" --> HD
    CU -- "username" --> ID
    SS -- "role" --> RR
    HD --> OUT
    ID --> OUT
    LO -- "clears" --> SS

    classDef store fill:#dbeafe,stroke:#2563eb,color:#000;
    classDef dead fill:#fde2e2,stroke:#c0392b,color:#000;
    classDef out fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class SS store;
    class RR dead;
    class OUT out;
```

## 2. Data Flow Graph — configuration and dashboard

```mermaid
flowchart LR
    CST["db.php L12-15<br/>constants: localhost, root,<br/>empty password, library_db"]
    CN["db.php L18<br/>new mysqli"]
    DB[("library_db")]
    ERR["db.php L21<br/>die with connect_error"]
    SRV(["Server<br/>DOCUMENT_ROOT, __FILE__"])
    PH["paths.php L15-45<br/>compute BASE_URL"]
    DBG["paths.php L54-63<br/>if GET debug_paths: echo paths"]
    LNK["BASE_URL in links, assets,<br/>redirect Location headers"]
    CNT["index.php L7-11<br/>5 COUNT queries"]
    DASH["index.php L26-46<br/>stat cards"]
    OUT(["Browser"])

    CST --> CN
    CN -- "credentials" --> DB
    CN -- "failure text" --> ERR
    ERR -- "internal error to client" --> OUT
    SRV --> PH
    PH --> LNK
    LNK --> OUT
    SRV -- "server paths" --> DBG
    DBG -- "unauthenticated disclosure" --> OUT
    DB -- "counts: books, authors, members, issued, overdue" --> CNT
    CNT --> DASH --> OUT

    classDef store fill:#dbeafe,stroke:#2563eb,color:#000;
    classDef dead fill:#fde2e2,stroke:#c0392b,color:#000;
    classDef out fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class DB store;
    class ERR,DBG dead;
    class OUT out;
```

## 3. Def-use table

| Variable | DEF | MOD | USE-P | USE-C |
|----------|-----|-----|-------|-------|
| `$_SESSION['user_id']` | `login_handler.php` L57 | | `auth/index.php` L5, `auth.php` L24 | `auth.php` L47 |
| `$_SESSION['username']` | L58 | | | `auth.php` L48 → `header.php` L26, `index.php` L18 |
| `$_SESSION['user_email']` | L59 | | | `auth.php` L49 only (**dead**) |
| `$_SESSION['role']` | L60 | | `auth.php` L36 (dead function) | `auth.php` L50 (**dead**) |
| `$loggedInUser` | `header.php` L25 | | | L26 |
| `$displayName` / `$initial` | L26-27 | | | L96, L99 (escaped) |
| `BASE_URL` | `paths.php` L25/L44 (constant) | | | all links and `Location:` headers |
| `$total_books ... $total_overdue` | `index.php` L7-11 | | | L26-46 (ints) |

## 4. Data paths

| Path | Trace | Safe? |
|------|-------|-------|
| session identity | DB row → 4 session keys → `currentUser()` → name in HTML via `htmlspecialchars` | Safe |
| `BASE_URL` | Derived from filesystem paths, **not** from user input, so it is not tainted when used in `Location:` or HTML attributes | **Yes** |
| `PHP_SELF` | `basename()` → compared with constants in `nav_active` (header L14-22), never echoed | **Yes** |
| dashboard counts | SQL `COUNT(*)` ints → echoed unescaped (L26-46) | Safe (integers) |

## 5. Findings

| Ref | Evidence | Status |
|-----|----------|--------|
| DF-10a | `paths.php` L54-63: any page loading it with `?debug_paths=1` echoes `__FILE__`, `DOCUMENT_ROOT` and the project root **to anyone**, logged in or not. The comment calls it "TEMPORARY". | **Confirmed** |
| DF-10b | `db.php` L21 sends `connect_error` text to the browser on failure. | **Confirmed** |
| DF-10c | `test_connection.php` is public and prints all book titles; its own header says to delete it. | **Confirmed** |
| DF-11 | DB credentials are literal constants: `root` with an **empty password** (`db.php` L12-15). | **Confirmed** |
| DF-12 | `user_email` and `role` flow into the session and `currentUser()` but no consumer reads them; `requireRole()` is never called. Only `username` is consumed. | **Confirmed** |
| DF-14b | `header.php` L25 shows "Guest" on every page that does not include `auth.php` (the 16 unguarded pages), even when logged in. | **Confirmed** |
| Minor | `logout.php` clears `$_SESSION` and calls `session_destroy()` but does not expire the session cookie. | **Confirmed** (low) |
