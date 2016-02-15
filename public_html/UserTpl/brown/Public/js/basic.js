$('.navlist').first().show(0);
$('.nav>li').first().mouseover(function () {
    $('.navlist').hide(0);
    $(this).next('li').find('.navlist').show(0);
});
$('.nav>li:gt(1)').mouseover(function () {
    $('.navlist').hide(0);
    $(this).find('.navlist').show(0);
});

//iframe 自适应高度
$(".section-main").load(function(){
    var mainheight = $(this).contents().find("body").height() + 30;
    $(this).height(mainheight);
});

