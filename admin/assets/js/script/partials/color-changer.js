
/*
|--------------------------------------------------------------------------
| Color Changer
|--------------------------------------------------------------------------
|
| This is a tiny code to implement color changer for our demonstrations.
|
*/

var demo_colors = ['primary', 'secondary', 'success', 'info', 'warning', 'danger', 'purple', 'pink', 'cyan', 'yellow', 'brown', 'dark'];


/*
 * Color changer using base pallet name
 */
$('[data-provide~="demo-color-changer"]').each(function(){
  var target    = $(this).data('target'),
      baseClass = $(this).data('base-class'),
      html      = '',
      name      = $(this).dataAttr('name', ''),
      checked   = $(this).dataAttr('checked', ''),
      exclude   = $(this).dataAttr('exclude', ''),
      prefix    = '';

  if ( $(this).hasDataAttr('pale') ) {
    prefix = 'pale-';
  }

  if ( name == '' ) {
    name = Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5);
  }

  html = '<div class="color-selector color-selector-sm">';

  $.each( demo_colors, function(i, key){

    // Check if we need to exclude any code
    if ( exclude.indexOf(key) > -1 ) {
      return;
    }

    var color = prefix + key;
    html += '<label'+ (prefix === 'pale-' ? ' class="inverse"' : '') +'><input type="radio" value="'
         + color +'" name="'+ name +'"'+ (checked === key ? ' checked' : '') +'><span class="bg-'
         + color +'"></span></label>';
  });

  html += '</div>';

  $(this).replaceWith(html);

  // Listen to the change event of checkboxes
  $(document).on('change', 'input[name="'+ name +'"]', function(){
    var val = $('input[name="'+ name +'"]:checked').val();
    $(target).attr('class', baseClass + val);
  });
});
