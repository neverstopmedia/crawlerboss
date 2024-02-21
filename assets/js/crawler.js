
(function ($) {
    'use strict';

    // This will hold all the sites that have a link to domain.
    let processedSites = [];

    /**
     * Get the html markup for the process list
     * 
     * @returns String
     */
    function getStepsMarkup(){
        return `<li class="d-flex ai-c jc-b flex-w complete"><span>Crawl Starting</span><small>Complete</small></li>
        <li class="d-flex ai-c jc-b flex-w working"><span>Fetching All Sites <span class="elapsed"></span></span><small class="loader"></small></li>
        <li class="d-flex ai-c jc-b flex-w pending"><span>Processing Sites <span class="elapsed"></span></span><small>Pending</small></li>
        <li class="d-flex ai-c jc-b flex-w pending"><span>Finalizing <span class="elapsed"></span></span><small>Pending</small></li>`;
    }

    /**
     * Ajax call to save the data of a crawl for a particular site.
     * 
     * #Step 3
     * @param {Array} siteData 
     * @param {Int} siteID 
     */
    function saveSiteData( siteData, siteID ){
        
        let request;

        // Abort any pending request
        if (request) {
            request.abort();
        }
        
        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'finalize_crawl',
                site_id: siteID,
                site_data: siteData
            }
        });

        // Callback handler that will be called on success
        request.done(function (response){

            $(".working").append(`<div class="description">${response.data.message}</div>`);
            
            if( response.success == true ){
                $(".loader").text('Complete').removeClass('loader');
                $(".working").removeClass('working').addClass('complete');

                alert('Processing complete');
                // generateFinalResults( siteData );

            }else{
                $(".loader").text('Error').removeClass('loader');
                $(".working").removeClass('working').addClass('error');
            }

        });
    
    }

    /**
     * Accept a chunk of sites as an array, and crawl them
     * 
     * #Step 2
     * @param {Array} chunk 
     */
    function processSitesChunk( sites, siteID, chunkCount ){

        let request;

        // Start date of the crawling process
        const start = Date.now();

        let chunkedSites = sites.splice(0, 1);

        // Let's group the sites into chunks of 10 and process
        $(".working").append(`<div id="chunk-${chunkCount}" class="description">${chunkCount}. Processing ${chunkedSites[0].domain}</div>`);

        // Abort any pending request
        if (request) {
            request.abort();
        }
        
        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'match_sites_chunk',
                chunk: chunkedSites,
                site_id: siteID
            }
        });

        // Callback handler that will be called on success
        request.done(function (response){

            // Let's convert the object of objects into an array, and filter out the false responses.
            let arrayResponse = Object.keys(response).map((key) => response[key]).filter(Boolean);

            if( arrayResponse.length )
            processedSites.push( arrayResponse );
            
            $('#chunk-'+chunkCount).addClass('complete').text( `${chunkedSites[0].domain} completed in ${Date.now() - start} ms` )
            chunkCount++;

            if( sites.length ){
                processSitesChunk(sites, siteID, chunkCount);
            }else{

                $(".loader").text('Complete').removeClass('loader');
                $(".working").removeClass('working').addClass('complete').next().addClass('working').removeClass('pending').find('small').html('').addClass('loader');

                // Run a new ajax call to save the data.
                if( processedSites.flat(1).length ){
                    saveSiteData( processedSites.flat(1), siteID );
                }
                
            }
            
        });
    
        // Callback handler that will be called regardless
        // if the request failed or succeeded
        request.always(function () {
            chunkCount++;
        });

    }

    /**
     * Submit handler for the individual crawl
     * 
     * #Step 1
     */
    $("#custom-domain-search-form").on('submit', function(e){

        e.preventDefault();
        e.stopPropagation();

        let siteID = $("#siteID").val();
        
        // If a site was not specific, let's just exit
        if( !siteID ){
            alert('Specify a site first');
            return false;
        }

        // AJAX function to run a crawl
        $(".crawl--results").html('<ul class="pl-0"></ul>');

        $(".crawl--results ul").prepend( getStepsMarkup() );

        let request;

        // Abort any pending request
        if (request) {
            request.abort();
        }
        
        $("#custom-domain-search-btn").prop('disabled', true);

        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'crawl_callback',
                site_id: siteID
            }
        });

        // Callback handler that will be called on success
        request.done(function (response){

            $("#custom-domain-search-btn").prop('disabled', false);

            if( response.success == true ){

                if(response.data.cache == true){



                    return true;
                }

                let sites = response.data;
                
                $(".working").append(`<div class="description">${sites.length} sites are ready to crawl</div>`);
                $(".loader").text('Complete').removeClass('loader');
                $(".working").removeClass('working').addClass('complete').next().addClass('working').removeClass('pending').find('small').html('').addClass('loader');
                
                processSitesChunk(sites, siteID, 1);
                
                return true;
            }

            // If response was an error
            $(".working").removeClass('working').addClass('error');
            $(".loader").text('Error').removeClass('loader');
            return false;

        });
    
        // Callback handler that will be called regardless
        // if the request failed or succeeded
        request.always(function () {
            $("#custom-domain-search-btn").prop('disabled', false);
        });

    });


})(jQuery);