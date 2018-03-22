#!/bin/bash

if [ "$DEP_ENV" == "OFF" ]; then
  chown www-data:www-data config/config.ini.php
  tail -F /var/log/syslog &
  exec /usr/bin/supervisord
else
  ln -s /pwk/config.ini.php config/config.ini.php
  chown www-data:www-data config/config.ini.php
  touch /etc/cron.d/piwik
  /awslogs-agent-setup.py -n -r us-west-2 -c http://coed-aws-log-setups.s3.amazonaws.com/$DEP_ENV/piwik.conf &
  tail -F /var/log/syslog &
  exec /usr/bin/supervisord
fi
