/*
 *	author : mrzhangsh
 *	记录网站常用 JS函数
*/	


var Help = {
	
	//检测浏览器
	CheckBrower : function()
	{
		var useragent = window.navigator.userAgent;
		var appname = "";
		var version = ""
		var platform = "";
		//var brower
		var regIE = /MSIE/;
		var regGG = /Chrome/;
		var regFF = /Firefox/;
		var regIpad = /iPad/;
		var regIphone = /iPhone/;
		var regAndroid = /Android/;
		var regPlatform = /Windows/;
		
		if(regPlatform.test(useragent))
		{
			if(regIE.test(useragent))
			{
				var arr = useragent.split(";");
				var txt = arr[1];
				txt = txt.split(" ");
				appname = txt[1];
				version = txt[2];
			}
			else
			{
				if(regGG.test(useragent))
				{
					var arr = useragent.split(" ");
					var txt = arr[(arr.length - 2)];
					txt = txt.split("/");
					appname = txt[0];
					version = txt[1];
				}
				else if(regFF.test(useragent))
				{
					var arr = useragent.split(" ");
					var txt = arr[(arr.length - 1)];
					//alert(useragent);
					txt = txt.split("/");
					appname = txt[0];
					version = txt[1];
				}
			}
			platform = "Windows";
		}
		else
		{
			platform = "Mobile";
			if(regIpad.test(useragent))
			{
				platform = "iPad";
				appname = "iPad";
			}
			else if(regIphone.test(useragent))
			{
				platform = "Mobile";
				appname = "iPhone";
			}
			else if(regAndroid.test(useragent))
			{
				platform = "Mobile";
				appname = "Android";
			}
		}
		
		return {platform : platform, appname : appname, version : version}
	},
	
	//取出字符串中的所有数字
	getNum : function(text){
		var value = text.replace(/[^0-9]/ig,""); 
		return value
	},
	
	
	//只含有以下字符
	Check_text : function(val)
	{
		var reStr =  /^[0-9a-zA-Z,._]+$/
		return reStr.test(val);
	},
	//表示含有以下非法字符
	Check_name : function(val)
	{
		var reStr = /[~`<>\/!\\\^+'";，。；'、]+/g;
		return reStr.test(val);
	},
	//表示含有數字
	Have_num : function(val)
	{
		var reStr = /[0-9]+/g;
		return reStr.test(val);
	},
	//只含有以下字符
	Check_num : function(val)
	{
		var reStr = /^[0-9,.]+$/g;
		return reStr.test(val);
	},
	
	CheckDate : function(val){
		var reStr = /^([0-9]{4}-[0-9]{2}-[0-9]{2})(\s[0-9]{2}:[0-9]{2})?$/;
		return reStr.test(val);
	},
	CheckMemberDate : function(val){
		var reStr = /^([0-9]{4}-[0-9]{2}-[0-9]{2})$/;
		return reStr.test(val);
	},
	CheckMail:function(val){
		var reMail = /^(?:[a-zA-Z0-9]+[_\-\+\.]?)*[a-zA-Z0-9]+@(?:([a-zA-Z0-9]+[_\-]?)*[a-zA-Z0-9]+\.)+([a-zA-Z]{2,})+$/;
		return reMail.test(val);

	},
	
	//js timestamp -- data
	FormatDate : function(timestamp, accuracy)
	{   
		var time = new Date(timestamp);
		var year = time.getFullYear();     
		var month = time.getMonth()+1;     
		var date = time.getDate();  
		var hour = time.getHours();
		var minute = time.getMinutes(); 
		var second = time.getSeconds();
		var result = "";
		
		switch(accuracy)
		{
			case "year":
			{
				result = year;
			}break;
			case "month":
			{
				result = year+"-"+month;
			}break;
			case "day":
			{
				result = year+"-"+month+"-"+date;
			}break;
			case "hour":
			{
				result = year+"-"+month+"-"+date+" "+hour+":00";
			}break;
			case "minute":
			{
				result = year+"-"+month+"-"+date+" "+hour+":"+minute;
			}break;
			case "second":
			{
				result = year+"-"+month+"-"+date+" "+hour+":"+minute+":"+second;
			}break;
			default:
			break;
		}
		return  result;
	},
	
	//cookies  = name~value&name~value	 
	Cookies_modify : function(name, id){
		var oldValue = that.get(name), value = oldValue;
		// 如果有这个值 就更新 如果没有 直接写入到cookie 中
		if(!oldValue){
			value = id;
		}else if(-1 == oldValue.indexOf(id)){
			value = id + '&' + oldValue;
		}
		Help.set(name, value);
		//alert(that.get(name));
	},
	Cookies_set : function(name, value, time){
		var str = name + "=" + escape(value);
		if(time > 0){                               //为时不设定过期时间，浏览器关闭时cookie自动消失
			var date = new Date();
			var ms = time*3600*1000;
			date.setTime(date.getTime() + ms);
			str += "; expires=" + date.toGMTString();
		}
		document.cookie = str;
	},
	Cookies_get : function(name){
		var arr = document.cookie.match(new RegExp("(^| )"+name+"=([^;]*)(;|$)"));
		return arr != null ?  unescape(arr[2]) : null;
	},
	
	Cookies_del : function(name){
		var exp = new Date();
		exp.setTime(exp.getTime() - 1);
		var cval = this.get(name);
		if(cval != null){
			document.cookie = name + "="+cval+";expires="+exp.toGMTString();
		}
	}
	,
	worldTime : function()	{
	var today = new Date((new Date()).getTime());
	var year = today.getFullYear();
	var month = today.getMonth() + 1;
	var day = today.getDate();
	var hour = today.getHours();
	var minute = today.getMinutes();
	var second = today.getSeconds();
	if (hour <= 9)
		hour = "0" + hour;
	if (minute <= 9)
		minute = "0" + minute;
	if (second <= 9)
		second = "0" + second;
			
	var utc = today.getTime() + (today.getTimezoneOffset() * 60000);

	var strhktime = year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second;
	return strhktime;
	
	}

}