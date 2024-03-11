<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Exception\AwsException;

use Aws\S3\S3Client;

class ImageController extends Controller
{
    public function upload()
    {
        return view('upload');
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $extension  = request()->file('image')->getClientOriginalExtension(); 
            $image_name = time() .'_' . $request->title . '.' . $extension;
            $path = $request->file('image')->storeAs(
                'images',
                $image_name,
                's3'
            );

            Image::create([
                'title'=>$request->title,
                'image'=>$path
            ]);
            
            // return redirect()->back()->with([
            //     'message'=> "Image uploaded successfully",
            // ]);
            
            $disk = 'invoices';
            return Storage::disk($disk)->url( $path);
        }
    }

    public function showForm()
    {
        return view('upload_form');
    }


    public function uploadWithS3(Request $request)
    {
        try {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $fileName = uniqid('file_') . '.' . $image->getClientOriginalExtension();
                Storage::disk('s3')->put($fileName, file_get_contents($image));

                $imageUrl = $this->createPresignedUrl($fileName);

                return redirect()->route('get.image', $fileName)->with('imageUrl', $imageUrl);
            }
        } catch (\Exception $e) {
            $this->logToCloudWatch("Error uploading file: " . $e->getMessage());
            \Log::error($e->getMessage());
            return response()->json(['error' => 'No image uploaded.'], 400);
        }
    }

    private function createPresignedUrl($key)
    {
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);

        $bucket = config('filesystems.disks.s3.bucket');

        $command = $s3Client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        return $s3Client->createPresignedRequest($command, '+5 minutes')->getUri()->__toString();
    }

    private function logToCloudWatch($message)
    {
        $client = new CloudWatchLogsClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        try {
            $result = $client->createLogStream([
                'logGroupName' => env('AWS_CLOUDWATCH_LOG_GROUP_NAME'),
                'logStreamName' => env('AWS_CLOUDWATCH_LOG_STREAM_NAME'),
            ]);

            $client->putLogEvents([
                'logGroupName' => env('AWS_CLOUDWATCH_LOG_GROUP_NAME'),
                'logStreamName' => env('AWS_CLOUDWATCH_LOG_STREAM_NAME'),
                'logEvents' => [
                    [
                        'timestamp' => round(microtime(true) * 1000),
                        'message' => $message,
                    ],
                ],
            ]);
        } catch (AwsException $e) {
            \Log::error("Error sending log to CloudWatch Logs: " . $e->getMessage());
        }
    }


    public function getImage($imageName)
    {
        $s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);

        $bucket = config('filesystems.disks.s3.bucket');

        try {
            // $result = $s3Client->getObject([
            //     'Bucket' => $bucket,
            //     'Key'    => $imageName,
            // ]);

            //$objectData = $result['Body']->getContents();
            $imageUrl = $s3Client->getObjectUrl($bucket, $imageName);
            return view('show-img')->with('imageUrl', $imageUrl);

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL;
        }
    }

    public function getAllImages()
    {
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);

        $bucket = config('filesystems.disks.s3.bucket');

        try {
            $objects = $s3->listObjectsV2([
                'Bucket' => $bucket,
            ]);
        
            foreach ($objects['Contents'] as $object) {
                echo $object['Key'] . PHP_EOL ."</br>";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . PHP_EOL;
        }
    }

    public function downloadFile()
    {
        $filePath = 'offer-letter.pdf'; // Adjust the file name

        // Use the AWS S3 disk to get the file
        $fileContent = Storage::disk('s3')->get($filePath);

        // Set the content type for the response
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ];

        // Return the file content as a response
        return response($fileContent, 200, $headers);
    }

}
