/**
 * Fix JetSmartFilters pagination rendering on initial page load with query parameters
 */
(function ($) {
  "use strict";

  function fixPagination() {
    // Check if we have props from PHP
    if (typeof jetEngineNestedListingFix === "undefined") {
      return;
    }

    var phpProvider = jetEngineNestedListingFix.provider;
    var phpQueryId = jetEngineNestedListingFix.queryId;
    var props = jetEngineNestedListingFix.props;

    console.log("Fixing pagination for:", phpProvider, phpQueryId);

    // Find the pagination widget that matches our provider and queryId
    $(".jet-smart-filters-pagination").each(function () {
      var $pagination = $(this);
      var provider = $pagination.data("content-provider");
      var queryId = $pagination.data("query-id");

      // Only process if this matches our PHP data
      if (provider !== phpProvider || queryId !== phpQueryId) {
        return;
      }

      // Check if pagination is empty (not rendered)
      if ($pagination.children().length === 0) {
        console.log("Found empty pagination widget, attempting to fix...");

        // Get the filter group
        if (window.JetSmartFilters && window.JetSmartFilters.filterGroups) {
          var groupKey = provider + "/" + queryId;
          var filterGroup = window.JetSmartFilters.filterGroups[groupKey];

          if (filterGroup && filterGroup.filters) {
            // Find the pagination filter
            var paginationFilter = null;

            for (var i = 0; i < filterGroup.filters.length; i++) {
              if (filterGroup.filters[i].name === "pagination") {
                paginationFilter = filterGroup.filters[i];
                break;
              }
            }

            if (paginationFilter && props && props.max_num_pages) {
              console.log(
                "Setting pagination:",
                props.max_num_pages,
                "pages, current page:",
                props.page
              );

              // Set the pagination data
              paginationFilter.pagesCount = props.max_num_pages;
              paginationFilter.pageIndex = props.page;

              // Build the pagination
              if (typeof paginationFilter.buildPagination === "function") {
                paginationFilter.buildPagination();
                console.log("Pagination rendered successfully!");
              }
            }
          }
        }
      }
    });
  }

  // Try multiple times with increasing delays
  $(document).ready(function () {
    setTimeout(fixPagination, 100);
    setTimeout(fixPagination, 500);
    setTimeout(fixPagination, 1000);
    setTimeout(fixPagination, 2000);
  });
})(jQuery);
