<?php

/**
 *    盛付通支付方式插件
 *
 *    @author    psmb
 *    @usage    none
 */

class AsiapayPayment extends BasePayment
{
    /* Asiapay 网关 */
    //var $_gateway   =   'https://www.paydollar.com/b2c2/eng/payment/payForm.jsp';
	
	var $_gateway = 'https://test.paydollar.com/b2cDemo/eng/payment/payForm.jsp';
    var $_code      =   'asiapay';
	
    /**
     *    获取支付表单
     *
     *    @author    psmb
     *    @param     array $order_info  待支付的订单信息，必须包含总费用及唯一外部交易号
     *    @return    array
     */
    function get_payform($order_info)
    {
        $params = array(
			//'merchantId'		=>  $this->_config['merchantId'],//手動輸入
			//'merchantId'		=>  '88583289 ',//正式商號
			'merchantId'		=>  '88109602',//測試商號
			'orderRef'			=>  $order_info['order_id'], //$this->_get_trade_sn($order_info),
			'currCode'			=>	$this->get_currCode($this->_config['asiapay_currency']),
			'amount'			=>	$order_info['order_amount'],
			'payType'			=>  'N',
			'mpsMode'			=>	'NIL',
			'payMethod'			=>  'ALL',
			'lang'				=>	$this->_config['asiapay_lang'],
			'successUrl'		=>  $this->_create_return_url($order_info['order_id']),
			'failUrl'			=> 	site_url(),
			'cancelUrl'			=>	site_url(),
			
			//'redirect'		=>  3,
			'remark'			=>	$this->_get_subject($order_info),
        );
		
		if($this->_config['secureHashSecret']) {
			$params['secureHash'] = $this->_get_sign($params);
		}
		
        return $this->_create_payform('POST', $params);
    }
	
	function get_currCode($code)
	{
		$array = array(
			'HKD' => 344,
			'USD' => 840,
			'SGD' => 702,
			'CNY' => 156,
			'JPY' => 392,
			'TWD' => 901,
			'AUD' => 036,
			'EUR' => 978,
			'GBP' => 826,
			'CAD' => 124
		);
		
		if(!empty($code) && isset($array[$code])) {
			return $array[$code];
		}
		return $array['CNY'];
	}


	/**
     *    获取签名字符串
     *
     *    @author    psmb
     *    @param     array $params
     *    @return    string
     */
	function _get_sign($params)
	{
		require_once('SHAPaydollarSecure.php');
		
		$paydollarSecure=new SHAPaydollarSecure(); 
		$secureHash=$paydollarSecure->generatePaymentSecureHash($params['merchantId'], $params['orderRef'], $params['currCode'], $params['amount'], $params['payType'], $this->_config['secureHashSecret']);

		return $secureHash;
	}

    /**
     *    返回通知结果
     *
     *    @author    psmb
     *    @param     array $order_info
     *    @param     bool  $strict
     *    @return    array
     */
    function verify_notify($order_info, $strict = false)
    {
        if (empty($order_info))
        {
            $this->_error('order_info_empty');

            return false;
        }

        /* 初始化所需数据 */
        $notify =   $this->_get_notify();

        /* 验证通知是否可信 */
        $sign_result = $this->_verify_sign($notify);

		if (!$sign_result)
        {
            /* 若本地签名与网关签名不一致，说明签名不可信 */
            $this->_error('sign_inconsistent');

            return false;
        }
		/*----------通知验证结束----------*/
		
		/*----------本地验证开始----------*/
        /* 验证与本地信息是否匹配 */

        if ($order_info['order_id'] != $notify['Ref'])
        {
            /* 通知中的订单与欲改变的订单不一致 */
            $this->_error('order_inconsistent');

            return false;
        }

        if ($order_info['order_amount'] != $notify['Amt'])
        {
            /* 支付的金额与实际金额不一致 */
            $this->_error('price_inconsistent');

            return false;
        }
        //至此，说明通知是可信的，订单也是对应的，可信的
		
		
		/* 按通知结果返回相应的结果 */
        switch ($notify['successcode'])
        {
            case '0':      //买家已付款，等待卖家发货

                $order_status = ORDER_ACCEPTED;
            break;
			
			default:
                $this->_error('undefined_status');
                return false;
            break;
        }

        return array(
            'target'    =>  $order_status,
        );
    }
	
    /**
     *    验证签名是否可信
     *
     *    @author    psmb
     *    @param     array $notify
     *    @return    bool
     */
    function _verify_sign($notify)
    {
		// 如果客户没有购买密钥，则不需要验证
		if(!$this->_config['secureHashSecret'])
		{
			return true; 
		}
		else
		{
			require_once('SHAPaydollarSecure.php');
		
			$secureHashs=explode(',', $notify['secureHash']);

			$paydollarSecure=new SHAPaydollarSecure();

			$verifyResult = false;

				while(list($key,$value)=each($secureHashs)){
			$verifyResult = $paydollarSecure->verifyPaymentDatafeed($notify['src'],$notify['prc'], $notify['successcode'], $notify['Ref'], $notify['PayRef'], $notify['Cur'], $notify['Amt'], $notify['payerAuth'],$notify['secureHashSecret'], $value);
			
				if($verifyResult) {
					return  true;
				}
				return false;
			}
		}
    }
	function _get_notify()
	{
		return $_POST;
	}
   
	function verify_result($result) 
    {
        if ($result)
        {
            echo 'OK';
        }
    }

}

?>