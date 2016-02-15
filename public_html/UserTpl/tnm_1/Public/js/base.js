$(function () {

    $('.navul').find('li').hover(function () {
        $(this).addClass('hover');
    }, function () {
        $(this).removeClass('hover');
    });

    var nav = $('.navlist');
    for (i = 0; i <= nav.length; i++) {
        var navd = nav.eq(i).find('dd');
        if (navd.length <= 5) {
            nav.eq(i).width(navd.length * 70);
        } else {
            nav.eq(i).width(420);
        }
    }

});

