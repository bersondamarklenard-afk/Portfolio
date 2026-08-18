<?php
/**
 * Legacy redirect — old admin.php → new admin dashboard
 */
header('Location: admin/index.php', true, 301);
exit;
