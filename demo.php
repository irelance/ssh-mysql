<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20
 * Time: 0:07
 */
include __DIR__ . '/SshMysql.php';

$db = new \Irelance\SshMysql\SshMysql([
    'ssh_host' => '192.168.1.2',
    'ssh_port' => '22',
    'ssh_auth_type' => 'password',
    'ssh_username' => 'root',
    'ssh_password' => 'root',
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_user' => 'root',
    'db_pass' => 'root',
    'db_name' => 'dbname',
    'db_charset' => 'utf8',
]);

if ($rs = $db->query('SELECT * FROM `user` LIMIT 1')) {
    var_dump($rs);
} else {
    echo $db->getErrorMassage();
}
