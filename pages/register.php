<?php
$errors = [];

if ( is_user_logged_in() ) {
	wp_redirect( site_url() . '/account' );
	exit;
}

function custom_login() {
	global $errors;
	if ( isset( $_POST['login_email'] ) && isset( $_POST['login_password'] ) ) {
		$credentials = [
			'user_login'    => $_POST['login_email'],
			'user_password' => $_POST['login_password'],
			'remember'      => true,
		];
		$user        = wp_signon( $credentials, false );

		if ( is_wp_error( $user ) ) {
			$errors[] = '<p>Ошибка авторизации: ' . $user->get_error_message() . '</p>';
		} else {
			wp_redirect( site_url() . '/account' );
			exit;
		}
	}
}

custom_login();
//add_action('init', 'custom_login');


function custom_register() {
	global $errors;
	if ( isset( $_POST['reg_email'] ) && isset( $_POST['reg_password'] ) ) {
		$email    = $_POST['reg_email'];
		$password = $_POST['reg_password'];

		if ( ! is_email( $email ) ) {
			$errors[] = '<p>Неверный формат email</p>';

			return;
		}

		if ( email_exists( $email ) ) {
			$errors[] = '<p>Пользователь с таким email уже существует</p>';

			return;
		}

		$user_id = wp_create_user( $email, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			$errors[] = '<p>Ошибка регистрации: ' . $user_id->get_error_message() . '</p>';
		} else {
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id );
			wp_redirect( site_url() . '/account' );
			exit;
		}
	}
}

custom_register();
//add_action('init', 'custom_register');
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="<?php echo PAYWAY_PLUGIN_URL; ?>/assets/css/style.css?ver=2">
    <title><?php echo get_bloginfo( 'title' ) . ' | Авторизация' ?></title>
</head>

<body>
<div class="wrapper register-page">
    <div class="register-header">
        <div class="container">
            <div class="register-header__wrap">
                <a href="<?php echo get_home_url(); ?>">
                    <img src="<?php echo PAYWAY_PLUGIN_URL; ?>/assets/imgs/title.png" alt=""/>
                </a>
            </div>
        </div>
    </div>
    <main class="main">
        <section class="register-form">
            <div class="container">
                <div class="form-wrap__toggle">
                    <button class="active">Войти</button>
                    <button>Регистрация</button>
                </div>
                <div class="form-wrap" style="width: 400px; margin: 20px auto;">
                    <div style="text-align: center">
						<?php
						if ( ! empty( $errors ) ) {
							foreach ( $errors as $error ) {
								echo $error;
							}
						}
						?>
                    </div>
                    <div class="auth-form active">
                        <h2>Авторизация</h2>
                        <form method="POST" action="">
                            <label>
                                <span>Электронная почта</span>
                                <input name="login_email" placeholder="Ваша почта" type="email" required/>
                            </label>
                            <label>
                                <span>Пароль</span>
                                <input name="login_password" placeholder="Ваш пароль" type="password" required/>
                            </label>
                            <button type="submit">Войти</button>
                        </form>
                    </div>
                    <div class="auth-form">
                        <div class="register-form__title">
                            <h2>Регистрация</h2>
                        </div>
                        <div class="register-form__form">
                            <form method="POST" action="">
                                <label>
                                    <span>Электронная почта</span>
                                    <input name="reg_email" placeholder="Ваша почта" type="email" required/>
                                </label>
                                <label>
                                    <span>Пароль</span>
                                    <input name="reg_password" placeholder="Ваш пароль" type="password" required/>
                                </label>
                                <label>
                                    <div class="register-check">
                                        <input type="checkbox" required/>
                                        <span>
                                                Я согласен с правилами обработки личной информации (<a href="#">Ознакомиться</a>)
                                            </span>
                                    </div>
                                </label>
                                <button type="submit">Регистрация</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="go-back-link">
            <a href="<?php echo get_home_url(); ?>">Вернуться на главную</a>
        </section>
    </main>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const toggleBtns = document.querySelectorAll(
                ".form-wrap__toggle button"
            );
            const formsRegister = document.querySelectorAll(".auth-form");

            if (toggleBtns.length != 0) {
                toggleBtns.forEach((item, index) => {
                    item.addEventListener("click", () => {
                        toggleBtns.forEach((btn) => {
                            btn.classList.remove("active");
                        });
                        item.classList.add("active");

                        formsRegister.forEach((form) => {
                            form.classList.remove("active");
                        });
                        formsRegister[index].classList.add("active");
                    });
                });
            }
        });
    </script>
</div>
</body>

</html>