/*!******************************************!*\
  !*** ./src/admin/js/ilj_admin_script.js ***!
  \******************************************/
(function ($) {
  $(function () {
    jQuery('.iljmessage.admin-warning-litespeed').on('click', '.notice-dismiss', function (e) {
      e.preventDefault();
      var data = {
        'action': 'ilj_dismiss_admin_warning_litespeed',
        'nonce': ilj_ajax_object.nonce
      };
      jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        statusCode: {
          500: function (xhr) {
            var $feedback = jQuery(xhr.responseJSON.message);
          }
        },
        success: function (data, textStatus, xhr) {}
      });
    });
  });
})(jQuery);
