<?php

// Подключаем стили родительской темы
function storefront_child_enqueue_styles()
{
  wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

// Создание пользовательского типа записи (Custom Post Type) под названием “Cities”
function create_cities_post_type()
{
  $labels = array(
    'name'               => 'Cities',
    'singular_name'      => 'City',
    'menu_name'          => 'Cities',
    'name_admin_bar'     => 'City',
    'add_new'            => 'Add New City',
    'add_new_item'       => 'Add New City',
    'new_item'           => 'New City',
    'edit_item'          => 'Edit City',
    'view_item'          => 'View City',
    'all_items'          => 'All Cities',
    'search_items'       => 'Search Cities',
    'parent_item_colon'  => 'Parent Cities:',
    'not_found'          => 'No cities found.',
    'not_found_in_trash' => 'No cities found in Trash.'
  );

  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => true,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'query_var'          => true,
    'rewrite'            => array('slug' => 'cities'),
    'capability_type'    => 'post',
    'has_archive'        => true,
    'hierarchical'       => false,
    'menu_position'      => 5,
    'menu_icon'          => 'dashicons-location',
    'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
    'show_in_rest'       => true,
  );

  register_post_type('cities', $args);
}
add_action('init', 'create_cities_post_type');
