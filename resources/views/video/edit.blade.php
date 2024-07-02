
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Editor</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <form id="video-editor-form">
        <!-- Add form inputs for video editing parameters -->
        <button type="submit">Edit Video</button>
    </form>

    <script>
        $('#video-editor-form').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            timeline: {
                background: "#000000",
                soundtrack: {
                    src: "https://s3-ap-southeast-2.amazonaws.com/shotstack-assets/music/disco.mp3",
                    effect: "fadeInFadeOut"
                },
                fonts: [],
                clips: [
                    {
                        asset: {
                            type: "video",
                            src: "https://shotstack-assets.s3-ap-southeast-2.amazonaws.com/footage/ocean.mp4"
                        },
                        start: 0,
                        length: 10,
                        effect: "zoomIn"
                    }
                ]
            },
            output: {
                format: "mp4",
                resolution: "sd"
            }
        };

        $.ajax({
            url: '/edit-video',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                console.log('Edited video URL:', response.url);
            },
            error: function(error) {
                console.error('Error:', error);
            }
        });
    });
    </script>
</body>
</html>