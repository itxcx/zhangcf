"use strict";

jQuery(document).ready(function($){
	$( window ).load(function() {
	});
	$(".main_menu a.templatemo_home, .responsive_menu a.templatemo_home").click(function(){
		$("#menu-container2 .contact").addClass("animated fadeInDown").show();
		$("#menu-container1 .contact").hide();
		$(this).addClass('active');
		$(".main_menu a.templatemo_page5, .responsive_menu a.templatemo_page5").removeClass('active');
		
		return false;
	});
	$(".main_menu a.templatemo_page5, .responsive_menu a.templatemo_page5").click(function(){
		$("#menu-container1 .contact").addClass("animated fadeInDown").show();
		$("#menu-container2 .contact").hide();
		$(this).addClass('active');
		$(".main_menu a.templatemo_home, .responsive_menu a.templatemo_home").removeClass('active');
		
		return false;
	});
});