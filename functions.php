<?php
/**
 * Генерируем массив строк
 * @param  array  $words
 * @param  int|integer $count
 * @return object
 */
function render_strngs(array $words, int $count = 1000) {

	$cnt_array = count($words);

	// Выдиляем фиксируемую память для массива
	$generate_array = new SplFixedArray($count);
	// Индекс последнего слова в массиве
	$end_word = $cnt_array - 1;

	for ($j = 0; $j < $count; $j++) {
		// Формируем строку
		$str = '';

		for ($i = 0; $i < $cnt_array; $i++) {
			// Если это последний элемент массива то пробел не дописываем
			if ($i === $end_word) {
				$str .= $words[rand(0, $cnt_array - 1)];
			} else {
				$str .= $words[rand(0, $cnt_array - 1)] . ' ';
			}
		}
		$generate_array[$j] = $str;
	}
	return $generate_array;

}

/**
 * Возращает уникальные строки в массиве
 * @param  SPLFixedArray $strings
 * @return object
 */
function get_uniques(SPLFixedArray $strings) {
	// Воспользуемся тем что свойства у нас уникальные
	$obj = new stdClass();
	foreach ($strings as $key => $value) {
		$obj->$value = true;
	}
	return $obj;
}

/**
 * Сохранение уникальних строк в БД
 * @param  stdClass $obj_strings
 * @return void
 */
function save_strings_db(stdClass $obj_strings) {

	// Всего получено строк для вставки
	$count_all_strings = count((array) $obj_strings);
	// Сколько уникальных строк будет всавлено;
	$cnt_insert = 0;
	// Флаг для проверки есть ли вообще строки для вставки
	$flag = false;

	try {
		$dbh = new PDO(DSN, USER_DB, USER_PASSWORD);
		// Формируем строку для всавки одним махом
		$query = 'INSERT INTO unique_string (u_string) VALUES ';
		$t = microtime(true);
		foreach ($obj_strings as $key => $value) {
			// Если в БД такой строки нет добавляем её к колбасе
			if (!check_string_db($key, $dbh)) {
				$flag = true;
				$query .= "('$key'),";
				$cnt_insert++;
			}
		}
		// Обрезаем последнюю ,
		$query = substr($query, 0, -1);
		// Если есть что добавлять добавляем одним махом для уменьшения запросов к БД
		if ($flag) {
			$res = $dbh->exec($query);
			if ($res === false) {
				throw new PDOException("Произошла ошибка при вставке в БД");
			}
		}
		echo "Всего строк для добавления - $count_all_strings, было добавленено - $cnt_insert T = " . (microtime(true) - $t) . "\n";

	} catch (PDOException $e) {

		echo $e->getMessage();
	}
}
/**
 * Проверка в БД на уникальность строки
 * @param  string $str Проверяемая строка
 * @param  object $db  экземпляр PDO
 * @return int
 */
function check_string_db(string $str, $db) {
	$sql = "SELECT check_string('$str') as result";
	// Выполняем хранимую функцию
	$stmt = $db->query($sql);
	$response = $stmt->fetch(PDO::FETCH_ASSOC);
	return $response['result'];
}

/**
 * Creates new user
 * @param  array  $user_data (name, email, password_hash)
 * @return string  Returns ID of created user
 */
function create_user(array $user_data) {
	global $client;
	if (!is_array($user_data)) {
		return false;
	}
	// Делаем примитивную защиту от всяких зловредных хацкеров
	$user_name = trim(strip_tags($user_data['name']));
	$user_email = trim(strip_tags($user_data['email']));
	$user_password = trim($user_data['password_hash']);
	// Если есть что добавлять добавляем в БД
	if (!empty($user_data) && !empty($user_email) && !empty($user_password)) {

		$client->incr('user_id');
		$user_id = $client->get('user_id');
		// Ключ по которому мы будем получать доступ в Redis
		$key = sha1($user_email);
		// Сохраняем данные
		$client->hmset($key, [
			'name' => $user_name,
			'email' => $user_email,
			'password' => $user_password,
			'id' => $user_id,
		]);

		return $user_id;
	}
	return;
}

/**
 * Finds user by combination of email and password hash
 *
 * @param string $email
 * @param string $password_hash
 *
 * @return int|null Returns ID of user or null if user not
found
 */
function authorize_user($email, $password_hash) {
	global $client;
	// Получаем все данные по ключу
	$data = $client->hgetall(sha1($email));
	if (!$data) {
		return;
	}
	// Если пароли не совпадают return
	if ($password_hash !== $data['password']) {
		return;
	}

	return $data['id'];
}