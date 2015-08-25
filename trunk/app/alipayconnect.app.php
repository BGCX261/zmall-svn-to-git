<?php

class AlipayconnectApp extends MallbaseApp
{
	var $_bink_mod;
	var $_app;
	var $alipay_config = array();
	
    function __construct()
    {
        $this->AlipayconnectApp();
    }
    function AlipayconnectApp()
    {
        parent::__construct();
		$this->_bink_mod 	= &m('member_bind');
		$this->_app      	= 'alipay';
		
		$this->alipay_config = $this->_hook('on_alipay_login');
    }
    
    function bind()
	{
		if ($this->visitor->has_login)
        {
            $this->show_warning('has_login');

            return;
        }
		
		if(!$_SESSION['openid'])
		{
			$this->show_warning('SESSION�ѹ��ڣ������µ�¼���ٰ󶨡�');
			return;
		}
			
		if(!IS_POST)
		{
			$this->display('member.bind.html');
		}
		else
		{
			$user_name = trim($_POST['user_name']);
			$password  = trim($_POST['password']);
			$bind_type = intval($_POST['bind_type']);
			
			$passlen = strlen($password);
            $user_name_len = strlen($user_name);
            if ($user_name_len < 3 || $user_name_len > 25)
            {
                $this->show_warning('�û���������3-25��Ӣ���ַ�֮�䣡');

                return;
            }
            if ($passlen < 6 || $passlen > 20)
            {
                $this->show_warning('���������6-20���ַ�');

                return;
            }
			
			$ms =& ms(); //�����û�����
			
			// ע�����˺�
			if(!$bind_type)
			{
				$email  = trim($_POST['email']);
				
				if (!is_email($email))
            	{
                	$this->show_warning('�����ʼ���ַ��ʽ���ԡ�');

                	return;
            	}
				
				if (!$ms->user->check_email($email))
            	{
                	$this->show_warning('�����ʼ���ַ��Ψһ��');

                	return;
            	}
				
				// ����Ƿ��д��ڵ��û��������û��
				if($ms->user->check_username($user_name)){
					$user_id = $ms->user->register($user_name, $password, $email);
				}
				else
				{
					$this->show_warning('�û����Ѿ����ڣ���������д��');
					return;
				}
			}
			
			// �������˺�
			else
			{
				$user_id = $ms->user->auth($user_name, $password);
				
				if(!$user_id)
				{
					$this->show_warning('���󶨵��˺Ų����ڣ���������д��');
					return;
				}
				
			}

			//��¼
			$this->_do_login($user_id);
				
			/* ͬ����½�ⲿϵͳ */
			$synlogin = $ms->user->synlogin($user_id);
			
			// ������Ϣ�������ݿ�
			$data = array(
				'openid' => $_SESSION['openid'],
				'user_id'=> $user_id,
				'app'   => $this->_app,
			);
			
			// ��������а󶨣����޸�
			if($this->_bink_mod->get(array('conditions'=>'user_id='.$user_id.' and app="'.$this->_app.'"','fields'=>'openid')))
			{
				$data = array('openid' => $_SESSION['openid']);
				$this->_bink_mod->edit('user_id='.$user_id.' and app="'.$this->_app.'"', $data);
			} else {
				$this->_bink_mod->add($data);
			}
			unset($_SESSION['openid']);
			
			$this->show_message(Lang::get('login_successed') . $synlogin,
				'back_index',SITE_URL
			);
		}
		
	}
	
