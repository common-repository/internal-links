/******/ "use strict";
/******/ var __webpack_modules__ = ({

/***/ "./assets/admin/js/components/ilj_modal.js":
/*!*************************************************!*\
  !*** ./assets/admin/js/components/ilj_modal.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   iljCreateModal: () => (/* binding */ iljCreateModal)
/* harmony export */ });
function iljCreateModal(title, content) {
  var modal = jQuery('<div/>').addClass('ilj_modal show');
  var modal_wrapper = jQuery('<div/>').addClass('ilj_modal_wrap').append(modal);
  var header = jQuery('<div/>').addClass('ilj_modal_header').append(jQuery('<h2 />').text(title));
  var body = jQuery('<div/>').addClass('ilj_modal_body').html(content);
  var footer = jQuery('<div/>').addClass('ilj_modal_footer').append(jQuery('<button/>').text('OK').addClass('button button-primary').on('click', function (e) {
    closeModal(e);
  }));
  var closeModal = function () {
    modal.removeClass('show').addClass('hide');
    jQuery('body').css({
      overflowY: 'auto'
    });
    setTimeout(function () {
      modal_wrapper.remove();
    }, 200);
  }.bind(modal_wrapper);
  modal.append(header);
  modal.append(body);
  modal.append(footer);
  jQuery('body').append(modal_wrapper).css({
    overflowY: 'hidden'
  });
}

/***/ })

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/define property getters */
/******/ (() => {
/******/ 	// define getter functions for harmony exports
/******/ 	__webpack_require__.d = (exports, definition) => {
/******/ 		for(var key in definition) {
/******/ 			if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 				Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 			}
/******/ 		}
/******/ 	};
/******/ })();
/******/ 
/******/ /* webpack/runtime/hasOwnProperty shorthand */
/******/ (() => {
/******/ 	__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ })();
/******/ 
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
var __webpack_exports__ = {};
/*!***************************************!*\
  !*** ./src/admin/js/ilj_statistic.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var components_ilj_modal__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! components/ilj_modal */ "./assets/admin/js/components/ilj_modal.js");

