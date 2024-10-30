/*!****************************************************!*\
  !*** ./src/admin/js/ilj-link-index-status-func.js ***!
  \****************************************************/
(function ($) {
  $.fn.updateLinkIndexStatus = function (timer) {
    var data = {
      action: "ilj_render_batch_info",
      nonce: ilj_ajax_object.nonce
    };
    $old_progress_value = parseFloat($("#ilj_batch_progress").text());
    $.ajax({
      url: ilj_ajax_object.ajaxurl,
      type: "POST",
      data: data
    }).done(function (data) {
      $batch_build_info = data;
      $("#ilj_batch_status").text($batch_build_info.status);
      if ($('#ilj-index-status').length) {
        $('#ilj-index-status').text($batch_build_info.status);
      }
      $("#progressbar>div").animate({
        width: $batch_build_info.progress + "%"
      }, {
        duration: 1000,
        easing: 'swing'
      });
      if ($old_progress_value != $batch_build_info.progress) {
        $({
          Counter: $old_progress_value
        }).animate({
          Counter: $batch_build_info.progress
        }, {
          duration: 1000,
          easing: 'swing',
          step: function () {
            $('#ilj_batch_progress').text(Math.ceil(this.Counter) + "%");
          },
          always: function () {
            $('#ilj_batch_progress').text(Math.ceil($batch_build_info.progress) + "%");
          }
        });
      }
      if ($batch_build_info.is_complete == true) {
        clearInterval(timer);
        updateDashboardElements($batch_build_info);
      }
    });
  };
  function updateDashboardElements(batch_build_info) {
    if ($('.button.ilj-index-rebuild').length) {
      $('.button.ilj-index-rebuild').removeClass("hidden");
      $('.button.ilj-index-restart-rebuild').addClass("hidden");
      $('#ilj-index-rebuild-spinner').remove();
      $('#ilj-index-rebuild-message').html("");
    }
    if ($('#ilj-linkindex-count').length) {
      if (batch_build_info != "") {
        $('#ilj-linkindex-count').html(batch_build_info.linkindex_count);
        $('#ilj-configured-keywords-count').html(batch_build_info.keywords_count);
        $('#ilj-last-built').html(batch_build_info.last_built);
      }
    }
  }
})(jQuery);
