<?php

return [
    'class'       => 'yii\db\Connection',
    'dsn'         => 'pgsql:host=localhost;port=5432;dbname=eventtracker_test',
    'username'    => 'eventtracker_tester',
    'password'    => 'passwd',
    'tablePrefix' => 'tbl_',
    'charset'     => 'utf8',
];