var input_eventSrc= null;
function selectstr(obj,event){
    event = event? event: window.event
    input_eventSrc = event.srcElement ? event.srcElement:event.target;
    
    var ptop = $(input_eventSrc).offset().top;
    var pleft = $(input_eventSrc).offset().left;
    var pheight = $(input_eventSrc).height();
    var pwidth = $(input_eventSrc).width();
    var setop = $("#_searchContent").offset().top;
    var seleft = $("#_searchContent").offset().left;
    var top = ptop-setop + pheight + 7;
    var left = pleft - seleft-1;
   
    $("#"+obj).css({"display":"block","top":top,"left":left,"width":pwidth+4,"background-color":"#FFF"});
    $("#"+obj+" li").mouseover(function(){
        $(this).css("background-color","#9dc5e8");
        $(this).css("color","#FFF");
    }).mouseout(function(){
        $(this).css("background-color","#FFF");
        $(this).css("color","#000");
    });
    
}
function selectfun1(obj1,obj2){
    $("input[name='"+obj2+"']").val(obj1);
    $("#"+obj2).hide();
}
        
function resetfun(){
    $("#_searchContent input[type='text']").val("");
    $("#_searchContent input[type='checkbox']").attr("checked",false);
    $("#_searchContent select").find("option[value='']").attr("selected",true);
    
}
function editfun(obj){
    $.post("/Admin/index.php?s=TableList/editList",{url:obj},function(data){
        $("#editList").html(data);
        $( "#sortable" ).sortable();
        $("#sortable li:even").css("background-color","#fafafa");        
        $("#sortable li").mousemove(function(){
            $(this).css("background-color","#efefef");
        }).mouseout(function(){
            $(this).css("background-color","#fff");
        });
        $(".xbutton_text").mouseover(function(){
            $(this).removeClass();
            $(this).addClass("xbutton_onmouseover");
        });
        $(".xbutton_text").mousedown(function(){
            $(this).removeClass();
            $(this).addClass("xbutton_onmousedown");
        });
        $(".xbutton_text").mouseout(function(){
            $(this).removeClass();
            $(this).addClass("xbutton_text");
        });
    });
    
}

function resetEditList(obj){
    
    $.post(obj,{},function(data){
        if(data!=""){
            alertMsg.error(data);
        }else{
			if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
				$.pdialog.reload();
				//initUI()
			}else{
				navTab.reload();
			}
		    $.pdialog.closeCurrent();
        }
    });
}
function setTitleName(obj,obj1){
    $("#title_"+obj1).css("display",'block');
    $("#span_"+obj1).html("");
    $("#title_"+obj1).select();
}
function setListStatus(obj,obj1){
    var status=$(obj).html();
    var setStatus=$("#titleStatus_"+obj1).html();
    if(setStatus=="显示"){
        $("#titleStatus_"+obj1).css("color","red");
    }else{
        $("#titleStatus_"+obj1).css("color","green");
    }    
    $(obj).html(setStatus);
    $("#titleStatus_"+obj1).html(status);
}
function outTitleName(obj){
    $("#title_"+obj).css("display",'none');
    $("#span_"+obj).html($("#title_"+obj).val())
}
function conformEditList(obj,obj2){
    var con=$("#sortable").children().length;
    var showList="";var sortList="";var titleList="";
    for(var i=0;i<con;i++){
        if($("#titleStatus_"+i).html()=="显示"){
            showList+=',1';
        }else{
            showList+=',0';
        }  
        sortList +=','+$("#sortable").children(":eq("+i+")").attr('id').substring(10); 
        titleList += ','+$("#title_"+i).val()
        
    }
    $.post(obj,{showList:showList,sortList:sortList,titleList:titleList},function(data){
        if(data!=""){
            alertMsg.error(data);
        }else{
            navTab.reload();
			$.pdialog.closeCurrent();
        }
    });
}

function xsOperation(obj){
    var frm=$("#xsOperation").attr("action",$("#xsOperation").attr("action")+obj);
    $("#xsOperation").submit();  
}
$("*").click(function(event){
        event = event? event: window.event
        var thissrc=event.srcElement ? event.srcElement:event.target;
        if(input_eventSrc != thissrc){
            $("div[id^='_selectsearch']").css("display","none");
        }
});





