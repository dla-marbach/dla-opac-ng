const PLUGIN_PATH = '/find/opac/id/';
const START_PLUGIN_PATH = '/katalog-ng/';

// mobile label switch
$(document).ready(function(){
    let isMobile = window.matchMedia("only screen and (max-width: 760px)").matches;

    if (isMobile) {
        $('*[data-mobile-label]').each(function (key, value) {
            var extractHtml = $(this).html().replace($(this).text().trim(), $(this).data('mobile-label'));
            $(this).html(extractHtml);
        });
    }
});

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
    $('.button-close').on('click', function(e){
        $(this).parent().siblings('ul').toggle();
        var arrow = $(this).children('span.bel-pfeil-o01')[0];
        if (arrow) {
            $(this).children('span.bel-pfeil-o01').removeClass('bel-pfeil-o01').addClass('bel-pfeil-u01');
        } else {
            $(this).children('span.bel-pfeil-u01').removeClass('bel-pfeil-u01').addClass('bel-pfeil-o01');
        }
    });
});

// reset search
$(document).ready(function(){
    if ($('.token-input-input-token input').val() != '') {
        $('.reset-search-icon').css('display', 'inline-block');
    }
    $('.reset-search-icon').on('click', function (e) {
        e.preventDefault();
        $('.token-input-input-token input').val('');
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
            orderurl = orderurl.replace("%name%", escape(nameValue));
            orderurl = orderurl.replace("%pw%", pwValue);

            $('.order-overlay-button').prop('disabled', true);
            $('#loading').addClass('lb-cancel');
            $.ajax({
                url: orderurl,
            })
            .done(function( data ) {
                $('.order-overlay .login-form').hide();
                $('.order-overlay .info-no-account').hide();
                var responseRoot = $(data);
                var responseMessageNode = responseRoot.find("#meldung").addBack("#meldung").first();
                var responseMessage = responseMessageNode.text().trim();

                if (!responseMessageNode.length || !responseMessage) {
                    var responseHeading = responseRoot.find('h1').first().text().trim();
                    var fallbackMessage = responseHeading || 'Die Bestellung konnte nicht verarbeitet werden. Bitte versuchen Sie es spaeter erneut.';

                    $('.order-overlay span.error').text(fallbackMessage);
                    $('.order-overlay span.error').show();
                } else if (responseMessage.indexOf('Leihschein') !== -1 && responseMessage.indexOf('gedruckt') !== -1) {
                    var filterTypeValues = $('.field-filterType_mv').text().toLowerCase();
                    var infoShown = false;
                    var hasType = function(typeLabel) {
                        return filterTypeValues.indexOf(typeLabel) !== -1;
                    };

                    $('.order-overlay .info span').hide();
                    if (hasType('bilder und objekte')) {
                        $('.order-overlay .info .order-info-bo').show();
                        infoShown = true;
                    } else if (hasType('gedrucktes')) {
                        $('.order-overlay .info .order-info-print').show();
                        infoShown = true;
                    } else if (hasType('handschriften')) {
                        $('.order-overlay .info .order-info-hs').show();
                        infoShown = true;
                    } else if (hasType('audio') || hasType('video')) {
                        $('.order-overlay .info .order-info-av').show();
                        infoShown = true;
                    }

                    if (!infoShown) {
                        $('.order-overlay .info .order-info-generic').show();
                    }
                    $('.order-overlay .info').show();
                } else {
                    $('.order-overlay span.error').text(responseMessage);
                    $('.order-overlay span.error').show();
                }
                $('.order-overlay .confirm').show().on("click", function (event) {
                    event.preventDefault();
                    $('.order-overlay').hide();
                    $('.order-overlay .info span').hide();
                });
                $('.order-overlay-button').prop('disabled', false);

            })
            .fail(function() {
                sendOrderAsPopup(orderurl);
                $('.order-overlay-button').prop('disabled', false);
            });


        }
    });

    $('.close-button').click(function (event) {
        event.preventDefault();
        $(this).parent().toggle();
    });

    // Hide details only if more than one exists (exemplar)
    if ($('.copy-details').length > 1) {
        $('.copy-details').hide();
    } else {
        $('.detail_link').hide();
    }

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

    var docId = getUrlParameter('tx_find_find[au]') || getUrlParameter('tx_find_find%5Bau%5D');
    if (docId) {
        var docIdCandidates = [docId];

        if (/^AU/i.test(docId)) {
            docIdCandidates.push(docId.replace(/^AU/i, ''));
        } else {
            docIdCandidates.push('AU' + docId);
        }

        var row = $();
        $.each(docIdCandidates, function (_, candidate) {
            row = $('.row-' + candidate);
            if (row.length) {
                return false;
            }
        });

        if (row.length) {
            row.addClass('au-highlighting');
            row.next().addClass('au-highlighting');
            //row.find('.detail_link').click();
        }
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

function copyUrlAction(context) {
    var location = window.location;
    var AU = context.data('auid');

    // Prefer routed detail URLs like /find/opac/id/{docId} (and language-prefixed variants).
    var detailMatch = location.pathname.match(/^(.*\/opac\/id\/)([^/?#]+)/);
    var detailBasePath = PLUGIN_PATH;
    var docId;

    if (detailMatch) {
        detailBasePath = detailMatch[1];
        docId = detailMatch[2];
    } else {
        docId = getUrlParameter('tx_find_find[id]') || getUrlParameter('tx_find_find%5Bid%5D');
    }

    if (!docId) {
        return;
    }

    if (AU) {
        var url = location.origin + detailBasePath + docId + '?tx_find_find[au]=' + AU + '#tabaccess';
        $('#action-copied-info-input-' + AU).val(url);
        $('.action-copied-info-button-' + AU).show();
        $('.action-copied-info-button-' + AU +'.action-copied-success-' + AU).hide();

        $('.action-copied-info-div-' + AU).toggle();
    } else {
        var url = location.origin + detailBasePath + docId;

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
        normalizedParam = decodeURIComponent(String(sParam).replace(/\+/g, ' ')),
        sParameterName,
        rawKey,
        decodedKey,
        rawValue,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        rawKey = sParameterName[0] || '';
        decodedKey = decodeURIComponent(rawKey.replace(/\+/g, ' '));

        if (rawKey === sParam || decodedKey === normalizedParam) {
            rawValue = sParameterName.slice(1).join('=');

            return rawValue === '' || rawValue === undefined
                ? true
                : decodeURIComponent(rawValue.replace(/\+/g, ' '));
        }
    }
};

function setExtendedSearchInputState(enabled) {
    $('.extended-search input, .extended-search select').prop('disabled', !enabled);
}

// extended search
$(document).ready(function () {

    setExtendedSearchInputState(false);

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
        setExtendedSearchInputState(true);
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
            $('.extended-search-container-button button[type="submit"]').trigger('click');
        }
    });

    $('.extended-search-container-button button[type="submit"]').on('click', function (event) {

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
                // Keep empty extended fields out of the request entirely.
                $('#extended-search-input-'+item).attr('name', '');
            }


        });
    });

    $('.extended-search-select').on('change', function (event) {
        $('#'+$(this).attr('id').replace('select','input')).attr('name', 'tx_find_find[q][' + $(this).children('option:selected').attr('name') + ']');
        $('#'+$(this).attr('id').replace('select','input')).attr('readonly', false);
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
        "id",
        "classification",
        "signatur",
        "exemplar",
        "searchall"];

    // find existing parameters and refill ext search inputs
    var i = 0;
    extendedFields.forEach(function (item, index, array) {
        if (value = getUrlParameter("tx_find_find%5Bq%5D%5B" + item + "%5D")) {
            if (item == "not_date") {
                $('#extended-search-checkbox-no-date').prop('checked', true);
            } else {
                $('#extended-search-select-'+i).children('[name='+item+']').prop('selected', true)
                $('#extended-search-input-'+i).val(value);
                $('#extended-search-input-'+i).attr('name', 'tx_find_find[q][' + item + ']');
                i++;
            }
        }
    });
    if (i > 0 && !$('.extended-search').is(":visible")) {
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
    $('#extended-search-checkbox-no-date').prop('checked', false);
}

function toggleExtendedSearch() {
    var showExtended = !$('.extended-search').is(":visible");
    $('.extended-search').toggle();
    $('.ctg-hd-search-form').toggle();
    setExtendedSearchInputState(showExtended);
    setExtendedSearchText();
}

function setExtendedSearchText() {
    if ($('.extended-search').is(":visible")) {
        $('.show-ext-search').text('Einfache Suche');
        $('.show-ext-search').text($('.show-ext-search').data('search'));
    } else {
        $('.show-ext-search').text('Erweiterte Suche');
        $('.show-ext-search').text($('.show-ext-search').data('extendedsearch'));
    }
}

// datepicker
function initDatepickerExtSearch(context = false) {
    var datePickerConfig = {
        timepicker: false,
        allowBlank: true,
        validateOnBlur: false,
        format: "Y-m-d",
        onClose: function (ct, $i) {

        }
    };
    if (context) {
        var dateInput = $('#' + context.attr('id').replace('select','input'));
        var isDateInput = (dateInput.attr("name").substr(0, 20) === 'tx_find_find[q][date')

        if (dateInput && !dateInput.hasClass('useDatepicker') && isDateInput) {
            dateInput.datetimepicker(datePickerConfig);
            dateInput.addClass('useDatepicker');
        } else {
            if (dateInput.hasClass('useDatepicker') && !isDateInput) {
                dateInput.datetimepicker('destroy');
                dateInput.removeClass('useDatepicker');
            }
        }
    } else {
        var dateInput = $('input[name^="tx_find_find[q][date"]');
        dateInput.datetimepicker(datePickerConfig);
        dateInput.addClass('useDatepicker');
    }
}


// Shared flyout element for dataservice format selection
var $dataserviceFlyout = null;
var dataserviceUrlBuilder = null;   // (format) → URL string
var dataserviceFilenameBase = null; // base filename without extension

// Cache for formats loaded from openapi.json, keyed by base URL
var dataserviceFormatsCache = {};
var DATASERVICE_FALLBACK_FORMATS = [
    { id: 'json',  label: 'JSON'  },
    { id: 'jsonl', label: 'JSONL' },
    { id: 'ris',   label: 'RIS'   },
    { id: 'mods',  label: 'MODS'  },
    { id: 'dc',    label: 'DC'    },
    { id: 'tsv',       label: 'TSV (alle Felder)'    },
    { id: 'tsv-light', label: 'TSV (Standardfelder)' }
];

// Loads the available export formats from the dataservice openapi.json
function loadDataserviceFormats(baseUrl, callback) {
    if (dataserviceFormatsCache[baseUrl]) {
        callback(dataserviceFormatsCache[baseUrl]);
        return;
    }

    // Temporarily disabled because requesting openapi.json can fail with CORS.
    // Keep this block for later reactivation when CORS is fixed.
    //
    // $.getJSON(baseUrl + '/openapi.json', function (spec) {
    //     var formats = [];
    //     try {
    //         var params = spec.paths['/records'].get.parameters;
    //         $.each(params, function (i, param) {
    //             if (param.name === 'format' && param.schema && param.schema.enum) {
    //                 $.each(param.schema.enum, function (j, fmt) {
    //                     formats.push({ id: fmt, label: fmt.toUpperCase() });
    //                 });
    //             }
    //         });
    //     } catch (e) {}
    //     dataserviceFormatsCache[baseUrl] = formats.length ? formats : DATASERVICE_FALLBACK_FORMATS;
    //     callback(dataserviceFormatsCache[baseUrl]);
    // }).fail(function () {
    //     dataserviceFormatsCache[baseUrl] = DATASERVICE_FALLBACK_FORMATS;
    //     callback(dataserviceFormatsCache[baseUrl]);
    // });

    dataserviceFormatsCache[baseUrl] = DATASERVICE_FALLBACK_FORMATS;
    callback(dataserviceFormatsCache[baseUrl]);
}


function initDataserviceFlyout() {
    $dataserviceFlyout = $('<div class="dataservice-format-flyout"><div class="dataservice-flyout-arrow"></div></div>');
    $('body').append($dataserviceFlyout);

    $dataserviceFlyout.on('click', '.dataservice-format-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (dataserviceUrlBuilder) {
            var format = $(this).data('format');
            var filenameMap = { 'tsv-light': dataserviceFilenameBase + '-standardfelder.tsv' };
            var filename = filenameMap[format] || (dataserviceFilenameBase + '.' + format);
            downloadDataserviceFile(dataserviceUrlBuilder(format), filename);
        }
        $dataserviceFlyout.removeClass('active');
    });

    $dataserviceFlyout.on('click', '.dataservice-copy-btn', function (e) {
        e.stopPropagation();
    });

    $(document).on('click.dataserviceFlyout', function (e) {
        if (!$(e.target).closest('.dataservice-format-flyout, .watchlist-export-dataservice, .dataservice-search-download, .dataservice-detail-download').length) {
            $dataserviceFlyout.removeClass('active');
        }
    });
}


