<?php
	require_once("Zend/Pdf.php");

	// It will be called payment_receipt.pdf
	header('Content-type: application/pdf');
	header('Content-Disposition: attachment; filename="payment_receipt' . rand(1, 100) . '.pdf"');
	header("Content-Transfer-Encoding: binary");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	
	$result_message = "Approved";
	$approval_code = "152489";
	$name_on_card = "Jim J. Smith";
	$address = "123 Main Street";
	$address2 = "Apt 203";
	$address3 = "";
	$city = "Anytown";
	$state = "AnyState";
	$zip = "95050";
	$amount = "12.01";
	$transaction_time = "2012-01-11 12:34 Eastern";

	//Create a PDF object
	$pdf = new Zend_Pdf();

	//Uncomment the code below if you want to drop text on top of a previouly created PDF shell.
	//Load a PDF document from a file
	//$pdf = Zend_Pdf::load('library/pdf_shell.pdf');

	// Now that we have a PDF instance, add new page to the document
	//$page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);  //older new page call.  Use if your framework had an older version of Zend_PDF
	$pdf->pages[] = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_LETTER);

	//Now tell the pdf object that you want to work with the first page
	$page = $pdf->pages[0];

	//Draw the address information so that it prints in a envelope window.
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText($name_on_card, 60, 625);

	//Use $add_y and $add_x to track where to draw text on the PDF page
	$add_y = 610;
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText($address, 60, 610);

	if($address2 != ''){
		$add_y = $add_y-15;
		$page->drawText($address2, 60, $add_y);
	}

	if($address3 != ''){
		$add_y = $add_y+15;
		$page->drawText($address3, 60, 610);
	}

	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText($city.", ".$state." ".$zip, 60, $add_y-15);

	//Draw the payment information
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText("Name On Card:", 50, 515);
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
	$page->drawText($name_on_card, 175, 515);

	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText("Transaction Result:", 50, 500);
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
	$page->drawText($result_message, 175, 500);

	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText("Approval Code:", 50, 485);
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
	$page->drawText($approval_code, 175, 485);

	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText("Card # Ending With:", 50, 470);
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
	$page->drawText("1234", 175, 470);

	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText("Transaction Time:", 50, 455);
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
	$page->drawText($transaction_time, 175, 455);

	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 12);
	$page->drawText("Amount:", 50, 440);
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
	$page->drawText(number_format($amount, 2, '.', ','), 175, 440);

	$page->drawLine(50, 425, 550, 425);

	// Put logo in top-left
	// define image resource
	$image = Zend_Pdf_Image::imageWithPath(APPLICATION_PATH . '/../public/images/gig-a-cause-logo-plain.png');

	// write image to page
	$page->drawImage($image, 50, 680, 144, 730);

	//Draw some stuff on the right side of the receipt
	//Draw the large 'Invoice' title
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 16);
	$page->drawText('Company XYZ', 400, 730);
	$page->drawText('CHARGE CARD', 400, 710);
	$page->drawText('PAYMENT RECEIPT', 400, 690);

	//Now add some verbiage at the bottom.
	$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 10);
	$page->drawText("Thank you for your!", 50, 200);

	// now render the pdf and write it to a view variable called pdffile.
	// the pdffile variable is sent to the displaypdf.phtml view
	echo $pdf->render();
	/*
	$pdfData = $pdf->render();
	$this->view->pdffile = $pdfData;
	$this->render('displaypdf');
	 */
