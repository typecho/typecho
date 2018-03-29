'use strict';



app.config({

  /*
  |--------------------------------------------------------------------------
  | Provide
  |--------------------------------------------------------------------------
  |
  | Specify an array of the name of vendors that should be load in all pages.
  | Visit following URL to see a list of available vendors.
  |
  | https://thetheme.io/theadmin/help/article-dependency-injection.html#provider-list
  |
  */

  provide: ['typeahead'],

  /*
  |--------------------------------------------------------------------------
  | Google API Key
  |--------------------------------------------------------------------------
  |
  | Here you may specify your Google API key if you need to use Google Maps
  | in your application
  |
  | Warning: You should replace the following value with your own Api Key.
  | Since this is our own API Key, we can't guarantee that this value always
  | works for you.
  |
  | https://developers.google.com/maps/documentation/javascript/get-api-key
  |
  */

  googleApiKey: 'AIzaSyDRBLFOTTh2NFM93HpUA4ZrA99yKnCAsto',

  /*
  |--------------------------------------------------------------------------
  | Google Analytics Tracking
  |--------------------------------------------------------------------------
  |
  | If you want to use Google Analytics, you can specify your Tracking ID in
  | this option. Your key would be a value like: UA-12345678-9
  |
  */

  googleAnalyticsId: '',

  /*
  |--------------------------------------------------------------------------
  | Smooth Scroll
  |--------------------------------------------------------------------------
  |
  | By changing the value of this option to true, the browser's scrollbar
  | moves smoothly on scroll.
  |
  */

  smoothScroll: false,

  /*
  |--------------------------------------------------------------------------
  | Save States
  |--------------------------------------------------------------------------
  |
  | If you turn on this option, we save the state of your application to load
  | them on the next visit (e.g. make topbar fixed).
  |
  | Supported states: Topbar fix, Sidebar fold
  |
  */

  saveState: false,


});





/*
|--------------------------------------------------------------------------
| Application Is Ready
|--------------------------------------------------------------------------
|
| When all the dependencies of the page are loaded and executed,
| the application automatically call this function. You can consider it as
| a replacer for jQuery ready function - "$( document ).ready()".
|
*/

app.ready(function() {


  /*
  |--------------------------------------------------------------------------
  | Plugins
  |--------------------------------------------------------------------------
  |
  | Import initialization of plugins that used in your application
  |
  */



/*
 * Search in Theadmin components
 */
if ( window["Bloodhound"] ) {
  var theadminComponents = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('tokens'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    prefetch: {
      url: app.dir.assets + 'data/json/files.json',
      cache: false
    }
  });

  $('#theadmin-search input').typeahead(null, {
    name: 'theadmin-components',
    display: 'title',
    source: theadminComponents,
    templates: {
      suggestion: function(data) {
        return '<a href="'+ location.origin +'/'+ data.url +'"><h6 class="mb-1">'+ data.title +'</h6><small>'+ data.description +'</small></a>';
      }
    }
  });

  $('#theadmin-search input').bind('typeahead:select', function(ev, data) {
    window.location.href = location.origin +'/'+ data.url;
  });

  $('#theadmin-search input').bind('typeahead:open', function(ev, data) {
    $(this).closest('#theadmin-search').find('.lookup-placeholder span').css('opacity', '0');
  });

  $('#theadmin-search input').bind('typeahead:close', function(ev, data) {
    if ( $(this).val() == "" ) {
      $(this).closest('#theadmin-search').find('.lookup-placeholder span').css('opacity', '1');
    }
  });
}


  /*
  |--------------------------------------------------------------------------
  | Paritials
  |--------------------------------------------------------------------------
  |
  | Import your main application code
  |
  */




/*
 * Display a warning when the page opened using "file" protocol
 */
if ( location.protocol == 'file:' ) {
  app.toast('Please open the page using "http" protocol for full functionality.', {
    duration: 15000,
    actionTitle: 'Read more',
    actionUrl: ''
  })
}




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




/*
 * Setting tab in the global quickview (#qv-global)
 */

// Topbar background color
$(document).on('change', 'input[name="global-topbar-color"]', function(){
  var val = $('input[name="global-topbar-color"]:checked').val();
  if ( val == 'default' ) {
    $('body > .topbar').removeClass('topbar-inverse').css('background-color', '#fff');
  }
  else {
    $('body > .topbar').addClass('topbar-inverse').css('background-color', '#'+ val);
  }
});

// Sidebar background color
$(document).on('change', 'input[name="global-sidebar-color"]', function(){
  var val = $('input[name="global-sidebar-color"]:checked').val();
  $('.sidebar').removeClass('sidebar-light sidebar-dark sidebar-default');
  $('.sidebar').addClass('sidebar-'+ val);
});

// Sidebar menu color
$(document).on('change', 'input[name="global-sidebar-menu-color"]', function(){
  var val = $('input[name="global-sidebar-menu-color"]:checked').val();
  $(".sidebar").removeClass (function (index, className) {
      return (className.match (/(^|\s)sidebar-color-\S+/g) || []).join(' ');
  }).addClass( 'sidebar-color-'+ val );

});




/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
|
| Handle some behaviors in sidebar demo page
|
*/

// Reset button
$(document).on('click', '#sidebar-reset-btn', function(){
  $('.sidebar').attr('class', 'sidebar');
  $('.sidebar-header').removeClass('sidebar-header-inverse')
  $('.sidebar .menu').attr('class', 'menu');
  $('body').removeClass('sidebar-folded');
});

// Header background color
$(document).on('change', 'input[name="sidebar-header-bg-color"]', function(){
  var val = $('input[name="sidebar-header-bg-color"]:checked').val();
  $('.sidebar-header').css('background-color', val);
});




/*
|--------------------------------------------------------------------------
| Timeline
|--------------------------------------------------------------------------
|
| Handle some behaviors in timelines demo page
|
*/


// Content position
$(document).on('click', '#timeline-alignment-selector .btn', function(){
  var val = $(this).children('input').val();
  $('#demo-timeline-alignment').attr('class', 'timeline timeline-content-'+ val);
});

// Point size
$(document).on('click', '#timeline-size-selector .btn', function(){
  var val = $(this).children('input').val();
  $('#demo-timeline-size').attr('class', 'timeline timeline-content-right timeline-point-'+ val);
});



});
