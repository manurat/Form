<?php
/**
======================================================================================

class Form
------------------------------------------------------------------------------------
Manages form submit and validation.
Stores form errors and response messages in dedicated session to share them between scripts.

@author     Emanuele Fornasier
@link       www.atriostudio.it
@rev.       2014/06/11

 =====================================================================================
*/
class Form {

	private $name = 'Form';
	private $sessionIndex;
	
	private $arValues = array();			// stores form values
	private $arErrors = array();			// store form errors

	// language array translations
	private $arTrans = array(
		'errore_form_obbligatorio' => 'Il campo &egrave; obbligatorio.'
		,'errore_form_password_1' => 'Devi scegliere e confermare una password.'
		,'errore_form_password_2' => 'Le password inserite non coincidono.'
		,'errore_form_password_3' => 'La password deve essere composta da almeno 8 caratteri.'
		,'errore_form_password_4' => 'Se scegli di cambiare la password, devi sceglierla e confermarla.'
		,'errore_form_tipo_0' => 'in questo campo non sono ammessi +*?[^]${}=!<>|:@<br/>'
		,'errore_form_tipo_1' => 'in questo campo sono ammessi solo numeri, senza spazi.'
		,'errore_form_tipo_2' => 'in questo campo sono ammessi soltanto lettere, numeri e .-().'
		,'errore_form_tipo_3' => 'in questo campo i caratteri <> non sono ammessi.'
		,'errore_form_tipo_4' => 'in questo campo non sono ammessi +*?[^]${}=!<>|:@.'
		,'errore_form_tipo_5' => 'l&rsquo;indirizzo &egrave; in un formato non corretto.'
		,'errore_form_tipo_6' => 'Devi inserire e confermare l&rsquo;email.'
		,'errore_form_tipo_7' => 'Gli indirizzi email non coincidono.'
		,'errore_form_privacy' => 'Devi accettare l&rsquo;informativa sulla privacy.'
	);		
	
	public $responseMsg;
		
		
	/**
	__construct()
	
	@param string $sessionIndex
	@param array arData : values to store
	@param array arTrans : translation to update default ones.
	*/
	public function __construct($sessionIndex = 'generic_form', array $arData = array(), $arTrans = NULL) {
		
		// creates session
		if (!empty($_SESSION[$sessionIndex])) {
			unset($_SESSION[$sessionIndex]);
		}
		$this->sessionIndex = $sessionIndex;
		
		// translations
		if ($arTrans) {
			$this->arTrans = array_merge($this->arTrans, $arTrans);
		}

		if (count($arData)) :
		// stores values in submit page
			$this->arValues = $_SESSION["form"][$this->sessionIndex]["values"] = $arData;
			$this->arErrors = $_SESSION["form"][$this->sessionIndex]["errors"] = array();
			$_SESSION["form"][$this->sessionIndex]["posted"] = 1;
		else :
		// in form's page, automatically restores values, errors and response from session
			if (isset($_SESSION["form"][$this->sessionIndex]["values"])) {$this->arValues = $_SESSION["form"][$this->sessionIndex]["values"];}
			if (isset($_SESSION["form"][$this->sessionIndex]["errors"])) {$this->arErrors = $_SESSION["form"][$this->sessionIndex]["errors"];}
			if (isset($_SESSION["form"][$this->sessionIndex]["responseMsg"])) {$this->responseMsg = $_SESSION["form"][$this->sessionIndex]["responseMsg"];}
		endif;
	
	}
	
	/**
	__destruct()
	
	Automatically stores values, errors and response in session
	*/
	public function __destruct() {
		$_SESSION["form"][$this->sessionIndex]["values"] = $this->arValues;
		$_SESSION["form"][$this->sessionIndex]["errors"] = $this->arErrors;
		$_SESSION["form"][$this->sessionIndex]["responseMsg"] = $this->responseMsg;
	}
	
