<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image to S3</title>
</head>
<body>
    <img src="https://gmt-s3.s3.ap-southeast-1.amazonaws.com/file_65ee71638faba.png" alt="Hình ảnh">
    <form action="{{route('file.upload')}}" method="post" enctype="multipart/form-data">
        @csrf
        <label for="image">Chọn hình ảnh:</label>
        <input type="file" name="image" id="image">
        <button type="submit">Tải lên</button>
    </form>
</body>
</html>
