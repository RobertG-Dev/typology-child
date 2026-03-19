<?php

/*
    This is Typology Child Theme functions file
    You can use it to modify specific features and styling of Gridlove Theme
*/

add_action('after_setup_theme', 'typology_child_theme_setup', 99);

function typology_child_theme_setup()
{
    add_action('wp_enqueue_scripts', 'typology_child_load_scripts');
}

function typology_child_load_scripts()
{
    wp_register_style('typology_child_style', trailingslashit(get_stylesheet_directory_uri()) . 'style.css', false, TYPOLOGY_THEME_VERSION, 'screen');
    wp_enqueue_style('typology_child_style');
}

// Completely disable comments across the entire site

// Disable support for comments and trackbacks in post types
function disable_comments_post_types()
{
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
}

add_action('admin_init', 'disable_comments_post_types');

// Close comments on the front-end
function disable_comments_status()
{
    return false;
}

add_filter('comments_open', 'disable_comments_status', 20, 2);
add_filter('pings_open', 'disable_comments_status', 20, 2);

// Hide existing comments
function hide_existing_comments($comments)
{
    return array();
}

add_filter('comments_array', 'hide_existing_comments', 10, 2);

// Remove comments from admin menu
function remove_comments_admin_menu()
{
    remove_menu_page('edit-comments.php');
}

add_action('admin_menu', 'remove_comments_admin_menu');

// Remove comments metabox from dashboard
function remove_comments_dashboard()
{
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}

add_action('admin_init', 'remove_comments_dashboard');

// Remove comments links from admin bar
function remove_comments_admin_bar()
{
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}

add_action('wp_before_admin_bar_render', 'remove_comments_admin_bar');

function typology_get_archive_heading()
{
    $defaults = array(
        'pre'    => '',
        'title'  => '',
        'desc'   => '',
        'avatar' => ''
    );

    $args = array();

    if (is_category()) {
        $obj           = get_queried_object();
        $args['pre']   = '';
        $args['title'] = single_cat_title('', false);
        $args['desc']  = typology_get_option('archive_description') ? category_description() : '';
    } elseif (is_author()) {
        $obj = get_queried_object();

        if (empty($obj)) {
            global $author;
            $obj = isset($_GET['author_name']) ? get_user_by('slug', $author_name) : get_userdata(intval($author));
        }

        $args['pre']    = __typology('author');
        $args['title']  = $obj->display_name;
        $args['desc']   = typology_get_option('archive_description') ? get_the_author_meta('description', $obj->ID) : '';
        $args['avatar'] = typology_get_option('use_author_image') ? get_avatar($obj->ID, 100) : '';
    } elseif (is_tax()) {
        $args['title'] = single_term_title('', false);
    } elseif (is_home() && ($posts_page = get_option('page_for_posts')) && !is_page_template('template-home.php')) {
        $args['title'] = get_the_title($posts_page);
    } elseif (is_search()) {
        $args['pre']   = __typology('search_results_for');
        $args['title'] = get_search_query();
    } elseif (is_tag()) {
        $args['pre']   = __typology('tag');
        $args['title'] = single_tag_title('', false);
        $args['desc']  = typology_get_option('archive_description') ? tag_description() : '';
    } elseif (is_day()) {
        $args['pre']   = __typology('archive');
        $args['title'] = get_the_date();
    } elseif (is_month()) {
        $args['pre']   = __typology('archive');
        $args['title'] = get_the_date('F Y');
    } elseif (is_year()) {
        $args['pre']   = __typology('archive');
        $args['title'] = get_the_date('Y');
    } elseif (is_home()) {
        $args['title'] = __typology('latest_stories');
    } elseif (is_archive()) {
        $args['pre']   = '';
        $args['title'] = post_type_archive_title('', false);
    }

    return wp_parse_args($args, $defaults);
}

