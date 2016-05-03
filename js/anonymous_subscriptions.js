
/**
 * @file
 * Some basic behaviors and utility functions for Views.
 */
(function ($) {

    Drupal.AnonymousSubscriptions = {};

    /**
     * jQuery Anonymous Subscription
     */
    Drupal.behaviors.anonymousSubscriptions = {
        attach: function (context) {
            $('#workbench-moderation-moderate-form select#edit-state').change(function(){
                Drupal.AnonymousSubscriptions.SelectListChanged();
            });
        }
    };

    Drupal.AnonymousSubscriptions.SelectListChanged = function() {
        var option = $('#workbench-moderation-moderate-form select#edit-state option:selected').val();
        if(option == 'published') {
            $('.form-item-send-emails').show();
        } else {
            $('.form-item-send-emails').hide();
        }
    }


    $(document).ready(function() {
        Drupal.AnonymousSubscriptions.SelectListChanged();
    });

})(jQuery);
