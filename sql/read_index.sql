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
