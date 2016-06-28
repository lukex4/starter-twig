<?php

if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
		} );
	return;
}

Timber::$dirname = array('views');

class StarterSite extends TimberSite {

	function __construct() {
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		parent::__construct();
	}

	function register_post_types() {
		//this is where you can register custom post types
	}

	function register_taxonomies() {
		//this is where you can register custom taxonomies
	}

	function add_to_context( $context ) {
		$context['foo'] = 'bar';
		$context['stuff'] = 'I am a value set in your functions.php file';
		$context['notes'] = 'These values are available everytime you call Timber::get_context();';
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;
		return $context;
	}

	function myfoo( $text ) {
		$text .= ' bar!';
		return $text;
	}

	function add_to_twig( $twig ) {
		/* this is where you can add your own fuctions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );
		$twig->addFilter('myfoo', new Twig_SimpleFilter('myfoo', array($this, 'myfoo')));
		return $twig;
	}

}

new StarterSite();

function doEnqueueScripts() {

  wp_register_script('js-jquery', get_template_directory_uri() . '/assets/js/jquery.js', $in_footer = true);
	wp_register_script('js-bootstrap', get_template_directory_uri() . '/assets/js/bootstrap.min.js', $in_footer = true);
  wp_register_script('js-clean-blog', get_template_directory_uri() . '/assets/js/clean-blog.min.js', $in_footer = true);

  wp_enqueue_script('js-jquery');
  wp_enqueue_script('js-bootstrap');
  wp_enqueue_script('js-clean-blog');

}

function doEnqueueStylesheets() {

	wp_register_style('bootstrap-min', get_template_directory_uri() . '/assets/css/bootstrap.min.css');
	wp_register_style('clean-blog', get_template_directory_uri() . '/assets/css/clean-blog.css');
  wp_register_style('main', get_template_directory_uri() . '/style.css');

  wp_register_style('font-awesome', 'http://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css');
  wp_register_style('google-font-lora', 'http://fonts.googleapis.com/css?family=Lora:400,700,400italic,700italic');
  wp_register_style('google-font-opensans', 'http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800');

  wp_enqueue_style('bootstrap-min');
	wp_enqueue_style('clean-blog');
  wp_enqueue_style('main');

  wp_enqueue_style('font-awesome');
  wp_enqueue_style('google-font-lora');
  wp_enqueue_style('google-font-opensans');

}

add_action('wp_enqueue_scripts', 'doEnqueueScripts');
add_action('wp_enqueue_scripts', 'doEnqueueStylesheets');