//分页处理插件
(function($){
	$.fn.extend({
		xtabelpagination: function(opts,autoload){
			function _getData(pagenum,event){
				if($(".dialog:visible").find(".pagination").length > 0){

					var pagerForm = $(".dialog:visible").find("#pagerForm");
					
					pagerForm.find("input[name='p']").val(pagenum);
					$.pdialog.reload(pagerForm.attr("action"),{data:pagerForm.serialize()});
					
				}else{
					var pagerForm = navTab.getCurrentPanel().find("#pagerForm");
					if(navTab._currentIndex != currentIndex){
						onloadPageData= [];
						onloadPageUrl = null;
						onloadPageRequestnavTab = null;
						if(getNextPageAjax){
							getNextPageAjax.abort();
						}
						getNextPageAjax = null;
						
						pagerForm.find("input[name='p']").val(pagenum);
						$.ajax({
							type:"POST",
							url:pagerForm.attr("action"),
							data:pagerForm.serialize(),
							success:function(){navTab.reload();}
						});
						//navTab.getCurrentPanel().find("#pagerForm").submit();
					//
					}else if(typeof(onloadPageData[pagenum])=="undefined" || onloadPageData[pagenum]==null){
						if(getNextPageAjax){
							getNextPageAjax.abort();
							getNextPageAjax = null;
						}
						onloadPageData[pagenum]=[];
						onloadPageData[pagenum][0]=0;
						pagerForm.find("input[name='p']").val(pagenum);
						$.ajax({
							type: "POST",
							url:onloadPageUrl+'/p/'+pagenum,
							data:onloadPageRequestnavTab,
							global:true,
							success:function(data){
								onloadPageData[pagenum][0]=1;
								onloadPageData[pagenum][1]=data;
								getDataHtml(pagenum,Math.ceil(opts.totalCount/opts.numPerPage),autoload);
							}	
						});
					}else if(onloadPageData[pagenum][0] == 1){
						pagerForm.find("input[name='p']").val(pagenum);
						getDataHtml(pagenum,Math.ceil(opts.totalCount/opts.numPerPage),autoload);
					}else{
						pagerForm.find("input[name='p']").val(pagenum);
						onloadPageData[pagenum][3] = 1;
						var ajaxbg = $("#background,#progressBar");
						ajaxbg.show();
					}
					var newopts=opts;
					newopts.currentPage = event.data.pageNum;
					navTab.getCurrentPanel().find(".pagination").xtabelpagination(newopts,autoload);
				}
				
			}
			//alert(navTab._currentIndex)
			//alert(navTab.getCurrentPanel().html())
			var setting = {
				//分页的几种HTML元素的查询标记
				first$:"li.j-first", prev$:"li.j-prev", next$:"li.j-next", last$:"li.j-last", nums$:"li.j-num>a", jumpto$:"li.jumpto",
				//
				pageNumFrag:'<li class="#liClass#"><a href="javascript:;">#pageNum#</a></li>'
			};
			return this.each(function(){
				if($(".dialog:visible").find(".pagination").length > 0){
					var $this = $(".dialog:visible").find(".pagination");
				}else{
					var $this = navTab.getCurrentPanel().find(".pagination");
				}
				//var $this=navTab.getCurrentPanel().find(".pagination");
				var pc = new Pagination(opts);
				var interval = pc.getInterval();
				
				var pageNumFrag = '';
				for (var i=interval.start; i<interval.end;i++){
					pageNumFrag += setting.pageNumFrag.replaceAll("#pageNum#", i).replaceAll("#liClass#", i==pc.getCurrentPage() ? 'selected j-num' : 'j-num');
				}
				$this.html(DWZ.frag["pagination"].replaceAll("#pageNumFrag#", pageNumFrag).replaceAll("#currentPage#", pc.getCurrentPage())).find("li").hoverClass();
				//获得四种按钮
				var $first = $this.find(setting.first$);
				var $prev = $this.find(setting.prev$);
				var $next = $this.find(setting.next$);
				var $last = $this.find(setting.last$);
				//如果有上一页，则绑定事件，
				if (pc.hasPrev()){
					$first.add($prev).find(">span").hide();
					_bindEvent($prev, pc.getCurrentPage()-1, pc.targetType(), pc.rel());
					_bindEvent($first, 1, pc.targetType(), pc.rel());
				} else {
					//否则变灰
					$first.add($prev).addClass("disabled").find(">a").hide();
				}
				
				if (pc.hasNext()) {
					$next.add($last).find(">span").hide();
					_bindEvent($next, pc.getCurrentPage()+1, pc.targetType(), pc.rel());
					_bindEvent($last, pc.numPages(), pc.targetType(), pc.rel());
				} else {
					$next.add($last).addClass("disabled").find(">a").hide();
				}
				//循环所有数字链接，并增加事件绑定
				$this.find(setting.nums$).each(function(i){
					
					_bindEvent($(this), i+interval.start, pc.targetType(), pc.rel());
				});

				//得到跳转部分代码
				$this.find(setting.jumpto$).each(function(){
					var $this = $(this);
					//得到输入框
					var $inputBox = $this.find(":text");
					//得到按钮
					var $button = $this.find(":button");
					//对按钮进行事件绑定
					$button.click(function(event){
						//alert('jumpto$')
						//取得填写的页数
						var pageNum = $inputBox.val();
						if (pageNum && pageNum.isPositiveInteger()) {
							var newopts1=opts;
							newopts1.currentPage = pageNum;
							$this.xtabelpagination(newopts1,autoload);
							_getData(pageNum,event);
							//dwzPageBreak({targetType:pc.targetType(), rel:pc.rel(), data: {pageNum:pageNum}});
						}
					});

					$inputBox.keyup(function(event){
						if (event.keyCode == DWZ.keyCode.ENTER) $button.click();
					});
				});
				
				
				
			});
			
			function _bindEvent($target, pageNum, targetType, rel){
				
				$target.bind("click", {pageNum:pageNum}, function(event){
					var pagenum = parseInt(event.data.pageNum);
					_getData(pageNum,event);
					dwzPageBreak({targetType:targetType, rel:rel, data:{pageNum:event.data.pageNum}});
					event.preventDefault();
					//alert(pagenum);
				});	
			}
			
		},
		
		orderBy: function(options){
			var op = $.extend({ targetType:"navTab", rel:"", asc:"asc", desc:"desc"}, options);
			return this.each(function(){
				var $this = $(this).css({cursor:"pointer"}).click(function(){
					var orderField = $this.attr("orderField");
					var orderDirection = $this.hasClass(op.asc) ? op.desc : op.asc;
					dwzPageBreak({targetType:op.targetType, rel:op.rel, data:{orderField: orderField, orderDirection: orderDirection}});
				});
				
			});
		},
		pagerForm: function(options){
			var op = $.extend({pagerForm$:"#pagerForm", parentBox:document}, options);
			var frag = '<input type="hidden" name="#name#" value="#value#" />';
			return this.each(function(){
				var $searchForm = $(this), $pagerForm = $(op.pagerForm$, op.parentBox);
				var actionUrl = $pagerForm.attr("action").replaceAll("#rel#", $searchForm.attr("action"));
				$pagerForm.attr("action", actionUrl);
				$searchForm.find(":input").each(function(){
					var $input = $(this), name = $input.attr("name");
					if (name && (!$input.is(":checkbox,:radio") || $input.is(":checked"))){
						if ($pagerForm.find(":input[name='"+name+"']").length == 0) {
							var inputFrag = frag.replaceAll("#name#", name).replaceAll("#value#", $input.val());
							$pagerForm.append(inputFrag);
						}
					}
				});
			});
		}
	});
	
	var Pagination = function(opts) {
		this.opts = $.extend({
			targetType:"navTab",	// navTab, dialog
			rel:"", //用于局部刷新div id号
			totalCount:0,
			numPerPage:10,
			pageNumShown:10,
			currentPage:1,
			callback:function(){return false;}
		}, opts);
	}
	
	$.extend(Pagination.prototype, {
		targetType:function(){return this.opts.targetType},
		rel:function(){return this.opts.rel},
		numPages:function() {
			return Math.ceil(this.opts.totalCount/this.opts.numPerPage);
		},
		getInterval:function(){
			var ne_half = Math.ceil(this.opts.pageNumShown/2);
			var np = this.numPages();
			var upper_limit = np - this.opts.pageNumShown;
			var start = this.getCurrentPage() > ne_half ? Math.max( Math.min(this.getCurrentPage() - ne_half, upper_limit), 0 ) : 0;
			var end = this.getCurrentPage() > ne_half ? Math.min(this.getCurrentPage()+ne_half, np) : Math.min(this.opts.pageNumShown, np);
			return {start:start+1, end:end+1};
		},
		getCurrentPage:function(){
			var currentPage = parseInt(this.opts.currentPage);
			if (isNaN(currentPage)) return 1;
			return currentPage;
		},
		hasPrev:function(){
			return this.getCurrentPage() > 1;
		},
		hasNext:function(){
			return this.getCurrentPage() < this.numPages();
		}
	});
})(jQuery);

