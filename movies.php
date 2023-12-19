<?php
    // global variables:
    $api_key = getenv('TMDB_API');
    $contentType = array(
        'Content-Type: application/json'
    );
    $imageBaseURL = "http://image.tmdb.org/t/p/w185";

    session_start();

    // cURL API to search for movies:
    function findMovies($title) {
        global $api_key, $contentType;

        // check if 'title' is already cached
        if(!empty($_SESSION[$title])) {
            return $_SESSION[$title];
        }

        // search API
        $ch = curl_init('http://api.themoviedb.org/3/search/movie?api_key=' . $api_key . '&query=' . $title);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $contentType);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $json = curl_exec($ch);
        curl_close($ch);

        // cache the results in session:
        $_SESSION[$title] = json_decode($json, true);
        return $_SESSION[$title];
    }

    // cURL API to fetch movie details:
    function fetchDetails($id) {
        global $api_key, $contentType;
        
        // check if 'id' is already cached
        if(!empty($_SESSION[$id])) {
            return $_SESSION[$id];
        }
        // details API
        $ch = curl_init('http://api.themoviedb.org/3/movie/'. $id .'?api_key=' . $api_key);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $contentType);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $details_json = curl_exec($ch);
        curl_close($ch);

        // cast API
        $cast_ch = curl_init('http://api.themoviedb.org/3/movie/' . $id . '/credits?api_key=' . $api_key);

        curl_setopt($cast_ch, CURLOPT_HTTPHEADER, $contentType);
        curl_setopt($cast_ch, CURLOPT_RETURNTRANSFER, true);

        $cast_json = curl_exec($cast_ch);
        curl_close($cast_ch);

        $details_json = json_decode($details_json, true);
        $cast_json = json_decode($cast_json, true)['cast'];
        $cast_details = array();
        
        // fetch top 5 cast members and append the array to main details json array
        $i = 0;
        while ($i < 5) {
            if(!empty($cast_json[$i]['name'])) {
                $cast_details[$i] = $cast_json[$i]['name'];
            }
            $i++;
        }
        $details_json['cast'] = $cast_details;
        
        // cache the results in session:
        $_SESSION[$id] = $details_json;
        return $_SESSION[$id];
    }

    // function to display movie search results:
    function displayTitles() {
        if(!empty($_GET['search'])) {
            $title = $_GET['search'];
            $results = findMovies($title);

            if(!empty($results)) {
                $results = $results['results'];
                echo '<ul style="display: inline-block;">';
                foreach($results as $_ => $movie) {
                    $id = $movie['id'];
                    $year = explode('-', $movie['release_date'])[0];
                    $params = '?search=' . $title . '&id=' . $id;

                    echo '
                        <li style="margin-bottom: 2%;"><a id="'. $id .'" href="'. $params .'">'. $movie['title'] .' - ('. $year .')</a></li>
                    ';
                }
                echo '</ul>';
            }
        }
    }

    // function to display movie details based on movie ID:
    function displayDetails() {
        global $imageBaseURL;
        if(!empty($_GET['id'])) {
            $id = $_GET['id'];
            // get movie details including cast in json:
            $results = fetchDetails($id);

            // set width of div based on query parameters
            $width = empty($_GET['search']) ? '100%' : '50%';

            if(!empty($results)) {
                // convert genres object from json to to a comma-separated string:
                $genres = $results['genres'];
                $genres_string = '';
                foreach($genres as $genre) {
                    $genres_string .= $genre['name'] . ', ';
                }
                $genres_string = rtrim($genres_string, ', ');

                echo '
                <div style="float: right; width: '. $width .'; text-align: center;">
                    <h1>' . $results['title'] . '</h1>
                    <img src="' . $imageBaseURL . $results['poster_path'] . '">
                    <p>' . $genres_string . '</p>
                    <p>' . $results['overview'] . '</p>
                    <p> • ' . implode('<br> • ', $results['cast']) . '</p>
                </div>';
            }
        }
    }

    // form to search for movies:
    echo ' 
    <form method="GET" action="movies.php">
        Movie title: <input type="text" id="search" name="search"/>
        <input type="submit" value="Display Info"/>
    </form>';

    // function calls on each page load:
    displayTitles();
    displayDetails();
?>
