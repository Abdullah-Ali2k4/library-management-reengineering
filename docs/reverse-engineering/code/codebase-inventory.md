# Codebase Inventory — Library Management System

## 1. Project Overview

The Library Management System is a web-based application developed using PHP and MySQL. It supports the management of books, authors, library members, user accounts, and book issue and return operations.

This document records the system's structure, technologies, database relationships, file responsibilities, application workflows, dependencies, and potential security or data-integrity issues as part of a software reverse-engineering task.

The inventory is based on the README, SQL scripts, directory structure, and PHP, JavaScript, and CSS source code supplied for inspection. The application has not been fully executed or runtime-tested.

## 2. Technologies Used

| Technology | Purpose |
|---|---|
| PHP (procedural) | Server-side application logic |
| MySQL | Relational database |
| MySQLi | PHP-to-MySQL database connectivity |
| HTML | Web page structure and forms |
| CSS | Layout, styling, and responsive design |
| Vanilla JavaScript | Client-side validation and interface interactions |
| Font Awesome | Icons |
| Git and GitHub | Version control and project hosting; repository configuration not verified |

## 3. Directory Structure

| Directory or file | Purpose |
|---|---|
| `assets/css/` | Application stylesheet |
| `assets/js/` | Client-side JavaScript |
| `auth/` | Login, registration, logout, and authentication processing |
| `authors/` | Author management |
| `books/` | Book management |
| `config/` | Database connection and base URL configuration |
| `database/` | SQL schema and sample data |
| `docs/reverse-engineering/code/` | Reverse-engineering documentation |
| `includes/` | Shared layout components and authentication helper |
| `issue_return/` | Book issue, return, and status operations |
| `members/` | Library member management |
| `index.php` | Dashboard |
| `search.php` | Book search |
| `README.md` | Project overview, features, and setup instructions |
| `test_connection.php` | Database connection and sample-query test |

## 4. Main Files and Responsibilities

| File | Responsibility |
|---|---|
| `index.php` | Displays dashboard statistics and quick-action links |
| `search.php` | Searches books by title, category, and author |
| `config/db.php` | Establishes the MySQL connection |
| `config/paths.php` | Calculates the application base URL |
| `includes/auth.php` | Provides login, role-checking, and current-user functions |
| `includes/header.php` | Shared page header, navigation, and layout opening |
| `includes/footer.php` | Shared footer, layout closing, and JavaScript loading |
| `assets/js/validate.js` | Client-side validation and common interface interactions |
| `assets/css/style.css` | Global styling and responsive layout |
| `database/library_db.sql` | Creates the main library tables and inserts sample data |
| `database/user_schema.sql` | Creates the authentication table and initial administrator record |
| `auth/login_handler.php` | Processes login submissions |
| `auth/register.php` | Validates and creates user accounts |
| `auth/logout.php` | Clears the current session and logs the user out |

## 5. Application Features

The inspected source code and project documentation identify the following features:

- User registration, administrator login, and logout.
- Dashboard statistics for books, authors, members, active issues, and overdue books.
- Book creation, listing, editing, and deletion.
- Author creation, listing, and editing.
- Member creation, listing, editing, and deletion.
- Book issuing and returning.
- Tracking of issue dates, due dates, return dates, and issue status.
- Overdue identification.
- Searching books by title, author name, and category.
- Shared navigation and responsive page layouts.

The Authors Delete implementation was not inspected, so its exact behavior has not been verified.

## 6. Database Overview

The application uses a MySQL database named `library_db`. Its schema is defined in `database/library_db.sql` and `database/user_schema.sql`.

### 6.1 Tables

| Table | Primary key | Purpose |
|---|---|---|
| `users` | `user_id` | Stores login credentials and user roles |
| `authors` | `author_id` | Stores author names and biographies |
| `books` | `book_id` | Stores book details and copy counts |
| `members` | `member_id` | Stores member contact information |
| `issued_books` | `issue_id` | Records book loans, due dates, returns, and status |

