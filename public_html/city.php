<?php

    require_once('includes/db-config.php');
    $title = "Search by City";
    
    if(isset($_GET[id])) {
        $id = mysqli_real_escape_string($connection, $_GET[id]);

        $cityData = "SELECT name, latitude, longitude, country, population FROM city WHERE id = $id";
        $cityData = mysqli_query($connection, $cityData);
        $cityData = mysqli_fetch_array($cityData);


        if($cityData) {
            $title = "$cityData[name]";
            
            $EQData = "SELECT DATE_FORMAT(time, '%M %e, %Y') as time1, TIME(time) as time2, mag, earthquake.latitude, earthquake.longitude, ST_Distance_Sphere(
                point(earthquake.longitude, earthquake.latitude),
                point(city.longitude, city.latitude )
            ) * .000621371192 AS distance  
            FROM earthquake INNER JOIN city ON city.id = $id
            HAVING distance < 50
            ORDER BY distance;";
            $EQData = mysqli_query($connection, $EQData);
            $numEQ = mysqli_num_rows($EQData);

            $content .= "<h2>$cityData[name]</h2>
            <ul>
                <li>Country: $cityData[country]</li>
                <li>Population: $cityData[population]</li>
                <li>Location: ($cityData[latitude], $cityData[longitude])</li>
            </ul>";

            if($numEQ > 0) {
                $content .= "<table><tr><td colspan=\"6\" class=\"first\">$numEQ earthquakes within 50 miles recorded in $cityData[name] this year.</td></tr><tr class=\"right\"><th class=\"left\">Date</th><th>Time</th><th>Latitude</th><th>Longitude</th><th>Magnitude</th><th>Miles from City Center</th></tr>";
                while($row = mysqli_fetch_array($EQData)) {
                    $row[distance] = round($row[distance], 1);
                    $content .= "<tr class=\"right\"><td class=\"left\">$row[time1]</td><td>$row[time2]</td><td>$row[mag]</td><td>$row[latitude]</td><td>$row[longitude]</td><td>$row[distance]</td></tr>";
                }
                $content .= "</table>";
            }

            else {
                $content .= "<p class=\"error\">No earthquakes within 50 miles recorded in $cityData[name] this year.</p>";
            }
        }

        else {
            $content .= "<p class=\"error\">No such city found.</p>";
        }
    }

    else if(isset($_GET[cityname]) && trim($_GET[cityname]) != "") {
    	$cityName = trim($_GET[cityname]);
        $cityName = mysqli_real_escape_string($connection, $cityName);

        $citiesList = "SELECT id, name, latitude, longitude, country, population FROM city WHERE name LIKE '%$cityName%' ORDER BY population DESC";
        $citiesList = mysqli_query($connection, $citiesList);
        $numCities = mysqli_num_rows($citiesList);

        if($numCities > 0) {
            $content .= "<table><tr><th class=\"left\">City</th><th class=\"left\">Country</th><th class=\"right\">Latitude</th><th class=\"right\">Longitude</th><th class=\"right\">Population</th></tr>";
            while($row = mysqli_fetch_array($citiesList)) {
                $content .= "<tr><td><a href=\"?id=$row[id]\">$row[name]</td><td>$row[country]</td><td class=\"right\">$row[latitude]</td><td class=\"right\">$row[longitude]</td><td class=\"right\">$row[population]</td></tr>";
            }
            $content .= "<tr><td colspan=\"5\" class=\"last\">$numCities cities found.</td></tr>";

        }

        else {
            $content .= "<p class=\"error\">We couldn't find a city by that name in our database.</p>";
        }
    }

    else {

        $citiesList = "SELECT id, name, latitude, longitude, country, population FROM city ORDER BY population DESC";
        $total_rows = mysqli_num_rows(mysqli_query($connection, $citiesList));
        $total_pages = ceil($total_rows / 200);

        if($_GET[page] >= 1 && $_GET[page] <= $total_pages) $page = $_GET[page];
        else $page = 1;
        $next_page = $page + 1;
        $prev_page = $page - 1;

        $offset = ($page - 1) * 200;
        $citiesListLimited = $citiesList . " LIMIT $offset, 200";
        $citiesData = mysqli_query($connection, $citiesListLimited);
        $numCities = mysqli_num_rows($citiesData);

        $paging_choices = "";
        if($page != 1) $paging_choices .= "<a href=\"?page=1\">First</a> | <a href=\"?page=$prev_page\">Previous</a> | ";
        else $paging_choices .= "First | Previous | ";
        $paging_choices .= "Page $page of $total_pages | ";
        $paging_choices .= $page != $total_pages ? "<a href=\"?page=$next_page\">Next</a> | <a href=\"?page=$total_pages\">Last</a>" : "Next | Last";

        $content .= "<table><tr><td colspan=\"8\" class=\"first\">$paging_choices</td></tr><tr><th class=\"left\">City</th><th class=\"left\">Country</th><th class=\"right\">Latitude</th><th class=\"right\">Longitude</th><th class=\"right\">Population</th></tr>";
        while($row = mysqli_fetch_array($citiesData)) {
            $content .= "<tr><td><a href=\"?id=$row[id]\">$row[name]</td><td>$row[country]</td><td class=\"right\">$row[latitude]</td><td class=\"right\">$row[longitude]</td><td class=\"right\">$row[population]</td></tr>";
        }

        $content .= <<<TABLE2
        <tr><td colspan="5" class="last">$numCities cities returned of $total_rows.<br>$paging_choices</td></tr>
    </table>

TABLE2;
    }

    include('includes/template.php');
    ?>