function reloadListPage()
{
	var p = navTab.getCurrentPanel().find(".pagination");
	var pagenum=p.attr('currentpage');
	var newopts={rel:p.attr('rel'),
	targetType:p.attr('targettype'),
	totalCount:p.attr('totalcount'),
	numPerPage:p.attr('numperpage'),
	pageNumShown:p.attr('pagenumshown'),
	currentPage:$('.jumpto .textInput',navTab.getCurrentPanel()).val()
	};
	//navTab.getCurrentPanel().find(".pagination").xtabelpagination(newopts,1);
	pagenum=$('.jumpto .textInput',navTab.getCurrentPanel()).val();
	if(pagenum > 0 && typeof(onloadPageData[pagenum])!="undefined"){
		onloadPageData[pagenum]=undefined;
	}
	getNextPageData(pagenum,p.attr('totalcount'),0,true);
}
// 高级搜索
function navTabSearchXs(form, navTabId){
	if($("#_searchContent>form").find("input[name=searchExcel]").val()==1){
		return true;
	}
	var $form = $(form);
	if (form[DWZ.pageInfo.pageNum]) form[DWZ.pageInfo.pageNum].value = 1;
	var urlstr="";
	for(var key in $form.serializeArray()){
		if($form.serializeArray()[key]['value']!=""){
			urlstr=urlstr+"&"+$form.serializeArray()[key]['name']+"="+encodeURIComponent($form.serializeArray()[key]['value']);
		}
	}
	if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
		$.pdialog.closeCurrent();
		$.pdialog.reload($form.attr('action')+urlstr,{data: $form.serializeArray()});
	}else{
		navTab.reload($form.attr('action')+urlstr, {data: $form.serializeArray(), navTabId:navTabId});
		$.pdialog.closeCurrent();
	}
	
	return false;
}
// 搜索回调
function navTabDialogSearchXs(form, navTabId){
	
	var $form = $(form);
	if (form[DWZ.pageInfo.pageNum]) form[DWZ.pageInfo.pageNum].value = 1;
	var urlstr="";
	for(var key in $form.serializeArray()){
		if($form.serializeArray()[key]['value']!=""){
			urlstr=urlstr+"&"+$form.serializeArray()[key]['name']+"="+encodeURIComponent($form.serializeArray()[key]['value']);
		}
	}
	if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
		$.pdialog.reload($form.attr('action')+urlstr,{data: $form.serializeArray()})
	}else{
		navTab.reload($form.attr('action')+urlstr, {data: $form.serializeArray(), navTabId:navTabId});
	}
	return false;
}