function typology_get_meta_data($meta_data = array())
{
    $output = '';

    if (empty($meta_data)) {
        return $output;
    }


    foreach ($meta_data as $mkey) {
        $meta = '';

        switch ($mkey) {
            case 'date':
                $date = typology_get_option('post_modified_date') ? get_the_modified_date() : get_the_date();
                $meta = '<span class="updated">' . $date . '</span>';
                break;

            case 'author':
                if (typology_is_co_authors_active() && $coauthors_meta = get_coauthors()) {
                    $temp = array();
                    foreach ($coauthors_meta as $key) {
                        $temp[] = '<span class="vcard author"><span class="fn"><a href="' . esc_url(get_author_posts_url($key->ID, $key->user_nicename)) . '">' . $key->display_name . '</a></span></span>';
                    }
                    $temp = implode(',', $temp);
                    $meta = __typology('by') . ' <div class="coauthors">' . $temp . '</div>';
                } else {
                    $author_id = get_post_field('post_author', get_the_ID());
                    $meta      = __typology('by') . ' <span class="vcard author"><span class="fn"><a href="' . esc_url(get_author_posts_url(get_the_author_meta('ID', $author_id))) . '">' . get_the_author_meta(
                            'display_name',
                            $author_id
                        ) . '</a></span></span>';
                }
                break;

            case 'rtime':
                $meta = typology_read_time(get_post_field('post_content', get_the_ID()));
                if (!empty($meta)) {
                    $meta .= ' ' . __typology('min_read');
                }
                break;

            case 'comments':
                if (comments_open() || get_comments_number()) {
                    ob_start();
                    comments_popup_link(__typology('no_comments'), __typology('one_comment'), __typology('multiple_comments'));
                    $meta = ob_get_contents();
                    ob_end_clean();
                } else {
                    $meta = '';
                }
                break;

            case 'category':
                $cats = get_the_category_list(', ');
                if (!empty($cats)) {
                    $meta = __typology('in') . ' ' . $cats;
                }
                break;

            case 'views':
                if (typology_is_wp_post_views_active()) {
                    $meta = the_views(false);
                }
                break;

            default:
                break;
        }

        if (!empty($meta)) {
            $output .= '<div class="meta-item meta-' . $mkey . '">' . $meta . '</div>';
        }
    }


    return wp_kses_post($output);
}

function custom_post_choir()
{
    $choir_labels = [
        'name'                  => _x('Chorai', 'Post Type General Name', 'text_domain'),
        'singular_name'         => _x('Choras', 'Post Type Singular Name', 'text_domain'),
        'menu_name'             => __('Chorai', 'text_domain'),
        'name_admin_bar'        => __('Choras', 'text_domain'),
        'archives'              => __('Lietuvos chorai', 'text_domain'),
        'attributes'            => __('Choras Attributes', 'text_domain'),
        'parent_item_colon'     => __('Parent Choras:', 'text_domain'),
        'all_items'             => __('Visi Chorai', 'text_domain'),
        'add_new_item'          => __('Pridėti naują Choras', 'text_domain'),
        'add_new'               => __('Pridėti naują', 'text_domain'),
        'new_item'              => __('New Choras', 'text_domain'),
        'edit_item'             => __('Edit Choras', 'text_domain'),
        'update_item'           => __('Update Choras', 'text_domain'),
        'view_item'             => __('View Choras', 'text_domain'),
        'view_items'            => __('View Chorai', 'text_domain'),
        'search_items'          => __('Search Choras', 'text_domain'),
        'not_found'             => __('Not found', 'text_domain'),
        'not_found_in_trash'    => __('Not found in Trash', 'text_domain'),
        'featured_image'        => __('Featured Image', 'text_domain'),
        'set_featured_image'    => __('Set featured image', 'text_domain'),
        'remove_featured_image' => __('Remove featured image', 'text_domain'),
        'use_featured_image'    => __('Use as featured image', 'text_domain'),
        'insert_into_item'      => __('Insert into choir', 'text_domain'),
        'uploaded_to_this_item' => __('Uploaded to this choras', 'text_domain'),
        'items_list'            => __('Chorai list', 'text_domain'),
        'items_list_navigation' => __('Chorai list navigation', 'text_domain'),
        'filter_items_list'     => __('Filter chorass list', 'text_domain'),
    ];
    $choir_args   = [
        'label'               => __('Choras', 'text_domain'),
        'description'         => __('Custom Post Type for Chorai', 'text_domain'),
        'labels'              => $choir_labels,
        'show_in_rest'        => true,
        'supports'            => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions'],
        'taxonomies'          => ['category', 'post_tag'],
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 1,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => false,
        'rewrite'             => [
            'slug' => 'choras',
        ],
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
    ];
    register_post_type('choras', $choir_args);
}

