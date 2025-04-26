<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Result | Property Valuation Calculator</title>

  <!-- BOOTSTRAP CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

  <!-- FONT AWESOME CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- EXTERNAL CSS -->
  <link rel="stylesheet" type="text/css" href="./style.css">

</head>

<body>
  <div class="page-wrapper">
    <!-- Bannner Section -->
    <div class="main-hero">
      <div class="main-inner-wrapper">
        <div class="container">
          <div class="row d-flex">
            <div class="col-lg-12 cols-md-12 cols-sm-12">
              <div class="main-content">
                <div class="main-content-wrapper">
                  <div class="row">
                    <div class="col-lg-12 cols-md-12 cols-sm-12">
                      <div class="hdb-val-card">
                        <h3 class="hdb-val-title"><strong>FREE Home Valuation Report</strong></h3>
                        <div class="hdb-val-content">
                            <p>
                              Expect a call from us soon with a complimentary consultation for <span id="full-address-result"><?= json_encode($_GET['full_address'] ?? '') ?></span>. Get a clear picture of your HDB unit <span id="unit-result"><?= json_encode($_GET['unit'] ?? '') ?></span> selling price with no obligations.
                            </p>
                            <ul>
                              <li>Recent rentals nearby</li>
                              <li>Highest transactions</li>
                              <li>Last 3 months report</li>
                              <li>Potential selling price</li>
                              <li>X-Value estimate</li>
                              <li>Nearby HDB comparison</li>
                          </ul>
                          <p>
                              Get market trends analysis to help you plan ahead, whether selling now or in the future.
                          </p>
                        </div>
                      </div>
                    </div>

                    <div class="col-lg-12 cols-md-12 cols-sm-12 mt-4">
                      <h1>Resale Flat Prices</h1>
                    </div>

                    <div class="col-lg-12 cols-md-12 cols-sm-12 mobile-p  mt-4">
                      <div class="upper-result">
                        <h2>Search Results</h2>

                        <div class="form-group-wrapper mt-5">
                          <div class="form-group">
                            <input type="text" name="project" class="form-input" value="<?= $_GET['project'] ?? 'N/A'; ?>" disabled>
                          </div>
                          <div class="form-group">
                            <input type="text" name="block" class="form-input" value="<?= $_GET['block'] ?? 'N/A'; ?>" disabled>
                          </div>
                        </div>

                        <div class="form-group-wrapper">
                          <div class="form-group">
                            <input type="text" name="floor" class="form-input" value="<?= $_GET['floor'] ?? 'N/A'; ?>" disabled>
                          </div>
                          <div class="form-group">
                            <input type="text" name="unit" class="form-input" value="<?= $_GET['unit_val'] ?? 'N/A'; ?>" disabled>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-12 cols-md-12 cols-sm-12 mobile-p  mt-4">
                      <div class="upper-result">
                        <!-- <div class="row border-bottom">
                          <div class="col-lg-4 cols-md-4 cols-sm-6">
                            <h2 style="border-bottom: none;">Search Table</h2>
                          </div>
                          <div class="col-lg-4 cols-md-4 cols-sm-6">
                            <div class="flat-type-input">
                              <select name="flat_type" class="form-selectt">
                                <option value="">Select entries</option>
                                <option value="1 ROOM">1</option>
                                <option value="3 ROOM">3</option>
                                <option value="4 ROOM">4</option>
                                <option value="6 ROOM">6</option>
                                <option value="8 ROOM">8</option>
                                <option value="10 ROOM">10</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-lg-4 cols-md-4 cols-sm-12">
                            <form action="" class="search-container">
                              <button type="submit"><i class="fa fa-search"></i></button>
                              <input type="text" placeholder="Search..." name="search" class="form-input">
                            </form>
                          </div>
                        </div> -->

                        <table id="condo-table">
                          <thead>
                              <tr>
                                <th>Date of Sales</th>
                                <th>Project Name</th>
                                <th>Street Name</th>
                                <th>Discrict</th>
                                <th>Market Segment</th>
                                <th>Tenure</th>
                                <th>Type of Sale</th>
                                <th>Floor Level</th>
                                <th>Area (Sqft)</th>
                                <th>Sale Price (S$)</th>
                            </tr>
                          </thead>
                          <tbody>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- BOOTSTRAP SCRIPT -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
    crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.3/js/standalone/selectize.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
      $(document).ready(function () {
          $('.dataTables_filter label input').attr('placeholder', ' ⌕ search here');
          $('.basic').select2();

          // Initial table load
          load_data({});
          
          $('#search-field').on('keyup', function () {
              reloadTable();
          });

          function reloadTable() {
              $('#condo-table').DataTable().destroy();
              load_data({
                  search: $('#search-field').val(),
              });
          }

          function load_data(params = {}) {
              $('#condo-table').DataTable({
                  processing: true,
                  serverSide: true,
                  ajax: {
                      url: '../util_project_data.php',
                      data: params
                  },
                  columns: [
                      { data: 'contractDate' },
                      { data: 'project' },
                      { data: 'street' },
                      { data: 'district' },
                      { data: 'marketSegment' },
                      { data: 'tenure' },
                      { data: 'typeOfSale' },
                      { data: 'floorRange' },
                      { data: 'area' },
                      { data: 'price' }
                  ],
                  order: [[0, "desc"]]
              });
          }
      });
  </script>
</body>

</html>