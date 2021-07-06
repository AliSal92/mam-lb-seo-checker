<?php
/**
 * Plugin Name: MAM Linkbuilding SEO CHECKER ADD ON
 * Plugin URI: https://github.com/AliSal92/mam-lb-seo-checker
 * Description: Used as an add-on for mam/linkbuilding plugin to auto update the resources metrics using SEO CHECKER API
 * Version: 1.0
 * Author: AliSal
 * Text Domain: mam-lb-seo-checker
 * Author URI: https://github.com/AliSal92/
 * MAM Linkbuilding SEO CHECKER ADD ON System is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * MAM Linkbuilding SEO CHECKER ADD ON System is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MAM Linkbuilding SEO CHECKER ADD ON System. If not, see <http://www.gnu.org/licenses/>.
 */

namespace MAM;

use MAM\SEOChecker\Init;


/**
 * Prevent direct access
 */
defined('ABSPATH') or die('</3');


/**
 * Require once the Composer Autoload
 */
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

/**
 * Initialize and run all the core classes of the plugin
 */
if (class_exists('MAM\SEOChecker\Init')) {
    Init::register_services();
}

function mam_lb_seo_checker_activation() {
    if ( ! wp_next_scheduled( 'update_metrics' ) ) {
        wp_schedule_event( time(), 'daily', 'update_metrics' );
    }
}
register_activation_hook( __FILE__, 'mam_lb_seo_checker_activation' );


function mam_lb_seo_checker_deactivation() {
    wp_clear_scheduled_hook( 'update_metrics' );
}
register_deactivation_hook( __FILE__, 'mam_lb_seo_checker_deactivation' );
