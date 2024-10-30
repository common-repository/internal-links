/*!*******************************************!*\
  !*** ./src/admin/js/ilj_menu_settings.js ***!
  \*******************************************/
function ilj_dynamicSelect(id, action, searchResults, nonceName) {
  jQuery(id).ilj_select2({
    width: '50%',
    minimumInputLength: 3,
    templateSelection: function (state) {
      var limit = 20;
      var element = jQuery(id);
      if (element && element.data("iljTitleCharacterLimit")) {
        limit = parseInt(element.data("iljTitleCharacterLimit"));
      }
      var title = state.text.length > limit ? state.text.substring(0, limit) + "..." : state.text;
      return title + " (ID: " + state.id + ")";
    },
    ajax: {
      url: ajaxurl,
      type: "POST",
      data: function (params) {
        return {
          action: action,
          nonce: ilj_menu_settings_obj[nonceName],
          search: params.term,
          per_page: searchResults,
          page: params.page || 1
        };
      },
      processResults: function (data) {
        if (data.length === 0) {
          return false;
        }
        more = true;
        if (data.length < searchResults) {
          more = false;
        }
        return data_new = {
          "results": data,
          "pagination": {
            "more": more
          }
        };
      }
    },
    language: {
      errorLoading: function () {
        return ilj_select2_translation.error_loading;
      },
      inputTooShort: function (args) {
        var remainingChars = args.minimum - args.input.length;
        return ilj_select2_translation.input_too_short + ': ' + remainingChars;
      },
      loadingMore: function () {
        return ilj_select2_translation.loading_more;
      },
      noResults: function () {
        return ilj_select2_translation.no_results;
      },
      searching: function () {
        return ilj_select2_translation.searching;
      }
    }
  });
}

/**
 * This function is used to disable a group of fields when a toggle is disabled,
 * enable it when its enabled ( both during page load and while changing the settings )
 *
 * @param {jQuery} field JQuery object for selected field
 * @param {jQuery} inverse_fields JQuery object for inverse fields.
 * @param {string} attribute The attribute to toggle (defaults to readonly)
 */
function ilj_menu_settings_inverse_fields(field, inverse_fields, attribute = 'readonly') {
  ilj_menu_settings_toggle_fields(field.prop('checked'), inverse_fields, attribute);
  field.on('change', function () {
    ilj_menu_settings_toggle_fields(field.prop('checked'), inverse_fields, attribute);
  });
}

/**
 * Disable the fields and its corresponding row.
 * @param {boolean} toggle Set to disabled or enabled based on this boolean.
 * @param {jQuery} inverse_fields The jQuery object for fields.
 * @param {string} attribute The attribute to toggle.
 */
