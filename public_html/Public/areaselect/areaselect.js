(function($) {

    $.extend({

        areaSelect: function(obj, url, def) {
            var obj = $(obj);
            $.clickLoad(obj, url, def);
        },

        //点击加载
        clickLoad: function(obj, url, def) {
            obj.parents('.core_con').css({'min-height':'410px'});

            obj.find('.country-select').click(function (e) {
                e.stopPropagation();
                var cH = (obj.find('.country-select').outerHeight() + obj.find('.country-now').outerHeight()) / 2;
                $(this).find('.country-list').toggle(0);
                $(this).find('.country-list').css('top', cH);
                $(this).find('.city-select-wrap').hide(0);
            });

            $(document).click(function (e) {
                e.stopPropagation()
                obj.find('.country-list').hide(0);
                obj.find('.city-select-wrap').hide(0);
                obj.find('.overseas-box').hide(0);
            });

            obj.find('.country-list>li').click(function () {
                $.init(obj);

                var locat = parseInt($(this).find('a').attr('data-value'));

                obj.find('.location-box').hide(0);

                $(this).siblings('li').removeClass('current');
                $(this).addClass('current');
                obj.find('.country-now').val($(this).find('a').html());
                obj.find('.country').val($(this).find('a').html());

                if (locat != 0) {
                    obj.find('.location-box').show(0);
                    obj.find('.location-box').html($.addLocation(locat));
                    var aH = (obj.find('.location-box').outerHeight() + obj.find('.city-title').outerHeight()) / 2;
                    obj.find('.city-select-wrap').css('top', aH);
                }


                //数据获取
                //省份
                if (locat == 1) {
                    //点击弹出地址选择
                    obj.find('.city-title').click(function (e) {
                        e.stopPropagation()
                        $(this).next('.city-select-wrap').toggle(0);
                        var aH = (obj.find('.location-box').outerHeight() + obj.find('.city-title').outerHeight()) / 2;
                        obj.find('.city-select-wrap').css('top', aH);
                        obj.find('.country-list').hide(0);
                    });

                    $.getJSON(url + '/areaselect/provinces.json?v='+new Date().getTime(),function(data){
                        var country =  JSON.parse(JSON.stringify(data));
                        $.each(country, function (name, value) {
                            var city_box = '';
                            var str = '';
                            for (i in value) {
                                str +=  '<a data-value="' + i + '" href="javascript:void(0);">' + value[i] + '</a>'
                            }

                            city_box = '<dl class="city-box">' +
                                            '<dt>' + name + '</dt>' + 
                                            '<dd>' + str + '</dd>' +
                                            '</dl>';
                            obj.find('.city-select.city-province').append(city_box);

                        });
                        $.areaList('province', obj, url, def);
                        $.tabChange(obj);
                    });
                } else {
                    //点击弹出地址选择
                    obj.find('.city-title').click(function (e) {
                        e.stopPropagation()
                        $(this).next('.overseas-box').toggle(0);
                        obj.find('.country-list').hide(0);
                    });
                    $.loadOther(obj, url);
                }

            });

            $.init(obj, def);


        },

        //初始化
        init: function(obj, def) {
            if (!!def) {
                var str = '';
                if (def.country && def.country != ' ') {
                    obj.find('.country').val(def.country);
                    obj.find('.country-now').val(def.country);
                    obj.find('.location-box').show(0);
                    str += def.country;
                    if (def.province) {
                        obj.find('.province').val(def.province);
                        str += (' / ' + def.province);

                        if (def.city) {
                            obj.find('.city').val(def.city);
                            str += (' / ' + def.city);

                            if (def.county) {
                                obj.find('.county').val(def.county);
                                str += (' / ' + def.county);

                                if (def.town) {
                                    obj.find('.town').val(def.town);
                                    str += (' / ' + def.town);
                                }
                            }
                        }
                    } else {
                        str = '请选择地址';
                    }

                    obj.find('.country-list').find('li').eq(1).click();
                    obj.find('.location-box .city-title').val(str);
                    obj.find('.country-list').toggle();
                }
            } else {
                obj.find('.province').val('');
                obj.find('.city').val('');
                obj.find('.county').val('');
                obj.find('.town').val('');
            }
        },

        //初始框架
        addLocation: function(locat) {
            var str = '';
            var strtab = '';
            switch (locat) {
                case 0:
                    break;
                case 1:
                    str += '<input class="city-title arrow-bg" type="text" value="请选地址" readonly>';
                    strtab = '<div class="city-select-tab">'+
                           '<a class="a-province current" href="javascript:void(0)">省份</a>'+
                           '<a class="a-city" href="javascript:void(0)">城市</a>'+
                           '<a class="a-county" href="javascript:void(0)">县区</a>'+
                           '<a class="a-town" href="javascript:void(0)">街道</a>'+
                       '</div>';
                    str += '<div class="city-select-wrap">'+ strtab +
                               '<div class="city-select-content">' +
                                    '<div class="city-select city-province">' +
                                    '</div>' + 
                                    '<div class="city-select city-city">' +
                                    '</div>' +
                                    '<div class="city-select city-county">' +
                                    '</div>' +
                                    '<div class="city-select city-town">' +
                                    '</div>' +
                                '</div>';
                    break;
                case 2:
                    str += '<input class="city-title arrow-bg" type="text" value="请选择国家" readonly><div class="overseas-box"></div>';
                    break;
                default: 
                    return '<span>输入有误！</span>';
            };
            return str;
        },
        //tab切换
        tabChange: function(obj) {
            obj.find('.city-select-tab').find('a').click(function (e) {
                e.stopPropagation()
                var index = $(this).index();
                $(this).siblings().removeClass('current');
                $(this).addClass('current');

                obj.find('.city-select').hide(0);
                switch (index) {
                    case 0:
                        obj.find('.city-province').show(0);
                        break;
                    case 1:
                        obj.find('.city-city').show(0);
                        break;
                    case 2:
                        obj.find('.city-county').show(0);
                        break;
                    case 3:
                        obj.find('.city-town').show(0);
                        break;
                    default:
                        return 0;
                }
            });
        },

        //地址列表点击事件
        areaList: function(area, obj, url, def) {
            obj.find('.city-' + area + ' .city-box').find('dd>a').click(function (e) {
                e.stopPropagation();
                var str = '';
                var output = {
                    province: obj.find('.province'),
                    city: obj.find('.city'),
                    county: obj.find('.county'),
                    town: obj.find('.town')
                }

                if (!!def && !!def.level) {
                    level = def.level;
                } else {
                    level = 'town';
                }
                if (area == level) {
                    obj.find('.city-select-wrap').hide(0);
                } else {
                    obj.find('.city-select').hide(0);
                    obj.find('.city-' + area).next().show(0);
                    $(this).parents('.city-select').find('.current').removeClass('current');
                    $(this).addClass('current');
                    if (!!(obj.find('.city-select-tab').find('.a-' + area).next().get(0))) {
                        obj.find('.city-select-tab').find('a').removeClass('current');
                        obj.find('.city-select-tab').find('.a-' + area).next().addClass('current');
                    }
                }

                obj.find('.' + area).val($(this).html());
                switch (area) {
                    case 'province':
                        output.city.val('');
                        obj.find('.city-city').html('');
                    case 'city':
                        output.county.val('');
                        obj.find('.city-county').html('');
                    case 'county':
                        output.town.val('');
                        obj.find('.city-town').html('');
                    case 'town':
                        break;
                    default:
                        return 0;
                }

                $.loadArea(area, $(this), obj, url, def);

                if (!!output.province.val())
                    str += output.province.val() + ' / ';
                if (!!output.city.val()) {
                    if (!(output.city.val() == '市辖区' || output.city.val() == '县' || output.city.val() == '省直辖县级行政单位')) {
                        str += output.city.val() + ' / ';
                    }
                }
                if (!!output.county.val())
                    if (!(output.county.val() == '市辖区')) {
                        str += output.county.val() + ' / ';
                    }
                if (!!output.town.val())
                    str += output.town.val();
                obj.find('.city-title').val(str);
            });
        },

        //载入地址列表
        loadArea: function(area, _this, obj, url, def) {
            var main = _this.attr('data-value');
            var mainkey = main.replace('CN','0');
            var areaNext = '';
            var areaJson = '';
            if (!def) {
                def = {};
                def.level = 'town';
            }
            switch(area) {
                case 'province':
                    areaNext = 'city';
                    areakey = 1;
                    break;
                case 'city': 
                    areaNext = 'county';
                    areakey = 2;
                    break;
                case 'county': 
                    areaNext = 'town';
                    areakey = 3;
                    break;
                case 'town': 
                    areaNext = ('town');
                    areakey = 4;
                    break;
                default:
                    areakey = 4;
                    break;
            }
            switch(def.level) {
                case 'province':
                    level = 1;
                    break;
                case 'city':
                    level = 2;
                    break;
                case 'county':
                    level = 3;
                    break;
                case 'town':
                    level = 4;
                    break;
                default:
                    level = 4;
                    break;
            }

            areaJson = url + '/areaselect/area_json/area'+(mainkey%110)+'.json';

            $.getJSON(areaJson,function(data){
                var parents =  JSON.parse(JSON.stringify(data));
                var son_json =  parents[main];
                var city_box = '';
                var str = '';

                if (!!son_json && areakey < level) {
                    for (i in son_json) {
                        str +=  '<a data-value="' + i + '" href="javascript:void(0);">' + son_json[i] + '</a>'
                    };
                    city_box = '<dl class="city-box city-select-'+areaNext+'">' +
                                   '<dd>' + str + '</dd>' +
                               '</dl>';
                    obj.find('.city-select.city-'+areaNext).html(city_box);

                    $.areaList(areaNext, obj, url, def);
                } else {
                    obj.find('.city-select-wrap').hide(0);
                }
            });
        },

        //其他国家
        loadOther: function (obj, url) {
            $.getJSON(url + '/areaselect/other.json?v='+new Date().getTime(),function(data){
                var json_data =  JSON.parse(JSON.stringify(data));
                var str = '';
                str = '<li class="current"><i>&radic;</i><a href="javascript:void(0);">请选择国家</a></li>';
                for (i in json_data) {
                    str +=  '<li><i>&radic;</i><a href="javascript:void(0);">' + json_data[i] + '</a></li>'
                };
                str = '<ul class="overseas-list">' + str + '</ul>';
                obj.find('.overseas-box').html(str);
                obj.find('.overseas-box').css('top', (obj.find('.location-box') + obj.find('.city-title').outerHeight()) / 2);

                obj.find('.overseas-list>li').click(function () {
                    $.init(obj);
                    $(this).siblings('.current').removeClass('current');
                    $(this).addClass('current');
                    obj.find('.country').val($(this).find('a').text());
                    obj.find('.city-title').val($(this).find('a').text());
                });
            });
        }


    });

})(jQuery); 


