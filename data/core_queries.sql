
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
SELECT t2.name, t1.id, t1.mag, ST_Distance_Sphere(
    point(t1.longitude, t1.latitude),
    point(t2.longitude, t2.latitude )
) * .000621371192 AS distance 
FROM (SELECT * FROM earthquake WHERE mag > 6.0) t1, (SELECT * FROM city WHERE population >100000) t2
-- GROUP BY t2.id
HAVING distance <500.0
ORDER BY mag;

-- Find the total population within x miles of a long/lat (example: 20 miles of New York).
SELECT Sum(population) as Population
FROM city
WHERE St_distance_sphere(Point(-74.01, 40.71),
	Point(longitude, latitude))
	* .000621371192 < 20;
	      
	      
-- Returns all of the countries that are not within a certain distance of a certain magnitude of earthquake
-- Runtime of 17 seconds
SELECT DISTINCT city.country FROM city
WHERE city.country NOT IN
(SELECT DISTINCT t1.country FROM
(SELECT city.country,  ST_Distance_Sphere(
    point(t2.longitude, t2.latitude),
    point(city.longitude, city.latitude )
) * .000621371192 AS distance 
FROM (SELECT * FROM earthquake WHERE mag > 6.0) t2, city
HAVING distance < 500.0
) t1);

-- Returns the earthquakes and the population within a certain distance of it's center
-- Runtime of 6 seconds
SELECT t1.id, t1.mag, Sum(population) as Population
FROM city, (SELECT * FROM earthquake WHERE mag >6.5) t1
WHERE ST_Distance_Sphere(
    point(t1.longitude, t1.latitude),
    point(city.longitude, city.latitude )
) * .000621371192 <50
GROUP BY t1.id
ORDER BY population DESC;
	      

	      
	      
	      
--THIS NEXT SECTION SOLELY INVOLVES THE CREATION OF THE VIEWS NECESSARY TO CREATE THE CLUSTER TABLE:	      
	      
	      
-- Creates a View that compares all of the Earthquakes above a 5.0 magnitude and looks for other quakes of 5.0 magnitude
-- If within 5 miles, it stores both of the quakes into a "clusterview"
-- We can rework the distance values if need be, but the magnitude should be rather high, as the time complexity for this can be pretty wonky	      
CREATE VIEW clusterview AS
SELECT t2.id AS q1id, t2.mag AS q1mag, t1.id AS q2id, t1.mag AS q2mag, ST_Distance_Sphere(
    point(t1.longitude, t1.latitude),
    point(t2.longitude, t2.latitude )
) * .000621371192 AS distance 
FROM (SELECT * FROM earthquake WHERE mag > 5.0) t1 CROSS JOIN (SELECT * FROM earthquake WHERE mag > 5.0) t2
HAVING distance < 10
AND t1.id !=t2.id;
	      
-- Next step removes duplicates, as a quake will appear in q1 as well as q2 later on in the list:
CREATE  VIEW clusterview2 AS
SELECT  least (q1id, q2id) q1id,
        greatest(q1id, q2id) q2id
FROM   clusterview7
GROUP   BY         
        least (q1id, q2id) ,
        greatest(q1id, q2id); 
	      
-- Creates another view that uses 'dense_rank()' to properly assign a unique identifier to each new value of the 'q1' column from the previous view.
-- This dense_rank value will later be used as our clusterid attribute
-- We can use the data from this view for the insert statements that will go into our cluster table.
	      
CREATE VIEW clusterview3 AS
SELECT dense_rank() over (order by q1id) AS clusterid ,q1id,q2id
FROM clusterview2;
	      
	      