(function ($) {
  /**
   * $ plugin for the tabnav handling
   */
  $.fn.ilj_tabnav = function () {
    var container = this;
    $(this).find('.nav-tab-wrapper').on('click', 'a', function () {
      //toggling tab-nav:
      $(this).addClass('nav-tab-active');
      $(this).siblings('a').removeClass('nav-tab-active');
      //toggling tab-content:
      container.find('.tab-content').removeClass('active');
      container.find('.tab-content#' + $(this).data('target')).addClass('active');
    });
  };

  /**
   * Config for tipso
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

  // /**
  //  * Config for swal
  //  */
  // var swalHandle = Swal.mixin({
  //     buttonsStyling: false,
  //     customClass: {
  //         confirmButton: 'button button-primary'
  //     },
  //     width: '48rem'
  // });

  /**
   * Internationalization config for dataTables
   */
  var dataTables_language = {
    aria: {
      sortAscending: ilj_statistic_translation.datatables_aria_sortAscending,
      sortDescending: ilj_statistic_translation.datatables_aria_sortDescending
    },
    paginate: {
      first: ilj_statistic_translation.datatables_paginate_first,
      last: ilj_statistic_translation.datatables_paginate_last,
      next: ilj_statistic_translation.datatables_paginate_next,
      previous: ilj_statistic_translation.datatables_paginate_previous
    },
    emptyTable: ilj_statistic_translation.datatables_empty_table,
    info: ilj_statistic_translation.datatables_info,
    infoEmpty: ilj_statistic_translation.datatables_info_empty,
    infoFiltered: ilj_statistic_translation.datatables_info_filtered,
    lengthMenu: ilj_statistic_translation.datatables_length_menu,
    loadingRecords: ilj_statistic_translation.datatables_loading_records,
    processing: ilj_statistic_translation.datatables_processing,
    search: ilj_statistic_translation.datatables_search,
    zeroRecords: ilj_statistic_translation.datatables_zero_records
  };
  var link_statistics_table_data = '';
  var anchor_statistics_table_data = '';
  var chunk_size = 1000;
  /**
   * Initialize the warning tip if possible
   */
  $(function () {
    var $warningTip = $('.warning-tip');
    if (!$warningTip.length) {
      return;
    }
    var tipsoWarningConfig = $.extend(Object.assign({}, tipsoConfig), {
      tooltipHover: true,
      useTitle: false,
      content: $('<div/>').html($warningTip.find('.the-tip').html()).css({
        'margin': '10px 20px',
        'display': 'block'
      }),
      maxWidth: '250px'
    });
    $warningTip.iljtipso(tipsoWarningConfig);
  });

  /**
   * Initializing the statistics
   */
  $(function () {
    var selected_types = ilj_link_statistic_filter_types.reduce(function (acc, e) {
      if (undefined === acc[e.main_type]) {
        acc[e.main_type] = new Set();
      }
      acc[e.main_type].add(e.sub_type);
      return acc;
    }, {});
    var all_types = {};
    Object.keys(selected_types).forEach(function (key, index) {
      all_types[key] = Array.from(new Set(selected_types[key]));
    });

    /**
     * Returns the translated label for a parent type (post, term, custom)
     */
    var get_main_type_label = function (slug) {
      switch (slug) {
        case 'post':
          slug = ilj_statistic_translation.filter_section_posts_pages;
          break;
        case 'term':
          slug = ilj_statistic_translation.filter_section_taxonomies;
          break;
        case 'custom':
          slug = ilj_statistic_translation.filter_section_custom_links;
          break;
      }
      return slug;
    };

    /**
     * Get the complete type filter node
     * @returns {jQuery}
     */
    var get_type_filter = function () {
      var $wrapper = $('<div id="ilj-type-filter-wrapper" class="ilj-type-filter-wrapper"/>');
      var $container = $('<ul id="ilj-link-statistics-filter" />');
      var $dropdown_link = $('<a/>').addClass('ilj-type-filter-dropdown').text(ilj_statistic_translation.filter_type).on('click', function () {
        $wrapper.toggleClass('show');
      });
      $wrapper.append($dropdown_link, $container);
      $.each(all_types, function (main_type, element) {
        var label = get_main_type_label(main_type);
        var $elem = $('<li/>').html($('<span/>').text(label));
        var $sub_container = $('<ul/>');
        $container.append($elem);
        if (element.length) {
          $elem.append($sub_container);
        }
        $.each(element, function (subindex, sub_type) {
          var $sub_elem_toggle = $('<input/>').attr({
            type: 'checkbox'
          }).on('change', function () {
            if (true === $(this).prop('checked')) {
              selected_types[main_type].add(sub_type);
            } else {
              selected_types[main_type].delete(sub_type);
            }
            jQuery('.ilj-statistic-table-links').dataTable().api().ajax.reload();
          });
          if (selected_types[main_type].has(sub_type)) {
            $sub_elem_toggle.attr('checked', true);
          }
          var $sub_elem_inner = $('<label/>').html($('<span/>').text(sub_type).attr('data-type', main_type));
          var $sub_elem = $('<li/>').addClass('type').html($sub_elem_inner);
          $sub_elem_inner.prepend($sub_elem_toggle);
          $sub_container.append($sub_elem);
        });
      });
      return $wrapper;
    };
    var $tabnav = $('.ilj-statistic').find('.nav-tab-wrapper');
    if ($tabnav.length) {
      $('.ilj-statistic').ilj_tabnav();
    }
    $('.tip').iljtipso(tipsoConfig);
    function create_link_statistics_table() {
      // Create a table element with class "ilj-statistic-table-links display"
      var table = document.createElement('table');
      table.className = 'ilj-statistic-table-links display';

      // Create the table header (thead) with table row (tr) and table headers (th)
      var thead = document.createElement('thead');
      var header_row = document.createElement('tr');

      // Loop through the localized header_titles and create table headers
      header_titles.forEach(titleText => {
        var th = document.createElement('th');
        if (titleText === 'Type') {
          th.className = 'type';
        }
        th.textContent = titleText;
        header_row.appendChild(th);
      });
      thead.appendChild(header_row);

      // Create the table body (tbody)
      var tbody = document.createElement('tbody');

      // Combine the elements to build the final structure
      table.appendChild(thead);
      table.appendChild(tbody);

      // Now you can use this "table" element as needed, for example, appending it to a parent element in the DOM
      var parentElement = document.getElementById('statistic-links'); // Replace with the actual parent element's ID
      // Check if the parent element exists before attempting to append the table
      if (parentElement) {
        parentElement.appendChild(table);
      }
    }
    render_link_statistics_table();

    /**
     * Helper function to get
     * @param {string} element_name The html element name
     * @param {string} content The html content, optional.
     * @param {object} attributes
     * @returns {string}
     */
    function ilj_create_element_with_attributes(element_name, content, attributes) {
      const element = document.createElement(element_name);
      if (content) {
        element.innerText = content;
      }
      Object.entries(attributes).forEach(function ([key, value]) {
        element.setAttribute(key, value);
      });
      const result = element.outerHTML;
      // Remove the element once we get the result.
      element.remove();
      return result;
    }
    function render_link_statistics_table() {
      create_link_statistics_table();
      var $table = jQuery('.ilj-statistic-table-links');
      jQuery('.ilj-statistic-table-links tbody').append(link_statistics_table_data);
      $tabnav.show();
      $('#statistic-links').append($table);
      /**
       * Render the statistics table
       */
      var dataTable = $table.DataTable({
        stateSave: false,
        serverSide: true,
        processing: true,
        searchDelay: 800,
        ajax: {
          beforeSend: function () {
            $('#link-statistics-loader').show();
          },
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'load_link_statistics',
            nonce: ilj_dashboard.nonce,
            main_types: function () {
              return Object.keys(selected_types);
            },
            sub_types: function () {
              var sub_types = Object.values(selected_types).reduce(function (acc, element) {
                return acc.concat(Array.from(element));
              }, []);
              return Array.from(new Set(sub_types));
            }
          }
        },
        columnDefs: [{
          className: 'asset-title',
          responsivePriority: 1,
          targets: 0,
          data: null,
          render: function (data, type, row) {
            // Use a regular expression to replace HTML tags with an empty string
            var cleanTitle = row.title.replace(/<\/?[^>]+(>|$)/g, "");
            return cleanTitle;
          }
        }, {
          targets: 1,
          data: null,
          render: function (data, type, row) {
            return row.keywords_count;
          }
        }, {
          className: 'type',
          responsivePriority: 2,
          data: null,
          targets: 2,
          render: function (data, type, row) {
            var content_type = row.sub_type ? row.sub_type : row.main_type;
            return ilj_create_element_with_attributes('span', content_type, {
              "data-type": row.main_type
            });
          }
        }, {
          responsivePriority: 3,
          data: null,
          targets: 3,
          render: function (data, type, row) {
            if (0 === parseInt(row.incoming_links)) {
              return "-";
            }
            return ilj_create_element_with_attributes('a', row.incoming_links, {
              "data-type": row.main_type,
              title: ilj_statistic_translation.show_incoming_links,
              class: 'tip ilj-statistic-detail',
              "data-id": row.id,
              "data-direction": 'to'
            });
          }
        }, {
          responsivePriority: 4,
          data: null,
          targets: 4,
          render: function (data, type, row) {
            if (0 === parseInt(row.outgoing_links)) {
              return "-";
            }
            return ilj_create_element_with_attributes('a', row.outgoing_links, {
              "data-type": row.main_type,
              title: ilj_statistic_translation.show_outgoing_links,
              class: 'tip ilj-statistic-detail',
              "data-id": row.id,
              "data-direction": 'from'
            });
          }
        }, {
          responsivePriority: 5,
          data: null,
          targets: 5,
          orderable: false,
          render: function (data, type, row) {
            return `<a href="${row.edit_link}" title="${row.edit_title}" class="tip"><span class="dashicons dashicons-edit"></span></a> <a class="tip"  target="_blank" rel="noopener" href="${row.permalink}" title="${row.permalink_title}"><span class="dashicons dashicons-external"></span></a>`;
          }
        }],
        language: dataTables_language,
        stateLoaded: function (settings, data) {
          $table.find('.tip').iljtipso(tipsoConfig);
        },
        drawCallback: function (settings) {
          const existing_filter = document.getElementById('ilj-type-filter-wrapper');
          if (existing_filter) {
            existing_filter.parentNode.removeChild(existing_filter);
          }
          $('#statistic-links .dataTables_wrapper .dataTables_filter').append(get_type_filter());
          $('#link-statistics-loader').hide();
        },
        responsive: true
      });

      // Unbind the default search input event handler
      $('.dataTables_filter input', $table.closest('.dataTables_wrapper')).off();

      // Custom search trigger on Enter key
      $('.dataTables_filter input', $table.closest('.dataTables_wrapper')).on('keyup', function (e) {
        if (e.keyCode === 13) {
          // Enter key
          dataTable.search(this.value).draw();
        }
      });
      $table.find('.tip').iljtipso(tipsoConfig);

      /**
       * Open detailed statistics
       */
      $table.on('click', '.ilj-statistic-detail', function () {
        $('.ilj-statistic-cover').show();
        var id = $(this).data('id');
        var type = $(this).data('type');
        var direction = $(this).data('direction');
        var headline = '';
        var link_count = 0;
        var title = $(this).closest('tr').find('td.asset-title').text();
        if (direction == 'to') {
          headline = ilj_statistic_translation.incoming_links;
          link_count = $(this).closest('a[data-direction="to"]').text();
        } else if (direction == 'from') {
          headline = ilj_statistic_translation.outgoing_links;
          link_count = $(this).closest('a[data-direction="from"]').text();
        }
        var data = {
          'action': 'ilj_render_link_detail_statistic',
          'nonce': ilj_dashboard.nonce,
          'id': id,
          'type': type,
          'direction': direction
        };
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: data
        }).done(function (data) {
          (0,components_ilj_modal__WEBPACK_IMPORTED_MODULE_0__.iljCreateModal)(headline + ' "' + title + '" (' + link_count + ')', $('<div/>').addClass('ilj-statistic').html(data));
          $('.ilj-statistic-cover').hide();
        });
      });
    }
    function create_link_anchor_statistics_table() {
      // Create a table element
      var table = document.createElement('table');
      table.className = 'ilj-statistic-table-anchors display';

      // Create the table header (thead) element
      var thead = document.createElement('thead');
      var headerRow = document.createElement('tr');

      // Create table header cells and add text content from localized data
      if ('undefined' !== typeof headerLabels) {
        headerLabels.forEach(function (labelText) {
          var th = document.createElement('th');
          th.textContent = labelText;
          headerRow.appendChild(th);
        });
      }
      thead.appendChild(headerRow);

      // Create the table body (tbody) element
      var tbody = document.createElement('tbody');

      // Append the header and body to the table
      table.appendChild(thead);
      table.appendChild(tbody);

      // Append the table to a specific element in the DOM
      var targetElement = document.getElementById('statistic-anchors');
      // Check if the parent element exists before attempting to append the table
      if (targetElement) {
        targetElement.appendChild(table);
      }
    }
    function render_link_anchor_statistics_table() {
      create_link_anchor_statistics_table();
      var spinnerUrl = ajaxurl.replace('admin-ajax.php', 'images/spinner.gif');
      $('body').append('<div style="position: fixed;top: 50%;left: 50%;transform: translate(-50%, -50%);display:none" id="loading-icon"><img src="' + spinnerUrl + '" alt="' + ilj_dashboard.loadingText + '"></div>');
      var $loadingIcon = $('#loading-icon');
      var $table = jQuery('.ilj-statistic-table-anchors');
      $('#statistic-anchors').html($table);

      /**
       * Render the anchor statistics table
       */
      var dataTable = $table.DataTable({
        ajax: {
          url: ajaxurl,
          type: 'POST',
          data: function (d) {
            d.action = 'load_anchor_statistics_chunk', d.nonce = ilj_dashboard.nonce;
          }
        },
        processing: false,
        serverSide: true,
        language: dataTables_language,
        stateLoaded: function (settings, data) {
          $table.find('.tip').iljtipso(tipsoConfig);
        },
        columnDefs: [{
          responsivePriority: 1,
          targets: 0
        }, {
          responsivePriority: 2,
          searchable: false,
          targets: 3
        }, {
          responsivePriority: 3,
          searchable: false,
          orderable: false,
          targets: 2
        }, {
          responsivePriority: 4,
          searchable: false,
          orderable: false,
          targets: 1
        }],
        responsive: true,
        searchDelay: 1000
      }).on('processing.dt', function (e, settings, processing) {
        $loadingIcon.toggle(processing);
      });
      $('#statistic-anchors input').off('keypress').on('keypress', function (e) {
        if (e.which == 13) {
          // Enter key
          dataTable.search(this.value).draw();
        }
      });

      /**
       * Open detailed statistics
       */
      $table.on('click', '.ilj-statistic-detail', function () {
        $('.ilj-statistic-cover').show();
        var anchor = $(this).data('anchor');
        var link_count = $(this).text();
        var data = {
          'action': 'ilj_render_anchor_detail_statistic',
          'nonce': ilj_dashboard.nonce,
          'anchor': anchor
        };
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: data
        }).done(function (data) {
          (0,components_ilj_modal__WEBPACK_IMPORTED_MODULE_0__.iljCreateModal)(ilj_statistic_translation.anchor_text + ' "' + anchor + '" (' + link_count + ')', $('<div/>').addClass('ilj-statistic').html(data));
          $('.ilj-statistic-cover').hide();
        });
      });
    }

    //generate the anchor statistics table and render it.
    render_link_anchor_statistics_table();
  });

  /**
   * Hide type filter if open
   */
  $(document).on('mouseup', function (e) {
    var $wrapper = $('#ilj-type-filter-wrapper');
    if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
      $wrapper.removeClass('show');
    }
  });
})(jQuery);