### 6.2 Important Fields

**`users`**
- `user_id`: Auto-incrementing primary key.
- `username`: Required and unique username.
- `email`: Required and unique email address.
- `password_hash`: Stores a password hash.
- `role`: Accepts `admin` or `staff`; defaults to `staff`.
- `created_at`: Automatically records the creation timestamp.

**`authors`**
- `author_id`: Primary key.
- `first_name`: Required first name.
- `last_name`: Required last name.
- `bio`: Optional biography.

**`books`**
- `book_id`: Primary key.
- `title`: Required book title.
- `author_id`: References an author and may be `NULL`.
- `isbn`: Optional ISBN with a unique constraint.
- `category`: Optional category.
- `total_copies`: Total number of copies, defaulting to 1.
- `available_copies`: Number of available copies, defaulting to 1.

**`members`**
- `member_id`: Primary key.
- `full_name`: Required member name.
- `email`: Required, unique email address.
- `phone`: Optional phone number.

**`issued_books`**
- `issue_id`: Primary key.
- `book_id`: References a book.
- `member_id`: References a member.
- `issue_date`: Date the book was issued.
- `due_date`: Expected return date.
- `return_date`: Actual return date, nullable until returned.
- `status`: Either `issued` or `returned`; defaults to `issued`.

### 6.3 Relationships

The database defines these foreign-key relationships:

1. `books.author_id` references `authors.author_id`.
2. `issued_books.book_id` references `books.book_id`.
3. `issued_books.member_id` references `members.member_id`.

An author may have multiple books. A book may have multiple issue records over time, and a member may borrow multiple books over time.

There is no declared foreign-key relationship between `users` and the library tables.

### 6.4 Constraints and Referential Actions

- All five tables have auto-incrementing primary keys.
- Book ISBN values must be unique when stored as non-`NULL` values.
- Member emails and user usernames/emails are unique.
- Required fields are protected by `NOT NULL` constraints.
- Deleting an author sets associated `books.author_id` values to `NULL`.
- Deleting a book cascades to its associated `issued_books` records.
- Deleting a member cascades to that member's associated `issued_books` records.
- Issue status is restricted to `issued` or `returned`.
- User role is restricted to `admin` or `staff`.

The cascading rules can remove borrowing history when books or members are deleted.

### 6.5 Sample Data

The main SQL script includes sample records for three authors, five books, three members, and two issue records. It also decreases the available-copy count for one book.

The user schema inserts an initial administrator account. The README lists the initial credentials as `admin@library.local` / `admin123`.

Default credentials should be changed before deployment. The supplied hash and successful login have not been independently verified.

### 6.6 Database Verification Status

The table definitions, relationships, constraints, and sample-data statements were inspected from the supplied SQL source. The SQL scripts were not executed, and the actual database contents have not been verified.

## 7. Database Configuration and Dependencies

### 7.1 `config/db.php`

**Purpose:** Establishes the MySQL database connection.

Configured settings:

| Setting | Value |
|---|---|
| Host | `localhost` |
| Username | `root` |
| Password | Empty string |
| Database | `library_db` |
| Character set | `utf8mb4` |

The file uses object-oriented MySQLi, checks for connection errors, and sets the connection character set.

**Observations:**
- These settings are typical of a local development environment.
- A production deployment should use a dedicated database account and securely managed credentials.
- Exposing raw database error details to ordinary users should be avoided in production.
- Successful database connectivity has not been tested.

### 7.2 `config/paths.php`

**Purpose:** Calculates the application's base URL and defines the `BASE_URL` constant.

The file:
- Uses `realpath()` to resolve the project root and server document root.
- Normalizes Windows path separators.
- Determines the URL prefix when the project is located inside the document root.
- Falls back to `/` if path resolution fails or the expected directory relationship is not found.
- Provides optional diagnostic output through the `debug_paths` query parameter.