function ilj_menu_settings_toggle_fields(toggle, inverse_fields, attribute) {
  if (toggle) {
    inverse_fields.each(function () {
      jQuery(this).prop(attribute, false).closest('tr').find('th').removeClass('inactive');
    });
  } else {
    inverse_fields.each(function () {
      jQuery(this).prop(attribute, true).closest('tr').find('th').addClass('inactive');
    });
  }
}
(function ($) {
  $(function () {
    jQuery('#ilj_settings_field_editor_role, #ilj_settings_field_index_generation, #ilj_settings_field_whitelist, #ilj_settings_field_taxonomy_whitelist, #ilj_settings_field_limit_taxonomy_list, #ilj_settings_field_keyword_order, #ilj_settings_field_no_link_tags, #ilj_settings_field_custom_fields_to_link_post, #ilj_settings_field_custom_fields_to_link_term').ilj_select2({
      minimumResultsForSearch: 10,
      width: '50%'
    });

    /**
     * ilj_settings_field_custom_fields_to_link_post, ilj_settings_field_custom_fields_to_link_term now supports
     * the regex rule matching. For example if the user types 'apple' in to the field we have to show the following three
     * options
     *
     * 1. Custom field name starts with 'apple'
     * 2. Custom field name ends with 'apple'
     * 3. Custom field name contains 'apple'
     *
     * To accomplish this behaviour we use the insertTag() option of select2 to dynamically add tags.
     */
    jQuery('#ilj_settings_field_custom_fields_to_link_post, #ilj_settings_field_custom_fields_to_link_term').ilj_select2({
      minimumResultsForSearch: 10,
      width: '50%',
      tags: true,
      insertTag: function (data, tag) {
        // Collect existing ids to perform O(1) lookup in a set.
        var ids = new Set(data.map(e => e.id));
        var startsWithId = `starts_with:${tag.id}`.trim();
        var endsWithId = `ends_with:${tag.id}`.trim();
        var containsId = `contains:${tag.id}`.trim();
        if (!ids.has(startsWithId)) {
          data.push({
            newOption: true,
            id: startsWithId,
            text: ilj_select2_translation.custom_field_starts_with.replace('%s', tag.text)
          });
        }
        if (!ids.has(endsWithId)) {
          data.push({
            newOption: true,
            id: endsWithId,
            text: ilj_select2_translation.custom_field_ends_with.replace('%s', tag.text)
          });
        }
        if (!ids.has(containsId)) {
          data.push({
            newOption: true,
            id: containsId,
            text: ilj_select2_translation.custom_field_has.replace('%s', tag.text)
          });
        }
      }
    });

    /***
     * When the user clicks on any one of the dynamically added option, we have to add them to select2 options
     * and make it selected.
     */
    jQuery('#ilj_settings_field_custom_fields_to_link_post, #ilj_settings_field_custom_fields_to_link_term').on('select2:select', function (e) {
      if (e.params.data.newOption) {
        var data = e.params.data;
        var newOption = new Option(data.text, data.id, true, true);
        $(this).append(newOption).trigger('change');
      }
    });

    /**
     * Toggle max incoming links field by limit incoming links toggle.
     */
    ilj_menu_settings_inverse_fields(jQuery('#ilj_settings_field_limit_incoming_links'), jQuery('#ilj_settings_field_max_incoming_links'));
    ilj_menu_settings_inverse_fields(jQuery('#ilj_settings_field_links_per_paragraph_switch'), jQuery('#ilj_settings_field_links_per_paragraph'));
    ilj_dynamicSelect('#ilj_settings_field_blacklist', 'ilj_search_posts', 20, 'nonce_ilj_search_posts');
    ilj_dynamicSelect('#ilj_settings_field_term_blacklist', 'ilj_search_terms', 20, 'nonce_ilj_search_terms');

    /**
     * Toggle Link preview template based on link preview toggle.
     */
    var link_preview_toggle_selector = jQuery('#ilj_settings_field_link_preview_switch');
    ilj_menu_settings_inverse_fields(link_preview_toggle_selector, jQuery('#ilj_settings_field_link_preview_template'));
    ilj_menu_settings_inverse_fields(link_preview_toggle_selector, jQuery('#ilj_settings_field_link_preview_template_reset_to_default'), 'disabled');

    /**
     * Toggle "links_per_page" and "links_per_target" depending on multiple keyword state
     */
    jQuery('#ilj_settings_field_multiple_keywords').on('change', function () {
      var $inverse_setting_field = jQuery('#ilj_settings_field_links_per_page, #ilj_settings_field_links_per_target, #ilj_settings_field_limit_incoming_links');
      if (this.checked) {
        $inverse_setting_field.each(function () {
          jQuery(this).closest('tr').find('th').addClass('inactive');
          if (!jQuery(this).parent().parent().hasClass('pro-setting')) {
            jQuery(this).prop('disabled', true);
          }
        });
        jQuery("#ilj_settings_field_links_per_page").val("0");
        jQuery("#ilj_settings_field_links_per_target").val("0");
      } else {
        $inverse_setting_field.each(function () {
          jQuery(this).closest('tr').find('th').removeClass('inactive');
          if (!jQuery(this).parent().parent().hasClass('pro-setting')) {
            jQuery(this).prop('disabled', false);
          }
        });
      }
    });
    jQuery('#ilj_settings_field_index_generation').on('change', function () {
      var $inverse_setting_field = jQuery('#ilj_settings_field_hide_status_bar');
      if (this.value == "index_mode_none") {
        $inverse_setting_field.each(function () {
          jQuery(this).closest('tr').find('th').addClass('inactive');
        });
        $inverse_setting_field.prop('disabled', true);
      } else {
        $inverse_setting_field.each(function () {
          jQuery(this).closest('tr').find('th').removeClass('inactive');
        });
        $inverse_setting_field.prop('disabled', false);
      }
    });
    var index_generation_mode = jQuery('#ilj_settings_field_index_generation').val();
    if (index_generation_mode == 'index_mode_none') {
      var disable_hide_status_bar_option = jQuery("#ilj_settings_field_hide_status_bar");
      disable_hide_status_bar_option.each(function () {
        jQuery(this).closest('tr').find('th').addClass('inactive');
      });
      disable_hide_status_bar_option.prop('disabled', true);
    }
    $(function () {
      var $multiple_keywords = jQuery('#ilj_settings_field_multiple_keywords');
      var $inverse_setting_field = jQuery('#ilj_settings_field_links_per_page, #ilj_settings_field_links_per_target');
      if (!$multiple_keywords.length) {
        return;
      }
      if ($multiple_keywords[0].checked) {
        $inverse_setting_field.each(function () {
          jQuery(this).closest('tr').find('th').addClass('inactive');
          if (!jQuery(this).parent().parent().hasClass('pro-setting')) {
            jQuery(this).prop('disabled', true);
          }
        });
      } else {
        $inverse_setting_field.each(function () {
          if (!jQuery(this).parent().parent().hasClass('pro-setting')) {
            jQuery(this).prop('disabled', false);
          }
        });
      }
      $('#ilj_settings_field_links_per_page, #ilj_settings_field_links_per_target, #ilj_settings_field_links_per_paragraph, #ilj_settings_field_max_incoming_links').change(function () {
        const val = jQuery(this).val();
        if (val == '-0') {
          $(this).val('');
          $(this).focus();
          alert(ilj_menu_settings_translation.negative_not_allowed_message);
        }
      });
    });

    /**
     * Adding Tooltip
     */
    var tipsoConfig = {
      width: '',
      maxWidth: '200',
      useTitle: true,
      delay: 100,
      speed: 500,
      background: '#32373c',
      color: '#eeeeee',
      size: 'small'
    };
    jQuery('.tip').iljtipso(tipsoConfig);
    jQuery('.pro-title, .pro-setting').attr('title', ilj_menu_settings_translation.pro_feature_title);
    jQuery('.pro-title, .pro-setting').on('click', function () {
      window.open(ilj_menu_settings_translation.upgrade_to_pro_link, '_blank');
    });
    jQuery(document).on('click', '.button.ilj-cancel-schedules', function (e) {
      e.preventDefault();
      var user_confirmed = confirm(ilj_menu_settings_translation.confirm_cancel_message);
      if (!user_confirmed) {
        return;
      }
      if (jQuery(this).attr('disabled')) {
        return;
      }
      jQuery(this).after(jQuery('<span id="ilj-cancel-schedule-spinner" class="spinner is-active" style="float:none"></span>'));
      var data = {
        'action': 'ilj_cancel_schedules',
        'nonce': ilj_menu_settings_obj.nonce
      };
      jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: data,
        statusCode: {
          500: function (xhr) {
            if (!'responseJSON' in xhr || !['success', 'error'].includes(xhr.responseJSON.status)) {
              return;
            }
            console.log('Error: ' + xhr.responseJSON.message);
          }
        },
        success: function (data, textStatus, xhr) {
          jQuery('#ilj-cancel-schedule-spinner').remove();
          if (jQuery('#ilj-cancel-schedule-feedback').length === 0) {
            var successMessage = '<div id="ilj-cancel-schedule-feedback" class="notice notice-success is-dismissible"><p>' + ilj_menu_settings_translation.success_message + '</p></div>';
            jQuery(successMessage).insertAfter('.button.ilj-cancel-schedules');
            setTimeout(function () {
              jQuery("#ilj-cancel-schedule-feedback").remove();
            }, 2000);
          }
        }
      });
    });
  });
  jQuery(document).on('click', '.button.ilj-fix-database-collation', function (e) {
    e.preventDefault();
    var user_confirmed = confirm(ilj_menu_settings_translation.confirm_database_collation_fix_message);
    if (!user_confirmed) {
      return;
    }
    if (jQuery(this).attr('disabled')) {
      return;
    }
    jQuery(this).after(jQuery('<span id="ilj-fix-database-collation-spinner" class="spinner is-active" style="float:none"></span>'));
    var data = {
      'action': 'ilj_fix_database_collation',
      'nonce': ilj_menu_settings_obj.nonce_ilj_fix_collation
    };
    jQuery.ajax({
      url: ajaxurl,
      type: 'POST',
      data: data,
      statusCode: {
        500: function (xhr) {
          if (!'responseJSON' in xhr || !['success', 'error'].includes(xhr.responseJSON.status)) {
            return;
          }
          console.log('Error: ' + xhr.responseJSON.message);
        }
      },
      success: function (data, textStatus, xhr) {
        jQuery('#ilj-fix-database-collation-spinner').remove();
        if (jQuery('#ilj-fix-database-collation-feedback').length === 0) {
          var successMessage = '<div id="ilj-fix-database-collation-feedback" class="notice notice-success is-dismissible"><p>' + ilj_menu_settings_translation.success_message + '</p></div>';
          jQuery(successMessage).insertAfter('.button.ilj-fix-database-collation');
          setTimeout(function () {
            jQuery("#ilj-fix-database-collation-feedback").remove();
          }, 2000);
        }
      }
    });
  });
})(jQuery);
