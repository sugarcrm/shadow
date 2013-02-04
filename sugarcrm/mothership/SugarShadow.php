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
     * Constructor should only be called on by shadow function
     *
     * @param STRING $server
     */
    private function __construct ($server)
    {
        $this->config = array(
                'shadow' => array(
                        'instancePath' => '/mnt/sugar/shadowed',
                        'addHost' => true,
                        'createDir' => true,
                        'siTemplate' => '/mnt/sugar/config_si.php',
                        'ip' => '127.0.0.1'
                )
        );

        $this->server = $server;
        $this->key = md5($server);

        $path = $this->config['shadow']['instancePath'] . '/' . $_SERVER['SERVER_NAME'];

        if ($_SERVER['REMOTE_ADDR'] != "127.0.0.1" && file_exists($path . "/maintenance.txt")) {
            die("<html><head><title>Under Maintence</title></head><body><center>This instance is currently under maintenance</center></body></html>");
        }
    }

    /**
     * Just look for the directory or a symlink to a instance directory
     */
    protected function getServerInfo ()
    {
        if (empty($data)) {
            $path = $this->config['shadow']['instancePath'] . '/' . $_SERVER['SERVER_NAME'];
            if (is_dir($path) || is_link($path)) {
                $data = array(
                        'key' => $this->key,
                        'path' => $path,
                        'server' => $_SERVER['SERVER_NAME']
                );
                // apc_store($this->key, $data);
            }
        }
        return $data;
    }

    /**
     * Enables Shadowing on a Sugar Server
     *
     * @param STRING $server
     */
    static function shadow ($server, $templatePath = null)
    {
        if (empty($templatePath) && ! empty($_SERVER['DOCUMENT_ROOT'])) {
            $templatePath = $_SERVER['DOCUMENT_ROOT'];
        }
        if (empty($templatePath) && ! empty($_SERVER['SHADOW_ROOT'])) {
            $templatePath = $_SERVER['SHADOW_ROOT'];
        }
        $shadow = new SugarShadow($server);
        $info = $shadow->getServerInfo();
        if (empty($templatePath) || empty($info)) {
            die('<h3>Invalid SugarCRM Instance</h3>');
        } else {
            if (! file_exists($info['path'])) {
                die('<h3>Invalid SugarCRM Instance</h3>');
            }
            shadow($templatePath, $info['path'], array('cache', 'upload', 'config.php'));
        }
    }
}

SugarShadow::shadow($_SERVER['SERVER_NAME'], $_SERVER['DOCUMENT_ROOT']);
