<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
logout_admin();
redirect('admin/login.php');
