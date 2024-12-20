

$( document ).ready(function() {
        activateNormdataAutocomplete();
});

function activateNormdataAutocomplete() {

    $( document ).ready(function() {

        $(".inputType-text").tokenInput("/?autocomplete=1", {
            propertyToSearch: "term",
            resultsFormatter: function(item){
                // if (item.term != item.normalized && item.term != item.id) {
                //     var output = '<li class="autocomplete-list-li">' +
                //         "<div style='display: inline-block; padding-left: 30px;'><div class='normalized'>" + item.term + " → siehe " + item.normalized + " </div>";
                //
                //     // output += "<div class='term'>Pseudonym: " + item.term + "</div></div></li>";
                // } else {
                if (item.autocomplete == '1') {
                    var output = '<li class="autocomplete-list-li">' + "<div style='display: inline-block; padding-left: 10px;'><div class='normalized'>" + item.normalized + "</div>";
                } else {
                    var output = '<li class="autocomplete-list-li normdata-autocomplete">' + "<div style='display: inline-block; padding-left: 10px;'><div class='normalized'>" + item.normalized + "</div>";
                }

                // }
                output += "</li>";
                return output;
            },
            tokenFormatter: function(item) {
                return "<li><p>" + item.normalized + "</p></li>"
            }
        });
    });
}

function createCookie(name, value, days) {
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ')
            c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0)
            return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}