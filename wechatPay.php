<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2017/12/18
 * Time: 11:20
 */

require_once "./wechat.php";

class WechatPay extends WeChat{
    //商户号
    protected $mchId;
    //商户密钥
    protected $key;

    public function __construct($appId, $appSecret,$mchId,$key)
    {
        $this->appId=$appId;
        $this->appSecret=$appSecret;
        $this->mchId=$mchId;
        $this->key=$key;
    }

    //统一下单
    public function unifiedOrder($body,$out_trade_no,$total_fee,$notify_url,$trade_type,$openid=null){
        $parameter=array();
        $parameter["appid"]=$this->appId;
        $parameter["mch_id"]=$this->mchId;
        $parameter["nonce_str"]=$this->randStr(32);
        $parameter["sign_type"]="MD5";
        $parameter["body"]=$body;
        $parameter["out_trade_no"]=$out_trade_no;
        $parameter["total_fee"]=$total_fee;
        $parameter["notify_url"]=$notify_url;
        $parameter["trade_type"]=$trade_type;

        if($openid!==null){
            $parameter["openid"]=$openid;
        }
        //签名
        $sign=$this->paySign($parameter);
        $parameter["sign"]=$sign;

        $xml=$this->arrToXml($parameter);

        $url="https://api.mch.weixin.qq.com/pay/unifiedorder";
        $result=$this->http_request($url,$xml,true);

        if(!$result){
            return false;
        }
        $data=$this->xmlToArr($result);

        return $data;
    }

    //生成签名
    protected function paySign($parameter){
        //字典排序
        ksort($parameter);
        //拼接键值对
        $str=$this->arrToUrl($parameter);

        return strtoupper(md5($str."&key=".$this->key));
    }

}