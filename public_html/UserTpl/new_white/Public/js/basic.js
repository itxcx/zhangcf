$(function () {
    $('.nav-list li').hover(function () {
        $(this).find('.nav-li').show(0);
        $(this).find('.nav-key').addClass('keyhover');
    },function () {
        $(this).find('.nav-li').hide(0);
        $(this).find('.nav-key').removeClass('keyhover');
    });
    $('.report').hover(function () {
        $(this).addClass('reporthover');
    }, function () {
        $(this).removeClass('reporthover');
    });
});
