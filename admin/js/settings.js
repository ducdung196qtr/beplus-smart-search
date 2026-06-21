"use strict";
(() => {
  // admin/js/settings.ts
  (function($) {
    "use strict";
    function togglePriceSettings($wrap) {
      const mode = $wrap.find('input[name*="[sidebar][price][display]"]:checked').val();
      $wrap.find('[data-bpss-price-settings="range"]').prop(
        "hidden",
        mode !== "range"
      );
      $wrap.find('[data-bpss-price-settings="segments"]').prop(
        "hidden",
        mode !== "segments"
      );
    }
    function reindexSegmentRows($wrap) {
      const optionKey = $wrap.find('input[name*="[sidebar][price][segments]"]').first().attr("name")?.match(/^[^\[]+/)?.[0];
      if (!optionKey) {
        return;
      }
      $wrap.find("#bpss-price-segments tbody .bpss-settings__segment-row").each(
        function(index) {
          $(this).find('input[data-name="label"], input[name*="[label]"]').attr(
            "name",
            optionKey + "[sidebar][price][segments][" + index + "][label]"
          );
          $(this).find('input[data-name="min"], input[name*="[min]"]').attr(
            "name",
            optionKey + "[sidebar][price][segments][" + index + "][min]"
          );
          $(this).find('input[data-name="max"], input[name*="[max]"]').attr(
            "name",
            optionKey + "[sidebar][price][segments][" + index + "][max]"
          );
        }
      );
    }
    function reindexCustomTaxRows($wrap) {
      const optionKey = $wrap.find('select[name*="[sidebar][facets][custom_taxonomies]"]').first().attr("name")?.match(/^[^\[]+/)?.[0];
      if (!optionKey) {
        return;
      }
      $wrap.find("#bpss-custom-taxonomies tbody .bpss-settings__custom-tax-row").each(function(index) {
        $(this).find(
          'select[data-name="taxonomy"], select[name*="[taxonomy]"]'
        ).attr(
          "name",
          optionKey + "[sidebar][facets][custom_taxonomies][" + index + "][taxonomy]"
        );
        $(this).find('input[data-name="label"], input[name*="[label]"]').attr(
          "name",
          optionKey + "[sidebar][facets][custom_taxonomies][" + index + "][label]"
        );
        $(this).find('select[data-name="mode"], select[name*="[mode]"]').attr(
          "name",
          optionKey + "[sidebar][facets][custom_taxonomies][" + index + "][mode]"
        );
        $(this).find(
          'input[data-name="show_sub"], input[name*="[show_sub]"]'
        ).attr(
          "name",
          optionKey + "[sidebar][facets][custom_taxonomies][" + index + "][show_sub]"
        );
      });
    }
    $(function() {
      const $wrap = $(".bpss-settings");
      if (!$wrap.length) {
        return;
      }
      $wrap.on("click", ".bpss-settings__tab", function(event) {
        event.preventDefault();
        const tab = $(this).data("tab");
        if (!tab) {
          return;
        }
        $wrap.find(".bpss-settings__tab").removeClass("is-active");
        $(this).addClass("is-active");
        $wrap.find(".bpss-settings__panel").removeClass("is-active");
        $wrap.find('.bpss-settings__panel[data-tab-panel="' + tab + '"]').addClass("is-active");
        $wrap.find('input[name="bpss_active_tab"]').val(tab);
      });
      $wrap.on(
        "change",
        'input[name*="[sidebar][price][display]"]',
        function() {
          togglePriceSettings($wrap);
        }
      );
      $wrap.on("click", ".bpss-add-segment", function() {
        const template = document.getElementById(
          "bpss-segment-row-template"
        );
        const tbody = $wrap.find("#bpss-price-segments tbody")[0];
        if (!template || !tbody) {
          return;
        }
        const clone = template.content.cloneNode(true);
        tbody.appendChild(clone);
        reindexSegmentRows($wrap);
      });
      $wrap.on("click", ".bpss-remove-segment", function() {
        $(this).closest(".bpss-settings__segment-row").remove();
        reindexSegmentRows($wrap);
      });
      $wrap.on("click", ".bpss-add-custom-tax", function() {
        const template = document.getElementById(
          "bpss-custom-tax-row-template"
        );
        const tbody = $wrap.find("#bpss-custom-taxonomies tbody")[0];
        if (!template || !tbody) {
          return;
        }
        const clone = template.content.cloneNode(true);
        tbody.appendChild(clone);
        reindexCustomTaxRows($wrap);
      });
      $wrap.on("click", ".bpss-remove-custom-tax", function() {
        $(this).closest(".bpss-settings__custom-tax-row").remove();
        reindexCustomTaxRows($wrap);
      });
      togglePriceSettings($wrap);
      if ($.fn.wpColorPicker) {
        $(".bpss-color-picker").wpColorPicker();
      }
    });
  })(jQuery);
})();
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsic2V0dGluZ3MudHMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbIi8qKlxuICogU21hcnQgU2VhcmNoIGFkbWluIHNldHRpbmdzIHRhYnMuXG4gKlxuICogQHBhY2thZ2UgQmVQbHVzU21hcnRTZWFyY2hcbiAqL1xuLy8gQHRzLW5vY2hlY2tcbiggZnVuY3Rpb24gKCAkICkge1xuXHQndXNlIHN0cmljdCc7XG5cblx0ZnVuY3Rpb24gdG9nZ2xlUHJpY2VTZXR0aW5ncyggJHdyYXAgKSB7XG5cdFx0Y29uc3QgbW9kZSA9ICR3cmFwXG5cdFx0XHQuZmluZCggJ2lucHV0W25hbWUqPVwiW3NpZGViYXJdW3ByaWNlXVtkaXNwbGF5XVwiXTpjaGVja2VkJyApXG5cdFx0XHQudmFsKCk7XG5cdFx0JHdyYXAuZmluZCggJ1tkYXRhLWJwc3MtcHJpY2Utc2V0dGluZ3M9XCJyYW5nZVwiXScgKS5wcm9wKFxuXHRcdFx0J2hpZGRlbicsXG5cdFx0XHRtb2RlICE9PSAncmFuZ2UnXG5cdFx0KTtcblx0XHQkd3JhcC5maW5kKCAnW2RhdGEtYnBzcy1wcmljZS1zZXR0aW5ncz1cInNlZ21lbnRzXCJdJyApLnByb3AoXG5cdFx0XHQnaGlkZGVuJyxcblx0XHRcdG1vZGUgIT09ICdzZWdtZW50cydcblx0XHQpO1xuXHR9XG5cblx0ZnVuY3Rpb24gcmVpbmRleFNlZ21lbnRSb3dzKCAkd3JhcCApIHtcblx0XHRjb25zdCBvcHRpb25LZXkgPSAkd3JhcFxuXHRcdFx0LmZpbmQoICdpbnB1dFtuYW1lKj1cIltzaWRlYmFyXVtwcmljZV1bc2VnbWVudHNdXCJdJyApXG5cdFx0XHQuZmlyc3QoKVxuXHRcdFx0LmF0dHIoICduYW1lJyApXG5cdFx0XHQ/Lm1hdGNoKCAvXlteXFxbXSsvKT8uWyAwIF07XG5cblx0XHRpZiAoICEgb3B0aW9uS2V5ICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdCR3cmFwLmZpbmQoICcjYnBzcy1wcmljZS1zZWdtZW50cyB0Ym9keSAuYnBzcy1zZXR0aW5nc19fc2VnbWVudC1yb3cnICkuZWFjaChcblx0XHRcdGZ1bmN0aW9uICggaW5kZXggKSB7XG5cdFx0XHRcdCQoIHRoaXMgKVxuXHRcdFx0XHRcdC5maW5kKCAnaW5wdXRbZGF0YS1uYW1lPVwibGFiZWxcIl0sIGlucHV0W25hbWUqPVwiW2xhYmVsXVwiXScgKVxuXHRcdFx0XHRcdC5hdHRyKFxuXHRcdFx0XHRcdFx0J25hbWUnLFxuXHRcdFx0XHRcdFx0b3B0aW9uS2V5ICtcblx0XHRcdFx0XHRcdFx0J1tzaWRlYmFyXVtwcmljZV1bc2VnbWVudHNdWycgK1xuXHRcdFx0XHRcdFx0XHRpbmRleCArXG5cdFx0XHRcdFx0XHRcdCddW2xhYmVsXSdcblx0XHRcdFx0XHQpO1xuXHRcdFx0XHQkKCB0aGlzIClcblx0XHRcdFx0XHQuZmluZCggJ2lucHV0W2RhdGEtbmFtZT1cIm1pblwiXSwgaW5wdXRbbmFtZSo9XCJbbWluXVwiXScgKVxuXHRcdFx0XHRcdC5hdHRyKFxuXHRcdFx0XHRcdFx0J25hbWUnLFxuXHRcdFx0XHRcdFx0b3B0aW9uS2V5ICtcblx0XHRcdFx0XHRcdFx0J1tzaWRlYmFyXVtwcmljZV1bc2VnbWVudHNdWycgK1xuXHRcdFx0XHRcdFx0XHRpbmRleCArXG5cdFx0XHRcdFx0XHRcdCddW21pbl0nXG5cdFx0XHRcdFx0KTtcblx0XHRcdFx0JCggdGhpcyApXG5cdFx0XHRcdFx0LmZpbmQoICdpbnB1dFtkYXRhLW5hbWU9XCJtYXhcIl0sIGlucHV0W25hbWUqPVwiW21heF1cIl0nIClcblx0XHRcdFx0XHQuYXR0cihcblx0XHRcdFx0XHRcdCduYW1lJyxcblx0XHRcdFx0XHRcdG9wdGlvbktleSArXG5cdFx0XHRcdFx0XHRcdCdbc2lkZWJhcl1bcHJpY2VdW3NlZ21lbnRzXVsnICtcblx0XHRcdFx0XHRcdFx0aW5kZXggK1xuXHRcdFx0XHRcdFx0XHQnXVttYXhdJ1xuXHRcdFx0XHRcdCk7XG5cdFx0XHR9XG5cdFx0KTtcblx0fVxuXG5cdGZ1bmN0aW9uIHJlaW5kZXhDdXN0b21UYXhSb3dzKCAkd3JhcCApIHtcblx0XHRjb25zdCBvcHRpb25LZXkgPSAkd3JhcFxuXHRcdFx0LmZpbmQoICdzZWxlY3RbbmFtZSo9XCJbc2lkZWJhcl1bZmFjZXRzXVtjdXN0b21fdGF4b25vbWllc11cIl0nIClcblx0XHRcdC5maXJzdCgpXG5cdFx0XHQuYXR0ciggJ25hbWUnIClcblx0XHRcdD8ubWF0Y2goIC9eW15cXFtdKy8pPy5bIDAgXTtcblxuXHRcdGlmICggISBvcHRpb25LZXkgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0JHdyYXBcblx0XHRcdC5maW5kKCAnI2Jwc3MtY3VzdG9tLXRheG9ub21pZXMgdGJvZHkgLmJwc3Mtc2V0dGluZ3NfX2N1c3RvbS10YXgtcm93JyApXG5cdFx0XHQuZWFjaCggZnVuY3Rpb24gKCBpbmRleCApIHtcblx0XHRcdFx0JCggdGhpcyApXG5cdFx0XHRcdFx0LmZpbmQoXG5cdFx0XHRcdFx0XHQnc2VsZWN0W2RhdGEtbmFtZT1cInRheG9ub215XCJdLCBzZWxlY3RbbmFtZSo9XCJbdGF4b25vbXldXCJdJ1xuXHRcdFx0XHRcdClcblx0XHRcdFx0XHQuYXR0cihcblx0XHRcdFx0XHRcdCduYW1lJyxcblx0XHRcdFx0XHRcdG9wdGlvbktleSArXG5cdFx0XHRcdFx0XHRcdCdbc2lkZWJhcl1bZmFjZXRzXVtjdXN0b21fdGF4b25vbWllc11bJyArXG5cdFx0XHRcdFx0XHRcdGluZGV4ICtcblx0XHRcdFx0XHRcdFx0J11bdGF4b25vbXldJ1xuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdCQoIHRoaXMgKVxuXHRcdFx0XHRcdC5maW5kKCAnaW5wdXRbZGF0YS1uYW1lPVwibGFiZWxcIl0sIGlucHV0W25hbWUqPVwiW2xhYmVsXVwiXScgKVxuXHRcdFx0XHRcdC5hdHRyKFxuXHRcdFx0XHRcdFx0J25hbWUnLFxuXHRcdFx0XHRcdFx0b3B0aW9uS2V5ICtcblx0XHRcdFx0XHRcdFx0J1tzaWRlYmFyXVtmYWNldHNdW2N1c3RvbV90YXhvbm9taWVzXVsnICtcblx0XHRcdFx0XHRcdFx0aW5kZXggK1xuXHRcdFx0XHRcdFx0XHQnXVtsYWJlbF0nXG5cdFx0XHRcdFx0KTtcblx0XHRcdFx0JCggdGhpcyApXG5cdFx0XHRcdFx0LmZpbmQoICdzZWxlY3RbZGF0YS1uYW1lPVwibW9kZVwiXSwgc2VsZWN0W25hbWUqPVwiW21vZGVdXCJdJyApXG5cdFx0XHRcdFx0LmF0dHIoXG5cdFx0XHRcdFx0XHQnbmFtZScsXG5cdFx0XHRcdFx0XHRvcHRpb25LZXkgK1xuXHRcdFx0XHRcdFx0XHQnW3NpZGViYXJdW2ZhY2V0c11bY3VzdG9tX3RheG9ub21pZXNdWycgK1xuXHRcdFx0XHRcdFx0XHRpbmRleCArXG5cdFx0XHRcdFx0XHRcdCddW21vZGVdJ1xuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdCQoIHRoaXMgKVxuXHRcdFx0XHRcdC5maW5kKFxuXHRcdFx0XHRcdFx0J2lucHV0W2RhdGEtbmFtZT1cInNob3dfc3ViXCJdLCBpbnB1dFtuYW1lKj1cIltzaG93X3N1Yl1cIl0nXG5cdFx0XHRcdFx0KVxuXHRcdFx0XHRcdC5hdHRyKFxuXHRcdFx0XHRcdFx0J25hbWUnLFxuXHRcdFx0XHRcdFx0b3B0aW9uS2V5ICtcblx0XHRcdFx0XHRcdFx0J1tzaWRlYmFyXVtmYWNldHNdW2N1c3RvbV90YXhvbm9taWVzXVsnICtcblx0XHRcdFx0XHRcdFx0aW5kZXggK1xuXHRcdFx0XHRcdFx0XHQnXVtzaG93X3N1Yl0nXG5cdFx0XHRcdFx0KTtcblx0XHRcdH0gKTtcblx0fVxuXG5cdCQoIGZ1bmN0aW9uICgpIHtcblx0XHRjb25zdCAkd3JhcCA9ICQoICcuYnBzcy1zZXR0aW5ncycgKTtcblx0XHRpZiAoICEgJHdyYXAubGVuZ3RoICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdCR3cmFwLm9uKCAnY2xpY2snLCAnLmJwc3Mtc2V0dGluZ3NfX3RhYicsIGZ1bmN0aW9uICggZXZlbnQgKSB7XG5cdFx0XHRldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0Y29uc3QgdGFiID0gJCggdGhpcyApLmRhdGEoICd0YWInICk7XG5cdFx0XHRpZiAoICEgdGFiICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdCR3cmFwLmZpbmQoICcuYnBzcy1zZXR0aW5nc19fdGFiJyApLnJlbW92ZUNsYXNzKCAnaXMtYWN0aXZlJyApO1xuXHRcdFx0JCggdGhpcyApLmFkZENsYXNzKCAnaXMtYWN0aXZlJyApO1xuXHRcdFx0JHdyYXAuZmluZCggJy5icHNzLXNldHRpbmdzX19wYW5lbCcgKS5yZW1vdmVDbGFzcyggJ2lzLWFjdGl2ZScgKTtcblx0XHRcdCR3cmFwXG5cdFx0XHRcdC5maW5kKCAnLmJwc3Mtc2V0dGluZ3NfX3BhbmVsW2RhdGEtdGFiLXBhbmVsPVwiJyArIHRhYiArICdcIl0nIClcblx0XHRcdFx0LmFkZENsYXNzKCAnaXMtYWN0aXZlJyApO1xuXHRcdFx0JHdyYXAuZmluZCggJ2lucHV0W25hbWU9XCJicHNzX2FjdGl2ZV90YWJcIl0nICkudmFsKCB0YWIgKTtcblx0XHR9ICk7XG5cblx0XHQkd3JhcC5vbihcblx0XHRcdCdjaGFuZ2UnLFxuXHRcdFx0J2lucHV0W25hbWUqPVwiW3NpZGViYXJdW3ByaWNlXVtkaXNwbGF5XVwiXScsXG5cdFx0XHRmdW5jdGlvbiAoKSB7XG5cdFx0XHRcdHRvZ2dsZVByaWNlU2V0dGluZ3MoICR3cmFwICk7XG5cdFx0XHR9XG5cdFx0KTtcblxuXHRcdCR3cmFwLm9uKCAnY2xpY2snLCAnLmJwc3MtYWRkLXNlZ21lbnQnLCBmdW5jdGlvbiAoKSB7XG5cdFx0XHRjb25zdCB0ZW1wbGF0ZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFxuXHRcdFx0XHQnYnBzcy1zZWdtZW50LXJvdy10ZW1wbGF0ZSdcblx0XHRcdCk7XG5cdFx0XHRjb25zdCB0Ym9keSA9ICR3cmFwLmZpbmQoICcjYnBzcy1wcmljZS1zZWdtZW50cyB0Ym9keScgKVsgMCBdO1xuXG5cdFx0XHRpZiAoICEgdGVtcGxhdGUgfHwgISB0Ym9keSApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCBjbG9uZSA9IHRlbXBsYXRlLmNvbnRlbnQuY2xvbmVOb2RlKCB0cnVlICk7XG5cdFx0XHR0Ym9keS5hcHBlbmRDaGlsZCggY2xvbmUgKTtcblx0XHRcdHJlaW5kZXhTZWdtZW50Um93cyggJHdyYXAgKTtcblx0XHR9ICk7XG5cblx0XHQkd3JhcC5vbiggJ2NsaWNrJywgJy5icHNzLXJlbW92ZS1zZWdtZW50JywgZnVuY3Rpb24gKCkge1xuXHRcdFx0JCggdGhpcyApLmNsb3Nlc3QoICcuYnBzcy1zZXR0aW5nc19fc2VnbWVudC1yb3cnICkucmVtb3ZlKCk7XG5cdFx0XHRyZWluZGV4U2VnbWVudFJvd3MoICR3cmFwICk7XG5cdFx0fSApO1xuXG5cdFx0JHdyYXAub24oICdjbGljaycsICcuYnBzcy1hZGQtY3VzdG9tLXRheCcsIGZ1bmN0aW9uICgpIHtcblx0XHRcdGNvbnN0IHRlbXBsYXRlID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoXG5cdFx0XHRcdCdicHNzLWN1c3RvbS10YXgtcm93LXRlbXBsYXRlJ1xuXHRcdFx0KTtcblx0XHRcdGNvbnN0IHRib2R5ID0gJHdyYXAuZmluZCggJyNicHNzLWN1c3RvbS10YXhvbm9taWVzIHRib2R5JyApWyAwIF07XG5cblx0XHRcdGlmICggISB0ZW1wbGF0ZSB8fCAhIHRib2R5ICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0IGNsb25lID0gdGVtcGxhdGUuY29udGVudC5jbG9uZU5vZGUoIHRydWUgKTtcblx0XHRcdHRib2R5LmFwcGVuZENoaWxkKCBjbG9uZSApO1xuXHRcdFx0cmVpbmRleEN1c3RvbVRheFJvd3MoICR3cmFwICk7XG5cdFx0fSApO1xuXG5cdFx0JHdyYXAub24oICdjbGljaycsICcuYnBzcy1yZW1vdmUtY3VzdG9tLXRheCcsIGZ1bmN0aW9uICgpIHtcblx0XHRcdCQoIHRoaXMgKS5jbG9zZXN0KCAnLmJwc3Mtc2V0dGluZ3NfX2N1c3RvbS10YXgtcm93JyApLnJlbW92ZSgpO1xuXHRcdFx0cmVpbmRleEN1c3RvbVRheFJvd3MoICR3cmFwICk7XG5cdFx0fSApO1xuXG5cdFx0dG9nZ2xlUHJpY2VTZXR0aW5ncyggJHdyYXAgKTtcblxuXHRcdGlmICggJC5mbi53cENvbG9yUGlja2VyICkge1xuXHRcdFx0JCggJy5icHNzLWNvbG9yLXBpY2tlcicgKS53cENvbG9yUGlja2VyKCk7XG5cdFx0fVxuXHR9ICk7XG59ICkoIGpRdWVyeSApO1xuIl0sCiAgIm1hcHBpbmdzIjogIjs7O0FBTUEsR0FBRSxTQUFXLEdBQUk7QUFDaEI7QUFFQSxhQUFTLG9CQUFxQixPQUFRO0FBQ3JDLFlBQU0sT0FBTyxNQUNYLEtBQU0sa0RBQW1ELEVBQ3pELElBQUk7QUFDTixZQUFNLEtBQU0sb0NBQXFDLEVBQUU7QUFBQSxRQUNsRDtBQUFBLFFBQ0EsU0FBUztBQUFBLE1BQ1Y7QUFDQSxZQUFNLEtBQU0sdUNBQXdDLEVBQUU7QUFBQSxRQUNyRDtBQUFBLFFBQ0EsU0FBUztBQUFBLE1BQ1Y7QUFBQSxJQUNEO0FBRUEsYUFBUyxtQkFBb0IsT0FBUTtBQUNwQyxZQUFNLFlBQVksTUFDaEIsS0FBTSwyQ0FBNEMsRUFDbEQsTUFBTSxFQUNOLEtBQU0sTUFBTyxHQUNaLE1BQU8sU0FBUyxJQUFLLENBQUU7QUFFMUIsVUFBSyxDQUFFLFdBQVk7QUFDbEI7QUFBQSxNQUNEO0FBRUEsWUFBTSxLQUFNLHdEQUF5RCxFQUFFO0FBQUEsUUFDdEUsU0FBVyxPQUFRO0FBQ2xCLFlBQUcsSUFBSyxFQUNOLEtBQU0sa0RBQW1ELEVBQ3pEO0FBQUEsWUFDQTtBQUFBLFlBQ0EsWUFDQyxnQ0FDQSxRQUNBO0FBQUEsVUFDRjtBQUNELFlBQUcsSUFBSyxFQUNOLEtBQU0sOENBQStDLEVBQ3JEO0FBQUEsWUFDQTtBQUFBLFlBQ0EsWUFDQyxnQ0FDQSxRQUNBO0FBQUEsVUFDRjtBQUNELFlBQUcsSUFBSyxFQUNOLEtBQU0sOENBQStDLEVBQ3JEO0FBQUEsWUFDQTtBQUFBLFlBQ0EsWUFDQyxnQ0FDQSxRQUNBO0FBQUEsVUFDRjtBQUFBLFFBQ0Y7QUFBQSxNQUNEO0FBQUEsSUFDRDtBQUVBLGFBQVMscUJBQXNCLE9BQVE7QUFDdEMsWUFBTSxZQUFZLE1BQ2hCLEtBQU0sc0RBQXVELEVBQzdELE1BQU0sRUFDTixLQUFNLE1BQU8sR0FDWixNQUFPLFNBQVMsSUFBSyxDQUFFO0FBRTFCLFVBQUssQ0FBRSxXQUFZO0FBQ2xCO0FBQUEsTUFDRDtBQUVBLFlBQ0UsS0FBTSw4REFBK0QsRUFDckUsS0FBTSxTQUFXLE9BQVE7QUFDekIsVUFBRyxJQUFLLEVBQ047QUFBQSxVQUNBO0FBQUEsUUFDRCxFQUNDO0FBQUEsVUFDQTtBQUFBLFVBQ0EsWUFDQywwQ0FDQSxRQUNBO0FBQUEsUUFDRjtBQUNELFVBQUcsSUFBSyxFQUNOLEtBQU0sa0RBQW1ELEVBQ3pEO0FBQUEsVUFDQTtBQUFBLFVBQ0EsWUFDQywwQ0FDQSxRQUNBO0FBQUEsUUFDRjtBQUNELFVBQUcsSUFBSyxFQUNOLEtBQU0sa0RBQW1ELEVBQ3pEO0FBQUEsVUFDQTtBQUFBLFVBQ0EsWUFDQywwQ0FDQSxRQUNBO0FBQUEsUUFDRjtBQUNELFVBQUcsSUFBSyxFQUNOO0FBQUEsVUFDQTtBQUFBLFFBQ0QsRUFDQztBQUFBLFVBQ0E7QUFBQSxVQUNBLFlBQ0MsMENBQ0EsUUFDQTtBQUFBLFFBQ0Y7QUFBQSxNQUNGLENBQUU7QUFBQSxJQUNKO0FBRUEsTUFBRyxXQUFZO0FBQ2QsWUFBTSxRQUFRLEVBQUcsZ0JBQWlCO0FBQ2xDLFVBQUssQ0FBRSxNQUFNLFFBQVM7QUFDckI7QUFBQSxNQUNEO0FBRUEsWUFBTSxHQUFJLFNBQVMsdUJBQXVCLFNBQVcsT0FBUTtBQUM1RCxjQUFNLGVBQWU7QUFDckIsY0FBTSxNQUFNLEVBQUcsSUFBSyxFQUFFLEtBQU0sS0FBTTtBQUNsQyxZQUFLLENBQUUsS0FBTTtBQUNaO0FBQUEsUUFDRDtBQUVBLGNBQU0sS0FBTSxxQkFBc0IsRUFBRSxZQUFhLFdBQVk7QUFDN0QsVUFBRyxJQUFLLEVBQUUsU0FBVSxXQUFZO0FBQ2hDLGNBQU0sS0FBTSx1QkFBd0IsRUFBRSxZQUFhLFdBQVk7QUFDL0QsY0FDRSxLQUFNLDJDQUEyQyxNQUFNLElBQUssRUFDNUQsU0FBVSxXQUFZO0FBQ3hCLGNBQU0sS0FBTSwrQkFBZ0MsRUFBRSxJQUFLLEdBQUk7QUFBQSxNQUN4RCxDQUFFO0FBRUYsWUFBTTtBQUFBLFFBQ0w7QUFBQSxRQUNBO0FBQUEsUUFDQSxXQUFZO0FBQ1gsOEJBQXFCLEtBQU07QUFBQSxRQUM1QjtBQUFBLE1BQ0Q7QUFFQSxZQUFNLEdBQUksU0FBUyxxQkFBcUIsV0FBWTtBQUNuRCxjQUFNLFdBQVcsU0FBUztBQUFBLFVBQ3pCO0FBQUEsUUFDRDtBQUNBLGNBQU0sUUFBUSxNQUFNLEtBQU0sNEJBQTZCLEVBQUcsQ0FBRTtBQUU1RCxZQUFLLENBQUUsWUFBWSxDQUFFLE9BQVE7QUFDNUI7QUFBQSxRQUNEO0FBRUEsY0FBTSxRQUFRLFNBQVMsUUFBUSxVQUFXLElBQUs7QUFDL0MsY0FBTSxZQUFhLEtBQU07QUFDekIsMkJBQW9CLEtBQU07QUFBQSxNQUMzQixDQUFFO0FBRUYsWUFBTSxHQUFJLFNBQVMsd0JBQXdCLFdBQVk7QUFDdEQsVUFBRyxJQUFLLEVBQUUsUUFBUyw2QkFBOEIsRUFBRSxPQUFPO0FBQzFELDJCQUFvQixLQUFNO0FBQUEsTUFDM0IsQ0FBRTtBQUVGLFlBQU0sR0FBSSxTQUFTLHdCQUF3QixXQUFZO0FBQ3RELGNBQU0sV0FBVyxTQUFTO0FBQUEsVUFDekI7QUFBQSxRQUNEO0FBQ0EsY0FBTSxRQUFRLE1BQU0sS0FBTSwrQkFBZ0MsRUFBRyxDQUFFO0FBRS9ELFlBQUssQ0FBRSxZQUFZLENBQUUsT0FBUTtBQUM1QjtBQUFBLFFBQ0Q7QUFFQSxjQUFNLFFBQVEsU0FBUyxRQUFRLFVBQVcsSUFBSztBQUMvQyxjQUFNLFlBQWEsS0FBTTtBQUN6Qiw2QkFBc0IsS0FBTTtBQUFBLE1BQzdCLENBQUU7QUFFRixZQUFNLEdBQUksU0FBUywyQkFBMkIsV0FBWTtBQUN6RCxVQUFHLElBQUssRUFBRSxRQUFTLGdDQUFpQyxFQUFFLE9BQU87QUFDN0QsNkJBQXNCLEtBQU07QUFBQSxNQUM3QixDQUFFO0FBRUYsMEJBQXFCLEtBQU07QUFFM0IsVUFBSyxFQUFFLEdBQUcsZUFBZ0I7QUFDekIsVUFBRyxvQkFBcUIsRUFBRSxjQUFjO0FBQUEsTUFDekM7QUFBQSxJQUNELENBQUU7QUFBQSxFQUNILEdBQUssTUFBTzsiLAogICJuYW1lcyI6IFtdCn0K
