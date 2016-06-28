<?php

/**
 * Template Name: RT Episode Page
 */


$context = Timber::get_context();
$post = new TimberPost();


/* Set up the Twig data objects */
$context['post'] = $post;
$context['sitepages'] = Timber::get_posts('post_type=page&post_parent=0');


/* Retrieve API response if there is a binding */
if (class_exists('ImmediateAPIBind')) {

  $post_id = $post->ID;
  $meta = get_post_meta($post_id);

  $api = ImmediateAPIBind::retrieveAPIResponseObject($post_id, $meta, false);

  if ($api===false) {
    $api = Array();
  }

  $context['apiResponse'] = (array)$api;

}


/* Hand over to Twig to render */
Timber::render(array('page-rt-episode.twig'), $context);

?>