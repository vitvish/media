<?php
echo "<a style='position:fixed; right: 0; font-size:34px; color: navy' href='redis.php'>redis</a>";
require_once "config.php";
require_once "functions.php";
// Level 1
$count_generates = 10000000;
$words = ['red', 'green', 'blue', 'yellow', 'orange'];

$t = microtime(true);
$strings = render_strngs($words, $count_generates);
echo "Массив $count_generates строк был сформирован T = " . (microtime(true) - $t) . "<br>";

$t = microtime(true);
$uniques = get_uniques($strings);
echo "Уникальные значения получены T = " . (microtime(true) - $t) . "<br>";

// Level 2
/**
 * Если бы нам нужно было хранить не такие большие строки до нескольких мегабайт можно было бы в БД создать поле с уникальним ключом и тогда СУБД не дало бы вставить одинаковые строки, а так только так:

1. Создаем таблицу
CREATE TABLE `unique_string`
(
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
`u_string` MEDIUMTEXT NOT NULL,
PRIMARY KEY (id)
) COMMENT 'Table unique string';

2. Создаем хранимую функцию
delimiter //
CREATE FUNCTION check_string (x1 MEDIUMTEXT) RETURNS INT
BEGIN
SELECT COUNT(*) into @res FROM `unique_string` where `u_string` = x1;
return @res;
END//
delimiter ;
 *
 *
 */

save_strings_db($uniques);

//Level 3
// DELETE FROM WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MONTHS)
