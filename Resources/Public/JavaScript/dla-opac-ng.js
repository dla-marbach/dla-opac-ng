$(document).ready(function(){
    $("[data-facet-toggle]").click(function(){
        $(this).closest(".ctg-facet").find(".facetList").toggle();
        $(this).find(".icon").toggleClass("bel-pfeil-u01 bel-pfeil-o01");
    });
});

$(document).ready(function(){
    $("[data-details-toggle]").click(function(){
        $(this).closest(".ctg-ri-actions").siblings(".ctg-ri-details").toggle();
        $(this).find(".icon").toggleClass("bel-pfeil-u01 bel-pfeil-o01");
    });
});

$(document).ready(function(){
    $("[data-sort]").change(function(){
        var uri = $(document).find("#sortUri"+$(this).find(":selected").index()).attr('uri');
        $(location).attr('href',uri);
    });
});

$(document).ready(function(){
    $('.dla-toggle-facets').on('click', function(e){
        e.preventDefault();
        var containingList =  $(this).parents('ul')[0];
        jQuery('.hidden', containingList).slideDown(300);
        $(this).parent().fadeOut(200);
    });
});

