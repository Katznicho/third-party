<?php

namespace App\Payments;

use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class YoAPI
{
    /**
     * Path to the log file
     * @var string
     */
    // private $log_file_path = 'C://Users/supportteam/Desktop/yoapi_log.log'; // Set the path to your log file

    // ... [Other methods]

    /**
     * Log message to file
     * @param string $message
     * @return void
     */
    // private function log_message($message)
    // {
    //     file_put_contents($this->log_file_path, $message.PHP_EOL, FILE_APPEND);
    // }

    /**
     * The Yo! Payments API Username
     * Required.
     * You may obtain the API Username from the web interface of your Payment Account.
     * @var string
     */
    private $username;

    /**
     * The Yo! Payments API Password
     * Required.
     * You may obtain the API Password from the web interface of your Payment Account.
     * @var string
     */
    private $password;

    /**
     * The Non Blocking Request variable
     * Optional.
     * Whether the connection to the Yo! Payments Gateway is maintained until your request is
     * fulfilled. "FALSE" maintains the connection till the request is complete.
     * Default: "FALSE"
     * Options: "FALSE", "TRUE".
     * @var string
     */
    private $NonBlocking = 'TRUE';

    /**
     * The External Reference variable
     * Optional.
     * An External Reference is something which yourself and the beneficiary agree upon
     * e.g. an invoice number
     * Default: NULL
     * @var string
     */
    private $external_reference = NULL;

    /**
     * The Internal Reference variable
     * Optional.
     * An Internal Reference is a reference code related to another Yo! Payments system transaction
     * If you are unsure about the meaning of this field, leave it as NULL
     * Default: NULL
     * @var string
     */
    private $internal_reference = NULL;

    /**
     * The Provider Reference Text variable
     * Optional.
     * A text you wish to be present in any confirmation message which the mobile money provider
     * network sends to the subscriber upon successful completion of the transaction.
     * Some mobile money providers automatically send a confirmatory text message to the subscriber
     * upon completion of transactions. This parameter allows you to provide some text which will
     * be appended to any such confirmatory message sent to the subscriber.
     * Default: NULL
     * @var string
     */
    private $provider_reference_text = NULL;

    /**
     * The Instant Notification URL variable
     * Optional.
     * A valid URL which is notified as soon as funds are successfully deposited into your account
     * A payment notification will be sent to this URL.
     * It must be properly URL encoded.
     * e.g. http://ipnurl?key1=This+value+has+encoded+white+spaces&key2=value
     * Any special XML Characters must be escaped or your request will fail
     * e.g. http://ipnurl?key1=This+value+has+encoded+white+spaces&amp;key2=value
     * Default: NULL
     * @var string
     */
    private $instant_notification_url = NULL;

    /**
     * The Failure Notification URL variable
     * Optional.
     * A valid URL which is notified as soon as your deposit request fails
     * A failure notification will be sent to this URL.
     * It must be properly URL encoded.
     * e.g. http://failureurl?key1=This+value+has+encoded+white+spaces&key2=value
     * Any special XML Characters must be escaped or your request will fail
     * e.g. http://failureurl?key1=This+value+has+encoded+white+spaces&amp;key2=value
     * Default: NULL
     * @var string
     */
    private $failure_notification_url = NULL;

    /**
     * The Authentication Signature Base64 variable
     * Optional.
     * It may be required to authenticate certain deposit requests.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required.
     * Default: NULL
     * @var string
     */
    private $authentication_signature_base64 = NULL;

    /**
     * The Deposit Transaction Type variable
     * Optional.
     * Set to "PUSH" if following up on the status of a push deposit funds transaction
     * Set to "PULL" if following up on the status of a pull deposit funds transaction
     * Default: "PULL"
     * Options: "PULL", "PUSH"
     * @var string
     */
    private $deposit_transaction_type = 'PULL';

    /**
     * The Yo Payments API URL
     * Required.
     * Default: "https://paymentsapi1.yo.co.ug/ybs/task.php"
     * Options:
     * * "https://paymentsapi1.yo.co.ug/ybs/task.php",
     * * "https://paymentsapi2.yo.co.ug/ybs/task.php",
     * * "https://41.220.12.206/services/yopaymentsdev/task.php" For Sandbox tests
     * @var string
     */
    private $YOURL = 'https://paymentsapi1.yo.co.ug/ybs/task.php';
    // private $YOURL = "https://paymentsdev1.yo.co.ug/yopaytest/task.php";
    // private $YOURL = "https://sandbox.yo.co.ug/services/yopaymentsdev/task.php";
    // https://sandbox.yo.co.ug/services/yopaymentsdev/task.php
    // https://paymentsapi1.yo.co.ug/ybs/task.php

    private $public_key_file = 'Yo_Uganda_Public_Certificate.crt';

    private $transaction_limit_account_identifier = NULL;

    /**
     * The Public Key Authentication Nonce
     * Required if public key authentication is enabled.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required.
     * Max Length: 255 charcaters
     * Reg Expression: [a-zA-Z0-9,-+]
     * Default: NULL
     * It must be unique for each API request made
     * @var string
     */
    private $public_key_authentication_nonce = NULL;

    /**
     * The Public Key Authentication Signature
     * Required if public key authentication is enabled.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required.
     * Max Length: 4096 charcaters
     * Reg Expression: [a-zA-Z0-9,-+]
     * Default: NULL
     * 1. It must be a concatenation of the parameters below in the indicated order:
     * * API Username
     * * Amount
     * * Account
     * * Narrative
     * * External Reference
     * * PublicKeyAuthenticationNonce
     * 2. The above concatenated string in 1 should be SHA1 hashed
     * 3. The SHA1 hash should be RSA signed using the private key associated with your public key
     * 4. Base64-encode the RSA signature calculated in 3 above
     * @var string
     */
    private $public_key_authentication_signature_base64 = NULL;

    /**
     * The location of the private key used to sign the public auth key
     * Required if public key authentication is enabled.
     * Contact Yo! Payments support services for clarification on the cases where this parameter
     * is required.
     * Max Length: 255 charcaters
     * Reg Expression: [a-zA-Z0-9,-+]
     * Default: NULL
     * It must be unique for each API request made
     * @var string
     */
    private $private_key_file_location = NULL;

    /**
     * YoAPI constructor.
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Set the API Username
     * @param string $username The Yo Payments API username to use
     * @return void
     */
    public function set_username($username)
    {
        $this->username = $username;
    }

    /**
     * Returns the API Username
     * @return string
     */
    public function get_username()
    {
        return $this->username;
    }

    /**
     * Set the API Password
     * @param string $password The Yo Payments API Password to use
     * @return void
     */
    public function set_password($password)
    {
        $this->password = $password;
    }

    /**
     * Returns the API Password
     * @return string
     */
    public function get_password()
    {
        return $this->password;
    }

    /**
     * Set the YO URL
     * @param string $yoURL The URL to submit API requests to
     * @return void
     */
    public function set_URL($yoURL)
    {
        $this->YOURL = $yoURL;
    }

    /**
     * Returns the YO URL
     * @return string
     */
    public function get_URL()
    {
        return $this->YOURL;
    }

    /**
     * Set the PUBLIC KEY PATH
     * @param string $public_key_file_URL
     * @return void
     */
    public function set_public_key_file_URL($public_key_file_URL)
    {
        $this->public_key_file = $public_key_file_URL;
    }

    /**
     * Returns the PUBLIC KEY PATH
     * @return string
     */
    public function get_public_key_file_URL()
    {
        return $this->public_key_file;
    }

    /**
     * Set the NonBlocking Variable
     * @param string $nonblocking TRUE for nonblocking API requests
     * @return void
     */
    public function set_nonblocking($nonblocking)
    {
        $this->NonBlocking = $nonblocking;
    }

    /**
     * Returns the NonBlocking Variable
     * @return string
     */
    public function get_nonblocking()
    {
        return $this->NonBlocking;
    }

    /**
     * Set the External Reference
     * @param string $external_reference Used when submitting payment requests
     * @return void
     */
    public function set_external_reference($external_reference)
    {
        $this->external_reference = $external_reference;
    }

    /**
     * Returns the external_reference Variable
     * @return string
     */
    public function get_external_reference()
    {
        return $this->external_reference;
    }

    /**
     * Set the Internal Reference
     * @param string $internal_reference Used when submitting payment requests
     * @return void
     */
    public function set_internal_reference($internal_reference)
    {
        $this->internal_reference = $internal_reference;
    }

    /**
     * Returns the internal_reference Variable
     * @return string
     */
    public function get_internal_reference()
    {
        return $this->internal_reference;
    }

    /**
     * Set the Provider Reference
     * @param string $provider_reference_text Used when submitting payment requests
     * @return void
     */
    public function set_provider_reference_text($provider_reference_text)
    {
        $this->provider_reference_text = $provider_reference_text;
    }

    /**
     * Returns the provider_reference_text Variable
     * @return string
     */
    public function get_provider_reference_text()
    {
        return $this->provider_reference_text;
    }

    /**
     * Set the Instant Notification URL
     * @param string $instant_notification_url Useful for nonblocking requests
     * @return void
     */
    public function set_instant_notification_url($instant_notification_url)
    {
        $this->instant_notification_url = $instant_notification_url;
    }

    /**
     * Returns the instant_notification_url Variable
     * @return string
     */
    public function get_instant_notification_url()
    {
        return $this->instant_notification_url;
    }

    /**
     * Set the Failure Notification URL
     * @param string $failure_notification_url Useful for nonblocking requests
     * @return void
     */
    public function set_failure_notification_url($failure_notification_url)
    {
        $this->failure_notification_url = $failure_notification_url;
    }

    /**
     * Returns the failure_notification_url Variable
     * @return string
     */
    public function get_failure_notification_url()
    {
        return $this->failure_notification_url;
    }

    /**
     * Set the Authentication Signature Base64
     * @param string $authentication_signature_base64
     * @return void
     */
    public function set_authentication_signature_base64($authentication_signature_base64)
    {
        $this->authentication_signature_base64 = $authentication_signature_base64;
    }

    /**
     * Returns the Authentication Signature Base64 Variable
     * @return string
     */
    public function get_authentication_signature_base64()
    {
        return $this->authentication_signature_base64;
    }

    /**
     * Set the Transaction Limit Account Identifier
     * Refer to your account administrator for using this feature
     * @param string $transaction_limit_account_identifier
     * @return void
     */
    public function set_transaction_limit_account_identifier($transaction_limit_account_identifier)
    {
        $this->transaction_limit_account_identifier = $transaction_limit_account_identifier;
    }

    /**
     * Returns the Trasaction Limit Account Identifier Variable
     * @return string
     */
    public function get_transaction_limit_account_identifier()
    {
        return $this->transaction_limit_account_identifier;
    }

    /**
     * Set the Public key authentication nonce
     * Refer to your account administrator for using this feature
     * @param string $public_key_authentication_nonce
     * @return void
     */
    public function set_public_key_authentication_nonce($public_key_authentication_nonce)
    {
        $this->public_key_authentication_nonce = $public_key_authentication_nonce;
    }

    /**
     * Returns the Public Key Authentication Nonce Variable
     * @return string
     */
    public function get_public_key_authentication_nonce()
    {
        return $this->public_key_authentication_nonce;
    }

    /**
     * Set the Public Key Authentication Base64-Encoded Signature
     * Refer to your account administrator for using this feature
     * @param string $public_key_authentication_signature_base64
     * @return void
     */
    public function set_public_key_authentication_signature_base64($public_key_authentication_signature_base64)
    {
        $this->public_key_authentication_signature_base64 = $public_key_authentication_signature_base64;
    }

    /**
     * Returns the Public Key Authentication Base64-Encoded Signature Variable
     * @return string
     */
    public function get_public_key_authentication_signature_base64()
    {
        return $this->public_key_authentication_signature_base64;
    }

    /**
     * Set the Private Key File Variable
     * Refer to your account administrator for using this feature
     * @param string $private_key_file_location
     * @return void
     */
    public function set_private_key_file_location($private_key_file_location)
    {
        $this->private_key_file_location = $private_key_file_location;
    }

    /**
     * Returns the Private Key File Variable
     * @return string
     */
    public function get_private_key_file_location()
    {
        return $this->private_key_file_location;
    }

    /**
     * Request Mobile Money User to deposit funds into your account
     * Shortly after you submit this request, the mobile money user receives an on-screen
     * notification on their mobile phone. The notification informs the mobile money user about
     * your request to transfer funds out of their account and requests them to authorize the
     * request to complete the transaction.
     * This request is not supported by all mobile money operator networks
     * @param integer  $msisdn the mobile money phone number in the format 256772123456
     * @param double $amount the amount of money to deposit into your account (floats are supported)
     * @param string $narrative the reason for the mobile money user to deposit funds
     * @return array
     */
    public function ac_deposit_funds($msisdn, $amount, $narrative)
    {
        $xml = '';
        $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate>';
        $xml .= '<Request>';
        $xml .= '<APIUsername>' . $this->username . '</APIUsername>';
        $xml .= '<APIPassword>' . $this->password . '</APIPassword>';
        $xml .= '<Method>acdepositfunds</Method>';
        $xml .= '<NonBlocking>' . $this->NonBlocking . '</NonBlocking>';
        $xml .= '<Account>' . $msisdn . '</Account>';
        $xml .= '<Amount>' . $amount . '</Amount>';
        $xml .= '<Narrative>' . $narrative . '</Narrative>';
        if ($this->external_reference != NULL) {
            $xml .= '<ExternalReference>' . $this->external_reference . '</ExternalReference>';
        }
        if ($this->internal_reference != NULL) {
            $xml .= '<InternalReference>' . $this->internal_reference . '</InternalReference>';
        }
        if ($this->provider_reference_text != NULL) {
            $xml .= '<ProviderReferenceText>' . $this->provider_reference_text . '</ProviderReferenceText>';
        }
        if ($this->instant_notification_url != NULL) {
            $xml .= '<InstantNotificationUrl>' . $this->instant_notification_url . '</InstantNotificationUrl>';
        }
        if ($this->failure_notification_url != NULL) {
            $xml .= '<FailureNotificationUrl>' . $this->failure_notification_url . '</FailureNotificationUrl>';
        }
        if ($this->authentication_signature_base64 != NULL) {
            $xml .= '<AuthenticationSignatureBase64>' . $this->authentication_signature_base64 . '</AuthenticationSignatureBase64>';
        }
        $xml .= '</Request>';
        $xml .= '</AutoCreate>';

        // Log the XML request for debugging
        Log::info('=== YOAPI XML REQUEST ===', [
            'method' => 'ac_deposit_funds',
            'phone' => $msisdn,
            'amount' => $amount,
            'narrative' => $narrative,
            'narrative_length' => strlen($narrative),
            'username' => substr($this->username, 0, 3) . '***',
            'has_password' => !empty($this->password),
            'external_reference' => $this->external_reference,
            'instant_notification_url' => $this->instant_notification_url,
            'xml_length' => strlen($xml)
        ]);

        $xml_response = $this->get_xml_response($xml);
        
        // Log the XML response for debugging
        Log::info('=== YOAPI XML RESPONSE ===', [
            'response_length' => strlen($xml_response),
            'response_preview' => substr($xml_response, 0, 500)
        ]);

        $simpleXMLObject = new SimpleXMLElement($xml_response);
        $response = $simpleXMLObject->Response;

        $result = array();
        $result['Status'] = (string) $response->Status;
        $result['StatusCode'] = (string) $response->StatusCode;
        $result['StatusMessage'] = (string) $response->StatusMessage;
        $result['TransactionStatus'] = (string) $response->TransactionStatus;
        if (!empty($response->ErrorMessageCode)) {
            $result['ErrorMessageCode'] = (string) $response->ErrorMessageCode;
        }
        if (!empty($response->ErrorMessage)) {
            $result['ErrorMessage'] = (string) $response->ErrorMessage;
        }
        if (!empty($response->TransactionReference)) {
            $result['TransactionReference'] = (string) $response->TransactionReference;
        }
        if (!empty($response->MNOTransactionReferenceId)) {
            $result['MNOTransactionReferenceId'] = (string) $response->MNOTransactionReferenceId;
        }
        if (!empty($response->IssuedReceiptNumber)) {
            $result['IssuedReceiptNumber'] = (string) $response->IssuedReceiptNumber;
        }

        return $result;
    }

    /**
     * Check the status of a transaction that was earlier submitted for processing.
     * Its particularly useful where the NonBlocking is set to TRUE.
     * It can also be used to check on any other transaction on the system.
     * @param string $transaction_reference the response from the Yo! Payments Gateway that uniquely identifies the transaction whose status you are checking
     * @param string $private_transaction_reference The External Reference that was used to carry out a transaction
     * @return array
     */
    public function ac_transaction_check_status($transaction_reference, $private_transaction_reference = NULL)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AutoCreate>';
        $xml .= '<Request>';
        $xml .= '<APIUsername>' . $this->username . '</APIUsername>';
        $xml .= '<APIPassword>' . $this->password . '</APIPassword>';
        $xml .= '<Method>actransactioncheckstatus</Method>';
        if ($transaction_reference != NULL) {
            $xml .= '<TransactionReference>' . $transaction_reference . '</TransactionReference>';
        }
        if ($private_transaction_reference != NULL) {
            $xml .= '<PrivateTransactionReference>' . $private_transaction_reference . '</PrivateTransactionReference>';
        }
        $xml .= '<DepositTransactionType>' . $this->deposit_transaction_type . '</DepositTransactionType>';
        $xml .= '</Request>';
        $xml .= '</AutoCreate>';

        // Log the XML request for debugging
        Log::info('YoAPI Transaction Check Request: ' . $xml);

        $xml_response = $this->get_xml_response($xml);

        // Log the XML response for debugging
        Log::info('YoAPI Transaction Check Response: ' . $xml_response);

        $simpleXMLObject = new SimpleXMLElement($xml_response);
        $response = $simpleXMLObject->Response;

        $result = [];
        $result['Status'] = (string) $response->Status ?? null;
        $result['StatusCode'] = (string) $response->StatusCode ?? null;
        $result['StatusMessage'] = (string) $response->StatusMessage ?? null;
        $result['TransactionStatus'] = (string) $response->TransactionStatus ?? null;
        $result['TransactionReference'] = (string) $response->TransactionReference ?? null;
        $result['MNOTransactionReferenceId'] = (string) $response->MNOTransactionReferenceId ?? null;
        $result['Amount'] = (string) $response->Amount ?? null;
        $result['AmountFormatted'] = (string) $response->AmountFormatted ?? null;
        $result['CurrencyCode'] = (string) $response->CurrencyCode ?? null;
        $result['TransactionInitiationDate'] = (string) $response->TransactionInitiationDate ?? null;
        $result['TransactionCompletionDate'] = (string) $response->TransactionCompletionDate ?? null;
        $result['IssuedReceiptNumber'] = (string) $response->IssuedReceiptNumber ?? null;

        // Log the parsed result for debugging
        Log::info('Parsed YoAPI Transaction Check Result: ', $result);

        return $result;
    }

    protected function get_xml_response($xml)
    {
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $this->YOURL);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($soap_do, CURLOPT_TIMEOUT, 120);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_POST, true);
        curl_setopt($soap_do, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($soap_do, CURLOPT_VERBOSE, false);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER, array('Content-Type: text/xml', 'Content-transfer-encoding: text', 'Content-Length: ' . strlen($xml)));

        $xml_response = curl_exec($soap_do);
        curl_close($soap_do);

        return $xml_response;
    }
}
