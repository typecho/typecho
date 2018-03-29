
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
