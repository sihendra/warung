<?php

// Create some random text-encoded data for a line chart.
header('content-type: image/png');
$url = 'https://chart.googleapis.com/chart?chid=' . md5(uniqid(rand(), true));

$module = $_REQUEST['mod'];

if ($module == 'order') {
    
    // data
    $o = new OrderService();
    $res = $o->getOrderStat();
    if ($res) {
        

        // X:
        // 0:|July|Agustus
        $chxl = '0:';
        
        // Y
        // t:29,15|2,0|2,0|3,0
        $chd = 't:';
        foreach($res as $row) {
            $chxl .= '|'.$row->month ;
            $chd .= $row->total . ',';
        }
        
        $chd = substr($chd, 0, -1);
        $chxl = substr($chd, 0, -1);
        
        
        // compose params
        $chart = array(
            'chf' => 'a,s,000000CD',                                   
            'chxl' => $chxl,                               
            'chxr' => '0,1,12',                                        
            'chxt' => 'x,y',                                           
            'chbh' => 'a',                                             
            'chs' => '300x225',                                        
            'cht' => 'bvg',                                            
            'chco' => 'A2C180,3D7930,FF9900,FF9900',                   
            'chd' => $chd,                            
            'chdl' => 'Ordered|Delivered|Received|Payment+Verified',   
            'chg' => '0,-1,0,4',                                       
            'chma' => '|0,5',                                          
            'chtt' => 'Order Per Bulan');

    }
}




//http://chart.apis.google.com/chart
//   ?chf=a,s,000000CD                                      -- bg fill= <fill_type>,s,<color>|       
//   &chxl=0:|July|Agustus                                  -- custom axis label =<axis_index>:|<label_1>|...|<label_n>
//   &chxr=0,1,12                                           -- axis range = <axis_index>,<start_val>,<end_val>,<opt_step>
//   &chxt=x,y                                              -- visible axis                      
//   &chbh=a                                                -- bar width and spacing= <bar_width_or_scale>,<space_between_bars>,<space_between_groups>
//   &chs=300x225                                           -- chart size
//   &cht=bvg                                               -- chart tipe
//   &chco=A2C180,3D7930,FF9900,FF9900                      -- Series color = <series_1_color>, ..., <series_n_color>          
//   &chd=t:29,15|2,0|2,0|3,0                               -- data=t:val,val,val|val,val,val...
//   &chdl=Ordered|Delivered|Received|Payment+Verified      -- Legend label=<data_series_1_label>|...|<data_series_n_label>
//   &chg=0,-1,0,4                                          -- grid line = <x_axis_step_size>,<y_axis_step_size>,<opt_dash_length>,<opt_space_length>,<opt_x_offset>,<opt_y_offset>
//   &chma=|0,5                                             -- chart margins= <left_margin>,<right_margin>,<top_margin>,<bottom_margin>|<opt_legend_width>,<opt_legend_height>
//   &chtt=Order+Per+Bulan                                  -- Title=<chart_title>

// Send the request, and print out the returned bytes.
$context = stream_context_create(
        array('http' => array(
                'method' => 'POST',
                'content' => http_build_query($chart))));
fpassthru(fopen($url, 'r', false, $context));
?>
