<?php 
/**
 * 
 * SugarExosphere is used to manage the Mongo DB for SugarShadow
 * @author mitani
 *
 */
class SugarExosphere{
	protected $mongo = null;
	protected $config = null;
	
	/**
	 * 
	 * Constructor for SugarExospher 
	 */
	function __construct(){
		require('shadow.config.php');
		$this->config = $shadow_config;
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
	 * Adds a Server to the Mongo DB and adds it to the host file
	 * @param STRING $server
	 */
	function addInstance($server){
			$this->mongoConnect();
			$cursor = $this->mongo->exosphere->instances->insert(array('key'=>md5($server), 'server'=>$server, 'path'=>$this->getInstancePath() . '/'. $server));
			if(!empty($this->config['shadow']['addHost'])){
				$this->addToHostFile($server);
			}
	}
	

	
	/**
	 * 
	 * Returns the full path of the instances directory
	 */
	function getInstancePath(){
		return realpath($this->config['shadow']['instancePath']); 
		
	}
	
	/**
	 * 
	 * Adds a given server to the host file enabling it to be visible
	 * @param $server
	 */
	function addToHostFile($server){
		$fp = fopen('/etc/hosts','a');
		fwrite($fp, '127.0.0.1 ' . $server . "\n");
		fclose($fp);
		
	}
	
	/**
	 * 
	 * Displays all instances stroed in the db
	 */
	function show(){
		$this->mongoConnect();
		$cursor = $this->mongo->exosphere->instances->find();
		while($cursor->hasNext()){
			echo '<pre>';
			print_r($cursor->getNext());
			echo '</pre>';
		}
		
	}
	
	/**
	 * 
	 * Drops all instances from the DB
	 */
	function deleteAll(){
		$this->mongoConnect();
		$this->mongo->exosphere->instances->drop();
	}
	
	
	
	
}
$se = new SugarExosphere();

if(!empty($_REQUEST['do'])){
	$se = new SugarExosphere();
	switch($_REQUEST['do']){
		case 'add':
			$se->addInstance($_POST['server']);
			echo "<h2> instance added </h2>";
			break;
		case 'list':
			$se->show();
			break;
		case 'deleteall':
			$se->deleteAll();
			break;

	}
}
?>

<form method='POST'>
<input type='hidden' name='do' value='add'/>
Server:<input name='server' size='60'/> <input type='submit' value='Add'/>
</form>