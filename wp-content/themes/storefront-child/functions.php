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

// Виджет для вывода погоды
class Cities_Weather_Widget extends WP_Widget
{

  function __construct()
  {
    parent::__construct(
      'cities_weather_widget',
      __('Город и Погода', 'text_domain'),
      array('description' => __('Показывает выбранный город и его погоду', 'text_domain'))
    );
  }

  // Формирование интерфейса виджета на фронтенде
  public function widget($args, $instance)
  {
    $city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';
    $api_key = defined('OPENWEATHERMAP_API_KEY') ? OPENWEATHERMAP_API_KEY : '';

    if ($city_id) {
      $city_name = get_the_title($city_id);
      $latitude = get_post_meta($city_id, '_city_latitude', true);
      $longitude = get_post_meta($city_id, '_city_longitude', true);

      if ($latitude && $longitude) {
        $weather_data = $this->get_weather_data($latitude, $longitude, $api_key);

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($city_name) . $args['after_title'];

        if ($weather_data) {
          echo '<p>Температура: ' . esc_html($weather_data['temp']) . '°C</p>';
        } else {
          echo '<p>Не удалось получить данные о погоде.</p>';
        }

        echo $args['after_widget'];
      } else {
        echo '<p>Геоданные не заданы.</p>';
      }
    }
  }

  // Обработка API-запроса к OpenWeatherMap
  private function get_weather_data($lat, $lon, $api_key)
  {
    $api_url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&units=metric&appid=$api_key";

    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['main']['temp'])) {
      return array(
        'temp' => round($data['main']['temp'], 1),
      );
    }

    return false;
  }

  // Форма настроек виджета в админке
  public function form($instance)
  {
    $city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';

    // Получаем список всех городов Cities
    $cities = get_posts(array(
      'post_type' => 'cities',
      'numberposts' => -1
    ));
?>

    <p>
      <label for="<?php echo $this->get_field_id('city_id'); ?>">Выберите город:</label>
      <select id="<?php echo $this->get_field_id('city_id'); ?>" name="<?php echo $this->get_field_name('city_id'); ?>">
        <?php foreach ($cities as $city) : ?>
          <option value="<?php echo esc_attr($city->ID); ?>" <?php selected($city_id, $city->ID); ?>>
            <?php echo esc_html($city->post_title); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </p>

<?php
  }

  // Сохранение настроек виджета
  public function update($new_instance, $old_instance)
  {
    $instance = array();
    $instance['city_id'] = (!empty($new_instance['city_id'])) ? strip_tags($new_instance['city_id']) : '';
    return $instance;
  }
}

// Регистрируем виджет
function register_cities_weather_widget()
{
  register_widget('Cities_Weather_Widget');
}
add_action('widgets_init', 'register_cities_weather_widget');


function get_city_weather($lat, $lon)
{
  $api_key = defined('OPENWEATHERMAP_API_KEY') ? OPENWEATHERMAP_API_KEY : '';

  if (!$api_key || !$lat || !$lon) {
    return 'API ключ или координаты пустые.';
  }

  $api_url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&units=metric&appid=$api_key";
  $response = wp_remote_get($api_url);

  if (is_wp_error($response)) {
    return 'Ошибка...';
  }

  $data = json_decode(wp_remote_retrieve_body($response), true);
  return isset($data['main']['temp']) ? round($data['main']['temp'], 1) . "°C" : 'Нет данных';
}
