
(function ($) {
    'use strict';

    // This will hold all the sites that have a link to domain.
    let processedSites = [];
    let crawlCount = 1;
    let sites = [];
    let siteID = null;

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
     * Finalizes the process of a single site within the crawl
     * 
     * @param {Boolean|Array} siteBreakdown The response, false or array
     * @param {Array} siteToCrawl - The site we are crawling
     * @param {Date} start - Start time
     * @param {Array} sites - The list of remaining sites to crawl
     */
    function finalizeSiteCrawl( siteBreakdown, siteToCrawl, start ){

        let arrayResponse = null;

        // Let's convert the object of objects into an array, and filter out the false responses.
        if( Array.isArray(siteBreakdown) == false && siteBreakdown != false )
        arrayResponse = Object.keys(siteBreakdown).map((key) => siteBreakdown[key]).filter(Boolean);

        if( arrayResponse && arrayResponse.length )
        processedSites.push( siteBreakdown );

        console.log(processedSites);

        $('#site-'+crawlCount).addClass('complete').text( `${siteToCrawl.domain} completed in ${Date.now() - start} ms` )
        crawlCount++;

        if( sites.length ){
            processSite();
        }else{

            $(".loader").text('Complete').removeClass('loader');
            $(".working").removeClass('working').addClass('complete').next().addClass('working').removeClass('pending').find('small').html('').addClass('loader');

            // Run a new ajax call to save the data.
            if( processedSites.flat(1).length ){
                saveSiteData( processedSites.flat(1), siteID );
            }
            
        }

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

                alert('Error occured, could not save');
            }

        });
    
    }

    /**
     * Process a list of sitemap links
     * 
     * #Step 3.1
     *
     * @param {Array} sitemapLinks - The list of links we are crawling
     * @param {Array} sitemap - The sitemap we are crawling [lastmod, sitemap]
     * @param {String}domain - The domain we are trying to look for
     * @param {Array} siteToCrawlSitemaps - The sitemaps of the referer site
     * @param {Array} siteToCrawl - The site we are crawling [id, domain]
     * @param {Date} start - Start time for the crawl
     */
    function processSitemapLinks(sitemapLinks, sitemap, domain, siteToCrawlSitemaps, siteToCrawl, start){

        let sitemapRequest;

        if (sitemapRequest) {
            sitemapRequest.abort();
        }

        sitemapRequest = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'crawl_sitemap_sets',
                links: sitemapLinks.splice(0, 50),
                sitemap: sitemap,
                domain: domain,
                site_to_crawl: siteToCrawl
            }
        });

        sitemapRequest.done(function (response){

            if( response.success == true && response.data.code == 'CHUNK_COMPLETE' ){
                console.log('Finalizing site crawl from ProcessSitemapLinks');
                return finalizeSiteCrawl( response.data.siteBreakdown, siteToCrawl, start );
            }

            if( sitemapLinks.length ){
                return processSitemapLinks( 
                    sitemapLinks, 
                    sitemap, 
                    domain, 
                    siteToCrawlSitemaps,
                    siteToCrawl, 
                    start
                );
            }else{
                
                if( Array.isArray(siteToCrawlSitemaps) == false )
                siteToCrawlSitemaps = Object.keys(siteToCrawlSitemaps).map((key) => siteToCrawlSitemaps[key] );
                
                // Let's remove the first element from the array of sitemaps so we can process the next
                siteToCrawlSitemaps.shift();

                if( !siteToCrawlSitemaps.length ){
                    console.log('Finalizing site crawl from ProcessSitemapLinks, after no sitemaps left');
                    return finalizeSiteCrawl( false, siteToCrawl, start );
                }

                return jumpToNextSitemap(
                    domain,
                    siteToCrawlSitemaps,
                    siteToCrawl,
                    start
                );

            }

        });

    }

    /**
     * After crawling one sitemap in a CRAWL_HEARTBEAT let's jump to the next sitemap
     * 
     * @param {String} domain - The domain we are trying to look for
     * @param {Array} siteToCrawlSitemaps - The sitemaps of the referer site
     * @param {Array} siteToCrawl - The site we are crawling
     * @param {Date} start - Start time for the crawl
     * 
     * #Step 3.2
     */
    function jumpToNextSitemap(domain, siteToCrawlSitemaps, siteToCrawl, start){

        let request;

        if (request) {
            request.abort();
        }

        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'jump_to_next_sitemap',
                site_to_crawl: siteToCrawl,
                site_id: siteID,
                domain: domain,
                site_to_crawl_sitemaps: siteToCrawlSitemaps
            }
        });

        request.done(function (response){

            if( response.success == true && response.data.code == 'CHUNK_COMPLETE' ){

                console.log('Finalizing site crawl from jumpToNextSitemap');

                return finalizeSiteCrawl( response.data.siteBreakdown, siteToCrawl, start );

            }else if( response.success == true && response.data.code == 'CRAWL_HEARTBEAT' ){

                return processSitemapLinks( 
                    response.data.sitemap_links.url, 
                    response.data.sitemap, 
                    response.data.domain, 
                    siteToCrawlSitemaps,
                    siteToCrawl, 
                    start
                );

            }else{
                console.log('Finalizing site crawl from jumpToNextSitemap, after the last condition');
                return finalizeSiteCrawl( false, siteToCrawl, start );
            }

        });

    }

    /**
     * Start the site processing step
     * 
     * #Step 2
     */
    function processSite(){

        let request;

        // Start date of the crawling process
        const start = Date.now();

        // Change this to change the number of sites crawled in a single call
        let siteToCrawl = sites.splice(0, 1);
        siteToCrawl = siteToCrawl[0];

        $(".working").append(`<div id="site-${crawlCount}" class="description">${crawlCount}. Processing ${siteToCrawl.domain}</div>`);

        // Abort any pending request
        if (request) {
            request.abort();
        }
        
        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'crawl_site',
                site_to_crawl: siteToCrawl,
                site_id: siteID
            }
        });

        request.done(function (response){
            
            if( response.success == true && response.data.code == 'CHUNK_COMPLETE' ){

                console.log('Finalizing site crawl from processSite');
                return finalizeSiteCrawl( response.data.siteBreakdown, siteToCrawl, start );
                
            }else if( response.success == true && response.data.code == 'CRAWL_HEARTBEAT' ){

                return processSitemapLinks( 
                    response.data.sitemap_links.url, 
                    response.data.sitemap, 
                    response.data.domain, 
                    response.data.site_to_crawl_sitemaps,
                    siteToCrawl, 
                    start
                );

            }
            
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

        siteID = $("#siteID").val();
        
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

                    $(".crawl--results").html(response.data.markup);
                    return true;
                }

                sites = response.data;
                
                $(".working").append(`<div class="description">${sites.length} sites are ready to crawl</div>`);
                $(".loader").text('Complete').removeClass('loader');
                $(".working").removeClass('working').addClass('complete').next().addClass('working').removeClass('pending').find('small').html('').addClass('loader');
                
                processSite();
                
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