// http://civicrm.org/licensing
CRM.$(function($) {
  $(document)
    .on('crmWysiwygCreate', function(e, type, editor) {
      if (type === 'ckeditor') {
        editor.on('maximize', function(e) {
          $('#wpadminbar').toggle(e.data === 2);
        });
      }
    });
  // Prevent screen reader shortcuts from changing the document hash and breaking angular routes
  $('a.screen-reader-shortcut').click(function() {
    var target = $(this).attr('href');
    // Show toolbar if hidden
    if (target === '#wp-toolbar' && CRM.menubar.position === 'over-cms-menu') {
      CRM.menubar.togglePosition(false);
    }
    $(target).focus();
    return false;
  });
  $('<a href="#crm-qsearch-input" class="screen-reader-shortcut">' + ts("Open CiviCRM Menu") + '</a>')
    .prependTo('#adminmenumain')
    .click(function() {
      CRM.menubar.open('Home');
      return false;
    });
  // Fix header for WordPress shorcode: CiviCRM Form headings should start from h2 and not h1
  if ($('h1').length > 1) {
    let target = $('.crm-title .title');
    if (target.length > 0) {
      $(target).each(function (_, value) {
        $(value).replaceWith($(value)[0].outerHTML.replace(/h1/g, 'h2'));
      });
    }
  }
});
