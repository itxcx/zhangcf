/**
* 注意！！！！！！！！在使用本脚本时,要确定加载的jquery库
*
*/
$.extend( 
{
	//默认国家
	area_default_country:'',//'中国',

	//默认省份/州
	area_default_province:'',//'北京市',

	//默认城市
	area_default_city:'',//'市辖区',

	//默认区县
	area_default_county:'',//'东城区',

	//默认乡镇
	area_default_town:'',//'建国门街道',

	//是否显示默认的国、省、市、区县
	area_default_show:false,
	
	//判断是否已加载过 省更新的方法
	province_select:false,

	//判断是否已加载过 城市更新的方法
	city_select:false,

	//判断是否已加载过 区县更新的方法
	county_select:false,

	//判断是否已加载过 街道更新的方法
	town_select:false,

	
	//区域选择绑定
	area_select_bind:function(countryId,provinceId,cityId,countyId,townId)
	{
		$.area_init_country(countryId,provinceId,cityId,countyId,townId);
	},

	/*
	* 初始化国家 Select 控件
	*
	* countryId		国家Select控件 的 ID 
	*/
	area_init_country:function(countryId,provinceId,cityId,countyId,townId)
	{
		$('#'+countryId).empty();
		$('#'+countryId).append("<option value=''>国家</option>");
		$.getJSON("/Public/directSell/country.json",function(data){ 
			$.each(data,function(areakey,areaval){
				var country_json=eval(areaval);
				var default_country=false;
				//加载国家对应的省
				for( var key in country_json)
				{
					if(key=="name"){
						if($.area_default_show && country_json['name']==$.area_default_country){
							default_country=true;
							$('#'+countryId).append("<option selected='selected' value='" + country_json['name'] + "' areakey='"+areakey+"'>" + country_json['name'] + "</option>");
						}else{
							$('#'+countryId).append("<option value='" + country_json['name'] + "' areakey='"+areakey+"'>" + country_json['name'] + "</option>");
						}
					}
				}
				if($.area_default_show){
					//触发加载省州
					$.area_bind_country(countryId,provinceId,cityId,countyId,townId);
					$('#'+countryId).change();
					if(default_country!=true)
						$.area_default_show=false;
					$.province_select=true;
				}else{
					if(!$.province_select)
						$.area_bind_country(countryId,provinceId,cityId,countyId,townId);
					$.province_select=true;
				}
			})
		})
	},
	/*
	* 绑定国家Select
	*
	* 当选择国家时,自动更新省/州Select
	*
	* countryId		国家Select控件 的 ID 
	*
	* provinceId	省份Select控件 的 ID 
	*
	* cityId		城市Select控件 的 ID,传值则自动清空,不传值则不处理
	*
	* countyId		区县Select控件 的 ID,传值则自动清空,不传值则不处理
	*
	* townId		乡镇Select控件 的 ID,传值则自动清空,不传值则不处理
	*
	*/
	area_bind_country:function(countryId,provinceId,cityId,countyId,townId)
	{
		$('#'+countryId).bind('change',function()
		{
			var country = $(this).children(":selected").attr('areakey');
			var countryval = $(this).children(":selected").val();
			//清空省Select
			$('#'+provinceId).empty();
			$('#'+provinceId).append("<option value=''>省份</option>");
			$.getJSON("/Public/directSell/country.json",function(data){
				var province_json=eval(data[country]);
				var default_province=false;
				//加载国家对应的省
				for( var key in province_json)
				{
					if(key!="name"){
						if(province_json[key]==$.area_default_province && countryval==$.area_default_country){
							default_province=true;
							$('#'+provinceId).append("<option selected='selected' value='" + province_json[key] + "' areakey='"+key+"'>" + province_json[key] + "</option>");
						}else{
							$('#'+provinceId).append("<option value='" + province_json[key] + "' areakey='"+key+"'>" + province_json[key] + "</option>");
						}
					}
				}
				if($.area_default_show){
					//触发加载城市
					$.area_bind_province(countryId,provinceId,cityId,countyId,townId);
					$('#'+provinceId).change();
					if(default_province!=true)
						$.area_default_show=false;
					$.city_select=true;
				}else{
					if(!$.city_select)
						$.area_bind_province(countryId,provinceId,cityId,countyId,townId);
					$.city_select=true;
				}
			})
			if(cityId) $('#'+cityId).empty().append("<option value=''>城市</option>");
			if(countyId) $('#'+countyId).empty().append("<option value=''>区县</option>");
			if(townId) $('#'+townId).empty().append("<option value=''>街道</option>");
		});
	},
	/*
	* 绑定省Select
	*
	* 当选择省时,自动更新市Select
	*
	* countryId		国家Select控件 的 ID
	*
	* provinceId	省份Select控件 的 ID 
	*
	* cityId		城市Select控件 的 ID
	*
	* countyId		区县Select控件 的 ID,传值则自动清空,不传值则不处理
	*
	* townId		乡镇Select控件 的 ID,传值则自动清空,不传值则不处理
	*
	*/
	area_bind_province:function(countryId,provinceId,cityId,countyId,townId)
	{
		$('#'+provinceId).bind('change',function()
		{
			var country  = $('#'+countryId).children(":selected").attr('areakey');
			var province = $(this).children(":selected").attr('areakey');
			var provinceval = $(this).children(":selected").val();
			//清空城市Select
			$('#'+cityId).empty();
			$('#'+cityId).append("<option value=''>城市</option>");
			if(province){
				//判断处于哪个文件中 取余数
				var provincekey=province.replace(country,'0');
				var default_city=false;
				$.getJSON("/Public/directSell/area"+(provincekey%110)+".json",function(data){
					var city_json=eval(data[province]);
					//加载国家对应的省
					for( var key in city_json)
					{
						if(city_json[key]==$.area_default_city && provinceval==$.area_default_province){
							default_city=true;
							$('#'+cityId).append("<option selected='selected' value='" + city_json[key] + "' areakey='"+key+"'>" + city_json[key] + "</option>");
						}else{
							$('#'+cityId).append("<option value='" + city_json[key] + "' areakey='"+key+"'>" + city_json[key] + "</option>");
						}
					}
					if($.area_default_show){
						//触发加载区县
						$.area_bind_city(countryId,provinceId,cityId,countyId,townId);
						$('#'+cityId).change();
						if(default_city!=true)
							$.area_default_show=false;
						$.county_select=true;
					}else{
						if(!$.county_select)
							$.area_bind_city(countryId,provinceId,cityId,countyId,townId);
						$.county_select=true;
					}
				})
			}
			if(countyId) $('#'+countyId).empty().append("<option value=''>区县</option>");
			if(townId) $('#'+townId).empty().append("<option value=''>街道</option>");
		});
	},
	/*
	* 绑定市Select
	*
	* 当选择市时,自动更新县Select
	*
	* countryId		国家Select控件 的 ID
	*
	* provinceId	省份Select控件 的 ID 
	*
	* cityId		城市Select控件 的 ID
	*
	* countyId		区县Select控件 的 ID
	*
	* townId		乡镇Select控件 的 ID,传值则自动清空,不传值则不处理
	*
	*/
	area_bind_city:function(countryId,provinceId,cityId,countyId,townId)
	{
		$('#'+cityId).bind('change',function()
		{
			var country  = $('#'+countryId).children(":selected").attr('areakey');
			var province = $('#'+provinceId).children(":selected").attr('areakey');
			var city = $(this).children(":selected").attr('areakey');
			var cityval = $(this).children(":selected").val();

			//清空区县Select
			$('#'+countyId).empty();
			$('#'+countyId).append("<option value=''>区县</option>");
			if(city){
				var citykey=city.replace(country,'0');
				$.getJSON("/Public/directSell/area"+(citykey%110)+".json",function(data){
					var county_json=eval(data[city]);
					//加载国家对应的省
					for( var key in county_json)
					{
						if(county_json[key] == $.area_default_county && cityval==$.area_default_city){
							$('#'+countyId).append("<option selected='selected' value='" + county_json[key] + "' areakey='"+key+"'>" + county_json[key] + "</option>");
						}else{
							$('#'+countyId).append("<option value='" + county_json[key] + "' areakey='"+key+"'>" + county_json[key] + "</option>");
						}
					}
					if($.area_default_show){
						//触发加载街道镇
						$.area_bind_county(countryId,provinceId,cityId,countyId,townId);
						$('#'+countyId).change();
						$.area_default_show=false;
						$.town_select=true;
					}else{
						if(!$.town_select)
							$.area_bind_county(countryId,provinceId,cityId,countyId,townId);
						$.town_select=true;
					}
				})
			}
			if(townId) $('#'+townId).empty().append("<option value=''>街道</option>");
		});
	},

	/*
	* 绑定区县Select
	*
	* 当选择区县时,自动更新乡镇Select
	*
	* countryId		国家Select控件 的 ID
	*
	* provinceId	省份Select控件 的 ID 
	*
	* cityId		城市Select控件 的 ID
	*
	* countyId		区县Select控件 的 ID
	*
	* townId		乡镇Select控件 的 ID
	*
	*/
	area_bind_county:function(countryId,provinceId,cityId,countyId,townId)
	{
		$('#'+countyId).bind('change',function()
		{
			var country		= $('#'+countryId).children(":selected").attr('areakey');
			var province	= $('#'+provinceId).children(":selected").attr('areakey');
			var city		= $('#'+cityId).children(":selected").attr('areakey');
			var county		= $('#'+countyId).children(":selected").attr('areakey');
			var countyval	= $('#'+countyId).children(":selected").val();
			//清空乡镇Select
			$('#'+townId).empty();
			$('#'+townId).append("<option value=''>街道</option>");
			if(county){
				var countykey=county.replace(country,'0');
				$.getJSON("/Public/directSell/area"+(countykey%110)+".json",function(data){
					var town_json=eval(data[county]);
					//加载国家对应的省
					for( var key in town_json)
					{
						if(town_json[key] == $.area_default_town && countyval==$.area_default_county){
							$('#'+townId).append("<option selected='selected' value='" + town_json[key] + "' areakey='"+key+"'>" + town_json[key] + "</option>");
						}else{
							$('#'+townId).append("<option value='" + town_json[key] + "' areakey='"+key+"'>" + town_json[key] + "</option>");
						}
					}
				})
			}
		});
	}
})
	