	/**
	Global validation status.
	@returns true / false
	*/
	public function isValid() {
		return !count($this->arErrors) ? true : false;
	}
	
	
	/**
	Global submit status.
	@returns true / false
	*/
	public function hasPost() {
		return !empty($_SESSION["form"][$this->sessionIndex]["posted"]) ? true : false;
	}
	
	
	/**
	Resets form.
	*/
	public function unsetForm() {
		$this->arErrors = array(); 
		$this->arValues = array(); 
		if (isset($_SESSION["form"][$this->sessionIndex]["posted"])) {unset($_SESSION["form"][$this->sessionIndex]["posted"]);}
		if (isset($_SESSION["form"][$this->sessionIndex])) {unset($_SESSION["form"][$this->sessionIndex]);}
	}
	
	
	/**
	Multiple values injection.
	@param array arData (request key => values)
	*/
	public function setValues(array $arData = array()) {
		$this->arValues = $_SESSION["form"][$this->sessionIndex]["values"] = $arData;
	}
		

	/**
	Single value injection.
	
	@param string $key 
	@param string $value
	*/
	public function setValue($key, $value) {
		$this->arValues[$key] = $value;
	}
		

	/**
	General field validation.
	Store errors in $arErrors.

	@param string $requestIndex 
	@param int $type
	@param string $fieldName 
	@param bool/int $required 
	
	@returns true/false

	*/
	function checkField($requestIndex, $type, $fieldName, $required = 0) { 

		$regExp = array (
			0 => '([\+\*\?\[\^\]\$\{\}\=\!\<\>\|\:@])'		// names	
			,1 => '([^0-9\+])'								// numbers
			,2 => '([^0-9a-zA-Z\.\-\(\) ])'					// addresses	
			,3 => '([\<\>])'								// others
			,4 => '([\+\*\?\[\^\]\$\{\}\=\!\<\>\|\:@])'		// file names
		);

		if (
			$required
			&& (
				!isset($this->arValues[$requestIndex]) || !strlen($this->arValues[$requestIndex])
			)
		) :
			$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_obbligatorio"];
			return false;
		endif;
		
		if(!empty($this->arValues[$requestIndex]) && preg_match($regExp[$type], $this->arValues[$requestIndex])) :
			$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_tipo_".$type];
			return false;
		endif;
		
		if (!empty($this->arErrors[$requestIndex])) {unset($this->arErrors[$requestIndex]);}

		return true;
	} 
	
	
	/**
	Email validation.
	
	see checkField()
	*/
	function checkMail($requestIndex, $fieldName, $required = 0) {
		
		if ($required && empty($this->arValues[$requestIndex])) :
			$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_obbligatorio"];
			return false;
		endif;
			
		
		if(!preg_match("/^[\w-_\.]+[\w-_]{1}@[\w-_\.]+\.[a-zA-Z]{2,4}$/", $this->arValues[$requestIndex])):
			$this->arErrors[$requestIndex] =  '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_tipo_5"];
			return false;
		endif;

		if (!empty($this->arErrors[$requestIndex])) {unset($this->arErrors[$requestIndex]);}

		return true;
	}
	
	
	/**
	Validation of "Email plus email confirmation".
	
	see checkField()
	*/
	function checkDoubleMail($requestIndex1, $requestIndex2, $fieldName1,  $fieldName2) {
		
		// obligatoriness 
		if (empty($this->arValues[$requestIndex1]) || empty($this->arValues[$requestIndex2])) :
			$this->arErrors[$requestIndex1] = '<strong>'.$fieldName1.' / '.$fieldName2.'</strong>: '.$this->arTrans["errore_form_tipo_6"];
			return false;

		// format
		elseif (!preg_match("/^[\w-_\.]+[\w-_]{1}@[\w-_\.]+\.[a-zA-Z]{2,4}$/", $this->arValues[$requestIndex1])):
			$this->arErrors[$requestIndex1] =  '<strong>'.$fieldName1.'</strong>: '.$this->arTrans["errore_form_tipo_5"];
			return false;
		elseif(!preg_match("/^[\w-_\.]+[\w-_]{1}@[\w-_\.]+\.[a-zA-Z]{2,4}$/", $this->arValues[$requestIndex2])):
			$this->arErrors[$requestIndex2] =  '<strong>'.$fieldName2.'</strong>: '.$this->arTrans["errore_form_tipo_5"];
			return false;
		// equality
		elseif ($this->arValues[$requestIndex1] != $this->arValues[$requestIndex2]) :
			$this->arErrors[$requestIndex1] =  '<strong>'.$fieldName1.' / '.$fieldName2.'</strong>: '.$this->arTrans["errore_form_tipo_7"];
			return false;
		endif;

		return true;

	}


