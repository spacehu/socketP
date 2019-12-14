<?php

namespace alice;

header("Content-Type: text/html;charset=utf-8");
//确保在连接客户端时不会超时
set_time_limit(0);
include_once 'LogDAL.php';

class WS {
    /* 主进程 */

    var $master;
    /* 子进程集合 */
    var $sockets = array();
    /* 是否开启调试 */
    var $debug = true;
    /* 创建的握手 */
    var $handshake = array();

    function __construct() {
        
    }

    function start($address, $port) {
        try {
            /**
             * 创建唯一的主套接字
             * 为这个套接字设置属性
             * 将它绑定到一个固定的ip和端口上
             * 并且设置监听上限
             */
            $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");
            socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1) or die("socket_option() failed");
            socket_bind($this->master, $address, $port) or die("socket_bind() failed");
            socket_listen($this->master, 600) or die("socket_listen() failed");

            /**
             * 输入到套接字的集合中
             * 设置握手成功
             */
            $this->sockets[] = $this->master;
            $this->handshake[] = true;
            $this->say("Server Started : " . date('Y-m-d H:i:s'));
            $this->say("Listening on   : " . $address . " port " . $port);
            $this->say("Master socket  : " . $this->master . "\n");

            while (true) {
                /**
                 * 初始化套接字集合
                 */
                $socketArr = $this->sockets;
                $write = NULL;
                $except = NULL;
                /* 自动选择来消息的socket 如果是握手 自动选择主机 */
                socket_select($socketArr, $write, $except, NULL);

                //$this->say("socket tree    : *" . json_encode($socketArr) . "*"); //打印链接树

                foreach ($socketArr as $skey => $socket) {
                    if ($socket === $this->master) {  //主机
                        $client = socket_accept($this->master); // 获取和主机链接的子机数量
                        if ($client < 0) {
                            $this->log("socket_accept() failed");
                            continue;
                        } else {
                            $this->connect($client); // 记录子机的#id
                        }
                    } else { // 子机
                        $bytes = @socket_recv($socket, $buffer, 2048, 0);
                        if ($bytes == 0) {
                            $this->disConnect($socket);
                        } else {
                            $bs=json_decode($buffer);
                            if(!empty($bs->tag)&&$bs->tag=='client'){
                                $this->say($buffer);
                                foreach ($this->sockets as $k => $v) {
                                    if ($v !== $this->master) {
                                        if($v==$socket){
                                            $this->log($bs->obj->value);
                                            $this->send_client($v,$bs->obj->value);
                                        }else{
                                            $this->send($v, $bs->obj->value);
                                        }
                                    }
                                }
                                $this->disConnect($socket);
                            }else if (empty($this->handshake[$skey]) || !$this->handshake[$skey]) {
                                $this->doHandShake($socket, $buffer);
                            } else {
                                $this->say($buffer);
                                $buffer = $this->decode($buffer);
                                $this->say($buffer);
                                if($buffer=="space_close"){
                                    //$this->say("close from space");
                                    $this->log("close from space");
                                    $this->disConnect($socket);
                                }
                                $this->say($socket . " : " . $buffer); //打印链接树
                                foreach ($this->sockets as $k => $v) {
                                    if ($v !== $this->master) {
                                        $this->send($v, $buffer);
                                        //die;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->log($e->getMessage());
            throw $e->getMessage();
        }
        $this->disConnect($this->master);
    }

    function send($client, $msg) {
        $msg = $this->frame($msg);
        try {
            socket_write($client, $msg, strlen($msg));
        } catch (Exception $e) {
            throw $e->getMessage();
        }
    }
    
    function send_client($client, $msg) {
        try {
            socket_write($client, $msg, strlen($msg));
        } catch (Exception $e) {
            throw $e->getMessage();
        }
    }

    function connect($socket) {
        $this->sockets[] = $socket;
        $this->say("\n" . $socket . " CONNECTED!");
        $this->say(date("Y-n-d H:i:s"));
        $this->log($this->sockets);
        $this->log($this->handshake);
    }

    function disConnect($socket) {
        $index = array_search($socket, $this->sockets);
        socket_close($socket);
        $this->say($socket . " DISCONNECTED!");
        if ($index >= 0) {
            array_splice($this->sockets, $index, 1);
            array_splice($this->handshake, $index, 1);
        }
    }

    function doHandShake($socket, $buffer) {
        $this->log("Requesting handshake...");
        $this->log($buffer);
        list($resource, $host, $origin, $key) = $this->getHeaders($buffer);
        $this->log("Handshaking...");
        $upgrade = "HTTP/1.1 101 Switching Protocol\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Accept: " . $this->calcKey($key) . "\r\n\r\n";  //必须以两个回车结尾
        $this->log($upgrade);
        socket_write($socket, $upgrade, strlen($upgrade));
        $this->handshake[] = true;
        $this->log("Done handshaking...");
        return true;
    }

    function getHeaders($req) {
        $r = $h = $o = $key = null;
        if (preg_match("/GET (.*) HTTP/", $req, $match)) {
            $r = $match[1];
        }
        if (preg_match("/Host: (.*)\r\n/", $req, $match)) {
            $h = $match[1];
        }
        if (preg_match("/Origin: (.*)\r\n/", $req, $match)) {
            $o = $match[1];
        }
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match)) {
            $key = $match[1];
        }
        return array($r, $h, $o, $key);
    }

    function calcKey($key) {
//基于websocket version 13
        $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        return $accept;
    }

    function decode($buffer) {
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;

        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    function frame($s) {
        $a = str_split($s, 125);
        if (count($a) == 1) {
            return "\x81" . chr(strlen($a[0])) . $a[0];
        }
        $ns = "";
        foreach ($a as $o) {
            $ns .= "\x81" . chr(strlen($o)) . $o;
        }
        return $ns;
    }

    function say($msg = "") {
        echo $msg . "\n";
    }

    function log($msg = "") {
        if ($this->debug) {
            //print_r($msg);
            LogDAL::saveLog('log', 'info', json_encode($msg));
        }
    }

    function straddcslashes($str) {
        return addslashes($str);
    }

    function strstripslashes($str) {
        return stripslashes($str);
    }

}

$ws = new WS();

//$ws->start('192.168.226.131', 12345);
$ws->start('0.0.0.0', 12345);
