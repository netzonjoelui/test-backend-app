<?php
/*======================================================================================
	
	class:		CHistoryMan

	Purpose:	Page manages the ongoing navigation and history of user using sessions
				This can be used to provide "Go Back" functionality and track funnels

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.

	Deps:		If you are using CSessions make sure it is included first

	Usage:		$historyman->get_history_back()

	Variables:	1.	$ALIB_CACHE_DIR (defaults to /tmp, only used if memcached is not used)

======================================================================================*/
@session_start();

$historyman=new history_links(
	0,	#unique for several classes on one page
	(isset($max_url_history)?$max_url_history:100),	#the maximal history
	(isset($arr)?$arr:array()),
	(isset($back_next_param)?$back_next_param:1)
);

class history_links{
	var $arr;
	var $max_arr;
	var $request_uri;
	var $request_method;
	var $request_query;
	var $class_ident;
	var $arr_select=
		array(
			'start'=>'',			#Start page for tracking, absolute "url" is recommended
			'name_back_param'=>'b',	#Name for "back" parameter (default: b) -> page.html?b=1
			'name_next_param'=>'n',	#Name for "next" parameter (default: n) -> page.html?n=1
			'header_param'=>'h_p',	#Name for "header()" (default: h_p) -> header("Location: page.html?h_p");
			'page_back'=>array(),	#Skip of page if back step
			'page_full'=>array(),	#Skip of page if back step or next step
			'param_back'=>array(),	#Skip of page if back step -> the page "page.html?param2=1" will be skipped
			'param_full'=>array(),	#Skip of page if back step or next step -> the page "page.html?param4=1" will be skipped
			'value_back'=>array(),	#Skip of page if back step -> the page "page.html?param=content3" will be skipped
			'value_full'=>array(),	#Skip of page if back step or next step -> the page "page.html?param=content4" will be skipped
			'post'=>0				#Will track page without "POST-request" (default: 0, always will skip page with POST)
		);
	var $next=0;
	var $back=0;
	var $page_start='';
	var $is_back_next_param=1;		#Use of additional parameters in URL (name_back_param,name_next_param) 
									#if 0, not use additional parameters in URL
									#!!!!! Correctly works only for one step !!!!!
	/**
	* constructor
    *
    * @param string $max (the maximal history)
	* @param integer $class_ident (must be unique for several classes on one page)
	* @param array(string, array(string), ...) $arr_select (the description above on page)
	* @param integer $back_next_param (Use of additional parameters in URL), if 0 - not use additional parameters in URL
	*/
	function history_links($class_ident=0,$max=100,$arr_select=array(),$back_next_param=1){
		
		$this->request_uri=$_SERVER['REQUEST_URI'];
		$this->request_method=$_SERVER['REQUEST_METHOD'];
		$this->request_query=$_SERVER['QUERY_STRING'];
		$this->class_ident=intval($class_ident);
		$this->max_arr=$max;
		$this->is_back_next_param=$back_next_param;
		
		$this->arr_select=array_merge($this->arr_select,$arr_select);
		
		#set back and step
		if(isset($_GET[$this->arr_select['name_back_param']]))$this->back=intval($_GET[$this->arr_select['name_back_param']]);
		if(isset($_GET[$this->arr_select['name_next_param']]))$this->next=intval($_GET[$this->arr_select['name_next_param']]);
		
		if(!isset($_SESSION['history_links'][$this->class_ident]))$this->set_history_start(1);
		$this->arr=$_SESSION['history_links'][$this->class_ident];
		
		#header
		if(isset($_GET[$this->arr_select['header_param']])){
			if(isset($_GET[$this->arr_select['name_back_param']])){
				header("location: ".$this->get_history_back($_GET[$this->arr_select['name_back_param']]));
				exit;
			}
			if(isset($_GET[$this->arr_select['name_next_param']])){
				header("location: ".$this->get_history_next($_GET[$this->arr_select['name_next_param']]));
				exit;
			}
		}
		
		$this->_set_history($_SESSION['history_links'][$this->class_ident]);
	}
	/**
	* Processing URL of page
	*
	* @param &array(string) $arr_history (array of a history of navigation on pages)
	* @access   private
	*/
	function _set_history(&$arr_history){
		if(!isset($this->request_uri))return;
		
		
		#check of a step back
		if($this->back){
			if($this->is_history_back($this->back))$this->arr[0][0]-=$this->back;
			else $this->arr[0][0]=0;
			if($this->is_always_back($this->back))$this->arr[0][1]-=$this->back;
			else $this->arr[0][1]=0;
		}
		
		#check of a step next
		elseif($this->next){
			if($this->is_history_next($this->next))$this->arr[0][0]+=$this->next;
			else $this->arr[0][0]=count($this->arr[1])-1;
			if($this->is_always_next($this->next))$this->arr[0][1]+=$this->next;
			else $this->arr[0][1]=count($this->arr[2])-1;
		}
		
		else{
			$this->_set_history_track_always();
			$this->_set_history_track();
		}
		$arr_history=$this->arr;
	}
	/**
	* Processing URL of page (normal history)
	*
	* @access   private
	*/
	function _set_history_track(){
		#tracking start page
		if(!empty($this->arr_select['start']) and preg_match("'".preg_quote($this->arr_select['start'],"'")."$'",$this->request_uri)){
			$this->arr[1][0]=$this->request_uri;
			$this->arr[0][0]=0;
		}
		
		#if back step
		elseif(
			$this->arr[0][0] and 
			!strcasecmp($this->request_method, 'get') and 
			!strcmp($this->request_uri, $this->arr[1][$this->arr[0][0]-1]) 
			){
			$this->arr[0][0]--;
		}
		
		#if next step
		elseif(
			isset($this->arr[1][$this->arr[0][0]+1]) and 
			!strcasecmp($this->request_method, 'get') and 
			!strcmp($this->request_uri, $this->arr[1][$this->arr[0][0]+1]) 
			){
			$this->arr[0][0]++;
		}
		
		else{
			$this->_skip_processing(
				$this->arr[1],
				$this->arr[0][0],
				$this->request_uri,
				$this->request_query
			);
		}
	}
	/**
	* Processing URL of page (always history)
	*
	* @access   private
	*/
	function _set_history_track_always(){
		#always tracking start page (uncomment if neddet)
		/*
		$always_uri=$this->_cut_back_next_string($this->request_uri);
		if(!empty($this->arr_select['start']) and preg_match("'".preg_quote($this->arr_select['start'],"'")."$'",$always_uri)){
			$this->arr[2][0]=$always_uri;
			$this->arr[0][1]=0;
		}
		else 
		*/
		#if back step
		if(
			$this->is_back_next_param!=1 and 
			$this->arr[0][1] and 
			!strcasecmp($this->request_method, 'get') and 
			!strcmp($this->request_uri, $this->arr[2][$this->arr[0][1]-1]) 
			){
			$this->arr[0][1]--;
		}
		
		#if next step
		elseif(
			$this->is_back_next_param!=1 and 
			isset($this->arr[2][$this->arr[0][1]+1]) and 
			!strcasecmp($this->request_method, 'get') and 
			!strcmp($this->request_uri, $this->arr[2][$this->arr[0][1]+1]) 
			){
			$this->arr[0][1]++;
		}
		
		else{
			#always tracking
			$this->_skip_processing(
				$this->arr[2],
				$this->arr[0][1],
				$this->_cut_back_next_string($this->request_uri),
				$this->_cut_back_next_string($this->request_query)
			);
			#(always) fix dublicate
			if(
				$this->arr[0][1]>0 and 
				count($this->arr[2])==$this->arr[0][1]+1 and 
				!strcmp($this->arr[2][$this->arr[0][1]],$this->arr[2][$this->arr[0][1]-1])
				){
				$this->arr[2]=array_slice($this->arr[2], 0, $this->arr[0][1]);
				$this->arr[0][1]--;
			}
		}
	}
	/**
	* To compare for ignoring or skip
	*
	* @param 	array (string) (history array) 
	* @param 	integer $index (step)
	* @param 	string $request_uri ($REQUEST_URI)
	* @param 	string $request_query ($REQUEST_QUERY)
	* @access   private
	*/
	function _skip_processing(&$arr,&$index,$request_uri,$request_query){
		#ignore itself
		if(!strcmp($request_uri, $arr[$index])){}
		
		#if skip
		elseif(
			!(!$this->arr_select['post'] and strcasecmp($this->request_method, 'get')) and
			!$this->_test_compare(array('\/','(\?|$)'),$this->arr_select['page_full'],$request_uri,$index) and
			!$this->_test_compare(array('(^|\&)','(\=|$)'),$this->arr_select['param_full'],$request_query,$index) and
			!$this->_test_compare(array('\=','(\&|$)'),$this->arr_select['value_full'],$request_query,$index)
			){
			
			#if replace
			$request_q=preg_split("/\?/",$arr[$index]);
			if(
				$this->_test_compare(array('\/','(\?|$)'),$this->arr_select['page_back'],$arr[$index],$index) or
				(isset($request_q[1]) and $this->_test_compare(array('(^|\&)','(\=|$)'),$this->arr_select['param_back'],end($request_q),$index)) or
				(isset($request_q[1]) and $this->_test_compare(array('\=','(\&|$)'),$this->arr_select['value_back'],end($request_q),$index))
				){
				$index--;
			}
			$index++;
			$arr=array_slice($arr, 0, $index+1);
			$arr[$index]=$request_uri;
			
			#if overflow of the array
			if(count($arr)>$this->max_arr){
				$first=array_shift($arr);
				$temp=array_shift($arr);
				array_unshift($arr,$first);
				$index--;
			}
		}
	}
	/**
	* preparation of a array for regular functions
	*
	* @param 	array (string) $arr 
	* @return	boolean (presence or absence of a array)
	* @access   private
	*/
	function _quote(&$arr){
		reset($arr);
        while(list($i,)=each($arr)){
			$arr[$i]=preg_quote($arr[$i], "/");
		}
		return (count($arr)!=0);
	}
	/**
	* Compare
	*
	* @param 	array 
	* @param 	&array $arr_select 
	* @param 	string $source 
	* @return	boolean
	* @access   private
	*/
	function _test_compare($arr_test,$arr_select,&$source,&$index){
		return (
			$index>0 and
			$this->_quote($arr_select) and
			preg_match("/".$arr_test[0]."((".implode(")|(",$arr_select)."))".$arr_test[1]."/",$source)
		);
	}
	function _cut_back_next_string($uri){
		if($this->back or $this->next){
			return preg_replace("/[\?\&]((".preg_quote($this->arr_select['name_back_param'],"/").")|(".preg_quote($this->arr_select['name_next_param'],"/")."))\=\d+$/","",$uri);
		}
		return $uri;
	}
	/**
	* Addition of parameter of step
	*
	* @param 	string $link
	* @param 	string $param
	* @param 	string $value
	* @return	string
	* @access   private
	*/
	function _add_back_param($link,$param,$value){
		if($this->is_back_next_param!=1)return $link;
		if(strrpos($link,'?')===false)return $link.'?'.$param.'='.$value;
		return $link.'&'.$param.'='.$value;
	}
		/**
	* URL for back_step
	*
	* @param	integer $back (back_step)
	* @param	array $arr (history array)
	* @param	integer $index (step)
	* @return	string 
	* @access   private
	*/
	function _get_history_back(&$back,&$arr,&$index){#$this->arr[1],$this->arr[0]
		$back=abs(intval($back));
		if($this->is_history_back($back))return $this->_add_back_param($arr[$index-$back],$this->arr_select['name_back_param'],$back);
		return $this->_add_back_param($arr[0],$this->arr_select['name_back_param'],$back);
	}
	/**
	* URL for next_step
	*
	* @param	integer $next (next_step)
	* @param	array $arr (history array)
	* @param	integer $index (step)
	* @return	string 
	* @access   private
	*/
	function _get_history_next(&$next,&$arr,&$index){#$this->arr[1],$this->arr[0]
		$next=abs(intval($next));
		if($this->is_history_next($next))return $this->_add_back_param($arr[$index+$next],$this->arr_select['name_next_param'],$next);
		return $this->_add_back_param(end($arr),$this->arr_select['name_next_param'],$next);
	}
	/**
	* URL exists?
	*
	* @param	integer $back (back_step)
	* @param	integer $index (step)
	* @return	boolean 
	* @access   private
	*/
	function _is_history_back(&$back,&$index){#$this->arr[0]
		$back=abs(intval($back));
		if($back<=$index)return true;
		return false;
	}
	/**
	* URL exists?
	*
	* @param	integer $next (next_step)
	* @param	array $arr (history array)
	* @param	integer $index (step)
	* @return	boolean 
	* @access   private
	*/
	function _is_history_next(&$next,&$arr,&$index){
		$next=abs(intval($next));
		if($next<count($arr)-$index)return true;
		return false;
	}
	/**
	* URL for back_step
	*
	* @param	integer $back (back_step)
	* @return	string 
	* @access   public
	*/
	function get_history_back($back=1){
		return $this->_get_history_back($back,$this->arr[1],$this->arr[0][0]);
	}
	/**
	* deprecated, see function get_history_back
	*/
	function get_history($back=1){
		return $this->get_history_back($back);
		#or 
		#return $this->get_always_back($back); #exact as in javascript:history.go()
	}
	/**
	* URL for next_step
	*
	* @param	integer $next (next_step)
	* @return	string 
	* @access   public
	*/
	function get_history_next($next=1){
		return $this->_get_history_next($next,$this->arr[1],$this->arr[0][0]);
	}
	/**
	* URL for next or back step
	*
	* @param	integer $go (next or back step), if $go>0 then next_step else back_step
	* @return	string 
	* @access   public
	*/
	function get_history_go($go=-1){
		$go=intval($go);
		return $go>0?$this->_get_history_next($go,$this->arr[1],$this->arr[0][0]):$this->_get_history_back(abs($go),$this->arr[1],$this->arr[0][0]);
	}
	/**
	* URL exists?
	*
	* @param	integer $back (back_step)
	* @return	boolean 
	* @access   public
	*/
	function is_history_back($back=1){
		return $this->_is_history_back($back,$this->arr[0][0]);
	}
	/**
	* URL exists?
	*
	* @param	integer $next (next_step)
	* @return	boolean 
	* @access   public
	*/
	function is_history_next($next=1){
		return $this->_is_history_next($next,$this->arr[1],$this->arr[0][0]);
	}
	/**
	* URL exists?
	*
	* @param	integer $go (next or back step)
	* @return	boolean 
	* @access   public
	*/
	function is_history_go($go=-1){
		$go=intval($go);
		return $go>0?$this->_is_history_next($go,$this->arr[1],$this->arr[0][0]):$this->_is_history_back(abs($go),$this->arr[0][0]);
	}
	/**
	* URL for step to start_page
	*
	* @return	string 
	* @access   public
	*/
	function get_history_backs(){
		return $this->get_history_back($this->max_arr);
	}
	/**
	* URL for step to end_page
	*
	* @return	string 
	* @access   public
	*/
	function get_history_nexts(){
		return $this->get_history_next($this->max_arr);
	}
	/**
	* URL for header_step
	*
	* @return	string 
	* @access   public
	*/
	function get_history_header(){
		$str=current(preg_split("/\?/",$this->request_uri));
		return $str.'?'.$this->arr_select['header_param'];
	}
	
