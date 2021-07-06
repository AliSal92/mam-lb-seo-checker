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
    public static function get_live_metrics($website): array
    {
        $api_key = Config::getInstance()->api_key;
        $json = file_get_contents('https://websiteseochecker.com/api.php?api_key=' . $api_key . '&items=1&item0=' . $website);
        return json_decode($json, true);
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
    public static function update_metrics()
    {
        $query = UpdateMetrics::get_all_resources_ids();
        if (!$query->have_posts()) {
            echo 'No resources found';
        }
        // Start looping over the query results.
        while ($query->have_posts()) {
            $query->the_post();

            $last_update = get_field('metrics_update_date', get_the_ID());
            $require_update = UpdateMetrics::time_to_update($last_update);
            if (!$require_update) {
                continue;
            }


            $live_metrics = UpdateMetrics::get_live_metrics(get_the_title());
            $live_metrics = $live_metrics[0];

            echo '#' . get_the_ID() . ' (' . get_the_title() . '): Update Date ' . date('Y-m-d');
            update_field('metrics_update_date', date('Y-m-d'), get_the_ID());

            if (isset($live_metrics['Domain Authority']) && $live_metrics['Domain Authority'] != '') {
                update_field('da', $live_metrics['Domain Authority'], get_the_ID());
                echo ' - DA: ' . $live_metrics['Domain Authority'];
            }
            if (isset($live_metrics['Page Authority']) && $live_metrics['Page Authority'] != '') {
                update_field('pa', $live_metrics['Page Authority'], get_the_ID());
                echo ' - PA: ' . $live_metrics['Page Authority'];
            }
            if (isset($live_metrics['Trust flow']) && $live_metrics['Trust flow'] != '') {
                update_field('tf', $live_metrics['Trust flow'], get_the_ID());
                echo ' - TF: ' . $live_metrics['Trust flow'];
            }
            if (isset($live_metrics['Citation flow']) && $live_metrics['Citation flow'] != '') {
                update_field('cf', $live_metrics['Citation flow'], get_the_ID());
                echo ' - CF: ' . $live_metrics['Citation flow'];
            }
            echo '<br />';
        }
    }
}