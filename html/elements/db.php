<?php

class Portal {
    
    private static $db = NULL;
    public static $targetDomain = NULL;
    public static $thisdomain = [ "name" => "captive", "zone" => "portal", ];


    public static function Init(){
        if(!file_exists("mytest.db")){
            self::$db = new SQLite3("mytest.db");
            $sql="CREATE TABLE `users` (
                `id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
                `username`	VARCHAR ( 45 ) NOT NULL UNIQUE,
                `password`	VARCHAR ( 255 ) NOT NULL,
                `fname`	VARCHAR ( 45 ) NOT NULL,
                `lname`	VARCHAR ( 45 ) NOT NULL,
                `patronymic`	VARCHAR ( 45 ),
                `address`	VARCHAR ( 45 ),
                `mail`	VARCHAR ( 45 ) NOT NULL,
                `phone`	INT NOT NULL,
                `t_mac`	VARCHAR(16) NOT NULL DEFAULT '00:00:00:00:00:00',
                `hash`	VARCHAR ( 255 ) DEFAULT 0,
                `endlogin`	TIMESTAMP ( 12 ) DEFAULT 0
            );
            CREATE TABLE `macs` (
            `id`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
            `uid`	INTEGER NOT NULL,
            `mac`	VARCHAR(16) NOT NULL DEFAULT '00:00:00:00:00:00',
            `session_start`	TIMESTAMP (12) NOT NULL DEFAULT 0,
            `session_end`	TIMESTAMP (12) NOT NULL DEFAULT 0
            );";
            self::$db->query($sql);
        }else if (self::$db == NULL){
            self::$db = new SQLite3("mytest.db");
        }
    }

    public static function AddUser($user,$pass,$fname,$lname,$addr,$mail,$phone){
        if (preg_match('/[0-9]+/', $fname) || preg_match('/[0-9]+/', $lname) || preg_match('/[A-Za-z]+/', $phone)) { return 441; }
        self::$db->exec('BEGIN;');
        $adduser="INSERT INTO `users` (`username`, `password`, `fname`, `lname`, `address`, `mail`, `phone`, `t_mac`) VALUES (:uname, :passwd, :fname, :lname, :addr, :mail, :phone, :mac)";
        $mac = self::GetMac();
        //$adduser="INSERT INTO `users` (`username`, `password`, `fname`, `lname`, `address`, `mail`, `phone`) VALUES (:uname, :passwd, :fname, :lname, :addr, :mail, :phone)";
        $st = self::$db->prepare($adduser);
        $st->bindValue(':uname', htmlspecialchars($user));
        $st->bindValue(':passwd', htmlspecialchars($pass));
        $st->bindValue(':fname', htmlspecialchars($fname));
        $st->bindValue(':lname', htmlspecialchars($lname));
        $st->bindValue(':addr', htmlspecialchars($addr));
        $st->bindValue(':mail', htmlspecialchars($mail));
        $st->bindValue(':phone', $phone);
        $st->bindValue(':mac', $mac);
        $result = $st->execute();
        self::$db->exec('COMMIT;');
        if (!$result) return 442;
        else return 0;
        //addMac($user, $mac);
    }

    public static function FindMac($mac){
        $sql="SELECT mac FROM macs WHERE mac = :mac;";
        $sta = self::$db->prepare($sql);
        $sta->bindParam(':mac', $mac);
        $result = $sta->execute();
        $row = $result->fetchArray();
        if ($row[0] == $mac){
            return true;
        }        
        else{
            return false;
        }
    }

    public static function AddMac($user, $mac){
        self::$db->exec('BEGIN;');
        $set="UPDATE users SET t_mac = :mac WHERE username = :usr;";
        $st = self::$db->prepare($set);
        $st->bindValue(':mac', $mac);
        $st->bindValue(':usr', $user);
        $st->execute(); 
        $addmac="INSERT INTO macs (uid, mac, session_start) SELECT id, :mac, datetime('now') FROM users WHERE username = :uname ;";
        $sta = self::$db->prepare($addmac);
        $sta->bindValue(':mac', $mac);
        $sta->bindValue(':uname', $user);
        $sta->execute(); 
        self::$db->exec('COMMIT;');
    }

    public static function Auth($user, $pass){
        $sql="SELECT id,password,username FROM users WHERE username = :usr;";
        $st = self::$db->prepare($sql);
        $st->bindParam(':usr', $user);
        $result = $st->execute();
        $row = $result->fetchArray();
        $mac = self::GetMac();
        //print_r($row[1]);
        if (password_verify($pass, $row[1])){
            //session_start();
            /*if(self::CheckSession($mac))
                self::AddMac($row[2], $mac);*/
            self::UpdateCookies($row[0]);
            return true;
        }

        return false;
    }

    public static function CheckSession($mac) {
        $sql="SELECT session_end FROM macs WHERE mac = :mac AND session_end = :t;";
        $st = self::$db->prepare($sql);
        $st->bindValue(':mac', $mac);
        $st->bindValue(':t', '0');
        $result = $st->execute(); 
        $row = $result->fetchArray();
        //var_dump($row);
        if ($row == false) return true;
        else return false;
    }

    private static function Enable_access($mac) {        
        exec("sudo ipset add usr_macs $mac");
        if (self::$targetDomain != NULL) {
            self::AutoRedirect('target', self::$targetDomain);
            die();
        }
    }    

    public static function FlushMacs($all = false) {
        $dba = new SQLite3("/var/www/html/mytest.db");   

        //Получаем маки с неоконченной сессией 
        $sql="SELECT mac,session_start FROM macs WHERE session_end = :t;";
        $st = $dba->prepare($sql);
        $st->bindValue(':t', '0');
        $result = $st->execute(); 
        $tarr = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)){            
            array_push($tarr, $row);            
        }
        $file = 'users.log';
        $flushed = array();
        //Закрываем сессии для названных
        foreach ($tarr as $val){
            if ((time() - strtotime($val['session_start']) >= 1800) || $all){
                $set="UPDATE macs SET session_end = :time WHERE macs.mac = :mac AND session_end = 0;";
                $sta = $dba->prepare($set);
                $time = date("Y-m-d H:i:s");
                $sta->bindValue(':time', $time);
                $sta->bindValue(':mac', $val['mac']);
                $sta->execute(); 

                exec("sudo ipset del usr_macs ". $val['mac']);

                $line = "Start: " . $val['session_start'] . " End: " . date("Y-m-d H:i:s") . "  Mac ". $val['mac'] . " was removed\n";
                file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
                array_push($flushed, $val['mac']); 

                echo "Success!\n";
            }
        } 
        $dba->close();

        /*//Консольный вывод
        echo "ARP\n";
        var_dump($macAddr);
        echo "--------------------------------------------\n     \n";*/
        echo "Unfinished sessions\n";
        print_r($tarr);
        echo "--------------------------------------------\n     \n";
        echo "Flushed Macs (". count($flushed) . ")\n";
        print_r($flushed);
    }

    /*public static function FlushMacs() {
        $dba = new SQLite3('../var/www/html/mytest.db');        
        //Обновляем ARP таблицу
        shell_exec("ip -s -s neigh flush all");
        sleep(30);
        $mac = shell_exec("arp");
        $lines=explode("\n", $mac);
        $macAddr= array();
        foreach($lines as $line)
        {
            preg_match('/eth1/', $line , $match);
            if ($match != NULL){
                preg_match('/(([\da-fA-F]{2}[-:]){5}[\da-fA-F]{2})/', $line , $matches);
                if ($matches != NULL)
                    array_push($macAddr, $matches[0]);
            }
        }        
        //Получаем маки с неоконченной сессией 
        $sql="SELECT mac,session_start FROM macs WHERE session_end = :t;";
        $st = $dba->prepare($sql);
        $st->bindValue(':t', '0');
        $result = $st->execute(); 
        $tarr = array();
        while ($row = $result->fetchArray()){            
            array_push($tarr, $row);            
        }    
        
        $flushed = array();
        //Закрываем сессии для названных
        foreach ($tarr as $val){
            if (!in_array( $val['mac'], $macAddr) & (time() - strtotime($val['session_start']) >= 1800)){
                $set="UPDATE macs SET session_end = :time WHERE macs.mac = :mac AND session_end = 0;";
                $sta = $dba->prepare($set);
                $time = date("Y-m-d H:i:s");
                $sta->bindValue(':time', $time);
                $sta->bindValue(':mac', $val['mac']);
                $sta->execute(); 

                array_push($flushed, $val['mac']); 
                echo "Success!";
            }
        } 
        $db->close();
        
        //Консольный вывод
        echo "ARP\n";
        var_dump($macAddr);
        echo "--------------------------------------------\n     \n";
        echo "Unfinished sessions\n";
        var_dump($tarr);
        echo "--------------------------------------------\n     \n";
        echo "Flushed Macs (". count($flushed) . ")\n";
        var_dump($flushed);
    }*/
    
    public static function GetMac() {
        $mac = shell_exec("arp -a ".$_SERVER['REMOTE_ADDR']);
        preg_match('/..:..:..:..:..:../',$mac , $matches);
        $mac = $matches[0];
        if (!isset($mac)) { exit; }
        return $mac;
    }

    public static function CheckAuthCookie() {
        $browser = get_browser(null, true);
        $data = md5($browser['parent'].$browser['platform'].$browser['version']);
        if (isset($_COOKIE[$data])){
            $hash = $_COOKIE[$data];
            $sql="SELECT t_mac,endlogin,username FROM users WHERE `hash` = :hash;";
            $st = self::$db->prepare($sql);
            $st->bindParam(':hash', $hash);
            $result = $st->execute();
            $row = $result->fetchArray();
            $mac = self::GetMac();
            //print_r($row);
            if ($row[0] == $mac){
                if (strtotime($row[1]) >= time()){
                    if (self::CheckSession($row[0])){
                        self::AddMac($row[2], $mac);
                        self::Enable_access($row[0]);
                    }                  
                    //exit();
                } 
                else {
                    unset($_COOKIE[$data]);
                    return false;   
                };
            }    
            else {
                unset($_COOKIE[$data]);
                return false;   
            };
        }
    }

    public static function UpdateCookies($userID) {
        $browser = get_browser(null, true);
        $data = md5($browser['parent'].$browser['platform'].$browser['version']);
        $get="SELECT id,t_mac,phone,datetime('now','+7 days') date FROM users WHERE users.id = :id;";
        $st = self::$db->prepare($get);
        $st->bindParam(':id', $userID);
        $result = $st->execute();
        $row = $result->fetchArray();
        $json = json_encode($row);
        $hash = md5($json);
        $set="UPDATE users SET endlogin = :date, hash = :hash WHERE users.id = :id;";
        $st = self::$db->prepare($set);
        $st->bindParam(':date', $row['date']);
        $st->bindParam(':hash', $hash);
        $st->bindParam(':id', $userID);
        $st->execute();
        setcookie($data, $hash, strtotime($row['date']));
    }

    public static function AutoRedirect($request, $param = NULL){
        switch ($request){
            case 'regst':
                require_once 'elements/reg.php';
                die();
            case 'login':
                require_once 'elements/login.php';
                die();
            case 'wait':
                require_once 'elements/wait.php';
                die();
            case 'redirect':             
                header("Location: https://" . self::$thisdomain['name'].".".self::$thisdomain['zone'] ."/index.php?redirect=" . $param);
                header("Connection: close");
                die();
            case 'target':
                header("Location:" . self::$targetDomain); 
                header("Connection: close"); 
                die();
        }
    }

    public static function ErrorDispatcher($GET){
        $err_text = NULL;
        if (isset($GET['err'])){
            switch ($GET['err']){
                case 100:
                    $err_text = "Incorrect login or password";
                    break;
                case 101:
                    $err_text = "Check the entered data";
                    break;
                case 102:
                    $err_text = "User already exist";
                    break;
            }
            echo "<script>alert('".$err_text."'); window.location = 'index.php?redirect=" . urlencode(self::$targetDomain) . "';</script>";
        }
        
    }

    public static function RedirectUriToString(){
        if (self::$targetDomain != NULL) { return urlencode(self::$targetDomain); }
        else { return "index.php"; }
    }

    public static function CheckUserMac(){
        $ref = NULL;
        if (self::$targetDomain != NULL) { $ref = "&redirect=".urlencode(self::$targetDomain); }
        $link = "index.php?reg$ref";
        if (self::FindMac(self::GetMac())){
            $link = "index.php?login$ref";
        }
        return $link;
    }
}
