<?php
session_start(); // Начинаем сессию

// Удаляем все данные сессии
session_unset(); 

// Уничтожаем саму сессию
session_destroy(); 

// Перенаправляем пользователя на главную страницу или страницу входа
header("Location: ../Index/Php_files/Index.php"); 
exit; // Выход из скрипта
?>