	/**
	"Privacy policy" validation
	
	see checkField()
	*/
	function checkPrivacy($requestIndex) {
		if (empty($this->arValues[$requestIndex])) {
			$this->arErrors[$requestIndex] =  $this->arTrans["errore_form_privacy"];
			return false;
		} else {
			$this->arValues[$requestIndex] = 1;
			unset($this->arErrors[$requestIndex]);
			return true;
		}
		

	}
	
	
	/**
	Passwords validation.

	*/
	function checkPassword ($pass_1_index, $pass_2_index, $mode = 'insert') {
		
		switch ($mode) {
			case  'insert'  : {
				// obligatoriness 
				if ( empty($this->arValues[$pass_1_index]) || empty($this->arValues[$pass_2_index]) ) {
					$this->arErrors["password"] =  $this->arTrans["errore_form_password_1"];
					return false;
				// equality
				} elseif ($this->arValues[$pass_1_index] != $this->arValues[$pass_2_index]) {
					$this->arErrors["password"] =  $this->arTrans["errore_form_password_2"];
					return false;
				// password length
				} elseif (strlen($this->arValues[$pass_1_index]) < 8 ) {
					$this->arErrors["password"] =  $this->arTrans["errore_form_password_3"];
					return false;
				}
			
				break;
			}
			case  'update' : {
				// obligatoriness 
				if (
					  ( !empty($this->arValues[$pass_1_index]) && empty($this->arValues[$pass_2_index]) )
					 || (empty($this->arValues[$pass_1_index]) && !empty($this->arValues[$pass_2_index]) )
				) {
					$this->arErrors["password"] =  $this->arTrans["errore_form_password_4"];
					return false;
				// equality
				} elseif ($this->arValues[$pass_1_index] != $this->arValues[$pass_2_index]) {
					$this->arErrors["password"] =  $this->arTrans["errore_form_password_2"];
					return false;
				// password length
				} elseif ( !empty($this->arValues[$pass_1_index]) && !empty($this->arValues[$pass_2_index]) && strlen($this->arValues[$pass_1_index]) < 8 ) {
					$this->arErrors["password"] =  $this->arTrans["errore_form_password_3"];
					return false;
				}
				
				break;
			}
			
		}
		
		if (!empty($this->arErrors["password"])) {unset($this->arErrors["password"]);}
		
		return true;
	}
	
	
	/**
	Checkboxes validation
	*/
	function checkMultipleCheckbox($requestIndex, $fieldName, $required = 0) {
		
		if ($required && empty($this->arValues[$requestIndex]))  {
			$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_obbligatorio"];
			return false;
		}  else {
			unset($this->arErrors[$requestIndex]);
			return true;
		}
		
	}
	
	
	/**
	Files validation.
	*/
	function checkFile ($requestIndex, $fieldName, $type, $required = 0) {
		
		$file = !empty($_FILES[$requestIndex]["name"]) ? $_FILES[$requestIndex]["name"] : '';
		$ext = explode('.', basename($file));
		$ext = $ext[count($ext)-1];   
		
		$this->arValues[$requestIndex] = $file;
		
		if ($required && empty($this->arValues[$requestIndex])) :
			$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_obbligatorio"];
			return false;
		endif;

		if($type == 'img'){$arExt = array("jpg","jpeg","png");}
		if($type == 'swf'){$arExt = array("swf","flv");}
		if($type == 'video'){$arExt = array("wmv","mpg","avi","mov","flv","mp4");}
		if($type == 'file'){$arExt = array("exe","msi","bat","com","inf","chm","cmd","cpl","dll","hlp","hta","lnk","ocx","pif","reg","scr","url","vbs","php","php3","html","htm","asp","aspx","js","sh","bin");}
		if($type == 'pdf'){$arExt = array("pdf");}
		if($type == 'csv'){$arExt = array("csv");}
		
		if($file && $type == 'file'){
			if(in_array($ext,$arExt)){
				$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_formato_file"];	
				return false;
			}
		}else if($file && $type != 'file'){
			if(!in_array($ext,$arExt)){
				$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_formato_file"];	
				return false;
			}
		}
		
		if (!empty($this->arErrors[$requestIndex])) {unset($this->arErrors[$requestIndex]);}
		
		return true;

	}
	
	
	/**
	Date validation.

	Validates a date against format "Y-m-d H:i:s" 
	*/
	function checkDate($requestIndex, $fieldName, $required = 0) {
		
		// obligatoriness
		if ($required && empty($this->arValues[$requestIndex])) :
			$this->arErrors[$requestIndex] = '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_obbligatorio"];
			return false;
		endif;
			
		$date = $this->arValues[$requestIndex];
 	
		// format
	 	if ( ! date('Y-m-d H:i:s', strtotime($date)) == $date) {
			$this->arErrors[$requestIndex] =  '<strong>'.$fieldName.'</strong>: '.$this->arTrans["errore_form_formato_data"];
			return false;
	    } 
	
		if (!empty($this->arErrors[$requestIndex])) {unset($this->arErrors[$requestIndex]);}
		
		return true;
	}
	

