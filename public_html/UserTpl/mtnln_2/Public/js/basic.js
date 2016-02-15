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
    //股票界面
    $('#holder').parent('div').width(750);
});
