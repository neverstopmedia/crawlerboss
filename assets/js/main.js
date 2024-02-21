
// In your Javascript (external .js resource or <script> tag)
jQuery(document).ready(function() {
    (function ($) {
        'use strict';

        // Select2
        $('.crawler--select2').on('change', function(){

            let siteID = $(this).val();

            if( siteID ){
                $("#custom-domain-search-btn").prop('disabled', false);
            }else{
                $("#custom-domain-search-btn").prop('disabled', true);
            }

        });

        // Select2
        $('.crawler--select2').select2({
            ajax: {
                url: crawler_ajax_obj.ajaxurl,
                dropdownParent: $('#custom-domain-search-form'),
                dataType: 'json',
                placeholder: 'Select a daomain',
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

            return `${site.text} <small>Site ID: ${site.id}</small><p><b>Last crawled:</b> ${site.last_checked ? site.last_checked : 'Not crawled yet'}</p>`;
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

    })(jQuery);
});