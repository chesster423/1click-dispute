    </body>
</html>

<script type="text/javascript" src="../js/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="../js/popper.min.js"></script>
<script type="text/javascript" src="../js/bootstrap.min.js"></script>
<script type="text/javascript" src="../js/mdb.min.js"></script>
<script type="text/javascript" src="../js/sweetalert2.all.min.js"></script>
<script type="text/javascript" src="../js/angular.js"></script>
<script type="text/javascript" src="../js/ckeditor_4.14.0_standard/ckeditor/ckeditor.js"></script>


<script>
    var uid;

    $(document).ready(function () {
        uid = window.location.href.split('uid=')[1].replace('#', '');
        $(".button-collapse").sideNav();
        $('.mdb-select').material_select();
        $('[data-toggle="tooltip"]').tooltip();

        findActivePage();
    });

    function findActivePage() {
        var page = window.location.href;
        $('li[onclick^="window.location"]').each(function () {
                var link = $(this).attr('onclick').split("'")[1].split("'")[0];
                if (page.indexOf(link) >= 0) {
                    console.log('found!');
                    $(this).css({color:"black",background:"white"});
                    $(this).find('a').css({color:"black",background:"white"});
                    $(this).find('i').css({color:"black",background:"#f2f2f2"});
                }
        });
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
</script>