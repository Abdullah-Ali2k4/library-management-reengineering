# CG 02: Login Workflow Call Graph (login → protected dashboard)

**Workflow:** two HTTP requests: `POST auth/login_handler.php`, then `GET index.php` after the redirect.

## 1. Call graph, request 1 (`auth/login_handler.php`)

```mermaid
flowchart TD
    LH["login_handler.php<br/>top-level script"]
    PH["config/paths.php<br/>L2"]
    PHC["realpath, str_replace,<br/>stripos, substr, trim, define"]
    SS["session_start L3"]
    DBF["config/db.php L5"]
    MY["new mysqli, set_charset"]
    PREP["mysqli: prepare, bind_param,<br/>execute, get_result,<br/>fetch_assoc, close<br/>L25-44"]
    PV["password_verify L50"]
    SR["session_regenerate_id L54"]
    CK["setcookie, session_get_cookie_params,<br/>session_name, session_id<br/>L63-77, only if remember"]
    HX["header Location + exit<br/>5 exit points"]
    TR["trim, isset L13-15"]

    LH --> PH --> PHC
    LH --> SS
    LH --> DBF --> MY
    LH --> TR
    LH --> PREP
    LH --> PV
    LH --> SR
    LH --> CK
    LH --> HX

    classDef ext fill:#fff4d6,stroke:#b8860b,color:#000;
    class MY,PREP,PV,SR,CK,SS ext;
```

## 2. Call graph, request 2 (`index.php`, the dashboard)

```mermaid
flowchart TD
    IDX["index.php"]
    DBF["config/db.php"]
    AUTH["includes/auth.php"]
    P1["config/paths.php"]
    SS2["session_status, session_start"]
    RL["requireLogin"]
    IL["isLoggedIn"]
    HDR["includes/header.php"]
    P2["config/paths.php<br/>(require_once, already loaded)"]
    NA["nav_active x8"]
    CU["currentUser"]
    Q["mysqli query x5 COUNT<br/>fetch_assoc x5"]
    FTR["includes/footer.php"]
    DT["date Y"]
    HE["htmlspecialchars"]

    IDX --> DBF
    IDX --> AUTH
    AUTH --> P1
    AUTH --> SS2
    IDX --> RL --> IL
    RL -. "not logged in: header Location + exit" .-> OUT(["auth/index.php"])
    IDX --> HDR
    HDR --> P2
    HDR -. "function_exists" .-> CU
    CU --> IL
    HDR --> NA
    HDR --> HE
    IDX --> Q
    IDX --> CU
    IDX --> HE
    IDX --> FTR --> DT
```

## 3. Relationships and observations

| Caller | Callee | Purpose |
|--------|--------|---------|
| `login_handler.php` | `paths.php`, `db.php` | defines `BASE_URL`; opens the global `$conn` |
| `login_handler.php` | `session_start()` (L3) | raw call; `auth.php` guards it with `session_status()` instead |
| `index.php` | `requireLogin()` → `isLoggedIn()` | the access guard |
| `header.php` | `currentUser()` → `isLoggedIn()` | display name |

| Ref | Evidence | Status |
|-----|----------|--------|
| CG-06 | Session start is done in **four** places by different styles: `login_handler.php` L3, `auth/index.php` L3, `auth/logout.php` L2 (all unconditional) and `auth.php` L19-21 (guarded). Session handling responsibility is not centralized. | **Confirmed** |
| CG-07 | `login_handler.php` never includes `auth.php`; it duplicates the session-key contract (`user_id`, `username`, `user_email`, `role`) that `auth.php` reads. The two files are coupled only by convention (documented in `auth.php` L10-15). | **Confirmed** |
