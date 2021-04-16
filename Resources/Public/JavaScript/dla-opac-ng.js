const PLUGIN_PATH = '/katalog-ng/suche/opac/id/';

$(document).ready(function(){
    initDatepickerExtSearch();
});

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
        event.preventDefault();
        $('#detail').show();
        $('#access').hide();
        $(this).toggleClass('ctg-dtvt-menu-active');
        $('#tabaccess').toggleClass('ctg-dtvt-menu-active');
    });

    $('#tabaccess').on("click", function (event) {
        event.preventDefault();
        $('#access').show();
        $('#detail').hide();
        $(this).toggleClass('ctg-dtvt-menu-active');
        $('#tabdetail').toggleClass('ctg-dtvt-menu-active');
    });


    $('.dlaResultCountSelect').change(function (event) {
        //$('#countHidden').val($(this).val());
        //$("form").submit();
        var uri = $(document).find("#pagingUri"+$(this).find(":selected").index()).attr('uri');
        $(location).attr('href',uri);

    });

    $('.order-button').click(function (event) {
        event.preventDefault();
        $('.order-overlay .login-form').show();
        $('.order-overlay .confirm').hide();
        $('.order-overlay').toggle();

        var orderurl = $(this).data('orderurl');

        $('.order-overlay-button').data('orderurl', orderurl);

        $('html,body').animate({scrollTop: $('#content-area').offset().top},'slow');
        $('#order-name').focus();
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
                $('.order-overlay .info').text($(data).filter("#meldung").text()).show();
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
        event.preventDefault();
        $(this).parent().toggle();
    });


    $('.copy-details').hide();

    $('.detail_link').click(function( event ) {
        event.preventDefault();

        $(this).parent().parent().next('.copy-details').toggle();
    });

    // permalink actions
    $('.permalink').on('click', function (event) {
        event.preventDefault();
        copyUrlAction($(this));
    });

    $('.au-permalink').on('click', function (event) {
        event.preventDefault();
        copyUrlAction($(this));
    });

    $('.copy-link-button').on('click', function (event) {
        event.preventDefault();
        copyUrl($(this));
    });

    var docId = getUrlParameter('tx_find_find%5Bau%5D');
    if (docId) {
        $('.row-'+docId).addClass('au-highlighting');
        $('.row-'+docId).next().addClass('au-highlighting');
        //$('.row-'+docId).find('.detail_link').click();
    }

    // export formats
    $('.export-choice-button').on('click', function (event) {
        event.preventDefault();
        $(this).next().toggle();
    });

    $('.citation-export').on('click', function (event) {
        event.preventDefault();

        var citation = "";

        var citationfields = $(this).find(".citation_field").each(function() {
            $(this).find('.citation_title').text($(this).find('.citation_title').text().trim());
            $(this).find('.citation_value').text($(this).find('.citation_value').text().trim());
            citation += $(this).text().trim().replace(/[\n\r\t]/g, "") + "\r\n";
            console.log(citation);
        });

        var fileExt = $(this).data('fileext');
        var exportName = $(this).data('exportname');

        downloadContentAsFile(citation, "text/plain", exportName + '.' + fileExt);

    });

});

function downloadContentAsFile(content, type, filename) {
    var prepareContent = "data:" + type + ";charset=utf-8,";
    prepareContent += content;

    var encodedUri = encodeURI(prepareContent);

    var link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();

}

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

// function copyUrlAction() {
//     var location = window.location;
//     var pageId = getUrlParameter('id');
//     var docId = getUrlParameter('tx_find_find%5Bid%5D');
//     var url = location.origin + location.pathname + '?id=' + pageId + '&tx_find_find[id]=' + docId + '';
//
//     $('.action-copied-info-input').val(url);
//     $('.action-copied-info-button').show();
//     $('.action-copied-info-button.action-copied-success').hide();
//
//     $('.action-copied-info').toggle();
// }

function copyUrlAction(context) {
    var location = window.location;
    var reg = new RegExp("\\/suche\\/opac\\/id\\/(.*)\\/");
    var AU = context.data('auid');

    var match = location.href.match(reg);

    if (match == undefined) {
        var pageId = getUrlParameter('id');
        var docId = getUrlParameter('tx_find_find%5Bid%5D');
        var match = [];
        match[1] = docId;
    }

    if (AU) {
        var url = location.origin + PLUGIN_PATH + match[1] + '?tx_find_find[au]=' + AU + '#tabaccess';
        $('#action-copied-info-input-' + AU).val(url);
        $('.action-copied-info-button-' + AU).show();
        $('.action-copied-info-button-' + AU +'.action-copied-success-' + AU).hide();

        $('.action-copied-info-div-' + AU).toggle();
    } else {
        var url = location.origin + PLUGIN_PATH + match[1];

        $('.action-copied-info-input-detail').val(url);
        $('.action-copied-info-button-detail').show();
        $('.action-copied-info-button-detail.action-copied-success-detail').hide();

        $('.action-copied-info-div-detail').toggle();
    }



}