**Observations:**
- Dynamic URL calculation reduces dependence on a hardcoded project directory.
- The fallback may produce incorrect links if the application is deployed outside the expected directory structure.
- Debug output reveals filesystem paths and server configuration information. It should be removed or restricted before public deployment.
- Correct URL generation has not been runtime-tested.

## 8. Authentication Module Analysis

### 8.1 Login Processing — `auth/login_handler.php`

**Purpose:** Authenticates users submitting the login form.

**Processing flow:**
1. Loads the path configuration, starts a session, and includes the database connection.
2. Accepts POST requests and redirects other requests to the login page.
3. Checks that email and password values are provided.
4. Retrieves the user by email through a prepared SQL statement.
5. Verifies the submitted password using `password_verify()`.
6. Checks that the account's role is `admin`.
7. Regenerates the session ID after successful authentication.
8. Stores the user ID, username, email, and role in the session.
9. Optionally extends the session cookie expiration when Remember Me is selected.
10. Redirects successful logins to the dashboard and failed logins to the login page.

**Security mechanisms observed:**
- Prepared SQL statements.
- Password hash verification.
- Session ID regeneration.
- Generic login failure messages.
- HTTP-only and SameSite cookie attributes inherited or configured through session cookie parameters.

**Findings:**
- Login explicitly accepts only the `admin` role.
- Registration creates accounts with the `staff` role, so those accounts cannot authenticate through this handler.
- The Remember Me implementation extends the session cookie expiration; persistent sessions also depend on PHP session configuration.
- `get_result()` requires a compatible MySQL native driver environment.
- The handler does not visibly implement login rate limiting or CSRF protection.
- The supplied source does not establish whether secure cookies are enabled in the actual deployment.

### 8.2 Registration — `auth/register.php`

**Purpose:** Validates and creates user accounts.

The registration code:
- Redirects users who are already logged in.
- Checks required username, email, and password values.
- Validates email formatting.
- Requires a password of at least six characters.
- Checks that the password and confirmation match.
- Uses a prepared statement to check username/email uniqueness.
- Hashes the password using `password_hash()` with `PASSWORD_DEFAULT`.
- Creates the account with the `staff` role.
- Displays success or validation messages.

**Findings:**
- The password length requirement is relatively weak by modern security standards.
- The password is trimmed before hashing, which can unintentionally change the user's intended password.
- Public registration is possible from the supplied logic unless restricted elsewhere.
- The form does not visibly include CSRF protection.
- The database's unique constraints remain important because application-level uniqueness checks alone cannot eliminate simultaneous-registration race conditions.

### 8.3 Logout — `auth/logout.php`

**Purpose:** Clears the current session and redirects to the login page.

The code starts a session, empties the session array, destroys the session, and redirects to `index.php` within the authentication directory.

**Finding:** The code does not explicitly expire the browser's session cookie. Cookie cleanup and session configuration should be reviewed.

### 8.4 Shared Authentication Guard — `includes/auth.php`

**Purpose:** Provides reusable authentication and role-checking functions.

Functions:
- `isLoggedIn()`: Checks whether `$_SESSION['user_id']` exists.
- `requireLogin()`: Redirects unauthenticated users to the login page.
- `requireRole($role)`: Requires login and checks the user's role, returning HTTP 403 when access is denied.
- `currentUser()`: Returns the current user's ID, username, email, and role, or `null` if logged out.

**Dependencies:**
- `config/paths.php`
- PHP sessions

**Findings:**
- The helper starts a session only when one is not already active.
- Including the helper does not automatically protect a page; the page must call `requireLogin()` or `requireRole()`.
- The login handler's session keys are consistent with the helper's expected data.
- The helper checks session state but does not independently validate the user against the database.

## 9. Dashboard Analysis — `index.php`

**Purpose:** Displays the main dashboard for authenticated users.

**Dependencies:**
- `config/db.php`
- `includes/auth.php`
- `includes/header.php`
- `includes/footer.php`

