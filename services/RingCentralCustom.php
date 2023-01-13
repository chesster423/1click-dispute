<?php
    class RingCentralCustom {

        private $userId;

        //API urls
        private $faxApiUrl;
        private $getTokensUrl;
        private $getAccountUrl;
        private $endpoint_id;
        private $productionDomain = 'https://platform.ringcentral.com';
        private $sandboxDomain = 'https://platform.devtest.ringcentral.com';

        //tokens
        private $access_token;
        private $refresh_token;

        //application credentials
        protected $production_client_id = 'DtJSdS4dTRSfdXy2J0dYgQ';
        protected $production_client_secret = "omlxwevPQ0ukveBDjs0caw9kMXkYEpRQa_V48QD8vf7g";
        protected $sandbox_client_id = "ybdLq3gaR1uNdpiO1XwXzA";
        protected $sandbox_client_secret = "wpFXwQYPTV6JtbqFOpbJpgOv2OnZcnQ2eVGXSPUqf0Yw";

        public function __construct($userId, $access_token, $refresh_token = null, $endpoint_id = null) {
            $this->userId = $userId;
            $this->endpoint_id = $endpoint_id;
            $this->access_token = $access_token;
            $this->refresh_token = $refresh_token;

            $this->setApiUrls();
        }

        public function isValidRCToken() {
            $header = array(
                "Content-Type: application/json",
                "Accept: application/json",
                "Authorization: Bearer " . $this->access_token
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->getAccountUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($result['errorCode']) && $result['errorCode'] == 'TokenInvalid')
                return false;

            return true;
        }

        public function getNewTokens() {
            $header = array(
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json"
            );

            //chesster423@gmail.com account
            if ($this->userId == 157)
                $header[] = "Authorization: Basic " . base64_encode($this->sandbox_client_id . ":" . $this->sandbox_client_secret);
            else
                $header[] = "Authorization: Basic " . base64_encode($this->production_client_id . ":" . $this->production_client_secret);

            $data = "grant_type=refresh_token&endpoint_id=".$this->endpoint_id."&refresh_token=". $this->refresh_token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->getTokensUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
            $result = json_decode(curl_exec($ch), true);
            curl_close($ch);

            return $result;
        }

        public function sendRequest($fileName, $faxNumber, $faxResolution = "High") {
            $boundaryId = uniqid();

            $data = array(
                "files" => array(
                    "file" => "https://app.30daycra.com/files/c2m_pdf/".$fileName
                ),
                "fields" => array(
                    "faxResolution" => $faxResolution,
                    "to" => $faxNumber
                )
            );

            $header = array(
                'Content-Type: multipart/form-data; boundary=-------------' . $boundaryId,
                'Accept: application/json'
            );

            $postData = $this->createMultipart($boundaryId, $data, $fileName);

            return json_decode($this->makeRequest($this->faxApiUrl, $postData, $header), true);
        }

        private function createMultipart($boundary, $data, $fileName) {
            $fields = $data['fields'];
            $files = $data['files'];

            $data = '';
            $eol = "\r\n";

            $delimiter = '-------------' . $boundary;

            foreach ($fields as $name => $content) {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                    . $content . $eol;
            }

            foreach ($files as $name => $content) {
                $data .= "--" . $delimiter . $eol
                    . 'Content-Disposition: form-data; name="' . $fileName . '"; filename="' . $fileName . '"' . $eol
                    // . 'Content-Type: application/pdf' . $eol
                    . 'Content-Type: multipart/form-data' . $eol                    
                    . 'Content-Transfer-Encoding: base64,';

                // $data .= $eol;
                $data .= base64_encode(file_get_contents($content)) . $eol;
            }

            $data .= "--" . $delimiter . "--" . $eol;

            return $data;
        }

        private function makeRequest($url, $postData, $header = array()) {

            if (empty($header)) {
                $header = array(
                    "Content-Type: application/json",
                    "Accept: application/json"
                );
            }

            $header[] = 'Authorization: Bearer ' . $this->access_token;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $result = curl_exec($ch);
            curl_close($ch);

            return $result;
        }

        private function setApiUrls() {
            $apiDomain = $this->productionDomain;

            //chesster423@gmail.com, dev account
            if ($this->userId == 157)
                $apiDomain = $this->sandboxDomain;

            $this->getTokensUrl = $apiDomain . '/restapi/oauth/token';
            $this->getAccountUrl = $apiDomain . '/restapi/v1.0/account/~';
            $this->faxApiUrl = $apiDomain . "/restapi/v1.0/account/~/extension/~/fax";
        }
    }
?>
