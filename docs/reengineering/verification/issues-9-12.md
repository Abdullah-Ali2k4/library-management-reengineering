# Issues 9–12 Reengineering Verification

## Implemented policy

- `admin`: all book, author, and member create/update/delete operations.
- `admin` and `staff`: authenticated catalog access and circulation issue/return/status operations.
- Anonymous users: login and registration only. Protected pages and POST handlers enforce the policy server-side.
- Public registration creates `staff` accounts only; submitted role values are never used.
- Initial admin creation is a CLI-only, first-user bootstrap. The old known default password seed was removed.
- Book/member records with any lending history are retained; records with active loans are also blocked. Author deletion relies on the schema's `ON DELETE SET NULL` relation.
- All three deletion handlers require POST, a valid session CSRF token, an integer ID, an existing record, and admin authorization.
- Database errors return a generic message while diagnostic detail goes to the server error log. `?debug_paths=1` output and `test_connection.php` are removed.

## Automated checks

Run `php tests/security_regression.php` for static regression checks and `find . -name '*.php'` with `php -l` for PHP syntax checks. These checks do not replace authenticated HTTP integration tests against a configured MySQL database. Record runtime results in the project submission after running them in the target XAMPP/PHP environment.

## Initial administrator setup

After importing `database/library_db.sql` and `database/user_schema.sql`, run from the project directory in a terminal (not a browser):

```sh
php scripts/create_admin.php
```

This script refuses to create an administrator if any user already exists. Choose a unique username, valid email, and password of at least 10 characters. Configure `DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME` in the server environment for non-default environments. The blank-password `root` fallback is for local XAMPP development only.
