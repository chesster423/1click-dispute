var auto_selector = {
    init : function() {
        this.getReasons();
        this.getInstructions();
        this.prependSideMenu();
    },
    properties : {
        reasons: [],
        instructions : [],
    },
    getReasons : function() {
        $("#ah_reason_0_cmb option").each(function()
        {
            let item_value = $(this).val();
            let item_text = $(this).text();
        
            let item = {
                value : item_value,
                text : item_text
            }
            auto_selector.properties.reasons.push(item);
        });
    },
    getInstructions : function() {
        $("#ah_explanation_0_dd option").each(function()
        {
            let item_value = $(this).val();
            let item_text = $(this).text();

            let item = {
                value : item_value,
                text : item_text
            }
            auto_selector.properties.instructions.push(item);
        });

    },
    reasonOptions : function() {
        var reason_options = "";

        $.each(auto_selector.properties.reasons, function( key, val ) {
            let option = "<option value="+auto_selector.properties.reasons[key].value+">"+auto_selector.properties.reasons[key].text+"</option>";
            reason_options = reason_options.concat(option);
        });

        return reason_options;
    },
    instructionOptions: function() {
        var instruction_options = "";

        $.each(auto_selector.properties.instructions, function( key, val ) {
            let option = "<option value='"+auto_selector.properties.instructions[key].value+"'>"+auto_selector.properties.instructions[key].text+"</option>";
            instruction_options = instruction_options.concat(option);
        });

        return instruction_options;
    },
    prependSideMenu : function() {

        $('body').prepend(
            '<div class="side-menu-selector" style="position: fixed;right: 5%; top: 15%; width: 300px;height: 250px; background: #f2f2f2; border-radius: 5px 10px; overflow: hidden;z-index: 999;">'+
                '<div style="background: linear-gradient(to bottom,#0077cf 0,#004e97 100%); height: 28px; border-top-left-radius: 5px 10px; border-top-right-radius: 5px 10px;">'+
                    '<label style="color: #FFF; padding: 6px; font-weight: bold; display: block;">Use Auto Selector</label>'+
                '</div>'+
                '<div style="padding: 5px 10px">'+
                    '<label>Reason</label><br>'+
                    '<select style="border: 1px solid #ccc; border-radius: 5px; color: #333; font-size: 12px; height: 29px; margin: 3px 0; padding: 5px; width: -webkit-fill-available;" class="reason_dropdown">'+this.reasonOptions()+'</select>'+
                '</div>'+
                '<div style="padding: 5px 10px">'+
                    '<label>Instruction</label><br>'+
                    '<select style="border: 1px solid #ccc; border-radius: 5px; color: #333; font-size: 12px; height: 29px; margin: 3px 0; padding: 5px; width: -webkit-fill-available;" class="instruction_dropdown">'+this.instructionOptions()+'</select>'+
                '</div>'+
                '<div style="display: flex; align-items: center; padding: 5px 10px">'+
                    '<input type="checkbox" class="btnsubmit" id="dispute_all_btn" title="check_all">Dispute all inquiry'+
                '</div>'+
                '<div style="padding: 20px 10px">'+
                    '<center>'+
                        '<a href="javascript:void(0)" style="text-decoration:none;" class="btnsubmit" id="apply_changes_btn">Apply changes</a>'+                    
                    '</center>'+
                '</div>'+
            '</div>');
    },
}

window.onload = function () {

    //create button for printing
    chrome.storage.sync.get(['userID', 'loggedIn'], function(items) {
        if (items.loggedIn &&  (window.location.href.indexOf("importcreditreport/preview_credit_report_sc") > -1 || window.location.href.indexOf("importcreditreport/preview_credit_report_iiq") > -1) ) {
          console.log("Auto selector initiated");
          auto_selector.init();
        }
    });

    $(document).ready(function() {

        $(document).on('click', '#dispute_all_btn', function() {

            $( ".table.table-striped.global-form.roles-form .custom-table-row .col-3.custom-col.p-l-0.p-r-0[onclick]").each(function() {
                $(this).trigger( "click" );
            });

        });

        $(document).on('click', '#apply_changes_btn', function() {

            var values = {
                reasons : $('.reason_dropdown').val(),
                instruction : $('.instruction_dropdown').val(), 
            };


            $( ".custom-table-row.custom-table-row-bg .custom-select-box.form-control.error.sans-b.w-966.iprt_red_border.select2-hidden-accessible, .custom-select-box.form-control.select2-hidden-accessible.pink-bg").each(function() {

                var s = $(this).select2();
                s.val(values.reasons).trigger('change');

                var sContainer = $(this).next();
                
                //let's put just generated html code to the CRC instance
                sContainer.next().html(sContainer.html());
                //let's remove our instance
                sContainer.remove();

            });

            $( ".custom-table-row.custom-table-row-bg.ci_explanation_dd .custom-select-box.form-control.error.sans-b.w-966.iprt_red_border.select2-hidden-accessible").each(function() {            

                var s = $(this).select2();
                s.val(values.instruction).trigger('change');

                var sContainer = $(this).next();
                
                //let's put just generated html code to the CRC instance
                sContainer.next().html(sContainer.html());
                //let's remove our instance
                sContainer.remove();

            });

            $( ".custom-table-row.custom-table-row-bg .custom-select-box.form-control.error.sans-b.w-966.select2-hidden-accessible.pink-bg").each(function() {            

                var s = $(this).select2();
                s.val(values.instruction).trigger('change');

                var sContainer = $(this).next();
                
                //let's put just generated html code to the CRC instance
                sContainer.next().html(sContainer.html());
                //let's remove our instance
                sContainer.remove();

            });
     
        });
    })
};