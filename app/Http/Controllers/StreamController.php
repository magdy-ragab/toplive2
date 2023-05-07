<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StreamController extends Controller
{

    /**
     * Get a token from the Agora API by providing a room name and API ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function agoraToken(Request $request)
    {
        // Validate the request parameters
        $validatedData = $request->validate([
            'room_name' => 'required|string',
            'app_id' => 'required|integer',
        ]);

        // Get the validated room name and app ID
        $room_name = $validatedData['room_name'];
        $app_id = $validatedData['app_id'];

        // Set the API endpoint URL
        $url = "https://api.agora.io/v1/token";

        // Set the request parameters
        $params = array(
            "app_id" => $app_id,
            "channel_name" => $room_name,
            "uid" => 0,
            "role" => "publisher",
            "expire" => 3600,
        );

        // Send the request to the Agora API and get the token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // handle errors
        if ($response === false) {
            return response()->json(['error' => curl_error($ch)], 500);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => json_last_error_msg()], 500);
        }

        $result = json_decode($response, true);

        // Check if the token is present in the response
        if (isset($result["rtcToken"]) && $result["rtcToken"]) {
            // Return the token as a JSON response
            return response()->json(['token' => $result["rtcToken"]]);
        } else {
            return response()->json(['error' => "token is not present"], 500);
        }
    }
}
