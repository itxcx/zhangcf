$(function () {

//菜单显示
var viewli = $('#view.index').children('.list').children('li'); 
viewli.click(function () {
    $('.js-index').hide(0);
    var viewindex = $(this).index();
    $('.js-index').addClass('pt-page-moveToLeft');
    $('.navlist' + viewindex).show(0);
    $('.navlist' + viewindex).addClass('pt-page-moveFromRight');
    setCookie('navshow', viewindex);
});
//菜单保持
if (!!getCookie('navshow')) {
    $('.js-index').hide(0);
    $('.navlist' + getCookie('navshow')).addClass('pt-page-moveFromLeft');
    $('.navlist' + getCookie('navshow')).show(0);
}

//返回首页
$('.js-home').click(function () {
    unsetCookie('navshow');
});

//菜单返回
$('.js-back').click(function () {
    unsetCookie('navshow');
    $('.navlist').hide(0);
    $('.js-index').removeClass('pt-page-moveToLeft');
    $('.js-index').addClass('pt-page-moveFromLeft');
    $('.js-index').show(0);
});

//返回
$('.js-goback').click(function () {
    history.back();
});

//汇款通知
$('.rem-info').find('.down').click(function () {
    $(this).hide(0);
    $(this).parents('.rem-info').find('.up').show(0);
    $$(this).parents('.rem-info').addClass('down');
});
$('.rem-info').find('.up').click(function () {
    $(this).hide(0);
    $(this).parents('.rem-info').find('.down').show(0);
    $(this).parents('.rem-info').removeClass('down');
});
//表单详情
$('.award-info').find('.more').click(function () {
    $(this).parents('.dl').find('.award-list').slideToggle(300);
    $(this).parents('.dl').find('.award-info .up').slideToggle(300);
    $(this).parents('.dl').find('.award-info .down').slideToggle(300);
});
$('.award-list').find('.up').click(function () {
    $(this).parents('.dl').find('.award-list').slideUp(300);
    $(this).parents('.dl').find('.award-info .up').slideToggle(300);
    $(this).parents('.dl').find('.award-info .down').slideToggle(300);
});

});

/*cookie*/
function setCookie(name, value) {
    var cookieText = encodeURIComponent(name) + '=' + encodeURIComponent(value);
    document.cookie = cookieText;
}
function getCookie(name) {
    var cookieName = encodeURIComponent(name) + '=';
    var cookieStart = document.cookie.indexOf(cookieName);
    var cookieValue = null;
    if (cookieStart > -1) {
        var cookieEnd = document.cookie.indexOf(';', cookieStart);
        if (cookieEnd == -1) {
            cookieEnd = document.cookie.length;
        }
        cookieValue = decodeURIComponent(
            document.cookie.substring(cookieStart + cookieName.length, cookieEnd)
        );
    }
    return cookieValue;
}
function unsetCookie(name) {
    document.cookie = name + "= ; expires=" + new Date(0);
}


