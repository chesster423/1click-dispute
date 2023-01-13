var lob = {
    urls : {
        profile: 'https://app.creditrepaircloud.com/userdesk/profile/',
        server: (localStorage['devtest']) ? 'http://localhost/dominique-app/action.php' :'https://app.30daycra.com/action.php',
        previewLetter: 'https://app.creditrepaircloud.com/everything/preview_letter',
        local : 'http://localhost/dominique-app/action.php',
        deleteLetter : 'https://app.creditrepaircloud.com/everything/print_letter_ids',
        member_login : 'https://app.30daycra.com/member/login.php?redirectToRC=1'
    },
    properties : {
        usersWithLettersToPrint : [],
        printProgress : 0,
        usersWithLettersToPrintTotal : 0,
        userLetterID : null,
        payloadData : null,
        options : {
            letter_type : null,
            mail_type: null,
            with_certified_return_receipt : null,
            restrict_identity_documents : false,
            upload_additional_documentation : false,
            ringcentral_enabled : false,
        },
        userID : null,
        failedPrints: [],
        failedPrintsTriggersInitiated: false,
        mostUsedAddresses: {
            default: {
                firstName: '',
                lastName: '',
                address: '',
                city: '',
                country: '',
                state: '',
                zip: ''
            },
            equifax: {
                firstName: 'Equifax',
                lastName: 'Information Services LLC',
                address: 'P.O. Box 740256',
                city: 'Atlanta',
                country: 'United States',
                state: 'GA',
                zip: '30374'
            },
            experian: {
                firstName: 'Experian',
                lastName: ' ',
                address: 'P.O. Box 4500',
                city: 'Allen',
                country: 'United States',
                state: 'TX',
                zip: '75013'
            },
            transunion: {
                firstName: 'TransUnion',
                lastName: 'Consumer Solutions',
                address: 'P.O. Box 2000',
                city: 'Chester',
                country: 'United States',
                state: 'PA',
                zip: '19016'
            },
        },
        uploadedFiles : [],
        active_mail : null,
    },
    print : function(){

        function showProgressTable(transaction_code, res, result) {
            if (res.data.from.name === undefined)
                res.data.from.name = res.request.address.firstName + ' ' + res.request.address.lastName;

            $('.lobtable tbody').append('<tr id="letter_'+transaction_code+'">'+
                '<td style="border: 1px solid #f2f2f2;">'+res.data.from.name+'</td>'+
                '<td style="text-align:center; border: 1px solid #f2f2f2;">'+result.success+'</td>'+
                result.msg+
            '</tr>');
            lob.properties.printProgress++;
            $('.lob-progress').html((lob.properties.printProgress+1));
            lob.print();

            if (lob.properties.printProgress === lob.properties.usersWithLettersToPrintTotal) {
                $('.lob-loader').hide();
                $('.lob-close-print-btn-container').show();
                $('.process-container').hide();
            }
        }   

        if (lob.properties.usersWithLettersToPrint.length) {
            if (!$('.lobBackdrop').length)
                lob.prependPrintModal();

            var user = lob.properties.usersWithLettersToPrint.pop();

            lob.properties.userLetterID = user.letterID;

            lob.getAddress(user.profileID, function (address) {

                lob.getLetter(user.letterID, function (letter) {

                    let transaction_code = lob.createTransactionCode(20)+"-"+lob.properties.userID;

                    lob.properties.payloadData = {
                        letterID: user.letterID,
                        pdf: letter,
                        address: address,
                        options : lob.properties.options,
                        user_id : lob.properties.userID,
                        transaction_code : transaction_code,
                        uploaded_files : lob.properties.uploadedFiles,
                        recipient: user.recipient
                    };

                    if (lob.properties.payloadData.recipient.address === '') {

                        //let's try to parse recipient address from the letter
                        $('body').append('<div class="d-none" id="letterPreviewContentTemp"></div>');
                        $("#letterPreviewContentTemp").html(letter);

                        try {
                            //version 1
                            var letterContent = $('#letterPreviewContentTemp p:eq(0)').html().split('<br>');

                            letterContent[6] = letterContent[6].replaceAll('&nbsp;', ' ')

                            lob.properties.payloadData.recipient = {
                                firstName: letterContent[4],
                                lastName: '',
                                address: letterContent[5],
                                city: letterContent[6].split(',')[0],
                                state: letterContent[6].split(',')[1].split(' ')[1],
                                zip: letterContent[6].split(',')[1].split(' ')[2],
                            }
                        } catch {
                            try {
                                //version 2
                                var letterContent = $('#letterPreviewContentTemp .pageBreak p:eq(1)').html().split('<br>');

                                lob.properties.payloadData.recipient = {
                                    firstName: letterContent[0].split('">')[1].split('</span')[0],
                                    lastName: '',
                                    address: letterContent[1].split('">')[1].split('</span')[0],
                                    city: letterContent[2].split('">')[1].split('</span')[0].split(',')[0],
                                    state: letterContent[2].split('">')[1].split('</span')[0].split(',')[1].split(' ')[1],
                                    zip: parseInt(letterContent[2].split('">')[1].split('</span')[0].split(',')[2])
                                }
                            } catch {
                                lob.properties.payloadData.recipient = lob.properties.mostUsedAddresses.default;
                            }
                        }

                        $("#letterPreviewContentTemp").remove();
                    }

                    lob.sendLetter(lob.properties.payloadData, function(response){
                        
                        var res = JSON.parse(response);

                        var desc = res.description;

                        var result = {
                            success : 'Failed',
                            msg : '' +
                                '<td style="text-align:center; border: 1px solid #f2f2f2;">' +
                                    '<span>'+desc+'</span>' +
                                    '<br><a style="display: none; color: #007bff !important" href="javascript:void(0)" class="tryAgain" data-index="'+lob.properties.failedPrints.length+'">Click Here To Try Again</a>' +
                                '</td>'
                        };

                        if (res.success) {

                            result.success = 'Success';
                            if (lob.active_mail === 'lob') {
                                result.msg = '<td style="text-align:center; border: 1px solid #f2f2f2;"><a href="'+res.data.url+'" target="_blank">'+res.data.id+'</a></td><td style="text-align:center; border: 1px solid #f2f2f2;display:none;">-</td>';
                            }else {
                                result.msg = '<td style="text-align:center; border: 1px solid #f2f2f2;"><a href="https://prod.postalocity.com/" target="_blank">Visit Postalocity</a></td><td style="text-align:center; border: 1px solid #f2f2f2;">-</td>';
                            }

                            lob.deleteLetter(lob.properties.userLetterID);

                            // Send FAX if RingCentral is enabled

                            if (lob.properties.options.ringcentral_enabled) {
                                /*
                                let payload = {
                                    user_id : lob.properties.userID,
                                    file : res.file_url,
                                    fax : res.fax
                                };

                                lob.sendFax(payload, function(response) {

                                    let r = JSON.parse(response);

                                    //this usually happens when API allowed limit exceeded
                                    if (r.data !== null && r.data.messageStatus === 'Queued') {
                                        result.msg = '<td style="text-align:center; border: 1px solid #f2f2f2;"><a href="'+res.data.url+'" target="_blank">'+res.data.id+'</a></td>'+
                                        '<td style="text-align:center; border: 1px solid #f2f2f2;">'+r.data.messageStatus+
                                        '</td>';
                                    }else{
                                        result.msg = '<td style="text-align:center; border: 1px solid #f2f2f2;"><a href="'+res.data.url+'" target="_blank">'+res.data.id+'</a></td>'+
                                        '<td style="text-align:center; border: 1px solid #f2f2f2; font-size: 12px">'+
                                        '<a style="color:red; cursor: pointer" class="rc_try_again" data-file="'+r.file+'" data-fax="'+r.fax+'" data-userid="'+r.user_id+'">Try again</a>'+
                                        '</td>';
                                    }

                                    //slow down because of RingCentral API limits
                                    setTimeout(function() {
                                        showProgressTable(transaction_code, res, result);
                                    }, Math.floor(Math.random() * (2500 - 1000 + 1) + 1000));
                                });
                                 */
                            }else{

                                showProgressTable(transaction_code, res, result);

                            }


                        } else {
                            res.pdf = letter;
                            res.address = address;
                            lob.properties.failedPrints.push(res);
                            showProgressTable(transaction_code, res, result);
                        }
                    });
                });
            });
        } else {
            var tryAgain = $('.tryAgain');
            var tryAgainStatus = $("#tryAgainStatus");
            var request = null;
            var recipient = null;
            var sender = null;
            var tryAgainTranscationCode = "";

            if (tryAgain.length) {
                if (!lob.properties.failedPrintsTriggersInitiated) {
                    lob.properties.failedPrintsTriggersInitiated = true;
                }

                tryAgain.show().on('click', function () {
                   request = lob.properties.failedPrints[parseInt($(this).attr('data-index'))];
                   recipient = request.request.recipient;
                   sender = request.request.address;
                   tryAgainTranscationCode = request.request.transaction_code;

                    $('#tryAgainModal #letterPreview').html(request.request.pdf);
                   lob.autoFillTryAgainDetails(sender, 'Sender');
                   lob.autoFillTryAgainDetails(recipient, 'Recipient');
                   $("#recipientAddress").val('other').trigger('change');
                   tryAgainStatus.text("");

                   $('.click2mailModal').hide();
                   $('#tryAgainModal, #tryAgainPrintBtn').show();
                });

                $("#tryAgainPrintBtn").on('click', function () {
                    tryAgainStatus.css('color', '#0088cc').text('Please Wait...');

                    lob.properties.payloadData = {
                        pdf: request.request.pdf,
                        address: {
                            firstName: $("#tryAgainSenderFirstName").val(),
                            lastName: $("#tryAgainSenderLastName").val(),
                            address: $("#tryAgainSenderAddress").val(),
                            city: $("#tryAgainSenderCity").val(),
                            country: $("#tryAgainSenderCountry").val(),
                            state: $("#tryAgainSenderState").val(),
                            zip: $("#tryAgainSenderZIP").val()
                        },
                        options : request.request.options,
                        user_id : request.request.user_id,
                        transaction_code : lob.createTransactionCode(20) + "-" + request.request.user_id,
                        recipient: {
                            firstName: $("#tryAgainRecipientFirstName").val(),
                            lastName: $("#tryAgainRecipientLastName").val(),
                            address: $("#tryAgainRecipientAddress").val(),
                            city: $("#tryAgainRecipientCity").val(),
                            country: $("#tryAgainRecipientCountry").val(),
                            state: $("#tryAgainRecipientState").val(),
                            zip: $("#tryAgainRecipientZIP").val()
                        }
                    };

                    lob.sendLetter(lob.properties.payloadData, function(response){
                        response = JSON.parse(response);

                        if (!response.success)
                            tryAgainStatus.css('color', 'red').text(response.description);
                        else {
                            var pdfUrl = response.data.url;
                            var pdfId = response.data.id;
                            tryAgainStatus.css('color', 'green').html(response.msg + ': <a href="'+pdfUrl+'" target="_blank">'+pdfId+'</a>');
                            $("#tryAgainPrintBtn").hide();
                            lob.deleteLetter(request.request.letterID);
                        }
                    });
                });

                $("#tryAgainCloseBtn").on('click', function () {
                    if (tryAgainStatus.html() !== '') {
                        var row = $("#letter_" + tryAgainTranscationCode);
                        var thirdColumn = row.find('td:eq(2)');
                        row.find('td:eq(1)').text('Success');

                        thirdColumn.find('a').remove();
                        thirdColumn.find('br').remove();
                        thirdColumn.find('span').html(tryAgainStatus.html().replace('Letter successfully created:',''));
                    }

                    $('#tryAgainModal').hide();
                    $('.click2mailModal').show();
                });

                $("#recipientAddress").on('change', function () {
                   var recipientAddress = $(this).val();

                   if (recipientAddress === '') {
                       $("#tryAgainInputs").hide();
                   } else {
                       if (recipientAddress === 'other')
                           lob.autoFillTryAgainDetails(recipient, 'Recipient');
                       else
                           lob.autoFillTryAgainDetails(lob.properties.mostUsedAddresses[recipientAddress], 'Recipient');

                       $("#tryAgainInputs").show();
                   }
                });
            }
        }
    },
    autoFillTryAgainDetails: function (data, type) {
        $("#tryAgain" + type + "FirstName").val(data.firstName);
        $("#tryAgain" + type + "LastName").val(data.lastName);
        $("#tryAgain" + type + "Address").val(data.address);
        $("#tryAgain" + type + "City").val(data.city);
        $("#tryAgain" + type + "Country").val(data.country);
        $("#tryAgain" + type + "State").val(data.state);
        $("#tryAgain" + type + "ZIP").val(data.zip);
    },
    prependPrintModal : function() {

        $('body').prepend('<div class="lobBackdrop" style="overflow-y: scroll; width:100%; height: 100%; background: rgba(0, 0, 0, .6); position: fixed; z-index: 999;">'+
            '<div class="click2mailModal" style="margin: 0 auto;width: 50%;height: auto;background: #fff;top: 20px;position: relative; padding: 12px; border-radius: 8px;padding-bottom: 50px;">'+
                '<div class="lob-container">'+
                    '<div class="lob-content">'+
                        '<div class="lob-header" style="padding: 5px 10px; background: #f2f2f2;"><h3>Print with 30DayCRA APP</h3></div>'+
                        '<table class="lobtable" style="width: 100%;border: 1px solid #f2f2f2; margin-top: 20px;">'+
                            '<thead>'+
                                '<tr style="background: #ebf6f8; padding: 5px; text-align: center">'+
                                    '<th>Name</th>'+
                                    '<th>Status</th>'+
                                    '<th>Job Link</th>'+
                                    //'<th>Fax Status</th>'+
                                '</tr>'+
                            '</thead>'+
                            '<tbody>'+
                            '</tbody>'+
                        '</table>'+
                        '<center><img class="lob-loader" src="../img/ajax-loader.gif"></center><br>'+
                        '<center><span class="process-container">Processing: <span class="lob-progress">1</span>/'+lob.properties.usersWithLettersToPrint.length+'</span></center>'+
                        '<center><span class="print-error-container" style="color:red;"></span></center>'+
                        '<div style="float:right;margin-top:5px;margin-bottom:20px;display:none;cursor:pointer" align="right" class="gray-btn-big lob-close-print-btn-container">'+
                        '<a href="javascript:void(0);" class="lob-close-print-btn" style="background: #f2f2f2; color: black; border-radius: 5px; padding: 10px;">Close</a></div>'+
                    '</div>'+
                '</div>'+
            '</div>'+
            '' +
            '<div id="tryAgainModal" class="modal" tabindex="-1" style="display:none;margin: 0 auto;width: 80%;height: auto;background: #fff;top: 20px;position: relative; padding: 12px; border-radius: 8px;padding-bottom: 12px;">' +
            '    <div class="modal-dialog" style="margin: unset; max-width: 100% !important; width: 100%;">' +
            '        <div class="modal-content">' +
            '            <div class="modal-header justify-content-center" style="padding: 12px !important;">' +
            '                <h3 style="font-weight: 600; padding: 5px 10px;font-size: 1.75rem;background: #f2f2f2;width: 100%;">Print with 30DayCRA APP</h3>' +
            '            </div>' +
            '            <div class="modal-body" style="padding-bottom: 0px !important;">' +
            '               <div class="row">' +
            '                   <div class="col-md-4">' +
            '                       <h4 class="text-center">Sender Address</h4>' +
                    '                First Name<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainSenderFirstName">' +
                    '                Last Name<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainSenderLastName">' +
                    '                Address<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainSenderAddress">' +
                    '                Country<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainSenderCountry">' +
                    '                City<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainSenderCity">' +
                    '                State<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainSenderState">' +
                    '                ZIP<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainSenderZIP">' +
            '                   </div>' +
            '                   <div class="col-md-4">' +
            '                       <h4 class="text-center">Recipient Address</h4>' +
                    '                Select Recipient<br>' +
                    '                <select id="recipientAddress" style="height: 37px; width: 100%">' +
                    '                   <option value="">Please Select...</option>' +
                    '                   <option value="experian">Experian</option>' +
                    '                   <option value="transunion">TransUnion</option>' +
                    '                   <option value="equifax">Equifax</option>' +
                    '                   <option value="other">Other</option>' +
                    '                </select>' +
                    '                First Name<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainRecipientFirstName">' +
                    '                Last Name<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainRecipientLastName">' +
                    '                Address<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainRecipientAddress">' +
                    '                Country<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainRecipientCountry">' +
                    '                City<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainRecipientCity">' +
                    '                State<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainRecipientState">' +
                    '                ZIP<br>' +
                    '                <input style="height: 37px;" class="form-control" id="tryAgainRecipientZIP">' +
                    '                <p id="tryAgainStatus" style="font-size: 16px"></p>' +
            '                   </div>' +
            '                   <div class="col-md-4">' +
            '                       <h4 class="text-center">Letter Preview</h4>' +
            '                       <div style="background: #f2f2f2;overflow: scroll;height: 450px;padding: 20px;margin-top: 30px;" id="letterPreview"></div>' +
            '                   </div>' +
            '               </div>' +
            '            </div>' +
            '            <div class="modal-footer" style="float: right; padding-right: 24px">' +
            '                <a href="javascript:void(0);" id="tryAgainPrintBtn" style="background: #00a650; color: white; padding: 8px; border-radius: 5px; margin-right: 10px;">Print</a>' +
            '                <a href="javascript:void(0);" id="tryAgainCloseBtn" style="background: #f2f2f2; color: black; border-radius: 5px; padding: 8px;">Close</a>' +
            '            </div>' +
            '        </div>' +
            '    </div>' +
            '</div>' +
        '</div>');
    },
    prependStartPrint : function() {

        let payload = {
            id : lob.properties.userID,
        };

        $.ajax({
            url: lob.urls.server+"?entity=user&action=get_user",
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(response) {

                if (!response.success) {
                    alert(response.msg);
                    return false;
                }

                if (!response.data.lob_api_key || response.data.lob_api_key === '') {
                    alert("Operation cannot proceed. Lob API key is missing");
                    return false;
                }

                lob.active_mail = response.data.active_mail;

                // True or false. If true, show checkbox to allow user to send fax via rincentral.
                var send_ringcentral_fax_html = '';

                //if (response.data.ringcentral_enabled) {
                //    send_ringcentral_fax_html = '<span style="display:flex; align-items: center;k"><input type="checkbox" id="send_ringcentral_fax" style="margin-right: 5px"> Send fax with RingCentral</span>';
                //}

                var upload_additional_documentation_option = '<span id="upload_additional_documentation_parent" style="width: 49%; float: left">'+
                '<div><input type="checkbox" id="upload_additional_documentation" style="margin-right: 5px"> Upload additional documentation</div>'+
                '<br><div id="dropZone" style="display: none"><p style="font-weight: bold; text-align: center">Drop Files</p><input type="file" id="fileupload" name="attachments[]" style="margin-left:4px;" multiple accept="image/png, image/jpeg"/>'+
                '<p id="error" style="text-align:center"></p><p id="progress" style="text-align:center"></p></div></span><style>#dropZone { border: 3px dashed #0088cc; width: 100%; padding-bottom: 7px; }</style>';

                $('body').prepend('<div class="print-options-lob-backdrop" style="width:100%; height: 100%; background: rgba(0, 0, 0, .6); position: fixed; z-index: 999;">'+
                '<div class="click2mailModal" style="margin: 0 auto;width: 25%;min-width:500px;height: auto;background: #fff;top: 20%;position: relative; padding: 12px; border-radius: 8px;padding-bottom: 60px;">'+
                    '<div class="rc-loader" style="height: 100%;width: 100%;background:#fff;position: absolute;top: 0px;left: 0;border-radius: 8px;text-align: center; display: none;"><p style="padding-top: 120px;">Validating RingCentral Token...</p></div>'+
                    '<div class="lob-container">'+
                        '<div class="lob-content">'+
                            '<div class="lob-header" style="padding: 5px 10px; background: #f2f2f2; padding-bottom: 20px;"><h3>Print Options</h3>'+
                            'Mail system: <span style="background: #3dd549; padding: 2px 10px; border-radius: 99px; color: #fff;">'+lob.active_mail+'<span>'+
                            '</div>'+
                            '<div class="lob-body" style="padding: 20px 10px;"><label style="padding-right: 20px;">Letter Type</label>'+
                            '<select class="selected-letter-type">'+
                                '<option value="standard" selected>Standard</option>'+
                                '<option value="certified">Certified</option>'+
                            '</select>'+
                            '<select id="selected-letter-mail-type" style="margin-left: 10px;">'+
                                '<option value="usps_first_class" selected>Black & White Letter, First Class</option>'+
                                '<option value="usps_standard">Black & White Letter, Standard Class</option>'+
                            '</select>'+
                            '<div class="certified-return-receipt-container" style="display:none; margin-right: 32px;">Certified Mail with Electronic Return Receipt<br>'+
                                '<input type="radio" name="with_certified_return_receipt" value="certified_return_receipt_yes" style="margin-left: 0; margin-right: 5px" checked>Yes'+
                                '<input type="radio" name="with_certified_return_receipt" value="certified_return_receipt_no" style="margin-left: 20px; margin-right: 5px">No'+
                            '</div>'+
                            '</div>'+
                            '<div style="margin-bottom: 30px;margin-top:10px;" class="#printOptions">'+ upload_additional_documentation_option +   
                                '<div id="files"></div>'+                            
                                '<span style="float: right; width: 49%;">'+
                                '<span style="display:flex; align-items: center;"><input type="checkbox" id="restrict_identity_documents" style="margin-right: 5px"> Don\'t send identity documents</span><br>'+send_ringcentral_fax_html+'</span>'+
                            '</div>'+
                            '<div style="margin-top: 100px; padding-bottom: 12px;">'+
                                '<div style="float:right;margin-top:5px;margin-bottom:20px;cursor:pointer" align="right" class="gray-btn-big lob-close-print-option-btn-container">'+
                                    '<a class="lob-close-option-print-btn" style="background: #f2f2f2; color: black; border-radius: 5px; padding: 8px;">Close</a>'+
                                '</div>'+
                                '<div style="float:right;margin-top:5px;margin-bottom:20px;cursor:pointer" align="right" class="gray-btn-big lob-start-print-option-btn-container">'+
                                    '<a class="lob-start-print-btn" style="background: #00a650; color: white; padding: 8px; border-radius: 5px; margin-right: 10px;">Start printing '+ lob.properties.usersWithLettersToPrintTotal +' letters</a>'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                    '</div>'+
                '</div>'+
            '</div>');

                if (lob.properties.usersWithLettersToPrintTotal > 1)
                    $("#upload_additional_documentation_parent").hide();

                $("#fileupload").fileupload({
                    url: lob.urls.server+"?entity=lob&action=upload_additional_files",
                    dropZone: '#dropZone',
                    dataType: 'json',
                    autoUpload: false
                }).on('fileuploadadd', function (e, data) {
                    var fileTypeAllowed = /.\.(jpg|png|jpeg)$/i;
                    var fileName = data.originalFiles[0]['name'];
                    var fileSize = data.originalFiles[0]['size'];

                    if (!fileTypeAllowed.test(fileName))
                        $("#error").html('Only images are allowed!');
                    else if (fileSize > 1000000)
                        $("#error").html('Your file is too big! Max allowed size is: 1MB');
                    else {
                        $("#error").html("");
                        data.submit();
                    }
                }).on('fileuploaddone', function(e, data) {
                    var status = data.jqXHR.responseJSON.status;
                    var msg = data.jqXHR.responseJSON.msg;

                    if (data.jqXHR.responseJSON.success) {
                        lob.properties.uploadedFiles.push(data.jqXHR.responseJSON.filename);
                    }else{                        
                        $('#error').html(data.jqXHR.responseJSON.msg);
                        $("#progress").html("");
                    }

                }).on('fileuploadprogressall', function(e,data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
  
                    $("#progress").html("Completed: " + progress + "%");    
                                
                });
            }

        });

    },
    getLetter : function(letterID, callback) {
        $.post(lob.urls.previewLetter, {
            lid: letterID,
            doc: 1,
            round: 1
        }, function (letter) {
            callback(letter);
        });
    },
    getAddress : function(profileID, callback) {
        $.get(lob.urls.profile + profileID, function (r) {

            var selectedCountry = lob.getBetween(r, 'id="country" value="', '"');

            var address = {
                firstName: lob.getBetween(r, 'id="fname" value="', '"'),
                lastName: lob.getBetween(r, 'id="lname" value="', '"'),
                city: lob.getBetween(r, 'id="city" value="', '"'),
                state: lob.getBetween(r, 'id="state" class="form-control" value="', '"'),
                country: lob.getBetween(r, '<option value=\''+selectedCountry+'\' selected>', '</option>'),
                zip: lob.getBetween(r, 'id="pcode" class="form-control" onpaste="return false;" maxlength="6" value="', '"'),
                fax: '', //lob.getBetween(r, 'id="fax" class="input" value="', '"'),
                address: lob.getBetween(r, 'onFocus="callGooglePlacesAPI()" value="', '"')
            };

            if (address.firstName === '')
                address.firstName = lob.getBetween(r, 'id="fname" value="', '"');

            if (address.state === '')
                address.state = lob.getBetween(r, 'id="state" class="form-control" value="', '"');

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
        if (localStorage.getItem('devTest') !== null)
            return;

        $.post(lob.urls.deleteLetter, {
            letter_id: letterID,
        }, function (letter) {
            // callback(letter);
        });
    },
    sendLetter : function(data, callback) {

        let mail_system = lob.active_mail;

        $.post(lob.urls.server+"?entity="+mail_system+"&action=send_letter", data, function (r) {
            callback(r);
        }).fail( function(r){
            lob.sendLetter(data, callback);
        });

    },
    sendFax : function(data, callback) {

        $.post(lob.urls.server+"?entity=ringcentral&action=send_fax", data, function (r) {
            callback(r);
        }).fail( function(r){
            lob.sendFax(data, callback);
        });
        
    },/*
    checkRCToken : function(data, callback) {

        $.post(lob.urls.server+"?entity=user&action=check_rc_token", data, function (r) {
            callback(r);
        }).fail( function(r){
            lob.checkRCToken(data, callback);
        });
        
    },*/
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

            lob.properties.userID = items.userID;

            if (items.loggedIn && items.active_mail) {
                var btn = '<div style="float:right;margin-top:-30px;margin-right:0px;" align="right" class="green-btn-lined2 font-normal">' +
                '<a href="javascript:void(0);" id="printWithExtension" data-toggle="modal" data-target="#printModal">Print with 30DayCRA APP</a></div>';
                $(btn).insertAfter($('#gridData'));

                $("#printWithExtension").on('click', function () {
                    lob.properties.usersWithLettersToPrint = [];
                    lob.properties.usersWithLettersToPrintTotal = 0;

                    $('#gridData input[type="checkbox"]').each(function() {
                        if ($(this).prop('checked') && $(this).attr('id') !== 'check_all_letter') {
                            var mainRow = $(this).closest('tr');
                            var letterID = $(this).attr('value');//mainRow.find('a[onclick^="return preview_letter_selected"]').attr('onclick').match(/[0-9]+/g)[0];
                            var editLetterBtn = mainRow.find('a[onclick^="edit_letter("]:eq(0)');
                            var profileID = editLetterBtn.attr('onclick').split(",'")[1].split("'")[0];
                            var toAddress = editLetterBtn.text().toLowerCase();
                            var recipient = null;

                            if (toAddress.indexOf('transunion') >= 0) {
                                recipient = lob.properties.mostUsedAddresses.transunion;
                            } else if (toAddress.indexOf('equifax') >= 0) {
                                recipient = lob.properties.mostUsedAddresses.equifax;
                            } else if (toAddress.indexOf('experian') >= 0) {
                                recipient = lob.properties.mostUsedAddresses.experian;
                            } else {
                                recipient = lob.properties.mostUsedAddresses.default;
                            }

                            mainRow.attr('id', 'pdf_'+letterID);
                            lob.properties.usersWithLettersToPrint.push({ letterID: letterID, profileID: profileID, recipient: recipient });
                            lob.properties.usersWithLettersToPrintTotal++;
                        }
                    });

                    lob.properties.usersWithLettersToPrint.reverse();
                    lob.prependStartPrint();

                });
            }

        });

    }

    $(document).on('click', '.lob-close-option-print-btn', function() {
        lob.properties.uploadedFiles = [];
        $('.print-options-lob-backdrop').remove();
    });

    $(document).on('click', '.lob-start-print-btn', function() {
        lob.properties.options.letter_type = $('.selected-letter-type').val();
        lob.properties.options.mail_type = $('#selected-letter-mail-type').val();
        lob.properties.options.with_certified_return_receipt = $("input[name='with_certified_return_receipt']:checked").val();
        lob.properties.options.restrict_identity_documents = ($('#restrict_identity_documents').prop("checked") == true) ? true : false;
        lob.properties.options.upload_additional_documentation = ($('#upload_additional_documentation').prop("checked") == true) ? true : false;
        lob.properties.options.ringcentral_enabled = ($('#send_ringcentral_fax').prop("checked") == true) ? true : false;

        $('.print-options-lob-backdrop').remove();
        lob.properties.failedPrints = [];
        lob.print();
    });

    /*
    $(document).on('change', '#send_ringcentral_fax', function() {

        if(this.checked) {

            const member_login_url = lob.urls.member_login;

            let payload_data = {
                user_id : lob.properties.userID,
            };

            $('.rc-loader').show();

            lob.checkRCToken(payload_data, function(response) {

                $('.rc-loader').hide();

                let res = JSON.parse(response);

                if (!res.success) {
                    alert(res.msg);
                    window.open(member_login_url, '_blank');
                    $('#send_ringcentral_fax').prop("checked", false);
                    return false;
                }
            })
            
        }
      
    });
    */

    $(document).on('click', '.lob-close-print-btn', function() {
        location.reload();
    });

    $(document).on('change', '.selected-letter-type', function() {
        let val = $(this).val();

        if (val === 'standard') {
            $('#selected-letter-mail-type').show();
            $('.certified-return-receipt-container').hide();
        }else{
            $('#selected-letter-mail-type').hide();
            //select "no electronic receipt" by default
            $('input[name="with_certified_return_receipt"]').prop('checked', true);
            $('.certified-return-receipt-container').hide();
        }
    })

    $(document).on('click', '#upload_additional_documentation', function() {
        if ($("#upload_additional_documentation").prop('checked')){
             $('#dropZone').show();
        } else {
             $('#dropZone').hide();
        }
    });

    $(document).on('click', '.rc_try_again', function(){

        let payload = {
            fax : $(this).data('fax'),
            file : $(this).data('file'),
            user_id : $(this).data('userid')
        }

        var rc_elem = $(this);

        if (confirm("Try sending fax again?")) {

            rc_elem.text("Sending fax...");

            lob.sendFax(payload, function(response) {

                let r = JSON.parse(response);

                if (r.success) {
                    rc_elem.text('RingCentral Fax URL');
                    rc_elem.css('color', '#0000ee');
                    rc_elem.attr('class', 'rc_success');
                    rc_elem.attr('href', r.data.uri);
                    rc_elem.attr('target', '_blank');
                }else{
                    rc_elem.text("Try again");
                    alert("An error occured. Please try again.");   
                }
                
            });
        }        

    })
});
