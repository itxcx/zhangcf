$(function () {
    //下拉菜单
    $('.navlist').hover(function () {
        $(this).find('.navul').slideDown(0);
    },function () {
        $(this).find('.navul').slideUp(0);
    });
    
    //位置
    var pattern = /^.*：/;
    if ($('.core_title_con').html()) {
        var str = $('.core_title_con').html();
        str = str.replace(pattern,'');
        str = str.substring(str.indexOf('&gt;&gt;') + 8,str.length);
        $('.core_title_con').before('<div class="tit"></div>');
        $('.tit').html(str);
    }

    //table2
    var mstr = $('#salereg').find('#table2').html();
    if (mstr) {
        if (mstr.length < 300) {
            $('#table2').hide(0);
            $('#table1').css({'margin':'0 auto','float':'none'});
        }
    }

    //Net
    if ($('.Net').get(0)) {
        $('.content-main').css('overflow','auto');
    }

});

