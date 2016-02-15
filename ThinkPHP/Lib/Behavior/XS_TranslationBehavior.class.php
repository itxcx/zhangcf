<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id: ContentReplaceBehavior.class.php 2777 2012-02-23 13:07:50Z liu21st $

/**
 +------------------------------------------------------------------------------
 * 系统行为扩展 模板内容输出替换
 +------------------------------------------------------------------------------
 */
class XS_TranslationBehavior extends Behavior {
    // 行为扩展的执行入口必须是run
  
    public function run(&$content){
    	if(isset($_COOKIE['languglo']) && $_COOKIE['languglo']==3){
    		 $content = $this->templateContentReplace($content);
    	}else{
        $content;}
    }

    /**
     +----------------------------------------------------------
     * 模板内容替换
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $content 模板内容
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function templateContentReplace($content) {
    
    	if(APP_NAME=='DmsAdmin' && GROUP_NAME=='User') 
    	{
    		$youdao=new translate();
    		//在显示期间是否发生过修改;
    		$isEdit=false;
			$data=include(LANG_PATH.'/templateData.php');
			if($data===false)
			{
				$isEdit=true;
				$data=array();
			}
    		$t_content = $content;
    		$t_content = preg_replace('/<script.*<\/script>/Us', '', $t_content); 
    		$t_content = preg_replace('/<style.*<\/style>/Us', '', $t_content); 
    		preg_match_all('/>([^<>]+)<\/(?!script|style)/Us',$t_content,$truevals,PREG_SET_ORDER);
    		ini_set('display_error','on');
    		
    		foreach($truevals as $trueval)
    		{
    			$text=trim($trueval[1]);
    			$text=str_replace('-->','',$text);
    			if($text!='' && $text!='&nbsp;')
    			{
    				if(!isset($data[$text]))
    				{
    					$isEdit=true;
    					$tret=json_decode($youdao->init($text));
    					if($tret)
    						$data[$text]=$tret->translation[0];
    				}
    				if(isset($data[$text]) && preg_match('/[x{4e00}-\x{9fa5}]/u',$text))
    				{
    					$reg_text = preg_quote($text);
    					//$reg_text = str_replace('/','\\/',$text);
						$reg_text=preg_quote($reg_text);
						$reg_text=str_replace('/','\/',$reg_text);
    					$content  = preg_replace('/(?<![x{4e00}-\x{9fa5}"\'])'.$reg_text.'(?![x{4e00}-\x{9fa5}"\'])/u',$data[$text],$content);
    				}
    			}	
    		}
			preg_match_all("/<input[^>]*\>/i",$content,$outs);
			foreach($outs[0] as $out)
			{
				preg_match("/type\s*=\s*['\"]?([^\s\>'\"]+)['\"].*?/i",$out,$out2);
				$name=$this->gettag($out,'type');
				
				if($name['1']=="button" or $name['1']=="submit"){
					
					$out3=$this->gettag($out,'value');
					if(!isset($data[$out3['1']]))
					{
						$tret1=json_decode($youdao->init($out3['1']));
						$data[$out3['1']]=$tret1->translation[0];
						
					}
					$content=str_replace($out3['1'],$data[$out3['1']],$content);
					$isEdit=true;
				}
			}
			if(preg_match_all("/^([x{4e00}-\x{9fa5}a-zA-Z0-9，。．]+)$/u",$content,$outs))
			{
				foreach($outs[0] as $out)
				{
					if(!isset($data[$out]))
					{
    					$isEdit=true;
    					$youdao=new translate();
    					$tret=json_decode($youdao->init($out));
    					$data[$out]=$tret->translation[0];    					
					}
					if(isset($data[$out]))
    				{
						$content = str_replace($out,$data[$out],$content);
					}
				}
			}
			//([x{4e00}-\x{9fa5}a-zA-Z0-9]+)\'\)
			if(preg_match_all("/html\(\"([x{4e00}-\x{9fa5}a-zA-Z0-9]+)\"\)/u",$content,$outs))
			{
				foreach($outs[1] as $out)
				{
					if(!isset($data[$out]))
					{
    					$isEdit=true;
    					$youdao=new translate();
    					$tret=json_decode($youdao->init($out));
    					$data[$out]=$tret->translation[0];    					
					}
					if(isset($data[$out]))
    				{
						$content = str_replace($out,$data[$out],$content);
					}
				}
			}
			if(preg_match_all("/alert\(\"([x{4e00}-\x{9fa5}a-zA-Z0-9]+)\"\)/u",$content,$outs))
			{
				foreach($outs[1] as $out)
				{
					if(!isset($data[$out]))
					{
    					$isEdit=true;
    					$youdao=new translate();
    					$tret=json_decode($youdao->init($out));
    					$data[$out]=$tret->translation[0];    					
					}
					if(isset($data[$out]))
    				{
						$content = str_replace($out,$data[$out],$content);
					}
				}
			}			
			if(preg_match_all("/confirm\(\'([x{4e00}-\x{9fa5}a-zA-Z0-9\?]+)\'\)/u",$content,$outs))
			{
				foreach($outs[1] as $out)
				{
					if(!isset($data[$out]))
					{
    					$isEdit=true;
    					$youdao=new translate();
    					$tret=json_decode($youdao->init($out));
    					$data[$out]=$tret->translation[0];    					
					}
					if(isset($data[$out]))
    				{
						$content = str_replace($out,$data[$out],$content);
					}
				}
			}
			if(preg_match_all("/val\(\'([x{4e00}-\x{9fa5}a-zA-Z0-9\?]+)\'\)/u",$content,$outs))
			{
				foreach($outs[1] as $out)
				{
					if(!isset($data[$out]))
					{
    					$isEdit=true;
    					$youdao=new translate();
    					$tret=json_decode($youdao->init($out));
    					$data[$out]=$tret->translation[0];    					
					}
					if(isset($data[$out]))
    				{
						$content = str_replace($out,$data[$out],$content);
					}
				}
			}
    		if($isEdit)
    		{
    			F('templateData',$data,LANG_PATH);
    		}
    		return $content;
    	}
    	else
    	{
    		return $content;
    	}
    }
	public function gettag($html,$tagname)
	{
		preg_match("/$tagname\s*=\s*['\"]?([^\s\>'\"]+)['\"].*?/",$html,$out2);
		return $out2;
	}

}
	
class translate{
 
	//开发者必须设置的参数
	protected	$apikey			= '879367895';	//从有道申请的APIKEY
	protected	$keyFrom		= 'Axinshang';//申请APPKEY时，填表的网站名称的内容
												//注意： $keyFrom 需要是【连续的英文、数字的组合】
	//开发者可以默认的参数										
	protected	$cacheSwitch	= false;		//是否开启缓存
	protected	$cacheTime		= 60;			//开启缓存情况下，缓存时间
	protected	$iconv			= true;			//用于UTF8格式的编码中
	protected	$autoCut		= false;		//是否开启自动截取字符串
	protected 	$doctype		= 'json';		//你希望得到请求接口返回的格式
												//可选： xml或json或jsonp					
	//开发者可以忽略的参数	
	public		$mc;
	public		$err_code		= 0;
	public		$err_message	= '';
	protected	$apiurl			= 'http://fanyi.youdao.com/fanyiapi.do?type=data&version=1.1';
 
 
	public function __construct( $key=NULL ){
		if( $key )
			return $this->init( $key );
	}
	public function init( $key ){
		if(mb_strlen($key)>200){
			if( $this->autoCut )
				$query = mb_substr( $key , 0 , 200 );
			else
				return $this->error( -1 , 'query string is too long , please be less than 200)' );
		}
		else{
			$query = $key;
		}
		/*if($this->iconv){
			$query = iconv('GBK','UTF-8', $query );
		}	*/	
		//organize apiurl
		$url = $this->apiurl;
		$url.= '&doctype='.$this->doctype.'&keyfrom='.$this->keyFrom;
		$url.= '&q='.$query.'&key='.$this->apikey;
		//request api interface and rebuilt response
		$result = $this->todo( $url );
		if( $result )
			//print result
			return $this->printf( $result );
	}
	private function todo( $url ){
		if( $this->cacheSwitch ){
			$result = $this->mcGet( $url );
		}
		if(!isset($result) || !$result ){
			$url .= '&rand_num='.rand();
			$data = $this->curlGet( $url );
			if( !$data ){
				return $this->error( -2 , 'query api interface failure' );
			}
			//organize api response
			$result = $this->organize( $data );
		}
 
		if( $result && $this->cacheSwitch ){
			$this->mcSet( $url , $result );
		}
 
		return $result;
	}
	private function organize( $data ){
	/**
	 *	更细一步的处理有道接口返回的数据
	 *	用户可根据自己的需求，对API接口返回的数据，进行更详细的处理操作
	 *
	 *	@param	string	$data		//接口直接返回的数据
	 *	@return string/array		//根据用户处理的结果
	 *	@author	pangee
	 */
 
		return $data;
	}
	private function printf( $result ){
	/**
	 *	返回结果处理
	 *	用户根据自己的需求，修改返回结果格式
	 *
	 *	@param 	uncertain $result	//通过$this->organize处理过的数据
	 *	@return uncertain			//根据用户处理的显示
	 *	@author pangee
	 */
 
		//我就直接输出结果了。
		//如果你想，可以进行写入MYSQL操作等等
		return $result;
	}
 
 
	protected function error( $code , $message ){
		$this->err_code = $code;
		$this->err_message = $message;
		return false;
	}
	protected function mc(){
		if( !$this->mc ){
			$this->mc = memcache_init();
		}
		return $this->mc;
	}
	protected function mcGet( $key ){
		$mc = $this->mc();
		$md5key = substr( md5( $key.'_translate' ) , 4 , 16 );
		$lifeKey = $md5key.'_t';
		$life = memcache_get( $mc , $lifeKey );
		if( $_SERVER['REQUEST_TIME'] - $life > $this->cacheTime ){
			return false;
		}else{
			return memcache_get( $mc , $key );
		}
	}
	protected function mcSet( $key , $value ){
		$mc = $this->mc();
		$md5key = substr( md5( $key.'_translate' ) , 4 , 16 );
		$lifeKey = $md5key.'_t';
		memcache_set( $md5key , $value );
		memcache_set( $lifeKey , $_SERVER['REQUEST_TIME'] );
		return true;
	}
	protected function curlGet($url, $head = null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$rs = curl_exec($ch);
		curl_close($ch);
		return $rs;
	}
 
}
?>