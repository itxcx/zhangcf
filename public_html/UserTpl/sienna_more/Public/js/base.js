$(function () {
    //菜单切换
    $('.navlist li').find('a').click(function () {
        var key = $(this).attr('class');
        key = key.match(/key\d/);
        $('.navlist li').find('.cur').removeClass('cur');
        $(this).addClass('cur');
        $('.nav-child.cur').removeClass('cur');
        $('dl.cur').removeClass('cur');
        $('.show_' + key).addClass('cur');
    });

});
//创建 cookie
function setCookie(name, value, expires, path, domain, secure) {
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
                document.cookie.substring(cookieStart + cookieName.length, cookieEnd));
    }
    return cookieValue;
}
//动态加载样式
function loadStyles(url) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = url;
    document.getElementsByTagName('head')[0].appendChild(link);
}

