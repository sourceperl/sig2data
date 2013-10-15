#!/usr/bin/python3
# -*- coding: utf-8 -*-

# this script upload data from local DB to Xively
import xively
import sys
import time
import datetime
import requests
import sqlite3

# some private const (no push to public repo)
import private
XIVELY_FEED_ID = private.XIVELY_FEED_ID
XIVELY_API_KEY = private.XIVELY_API_KEY

# some consts
SENSOR_DB = 'sensor.db'
#SENSOR_DB = '/usr/local/share/sensor/sensor.db'
DEBUG     = 1

# open database
try:
  conn = sqlite3.connect(SENSOR_DB)
except:
  print("DB connect error", file=sys.stderr)
  exit(1)

# access to row by name
conn.row_factory = sqlite3.Row
#create db cursor
c = conn.cursor()  

# search object to poll
c.execute("select * from sig_stat where object_id = 2 and var_id = 2 " +
          "order by rx_timestamp DESC limit 1;")
stat = c.fetchone()

t_inst  = stat['var_inst']
#timestamp = stat['rx_timestamp']

c.execute("select * from sig_stat where object_id = 2 and var_id = 1 " +
          "order by rx_timestamp DESC limit 1;")
stat = c.fetchone()

p_inst  = stat['var_inst']
#timestamp = stat['rx_timestamp']

# update Xively

api = xively.XivelyAPIClient(XIVELY_API_KEY)
feed = api.feeds.get(XIVELY_FEED_ID)

now = datetime.datetime.utcnow()

feed.datastreams = [
  xively.Datastream(id='sensor1', current_value=t_inst, at=now),
  xively.Datastream(id='sensor2', current_value=p_inst, at=now),
]

feed.update()