//高级搜索excel导出
function _searchExcelfun(){
	
	$("#_searchContent>form").find("input[name=searchExcel]").val('1');
	//$("#_searchContent>form").attr('onsubmit','');
	$("#_searchContent>form").submit();
}

function _doSearchFun(){
	$("#_searchContent>form").find("input[name=searchExcel]").val('0');
	//$("#_searchContent>form").attr('onsubmit','return navTabSearchXs(this);');
	//$("#_searchContent>form").submit();
}

//刷新
function reloadXs(url){
	if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
		$.pdialog.reload(url);
	}else{
		navTab.reload(url);
	}
}

var onloadPageData= [];
var onloadPageUrl = null;
var onloadPageRequestnavTab = null; 
var onloadPageRequestdiaLog = null;
var getNextPageAjax = null;
var currentIndex = null;
var searchPageData = [];


function displine(json,pagenum,request,url,totalpage,autoload,searchUrl){

	pagenum = parseInt(pagenum);
	if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
		onloadPageRequestdiaLog = request;
	}else{
		onloadPageUrl = url;
		currentIndex = navTab._currentIndex;
		searchPageData = [];
		onloadPageData = [];
		onloadPageRequestnavTab = request;
		if(autoload){
			onloadPageData[pagenum] = []
			onloadPageData[pagenum][0]=1;
			//onloadPageData[pagenum][1]=JSON.stringify(json);
			onloadPageData[pagenum][1]=json;
		}
	}
	var innerhtml='';
	$.each(json, function(i,val){
		  if(i == 'nodata'){
			innerhtml = val;
		  }else{
				$.each(val, function(j,tdval){
					if(tdval[1] != undefined){
						innerhtml+='<tr target="tl_id" rel="'+j+'" onclick="'+tdval[1]+'">';
					}else{
						innerhtml+='<tr target="tl_id" rel="'+j+'">';
					}
					$.each(tdval[0], function(a,tdval){
						innerhtml+='<td>';
						innerhtml+=tdval;
						innerhtml+='</td>';
					});
					innerhtml+='</tr>';
				});
		  }
	});
	//alert($(".dialog:visible").find("tbody[class='xtabletbody']").length);
	if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
		
		$(".dialog:visible").find("tbody[class='xtabletbody']").html(innerhtml);
		
		var currentPanel = $(".dialog:visible");
		rebindGridXs(currentPanel);
		//myinitUI($(".dialog:visible"));
	}else{
		$("#navTab .navTab-panel>div").eq(currentIndex).find("tbody[class='xtabletbody']").html(innerhtml);
		var nextPagenum = parseInt(pagenum)+1;
		if(nextPagenum <= parseInt(totalpage) && autoload){
			getNextPageData(nextPagenum,totalpage,autoload);
		}
		rebindGridXs(navTab.getCurrentPanel());
		//高级搜索框预加载
		$.ajax({
			type: "POST",
			url:searchUrl,
			//data:{currentIndex,currentIndex},
			global:false,
			success:function(data){
				searchPageData[currentIndex] = data;
				
			}
		});
	}
	
}
//高级搜索框点击 显示
function getSearchPageData(height,searchUrl){
	
	if(height > $(window).height()){
		height = $(window).height()
	}
	//if($('#navTab-tab').children())
	//alert($('.navTab-tab').children().length)
	if(navTab._currentIndex != currentIndex){
		searchPageData = [];
	}
	currentIndex = navTab._currentIndex;
	if(!searchPageData[currentIndex]){
		$.ajax({
			type: "POST",
			url:searchUrl,
			global:false,
			success:function(data){
				searchPageData[currentIndex] = data;
				$.pdialog.open(false,'_searchDialog', "查询",{width:'630',height:height,mask:true,mixable:true,minable:true,resizable:true,drawable:true,fresh:true},searchPageData[currentIndex]);
			}
		});
	}else{
		$.pdialog.open(false,'_searchDialog', "查询",{width:'630',height:height,mask:true,mixable:true,minable:true,resizable:true,drawable:true,fresh:true},searchPageData[currentIndex]);
	}
}

