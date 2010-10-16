jQuery(document).ready(function(){
    jQuery('#csn_form').submit(function(e){
        e.preventDefault();
        jQuery.post(
            'wp-content/plugins/community-submitted-news/community-submitted-news.php',
            {
                _wpnonce: jQuery('#_wpnonce').val(),
                _wp_http_referer: jQuery("input[name='_wp_http_referer']").val(),
                csn_user_name: jQuery("#csn_user_name").val(),
                csn_user_email: jQuery("#csn_user_email").val(),
                csn_user_title: jQuery("#csn_user_title").val(),
                csn_user_story: jQuery("#csn_user_story").val(),
                csn_captcha_code: jQuery("#csn_captcha_code").val()
            },
            function(e){
                if(e.length != 0){
                    jQuery('#csn_user_submission').before(e);
                }
                else{
                    jQuery('#csn_form').html('<p>Thank your for your submission. Click <a href="'+window.location.href+'" id="csn_reload_form">here</a> to sumbit another story</p>');
                    jQuery('#csn_msg').show();
                }
            }
        );
    });
    jQuery('#csn_form').ajaxError(function(e, xhr, settings, exception){
        alert(xhr);
    });

    jQuery('a#csn_reload_form').click(function(event){
        event.preventDefault();
        alert(';stiuf');
        jQuery('#csn_form').text('[csn_news_form /]')
    });

    jQuery('#csn a').click(function(event){
        if(this.className == 'publish' || this.className == 'delete'){
            event.preventDefault();
            jQuery.post(
                '../wp-content/plugins/community-submitted-news/community-submitted-news.php',
                {
                    action: this.className,
                    id: this.id
                },
                function(e){
                    jQuery('#csn_msg').html(e);
                    jQuery('#csn_msg').show();
                    var row = jQuery(event.target).parents('tr');
                    row.each(function(){
                        jQuery(this).fadeOut('slow', function(e){
                            jQuery(this).remove();
                        });
                    });
                    jQuery(row).fadeOut('slow', function(e){
                        jQuery(row).remove();
                    });
                    setTimeout(function(e){
                        jQuery('#csn_msg').fadeOut('slow');
                    }, 5000);
                }
            );
        }
    });
});