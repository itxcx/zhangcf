function cnyOut(){
    this.bankcards = {};
}

cnyOut.prototype.addNewAddr = function(){
    $('#newAddress').show();
    $('#addAddress_tr').hide();
}
//保存提现地址
cnyOut.prototype.submitNewAddr = function(){
    new_label = $('#new_label').val();
    if(!new_label){
        this.fade('#labelmsg', '请添加标签');
        $("#addNew").removeAttr('disabled');
        return false;
    }
    bank = $('#bank').val();
    if(bank == ''){
        this.fade('#bankmsg','请选择银行');
        $("#addNew").removeAttr('disabled');
        return false;
    }
    province = $('#province_id').val();
    city = $('#city_id').val();
    county = $('#county_id').val();
    if(province == "" || city == "" || county==""){
        this.fade('#addressmsg', '请选择银行卡所在地');
        $("#addNew").removeAttr('disabled');
        return false;
    }
    account = $('#account').val();
    if(!account){
        this.fade('#accountmsg', '请填写银行卡号');
        $("#addNew").removeAttr('disabled');
        return false;
    }
    name = $('#name').val();
    if(!name){
        this.fade('#accountmsg', '请填写银行卡开户名');
        $("#addNew").removeAttr('disabled');
        return false;
    }
	var data ={label:new_label,bank:bank,province:province,city:city,county:county,bankcard:account,bankname:name};
    this.ajaxSubmit(data,"/index.php?s=/User/Fun_bank/addgetcount", 'post', false);
}
cnyOut.prototype.fade = function(id, words){
    $(id).html(words).fadeOut().fadeIn().fadeOut().fadeIn().fadeOut().fadeIn();
}
//提交表单
cnyOut.prototype.ajaxSubmit = function(data, url, type, async){
    $.ajax({
        type: type,
        url: url,
        data:data,
        dataType: "json",
        async: async,
        success: function(data){
            var str = eval(data);
            if(str.status == 0){
                alert(str.message);
            }else{
                alert(str.message);
                Fnc.reload_page();
            }
        }
    });
}
//默认地址
cnyOut.prototype.setAddr = function(id){
    data = {};
    data['id'] = id;
    this.ajaxSubmit(data, '/index.php?s=/User/Fun_bank/setgetcount', 'post', false);
}
//删除地址
cnyOut.prototype.delAddr = function(id){
    data = {};
    data['id'] = id;
    this.ajaxSubmit(data, '/index.php?s=/User/Fun_bank/delgetcount', 'post', false);
}
//提现实际金额
cnyOut.prototype.daozhang = function(){
    money = $('#getsum').val();
    //百分比
    var outfee = $('#outfee').html()/100;
    //提现额
	out = money.replace(/[^\d.]/g,'');
	
	if($('#outfrom').val()==1){
		//手续费
	    var fee=money*parseFloat(outfee);
	    
	    //判断手续费的最大最小值
	    if(fee<$('#outfeemin').html()){
			fee=$('#outfeemin').html()
		}
		if($('#outfeemax').html()>0 && fee>$('#outfeemax').html()){
			fee=$('#outfeemax').html();
		}
	}else{
		fee=0;
	}
	var true_daozhang=money-fee;
	//换算比率
	var outRatio=$('#outRatio').html();
	if(outRatio>1 || outRatio<1 && outRatio>0){
		true_daozhang=true_daozhang*outRatio;
	}
	if(true_daozhang<0){
		true_daozhang=0
	}
    $('#getsum').prop('value', out);
    $('#outfee').prop('value', fee);
    $('#true_daozhang').attr("value", this.toDecimal2(true_daozhang));
}
//提现表单
cnyOut.prototype.rmbconfirm = function(){
	data = {};
    money = parseFloat($('#getsum').val());
    empty_money = parseFloat($('#getsum').val());
    out_min = parseFloat($('#out_min').html());
    out_max = parseFloat($('#out_max').html());
	if(!money){
		this.Rfade('#rmbout_showtips', '请填写提现金额');
		$("#tijiao").removeAttr('disabled');
		return false;
	}
    if(money < out_min || empty_money < out_min){
		this.Rfade('#rmbout_showtips', '最小提现为'+this.toDecimal2(out_min)+'元');
		$("#tijiao").removeAttr('disabled');
		return false;
    }
    if(out_max>0 && money > out_max){
        this.Rfade('#rmbout_showtips', '最大提现为'+this.toDecimal2(out_max)+'元');
        $("#tijiao").removeAttr('disabled');
        return false;
    }
    if($('#pass2').val()!=undefined){
	    pass2 = $('#pass2').val();
	    if(!pass2){
	    	this.Rfade('#rmbout_showtips', '请输入正确的二级密码');
	    	$("#tijiao").removeAttr('disabled');
	    	return false;
	    }
	    data['pass2']=pass2;
	}
	if($('#pass3').val()!=undefined){
	    pass3 = $('#pass3').val();
	    if(!pass3){
	    	this.Rfade('#rmbout_showtips', '请输入正确的三级密码');
	    	$("#tijiao").removeAttr('disabled');
	    	return false;
	    }
	    data['pass3']=pass3;
	}
	//短信 getSmsVerfy
	if($('#getSmsVerfy').val()!=undefined){
		getSmsVerfy=$('#getSmsVerfy').val();
		if(!getSmsVerfy){
	    	this.Rfade('#rmbout_showtips', '请输入正确的短信验证码');
	    	$("#tijiao").removeAttr('disabled');
	    	return false;
	    }
	    data['getSmsVerfy']=getSmsVerfy;
	}
	//密保 getSmsVerfy
	if($('#getsafeanswer').val()!=undefined){
		getsafeanswer=$('#getsafeanswer').val();
		if(!getsafeanswer){
	    	this.Rfade('#rmbout_showtips', '请输入正确的密保答案');
	    	$("#tijiao").removeAttr('disabled');
	    	return false;
	    }
	    data['getsafeanswer']=getsafeanswer;
	}
	//最大提现额
    out_over = parseFloat($('#out_over').html());
    if($('#outfrom').val()==0){
    	money+=$('#outfee').val();
    }
    if(money > out_over){
        this.Rfade('#rmbout_showtips', '可用余额不足');
        $("#tijiao").removeAttr('disabled');
        return false;
    }
    var checktype=$("input:radio[name='xuanzhong']:checked").val();
	if(!checktype || checktype<=0){
		this.Rfade('#rmbout_showtips', '请选择提款地址');
		$("#tijiao").removeAttr('disabled');
        return false;
	}
	xpath=$("#xpath").val();
    data['getsum']=money;
    data['getbanktype']=$("input:radio[name='xuanzhong']:checked").val();
    this.ajaxSubmit(data, '/index.php?s=/User/Fun_bank/getSave:'+xpath, 'post', false);
    $("#tijiao").removeAttr('disabled');
}
//撤销提现
cnyOut.prototype.rmbconcel = function(id){
	data = {};
    data['id']=id;
	this.ajaxSubmit(data, '/index.php?s=/User/Fun_bank/getcancel', 'get', false);
}
cnyOut.prototype.Rfade = function(id, words){
    $(id).html(words).fadeOut().fadeIn().fadeOut().fadeIn().fadeOut().fadeIn();
}

cnyOut.prototype.checkbox = function(id){
    $('#'+id+'cl').attr('checked', 'checked');
}

cnyOut.prototype.toDecimal2 = function(x) {
  var f = parseFloat(x);
  if (isNaN(f)) {
      return false;
  }
  var f = Math.round(x * 100) / 100;
  var s = f.toString();
  var rs = s.indexOf('.');
  if (rs < 0) {
      rs = s.length;
      s += '.';
  }
  while (s.length <= rs + 2) {
      s += '0';
  }
  return s;
}

cnyOut = new cnyOut();

$('#rmbout_con').submit(function(){
	value = cnyOut.rmbconfirm();
	if(value != true){
		return false;
	}
	return true;
});
