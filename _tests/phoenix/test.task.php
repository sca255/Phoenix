<?php

require_once $settings['functions'].'function.task.php';

$result = task($connection, $settings, '__TASK__', 1);

$delete = 'DELETE FROM `'.$settings['db_prefix'].'tasks` WHERE `name` LIKE \'__TEST_%\';';
mysqli_query($connection, $delete);

if ( !$result ) {
	echo 'Error: Test for Function "task" failed.';
	exit(1);
}