// alignRight: if true, the flyout right edge aligns with the button right edge (opens to the left)
function showDataserviceFlyout($button, baseUrl, urlBuilder, filenameBase, recordCount, alignRight) {
    if (!$dataserviceFlyout) {
        initDataserviceFlyout();
    }
    dataserviceUrlBuilder = urlBuilder;
    dataserviceFilenameBase = filenameBase;
    var downloadHint = $.trim($button.data('download-hint') || '');
    var moreInfoText = $.trim($button.data('more-info-text') || '');
    var infoUrl      = $.trim($button.data('info-url')  || '');
    loadDataserviceFormats(baseUrl, function (formats) {
        $dataserviceFlyout.find('.dataservice-flyout-hint, .dataservice-format-row, .dataservice-flyout-info').remove();

        if (downloadHint) {
            var hintText = downloadHint.replace('%d', recordCount || 0);
            $('<p class="dataservice-flyout-hint"></p>').text(hintText).appendTo($dataserviceFlyout);
        }

        $.each(formats, function (i, fmt) {
            var $row = $('<div class="dataservice-format-row"></div>');
            $('<button type="button" class="dataservice-format-btn"></button>')
                .text(fmt.label)
                .data('format', fmt.id)
                .appendTo($row);
            $('<a class="dataservice-copy-btn" title="URL öffnen" target="_blank" rel="noopener noreferrer"></a>')
                .attr('href', urlBuilder(fmt.id))
                .append($('<span class="icon bel-verbinden"></span>'))
                .appendTo($row);
            $row.appendTo($dataserviceFlyout);
        });

        if (moreInfoText || infoUrl) {
            var $info = $('<div class="dataservice-flyout-info"></div>');
            if (moreInfoText) {
                $info.append(document.createTextNode(moreInfoText));
            }
            if (infoUrl) {
                var $link = $('<a class="dataservice-flyout-info-link" target="_blank" rel="noopener noreferrer"></a>')
                    .attr('href', infoUrl)
                    .attr('title', 'Datendienst');
                $link.append($('<span class="icon bel-info"></span>'));
                if (moreInfoText) {
                    $info.append(' ');
                }
                $info.append($link);
            }
            $dataserviceFlyout.append($info);
        }
        $dataserviceFlyout.addClass('active');

        var offset   = $button.offset();
        var buttonW  = $button.outerWidth();
        var flyoutW  = $dataserviceFlyout.outerWidth();
        var top      = offset.top + $button.outerHeight() + 2;
        var left     = alignRight ? offset.left + buttonW - flyoutW : offset.left;

        // Position the arrow so it points at the horizontal centre of the trigger button
        var arrowLeft = (offset.left + buttonW / 2) - left - 9;
        arrowLeft = Math.max(8, Math.min(arrowLeft, flyoutW - 26));

        $dataserviceFlyout.css({ top: top, left: left });
        $dataserviceFlyout.find('.dataservice-flyout-arrow').css('left', arrowLeft + 'px');
    });
}

