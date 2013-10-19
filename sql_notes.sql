/* read solar prod (in uah) for day 2013-10-19 */
SELECT
  object_id, var_id,
  MAX(index_p) - MIN(index_p) AS prod,
  MAX(index_n) - MIN(index_n) AS cons  
FROM 
  sig_index 
WHERE 
  datetime(rx_timestamp, 'unixepoch') LIKE "2013-10-19%" 
GROUP BY 
  object_id, var_id;
/* result -> 
object_id|var_id|prod|cons
1|1|20073|0
CPU Time: user 0.010000 sys 0.000000
*/

/* prod reporting for 2013-10-% */
SELECT
  date(rx_timestamp, 'unixepoch') as date,
  object_id, var_id,
  MAX(index_p) - MIN(index_p) AS prod,
  MAX(index_n) - MIN(index_n) AS cons
FROM
  sig_index
WHERE
  datetime(rx_timestamp, 'unixepoch') LIKE "2013-10-%"
GROUP BY
  date, object_id, var_id;
