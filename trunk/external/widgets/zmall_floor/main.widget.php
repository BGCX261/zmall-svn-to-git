<?php

class Zmall_floorWidget extends BaseWidget
{
    var $_name = 'zmall_floor';

    function _get_data()
    {
		$gcategory_mod =& bm('gcategory');
        $gcategories = array();
        if(!empty($this->options['cate_id']))
        {
             $gcategorys = $gcategory_mod->get_children($this->options['cate_id']);
			 foreach($gcategorys as $key => $cate)
			 {
				$gcategorys[$key]['children'] = array();
				$gcategorys[$key]['children'] = $gcategory_mod->get_children($cate['cate_id']);
				foreach($gcategorys[$key]['children'] as $k => $v)
				{
					$gcategorys[$key]['children'][$k]['children'] = $gcategory_mod->get_children($v['cate_id']);
				}
			 }
         }
		 $ads=array();
		 for($i=1;$i<9;$i++)
		 {
			 $ads[$i]['ad_image_url']= $this->options['ad'.$i.'_image_url'];
 			 $ads[$i]['ad_link_url']= $this->options['ad'.$i.'_link_url'];
		 }
        $data= array(
		    'model_color'    => $this->options['model_color'],
			're_store'    => $this->options['re_store'],
			'gcategories' => $gcategorys,
			'ad0_image_url'  => $this->options['ad0_image_url'],
            'ads'  => $ads,
			'wid'  => md5($this->options['model_color']),
        );
		return $data;
    }
	function get_config_datasrc()
    {
        // 取得一级商品分类
        $this->assign('gcategories', $this->_get_gcategory_options(1));
    }
    function parse_config($input)
    {
        $images = $this->_upload_image();
        if ($images)
        {
            foreach ($images as $key => $image)
            {
                $input['ad' . $key . '_image_url'] = $image;
            }
        }

        return $input;
    }

    function _upload_image()
    {
        import('uploader.lib');
        $images = array();
        for ($i = 0; $i < 9; $i++)
        {
            $file = $_FILES['ad' . $i . '_image_file'];
            if ($file['error'] == UPLOAD_ERR_OK)
            {
                $uploader = new Uploader();
                $uploader->allowed_type(IMAGE_FILE_TYPE);
                $uploader->addFile($file);
                $uploader->root_dir(ROOT_PATH);
                $images[$i] = $uploader->save('data/files/mall/template', $uploader->random_filename());
            }
        }

        return $images;
    }
}

?>