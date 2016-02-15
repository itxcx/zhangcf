$(function () {
    //下拉菜单
    $('.navlist').find('.key').click(function () {
        $(this).parents('.navlist').siblings().find('.navul').slideUp();
        $(this).next('.navul').slideToggle();
    });
    //股票界面
    $('#holder').parent('div').width(750);
});
