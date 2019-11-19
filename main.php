<?php

namespace alice;

include_once 'LogDAL.php';

class main {

    public static $config;

    function __construct($config) {
        //载入配置文件
        self::$config = $config;
    }

    function run() {
        $_action = addslashes(htmlspecialchars(isset($_GET['a']) ? $_GET['a'] : 'default'));
        $_method = addslashes(htmlspecialchars(isset($_GET['m']) ? $_GET['m'] : ''));
        if ($_action == "default") {
            switch ($_method) {
                case 'socketStart':
                    $res = $this->socketStart();
                    if ($res['success']) {
                        exit(json_encode($res['data']));
                    } else {
                        exit(json_encode($res['data']));
                    }
                    break;
                case 'socketStop':
                    $res = $this->socketStop();
                    if ($res['success']) {
                        exit(json_encode($res['data']));
                    } else {
                        exit(json_encode($res['data']));
                    }
                    break;
                default:
                    break;
            }
        }
        include_once 'index.html';
    }

    function socketStart() {
        //start socket
        try {
            $_key = rand(0, 9999999);
            $command = "php socket.php &";
//            LogDAL::saveLog('debug', 'info', '[' . $_key . ']command : ' . json_encode($command));

            $process = proc_open($command, array(), $pipes);
//            LogDAL::saveLog('debug', 'info', '[' . $_key . ']process : ' . json_encode($process));
//            LogDAL::saveLog('debug', 'info', '[' . $_key . ']pipes : ' . json_encode($pipes));

            $var = proc_get_status($process);
//            LogDAL::saveLog('debug', 'info', '[' . $_key . ']var : ' . json_encode($var));

            LogDAL::saveConfig($var['pid'] + 1);
            proc_close($process);
            return ['success' => true, 'data' => $var];
        } catch (Exception $ex) {
            return ['success' => false, 'data' => $ex];
        }
    }

    function socketStop() {
        try {
            $var = LogDAL::getConfig();
            $str = "sudo kill -9 " . $var;
            LogDAL::saveLog('debug', 'info', 'exec : ' . json_encode($str));
            $process = system($str);
            return ['success' => true, 'data' => $process];
        } catch (Exception $ex) {
            return ['success' => false, 'data' => $ex];
        }
    }

}
