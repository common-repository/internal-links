/*!********************************************!*\
  !*** ./src/admin/js/ilj_admin_menu_bar.js ***!
  \********************************************/
(function ($) {
  $(function () {
    var timer,
      delay = 3000;
    timer = setInterval(function () {
      $().updateLinkIndexStatus(timer);
    }, delay);
  });
})(jQuery);
