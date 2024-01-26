<?php
session_start();
include('classes/databaseConnection.class.php');

// if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] == 1) {

?>
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


</head>

<body class="hold-transition layout-top-nav">
  <div class="wrapper">
    <?php include('assets/includes/top_menu.php'); ?>
    <div class="content-wrapper">
      <div class="content">

        <?php if (isset($_REQUEST['rid'])) { ?>
          <div class="container-fluid" style="width:45%;">
            <div class="card card-outline card-success">
              <div class="card-header">
                <div class="row">
                  <div class="col-sm-8">
                    <h5>CBN Collections</h5>
                  </div>
                  <div class="col-sm-4">
                    <a href="view.php" class="btn btn-secondary float-right">Back</a>
                  </div>
                </div>
              </div>
          <div class="card-body">
            <?php echo getSpecificRecord($_REQUEST['rid']); ?>
          </div>
        <?php } else { ?>
          <div class="container-fluid" style="width:85%;">
            <div class="card card-outline card-success">
              <div class="card-header">
                <div class="row">
                  <div class="col-sm-8">
                    <h5>CBN Collections</h5>
                  </div>
                  <div class="col-sm-4">
                    <a href="upload_file.php" class="btn btn-info float-right">Create New</a>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="card-body">
                    <table id="grids" class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th></th>
                          <th>Account Number</th>
                          <th>Report Date</th>
                          <th>Statement Date</th>
                          <th>Opening Balance</th>
                          <th>Closing Balance</th>
                          <th>Debit(s)</th>
                          <th>Collection</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php echo getCollectionRecords(); ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>


  <script src="assets/datatables/jquery.dataTables.min.js"></script>
  <script src="assets/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="assets/datatables/dataTables.buttons.min.js"></script>

  <script>
    $(function() {
      $("#grids").DataTable({
        "paging": true,
        "lengthChange": false,
        "ordering": true,
        "searching": true,
        "info": true,
        "autoWidth": true,
        "responsive": true,
        // "buttons": ["excel", "pdf", "colvis"]
      }).buttons().container().appendTo('#grids_wrapper .col-md-6:eq(0)');
    });
  </script>

</body>
<?php //}   
?>

<?php

function getCollectionRecords()
{
  $rtn = '';
  try {
    $db = new connectDatabase();
    if ($db->isLastQuerySuccessful()) {
      $con = $db->connect();

      $sql = "SELECT cid,crd,cad,can,obal,cbal,debtot FROM cbn_collection_test ORDER BY can ASC, cad DESC";
      $stmt = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
      $stmt->execute();
      $stmt->setFetchMode(PDO::FETCH_ASSOC);

      foreach ($stmt->fetchAll() as $row) {
        $rID = $row['cid'];
        $diff = (floatval($row['cbal']) + floatval($row['debtot'])) - floatval($row['obal']);

        $rtn .= '<tr><td><a href="view.php?rid=' . $rID . '"><i class="fas fa-money-check" title="Details" style="color:red;"></i></a></td><td>'
          . $row['can'] . '</td><td>' . $row['crd'] . '</td>'
          . '<td>' . $row['cad'] . '</td><td style="text-align: right;">' . number_format($row['obal'], 2) . '</td>'
          . '<td style="text-align: right;">' . number_format($row['cbal'], 2)
          . '</td><td style="text-align: right;">' . number_format($row['debtot'], 2)
          . '</td><td style="text-align: right;">' . number_format($diff, 2) . '</td></tr>';
      }
    } else {
      trigger_error($db->connectionError(), E_USER_NOTICE);
    }
    $db->closeConnection();
  } catch (Exception $e) {
    trigger_error($e->getMessage(), E_USER_NOTICE);
  }
  return ($rtn == '') ? '<tr><td colspan="7" style="color:red;text-align:center;"><b>No Collections Record Available</b></td></tr>' : $rtn;
}


function getSpecificRecord($rid)
{
  $rtn = '';
  try {
    $db = new connectDatabase();
    if ($db->isLastQuerySuccessful()) {
      $con = $db->connect();

      $sql = "SELECT cid,crd,cad,can,obal,cbal,deb,debtot FROM cbn_collection_test WHERE cid=:rid";
      $stmt = $con->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
      $stmt->bindparam(":rid", $rid, PDO::PARAM_INT);

      $stmt->execute();
      $stmt->setFetchMode(PDO::FETCH_ASSOC);

      foreach ($stmt->fetchAll() as $row) {
        $rID = $row['cid'];
        $diff = (floatval($row['cbal']) + floatval($row['debtot'])) - floatval($row['obal']);
        $dt = new DateTime($row['cad']);

        $rtn .= '<div class="row"><div class="col-sm-6"><div class="form-group">';
        $rtn .= '<label for="can">Account Number </label><input type="text" class="form-control" name="can" readonly value="' . $row['can'] . '">';
        $rtn .= '<label for="crd">Report Date and Time </label><input type="text" class="form-control" name="crd" readonly value="' . $row['crd'] . '">';
        $rtn .= '<label for="cad">Account Date </label><input type="text" class="form-control" name="cad" readonly value="' . $dt->format('d M Y') . '">';
        $rtn .= '</div></div>';

        $rtn .= '<div class="col-sm-6"><div class="form-group">';
        $rtn .= '<label for="obal">Opening Balance </label><input type="text" style="text-align: right;" class="form-control" name="obal" readonly value="' . number_format($row['obal'], 2) . '">';
        $rtn .= '<label for="cbal">Closing Balance </label><input type="text" style="text-align: right;" class="form-control" name="cbal" readonly value="' . number_format($row['cbal'], 2) . '">';
        $rtn .= '<label for="debtot">Debit Total </label><input type="text" style="text-align: right;" class="form-control" name="debtot" readonly value="' . number_format($row['debtot'], 2) . '">';
        $rtn .= '</div></div>';

        $rtn .= '<div class="col-sm-12"><div class="form-group">';
        $rtn .= '<label for="deb">Debit Entry(s)</label>';
        $rtn .= '<textarea class="form-control" rows="2" name="deb" readonly>' . $row['deb'] . '</textarea></div></div></div>';
      }
    } else {
      trigger_error($db->connectionError(), E_USER_NOTICE);
    }
    $db->closeConnection();
  } catch (Exception $e) {
    trigger_error($e->getMessage(), E_USER_NOTICE);
  }

  return ($rtn == '') ? '<span style="color:red;text-align:center;"><b>No Collections Record for this ID</b></span></div>' : $rtn;
}
