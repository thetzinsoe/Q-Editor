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
        $path = $video->getRealPath();
        $fileName = $video->getClientOriginalName();

        try {
            // Step 1: Start the upload
            $startResponse = Http::withToken(env('VIMEO_ACCESS_TOKEN'))
                ->withOptions([
                    'curl' => [
                        CURLOPT_CAINFO => storage_path('app/cacert.pem'),
                    ],
                ])->post('https://api.vimeo.com/me/videos', [
                    'upload' => [
                        'approach' => 'tus',
                        'size' => filesize($path),
                    ],
                    'name' => $fileName
                ]);

            if ($startResponse->failed()) {
                \Log::error('Failed to start video upload', [
                    'response' => $startResponse->body(),
                    'status' => $startResponse->status(),
                    'headers' => $startResponse->headers()
                ]);
                return response()->json(['error' => 'Failed to start video upload.'], 400);
            }

            $startData = $startResponse->json();
            $uploadLink = $startData['upload']['upload_link'];
            $videoUri = $startData['uri'];

            // Step 2: Upload the video using the TUS protocol
            $uploadResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('VIMEO_ACCESS_TOKEN'),
                'Content-Type' => 'application/offset+octet-stream',
                'Tus-Resumable' => '1.0.0',
                'Upload-Offset' => 0,
                'Upload-Length' => filesize($path),
            ])->put($uploadLink, file_get_contents($path));

            if ($uploadResponse->failed()) {
                \Log::error('Failed to upload video file', [
                    'response' => $uploadResponse->body(),
                    'status' => $uploadResponse->status(),
                    'headers' => $uploadResponse->headers()
                ]);
                return response()->json(['error' => 'Failed to upload video file.'], 400);
            }

            // Extract video ID from the video URI
            $videoId = basename($videoUri);

            return response()->json(['video_id' => $videoId], 200);
        } catch (\Exception $e) {
            \Log::error('Exception occurred during video upload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An error occurred during video upload.'], 500);
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
