# CFG 02 — `issue_return/issue.php` (Issue a Book)

**Workflow:** Core business transaction  **Type:** Script (form handler + page template)  **Lines:** 120
**Why selected:** Validation chain with `if/elseif`, a database transaction with `try/catch/rollback`, and a `throw`-driven error path.

## 1. Control Flow Graph — request-handling logic (L1-65)

```mermaid
flowchart TD
    S([Start])
    B0["B0 L2-9<br/>require db.php, init errors/success,<br/>query available books + members"]
    D1{"D1 L11<br/>method === POST?"}
    B1["B1 L12-14<br/>read + trim book_id, member_id, due_date"]
    D2{"D2 L16<br/>book_id === ''?"}
    A2["errors[] = select a book"]
    D3{"D3 L19<br/>member_id === ''?"}
    A3["errors[] = select a member"]
    D4{"D4 L22<br/>due_date === ''?"}
    A4["errors[] = choose a due date"]
    D5{"D5 L24<br/>due_date before today?"}
    A5["errors[] = date in the past"]
    D6{"D6 L28<br/>errors empty?"}
    B2["B2 L31<br/>begin_transaction"]
    B3["B3 L36-40<br/>SELECT available_copies FOR UPDATE"]
    D7a{"D7a L42<br/>book not found?"}
    D7b{"D7b L42<br/>available_copies &lt; 1?"}
    T["THROW L43<br/>no longer available"]
    B4["B4 L46-51<br/>INSERT issued_books"]
    B5["B5 L53-56<br/>UPDATE available_copies - 1"]
    B6["B6 L58-59<br/>commit, success = true"]
    C["CATCH L60-62<br/>rollback, errors[] = message"]
    R(["RENDER L67+<br/>include header, template, footer"])
    E([End L119-120<br/>conn close, script ends])

    S --> B0 --> D1
    D1 -- No --> R
    D1 -- Yes --> B1 --> D2
    D2 -- Yes --> A2 --> D3
    D2 -- No --> D3
    D3 -- Yes --> A3 --> D4
    D3 -- No --> D4
    D4 -- Yes --> A4 --> D6
    D4 -- No --> D5
    D5 -- Yes --> A5 --> D6
    D5 -- No --> D6
    D6 -- No --> R
    D6 -- Yes --> B2 --> B3 --> D7a
    D7a -- Yes --> T
    D7a -- No --> D7b
    D7b -- Yes --> T
    D7b -- No --> B4 --> B5 --> B6 --> R
    T --> C --> R
    R --> E

    classDef thr fill:#fde2e2,stroke:#c0392b,color:#000;
    classDef ok fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class T,C thr;
    class B6 ok;
```

## 2. Conditional branches

| ID | Line | Condition | True path | False path |
|----|------|-----------|-----------|------------|
| D1 | 11 | `REQUEST_METHOD === 'POST'` | B1 | RENDER (blank form) |
| D2 | 16 | `$book_id === ''` | add error, → D3 | D3 |
| D3 | 19 | `$member_id === ''` | add error, → D4 | D4 |
| D4 | 22 | `$due_date === ''` | add error, → D6 | D5 (`elseif`) |
| D5 | 24 | `strtotime($due_date) < strtotime(today)` | add error, → D6 | D6 |
| D6 | 28 | `empty($errors)` | B2 (transaction) | RENDER with errors |
| D7a | 42 | `!$book` | THROW | D7b |
| D7b | 42 | `$book['available_copies'] < 1` | THROW | B4 |
| catch | 60 | any `Exception` raised in the `try` | rollback, add message | n/a |

Template decisions (L72-115):

| Line | Construct | Meaning |
|------|-----------|---------|
| 72 | `if ($success)` | show success banner |
| 78 | `if (!empty($errors))` | show error list |
| 81 | `foreach ($errors ...)` | **loop** — one `<li>` per error |
| 92 | `while ($book = fetch_assoc())` | **loop** — one `<option>` per available book |
| 102 | `while ($member = fetch_assoc())` | **loop** — one `<option>` per member |

## 3. Loops

| Line | Type | Iterates over | Exit condition |
|------|------|---------------|----------------|
| 81 | `foreach` | `$errors` | array exhausted |
| 92 | `while` | `$books_result` rows | `fetch_assoc()` returns null |
| 102 | `while` | `$members_result` rows | `fetch_assoc()` returns null |

## 4. Return / exit paths

The script has **no** `exit`, `die`, `return` or redirect. Every path converges on RENDER then the end of the script.

| Path | Route | Page shown |
|------|-------|-----------|
| P1 | D1 false | Empty form |
| P2 | D1 true, D6 false | Form + validation errors |
| P3 | D6 true, D7a or D7b true, THROW, CATCH | Form + "no longer available" |
| P4 | D6 true, all checks pass, B6 | Form + success banner |
| P5 | D6 true, DB exception in B3/B4/B5 (PHP ≥ 8.1 mysqli throws by default), CATCH | Form + raw DB error text |

## 5. Metrics

| Metric | Logic (L1-65) | Whole file (incl. template) |
|--------|---------------|-----------------------------|
| Decision predicates | 9 (D1-D6, D7a, D7b, catch) | 14 (+L72, L78, L81, L92, L102) |
| V(G) | **10** | **15** |
| Terminal outcomes | 4 (P1-P4) + P5 | same |

## 6. Suspicious code and observations

| Ref | Evidence | Status |
|-----|----------|--------|
| F-01 | No `requireLogin()` anywhere in the file and `auth.php` is not included. No auth node exists on any path from Start to the INSERT. Anyone with the URL can issue books. | **Confirmed** |
| F-05 | The available-books query (L8) runs **before** the transaction (L31-58). After a successful issue (P4) the dropdown is rendered from the pre-update result, so it shows the old copy count and may still list a book whose last copy was just issued. `return.php` re-queries after its update (L62), so the two pages are inconsistent. | **Confirmed** |
| F-06 | After success (B6) the page renders in the same POST request: no Post-Redirect-Get. A browser refresh re-submits the POST and **issues a duplicate copy**. `return.php` is protected against the same replay by its L38 check. | **Confirmed** |
| F-11 | D7a's `!$book` branch is reachable only for a book id that does not exist (crafted POST). `member_id` is never checked for existence in PHP; a bad id surfaces only as a foreign-key error from the INSERT, whose message is echoed at L62/L82 (information disclosure in P5). | Likely |
| F-15 | L92 `while ($book = ...)` overwrites `$book`, which L39 already used for the locked row. Harmless today because L39's value is not used after the transaction, but it is a shadowed variable. | **Confirmed** (low) |
