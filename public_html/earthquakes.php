<?php

    require_once 'includes/db-config.php';

    /* Sanitize input for query. */
    $mag = is_numeric($_GET[mag]) ? $_GET[mag] : 0;
    if($_GET[mag_direction] == "lt") $mag_direction = "<=";
    elseif($_GET[mag_direction] == "eq") $mag_direction = "=";
    else $mag_direction = ">=";

    $injuries = is_numeric($_GET[injuries]) && $_GET[injuries] != 0 ? $_GET[injuries] : "0 OR injuries IS NULL";
    if($_GET[injuries_direction] == "lt") $injuries_direction = "<=";
    elseif($_GET[injuries_direction] == "eq") $injuries_direction = "=";
    else $injuries_direction = ">=";

    $fatalities = is_numeric($_GET[fatalities]) && $_GET[fatalities] != 0 ? $_GET[fatalities] : "0 OR fatalities IS NULL";
    if($_GET[fatalities_direction] == "lt") $fatalities_direction = "<=";
    elseif($_GET[fatalities_direction] == "eq") $fatalities_direction = "=";
    else $fatalities_direction = ">=";

    $sortOptions = ["id", "time", "latitude", "longitude", "mag", "costs", "injuries", "fatalities"];
    $sort = in_array($_GET[sort], $sortOptions) ? $_GET[sort] : "id";
    $order = $_GET[order] == "asc" ? "asc" : "desc";

    /* Gather the total number of rows possible based on the filter. */
    $query_no_limit = <<<query
    SELECT DATE_FORMAT(time, '%M %e, %Y') as formattedDate,
    TIME(time) as formattedTime,
    latitude,
    longitude,
    mag,
    effected_population,
    costs,
    injuries,
    fatalities
    FROM  earthquake LEFT JOIN damage ON id = earthquake_id WHERE 
        mag $mag_direction $mag AND
        (injuries $injuries_direction $injuries) AND
        (fatalities $fatalities_direction $fatalities)
query;

    $total_rows = mysqli_query($connection, $query_no_limit);
    $total_rows = mysqli_num_rows($total_rows);
    $total_pages = ceil($total_rows / 200);

    /* Calculate which page the user is on and deliver the data for that page. */
    if($_GET[page] >= 1 && $_GET[page] <= $total_pages) $page = $_GET[page];
    else $page = 1;
    $offset = ($page - 1) * 200;

    /* Gather the 200 rows based on the current page. */
    $query_concat_limit = "ORDER BY $sort $order LIMIT $offset, 200";
    $query = $query_no_limit . $query_concat_limit;

    /* Generate paging. */
    $prev_page = $page - 1;
    $next_page = $page + 1;

    $paging_choices = "";
    if($page != 1) $paging_choices .= "<a href=\"?page=1\">First</a> | <a href=\"?page=$prev_page\">Previous</a> | ";
    else $paging_choices .= "First | Previous | ";
    $paging_choices .= "Page $page of $total_pages | ";
    $paging_choices .= $page != $total_pages ? "<a href=\"?page=$next_page\">Next</a> | <a href=\"?page=$total_pages\">Last</a>" : "Next | Last";

    $result = mysqli_query($connection, $query);
    $row_count = mysqli_num_rows($result);

    /* Start generating content. */
    $title = "Earthquakes";
    $content = <<<TABLE

        <table class="right">
            <tr><td colspan="9" class="first">$paging_choices</td></tr>
            <tr><th class="left">Date</th><th>Time</th><th>Latitude</th><th>Longitude</th><th>Magnitude</th><th>Effected Population</th><th>Economic Cost</th><th>Injuries</th><th>Fatalities</th></tr>

TABLE;

    for($i = 0; $i < $row_count; $i++) {
        $row = mysqli_fetch_assoc($result);
        if($row[costs] != NULL) $row[costs] = "\$" . $row[costs];

        $content .= "            <tr><td class=\"left\">$row[formattedDate]</td><td>$row[formattedTime]</td><td>$row[latitude]</td><td>$row[longitude]</td><td>$row[mag]</td><td>$row[effected_population]</td><td>$row[costs]</td><td>$row[injuries]</td><td>$row[fatalities]</td></tr>";
    }

    $content .= <<<TABLE2
            <tr><td colspan="9" class="last">$row_count rows returned of $total_rows.<br>$paging_choices</td></tr>
        </table>

TABLE2;

    include('includes/template.php');

?>
