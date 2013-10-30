#!/usr/bin/python3
# -*- coding: utf-8 -*-

# this script download messages (last 20) from sensor (cloud-on-chip) 
# platform to a sqlite DB.
# objects are list on "objects" table, 
# the messages are store on "messages" table

# call this script regulary (with crond) for populate "messages" table

# call "sensor_poll.py --help" for args help

# some libs
import sys
import sensor
import time
from struct import *
import sqlite3
import argparse

# some consts
SENSOR_DB   = "sensor.db"
#SENSOR_DB   = "/usr/local/share/sensor/sensor.db"

# some vars
td12xx_id   = 0
td12xx_key  = 0

# process args
parser = argparse.ArgumentParser()
parser.add_argument("-r", "--day_rebuild", type=int,
                    help="resync local DB for a number of day")
parser.add_argument("-o", "--obj_id", type=int,
                    help="resync local DB for this object ID")
args = parser.parse_args()

if args.day_rebuild:
  print("rebuild turned on for %s day(s)" % args.day_rebuild)

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
if (args.obj_id):
  c.execute("SELECT object_id, modem_id, modem_key from objects where object_id=%d" % args.obj_id)
else:  
  c.execute("SELECT object_id, modem_id, modem_key from objects")

objects_rec = c.fetchall()

for row in objects_rec:
  object_id  = row['object_id']
  modem_id  = format(row['modem_id'], "04X")
  modem_key = format(row['modem_key'], "08X")
  print("*** new request: [OBJ_ID = %d, MOD_ID = %s]" % (object_id, modem_id))

  # use sensor object
  device = sensor.Device()
  # get token
  if (not device.set_device(modem_id, modem_key)):
    print('get token ko !', file=sys.stderr)
    exit(2)

  # init timestamp for search history end date
  history_end_search = 0
  history_max_older  = 0
  last_get_loop      = False
  end_get_loop       = False
  
  # get loop
  while True:
    # get message history
    msgs = device.get_history(20, until = history_end_search)
    # skip if error
    if msgs == 0:
      print('sensor return empty history', file=sys.stderr)
      # exit get loop
      break

    # last get request (sensor return less of 20 msgs : sensor reach end of db)
    if (len(msgs) != 20):
      last_get_loop = True
   
    for msg in msgs:
      # test and format vars from JSON, process KeyError if json vars is not set
      try:
        msg_when = msg['when'] 
        msg_type = msg['type']
        msg_station = int(msg['station'], 16)
        msg_lvl = round(float(msg['lvl']))
        msg_pld = msg['payload']   
      # next message if json var missing
      except KeyError:
        continue
      # set "age" vars
      if (history_max_older < msg_when):
        history_max_older = msg_when     
      
      if args.day_rebuild:
        if (history_max_older - msg_when) > (args.day_rebuild * 24 * 3600 * 1000):
          end_get_loop = True
          break
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

    # for next get_history : end_search is last msg_when timestamp
    history_end_search = msg_when

    # end of the get loop : - not in rebuild mode
    #                       - it's last get (get return less than 20 messages)
    #                       - max rebuild day reach
    if (not args.day_rebuild) or last_get_loop or end_get_loop:
      break

exit(0)