function getNextPageData(pagenum,totalpage,autoload,dispnow){
	url = onloadPageUrl;
	request = onloadPageRequestnavTab;
	if(pagenum > 0 && typeof(onloadPageData[pagenum])=="undefined"){
		onloadPageData[pagenum]=[];
		onloadPageData[pagenum][0]=0;
		getNextPageAjax =$.ajax({
			type: "POST",
			url:url+'/p/'+pagenum,
			data:request,
			global:false,
			complete:function(XMLHttpRequest,textStatus){
				if(textStatus != "success"){
					onloadPageData[pagenum] = null;
				}
			},
			success:function(data){
				if(data.length == 0){
					onloadPageData[pagenum] = null;
					return true;
				}
				onloadPageData[pagenum][0]=1;
				onloadPageData[pagenum][1]=data;
				if(dispnow == true){
					getDataHtml(pagenum,totalpage,autoload);
				}
				var ajaxbg = $("#background,#progressBar");
				ajaxbg.hide();
			}
			
		});
	}
}
function getDataHtml(pagenum,totalpage,autoload){
		var innerhtml='';
		var json = onloadPageData[pagenum][1];
		
		if(typeof(json)=="string"){
			eval("json="+json+";");
		}
		$.each(json, function(i,val){
			  if(i == 'nodata'){
				innerhtml = val;
			  }else{
					$.each(val, function(j,tdval){
						if(tdval[1] != undefined){
							innerhtml+='<tr target="tl_id" rel="'+j+'" onclick="'+tdval[1]+'">';
						}else{
							innerhtml+='<tr target="tl_id" rel="'+j+'">';
						}
						$.each(tdval[0], function(a,tdval){
							innerhtml+='<td><div>';
							innerhtml+=tdval;
							innerhtml+='</div></td>';
						});
						innerhtml+='</tr>';
					});
			  }
		});
		if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
			var currentPanel = $(".dialog:visible");
			$(".dialog:visible").find("tbody[class='xtabletbody']").html(innerhtml);
			myinitUI($(".dialog:visible"));
		}else{
			var currentPanel = navTab.getCurrentPanel();
			currentPanel.find("tbody[class='xtabletbody']").html(innerhtml);
			//alert(navTab);
			myinitUI(currentPanel);
		}
		var nextPagenum = parseInt(pagenum)+1;
		if(nextPagenum <= totalpage && autoload){
			getNextPageData(nextPagenum,totalpage,autoload);
		}else if(!autoload){
			onloadPageData[pagenum] = null;
		}
		rebindGridXs(currentPanel)
}

