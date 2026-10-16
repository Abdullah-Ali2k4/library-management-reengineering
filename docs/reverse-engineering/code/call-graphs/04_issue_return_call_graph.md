# CG 04: Issue and Return Workflow Call Graphs

## 1. `issue_return/issue.php`

```mermaid
flowchart TD
    ISS["issue.php<br/>top-level script"]
    DBF["config/db.php L2"]
    Q["mysqli query x2<br/>books, members<br/>L8-9"]
    TRIM["trim x3, strtotime x2, date<br/>L12-26"]
    TX["begin_transaction L31"]
    CHK["prepare, bind_param, execute,<br/>get_result, fetch_assoc, close<br/>SELECT FOR UPDATE L36-40"]
    EX["throw Exception L43"]
    INS["prepare, bind_param, execute, close<br/>INSERT issued_books L48-51"]
    UPD["prepare, bind_param, execute, close<br/>UPDATE books L53-56"]
    CM["commit L58"]
    RB["rollback L61, in catch"]
    HDR["include header.php L67"]
    FTR["include footer.php L118"]
    CLS["conn close L119"]
    HE["htmlspecialchars x3<br/>L82, L94, L104"]

    ISS --> DBF
    ISS --> Q
    ISS --> TRIM
    ISS --> TX --> CHK
    CHK -- "unavailable" --> EX --> RB
    CHK --> INS --> UPD --> CM
    ISS --> HDR
    ISS --> HE
    ISS --> FTR
    ISS --> CLS
```

## 2. `issue_return/return.php`

```mermaid
flowchart TD
    RET["return.php<br/>top-level script"]
    DBF["config/db.php L2"]
    Q1["mysqli query L8-15<br/>3-table JOIN"]
    TRIM["trim L18"]
    TX["begin_transaction L25"]
    CHK["prepare, bind_param, execute,<br/>get_result, fetch_assoc, close<br/>SELECT FOR UPDATE L29-33"]
    EX["throw Exception x2<br/>L36, L39"]
    U1["prepare ... UPDATE issued_books<br/>L44-47"]
    U2["prepare ... UPDATE books<br/>L49-52"]
    CM["commit L54"]
    RB["rollback L57"]
    Q2["mysqli query L62-69<br/>SAME JOIN, duplicated"]
    HDR["include header.php L73"]
    FTR["include footer.php L111"]

    RET --> DBF
    RET --> Q1
    RET --> TRIM
    RET --> TX --> CHK
    CHK -- "missing or already returned" --> EX --> RB
    CHK --> U1 --> U2 --> CM
    RET --> Q2
    RET --> HDR
    RET --> FTR
```

## 3. Shared shape and duplication

| Aspect | `issue.php` | `return.php` |
|--------|------------|--------------|
| Transaction boundary | L31-L58 | L25-L54 |
| Locking read | `books` row | `issued_books` row |
| Tables written | `issued_books` (INSERT), `books` (UPDATE) | `issued_books` (UPDATE), `books` (UPDATE) |
| Page assembly | `header.php` + `footer.php` | same |

| Ref | Evidence | Status |
|-----|----------|--------|
| CG-11 | The same transaction pattern (begin, locking SELECT, 2 writes, commit/rollback) is **copy-pasted** in two scripts; there is no shared function such as `issueBook()` / `returnBook()`. Business rules about `available_copies` therefore live inside two presentation scripts (plus `books/edit.php`). | **Confirmed** |
| CG-12 | `return.php` contains the identical JOIN query twice (L8-15 and L62-69). | **Confirmed** |
| CG-13 | `issue.php` and `return.php` call `header.php` without `auth.php`, so `header.php`'s `currentUser()` call is skipped (see CG-02). | **Confirmed** |
