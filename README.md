# Professional Portfolio CMS

A modern, employer-focused one-page portfolio website with a secure admin panel for managing content without editing source code.

Built with **PHP**, **MySQL**, **HTML/CSS**, and vanilla **JavaScript**. Designed for local XAMPP development and deployment to PHP/MySQL hosts such as **Hostinger**.

> **Important:** This project cannot run on GitHub Pages. GitHub Pages only serves static files and does not execute PHP or provide MySQL. Use GitHub for source control; deploy the app to PHP/MySQL hosting.

## Features

- One-page public portfolio (Hero, About, Skills, Experience, Projects, Education, Certifications, Contact)
- Admin CMS with dashboard and full CRUD for all content types
- Session-based authentication with password hashing and optional Google OAuth sign-in
- Contact form with message inbox
- Image/resume uploads with validation
- Responsive design (desktop, tablet, mobile)
- SEO meta tags and Open Graph basics
- CSRF protection, prepared statements, output escaping

## Technologies

- PHP 8+ (PDO)
- MySQL / MariaDB
- HTML5, CSS3, vanilla JS
- Font Awesome icons
- Google Fonts (Sora, IBM Plex Sans)

## Folder structure

```
portfolio/
├── admin/                 # Admin panel
│   ├── includes/          # Layout partials
│   ├── index.php          # Dashboard
│   ├── login.php
│   ├── google-login.php   # Starts Google OAuth
│   ├── google-callback.php
│   ├── logout.php
│   ├── personal.php
│   ├── skills.php
│   ├── experience.php
│   ├── projects.php
│   ├── education.php
│   ├── certifications.php
│   ├── social.php
│   ├── messages.php
│   └── settings.php
├── assets/
│   ├── css/
│   ├── js/
│   ├── icons/
│   ├── images/
│   └── uploads/           # User uploads (not committed)
├── config/
│   ├── config.example.php
│   ├── config.php         # Local/production secrets (gitignored)
│   └── database.php
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── migrate.php        # One-time upgrade from legacy DB
├── includes/
│   ├── auth.php
│   ├── google-oauth.php
│   └── functions.php
├── contact.php
├── index.php              # Public portfolio
├── .gitignore
└── README.md
```

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache (XAMPP, Hostinger, etc.) with `mod_rewrite` optional

## Local setup (XAMPP)

1. Place the project in `C:\xampp\htdocs\portfolio` (or your web root).
2. Start **Apache** and **MySQL** in XAMPP.
3. Copy configuration:

   ```bash
   copy config\config.example.php config\config.php
   ```

4. Edit `config/config.php` if needed (default XAMPP: user `root`, empty password, database `portfolio_db`).
5. Run the migrator once (creates tables and preserves existing data if upgrading):

   - Browser: [http://localhost/portfolio/database/migrate.php](http://localhost/portfolio/database/migrate.php)
   - Or CLI: `php database/migrate.php`

6. Open the site:

   - Portfolio: [http://localhost/portfolio/](http://localhost/portfolio/)
   - Admin: [http://localhost/portfolio/admin/login.php](http://localhost/portfolio/admin/login.php)

### Default admin (fresh install)

- Username: `admin`
- Password: `admin123`

**Change this password immediately** under Admin → Settings.

If you already had an admin account in the old app, it is migrated automatically.

## Database setup (manual)

Alternatively, import SQL files via phpMyAdmin:

1. Import `database/schema.sql`
2. Import `database/seed.sql` (optional sample content)
3. Or prefer `database/migrate.php`, which also migrates legacy `site_settings` / `projects` data

## Admin usage

After login you can manage:

| Section | What you can do |
|--------|------------------|
| Personal Info | Name, title, about, photo, resume, contact, SEO |
| Skills | Add / edit / delete, category, proficiency, icons |
| Experience | Roles, dates, responsibilities, technologies |
| Projects | Descriptions, images, GitHub/live URLs, featured flag |
| Education | Degrees and institutions |
| Certifications | Awards, training, certificate images |
| Social Links | LinkedIn, GitHub, etc. |
| Messages | Contact form inbox |
| Settings | Username, email, password |

The public page updates as soon as you save.

## Google sign-in (Admin)

The login page supports **Continue with Google** using Google OAuth 2.0 / OpenID Connect. Only the Google account listed in config can enter Admin. Username/password login still works.

This project has no Composer dependencies. The flow talks to Google’s official authorization and token endpoints from PHP (authorization code + PKCE, then server-side ID token verification). The Client Secret never appears in HTML or JavaScript.

1. Open [Google Cloud Console](https://console.cloud.google.com/) and create (or select) a project.
2. APIs & Services → **OAuth consent screen** (External or Internal). Add your Google account as a test user while the app is in testing.
3. APIs & Services → **Credentials** → Create credentials → **OAuth client ID** → Application type **Web application**.
4. Add Authorized JavaScript origins:
   - Local: `http://localhost`
   - Live: `https://yourdomain.com`
5. Add Authorized redirect URIs:
   - Local: `http://localhost/portfolio/admin/google-callback.php`
   - Live: `https://yourdomain.com/admin/google-callback.php`  
     (If the site is in a subfolder, keep that path, matching `app.url`.)
6. Copy the Client ID and Client Secret into `config/config.php`:

```php
'google' => [
    'client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'allowed_email' => 'you@gmail.com',
    'redirect_uri' => '', // empty = app.url + /admin/google-callback.php
],
```

7. Set `allowed_email` to the **only** Google account that may access Admin. Unauthorized accounts see **Access Denied** and are not signed in.
8. Keep `config/config.php` gitignored. `config/.htaccess` blocks HTTP access to that folder.

On production, set `app.url` to your HTTPS domain so the callback URL updates automatically (or set `google.redirect_uri` explicitly).

## Deployment (Hostinger / shared hosting)

1. Create a MySQL database in your hosting panel.
2. Upload project files (FTP / File Manager / Git deploy).
3. Copy `config.example.php` → `config.php` and set:

   - Database host, name, user, password
   - `app.url` to your live domain (e.g. `https://yourdomain.com`)
   - `app.debug` → `false`
   - Google OAuth Client ID / Secret / allowed email (if using Continue with Google)
   - Add the live callback URL in Google Cloud: `{app.url}/admin/google-callback.php`

4. Import `schema.sql` (or upload and run `migrate.php` once, then delete it).
5. Ensure `assets/uploads/` is writable (`0755` or `0775`).
6. Log in at `https://yourdomain.com/admin/login.php` and update content.
7. Delete or protect `database/migrate.php` after setup.

## GitHub

```bash
git init
git add .
git commit -m "Initial professional portfolio CMS"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

Do **not** commit `config/config.php` or uploaded private files. The `.gitignore` already excludes them.

### Why not GitHub Pages?

| Need | GitHub Pages | This project |
|------|--------------|--------------|
| Static HTML/CSS/JS | Yes | Also used |
| PHP execution | No | Required |
| MySQL | No | Required |
| Admin login / sessions | No | Required |
| Contact form backend | No | Required |

Host the repository on GitHub; host the live site on PHP/MySQL hosting.

## Security notes

- Passwords stored with `password_hash()` / `password_verify()`
- PDO prepared statements for SQL
- CSRF tokens on forms
- Session regeneration on login
- Upload MIME/extension validation
- Config directory blocked via `.htaccess`
- Escape all public output with `htmlspecialchars`

## License

Personal portfolio project — customize and use freely for your own career materials.
