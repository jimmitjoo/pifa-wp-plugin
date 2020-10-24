<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://resilient-brook-fdgmnomofbq9.vapor-farm-c1.com/
 * @since             1.0.0
 * @package           Pifa
 *
 * @wordpress-plugin
 * Plugin Name:       Pifa
 * Plugin URI:        https://resilient-brook-fdgmnomofbq9.vapor-farm-c1.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Jimmie Johansson
 * Author URI:        https://resilient-brook-fdgmnomofbq9.vapor-farm-c1.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pifa
 * Domain Path:       /languages
 */

include_once 'api.php';
include_once 'helpers.php';

class PifaPlugin
{
    private $api;

    public function __construct()
    {
        $this->api = new API();

        // Hook into the public
        add_shortcode('pifa', array($this, 'show_product_feed'));
    }

    public function init()
    {
        add_filter('template_include', array($this, 'include_template'));
        add_filter('init', array($this, 'rewrite_rules'));

        // Hook into the admin menu
        add_action('admin_menu', array($this, 'create_plugin_settings_page'));
        add_action('admin_init', array($this, 'setup_sections'));
        add_action('admin_init', array($this, 'setup_fields'));

        add_action('wp_enqueue_scripts', array($this, 'wpdocs_theme_name_scripts'));
    }

    public function fetch_product($productId)
    {
        global $product;
        $product = $this->api->product($productId);
    }

    public function include_template($template)
    {
        //try and get the query var we registered in our query_vars() function
        $productId = get_query_var('product_id');

        //if the query var has data, we must be on the right page, load our custom template
        if ($productId) {
            $this->fetch_product($productId);
            add_filter('pre_get_document_title', array($this, 'generate_custom_title'), 10);

            return __DIR__ . '/template.php';
        }

        return $template;
    }

    function generate_custom_title()
    {
        global $product;
        return $product->name . ' - ' . get_bloginfo('name');
    }

    public function wpdocs_theme_name_scripts()
    {
        wp_enqueue_style('pifa-style', plugin_dir_url('pifa') . 'pifa/style.css');
        //wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
    }

    public function show_product_feed($atts)
    {
        $feedId = $atts['key'];
        $page = $atts['page'] ?? 1;

        $feed = $this->api->feed($feedId, $page);

        $html = '<div class="pifa-products">';
        foreach ($feed->data as $product) {
            $html .= $this->markup_product_item($product);
        }
        $html .= '</div>';

        return $html;
    }

