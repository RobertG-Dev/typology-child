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

function typology_child_render_sponsors_section()
{
    if (is_shop() || is_product_taxonomy()) {
        get_template_part('template-parts/sponsors-section');
    }
}

add_action('woocommerce_after_main_content', 'typology_child_render_sponsors_section', 20);

// Remove the "Downloads remaining" and "Expires" columns
function remove_download_columns($columns)
{
    unset($columns['download-remaining']);
    unset($columns['download-expires']);

    return $columns;
}

add_filter('woocommerce_account_downloads_columns', 'remove_download_columns');

// Add Board Members metabox
require_once get_stylesheet_directory() . '/inc/board-members.php';

// Member registration API endpoint
require_once get_stylesheet_directory() . '/inc/member-registration-api-endpoint.php';
