(function($) {

  Drupal.behaviors.simpleForm = {
    attach: function(context, settings) {

      $(document).ajaxComplete(function(e, xhr, settings) {
        // Scroll to the top of page.
        var simpleformNavigationTriggered = settings.extraData._triggering_element_name == 'next' || settings.extraData._triggering_element_name == 'back';
        if (simpleformNavigationTriggered) {
          $('html, body').stop().animate({scrollTop: 0}, '500', 'swing');
        }
      });
    }
  };

})(jQuery);
