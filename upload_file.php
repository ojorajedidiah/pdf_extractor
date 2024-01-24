<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Collections</title>
  <link rel="stylesheet" href="assets/css/all.min.css">
  <link rel="stylesheet" href="assets/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/css/jquery-ui.css">

  <script src="assets/js/jquery-3.6.0.js"></script>
  <script src="assets/js/jquery-ui.js"></script>

  <style>
    .custom-file-input {
      width: 450px;
    }

    .btn-primary {
      margin-left: 15px;
    }
  </style>

</head>

<body class="hold-transition layout-top-nav">
  <!-- <div id="app"> -->
  <div class="wrapper">
    <?php include('assets/includes/top_menu.php'); ?>
    <div class="content-wrapper">
      <div class="content">
        <div class="container-fluid" style="width:85%;">
          <div class="row" style="width:70%;">
            <div class="col-sm-12">
              <div class="form-group">
                <form action="" method="post" enctype="multipart/form-data">
                  <label for="pdfFile">Choose a PDF file:</label>
                  <div class="input-group">
                    <div class="custom-file">
                      <input type="file" class="custom-file-input" name="pdfFile" id="pdfFile" accept=".pdf">
                      <label class="custom-file-label" for="pdfFile"></label>
                    </div>
                    <div class="input-group-append">
                      <button class="btn btn-primary" type="submit" name="submit">Upload</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12" style="width:75%; margin-left:20px;">
              <?php if (isset($_POST['submit'])) {
                include('read.php');
              } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script type="application/javascript">
    $('input[type="file"]').change(function(e) {
      var fileName = e.target.files[0].name;
      $('.custom-file-label').html(fileName);
    });
  </script>

</body>

</html>