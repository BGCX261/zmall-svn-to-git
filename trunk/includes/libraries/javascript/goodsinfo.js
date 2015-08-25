/* spec对象 */
function spec(id, spec1, spec2, price,pro_price, stock, is_pro,discount) //  add pro_price by psmb 
{
    this.id    = id;
    this.spec1 = spec1;
    this.spec2 = spec2;
    this.price = price;
	this.pro_price = pro_price; // add by psmb 
    this.stock = stock;
	this.is_pro = is_pro;
	this.discount=discount;
}

/* goodsspec对象 */
function goodsspec(specs, specQty, defSpec)
{
    this.specs = specs;
    this.specQty = specQty;
    this.defSpec = defSpec;
    this.spec1 = null;
    this.spec2 = null;
    if (this.specQty >= 1)
    {
        for(var i = 0; i < this.specs.length; i++)
        {
            if (this.specs[i].id == this.defSpec)
            {
                this.spec1 = this.specs[i].spec1;
                if (this.specQty >= 2)
                {
                    this.spec2 = this.specs[i].spec2;
                }
                break;
            }
        }
    }

    // 取得某字段的不重复值，如果有spec1，以此为条件
    this.getDistinctValues = function(field, spec1)
    {
        var values = new Array();
        for (var i = 0; i < this.specs.length; i++)
        {
            var value = this.specs[i][field];
            if (spec1 != '' && spec1 != this.specs[i].spec1) continue;
            if ($.inArray(value, values) < 0)
            {
                values.push(value);
            }
        }
        return (values);
    }

    // 取得选中的spec
    this.getSpec = function()
    {
        for (var i = 0; i < this.specs.length; i++)
        {
            if (this.specQty >= 1 && this.specs[i].spec1 != this.spec1) continue;
            if (this.specQty >= 2 && this.specs[i].spec2 != this.spec2) continue;

            return this.specs[i];
        }
        return null;
    }

    // 初始化
    this.init = function()
    {
        if (this.specQty >= 1)
        {
            var spec1Values = this.getDistinctValues('spec1', '');
            for (var i = 0; i < spec1Values.length; i++)
            {
                if (spec1Values[i] == this.spec1)
                {
                    $(".handle ul:eq(0)").append("<li class='solid' onclick='selectSpec(1, this)'>" + spec1Values[i] + "</li>");
                }
                else
                {
                    $(".handle ul:eq(0)").append("<li class='dotted' onclick='selectSpec(1, this)'>" + spec1Values[i] + "</li>");
                }
            }
        }
        if (this.specQty >= 2)
        {
            var spec2Values = this.getDistinctValues('spec2', this.spec1);
            for (var i = 0; i < spec2Values.length; i++)
            {
                if (spec2Values[i] == this.spec2)
                {
                    $(".handle ul:eq(1)").append("<li class='solid' onclick='selectSpec(2, this)'>" + spec2Values[i] + "</li>");
                }
                else
                {
                    $(".handle ul:eq(1)").append("<li class='dotted' onclick='selectSpec(2, this)'>" + spec2Values[i] + "</li>");
                }
            }
        }
        var spec = this.getSpec();
        $("[ectype='current_spec']").html(spec.spec1 + ' ' + spec.spec2);
    }
}

