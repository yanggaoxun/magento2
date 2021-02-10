/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
     'mage/apply/main'
], function ($,mage) {
    'use strict';
    $.widget('mage.ajaxnavigation', {
        options: {
        }, 
        _create: function () {
            this.url = $(location).attr('href'); 
            this.useAjax = this.options.useAjax;
            this.initNavigation();
        },
        initNavigation: function() { 
  
            var self = this;
            this.addFilterObservers();
            setTimeout(function(){ 
                self.addToolbarObservers();
            },300);
        },
        addFilterObservers: function() {
            var selectedIds, checkbox, filterItem,
                self = this;

            $("#layered-filter-block .filter-title strong").off();

            $(".mgs-layered-checkbox").on('change',function(){
                self.applyFilter($(this).parent().next());
                return false;
            });

            $(".mgs-ajax-layer-item" ).off();
            $(".mgs-ajax-layer-item").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
				
                var checkboxWrapper = $(this).prev(),
                    checkbox = checkboxWrapper.find('input');

                self.toggleCheckbox(checkbox);
                self.applyFilter($(this));
                return false;
            });
			
			$(".range-slider-action" ).off();
            $(".range-slider-action").on("click", function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (typeof $(this).attr('href') !== typeof undefined && $(this).attr('href') !== false) {
					self.applyFilter($(this));
				}
				return false;
            });

            $(".filter-active-item-link").off();
            $(".state-item-remove").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.applyFilter($(this).next());
                return false;
            });


            $( ".swatch-attribute-options a" ).off();
            $(".swatch-attribute-options a").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.applyFilter($(this));
                return false;
            });

            $(".filter-active-item-clear-all").off();
            $(".filter-active-item-clear-all").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.applyFilter($(this));
                return false;
            });
            // show hide filter
            $( ".filter-content dt" ).off();
            $(".filter-content dt").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleFilter($(this));
                return false;
            });
        },
        toggleCheckbox: function(el) {
            if (el.prop('checked')) {
                el.prop("checked", false);
            } else {
                el.addClass('loading');
                el.prop("checked", true);
            }
        },

        addToolbarObservers: function() {
            var self = this;
            $("#mode-list").off();
            $("#mode-list").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation(); 
                self.applyToolbarElement('product_list_mode', 'list');
                return false;
            });

            $("#mode-grid").off();
            $("#mode-grid").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation(); 
                self.applyToolbarElement('product_list_mode', 'grid');
                return false;
            });

            $("#sorter").off();
            $("#sorter").on("change", function(e) {
                e.preventDefault();
                e.stopPropagation(); 
                self.applyToolbarElement('product_list_order', $(this).val());
                return false;
            });

            $(".sorter-action").off();
            $(".sorter-action").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation(); 
                self.applyToolbarElement('product_list_dir', $(this).attr("data-value"));
                return false;
            });


            $(".limiter-options").off();
            $(".limiter-options").on("change", function(e) {
                e.preventDefault();
                e.stopPropagation();

                self.applyToolbarElement('product_list_limit', $(this).val());
                return false;
            });

            $(".pages-items a").off();
            $(".pages-items a").on("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.applyFilter($(this));
                $('html, body').animate({
                    scrollTop: $("#maincontent").offset().top
                }, 500);
                return false;
            });
        },

        applyToolbarElement: function(param, value) {
            var self = this,
                urlParams = self.urlParams(self.url),
                url = self.url.split("?")[0];

            urlParams[param] = value;
            self.ajax_init(url + '?' + $.param(urlParams));
        },

        applyFilter: function(el) {
            this.ajax_init($(el).attr('href'));
        },

        ajax_init: function(url) {
            var self = this;
            if (!this.useAjax) {
                window.location = decodeURIComponent(url);
                return false;
            }
            window.history.pushState("", "", decodeURIComponent(url));
            $.ajax({
                method: "GET",
                url: decodeURIComponent(url),
                dataType: "json",
                data: { is_ajax: 1 },
                showLoader: true
            }) .done(function(data) {
                if (data.list) {
                    $(".main .toolbar-products").remove();
                    $(".main .products").remove();
                    $(".main .filter-active").remove();
					if($('.ajaxscroll-enable').length){
						$('.ajaxscroll-enable').remove();
					}
					$(".main .message").remove();
                    $("#filter-container").first().before(data.state);
                    $(".category-product-actions").first().after(data.list);
                }
                if (data.filters) {
                    $("#layered-filter-block").remove();
					if($('.sidebar-main').length){
						$(".sidebar-main").prepend(data.filters);
					}else{
						$(".column.main").prepend(data.filters);
					}
                    
                }
				if($('.category-product-actions').length > 1){
					$('.category-product-actions').first().remove();
				}
                self.url = url;
                self.initNavigation();
                $(mage.apply);
				self.reInitFunction();
				$('body').removeClass('ajax-loading');
				$('.loading-mask').hide();
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.log(errorThrown);
            });
        },

        urlParams: function(url) {
            var result = {};
            var searchIndex = url.indexOf("?");
            if (searchIndex == -1 ) return result;
            var sPageURL = url.substring(searchIndex +1);
            var sURLVariables = sPageURL.split('&');
            for (var i = 0; i < sURLVariables.length; i++) {
                var sParameterName = sURLVariables[i].split('=');
                result[sParameterName[0]] = sParameterName[1];
            }

            return result;
        },

        toggleFilter: function(el) {
            el.toggleClass('inactive');
            el.toggleClass('active');
            el.next().slideToggle(); 
        },

        isMobile: function() {
            if( navigator.userAgent.match(/Android/i)
                 || navigator.userAgent.match(/webOS/i)
                 || navigator.userAgent.match(/iPhone/i)
                 || navigator.userAgent.match(/iPad/i)
                 || navigator.userAgent.match(/iPod/i)
                 || navigator.userAgent.match(/BlackBerry/i)
                 || navigator.userAgent.match(/Windows Phone/i)
                 ){
                return true;
            }else {
                return false;
            }
        },
		reInitFunction: function(){
			var formKey = $("input[name*='form_key']").first().val();
			$("input[name*='form_key']").val(formKey);
			$(".mgs-quickview").bind("click", function() {
				var b = $(this).attr("data-quickview-url");
				b.length && reInitQuickview($, b)
			});
			
			$("img.lazy").unveil(25, function(){
				var self = $(this);
				setTimeout(function(){
					self.removeClass('lazy');
				}, 0);
			});
			
			return;
			
		}
    });

    return $.mage.ajaxnavigation;
});
