import '../css/app.scss';

// We need bootstrap collapse
import collapse from 'bootstrap/js/src/collapse';
var { DateTime } = require('luxon');
import hljs from 'highlightjs';

import $ from 'jquery';

$(document).ready(function() {
  // hamburger menu toggle foo
  $("#menu-toggle").click(function(e) {
      e.preventDefault();
      $("#main-wrapper").toggleClass("toggled");
  });
  $(window).resize(function() {
    if($(window).width()<=768){
      $("#main-wrapper").removeClass("toggled");
    }else{
      $("#main-wrapper").addClass("toggled");
    }
  });
  convertDates();

  hljs.initHighlightingOnLoad();
});

function convertDates() {
  Array.from(document.querySelectorAll('[data-processor="localdate"]')).forEach(function(element) {
    const value = element.dataset.value;

    element.textContent = DateTime.fromISO(value).toLocaleString(DateTime.DATETIME_FULL);
  });
}