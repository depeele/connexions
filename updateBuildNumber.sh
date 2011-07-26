#!/bin/sh
config='application/configs/application.ini'
  date=`date +%Y%m%d`


sedCmd="s/^(app\\.version\\.build[ ]+)= [0-9]+\$/\\1= ${date}/"

sed -Ee "${sedCmd}" "${config}" > "${config}.tmp"
mv "${config}.tmp" "${config}"
