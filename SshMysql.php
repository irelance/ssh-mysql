<?php
/**
 * Created by PhpStorm.
 * User: Irelance
 * Date: 2018/7/19
 * Time: 21:40
 */

namespace Irelance\SshMysql;


class SshMysql
{
    protected $config = [
        'ssh_host' => '',
        'ssh_port' => '22',
        'ssh_auth_type' => 'none',//use the ssh2_auth_xxx function, this version only support none,password,pubkey_file
        'ssh_username' => '',
        'ssh_password' => '',
        'ssh_pubkeyfile' => '',
        'ssh_privkeyfile' => '',
        'ssh_passphrase' => '',
        //mysql
        'db_host' => '',
        'db_port' => '3306',
        'db_user' => '',
        'db_pass' => '',
        'db_name' => '',
        'db_charset' => 'utf8',
    ];

    protected $session;

    protected $errorCode = '';
    protected $errorMessage = '';
    protected $sqlState = '';

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->connectSsh();
    }

    public function __destruct()
    {
    }

    protected function connectSsh()
    {
        $this->session = ssh2_connect(
            $this->config['ssh_host'],
            $this->config['ssh_port']
        );
        switch ($this->config['ssh_auth_type']) {
            case 'none':
                $result = ssh2_auth_none($this->session, $this->config['ssh_username']);
                break;
            case 'password':
                $result = ssh2_auth_password($this->session, $this->config['ssh_username'], $this->config['ssh_password']);
                break;
            case 'pubkey_file':
                $result = ssh2_auth_pubkey_file($this->session, $this->config['ssh_username'],
                    $this->config['ssh_pubkeyfile'], $this->config['ssh_privkeyfile'],
                    $this->config['ssh_passphrase']);
                break;
            default:
                throw new \Error("Authentication Method Not Support\n");
        }
        if (!$result) {
            throw new \Error("Authentication Failed...\n");
        }
    }

    public function query($sql)
    {
        $stdout_stream = ssh2_exec($this->session, sprintf(
            'mysql -h"%s" -P"%s" -u"%s" -p"%s" -D"%s" --default-character-set="%s" -e"%s"',
            $this->config['db_host'],
            $this->config['db_port'],
            $this->config['db_user'],
            $this->config['db_pass'],
            $this->config['db_name'],
            $this->config['db_charset'],
            $sql
        ));
        stream_set_blocking($stdout_stream, true);
        $err_stream = ssh2_fetch_stream($stdout_stream, SSH2_STREAM_STDERR);
        stream_set_blocking($err_stream, true);
        if ($result_err = stream_get_contents($err_stream)) {
            $pattern = "/^ERROR ([0-9]+) \(([0-9A-Za-z]+)\) at line 1: (.+)/";
            $matches = [];
            preg_match($pattern, $result_err, $matches);
            $this->errorCode = $matches[1];
            $this->sqlState = $matches[2];
            $this->errorMessage = $matches[3];
            return false;
        }
        if ($stdout = stream_get_contents($stdout_stream)) {
            $stdout = explode("\n", $stdout);
            $header = explode("\t", array_shift($stdout));
            $result = [];
            foreach ($stdout as $item) {
                if ($item = trim($item)) {
                    $item = explode("\t", $item);
                    $one = [];
                    foreach ($item as $key => $value) {
                        if ($value == 'NULL') {
                            $value = null;
                        }
                        $one[$header[$key]] = $value;
                    }
                    $result[] = $one;
                }
            }
            return $result;
        }
        return true;
    }

    public function getErrorMassage()
    {
        return $this->errorMessage;
    }
}