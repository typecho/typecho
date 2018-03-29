

// =====================
// Chart plugins
// =====================
//
+function($){


  provider.initCharts = function() {

    provider.initPeity();
    provider.initSparkline();
    provider.initEasyPieChart();
    provider.initChartjs();

  };




  // Peity
  //
  provider.initPeity = function() {
    if ( ! $.fn.peity ) {
      return;
    }

    provider.provide('peity', function(){
      var type = $(this).dataAttr('type', '');

      switch(type) {
        case 'pie':
          var options = {
            width: 38,
            height: 38,
            radius: 8,
            fill: app.colors.primary +','+ app.colors.bg,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          if ( options.size ) {
            options.width = options.height = options.size;
          }

          options.fill = options.fill.split(',');

          $(this).peity("pie", options);
          break;


        case 'donut':
          var options = {
            width: 38,
            height: 38,
            radius: 8,
            fill: app.colors.primary +','+ app.colors.bg,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          if ( options.size ) {
            options.width = options.height = options.size;
          }

          options.fill = options.fill.split(',');

          $(this).peity("donut", options);
          break;


        case 'line':
          var options = {
            height: 38,
            width: 120,
            delimiter: ',',
            min: 0,
            max: null,
            fill: app.colors.bg,
            stroke: app.colors.primary,
            strokeWidth: 1,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          $(this).peity("line", options);
          break;


        case 'bar':
          var options = {
            height: 38,
            width: 120,
            delimiter: ',',
            min: 0,
            max: null,
            padding: 0.2,
            fill: app.colors.primary,
          }
          options = $.extend(options, app.getDataOptions( $(this) ));

          options.fill = options.fill.split(',');

          $(this).peity("bar", options);
          break;
      }


    });

  };




  // Easy pie chart
  //
  provider.initEasyPieChart = function() {
    if ( !$.fn.easyPieChart ) {
      return;
    }


    provider.provide('easypie', function(){
      var options = {
        barColor: app.colors.primary,
        trackColor: app.colors.bg,
      };
      options = $.extend(options, app.getDataOptions( $(this) ));

      if ( options.color ) {
        options.barColor = options.color;
        options.trackColor = app.colors.bg;
      }

      $(this).easyPieChart(options);
    });

  };





  // Sparkline
  //
  provider.initSparkline = function() {
    if ( !$.fn.sparkline ) {
      return;
    }


    var defColor = 'rgba(51,202,185,0.5)',
        spotColor = app.colors.primary,
        spotHighlightColor = app.colors.danger,
        negColor = app.colors.danger;

    $.extend($.fn.sparkline.defaults.common, {
      enableTagOptions: true,
      tagOptionsPrefix: 'data-',
      tagValuesAttribute: 'data-values',
      lineColor: defColor,
      fillColor: defColor,
    });


    $.extend($.fn.sparkline.defaults.line, {
      spotColor: spotColor,
      minSpotColor: spotColor,
      maxSpotColor: spotColor,
      highlightSpotColor: spotHighlightColor,
      highlightLineColor: null,
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.bar, {
      barWidth: 7,
      barSpacing: 4,
      barColor: defColor,
      negBarColor: negColor,
      zeroColor: defColor,
      stackedBarColor: [defColor, negColor],
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.tristate, {
      barWidth: 7,
      barSpacing: 4,
      posBarColor: defColor,
      negBarColor: negColor,
      zeroBarColor: '#e3e4e5',
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.discrete, {
      thresholdColor: negColor,
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.pie, {
      sliceColors: [defColor, negColor],
      width: 38,
      height: 38,
    });


    $.extend($.fn.sparkline.defaults.box, {
      boxLineColor: '#e3e4e5',
      boxFillColor: '#f3f5f6',
      whiskerColor: app.colors.primary,
      outlierLineColor: defColor,
      outlierFillColor: defColor,
      medianColor: negColor,
      targetColor: defColor,
    });


    $.extend($.fn.sparkline.defaults.bullet, {
      targetWidth: 2,
      targetColor: negColor,
      performanceColor: defColor,
      rangeColors: ['#f3f5f6', '#ebeced', '#e3e4e5'],
    });



    provider.provide('sparkline', function(){
      var options = {}
      options = $.extend(options, app.getDataOptions( $(this) ));

      $(this).sparkline('html', options);
    });


  };



  // Chart.js
  //
  provider.initChartjs = function() {
    if ( !window['Chart'] != undefined ) {
      return;
    }


    // Globals
    //
    $.extend(Chart.defaults.global, {
      defaultFontColor: app.colors.text,
      defaultFontSize: 13,
      defaultColor: 'rgba(0,0,0,0.05)',
    });


    // Globals
    //
    $.extend(Chart.defaults.scale.gridLines, {
      color: 'rgba(0,0,0,0.05)',
      zeroLineColor: 'rgba(0,0,0,0.15)',
    });



    // Legend labels
    //
    $.extend(Chart.defaults.global.legend.labels, {
      boxWidth: 24,
      padding: 16,
    });


    // Tooltip
    //
    $.extend(Chart.defaults.global.tooltips, {
      backgroundColor: 'rgba(0,0,0,0.7)',
      bodySpacing: 6,
      titleMarginBottom: 8,

      xPadding: 12,
      yPadding: 12,
      caretSize: 8,
      cornerRadius: 2,
    });


    // Arc
    //
    $.extend(Chart.defaults.global.elements.arc, {
      backgroundColor: 'rgba(51,202,185,0.5)',
    });


    // Line
    //
    $.extend(Chart.defaults.global.elements.line, {
      backgroundColor: 'rgba(51,202,185,0.5)',
      borderColor: 'rgba(51,202,185,0.5)',
      borderWidth: 1,
    });


    // Point
    //
    $.extend(Chart.defaults.global.elements.point, {
      backgroundColor: 'rgba(51,202,185,0.5)',
      borderColor: '#fff',
    });


    // Rectangle
    //
    $.extend(Chart.defaults.global.elements.rectangle, {
      backgroundColor: 'rgba(51,202,185,0.5)',
      borderColor: '#fff',
    });


  };



  // Morris
  //
  provider.initMorris = function() {
    if ( !window['Morris'] != undefined ) {
      return;
    }

  };




}(jQuery);
