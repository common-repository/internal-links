/*!***********************************!*\
  !*** ./src/admin/js/ilj_tools.js ***!
  \***********************************/
(function ($) {
  var ilj_select2_multi_input = {
    minimumResultsForSearch: 10,
    width: '70%'
  };
  var createFeedbackNotice = function () {
    var $feedbackNotice = $('<div/>').addClass('feedback').hide();
    $feedbackNotice.setNotice = function (message) {
      var $message = $('<p/>').html(message);
      var $dismiss = $('<button/>').addClass('notice-dismiss');
      $dismiss.on('click', function (e) {
        e.preventDefault();
        $feedbackNotice.hide();
      });
      $(this).removeClass().addClass('feedback');
      $(this).html($message).append($dismiss);
      return this;
    };
    $feedbackNotice.setSuccess = function (message) {
      this.setNotice(message);
      $(this).addClass('success');
      return this;
    };
    $feedbackNotice.setError = function (message) {
      this.setNotice(message);
      $(this).addClass('error');
      return this;
    };
    return $feedbackNotice;
  };
  $.fn.applyFileImport = function (options) {
    var that = this;
    this.createLoadingText = function () {
      var $loading = $('<span></span>').html('<small>' + ilj_tools.translation.loading + ' - <span>0</span>%</small>').hide();
      $loading.setPercent = function (percent) {
        $(this).find('span').text(percent);
      };
      return $loading;
    };
    this.each(function () {
      var settings = $.extend({
        fileSize: 0,
        fileType: '.'
      }, $(this).data());
      var $settingsForm = $(this).find('form[name^="ilj-import"]');
      var $settingsFile = $settingsForm.find('.import-file');
      var $uploadButton = $settingsForm.find('.button');
      var $progress = $settingsForm.find('.ilj-progress');
      var $progressBar = $progress.find('.ilj-progress-bar');
      var $settingsHint = $settingsForm.find('.hint');
      var $feedbackNotice = createFeedbackNotice();
      var $loading = that.createLoadingText();
      var $startImport = $('<button/>').addClass('button button-primary ilj-import-settings-start').text(ilj_tools.translation.start_import).on('click', function (e) {
        e.preventDefault();
        $cancelImport.hide();
        $(this).attr('disabled', true).removeClass('button-primary');
        var $importSpinner = $('<span/>').addClass('spinner');
        $(this).after($importSpinner);
        $importSpinner.addClass('is-active');
        $.ajax({
          url: ilj_tools.ajax_url,
          data: {
            'action': 'ilj_start_import',
            'nonce': ilj_tools.nonce,
            'file_type': settings.fileType
          },
          type: 'POST',
          success: function (resp) {
            setTimeout(function () {
              $feedbackNotice.hide();
              $uploadButton.show();
              $feedbackNotice.setSuccess(ilj_tools.translation.import_success);
              $startImport.hide().attr('disabled', false).addClass('button-primary');
              $importSpinner.remove();
              $feedbackNotice.show();
            }, 500);
          },
          error: function (jqXHR) {
            var error_msg = ilj_tools.translation.error;
            if (jqXHR.responseJSON.data) {
              error_msg = jqXHR.responseJSON.data;
            }
            $feedbackNotice.setError(error_msg);
            setTimeout(function () {
              $startImport.hide().attr('disabled', false).addClass('button-primary');
              $cancelImport.hide();
              $feedbackNotice.show();
              $uploadButton.show();
              $importSpinner.remove();
            }, 500);
          }
        });
      }).hide().insertAfter($settingsHint);
      var $cancelImport = $('<button/>').addClass('button').text(ilj_tools.translation.cancel_import).on('click', function (e) {
        e.preventDefault();
        $uploadButton.show();
        $feedbackNotice.hide();
        $startImport.hide();
        $cancelImport.hide();
      }).hide().insertAfter($startImport);
      $progressBar.setPercent = function (percent) {
        $(this).css('width', percent + "%");
      };
      $feedbackNotice.insertAfter($settingsHint);
      $progress.after($loading);
      $uploadButton.on('click', function (e) {
        e.preventDefault();
        $settingsFile.trigger('click');
      });
      $settingsFile.on('change', function (e) {
        e.preventDefault();
        if ($settingsFile[0].files[0].size > settings.fileSize) {
          $feedbackNotice.setError(ilj_tools.translation.upload_error_filesize).show();
          return;
        }
        var formData = new FormData();
        formData.append('action', 'ilj_upload_import');
        formData.append('file_type', settings.fileType); // settings / keyword
        formData.append('file_data', $settingsFile[0].files[0]);
        formData.append('file_name', $settingsFile[0].files[0].name);
        formData.append('nonce', ilj_tools.nonce);
        $.ajax({
          url: ilj_tools.ajax_url,
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          type: 'POST',
          success: function (resp) {
            $feedbackNotice.setNotice(ilj_tools.translation.upload_success);
            $cancelImport.after($('<div/>').addClass('clear'));
            setTimeout(function () {
              $progress.hide();
              $loading.hide();
              $feedbackNotice.show();
              $cancelImport.show();
              $startImport.show();
            }, 800);
          },
          error: function (jqXHR) {
            var error_msg = ilj_tools.translation.error;
            if (jqXHR.responseJSON.data) {
              error_msg = jqXHR.responseJSON.data;
            }
            $feedbackNotice.setError(error_msg);
            setTimeout(function () {
              $progress.hide();
              $loading.hide();
              $feedbackNotice.show();
              $uploadButton.show();
            }, 800);
          },
          beforeSend: function () {
            $feedbackNotice.hide();
            $uploadButton.hide();
            $progressBar.setPercent(0);
            $loading.setPercent(0);
            $progress.show();
            $loading.show();
          },
          xhr: function () {
            var myXhr = $.ajaxSettings.xhr();
            if (myXhr.upload) {
              myXhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                  var percent = e.loaded / e.total * 100;
                  percent = percent.toFixed(2);
                  $progressBar.setPercent(percent);
                  $loading.setPercent(percent);
                }
              }, false);
            }
            return myXhr;
          }
        });
        $(this).val('');
      });
    });
    return this;
  };
  $(function () {
    $('.ilj-upload-form').applyFileImport();
    $('button.ilj-export').on('click', function () {
      var export_type = $(this).data('export');
      var $spinner = $(this).parent().find('.spinner');
      $spinner.addClass('is-active');
      var current_url = window.location.href;
      var download_url = current_url + '&ilj_export=' + export_type;
      if (export_type == "keywords") {
        var $export_empty = $(this).closest('.wrap').find('input#ilj-export-empty');
        if ($export_empty.is(':checked')) {
          download_url += '&empty=1';
        }
        var $addcols = $('#ilj-export-additional-columns');
        if ($addcols.is(':checked')) {
          download_url += '&addcols=1';
        }
      }
      var $download_frame = $('<iframe/>').attr('src', download_url).css('display', 'none');
      $('body').append($download_frame);
      setTimeout(function () {
        $spinner.removeClass('is-active');
      }, 500);
    });
    jQuery('#ilj-import-intern-type-post, #ilj-import-intern-type-term').ilj_select2(ilj_select2_multi_input);
  });
})(jQuery);
