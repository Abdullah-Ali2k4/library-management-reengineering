# CG 05: Catalog Operations Call Graphs (`books/add.php`, `books/delete.php`)

`members/*` and `authors/*` pages follow the same structure (shared include set and per-page SQL).

## 1. `books/add.php`

```mermaid
flowchart TD
    ADD["books/add.php<br/>top-level script"]
    DBF["config/db.php L2"]
    AQ["mysqli query L8<br/>SELECT authors"]
    TR["trim x5, is_numeric, empty<br/>L12-27"]
    ISBN["prepare, bind_param, execute,<br/>store_result, close<br/>ISBN uniqueness L30-37"]
    INS["prepare, bind_param, execute, close<br/>INSERT books L45-53"]
    HDR["include header.php L57"]
    FTR["include footer.php L110"]
    HE["htmlspecialchars<br/>errors, author names"]

    ADD --> DBF
    ADD --> AQ
    ADD --> TR --> ISBN --> INS
    ADD --> HDR
    ADD --> HE
    ADD --> FTR
```

## 2. `books/delete.php`

```mermaid
flowchart TD
    DEL["books/delete.php<br/>top-level script"]
    DBF["config/db.php L2"]
    CAST["isset, int cast, die L4-8"]
    CNT["prepare, bind_param, execute,<br/>get_result, fetch_assoc, close<br/>COUNT active issues L11-15"]
    HDR["include header.php L19<br/>only on the refusal path"]
    FTR["include footer.php L22"]
    DELQ["prepare, bind_param, execute, close<br/>DELETE books L28-31"]
    CLS["conn close"]
    RD["header Location view.php?deleted=1 + exit<br/>L34-35"]
    VIEW(["books/view.php"])

    DEL --> DBF
    DEL --> CAST
    CAST --> CNT
    CNT -- "active > 0" --> HDR --> FTR --> CLS
    CNT -- "active = 0" --> DELQ --> CLS --> RD -. "HTTP redirect" .-> VIEW
```

## 3. Observations

| Ref | Evidence | Status |
|-----|----------|--------|
| CG-14 | **Dead hand-off.** `books/`, `authors/` and `members/` `delete.php` redirect to `view.php?deleted=1`, but no file reads `$_GET['deleted']` (`grep` finds none). The flag is produced and never consumed, so users get no deletion feedback. | **Confirmed** |
| CG-15 | The "does this record have active loans" check is implemented separately in `books/delete.php` and `members/delete.php` (same query shape, different column). `authors/delete.php` has no check (relies on `ON DELETE SET NULL`). | **Confirmed** |
| CG-16 | Layout is included **after** logic in every page, so header output depends on state set earlier (`$current_page` globals). On the refusal path of `delete.php` the page is assembled by `include` calls in the middle of an `if` block (L19-22), mixing control flow and layout. | **Confirmed** |
