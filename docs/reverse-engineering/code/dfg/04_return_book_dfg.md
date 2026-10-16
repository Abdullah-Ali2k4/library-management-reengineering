# DFG 04 — Return a Book (`issue_return/return.php`)

**Workflow:** Core business transaction  **Entry:** `POST issue_id`  **Sinks:** `issued_books` UPDATE, `books.available_copies` UPDATE

## 1. Data Flow Graph

```mermaid
flowchart LR
    U(["Browser<br/>return form"])
    P0["P0 L8-15<br/>query issued records<br/>JOIN books, members"]
    P1["P1 L18-22<br/>trim issue_id, empty check"]
    P2["P2 L25-33<br/>BEGIN, SELECT book_id, status<br/>FOR UPDATE"]
    P3["P3 L35-40<br/>exists and not already returned"]
    CLK(["Server clock<br/>return_date"])
    P4["P4 L42-47<br/>UPDATE issued_books<br/>status returned, return_date"]
    P5["P5 L49-52<br/>UPDATE books<br/>copies + 1 using record.book_id"]
    P6["P6 L54-59<br/>COMMIT or ROLLBACK"]
    P7["P7 L62-69<br/>re-query issued records"]
    IB[("issued_books")]
    BK[("books")]
    ER["errors array"]
    R["P8 L73-108<br/>render"]
    OUT(["Browser<br/>HTML"])

    IB -- "issue_id, due_date + joined title, full_name" --> P0
    P0 --> R
    U -- "issue_id" --> P1
    P1 -- "message" --> ER
    P1 -- "issue_id" --> P2
    P2 -- "issue_id bound" --> IB
    IB -- "record: book_id, status" --> P2
    P2 --> P3
    P3 -- "exception message" --> ER
    CLK --> P4
    P2 -- "issue_id" --> P4
    P4 --> IB
    P2 -- "record.book_id from DB, not from user" --> P5
    P5 --> BK
    P4 --> P6
    P5 --> P6
    P6 -- "success flag" --> R
    P6 --> P7
    IB -- "fresh rows" --> P7
    P7 --> R
    ER --> R
    R --> OUT

    classDef input fill:#ffe8cc,stroke:#d9822b,color:#000;
    classDef store fill:#dbeafe,stroke:#2563eb,color:#000;
    classDef out fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class U input;
    class IB,BK store;
    class OUT out;
```

## 2. Def-use table

| Variable | DEF | MOD | USE-P | USE-C |
|----------|-----|-----|-------|-------|
| `$issued_result` | L8 | **redefined L62** | L92 (`num_rows`) | L97 (loop) |
| `$issue_id` | L18 | | L20 | L30, L45 (`bind "i"`) |
| `$errors` | L4 | L21, L58 | L24, L82 | L85-87 |
| `$success` | L5 | L55 | L78 | |
| `$record` | L32 (`book_id`, `status`) | | L35, L38 | **L50** (`book_id` → `books` UPDATE) |
| `$return_date` | L42 (`date()`) | | | L45 |

## 3. Data paths

| Path | Trace | Safe? |
|------|-------|-------|
| `issue_id` → lookup | `$_POST` → `trim` → `bind "i"` → locked SELECT | SQL-safe |
| **`book_id` for the counter** | Comes from the **DB row** (`$record['book_id']`, L50), **not** from user input. A client cannot choose which book's counter is incremented | **Better than `issue.php`**, where `book_id` is user-supplied |
| double return | Status read under row lock (L29), rejected at L38 | **Yes** (idempotent) |
| DB → dropdown | `title`, `full_name`, `due_date` through `htmlspecialchars` (L99); `issue_id` raw (L98, an int from DB) | Safe |

## 4. Findings

| Ref | Evidence | Status |
|-----|----------|--------|
| DF-09 | L62-69 re-defines `$issued_result` with the identical query from L8-15 even when the return failed. Duplicate def with no change in meaning. | **Confirmed** |
| F-07a | `$errors` can only be filled by L21, so L24 always equals the negation of L20: two of four data-dependent paths cannot occur. | **Confirmed** |
| DF-06 | No `requireLogin()`: unauthenticated users can alter `issued_books` and `books`. | **Confirmed** |
| DF-02 | `+ 1` has no upper bound against `total_copies`; if `books/edit.php` ever lowered `total_copies` or set `available_copies` independently, returns can push `available_copies` above `total_copies`. | Likely (derived from the writers listed in `00_data_inventory.md`) |
