<?php

// Подключаем файл виджета
require_once get_stylesheet_directory() . '/inc/cities-weather-widget.php';

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

// На странице редактирования записи создать метабокс
// с произвольными полями (Custom Post Fields) “latitude” и “longitude”, для
// ввода широты и долготы города соответственно. При необходимости создать дополнительные поля.
function cities_add_custom_metabox()
{
  add_meta_box(
    'cities_location_meta',  // Идентификатор метабокса
    'Геолокация города',     // Заголовок метабокса
    'cities_location_metabox_callback', // Функция рендеринга
    'cities',                // Custom Post Type, где показывать
    'normal',
    'high'
  );
}
add_action('add_meta_boxes', 'cities_add_custom_metabox');

function cities_location_metabox_callback($post)
{
  // Получаем текущие значения (если уже сохранены)
  $latitude = get_post_meta($post->ID, '_city_latitude', true);
  $longitude = get_post_meta($post->ID, '_city_longitude', true);

  // Поля ввода
  echo '<label for="city_latitude">Широта (Latitude): </label>';
  echo '<input type="text" id="city_latitude" name="city_latitude" value="' . esc_attr($latitude) . '" size="25" /><br><br>';

  echo '<label for="city_longitude">Долгота (Longitude): </label>';
  echo '<input type="text" id="city_longitude" name="city_longitude" value="' . esc_attr($longitude) . '" size="25" />';
}

// Сохранение данных полей при обновлении записи
function cities_save_location_meta($post_id)
{
  // Проверяем, если это автосохранение — выходим
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

  // Проверяем права доступа
  if (!current_user_can('edit_post', $post_id)) return;

  // Сохраняем широту
  if (isset($_POST['city_latitude'])) {
    update_post_meta($post_id, '_city_latitude', sanitize_text_field($_POST['city_latitude']));
  }

  // Сохраняем долготу
  if (isset($_POST['city_longitude'])) {
    update_post_meta($post_id, '_city_longitude', sanitize_text_field($_POST['city_longitude']));
  }
}
add_action('save_post', 'cities_save_location_meta');

// Создать пользовательскую таксономию (Custom Taxonomy) “Countries” и прикрепить ее к “Cities”.
function create_countries_taxonomy()
{
  $labels = array(
    'name'              => 'Countries',
    'singular_name'     => 'Country',
    'search_items'      => 'Search Countries',
    'all_items'         => 'All Countries',
    'parent_item'       => 'Parent Country',
    'parent_item_colon' => 'Parent Country:',
    'edit_item'         => 'Edit Country',
    'update_item'       => 'Update Country',
    'add_new_item'      => 'Add New Country',
    'new_item_name'     => 'New Country Name',
    'menu_name'         => 'Countries',
  );

  $args = array(
    'labels'            => $labels,
    'public'            => true,
    'hierarchical'      => true, // Делаем таксономию древовидной (как рубрики)
    'show_ui'           => true,
    'show_admin_column' => true,
    'query_var'         => true,
    'rewrite'           => array('slug' => 'countries'),
    'show_in_rest'      => true, // Поддержка редактора Gutenberg
  );

  register_taxonomy('countries', array('cities'), $args);
}
add_action('init', 'create_countries_taxonomy');

// Регистрируем виджет
function register_cities_weather_widget()
{
  register_widget('Cities_Weather_Widget');
}
add_action('widgets_init', 'register_cities_weather_widget');

// Функция для получения погоды
function get_weather_for_city($lat, $lon)
{
  if (!$lat || !$lon) {
    return 'Нет данных';
  }

  // Создаём экземпляр виджета
  $weather_widget = new Cities_Weather_Widget();

  // Вызываем метод get_weather_data() внутри класса
  $weather_data = $weather_widget->get_weather_data($lat, $lon);

  return is_array($weather_data) && isset($weather_data['temp']) ? $weather_data['temp'] . '°C' : 'Нет данных';
}

// Загружаем jQuery
function enqueue_custom_scripts()
{
  wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

// Загружаем TailwindCSS на кастомную страницу
function enqueue_cdn_for_custom_page()
{
  if (is_page_template('page-cities-weather.php')) {
    wp_enqueue_script('tw-cdn', 'https://cdn.tailwindcss.com', array(), null, true);
  }
}
add_action('wp_enqueue_scripts', 'enqueue_cdn_for_custom_page');


// Формируем метод с фильтром по названию города
function search_cities_ajax()
{
  global $wpdb;
  $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

  $sql = "
      SELECT
          p.ID,
          p.post_title AS city_name,
          t.name AS country_name,
          pm_lat.meta_value AS latitude,
          pm_lon.meta_value AS longitude
      FROM {$wpdb->posts} p
      LEFT JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
      LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
      LEFT JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
      LEFT JOIN {$wpdb->postmeta} pm_lat ON (p.ID = pm_lat.post_id AND pm_lat.meta_key = '_city_latitude')
      LEFT JOIN {$wpdb->postmeta} pm_lon ON (p.ID = pm_lon.post_id AND pm_lon.meta_key = '_city_longitude')
      WHERE p.post_type = 'cities' AND p.post_status = 'publish' AND tt.taxonomy = 'countries'
      AND p.post_title LIKE %s
  ";

  $cities = $wpdb->get_results($wpdb->prepare($sql, '%' . $query . '%'));

  if ($cities) {
    foreach ($cities as $city) {
      $temperature = get_city_weather($city->latitude, $city->longitude);
      echo "<tr>
              <td>{$city->country_name}</td>
              <td>{$city->city_name}</td>
              <td>{$temperature}</td>
          </tr>";
    }
  } else {
    echo "<tr><td colspan='3'>Город не найден.</td></tr>";
  }

  wp_die();
}

// Регистрация action для авторизированных и неавторизированных пользователей
add_action('wp_ajax_search_cities', 'search_cities_ajax');
add_action('wp_ajax_nopriv_search_cities', 'search_cities_ajax');

function register_custom_widget_area()
{
  register_sidebar(array(
    'name'          => __('Виджеты для страницы городов', 'text_domain'),
    'id'            => 'cities_custom_sidebar',
    'description'   => __('Эта область виджетов отображается на странице с городами.', 'text_domain'),
    'before_widget' => '<div class="widget-container">',
    'after_widget'  => '</div>',
    'before_title'  => '<h3 class="widget-title">',
    'after_title'   => '</h3>',
  ));
}
add_action('widgets_init', 'register_custom_widget_area');
