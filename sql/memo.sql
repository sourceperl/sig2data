/* prod reporting for 2013-11-% */
SELECT
  DATE(FROM_UNIXTIME(rx_timestamp)) as date,
  object_id, var_id,
  MAX(index_p) - MIN(index_p) AS prod,
  MAX(index_n) - MIN(index_n) AS cons
FROM
  sig_index
WHERE
    FROM_UNIXTIME(rx_timestamp) LIKE "2013-11-%"
  AND
    object_id = 4
GROUP BY
  date, object_id, var_id;

/* object : last view */
SELECT FROM_UNIXTIME( MAX( `rx_timestamp` ) /1000 ) AS last_view, object_id
FROM `messages`
GROUP BY `object_id`
LIMIT 100;