	#######
	#Always step, exact as in javascript:history.go()
	#####################################
	/**
	* URL for "always" back_step
	*
	* @param	integer $back (back_step)
	* @return	string 
	* @access   public
	*/
	function get_always_back($back=1){
		return $this->_get_history_back($back,$this->arr[2],$this->arr[0][1]);
	}
	/**
	* URL for "always" next_step
	*
	* @param	integer $next (next_step)
	* @return	string 
	* @access   public
	*/
	function get_always_next($next=1){
		return $this->_get_history_next($next,$this->arr[2],$this->arr[0][1]);
	}
	/**
	* URL for "always" next or back step
	*
	* @param	integer $go (next or back)
	* @return	string 
	* @access   public
	*/
	function get_always_go($go=-1){
		$go=intval($go);
		return $go>0?$this->_get_history_next($go,$this->arr[2],$this->arr[0][1]):$this->_get_history_back(abs($go),$this->arr[2],$this->arr[0][1]);
	}
	/**
	* URL exists? ("always")
	*
	* @param	integer $back (back_step)
	* @return	boolean 
	* @access   public
	*/
	function is_always_back($back=1){
		return $this->_is_history_back($back,$this->arr[0][1]);
	}
	/**
	* URL exists? ("always")
	*
	* @param	integer $next (next_step)
	* @return	boolean 
	* @access   public
	*/
	function is_always_next($next=1){
		return $this->_is_history_next($next,$this->arr[2],$this->arr[0][1]);
	}
	/**
	* URL exists? ("always")
	*
	* @param	integer $go (next or back)
	* @return	boolean 
	* @access   public
	*/
	function is_always_go($go=-1){
		$go=intval($go);
		return $go>0?$this->_is_history_next($go,$this->arr[2],$this->arr[0][1]):$this->_is_history_back(abs($go),$this->arr[0][1]);
	}
	/**
	* URL for step to start_page
	*
	* @return	string 
	* @access   public
	*/
	function get_always_backs(){
		return $this->get_always_back($this->max_arr);
	}
	/**
	* URL for step to end_page
	*
	* @return	string 
	* @access   public
	*/
	function get_always_nexts(){
		return $this->get_always_next($this->max_arr);
	}
	######################################
	/**
	* Set start page
	*
	* @param	integer $clear (assign 1 to clear history array)
	* @access   public
	*/
	function set_history_start($clear=0){
		if($clear!=0)$_SESSION['history_links'][$this->class_ident][0]=array(0,0);
		
		#If session is lost
		if($this->back or $this->next){
			$param=$this->back?$this->arr_select['name_back_param']:$this->arr_select['name_next_param'];
			$this->request_uri=preg_replace("/[\?\&]".preg_quote($param,"/")."=\d+$/","",$this->request_uri);
		}
		
		if(!empty($this->arr_select['start']))$start=$this->arr_select['start'];
		else $start=$this->request_uri;
		
		if($clear!=0){
			$_SESSION['history_links'][$this->class_ident][1]=array($start);
			$_SESSION['history_links'][$this->class_ident][2]=array($start);
		}
		else{
			$_SESSION['history_links'][$this->class_ident][1][0]=$start;
			$_SESSION['history_links'][$this->class_ident][2][0]=$start;
		}
	}
	/**
	* Get tracker
	*
	* @param	integer $ident (assign 0 to get default, 1 to get "always")
	* @return	integer
	* @access   public
	*/
	function get_history_tracker($ident=0){
		return $_SESSION['history_links'][$this->class_ident][0][intval($ident)];
	}
	/**
	* Get history array
	*
	* @param	integer $ident (assign 0 to get default, 1 to get "always")
	* @return	array
	* @access   public
	*/
	function get_history_array($ident=0){
		return $_SESSION['history_links'][$this->class_ident][intval($ident)+1];
	}
}
?>
