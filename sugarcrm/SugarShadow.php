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

	function configCacheDir($ipath, $from)
	{
		$paths = array("", "layout", "csv", "import", "pdf", "feeds", "images", "upload", "xml");
		foreach($paths as $path) {
			mkdir("$ipath/cache/$path", 0755, true);
			copy("$from/cache/index.html", "$ipath/cache/$path/index.html");
		}
	}

	/**
	 * Creates all required instance directories
	 * Enter description here ...
	 * @param unknown_type $path
	 */
	protected function createInstance($path)
	{
		$this->configCacheDir($path, dirname(__FILE__));
		mkdir($path . '/custom', 0775, true);
		$this->createSiTempate($path);
	}

	/**
	 * Copy config_si.php with server instantiation
	 * @param string $path
	 */
	protected function createSiTemplate($path)
	{
	   if(!empty($this->config['shadow']['siTemplate']) && file_exists($this->config['shadow']['siTemplate'])) {
           require($this->config['shadow']['siTemplate']);
           $sugar_config_si['setup_db_database_name'] .= preg_replace("/[^A-Za-z0-9]+/","_",$server);
           $sugar_config_si['setup_site_url'] = str_replace('SERVER', $server, $sugar_config_si['setup_site_url']);
           $config = '<?php $sugar_config_si = '.var_export($sugar_config_si, true).";";
           file_put_contents($path."/config_si.php", $config);
       }
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
                if ($shadow->config['shadow']['createDir']) {
					$shadow->createInstance($info['path']);
				} else {
					die ('<h3>Invalid SugarCRM Instance</h3>');
				}
			}
			shadow(dirname(__FILE__),$info['path'], array('cache', 'config.php'));
		}


	}





}
