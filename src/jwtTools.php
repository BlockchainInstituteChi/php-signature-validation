<?php

/*

Copyright (c) 2019 Blockchain Institute

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace Blockchaininstitute;

use Mdanter\Ecc\Crypto\Signature\SignHasher;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Curves\CurveFactory;
use Mdanter\Ecc\Curves\SecgCurve;
use Mdanter\Ecc\Math\GmpMathInterface;

use kornrunner\Secp256k1;
use kornrunner\Signature\Signature as kSig;

use Tuupola\Base58;

class jwtTools
{

    /**
     * Create a new Skeleton Instance
     */
    public function __construct()
    {
    }


    /**
     * Friendly welcome
     *
     * @param string $phrase Phrase to return
     *
     * @return string Returns the phrase passed in
     */

    public function verifyJWT ($jwt) {

        $publicKeyLong = $this->resolvePublicKeyFromJWT($jwt);

        $publicKey =  substr($publicKeyLong, 2);

        $opt = $this->deconstructAndDecode($jwt);
        // print_r("\r\nopt\r\n");
        // print_r(json_decode(base64_decode(urldecode($opt["body"]))));

        $secp256k1 = new Secp256k1();
        $CurveFactory = new CurveFactory;
        $adapter = EccFactory::getAdapter();
        $generator = CurveFactory::getGeneratorByName('secp256k1');

        $signatureSet = $this->createSignatureObject($opt['signature']);   
        $signatureK = new kSig ($signatureSet["rGMP"], $signatureSet["sGMP"], $signatureSet["v"]);

        $algorithm = 'sha256';
        
        $document = $opt['header'] . "." . $opt['body'];    
        $hash = hash($algorithm, $document);

        return $secp256k1->verify($hash, $signatureK, $publicKey);

    }


    public function resolveDIDFromJWT ($jwt) {
        $infuraPayload = $this->resolve_did("uPortProfileIPFS1220", $jwt);

        $infuraResponse = $this->resolveInfuraPayload($infuraPayload);

        $address = json_decode($infuraResponse, false);

        $addressOutput = $address->result;

        $ipfsEncoded = $this->registryEncodingToIPFS($addressOutput);

        $ipfsResult = json_decode($this->fetchIpfs($ipfsEncoded));
        
        return $ipfsResult;

    }

    public function resolvePublicKeyFromJWT ($jwt) {

        $ipfsResult = $this->resolveDIDFromJWT($jwt);
        
        return $ipfsResult->publicKey;

    }

    // This is a very mediocre hack that needs to be resolved in the future - function newTopic(topicName) in uport-connect/src/topicFactory.js npm module for expected behaviour
    public function chasquiFactory ($topicName) {
        
        $CHASQUI_URL = 'https://chasqui.uport.me/api/v1/topic/';
        return $CHASQUI_URL;

    }

    // function decodeJWT(jwt) {
    //   if (!jwt) throw new Error('no JWT passed into decodeJWT');
    //   var parts = jwt.match(/^([a-zA-Z0-9_-]+)\.([a-zA-Z0-9_-]+)\.([a-zA-Z0-9_-]+)$/);
    //   if (parts) {
    //     return {
    //       header: JSON.parse(_base64url2.default.decode(parts[1])),
    //       payload: JSON.parse(_base64url2.default.decode(parts[2])),
    //       signature: parts[3],
    //       data: parts[1] + '.' + parts[2]
    //     };
    //   }
    //   throw new Error('Incorrect format JWT');
    // }

    public function resolveInfuraPayload ($infuraPayload) {
        $params  = (object)[];
        $params     ->to    = $infuraPayload->rpcUrl;
        $params     ->data  = $infuraPayload->callString;

        $payloadOptions = (object)[];

        $payloadOptions->method     = 'eth_call';
        $payloadOptions->id         = 1         ;
        $payloadOptions->jsonrpc    = '2.0'     ;
        $payloadOptions->params     = array($params, 'latest');

        $payloadOptions = json_encode($payloadOptions);

        $options = array(CURLOPT_URL => 'https://rinkeby.infura.io/uport-lite-library',
                     CURLOPT_HEADER => false,
                     CURLOPT_FRESH_CONNECT => true,
                     CURLOPT_POSTFIELDS => $payloadOptions,
                     CURLOPT_RETURNTRANSFER => true,
                     CURLOPT_POST => 1,
                     CURLOPT_HTTPHEADER => array( 'Content-Type: application/json')
                    );

        $ch = curl_init();

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;

    }

    public function registryEncodingToIPFS ($hexStr) {
        $base58 = new Base58([
            "characters" => Base58::IPFS,
            "version" => 0x00
        ]);
        $sliced = '1220' . subStr($hexStr, 2);
        $decoded = pack("H*", $sliced);
        $base58enc = $base58->encode($decoded);

        return $base58enc;
    }

    public function fetchIpfs($ipfsHash) {
        $uri = "https://ipfs.infura.io/ipfs/" . $ipfsHash;

        $options = array(CURLOPT_URL => $uri,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_HTTPHEADER => array( 'Content-Type: application/json')
            );

        $ch = curl_init();

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function deconstructAndDecode ($jwt) {

        $exp = explode(".", $jwt);
        $decodedParts = [
            "header" => $exp[0],
            "body" => $exp[1],
            "signature" => $exp[2]
        ];
        return $decodedParts;

    }


    public function resolve_did($profileId, $jwt)
    {
        $senderMnid = $this->getSenderMnid($jwt);
        $signerMnid = $this->getAudienceMnid($jwt);

        if ( ( $senderMnid === null ) || ( $signerMnid === null ) ) {
            $signerMnid = $senderMnid = $this->getIssuerMnid($jwt);
            print_r("\r\ngot issuer mnid\r\n");
            print_r($signerMnid);

            return $this->prepareRegistryCallString($profileId, $senderMnid, $senderMnid);
        } else {
            print_r("\r\ngot sender mnid\r\n");
            print_r($senderMnid);
            
            return $this->prepareRegistryCallString($profileId, $senderMnid, $senderMnid);
        }
        
    }

    public function getIssuerMnid ($jwt) {

        // $jsonBody = $this->base64url_decode(($this->deconstructAndDecode($jwt))["body"]);
        $jsonBody = base64_decode(urldecode(($this->deconstructAndDecode($jwt))["body"]));

        print_r("\r\n\r\njwt:\r\n");
        print_r($jsonBody);
        print_r("\r\n");
        print_r(json_decode($jsonBody));

        if ( isset((json_decode($jsonBody, true))['iss']) ) {
            $sender = (json_decode($jsonBody, true))['iss'];
            return $sender;
        } else {       
            return null; 
        }

    }

    public function getSenderMnid ($jwt) {

        // $jsonBody = $this->base64url_decode(($this->deconstructAndDecode($jwt))["body"]);
        $jsonBody = base64_decode(urldecode(($this->deconstructAndDecode($jwt))["body"]));        

        print_r("\r\n\r\njwt:\r\n");
        print_r($jsonBody);
        print_r("\r\n");
        print_r(json_decode($jsonBody));

        if ( isset((json_decode($jsonBody, true))['nad']) ) {
            $sender = (json_decode($jsonBody, true))['nad'];
            return $sender;
        } else {       
            return null; 
        }

    }

    public function getAudienceMnid ($jwt) {

        // $jsonBody = $this->base64url_decode(($this->deconstructAndDecode($jwt))["body"]);
        $jsonBody = base64_decode(urldecode(($this->deconstructAndDecode($jwt))["body"]));
        print_r("\r\n\r\njwt:\r\n");
        print_r($jsonBody);
        print_r("\r\n");
        print_r(json_decode($jsonBody));


        if ( isset(json_decode($jsonBody, true)['aud']) ) {
            $sender = (json_decode($jsonBody, true))['aud'];
            return $sender;

        } else {
            return null;
        }
        
    }    

    // Utilities Functionality
    public function base64url_decode( $payload ){
        print_r("\r\n decoding payload: \r\n");
        print_r($payload);

        // converts from base64url to base64, then decodes
        return base64_decode( strtr( $payload, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $payload )) % 4 ));

    }   

    public function encodeByteArrayToHex ($byteArray) {

        $chars = array_map("chr", $byteArray);
        $bin = join($chars);
        $hex = bin2hex($bin);

        return $hex;

    }

    public function String2Hex($string){
        $hex='';
        for ($i=0; $i < strlen($string); $i++){
            // echo "\r\nconverting " . $string[$i] . " to " . dechex(ord($string[$i]));

            $newBit = dechex(ord($string[$i]));

            if ( strlen($newBit) == 1 ) {
                $newBit = "0" . $newBit;
            }

            $hex .= $newBit;
        }
        return $hex;
    }

    private function createSignatureObject ($signature) {

        $rawSig = $this->base64url_decode($signature);

        $sigObj = [
            "v" => 0,
            "rGMP" => gmp_init("0x" . $this->String2Hex(substr( $rawSig, 0, 32 )), 16),
            "sGMP" => gmp_init("0x" . $this->String2Hex(substr( $rawSig, 32, 64 )), 16)
        ];

        return $sigObj;

    }

    private function prepareRegistryCallString($registrationIdentifier, $issuerId, $subjectId) {

        print_r("\r\nprepRegCall\r\n" . $registrationIdentifier . "\r\n" . $issuerId . "\r\n\r\n" . $subjectId . "\r\n"); 

        $callObj = (object)[];
        $issuer = $this->eaeDecode($issuerId);
        $subject = $this->eaeDecode($subjectId);
        $networks = $this->getNetworks();

        if ( $issuer['network'] !== $subject['network'] ) {
            return "Error: Subject and Issuer must be in the same network!";
        }

        if (!$networks[$issuer['network']]) {
           return 'Network id ' . $issuer['network'] . ' is not configured';
        } 
        
        $callObj->rpcUrl = $networks[$issuer['network']]['registry'];
        $callObj->registryAddress = $networks[$issuer['network']]['registry'];
        $callObj->functionSignature = '0x447885f0';
        $callObj->callString = $this->encodeFunctionCall($callObj->functionSignature, $registrationIdentifier, $issuer['address'], $subject['address']);

        return $callObj;

    }

    private function encodeFunctionCall ($functionSignature, $registrationIdentifier, $issuer, $subject) {

        $callString = $functionSignature;

        $regStub = $this->String2Hex($registrationIdentifier);
        $issStub = subStr($issuer, (-1)*(strlen($issuer) - 2));
        $subStub = subStr($subject, (-1)*(strlen($issuer) - 2));

        $callString .= $this->pad('0000000000000000000000000000000000000000000000000000000000000000', $regStub, false);
        $callString .= $this->pad('0000000000000000000000000000000000000000000000000000000000000000', $issStub, true);
        $callString .= $this->pad('0000000000000000000000000000000000000000000000000000000000000000', $subStub, true);
        return $callString;

    }

    private function ascii2Hex($string){
        $hex='';
        for ($i=0; $i < strlen($string); $i++){
            $hex .= dechex(ord($string[$i]));
        }
        return $hex;
    }

    private function pad ($pad, $str, $padLeft) {
        if ( gettype($str) == "undefined" ) {
            return $pad;
        }
        if ( $padLeft === true ) {
            return substr( ($pad . $str), (-1)*strlen($pad) );
        } else {
            return substr( ($str . $pad), 0, strlen($pad) );
        }
    }

    private function eaeDecode ($payload) {
        $base58 = new Base58([
            "characters" => Base58::IPFS,
            "version" => 0x00
        ]);
        $data = unpack( "C*", $base58->decode($payload) );
        $netLength = sizeof($data) - 24;
        $network = array_slice($data, 1, $netLength - 1);
        $address = array_slice($data, $netLength, 20 + $netLength - 2);
        $network = "0x" . $this->encodeByteArrayToHex($network);
        $address = "0x" . $this->encodeByteArrayToHex($address);
        return [
            "address" => $address,
            "network" => $network
        ];              
    }

    private function getNetworks () {
        return [
              '0x01' => [
                    'registry' => '0xab5c8051b9a1df1aab0149f8b0630848b7ecabf6',
                    'rpcUrl' => 'https://mainnet.infura.io'
              ], 
              '0x02' => [
                    'registry' => '0x41566e3a081f5032bdcad470adb797635ddfe1f0',
                    'rpcUrl' => 'https://ropsten.infura.io'
              ], 
              '0x03' => [
                    'registry' => '0x5f8e9351dc2d238fb878b6ae43aa740d62fc9758',
                    'rpcUrl' => 'https://kovan.infura.io'
              ],
              '0x04' => [
                    'registry' => '0x2cc31912b2b0f3075a87b3640923d45a26cef3ee',
                    'rpcUrl' => 'https://rinkeby.infura.io'
              ]
        ];
    }

}

?>