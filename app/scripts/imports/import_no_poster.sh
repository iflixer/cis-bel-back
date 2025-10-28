for i in {1..5}; do
    echo "Запуск №$i"
    curl --location --request GET 'https://master.futmax.info/cronjob/import_empty_posters_tmdb?limit=1000&offset=1300' \
    --header 'User-Agent: Apidog/1.0.0 (https://apidog.com)' \
    --header 'Authorization: Bearer $2y$10$HHZk04WG/SbW/2703zRJeeDSNwnV6TzPe1ulORbJlnP4shp8fZxVa'
done