$(function () {
    /*菜单*/
    $('.nav-list').hover(function() {
        $(this).addClass('hover');
        $(this).find('.navul').show(0);
    }, function() {
        $(this).removeClass('hover');
        $(this).find('.navul').hide(0);
    });

    $('.slider').lateralSlider({transitions: "fade"});
    $('.circle').appendTo('.slider-a');

});
