jQuery(document).ready(function () {
  var WO_Ajax_Filter = function (opts) {
    this.init(opts);
  };

  WO_Ajax_Filter.prototype = {
    selected: function () {

      arr = this.loop(jQuery('.' + this.selected_filters + ':selected, .' + this.selected_filters + ' input:checked'));
      // Join the array with an "&" so we can break it later.
      return arr.join('&');

    },
    loop: function (node) {

      // Return an array of selected navigation classes.
      var arr = [];
      arr.push("search=" + jQuery("#searcher").val());
      node.each(function () {
        arr.push(jQuery(this).data('slug') + '=' + jQuery(this).data('tax'));
      });
      return arr;

    },
    filter: function (arr) {

      var self = this;

      // Return all the relevant posts...
      jQuery.ajax({
        url: wo_js_vars.ajaxurl,
        data: {
          'action': 'wpoad-ajax-search',
          'filters': arr,
          'postsperpage': jQuery("#ajax-filtered-section").attr('data-postsperpage'),
          'paged': wo_js_vars.thisPage,
          '_ajax_nonce': wo_js_vars.nonce
        },
        beforeSend: function () {
          self.section.animate({
            'opacity': .0
          }, 'slow');
          jQuery(".ajax-filter .pagination").hide("slow"); // show pagination 
        },
        success: function (html) {
          self.section.empty();
          self.section.append(html);
        },
        complete: function () {
          jQuery('html, body').animate({
            scrollTop: jQuery(self.section).offset().top - 120
          }, 500);
          self.section.animate({
            'opacity': 1
          }, 'slow');
          jQuery(".pagination").show("slow"); // show pagination 
          self.running = false;
        },
        error: function () {
        }

      });
    },
    clicker: function () {

      var self = this;

      jQuery('body').on('click', this.links, function (e) {

        if (self.running === false) {

          // Set to true to stop function chaining.
          self.running = true;

          // Cache some of the DOM elements for re-use later in the method.
          var link = jQuery(this),
                  parent = link.parent('li');

          if (parent.length > 0) {
            wo_js_vars.thisPage = 1;
          }

          self.filter(self.selected());

        }

        e.preventDefault();

      });

      jQuery('body').on('change', this.select, function (e) {

        if (self.running === false) {

          // Set to true to stop function chaining.
          self.running = true;

          wo_js_vars.thisPage = 1;

          self.filter(self.selected());

        }

        e.preventDefault();

      });

    },
    reset: function () {

      // remove all other ".no-results" 
      jQuery(".no-results").remove();

      jQuery("body.ajax-filter #ajax-filtered-section").append("<p class='no-results'></p>"); // add msg 
      jQuery(".no-results").html(wo_js_vars.on_load_text).fadeIn();
      //jQuery("body.ajax-filter .ajax-loaded").hide(); // hide all results 
      jQuery("body.ajax-filter .pagination").hide(); // hide pagination 

      jQuery('html, body').animate({
        scrollTop: jQuery("#ajax-filtered-section").offset().top - 120
      }, 500);

    },
    init: function (opts) {

      // Set up the properties
      this.opts = opts;
      this.running = false;
      this.section = jQuery(this.opts['section']);
      this.links = this.opts['links'];
      this.selected_filters = this.opts['selected_filters'];

      // Run the methods.
      this.clicker();

    }

  };

  var af_filter = new WO_Ajax_Filter({
    'section': '#ajax-filtered-section',
    'links': '.pagelink, #go',
    'selected_filters': 'filter-selected'
  });

  // toggle placeholder text on search input 
  var placeholder_search = jQuery('#searcher').attr('placeholder');
  jQuery('#searcher').focus(function () {
    jQuery(this).attr('placeholder', '');
  });
  jQuery('#searcher').focusout(function () {
    jQuery(this).attr('placeholder', placeholder_search);
  });

  // reset search 
  jQuery(".reset").click(function (e) {

    // stop default action 
    e.preventDefault();

    // empty search 
    jQuery("input#searcher").val("");

    // reset all forms 
    jQuery("#ajax-filters select").each(function () {
      jQuery(this).find('option:first').attr('selected', 'selected'); // select first option 
      jQuery(this).find("option").show(); // show all options 
      jQuery(this).prop('selectedIndex', 0);
    });

    // back to basics 
    af_filter.reset();

  });

  jQuery("input#searcher").keypress(function (event) {
    if (event.which === 13) {
      event.preventDefault();
      jQuery('#go')[0].click();
    }
  });

});