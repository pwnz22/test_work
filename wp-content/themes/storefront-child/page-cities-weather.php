<?php

/**
 * Template Name: Cities Weather List
 */

get_header(); ?>

<div class="flex">
  <div class="w-3/4">
    <h1 class="text-2xl mb-2">Список городов и погоды</h1>

    <!-- Custom Hook: Перед таблицей -->
    <?php do_action('before_cities_weather_table'); ?>

    <!-- Поле поиска -->
    <input class="w-full" type="text" id="search-city" placeholder="Введите название города..." />

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

        $query = "
             SELECT
                 p.ID,
                 p.post_title AS city_name,
                 IFNULL(t.name, 'Без страны') AS country_name,
                 pm_lat.meta_value AS latitude,
                 pm_lon.meta_value AS longitude
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
             LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'countries')
             LEFT JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
             LEFT JOIN {$wpdb->postmeta} pm_lat ON (p.ID = pm_lat.post_id AND pm_lat.meta_key = '_city_latitude')
             LEFT JOIN {$wpdb->postmeta} pm_lon ON (p.ID = pm_lon.post_id AND pm_lon.meta_key = '_city_longitude')
             WHERE p.post_type = 'cities' AND p.post_status = 'publish'
             ORDER BY p.post_title ASC
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

  <div class="w-1/4 p-4 mt-2">
    <!-- Sidebar с виджетами -->
    <?php if (is_active_sidebar('cities_custom_sidebar')) : ?>
      <aside class="custom-widgets">
        <?php dynamic_sidebar('cities_custom_sidebar'); ?>
      </aside>
    <?php endif; ?>
  </div>
</div>

<?php get_footer(); ?>

<script>
  jQuery(document).ready(function($) {
    // Функция дебаунс для уменьшения ненужных запросов
    function debounce(func, delay) {
      let timer;
      return function() {
        let context = this,
          args = arguments;
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(context, args), delay);
      };
    }

    // Функция поиска городов
    function searchCities() {
      let query = $("#search-city").val().trim();
      if (query.length < 2) return; // Минимум 2 символа для запроса

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
    }

    $("#search-city").on("keyup", debounce(searchCities, 300)); // 300 мс задержка
  });
</script>
