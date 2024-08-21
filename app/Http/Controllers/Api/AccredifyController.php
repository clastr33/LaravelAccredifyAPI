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
        $signature = $request->input('signature');

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

            if ($validatorCondition3->fails()) {
                $verificationResult = ['error' => 'invalid_signature'];
            }
        }

        // JSON has a valid signature
        if ($verificationResult === 'verified') {
            // Step 1: Flatten the data object into dot notation
            // {
            //    "name": "Certificate of Completion",
            //    "recipient.name": "Marty McFly",
            // ... }
            $flattenedData = $this->flattenToDotNotation($data);

            // Step 2: Compute a hash for each key-value pair. Use sha256.
            //[ "8d79f393cc294fd3daca0402209997db5ff8a2ad1a498702f0956952677881ae",
            // "cd77eab0fa4b92136f883dfe6fe63d7ee68a98a7697874609a5f9d24adaa0f04",
            // ... ]
            $hashes = [];
            foreach ($flattenedData as $key => $value) {
                $concatenated = '{"' . $key . '":"' . $value . '"}';
                $hashes[] = hash('sha256', $concatenated);
            }

            // Step 3: Sort the hashes and compute the final target hash
            sort($hashes);

            $finalHashInputArr = [];
            foreach ($hashes as $hash) {
                $finalHashInputArr[] = '"' . $hash . '"';
            }
            $finalHashInputLine = implode(',', $finalHashInputArr);
            $finalHashInputLine = '[' . $finalHashInputLine . ']';

            // Hash them all together
            $computedTargetHash = hash('sha256', $finalHashInputLine);
            if ($computedTargetHash !== $signature['targetHash']) {
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

    private function flattenToDotNotation(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $prefixedKey = $prefix . $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenToDotNotation($value, $prefixedKey . '.'));
            } else {
                $result[$prefixedKey] = $value;
            }
        }
        return $result;
    }
}
