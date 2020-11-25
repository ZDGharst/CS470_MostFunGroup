
-- Returns a list of the closest earthquakes to a given city
SELECT earthquake.id, mag, ST_Distance_Sphere(
    point(earthquake.longitude, earthquake.latitude),
    point(city.longitude, city.latitude )
) * .000621371192 AS distance  
FROM earthquake INNER JOIN city ON name = 'Miami' ORDER BY distance;

-- Same as previous query, but uses cross join and WHERE in order to be more semantic
SELECT earthquake.id, mag, ST_Distance_Sphere(
    point(earthquake.longitude, earthquake.latitude),
    point(city.longitude, city.latitude )
) * .000621371192 AS distance  
FROM earthquake CROSS JOIN city WHERE name = 'Miami' ORDER BY distance;

-- Returns a list of all the cities with population > X and distance <Y and magnitude >Z 
SELECT city.name, earthquake.id, earthquake.mag, ST_Distance_Sphere(
    point(earthquake.longitude, earthquake.latitude),
    point(city.longitude, city.latitude )
) * .000621371192 AS distance 
FROM earthquake, city 
WHERE mag > 5.0
AND population >100000
GROUP BY city.id
HAVING distance <1000.0
ORDER BY mag;

-- Find the total population within x miles of a long/lat (example: 20 miles of New York).
SELECT Sum(population) as Population
FROM city
WHERE St_distance_sphere(Point(-74.01, 40.71),
	Point(longitude, latitude))
	* .000621371192 < 20;
