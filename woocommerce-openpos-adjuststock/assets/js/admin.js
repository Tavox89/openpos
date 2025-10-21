(function($) {
    "use strict";

    function add_row_change(row_id){
        let action_obj = $('#save-row-'+row_id);
        action_obj.addClass('has-update');
    }
    $(document).on('click','.transfer-arrow-plus',function(){
        let row_id = $(this).data('id');
        let qty_obj = $('#row-'+row_id);
        let current_qty = 1 * qty_obj.val();
        qty_obj.val(current_qty + 1);
        add_row_change(row_id);
    });
    $(document).on('click','.transfer-arrow-reduct',function(){
        let row_id = $(this).data('id');
        let qty_obj = $('#row-'+row_id);
        let current_qty = 1 * qty_obj.val();
        qty_obj.val(current_qty - 1);
        add_row_change(row_id);
    });
    $(document).on('change','input.transfer-batch-no,input.transfer-exp-date,input.transfer-cost',function(){
        let row_id = $(this).data('id');
        add_row_change(row_id);
    });
    $(document).on('click','.row-calendar-icon',function(){
        let row_id = $(this).data('id');
        let row_exp = null;
        if(row_exp == null)
        {
            
            row_exp = $('#row-exp-'+row_id).datetimepicker({
                format: 'L'
            });
        }
        $('#row-exp-'+row_id).data("DateTimePicker").show();
        
        
    });

})( jQuery );