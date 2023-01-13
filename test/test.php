<?php

$test = 'hello';
$rr = 23232;

echo '1this is the text- ${test}<br>';
echo '2this is the text- "${test}"<br>';
echo '3{"this" is the text: "'.$test.'" - '.$rr.'}<br>';
echo "4this is the text- ${test}";die;

require 'vendor/autoload.php';
include "lib/DB.php";

use RingCentral\SDK\Http\HttpException;
use RingCentral\SDK\Http\ApiResponse;
use RingCentral\SDK\SDK;

// $file = fopen('https://developers.ringcentral.com/assets/images/ico_case_crm.png', "r");

// //Output lines until EOF is reached
// while(! feof($file)) {
//   $line = fgets($file);
//   echo $line. "<br>";
// }

// fclose($file);

// Expected HTTP Error: 400 Bad Request (and additional error happened during JSON parse: Response is not JSON)
class RingCentralService
{
   
   protected $db = null;
   
   public function __construct($db)
   {
      $this->db = $db;
   }

   public function getUserRCSettings($user_id) {

      $id = $this->db->escape($user_id);

      $query = "SELECT 
      ringcentral_server_url, 
      ringcentral_username, 
      ringcentral_password, 
      ringcentral_id, 
      ringcentral_secret,
      transunion_faxnumber,
      equifax_faxnumber,
      experian_faxnumber  
      FROM user_mail_settings WHERE user_id = '" . $id . "'";

      $data = $this->db->GetDataFromDb($query);

      return $data;

   }

   public function sendFax($request) {

      try{

         if (!$request['fax'] || $request['fax'] == '') {
            return ['success'=>false, 'msg'=>'No fax found.'];
         }

         if (!$request['user_id'] || !$request['file']) {
            return ['success'=>false, 'msg'=>'`user_id` or `file` is missing'];
         }      

         $credentials = $this->getUserRCSettings($request['user_id']);

         $fax_number = null;
         $client_id  = $credentials['ringcentral_id'];
         $extension  = 101;

         // $request['file'] is a url ex. example.com/files/myfile.pdf
         $file       = $request['file'];

         // $request['file_content'] is raw html
         $file_content = $request['file_content'];

         $rcsdk = new SDK($credentials['ringcentral_id'], $credentials['ringcentral_secret'], $credentials['ringcentral_server_url'], 'Demo', '1.0.0');

         $platform = $rcsdk->platform();

         // Authorize         
         $platform->login($credentials['ringcentral_username'], $extension, $credentials['ringcentral_password']);

         // Send Fax
         // Assign fax number based on what is in the letter(TransUnion, Equifax, Experian). 
         switch ($request['fax']) {
            case 'TransUnion':
               $fax_number = $credentials['transunion_faxnumber'];
               break;
            case 'Experian':
               $fax_number = $credentials['experian_faxnumber'];
               break;
            case 'Equifax':
               $fax_number = $credentials['equifax_faxnumber'];
               break;
            default:
               # code...
               break;
         }

         $filename = $fax_number.'.pdf';

         $sendfax = $rcsdk->createMultipartBuilder()
         ->setBody(array(
            'to'         => array(
                array('phoneNumber' => $fax_number),
            ),
            'faxResolution' => 'High',
         ))         
         ->add($file_content, $filename)         
         ->request('/account/~/extension/~/fax');

         //print $request->getBody() . PHP_EOL;

         $response = $platform->sendRequest($sendfax);

         return [
            'success'   	=> true, 
            'msg'       	=> 'Sent Fax ' . $response->json()->uri, 
            'data'      	=> $response->json()->messageStatus, 
            'ringcentral'	=> $response->json(), 
            'url'       	=> $response->json()->uri
         ];

      }catch (\RingCentral\SDK\Http\ApiException $e) {
         // Getting error messages using PHP native interface
         return [
            'success'	=> false, 
            'msg'		=>'Expected HTTP Error: ' . $e->getMessage(), 
            'data' 		=> $credentials,
            'request' 	=> $request,
            'fax' 		=> $fax_number
         ];
      }

   }

}


$r = new RingCentralService($DB);

