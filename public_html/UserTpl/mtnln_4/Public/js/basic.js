$(function () {
    //下拉菜单
    $('.navlist').hover(function () {
        $(this).addClass('navhover');
        $(this).find('.navul').slideDown(0);
    },function () {
        $(this).find('.navul').slideUp(0);
        $(this).removeClass('navhover');
    });
    //基本信息
    $('.tbleft,.tbright').find('tr:nth-child(2n)').css('background','#f9f5e8');
    //公告背景
    $('.notice-tb').find('tr:nth-child(2n+2)').css('background','#f9f5e8');
    //表格背景
    $('.tablebg').find('tr:nth-child(2n)').css('background','#f9f5e8');

    showClock();
    navHeight();
});
//时间
var nowsecond;
var nowminutes;
function showClock(){
    var date = new Date();
    var year=date.getFullYear();
    var month=date.getMonth()+1;
    var day=date.getDate()
    var hour = date.getHours();
    var minute = date.getMinutes();
    var second = date.getSeconds();
    if( hour<10 )   hour = "0"+hour;
    if( minute<10 ) minute = "0"+minute;
    if( second<10 ) second = "0"+second;
    $(".nowtime").html(hour+":"+minute);
    $(".nowdate").html(year+"年"+month+"月"+day+"日");
    setTimeout( "showClock()",1000 );
}
//nav-left 高度
function navHeight() {
    navh = document.documentElement.clientHeight-110;
    contenth = $('.content').height()+60;
    if (navh > 750 || contenth > 750) {
        if (navh > contenth) {
            $('.left-nav').height(navh);
        } else {
            $('.left-nav').height(contenth);
        }
    }
}