**Authentication:** Calls `requireLogin()` before displaying dashboard content.

**Database operations:** Executes five aggregate queries to calculate:
1. Total books.
2. Total authors.
3. Total members.
4. Currently issued records where `status = 'issued'`.
5. Overdue records where `status = 'issued'` and `due_date < CURDATE()`.

**Interface:**
- Personalized welcome message.
- Five statistics cards.
- Quick-action links to book management, issue/return operations, and search.

**Findings:**
- The username is escaped using `htmlspecialchars()`.
- The dashboard requires login but does not enforce a specific role.
- Database-query failures are not explicitly checked before the results are used.

## 10. Books Module Analysis

### 10.1 Files and Responsibilities

**`books/view.php`**
- Retrieves books and author names using a `LEFT JOIN`.
- Displays book information, including copy counts.
- Provides Edit and Delete links.
- Escapes displayed values using `htmlspecialchars()`.
- Calls `requireLogin()`.

**`books/add.php`**
- Retrieves authors for the author-selection dropdown.
- Validates the title, author selection, and total copies.
- Checks ISBN uniqueness using a prepared statement.
- Inserts a new book with available copies initially equal to total copies.

**`books/edit.php`**
- Retrieves a book by ID and pre-fills the form.
- Validates title, author, total copies, and available copies.
- Checks ISBN uniqueness while excluding the current book.
- Updates the selected book using a prepared statement.

**`books/delete.php`**
- Checks whether the book has active issue records.
- Blocks deletion when the book still has books issued.
- Otherwise deletes the book and redirects to the book list.

### 10.2 Database Dependencies

- `books`: Stores book details and copy counts.
- `authors`: Supplies author information.
- `issued_books`: Used to check active borrowing before deletion.

### 10.3 Security and Data-Integrity Findings

- The supplied Add, Edit, and Delete files do not explicitly call `requireLogin()`.
- The Delete operation is triggered through a GET request and has no visible CSRF protection.
- The `bind_param()` type strings in Add and Edit appear inconsistent with the intended integer fields for author IDs and copy counts. These should be corrected and tested.
- Empty ISBN values are stored as empty strings rather than normalized to `NULL`. Because ISBN is unique, multiple blank values may cause insertion errors.
- The Edit form allows available-copy counts to be changed independently of active issue records.
- The database's cascading delete rule can remove historical issue records when a book is deleted.
- Prepared statements are used for the main database writes and delete checks.

## 11. Authors Module Analysis

### 11.1 Files and Responsibilities

**`authors/view.php`**
- Lists authors and calculates the number of associated books using `LEFT JOIN` and `COUNT()`.
- Truncates biographies for display using `mb_strimwidth()`.
- Provides Edit and Delete links.

**`authors/add.php`**
- Collects first name, last name, and biography.
- Validates that the names are present.
- Inserts an author using a prepared statement.

**`authors/edit.php`**
- Retrieves an author by ID.
- Pre-fills the edit form.
- Validates the submitted names.
- Updates the author record using a prepared statement.

**`authors/delete.php`**
- The file is reported to exist, but its source code was not inspected. Its deletion logic and security controls remain unverified.

### 11.2 Database Dependencies

- `authors`: Stores author information.
- `books`: Used to count books associated with each author.
- The `books.author_id` foreign key uses `ON DELETE SET NULL`, preserving books when an author is deleted.

### 11.3 Security and Validation Findings

- The supplied View, Add, and Edit files do not explicitly call `requireLogin()`.
- Prepared statements are used for Add and Edit database writes.
- Displayed author information is generally escaped using `htmlspecialchars()`.
- Explicit server-side length checks for author names are not visible.
- `mb_strimwidth()` requires PHP's multibyte string extension.

## 12. Members Module Analysis

### 12.1 Files and Responsibilities

