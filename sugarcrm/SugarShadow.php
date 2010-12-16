<?php
/**
 * 
 * Wrapper class for enabling Shadow
 * @author mitani
 *
 */
class SugarShadow{
	protected $key = null;
	protected $server = null;
	protected $mongo = null;
	
	/**
	 * 
	 * Constructor should only be called on by shadow function 
	 * @param STRING $server
	 */
	private function __construct($server){
		require('shadow.config.php');
		$this->config = $shadow_config;
		$this->server = $server;
		$this->key = md5($server);
	}
	
	/**
	 * Creates all required instance directories
	 * Enter description here ...
	 * @param unknown_type $path
	 */
	protected function createInstance($path){
		
		mkdir($path . '/cache/images', 0775, true);
		mkdir($path . '/cache/import', 0775, true);
		mkdir($path . '/cache/layout', 0775, true);
		mkdir($path . '/cache/pdf', 0775, true);
		mkdir($path . '/cache/upload', 0775, true);
		mkdir($path . '/cache/xml', 0775, true);
		mkdir($path . '/cache/smarty/templates_c', 0775, true);
		mkdir($path . '/custom', 0775, true);
	}
	
	/**
	 * 
	 * Generates the Mongo Connection based on the config file
	 */
	protected function mongoConnect(){
		if(empty($this->mongo)){
			$auth = $this->config['mongo']['server'] . ':' . $this->config['mongo']['port'];
			if(!empty($this->config['mongo']['username']) && !empty($this->config['mongo']['password'])){
				$auth  = $this->config['mongo']['username'] .':' . $this->config['mongo']['password'] .'@'. $auth;
			}
			$this->mongo  = new Mongo('mongodb://'. $auth);
		}
	}
	
	
	/**
	 * Pulls the server information from either mongo or an apc cache  
	 * 
	 */
	protected function getServerInfo(){
		$data = apc_fetch($this->key);
		if(empty($data)){
			$this->mongoConnect();
			$cursor = $this->mongo->exosphere->instances->find(array('key'=>$this->key));
			if($cursor->hasNext()){
				$data = $cursor->getNext();
				apc_store($this->key, $data);
			}
			
		}
		return $data;
	}
	
	/**
	 * 
	 * Enables Shadowing on a Sugar Server
	 * @param STRING $server
	 */
	static function shadow($server){
		$shadow = new SugarShadow($server);
		$info = $shadow->getServerInfo();
		if(empty($info)){
			die ('<h3>Invalid SugarCRM Instance</h3>');
		}else{
			if(!file_exists($info['path'])){
				$shadow->createInstance($info['path']);
			}
			shadow(dirname(__FILE__),$info['path'], array('cache', 'config.php'));
		}
		
		
	}
	
	
	
	
	
}
