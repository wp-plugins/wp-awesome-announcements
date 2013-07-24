jQuery(document).ready(function($) {
    
    /* Backend Scripts */
    if($('#sap_start_date').length){
        $(function(){
            var pickerOpts = {
                dateFormat: "yy-mm-dd"
            };	
            jQuery("#sap_start_date").datepicker(pickerOpts);
            jQuery("#sap_end_date").datepicker(pickerOpts);
        });

    }

    /* Frontend Scripts */
    if($('#announcements').length){    
        if($.cookie('sap_active') == 'false') {
            $("#announcements").hide();
        };
        $("#close").click(function() {
            $("#announcements").slideUp("normal");
            $.cookie('sap_active', 'false', { expires: 2, path: '/'});
            return false;
        });
        $("body").prepend($("#announcements"));
        $('#announcements .sap_message').cycle('fade');
    }
});