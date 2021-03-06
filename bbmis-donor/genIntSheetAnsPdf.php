<?php
	include "connection.php";
	session_start();
	$varDbId = $_SESSION["sessId"];

	$qryFetchClientId = mysqli_query($conn,"
		SELECT intClientId
		FROM tblclient
		WHERE intClientId = '$varDbId'
	");
	while($rowFetchClientId = mysqli_fetch_assoc($qryFetchClientId));
	$varClientId = $rowFetchClientId["intClientId"];

	require("../public/fpdf/fpdf.php");

	$pdf = new FPDF('P', 'in', 'Letter');
	$pdf -> SetMargins(0, 0.5, 0);
	$pdf -> SetFont('Times','',12);
	$pdf -> AddPage();

	$pdf -> Image("../public/assets/logo-a1.png", 0.35, 0.35, 1.75);

	$pdf -> SetFont('Arial', 'B', 20);
	$pdf -> Text(2.25, 0.95, 'Medical Exam Interview Sheet');

	$qryFetchSheetVer = mysqli_query($conn, "
		SELECT DISTINCT(q.decQuestionVersion)
		FROM tblquestion q
		JOIN tblmedicalexam me ON q.intQuestionId = me.intQuestionId
		WHERE me.intDonationId = (
			SELECT me1.intDonationId
			FROM tbldonation d
			JOIN tblmedicalexam me1 ON d.intDonationId = me1.intDonationId
			WHERE d.intClientId = '$clientid'
			LIMIT 1
		)
	");

	while($rowVer = mysqli_fetch_assoc($qryFetchSheetVer))	{
		$pdf -> SetFont('Times', '', 10);
		$pdf -> Text(2.25, 1.15, 'v' . $rowVer["decQuestionVersion"]);
	}

	$pdf -> Line(0.5, 1.65, 8, 1.65);

	$qryFetchClientInfo = mysqli_query($conn, "
		SELECT c.strClientFirstName, c.strClientMiddleName, c.strClientLastName, d.intDonationId, me.dtmExamTaken
		FROM tblclient c
		JOIN tbldonation d ON c.intClientId = d.intClientId
		JOIN tblmedicalexam me ON d.intDonationId = me.intDonationId
		WHERE c.intClientId = '$clientid'
		ORDER BY 5 DESC
		LIMIT 1
	");

	while($rowClientInfo = mysqli_fetch_assoc($qryFetchClientInfo))	{
		$varFname = $rowClientInfo["strClientFirstName"];
		$varMname = $rowClientInfo["strClientMiddleName"];
		$varLname = $rowClientInfo["strClientLastName"];

		if($varMname == '')	{
			$varFullName1 = $varLname . ', ' . $varFname;
			$varFullName2 = $varFname . ' ' . $varLname;
		}
		else	{
			$varFullName1 = $varLname . ', ' . $varFname . ' ' . $varMname;
			$varFullName2 = $varFname . ' ' . $varMname . ' ' . $varLname;
		}

		$pdf -> SetFont('Arial', '', 12.5);
		$pdf -> Text(0.75, 2, 'Name : ' .  strtoupper($varFullName1));
		$varDonationId = $rowClientInfo["intDonationId"];
		$varExamTaken = $rowClientInfo["dtmExamTaken"];
	}

	$varDonCode = $varDonationId . date_format(date_create($varExamTaken), 'Y') . date_format(date_create($varExamTaken), 'm') . date_format(date_create($varExamTaken), 'd');

	$pdf -> Text(4.25, 2, 'Donation Code : ' . $varDonCode);

	$pdf -> Text(4.25, 2.25, 'Date Taken : ' . date_format(date_create($varExamTaken), 'F d, Y'));

	$pdf -> Line(0.5, 2.50, 8, 2.50);

	$pdf -> Ln(2.25);

	$qryFetchQueCtg = mysqli_query($conn, "
		SELECT DISTINCT(qc.stfQuestionCategory)
		FROM tblquestioncategory qc
		JOIN tblquestion q ON qc.intQuestionCategoryId = q.intQuestionCategoryId
		JOIN tblmedicalexam me ON q.intQuestionId = me.intQuestionId
		WHERE me.intDonationId = (
			SELECT intDonationId
			FROM tbldonation
			WHERE intClientId = '$clientid'
			ORDER BY 1 DESC
			LIMIT 1
		)
	");

	//Distincted Question Category used.
	$varCountQueCtg = mysqli_num_rows($qryFetchQueCtg);

	//Item counter.
	$varCountItems = 1;

	for($varOffset=0; $varOffset<$varCountQueCtg; $varOffset++)	{
		$qryFetchQueCtgOff = mysqli_query($conn, "
			SELECT DISTINCT(qc.stfQuestionCategory)
			FROM tblquestioncategory qc
			JOIN tblquestion q ON qc.intQuestionCategoryId = q.intQuestionCategoryId
			JOIN tblmedicalexam me ON q.intQuestionId = me.intQuestionId
			WHERE me.intDonationId = (
				SELECT MAX(d1.intDonationId)
				FROM tbldonation d1
				WHERE d1.intClientId = '$clientid'
				ORDER BY 1 DESC
			)
			LIMIT 1 OFFSET $varOffset
		");

		//Question Category heading
		while($rowQueCtgOff = mysqli_fetch_assoc($qryFetchQueCtgOff))	{
			$varQueCtgOff = $rowQueCtgOff["stfQuestionCategory"];
			$pdf -> SetFont('Times', 'B', 12);
			$pdf -> SetTextColor(255, 255, 255);
			$pdf -> SetFillColor(220, 53, 69);
			$pdf -> SetX(0.5);
			$pdf -> Cell(7.5, 0.3, $varQueCtgOff, 1, 1, 'C', true);

			$pdf -> SetX(0.5);
			$pdf -> SetFont('Times', 'B', 12);
			$pdf -> SetTextColor(0, 0, 0);
			$pdf -> Cell(0.35, 0.3, 'No.', 'LB', 0, 'C');
			$pdf -> Cell(3.575, 0.3, 'Question', 'RBL', 0, 'C');
			$pdf -> Cell(3.575, 0.3, 'Answer/s', 'RB', 1, 'C');
		}

		$qryFetchItemAns = mysqli_query($conn, "
			SELECT q.txtQuestion, me.stfAnswerYn, me.strAnswerString, me.datAnswerDate, me.intAnswerQuantity
			FROM tblquestion q
			JOIN tblmedicalexam me ON q.intQuestionId = me.intQuestionId
			WHERE me.intDonationId = (
				SELECT me1.intDonationId
				FROM tbldonation d
				JOIN tblmedicalexam me1 ON d.intDonationId = me1.intDonationId
				WHERE d.intClientId = '$clientid'
				LIMIT 1
			)
			AND q.intQuestionCategoryId = (
				SELECT intQuestionCategoryId
				FROM tblquestioncategory
				WHERE stfQuestionCategory = '$varQueCtgOff'
			)
		");

		while($rowItemAns = mysqli_fetch_assoc($qryFetchItemAns))	{
			// Attempt 3
			$varWdNo = 0.35;
			$varWdQA = 3.575;

			$pdf -> SetFont('Times', '', 11.5);
			$pdf -> SetTextColor(0, 0, 0);
			$pdf -> SetX(0.85);
			$pdf -> MultiCell($varWdQA, 0.25, $rowItemAns["txtQuestion"], 'LRB');
			$varGetY = $pdf -> GetY();
			$varGetX = $pdf -> GetX();
			$pdf -> SetXY(0.85 + $varGetX + $varWdQA, $varGetY - 0.25);

			$varAnsYn = $rowItemAns["stfAnswerYn"];
			if($varAnsYn == '')	{
				$varAnsYn = '';
			}
			else	{
				if($varAnsYn == 'Yes')	{
					$varAnsYn = 'Oo';
				}
				else if($varAnsYn == 'No')	{
					$varAnsYn = 'Hindi';
				}
			}

			$varAnsStr = $rowItemAns["strAnswerString"];
			if($varAnsStr == '')	{
				$varAnsStr = '';
			}

			$varAnsDat = $rowItemAns["datAnswerDate"];
			if($varAnsDat == '0000-00-00')	{
				$varAnsDat = '';
			}
			else	{
				$varAnsDat = date_format(date_create($rowItemAns["datAnswerDate"]), 'F d Y');
			}

			$varAnsQua = $rowItemAns["intAnswerQuantity"];
			if($varAnsQua == 0)	{
				$varAnsQua = '';
			}

			$pdf -> Cell($varWdQA, 0.25, $varAnsYn . ' ' . $varAnsStr . ' ' . $varAnsDat . ' ' . $varAnsQua, 'TRB');
			$pdf -> SetXY(0.5, $varGetY - 0.25);
			$pdf -> MultiCell($varWdNo, 0.25, $varCountItems, 'TLB');

			$varCountItems++;
		}

		$pdf -> Ln(0.25);
	}

	$pdf -> SetMargins(0.5, 0.5, 0.5);

	$pdf -> SetFont('Times', 'B', 12);
	$pdf -> Write(0.25, "
	DONOR'S CONSENT (PAHINTULOT)
	");

	$pdf -> Ln(0.05);

	$pdf -> SetFont('Times', '', 12);
	$pdf -> Write(0.25, "
	     Nagpapatunay na ako ang taong tinutukoy at ang lahat ng nakasulat dito ay nabasa ko at naiintindihan at ako ay kusang-loob na magbigay ng dugo. Alam ko ang mga panganib at kahihihatnan sa panahong pagkuha ng dugo sa akin at pagkatapos ng donasyon. Ito ay ipinaliwanag sa akin at naintindihan ko ng mabuti.

	     Pagkatapos masagutan ng buong katapatan ang mga tanong, ako ay kusa at buong-loob na magbibigay ng dugo sa Blood Bank ng Our Lady of Lourdes Hospital. Naiintindihan ko na ang aking dugo ay susuriin ng mabuti upang malaman ang blood type, hematocrit, hemoglobin, malaria, syphilis, Hepatitis B & C, at HIV.
	");

	$pdf -> Ln(1);
	$pdf -> SetFont('Times', '', 13);
	$pdf -> SetX(4.5);
	$pdf -> Cell(3, 0.3, $varFullName2, 'B', 1, 'C');
	$pdf -> SetFont('Times', '', 11.5);
	$pdf -> SetX(4.5);
	$pdf -> Cell(3, 0.3, 'Signature over Printed Name', 0, 0, 'C');

	$pdf -> Output();
?>
