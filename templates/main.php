<?= $content_project; ?>

<main class="content__main">
    <h2 class="content__main-heading">Список задач</h2>
    <!-- Полнотекстовый поиск по задачам -->
    <form class="search-form" action="index.php" method="get" autocomplete="off">
        <input class="search-form__input" type="text" name="search" value="<?= filter_input(INPUT_GET, 'search'); ?>" placeholder="Поиск по задачам">

        <input class="search-form__submit" type="submit" name="" value="Искать">
    </form>
    <!-- Фильтр списка задач -->
    <div class="tasks-controls">
        <nav class="tasks-switch">
            <a href="/index.php?id=<?= $project_id; ?>&filter=1&show_completed=<?= $show_complete_tasks; ?>" class="tasks-switch__item <?php if (1 === $filter || false === (bool)$filter) {
                                                                                                                                            echo 'tasks-switch__item--active';
                                                                                                                                        } ?>">Все задачи</a>
            <a href="/index.php?id=<?= $project_id; ?>&filter=2&show_completed=<?= $show_complete_tasks; ?>" class="tasks-switch__item <?php if (2 === $filter) {
                                                                                                                                            echo 'tasks-switch__item--active';
                                                                                                                                        } ?>">Повестка дня</a>
            <a href="/index.php?id=<?= $project_id; ?>&filter=3&show_completed=<?= $show_complete_tasks; ?>"" class=" tasks-switch__item <?php if (3 === $filter) {
                                                                                                                                                echo 'tasks-switch__item--active';
                                                                                                                                            } ?>">Завтра</a>
            <a href="/index.php?id=<?= $project_id; ?>&filter=4&show_completed=<?= $show_complete_tasks; ?>" class="tasks-switch__item <?php if (4 === $filter) {
                                                                                                                                            echo 'tasks-switch__item--active';
                                                                                                                                        } ?>">Просроченные</a>
        </nav>

        <label class="checkbox">
            <!--Добавление атрибута "checked", если переменная $show_complete_tasks равна единице-->
            <input class="checkbox__input visually-hidden show_completed" type="checkbox" <?php if (1 === $show_complete_tasks) echo 'checked'; ?>>
            <span class="checkbox__text">Показывать выполненные</span>
        </label>
    </div>
    <!-- Вывод списка задач -->
    <table class="tasks">
        <?php foreach ($tasks as $key => $task) : ?>
            <?php if ($task['task_status'] && 0 === $show_complete_tasks) continue; ?>
            <tr class="tasks__item task <?php if ($task['task_status']) echo 'task--completed'; ?> <?php if (!empty($task['task_deadline']) && strtotime($task['task_deadline']) < strtotime("now + 24 hours")) echo 'task--important'; ?>">
                <td class="task__select">
                    <label class="checkbox task__checkbox">
                        <input class="checkbox__input visually-hidden task__checkbox" type="checkbox" value="<?= $task['task_id']; ?>&id=<?= $project_id; ?>&filter=<?= $filter; ?>&show_completed=<?= $show_complete_tasks; ?>" <?php if ($task['task_status']) echo 'checked'; ?>>
                        <span class="checkbox__text"><?= htmlspecialchars($task['task_name']); ?></span>
                    </label>
                </td>
                <!-- Ссылка на файл, загруженный пользователем, если он был прикреплен задаче -->
                <td class="task__file">
                    <?php if (null !== $task['task_file']) : ?>
                        <a class="download-link" href="uploads/<?= $task['task_file'] ?>" target="_blank"><?= 'Файл' ?></a>
                    <?php endif ?>
                </td>

                <td class="task__date"><?= $task['task_deadline'] ?></td>
            </tr>
        <?php endforeach; ?>
        <!-- Вывод отрицательного результата полнотекстового поиска -->
        <?php if ($not_found) : ?>
            <tr class="tasks__item task">
                <td class="task__select">
                    <span class=""><?= $not_found; ?></span>
                </td>
            </tr>
        <?php endif ?>
    </table>
</main>