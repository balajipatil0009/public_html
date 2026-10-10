<?php
session_start();
session_destroy();
header('Location: ../../intern-login.php');
exit();
?>