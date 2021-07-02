<?php


namespace MAM\Plugin;

// Singleton class
class Config
{

    /**
     * @var string The plugin path (eg: use for require templates).
     */
    public $plugin_path;
    /**
     * @var string The plugin url (eg: use for enqueue css/js files).
     */
    public $plugin_url;
    /**
     * @var string The name (eg: use for adding links to the plugin action links).
     */
    public $plugin_basename;
    /**
     * @var array The list of currencies.
     */
    public $currencies;

    /**
     * @var string Used to get actual page URL
     */
    public $actual_url;

    /**
     * @var Config Used for singleton class setup
     */
    private static $instance;

    /**
     * Construct base configs
     */
    private final function __construct()
    {
        $this->plugin_url = plugin_dir_url(__DIR__);
        $this->plugin_path = plugin_dir_path(__DIR__);
        $this->plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . '/mam-reaxml-properties.php');
        $this->actual_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * get Instance of the class
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}