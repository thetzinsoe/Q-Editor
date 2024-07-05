<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <form id="video-upload-form" enctype="multipart/form-data">
        <label for="video">Select video:</label>
        <input type="file" id="video" name="video" accept="video/*" required>
        <button type="submit">Upload Video</button>
    </form>

    <script>
        // Set the CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        console.log($('meta[name="csrf-token"]').attr('content'));

        $('#video-upload-form').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            $.ajax({
                url: 'video/upload-video',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Video ID:', response.video_id);
                    alert('Video uploaded successfully. Video ID: ' + response.video_id);
                },
                error: function(error) {
                    console.error('Error:', error);
                    alert('Failed to upload video.');
                }
            });
        });
    </script>
</body>
</html>
