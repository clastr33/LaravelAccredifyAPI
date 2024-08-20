<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccredifyController extends Controller
{
    public function verifyFile(Request $request)
    {
        // Validator Condition 1
        $validatorCondition1 = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.recipient' => 'required|array',
            'data.recipient.name' => 'required|string',
            'data.recipient.email' => 'required|email',
        ]);

        if ($validatorCondition1->fails()) {
            return response()->json(['error' => 'invalid_recipient'], 200);
        }

        // Validator Condition 2
        $validatorCondition2 = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.issuer' => 'required|array',
            'data.issuer.name' => 'required|string',
            'data.issuer.identityProof' => 'required|array',
            'data.issuer.identityProof.key' => 'required|string',
            'data.issuer.identityProof.location' => 'required|string',
        ]);

        // TODO AS: For the sample JSON,
        //  did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller
        //  is found in the DNS TXT record of ropstore.accredify.io
        //  Can use Google DNS API for DNS lookup
        //  e.g. https://dns.google/resolve?name=ropstore.accredify.io&type=TXT

        if ($validatorCondition2->fails()) {
            return response()->json(['error' => 'invalid_issuer'], 200);
        }

        // Validator Condition 3
        $validatorCondition3 = Validator::make($request->all(), [
            'signature' => 'required|array',
            'signature.type' => 'required|string',
            'signature.targetHash' => 'required|string',
        ]);

        // TODO AS: Condition 3: JSON has a valid signature

        if ($validatorCondition3->fails()) {
            return response()->json(['error' => 'invalid_signature'], 200);
        }

        // Success
        $data = $request->input('data');
        $issuer = $data['issuer']['name'];

        return response()->json([
            'data' => [
                'issuer' => $issuer,
                'result' => 'verified'
            ]
        ], 200);
    }
}
