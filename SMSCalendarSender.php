<?if (CModule::IncludeModule("calendar")&&CModule::IncludeModule("iblock")){
	
	$HistoryIBlockID = 31; //ID Инфоблока с записью отправленных SMS
	$TimeBefore = 5;//Время в минутах перед событием для отправки СМС
	
	$Params = array('getUserfields' => true,"FROM_LIMIT" => CCalendar::Date(time(), false),"TO_LIMIT" => CCalendar::Date(time()+($TimeBefore*60), false));
	$arExistEvents = CCalendarEvent::GetList($Params);
	foreach ($arExistEvents AS $arFields) {

		$AlreadySended = 0;
		$arSelect = Array("ID", "NAME");
		$arFilter = Array("IBLOCK_ID"=>$HistoryIBlockID, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y","NAME"=>$arFields["ID"]);
		$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
		if($ob = $res->GetNextElement())
		{
			$AlreadySended = 1;
		}
		
		$TimeMinus = strtotime($arFields["ID"]) - time();
		if (($AlreadySended == 0)&&($TimeMinus > 0)&&($TimeMinus < ($TimeBefore * 60))) {
			$SmsSendUsers[] = array();
			/*Если надо отправлять СМС сотрудникам тоже - раскомментировать
			$SmsSendUsers[] = $arFields["MEETING_HOST"];
			foreach($arFields["ATTENDEES_CODES"] AS $userCode) {
				$SmsSendUsers[] = str_replace("U","",$userCode);
			}

			foreach($SmsSendUsers AS $userID) {
				$rsUser = CUser::GetByID($userID);
				$arUser = $rsUser->Fetch();
				if (CModule::IncludeModule("sozdavatel.sms"))  
				{  
					$message = "В течение ближайших ".$TimeBefore." минут у Вас состоится встреча:\n".$arFields["NAME"];  
					CSMS::Send($message, $arUser["PERSONAL_MOBILE"], "UTF-8");  
				} 
			}
			*/
			//$SmsSendLeads[] = array(); Это в приницпе лишнее
			foreach($arFields["UF_CRM_CAL_EVENT"] as $CrmEvent) {
				if(strripos($CrmEvent,"L_")) {
					$LeadID = ("L_","",$CrmEvent);
					//Тут вставить функцию вытаскивания номера из лида, номер записать в $mobileNum
					$res = CCrmFieldMulti::GetList(array(),array("ELEMENT_ID"=>$LeadID,"TYPE_ID"=>"PHONE","VALUE_TYPE"=>"MOBILE"));
					$mobileNum = "";
					if($ob = $res->Fetch())
					{
						$mobileNum = $ob["VALUE"];
					}
					if (CModule::IncludeModule("sozdavatel.sms")&&(!empty($mobileNum)))  
				{  
					$message = "В течение ближайших ".$TimeBefore." минут у Вас состоится встреча:\n".$arFields["NAME"];  
					CSMS::Send($message, $mobileNum, "UTF-8");  
				} 
					
				} 
			}
			$arLoadProductArray = Array(
				"MODIFIED_BY"    => $USER->GetID(),
				"IBLOCK_SECTION_ID" => false, 
				"IBLOCK_ID"      => $HistoryIBlockID,
				"NAME"           => $arFields["ID"],
				"ACTIVE"         => "Y",
				"PREVIEW_TEXT" => "SMS Sended"
			);
			
			$el = new CIBlockElement;
			if($PRODUCT_ID = $el->Add($arLoadProductArray))
				//echo "New ID: ".$PRODUCT_ID;
			else
				//echo "Error: ".$el->LAST_ERROR;
		} else {
			//echo "Already added or not need to send";
		}
	}
}?>
