
// In your Javascript (external .js resource or <script> tag)
jQuery(document).ready(function() {
    (function ($) {
        'use strict';

        if( $("#cronLogs").length )
        new DataTable('#cronLogs', {
            "ordering": false
        });

        // Select2
        $('.crawler--select2').on('change', function(){

            let siteURL = $(this).val();
            window.location.href = siteURL;

        });

        // Select2
        $('.crawler--select2').select2({
            ajax: {
                url: crawler_ajax_obj.ajaxurl,
                dropdownParent: $('#custom-domain-search-form'),
                dataType: 'json',
                placeholder: 'Select a domain',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page || 1,
                        action: 'get_sites',
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    
                    return {
                        results: data.results,
                        pagination: {
                            more: data.more,
                        }
                    };
                },
                cache: true,
            },
            minimumInputLength: 2,
            escapeMarkup: function (markup) { return markup; },
            templateResult: formatSiteResult,
            dropdownPosition: 'above'
        });

        // Custom template functions for formatting options and selections
        function formatSiteResult(site) {
            if (site.loading) { return 'Searching...'; }

            return `${site.text} <small>Site ID: ${site.internal_id}</small><p><b>Last crawled:</b> ${site.last_checked ? site.last_checked : 'Not crawled yet'}</p>`;
        }

        // Tooltip
        $('.tooltip').tooltipster();
        $('.html-tooltip').tooltipster({

            contentAsHTML: true,
            functionInit: function(origin, content) {
                
                let htmlContent = $(content.origin).find('.tooltip-content').html();

                origin.content(htmlContent);
                
            }

        });

        // Domain dynamic search
        $("#dynamic-search").on("keyup", function() {
            let searchTerm = $(this).val().toLowerCase();
        
            $("#siteTable tbody tr").each(function() {
                let firstTdText = $(this).find("td:first").text().toLowerCase();
                $(this).toggle(firstTdText.indexOf(searchTerm) !== -1);
            });
            
        });

        // Regenerate sitemap
        $("#regenerate-sitemaps").on('click', function(){

            let request;

            // Abort any pending request
            if (request) {
                request.abort();
            }

            $(this).prop('disabled', true);

            request = $.ajax({
                url: crawler_ajax_obj.ajaxurl,
                type: "post",
                data: {
                    action: 'regenerate_sitemaps'
                }
            });

            // Callback handler that will be called on success
            request.done(function (response){
                alert( response.data.message );
            });
        
            // Callback handler that will be called regardless
            // if the request failed or succeeded
            request.always(function () {
                $("#regenerate-sitemaps").prop('disabled', false);
            });

        });

        // Regenerate sitemap
        $(".ajax-content").each(function(){

            let $this = $(this),
            template = $this.data('template'),
            siteID = $this.data('id');

            $this.html('<p class="text-center mb-0">Please wait, this shit takes time to load<p>');

            let request;

            // Abort any pending request
            if (request) {
                request.abort();
            }

            request = $.ajax({
                url: crawler_ajax_obj.ajaxurl,
                type: "post",
                data: {
                    action: 'get_content_ajax',
                    template: template,
                    site_id: siteID
                }
            });

            // Callback handler that will be called on success
            request.done(function (response){
                $this.html( response.data.markup );
            });
            
        });

        $(".can-toggle .card-header").on('click', function(){
            $(this).closest('.card').find('.card-body').toggle();
        });

    })(jQuery);
});