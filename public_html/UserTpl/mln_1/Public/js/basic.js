$(function () {
    //下拉菜单
    $('.navlist').find('.key').click(function () {
        $(this).parents('.navlist').siblings().find('.arrow1').show(0);
        $(this).parents('.navlist').siblings().find('.arrow2').hide(0);
        $(this).parents('.navlist').siblings().find('.navul').slideUp();
        $(this).find('.arrow1').toggle(0);
        $(this).find('.arrow2').toggle(0);
        $(this).next('.navul').slideToggle();
    });
    //导航标题
    var pattern = /^.*&gt;/;
    if ($('.core_title_con').html()) {
        var str = $('.core_title_con').html();
        str = str.replace(pattern,'');
        $('.core-m').prepend('<h3 class="h3 banner-text"></h3>');
        $('.banner-text').html(str);
    }
    //股票界面
    $('#holder').parent('div').width(750);
});
