<head>
    <title>Stock transfer</title>
    <link rel='stylesheet'   href='<?php echo OPENPOS_APP_STOCK_ADJUST_URL; ?>/assets/css/bootstrap.css' media='all' />
    <link rel='stylesheet'   href='<?php echo OPENPOS_APP_STOCK_ADJUST_URL; ?>/assets/css/datatables.min.css' media='all' />
    <link rel='stylesheet'   href='<?php echo OPENPOS_APP_STOCK_ADJUST_URL; ?>/assets/css/jquery.dataTables.css' media='all' />
    <style id='openpos.front-inline-css' type='text/css'>
        .from-wh{
            color: red;
        }
        .to-wh{
            color:green;
        }
        .create-time{
            font-size: 10px;

        }
        .status{
            font-size: 12px;
            margin: 0;
            padding: 2px 6px;
            border: solid 1px #000;
            border-radius: 10px;
        }
        .status.declined{
            color: #fff;
            background: red;
            border-color: red;
        }
        .status.received{
            color: #fff;
            background: green;
            border-color: green;
        }
        .status.pending-receive{
            color: #fff;
            background: #ff9900fc;
            border-color: #ff9900fc;
        }
        .update-row:focus,
        .update-row:hover,
        .update-row {
            border: solid #ccc 1px;
            text-decoration: none;
            padding: 4px 5px;
            text-transform: uppercase;
            font-size: 12px;
            color: #fff;
            background: #333;
        }
        .update-row:hover{
            color:red;
        }
        .product-barcode{
            color: green;
            font-size: 12px;
            font-weight: bold;
        }
        .warehouse-product-qty{
            padding: 0 20px;
            position: relative;
        }
        .product-qty-warehouse{
            text-align: center;
            max-width: 80px;

        }
        .reduct-qty,
        .plus-qty{
            position:absolute;
            top: calc(50% - 12px);
        }
        .reduct-qty{
            left: 0;
            
        }
        .plus-qty{
            right:0;
        }
        
        .increase{
            background:green;
        }
        .decrease{
            background:red;
        }
        #op-transfer-grid  td{
            vertical-align: middle;
        }
        #op-transfer-grid  td p,
        #op-transfer-grid  td form{
            margin:0;
            padding:0;
        }
    </style>

   <script>
       var transfer_app_ajax = '<?php echo admin_url('admin-ajax.php?session='.$session['session']);?>';
   </script>
    <?php
    $handes = array(
        'openpos.stocktransfer.app.style'
    );
    wp_print_styles($handes);
    ?>
</head>
<body>
<div class="transfers-container container-fluid" style="padding-top: 15px;">
    
    <div class="row">
        <div class="col-md-12" style="padding-top: 5px;">
            <table class="table table-striped" id="op-transfer-grid">
                <thead>
                    <tr>
                    <th>#</th>
                    <th><?php echo __('Product','woocommerce-openpos-adjust-stock');?></th>
                    
                    <th><?php echo __('Price','woocommerce-openpos-adjust-stock');?></th>
                    <th><?php echo __('Qty','woocommerce-openpos-adjust-stock');?></th>
                    <th><?php echo __('Adjust','woocommerce-openpos-adjust-stock');?></th>
                    <th><?php echo __('Action','woocommerce-openpos-adjust-stock');?></th>
                    </tr>
                </thead>
            
                </table>
        </div>
    </div>
</div>
</body>
<?php
$handes = array(
    'openpos.adjust.stock.app.script'
);

wp_print_scripts(apply_filters('adjust_stock_app_js',$handes));
?>

<script type="text/javascript">
    
    (function($) {
        "use strict";
        var custom_rows = {};
        var table = $('#op-transfer-grid').DataTable({
            "processing": true,
            "serverSide": true,
            responsive: true,
            order: [[0, 'desc']],
            ajax: {
                url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                type: 'post',
                data: {action: 'app_get_adjust_stocks',session: '<?php echo $session['session']; ?>'}
            },
            pageLength : 10,
            "language": {
                    "emptyTable":     "<?php echo __( 'No data available in table', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "info":           "<?php echo __( 'Showing _START_ to _END_ of _TOTAL_ entries', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "infoEmpty":      "<?php echo __( 'Showing 0 to 0 of 0 entries', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "infoFiltered":   "<?php echo __( '(filtered from _MAX_ total entries)', 'woocommerce-openpos-adjust-stock' ); ?>",
                
                    "lengthMenu":     "<?php echo __( 'Show _MENU_ entries', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "loadingRecords": "<?php echo __( 'Loading...', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "processing":     "<?php echo __( 'Processing...', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "search":         "<?php echo __( 'Search:', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "zeroRecords":    "<?php echo __( 'No matching records found', 'woocommerce-openpos-adjust-stock' ); ?>",
                    "paginate": {
                        "first":      "<?php echo __( 'First', 'woocommerce-openpos-adjust-stock' ); ?>",
                        "last":       "<?php echo __( 'Last', 'woocommerce-openpos-adjust-stock' ); ?>",
                        "next":       "<?php echo __( 'Next', 'woocommerce-openpos-adjust-stock' ); ?>",
                        "previous":   "<?php echo __( 'Previous', 'woocommerce-openpos-adjust-stock' ); ?>"
                    },
                    "aria": {
                        "sortAscending":  ": activate to sort column ascending",
                        "sortDescending": ": activate to sort column descending"
                    }
                }
        } );
       
        $(document).on('keyup','.product-qty-warehouse',function (e) { 
            var id = $(this).data('id');
            custom_rows[id] = $(this).val();
           
        });
        $(document).on('click','.update-row',function(){
            var id = $(this).data('id');
            var update_action = $(this).data('action');
            var input_selected = $(document).find('#input-qty-'+id);

            table.rows().nodes().to$().find( "input[name='qty["+id+"]']" ).each(function( index, element ) {
                  $(element).val();
            });

            var qty = input_selected.val();
            if(custom_rows[id])
            {
                qty = custom_rows[id];
            }
           
            $.ajax({
                url: "<?php echo admin_url( 'admin-ajax.php?session='.$session['session'] ); ?>",
                type: 'post',
                data: {action: 'op_app_stock_update',id: id,update_action:update_action, qty : qty },
                dataType: 'json',
                beforeSend:function(){
                    input_selected.prop('disabled',true);
                },
                success: function(response){
                    input_selected.prop('disabled',false);
                    table.ajax.reload( null, false );
                }
                
            });
            

        });
        $(document).on('click','.plus-qty',function(){
            var current_value = $(this).closest('p').find('input').val();
            var new_value =  parseInt(current_value) + 1;
            var id = $(this).data('id');
           $(this).closest('p').find('input').val(new_value);
           custom_rows[id] = new_value;
        });
        $(document).on('click','.reduct-qty',function(){
            var current_value = $(this).closest('p').find('input').val();
            if(current_value > 0)
            {
                var new_value =  parseInt(current_value) -1;
                var id = $(this).data('id');
                $(this).closest('p').find('input').val(new_value);
                custom_rows[id] = new_value;
            }
            
        });

    })( jQuery );
</script>