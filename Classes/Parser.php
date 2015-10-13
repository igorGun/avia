<?php

class Parser {

	private $uagent = '';
	private $ch = false;
	public $content = '';
	public $select = '';
	public $requestPost = array();
	public $form = '';
	public $ways = array();
	public $fromDateFieldName = '';
	public $toDateFieldName = '';

	public function __construct($url, $post = '') {
		$this->changeBrowserUA();
		$this->get_page($url, $post);
		$this->url = $url;
	}

	public function get_page($url,$post=0,$head=0,$setcooc=0){
		$this->ch = curl_init();

	    curl_setopt($this->ch, CURLOPT_URL, $url);
	    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($this->ch, CURLOPT_HEADER, 0);
	    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
	    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->uagent);    //прикидываемся браузером
	    curl_setopt($this->ch, CURLOPT_TIMEOUT, 120);
	    curl_setopt($this->ch, CURLOPT_FAILONERROR, 1);
	    curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1);
	    curl_setopt($this->ch, CURLOPT_COOKIEJAR, dirname(__FILE__).DS.'cookies.txt');
	    curl_setopt($this->ch, CURLOPT_COOKIEFILE, dirname(__FILE__).DS.'cookies.txt');
		if(!empty($post)){
		    curl_setopt($this->ch, CURLOPT_POST, 1);
	        curl_setopt($this->ch, CURLOPT_POSTFIELDS,http_build_query ($post));
		}
		if(!empty($setcooc)){
			//curl_setopt($this->ch, CURLOPT_COOKIE, $setcooc);
		}
		if(!empty($head)){

			curl_setopt($this->ch, CURLOPT_HTTPHEADER,$head);
		}
		$this->content = curl_exec( $this->ch );
	    curl_close($this->ch);
		 
	    return $this;
	}

	public function changeBrowserUA() {
		$this->uagent = chooseBrowser();		
	}

	public function createDom() {
		$this->dom = str_get_html($this->content);
		return $this;
	}

	public function createRequestURL() {
		if($this->form === '') $this->form = $form = $this->dom->find('form', 0);
		$action = false;

		if(strpos($form->action, '/') === false ) {
		 	$url = explode('/', $this->url);
		 	$url[count($url) - 1] = $form->action;
		 	$action = implode('/', $url);
		 } else {
		 	echo 'has another action attr';
		 }

		 $this->action = $action;

		 return $this;
	}

	public function createRequestPostArray() {
		$resources = $this->form->find('input[type!=submit]');
		foreach($resources as $res){
			if($res->tag == 'input') {
				$this->requestPost[$res->name] = $res->value;
			}
		}

		return $this;
	}

	public function waysList($notValue) {

		 $this->select = $select = $this->form->find('select', 0);

		 $options = $select->find('option');

		 foreach($options as $option) {
		 	if($option->value == $notValue) continue;
		 	$wayName = trim($option->plaintext);
		 	$this->ways[$wayName] = $option->value;
		 }

		 return $this;
	}

	public function setDate($interval) {
		$this->startdate = date('d.m.Y');
		$datetime = new DateTime();
		$datetime->add(date_interval_create_from_date_string($interval));
		$this->enddate = $datetime->format('d.m.Y');
		$this->requestPost[$this->fromDateFieldName] = $this->startdate;
		$this->requestPost[$this->toDateFieldName] = $this->enddate;
	}

	public function getCurrentWayResult($way) {
		$this->requestPost[$this->select->name] = $way;
		return $this->get_page($this->action, $this->requestPost)->content;
	}

	public function addPostData($postArr) {
		foreach ($postArr as $key => $value) {
			$this->requestPost[$key] = $value;
		}

		return $this;
	}


}

?>