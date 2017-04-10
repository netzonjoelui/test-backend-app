<?php

include_once(dirname(__FILE__) . '/Pdf/class.pdf.php');
include_once(dirname(__FILE__) . '/Pdf/class.ezpdf.php');

define("AL_ROOT", dirname(__FILE__));

/**
 * Wrapper class for generating PDF documents. Currently backed with RO&S and EZPdf classes
 */


/**
 * PDF wrapper class
 */
class ALib_Pdf
{
	/**
	 * Handle to ezpdf class
	 *
	 * @var Cezpdf
	 */
	public $ezPdf = null;

	/**
	 * Class constructor
	 */
	function __construct($paper='LETTER',$orientation='portrait')
	{
		$this->ezPdf = new Cezpdf("LETTER", "portrait");
	}

	/**
	 * Set page margins in px
	 */
	public function setMargins($top,$bottom,$left,$right)
	{
		$this->ezPdf->ezSetMargins($top,$bottom,$left,$right);
	}

	/**
	 * Set page margins in centimeters
	 */
	public function setCmMargins($top,$bottom,$left,$right)
	{
		$this->ezPdf->ezSetCmMargins($top,$bottom,$left,$right);
	}

	/**
	 * start from the current y-position, make the set number of columne
	 */
	public function columnStart($options=array())
	{
		$this->ezPdf->ezColumnsStart($options);
	}

	public function columnStop()
	{
		$this->ezPdf->ezColumnsStop();
	}

	/**
	 * puts the document into insert mode. new pages are inserted until this is re-called with status=0
	 * by default pages wil be inserted at the start of the documen
	 */
	public function insertMode($status=1,$pageNum=1,$pos='before')
	{
		$this->ezPdf->ezInsertMode($status, $pageNum, $pos);
	}

	/**
	 * Start a new page
	 */
	public function newPage()
	{
		$this->ezPdf->ezNewPage();
	}

	/**
	 * Put page numbers on the pages from here
	 */
	public function startPageNumbers($x,$y,$size,$pos='left',$pattern='{PAGENUM} of {TOTALPAGENUM}',$num='')
	{
		$this->ezPdf->ezStartPageNumbers($x,$y,$size,$pos,$pattern,$num);
	}

	/**
	 * Stop putting page numbers on the pages from here
	 */
	public function stopPageNumbers($stopTotal=0,$next=0,$i=0)
	{
		$this->ezPdf->ezStopPageNumbers($stopTotal,$next,$i);
	}

	/**
	 * given a particular generic page number (ie, document numbered sequentially from beginning)
	 *
	 * @return the page number under a particular page numbering scheme ($i
	 */
	public function whatPageNumber($pageNum,$i=0)
	{
		$this->ezPdf->ezWhatPageNumber($pageNum, $i);
	}

	/**
	 * Stream/print document
	 */
	public function stream($options='')
	{
		$this->ezPdf->ezStream($options);
	}

	/**
	 * Set the current y position of this document
	 *
	 * @param int $y The y position in px
	 */
	public function setY($y)
	{
		$this->ezPdf->setY($y);
	}

	/**
	 * Add a table to the PDF
	 */
	public function table(&$data,$cols='',$title='',$options='')
	{
		$this->ezPdf->ezTable($data,$cols,$title,$options);
	}

	/**
	 * Add flowing text
	 */
	public function text($text, $size=0, $options=array(), $test=0)
	{
		$this->ezPdf->ezText($text, $size, $options, $test);
	}

	/**
	 * Add easy image
	 */
	public function image($image,$pad = 5,$width = 0,$resize = 'full',$just = 'center',$border = '')
	{
		$this->ezPdf->ezImage($image,$pad,$width,$resize,$just,$border);
	}
}
