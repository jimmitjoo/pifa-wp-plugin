<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://pifa.network/
 * @since             1.0.0
 * @package           Pifa
 *
 * @wordpress-plugin
 * Plugin Name:       Pifa - Product Importer for Affiliates
 * Plugin URI:        https://pifa.network/
 * Description:       This plugin lets you display your products from pifa.network on your WordPress website..
 * Version:           1.0.0
 * Author:            Jimmie Johansson
 * Author URI:        https://github.com/jimmitjoo
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
        wp_enqueue_style('pifa-style', plugin_dir_url(__FILE__) . 'style.css');
        //wp_enqueue_script( 'script-name', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
    }

    public function show_product_feed($atts)
    {
        $feedId = $atts['key'];
        $page = $_GET['fp'] ?? 1;
        $limit = $atts['limit'] ?? 15;
        $pagination = $atts['pagination'] ?? false;
        $perRow = $atts['per-row'] ?? 3;


        $feed = $this->api->feed($feedId, $page);


        if ($pagination) {
            $html = $this->get_pagination($feed);
        } else {
            $html = '';
        }
        $html .= '<div class="pifa-products pifa-per-row-' . $perRow . '">';
        foreach ($feed->data as $product) {
            $html .= $this->markup_product_item($product);
        }
        $html .= '</div>';
        if ($pagination) {
            $html .= $this->get_pagination($feed);
        }
        $html .= '</div>';

        return $html;
    }

    public function get_pagination($feed)
    {
        if ($feed->last_page === 1) return;

        $currentPageUrl = strtok($_SERVER['REQUEST_URI'], '?');
        $prevPage = $feed->current_page - 1;
        $nextPage = $feed->current_page + 1;
        $firstPageUrl = $currentPageUrl . '?fp=' . 1;
        $prevPageUrl = $currentPageUrl . '?fp=' . $prevPage;
        $nextPageUrl = $currentPageUrl . '?fp=' . $nextPage;
        $lastPageUrl = $currentPageUrl . '?fp=' . $feed->last_page;

        $html = '';
        $html .= '<div class="pifa-pagination">';

        if ($feed->current_page >= 2) {
            $html .= '<a class="pifa-paginate-first-page" href="' . $firstPageUrl . '">' . __('First Page', 'pifa') . '</a>';
        } else {
            $html .= '<span>' . __('First Page', 'pifa') . '</span>';
        }
        if ($prevPage > 0) {
            $html .= '<a class="pifa-paginate-previous-page" href="' . $prevPageUrl . '">' . __('Previous Page', 'pifa') . '</a>';
        } else {
            $html .= '<span>' . __('Previous Page', 'pifa') . '</span>';
        }
        $html .= '<span class="pifa-current-page">' . $feed->current_page . '</span>';
        if ($feed->current_page < $feed->last_page) {
            $html .= '<a class="pifa-paginate-next-page" href="' . $nextPageUrl . '">' . __('Next Page', 'pifa') . '</a>';
        } else {
            $html .= '<span>' . __('Next Page', 'pifa') . '</span>';
        }
        if ($feed->current_page < $feed->last_page) {
            $html .= '<a class="pifa-paginate-last-page" href="' . $lastPageUrl . '">' . __('Last Page', 'pifa') . '</a>';
        } else {
            $html .= '<span>' . __('Last Page', 'pifa') . '</span>';
        }
        $html .= '</div>';

        return $html;
    }

    public function markup_product_item($product)
    {
        $html = '<div class="product-item">';
        $html .= '<div>';
        $html .= '<a class="product-image" style="background-image: url(' . $product->image_url . ')" href="/' . get_option('pifa_product_url_prefix') . '/' . $product->slug . '">';
        $html .= $product->name;
        $html .= '</a>';
        $html .= '</div>';
        $html .= '<div class="product-meta">';
        $html .= '<div>';
        $html .= '<a href="/' . get_option('pifa_product_url_prefix') . '/' . $product->slug . '"><h2>';
        $html .= $product->name;
        $html .= '</h2></a>';
        if ($product->price < $product->regular_price) {
            $html .= '<span class="sale">' . display_price($product->price, $product->currency) . '</span>';
            $html .= '<del>' . display_price($product->regular_price, $product->currency) . '</del>';
        } else {
            $html .= '<span>' . display_price($product->price, $product->currency) . '</span>';
        }
        $html .= '</div>';
        if (get_option('pifa_display_show_more_button') == 'yes') {
            $html .= '<div>';
            $html .= '<a href="/' . get_option('pifa_product_url_prefix') . '/' . $product->slug . '">' . get_option('pifa_show_more_label') . '</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function setup_sections()
    {
        add_settings_section('first_section', 'API Key', array($this, 'section_callback'), 'smashing_fields');
        add_settings_section('second_section', 'Url Setting', array($this, 'section_callback'), 'smashing_fields');
        add_settings_section('third_section', 'Button Labels', array($this, 'section_callback'), 'smashing_fields');
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
        add_rewrite_rule($productUrlPrefix . '/(.+?)([^-]*)/?$', 'index.php?product_id=$matches[2]', 'top');
        add_rewrite_tag('%product_id%', '([^&]+)');
    }

    public function section_callback($arguments)
    {
        switch ($arguments['id']) {
            case 'second_section':
            case 'first_section':
            case 'third_section':
                break;
        }
    }

    public function setup_fields()
    {
        $this->api = new API();

        $fields = [
            [
                'uid' => 'pifa_api_key',
                'label' => __('Your API key', 'pifa'),
                'section' => 'first_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => __('Your API key', 'pifa'),
                'helper' => __('You can create a Team API key <a target="_blank" href="' . $this->api->root . '">here</a>.', 'pifa'),
                'supplemental' => null,
                'default' => null
            ],
            [
                'uid' => 'pifa_product_url_prefix',
                'label' => __('Product Url Prefix', 'pifa'),
                'section' => 'second_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => __('Product Url Prefix', 'pifa'),
                'helper' => __('This url prefix is used on product pages. Defaults to "product".', 'pifa'),
                'supplemental' => __('You may need to hit save on permalinks setting page when updating this value.', 'pifa'),
                'default' => null
            ],
            [
                'uid' => 'pifa_show_more_label',
                'label' => __('Show More Button Label', 'pifa'),
                'section' => 'third_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => __('Show More', 'pifa'),
                'helper' => null,
                'supplemental' => null,
                'default' => __('Show More', 'pifa'),
            ],
            [
                'uid' => 'pifa_display_show_more_button',
                'label' => __('Display Show More Button', 'pifa'),
                'section' => 'third_section',
                'type' => 'select',
                'options' => [
                    'no' => __('Hide', 'pifa'),
                    'yes' => __('Show', 'pifa'),
                ],
                'placeholder' => null,
                'helper' => null,
                'supplemental' => null,
                'default' => 'yes',
            ],
            [
                'uid' => 'pifa_external_buy_label',
                'label' => __('Buy Button Label', 'pifa'),
                'section' => 'third_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => __('Buy Now', 'pifa'),
                'helper' => null,
                'supplemental' => null,
                'default' => __('Buy Now', 'pifa'),
            ],
        ];

        if (get_option('pifa_api_key') && !empty(get_option('pifa_api_key'))) {
            $channels = [];
            $channels[''] = __('Choose channel', 'pifa');

            $channelsResponse = $this->api->channels();
            if (is_array($channelsResponse)) {
                foreach ($channelsResponse as $channel) {
                    $channels[$channel->id] = $channel->name;
                }
                $channelsField = [
                    'uid' => 'pifa_channel',
                    'label' => __('Your Channel', 'pifa'),
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
        $page_title = __('Pifa Settings Page', 'pifa');
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
            <h2><?php echo __('Pifa Settings Page', 'pifa'); ?></h2>
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
                        echo '<p>' . __('Create some feeds for this channel', 'pifa') . ' <a target="_blank" href="' . $this->api->createFeedLink(get_option('pifa_channel')) . '">' . __('here', 'pifa') . '</a>.</p>';
                    } else {
                        echo '<h2>' . __('Your feed shortcodes', 'pifa') . '</h2>';
                    }
                    echo '<table>';
                    foreach ($feedsResponse as $feed) {
                        echo '<tr>';
                        echo '<td>';
                        echo $feed->name;
                        echo '</td>';
                        echo '<td>';
                        echo '<input disabled type="text" value="[pifa key=' . $feed->id . ' page=1 limit=5 pagination=true]" />';
                        echo '</td>';
                        echo '<td>';
                        echo '<a target="_blank" href="' . $this->api->root . '/channels/1/update/' . $feed->id . '">Edit</a>';
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