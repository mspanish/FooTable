<?php error_log(__FILE__);

error_log(var_export($_REQUEST,true));

    $aColumns = array( 'engine', 'browser', 'platform', 'version', 'grade' );

    /* Indexed column (used for fast and accurate table cardinality) */
    $sIndexColumn = "id";

    /* DB table to use */
    $sTable = "ajax";

    /* Database connection information */
    $gaSql['user']       = "root";
    $gaSql['password']   = "1qaz@WSX";
    $gaSql['db']         = "test";
    $gaSql['server']     = "localhost";


    function fatal_error ( $sErrorMessage = '' )
    {
        header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
        die( $sErrorMessage );
    }

    if ( ! $gaSql['link'] = mysql_pconnect( $gaSql['server'], $gaSql['user'], $gaSql['password']  ) )
    {
        fatal_error( 'Could not open connection to server' );
    }

    if ( ! mysql_select_db( $gaSql['db'], $gaSql['link'] ) )
    {
        fatal_error( 'Could not select database ' );
    }


    /*
     * Paging
     */
    $sLimit = "";
    if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
    {
        $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
            intval( $_GET['iDisplayLength'] );
    }


    /*
     * Ordering
     */
    $sOrder = "";
    if ( isset( $_GET['iSortCol_0'] ) )
    {
        $sOrder = "ORDER BY  ";
        for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
        {
            if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
            {
                $sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
                    ($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
            }
        }

        $sOrder = substr_replace( $sOrder, "", -2 );
        if ( $sOrder == "ORDER BY" )
        {
            $sOrder = "";
        }
    }


    /*
     * Filtering
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here, but concerned about efficiency
     * on very large tables, and MySQL's regex functionality is very limited
     */
    $sWhere = "";
    if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
    {
        $sWhere = "WHERE (";
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            $sWhere .= "`".$aColumns[$i]."` LIKE '%".mysql_real_escape_string( $_GET['sSearch'] )."%' OR ";
        }
        $sWhere = substr_replace( $sWhere, "", -3 );
        $sWhere .= ')';
    }

    /* Individual column filtering */
    for ( $i=0 ; $i<count($aColumns) ; $i++ )
    {
        if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
        {
            if ( $sWhere == "" )
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
            $sWhere .= "`".$aColumns[$i]."` LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
        }
    }


    /*
     * SQL queries
     * Get data to display
     */
    $sQuery = "
        SELECT SQL_CALC_FOUND_ROWS `".str_replace(" , ", " ", implode("`, `", $aColumns))."`
        FROM   $sTable
        $sWhere
        $sOrder
        $sLimit
        ";
    $rResult = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );

    /* Data set length after filtering */
    $sQuery = "
        SELECT FOUND_ROWS()
    ";
    $rResultFilterTotal = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
    $aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];

    /* Total data set length */
    $sQuery = "
        SELECT COUNT(`".$sIndexColumn."`)
        FROM   $sTable
    ";
    $rResultTotal = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
    $aResultTotal = mysql_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];


    /*
     * Output
     */
    $output = array(
        "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );

    while ( $aRow = mysql_fetch_array( $rResult ) )
    {
        $row = array();
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( $aColumns[$i] == "version" )
            {
                /* Special output formatting for 'version' column */
                $row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
            }
            else if ( $aColumns[$i] != ' ' )
            {
                /* General output */
                $row[] = $aRow[ $aColumns[$i] ];
            }
        }
        $output['aaData'][] = $row;
    }

//    echo json_encode( $output );

//error_log(var_export($output, true));

echo <<<EOS
<tr><td>Annea</td><td>Dewitt</td><td>Clinical Services Director</td><td data-value="456557780410">20 Jun 1984</td><td data-value="1">Active</td></tr>
<tr><td>Gwyn</td><td>Hyland</td><td>Childcare Center Administrator</td><td data-value="-195250065036">25 Oct 1963</td><td data-value="3">Suspended</td></tr>
<tr><td>Marline</td><td>Guilford</td><td>Childrens Pastor</td><td data-value="-90818037567">15 Feb 1967</td><td data-value="2">Disabled</td></tr>
<tr><td>Ardelia</td><td>Mosher</td><td>Offbearer</td><td data-value="-5040496561">4 Nov 1969</td><td data-value="3">Suspended</td></tr>
<tr><td>Myesha</td><td>Mcfetridge</td><td>Geophysical Engineer</td><td data-value="-310243862580">3 Mar 1960</td><td data-value="2">Disabled</td></tr>
EOS;
