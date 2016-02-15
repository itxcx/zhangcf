$(function () {
    //下拉菜单
    $('.navlist').hover(function () {
        $(this).addClass('navhover');
        $(this).find('.navul').slideDown(0);
    },function () {
        $(this).find('.navul').slideUp(0);
        $(this).removeClass('navhover');
    });
    var leftnav = $('.left-nav');
    var mainl = $('.main');
    if (mainl.height() > 700)
        leftnav.height(mainl.height()+20);
});
