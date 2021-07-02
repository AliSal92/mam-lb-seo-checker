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

use Dotenv\Dotenv;
use MAM\Plugin\Init;


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
 * Initialize .env
 */
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

/**
 * Initialize and run all the core classes of the plugin
 */
if (class_exists('MAM\Plugin\Init')) {
    Init::register_services();
}