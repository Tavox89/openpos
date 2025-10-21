(function($) {
    "use strict";

    $(document).ready( function () {
        $('#transfer-list').DataTable({
            processing: true,
            serverSide: true,
            ajax:  {
                url: transfer_app_ajax+'&action=app_get_transfers',
                type: 'POST',
            },
        });
    } );

})( jQuery );