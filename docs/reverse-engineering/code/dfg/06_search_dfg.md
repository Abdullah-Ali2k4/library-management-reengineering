# DFG 06 — Book Search (`search.php`)

**Workflow:** Read-only query from user input  **Entry:** `GET q`  **Sink:** prepared `LIKE` query, HTML table

## 1. Data Flow Graph

```mermaid
flowchart LR
    U(["Browser<br/>GET q"])
    P1["P1 L5<br/>query = trim q"]
    P2["P2 L8<br/>gate: query not empty"]
    P3["P3 L9<br/>like = percent + query + percent"]
    P4["P4 L10-22<br/>prepared SELECT, 4 LIKE params<br/>books LEFT JOIN authors"]
    BK[("books")]
    AU[("authors")]
    P5["P5 L48-62<br/>loop rows, escape, cast ints"]
    OUT(["Browser<br/>HTML table"])

    U -- "q (string)" --> P1 --> P2
    P2 -- "non-empty" --> P3
    P3 -- "like, bound 4 times" --> P4
    P4 --> BK
    P4 --> AU
    BK -- "book columns" --> P5
    AU -- "first_name, last_name" --> P5
    P1 -- "query, htmlspecialchars L31, L37" --> OUT
    P5 -- "title, author, category escaped; copies int; edit link book_id int" --> OUT

    classDef input fill:#ffe8cc,stroke:#d9822b,color:#000;
    classDef store fill:#dbeafe,stroke:#2563eb,color:#000;
    classDef out fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    class U input;
    class BK,AU store;
    class OUT out;
```

## 2. Def-use table

| Variable | DEF | MOD | USE-P | USE-C |
|----------|-----|-----|-------|-------|
| `$query` | L5 | | L8, L36 | L9, L31, L37 (escaped) |
| `$like` | L9 | | | L20 (`bind "ssss"`) |
| `$stmt` | L10 | | | L20-22 |
| `$results` | L6 (null) | L22 | L48 | L49 (loop) |
| `$row` | L49 (per iteration) | | | L51-56 |

## 3. Data paths

| Path | Trace | Safe? |
|------|-------|-------|
| `q` → SQL | `trim` → `"%".$q."%"` → `bind_param` ×4 | SQL-injection safe |
| `q` → HTML | `htmlspecialchars` at L31 (attribute) and L37 (heading) | XSS safe |
| DB → HTML | `title`, author, `category` escaped; `total/available_copies` and `book_id` cast `(int)` | XSS safe |

## 4. Findings

| Ref | Evidence | Status |
|-----|----------|--------|
| DF-07/08 | Taint analysis finds **no** injection or XSS path: one untrusted source (`q`), one parameterized sink, every output escaped or int-cast. | **Confirmed** (positive) |
| DF-09 | `%` and `_` typed by the user are not escaped before L9, so they act as wildcards (`%` lists the whole catalog). Low impact. | **Confirmed** (low) |
| DF-06 | No `requireLogin()`, and L56 emits an **Edit** link per result to `books/edit.php`, which is also unguarded. | **Confirmed** |
