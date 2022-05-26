<section class="content__side">
    <h2 class="content__side-heading">Проекты</h2>
    <!-- Блок навигации по списку проектов пользователя -->
    <nav class="main-navigation">
        <ul class="main-navigation__list">
            <?php foreach ($projects as $project) : $project_name = $project['project_name']; ?>
                <li class="main-navigation__list-item <?php if ((int)$project['project_id'] === $project_id) {
                                                            echo 'main-navigation__list-item--active';
                                                        } ?>">
                    <a class="main-navigation__list-item-link" href="/index.php?id=<?= $project['project_id']; ?>&filter=<?= $filter; ?>&show_completed=<?= $show_complete_tasks; ?>"><?= htmlspecialchars($project_name); ?></a>
                    <span class="main-navigation__list-item-count"><?= $project['count_tasks']; ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <a class="button button--transparent button--plus content__side-button" href="add_project.php" target="project_add">Добавить проект</a>
</section>