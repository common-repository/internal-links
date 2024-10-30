/*!************************************!*\
  !*** ./src/admin/js/ilj_editor.js ***!
  \************************************/
(function ($) {
  $.fn.ilj_editor = function () {
    var elem = this;
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

    /**
     * Creates a UI styled toggle field
     */
    var getToggleField = function (id, checked = false, disabled = false) {
      var $toggleField = $('<div />').addClass('ilj-toggler-wrap');
      var checkboxAttributes = {
        type: 'checkbox',
        id: id,
        name: id,
        value: 1
      };
      if (checked) {
        checkboxAttributes.checked = 'checked';
      }
      if (disabled) {
        checkboxAttributes.disabled = 'disabled';
      }
      var $checkbox = $('<input />').addClass('ilj-toggler-input').attr(checkboxAttributes);
      $toggleField.append($checkbox);
      var $label = $('<label />').addClass('ilj-toggler-label').attr({
        for: id
      });
      var $labelInside = $('<div class="ilj-toggler-switch" aria-hidden="true">' + '<div class="ilj-toggler-option-l" aria-hidden="true">' + '<svg class="ilj-toggler-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" width="548.9" height="548.9" viewBox="0 0 548.9 548.9" xml:space="preserve"><polygon points="449.3 48 195.5 301.8 99.5 205.9 0 305.4 95.9 401.4 195.5 500.9 295 401.4 548.9 147.5 "/></svg>' + '</div>' + '<div class="ilj-toggler-option-r" aria-hidden="true">' + '<svg class="ilj-toggler-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" x="0" y="0" viewBox="0 0 28 28" xml:space="preserve"><polygon points="28 22.4 19.6 14 28 5.6 22.4 0 14 8.4 5.6 0 0 5.6 8.4 14 0 22.4 5.6 28 14 19.6 22.4 28 " fill="#030104"/></svg>' + '</div>' + '</div>');
      $label.append($labelInside);
      $toggleField.append($label);
      return $('<div />').append($toggleField).html();
    };
    var get_upgrade_button = function (pro = false) {
      $button = '';
      if (pro) {
        $button = '<button class="ilj-upgrade-to-pro-button" ilj-link-target="' + ilj_editor_translation.upgrade_to_pro_link + '">' + ilj_editor_translation.upgrade_to_pro_button_text + '</button>';
      }
      return $button;
    };

    /**
     * The box object represents the ILJ editor box
     */
    var Box = {
      keywords: [],
      inputField: $(elem).find('input[name="ilj_linkdefinition_keys"]'),
      blacklistField: $(elem).find('input[name="ilj_blacklistdefinition"]'),
      isBlacklisted: $(elem).find('input[name="ilj_is_blacklisted"]'),
      limitField: $(elem).find('input[name="ilj_limitincominglinks"]'),
      maxlimitField: $(elem).find('input[name="ilj_maxincominglinks"]'),
      linksperparagraphField: $(elem).find('input[name="ilj_limitlinksperparagraph"]'),
      maxlinksperparagraphField: $(elem).find('input[name="ilj_linksperparagraph"]'),
      limitoutgoingField: $(elem).find('input[name="ilj_limitoutgoinglinks"]'),
      maxoutgoinglimitField: $(elem).find('input[name="ilj_maxoutgoinglinks"]'),
      errorMessage: $('<div class="error-feedback"></div>'),
      keywordInputInfo: $('<span class="dashicons dashicons-info"></span>').css({
        'margin-top': '10px'
      }).iljtipso({
        content: $('<ul>' + '<li>' + ilj_editor_translation.howto_case + '</li>' + '<li>' + ilj_editor_translation.howto_keyword + '</li>' + '</ul>').css({
          'list-style-type': 'square',
          'list-style-position:': 'outside',
          'text-align': 'left',
          'padding': '0px',
          'margin': '10px 20px'
        }),
        delay: 100,
        speed: 500,
        background: '#32373c',
        color: '#eeeeee',
        size: 'small',
        position: 'left'
      }),
      gapInputInfo: $('<span class="dashicons dashicons-info"></span>').iljtipso({
        content: $('<p>' + ilj_editor_translation.howto_gap + '</p>').css({
          'text-align': 'left',
          'padding': '0px',
          'margin': '10px'
        }),
        delay: 100,
        speed: 500,
        background: '#32373c',
        color: '#eeeeee',
        size: 'small',
        position: 'left',
        tooltipHover: true
      }),
      limitLinksPerParagraphInputInfo: $('<span class="dashicons dashicons-info"></span>').iljtipso({
        content: $('<ul>' + '<li>' + ilj_editor_translation.howto_links_per_paragraph + '</li>' + '</ul>').css({
          'list-style-type': 'square',
          'list-style-position:': 'outside',
          'text-align': 'left',
          'padding': '0px',
          'margin': '10px 20px'
        }),
        delay: 100,
        speed: 500,
        background: '#32373c',
        color: '#eeeeee',
        size: 'small',
        position: 'left'
      }),
      blacklistStatusInfo: $('<span class="dashicons dashicons-info"></span>').iljtipso({
        content: $('<ul>' + '<li>' + ilj_editor_translation.howto_add_to_blacklist + '</li>' + '</ul>').css({
          'list-style-type': 'square',
          'list-style-position:': 'outside',
          'text-align': 'left',
          'padding': '0px',
          'margin': '10px 20px'
        }),
        delay: 100,
        speed: 500,
        background: '#32373c',
        color: '#eeeeee',
        size: 'small',
        position: 'left'
      }),
      limitIncomingLinksInfo: $('<span class="dashicons dashicons-info"></span>').iljtipso({
        content: $('<ul>' + '<li>' + ilj_editor_translation.howto_limit_incoming_links + '</li>' + '</ul>').css({
          'list-style-type': 'square',
          'list-style-position:': 'outside',
          'text-align': 'left',
          'padding': '0px',
          'margin': '10px 20px'
        }),
        delay: 100,
        speed: 500,
        background: '#32373c',
        color: '#eeeeee',
        size: 'small',
        position: 'left'
      }),
      limitOutgoingLinksInfo: $('<span class="dashicons dashicons-info"></span>').iljtipso({
        content: $('<ul>' + '<li>' + ilj_editor_translation.howto_limit_outgoing_links + '</li>' + '</ul>').css({
          'list-style-type': 'square',
          'list-style-position:': 'outside',
          'text-align': 'left',
          'padding': '0px',
          'margin': '10px 20px'
        }),
        delay: 100,
        speed: 500,
        background: '#32373c',
        color: '#eeeeee',
        size: 'small',
        position: 'left'
      }),
      tabs: $('<div class="tab">' + '   <button class="tablinks active">Keywords</button>' + '   <button class="tablinks">Settings</button>' + '</div>'),
      inputGui: $('<div id="Keywords" class="tabcontent active">' + '   <div class="input-gui">' + '       <input class="keywordInput" type="text" name="keyword" placeholder="' + ilj_editor_translation.placeholder_keyword + '"/>' + '       <a class="button add-keyword">' + ilj_editor_translation.add_keyword + '</a>' + '       <div class="gaps">' + '           <h4>' + ilj_editor_translation.headline_gaps + '</h4> ' + '           <input type="number" name="count" placeholder="0"/>' + '           <a class="button add-gap">' + ilj_editor_translation.add_gap + '</a>' + '           <h5>' + ilj_editor_translation.gap_type + '</h5>' + '           <div class="gap-types">' + '               <div class="type min"><label for="gap-min" class="tip" title="' + ilj_editor_translation.type_min + '"><input type="radio" name="gap" value="min" id="gap-min"/><span class="dashicons dashicons-upload"></span></label></div>' + '               <div class="type exact active"><label for="gap-exact" class="tip" title="' + ilj_editor_translation.type_exact + '"><input type="radio" name="gap" value="exact" checked="checked" id="gap-exact"/><span class="dashicons dashicons-migrate"></span></label></div>' + '               <div class="type max"><label for="gap-max" class="tip" title="' + ilj_editor_translation.type_max + '"><input type="radio" name="gap" value="max" id="gap-max"/><span class="dashicons dashicons-download"></span></label></div>' + '           </div>' + '           <div class="gap-hints">' + '               <div class="hint min" id="min"><p class="howto">' + ilj_editor_translation.howto_gap_min + '</p></div>' + '               <div class="hint exact active" id="exact"><p class="howto">' + ilj_editor_translation.howto_gap_exact + '</p></div>' + '           <div class="hint max" id="max"><p class="howto">' + ilj_editor_translation.howto_gap_max + '</p></div>' + '           </div>' + '       </div>' + '       <a class="show-gaps">&raquo; ' + ilj_editor_translation.insert_gaps + '</a>' + '   </div>' + '   <div class="keyword-view-gui">' + '       <h4>' + ilj_editor_translation.headline_configured_keywords + '</h4>' + '       <ul class="keyword-view" role="list"></ul>' + '   </div>' + '</div>'),
      settingsTab: $('<div id="Settings" class="settings tabcontent">' + '</div>'),
      limitIncomingLinks: $('   <div class="input-gui limit-incoming-links ilj-row ' + ilj_editor_basic_restriction.disable_setting + '" ' + ilj_editor_basic_restriction.disabled + ' >' + '       <div class="input-gui ilj-row ilj-editor-settings-field">' + '           <div class="col-7 limit-incoming-links-label">' + '               <label><span ' + ilj_editor_basic_restriction.disable_title + '>' + ilj_editor_basic_restriction.lock_icon + ilj_editor_translation.limit_incoming_links + '</span></label>' + '           </div>' + '           <div class="col-5 ilj-limit-incoming-links-toggle">' + getToggleField('limitincominglinks', false, ilj_editor_basic_restriction.is_active) + '           </div>' + '       </div>' + '   </div>'),
      maxIncomingLinks: $('       <div class="input-gui max-incoming-links ilj-row ' + ilj_editor_basic_restriction.disable_setting + '" style="display:none;" ' + ilj_editor_basic_restriction.disabled + '>' + '           <div class="col-7">' + '               <label><span ' + ilj_editor_basic_restriction.disable_title + '>' + ilj_editor_basic_restriction.lock_icon + ilj_editor_translation.max_incoming_links + '</span></label>' + '           </div>' + '           <div class="col-5">' + '               <input type="number" class="maxincominglinks ' + ilj_editor_basic_restriction.disable_setting + '" min="1" value="1" name="ilj_maxincominglinks" ' + ilj_editor_basic_restriction.disabled + ' >' + '           </div>' + '       </div>'),
      limitOutgoingLinks: $('   <div class="input-gui limit-outgoing-links ilj-row ' + ilj_editor_basic_restriction.disable_setting + '" ' + ilj_editor_basic_restriction.disabled + ' >' + '       <div class="input-gui ilj-row ilj-editor-settings-field">' + '           <div class="col-7 limit-outgoing-links-label">' + '               <label><span ' + ilj_editor_basic_restriction.disable_title + '>' + ilj_editor_basic_restriction.lock_icon + ilj_editor_translation.limit_outgoing_links + '</span></label>' + '           </div>' + '           <div class="col-5 ilj-limit-outgoing-links-toggle">' + getToggleField('limitoutgoinglinks', false, ilj_editor_basic_restriction.is_active) + '           </div>' + '       </div>' + get_upgrade_button(ilj_editor_basic_restriction.is_active) + '   </div>'),
      maxOutgoingLinks: $('       <div class="input-gui max-outgoing-links ilj-row ' + ilj_editor_basic_restriction.disable_setting + '" style="display:none;" ' + ilj_editor_basic_restriction.disabled + '>' + '           <div class="col-7">' + '               <label><span ' + ilj_editor_basic_restriction.disable_title + '>' + ilj_editor_basic_restriction.lock_icon + ilj_editor_translation.max_outgoing_links + '</span></label>' + '           </div>' + '           <div class="col-5">' + '               <input type="number" class="maxoutgoinglinks ' + ilj_editor_basic_restriction.disable_setting + '" min="1" value="1" name="ilj_maxoutgoinglinks" ' + ilj_editor_basic_restriction.disabled + ' >' + '           </div>' + '       </div>'),
      blacklistStatus: $('   <div class="input-gui ilj-row blacklistStatus ilj-editor-settings-field">' + '       <div class="col-7 blacklist-status-label">' + '           <label>' + ilj_editor_translation.is_blacklisted + '</label>' + '       </div>' + '       <div class="col-5 ilj-blacklist-status-toggle">' + getToggleField('is_blacklisted', false) + '       </div>' + '   </div>'),
      blacklistKeywords: $('   <div class="input-gui ilj-row blacklistKeyword">' + '       <div class="col-12">' + '           <label>' + ilj_editor_translation.blacklist_incoming_links + '</label>' + '           <input class="keywordInput" type="text" name="blacklistkeyword"></input>' + '           <a class="button add-keyword">' + ilj_editor_translation.add_keyword + '</a>' + '       </div>' + '       <div class="col-12 keyword-view-gui blacklistView">' + '           <h4>' + ilj_editor_translation.headline_configured_keywords_blacklist + '</h4>' + '           <ul class="keyword-view" role="list"></ul>' + '       </div>' + '   </div>'),
      limitLinksPerParagraph: $('   <div class="input-gui ilj-row limit-links-per-paragraph ' + ilj_editor_basic_restriction.disable_setting + '" ' + ilj_editor_basic_restriction.disabled + '>' + '       <div class="input-gui ilj-row ilj-editor-settings-field">' + '           <div class="col-7 limit-links-per-paragraph-label">' + '               <label ><span ' + ilj_editor_basic_restriction.disable_title + '>' + ilj_editor_basic_restriction.lock_icon + ilj_editor_translation.limit_links_per_paragraph + '</span></label>' + '           </div>' + '           <div class="col-5 ilj-limit-links-per-paragraph-toggle">' + getToggleField('limitlinksperparagraph', false, ilj_editor_basic_restriction.is_active) + '           </div>' + '       </div>' + '   </div>'),
      linksPerParagraph: $('   <div class="input-gui max-limit-links-in-paragraph ilj-row  ' + ilj_editor_basic_restriction.disable_setting + '" style="display:none;"  ' + ilj_editor_basic_restriction.disabled + '>' + '       <div class="col-7">' + '           <label><span ' + ilj_editor_basic_restriction.disable_title + '>' + ilj_editor_basic_restriction.lock_icon + ilj_editor_translation.max_links_per_paragraph + '</span></label>' + '       </div>' + '       <div class="col-5">' + '           <input class="maxlinksperparagraph ' + ilj_editor_basic_restriction.disable_setting + '" type="number" min="1" value="1" name="ilj_linksperparagraph"  ' + ilj_editor_basic_restriction.disabled + '>' + '       </div>' + '   </div>' + '   <br>'),
      footer: $('#ilj_keyword_metabox_footer').html(),
      // A section to display feedback upon error or success
      feedback: $('<div id="ilj-editor-feedback"></div>'),
      /**
       * Init function for the object
       */
      init: function () {
        var that = this;

        /**
         * Hide the standard, non-javascript, parts
         */
        this.inputField.css('display', 'none').parent('p').hide();
        this.clearError();

        /**
         * Init meta box
         */
        elem.find('.inside').append(this.feedback, this.tabs, this.errorMessage, this.inputGui, this.settingsTab, this.footer);
        elem.find('h2').prepend($('<i/>').addClass('icon icon-ilj'));

        /**
         * Init Settings tab
         */
        if (ilj_editor_basic_restriction.current_screen != "ilj_customlinks") {
          elem.find('.settings.tabcontent').append(this.blacklistStatus);
        }
        if (ilj_editor_basic_restriction.current_screen != "ilj_customlinks") {
          elem.find('.settings.tabcontent').append(this.limitLinksPerParagraph);
          elem.find('.settings.tabcontent').append(this.linksPerParagraph);
        }
        elem.find('.settings.tabcontent').append(this.limitIncomingLinks, this.maxIncomingLinks);
        if (ilj_editor_basic_restriction.current_screen != "ilj_customlinks") {
          elem.find('.settings.tabcontent').append(this.limitOutgoingLinks, this.maxOutgoingLinks);
        }
        if (ilj_editor_basic_restriction.current_screen != "ilj_customlinks") {
          elem.find('.settings.tabcontent').append(this.blacklistKeywords);
        }

        //jQuery UI sortable:
        this.keywords = this.inputGui.ilj_keywords({
          inputField: this.inputField,
          errorMessage: this.errorMessage,
          requiresPro: false,
          sortable: true
        });
        this.blacklistKeywords = this.settingsTab.ilj_keywords({
          inputField: this.blacklistField,
          errorMessage: this.errorMessage,
          requiresPro: true,
          sortable: false
        });

        /**
         * Insert information text
         */
        this.inputGui.find('.add-keyword').after(this.keywordInputInfo);
        this.inputGui.find('.add-gap').after(this.gapInputInfo);

        /**
         * Disable enter hits on input fields
         */
        this.inputGui.on('keypress', 'input[name="count"]', function (e) {
          if (e.keyCode === 13) {
            that.inputGui.find('a.add-gap').trigger('click');
          }
          return e.keyCode != 13;
        });
        this.inputGui.on('keypress', 'input[name="gap"]', function (e) {
          if (e.keyCode === 13) {
            that.inputGui.find('input[name="count"]').trigger('focus');
          }
          return e.keyCode != 13;
        });
        this.inputGui.on('click', '.show-gaps', function (e) {
          e.preventDefault();
          $(this).hide();
          that.inputGui.find('.gaps').show();
        });
        this.inputGui.on('click', 'a.add-gap', function (e) {
          e.preventDefault();
          var $count_field = $(this).siblings('input[name="count"]');
          var gap_type = $(this).siblings('.gap-types').find('input[name="gap"]:checked').val();
          var gap_value = $count_field.val();
          var old_value = that.inputGui.find('input[name="keyword"]').val();
          var gap_placeholder = '';
          if (/^\d+$/.test(gap_value) === false) {
            return;
          }
          switch (gap_type) {
            case "min":
              gap_placeholder = '{+' + gap_value + '}';
              break;
            case "max":
              gap_placeholder = '{-' + gap_value + '}';
              break;
            default:
              gap_placeholder = '{' + gap_value + '}';
          }
          $count_field.val('');
          that.inputGui.find('input[name="keyword"]').val(old_value + gap_placeholder);
          that.inputGui.find('input[name="keyword"]').trigger('focus');
        });

        /**
         * All interactions with the gap types
         */
        this.inputGui.on('change', 'input[name="gap"]', function () {
          var selected = $(this).val();
          that.inputGui.find('.gap-types .type').removeClass('active');
          that.inputGui.find('.gap-types .type.' + selected).addClass('active');
          that.inputGui.find('.gap-hints .hint').removeClass('active');
          that.inputGui.find('.gap-hints .hint.' + selected).addClass('active');
        });
        this.tabs.on('click', '.tablinks', function (evt) {
          evt.preventDefault();
          jQuery(".tabcontent").removeClass("active");
          jQuery(".tablinks").removeClass("active");
          $(this).addClass("active");
          var tabname = $(this).html();
          jQuery("#" + tabname).addClass("active");
        });
        this.settingsTab.find('.ilj-limit-links-per-paragraph-toggle').append(this.limitLinksPerParagraphInputInfo);
        this.settingsTab.find('.ilj-limit-incoming-links-toggle').append(this.limitIncomingLinksInfo);
        this.settingsTab.find('.ilj-limit-outgoing-links-toggle').append(this.limitOutgoingLinksInfo);
        this.settingsTab.find('.ilj-blacklist-status-toggle').append(this.blacklistStatusInfo);
        this.settingsTab.on('change', this.isBlacklisted, function () {
          var toggleCheck = $("input[name='is_blacklisted']");
          var isBlacklistedFieldValue = that.toggleSwitchInput(toggleCheck);
          that.isBlacklisted.val(isBlacklistedFieldValue);
        });
        $('#ilj-delete-cache').on('click', function () {
          $(this).prop('disabled', true);
          $(this).next('.spinner').removeClass('ilj-hidden');
          const button = $(this);
          const feedback = $('#ilj-editor-feedback');
          $.ajax({
            url: $(this).data('ilj-delete-cache-url')
          }).always(function () {
            button.next('.ilj-spinner').addClass('ilj-hidden');
            button.prop('disabled', false);
            feedback.html('<div class="notice notice-success is-dismissible ilj-editor-feedback-notice"><p>' + ilj_editor_translation.cache_cleared + '</p></div>');
            // Remove the notice after 2 seconds
            setTimeout(function () {
              feedback.html('');
            }, 2000);
          });
        });
        this.initSettingsTab();
      },
      /**
      * Onchange Toggle Switch
      */
      toggleSwitchInput: function (toggleCheck, showField = "") {
        var checked = toggleCheck.prop("checked");
        if (checked) {
          if (showField != "") {
            this.settingsTab.find("." + showField).css("display", "block");
          }
          return 1;
        } else {
          if (showField != "") {
            this.settingsTab.find("." + showField).css("display", "none");
          }
          return 0;
        }
      },
      /**
       * Initialize Settings Tab
       */
      initSettingsTab: function () {
        var is_blacklisted = this.isBlacklisted.val();
        if (is_blacklisted == true) {
          $("input[name='is_blacklisted']").prop('checked', true);
        }
        if (ilj_editor_basic_restriction.is_active) {
          $(document).on('click', '.ilj-upgrade-to-pro-button', function () {
            var url = $(this).attr('ilj-link-target');
            window.open(url, '_blank');
          });
        }
      },
      /**
       * Displays an error message
       */
      setError: function (message) {
        this.errorMessage.html(message);
        this.errorMessage.show();
      },
      /**
       * Clears the error message
       */
      clearError: function () {
        this.errorMessage.html('');
        this.errorMessage.hide();
      }
    };

    //Initializing the ILJ box object
    Box.init();
  };
  $(function () {
    jQuery('#ilj_linkdefinition').ilj_editor();
    jQuery('.pro-setting').attr('title', ilj_editor_translation.pro_feature_title);
    jQuery('.pro-setting').on('click', function () {
      window.open(ilj_editor_translation.upgrade_to_pro_link, '_blank');
    });
  });
})(jQuery);
