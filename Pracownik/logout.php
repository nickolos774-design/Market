<?php
session_start();

// Разрушаем все данные сессии
session_unset();
session_destroy();

// Перенаправляем на страницу логина
header("Location: ../Index/Php_files/Index.php");
exit();
?>
