
/*
|--------------------------------------------------------------------------
| Icons
|--------------------------------------------------------------------------
|
| Handle some behaviors in icons demo page
|
*/

$(document).on('change', '#icon-font-changer', function() {
  var size = $(this).find('option:selected').text();
  $('.demo-icons-list').attr('class', 'demo-icons-list icons-size-'+ size);
});

$(document).on('mouseenter', '.demo-icons-list li', function(){
  var value = $(this).dataAttr('clipboard-text');
  $('#icon-selected').removeClass('text-secondary text-danger').addClass('text-info').text(value);
});

$(document).on('click', '.demo-icons-list li', function(){
  var value = $(this).dataAttr('clipboard-text');
  value += '<small class="sidetitle">COPIED</small>';
  $('#icon-selected').removeClass('text-secondary text-info').addClass('text-danger').html(value);
});

$(document).on('mouseleave', '.demo-icons-list', function(){
  $('#icon-selected').removeClass('text-info text-danger').addClass('text-secondary').text('Click an icon to copy the class name');
});

// Search
$.expr.pseudos.iconsSearch = function(a, i, m) {
  return $(a).dataAttr('clipboard-text').toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
};

$('#icons-search-input').on('keyup', function(e) {
  var s       = $(this).val().trim(),
      icons   = $(".tab-pane:not(#tab-search-result) .demo-icons-list li"),
      tabular = $('#icon-tabs').length;

  if ( !tabular ) {
    icons = $(".demo-icons-list li")
  }

  if (s === '') {
    icons.show();
    $('#icon-tabs li:eq(1) a').tab('show');
  }
  else {
    icons.not(':iconsSearch(' + s + ')').hide();
    icons.filter(':iconsSearch(' + s + ')').show();

    if ( tabular ) {
      // Show results in another tab
      $('#tab-search-result ul').html( icons.filter(':iconsSearch(' + s + ')').outerHTML() );
      $('#icon-tabs li:first a').tab('show');
    }
  }
});

// Remove search results on change tab
$('#icon-tabs li:first a').on('hide.bs.tab', function() {
  $('#icons-search-input').val('');
  $(".demo-icons-list li").show();
});

