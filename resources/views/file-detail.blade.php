<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Show Image</title>
</head>
<body>
    @if (isset($imageUrl))
        <img src="{{ $imageUrl }}" alt="Image">
    @else
        <p>No valid image URL available.</p>
    @endif
</body>
</html>
