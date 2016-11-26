<?php 
/* ==========================================================================
Basic Setup
========================================================================== */
function winecouncil_setup(){
  add_theme_support('title-tag' );
  //allows you to edit the titles of pages
}
add_action('after_setup_theme', 'winecouncil_setup' );

/* ==========================================================================
Styles
========================================================================== */
function wpt_theme_styles() {
  wp_enqueue_style('main', get_template_directory_uri() . '/build/css/app.css');
}
add_action( 'wp_enqueue_scripts', 'wpt_theme_styles' );

/* ==========================================================================
Scripts
========================================================================== */
function wpt_theme_js() {
  wp_enqueue_script( 'mainjs', get_template_directory_uri() . '/build/js/all.js', '', '', true );
}
add_action( 'wp_enqueue_scripts', 'wpt_theme_js' );

/* ==========================================================================
Add Classes to <body> 
========================================================================== */
//Add class names to <body class="<?php body_class()>"> based on page
function my_class_names($classes) {
    // add 'class-name' to the $classes array
    if(is_page('home')) $classes[] = 'hp_HomePage';
    if(is_page('login')) $classes[] = 'lg_LoginPage';
    if(is_page('member-info')) $classes[] = 'member-info';
    if(is_page('events-and-meetings')) $classes[] = 'events-and-meetings';
    if(is_page('marketing-resources')) $classes[] = 'marketing-resources';
    if(is_page('industry-data-and-reports')) $classes[] = 'industry-data-and-reports';
    if(is_page('policy-and-government-affairs')) $classes[] = 'policy-and-government-affairs';
    if(is_page('sustainable-winemaking-ontario')) $classes[] = 'sustainable-winemaking-ontario';
    if(is_page('trade-members')) $classes[] = 'trade-members';
    if(is_page('subscriber-redirect')) $classes[] = 'subscriber-redirect';
    if(is_page('register')) $classes[] = 'register';
    // return the $classes array
    return $classes;
}
add_filter('body_class','my_class_names');

/* ==========================================================================
Create "Advertisements" page link in the sidebar
========================================================================== */
//Creates a new admin page and adds a link to the sidebar
//This is great for a custom field that needs to be site wide.

//Instead of going into the field group settings and saying:
  //Show this field group if: 
    //Page is equal to page and
    //Page is equal to page and
    //Page is equal to page and
    //Etc etc

//You can use the below code to create ONE page that allows you to change some fields site wide instead of changing it on multiple pages

if( function_exists('acf_add_options_page') ) {
  acf_add_options_page(array(
    'page_title'  => 'Advertisements',
    'menu_title'  => 'Advertisements',
    'menu_slug'   => 'theme-advertisements',
    'capability'  => 'edit_posts',
    'redirect'    => false
  ));
}
//I then go and create an advanced custom field group with the rules of:
  //Show this field group if: 'Options Page' is equal to 'Advertisements' 
//The custom fields then show up in this blank page

/* ==========================================================================
Login Functions (login, verify login,login failed etc)
========================================================================== */
function login_failed() {
  //check if the user logged in properly - if not redirect to login page
  $login_page  = home_url( '/login/' );
  wp_redirect( $login_page . '?login=failed' );
  exit;
}
add_action( 'wp_login_failed', 'login_failed' );

function verify_username_password( $user, $username, $password ) {
  //checks if password or username is empty
  $login_page  = home_url( '/login/' );
    if( $username == "" || $password == "" ) {
        wp_redirect( $login_page . "?login=empty" );
        exit;
    }
}
add_filter( 'authenticate', 'verify_username_password', 1, 3);

