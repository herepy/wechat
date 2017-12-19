<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2017/11/13
 * Time: 23:51
 */

class WeChat{
    protected $appId;
    protected $appSecret;

    public function __construct($appId,$appSecret)
    {
        $this->appId=$appId;
        $this->appSecret=$appSecret;
    }

    //获取终端ip
    protected function getIp(){
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) { array_unshift($ips, $ip); $ip = false; }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match ("^(10│172.16│192.168).", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    //数组转xml数据
    protected function arrToXml($arr){
        $str="";
        foreach ($arr as $key => $val){
            $str.="<{$key}>".$val."</{$key}>";
        }
        return "<xml>".$str."</xml>";
    }

    //xml转数组
    protected function xmlToArr($xml){
        if(!$xml){
            return false;
        }
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    //数组生成url键值对形式(不转码)
    protected function arrToUrl($arr){
        $str="";
        foreach ($arr as $k => $v){
            if($k=="sign"){
                continue;
            }
            $str.=$k."=".$v."&";
        }
        return rtrim($str,"&");
    }

    //生成随机字符串
    public function randStr($length=16){
        $lib="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $str="";
        while ($length>0){
            $str.=$lib[mt_rand(0,strlen($lib)-1)];
            $length--;
        }
        return $str;
    }

    //curl 请求数据
     function http_request($url,$query=null,$is_post=false,$certPath=null,$keyPath=null){
         if($query!==null && $is_post==false){
             if(is_array($query)){
                 $url.="?".http_build_query($query);
             }else{
                 $url.="?".$query;
             }
         }
         $ch=curl_init($url);
         if($is_post){
             curl_setopt($ch,CURLOPT_POST,true);
             curl_setopt($ch,CURLOPT_POSTFIELDS,$query);
         }
         if($certPath && $keyPath){   //需要验证证书
             //默认格式为PEM
             curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
             curl_setopt($ch,CURLOPT_SSLCERT,$certPath);

             curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
             curl_setopt($ch,CURLOPT_SSLKEY,$keyPath);
         }else{
             curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);
             curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
         }

         curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
         $res=curl_exec($ch);

         return $res;
    }

    //获取调用接口的access_token  7200s
    public function getAccessToken(){
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
        $res=json_decode($this->http_request($url),true);
        $ok=array_key_exists("errcode",$res);
        return $ok?false:$res["access_token"];
     }

     //获取微信服务器ip地址  return array("127.0.0.1","127.0.0.2",)
    public function wechatIpList($access_token){
        $url="https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$access_token;
        $res=json_decode($this->http_request($url),true);
        $ok=array_key_exists("errcode",$res);
        return $ok?false:$res["ip_list"];
    }

    //获取jsapi_ticket(config签名使用)  7200s
    protected function getJsapiTicket($access_token){
        $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
        $res=json_decode($this->http_request($url));
        return $res->errcode==0?$res->ticket:false;
    }

    //生成JS-SDK 签名(config使用)
    public function configSign($jsapi_ticket,$url){
        $param=array(
            "noncestr"      =>  $this->randStr(),
            "jsapi_ticket"  =>  $jsapi_ticket,
            "timestamp"     =>  time(),
            "url"           =>  $url,
        );
        //字典排序
        ksort($param);
        //拼成url参数形式
        $str=$this->arrToUrl($param);
        //sha1加密
        $sign=sha1($str);
        $param["signature"]=$sign;
        $param["appid"]=$this->appId;
        return $param;
    }

    //获取api_ticket(微信卡券使用)  7200s
    public function getApiTicket($access_token){
        $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=wx_card";
        $res=json_decode($this->http_request($url));
        return $res->errcode==0?$res->ticket:false;
    }

    //生成卡券签名
    public function cardSign($api_access){

    }





}
