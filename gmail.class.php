<?php
/**
 * Created by PhpStorm.
 * User: Agafonov Alexey (supmea@gmail.com)
 * Date: 20.11.13
 * Time: 15:25
 */

class gmail{

    public function __construct($email,$password){
        $this->account =array(
            'accountType' => 'GOOGLE',
            'Email' => $email,
            'Passwd' => $password,
            'service' => 'apps',
        );
        $this->token = "";
    }

    private function getToken(){
        $tk_ch = curl_init();

        curl_setopt($tk_ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
        curl_setopt($tk_ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($tk_ch, CURLOPT_POST, true);
        curl_setopt($tk_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($tk_ch, CURLOPT_POSTFIELDS, $this->account);

        $response = curl_exec($tk_ch);

        curl_close($tk_ch);

        $str_split = explode('=', $response);

        $this->token = $str_split[3];

        return $this->token;
    }

    public function response($username, $domain, $setting, $action, array $params = array()){
        $username = strtolower($username);
        $domain = strtolower($domain);
        $setting = strtolower($setting);
        $action = strtolower($action);
        if($this->token == "") $this->getToken();
        $xml = "<?xml version='1.0' encoding='utf-8'?>\n";
        $xml .= "<atom:entry xmlns:atom='http://www.w3.org/2005/Atom' xmlns:apps='http://schemas.google.com/apps/2006'>\n";
        foreach($params as $key=>$param){
            $name = $key;
            $value = htmlentities($param);
            $xml .= "<apps:property name='$name' value='$value' />\n";
        }
        $xml .= "</atom:entry>\n";

        $ch = curl_init();

        if ($username)
            $url_feed = "https://apps-apis.google.com/a/feeds/emailsettings/2.0/".$domain."/".$username."/".$setting;
        else
            $url_feed = "https://apps-apis.google.com/a/feeds/emailsettings/2.0/".$domain."/".$setting;

        curl_setopt($ch, CURLOPT_URL, $url_feed);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: GoogleLogin auth="'.trim($this->token).'"',
            'Content-type: application/atom+xml'
        ));
        switch ($action) {
            case "create":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                break;
            case "update":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                break;
            case "delete":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                break;
            case "retrieve":
                break;
        }

        $result = curl_exec($ch);

        curl_close($ch);

        if ($action == "retrieve") return html_entity_decode($result);
        else
            if($result)
                return true;
            else
                return false;
    }
}
?>
