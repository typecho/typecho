'use strict';



require('./config');
require('./util.js');



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

  require('./plugins/typeahead.js');



  /*
  |--------------------------------------------------------------------------
  | Paritials
  |--------------------------------------------------------------------------
  |
  | Import your main application code
  |
  */

  require('./partials/notification.js');
  require('./partials/color-changer.js');
  require('./partials/icon.js');
  require('./partials/quickview.js');
  require('./partials/sidebar.js');
  require('./partials/timeline.js');


});
