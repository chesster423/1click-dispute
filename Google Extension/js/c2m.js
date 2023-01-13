var c2m = {
    urls : {
        profile: 'https://app.creditrepaircloud.com/userdesk/profile/',
        server: (localStorage['devtest']) ? 'http://localhost/dominique-app/action.php' :'https://app.30daycra.com/action.php',
        previewLetter: 'https://app.creditrepaircloud.com/everything/preview_letter',
        local : 'http://localhost/dominique-app/action.php',
        deleteLetter : 'https://app.creditrepaircloud.com/everything/print_letter_ids',
    },
    properties : {
        usersWithLettersToPrint : [],
        printProgress : 0,
        usersWithLettersToPrintTotal : 0,
        userLetterID : null,
        payloadData : null,
        options : {
            letter_type : null,
        },
        userID : null,
    },
    print : function(){
        if (c2m.properties.usersWithLettersToPrint.length) {
            if (!$('.c2mBackdrop').length)
                c2m.prependPrintModal();

            var user = c2m.properties.usersWithLettersToPrint.pop();

            c2m.properties.userLetterID = user.letterID;

            c2m.getAddress(user.profileID, function (address) {
                c2m.getLetter(user.letterID, function (letter) {

                    let transaction_code = c2m.createTransactionCode(20)+"-"+c2m.properties.userID;

                    c2m.properties.payloadData = {
                        pdf: letter,
                        address: address,
                        options : c2m.properties.options,
                        user_id : c2m.properties.userID,
                        transaction_code : transaction_code
                    };

                    c2m.savePDF(c2m.properties.payloadData, function(response_a){
                        
                        let res_a = JSON.parse(response_a);

                        c2m.properties.payloadData.doc_id = res_a.data.document_id;

                        c2m.createAddressList(c2m.properties.payloadData, function(response_b){

                            let res_b = JSON.parse(response_b);

                            c2m.properties.payloadData.address_id = res_b.data.addresslist_id; 

                            c2m.createJob(c2m.properties.payloadData, function(response_c){

                                let res_c = JSON.parse(response_c);

                                let desc = res_c.data.description;

                                // if (desc == 'Document is not compatible with the document class in this job.') {
                                //     desc = 'PDF file size is too large. Certified letter can only have 2 pages max.';
                                // }

                                let result = {
                                    success : 'Failed',
                                    msg : '<td style="text-align:center; border: 1px solid #f2f2f2;">'+desc+'</td>'
                                };

                                if (res_c.success == true) {
                                    result.success = 'Success';
                                    result.msg = '<td style="text-align:center; border: 1px solid #f2f2f2;"><a href="'+res_c.data.statusUrl+'" target="_blank">'+res_c.data.id+'</a></td>';
                                    c2m.deleteLetter(c2m.properties.userLetterID);
                                }

                                $('.c2mtable tbody').append('<tr>'+
                                    '<td style="border: 1px solid #f2f2f2;">'+res_c.data.name+'</td>'+
                                    '<td style="text-align:center; border: 1px solid #f2f2f2;">'+result.success+'</td>'+
                                    result.msg+
                                '</tr>');
                                c2m.properties.printProgress++;
                                $('.c2m-progress').html((c2m.properties.printProgress+1));
                                c2m.print();

                                if (c2m.properties.printProgress === c2m.properties.usersWithLettersToPrintTotal) {
                                    $('.c2m-loader').hide();
                                    $('.c2m-close-print-btn-container').show();
                                    $('.process-container').hide();
                                }
                            
                            });                        
                        });
                    });
                });
            });
        }
    },
    prependPrintModal : function() {

        $('body').prepend('<div class="c2mBackdrop" style="width:100%; height: 100%; background: rgba(0, 0, 0, .6); position: fixed; z-index: 999;">'+
            '<div class="click2mailModal" style="margin: 0 auto;width: 35%;height: auto;background: #fff;top: 20%;position: relative; padding: 12px; border-radius: 8px;padding-bottom: 50px;">'+
                '<div class="c2m-container">'+
                    '<div class="c2m-content">'+
                        '<div class="c2m-header" style="padding: 5px 10px; background: #f2f2f2;"><h3>Print with 30DayCRA APP</h3></div>'+
                        '<table class="c2mtable" style="width: 100%;border: 1px solid #f2f2f2; margin-top: 20px;">'+
                            '<thead>'+
                                '<tr style="background: #ebf6f8; padding: 5px;">'+
                                    '<th>Name</th>'+
                                    '<th>Status</th>'+
                                    '<th>Job Link</th>'+
                                '</tr>'+
                            '</thead>'+
                            '<tbody>'+
                            '</tbody>'+
                        '</table>'+
                        '<center><img class="c2m-loader" src="../img/ajax-loader.gif"><br>'+
                        '<span class="process-container">Processing: <span class="c2m-progress">1</span>/'+c2m.properties.usersWithLettersToPrint.length+'</span></center>'+
                        '<div style="float:right;margin-top:5px;margin-bottom:20px;display:none;cursor:pointer" align="right" class="gray-btn-big c2m-close-print-btn-container">'+
                        '<a href="javascript:void(0);" class="c2m-close-print-btn">Close</a></div>'+
                    '</div>'+
                '</div>'+
            '</div>'+
        '</div>');
    },
    prependPrintOptions : function() {

        $('body').prepend('<div class="print-options-c2m-backdrop" style="width:100%; height: 100%; background: rgba(0, 0, 0, .6); position: fixed; z-index: 999;">'+
            '<div class="click2mailModal" style="margin: 0 auto;width: 25%;height: auto;background: #fff;top: 20%;position: relative; padding: 12px; border-radius: 8px;padding-bottom: 50px;">'+
                '<div class="c2m-container">'+
                    '<div class="c2m-content">'+
                        '<div class="c2m-header" style="padding: 5px 10px; background: #f2f2f2;"><h3>Print Options</h3></div>'+
                        '<div class="c2m-body" style="padding: 20px 10px;">'+
                            '<label style="padding-right: 20px;">Letter Type</label>'+
                            '<select class="selected-letter-type">'+
                                '<option value="Letter 8.5 x 11" selected>Letter 8.5 x 11</option>'+
                                '<option value="Certified Letter 8.5 x 11">Certified Letter 8.5 x 11</option>'+
                            '</select>'+
                        '</div>'+
                        '<div style="float:right;margin-top:5px;margin-bottom:20px;cursor:pointer" align="right" class="gray-btn-big c2m-close-print-option-btn-container">'+
                            '<a class="c2m-close-option-print-btn">Close</a>'+
                        '</div>'+
                        '<div style="float:right;margin-top:5px;margin-bottom:20px;cursor:pointer" align="right" class="gray-btn-big c2m-start-print-option-btn-container">'+
                            '<a class="c2m-start-print-btn">Start printing '+ c2m.properties.usersWithLettersToPrint.length +' letters</a>'+
                        '</div>'+
                    '</div>'+
                '</div>'+
            '</div>'+
        '</div>');
    },
    getLetter : function(letterID, callback) {
        $.post(c2m.urls.previewLetter, {
            lid: letterID,
            doc: 1,
            round: 1
        }, function (letter) {
            callback(letter);
        });
    },
    getAddress : function(profileID, callback) {
        $.get(c2m.urls.profile + profileID, function (r) {
            var selectedCountry = c2m.getBetween(r, 'id="country" value="', '"');

            var address = {
                firstName: c2m.getBetween(r, 'id="fname" class="input" value="', '"'),
                lastName: c2m.getBetween(r, 'id="lname" class="input"  value="', '"'),
                city: c2m.getBetween(r, 'id="city"  class="input" value="', '"'),
                state: c2m.getBetween(r, 'id="state"  class="input statetb" value="', '"'),
                country: c2m.getBetween(r, '<option value=\''+selectedCountry+'\' selected>', '</option>'),
                zip: c2m.getBetween(r, 'id="pcode" class="input" value="', '"'),
                address: c2m.getBetween(r, 'onFocus="callGooglePlacesAPI()" value="', '"')
            };

            callback(address);
        });
    },
    getBetween : function(str, start, end) {
        try {
            return str.split(start)[1].split(end)[0];
        } catch (e) {}

        return "";
    },
    deleteLetter : function(letterID) {
        $.post(c2m.urls.deleteLetter, {
            letter_id: letterID,
        }, function (letter) {
            // callback(letter);
        });
    },
    savePDF : function(data, callback) {
        $.post(c2m.urls.server+"?entity=click2mail&action=save_document", data, function (r) {
            callback(r);
        }).fail( function(r){
            c2m.savePDF(data, callback);
        });
    },
    createAddressList : function(data, callback) {
        $.post(c2m.urls.server+"?entity=click2mail&action=create_addresslist", data, function (r) {
            callback(r);
        }).fail(function(r){
            c2m.createAddressList(data, callback);
        });
    },
    createJob : function(data, callback) {
        $.post(c2m.urls.server+"?entity=click2mail&action=create_job", data, function (r) {
            callback(r);
        }).fail( function(r){
            c2m.createJob(data,callback);
        });
    },
    createTransactionCode : function(length) {
       var result           = '';
       var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
       var charactersLength = characters.length;
       for ( var i = 0; i < length; i++ ) {
          result += characters.charAt(Math.floor(Math.random() * charactersLength));
       }
       return result;
    }
}

