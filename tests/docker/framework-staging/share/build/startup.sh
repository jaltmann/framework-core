#!/bin/bash
# this script will be run at startup on the docker container


# remove old pid-files
rm -f /var/run/mysqld/mysqld.pid
rm -f /var/run/mysqld/mysqld.sock
rm -f /var/run/myslqd/mysqld.sock.lock

# start supervisord and save its process-id into $PID
/usr/bin/supervisord & PID=$!

# when this script will be terminated by SIGINT or SIGTERM send a SIGTERM to the recorded $PID
# and thus allowing it to close itself gracefulls or kill it forcefully if needed
trap "kill $PID" INT TERM

# wait for child process to end natuarly
wait

