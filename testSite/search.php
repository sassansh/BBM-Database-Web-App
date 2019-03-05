<!DOCTYPE html>
<html>
<head>
  <?php
    session_start();
    require_once ('FileMaker.php');
    require_once ('partials/header.php');
    require_once ('functions.php');

    // echo "FM_FILE: $FM_FILE <br>
    //       FM_HOST: $FM_HOST <br>
    //       FM_USER: $FM_USER <br>
    //       FM_PASS: $FM_PASS <br>";

    $layouts = $fm->listLayouts();

    if (FileMaker::isError($layouts)) {
      $_SESSION['error'] = $layouts->getMessage();
      header('Location: error.php');
      exit;
    }

    $layout = $layouts[0];

    foreach ($layouts as $l) {
      //get current database name
      $page = substr($_SERVER['REQUEST_URI'], strrpos($_SERVER['REQUEST_URI'], '=') + 1);
      if ($page == 'mi') {
        if (strpos($l, 'search') !== false) {
          $layout = $l;
          break;
        }
      }
      else if (strpos($l, 'search') !== false) {
        $layout = $l;
      }
    }
    $fmLayout = $fm->getLayout($layout);
    $layoutFields = $fmLayout->listFields();
  ?>
</head>
<body class="container-fluid" onunload="">
 <?php require_once ('partials/navbar.php'); ?>
 <div class ="row">
  <div id="form" class = "col-sm-4"  >
  <form action="render.php" method="get" id = "submit-form">
    <div class="form-group">
      <input type="text" name="Database" style="display:none;" 
      value=<?php if (isset($_GET['Database'])) echo htmlspecialchars($_GET['Database']); ?>>
    </div>
    <?php foreach ($layoutFields as $rf) {
      if ($rf === 'SortNum') continue; ?>
    <div class="row">
      <div class="col">
        <label style="position:relative; top:6px" for="field-<?php echo $rf?>">
          <?php echo htmlspecialchars(formatField($rf)) ?>
        </label>
      </div>
      <div class="col">
        <input type="text" id="field-<?php echo $rf?>" 
          name="<?php echo htmlspecialchars($rf) ?>"
          class="form-control">
      </div>
    </div> 
    <?php } ?>
    <div class = "col-sm-4" style="position:relative; top:8px">
      <input id="form" class="btn btn-primary" type="button" value="Submit" onclick="Process(clearURL())">    
    </div>
  </form>
  </div>
  <div id="legend" class="border col-sm-5 offset-sm-2" style="position:relative; top:6px; padding-top:14px"> 
      <header style="padding-bottom:12px"> Search Operators </header>
      <div class="row">
        <div class="col-sm-1" >
         =    <br>
        ==   <br>
        &lt> <br>
        !    <br>
        <    <br>
        <=   <br>
        >    <br>
        >=  <br>
        ...  <br>
        //  <br>
        ?   <br>
        @    <br>
        #    <br>
        *   <br>
        \   <br>
        ""  <br>
        *""    <br>
        </div>
        <div class="col-sm-11">
       match a whole word (or match empty) <br> 
       match entire field exactly <br>
       find records that do NOT contain the value specified<br>
       find duplicate values <br>
       find records with values less than to the one specified <br>
       find records with values less than or equal to the one specified<br>
       find records with values greater than to the one specified <br>
       find records with values greater than or equal to the one specified <br>
       find records with values in a range (Ex. 10...20)<br>
       find records with today's date <br>
       find records invalid date and time <br>       
       match any one character <br>
       match any digit <br>
       match zero or more characters <br>
       escape next character<br>
       match phrase from word start<br>
       match phrase from anywhere
        </div>
        </div>       
   </div>
   </div>



  <?php require_once("partials/footer.php");?>
  <script src="js/process.js"> </script>
</body>
</html>
