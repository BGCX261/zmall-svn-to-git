<?php

/**
 * 品牌挂件
 *
 * @return  array
 */
class Zeuz_index_brandWidget extends BaseWidget
{
    var $_name = 'zeuz_index_brand';
    var $_ttl  = 86400;
    var $_num ;

    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$this->_num=$this->options['num']?$this->options['num']:16;
            $brand_mod =& m('brand');
            $data = $brand_mod->find(array(
                'conditions' => "recommended = 1",
                'order' => 'sort_order',
                'limit' => $this->_num,
            ));
            $cache_server->set($key, $data, $this->_ttl);
        }

        return $data;
    }
}

?>