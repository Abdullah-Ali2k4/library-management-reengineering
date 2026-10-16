# CG 03: Registration Workflow Call Graph (`auth/register.php`)

## 1. Call graph

```mermaid
flowchart TD
    REG["auth/register.php<br/>top-level script"]
    PH["config/paths.php L7<br/>__DIR__ style"]
    DBF["config/db.php L8<br/>relative style"]
    AUTH["includes/auth.php L9"]
    IL["isLoggedIn L11"]
    RED["header Location + exit L12-13"]
    VAL["trim x4, filter_var, strlen<br/>L20-36"]
    DUP["mysqli: prepare, bind_param, execute,<br/>store_result, close<br/>L39-46"]
    HASH["password_hash L50"]
    INS["mysqli: prepare, bind_param,<br/>execute, close<br/>L53-61"]
    CLS["conn close L65"]
    HTML["inline HTML template L72-131<br/>htmlspecialchars"]

    REG --> PH
    REG --> DBF
    REG --> AUTH
    REG --> IL
    IL -- "true" --> RED
    REG --> VAL
    VAL --> DUP
    DUP --> HASH --> INS
    REG --> CLS
    REG --> HTML

    classDef ext fill:#fff4d6,stroke:#b8860b,color:#000;
    class DUP,INS,HASH,VAL ext;
```

## 2. Observations

| Ref | Evidence | Status |
|-----|----------|--------|
| CG-08 | `register.php` does **not** use `header.php`/`footer.php`: it writes its own `<head>` (L72-81) and links Font Awesome + `style.css`. `auth/index.php` also hand-writes its head (links `style.css` and `auth.css`, **no** Font Awesome). Three layout paths exist for the same UI. | **Confirmed** |
| CG-09 | Because `footer.php` is not included, `validate.js` is **not loaded** on the registration page; its form has no `validate-form` class. Validation is server-side only here. | **Confirmed** |
| CG-10 | Business rule "new accounts get role `staff`" (L51) lives in a page script, while the rule "only `admin` may log in" lives in a different page script (`login_handler.php` L49). Related rules are split across two files with no shared definition (this causes finding F-03 in the CFG report). | **Confirmed** |
