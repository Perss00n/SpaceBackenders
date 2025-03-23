<?php
require __DIR__ . '/Medoo.php';

use Medoo\Medoo;

$db = new Medoo([
  'type' => 'mysql',
  'host' => 'localhost',
  'database' => '',
  'username' => '',
  'password' => '',
  'charset' => 'utf8',
  'error' => PDO::ERRMODE_EXCEPTION
]);
