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
      function toggleCachePanel($wrap2) {
        const enabled = $wrap2.find("[data-bpss-cache-toggle]").is(":checked");
        $wrap2.find("[data-bpss-cache-panel]").prop("hidden", !enabled);
        $wrap2.find("[data-bpss-cache-off-note]").prop("hidden", enabled);
        $wrap2.find("[data-bpss-cache-state-label]").text(
          enabled ? window.bpssAdmin?.i18n?.on || "On" : window.bpssAdmin?.i18n?.off || "Off"
        );
      }
      function formatLastCleared(timestamp) {
        if (!timestamp || !window.bpssAdmin) {
          return window.bpssAdmin?.i18n?.neverCleared || "";
        }
        const date = new Date(timestamp * 1e3);
        const formatted = date.toLocaleString();
        return (window.bpssAdmin.i18n.lastCleared || "Last cleared:") + " " + formatted;
      }
      function renderBenchmark($wrap2, labels, measuredAt) {
        const $body = $wrap2.find("[data-bpss-benchmark-body]");
        $body.find("[data-bpss-benchmark-empty]").remove();
        let $grid = $body.find(".bpss-cache__benchmark-grid");
        if (!$grid.length) {
          $grid = $(`
					<div class="bpss-cache__benchmark-grid">
						<div class="bpss-cache__benchmark-stat">
							<span class="bpss-cache__benchmark-label"></span>
							<strong class="bpss-cache__benchmark-value" data-bpss-benchmark-cold></strong>
						</div>
						<div class="bpss-cache__benchmark-stat is-highlight">
							<span class="bpss-cache__benchmark-label"></span>
							<strong class="bpss-cache__benchmark-value" data-bpss-benchmark-warm></strong>
						</div>
						<div class="bpss-cache__benchmark-stat">
							<span class="bpss-cache__benchmark-label"></span>
							<strong class="bpss-cache__benchmark-value" data-bpss-benchmark-saved></strong>
						</div>
					</div>
					<p class="description bpss-cache__benchmark-meta" data-bpss-benchmark-meta></p>
				`);
          $body.append($grid);
        }
        $grid.find(".bpss-cache__benchmark-stat").eq(0).find(".bpss-cache__benchmark-label").text(window.bpssAdmin?.i18n?.coldLabel || "Without cache");
        $grid.find(".bpss-cache__benchmark-stat").eq(1).find(".bpss-cache__benchmark-label").text(window.bpssAdmin?.i18n?.warmLabel || "With cache");
        $grid.find(".bpss-cache__benchmark-stat").eq(2).find(".bpss-cache__benchmark-label").text(window.bpssAdmin?.i18n?.savedLabel || "Estimated saving");
        $body.find("[data-bpss-benchmark-cold]").text(labels.cold);
        $body.find("[data-bpss-benchmark-warm]").text(labels.warm);
        $body.find("[data-bpss-benchmark-saved]").text(labels.saved + " (" + labels.percent + "% faster)");
        if (measuredAt) {
          const formatted = new Date(measuredAt * 1e3).toLocaleString();
          $body.find("[data-bpss-benchmark-meta]").text(
            (window.bpssAdmin?.i18n?.measuredAt || "Measured:") + " " + formatted
          );
        }
      }
      $wrap.on("change", "[data-bpss-cache-toggle]", function() {
        toggleCachePanel($wrap);
      });
      $wrap.on("click", "[data-bpss-clear-cache]", function() {
        const $button = $(this);
        const $notice = $wrap.find("#bpss-cache-notice");
        const $status = $wrap.find("[data-bpss-cache-status]");
        if (!window.bpssAdmin?.ajaxUrl || !window.bpssAdmin?.nonce) {
          return;
        }
        $button.prop("disabled", true);
        $notice.prop("hidden", true).removeClass("is-success is-error");
        $.post(window.bpssAdmin.ajaxUrl, {
          action: "bpss_clear_cache",
          nonce: window.bpssAdmin.nonce
        }).done(function(response) {
          if (!response?.success) {
            throw new Error("clear_failed");
          }
          const clearedAt = response.data?.clearedAt || 0;
          $status.text(formatLastCleared(clearedAt));
          $notice.text(
            response.data?.message || window.bpssAdmin.i18n.cleared
          ).addClass("is-success").prop("hidden", false);
        }).fail(function() {
          $notice.text(window.bpssAdmin.i18n.clearError).addClass("is-error").prop("hidden", false);
        }).always(function() {
          $button.prop("disabled", false);
        });
      });
      $wrap.on("click", "[data-bpss-benchmark-cache]", function() {
        const $button = $(this);
        const $notice = $wrap.find("#bpss-benchmark-notice");
        if (!window.bpssAdmin?.ajaxUrl || !window.bpssAdmin?.nonce) {
          return;
        }
        $button.prop("disabled", true);
        $notice.prop("hidden", true).removeClass("is-success is-error");
        $.post(window.bpssAdmin.ajaxUrl, {
          action: "bpss_benchmark_cache",
          nonce: window.bpssAdmin.nonce
        }).done(function(response) {
          if (!response?.success || !response.data?.labels) {
            throw new Error("benchmark_failed");
          }
          renderBenchmark(
            $wrap,
            response.data.labels,
            response.data.benchmark?.measured_at || 0
          );
          $notice.addClass("is-success").prop("hidden", true);
        }).fail(function() {
          $notice.text(window.bpssAdmin.i18n.measureError).addClass("is-error").prop("hidden", false);
        }).always(function() {
          $button.prop("disabled", false);
        });
      });
      toggleCachePanel($wrap);
      if ($.fn.wpColorPicker) {
        $(".bpss-color-picker").wpColorPicker();
      }
    });
  })(jQuery);
})();
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsic2V0dGluZ3MudHMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbIi8qKlxuICogU21hcnQgU2VhcmNoIGFkbWluIHNldHRpbmdzIHRhYnMuXG4gKlxuICogQHBhY2thZ2UgQmVQbHVzRmFzdFByb2R1Y3RGaWx0ZXJMaXZlU2VhcmNoXG4gKi9cbi8vIEB0cy1ub2NoZWNrXG4oIGZ1bmN0aW9uICggJCApIHtcblx0J3VzZSBzdHJpY3QnO1xuXG5cdGZ1bmN0aW9uIHRvZ2dsZVByaWNlU2V0dGluZ3MoICR3cmFwICkge1xuXHRcdGNvbnN0IG1vZGUgPSAkd3JhcFxuXHRcdFx0LmZpbmQoICdpbnB1dFtuYW1lKj1cIltzaWRlYmFyXVtwcmljZV1bZGlzcGxheV1cIl06Y2hlY2tlZCcgKVxuXHRcdFx0LnZhbCgpO1xuXHRcdCR3cmFwLmZpbmQoICdbZGF0YS1icHNzLXByaWNlLXNldHRpbmdzPVwicmFuZ2VcIl0nICkucHJvcChcblx0XHRcdCdoaWRkZW4nLFxuXHRcdFx0bW9kZSAhPT0gJ3JhbmdlJ1xuXHRcdCk7XG5cdFx0JHdyYXAuZmluZCggJ1tkYXRhLWJwc3MtcHJpY2Utc2V0dGluZ3M9XCJzZWdtZW50c1wiXScgKS5wcm9wKFxuXHRcdFx0J2hpZGRlbicsXG5cdFx0XHRtb2RlICE9PSAnc2VnbWVudHMnXG5cdFx0KTtcblx0fVxuXG5cdGZ1bmN0aW9uIHJlaW5kZXhTZWdtZW50Um93cyggJHdyYXAgKSB7XG5cdFx0Y29uc3Qgb3B0aW9uS2V5ID0gJHdyYXBcblx0XHRcdC5maW5kKCAnaW5wdXRbbmFtZSo9XCJbc2lkZWJhcl1bcHJpY2VdW3NlZ21lbnRzXVwiXScgKVxuXHRcdFx0LmZpcnN0KClcblx0XHRcdC5hdHRyKCAnbmFtZScgKVxuXHRcdFx0Py5tYXRjaCggL15bXlxcW10rLyk/LlsgMCBdO1xuXG5cdFx0aWYgKCAhIG9wdGlvbktleSApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHQkd3JhcC5maW5kKCAnI2Jwc3MtcHJpY2Utc2VnbWVudHMgdGJvZHkgLmJwc3Mtc2V0dGluZ3NfX3NlZ21lbnQtcm93JyApLmVhY2goXG5cdFx0XHRmdW5jdGlvbiAoIGluZGV4ICkge1xuXHRcdFx0XHQkKCB0aGlzIClcblx0XHRcdFx0XHQuZmluZCggJ2lucHV0W2RhdGEtbmFtZT1cImxhYmVsXCJdLCBpbnB1dFtuYW1lKj1cIltsYWJlbF1cIl0nIClcblx0XHRcdFx0XHQuYXR0cihcblx0XHRcdFx0XHRcdCduYW1lJyxcblx0XHRcdFx0XHRcdG9wdGlvbktleSArXG5cdFx0XHRcdFx0XHRcdCdbc2lkZWJhcl1bcHJpY2VdW3NlZ21lbnRzXVsnICtcblx0XHRcdFx0XHRcdFx0aW5kZXggK1xuXHRcdFx0XHRcdFx0XHQnXVtsYWJlbF0nXG5cdFx0XHRcdFx0KTtcblx0XHRcdFx0JCggdGhpcyApXG5cdFx0XHRcdFx0LmZpbmQoICdpbnB1dFtkYXRhLW5hbWU9XCJtaW5cIl0sIGlucHV0W25hbWUqPVwiW21pbl1cIl0nIClcblx0XHRcdFx0XHQuYXR0cihcblx0XHRcdFx0XHRcdCduYW1lJyxcblx0XHRcdFx0XHRcdG9wdGlvbktleSArXG5cdFx0XHRcdFx0XHRcdCdbc2lkZWJhcl1bcHJpY2VdW3NlZ21lbnRzXVsnICtcblx0XHRcdFx0XHRcdFx0aW5kZXggK1xuXHRcdFx0XHRcdFx0XHQnXVttaW5dJ1xuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdCQoIHRoaXMgKVxuXHRcdFx0XHRcdC5maW5kKCAnaW5wdXRbZGF0YS1uYW1lPVwibWF4XCJdLCBpbnB1dFtuYW1lKj1cIlttYXhdXCJdJyApXG5cdFx0XHRcdFx0LmF0dHIoXG5cdFx0XHRcdFx0XHQnbmFtZScsXG5cdFx0XHRcdFx0XHRvcHRpb25LZXkgK1xuXHRcdFx0XHRcdFx0XHQnW3NpZGViYXJdW3ByaWNlXVtzZWdtZW50c11bJyArXG5cdFx0XHRcdFx0XHRcdGluZGV4ICtcblx0XHRcdFx0XHRcdFx0J11bbWF4XSdcblx0XHRcdFx0XHQpO1xuXHRcdFx0fVxuXHRcdCk7XG5cdH1cblxuXHRmdW5jdGlvbiByZWluZGV4Q3VzdG9tVGF4Um93cyggJHdyYXAgKSB7XG5cdFx0Y29uc3Qgb3B0aW9uS2V5ID0gJHdyYXBcblx0XHRcdC5maW5kKCAnc2VsZWN0W25hbWUqPVwiW3NpZGViYXJdW2ZhY2V0c11bY3VzdG9tX3RheG9ub21pZXNdXCJdJyApXG5cdFx0XHQuZmlyc3QoKVxuXHRcdFx0LmF0dHIoICduYW1lJyApXG5cdFx0XHQ/Lm1hdGNoKCAvXlteXFxbXSsvKT8uWyAwIF07XG5cblx0XHRpZiAoICEgb3B0aW9uS2V5ICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdCR3cmFwXG5cdFx0XHQuZmluZCggJyNicHNzLWN1c3RvbS10YXhvbm9taWVzIHRib2R5IC5icHNzLXNldHRpbmdzX19jdXN0b20tdGF4LXJvdycgKVxuXHRcdFx0LmVhY2goIGZ1bmN0aW9uICggaW5kZXggKSB7XG5cdFx0XHRcdCQoIHRoaXMgKVxuXHRcdFx0XHRcdC5maW5kKFxuXHRcdFx0XHRcdFx0J3NlbGVjdFtkYXRhLW5hbWU9XCJ0YXhvbm9teVwiXSwgc2VsZWN0W25hbWUqPVwiW3RheG9ub215XVwiXSdcblx0XHRcdFx0XHQpXG5cdFx0XHRcdFx0LmF0dHIoXG5cdFx0XHRcdFx0XHQnbmFtZScsXG5cdFx0XHRcdFx0XHRvcHRpb25LZXkgK1xuXHRcdFx0XHRcdFx0XHQnW3NpZGViYXJdW2ZhY2V0c11bY3VzdG9tX3RheG9ub21pZXNdWycgK1xuXHRcdFx0XHRcdFx0XHRpbmRleCArXG5cdFx0XHRcdFx0XHRcdCddW3RheG9ub215XSdcblx0XHRcdFx0XHQpO1xuXHRcdFx0XHQkKCB0aGlzIClcblx0XHRcdFx0XHQuZmluZCggJ2lucHV0W2RhdGEtbmFtZT1cImxhYmVsXCJdLCBpbnB1dFtuYW1lKj1cIltsYWJlbF1cIl0nIClcblx0XHRcdFx0XHQuYXR0cihcblx0XHRcdFx0XHRcdCduYW1lJyxcblx0XHRcdFx0XHRcdG9wdGlvbktleSArXG5cdFx0XHRcdFx0XHRcdCdbc2lkZWJhcl1bZmFjZXRzXVtjdXN0b21fdGF4b25vbWllc11bJyArXG5cdFx0XHRcdFx0XHRcdGluZGV4ICtcblx0XHRcdFx0XHRcdFx0J11bbGFiZWxdJ1xuXHRcdFx0XHRcdCk7XG5cdFx0XHRcdCQoIHRoaXMgKVxuXHRcdFx0XHRcdC5maW5kKCAnc2VsZWN0W2RhdGEtbmFtZT1cIm1vZGVcIl0sIHNlbGVjdFtuYW1lKj1cIlttb2RlXVwiXScgKVxuXHRcdFx0XHRcdC5hdHRyKFxuXHRcdFx0XHRcdFx0J25hbWUnLFxuXHRcdFx0XHRcdFx0b3B0aW9uS2V5ICtcblx0XHRcdFx0XHRcdFx0J1tzaWRlYmFyXVtmYWNldHNdW2N1c3RvbV90YXhvbm9taWVzXVsnICtcblx0XHRcdFx0XHRcdFx0aW5kZXggK1xuXHRcdFx0XHRcdFx0XHQnXVttb2RlXSdcblx0XHRcdFx0XHQpO1xuXHRcdFx0XHQkKCB0aGlzIClcblx0XHRcdFx0XHQuZmluZChcblx0XHRcdFx0XHRcdCdpbnB1dFtkYXRhLW5hbWU9XCJzaG93X3N1YlwiXSwgaW5wdXRbbmFtZSo9XCJbc2hvd19zdWJdXCJdJ1xuXHRcdFx0XHRcdClcblx0XHRcdFx0XHQuYXR0cihcblx0XHRcdFx0XHRcdCduYW1lJyxcblx0XHRcdFx0XHRcdG9wdGlvbktleSArXG5cdFx0XHRcdFx0XHRcdCdbc2lkZWJhcl1bZmFjZXRzXVtjdXN0b21fdGF4b25vbWllc11bJyArXG5cdFx0XHRcdFx0XHRcdGluZGV4ICtcblx0XHRcdFx0XHRcdFx0J11bc2hvd19zdWJdJ1xuXHRcdFx0XHRcdCk7XG5cdFx0XHR9ICk7XG5cdH1cblxuXHQkKCBmdW5jdGlvbiAoKSB7XG5cdFx0Y29uc3QgJHdyYXAgPSAkKCAnLmJwc3Mtc2V0dGluZ3MnICk7XG5cdFx0aWYgKCAhICR3cmFwLmxlbmd0aCApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHQkd3JhcC5vbiggJ2NsaWNrJywgJy5icHNzLXNldHRpbmdzX190YWInLCBmdW5jdGlvbiAoIGV2ZW50ICkge1xuXHRcdFx0ZXZlbnQucHJldmVudERlZmF1bHQoKTtcblx0XHRcdGNvbnN0IHRhYiA9ICQoIHRoaXMgKS5kYXRhKCAndGFiJyApO1xuXHRcdFx0aWYgKCAhIHRhYiApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHQkd3JhcC5maW5kKCAnLmJwc3Mtc2V0dGluZ3NfX3RhYicgKS5yZW1vdmVDbGFzcyggJ2lzLWFjdGl2ZScgKTtcblx0XHRcdCQoIHRoaXMgKS5hZGRDbGFzcyggJ2lzLWFjdGl2ZScgKTtcblx0XHRcdCR3cmFwLmZpbmQoICcuYnBzcy1zZXR0aW5nc19fcGFuZWwnICkucmVtb3ZlQ2xhc3MoICdpcy1hY3RpdmUnICk7XG5cdFx0XHQkd3JhcFxuXHRcdFx0XHQuZmluZCggJy5icHNzLXNldHRpbmdzX19wYW5lbFtkYXRhLXRhYi1wYW5lbD1cIicgKyB0YWIgKyAnXCJdJyApXG5cdFx0XHRcdC5hZGRDbGFzcyggJ2lzLWFjdGl2ZScgKTtcblx0XHRcdCR3cmFwLmZpbmQoICdpbnB1dFtuYW1lPVwiYnBzc19hY3RpdmVfdGFiXCJdJyApLnZhbCggdGFiICk7XG5cdFx0fSApO1xuXG5cdFx0JHdyYXAub24oXG5cdFx0XHQnY2hhbmdlJyxcblx0XHRcdCdpbnB1dFtuYW1lKj1cIltzaWRlYmFyXVtwcmljZV1bZGlzcGxheV1cIl0nLFxuXHRcdFx0ZnVuY3Rpb24gKCkge1xuXHRcdFx0XHR0b2dnbGVQcmljZVNldHRpbmdzKCAkd3JhcCApO1xuXHRcdFx0fVxuXHRcdCk7XG5cblx0XHQkd3JhcC5vbiggJ2NsaWNrJywgJy5icHNzLWFkZC1zZWdtZW50JywgZnVuY3Rpb24gKCkge1xuXHRcdFx0Y29uc3QgdGVtcGxhdGUgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChcblx0XHRcdFx0J2Jwc3Mtc2VnbWVudC1yb3ctdGVtcGxhdGUnXG5cdFx0XHQpO1xuXHRcdFx0Y29uc3QgdGJvZHkgPSAkd3JhcC5maW5kKCAnI2Jwc3MtcHJpY2Utc2VnbWVudHMgdGJvZHknIClbIDAgXTtcblxuXHRcdFx0aWYgKCAhIHRlbXBsYXRlIHx8ICEgdGJvZHkgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgY2xvbmUgPSB0ZW1wbGF0ZS5jb250ZW50LmNsb25lTm9kZSggdHJ1ZSApO1xuXHRcdFx0dGJvZHkuYXBwZW5kQ2hpbGQoIGNsb25lICk7XG5cdFx0XHRyZWluZGV4U2VnbWVudFJvd3MoICR3cmFwICk7XG5cdFx0fSApO1xuXG5cdFx0JHdyYXAub24oICdjbGljaycsICcuYnBzcy1yZW1vdmUtc2VnbWVudCcsIGZ1bmN0aW9uICgpIHtcblx0XHRcdCQoIHRoaXMgKS5jbG9zZXN0KCAnLmJwc3Mtc2V0dGluZ3NfX3NlZ21lbnQtcm93JyApLnJlbW92ZSgpO1xuXHRcdFx0cmVpbmRleFNlZ21lbnRSb3dzKCAkd3JhcCApO1xuXHRcdH0gKTtcblxuXHRcdCR3cmFwLm9uKCAnY2xpY2snLCAnLmJwc3MtYWRkLWN1c3RvbS10YXgnLCBmdW5jdGlvbiAoKSB7XG5cdFx0XHRjb25zdCB0ZW1wbGF0ZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKFxuXHRcdFx0XHQnYnBzcy1jdXN0b20tdGF4LXJvdy10ZW1wbGF0ZSdcblx0XHRcdCk7XG5cdFx0XHRjb25zdCB0Ym9keSA9ICR3cmFwLmZpbmQoICcjYnBzcy1jdXN0b20tdGF4b25vbWllcyB0Ym9keScgKVsgMCBdO1xuXG5cdFx0XHRpZiAoICEgdGVtcGxhdGUgfHwgISB0Ym9keSApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCBjbG9uZSA9IHRlbXBsYXRlLmNvbnRlbnQuY2xvbmVOb2RlKCB0cnVlICk7XG5cdFx0XHR0Ym9keS5hcHBlbmRDaGlsZCggY2xvbmUgKTtcblx0XHRcdHJlaW5kZXhDdXN0b21UYXhSb3dzKCAkd3JhcCApO1xuXHRcdH0gKTtcblxuXHRcdCR3cmFwLm9uKCAnY2xpY2snLCAnLmJwc3MtcmVtb3ZlLWN1c3RvbS10YXgnLCBmdW5jdGlvbiAoKSB7XG5cdFx0XHQkKCB0aGlzICkuY2xvc2VzdCggJy5icHNzLXNldHRpbmdzX19jdXN0b20tdGF4LXJvdycgKS5yZW1vdmUoKTtcblx0XHRcdHJlaW5kZXhDdXN0b21UYXhSb3dzKCAkd3JhcCApO1xuXHRcdH0gKTtcblxuXHRcdHRvZ2dsZVByaWNlU2V0dGluZ3MoICR3cmFwICk7XG5cblx0XHRmdW5jdGlvbiB0b2dnbGVDYWNoZVBhbmVsKCAkd3JhcCApIHtcblx0XHRcdGNvbnN0IGVuYWJsZWQgPSAkd3JhcFxuXHRcdFx0XHQuZmluZCggJ1tkYXRhLWJwc3MtY2FjaGUtdG9nZ2xlXScgKVxuXHRcdFx0XHQuaXMoICc6Y2hlY2tlZCcgKTtcblxuXHRcdFx0JHdyYXAuZmluZCggJ1tkYXRhLWJwc3MtY2FjaGUtcGFuZWxdJyApLnByb3AoICdoaWRkZW4nLCAhIGVuYWJsZWQgKTtcblx0XHRcdCR3cmFwLmZpbmQoICdbZGF0YS1icHNzLWNhY2hlLW9mZi1ub3RlXScgKS5wcm9wKCAnaGlkZGVuJywgZW5hYmxlZCApO1xuXHRcdFx0JHdyYXBcblx0XHRcdFx0LmZpbmQoICdbZGF0YS1icHNzLWNhY2hlLXN0YXRlLWxhYmVsXScgKVxuXHRcdFx0XHQudGV4dChcblx0XHRcdFx0XHRlbmFibGVkXG5cdFx0XHRcdFx0XHQ/IHdpbmRvdy5icHNzQWRtaW4/LmkxOG4/Lm9uIHx8ICdPbidcblx0XHRcdFx0XHRcdDogd2luZG93LmJwc3NBZG1pbj8uaTE4bj8ub2ZmIHx8ICdPZmYnXG5cdFx0XHRcdCk7XG5cdFx0fVxuXG5cdFx0ZnVuY3Rpb24gZm9ybWF0TGFzdENsZWFyZWQoIHRpbWVzdGFtcCApIHtcblx0XHRcdGlmICggISB0aW1lc3RhbXAgfHwgISB3aW5kb3cuYnBzc0FkbWluICkge1xuXHRcdFx0XHRyZXR1cm4gd2luZG93LmJwc3NBZG1pbj8uaTE4bj8ubmV2ZXJDbGVhcmVkIHx8ICcnO1xuXHRcdFx0fVxuXG5cdFx0XHRjb25zdCBkYXRlID0gbmV3IERhdGUoIHRpbWVzdGFtcCAqIDEwMDAgKTtcblx0XHRcdGNvbnN0IGZvcm1hdHRlZCA9IGRhdGUudG9Mb2NhbGVTdHJpbmcoKTtcblx0XHRcdHJldHVybiAoXG5cdFx0XHRcdCggd2luZG93LmJwc3NBZG1pbi5pMThuLmxhc3RDbGVhcmVkIHx8ICdMYXN0IGNsZWFyZWQ6JyApICtcblx0XHRcdFx0JyAnICtcblx0XHRcdFx0Zm9ybWF0dGVkXG5cdFx0XHQpO1xuXHRcdH1cblxuXHRcdGZ1bmN0aW9uIHJlbmRlckJlbmNobWFyayggJHdyYXAsIGxhYmVscywgbWVhc3VyZWRBdCApIHtcblx0XHRcdGNvbnN0ICRib2R5ID0gJHdyYXAuZmluZCggJ1tkYXRhLWJwc3MtYmVuY2htYXJrLWJvZHldJyApO1xuXHRcdFx0JGJvZHkuZmluZCggJ1tkYXRhLWJwc3MtYmVuY2htYXJrLWVtcHR5XScgKS5yZW1vdmUoKTtcblxuXHRcdFx0bGV0ICRncmlkID0gJGJvZHkuZmluZCggJy5icHNzLWNhY2hlX19iZW5jaG1hcmstZ3JpZCcgKTtcblx0XHRcdGlmICggISAkZ3JpZC5sZW5ndGggKSB7XG5cdFx0XHRcdCRncmlkID0gJCggYFxuXHRcdFx0XHRcdDxkaXYgY2xhc3M9XCJicHNzLWNhY2hlX19iZW5jaG1hcmstZ3JpZFwiPlxuXHRcdFx0XHRcdFx0PGRpdiBjbGFzcz1cImJwc3MtY2FjaGVfX2JlbmNobWFyay1zdGF0XCI+XG5cdFx0XHRcdFx0XHRcdDxzcGFuIGNsYXNzPVwiYnBzcy1jYWNoZV9fYmVuY2htYXJrLWxhYmVsXCI+PC9zcGFuPlxuXHRcdFx0XHRcdFx0XHQ8c3Ryb25nIGNsYXNzPVwiYnBzcy1jYWNoZV9fYmVuY2htYXJrLXZhbHVlXCIgZGF0YS1icHNzLWJlbmNobWFyay1jb2xkPjwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzPVwiYnBzcy1jYWNoZV9fYmVuY2htYXJrLXN0YXQgaXMtaGlnaGxpZ2h0XCI+XG5cdFx0XHRcdFx0XHRcdDxzcGFuIGNsYXNzPVwiYnBzcy1jYWNoZV9fYmVuY2htYXJrLWxhYmVsXCI+PC9zcGFuPlxuXHRcdFx0XHRcdFx0XHQ8c3Ryb25nIGNsYXNzPVwiYnBzcy1jYWNoZV9fYmVuY2htYXJrLXZhbHVlXCIgZGF0YS1icHNzLWJlbmNobWFyay13YXJtPjwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0XHQ8ZGl2IGNsYXNzPVwiYnBzcy1jYWNoZV9fYmVuY2htYXJrLXN0YXRcIj5cblx0XHRcdFx0XHRcdFx0PHNwYW4gY2xhc3M9XCJicHNzLWNhY2hlX19iZW5jaG1hcmstbGFiZWxcIj48L3NwYW4+XG5cdFx0XHRcdFx0XHRcdDxzdHJvbmcgY2xhc3M9XCJicHNzLWNhY2hlX19iZW5jaG1hcmstdmFsdWVcIiBkYXRhLWJwc3MtYmVuY2htYXJrLXNhdmVkPjwvc3Ryb25nPlxuXHRcdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0PC9kaXY+XG5cdFx0XHRcdFx0PHAgY2xhc3M9XCJkZXNjcmlwdGlvbiBicHNzLWNhY2hlX19iZW5jaG1hcmstbWV0YVwiIGRhdGEtYnBzcy1iZW5jaG1hcmstbWV0YT48L3A+XG5cdFx0XHRcdGAgKTtcblx0XHRcdFx0JGJvZHkuYXBwZW5kKCAkZ3JpZCApO1xuXHRcdFx0fVxuXG5cdFx0XHQkZ3JpZFxuXHRcdFx0XHQuZmluZCggJy5icHNzLWNhY2hlX19iZW5jaG1hcmstc3RhdCcgKVxuXHRcdFx0XHQuZXEoIDAgKVxuXHRcdFx0XHQuZmluZCggJy5icHNzLWNhY2hlX19iZW5jaG1hcmstbGFiZWwnIClcblx0XHRcdFx0LnRleHQoIHdpbmRvdy5icHNzQWRtaW4/LmkxOG4/LmNvbGRMYWJlbCB8fCAnV2l0aG91dCBjYWNoZScgKTtcblx0XHRcdCRncmlkXG5cdFx0XHRcdC5maW5kKCAnLmJwc3MtY2FjaGVfX2JlbmNobWFyay1zdGF0JyApXG5cdFx0XHRcdC5lcSggMSApXG5cdFx0XHRcdC5maW5kKCAnLmJwc3MtY2FjaGVfX2JlbmNobWFyay1sYWJlbCcgKVxuXHRcdFx0XHQudGV4dCggd2luZG93LmJwc3NBZG1pbj8uaTE4bj8ud2FybUxhYmVsIHx8ICdXaXRoIGNhY2hlJyApO1xuXHRcdFx0JGdyaWRcblx0XHRcdFx0LmZpbmQoICcuYnBzcy1jYWNoZV9fYmVuY2htYXJrLXN0YXQnIClcblx0XHRcdFx0LmVxKCAyIClcblx0XHRcdFx0LmZpbmQoICcuYnBzcy1jYWNoZV9fYmVuY2htYXJrLWxhYmVsJyApXG5cdFx0XHRcdC50ZXh0KCB3aW5kb3cuYnBzc0FkbWluPy5pMThuPy5zYXZlZExhYmVsIHx8ICdFc3RpbWF0ZWQgc2F2aW5nJyApO1xuXG5cdFx0XHQkYm9keS5maW5kKCAnW2RhdGEtYnBzcy1iZW5jaG1hcmstY29sZF0nICkudGV4dCggbGFiZWxzLmNvbGQgKTtcblx0XHRcdCRib2R5LmZpbmQoICdbZGF0YS1icHNzLWJlbmNobWFyay13YXJtXScgKS50ZXh0KCBsYWJlbHMud2FybSApO1xuXHRcdFx0JGJvZHlcblx0XHRcdFx0LmZpbmQoICdbZGF0YS1icHNzLWJlbmNobWFyay1zYXZlZF0nIClcblx0XHRcdFx0LnRleHQoIGxhYmVscy5zYXZlZCArICcgKCcgKyBsYWJlbHMucGVyY2VudCArICclIGZhc3RlciknICk7XG5cblx0XHRcdGlmICggbWVhc3VyZWRBdCApIHtcblx0XHRcdFx0Y29uc3QgZm9ybWF0dGVkID0gbmV3IERhdGUoIG1lYXN1cmVkQXQgKiAxMDAwICkudG9Mb2NhbGVTdHJpbmcoKTtcblx0XHRcdFx0JGJvZHlcblx0XHRcdFx0XHQuZmluZCggJ1tkYXRhLWJwc3MtYmVuY2htYXJrLW1ldGFdJyApXG5cdFx0XHRcdFx0LnRleHQoXG5cdFx0XHRcdFx0XHQoIHdpbmRvdy5icHNzQWRtaW4/LmkxOG4/Lm1lYXN1cmVkQXQgfHwgJ01lYXN1cmVkOicgKSArXG5cdFx0XHRcdFx0XHRcdCcgJyArXG5cdFx0XHRcdFx0XHRcdGZvcm1hdHRlZFxuXHRcdFx0XHRcdCk7XG5cdFx0XHR9XG5cdFx0fVxuXG5cdFx0JHdyYXAub24oICdjaGFuZ2UnLCAnW2RhdGEtYnBzcy1jYWNoZS10b2dnbGVdJywgZnVuY3Rpb24gKCkge1xuXHRcdFx0dG9nZ2xlQ2FjaGVQYW5lbCggJHdyYXAgKTtcblx0XHR9ICk7XG5cblx0XHQkd3JhcC5vbiggJ2NsaWNrJywgJ1tkYXRhLWJwc3MtY2xlYXItY2FjaGVdJywgZnVuY3Rpb24gKCkge1xuXHRcdFx0Y29uc3QgJGJ1dHRvbiA9ICQoIHRoaXMgKTtcblx0XHRcdGNvbnN0ICRub3RpY2UgPSAkd3JhcC5maW5kKCAnI2Jwc3MtY2FjaGUtbm90aWNlJyApO1xuXHRcdFx0Y29uc3QgJHN0YXR1cyA9ICR3cmFwLmZpbmQoICdbZGF0YS1icHNzLWNhY2hlLXN0YXR1c10nICk7XG5cblx0XHRcdGlmICggISB3aW5kb3cuYnBzc0FkbWluPy5hamF4VXJsIHx8ICEgd2luZG93LmJwc3NBZG1pbj8ubm9uY2UgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0JGJ1dHRvbi5wcm9wKCAnZGlzYWJsZWQnLCB0cnVlICk7XG5cdFx0XHQkbm90aWNlLnByb3AoICdoaWRkZW4nLCB0cnVlICkucmVtb3ZlQ2xhc3MoICdpcy1zdWNjZXNzIGlzLWVycm9yJyApO1xuXG5cdFx0XHQkLnBvc3QoIHdpbmRvdy5icHNzQWRtaW4uYWpheFVybCwge1xuXHRcdFx0XHRhY3Rpb246ICdicHNzX2NsZWFyX2NhY2hlJyxcblx0XHRcdFx0bm9uY2U6IHdpbmRvdy5icHNzQWRtaW4ubm9uY2UsXG5cdFx0XHR9IClcblx0XHRcdFx0LmRvbmUoIGZ1bmN0aW9uICggcmVzcG9uc2UgKSB7XG5cdFx0XHRcdFx0aWYgKCAhIHJlc3BvbnNlPy5zdWNjZXNzICkge1xuXHRcdFx0XHRcdFx0dGhyb3cgbmV3IEVycm9yKCAnY2xlYXJfZmFpbGVkJyApO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGNvbnN0IGNsZWFyZWRBdCA9IHJlc3BvbnNlLmRhdGE/LmNsZWFyZWRBdCB8fCAwO1xuXHRcdFx0XHRcdCRzdGF0dXMudGV4dCggZm9ybWF0TGFzdENsZWFyZWQoIGNsZWFyZWRBdCApICk7XG5cdFx0XHRcdFx0JG5vdGljZVxuXHRcdFx0XHRcdFx0LnRleHQoXG5cdFx0XHRcdFx0XHRcdHJlc3BvbnNlLmRhdGE/Lm1lc3NhZ2UgfHxcblx0XHRcdFx0XHRcdFx0XHR3aW5kb3cuYnBzc0FkbWluLmkxOG4uY2xlYXJlZFxuXHRcdFx0XHRcdFx0KVxuXHRcdFx0XHRcdFx0LmFkZENsYXNzKCAnaXMtc3VjY2VzcycgKVxuXHRcdFx0XHRcdFx0LnByb3AoICdoaWRkZW4nLCBmYWxzZSApO1xuXHRcdFx0XHR9IClcblx0XHRcdFx0LmZhaWwoIGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0XHQkbm90aWNlXG5cdFx0XHRcdFx0XHQudGV4dCggd2luZG93LmJwc3NBZG1pbi5pMThuLmNsZWFyRXJyb3IgKVxuXHRcdFx0XHRcdFx0LmFkZENsYXNzKCAnaXMtZXJyb3InIClcblx0XHRcdFx0XHRcdC5wcm9wKCAnaGlkZGVuJywgZmFsc2UgKTtcblx0XHRcdFx0fSApXG5cdFx0XHRcdC5hbHdheXMoIGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0XHQkYnV0dG9uLnByb3AoICdkaXNhYmxlZCcsIGZhbHNlICk7XG5cdFx0XHRcdH0gKTtcblx0XHR9ICk7XG5cblx0XHQkd3JhcC5vbiggJ2NsaWNrJywgJ1tkYXRhLWJwc3MtYmVuY2htYXJrLWNhY2hlXScsIGZ1bmN0aW9uICgpIHtcblx0XHRcdGNvbnN0ICRidXR0b24gPSAkKCB0aGlzICk7XG5cdFx0XHRjb25zdCAkbm90aWNlID0gJHdyYXAuZmluZCggJyNicHNzLWJlbmNobWFyay1ub3RpY2UnICk7XG5cblx0XHRcdGlmICggISB3aW5kb3cuYnBzc0FkbWluPy5hamF4VXJsIHx8ICEgd2luZG93LmJwc3NBZG1pbj8ubm9uY2UgKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0JGJ1dHRvbi5wcm9wKCAnZGlzYWJsZWQnLCB0cnVlICk7XG5cdFx0XHQkbm90aWNlLnByb3AoICdoaWRkZW4nLCB0cnVlICkucmVtb3ZlQ2xhc3MoICdpcy1zdWNjZXNzIGlzLWVycm9yJyApO1xuXG5cdFx0XHQkLnBvc3QoIHdpbmRvdy5icHNzQWRtaW4uYWpheFVybCwge1xuXHRcdFx0XHRhY3Rpb246ICdicHNzX2JlbmNobWFya19jYWNoZScsXG5cdFx0XHRcdG5vbmNlOiB3aW5kb3cuYnBzc0FkbWluLm5vbmNlLFxuXHRcdFx0fSApXG5cdFx0XHRcdC5kb25lKCBmdW5jdGlvbiAoIHJlc3BvbnNlICkge1xuXHRcdFx0XHRcdGlmICggISByZXNwb25zZT8uc3VjY2VzcyB8fCAhIHJlc3BvbnNlLmRhdGE/LmxhYmVscyApIHtcblx0XHRcdFx0XHRcdHRocm93IG5ldyBFcnJvciggJ2JlbmNobWFya19mYWlsZWQnICk7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0cmVuZGVyQmVuY2htYXJrKFxuXHRcdFx0XHRcdFx0JHdyYXAsXG5cdFx0XHRcdFx0XHRyZXNwb25zZS5kYXRhLmxhYmVscyxcblx0XHRcdFx0XHRcdHJlc3BvbnNlLmRhdGEuYmVuY2htYXJrPy5tZWFzdXJlZF9hdCB8fCAwXG5cdFx0XHRcdFx0KTtcblx0XHRcdFx0XHQkbm90aWNlXG5cdFx0XHRcdFx0XHQuYWRkQ2xhc3MoICdpcy1zdWNjZXNzJyApXG5cdFx0XHRcdFx0XHQucHJvcCggJ2hpZGRlbicsIHRydWUgKTtcblx0XHRcdFx0fSApXG5cdFx0XHRcdC5mYWlsKCBmdW5jdGlvbiAoKSB7XG5cdFx0XHRcdFx0JG5vdGljZVxuXHRcdFx0XHRcdFx0LnRleHQoIHdpbmRvdy5icHNzQWRtaW4uaTE4bi5tZWFzdXJlRXJyb3IgKVxuXHRcdFx0XHRcdFx0LmFkZENsYXNzKCAnaXMtZXJyb3InIClcblx0XHRcdFx0XHRcdC5wcm9wKCAnaGlkZGVuJywgZmFsc2UgKTtcblx0XHRcdFx0fSApXG5cdFx0XHRcdC5hbHdheXMoIGZ1bmN0aW9uICgpIHtcblx0XHRcdFx0XHQkYnV0dG9uLnByb3AoICdkaXNhYmxlZCcsIGZhbHNlICk7XG5cdFx0XHRcdH0gKTtcblx0XHR9ICk7XG5cblx0XHR0b2dnbGVDYWNoZVBhbmVsKCAkd3JhcCApO1xuXG5cdFx0aWYgKCAkLmZuLndwQ29sb3JQaWNrZXIgKSB7XG5cdFx0XHQkKCAnLmJwc3MtY29sb3ItcGlja2VyJyApLndwQ29sb3JQaWNrZXIoKTtcblx0XHR9XG5cdH0gKTtcbn0gKSggalF1ZXJ5ICk7XG4iXSwKICAibWFwcGluZ3MiOiAiOzs7QUFNQSxHQUFFLFNBQVcsR0FBSTtBQUNoQjtBQUVBLGFBQVMsb0JBQXFCLE9BQVE7QUFDckMsWUFBTSxPQUFPLE1BQ1gsS0FBTSxrREFBbUQsRUFDekQsSUFBSTtBQUNOLFlBQU0sS0FBTSxvQ0FBcUMsRUFBRTtBQUFBLFFBQ2xEO0FBQUEsUUFDQSxTQUFTO0FBQUEsTUFDVjtBQUNBLFlBQU0sS0FBTSx1Q0FBd0MsRUFBRTtBQUFBLFFBQ3JEO0FBQUEsUUFDQSxTQUFTO0FBQUEsTUFDVjtBQUFBLElBQ0Q7QUFFQSxhQUFTLG1CQUFvQixPQUFRO0FBQ3BDLFlBQU0sWUFBWSxNQUNoQixLQUFNLDJDQUE0QyxFQUNsRCxNQUFNLEVBQ04sS0FBTSxNQUFPLEdBQ1osTUFBTyxTQUFTLElBQUssQ0FBRTtBQUUxQixVQUFLLENBQUUsV0FBWTtBQUNsQjtBQUFBLE1BQ0Q7QUFFQSxZQUFNLEtBQU0sd0RBQXlELEVBQUU7QUFBQSxRQUN0RSxTQUFXLE9BQVE7QUFDbEIsWUFBRyxJQUFLLEVBQ04sS0FBTSxrREFBbUQsRUFDekQ7QUFBQSxZQUNBO0FBQUEsWUFDQSxZQUNDLGdDQUNBLFFBQ0E7QUFBQSxVQUNGO0FBQ0QsWUFBRyxJQUFLLEVBQ04sS0FBTSw4Q0FBK0MsRUFDckQ7QUFBQSxZQUNBO0FBQUEsWUFDQSxZQUNDLGdDQUNBLFFBQ0E7QUFBQSxVQUNGO0FBQ0QsWUFBRyxJQUFLLEVBQ04sS0FBTSw4Q0FBK0MsRUFDckQ7QUFBQSxZQUNBO0FBQUEsWUFDQSxZQUNDLGdDQUNBLFFBQ0E7QUFBQSxVQUNGO0FBQUEsUUFDRjtBQUFBLE1BQ0Q7QUFBQSxJQUNEO0FBRUEsYUFBUyxxQkFBc0IsT0FBUTtBQUN0QyxZQUFNLFlBQVksTUFDaEIsS0FBTSxzREFBdUQsRUFDN0QsTUFB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
