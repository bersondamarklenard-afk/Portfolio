<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

try {
    $portfolioPublic = is_portfolio_public();
} catch (Throwable $e) {
    $portfolioPublic = true;
}

if (!$portfolioPublic) {
    http_response_code(503);
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php#contact');
}

verify_csrf();

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) > 120) {
    $errors[] = 'Please enter a valid name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if ($message === '' || mb_strlen($message) > 5000) {
    $errors[] = 'Please enter a message.';
}
if (mb_strlen($subject) > 200) {
    $errors[] = 'Subject is too long.';
}

if ($errors) {
    $_SESSION['contact_flash'] = ['type' => 'error', 'message' => implode(' ', $errors)];
    redirect('index.php#contact');
}

try {
    $stmt = db()->prepare(
        'INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $subject ?: null, $message]);
    $_SESSION['contact_flash'] = [
        'type' => 'success',
        'message' => 'Thank you! Your message has been sent. I will get back to you soon.',
    ];
} catch (Throwable $e) {
    $_SESSION['contact_flash'] = [
        'type' => 'error',
        'message' => 'Sorry, something went wrong. Please try emailing me directly.',
    ];
}

redirect('index.php#contact');
