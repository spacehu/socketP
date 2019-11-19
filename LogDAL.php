<?php

namespace alice;

/*
 * 用来返回生成首页需要的数据
 * 类
 * 访问数据库用
 * 继承数据库包
 */

class LogDAL {

    // 文件最大3M
    const maxSize = 3;

    function __construct() {
        
    }

    public static function save($str, $filename = 'log') {
        $logPath = './log/';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0777);
        }

        $file = $logPath . $filename . '.txt';
        if (!is_file($file)) {
            touch($file);
        }
        if (filesize($file) > self::maxSize * 1024 * 1024) {
            for ($i = 0; $i < 100000; $i ++) {
                $newfile = $logPath . $filename . "." . $i . ".txt";
                if (!is_file($newfile)) {
                    copy($file, $newfile);
                    unlink($file);
                    break;
                }
            }
        }

        file_put_contents($file, $str . "\n", FILE_APPEND);
    }

    public static function saveLog($level, $info, $keyword) {
        $str = "[" . $level . "][" . $info . "][" . date("Y-m-d H:i:s") . "]:" . $keyword . "";
        self::save($str, $level);
    }

    /** save pid */
    public static function saveConfig($str, $filename = 'config') {
        $logPath = './config/';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0777);
        }

        $file = $logPath . $filename . '.txt';
        if (!is_file($file)) {
            touch($file);
        }
        file_put_contents($file, $str);
    }

    /** get pid */
    public static function getConfig($filename = 'config') {
        $logPath = './config/';
        $file = $logPath . $filename . '.txt';
        if (!is_file($file)) {
            touch($file);
        }
        $res = file_get_contents($file);
        return $res;
    }

}
