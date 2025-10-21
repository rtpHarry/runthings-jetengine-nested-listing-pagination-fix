<?php
/**
 * Plugin Name: JetEngine Nested Listing Pagination Fix
 * Description: Fixes pagination issues when using nested listings within JetEngine Query Builder listings with JetSmartFilters
 * Version: 1.0.0
 * Author: runthingsdev
 * Author URI: https://runthings.dev/
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: runthings-jetengine-nested-listing-pagination-fix
 */

namespace RunthingsJetEngineNestedListingPaginationFix;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class
 */
class Plugin {
    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Settings option name
     */
    const OPTION_NAME = 'runthings_jetengine_nested_listing_fix_queries';

    /**
     * Stored pagination props from main query
     */
    private $main_query_props = null;

    /**
     * Get plugin instance
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if JetEngine and JetSmartFilters are active
        if ( ! $this->check_dependencies() ) {
            add_action( 'admin_notices', [ $this, 'dependency_notice' ] );
            return;
        }

        // Admin hooks
        if ( is_admin() ) {
            add_action( 'admin_menu', [ $this, 'register_settings_page' ], 100 );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'admin_notices', [ $this, 'show_query_edit_notice' ] );
        }

        // Hook into JetEngine query builder
        add_filter( 'jet-engine/query-builder/set-props', [ $this, 'store_main_query_props' ], 5, 3 );

        // Fix pagination in AJAX response
        add_filter( 'jet-smart-filters/render/ajax/data', [ $this, 'fix_pagination_in_ajax_response' ], 20 );
    }

    /**
     * Check if required plugins are active
     */
    private function check_dependencies() {
        return function_exists( 'jet_engine' ) && function_exists( 'jet_smart_filters' );
    }

    /**
     * Show admin notice if dependencies are missing
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'RunThings JetEngine Nested Listing Pagination Fix', 'runthings-jetengine-nested-listing-pagination-fix' ); ?></strong>
                <?php esc_html_e( 'requires JetEngine and JetSmartFilters to be installed and activated.', 'runthings-jetengine-nested-listing-pagination-fix' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Register settings page under JetEngine menu
     */
    public function register_settings_page() {
        add_submenu_page(
            'jet-engine',
            __( 'Nested Listing Fix', 'runthings-jetengine-nested-listing-pagination-fix' ),
            __( 'Nested Listing Fix', 'runthings-jetengine-nested-listing-pagination-fix' ),
            'manage_options',
            'jet-engine-nested-listing-fix',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'runthings_jetengine_nested_listing_fix', self::OPTION_NAME );
    }

    /**
     * Get all JetEngine queries
     */
    private function get_all_queries() {
        global $wpdb;

        $queries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, labels FROM {$wpdb->prefix}jet_post_types WHERE status = %s ORDER BY id ASC",
                'query'
            )
        );

        // Unserialize labels to get query names
        if ( $queries ) {
            foreach ( $queries as &$query ) {
                $labels = maybe_unserialize( $query->labels );
                $query->query_name = isset( $labels['name'] ) ? $labels['name'] : 'Untitled Query';
                unset( $query->labels );
            }
        }

        return $queries;
    }

    /**
     * Get selected query IDs from settings
     */
    private function get_selected_query_ids() {
        $selected = get_option( self::OPTION_NAME, [] );
        return is_array( $selected ) ? array_map( 'intval', $selected ) : [];
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle form submission
        if ( isset( $_POST['submit'] ) && check_admin_referer( 'runthings_jetengine_nested_listing_fix_save' ) ) {
            $selected_queries = isset( $_POST['selected_queries'] ) ? array_map( 'intval', $_POST['selected_queries'] ) : [];
            update_option( self::OPTION_NAME, $selected_queries );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'runthings-jetengine-nested-listing-pagination-fix' ) . '</p></div>';
        }

        $all_queries = $this->get_all_queries();
        $selected_queries = $this->get_selected_query_ids();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <p><?php esc_html_e( 'Select all primary listings which contain nested listings in their template.', 'runthings-jetengine-nested-listing-pagination-fix' ); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field( 'runthings_jetengine_nested_listing_fix_save' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Primary Listings', 'runthings-jetengine-nested-listing-pagination-fix' ); ?></th>
                        <td>
                            <?php if ( empty( $all_queries ) ) : ?>
                                <p><?php esc_html_e( 'No queries found. Create a query in JetEngine Query Builder first.', 'runthings-jetengine-nested-listing-pagination-fix' ); ?></p>
                            <?php else : ?>
                                <fieldset>
                                    <?php foreach ( $all_queries as $query ) : ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input
                                                type="checkbox"
                                                name="selected_queries[]"
                                                value="<?php echo esc_attr( $query->id ); ?>"
                                                <?php checked( in_array( (int) $query->id, $selected_queries ) ); ?>
                                            />
                                            <?php echo esc_html( $query->query_name ); ?>
                                            <span style="color: #666;">(ID: <?php echo esc_html( $query->id ); ?>)</span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Show notice on query edit page
     */
    public function show_query_edit_notice() {
        // Check if we're on the query edit page
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'jet-engine-query' ) {
            return;
        }

        if ( ! isset( $_GET['query_action'] ) || $_GET['query_action'] !== 'edit' || ! isset( $_GET['id'] ) ) {
            return;
        }

        $query_id = intval( $_GET['id'] );
        $selected_queries = $this->get_selected_query_ids();

        if ( ! in_array( $query_id, $selected_queries ) ) {
            return;
        }

        $settings_url = admin_url( 'admin.php?page=jet-engine-nested-listing-fix' );

        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e( 'Nested Listing Pagination Fix:', 'runthings-jetengine-nested-listing-pagination-fix' ); ?></strong>
                <?php esc_html_e( 'This listing will be modified for jet smart filter ajax queries to attempt to fix broken pagination when the layout contains a nested query.', 'runthings-jetengine-nested-listing-pagination-fix' ); ?>
                <br>
                <em><?php esc_html_e( 'Note: Experimental.', 'runthings-jetengine-nested-listing-pagination-fix' ); ?></em>
                <a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Manage the included listings here', 'runthings-jetengine-nested-listing-pagination-fix' ); ?></a>.
            </p>
        </div>
        <?php
    }

    /**
     * Store main query pagination props
     * Stores the props from main listing queries so we can restore them later
     */
    public function store_main_query_props( $props, $provider, $query_id ) {
        $numeric_query_id = isset( $props['query_id'] ) ? $props['query_id'] : null;
        $selected_queries = $this->get_selected_query_ids();

        // If this is a main query, store its props
        if ( in_array( $numeric_query_id, $selected_queries ) ) {
            $this->main_query_props = $props;
        }

        // Always return props unchanged - let all queries set their props
        return $props;
    }

    /**
     * Fix pagination in AJAX response
     * Replaces the pagination data with the main query's props
     */
    public function fix_pagination_in_ajax_response( $data ) {
        // If we have stored main query props, use them for pagination
        if ( $this->main_query_props !== null ) {
            $data['pagination'] = $this->main_query_props;
        }

        return $data;
    }
}

// Initialize plugin
Plugin::instance();

