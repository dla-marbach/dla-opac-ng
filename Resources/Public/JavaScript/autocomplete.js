
$.getScript( window.location.origin + "/typo3conf/ext/dla_opac_ng/Resources/Public/JavaScript/jquery.tokeninput.js").

done(function( script, textStatus ) {

   $(".inputType-text").tokenInput("index.php?eID=autocomplete", {
       propertyToSearch: "term",
       resultsFormatter: function(item){
           if (item.term != item.normalized) {
               var output = '<li class="autocomplete-list-li">' +
                   "<div style='display: inline-block; padding-left: 30px;'><div class='normalized'>" + item.term + " → siehe " + item.normalized + " </div>";

               // output += "<div class='term'>Pseudonym: " + item.term + "</div></div></li>";
           } else {
               var output = '<li class="autocomplete-list-li">' + "<div style='display: inline-block; padding-left: 10px;'><div class='normalized'>" + item.normalized + "</div>";
           }
           output += "</li>";
           return output;
       },
       tokenFormatter: function(item) {
           return "<li><p>" + item.normalized + "</p></li>"
       }
   });
});