/**
 * Stellt die in jquery-save.js gemerkte jQuery-Instanz wieder her.
 *
 * Bindet die Website im Footer ein eigenes jQuery ein (page.includeJSFooterlibs),
 * überschreibt es window.jQuery und window.$. Die Skripte der Extension und die
 * Inline-Skripte in den Templates würden dann mit diesem jQuery laufen, an dem
 * weder jQuery UI noch die Plugins der Extension hängen. Deshalb wird dieses
 * Skript als erstes Footer-Skript (forceOnTop) geladen.
 */
(function (window) {
    var ownJQuery = window.dlaOpacNg && window.dlaOpacNg.jQuery;
    if (ownJQuery && window.jQuery !== ownJQuery) {
        window.jQuery = window.$ = ownJQuery;
    }
})(window);