/* ==========================================================================
Redirect User to specific page based on their role
========================================================================== */
function redirect_to_specific_page() {
  $login_page  = home_url( '/login/' );

  if (! is_user_logged_in() && ! is_page('login') && ! is_page('subscriber-redirect') && ! is_page('register')) {
    wp_redirect($login_page);
    exit;
    //If the user is not logged in, not on a login/register page, or not on a splash page - redirect them to a login page. No access for them.
  }
  if(current_user_can('winemember') && is_page('trade-members')) {
      wp_redirect("http://devwindows9.niagararesearch.ca/");
      exit;
      //Users of the type 'WineMembers' can't view trade-member content so redirect them back to home.
  }
  if(current_user_can('winemember') && is_category('trade members')){
    wp_redirect("http://devwindows9.niagararesearch.ca/");
    exit;
    //Users of the type 'WineMembers' can't view trade-member content so redirect them back to home.
  }
  if(current_user_can('trademember') && !is_page('trade-members') && !is_page('home')){
    wp_redirect("http://devwindows9.niagararesearch.ca/");
    exit;
    //Users of the type 'TradeMembers' can't view WineMember content so redirect them back to home.
    //They can only view the Home page and the Trade Members page
  }
  if(current_user_can('subscriber') && ! is_page('subscriber-redirect')){
    wp_redirect("subscriber-redirect");
    exit;
    //When a new member registers for the first time WordPress automatically assigns them the role of 'Subscriber'
    //Subscribers can't view any content on the site besides the "Thanks for creating an account" splash page
  }
  if(is_user_logged_in() && ! is_page('login')) {
    return;
    //if the user is logged in and it's not login page - don't do anything
  }
}
add_action( 'template_redirect', 'redirect_to_specific_page' );

/* ==========================================================================
Where to redirect the user on login
========================================================================== */
function my_login_redirect( $url, $request, $user ){
  $login_page  = home_url( '/login/' );
  $page_viewed = basename($_SERVER['REQUEST_URI']);
  if( $page_viewed == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
    wp_redirect($login_page);
    exit;
    //take them to the login page because we're using a custom login page and not the default 'wp-login' page
  }
  if( $user && is_object( $user ) && is_a( $user, 'WP_User' ) ) {
      $url = home_url();
      //take them to the homepage
  }
  return $url;
}
add_filter('login_redirect', 'my_login_redirect', 10, 3 );


function wp_registration_redirect() {
    return home_url( '/subscriber-redirect' );
    //when user registers take them to the splash page for new members
}
add_filter( 'registration_redirect', 'wp_registration_redirect' );

/* ==========================================================================
Create New Custom member roles and rules
========================================================================== */
//creating some custom roles for different types of members 
$newMember = add_role( 'winemember', __('WineMember'),
  array(
    'read' => true, // true allows this capability
    'edit_posts' => false, // Allows user to edit their own posts
    'edit_pages' => false, // Allows user to edit pages
    'edit_others_posts' => false, // Allows user to edit others posts not just their own
    'create_posts' => false, // Allows user to create new posts
    'manage_categories' => false, // Allows user to manage post categories
    'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode
    'edit_themes' => false, // false denies this capability. User can’t edit your theme
    'install_plugins' => false, // User cant add new plugins
    'update_plugin' => false, // User can’t update any plugins
    'update_core' => false, // user cant perform core updates
));
$newMember = add_role( 'trademember', __('TradeMember'),
  array(
    'read' => true, // true allows this capability
    'edit_posts' => false, // Allows user to edit their own posts
    'edit_pages' => false, // Allows user to edit pages
    'edit_others_posts' => false, // Allows user to edit others posts not just their own
    'create_posts' => false, // Allows user to create new posts
    'manage_categories' => false, // Allows user to manage post categories
    'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode
    'edit_themes' => false, // false denies this capability. User can’t edit your theme
    'install_plugins' => false, // User cant add new plugins
    'update_plugin' => false, // User can’t update any plugins
    'update_core' => false, // user cant perform core updates
));

/* ==========================================================================
Hide Admin bar and Dashboard access if not an admin
========================================================================== */
function remove_admin_bar() {
  if (!current_user_can('administrator') && !is_admin()) {
    show_admin_bar(false);
    //if they're not an admin they cant see the black admin bar on top of screen
  }
}
add_action('after_setup_theme', 'remove_admin_bar');
function remove_dashboard_widgets(){
  remove_meta_box('dashboard_activity', 'dashboard', 'normal');
  remove_meta_box('dashboard_primary', 'dashboard', 'side');
  //hide dashboard widgets on /wp-admin page from non admins
}
add_action('wp_dashboard_setup', 'remove_dashboard_widgets');

?>