/*!
 * WViewPlot 1.0
 *
 * Copyright 2014-2015 Henrik Enquist
 * Released under the GPLv3 license
 */
 
function plotJson(series,placeholder,clickplaceholder) {
    //basic plot options
    if (series.message === 'ok') {
        var options = {
            lines: {
                show: true
            },
            //points: {
                //show: false
            //},
            xaxis: {
                mode: "time",
                timezone: "browser"
            },
            crosshair: {
                mode: "xy"
            },
            grid: {
                hoverable: true,
                clickable: true,
                autoHighlight: false
            },
            yaxes: []
        };
    
        var data=[];

        var unitarray = [];
        for ( var sensor in series.units ){
            if (sensor != 'dateTime') {
                unitarray.push( series.units[ sensor ] );
            }
        }
        var plotunits = $.unique(unitarray);
        var nbr_axes = plotunits.length;
        for (var axis = 1; axis <= nbr_axes; axis++) {
            if (axis == 2) {
                temppos = "right";
            } else {
                temppos =  "left";
            }
            options.yaxes.push( { position: temppos,  axisLabel: plotunits[axis-1], alignTicksWithAxis: axis > 1 ? 1 : null });
        }
    
        for (var sens = 1; sens < series.sensors.length; sens++) {
            seriesdata=[];
            var serieslabel = series.sensors[sens];
            var seriesunit = series.units[serieslabel];
            var seriesaxis = plotunits.indexOf(seriesunit)+1;

            //loop through all values
            for (var tpoint = 0; tpoint < series.data.dateTime.length; tpoint++) {
                seriesdata.push([series.data.dateTime[tpoint]*1000, series.data[series.sensors[sens]][tpoint]]);
            }

            // build dataset for Flot
            var tempdata = {data: seriesdata, yaxis: seriesaxis, label: serieslabel + ', ' + seriesunit };

            //special treatment of rain (bar plot)
            if (serieslabel === "rain") {
                //x-axis step needed to set bar width (only used for rain)
                var timestep = (series.data.dateTime[1]-series.data.dateTime[0])*1000;
                tempdata["bars"] = { show: true , barWidth: timestep};
                tempdata["lines"] = { show: false };
            }
            //push new series into plot dataset
            data.push(tempdata);
        }  
        //plot it!
        $.plot(placeholder, data, options);

        //bind plotclick
        $(placeholder).bind("plotclick", function (event, pos, item) {
            if (item) {
                var x = new Date(item.datapoint[0]),
                y = item.datapoint[1].toFixed(2);
                $(clickplaceholder).text( x.toString() + "   -   " + item.series.label + ":   " + y );
                //plot.highlight(item.series, item.datapoint);
            }
        });
    } else {
        $(clickplaceholder).text(series.message);
    }
}


function updateGraph() //run to update plot 
{
    var datesRel, datesAbs, values, index, dAbs;

    // Get the parameters as an array
    values = $("#formParams").serializeArray();
    datesRel =  $("#formDatesRel").serializeArray();
    datesAbs =  $("#formDatesAbs").serializeArray();


    var activeTab = $("ul#dateTabs li.active").attr('id');
    if (activeTab == "absolute") {
        values.push({name: "dAbs", value: 1});
    } else {
        values.push({name: "dAbs", value: 0});
    }

    //plot button pressed, get form
    var str1 = jQuery.param(values);
    var str2 = jQuery.param(datesRel);
    var str3 = jQuery.param(datesAbs);
  
    //var str = $("form").serialize();  
    var dataurl = './getjson.php?' + str1 + '&' + str2 + '&' + str3;

    //request data
    $.ajax({
        url: dataurl,
        type: "GET",
        dataType: "json",
        success: function(json) {
            plotJson(json, "#placeholder","#clickplaceholder");
        }
    });
}


