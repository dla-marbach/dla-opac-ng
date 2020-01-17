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

        $('.order-overlay .login-form').show();
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

            $.ajax({
                url: orderurl,
            })
            .done(function( data ) {
                $('.order-overlay .login-form').hide();
                $('.order-overlay .info').text($(data).find(".kginfo").text()).show();
                $('.order-overlay .confirm').show().on("click", function (event) {
                    event.preventDefault();
                    $('.order-overlay').hide();
                });

            })
            .fail(function() {
                sendOrderAsPopup(orderurl);
            });


        }
    });

    $('.close-button').click(function (event) {
        $(this).parent().toggle();
    });
});

function sendOrderAsPopup(orderurl) {
    // if ajax failed
    var win = window.open(orderurl, '_blank');
    if (win) {
        //Browser has allowed it to be opened
        win.focus();
    } else {
        //Browser has blocked it
        alert('Please allow popups for this website');
    }
    $('.order-overlay').hide();
}

function copyUrlAction() {
    var location = window.location;
    var pageId = getUrlParameter('id');
    var docId = getUrlParameter('tx_find_find%5Bid%5D');
    var url = location.origin + location.pathname + '?id=' + pageId + '&tx_find_find[id]=' + docId + '';

    $('.action-copied-info-input').val(url);
    $('.action-copied-info-button').show();
    $('.action-copied-info-button.action-copied-success').hide();

    $('.action-copied-info').toggle();
}

function copyUrl() {

    var input = document.getElementById('action-copied-info-input');

    input.select();
    document.execCommand('copy');

    $('.action-copied-info-button').hide();
    $('.action-copied-info-button.action-copied-success').fadeIn();

    $('.action-copied-info').delay(2000).fadeOut();
}

function sendUrlByMail(title) {
    var subject= title;
    var body = "";
    body += window.location.href;
    body += "";
    var uri = "mailto:?subject=";
    uri += encodeURIComponent(subject);
    uri += "&body=";
    uri += encodeURIComponent(body);
    window.open(uri);
}

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
};

