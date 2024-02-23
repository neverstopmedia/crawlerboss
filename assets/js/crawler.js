
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
     * Finalizes the process of a single site within the crawl
     * 
     * @param {Boolean|Array} chunk The response, false or array
     * @param {Array} siteToCrawl - The site we are crawling
     * @param {Date} start - Start time
     * @param {Array} sites - The list of remaining sites to crawl
     * @param {Int} siteID - Site ID that we are crawling
     * @param {Int} crawlCount - The current crawl count
     */
    function finalizeSiteCrawl( chunk, siteToCrawl, start, sites, siteID, crawlCount ){

        // Let's convert the object of objects into an array, and filter out the false responses.
        let arrayResponse = Object.keys(chunk).map((key) => chunk[key]).filter(Boolean);

        if( arrayResponse.length )
        processedSites.push( arrayResponse );
        
        $('#site-'+crawlCount).addClass('complete').text( `${siteToCrawl[0].domain} completed in ${Date.now() - start} ms` )
        crawlCount++;

        if( sites.length ){
            processSitesChunk(sites, siteID, crawlCount);
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
     * @param {String} domain - The domain we are trying to look for
     * @param {Array} siteToCrawl - The site we are crawling
     * @param {Array} refererSite - The site we are searching in
     * @param {Date} start - Start time for the crawl
     * @param {Array} sites - List of all sites
     * @param {Int} siteID - The ID of the site we are trying to look for
     * @param {Int} crawlCount - The current count in the crawl
     * @param {Array} refererSitemaps - The sitemaps of the referer site
     */
    function processSitemapLinks(sitemapLinks, sitemap, domain, siteToCrawl, refererSite, start, sites, siteID, crawlCount, refererSitemaps){

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
                referer_site: refererSite
            }
        });

        sitemapRequest.done(function (response){

            if( response.success == true && response.data.code == 'CHUNK_COMPLETE' ){
                finalizeSiteCrawl( response.data.chunk, siteToCrawl, start, sites, siteID, crawlCount );
            }

            if( sitemapLinks.length ){
                processSitemapLinks( 
                    sitemapLinks, 
                    sitemap, 
                    domain, 
                    siteToCrawl, 
                    refererSite, 
                    start, 
                    sites, 
                    siteID, 
                    crawlCount,
                    refererSitemaps
                );
            }else{
                
                if( Array.isArray(refererSitemaps) == false )
                refererSitemaps = Object.keys(refererSitemaps).map((key) => refererSitemaps[key] );
                
                // Let's remove the first element from the array of sitemaps so we can process the next
                refererSitemaps.shift();

                if( !refererSitemaps.length ){
                    finalizeSiteCrawl( [ false ], siteToCrawl, start, sites, siteID, crawlCount );
                    return;
                }

                jumpToNextSitemap(
                    refererSite,
                    siteID,
                    domain,
                    refererSitemaps,
                    siteToCrawl,
                    start,
                    sites,
                    crawlCount
                );

            }

        });

    }

    /**
     * After crawling one sitemap in a CRAWL_HEARTBEAT let's jump to the next sitemap
     * 
     * @param {Array} refererSite - The site we are searching in
     * @param {Int} siteID - The ID of the site we are trying to look for
     * @param {String} domain - The domain we are trying to look for
     * @param {Array} refererSitemaps - The sitemaps of the referer site
     * @param {Array} siteToCrawl - The site we are crawling
     * @param {Date} start - Start time for the crawl
     * @param {Array} sites - List of all sites
     * @param {Int} crawlCount - The current count in the crawl
     * 
     * #Step 3.2
     */
    function jumpToNextSitemap(refererSite, siteID, domain, refererSitemaps, siteToCrawl, start, sites,  crawlCount){

        let request;

        if (request) {
            request.abort();
        }

        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'jump_to_next_sitemap',
                referer_site: refererSite,
                site_id: siteID,
                domain: domain,
                referer_sitemaps: refererSitemaps
            }
        });

        request.done(function (response){

            if( response.success == true && response.data.code == 'CHUNK_COMPLETE' ){

                finalizeSiteCrawl( response.data.chunk, siteToCrawl, start, sites, siteID, crawlCount );
                
            }else if( response.success == true && response.data.code == 'CRAWL_HEARTBEAT' ){

                processSitemapLinks( 
                    response.data.sitemap_links.url, 
                    response.data.sitemap, 
                    response.data.domain, 
                    siteToCrawl, 
                    response.data.referer_site, 
                    start, 
                    sites, 
                    siteID, 
                    crawlCount,
                    refererSitemaps
                );

            }else{
                finalizeSiteCrawl( [false], siteToCrawl, start, sites, siteID, crawlCount );
                return;
            }

        });

    }

    /**
     * Accept a chunk of sites as an array, and crawl them
     * 
     * #Step 2
     * @param {Array} chunk 
     */
    function processSitesChunk( sites, siteID, crawlCount ){

        let request;

        // Start date of the crawling process
        const start = Date.now();

        // Change this to change the number of sites crawled in a single call
        let siteToCrawl = sites.splice(0, 1);

        $(".working").append(`<div id="site-${crawlCount}" class="description">${crawlCount}. Processing ${siteToCrawl[0].domain}</div>`);

        // Abort any pending request
        if (request) {
            request.abort();
        }
        
        request = $.ajax({
            url: crawler_ajax_obj.ajaxurl,
            type: "post",
            data: {
                action: 'match_sites_chunk',
                site: siteToCrawl,
                site_id: siteID
            }
        });

        // Callback handler that will be called on success
        request.done(function (response){

            console.log(response);

            if( response.success == true && response.data.code == 'CHUNK_COMPLETE' ){

                finalizeSiteCrawl( response.data.chunk, siteToCrawl, start, sites, siteID, crawlCount );
                
            }else if( response.success == true && response.data.code == 'CRAWL_HEARTBEAT' ){

                processSitemapLinks( 
                    response.data.sitemap_links.url, 
                    response.data.sitemap, 
                    response.data.domain, 
                    siteToCrawl, 
                    response.data.referer_site, 
                    start, 
                    sites, 
                    siteID, 
                    crawlCount,
                    response.data.referer_sitemaps
                );

            }
            
        });
    
        // Callback handler that will be called regardless
        // if the request failed or succeeded
        request.always(function () {
            crawlCount++;
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

                    $(".crawl--results").html(response.data.markup);

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