add_action('init', 'custom_post_choir', 0);

function typology_load_metaboxes()
{
    /* Display settings metabox */
    add_meta_box(
        'typology_display_settings',
        esc_html__('Display settings', 'typology'),
        'typology_display_settings_metabox',
        array('page', 'post', 'choras'),
        'side',
        'default'

    );
}

function typology_save_metaboxes($post_id, $post)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['typology_post_metabox_nonce']) || !wp_verify_nonce($_POST['typology_post_metabox_nonce'], 'typology_post_metabox_save')) {
        return;
    }

    if (($post->post_type == 'post' || $post->post_type == 'page' || $post->post_type == 'choras') && isset($_POST['typology'])) {
        $post_type = get_post_type_object($post->post_type);
        if (!current_user_can($post_type->cap->edit_post, $post_id)) {
            return $post_id;
        }

        $typology_meta = array();

        if (isset($_POST['typology']['display_settings'])) {
            $typology_meta['display_settings'] = $_POST['typology']['display_settings'];

            if ($_POST['typology']['display_settings'] == 'custom') {
                if (isset($_POST['typology']['cover'])) {
                    $typology_meta['cover'] = $_POST['typology']['cover'];
                }
                if (isset($_POST['typology']['fimg'])) {
                    $typology_meta['fimg'] = $_POST['typology']['fimg'];
                }
            }
        }

        if (!empty($typology_meta)) {
            update_post_meta($post_id, '_typology_meta', $typology_meta);
        } else {
            delete_post_meta($post_id, '_typology_meta');
        }
    }
}

if (!function_exists('typology_get_choras_meta')) :
    function typology_get_choras_meta($post_id)
    {
        $defaults = array(
            'display_settings' => 'inherit',
            'cover'            => 0,
            'fimg'             => 'content'
        );

        $meta = get_post_meta($post_id, '_typology_meta', true);

        return wp_parse_args((array) $meta, $defaults);
    }
endif;

// Make sure these actions are registered
add_action('add_meta_boxes', 'typology_load_metaboxes');
add_action('save_post', 'typology_save_metaboxes', 10, 2);

// Remove the "Downloads remaining" and "Expires" columns
function remove_download_columns($columns)
{
    unset($columns['download-remaining']);
    unset($columns['download-expires']);

    return $columns;
}

add_filter('woocommerce_account_downloads_columns', 'remove_download_columns');

/**
 * Add Category support to Pages
 */
function my_child_theme_add_categories_to_pages() {
    register_taxonomy_for_object_type( 'category', 'page' );
}
add_action( 'init', 'my_child_theme_add_categories_to_pages' );

/**
 * Add Category Slugs to Body Class on Pages
 */
function typology_child_add_category_to_body_classes( $classes ) {
    if ( is_page() && has_category() ) {
        $categories = get_the_category();

        foreach( $categories as $category ) {
            $classes[] = 'category-' . $category->slug;
        }
    }
    return $classes;
}
add_filter( 'body_class', 'typology_child_add_category_to_body_classes' );

// Add Board Members metabox
require_once get_stylesheet_directory() . '/inc/board-members.php';

// Member registration API endpoint
require_once get_stylesheet_directory() . '/inc/member-registration-api-endpoint.php';
