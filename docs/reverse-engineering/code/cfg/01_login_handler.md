# CFG 01 — `auth/login_handler.php` (Login)

**Workflow:** Authentication  **Type:** Script (top-level, no functions)  **Lines:** 91
**Why selected:** Guard clauses, a short-circuit compound condition, an optional branch ("remember me") and five distinct exit points.

## 1. Control Flow Graph

```mermaid
flowchart TD
    S([Start<br/>L1-5: require paths.php, session_start, require db.php])
    D1{"D1 L8<br/>REQUEST_METHOD != POST?"}
    X1([EXIT-1 L9-10<br/>redirect index.php])
    B1["B1 L13-15<br/>read email, password, remember"]
    D2a{"D2a L18<br/>email === ''?"}
    D2b{"D2b L18<br/>password === ''?"}
    X2([EXIT-2 L19-21<br/>login_error + redirect])
    B2["B2 L25-30<br/>prepare SELECT user BY email"]
    D3{"D3 L32<br/>prepare failed?"}
    X3([EXIT-3 L33-35<br/>generic error + redirect])
    B3["B3 L38-44<br/>bind, execute, fetch_assoc, close"]
    D4{"D4 L48<br/>user found?"}
    D5{"D5 L49<br/>role === 'admin'?"}
    D6{"D6 L50<br/>password_verify OK?"}
    B4["B4 L54-60<br/>session_regenerate_id, set 4 session vars"]
    D7{"D7 L63<br/>remember checked?"}
    B5["B5 L64-77<br/>setcookie 30 days"]
    X5([EXIT-5 L81-82<br/>redirect BASE_URL index.php])
    X4([EXIT-4 L87-90<br/>Invalid email or password + redirect])

    S --> D1
    D1 -- Yes --> X1
    D1 -- No --> B1 --> D2a
    D2a -- Yes --> X2
    D2a -- No --> D2b
    D2b -- Yes --> X2
    D2b -- No --> B2 --> D3
    D3 -- Yes --> X3
    D3 -- No --> B3 --> D4
    D4 -- No --> X4
    D4 -- Yes --> D5
    D5 -- No --> X4
    D5 -- Yes --> D6
    D6 -- No --> X4
    D6 -- Yes --> B4 --> D7
    D7 -- Yes --> B5 --> X5
    D7 -- No --> X5

    classDef exit fill:#fde2e2,stroke:#c0392b,color:#000;
    classDef ok fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class X1,X2,X3,X4 exit;
    class X5 ok;
```

## 2. Conditional branches

| ID | Line | Condition | True path | False path |
|----|------|-----------|-----------|------------|
| D1 | 8 | `$_SERVER['REQUEST_METHOD'] !== 'POST'` | EXIT-1 | B1 |
| D2a | 18 | `$email === ''` | EXIT-2 | D2b |
| D2b | 18 | `$password === ''` (evaluated only if D2a false) | EXIT-2 | B2 |
| D3 | 32 | `!$stmt` (prepare failed) | EXIT-3 | B3 |
| D4 | 48 | `$user` (row found) | D5 | EXIT-4 |
| D5 | 49 | `$user['role'] === 'admin'` | D6 | EXIT-4 |
| D6 | 50 | `password_verify(...)` | B4 | EXIT-4 |
| D7 | 63 | `$remember` | B5 | EXIT-5 |

D4-D6 are one `if (a && b && c)` statement; PHP short-circuits, so `password_verify` runs only for an existing admin.

## 3. Loops

None.

## 4. Return / exit paths

| Exit | Lines | Type | Outcome |
|------|-------|------|---------|
| EXIT-1 | 9-10 | `header()` + `exit` | Non-POST request bounced to login form |
| EXIT-2 | 19-21 | `header()` + `exit` | Empty email or password |
| EXIT-3 | 33-35 | `header()` + `exit` | Statement preparation failed |
| EXIT-4 | 87-90 | `header()` + `exit` | Bad credentials **or** non-admin role (same message) |
| EXIT-5 | 81-82 | `header()` + `exit` | Successful login to dashboard |

Every path ends in `exit`; the script never falls off the end (no implicit return).

## 5. Metrics

| Metric | Value |
|--------|-------|
| Decision predicates (each `&&`/`||` operand counted) | 8 (D1, D2a, D2b, D3, D4, D5, D6, D7) |
| Cyclomatic complexity V(G) = predicates + 1 | **9** |
| Exit points | 5 |
| Independent paths to test | 9 |

## 6. Suspicious code and observations

| Ref | Evidence | Status |
|-----|----------|--------|
| F-03 | D5 (L49) only admits `role === 'admin'`, but `register.php` L51 always creates `'staff'`. No staff account can ever pass D5, so the "staff logs in" path is effectively dead. | **Confirmed** |
| F-04 | L14 reads `$_POST['password']` **without** `trim()`; `register.php` L22 stores a **trimmed** password. A password with leading/trailing spaces is hashed trimmed, then verified untrimmed, which always fails. | **Confirmed** |
| F-12 | "Remember me" (B5, L64-77) only re-sends the *session* cookie with a 30-day expiry. The server-side session file is still governed by `session.gc_maxlifetime` (PHP default ~24 min), so persistence beyond that depends on server config. | Likely |
| F-13 | D4/D5/D6 short-circuit: an unknown email skips `password_verify`, so response time differs from a wrong password for an existing admin (timing-based user enumeration). Messages themselves are uniform (L87). | Likely (low) |