$payload = [
	'user_id' 		=> 162,
	'file' 			=> 'https://app.30daycra.com/files/c2m_pdf/Dominique_Brown-2021-08-06-149ee11-h49UX11nXsfWHYnLkdGn-162.pdf',
	'fax' 			=> 'Equifax',
	'file_content' 	=> '<div class="pageBreak" style="page-break-after:always; display: inline-block;">
<p>Dominique Brown <br />10559 Andrew Humphreys Court<br />Bristow, Virginia 20136<br />Telephone: (703) 298-3803<br />Date of Birth: 01/15/1984<br />SS#: 0032</p>
<p>Equifax<br /> P.O. Box 105069<br /> Atlanta, GA 30348</p>
<p>06/30/2021</p>
<p>To Whom It May Concern,</p>
<p>This letter is a formal complaint that you are reporting inaccurate and incomplete credit information. I am distressed that you have included the information below in my credit profile and that you have failed to maintain reasonable procedures in your operations to assure maximum possible accuracy in the credit reports you publish. Credit reporting laws ensure that bureaus report only 100% accurate credit information. Every step must be taken to assure the information reported is completely accurate and correct. The following information therefore needs to be re-investigated.</p>
<p>1. Please investigate the reporting of this account. I do not believe this account is reporting accurately.<br />&nbsp;&nbsp;&nbsp;&nbsp;Account Number: <br />&nbsp;&nbsp;&nbsp;&nbsp;Remove all date of birth from my credit profile that do not match this date: 01/15/1984<br /><br /></p>
<p>I respectfully request to be provided proof of this alleged item, specifically the contract, note or other instrument bearing my signature.</p>
<p>Failing that, the item must be deleted from the report as soon as possible. This information is entirely inaccurate and incomplete, and as such represents a very serious error in your reporting. Please delete this misleading information and supply a corrected credit profile to all creditors who have received a copy within the last six months, or the last two years for employment purposes.</p>
<p>Additionally, please provide the name, address, and telephone number of each credit grantor or other subscriber.</p>
<p>Under federal law, you have thirty (30) days to complete your re-investigation. Be advised that the description of the procedure used to determine the accuracy and completeness of the information is hereby requested as well, to be provided within fifteen (15) days of the completion of your re-investigation.</p>
<p>Sincerely yours,</p>
<p><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAlgAAABBCAYAAAANINqmAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAACWKADAAQAAAABAAAAQQAAAACPpNZgAAARZElEQVR4Ae2dCbRdVXnHlSEMDhCxaKsQKIIDs4qlUIhSyJKCKApqRYXWFkQKlSrgsCRFtAhRjAologmPRRmKLMBSkTENZRSoKAilCLwokgBBMUiUwdD fnB21vb03vduXt4z7z7 31r/nL2/PZy9f ck 7v7nHvzvOfFQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQiAEQmAZgVWb1GSOk9ATy0qSCIGxI C9tgvaHC1Bv0KxEAiBEAiBEJhQBNZgNgvQDDSATkGxEBhtAvvS4U3oDPQw t9Gv X4SRQLgRAIgRAIgQlF4I3MxsXu283xkgk1u0xmvBA4q7m/vNeuQwejLyJ3r/S9GcVCIARCIARCYMIQeAszcYG7ujkmwJowl3ZcTeSa5v76UmtUuzf 2S1/siEQAiEQAiHQ1wRewegNsMpjm3/v69lk8OORwHoM6inkfbZZa4BrkX8czW35kw2BEAiBEAiBviWwCiO/Hz2IXAQ1HxXGQmA0CexNZ6uhpWiw1fHT5J PFrX8yYZACIRACIRA3xIwwNJmPnt45s97qnSSITAaBAywtPuQO1m17UBmErq1diYdAiEQAiEQAhOBwAuYxGLkI5wPT4QJZQ7jhsDajOQ3yHvrig6j kJTtk2HsrhCIARCIARCoO8J OjGRfCXaMu n00mMF4IlC9ReG/N6jCo2/G5sxULgRAIgRAIgQlDoDwirCe0Dhm/Rv O2pl0CIyQwLZVu/bjZx8dvg61v1lYNUkyBEIgBEIgBPqbgDtYj6D3IH/R3ZePP4NiIbAiBE6lsbtXqryLZX8vRHeh cgfu42FQAiEQAiEwIQkUAIsJ7cj8puFLornIt j6Uc7kEF/fYwH/jL6Pxv5KCz2/wmch6sEWFs1xetzLL LdRvpQ9EWyG8TxkIgBEIgBEJgQhGoAywntiH6AXJxvAVNQf1m/8qATxvjQfsI7CdouzE T792fzkD9x5SfplCuxgVnzulJe1PhuyFYiEQAiEQAiEwYQgMMhMfEdbmgng cgF8CO2EYhOPgDtHfrHh1WMwtcvo89eNSvcDJC5Cr0IvQu6YHoYWIu81HyuWYIxkLARCIARCIAT6l0CnAMvZuPh FrnwPYnehSai/b7fA3o9EM9B30NvXU6gr6T HWi/Lu1Wxd/pCwydqvvL6vOR13cRGm3zfwaw77t76Hgydc5s6g/0UH8iVPFa SOssRAIgRAIgQlKoFuAVabry /uRPibRjsUZ5ejuxJ7IF SPwG9Bo1n8z2qpejvm0FuzPFS9CgaQL0GK1TtyQyMnkK/RQvQYrQu6sVcjMv7S2W87XZzcHy/7eyQ9906fyLBAKjIBX80reyAXtVjpwb0fovV8UxrtfHF LnI 8rdr5HY7/ve3I1BHo7a95Ds90W I g9UAJ8d/IeQP58hW1jIRACIRACfU5guADL6fme0S QOx3dFrg/o wnqCzYHg0mpqP1kTs37th8BfmC8xGobQZke6GXtAvIG2D8MSoLUocqy1z200vAsDP1HOdMtDW6v8n7f Tp3xV1sxdTsEG3wg5 A1XfO7oBvRwdijzHPqgXM2C1vmNcq0ODNfEZGBo4DWcfoYJ93YXcaRqLHaxzmnOczbFX24qK3jODyICr2AdJON6im0gbpPRqK3Jvfp6T3Izq8dTnPYDMLrWjSV/J0fFejv4KHYCOQQ8h/f/VHL3Xv9qky313DflYCIRACIRAnxMYZPztd7A6TWlHnO72 Am7/al8Kj53ZVw4htMD1DkfbYv Bp2MDKpciEpb zIQ0wyUpiPHaPnP0dtQN3sjBdb7ZLcKld/zW/d6tBgNIhdLAy7970XdzHeJlqBeArntqOcO4L8gAyGtBExvfzY75J/uHBqcDTUmH Fa/jU0nDlfGW DDB6 j0bbzqBDx3Picnbs2Gy3cdWuBIT6a 1e1emWnErBSO5N 3MnzfMZiHYKsLwull L2uY9Xo/V9GPoLLQb hnS546pRzlt0qTv5BgLgRAIgRDocwKDjL XAMtpuni7GNRBwXrky2JhwPE5dBI6F81DBha3of2QO1DFyqd2 1MGEO52HICuQwZzW6N/Q5bfjeagJ5BBVjf7BgXWP6pbhco/o6lr/V hLZuyCxv/Rk2 fdgQh Pz0Wmnhbeu/yIy96LP1k7S/4M872tb/nbWAO4WZF2ZdrMy5p27VWj8kzk6dgPaNyD7nY5G2wbo0L6PWM6OZzft3lG1O5a0uzt7IQOUEjAdSXooG m9WfqcS8I5HF4c1dHr7k5atzl pyk7jONU9HpUgmsfedquaIC0ZsCrbwDFQiAEQiAE pzAIOPvNcByV8EF4PRqziVQMvDxk3lta5AxCPlY7SS9ByqLSzkeUtX5y6bcT/yWz6zKDOYMhjqZj87cibLNrp0qtHwu1uX8h1Zl95FeWOXbyU/jsN017YIO Tn4HkKrVWUGQbZf1PJXVZYl303Kuj9G6yzz/m7inWSt43U0IBvKDFKsOwPdgQyAX45G25y35/m75ezYgMx2R1ftbif9 Sq/GWmv0QcqX6fkSO7N0o8fBhzHErRucVbH6aQtfwq1 Rl8/QJ5D5lum O3rfJ Lo98P9T4DuIYC4EQCIEQ6HMCg4y/1wDLqS5AV5vANkDuLLijsDdq21QcLiLbtwpcdE9G30SW/zeqF6J9G79lV7bKPJ/1O9lf47SNi9ZwgYbtL0HWn48mIe1lSN8FZrrYLfit08tCuJR6zqG2q8jYXgbD2RVUsO6eXSpugd A0zqXdqlT3DL IrKugZXH/dFY2Dfo1P7nLWfnxzTtjmjalWBk06qfNUg7Z3eFutlI783S3z QcPyziqM6/m1TZvn3Kn9JTmvKZdDJ/PtgW1XfQ6c0vq05drNyn3Yrjz8EQiAEQmCcEBhkHMsTYD1IfQME7RPIReJTZjrYefiWoNU7lOmah2zffnzmIxn9TyMfmxR7KQn9BkZtc1Erj46Obxc2 RJguCukuQtif/UO2180viM5drLX4nRc7th12tmwje/mzEbrI/u/EBX7CAl96k Ls8tRbo8j31urd8BK9deQWIhKf56zm02h4AfIvkr9Y7tVbvydzjlMk2XFM0l5nsuXeXpLnNm0KwHlaeRvbjV9L3l3jgy0OtlOOB9Gnn k9 a1TXsZ17YPmXKf2f/pdSHpVdAPkffIjqiTfQinbR1jHTDdSN7AsduHgxMpk2ssBEIgBEKgDwgMMkYfVfVi5bHJcU3lb3N0odi4Q ODm7KPdijTNRmVheoNrTqnkrffuS3/Vo1/Vsu/KXmDRIMe270FtW0dHOciyweQedNLkYFQsaNJ6N lOKrjm0gbYFp VeWvk3uQ8R2xJ9GfIOtehNZEB6Iy53tJPx8NZZ m0PYXdKhkoGdwZX/XIet9GbXNxfoQ5COrEly5 He7LhQ9Y1/jz 826ZEcDDROQI6rfvw6VF9rU7igabMRx4OQY30/qu0KMrfXjibtOd358po ijz3SO7NDWjnee9AtRms6b8ffQvZv9eotiPJ6G/fo3Udr5N1fFex2OokDKb/sziqo0HYPyPbTKv8SYZACIRACIxjAoOM7a4exmcwcA7yH/mpTf07ObrgtD9x74/PxcJHVt2CiPdQZl/3obZdi8My 6ltdzL6p1fOKaQHG7/jsXxDVNuuZOYjg5GTkcHODsi6t6LaStC4feV0J8dHRgZNZWdkTlVu0pfZZyH7vAfthOTiQu8ungGOuxM/RtY5HnUzg78zkfWUgUpt7yKzGBkY/zn6ILLebFTMAPZg5Fic9xloc2S9uaibeb0 gax3TLdKPfrty CwzEEeQ5kBjHXPQq9CT6OzUd3Oeem/GNW2LZkbkO09p/e09eq2ZJ 5px7nONS9uR/l9vNPSPPaOg59Xr9NkMGf TrAOrDx/ZTji1E3u4wC276zquD49V1S Uxuhm5Eln0BxUIgBEIgBPqEwDWM03/wh7I/onAA Y/8caiYgYO7RsVcFM9D1rsd/SHqZuUTuQFP29wBcnH8g1bB3uTtex7aDhn0PIL0fRld2KRLuy3Jl6BwIelpqFhZDE8rjuZYgrvtyU9BLqQGKZ7jP1AJ8sq418BnIHMfss7p6IWomGN2DIehddDHkfXeh9r2ahzuHBmIPYRmIOuWc01p0vquRxsg7XVIn 3OQs7hSWRQZ9tNkTYZeb0GkeNu2244bkb2dTWq50F2RPYCWp2P7PNKtAUy8KrNAPZzyEBwCXJeBjUnonaAtBk 5KptjNyzrb1GvgIT7Ofkd6bn6Kt5/AaHYQebPKXcpShNhVZR99b0bnIe/ZHqPAm2dEW4LXtlKpU9vrcNVwb7YjOQM7rMfRhFAuBEAiBEOgjAusyVhfBYkeTGEDHo5OQC4if F2wDWJ8DFPsVhIuChchFzfTLgjHoU4LOO5ldj0p609b5nk2sXrjd6Fq2yQc85Dtilys3A3T/hHpvw7d3aQdzyloPVTbV8hY92O1k7QLpX4X6HKOu0h/ADn3tdBv0CJ0NTKosZ47G3uioeylFA4g62 HZL87 hK6A n3eAgywFgfOf6H0bXIBdw6jl1OtX2VjAux45iN3o46XYOv47ePH6KZaBYy8HHXRf9iZICxJhpNO4rOHJ/neAR9F52ELkMLkX7H/iY0lDnvnyP7KmM2ADLYrse8Ivem83c8v26OXmP7XxUVcxzl/NZV30LDBaUvaeo yrG2EiT7d81rbn9PoTloIxQLgRAIgRDocwL7Mv4fIYOIXyIDoVOQu1Nt2xXHE8gA4BJ0LNoW9WIGDC6M7UDBRfJONAN1MgOcHdA aHNUL3oGUY7XReo2dALaBHWyb Jcit7cKtyGvGO7ERmM7Ibqc5D9nXeaDBTejdrzsF7bzsNRFuN7SZeAaRFpg43tUdsOwWEQ6YJsMLITWhFbjcZHoe gB5CL Xx0Nno/WhuNlRlQfhTNRc7/SbQQXYyORsMFJ1R5xt7GnzehC9D70CTUthW5N73X7f92ZPD7StTJDAZPRfujbnXa7TbGYeAkg7bNwOHfPXkcjl6BYiEQAiEQAs9RAu3gY3kwrLI8lZejbvsRVLemBhsjtV7PUff/GTIlwDK4uxDtiXoJzkZyProe1saq32FPTIWxPveK3Ju9jH kdcbruEY6n7QLgRAIgRAIgZVO4B5GYJD18ZU kgwgBEIgBEIgBEJgVAmM1c7RqA5ygnbmYzFtq2cP TMEQiAEQiAEQmCiEEiAtfKvZN6vWfnXICMIgRAIgRAIgVElkABrVHGOqLOfjahVGoVACIRACIRACIxbAgmwVt6l8VuXmt8Wi4VACIRACIRACEwgAgmwVt7FXNKc2p8AiIVACIRACIRACEwgAgmwVt7FfKw5dXawVt41yJlDIARCIARCYEwIrMjvP43JgJ5Dnd7AXP0h1J8 h acqYZACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRACIRAHxP4P5xg3ZUGWrRGAAAAAElFTkSuQmCC" alt="" /><br />Dominique Brown</p></div>
<div class="pageBreak" style="page-break-after:always; display: inline-block;">
<img style="max-width: 573px;width: fit-content; height:auto;" src="https://a2ced74a0ce18934fb73-966537272d830107c9657c395c212596.ssl.cf1.rackcdn.com/6249_client_document_1593857378.jpeg"><br /><br />
</div>
<div class="pageBreak" style="page-break-after:always; display: inline-block;">
<img style="max-width: 573px;width: fit-content; height:auto;" src="https://a2ced74a0ce18934fb73-966537272d830107c9657c395c212596.ssl.cf1.rackcdn.com/6249_client_document_1593857399.jpeg">
</div>
<input type="hidden" id="letter_id_div" class="letter_id_div" value="104431">
<style type="text/css">
#preview_letter_content div, #preview_letter_content table {background: none repeat scroll 0 0 #FFFFFF; border: 1px solid #CCCCCC; box-shadow: 1px 5px 11px #333333; margin: 1%; padding: 24mm; }
@media print {
.pageBreak .pageBreak { page-break-before: always; }
.pageBreak:last-child { page-break-after: avoid; }
}
</style>',
];

$send = $r->sendFax($payload);

var_dump($send);die;

?>