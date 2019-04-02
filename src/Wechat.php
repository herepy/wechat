<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2017/11/13
 * Time: 23:51
 */

namespace Pywechat;

class WeChat{
    protected $appId;
    protected $appSecret;

    public function __construct($appId,$appSecret)
    {
        $this->appId=$appId;
        $this->appSecret=$appSecret;
    }

    //获取终端ip
    protected function getIp()
    {
        $ip=false;

        if($_SERVER['REMOTE_ADDR']){
            $ip = $_SERVER['REMOTE_ADDR'];
        }else if(getenv("REMOTE_ADDR")){
            $ip=getenv("REMOTE_ADDR");
        }

        return $ip;
    }

    //数组转xml数据
    protected function arrToXml($arr)
    {
        $str="";

        foreach ($arr as $key => $val){
            $str.="<{$key}>".$val."</{$key}>";
        }

        return "<xml>".$str."</xml>";
    }

    //xml转数组
    protected function xmlToArr($xml)
    {
        if(!$xml){
            return false;
        }

        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $data;
    }

    //数组生成url键值对形式(不转码)
    protected function arrToUrl($arr)
    {
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
    public function randStr($length=16)
    {
        $lib="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

        $str="";
        while ($length>0){
            $str.=$lib[mt_rand(0,strlen($lib)-1)];
            $length--;
        }

        return $str;
    }

    //curl 请求数据
     function request($url,$query=null,$isPost=false,$certPath=null,$keyPath=null)
     {
         if($query!==null && $isPost==false){
             if(is_array($query)){
                 $url.="?".http_build_query($query);
             }else{
                 $url.="?".$query;
             }
         }

         $ch=curl_init($url);

         if($isPost){
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
    public function getAccessToken()
    {
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;

        $res=json_decode($this->request($url),true);
        $ok=array_key_exists("errcode",$res);

        return $ok?false:$res["access_token"];
     }

     //获取微信服务器ip地址  return array("127.0.0.1","127.0.0.2",)
    public function wechatIpList($accessToken)
    {
        $url="https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=".$accessToken;

        $res=json_decode($this->request($url),true);
        $ok=array_key_exists("errcode",$res);

        return $ok?false:$res["ip_list"];
    }

    //获取jsapi_ticket(config签名使用)  7200s
    protected function getJsapiTicket($accessToken)
    {
        $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$accessToken."&type=jsapi";

        $res=json_decode($this->request($url));

        return $res->errcode==0?$res->ticket:false;
    }

    //生成JS-SDK 签名(config使用)
    public function configSign($jsapiTicket,$url)
    {
        $param=array(
            "noncestr"      =>  $this->randStr(),
            "jsapi_ticket"  =>  $jsapiTicket,
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
    public function getApiTicket($accessToken)
    {
        $url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$accessToken."&type=wx_card";

        $res=json_decode($this->request($url));

        return $res->errcode==0?$res->ticket:false;
    }

    //生成卡券签名
    public function cardSign($apiAccess)
    {

    }





}
