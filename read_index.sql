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
AND
  object_id = 4
GROUP BY
  date, object_id, var_id;