**`members/view.php`**
- Lists members and their contact information.
- Uses a `LEFT JOIN` with `issued_books` to calculate the number of currently issued books.
- Provides Edit and Delete links.

**`members/add.php`**
- Validates the full name and email.
- Checks email formatting and uniqueness.
- Inserts the member using a prepared statement.
- Displays success or error messages.

**`members/edit.php`**
- Retrieves a member by ID.
- Pre-fills the form with existing information.
- Validates the name and email.
- Checks email uniqueness while excluding the current member.
- Updates the member record using a prepared statement.

**`members/delete.php`**
- Checks whether the member has active issue records.
- Blocks deletion if the member still has books issued.
- Deletes the member and redirects to the member list if no active issues remain.

### 12.2 Database Dependencies

- `members`: Stores member names, email addresses, and phone numbers.
- `issued_books`: Used to count active issues and check whether deletion is allowed.

### 12.3 Security and Data-Integrity Findings

- Prepared statements are used for database operations involving supplied values.
- Displayed member details and validation messages are escaped using `htmlspecialchars()`.
- The supplied Members files do not explicitly call `requireLogin()`.
- The Delete operation uses a GET request and has no visible CSRF protection.
- The deletion code does not explicitly verify that the DELETE query affected a row.
- The database's cascading delete rule may remove historical issue records when a member is deleted, even if there are no active issues.

## 13. Issue and Return Module Analysis

### 13.1 Files and Responsibilities

**`issue_return/issue.php`**
- Lists books with at least one available copy.
- Lists registered members.
- Validates the selected book, member, and due date.
- Starts a database transaction.
- Locks the selected book row using `FOR UPDATE`.
- Rechecks copy availability.
- Inserts an issue record and decreases the available-copy count.
- Commits the transaction or rolls it back after an exception.

**`issue_return/return.php`**
- Lists issue records whose status is `issued`.
- Locks and checks the selected issue record.
- Rejects missing records and records already marked as returned.
- Sets the status to `returned` and records the return date.
- Increases the book's available-copy count.
- Commits the transaction or rolls it back after an exception.

**`issue_return/status.php`**
- Lists issue records with book and member details.
- Displays issue date, due date, return date, and status.
- Identifies overdue books and visually highlights them.

### 13.2 Database Dependencies

- `issued_books`: Stores loan and return information.
- `books`: Supplies book information and maintains available-copy counts.
- `members`: Supplies borrower information.

### 13.3 Issue Workflow

1. Retrieve books with available copies and registered members.
2. Display a form to select a book, member, and due date.
3. Validate required values and reject due dates earlier than the current date.
4. Start a database transaction.
5. Lock the selected book row and recheck availability.
6. Insert the issue record.
7. Decrease the book's available-copy count.
8. Commit the transaction, or roll back if an exception occurs.

### 13.4 Return Workflow

1. Retrieve issue records still marked as `issued`.
2. Select and lock the chosen issue record.
3. Confirm that the record exists and has not already been returned.
4. Update its status and return date.
5. Increase the corresponding book's available-copy count.
6. Commit the transaction, or roll back if an exception occurs.

### 13.5 Overdue Tracking

A book is considered overdue when both conditions are true:

- Its issue status is `issued`.
- Its due date is earlier than the current date.

The dashboard and status page use this rule to identify overdue records.

### 13.6 Security and Data-Integrity Findings

- Prepared statements are used for key issue and return database operations.
- Transactions and `FOR UPDATE` locking help prevent inconsistent updates to issue records and book-copy counts.
- Correct locking and rollback behavior depend on a transactional database engine, such as InnoDB.
- The supplied issue, return, and status files do not explicitly call `requireLogin()`.
- The forms do not show explicit CSRF protection.
- Server-side validation should verify that submitted IDs are valid integers and reference existing records.
- Due dates should be validated as actual calendar dates, not only compared using `strtotime()`.
- The return logic does not explicitly check that available copies remain at or below total copies.
- The status page identifies overdue records but does not itself send reminders or notifications.

