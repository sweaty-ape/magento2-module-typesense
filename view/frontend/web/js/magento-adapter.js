requirejs([
    'Develo_Typesense/js/typesense-adapter',
    'algoliaAnalytics',
    'algoliaBundle',
    'suggestionsHtml',
    'algoliaCommon',
    'domReady!'
], function (TypesenseInstantSearchAdapter) {

    var isAvailable =
        typeof algoliaConfig !== 'undefined' &&
        typeof algoliaConfig.typesense !== 'undefined' &&
        typeof algolia !== 'undefined';

    if (!isAvailable) {
        return;
    }

    if (!algoliaConfig.typesense.isEnabled) {
        return;
    }

    var query_by = 'name,categories';
    if (typeof algoliaConfig.typesense_searchable !== 'undefined') {
        query_by = algoliaConfig.typesense_searchable.products + ',embedding';
    }

    window.typesenseInstantsearchAdapter = new TypesenseInstantSearchAdapter({
        server: algoliaConfig.typesense.config,
        additionalSearchParameters: {
            query_by: query_by,
            exclude_fields: 'embedding',
            sort_by: '_text_match(buckets: 10):desc,ordered_qty:desc'
        }
    });

    algolia.registerHook('beforeInstantsearchInit', function (instantsearchOptions) {

        window.typesenseInstantsearchAdapter.searchClient.addAlgoliaAgent = function () {
            // do nothing, function is required.
        }

        instantsearchOptions.searchClient = window.typesenseInstantsearchAdapter.searchClient;

        return instantsearchOptions;
    })
});