function copyUrl(context) {
    var AU = context.data('auid');

    if (AU) {
        var input = document.getElementById('action-copied-info-input-' + AU);
    } else {
        var input = document.getElementById('action-copied-info-input-detail');
    }

    input.select();
    document.execCommand('copy');

    if (AU) {
        $('.action-copied-info-button-' + AU).hide();
        $('.action-copied-info-button-' + AU + '.action-copied-success-' + AU).fadeIn();

        $('.action-copied-info-' + AU).delay(2000).fadeOut();
    } else {
        $('.action-copied-info-button-detail').hide();
        $('.action-copied-info-button-detail.action-copied-success-detail').fadeIn();

        $('.action-copied-info-div-detail').delay(2000).fadeOut();
    }


}

function sendUrlByMail(title) {
    var subject= title;
    var body = "";
    body += window.location.href;
    body += "";
    var uri = "mailto:?subject=";
    uri += encodeURIComponent(decodeURI(subject));
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

// extended search
$(document).ready(function () {

    // remove all extended search values otherwise both search modes are combined
    $('.ctg-hd-search-form button').on('click', function (event) {
        resetExtendedSearch();
    });

    $('.extended-search-reset').on('click', function (event) {
        resetExtendedSearch();
    });

    var extSearch =  getUrlParameter('tx_find_find%5BextSearch%5D');

    if (extSearch) {
        $('.extended-search').show();
        $('.ctg-hd-search-form').hide();
    }

    $('.linkExtendedSearch').on('click', function (event) {
        event.preventDefault();
        toggleExtendedSearch();
        //add extended search parameter to link
    });

    // prevent enter in extended search input fields because it triggers the basic search
    $("[id^=extended-search-input-]").on('keypress', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            $('.extended-search button').click();
        }
    });

    $('.extended-search-container-button button').on('click', function (event) {

        $('.ctg-hd-search-form input').val('');

        var extendedInputs = [0,1,2,3,4];
        extendedInputs.forEach(function (item, index, array) {
            var currentSelectName = $('#extended-search-select-'+item).children('option:selected').attr('name');

            // check if duplicate options are chosen
            if ($('#extended-search-input-'+item).val().length !== 0) {

                if ($('#extended-search-select-0').children('option:selected').attr('name') == currentSelectName && item != 0) {
                    if ($('#extended-search-input-0').val().length !== 0) {
                        $('#extended-search-input-'+item).val($('#extended-search-input-'+item).val() + ' ' + $('#extended-search-input-0').val());
                        $('#extended-search-input-0').val('');
                    }
                }

                if ($('#extended-search-select-1').children('option:selected').attr('name') == currentSelectName && item != 1) {
                    if ($('#extended-search-input-1').val().length !== 0) {
                        $('#extended-search-input-'+item).val($('#extended-search-input-'+item).val() + ' ' + $('#extended-search-input-1').val());
                        $('#extended-search-input-1').val('');
                    }
                }

                if ($('#extended-search-select-2').children('option:selected').attr('name') == currentSelectName && item != 2) {
                    if ($('#extended-search-input-2').val().length !== 0) {
                        $('#extended-search-input-'+item).val($('#extended-search-input-'+item).val() + ' ' + $('#extended-search-input-2').val());
                        $('#extended-search-input-2').val('');
                    }
                }

                if ($('#extended-search-select-3').children('option:selected').attr('name') == currentSelectName && item != 3) {
                    if ($('#extended-search-input-3').val().length !== 0) {
                        $('#extended-search-input-'+item).val($('#extended-search-input-'+item).val() + ' ' + $('#extended-search-input-3').val());
                        $('#extended-search-input-3').val('');
                    }
                }

                if ($('#extended-search-select-4').children('option:selected').attr('name') == currentSelectName && item != 4) {
                    if ($('#extended-search-input-4').val().length !== 0) {
                        $('#extended-search-input-'+item).val($('#extended-search-input-'+item).val() + ' ' + $('#extended-search-input-4').val());
                        $('#extended-search-input-4').val('');
                    }
                }
            } else {
                $('#extended-search-input-'+item).attr('name', 'tx_find_find[q]['+item+']');
            }


        });
    });

    $('.extended-search-select').on('change', function (event) {
        $('#'+$(this).attr('id').replace('select','input')).attr('name', 'tx_find_find[q][' + $(this).children('option:selected').attr('name') + ']');
        if ($(this).children('option:selected').attr('name') == 'not_date') {
            $('#'+$(this).attr('id').replace('select','input')).attr('readonly', true).val("*");
        } else {
            $('#'+$(this).attr('id').replace('select','input')).attr('readonly', false);
        }
        initDatepickerExtSearch($(this));
    });

    var extendedFields = ["author",
        "author_von",
        "author_an",
        "author_ueber",
        "author_unter",
        "title",
        "title_ueber",
        "date",
        "date_von",
        "date_bis",
        "not_date",
        "new_von",
        "new_bis",
        "place",
        "numbers",
        "signatur",
        "exemplar",
        "searchall"];

    var i = 0;
    extendedFields.forEach(function (item, index, array) {
        if (value = getUrlParameter("tx_find_find%5Bq%5D%5B" + item + "%5D")) {
            $('#extended-search-select-'+i).children('[name='+item+']').prop('selected', true)
            $('#extended-search-input-'+i).val(value);
            $('#extended-search-input-'+i).attr('name', 'tx_find_find[q][' + item + ']');
            if (item == "not_date") {
                $('#extended-search-input-'+i).attr('readonly', true);
            }
            i++;
        }
    });
    if (i > 0) {
        toggleExtendedSearch();
    }

    // check if extended search is visible
    setExtendedSearchText();
});