## 14. Search Module Analysis — `search.php`

**Purpose:** Searches the library catalogue by book title, category, and author name.

### Processing Flow

1. Reads the search term from the GET parameter `q`.
2. Trims the submitted value.
3. If the search term is non-empty, adds wildcard characters for partial matching.
4. Executes a prepared SQL query joining `books` and `authors`.
5. Displays matching titles, author names, categories, and available/total copies.
6. Provides an Edit link for each result.

### Database Dependencies

- `books`: Book details, categories, and copy counts.
- `authors`: First and last names used in searching and display.

### Security Findings

- The search query uses a prepared statement.
- Search terms and displayed database values are escaped with `htmlspecialchars()`.
- The supplied search file does not explicitly call `requireLogin()`.
- Access to book-editing operations must be enforced by the destination page, not merely by displaying or hiding links.

## 15. Shared Interface Components

### 15.1 Shared Header — `includes/header.php`

**Purpose:** Provides the common HTML structure, navigation, user information, and opening layout elements.

Main features:
- Identifies the current page and folder for active-navigation highlighting.
- Displays the current username or a Guest label.
- Displays an avatar initial.
- Loads Font Awesome and `assets/css/style.css`.
- Provides sidebar navigation to the dashboard, books, authors, members, issue/return, status, search, and logout.
- Includes a mobile menu button and sidebar overlay.
- Opens the main-content area for individual pages.

**Findings:**
- The displayed username and avatar initial are escaped.
- The header does not independently enforce authentication or authorization.
- Each protected page must enforce its own access checks.

### 15.2 Shared Footer — `includes/footer.php`

**Purpose:** Closes the shared page layout and loads the global JavaScript file.

Main features:
- Closes the main content and layout elements.
- Displays the copyright symbol, current year, and project name.
- Loads `assets/js/validate.js` using `BASE_URL`.
- Closes the HTML body and document.

The header and footer work together as shared presentation components.

### 15.3 Global JavaScript — `assets/js/validate.js`

**Purpose:** Provides client-side form validation and shared interface interactions.

Main features:
1. Checks required fields, basic email formatting, and negative numeric inputs on forms with the `validate-form` class.
2. Displays validation messages beside invalid fields.
3. Controls the responsive sidebar, overlay, and collapsed-sidebar state.
4. Supports user-dropdown interactions, outside-click dismissal, Escape-key dismissal, and accessibility state updates.
5. Shows and hides password fields through elements with the `password-toggle` class.

**Security observations:**
- Client-side validation improves usability but does not replace server-side PHP validation.
- The backend must independently validate inputs before database operations.
- Error messages are inserted using `textContent`, avoiding HTML interpretation of the message string.
- Optional interface elements are checked before event handlers are attached.

### 15.4 Global CSS — `assets/css/style.css`

**Purpose:** Defines the shared visual design and responsive layout.

Main components:
- Dark navy sidebar and footer with a light main background.
- Blue primary buttons and active navigation states.
- Dashboard statistics and quick-action grids.
- Forms, inputs, buttons, error messages, and responsive tables.
- Status styling for issued, overdue, and returned records.
- Mobile navigation drawer and overlay.

**Responsive breakpoints:**
- `768px`: Adjusts navigation, dashboard, forms, and content for smaller screens.
- `420px`: Changes the statistics grid to one column and reduces content padding.

The stylesheet works with HTML classes and IDs defined in PHP templates. JavaScript toggles classes such as `open`, `visible`, and `sidebar-collapsed`.

## 16. Cross-Module Security and Data-Integrity Review

The following issues were identified from the supplied source code.

### 16.1 Access Control

Several supplied pages do not explicitly call `requireLogin()` or `requireRole()`, including files in the Add/Edit/Delete portions of the Books, Authors, and Members modules, as well as issue/return and search pages.

The existence of `includes/auth.php` does not automatically protect pages. Authentication and authorization must be enforced at the relevant entry points.

