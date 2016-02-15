$(function () {
    //下拉菜单
    $('.navlist').hover(function () {
        $(this).addClass('navhover');
        $(this).find('.navul').slideDown(100);
    },function () {
        $(this).find('.navul').slideUp(0);
        $(this).removeClass('navhover');
    });
    //导航标题
    var pattern = /^.*&gt;/;
    if ($('.core_title_con').html()) {
        var str = $('.core_title_con').html();
        str = str.replace(pattern,'');
        $('.core_title').after('<h3 class="h3 banner-text"></h3>');
        $('.banner-text').html(str);
    }
    //股票界面
    $('#holder').parent('div').width(650);
    //发送邮件
    $('.Mail#send').find('#content_1').width(550);
});