function resetExtendedSearch() {
    var extendedInputs = [0,1,2,3,4];
    extendedInputs.forEach(function (item, index, array) {
        $('#extended-search-input-'+item).val("");
    });
}

function toggleExtendedSearch() {
    $('.extended-search').toggle();
    $('.ctg-hd-search-form').toggle();
    setExtendedSearchText();
}

function setExtendedSearchText() {
    if ($('.extended-search').is(":visible")) {
        $('.show-ext-search').text('Einfache Suche');
    } else {
        $('.show-ext-search').text('Erweiterte Suche');
    }
}

// datepicker
function initDatepickerExtSearch(context = false) {
    var datePickerConfig = {
        changeMonth: true,
        changeYear: true,
        dateFormat: "yy-mm-dd"
    };
    if (context) {
        var dateInput = $('#' + context.attr('id').replace('select','input'));
        var isDateInput = (dateInput.attr("name").substr(0, 20) === 'tx_find_find[q][date')

        if (dateInput && !dateInput.hasClass('useDatepicker') && isDateInput) {
            dateInput.datepicker(datePickerConfig);
            dateInput.addClass('useDatepicker');
        } else {
            if (dateInput.hasClass('useDatepicker') && !isDateInput) {
                dateInput.datepicker('destroy');
                dateInput.removeClass('useDatepicker');
            }
        }
    } else {
        var dateInput = $('input[name^="tx_find_find[q][date"]');
        dateInput.datepicker(datePickerConfig);
        dateInput.addClass('useDatepicker');
    }
}