//有一个变量，表示目前是否正在缓存，缓存的页数，0为未缓存，N为正在缓存的页数
//点击某页
//如果正在缓存某页，则停止AJAX链接，并立即开始缓存页
//如果正在缓存的是当页，则什么也不做，等到缓存完成
//缓存完成时，判断当前显示页是否为当前缓存的页，如果是，则直接刷新
function resetpage(count,PerPage,NumShown,p,autoload){
	
	//转换页数	
	setTimeout(function(){
		if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
			var currentPanel = $(".dialog:visible");
		}else{
			var currentPanel = navTab.getCurrentPanel();
		}
		currentPanel.find("div.pagination").each(function(){
			
			var $this = $(this);
			$this.xtabelpagination({
				targetType:$this.attr("targetType"),
				rel:$this.attr("rel"),
				totalCount:count,
				numPerPage:PerPage,
				pageNumShown:NumShown,
				currentPage:p
			},autoload);
		});
	},100);
}
function myinitUI(_box){
	var p$ = $(_box || document);
	$("a[target=navTab]", p$).each(function(){
		$(this).click(function(event){
			var $this = $(this);
			var title = $this.attr("title") || $this.text();
			var tabid = $this.attr("rel") || "_blank";
			var fresh = eval($this.attr("fresh") || "true");
			var external = eval($this.attr("external") || "false");
			var url = unescape($this.attr("href")).replaceTmById($(event.target).parents(".unitBox:first"));
			DWZ.debug(url);
			if (!url.isFinishedTm()) {
				alertMsg.error($this.attr("warn") || DWZ.msg("alertSelectMsg"));
				return false;
			}
			navTab.openTab(tabid, url,{title:title, fresh:fresh, external:external});

			event.preventDefault();
		});
	});
	
	//dialogs
	$("a[target=dialog]", p$).each(function(){
		$(this).click(function(event){
			var $this = $(this);
			var title = $this.attr("title") || $this.text();
			var rel = $this.attr("rel") || "_blank";
			var options = {};
			var w = $this.attr("width");
			var h = $this.attr("height");
			if (w) options.width = w;
			if (h) options.height = h;
			options.max = eval($this.attr("max") || "false");
			options.mask = eval($this.attr("mask") || "false");
			options.maxable = eval($this.attr("maxable") || "true");
			options.minable = eval($this.attr("minable") || "true");
			options.fresh = eval($this.attr("fresh") || "true");
			options.resizable = eval($this.attr("resizable") || "true");
			options.drawable = eval($this.attr("drawable") || "true");
			options.close = eval($this.attr("close") || "");
			options.param = $this.attr("param") || "";

			var url = unescape($this.attr("href")).replaceTmById($(event.target).parents(".unitBox:first"));
			DWZ.debug(url);
			if (!url.isFinishedTm()) {
				alertMsg.error($this.attr("warn") || DWZ.msg("alertSelectMsg"));
				return false;
			}
			$.pdialog.open(url, rel, title, options);
			
			return false;
		});
	});
	$("a[target=ajax]", p$).each(function(){
		$(this).click(function(event){
			var $this = $(this);
			var rel = $this.attr("rel");
			if (rel) {
				var $rel = $("#"+rel);
				$rel.loadUrl($this.attr("href"), {}, function(){
					$rel.find("[layoutH]").layoutH();
				});
			}
			event.preventDefault();
		});
	});
}
//重新调整显示样式
function rebindGridXs(currentPanel){
	var $table =currentPanel.find('.j-resizeGrid .grid:first');
		
	$(">input",$table).remove();
	var tlength = $table.width();
	//alert(tlength);
	var aStyles = [];
	var oldThs = currentPanel.find('.gridThead>table').find("thead>tr:last-child").find("th");
	
	for(var i = 0, l = oldThs.size(); i < l; i++) {
		var $th = $(oldThs[i]);
		var style = []
		style[0] = $th[0].style.width;
		style[1] = $th.attr("align");
		aStyles[aStyles.length] = style;
	}
	tbody=$('.xtabletbody',$table)
	var ftr = $(">tr:first-child", tbody);
	$(">td",ftr).each(function(i){
		if (i < aStyles.length) $(this).css('width',(aStyles[i][0]));
	});
	//*--
	var $trs = tbody.find('>tr');
	
	$trs.hoverClass().each(function(event){
		
		var $tr = $(this);
		var $ftds = $(">td", this);
		
		
		//多选处理
		$tr.bind("selectstart",function(event){return !event.shiftKey;});
		
		$tr.click(function(event){
			//多选处理
			event = event || window.event;
			if(event.shiftKey){
				
				var thisprevNum = $tr.prevAll().length;
				var prevNum = $('tr.selected',$tr.parent('tbody')).prevAll().length;
				$tr.parent('tbody').find('>tr').each(function(i,v){
					//alert($(v).hasClass('selected'));
					if((i>thisprevNum && i<prevNum) || (i>prevNum && i<thisprevNum)){
						$(v).addClass("selected");
					}
				});
			}else if(event.ctrlKey){
				
			}else{
				$trs.filter(".selected").removeClass("selected");
			}
			var rels = $tr.attr("rel");
			$tr.siblings('.selected').each(function(i,v){
				rels += ','+$(v).attr('rel')
			});
			$tr.addClass("selected");
			
			var sTarget = $tr.attr("target");
			if (sTarget) {
				if ($("#"+sTarget, $table).size() == 0) {
					$table.prepend('<input id="'+sTarget+'" type="hidden" />');
				}

				$("#"+sTarget, $table).val(rels);
			}
		});
	});
}

