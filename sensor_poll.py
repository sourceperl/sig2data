#!/usr/bin/python3
# -*- coding: utf-8 -*-

# this script download messages (last 8) from sensor (cloud-on-chip) 
# platform to a sqlite DB.
# objects are list on "objects" table, 
# the messages are store on "messages" table

# call this script regulary (with crond) for populate "messages" table

# some libs
import sys
import sensor
import time
from struct import *
import sqlite3

# some consts
SENSOR_DB   = "sensor.db"
#SENSOR_DB   = "/usr/local/share/sensor/sensor.db"

# some vars
td12xx_id   = 0
td12xx_key  = 0

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
c.execute("SELECT object_id, modem_id, modem_key from objects")
objects_rec = c.fetchall()

for row in objects_rec:
  object_id  = row['object_id']
  modem_id  = format(row['modem_id'], "04X")
  modem_key = format(row['modem_key'], "08X")
  print("*** new request ***")
  print("OBJ_ID  =", object_id)
  print("OBJ_ID  =", modem_id)
  print("OBJ_KEY =", modem_key)

  # use sensor object
  device = sensor.Device()
  # get token
  if (not device.set_device(modem_id, modem_key)):
    print('get token ko !', file=sys.stderr)
    exit(2)

  # get message history
  msgs = device.get_history(8)
  # skip if error
  if msgs == 0:
    print('sensor return empty history', file=sys.stderr)
    exit(3)

  for msg in msgs:
    # test and format vars from JSON
    msg_when = msg['when'] 
    msg_type = msg['type']
    msg_station = int(msg['station'], 16)
    msg_lvl = round(float(msg['lvl']))
    msg_pld = msg['payload']   
    # insert into database
    sql_command = ("INSERT INTO messages (`message_id`,`object_id`, " +
                   "`rx_timestamp`, `type`, `payload`, " + 
                   "`station_id`, `station_lvl`) " + 
                   "VALUES (NULL, '"+str(object_id)+"','"+str(msg_when)+"', '" +
                   str(msg_type)+"', '"+str(msg_pld)+
                   "', '"+str(msg_station)+"', '"+str(msg_lvl)+"');")
    print("%s" % (sql_command))    
    try:
      c.execute(sql_command)
      conn.commit()
    except sqlite3.IntegrityError:
      print("duplicate line, skip") 

exit(0)
