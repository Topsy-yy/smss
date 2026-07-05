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

## Recommendation Engine (New IR Algorithm)

The scholarship matching system now uses an **Information Retrieval (IR)** pipeline instead of the old weighted-rule matcher.

### What Changed

- Previous approach: manual weighted scoring (financial need + category overlap).
- Current approach: TF-IDF vectorization + cosine similarity over normalized student/scholarship text.
- Runtime behavior: matching is now **IR-only** (legacy weighted fallback has been removed).

### End-to-End Matching Pipeline

1. Build a student profile document from structured fields (level, financial need, interests, department, region, etc.).
2. Build scholarship documents from scholarship fields (title, description, eligibility, benefits, category, funding, etc.).
3. Preprocess text:
	- lowercase
	- punctuation cleanup
	- stop-word removal
	- synonym normalization (for example: `AI` -> `artificial intelligence`)
	- protected phrase normalization (for example: `computer science` -> `computer_science`)
4. Build/refresh vocabulary from active approved scholarships.
5. Compute TF-IDF vectors for scholarships and students.
6. Compute cosine similarity between each student vector and scholarship vectors.
7. Rank by similarity score and return matches.

### Listing Threshold

- Student dashboard shows only scholarships with **match > 50%**.
- Stats counters are aligned with the same strict threshold (> 50%).

### Explainability

- The engine stores top contributing terms per match.
- "Why matched" reasons are used in SMS messages (not displayed on the student dashboard cards).

### Caching and Recompute Strategy

The engine uses database-backed caching and invalidation:

- Student vector/cache invalidates when student profile matching fields are updated.
- Scholarship corpus/vocabulary/vector cache invalidates when scholarships are created/updated/blocked/unblocked.
- Recommendations are cached with model/profile/corpus hashes and TTL.

This avoids recomputing all vectors on every page load.

### Database Objects Added

The SQL dump now includes IR support tables:

- `ir_engine_meta`
- `ir_stopwords`
- `ir_synonyms`
- `ir_vocabulary`
- `ir_scholarship_vectors`
- `ir_scholarship_norms`
- `ir_student_documents`
- `ir_student_vectors`
- `ir_recommendation_cache`

### Referential Integrity (IR Tables)

The SQL schema now includes foreign keys for IR relational tables to keep vectors/cache rows synchronized with parent records.

- `ir_recommendation_cache.student_id -> student.studentID`
- `ir_recommendation_cache.scholarship_id -> scholarship.scholarshipID`
- `ir_scholarship_norms.scholarship_id -> scholarship.scholarshipID`
- `ir_scholarship_vectors.scholarship_id -> scholarship.scholarshipID`
- `ir_scholarship_vectors.term_id -> ir_vocabulary.term_id`
- `ir_student_documents.student_id -> student.studentID`
- `ir_student_vectors.student_id -> student.studentID`
- `ir_student_vectors.term_id -> ir_vocabulary.term_id`

`sms_dispatch_log` intentionally has no foreign keys because it is an append-only operational log used for reporting.

Before applying these constraints on an existing live database, run orphan checks:

```sql
SELECT COUNT(*) AS orphan_rec_cache_students
FROM ir_recommendation_cache c
LEFT JOIN student s ON s.studentID = c.student_id
WHERE s.studentID IS NULL;

SELECT COUNT(*) AS orphan_rec_cache_scholarships
FROM ir_recommendation_cache c
LEFT JOIN scholarship sc ON sc.scholarshipID = c.scholarship_id
WHERE sc.scholarshipID IS NULL;

SELECT COUNT(*) AS orphan_sch_norms
FROM ir_scholarship_norms n
LEFT JOIN scholarship sc ON sc.scholarshipID = n.scholarship_id
WHERE sc.scholarshipID IS NULL;

SELECT COUNT(*) AS orphan_sch_vectors_sch
FROM ir_scholarship_vectors v
LEFT JOIN scholarship sc ON sc.scholarshipID = v.scholarship_id
WHERE sc.scholarshipID IS NULL;

SELECT COUNT(*) AS orphan_sch_vectors_terms
FROM ir_scholarship_vectors v
LEFT JOIN ir_vocabulary t ON t.term_id = v.term_id
WHERE t.term_id IS NULL;

SELECT COUNT(*) AS orphan_student_docs
FROM ir_student_documents d
LEFT JOIN student s ON s.studentID = d.student_id
WHERE s.studentID IS NULL;

SELECT COUNT(*) AS orphan_student_vectors_students
FROM ir_student_vectors v
LEFT JOIN student s ON s.studentID = v.student_id
WHERE s.studentID IS NULL;

SELECT COUNT(*) AS orphan_student_vectors_terms
FROM ir_student_vectors v
LEFT JOIN ir_vocabulary t ON t.term_id = v.term_id
WHERE t.term_id IS NULL;
```

For existing deployments, a one-time migration script is included at [backend/migrations/2026-07-05_add_ir_foreign_keys.sql](backend/migrations/2026-07-05_add_ir_foreign_keys.sql).

Example run:

```bash
mysql -u root -p sms < backend/migrations/2026-07-05_add_ir_foreign_keys.sql
```

### Main Integration Files

- `backend/IRRecommendationEngine.php`: core IR pipeline, indexing, vectorization, cosine scoring, cache lifecycle.
- `backend/MatchingEngine.php`: public matching facade (now IR-only).
- `student/tempUserHome.php`: student listing threshold filter (>50%).
- `backend/adminCreateOpportunity.php`: eligibility SMS for new scholarships.
- `backend/adminAddDelSch.php`: signatory scholarship create/edit hooks and eligibility SMS.
- `backend/sigSendSms.php`: signatory SMS templates, including "Re-send Matched Scholarships".
- `signatory/tempSigHome.php`: SMS template selector UI.

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
4. Create a local `.env` file (copy from `.env.example`) and set your environment values:
	 - `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`
	 - SMTP settings (`SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, etc.)
	 - `config.php` now reads these values from `.env`.
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

- Keep credentials in `.env` and out of source control.
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