	function callback()
	{
		//����ó�֪ͨ��֤���
		$alipayNotify = new AlipayNotify($this->alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result) {//��֤�ɹ�
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//������������̻���ҵ���߼��������
	
			//�������������ҵ���߼�����д�������´�������ο�������
    		//��ȡ֧������֪ͨ���ز������ɲο������ĵ���ҳ����תͬ��֪ͨ�����б�

			//֧�����û���

			$openid = $_GET['user_id'];

			//��Ȩ����
			$token = $_GET['token'];
			
			if($mb = $this->_bink_mod->get(array('conditions'=>"openid='".$openid."' AND app='".$this->_app."'")))
			{
				// �����openid�Ѿ��󶨣���ִ�е�¼
				$this->_do_login($mb['user_id']);
				
				/* ͬ����½�ⲿϵͳ */
				$ms =& ms();
				$synlogin = $ms->user->synlogin($mb['user_id']);
				$this->show_message(Lang::get('login_successed') . $synlogin,'back_index',SITE_URL);
			}
			else
			{
				$_SESSION['openid'] = $openid;
				header('location:index.php?app=alipayconnect&act=bind');
			}
		}
		else {
    		//��֤ʧ��
    		//��Ҫ���ԣ��뿴alipay_notify.phpҳ���verifyReturn����
    		$this->show_warning('��֤ʧ��');
			return;
		}

	}
	function login()
	{
		$alipay_config = $this->alipay_config;
		
		/**************************�������**************************/

        //Ŀ������ַ
        $target_service = "user.auth.quick.login";
        //����
        

        //������ʱ���
        $anti_phishing_key = "";
        //��Ҫʹ����������ļ�submit�е�query_timestamp����

        //�ͻ��˵�IP��ַ
        $exter_invoke_ip = "";
        //�Ǿ�����������IP��ַ���磺221.0.0.1


		/************************************************************/

		//����Ҫ����Ĳ������飬����Ķ�
		$parameter = array(
			"service" => "alipay.auth.authorize",
			"partner" => trim($alipay_config['partner']),
			"target_service"	=> $target_service,
			"return_url"	=> $alipay_config['return_url'],
			"anti_phishing_key"	=> $anti_phishing_key,
			"exter_invoke_ip"	=> $exter_invoke_ip,
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
		);

		//��������
		$alipaySubmit = new AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "loading...");
		echo $html_text;
	}
}

/* *
 * ֧�����ӿڹ��ú���
 * ��ϸ������������֪ͨ���������ļ������õĹ��ú������Ĵ����ļ�
 * �汾��3.3
 * ���ڣ�2012-07-19
 * ˵����
 * ���´���ֻ��Ϊ�˷����̻����Զ��ṩ���������룬�̻����Ը����Լ���վ����Ҫ�����ռ����ĵ���д,����һ��Ҫʹ�øô��롣
 * �ô������ѧϰ���о�֧�����ӿ�ʹ�ã�ֻ���ṩһ���ο���
 */

/**
 * ����������Ԫ�أ����ա�����=����ֵ����ģʽ�á�&���ַ�ƴ�ӳ��ַ���
 * @param $para ��Ҫƴ�ӵ�����
 * return ƴ������Ժ���ַ���
 */
function createLinkstring($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".$val."&";
	}
	//ȥ�����һ��&�ַ�
	$arg = substr($arg,0,count($arg)-2);
	
	//�������ת���ַ�����ôȥ��ת��
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
	return $arg;
}
/**
 * ����������Ԫ�أ����ա�����=����ֵ����ģʽ�á�&���ַ�ƴ�ӳ��ַ����������ַ�����urlencode����
 * @param $para ��Ҫƴ�ӵ�����
 * return ƴ������Ժ���ַ���
 */
function createLinkstringUrlencode($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".urlencode($val)."&";
	}
	//ȥ�����һ��&�ַ�
	$arg = substr($arg,0,count($arg)-2);
	
	//�������ת���ַ�����ôȥ��ת��
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
	return $arg;
}
/**
 * ��ȥ�����еĿ�ֵ��ǩ������
 * @param $para ǩ��������
 * return ȥ����ֵ��ǩ�����������ǩ��������
 */
function paraFilter($para) {
	$para_filter = array();
	while (list ($key, $val) = each ($para)) {
		if($key == "sign" || $key == "sign_type" || $val == "")continue;
		else	$para_filter[$key] = $para[$key];
	}
	return $para_filter;
}
/**
 * ����������
 * @param $para ����ǰ������
 * return ����������
 */
function argSort($para) {
	ksort($para);
	reset($para);
	return $para;
}
/**
 * д��־��������ԣ�����վ����Ҳ���ԸĳɰѼ�¼�������ݿ⣩
 * ע�⣺��������Ҫ��ͨfopen����
 * @param $word Ҫд����־����ı����� Ĭ��ֵ����ֵ
 */
