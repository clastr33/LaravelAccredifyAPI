<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VerificationResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccredifyController extends Controller
{
    public function verifyFile(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $verificationResult = 'verified';

        $data = $request->input('data');

        $megabyte = 1024 * 1024;
        $maxSize = 2;

        if (strlen($request->getContent()) > $maxSize  * $megabyte) {
            $verificationResult = ['error' => 'The JSON payload exceeds the maximum size of ' . $maxSize . 'MB.'];
        }

        // Validator Condition 1
        if ($verificationResult === 'verified') {
        $validatorCondition1 = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.recipient' => 'required|array',
            'data.recipient.name' => 'required|string',
            'data.recipient.email' => 'required|email',
        ]);

        if ($validatorCondition1->fails()) {
            $verificationResult = ['error' => 'invalid_recipient'];
        }
        }

        // Validator Condition 2
        if ($verificationResult === 'verified') {
            $validatorCondition2 = Validator::make($request->all(), [
                'data' => 'required|array',
                'data.issuer' => 'required|array',
                'data.issuer.name' => 'required|string',
                'data.issuer.identityProof' => 'required|array',
                'data.issuer.identityProof.key' => 'required|string',
                'data.issuer.identityProof.location' => 'required|string',
            ]);

            if ($validatorCondition2->fails()) {
                $verificationResult = ['error' => 'invalid_issuer'];
            }
        }

        // Check DNS TXT records
        if ($verificationResult === 'verified') {
            $issuerKey = $data['issuer']['identityProof']['key'];
            $issuerLocation = $data['issuer']['identityProof']['location'];

            $dnsRecords = dns_get_record($issuerLocation, DNS_TXT);

            $foundKey = false;
            foreach ($dnsRecords as $record) {
                if (isset($record['txt']) && strpos($record['txt'], $issuerKey) !== false) {
                    $foundKey = true;
                    break;
                }
            }

            if (!$foundKey) {
                $verificationResult = ['error' => 'invalid_issuer'];
            }
        }

        // Validator Condition 3
        if ($verificationResult === 'verified') {
            $validatorCondition3 = Validator::make($request->all(), [
                'signature' => 'required|array',
                'signature.type' => 'required|string',
                'signature.targetHash' => 'required|string',
            ]);

            // TODO AS: Condition 3: JSON has a valid signature

            if ($validatorCondition3->fails()) {
                $verificationResult = ['error' => 'invalid_signature'];
            }
        }

        // Success
        if ($verificationResult === 'verified') {
            $issuer = $data['issuer']['name'];

            // Store the result in the database
            VerificationResult::create([
                'user_id' => $userId,
                'file_type' => 'JSON',
                'verification_result' => 'verified',
            ]);

            $verificationResult = [
                'data' => [
                    'issuer' => $issuer,
                    'result' => 'verified'
                ]
            ];
        }

        return response()->json($verificationResult, 200);
    }
}
