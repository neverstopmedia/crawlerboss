(function ($) {
    'use strict';

    $("#checkHeadings").on('click', function(){

        let $this = $(this);

        $this.text('Checking').prop('disabled', true);
        $this.append('<span class="loader loader-sm ml-10"></span>');
        let siteID = $this.data('id');

        let request;

        if (request) {
            request.abort();
        }

        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'check_heading_structure',
                site_id: siteID
            }
        });

        request.done(function (response){

            alert(response.data.message);
            $(".heading-structure").html('');

            if( response.success == true ){

                console.log(response);

                $(".heading-structure").append('<ul style="flex: 0 0 100%;"></ul>');
                response.data.structure.forEach(function(element){
                    $(".heading-structure > ul").append(`<li class="${element.valid ? 'valid' : 'invalid'}">
                        <p class="mb-5 fw-sb fs-14" style="word-break: break-word;">${element.link[0]}</p>
                        <span class="fs-12">${element.headings.join(', ')}</span>
                    </li>`);
                });

            }

        });

    });

    if( !$("#keywordDistributionChart").length )
    return;

    am5.ready(function() {

        let request;
        let siteID = $("#keywordDistributionChart").data('id');

        // Abort any pending request
        if (request) {
            request.abort();
        }
        
        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'generate_keyword_distribution_graph',
                site_id: siteID
            }
        });

        // Callback handler that will be called on success
        request.done(function (response){

            $("#keywordDistributionChart").html('');

            if( response.success == true ){
                doKeywordDistributionGraph( response.data.keywords );
            }else{
                $("#keywordDistributionChart").html(`<p>${response.data.message}</p>`);
            }

        });

        function doKeywordDistributionGraph( keywordData ){
            // Create root element
            // https://www.amcharts.com/docs/v5/getting-started/#Root_element
            var root = am5.Root.new("keywordDistributionChart");
            
            // Set themes
            // https://www.amcharts.com/docs/v5/concepts/themes/
            root.setThemes([
            am5themes_Animated.new(root)
            ]);
            
            // Create chart
            // https://www.amcharts.com/docs/v5/charts/xy-chart/
            var chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: "none",
                wheelY: "none",
                paddingLeft: 0
            }));
            
            // We don't want zoom-out button to appear while animating, so we hide it
            chart.zoomOutButton.set("forceHidden", true);
            
            // Create axes
            // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
            var yRenderer = am5xy.AxisRendererY.new(root, {
            minGridDistance: 30,
            minorGridEnabled: true
            });
            
            yRenderer.grid.template.set("location", 1);
            
            var yAxis = chart.yAxes.push(am5xy.CategoryAxis.new(root, {
                maxDeviation: 0,
                categoryField: "keyword",
                renderer: yRenderer,
                tooltip: am5.Tooltip.new(root, { themeTags: ["axis"] })
            }));
            
            var xAxis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
                maxDeviation: 0,
                min: 0,
                numberFormatter: am5.NumberFormatter.new(root, {
                    "numberFormat": "#,###a"
                }),
                extraMax: 0.1,
                renderer: am5xy.AxisRendererX.new(root, {
                    strokeOpacity: 0.1,
                    minGridDistance: 80
                })
            }));
            
            
            // Add series
            // https://www.amcharts.com/docs/v5/charts/xy-chart/series/
            var series = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: "Series 1",
                xAxis: xAxis,
                yAxis: yAxis,
                valueXField: "value",
                categoryYField: "keyword",
                tooltip: am5.Tooltip.new(root, {
                    pointerOrientation: "left",
                    labelText: "{valueX}"
                })
            }));
            
            
            // Rounded corners for columns
            series.columns.template.setAll({
                cornerRadiusTR: 5,
                cornerRadiusBR: 5,
                strokeOpacity: 0
            });
            
            // Make each column to be of a different color
            series.columns.template.adapters.add("fill", function (fill, target) {
                return chart.get("colors").getIndex(series.columns.indexOf(target));
            });
            
            series.columns.template.adapters.add("stroke", function (stroke, target) {
                return chart.get("colors").getIndex(series.columns.indexOf(target));
            });
            
            
            // Set data
            var data = keywordData;
            
            yAxis.data.setAll(data);
            series.data.setAll(data);
            
            chart.set("cursor", am5xy.XYCursor.new(root, {
            behavior: "none",
            xAxis: xAxis,
            yAxis: yAxis
            }));
            
            // Make stuff animate on load
            // https://www.amcharts.com/docs/v5/concepts/animations/
            series.appear(1000);
            chart.appear(1000, 100);

        }
        
    });

})(jQuery);