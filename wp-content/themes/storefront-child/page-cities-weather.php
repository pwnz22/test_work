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

    <div class="relative">
      <!-- Поле поиска -->
      <input class="w-full" type="text" id="search-city" placeholder="Введите название города..." />
      <!-- Прелоадер (изначально скрыт) -->
      <button id="preloader" class="hidden inline-flex absolute top-1/2 -translate-y-1/2 right-1">
        <svg class=" animate-spin -ml-1 mr-3 h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </button>
    </div>

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
      var query = $("#search-city").val().trim();
      var preloader = $("#preloader");

      // Показываем прелоадер
      preloader.removeClass("hidden");

      $.ajax({
        url: "<?php echo admin_url('admin-ajax.php'); ?>",
        type: "POST",
        data: {
          action: "search_cities",
          query: query
        },
        success: function(response) {
          $("#cities-weather-table tbody").html(response);
        },
        complete: function() {
          // Скрываем прелоадер после завершения запроса
          preloader.addClass("hidden");
        }
      });
    }

    $("#search-city").on("keyup", debounce(searchCities, 300)); // 300 мс задержка
  });
</script>
