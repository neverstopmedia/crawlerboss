(function ($) {
    'use strict';

    am5.ready(function() {

        let request;
        let network = $("#siteDistributionChart").data('network');

        // Abort any pending request
        if (request) {
            request.abort();
        }
        
        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'generate_network_graph',
                network: network
            }
        });

        // Callback handler that will be called on success
        request.done(function (response){

            $("#siteDistributionChart").html('');

            console.log(response);
            if( response.success == true ){
                doGraph( response.data.sites );
            }else{
                $("#siteDistributionChart").html(`<p>${response.data.message}</p>`);
            }

        });


        function doGraph( linksData ){

            // Create root element
            // https://www.amcharts.com/docs/v5/getting-started/#Root_element
            var root = am5.Root.new("siteDistributionChart");

            // Set themes
            // https://www.amcharts.com/docs/v5/concepts/themes/
            root.setThemes([
                am5themes_Animated.new(root)
            ]);
            
            var data = {
                value: 1,
                children: linksData
            };
            
            var zoomableContainer = root.container.children.push(
                am5.ZoomableContainer.new(root, {
                    width: am5.p100,
                    height: am5.p100,
                    wheelable: true,
                    pinchZoom: true,
                    minZoomLevel: 0.1, // Allow zooming out further (10%)
                    maxZoomLevel: 5, // Allow zooming in closer (5x)
                })
            );
            
            var zoomTools = zoomableContainer.children.push(am5.ZoomTools.new(root, {
                target: zoomableContainer
            }));
            
            // Create series
            // https://www.amcharts.com/docs/v5/charts/hierarchy/#Adding
            var series = zoomableContainer.contents.children.push(am5hierarchy.ForceDirected.new(root, {
                maskContent: false, //!important with zoomable containers
                downDepth: 1,    
                singleBranchOnly: false,
                topDepth: 1,
                initialDepth: 2,
                categoryField: "name",
                childDataField: "children",
                idField: "name",
                linkWithStrength: 1,
                manyBodyStrength: -10,
                centerStrength: 0.8,
                nodePadding: 10
            }));
            
            series.data.setAll([data]);
            
            series.set("selectedDataItem", series.dataItems[0]);
            
            // Make stuff animate on load
            series.appear(1000, 100);

        }
        
    });

})(jQuery);