document.onkeydown = function(event){
	event = event || window.event;
	var a= event.keyCode;  
	if((a==70)&&(event.ctrlKey)&&(event.shiftKey))
	{  
		var jshref = navTab.getCurrentPanel().find(".xsSearchButton").attr('href');
		if(jshref!= undefined){
			var jsfunction = jshref.substring(11,jshref.length);
			eval(jsfunction);
		}
	}
}

//浮动框显示详情 row=>'<div onmouseover="detailView(this,\'[备注]\');">详情</span>'
function detailView(obj,html){
	var w = $(window).width();
	var h = $(window).height();
	$("body").append("<div id='detailView'><div>"+html+"</div></div>");
	var show_x = $(obj).offset().left + $(obj).width();
	var show_y = $(obj).offset().top;
	var scroll_y = $(window).scrollTop();
	$("#detailView").css({
		position:"absolute",
		padding:"4px",
		border:"1px solid #f3f3f3",
		backgroundColor:"#eeeeee",
		top:show_y + "px",
		left:show_x + "px",
		'text-align':'left',
		zIndex:1000
	});
	$("#detailView > div").css({
		padding:"5px",
		backgroundColor:"white",
		border:"1px solid #cccccc"
	});
	if (show_y + 230 > h + scroll_y) {
		$("#detailView").css("bottom", h - show_y - $(obj).height() + "px").css("top", "auto");
	} else {
		$("#detailView").css("top", show_y + "px").css("bottom", "auto");
	}
	$("#preview").fadeIn("fast");
	$(obj).mouseout(function(){
		$("#detailView").remove();
	})
}

function getsumList(obj){
	if($(".dialog:visible").find("tbody[class='xtabletbody']").length > 0){
		var tbs = $(".dialog:visible").find("tbody[class='xtabletbody']");
	}else{
		var tbs = navTab.getCurrentPanel().find("tbody[class='xtabletbody']");
	}
	idx = tbs.find("tr").length;
	curtr = tbs.find("tr")[idx-1];
	str = curtr.innerHTML.replace('刷新','加载中......');
	curtr.innerHTML=str;
    
    $.ajax({
		type: "POST",
		url:obj,
		data:onloadPageRequestnavTab,
		global:true,
		success:function(data){
			if(data=="notok"){
	            alertMsg.error(data);
	        }else{
	        	curtr.remove();
				tbs.append(data);
	        }
		}	
	});
}