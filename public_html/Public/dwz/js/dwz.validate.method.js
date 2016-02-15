/**
 * @requires jquery.validate.js
 * @author ZhangHuihua@msn.com
 */
(function($){
	if ($.validator) {
		$.validator.addMethod("alphanumeric", function(value, element) {
			return this.optional(element) || /^\w+$/i.test(value);
		}, "Letters, numbers or underscores only please");
		
		$.validator.addMethod("lettersonly", function(value, element) {
			return this.optional(element) || /^[a-z]+$/i.test(value);
		}, "Letters only please"); 
		
		//验证电话
		$.validator.addMethod("phone", function(value, element) {
			return this.optional(element) || /^(((((\(\d{3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7})|([1-9]{1}[0-9]{10})){1}[,，\s]{0,1})+$/.test(value);
		}, "Please specify a valid phone number");
		
		$.validator.addMethod("postcode", function(value, element) {
			return this.optional(element) || /^[0-9 A-Za-z]{5,20}$/.test(value);
		}, "Please specify a valid postcode");
		
		//验证帐号
		$.validator.addMethod("account", function(value, element) {
			return this.optional(element) || /^[a-zA-Z0-9]+[A-Za-z0-9\-\._]*$/.test(value);
		}, "Please specify a valid account");
	
		//验证邮箱
		$.validator.addMethod("email", function(value, element) {
			return this.optional(element) || /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(value);
		}, "Please specify a valid email");

		//验证手机
		$.validator.addMethod("mobile", function(value, element) {
			return this.optional(element) || /^([1-9]{1}[0-9]{10}[,]{0,1})+$/.test(value);
		}, "Please specify a valid mobile");

		//验证url链接
		$.validator.addMethod("url", function(value, element) {
			return this.optional(element) || /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/.test(value);
		}, "Please specify a valid url");

		//验证货币
		$.validator.addMethod("currency", function(value, element) {
			return this.optional(element) || /^\d+(\.\d+)?$/.test(value);
		}, "Please specify a valid currency");

		//验证身份证
		$.validator.addMethod("idcard", function(value, element) {
			return this.optional(element) || /^\d{15}(\d{2}[A-Za-z0-9])?$/.test(value);
		}, "Please specify a valid idcard");

		//验证数字
		$.validator.addMethod("number", function(value, element) {
			return this.optional(element) || /^\d+$/.test(value);
		}, "Please specify a valid number");

		//验证QQ
		$.validator.addMethod("qq", function(value, element) {
			return this.optional(element) || /^[1-9]\d{4,}$/.test(value);
		}, "Please specify a valid idcard");

		//验证整数
		$.validator.addMethod("integer", function(value, element) {
			return this.optional(element) || /^[-\+]?\d+$/.test(value);
		}, "Please specify a valid integer");

		//验证邮编
		$.validator.addMethod("zip", function(value, element) {
			return this.optional(element) || /^[1-9]\d{5}$/.test(value);
		}, "Please specify a valid zip");

		//验证浮点数
		$.validator.addMethod("double", function(value, element) {
			return this.optional(element) || /^[-\+]?\d+(\.\d+)?$/.test(value);
		}, "Please specify a valid double");

		//验证英文
		$.validator.addMethod("english", function(value, element) {
			return this.optional(element) || /^[A-Za-z]+$/.test(value);
		}, "Please specify a valid english");

		//验证中文
		$.validator.addMethod("chinese", function(value, element) {
			return this.optional(element) || /^[\u0391-\uFFE5]+$/.test(value);
		}, "Please specify a valid chinese");
		

		$.validator.addMethod("date", function(value, element) {
			value = value.replace(/\s+/g, "");
			if (String.prototype.parseDate){
				var $input = $(element);
				var pattern = $input.attr('dateFmt') || 'yyyy-MM-dd';
	
				return !$input.val() || $input.val().parseDate(pattern);
			} else {
				return this.optional(element) || value.match(/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/);
			}
		}, "Please enter a valid date.");
		
		/*自定义js函数验证
		 * <input type="text" name="xxx" customvalid="xxxFn(element)" title="xxx" />
		 */
		$.validator.addMethod("customvalid", function(value, element, params) {
			try{
				return eval('(' + params + ')');
			}catch(e){
				return false;
			}
		}, "Please fix this field.");
		
		$.validator.addClassRules({
			date: {date: true},
			alphanumeric: { alphanumeric: true },
			lettersonly: { lettersonly: true },
			phone: { phone: true },
			postcode: {postcode: true}
		});
		$.validator.setDefaults({errorElement:"span"});
		$.validator.autoCreateRanges = true;
		
	}

})(jQuery);