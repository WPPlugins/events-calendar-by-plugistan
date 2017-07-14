var ipenelo_calendarAdmin = function () {}
 
ipenelo_calendarAdmin.prototype = {
    options           : {},
    generateShortCode : function() {
 
        var attrs = '';
        jQuery.each(this['options'], function(name, value){
            if (value != '') {
                attrs += ' ' + name + '="' + value + '"';
            }
        });
        return '[ipenelo_calendar ' + attrs + ']';
    },
    sendToEditor      : function(f) {
        var collection = jQuery(f).find("select[id^=ipenelo_calendar], input[id^=ipenelo_calendar]:not(input:checkbox),input[id^=ipenelo_calendar]:checkbox:checked");
        var $this = this;
        collection.each(function () {
            var name = this.name.substring(17, this.name.length-1);
            $this['options'][name] = jQuery(this).val();
        });
        send_to_editor(this.generateShortCode());
        return false;
    }
}
 
var ipenelo_calendarAdmin = new ipenelo_calendarAdmin();