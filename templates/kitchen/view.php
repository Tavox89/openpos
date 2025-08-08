<div class="container">
    <div class="header-container" id="header-container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h3><?php echo __('KitChen View','openpos'); ?></h3>
            </div>
        </div>
        <div class="row kitchen-control-container">
            <div class="col-sm-2 col-md-2 col-xs-12 pull-left grid-view-control" >
                        <a href="javascript:void(0);" data-id="items" class="grid-view <?php echo $grid_type == 'items' ? 'selected':'' ; ?>">
                            <span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>
                        </a>
                        <a href="javascript:void(0);" data-id="orders" class="grid-view <?php echo $grid_type == 'orders' ? 'selected':'' ; ?>">
                            <span class="glyphicon glyphicon-th-large" aria-hidden="true"></span>
                        </a>
            </div>
            <div class="col-md-8 col-sm-8 col-xs-8 grid-view-area">
                <div class="col-md-6 col-md-offset-3">
                    <form class="form-horizontal"  action="<?php echo $kitchen_url ; ?>" id="kitchen-form" method="get">
                        <div class="form-group">
                            <label for="inputEmail3" class="col-sm-3 col-xs-3 col-md-3 control-label"><?php echo __('Area','openpos'); ?></label>
                            <div class="col-sm-8 col-xs-8 col-md-8">
                                    <select class="form-control" name="type">
                                        <option value="all" <?php echo ($kitchen_type == 'all') ? 'selected':'';?> > <?php echo __('All','openpos'); ?></option>
                                        <?php foreach($all_area as $a_code => $area): ?>
                                            <option value="<?php echo esc_attr($a_code); ?>" <?php echo ($kitchen_type == $a_code ) ? 'selected':'';?> ><?php echo $area['label']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="display" value="<?php echo $grid_type; ?>"  />
                                    <input type="hidden" name="id" value="<?php echo $id ; ?>"  />
                                    
                                    <input type="submit" style="display:none;" />
                            </div>
                            
                        </div>

                    </form>
                </div>
            </div>
            <div class="col-sm-2 col-md-2 pull-right grid-view-reload" style="text-align:right;">
                        <a href="javascript:void(0);" data-id="<?php echo $id; ?>" id="refresh-kitchen"> <span class="glyphicon glyphicon-retweet" aria-hidden="true"></span> </a>
            </div>
        </div>
    </div>
    <div  id="bill-content">
        <?php if($grid_type == 'items'): ?>
            <div id="bill-content-items" class="bill-content-container">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th><?php echo __('Item','openpos'); ?></th>
                            <th class="text-center"><?php echo __('Qty','openpos'); ?></th>
                            <th><?php echo __('Order Time','openpos'); ?></th>
                            <th><?php echo __('Table / Order','openpos'); ?></th>
                            <th class="text-center"><?php echo __('Ready ?','openpos'); ?></th>
                        </tr>
                        </thead>
                        <tbody id="kitchen-table-body">

                        </tbody>
                    </table>
            </div>
        <?php else: ?>
            <div id="bill-content-orders" class="bill-content-container" >
                <div id="kitchen-table-body"></div>
            </div>
            
        <?php endif; ?>
    </div>
 
</div>
<?php if($grid_type != 'items'): ?>
 <div id="bill-content-page-container">
    <div class="top-control">
        <a href="javascript:void(0)" data-action="setting"  class="page-menu"><span class="glyphicon glyphicon-wrench" aria-hidden="true"></span></a>
    </div>
    <div class="mid-control" id="bill-pagination"></div>
    <div class="bottom-control">
        <a href="javascript:void(0)" data-action="refresh" class="page-menu"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></a>
    </div>

    <a href="javascript:void(0)" class="page-menu-arrow" id="page-menu-arrow">
        <span class="glyphicon glyphicon-triangle-right menu-close" aria-hidden="true"></span>
        <span class="glyphicon glyphicon-triangle-left menu-open " aria-hidden="true"></span>
    </a>
       
 </div>
 <?php endif; ?>
 <!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Setting</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form class="form-horizontal">
            <div class="form-group">
                <label for="inputEmail3" class="col-sm-2 col-xs-4 control-label">Columns</label>
                <div class="col-sm-6 col-xs-8">
                    <div class="row">
                        <div class="col-sm-4 col-xs-4">
                            <input class="btn btn-default pull-right grid-setting-action" data-action="reduct" data-type="column"  type="button" value="-">
                        </div>
                        <div class="col-sm-4 col-xs-4">
                            <input type="number" disabled class="form-control" id="input-column">
                        </div>
                        <div class="col-sm-4 col-xs-4">
                            <input class="btn btn-default pull-left grid-setting-action" data-action="increase" data-type="column" type="button" value="+">
                        </div>
                    </div>
               
                </div>
            </div>
            <div class="form-group">
                <label for="inputPassword3" class="col-sm-2 col-xs-4 control-label">Rows</label>
                <div class="col-sm-6 col-xs-8">
                    <div class="row">
                        <div class="col-sm-4 col-xs-4">
                            <input class="btn btn-default pull-right grid-setting-action" data-action="reduct" data-type="row" type="button" value="-">
                        </div>
                        <div class="col-sm-4 col-xs-4">
                            <input type="number" disabled class="form-control" id="input-row">
                        </div>
                        <div class="col-sm-4 col-xs-4">
                            <input class="btn btn-default pull-left grid-setting-action" data-action="increase" data-type="row" type="button" value="+">
                        </div>
                    </div>
                    
                </div>
            </div>
            
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        
      </div>
    </div>
  </div>
</div>