/* 选中某规格 num=1,2 */
function selectSpec(num, liObj)
{
    goodsspec['spec' + num] = $(liObj).html();
    $(liObj).attr("class", "solid");
    $(liObj).siblings(".solid").attr("class", "dotted");

    // 当有2种规格并且选中了第一个规格时，刷新第二个规格
    if (num == 1 && goodsspec.specQty == 2)
    {
        goodsspec.spec2 = null;
        $(".aggregate").html("");
        $(".handle ul:eq(1) li[class='handle_title']").siblings().remove();

        var spec2Values = goodsspec.getDistinctValues('spec2', goodsspec.spec1);
        for (var i = 0; i < spec2Values.length; i++)
        {
            $(".handle ul:eq(1)").append("<li class='dotted' onclick='selectSpec(2, this)'>" + spec2Values[i] + "</li>");
        }
    }
    else
    {
        var spec = goodsspec.getSpec();
        if (spec != null)
        {
            //  edit  by  psmb 
            $("[ectype='current_spec']").html(spec.spec1 + ' ' + spec.spec2);
			if(spec.is_pro) {
				$("#is_pro").css('display','block');
				$("#not_pro").css('display','none');
				$("[ectype='goods_price']").html('<del class="price-del">'+price_format(spec.price)+'</del>');
				$("[ectype='goods_pro_price']").html(price_format(spec.pro_price));
			}else {
				$("[ectype='goods_price']").html(price_format(spec.price));
				$("#is_pro").css('display','none');
				$("#not_pro").css('display','block');
					if(spec.discount < 1 && spec.discount > 0){
	    		$("[ectype='member_price']").html(price_format(spec.price*spec.discount));
	   	 	}
			}
			// end psmb 
			
            $("[ectype='goods_stock']").html(spec.stock);
        }
    }
}
function slideUp_fn()
{
    $('.ware_cen').slideUp('slow');
}
$(function(){
    goodsspec.init();

    //放大镜效果/
    if ($(".jqzoom img").attr('jqimg'))
    {
        $(".jqzoom").jqueryzoom({ xzoom: 430, yzoom: 300 });
    }

    // 图片替换效果
    $('.ware_box li').mouseover(function(){
        $('.ware_box li').removeClass();
        $(this).addClass('ware_pic_hover');
        $('.big_pic img').attr('src', $(this).children('img:first').attr('src'));
        $('.big_pic img').attr('jqimg', $(this).attr('bigimg'));
    });

    //点击后移动的距离
    var left_num = -61;

    //整个ul超出显示区域的尺寸
    var li_length = ($('.ware_box li').width() + 6) * $('.ware_box li').length - 305;

    $('.right_btn').click(function(){
        var posleft_num = $('.ware_box ul').position().left;
        if($('.ware_box ul').position().left > -li_length){
            $('.ware_box ul').css({'left': posleft_num + left_num});
        }
    });

    $('.left_btn').click(function(){
        var posleft_num = $('.ware_box ul').position().left;
        if($('.ware_box ul').position().left < 0){
            $('.ware_box ul').css({'left': posleft_num - left_num});
        }
    });

    // 加入购物车弹出层
    $('.close_btn').click(function(){
        $('.ware_cen').slideUp('slow');
    });
	
	// tyioocom delivery 
	$('.postage-cont').hover(function(){
		$(this).find('.postage-area').show();
	},function(){
		$(this).find('.postage-area').hide();
	});
	$('.province a').click(function(){
		$('.cities').find('div').hide();
		$('.cities .city_'+this.id).show();		
		$('.cities .city_'+this.id).before('<div style="color:#f00;">请选择以下详细区域</div>');
		$('.province').find('a').attr('class','');
		$(this).attr('class','selected');
	});
	$('.cities a').click(function(){
		$('.cities').find('a').attr('class','');
		$(this).attr('class','selected');
						
		delivery_template_id = $(this).attr('delivery_template_id');
		city_id 	= $(this).attr('city_id');
		store_id    = $(this).attr('store_id');
		goods_id    = $(this).attr('goods_id');
			
		//  加载指定城市的运费
		load_city_logist(delivery_template_id,store_id,goods_id,city_id); //传递 store_id,是为了在delivery_templaet_id 为0 的情况下，获取店铺的默认运费模板
	});

});

//  加载城市的运费(指定城市id或者根据ip自动判断城市id)
function load_city_logist(delivery_template_id,store_id,goods_id,city_id)
{
	html = '';
	if(city_id==undefined) {
		city_id = '';
	}
	var url = SITE_URL + '/index.php?app=logist&delivery_template_id='+delivery_template_id+'&store_id='+store_id+'&goods_id='+goods_id+'&city_id='+city_id;
		$.getJSON(url,function(data){
			if (data.done){
				data = data.retval;
				$('.postage-area').hide();
				var city_name = data.city_name;
				if(data.city_name == '所有配送地区'){
					city_name = '请选择配送地区';
				}else{
					city_name = '至&nbsp;'+city_name;
				}
				$('#selected_city').html(city_name);
				if(data.inside == false){
					$('.postage-info').html("<b>请选择配送地区</b>");
					$('.btn_c2 a').addClass('none');
					$('.btn_c2 a').attr('href','javascript:;');
					$('.btn_c1 a').addClass('none');
					$('.btn_c1 a').attr('href','javascript:;');
				}else{
					html += '<div style="clear:both;">';
					$.each(data.logist_fee,function(n,v){
						if(n > 0){
							html += '<div class="postage-item"><del>'+v.name+':'+v.start_fees+'元</del></div>';
						}else{
							html += '<div class="postage-item">'+v.name+':'+v.start_fees+'元 </div>';
						}
					});
					html += '<div style="clear:both;"></div></div>';
					$('.postage-info').html(html);
					$('.btn_c2 a').removeClass('none');
					$('.btn_c2 a').attr('href','javascript:buy();');
					$('.btn_c1 a').removeClass('none');
					$('.btn_c1 a').attr('href','javascript:checkout();');
				}
				if(data.trans){
					$.each(data.trans,function(index,value){
						$('.postage-info').append('<div style="clear:both;margin-top:2px;color:#f00;background:#ececec;border:1px solid #ccc;text-align:center;">'+value+'</div>');
					});
				}
			}
	});
}

function load_city_nofee(delivery_template_id,store_id,goods_id,city_id)
{
	html = '';
	if(city_id==undefined) {
		city_id = '';
	}
	var url = SITE_URL + '/index.php?app=logist&delivery_template_id='+delivery_template_id+'&store_id='+store_id+'&goods_id='+goods_id+'&city_id='+city_id;
		$.getJSON(url,function(data){
			if (data.done){
				data = data.retval;
				$('.postage-area').hide();
				var city_name = data.city_name;
				if(data.city_name == '所有配送地区'){
					city_name = '请选择配送地区';
				}else{
					city_name = '至&nbsp;'+city_name;
				}
				$('#selected_city').html(city_name);
			}
	});
}