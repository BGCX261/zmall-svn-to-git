<?php

return array(
    'code'      => 'asiapay',
    'name'      => Lang::get('asiapay'),
    'desc'      => Lang::get('asiapay_desc'),
    'is_online' => '1',
    'author'    => 'PSMB TEAM',
    'website'   => 'http://www.psmoban.com',
    'version'   => '1.0',
    'currency'  => Lang::get('asiapay_currency'),
	

	'config'    => array(
		/*
		'merchantId'   => array(        // 商户号ID
            'text'  => Lang::get('merchantId'),
			'desc'  => Lang::get('merchantId_desc'),
            'type'  => 'text',
			'configdisbale' => true,
        ),
		*/
		
		'secureHashSecret' => array(
			'text'  => Lang::get('secureHashSecret'),
			'desc'  => Lang::get('secureHashSecret_desc'),
            'type'  => 'text',
			'configdisbale' => true,
		),
		
		'asiapay_currency'  => array(
            'text' => Lang::get('currency'),
            'desc' => Lang::get('currency_desc'),
            'type' => 'select',
            'items' => array(
                'HKD' => Lang::get('HKD'),
				/*
				'CNY' => Lang::get('CNY'),
				'USD' => Lang::get('USD'),
				'EUR' => Lang::get('EUR'),
				'SGD' => Lang::get('SGD'),
				'HKD' => Lang::get('HKD'),
				'TWD' => Lang::get('TWD'),
    			'AUD' => Lang::get('AUD'),
    			'CAD' => Lang::get('CAD'),
    			'EUR' => Lang::get('EUR'),
    			'GBP' => Lang::get('GBP'),
    			'JPY' => Lang::get('JPY'),
				*/
            ),
        ),
		'asiapay_lang'  => array(
            'text' => Lang::get('lang'),
            'desc' => Lang::get('lang_desc'),
            'type' => 'select',
            'items' => array(
				'X' => Lang::get('X'),
                'C' => Lang::get('C'),
				'E' => Lang::get('E'),
				'K' => Lang::get('K'),
				'J' => Lang::get('J'),
				'T' => Lang::get('T'),
            ),
        ),
    ),
);

?>