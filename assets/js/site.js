(function ($) {
    'use strict';

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

})(jQuery);