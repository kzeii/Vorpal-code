# Sun Son Solar customer and admin portal

Sun Son Solar is a CodeIgniter 4 solar storefront and operations portal. Homeowners can explore solar packages, estimate possible savings, request a tailored assessment, and track quote, installation, and support progress. Employees manage assigned operations work; admins manage solar leads, customer accounts, project delivery, service work, and employee access.

## Requirements

- PHP 8.2+ with `intl`, `mbstring`, and `mysqli` enabled
- MySQL or MariaDB
- Composer
- Apache/XAMPP or the CodeIgniter development server

## Database setup with phpMyAdmin

1. Start Apache and MySQL in XAMPP, then open phpMyAdmin.
2. Select **Import**, choose `database/sun_son_solar.sql`, and import it. The script uses `IF NOT EXISTS` and seeds the solar packages with `INSERT IGNORE`, so it can add the newer portal tables to an existing database without replacing existing users.
3. Copy `env` to `.env` (on Windows, `Copy-Item env .env`). The sample file is configured for a default local XAMPP setup. Set `app.baseURL` if your project folder has a different URL.
4. In `.env`, confirm these database values match your MySQL account:

   ```ini
   database.default.hostname = localhost
   database.default.database = sun_son_solar
   database.default.username = root
   database.default.password =
   database.default.DBDriver = MySQLi
   database.default.port = 3306
   ```

5. Install dependencies with `composer install`.
6. Create your initial admin with `php spark admin:create`.
7. Run `php spark serve --host 127.0.0.1 --port 8080` and open `http://localhost:8080`.

## Optional presentation demo data

To show populated customer, staff, and admin workspaces for a class presentation, import `database/demo_data.sql` after the main schema. It adds a clearly labeled sample customer, employee, quote, installation, and support request without replacing existing accounts.

- Demo customer username: `mia.santos`
- Demo customer password: `SolarDemo2026!`
- Demo employee username: `solar.tech`
- Demo employee temporary password: `SolarStaff2026!` (the employee must change it at first sign-in)

Use the admin account from `php spark admin:create` to view the operations dashboard and provision employee accounts. Employee accounts are admin-created and require a password change at first sign-in; only customers can self-register. These are local demonstration records; do not reuse the demo passwords for real accounts.

phpMyAdmin is the separate database-management web app supplied with packages such as XAMPP. This project provides the SQL file for it; it does not include a phpMyAdmin copy.

## Create the first admin

Run `php spark admin:create` once to create the first administrator. The command refuses to create another admin once one exists. Use the site's sign-in page to log in; admins are sent to `/admin`, customers to `/customer`.

## Customer and staff experience

- Solar storefront with three indicative home-system packages and a monthly bill savings estimator.
- Customer sign-up and sign-in with unique email/username validation; passwords are stored with `password_hash()`.
- Free assessment/quote requests with monthly bill, roof type, and home notes.
- Customer quote journey, installation milestones, progress, support requests, and admin replies.
- Admin sales pipeline for new enquiries through proposal and won/lost outcomes.
- Marking a quote **Won** automatically opens an installation tracker. Admins can schedule site visits, assign a package, and update customer-visible progress.
- Admin-only employee account creation, activation/deactivation, and first-login password reset enforcement.
- Staff operations dashboard for quote pipeline, installations, service queue, customer list, and packages; employees have read-only customer access while admins manage account access.
- Search and status filters for quote, project, and service queues; customer account search.
- Admin activity log and customer-facing recent updates for quote, project, and support changes.
- Interactive monthly bill savings estimator with an illustrative estimate, already available on the public storefront.
- Staff-only electricity bill calculator using billed kWh, the customer’s per-kWh rate, additional charges, and credits; the estimate is not saved to customer records.
- Customer account search and access controls, field work queue, and service/support queue.
- Forms include CodeIgniter CSRF tokens and server-side validation.

The public homepage describes the company, solar benefits, consultation/installation/maintenance, an illustrative demo journey, FAQs, account links, and terms/privacy pages. Replace classroom example content with verified business contact details and reviewed legal text before presenting it as a real commercial service.

Package prices and savings are illustrative planning figures. Confirm system design, warranties, utility interconnection, and final price during a site assessment and proposal.
