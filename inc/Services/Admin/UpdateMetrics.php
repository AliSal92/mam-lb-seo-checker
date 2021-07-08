<?php


namespace MAM\SEOChecker\Services\Admin;

use WP_Query;
use Exception;
use MAM\SEOChecker\Config;
use MAM\SEOChecker\Services\WPAPI\Endpoint;
use MAM\SEOChecker\Services\ServiceInterface;


class UpdateMetrics implements ServiceInterface
{

    /**
     * @var Endpoint
     */
    private $endpoint_api;


    public function __construct()
    {
        $this->endpoint_api = new Endpoint();
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        add_action('plugins_loaded', [$this, 'add_option_page']);
        add_action('plugins_loaded', [$this, 'add_custom_fields']);
        add_action('update_metrics', [$this, 'update_metrics']);

        try {
            $this->endpoint_api->add_endpoint('mam-update-metrics')->with_template('mam-update-metrics.php')->register_endpoints();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public static function add_custom_fields()
    {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_60dec07b2be3d',
                'title' => 'SEO Checker',
                'fields' => array(
                    array(
                        'key' => 'field_60dec082f2cbf',
                        'label' => 'API KEY',
                        'name' => 'api_key',
                        'type' => 'password',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_60dec082f2cff',
                        'label' => 'API KEY AHREF',
                        'name' => 'api_key_ahref',
                        'type' => 'password',
                        'instructions' => '<a href="https://ahrefs.com/web/oauth/authorize?client_id=Ahrefs%20SEO%20Wordpress%20plugin&redirect_uri=https%3A%2F%2Fahrefs.com%2Fweb%2Fwp-plugin%2Fapi-token&response_type=code&scope=api&state=ugtCtdtXxSqVbgzYyzpc">From Here</a>',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'seo-checker-settings',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));
        }
    }

    public static function add_option_page()
    {
        // Register the option page using ACF
        if (function_exists('acf_add_options_page')) {
            // parent page
            acf_add_options_page(array(
                'page_title' => 'SEO Checker Checker',
                'menu_title' => 'SEO Checker Checker',
                'menu_slug' => 'mam-seo-checker',
                'capability' => 'read',
                'redirect' => true
            ));

            // child page
            acf_add_options_sub_page(array(
                'page_title' => 'Settings',
                'menu_title' => 'Settings',
                'menu_slug' => 'seo-checker-settings',
                'capability' => 'read',
                'parent_slug' => 'mam-seo-checker'
            ));

        }
    }

    /**
     * @return WP_Query
     */
    public static function get_all_resources_ids()
    {
        // args
        $args = array(
            'numberposts' => -1,
            'posts_per_page' => -1,
            'post_type' => 'resources',
            'fields' => 'ids'
        );
        return new WP_Query($args);

    }

    /**
     * get the live metrics data from SEO SITE CHECKER
     * @param $website string the website url
     * @return array empty if no results
     */
    public static function get_live_metrics(string $website): array
    {
        $api_key = Config::getInstance()->api_key;
        $json = file_get_contents('https://websiteseochecker.com/api.php?api_key=' . $api_key . '&items=1&item0=' . $website);
        return json_decode($json, true);
    }

    /**
     * get the live metrics data from AHref
     * @param $website string the website url
     * @return array empty if no results
     */
    public static function get_live_metrics_ahref($website): array
    {
        $api_key = Config::getInstance()->api_key_ahref;
        $result = array();
        $json = file_get_contents('https://apiv2.ahrefs.com/?token=' . $api_key . '&limit=1&output=json&from=domain_rating&mode=subdomains&target=' . $website);
        $array = json_decode($json, true);
        $result['dr'] = $array['domain']['domain_rating'];
        $json = file_get_contents('https://apiv2.ahrefs.com/?token=' . $api_key . '&limit=1&output=json&from=refdomains&mode=subdomains&target=' . $website);
        $array = json_decode($json, true);
        $result['rd'] = $array['stats']['refdomains'];
        $json = file_get_contents('https://apiv2.ahrefs.com/?token=' . $api_key . '&limit=1&output=json&from=positions_metrics&mode=subdomains&target=' . $website);
        $array = json_decode($json, true);
        $result['ok'] = $array['metrics']['positions'];
        $result['tr'] = round($array['metrics']['traffic']);
        return $result;
    }

    /**
     * check if this is the correct time to update
     * @param $last_update string Date format YYYY-MM-DD
     * @return bool true when it's time to update false if it not the time to update
     */
    public static function time_to_update($last_update): bool
    {
        if (!$last_update) {
            return true;
        }
        if (strtotime($last_update) < strtotime('-1 week')) {
            return true;
        }
        return false;
    }

    /**
     * Update the resources metrics if an update is required (2 weeks)
     */
    public static function update_metrics($id = null)
    {
        //  update only one selectedresource
        if (isset($_GET['website'])) {
            $id = $_GET['website'];
            UpdateMetrics::update_resource_metrics($id);
        } if($id){
            UpdateMetrics::update_resource_metrics($id);
        }else {
            // update all resources
            $query = UpdateMetrics::get_all_resources_ids();
            if (!$query->have_posts()) {
                echo 'No resources found';
            }
            // Start looping over the query results.
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();

                $last_update = get_field('metrics_update_date', $id);
                $require_update = UpdateMetrics::time_to_update($last_update);
                if (!$require_update) {
                    continue;
                }

                UpdateMetrics::update_resource_metrics($id);
            }
        }
    }

