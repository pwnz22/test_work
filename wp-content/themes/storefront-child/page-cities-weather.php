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
        // Получаем список городов через функцию
        $cities = get_cities_list();

        if ($cities) {
          foreach ($cities as $city) {
            // Получаем координаты
            $temperature = get_weather_for_city($city->latitude, $city->longitude);

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
