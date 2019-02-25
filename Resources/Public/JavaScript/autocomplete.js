
$.getScript( window.location.origin + "/typo3conf/ext/dla_opac_ng/Resources/Public/JavaScript/jquery.tokeninput.js").

done(function( script, textStatus ) {

   $(".inputType-text").tokenInput("index.php?eID=autocomplete", {
       propertyToSearch: "term",
       resultsFormatter: function(item){
           var output = "<li>" + "<div style='display: inline-block; padding-left: 10px;'><div class='normalized'>" + item.normalized + "</div>";
           if (item.term != item.normalized) {
               output += "<div class='term'>Pseudonym: " + item.term + "</div></div></li>";
           }
           output += "</li>";
           return output;
       },
       tokenFormatter: function(item) {
           return "<li><p>" + item.normalized + "</p></li>"
       }
   });
});