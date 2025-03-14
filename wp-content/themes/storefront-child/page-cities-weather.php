<?php

/**
 * Template Name: Cities Weather List
 */

get_header(); ?>

<div class="container">
  <h1>Список городов и погоды</h1>

  <!-- Custom Hook: Перед таблицей -->
  <?php do_action('before_cities_weather_table'); ?>

  <!-- Поле поиска -->
  <input type="text" id="search-city" placeholder="Введите название города..." />

  <!-- Таблица с городами -->
  <table id="cities-weather-table">
    <thead>
      <tr>
        <th>Страна</th>
        <th>Город</th>
        <th>Температура (°C)</th>
      </tr>
    </thead>
    <tbody>
      <?php
      global $wpdb;

      // Получаем города, страны и координаты из базы
      $query = "
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
            ";

      $cities = $wpdb->get_results($query);

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
        echo "<tr><td colspan='3'>Города не найдены.</td></tr>";
      }
      ?>
    </tbody>
  </table>

  <!-- Custom Hook: После таблицы -->
  <?php do_action('after_cities_weather_table'); ?>
</div>

<?php get_footer(); ?>

<script>
  $(function() {
    $("#search-city").on("keyup", function() {
      var query = $(this).val();
      $.ajax({
        url: "<?php echo admin_url('admin-ajax.php'); ?>",
        type: "POST",
        data: {
          action: "search_cities",
          query: query
        },
        success: function(response) {
          $("#cities-weather-table tbody").html(response);
        }
      });
    })
  });
</script>
