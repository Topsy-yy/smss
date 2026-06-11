# Scholarship Management System (SMS)

A role-based PHP web application for managing scholarships.

It supports three main user roles:

- Student: sign up, log in, browse scholarships, and submit applications.
- Signatory: create and manage scholarships, review student applications.
- Admin: verify users, approve/reject scholarships, and manage platform access.

## Tech Stack

- PHP (classic multi-page app)
- MySQL / MariaDB
- HTML, CSS, JavaScript, jQuery, Bootstrap
- PHPMailer (email verification and password reset flows)

## Project Structure

- `index.php`: login page (entry point)
- `signup.php`, `signup_student.php`, `signup_sig.php`: registration flows
- `forgotpassword.php`, `backend/reset_pass.php`: password reset flow
- `admin/`: admin pages
- `signatory/`: signatory pages
- `student/`: student pages
- `backend/`: authentication, security, data handlers, SQL dump
- `config.php`: database and SMTP configuration
- `css/`, `js/`, `images/`, `fonts/`: static assets

## Prerequisites

- PHP 7.x or newer (project was originally developed around PHP 7.3)
- MySQL/MariaDB
- Apache/Nginx (or local stacks like XAMPP/LAMP/WAMP)

## Setup Instructions

1. Clone or copy the project into your web server root.
2. Create a MySQL database (or reuse the bundled one from SQL import).
3. Import the schema and seed data:
	 - File: `backend/scholarship_management_system.sql`
	 - Example command:
		 ```bash
		 mysql -u root -p < backend/scholarship_management_system.sql
		 ```
4. Update `config.php` with your local environment values:
	 - `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`
	 - SMTP settings (`SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, etc.)
5. Ensure PHP has required extensions enabled:
	 - `mysqli`
	 - `pdo_mysql`
6. Start your web server and open:
	 - `http://localhost/smss/index.php`

## Default Seed Accounts

The bundled SQL dump includes sample users. Example admin account in seed data:

- Email: `admin@gmail.com`
- Password: stored as a hash in DB (use password reset flow or create your own user if needed)

## Security Notes

- Rotate/remove any hardcoded credentials from `config.php` before deploying.
- Use environment-specific secrets for DB and SMTP credentials.
- Disable debug output in production.
- Serve the app over HTTPS in production.

## Common Troubleshooting

- Database connection error:
	- Verify DB credentials and database name in `config.php`.
	- Confirm MySQL service is running.
- Email not sending:
	- Verify SMTP host/port/security values.
	- If using Gmail, use an App Password and allow SMTP access.
- Blank page / 500 error:
	- Check web server and PHP error logs.
	- Confirm required PHP extensions are enabled.

## Notes for Development

- This project uses a traditional PHP page structure (not MVC).
- Business logic and presentation are mixed in multiple files; refactoring to controllers/services can improve maintainability.

## License

No explicit license file is currently included in this repository.
Add a `LICENSE` file if you plan to distribute or open-source the project.
