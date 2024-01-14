<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF File Upload</title>
</head>
<body>

<form action="readerbots.php" method="post" enctype="multipart/form-data">
    <label for="pdfFile">Choose a PDF file:</label>
    <input type="file" name="pdfFile" id="pdfFile" accept=".pdf">
    <button type="submit" name="submit">Upload</button>
</form>

</body>
</html>