    /**
     * Update the resource metrics by Post ID
     * @param $id int Poist ID
     */
    public static function update_resource_metrics($id){
        echo '#' . $id . ' (' . get_the_title($id) . '): Update Date ' . date('Y-m-d');

        // Website SEO Checker
        $live_metrics = UpdateMetrics::get_live_metrics(get_the_title($id));
        $live_metrics = $live_metrics[0];
        if (isset($live_metrics['Domain Authority']) && $live_metrics['Domain Authority'] != '') {
            update_field('da', $live_metrics['Domain Authority'], $id);
            echo ' - DA: ' . $live_metrics['Domain Authority'];
        }
        if (isset($live_metrics['Page Authority']) && $live_metrics['Page Authority'] != '') {
            update_field('pa', $live_metrics['Page Authority'], $id);
            echo ' - PA: ' . $live_metrics['Page Authority'];
        }
        if (isset($live_metrics['Trust flow']) && $live_metrics['Trust flow'] != '') {
            update_field('tf', $live_metrics['Trust flow'], $id);
            echo ' - TF: ' . $live_metrics['Trust flow'];
        }
        if (isset($live_metrics['Citation flow']) && $live_metrics['Citation flow'] != '') {
            update_field('cf', $live_metrics['Citation flow'], $id);
            echo ' - CF: ' . $live_metrics['Citation flow'];
        }

        // AHref Data
        $live_metrics = UpdateMetrics::get_live_metrics_ahref(get_the_title($id));
        if (isset($live_metrics['dr']) && $live_metrics['dr'] != '') {
            update_field('dr', $live_metrics['dr'], $id);
            echo ' - DR: ' . $live_metrics['dr'];
        }
        if (isset($live_metrics['rd']) && $live_metrics['rd'] != '') {
            update_field('rd', $live_metrics['rd'], $id);
            echo ' - RD: ' . $live_metrics['rd'];
        }
        if (isset($live_metrics['ok']) && $live_metrics['ok'] != '') {
            update_field('organic_keywords', $live_metrics['ok'], $id);
            echo ' - Organic Keywords: ' . $live_metrics['ok'];
        }
        if (isset($live_metrics['tr']) && $live_metrics['tr'] != '') {
            update_field('tr', $live_metrics['tr'], $id);
            echo ' - TR: ' . $live_metrics['tr'];
        }

        update_field('metrics_update_date', date('Y-m-d'), $id);
        echo '<br />';
    }
}