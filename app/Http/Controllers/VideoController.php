<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class VideoController extends Controller
{

    public function index()
    {
        return view('video.index');
    }

    public function upload(Request $request)
    {
        // Validate the request
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:200000',
        ]);

        // Get the file from the request
        $video = $request->file('video');
        $fileName = preg_replace('/[^\x20-\x7E]/', '', $video->getClientOriginalName());
        $path = preg_replace('/[^\x20-\x7E]/', '', $video->getRealPath());

        // Prepare the payload
        $payload = [
            'upload' => [
                'approach' => 'tus',
                'size' => filesize($path),
            ],
            'name' => $fileName,
        ];

        $client = new Client([
            'base_uri' => 'https://api.vimeo.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . env('VIMEO_ACCESS_TOKEN'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.vimeo.*+json;version=3.4',
            ],
            'verify' => base_path('storage/cacert-2024-07-02.pem'),
        ]);

        // Step 1: Initialize Upload
        try {
            $response = $client->post('me/videos', [
                'json' => $payload,
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                \Log::error('Failed to start video upload', [
                    'response' => (string) $response->getBody(),
                    'status' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                ]);
                return response()->json(['error' => 'Failed to start video upload.'], 400);
            }

            $startData = json_decode($response->getBody(), true);
            $uploadLink = $startData['upload']['upload_link'];
            $videoUri = $startData['uri'];

            \Log::info('Upload link received from Vimeo:', ['upload_link' => $uploadLink]);
        } catch (\Exception $e) {
            \Log::error('Exception occurred during video upload initialization', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Exception occurred during video upload initialization.'], 500);
        }

        // Step 2: Upload Video File
        try {
            $fileSize = filesize($path);
            $fileStream = fopen($path, 'r');

            $headers = [
                'Content-Type' => 'application/offset+octet-stream',
                'Upload-Offset' => 0,
                'Tus-Resumable' => '1.0.0',
            ];

            $putResponse = $client->patch($uploadLink, [
                'body' => $fileStream,
                'headers' => $headers,
            ]);

            if ($putResponse->getStatusCode() < 200 || $putResponse->getStatusCode() >= 300) {
                \Log::error('Failed to upload video file', [
                    'response' => (string) $putResponse->getBody(),
                    'status' => $putResponse->getStatusCode(),
                    'headers' => $putResponse->getHeaders(),
                ]);
                return response()->json(['error' => 'Failed to upload video file.'], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Exception occurred during video file upload', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Exception occurred during video file upload.'], 500);
        }
    }

    public function edit(Request $request)
    {
        $client = new Client();
        $response = $client->post('https://api.shotstack.io/stage/render', [
            'headers' => [
                'x-api-key' => env('SHOTSTACK_API_KEY'),
                'Content-Type' => 'application/json',
            ],
            'json' => $request->all(),
        ]);

        return response()->json(json_decode($response->getBody(), true));
    }
}
