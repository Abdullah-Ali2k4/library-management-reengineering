# DFG 05 — Book Create / Update (`books/add.php`, `books/edit.php`)

**Workflow:** Catalog maintenance  **Entry:** `POST title, author_id, isbn, category, total_copies [, available_copies]`  **Sink:** `books` INSERT / UPDATE
`members/add.php` follows the same pattern (trim → validate → uniqueness SELECT → prepared INSERT).

## 1. Data Flow Graph — create (`books/add.php`)

```mermaid
flowchart LR
    U(["Browser<br/>add-book form"])
    A0["P0 L8<br/>SELECT authors for dropdown"]
    P1["P1 L12-16<br/>trim 5 fields"]
    P2["P2 L19-27<br/>validate: title, author, copies numeric ge 1"]
    P3["P3 L28-38<br/>ISBN uniqueness SELECT<br/>only when isbn not empty"]
    P4["P4 L42-43<br/>total int cast<br/>available = total"]
    P5["P5 L45-54<br/>prepared INSERT"]
    BK[("books")]
    AU[("authors")]
    ER["errors array"]
    OUT(["Browser<br/>HTML"])

    AU -- "author_id, first_name, last_name" --> A0
    A0 -- "options, names escaped" --> OUT
    U -- "title, author_id, isbn, category, total_copies" --> P1
    P1 --> P2
    P2 -- "messages" --> ER
    P1 -- "isbn" --> P3
    P3 -- "isbn bound" --> BK
    BK -- "row count" --> P3
    P3 -- "duplicate message" --> ER
    P1 -- "title, author_id, isbn, category, total_copies" --> P4
    P4 -- "6 values, bind sisssi" --> P5
    P5 --> BK
    P5 -- "success flag / error" --> OUT
    ER -- "escaped" --> OUT

    classDef input fill:#ffe8cc,stroke:#d9822b,color:#000;
    classDef store fill:#dbeafe,stroke:#2563eb,color:#000;
    classDef out fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class U input;
    class BK,AU store;
    class OUT out;
```

## 2. Data Flow Graph — update (`books/edit.php`)

```mermaid
flowchart LR
    U(["Browser<br/>URL id + edit form"])
    P0["P0 L8-10<br/>book_id = GET id, else POST book_id<br/>int cast, must be gt 0"]
    P1["P1 L19-24<br/>trim 6 fields incl. available_copies"]
    P2["P2 L26-40<br/>validate incl. available le total"]
    P3["P3 L41-51<br/>ISBN unique excluding this book"]
    P4["P4 L53-66<br/>int casts, prepared UPDATE"]
    P5["P5 L69-78<br/>re-SELECT book, die if missing"]
    P6["P6 L81-86<br/>POST value else DB value"]
    BK[("books")]
    OUT(["Browser<br/>form re-rendered, escaped"])

    U -- "id, form fields" --> P0
    P0 --> P1 --> P2 --> P3 --> P4
    P4 -- "7 values, bind sisssii" --> BK
    BK -- "fresh row" --> P5
    P5 -- "book row" --> P6
    P1 -- "raw POST values" --> P6
    P6 -- "htmlspecialchars" --> OUT

    classDef input fill:#ffe8cc,stroke:#d9822b,color:#000;
    classDef store fill:#dbeafe,stroke:#2563eb,color:#000;
    classDef out fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class U input;
    class BK store;
    class OUT out;
```

## 3. Def-use table (`add.php`)

| Variable | DEF | MOD | USE-P | USE-C |
|----------|-----|-----|-------|-------|
| `$title` | L12 | | L19 | L46 |
| `$author_id` | L13 (string) | | L22 | L46 (`bind "i"`), L88 (selected option) |
| `$isbn` | L14 | | L28 | L31, L46 (**`''` is bound as a real value**) |
| `$category` | L15 | | | L46 |
| `$total_copies` | L16 (string) | L42 `(int)` | L25 | L46 (bound as `"s"`) |
| `$available_copies` | L43 (= total) | | | L46 |
| `$errors` | L4 | L20, 23, 26, 35, 51 | L41, L68 | L71-73 |

## 4. Data paths

| Path | Trace | Safe? |
|------|-------|-------|
| all text → SQL | `trim` → prepared `INSERT` / `UPDATE` | SQL-safe |
| all text → HTML | `htmlspecialchars` on every echo (edit L113-138) | XSS-safe |
| `book_id` (edit) | `(int)` cast at L8 before any use, so L109-110 print an int | **Yes** |
| `available_copies` (edit) | user value, only checked `0 ≤ x ≤ total` (L35-38) | **Not tied to real loans** |

## 5. Findings

| Ref | Evidence | Status |
|-----|----------|--------|
| DF-03 | **Empty ISBN.** L28 skips the uniqueness check when `$isbn === ''`, but L46 still inserts `''` into `books.isbn`, which is `UNIQUE`. `''` is not NULL, so the **second** book without an ISBN fails with the generic message at L51. Same in `edit.php` L41/L58. | **Confirmed** (code + schema) |
| DF-02 | `edit.php` L55-58 writes any `available_copies` between 0 and `total_copies`, independent of active loans in `issued_books`. `total_copies` can also be lowered below the number of copies currently out. Four writers of this field exist. | **Confirmed** |
| DF-16 | `edit.php` L70-73 re-reads the book into `$book`, but L81-86 immediately prefer `$_POST[...]` for all six form fields. On a POST, the fresh DB read is never used (dead definition except for the L76 existence check), and the form shows the **untrimmed** submitted text even though the **trimmed** text was stored. | **Confirmed** |
| DF-04 | `author_id` is only checked for non-empty (add L22). A non-numeric value is bound as `"i"` (becomes 0) and fails the foreign key at INSERT time. | Likely |
| DF-01 | No server-side or HTML length limits (`title` 150, `category` 50, `isbn` 20). `grep` finds no `strlen`/`maxlength` for these. | **Confirmed** (absence) |
| Minor | `total_copies` is bound with type `"s"` (add L46, edit L58) although it was cast to int. MySQL coerces it, so it works but is inconsistent. `edit.php` L8 accepts `book_id` from both URL and hidden field; the URL wins, so the hidden field is redundant. | **Confirmed** (low) |
| DF-06 / DF-05 | Both scripts lack `requireLogin()` and CSRF tokens. | **Confirmed** |
