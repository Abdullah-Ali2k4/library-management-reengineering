# Use Case Diagram: Library Management System (whole system)

Recovered from the 27 PHP scripts of the uploaded project. Drawn with Mermaid in UML use-case style (actors outside the system boundary, ovals for use cases, dashed arrows for `include`).

```mermaid
flowchart LR
    V(["Visitor<br/>not logged in"])
    ST(["Staff user<br/>role staff"])
    AD(["Administrator<br/>role admin"])
    DBA(["MySQL database<br/>secondary actor"])

    subgraph SYS["Library Management System"]
        subgraph ACC["Account"]
            R1(["Register account"])
            L1(["Log in"])
            O1(["Log out"])
        end
        subgraph CAT["Catalog"]
            D1(["View dashboard"])
            B1(["View books"])
            B2(["Add book"])
            B3(["Edit book"])
            B4(["Delete book"])
            S1(["Search books"])
        end
        subgraph PPL["Authors and members"]
            A1(["View authors"])
            A2(["Add author"])
            A3(["Edit author"])
            A4(["Delete author"])
            M1(["View members"])
            M2(["Add member"])
            M3(["Edit member"])
            M4(["Delete member"])
        end
        subgraph CIRC["Circulation"]
            C1(["Issue book"])
            C2(["Return book"])
            C3(["View issue status"])
        end
        I1(["Verify credentials and role"])
        I2(["Check unique username or e-mail"])
        I3(["Check ISBN uniqueness"])
        I4(["Check active loans"])
        I5(["Check availability and lock copy"])
        I6(["Verify loan not already returned"])
        I7(["Check unique member e-mail"])
    end

    V --- R1
    V --- L1
    ST -. "can register, cannot log in" .-> R1
    AD --- L1
    AD --- O1
    AD --- CAT
    AD --- PPL
    AD --- CIRC
    SYS -. "reads and writes" .-> DBA

    L1 -. "include" .-> I1
    R1 -. "include" .-> I2
    B2 -. "include" .-> I3
    B3 -. "include" .-> I3
    B4 -. "include" .-> I4
    M4 -. "include" .-> I4
    C1 -. "include" .-> I5
    C2 -. "include" .-> I6
    M2 -. "include" .-> I7

    classDef guarded fill:#e1f5e1,stroke:#1f7a3d,color:#000;
    classDef open fill:#fde2e2,stroke:#c0392b,color:#000;
    classDef pub fill:#eeeeee,stroke:#666,color:#000;
    classDef inc fill:#fff,stroke:#999,stroke-dasharray:4 3,color:#000;
    class D1,B1 guarded;
    class B2,B3,B4,S1,A1,A2,A3,A4,M1,M2,M3,M4,C1,C2,C3 open;
    class R1,L1,O1 pub;
    class I1,I2,I3,I4,I5,I6,I7 inc;
```

**Colour key:** green = page enforces login, red = page enforces **no** login (any visitor who knows the URL can use it), grey = public by design (register, log in, log out).

## Actors

| Actor | Meaning | Evidence |
|-------|---------|----------|
| Visitor | Not logged in; can register and log in | `auth/register.php`, `auth/index.php` |
| Administrator | The only role that can log in; performs all management use cases | `auth/login_handler.php` L49 |
| Staff user | Account created by registration; **cannot log in** | `register.php` L51 vs `login_handler.php` L49 |
| MySQL database | Secondary actor: stores and returns all data | `config/db.php` |

## Use cases (20) and their source files

| Group | Use cases | Source |
|-------|-----------|--------|
| Account | Register account, Log in, Log out | `auth/register.php`, `auth/login_handler.php`, `auth/logout.php` |
| Catalog | View dashboard, View books, Add book, Edit book, Delete book, Search books | `index.php`, `books/*.php`, `search.php` |
| Authors | View, Add, Edit, Delete author | `authors/*.php` |
| Members | View, Add, Edit, Delete member | `members/*.php` |
| Circulation | Issue book, Return book, View issue status | `issue_return/*.php` |

Included behaviours: verify credentials and role (log in), unique username or e-mail (register), ISBN uniqueness (add and edit book), active-loan check (delete book and member), availability check with row lock (issue), already-returned check (return), unique member e-mail (add member).

## Observations

- Only 2 of the 17 management and reporting use cases (dashboard, view books) enforce login; the other 15 do not.
- A registered **Staff user** has no usable use case because log in always rejects that role.
- There is no use case for managing users, changing a password, renewing or reserving a book, or fines.
