<?php
// Сценарий главной страницы

require_once('init.php');

//Подключение лейаута для анонимного посетителя (гостевая страница)
if (!$user['user_id']) {
    $guest_content = include_template('guest.php');

    $layout_content = include_template('layout.php', [
        'content' => $guest_content,
        'title' => $title,
        'user' => $user,
        'year' => $year
    ]);

    print($layout_content);
    exit;
}

// Получение параметров чекбокса задачи и её id из GET-запроса 
$tasks_check = (int)filter_input(INPUT_GET, 'check', FILTER_SANITIZE_NUMBER_INT);
$task_id = (int)filter_input(INPUT_GET, 'task_id', FILTER_SANITIZE_NUMBER_INT);

$tasks_status = [
    'tasks_status' => $tasks_check,
    'user_id' => $user['user_id'],
    'task_id' => $task_id
];

// SQL-запрос на инверование статуса задачи
$sql = "UPDATE tasks SET task_status = ? WHERE user_id = ? AND task_id = ?";

$stmt = get_prepare_stmt($link, $sql, $tasks_status);

if (false === $stmt) {
    output_error_sql($link, $error_template_data);
}

$result = mysqli_stmt_execute($stmt);

if (false === $result) {
    output_error_sql($link, $error_template_data);
}

mysqli_stmt_get_result($stmt);

// Запрос в БД списка проектов пользователя и количества задач в каждом из них
$projects = get_user_projects($link, $user['user_id'], $error_template_data);

//Получение параметров чекбокса "Показывать выполненные" из GET-запроса
$show_complete_tasks = (int)filter_input(INPUT_GET, 'show_completed', FILTER_SANITIZE_NUMBER_INT);

// Получение id проекта из GET-запроса для фильтрации задач по проектам
$project_id  = (int)filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Получение состояния блока фильтра задач из GET-запроса для фильтрации задач в блоке фильтров
$filter = (int)filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_NUMBER_INT);

// Запрос к БД на получение списка ВСЕХ задач пользователя в зависимости от состояния блока фильтров задач
$sql_filter_add = preparation_insert_filtration($filter);

$sql = compose_sql_user_tasks($sql_filter_add);

$sql_data = [$user['user_id']];

$sql_result = get_result_prepare_sql($link, $sql, $sql_data);

// Проверка полученного id проекта из GET-запроса на существование у пользователя и вывод ошибки 404 при несуществующем id проекта
if ($project_id && false === validate_project_id($link, $user, $project_id)) {
    $content = include_template('404.php');

    $layoutContent = include_template('layout.php', [
        'title' => $title,
        'content' => $content,
        'user' => $user,
        'year' => $year
    ]);

    http_response_code(404);

    print($layoutContent);
    exit();
}

// Запрос к БД на получение списка задач в ВЫБРАННОМ проекте в зависимости от состояния блока фильтров задач
if ($project_id) {

    $sql = compose_sql_user_tasks($sql_filter_add, $project_id);

    $sql_data = [$user['user_id'], $project_id];

    $sql_result = get_result_prepare_sql($link, $sql, $sql_data);
}

$tasks = mysqli_fetch_all($sql_result, MYSQLI_ASSOC);

// Получение строки из поискового запроса пользователя
$search = $_GET['search'] ?? '';

//Фильтрация, в том числе и символов "*, (, )" из строки запроса
$search = preg_replace('/[^\p{L}\p{N}\s]/u', '', trim($search));

$not_found = false;

// Полнотекстовый поиск по задачам пользователя
if ($search) {
    $search_request = $search . '*';

    $sql = compose_sql_user_tasks($sql_filter_add, $project_id, $search);

    $sql_data = [$user['user_id'], $search_request];

    $sql_result = get_result_prepare_sql($link, $sql, $sql_data);

    $tasks = mysqli_fetch_all($sql_result, MYSQLI_ASSOC);

    if (!$tasks) {
        $not_found = 'Ничего не найдено по вашему запросу';
    }
}

// Подключение шаблонов страниц
$content_project = include_template('project_side.php', [
    'projects' => $projects,
    'project_id' => $project_id,
    'show_complete_tasks' => $show_complete_tasks,
    'filter' => $filter
]);

$main_content = include_template('main.php', [
    'content_project' => $content_project,
    'tasks' => $tasks,
    'show_complete_tasks' => $show_complete_tasks,
    'project_id' => $project_id,
    'not_found' => $not_found,
    'filter' => $filter
]);

$layout_content = include_template('layout.php', [
    'title' => $title,
    'content' => $main_content,
    'user' => $user,
    'year' => $year
]);

print($layout_content);
