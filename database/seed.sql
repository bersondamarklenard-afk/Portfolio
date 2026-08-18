-- Optional seed data for a fresh database (after schema.sql)
-- Prefer database/migrate.php when upgrading an existing portfolio_db

USE portfolio_db;

INSERT INTO admins (username, email, password_hash)
SELECT 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');
-- Note: the hash above is a placeholder example for "password".
-- Always create the admin via migrate.php so the hash matches admin123.

INSERT INTO personal_information (
  full_name, professional_title, tagline, about_me, what_i_do, career_objective,
  interests, strengths, email, phone, location, years_experience,
  availability_status, meta_title, meta_description, footer_text
)
SELECT
  'Mark Lenard G. Bersonda',
  'IT Graduate · Web Developer',
  'Building practical web solutions with PHP, MySQL, and modern front-end tools.',
  'Passionate IT graduate with hands-on experience in web development. Specialized in PHP, MySQL, and JavaScript.',
  'IT Graduate\nWeb Developer',
  'Seeking an entry-level web developer or IT role where I can contribute to real projects and grow with a team.',
  'Web development, UI design, system analysis, continuous learning',
  'Problem-solving, attention to detail, collaboration, fast learner',
  '',
  '',
  'Philippines',
  '1+ year',
  'Open to opportunities',
  'Mark Lenard Bersonda · IT Graduate & Web Developer',
  'Portfolio of Mark Lenard G. Bersonda — IT graduate specializing in PHP, MySQL, and web development.',
  'IT Graduate · Web Developer Portfolio'
WHERE NOT EXISTS (SELECT 1 FROM personal_information LIMIT 1);
