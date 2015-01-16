WViewPlot 1.0
=========

Interactive plotting of weather data using Flot Charts.

http://www.flotcharts.org/


Compatible with the sqlite databases from Wview and Weewx

http://www.wviewweather.com/

http://www.weewx.com/



See example site at http://dulcenet.home.kg/weewx/wviewplot 

The site uses jQuery UI, Bootstrap, Select2

http://jqueryui.com/

http://getbootstrap.com/

https://select2.github.io/



Installation


To get it working, place all the files on your web server. Get all the dependencies (flot, jQuery, jQuery-UI, bootstrap, select2) and update index.htm to point at them. Adjust the date in "$( "#dateStart" ).datepicker(..)" and "$( "#dateEnd" ).datepicker(..)" to suit your database.

Also change the "$database" variable in getjson.php to point at your Wview/Weewx sqlite database.


Verify that the php script works by a simple manual query:

http://(yoursite)/wviewplot/getjson.dev.php?dS=7&dE=0&dAbs=0&s=rain

This should give you an array of data ending with "message":"ok".

