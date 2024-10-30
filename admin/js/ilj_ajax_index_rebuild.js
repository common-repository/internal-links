/*!************************************************!*\
  !*** ./src/admin/js/ilj_ajax_index_rebuild.js ***!
  \************************************************/
(function ($) {
  $(function () {
    var timer = null,
      delay = 3000;
    jQuery(document).on('click', '.button.ilj-index-rebuild', function (e) {
      e.preventDefault();
      if (jQuery(this).attr('disabled')) {
        return;
      }
      jQuery(this).after(jQuery('<span id="ilj-index-rebuild-spinner" class="spinner is-active" style="float:none"></span>'));
      var data = {
        'action': 'ilj_rebuild_index',
        'nonce': ilj_dashboard.nonce
      };
      jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        statusCode: {
          500: function (xhr) {
            if (!"responseJSON" in xhr || !["success", "error"].includes(xhr.responseJSON.status)) {
              var $feedback = jQuery(ilj_index_rebuild_button.error_500);
              return;
            }
            var $feedback = jQuery(xhr.responseJSON.message);
          }
        },
        success: function (data, textStatus, xhr) {
          var $feedback = jQuery(data.message);
          jQuery('#ilj-index-rebuild-message').html(xhr.responseJSON.message);
        }
      });
      jQuery(this).addClass('hidden');
      jQuery(".button.ilj-index-restart-rebuild").removeClass("hidden");
      if (timer != null) {
        clearInterval(timer);
      }
      timer = setInterval(function () {
        jQuery().updateLinkIndexStatus(timer);
      }, delay);
    });
    jQuery(document).on('click', '.button.ilj-index-restart-rebuild', function (e) {
      e.preventDefault();
      if (jQuery(this).attr('disabled')) {
        return;
      }
      jQuery('#ilj-index-rebuild-message').html("");
      if (jQuery("#ilj-index-rebuild-spinner").length == false) {
        jQuery(this).before(jQuery('<span id="ilj-index-rebuild-spinner" class="spinner is-active" style="float:none"></span>'));
      }
      var data = {
        'action': 'ilj_rebuild_index',
        'nonce': ilj_dashboard.nonce
      };
      jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        statusCode: {
          500: function (xhr) {
            if (!"responseJSON" in xhr || !["success", "error"].includes(xhr.responseJSON.status)) {
              var $feedback = jQuery(ilj_index_rebuild_button.error_500);
              return;
            }
            var $feedback = jQuery(xhr.responseJSON.message);
          }
        },
        success: function (data, textStatus, xhr) {
          var $feedback = jQuery(data.message);
          jQuery('#ilj-index-rebuild-message').html(xhr.responseJSON.message);
        }
      });
      if (timer != null) {
        clearInterval(timer);
      }
      timer = setInterval(function () {
        jQuery().updateLinkIndexStatus(timer);
      }, delay);
    });
  });
})(jQuery);
