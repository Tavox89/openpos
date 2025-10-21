(function($) {
    "use strict";
    $(document).ready(function(){
        $(document).on('click','.delete-batch-product-btn',function(){
            let id = $(this).data('id');
            let current = $(this);
            if(confirm('Are you sure ?'))
            {
                
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    dataType: 'json',
                    data: {action: 'op_delete_batch_product', batch_id: id},
                    success: function(){
                        current.closest('tr').remove();
                    }
                });
            }
            
        })
        $(document).on('click','.assign-item-batch-a',function(){
            let order_id = $(this).data('order_id');
            let item_id = $(this).data('item_id');
            let product_id = $(this).data('product_id');
            
            $.ajax({
                url: ajaxurl,
                type: 'post',
                dataType: 'json',
                data: {order_id:order_id,item_id:item_id,product_id:product_id,action:'op_transfer_load_batch_assign_html'},
                success: function(data){
                    
                    $( "#op-transfer-dialog-content" ).html(data.content);
                    $( "#op-transfer-dialog" ).dialog({
                        resizable: false,
                        height: "auto",
                        width: 400,
                        modal: true,
                        dialogClass: "op-transfer-dialog-alert",
                        title: 'Assign batch item',
                        buttons: {
                            // "Quick Assign": function() {
                            //     $( this ).dialog( "close" );
                            // },
                            "Assign": function() {
                                let current_dialog =   $( this );
                                let form_data = $( "#op-transfer-dialog-content" ).find('form').serialize();
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'post',
                                    dataType: 'json',
                                    data: form_data,
                                    success: function(data){
                                        if(data.status == 1)
                                        {
                                            current_dialog.dialog( "close" );
                                            location.reload();
                                        }else{
                                            alert(data.message);
                                        }
                                    }
                                });
                          },
                         
                          Cancel: function() {
                            $( this ).dialog( "close" );
                          }
                        }
                    });
                }
            })
            
           
        })
    });
})( jQuery );