    public function markup_product_item($product)
    {
        $html = '<div class="product-item">';
        $html .= '<div>';
        $html .= '<a href="/' . get_option('pifa_product_url_prefix') . '/' . $product->id . '">';
        $html .= '<img src="' . $product->image_url . '">';
        $html .= '</a>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<a href="/' . get_option('pifa_product_url_prefix') . '/' . $product->id . '"><h2>';
        $html .= $product->name;
        $html .= '</h2></a>';
        $html .= '<span>' . display_price($product->price, $product->currency) . '</span>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<a href="/' . get_option('pifa_product_url_prefix') . '/' . $product->id . '">Show more</a>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function setup_sections()
    {
        add_settings_section('first_section', 'API Key', array($this, 'section_callback'), 'smashing_fields');
        add_settings_section('second_section', 'Url Setting', array($this, 'section_callback'), 'smashing_fields');
        //add_settings_section('our_third_section', 'My Third Section Title', array($this, 'section_callback'), 'smashing_fields');
    }

    public function flush_rules()
    {
        $this->rewrite_rules();

        flush_rewrite_rules();
    }

    public function rewrite_rules()
    {
        $productUrlPrefix = get_option('pifa_product_url_prefix');
        if (!$productUrlPrefix) {
            $productUrlPrefix = 'product';
        }
        add_rewrite_rule($productUrlPrefix . '/(.+?)/?$', 'index.php?product_id=$matches[1]', 'top');
        add_rewrite_tag('%product_id%', '([^&]+)');
    }

    public function section_callback($arguments)
    {
        switch ($arguments['id']) {
            case 'second_section':
            case 'first_section':
                break;
            /*case 'our_third_section':
                echo 'Third time is the charm!';
                break;*/
        }
    }

    public function setup_fields()
    {
        $this->api = new API();

        $fields = [
            [
                'uid' => 'pifa_api_key',
                'label' => 'Your API key',
                'section' => 'first_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => 'Your API key',
                'helper' => 'You can create a Team API key <a target="_blank" href="' . $this->api->root . '">here</a>.',
                'supplemental' => null,
                'default' => null
            ],
            [
                'uid' => 'pifa_product_url_prefix',
                'label' => 'Product Url Prefix',
                'section' => 'second_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => 'Product Url Prefix',
                'helper' => 'This url prefix is used on product pages. Defaults to "product".',
                'supplemental' => 'You may need to hit save on permalinks setting page when updating this value.',
                'default' => null
            ],
        ];

        if (get_option('pifa_api_key') && !empty(get_option('pifa_api_key'))) {
            $channels = [];
            $channels[''] = 'Choose channel';

            $channelsResponse = $this->api->channels();
            if (is_array($channelsResponse)) {
                foreach ($channelsResponse as $channel) {
                    $channels[$channel->id] = $channel->name;
                }
                $channelsField = [
                    'uid' => 'pifa_channel',
                    'label' => 'Your channel',
                    'section' => 'first_section',
                    'type' => 'select',
                    'options' => $channels,
                    'placeholder' => null,
                    'helper' => null,
                    'supplemental' => null,
                    'default' => null
                ];

                array_push($fields, $channelsField);
            }
        }

        foreach ($fields as $field) {
            add_settings_field($field['uid'], $field['label'], array($this, 'field_callback'), 'smashing_fields', $field['section'], $field);
            register_setting('smashing_fields', $field['uid']);
        }
    }

    public function field_callback($arguments)
    {
        $value = get_option($arguments['uid']); // Get the current value, if there is one
        if (!$value) { // If no value exists
            $value = $arguments['default']; // Set to our default
        } else if ($arguments['uid'] === 'pifa_product_url_prefix') {
            $this->flush_rules();
        }

        // Check which type of field we want
        switch ($arguments['type']) {
            case 'text': // If it is a text field
                printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value);
                break;
            case 'textarea': // If it is a textarea
                printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value);
                break;
            case 'select': // If it is a select dropdown
                if (!empty ($arguments['options']) && is_array($arguments['options'])) {
                    $options_markup = '';
                    foreach ($arguments['options'] as $key => $label) {
                        $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value, $key, false), $label);
                    }
                    printf('<select name="%1$s" id="%1$s">%2$s</select>', $arguments['uid'], $options_markup);
                }
                break;
        }

        // If there is help text
        if ($helper = $arguments['helper']) {
            printf('<span class="helper"> %s</span>', $helper); // Show it
        }

        // If there is supplemental text
        if ($supplimental = $arguments['supplemental']) {
            printf('<p class="description">%s</p>', $supplimental); // Show it
        }
    }

    public function create_plugin_settings_page()
    {
        // Add the menu item and page
        $page_title = 'Pifa Settings Page';
        $menu_title = 'Pifa';
        $capability = 'manage_options';
        $slug = 'smashing_fields';
        $callback = array($this, 'plugin_settings_page_content');
        $icon = 'dashicons-admin-plugins';
        $position = 100;

        add_submenu_page('options-general.php', $page_title, $menu_title, $capability, $slug, $callback);
    }

    public function plugin_settings_page_content()
    { ?>
        <div class="wrap">
            <h2>Pifa Settings Page</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('smashing_fields');
                do_settings_sections('smashing_fields');
                submit_button();
                ?>
            </form>
            <?php
            if (get_option('pifa_channel')) {
                $this->api = new API();
                $feedsResponse = $this->api->feeds(get_option('pifa_channel'));

                if (is_array($feedsResponse)) {
                    if (count($feedsResponse) === 0) {
                        echo '<p>Create some feeds for this channel <a target="_blank" href="' . $this->api->createFeedLink(get_option('pifa_channel')) . '">here</a>.</p>';
                    } else {
                        echo '<h2>Your feed shortcodes</h2>';
                    }
                    echo '<table>';
                    foreach ($feedsResponse as $feed) {
                        echo '<tr>';
                        echo '<td>';
                        echo $feed->name;
                        echo '</td>';
                        echo '<td>';
                        echo '<input disabled type="text" value="[pifa key=' . $feed->id . ' page=1]" />';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }

            ?>
        </div> <?php
    }
}

add_action('plugins_loaded', array(new PifaPlugin, 'init'));

// One time activation functions
register_activation_hook(__FILE__, array(new PifaPlugin, 'flush_rules'));