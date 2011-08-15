<?php

class WarungChart {
    public static function getOrderChartURL($width, $height) {
        $url = 'https://chart.googleapis.com/chart?chid=' . md5(uniqid(rand(), true));
        $chart = array();
        
        // data
        $o = new OrderService();
        $res = $o->getOrderStat();
//      $res = array(
//          (object)array("month"=>"Jul","status"=>"Ordered","total"=>31),
//          (object)array("month"=>"Agt","status"=>"Ordered","total"=>15),
//          (object)array("month"=>"Jul","status"=>"Delivered","total"=>2),
//          (object)array("month"=>"Jul","status"=>"Received","total"=>1),
//      );
//        
        if ($res) {

            // normalize res

            // X:
            // 0:|July|Agustus
            $chxl = '0:';

            // Y
            // t:29,15|2,0|2,0|3,0
            $chd = 't:';

            // Series label
            $chdl = '';

            $currentX = array();
            $currentS = array();
            foreach($res as $row) {
                // X
                if (sizeof($currentX) == 0 || !isset($currentX[$row->month])) {
                    $chxl .= '|'.$row->month ; 
                    $currentX[$row->month]=$row->month;
                }

                // DATA & SERIES
                if (sizeof($currentS) == 0) {
                    $currentS[$row->status] = $row->status;
                    $chd .= $row->total;
                    $chdl .= $row->status;
                } else if (! isset($currentS[$row->status])) {
                    $currentS[$row->status] = $row->status;
                    $chd .= '|' . $row->total;
                    $chdl .= '|' . $row->status;
                } else {
                    $chd .= ',' . $row->total;  
                }


            }

        
            // compose params
            $chart = array(
                'chf' => 'a,s,000000CD',                                   
                'chxl' => $chxl,                               
                'chxr' => '0,1,12',                                        
                'chxt' => 'x,y',                                           
                'chbh' => 'a',                                             
                'chs' => "$width"."x"."$height",                                        
                'cht' => 'bvg',                                            
                'chco' => 'A2C180,3D7930,FF9900,99FF00,CF0099',                   
                'chd' => $chd,                            
                'chdl' => $chdl,   
                'chg' => '0,-1,0,4',                                       
                'chma' => '|0,5',                                          
                'chtt' => 'Order Per Bulan',
                'chm' => 'N,000000,0,-1,11|N,000000,1,-1,11|N,000000,2,-1,11'
                
            );

        }
        
        return $url.'&'.http_build_query($chart);

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
//$context = stream_context_create(
//        array('http' => array(
//                'method' => 'POST',
//                'content' => http_build_query($chart))));
//fpassthru(fopen($url, 'r', false, $context));
?>
