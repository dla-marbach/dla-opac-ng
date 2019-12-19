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
        var containingList = $(this).parents('ul')[0];
        if ($(this).hasClass('dla-toggle-facets-collapsed')) {
            jQuery('.hidden', containingList).slideDown(300);
            $(this).html($(this).data('translate-show-less'));
        } else {
            jQuery('.hidden', containingList).slideUp(300);
            $(this).html($(this).data('translate-show-all'));
        }
        $(this).toggleClass('dla-toggle-facets-collapsed');
    });
});

$(document).ready(function(){
    var anchor =  document.location.hash;

    if (anchor == "#tabaccess") {
        $('#access').show();
        $('#detail').hide();
        $('#tabaccess').toggleClass('ctg-dtvt-menu-active');
        $('#tabdetail').toggleClass('ctg-dtvt-menu-active');
    }

    $('#tabdetail').on("click", function (event) {
        //event.preventDefault();
        $('#detail').show();
        $('#access').hide();
        $(this).toggleClass('ctg-dtvt-menu-active');
        $('#tabaccess').toggleClass('ctg-dtvt-menu-active');
    });

    $('#tabaccess').on("click", function (event) {
        //event.preventDefault();
        $('#access').show();
        $('#detail').hide();
        $(this).toggleClass('ctg-dtvt-menu-active');
        $('#tabdetail').toggleClass('ctg-dtvt-menu-active');
    });


    $('.dlaResultCountSelect').change(function (event) {
        $('#countHidden').val($(this).val());
        $("form").submit();
    });

    $('.order-button').click(function (event) {

        $('.order-overlay').toggle();

        var orderurl = $(this).data('orderurl');

        $('.order-overlay-button').data('orderurl', orderurl);
    });

    $('.order-overlay-button').click(function (event) {
        var nameValue = $('#order-name').val();
        var pwValue = $('#order-pw').val();
        if (!nameValue || !pwValue) {
            $('.order-overlay .error').show();
        } else {

            var orderurl = $(this).data('orderurl');
            orderurl = orderurl.replace("%name%", encodeURI(nameValue));
            orderurl = orderurl.replace("%pw%", pwValue);

            var win = window.open(orderurl, '_blank');
            if (win) {
                //Browser has allowed it to be opened
                win.focus();
            } else {
                //Browser has blocked it
                alert('Please allow popups for this website');
            }
        }
    });

    $('.close-button').click(function (event) {
        $(this).parent().toggle();
    });
});




