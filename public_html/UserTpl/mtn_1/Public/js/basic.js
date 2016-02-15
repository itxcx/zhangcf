$(function () {
    //下拉菜单
    $('.navlist').hover(function () {
        $(this).addClass('navhover');
        $(this).find('.navul').slideDown(0);
    },function () {
        $(this).find('.navul').slideUp(0);
        $(this).removeClass('navhover');
    });
    //股票界面
    $('#holder').parent('div').width(650);
    $('#holder').parents('.tablebg').css('background','#865e2b');
    //发送邮件
    $('.Mail#send').find('#content_1').width(600);
});
