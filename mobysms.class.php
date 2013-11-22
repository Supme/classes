<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Agafonov Alexey (supmea@gmail.com)
 * Date: 17.10.13
 * Time: 13:44
 */

class mobysms{

    public function __construct() {
        $this->debug = false;
        $this->url = "http://service.smsconsult.ru";
        $this->login = "login";
        $this->password = "password";
        $this->sender = false;
    }

    public function readSms($id = false){
        // собираем XML для получения сообщений
        $domout = new DomDocument('1.0', 'utf-8');
        $package = $domout->appendChild($domout->createElement('package'));
        $package->setAttribute('login', $this->login);
        $package->setAttribute('password', $this->password);
        $incoming = $package->appendChild($domout->createElement('incoming'));
        $get_msg = $incoming->appendChild($domout->createElement('get_msg'));
        if ($id) {
            if (is_array($id)){
                foreach ($id as $point){
                    $msg = $incoming->appendChild($domout->createElement('msg'));
                    $msg->setAttribute('sms_id',$point);
                }
            } else {
                    $msg = $incoming->appendChild($domout->createElement('msg'));
                    $msg->setAttribute('sms_id',$id);
            }
        }
        $domout->formatOutput = true;
        $xml = $domout->saveXML();
        if($this->debug){
            echo "\nSend XML:\n";
            print_r($xml);
        }

        // засылаем на сервер и получаем ответ
        $result = $this->curlXml($xml);

        // разбираем полученую XML в массив
        $domin = new DomDocument();
        $domin->loadXML($result);
        $msg = $domin->getElementsByTagName('msg');
        $total=false;
        foreach ($msg as $sms){
            $total[$sms->getAttribute('sms_id')]['sms_id'] = $sms->getAttribute('sms_id');
            $total[$sms->getAttribute('sms_id')]['sender'] = $sms->getAttribute('sender');
            $total[$sms->getAttribute('sms_id')]['destination'] = $sms->getAttribute('destination');
            $total[$sms->getAttribute('sms_id')]['date_received'] = $sms->getAttribute('date_received');
            $total[$sms->getAttribute('sms_id')]['text'] = $sms->nodeValue;
        }
        return $total;
    }

    public function sendSms($phone, $text){
        //ToDo: тут бы с кавычками разобраться, не заменяются они
        $search  = array('<', '>', chr(147), chr(148), '\x9d', '\x8c', '\x99', '\x98', '\x80', '\xc2', '&');
        $replace = array('&lt;', '&gt;', '&quot;', '&quot;', '&quot;', '&quot;', '&apos;', '&apos;', '&apos;', '&apos;', '&amp;');

        // собираем XML для отправки
        $domout = new DomDocument('1.0', 'utf-8');
        $package = $domout->appendChild($domout->createElement('package'));
        $package->setAttribute('login',$this->login);
        $package->setAttribute('password',$this->password);
        $message = $package->appendChild($domout->createElement('message'));
        $default = $message->appendChild($domout->createElement('default'));
        if ($this->sender) $default->setAttribute('sender', $this->sender);
        if (is_array($phone)){
            $count = 0;
            foreach ($phone as $phn){
                $msg = $message->appendChild($domout->createElement('msg'));
                $msg->setAttribute('recipient',$phn);
                if (is_array($text)){
                    $msg->appendChild($domout->createTextNode(str_replace($search, $replace,$text[$count])));
                    $count = $count+1;
                } else
                    $msg->appendChild($domout->createTextNode(str_replace($search, $replace,$text)));
            }
        } else {
            $msg = $message->appendChild($domout->createElement('msg'));
            $msg->setAttribute('recipient',$phone);
            $msg->appendChild($domout->createTextNode($text));
        }
        $domout->formatOutput = true;
        $xml = $domout->saveXML();
        if($this->debug){
            echo "\nSend XML:\n";
            print_r($xml);
        }

        // засылаем на сервер и получаем ответ
        $result = $this->curlXml($xml);

        // разбираем полученую XML в массив
        $domin = new DomDocument();
        $domin->loadXML($result);
        $msg = $domin->getElementsByTagName('msg');
        $total=false;
        foreach ($msg as $sms){
            $total[$sms->getAttribute('sms_id')]['sms_id'] = $sms->getAttribute('sms_id');
            switch ($sms->nodeValue){
                case 100:
                    $status = 'OK';
                    break;
                case 200:
                    $status = 'ERR_UNKNOWN';
                    break;
                case 201:
                    $status = 'ERR_FORMAT';
                    break;
                case 202:
                    $status = 'ERR_AUTHORIZATION';
                    break;
                default:
                    $status = 'UNKNOWN';
            }
            if (!$sms->getAttribute('sms_id')) $status = 'ERR_NUMBER';
            $total[$sms->getAttribute('sms_id')]['status'] = $status;
        }

        return $total;
    }

