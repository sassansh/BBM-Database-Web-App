<div class="container">

<?php
require_once ('FileMaker.php');
require_once ('db.php');
require_once ('partials/header.php');
require_once ('functions.php');
?>

<html>
  <body>

  <?php
  // Check if layout exists, and get fields of layout
  If(FileMaker::isError($result)){
    echo $result;
  } else {
    $recFields = $result->getFields();
  ?>

  <!-- construct table for given layout and fields -->
  <table class="table">
    <thead>
      <tr>
        <?php foreach($recFields as $i){?>
          <th scope="col"><?php echo $i ?></th>
        <?php }?>
      </tr>
    </thead>
    <tbody>
      <?php foreach($findAllRec as $i){
      ?>
      <tr>
        <?php foreach($recFields as $j){
          if($j == 'Accession No.'){
            echo '<td><a href=\'details.php?AccessionNo='.$i->getField($j).'\'>'.$i->getField($j).'</a></td>';
          } 
          else {
            echo '<td>'.$i->getField($j).'</td>';
          }
        }?>
      </tr>
      <?php }?>
    </tbody>
  </table>
    
  <?php
  }
  ?>

  </body>
</html>
</div>