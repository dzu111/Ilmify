<?php
session_start();
session_destroy();
header("Location: /ilmify/auth/login.php");
exit;
