var dataTableFunnels, uid, dataTableApps, appsLoaded = false;

var baseUrl = 'https://mempro.io/';
//var baseUrl = 'http://localhost/memberspro/';

$(document).ready(function() {
    uid = window.location.href.split('uid=')[1].replace('#', '');

    $("#manageFunnelsMaster").on('click', function () {
        $('#membershipModal').modal('show');
    });

    $('#membershipModal').on('hidden.bs.modal', function () {
        dataTableFunnels.destroy();
        $("#funnelsTable tbody").html("");
        resetMembershipModal();
    });
});

function resetMembershipModal() {
    $("#membershipModal .modal-footer input").fadeIn();
    $("#membershipModal #newFunnel").fadeOut();
    $("#membershipModal #allFunnels").fadeIn();
    $("#funnelName").val("");
}

function saveMembership() {
    var name = $("#funnelName");
    var appName = $("#appName");
    var appId = $('#appId option:selected');

    if (isNotEmpty(name) || isNotEmpty(appName)) {
        $.ajax({
            url: baseUrl + 'manageMemberships.php?uid=' + uid,
            method: 'POST',
            dataType: 'json',
            data: {
                addNewMembership: 1,
                onBoarding: 1,
                name: name.val(),
                appName: appName.val(),
                appId: appId.val(),
                formAction: $("#membershipFormAction").val(),
                editID: $("#editMembershipID").val()
            }, success: function (response) {
                if (response.status == "updated") {
                    var tableRow = $("#trMembership_" + $("#editMembershipID").val());
                    tableRow.find('td:eq(0)').text(name.val());
                    $("#selectFunnelMaster").find('option[value="'+$("#editMembershipID").val()+'"]').html(name.val());
                } else {

                    window.location = 'onboarding.php?uid=' + uid;
                    /*
                    dataTableFunnels.row.add([
                        name.val(),
                        '<input type="button" value="Edit" style="width: 47px;" class="btn-primary btn" onclick="editMembership(' + response.rowID + ')">' +
                        '<input type="button" value="Delete" style="margin-left: 5px;" class="btn-danger btn" onclick="del(' + response.rowID + ', \'memberships\')">'
                    ]).draw(false);

                    $('#funnelsTable tbody').find('tr:last').attr('id', 'trMembership_' + response.rowID);
                    $("#selectFunnelMaster").append('<option value="'+response.rowID+'">'+name.val()+'</option>');
                    */
                }

                name.val("");
                $('#membershipFormAction').val('addNewMembership');
                //$('#saveMembership').html('<i class="fa fa-plus" style="float: left; margin-left: 10px; margin-right: 10px; margin-top: 2px;"></i> Add Membership');
                //resetMembershipModal();
            }
        });
    }
}

function resetAppModal() {
    $("#appsModal .modal-footer input").fadeIn();
    $("#appsModal #newAPP").fadeOut();
    $("#appsModal #allApps").fadeIn();
    $("#appName").val("");
}

function saveAPP() {
    var appName = $("#appName");

    if (isNotEmpty(appName)) {
        $.ajax({
            url: baseUrl + 'manageMemberships.php?uid=' + uid,
            method: 'POST',
            dataType: 'json',
            data: {
                addNewAPP: 1,
                appName: appName.val(),
                formAction: $("#appFormAction").val(),
                registerAllowedToAll: $("#registerAllowedToAll").val(),
                appID: $("#editAppID").val()
            }, success: function (response) {
                if (response.status == "updated") {
                    var tableRow = $("#trAPPS_" + $("#editAppID").val());
                    tableRow.attr('data-registerAllowedToAll', $("#registerAllowedToAll").val());
                    tableRow.find('td:eq(0)').text(appName.val());
                    $("#selectApplication").find('option[value="'+$("#editAppID").val()+'"]').html(appName.val());
                } else {
                    window.location.reload();
                }

                appName.val("");
                $('#appFormAction').val('addNewAPP');
                $('#saveAPP').html('<i class="fa fa-plus" style="float: left; margin-left: 10px; margin-right: 10px; margin-top: 2px;"></i> Add APP');
                resetAppModal();
            }
        });
    }
}

function editAPP(id) {
    var tableRow = $("#trAPPS_" + id);
    $("#registerAllowedToAll").val(tableRow.attr('data-registerAllowedToAll'));
    $("#appFormAction").val("edit");
    $("#editAppID").val(id);
    $("#appName").val(tableRow.find('td:eq(0)').text());
    $('#saveAPP').html('<i class="fa fa-save" style="float: left; margin-left: 10px; margin-right: 10px; margin-top: 2px;"></i> Update');
    $('#appsModal').modal('show');
    $("#allApps").fadeOut();
    $("#newAPP").fadeIn();
    $("#appsModal .modal-footer input").fadeOut();
}

function editMembership(id) {
    var tableRow = $("#trMembership_" + id);
    $("#membershipFormAction").val("edit");
    $("#editMembershipID").val(id);
    $("#funnelName").val(tableRow.find('td:eq(0)').text());
    $('#saveMembership').html('<i class="fa fa-save" style="float: left; margin-left: 10px; margin-right: 10px; margin-top: 2px;"></i> Update');
    $('#membershipModal').modal('show');
    $("#allFunnels").fadeOut();
    $("#newFunnel").fadeIn();
    $("#membershipModal .modal-footer input").fadeOut();
}

