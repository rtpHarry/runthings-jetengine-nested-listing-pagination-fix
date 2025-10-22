# runthings-jetengine-nested-listing-pagination-fix

This is a highly experimental plugin to fix pagination issues when using nested listings within JetEngine Query Builder listings with JetSmartFilters.

I did not want to solve this problem but had to work around bugs.

I am not offering support for this plugin but it may give you a starting point if you are developer.

The issues I had were as follows:

I had a jetengine listing for my main cpt, which had a nested taxonomy listing inside of it.

When I added the pagination and set it up according to crocoblock docs, I was seeing the paging information for the inner taxonomy being returned, not the main cpt.

So this plugin lets you specify your main query builder queries, that have nested queries, and it will fix the pagination for you, by taking a snapshot of the main query's pagination props and restoring them after all queries have run.

This fixed things for the initial page load, and for ajax requests when you start from the first page.

However, my components were all set to "mixed" mode so they were updating the querystring. If I loaded in to a page that already had querystring parameters, the pagination would not show.

When I dug into it, I found that the pagination settings were coming up blank.

It looks like the fix that I had done, runs after this code is output, so it wasn't seeing it.

So the second thing it does is output the main query's pagination props to javascript into the footer.

That makes the missing paging information available on the javascript global scope.

It then uses jquery to find the pagination widget and regenerate the paging using the pagination components api/js.

It's a collection of hacks, but it works for my code.

Some assumptions that might be required or might not:

- The listing grid is using query builder to power both the main listing query and the sub queries
- The listing result is on a page with jet smart filters, and everything is set to "mixed" mode
- There are multiple listing grids on the page, so a custom id is set on the main query builder listing, plus the listing grid, pagination control, plus all of the related jet smart filters
- The listing grid is not using "is archive mode"

I have raised the issue here, but its not a very clean bug report and I don't expect it to be fixed unless a lot of people are affected:

https://github.com/Crocoblock/suggestions/issues/8303

If this is affecting you, good luck!