function logResult($word='') {
	$fp = fopen("log.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"ִ�����ڣ�".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

/**
 * Զ�̻�ȡ���ݣ�POSTģʽ
 * ע�⣺
 * 1.ʹ��Crul��Ҫ�޸ķ�������php.ini�ļ������ã��ҵ�php_curl.dllȥ��ǰ���";"������
 * 2.�ļ�����cacert.pem��SSL֤���뱣֤��·����Ч��ĿǰĬ��·���ǣ�getcwd().'\\cacert.pem'
 * @param $url ָ��URL����·����ַ
 * @param $cacert_url ָ����ǰ����Ŀ¼����·��
 * @param $para ���������
 * @param $input_charset �����ʽ��Ĭ��ֵ����ֵ
 * return Զ�����������
 */
function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

	if (trim($input_charset) != '') {
		$url = $url."_input_charset=".$input_charset;
	}
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL֤����֤
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//�ϸ���֤
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//֤���ַ
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // ����HTTPͷ
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// ��ʾ������
	curl_setopt($curl,CURLOPT_POST,true); // post��������
	curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post��������
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//���ִ��curl�����г����쳣���ɴ򿪴˿��أ��Ա�鿴�쳣����
	curl_close($curl);
	
	return $responseText;
}

/**
 * Զ�̻�ȡ���ݣ�GETģʽ
 * ע�⣺
 * 1.ʹ��Crul��Ҫ�޸ķ�������php.ini�ļ������ã��ҵ�php_curl.dllȥ��ǰ���";"������
 * 2.�ļ�����cacert.pem��SSL֤���뱣֤��·����Ч��ĿǰĬ��·���ǣ�getcwd().'\\cacert.pem'
 * @param $url ָ��URL����·����ַ
 * @param $cacert_url ָ����ǰ����Ŀ¼����·��
 * return Զ�����������
 */
function getHttpResponseGET($url,$cacert_url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // ����HTTPͷ
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// ��ʾ������
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL֤����֤
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//�ϸ���֤
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//֤���ַ
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//���ִ��curl�����г����쳣���ɴ򿪴˿��أ��Ա�鿴�쳣����
	curl_close($curl);
	
	return $responseText;
}

/**
 * ʵ�ֶ����ַ����뷽ʽ
 * @param $input ��Ҫ������ַ���
 * @param $_output_charset ����ı����ʽ
 * @param $_input_charset ����ı����ʽ
 * return �������ַ���
 */
function charsetEncode($input,$_output_charset ,$_input_charset) {
	$output = "";
	if(!isset($_output_charset) )$_output_charset  = $_input_charset;
	if($_input_charset == $_output_charset || $input ==null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset change.");
	return $output;
}
/**
 * ʵ�ֶ����ַ����뷽ʽ
 * @param $input ��Ҫ������ַ���
 * @param $_output_charset ����Ľ����ʽ
 * @param $_input_charset ����Ľ����ʽ
 * return �������ַ���
 */
function charsetDecode($input,$_input_charset ,$_output_charset) {
	$output = "";
	if(!isset($_input_charset) )$_input_charset  = $_input_charset ;
	if($_input_charset == $_output_charset || $input ==null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset changes.");
	return $output;
}


/* *
 * MD5
 * ��ϸ��MD5����
 * �汾��3.3
 * ���ڣ�2012-07-19
 * ˵����
 * ���´���ֻ��Ϊ�˷����̻����Զ��ṩ���������룬�̻����Ը����Լ���վ����Ҫ�����ռ����ĵ���д,����һ��Ҫʹ�øô��롣
 * �ô������ѧϰ���о�֧�����ӿ�ʹ�ã�ֻ���ṩһ���ο���
 */

/**
 * ǩ���ַ���
 * @param $prestr ��Ҫǩ�����ַ���
 * @param $key ˽Կ
 * return ǩ�����
 */
function md5Sign($prestr, $key) {
	$prestr = $prestr . $key;
	return md5($prestr);
}

/**
 * ��֤ǩ��
 * @param $prestr ��Ҫǩ�����ַ���
 * @param $sign ǩ�����
 * @param $key ˽Կ
 * return ǩ�����
 */
function md5Verify($prestr, $sign, $key) {
	$prestr = $prestr . $key;
	$mysgin = md5($prestr);

	if($mysgin == $sign) {
		return true;
	}
	else {
		return false;
	}
}

/* *
 * ������AlipaySubmit
 * ���ܣ�֧�������ӿ������ύ��
 * ��ϸ������֧�������ӿڱ�HTML�ı�����ȡԶ��HTTP����
 * �汾��3.3
 * ���ڣ�2012-07-23
 * ˵����
 * ���´���ֻ��Ϊ�˷����̻����Զ��ṩ���������룬�̻����Ը����Լ���վ����Ҫ�����ռ����ĵ���д,����һ��Ҫʹ�øô��롣
 * �ô������ѧϰ���о�֧�����ӿ�ʹ�ã�ֻ���ṩһ���ο���
 */
//require_once("alipay_core.function.php");
//require_once("alipay_md5.function.php");

class AlipaySubmit {

	var $alipay_config;
	/**
	 *֧�������ص�ַ���£�
	 */
	var $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';

	function __construct($alipay_config){
		$this->alipay_config = $alipay_config;
	}
    function AlipaySubmit($alipay_config) {
    	$this->__construct($alipay_config);
    }
	
	/**
	 * ����ǩ�����
	 * @param $para_sort ������Ҫǩ��������
	 * return ǩ������ַ���
	 */
	function buildRequestMysign($para_sort) {
		//����������Ԫ�أ����ա�����=����ֵ����ģʽ�á�&���ַ�ƴ�ӳ��ַ���
		$prestr = createLinkstring($para_sort);
		
		$mysign = "";
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "MD5" :
				$mysign = md5Sign($prestr, $this->alipay_config['key']);
				break;
			default :
				$mysign = "";
		}
		
		return $mysign;
	}

	/**
     * ����Ҫ�����֧�����Ĳ�������
     * @param $para_temp ����ǰ�Ĳ�������
     * @return Ҫ����Ĳ�������
     */
	function buildRequestPara($para_temp) {
		//��ȥ��ǩ�����������еĿ�ֵ��ǩ������
		$para_filter = paraFilter($para_temp);

		//�Դ�ǩ��������������
		$para_sort = argSort($para_filter);

		//����ǩ�����
		$mysign = $this->buildRequestMysign($para_sort);
		
		//ǩ�������ǩ����ʽ���������ύ��������
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
		
		return $para_sort;
	}

	/**
     * ����Ҫ�����֧�����Ĳ�������
     * @param $para_temp ����ǰ�Ĳ�������
     * @return Ҫ����Ĳ��������ַ���
     */
	function buildRequestParaToString($para_temp) {
		//�������������
		$para = $this->buildRequestPara($para_temp);
		
		//�Ѳ�����������Ԫ�أ����ա�����=����ֵ����ģʽ�á�&���ַ�ƴ�ӳ��ַ����������ַ�����urlencode����
		$request_data = createLinkstringUrlencode($para);
		
		return $request_data;
	}
	
    /**
     * ���������Ա�HTML��ʽ���죨Ĭ�ϣ�
     * @param $para_temp �����������
     * @param $method �ύ��ʽ������ֵ��ѡ��post��get
     * @param $button_name ȷ�ϰ�ť��ʾ����
     * @return �ύ��HTML�ı�
     */
	function buildRequestForm($para_temp, $method, $button_name) {
		//�������������
		$para = $this->buildRequestPara($para_temp);
		
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".trim(strtolower($this->alipay_config['input_charset']))."' method='".$method."'>";
		while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }

		//submit��ť�ؼ��벻Ҫ����name����
        $sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";
		
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		
		return $sHtml;
	}
	
	/**
     * ����������ģ��Զ��HTTP��POST����ʽ���첢��ȡ֧�����Ĵ�����
     * @param $para_temp �����������
     * @return ֧����������
     */
	function buildRequestHttp($para_temp) {
		$sResult = '';
		
		//��������������ַ���
		$request_data = $this->buildRequestPara($para_temp);

		//Զ�̻�ȡ����
		$sResult = getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'],$request_data,trim(strtolower($this->alipay_config['input_charset'])));

		return $sResult;
	}
	
	/**
     * ����������ģ��Զ��HTTP��POST����ʽ���첢��ȡ֧�����Ĵ����������ļ��ϴ�����
     * @param $para_temp �����������
     * @param $file_para_name �ļ����͵Ĳ�����
     * @param $file_name �ļ���������·��
     * @return ֧�������ش�����
     */
	function buildRequestHttpInFile($para_temp, $file_para_name, $file_name) {
		
		//�������������
		$para = $this->buildRequestPara($para_temp);
		$para[$file_para_name] = "@".$file_name;
		
		//Զ�̻�ȡ����
		$sResult = getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'],$para,trim(strtolower($this->alipay_config['input_charset'])));

		return $sResult;
	}
	
	/**
     * ���ڷ����㣬���ýӿ�query_timestamp����ȡʱ����Ĵ�����
	 * ע�⣺�ù���PHP5����������֧�֣���˱�������������ص�����װ��֧��DOMDocument��SSL��PHP���û��������鱾�ص���ʱʹ��PHP�������
     * return ʱ����ַ���
	 */
	function query_timestamp() {
		$url = $this->alipay_gateway_new."service=query_timestamp&partner=".trim(strtolower($this->alipay_config['partner']));
		$encrypt_key = "";		

		$doc = new DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName( "encrypt_key" );
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;
		
		return $encrypt_key;
	}
}


class AlipayNotify {
    /**
     * HTTPS��ʽ��Ϣ��֤��ַ
     */
	var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	/**
     * HTTP��ʽ��Ϣ��֤��ַ
     */
	var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
	var $alipay_config;

	function __construct($alipay_config){
		$this->alipay_config = $alipay_config;
	}
    function AlipayNotify($alipay_config) {
    	$this->__construct($alipay_config);
    }
    /**
     * ���notify_url��֤��Ϣ�Ƿ���֧���������ĺϷ���Ϣ
     * @return ��֤���
     */
	function verifyNotify(){
		if(empty($_POST)) {//�ж�POST���������Ƿ�Ϊ��
			return false;
		}
		else {
			//����ǩ�����
			$isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
			//��ȡ֧����Զ�̷�����ATN�������֤�Ƿ���֧������������Ϣ��
			$responseTxt = 'true';
			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}
			
			//д��־��¼
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_POST);
			//logResult($log_text);
			
			//��֤
			//$responsetTxt�Ľ������true����������������⡢���������ID��notify_idһ����ʧЧ�й�
			//isSign�Ľ������true���밲ȫУ���롢����ʱ�Ĳ�����ʽ���磺���Զ�������ȣ��������ʽ�й�
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	
    /**
     * ���return_url��֤��Ϣ�Ƿ���֧���������ĺϷ���Ϣ
     * @return ��֤���
     */
	function verifyReturn(){
		if(empty($_GET)) {//�ж�POST���������Ƿ�Ϊ��
			return false;
		}
		else { 
			
			/* ȥ��������ǩ�������� */
        	unset($_GET['app'], $_GET['act']);
			
			//����ǩ�����
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);
			//��ȡ֧����Զ�̷�����ATN�������֤�Ƿ���֧������������Ϣ��
			$responseTxt = 'true';
			if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}
			
			//д��־��¼
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n return_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_GET);
			//logResult($log_text);

			//��֤
			//$responsetTxt�Ľ������true����������������⡢���������ID��notify_idһ����ʧЧ�й�
			//isSign�Ľ������true���밲ȫУ���롢����ʱ�Ĳ�����ʽ���磺���Զ�������ȣ��������ʽ�й�
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	
    /**
     * ��ȡ����ʱ��ǩ����֤���
     * @param $para_temp ֪ͨ�������Ĳ�������
     * @param $sign ���ص�ǩ�����
     * @return ǩ����֤���
     */
	function getSignVeryfy($para_temp, $sign) {
		//��ȥ��ǩ�����������еĿ�ֵ��ǩ������
		$para_filter = paraFilter($para_temp);

		//�Դ�ǩ��������������
		$para_sort = argSort($para_filter);
		
		//����������Ԫ�أ����ա�����=����ֵ����ģʽ�á�&���ַ�ƴ�ӳ��ַ���
		$prestr = createLinkstring($para_sort);
		
		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "MD5" :
				$isSgin = md5Verify($prestr, $sign, $this->alipay_config['key']);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}

    /**
     * ��ȡԶ�̷�����ATN���,��֤����URL
     * @param $notify_id ֪ͨУ��ID
     * @return ������ATN���
     * ��֤�������
     * invalid����������� ��������������ⷵ�ش�����partner��key�Ƿ�Ϊ�� 
     * true ������ȷ��Ϣ
     * false �������ǽ�����Ƿ�������ֹ�˿������Լ���֤ʱ���Ƿ񳬹�һ����
     */
	function getResponse($notify_id) {
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = $this->https_verify_url;
		}
		else {
			$veryfy_url = $this->http_verify_url;
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);
		
		return $responseTxt;
	}
}

?>