	/**
	Checks for a specific error.

	@param string $requestIndex
	@return true / false
	*/
	public function hasError($requestIndex) {
		return !empty($this->arErrors[$requestIndex])  ? true : false;
	}
	
	
	/**
	Prints errors.
	*/
	public function printErrors($return = false) {
		if (!$return) :
			foreach ($this->arErrors as $k => $msg) :
					echo $msg.'<br>';
			endforeach;
		else :
			return implode('<br>',$this->arErrors);
		endif;
	}
	
	
	/**
	Access to values (single / multiples)
	*/
	public function getValue($requestIndex) {
		return isset($this->arValues[$requestIndex])  ? $this->arValues[$requestIndex] : NULL;
	}
	
	public function getAllValues() {
		return $this->arValues;
	}
	
		
	/**
	Adds a custom error from outside the class.
	*/
	public function addError($requestIndex, $strError) {
		if (!isset($this->arErrors[$requestIndex])) {
			$this->arErrors[$requestIndex] = '';
		}
		$this->arErrors[$requestIndex] .= $strError;
	}
	
	/**
	Empties errors.
	*/
	public function unsetErrors() {
		$this->arErrors = array();
	}
	
	
	/**
	Creates a simple mail body to send an email notification.

	@param array $arFields (label => value)
	*/
	public function createMailBody($arFields = array()) {
		
		$strbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Mail body</title>
		<style type="text/css">
		body{
			margin: 0px;
			padding: 0px;
			font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;
			font-size: 12px;
			height: 100%;
			color: #000000;
			text-align: left;
		}
		html{height: 100%;}
		table{
			margin: 0px;
			padding: 0px;
			font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
			font-size: 11px;
			color: #003C79;
			text-align: left;
		}

		</style>
		</head>
		<body>
		<br><br>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
		  	<tr>
			    	<td align="center" valign="top">
		    		<table width="96%" border="0" align="center" cellpadding="5" cellspacing="0">
					  	<tr>
							<td valign="top" colspan="2" style="background: #000; color: #fff;">DATI DELLA RICHIESTA</td>
			  			</tr>'."\n";								

		foreach ($arFields as $label => $value): 
			
			$strbody .= '<tr>';								
			$strbody .= '<td valign="top" style="border-bottom: 1px solid #ccc;border-left: 1px solid #ccc;border-right: 1px solid #ccc;">'.$label.'</td>';								
			$strbody .= '<td valign="top" style="border-bottom: 1px solid #ccc;border-right: 1px solid #ccc;">'.$value.'</td>';								
			$strbody .= '</tr>';								

		endforeach;					
			
		$strbody .= '    		</table>
		    	</td>
			</tr>
		</table>
		</body>
		</html>';

		return $strbody;

	} 


}
?>