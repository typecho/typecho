
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

