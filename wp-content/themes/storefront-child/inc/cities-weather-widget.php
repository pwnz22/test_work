<?php
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

  public function widget($args, $instance)
  {
    $city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';

    if ($city_id) {
      $city_name = get_the_title($city_id);
      $latitude = get_post_meta($city_id, '_city_latitude', true);
      $longitude = get_post_meta($city_id, '_city_longitude', true);

      if ($latitude && $longitude) {
        $weather_data = $this->get_weather_data($latitude, $longitude);

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

  public function get_weather_data($lat, $lon)
  {
    $api_key = defined('OPENWEATHERMAP_API_KEY') ? OPENWEATHERMAP_API_KEY : '';
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

  public function form($instance)
  {
    $city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';

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

  public function update($new_instance, $old_instance)
  {
    $instance = array();
    $instance['city_id'] = (!empty($new_instance['city_id'])) ? strip_tags($new_instance['city_id']) : '';
    return $instance;
  }
}
