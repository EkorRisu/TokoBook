TokoBook - Simple PHP Book Store

Setup:
1. Import `sql/schema.sql` into your MySQL (phpMyAdmin or mysql CLI) to create the database and tables.
2. Ensure `db.php` credentials match your MySQL server (default is root with no password in workspace).
3. Place the project in your webroot (c:/laragon/www/TokoBook). Access http://localhost/TokoBook/

Features implemented:
- User register/login/logout
- Search books by title
- Admin panel: add/edit/delete books, list users, list/update orders, generate CSV report
- Contact form (stores messages)
- Cart and checkout (creates orders)

Notes:
- This is a minimal demo. For production, add proper validation, CSRF protection, and file uploads for images.
