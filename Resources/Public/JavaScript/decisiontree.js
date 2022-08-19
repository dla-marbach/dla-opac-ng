$( document ).ready(function() {

    // "personen" aus data auslesen!?
    //var activeFacetParameter = 'tx_find_find[facet][personen]';

    // facet_names_relations from data-attribute
    //var field1Parameter = 'tx_find_find[facet][facet_names_relations]';

    //var roleFacetParameter = 'tx_find_find[facet][facet_names_roles]';

    $('.decisiontree').click(function(event) {
        event.preventDefault();
        var currentElement = $(this);

        var fieldOriginParameter = 'tx_find_find[facet][' + currentElement.data('fieldOrigin') + ']';
        var field1 = currentElement.data('field1');
        var field2 = currentElement.data('field2');
        var field1Parameter = 'tx_find_find[facet][' + field1 + ']';
        var field2Parameter = 'tx_find_find[facet][' + field2 + ']';

        if (currentElement.parent("div").children('ul.rolerelation').length == 0) {
            $.ajax({
                url: $(this).data("url"),
                dataType: "json",
            }).done(function(data) {
                currentElement.parent("div").append('<ul class="rolerelation"></ul>');
                var listElement = currentElement.parent("div").children(".rolerelation");

                var name = currentElement.children("a.decisiontree").text();
                var counter = currentElement.children("em").text();
                var urlAll = currentElement.parent("div").children("a.link-action-all").prop("href");
                var url = new URL(window.location.href);

                // check if facet is active and set activeFacet class
                if (url.searchParams.get(fieldOriginParameter + "["+name+"]") == 1) {
                    listElement.append('<li><a href="' + urlAll + '" data-completefacet="' + this + '" class="activeFacet"><span class="icon bel-ok01"></span>' + 'Alle' + '<em>' + counter + '</em></a></li>');
                } else {
                    listElement.append('<li><a href="' + urlAll + '" data-completefacet="' + this + '"><span class="icon bel-kreis01"></span>' + 'Alle' + '<em>' + counter + '</em></a></li>');
                }

                $(data).each(function(a) {
                    var items = $(this);
                    items.each(function (index) {
                        if (index%2 == 0) {
                            if (typeof data !== "undefined") {
                                output = this.split('␝');

                                var active = false;

                                var url = new URL(window.location.href);

                                // remove chash
                                url.searchParams.delete("cHash");

                                var location = window.location;
                                var linkUrl = location.origin + location.pathname + location.search;

                                if (a == 0) {
                                    var c = url.searchParams.get(field1Parameter + "["+this+"]");

                                    if (c != 1) {
                                        // relations
                                        // var addParameter = "&tx_find_find%5Bfacet%5D%5Bfacet_names_relations%5D%5B" + encodeURI(this) + "%5D=1";
                                        var addParameter = '&' + encodeURI(field1Parameter + '[' + this + ']=1');

                                        linkUrl = linkUrl + addParameter;
                                    } else {
                                        url.searchParams.delete(field1Parameter + "["+this+"]");
                                        active = true;
                                    }


                                } else if (a == 1){
                                    var c = url.searchParams.get(field2Parameter + "["+this+"]");

                                    if (c != 1) {
                                        // roles
                                        // var addParameter = "&tx_find_find%5Bfacet%5D%5Bfacet_names_roles%5D%5B" + encodeURI(this) + "%5D=1";
                                        var addParameter = '&' + encodeURI(field2Parameter + '[' + this + ']=1');

                                        linkUrl = linkUrl + addParameter;
                                    } else {

                                        url.searchParams.delete(field2Parameter + "["+this+"]");
                                        active = true;
                                    }

                                    if (index == 0) {
                                        listElement.append('<hr>');
                                    }
                                }

                                if (active) {
                                    listElement.append('<li>' +
                                        '<a href="' + url + '" data-completefacet="' + this + '" class="activeFacet">' +
                                        '<span class="icon bel-ok01"></span>' +
                                        '</a>' +
                                        '<a href="' + url + '" data-completefacet="' + this + '" class="activeFacet">' +
                                        '' + output[1] + '<em>(' + items[index+1] + ')</em>' +
                                        '</a></li>');
                                } else {
                                    listElement.append('<li>' +
                                        '<a href="' + linkUrl + '" data-completefacet="' + this + '">' +
                                        '<span class="icon bel-kreis01"></span>' +
                                        '</a>' +
                                        '<a href="' + linkUrl + '" data-completefacet="' + this + '">' +
                                        '' + output[1] + '<em>(' + items[index+1] + ')</em>' +
                                        '</a></li>');
                                }

                            }
                        } else {

                        }
                    });
                });
            });
        } else {
            $(this).siblings('.rolerelation').toggle();
        }
    });
});