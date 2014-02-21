<?php 

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: overview.php 1676 2010-01-20 02:50:34Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

$filename = "rsvp_export.csv";

$rsvpdata = $this->rsvpdata;

$data = array();

$headerrow = array(JText::_('RSVP_ATTENDEE_NUMBER'), JText::_('RSVP_ATTENDEE'), JText::_('RSVP_ATTENDEE_NAME'), JText::_('RSVP_ATTENDEE_EMAIL'), JText::_('RSVP_ATTENDEE_USERNAME'), JText::_('RSVP_CONFIRMED'),JText::_('JEV_WAITING'),JText::_('RSVP_ATTEND_STATUS'),JText::_('RSVP_ATTENDED'));
$colcount = 5;
if (count($this->rows)>0){

	$row= $this->rows[0];

	$template = $rsvpdata->template;
	// New parameterised fields
	if ($template!=""){
		$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

		if (is_int($xmlfile) || file_exists($xmlfile)) {
			$params = new JevRsvpParameter("",$xmlfile,$rsvpdata, $row);
			$params  = $params->renderToBasicArray();
			foreach ($params as $param) {
				if ($param['label']!="")	{
					$headerrow [] = JText::_($param['label']);
				}
			}
		}
	}
	$headerrow [] = JText::_('JEV_REGISTRATION_TIME');
	$headerrow [] =  JText::_('JEV_MODIFICATION_TIME');
	$data[]=$headerrow;

	$attendstate = array(	JText::_('RSVP_NOT_ATTENDING'), JText::_('RSVP_ATTENDING'), JText::_('RSVP_MAYBE_ATTENDING'), JText::_('RSVP_PENDING_APPROVAL'), JText::_('RSVP_OUTSTANDING_BALANCE'));

	$n=count( $this->rows);
	for( $i=0; $i < $n; $i++ ){
		$row = &$this->rows[$i];
		$attendee= $row;
		
		$guestcount = (isset($attendee->guestcount) && $attendee->guestcount>1) ?   $attendee->guestcount : 1;
		for($guest=0; $guest<$guestcount; $guest ++){
			$datarow = array();
			$datarow[] = $row->atdee_id;
			$datarow[] = $row->attendee;
			$datarow[] = $row->attendeename;
			$datarow[] = $row->attendeemail;
			$datarow[] = $row->attendeeusername ;
			$datarow[] = $row->confirmed;
			$datarow[] = $row->waiting;
			$datarow[] = $attendstate[$row->attendstate];
			$datarow[] = $row->didattend;

			// New parameterised fields
			if ($rsvpdata->template!=""){
				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if (is_int($xmlfile) || file_exists($xmlfile)) {
					// transfer attendee specific information into the event row
					$eventrow = clone $this->repeat;
					foreach (get_object_vars($attendee) as $key => $val)
					{
						$eventrow->$key = $val;
					}
					if (isset($attendee->params)){
						$params = new JevRsvpParameter($attendee->params,$xmlfile,$rsvpdata, $eventrow);
						$feesAndBalances = $params->outstandingBalance($attendee);
					}
					else {
						$params = new JevRsvpParameter("",$xmlfile,$rsvpdata, $eventrow);
						$feesAndBalances =  false;
					}

					$params  = $params->renderToBasicArray('xmlfile', $attendee);
					foreach ($params as $param) {
						if ($param['label']!="")  {
							if ($param["peruser"]<=0){
								$datarow[] = $param['value'];
							}
							else if ($param["peruser"]==1){
								if (!is_array($param['value']) && $guest ==0){
									$datarow[] = $param['value'];
								}
								else if (array_key_exists($guest,$param['value'])){
									$datarow[] = $param['value'][$guest];
								}							
								else {
									$datarow[] = "";
								}
							}
							else if ($param["peruser"]==2){
								if (array_key_exists($guest,$param['value'])){
									$datarow[] = $param['value'][$guest];
								}							
								else {
									$datarow[] = "";
								}
							}
						}
					}

				}
			}
			$datarow[] = $row->created;
			$datarow[] = $row->modified;
			
			$data[] = $datarow;
		}
	}


	$data = exportAsCSV($data);

	// Finally, generate a file
	$size = strlen($data);

	@ob_end_clean();
	@ini_set("zlib.output_compression", "Off");
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: private");
	header("Content-Type: text/csv; charset=utf-8");
	header("Content-Disposition: attachment; filename=$filename");
	header("Accept-Ranges: bytes");
	header("Content-Length: $size");
	// force UTF-8 BOM headers in file - see http://stackoverflow.com/questions/5368150/php-header-excel-and-utf-8
	echo pack('CCC',0xef,0xbb,0xbf);
	echo $data;
	exit();
}

function outputCSV($data) {
	ob_start();
    $outstream = fopen("php://output", 'w');

    function __outputCSV(&$vals, $key, $filehandler) {
		// TODO THIS IS NOT CORRECT!!
	$temp = array();
	foreach ($vals as $val){
		if (is_array($val)){
			$val = implode(", ",$val);
		}
		/*
		if (strpos($val, "0")===0){
			$val .= " ";
		}
		 */
		$temp[] = $val;
	}
        fputcsv($filehandler, $temp, ',', '"');
    }
    array_walk($data, '__outputCSV', $outstream);

    fclose($outstream);
	return ob_get_clean();
}

function exportAsCSV( &$exportData ) {
	return  outputCSV($exportData);
	/*
	// default csv options
	$titles = true;
	$fieldTerminator = ",";
	$fieldEnclosed = "\"";
	$fieldEscaped = "\\";
	$lineTerminator = "\n";

	$data = '';
	foreach ( $exportData as $record ) {

		// parse each field
		foreach ( $record as $field ) {
			$data .= $fieldEnclosed.str_replace($fieldEnclosed, $fieldEscaped.$fieldEnclosed, $field).$fieldEnclosed;
			$data .= $fieldTerminator;
		} // foreach row

		// Remove the dangling fieldTerminator, and add a lineTerminator
		$data = substr($data,0,strlen($data) - strlen($fieldTerminator)) . $lineTerminator;

	} // foreach record

	return $data;
	 * */

}
