jQuery(document).ready(function() {
	var $ = jQuery;
    var self = this;

    // use the symphony duplicator javascript to allow the user
    // to an an arbitrary number of column and field mappings.
    $('ol.csv-mapping-duplicator').symphonyDuplicator({
        orderable:	true
    });

    // when the value of the section selector changes, update the
    // selected field mappings for the new section
    $('#csv-section-toggle').bind('change', function() {
        $('.csv-section-' + $(this).val()).show().siblings().hide();
    }).change();

    // when the value of the header option changes, update the
    // example for the new value
    $('#csv-header-toggle').bind('change', function() {
        var value = $(this).is(':checked');
        $('.csv-example-' + value).show().siblings().hide();
        $('.csv-heading-' + value).show().siblings().hide();
    }).change();

    $('form').bind('submit', function() {
        $('.csv-section-list').children(':not(:visible)').remove();
    });
});
