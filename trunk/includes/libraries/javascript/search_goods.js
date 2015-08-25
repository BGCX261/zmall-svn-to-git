$(function(){
    /* 显示全部分类 */
    $("#show_category").click(function(){
        $("ul[ectype='ul_category'] li").show();
        $(this).hide();
    });

    /* 显示全部品牌 */
    $("#show_brand").click(function(){
        $("ul[ectype='ul_brand'] li").show();
        $(this).hide();
    });

    /* 自定义价格区间 */
    $("#set_price_interval").click(function(){
        $("ul[ectype='ul_price'] li").show();
        $(this).hide();
    });

    /* 显示全部地区 */
    $("#show_region").click(function(){
        $("ul[ectype='ul_region'] li").show();
        $(this).hide();
    });

    /* 筛选事件 */
    $("ul[ectype='ul_category'] a").click(function(){
        replaceParam('cate_id', this.id);
        return false;
    });
    $("ul[ectype='ul_brand'] a").click(function(){
        replaceParam('brand', this.id);
        return false;
    });
    $("ul[ectype='ul_price'] a").click(function(){
        replaceParam('price', this.title);
        return false;
    });
    $("#search_by_price").click(function(){
        replaceParam('price', $(this).siblings("input:first").val() + '-' + $(this).siblings("input:last").val());
        return false;
    });
    $("ul[ectype='ul_region'] a").click(function(){
        replaceParam('region_id', this.id);
        return false;
    });
    $("li[ectype='li_filter'] img").click(function(){
        dropParam(this.title);
        return false;
    });
	
	/* sku psmb */
	$("div[ectype='dl_props'] a").click(function(){
		id = $(this).attr('selected_props')+this.id;
		replaceParam('props',id);
		return false;
	});
	$("div[ectype='attribute'] a").click(function(){
		dropParam(this.title);
		return false;
	});
	
    $("[ectype='order_by']").change(function(){
        replaceParam('order', this.value);
        return false;
    });

    /* 下拉过滤器 */
    $("li[ectype='dropdown_filter_title'] a").click(function(){
        var jq_li = $(this).parents("li[ectype='dropdown_filter_title']");
        var status = jq_li.find("img").attr("src") == upimg ? 'off' : 'on';
        switch_filter(jq_li.attr("ecvalue"), status)
    });

    /* 显示方式 */
    $("[ectype='display_mode']").click(function(){
        $("[ectype='current_display_mode']").attr('class', $(this).attr('ecvalue'));
        $.setCookie('goodsDisplayMode', $(this).attr('ecvalue'));
    });
	
	
	
	//by psmoban.com
	 /* 筛选事件 */
    $("ul li[ectype='category'] input").change(function(){
        replaceParam('cate_id', $(this).val());
        return false;
    });
	
	/* sku psmb */
	$("ul li[ectype='dl_props'] input").click(function(){
		id = $(this).attr('selected_props')+this.id;
		replaceParam('props',id);
		return false;
	});
	
	
});

/** 打开/关闭过滤器
 *  参数 filter 过滤器   brand | price | region
 *  参数 status 目标状态 on | off
 */
function switch_filter(filter, status)
{
    $("li[ectype='dropdown_filter_title']").attr('class', 'normal');
    $("li[ectype='dropdown_filter_title'] img").attr('src', downimg);
    $("div[ectype='dropdown_filter_content']").hide();

    if (status == 'on')
    {
        $("li[ectype='dropdown_filter_title'][ecvalue='" + filter + "']").attr('class', 'active');
        $("li[ectype='dropdown_filter_title'][ecvalue='" + filter + "'] img").attr('src', upimg);
        $("div[ectype='dropdown_filter_content'][ecvalue='" + filter + "']").show();
    }
}

/* 替换参数 */
function replaceParam(key, value)
{
    var params = location.search.substr(1).split('&');
    var found  = false;
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
        if (pKey == key)
        {
            params[i] = key + '=' + value;
            found = true;
        }
    }
    if (!found)
    {
        value = transform_char(value);
        params.push(key + '=' + value);
    }
    location.assign(SITE_URL + '/index.php?' + params.join('&'));
}

/* 删除参数 */
function dropParam(key)
{
    var params = location.search.substr(1).split('&');
    for (var i = 0; i < params.length; i++)
    {
        param = params[i];
        arr   = param.split('=');
        pKey  = arr[0];
        if (pKey == 'page')
        {
            params[i] = 'page=1';
        }
		<!-- sku psmb -->
		if (pKey == 'props' || pKey == 'brand')
		{
			//alert(arr[1].indexOf(key));
			//params[i] = '6:5;20:41;';
			//alert(key);
			arr1 = arr[1];
			arr1 = arr1.replace(key,'');
			arr1 = arr1.replace(";;",';');
			if(arr1.substr(0,1)==";") {
				arr1 = arr1.substr(1,arr1.length-1);
				//alert('ddd');
			}
			//alert(arr1);
			if(arr1.substr(arr1.length-1,1) == ";") {
				arr1 = arr1.substr(0,arr1.length-1);
			}
			params[i]=pKey + "=" + arr1;
			
			//alert(params[i]);
		}
        if (pKey == key || params[i]=='props=' || params[i]=='brand=')
        {
            params.splice(i, 1);
        }
		<!-- end sku -->
    }
    location.assign(SITE_URL + '/index.php?' + params.join('&'));
}
