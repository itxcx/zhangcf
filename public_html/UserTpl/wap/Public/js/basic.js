$(function() {
    FastClick.attach(document.body);
});

function showNav(navid) {
    $('#content').find('nav').hide(0);
    $('.content-main').hide(0);
    $('#cd'+navid).fadeIn(0);
}

