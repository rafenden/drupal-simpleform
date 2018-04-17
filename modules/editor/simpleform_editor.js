(function($) {

  Drupal.behaviors.simpleFormEditor = {
    attach: function(context, settings) {

      $('.ace-enabled').each(function(i) {
        var $textarea = $(this);
        id = $textarea.attr('id') + '-ace';
        $textarea.before('<div id="' + id + '"></div>');
        var editor = ace.edit(id);
        editor.setOptions({
          maxLines: Infinity
        });
        editor.setTheme('ace/theme/monokai');
        editor.getSession().setMode('ace/mode/yaml');
        editor.getSession().setUseWrapMode(true);
        editor.renderer.setScrollMargin(10, 10);
        editor.setValue($textarea.val(), -1);
        editor.on('change', function(){
          $textarea.val(editor.getValue());
        });
        $textarea.hide();
      });
    }
  };

})(jQuery);
