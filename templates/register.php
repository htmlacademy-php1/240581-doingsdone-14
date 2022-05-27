      <div class="content">
        <section class="content__side">
          <p class="content__side-info">Если у вас уже есть аккаунт, авторизуйтесь на сайте</p>

          <a class="button button--transparent content__side-button" href="auth.php">Войти</a>
        </section>

        <main class="content__main">
          <h2 class="content__main-heading">Регистрация аккаунта</h2>

          <form class="form" action="register.php" method="post" autocomplete="off">
            <div class="form__row">
              <label class="form__label" for="email">E-mail <sup>*</sup></label>
              <!--Вывод тега <p> с сообщением об ошибке заполнения поля 'email' и присвоение класса ошибки-->
              <?php if (isset($errors['email'])) : ?>
                <?php $email_class = "form__input--error"; ?>
                <p class="form__message">
                  <strong><?= $errors['email']; ?></strong>
                </p>
              <?php endif; ?>
              <input class="form__input <?= $email_class; ?>" type="text" name="email" id="email" value="<?= get_post_val('email'); ?>" placeholder="Введите e-mail">

            </div>

            <div class="form__row">
              <label class="form__label" for="password">Пароль <sup>*</sup></label>
              <!--Вывод тега <p> с сообщением об ошибке заполнения поля 'password' и присвоение класса ошибки-->
              <?php if (isset($errors['password'])) : ?>
                <?php $password_class = "form__input--error"; ?>
                <p class="form__message">
                  <strong><?= $errors['password']; ?></strong>
                </p>
              <?php endif; ?>
              <input class="form__input <?= $password_class; ?>" type="password" name="password" id="password" value="<?= get_post_val('password'); ?>" placeholder="Введите пароль">
            </div>

            <div class="form__row">
              <label class="form__label" for="name">Имя <sup>*</sup></label>
              <!--Вывод тега <p> с сообщением об ошибке заполнения поля 'name' и присвоение класса ошибки-->
              <?php if (isset($errors['name'])) : ?>
                <?php $name_class = "form__input--error"; ?>
                <p class="form__message">
                  <strong><?= $errors['name']; ?></strong>
                </p>
              <?php endif; ?>
              <input class="form__input <?= $name_class; ?>" type="text" name="name" id="name" value="<?= get_post_val('name'); ?>" placeholder="Введите имя">
            </div>

            <div class="form__row form__row--controls">
              <!--Вывод тега <p> с сообщением об ошибке заполнения формы-->
              <?php if (count($errors)) : ?>
                <p class="error-message"><?= $error_message; ?></p>
              <?php endif; ?>

              <input class="button" type="submit" name="" value="Зарегистрироваться">
            </div>
          </form>
        </main>
      </div>