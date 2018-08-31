<?php
// Level 4
require_once "predis/autoload.php";
require_once "functions.php";
try {
	// Для локального Redis
	$client = new Predis\Client();

	// Инициализируем счетчик id для пользователей
	if (!$client->get('user_id')) {
		$client->set('user_id', 0);
	}
	// Массив для всавки в Redis
	$user = [
		'name' => 'Mike',
		'email' => 'mike@smith.com',
		'password' => md5('password'),
	];
	// Создаем нового пользователя
	$user_id = create_user($user);
	if ($user_id) {
		echo "Пользователь успешно создан<br/>";
	} else {
		echo "Не удалось создать пользователя<br>";
	}

	// Проверяем есть ли такой пользователь в Redis и если есть и пароль совпадает возращаем id
	$get_id_user = authorize_user($user['email'], $user['password']);
	if ($get_id_user) {
		echo 'Пользователь с id: ' . $get_id_user . 'авторизован<br>';
	} else {
		echo 'Нет такого пользователя или пароль не совпадает<br>';
	}
} catch (Exception $e) {
	die($e->getMessage());
}
