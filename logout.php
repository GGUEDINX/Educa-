<?php
// logout.php
require_once __DIR__ . '/app/autoload.php';

use App\Controllers\AuthController;
use App\Core\Security;

Security::iniciarSessaoSegura();

(new AuthController())->logout();
