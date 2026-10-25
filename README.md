# Hungry Man's Restaurant Website

Hungry Man's is a PHP and MySQL restaurant website for browsing a menu, placing orders, requesting table reservations, and contacting the restaurant. It also includes a staff area for managing restaurant activity.

This repository contains the website source code and SQL database schemas. It does not include a payment gateway, email delivery service, or a third-party AI chatbot service.

## Contents

- [Features](#features)
- [Technology](#technology)
- [Requirements](#requirements)
- [Run locally with XAMPP](#run-locally-with-xampp)
- [Database setup](#database-setup)
- [Configure the database connection](#configure-the-database-connection)
- [Create an administrator account](#create-an-administrator-account)
- [Project structure](#project-structure)
- [Important workflows and limitations](#important-workflows-and-limitations)
- [Deployment and security notes](#deployment-and-security-notes)
- [Troubleshooting](#troubleshooting)

## Features

### Customer-facing website

- Browse the restaurant homepage, restaurant information, and menu.
- Create an account, sign in, and sign out.
- Place an order from available menu items and view recent orders.
- Submit a table reservation request while signed in.
- Send a message through the contact form.
- Use the built-in chatbot for common questions about the restaurant.
- Use the responsive layout on desktop and mobile-sized screens.

### Staff area

Staff can sign in separately from customers and use the admin pages to:

- Review dashboard activity and notifications.
- Add, edit, and manage menu items.
- Review customer orders and update order and transfer statuses.
- Review and manage reservations.
- View customer accounts and block or unblock customers.
- Read contact form messages.
- Update restaurant settings.

## Technology

- **Server-side:** PHP
- **Database:** MySQL or a compatible MariaDB server
- **Database access:** PHP MySQLi extension
- **Client-side:** HTML, CSS, and vanilla JavaScript
- **Local web server:** Apache (for example, the one included with XAMPP)

The project does not use Composer or npm packages. There is no automated test or build command configured in the repository.

## Requirements

For local development, install:

1. XAMPP (Apache, PHP, and MySQL/MariaDB).
2. A modern web browser.
3. A code editor, if you plan to change the source.

The PHP installation must have MySQLi enabled. The database schema uses InnoDB and `utf8mb4`.

## Run locally with XAMPP

1. Copy or clone this project into XAMPP's web root:

   ```text
   C:\xampp\htdocs\hungrymans
   ```

2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.

3. Create the database and import the schema as described in [Database setup](#database-setup).

4. Check the database values in [`connection.php`](./connection.php). The defaults are intended for a typical local XAMPP setup; update them if your MySQL username, password, host, or database name differs.

5. Open the site:

   ```text
   http://localhost/hungrymans/
   ```

The database connection file also attempts to create the configured database and application tables automatically. For predictable setup, import the schema manually first. Automatic creation requires the MySQL account to have permission to create databases and tables.

## Database setup

Two schema files are included:

- [`Hunger_schema_new.sql`](./Hunger_schema_new.sql) — recommended for a fresh installation; includes the current tables and starter restaurant settings and menu data.
- [`Hunger_schema.sql`](./Hunger_schema.sql) — an older schema snapshot retained for reference.

Import **only `Hunger_schema_new.sql`** for a new installation:

1. Visit phpMyAdmin at `http://localhost/phpmyadmin`.
2. Create a database named `hungrymans` (or choose another name and use it in `connection.php`).
3. Select the database, choose **Import**, and select `Hunger_schema_new.sql`.
4. Run the import and confirm that the tables were created.

The schema creates these tables:

| Table | Purpose |
| --- | --- |
| `users` | Customer accounts |
| `admin_users` | Staff/admin accounts |
| `menu_items` | Dishes shown on the menu |
| `orders` | Order summary, status, and payment status |
| `order_items` | Individual items belonging to an order |
| `reservations` | Customer reservation requests |
| `contact_messages` | Contact form submissions |
| `restaurant_settings` | Restaurant details used by the site |
| `admin_notifications` | Staff-facing notifications |
| `chatbot_conversations` | Conversation storage table in the schema |

The current chatbot response handler uses predefined rules and responses and does not write its conversations to the database.

## Configure the database connection

Open [`connection.php`](./connection.php) and set the MySQL connection values near the top of the file:

```php
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'hungrymans';
```

Use the host, username, password, and database name supplied by your database provider when deploying. Never publish real production credentials. The current project keeps these values directly in `connection.php`, so protect that file and do not commit production secrets. The root [`.gitignore`](./.gitignore) excludes common environment files, but does not remove credentials from tracked source files.

## Create an administrator account

The SQL schema does not create a default administrator account. Create one explicitly using a PHP-generated password hash:

1. From a trusted local PHP environment, generate a hash (replace the example password with a unique password):

   ```bash
   php -r "echo password_hash('Choose-A-Strong-Password', PASSWORD_DEFAULT), PHP_EOL;"
   ```

2. In phpMyAdmin, select the project database and run this query, replacing the name, email, and placeholder hash with your own values:

   ```sql
   INSERT INTO admin_users (first_name, last_name, email, password)
   VALUES ('Site', 'Administrator', 'admin@example.com', 'PASTE_THE_GENERATED_HASH_HERE');
   ```

3. Sign in at `http://localhost/hungrymans/admin-login.php`.

There is no default password. Store administrator credentials securely and do not use the example email in a live installation.

## Project structure

```text
hungrymans/
├── index.php                  # Homepage
├── about.php                  # Restaurant information
├── menu.php                   # Menu listing
├── reservations.php           # Reservation form and customer requests
├── contact-page.php           # Contact page and form
├── contact-submit.php         # Contact form handler
├── auth.php                   # Customer sign-up, login, and logout handler
├── customer-orders.php        # Customer order history endpoint
├── place-order.php            # Order creation and transfer-submission handler
├── connection.php             # MySQL connection and table initialization
├── session-config.php         # PHP session and CSRF-token initialization
├── admin-login.php            # Staff login
├── admin-logout.php           # Staff logout
├── admin-dashboard.php        # Staff dashboard and customer controls
├── admin-layout.php           # Shared admin layout
├── admin-menu.php             # Menu management
├── admin-orders.php           # Order and transfer management
├── admin-reservations.php     # Reservation management
├── admin-customers.php        # Customer management
├── admin-messages.php         # Contact message management
├── admin-notifications.php    # Notifications
├── admin-settings.php         # Restaurant settings
├── chatbot.php                # Chatbot widget markup
├── chatbot.html               # Standalone chatbot page
├── chatbot-final.html         # Alternate chatbot page
├── chatbot.js                 # Chatbot client-side behavior
├── chatbot-reply.php          # Rule-based chatbot response endpoint
├── chatbot.css                # Chatbot styles
├── orders.js                  # Cart and order-related browser behavior
├── script.js                  # General browser interactions
├── style.css                  # Main site styles
├── images/                    # Image assets
├── logo.png                   # Restaurant logo asset
├── Hunger_schema_new.sql      # Recommended database schema
├── Hunger_schema.sql          # Older schema snapshot
├── Database backup/           # Local database backups; ignored by Git
├── COMPLETION_STATUS.md       # Project completion notes
└── README.md                  # Project documentation
```

Admin PHP files live in the project root; there is no `admin/` subdirectory.

## Important workflows and limitations

- **Orders:** Customers must be signed in to submit an order. The server checks the menu items and prices and saves the order and its line items. Online payment is not integrated: the current workflow records a customer-reported bank transfer for staff to review.
- **Reservations:** Customers must be signed in. Submitting the form creates a request; staff manage its status in the admin area.
- **Chatbot:** `chatbot-reply.php` uses keyword-based, predefined answers. It is not an AI service, and the response handler currently does not save chat messages.
- **Images:** Menu image URLs are stored in the database; the default seed menu uses remote image URLs. A working internet connection may be required to load remote images.
- **Database snapshots:** Keep schema files in version control when they are part of the project. The `Database backup/` directory is treated as local data and ignored.
- **Local cookie files:** `cookies.txt` and `admincookies.txt` may contain active PHP session identifiers. Do not share or commit them.

## Deployment and security notes

For a PHP/MySQL hosting provider:

1. Create a database and import `Hunger_schema_new.sql`.
2. Configure `connection.php` with the provider's database details.
3. Upload the application files to the web directory for the site.
4. Create an administrator account using the steps above.
5. Test sign-in, menu browsing, order placement, reservations, contact submissions, and staff workflows on the deployed site.

Before making the site public:

- Use unique, strong passwords for database and staff accounts.
- Do not commit database credentials, `.env` files, session cookies, logs, or database backups.
- Keep database backups outside the public web directory and restrict access to them.
- Configure HTTPS on the hosting provider.
- Review the host's PHP version, MySQLi support, database permissions, and error-log settings.
- Avoid displaying detailed PHP or database errors to visitors in production.

The included [`.gitignore`](./.gitignore) prevents common local-only and sensitive files from being newly added to Git. If a file was already committed, adding it to `.gitignore` does not remove it from the repository history.

## Troubleshooting

### The site reports a database connection error

- Confirm that MySQL is running in XAMPP.
- Check the host, username, password, and database name in `connection.php`.
- Confirm that the database exists and that `Hunger_schema_new.sql` has been imported.
- Check that PHP's MySQLi extension is enabled.
- On hosted environments, confirm that the database user has access to the selected database.

### A menu image does not load

- Check that the image URL saved for the menu item is valid and reachable.
- For a local image, use a path that exists in the deployed project.
- If the image is remote, check the server or browser's internet access.

### Customer orders or reservations are unavailable

- Customer accounts must be signed in before ordering or requesting a reservation.
- Confirm that the customer account is active and that the relevant database tables exist.
- Check the PHP and MySQL error logs for server-side errors.

### Staff cannot sign in

- Confirm an administrator row exists in `admin_users`.
- Ensure the `password` value is a hash generated by PHP's `password_hash`, not a plain-text password.
- Use the staff login page at `/admin-login.php`.