function loadMemberships(start, max) {
    var app = 0;

    if (window.location.href.indexOf('/apps/') >= 0)
        app = 1;

    if (start == 0) {
        $("#allFunnels").parent().fadeIn();
        $('#membershipFormAction').val('addNewMembership');
        $('#funnelName').val('');
        $('#saveMembership').html('<i class="fa fa-plus" style="float: left; margin-left: 10px; margin-right: 10px; margin-top: 2px;"></i> Add Membership');
    } else if (start > max) {
        dataTableFunnels = $("#funnelsTable").DataTable({
            paging: false,
            searching: true,
            "order": []
        });

        $("#loading").fadeOut();

        if (app) {
            $("#funnelsTable tbody").sortable({
                stop: function(e, ui) {
                    updateMembershipPositions();
                }
            });
        }

        return;
    }

    $.ajax({
        url: baseUrl + 'manageMemberships.php?uid=' + uid,
        method: 'POST',
        dataType: 'text',
        data: {
            loadMemberships: 1,
            start: start,
            apps: app
        }, success: function (response) {
            $("#funnelsTable tbody").append(response);
            start += 20;
            loadMemberships(start, max);
        }
    });
}

function loadAPPS(start, max) {
    if (appsLoaded)
        return;

    var app = 1;

    if (start == 0) {
        $("#allApps").parent().fadeIn();
        $('#membershipFormAction').val('addNewAPP');
        $('#appName').val('');
        $('#saveAPP').html('<i class="fa fa-plus" style="float: left; margin-left: 10px; margin-right: 10px; margin-top: 2px;"></i> Add APP');
    } else if (start > max) {
        dataTableApps = $("#appsTable").DataTable({
            paging: false,
            searching: true,
            "order": []
        });

        $("#loading").fadeOut();
        appsLoaded = true;

        return;
    }

    $.ajax({
        url: baseUrl + 'manageMemberships.php?uid=' + uid,
        method: 'POST',
        dataType: 'text',
        data: {
            loadApps: 1,
            start: start
        }, success: function (response) {
            $("#appsTable tbody").append(response);
            start += 20;
            loadAPPS(start, max);
        }
    });
}

function updateMembershipPositions() {
    var arr = [];

    $('#funnelsTable tbody').find('tr').each(function () {
        var id = parseInt($(this).attr('id').replace('trMembership_', ''));
        if ($.inArray(id, arr) < 0)
            arr.push(id);
    });

    $.ajax({
        url: baseUrl + 'apps/appDashboard.php?uid=' + uid,
        method: 'POST',
        dataType: 'text',
        data: {
            updatePositions: 1,
            ids: arr
        }, success: function (response) {
        }
    });
}

function del(id, table) {
    var msg = "Are you sure?";

    if (table == "applications")
        msg = "Are you sure? \r\nAll data associated with this app will be DELETED too (including all memberships and their content)!";

    if (table == "memberships")
        msg = "Are you sure? \r\nAll data associated with this membership will be deleted too!";

    if (table == "lessons")
        msg = "Are you sure? \r\nAll student activities for this lesson will be deleted too!";

    if (table == "modules")
        msg = "Are you sure? \r\nAll lessons and student activities for those lessons will be deleted too!";

    if (confirm(msg)) {
        $.ajax({
            url: baseUrl + 'manageMemberships.php?uid='+uid,
            method: 'POST',
            async: false,
            dataType: 'text',
            data: {
                del: 1,
                id: id,
                table: table
            }, success: function (response) {
                if (table == 'applications')
                    $("#trAPPS_"+id).remove();
                else if (table == 'lessons')
                    $("#lesson-"+id).remove();
                else if (table == 'modules') {
                    $("#module-" + id).remove();

                    $("#insertModuleAfter").html("");

                    $('div[id^="module-"]').each(function () {
                        var rID = $(this).attr('id').replace('module-', '');

                        if (rID != "") {
                            var name = $(this).find('.card-header').find('.btn-link').text().trim();
                            $("#insertModuleAfter").append('<option value="'+rID+'">'+name+'</option>');
                        }
                    });

                    $("#lessonModule").html($("#insertModuleAfter").html());
                    $("#newLessonModule").html($("#insertModuleAfter").html());

                    $("#advancedSettings").modal('hide');
                } else if (table == "whiteListedEmails")
                    whiteListedTable.row($("#email_" + id)).remove().draw(false);
                else if (table == "blackListedEmails")
                    blackListedTable.row($("#email_" + id)).remove().draw(false);
                else if (table == "products")
                    dataTableProducts.row($("#tr_" + id)).remove().draw(false);
                else if (table == 'memberships') {
                    dataTableFunnels.row($("#trMembership_" + id)).remove().draw(false);
                    var funnelName = $("option[value='"+id+"']").text();
                    $("option[value='"+id+"']").remove();

                    if (window.location.href.indexOf('products.php') >= 0)
                        window.location = 'index.php';
                }
            }
        });

        return true;
    } else
        return false;
}

function isNotEmpty(caller) {
    if (caller.val() == "") {
        caller.css('border', '1px solid red');
        return false;
    } else {
        caller.css('border', '');
        return true;
    }
}