<?php
/**
 * Plugin Name:     MAS Elementor Templates Importer
 * Plugin URI:      https://themeforest.net/user/madrasthemes/portfolio
 * Description:     MAS Elementor Templates Importer is a free plugin that allows you to import templates for Elementor
 * Author:          MadrasThemes
 * Author URI:      https://madrasthemes.com/
 * Version:         1.0.0
 * Text Domain:     mas-elementor-templates-importer
 * Domain Path:     /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Plugin;
use Elementor\Core\Editor\Editor;
use Elementor\Core\Base\Document;
use Elementor\TemplateLibrary\Source_Local;

if( ! class_exists( 'MAS_Elementor_Templates_Importer' ) ) {
    /**
     * Main MAS_Elementor_Templates_Importer Class
     *
     * @class MAS_Elementor_Templates_Importer
     * @version 1.0.0
     * @since 1.0.0
     * @package Vodi
     * @author MadrasThemes
     */
    final class MAS_Elementor_Templates_Importer {
        /**
         * MAS_Elementor_Templates_Importer The single instance of Jobhunt_Demo.
         * @var     object
         * @access  private
         * @since   1.0.0
         */
        private static $_instance = null;

        /**
         * The token.
         * @var     string
         * @access  public
         * @since   1.0.0
         */
        public $token;

        /**
         * The version number.
         * @var     string
         * @access  public
         * @since   1.0.0
         */
        public $version;

        public $allowed_template_types = array( 'page', 'section' );

        public $categories = array();

        public $keywords = array();

        public $templates = array();

        /**
         * Constructor function.
         * @access  public
         * @since   1.0.0
         * @return  void
         */
        public function __construct () {

            $this->token    = 'mas-elementor-templates-importer';
            $this->version  = '1.0.0';

            add_action( 'plugins_loaded', array( $this, 'setup_constants' ),        20 );
            add_action( 'init', array( $this, 'init' ),                             20 );
            add_action( 'init', array( $this, 'setup_hooks' ),                      30 );
        }

        /**
         * Main MAS_Elementor_Templates_Importer Instance
         *
         * Ensures only one instance of MAS_Elementor_Templates_Importer is loaded or can be loaded.
         *
         * @since 1.0.0
         * @static
         * @see MAS_Elementor_Templates_Importer()
         * @return Main Vodi instance
         */
        public static function instance () {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Setup plugin constants
         *
         * @access public
         * @since  1.0.0
         * @return void
         */
        public function setup_constants() {

            // Plugin Folder Path
            if ( ! defined( 'LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_DIR' ) ) {
                define( 'LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_DIR', plugin_dir_path( __FILE__ ) );
            }

            // Plugin Folder URL
            if ( ! defined( 'LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_URL' ) ) {
                define( 'LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_URL', plugin_dir_url( __FILE__ ) );
            }

            // Plugin Root File
            if ( ! defined( 'LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_FILE' ) ) {
                define( 'LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_FILE', __FILE__ );
            }
        }

        public function init() {

            foreach( $this->allowed_template_types as $template_type ) {
                $all_categories = array();
                $all_keywords = array();
                $all_posts = array();

                $posts_args = array(
                    'posts_per_page' => -1,
                    'post_type' => Source_Local::CPT,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        array(
                            'key' => Document::TYPE_META_KEY,
                            'value' => $template_type,
                        ),
                    ),
                );

                $posts_list = get_posts( $posts_args );

                foreach( $posts_list as $post ) {
                    $id = $post->ID;

                    $featured_img_url = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'full' ) : LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_URL . 'assets/images/placeholder.png';

                    $categories = array();
                    $categories_obj = get_the_terms( $id, Source_Local::TAXONOMY_CATEGORY_SLUG );
                    if ( ! empty( $categories_obj ) && ! is_wp_error( $categories_obj ) ) {
                        foreach( $categories_obj as $category_obj ) {
                            $categories[] = $category_obj->slug;
                            $all_categories[ $category_obj->slug ] = $category_obj->name;
                        }
                    }

                    $keywords = array();
                    $keywords[] = $post->post_name;
                    $all_keywords[ $post->post_name ] = $post->post_title;

                    $all_posts[] = array(
                        'template_id' => $id,
                        'pro' => true,
                        'notice' => '',
                        'title' => $post->post_title,
                        'author' => $post->post_author,
                        'categories' => $categories,
                        'keywords' => $keywords,
                        'dependencies' => '',
                        'source' => 'premium-api',
                        'type' => $template_type,
                        'modified' => $post->post_modified,
                        'thumbnail' => $featured_img_url,
                        'preview' => $featured_img_url,
                        'url' => get_permalink( $id ),
                    );
                }

                wp_reset_postdata();

                $this->categories[ $template_type ] = $all_categories;
                $this->keywords[ $template_type ] = $all_keywords;
                $this->templates[ $template_type ] = $all_posts;
            }

        }

        public function setup_hooks() {
            $source = Plugin::$instance->templates_manager->get_source( 'local' );
            remove_action( 'template_redirect', array( $source, 'block_template_frontend' ) );
            add_filter( 'template_include', array( $this, 'template_loader' ), 90 );
            add_action( 'rest_api_init', array( $this, 'register_route' ), 10 );
        }

        public function template_loader( $template ) {
            if ( is_singular( Source_Local::CPT ) && ! current_user_can( Editor::EDITING_CAPABILITY ) ) {
                $template = LANDKIT_ELEMENTOR_TEMPLATES_IMPORTER_DIR . 'templates/single-elementor_library.php';
            }

            return $template;
        }

        /**
         * Include required files
         *
         * @access public
         * @since  1.0.0
         * @return void
         */
        public function register_route() {
            register_rest_route( 'mastemp/v1', '/categories/(?P<type>[a-zA-Z0-9-]+)',
                array(
                    'methods' => 'GET',
                    'callback' => array( $this, 'mastemp_v1_categories' ),
                    'args' => array(
                        'type' => array( 
                            'validate_callback' => function( $param, $request, $key ) {
                                return in_array( $param, $this->allowed_template_types );
                            }
                        ),
                    ),
                    'permission_callback' => function () {
                        return current_user_can( 'edit_posts' );
                    },
                )
            );

            register_rest_route( 'mastemp/v1', '/keywords/(?P<type>[a-zA-Z0-9-]+)',
                array(
                    'methods' => 'GET',
                    'callback' => array( $this, 'mastemp_v1_keywords' ),
                    'args' => array(
                        'type' => array( 
                            'validate_callback' => function( $param, $request, $key ) {
                                return in_array( $param, $this->allowed_template_types );
                            }
                        ),
                    ),
                    'permission_callback' => function () {
                        return current_user_can( 'edit_posts' );
                    },
                )
            );

            register_rest_route( 'mastemp/v1', '/templates/(?P<type>[a-zA-Z0-9-]+)',
                array(
                    'methods' => 'GET',
                    'callback' => array( $this, 'mastemp_v1_templates' ),
                    'args' => array(
                        'type' => array( 
                            'validate_callback' => function( $param, $request, $key ) {
                                return in_array( $param, $this->allowed_template_types );
                            }
                        ),
                    ),
                    'permission_callback' => function () {
                        return current_user_can( 'edit_posts' );
                    },
                )
            );

            register_rest_route( 'mastemp/v1', '/template/(?P<id>\d+)',
                array(
                    'methods' => 'GET',
                    'callback' => array( $this, 'mastemp_v1_template' ),
                    'args' => array(
                        'id' => array( 
                            'validate_callback' => function( $param, $request, $key ) {
                                return is_numeric( $param );
                            }
                        ),
                    ),
                    'permission_callback' => function () {
                        return current_user_can( 'edit_posts' );
                    },
                )
            );

            register_rest_route( 'mastemp/v1', '/info/',
                array(
                    'methods' => 'GET',
                    'callback' => array( $this, 'mastemp_v1_info' ),
                    'permission_callback' => function () {
                        return current_user_can( 'edit_posts' );
                    },
                )
            );
        }

        public function mastemp_v1_categories( $data ) {
            if( isset( $data[ 'type' ] ) && isset( $this->categories[ $data[ 'type' ] ] ) ) {
                $results = array(
                    'success' => true,
                    'terms' => $this->categories[ $data[ 'type' ] ],
                );
            } else {
                $results = array(
                    'success' => false,
                );
            }

            return rest_ensure_response( $results );
        }

        public function mastemp_v1_keywords( $data ) {
            if( isset( $data[ 'type' ] ) && isset( $this->keywords[ $data[ 'type' ] ] ) ) {
                $results = array(
                    'success' => true,
                    'terms' => $this->keywords[ $data[ 'type' ] ],
                );
            } else {
                $results = array(
                    'success' => false,
                );
            }

            return rest_ensure_response( $results );
        }

        public function mastemp_v1_templates( $data ) {
            if( isset( $data[ 'type' ] ) && isset( $this->templates[ $data[ 'type' ] ] ) ) {
                $results = array(
                    'success' => true,
                    'templates' => $this->templates[ $data[ 'type' ] ],
                );
            } else {
                $results = array(
                    'success' => false,
                );
            }

            return rest_ensure_response( $results );
        }

        public function mastemp_v1_template( $data ) {
            if( isset( $data[ 'id' ] ) ) {
                $content = get_post_meta( $data[ 'id' ], '_elementor_data', true );
                if ( is_string( $content ) && ! empty( $content ) ) {
                    $content = json_decode( $content, true );
                }
                $template_type = get_post_meta( $data[ 'id' ], Document::TYPE_META_KEY, true );

                $results = array(
                    'success' => true,
                    'is_pro' => true,
                    'license' => true,
                    'content' => $content,
                    'type' => $template_type,
                );
            } else {
                $results = array(
                    'success' => false,
                );
            }

            return rest_ensure_response( $results );
        }

        public function mastemp_v1_info() {
            $results = array(
                'success' => true,
                'api_version' => $this->version,
                'remote_core_version' => $this->version,
            );

            return rest_ensure_response( $results );
        }

        /**
         * Cloning is forbidden.
         *
         * @since 1.0.0
         */
        public function __clone () {
            _doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'mas-elementor-templates-importer' ), '1.0.0' );
        }

        /**
         * Unserializing instances of this class is forbidden.
         *
         * @since 1.0.0
         */
        public function __wakeup () {
            _doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'mas-elementor-templates-importer' ), '1.0.0' );
        }
    }
}

/**
 * Returns the main instance of MAS_Elementor_Templates_Importer to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object MAS_Elementor_Templates_Importer
 */
function mas_elementor_templates_imoprter() {
    return MAS_Elementor_Templates_Importer::instance();
}

/**
 * Initialise the plugin
 */
function mas_elementor_templates_imoprter_load_plugin() {
    if ( did_action( 'elementor/loaded' ) ) {
        mas_elementor_templates_imoprter();
    }
}

add_action( 'plugins_loaded', 'mas_elementor_templates_imoprter_load_plugin' );