// watchlist
$(document).ready(function () {
    updateCounter();
    markWatchlistButtons();

    $('body').mousedown(function(e) {
        var clicked = $(e.target);
        if (clicked.is('.watchlist-container') || clicked.parents().is('.watchlist-container')) {
            return;
        } else {
            $('.watchlist-container').hide();
        }
    });

    $('#watchlist').on('click', function (event) {
        event.preventDefault();
        buildWatchlist();
        $('.watchlist-container').toggle();

        // watchlist export csv
        $('.watchlist-export').on('click', function (event) {
            event.preventDefault();
            let csvContent = "";
            $('#watchlist-list li').each(function () {
                csvContent += '"' + location.origin + PLUGIN_PATH + $(this).find('a').data('docid') + '";' + $(this).text().replace('/;/g', '') + '\r\n';
            });

            downloadContentAsFile(csvContent, "text/csv", "marbach.csv");
        });

        // watchlist send via mail
        $('.watchlist-send').on('click', function (event) {
            //event.preventDefault();
            let mailBody = "";
            $('#watchlist-list li').each(function () {
                mailBody += location.origin + PLUGIN_PATH + $(this).find('a').data('docid') + '%20' + $(this).text() + '%0D%0A';
            });
            $(this).attr('href', 'mailto:?subject=Marbach%20Merkliste&body='+mailBody);
        });

        // print watchlist
        $('.watchlist-print').on('click', function (event) {
            var pdf = new jsPDF("p", "mm", "a4");
            var i = 0;
            var margin = 7;
            $('#watchlist-list li').each(function () {
                if ((30 + (i * margin)) > 260) {
                    pdf.addPage();
                    i = 0;
                }
                i = i + 2;
                pdf.setFontSize(16);
                var splitText = pdf.splitTextToSize($(this).text(), 160);
                pdf.text (splitText, 20, (30 + (i * margin)));

                if (splitText.length > 1) {
                    i = i + splitText.length;
                } else {
                    i++;
                }

                pdf.setFontSize(12);
                pdf.text ($(this).find('a').data('docid'), 30, (30 + (i * margin)));

            });
            pdf.save ("Merkliste.pdf");
        });

    });

    // Remove all from watchlist
    $('.watchlist-delall').on('click', function () {
        removeAllFromWatchlist();
        updateCounter();
        $('.add-watchlist-button-marked').removeClass('add-watchlist-button-marked');
    });

    // Close watchlist
    $('.watchlist-close a').on('click', function (evt) {
        evt.preventDefault();
        $('.watchlist-container').toggle();
    });

    // Add document to watchlist
    $('.add-watchlist-button').on('click', function () {
        var docId = $(this).data('docid');
        addToWatchlist(docId);
        updateCounter();
        $(this).toggleClass('add-watchlist-button-marked');
    });

});

function markWatchlistButtons() {
    if (Cookies.get('list') != undefined) {
        var list = Cookies.get('list');
        var listArray = list.split(',');
        listArray.forEach(function (item, index, array) {
            $('.add-watchlist-button[data-docid="'+item+'"]').toggleClass('add-watchlist-button-marked');
        });
    }
}

function addToWatchlist(id) {
    if (Cookies.get('list') != undefined) {
        var list = Cookies.get('list');
        if (list.search(id) == -1) {
            if (list == '') {
                list += id;
            } else {
                list += ','+id;
            }

            list = list.replace(',,', ',');
            Cookies.set('list', list);
        } else {
            removeFromWatchlist(id);
        }

    } else {
        Cookies.set('list', id);
    }
}

function removeFromWatchlist(id) {
    if (Cookies.get('list') != undefined) {
        var list = Cookies.get('list');
        list = list.replace(id, '');
        list = list.replace(',,', ',');
        list = list.replace(/^,|,$/, "");
        Cookies.set('list', list);
    }
}

function removeAllFromWatchlist() {
    if (Cookies.get('list') != undefined) {
        Cookies.set('list', '');
        $('#watchlist-list').html('');
    }
}

function updateCounter() {
    $('#watchlist .watchlist-counter').text($('#watchlist .watchlist-counter').text().replace(/\d+/, countWatchlist()));
}

function countWatchlist() {
    if (Cookies.get('list') != undefined) {
        var list = Cookies.get('list');
        if (list == "") {
            return 0;
        } else {
            return list.split(',').length;
        }
    } else {
        return 0;
    }
}

function buildWatchlist() {
    if (Cookies.get('list') != undefined) {
        var list = Cookies.get('list');

        $.ajax({
            url: "/index.php?eID=getEntities&q=" + list,
        })
            .done(function( data ) {
                $('#watchlist-list').html('');
                data.forEach(function (item, index, array) {
                    $('#watchlist-list')
                        // .append('<li><input type="checkbox" data-docid="'+data.id+'">' +
                        .append('<li>' +
                            '<span class="watchlist-delete-container">' +
                            '<a href="#" class="watchlist-delete-'+item.id+'" data-docid="'+item.id+'"><span class="bel-ende03 " style="font-size:48px;"></span></a>' +
                            '</span>' +
                            '<a href="/suche/opac/id/' + item.id + '" data-docid="'+item.id+'">' + item.listview_title + '</a>' +
                            '</li>');
                    $('.watchlist-delete-'+item.id).on('click', function (event) {
                        event.preventDefault();
                        var docId = $(this).data('docid');
                        removeFromWatchlist(docId);
                        updateCounter();
                        $(this).closest('li').remove();
                        $("a[data-docid='" + docId + "']").removeClass('add-watchlist-button-marked');
                    });
                });

            });


    } else {
        $('#watchlist-entries').html('<h2>Keine Einträge vorhanden</h2>');
    }
}