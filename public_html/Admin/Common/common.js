//获取ajax对象
function getXMLRequester( )
{    
	var xmlhttp_request = false;    
	try
	{  
		if( window.ActiveXObject )
		{            
			for( var i = 5; i; i-- )
			{  
				try
				{                   
					if( i == 2 )
					{
						xmlhttp_request = new ActiveXObject( "Microsoft.XMLHTTP" );                       
					}
					else
					{    
						xmlhttp_request = new ActiveXObject( "Msxml2.XMLHTTP." + i + ".0" ); 
						xmlhttp_request.setRequestHeader("Content-Type","text/xml");
						xmlhttp_request.setRequestHeader("Content-Type","gb2312");
					}
					break;
				}
				catch(e)
				{   
					xmlhttp_request = false;
				}
			}
		}
		else if( window.XMLHttpRequest )
		{
			xmlhttp_request = new XMLHttpRequest();           
			if (xmlhttp_request.overrideMimeType) 
			{   
				xmlhttp_request.overrideMimeType('text/xml');            
			}
		}
	}
	catch(e)
	{        
		xmlhttp_request = false;   
	}    
	return xmlhttp_request ;
}

function fleshVerify()
{
	//重载验证码
	$('#verifyImg').attr("src", _APP_ + '/Public/verify/'+new Date().getTime());
}

//显示快捷搜索
function showQuickSearch()
{
	$('#quick_search').show();

	if( $('#quick_search_val').val() != '' )
	{
		var xmlHttp=getXMLRequester();
		var url = _APP_ + '/QuickSearch/index/value/'+$('#quick_search_val').val()+"/rn/"+Math.random();
		xmlHttp.onreadystatechange=function()
		{
			if (xmlHttp.readyState==4 && xmlHttp.status==200)
			{ 
				var xmlstr=xmlHttp.responseText;
				$('#quick_search_content').html(xmlstr).initUI(); //初始化UI

				//绑定a 点击事件
				$('#quick_search_content a').bind('click',function(){
					isMouseoverQuickContent = false;
					$('#quick_search').hide();
				});

				//绑定重新搜索功能
				$("#quick_search_content a[target='search']").bind('click',function()
				{
					isMouseoverQuickContent = true;
					$('#quick_search_val').val( $(this).attr('search') );
					showQuickSearch();
					return false;
				});
			}
		}
						
		xmlHttp.open("GET",url,true);
		xmlHttp.send(null);
	}
	else
	{
		$('#quick_search').hide();
	}
}

//关闭快捷搜索
function closeQuickSearch()
{
	$('#quick_search').hide();
}
//执行关闭快捷搜索
function doCloseQuickSearch()
{
	$('#quick_search').hide();
}