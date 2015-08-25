<?php
class Zeuz_floor_listWidget extends BaseWidget
{
    var $_name = 'zeuz_floor_list';
    var $_ttl  = 1800;

    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$num=$this->options['num'] ? $this->options['num'] : 6;
            $recom_mod =& m('recommend');
            $data = $recom_mod->get_recommended_goods($this->options['recom_id'], $num, true, $this->options['cate_id']);
            $cache_server->set($key,$data, $this->_ttl);
        }
        return array(
            'model_name'       => $this->options['model_name'],
            'goods_list'    => $data,
        );
    }

    function get_config_datasrc()
    {
        // 取得推荐类型
        $this->assign('recommends', $this->_get_recommends());

        // 取得一级商品分类
        $this->assign('gcategories', $this->_get_gcategory_options(1));
    }

    function parse_config($input)
    {
        $filename = $this->_upload_image();
        if ($filename)
        {
            $input['ad_image_url'] = $filename;
        }

        if ($input['img_recom_id'] >= 0)
        {
            $input['img_cate_id'] = 0;
        }
        if ($input['txt_recom_id'] >= 0)
        {
            $input['txt_cate_id'] = 0;
        }

        return $input;
    }

    function _upload_image()
    {
        import('uploader.lib');
        $file = $_FILES['ad_image_file'];
        if ($file['error'] == UPLOAD_ERR_OK)
        {
            $uploader = new Uploader();
            $uploader->allowed_type(IMAGE_FILE_TYPE);
            $uploader->addFile($file);
            $uploader->root_dir(ROOT_PATH);

            return $uploader->save('data/files/mall/template', $uploader->random_filename());
        }

        return '';
    }
}

?>