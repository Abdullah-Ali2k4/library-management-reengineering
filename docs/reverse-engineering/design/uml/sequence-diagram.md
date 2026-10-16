# Sequence Diagram: Library Management System (whole system)

One session from first visit to log out, in 7 phases: register and log in, dashboard, manage books/authors/members, issue, return, status and search, log out. Each phase is a coloured block.

```mermaid
sequenceDiagram
    actor U as Librarian
    participant B as Browser
    participant A as Auth scripts
    participant P as Page scripts
    participant C as Circulation scripts
    participant S as PHP session
    participant DB as MySQL

    rect rgb(238,242,255)
    Note over U,DB: 1. Register and log in
    opt first time only
        U->>B: fill registration form
        B->>A: POST register.php
        A->>DB: SELECT duplicate username or email
        A->>DB: INSERT user with role staff
        A-->>B: success page, staff cannot log in
    end
    U->>B: open the application
    B->>P: GET index.php
    P->>S: requireLogin, user_id set?
    S-->>P: no
    P-->>B: redirect to login page
    U->>B: submit email and password
    B->>A: POST login_handler.php
    A->>DB: SELECT user by email
    DB-->>A: user row
    alt role admin and password correct
        A->>S: regenerate id, store user_id, username, email, role
        A-->>B: redirect to dashboard
    else rejected
        A->>S: store login_error
        A-->>B: redirect to login page
    end
    end

    rect rgb(240,250,240)
    Note over U,DB: 2. Dashboard
    B->>P: GET index.php
    P->>S: user_id set, continue
    P->>DB: 5 COUNT queries
    DB-->>P: totals
    P-->>B: dashboard page
    end

    rect rgb(255,248,230)
    Note over U,DB: 3. Manage books, authors, members
    B->>P: GET view page
    P->>DB: SELECT list
    DB-->>P: rows
    P-->>B: list page
    B->>P: POST add or edit form
    P->>P: trim and validate
    P->>DB: INSERT or UPDATE, prepared statement
    P-->>B: page with success or errors
    B->>P: GET delete.php with id
    P->>DB: COUNT active loans
    alt active loans exist or id invalid
        P-->>B: refuse deletion
    else none
        P->>DB: DELETE row, cascade removes loan history
        P-->>B: redirect to list
    end
    end

    rect rgb(252,238,238)
    Note over U,DB: 4. Issue a book
    B->>C: GET issue.php
    C->>DB: SELECT available books and members
    C-->>B: form
    B->>C: POST book, member, due date
    C->>C: validate
    C->>DB: BEGIN
    C->>DB: SELECT copies FOR UPDATE
    alt copy available
        C->>DB: INSERT loan and UPDATE copies minus 1
        C->>DB: COMMIT
        C-->>B: success page
    else not available
        C->>DB: ROLLBACK
        C-->>B: error page
    end
    end

    rect rgb(252,238,238)
    Note over U,DB: 5. Return a book
    B->>C: GET return.php
    C->>DB: SELECT issued loans
    C-->>B: form
    B->>C: POST issue_id
    C->>DB: BEGIN
    C->>DB: SELECT loan FOR UPDATE
    alt loan open
        C->>DB: UPDATE loan returned and UPDATE copies plus 1
        C->>DB: COMMIT
    else missing or already returned
        C->>DB: ROLLBACK
    end
    C->>DB: SELECT issued loans again
    C-->>B: page with message and refreshed list
    end

    rect rgb(238,242,255)
    Note over U,DB: 6. Status and search
    B->>C: GET status.php
    C->>DB: SELECT loans JOIN books, members
    C-->>B: list with overdue highlighted
    B->>P: GET search.php with q
    P->>DB: SELECT with LIKE on title, ISBN, category, author
    P-->>B: results table
    end

    rect rgb(240,250,240)
    Note over U,DB: 7. Log out
    B->>A: GET logout.php
    A->>S: clear and destroy session
    A-->>B: redirect to login page
    end
    Note over B,S: only index.php and books/view.php check login, all other pages skip the check
```

## Participants and source files

| Participant | Stands for | Files |
|-------------|-----------|-------|
| Browser | the user's web browser | n/a |
| Auth scripts | login, registration, logout | `auth/index.php`, `auth/login_handler.php`, `auth/register.php`, `auth/logout.php` |
| Page scripts | dashboard, books, authors, members, search | `index.php`, `books/*.php`, `authors/*.php`, `members/*.php`, `search.php` |
| Circulation scripts | issue, return, status | `issue_return/issue.php`, `return.php`, `status.php` |
| PHP session | server-side session data | `includes/auth.php` |
| MySQL | database `library_db` | tables `users`, `authors`, `books`, `members`, `issued_books` |

## Notes (verified against the source)

- Every script talks to MySQL directly through `mysqli`; there is no separate data-access layer.
- Issue and return use `BEGIN`, a locking read, two writes and `COMMIT` or `ROLLBACK`.
- After a successful issue the same page is shown again with no redirect, so a browser refresh would submit it a second time.
- Only `index.php` and `books/view.php` check login (`requireLogin()`).