### 16.2 CSRF Protection

No explicit CSRF token generation and validation was observed in the supplied forms or deletion links. State-changing operations should be protected against cross-site request forgery.

### 16.3 Destructive GET Requests

Book, author, and member listings use Delete links. The inspected book and member deletion handlers perform deletion through GET requests. Destructive actions should use POST requests with appropriate authorization and CSRF protection.

The Authors Delete handler was not inspected, so its implementation remains unverified.

### 16.4 Input Validation

The application performs several server-side checks, including required fields, email formatting, uniqueness checks, positive copy counts, and due-date comparisons.

Further review should verify numeric ID validation, database error handling, field-length limits, and consistency between application rules and database constraints.

### 16.5 SQL Injection and Output Escaping

Many database operations use prepared statements, reducing SQL injection risk for the bound values. Displayed user-controlled and database values are generally escaped with `htmlspecialchars()`.

All database queries and output locations should still be reviewed to ensure consistent handling.

### 16.6 Book-Copy Consistency

The issue and return workflows use transactions and row locking to help maintain copy counts. However, manually editing available-copy values and not enforcing an upper limit during returns may lead to inconsistent inventory.

### 16.7 Borrowing-History Preservation

The database cascades book and member deletion to associated issue records. This can remove historical borrowing information. A production system may need a policy for preserving history, such as restricting deletion or using archival/soft-delete behavior.

### 16.8 Configuration and Error Handling

The database configuration uses local development credentials. The path configuration can reveal filesystem details through its debug parameter. Production configuration should use secure credentials and avoid exposing internal errors or paths.

## 17. Application Flow and Dependencies

The application's general flow can be summarized as follows:

1. The browser requests a PHP page.
2. The page includes the required configuration and shared components.
3. PHP connects to MySQL through `config/db.php` when database access is needed.
4. Protected pages are expected to enforce authentication through `includes/auth.php`.
5. Page-specific logic retrieves or modifies database records.
6. PHP renders HTML, using the shared header and footer where applicable.
7. CSS controls the page layout and appearance.
8. JavaScript provides client-side validation and interface interactions.
9. MySQL stores changes to books, authors, members, user accounts, and issue records.

### Main Dependency Relationships

- `index.php` depends on the database, authentication helper, header, and footer.
- Books pages depend on the `books` table and, where necessary, `authors` and `issued_books`.
- Authors pages depend on the `authors` table and book relationships.
- Members pages depend on the `members` table and issue records.
- Issue/return pages depend on `issued_books`, `books`, and `members`.
- `search.php` depends on `books` and `authors`.
- Authentication handlers depend on `users`, PHP sessions, and configuration files.
- Shared pages depend on the header, footer, CSS, JavaScript, and base URL configuration.

## 18. Reverse-Engineering Summary

The application follows a modular, procedural PHP structure. Each major library function is separated into its own directory, while configuration, shared layout, authentication helpers, and front-end resources are centralized.

The database uses five related tables. Prepared statements are used in many operations, passwords are hashed, and the issue/return workflows use transactions to help preserve inventory consistency.

The main areas requiring further security and quality review are consistent access control, CSRF protection, destructive actions, database error handling, book-copy integrity, and preservation of historical issue records.

## 19. Verification Status and Limitations

### Source Code Inspected

- Project README and directory structure.
- Database connection and path configuration.
- Main database schema and user schema.
- Login handler, registration logic, logout logic, and shared authentication helper.
- Dashboard.
- Shared header and footer.
- Global JavaScript and CSS.
- Books Add, View, Edit, and Delete files.
- Authors View, Add, and Edit files.
- Members View, Add, Edit, and Delete files.
- Issue, Return, and Status files.
- Search page.

**Final note:** This inventory documents the supplied implementation and identifies areas for further review. The findings are based on source inspection and should not be interpreted as confirmation that every feature works correctly at runtime.