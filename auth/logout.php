<?php
session_start();
session_destroy();
header("Location: /tinytale/auth/login.php");
exit;
