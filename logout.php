<?php
// Сценарий выхода из сессии

session_start();

// Выход из сессии и перенаправление пользователя на главную (гостевую) страницу
unset($_SESSION['user_id'], $_SESSION['user_name']);

header("Location: index.php");