$(document).ready(function () {
    if (window.location.href.indexOf('todays_letter') >= 0) {

        //create button for printing
        chrome.storage.sync.get(['userID', 'loggedIn', 'active_mail'], function(items) {

            c2m.properties.userID = items.userID;

            if (items.loggedIn && items.active_mail == 'c2m') {
                var btn = '<div style="float:right;margin-top:5px;margin-bottom:20px;" align="right" class="gray-btn-big">' +
                    '<a href="javascript:void(0);" id="printWithExtension" data-toggle="modal" data-target="#printModal">Print with 30DayCRA APP</a></div>';
                $(btn).insertBefore($('.gray-btn-big:eq(1)'));

                $("#printWithExtension").on('click', function () {
                    c2m.properties.usersWithLettersToPrint = [];

                    $('input[type="checkbox"]').each(function() {
                        if ($(this).prop('checked') && $(this).attr('id') !== 'check_all_letter') {
                            var mainRow = $(this).parent().parent();
                            var letterID = mainRow.find('a[onclick^="return preview_letter_selected"]').attr('onclick').match(/[0-9]+/g)[0];
                            var profileID = mainRow.find('a[title="edit"]:eq(0)').attr('href').split('/'+letterID+'/')[1];
                            mainRow.attr('id', 'pdf_'+letterID);
                            c2m.properties.usersWithLettersToPrint.push({ letterID: letterID, profileID: profileID });
                            c2m.properties.usersWithLettersToPrintTotal++;
                        }
                    });

                    c2m.properties.usersWithLettersToPrint.reverse();
                    c2m.prependPrintOptions();            

                });
            }

        });

    }

    $(document).on('click', '.c2m-close-option-print-btn', function() {
        $('.print-options-c2m-backdrop').remove();
    });

    $(document).on('click', '.c2m-start-print-btn', function() {
        c2m.properties.options.letter_type = $('.selected-letter-type').val();
        $('.print-options-c2m-backdrop').remove();
        c2m.print();
    });

    $(document).on('click', '.c2m-close-print-btn', function() {
        location.reload();
    });
});