    public function statusSms($id){

        // собираем XML для получения сообщений
        $domout = new DomDocument('1.0', 'utf-8');
        $package = $domout->appendChild($domout->createElement('package'));
        $package->setAttribute('login',$this->login);
        $package->setAttribute('password',$this->password);
        $status = $package->appendChild($domout->createElement('status'));
        if (is_array($id)){
            foreach ($id as $i){
                $msg = $status->appendChild($domout->createElement('msg'));
                $msg->setAttribute('sms_id',$i);
            }
        } else {
            $msg = $status->appendChild($domout->createElement('msg'));
            $msg->setAttribute('sms_id',$id);
        }

        $domout->formatOutput = true;
        $xml = $domout->saveXML();

        // засылаем на сервер и получаем ответ
        $result=$this->curlXml($xml);


        // разбираем полученую XML в массив
        $domin = new DomDocument();
        $domin->loadXML($result);
        $msg = $domin->getElementsByTagName('msg');
        $total=false;
        foreach ($msg as $sms){
            $total[$sms->getAttribute('sms_id')]['sms_id'] = $sms->getAttribute('sms_id');
            $total[$sms->getAttribute('sms_id')]['date_completed'] = $sms->getAttribute('date_completed');
            $total[$sms->getAttribute('sms_id')]['error_code'] = $sms->getAttribute('error_code');
            switch ($sms->nodeValue){
                case 100:
                    $status = 'SCHEDULED';
                    break;
                case 101:
                    $status = 'ENROUTE';
                    break;
                case 102:
                    $status = 'DELIVERED';
                    break;
                case 103:
                    $status = 'EXPIRED';
                    break;
                case 104:
                    $status = 'DELETED';
                    break;
                case 105:
                    $status = 'UNDELIVERABLE';
                    break;
                case 106:
                    $status = 'ACCEPTED';
                    break;
                case 107:
                    $status = 'UNKNOWN';
                    break;
                case 108:
                    $status = 'REJECTED';
                    break;
                case 109:
                    $status = 'DISCARDED';
                    break;
                case 200:
                    $status = 'ERR_UNKNOWN';
                    break;
                case 201:
                    $status = 'ERR_ID';
                    break;
                case 202:
                    $status = 'ERR_SENDER';
                    break;
                case 203:
                    $status = 'ERR_RECIPIENT';
                    break;
                case 204:
                    $status = 'ERR_LENGTH';
                    break;
                case 205:
                    $status = 'ERR_USER_DISABLE';
                    break;
                case 206:
                    $status = 'ERR_BILLING';
                    break;
                case 207:
                    $status = 'ERR_OVERLIMIT';
                    break;
                default:
                    $status = 'UNKNOWN';
            }
            $total[$sms->getAttribute('sms_id')]['status'] = $status;
        }

        return $total;
    }

    private function curlXml($xml){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        if($this->debug){
            echo "\nLoad XML:\n";
            print_r($result);
        }
        return $result;
    }

}
