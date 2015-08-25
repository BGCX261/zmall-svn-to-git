/***
 * 判断返回是否json格式
 * Add by MingFONG @ 2015-02-27
 */
isJson = function(obj){
    var isjson = typeof(obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && !obj.length;
    return isjson;
}
//获取购物车内容
function getCartajax(){
    $.get('index.php?app=cart&act=getAjax',function(data){
//                c**ole.log(data);
        if (!isJson(data)) data = eval('('+data+')');
        var quantity = data.quantity;
//        alert(quantity);
        $("#cart_nums").html(quantity);
        var _html = '';
		var img = 'http://127.0.0.1/themes/mall/zmall/styles/default/images/icon_32_shoppingcart_bw.jpg';
        if (quantity > 0){
            _html += '<p class="cart_list_title bd">最新加入的宝贝</p>';
            _html += '<ul class="cart_list_li">';
            var len = data.goods.length;
            var carts = data.goods;
            for (var i = 0; i < len; i++){
                _html += '<li class="clrfix"><div class="imgs fl"><p><img src="'+carts[i]['goods_image']+'" title="'+carts[i]['goods_name']+'" /></p></div><div class="info fl"><p class="clrfix"><span class="fl">'+carts[i]['goods_name']+'</span><span class="price fr">'+carts[i]['price']+'</span></p><p class="clrfix"><span class="gray fl">'+carts[i]['specification']+'</span><span class="fr"><a href="javascript:void(0);" onclick="drop_header_cart_item('+carts[i]['rec_id']+')">删除</a></span></p></div></li>';
            }
            _html += '</ul>';
            _html += '<p class="cart_list_bottom tr"><a href="index.php?app=cart"><img src="'+img+'" alt="查看我的购物车"/> </a></p>';
        }else{
            _html += '<p class="empty"><span>您购物车里还没有任何宝贝。</span><a href="index.php?app=cart"><img src="'+img+'" alt="查看我的购物车"/> </a></p>';
        }
        $("#cart_list").html(_html);
    },'json');
}

//header_drop
function drop_header_cart_item(rec_id){
    $.getJSON('index.php?app=cart&act=drop&rec_id=' + rec_id, function(result){
        if(result.done){
            getCartajax();//更新购物车内容
        }
    });
}