// Downloads a dataservice URL as a file via streaming fetch so the browser shows
// a progress toast immediately. Falls back to window.open() on CORS errors.
function downloadDataserviceFile(url, filename) {
    var $toast = $('<div class="dla-download-toast"><span class="dla-download-toast-label">Download wird vorbereitet…</span></div>');
    $('body').append($toast);

    fetch(url)
        .then(function (response) {
            if (response.status === 204) {
                $toast.find('.dla-download-toast-label').text('Für diese Anfrage sind keine Daten vorhanden.');
                setTimeout(function () { $toast.remove(); }, 4000);
                return null;
            }
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            var disposition = response.headers.get('Content-Disposition');
            if (disposition) {
                var match = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                if (match && match[1]) {
                    filename = match[1].replace(/['"]/g, '');
                }
            }
            var total = parseInt(response.headers.get('Content-Length') || '0', 10);
            var loaded = 0;
            var chunks = [];
            var reader = response.body.getReader();

            function pump() {
                return reader.read().then(function (result) {
                    if (result.done) { return; }
                    chunks.push(result.value);
                    loaded += result.value.length;
                    var label = total > 0
                        ? Math.round((loaded / total) * 100) + ' % — ' + formatDownloadBytes(loaded) + ' / ' + formatDownloadBytes(total)
                        : formatDownloadBytes(loaded) + ' geladen…';
                    $toast.find('.dla-download-toast-label').text(label);
                    return pump();
                });
            }

            return pump().then(function () { return new Blob(chunks); });
        })
        .then(function (blob) {
            if (!blob) { return; }
            $toast.remove();
            var objectUrl = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = objectUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(objectUrl);
        })
        .catch(function () {
            $toast.remove();
            window.open(url, '_blank');
        });
}

function formatDownloadBytes(bytes) {
    if (bytes < 1024) { return bytes + ' B'; }
    if (bytes < 1048576) { return (bytes / 1024).toFixed(1) + ' KB'; }
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function getDataserviceFormatFilterQuery(format) {
    var formatKey = String(format || '').toLowerCase();
    var formatFilterMap = {
        dc: 'exportDC:*',
        mods: 'exportMODS:*',
        ris: 'exportRIS:*'
    };

    return formatFilterMap[formatKey] || null;
}

function buildDataserviceRecordsUrl(baseUrl, query, format) {
    var url = baseUrl + '/v1/records?q=' + encodeURIComponent(query) + '&format=' + encodeURIComponent(format);
    var additionalFilters = (typeof DLA_FACETS !== 'undefined' && DLA_FACETS && Array.isArray(DLA_FACETS.additionalFilters))
        ? DLA_FACETS.additionalFilters
        : [];

    $.each(additionalFilters, function (_, filterQuery) {
        if (filterQuery) {
            url += '&fq=' + encodeURIComponent(filterQuery);
        }
    });

    var formatFilterQuery = getDataserviceFormatFilterQuery(format);
    if (formatFilterQuery) {
        url += '&fq=' + encodeURIComponent(formatFilterQuery);
    }

    return url;
}


// Builds a Solr query for the dataservice from the current URL parameters.
// Includes search query fields (tx_find_find[q][field]) and
// active facets (tx_find_find[facet][ID][Term]).
// Both queryField templates and facet field mappings are resolved via DLA_QUERY_FIELDS
// and DLA_FACETS, which are injected from TypoScript settings on the page.
function buildDataserviceQuery() {
    var params = new URLSearchParams(window.location.search);
    var queryParts = [];
    var arrayQueryFields = {}; // { fieldId: { index: value } } for tx_find_find[q][field][N]=value
    var queryFields = (typeof DLA_QUERY_FIELDS !== 'undefined') ? DLA_QUERY_FIELDS : [];

    params.forEach(function (value, key) {
        // Array search query fields: tx_find_find[q][field][N]=value
        // Used by hidden queryFields with multiple placeholders (e.g. detail_weitere_handschriften).
        var qArrayMatch = key.match(/^tx_find_find\[q\]\[([^\]]+)\]\[(\d+)\]$/);
        if (qArrayMatch && value) {
            var fieldId = qArrayMatch[1];
            var idx = parseInt(qArrayMatch[2], 10);
            if (!arrayQueryFields[fieldId]) {
                arrayQueryFields[fieldId] = {};
            }
            arrayQueryFields[fieldId][idx] = value;
            return;
        }

        // Simple search query fields: tx_find_find[q][field]=value
        var qMatch = key.match(/^tx_find_find\[q\]\[([^\]]+)\]$/);
        if (qMatch && value) {
            var simpleFieldId = qMatch[1];
            var simpleQueryField = queryFields.find(function (qf) { return qf.id === simpleFieldId; });
            if (simpleQueryField && simpleQueryField.query) {
                if (simpleQueryField.query.indexOf('%1$s') !== -1) {
                    // Template with indexed placeholder: substitute value everywhere it appears.
                    var escapedValue = value;
                    var resolved = simpleQueryField.query.replace(/%1\$s/g, function () { return escapedValue; });
                    // Only use resolved query if no further unresolved placeholders remain
                    // (%2$s etc. would be left if the field requires multiple values).
                    if (!resolved.match(/%\d+\$s/)) {
                        queryParts.push(resolved);
                    } else {
                        queryParts.push(simpleFieldId + ':' + value);
                    }
                } else if (simpleQueryField.query.indexOf('%s') === -1) {
                    // Fixed query without any placeholder (e.g. not_date checkbox): use as-is.
                    queryParts.push(simpleQueryField.query);
                } else {
                    // find-extension format (query = %s). If the value is already a Solr field:value
                    // expression (e.g. searchEntity_id_mv:PE00003793 from Normdaten entity selection),
                    // pass it directly instead of wrapping it as default:searchEntity_id_mv:...
                    if (/^[a-z][a-zA-Z0-9_]*:/.test(value)) {
                        queryParts.push(value);
                    } else {
                        queryParts.push(simpleFieldId + ':' + value);
                    }
                }
            } else {
                queryParts.push(simpleFieldId + ':' + value);
            }
            return;
        }

        // Active facets: tx_find_find[facet][ID][Term]=1
        var fMatch = key.match(/^tx_find_find\[facet\]\[([^\]]+)\]\[([^\]]+)\]$/);
        if (!fMatch) { return; }

        var facetId   = fMatch[1];
        var facetTerm = fMatch[2];
        var facets = (typeof DLA_FACETS !== 'undefined') ? DLA_FACETS : { fieldMap: {}, queryMap: {}, fixedMap: {} };

        // Fixed-query facets (e.g. Digital switch): query is independent of the term value.
        if (facets.fixedMap[facetId]) {
            queryParts.push(facets.fixedMap[facetId]);
            return;
        }

        // facetQuery facets (e.g. NeuImKatalog): term selects a predefined query string.
        if (facets.queryMap[facetId] && facets.queryMap[facetId][facetTerm]) {
            queryParts.push(facets.queryMap[facetId][facetTerm]);
            return;
        }

        // Standard field facets: map facet ID to Solr field name.
        var field = facets.fieldMap[facetId];
        if (field) {
            queryParts.push(field + ':("' + facetTerm + '")');
        }
    });

    // Resolve array query fields by substituting values into TypoScript query templates.
    // Placeholders %1$s, %2$s, ... correspond to array indices 0, 1, ...
    Object.keys(arrayQueryFields).forEach(function (fieldId) {
        var values = arrayQueryFields[fieldId];
        var queryField = queryFields.find(function (qf) { return qf.id === fieldId; });
        if (queryField && queryField.query) {
            var resolved = queryField.query;
            Object.keys(values).sort(function (a, b) { return a - b; }).forEach(function (idx) {
                var placeholder = new RegExp('%' + (parseInt(idx, 10) + 1) + '\\$s', 'g');
                var replacement = values[idx];
                resolved = resolved.replace(placeholder, function () { return replacement; });
            });
            queryParts.push(resolved);
        } else {
            // No template found: fall back to value at index 0
            var firstIdx = Object.keys(values).sort(function (a, b) { return a - b; })[0];
            queryParts.push(fieldId + ':' + values[firstIdx]);
        }
    });

    return queryParts.join(' AND ');
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

            var url = location.origin + START_PLUGIN_PATH + '?tx_dlaopacng_dlastart%5Bcontroller%5D=Export&tx_dlaopacng_dlastart%5Baction%5D=csv&tx_dlaopacng_dlastart%5Bids%5D=';
            $('#watchlist-list li').each(function () {
                url += $(this).find('a').data('docid') + ',';
            });
            
            window.open(url, '_blank').focus();
        });




        // watchlist send via mail
        // $('.watchlist-send').on('click', function (event) {
        //     //event.preventDefault();
        //     let mailBody = "";
        //     $('#watchlist-list li').each(function () {
        //         mailBody += location.origin + PLUGIN_PATH + $(this).find('a').data('docid') + '%20' + $(this).text().replaceAll('&', '%26') + '%0D%0A';
        //     });
        //     $(this).attr('href', 'mailto:?subject=Marbach%20Merkliste&body='+mailBody);
        // });

    });

    // watchlist export csv
    $(document).on('click', '.watchlist-export', function (event) {
        event.preventDefault();
        var exportBaseUrl = $(this).attr('href') || $(this).data('export-url');
        var url = new URL(window.location.origin + window.location.pathname);

        if (exportBaseUrl) {
            var exportUrl = new URL(exportBaseUrl, window.location.origin);
            exportUrl.searchParams.forEach(function (value, key) {
                url.searchParams.set(key, value);
            });
        }

        var win = window.open(url.toString(), '_blank');
        if (win) {
            win.focus();
        }
        return false;
    });

    // watchlist export via dataservice (format selection)
    $(document).on('click', '.watchlist-export-dataservice', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var dataserviceUrl = $(this).data('dataservice-url');
        if (!dataserviceUrl) {
            return;
        }
        var ids = [];
        $('#watchlist-list li').each(function () {
            var id = $(this).find('a').data('docid');
            if (id) {
                ids.push(id);
            }
        });
        if (ids.length === 0) {
            return;
        }
        var idQuery = ids.map(function (id) { return 'id:' + id; }).join(' OR ');
        var baseUrl = dataserviceUrl.replace(/\/$/, '');
        showDataserviceFlyout($(this), baseUrl, function (format) {
            return buildDataserviceRecordsUrl(baseUrl, idQuery, format);
        }, 'merkliste', ids.length);
    });

    // Download single record via dataservice (detail page)
    $(document).on('click', '.dataservice-detail-download', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var dataserviceUrl = $(this).data('dataservice-url');
        var docId = $(this).data('docid');
        if (!dataserviceUrl || !docId) {
            return;
        }
        var baseUrl = dataserviceUrl.replace(/\/$/, '');
        showDataserviceFlyout($(this), baseUrl, function (format) {
            return buildDataserviceRecordsUrl(baseUrl, 'id:' + docId, format);
        }, docId, 1, true);
    });

    // Download search results via dataservice (format selection)
    $(document).on('click', '.dataservice-search-download', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var dataserviceUrl = $(this).data('dataservice-url');
        if (!dataserviceUrl) {
            return;
        }
        var query = buildDataserviceQuery() || '*:*';
        var numFound = parseInt($(this).data('numfound'), 10) || 0;
        var baseUrl = dataserviceUrl.replace(/\/$/, '');
        showDataserviceFlyout($(this), baseUrl, function (format) {
            return buildDataserviceRecordsUrl(baseUrl, query, format);
        }, 'trefferliste', numFound, true);
    });

    // print watchlist
    $(document).on('click', '.watchlist-print', function (event) {
        event.preventDefault();
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

            pdf.setFontSize(12);
            pdf.text ($(this).find('a.watchlist-entry').data('link'), 30, (35 + (i * margin)));

        });
        pdf.save ("Merkliste.pdf");
        return false;
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
    $('.watchlist-counter').text($('.watchlist-counter').text().replace(/\d+/, countWatchlist()));
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
        var url = new URL(window.location.origin + window.location.pathname);
        url.searchParams.set('getEntities', '1');
        url.searchParams.set('q', list);

        $.ajax({
            url: url.toString(),
        })
            .done(function( data ) {
                $('#watchlist-list').html('');
                data.forEach(function (item, index, array) {
                    var locationpath = PLUGIN_PATH + item.id;
                    var fulllocation = window.location.origin + locationpath;
                    $('#watchlist-list')
                        // .append('<li><input type="checkbox" data-docid="'+data.id+'">' +
                        .append('<li>' +
                            '<span class="watchlist-delete-container">' +
                            '<a href="#" class="watchlist-delete-'+item.id+'" data-docid="'+item.id+'"><span class="bel-ende03 " style="font-size:48px;"></span></a>' +
                            '</span>' +
                            '<a class="watchlist-entry" href="' + locationpath + '" data-docid="'+item.id+'" data-link="' + fulllocation + '">' + item.title + '</a>' +
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