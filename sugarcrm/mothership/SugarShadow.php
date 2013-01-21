<?php
/**
 *
 * Wrapper class for enabling Shadow
 * @author mitani
 *
 */
class SugarShadow
{
	protected $key = null;
	protected $server = null;

	/**
	 *
	 * Constructor should only be called on by shadow function
	 * @param STRING $server
	 */
	private function __construct($server){
		$this->config = array(
			'shadow' => array('instancePath'=>'/mnt/sugar/shadowed', 'addHost'=>true, 'createDir' => true, 'siTemplate' => '/mnt/sugar/config_si.php', 'ip' => '127.0.0.1'),
		);

		$this->server = $server;
		$this->key = md5($server);

                $path = $this->config['shadow']['instancePath'] . '/' . $_SERVER['SERVER_NAME'];
                if ($_SERVER ['REMOTE_ADDR'] != "127.0.0.1" && file_exists($path . "/maintenance.txt")) {
                        die("<html><head><title>Under Maintence</title></head><body><center>This instance is currently under maintenance</center></body></html>");
                }
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
	 * Just look for the directory or a symlink to a instance directory
	 *
	 */
	protected function getServerInfo(){
		if (empty($data)) {
			$path = $this->config['shadow']['instancePath'] . '/' . $_SERVER['SERVER_NAME'];
			if (is_dir($path) || is_link($path)) {
				$data = array(
					'key' => $this->key,
					'path' => $path,
					'server' => $_SERVER['SERVER_NAME'],
					);
				//apc_store($this->key, $data);
			}
		}
		return $data;
	}

	/**
	 *
	 * Enables Shadowing on a Sugar Server
	 * @param STRING $server
	 */
	static function shadow($server, $templatePath = null)
	{
		if(empty($templatePath) && !empty($_SERVER['DOCUMENT_ROOT'])) {
			$templatePath = $_SERVER['DOCUMENT_ROOT'];
		}
		if(empty($templatePath) && !empty($_SERVER['SHADOW_ROOT'])) {
			$templatePath = $_SERVER['SHADOW_ROOT'];
		}
		$shadow = new SugarShadow($server);
		$info = $shadow->getServerInfo();
		if(empty($templatePath) || empty($info)){
			die ('<h3>Invalid SugarCRM Instance</h3>');
		}else{
			if(!file_exists($info['path'])){
				if($this->config['shadow']['createDir']) {
					$shadow->createInstance($info['path']);
				} else {
					die ('<h3>Invalid SugarCRM Instance</h3>');
				}
			}
			shadow($templatePath, $info['path'], array('cache', 'upload', 'config.php'));
		}
	}
}

SugarShadow::shadow($_SERVER['SERVER_NAME'], $_SERVER['DOCUMENT_ROOT']);
